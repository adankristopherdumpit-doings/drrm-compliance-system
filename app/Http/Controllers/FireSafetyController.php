<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FireSafetySchool;
use App\Models\FireSafetyExtinguisher;
use App\Models\FireSafetyAlarmSystem;
use App\Models\FireSafetyBuilding;
use App\Models\FireSafetyEvacuationPlan;
use App\Models\FireSafetyInspection;
use App\Models\FireSafetyRoom;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $schools = FireSafetySchool::with([
            'buildings',
            'buildings.rooms',
            'buildings.fireExtinguishers.centerRoom',
            'buildings.fireExtinguishers.coveredRooms',
        ])->get();

        return view('fire-safety.extinguishers', [
            'schools' => $schools
        ]);
    }

    // Get rooms for a building (AJAX)
    public function getRooms($buildingId)
    {
        $rooms = FireSafetyRoom::where('building_id', $buildingId)
            ->orderBy('floor_no')
            ->orderBy('room_name')
            ->get();

        return response()->json($rooms);
    }

    // Store a room (AJAX)
    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'building_id' => 'required|exists:firesafety_buildings,id',
            'room_code' => 'nullable|string|max:50',
            'room_name' => 'required|string|max:120',
            'room_type' => 'required|in:classroom,laboratory,auxiliary,office,storage,others',
            'floor_no' => 'nullable|integer|min:1|max:50',
        ]);

        // Ensure building belongs to school
        $building = FireSafetyBuilding::where('id', $validated['building_id'])
            ->where('school_id', $validated['school_id'])
            ->first();
        if (!$building) {
            return response()->json([
                'success' => false,
                'message' => 'Building does not belong to the selected school.'
            ], 422);
        }

        $room = FireSafetyRoom::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Room added successfully!',
            'room' => $room
        ]);
    }

    // Store extinguisher (AJAX) - room-based coverage rules enforced here
    public function storeExtinguisher(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'building_id' => 'required|exists:firesafety_buildings,id',
            'code' => 'required|string|max:50',
            'status' => 'required|in:active,expired,maintenance,missing',
            'date_checked' => 'required|date',
            'evaluation_result' => 'required|string|max:255',
            'room_id' => 'required|exists:fire_safety_rooms,id', // center room
            'covered_room_ids' => 'required|array|min:1|max:3',
            'covered_room_ids.*' => 'integer|exists:fire_safety_rooms,id',
        ]);

        // Ensure building belongs to school
        $building = FireSafetyBuilding::where('id', $validated['building_id'])
            ->where('school_id', $validated['school_id'])
            ->first();
        if (!$building) {
            return response()->json([
                'success' => false,
                'message' => 'Building does not belong to the selected school.'
            ], 422);
        }

        $centerRoom = FireSafetyRoom::where('id', $validated['room_id'])
            ->where('building_id', $validated['building_id'])
            ->where('school_id', $validated['school_id'])
            ->first();
        if (!$centerRoom) {
            return response()->json([
                'success' => false,
                'message' => 'Center room does not belong to the selected building/school.'
            ], 422);
        }

        $coveredRoomIds = array_values(array_unique($validated['covered_room_ids']));

        // Center room must be included
        if (!in_array((int) $validated['room_id'], array_map('intval', $coveredRoomIds), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Covered rooms must include the center room.'
            ], 422);
        }

        // Validate all covered rooms belong to same building/school
        $coveredRooms = FireSafetyRoom::whereIn('id', $coveredRoomIds)
            ->where('building_id', $validated['building_id'])
            ->where('school_id', $validated['school_id'])
            ->get();
        if ($coveredRooms->count() !== count($coveredRoomIds)) {
            return response()->json([
                'success' => false,
                'message' => 'All covered rooms must belong to the selected building and school.'
            ], 422);
        }

        // Laboratory rule: lab can only share with ONE auxiliary room (total 2 rooms)
        if ($centerRoom->room_type === 'laboratory' && count($coveredRoomIds) > 2) {
            return response()->json([
                'success' => false,
                'message' => 'Laboratory center room can cover only itself, or itself + 1 auxiliary room.'
            ], 422);
        }
        if ($centerRoom->room_type === 'laboratory' && count($coveredRoomIds) === 2) {
            $otherRoom = $coveredRooms->firstWhere('id', '!=', $centerRoom->id);
            if (!$otherRoom || $otherRoom->room_type !== 'auxiliary') {
                return response()->json([
                    'success' => false,
                    'message' => 'Laboratory can only share with an auxiliary room.'
                ], 422);
            }
        }

        // Enforce "1 extinguisher per room" coverage (no duplicate coverage)
        $alreadyCovered = DB::table('fire_safety_extinguisher_room_coverage')
            ->whereIn('room_id', $coveredRoomIds)
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($alreadyCovered)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected rooms already have an extinguisher assigned.'
            ], 422);
        }

        // Ensure code is unique within the same school (simple constraint at app level)
        $codeExists = FireSafetyExtinguisher::where('school_id', $validated['school_id'])
            ->where('code', $validated['code'])
            ->exists();
        if ($codeExists) {
            return response()->json([
                'success' => false,
                'message' => 'Extinguisher code already exists for this school.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $ext = FireSafetyExtinguisher::create([
                'school_id' => $validated['school_id'],
                'building_id' => $validated['building_id'],
                'room_id' => $validated['room_id'],
                'code' => $validated['code'],
                'status' => $validated['status'],
                'date_checked' => $validated['date_checked'],
                'evaluation_result' => $validated['evaluation_result'],
            ]);

            $ext->coveredRooms()->sync($coveredRoomIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Extinguisher added successfully!',
                'extinguisher_id' => $ext->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing extinguisher: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add extinguisher: ' . $e->getMessage()
            ], 500);
        }
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

    // Get inspection details (AJAX) - used by Buildings page
    public function getInspection($id)
    {
        try {
            $inspection = FireSafetyInspection::with(['building', 'school'])->findOrFail($id);
            return response()->json($inspection);
        } catch (\Exception $e) {
            Log::error('Error getting inspection: ' . $e->getMessage());
            return response()->json([
                'error' => 'Inspection not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    // Cancel inspection (AJAX) - used by Buildings page
    public function cancelInspection($id)
    {
        try {
            $inspection = FireSafetyInspection::findOrFail($id);
            $inspection->status = 'cancelled';
            $inspection->save();

            return response()->json([
                'success' => true,
                'message' => 'Inspection cancelled successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling inspection: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel inspection: ' . $e->getMessage()
            ], 500);
        }
    }

    // Placeholder checklist page to prevent 404 (until checklist UI is implemented)
    public function inspectionChecklist($id)
    {
        $inspection = FireSafetyInspection::with(['building', 'school'])->findOrFail($id);
        return view('fire-safety.inspection-checklist', [
            'inspection' => $inspection
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
