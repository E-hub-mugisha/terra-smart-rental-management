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
use App\Http\Controllers\RentalUnitController;
use App\Http\Controllers\TenantPortal\MaintenanceRequestController as TenantMaintenanceRequestController;
use App\Http\Controllers\PropertyManager\MaintenanceRequestController as ManagerMaintenanceRequestController;
use App\Http\Controllers\TenantPortal\RentalController;
use App\Http\Controllers\TenantPortal\ProfileController as TenantProfileController;
use App\Http\Controllers\TenantPortal\DashboardController as TenantDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

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
