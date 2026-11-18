<?php

namespace App\Http\Controllers\FieldStaff;

use App\Http\Controllers\Admin\PropertyManagerController;
use App\Models\PropertyManager;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Field staff register property managers; the account always starts active. */
class ManagerRegistrationController extends PropertyManagerController
{
    public function create(): View
    {
        return view('field-staff.register-manager', ['manager' => new PropertyManager()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['status' => 'active']);

        return parent::store($request);
    }

    protected function afterStoreUrl(PropertyManager $manager): string
    {
        return route('field-staff.registrations');
    }

    protected function registered(PropertyManager $manager): void
    {
        app(Notifier::class)->fieldRegistration(request()->user(), 'manager', $manager->user?->name ?? 'a manager', route('admin.managers.show', $manager));
    }
}
