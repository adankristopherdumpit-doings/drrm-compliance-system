@extends('layouts.fire-safety')

@section('title', 'Fire Safety Dashboard')

@section('styles')
    <style>
        /* Cards */
        .dashboard-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
@endsection

@section('page_title', 'Dashboard')

@section('content')
        @if(auth()->user()->role === 'admin')
        <!-- Summary Cards (Admin Only) -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card stat-card border-left-success h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Passed Inspections</div>
                                <div class="h2 mb-0 fw-bold text-gray-800">{{ $schools->where('status', 'passed')->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card stat-card border-left-warning h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">Ongoing Improvement</div>
                                <div class="h2 mb-0 fw-bold text-gray-800">{{ $schools->where('status', 'warning')->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tools fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card stat-card border-left-secondary h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Unconfigured Schools</div>
                                <div class="h2 mb-0 fw-bold text-gray-800">{{ $schools->where('status', 'unconfigured')->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-cog fa-2x text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card stat-card border-left-dark h-100" style="border-left: 0.25rem solid #212529 !important;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-dark text-uppercase mb-1">Total Schools</div>
                                <div class="h2 mb-0 fw-bold text-gray-800">{{ $schools->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-school fa-2x text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- School Safety Status Section -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card dashboard-card mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">School Safety Status</h6>
                        <div class="d-flex gap-2 align-items-center">
                            @if(auth()->user()->role === 'admin')
                                <input id="schoolSearchInput" type="search" class="form-control form-control-sm" placeholder="Search school name..." style="width: 220px;">
                                <select id="statusFilter" class="form-select form-select-sm" style="width: auto;">
                                    <option value="all">All Status</option>
                                    <option value="passed">Passed</option>
                                    <option value="warning">Ongoing Improvement</option>
                                    <option value="unconfigured">Unconfigured</option>
                                </select>
                                <select id="sortFilter" class="form-select form-select-sm" style="width: auto;">
                                    <option value="name_asc">School Name (A-Z)</option>
                                    <option value="name_desc">School Name (Z-A)</option>
                                    <option value="inspection_asc">Last Inspection (Oldest)</option>
                                    <option value="inspection_desc">Last Inspection (Newest)</option>
                                </select>
                            @endif

                            <a href="{{ route('fire-safety.report.school-summary') }}" target="_blank" class="btn btn-success flex-grow-1 flex-md-grow-0">
                                <i class="fas fa-file-pdf me-2"></i> Generate Report
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8">
                                @if(auth()->user()->role === 'admin')
                                    <!-- Table View for Admins -->
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>School</th>
                                                    <th>Status</th>
                                                    <th>Issues</th>
                                                    <th>Last Inspection</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="schoolsTableBody">
                                                @forelse($schools as $school)
                                                    <tr data-status="{{ $school->status }}"
                                                        data-school-name="{{ $school->school_name }}"
                                                        data-inspection-date="{{ $school->last_inspection_date ? \Carbon\Carbon::parse($school->last_inspection_date)->format('Y-m-d') : '1900-01-01' }}">
                                                        <td>
                                                            <strong>{{ $school->school_name }}</strong>
                                                            <div class="text-muted small">ID: {{ $school->school_id }}</div>
                                                        </td>
                                                        <td>
                                                            @if($school->status === 'passed')
                                                                <span class="status-badge bg-success" style="font-size: 0.7rem;">PASSED</span>
                                                            @elseif($school->status === 'unconfigured')
                                                                <span class="status-badge bg-secondary" style="font-size: 0.7rem;">UNCONFIGURED</span>
                                                            @else
                                                                <span class="status-badge bg-warning text-dark" style="font-size: 0.7rem;">ONGOING IMPROVEMENT</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div style="font-size: 0.85rem; max-width: 250px;">
                                                                @if($school->status === 'unconfigured')
                                                                    <span class="text-primary fw-bold">Setup Needed</span>
                                                                @elseif ($school->status === 'passed')
                                                                    <span class="text-success">None</span>
                                                                @else
                                                                    <div class="d-flex flex-wrap gap-1">
                                                                        @php $fontSize = count($school->issues_list) > 3 ? '0.7rem' : '0.8rem'; @endphp
                                                                        @foreach($school->issues_list as $issue)
                                                                            <span class="badge bg-light text-danger border" style="font-size: {{ $fontSize }};">{{ $issue }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if($school->last_inspection_date)
                                                                <div class="small">{{ \Carbon\Carbon::parse($school->last_inspection_date)->format('M d, Y') }}</div>
                                                                <div class="text-muted" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($school->last_inspection_date)->diffForHumans() }}</div>
                                                            @else
                                                                <span class="text-muted small">No Activity</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-column gap-1">
                                                                <a href="{{ route('fire-safety.report.full-school', $school->id) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                                                                    <i class="fas fa-print me-1"></i> Print
                                                                </a>
                                                                @if($school->status === 'passed')
                                                                    <button class="btn btn-sm btn-outline-success view-passed-btn"
                                                                            data-school-json="{{ json_encode($school) }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#passedDetailsModal">
                                                                        <i class="fas fa-certificate me-1"></i> Success
                                                                    </button>
                                                                @else
                                                                    <button class="btn btn-sm btn-outline-primary details-btn"
                                                                            data-school-json="{{ json_encode($school) }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#issuesModal">
                                                                        <i class="fas fa-tasks me-1"></i> Details
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-4">
                                                            No schools found.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2" id="schoolsPaginationWrap">
                                        <small class="text-muted" id="schoolsPaginationInfo">Showing 0 of 0 schools</small>
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Schools pagination">
                                            <button type="button" class="btn btn-outline-secondary" id="schoolsPrevPageBtn">Previous</button>
                                            <button type="button" class="btn btn-outline-secondary" id="schoolsNextPageBtn">Next</button>
                                        </div>
                                    </div>
                                @else
                                    <!-- Management View for Contributors -->
                                    @php $school = $schools->first(); @endphp
                                    @if($school)
                                        <div class="school-profile mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <div>
                                                    <h3 class="fw-bold text-dark mb-1">{{ $school->school_name }}</h3>
                                                    <p class="text-muted mb-0"><i class="fas fa-map-marker-alt me-2"></i>{{ $school->address ?? 'Address not set' }}</p>
                                                </div>
                                                <div class="text-end">
                                                    @if($school->status === 'passed')
                                                        <div class="badge bg-success p-2 px-3 fs-6"><i class="fas fa-check-circle me-2"></i>STATUS: PASSED</div>
                                                    @elseif($school->status === 'unconfigured')
                                                        <div class="badge bg-secondary p-2 px-3 fs-6"><i class="fas fa-cog fa-spin me-2"></i>STATUS: UNCONFIGURED</div>
                                                    @else
                                                        <div class="badge bg-warning text-dark p-2 px-3 fs-6"><i class="fas fa-tools me-2"></i>STATUS: ONGOING IMPROVEMENT</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="row g-4 mb-4">
                                                <div class="col-md-6">
                                                    <div class="card h-100 bg-light border-0">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user-tie me-2"></i>Leadership</h6>
                                                            <p class="mb-2"><strong>School Head:</strong> {{ $school->school_head ?? 'Not recorded' }}</p>
                                                            <p class="mb-0"><strong>DRRM Coordinator:</strong> {{ $school->school_drrm_coordinator ?? 'Not recorded' }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card h-100 bg-light border-0">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-history me-2"></i>Recent Activity</h6>
                                                            @if($school->last_inspection_date)
                                                                <p class="mb-1"><strong>Last Updated:</strong> {{ \Carbon\Carbon::parse($school->last_inspection_date)->format('M d, Y') }}</p>
                                                                <p class="mb-0 text-muted small">{{ \Carbon\Carbon::parse($school->last_inspection_date)->diffForHumans() }}</p>
                                                            @else
                                                                <p class="mb-0 text-muted">No inspection activity recorded yet.</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Dynamic Issues/Setup Checklist -->
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body">
                                                    @if($school->status === 'passed')
                                                        <div class="text-center py-4">
                                                            <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                                                <i class="fas fa-check fa-2x text-white"></i>
                                                            </div>
                                                            <h4 class="fw-bold text-success">Compliant & Secure</h4>
                                                            <p class="text-muted">Your school has fulfilled all fire safety requirements. Keep up the good work!</p>
                                                            <div class="d-flex justify-content-center gap-3 mt-3">
                                                                <div class="text-center px-3 border-end">
                                                                    <div class="h5 mb-0 fw-bold">100%</div>
                                                                    <small class="text-muted text-uppercase" style="font-size: 0.6rem;">Configuration</small>
                                                                </div>
                                                                <div class="text-center px-3 border-end">
                                                                    <div class="h5 mb-0 fw-bold">0</div>
                                                                    <small class="text-muted text-uppercase" style="font-size: 0.6rem;">Active Issues</small>
                                                                </div>
                                                                <div class="text-center px-3">
                                                                    <div class="h5 mb-0 fw-bold text-success">Passed</div>
                                                                    <small class="text-muted text-uppercase" style="font-size: 0.6rem;">Final Rating</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif($school->status === 'unconfigured')
                                                        <h6 class="text-muted text-uppercase small fw-bold mb-3"><i class="fas fa-list-check me-2"></i>Required Setup Checklist</h6>
                                                        <div class="row g-3">
                                                            @php
                                                                $config = $school->config_status;
                                                                $bldgAlarmDone = $config['has_buildings'] && $config['has_alarms'];
                                                                $extRoomDone = $config['has_rooms'] && $config['has_extinguishers'];
                                                                $planDone = $config['has_plans'];

                                                                // Highlight logic: next card to configure
                                                                $highlightBldg = !$bldgAlarmDone && !$extRoomDone;
                                                                $highlightExtRoom = $bldgAlarmDone && !$extRoomDone;
                                                                // Also highlight ext/room if user started there first (ext/room done but bldg not)
                                                                $highlightBldgAlt = !$bldgAlarmDone && $extRoomDone;
                                                                $highlightPlan = $bldgAlarmDone && $extRoomDone && !$planDone;
                                                            @endphp
                                                            {{-- Buildings & Alarms card --}}
                                                            <div class="col-md-4">
                                                                <div class="card h-100 text-center p-3 border-2
                                                                    {{ $bldgAlarmDone ? 'border-success bg-success-subtle' : (($highlightBldg || $highlightBldgAlt) ? 'border-primary bg-primary-subtle' : 'border-secondary bg-light') }}">
                                                                    <div class="d-flex justify-content-center gap-2 mb-2">
                                                                        <i class="fas fa-building fa-2x {{ $bldgAlarmDone ? 'text-success' : (($highlightBldg || $highlightBldgAlt) ? 'text-primary' : 'text-secondary') }}"></i>
                                                                        <i class="fas fa-bell fa-2x {{ $bldgAlarmDone ? 'text-success' : (($highlightBldg || $highlightBldgAlt) ? 'text-primary' : 'text-secondary') }}"></i>
                                                                    </div>
                                                                    <h6 class="small fw-bold">Buildings & Alarms</h6>
                                                                    <div class="mb-2">
                                                                        @if($bldgAlarmDone)
                                                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Done</span>
                                                                        @elseif($highlightBldg || $highlightBldgAlt)
                                                                            <span class="badge bg-primary"><i class="fas fa-arrow-right"></i> Next Step</span>
                                                                        @else
                                                                            <span class="badge bg-secondary">Pending</span>
                                                                        @endif
                                                                    </div>
                                                                    <a href="{{ route('fire-safety.schools.buildings', ['school' => $school->id]) }}" class="btn btn-sm w-100 {{ $bldgAlarmDone ? 'btn-success' : (($highlightBldg || $highlightBldgAlt) ? 'btn-primary' : 'btn-outline-dark') }}">
                                                                        <i class="fas {{ $bldgAlarmDone ? 'fa-edit' : 'fa-plus' }} me-1"></i> {{ $bldgAlarmDone ? 'Update' : 'Setup Now' }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            {{-- Extinguisher & Rooms card --}}
                                                            <div class="col-md-4">
                                                                <div class="card h-100 text-center p-3 border-2
                                                                    {{ $extRoomDone ? 'border-success bg-success-subtle' : ($highlightExtRoom ? 'border-primary bg-primary-subtle' : 'border-secondary bg-light') }}">
                                                                    <div class="d-flex justify-content-center gap-2 mb-2">
                                                                        <i class="fas fa-fire-extinguisher fa-2x {{ $extRoomDone ? 'text-success' : ($highlightExtRoom ? 'text-primary' : 'text-secondary') }}"></i>
                                                                        <i class="fas fa-door-open fa-2x {{ $extRoomDone ? 'text-success' : ($highlightExtRoom ? 'text-primary' : 'text-secondary') }}"></i>
                                                                    </div>
                                                                    <h6 class="small fw-bold">Extinguisher & Rooms</h6>
                                                                    <div class="mb-2">
                                                                        @if($extRoomDone)
                                                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Done</span>
                                                                        @elseif($highlightExtRoom)
                                                                            <span class="badge bg-primary"><i class="fas fa-arrow-right"></i> Next Step</span>
                                                                        @else
                                                                            <span class="badge bg-secondary">Pending</span>
                                                                        @endif
                                                                    </div>
                                                                    <a href="{{ route('fire-safety.schools.extinguishers', ['school' => $school->id]) }}" class="btn btn-sm w-100 {{ $extRoomDone ? 'btn-success' : ($highlightExtRoom ? 'btn-primary' : 'btn-outline-dark') }}">
                                                                        <i class="fas {{ $extRoomDone ? 'fa-edit' : 'fa-plus' }} me-1"></i> {{ $extRoomDone ? 'Update' : 'Setup Now' }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            {{-- Evacuation Plans card --}}
                                                            <div class="col-md-4">
                                                                <div class="card h-100 text-center p-3 border-2
                                                                    {{ $planDone ? 'border-success bg-success-subtle' : ($highlightPlan ? 'border-primary bg-primary-subtle' : 'border-secondary bg-light') }}">
                                                                    <i class="fas fa-map-signs fa-2x mb-2 {{ $planDone ? 'text-success' : ($highlightPlan ? 'text-primary' : 'text-secondary') }}"></i>
                                                                    <h6 class="small fw-bold">Evacuation Plans</h6>
                                                                    <div class="mb-2">
                                                                        @if($planDone)
                                                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Done</span>
                                                                        @elseif($highlightPlan)
                                                                            <span class="badge bg-primary"><i class="fas fa-arrow-right"></i> Next Step</span>
                                                                        @else
                                                                            <span class="badge bg-secondary">Pending</span>
                                                                        @endif
                                                                    </div>
                                                                    <a href="{{ route('fire-safety.schools.evacuation-plans', ['school' => $school->id]) }}" class="btn btn-sm w-100 {{ $planDone ? 'btn-success' : ($highlightPlan ? 'btn-primary' : 'btn-outline-dark') }}">
                                                                        <i class="fas {{ $planDone ? 'fa-edit' : 'fa-plus' }} me-1"></i> {{ $planDone ? 'Update' : 'Setup Now' }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <h6 class="text-muted text-uppercase small fw-bold mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Identified Issues per Module</h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm align-middle">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Module</th>
                                                                        <th>Location / Building</th>
                                                                        <th class="text-center">Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php
                                                                        $mi = $school->module_issues;
                                                                        $combinedModules = [
                                                                            [
                                                                                'label' => 'Buildings & Alarms',
                                                                                'icons' => ['fa-building', 'fa-bell'],
                                                                                'data' => $mi['buildings_alarms'],
                                                                                'route' => 'fire-safety.schools.buildings',
                                                                                'schoolId' => $school->id,
                                                                            ],
                                                                            [
                                                                                'label' => 'Extinguisher & Rooms',
                                                                                'icons' => ['fa-fire-extinguisher', 'fa-door-open'],
                                                                                'data' => $mi['ext_rooms'],
                                                                                'route' => 'fire-safety.schools.extinguishers',
                                                                                'schoolId' => $school->id,
                                                                            ],
                                                                            [
                                                                                'label' => 'Evacuation Plans',
                                                                                'icons' => ['fa-map-signs'],
                                                                                'data' => $mi['plans'],
                                                                                'route' => 'fire-safety.schools.evacuation-plans',
                                                                                'schoolId' => $school->id,
                                                                            ],
                                                                        ];
                                                                    @endphp
                                                                    @foreach($combinedModules as $cm)
                                                                        @php
                                                                            $hasIssues = count($cm['data']['issues']) > 0;
                                                                            $isGreen = !$hasIssues && !empty($cm['data']['green_msg']);
                                                                            $worstSeverity = 'green';
                                                                            foreach ($cm['data']['issues'] as $iss) {
                                                                                if ($iss['severity'] === 'red') { $worstSeverity = 'red'; break; }
                                                                                if ($iss['severity'] === 'yellow') $worstSeverity = 'yellow';
                                                                            }
                                                                            $arrowColor = $worstSeverity === 'red' ? 'danger' : ($worstSeverity === 'yellow' ? 'warning' : 'success');
                                                                        @endphp
                                                                        <tr>
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    @foreach($cm['icons'] as $icon)
                                                                                        <i class="fas {{ $icon }} text-dark me-1"></i>
                                                                                    @endforeach
                                                                                    <span class="fw-bold small ms-1">{{ $cm['label'] }}</span>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                @if($isGreen)
                                                                                    <span class="text-success small"><i class="fas fa-check-circle me-1"></i>{{ $cm['data']['green_msg'] }}</span>
                                                                                @elseif(!empty($cm['data']['green_msg']) && $hasIssues)
                                                                                    <span class="text-success small d-block mb-1"><i class="fas fa-check-circle me-1"></i>{{ $cm['data']['green_msg'] }}</span>
                                                                                @endif
                                                                                @foreach($cm['data']['issues'] as $iss)
                                                                                    <span class="badge bg-light text-{{ $iss['severity'] === 'red' ? 'danger' : ($iss['severity'] === 'yellow' ? 'warning' : 'success') }} border small mb-1 d-inline-block">{{ $iss['text'] }}</span>
                                                                                @endforeach
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <a href="{{ route($cm['route'], ['school' => $cm['schoolId']]) }}" class="btn btn-sm btn-outline-{{ $arrowColor }} switch-school-link" data-school-id="{{ $cm['schoolId'] }}">
                                                                                    <i class="fas fa-arrow-right"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                            <div class="col-lg-4">
                                <!-- Alerts for All Schools -->
                                <div class="card dashboard-card mb-4">
                                    <div class="card-header py-3 bg-danger text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="m-0 fw-bold">
                                                <i class="fas fa-exclamation-circle me-2"></i> Alerts
                                            </h6>
                                            @if(auth()->user()->role !== 'viewer')
                                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addAlertModal">
                                                    <i class="fas fa-bullhorn me-1"></i> Announce
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body" id="allAlerts" style="max-height: 400px; overflow-y: auto;">
                                        @forelse($allAlerts as $alert)
                                            @php
                                                $borderClass = $alert['type'] == 'danger' ? 'border-danger' : ($alert['type'] == 'warning' ? 'border-warning' : 'border-info');
                                            @endphp
                                            <div class="card mb-2 border-start border-4 {{ $borderClass }}">
                                                <div class="card-body p-2">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1 fw-bold text-{{ $alert['type'] }}" style="font-size: 0.85rem;">
                                                            <i class="fas fa-exclamation-triangle me-1"></i> {{ $alert['school_name'] }}: {{ $alert['title'] }}
                                                        </h6>
                                                        <small class="text-muted text-nowrap ms-2" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($alert['created_at'])->diffForHumans() }}</small>
                                                    </div>
                                                    <p class="mb-0 small text-dark">{{ $alert['description'] }}</p>
                                                    @if(!empty($alert['posted_by']))
                                                        <small class="text-muted" style="font-size: 0.65rem;"><i class="fas fa-user me-1"></i>{{ $alert['posted_by'] }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center text-muted py-3">
                                                <i class="fas fa-check-circle me-2"></i> No active alerts
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Events for All Schools -->
                                <div class="card dashboard-card">
                                    <div class="card-header py-3 bg-primary text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="m-0 fw-bold">
                                                <i class="fas fa-calendar-alt me-2"></i> Events
                                            </h6>
                                            @if(auth()->user()->role !== 'viewer')
                                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                                    <i class="fas fa-bullhorn me-1"></i> Announce
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body" id="allEvents" style="max-height: 400px; overflow-y: auto;">
                                        @forelse($allEvents as $event)
                                            <div class="card mb-2 border-start border-4 border-primary">
                                                <div class="card-body p-2">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1 fw-bold text-primary" style="font-size: 0.85rem;">
                                                            <i class="fas fa-calendar-alt me-1"></i> {{ $event['school_name'] }}: {{ $event['title'] }}
                                                        </h6>
                                                        <small class="text-muted text-nowrap ms-2" style="font-size: 0.7rem;">{{ $event['date'] }} {{ $event['time'] }}</small>
                                                    </div>
                                                    <p class="mb-0 small text-dark">{{ $event['description'] }}</p>
                                                    @if(!empty($event['posted_by']))
                                                        <small class="text-muted" style="font-size: 0.65rem;"><i class="fas fa-user me-1"></i>{{ $event['posted_by'] }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center text-muted py-3">
                                                <i class="fas fa-calendar me-2"></i> No upcoming events
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- <- School Safety Status Section -->


        <!-- Passed Details Modal (Successful Inspection) -->
        <div class="modal fade" id="passedDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-success" style="border-width: 2px;">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-check-double me-2"></i> Inspection Successful</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-graduation-cap fa-3x text-white"></i>
                            </div>
                            <h4 id="passedSchoolName" class="fw-bold mb-1"></h4>
                            <p class="text-muted">Has passed all safety requirements</p>
                            <span class="badge bg-success px-4 py-2">PERFECT 100% COMPLIANCE</span>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-info-circle me-2"></i>School Details</h6>
                                        <p class="mb-2"><strong>School ID:</strong> <span id="passedSchoolId"></span></p>
                                        <p class="mb-2"><strong>Address:</strong> <span id="passedAddress"></span></p>
                                        <p class="mb-2"><strong>School Head:</strong> <span id="passedSchoolHead"></span></p>
                                        <p class="mb-2"><strong>DRRM Coordinator:</strong> <span id="passedCoordinator"></span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-clipboard-check me-2"></i>Verified Status</h6>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i> <span>All Buildings Compliant</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i> <span>Fire Alarms Fully Operational</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i> <span>Evacuation Plans Approved</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i> <span>Fire Extinguishers Active</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-success-subtle rounded border border-success">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-success fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Safe Learning Environment Verified</h6>
                                    <small class="text-muted">Last full configuration and inspection verified on: <span id="passedLastConfig"></span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Issues/Configuration Modal -->
        <div class="modal fade" id="issuesModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-primary" style="border-width: 2px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-clipboard-list me-2"></i> Configuration & Issues</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h5 id="issuesSchoolNameTitle" class="fw-bold mb-4"></h5>

                        <!-- School Details -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-info-circle me-2"></i>School Details</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Address:</strong> <span id="issuesAddress"></span></p>
                                        <p class="mb-2"><strong>School Head:</strong> <span id="issuesSchoolHead"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>DRRM Coordinator:</strong> <span id="issuesCoordinator"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Unconfigured View (Interactive Buttons) -->
                        <div id="unconfiguredView" style="display: none;">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Required Setup Checklist</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card h-100 text-center p-3 border-2" id="btnConfigBuilding">
                                        <div class="d-flex justify-content-center gap-2 mb-2">
                                            <i class="fas fa-building fa-2x"></i>
                                            <i class="fas fa-bell fa-2x"></i>
                                        </div>
                                        <h6>Buildings & Alarms</h6>
                                        <div class="mt-2 status-indicator"></div>
                                        <a href="{{ route('fire-safety.buildings') }}" class="btn btn-sm mt-2">{{ auth()->user()->role === 'viewer' ? 'View' : 'Configure' }}</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 text-center p-3 border-2" id="btnConfigExtRoom">
                                        <div class="d-flex justify-content-center gap-2 mb-2">
                                            <i class="fas fa-fire-extinguisher fa-2x"></i>
                                            <i class="fas fa-door-open fa-2x"></i>
                                        </div>
                                        <h6>Extinguisher & Rooms</h6>
                                        <div class="mt-2 status-indicator"></div>
                                        <a href="{{ route('fire-safety.extinguishers') }}" class="btn btn-sm mt-2">{{ auth()->user()->role === 'viewer' ? 'View' : 'Configure' }}</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 text-center p-3 border-2" id="btnConfigPlan">
                                        <i class="fas fa-map-signs fa-2x mb-2"></i>
                                        <h6>Evacuation Plans</h6>
                                        <div class="mt-2 status-indicator"></div>
                                        <a href="{{ route('fire-safety.evacuation-plans') }}" class="btn btn-sm mt-2">{{ auth()->user()->role === 'viewer' ? 'View' : 'Configure' }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ongoing Improvement View (Table) -->
                        <div id="ongoingView" style="display: none;">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Identified Issues per Module</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Module</th>
                                            <th>Location/Building</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ongoingIssuesTable">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Register school for Fire Safety (from main directory) -->
        <div class="modal fade" id="addInspectionModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Register school for Fire Safety</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="addSchoolForm">
                        @csrf
                        <div class="modal-body">
                            <p class="text-muted small">Schools are created on <strong>DRRM Main Dashboard → Schools</strong>. Here you only <em>register</em> an existing directory school into this module.</p>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Select school *</label>
                                <select class="form-select" id="fs_register_school_select" required>
                                    <option value="">— Choose a school —</option>
                                    @forelse($schoolsAvailableForFireRegistration ?? [] as $dir)
                                        <option
                                            value="{{ $dir->id }}"
                                            data-school-name="{{ e($dir->school_name) }}"
                                            data-school-id="{{ e($dir->school_id ?? '') }}"
                                            data-school-id-num="{{ e($dir->school_id_number ?? '') }}"
                                            data-address="{{ e($dir->address ?? '') }}"
                                            data-head="{{ e($dir->school_head ?? '') }}"
                                            data-drrm="{{ e($dir->drrm_coordinator ?? '') }}"
                                            data-c1="{{ e($dir->contact_number ?? '') }}"
                                            data-c2="{{ e($dir->contact_number_2 ?? '') }}"
                                            data-district="{{ e($dir->district ?? '') }}"
                                            data-division="{{ e($dir->division ?? '') }}"
                                            data-region="{{ e($dir->region ?? '') }}"
                                        >
                                            {{ $dir->school_name }}
                                        </option>
                                    @empty
                                        <option value="" disabled>All directory schools are already registered, or none exist yet.</option>
                                    @endforelse
                                </select>
                            </div>
                            <div id="fs_register_preview" class="row g-2 small text-muted" style="display:none;">
                                <div class="col-md-6">
                                    <strong>School ID / Code:</strong>
                                    <input type="text" id="fs_pv_id" class="form-control form-control-sm mt-1" value="" readonly>
                                </div>
                                <div class="col-md-6">
                                    <strong>Name:</strong>
                                    <input type="text" id="fs_pv_name" class="form-control form-control-sm mt-1" value="" readonly>
                                </div>
                                <div class="col-12">
                                    <strong>Address:</strong>
                                    <input type="text" id="fs_pv_addr" class="form-control form-control-sm mt-1" value="" readonly>
                                </div>
                                <div class="col-md-6">
                                    <strong>Head:</strong>
                                    <input type="text" id="fs_pv_head" class="form-control form-control-sm mt-1" value="" readonly>
                                </div>
                                <div class="col-md-6">
                                    <strong>DRRM Coordinator:</strong>
                                    <input type="text" id="fs_pv_drrm" class="form-control form-control-sm mt-1" value="" readonly>
                                </div>
                                <div class="col-md-6">
                                    <strong>District / Division:</strong>
                                    <input type="text" id="fs_pv_dist" class="form-control form-control-sm mt-1" value="" readonly>
                                </div>
                                <div class="col-md-6">
                                    <strong>Region:</strong>
                                    <input type="text" id="fs_pv_reg" class="form-control form-control-sm mt-1" value="" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="fs_register_submit" @if(($schoolsAvailableForFireRegistration ?? collect())->isEmpty()) disabled @endif>Register for Fire Safety</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Add Alert Modal -->
        <div class="modal fade" id="addAlertModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Add Safety Alert</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="addAlertForm">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">School *</label>
                                <select class="form-select" name="school_id" id="alertSchoolSelect" required>
                                    @if(auth()->user()->role === 'admin')
                                        <option value="">Select School</option>
                                        <option value="all">All Schools</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}">{{ $school->school_name }}</option>
                                        @endforeach
                                    @else
                                        @php
                                            $mySchool = $schools->first();
                                        @endphp
                                        @if($mySchool)
                                            <option value="{{ $mySchool->id }}" selected>{{ $mySchool->school_name }}</option>
                                        @endif
                                    @endif
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alert Title *</label>
                                <input type="text" class="form-control" name="title" placeholder="e.g., Blockage in Fire Exit" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alert Type *</label>
                                <select class="form-select" name="type" required>
                                    <option value="danger">Critical (Red)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="info">Information (Blue)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="3" placeholder="Provide more details about the alert..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Post Alert</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Event Modal -->
        <div class="modal fade" id="addEventModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i> Add Safety Event</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="addEventForm">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">School *</label>
                                <select class="form-select" name="school_id" id="eventSchoolSelect" required>
                                    @if(auth()->user()->role === 'admin')
                                        <option value="">Select School</option>
                                        <option value="all">All Schools</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}">{{ $school->school_name }}</option>
                                        @endforeach
                                    @else
                                        @php
                                            $mySchool = $schools->first();
                                        @endphp
                                        @if($mySchool)
                                            <option value="{{ $mySchool->id }}" selected>{{ $mySchool->school_name }}</option>
                                        @endif
                                    @endif
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Event Title *</label>
                                <input type="text" class="form-control" name="title" placeholder="e.g., Annual Fire Drill" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date *</label>
                                    <input type="date" class="form-control" name="date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Time</label>
                                    <input type="time" class="form-control" name="time">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="3" placeholder="Provide more details about the event..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Schedule Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


    </div>

@endsection

@section('scripts')

<script>
const userRole = "{{ auth()->user()->role }}";

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching - load school-specific alerts/events
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            const schoolSlug = target.replace('#', '');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Success Modal Handler (PASSED)
    document.querySelectorAll('.view-passed-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const school = JSON.parse(this.getAttribute('data-school-json'));
            document.getElementById('passedSchoolName').textContent = school.school_name;
            document.getElementById('passedSchoolId').textContent = school.school_id;
            document.getElementById('passedAddress').textContent = school.address || 'N/A';
            document.getElementById('passedSchoolHead').textContent = school.school_head || 'Not recorded';
            document.getElementById('passedCoordinator').textContent = school.school_drrm_coordinator || 'Not recorded';

            const lastConfigStr = school.last_inspection_date ?
                new Date(school.last_inspection_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) :
                'Not recorded';
            document.getElementById('passedLastConfig').textContent = lastConfigStr;
        });
    });

    // Details Modal Handler (ISSUES/UNCONFIGURED)
    document.querySelectorAll('.details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const school = JSON.parse(this.getAttribute('data-school-json'));
            const status = school.status;

            document.getElementById('issuesSchoolNameTitle').textContent = school.school_name;
            document.getElementById('issuesAddress').textContent = school.address || 'N/A';
            document.getElementById('issuesSchoolHead').textContent = school.school_head || 'Not recorded';
            document.getElementById('issuesCoordinator').textContent = school.school_drrm_coordinator || 'Not recorded';

            const unconfiguredView = document.getElementById('unconfiguredView');
            const ongoingView = document.getElementById('ongoingView');

            if (status === 'unconfigured') {
                unconfiguredView.style.display = 'block';
                ongoingView.style.display = 'none';
                renderUnconfiguredChecklist(school.config_status, school.school_id, school.id);
            } else {
                unconfiguredView.style.display = 'none';
                ongoingView.style.display = 'block';
                renderOngoingIssues(school.module_issues, school.id);
            }
        });
    });

    function renderUnconfiguredChecklist(config, schoolIdDisplay, schoolIdDb) {
        const bldgAlarmDone = config.has_buildings && config.has_alarms;
        const extRoomDone = config.has_rooms && config.has_extinguishers;
        const planDone = config.has_plans;

        const highlightBldg = !bldgAlarmDone && !extRoomDone;
        const highlightExtRoom = bldgAlarmDone && !extRoomDone;
        const highlightBldgAlt = !bldgAlarmDone && extRoomDone;
        const highlightPlan = bldgAlarmDone && extRoomDone && !planDone;

        const cards = [
            { id: 'Building', done: bldgAlarmDone, highlight: highlightBldg || highlightBldgAlt },
            { id: 'ExtRoom', done: extRoomDone, highlight: highlightExtRoom },
            { id: 'Plan', done: planDone, highlight: highlightPlan },
        ];

        cards.forEach(c => {
            const card = document.getElementById(`btnConfig${c.id}`);
            if (!card) return;
            const indicator = card.querySelector('.status-indicator');

            if (c.done) {
                card.className = 'card h-100 text-center p-3 border-2 border-success bg-success-subtle';
            } else if (c.highlight) {
                card.className = 'card h-100 text-center p-3 border-2 border-primary bg-primary-subtle';
            } else {
                card.className = 'card h-100 text-center p-3 border-2 border-secondary bg-light';
            }

            if (c.done) {
                indicator.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Configured</span>';
            } else if (c.highlight) {
                indicator.innerHTML = '<span class="badge bg-primary"><i class="fas fa-arrow-right me-1"></i> Next Step</span>';
            } else {
                indicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-clock me-1"></i> Pending</span>';
            }

            // Update icons color
            card.querySelectorAll('i.fa-2x, i.fas.fa-2x').forEach(icon => {
                icon.classList.remove('text-success', 'text-primary', 'text-secondary');
                if (c.done) icon.classList.add('text-success');
                else if (c.highlight) icon.classList.add('text-primary');
                else icon.classList.add('text-secondary');
            });

            const btn = card.querySelector('a');
            const route = btn.getAttribute('href');
            if (c.done) {
                btn.className = 'btn btn-sm mt-2 btn-success';
            } else if (c.highlight) {
                btn.className = 'btn btn-sm mt-2 btn-primary';
            } else {
                btn.className = 'btn btn-sm mt-2 btn-outline-dark';
            }

            if (userRole === 'viewer') {
                btn.innerHTML = '<i class="fas fa-search me-1"></i> View';
            } else {
                btn.innerHTML = c.done
                    ? '<i class="fas fa-edit me-1"></i> Update'
                    : '<i class="fas fa-plus me-1"></i> Setup Now';
            }

            if (userRole === 'admin') {
                btn.onclick = function(e) {
                    e.preventDefault();
                    switchSchoolAndRedirect(schoolIdDb, route);
                };
            }
        });
    }

    function renderOngoingIssues(issues, schoolIdDb) {
        const tbody = document.getElementById('ongoingIssuesTable');
        tbody.innerHTML = '';

        const moduleMap = [
            {
                label: 'Buildings & Alarms',
                icons: ['fa-building', 'fa-bell'],
                dataKey: 'buildings_alarms',
                route: 'buildings'
            },
            {
                label: 'Extinguisher & Rooms',
                icons: ['fa-fire-extinguisher', 'fa-door-open'],
                dataKey: 'ext_rooms',
                route: 'extinguishers'
            },
            {
                label: 'Evacuation Plans',
                icons: ['fa-map-signs'],
                dataKey: 'plans',
                route: 'evacuation-plans'
            }
        ];

        moduleMap.forEach(m => {
            const data = issues[m.dataKey];
            if (!data) return;

            const hasIssues = data.issues && data.issues.length > 0;
            const greenMsg = data.green_msg || '';

            // Determine worst severity for arrow color
            let worstSeverity = 'green';
            if (hasIssues) {
                data.issues.forEach(iss => {
                    if (iss.severity === 'red') worstSeverity = 'red';
                    else if (iss.severity === 'yellow' && worstSeverity !== 'red') worstSeverity = 'yellow';
                });
            }

            const arrowColorClass = worstSeverity === 'red' ? 'danger' : (worstSeverity === 'yellow' ? 'warning' : 'success');

            const tr = document.createElement('tr');

            // Module column
            const iconHtml = m.icons.map(ic => `<i class="fas ${ic} text-dark me-1"></i>`).join('');

            // Location column
            let locationHtml = '';
            if (!hasIssues && greenMsg) {
                locationHtml = `<span class="text-success small"><i class="fas fa-check-circle me-1"></i>${greenMsg}</span>`;
            } else {
                if (greenMsg) {
                    locationHtml += `<span class="text-success small d-block mb-1"><i class="fas fa-check-circle me-1"></i>${greenMsg}</span>`;
                }
                if (hasIssues) {
                    data.issues.forEach(iss => {
                        const badgeColor = iss.severity === 'red' ? 'danger' : (iss.severity === 'yellow' ? 'warning' : 'success');
                        locationHtml += `<span class="badge bg-light text-${badgeColor} border small mb-1 d-inline-block me-1">${iss.text}</span>`;
                    });
                }
            }

            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        ${iconHtml}
                        <span class="fw-bold small ms-1">${m.label}</span>
                    </div>
                </td>
                <td>${locationHtml}</td>
                <td class="text-center">
                    <a href="/fire-safety/${m.route}" class="btn btn-xs btn-outline-${arrowColorClass} switch-school-link" data-school-id="${schoolIdDb}">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        });

        if (tbody.innerHTML === '') {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-success">All modules reported 100% compliant!</td></tr>';
        }

        // Attach school switch handlers to arrow links
        tbody.querySelectorAll('.switch-school-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const sId = this.getAttribute('data-school-id');
                const targetUrl = this.getAttribute('href');
                switchSchoolAndRedirect(sId, targetUrl);
            });
        });
    }

    // Filter and Sort functionality
    const statusFilter = document.getElementById('statusFilter');
    const sortFilter = document.getElementById('sortFilter');
    const schoolSearchInput = document.getElementById('schoolSearchInput');
    const schoolsTableBody = document.getElementById('schoolsTableBody');
    const schoolsPrevPageBtn = document.getElementById('schoolsPrevPageBtn');
    const schoolsNextPageBtn = document.getElementById('schoolsNextPageBtn');
    const schoolsPaginationInfo = document.getElementById('schoolsPaginationInfo');

    let schoolsCurrentPage = 1;
    const schoolsPageSize = 5;

    function applySchoolsPagination(visibleRows) {
        const totalRows = visibleRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / schoolsPageSize));

        if (schoolsCurrentPage > totalPages) {
            schoolsCurrentPage = totalPages;
        }

        visibleRows.forEach(row => {
            row.style.display = 'none';
        });

        const startIndex = (schoolsCurrentPage - 1) * schoolsPageSize;
        const paginatedRows = visibleRows.slice(startIndex, startIndex + schoolsPageSize);
        paginatedRows.forEach(row => {
            row.style.display = '';
        });

        if (schoolsPrevPageBtn) schoolsPrevPageBtn.disabled = schoolsCurrentPage <= 1;
        if (schoolsNextPageBtn) schoolsNextPageBtn.disabled = schoolsCurrentPage >= totalPages;
        if (schoolsPaginationInfo) {
            if (totalRows === 0) {
                schoolsPaginationInfo.textContent = 'Showing 0 of 0 schools';
            } else {
                const endItem = Math.min(startIndex + paginatedRows.length, totalRows);
                schoolsPaginationInfo.textContent = `Showing ${startIndex + 1}-${endItem} of ${totalRows} schools`;
            }
        }
    }

    function filterAndSortSchools() {
        if (!schoolsTableBody) return;

        const statusValue = statusFilter.value;
        const sortValue = sortFilter.value;
        const searchValue = (schoolSearchInput?.value || '').toLowerCase().trim();
        const rows = Array.from(schoolsTableBody.querySelectorAll('tr'));

        rows.forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            const rowStatus = row.dataset.status;
            const schoolName = (row.dataset.schoolName || '').toLowerCase();
            const matchesStatus = statusValue === 'all' || rowStatus === statusValue;
            const matchesSearch = !searchValue || schoolName.includes(searchValue);
            if (matchesStatus && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const visibleRows = rows.filter(row => row.style.display !== 'none' && !row.querySelector('td[colspan]'));

        visibleRows.sort((a, b) => {
            if (sortValue.startsWith('name_')) {
                const nameA = a.dataset.schoolName.toLowerCase();
                const nameB = b.dataset.schoolName.toLowerCase();
                return sortValue === 'name_asc' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
            } else if (sortValue.startsWith('inspection_')) {
                const dateA = new Date(a.dataset.inspectionDate);
                const dateB = new Date(b.dataset.inspectionDate);
                return sortValue === 'inspection_asc' ? dateA - dateB : dateB - dateA;
            }
            return 0;
        });

        visibleRows.forEach(row => schoolsTableBody.appendChild(row));
        applySchoolsPagination(visibleRows);
    }

    if (statusFilter && sortFilter && schoolsTableBody) {
        statusFilter.addEventListener('change', () => {
            schoolsCurrentPage = 1;
            filterAndSortSchools();
        });
        sortFilter.addEventListener('change', () => {
            schoolsCurrentPage = 1;
            filterAndSortSchools();
        });
        if (schoolSearchInput) {
            schoolSearchInput.addEventListener('input', () => {
                schoolsCurrentPage = 1;
                filterAndSortSchools();
            });
        }
        if (schoolsPrevPageBtn) {
            schoolsPrevPageBtn.addEventListener('click', () => {
                if (schoolsCurrentPage > 1) {
                    schoolsCurrentPage -= 1;
                    filterAndSortSchools();
                }
            });
        }
        if (schoolsNextPageBtn) {
            schoolsNextPageBtn.addEventListener('click', () => {
                schoolsCurrentPage += 1;
                filterAndSortSchools();
            });
        }

        filterAndSortSchools();
    }

    // Attach school switch handlers to inline arrow links (Blade-rendered)
    document.querySelectorAll('.switch-school-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sId = this.getAttribute('data-school-id');
            const targetUrl = this.getAttribute('href');
            switchSchoolAndRedirect(sId, targetUrl);
        });
    });

    const addSchoolForm = document.getElementById('addSchoolForm');
    const fsRegSelect = document.getElementById('fs_register_school_select');
    if (fsRegSelect) {
        fsRegSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const prev = document.getElementById('fs_register_preview');
            if (!opt || !opt.value) {
                prev.style.display = 'none';
                return;
            }
            prev.style.display = 'flex';
            const code = opt.dataset.schoolIdNum || opt.dataset.schoolId || '—';
            document.getElementById('fs_pv_id').value = code;
            document.getElementById('fs_pv_name').value = opt.dataset.schoolName || '';
            document.getElementById('fs_pv_addr').value = opt.dataset.address || '—';
            document.getElementById('fs_pv_head').value = opt.dataset.head || '—';
            document.getElementById('fs_pv_drrm').value = opt.dataset.drrm || '—';
            document.getElementById('fs_pv_dist').value = [opt.dataset.district, opt.dataset.division].filter(Boolean).join(' / ') || '—';
            document.getElementById('fs_pv_reg').value = opt.dataset.region || '—';
        });
    }
    if (addSchoolForm) {
        addSchoolForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const sel = document.getElementById('fs_register_school_select');
            const id = sel && sel.value ? parseInt(sel.value, 10) : 0;
            if (!id) {
                Swal.fire({ icon: 'warning', title: 'Select a school', text: 'Choose a school from the directory first.' });
                return;
            }
            fetch('{{ route('fire-safety.school.register-from-directory') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ unified_school_id: id })
            })
            .then(async response => {
                const data = await response.json();
                if (response.ok && data.success) {
                    Swal.fire({ icon: 'success', title: 'Registered', text: data.message || 'School registered for Fire Safety.', confirmButtonText: 'OK' })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Notice', text: data.message || 'Failed to register school.' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed.' }));
        });
    }

    // Add Alert Form Submission
    const addAlertForm = document.getElementById('addAlertForm');
    if (addAlertForm) {
        addAlertForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('{{ route("fire-safety.school.alert.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(Object.fromEntries(formData.entries()))
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    Swal.fire('Success', data.message || 'Alert posted successfully!', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Failed to post alert.', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
        });
    }

    // Add Event Form Submission
    const addEventForm = document.getElementById('addEventForm');
    if (addEventForm) {
        addEventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('{{ route("fire-safety.school.event.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(Object.fromEntries(formData.entries()))
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    Swal.fire('Success', data.message || 'Event scheduled successfully!', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Failed to schedule event.', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
        });
    }
});

</script>
@endsection
