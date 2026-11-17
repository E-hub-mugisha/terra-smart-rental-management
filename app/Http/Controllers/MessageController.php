<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\Messaging\ContactDirectory;
use App\Services\Notifier;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Tenant–landlord communication (brief §13): direct conversations and
 * property announcements with document/photo attachments, kept as a
 * permanent history linked to the relevant property and unit.
 */
class MessageController extends Controller
{
    private const ATTACHMENT_RULES = ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt'];

    public function __construct(
        private ContactDirectory $directory,
        private PropertyAudience $audience,
        private Notifier $notifier,
    ) {
    }

    public function index(Request $request)
    {
        return $this->inbox($request, null);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $participant = $this->participantOrAbort($conversation, $request->user());

        $participant->markReadUpTo(Message::where('conversation_id', $conversation->id)->max('id'));
        $this->markThreadNotificationsRead($request->user(), $conversation);

        return $this->inbox($request, $conversation);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $contacts = $this->directory->for($user);
        $properties = $this->directory->announceableProperties($user);

        return view('messages.create', [
            'contacts' => $contacts,
            'properties' => $properties,
            'mode' => $request->query('mode') === 'announcement' && $properties->isNotEmpty() ? 'announcement' : 'direct',
            'preselect' => (int) $request->query('to'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isAnnouncement = $request->input('type') === Conversation::TYPE_ANNOUNCEMENT;

        $data = $request->validate([
            'type' => ['required', Rule::in([Conversation::TYPE_DIRECT, Conversation::TYPE_ANNOUNCEMENT])],
            'recipient_id' => [Rule::requiredIf(! $isAnnouncement), 'nullable', 'integer'],
            'property_id' => [Rule::requiredIf($isAnnouncement), 'nullable', 'integer'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => self::ATTACHMENT_RULES,
        ]);

        if ($isAnnouncement) {
            $propertyId = (int) $data['property_id'];
            abort_unless($this->directory->announceableProperties($user)->contains('id', $propertyId), 403);

            $tenants = $this->audience->tenantUsersForProperty($propertyId);
            if ($tenants->isEmpty()) {
                return back()->withInput()->withErrors(['property_id' => 'This property has no tenants with a Terra account yet.']);
            }

            // Staff can discuss the announcement; tenants receive it read-only.
            $participants = $tenants->mapWithKeys(fn ($u) => [$u->id => false])
                ->union($this->audience->staffUsers($propertyId)->mapWithKeys(fn ($u) => [$u->id => true]))
                ->put($user->id, true);

            $context = ['property_id' => $propertyId, 'unit_id' => null];
        } else {
            $contact = $this->directory->find($user, (int) $data['recipient_id']);
            if (! $contact) {
                return back()->withInput()->withErrors(['recipient_id' => 'You can only message people connected to your properties or lease.']);
            }

            $participants = collect([$user->id => true, $contact['user']->id => true]);
            $context = ['property_id' => $contact['property_id'], 'unit_id' => $contact['unit_id']];
        }

        [$conversation, $message] = DB::transaction(function () use ($user, $data, $isAnnouncement, $participants, $context, $request) {
            $conversation = Conversation::create([
                'type' => $isAnnouncement ? Conversation::TYPE_ANNOUNCEMENT : Conversation::TYPE_DIRECT,
                'subject' => $data['subject'],
                'property_id' => $context['property_id'],
                'unit_id' => $context['unit_id'],
                'created_by' => $user->id,
                'last_message_at' => now(),
            ]);

            foreach ($participants as $userId => $canReply) {
                $conversation->participants()->create([
                    'user_id' => $userId,
                    'can_reply' => $canReply,
                    'last_read_at' => $userId === $user->id ? now() : null,
                ]);
            }

            $message = $this->createMessage($conversation, $user, $data['body'] ?? null, $request->file('attachments', []));
            $conversation->participants()->where('user_id', $user->id)->update(['last_read_message_id' => $message->id]);

            return [$conversation, $message];
        });

        $this->notifier->newMessage($message, $this->recipients($conversation, $user));

        return redirect()
            ->route('messages.show', $conversation)
            ->with('success', $isAnnouncement ? 'Announcement sent to ' . ($participants->count() - 1) . ' people.' : 'Message sent.');
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $participant = $this->participantOrAbort($conversation, $user);
        abort_unless($participant->can_reply, 403, 'Replies are turned off for this announcement.');

        $data = $request->validate([
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => self::ATTACHMENT_RULES,
        ]);

        $message = DB::transaction(function () use ($conversation, $user, $data, $request, $participant) {
            $message = $this->createMessage($conversation, $user, $data['body'] ?? null, $request->file('attachments', []));
            $conversation->update(['last_message_at' => $message->created_at]);
            // A new message brings the thread back into everyone's inbox.
            $conversation->participants()->update(['archived_at' => null]);
            $participant->update(['last_read_message_id' => $message->id, 'last_read_at' => now()]);

            return $message;
        });

        $this->notifier->newMessage($message, $this->recipients($conversation, $user));

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $message->id,
                'html' => view('messages._message', ['message' => $message->load(['sender', 'attachments']), 'me' => $user])->render(),
            ]);
        }

        return redirect()->route('messages.show', $conversation)->withFragment('m' . $message->id);
    }

    /** Lightweight polling for new messages in an open thread (low-bandwidth friendly). */
    public function poll(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $participant = $this->participantOrAbort($conversation, $user);

        $messages = $conversation->messages()
            ->where('id', '>', (int) $request->query('after'))
            ->with(['sender', 'attachments'])
            ->limit(50)
            ->get();

        if ($messages->isNotEmpty()) {
            $participant->markReadUpTo($messages->last()->id);
            $this->markThreadNotificationsRead($user, $conversation);
        }

        return response()->json([
            'last_id' => $messages->last()?->id,
            'html' => $messages->map(fn ($m) => view('messages._message', ['message' => $m, 'me' => $user])->render())->implode(''),
        ]);
    }

    public function attachment(Request $request, MessageAttachment $attachment)
    {
        $attachment->loadMissing('message.conversation');
        $this->participantOrAbort($attachment->message->conversation, $request->user());

        $disk = Storage::disk(MessageAttachment::DISK);
        abort_unless($disk->exists($attachment->path), 404);

        $inline = $attachment->isImage() || $attachment->mime_type === 'application/pdf';

        return $inline && ! $request->boolean('download')
            ? $disk->response($attachment->path, $attachment->original_name, ['Content-Type' => $attachment->mime_type])
            : $disk->download($attachment->path, $attachment->original_name);
    }

    public function archive(Request $request, Conversation $conversation)
    {
        $participant = $this->participantOrAbort($conversation, $request->user());
        $participant->update(['archived_at' => $participant->archived_at ? null : now()]);

        return redirect()->route('messages.index')
            ->with('success', $participant->archived_at ? 'Conversation archived.' : 'Conversation moved back to your inbox.');
    }

    /* ------------------------------------------------------------------ */

    private function inbox(Request $request, ?Conversation $active)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'inbox');
        $search = trim((string) $request->query('q'));

