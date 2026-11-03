<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $units = Unit::query()
            ->where('status', 'available')
            ->with(['building', 'floor', 'images'])
            ->latest()
            ->paginate(6);

        return view('home', compact('units'));
    }
}
