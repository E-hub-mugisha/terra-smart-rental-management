<?php

use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\LeaseLifecycleController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyExpenseController;
use App\Http\Controllers\Admin\PropertyImageController;
use App\Http\Controllers\Admin\PropertyManagerController;
use App\Http\Controllers\Admin\PropertyOwnerController;
use App\Http\Controllers\Admin\PropertyStructureController;
use App\Http\Controllers\Admin\RentInvoiceController;
use App\Http\Controllers\Admin\RentPaymentController;
use App\Http\Controllers\Admin\TenantApplicationController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicTenantApplicationController;
use App\Http\Controllers\PublicPropertyController;
use App\Http\Controllers\RentalUnitController;
use App\Http\Controllers\TenantPortal\MaintenanceRequestController as TenantMaintenanceRequestController;
use App\Http\Controllers\PropertyManager\MaintenanceRequestController as ManagerMaintenanceRequestController;
use App\Http\Controllers\TenantPortal\RentalController;
use App\Http\Controllers\TenantPortal\ProfileController as TenantProfileController;
use App\Http\Controllers\TenantPortal\DashboardController as TenantDashboardController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Public property pages (property → building → units)
Route::get('/properties', [PublicPropertyController::class, 'index'])
    ->name('listings.index');

Route::get('/properties/{property}', [PublicPropertyController::class, 'show'])
    ->whereNumber('property')
    ->name('listings.show');

// Public rental listings
Route::get('/rentals', [RentalUnitController::class, 'index'])
    ->name('rentals.index');

Route::get('/rentals/{unit}', [RentalUnitController::class, 'show'])
    ->name('rentals.show');

// Submitting an application requires authentication
Route::middleware('auth')->group(function () {
    Route::post(
        '/rentals/{unit}/apply',
        [PublicTenantApplicationController::class, 'store']
    )->name('rentals.apply');
});

