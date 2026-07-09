<?php

namespace App\Http\Controllers;

use App\Models\DrillMonitoring;
use App\Models\School;
use App\Models\FireSafetyEvacuationDrill;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DrillMonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the drill monitoring dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->input('school_id');

        if ($user->role === 'admin') {
            $schools = School::orderBy('school_name')->get();
            $activeSchool = $schoolId ? School::find($schoolId) : $schools->first();
        } else {
            $activeSchool = School::find($user->school_id);
            $schools = collect([$activeSchool]);
        }

        if (!$activeSchool) {
            return redirect()->route('dashboard')->with('error', 'No school context available.');
        }

        $monitorings = DrillMonitoring::where('unified_school_id', $activeSchool->id)
            ->with('scheduledDrill')
            ->orderBy('inspection_date', 'desc')
            ->paginate(15);

        $upcomingDrills = DrillMonitoring::where('unified_school_id', $activeSchool->id)
            ->where('inspection_date', '>', Carbon::today())
            ->orderBy('inspection_date', 'asc')
            ->get();

        $stats = [
            'total_monitored' => DrillMonitoring::where('unified_school_id', $activeSchool->id)->count(),
            'avg_participants' => DrillMonitoring::where('unified_school_id', $activeSchool->id)
                ->selectRaw('AVG(no_of_students + no_of_personnel) as avg')
                ->value('avg') ?? 0
        ];

        return view('DrillMonitoring.dashboard', compact('schools', 'activeSchool', 'monitorings', 'stats', 'upcomingDrills'));
    }

    /**
     * Store a new monitoring record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unified_school_id' => 'required|exists:schools,id',
            'drill_id'          => 'nullable|exists:fire_safety_evacuation_drills,id',
            'drill_type'        => 'required|string',
            'inspection_date'   => 'required|date',
            'no_of_students'    => 'required|integer|min:0',
            'no_of_personnel'   => 'nullable|integer|min:0',
            'monitored_by'      => 'required|string|max:255',
            'coordinator_name'  => 'required|string|max:255',
            'status'            => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $request->all();
        if (!isset($data['status'])) {
            $data['status'] = 'Completed';
        }

        $monitoring = DrillMonitoring::create($data);

        ActivityLog::log('drill_monitoring', 'Recorded drill monitoring: ' . $monitoring->drill_type, [
            'school_id' => $monitoring->unified_school_id,
            'notes'     => $monitoring->remarks ?? null
        ]);

        return response()->json(['success' => true, 'message' => 'Monitoring record saved successfully.', 'data' => $monitoring]);
    }

    /**
     * Show specific monitoring record details.
     */
    public function show($id)
    {
        $monitoring = DrillMonitoring::with(['school', 'scheduledDrill'])->findOrFail($id);
        return response()->json($monitoring);
    }

    /**
     * Update an existing monitoring record.
     */
    public function update(Request $request, $id)
    {
        $monitoring = DrillMonitoring::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'drill_type'        => 'required|string',
            'inspection_date'   => 'required|date',
            'inspection_time'   => 'required',
            'no_of_students'    => 'required|integer|min:0',
            'no_of_personnel'   => 'nullable|integer|min:0',
            'monitored_by'      => 'required|string|max:255',
            'coordinator_name'  => 'required|string|max:255',
            'status'            => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $request->all();
        $monitoring->update($data);

        ActivityLog::log('drill_monitoring', 'Updated drill monitoring: ' . $monitoring->drill_type, [
            'school_id' => $monitoring->unified_school_id,
            'notes'     => $monitoring->remarks ?? null
        ]);

        return response()->json(['success' => true, 'message' => 'Monitoring record updated successfully.', 'data' => $monitoring]);
    }

    /**
     * Remove a monitoring record.
     */
    public function destroy($id)
    {
        $monitoring = DrillMonitoring::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'admin' && (int)$user->school_id !== (int)$monitoring->unified_school_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        ActivityLog::log('drill_monitoring', 'Deleted drill monitoring: ' . $monitoring->drill_type, [
            'school_id' => $monitoring->unified_school_id
        ]);

        $monitoring->delete();
        return response()->json(['success' => true, 'message' => 'Monitoring record deleted.']);
    }

    /**
     * Display the drill monitoring notifications.
     */
    public function notifications(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->input('school_id');

        if ($user->role === 'admin') {
            $schools = School::orderBy('school_name')->get();
            $activeSchool = $schoolId ? School::find($schoolId) : $schools->first();
        } else {
            $activeSchool = School::find($user->school_id);
            $schools = collect([$activeSchool]);
        }

        if (!$activeSchool) {
            return redirect()->route('dashboard')->with('error', 'No school context available.');
        }

        $announcements = Announcement::where('is_active', true)->latest()->get();
        
        $upcomingDrills = FireSafetyEvacuationDrill::where('unified_school_id', $activeSchool->id)
            ->where('drill_date', '>=', Carbon::today())
            ->where('status', 'scheduled')
            ->get();

        return view('DrillMonitoring.notifications', compact('schools', 'activeSchool', 'announcements', 'upcomingDrills'));
    }

    /**
     * Print the specific drill monitoring tool report.
     */
    public function printInspection($id)
    {
        $inspection = DrillMonitoring::with(['school'])->findOrFail($id);

        $observers = SystemConfiguration::where('config_type', 'inspection_observer')
            ->where('is_active', true)
            ->get();

        return view('DrillMonitoring.print.monitoring-tool', compact('inspection', 'observers'));
    }

    }
