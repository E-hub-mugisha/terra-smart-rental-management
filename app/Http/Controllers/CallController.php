<?php

namespace App\Http\Controllers;

use App\Models\SubscriberCall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** In-platform (browser) calls between a Terra admin and a subscriber. */
class CallController extends Controller
{
    public function join(Request $request, SubscriberCall $call): View
    {
        $user = $request->user();
        abort_unless($call->channel === 'browser' && $call->room, 404);
        abort_unless($user->isAdmin() || $call->user_id === $user->id, 403);

        // Invitations expire after 12 hours.
        abort_if($call->called_at->lt(now()->subHours(12)), 410, 'This call has ended.');

        return view('calls.join', [
            'call' => $call->load(['subscriber:id,name', 'caller:id,name']),
            'isAdmin' => $user->isAdmin(),
            'displayName' => $user->name,
        ]);
    }

    /** The admin closes the call and records how it went. */
    public function end(Request $request, SubscriberCall $call): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() && $call->channel === 'browser', 403);

        $data = $request->validate([
            'outcome' => ['required', Rule::in(array_keys(SubscriberCall::OUTCOMES))],
            'minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $call->update([
            'outcome' => $data['outcome'],
            'duration_seconds' => isset($data['minutes']) ? (int) $data['minutes'] * 60 : max(0, (int) $call->called_at->diffInSeconds(now())),
            'notes' => $data['notes'] ?? $call->notes,
        ]);

        return redirect()->route('admin.subscribers.show', $call->user_id)->with('success', 'Call saved to the contact history.');
    }
}
