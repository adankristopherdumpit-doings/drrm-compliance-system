<?php

namespace App\Http\Controllers;

use App\Models\FireSafetySchoolSnapshot;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\SchoolSpecificsInformation;
use App\Models\FireSafetyExtinguisher;
use App\Models\FireSafetyAlarmSystem;
use App\Models\FireSafetyBuilding;
use App\Models\FireSafetyEvacuationPlan;
use App\Models\FireSafetyInspection;
use App\Models\FireSafetyExtinguisherInspection;
use App\Models\FireSafetyRoom;
use App\Models\FireSafetyEvacuationDrill;
use App\Models\FireSafetyArchive;
use App\Models\FireSafetyNotification;
use App\Models\ComprehensiveFacility;
use App\Models\ActivityLog;
use Carbon\Carbon;
use App\Models\User;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class FireSafetyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $query = School::with([
            'fireSafetyBuildings.actualRooms',
            'fireSafetyBuildings.alarmSystems',
            'fireSafetyBuildings.alarmSystemsMany',
            'fireSafetyBuildings.fireExtinguishers.coveredRooms',
            'fireSafetyBuildings.evacuationPlan'
        ])->withCount(['fireSafetyExtinguishers as extinguishers_count', 'fireSafetyAlarms as alarm_systems_count', 'fireSafetyBuildings as buildings_count', 'fireSafetyEvacuationPlans as evacuation_plans_count'])
            ->whereHas('specifics', function ($q) {
                $q->where('module', 'fire_safety')->where('key', 'original_fire_safety_id');
            });

        if (auth()->user()->role !== 'admin') {
            $query->where('id', auth()->user()->school_id);
        }

        $schools = $query->get()
            ->map(function($school) {
                return $this->calculateDetailedSchoolStatus($school);
            });

        // Pull only manually posted alerts and events from notifications table
        $alertQuery = FireSafetyNotification::where('type', 'alert')
            ->orderBy('created_at', 'desc')
            ->limit(10);
        $eventQuery = FireSafetyNotification::where('type', 'event')
            ->orderBy('created_at', 'desc')
            ->limit(10);

        if (auth()->user()->role !== 'admin') {
            $userSchoolId = auth()->user()->school_id;
            $alertQuery->where(function($q) use ($userSchoolId) {
                $q->where('school_id', $userSchoolId)->orWhereNull('school_id');
            });
            $eventQuery->where(function($q) use ($userSchoolId) {
                $q->where('school_id', $userSchoolId)->orWhereNull('school_id');
            });
        }

        $allAlerts = $alertQuery->get()->map(function($n) use ($schools) {
            $actionData = $n->action_data ?? [];
            $notificationSchoolId = $n->school_id ?? ($actionData['unified_school_id'] ?? $actionData['school_id'] ?? null);
            $school = $schools->firstWhere('id', $notificationSchoolId);
            return [
                'id' => $n->id,
                'title' => preg_replace('/^Alert:\s*/', '', $n->title),
                'description' => preg_replace('/\s*\(Posted by:.*?\)\s*$/', '', $n->message),
                'type' => $actionData['alert_type'] ?? 'info',
                'school_name' => $school->school_name ?? 'Unknown',
                'posted_by' => $actionData['posted_by'] ?? '',
                'created_at' => $n->created_at->toDateTimeString(),
            ];
        })->toArray();

        $allEvents = $eventQuery->get()->map(function($n) use ($schools) {
            $actionData = $n->action_data ?? [];
            $notificationSchoolId = $n->school_id ?? ($actionData['unified_school_id'] ?? $actionData['school_id'] ?? null);
            $school = $schools->firstWhere('id', $notificationSchoolId);
            return [
                'id' => $n->id,
                'title' => preg_replace('/^Event:\s*/', '', $n->title),
                'description' => preg_replace('/\s*(\|.*|\(Posted by:.*?\))\s*$/', '', $n->message),
                'date' => $actionData['event_date'] ?? $n->created_at->format('Y-m-d'),
                'time' => $actionData['event_time'] ?? '',
                'school_name' => $school->school_name ?? 'Unknown',
                'posted_by' => $actionData['posted_by'] ?? '',
                'created_at' => $n->created_at->toDateTimeString(),
            ];
        })->toArray();

        $schoolsAvailableForFireRegistration = collect();
        if (auth()->user()->role === 'admin') {
            $schoolsAvailableForFireRegistration = School::query()
                ->whereDoesntHave('specifics', function ($q) {
                    $q->where('module', 'fire_safety')->where('key', 'original_fire_safety_id');
                })
                ->orderBy('school_name')
                ->get(['id', 'school_name', 'school_id', 'school_id_number', 'address', 'school_head', 'drrm_coordinator', 'contact_number', 'contact_number_2', 'district', 'division', 'region']);
        }

        return view('fire-safety.dashboard', [
            'schools' => $schools,
            'allAlerts' => $allAlerts,
            'allEvents' => $allEvents,
            'schoolsAvailableForFireRegistration' => $schoolsAvailableForFireRegistration,
        ]);
    }

    private function calculateDetailedSchoolStatus($school)
    {
        // Initialize issues
        $issues = [];
        $isUnconfigured = false;

        $buildingIssuesModules = [];
        $alarmIssuesModules = [];
        $roomIssuesModules = [];
        $extinguisherIssuesModules = [];
        $planIssuesModules = [];

        $buildingAlarmIssues = [];
        $extRoomIssues = [];
        $planIssuesList = [];

        $schoolConfig = [
            'has_buildings' => $school->fireSafetyBuildings->count() > 0,
            'has_alarms' => false,
            'has_rooms' => false,
            'has_extinguishers' => false,
            'has_plans' => false,
            'needs_building' => $school->fireSafetyBuildings->count() === 0,
            'needs_alarm' => false,
            'needs_room' => false,
            'needs_extinguisher' => false,
            'needs_plan' => false
        ];

        if ($school->fireSafetyBuildings->count() === 0) {
            $isUnconfigured = true;
            $issues[] = 'Setup Needed';
        } else {
            if ($schoolConfig['has_buildings']) {
                $buildingsWithAlarms = 0;
                $buildingsWithAllRooms = 0;
                $buildingsWithPlan = 0;
                $buildingsWithExtinguishers = 0;
                $totalBldgs = $school->fireSafetyBuildings->count();

                foreach ($school->fireSafetyBuildings as $b) {
                    $bldAlarms = $b->alarmSystems->count();
                    $schoolHasAlarm = $school->fireSafetyAlarms()->where('status', 'active')->exists();
                    $bldRoomsReady = $b->rooms > 0 && $b->actualRooms->count() >= $b->rooms;
                    $bldPlan = $b->evacuationPlan !== null;
                    if (!$bldPlan) {
                        $bldPlan = FireSafetyEvacuationPlan::where('unified_school_id', $school->id)
                            ->whereNull('building_id')
                            ->where('status', 'active')
                            ->exists();
                    }
                    $reqExt = $b->required_extinguishers_count;
                    $bldExtReady = $b->fireExtinguishers->count() >= $reqExt;

                    if ($bldAlarms > 0) $buildingsWithAlarms++;
                    if ($bldRoomsReady) $buildingsWithAllRooms++;
                    if ($bldPlan) $buildingsWithPlan++;
                    if ($bldExtReady) $buildingsWithExtinguishers++;

                    if (!$bldRoomsReady) $roomIssuesModules[] = $b->building_no;
                    if ($bldAlarms === 0 && !$schoolHasAlarm) {
                        $alarmIssuesModules[] = $b->building_no;
                        $buildingAlarmIssues[] = ['text' => "Building {$b->building_no} — No alarm installed", 'severity' => 'yellow', 'building' => $b->building_no];
                    } else {
                        $badAlarmStatuses = ['offline', 'broken', 'missing', 'jammed', 'under-repair', 'system-error'];
                        if ($b->alarmSystems->whereIn('status', $badAlarmStatuses)->count() > 0) {
                            $alarmIssuesModules[] = $b->building_no;
                            $buildingAlarmIssues[] = ['text' => "Building {$b->building_no} — Alarm status: " . $b->alarmSystems->whereIn('status', $badAlarmStatuses)->pluck('status')->unique()->implode(', '), 'severity' => 'red', 'building' => $b->building_no];
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
                        $buildingAlarmIssues[] = ['text' => "Building {$b->building_no} — Non-Compliant", 'severity' => 'red', 'building' => $b->building_no];
                    }

                    // Extinguishers & Rooms detailed checks
                    $redExtStatuses = ['missing', 'expired'];
                    $redExts = $b->fireExtinguishers->whereIn('status', $redExtStatuses);
                    if ($redExts->count() > 0) {
                        $extRoomIssues[] = ['text' => "Building {$b->building_no} — Ext: " . $redExts->pluck('status')->unique()->map(fn($s) => ucfirst(str_replace('-', ' ', $s)))->implode(', '), 'severity' => 'red', 'building' => $b->building_no];
                    }

                    // Room issues - uncovered rooms
                    $allRoomIds = $b->actualRooms->pluck('id')->toArray();
                    $coveredRoomIds = [];
                    foreach ($b->fireExtinguishers as $ext) {
                        foreach ($ext->coveredRooms as $cr) $coveredRoomIds[] = $cr->id;
                    }
                    $uncoveredRoomsCount = count(array_diff($allRoomIds, array_unique($coveredRoomIds)));
                    if ($uncoveredRoomsCount > 0) {
                        $extRoomIssues[] = ['text' => "Building {$b->building_no} — {$uncoveredRoomsCount} uncovered room(s)", 'severity' => 'red', 'building' => $b->building_no];
                    }
                }

                $schoolConfig['has_alarms'] = $buildingsWithAlarms > 0;
                $schoolConfig['has_rooms'] = $buildingsWithAllRooms > 0;
                $schoolConfig['has_extinguishers'] = $buildingsWithExtinguishers > 0;

                $schoolHasPlan = FireSafetyEvacuationPlan::where('unified_school_id', $school->id)
                    ->whereNull('building_id')
                    ->where('status', 'active')
                    ->exists();
                $schoolConfig['has_plans'] = $buildingsWithPlan > 0 || $schoolHasPlan;
                if ($schoolHasPlan) {
                    $buildingsWithPlan = $totalBldgs;
                }

                $schoolConfig['needs_alarm'] = (!$schoolHasAlarm) && ($buildingsWithAlarms < $totalBldgs);
                $schoolConfig['needs_room'] = $buildingsWithAllRooms < $totalBldgs;
                $schoolConfig['needs_plan'] = !$schoolConfig['has_plans'];
                $schoolConfig['needs_extinguisher'] = $buildingsWithExtinguishers < $totalBldgs;

                // Evacuation plan messages
                if ($schoolHasPlan) {
                    $planIssuesList[] = ['text' => 'Has entire school plan even individual ones', 'severity' => 'green'];
                } else if ($totalBldgs > 0 && $buildingsWithPlan < $totalBldgs) {
                    $planIssuesList[] = ['text' => 'Some buildings missing evacuation plans', 'severity' => 'red'];
                }
            } else {
                $schoolConfig['needs_alarm'] = true;
                $schoolConfig['needs_room'] = true;
                $schoolConfig['needs_plan'] = true;
                $schoolConfig['needs_extinguisher'] = true;
                $isUnconfigured = true;
            }
        }

        if ($school->fireSafetyBuildings->count() === 0 || (!$schoolConfig['has_alarms'] && !$schoolConfig['has_plans'] && !$schoolConfig['has_rooms'])) {
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
        $school->module_issues = [
            'buildings_alarms' => ['issues' => $buildingAlarmIssues],
            'ext_rooms' => ['issues' => $extRoomIssues],
            'plans' => ['issues' => $planIssuesList],
            'buildings' => $buildingIssuesModules,
            'alarms' => $alarmIssuesModules,
            'rooms' => $roomIssuesModules,
            'extinguishers' => $extinguisherIssuesModules,
        ];

        // Last Inspection Date
        $updates = [$school->updated_at];
        foreach ($school->buildings as $b) {
            $updates[] = $b->updated_at;
            foreach ($b->fireExtinguishers as $e) $updates[] = $e->updated_at;
            foreach ($b->alarmSystems as $a) $updates[] = $a->updated_at;
            if ($b->evacuationPlan) $updates[] = $b->evacuationPlan->updated_at;
        }
        $lastConfig = collect($updates)->filter()->max();
        $school->last_inspection_date = $lastConfig ? $lastConfig : null;

        return $school;
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
        $school = School::findOrFail($id);

        // If user is not admin and trying to set a school that isn't theirs
        if (auth()->user()->role !== 'admin' && auth()->user()->school_id != $id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        session(['fire_safety_active_unified_school_id' => $id]);

        return response()->json(['success' => true]);
    }

    private function getActiveSchool($schools) {
        if ($schools->isEmpty()) return null;

        $activeId = session('fire_safety_active_unified_school_id');

        // If specific school is inactive or deleted, fallback
        $activeSchool = $schools->where('id', $activeId)->first();

        return $activeSchool ?? $schools->first();
    }

    private function getSchoolSwitcherOptions()
    {
        if (auth()->user()->role === 'admin') {
            return School::query()
                ->orderBy('school_name')
                ->get(['id', 'school_name', 'school_id']);
        }

        $userSchool = School::query()
            ->where('id', auth()->user()->school_id)
            ->first(['id', 'school_name', 'school_id']);

        return $userSchool ? collect([$userSchool]) : collect();
    }

    private function resolveScopedSchool(?School $school = null): ?School
    {
        if ($school) {
            if (auth()->user()->role !== 'admin' && (int) auth()->user()->school_id !== (int) $school->id) {
                return null;
            }
            return $school;
        }

        if (auth()->user()->role !== 'admin') {
            return School::find(auth()->user()->school_id);
        }

        $activeId = session('fire_safety_active_unified_school_id');
        if ($activeId) {
            $active = School::find($activeId);
            if ($active) return $active;
        }

        return School::orderBy('school_name')->first();
    }

    public function alarmSystems()
    {
        $query = School::with(['alarmSystems.buildings', 'buildings']);

        if (auth()->user()->role !== 'admin') {
            $query->where('id', auth()->user()->school_id);
        }

        $schools = $query->get();
        $activeSchool = $this->getActiveSchool($schools);

        $alarmTypes = SystemConfiguration::where('config_type', 'alarm_type')->where('is_active', true)->orderBy('sort_order')->get();
        $alarmStatusesByType = SystemConfiguration::where('config_type', 'alarm_status')->where('is_active', true)->get()->groupBy('parent_id');

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
        $buildings = FireSafetyBuilding::where('unified_school_id', $schoolId)->with(['alarmSystems', 'alarmSystemsMany', 'anchoredAlarmSystems'])->get();
        return response()->json($buildings);
    }

    // Get alarm details (AJAX)
    public function getAlarm($id)
    {
        try {
            $alarm = FireSafetyAlarmSystem::with(['building', 'school', 'buildings'])->findOrFail($id);
            $payload = $alarm->toArray();
            $payload['school_id'] = $alarm->unified_school_id;

            return response()->json($payload);

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
                'unified_school_id' => 'required|exists:schools,id',
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
            $originalStatus = strtolower(str_replace(' ', '_', $validated['status']));
            $statusMap = [
                'functional' => 'active',
            ];
            $validated['status'] = $statusMap[$originalStatus] ?? $originalStatus;

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
            $exists = FireSafetyAlarmSystem::where('unified_school_id', $validated['unified_school_id'])
                ->where('code', $validated['code'])
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alarm code already exists in this school. Please use a different code.'
                ], 422);
            }

            $alarm = FireSafetyAlarmSystem::create($validated);
            if ($alarm->building_id && $alarm->anchor_building_id) {
                $alarm->anchor_building_id = null;
                $alarm->saveQuietly();
            }

            if ($request->has('building_ids') && is_array($request->building_ids)) {
                $alarm->buildings()->sync($request->building_ids);
            } elseif ($request->building_id) {
                $alarm->buildings()->sync([$request->building_id]);
            }

            ActivityLog::log('fire_safety', 'Created alarm: ' . $alarm->code, [
                'unified_school_id' => $alarm->unified_school_id,
                'notes' => $validated['notes'] ?? null,
            ]);

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

        if (auth()->user()->role !== 'admin' && (int)$alarm->unified_school_id !== (int)auth()->user()->school_id) {
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
            'building_id' => 'nullable|exists:firesafety_buildings,id',
            'floor_id' => 'nullable|string',
            'building_ids' => 'nullable|array',
            'building_ids.*' => 'exists:firesafety_buildings,id',
            'context_building_id' => 'nullable|integer|exists:firesafety_buildings,id',
        ]);

        // Check if code changes to an existing one
        if ($validated['code'] !== $alarm->code) {
            $exists = FireSafetyAlarmSystem::where('unified_school_id', $alarm->unified_school_id)
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

        if (array_key_exists('building_id', $validated) && ($validated['building_id'] === '' || $validated['building_id'] === null)) {
            $validated['building_id'] = null;
        }

        // Track what changed for notification (excluding next_test_due which has its own notif)
        $alarm->fill(collect($validated)->except(['context_building_id'])->all());
        $changes = [];
        if ($alarm->isDirty('code')) $changes[] = 'Code: ' . $validated['code'];
        if ($alarm->isDirty('status')) $changes[] = 'Status: ' . ucfirst($validated['status']);
        if ($alarm->isDirty('location')) $changes[] = 'Location: ' . ($validated['location'] ?? 'Not Specified');
        if ($alarm->isDirty('manufacturer')) $changes[] = 'Manufacturer: ' . ($validated['manufacturer'] ?? 'N/A');
        if ($alarm->isDirty('notes')) $changes[] = 'Notes updated';
        if ($alarm->isDirty('last_test')) $changes[] = 'Last Test: ' . ($validated['last_test'] ?? 'N/A');
        if ($alarm->isDirty('installation_date')) $changes[] = 'Installation Date: ' . ($validated['installation_date'] ?? 'N/A');
        if ($alarm->isDirty('building_id')) $changes[] = 'Primary Building changed';
        if ($alarm->isDirty('floor_id')) $changes[] = 'Floor Level changed';

        $alarm->save();

        // Pivot: additional covered buildings only (primary is firesafety_alarm_systems.building_id).
        $coversMultiple = $request->boolean('covers_multiple');
        $incomingBuildingIds = $request->input('building_ids', $request->input('building_ids[]', []));
        $incoming = array_values(array_unique(array_filter(array_map('intval', (array) $incomingBuildingIds))));

        // Never store the primary building in the additional-coverage pivot.
        $primaryId = (int) ($alarm->building_id ?? 0);
        $incomingAdditional = array_values(array_diff($incoming, [$primaryId]));

        if (! $coversMultiple) {
            $alarm->buildings()->sync([]);
        } else {
            $alarm->buildings()->sync($incomingAdditional);
        }

        $alarm->refresh();
        if ($alarm->building_id && $alarm->anchor_building_id) {
            $alarm->anchor_building_id = null;
            $alarm->saveQuietly();
        }
        if (! $alarm->building_id && ! $alarm->buildings()->exists() && ! $alarm->anchor_building_id) {
            $fallbackAnchor = (int) ($request->input('context_building_id') ?: 0);
            if ($fallbackAnchor > 0) {
                $alarm->anchor_building_id = $fallbackAnchor;
                $alarm->saveQuietly();
            }
        }

        // Create notification for alarm update (only if something meaningful changed, excluding next_test_due)
        if (!empty($changes)) {
            $user = auth()->user();
            $school = School::find($alarm->unified_school_id);
            self::createFireSafetyNotification(
                'alarm_update',
                'Alarm Updated: ' . $alarm->code,
                $user->name . ' updated alarm ' . $alarm->code . '. Changes: ' . implode(', ', $changes),
                $alarm->unified_school_id,
                'go_test',
                ['alarm_id' => $alarm->id, 'unified_school_id' => $alarm->unified_school_id, 'updated_by' => $user->name]
            );
        }

        ActivityLog::log('fire_safety', 'Updated alarm: ' . $alarm->code, [
            'unified_school_id' => $alarm->unified_school_id,
            'notes' => $validated['notes'] ?? null,
        ]);

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

        // Create notification for alarm test
        self::createFireSafetyNotification(
            'alarm_due',
            'Alarm Tested: ' . $alarm->code,
            'Alarm ' . $alarm->code . ' has been tested successfully.',
            $alarm->unified_school_id,
            'go_test',
            ['alarm_id' => $alarm->id, 'unified_school_id' => $alarm->unified_school_id]
        );

        ActivityLog::log('fire_safety', 'Tested alarm: ' . $alarm->code, [
            'unified_school_id' => $alarm->unified_school_id,
        ]);

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
            'reason' => 'required|string|max:500',
            'from_building_id' => 'required|integer|exists:firesafety_buildings,id',
        ]);

        try {
            DB::beginTransaction();

            $alarm = FireSafetyAlarmSystem::with(['building'])->findOrFail($id);

            if (auth()->user()->role !== 'admin' && (int)$alarm->unified_school_id !== (int)auth()->user()->school_id) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Unauthorized access to this alarm.'], 403);
            }

            if ($alarm->building_id) {
                if ((int) $alarm->building_id !== (int) $request->from_building_id) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'You can only remove an alarm from the building where it is installed. On covered buildings, use Unassign.',
                    ], 422);
                }
            } elseif ((int) $alarm->anchor_building_id !== (int) $request->from_building_id) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Remove this alarm from the building where it is shown as unassigned.',
                ], 422);
            }

            // Create archive entry
            FireSafetyArchive::create([
                'unified_school_id' => $alarm->unified_school_id,
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

            $schoolId = $alarm->unified_school_id;
            $alarmCode = $alarm->code;
            $alarm->delete();

            ActivityLog::log('fire_safety', 'Removed alarm: ' . $alarmCode, [
                'unified_school_id' => $schoolId,
                'notes' => $request->reason,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Alarm system removed and archived.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing alarm: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove alarm system.'], 500);
        }
    }

    public function unassignAlarmFromBuilding(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot unassign alarms.'], 403);
        }

        $request->validate([
            'building_id' => 'required|integer|exists:firesafety_buildings,id',
        ]);

        $alarm = FireSafetyAlarmSystem::findOrFail($id);
        $bid = (int) $request->building_id;

        if (auth()->user()->role !== 'admin' && (int) $alarm->unified_school_id !== (int) auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this alarm.'], 403);
        }

        $building = FireSafetyBuilding::findOrFail($bid);
        if ((int) $building->unified_school_id !== (int) $alarm->unified_school_id) {
            return response()->json(['success' => false, 'message' => 'Building does not belong to this school.'], 422);
        }

        if ((int) $alarm->building_id === $bid) {
            $alarm->building_id = null;
            $alarm->anchor_building_id = $bid;
            DB::table('fire_safety_alarm_building')->where('alarm_id', $alarm->id)->delete();
            $alarm->save();
        } else {
            $alarm->buildings()->detach($bid);
            $alarm->refresh();
            $hasPivot = $alarm->buildings()->exists();
            if (! $alarm->building_id && ! $hasPivot && ! $alarm->anchor_building_id) {
                $alarm->anchor_building_id = $bid;
                $alarm->save();
            }
        }

        ActivityLog::log('fire_safety', 'Unassigned alarm from building: ' . $alarm->code, [
            'unified_school_id' => $alarm->unified_school_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alarm unassigned from this building. It stays listed here until you reassign it.',
        ]);
    }

    public function getAlarmHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('unified_school_id', $schoolId)
            ->where('type', 'alarm')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function extinguishers(Request $request, ?School $school = null)
    {
        $resolvedSchool = $this->resolveScopedSchool($school);
        if (!$resolvedSchool) {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Unauthorized or no school assigned.');
        }

        if (!$school) {
            return redirect()->route('fire-safety.schools.extinguishers', ['school' => $resolvedSchool->id] + $request->query());
        }

        session(['fire_safety_active_unified_school_id' => (int) $resolvedSchool->id]);

        $selectedSchool = School::with([
            'buildings',
            'buildings.actualRooms',
            'buildings.fireExtinguishers.centerRoom',
            'buildings.fireExtinguishers.coveredRooms',
        ])->find($resolvedSchool->id);

        if (!$selectedSchool) {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Selected school was not found.');
        }

        $schools = collect([$selectedSchool]);

        // Pre-load recent updates for each school to avoid "Loading..." state on page load
        foreach ($schools as $school) {
            // Sort extinguishers naturally by code for each building
            foreach ($school->buildings as $building) {
                $sortedExts = $building->fireExtinguishers->sortBy('code', SORT_NATURAL)->values();
                $building->setRelation('fireExtinguishers', $sortedExts);
            }

            $school->recent_inspections_data = $this->buildRecentExtinguisherActivity($school->id, 10);

            $school->recent_room_updates_data = FireSafetyRoom::where('unified_school_id', $school->id)
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
            'schoolNavOptions' => $this->getSchoolSwitcherOptions(),
            'schoolSwitchUrlPattern' => '/fire-safety/schools/__SCHOOL_ID__/extinguishers',
        ]);
    }

    private function buildRecentExtinguisherActivity(int $schoolId, int $limit = 10)
    {
        $inspectionRows = FireSafetyExtinguisherInspection::whereHas('extinguisher', function ($q) use ($schoolId) {
                $q->where('unified_school_id', $schoolId);
            })
            ->with(['extinguisher', 'extinguisher.building', 'extinguisher.centerRoom', 'inspector'])
            ->latest('inspection_date')
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function ($insp) {
                return [
                    'ts' => (string) $insp->created_at,
                    'date' => Carbon::parse($insp->created_at)->format('Y-m-d h:i A'),
                    'code' => $insp->extinguisher->code ?? 'N/A',
                    'location' => ($insp->extinguisher->building->building_no ?? '?') . ' - ' . ($insp->extinguisher->centerRoom->room_name ?? 'Unassigned'),
                    'inspector' => $insp->inspector->name ?? 'Unknown',
                    'status' => $insp->status,
                    'pressure_level' => $insp->pressure_level,
                    'notes' => $insp->notes,
                ];
            });

        $archiveRows = FireSafetyArchive::where('unified_school_id', $schoolId)
            ->where('type', 'extinguisher')
            ->latest('removed_at')
            ->take($limit)
            ->get()
            ->map(function ($archive) {
                $itemData = is_array($archive->item_data) ? $archive->item_data : [];
                return [
                    'ts' => (string) ($archive->removed_at ?? $archive->created_at),
                    'date' => Carbon::parse($archive->removed_at ?? $archive->created_at)->format('Y-m-d h:i A'),
                    'code' => $archive->item_code ?? 'N/A',
                    'location' => ($itemData['building_name'] ?? '?') . ' - ' . ($itemData['room_name'] ?? 'Unassigned'),
                    'inspector' => 'System Archive',
                    'status' => 'decommissioned',
                    'pressure_level' => $itemData['pressure_level'] ?? '-',
                    'notes' => 'Removed/Decommissioned: ' . ($archive->reason ?? 'No reason provided'),
                ];
            });

        return $inspectionRows
            ->concat($archiveRows)
            ->sortByDesc('ts')
            ->take($limit)
            ->values();
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

        if (auth()->user()->role !== 'admin' && (int)$room->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this room.'], 403);
        }
        $rules = [
            'room_code' => 'nullable|string|max:50',
            'room_name' => 'nullable|string',
            'room_type_config_id' => 'nullable|exists:system_configurations,id',
            'nearest_extinguisher_room_id' => 'nullable|exists:fire_safety_rooms,id',
            'smoke_detector_required' => 'boolean',
            'has_smoke_detector' => 'boolean',
            'has_secondary_exit' => 'boolean',
            'secondary_exit_remarks' => 'nullable|string',
            'remarks' => 'nullable|string',
            'is_evacuation_room' => 'boolean',
            'Buffer_evac' => 'boolean',
            'Main_evac' => 'boolean',
        ];

        if (Schema::hasColumn('fire_safety_rooms', 'engineer_last_inspection_date')) {
            $rules['engineer_last_inspection_date'] = 'nullable|date';
        }

        $validated = $request->validate($rules);

        if (!isset($validated['smoke_detector_required'])) {
            $validated['smoke_detector_required'] = false;
        }

        if (!isset($validated['has_smoke_detector'])) {
            $validated['has_smoke_detector'] = false;
        }

        if (!isset($validated['has_secondary_exit'])) {
            $validated['has_secondary_exit'] = false;
        }

        if (!isset($validated['is_evacuation_room'])) {
            $validated['is_evacuation_room'] = false;
        }

        if (!isset($validated['Buffer_evac'])) {
            $validated['Buffer_evac'] = false;
        }

        if (!isset($validated['Main_evac'])) {
            $validated['Main_evac'] = false;
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

        // Track what changed for notification
        $roomChanges = [];
        if (!empty($validated['room_name']) && $room->room_name !== ($validated['room_name'] ?? $room->room_name)) $roomChanges[] = 'Name: ' . $validated['room_name'];
        if (isset($validated['room_code']) && $room->room_code !== $validated['room_code']) $roomChanges[] = 'Code: ' . $validated['room_code'];
        if (isset($validated['smoke_detector_required']) && (bool)$room->smoke_detector_required !== (bool)$validated['smoke_detector_required']) $roomChanges[] = 'Smoke Detector Required: ' . ($validated['smoke_detector_required'] ? 'Yes' : 'No');
        if (isset($validated['has_smoke_detector']) && (bool)$room->has_smoke_detector !== (bool)$validated['has_smoke_detector']) $roomChanges[] = 'Has Smoke Detector: ' . ($validated['has_smoke_detector'] ? 'Yes' : 'No');
        if (isset($validated['has_secondary_exit']) && (bool)$room->has_secondary_exit !== (bool)$validated['has_secondary_exit']) $roomChanges[] = 'Secondary Exit: ' . ($validated['has_secondary_exit'] ? 'Yes' : 'No');
        if (isset($validated['remarks']) && $room->remarks !== ($validated['remarks'] ?? $room->remarks)) $roomChanges[] = 'Remarks updated';

        if ($user->role === 'contributor') {
            $validated['approval_status'] = 'pending';

            // Notify via notification system
            self::createFireSafetyNotification(
                'room_approval',
                'Room Update Pending Approval: ' . $room->room_code,
                $user->name . ' updated room ' . $room->room_code . ' and it requires administrator approval.' . (!empty($roomChanges) ? ' Changes: ' . implode(', ', $roomChanges) : ''),
                $room->unified_school_id,
                'see_inspection',
                ['room_id' => $room->id, 'unified_school_id' => $room->unified_school_id, 'status' => 'pending', 'updated_by' => $user->name]
            );
        } else {
            $validated['approval_status'] = 'approved';

            // Admin updates also create a notification
            if (!empty($roomChanges)) {
                self::createFireSafetyNotification(
                    'room_update',
                    'Room Updated: ' . $room->room_code,
                    $user->name . ' updated room ' . $room->room_code . '. Changes: ' . implode(', ', $roomChanges),
                    $room->unified_school_id,
                    'see_inspection',
                    ['room_id' => $room->id, 'unified_school_id' => $room->unified_school_id, 'updated_by' => $user->name]
                );
            }
        }

        $room->update($validated);

        // If room type changed, clear all extinguisher coverage for this room
        if ($roomTypeChanged) {
            $affectedRoomIds = [];

            // If this room WAS a center room of extinguisher(s), clear each extinguisher's
            // ENTIRE coverage (all rooms it was covering), then unlink the center room.
            $hostedExtinguishers = FireSafetyExtinguisher::where('room_id', $id)->get();
            if ($hostedExtinguishers->isNotEmpty()) {
                // Capture affected rooms BEFORE deleting pivot rows
                $affectedRoomIds = DB::table('fire_safety_extinguisher_room_coverage')
                    ->whereIn('extinguisher_id', $hostedExtinguishers->pluck('id'))
                    ->pluck('room_id')
                    ->map(fn ($v) => (int) $v)
                    ->values()
                    ->toArray();

                foreach ($hostedExtinguishers as $hostedExt) {
                    // Remove ALL rooms from this extinguisher's coverage pivot
                    // Use relationship detach to ensure the correct connection/table is used.
                    $hostedExt->coveredRooms()->detach();

                    // Safety cleanup in case of legacy rows
                    DB::table('fire_safety_extinguisher_room_coverage')
                        ->where('extinguisher_id', $hostedExt->id)
                        ->delete();

                    // Unlink center room
                    $hostedExt->room_id = null;
                    $hostedExt->save();
                }

                // Clear nearest pointers that reference this former host room
                FireSafetyRoom::where('building_id', $room->building_id)
                    ->where('nearest_extinguisher_room_id', $id)
                    ->update(['nearest_extinguisher_room_id' => null]);
            } else {
                // Not a center room — just remove this room from any coverage pivot
                DB::table('fire_safety_extinguisher_room_coverage')->where('room_id', $id)->delete();
                $affectedRoomIds = [(int) $id];
            }
            ActivityLog::log('fire_safety', 'Updated room: ' . ($room->room_code ?? $room->room_name ?? 'Room'), [
                'unified_school_id' => $room->unified_school_id,
                'notes' => $validated['remarks'] ?? null,
            ]);
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

        ActivityLog::log('fire_safety', 'Updated room: ' . ($room->room_code ?? $room->room_name ?? 'Room'), [
            'unified_school_id' => $room->unified_school_id,
            'notes' => $validated['remarks'] ?? null,
        ]);

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

        // Create notification for room approval
        self::createFireSafetyNotification(
            'room_approval',
            'Room Update Approved',
            'Room ' . $room->room_code . ' has been approved by ' . auth()->user()->name . '.',
            $room->unified_school_id,
            'see_inspection',
            ['room_id' => $room->id, 'unified_school_id' => $room->unified_school_id, 'status' => 'approved']
        );

        ActivityLog::log('fire_safety', 'Approved room: ' . ($room->room_code ?? $room->room_name ?? 'Room'), [
            'unified_school_id' => $room->unified_school_id,
        ]);

        return response()->json(['success' => true]);
    }

    public function rejectRoom(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only administrators can reject room updates.'], 403);
        }

        $room = FireSafetyRoom::findOrFail($id);
        $reason = $request->input('reason', 'Rejected by ' . auth()->user()->name);
        $room->update([
            'approval_status' => 'rejected',
            'approval_message' => $reason
        ]);

        ActivityLog::log('fire_safety', 'Rejected room: ' . ($room->room_code ?? $room->room_name ?? 'Room'), [
            'unified_school_id' => $room->unified_school_id,
            'notes' => $reason,
        ]);

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

    if (! $request->filled('unified_school_id') && $request->filled('school_id')) {
        $request->merge(['unified_school_id' => $request->school_id]);
    }

    $rules = [
        'unified_school_id' => 'required|exists:schools,id',
        'building_id' => 'required|exists:firesafety_buildings,id',
        'room_code' => 'nullable|string|max:50',
        'room_name' => 'nullable|string|max:100',
        'room_type_config_id' => 'required|exists:system_configurations,id',
        'floor_no' => 'required|integer|min:1|max:50',
        'smoke_detector_required' => 'boolean',
        'has_smoke_detector' => 'boolean',
        'has_secondary_exit' => 'boolean',
        'secondary_exit_remarks' => 'nullable|string',
        'remarks' => 'nullable|string',
        'is_evacuation_room' => 'boolean',
        'Buffer_evac' => 'boolean',
        'Main_evac' => 'boolean'
    ];

    if (Schema::hasColumn('fire_safety_rooms', 'engineer_last_inspection_date')) {
        $rules['engineer_last_inspection_date'] = 'nullable|date';
    }

    $validated = $request->validate($rules);

    if (!isset($validated['smoke_detector_required'])) {
        $validated['smoke_detector_required'] = false;
    }

    if (!isset($validated['has_smoke_detector'])) {
        $validated['has_smoke_detector'] = false;
    }

    if (!isset($validated['has_secondary_exit'])) {
        $validated['has_secondary_exit'] = false;
    }

    if (!isset($validated['is_evacuation_room'])) {
        $validated['is_evacuation_room'] = false;
    }

    if (!isset($validated['Buffer_evac'])) {
        $validated['Buffer_evac'] = false;
    }

    if (!isset($validated['Main_evac'])) {
        $validated['Main_evac'] = false;
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

        // Notify Admins via notification
        $school = School::find($validated['unified_school_id']);
        if ($school) {
            self::createFireSafetyNotification(
                'room_approval',
                'New Room Created (Pending Approval)',
                "Contributor {$user->name} created a new room and it requires administrator approval.",
                $school->id,
                null,
                ['posted_by' => $user->name]
            );
        }
    } else {
        $validated['approval_status'] = 'approved';
    }

    $room = FireSafetyRoom::create($validated);

    ActivityLog::log('fire_safety', 'Created room: ' . ($room->room_name ?? $room->room_code ?? 'Room'), [
        'unified_school_id' => $room->unified_school_id,
        'notes' => $validated['remarks'] ?? null,
    ]);

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
            'engineer_last_inspection_date' => $room->engineer_last_inspection_date,
            'remarks' => $room->remarks,
            'has_smoke_detector' => $room->has_smoke_detector,
            'has_secondary_exit' => $room->has_secondary_exit,
            'secondary_exit_remarks' => $room->secondary_exit_remarks,
            'is_center_room' => $isCenterRoom,
            'is_evacuation_room' => (bool)$room->is_evacuation_room,
            'Buffer_evac' => (bool)$room->Buffer_evac,
            'Main_evac' => (bool)$room->Main_evac,
            'host_room_id' => $hostRoomId,
            'host_room_code' => $hostRoomCode,
            'building' => $room->building ? ($room->building->building_no . ($room->building->building_name ? ' - ' . $room->building->building_name : '')) : 'N/A',
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

        if (! $request->filled('unified_school_id') && $request->filled('school_id')) {
            $request->merge(['unified_school_id' => $request->school_id]);
        }

        // floor, room and coverage are now optional to allow creation without assignment
        $validated = $request->validate([
            'unified_school_id' => 'required|exists:schools,id',
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

        $pressureError = $this->validateExtinguisherPressureByStatus($validated['status'], (int) $validated['pressure_level']);
        if ($pressureError) {
            return response()->json([
                'success' => false,
                'message' => $pressureError,
            ], 422);
        }

        // Ensure building belongs to school
        $building = FireSafetyBuilding::where('id', $validated['building_id'])
            ->where('unified_school_id', $validated['unified_school_id'])
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
                ->where('unified_school_id', $validated['unified_school_id'])
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
                ->where('unified_school_id', $validated['unified_school_id'])
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
            $centerRoom->loadMissing(['roomTypeConfig.parent']);
            $hostLimit = $this->getCenterRoomHostLimit($centerRoom);
            $existingCenterHostCount = FireSafetyExtinguisher::where('room_id', $validated['room_id'])->count();
            if ($existingCenterHostCount >= $hostLimit) {
                return response()->json([
                    'success' => false,
                    'message' => "Selected center room can only host up to {$hostLimit} extinguisher(s)."
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
        $codeExists = FireSafetyExtinguisher::where('unified_school_id', $validated['unified_school_id'])
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

            // If this is the first hosted extinguisher for the center room, clear previous third-party coverage rows.
            if (!empty($validated['room_id']) && (($existingCenterHostCount ?? 0) === 0)) {
                DB::table('fire_safety_extinguisher_room_coverage')
                    ->where('room_id', (int) $validated['room_id'])
                    ->delete();
            }

            $ext = FireSafetyExtinguisher::create([
                'unified_school_id' => $validated['unified_school_id'],
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

            ActivityLog::log('fire_safety', 'Created extinguisher: ' . $ext->code, [
                'unified_school_id' => $ext->unified_school_id,
                'notes' => $validated['remarks'] ?? null,
            ]);

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
                ->get()
                ->sortBy('code', SORT_NATURAL);

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

            $hostCountsByRoom = $extinguishers
                ->pluck('room_id')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->countBy();

            // Build covering extinguisher lookup (room_id -> all extinguisher codes)
            $coveringMap = [];
            foreach ($extinguishers as $ext) {
                foreach ($ext->coveredRooms as $r) {
                    $coveringMap[(int) $r->id] ??= [];
                    $coveringMap[(int) $r->id][] = [
                        'id' => (int) $ext->id,
                        'code' => (string) $ext->code,
                        'is_center_room' => (int) $ext->room_id === (int) $r->id,
                    ];
                }
            }

            // Get room details with coverage status
            $rooms = $building->actualRooms->map(function($room) use ($coveredRoomIds, $hostRoomIds, $hostCountsByRoom, $coveringMap) {
                $priority = $room->roomTypeConfig?->parent;
                $maxLimit = ($priority && $priority->config_type === 'calculated_priority')
                    ? (int)($priority->max_rooms_covered ?? 3)
                    : (in_array(strtolower($room->room_type), ['classroom', 'department', 'library']) ? 3 : 2);

                $priorityLabel = $priority?->name ?? $room->calculated_priority_label ?? null;
                $coverings = $coveringMap[(int) $room->id] ?? [];
                $hostCount = (int) ($hostCountsByRoom->get((int) $room->id, 0));
                $hostLimit = $this->getCenterRoomHostLimit($room);

                return [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'room_name' => $room->room_name,
                    'floor_no' => $room->floor_no,
                    'room_type' => $room->room_type,
                    'max_rooms' => $maxLimit,
                    'priority_label' => $priorityLabel,
                    'host_count' => $hostCount,
                    'host_limit' => $hostLimit,
                    'can_host_more' => $hostCount < $hostLimit,
                    'is_covered' => in_array($room->id, $coveredRoomIds),
                    'is_host_room' => in_array((int)$room->id, $hostRoomIds, true),
                    'covering_extinguishers' => $coverings,
                    'covering_extinguisher_ids' => array_column($coverings, 'id'),
                    'covering_extinguisher_codes' => array_column($coverings, 'code'),
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

    private function getCenterRoomHostLimit(?FireSafetyRoom $room): int
    {
        if (!$room) {
            return 1;
        }

        $room->loadMissing(['roomTypeConfig.parent']);
        $priorityConfig = $room->roomTypeConfig?->parent;
        $requiredExtinguishers = (int) ($priorityConfig?->required_extinguishers ?? 1);

        return max(1, min(5, $requiredExtinguishers));
    }

    private function validateExtinguisherPressureByStatus(string $status, int $pressure): ?string
    {
        if ($status === 'active' && ($pressure < 70 || $pressure > 100)) {
            return 'OK (Active) pressure must be between 70 and 100.';
        }

        if ($status === 'maintenance' && ($pressure < 20 || $pressure > 69)) {
            return 'For Preventive Maintenance pressure must be between 20 and 69.';
        }

        if ($status === 'expired' && ($pressure < 0 || $pressure > 19)) {
            return 'Used pressure must be between 0 and 19.';
        }

        return null;
    }

    // Update Extinguisher Status & Log Inspection
    public function updateExtinguisher(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update extinguishers.'], 403);
        }

        $request->validate([
            'code' => 'required|string|max:255',
            'status' => 'required|in:active,expired,maintenance,missing,purchase,decommissioned',
            'pressure_level' => 'required|integer|min:0|max:100',
            'evaluation_result' => 'nullable|in:Passed,Failed',
            'notes' => 'required|string',
            'room_id' => 'nullable|integer|exists:fire_safety_rooms,id',
            'covered_room_ids' => 'nullable|array',
            'covered_room_ids.*' => 'integer|exists:fire_safety_rooms,id'
        ]);

        $pressureError = $this->validateExtinguisherPressureByStatus($request->status, (int) $request->pressure_level);
        if ($pressureError) {
            return response()->json([
                'success' => false,
                'message' => $pressureError,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ext = FireSafetyExtinguisher::findOrFail($id);

            // Ensure code is unique within the same school
            $codeExists = FireSafetyExtinguisher::where('unified_school_id', $ext->unified_school_id)
                ->where('code', $request->code)
                ->where('id', '!=', $id)
                ->exists();
            if ($codeExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Extinguisher code already exists for this school.'
                ], 422);
            }

            // A room can host its own extinguisher even if it was previously covered by another extinguisher.
            // Shared Space rooms can host up to 2 extinguishers; all others can host 1.
            $newCenterId = $request->room_id ? (int) $request->room_id : null;
            if ($newCenterId) {
                $newCenterRoom = FireSafetyRoom::with(['roomTypeConfig.parent'])->find($newCenterId);
                $hostLimit = $this->getCenterRoomHostLimit($newCenterRoom);
                $hostsAnotherExtCount = FireSafetyExtinguisher::where('room_id', $newCenterId)
                    ->where('id', '!=', $id)
                    ->count();
                if ($hostsAnotherExtCount >= $hostLimit) {
                    return response()->json([
                        'success' => false,
                        'message' => "Selected center room can only host up to {$hostLimit} extinguisher(s)."
                    ], 422);
                }

                if ($hostsAnotherExtCount === 0) {
                    // When this is the first host in the room, clear stale third-party coverage rows.
                    DB::table('fire_safety_extinguisher_room_coverage')
                        ->where('room_id', $newCenterId)
                        ->where('extinguisher_id', '!=', $id)
                        ->delete();
                }
            }

            $ext->code = $request->code;
            $ext->status = $request->status;
            $ext->pressure_level = $request->pressure_level;
            $ext->date_checked = now();
            $ext->room_id = $request->room_id ?: null;
            $ext->evaluation_result = $request->input('evaluation_result') ?: (($request->status === 'active') ? 'Passed' : 'Failed');
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

            // Create notification for extinguisher inspection
            self::createFireSafetyNotification(
                'extinguisher_inspection',
                'Extinguisher Inspected: ' . $ext->code,
                'Extinguisher ' . $ext->code . ' was inspected. Status: ' . ucfirst($ext->status) . ', Pressure: ' . $ext->pressure_level . '%',
                $ext->unified_school_id,
                'update_now',
                ['extinguisher_id' => $ext->id, 'unified_school_id' => $ext->unified_school_id]
            );

            ActivityLog::log('fire_safety', 'Updated extinguisher: ' . $ext->code, [
                'unified_school_id' => $ext->unified_school_id,
                'notes' => $request->notes,
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

            ActivityLog::log('fire_safety', 'Unassigned extinguisher: ' . $ext->code, [
                'unified_school_id' => $ext->unified_school_id,
            ]);

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
            $schoolId = $ext->unified_school_id;
            $code = $ext->code;

            // Create archive entry
            FireSafetyArchive::create([
                'unified_school_id' => $ext->unified_school_id,
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

            // Record this lifecycle action in Recent Extinguisher Inspections.
            FireSafetyExtinguisherInspection::create([
                'extinguisher_id' => $ext->id,
                'user_id' => Auth::id(),
                'inspection_date' => now(),
                'status' => 'decommissioned',
                'pressure_level' => $ext->pressure_level,
                'notes' => 'Removed/Decommissioned: ' . $request->reason,
            ]);

            // Delete the extinguisher
            $ext->delete();

            ActivityLog::log('fire_safety', 'Removed extinguisher: ' . $code, [
                'unified_school_id' => $schoolId,
                'notes' => $request->reason,
            ]);

            self::createFireSafetyNotification(
                'extinguisher_decommissioned',
                'Extinguisher Decommissioned: ' . $code,
                'Extinguisher ' . $code . ' was decommissioned and removed from active inventory. Reason: ' . $request->reason,
                $schoolId,
                'update_now',
                [
                    'extinguisher_code' => $code,
                    'unified_school_id' => $schoolId,
                    'status' => 'decommissioned'
                ]
            );

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getExtinguisherHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('unified_school_id', $schoolId)
            ->where('type', 'extinguisher')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function transferExtinguisher(Request $request, $id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot transfer extinguishers.'], 403);
        }

        $request->validate([
            'building_id' => 'required|integer|exists:firesafety_buildings,id'
        ]);

        try {
            DB::beginTransaction();

            $ext = FireSafetyExtinguisher::with(['building'])->findOrFail($id);
            $targetBuilding = FireSafetyBuilding::findOrFail($request->building_id);
            $sourceBuildingLabel = $ext->building?->building_no ?? ($ext->building?->building_name ?? 'Unknown');

            // Ensure target building is in the same school
            if ($targetBuilding->unified_school_id !== $ext->unified_school_id) {
                return response()->json(['success' => false, 'message' => 'Cannot transfer to a building in a different school.'], 422);
            }

            // Ensure not transferring to the same building
            if ((int) $targetBuilding->id === (int) $ext->building_id) {
                return response()->json(['success' => false, 'message' => 'Extinguisher is already in this building.'], 422);
            }

            // Remove all covered room associations
            DB::table('fire_safety_extinguisher_room_coverage')->where('extinguisher_id', $ext->id)->delete();

            // Update extinguisher to new building, unlink from room
            $ext->building_id = $targetBuilding->id;
            $ext->room_id = null;
            $ext->save();

            // Record transfer action in Recent Extinguisher Inspections.
            FireSafetyExtinguisherInspection::create([
                'extinguisher_id' => $ext->id,
                'user_id' => Auth::id(),
                'inspection_date' => now(),
                'status' => $ext->status,
                'pressure_level' => $ext->pressure_level,
                'notes' => 'Transferred from ' . $sourceBuildingLabel . ' to ' . ($targetBuilding->building_no ?? $targetBuilding->building_name ?? 'Unknown'),
            ]);

            ActivityLog::log('fire_safety', 'Transferred extinguisher: ' . $ext->code . ' to ' . $targetBuilding->building_no, [
                'unified_school_id' => $ext->unified_school_id,
            ]);

            self::createFireSafetyNotification(
                'extinguisher_transferred',
                'Extinguisher Transferred: ' . $ext->code,
                'Extinguisher ' . $ext->code . ' was transferred from ' . $sourceBuildingLabel . ' to ' . ($targetBuilding->building_no ?? $targetBuilding->building_name ?? 'Unknown Building') . '.',
                $ext->unified_school_id,
                'update_now',
                [
                    'extinguisher_id' => $ext->id,
                    'extinguisher_code' => $ext->code,
                    'unified_school_id' => $ext->unified_school_id,
                    'source_building' => $sourceBuildingLabel,
                    'target_building' => ($targetBuilding->building_no ?? $targetBuilding->building_name ?? 'Unknown Building')
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Extinguisher {$ext->code} transferred to {$targetBuilding->building_no} successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRoomHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('unified_school_id', $schoolId)
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
            $schoolId = $room->unified_school_id;
            $roomLabel = $room->room_code ?? $room->room_name ?? 'Room';

            $message = $this->processRoomRemoval($room, $request->reason);

            ActivityLog::log('fire_safety', 'Removed room: ' . $roomLabel, [
                'unified_school_id' => $schoolId,
                'notes' => $request->reason,
            ]);

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

        return response()->json($this->buildRecentExtinguisherActivity((int) $schoolId, 10));
    }

    public function getRecentRoomUpdates($schoolId)
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && (int)$user->school_id !== (int)$schoolId) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $updates = FireSafetyRoom::where('unified_school_id', $schoolId)
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
                    'location' => ($r->building->building_no ?? '?') . ', ' . $r->floor_label,
                    'nearest_extinguisher' => $nearest,
                    'inspector' => $r->lastInspector->name ?? 'Unknown',
                    'remarks' => $remarks,
                    'last_updated' => $r->updated_at->format('Y-m-d h:i A'),
                    'approval_status' => $r->approval_status,
                    'is_evacuation_room' => (bool)$r->is_evacuation_room,
                    'Main_evac' => (bool)$r->Main_evac,
                    'Buffer_evac' => (bool)$r->Buffer_evac
                ];
            });

        return response()->json($updates);
    }

    public function buildings(Request $request, ?School $school = null)
    {
        if (auth()->user()->role === 'viewer' && !isset(auth()->user()->school_id)) {
            return redirect()->route('fire-safety.dashboard')->with('error', 'No school assigned.');
        }

        if (!$school && $request->filled('school_id')) {
            $requestedSchool = School::find((int) $request->input('school_id'));
            if ($requestedSchool) {
                return redirect()->route('fire-safety.schools.buildings', ['school' => $requestedSchool->id] + $request->query());
            }
        }

        $resolvedSchool = $this->resolveScopedSchool($school);
        if (!$resolvedSchool) {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Unauthorized or no school assigned.');
        }

        if (!$school) {
            return redirect()->route('fire-safety.schools.buildings', ['school' => $resolvedSchool->id] + $request->query());
        }

        session(['fire_safety_active_unified_school_id' => (int) $resolvedSchool->id]);

        $selectedSchool = School::with([
            'fireSafetyBuildings.actualRooms',
            'fireSafetyBuildings.fireExtinguishers.coveredRooms',
            'fireSafetyBuildings.alarmSystems',
            'fireSafetyBuildings.alarmSystemsMany',
            'fireSafetyBuildings.evacuationPlan'
        ])->find($resolvedSchool->id);

        if (!$selectedSchool) {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Selected school was not found.');
        }

        $schools = collect([$selectedSchool]);

        $activeSchool = $this->getActiveSchool($schools);
        $buildingTypes = SystemConfiguration::where('config_type', 'building_type')->where('is_active', true)->orderBy('sort_order')->get()->unique('name');
        $checklists = SystemConfiguration::where('config_type', 'inspection_checklist')->where('is_active', true)->orderBy('sort_order')->get();
        $observers = SystemConfiguration::where('config_type', 'inspection_observer')->where('is_active', true)->orderBy('sort_order')->get();

        $alarmTypes = SystemConfiguration::where('config_type', 'alarm_type')->where('is_active', true)->orderBy('sort_order')->get();
        $alarmStatusesByType = SystemConfiguration::where('config_type', 'alarm_status')->where('is_active', true)->get()->groupBy('parent_id');
        $safetyFeatures = SystemConfiguration::where('config_type', 'safety_feature')->where('is_active', true)->orderBy('sort_order')->get()->unique('name');

        return view('fire-safety.buildings',[
            'schools' => $schools,
            'activeSchool' => $activeSchool,
            'buildingTypes' => $buildingTypes,
            'checklists' => $checklists,
            'observers' => $observers,
            'alarmTypes' => $alarmTypes,
            'alarmStatusesByType' => $alarmStatusesByType,
            'safetyFeatures' => $safetyFeatures,
            'isViewer' => auth()->user()->role === 'viewer',
            'schoolNavOptions' => $this->getSchoolSwitcherOptions(),
            'schoolSwitchUrlPattern' => '/fire-safety/schools/__SCHOOL_ID__/buildings',
        ]);
    }

    public static function calculateBuildingCompliance($building)
    {
        // Start from the model's computed safety score (0–100)
        $score = $building->safety_score;

        // Requirement 1: Building must have at least one active alarm covering it
        // Check: "Installed Here" (building_id), "Covering" via pivot, OR any active alarm in the school
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

        // Also accept if the school has any active alarm (matches safety_score accessor logic)
        if (!$hasCoveringAlarm) {
            $hasCoveringAlarm = $building->school->fireSafetyAlarms()
                ->whereIn('status', $activeAlarmStatuses)
                ->exists();
        }

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

        if (auth()->user()->role !== 'admin' && (int)$request->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $validated = $request->validate([
            'unified_school_id' => 'required|exists:schools,id',
            'building_no' => 'required|string|max:50',
            'building_name' => 'nullable|string|max:100',
            'floors' => 'required|integer|min:1',
            'rooms' => 'required|integer|min:1',
            'year_constructed' => 'nullable|integer|min:1900|max:' . date('Y'),
            'last_renovation' => 'nullable|integer|min:1900|max:' . date('Y'),
            'emergency_exits' => 'nullable|integer|min:0',
            'building_type' => 'nullable|string',
            'other_building_type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'required_extinguishers' => 'nullable|integer|min:0',
            'compliance_status' => 'nullable|in:Compliant,Non-Compliant,Partially Compliant',
            'compliance_reason' => 'nullable|required_if:compliance_status,Non-Compliant|string|max:500'
        ]);

        if ($request->building_type === 'Others' && $request->has('other_building_type')) {
            $validated['building_type'] = $request->other_building_type;
        }

        // Unique Building Code Check (Per School)
        $exists = FireSafetyBuilding::where('unified_school_id', $validated['unified_school_id'])
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
    $validated['features'] = $validated['features'] ?? null;

    // Fallback if the field is empty in the request
    $validated['compliance_status'] = $validated['compliance_status'] ?? 'Non-Compliant';

    $building = FireSafetyBuilding::create($validated);

        ActivityLog::log('fire_safety', 'Created building: ' . ($building->building_name ?? $building->building_no), [
            'unified_school_id' => $building->unified_school_id,
            'notes' => $validated['description'] ?? null,
        ]);

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

        if (auth()->user()->role !== 'admin' && (int)$building->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this building.'], 403);
        }

        $validated = $request->validate([
            'building_no' => 'required|string|max:50',
            'building_name' => 'nullable|string|max:100',
            'building_type' => 'nullable|string|max:100',
            'floors' => 'nullable|integer|min:1',
            'rooms' => 'nullable|integer|min:1',
            'year_constructed' => 'nullable|integer|min:1900|max:' . date('Y'),
            'last_renovation' => 'nullable|integer|min:1900|max:' . date('Y'),
            'emergency_exits' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'other_building_type' => 'nullable|string|max:100',
            'features' => 'nullable|array',
            'removed_floor' => 'nullable|integer',
            'removed_room_id' => 'nullable|array',
            'removed_room_id.*' => 'exists:fire_safety_rooms,id',
            'floor_removal_reason' => 'nullable|required_with:removed_floor|string|max:500',
            'room_removal_reason' => 'nullable|required_with:removed_room_id|string|max:500',
            'required_extinguishers' => 'nullable|integer|min:0'
        ]);

        if ($request->building_type === 'Others' && $request->has('other_building_type')) {
            $validated['building_type'] = $request->other_building_type;
        }

        // Check uniqueness if building_no is changing
        if ($validated['building_no'] !== $building->building_no) {
            $exists = FireSafetyBuilding::where('unified_school_id', $building->unified_school_id)
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

        // Handle Building Type update
        if (isset($validated['building_type']) && $validated['building_type'] !== $building->building_type) {
            $building->building_type = $validated['building_type'];
        }

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
                    'unified_school_id' => $building->unified_school_id,
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
                        'unified_school_id' => $alarm->unified_school_id,
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

            // Allow reducing room capacity even without explicit room-id removals,
            // as long as encoded/active room records do not exceed the requested capacity.
            if ($request->filled('rooms') && (int)$request->rooms < $building->rooms) {
                $targetRooms = (int) $request->rooms;
                $activeRoomsCount = FireSafetyRoom::where('building_id', $id)->count();

                if ($targetRooms < $activeRoomsCount) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot reduce total rooms to {$targetRooms}. {$activeRoomsCount} room(s) are currently encoded. Remove specific rooms first or set a higher room count."
                    ], 422);
                }

                $building->rooms = $targetRooms;
                $cascadingMessages[] = "Room capacity updated to {$targetRooms}.";
            }

            // Prepare fill data except for specific handling fields
            $fillData = collect($validated)->except(['removed_floor', 'removed_room_id', 'building_type', 'floors', 'rooms', 'floor_removal_reason', 'room_removal_reason'])->toArray();

            $building->fill($fillData);

            // Track what changed for notification
            $buildingChanges = [];
            if ($building->isDirty('building_no')) $buildingChanges[] = 'Building No: ' . $building->building_no;
            if ($building->isDirty('building_name')) $buildingChanges[] = 'Name: ' . ($building->building_name ?? 'N/A');
            if ($building->isDirty('year_constructed')) $buildingChanges[] = 'Year Constructed: ' . ($building->year_constructed ?? 'N/A');
            if ($building->isDirty('last_renovation')) $buildingChanges[] = 'Last Renovation: ' . ($building->last_renovation ?? 'N/A');
            if ($building->isDirty('emergency_exits')) $buildingChanges[] = 'Emergency Exits: ' . ($building->emergency_exits ?? 0);
            if ($building->isDirty('description')) $buildingChanges[] = 'Description updated';
            if ($building->isDirty('features')) $buildingChanges[] = 'Safety Features updated';
            if ($building->isDirty('required_extinguishers')) $buildingChanges[] = 'Required Extinguishers: ' . ($building->required_extinguishers ?? 0);

            $building->save();

            // Create notification for building update
            $allChanges = array_merge($buildingChanges, $cascadingMessages);
            if (!empty($allChanges)) {
                $user = auth()->user();
                self::createFireSafetyNotification(
                    'building_update',
                    'Building Updated: ' . $building->building_no,
                    $user->name . ' updated building ' . ($building->building_name ?? $building->building_no) . '. Changes: ' . implode(', ', $allChanges),
                    $building->unified_school_id,
                    null,
                    ['building_id' => $building->id, 'unified_school_id' => $building->unified_school_id, 'updated_by' => $user->name]
                );
            }

            $notes = array_filter([
                $request->floor_removal_reason,
                $request->room_removal_reason,
            ]);
            ActivityLog::log('fire_safety', 'Updated building: ' . ($building->building_name ?? $building->building_no), [
                'unified_school_id' => $building->unified_school_id,
                'notes' => implode('; ', $notes) ?: null,
            ]);

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

        if (auth()->user()->role !== 'admin' && (int)$building->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            // Archive Building
            FireSafetyArchive::create([
                'unified_school_id' => $building->unified_school_id,
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
            // Archive and delete Alarms
            $alarms = $building->alarmSystems()->get();
            foreach ($alarms as $alarm) {
                FireSafetyArchive::create([
                    'unified_school_id' => $alarm->unified_school_id ?? $building->unified_school_id,
                    'type' => 'alarm',
                    'item_id' => null, // Deleting
                    'item_code' => $alarm->code,
                    'item_data' => [
                        'alarm_type' => $alarm->alarm_type,
                        'status' => $alarm->status,
                        'is_shared' => $alarm->is_shared ?? false,
                        'building_name' => collect($alarm->buildings)->pluck('building_name')->implode(', ') ?: ($building->building_name ?? $building->building_no),
                        'cascaded_from' => "Building {$building->building_no} Removal"
                    ],
                    'reason' => "Cascading removal: Building {$building->building_no} removed. Reason: " . $validated['reason'],
                    'removed_at' => now()
                ]);
                $alarm->delete();
            }

            // Archive and delete Rooms (and Extinguishers)
            $rooms = FireSafetyRoom::where('building_id', $building->id)->get();
            $cascadeReason = "Cascading removal: Building {$building->building_no} removed. Reason: " . $validated['reason'];
            foreach ($rooms as $room) {
               $this->processRoomRemoval($room, $cascadeReason);
            }

            $schoolId = $building->unified_school_id;
            $buildingName = $building->building_name ?? $building->building_no;
            $building->delete();

            ActivityLog::log('fire_safety', 'Removed building: ' . $buildingName, [
                'unified_school_id' => $schoolId,
                'notes' => $validated['reason'],
            ]);

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
            'unified_school_id' => $room->unified_school_id,
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
                    'unified_school_id' => $ext->unified_school_id,
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
            // 1. Eager load ALL necessary relationships
            $building = FireSafetyBuilding::with([
                'school',
                'actualRooms',
                'fireExtinguishers',
                'alarmSystems.building',
                'alarmSystemsMany' => static function ($q) {
                    $q->with('building');
                },
            ])->findOrFail($id);

            // 2. Access Control Check (with type coercion to ensure match)
            if (auth()->user()->role !== 'admin' && (int)$building->unified_school_id !== (int)auth()->user()->school_id) {
                Log::warning('Access denied for user ' . auth()->id() . ' to building ' . $id);
                return response()->json(['error' => 'Access denied'], 403);
            }

            // 3. Alarms: direct, pivot coverage, and anchored-unassigned (shown on anchor building only)
            $anchored = FireSafetyAlarmSystem::with('building')
                ->where('unified_school_id', $building->unified_school_id)
                ->whereNull('building_id')
                ->where('anchor_building_id', $building->id)
                ->get();

            $allAlarms = $building->alarmSystems
                ->merge($building->alarmSystemsMany)
                ->merge($anchored)
                ->unique('id')
                ->values();

            $alarmPayload = $allAlarms->map(static function (FireSafetyAlarmSystem $alarm) use ($building) {
                if (!isset($alarm->floor_id) && isset($alarm->floor)) {
                    $alarm->floor_id = $alarm->floor;
                }
                if (!isset($alarm->floor_id) && isset($alarm->floor_no)) {
                    $alarm->floor_id = $alarm->floor_no;
                }

                $primaryBuilding = $alarm->relationLoaded('building') ? $alarm->building : $alarm->building()->first();
                $siteLabel = 'Unknown';
                if ($primaryBuilding) {
                    $parts = array_filter([$primaryBuilding->building_no, $primaryBuilding->building_name]);
                    $siteLabel = count($parts) ? implode(' — ', $parts) : 'Unknown';
                }

                $isAnchoredUnassigned = $alarm->building_id === null
                    && (int) $alarm->anchor_building_id === (int) $building->id;

                $row = $alarm->toArray();
                $row['is_anchored_unassigned'] = $isAnchoredUnassigned;
                $row['is_installed_here'] = (int) $alarm->building_id === (int) $building->id;
                $inPivot = $building->alarmSystemsMany->pluck('id')->contains($alarm->id);
                $row['is_coverage_only'] = (int) $alarm->building_id !== (int) $building->id
                    && ! $isAnchoredUnassigned
                    && $inPivot;
                $row['installation_site_label'] = $isAnchoredUnassigned ? 'Unassigned' : $siteLabel;
                $row['installation_location_detail'] = $alarm->location;

                return $row;
            })->values()->all();

            // 4. Transform data for the Frontend (Providing both casing styles)
            $buildingData = $building->toArray();
            $buildingData['alarm_systems'] = $alarmPayload;
            $buildingData['alarmSystems'] = $alarmPayload;
            $buildingData['actual_rooms'] = $building->actualRooms;
            $buildingData['actualRooms'] = $building->actualRooms;
            $buildingData['fire_extinguishers'] = $building->fireExtinguishers;
            $buildingData['fireExtinguishers'] = $building->fireExtinguishers;

            // 5. Explicit counts for frontend metrics
            $buildingData['active_extinguishers_count'] = $building->fireExtinguishers->where('status', 'active')->count();
            $buildingData['required_extinguishers_count'] = $building->required_extinguishers ?: 1;

            // 6. Add rooms_list for update modal (keeping exists logic for specific room info)
            $roomsList = $building->actualRooms->map(function($room) {
                $isCenterRoom = FireSafetyExtinguisher::where('room_id', $room->id)->exists();
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
            $buildingData['rooms_list'] = $roomsList;

            Log::info('Successfully returned building data for building #' . $id);
            return response()->json($buildingData);

        } catch (\Exception $e) {
            Log::error('Error loading building details: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => 'Building details could not be loaded: ' . $e->getMessage()], 500);
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
            $inspections = FireSafetyInspection::where('unified_school_id', $schoolId)
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
        $buildings = FireSafetyBuilding::where('unified_school_id', $schoolId)->get();

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

        $buildings = FireSafetyBuilding::where('unified_school_id', $schoolId)
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

    public function evacuationPlans(Request $request, ?School $school = null)
    {
        $resolvedSchool = $this->resolveScopedSchool($school);
        if (!$resolvedSchool) {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Unauthorized or no school assigned.');
        }

        if (!$school) {
            return redirect()->route('fire-safety.schools.evacuation-plans', ['school' => $resolvedSchool->id] + $request->query());
        }

        session(['fire_safety_active_unified_school_id' => (int) $resolvedSchool->id]);

        $selectedSchool = School::with([
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
        ])->find($resolvedSchool->id);

        if (!$selectedSchool) {
            return redirect()->route('fire-safety.dashboard')->with('error', 'Selected school was not found.');
        }

        $schools = collect([$selectedSchool]);
        $activeSchool = $this->getActiveSchool($schools);

        return view('fire-safety.evacuation-plans', [
            'schools' => $schools,
            'activeSchool' => $activeSchool,
            'schoolNavOptions' => $this->getSchoolSwitcherOptions(),
            'schoolSwitchUrlPattern' => '/fire-safety/schools/__SCHOOL_ID__/evacuation-plans',
        ]);
    }
    public function storeEvacuationPlan(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot add evacuation plans.'], 403);
        }

        if (! $request->filled('unified_school_id') && $request->filled('school_id')) {
            $request->merge(['unified_school_id' => $request->school_id]);
        }

        if (auth()->user()->role !== 'admin' && (int)$request->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $request->validate([
            'unified_school_id' => 'required|exists:schools,id',
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
        $existingPlan = FireSafetyEvacuationPlan::where('unified_school_id', $request->unified_school_id)->where('building_id', $request->building_id)->first();
        if ($existingPlan) {
            return response()->json(['success' => false, 'message' => 'An evacuation plan already exists for this building. Please edit the existing plan instead.'], 422);
        }

        // Ensure plan_no is unique within the school
        $planNoExists = FireSafetyEvacuationPlan::where('unified_school_id', $request->unified_school_id)
            ->where('plan_no', $request->plan_no)
            ->exists();
        if ($planNoExists) {
            return response()->json(['success' => false, 'message' => 'Plan name already exists in this school. Please use a different name.'], 422);
        }

        try {
            DB::beginTransaction();

            $plan = FireSafetyEvacuationPlan::create([
                'unified_school_id' => $request->unified_school_id,
                'school_id' => $request->unified_school_id,
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

            $this->syncAssemblyAreaFacilities(
                (int) $request->unified_school_id,
                $request->primary_assembly_area,
                $request->secondary_assembly_area
            );

            // Update building status if this is a building-specific plan
            if ($request->building_id) {
                $building = FireSafetyBuilding::findOrFail($request->building_id);
                $this->updateBuildingComplianceStatus($building);
            } else {
                // If it's a school-wide plan, update all buildings without specific plans
                $buildings = FireSafetyBuilding::where('unified_school_id', $request->unified_school_id)->get();
                foreach ($buildings as $building) {
                    $this->updateBuildingComplianceStatus($building);
                }
            }

            DB::commit();

            // Create notification for evacuation plan creation
            $user = auth()->user();
            $planLabel = $request->building_id
                ? 'Building Plan (' . (FireSafetyBuilding::find($request->building_id)->building_name ?? 'Unknown') . ')'
                : 'School-Wide Plan';
            self::createFireSafetyNotification(
                'evacuation_plan',
                'Evacuation Plan Created: ' . $request->plan_no,
                $user->name . ' created a new evacuation plan "' . $request->plan_no . '" - ' . $planLabel,
                $request->unified_school_id,
                null,
                ['plan_id' => $plan->id, 'plan_type' => $request->building_id ? 'building' : 'school', 'posted_by' => $user->name]
            );

            ActivityLog::log('fire_safety', 'Created evacuation plan: ' . $request->plan_no, [
                'unified_school_id' => (int) $request->unified_school_id,
                'notes' => $request->special_instructions ?: null,
            ]);

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
        $schoolBuildings = FireSafetyBuilding::where('unified_school_id', $plan->unified_school_id)
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

        if (auth()->user()->role !== 'admin' && (int)$plan->unified_school_id !== (int)auth()->user()->school_id) {
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
            $exists = FireSafetyEvacuationPlan::where('unified_school_id', $plan->unified_school_id)
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

            $this->syncAssemblyAreaFacilities(
                (int) $plan->unified_school_id,
                $request->primary_assembly_area,
                $request->secondary_assembly_area
            );

            // update related building compliance
            if ($plan->building_id) {
                $building = FireSafetyBuilding::find($plan->building_id);
                if ($building) {
                    $this->updateBuildingComplianceStatus($building);
                }
            } else {
                $buildings = FireSafetyBuilding::where('unified_school_id', $plan->unified_school_id)->get();
                foreach ($buildings as $building) {
                    $this->updateBuildingComplianceStatus($building);
                }
            }
            DB::commit();

            // Create notification for evacuation plan update
            $user = auth()->user();
            $planLabel = $plan->building_id
                ? 'Building Plan (' . (FireSafetyBuilding::find($plan->building_id)->building_name ?? 'Unknown') . ')'
                : 'School-Wide Plan';
            self::createFireSafetyNotification(
                'evacuation_plan',
                'Evacuation Plan Updated: ' . $plan->plan_no,
                $user->name . ' updated evacuation plan "' . $plan->plan_no . '" - ' . $planLabel,
                $plan->unified_school_id,
                null,
                ['plan_id' => $plan->id, 'plan_type' => $plan->building_id ? 'building' : 'school', 'posted_by' => $user->name]
            );

            ActivityLog::log('fire_safety', 'Updated evacuation plan: ' . $plan->plan_no, [
                'unified_school_id' => $plan->unified_school_id,
                'notes' => $request->special_instructions ?: null,
            ]);
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

        if (auth()->user()->role !== 'admin' && (int)$plan->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this plan.'], 403);
        }

        $schoolId = $plan->unified_school_id;
        $planNo = $plan->plan_no;
        $plan->delete();

        // after removing plan, refresh building compliance
        if ($plan->building_id) {
            $building = FireSafetyBuilding::find($plan->building_id);
            if ($building) {
                $this->updateBuildingComplianceStatus($building);
            }
        } else {
            // removed school-wide plan; update all buildings
            $buildings = FireSafetyBuilding::where('unified_school_id', $plan->unified_school_id)->get();
            foreach ($buildings as $building) {
                $this->updateBuildingComplianceStatus($building);
            }
        }

        ActivityLog::log('fire_safety', 'Deleted evacuation plan: ' . $planNo, [
            'unified_school_id' => $schoolId,
        ]);

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
        $buildings = FireSafetyBuilding::where('unified_school_id', $schoolId)->get();
        return response()->json($buildings);
    }

    public function scheduleDrill(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot schedule drills.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int)$request->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }
        $request->validate([
            'unified_school_id' => 'required|exists:schools,id',
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
            'unified_school_id', 'drill_type', 'drill_date', 'start_time', 'end_time',
            'participants_count', 'evacuation_time_minutes', 'status',
            'remarks', 'coordinator', 'notes'
        ]));

        $drill->buildings()->attach($request->building_ids);

        ActivityLog::log('fire_safety', 'Scheduled drill: ' . $drill->drill_type, [
            'unified_school_id' => $drill->unified_school_id,
            'notes' => "Date: {$drill->drill_date}",
        ]);

        return response()->json(['success' => true, 'message' => 'Drill scheduled successfully']);
    }

    public function getDrill($id)
    {
        $drill = FireSafetyEvacuationDrill::with('buildings')->findOrFail($id);
        return response()->json($drill);
    }

    public function cancelDrill($id)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot cancel drills.'], 403);
        }

        $drill = FireSafetyEvacuationDrill::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)$drill->unified_school_id !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this drill.'], 403);
        }
        $drill->update(['status' => 'cancelled']);

        ActivityLog::log('fire_safety', 'Cancelled drill: ' . $drill->drill_type, [
            'unified_school_id' => $drill->unified_school_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Drill cancelled successfully']);
    }

    public function getEvacuationSidebarStats($schoolId)
    {
        $school = School::withCount(['buildings', 'evacuationPlans'])->findOrFail($schoolId);
        $activePlans = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)->where('status', 'active')->count();
        $draftPlans = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)->where('status', 'draft')->count();

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
        $school = School::withCount(['buildings', 'evacuationPlans'])->findOrFail($schoolId);

        $activePlans = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)
            ->where('status', 'active')
            ->count();

        $draftPlans = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)
            ->where('status', 'draft')
            ->count();

        $buildingsWithPlansIds = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)->pluck('building_id');
        $buildingsWithoutPlans = FireSafetyBuilding::where('unified_school_id', $schoolId)
            ->whereNotIn('id', $buildingsWithPlansIds)
            ->count();

        // Safety score average
        $buildings = FireSafetyBuilding::where('unified_school_id', $schoolId)->with(['fireExtinguishers', 'alarmSystems'])->get();
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
        $drills = FireSafetyEvacuationDrill::where('unified_school_id', $schoolId)
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

        $query = School::with([
            'buildings.actualRooms',
            'buildings.alarmSystems',
            'buildings.fireExtinguishers.coveredRooms',
            'buildings.evacuationPlan'
        ])->withCount(['buildings', 'rooms', 'alarmSystems', 'extinguishers as fire_extinguishers_count']);

        if ($user->role === 'admin') {
            $schools = $query->get()->map(function($school) {
                return $this->calculateDetailedSchoolStatus($school);
            });

            $buildingTypes = SystemConfiguration::where('config_type', 'building_type')->orderBy('sort_order')->get()->unique('name');
            $alarmTypes = SystemConfiguration::where('config_type', 'alarm_type')->orderBy('id')->get()->unique('name');
            $alarmStatusesByType = SystemConfiguration::where('config_type', 'alarm_status')->get()->unique('name')->groupBy('parent_id');
            $extinguisherTypes = SystemConfiguration::where('config_type', 'extinguisher_type')->get()->unique('name');
            $extinguisherStatuses = SystemConfiguration::where('config_type', 'extinguisher_status')->get()->unique('name');
            $safetyFeatures = SystemConfiguration::where('config_type', 'safety_feature')->where('is_active', true)->orderBy('sort_order')->get()->unique('name');
            $calculatedPriorities = SystemConfiguration::where('config_type', 'calculated_priority')->orderBy('sort_order')->get()->unique('name');
            $roomTypes = SystemConfiguration::where('config_type', 'room_type')->orderBy('sort_order')->get()->unique('name');
            $inspectionChecklists = SystemConfiguration::where('config_type', 'inspection_checklist')->orderBy('sort_order')->get();
            $inspectionObservers = SystemConfiguration::where('config_type', 'inspection_observer')->orderBy('sort_order')->get();

            $directorySchoolsForFireRegistration = School::query()
                ->whereDoesntHave('specifics', function ($q) {
                    $q->where('module', 'fire_safety')->where('key', 'original_fire_safety_id');
                })
                ->orderBy('school_name')
                ->get(['id', 'school_name', 'school_id', 'school_id_number', 'address', 'school_head', 'drrm_coordinator']);

            return view('fire-safety.customization', compact(
                'schools', 'buildingTypes', 'alarmTypes', 'alarmStatusesByType',
                'extinguisherTypes', 'extinguisherStatuses', 'safetyFeatures',
                'calculatedPriorities', 'roomTypes', 'inspectionChecklists', 'inspectionObservers',
                'directorySchoolsForFireRegistration'
            ));
        } else {
            $school = $query->find($user->school_id);
            if ($school) {
                $school = $this->calculateDetailedSchoolStatus($school);
            }

            $schools = $school ? collect([$school]) : collect([]);
            $directorySchoolsForFireRegistration = collect();

            return view('fire-safety.customization', compact('schools', 'directorySchoolsForFireRegistration'));
        }
    }

    public function updateSchool(Request $request, $id = null)
    {
        $user = auth()->user();

        // Use route parameter or user's unified_school_id
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

        $school = School::findOrFail($schoolId);
        $school->update([
            'school_name' => $validated['school_name'],
            'address' => $validated['address'],
            'school_head' => $validated['school_head'],
            'drrm_coordinator' => $validated['school_drrm_coordinator'],
            'contact_number' => $validated['school_head_contact'] ?? $school->contact_number,
            'contact_number_2' => $validated['drrm_coordinator_contact'] ?? $school->contact_number_2,
        ]);

        ActivityLog::log('fire_safety', 'Updated school profile: ' . $school->school_name, [
            'school_id' => $school->id,
            'notes' => $request->input('remarks') ?: null,
        ]);

        return response()->json(['success' => true, 'message' => 'School updated successfully']);
    }

    // API endpoints for AJAX calls
    public function getSchoolDetails($id)
    {
        $school = School::withCount(['extinguishers', 'alarmSystems', 'buildings', 'evacuationPlans'])
            ->findOrFail($id);

        return response()->json([
            'id' => $school->id,
            'school_name' => $school->school_name,
            'school_id' => $school->school_id,
            'address' => $school->address,
            'school_head' => $school->school_head,
            'school_drrm_coordinator' => $school->drrm_coordinator,
            'status' => $school->fire_safety_status,
            'fire_extinguishers_count' => $school->extinguishers_count,
            'alarm_systems_count' => $school->alarm_systems_count,
            'evacuation_plans_count' => $school->evacuation_plans_count,
            'buildings_count' => $school->buildings_count,
        ]);
    }

    public function getSchoolIssues($id)
    {
        $school = School::with(['extinguishers', 'alarmSystems'])->findOrFail($id);
        $issues = [];

        // Check if school is unconfigured
        if ($school->fire_safety_status === 'unconfigured') {
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
            $existingSchoolCount = School::where('id', $user->school_id)->count();
            // If they already have a school assigned, or if they've already created one (in case unified_school_id is not yet set)
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
            'school_id' => 'required|string|max:50|unique:schools,school_id',
            'address' => 'required|string|max:500',
            'school_head' => 'required|string|max:255',
            'school_drrm_coordinator' => 'required|string|max:255',
            'status' => 'required|string',
        ]);

        $school = School::create([
            'school_name' => $validated['school_name'],
            'school_id' => $validated['school_id'],
            'school_id_number' => $validated['school_id'],
            'address' => $validated['address'],
            'school_head' => $validated['school_head'],
            'drrm_coordinator' => $validated['school_drrm_coordinator'],
            'fire_safety_status' => $validated['status'] ?? 'unconfigured',
        ]);

        SchoolSpecificsInformation::updateOrInsert(
            ['school_id' => $school->id, 'module' => 'fire_safety', 'key' => 'original_fire_safety_id'],
            ['value' => (string) $school->id, 'created_at' => now(), 'updated_at' => now()]
        );

        ActivityLog::log('fire_safety', 'Created school: ' . $school->school_name, [
            'school_id' => $school->id,
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
     * Link an existing main-directory school row to Fire Safety (no new school record).
     */
    public function registerSchoolFromDirectory(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'unified_school_id' => 'required|integer|exists:schools,id',
        ]);

        $school = School::findOrFail($validated['unified_school_id']);

        $exists = SchoolSpecificsInformation::where('school_id', $school->id)
            ->where('module', 'fire_safety')
            ->where('key', 'original_fire_safety_id')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This school is already registered for Fire Safety.',
            ], 422);
        }

        SchoolSpecificsInformation::updateOrInsert(
            ['school_id' => $school->id, 'module' => 'fire_safety', 'key' => 'original_fire_safety_id'],
            ['value' => (string) $school->id, 'created_at' => now(), 'updated_at' => now()]
        );

        if (empty($school->fire_safety_status)) {
            $school->fire_safety_status = 'unconfigured';
            $school->save();
        }

        ActivityLog::log('fire_safety', 'Registered school for Fire Safety (from directory): ' . $school->school_name, [
            'school_id' => $school->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School registered for Fire Safety.',
            'school' => $school->fresh(),
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
            $school = School::withCount(['buildings', 'alarmSystems', 'extinguishers as fire_extinguishers_count', 'evacuationPlans'])
                ->findOrFail($id);
            $schoolName = $school->school_name;

            // Capture FULL snapshot of school data before deletion
            $fullSchoolData = School::with([
                'buildings.actualRooms',
                'buildings.fireExtinguishers.coveredRooms',
                'buildings.alarmSystems',
                'buildings.alarmSystemsMany', // Fixed: Preload covering alarms
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
                'school_code' => $school->school_id ?? $school->school_id_number,
                'school_head' => $school->school_head,
                'drrm_coordinator' => $school->drrm_coordinator,
                'address' => $school->address,
                'buildings' => $school->buildings_count ?? 0,
                'alarm_systems' => $school->alarm_systems_count ?? 0,
                'extinguishers' => $school->fire_extinguishers_count ?? 0,
                'evacuation_plans' => $school->evacuation_plans_count ?? 0,
                'evacuation_coverage_status' => $school->coverageStatus,
            ];

            // Save to dedicated snapshots table for full data retention
            FireSafetySchoolSnapshot::create([
                'school_id_code' => (string) ($school->school_id ?? $school->school_id_number ?? $school->id),
                'school_name' => $school->school_name,
                'full_data' => $fullSchoolData->toArray(),
                'deleted_by' => $user->name,
                'reason' => $reason,
                'deleted_at' => now(),
            ]);

            // Also keep a record in general archives for the history log
            FireSafetyArchive::create([
                'unified_school_id' => null, // School will be gone
                'type' => 'school_deletion',
                'item_id' => $school->id,
                'item_code' => (string) ($school->school_id ?? $school->school_id_number ?? $school->id),
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

            ActivityLog::log('fire_safety', 'Deleted school: ' . $schoolName, [
                'unified_school_id' => (int) $id,
                'notes' => $reason,
            ]);

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

        // For contributors, always use their assigned school
        if (auth()->user()->role === 'contributor' && auth()->user()->school_id) {
            $request->merge(['unified_school_id' => (string) auth()->user()->school_id]);
        }

        $validated = $request->validate([
            'unified_school_id' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:danger,warning,info'
        ]);

        $user = auth()->user();
        if ($user->role === 'contributor') {
            if ($validated['unified_school_id'] === 'all') {
                return response()->json(['success' => false, 'message' => 'Contributors cannot send alerts to all schools.'], 403);
            }
            if ((int)$validated['unified_school_id'] !== (int)$user->school_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
            }
        }

        if ($validated['unified_school_id'] === 'all') {
            $schools = School::all();
            foreach ($schools as $school) {
                self::createFireSafetyNotification(
                    'alert',
                    'Alert: ' . $validated['title'],
                    $validated['description'] . ' (Posted by: ' . $user->name . ')',
                    $school->id,
                    null,
                    ['alert_type' => $validated['type'], 'posted_by' => $user->name]
                );
            }
            return response()->json(['success' => true, 'message' => 'Alert applied to all schools!']);
        }

        $school = School::findOrFail($validated['unified_school_id']);

        self::createFireSafetyNotification(
            'alert',
            'Alert: ' . $validated['title'],
            $validated['description'] . ' (Posted by: ' . $user->name . ')',
            $school->id,
            null,
            ['alert_type' => $validated['type'], 'posted_by' => $user->name]
        );

        ActivityLog::log('fire_safety', 'Posted alert: ' . $validated['title'], [
            'unified_school_id' => $school->id,
            'notes' => $validated['description'],
        ]);

        return response()->json(['success' => true, 'message' => 'Alert added successfully!']);
    }

    public function storeEvent(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot schedule events.'], 403);
        }

        // For contributors, always use their assigned school
        if (auth()->user()->role === 'contributor' && auth()->user()->school_id) {
            $request->merge(['unified_school_id' => (string) auth()->user()->school_id]);
        }

        $validated = $request->validate([
            'unified_school_id' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date',
            'time' => 'nullable'
        ]);

        $user = auth()->user();
        if ($user->role === 'contributor') {
            if ($validated['unified_school_id'] === 'all') {
                return response()->json(['success' => false, 'message' => 'Contributors cannot schedule events for all schools.'], 403);
            }
            if ((int)$validated['unified_school_id'] !== (int)$user->school_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
            }
        }

        if ($validated['unified_school_id'] === 'all') {
            $schools = School::all();
            foreach ($schools as $school) {
                self::createFireSafetyNotification(
                    'event',
                    'Event: ' . $validated['title'],
                    $validated['description'] . ' | Date: ' . $validated['date'] . ($validated['time'] ? ' at ' . $validated['time'] : '') . ' (Posted by: ' . $user->name . ')',
                    $school->id,
                    null,
                    ['event_date' => $validated['date'], 'event_time' => $validated['time'], 'posted_by' => $user->name]
                );
            }
            return response()->json(['success' => true, 'message' => 'Event scheduled for all schools!']);
        }

        $school = School::findOrFail($validated['unified_school_id']);

        self::createFireSafetyNotification(
            'event',
            'Event: ' . $validated['title'],
            $validated['description'] . ' | Date: ' . $validated['date'] . ($validated['time'] ? ' at ' . $validated['time'] : '') . ' (Posted by: ' . $user->name . ')',
            $school->id,
            null,
            ['event_date' => $validated['date'], 'event_time' => $validated['time'], 'posted_by' => $user->name]
        );

        ActivityLog::log('fire_safety', 'Scheduled event: ' . $validated['title'], [
            'unified_school_id' => $school->id,
            'notes' => $validated['description'] . " (Date: {$validated['date']})",
        ]);

        return response()->json(['success' => true, 'message' => 'Event added successfully!']);
    }

    public function checkAlarmCode($schoolId, $code)
    {
        $exists = FireSafetyAlarmSystem::where('unified_school_id', $schoolId)
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

        ActivityLog::log('fire_safety', 'Reordered configuration: ' . $type);

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
            $rules['required_extinguishers'] = 'required|integer|min:1|max:5';
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
            $data['required_extinguishers'] = (int) $validated['required_extinguishers'];
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

        ActivityLog::log('fire_safety', "Created configuration ($configType): " . $config->name);

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
            $rules['required_extinguishers'] = 'required|integer|min:1|max:5';
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
            'is_active' => $request->has('is_active') ? ($request->is_active == 'on' || $request->is_active == '1' || $request->is_active == 'true') : false,
            'color_class' => $request->input('color_class') ?? $config->color_class,
            'code' => $request->input('code') ?? $config->code,
        ];
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
            $update['required_extinguishers'] = (int) $validated['required_extinguishers'];
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

        ActivityLog::log('fire_safety', "Updated configuration ($configType): " . $config->name);

        return response()->json(['success' => true, 'message' => 'Configuration updated successfully']);
    }

    public function deleteConfig($type, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $config = SystemConfiguration::where('id', $id)
            ->where('config_type', str_replace('-', '_', $type))
            ->first();

        $name = $config ? $config->name : 'Unknown';

        if ($config) {
            $config->delete();
            ActivityLog::log('fire_safety', "Deleted configuration ($type): " . $name);
        }

        return response()->json(['success' => true, 'message' => 'Configuration deleted successfully']);
    }

    private function fireSafetyBackupTables(): array
    {
        return [
            // Core data
            'schools',
            'school_specifics_information',
            'firesafety_buildings',
            'fire_safety_rooms',
            'firesafety_alarm_systems',
            'firesafety_fire_extinguishers',
            'firesafety_evacuationplans',
            // Related
            'fire_safety_inspections',
            'fire_safety_evacuation_drills',
            'fire_safety_drill_building',
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

        ActivityLog::log('fire_safety', 'Created backup: ' . $fileName);

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

        ActivityLog::log('fire_safety', 'Restored backup: ' . $fileName, [
            'notes' => 'Full data restore from backup file',
        ]);

        return response()->json(['success' => true]);
    }

    public function exportSchools()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $schools = School::with([
            'buildings.actualRooms',
            'buildings.alarmSystems',
            'buildings.fireExtinguishers.coveredRooms',
            'buildings.evacuationPlan'
        ])->get()->map(function($school) {
            return $this->calculateDetailedSchoolStatus($school);
        });

        $filename = "schools_export_" . date('Y-m-d') . ".csv";
        $handle = fopen('php://output', 'w');

        // Add CSV header (Remove ID, rename Last Updated to Last Inspection)
        fputcsv($handle, ['School ID', 'Name', 'Address', 'Head', 'DRRM Coordinator', 'Status', 'Last Inspection']);

        foreach ($schools as $school) {
            $statusText = 'Unconfigured';
            if ($school->status === 'passed') $statusText = 'Passed';
            if ($school->status === 'warning') $statusText = 'Ongoing Improvement';

            fputcsv($handle, [
                $school->unified_school_id,
                $school->school_name,
                $school->address,
                $school->school_head,
                $school->school_drrm_coordinator,
                $statusText,
                $school->last_inspection_date ? \Carbon\Carbon::parse($school->last_inspection_date)->format('M d, Y') : 'Never'
            ]);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fclose($handle);
        exit;
    }
    public function getBuildingHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('unified_school_id', $schoolId)
            ->whereIn('type', ['floor', 'room'])
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    public function getRemovedBuildingsHistory($schoolId)
    {
        $archives = FireSafetyArchive::where('unified_school_id', $schoolId)
            ->where('type', 'building')
            ->orderBy('removed_at', 'desc')
            ->get();

        return response()->json($archives);
    }

    // MAP: Get Full Data for School Map
    public function getSchoolMapData($schoolId)
    {
        $school = School::with([
            'buildings.actualRooms.roomTypeConfig',
            'buildings.alarmSystems',
            'buildings.alarmSystemsMany',
            'buildings.fireExtinguishers',
            'buildings.evacuationPlan',
            'facilities'
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

        $school = School::findOrFail($schoolId);
        $school->evacuation_map_layout = $request->layout;
        $school->save();

        ActivityLog::log('fire_safety', 'Updated evacuation map layout', [
            'unified_school_id' => $school->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Map layout saved successfully!']);
    }

    public function decrementSchoolGates(Request $request, $schoolId)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot delete gates.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int) $schoolId !== (int) auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        if (!Schema::hasColumn('schools', 'number_gates')) {
            return response()->json(['success' => false, 'message' => 'Gate count column is not available on this environment.'], 422);
        }

        $validated = $request->validate([
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        $decrementBy = (int) ($validated['count'] ?? 1);
        $school = School::findOrFail((int) $schoolId);
        $current = (int) ($school->number_gates ?? 0);
        $school->number_gates = max(0, $current - $decrementBy);
        $school->save();

        ActivityLog::log('fire_safety', 'Reduced school gate count from evacuation map', [
            'unified_school_id' => $school->id,
            'notes' => 'Updated number_gates to ' . $school->number_gates,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gate count updated successfully.',
            'number_gates' => (int) $school->number_gates,
        ]);
    }

    public function storeSharedFacility(Request $request)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot add facilities.'], 403);
        }

        $validated = $request->validate([
            'school_id' => 'required|integer|exists:schools,id',
            'type' => 'nullable|string|in:commercial,industrial,residential,educational,public/institutional,assembly_area',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'required|string|in:excellent,good,fair,poor,critical',
            'remarks' => 'nullable|string',
        ]);

        if (auth()->user()->role !== 'admin' && (int) $validated['school_id'] !== (int) auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $facility = ComprehensiveFacility::create([
            'school_id' => (int) $validated['school_id'],
            'type' => $validated['type'] ?? 'public/institutional',
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'condition' => $validated['condition'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Facility saved successfully.',
            'facility' => $facility,
        ]);
    }

    public function updateSharedFacility(Request $request, $facilityId)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot update facilities.'], 403);
        }

        $facility = ComprehensiveFacility::findOrFail((int) $facilityId);
        if (auth()->user()->role !== 'admin' && (int) $facility->school_id !== (int) auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this facility.'], 403);
        }

        $validated = $request->validate([
            'type' => 'nullable|string|in:commercial,industrial,residential,educational,public/institutional,assembly_area',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'required|string|in:excellent,good,fair,poor,critical',
            'remarks' => 'nullable|string',
        ]);

        $facility->update([
            'type' => $validated['type'] ?? ($facility->type ?? 'public/institutional'),
            'name' => $validated['name'],
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $facility->description,
            'condition' => $validated['condition'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Facility updated successfully.',
            'facility' => $facility,
        ]);
    }

    public function deleteSharedFacility($facilityId)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot delete facilities.'], 403);
        }

        $facility = ComprehensiveFacility::findOrFail((int) $facilityId);
        if (auth()->user()->role !== 'admin' && (int) $facility->school_id !== (int) auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this facility.'], 403);
        }

        $facility->delete();
        return response()->json(['success' => true, 'message' => 'Facility deleted successfully.']);
    }

    private function syncAssemblyAreaFacilities(int $schoolId, ?string $primaryArea, ?string $secondaryArea): void
    {
        $upsertAssembly = function (string $marker, ?string $name) use ($schoolId): void {
            $trimmedName = trim((string) $name);
            $description = $marker . ' (from Fire Safety Evacuation Plan)';

            if ($trimmedName === '') {
                ComprehensiveFacility::query()
                    ->where('school_id', $schoolId)
                    ->where('type', 'assembly_area')
                    ->where('description', $description)
                    ->delete();
                return;
            }

            $facility = ComprehensiveFacility::query()->firstOrNew([
                'school_id' => $schoolId,
                'type' => 'assembly_area',
                'description' => $description,
            ]);

            $facility->name = $trimmedName;
            if (!$facility->exists) {
                $facility->condition = 'good';
            }
            $facility->save();
        };

        $upsertAssembly('Primary Assembly Area', $primaryArea);
        $upsertAssembly('Secondary Assembly Area', $secondaryArea);
    }

    public function notifyMapUpdate(Request $request, $schoolId)
    {
        if (auth()->user()->role === 'viewer') {
            return response()->json(['success' => false, 'message' => 'Viewers cannot send notifications.'], 403);
        }

        if (auth()->user()->role !== 'admin' && (int)$schoolId !== (int)auth()->user()->school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this school.'], 403);
        }

        $request->validate([
            'description' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        $school = School::findOrFail($schoolId);

        self::createFireSafetyNotification(
            'evacuation_plan',
            'Evacuation Map Updated: ' . $school->school_name,
            $user->name . ' updated the evacuation map layout. Details: ' . $request->description,
            $school->id,
            null,
            ['plan_type' => 'map', 'posted_by' => $user->name]
        );

        ActivityLog::log('fire_safety', 'Sent map update notification', [
            'unified_school_id' => $school->id,
            'notes' => $request->description,
        ]);

        return response()->json(['success' => true, 'message' => 'Administrator has been notified about the map update.']);
    }

    // --- Report Printing Methods ---

    public function printSchoolSummary()
    {
        $schools = School::with([
            'buildings.actualRooms',
            'buildings.fireExtinguishers',
            'buildings.alarmSystems'
        ])->get();

        return view('fire-safety.reports.school-summary', compact('schools'));
    }

    public function printBuildingSummary($schoolId)
    {
        $school = School::with([
            'buildings.actualRooms.roomTypeConfig',
            'buildings.fireExtinguishers',
            'buildings.alarmSystems',
            'buildings.alarmSystemsMany'
        ])->findOrFail($schoolId);

        return view('fire-safety.reports.building-summary', compact('school'));
    }

    public function printAlarmDetails($schoolId)
    {
        $school = School::findOrFail($schoolId);
        $alarms = FireSafetyAlarmSystem::where('unified_school_id', $schoolId)
            ->with(['buildings', 'building'])
            ->get();

        return view('fire-safety.reports.alarm-details', compact('school', 'alarms'));
    }

    public function printExtinguisherDetails($schoolId)
    {
        $school = School::with('buildings')->findOrFail($schoolId);
        $extinguishers = FireSafetyExtinguisher::where('unified_school_id', $schoolId)
            ->with(['building', 'centerRoom', 'coveredRooms'])
            ->get()
            ->sortBy('code', SORT_NATURAL)
            ->values();

        $rooms = FireSafetyRoom::where('unified_school_id', $schoolId)
            ->with(['building', 'roomTypeConfig', 'extinguishersCoveringThisRoom', 'hostedExtinguisher'])
            ->orderBy('building_id')
            ->orderBy('floor_no')
            ->orderBy('room_name')
            ->get();

        $neededExtinguishers = (int) $school->buildings->sum(function ($building) {
            return (int) $building->requiredExtinguishersCount;
        });
        $existingExtinguishers = (int) $extinguishers->count();
        $passedExtinguishers = (int) $extinguishers->filter(function ($ext) {
            return strtolower((string) $ext->evaluation_result) === 'passed';
        })->count();

        return view('fire-safety.reports.extinguisher-details', compact(
            'school',
            'extinguishers',
            'rooms',
            'neededExtinguishers',
            'existingExtinguishers',
            'passedExtinguishers'
        ));
    }

    public function printEvacuationPlans(Request $request, $schoolId)
    {
        $school = School::findOrFail($schoolId);

        // School-wide plan (building_id is null)
        $schoolPlan = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)
            ->whereNull('building_id')
            ->first();

        // Building-level plans
        $buildingPlans = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)
            ->whereNotNull('building_id')
            ->with(['building'])
            ->get();

        // Legacy: also support filtered queries
        $query = FireSafetyEvacuationPlan::where('unified_school_id', $schoolId)->with(['building']);
        if ($request->has('building_id')) {
            $query->where('building_id', $request->building_id);
        }
        if ($request->has('plan_id')) {
            $query->where('id', $request->plan_id);
        }
        $plans = $query->get();

        return view('fire-safety.reports.evacuation-plans', compact('school', 'plans', 'schoolPlan', 'buildingPlans'));
    }

    public function printFullSchoolReport($schoolId)
    {
        $school = School::with([
            'buildings.actualRooms.roomTypeConfig',
            'buildings.alarmSystems',
            'buildings.alarmSystemsMany',
            'buildings.fireExtinguishers.coveredRooms',
            'buildings.evacuationPlan',
            'schoolEvacuationPlan',
        ])->findOrFail($schoolId);

        $user = auth()->user();
        if ($user->role !== 'admin' && (int) $user->school_id !== (int) $school->id) {
            abort(403, 'Unauthorized access to this school report.');
        }

        $alarms = FireSafetyAlarmSystem::query()
            ->where('unified_school_id', $school->id)
            ->with(['buildings', 'building'])
            ->orderBy('code')
            ->get();

        $extinguishers = FireSafetyExtinguisher::query()
            ->where('unified_school_id', $school->id)
            ->with(['building', 'centerRoom', 'coveredRooms'])
            ->orderBy('code')
            ->get();

        $rooms = FireSafetyRoom::query()
            ->where('unified_school_id', $school->id)
            ->with(['building', 'roomTypeConfig'])
            ->orderBy('building_id')
            ->orderBy('floor_no')
            ->orderBy('room_name')
            ->get();

        $buildingPlans = FireSafetyEvacuationPlan::query()
            ->where('unified_school_id', $school->id)
            ->whereNotNull('building_id')
            ->with('building')
            ->orderBy('building_id')
            ->get();

        $schoolPlan = FireSafetyEvacuationPlan::query()
            ->where('unified_school_id', $school->id)
            ->whereNull('building_id')
            ->first();

        $mapLayout = $school->evacuation_map_layout;

        ActivityLog::log('fire_safety', 'Printed full school report: ' . $school->school_name, [
            'unified_school_id' => $school->id,
        ]);

        return view('fire-safety.reports.full-school-report', compact(
            'school',
            'alarms',
            'extinguishers',
            'rooms',
            'buildingPlans',
            'schoolPlan',
            'mapLayout'
        ));
    }

    public function getNotifications()
    {
        $user = auth()->user();

        // Auto-generate alarm-due notifications for today's scheduled alarms
        $this->generateAlarmDueNotifications($user);

        // Fetch notifications from the notifications table (including alerts & events now)
        $query = FireSafetyNotification::forCompliance('fire_safety')
            ->with('school')
            ->orderBy('created_at', 'desc');

        if ($user->role !== 'admin') {
            // Contributors see: their own notifications + admin-created notifications for their school + global notifications
            $query->where(function($q) use ($user) {
                $q->where('unified_school_id', $user->school_id)
                  ->orWhereNull('unified_school_id');
            });
        }

        $notifications = $query->limit(20)->get()->map(function($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'action_type' => $n->action_type,
                'action_url' => $n->action_url,
                'action_data' => $n->action_data,
                'is_read' => $n->is_read,
                'unified_school_id' => $n->unified_school_id,
                'school_name' => $n->school ? $n->school->school_name : null,
                'created_at' => $n->created_at->toDateTimeString(),
                'time_ago' => $n->created_at->diffForHumans(),
            ];
        });

        $unreadCount = FireSafetyNotification::forCompliance('fire_safety')
            ->unread();

        if ($user->role !== 'admin') {
            $unreadCount->where(function($q) use ($user) {
                $q->where('unified_school_id', $user->school_id)
                  ->orWhereNull('unified_school_id');
            });
        }

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount->count(),
        ]);
    }

    public function notificationsPage(Request $request)
    {
        $user = auth()->user();

        // Get schools for the layout
        $schoolQuery = School::query();
        if ($user->role !== 'admin') {
            $schoolQuery->where('id', $user->school_id);
        }
        $schools = $schoolQuery->get();
        $activeSchool = $this->getActiveSchool($schools);

        // Get all schools for filter dropdown (admin gets all, contributor gets own)
        $filterSchools = $schools;

        // School filter from query parameter
        $filterSchoolId = $request->query('unified_school_id');

        // Get all notifications (now including alerts & events)
        $query = FireSafetyNotification::forCompliance('fire_safety')
            ->with(['school', 'user'])
            ->orderBy('created_at', 'desc');

        if ($user->role !== 'admin') {
            // Contributors see: notifications for their school + global notifications
            $query->where(function($q) use ($user) {
                $q->where('unified_school_id', $user->school_id)
                  ->orWhereNull('unified_school_id');
            });
        }

        // Apply school filter if selected
        if ($filterSchoolId && $filterSchoolId !== 'all') {
            $query->where('unified_school_id', $filterSchoolId);
        }

        $notifications = $query->paginate(20)->appends($request->query());

        return view('fire-safety.notifications', compact('schools', 'activeSchool', 'notifications', 'filterSchools', 'filterSchoolId'));
    }

    public function markNotificationRead($id)
    {
        $notification = FireSafetyNotification::findOrFail($id);
        $user = auth()->user();

        // Authorization check
        if ($user->role !== 'admin' && $notification->unified_school_id && (int)$user->school_id !== (int)$notification->unified_school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        $user = auth()->user();

        $query = FireSafetyNotification::forCompliance('fire_safety')
            ->unread();

        if ($user->role !== 'admin') {
            $query->where(function($q) use ($user) {
                $q->where('unified_school_id', $user->school_id)
                  ->orWhereNull('unified_school_id');
            });
        }

        $query->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Create a fire safety notification
     */
    public static function createFireSafetyNotification($type, $title, $message, $schoolId = null, $actionType = null, $actionData = null)
    {
        // Duplicate check: same type, title, school, and action_data within the last 10 seconds
        $recent = FireSafetyNotification::where('compliance_type', 'fire_safety')
            ->where('type', $type)
            ->where('title', $title)
            ->where('school_id', $schoolId)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->exists();

        if ($recent) {
            return null; // Skip duplicate
        }

        return FireSafetyNotification::create([
            'compliance_type' => 'fire_safety',
            'module' => 'fire_safety',
            'school_id' => $schoolId,
            'user_id' => auth()->id(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_type' => $actionType,
            'action_url' => null,
            'action_data' => $actionData,
            'is_read' => false,
        ]);
    }

    /**
     * Auto-generate alarm-due notifications for alarms with next_test_due today
     */
    private function generateAlarmDueNotifications($user)
    {
        $today = Carbon::today()->toDateString();

        $alarmQuery = FireSafetyAlarmSystem::whereDate('next_test_due', $today)
            ->where('status', 'active');

        if ($user->role !== 'admin') {
            $alarmQuery->where('unified_school_id', $user->school_id);
        }

        $alarms = $alarmQuery->get();

        foreach ($alarms as $alarm) {
            // Check if we already created a notification for this alarm today
            $exists = FireSafetyNotification::where('compliance_type', 'fire_safety')
                ->where('type', 'alarm_due')
                ->where('action_data->alarm_id', $alarm->id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$exists) {
                FireSafetyNotification::create([
                    'compliance_type' => 'fire_safety',
                    'module' => 'fire_safety',
                    'school_id' => $alarm->unified_school_id,
                    'user_id' => null,
                    'type' => 'alarm_due',
                    'title' => 'Alarm Test Due Today: ' . $alarm->code,
                    'message' => 'Alarm ' . $alarm->code . ' is scheduled for testing today.',
                    'action_type' => 'go_test',
                    'action_data' => ['alarm_id' => $alarm->id, 'unified_school_id' => $alarm->unified_school_id],
                    'is_read' => false,
                ]);
            }
        }
    }

    public function replyToNotification(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|string',
            'unified_school_id' => 'required|exists:schools,id',
            'message' => 'required|string|max:1000'
        ]);

        $school = School::findOrFail($validated['unified_school_id']);

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

        ActivityLog::log('fire_safety', 'Replied to notification', [
            'unified_school_id' => $school->id,
            'notes' => $validated['message'],
        ]);

        return response()->json(['success' => true, 'message' => 'Reply sent successfully!']);
    }
    public function uploadMap(Request $request, $id)
    {
        $school = School::findOrFail($id);

        if (auth()->user()->role !== 'admin' && (int)auth()->user()->school_id !== (int)$school->id) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'evacuation_map' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('evacuation_map')) {
            if ($school->attached_evacuation_map) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($school->attached_evacuation_map);
            }
            $path = $request->file('evacuation_map')->store('evacuation_maps', 'public');
            $school->attached_evacuation_map = $path;
            $school->save();

            \App\Models\ActivityLog::log('fire_safety', 'Uploaded Custom Evacuation Map', [
                'unified_school_id' => $school->id,
            ]);

            return redirect()->back()->with('success', 'Evacuation Map uploaded successfully.');
        }

        return redirect()->back()->with('error', 'Failed to upload image.');
    }


public function getRoomsForBuilding($building_id)
{
    // Laravel queries your database (visible in phpMyAdmin)
    $rooms = DB::table('fire_safety_rooms') // or whatever your rooms table is named
               ->where('building_id', $building_id)
               ->get();

    return response()->json($rooms);
}
}
