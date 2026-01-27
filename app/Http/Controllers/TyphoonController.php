<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TyphoonController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the typhoon/flooding dashboard.
     */
    public function dashboard()
    {
        return view('typhoon.dashboard');
    }
}