// Tenant portal
Route::middleware(['auth'])
    ->prefix('tenant-portal')
    ->name('tenant-portal.')
    ->group(function () {

        Route::get('/dashboard', [TenantDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/profile', [TenantProfileController::class, 'show'])->name('profile.index');
        Route::get('/profile/edit', [TenantProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::put('/profile', [TenantProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('/rentals', [RentalController::class, 'index'])
            ->name('rentals.index');
        Route::get(
            '/applications',
            [App\Http\Controllers\TenantPortal\TenantApplicationController::class, 'index']
        )->name('applications.index');

        Route::get(
            '/applications/{tenantApplication}',
            [App\Http\Controllers\TenantPortal\TenantApplicationController::class, 'show']
        )->name('applications.show');

        Route::get('/leases', [App\Http\Controllers\TenantPortal\TenantLeaseController::class, 'index'])
            ->name('leases.index');

        Route::get('/leases/{lease}', [App\Http\Controllers\TenantPortal\TenantLeaseController::class, 'show'])
            ->name('leases.show');

        Route::post('/leases/{lease}/accept', [App\Http\Controllers\TenantPortal\TenantLeaseController::class, 'accept'])
            ->name('leases.accept');

        Route::get('/rent-invoices', [App\Http\Controllers\TenantPortal\RentInvoiceController::class, 'index'])
            ->name('rent-invoices.index');

        Route::get('/rent-invoices/{invoice}', [App\Http\Controllers\TenantPortal\RentInvoiceController::class, 'show'])
            ->whereNumber('invoice')
            ->name('rent-invoices.show');

        Route::get('/payments', [App\Http\Controllers\TenantPortal\RentPaymentController::class, 'index'])
            ->name('payments.index');

        Route::get('/payments/{payment}', [App\Http\Controllers\TenantPortal\RentPaymentController::class, 'show'])
            ->whereNumber('payment')
            ->name('payments.show');

        Route::get('/maintenance-requests', [TenantMaintenanceRequestController::class, 'index'])
            ->name('maintenance-requests.index');

        Route::get('/maintenance-requests/create', [TenantMaintenanceRequestController::class, 'create'])
            ->name('maintenance-requests.create');

        Route::post('/maintenance-requests', [TenantMaintenanceRequestController::class, 'store'])
            ->name('maintenance-requests.store');

        Route::get('/maintenance-requests/{maintenanceRequest}', [TenantMaintenanceRequestController::class, 'show'])
            ->name('maintenance-requests.show');
    });

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::get('/forgot-password', [
        PasswordResetController::class,
        'createRequest',
    ])->name('password.request');

    Route::post('/forgot-password', [
        PasswordResetController::class,
        'sendLink',
    ])->middleware('throttle:5,1')->name('password.email');

    Route::get('/reset-password/{token}', [
        PasswordResetController::class,
        'createReset',
    ])->name('password.reset');

    Route::post('/reset-password', [
        PasswordResetController::class,
        'reset',
    ])->middleware('throttle:5,1')->name('password.store');
});

Route::post('/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin')
        ->group(function () {

            Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/users', [UserController::class, 'index'])
                ->name('users.index');

            Route::post('/users', [UserController::class, 'store'])
                ->name('users.store');

            Route::put('/users/{user}', [UserController::class, 'update'])
                ->name('users.update');

            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])
                ->name('users.role.update');

            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->name('users.destroy');

            Route::patch('/users/{user}/role', [
                UserController::class,
                'updateRole',
            ])->name('users.role.update');

            Route::resource('owners', PropertyOwnerController::class)
                ->parameters(['owners' => 'owner'])
                ->except(['destroy']);

            Route::delete('/owners/{owner}', [
                PropertyOwnerController::class,
                'destroy',
            ])->name('owners.destroy');

            Route::post('owners/{owner}/resend-credentials', [PropertyOwnerController::class, 'resendCredentials'])
                ->name('owners.resend-credentials');

            Route::resource('managers', PropertyManagerController::class)
                ->parameters(['managers' => 'manager'])
                ->except(['destroy']);

            Route::delete('/managers/{manager}', [
                PropertyManagerController::class,
                'destroy',
            ])->name('managers.destroy');

            Route::post('managers/{manager}/resend-credentials', [PropertyManagerController::class, 'resendCredentials'])
                ->name('managers.resend-credentials');

            Route::resource('tenants', TenantController::class);
            Route::post('tenants/{tenant}/resend-credentials', [TenantController::class, 'resendCredentials'])
                ->name('tenants.resend-credentials');

            Route::get(
                'tenant-applications',
                [TenantApplicationController::class, 'index']
            )->name('tenant-applications.index');

            Route::get('/tenant-applications/create', [
                TenantApplicationController::class,
                'create'
            ])->name('tenant-applications.create');

            Route::get(
                'tenant-applications/{tenantApplication}',
                [TenantApplicationController::class, 'show']
            )->name('tenant-applications.show');



            Route::post('/tenant-applications', [
                TenantApplicationController::class,
                'store'
            ])->name('tenant-applications.store');

            Route::post(
                'tenant-applications/{tenantApplication}/approve',
                [TenantApplicationController::class, 'approve']
            )->name('tenant-applications.approve');

            Route::post(
                'tenant-applications/{tenantApplication}/reject',
                [TenantApplicationController::class, 'reject']
            )->name('tenant-applications.reject');

            Route::resource('properties', PropertyController::class)
                ->except(['destroy']);

            Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])
                ->name('properties.destroy');

            Route::get('/leases', [LeaseLifecycleController::class, 'index'])
                ->name('leases.index');

            Route::get('/leases/{lease}', [LeaseLifecycleController::class, 'show'])
                ->name('leases.show');

            Route::post('/leases/{lease}/send-to-tenant', [LeaseLifecycleController::class, 'sendToTenant'])
                ->name('leases.send-to-tenant');

            Route::post('/leases/{lease}/recall', [LeaseLifecycleController::class, 'recall'])
                ->name('leases.recall');

            Route::post(
                '/leases/{lease}/generate-required-invoices',
                [LeaseLifecycleController::class, 'generateRequiredInvoices']
            )->name('leases.generate-required-invoices');

            Route::post(
                '/leases/{lease}/confirm-move-in',
                [LeaseLifecycleController::class, 'confirmMoveIn']
            )->name('leases.confirm-move-in');

            Route::post(
                '/leases/{lease}/activate',
                [LeaseLifecycleController::class, 'activate']
            )->name('leases.activate');

            Route::get('/payments', [RentPaymentController::class, 'index'])
                ->name('payments.index');

            Route::get('/payments/create', [RentPaymentController::class, 'create'])
                ->name('payments.create');

            Route::post('/payments', [RentPaymentController::class, 'store'])
                ->name('payments.store');

            Route::get('/payments/{payment}', [RentPaymentController::class, 'show'])
                ->name('payments.show');

            Route::post('/payments/{payment}/confirm', [RentPaymentController::class, 'confirm'])
                ->name('payments.confirm');

            Route::post('/payments/{payment}/allocate', [RentPaymentController::class, 'allocate'])
                ->name('payments.allocate');

            Route::get('/rent-invoices', [RentInvoiceController::class, 'index'])
                ->name('rent-invoices.index');

            Route::get('/rent-invoices/{rentInvoice}', [RentInvoiceController::class, 'show'])
                ->name('rent-invoices.show');

            Route::post(
                '/rent-invoices/generate',
                [RentInvoiceController::class, 'generate']
            )->name('rent-invoices.generate');

            Route::get('/financial-reports', [FinancialReportController::class, 'index'])
                ->name('financial-reports.index');

            Route::get('/financial-reports/export', [FinancialReportController::class, 'export'])
                ->name('financial-reports.export');

            Route::resource('property-expenses', PropertyExpenseController::class)
                ->except(['destroy'])
                ->names('property-expenses');

            Route::delete(
                'property-expenses/{propertyExpense}',
                [PropertyExpenseController::class, 'destroy']
            )->name('property-expenses.destroy');

            Route::prefix('properties/{property}')->name('properties.')->group(function () {
                Route::post('/buildings', [PropertyStructureController::class, 'storeBuilding'])
                    ->name('buildings.store');

                Route::put('/buildings/{building}', [PropertyStructureController::class, 'updateBuilding'])
                    ->name('buildings.update');

                Route::delete('/buildings/{building}', [PropertyStructureController::class, 'deactivateBuilding'])
                    ->name('buildings.destroy');

                Route::post('/floors', [PropertyStructureController::class, 'storeFloor'])
                    ->name('floors.store');

                Route::put('/floors/{floor}', [PropertyStructureController::class, 'updateFloor'])
                    ->name('floors.update');

                Route::delete('/floors/{floor}', [PropertyStructureController::class, 'deactivateFloor'])
                    ->name('floors.destroy');

                Route::post('/units', [PropertyStructureController::class, 'storeUnit'])
                    ->name('units.store');

                Route::put('/units/{unit}', [PropertyStructureController::class, 'updateUnit'])
                    ->name('units.update');

                Route::delete('/units/{unit}', [PropertyStructureController::class, 'deactivateUnit'])
                    ->name('units.destroy');

                Route::put(
                    'buildings/{building}/images/{image}/primary',
                    [PropertyImageController::class, 'setPrimaryBuilding']
                )->name('buildings.images.primary');

                Route::delete(
                    'buildings/{building}/images/{image}',
                    [PropertyImageController::class, 'deleteBuildingImage']
                )->name('buildings.images.destroy');

                // Floor images
                Route::put(
                    'floors/{floor}/images/{image}/primary',
                    [PropertyImageController::class, 'setPrimaryFloor']
                )->name('floors.images.primary');

                Route::delete(
                    'floors/{floor}/images/{image}',
                    [PropertyImageController::class, 'deleteFloorImage']
                )->name('floors.images.destroy');

                // Unit images
                Route::put(
                    'units/{unit}/images/{image}/primary',
                    [PropertyImageController::class, 'setPrimaryUnit']
                )->name('units.images.primary');

                Route::delete(
                    'units/{unit}/images/{image}',
                    [PropertyImageController::class, 'deleteUnitImage']
                )->name('units.images.destroy');
            });
        });

    Route::view('/manager/dashboard', 'dashboards.manager')
        ->middleware('role:manager,admin')
        ->name('manager.dashboard');

    Route::view('/tenant/dashboard', 'dashboards.tenant')
        ->middleware('role:tenant,admin')
        ->name('tenant.dashboard');
});



