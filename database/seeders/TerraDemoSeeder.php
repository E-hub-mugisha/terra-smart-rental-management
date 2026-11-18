<?php

namespace Database\Seeders;

use App\Models\AgentCommission;
use App\Models\Building;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\FieldStaff;
use App\Models\Floor;
use App\Models\Inspection;
use App\Models\Lease;
use App\Models\LeaseRequest;
use App\Models\LeaseTemplate;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestActivity;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\PropertyManager;
use App\Models\PropertyManagerAssignment;
use App\Models\PropertyOwner;
use App\Models\PropertyUserRole;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\RentPaymentAllocation;
use App\Models\ServiceProvider;
use App\Models\SubscriberCall;
use App\Models\SubscriptionContract;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\User;
use App\Services\ContractBuilder;
use App\Services\InspectionService;
use App\Support\FileUpload;
use App\Support\Phone;
use Carbon\Carbon;
use Database\Seeders\Support\DemoImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Terra demo data — realistic Rwandan properties, people and activity for
 * launches, reviews and training.
 *
 *   php artisan db:seed --class=TerraDemoSeeder
 *
 * Safety:
 *  - Refuses to run in production unless ALLOW_DEMO_SEED=true is set.
 *  - Runs once: if the demo admin already exists it stops (use a fresh demo
 *    database, e.g. `php artisan migrate:fresh` then seed, to rebuild).
 *  - Model events are switched off while seeding, so no emails, SMS or
 *    queued notifications are produced. Demo accounts use the reserved
 *    ".test" domain, which can never receive mail.
 *
 * Every demo account's password is DEMO_PASSWORD below. Change or delete the
 * accounts before exposing a demo site publicly.
 */
class TerraDemoSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Terra@Demo2026';
    public const DOMAIN = 'terra-demo.test';

    private Carbon $today;
    private string $passwordHash;
    private DemoImages $images;
    private int $seq = 0;
    private int $leaseSeq = 0;

    /** @var array<string, User> */
    private array $users = [];
    /** @var array<string, Property> */
    private array $properties = [];
    /** @var array<string, Unit> keyed "CODE:UNIT" */
    private array $units = [];
    /** @var array<string, Lease> keyed "CODE:UNIT" */
    private array $leases = [];
    /** @var array<string, ServiceProvider> */
    private array $providers = [];
    private array $interiorPool = [];

    public function run(): void
    {
        if (app()->isProduction() && ! filter_var(env('ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->error('Refusing to seed demo data in production. Set ALLOW_DEMO_SEED=true on a dedicated demo server to override.');

            return;
        }

        if (User::where('email', 'admin@' . self::DOMAIN)->exists()) {
            $this->command?->warn('Terra demo data is already present (admin@' . self::DOMAIN . '). Use a fresh database to rebuild it.');

            return;
        }

        $this->today = Carbon::today();
        $this->passwordHash = Hash::make(self::DEMO_PASSWORD);
        $this->images = new DemoImages();
        mt_srand(2026);

        Model::withoutEvents(function () {
            DB::transaction(function () {
                $this->people();
                $this->serviceProviders();
                $this->portfolio();
                $this->tenancies();
                $this->expenses();
                $this->maintenance();
                $this->inspections();
                $this->documents();
                $this->contractsAndRequests();
                $this->communication();
                $this->rolesAndCommissions();
                $this->subscriptions();
                $this->recentActivity();
            });
        });

        $this->printCredentials();
    }

    /* ================================================================
     |  People
     * ================================================================ */

    private function people(): void
    {
        $this->users['admin'] = $this->user('Terra Administrator', 'admin', 'admin');

        // Owners: individuals with logins, and companies.
        $owners = [
            ['kayitesi', 'Kayitesi Diane', 'individual', null, '0788 412 903', 'Gasabo'],
            ['rugamba', 'Rugamba Jean Pierre', 'company', 'Rugamba Investments Ltd', '0788 301 556', 'Gasabo'],
            ['ndayisaba', 'Ndayisaba Félix', 'individual', null, '0783 220 418', 'Kicukiro'],
            ['murekatete', 'Murekatete Solange', 'individual', null, '0789 667 012', 'Musanze'],
            ['gasana', 'Gasana Eric', 'company', 'Gasana Real Estate Ltd', '0722 504 881', 'Gasabo'],
        ];
        foreach ($owners as $i => [$key, $name, $type, $company, $phone, $district]) {
            $user = $this->user($name, $key === 'kayitesi' ? 'owner' : 'owner.' . $key, 'owner');
            $this->users['owner_' . $key] = $user;
            PropertyOwner::forceCreate([
                'user_id' => $user->id, 'owner_number' => sprintf('OWN-D%03d', $i + 1), 'owner_type' => $type,
                'name' => $company ?? $name, 'email' => $user->email, 'phone' => $this->phone($phone),
                'national_id' => $type === 'individual' ? $this->nationalId(1970 + $i * 4, $i % 2 === 0 ? 'f' : 'm') : null,
                'company_name' => $company, 'country' => 'Rwanda', 'province' => $district === 'Musanze' ? 'Northern Province' : 'Kigali City',
                'district' => $district, 'address' => $district . ', Rwanda', 'verification_status' => 'verified', 'status' => 'active',
                'created_by' => $this->users['admin']->id,
            ]);
        }

        $managers = [
            ['mugisha', 'Mugisha Patrick', 'manager', '0788 554 210', 'Senior Property Manager', 'Gasabo'],
            ['uwamahoro', 'Uwamahoro Grace', 'manager.grace', '0785 112 349', 'Property Manager', 'Gasabo'],
            ['nkurunziza', 'Nkurunziza Jean Bosco', 'manager.bosco', '0782 908 771', 'Regional Property Manager (North & West)', 'Musanze'],
        ];
        foreach ($managers as $i => [$key, $name, $email, $phone, $title, $district]) {
            $user = $this->user($name, $email, 'manager');
            $this->users['mgr_' . $key] = $user;
            PropertyManager::forceCreate([
                'user_id' => $user->id, 'manager_number' => sprintf('MGR-D%03d', $i + 1), 'phone' => $this->phone($phone),
                'job_title' => $title, 'employment_type' => 'full_time', 'start_date' => $this->today->copy()->subYears(2 + $i)->toDateString(),
                'status' => 'active', 'country' => 'Rwanda', 'province' => $district === 'Musanze' ? 'Northern Province' : 'Kigali City',
                'district' => $district, 'address' => $district, 'created_by' => $this->users['admin']->id,
            ]);
        }

        $this->users['accountant'] = $this->user('Ishimwe Clarisse', 'accountant', 'accountant');
        $this->users['agent'] = $this->user('Kwizera Samuel', 'agent', 'agent');
    }

    private function serviceProviders(): void
    {
        $list = [
            ['plumbing', 'Kigali Plumbing Services Ltd', 'Habyarimana Théoneste', '0788 640 215', ['plumbing'], 'Gasabo', true, 'technician'],
            ['electric', 'Umucyo Electrical Works', 'Ndahiro Claude', '0789 331 870', ['electrical', 'appliances'], 'Kicukiro', true, 'technician.umucyo'],
            ['cleaning', 'Isuku Cleaning Co.', 'Mukeshimana Vestine', '0786 207 554', ['cleaning', 'landscaping'], 'Gasabo', true, 'technician.isuku'],
            ['paint', 'Amabara Painters & Finishes', 'Rukundo Alexis', '0723 118 640', ['painting', 'construction'], 'Nyarugenge', false, null],
            ['security', 'Intore Security Services', 'Gatete Bernard', '0788 902 331', ['security'], 'Gasabo', true, null],
            ['north', 'Musanze Home Repairs', 'Niyonsaba Eric', '0785 440 912', ['plumbing', 'electrical', 'other'], 'Musanze', false, null],
        ];
        foreach ($list as [$key, $company, $contact, $phone, $cats, $district, $verified, $login]) {
            $user = $login ? $this->user($contact, $login, 'technician') : null;
            $this->providers[$key] = ServiceProvider::forceCreate([
                'user_id' => $user?->id, 'company_name' => $company, 'contact_name' => $contact, 'phone' => $this->phone($phone),
                'email' => $user?->email ?? Str::slug($company) . '@' . self::DOMAIN, 'categories' => $cats, 'district' => $district,
                'status' => 'active', 'verified_at' => $verified ? $this->today->copy()->subMonths(6) : null,
                'verified_by' => $verified ? $this->users['admin']->id : null,
                'notes' => 'Call-out fee RWF 10,000; quotes shared on WhatsApp before work starts.',
                'created_by' => $this->users['mgr_mugisha']->id,
            ]);
        }
    }

    /* ================================================================
     |  Properties, buildings, units, photos
     * ================================================================ */

    private function portfolio(): void
    {
        // Shared interior photos, reused across units.
        foreach (['living', 'bedroom', 'kitchen', 'bathroom', 'living', 'bedroom', 'kitchen', 'living'] as $i => $room) {
            $this->interiorPool[] = $this->images->interior(FileUpload::UNIT_IMAGES . "/demo-{$room}-{$i}.jpg", 100 + $i, $room);
        }

        foreach ($this->propertySpecs() as $spec) {
            $owner = PropertyOwner::where('owner_number', $spec['owner'])->firstOrFail();
            $property = Property::forceCreate([
                'property_owner_id' => $owner->id, 'property_code' => $spec['code'], 'upi' => $spec['upi'], 'name' => $spec['name'],
                'property_type' => $spec['type'], 'description' => $spec['description'], 'amenities' => $spec['amenities'],
                'utilities' => $spec['utilities'], 'country' => 'Rwanda', 'province' => $spec['province'], 'district' => $spec['district'],
                'sector' => $spec['sector'], 'cell' => $spec['cell'], 'village' => $spec['village'], 'address' => $spec['address'],
                'latitude' => $spec['lat'], 'longitude' => $spec['lng'], 'year_built' => $spec['year'], 'status' => 'active',
                'created_by' => $this->users['admin']->id,
            ]);
            $this->properties[$spec['code']] = $property;

            foreach ($spec['managers'] as $m) {
                $manager = PropertyManager::where('user_id', $this->users['mgr_' . $m]->id)->firstOrFail();
                PropertyManagerAssignment::forceCreate([
                    'property_id' => $property->id, 'property_manager_id' => $manager->id,
                    'start_date' => $this->today->copy()->subMonths(18)->toDateString(), 'status' => 'active',
                    'assigned_by' => $this->users['admin']->id,
                ]);
            }

            // Exterior photos.
            for ($p = 0; $p < 3; $p++) {
                $path = $this->images->exterior(FileUpload::PROPERTY_IMAGES . "/demo-{$spec['code']}-{$p}.jpg", crc32($spec['code']) % 1000 + $p, $spec['style'], $spec['photo_floors']);
                $property->images()->forceCreate([
                    'image_path' => $path, 'caption' => [$spec['name'], 'Street view', 'Compound and parking'][$p],
                    'is_primary' => $p === 0, 'sort_order' => $p,
                ]);
            }

            $unitCount = 0;
            foreach ($spec['buildings'] as $b) {
                $building = Building::forceCreate([
                    'property_id' => $property->id, 'building_code' => $b['code'], 'name' => $b['name'],
                    'description' => $b['description'] ?? null, 'number_of_floors' => count($b['floors']), 'status' => 'active',
                ]);
                foreach ($b['floors'] as $floorNo => $unitsOnFloor) {
                    $floor = Floor::forceCreate([
                        'building_id' => $building->id, 'floor_number' => $floorNo,
                        'name' => $floorNo === 0 ? 'Ground floor' : $this->ordinal($floorNo) . ' floor', 'status' => 'active',
                    ]);
                    foreach ($unitsOnFloor as $u) {
                        $unit = $this->makeUnit($spec, $building, $floor, $u);
                        $this->units[$spec['code'] . ':' . $unit->unit_number] = $unit;
                        $unitCount++;
                    }
                }
            }

            $property->forceFill(['total_buildings' => count($spec['buildings']), 'total_units' => $unitCount])->save();
        }
    }

    private function makeUnit(array $spec, Building $building, Floor $floor, array $u): Unit
    {
        [$number, $kind, $beds, $baths, $sqm, $rent] = $u;
        $features = $spec['features'][$kind] ?? $spec['features']['default'];
        $unit = Unit::forceCreate([
            'building_id' => $building->id, 'floor_id' => $floor->id, 'unit_number' => $number,
            'unit_type' => $kind === 'shop' || $kind === 'office' ? $kind : ($kind === 'villa' ? 'house' : ($beds === 0 ? 'studio' : 'apartment')),
            'bedrooms' => $beds, 'bathrooms' => $baths, 'area_sqm' => $sqm, 'rent_amount' => $rent,
            'deposit_amount' => $rent * ($kind === 'shop' || $kind === 'office' ? 3 : 2), 'currency' => 'RWF',
            'description' => $this->unitDescription($kind, $beds, $sqm, $spec['name']),
            'features' => $features, 'status' => 'available',
            'electricity_meter' => '0' . (14000000000 + crc32($spec['code'] . $number) % 899999999),
            'water_meter' => 'WASAC-' . strtoupper(substr($spec['district'], 0, 3)) . '-' . (100000 + crc32($number . $spec['code']) % 899999),
            'inventory' => $kind === 'shop' || $kind === 'office'
                ? "1 x Roller shutter\n1 x Distribution board\n2 x Ceiling lights"
                : "1 x Cooker (4 burners)\n1 x Fridge\n{$beds} x Bed frame\n1 x Sofa set\n1 x Dining table\n4 x Dining chairs\n1 x Water heater",
        ]);

        foreach ([0, 1] as $i) {
            $unit->images()->forceCreate([
                'image_path' => $this->interiorPool[(crc32($number) + $i * 3) % count($this->interiorPool)],
                'caption' => ['Living area', 'Bedroom'][$i], 'is_primary' => $i === 0, 'sort_order' => $i,
            ]);
        }

        return $unit;
    }

    /* ================================================================
     |  Tenants, applications, leases, invoices, payments
     * ================================================================ */

    private function tenancies(): void
    {
        $specials = [
            // "CODE:UNIT" => [scenario, tenant key]
            'TRD-KCY:A201' => ['demo_tenant', 'aline'],
            'TRD-KCY:B102' => ['awaiting_tenant', 'kevin'],
            'TRD-KMH:301' => ['pending_activation', 'brian'],
            'TRD-KCK:402' => ['draft', 'divine'],
            'TRD-MSZ:201' => ['terminated', 'fabrice'],
            'TRD-KCY:A301' => ['expiring', null],
            'TRD-KMH:102' => ['expiring', null],
            'TRD-KCK:201' => ['expiring', null],
        ];
        $vacant = ['TRD-KCY:A401', 'TRD-KCY:A402', 'TRD-KCK:103', 'TRD-KCK:404', 'TRD-RMR:G04', 'TRD-RBV:202', 'TRD-NYR:V5', 'TRD-KMH:303'];
        $underMaintenance = ['TRD-KCY:B301'];
        $arrears = ['TRD-KCY:A102', 'TRD-KCK:302', 'TRD-RMR:104', 'TRD-MSZ:102', 'TRD-KMH:201', 'TRD-RBV:101'];

        $names = $this->tenantNames();
        $businesses = $this->businessTenants();
        $n = 0;
        $b = 0;
        $nextTenant = function (?string $key, Unit $unit) use (&$n, &$b, $names, $businesses) {
            if ($key && isset($this->namedTenants()[$key])) {
                return $this->tenant(...$this->namedTenants()[$key]);
            }
            if (in_array($unit->unit_type, ['shop', 'office'], true)) {
                $row = $businesses[$b++ % count($businesses)];

                return $this->tenant($row[0], $row[1], $row[2], null, true);
            }
            $row = $names[$n++ % count($names)];

            return $this->tenant($row[0], $row[1], $row[2], null, $n % 3 !== 0);
        };

        foreach ($this->units as $key => $unit) {
            [$scenario, $tenantKey] = $specials[$key] ?? [null, null];
            if (in_array($key, $vacant, true)) {
                continue;
            }
            if (in_array($key, $underMaintenance, true)) {
                $unit->forceFill(['status' => 'maintenance'])->save();
                continue;
            }

            $scenario ??= 'active';
            $tenant = $nextTenant($tenantKey, $unit);

            match ($scenario) {
                'demo_tenant' => $this->activeLease($key, $unit, $tenant, months: 8, term: 24, unpaidCurrent: true),
                'expiring' => $this->activeLease($key, $unit, $tenant, months: 11, term: 12, endInDays: ['TRD-KCY:A301' => 24, 'TRD-KMH:102' => 52, 'TRD-KCK:201' => 38][$key]),
                'awaiting_tenant' => $this->openLease($key, $unit, $tenant, 'pending_tenant_acceptance'),
                'pending_activation' => $this->openLease($key, $unit, $tenant, 'pending_activation'),
                'draft' => $this->openLease($key, $unit, $tenant, 'draft'),
                'terminated' => $this->terminatedLease($key, $unit, $tenant),
                default => $this->activeLease($key, $unit, $tenant, months: 2 + mt_rand(0, 16), term: [12, 24][mt_rand(0, 1)],
                    arrearsMonths: in_array($key, $arrears, true) ? 1 + mt_rand(0, 1) : 0),
            };
        }

        // Applications waiting on vacant units.
        $appTenants = [
            ['Uwera Christine', 'f', '0788 220 671', 'Employed — Bank of Kigali', 'TRD-KCY:A401', 'pending'],
            ['Otieno Brian Kip', 'm', '0790 118 552', 'Employed — NGO (East African Community)', 'TRD-KCK:103', 'pending'],
            ['Tuyishime Yves', 'm', '0782 663 904', 'Self-employed — tour operator', 'TRD-RBV:202', 'under_review'],
            ['Mbabazi Carine', 'f', '0785 334 190', 'Student — University of Rwanda', 'TRD-KCY:A402', 'rejected'],
        ];
        foreach ($appTenants as [$name, $g, $phone, $job, $unitKey, $status]) {
            $tenant = $this->tenant($name, $g, $phone, null, false);
            $unit = $this->units[$unitKey];
            TenantApplication::forceCreate([
                'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'application_number' => $this->num('APP'),
                'application_date' => $this->today->copy()->subDays(mt_rand(1, 9))->toDateString(),
                'desired_move_in_date' => $this->today->copy()->addDays(mt_rand(10, 30))->toDateString(),
                'number_of_occupants' => mt_rand(1, 4), 'offered_rent' => $unit->rent_amount, 'currency' => 'RWF',
                'employment_status' => $job, 'status' => $status,
                'reviewed_by' => in_array($status, ['rejected', 'under_review'], true) ? $this->users['mgr_mugisha']->id : null,
                'reviewed_at' => $status === 'rejected' ? $this->today->copy()->subDays(1) : null,
                'decision_notes' => $status === 'rejected' ? 'Income documents did not cover the monthly rent. Suggested a studio at Kicukiro Sunrise instead.' : null,
            ]);
        }
    }

    private function activeLease(string $key, Unit $unit, Tenant $tenant, int $months, int $term, bool $unpaidCurrent = false, int $arrearsMonths = 0, ?int $endInDays = null): Lease
    {
        $start = $this->today->copy()->subMonths($months)->startOfMonth();
        $end = $endInDays !== null ? $this->today->copy()->addDays($endInDays) : $start->copy()->addMonths($term)->subDay();
        if ($endInDays !== null) {
            $start = $end->copy()->addDay()->subMonths($term);
        }

        $lease = $this->lease($unit, $tenant, $start, $end, 'active', [
            'tenant_accepted_at' => $start->copy()->subDays(6), 'move_in_confirmed_at' => $start, 'activated_at' => $start,
        ]);
        $unit->forceFill(['status' => 'occupied'])->save();
        $this->leases[$key] = $lease;

        $this->depositInvoice($lease, paid: true);

        // Monthly rent from the start month to this month.
        $month = $start->copy()->startOfMonth();
        $monthsTotal = 0;
        $periods = [];
        while ($month->lte($this->today)) {
            $periods[] = $month->copy();
            $month->addMonth();
            $monthsTotal++;
        }
        foreach ($periods as $i => $periodStart) {
            $isCurrent = $periodStart->isSameMonth($this->today);
            $fromEnd = count($periods) - 1 - $i;
            $paid = match (true) {
                $isCurrent && $unpaidCurrent => 0.0,
                $arrearsMonths > 0 && $fromEnd < $arrearsMonths => ($fromEnd === $arrearsMonths - 1 && mt_rand(0, 1) ? 0.5 : 0.0),
                $isCurrent && $periodStart->copy()->day((int) $lease->rent_due_day)->isFuture() => (mt_rand(0, 2) ? 1.0 : 0.0),
                default => 1.0,
            };
            $this->rentInvoice($lease, $periodStart, $paid);
        }

        return $lease;
    }

    private function openLease(string $key, Unit $unit, Tenant $tenant, string $status): Lease
    {
        $start = $this->today->copy()->addDays($status === 'pending_activation' ? 0 : 14);
        $application = TenantApplication::forceCreate([
            'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'application_number' => $this->num('APP'),
            'application_date' => $this->today->copy()->subDays(12)->toDateString(), 'desired_move_in_date' => $start->toDateString(),
            'number_of_occupants' => 2, 'offered_rent' => $unit->rent_amount, 'currency' => 'RWF',
            'employment_status' => 'Employed', 'status' => 'approved', 'reviewed_by' => $this->managerFor($unit)->id,
            'reviewed_at' => $this->today->copy()->subDays(8), 'decision_notes' => 'References checked; employment confirmed.',
        ]);

        $lease = $this->lease($unit, $tenant, $start, $start->copy()->addYear()->subDay(), $status, [
            'tenant_application_id' => $application->id,
            'tenant_accepted_at' => $status === 'pending_activation' ? $this->today->copy()->subDays(3) : null,
        ]);
        $unit->forceFill(['status' => $status === 'draft' ? 'available' : 'reserved'])->save();
        $this->leases[$key] = $lease;

        if ($status === 'pending_activation') {
            $this->depositInvoice($lease, paid: true);
            $this->rentInvoice($lease, $start->copy()->startOfMonth(), 0.0, $start, $start->copy()->addMonthNoOverflow()->subDay(), 'Initial rent - ' . $lease->lease_number);
        }

        return $lease;
    }

    private function terminatedLease(string $key, Unit $unit, Tenant $tenant): Lease
    {
        $start = $this->today->copy()->subMonths(13)->startOfMonth();
        $termination = $this->today->copy()->subDays(20);
        $lease = $this->lease($unit, $tenant, $start, $start->copy()->addMonths(24)->subDay(), 'terminated', [
            'tenant_accepted_at' => $start->copy()->subDays(5), 'move_in_confirmed_at' => $start, 'activated_at' => $start,
            'termination_date' => $termination->toDateString(), 'termination_reason' => 'Tenant relocated to Kigali for a new job; gave 30 days\' notice.',
        ]);
        $this->leases[$key] = $lease;
        $this->depositInvoice($lease, paid: true);
        for ($m = $start->copy(); $m->lt($termination); $m->addMonth()) {
            $this->rentInvoice($lease, $m->copy(), 1.0);
        }
        $unit->forceFill(['status' => 'available'])->save();

        return $lease;
    }

    private function lease(Unit $unit, Tenant $tenant, Carbon $start, Carbon $end, string $status, array $extra = []): Lease
    {
        return Lease::forceCreate($extra + [
            'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'lease_number' => 'LSE-' . $start->format('Y') . '-' . str_pad((string) ++$this->leaseSeq, 4, '0', STR_PAD_LEFT),
            'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'monthly_rent' => $unit->rent_amount,
            'deposit_amount' => $unit->deposit_amount, 'currency' => 'RWF', 'rent_due_day' => 5, 'payment_frequency' => 'monthly',
            'status' => $status, 'terms' => 'Standard Terra residential terms. Rent payable by the 5th of each month via Terra (Mobile Money or bank transfer).',
            'created_at' => $start->copy()->subDays(10),
        ]);
    }

    private function depositInvoice(Lease $lease, bool $paid): void
    {
        if ((float) $lease->deposit_amount <= 0) {
            return;
        }
        $start = $lease->start_date;
        $invoice = $this->invoice($lease, 'deposit', $start, $start, $start->copy()->subDays(7), $start->copy()->subDays(2), (float) $lease->deposit_amount, 'Security deposit - ' . $lease->lease_number);
        if ($paid) {
            $this->pay($invoice, (float) $lease->deposit_amount, $start->copy()->subDays(mt_rand(2, 5)));
        }
    }

    private function rentInvoice(Lease $lease, Carbon $periodStart, float $paidShare, ?Carbon $from = null, ?Carbon $to = null, ?string $description = null): void
    {
        $from ??= $periodStart->copy()->max($lease->start_date);
        $to ??= $periodStart->copy()->endOfMonth()->min($lease->end_date);
        $due = $periodStart->copy()->day((int) $lease->rent_due_day)->max($lease->start_date);
        $amount = (float) $lease->monthly_rent;

        $invoice = $this->invoice($lease, 'rent', $from, $to, $periodStart->copy()->subDays(4)->max($lease->start_date->copy()->subDays(7)), $due, $amount,
            $description ?? 'Rent ' . $periodStart->format('F Y') . ' - Unit ' . $lease->unit?->unit_number);

        if ($paidShare > 0) {
            $date = $due->copy()->addDays(mt_rand(-4, 6));
            if ($date->gt($this->today)) {
                $date = $this->today->copy();
            }
            $this->pay($invoice, round($amount * $paidShare, -3), $date);
        }
    }

    private function invoice(Lease $lease, string $type, Carbon $from, Carbon $to, Carbon $issue, Carbon $due, float $amount, string $description): RentInvoice
    {
        return RentInvoice::forceCreate([
            'lease_id' => $lease->id, 'tenant_id' => $lease->tenant_id, 'unit_id' => $lease->unit_id, 'invoice_type' => $type,
            'invoice_number' => $this->num('INV', 6), 'billing_period_start' => $from->toDateString(), 'billing_period_end' => $to->toDateString(),
            'issue_date' => $issue->toDateString(), 'due_date' => $due->toDateString(), 'subtotal' => $amount, 'tax_amount' => 0,
            'total_amount' => $amount, 'amount_paid' => 0, 'balance_due' => $amount, 'currency' => 'RWF',
            'status' => $due->lt($this->today) ? 'overdue' : 'unpaid', 'description' => $description,
            'created_at' => $issue, 'updated_at' => $issue,
        ]);
    }

    private function pay(RentInvoice $invoice, float $amount, Carbon $date): void
    {
        $r = mt_rand(1, 100);
        [$method, $provider, $ref] = match (true) {
            $r <= 55 => ['mobile_money', 'MTN Mobile Money', 'MP' . $date->format('ymd') . '.' . mt_rand(1000, 9999) . '.' . strtoupper(Str::random(5))],
            $r <= 70 => ['mobile_money', 'Airtel Money', 'AM' . mt_rand(100000000, 999999999)],
            $r <= 95 => ['bank_transfer', ['Bank of Kigali', 'Equity Bank Rwanda', 'I&M Bank Rwanda', 'BPR Bank Rwanda'][mt_rand(0, 3)], 'FT' . $date->format('ymd') . mt_rand(10000, 99999)],
            default => ['cash', null, null],
        };

        $payment = RentPayment::forceCreate([
            'payment_number' => $this->num('PAY', 6), 'tenant_id' => $invoice->tenant_id, 'lease_id' => $invoice->lease_id, 'unit_id' => $invoice->unit_id,
            'recorded_by' => $this->managerFor($invoice->unit)->id, 'payment_method' => $method, 'provider' => $provider,
            'transaction_reference' => $ref ? $ref . '-' . $this->seq : null, 'amount' => $amount, 'currency' => 'RWF',
            'payment_date' => $date->toDateString(), 'status' => 'confirmed',
            'notes' => $method === 'cash' ? 'Cash received at the site office; receipt issued.' : null,
            'created_at' => $date, 'updated_at' => $date,
        ]);
        RentPaymentAllocation::forceCreate([
            'rent_payment_id' => $payment->id, 'rent_invoice_id' => $invoice->id, 'amount' => $amount, 'allocated_at' => $date->toDateString(),
        ]);

        $paid = min((float) $invoice->total_amount, $amount);
        $balance = max(0, (float) $invoice->total_amount - $paid);
        $invoice->forceFill([
            'amount_paid' => $paid, 'balance_due' => $balance,
            'status' => $balance <= 0 ? 'paid' : 'partially_paid',
        ])->save();
    }

    /* ================================================================
     |  Expenses, maintenance, inspections, documents
     * ================================================================ */

    private function expenses(): void
    {
        $vendors = [
            ['security', 'Guard services (2 guards, day & night)', 'Intore Security Services', 0.12],
            ['cleaning', 'Common-area cleaning', 'Isuku Cleaning Co.', 0.035],
            ['utilities', 'Common-area electricity (REG prepaid)', 'Rwanda Energy Group (REG)', 0.02],
            ['utilities', 'Water — common taps & garden', 'WASAC', 0.012],
            ['management_fee', 'Property management fee (8%)', 'Terra Property Management', 0.08],
        ];

        foreach ($this->properties as $code => $property) {
            $monthlyRent = (float) Unit::whereHas('building', fn ($q) => $q->where('property_id', $property->id))->where('status', 'occupied')->sum('rent_amount');
            for ($m = 5; $m >= 0; $m--) {
                $date = $this->today->copy()->subMonths($m)->startOfMonth()->addDays(mt_rand(2, 9));
                if ($date->gt($this->today)) {
                    continue;
                }
                foreach ($vendors as [$cat, $title, $vendor, $share]) {
                    PropertyExpense::forceCreate([
                        'property_id' => $property->id, 'recorded_by' => $this->managerFor($property)->id, 'expense_number' => $this->num('EXP'),
                        'category' => $cat, 'title' => $title . ' — ' . $date->format('M Y'), 'amount' => max(15000, round($monthlyRent * $share, -3)),
                        'currency' => 'RWF', 'expense_date' => $date->toDateString(),
                        'payment_method' => $cat === 'utilities' ? 'mobile_money' : 'bank_transfer', 'vendor_name' => $vendor,
                        'reference_number' => 'EXP-REF-' . mt_rand(10000, 99999), 'status' => 'paid',
                    ]);
                }
            }
            // Occasional repairs.
            PropertyExpense::forceCreate([
                'property_id' => $property->id, 'recorded_by' => $this->managerFor($property)->id, 'expense_number' => $this->num('EXP'),
                'category' => 'repairs', 'title' => ['Gate motor replacement', 'Roof gutter repairs after rainy season', 'Repainting stairwell', 'Water pump service'][crc32($code) % 4],
                'amount' => [380000, 245000, 520000, 165000][crc32($code) % 4], 'currency' => 'RWF',
                'expense_date' => $this->today->copy()->subMonths(2)->addDays(5)->toDateString(), 'payment_method' => 'bank_transfer',
                'vendor_name' => 'Amabara Painters & Finishes', 'reference_number' => 'INV-' . mt_rand(1000, 9999), 'status' => 'paid',
            ]);
        }
    }

    private function maintenance(): void
    {
        $cases = [
            ['TRD-KCY:A201', 'Water heater not heating', 'The water heater in the bathroom stopped heating two days ago. Cold water only.', 'plumbing', 'high', 'resolved', 'plumbing', 4, null],
            ['TRD-KCY:A102', 'Leaking kitchen tap', 'The kitchen tap drips constantly even when fully closed.', 'plumbing', 'normal', 'closed', 'plumbing', 9, 5],
            ['TRD-KCY:A202', 'Bedroom socket sparking', 'The socket next to the bed sparks when plugging in a charger.', 'electrical', 'emergency', 'in_progress', 'electric', 1, null],
            ['TRD-KMH:101', 'Blocked shower drain', 'Water collects in the shower and drains very slowly.', 'plumbing', 'normal', 'scheduled', 'plumbing', 2, null],
            ['TRD-KMH:202', 'Gate remote not working', 'My gate remote stopped opening the main gate.', 'security', 'low', 'open', null, 3, null],
            ['TRD-KCK:101', 'Cockroaches in the kitchen', 'Seeing cockroaches in the kitchen cupboards at night.', 'pest_control', 'normal', 'closed', 'cleaning', 30, 4],
            ['TRD-KCK:301', 'Ceiling stain after heavy rain', 'Brown stain spreading on the living room ceiling after last week\'s rain.', 'structural', 'high', 'in_progress', 'paint', 6, null],
            ['TRD-KCK:204', 'Broken window latch', 'The bedroom window latch is broken and the window will not lock.', 'other', 'normal', 'resolved', 'paint', 12, null],
            ['TRD-RMR:101', 'Shop roller shutter jammed', 'Front roller shutter jams halfway, cannot close the shop properly.', 'other', 'high', 'closed', 'electric', 20, 5],
            ['TRD-RMR:103', 'Office AC making noise', 'The wall air conditioner in the office is very loud.', 'appliance', 'low', 'open', null, 1, null],
            ['TRD-MSZ:101', 'No water pressure', 'Very low water pressure on the upper floor since Monday.', 'plumbing', 'high', 'scheduled', 'north', 2, null],
            ['TRD-RBV:102', 'Lights flickering', 'All the lights flicker in the evening.', 'electrical', 'normal', 'closed', 'north', 25, 3],
            ['TRD-NYR:V2', 'Garden needs trimming', 'The garden hedges are overgrown and blocking the path.', 'cleaning', 'low', 'closed', 'cleaning', 15, 5],
        ];

        foreach ($cases as $i => [$unitKey, $title, $desc, $cat, $priority, $status, $providerKey, $daysAgo, $rating]) {
            $lease = $this->leases[$unitKey] ?? null;
            if (! $lease) {
                continue;
            }
            $opened = $this->today->copy()->subDays($daysAgo)->setTime(9, 15);
            $provider = $providerKey ? $this->providers[$providerKey] : null;
            $tenantUser = $lease->tenant->user;
            $manager = $this->managerFor($lease->unit);
            $resolved = in_array($status, ['resolved', 'closed'], true) ? $opened->copy()->addDays(max(1, (int) ($daysAgo / 3)))->setTime(15, 30) : null;

            $request = MaintenanceRequest::forceCreate([
                'request_number' => $this->num('MR'), 'tenant_id' => $lease->tenant_id, 'unit_id' => $lease->unit_id, 'title' => $title,
                'description' => $desc, 'category' => $cat, 'priority' => $priority, 'status' => $status,
                'service_provider_id' => $provider?->id, 'assigned_to' => $provider?->user_id,
                'preferred_visit_at' => $opened->copy()->addDay()->setTime(10, 0),
                'scheduled_at' => in_array($status, ['scheduled', 'in_progress'], true) ? $this->today->copy()->addDays(1)->setTime(10, 0) : null,
                'resolved_at' => $resolved, 'resolution_notes' => $resolved ? ['Replaced the heating element and thermostat.', 'Replaced worn washer and cartridge.', 'Treated kitchen and bathroom; follow-up in 3 weeks.', 'Fitted new latch and lubricated hinges.', 'Replaced shutter spring and motor capacitor.', 'Loose neutral at the distribution board fixed.', 'Hedges trimmed and green waste removed.'][$i % 7] : null,
                'actual_cost' => $resolved ? [45000, 25000, 60000, 18000, 85000, 30000, 40000][$i % 7] : null,
                'tenant_confirmed_at' => $status === 'closed' ? $resolved?->copy()->addDay() : null, 'tenant_rating' => $status === 'closed' ? $rating : null,
                'created_by' => $tenantUser?->id ?? $manager->id, 'created_at' => $opened, 'updated_at' => $resolved ?? $opened,
            ]);

            $log = [['submitted', 'Request submitted by the tenant.', null, 'submitted', $tenantUser?->id, $opened]];
            if ($provider) {
                $log[] = ['assigned', 'Assigned to ' . $provider->company_name . '.', 'submitted', 'in_progress', $manager->id, $opened->copy()->addHours(3)];
            }
            if ($resolved) {
                $log[] = ['technician_update', 'Marked resolved by the technician.', 'in_progress', 'resolved', $provider?->user_id ?? $manager->id, $resolved];
            }
            if ($status === 'closed') {
                $log[] = ['tenant_confirmed', 'Tenant confirmed the repair (' . $rating . '/5).', 'resolved', 'closed', $tenantUser?->id, $resolved->copy()->addDay()];
            }
            foreach ($log as [$type, $text, $from, $to, $by, $at]) {
                MaintenanceRequestActivity::forceCreate([
                    'maintenance_request_id' => $request->id, 'user_id' => $by, 'activity_type' => $type, 'description' => $text,
                    'old_status' => $from, 'new_status' => $to, 'created_at' => $at, 'updated_at' => $at,
                ]);
            }
        }
    }

    private function inspections(): void
    {
        $service = app(InspectionService::class);
        $rate = function (Inspection $inspection, array $issues = []) {
            foreach ($inspection->items()->with('baseline')->get() as $item) {
                // Move-out items match move-in unless there is a specific issue.
                $condition = $issues[$item->area . '|' . $item->item][0]
                    ?? $item->baseline?->condition
                    ?? (mt_rand(1, 10) > 2 ? 'good' : 'excellent');
                $item->forceFill(['condition' => $condition, 'notes' => $issues[$item->area . '|' . $item->item][1] ?? null,
                    'deduction_amount' => $issues[$item->area . '|' . $item->item][2] ?? 0])->save();
            }
        };

        // Completed move-in inspections on a few active leases.
        foreach (['TRD-KCY:A201', 'TRD-KCY:A102', 'TRD-KMH:101', 'TRD-KCK:301', 'TRD-RMR:101'] as $key) {
            $lease = $this->leases[$key];
            $inspection = $service->start($lease, Inspection::TYPE_MOVE_IN, $this->managerFor($lease->unit));
            $rate($inspection, ['Living room|Walls' => ['fair', 'Small scuff marks near the TV wall.']]);
            $date = $lease->start_date->copy();
            $inspection->forceFill([
                'status' => 'completed', 'inspection_date' => $date->toDateString(), 'electricity_reading' => mt_rand(100, 900) + 0.5,
                'water_reading' => mt_rand(10, 200), 'keys_handed' => 3, 'general_notes' => 'Unit clean and freshly painted at handover.',
                'submitted_at' => $date, 'tenant_responded_at' => $date->copy()->addHours(5), 'completed_at' => $date->copy()->addHours(5),
                'created_at' => $date, 'updated_at' => $date,
            ])->save();
        }

        // Terminated lease: move-in, then move-out with deposit settlement.
        $lease = $this->leases['TRD-MSZ:201'];
        $manager = $this->managerFor($lease->unit);
        $lease->forceFill(['status' => 'active'])->save(); // allow the service to start a move-in on history
        $moveIn = $service->start($lease, Inspection::TYPE_MOVE_IN, $manager);
        $rate($moveIn);
        $moveIn->forceFill(['status' => 'completed', 'inspection_date' => $lease->start_date->toDateString(), 'electricity_reading' => 220.0,
            'water_reading' => 41, 'keys_handed' => 2, 'submitted_at' => $lease->start_date, 'tenant_responded_at' => $lease->start_date,
            'completed_at' => $lease->start_date])->save();
        $lease->forceFill(['status' => 'terminated'])->save();

        $moveOut = $service->start($lease, Inspection::TYPE_MOVE_OUT, $manager);
        $issues = [];
        foreach ($moveOut->items()->get() as $item) {
            if ($item->area === 'Living room' && $item->item === 'Walls') {
                $issues[$item->area . '|' . $item->item] = ['damaged', 'Holes from wall-mounted TV bracket; needs filling and repainting.', 45000];
            }
            if ($item->area === 'Kitchen' && $item->item === 'Cooker / stove') {
                $issues[$item->area . '|' . $item->item] = ['poor', 'One burner not working.', 30000];
            }
        }
        $rate($moveOut, $issues);
        $deductions = (float) $moveOut->items()->sum('deduction_amount');
        $out = $this->today->copy()->subDays(19);
        $moveOut->forceFill([
            'status' => 'completed', 'inspection_date' => $out->toDateString(), 'electricity_reading' => 1612.0, 'water_reading' => 298,
            'keys_handed' => 2, 'general_notes' => 'Unit returned clean. Minor damage noted below.',
            'submitted_at' => $out, 'tenant_responded_at' => $out->copy()->addDay(), 'completed_at' => $out->copy()->addDay(),
            'tenant_comment' => 'Agreed. Thank you for a smooth move-out.',
            'deposit_held' => $lease->deposit_amount, 'deposit_deductions' => $deductions, 'deposit_refund' => (float) $lease->deposit_amount - $deductions,
            'deposit_settled_at' => $out->copy()->addDays(3), 'deposit_refund_reference' => 'MTN MoMo MP' . $out->format('ymd') . '.4471.KQZ2',
            'settlement_notes' => 'Refund sent to the tenant\'s MTN number.',
        ])->save();

        // Awaiting the tenant's confirmation (for training: tenant confirms).
        $pending = $this->leases['TRD-KMH:301'];
        $inspection = $service->start($pending, Inspection::TYPE_MOVE_IN, $this->managerFor($pending->unit));
        $rate($inspection, ['Kitchen|Fridge' => ['fair', 'Door seal slightly worn.']]);
        $inspection->forceFill(['status' => 'awaiting_tenant', 'submitted_at' => $this->today->copy()->subDay(), 'electricity_reading' => 512.0, 'water_reading' => 77, 'keys_handed' => 3])->save();
    }

    private function documents(): void
    {
        $pdf = fn (string $title, string $line) => $this->simplePdf($title, $line);

        foreach (Tenant::whereNotNull('user_id')->get() as $i => $tenant) {
            $status = $tenant->full_name === 'Habimana Kevin' ? 'pending' : ($i % 7 === 3 ? 'pending' : 'verified');
            $path = FileUpload::put(FileUpload::KYC, "demo-national-id-{$tenant->id}.pdf", $pdf('DEMO DOCUMENT — Identity document placeholder', 'Sample file for ' . $tenant->full_name . '. Not a real identity document.'), true);
            $tenant->documents()->forceCreate([
                'category' => 'national_id', 'title' => 'National ID', 'path' => $path, 'original_name' => 'national-id.pdf',
                'mime_type' => 'application/pdf', 'size' => FileUpload::size($path, true),
                'uploaded_by' => $tenant->user_id, 'status' => $status,
                'verified_by' => $status === 'verified' ? $this->users['mgr_mugisha']->id : null,
                'verified_at' => $status === 'verified' ? $this->today->copy()->subMonths(1) : null,
            ]);
            $tenant->forceFill(['kyc_status' => $status, 'kyc_verified_at' => $status === 'verified' ? $this->today->copy()->subMonths(1) : null])->save();
        }

        foreach ($this->properties as $code => $property) {
            $path = FileUpload::put(FileUpload::PROPERTY_DOCUMENTS, "demo-title-deed-{$property->id}.pdf", $pdf('DEMO DOCUMENT — Title deed placeholder', $property->name . ' · UPI ' . $property->upi . '. Sample file only.'), true);
            $property->documents()->forceCreate([
                'category' => 'title_deed', 'title' => 'Certificate of registration (UPI ' . $property->upi . ')', 'path' => $path,
                'original_name' => 'title-deed.pdf', 'mime_type' => 'application/pdf', 'size' => FileUpload::size($path, true),
                'uploaded_by' => $this->users['admin']->id, 'status' => 'not_required',
            ]);
        }
    }

    /* ================================================================
     |  Contracts, renewals, messaging, roles
     * ================================================================ */

    private function contractsAndRequests(): void
    {
        $template = LeaseTemplate::where('is_default', true)->first();
        if ($template) {
            $builder = app(ContractBuilder::class);
            foreach (['TRD-KCY:A201', 'TRD-KMH:301', 'TRD-KCY:B102'] as $key) {
                $contract = $builder->generate($this->leases[$key], $template, $this->managerFor($this->leases[$key]->unit), ['notice_period_days' => 30]);
                $contract->forceFill(['status' => 'final', 'finalized_at' => $this->today->copy()->subDays(4)])->save();
            }
            $builder->generate($this->leases['TRD-KCK:402'], $template, $this->managerFor($this->leases['TRD-KCK:402']->unit), ['notice_period_days' => 30]);
        }

        $a301 = $this->leases['TRD-KCY:A301'];
        LeaseRequest::forceCreate([
            'lease_id' => $a301->id, 'tenant_id' => $a301->tenant_id, 'type' => 'renewal', 'status' => 'requested',
            'requested_by' => $a301->tenant->user_id, 'requested_months' => 12, 'message' => 'We love the apartment and would like to stay another year.',
            'current_end_date' => $a301->end_date, 'current_rent' => $a301->monthly_rent, 'created_at' => $this->today->copy()->subDays(2),
        ]);
        $k102 = $this->leases['TRD-KMH:102'];
        LeaseRequest::forceCreate([
            'lease_id' => $k102->id, 'tenant_id' => $k102->tenant_id, 'type' => 'renewal', 'status' => 'offered',
            'requested_by' => $this->managerFor($k102->unit)->id, 'current_end_date' => $k102->end_date, 'current_rent' => $k102->monthly_rent,
            'offered_end_date' => $k102->end_date->copy()->addYear(), 'offered_rent' => round((float) $k102->monthly_rent * 1.05, -3),
            'response' => 'Happy to renew for another year with a 5% adjustment in line with market rates.',
            'responded_by' => $this->managerFor($k102->unit)->id, 'responded_at' => $this->today->copy()->subDays(1),
        ]);
        $k201 = $this->leases['TRD-KCK:201'];
        LeaseRequest::forceCreate([
            'lease_id' => $k201->id, 'tenant_id' => $k201->tenant_id, 'type' => 'move_out', 'status' => 'requested',
            'requested_by' => $k201->tenant->user_id, 'move_out_date' => $k201->end_date, 'message' => 'Moving to Huye for work at the end of my lease.',
            'current_end_date' => $k201->end_date, 'current_rent' => $k201->monthly_rent, 'created_at' => $this->today->copy()->subDays(3),
        ]);
    }

    private function communication(): void
    {
        $aline = $this->users['tenant_aline'];
        $mugisha = $this->users['mgr_mugisha'];
        $kcy = $this->properties['TRD-KCY'];
        $a201 = $this->units['TRD-KCY:A201'];

        $this->conversation('Water heater in A201', [$aline, $mugisha], $kcy, $a201, [
            [$aline, 'Mwaramutse Patrick, the water heater stopped working again this morning. Could someone check it?', 4 * 24 + 2],
            [$mugisha, 'Mwaramutse Aline. Sorry about that — I have assigned Kigali Plumbing Services. They will come tomorrow at 10:00.', 4 * 24],
            [$aline, 'Murakoze! I will be home.', 4 * 24 - 1],
            [$mugisha, 'The technician has marked it fixed. Please confirm in the maintenance page when you have hot water.', 20],
        ], unreadFor: $aline);

        $this->conversation('November rent invoice', [$aline, $mugisha], $kcy, $a201, [
            [$aline, 'Hello, I will pay the November rent with MTN MoMo on Friday when my salary comes in. Is that okay?', 30],
            [$mugisha, 'No problem Aline, thank you for letting me know. You can pay directly from the invoice page.', 28],
        ]);

        $kevin = $this->users['tenant_kevin'];
        $this->conversation('Questions about my lease at B102', [$kevin, $mugisha], $kcy, $this->units['TRD-KCY:B102'], [
            [$kevin, 'Good afternoon, is parking included for one car? I could not see it in the lease.', 26],
            [$mugisha, 'Yes, one parking space is included with B102. It is in the house rules section.', 5],
        ], unreadFor: $kevin);

        // Announcements.
        $tenants = User::whereHas('tenant.leases', fn ($q) => $q->whereIn('status', ['active', 'pending_activation'])->whereHas('unit.building', fn ($b) => $b->where('property_id', $kcy->id)))->get();
        $this->conversation('Planned water interruption — Saturday 8:00–14:00', $tenants->push($mugisha)->unique('id')->all(), $kcy, null, [
            [$mugisha, "Muraho neza,\n\nWASAC will carry out maintenance on the main supply line this Saturday from 8:00 to 14:00. Please store some water on Friday evening. The building's reserve tank will supply the kitchens only.\n\nMurakoze — Kacyiru Heights management", 22],
        ], announcement: true);

        $rmr = $this->properties['TRD-RMR'];
        $grace = $this->users['mgr_uwamahoro'];
        $rmrTenants = User::whereHas('tenant.leases', fn ($q) => $q->where('status', 'active')->whereHas('unit.building', fn ($b) => $b->where('property_id', $rmr->id)))->get();
        $this->conversation('Fire safety drill — Thursday 10:00', $rmrTenants->push($grace)->unique('id')->all(), $rmr, null, [
            [$grace, 'Dear business owners, Rwanda National Police Fire and Rescue will run a short evacuation drill on Thursday at 10:00. It takes about 15 minutes. Thank you for your cooperation.', 50],
        ], announcement: true);

        // In-app notifications so each demo account has something to see.
        $note = function (User $u, string $category, string $title, string $message, ?string $url, string $tone, int $hoursAgo, bool $read = false) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(), 'type' => \App\Notifications\TerraAlert::class, 'notifiable_type' => User::class, 'notifiable_id' => $u->id,
                'data' => json_encode(['category' => $category, 'title' => $title, 'message' => $message, 'url' => $url,
                    'icon' => \App\Notifications\TerraAlert::iconFor($category), 'tone' => $tone, 'context' => []]),
                'read_at' => $read ? now() : null, 'created_at' => now()->subHours($hoursAgo), 'updated_at' => now()->subHours($hoursAgo),
            ]);
        };
        $alineInvoice = RentInvoice::where('lease_id', $this->leases['TRD-KCY:A201']->id)->where('invoice_type', 'rent')->latest('billing_period_start')->first();
        $note($aline, 'rent_overdue', 'Rent overdue', 'RWF ' . number_format((float) $alineInvoice->balance_due) . ' for Unit A201, Kacyiru Heights Apartments is overdue. Please pay as soon as possible.', url('/tenant-portal/rent-invoices/' . $alineInvoice->id), 'danger', 6);
        $note($aline, 'maintenance', 'Maintenance update: Resolved', 'Your request "Water heater not heating" is now Resolved. Please confirm the repair.', null, 'success', 20);
        $note($aline, 'announcement', 'Announcement: Planned water interruption — Saturday 8:00–14:00', 'WASAC will carry out maintenance on the main supply line this Saturday…', url('/messages'), 'info', 22);
        $note($kevin, 'lease', 'Your lease is ready to review', 'Lease for Unit B102, Kacyiru Heights Apartments is waiting for your review and acceptance.', url('/tenant-portal/leases/' . $this->leases['TRD-KCY:B102']->id), 'warning', 30);
        $note($mugisha, 'maintenance', 'New maintenance request: Bedroom socket sparking', 'Emergency priority request for Unit A202, Kacyiru Heights Apartments.', null, 'danger', 26);
        $note($mugisha, 'application', 'New rental application', 'Uwera Christine applied for Unit A401, Kacyiru Heights Apartments.', null, 'info', 40);
        $note($mugisha, 'lease', 'Renewal requested', 'A tenant would like to renew the lease for Unit A301 for 12 months.', url('/property-manager/leases/' . $this->leases['TRD-KCY:A301']->id), 'info', 48);
        $note($mugisha, 'payment', 'Rent received', 'A tenant paid RWF 650,000 for Unit A203, Kacyiru Heights Apartments.', null, 'success', 70, true);
        $note($this->users['mgr_uwamahoro'], 'lease', 'Lease accepted by tenant', 'Lease for Unit 301, Kimihurura Garden Residences is ready for activation.', url('/property-manager/leases/' . $this->leases['TRD-KMH:301']->id), 'success', 60);
    }

    private function rolesAndCommissions(): void
    {
        foreach (['TRD-KCY', 'TRD-KMH', 'TRD-RMR'] as $code) {
            PropertyUserRole::forceCreate(['property_id' => $this->properties[$code]->id, 'user_id' => $this->users['accountant']->id, 'role' => 'accountant', 'assigned_by' => $this->users['admin']->id]);
        }
        foreach (['TRD-KCY' => 50, 'TRD-KCK' => 50, 'TRD-NYR' => 40] as $code => $rate) {
            PropertyUserRole::forceCreate(['property_id' => $this->properties[$code]->id, 'user_id' => $this->users['agent']->id, 'role' => 'agent', 'commission_rate' => $rate, 'assigned_by' => $this->users['admin']->id]);
        }
        foreach ([['TRD-KCY:A202', 'paid'], ['TRD-KCK:302', 'paid'], ['TRD-NYR:V3', 'approved'], ['TRD-KCK:402', 'pending']] as [$key, $status]) {
            $lease = $this->leases[$key] ?? null;
            if (! $lease) {
                continue;
            }
            $rate = str_starts_with($key, 'TRD-NYR') ? 40 : 50;
            AgentCommission::forceCreate([
                'user_id' => $this->users['agent']->id, 'property_id' => $lease->unit->building->property_id, 'lease_id' => $lease->id,
                'amount' => round((float) $lease->monthly_rent * $rate / 100, -3), 'currency' => 'RWF', 'status' => $status,
                'description' => 'Tenant placement · lease ' . $lease->lease_number,
                'paid_at' => $status === 'paid' ? $lease->start_date->copy()->addDays(10) : null,
                'payment_reference' => $status === 'paid' ? 'FT' . mt_rand(100000, 999999) : null,
                'recorded_by' => $this->managerFor($lease->unit)->id,
            ]);
        }
    }

    /** A short, plausible audit trail so the admin activity feed isn't empty on day one. */
    private function recentActivity(): void
    {
        $admin = $this->users['admin'];
        $mugisha = $this->users['mgr_mugisha'];
        $grace = $this->users['mgr_uwamahoro'];
        $aline = $this->users['tenant_aline'];
        $entries = [
            [$admin, 'login', $admin, null, 9],
            [$mugisha, 'login', $mugisha, null, 7],
            [$mugisha, 'updated', $this->leases['TRD-KCY:B102'], ['status' => 'pending_tenant_acceptance'], 6],
            [$grace, 'updated', $this->leases['TRD-KMH:301'], ['tenant_accepted_at' => now()->subDays(3)->toDateTimeString(), 'status' => 'pending_activation'], 5],
            [$mugisha, 'updated', MaintenanceRequest::where('title', 'Bedroom socket sparking')->first(), ['status' => 'in_progress'], 4],
            [$aline, 'login', $aline, null, 3],
            [$mugisha, 'created', RentPayment::latest('id')->first(), ['status' => 'confirmed'], 2],
            [$admin, 'updated', $this->providers['plumbing'], ['verified_at' => now()->subMonths(6)->toDateTimeString()], 1],
        ];
        foreach ($entries as [$user, $event, $subject, $new, $hoursAgo]) {
            if (! $subject) {
                continue;
            }
            \App\Models\AuditLog::forceCreate([
                'user_id' => $user->id, 'event' => $event, 'auditable_type' => $subject::class, 'auditable_id' => $subject->getKey(),
                'new_values' => $new, 'ip_address' => '196.12.' . mt_rand(128, 159) . '.' . mt_rand(2, 250),
                'user_agent' => 'Mozilla/5.0 (Demo data)', 'created_at' => now()->subHours($hoursAgo),
            ]);
        }
    }

    /* ================================================================
     |  Helpers
     * ================================================================ */

    private function user(string $name, string $local, string $role): User
    {
        return User::forceCreate([
            'name' => $name, 'email' => $local . '@' . self::DOMAIN, 'password' => $this->passwordHash, 'role' => $role,
            'email_verified_at' => now(), 'notify_email' => false, 'notify_sms' => false, 'locale' => 'en',
        ]);
    }

    private function tenant(string $name, string $gender, string $phone, ?string $login = null, bool $withAccount = true): Tenant
    {
        $user = null;
        if ($withAccount || $login) {
            $local = $login ?? Str::slug($name, '.');
            for ($i = 2; ! $login && User::where('email', $local . '@' . self::DOMAIN)->exists(); $i++) {
                $local = Str::slug($name, '.') . $i;
            }
            $user = $this->user($name, $local, 'tenant');
            if ($login) {
                $this->users['tenant_' . explode('.', $login)[1]] = $user;
            }
        }

        return Tenant::forceCreate([
            'user_id' => $user?->id, 'tenant_number' => $this->num('TNT'), 'full_name' => $name, 'email' => $user?->email,
            'phone' => $this->phone($phone), 'id_type' => 'national_id', 'id_number' => $this->nationalId(1975 + crc32($name) % 30, $gender),
            'occupants' => 1 + crc32($name) % 4, 'kyc_status' => 'unverified', 'country' => 'Rwanda', 'province' => 'Kigali City',
            'district' => 'Gasabo', 'address' => 'Kigali', 'emergency_contact_name' => ['Uwimana Marie', 'Habimana Jean', 'Mukamana Agnès', 'Niyonsaba Paul'][crc32($name) % 4],
            'emergency_contact_phone' => $this->phone('0788 ' . (100000 + crc32($name) % 899999)), 'status' => 'active',
            'created_by' => $this->users['mgr_mugisha']->id,
        ]);
    }

    private function managerFor($model): User
    {
        $propertyId = match (true) {
            $model instanceof Property => $model->id,
            $model instanceof Unit => $model->building->property_id,
            default => $model->building?->property_id,
        };
        $code = collect($this->properties)->search(fn ($p) => $p->id === $propertyId);
        $spec = collect($this->propertySpecs())->firstWhere('code', $code);

        return $this->users['mgr_' . ($spec['managers'][0] ?? 'mugisha')];
    }

    private function conversation(string $subject, array $users, ?Property $property, ?Unit $unit, array $messages, ?User $unreadFor = null, bool $announcement = false): void
    {
        $first = now()->subHours($messages[0][2]);
        $conversation = Conversation::forceCreate([
            'type' => $announcement ? 'announcement' : 'direct', 'subject' => $subject, 'property_id' => $property?->id, 'unit_id' => $unit?->id,
            'created_by' => $messages[0][0]->id, 'last_message_at' => now()->subHours(end($messages)[2]), 'created_at' => $first,
        ]);
        $ids = [];
        foreach ($messages as [$from, $body, $hoursAgo]) {
            $ids[] = $conversation->messages()->forceCreate(['user_id' => $from->id, 'body' => $body, 'created_at' => now()->subHours($hoursAgo), 'updated_at' => now()->subHours($hoursAgo)])->id;
        }
        foreach ($users as $u) {
            $isStaff = in_array($u->role, ['manager', 'admin', 'owner'], true);
            $readUpTo = $unreadFor && $unreadFor->id === $u->id ? $ids[count($ids) - 2] ?? null : (end($ids));
            if ($announcement && ! $isStaff) {
                $readUpTo = null;
            }
            $conversation->participants()->forceCreate([
                'user_id' => $u->id, 'can_reply' => ! $announcement || $isStaff, 'last_read_message_id' => $readUpTo,
                'last_read_at' => $readUpTo ? now()->subHours(1) : null,
            ]);
        }
    }

    private function num(string $prefix, int $pad = 4): string
    {
        return $prefix . '-D' . str_pad((string) ++$this->seq, $pad, '0', STR_PAD_LEFT);
    }

    /** Demo numbers are stored the same way as real ones: E.164 (+2507...). */
    private function phone(string $p): string
    {
        return Phone::e164($p) ?? preg_replace('/\D/', '', $p);
    }

    /** Rwandan national ID shape: 1 YYYY G NNNNNNN F CC (16 digits). Fictitious. */
    private function nationalId(int $year, string $gender): string
    {
        return '1' . $year . ($gender === 'f' ? '7' : '8') . str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT) . '0' . str_pad((string) mt_rand(10, 99), 2, '0');
    }

    private function ordinal(int $n): string
    {
        return $n . (['th', 'st', 'nd', 'rd'][($n % 100 >= 11 && $n % 100 <= 13) ? 0 : min($n % 10, 4) % 4] ?? 'th');
    }

    private function unitDescription(string $kind, int $beds, float $sqm, string $property): string
    {
        return match ($kind) {
            'shop' => "Ground-floor shop of {$sqm} m² with street frontage and roller shutter at {$property}. Suitable for retail, pharmacy or café.",
            'office' => "Bright office space of {$sqm} m² with natural light, ready for partitioning, in {$property}.",
            'villa' => "Standalone {$beds}-bedroom family house of {$sqm} m² with private garden, servant quarters and secure parking.",
            default => ($beds === 0 ? 'Compact studio' : "{$beds}-bedroom apartment") . " of {$sqm} m² with balcony, fitted kitchen and backup water supply at {$property}.",
        };
    }

    /** Minimal valid one-page PDF with two lines of text. */
    private function simplePdf(string $title, string $line): string
    {
        $esc = fn ($s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], iconv('UTF-8', 'Windows-1252//TRANSLIT', $s));
        $content = "BT /F1 16 Tf 60 760 Td (" . $esc($title) . ") Tj 0 -28 Td /F1 11 Tf (" . $esc($line) . ") Tj 0 -20 Td (Generated by the Terra demo seeder.) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= str_pad((string) $o, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    /* ================================================================
     |  Terra subscriptions, field staff and contract documents
     * ================================================================ */

    private function subscriptions(): void
    {
        $admin = $this->users['admin'];

        // Field staff who signed some of the owners and managers up.
        $field = [];
        foreach ([['alice', 'Niyonsaba Alice', 'field', '0788 640 215', 'Kigali City', 'Gasabo'], ['olivier', 'Habimana Olivier', 'field.olivier', '0783 771 094', 'Northern Province', 'Musanze']] as $i => [$key, $name, $email, $phone, $region, $district]) {
            $user = $this->user($name, $email, 'field_staff');
            $user->forceFill(['phone' => $this->phone($phone)])->save();
            FieldStaff::forceCreate([
                'user_id' => $user->id, 'staff_number' => sprintf('FS-D%03d', $i + 1), 'phone' => $this->phone($phone),
                'region' => $region, 'district' => $district, 'status' => 'active',
                'last_active_at' => $this->today->copy()->subHours(3 + $i * 20), 'created_by' => $admin->id,
                'created_at' => $this->today->copy()->subMonths(10), 'updated_at' => $this->today->copy()->subMonths(10),
            ]);
            $field[$key] = $user;
        }
        $this->users['field_alice'] = $field['alice'];
        PropertyOwner::whereIn('user_id', [$this->users['owner_murekatete']->id, $this->users['owner_gasana']->id])
            ->update(['created_by' => $field['alice']->id, 'created_at' => $this->today->copy()->subMonths(4)]);
        PropertyOwner::where('user_id', $this->users['owner_ndayisaba']->id)->update(['created_by' => $field['olivier']->id, 'created_at' => $this->today->copy()->subMonths(7)]);
        PropertyManager::where('user_id', $this->users['mgr_nkurunziza']->id)->update(['created_by' => $field['olivier']->id, 'created_at' => $this->today->copy()->subMonths(2)]);

        // Login accounts carry the profile phone (E.164).
        foreach (PropertyOwner::whereNotNull('user_id')->get() as $o) {
            User::whereKey($o->user_id)->update(['phone' => $o->phone]);
        }
        foreach (PropertyManager::whereNotNull('user_id')->get() as $m) {
            User::whereKey($m->user_id)->update(['phone' => $m->phone]);
        }

        $plans = [
            'starter' => SubscriptionPlan::forceCreate(['name' => 'Starter', 'code' => 'starter', 'billing_cycle' => 'monthly', 'price' => 25000, 'currency' => 'RWF', 'max_properties' => 1, 'max_units' => 20, 'description' => 'One property, up to 20 units. Rent, leases, maintenance and tenant portal.', 'is_active' => true]),
            'growth' => SubscriptionPlan::forceCreate(['name' => 'Growth', 'code' => 'growth', 'billing_cycle' => 'monthly', 'price' => 60000, 'currency' => 'RWF', 'max_properties' => 5, 'max_units' => 150, 'description' => 'Up to 5 properties. Adds inspections, contracts, reports and SMS alerts.', 'is_active' => true]),
            'portfolio' => SubscriptionPlan::forceCreate(['name' => 'Portfolio', 'code' => 'portfolio', 'billing_cycle' => 'annual', 'price' => 650000, 'currency' => 'RWF', 'max_properties' => null, 'max_units' => null, 'description' => 'Unlimited properties for companies, billed yearly, with priority support.', 'is_active' => true]),
        ];

        // [user key, plan, status, start (months ago), term months, unpaid recent periods, auto renew]
        $specs = [
            ['owner_kayitesi', 'growth', 'active', 8, 12, 1, false],
            ['owner_rugamba', 'portfolio', 'active', 5, 12, 0, true],
            ['owner_ndayisaba', 'starter', 'active', 7, 12, 3, false],
            ['owner_murekatete', 'starter', 'active', 11, 12, 0, false],
            ['owner_gasana', 'growth', 'pending', 0, 12, 0, false],
            ['mgr_mugisha', 'starter', 'active', 6, 12, 0, true],
            ['mgr_uwamahoro', 'starter', 'expired', 14, 12, 0, false],
            ['mgr_nkurunziza', 'starter', 'terminated', 2, 12, 1, false],
        ];

        $contracts = [];
        foreach ($specs as $n => [$key, $planKey, $status, $ago, $term, $unpaid, $renew]) {
            $plan = $plans[$planKey];
            $user = $this->users[$key];
            $start = $this->today->copy()->startOfMonth()->subMonths($ago);
            if ($key === 'owner_murekatete') {
                $start = $this->today->copy()->addDays(20)->subMonths(12)->addDay(); // expires in 20 days
            }
            $end = $start->copy()->addMonths($term)->subDay();
            $contract = SubscriptionContract::forceCreate([
                'contract_number' => 'SUB-' . $start->format('Y') . '-D' . str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                'user_id' => $user->id, 'subscription_plan_id' => $plan->id,
                'title' => 'Terra ' . $plan->name . ' subscription agreement', 'start_date' => $start, 'end_date' => $end,
                'billing_cycle' => $plan->billing_cycle, 'amount' => $plan->price, 'currency' => 'RWF', 'payment_due_days' => 7,
                'status' => $status, 'auto_renew' => $renew,
                'terms' => "1. Terra provides access to the platform for the properties covered by the plan.\n\n2. Fees are invoiced at the start of each billing cycle and are payable within 7 days.\n\n3. Either party may end the agreement with 30 days' written notice.",
                'signed_at' => $status === 'pending' ? null : $start, 'activated_at' => $status === 'pending' ? null : $start,
                'terminated_at' => $status === 'terminated' ? $this->today->copy()->subDays(9) : null,
                'termination_reason' => $status === 'terminated' ? 'Manager moved to a company that already subscribes.' : null,
                'created_by' => $admin->id, 'created_at' => $start->copy()->subDays(5), 'updated_at' => $start,
            ]);
            $contracts[$key] = $contract;
            if ($status === 'pending') {
                continue;
            }

            // Invoices for every started cycle up to today (or the contract end / termination).
            $months = SubscriptionContract::CYCLES[$plan->billing_cycle];
            $lastDay = $status === 'expired' ? $end : ($status === 'terminated' ? $this->today->copy()->subDays(9) : $this->today);
            $periods = [];
            for ($p = $start->copy(); $p->lte($lastDay) && $p->lte($end); $p->addMonths($months)) {
                $periods[] = $p->copy();
            }
            foreach ($periods as $idx => $periodStart) {
                $isUnpaid = $idx >= count($periods) - $unpaid;
                $due = $periodStart->copy()->addDays(7);
                $invoice = SubscriptionInvoice::forceCreate([
                    'invoice_number' => 'SINV-' . $periodStart->format('Ym') . '-D' . str_pad((string) ++$this->seq, 4, '0', STR_PAD_LEFT),
                    'subscription_contract_id' => $contract->id, 'user_id' => $user->id,
                    'period_start' => $periodStart, 'period_end' => $periodStart->copy()->addMonths($months)->subDay()->min($end),
                    'issue_date' => $periodStart, 'due_date' => $due, 'amount' => $plan->price, 'amount_paid' => 0,
                    'currency' => 'RWF', 'status' => 'unpaid', 'created_at' => $periodStart, 'updated_at' => $periodStart,
                ]);
                if ($isUnpaid) {
                    continue;
                }
                $paidAt = $periodStart->copy()->addDays(($idx * 3 + $n) % 7)->setTime(10 + $idx % 6, 15);
                SubscriptionPayment::forceCreate([
                    'reference' => ($n % 3 === 1 ? 'BK' : 'MP') . $paidAt->format('ymd') . str_pad((string) ($n * 37 + $idx), 4, '0', STR_PAD_LEFT),
                    'subscription_invoice_id' => $invoice->id, 'subscription_contract_id' => $contract->id, 'user_id' => $user->id,
                    'amount' => $plan->price, 'currency' => 'RWF', 'method' => $n % 3 === 1 ? 'bank_transfer' : 'mobile_money',
                    'paid_at' => $paidAt, 'recorded_by' => $admin->id, 'created_at' => $paidAt, 'updated_at' => $paidAt,
                ]);
                $invoice->forceFill(['amount_paid' => $plan->price, 'status' => 'paid'])->save();
            }
        }

        // Partial payment on Ndayisaba's oldest overdue invoice.
        $oldest = $contracts['owner_ndayisaba']->invoices()->where('status', 'unpaid')->orderBy('period_start')->first();
        if ($oldest) {
            SubscriptionPayment::forceCreate([
                'reference' => 'MP' . $oldest->due_date->format('ymd') . '9001', 'subscription_invoice_id' => $oldest->id,
                'subscription_contract_id' => $oldest->subscription_contract_id, 'user_id' => $oldest->user_id, 'amount' => 10000,
                'currency' => 'RWF', 'method' => 'mobile_money', 'paid_at' => $oldest->due_date->copy()->addDays(3)->setTime(16, 40),
                'notes' => 'Part payment, promised the rest at month end.', 'recorded_by' => $admin->id,
            ]);
            $oldest->forceFill(['amount_paid' => 10000, 'status' => 'partial'])->save();
        }

        // Contact history.
        $ndayisaba = $this->users['owner_ndayisaba'];
        foreach ([
            [$ndayisaba, 'phone', 'outbound', 'no_answer', null, 'Overdue subscription follow-up', null, 12],
            [$ndayisaba, 'phone', 'outbound', 'answered', 260, 'Overdue subscription follow-up', 'Owner confirmed the invoices; will pay the balance after collecting rent on the 5th.', 10],
            [$ndayisaba, 'email', 'outbound', 'sent', null, 'Reminder: overdue Terra subscription', "Dear Félix,\nThis is a reminder that two Terra subscription invoices are overdue. You can pay by MTN MoMo using the invoice number as reference.", 6],
            [$this->users['owner_gasana'], 'browser', 'outbound', 'answered', 1140, 'Walk-through of the Growth plan', 'Demonstrated reports and inspections. Sending the agreement for signature.', 3],
            [$this->users['owner_murekatete'], 'whatsapp', 'outbound', 'callback', null, 'Renewal before expiry', 'Asked to call back next week to discuss the Growth plan.', 1],
        ] as [$u, $channel, $dir, $outcome, $secs, $subject, $notes, $daysAgo]) {
            SubscriberCall::forceCreate([
                'user_id' => $u->id, 'called_by' => $admin->id, 'direction' => $dir, 'channel' => $channel, 'phone' => $u->phone,
                'outcome' => $outcome, 'duration_seconds' => $secs, 'subject' => $subject, 'notes' => $notes,
                'called_at' => $this->today->copy()->subDays($daysAgo)->setTime(11, 20),
            ]);
        }

        // Signed contracts as PDFs on owner and tenant profiles, and on subscription agreements.
        $put = function ($owner, string $key, string $category, string $title, string $file, string $line, int $uploader) {
            $path = FileUpload::put(Document::directoryFor($category, $key), "demo-{$key}-{$owner->getKey()}-{$file}", $this->simplePdf('DEMO DOCUMENT — ' . $title, $line), true);
            $owner->documents()->forceCreate([
                'category' => $category, 'title' => $title, 'path' => $path, 'original_name' => $file, 'mime_type' => 'application/pdf',
                'size' => FileUpload::size($path, true), 'uploaded_by' => $uploader, 'status' => 'not_required',
            ]);
        };
        foreach (['owner_kayitesi', 'owner_rugamba', 'owner_ndayisaba'] as $key) {
            $owner = PropertyOwner::where('user_id', $this->users[$key]->id)->first();
            $put($owner, 'owner', 'management_agreement', 'Property management agreement', 'management-agreement.pdf', 'Agreement between ' . $owner->name . ' and Terra. Sample file only.', $admin->id);
        }
        foreach ($contracts as $key => $contract) {
            if (in_array($contract->status, ['active', 'expired'], true)) {
                $put($contract, 'subscription', 'subscription_agreement', 'Signed agreement ' . $contract->contract_number, 'signed-agreement.pdf', $contract->title . ' — sample signature page.', $admin->id);
            }
        }
        // Field staff activity for the admin monitor.
        $gasanaOwner = PropertyOwner::where('user_id', $this->users['owner_gasana']->id)->first();
        $murekateteOwner = PropertyOwner::where('user_id', $this->users['owner_murekatete']->id)->first();
        $ndayisabaOwner = PropertyOwner::where('user_id', $ndayisaba->id)->first();
        foreach ([
            [$field['alice'], 'created', $murekateteOwner, ['verification_status' => 'pending'], $this->today->copy()->subMonths(4)->setTime(10, 5)],
            [$field['alice'], 'created', $gasanaOwner, ['verification_status' => 'pending'], $this->today->copy()->subMonths(4)->setTime(15, 40)],
            [$field['olivier'], 'created', $ndayisabaOwner, ['verification_status' => 'pending'], $this->today->copy()->subMonths(7)->setTime(9, 30)],
            [$field['olivier'], 'login', $field['olivier'], null, now()->subDay()->setTime(8, 2)],
            [$field['alice'], 'login', $field['alice'], null, now()->subHours(3)],
        ] as [$actor, $event, $subject, $new, $when]) {
            \App\Models\AuditLog::forceCreate([
                'user_id' => $actor->id, 'event' => $event, 'auditable_type' => $subject::class, 'auditable_id' => $subject->getKey(),
                'new_values' => $new, 'ip_address' => '41.186.' . mt_rand(10, 90) . '.' . mt_rand(2, 250),
                'user_agent' => 'Mozilla/5.0 (Android; Demo data)', 'created_at' => $when,
            ]);
        }

        // Subscription alerts in the bell.
        $alert = function (User $u, string $category, string $title, string $message, ?string $url, string $tone, int $hoursAgo) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(), 'type' => \App\Notifications\TerraAlert::class, 'notifiable_type' => User::class, 'notifiable_id' => $u->id,
                'data' => json_encode(['category' => $category, 'title' => $title, 'message' => $message, 'url' => $url,
                    'icon' => \App\Notifications\TerraAlert::iconFor($category), 'tone' => $tone, 'context' => []]),
                'read_at' => null, 'created_at' => now()->subHours($hoursAgo), 'updated_at' => now()->subHours($hoursAgo),
            ]);
        };
        $alert($ndayisaba, 'subscription', 'Subscription payment overdue', 'Two Terra subscription invoices are overdue (RWF 65,000). Please pay to keep your account active.', '/my-subscription', 'danger', 30);
        $alert($this->users['owner_gasana'], 'subscription', 'Your Terra subscription agreement is ready', 'Agreement ' . $contracts['owner_gasana']->contract_number . ' (Growth plan) is waiting for your signature.', '/my-subscription', 'info', 50);
        $alert($admin, 'registration', 'New owner registered in the field', 'Niyonsaba Alice registered Gasana Real Estate Ltd as a property owner.', '/admin/owners/' . $gasanaOwner->id, 'info', 20);

        foreach ($this->leases as $lease) {
            if ($lease->status === 'active' && $lease->tenant) {
                $put($lease->tenant, 'tenant', 'tenancy_contract', 'Signed tenancy contract ' . $lease->lease_number, 'tenancy-contract.pdf', 'Tenancy contract for ' . $lease->tenant->full_name . '. Sample file only.', $this->managerFor($lease->unit)->id);
                break;
            }
        }
    }

    private function printCredentials(): void
    {
        if (! $this->command) {
            return;
        }
        $this->command->info('Terra demo data created. Password for every account: ' . self::DEMO_PASSWORD);
        $this->command->table(['Role', 'Email', 'What to show'], [
            ['Administrator', 'admin@' . self::DOMAIN, 'Platform monitor, audit log, users, all properties'],
            ['Property manager', 'manager@' . self::DOMAIN, 'Kacyiru & Nyarutarama: leases, maintenance, renewals'],
            ['Property manager', 'manager.grace@' . self::DOMAIN, 'Kimihurura & Remera: lease ready to activate'],
            ['Property manager', 'manager.bosco@' . self::DOMAIN, 'Kicukiro, Musanze, Rubavu'],
            ['Owner', 'owner@' . self::DOMAIN, 'Kayitesi Diane — Kimihurura & Rubavu portfolio'],
            ['Tenant', 'tenant.aline@' . self::DOMAIN, 'Overdue invoice to pay, repair to confirm, messages'],
            ['Tenant', 'tenant.kevin@' . self::DOMAIN, 'Lease waiting for acceptance'],
            ['Tenant', 'tenant.brian@' . self::DOMAIN, 'Move-in inspection to confirm'],
            ['Accountant', 'accountant@' . self::DOMAIN, 'Finance workspace & reports'],
            ['Agent', 'agent@' . self::DOMAIN, 'Assigned properties & commissions'],
            ['Technician', 'technician@' . self::DOMAIN, 'Kigali Plumbing — assigned jobs'],
            ['Field staff', 'field@' . self::DOMAIN, 'Register owners & managers; nothing else'],
        ]);
    }

    /* ================================================================
     |  Data
     * ================================================================ */

    /** Named tenants used in training scenarios: [name, gender, phone, login]. */
    private function namedTenants(): array
    {
        return [
            'aline' => ['Uwase Aline', 'f', '0788 316 724', 'tenant.aline'],
            'kevin' => ['Habimana Kevin', 'm', '0783 552 190', 'tenant.kevin'],
            'brian' => ['Nshuti Brian', 'm', '0722 870 431', 'tenant.brian'],
            'divine' => ['Ingabire Divine', 'f', '0789 204 615', 'tenant.divine'],
            'fabrice' => ['Niyomugabo Fabrice', 'm', '0785 990 362', 'tenant.fabrice'],
        ];
    }

    /** [name, gender, phone] */
    private function tenantNames(): array
    {
        return [
            ['Mukamana Josiane', 'f', '0788 101 552'], ['Nshimiyimana Eric', 'm', '0788 223 904'], ['Uwimana Grace', 'f', '0789 450 117'],
            ['Habimana Olivier', 'm', '0783 662 039'], ['Iradukunda Sandrine', 'f', '0785 118 770'], ['Mugabo Emmanuel', 'm', '0722 304 818'],
            ['Umutoni Christine', 'f', '0788 774 260'], ['Bizimana Alexis', 'm', '0787 552 119'], ['Mutesi Vanessa', 'f', '0786 330 945'],
            ['Hakizimana Damascène', 'm', '0788 908 412'], ['Uwera Clémentine', 'f', '0723 661 054'], ['Kamanzi Thierry', 'm', '0788 440 381'],
            ['Ingabire Esther', 'f', '0789 115 662'], ['Murenzi Didier', 'm', '0784 772 093'], ['Mbabazi Gisèle', 'f', '0788 290 517'],
            ['Rukundo Innocent', 'm', '0785 603 288'], ['Tuyishime Carine', 'f', '0782 117 940'], ['Byiringiro Alain', 'm', '0788 551 306'],
            ['Uwamariya Diane', 'f', '0787 409 233'], ['Ndahiro Samuel', 'm', '0722 338 761'], ['Mukeshimana Agnès', 'f', '0788 662 410'],
            ['Kalisa Jean Paul', 'm', '0789 773 058'], ['Nakato Sarah', 'f', '0790 225 614'], ['Janssens Pieter', 'm', '0788 914 220'],
            ['Karekezi Yves', 'm', '0783 120 448'], ['Nzeyimana Fabiola', 'f', '0785 774 309'], ['Gatete Christian', 'm', '0788 336 721'],
            ['Umuhoza Belyse', 'f', '0786 552 093'], ['Niyonsaba Patrick', 'm', '0788 207 664'], ['Hassan Amina', 'f', '0789 330 118'],
            ['Nyiransabimana Odette', 'f', '0784 336 509'], ['Sibomana Gilbert', 'm', '0788 102 774'], ['Uwizeye Lydia', 'f', '0789 667 281'],
            ['Munyaneza Robert', 'm', '0785 449 106'], ['Mukandayisenga Alice', 'f', '0788 223 619'], ['Nkubito Frank', 'm', '0722 774 503'],
            ['Akimana Joyce', 'f', '0788 905 337'], ['Twagirumukiza Didace', 'm', '0783 616 042'], ['Dusabe Ange', 'f', '0786 118 755'],
            ['Hategekimana Moïse', 'm', '0788 330 991'], ['Umwali Nadine', 'f', '0789 504 216'], ['Rwigema Steven', 'm', '0788 712 648'],
            ['Kantengwa Jeanne', 'f', '0785 220 873'], ['Nsabimana Aimable', 'm', '0788 449 302'], ['Uwineza Sonia', 'f', '0787 663 915'],
        ];
    }

    /** Business tenants for shops and offices: [name, gender of contact, phone]. */
    private function businessTenants(): array
    {
        return [
            ['Inzozi Coffee Ltd', 'f', '0788 640 902'], ['Ubuzima Pharmacy Ltd', 'm', '0788 512 337'],
            ['Kigali Tech Hub Ltd', 'm', '0783 900 116'], ['Amahoro Insurance Brokers Ltd', 'f', '0788 760 248'],
            ['Isimbi Beauty Salon', 'f', '0789 441 205'], ['Akagera Mobile Money Agency', 'm', '0788 117 630'],
            ['Umurage Accounting Services', 'f', '0785 902 448'], ['Nyamata Hardware Ltd', 'm', '0722 660 317'],
        ];
    }

    private function propertySpecs(): array
    {
        $flat = fn (array $features) => ['default' => $features];
        $apt = fn (string $n, int $beds, float $sqm, int $rent) => [$n, 'apt', $beds, max(1, $beds - ($beds > 2 ? 1 : 0)), $sqm, $rent];

        return [
            [
                'code' => 'TRD-KCY', 'name' => 'Kacyiru Heights Apartments', 'type' => 'residential', 'owner' => 'OWN-D002', 'managers' => ['mugisha'],
                'province' => 'Kigali City', 'district' => 'Gasabo', 'sector' => 'Kacyiru', 'cell' => 'Kamatamu', 'village' => 'Kibiraro', 'address' => 'KG 7 Ave, near the US Embassy',
                'lat' => -1.9355, 'lng' => 30.0817, 'year' => 2019, 'upi' => '1/02/09/03/1871', 'style' => 'apartments', 'photo_floors' => 4,
                'description' => 'Modern apartments in a quiet, secure part of Kacyiru, five minutes from the ministries, embassies and Kigali Heights. Two blocks with lifts, backup generator, borehole water and 24/7 security.',
                'amenities' => ['parking', 'security', 'cctv', 'backup_power', 'water_tank', 'internet', 'elevator'], 'utilities' => ['electricity', 'water', 'internet', 'waste'],
                'features' => $flat(['Spacious living room', 'Modern fitted kitchen', 'Balcony with city view', 'Built-in wardrobes']),
                'buildings' => [
                    ['code' => 'A', 'name' => 'Block A', 'floors' => [
                        1 => [$apt('A101', 1, 62, 450000), $apt('A102', 2, 88, 650000), $apt('A103', 3, 120, 850000)],
                        2 => [$apt('A201', 2, 88, 650000), $apt('A202', 2, 88, 650000), $apt('A203', 2, 90, 650000)],
                        3 => [$apt('A301', 3, 120, 850000), $apt('A302', 1, 62, 450000), $apt('A303', 2, 88, 650000)],
                        4 => [$apt('A401', 3, 125, 900000), $apt('A402', 1, 60, 450000), $apt('A403', 2, 88, 680000)],
                    ]],
                    ['code' => 'B', 'name' => 'Block B', 'floors' => [
                        1 => [$apt('B101', 1, 58, 420000), $apt('B102', 2, 84, 620000)],
                        2 => [$apt('B201', 1, 58, 420000), $apt('B202', 2, 84, 620000)],
                        3 => [$apt('B301', 2, 84, 640000), $apt('B302', 3, 112, 820000)],
                    ]],
                ],
            ],
            [
                'code' => 'TRD-KMH', 'name' => 'Kimihurura Garden Residences', 'type' => 'residential', 'owner' => 'OWN-D001', 'managers' => ['uwamahoro'],
                'province' => 'Kigali City', 'district' => 'Gasabo', 'sector' => 'Kimihurura', 'cell' => 'Rugando', 'village' => 'Gasasa', 'address' => 'KG 9 Ave, Kimihurura',
                'lat' => -1.9536, 'lng' => 30.0925, 'year' => 2017, 'upi' => '1/02/10/01/0944', 'style' => 'apartments', 'photo_floors' => 3,
                'description' => 'Leafy residences set in landscaped gardens in Kimihurura, walking distance to restaurants, the Kigali Golf Club and Kigali Convention Centre.',
                'amenities' => ['parking', 'security', 'garden', 'backup_power', 'water_tank', 'internet', 'playground'], 'utilities' => ['electricity', 'water', 'internet', 'waste'],
                'features' => $flat(['Garden-facing balcony', 'Open-plan kitchen', 'Master bedroom en suite', 'Laundry area']),
                'buildings' => [['code' => 'M', 'name' => 'Main Residence', 'floors' => [
                    1 => [$apt('101', 2, 95, 700000), $apt('102', 3, 130, 950000), $apt('103', 2, 95, 700000)],
                    2 => [$apt('201', 2, 95, 720000), $apt('202', 3, 130, 980000), $apt('203', 2, 95, 720000)],
                    3 => [$apt('301', 3, 135, 1000000), $apt('302', 2, 98, 750000), $apt('303', 3, 135, 1000000)],
                ]]],
            ],
            [
                'code' => 'TRD-NYR', 'name' => 'Nyarutarama Villas', 'type' => 'residential', 'owner' => 'OWN-D005', 'managers' => ['mugisha'],
                'province' => 'Kigali City', 'district' => 'Gasabo', 'sector' => 'Remera', 'cell' => 'Nyarutarama', 'village' => 'Gaculiro', 'address' => 'KG 13 Ave, Nyarutarama',
                'lat' => -1.9420, 'lng' => 30.1040, 'year' => 2015, 'upi' => '1/02/11/05/2210', 'style' => 'villa', 'photo_floors' => 2,
                'description' => 'Five standalone family villas in a gated compound in Nyarutarama, close to the golf course, international schools and MTN Centre.',
                'amenities' => ['parking', 'security', 'cctv', 'garden', 'backup_power', 'water_tank', 'swimming_pool'], 'utilities' => ['electricity', 'water', 'internet', 'waste', 'gas'],
                'features' => ['villa' => ['Private garden', 'Servant quarters', 'Double garage', 'Fireplace in the lounge'], 'default' => ['Private garden']],
                'buildings' => [['code' => 'V', 'name' => 'Villa Compound', 'floors' => [0 => [
                    ['V1', 'villa', 4, 3, 280, 2200000], ['V2', 'villa', 4, 3, 300, 2400000], ['V3', 'villa', 5, 4, 340, 2800000],
                    ['V4', 'villa', 4, 3, 280, 2200000], ['V5', 'villa', 3, 2, 220, 1800000],
                ]]]],
            ],
            [
                'code' => 'TRD-KCK', 'name' => 'Kicukiro Sunrise Apartments', 'type' => 'residential', 'owner' => 'OWN-D003', 'managers' => ['nkurunziza'],
                'province' => 'Kigali City', 'district' => 'Kicukiro', 'sector' => 'Niboye', 'cell' => 'Gatare', 'village' => 'Rwimbogo', 'address' => 'KK 15 Rd, Niboye',
                'lat' => -1.9800, 'lng' => 30.1050, 'year' => 2021, 'upi' => '1/03/04/02/3315', 'style' => 'apartments', 'photo_floors' => 4,
                'description' => 'Affordable, well-kept apartments in Niboye, Kicukiro — close to Sonatubes, the airport road and public transport. Popular with young professionals.',
                'amenities' => ['parking', 'security', 'water_tank', 'internet'], 'utilities' => ['electricity', 'water', 'waste'],
                'features' => $flat(['Tiled floors throughout', 'Kitchenette', 'Secure entrance', 'Water tank backup']),
                'buildings' => [['code' => 'S', 'name' => 'Sunrise Block', 'floors' => [
                    1 => [$apt('101', 0, 32, 180000), $apt('102', 1, 45, 250000), $apt('103', 2, 65, 350000), $apt('104', 1, 45, 250000)],
                    2 => [$apt('201', 1, 45, 250000), $apt('202', 2, 65, 350000), $apt('203', 0, 32, 180000), $apt('204', 1, 45, 250000)],
                    3 => [$apt('301', 2, 65, 360000), $apt('302', 1, 45, 260000), $apt('303', 0, 32, 190000), $apt('304', 2, 65, 360000)],
                    4 => [$apt('401', 1, 46, 270000), $apt('402', 2, 66, 370000), $apt('403', 0, 33, 190000), $apt('404', 2, 66, 370000)],
                ]]],
            ],
            [
                'code' => 'TRD-RMR', 'name' => 'Remera Business Plaza', 'type' => 'mixed_use', 'owner' => 'OWN-D002', 'managers' => ['uwamahoro'],
                'province' => 'Kigali City', 'district' => 'Gasabo', 'sector' => 'Remera', 'cell' => 'Rukiri I', 'village' => 'Amahoro', 'address' => 'KG 17 Ave, opposite Amahoro Stadium',
                'lat' => -1.9570, 'lng' => 30.1130, 'year' => 2018, 'upi' => '1/02/11/02/0577', 'style' => 'commercial', 'photo_floors' => 3,
                'description' => 'Busy mixed-use plaza on the main Remera road with ground-floor shops and first-floor offices. High foot traffic near Amahoro Stadium and the Remera taxi park.',
                'amenities' => ['parking', 'security', 'cctv', 'backup_power', 'internet'], 'utilities' => ['electricity', 'water', 'internet', 'waste'],
                'features' => ['shop' => ['Street frontage', 'Roller shutter', 'Shared customer parking', '3-phase power'], 'office' => ['Natural light', 'Shared meeting room', 'Fibre internet ready'], 'default' => ['Street frontage']],
                'buildings' => [['code' => 'P', 'name' => 'Plaza', 'floors' => [
                    0 => [['G01', 'shop', 0, 1, 48, 900000], ['G02', 'shop', 0, 1, 40, 750000], ['G03', 'shop', 0, 1, 40, 750000], ['G04', 'shop', 0, 1, 55, 950000]],
                    1 => [['101', 'office', 0, 1, 60, 650000], ['102', 'office', 0, 1, 45, 500000], ['103', 'office', 0, 1, 45, 500000], ['104', 'office', 0, 1, 80, 850000]],
                ]]],
            ],
            [
                'code' => 'TRD-MSZ', 'name' => 'Musanze Volcano View Apartments', 'type' => 'residential', 'owner' => 'OWN-D004', 'managers' => ['nkurunziza'],
                'province' => 'Northern Province', 'district' => 'Musanze', 'sector' => 'Muhoza', 'cell' => 'Cyabararika', 'village' => 'Bukane', 'address' => 'NR 4, Musanze town',
                'lat' => -1.4995, 'lng' => 29.6340, 'year' => 2020, 'upi' => '4/04/09/03/0812', 'style' => 'apartments', 'photo_floors' => 2,
                'description' => 'Comfortable apartments in Musanze town with views of the Virunga volcanoes. Ideal for tourism, conservation and NGO staff working in the north.',
                'amenities' => ['parking', 'security', 'garden', 'water_tank'], 'utilities' => ['electricity', 'water', 'waste'],
                'features' => $flat(['Volcano view', 'Fireplace', 'Hot water system', 'Covered parking']),
                'buildings' => [['code' => 'V', 'name' => 'Volcano View', 'floors' => [
                    1 => [$apt('101', 1, 55, 180000), $apt('102', 2, 78, 280000), $apt('103', 3, 105, 380000)],
                    2 => [$apt('201', 2, 78, 290000), $apt('202', 1, 55, 190000), $apt('203', 3, 105, 390000)],
                ]]],
            ],
            [
                'code' => 'TRD-RBV', 'name' => 'Rubavu Lakeside Residences', 'type' => 'residential', 'owner' => 'OWN-D001', 'managers' => ['nkurunziza'],
                'province' => 'Western Province', 'district' => 'Rubavu', 'sector' => 'Gisenyi', 'cell' => 'Bugoyi', 'village' => 'Ubumwe', 'address' => 'Avenue de la Production, Gisenyi',
                'lat' => -1.7020, 'lng' => 29.2560, 'year' => 2016, 'upi' => '3/03/04/01/1450', 'style' => 'apartments', 'photo_floors' => 2,
                'description' => 'Relaxed residences a short walk from the Lake Kivu shoreline in Gisenyi, with a shared garden and lake breezes.',
                'amenities' => ['parking', 'security', 'garden', 'backup_power', 'water_tank'], 'utilities' => ['electricity', 'water', 'waste'],
                'features' => $flat(['Partial lake view', 'Shared garden', 'Large balcony', 'Mosquito screens']),
                'buildings' => [['code' => 'L', 'name' => 'Lakeside', 'floors' => [
                    1 => [$apt('101', 2, 85, 320000), $apt('102', 3, 110, 450000), $apt('103', 2, 85, 320000)],
                    2 => [$apt('201', 2, 85, 330000), $apt('202', 3, 112, 460000), $apt('203', 2, 85, 330000)],
                ]]],
            ],
        ];
    }
}
