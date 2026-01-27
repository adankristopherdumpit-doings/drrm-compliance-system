<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FireSafetyController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the fire safety dashboard.
     */
    public function dashboard()
    {
        return view('fire-safety.dashboard');
    }
}
