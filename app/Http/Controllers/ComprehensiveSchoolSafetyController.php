<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ComprehensiveAssessment;
use App\Models\ComprehensiveAssessmentItem;
use App\Models\ComprehensiveArchive;
use App\Models\ComprehensiveFacility;
use App\Models\ComprehensiveStorage;
use App\Models\ComprehensiveStudent;
use App\Models\ComprehensiveSummaryFinding;
use App\Models\School;
use App\Models\SchoolSpecificsInformation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Shuchkin\SimpleXLSX;

class ComprehensiveSchoolSafetyController extends Controller
{
    private const QUESTIONNAIRE_TEMPLATE_KEY = 'assessment_questionnaire_template';
    private const STUDENT_IMPORT_ATTACHMENTS_KEY = 'student_import_attachments';
    private const ASSESSMENT_SUMMARY_KEY_PREFIX = 'assessment_summary_';
    private const CATEGORY_DIVIDER = ':::';
    private const ACADEMIC_YEAR_START_MONTH = 6;

    private function academicYearFromDate($dateValue): string
    {
        try {
            $date = $dateValue ? Carbon::parse($dateValue) : now();
        } catch (\Throwable $e) {
            $date = now();
        }

        $year = (int) $date->year;
        $startYear = (int) ($date->month >= self::ACADEMIC_YEAR_START_MONTH ? $year : ($year - 1));

        return $startYear . '-' . ($startYear + 1);
    }

    private function currentAcademicYearLabel(): string
    {
        return $this->academicYearFromDate(now());
    }