Route::middleware(['auth'])
    ->prefix('property-manager')
    ->name('property-manager.')
    ->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\PropertyManager\DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/properties', [App\Http\Controllers\PropertyManager\PropertyController::class, 'index'])
            ->name('properties.index');

        Route::get('/properties/{property}', [App\Http\Controllers\PropertyManager\PropertyController::class, 'show'])
            ->whereNumber('property')
            ->name('properties.show');

        Route::get('/properties/create', [
            App\Http\Controllers\PropertyManager\PropertyController::class,
            'create'
        ])->name('properties.create');

        Route::post('/properties', [
            App\Http\Controllers\PropertyManager\PropertyController::class,
            'store'
        ])->name('properties.store');

        Route::post('/properties/{property}/buildings', [App\Http\Controllers\PropertyManager\PropertyController::class, 'storeBuilding'])
            ->whereNumber('property')
            ->name('properties.buildings.store');

        Route::put('/properties/{property}/buildings/{building}', [App\Http\Controllers\PropertyManager\PropertyController::class, 'updateBuilding'])
            ->whereNumber('property')
            ->whereNumber('building')
            ->name('properties.buildings.update');

        Route::post('/properties/{property}/floors', [App\Http\Controllers\PropertyManager\PropertyController::class, 'storeFloor'])
            ->whereNumber('property')
            ->name('properties.floors.store');

        Route::put('/properties/{property}/floors/{floor}', [App\Http\Controllers\PropertyManager\PropertyController::class, 'updateFloor'])
            ->whereNumber('property')
            ->whereNumber('floor')
            ->name('properties.floors.update');

        Route::post('/properties/{property}/units', [App\Http\Controllers\PropertyManager\PropertyController::class, 'storeUnit'])
            ->whereNumber('property')
            ->name('properties.units.store');

        Route::put('/properties/{property}/units/{unit}', [App\Http\Controllers\PropertyManager\PropertyController::class, 'updateUnit'])
            ->whereNumber('property')
            ->whereNumber('unit')
            ->name('properties.units.update');

        Route::post(
            '/properties/{property}/managers',
            [App\Http\Controllers\PropertyManager\PropertyController::class, 'assignManager']
        )->whereNumber('property')->name('properties.managers.assign');

        Route::resource('tenants', App\Http\Controllers\PropertyManager\TenantController::class);

        Route::get('/applications', [App\Http\Controllers\PropertyManager\TenantApplicationController::class, 'index'])
            ->name('applications.index');

        Route::get('/applications/{application}', [App\Http\Controllers\PropertyManager\TenantApplicationController::class, 'show'])
            ->name('applications.show');

        Route::patch('/applications/{application}/review', [App\Http\Controllers\PropertyManager\TenantApplicationController::class, 'startReview'])
            ->name('applications.review');

        Route::patch('/applications/{application}/approve', [App\Http\Controllers\PropertyManager\TenantApplicationController::class, 'approve'])
            ->name('applications.approve');

        Route::patch('/applications/{application}/reject', [App\Http\Controllers\PropertyManager\TenantApplicationController::class, 'reject'])
            ->name('applications.reject');

        Route::get('/leases', [App\Http\Controllers\PropertyManager\LeaseController::class, 'index'])
            ->name('leases.index');

        Route::get('/leases/{lease}/edit', [App\Http\Controllers\PropertyManager\LeaseController::class, 'edit'])
            ->name('leases.edit');

        Route::put('/leases/{lease}', [App\Http\Controllers\PropertyManager\LeaseController::class, 'update'])
            ->name('leases.update');

        Route::get('/leases/{lease}', [App\Http\Controllers\PropertyManager\LeaseController::class, 'show'])
            ->name('leases.show');

        Route::patch('/leases/{lease}/send-for-signature', [App\Http\Controllers\PropertyManager\LeaseController::class, 'sendForSignature'])
            ->name('leases.send-for-signature');

        Route::patch('/leases/{lease}/recall', [App\Http\Controllers\PropertyManager\LeaseController::class, 'recall'])
            ->name('leases.recall');

        Route::patch('/leases/{lease}/confirm-move-in', [App\Http\Controllers\PropertyManager\LeaseController::class, 'confirmMoveIn'])
            ->name('leases.confirm-move-in');

        Route::patch('/leases/{lease}/activate', [App\Http\Controllers\PropertyManager\LeaseController::class, 'activate'])
            ->name('leases.activate');

        Route::patch('/leases/{lease}/terminate', [App\Http\Controllers\PropertyManager\LeaseController::class, 'terminate'])
            ->name('leases.terminate');

        Route::post(
            '/leases/{lease}/generate-required-invoices',
            [\App\Http\Controllers\PropertyManager\LeaseController::class, 'generateRequiredInvoices']
        )->name('leases.generate-required-invoices');

        Route::get('/invoices', [App\Http\Controllers\PropertyManager\RentInvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::get('/invoices/{invoice}', [App\Http\Controllers\PropertyManager\RentInvoiceController::class, 'show'])
            ->whereNumber('invoice')
            ->name('invoices.show');

        Route::prefix('payments')->name('payments.')->controller(App\Http\Controllers\PropertyManager\RentPaymentController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{payment}', 'show')->whereNumber('payment')->name('show');

            Route::post('/{payment}/confirm', 'confirm')
                ->whereNumber('payment')
                ->name('confirm');

            Route::post('/{payment}/allocate', 'allocate')
                ->whereNumber('payment')
                ->name('allocate');
        });

        Route::resource('expenses', App\Http\Controllers\PropertyManager\PropertyExpenseController::class)
            ->except(['destroy']);

        Route::delete(
            'expenses/{expense}',
            [App\Http\Controllers\PropertyManager\PropertyExpenseController::class, 'destroy']
        )->name('expenses.destroy');

        Route::get('/reports', [App\Http\Controllers\PropertyManager\ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/maintenance-requests', [ManagerMaintenanceRequestController::class, 'index'])
            ->name('maintenance-requests.index');

        Route::get('/maintenance-requests/create', [ManagerMaintenanceRequestController::class, 'create'])
            ->name('maintenance-requests.create');

        Route::post('/maintenance-requests', [ManagerMaintenanceRequestController::class, 'store'])
            ->name('maintenance-requests.store');

        Route::get('/maintenance-requests/{maintenanceRequest}/edit', [ManagerMaintenanceRequestController::class, 'edit'])
            ->name('maintenance-requests.edit');

        Route::put('/maintenance-requests/{maintenanceRequest}', [ManagerMaintenanceRequestController::class, 'update'])
            ->name('maintenance-requests.update');

        Route::delete('/maintenance-requests/{maintenanceRequest}', [ManagerMaintenanceRequestController::class, 'destroy'])
            ->name('maintenance-requests.destroy');

        Route::get('/maintenance-requests/{maintenanceRequest}', [ManagerMaintenanceRequestController::class, 'show'])
            ->name('maintenance-requests.show');

        Route::patch('/maintenance-requests/{maintenanceRequest}/status', [ManagerMaintenanceRequestController::class, 'updateStatus'])
            ->name('maintenance-requests.status');
    });

// route to run seeder for testing purposes
Route::get('/run-seeder', function () {
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\TestSeeder',
        '--force' => true,
    ]);
    return 'Seeder executed successfully.';
});

