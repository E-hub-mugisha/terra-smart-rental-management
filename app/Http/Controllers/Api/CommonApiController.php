<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MaintenanceRequest;
use App\Models\Message;
use App\Models\Property;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Notifications, messaging, technician jobs and public listings over the API. */
class CommonApiController extends Controller
{
    /* ---------------------------- Notifications ---------------------------- */

    public function notifications(Request $request)
    {
        return $request->user()->notifications()
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->latest()->paginate(20)
            ->through(fn ($n) => ['id' => $n->id, 'read' => $n->read_at !== null, 'created_at' => $n->created_at->toIso8601String()] + (array) $n->data);
    }

    public function readNotification(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->noContent();
    }

    /* ------------------------------ Messaging ------------------------------ */

    public function conversations(Request $request)
    {
        $user = $request->user();

        return Conversation::forUser($user)->with(['latestMessage.sender:id,name', 'users:id,name', 'participants' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByDesc('last_message_at')->paginate(20)
            ->through(fn ($c) => [
                'id' => $c->id, 'type' => $c->type, 'subject' => $c->subject, 'title' => $c->titleFor($user),
                'last_message' => $c->latestMessage ? ['body' => $c->latestMessage->body, 'sender' => $c->latestMessage->sender?->name, 'at' => $c->latestMessage->created_at->toIso8601String()] : null,
                'unread' => $c->latestMessage && $c->latestMessage->user_id !== $user->id && $c->latestMessage->id > (int) $c->participants->first()?->last_read_message_id,
                'can_reply' => (bool) $c->participants->first()?->can_reply,
            ]);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $participant = $conversation->participantFor($request->user());
        abort_unless($participant, 404);

        $messages = $conversation->messages()->with(['sender:id,name', 'attachments'])
            ->when($request->filled('after'), fn ($q) => $q->where('id', '>', (int) $request->query('after')))
            ->reorder('id', 'desc')->limit(100)->get()->reverse()->values();

        $participant->markReadUpTo($messages->max('id'));

        return ['data' => $messages->map(fn ($m) => [
            'id' => $m->id, 'body' => $m->body, 'sender' => $m->sender?->name, 'mine' => $m->user_id === $request->user()->id,
            'at' => $m->created_at->toIso8601String(),
            'attachments' => $m->attachments->map(fn ($a) => ['name' => $a->original_name, 'size' => $a->size, 'url' => route('messages.attachments.show', $a)]),
        ])];
    }

    public function sendMessage(Request $request, Conversation $conversation, Notifier $notifier)
    {
        $user = $request->user();
        $participant = $conversation->participantFor($user);
        abort_unless($participant, 404);
        abort_unless($participant->can_reply, 403, 'Replies are turned off for this announcement.');

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $message = DB::transaction(function () use ($conversation, $user, $data, $participant) {
            $message = $conversation->messages()->create(['user_id' => $user->id, 'body' => trim($data['body'])]);
            $conversation->update(['last_message_at' => $message->created_at]);
            $conversation->participants()->update(['archived_at' => null]);
            $participant->update(['last_read_message_id' => $message->id, 'last_read_at' => now()]);

            return $message;
        });
        $notifier->newMessage($message, $conversation->users()->where('users.id', '!=', $user->id)->get());

        return response()->json(['data' => ['id' => $message->id, 'at' => $message->created_at->toIso8601String()]], 201);
    }

    /* --------------------------- Technician jobs --------------------------- */

    public function jobs(Request $request)
    {
        $provider = $request->user()->serviceProvider;
        abort_unless($provider && $provider->status === 'active', 403);

        return MaintenanceRequest::where('service_provider_id', $provider->id)
            ->when($request->query('status') === 'open', fn ($q) => $q->whereNotIn('status', ['resolved', 'closed', 'rejected']))
            ->with(['unit.building.property', 'tenant'])->latest('id')->paginate(20)
            ->through(fn ($m) => [
                'id' => $m->id, 'number' => $m->request_number, 'title' => $m->title, 'description' => $m->description, 'priority' => $m->priority, 'status' => $m->status,
                'property' => $m->unit?->building?->property?->name, 'unit' => $m->unit?->unit_number,
                'latitude' => $m->unit?->building?->property?->latitude, 'longitude' => $m->unit?->building?->property?->longitude,
                'tenant' => ['name' => $m->tenant?->full_name, 'phone' => $m->tenant?->phone], 'scheduled_at' => $m->scheduled_at?->toIso8601String(),
            ]);
    }

    public function updateJob(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        // Same rules as the web technician portal.
        $job = app(\App\Http\Controllers\MaintenanceWorkController::class)->applyTechnicianUpdate($request, $maintenanceRequest);

        return ['data' => ['id' => $job->id, 'status' => $job->status, 'scheduled_at' => $job->scheduled_at?->toIso8601String()]];
    }

    /* --------------------------- Public listings --------------------------- */

    /** Map-based property discovery (brief §19) and marketplace feed (§21). */
    public function listings(Request $request)
    {
        $data = $request->validate([
            'district' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'available' => ['nullable', 'boolean'],
            // Bounding box: south,west,north,east
            'bbox' => ['nullable', 'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?,-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'],
        ]);

        $query = Property::where('status', 'active')
            ->whereHas('units', fn ($q) => $q->where('units.status', '!=', 'inactive'))
            ->withCount(['units as units_available' => fn ($q) => $q->where('units.status', 'available')])
            ->withMin(['units as rent_from' => fn ($q) => $q->where('units.status', 'available')], 'rent_amount')
            ->with('images')
            ->when($data['district'] ?? null, fn ($q, $d) => $q->where('district', $d))
            ->when($data['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sector', 'like', "%{$s}%")->orWhere('district', 'like', "%{$s}%")))
            ->when($request->boolean('available'), fn ($q) => $q->whereHas('units', fn ($u) => $u->where('units.status', 'available')));

        if (! empty($data['bbox'])) {
            [$south, $west, $north, $east] = array_map('floatval', explode(',', $data['bbox']));
            $query->whereBetween('latitude', [$south, $north])->whereBetween('longitude', [$west, $east]);
        }

        return ['data' => $query->orderBy('name')->limit(500)->get()->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'location' => $p->location_line, 'district' => $p->district,
            'latitude' => $p->latitude !== null ? (float) $p->latitude : null, 'longitude' => $p->longitude !== null ? (float) $p->longitude : null,
            'units_available' => $p->units_available, 'rent_from' => $p->rent_from !== null ? (float) $p->rent_from : null,
            'image' => $p->images->first() ? asset('storage/' . $p->images->first()->image_path) : null,
            'url' => route('listings.show', $p),
        ])];
    }
}
