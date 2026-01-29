<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FireSafetySchool;
use App\Models\FireSafetyExtinguisher;
use App\Models\FireSafetyAlarmSystem;
use App\Models\FireSafetyBuilding;
use App\Models\FireSafetyEvacuationPlan;
use App\Models\FireSafetyInspection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
        $schools = FireSafetySchool::with(['alarmSystems.building', 'buildings'])->get();

        return view('fire-safety.alarm-systems', [
            'schools' => $schools
        ]);
    }

    // Get buildings for a school (AJAX)
    public function getBuildings($schoolId)
    {
        $buildings = FireSafetyBuilding::where('school_id', $schoolId)->get();
        return response()->json($buildings);
    }

    // Get alarm details (AJAX)
    public function getAlarm($id)
    {
        try {
            $alarm = FireSafetyAlarmSystem::with(['building', 'school'])->findOrFail($id);
            return response()->json($alarm);

        } catch (\Exception $e) {
            Log::error('Error getting alarm: ' . $e->getMessage());
            return response()->json([
                'error' => 'Alarm not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    // Store new alarm
    public function storeAlarm(Request $request)
    {
        try {
            Log::info('Alarm store request received:', $request->all());

            $validated = $request->validate([
                'school_id' => 'required|exists:firesafety_school_information,id',
                'building_id' => 'nullable|exists:firesafety_buildings,id',
                'code' => 'required|string|max:50',
                'alarm_type' => 'required|in:Bell,Mechanical,Digital',
                'status' => 'required|string',
                'location' => 'required|string|max:255',
                'manufacturer' => 'nullable|string|max:100',
                'installation_date' => 'nullable|date',
                'last_test' => 'nullable|date',
                'next_test_due' => 'required|date',
                'notes' => 'nullable|string'
            ]);

            // Format status (convert to lowercase with underscores)
            $validated['status'] = strtolower(str_replace(' ', '_', $validated['status']));

            Log::info('Validation passed:', $validated);

            // Check if code already exists
            $exists = FireSafetyAlarmSystem::where('code', $validated['code'])->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alarm code already exists. Please use a different code.'
                ], 422);
            }

            $alarm = FireSafetyAlarmSystem::create($validated);

            Log::info('Alarm created successfully:', ['id' => $alarm->id]);

            return response()->json([
                'success' => true,
                'message' => 'Alarm system added successfully!',
                'alarm_id' => $alarm->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error creating alarm: ' . $e->getMessage());
            Log::error('Stack trace:', $e->getTrace());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update alarm
    public function updateAlarm(Request $request, $id)
    {
        $alarm = FireSafetyAlarmSystem::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string',
            'last_test' => 'nullable|date',
            'next_test_due' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $statusMap = [
        'functional' => 'active',
        'broken' => 'maintenance',
        ];
        $originalStatus = strtolower(str_replace(' ', '_', $validated['status']));
        $validated['status'] = $statusMap[$originalStatus] ?? $originalStatus;

        $alarm->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Alarm system updated successfully!'
        ]);
    }

    // Test alarm (update last test date)
    public function testAlarm($id)
    {
        $alarm = FireSafetyAlarmSystem::findOrFail($id);
        $alarm->last_test = now();
        $alarm->save();

        return response()->json([
            'success' => true,
            'message' => 'Alarm test recorded successfully!'
        ]);
    }

    // Delete alarm
    public function destroyAlarm($id)
    {
        $alarm = FireSafetyAlarmSystem::findOrFail($id);
        $alarm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alarm system removed successfully!'
        ]);
    }

    public function extinguishers()
    {
        return view('fire-safety.extinguishers');
    }

    public function buildings()
    {
        $schools = FireSafetySchool::with(['buildings', 'buildings.alarmSystems', 'buildings.fireExtinguishers'])->get();

        return view('fire-safety.buildings',[
            'schools' => $schools
        ]);
    }

    public static function calculateBuildingCompliance($building)
    {
        // This is a simplified compliance calculation
        $score = 0;
        $maxScore = 100;

        // Check for alarms
        if ($building->alarmSystems->count() > 0) {
            $score += 30;
        }

        // Check for extinguishers
        if ($building->fireExtinguishers->count() > 0) {
            $score += 30;
        }

        // Check for emergency exits
        if ($building->emergency_exits && $building->emergency_exits > 0) {
            $score += 20;
        }

        // Check for safety features
        if ($building->features) {
            $features = explode(',', $building->features);
            $score += min(20, count($features) * 5);
        }

        return $score;
    }

    // Store new building
    public function storeBuilding(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'building_no' => 'required|string|max:50',
            'building_name' => 'nullable|string|max:100',
            'floors' => 'required|integer|min:1',
            'rooms' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'year_constructed' => 'nullable|integer|min:1900|max:' . date('Y'),
            'last_renovation' => 'nullable|integer|min:1900|max:' . date('Y'),
            'emergency_exits' => 'nullable|integer|min:0',
            'building_type' => 'nullable|string',
            'description' => 'nullable|string',
            'features' => 'nullable|array'
        ]);

        // Convert features array to comma-separated string
        if (isset($validated['features'])) {
            $validated['features'] = implode(',', $validated['features']);
        }
        $building = FireSafetyBuilding::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Building added successfully!'
        ]);
    }
    // Get building details
    public function getBuilding($id)
    {
        $building = FireSafetyBuilding::with(['school', 'alarmSystems', 'fireExtinguishers'])->findOrFail($id);

        return response()->json($building);
    }

    // Get inspections for a school
    public function getInspections($schoolId)
    {
        try {
            $inspections = FireSafetyInspection::where('school_id', $schoolId)
                ->with('building')
                ->orderBy('inspection_date', 'asc')
                ->get()
                ->map(function($inspection) {
                    return [
                        'id' => $inspection->id,
                        'inspection_date' => $inspection->inspection_date,
                        'building_name' => $inspection->building->building_no ?? 'N/A',
                        'inspection_type' => $inspection->inspection_type,
                        'inspector' => $inspection->inspector,
                        'status' => $inspection->status
                    ];
                });

            return response()->json($inspections);

        } catch (\Exception $e) {
            Log::error('Error loading inspections: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    // Get compliance stats for a school
    public function getComplianceStats($schoolId)
    {
        $buildings = FireSafetyBuilding::where('school_id', $schoolId)->get();

        $compliant = 0;
        $needsAttention = 0;
        $nonCompliant = 0;

        foreach ($buildings as $building) {
            $compliance = $this->calculateBuildingCompliance($building);

            if ($compliance >= 80) {
                $compliant++;
            } elseif ($compliance >= 60) {
                $needsAttention++;
            } else {
                $nonCompliant++;
            }
        }

        return response()->json([
            'compliant' => $compliant,
            'needs_attention' => $needsAttention,
            'non_compliant' => $nonCompliant
        ]);
    }

    // Get sidebar stats
    public function getSidebarStats($schoolId)
    {
        $stats = $this->getComplianceStats($schoolId);
        return response()->json(json_decode($stats->getContent()));
    }

    // Get buildings list for dropdown
    public function getBuildingsList($schoolId)
    {
        $buildings = FireSafetyBuilding::where('school_id', $schoolId)
            ->select('id', 'building_no', 'building_name')
            ->get();

        return response()->json($buildings);
    }

    // Schedule inspection
    public function scheduleInspection(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'building_id' => 'required|exists:firesafety_buildings,id',
            'inspection_date' => 'required|date',
            'inspection_type' => 'required|string',
            'inspector' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $validated['status'] = 'scheduled';

        $inspection = FireSafetyInspection::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inspection scheduled successfully!'
        ]);
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
    public function checkAlarmCode($code)
    {
        $exists = FireSafetyAlarmSystem::where('code', $code)->exists();
        return response()->json(['exists' => $exists]);
    }
}