// Messaging & notifications (brief §12–13) — shared by every signed-in role.
Route::middleware('auth')->group(function () {
    Route::controller(App\Http\Controllers\MessageController::class)
        ->prefix('messages')
        ->name('messages.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/new', 'create')->name('create');
            Route::post('/', 'store')->middleware('throttle:20,1')->name('store');
            Route::get('/attachments/{attachment}', 'attachment')->name('attachments.show');
            Route::get('/{conversation}', 'show')->name('show');
            Route::get('/{conversation}/poll', 'poll')->middleware('throttle:120,1')->name('poll');
            Route::post('/{conversation}/reply', 'reply')->middleware('throttle:40,1')->name('reply');
            Route::patch('/{conversation}/archive', 'archive')->name('archive');
        });

    Route::controller(App\Http\Controllers\NotificationController::class)
        ->prefix('notifications')
        ->name('notifications.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/read-all', 'readAll')->name('read-all');
            Route::put('/preferences', 'updatePreferences')->name('preferences');
            Route::get('/{id}/open', 'open')->name('open');
            Route::delete('/{id}', 'destroy')->name('destroy');
        });
});

// Move-in / move-out inspections & deposit settlement (brief §16) — staff and tenants.
Route::middleware('auth')
    ->controller(App\Http\Controllers\InspectionController::class)
    ->prefix('inspections')
    ->name('inspections.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/media/{media}', 'media')->name('media.show');
        Route::get('/{inspection}', 'show')->name('show');
        Route::get('/{inspection}/edit', 'edit')->name('edit');
        Route::put('/{inspection}', 'update')->name('update');
        Route::post('/{inspection}/media', 'uploadMedia')->middleware('throttle:30,1')->name('media.store');
        Route::delete('/{inspection}/media/{media}', 'destroyMedia')->name('media.destroy');
        Route::post('/{inspection}/submit', 'submit')->name('submit');
        Route::post('/{inspection}/respond', 'respond')->name('respond');
        Route::post('/{inspection}/settle-deposit', 'settleDeposit')->name('settle');
        Route::get('/{inspection}/report', 'report')->name('report');
    });