        $conversations = Conversation::forUser($user)
            ->with(['users:id,name,role', 'latestMessage.sender:id,name', 'property:id,name', 'unit:id,unit_number',
                'participants' => fn ($q) => $q->where('user_id', $user->id)])
            ->when($filter === 'unread', fn ($q) => $q->unreadFor($user))
            ->when($filter === 'announcements', fn ($q) => $q->where('type', Conversation::TYPE_ANNOUNCEMENT))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id)
                ->when($filter === 'archived', fn ($a) => $a->whereNotNull('archived_at'), fn ($a) => $a->whereNull('archived_at')))
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('subject', 'like', "%{$search}%")
                ->orWhereHas('messages', fn ($m) => $m->where('body', 'like', "%{$search}%"))
                ->orWhereHas('users', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        $thread = null;
        if ($active) {
            $active->load(['users:id,name,role', 'property:id,name', 'unit:id,unit_number', 'creator:id,name']);
            $thread = $active->messages()->with(['sender:id,name,role', 'attachments'])->reorder('id', 'desc')->limit(200)->get()->reverse()->values();
        }

        return view('messages.index', [
            'conversations' => $conversations,
            'active' => $active,
            'thread' => $thread,
            'me' => $user,
            'myParticipant' => $active?->participantFor($user),
            'filter' => $filter,
            'search' => $search,
            'unreadTotal' => Conversation::unreadFor($user)->count(),
            'canAnnounce' => $this->directory->announceableProperties($user)->isNotEmpty(),
        ]);
    }

    private function createMessage(Conversation $conversation, User $user, ?string $body, array $files): Message
    {
        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'body' => $body !== null ? trim($body) : null,
        ]);

        foreach ($files as $file) {
            $message->attachments()->create([
                'path' => $file->store('message-attachments/' . now()->format('Y/m'), MessageAttachment::DISK),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return $message;
    }

    private function recipients(Conversation $conversation, User $sender): Collection
    {
        return $conversation->users()->where('users.id', '!=', $sender->id)->get();
    }

    private function participantOrAbort(Conversation $conversation, ?User $user): ConversationParticipant
    {
        $participant = $user ? $conversation->participantFor($user) : null;
        abort_unless($participant, 404);

        return $participant;
    }

    private function markThreadNotificationsRead(User $user, Conversation $conversation): void
    {
        $user->unreadNotifications()
            ->where('data', 'like', '%"conversation_id":' . $conversation->id . '}%')
            ->update(['read_at' => now()]);
    }
}
