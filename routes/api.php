<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommonApiController;
use App\Http\Controllers\Api\PortfolioApiController;
use App\Http\Controllers\Api\TenantApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Terra API v1 (brief §22, §25–26)
|--------------------------------------------------------------------------
| Bearer-token API for the Flutter mobile app and approved integrations.
| Documentation: GET /api/docs (web). Every endpoint applies the same
| per-record access rules as the web portals.
*/

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/auth/token', [AuthController::class, 'token'])->middleware('throttle:10,1')->name('token');
    Route::get('/listings', [CommonApiController::class, 'listings'])->middleware('throttle:60,1')->name('listings');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::delete('/auth/token', [AuthController::class, 'revoke'])->name('token.revoke');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::get('/notifications', [CommonApiController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/{id}/read', [CommonApiController::class, 'readNotification'])->name('notifications.read');
        Route::get('/conversations', [CommonApiController::class, 'conversations'])->name('conversations');
        Route::get('/conversations/{conversation}/messages', [CommonApiController::class, 'messages'])->name('messages');
        Route::post('/conversations/{conversation}/messages', [CommonApiController::class, 'sendMessage'])->middleware('throttle:40,1')->name('messages.send');

        Route::middleware('abilities:tenant')->prefix('tenant')->name('tenant.')->group(function () {
            Route::get('/dashboard', [TenantApiController::class, 'dashboard'])->name('dashboard');
            Route::get('/leases', [TenantApiController::class, 'leases'])->name('leases');
            Route::get('/invoices', [TenantApiController::class, 'invoices'])->name('invoices');
            Route::post('/invoices/{invoice}/pay', [TenantApiController::class, 'pay'])->middleware('throttle:10,1')->name('pay');
            Route::get('/payment-intents/{intent}', [TenantApiController::class, 'paymentStatus'])->name('payment-status');
            Route::get('/payments', [TenantApiController::class, 'payments'])->name('payments');
            Route::get('/maintenance-requests', [TenantApiController::class, 'maintenance'])->name('maintenance');
            Route::post('/maintenance-requests', [TenantApiController::class, 'createMaintenance'])->middleware('throttle:20,1')->name('maintenance.store');
        });

        Route::middleware('ability:staff,finance')->prefix('portfolio')->name('portfolio.')->group(function () {
            Route::get('/summary', [PortfolioApiController::class, 'summary'])->name('summary');
            Route::get('/properties', [PortfolioApiController::class, 'properties'])->name('properties');
            Route::get('/properties/{property}', [PortfolioApiController::class, 'property'])->name('property');
            Route::get('/maintenance-requests', [PortfolioApiController::class, 'maintenance'])->name('maintenance');
            Route::get('/payments', [PortfolioApiController::class, 'payments'])->name('payments');
        });

        Route::middleware('abilities:technician')->prefix('technician')->name('technician.')->group(function () {
            Route::get('/jobs', [CommonApiController::class, 'jobs'])->name('jobs');
            Route::patch('/jobs/{maintenanceRequest}', [CommonApiController::class, 'updateJob'])->name('jobs.update');
        });
    });
});
