<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FieldStaffAccountCreated;
use App\Models\AuditLog;
use App\Models\FieldStaff;
use App\Models\PropertyManager;
use App\Models\PropertyOwner;
use App\Models\User;
use App\Rules\InternationalPhone;
use App\Support\IssuedCredentials;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/** Admin management of field staff: accounts, activity and registrations per person. */
class FieldStaffController extends Controller
{
    public function index(Request $request): View
    {
        $monthStart = now()->startOfMonth();

        $staff = FieldStaff::query()
            ->with('user:id,name,email,phone')
            ->withCount([
                'registeredOwners as owners_count',
                'registeredManagers as managers_count',
                'registeredOwners as owners_month_count' => fn ($q) => $q->where('created_at', '>=', $monthStart),
                'registeredManagers as managers_month_count' => fn ($q) => $q->where('created_at', '>=', $monthStart),
            ])
            ->when(isset(FieldStaff::STATUSES[$request->query('status')]), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('q')) . '%';
                $q->where(fn ($w) => $w->where('staff_number', 'like', $term)->orWhere('region', 'like', $term)->orWhere('district', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->orderBy('status')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $staffIds = FieldStaff::pluck('user_id');
        $stats = [
            'total' => FieldStaff::count(),
            'active' => FieldStaff::where('status', 'active')->count(),
            'owners' => PropertyOwner::whereIn('created_by', $staffIds)->count(),
            'managers' => PropertyManager::whereIn('created_by', $staffIds)->count(),
            'this_month' => PropertyOwner::whereIn('created_by', $staffIds)->where('created_at', '>=', $monthStart)->count()
                + PropertyManager::whereIn('created_by', $staffIds)->where('created_at', '>=', $monthStart)->count(),
            'active_today' => FieldStaff::where('last_active_at', '>=', today())->count(),
        ];

        return view('admin.field-staff.index', compact('staff', 'stats'));
    }

    public function create(): View
    {
        return view('admin.field-staff.form', ['member' => new FieldStaff(['status' => 'active']), 'user' => new User()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $password = $this->chosenPassword($request) ?? IssuedCredentials::generatePassword();

        try {
            $member = DB::transaction(function () use ($data, $password, $request) {
                $user = new User();
                $user->forceFill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'role' => 'field_staff',
                ])->save();

                return FieldStaff::create([
                    'user_id' => $user->id,
                    'staff_number' => FieldStaff::nextNumber(),
                    'phone' => $data['phone'] ?? null,
                    'region' => $data['region'] ?? null,
                    'district' => $data['district'] ?? null,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'The field staff account could not be created. Nothing was saved.');
        }

        $emailed = $request->boolean('send_email', true) && $this->sendCredentials($member, $password);
        IssuedCredentials::flash($member->user->name, $member->user->email, $password, $emailed, 'field staff');

        return redirect()->route('admin.field-staff.show', $member)->with('success', match (true) {
            $emailed => "Field staff account created. Login details were emailed to {$member->user->email}.",
            $request->boolean('send_email', true) => 'Field staff account created, but the email could not be sent. Share the login details below directly.',
            default => 'Field staff account created. Share the login details below with the staff member.',
        });
    }

    public function show(Request $request, FieldStaff $fieldStaff): View
    {
        $fieldStaff->load(['user', 'creator:id,name']);
        $userId = $fieldStaff->user_id;

        $owners = PropertyOwner::where('created_by', $userId)->with('user:id,email')->latest()->get()
            ->map(fn ($o) => ['type' => 'Owner', 'name' => $o->name, 'number' => $o->owner_number, 'email' => $o->email, 'phone' => $o->phone,
                'status' => $o->verification_status, 'created_at' => $o->created_at, 'url' => route('admin.owners.show', $o), 'user_id' => $o->user_id]);
        $managers = PropertyManager::where('created_by', $userId)->with('user:id,name,email')->latest()->get()
            ->map(fn ($m) => ['type' => 'Manager', 'name' => $m->user?->name, 'number' => $m->manager_number, 'email' => $m->user?->email, 'phone' => $m->phone,
                'status' => $m->status, 'created_at' => $m->created_at, 'url' => route('admin.managers.show', $m), 'user_id' => $m->user_id]);
        $registrations = $owners->concat($managers)->sortByDesc('created_at')->values();

        // Registrations per month, last 6 months.
        $months = collect(range(5, 0))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonths($i)->format('Y-m') => 0])->all();
        foreach ($registrations as $r) {
            $key = $r['created_at']?->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]++;
            }
        }

        $activity = class_exists(AuditLog::class)
            ? AuditLog::where('user_id', $userId)->latest()->limit(40)->get()
            : collect();

        return view('admin.field-staff.show', [
            'member' => $fieldStaff,
            'registrations' => $registrations,
            'months' => $months,
            'activity' => $activity,
        ]);
    }

    public function edit(FieldStaff $fieldStaff): View
    {
        return view('admin.field-staff.form', ['member' => $fieldStaff, 'user' => $fieldStaff->user]);
    }

    public function update(Request $request, FieldStaff $fieldStaff): RedirectResponse
    {
        $data = $this->validated($request, $fieldStaff);
        $newPassword = $this->chosenPassword($request); // only when "set a new password" was chosen

        DB::transaction(function () use ($fieldStaff, $data, $newPassword) {
            $account = ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null];
            if ($newPassword) {
                $account['password'] = Hash::make($newPassword);
            }
            $fieldStaff->user->forceFill($account)->save();
            $fieldStaff->update([
                'phone' => $data['phone'] ?? null,
                'region' => $data['region'] ?? null,
                'district' => $data['district'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $message = 'Field staff details updated.';
        if ($newPassword) {
            $emailed = $request->boolean('send_email', true) && $this->sendCredentials($fieldStaff->fresh('user'), $newPassword, true);
            IssuedCredentials::flash($fieldStaff->user->name, $fieldStaff->user->email, $newPassword, $emailed, 'field staff');
            $message .= $emailed ? ' The new password was emailed.' : ' The new password is shown below.';
        }

        return redirect()->route('admin.field-staff.show', $fieldStaff)->with('success', $message);
    }

    public function toggle(FieldStaff $fieldStaff): RedirectResponse
    {
        $fieldStaff->update(['status' => $fieldStaff->isActive() ? 'suspended' : 'active']);

        return back()->with('success', $fieldStaff->isActive() ? 'Account reactivated.' : 'Account suspended. The staff member can no longer sign in.');
    }

    /**
     * "Resend credentials": a new password is generated (the old one cannot be
     * read back), emailed to the staff member and shown once to the admin.
     */
    public function resendCredentials(FieldStaff $fieldStaff): RedirectResponse
    {
        $password = IssuedCredentials::generatePassword();
        $fieldStaff->user->forceFill(['password' => Hash::make($password)])->save();

        $emailed = $this->sendCredentials($fieldStaff, $password, true);
        IssuedCredentials::flash($fieldStaff->user->name, $fieldStaff->user->email, $password, $emailed, 'field staff');

        return $emailed
            ? back()->with('success', "New login details were emailed to {$fieldStaff->user->email}.")
            : back()->with('error', 'A new password was set, but the email could not be sent. Share the login details below directly.');
    }

    /** The admin chooses the password (or generates one) and decides whether to email it. */
    public function setPassword(Request $request, FieldStaff $fieldStaff): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'max:72'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $password = (string) $request->input('password');
        $fieldStaff->user->forceFill(['password' => Hash::make($password)])->save();

        $emailed = $request->boolean('send_email', true) && $this->sendCredentials($fieldStaff, $password, true);
        IssuedCredentials::flash($fieldStaff->user->name, $fieldStaff->user->email, $password, $emailed, 'field staff');

        return back()->with('success', $emailed ? 'Password changed and emailed to the staff member.' : 'Password changed. Share the login details below.');
    }

    /* ------------------------------------------------------------------ */

    /** The password typed by the admin when "set password manually" is chosen, otherwise null. */
    private function chosenPassword(Request $request): ?string
    {
        return $request->input('password_mode') === 'manual' ? (string) $request->input('password') : null;
    }

    private function validated(Request $request, ?FieldStaff $member = null): array
    {
        Phone::normalizeRequest($request, ['phone']);

        $request->validate([
            'password_mode' => ['nullable', Rule::in(['auto', 'manual', 'keep'])],
            'password' => ['nullable', 'required_if:password_mode,manual', 'string', Password::min(8)->letters()->numbers(), 'max:72'],
            'send_email' => ['nullable', 'boolean'],
        ], ['password.required_if' => 'Type the password, or choose to generate one automatically.']);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member?->user_id)],
            'phone' => ['required', 'string', 'max:30', new InternationalPhone()],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(array_keys(FieldStaff::STATUSES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['email.unique' => 'A user account with this email already exists.']);
    }

    private function sendCredentials(FieldStaff $member, string $password, bool $reset = false): bool
    {
        try {
            Mail::to($member->user->email)->send(new FieldStaffAccountCreated(
                staffName: $member->user->name,
                staffNumber: $member->staff_number,
                email: $member->user->email,
                password: $password,
                loginUrl: route('login'),
                isReset: $reset,
            ));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

}