    private function formatDateValue($value, string $format = 'Y-m-d'): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function ensureAcademicYearForAssessments(School $school): void
    {
        if (!Schema::hasColumn('cmpr_schl_sfty_assessments', 'academic_year')) {
            return;
        }

        $school->assessments()
            ->whereNull('academic_year')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $row->academic_year = $this->academicYearFromDate($row->date_visited ?? $row->created_at);
                    $row->save();
                }
            });
    }

    private function snapshotFacilityArchiveForAcademicYear(School $school, ?string $academicYear = null): void
    {
        if (!Schema::hasTable('cmpr_schl_sfty_archives')) {
            return;
        }

        $resolvedAcademicYear = $academicYear ?: $this->currentAcademicYearLabel();
        $facilitiesSnapshot = $school->facilities()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'condition', 'description', 'remarks', 'updated_at'])
            ->map(function ($facility) {
                return [
                    'id' => (int) $facility->id,
                    'name' => (string) ($facility->name ?? ''),
                    'type' => (string) ($facility->type ?? ''),
                    'condition' => (string) ($facility->condition ?? ''),
                    'description' => (string) ($facility->description ?? ''),
                    'remarks' => (string) ($facility->remarks ?? ''),
                    'updated_at' => optional($facility->updated_at)->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        ComprehensiveArchive::updateOrCreate(
            [
                'school_id' => $school->id,
                'archive_type' => 'facility',
                'academic_year' => $resolvedAcademicYear,
            ],
            [
                'payload' => [
                    'facility_count' => count($facilitiesSnapshot),
                    'facilities' => $facilitiesSnapshot,
                ],
                'archived_at' => now(),
            ]
        );
    }

    private function ensureAcademicYearArchives(School $school): void
    {
        if (!Schema::hasTable('cmpr_schl_sfty_archives')) {
            return;
        }

        $this->ensureAcademicYearForAssessments($school);

        $currentAcademicYear = $this->currentAcademicYearLabel();
        $this->snapshotFacilityArchiveForAcademicYear($school, $currentAcademicYear);

        if (!Schema::hasColumn('cmpr_schl_sfty_assessments', 'academic_year')) {
            return;
        }

        $archivedAssessmentYears = $school->assessments()
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', $currentAcademicYear)
            ->select('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->filter(fn ($year) => trim((string) $year) !== '');

        foreach ($archivedAssessmentYears as $academicYear) {
            $yearAssessments = $school->assessments()
                ->where('academic_year', $academicYear)
                ->orderBy('date_visited')
                ->get(['id', 'date_visited', 'assessed_by', 'total_score', 'status']);

            if ($yearAssessments->isEmpty()) {
                continue;
            }

            ComprehensiveArchive::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'archive_type' => 'assessment',
                    'academic_year' => (string) $academicYear,
                ],
                [
                    'payload' => [
                        'assessment_count' => $yearAssessments->count(),
                        'average_score' => round((float) $yearAssessments->avg('total_score'), 2),
                        'latest_date' => $this->formatDateValue($yearAssessments->last()->date_visited),
                        'assessments' => $yearAssessments->map(function ($assessment) {
                            return [
                                'id' => (int) $assessment->id,
                                'code' => $this->getAssessmentCode((int) $assessment->id),
                                'date_visited' => $this->formatDateValue($assessment->date_visited),
                                'assessed_by' => (string) ($assessment->assessed_by ?? ''),
                                'total_score' => (float) ($assessment->total_score ?? 0),
                                'status' => (string) ($assessment->status ?? ''),
                            ];
                        })->values()->all(),
                    ],
                    'archived_at' => now(),
                ]
            );
        }
    }

    private function ensureAdmin(): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Only administrators can manage school directory records.');
        }
    }

    private function resolveContributorSchool(): ?School
    {
        $user = Auth::user();

        if (!$user || $user->role === 'admin') {
            return null;
        }

        if (!empty($user->school_id)) {
            return School::find($user->school_id);
        }

        return null;
    }

    private function findSchoolOrAbortForUser(int $schoolId): School
    {
        $school = School::findOrFail($schoolId);
        $user = Auth::user();

        if ($user && $user->role !== 'admin') {
            $allowedSchool = $this->resolveContributorSchool();
            if (!$allowedSchool || (int) $allowedSchool->id !== (int) $school->id) {
                abort(403, 'You are not allowed to access this school.');
            }
        } elseif ($user && $user->role === 'admin') {
            session(['csss_active_school_id' => (int) $school->id]);
        }

        return $school;
    }

    private function getDefaultAssessmentSections(): array
    {
        return [
            'enabling_environment' => [
                'title' => 'Enabling Environment',
                'division' => 'checklist_tools',
                'items' => [
                    ['text' => 'Adopted/Localized policies relating to DRRM/CCA/EIE on education/school safety', 'default_points' => 1],
                    ['text' => 'Formed School DRRM Team, with a focal person and consisting of personnel from different offices', 'default_points' => 1],
                    ['text' => 'Has a comprehensive School DRRM Plan, which includes CCA and EIE measures', 'default_points' => 1],
                    ['text' => 'School budget supports regular DRRM activities', 'default_points' => 1],
                    ['text' => 'Conducted student-led school watching and hazard mapping', 'default_points' => 1],
                    ['text' => 'Incorporated results of student-led school watching and hazard mapping in the SIP', 'default_points' => 1],
                    ['text' => 'Data collection and consolidation of programs and activities on DRRM', 'default_points' => 1],
                ],
            ],
            'safe_learning_facility' => [
                'title' => 'Safe Learning Facilities',
                'division' => 'checklist_tools',
                'items' => [
                    ['text' => 'School building/classroom components are according to DepEd/National Building Code', 'default_points' => 1],
                    ['text' => 'School conducted risk assessment of buildings', 'default_points' => 1],
                    ['text' => 'School has taken appropriate action with respect to unsafe school buildings', 'default_points' => 1],
                    ['text' => 'Undertaken regular inspection and repair of minor classroom damages', 'default_points' => 1],
                    ['text' => 'School heads and teachers have training on psychosocial support', 'default_points' => 1],
                    ['text' => 'Classrooms have usually 2 doors that swing out', 'default_points' => 1],
                    ['text' => 'Wide corridors for easy movement', 'default_points' => 1],
                ],
            ],
            'disaster_risk_management' => [
                'title' => 'Disaster Risk Management',
                'division' => 'checklist_tools',
                'items' => [
                    ['text' => 'School has a contingency plan for various hazards', 'default_points' => 1],
                    ['text' => '95% of students and personnel participated in drills', 'default_points' => 1],
                    ['text' => 'School has a functional early warning system', 'default_points' => 1],
                    ['text' => 'School has available emergency kits/equipment', 'default_points' => 1],
                    ['text' => 'School has a trained emergency response team', 'default_points' => 1],
                ],
            ],
            'pillar1_school_building_components' => [
                'title' => '1.0 School Building Components',
                'division' => 'pillar_1',
                'items' => [
                    ['text' => 'School building/classroom components is/are according to the DepED and/or National Building Code approved/standard design and specifications.', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - a. Wall Finish', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - b. Flooring', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - c. Ceiling', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - d. Window/Ventilations', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - e. Roofing', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - f. Corridor', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - g. 2-Doors per classroom', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - h. Railings/handrails/ramps', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - i. Standard room dimensions', 'default_points' => 1],
                    ['text' => 'Academic or Instructional Rooms - j. Presence of emergency fire exits and signages', 'default_points' => 1],
                ],
            ],
            'pillar1_ancillary_facilities' => [
                'title' => '2.0 Ancillary Facilities',
                'division' => 'pillar_1',
                'items' => [
                    ['text' => 'Provisions/presence of School Health Clinic', 'default_points' => 1],
                    ['text' => 'Provisions/presence of Guidance and Counselling', 'default_points' => 1],
                    ['text' => 'Provisions/presence of School Canteen', 'default_points' => 1],
                    ['text' => 'Provisions/presence of Home Economics Building/Room', 'default_points' => 1],
                    ['text' => 'Provisions/presence of Science Laboratory Room/Bldg.', 'default_points' => 1],
                ],
            ],
        ];
    }

    private function normalizeSectionItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $text = trim((string) ($item['text'] ?? ''));
                $subtitle = trim((string) ($item['subtitle'] ?? ''));
                $defaultPoints = (float) ($item['default_points'] ?? 0);
                $subItems = collect((array) ($item['sub_items'] ?? []))
                    ->map(function ($subItem) {
                        if (is_array($subItem)) {
                            $subText = trim((string) ($subItem['text'] ?? ''));
                            $subPoints = (float) ($subItem['default_points'] ?? 0);
                        } else {
                            $subText = trim((string) $subItem);
                            $subPoints = 0;
                        }

                        if ($subText === '') {
                            return null;
                        }

                        return [
                            'text' => $subText,
                            'default_points' => max(0, $subPoints),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            } else {
                $text = trim((string) $item);
                $subtitle = '';
                $defaultPoints = 0;
                $subItems = [];
            }

            if ($text === '') {
                continue;
            }

            $normalizedItem = [
                'text' => $text,
                'default_points' => $defaultPoints,
            ];

            if ($subtitle !== '') {
                $normalizedItem['subtitle'] = $subtitle;
            }

            if (!empty($subItems)) {
                $normalizedItem['sub_items'] = $subItems;
            }

            $normalized[] = $normalizedItem;
        }

        return $normalized;
    }

    private function normalizeDivisionKey(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : 'checklist_tools';
    }

    private function normalizeDivisionLabel(string $divisionKey, ?string $label = null): string
    {
        $candidate = trim((string) ($label ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }

        return collect(explode('_', $divisionKey))
            ->filter(fn ($part) => $part !== '')
            ->map(fn ($part) => ucfirst($part))
            ->implode(' ');
    }

    private function buildCriteriaLabel(string $itemText, string $subtitle, string $subText): string
    {
        $parts = [];
        if (trim($itemText) !== '') {
            $parts[] = trim($itemText);
        }
        if (trim($subtitle) !== '') {
            $parts[] = trim($subtitle);
        }
        $parts[] = trim($subText);

        return implode(' - ', array_filter($parts));
    }

    private function expandSectionItemsForSubmission(array $section): array
    {
        $expanded = [];

        foreach (($section['items'] ?? []) as $index => $item) {
            if (is_array($item)) {
                $itemText = trim((string) ($item['text'] ?? ''));
                $subtitle = trim((string) ($item['subtitle'] ?? ''));
                $itemDefaultPoints = (float) ($item['default_points'] ?? 0);
                $subItems = (array) ($item['sub_items'] ?? []);
            } else {
                $itemText = trim((string) $item);
                $subtitle = '';
                $itemDefaultPoints = 0;
                $subItems = [];
            }

            if ($itemText === '') {
                continue;
            }

            if (!empty($subItems)) {
                foreach ($subItems as $subIndex => $subItem) {
                    if (is_array($subItem)) {
                        $subText = trim((string) ($subItem['text'] ?? ''));
                        $subDefaultPoints = (float) ($subItem['default_points'] ?? 0);
                    } else {
                        $subText = trim((string) $subItem);
                        $subDefaultPoints = 0;
                    }

                    if ($subText === '') {
                        continue;
                    }

                    $expanded[] = [
                        'field_suffix' => $index . '_sub_' . $subIndex,
                        'criteria' => $this->buildCriteriaLabel($itemText, $subtitle, $subText),
                        'default_points' => max(0, $subDefaultPoints),
                    ];
                }

                continue;
            }

            $expanded[] = [
                'field_suffix' => (string) $index,
                'criteria' => $itemText,
                'default_points' => max(0, $itemDefaultPoints),
            ];
        }

        return $expanded;
    }

    private function normalizeAssessmentSections(array $sections): array
    {
        $normalized = [];

        foreach ($sections as $key => $section) {
            $sectionKey = is_string($key) && $key !== ''
                ? $key
                : ('section_' . (count($normalized) + 1));

            $title = trim((string) ($section['title'] ?? ''));
            $division = $this->normalizeDivisionKey((string) ($section['division'] ?? 'checklist_tools'));
            $divisionLabel = $this->normalizeDivisionLabel($division, (string) ($section['division_label'] ?? ''));
            $items = $this->normalizeSectionItems((array) ($section['items'] ?? []));

            if ($title === '' || empty($items)) {
                continue;
            }

            $normalized[$sectionKey] = [
                'title' => $title,
                'division' => $division,
                'division_label' => $divisionLabel,
                'items' => $items,
            ];
        }

        return $normalized;
    }

    private function getAssessmentSections(?int $schoolId = null): array
    {
        if (!$schoolId) {
            return $this->normalizeAssessmentSections($this->getDefaultAssessmentSections());
        }

        $record = SchoolSpecificsInformation::query()
            ->where('school_id', $schoolId)
            ->where('module', 'comprehensive')
            ->where('key', self::QUESTIONNAIRE_TEMPLATE_KEY)
            ->first();

        $decoded = json_decode((string) ($record->value ?? ''), true);
        if (is_array($decoded) && !empty($decoded)) {
            if (isset($decoded['sections']) && is_array($decoded['sections'])) {
                return $this->normalizeAssessmentSections($decoded['sections']);
            }

            return $this->normalizeAssessmentSections($decoded);
        }

        return $this->normalizeAssessmentSections($this->getDefaultAssessmentSections());
    }

    private function composeStoredCategory(string $division, string $title): string
    {
        $normalizedDivision = $this->normalizeDivisionKey($division);
        return $normalizedDivision . self::CATEGORY_DIVIDER . $title;
    }

    private function parseStoredCategory(string $stored): array
    {
        if (str_contains($stored, self::CATEGORY_DIVIDER)) {
            [$division, $title] = explode(self::CATEGORY_DIVIDER, $stored, 2);
            return [
                'division' => $this->normalizeDivisionKey($division),
                'division_label' => $this->normalizeDivisionLabel($this->normalizeDivisionKey($division)),
                'title' => trim($title),
            ];
        }

        return [
            'division' => 'checklist_tools',
            'division_label' => $this->normalizeDivisionLabel('checklist_tools'),
            'title' => trim($stored),
        ];
    }

    private function getAssessmentSummaryKey(int $assessmentId): string
    {
        return self::ASSESSMENT_SUMMARY_KEY_PREFIX . $assessmentId;
    }

    private function getAssessmentSummary(int $schoolId, int $assessmentId): string
    {
        $record = SchoolSpecificsInformation::query()
            ->where('school_id', $schoolId)
            ->where('module', 'comprehensive')
            ->where('key', $this->getAssessmentSummaryKey($assessmentId))
            ->first();

        return (string) ($record->value ?? '');
    }

    private function saveAssessmentSummary(int $schoolId, int $assessmentId, ?string $summary): void
    {
        SchoolSpecificsInformation::updateOrCreate(
            [
                'school_id' => $schoolId,
                'module' => 'comprehensive',
                'key' => $this->getAssessmentSummaryKey($assessmentId),
            ],
            [
                'value' => trim((string) ($summary ?? '')),
            ]
        );
    }

    private function getAssessmentCode(int $assessmentId): string
    {
        return 'ASMT-' . str_pad((string) $assessmentId, 3, '0', STR_PAD_LEFT);
    }

    private function collectAssessmentResponses(ComprehensiveAssessment $assessment): array
    {
        return $assessment->items
            ->mapWithKeys(function ($item) {
                $parsed = $this->parseStoredCategory((string) $item->category);
                $categoryKey = str($parsed['division'] . '_' . $parsed['title'])->lower()->replace([' ', '/', '-'], '_')->value();
                $criteriaKey = md5((string) $item->criteria);

                return [
                    $categoryKey . ':' . $criteriaKey => [
                        'is_compliant' => $item->is_compliant === null ? null : (bool) $item->is_compliant,
                        'points' => (float) $item->points,
                        'remarks' => $item->remarks,
                    ],
                ];
            })
            ->all();
    }

    private function getAssessmentSectionsForAssessment(ComprehensiveAssessment $assessment): array
    {
        $sections = [];

        foreach ($assessment->items->sortBy('id') as $item) {
            $parsed = $this->parseStoredCategory((string) $item->category);
            $baseKey = str($parsed['division'] . '_' . $parsed['title'])->lower()->replace([' ', '/', '-'], '_')->value();
            $sectionKey = $baseKey;
            $suffix = 1;

            while (isset($sections[$sectionKey]) && $sections[$sectionKey]['title'] !== $parsed['title']) {
                $suffix++;
                $sectionKey = $baseKey . '_' . $suffix;
            }

            if (!isset($sections[$sectionKey])) {
                $sections[$sectionKey] = [
                    'title' => $parsed['title'] !== '' ? $parsed['title'] : 'Section',
                    'division' => $parsed['division'],
                    'division_label' => $parsed['division_label'] ?? $this->normalizeDivisionLabel($parsed['division']),
                    'items' => [],
                ];
            }

            $sections[$sectionKey]['items'][] = [
                'text' => (string) $item->criteria,
                'default_points' => (float) $item->points,
            ];
        }

        return $sections;
    }

    public function dashboard(Request $request)
    {
        if (!Schema::hasTable('schools')) {
            return view('comprehensive-school-safety.module-dashboard', [
                'stats' => [
                    'directory_total' => 0,
                    'registered_comprehensive' => 0,
                    'pending_registration' => 0,
                ],
                'recentSchools' => collect(),
                'directorySchoolsForComprehensiveRegistration' => collect(),
                'setupNotice' => 'School tables are not yet migrated. Run php artisan migrate to complete setup.',
            ]);
        }

        $user = Auth::user();

        if ($user && $user->role === 'admin' && $request->filled('school_id')) {
            $switchSchoolId = (int) $request->input('school_id');
            $switchSchool = School::find($switchSchoolId);
            if ($switchSchool) {
                session(['csss_active_school_id' => $switchSchool->id]);
            }
        }

        if ($user && $user->role !== 'admin') {
            $assignedSchool = $this->resolveContributorSchool();

            if ($assignedSchool) {
                return redirect()->route('comprehensive-school-safety.school.dashboard', $assignedSchool->id);
            }

            return view('comprehensive-school-safety.module-dashboard', [
                'stats' => [
                    'directory_total' => 0,
                    'registered_comprehensive' => 0,
                    'pending_registration' => 0,
                ],
                'recentSchools' => collect(),
                'directorySchoolsForComprehensiveRegistration' => collect(),
                'setupNotice' => 'No school assignment found for your account. Please contact an administrator.',
            ]);
        }

        $directoryTotal = School::count();

        $registeredComprehensiveQuery = School::whereHas('specifics', function ($q) {
            $q->where('module', 'comprehensive')->where('key', 'original_cmpr_school_id');
        });

        $registeredComprehensiveCount = (clone $registeredComprehensiveQuery)->count();

        $directorySchoolsForComprehensiveRegistration = School::query()
            ->whereDoesntHave('specifics', function ($q) {
                $q->where('module', 'comprehensive')->where('key', 'original_cmpr_school_id');
            })
            ->orderBy('school_name')
            ->get([
                'id', 'school_name', 'school_id', 'school_id_number', 'address', 'school_head',
                'drrm_coordinator', 'contact_number', 'district', 'division', 'region',
            ]);

        $stats = [
            'directory_total' => $directoryTotal,
            'registered_comprehensive' => $registeredComprehensiveCount,
            'pending_registration' => $directorySchoolsForComprehensiveRegistration->count(),
        ];

        $recentSchools = (clone $registeredComprehensiveQuery)->latest()->take(8)->get();

        return view('comprehensive-school-safety.module-dashboard', compact(
            'stats',
            'recentSchools',
            'directorySchoolsForComprehensiveRegistration'
        ));
    }

    /**
     * Link an existing main-directory school to Comprehensive School Safety (no new school row).
     */
    public function registerSchoolFromDirectory(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'unified_school_id' => 'required|integer|exists:schools,id',
        ]);

        $school = School::findOrFail($validated['unified_school_id']);

        $exists = SchoolSpecificsInformation::where('school_id', $school->id)
            ->where('module', 'comprehensive')
            ->where('key', 'original_cmpr_school_id')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This school is already registered for Comprehensive School Safety.',
            ], 422);
        }

        SchoolSpecificsInformation::updateOrInsert(
            [
                'school_id' => $school->id,
                'module' => 'comprehensive',
                'key' => 'original_cmpr_school_id',
            ],
            [
                'value' => (string) $school->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        ActivityLog::log('comprehensive_school_safety', 'Registered school for Comprehensive School Safety (from directory): ' . $school->school_name, [
            'school_id' => $school->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School registered for Comprehensive School Safety.',
            'school' => $school->fresh(),
        ]);
    }

    public function schools()
    {
        $schools = School::orderBy('school_name')->paginate(15);

        return view('comprehensive-school-safety.schools-index', compact('schools'));
    }

    public function schoolDashboard($schoolId)
    {
        $user = Auth::user();
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $allSchools = School::orderBy('school_name')->get();

        if ($user && $user->role === 'admin') {
            session(['csss_active_school_id' => (int) $school->id]);
        }

        $stats = [
            'total_students' => $school->students()->count(),
            'total_facilities' => $school->facilities()->count(),
            'avg_safety_score' => round($school->assessments()->avg('total_score') ?? 0, 2),
            'pending_assessments' => $school->assessments()->where('status', '!=', 'completed')->count(),
        ];

        $recentAssessments = $school->assessments()->latest()->take(5)->get();

        return view('comprehensive-school-safety.school-dashboard', compact('school', 'stats', 'recentAssessments', 'allSchools'));
    }

    public function schoolAssessments($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $this->ensureAcademicYearArchives($school);

        $currentAcademicYear = $this->currentAcademicYearLabel();
        $assessmentQuery = $school->assessments();

        if (Schema::hasColumn('cmpr_schl_sfty_assessments', 'academic_year')) {
            $assessmentQuery->where('academic_year', $currentAcademicYear);
        }

        $assessments = $assessmentQuery->latest()->paginate(15);

        $assessments->getCollection()->transform(function ($assessment) {
            $assessment->assessment_code = $this->getAssessmentCode((int) $assessment->id);
            return $assessment;
        });

        $archivedAssessmentHistory = collect();
        if (Schema::hasTable('cmpr_schl_sfty_archives')) {
            $archivedAssessmentHistory = ComprehensiveArchive::query()
                ->where('school_id', $school->id)
                ->where('archive_type', 'assessment')
                ->orderByDesc('academic_year')
                ->get();
        }

        return view('comprehensive-school-safety.school-assessments', compact(
            'school',
            'assessments',
            'currentAcademicYear',
            'archivedAssessmentHistory'
        ));
    }

    public function newSafetyAssessmentForm($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $this->ensureAcademicYearArchives($school);
        $assessmentSections = $this->getAssessmentSections((int) $school->id);
        $assessmentSummary = '';
        $currentAcademicYear = $this->currentAcademicYearLabel();

        return view('comprehensive-school-safety.school-assessment-form', compact(
            'school',
            'assessmentSections',
            'assessmentSummary',
            'currentAcademicYear'
        ));
    }

    public function viewAssessment($schoolId, $assessmentId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $assessment = ComprehensiveAssessment::with('items')
            ->where('school_id', $school->id)
            ->findOrFail((int) $assessmentId);

        $assessmentSections = $this->getAssessmentSectionsForAssessment($assessment);
        $assessmentResponses = $this->collectAssessmentResponses($assessment);
        $assessmentCode = $this->getAssessmentCode((int) $assessment->id);
        $assessmentSummary = $this->getAssessmentSummary((int) $school->id, (int) $assessment->id);
        $formMode = 'view';

        return view('comprehensive-school-safety.school-assessment-form', compact(
            'school',
            'assessment',
            'assessmentSections',
            'assessmentResponses',
            'assessmentCode',
            'assessmentSummary',
            'formMode'
        ));
    }

    public function editAssessmentForm($schoolId, $assessmentId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $assessment = ComprehensiveAssessment::with('items')
            ->where('school_id', $school->id)
            ->findOrFail((int) $assessmentId);

        $assessmentSections = $this->getAssessmentSectionsForAssessment($assessment);
        $assessmentResponses = $this->collectAssessmentResponses($assessment);
        $assessmentCode = $this->getAssessmentCode((int) $assessment->id);
        $assessmentSummary = $this->getAssessmentSummary((int) $school->id, (int) $assessment->id);
        $formMode = 'edit';

        return view('comprehensive-school-safety.school-assessment-form', compact(
            'school',
            'assessment',
            'assessmentSections',
            'assessmentResponses',
            'assessmentCode',
            'assessmentSummary',
            'formMode'
        ));
    }

    public function storeAssessment(Request $request, $schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $this->ensureAcademicYearArchives($school);
        $assessmentSections = $this->getAssessmentSections((int) $school->id);
        $currentAcademicYear = $this->currentAcademicYearLabel();

        $validated = $request->validate([
            'date_visited' => ['required', 'date'],
            'assessed_by' => ['required', 'string', 'max:255'],
            'summary_sheet' => ['nullable', 'string'],
        ]);

        $totalScore = 0;

        DB::transaction(function () use ($request, $school, $validated, $assessmentSections, $currentAcademicYear, &$totalScore) {
            $assessmentPayload = [
                'school_id' => $school->id,
                'date_visited' => $validated['date_visited'],
                'assessed_by' => $validated['assessed_by'],
                'total_score' => 0,
                'status' => 'completed',
            ];

            if (Schema::hasColumn('cmpr_schl_sfty_assessments', 'academic_year')) {
                $assessmentPayload['academic_year'] = $currentAcademicYear;
            }

            $assessment = ComprehensiveAssessment::create($assessmentPayload);

            foreach ($assessmentSections as $categoryKey => $section) {
                foreach ($this->expandSectionItemsForSubmission($section) as $expandedItem) {
                    $criteria = (string) $expandedItem['criteria'];
                    $fieldName = $categoryKey . '_' . $expandedItem['field_suffix'];
                    $isCompliant = $request->input($fieldName) === 'yes';
                    $points = (float) $request->input($categoryKey . '_points_' . $expandedItem['field_suffix'], $expandedItem['default_points'] ?? 0);
                    if ($points < 0) {
                        $points = 0;
                    }
                    $totalScore += $points;

                    ComprehensiveAssessmentItem::create([
                        'assessment_id' => $assessment->id,
                        'category' => $this->composeStoredCategory((string) ($section['division'] ?? 'checklist_tools'), (string) $section['title']),
                        'criteria' => $criteria,
                        'is_compliant' => $isCompliant,
                        'points' => $points,
                        'remarks' => $request->input($categoryKey . '_remarks_' . $expandedItem['field_suffix']),
                    ]);
                }
            }

            $assessment->update([
                'total_score' => $totalScore,
            ]);

            $this->saveAssessmentSummary((int) $school->id, (int) $assessment->id, $validated['summary_sheet'] ?? '');

            ActivityLog::log('comprehensive_school_safety', 'Created safety assessment: CSSS-' . str_pad((string) $assessment->id, 4, '0', STR_PAD_LEFT), [
                'school_id' => $school->id,
                'notes' => 'Assessed by: ' . ($validated['assessed_by'] ?? 'N/A') . ', Total Score: ' . (string) $totalScore,
            ]);
        });

        return redirect()
            ->route('comprehensive-school-safety.school.assessments', $school->id)
            ->with('success', 'Assessment saved successfully.');
    }

    public function updateAssessment(Request $request, $schoolId, $assessmentId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $this->ensureAcademicYearForAssessments($school);
        $assessment = ComprehensiveAssessment::where('school_id', $school->id)->findOrFail((int) $assessmentId);
        $assessmentSections = $this->getAssessmentSectionsForAssessment($assessment);
        $currentAcademicYear = $this->currentAcademicYearLabel();

        $validated = $request->validate([
            'date_visited' => ['required', 'date'],
            'assessed_by' => ['required', 'string', 'max:255'],
            'summary_sheet' => ['nullable', 'string'],
        ]);

        $totalScore = 0;

        DB::transaction(function () use ($request, $school, $assessment, $validated, $assessmentSections, $currentAcademicYear, &$totalScore) {
            $assessment->items()->delete();

            foreach ($assessmentSections as $categoryKey => $section) {
                foreach ($this->expandSectionItemsForSubmission($section) as $expandedItem) {
                    $criteria = (string) $expandedItem['criteria'];
                    $fieldName = $categoryKey . '_' . $expandedItem['field_suffix'];
                    $isCompliant = $request->input($fieldName) === 'yes';
                    $points = (float) $request->input($categoryKey . '_points_' . $expandedItem['field_suffix'], $expandedItem['default_points'] ?? 0);
                    if ($points < 0) {
                        $points = 0;
                    }
                    $totalScore += $points;

                    ComprehensiveAssessmentItem::create([
                        'assessment_id' => $assessment->id,
                        'category' => $this->composeStoredCategory((string) ($section['division'] ?? 'checklist_tools'), (string) $section['title']),
                        'criteria' => $criteria,
                        'is_compliant' => $isCompliant,
                        'points' => $points,
                        'remarks' => $request->input($categoryKey . '_remarks_' . $expandedItem['field_suffix']),
                    ]);
                }
            }

            $updatePayload = [
                'date_visited' => $validated['date_visited'],
                'assessed_by' => $validated['assessed_by'],
                'total_score' => $totalScore,
            ];

            if (Schema::hasColumn('cmpr_schl_sfty_assessments', 'academic_year')) {
                $updatePayload['academic_year'] = $assessment->academic_year ?: $currentAcademicYear;
            }

            $assessment->update($updatePayload);

            $this->saveAssessmentSummary((int) $school->id, (int) $assessment->id, $validated['summary_sheet'] ?? '');

            ActivityLog::log('comprehensive_school_safety', 'Updated safety assessment: CSSS-' . str_pad((string) $assessment->id, 4, '0', STR_PAD_LEFT), [
                'school_id' => $school->id,
                'notes' => 'Assessed by: ' . ($validated['assessed_by'] ?? 'N/A') . ', Total Score: ' . (string) $totalScore,
            ]);
        });

        return redirect()
            ->route('comprehensive-school-safety.school.assessments', $school->id)
            ->with('success', 'Assessment updated successfully.');
    }

    public function editAssessmentQuestionnaire($schoolId)
    {
        $this->ensureAdmin();
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $assessmentSections = $this->getAssessmentSections((int) $school->id);

        return view('comprehensive-school-safety.assessment-questionnaire-editor', compact('school', 'assessmentSections'));
    }

    public function updateAssessmentQuestionnaire(Request $request, $schoolId)
    {
        $this->ensureAdmin();
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $sections = null;

        if ($request->filled('sections_json')) {
            $validated = $request->validate([
                'sections_json' => ['required', 'string'],
            ]);

            $decoded = json_decode($validated['sections_json'], true);
            if (is_array($decoded)) {
                $sections = $decoded;
            }
        }

        if ($sections === null) {
            $validated = $request->validate([
                'sections' => ['required', 'array', 'min:1'],
                'sections.*.title' => ['required', 'string'],
                'sections.*.division' => ['required', 'string'],
                'sections.*.division_label' => ['nullable', 'string'],
                'sections.*.items' => ['required', 'array', 'min:1'],
                'sections.*.items.*.text' => ['nullable', 'string'],
                'sections.*.items.*.subtitle' => ['nullable', 'string'],
                'sections.*.items.*.default_points' => ['nullable', 'numeric', 'min:0'],
                'sections.*.items.*.sub_items' => ['nullable', 'array'],
                'sections.*.items.*.sub_items.*.text' => ['nullable', 'string'],
                'sections.*.items.*.sub_items.*.default_points' => ['nullable', 'numeric', 'min:0'],
            ]);

            $sections = [];
            foreach ($validated['sections'] as $index => $section) {
                $sections[] = [
                    'key' => 'section_' . ($index + 1),
                    'title' => $section['title'] ?? '',
                    'division' => $section['division'] ?? 'checklist_tools',
                    'division_label' => $section['division_label'] ?? '',
                    'items' => $section['items'] ?? [],
                ];
            }
        }

        if (!is_array($sections) || empty($sections)) {
            return redirect()
                ->route('comprehensive-school-safety.school.assessments.questionnaire.edit', $school->id)
                ->with('error', 'Invalid questionnaire payload.');
        }

        $cleaned = [];
        foreach ($sections as $section) {
            $key = (string) ($section['key'] ?? '');
            $title = trim((string) ($section['title'] ?? ''));
            $division = $this->normalizeDivisionKey((string) ($section['division'] ?? 'checklist_tools'));
            $divisionLabel = $this->normalizeDivisionLabel($division, (string) ($section['division_label'] ?? ''));
            $items = collect($section['items'] ?? [])
                ->map(function ($item) {
                    if (is_array($item)) {
                        $text = trim((string) ($item['text'] ?? ''));
                        $subtitle = trim((string) ($item['subtitle'] ?? ''));
                        $points = (float) ($item['default_points'] ?? 0);
                        $subItems = collect((array) ($item['sub_items'] ?? []))
                            ->map(function ($subItem) {
                                if (is_array($subItem)) {
                                    $subText = trim((string) ($subItem['text'] ?? ''));
                                    $subPoints = (float) ($subItem['default_points'] ?? 0);
                                } else {
                                    $subText = trim((string) $subItem);
                                    $subPoints = 0;
                                }

                                if ($subText === '') {
                                    return null;
                                }

                                return [
                                    'text' => $subText,
                                    'default_points' => max(0, $subPoints),
                                ];
                            })
                            ->filter()
                            ->values()
                            ->all();
                    } else {
                        $text = trim((string) $item);
                        $subtitle = '';
                        $points = 0;
                        $subItems = [];
                    }

                    if ($text === '') {
                        return null;
                    }

                    $normalizedItem = [
                        'text' => $text,
                        'default_points' => max(0, $points),
                    ];

                    if ($subtitle !== '') {
                        $normalizedItem['subtitle'] = $subtitle;
                    }

                    if (!empty($subItems)) {
                        $normalizedItem['sub_items'] = $subItems;
                    }

                    return $normalizedItem;
                })
                ->filter()
                ->values()
                ->all();

            if ($key === '' || $title === '' || empty($items)) {
                continue;
            }

            $cleaned[$key] = [
                'title' => $title,
                'division' => $division,
                'division_label' => $divisionLabel,
                'items' => $items,
            ];
        }

        if (empty($cleaned)) {
            return redirect()
                ->route('comprehensive-school-safety.school.assessments.questionnaire.edit', $school->id)
                ->with('error', 'Questionnaire must contain at least one section with one question.');
        }

        SchoolSpecificsInformation::updateOrCreate(
            [
                'school_id' => $school->id,
                'module' => 'comprehensive',
                'key' => self::QUESTIONNAIRE_TEMPLATE_KEY,
            ],
            [
                'value' => json_encode($cleaned),
            ]
        );

        ActivityLog::log('comprehensive_school_safety', 'Updated assessment questionnaire template', [
            'school_id' => $school->id,
            'notes' => 'Sections updated: ' . (string) count($cleaned),
        ]);

        return redirect()
            ->route('comprehensive-school-safety.school.assessments', $school->id)
            ->with('success', 'Assessment questionnaire updated.');
    }

    public function schoolStudents($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $students = $school->students()->latest()->paginate(15);
        $studentImportAttachments = SchoolSpecificsInformation::query()
            ->where('school_id', $school->id)
            ->where('module', 'comprehensive')
            ->where('key', self::STUDENT_IMPORT_ATTACHMENTS_KEY)
            ->first();

        $attachments = collect(json_decode($studentImportAttachments->value ?? '[]', true) ?: []);

        return view('comprehensive-school-safety.school-students', compact('school', 'students', 'attachments'));
    }

    public function storeStudent(Request $request, $schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'student_lrn' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $student = ComprehensiveStudent::create([
            'school_id' => $school->id,
            'name' => $validated['student_name'],
            'grade_level' => $validated['grade_level'] ?? null,
            'section' => $validated['section'] ?? null,
            'student_lrn' => $validated['student_lrn'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_contact' => $validated['guardian_contact'] ?? null,
        ]);

        ActivityLog::log('comprehensive_school_safety', 'Added student: ' . ($student->name ?? $validated['student_name']), [
            'school_id' => $school->id,
            'notes' => 'Grade: ' . ($student->grade_level ?? 'N/A') . ', Section: ' . ($student->section ?? 'N/A'),
        ]);

        return redirect()
            ->route('comprehensive-school-safety.school.students', $school->id)
            ->with('success', 'Student added successfully.');
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        return trim($value, '_');
    }

    private function resolveHeaderValue(array $row, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $key = $this->normalizeHeader($candidate);
            if (array_key_exists($key, $row)) {
                $val = trim((string) $row[$key]);
                return $val === '' ? null : $val;
            }
        }

        return null;
    }

    private function parseDelimitedStudentsFile(UploadedFile $file): array
    {
        $rows = [];
        $headers = [];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }

        while (($raw = fgetcsv($handle)) !== false) {
            if (empty($headers)) {
                $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $raw);
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = isset($raw[$index]) ? trim((string) $raw[$index]) : null;
            }

            if (collect($assoc)->filter(fn ($v) => !is_null($v) && $v !== '')->isEmpty()) {
                continue;
            }

            $rows[] = $assoc;
        }

        fclose($handle);

        return $rows;
    }

    private function parseXlsxStudentsFile(UploadedFile $file): array
    {
        $xlsx = SimpleXLSX::parse($file->getRealPath());
        if ($xlsx === false) {
            return [];
        }

        $allRows = $xlsx->rows();
        if (empty($allRows)) {
            return [];
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), (array) ($allRows[0] ?? []));
        $rows = [];

        foreach (array_slice($allRows, 1) as $raw) {
            $assoc = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = isset($raw[$index]) ? trim((string) $raw[$index]) : null;
            }

            if (collect($assoc)->filter(fn ($v) => !is_null($v) && $v !== '')->isEmpty()) {
                continue;
            }

            $rows[] = $assoc;
        }

        return $rows;
    }

    private function parseStudentsImportFile(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === 'xlsx') {
            return $this->parseXlsxStudentsFile($file);
        }

        return $this->parseDelimitedStudentsFile($file);
    }

    public function importStudents(Request $request, $schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $validated = $request->validate([
            'student_list_file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $rows = $this->parseStudentsImportFile($validated['student_list_file']);
        if (empty($rows)) {
            return redirect()
                ->route('comprehensive-school-safety.school.students', $school->id)
                ->with('error', 'No student rows were found in the imported file.');
        }

        $hasFirstName = Schema::hasColumn('cmpr_schl_sfty_students', 'first_name');
        $hasMiddleName = Schema::hasColumn('cmpr_schl_sfty_students', 'middle_name');
        $hasLastName = Schema::hasColumn('cmpr_schl_sfty_students', 'last_name');
        $hasName = Schema::hasColumn('cmpr_schl_sfty_students', 'name');

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $studentLrn = $this->resolveHeaderValue($row, ['student_lrn', 'lrn', 'student lrn', 'learner reference number']);
            $firstName = $this->resolveHeaderValue($row, ['first_name', 'firstname', 'first name', 'given_name']);
            $middleName = $this->resolveHeaderValue($row, ['middle_name', 'middlename', 'middle name', 'mi']);
            $lastName = $this->resolveHeaderValue($row, ['last_name', 'lastname', 'last name', 'surname']);
            $fullName = $this->resolveHeaderValue($row, ['name', 'student_name', 'student name']);
            $gradeLevel = $this->resolveHeaderValue($row, ['grade_level', 'grade', 'grade level']);
            $section = $this->resolveHeaderValue($row, ['section', 'class_section', 'class section']);
            $guardianName = $this->resolveHeaderValue($row, ['guardian_name', 'guardian', 'guardian name', 'parent_name']);
            $guardianContact = $this->resolveHeaderValue($row, ['guardian_contact', 'guardian contact', 'contact_number', 'guardian phone']);

            if (!$fullName) {
                $fullName = trim(collect([$firstName, $middleName, $lastName])->filter()->implode(' '));
            }

            if (!$fullName) {
                $fullName = 'Unnamed Student';
            }

            $payload = [
                'school_id' => $school->id,
                'student_lrn' => $studentLrn,
                'grade_level' => $gradeLevel,
                'section' => $section,
                'guardian_name' => $guardianName,
                'guardian_contact' => $guardianContact,
            ];

            if ($hasFirstName) {
                $payload['first_name'] = $firstName;
            }
            if ($hasMiddleName) {
                $payload['middle_name'] = $middleName;
            }
            if ($hasLastName) {
                $payload['last_name'] = $lastName;
            }
            if ($hasName) {
                $payload['name'] = $fullName;
            }

            if ($studentLrn) {
                $student = ComprehensiveStudent::where('school_id', $school->id)
                    ->where('student_lrn', $studentLrn)
                    ->first();

                if ($student) {
                    $student->update($payload);
                    $updated++;
                } else {
                    ComprehensiveStudent::create($payload);
                    $created++;
                }
            } else {
                ComprehensiveStudent::create($payload);
                $created++;
            }
        }

        // Keep import-file history, while manual attachment uploads are removed.
        $historyRecord = SchoolSpecificsInformation::firstOrCreate(
            [
                'school_id' => $school->id,
                'module' => 'comprehensive',
                'key' => self::STUDENT_IMPORT_ATTACHMENTS_KEY,
            ],
            [
                'value' => '[]',
            ]
        );

        $history = collect(json_decode($historyRecord->value ?? '[]', true) ?: []);
        $history->prepend([
            'name' => $validated['student_list_file']->getClientOriginalName(),
            'url' => null,
            'uploaded_at' => now()->toDateTimeString(),
        ]);

        $historyRecord->value = $history->take(30)->values()->toJson();
        $historyRecord->save();

        ActivityLog::log('comprehensive_school_safety', 'Imported student list file: ' . $validated['student_list_file']->getClientOriginalName(), [
            'school_id' => $school->id,
            'notes' => 'Added: ' . (string) $created . ', Updated: ' . (string) $updated,
        ]);

        return redirect()
            ->route('comprehensive-school-safety.school.students', $school->id)
            ->with('success', "Student list imported. {$created} added, {$updated} updated.");
    }

    public function updateStudent(Request $request, $schoolId, $studentId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $student = ComprehensiveStudent::where('school_id', $school->id)->findOrFail((int) $studentId);

        $validated = $request->validate([
            'student_lrn' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [];
        $columns = [
            'student_lrn',
            'first_name',
            'middle_name',
            'last_name',
            'grade_level',
            'section',
            'guardian_name',
            'guardian_contact',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('cmpr_schl_sfty_students', $column)) {
                $payload[$column] = $validated[$column] ?? null;
            }
        }

        if (Schema::hasColumn('cmpr_schl_sfty_students', 'name')) {
            $payload['name'] = trim(collect([
                $validated['first_name'] ?? null,
                $validated['middle_name'] ?? null,
                $validated['last_name'] ?? null,
            ])->filter()->implode(' '));
        }

        $student->update($payload);

        ActivityLog::log('comprehensive_school_safety', 'Updated student record: ' . ($student->name ?? ('Student #' . $student->id)), [
            'school_id' => $school->id,
            'notes' => 'Grade: ' . ($student->grade_level ?? 'N/A') . ', Section: ' . ($student->section ?? 'N/A'),
        ]);

        return redirect()
            ->route('comprehensive-school-safety.school.students', $school->id)
            ->with('success', 'Student record updated.');
    }

    public function exportStudents($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $studentsQuery = $school->students();

        if (Schema::hasColumn('cmpr_schl_sfty_students', 'last_name')) {
            $studentsQuery->orderBy('last_name');
        }
        if (Schema::hasColumn('cmpr_schl_sfty_students', 'first_name')) {
            $studentsQuery->orderBy('first_name');
        }
        if (Schema::hasColumn('cmpr_schl_sfty_students', 'name')) {
            $studentsQuery->orderBy('name');
        }

        $students = $studentsQuery->get();

        $filename = 'students_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($school->name ?? 'school')) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student LRN', 'First Name', 'Middle Name', 'Last Name', 'Grade Level', 'Section', 'Guardian Name', 'Guardian Contact']);

            foreach ($students as $student) {
                fputcsv($out, [
                    $student->student_lrn,
                    $student->first_name,
                    $student->middle_name,
                    $student->last_name,
                    $student->grade_level,
                    $student->section,
                    $student->guardian_name,
                    $student->guardian_contact,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function schoolFacilities($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $this->ensureAcademicYearArchives($school);
        $fireSafetyBuildings = $school->buildings()->latest()->get();
        $roomsByBuilding = $school->rooms()
            ->orderBy('building_id')
            ->orderByRaw('COALESCE(floor_no, 1)')
            ->orderBy('room_code')
            ->get()
            ->groupBy('building_id');
        $fireSafetyPlan = $school->schoolEvacuationPlan;
        $facilities = $school->facilities()->latest()->get();
        $summaryFindings = ComprehensiveSummaryFinding::query()
            ->where('school_id', $school->id)
            ->latest('observation_date')
            ->latest()
            ->get();

        $riskRegister = $fireSafetyBuildings->map(function ($building) use ($fireSafetyPlan, $school) {
            $riskLevel = $building->safetyStatus;

            return [
                'building_id' => $building->id,
                'title' => $building->building_name ?? ('Building ' . $building->building_no),
                'status' => $building->safetyStatusLabel,
                'color' => $building->safetyStatusColor,
                'floors' => (int) ($building->floors ?? 0),
                'rooms' => (int) ($building->rooms ?? 0),
                'extinguishers' => (int) ($building->active_extinguishers_count ?? 0),
                'alarms' => (int) ($building->functional_alarms_count ?? 0),
                'exits' => (int) ($building->emergency_exits ?? 0),
                'description' => trim((string) ($building->description ?? '')),
                'needs_attention' => $riskLevel !== 'good' || !$building->evacuationPlan,
                'manage_url' => route('fire-safety.buildings', [
                    'school_id' => $school->id,
                    'building_id' => $building->id,
                ]),
            ];
        })->sortByDesc('needs_attention')->values();

        $assessmentSummary = [
            'building_count' => $fireSafetyBuildings->count(),
            'average_score' => $fireSafetyBuildings->count() > 0 ? round($fireSafetyBuildings->avg('safety_score'), 1) : 0,
            'good_count' => $fireSafetyBuildings->where('safetyStatus', 'good')->count(),
            'fair_count' => $fireSafetyBuildings->where('safetyStatus', 'fair')->count(),
            'poor_count' => $fireSafetyBuildings->where('safetyStatus', 'poor')->count(),
        ];

        $defaultConcernCategories = [
            'General Safety',
            'Electrical',
            'Storage/Space',
            'Structural',
            'Pathways',
            'Accessibilit',
            'Accessibility',
            'Sanitation/Utility',
            'External Grounds',
            'Others Please Specify',
        ];

        $concernCategoryOptions = collect($defaultConcernCategories)
            ->merge($summaryFindings->pluck('concern_category'))
            ->filter(fn ($v) => trim((string) $v) !== '')
            ->map(fn ($v) => trim((string) $v))
            ->unique()
            ->values();

        $concernTypeOptions = $summaryFindings->pluck('concern_type')
            ->filter(fn ($v) => trim((string) $v) !== '')
            ->map(fn ($v) => trim((string) $v))
            ->unique()
            ->values();

        $findingsByBuilding = $summaryFindings->groupBy('building_id');

        return view('comprehensive-school-safety.school-facilities', compact(
            'school',
            'fireSafetyBuildings',
            'fireSafetyPlan',
            'assessmentSummary',
            'riskRegister',
            'roomsByBuilding',
            'facilities',
            'summaryFindings',
            'findingsByBuilding',
            'concernCategoryOptions',
            'concernTypeOptions'
        ));
    }

    public function storeSummaryFinding(Request $request, $schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $hasFloorNumber = Schema::hasColumn('cmpr_schl_sfty_sumFindings', 'floor_number');
        $hasRoomCode = Schema::hasColumn('cmpr_schl_sfty_sumFindings', 'room_code');
        $hasInsideDetails = Schema::hasColumn('cmpr_schl_sfty_sumFindings', 'chairs_count');

        $validated = $request->validate([
            'building_id' => ['required', 'integer', 'exists:firesafety_buildings,id'],
            'concern_category' => ['required', 'string', 'max:255'],
            'other_concern_category' => ['nullable', 'string', 'max:255'],
            'concern_type' => ['required', 'string', 'max:255'],
            'other_concern_type' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:high,medium,low'],
            'observation_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'floor_number' => ['nullable', 'integer', 'min:1', 'max:50'],
            'room_code' => ['nullable', 'string', 'max:100'],
            'chairs_count' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'tables_count' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'tv_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'electric_fan_count' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'ceiling_fan_count' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'water_dispenser_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'window_type' => ['nullable', 'string', 'max:255'],
        ]);

        $building = $school->buildings()->where('id', (int) $validated['building_id'])->first();
        if (!$building) {
            return redirect()
                ->route('comprehensive-school-safety.school.facilities', $school->id)
                ->with('error', 'Invalid building selected for this school.');
        }

        $category = trim((string) $validated['concern_category']);
        if (($category === '__other__' || strcasecmp($category, 'Others Please Specify') === 0) && !empty($validated['other_concern_category'])) {
            $category = trim((string) $validated['other_concern_category']);
        }

        $concernType = trim((string) $validated['concern_type']);
        if ($concernType === '__other__' && !empty($validated['other_concern_type'])) {
            $concernType = trim((string) $validated['other_concern_type']);
        }

        $payload = [
            'school_id' => $school->id,
            'building_id' => $building->id,
            'concern_category' => $category,
            'concern_type' => $concernType,
            'description' => $validated['description'],
            'priority' => strtolower((string) $validated['priority']),
            'observation_date' => $validated['observation_date'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        if ($hasFloorNumber) {
            $payload['floor_number'] = !empty($validated['floor_number']) ? (int) $validated['floor_number'] : null;
        }

        if ($hasRoomCode) {
            $payload['room_code'] = !empty($validated['room_code']) ? trim((string) $validated['room_code']) : null;
        }

        if ($hasInsideDetails) {
            $payload['chairs_count'] = (int) ($validated['chairs_count'] ?? 0);
            $payload['tables_count'] = (int) ($validated['tables_count'] ?? 0);
            $payload['tv_count'] = (int) ($validated['tv_count'] ?? 0);
            $payload['electric_fan_count'] = (int) ($validated['electric_fan_count'] ?? 0);
            $payload['ceiling_fan_count'] = (int) ($validated['ceiling_fan_count'] ?? 0);
            $payload['water_dispenser_count'] = (int) ($validated['water_dispenser_count'] ?? 0);
            $payload['window_type'] = !empty($validated['window_type']) ? trim((string) $validated['window_type']) : null;
        }

        $finding = ComprehensiveSummaryFinding::create($payload);

        ActivityLog::log('comprehensive_school_safety', 'Added summary finding for ' . ($building->building_name ?? ('Building ' . $building->building_no)), [
            'school_id' => $school->id,
            'notes' => 'Category: ' . $finding->concern_category . ', Type: ' . $finding->concern_type . ', Priority: ' . strtoupper((string) $finding->priority) .
                (!empty($finding->room_code) ? (', Room: ' . $finding->room_code) : ''),
        ]);

        return redirect()
            ->route('comprehensive-school-safety.school.facilities', $school->id)
            ->with('success', 'Summary finding saved successfully.');
    }

    public function destroySummaryFinding($schoolId, $findingId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $finding = ComprehensiveSummaryFinding::query()
            ->where('school_id', $school->id)
            ->findOrFail((int) $findingId);

        $notes = 'Category: ' . ($finding->concern_category ?? 'N/A') . ', Type: ' . ($finding->concern_type ?? 'N/A');
        $finding->delete();

        ActivityLog::log('comprehensive_school_safety', 'Deleted summary finding', [
            'school_id' => $school->id,
            'notes' => $notes,
        ]);

        return redirect()
            ->route('comprehensive-school-safety.school.facilities', $school->id)
            ->with('success', 'Summary finding deleted.');
    }

    public function updateSummaryFinding(Request $request, $schoolId, $findingId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $finding = ComprehensiveSummaryFinding::query()
            ->where('school_id', $school->id)
            ->findOrFail((int) $findingId);

        $hasFloorNumber = Schema::hasColumn('cmpr_schl_sfty_sumFindings', 'floor_number');
        $hasRoomCode = Schema::hasColumn('cmpr_schl_sfty_sumFindings', 'room_code');
        $hasInsideDetails = Schema::hasColumn('cmpr_schl_sfty_sumFindings', 'chairs_count');

        $validated = $request->validate([
            'building_id' => ['required', 'integer', 'exists:firesafety_buildings,id'],
            'concern_category' => ['required', 'string', 'max:255'],
            'other_concern_category' => ['nullable', 'string', 'max:255'],
            'concern_type' => ['required', 'string', 'max:255'],
            'other_concern_type' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:high,medium,low'],
            'observation_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'floor_number' => ['nullable', 'integer', 'min:1', 'max:50'],
            'room_code' => ['nullable', 'string', 'max:100'],
            'chairs_count' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'tables_count' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'tv_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'electric_fan_count' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'ceiling_fan_count' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'water_dispenser_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'window_type' => ['nullable', 'string', 'max:255'],
        ]);

        $building = $school->buildings()->where('id', (int) $validated['building_id'])->first();
        if (!$building) {
            return redirect()
                ->route('comprehensive-school-safety.school.facilities', $school->id)
                ->with('error', 'Invalid building selected for this school.');
        }

        $category = trim((string) $validated['concern_category']);
        if (($category === '__other__' || strcasecmp($category, 'Others Please Specify') === 0) && !empty($validated['other_concern_category'])) {
            $category = trim((string) $validated['other_concern_category']);
        }

        $concernType = trim((string) $validated['concern_type']);
        if ($concernType === '__other__' && !empty($validated['other_concern_type'])) {
            $concernType = trim((string) $validated['other_concern_type']);
        }

        $payload = [
            'building_id' => $building->id,
            'concern_category' => $category,
            'concern_type' => $concernType,
            'description' => $validated['description'],
            'priority' => strtolower((string) $validated['priority']),
            'observation_date' => $validated['observation_date'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        if ($hasFloorNumber) {
            $payload['floor_number'] = !empty($validated['floor_number']) ? (int) $validated['floor_number'] : null;
        }

        if ($hasRoomCode) {
            $payload['room_code'] = !empty($validated['room_code']) ? trim((string) $validated['room_code']) : null;
        }

        if ($hasInsideDetails) {
            $payload['chairs_count'] = (int) ($validated['chairs_count'] ?? 0);
            $payload['tables_count'] = (int) ($validated['tables_count'] ?? 0);
            $payload['tv_count'] = (int) ($validated['tv_count'] ?? 0);
            $payload['electric_fan_count'] = (int) ($validated['electric_fan_count'] ?? 0);
            $payload['ceiling_fan_count'] = (int) ($validated['ceiling_fan_count'] ?? 0);
            $payload['water_dispenser_count'] = (int) ($validated['water_dispenser_count'] ?? 0);
            $payload['window_type'] = !empty($validated['window_type']) ? trim((string) $validated['window_type']) : null;
        }

        $finding->update($payload);

        ActivityLog::log('comprehensive_school_safety', 'Updated summary finding', [
            'school_id' => $school->id,
            'notes' => 'Category: ' . ($finding->concern_category ?? 'N/A') . ', Type: ' . ($finding->concern_type ?? 'N/A') . ', Priority: ' . strtoupper((string) $finding->priority),
        ]);

        return redirect()
            ->route('comprehensive-school-safety.school.facilities', $school->id)
            ->with('success', 'Summary finding updated successfully.');
    }

    public function storeFacility(Request $request, $schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:commercial,industrial,residential,educational,public/institutional,assembly_area'],
            'condition' => ['required', 'string', 'in:excellent,good,fair,poor,critical'],
            'description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $facility = ComprehensiveFacility::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'type' => $validated['type'] ?? 'public/institutional',
            'condition' => $validated['condition'],
            'description' => $validated['description'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLog::log('comprehensive_school_safety', 'Added facility: ' . $facility->name, [
            'school_id' => $school->id,
            'notes' => 'Type: ' . ($facility->type ?? 'N/A') . ', Condition: ' . ($facility->condition ?? 'N/A'),
        ]);

        $this->snapshotFacilityArchiveForAcademicYear($school);

        return redirect()
            ->route('comprehensive-school-safety.school.facilities', $school->id)
            ->with('success', 'Facility added successfully.');
    }

    public function updateFacility(Request $request, $schoolId, $facilityId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $facility = ComprehensiveFacility::query()
            ->where('school_id', $school->id)
            ->findOrFail((int) $facilityId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:commercial,industrial,residential,educational,public/institutional,assembly_area'],
            'condition' => ['required', 'string', 'in:excellent,good,fair,poor,critical'],
            'description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $facility->update([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? ($facility->type ?? 'public/institutional'),
            'condition' => $validated['condition'],
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $facility->description,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLog::log('comprehensive_school_safety', 'Updated facility: ' . $facility->name, [
            'school_id' => $school->id,
            'notes' => 'Type: ' . ($facility->type ?? 'N/A') . ', Condition: ' . ($facility->condition ?? 'N/A'),
        ]);

        $this->snapshotFacilityArchiveForAcademicYear($school);

        return redirect()
            ->route('comprehensive-school-safety.school.facilities', $school->id)
            ->with('success', 'Facility updated successfully.');
    }

    public function schoolReports($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $this->ensureAcademicYearArchives($school);

        $currentAcademicYear = $this->currentAcademicYearLabel();
        $assessments = $school->assessments()
            ->latest()
            ->get(['id', 'date_visited', 'assessed_by', 'total_score', 'status', 'academic_year']);

        if (Schema::hasColumn('cmpr_schl_sfty_assessments', 'academic_year')) {
            $assessments = $assessments->where('academic_year', $currentAcademicYear)->values();
        }

        $reportStats = [
            'assessments_completed' => $school->assessments()->where('status', 'completed')->count(),
            'total_assessments' => $school->assessments()->count(),
            'total_students' => $school->students()->count(),
            'total_facilities' => $school->facilities()->count(),
            'total_storage_items' => $school->storageItems()->count(),
        ];

        $allItems = ComprehensiveAssessmentItem::query()
            ->whereIn('assessment_id', $assessments->pluck('id'))
            ->get();

        $compliantCount = $allItems->where('is_compliant', true)->count();
        $nonCompliantCount = $allItems->where('is_compliant', false)->count();
        $totalReviewed = $compliantCount + $nonCompliantCount;

        $assessmentPreview = [
            'latest_code' => $assessments->isNotEmpty() ? $this->getAssessmentCode((int) $assessments->first()->id) : 'N/A',
            'latest_date' => $assessments->isNotEmpty() ? ($this->formatDateValue($assessments->first()->date_visited, 'M d, Y') ?: 'N/A') : 'N/A',
            'average_score' => $assessments->isNotEmpty() ? round((float) $assessments->avg('total_score'), 2) : 0,
            'compliance_rate' => $totalReviewed > 0 ? round(($compliantCount / $totalReviewed) * 100, 1) : 0,
            'total_reviewed' => $totalReviewed,
        ];

        $buildings = $school->buildings()->latest()->get();
        $summaryFindings = ComprehensiveSummaryFinding::query()
            ->where('school_id', $school->id)
            ->latest('observation_date')
            ->latest()
            ->get();

        $safetyIndexPreview = [
            'average_score' => $buildings->isNotEmpty() ? round((float) $buildings->avg('safety_score'), 1) : 0,
            'building_count' => $buildings->count(),
            'high_findings' => $summaryFindings->where('priority', 'high')->count(),
            'medium_findings' => $summaryFindings->where('priority', 'medium')->count(),
            'low_findings' => $summaryFindings->where('priority', 'low')->count(),
        ];

        $archives = collect();
        if (Schema::hasTable('cmpr_schl_sfty_archives')) {
            $archives = ComprehensiveArchive::query()
                ->where('school_id', $school->id)
                ->orderByDesc('academic_year')
                ->orderByDesc('archived_at')
                ->get();
        }

        return view('comprehensive-school-safety.school-reports', compact(
            'school',
            'reportStats',
            'assessments',
            'currentAcademicYear',
            'assessmentPreview',
            'safetyIndexPreview',
            'archives'
        ));
    }

    public function printAssessmentReport(Request $request, $schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $assessmentId = (int) $request->query('assessment_id');
        $assessment = $school->assessments()
            ->with('items')
            ->where('id', $assessmentId)
            ->first();

        if (!$assessment) {
            return redirect()
                ->route('comprehensive-school-safety.school.reports', $school->id)
                ->with('error', 'Please select a valid assessment to print.');
        }

        $criteriaRows = $assessment->items
            ->sortBy('id')
            ->map(function ($item) {
                $parsed = $this->parseStoredCategory((string) $item->category);
                return [
                    'category' => $parsed['title'] ?? 'Section',
                    'criteria' => $item->criteria,
                    'is_compliant' => $item->is_compliant,
                    'points' => $item->points,
                    'remarks' => $item->remarks,
                ];
            })
            ->values();

        ActivityLog::log('comprehensive_school_safety', 'Printed assessment report: CSSS-' . str_pad((string) $assessment->id, 4, '0', STR_PAD_LEFT), [
            'school_id' => $school->id,
        ]);

        return view('comprehensive-school-safety.reports.assessment-report', compact('school', 'assessment', 'criteriaRows'));
    }

    public function printSafetyIndexReport($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $buildings = $school->buildings()->latest()->get();
        $summaryFindings = ComprehensiveSummaryFinding::query()
            ->where('school_id', $school->id)
            ->latest('observation_date')
            ->latest()
            ->get();

        $indexStats = [
            'building_count' => $buildings->count(),
            'average_score' => $buildings->count() > 0 ? round($buildings->avg('safety_score'), 1) : 0,
            'good_count' => $buildings->where('safetyStatus', 'good')->count(),
            'fair_count' => $buildings->where('safetyStatus', 'fair')->count(),
            'poor_count' => $buildings->where('safetyStatus', 'poor')->count(),
            'high_findings' => $summaryFindings->where('priority', 'high')->count(),
            'medium_findings' => $summaryFindings->where('priority', 'medium')->count(),
            'low_findings' => $summaryFindings->where('priority', 'low')->count(),
        ];

        ActivityLog::log('comprehensive_school_safety', 'Printed safety index report', [
            'school_id' => $school->id,
        ]);

        return view('comprehensive-school-safety.reports.safety-index-report', compact('school', 'buildings', 'summaryFindings', 'indexStats'));
    }

    public function printTimelineReport($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $this->ensureAcademicYearArchives($school);

        $assessmentEvents = $school->assessments()
            ->latest('date_visited')
            ->get()
            ->map(function ($assessment) {
                return [
                    'event_date' => $assessment->date_visited,
                    'event_type' => 'Assessment',
                    'title' => 'Assessment CSSS-' . str_pad((string) $assessment->id, 4, '0', STR_PAD_LEFT),
                    'details' => 'Assessed by: ' . ($assessment->assessed_by ?? 'N/A') . ' | Score: ' . ($assessment->total_score ?? 0),
                ];
            });

        $findingEvents = ComprehensiveSummaryFinding::query()
            ->where('school_id', $school->id)
            ->latest('observation_date')
            ->get()
            ->map(function ($finding) {
                return [
                    'event_date' => $finding->observation_date,
                    'event_type' => 'Summary Finding',
                    'title' => ($finding->concern_category ?? 'Concern') . ' - ' . ($finding->concern_type ?? 'Type'),
                    'details' => 'Priority: ' . strtoupper((string) $finding->priority) . ' | ' . (string) $finding->description,
                ];
            });

        $timelineEvents = $assessmentEvents
            ->concat($findingEvents)
            ->sortByDesc(function ($event) {
                return strtotime((string) $event['event_date']);
            })
            ->values();

        $archiveEvents = collect();
        if (Schema::hasTable('cmpr_schl_sfty_archives')) {
            $archiveEvents = ComprehensiveArchive::query()
                ->where('school_id', $school->id)
                ->orderByDesc('academic_year')
                ->get()
                ->map(function ($archive) {
                    $payload = is_array($archive->payload) ? $archive->payload : [];
                    $typeLabel = $archive->archive_type === 'facility' ? 'Facility Records' : 'Assessment History';
                    $summaryText = $archive->archive_type === 'facility'
                        ? 'Facility records captured: ' . (int) ($payload['facility_count'] ?? 0)
                        : 'Assessments archived: ' . (int) ($payload['assessment_count'] ?? 0) . ' | Average score: ' . (float) ($payload['average_score'] ?? 0);

                    return [
                        'event_date' => $this->formatDateValue($archive->archived_at) ?: now()->toDateString(),
                        'event_type' => 'Archive Snapshot',
                        'title' => $archive->academic_year . ' - ' . $typeLabel,
                        'details' => $summaryText,
                    ];
                });
        }

        $timelineEvents = $timelineEvents
            ->concat($archiveEvents)
            ->sortByDesc(function ($event) {
                return strtotime((string) $event['event_date']);
            })
            ->values();

        ActivityLog::log('comprehensive_school_safety', 'Printed timeline report', [
            'school_id' => $school->id,
        ]);

        return view('comprehensive-school-safety.reports.timeline-report', compact('school', 'timelineEvents'));
    }

    public function schoolStorage($schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $storageItems = $school->storageItems()->latest()->get();

        return view('comprehensive-school-safety.school-storage', compact('school', 'storageItems'));
    }

    public function storeStorageItem(Request $request, $schoolId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'source_from' => ['nullable', 'string', 'max:255'],
            'is_available' => ['nullable', 'boolean'],
            'is_functional' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $item = ComprehensiveStorage::create([
            'school_id' => $school->id,
            'item_name' => $validated['item_name'],
            'item_type' => $validated['item_type'] ?? null,
            'source_from' => $validated['source_from'] ?? null,
            'is_available' => (bool) ($request->boolean('is_available')),
            'is_functional' => (bool) ($request->boolean('is_functional')),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLog::log('comprehensive_school_safety', 'Added storage item: ' . $item->item_name, [
            'school_id' => $school->id,
            'notes' => 'Type: ' . ($item->item_type ?? 'N/A') . ', From: ' . ($item->source_from ?? 'N/A') . ', Available: ' . ($item->is_available ? 'Yes' : 'No') . ', Functional: ' . ($item->is_functional ? 'Yes' : 'No'),
        ]);

        return redirect()
            ->route('#', $school->id)
            ->with('success', 'Storage item added successfully.');
    }

    public function updateStorageItem(Request $request, $schoolId, $storageId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $item = ComprehensiveStorage::query()
            ->where('school_id', $school->id)
            ->findOrFail((int) $storageId);

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'source_from' => ['nullable', 'string', 'max:255'],
            'is_available' => ['nullable', 'boolean'],
            'is_functional' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $item->update([
            'item_name' => $validated['item_name'],
            'item_type' => $validated['item_type'] ?? null,
            'source_from' => $validated['source_from'] ?? null,
            'is_available' => (bool) $request->boolean('is_available'),
            'is_functional' => (bool) $request->boolean('is_functional'),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLog::log('comprehensive_school_safety', 'Updated storage item: ' . $item->item_name, [
            'school_id' => $school->id,
            'notes' => 'Type: ' . ($item->item_type ?? 'N/A') . ', From: ' . ($item->source_from ?? 'N/A') . ', Available: ' . ($item->is_available ? 'Yes' : 'No') . ', Functional: ' . ($item->is_functional ? 'Yes' : 'No'),
        ]);

        return redirect()
            ->route('#', $school->id)
            ->with('success', 'Storage item updated.');
    }

    public function destroyStorageItem($schoolId, $storageId)
    {
        $school = $this->findSchoolOrAbortForUser((int) $schoolId);
        $item = ComprehensiveStorage::query()
            ->where('school_id', $school->id)
            ->findOrFail((int) $storageId);

        $name = $item->item_name;
        $item->delete();

        ActivityLog::log('comprehensive_school_safety', 'Deleted storage item: ' . $name, [
            'school_id' => $school->id,
        ]);

        return redirect()
            ->route('#', $school->id)
            ->with('success', 'Storage item removed.');
    }
}
