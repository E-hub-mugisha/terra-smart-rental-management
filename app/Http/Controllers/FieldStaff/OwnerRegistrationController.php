<?php

namespace App\Http\Controllers\FieldStaff;

use App\Http\Controllers\Admin\PropertyOwnerController;
use App\Models\PropertyOwner;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Field staff register property owners with the same rules and account
 * creation as admins, but cannot set verification or account status:
 * every field registration starts "pending review" for an admin to verify.
 */
class OwnerRegistrationController extends PropertyOwnerController
{
    public function create(): View
    {
        return view('field-staff.register-owner', ['owner' => new PropertyOwner()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['verification_status' => 'pending', 'status' => 'active']);

        return parent::store($request);
    }

    protected function afterStoreUrl(PropertyOwner $owner): string
    {
        return route('field-staff.registrations');
    }

    protected function registered(PropertyOwner $owner): void
    {
        app(Notifier::class)->fieldRegistration(request()->user(), 'owner', $owner->name, route('admin.owners.show', $owner));
    }
}