// Online rent payment, receipts and ledger (brief §9–11).
Route::post('/payments/callback/{provider}', [App\Http\Controllers\OnlinePaymentController::class, 'callback'])
    ->middleware('throttle:120,1')
    ->name('payments.callback');
Route::get('/receipts/shared/{payment}', [App\Http\Controllers\ReceiptController::class, 'shared'])
    ->middleware('signed')
    ->name('receipts.shared');

Route::middleware('auth')->group(function () {
    Route::controller(App\Http\Controllers\OnlinePaymentController::class)->group(function () {
        Route::get('/pay/invoices/{invoice}', 'create')->name('payments.online.create');
        Route::post('/pay/invoices/{invoice}', 'store')->middleware('throttle:10,1')->name('payments.online.store');
        Route::get('/pay/{intent}', 'show')->name('payments.online.show');
        Route::get('/pay/{intent}/status', 'status')->middleware('throttle:60,1')->name('payments.online.status');
        Route::post('/pay/{intent}/sandbox', 'sandboxDecision')->name('payments.online.sandbox');
    });

    Route::get('/receipts/{payment}', [App\Http\Controllers\ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/ledger/leases/{lease}', [App\Http\Controllers\LedgerController::class, 'show'])->name('ledger.show');

    // Documents & tenant KYC (brief §6, MVP #14).
    Route::controller(App\Http\Controllers\DocumentController::class)->prefix('documents')->name('documents.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
        Route::put('/identity', 'updateIdentity')->name('identity');
        Route::get('/{document}', 'download')->name('download');
        Route::post('/{document}/review', 'review')->name('review');
        Route::delete('/{document}', 'destroy')->name('destroy');
    });

    // Lease renewal & move-out notice.
    Route::post('/leases/{lease}/requests', [App\Http\Controllers\LeaseRequestController::class, 'store'])->name('lease-requests.store');
    Route::post('/lease-requests/{leaseRequest}/respond', [App\Http\Controllers\LeaseRequestController::class, 'respond'])->name('lease-requests.respond');

    // Audit trail (admins only).
    Route::get('/admin/audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])
        ->middleware('role:admin')
        ->name('admin.audit-logs.index');
});

// ---- Batch 2: contracts, service providers, finance, agents, reports ----
Route::middleware('auth')->group(function () {
    // Lease contract builder (brief §8).
    Route::controller(App\Http\Controllers\LeaseContractController::class)->prefix('leases/{lease}/contract')->name('contracts.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::get('/edit', 'edit')->name('edit');
        Route::post('/generate', 'generate')->name('generate');
        Route::put('/', 'update')->name('update');
        Route::post('/finalize', 'finalize')->name('finalize');
    });
    Route::resource('lease-templates', App\Http\Controllers\LeaseTemplateController::class)->except('show');

    // Service provider network & maintenance work-flow (brief §14–15).
    Route::get('/service-providers', [App\Http\Controllers\ServiceProviderController::class, 'index'])->name('service-providers.index');
    Route::post('/service-providers', [App\Http\Controllers\ServiceProviderController::class, 'store'])->name('service-providers.store');
    Route::put('/service-providers/{serviceProvider}', [App\Http\Controllers\ServiceProviderController::class, 'update'])->name('service-providers.update');
    Route::post('/service-providers/{serviceProvider}/verify', [App\Http\Controllers\ServiceProviderController::class, 'verify'])->name('service-providers.verify');
    Route::post('/maintenance/{maintenanceRequest}/assign', [App\Http\Controllers\MaintenanceWorkController::class, 'assign'])->name('maintenance.assign');
    Route::post('/maintenance/{maintenanceRequest}/tenant-confirm', [App\Http\Controllers\MaintenanceWorkController::class, 'tenantConfirm'])->name('maintenance.tenant-confirm');

    Route::middleware('role:technician')->prefix('service-provider')->name('service-provider.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\MaintenanceWorkController::class, 'technicianIndex'])->name('dashboard');
        Route::get('/jobs', [App\Http\Controllers\MaintenanceWorkController::class, 'technicianIndex'])->name('maintenance.index');
        Route::get('/jobs/{maintenanceRequest}', [App\Http\Controllers\MaintenanceWorkController::class, 'technicianShow'])->name('maintenance.show');
        Route::patch('/jobs/{maintenanceRequest}', [App\Http\Controllers\MaintenanceWorkController::class, 'technicianUpdate'])->name('maintenance.update');
    });

    // Finance workspace for accountants (brief §4F).
    Route::controller(App\Http\Controllers\FinanceController::class)->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/payments', 'payments')->name('payments.index');
        Route::get('/invoices', 'invoices')->name('rent-invoices.index');
        Route::get('/expenses', 'expenses')->name('expenses.index');
    });
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [App\Http\Controllers\ReportController::class, 'show'])->name('reports.show');
    Route::get('/finance/reports', fn () => redirect()->route('reports.index'))->name('finance.reports.index');

    // Agent workspace (brief §4D).
    Route::controller(App\Http\Controllers\AgentController::class)->prefix('agent')->name('agent.')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/properties', 'properties')->name('properties.index');
        Route::get('/applications', 'applications')->name('applications.index');
        Route::get('/commissions', 'commissions')->name('commissions.index');
    });

    // Accountant / agent assignments and commissions.
    Route::controller(App\Http\Controllers\PropertyRoleController::class)->prefix('property-roles')->name('property-roles.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::delete('/{propertyRole}', 'destroy')->name('destroy');
        Route::post('/commissions', 'storeCommission')->name('commissions.store');
        Route::patch('/commissions/{commission}', 'updateCommission')->name('commissions.update');
    });
});

