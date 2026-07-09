<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Announcement;
use App\Models\School;
use App\Models\SchoolSpecificsInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use App\Services\SchoolPermanentDeletionService;

class DashboardController extends Controller
{
    private function resolveComprehensiveSchoolIdFromFireSafetyId(?int $schoolId): ?int
    {
        return $schoolId ? (int) $schoolId : null;
    }

    private function resolveFireSafetySchoolIdFromComprehensiveSchoolId(?int $schoolId): ?int
    {
        return $schoolId ? (int) $schoolId : null;
    }

    private function registerSchoolAcrossModules(School $school): void
    {
        $registrations = [
            ['module' => 'fire_safety', 'key' => 'original_fire_safety_id'],
            ['module' => 'typhoon_flood', 'key' => 'original_evacuation_center_id'],
            ['module' => 'incident', 'key' => 'original_incident_school_id'],
            ['module' => 'comprehensive', 'key' => 'original_cmpr_school_id'],
        ];

        foreach ($registrations as $registration) {
            SchoolSpecificsInformation::updateOrInsert(
                [
                    'school_id' => $school->id,
                    'module' => $registration['module'],
                    'key' => $registration['key'],
                ],
                [
                    'value' => (string) $school->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $schoolUserCandidates = User::query()
            ->whereIn('role', ['contributor', 'viewer'])
            ->with(['school', 'typhoonSchool', 'incidentSchool'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'position', 'school_id', 'typhoon_school_id', 'incident_school_id']);
        $unassignedSchoolUserCandidates = User::query()
            ->whereIn('role', ['contributor', 'viewer'])
            ->whereNull('school_id')
            ->whereNull('typhoon_school_id')
            ->whereNull('incident_school_id')
            ->where(function ($query) {
                $query->where('needs_fs_registration', false)
                    ->orWhere('needs_fs_registration', 0)
                    ->orWhereNull('needs_fs_registration');
            })
            ->where(function ($query) {
                $query->where('needs_tf_registration', false)
                    ->orWhere('needs_tf_registration', 0)
                    ->orWhereNull('needs_tf_registration');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'position']);

        $contributorAssignedSchoolId = null;
        if ($user->role === 'contributor') {
            $contributorAssignedSchoolId = $user->school_id
                ?: ($user->typhoon_school_id ?: $user->incident_school_id);
        }
        $contributorAssignedSchool = $contributorAssignedSchoolId
            ? School::find((int) $contributorAssignedSchoolId)
            : null;
        $contributorSchoolAccountUsers = collect();
        if ($contributorAssignedSchool) {
            $contributorSchoolAccountUsers = User::query()
                ->where('school_id', $contributorAssignedSchool->id)
                ->where('position', 'School Account')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'position']);
        }

        $schools = School::orderBy('school_name')->get();
        if ($user->role === 'contributor' && $user->school_id) {
            $schools = School::where('id', $user->school_id)->get();
        }

        $allSchools = [];
        if ($isAdmin) {
            $allSchools = School::orderBy('school_name')->get();
        }

        // --- NEW OVERALL SYSTEM METRICS ---
        $totalSchoolsCount = School::count();
        $totalUsersCount = User::count();

        // 1. Fire Safety Compliance %
        $fsPassed = School::where('fire_safety_status', 'passed')->count();
        $fsRate = $totalSchoolsCount > 0 ? ($fsPassed / $totalSchoolsCount) * 100 : 0;

        // 2. Typhoon Monitoring %
        $tfOperating = School::where('evacuation_status', 'operating')->count();
        $tfRate = $totalSchoolsCount > 0 ? ($tfOperating / $totalSchoolsCount) * 100 : 0;

        // 3. Incident Checklist %
        $icLogs = School::where('incident_count', '>', 0)->count();
        $icRate = $totalSchoolsCount > 0 ? ($icLogs / $totalSchoolsCount) * 100 : 0;

        // 4. CSS Assessment %
        $cssPassed = School::whereHas('assessments', function($q) {
            $q->where('status', 'completed');
        })->count();
        $cssRate = $totalSchoolsCount > 0 ? ($cssPassed / $totalSchoolsCount) * 100 : 0;

        // 5. Drill Monitoring %
        $dmMonitored = School::whereHas('fireSafetyDrills', function($q) {
            $q->where('status', 'completed');
        })->count();
        $dmRate = $totalSchoolsCount > 0 ? ($dmMonitored / $totalSchoolsCount) * 100 : 0;

        // 6. Hazard Mapping %
        $hmConfigured = School::whereHas('fireSafetyBuildings')->count();
        $hmRate = $totalSchoolsCount > 0 ? ($hmConfigured / $totalSchoolsCount) * 100 : 0;

        // 7. Damage Report %
        $daCompleted = School::whereHas('damageReport', function($q) {
            $q->where('status', 'completed');
        })->count();
        $daRate = $totalSchoolsCount > 0 ? ($daCompleted / $totalSchoolsCount) * 100 : 0;

        $overallComplianceRate = round(($fsRate + $tfRate + $icRate + $cssRate + $dmRate + $hmRate + $daRate) / 7, 1);

        // 4th Metric: Total Population (Students + Personnel)
        $totalPopulationCount = School::sum('number_students') + School::sum('number_personnel');
        // --- END METRICS ---

        $announcements = Announcement::where('is_active', true)->latest()->get();
        return view('dashboard', compact(
            'schools', 
            'announcements', 
            'allSchools', 
            'isAdmin', 
            'contributorAssignedSchool', 
            'contributorSchoolAccountUsers', 
            'schoolUserCandidates', 
            'unassignedSchoolUserCandidates',
            'totalSchoolsCount',
            'totalUsersCount',
            'overallComplianceRate',
            'totalPopulationCount'
        ));
    }

    public function updateAssignedSchool(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'contributor') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $assignedSchoolId = $user->school_id ?: ($user->typhoon_school_id ?: $user->incident_school_id);
        if (!$assignedSchoolId) {
            return response()->json(['success' => false, 'message' => 'No assigned school found for this account.'], 422);
        }

        $school = School::findOrFail((int) $assignedSchoolId);

        $rules = [
            'school_name' => 'required|string|max:255|unique:schools,school_name,' . $school->id,
            'school_id' => 'nullable|string|max:255',
            'address' => 'required|string',
            'school_head' => 'nullable|string|max:255',
            'drrm_coordinator' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'contact_number_2' => 'nullable|string|max:255',
            'number_students' => 'nullable|integer|min:0',
            'number_personnel' => 'nullable|integer|min:0',
            'emergency_resources' => 'nullable|string',
        ];

        if (Schema::hasColumn('schools', 'number_gates')) {
            $rules['number_gates'] = 'nullable|integer|min:0';
        }
        if (Schema::hasColumn('schools', 'engineer_last_inspection_date')) {
            $rules['engineer_last_inspection_date'] = 'nullable|date';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $payload = $request->only([
            'school_name',
            'school_id',
            'address',
            'school_head',
            'drrm_coordinator',
            'district',
            'division',
            'region',
            'contact_number',
            'contact_number_2',
            'number_students',
            'number_personnel',
            'number_gates',
            'engineer_last_inspection_date',
            'emergency_resources',
        ]);

        if (array_key_exists('school_head', $payload) && trim((string) $payload['school_head']) === '') {
            unset($payload['school_head']);
        }
        if (array_key_exists('drrm_coordinator', $payload) && trim((string) $payload['drrm_coordinator']) === '') {
            unset($payload['drrm_coordinator']);
        }
        unset($payload['school_head_user_id'], $payload['drrm_coordinator_user_id']);

        if (!Schema::hasColumn('schools', 'number_gates')) {
            unset($payload['number_gates']);
        }
        if (!Schema::hasColumn('schools', 'engineer_last_inspection_date')) {
            unset($payload['engineer_last_inspection_date']);
        }

        $school->update($payload);

        return response()->json(['success' => true, 'message' => 'Assigned school information updated successfully.']);
    }

    private function syncSchoolRoleAssignment(School $school, ?int $userId, string $position): ?User
    {
        if (!$userId) {
            return null;
        }

        $selectedUser = User::query()
            ->whereIn('role', ['contributor', 'viewer'])
            ->findOrFail($userId);

        $linkedSchoolIds = collect([
            $selectedUser->school_id,
            $selectedUser->typhoon_school_id,
            $selectedUser->incident_school_id,
        ])->filter()->map(fn ($value) => (int) $value)->unique()->values();

        if ($linkedSchoolIds->isNotEmpty() && !$linkedSchoolIds->every(fn (int $linkedSchoolId) => $linkedSchoolId === (int) $school->id)) {
            throw new \RuntimeException('Selected user is already assigned to another school.');
        }

        User::query()
            ->where('school_id', $school->id)
            ->where('id', '!=', $selectedUser->id)
            ->update(['school_id' => null, 'position' => null]);

        $selectedUser->school_id = $school->id;
        $selectedUser->position = null;
        $selectedUser->save();

        return $selectedUser;
    }

    /**
     * Get detailed school info for the View Details modal
     */
    public function getUnifiedSchoolDetails($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $school = School::with('specifics')->findOrFail($id);

        // Modules registration status mapping
        $modulesSatus = [
            'fire_safety' => $school->specifics->where('module', 'fire_safety')->where('key', 'original_fire_safety_id')->isNotEmpty(),
            'typhoon_flood' => $school->specifics->where('module', 'typhoon_flood')->where('key', 'original_evacuation_center_id')->isNotEmpty(),
            'incident_checklist' => $school->specifics->where('module', 'incident')->where('key', 'original_incident_school_id')->isNotEmpty(),
            'comprehensive_school_safety' => $school->specifics->where('module', 'comprehensive')->where('key', 'original_cmpr_school_id')->isNotEmpty(),
            'hazard_mapping' => false // Still to be developed
        ];

        $assignedUsers = User::query()
            ->where('school_id', $school->id)
            ->whereIn('role', ['contributor', 'viewer'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $availableUsers = User::query()
            ->whereNull('school_id')
            ->whereNull('typhoon_school_id')
            ->whereNull('incident_school_id')
            ->where(function ($query) {
                $query->where('needs_fs_registration', false)
                    ->orWhereNull('needs_fs_registration');
            })
            ->where(function ($query) {
                $query->where('needs_tf_registration', false)
                    ->orWhereNull('needs_tf_registration');
            })
            ->whereIn('role', ['contributor', 'viewer'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'position']);

        return response()->json([
            'school' => $school,
            'modules' => $modulesSatus,
            'assigned_users' => $assignedUsers,
            'available_users' => $availableUsers,
            'school_account_user' => $assignedUsers->first(),
            'school_account_users' => $assignedUsers->take(1)->values(),
        ]);
    }

    public function checkSchoolModuleRegistration(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'nullable|integer|exists:schools,id',
            'module' => 'required|string|in:fire_safety,typhoon_flood,incident_checklist,comprehensive_school_safety,hazard_mapping',
        ]);

        $schoolId = isset($validated['school_id']) ? (int) $validated['school_id'] : null;
        if (!$schoolId) {
            return response()->json([
                'registered' => true,
                'message' => 'No school scope provided for this redirect.',
            ]);
        }

        $school = School::with('specifics')->findOrFail($schoolId);
        $module = $validated['module'];

        $registered = in_array($module, ['fire_safety', 'typhoon_flood', 'incident_checklist', 'comprehensive_school_safety', 'hazard_mapping'], true);

        return response()->json([
            'registered' => $registered,
            'message' => $registered
                ? 'Module registration found for this school.'
                : 'This module still has not registered this school yet. Contact administrator to register it.',
        ]);
    }

    /**
     * Store a new school from the Schools tab
     */
    public function storeUnifiedSchool(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $rules = [
            'school_name' => 'required|string|max:255|unique:schools,school_name',
            'school_id' => 'nullable|string|max:255',
            'address' => 'required|string',
            'school_head' => 'nullable|string|max:255',
            'drrm_coordinator' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'contact_number_2' => 'nullable|string|max:255',
            'number_students' => 'nullable|integer|min:0',
            'number_personnel' => 'nullable|integer|min:0',
            'emergency_resources' => 'nullable|string',
        ];

        if (Schema::hasColumn('schools', 'number_gates')) {
            $rules['number_gates'] = 'nullable|integer|min:0';
        }
        if (Schema::hasColumn('schools', 'engineer_last_inspection_date')) {
            $rules['engineer_last_inspection_date'] = 'nullable|date';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $payload = $request->only([
            'school_name',
            'school_id',
            'address',
            'school_head',
            'drrm_coordinator',
            'district',
            'division',
            'region',
            'contact_number',
            'contact_number_2',
            'number_students',
            'number_personnel',
            'number_gates',
            'engineer_last_inspection_date',
            'emergency_resources',
        ]);

        if (array_key_exists('school_head', $payload) && trim((string) $payload['school_head']) === '') {
            unset($payload['school_head']);
        }
        if (array_key_exists('drrm_coordinator', $payload) && trim((string) $payload['drrm_coordinator']) === '') {
            unset($payload['drrm_coordinator']);
        }

        if (!Schema::hasColumn('schools', 'number_gates')) {
            unset($payload['number_gates']);
        }
        if (!Schema::hasColumn('schools', 'engineer_last_inspection_date')) {
            unset($payload['engineer_last_inspection_date']);
        }

        try {
            $school = School::create($payload);
            $this->registerSchoolAcrossModules($school);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'School added successfully!', 'school' => $school->fresh()]);
    }

    /**
     * Update school details
     */
    public function updateUnifiedSchool(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $school = School::findOrFail($id);

        $rules = [
            'school_name' => 'required|string|max:255|unique:schools,school_name,' . $id,
            'school_id' => 'nullable|string|max:255',
            'address' => 'required|string',
            'school_head' => 'nullable|string|max:255',
            'drrm_coordinator' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'contact_number_2' => 'nullable|string|max:255',
            'number_students' => 'nullable|integer|min:0',
            'number_personnel' => 'nullable|integer|min:0',
            'emergency_resources' => 'nullable|string',
        ];

        if (Schema::hasColumn('schools', 'number_gates')) {
            $rules['number_gates'] = 'nullable|integer|min:0';
        }
        if (Schema::hasColumn('schools', 'engineer_last_inspection_date')) {
            $rules['engineer_last_inspection_date'] = 'nullable|date';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $payload = $request->only([
            'school_name',
            'school_id',
            'address',
            'school_head',
            'drrm_coordinator',
            'district',
            'division',
            'region',
            'contact_number',
            'contact_number_2',
            'number_students',
            'number_personnel',
            'number_gates',
            'engineer_last_inspection_date',
            'emergency_resources',
        ]);

        if (array_key_exists('school_head', $payload) && trim((string) $payload['school_head']) === '') {
            unset($payload['school_head']);
        }
        if (array_key_exists('drrm_coordinator', $payload) && trim((string) $payload['drrm_coordinator']) === '') {
            unset($payload['drrm_coordinator']);
        }

        if (!Schema::hasColumn('schools', 'number_gates')) {
            unset($payload['number_gates']);
        }
        if (!Schema::hasColumn('schools', 'engineer_last_inspection_date')) {
            unset($payload['engineer_last_inspection_date']);
        }

        try {
            $school->update($payload);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'School updated successfully!', 'school' => $school->fresh()]);
    }

    public function destroyUnifiedSchool(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $password = (string) $request->input('password', '');
        if ($password === '' || !Hash::check($password, Auth::user()->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid password.'], 403);
        }

        $school = School::findOrFail($id);

        try {
            $name = $school->school_name;
            app(SchoolPermanentDeletionService::class)->deletePermanently($school);

            return response()->json(['success' => true, 'message' => "School and related compliance data permanently deleted: {$name}"]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot complete deletion (database constraint). If this persists, contact support.',
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school.',
            ], 500);
        }
    }

    public function getUsers(Request $request)
    {
        $isAdmin = Auth::user()->role === 'admin';

        if ($isAdmin) {
            $query = User::with(['school', 'typhoonSchool', 'incidentSchool']);

            // Filters
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            $sort = $request->get('sort', 'name');
            $order = $request->get('order', 'asc');

            if ($sort === 'created_at') {
                $query->orderBy('created_at', $order);
            } else {
                $query->orderBy('name', $order);
            }

            $users = $query->get();
        } else {
            $users = User::with(['school', 'typhoonSchool', 'incidentSchool'])->whereKey(Auth::id())->get();
        }

        $schools = School::orderBy('school_name')->get();
        $comprehensiveSchools = School::orderBy('school_name')->get(['id', 'school_name', 'school_id_number', 'district', 'division']);
        $typhoonSchools = School::orderBy('school_name')->get();
        $incidentSchools = School::orderBy('school_name')->get(['id', 'school_name']);
        $adminCount = User::where('role', 'admin')->count();

        if ($request->expectsJson()) {
            return response()->json($users);
        }

        return view('users.index', compact('users', 'schools', 'comprehensiveSchools', 'typhoonSchools', 'incidentSchools', 'adminCount', 'isAdmin'));
    }

    public function storeUser(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,contributor,viewer',
            'admin_confirmation' => 'required_if:role,admin'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        if ($request->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount >= 2) {
                return response()->json(['success' => false, 'message' => 'The system is limited to a maximum of 2 administrators.'], 403);
            }
            if (!Hash::check($request->admin_confirmation, Auth::user()->password)) {
                return response()->json(['success' => false, 'message' => 'Invalid admin confirmation password'], 403);
            }
        }

        $defaultContributorModules = [
            'fire_safety',
            'typhoon_flood',
            'incident_checklist',
            'drill_monitoring',
            'hazard_mapping',
        ];

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'position' => null,
            'module_access' => $request->role === 'contributor' ? $defaultContributorModules : [],
            'needs_fs_registration' => ($request->role === 'contributor'),
            'needs_tf_registration' => ($request->role === 'contributor'),
        ]);

        return response()->json(['success' => true, 'message' => 'User created successfully', 'user' => $user]);
    }

    public function getUser($id)
    {
        $isAdmin = Auth::user()->role === 'admin';
        if (!$isAdmin && (int) $id !== (int) Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::with(['school', 'incidentSchool'])->findOrFail($id);
        $user->school_safety_id = $this->resolveComprehensiveSchoolIdFromFireSafetyId($user->school_id);

        return response()->json($user);
    }

    public function updateUser(Request $request, $id)
    {
        $isAdmin = Auth::user()->role === 'admin';
        if (!$isAdmin && (int) $id !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
        ];

        if ($isAdmin) {
            $rules['role'] = 'required|string|in:admin,contributor,viewer';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($isAdmin) {
            $user->role = $request->role;
            $user->position = null;

            if ($request->role === 'contributor') {
                $currentModules = collect((array) $user->module_access);
                $baseModules = collect([
                    'fire_safety',
                    'typhoon_flood',
                    'incident_checklist',
                    'drill_monitoring',
                    'hazard_mapping',
                    'casualty_tracking',
                ]);

                $mergedModules = $baseModules
                    ->merge($currentModules->contains('comprehensive_school_safety') ? ['comprehensive_school_safety'] : [])
                    ->unique()
                    ->values()
                    ->all();

                $user->module_access = $mergedModules;
            }
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['success' => true, 'message' => 'User updated successfully']);
    }

    public function toggleUserStatus($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($id == Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Cannot deactivate yourself'], 400);
        }

        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        return response()->json(['success' => true, 'message' => "User account has been $status successfully."]);
    }

    public function assignAccess(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $allowedModules = [
            'fire_safety',
            'typhoon_flood',
            'incident_checklist',
            'comprehensive_school_safety',
            'drill_monitoring',
            'hazard_mapping',
            'casualty_tracking',
        ];

        $selectedModules = collect((array) $request->input('modules', []))
            ->filter(fn ($module) => in_array($module, $allowedModules, true))
            ->values()
            ->all();

        if ($user->role === 'contributor') {
            $selectedModules = [
                'fire_safety',
                'typhoon_flood',
                'incident_checklist',
                'drill_monitoring',
                'hazard_mapping',
                'casualty_tracking',
            ];

            if (in_array('comprehensive_school_safety', (array) $request->input('modules', []), true)) {
                $selectedModules[] = 'comprehensive_school_safety';
            }
        }

        $universalSchoolId = $request->filled('universal_school_id')
            ? (int) $request->input('universal_school_id')
            : null;

        if (!empty($selectedModules) && !$universalSchoolId) {
            return response()->json([
                'success' => false,
                'message' => 'This user needs a school assignment first before assigning module access.',
            ], 422);
        }

        $hasModule = fn (string $module): bool => in_array($module, $selectedModules, true);

        // Keep a selected school assignment even when modules are configured later.
        $user->school_id = $universalSchoolId;
        $user->typhoon_school_id = $hasModule('typhoon_flood') ? $universalSchoolId : null;
        $user->incident_school_id = $hasModule('incident_checklist') ? $universalSchoolId : null;

        $user->needs_fs_registration = false;
        $user->needs_tf_registration = false;
        $user->module_access = $selectedModules;

        $user->save();

        return response()->json(['success' => true, 'message' => 'Permissions updated successfully']);
    }

    public function deleteUser($id)
    {
        return response()->json(['success' => false, 'message' => 'User deletion is disabled. Please deactivate the account instead.'], 400);
    }

    public function assignUserToSchool(Request $request, $schoolId, $userId)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $school = School::findOrFail($schoolId);
        $user = User::findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        if (!in_array($user->role, ['contributor', 'viewer'], true)) {
            return response()->json(['success' => false, 'message' => 'Only contributors/viewers can be assigned.'], 422);
        }

        $linkedSchoolIds = collect([
            $user->school_id,
            $user->typhoon_school_id,
            $user->incident_school_id,
        ])->filter()->map(fn ($value) => (int) $value)->unique()->values();

        if ($linkedSchoolIds->isNotEmpty() && !$linkedSchoolIds->every(fn (int $linkedSchoolId) => $linkedSchoolId === (int) $school->id)) {
            return response()->json(['success' => false, 'message' => 'This user is already assigned to another school.'], 422);
        }

        User::query()
            ->where('school_id', $school->id)
            ->where('id', '!=', $user->id)
            ->update(['school_id' => null, 'position' => null]);

        $user->school_id = $school->id;
        $user->position = null;
        $user->needs_fs_registration = false;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User assigned successfully.',
            'school' => $school->fresh(),
        ]);
    }

    public function removeUserSchoolAssignment(Request $request, $schoolId, $userId)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $school = School::findOrFail($schoolId);
        $user = User::findOrFail($userId);

        if ((int) $user->school_id !== (int) $schoolId) {
            return response()->json(['success' => false, 'message' => 'User is not assigned to this school.'], 422);
        }

        $user->school_id = null;
        $user->position = null;
        $user->needs_fs_registration = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Assignment removed successfully.',
            'school' => $school->fresh(),
        ]);
    }

    public function registerMySchool(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->role !== 'contributor') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'school_name' => 'required|string|max:255',
            'school_id_number' => 'required|string|unique:schools,school_id',
            'address' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            DB::transaction(function () use ($request, &$user) {
                $school = School::create([
                    'school_name' => $request->school_name,
                    'school_id' => $request->school_id_number,
                    'school_id_number' => $request->school_id_number,
                    'address' => $request->address,
                    'fire_safety_status' => 'unconfigured',
                    'evacuation_identification' => $request->school_id_number,
                    'identification' => $request->school_id_number,
                    'evacuation_location' => $request->address,
                    'evacuation_status' => 'cleared',
                ]);

                $this->registerSchoolAcrossModules($school);

                if ($user->needs_fs_registration) {
                    $user->school_id = $school->id;
                    $user->needs_fs_registration = false;
                }

                if ($user->needs_tf_registration) {
                    $user->typhoon_school_id = $school->id;
                    $user->needs_tf_registration = false;
                }

                $user->save();
            });

            return response()->json(['success' => true, 'message' => 'Your school has been registered and assigned!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function storeAnnouncement(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'what' => 'required|string|max:255',
            'when' => 'required|date',
            'where' => 'required|string|max:255',
            'why' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max (current runtime safe ceiling)
        ], [
            'image.required' => 'Please select an image file.',
            'image.max' => 'Image is too large. Use an image 2MB or smaller.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Allowed image formats: jpeg, png, jpg, gif.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            // Check if there's an existing active announcement and deactivate it

            $validated = $validator->validated();
            $imagePath = $request->file('image')->store('announcements', 'public');

            $normalizedWhen = Carbon::parse((string) $validated['when'])->format('Y-m-d H:i:s');

            $announcement = Announcement::create([
                'what' => $validated['what'],
                'when' => $normalizedWhen,
                'where' => $validated['where'],
                'why' => $validated['why'],
                'image_path' => $imagePath,
                'is_active' => true,
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Announcement posted successfully!', 'announcement' => $announcement]);
            }

            return redirect()->back()->with('success', 'Announcement posted successfully!');
        } catch (\Exception $e) {
            Log::error('Dashboard announcement posting failed', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to save announcement: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to save announcement: ' . $e->getMessage());
        }
    }

    public function deleteAnnouncement($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json(['success' => true, 'message' => 'Announcement deleted successfully']);
    }
}
