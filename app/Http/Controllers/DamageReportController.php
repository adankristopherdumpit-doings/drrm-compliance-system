<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DmgAssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Assuming you'll add a module access check for damage_assessment later
        // $this->middleware('module.access:damage_assessment');
    }

    /**
     * Display the damage assessment dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->input('school_id');

        if ($user->role === 'admin') {
            $schools = School::orderBy('school_name')->get();
            $activeSchool = $schoolId ? School::find($schoolId) : $schools->first();
        } else {
            // For contributors, they can only see their assigned school
            $activeSchool = School::find($user->school_id);
            $schools = collect([$activeSchool]); // Limit schools dropdown to only their school
        }

        if (!$activeSchool) {
            // If no school is assigned or found, redirect to dashboard with an error
            return redirect()->route('dashboard')->with('error', 'No school context available for Damage Assessment.');
        }

        // Fetch damage assessments for the active school
        $damageReport = DamageReport::where('school_id', $activeSchool->id)
            ->orderBy('inspection_date', 'desc') // Assuming 'inspection_date' exists in DamageReport model
            ->paginate(15);

        // Calculate some basic stats
        $stats = [
            'total_assessments' => DamageReport::where('school_id', $activeSchool->id)->count(),
            'completed_assessments' => DamageReport::where('school_id', $activeSchool->id)
                ->where('status', 'completed')
                ->count(),
            // Add more stats as needed, e.g., average damage score, number of critical findings
        ];

        return view('DamageReport.Dashboard', compact(
            'schools', 'activeSchool', 'damageReport', 'stats'
        ));
    }
}