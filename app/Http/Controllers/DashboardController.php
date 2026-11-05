<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'manager','owner' => redirect()->route('property-manager.dashboard'),
            'tenant' => redirect()->route('tenant-portal.dashboard'),
            default => abort(403, 'No dashboard is assigned to your account.'),
        };
    }
}