// ---- Batch 3: security, language, maps, API docs ----
Route::middleware('guest')->controller(App\Http\Controllers\Auth\TwoFactorController::class)->group(function () {
    Route::get('/two-factor', 'challenge')->name('two-factor.challenge');
    Route::post('/two-factor', 'verify')->middleware('throttle:10,1')->name('two-factor.verify');
    Route::post('/two-factor/resend', 'resend')->middleware('throttle:3,1')->name('two-factor.resend');
});
Route::post('/locale', [App\Http\Controllers\AccountSecurityController::class, 'locale'])->name('locale.update');
Route::get('/properties-map', [App\Http\Controllers\MapController::class, 'public'])->name('listings.map');
Route::view('/api/docs', 'api.docs')->name('api.docs');

Route::middleware('auth')->group(function () {
    Route::controller(App\Http\Controllers\AccountSecurityController::class)->prefix('account/security')->name('account.security')->group(function () {
        Route::get('/', 'show');
        Route::put('/two-factor', 'twoFactor')->name('.two-factor');
        Route::post('/tokens', 'createToken')->middleware('throttle:10,1')->name('.tokens.store');
        Route::delete('/tokens/{token}', 'revokeToken')->name('.tokens.destroy');
    });
    Route::get('/portfolio/map', [App\Http\Controllers\MapController::class, 'portfolio'])->name('portfolio.map');
});

