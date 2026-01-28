<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FireSafetyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        return view('fire-safety.dashboard');
    }

    public function alarmSystems()
    {
        return view('fire-safety.alarm-systems');
    }

    public function extinguishers()
    {
        return view('fire-safety.extinguishers');
    }

    public function buildings()
    {
        return view('fire-safety.buildings');
    }

    public function evacuationPlans()
    {
        return view('fire-safety.evacuation-plans');
    }

    public function settings()
    {
        return view('fire-safety.settings');
    }
}
