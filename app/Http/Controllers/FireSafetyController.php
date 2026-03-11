<?php

namespace App\Http\Controllers;

use App\Models\FireSafetySchoolSnapshot;
use Illuminate\Http\Request;
use App\Models\FireSafetySchool;
use App\Models\FireSafetyExtinguisher;
use App\Models\FireSafetyAlarmSystem;
use App\Models\FireSafetyBuilding;
use App\Models\FireSafetyEvacuationPlan;
use App\Models\FireSafetyInspection;
use App\Models\FireSafetyExtinguisherInspection;
use App\Models\FireSafetyRoom;
use App\Models\FireSafetyEvacuationDrill;
use App\Models\FireSafetyArchive;
use Carbon\Carbon;
use App\Models\User;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FireSafetyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $query = FireSafetySchool::with([
            'buildings.actualRooms',
            'buildings.alarmSystems',
            'buildings.fireExtinguishers',
            'buildings.evacuationPlan'
        ])->withCount(['extinguishers', 'alarmSystems', 'buildings', 'evacuationPlans']);

        if (auth()->user()->role !== 'admin') {
            $query->where('id', auth()->user()->school_id);
        }

        $schools = $query->get()
            ->map(function($school) {
                // Initialize issues
                $issues = [];
                $isUnconfigured = false;

                $buildingIssuesModules = [];
                $alarmIssuesModules = [];
                $roomIssuesModules = [];
                $extinguisherIssuesModules = [];
                $planIssuesModules = [];

                $schoolConfig = [
                    'has_buildings' => $school->buildings->count() > 0,
                    'has_alarms' => false,
                    'has_rooms' => false,
                    'has_extinguishers' => false,
                    'has_plans' => false,
                    'needs_building' => $school->buildings->count() === 0,
                    'needs_alarm' => false,
                    'needs_room' => false,
                    'needs_extinguisher' => false,
                    'needs_plan' => false
                ];

                if ($school->buildings->count() === 0) {
                    $isUnconfigured = true;
                    $issues[] = 'Setup Needed';
                } else {
                    if ($schoolConfig['has_buildings']) {
                        $buildingsWithAlarms = 0;
                        $buildingsWithAllRooms = 0;
                        $buildingsWithPlan = 0;
                        $buildingsWithExtinguishers = 0;
                        $totalBldgs = $school->buildings->count();

                        foreach ($school->buildings as $b) {
                            $bldAlarms = $b->alarmSystems->count();
                            $schoolHasAlarm = $school->alarmSystems()->where('status','active')->exists();
                            $bldRoomsReady = $b->rooms > 0 && $b->actualRooms->count() >= $b->rooms;
                            $bldPlan = $b->evacuationPlan !== null;
                            // if no specific building plan, but school has an active school-wide plan, count it
                            if (!$bldPlan) {
                                $bldPlan = FireSafetyEvacuationPlan::where('school_id', $school->id)
                                    ->whereNull('building_id')
                                    ->where('status','active')
                                    ->exists();
                            }
                            $reqExt = $b->required_extinguishers_count;
                            $bldExtReady = $b->fireExtinguishers->count() >= $reqExt;

                            if ($bldAlarms > 0) $buildingsWithAlarms++;
                            if ($bldRoomsReady) $buildingsWithAllRooms++;
                            if ($bldPlan) $buildingsWithPlan++;
                            if ($bldExtReady) $buildingsWithExtinguishers++;

                            // Collect issues for Ongoing Improvement
                            if (!$bldRoomsReady) $roomIssuesModules[] = $b->building_no;
                            if ($bldAlarms === 0 && !$schoolHasAlarm) {
                                // no alarms on this building and none available school-wide
                                $alarmIssuesModules[] = $b->building_no;
                            } else {
                                $badAlarmStatuses = ['offline', 'broken', 'missing', 'jammed', 'under-repair', 'system-error'];
                                if ($b->alarmSystems->whereIn('status', $badAlarmStatuses)->count() > 0) {
                                    $alarmIssuesModules[] = $b->building_no;
                                }
                            }
                            if (!$bldPlan) $planIssuesModules[] = $b->building_no;
                            if (!$bldExtReady) {
                                $extinguisherIssuesModules[] = $b->building_no;
                            } else {
                                $badExtStatuses = ['expired', 'broken', 'missing', 'for-purchase', 'empty'];
                                if ($b->fireExtinguishers->whereIn('status', $badExtStatuses)->count() > 0) {
                                    $extinguisherIssuesModules[] = $b->building_no;
                                }
                            }

                            if ($this->calculateBuildingCompliance($b) < 80) {
                                $buildingIssuesModules[] = $b->building_no;
                            }
                        }

                        $schoolConfig['has_alarms'] = $buildingsWithAlarms > 0;
                        $schoolConfig['has_rooms'] = $buildingsWithAllRooms > 0;
                        $schoolConfig['has_extinguishers'] = $buildingsWithExtinguishers > 0;

                        // treat school-wide plan as covering all buildings
                        $schoolHasPlan = FireSafetyEvacuationPlan::where('school_id', $school->id)
                            ->whereNull('building_id')
                            ->where('status','active')
                            ->exists();
                        $schoolConfig['has_plans'] = $buildingsWithPlan > 0 || $schoolHasPlan;
                        if ($schoolHasPlan) {
                            $buildingsWithPlan = $totalBldgs;
                        }

                        $schoolConfig['needs_alarm'] = (!$schoolHasAlarm) && ($buildingsWithAlarms < $totalBldgs);
                        $schoolConfig['needs_room'] = $buildingsWithAllRooms < $totalBldgs;
                        $schoolConfig['needs_plan'] = !$schoolConfig['has_plans'];
                        $schoolConfig['needs_extinguisher'] = $buildingsWithExtinguishers < $totalBldgs;
                    } else {
                        $schoolConfig['needs_alarm'] = true;
                        $schoolConfig['needs_room'] = true;
                        $schoolConfig['needs_plan'] = true;
                        $schoolConfig['needs_extinguisher'] = true;
                        $isUnconfigured = true;
                    }
                }

                // Determine unconfigured status
                if ($school->buildings->count() === 0 || (!$schoolConfig['has_alarms'] && !$schoolConfig['has_plans'] && !$schoolConfig['has_rooms'])) {
                    $isUnconfigured = true;
                    $issues = ['Setup Needed'];
                } else {
                    if (count($buildingIssuesModules) > 0) $issues[] = 'Building Issues';
                    if (count($alarmIssuesModules) > 0) $issues[] = 'Alarm Issues';
                    if (count($roomIssuesModules) > 0) $issues[] = 'Room Issues';
                    if (count($extinguisherIssuesModules) > 0) $issues[] = 'Fire Extinguisher Issues';
                    if (count($planIssuesModules) > 0) $issues[] = 'Evacuation Plan Issues';
                }

                if ($isUnconfigured) {
                    $school->status = 'unconfigured';
                } elseif (count($issues) > 0) {
                    $school->status = 'warning'; // Ongoing Improvement
                } else {
                    $school->status = 'passed';
                    $issues = ['None'];
                }

                $school->issues_list = $issues;
                $school->issues_count = ($school->status === 'passed') ? 0 : count($issues);
                $school->config_status = $schoolConfig;

                // For the "Ongoing Improvement" details modal, provide specific counts/lists
                $school->module_issues = [
                    'buildings' => $buildingIssuesModules,
                    'alarms' => $alarmIssuesModules,
                    'rooms' => $roomIssuesModules,
                    'extinguishers' => $extinguisherIssuesModules,
                    'plans' => $planIssuesModules,
                ];

                // Last Inspection / Real-time configuration date
                // Check all relevant tables for latest update
                $updates = [];
                $updates[] = $school->updated_at;
                foreach ($school->buildings as $b) {
                    $updates[] = $b->updated_at;
                    foreach ($b->fireExtinguishers as $e) $updates[] = $e->updated_at;
                    foreach ($b->alarmSystems as $a) $updates[] = $a->updated_at;
                    if ($b->evacuationPlan) $updates[] = $b->evacuationPlan->updated_at;
                }

                $lastConfig = collect($updates)->filter()->max();
                $school->last_inspection_date = $lastConfig ? $lastConfig : null;

                return $school;
            });

        $allAlerts = [];
        $allEvents = [];

        foreach ($schools as $school) {
            if ($school->alerts) {
                foreach ($school->alerts as $alert) {
                    $alert['school_name'] = $school->school_name;
                    $allAlerts[] = $alert;
                }
            }
            if ($school->events) {
                foreach ($school->events as $event) {
                    $event['school_name'] = $school->school_name;
                    $allEvents[] = $event;
                }
            }
        }

        // Sort by date
        usort($allAlerts, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        usort($allEvents, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        return view('fire-safety.dashboard', [
            'schools' => $schools,
            'allAlerts' => array_slice($allAlerts, 0, 10),
            'allEvents' => array_slice($allEvents, 0, 10)
        ]);
    }

    private function calculateStatus($school)
    {
        $issues = $school->issues_count;

        if ($issues === 0) return 'passed';
        if ($issues >= 3) return 'failed';
        return 'warning';
    }

    public function setActiveSchool(Request $request, $id)
    {
        $school = FireSafetySchool::findOrFail($id);

        // If user is not admin and trying to set a school that isn't theirs (though middleware/logic should prevent fetching others)
        if (auth()->user()->role !== 'admin' && auth()->user()->school_id != $id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        session(['fire_safety_active_school_id' => $id]);

        return response()->json(['success' => true]);
    }

    private function getActiveSchool($schools) {
        if ($schools->isEmpty()) return null;

        $activeId = session('fire_safety_active_school_id');

        // If specific school is inactive or deleted, fallback
        $activeSchool = $schools->where('id', $activeId)->first();

        return $activeSchool ?? $schools->first();
    }

    public function alarmSystems()
    {
        $query = FireSafetySchool::with(['alarmSystems.buildings', 'buildings']);

        if (auth()->user()->role !== 'admin') {
            $query->where('id', auth()->user()->school_id);
        }

        $schools = $query->get();
        $activeSchool = $this->getActiveSchool($schools);

        $alarmTypes = SystemConfiguration::where('config_type', 'alarm_type')->where('is_active', true)->orderBy('sort_order')->get();
        $alarmStatusesByType = SystemConfiguration::where('config_type', 'alarm_status')->where('is_active', true)->whereNotNull('parent_id')->get()->groupBy('parent_id');

        return view('fire-safety.alarm-systems', [
            'schools' => $schools,
            'activeSchool' => $activeSchool,
            'alarmTypes' => $alarmTypes,
            'alarmStatusesByType' => $alarmStatusesByType
        ]);
    }

    // Get buildings for a school (AJAX)
    public function getBuildings($schoolId)
    {
        $buildings = FireSafetyBuilding::where('school_id', $schoolId)->with(['alarmSystems', 'alarmSystemsMany'])->get();
        return response()->json($buildings);
    }

    // Get alarm details (AJAX)
    public function getAlarm($id)
    {
        try {
            $alarm = FireSafetyAlarmSystem::with(['building', 'school', 'buildings'])->findOrFail($id);
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
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot add alarm systems.'], 403);
        }

        try {
            Log::info('Alarm store request received:', $request->all());

            $validated = $request->validate([
                'school_id' => 'required|exists:firesafety_school_information,id',
                'building_id' => 'nullable|exists:firesafety_buildings,id',
                'building_ids' => 'nullable|array',
                'building_ids.*' => 'exists:firesafety_buildings,id',
                'floor_id' => 'nullable|string',
                'code' => 'required|string|max:50',
                'alarm_type' => 'required|string',
                'status' => 'required|string',
                'location' => 'nullable|string|max:255',
                'manufacturer' => 'nullable|string|max:100',
                'installation_date' => 'nullable|date',
                'last_test' => 'nullable|date',
                'next_test_due' => 'required|date',
                'notes' => 'nullable|string'
            ]);

            // Format status (convert to lowercase with underscores)
            $validated['status'] = strtolower(str_replace(' ', '_', $validated['status']));

            if (empty($validated['location'])) {
                $validated['location'] = 'Not Specified';
            }

            // Handle 'all' floor_id - Convert to null for DB compatibility
            if (isset($validated['floor_id']) && strtolower($validated['floor_id']) === 'all') {
                $validated['floor_id'] = null;
            }

            Log::info('Validation passed:', $validated);

            // Check if code already exists
            // Check if code already exists for this school
            $exists = FireSafetyAlarmSystem::where('school_id', $validated['school_id'])
                ->where('code', $validated['code'])
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alarm code already exists in this school. Please use a different code.'
                ], 422);
            }

            $alarm = FireSafetyAlarmSystem::create($validated);

            if ($request->has('building_ids') && is_array($request->building_ids)) {
                $alarm->buildings()->sync($request->building_ids);
            } elseif ($request->building_id) {
                $alarm->buildings()->sync([$request->building_id]);
            }

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
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update alarm systems.'], 403);
        }

        $alarm = FireSafetyAlarmSystem::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$alarm->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this alarm.'], 403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'status' => 'required|string',
            'location' => 'nullable|string|max:255',
            'last_test' => 'nullable|date',
            'next_test_due' => 'required|date',
            'notes' => 'nullable|string',
            'manufacturer' => 'nullable|string|max:100',
            'installation_date' => 'nullable|date',
            'building_ids' => 'nullable|array',
            'building_ids.*' => 'exists:firesafety_buildings,id'
        ]);

        // Check if code changes to an existing one
        if ($validated['code'] !== $alarm->code) {
            $exists = FireSafetyAlarmSystem::where('school_id', $alarm->school_id)
                ->where('code', $validated['code'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alarm code already exists in this school.'
                ], 422);
            }
        }

        $statusMap = [
        'functional' => 'active',
        'broken' => 'maintenance',
        ];
        $originalStatus = strtolower(str_replace(' ', '_', $validated['status']));
        $validated['status'] = $statusMap[$originalStatus] ?? $originalStatus;

        if (empty($validated['location'])) {
            $validated['location'] = 'Not Specified';
        }

        // Handle 'all' floor_id - Convert to null for DB compatibility
        if (isset($validated['floor_id']) && strtolower($validated['floor_id']) === 'all') {
            $validated['floor_id'] = null;
        }

        $alarm->update($validated);

        if ($request->has('building_ids')) {
            $alarm->buildings()->sync($request->building_ids);
        }

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

    // Remove alarm (Archive and Delete)
    public function removeAlarm(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot remove alarm systems.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $alarm = FireSafetyAlarmSystem::with(['building'])->findOrFail($id);

            if (auth()->user()->role !== 'admin' && (int)$alarm->school_id !== (int)auth()->user()->school_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to this alarm.'], 403);
            }

            // Create archive entry
            FireSafetyArchive::create([
                'school_id' => $alarm->school_id,
                'type' => 'alarm',
                'item_id' => $alarm->id,
                'item_code' => $alarm->code,
                'item_data' => [
                    'alarm_type' => $alarm->alarm_type,
                    'status' => $alarm->status,
                    'building_name' => $alarm->building?->building_name ?? 'N/A',
                    'manufacturer' => $alarm->manufacturer,
                    'last_test' => $alarm->last_test
                ],
                'reason' => $request->reason,
                'removed_at' => now()
            ]);

            // Delete associations if any (e.g., if there's a pivot for multi-building)
            DB::table('fire_safety_alarm_building')->where('alarm_id', $alarm->id)->delete();

            $alarm->delete();

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing alarm: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove alarm system.'], 500);
        }
    }

    public function getAlarmHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('school_id', $schoolId)
            ->where('type', 'alarm')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function extinguishers()
    {
        $query = FireSafetySchool::with([
            'buildings',
            'buildings.actualRooms',
            'buildings.fireExtinguishers.centerRoom',
            'buildings.fireExtinguishers.coveredRooms',
        ]);

        if (auth()->user()->role !== 'admin') {
            $query->where('id', auth()->user()->school_id);
        }

        $schools = $query->get();

        // Pre-load recent updates for each school to avoid "Loading..." state on page load
        foreach ($schools as $school) {
            $school->recent_inspections_data = FireSafetyExtinguisherInspection::whereHas('extinguisher', function($q) use ($school) {
                    $q->where('school_id', $school->id);
                })
                ->with(['extinguisher', 'extinguisher.building', 'extinguisher.centerRoom', 'inspector'])
                ->latest('inspection_date')
                ->take(10)
                ->get();

            $school->recent_room_updates_data = FireSafetyRoom::where('school_id', $school->id)
                ->with(['building', 'nearestExtinguisherRoom', 'hostedExtinguisher', 'lastInspector'])
                ->latest('updated_at')
                ->take(10)
                ->get();
        }

        $activeSchool = $this->getActiveSchool($schools);

        $calculatedPriorities = SystemConfiguration::where('config_type', 'calculated_priority')
            ->orderBy('sort_order')
            ->get();
        $roomTypes = SystemConfiguration::where('config_type', 'room_type')
            ->orderBy('sort_order')
            ->get();

        return view('fire-safety.extinguishers', [
            'schools' => $schools,
            'activeSchool' => $activeSchool,
            'calculatedPriorities' => $calculatedPriorities,
            'roomTypes' => $roomTypes,
        ]);
    }

    // Get rooms for a building (AJAX)
    public function getRooms($buildingId)
    {
        $rooms = FireSafetyRoom::where('building_id', $buildingId)
            ->with(['roomTypeConfig.parent'])
            ->orderBy('floor_no')
            ->orderBy('room_name')
            ->get();

        $rooms->map(function($r) {
            $priority = $r->roomTypeConfig?->parent;
            $r->max_rooms = ($priority && $priority->config_type === 'calculated_priority')
                ? (int)($priority->max_rooms_covered ?? 3)
                : (in_array(strtolower($r->room_type), ['classroom', 'department', 'library']) ? 3 : 2);
            return $r;
        });

        return response()->json($rooms);
    }

    public function updateRoom(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update rooms.'], 403);
        }

        $room = FireSafetyRoom::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$room->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this room.'], 403);
        }
        $validated = $request->validate([
            'room_code' => 'nullable|string|max:50',
            'room_name' => 'nullable|string',
            'room_type_config_id' => 'nullable|exists:system_configurations,id',
            'nearest_extinguisher_room_id' => 'nullable|exists:fire_safety_rooms,id',
            'smoke_detector_required' => 'boolean',
            'has_smoke_detector' => 'boolean',
            'has_secondary_exit' => 'boolean',
            'secondary_exit_remarks' => 'nullable|string',
            'remarks' => 'nullable|string'
        ]);

        if (!isset($validated['smoke_detector_required'])) {
            $validated['smoke_detector_required'] = false;
        }

        if (!isset($validated['has_smoke_detector'])) {
            $validated['has_smoke_detector'] = false;
        }

        if (!isset($validated['has_secondary_exit'])) {
            $validated['has_secondary_exit'] = false;
        }

        // If room type changed, update the snapshot fields and clear all extinguisher coverage for this room
        $roomTypeChanged = false;
        if (!empty($validated['room_type_config_id']) && $validated['room_type_config_id'] != $room->room_type_config_id) {
            $roomTypeChanged = true;
            $newTypeConfig = \App\Models\SystemConfiguration::find($validated['room_type_config_id']);
            if ($newTypeConfig) {
                $validated['room_type'] = $newTypeConfig->name;
                $priorityConfig = $newTypeConfig->parent_id
                    ? \App\Models\SystemConfiguration::find($newTypeConfig->parent_id)
                    : null;
                $validated['calculated_priority_label'] = $priorityConfig->name ?? null;
                $limit = (int) ($priorityConfig->max_rooms_covered ?? 2);
                $validated['coverage_limit'] = max(1, min(5, $limit));
            }
        } else {
            unset($validated['room_type_config_id']);
        }

        // Only update room_name if it was actually provided
        if (empty($validated['room_name'])) {
            unset($validated['room_name']);
        }

        $user = auth()->user();
        $validated['last_inspector_id'] = $user->id;

        if ($user->role === 'contributor') {
            $validated['approval_status'] = 'pending';

            // Notify Admins
            $school = FireSafetySchool::find($room->school_id);
            if ($school) {
                $alerts = $school->alerts ?? [];
                $alerts[] = [
                    'id' => uniqid(),
                    'title' => 'Room Update Pending Approval',
                    'description' => "Contributor {$user->name} updated room {$room->room_code} and it requires administrator approval.",
                    'type' => 'warning',
                    'created_at' => now()->toDateTimeString()
                ];
                $school->alerts = $alerts;
                $school->save();
            }
        } else {
            $validated['approval_status'] = 'approved';
        }

        $room->update($validated);

        // If room type changed, clear all extinguisher coverage for this room
        if ($roomTypeChanged) {
            $affectedRoomIds = [];

            // If this room WAS a center room of an extinguisher, clear that extinguisher's
            // ENTIRE coverage (all rooms it was covering), then unlink the center room.
            $hostedExt = FireSafetyExtinguisher::where('room_id', $id)->first();
            if ($hostedExt) {
                // Capture affected rooms BEFORE deleting pivot rows
                $affectedRoomIds = DB::table('fire_safety_extinguisher_room_coverage')
                    ->where('extinguisher_id', $hostedExt->id)
                    ->pluck('room_id')
                    ->map(fn ($v) => (int) $v)
                    ->values()
                    ->toArray();

                // Remove ALL rooms from this extinguisher's coverage pivot
                // Use relationship detach to ensure the correct connection/table is used.
                $hostedExt->coveredRooms()->detach();

                // Safety cleanup in case of legacy rows
                DB::table('fire_safety_extinguisher_room_coverage')
                    ->where('extinguisher_id', $hostedExt->id)
                    ->delete();

                // Clear nearest pointers that reference this former host room
                FireSafetyRoom::where('building_id', $room->building_id)
                    ->where('nearest_extinguisher_room_id', $id)
                    ->update(['nearest_extinguisher_room_id' => null]);

                // Unlink center room
                $hostedExt->room_id = null;
                $hostedExt->save();
            } else {
                // Not a center room — just remove this room from any coverage pivot
                DB::table('fire_safety_extinguisher_room_coverage')->where('room_id', $id)->delete();
                $affectedRoomIds = [(int) $id];
            }
            return response()->json([
                'success' => true,
                'type_changed' => true,
                'affected_room_ids' => $affectedRoomIds,
            ]);
        }

        // Sync Coverage Pivot Table
        if ($request->filled('nearest_extinguisher_room_id')) {
            // Find the extinguisher that has this nearest room as its CENTER room
            $ext = FireSafetyExtinguisher::where('room_id', $validated['nearest_extinguisher_room_id'])->first();
            if ($ext) {
                // CAPACITY VALIDATION
                $hostRoom = FireSafetyRoom::with(['roomTypeConfig.parent'])->find($ext->room_id);
                $priority = $hostRoom->roomTypeConfig?->parent;
                $maxLimit = 3;
                if ($priority && $priority->config_type === 'calculated_priority') {
                    $maxLimit = (int) ($priority->max_rooms_covered ?? 3);
                } else {
                    $maxLimit = in_array(strtolower($hostRoom->room_type), ['classroom', 'department', 'library']) ? 3 : 2;
                }

                $currentCount = $ext->coveredRooms()->count();
                $isAlreadyCoveredByThis = $ext->coveredRooms()->where('room_id', $id)->exists();

                if (!$isAlreadyCoveredByThis && $currentCount >= $maxLimit) {
                    return response()->json([
                        'success' => false,
                        'message' => "The selected extinguisher (in {$hostRoom->room_code}) is already covering its maximum limit of $maxLimit rooms."
                    ], 422);
                }

                // Remove existing coverage for this room first
                DB::table('fire_safety_extinguisher_room_coverage')->where('room_id', $id)->delete();
                // Attach to the selected extinguisher
                $ext->coveredRooms()->attach($id);
            }
        } else {
            // If it was covered by something else and now is NOT, remove it
            // Only if it's NOT the center room of its own extinguisher
            $isCenter = FireSafetyExtinguisher::where('room_id', $id)->exists();
            if (!$isCenter) {
                DB::table('fire_safety_extinguisher_room_coverage')->where('room_id', $id)->delete();
            }
        }

        return response()->json(['success' => true]);
    }

    public function approveRoom($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only administrators can approve room updates.'], 403);
        }

        $room = FireSafetyRoom::findOrFail($id);
        $room->update([
            'approval_status' => 'approved',
            'approval_message' => 'Approved by ' . auth()->user()->name
        ]);

        // Notify Contributor
        if ($room->lastInspector && $room->lastInspector->role === 'contributor') {
            $school = FireSafetySchool::find($room->school_id);
            if ($school) {
                $alerts = $school->alerts ?? [];
                $alerts[] = [
                    'id' => uniqid(),
                    'title' => 'Room Update Approved',
                    'description' => "Your update for room {$room->room_code} has been approved by the administrator.",
                    'type' => 'success',
                    'created_at' => now()->toDateTimeString()
                ];
                $school->alerts = $alerts;
                $school->save();
            }
        }

        return response()->json(['success' => true]);
    }

    public function rejectRoom(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only administrators can reject room updates.'], 403);
        }

        $room = FireSafetyRoom::findOrFail($id);
        $room->update([
            'approval_status' => 'rejected',
            'approval_message' => $request->input('reason', 'Rejected by ' . auth()->user()->name)
        ]);

        // Notify Contributor
        if ($room->lastInspector && $room->lastInspector->role === 'contributor') {
            $school = FireSafetySchool::find($room->school_id);
            if ($school) {
                $alerts = $school->alerts ?? [];
                $alerts[] = [
                    'id' => uniqid(),
                    'title' => 'Room Update Rejected',
                    'description' => "Your update for room {$room->room_code} has been rejected. Reason: " . ($request->input('reason') ?? 'No reason provided.'),
                    'type' => 'danger',
                    'created_at' => now()->toDateTimeString()
                ];
                $school->alerts = $alerts;
                $school->save();
            }
        }

        return response()->json(['success' => true]);
    }

    public function getNearestCandidateRooms($roomId)
    {
        $currentRoom = FireSafetyRoom::findOrFail($roomId);

        $extinguishers = FireSafetyExtinguisher::where('building_id', $currentRoom->building_id)
            ->where('status', '!=', 'missing')
            ->whereHas('centerRoom', function($q) use ($currentRoom) {
                $q->where('floor_no', $currentRoom->floor_no)
                  ->where('id', '!=', $currentRoom->id);
            })
            ->with(['centerRoom', 'coveredRooms'])
            ->get();

        $availableRooms = [];

        foreach ($extinguishers as $ext) {
            $count = $ext->coveredRooms->count();
            $centerRoomType = $ext->centerRoom->room_type;

            // Capacity limit from prioritized configuration
            $ext->centerRoom->load(['roomTypeConfig.parent']);
            $limit = (int) ($ext->centerRoom->coverage_limit ?? 0);
            if ($limit <= 0) {
                // Try to get from Room Type priority
                $priority = $ext->centerRoom->roomTypeConfig?->parent;
                if ($priority && $priority->config_type === 'calculated_priority') {
                    $limit = (int) ($priority->max_rooms_covered ?? 3);
                } else {
                    // Final legacy fallback
                    if (in_array(strtolower($centerRoomType), ['classroom', 'department', 'library'])) {
                        $limit = 3;
                    } else {
                        $limit = 2;
                    }
                }
            }

            if ($count < $limit) {
                $availableRooms[] = [
                    'id' => $ext->centerRoom->id,
                    'room_name' => $ext->centerRoom->room_name,
                    'room_code' => $ext->centerRoom->room_code,
                    'extinguisher_code' => $ext->code,
                ];
            }
        }

        return response()->json($availableRooms);
    }

    // Store a room (AJAX)
