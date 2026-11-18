<?php

namespace App\Http\Controllers\FieldStaff;

use App\Http\Controllers\Controller;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** The field staff workspace: register owners and managers, see your own registrations. */
class PortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $monthStart = now()->startOfMonth();

        $owners = PropertyOwner::where('created_by', $user->id);
        $managers = PropertyManager::where('created_by', $user->id);

        return view('field-staff.dashboard', [
            'member' => $user->fieldStaff,
            'stats' => [
                'owners' => (clone $owners)->count(),
                'managers' => (clone $managers)->count(),
                'month' => (clone $owners)->where('created_at', '>=', $monthStart)->count() + (clone $managers)->where('created_at', '>=', $monthStart)->count(),
                'today' => (clone $owners)->whereDate('created_at', today())->count() + (clone $managers)->whereDate('created_at', today())->count(),
                'verified' => (clone $owners)->where('verification_status', 'verified')->count(),
            ],
            'recent' => $this->registrationsFor($user->id)->take(8),
        ]);
    }

    public function registrations(Request $request): View
    {
        return view('field-staff.registrations', ['registrations' => $this->registrationsFor($request->user()->id)]);
    }

    public function password(): View
    {
        return view('field-staff.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill(['password' => Hash::make($request->input('password'))])->save();

        return redirect()->route('field-staff.dashboard')->with('success', 'Your password has been changed.');
    }

    /** Only the contact details a field agent needs; no finance or property data. */
    private function registrationsFor(int $userId)
    {
        $owners = PropertyOwner::where('created_by', $userId)->latest()->get(['id', 'owner_number', 'name', 'email', 'phone', 'owner_type', 'district', 'verification_status', 'created_at'])
            ->map(fn ($o) => ['type' => 'Owner', 'name' => $o->name, 'number' => $o->owner_number, 'email' => $o->email, 'phone' => $o->phone,
                'place' => $o->district, 'status' => ucfirst((string) $o->verification_status), 'created_at' => $o->created_at]);

        $managers = PropertyManager::where('created_by', $userId)->with('user:id,name,email')->latest()->get()
            ->map(fn ($m) => ['type' => 'Manager', 'name' => $m->user?->name, 'number' => $m->manager_number, 'email' => $m->user?->email, 'phone' => $m->phone,
                'place' => $m->district, 'status' => ucfirst((string) $m->status), 'created_at' => $m->created_at]);

        return $owners->concat($managers)->sortByDesc('created_at')->values();
    }
}
