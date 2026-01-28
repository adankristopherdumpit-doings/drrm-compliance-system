<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FireSafetySchool;
use App\Models\FireSafetyExtinguisher;
use App\Models\FireSafetyAlarmSystem;
use App\Models\FireSafetyBuilding;
use App\Models\FireSafetyEvacuationPlan;
use Carbon\Carbon;

class FireSafetyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $schools = FireSafetySchool::withCount(['extinguishers', 'alarmSystems', 'buildings', 'evacuationPlans'])
            ->get()
            ->map(function($school) {
                // Set status and issues count
                if ($school->extinguishers_count === 0 || $school->alarm_systems_count === 0) {
                    $school->status = 'unconfigured';
                    $school->issues_count = 1;
                } else {
                    // Calculate based on actual data
                    $expiredExtinguishers = $school->extinguishers()
                        ->where('status', 'expired')
                        ->count();

                    $offlineAlarms = $school->alarmSystems()
                        ->where('status', 'offline')
                        ->count();

                    $school->issues_count = $expiredExtinguishers + $offlineAlarms;
                    $school->status = $this->calculateStatus($school);
                }

                $school->last_inspection_date = $school->extinguishers()
                    ->latest('date_checked')
                    ->first()
                    ?->date_checked ?? null;

                return $school;
            });

        return view('fire-safety.dashboard', ['schools' => $schools]);
    }

    private function calculateStatus($school)
    {
        $issues = $school->issues_count;

        if ($issues === 0) return 'passed';
        if ($issues >= 3) return 'failed';
        return 'warning';
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

    // API endpoints for AJAX calls
    public function getSchoolDetails($id)
    {
        $school = FireSafetySchool::withCount(['extinguishers', 'alarmSystems', 'buildings', 'evacuationPlans'])
            ->findOrFail($id);

        return response()->json([
            'name' => $school->school_name,
            'school_id' => $school->school_id,
            'school_head' => $school->school_head,
            'drrm_coordinator' => $school->school_drrm_coordinator,
            'fire_extinguishers_count' => $school->extinguishers_count,
            'alarm_systems_count' => $school->alarm_systems_count,
            'evacuation_plans_count' => $school->evacuation_plans_count,
            'buildings_count' => $school->buildings_count
        ]);
    }

    public function getSchoolIssues($id)
    {
        $school = FireSafetySchool::with(['extinguishers', 'alarmSystems'])->findOrFail($id);
        $issues = [];

        // Check if school is unconfigured
        if ($school->status === 'unconfigured') {
            if ($school->alarmSystems()->count() === 0){
                $issues[] = [
                    'type' => 'warning',
                    'title' => 'Setup Needed',
                    'description' => 'Alarm systems unconfigured yet',
                    'link' => route('fire-safety.alarm-systems')
                ];
            }
            if ($school->extinguishers()->count() === 0){
                $issues[] = [
                    'type' => 'warning',
                    'title' => 'Setup Needed',
                    'description' => 'No fire extinguishers',
                    'link' => route('fire-safety.extinguishers')
                ];
            }
            if ($school->evacuationPlans()->count() === 0){
                $issues[] = [
                    'type' => 'warning',
                    'title' => 'Setup Needed',
                    'description' => 'No Evactuation Plan',
                    'link' => route('fire-safety.evacuatio-plans')
                ];
            }
            if ($school->buildings()->count() === 0){
                $issues[] = [
                    'type' => 'warning',
                    'title' => 'Setup Needed',
                    'description' => 'Setup Buildings',
                    'link' => route('fire-safety.buildings')
                ];
            }
        }

        // Check extinguisher issues
        $expiredExtinguishers = $school->extinguishers()->where('status', 'expired')->get();
        foreach ($expiredExtinguishers as $extinguisher) {
            $issues[] = [
                'type' => 'danger',
                'title' => 'Expired Fire Extinguisher',
                'description' => "Code: {$extinguisher->code} - Last checked: {$extinguisher->date_checked}"
            ];
        }

        // Check alarm system issues
        $offlineAlarms = $school->alarmSystems()->where('status', 'offline')->get();
        foreach ($offlineAlarms as $alarm) {
            $issues[] = [
                'type' => 'warning',
                'title' => 'Alarm System Offline',
                'description' => "Code: {$alarm->code} - Last test: {$alarm->last_test}"
            ];
        }

        return response()->json([
            'school_name' => $school->school_name,
            'issues' => $issues
        ]);
    }

    public function storeSchool(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|string|max:50|unique:firesafety_school_information,school_id',
            'school_head' => 'required|string|max:255',
            'drrm_coordinator' => 'required|string|max:255'
        ]);

        $school = FireSafetySchool::create([
            'school_name' => $validated['name'],
            'school_id' => $validated['school_id'],
            'school_head' => $validated['school_head'],
            'school_drrm_coordinator' => $validated['drrm_coordinator'],
            'status' => 'unconfigured'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School added successfully!',
            'school' => $school
        ]);
    }
}