public function storeRoom(Request $request)
{
    if (auth()->user()->role === 'viewer') {
        return response()->json(['success' => false, 'message' => 'Viewers cannot add rooms.'], 403);
    }

    $validated = $request->validate([
        'school_id' => 'required|exists:firesafety_school_information,id',
        'building_id' => 'required|exists:firesafety_buildings,id',
        'room_code' => 'nullable|string|max:50',
        'room_name' => 'nullable|string|max:100',
        'room_type_config_id' => 'required|exists:system_configurations,id',
        'floor_no' => 'required|integer|min:1|max:50',
        'smoke_detector_required' => 'boolean',
        'has_smoke_detector' => 'boolean',
        'has_secondary_exit' => 'boolean',
        'secondary_exit_remarks' => 'nullable|string',
        'remarks' => 'nullable|string'
    ]);

    if (!isset($validated['smoke_detector_required'])) {
        $validated['smoke_detector_required'] = false;
    }

    if (!isset($validated['has_smoke_detector'])) {
        $validated['has_smoke_detector'] = false;
    }

    if (!isset($validated['has_secondary_exit'])) {
        $validated['has_secondary_exit'] = false;
    }

    // ensure room_name is not null since database column is non-nullable
    if (empty($validated['room_name'])) {
        $validated['room_name'] = $validated['room_code'] ?? '';
    }

    $roomTypeConfig = SystemConfiguration::where('id', $validated['room_type_config_id'])
        ->where('config_type', 'room_type')
        ->firstOrFail();

    $priorityConfig = null;
    if ($roomTypeConfig->parent_id) {
        $priorityConfig = SystemConfiguration::where('id', $roomTypeConfig->parent_id)
            ->where('config_type', 'calculated_priority')
            ->first();
    }
    $limit = (int) ($priorityConfig->max_rooms_covered ?? 0);
    if ($limit <= 0) {
        // safe default if misconfigured
        $limit = 2;
    }
    if ($limit > 5) {
        $limit = 5;
    }

    // Snapshot values so later config edits do NOT retroactively alter existing rooms.
    $validated['room_type'] = $roomTypeConfig->name;
    $validated['calculated_priority_label'] = $priorityConfig->name ?? null;
    $validated['coverage_limit'] = $limit;

    $building = FireSafetyBuilding::findOrFail($validated['building_id']);

    // Check building target rooms
    $totalTargetRooms = $building->rooms; // the standard number of rooms
    $totalFloors = $building->floors;
    $currentRoomsCount = $building->actualRooms()->count();

    if ($currentRoomsCount >= $totalTargetRooms) {
        return response()->json([
            'success' => false,
            'message' => "Standard room count ($totalTargetRooms) for this building reached. Cannot add more rooms."
        ], 422);
    }

    // Floor distribution constraint: at least 1 room per floor.
    // Logic: Remaining Slots >= Count of floors currently having 0 rooms (excluding the floor we are adding to if it was empty)

    $roomsByFloor = $building->actualRooms()->get()->groupBy('floor_no')->map->count();
    $emptyFloors = [];
    for ($i = 1; $i <= $totalFloors; $i++) {
        if (!isset($roomsByFloor[$i]) || $roomsByFloor[$i] === 0) {
            $emptyFloors[] = $i;
        }
    }

    $addingFloorIsEmpty = (!isset($roomsByFloor[$validated['floor_no']]) || $roomsByFloor[$validated['floor_no']] === 0);
    $otherEmptyFloorsCount = $addingFloorIsEmpty ? count($emptyFloors) - 1 : count($emptyFloors);
    $remainingSlotsAfterThis = $totalTargetRooms - $currentRoomsCount - 1;

    if ($remainingSlotsAfterThis < $otherEmptyFloorsCount) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot add room to this floor. Doing so would prevent other floors from having at least one room assigned.'
        ], 422);
    }

    // Unique Room Code Check Removed per request

    $user = auth()->user();
    $validated['last_inspector_id'] = $user->id;

    if ($user->role === 'contributor') {
        $validated['approval_status'] = 'pending';

        // Notify Admins
        $school = FireSafetySchool::find($validated['school_id']);
        if ($school) {
            $alerts = $school->alerts ?? [];
            $alerts[] = [
                'id' => uniqid(),
                'title' => 'New Room Created (Pending Approval)',
                'description' => "Contributor {$user->name} created a new room and it requires administrator approval.",
                'type' => 'warning',
                'created_at' => now()->toDateTimeString()
            ];
            $school->alerts = $alerts;
            $school->save();
        }
    } else {
        $validated['approval_status'] = 'approved';
    }

    $room = FireSafetyRoom::create($validated);

    return response()->json([
        'success' => true,
        'message' => 'Room added successfully!',
        'room' => $room
    ]);
}
    // Get room details with extinguisher info
    public function getRoom($id)
    {
        $room = FireSafetyRoom::with(['building', 'extinguishersCoveringThisRoom.room'])->findOrFail($id);

        // Is this room the host (center) of ANY extinguisher?
        $hostedExtinguisher = FireSafetyExtinguisher::where('room_id', $id)->first();
        $isCenterRoom = !is_null($hostedExtinguisher);

        // Which extinguisher covers this room?
        $extinguisher = null;
        $hostRoomCode = null;
        $hostRoomId = null;

        if ($room->extinguishersCoveringThisRoom->count() > 0) {
            $ext = $room->extinguishersCoveringThisRoom->first();
            $extinguisher = [
                'id' => $ext->id,
                'code' => $ext->code,
                'type' => $ext->type,
                'status' => $ext->status,
                'pressure_level' => $ext->pressure_level,
                'date_checked' => $ext->date_checked
            ];
            $hostRoomId = $ext->room_id;
            $hostRoomCode = $ext->room?->room_code;
        }

        return response()->json([
            'id' => $room->id,
            'room_code' => $room->room_code,
            'room_name' => $room->room_name,
            'room_type' => $room->room_type,
            'room_type_config_id' => $room->room_type_config_id,
            'floor_no' => $room->floor_no,
            'remarks' => $room->remarks,
            'has_smoke_detector' => $room->has_smoke_detector,
            'has_secondary_exit' => $room->has_secondary_exit,
            'secondary_exit_remarks' => $room->secondary_exit_remarks,
            'is_center_room' => $isCenterRoom,
            'host_room_id' => $hostRoomId,
            'host_room_code' => $hostRoomCode,
            'building' => $room->building->building_no ?? 'N/A',
            'extinguisher' => $extinguisher,
            'approval_status' => $room->approval_status,
            'last_inspector_id' => $room->last_inspector_id,
            'last_inspector_role' => $room->lastInspector->role ?? 'admin'
        ]);
    }
    // Store extinguisher (AJAX) - room-based coverage rules enforced here
    public function storeExtinguisher(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot add extinguishers.'], 403);
        }

        // floor, room and coverage are now optional to allow creation without assignment
        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'building_id' => 'required|exists:firesafety_buildings,id',
            'code' => 'required|string|max:50',
            'type' => 'required|string|max:50', // ABC, CO2, etc.
            'status' => 'required|in:active,expired,maintenance,missing,purchase,decommissioned',
            'pressure_level' => 'required|integer|min:0|max:100',
            'date_checked' => 'nullable|date',
            'evaluation_result' => 'nullable|string|max:255',
            'room_id' => 'nullable|exists:fire_safety_rooms,id', // allow unassigned extinguishers
            'covered_room_ids' => 'nullable|array|max:5', // limit in browser but backend checks priority
            'covered_room_ids.*' => 'integer|exists:fire_safety_rooms,id',
            'remarks' => 'nullable|string',
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

        // if a center room is provided, perform the usual validations related to it
        $centerRoom = null;
        if (!empty($validated['room_id'])) {
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
        }

        $coveredRoomIds = [];
        if (!empty($validated['covered_room_ids'])) {
            $coveredRoomIds = array_values(array_unique($validated['covered_room_ids']));

            // if center room exists, ensure it's part of coverage
            if ($centerRoom && !in_array((int)$centerRoom->id, array_map('intval', $coveredRoomIds), true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Covered rooms must include the center room.'
                ], 422);
            }
        }

        // Validate all covered rooms belong to same building/school (only if any were supplied)
        if (count($coveredRoomIds) > 0) {
            $coveredRooms = FireSafetyRoom::whereIn('id', $coveredRoomIds)
                ->where('building_id', $validated['building_id'])
                ->where('school_id', $validated['school_id'])
                ->get();
        }
        if (isset($coveredRooms) && $coveredRooms->count() !== count($coveredRoomIds)) {
            return response()->json([
                'success' => false,
                'message' => 'All covered rooms must belong to the selected building and school.'
            ], 422);
        }

        // Do not allow installing multiple extinguishers in the same host/center room
        if (!empty($validated['room_id'])) {
            $centerAlreadyHosts = FireSafetyExtinguisher::where('room_id', $validated['room_id'])->exists();
            if ($centerAlreadyHosts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected center room already hosts an extinguisher.'
                ], 422);
            }
        }

        // Covered rooms must NOT include rooms that already host an extinguisher (except the selected center room itself)
        if (count($coveredRoomIds) > 0) {
            $coveredHostRooms = FireSafetyExtinguisher::whereNotNull('room_id')
                ->whereIn('room_id', array_values(array_diff($coveredRoomIds, [(int)$validated['room_id']])))
                ->pluck('room_id')
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all();
            if (!empty($coveredHostRooms)) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more selected covered rooms already host their own extinguisher. Remove them from Covered Rooms.'
                ], 422);
            }
        }

        // Exclude Dedicated / Limited Shared rooms from being covered (they should host their own extinguisher)
        if (count($coveredRoomIds) > 0) {
            $centerId = (int) $validated['room_id'];
            $roomsToValidate = array_values(array_diff($coveredRoomIds, [$centerId]));
            if (count($roomsToValidate) > 0) {
                $dedicatedLike = FireSafetyRoom::whereIn('id', $roomsToValidate)
                    ->with(['roomTypeConfig.parent'])
                    ->get()
                    ->filter(function ($r) {
                        $label = $r->roomTypeConfig?->parent?->name ?? $r->calculated_priority_label ?? '';
                        $norm = strtolower(trim((string) $label));
                        $norm = str_replace(['_', '-'], ' ', $norm);
                        $norm = preg_replace('/\s+/', ' ', $norm);
                        return in_array($norm, ['dedicated', 'limited shared'], true);
                    })
                    ->pluck('id')
                    ->map(fn ($v) => (int) $v)
                    ->values()
                    ->all();
                if (!empty($dedicatedLike)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dedicated / Limited Shared rooms should not be selected as covered rooms. They must host their own extinguisher.'
                    ], 422);
                }
            }
        }

        // PRIORITY-BASED COVERAGE LIMIT (only when a center room is selected)
        if ($centerRoom) {
            $centerRoom->load(['roomTypeConfig.parent']);
            $priority = $centerRoom->roomTypeConfig?->parent;
            $maxLimit = 3; // Default fallback
            if ($priority && $priority->config_type === 'calculated_priority') {
                $maxLimit = (int) ($priority->max_rooms_covered ?? 3);
            } else {
                // Legacy fallback if priority config is missing
                $maxLimit = in_array(strtolower($centerRoom->room_type), ['classroom', 'department', 'library']) ? 3 : 2;
            }

            if (count($coveredRoomIds) > $maxLimit) {
                $typeName = $centerRoom->roomTypeConfig?->name ?? $centerRoom->room_type;
                $priorityName = $priority ? $priority->name : "Standard";
                return response()->json([
                    'success' => false,
                    'message' => "Room type ($typeName) with priority '$priorityName' can only cover up to $maxLimit rooms."
                ], 422);
            }
        }

        // Allow a room to host its own extinguisher even if it was previously "covered" by another extinguisher.
        // We'll remove any existing coverage rows for the selected center room INSIDE the transaction below,
        // so a later validation failure does not accidentally clear coverage.
        if ($centerRoom && count($coveredRoomIds) > 0) {
            $centerId = (int) $validated['room_id'];
            $coveredRoomIdsToCheck = array_values(array_diff(array_map('intval', $coveredRoomIds), [$centerId]));

            if (count($coveredRoomIdsToCheck) > 0) {
                $alreadyCovered = DB::table('fire_safety_extinguisher_room_coverage')
                    ->whereIn('room_id', $coveredRoomIdsToCheck)
                    ->pluck('room_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (!empty($alreadyCovered)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more selected rooms already have an extinguisher assigned.'
                    ], 422);
                }
            }
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
            // Automatic Evaluation Result: Only 'active' is Passed
            $evaluationResult = ($validated['status'] === 'active') ? 'Passed' : 'Failed';

            // If a center room is selected, clear previous coverage rows for that room
            if (!empty($validated['room_id'])) {
                DB::table('fire_safety_extinguisher_room_coverage')
                    ->where('room_id', (int) $validated['room_id'])
                    ->delete();
            }

            $ext = FireSafetyExtinguisher::create([
                'school_id' => $validated['school_id'],
                'building_id' => $validated['building_id'],
                'room_id' => $validated['room_id'] ?? null,
                'code' => $validated['code'],
                'type' => $validated['type'],
                'status' => $validated['status'],
                'pressure_level' => $validated['pressure_level'],
                // DB column is non-null in older installs; default to today if empty
                'date_checked' => $validated['date_checked'] ?? now()->toDateString(),
                'evaluation_result' => $evaluationResult,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            if (!empty($validated['room_id']) && count($coveredRoomIds) > 0) {
                $ext->coveredRooms()->sync($coveredRoomIds);
            }

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

    // Get single extinguisher details
    public function getExtinguisher($id)
    {
        try {
            $extinguisher = FireSafetyExtinguisher::with([
                'building',
                'room',
                'coveredRooms',
                'building.actualRooms' // Add this to get all rooms in the building
            ])->findOrFail($id);

            // Get all rooms in the same building
            $buildingRooms = collect([]);
            if ($extinguisher->building) {
                $buildingRooms = $extinguisher->building->actualRooms;
            }

            // Get all room IDs covered by OTHER extinguishers in this building
            $allCoveredRoomIds = [];
            if ($extinguisher->building) {
                $allCoveredRoomIds = FireSafetyExtinguisher::where('building_id', $extinguisher->building_id)
                    ->where('id', '!=', $extinguisher->id)
                    ->with('coveredRooms')
                    ->get()
                    ->flatMap(fn($e) => $e->coveredRooms->pluck('id'))
                    ->unique()
                    ->values()
                    ->toArray();
            }

            return response()->json([
                'id' => $extinguisher->id,
                'code' => $extinguisher->code,
                'type' => $extinguisher->type,
                'status' => $extinguisher->status,
                'pressure_level' => $extinguisher->pressure_level,
                'date_checked' => $extinguisher->date_checked,
                'evaluation_result' => $extinguisher->evaluation_result,
                'remarks' => $extinguisher->remarks,
                'building_id' => $extinguisher->building_id,
                'room_id' => $extinguisher->room_id,
                'floor_no' => $extinguisher->room?->floor_no,
                'building' => $extinguisher->building ? [
                    'id' => $extinguisher->building->id,
                    'building_no' => $extinguisher->building->building_no,
                    'building_name' => $extinguisher->building->building_name,
                ] : null,
                'room' => $extinguisher->room ? [
                    'id' => $extinguisher->room->id,
                    'room_name' => $extinguisher->room->room_name,
                    'room_code' => $extinguisher->room->room_code,
                    'floor_no' => $extinguisher->room->floor_no,
                    'room_type' => $extinguisher->room->room_type,
                ] : null,
                'covered_rooms' => $extinguisher->coveredRooms->map(function($room) {
                    return [
                        'id' => $room->id,
                        'room_code' => $room->room_code,
                        'room_name' => $room->room_name,
                        'floor_no' => $room->floor_no,
                        'room_type' => $room->room_type,
                    ];
                })->toArray(),
                'all_covered_room_ids' => $allCoveredRoomIds,
                'building_rooms' => $buildingRooms->map(function($room) {
                    return [
                        'id' => $room->id,
                        'room_code' => $room->room_code,
                        'room_name' => $room->room_name,
                        'floor_no' => $room->floor_no,
                        'room_type' => $room->room_type,
                    ];
                })->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching extinguisher: ' . $e->getMessage());
            return response()->json(['error' => 'Extinguisher not found'], 404);
        }
    }

    public function getBuildingRoomsWithCoverage($buildingId)
    {
        try {
            $building = FireSafetyBuilding::findOrFail($buildingId);
            $building->load(['actualRooms.roomTypeConfig.parent']);

            // Get all extinguishers in this building with their covered rooms
            $extinguishers = FireSafetyExtinguisher::where('building_id', $buildingId)
                ->with('coveredRooms')
                ->get();

            // Get all covered room IDs
            $coveredRoomIds = $extinguishers
                ->flatMap(fn($ext) => $ext->coveredRooms->pluck('id'))
                ->unique()
                ->values()
                ->toArray();

            // Rooms that host (center) an extinguisher in this building
            $hostRoomIds = $extinguishers
                ->pluck('room_id')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->toArray();

            // Build covering extinguisher lookup (room_id -> extinguisher_code)
            $coveringMap = [];
            foreach ($extinguishers as $ext) {
                foreach ($ext->coveredRooms as $r) {
                    $coveringMap[(int) $r->id] = [
                        'id' => (int) $ext->id,
                        'code' => (string) $ext->code,
                    ];
                }
            }

            // Get room details with coverage status
            $rooms = $building->actualRooms->map(function($room) use ($coveredRoomIds, $hostRoomIds, $coveringMap) {
                $priority = $room->roomTypeConfig?->parent;
                $maxLimit = ($priority && $priority->config_type === 'calculated_priority')
                    ? (int)($priority->max_rooms_covered ?? 3)
                    : (in_array(strtolower($room->room_type), ['classroom', 'department', 'library']) ? 3 : 2);

                $priorityLabel = $priority?->name ?? $room->calculated_priority_label ?? null;
                $covering = $coveringMap[(int)$room->id] ?? null;

                return [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'room_name' => $room->room_name,
                    'floor_no' => $room->floor_no,
                    'room_type' => $room->room_type,
                    'max_rooms' => $maxLimit,
                    'priority_label' => $priorityLabel,
                    'is_covered' => in_array($room->id, $coveredRoomIds),
                    'is_host_room' => in_array((int)$room->id, $hostRoomIds, true),
                    'covering_extinguisher_id' => $covering['id'] ?? null,
                    'covering_extinguisher_code' => $covering['code'] ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'rooms' => $rooms,
                'covered_room_ids' => $coveredRoomIds,
                'host_room_ids' => $hostRoomIds,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching building rooms: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load rooms'], 500);
        }
    }

    private function getCoveringExtinguisherId($roomId)
    {
        $coverage = DB::table('fire_safety_extinguisher_room_coverage')
            ->where('room_id', $roomId)
            ->first();

        return $coverage ? $coverage->extinguisher_id : null;
    }

    // Update Extinguisher Status & Log Inspection
    public function updateExtinguisher(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update extinguishers.'], 403);
        }

        $request->validate([
            'status' => 'required|in:active,expired,maintenance,missing,purchase,decommissioned',
            'pressure_level' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string',
            'room_id' => 'required|integer|exists:fire_safety_rooms,id',
            'covered_room_ids' => 'nullable|array',
            'covered_room_ids.*' => 'integer|exists:fire_safety_rooms,id'
        ]);

        try {
            DB::beginTransaction();

            $ext = FireSafetyExtinguisher::findOrFail($id);

            // A room can host its own extinguisher even if it was previously covered by another extinguisher.
            // But it cannot host multiple extinguishers at once.
            $newCenterId = $request->room_id ? (int) $request->room_id : null;
            if ($newCenterId) {
                $hostsAnotherExt = FireSafetyExtinguisher::where('room_id', $newCenterId)
                    ->where('id', '!=', $id)
                    ->exists();
                if ($hostsAnotherExt) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected center room already hosts another extinguisher.'
                    ], 422);
                }

                // Ensure the new center room is NOT covered by other extinguishers anymore
                DB::table('fire_safety_extinguisher_room_coverage')
                    ->where('room_id', $newCenterId)
                    ->where('extinguisher_id', '!=', $id)
                    ->delete();
            }

            $ext->status = $request->status;
            $ext->pressure_level = $request->pressure_level;
            $ext->date_checked = now();
            $ext->room_id = $request->room_id ?: null;
            $ext->evaluation_result = ($request->status === 'active') ? 'Passed' : 'Failed';
            $ext->save();

            // Update covered rooms if provided
            if ($request->has('covered_room_ids')) {
                $coveredRoomIds = $request->covered_room_ids ?? [];

                // Ensure center room is included in covered rooms
                if ($request->room_id && !in_array($request->room_id, $coveredRoomIds)) {
                    $coveredRoomIds[] = $request->room_id;
                }

                // Do not allow covered rooms that already host an extinguisher (except this extinguisher's own center room)
                $centerId = $request->room_id ? (int) $request->room_id : 0;
                $coveredInt = array_map('intval', $coveredRoomIds);
                $coveredNoCenter = array_values(array_diff($coveredInt, [$centerId]));
                if (count($coveredNoCenter) > 0) {
                    $hostRoomIds = FireSafetyExtinguisher::whereNotNull('room_id')
                        ->whereIn('room_id', $coveredNoCenter)
                        ->pluck('room_id')
                        ->map(fn ($v) => (int) $v)
                        ->values()
                        ->all();
                    if (!empty($hostRoomIds)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'One or more selected covered rooms already host their own extinguisher. Remove them from Covered Rooms.'
                        ], 422);
                    }
                }

                // Exclude Dedicated / Limited Shared rooms from being covered (except the center room itself)
                if (count($coveredNoCenter) > 0) {
                    $dedicatedLike = FireSafetyRoom::whereIn('id', $coveredNoCenter)
                        ->with(['roomTypeConfig.parent'])
                        ->get()
                        ->filter(function ($r) {
                            $label = $r->roomTypeConfig?->parent?->name ?? $r->calculated_priority_label ?? '';
                            $norm = strtolower(trim((string) $label));
                            $norm = str_replace(['_', '-'], ' ', $norm);
                            $norm = preg_replace('/\s+/', ' ', $norm);
                            return in_array($norm, ['dedicated', 'limited shared'], true);
                        })
                        ->pluck('id')
                        ->map(fn ($v) => (int) $v)
                        ->values()
                        ->all();
                    if (!empty($dedicatedLike)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Dedicated / Limited Shared rooms should not be selected as covered rooms. They must host their own extinguisher.'
                        ], 422);
                    }
                }

                // PRIORITY-BASED COVERAGE LIMIT VALIDATION
                $centerRoom = FireSafetyRoom::with(['roomTypeConfig.parent'])->find($request->room_id);
                $priority = $centerRoom->roomTypeConfig?->parent;
                $maxLimit = 3;
                if ($priority && $priority->config_type === 'calculated_priority') {
                    $maxLimit = (int) ($priority->max_rooms_covered ?? 3);
                } else {
                    $maxLimit = in_array(strtolower($centerRoom->room_type), ['classroom', 'department', 'library']) ? 3 : 2;
                }

                if (count($coveredRoomIds) > $maxLimit) {
                    $typeName = $centerRoom->roomTypeConfig?->name ?? $centerRoom->room_type;
                    $priorityName = $priority ? $priority->name : "Standard";
                    return response()->json([
                        'success' => false,
                        'message' => "Room type ($typeName) with priority '$priorityName' can only cover up to $maxLimit rooms."
                    ], 422);
                }

                // Disallow overlapping coverage for NON-center rooms (center is already force-uncovered above)
                if (count($coveredNoCenter) > 0) {
                    $alreadyCovered = DB::table('fire_safety_extinguisher_room_coverage')
                        ->whereIn('room_id', $coveredNoCenter)
                        ->where('extinguisher_id', '!=', $id)
                        ->pluck('room_id')
                        ->map(fn ($v) => (int) $v)
                        ->values()
                        ->all();
                    if (!empty($alreadyCovered)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'One or more selected rooms already have an extinguisher assigned.'
                        ], 422);
                    }
                }

                $ext->coveredRooms()->sync($coveredRoomIds);
            }

            // Log inspection
            FireSafetyExtinguisherInspection::create([
                'extinguisher_id' => $ext->id,
                'user_id' => Auth::id(),
                'inspection_date' => now(),
                'status' => $ext->status,
                'pressure_level' => $ext->pressure_level,
                'notes' => $request->notes
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Extinguisher updated successfully!']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating extinguisher: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update extinguisher.'], 500);
        }
    }

    public function unassignExtinguisher(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot modify extinguishers.'], 403);
        }

        try {
            DB::beginTransaction();

            $ext = FireSafetyExtinguisher::findOrFail($id);

            // Remove all room coverage
            DB::table('fire_safety_extinguisher_room_coverage')->where('extinguisher_id', $id)->delete();

            // Unlink center room
            $ext->room_id = null;
            $ext->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Extinguisher assignment removed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error unassigning extinguisher: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove assignment.'], 500);
        }
    }

    public function removeExtinguisher(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot remove extinguishers.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $ext = FireSafetyExtinguisher::with(['building', 'room'])->findOrFail($id);

            // Create archive entry
            FireSafetyArchive::create([
                'school_id' => $ext->school_id,
                'type' => 'extinguisher',
                'item_id' => $ext->id,
                'item_code' => $ext->code,
                'item_data' => [
                    'type' => $ext->type,
                    'status' => $ext->status,
                    'pressure_level' => $ext->pressure_level,
                    'building_name' => $ext->building?->building_name ?? 'N/A',
                    'floor_no' => $ext->room?->floor_no ?? 'N/A',
                    'room_name' => $ext->room?->room_name ?? 'N/A'
                ],
                'reason' => $request->reason,
                'removed_at' => now()
            ]);

            // Remove coverage associations
            DB::table('fire_safety_extinguisher_room_coverage')->where('extinguisher_id', $ext->id)->delete();

            // Delete the extinguisher
            $ext->delete();

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getExtinguisherHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('school_id', $schoolId)
            ->where('type', 'extinguisher')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function getRoomHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('school_id', $schoolId)
            ->where('type', 'room')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function removeRoom(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot remove rooms.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $room = FireSafetyRoom::with(['building'])->findOrFail($id);
            $building = $room->building;

            $message = $this->processRoomRemoval($room, $request->reason);

            if ($building) {
                // Ensure room removals from the Extinguisher page DO NOT reduce the Total Rooms capacity of the Building
                // We only process the room archival, leaving the overall building room quota intact.
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing room: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove room.'], 500);
        }
    }

    // Get Recent Inspections (AJAX)
    public function getRecentExtinguisherInspections($schoolId)
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && (int)$user->school_id !== (int)$schoolId) {
            return response()->json(['error' => 'Unauthorized access to this school.'], 403);
        }

        $inspections = FireSafetyExtinguisherInspection::whereHas('extinguisher', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->with(['extinguisher', 'extinguisher.building', 'extinguisher.centerRoom', 'inspector'])
            ->latest('inspection_date')
            ->take(10) // Limit to last 10
            ->get()
            ->map(function($insp) {
                return [
                    'date' => \Carbon\Carbon::parse($insp->inspection_date)->format('Y-m-d'),
                    'code' => $insp->extinguisher->code,
                    'location' => ($insp->extinguisher->building->building_no ?? '?') . ' - ' . ($insp->extinguisher->centerRoom->room_name ?? '?'),
                    'inspector' => $insp->inspector->name ?? 'Unknown',
                    'status' => $insp->status,
                    'pressure_level' => $insp->pressure_level,
                    'notes' => $insp->notes
                ];
            });

        return response()->json($inspections);
    }

    public function getRecentRoomUpdates($schoolId)
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && (int)$user->school_id !== (int)$schoolId) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $updates = FireSafetyRoom::where('school_id', $schoolId)
            ->with(['building', 'nearestExtinguisherRoom', 'hostedExtinguisher', 'lastInspector'])
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(function($r) {
                $nearest = 'None / Uncovered';
                if ($r->hostedExtinguisher) {
                    $nearest = 'HOST ROOM';
                } elseif ($r->nearestExtinguisherRoom) {
                    $nearest = $r->nearestExtinguisherRoom->room_code;
                }

                $remarks = $r->remarks ?? '-';
                if ($r->approval_status === 'pending') {
                    $remarks .= ' (Pending Approval)';
                } elseif ($r->approval_status === 'approved' && $r->lastInspector && $r->lastInspector->role === 'contributor') {
                    $remarks .= ' (Approve)';
                } elseif ($r->approval_status === 'rejected') {
                    $remarks .= ' (Not Approve)';
                }

                return [
                    'room_id' => $r->id,
                    'room_code' => $r->room_code,
                    'room_name' => $r->room_name,
                    'floor_level' => $r->floor_label,
                    'building_code' => $r->building->building_no ?? '?',
                    'nearest_extinguisher' => $nearest,
                    'inspector' => $r->lastInspector->name ?? 'Unknown',
                    'remarks' => $remarks,
                    'last_updated' => $r->updated_at->format('Y-m-d H:i'),
                    'approval_status' => $r->approval_status
                ];
            });

        return response()->json($updates);
    }

    public function buildings()
    {
        $query = FireSafetySchool::with(['buildings', 'buildings.alarmSystems', 'buildings.fireExtinguishers']);

        if (auth()->user()->role !== 'admin') {
            if (auth()->user()->role === 'viewer' && !isset(auth()->user()->school_id)) {
                return redirect()->route('fire-safety.dashboard')->with('error', 'No school assigned.');
            }
            $query->where('id', auth()->user()->school_id);
        }

        $schools = $query->get();
        if ($schools->isEmpty() && auth()->user()->role !== 'admin') {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Unauthorized or no school assigned.');
        }

        $activeSchool = $this->getActiveSchool($schools);
        $buildingTypes = SystemConfiguration::where('config_type', 'building_type')->where('is_active', true)->orderBy('sort_order')->get();
        $checklists = SystemConfiguration::where('config_type', 'inspection_checklist')->where('is_active', true)->orderBy('sort_order')->get();
        $observers = SystemConfiguration::where('config_type', 'inspection_observer')->where('is_active', true)->orderBy('sort_order')->get();

        $alarmTypes = SystemConfiguration::where('config_type', 'alarm_type')->where('is_active', true)->orderBy('sort_order')->get();
        $alarmStatusesByType = SystemConfiguration::where('config_type', 'alarm_status')->where('is_active', true)->whereNotNull('parent_id')->get()->groupBy('parent_id');

        return view('fire-safety.buildings',[
            'schools' => $schools,
            'activeSchool' => $activeSchool,
            'buildingTypes' => $buildingTypes,
            'checklists' => $checklists,
            'observers' => $observers,
            'alarmTypes' => $alarmTypes,
            'alarmStatusesByType' => $alarmStatusesByType,
            'isViewer' => auth()->user()->role === 'viewer'
        ]);
    }

    public static function calculateBuildingCompliance($building)
    {
        // Start from the model's computed safety score (0–100)
        $score = $building->safety_score;

        // Requirement 1: Building must have at least one active alarm covering it
        // "Installed Here" (building_id) OR listed as "Covering" via the pivot table
        $activeAlarmStatuses = ['active', 'functional', 'online'];

        $directAlarmIds = $building->alarmSystems()
            ->whereIn('status', $activeAlarmStatuses)
            ->pluck('firesafety_alarm_systems.id')
            ->toArray();

        $coveredAlarmIds = $building->alarmSystemsMany()
            ->whereIn('status', $activeAlarmStatuses)
            ->pluck('firesafety_alarm_systems.id')
            ->toArray();

        $hasCoveringAlarm = count(array_unique(array_merge($directAlarmIds, $coveredAlarmIds))) > 0;

        // Requirement 2: All rooms must exist and be covered by a fire extinguisher
        $totalRooms = (int) ($building->rooms ?? 0);
        $actualRoomsCount = $building->actualRooms()->count();
        $coveredRoomsCount = $building->actualRooms()
            ->whereHas('extinguishersCoveringThisRoom')
            ->count();

        $roomsFullyCovered = $totalRooms === 0
            ? true
            : ($actualRoomsCount >= $totalRooms && $coveredRoomsCount >= $totalRooms);

        // Enforce that a building cannot be "Compliant" (>= 80)
        // unless it has both an alarm covering it and full room coverage.
        if (!$hasCoveringAlarm || !$roomsFullyCovered) {
            $score = min($score, 75);
        }

        return $score;
    }

    // Store new building
    public function storeBuilding(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot add buildings.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int)$request->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'building_no' => 'required|string|max:50',
            'building_name' => 'nullable|string|max:100',
            'floors' => 'required|integer|min:1',
            'rooms' => 'required|integer|min:1',
            'year_constructed' => 'nullable|integer|min:1900|max:' . date('Y'),
            'last_renovation' => 'nullable|integer|min:1900|max:' . date('Y'),
            'emergency_exits' => 'nullable|integer|min:0',
            'building_type' => 'nullable|string',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'required_extinguishers' => 'nullable|integer|min:0'
        ]);

        // Unique Building Code Check (Per School)
        $exists = FireSafetyBuilding::where('school_id', $validated['school_id'])
            ->where('building_no', $validated['building_no'])
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Building code already exists in this school.'
            ], 422);
        }

        // Ensure rooms >= floors
        if ($validated['rooms'] < $validated['floors']) {
            return response()->json([
                'success' => false,
                'message' => 'Total rooms cannot be less than total floors.'
            ], 422);
        }

        // Gymnasium & Cafeteria restriction: only 1 room
    if (in_array(strtolower($validated['building_type']), ['gymnasium', 'cafeteria'])) {
        $validated['rooms'] = 1;
    }

    // Initialize limit columns
    $validated['max_floors'] = $validated['floors'];
    $validated['max_rooms'] = $validated['rooms'];

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

    // Update building
    public function updateBuilding(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update buildings.'], 403);
        }

        $building = FireSafetyBuilding::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$building->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this building.'], 403);
        }

        $validated = $request->validate([
            'building_no' => 'required|string|max:50',
            'building_name' => 'nullable|string|max:100',
            'floors' => 'nullable|integer|min:1',
            'rooms' => 'nullable|integer|min:1',
            'year_constructed' => 'nullable|integer|min:1900|max:' . date('Y'),
            'last_renovation' => 'nullable|integer|min:1900|max:' . date('Y'),
            'emergency_exits' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'removed_floor' => 'nullable|integer',
            'removed_room_id' => 'nullable|array',
            'removed_room_id.*' => 'exists:fire_safety_rooms,id',
            'floor_removal_reason' => 'nullable|required_with:removed_floor|string|max:500',
            'room_removal_reason' => 'nullable|required_with:removed_room_id|string|max:500',
            'required_extinguishers' => 'nullable|integer|min:0'
        ]);

        // Check uniqueness if building_no is changing
        if ($validated['building_no'] !== $building->building_no) {
            $exists = FireSafetyBuilding::where('school_id', $building->school_id)
                ->where('building_no', $validated['building_no'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Building code already exists in this school.'
                ], 422);
            }
        }

        // Gymnasium & Cafeteria restriction is enforced at creation only
        // Building type cannot be changed after creation

        // Convert features array to comma-separated string
        if (isset($validated['features'])) {
            $validated['features'] = implode(',', $validated['features']);
        } else {
            // If no features selected (unchecked all), clear the column
            $validated['features'] = null;
        }

        // Handle Cascading Removals
        $cascadingMessages = [];

        DB::beginTransaction();
        try {
            // 1. Floor Removal
            if ($request->filled('removed_floor')) {
                $floorNo = $request->removed_floor;
                $reason = $request->floor_removal_reason;

                // Archive Floor
                FireSafetyArchive::create([
                    'school_id' => $building->school_id,
                    'type' => 'floor',
                    'item_id' => null,
                    'item_code' => "FLR-{$floorNo}",
                    'item_data' => [
                        'building_name' => $building->building_name ?? $building->building_no,
                        'building_no' => $building->building_no,
                        'floor_no' => $floorNo
                    ],
                    'reason' => $reason,
                    'removed_at' => now()
                ]);

                // Archive & Remove Alarms on this floor
                // Match location: "1st Floor", "2nd Floor", "Floor 1", "Floor 2", etc.
                $alarmsToRemove = FireSafetyAlarmSystem::where('building_id', $id)
                    ->where(function ($q) use ($floorNo) {
                        $q->where('location', 'like', "%{$floorNo}st Floor%")
                          ->orWhere('location', 'like', "%{$floorNo}nd Floor%")
                          ->orWhere('location', 'like', "%{$floorNo}rd Floor%")
                          ->orWhere('location', 'like', "%{$floorNo}th Floor%")
                          ->orWhere('location', 'like', "%Floor {$floorNo} %")
                          ->orWhere('location', 'like', "%Floor {$floorNo},%")
                          ->orWhere('location', 'like', "%Floor {$floorNo}-%")
                          ->orWhere('location', 'like', "%Floor {$floorNo}%");
                    })
                    ->get();

                foreach ($alarmsToRemove as $alarm) {
                    FireSafetyArchive::create([
                        'school_id' => $alarm->school_id,
                        'type' => 'alarm',
                        'item_id' => $alarm->id,
                        'item_code' => $alarm->code,
                        'item_data' => [
                            'alarm_type' => $alarm->alarm_type,
                            'status' => $alarm->status,
                            'building_name' => $building->building_name ?? 'N/A',
                            'manufacturer' => $alarm->manufacturer,
                            'last_test' => $alarm->last_test,
                            'cascaded_from' => "Floor {$floorNo} Removal"
                        ],
                        'reason' => "Cascading removal: Floor {$floorNo} removed. Reason: " . $reason,
                        'removed_at' => now()
                    ]);

                    $alarm->buildings()->detach();
                    $alarm->delete();
                }

                // Get rooms on this floor to remove extinguishers
                $roomsOnFloor = FireSafetyRoom::where('building_id', $id)->where('floor_no', $floorNo)->get();
                foreach ($roomsOnFloor as $room) {
                    $this->processRoomRemoval($room, "Cascading removal: Floor {$floorNo} removed. Reason: " . $reason);
                }

                $building->decrement('floors');
                $cascadingMessages[] = "Floor {$floorNo} and its associated safety systems were archived and removed.";
            }

            // 2. Room Removal
            if ($request->has('removed_room_id') && is_array($request->removed_room_id)) {
                $reason = $request->room_removal_reason;
                foreach ($request->removed_room_id as $roomId) {
                    $room = FireSafetyRoom::find($roomId);
                    if ($room) {
                        $res = $this->processRoomRemoval($room, $reason);
                        $building->decrement('rooms');
                        if ($res) $cascadingMessages[] = $res;
                    }
                }
            }

            // 3. Handle Floor and Room Increments (do not overwrite floors when we just removed one)
            if (!$request->filled('removed_floor') && $request->filled('floors') && (int)$request->floors > $building->floors) {
                $building->floors = (int)$request->floors;
            }

            if ($request->filled('rooms') && (int)$request->rooms > $building->rooms) {
                $building->rooms = (int)$request->rooms;
            }

            // Prepare fill data except for specific handling fields
            $fillData = collect($validated)->except(['removed_floor', 'removed_room_id', 'building_type', 'floors', 'rooms', 'floor_removal_reason', 'room_removal_reason'])->toArray();

            $building->fill($fillData);
            $building->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Building updated successfully!' . (count($cascadingMessages) > 0 ? ' ' . implode(' ', $cascadingMessages) : '')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating building: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update building: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeBuilding(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot delete buildings.'], 403);
        }

        $building = FireSafetyBuilding::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$building->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            // Archive Building
            FireSafetyArchive::create([
                'school_id' => $building->school_id,
                'type' => 'building',
                'item_id' => null, // It's deleted
                'item_code' => $building->building_no,
                'item_data' => [
                    'building_name' => $building->building_name,
                    'type' => $building->building_type,
                    'required_fext' => $building->required_extinguishers,
                    'year_constructed' => $building->year_constructed,
                    'last_renovation' => $building->last_renovation,
                    'description' => $building->description,
                    'safety_features' => $building->features,
                ],
                'reason' => $validated['reason'],
                'removed_at' => now()
            ]);

            // Cascade deletes manually to be safe
            $building->alarmSystems()->delete();

            $rooms = FireSafetyRoom::where('building_id', $building->id)->get();
            foreach ($rooms as $room) {
               FireSafetyExtinguisher::where('room_id', $room->id)->delete();
               $room->delete();
            }

            $building->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Building removed successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing building: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove building: ' . $e->getMessage()
            ], 500);
        }
    }


    private function processRoomRemoval($room, $reason = 'Manual removal')
    {
        // Archive Room
        FireSafetyArchive::create([
            'school_id' => $room->school_id,
            'type' => 'room',
            'item_id' => $room->id,
            'item_code' => $room->room_code ?? "RM-{$room->id}",
            'item_data' => [
                'room_name' => $room->room_name,
                'room_type' => $room->room_type,
                'floor_no' => $room->floor_no,
                'building_name' => $room->building->building_name ?? $room->building->building_no
            ],
            'reason' => $reason,
            'removed_at' => now()
        ]);

        // Find extinguishers where this room is the center
        $extsAsCenter = FireSafetyExtinguisher::where('room_id', $room->id)->get();
        foreach ($extsAsCenter as $ext) {
            // Find other rooms covered by this extinguisher on same floor
            $otherRooms = $ext->coveredRooms()->where('fire_safety_rooms.id', '!=', $room->id)->get();
            if ($otherRooms->count() > 0) {
                $newCenter = $otherRooms->random();
                $ext->update(['room_id' => $newCenter->id]);
            } else {
                // Archive Extinguisher before delete
                FireSafetyArchive::create([
                    'school_id' => $ext->school_id,
                    'type' => 'extinguisher',
                    'item_id' => $ext->id,
                    'item_code' => $ext->code,
                    'item_data' => [
                        'type' => $ext->type,
                        'status' => $ext->status,
                        'pressure_level' => $ext->pressure_level,
                        'building_name' => $ext->building?->building_name ?? 'N/A',
                        'floor_no' => $room->floor_no ?? 'N/A',
                        'room_name' => $room->room_name ?? 'N/A',
                        'cascaded_from' => "Room {$room->room_name} Removal"
                    ],
                    'reason' => "Cascading removal: Room {$room->room_name} removed. Reason: " . $reason,
                    'removed_at' => now()
                ]);

                // Remove coverage associations
                DB::table('fire_safety_extinguisher_room_coverage')->where('extinguisher_id', $ext->id)->delete();
                $ext->delete(); // No rooms left to cover
            }
        }

        // Detach from all extinguisher coverages
        $room->extinguishersCoveringThisRoom()->detach();
        $roomName = $room->room_name;
        $room->delete();

        return "Room {$roomName} was archived and removed.";
    }

    // Get building details
    public function getBuilding($id)
    {
        Log::info('getBuilding called with id: ' . $id);
        try {
            $building = FireSafetyBuilding::with(['school', 'actualRooms'])->findOrFail($id);
            $building->load(['alarmSystems', 'alarmSystemsMany']);

            // Merge direct alarms and covered alarms
            $allAlarms = $building->alarmSystems->merge($building->alarmSystemsMany)->unique('id')->values();
            foreach ($allAlarms as $alarm) {
                // If floor_id is stored in a different field, map it here
                // For example, if it's stored in 'floor' or 'floor_no'
                if (!isset($alarm->floor_id) && isset($alarm->floor)) {
                    $alarm->floor_id = $alarm->floor;
                }
            }
            $building->setRelation('alarmSystems', $allAlarms);
            Log::info('Building found: ' . $building->id);

            // Check if user has access to this building's school
            if (auth()->user()->role !== 'admin' && $building->school_id !== auth()->user()->school_id) {
                Log::warning('Access denied for user ' . auth()->id() . ' to building ' . $id);
                return response()->json(['error' => 'Access denied'], 403);
            }

            // Add rooms_list for update modal
            $roomsList = $building->actualRooms->map(function($room) {
                // Check if room is a center room for any extinguisher
                $isCenterRoom = FireSafetyExtinguisher::where('room_id', $room->id)->exists();

                // Check if there are other rooms on the same floor
                $hasOthersOnFloor = FireSafetyRoom::where('building_id', $room->building_id)
                    ->where('floor_no', $room->floor_no)
                    ->where('id', '!=', $room->id)
                    ->exists();

                return [
                    'id' => $room->id,
                    'room_name' => $room->room_name,
                    'room_code' => $room->room_code,
                    'floor_no' => $room->floor_no,
                    'is_center_room' => $isCenterRoom,
                    'has_other_rooms_on_floor' => $hasOthersOnFloor
                ];
            });

            $buildingData = $building->toArray();
            $buildingData['rooms_list'] = $roomsList;
            $buildingData['required_extinguishers_count'] = $building->required_extinguishers_count;

            Log::info('Returning building data with rooms list');
            return response()->json($buildingData);
        } catch (\Exception $e) {
            Log::error('Error loading building details: ' . $e->getMessage());
            return response()->json(['error' => 'Building not found or error loading details'], 404);
        }
    }

    // Get inspections for a school
    public function getInspections($schoolId)
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && (int)$user->school_id !== (int)$schoolId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $inspections = FireSafetyInspection::where('school_id', $schoolId)
                ->orderBy('inspection_date', 'desc')
                ->get();

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
        $user = auth()->user();
        if ($user->role !== 'admin' && (int)$user->school_id !== (int)$schoolId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $buildings = FireSafetyBuilding::where('school_id', $schoolId)
            ->select('id', 'building_no', 'building_name', 'floors', 'rooms', 'building_type')
            ->get();

        return response()->json($buildings);
    }

    // Get specific inspection
    public function getInspection($id)
    {
        try {
            $inspection = FireSafetyInspection::with('school')->findOrFail($id);
            return response()->json($inspection);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Inspection not found'], 404);
        }
    }

    // Print inspection monitoring tool
    public function printInspection($id)
    {
        try {
            $inspection = FireSafetyInspection::with('school')->findOrFail($id);
            $checklists = SystemConfiguration::where('config_type', 'inspection_checklist')->orderBy('sort_order')->get();
            $observers = SystemConfiguration::where('config_type', 'inspection_observer')->orderBy('sort_order')->get();
            return view('fire-safety.reports.monitoring-tool', compact('inspection', 'checklists', 'observers'));
        } catch (\Exception $e) {
            Log::error('Print Inspection Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Inspection not found or unable to print. Error: ' . $e->getMessage());
        }
    }

    // Store inspection (Inspect Now)
    public function storeInspection(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot save inspections.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int)$request->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'drill_type' => 'required|string',
            'inspection_date' => 'required|date',
            'inspection_time' => 'required',
            'time_started' => 'nullable',
            'time_finished' => 'nullable',
            'elapsed_time' => 'nullable',
            'no_of_exits' => 'nullable|integer',
            'no_of_buildings' => 'nullable|integer',
            'no_of_students' => 'nullable|integer',
            'no_of_personnel' => 'nullable|integer',
            'monitored_by' => 'required|string',
            'monitored_by_position' => 'nullable|string',
            'checklist_data' => 'nullable|array',
            'observers_data' => 'nullable|array',
            'remarks' => 'nullable|string',
            'coordinator_name' => 'nullable|string',
            'school_head_name' => 'nullable|string',
        ]);

        $inspection = FireSafetyInspection::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inspection saved successfully!',
            'inspection' => $inspection
        ]);
    }

    // Update inspection
    public function updateInspection(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update inspections.'], 403);
        }

        $inspection = FireSafetyInspection::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$inspection->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this inspection.'], 403);
        }

        $validated = $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'drill_type' => 'required|string',
            'inspection_date' => 'required|date',
            'inspection_time' => 'required',
            'time_started' => 'nullable',
            'time_finished' => 'nullable',
            'elapsed_time' => 'nullable|string',
            'no_of_exits' => 'nullable|integer',
            'no_of_buildings' => 'nullable|integer',
            'no_of_students' => 'nullable|integer',
            'no_of_personnel' => 'nullable|integer',
            'monitored_by' => 'required|string',
            'monitored_by_position' => 'nullable|string',
            'checklist_data' => 'nullable|array',
            'observers_data' => 'nullable|array',
            'remarks' => 'nullable|string',
            'coordinator_name' => 'nullable|string',
            'school_head_name' => 'nullable|string',
        ]);

        $inspection->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inspection updated successfully!',
            'inspection' => $inspection
        ]);
    }

    // Placeholder checklist page to prevent 404 (until checklist UI is implemented)
    public function inspectionChecklist($id)
    {
        $inspection = FireSafetyInspection::with(['building', 'school'])->findOrFail($id);
        return view('fire-safety.inspection-checklist', [
            'inspection' => $inspection
        ]);
    }

    /**
     * Update Building Compliance Status based on all safety components
     * Building is "Compliant" (100%) when:
     * 1. Alarm system exists
     * 2. Fire extinguishers are added and passed status (meets minimum)
     * 3. Evacuation plan exists (90% for school-wide, 100% for building-specific)
     */
    private function updateBuildingComplianceStatus($building)
    {
        try {
            // rely on the model's safety_score accessor for calculation
            $score = $building->safety_score;

            $statusText = 'Poor';
            if ($score >= 100) {
                $statusText = 'Perfect';
            } elseif ($score >= 90) {
                $statusText = 'Passed';
            } elseif ($score >= 80) {
                $statusText = 'Good';
            } elseif ($score >= 60) {
                $statusText = 'Fair';
            }

            $building->update([
                'safety_score' => $score,
                'compliance_status' => $statusText,
                'compliance_reason' => null,
            ]);

            Log::info("Building {$building->building_no} compliance updated: {$statusText} ({$score}%)");
        } catch (\Exception $e) {
            Log::error("Error updating building compliance: " . $e->getMessage());
        }
    }

    public function evacuationPlans()
    {
        $query = FireSafetySchool::with([
            'buildings' => function($query) {
                $query->with([
                    'evacuationPlan',
                    'alarmSystems' => function($q) {
                        $q->where('status', 'active');
                    },
                    'fireExtinguishers' => function($q) {
                        $q->where('status', 'active');
                    },
                    'actualRooms'
                ]);
            },
            'buildings.evacuationPlan',
            'schoolEvacuationPlan'
        ]);

        if (auth()->user()->role !== 'admin') {
            $query->where('id', auth()->user()->school_id);
        }

        $schools = $query->get();
        $activeSchool = $this->getActiveSchool($schools);

        return view('fire-safety.evacuation-plans', compact('schools', 'activeSchool'));
    }
    public function storeEvacuationPlan(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot add evacuation plans.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int)$request->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'building_id' => 'nullable|exists:firesafety_buildings,id',
            'plan_no' => 'required|string|max:50',
            'exits' => 'nullable|integer|min:0',
            'routes' => 'nullable|integer|min:1|max:10',
            'areas' => 'nullable|string',
            'primary_route' => 'nullable|string',
            'secondary_route' => 'nullable|string',
            'primary_assembly_area' => 'nullable|string',
            'secondary_assembly_area' => 'nullable|string',
            'assembly_capacity' => 'nullable|integer|min:0',
            'status' => 'nullable|string',
            'safety_features_installed' => 'nullable|string',
            'emergency_contacts' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'map_data' => 'nullable|string',
        ]);

        // Prevent duplicate plans for the same building
        $existingPlan = FireSafetyEvacuationPlan::where('school_id', $request->school_id)->where('building_id', $request->building_id)->first();
        if ($existingPlan) {
            return response()->json(['success' => false, 'message' => 'An evacuation plan already exists for this building. Please edit the existing plan instead.'], 422);
        }

        // Ensure plan_no is unique within the school
        $planNoExists = FireSafetyEvacuationPlan::where('school_id', $request->school_id)
            ->where('plan_no', $request->plan_no)
            ->exists();
        if ($planNoExists) {
            return response()->json(['success' => false, 'message' => 'Plan name already exists in this school. Please use a different name.'], 422);
        }

        try {
            DB::beginTransaction();

            $plan = FireSafetyEvacuationPlan::create([
                'school_id' => $request->school_id,
                'building_id' => $request->building_id,
                'plan_no' => $request->plan_no,
                'exits' => $request->exits ?? 0,
                'routes' => $request->routes ?? 1,
                'areas' => $request->areas,
                'primary_route' => $request->primary_route,
                'secondary_route' => $request->secondary_route,
                'primary_assembly_area' => $request->primary_assembly_area,
                'secondary_assembly_area' => $request->secondary_assembly_area,
                'assembly_capacity' => $request->assembly_capacity ?? 0,
                'emergency_contacts' => $request->emergency_contacts,
                'special_instructions' => $request->special_instructions,
                'safety_features_installed' => $request->safety_features_installed,
                'status' => $request->status ?? 'active',
                'approved_at' => $request->status === 'active' ? now() : null,
                'map_data' => $request->map_data,
            ]);

            // Update building status if this is a building-specific plan
            if ($request->building_id) {
                $building = FireSafetyBuilding::findOrFail($request->building_id);
                $this->updateBuildingComplianceStatus($building);
            } else {
                // If it's a school-wide plan, update all buildings without specific plans
                $buildings = FireSafetyBuilding::where('school_id', $request->school_id)->get();
                foreach ($buildings as $building) {
                    $this->updateBuildingComplianceStatus($building);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Evacuation plan created successfully', 'plan' => $plan]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing evacuation plan: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create evacuation plan: ' . $e->getMessage()], 500);
        }
    }

    public function getEvacuationPlan($id)
    {
        $plan = FireSafetyEvacuationPlan::with('building')->findOrFail($id);
        return response()->json($plan);
    }

    public function getEvacuationPlanDetails($id)
    {
        $plan = FireSafetyEvacuationPlan::with(['building', 'school'])->findOrFail($id);

        // Load all buildings for the school with their safety components
        $schoolBuildings = FireSafetyBuilding::where('school_id', $plan->school_id)
            ->with(['actualRooms', 'fireExtinguishers', 'alarmSystemsMany'])
            ->get();

        return response()->json([
            'plan' => $plan,
            'building' => $plan->building,
            'school_buildings' => $schoolBuildings,
            'stats' => $plan->safety_equipment_summary,
            'status_label' => $plan->status_label,
            'status_color' => $plan->status_color
        ]);
    }

    public function updateEvacuationPlan(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update evacuation plans.'], 403);
        }

        $plan = FireSafetyEvacuationPlan::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$plan->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this plan.'], 403);
        }

        $request->validate([
            'plan_no' => 'required|string|max:50',
            'exits' => 'nullable|integer|min:0',
            'routes' => 'nullable|integer|min:1|max:10',
            'areas' => 'nullable|string',
            'primary_route' => 'nullable|string',
            'secondary_route' => 'nullable|string',
            'primary_assembly_area' => 'nullable|string',
            'secondary_assembly_area' => 'nullable|string',
            'assembly_capacity' => 'nullable|integer|min:0',
            'status' => 'required|string',
            'safety_features_installed' => 'nullable|string',
            'emergency_contacts' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'map_data' => 'nullable|string',
        ]);

        // ensure plan_no uniqueness
        if ($request->plan_no !== $plan->plan_no) {
            $exists = FireSafetyEvacuationPlan::where('school_id', $plan->school_id)
                        ->where('plan_no', $request->plan_no)
                        ->where('id', '<>', $plan->id)
                        ->exists();
            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Plan name already exists in this school.'], 422);
            }
        }

        $data = $request->only([
            'plan_no', 'exits', 'routes', 'areas',
            'primary_route', 'secondary_route',
            'primary_assembly_area', 'secondary_assembly_area',
            'assembly_capacity', 'emergency_contacts',
            'special_instructions', 'safety_features_installed', 'status', 'map_data'
        ]);

        // Ensure defaults for numeric fields
        $data['exits'] = $request->exits ?? 0;
        $data['routes'] = $request->routes ?? 1;
        $data['assembly_capacity'] = $request->assembly_capacity ?? 0;

        if ($request->status === 'active' && $plan->status !== 'active') {
            $data['approved_at'] = now();
        }

        DB::beginTransaction();
        try {
            $plan->update($data);

            // update related building compliance
            if ($plan->building_id) {
                $building = FireSafetyBuilding::find($plan->building_id);
                if ($building) {
                    $this->updateBuildingComplianceStatus($building);
                }
            } else {
                $buildings = FireSafetyBuilding::where('school_id', $plan->school_id)->get();
                foreach ($buildings as $building) {
                    $this->updateBuildingComplianceStatus($building);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating evacuation plan: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update evacuation plan.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Evacuation plan updated successfully', 'plan' => $plan]);
    }

    public function deleteEvacuationPlan($id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot delete evacuation plans.'], 403);
        }

        $plan = FireSafetyEvacuationPlan::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$plan->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this plan.'], 403);
        }

        $plan->delete();

        // after removing plan, refresh building compliance
        if ($plan->building_id) {
            $building = FireSafetyBuilding::find($plan->building_id);
            if ($building) {
                $this->updateBuildingComplianceStatus($building);
            }
        } else {
            // removed school-wide plan; update all buildings
            $buildings = FireSafetyBuilding::where('school_id', $plan->school_id)->get();
            foreach ($buildings as $building) {
                $this->updateBuildingComplianceStatus($building);
            }
        }

        return response()->json(['success' => true, 'message' => 'Evacuation plan deleted successfully']);
    }

    public function checkBuildingPlan($buildingId)
    {
        $plan = FireSafetyEvacuationPlan::where('building_id', $buildingId)->first();
        return response()->json([
            'has_plan' => $plan !== null,
            'plan_id' => $plan ? $plan->id : null
        ]);
    }

    public function getBuildingEvacuationData($buildingId)
    {
        $building = FireSafetyBuilding::findOrFail($buildingId);
        $featuresRaw = $building->features;
        $featuresDisplay = 'No safety features recorded';
        if (!empty($featuresRaw)) {
            $arr = is_array($featuresRaw) ? $featuresRaw : explode(',', (string) $featuresRaw);
            $labels = [
                'sprinklers' => 'Sprinkler System',
                'emergency_lights' => 'Emergency Lighting',
                'exit_signs' => 'Exit Signs',
                'fire_doors' => 'Fire Doors',
                'two_stairways' => 'Two Stairways',
                'smoke_detectors' => 'Smoke Detectors',
                'alarm_systems' => 'Alarm Systems',
            ];
            $featuresDisplay = implode(', ', array_filter(array_map(function ($v) use ($labels) {
                $v = trim(strtolower($v));
                if (empty($v)) return null;
                // Try direct mapping first
                if (isset($labels[$v])) return $labels[$v];
                // Try cleaning up keys with spaces or underscores
                $cleanKey = str_replace([' ', '-'], '_', $v);
                if (isset($labels[$cleanKey])) return $labels[$cleanKey];

                return ucwords(str_replace(['_', '-'], ' ', $v));
            }, $arr)));
        }
        return response()->json([
            'success' => true,
            'building' => [
                'building_name' => $building->building_name,
                'building_no' => $building->building_no,
                'rooms' => $building->rooms ?? 0,
                'emergency_exits' => $building->emergency_exits ?? 0,
                'alarms' => $building->functional_alarms_count,
                'extinguishers' => $building->active_extinguishers_count,
                'features' => $featuresDisplay,
            ]
        ]);
    }

    public function getDrillBuildings($schoolId)
    {
        $buildings = FireSafetyBuilding::where('school_id', $schoolId)->get();
        return response()->json($buildings);
    }

    public function scheduleDrill(Request $request)
    {
        if (!Schema::hasTable('fire_safety_evacuation_drills') || !Schema::hasTable('fire_safety_drill_building')) {
            return response()->json(['success' => false, 'message' => 'Evacuation drills module is disabled.'], 410);
        }

        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot schedule drills.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int)$request->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }
        $request->validate([
            'school_id' => 'required|exists:firesafety_school_information,id',
            'drill_type' => 'required|string',
            'drill_date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'participants_count' => 'nullable|integer',
            'evacuation_time_minutes' => 'nullable|integer',
            'status' => 'required|string',
            'coordinator' => 'nullable|string',
            'remarks' => 'nullable|string',
            'notes' => 'nullable|string',
            'building_ids' => 'required|array',
        ]);

        $drill = FireSafetyEvacuationDrill::create($request->only([
            'school_id', 'drill_type', 'drill_date', 'start_time', 'end_time',
            'participants_count', 'evacuation_time_minutes', 'status',
            'remarks', 'coordinator', 'notes'
        ]));

        $drill->buildings()->attach($request->building_ids);

        return response()->json(['success' => true, 'message' => 'Drill scheduled successfully']);
    }

    public function getDrill($id)
    {
        if (!Schema::hasTable('fire_safety_evacuation_drills') || !Schema::hasTable('fire_safety_drill_building')) {
            return response()->json(['success' => false, 'message' => 'Evacuation drills module is disabled.'], 410);
        }

        $drill = FireSafetyEvacuationDrill::with('buildings')->findOrFail($id);
        return response()->json($drill);
    }

    public function cancelDrill($id)
    {
        if (!Schema::hasTable('fire_safety_evacuation_drills') || !Schema::hasTable('fire_safety_drill_building')) {
            return response()->json(['success' => false, 'message' => 'Evacuation drills module is disabled.'], 410);
        }

        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot cancel drills.'], 403);
        }

        $drill = FireSafetyEvacuationDrill::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$drill->school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this drill.'], 403);
        }
        $drill->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Drill cancelled successfully']);
    }

    public function getEvacuationSidebarStats($schoolId)
    {
        $school = FireSafetySchool::withCount(['buildings', 'evacuationPlans'])->findOrFail($schoolId);
        $activePlans = FireSafetyEvacuationPlan::where('school_id', $schoolId)->where('status', 'active')->count();
        $draftPlans = FireSafetyEvacuationPlan::where('school_id', $schoolId)->where('status', 'draft')->count();

        return response()->json([
            'total_buildings' => $school->buildings_count,
            'total_plans' => $school->evacuation_plans_count,
            'active_plans' => $activePlans,
            'draft_plans' => $draftPlans,
            'no_plan' => $school->buildings_count - ($activePlans + $draftPlans),
        ]);
    }

    public function getPlanStats($schoolId)
    {
        $school = FireSafetySchool::withCount(['buildings', 'evacuationPlans'])->findOrFail($schoolId);

        $activePlans = FireSafetyEvacuationPlan::where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();

        $draftPlans = FireSafetyEvacuationPlan::where('school_id', $schoolId)
            ->where('status', 'draft')
            ->count();

        $buildingsWithPlansIds = FireSafetyEvacuationPlan::where('school_id', $schoolId)->pluck('building_id');
        $buildingsWithoutPlans = FireSafetyBuilding::where('school_id', $schoolId)
            ->whereNotIn('id', $buildingsWithPlansIds)
            ->count();

        // Safety score average
        $buildings = FireSafetyBuilding::where('school_id', $schoolId)->with(['fireExtinguishers', 'alarmSystems'])->get();
        $totalScore = 0;
        foreach ($buildings as $building) {
            $alarmCount = $building->alarmSystems->whereIn('status', ['functional', 'online'])->count();
            $extinguisherCount = $building->fireExtinguishers->where('status', 'active')->count();
            $emergencyExits = $building->emergency_exits ?? 0;

            $score = 0;
            if($alarmCount > 0) $score += 30;
            if($extinguisherCount >= max(1, ceil(($building->rooms ?? 0) / 3))) $score += 40;
            if($emergencyExits >= min(2, ceil(($building->floors ?? 1) * 0.5))) $score += 30;
            $totalScore += $score;
        }
        $avgSafetyScore = $buildings->count() > 0 ? round($totalScore / $buildings->count()) : 0;

        return response()->json([
            'active_plans' => $activePlans,
            'draft_plans' => $draftPlans,
            'total_buildings' => $school->buildings_count,
            'no_plan' => $buildingsWithoutPlans,
            'avg_safety_score' => $avgSafetyScore
        ]);
    }

    public function getDrillHistory($schoolId)
    {
        if (!Schema::hasTable('fire_safety_evacuation_drills')) {
            return response()->json([]);
        }

        $drills = FireSafetyEvacuationDrill::where('school_id', $schoolId)
            ->orderBy('drill_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json($drills);
    }

    public function customization()
    {
        $user = auth()->user();

        if ($user->role === 'viewer') {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Unauthorized access.');
        }

        if ($user->role === 'admin') {
            // Load all data for admin
            $schools = FireSafetySchool::withCount(['buildings', 'rooms', 'alarmSystems', 'extinguishers as fire_extinguishers_count'])->get();
            $buildingTypes = SystemConfiguration::where('config_type', 'building_type')->orderBy('sort_order')->get()->unique('name');
            $alarmTypes = SystemConfiguration::where('config_type', 'alarm_type')->orderBy('id')->get()->unique('name');
            $alarmStatusesByType = SystemConfiguration::where('config_type', 'alarm_status')->whereNotNull('parent_id')->get()->unique('name')->groupBy('parent_id');
            $extinguisherTypes = SystemConfiguration::where('config_type', 'extinguisher_type')->get()->unique('name');
            $extinguisherStatuses = SystemConfiguration::where('config_type', 'extinguisher_status')->get()->unique('name');
            $safetyFeatures = SystemConfiguration::where('config_type', 'safety_feature')->orderBy('sort_order')->get()->unique('name');
            $calculatedPriorities = SystemConfiguration::where('config_type', 'calculated_priority')->orderBy('sort_order')->get()->unique('name');
            $roomTypes = SystemConfiguration::where('config_type', 'room_type')->orderBy('sort_order')->get()->unique('name');
            $inspectionChecklists = SystemConfiguration::where('config_type', 'inspection_checklist')->orderBy('sort_order')->get();
            $inspectionObservers = SystemConfiguration::where('config_type', 'inspection_observer')->orderBy('sort_order')->get();

            return view('fire-safety.customization', compact(
                'schools', 'buildingTypes', 'alarmTypes', 'alarmStatusesByType',
                'extinguisherTypes', 'extinguisherStatuses', 'safetyFeatures',
                'calculatedPriorities', 'roomTypes', 'inspectionChecklists', 'inspectionObservers'
            ));
        } else {
            // For contributors, just load their school
            $school = FireSafetySchool::withCount(['buildings', 'rooms', 'alarmSystems', 'extinguishers as fire_extinguishers_count'])
                ->find($user->school_id);

            $schools = $school ? collect([$school]) : collect([]);
            return view('fire-safety.customization', compact('schools'));
        }
    }

    public function updateSchool(Request $request, $id = null)
    {
        $user = auth()->user();

        // Use route parameter or user's school_id
        $finalId = $id ?? $user->school_id;
        $schoolId = (int) $finalId;

        if ($schoolId <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid school.'], 422);
        }

        if ($user->role !== 'admin') {
            if ((int) $user->school_id !== $schoolId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'address' => 'required|string',
            'school_head' => 'required|string|max:255',
            'school_drrm_coordinator' => 'required|string|max:255',
            'school_head_contact' => 'nullable|string|max:20',
            'drrm_coordinator_contact' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        $school = FireSafetySchool::findOrFail($schoolId);
        $school->update($validated);

        return response()->json(['success' => true, 'message' => 'School updated successfully']);
    }

    // API endpoints for AJAX calls
    public function getSchoolDetails($id)
    {
        $school = FireSafetySchool::withCount(['extinguishers', 'alarmSystems', 'buildings', 'evacuationPlans'])
            ->findOrFail($id);

        return response()->json([
            'id' => $school->id,
            'school_name' => $school->school_name,
            'school_id' => $school->school_id,
            'address' => $school->address,
            'school_head' => $school->school_head,
            'school_drrm_coordinator' => $school->school_drrm_coordinator,
            'status' => $school->status,
            'fire_extinguishers_count' => $school->extinguishers_count,
            'alarm_systems_count' => $school->alarm_systems_count,
            'evacuation_plans_count' => $school->evacuation_plans_count,
            'buildings_count' => $school->buildings_count,
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
        $user = auth()->user();

        // Restriction: Contributor can only add one school
        if ($user->role === 'contributor') {
            $existingSchoolCount = FireSafetySchool::where('id', $user->school_id)->count();
            // If they already have a school assigned, or if they've already created one (in case school_id is not yet set)
            // Actually, if it's a contributor, they should only manage their assigned school.
            // If they are trying to create a NEW school, and they are a contributor, we should check if they already have one.
            if ($user->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already assigned to a school and cannot create another one.'
                ], 403);
            }
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_id' => 'required|string|max:50|unique:firesafety_school_information,school_id',
            'address' => 'required|string|max:500',
            'school_head' => 'required|string|max:255',
            'school_drrm_coordinator' => 'required|string|max:255',
            'status' => 'required|string',
        ]);

        $school = FireSafetySchool::create([
            'school_name' => $validated['school_name'],
            'school_id' => $validated['school_id'],
            'address' => $validated['address'],
            'school_head' => $validated['school_head'],
            'school_drrm_coordinator' => $validated['school_drrm_coordinator'],
            'status' => $validated['status'] ?? 'unconfigured',
        ]);

        // If contributor creates the school, assign them to it automatically
        if ($user->role === 'contributor' && !$user->school_id) {
            $user->school_id = $school->id;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'School added successfully!',
            'school' => $school
        ]);
    }

    /**
     * Permanently delete a school (admin and assigned school contributor only) with password confirmation and archiving.
     */
    public function destroySchool(Request $request, $id)
    {
        $user = auth()->user();

        // Check if user is admin OR contributor assigned to this school
        if ($user->role !== 'admin' && ($user->role !== 'contributor' || (int)$user->school_id !== (int)$id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'password' => 'required|string|max:255',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $school = FireSafetySchool::withCount(['buildings', 'alarmSystems', 'extinguishers as fire_extinguishers_count', 'evacuationPlans'])
                ->findOrFail($id);

            // Capture FULL snapshot of school data before deletion
            $fullSchoolData = FireSafetySchool::with([
                'buildings.actualRooms',
                'buildings.fireExtinguishers',
                'buildings.alarmSystemsMany',
                'buildings.evacuationPlan',
                'alarmSystems',
                'extinguishers',
                'evacuationPlans',
                'rooms', // Direct rooms if any
                'inspections',
                'drills.buildings'
            ])->findOrFail($id);

            $reason = $request->input('reason') ?: ('School deleted by ' . $user->name);

            $archiveData = [
                'school_name' => $school->school_name,
                'school_code' => $school->school_id,
                'school_head' => $school->school_head,
                'drrm_coordinator' => $school->school_drrm_coordinator,
                'address' => $school->address,
                'buildings' => $school->buildings_count ?? 0,
                'alarm_systems' => $school->alarm_systems_count ?? 0,
                'extinguishers' => $school->fire_extinguishers_count ?? 0,
                'evacuation_plans' => $school->evacuation_plans_count ?? 0,
                'evacuation_coverage_status' => $school->coverageStatus,
            ];

            // Save to dedicated snapshots table for full data retention
            FireSafetySchoolSnapshot::create([
                'school_id_code' => $school->school_id,
                'school_name' => $school->school_name,
                'full_data' => $fullSchoolData->toArray(),
                'deleted_by' => $user->name,
                'reason' => $reason,
                'deleted_at' => now(),
            ]);

            // Also keep a record in general archives for the history log
            FireSafetyArchive::create([
                'school_id' => null, // School will be gone
                'type' => 'school_deletion',
                'item_id' => $school->id,
                'item_code' => $school->school_id,
                'item_data' => $archiveData,
                'reason' => $reason,
                'removed_at' => now(),
            ]);

            // Database level cascade ($table->onDelete('cascade')) is now active via migration,
            // but we still do manual cleanup to trigger any model-level events or handle pivots.
            $school->buildings()->each(function($b) {
                // Clear pivot relationships for drills and alarms
                $b->drills()->detach();
                $b->alarmSystemsMany()->detach();

                // Sub-items (handled by cascade too, but safe for events)
                $b->actualRooms()->delete();
                $b->fireExtinguishers()->delete();
                $b->alarmSystems()->delete();
                $b->evacuationPlan()->delete();

                $b->delete();
            });

            // Clean up remaining school relationships
            $school->alarmSystems()->delete();
            $school->extinguishers()->delete();
            $school->evacuationPlans()->delete();
            $school->rooms()->delete();
            $school->inspections()->delete();

            // Clean up drills (already detached from buildings above)
            $school->drills()->each(function($d) {
                $d->buildings()->detach();
                $d->delete();
            });

            $school->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting school: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school.'
            ], 500);
        }
    }

    /**
     * Get archived schools history (admin only).
     */
    public function getSchoolHistory()
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $archives = FireSafetyArchive::where('type', 'school_deletion')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function storeAlert(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot add alerts.'], 403);
        }
        $validated = $request->validate([
            'school_id' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:danger,warning,info'
        ]);

        $user = auth()->user();
        if ($user->role === 'contributor') {
            if ($validated['school_id'] === 'all') {
                return response()->json(['success' => false, 'message' => 'Contributors cannot send alerts to all schools.'], 403);
            }
            if ((int)$validated['school_id'] !== (int)$user->school_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
            }
        }

        if ($validated['school_id'] === 'all') {
            $schools = FireSafetySchool::all();
            foreach ($schools as $school) {
                $alerts = $school->alerts ?? [];
                $alerts[] = [
                    'id' => uniqid(),
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'type' => $validated['type'],
                    'posted_by' => $user->name,
                    'posted_by' => $user->name,
                    'created_at' => now()->toDateTimeString()
                ];
                $school->alerts = $alerts;
                $school->save();
            }
            return response()->json(['success' => true, 'message' => 'Alert applied to all schools!']);
        }

        $school = FireSafetySchool::findOrFail($validated['school_id']);

        $alerts = $school->alerts ?? [];
        $alerts[] = [
            'id' => uniqid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'created_at' => now()->toDateTimeString()
        ];

        $school->alerts = $alerts;
        $school->save();

        return response()->json(['success' => true, 'message' => 'Alert added successfully!', 'alerts' => $alerts]);
    }

    public function storeEvent(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot schedule events.'], 403);
        }
        $validated = $request->validate([
            'school_id' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date',
            'time' => 'nullable'
        ]);

        $user = auth()->user();
        if ($user->role === 'contributor') {
            if ($validated['school_id'] === 'all') {
                return response()->json(['success' => false, 'message' => 'Contributors cannot schedule events for all schools.'], 403);
            }
            if ((int)$validated['school_id'] !== (int)$user->school_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
            }
        }

        if ($validated['school_id'] === 'all') {
            $schools = FireSafetySchool::all();
            foreach ($schools as $school) {
                $events = $school->events ?? [];
                $events[] = [
                    'id' => uniqid(),
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'date' => $validated['date'],
                    'time' => $validated['time'],
                    'posted_by' => $user->name,
                    'posted_by' => $user->name,
                    'created_at' => now()->toDateTimeString()
                ];
                $school->events = $events;
                $school->save();
            }
            return response()->json(['success' => true, 'message' => 'Event scheduled for all schools!']);
        }

        $school = FireSafetySchool::findOrFail($validated['school_id']);

        $events = $school->events ?? [];
        $events[] = [
            'id' => uniqid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'created_at' => now()->toDateTimeString()
        ];

        $school->events = $events;
        $school->save();

        return response()->json(['success' => true, 'message' => 'Event added successfully!', 'events' => $events]);
    }

    public function checkAlarmCode($schoolId, $code)
    {
        $exists = FireSafetyAlarmSystem::where('school_id', $schoolId)
            ->where('code', $code)
            ->exists();
        return response()->json(['exists' => $exists]);
    }

    // User Management Methods
    // Configuration Management Methods
    public function updateConfigOrder(Request $request, $type)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $order = $request->input('order');
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'No order provided'], 400);
        }

        foreach ($order as $index => $id) {
            SystemConfiguration::where('id', $id)
                ->where('config_type', str_replace('-', '_', $type))
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function storeConfig(Request $request, $type)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'color_class' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean'
        ];
        $configType = str_replace('-', '_', $type);
        if ($configType === 'alarm_type') {
            $rules['statuses'] = 'required|array';
            $rules['statuses.*'] = 'nullable|string|max:255';
        }
        if ($configType === 'building_type') {
            $rules['min_floors'] = 'nullable|integer|min:0';
            $rules['total_rooms'] = 'nullable|integer|min:0';
        }
        if ($configType === 'extinguisher_status') {
            $rules['pressure_min'] = 'required|numeric|min:0';
            $rules['pressure_max'] = 'required|numeric|min:0';
        }
        if ($configType === 'calculated_priority') {
            $rules['max_rooms_covered'] = 'required|integer|min:1|max:5';
        }
        if ($configType === 'room_type') {
            $rules['parent_id'] = 'required|exists:system_configurations,id';
        }
        $validated = $request->validate($rules);

        $data = [
            'config_type' => $configType,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'] ?? null,
            'category' => $validated['category'] ?? null,
            'color_class' => $validated['color_class'] ?? null,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'sort_order' => SystemConfiguration::where('config_type', $configType)->count()
        ];
        if ($configType === 'building_type') {
            $v = $request->input('min_floors');
            $data['min_floors'] = ($v !== '' && $v !== null) ? (int) $v : null;
            $v = $request->input('total_rooms');
            $data['total_rooms'] = ($v !== '' && $v !== null) ? (int) $v : null;
        }
        if ($configType === 'extinguisher_status') {
            $data['pressure_min'] = $request->input('pressure_min') !== null && $request->input('pressure_min') !== '' ? (float) $request->input('pressure_min') : null;
            $data['pressure_max'] = $request->input('pressure_max') !== null && $request->input('pressure_max') !== '' ? (float) $request->input('pressure_max') : null;
        }
        if ($configType === 'calculated_priority') {
            $data['max_rooms_covered'] = (int) $validated['max_rooms_covered'];
        }
        if ($configType === 'room_type') {
            $parent = SystemConfiguration::where('id', $validated['parent_id'])
                ->where('config_type', 'calculated_priority')
                ->firstOrFail();
            $data['parent_id'] = $parent->id;
        }
        $config = SystemConfiguration::create($data);

        if ($configType === 'alarm_type') {
            $statuses = array_values(array_filter(array_map('trim', $validated['statuses'] ?? [])));
            if (count($statuses) === 0) {
                return response()->json(['success' => false, 'message' => 'At least one alarm status is required.'], 422);
            }
            foreach ($statuses as $i => $statusName) {
                SystemConfiguration::create([
                    'config_type' => 'alarm_status',
                    'parent_id' => $config->id,
                    'name' => $statusName,
                    'sort_order' => $i,
                    'is_active' => true,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Configuration saved successfully', 'config' => $config]);
    }

    public function updateConfig(Request $request, $type, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable'
        ];
        $configType = str_replace('-', '_', $type);
        if ($configType === 'building_type') {
            $rules['min_floors'] = 'nullable|integer|min:0';
            $rules['total_rooms'] = 'nullable|integer|min:0';
        }
        if ($configType === 'extinguisher_status') {
            $rules['pressure_min'] = 'required|numeric|min:0';
            $rules['pressure_max'] = 'required|numeric|min:0';
        }
        if ($configType === 'safety_feature') {
            $rules['category'] = 'nullable|string|max:100';
        }
        if ($configType === 'calculated_priority') {
            $rules['max_rooms_covered'] = 'required|integer|min:1|max:5';
        }
        if ($configType === 'room_type') {
            $rules['parent_id'] = 'required|exists:system_configurations,id';
        }
        $validated = $request->validate($rules);

        $config = SystemConfiguration::where('id', $id)
            ->where('config_type', $configType)
            ->firstOrFail();

        $update = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];
        if (in_array($configType, ['building_type', 'alarm_type'], true)) {
            $update['is_active'] = $request->has('is_active') ? ($request->is_active == 'on' || $request->is_active == '1') : false;
        }
        if ($configType === 'building_type') {
            $v = $request->input('min_floors');
            $update['min_floors'] = ($v !== '' && $v !== null) ? (int) $v : null;
            $v = $request->input('total_rooms');
            $update['total_rooms'] = ($v !== '' && $v !== null) ? (int) $v : null;
        }
        if ($configType === 'extinguisher_status') {
            $update['pressure_min'] = $request->input('pressure_min') !== null && $request->input('pressure_min') !== '' ? (float) $request->input('pressure_min') : null;
            $update['pressure_max'] = $request->input('pressure_max') !== null && $request->input('pressure_max') !== '' ? (float) $request->input('pressure_max') : null;
        }
        if ($configType === 'safety_feature') {
            $update['category'] = $request->input('category') ?: null;
        }
        if ($configType === 'calculated_priority') {
            $update['max_rooms_covered'] = (int) $validated['max_rooms_covered'];
        }
        if ($configType === 'room_type') {
            $parent = SystemConfiguration::where('id', $validated['parent_id'])
                ->where('config_type', 'calculated_priority')
                ->firstOrFail();
            $update['parent_id'] = $parent->id;
        }
        $config->update($update);

        if ($configType === 'alarm_type' && $request->has('statuses')) {
            foreach ($request->statuses as $s) {
                if (!empty($s['name'])) {
                    if (isset($s['id']) && !empty($s['id'])) {
                        \App\Models\SystemConfiguration::where('id', $s['id'])
                            ->where('config_type', 'alarm_status')
                            ->where('parent_id', $config->id)
                            ->update([
                                'name' => $s['name'],
                                'color_class' => $s['color_class'] ?? 'bg-secondary'
                            ]);
                    } else {
                        \App\Models\SystemConfiguration::create([
                            'config_type' => 'alarm_status',
                            'name' => $s['name'],
                            'parent_id' => $config->id,
                            'color_class' => $s['color_class'] ?? 'bg-secondary',
                            'is_active' => true
                        ]);
                    }
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Configuration updated successfully']);
    }

    public function deleteConfig($type, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        SystemConfiguration::where('id', $id)
            ->where('config_type', str_replace('-', '_', $type))
            ->delete();

        return response()->json(['success' => true, 'message' => 'Configuration deleted successfully']);
    }

    private function fireSafetyBackupTables(): array
    {
        return [
            // Core data
            'firesafety_school_information',
            'firesafety_buildings',
            'fire_safety_rooms',
            'firesafety_alarm_systems',
            'firesafety_fire_extinguishers',
            'firesafety_evacuationplans',
            // Related
            'fire_safety_inspections',
            'fire_safety_alarm_building',
            'fire_safety_extinguisher_room_coverage',
            // Config + archives
            'system_configurations',
            'fire_safety_archives',
        ];
    }

    public function listFireSafetyBackups(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $dir = 'fire-safety-backups';
        if (!Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }

        $files = collect(Storage::disk('local')->files($dir))
            ->filter(fn($p) => str_ends_with($p, '.json'))
            ->map(function ($p) {
                return [
                    'name' => basename($p),
                    'path' => $p,
                    'size' => Storage::disk('local')->size($p),
                    'last_modified' => Storage::disk('local')->lastModified($p),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return response()->json(['success' => true, 'files' => $files]);
    }

    public function createFireSafetyBackup(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $tables = $this->fireSafetyBackupTables();
        $payload = [
            'module' => 'fire_safety',
            'generated_at' => now()->toIso8601String(),
            'generated_by' => $request->user()->email ?? null,
            'tables' => [],
        ];

        foreach ($tables as $t) {
            try {
                $rows = DB::table($t)->get()->map(fn($r) => (array) $r)->all();
                $payload['tables'][$t] = $rows;
            } catch (\Throwable $e) {
                // If a table doesn't exist yet (older schema), skip it.
                $payload['tables'][$t] = [];
            }
        }

        $dir = 'fire-safety-backups';
        if (!Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }
        $fileName = 'fire-safety-backup-' . now()->format('Ymd_His') . '.json';
        $path = $dir . '/' . $fileName;
        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT));

        return response()->json(['success' => true, 'file' => $fileName]);
    }

    public function restoreFireSafetyBackup(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'file' => 'required|string',
        ]);

        $fileName = basename($validated['file']);
        $path = 'fire-safety-backups/' . $fileName;
        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Backup file not found.'], 404);
        }

        $contents = Storage::disk('local')->get($path);
        $data = json_decode($contents, true);
        if (!is_array($data) || !isset($data['tables']) || !is_array($data['tables'])) {
            return response()->json(['success' => false, 'message' => 'Invalid backup file format.'], 422);
        }

        $tables = $this->fireSafetyBackupTables();
        DB::transaction(function () use ($data, $tables) {
            // MySQL: safe truncate/restore
            try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

            foreach ($tables as $t) {
                try {
                    // Use DELETE instead of TRUNCATE to avoid implicit commits that break active transactions
                    DB::table($t)->delete();
                } catch (\Throwable $e) {
                    // ignore missing tables
                }
            }

            foreach ($tables as $t) {
                $rows = $data['tables'][$t] ?? [];
                if (!is_array($rows) || count($rows) === 0) continue;
                // Insert in chunks
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($t)->insert($chunk);
                }
            }

            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
        });

        return response()->json(['success' => true]);
    }

    public function exportSchools()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $schools = FireSafetySchool::all();
        $filename = "schools_export_" . date('Y-m-d') . ".csv";
        $handle = fopen('php://output', 'w');

        // Add CSV header
        fputcsv($handle, ['ID', 'School ID', 'Name', 'Address', 'Head', 'DRRM Coordinator', 'Status', 'Last Updated']);

        foreach ($schools as $school) {
            fputcsv($handle, [
                $school->id,
                $school->school_id,
                $school->school_name,
                $school->address,
                $school->school_head,
                $school->school_drrm_coordinator,
                $school->status,
                $school->updated_at
            ]);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fclose($handle);
        exit;
    }
    public function getBuildingHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('school_id', $schoolId)
            ->whereIn('type', ['floor', 'room'])
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function getRemovedBuildingsHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('school_id', $schoolId)
            ->where('type', 'building')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    // MAP: Get Full Data for School Map
    public function getSchoolMapData($schoolId)
    {
        $school = FireSafetySchool::with([
            'buildings.actualRooms.roomTypeConfig',
            'buildings.alarmSystems',
            'buildings.alarmSystemsMany',
            'buildings.fireExtinguishers',
            'buildings.evacuationPlan'
        ])->findOrFail($schoolId);

        return response()->json($school);
    }

    // MAP: Save Layout Coordinates
    public function saveMapLayout(Request $request, $schoolId)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot save map layouts.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int)$schoolId !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $request->validate([
            'layout' => 'required|array'
        ]);

        $school = FireSafetySchool::findOrFail($schoolId);
        $school->evacuation_map_layout = $request->layout;
        $school->save();

        return response()->json(['success' => true, 'message' => 'Map layout saved successfully!']);
    }

    // --- Report Printing Methods ---

    public function printSchoolSummary()
    {
        $schools = FireSafetySchool::with([
            'buildings.actualRooms',
            'buildings.fireExtinguishers',
            'buildings.alarmSystems'
        ])->get();

        return view('fire-safety.reports.school-summary', compact('schools'));
    }

    public function printBuildingSummary($schoolId)
    {
        $school = FireSafetySchool::with([
            'buildings.actualRooms.roomTypeConfig',
            'buildings.fireExtinguishers',
            'buildings.alarmSystems',
            'buildings.alarmSystemsMany'
        ])->findOrFail($schoolId);

        return view('fire-safety.reports.building-summary', compact('school'));
    }

    public function printAlarmDetails($schoolId)
    {
        $school = FireSafetySchool::findOrFail($schoolId);
        $alarms = FireSafetyAlarmSystem::where('school_id', $schoolId)
            ->with('buildings')
            ->get();

        return view('fire-safety.reports.alarm-details', compact('school', 'alarms'));
    }

    public function printExtinguisherDetails($schoolId)
    {
        $school = FireSafetySchool::findOrFail($schoolId);
        $extinguishers = FireSafetyExtinguisher::where('school_id', $schoolId)
            ->with(['building', 'centerRoom', 'coveredRooms'])
            ->get();

        return view('fire-safety.reports.extinguisher-details', compact('school', 'extinguishers'));
    }

    public function printEvacuationPlans(Request $request, $schoolId)
    {
        $school = FireSafetySchool::findOrFail($schoolId);
        $query = FireSafetyEvacuationPlan::where('school_id', $schoolId)->with(['building']);

        if ($request->has('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->has('plan_id')) {
            $query->where('id', $request->plan_id);
        }

        $plans = $query->get();

        return view('fire-safety.reports.evacuation-plans', compact('school', 'plans'));
    }
    public function getNotifications()
    {
        $user = auth()->user();
        $query = FireSafetySchool::query();

        if ($user->role !== 'admin') {
            $query->where('id', $user->school_id);
        }

        $schools = $query->get();
        $allAlerts = [];
        $allEvents = [];

        foreach ($schools as $school) {
            if ($school->alerts) {
                foreach ($school->alerts as $alert) {
                    $alert['school_name'] = $school->school_name;
                    $alert['school_id'] = $school->id;
                    $alert['item_type'] = 'alert';
                    // Attach replies
                    $alert['replies'] = collect($school->replies ?? [])
                        ->where('item_id', $alert['id'])
                        ->values()
                        ->all();
                    $allAlerts[] = $alert;
                }
            }
            if ($school->events) {
                foreach ($school->events as $event) {
                    $event['school_name'] = $school->school_name;
                    $event['school_id'] = $school->id;
                    $event['item_type'] = 'event';
                    // Attach replies
                    $event['replies'] = collect($school->replies ?? [])
                        ->where('item_id', $event['id'])
                        ->values()
                        ->all();
                    $allEvents[] = $event;
                }
            }
        }

        // Sort by date/created_at
        usort($allAlerts, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        usort($allEvents, fn($a, $b) => strtotime($a['date'] . ' ' . ($a['time'] ?? '00:00')) - strtotime($b['date'] . ' ' . ($b['time'] ?? '00:00')));

        return response()->json([
            'alerts' => array_slice($allAlerts, 0, 10),
            'events' => array_slice($allEvents, 0, 10),
            'unread_count' => count(array_filter($allAlerts, fn($a) => strtotime($a['created_at']) > (strtotime('yesterday'))))
        ]);
    }

    public function replyToNotification(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|string',
            'school_id' => 'required|exists:firesafety_school_information,id',
            'message' => 'required|string|max:1000'
        ]);

        $school = FireSafetySchool::findOrFail($validated['school_id']);

        $user = auth()->user();
        if ($user->role !== 'admin' && (int)$user->school_id !== (int)$school->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $replies = $school->replies ?? [];
        $replies[] = [
            'id' => uniqid(),
            'item_id' => $validated['item_id'],
            'user_name' => $user->name,
            'user_role' => $user->role,
            'message' => $validated['message'],
            'created_at' => now()->toDateTimeString()
        ];

        $school->replies = $replies;
        $school->save();

        return response()->json(['success' => true, 'message' => 'Reply sent successfully!']);
    }
}