/*
|--------------------------------------------------------------------------
| Terra admin: subscribers, subscription contracts and field staff
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::controller(App\Http\Controllers\Admin\SubscriberController::class)->prefix('subscribers')->name('subscribers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{subscriber}', 'show')->name('show');
        Route::post('/{subscriber}/email', 'email')->middleware('throttle:20,1')->name('email');
        Route::post('/{subscriber}/calls', 'logCall')->name('calls.store');
        Route::post('/{subscriber}/calls/browser', 'startCall')->middleware('throttle:10,1')->name('calls.start');
    });

    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::controller(App\Http\Controllers\Admin\SubscriptionContractController::class)->group(function () {
            Route::get('/contracts', 'index')->name('contracts.index');
            Route::get('/contracts/create', 'create')->name('contracts.create');
            Route::post('/contracts', 'store')->name('contracts.store');
            Route::get('/contracts/{contract}', 'show')->name('contracts.show');
            Route::get('/contracts/{contract}/edit', 'edit')->name('contracts.edit');
            Route::put('/contracts/{contract}', 'update')->name('contracts.update');
            Route::post('/contracts/{contract}/transition', 'transition')->name('contracts.transition');
            Route::post('/contracts/{contract}/invoices', 'issueInvoice')->name('contracts.invoices.store');
            Route::post('/contracts/{contract}/payments', 'recordPayment')->name('contracts.payments.store');
            Route::post('/invoices/{invoice}/void', 'voidInvoice')->name('invoices.void');
        });
        Route::controller(App\Http\Controllers\Admin\SubscriptionFinanceController::class)->group(function () {
            Route::get('/invoices', 'invoices')->name('invoices');
            Route::get('/payments', 'payments')->name('payments');
            Route::get('/plans', 'plans')->name('plans');
            Route::post('/plans', 'storePlan')->name('plans.store');
            Route::put('/plans/{plan}', 'updatePlan')->name('plans.update');
        });
    });

    Route::controller(App\Http\Controllers\Admin\FieldStaffController::class)->prefix('field-staff')->name('field-staff.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{fieldStaff}', 'show')->name('show');
        Route::get('/{fieldStaff}/edit', 'edit')->name('edit');
        Route::put('/{fieldStaff}', 'update')->name('update');
        Route::post('/{fieldStaff}/toggle', 'toggle')->name('toggle');
        Route::post('/{fieldStaff}/reset-password', 'resetPassword')->middleware('throttle:5,1')->name('reset-password');
    });
});

/* Field staff portal: registration of owners and managers only. */
Route::middleware(['auth', 'role:field_staff'])->prefix('field')->name('field-staff.')->group(function () {
    Route::get('/', [App\Http\Controllers\FieldStaff\PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/registrations', [App\Http\Controllers\FieldStaff\PortalController::class, 'registrations'])->name('registrations');
    Route::get('/owners/create', [App\Http\Controllers\FieldStaff\OwnerRegistrationController::class, 'create'])->name('owners.create');
    Route::post('/owners', [App\Http\Controllers\FieldStaff\OwnerRegistrationController::class, 'store'])->middleware('throttle:30,1')->name('owners.store');
    Route::get('/managers/create', [App\Http\Controllers\FieldStaff\ManagerRegistrationController::class, 'create'])->name('managers.create');
    Route::post('/managers', [App\Http\Controllers\FieldStaff\ManagerRegistrationController::class, 'store'])->middleware('throttle:30,1')->name('managers.store');
    Route::get('/password', [App\Http\Controllers\FieldStaff\PortalController::class, 'password'])->name('password');
    Route::put('/password', [App\Http\Controllers\FieldStaff\PortalController::class, 'updatePassword'])->middleware('throttle:10,1')->name('password.update');
});

/* Subscribers' own subscription page, and in-platform calls. */
Route::middleware('auth')->group(function () {
    Route::get('/my-subscription', [App\Http\Controllers\SubscriptionController::class, 'show'])->middleware('role:owner,manager')->name('subscription.show');
    Route::get('/calls/{call}', [App\Http\Controllers\CallController::class, 'join'])->name('calls.join');
    Route::post('/calls/{call}/end', [App\Http\Controllers\CallController::class, 'end'])->name('calls.end');
});
