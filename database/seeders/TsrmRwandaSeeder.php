<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Realistic Rwandan demo data: 5 records per table.
 *
 * Place in database/seeders/TsrmRwandaSeeder.php and run:
 *   php artisan db:seed --class=TsrmRwandaSeeder
 *
 * Safe to re-run: every row is keyed on its unique column (email, code, number...)
 * and updated instead of duplicated. All demo users get the password: "password".
 */
class TsrmRwandaSeeder extends Seeder
{
    private Carbon $now;

    /** Insert or update by unique keys, return the row id. */
    private function upsert(string $table, array $keys, array $values): int
    {
        $values['created_at'] = $values['created_at'] ?? $this->now;
        $values['updated_at'] = $this->now;

        DB::table($table)->updateOrInsert($keys, $values);

        return (int) DB::table($table)->where($keys)->value('id');
    }

    public function run(): void
    {
        $this->now = Carbon::now();
        $password  = Hash::make('password');

        DB::transaction(function () use ($password) {

            $adminId = DB::table('users')->where('role', 'admin')->value('id');

            /* ------------------------------------------------------------
             | USERS (5 owners, 5 managers, 5 tenants)
             * ---------------------------------------------------------- */
            $mkUser = fn (string $name, string $email, string $role) => $this->upsert(
                'users',
                ['email' => $email],
                [
                    'name'              => $name,
                    'email_verified_at' => $this->now,
                    'password'          => $password,
                    'role'              => $role,
                ]
            );

            $ownerUsers = [
                $mkUser('Uwimana Jean Claude',      'jc.uwimana@example.rw',     'owner'),
                $mkUser('Mukamana Immaculée',       'immaculee.m@example.rw',    'owner'),
                $mkUser('Habimana Eric',            'eric.habimana@example.rw',  'owner'),
                $mkUser('Nsengiyumva Patrick',      'patrick.nsengi@example.rw', 'owner'),
                $mkUser('Ingabire Marie Rose',      'mrose.ingabire@example.rw', 'owner'),
            ];

            $managerUsers = [
                $mkUser('Niyonzima Olivier',   'olivier.niyonzima@example.rw', 'manager'),
                $mkUser('Uwase Claudine',      'claudine.uwase@example.rw',    'manager'),
                $mkUser('Mugabo Emmanuel',     'emmanuel.mugabo@example.rw',   'manager'),
                $mkUser('Nikuze Aline',        'aline.nikuze@example.rw',      'manager'),
                $mkUser('Kamanzi Théogène',    'theogene.kamanzi@example.rw',  'manager'),
            ];

            $tenantUsers = [
                $mkUser('Iradukunda Sandrine', 'sandrine.irad@example.rw',     'tenant'),
                $mkUser('Bizimana Alexis',     'alexis.bizimana@example.rw',   'tenant'),
                $mkUser('Mutesi Josiane',      'josiane.mutesi@example.rw',    'tenant'),
                $mkUser('Hakizimana Damascène','damascene.hakiz@example.rw',   'tenant'),
                $mkUser('Umutoni Béatrice',    'beatrice.umutoni@example.rw',  'tenant'),
            ];

            /* ------------------------------------------------------------
             | PROPERTY OWNERS
             * ---------------------------------------------------------- */
            $ownerRows = [
                ['individual', 'Uwimana Jean Claude', '+250788412305', '1197280012345678', null,                          'Kigali City',       'Gasabo',     'Kimironko, Kigali'],
                ['individual', 'Mukamana Immaculée',  '+250722918446', '1198570023456789', null,                          'Kigali City',       'Nyarugenge', 'Nyamirambo, Kigali'],
                ['company',    'Habimana Eric',       '+250739204518', '1198080034567890', 'Habimana Investments Ltd',    'Kigali City',       'Kicukiro',   'Kicukiro Centre, Kigali'],
                ['individual', 'Nsengiyumva Patrick', '+250781735092', '1196950045678901', null,                          'Northern Province', 'Musanze',    'Muhoza, Musanze'],
                ['company',    'Ingabire Marie Rose', '+250788660174', '1199170056789012', 'Rose Properties Rwanda Ltd',  'Western Province',  'Rubavu',     'Gisenyi, Rubavu'],
            ];

            $ownerIds = [];
            foreach ($ownerRows as $i => [$type, $name, $phone, $nid, $company, $prov, $dist, $addr]) {
                $ownerIds[] = $this->upsert(
                    'property_owners',
                    ['owner_number' => sprintf('OWN-20261001-SEED%02d', $i + 1)],
                    [
                        'user_id'             => $ownerUsers[$i],
                        'owner_type'          => $type,
                        'name'                => $name,
                        'email'               => DB::table('users')->where('id', $ownerUsers[$i])->value('email'),
                        'phone'               => $phone,
                        'national_id'         => $nid,
                        'company_name'        => $company,
                        'country'             => 'Rwanda',
                        'province'            => $prov,
                        'district'            => $dist,
                        'address'             => $addr,
                        'verification_status' => 'verified',
                        'status'              => 'active',
                        'created_by'          => $adminId,
                    ]
                );
            }

            /* ------------------------------------------------------------
             | PROPERTY MANAGERS
             * ---------------------------------------------------------- */
            $managerRows = [
                ['+250788301122', 'Kigali City',       'Gasabo',     'Remera, Kigali',        'Senior Property Manager', 'full_time', '2025-02-03'],
                ['+250722554433', 'Kigali City',       'Nyarugenge', 'Kiyovu, Kigali',        'Property Manager',        'full_time', '2025-06-16'],
                ['+250739887766', 'Kigali City',       'Kicukiro',   'Gikondo, Kigali',       'Leasing Officer',         'part_time', '2025-09-01'],
                ['+250781990011', 'Northern Province', 'Musanze',    'Cyuve, Musanze',        'Property Manager',        'full_time', '2026-01-12'],
                ['+250788223355', 'Western Province',  'Rubavu',     'Nyundo, Rubavu',        'Facilities Coordinator',  'contract',  '2026-03-02'],
            ];

            $managerIds = [];
            foreach ($managerRows as $i => [$phone, $prov, $dist, $addr, $title, $emp, $start]) {
                $managerIds[] = $this->upsert(
                    'property_managers',
                    ['manager_number' => sprintf('MGR-20261001-SEED%02d', $i + 1)],
                    [
                        'user_id'         => $managerUsers[$i],
                        'phone'           => $phone,
                        'country'         => 'Rwanda',
                        'province'        => $prov,
                        'district'        => $dist,
                        'address'         => $addr,
                        'job_title'       => $title,
                        'employment_type' => $emp,
                        'start_date'      => $start,
                        'status'          => 'active',
                        'created_by'      => $adminId,
                    ]
                );
            }

            /* ------------------------------------------------------------
             | PROPERTIES
             * ---------------------------------------------------------- */
            $propertyRows = [
                ['PROP-KMRN0001', 'Kimironko Heights',        'residential', 'Kigali City',       'Gasabo',     'Kimironko', 'Bibare',    'Nyagatovu', 'KG 11 Ave, Kimironko',     -1.9441, 30.1260, 2018],
                ['PROP-NYMB0002', 'Nyamirambo Plaza',         'commercial',  'Kigali City',       'Nyarugenge', 'Nyamirambo','Rugarama',  'Kivugiza',  'KN 2 St, Nyamirambo',      -1.9784, 30.0436, 2015],
                ['PROP-KCKR0003', 'Gikondo Garden Residence', 'residential', 'Kigali City',       'Kicukiro',   'Gikondo',   'Kinunga',   'Rugende',   'KK 15 Rd, Gikondo',        -1.9712, 30.0771, 2020],
                ['PROP-MSZE0004', 'Musanze Volcano View',     'mixed_use',   'Northern Province', 'Musanze',    'Muhoza',    'Ruhengeri', 'Kabeza',    'Rue de Ruhengeri, Musanze', -1.4998, 29.6350, 2017],
                ['PROP-RBVU0005', 'Gisenyi Lakeside Court',   'residential', 'Western Province',  'Rubavu',     'Gisenyi',   'Umuganda',  'Kivumu',    'Avenue de la Paix, Gisenyi',-1.7026, 29.2563, 2019],
            ];

            $propertyIds = [];
            foreach ($propertyRows as $i => [$code, $name, $type, $prov, $dist, $sector, $cell, $village, $addr, $lat, $lng, $year]) {
                $propertyIds[] = $this->upsert(
                    'properties',
                    ['property_code' => $code],
                    [
                        'property_owner_id' => $ownerIds[$i],
                        'name'              => $name,
                        'property_type'     => $type,
                        'description'       => "{$name} is a well-maintained {$type} property in {$dist} District, close to public transport, shops and main roads.",
                        'country'           => 'Rwanda',
                        'province'          => $prov,
                        'district'          => $dist,
                        'sector'            => $sector,
                        'cell'              => $cell,
                        'village'           => $village,
                        'address'           => $addr,
                        'latitude'          => $lat,
                        'longitude'         => $lng,
                        'year_built'        => $year,
                        'total_buildings'   => 1,
                        'total_units'       => 1,
                        'status'            => 'active',
                        'created_by'        => $ownerUsers[$i],
                    ]
                );
            }

            /* ------------------------------------------------------------
             | PROPERTY IMAGES
             * ---------------------------------------------------------- */
            foreach ($propertyIds as $i => $pid) {
                $this->upsert('property_images', ['property_id' => $pid, 'sort_order' => 1], [
                    'image_path' => 'properties/seed/property-' . ($i + 1) . '.jpg',
                    'caption'    => 'Front view',
                    'is_primary' => 1,
                ]);
            }

            /* ------------------------------------------------------------
             | MANAGER ASSIGNMENTS
             * ---------------------------------------------------------- */
            foreach ($propertyIds as $i => $pid) {
                DB::table('property_manager_assignments')->updateOrInsert(
                    ['property_id' => $pid, 'property_manager_id' => $managerIds[$i]],
                    [
                        'start_date'  => $managerRows[$i][6],
                        'end_date'    => null,
                        'status'      => 'active',
                        'assigned_by' => $ownerUsers[$i],
                        'notes'       => 'Assigned as day-to-day manager of the property.',
                        'created_at'  => $this->now,
                        'updated_at'  => $this->now,
                    ]
                );
            }

            /* ------------------------------------------------------------
             | BUILDINGS
             * ---------------------------------------------------------- */
            $buildingRows = [
                ['BLK-A', 'Block A',        4],
                ['MAIN',  'Main Building',  3],
                ['BLK-1', 'Block 1',        2],
                ['BLK-B', 'Block B',        3],
                ['VILLA', 'Lakeside Villas',2],
            ];

            $buildingIds = [];
            foreach ($buildingRows as $i => [$code, $name, $floors]) {
                $buildingIds[] = $this->upsert(
                    'buildings',
                    ['property_id' => $propertyIds[$i], 'building_code' => $code],
                    [
                        'name'             => $name,
                        'description'      => "{$name} of {$propertyRows[$i][1]}.",
                        'number_of_floors' => $floors,
                        'status'           => 'active',
                    ]
                );
                $this->upsert('building_images', ['building_id' => $buildingIds[$i], 'sort_order' => 1], [
                    'image_path' => 'properties/buildings/seed-building-' . ($i + 1) . '.jpg',
                    'caption'    => $name,
                    'is_primary' => 1,
                ]);
            }

            /* ------------------------------------------------------------
             | FLOORS
             * ---------------------------------------------------------- */
            $floorRows = [
                [1, 'First Floor'],
                [0, 'Ground Floor'],
                [2, 'Second Floor'],
                [1, 'First Floor'],
                [0, 'Ground Floor'],
            ];

            $floorIds = [];
            foreach ($floorRows as $i => [$number, $name]) {
                $floorIds[] = $this->upsert(
                    'floors',
                    ['building_id' => $buildingIds[$i], 'floor_number' => $number],
                    [
                        'name'        => $name,
                        'description' => "{$name} of {$buildingRows[$i][1]}.",
                        'status'      => 'active',
                    ]
                );
                $this->upsert('floor_images', ['floor_id' => $floorIds[$i], 'sort_order' => 1], [
                    'image_path' => 'properties/floors/seed-floor-' . ($i + 1) . '.jpg',
                    'caption'    => $name,
                    'is_primary' => 1,
                ]);
            }

            /* ------------------------------------------------------------
             | UNITS  (monthly rent / deposit in RWF)
             * ---------------------------------------------------------- */
            $unitRows = [
                ['A101', 'apartment', 2, 2,  85.00, 350000, 700000, '2-bedroom apartment with balcony, backup water tank and parking.'],
                ['S01',  'shop',      0, 1,  42.50, 250000, 500000, 'Street-facing shop with shutter door and display windows.'],
                ['H12',  'house',     3, 3, 160.00, 600000,1200000, '3-bedroom family house with garden, DSTV-ready and fenced compound.'],
                ['B204', 'apartment', 1, 1,  48.00, 180000, 360000, 'Cosy 1-bedroom apartment with mountain view.'],
                ['V03',  'villa',     3, 2, 140.00, 450000, 900000, 'Lake-view villa 5 minutes from Lake Kivu beach.'],
            ];

            $unitIds = [];
            foreach ($unitRows as $i => [$no, $type, $bed, $bath, $area, $rent, $dep, $desc]) {
                $unitIds[] = $this->upsert(
                    'units',
                    ['building_id' => $buildingIds[$i], 'unit_number' => $no],
                    [
                        'floor_id'       => $floorIds[$i],
                        'unit_type'      => $type,
                        'bedrooms'       => $bed,
                        'bathrooms'      => $bath,
                        'area_sqm'       => $area,
                        'rent_amount'    => $rent,
                        'deposit_amount' => $dep,
                        'currency'       => 'RWF',
                        'status'         => 'occupied',
                        'description'    => $desc,
                    ]
                );
                $this->upsert('unit_images', ['unit_id' => $unitIds[$i], 'sort_order' => 1], [
                    'image_path' => 'properties/units/seed-unit-' . ($i + 1) . '.jpg',
                    'caption'    => "Unit {$no}",
                    'is_primary' => 1,
                ]);
            }

            /* ------------------------------------------------------------
             | TENANTS
             * ---------------------------------------------------------- */
            $tenantRows = [
                ['Iradukunda Sandrine',  '0788123401', '0722123401', 'Kigali City',       'Gasabo',     'KG 9 Ave, Kimironko',       'Iradukunda Pascal',  '0788555101'],
                ['Bizimana Alexis',      '0722456702', '0739456702', 'Kigali City',       'Nyarugenge', 'KN 5 Rd, Nyamirambo',       'Bizimana Chantal',   '0722555102'],
                ['Mutesi Josiane',       '0739789803', '0788789803', 'Kigali City',       'Kicukiro',   'KK 20 St, Gikondo',         'Mutesi Eugène',      '0739555103'],
                ['Hakizimana Damascène', '0781321904', '0722321904', 'Northern Province', 'Musanze',    'Cyuve, Musanze',            'Hakizimana Solange', '0781555104'],
                ['Umutoni Béatrice',     '0788654005', '0739654005', 'Western Province',  'Rubavu',     'Gisenyi, Rubavu',           'Umutoni Fidèle',     '0788555105'],
            ];

            $tenantIds = [];
            foreach ($tenantRows as $i => [$name, $phone, $alt, $prov, $dist, $addr, $ecName, $ecPhone]) {
                $tenantIds[] = $this->upsert(
                    'tenants',
                    ['tenant_number' => sprintf('TEN-20261001-SEED%02d', $i + 1)],
                    [
                        'user_id'                 => $tenantUsers[$i],
                        'created_by'              => $managerUsers[$i],
                        'full_name'               => $name,
                        'email'                   => DB::table('users')->where('id', $tenantUsers[$i])->value('email'),
                        'phone'                   => $phone,
                        'alternative_phone'       => $alt,
                        'country'                 => 'Rwanda',
                        'province'                => $prov,
                        'district'                => $dist,
                        'address'                 => $addr,
                        'emergency_contact_name'  => $ecName,
                        'emergency_contact_phone' => $ecPhone,
                        'status'                  => 'active',
                    ]
                );
            }

            /* ------------------------------------------------------------
             | TENANT APPLICATIONS
             * ---------------------------------------------------------- */
            $appRows = [
                ['2026-07-10', '2026-08-01', 2, 'employed',      'Documents verified, employer letter from a bank in Kigali.'],
                ['2026-08-02', '2026-09-01', 1, 'self_employed', 'Existing small business, RDB certificate provided.'],
                ['2026-08-15', '2026-09-15', 4, 'employed',      'Family of four, stable government employee.'],
                ['2026-09-01', '2026-10-01', 1, 'employed',      'Teacher at a local secondary school, good references.'],
                ['2026-09-10', '2026-10-01', 3, 'self_employed', 'Runs a guesthouse business in Rubavu, two months deposit paid.'],
            ];

            $appIds = [];
            foreach ($appRows as $i => [$date, $moveIn, $occupants, $employment, $notes]) {
                $appIds[] = $this->upsert(
                    'tenant_applications',
                    ['application_number' => sprintf('APP-2026-%04d', $i + 1)],
                    [
                        'tenant_id'            => $tenantIds[$i],
                        'unit_id'              => $unitIds[$i],
                        'reviewed_by'          => $ownerUsers[$i],
                        'application_date'     => $date,
                        'desired_move_in_date' => $moveIn,
                        'number_of_occupants'  => $occupants,
                        'offered_rent'         => $unitRows[$i][5],
                        'currency'             => 'RWF',
                        'employment_status'    => $employment,
                        'status'               => 'approved',
                        'decision_notes'       => $notes,
                        'reviewed_at'          => Carbon::parse($date)->addDays(3),
                    ]
                );
            }

            /* ------------------------------------------------------------
             | LEASES
             * ---------------------------------------------------------- */
            $leaseStarts = ['2026-08-01', '2026-09-01', '2026-09-15', '2026-10-01', '2026-10-01'];
            $leaseIds    = [];

            foreach ($leaseStarts as $i => $start) {
                $startDate = Carbon::parse($start);
                $leaseIds[] = $this->upsert(
                    'leases',
                    ['lease_number' => sprintf('LSE-2026-%04d', $i + 1)],
                    [
                        'tenant_id'              => $tenantIds[$i],
                        'unit_id'                => $unitIds[$i],
                        'tenant_application_id'  => $appIds[$i],
                        'start_date'             => $startDate->toDateString(),
                        'end_date'               => $startDate->copy()->addYear()->subDay()->toDateString(),
                        'monthly_rent'           => $unitRows[$i][5],
                        'deposit_amount'         => $unitRows[$i][6],
                        'currency'               => 'RWF',
                        'rent_due_day'           => 5,
                        'payment_frequency'      => 'monthly',
                        'status'                 => 'active',
                        'terms'                  => 'Rent is payable on or before the 5th of every month. Tenant is responsible for water (WASAC) and electricity (REG/EUCL) bills. Two months written notice is required before termination.',
                        'tenant_accepted_at'     => $startDate->copy()->subDays(5),
                        'move_in_confirmed_at'   => $startDate,
                        'activated_at'           => $startDate,
                    ]
                );
            }

            /* ------------------------------------------------------------
             | RENT INVOICES (October 2026 rent)
             |   1,2 paid | 3 paid in two instalments | 4 partial | 5 overdue
             * ---------------------------------------------------------- */
            $paidAmounts = [350000, 250000, 600000, 100000, 0];
            $invStatus   = ['paid', 'paid', 'paid', 'partial', 'overdue'];
            $invoiceIds  = [];

            foreach ($leaseIds as $i => $leaseId) {
                $total = $unitRows[$i][5];
                $invoiceIds[] = $this->upsert(
                    'rent_invoices',
                    ['invoice_number' => sprintf('INV-202610-%04d', $i + 1)],
                    [
                        'lease_id'             => $leaseId,
                        'tenant_id'            => $tenantIds[$i],
                        'unit_id'              => $unitIds[$i],
                        'invoice_type'         => 'rent',
                        'billing_period_start' => '2026-10-01',
                        'billing_period_end'   => '2026-10-31',
                        'issue_date'           => '2026-10-01',
                        'due_date'             => '2026-10-05',
                        'subtotal'             => $total,
                        'tax_amount'           => 0,
                        'total_amount'         => $total,
                        'amount_paid'          => $paidAmounts[$i],
                        'balance_due'          => $total - $paidAmounts[$i],
                        'description'          => 'Monthly rent for October 2026',
                        'currency'             => 'RWF',
                        'status'               => $invStatus[$i],
                    ]
                );
            }

            /* ------------------------------------------------------------
             | RENT PAYMENTS  (+ allocations)
             |   [invoice index, method, provider, reference, amount, date]
             * ---------------------------------------------------------- */
            $paymentRows = [
                [0, 'mobile_money',  'MTN MoMo',      'MP261003.1142.A38271', 350000, '2026-10-03'],
                [1, 'bank_transfer', 'Bank of Kigali','BK-TRF-20261004-7781',  250000, '2026-10-04'],
                [2, 'mobile_money',  'Airtel Money',  'AM261002.0915.B55102', 400000, '2026-10-02'],
                [3, 'cash',          null,            'CASH-RCPT-0004',       100000, '2026-10-06'],
                [2, 'bank_transfer', 'I&M Bank',      'IM-TRF-20261008-3394', 200000, '2026-10-08'],
            ];

            foreach ($paymentRows as $i => [$invIdx, $method, $provider, $ref, $amount, $date]) {
                $paymentId = $this->upsert(
                    'rent_payments',
                    ['payment_number' => sprintf('PAY-202610-%04d', $i + 1)],
                    [
                        'tenant_id'             => $tenantIds[$invIdx],
                        'lease_id'              => $leaseIds[$invIdx],
                        'unit_id'               => $unitIds[$invIdx],
                        'recorded_by'           => $managerUsers[$invIdx],
                        'payment_method'        => $method,
                        'provider'              => $provider,
                        'transaction_reference' => $ref,
                        'amount'                => $amount,
                        'currency'              => 'RWF',
                        'payment_date'          => $date,
                        'status'                => 'completed',
                        'notes'                 => 'October 2026 rent payment',
                    ]
                );

                DB::table('rent_payment_allocations')->updateOrInsert(
                    ['rent_payment_id' => $paymentId, 'rent_invoice_id' => $invoiceIds[$invIdx]],
                    [
                        'amount'       => $amount,
                        'allocated_at' => $date,
                        'notes'        => 'Allocated to October 2026 rent invoice',
                        'created_at'   => $this->now,
                        'updated_at'   => $this->now,
                    ]
                );
            }

            /* ------------------------------------------------------------
             | PROPERTY EXPENSES
             * ---------------------------------------------------------- */
            $expenseRows = [
                ['repairs',     'Roof leakage repair',        'Repair of roof leakage after heavy rains.',               85000,  '2026-10-12', 'cash',          'Mugisha Roofing Works',     'RCPT-0112'],
                ['utilities',   'Common area water bill',     'WASAC water bill for shared facilities.',                 42000,  '2026-10-15', 'mobile_money',  'WASAC Ltd',                 'WASAC-102938'],
                ['security',    'Monthly security guards',    'Night and day security guards for October.',              180000, '2026-10-30', 'bank_transfer', 'Intersec Security Rwanda',  'INV-IS-2210'],
                ['cleaning',    'Compound cleaning service',  'Cleaning of corridors, stairs and compound.',             60000,  '2026-10-28', 'mobile_money',  'Isuku Cleaning Services',   'MP261028.0830.C7710'],
                ['maintenance', 'Electrical wiring maintenance','Replacement of faulty breakers and common-area lighting.', 120000, '2026-10-20', 'cash',          'Kabera Electrical',         'RCPT-0087'],
            ];

            foreach ($expenseRows as $i => [$cat, $title, $desc, $amount, $date, $method, $vendor, $ref]) {
                $this->upsert(
                    'property_expenses',
                    ['expense_number' => sprintf('EXP-SEED-%04d', $i + 1)],
                    [
                        'property_id'      => $propertyIds[$i],
                        'recorded_by'      => $ownerUsers[$i],
                        'category'         => $cat,
                        'title'            => $title,
                        'description'      => $desc,
                        'amount'           => $amount,
                        'currency'         => 'RWF',
                        'expense_date'     => $date,
                        'payment_method'   => $method,
                        'vendor_name'      => $vendor,
                        'reference_number' => $ref,
                        'status'           => 'paid',
                        'notes'            => $desc,
                    ]
                );
            }

            /* ------------------------------------------------------------
             | MAINTENANCE REQUESTS (+ one activity each)
             * ---------------------------------------------------------- */
            $mrRows = [
                ['Kitchen tap leaking',            'The kitchen tap has been dripping for three days and wastes water.',   'plumbing',   'normal', 'in_progress', 'Water is dripping from the base of the tap.'],
                ['Shop shutter door stuck',        'The shutter door is hard to open and close, locks do not align.',       'general',    'high',   'scheduled',   'Technician scheduled to repair the shutter rails.'],
                ['Power sockets not working',      'Two sockets in the living room have no power since yesterday.',          'electrical', 'high',   'submitted',   'New request submitted by tenant.'],
                ['Broken window latch',            'Bedroom window latch is broken and the window cannot be locked.',        'security',   'normal', 'resolved',    'Latch replaced and tested by the maintenance team.'],
                ['Water heater not heating',       'The electric water heater in the bathroom no longer heats water.',       'appliance',  'low',    'submitted',   'New request submitted by tenant.'],
            ];

            foreach ($mrRows as $i => [$title, $desc, $cat, $priority, $status, $note]) {
                $requestId = $this->upsert(
                    'maintenance_requests',
                    ['request_number' => sprintf('MR-2026-%04d', $i + 1)],
                    [
                        'tenant_id'          => $tenantIds[$i],
                        'unit_id'            => $unitIds[$i],
                        'title'              => $title,
                        'description'        => $desc,
                        'category'           => $cat,
                        'priority'           => $priority,
                        'status'             => $status,
                        'assigned_to'        => in_array($status, ['in_progress', 'scheduled', 'resolved']) ? $managerUsers[$i] : null,
                        'preferred_visit_at' => Carbon::parse('2026-10-25 10:00:00')->addDays($i),
                        'scheduled_at'       => in_array($status, ['scheduled', 'in_progress', 'resolved']) ? Carbon::parse('2026-10-27 09:00:00')->addDays($i) : null,
                        'resolved_at'        => $status === 'resolved' ? Carbon::parse('2026-10-30 15:30:00') : null,
                        'resolution_notes'   => $status === 'resolved' ? $note : null,
                        'estimated_cost'     => [15000, 40000, 25000, 12000, 60000][$i],
                        'actual_cost'        => $status === 'resolved' ? 10000 : null,
                        'created_by'         => $tenantUsers[$i],
                    ]
                );

                DB::table('maintenance_request_activities')->updateOrInsert(
                    ['maintenance_request_id' => $requestId, 'activity_type' => 'status_changed'],
                    [
                        'user_id'     => $managerUsers[$i],
                        'description' => $note,
                        'old_status'  => 'submitted',
                        'new_status'  => $status,
                        'metadata'    => json_encode(['source' => 'seeder', 'priority' => $priority], JSON_UNESCAPED_UNICODE),
                        'created_at'  => $this->now,
                        'updated_at'  => $this->now,
                    ]
                );
            }
        });
    }
}
