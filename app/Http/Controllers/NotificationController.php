<?php

namespace App\Http\Controllers;

use App\Notifications\TerraAlert;
use Illuminate\Http\Request;

/** In-app notification centre and per-user channel preferences (brief §12). */
class NotificationController extends Controller
{
    public const CATEGORIES = [
        'rent' => ['Rent & invoices', ['rent_due', 'rent_overdue', 'invoice']],
        'payment' => ['Payments', ['payment']],
        'lease' => ['Leases & inspections', ['lease', 'inspection']],
        'maintenance' => ['Maintenance', ['maintenance']],
        'application' => ['Applications', ['application', 'vacancy']],
        'document' => ['Documents', ['document']],
        'message' => ['Messages & calls', ['message', 'announcement', 'call']],
        'subscription' => ['Subscription & accounts', ['subscription', 'registration']],
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $category = $request->query('category');

        $notifications = $user->notifications()
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when(isset(self::CATEGORIES[$category]), function ($q) use ($category) {
                $q->where(function ($inner) use ($category) {
                    foreach (self::CATEGORIES[$category][1] as $cat) {
                        $inner->orWhere('data', 'like', '%"category":"' . $cat . '"%');
                    }
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'category' => $category,
            'unreadCount' => $user->unreadNotifications()->count(),
            'categories' => self::CATEGORIES,
            'smsAvailable' => (bool) config('services.sms.enabled'),
        ]);
    }

    /** Mark one notification read and follow its link. */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Only follow links that point back into this application.
        if ($url && str_starts_with($url, url('/'))) {
            return redirect()->to($url);
        }

        return redirect()->route('notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->delete();

        return back();
    }

    public function updatePreferences(Request $request)
    {
        $request->user()->update([
            'notify_email' => $request->boolean('notify_email'),
            'notify_sms' => $request->boolean('notify_sms'),
        ]);

        return back()->with('success', 'Notification preferences saved.');
    }

    public static function iconFor(array $data): string
    {
        return $data['icon'] ?? TerraAlert::iconFor($data['category'] ?? '');
    }
}
