@extends('layouts.fire-safety')

@section('title', 'Buildings - Fire Safety')
@section('page_title', 'Buildings & Alarms')

@section('styles')
    <style>
        /* SweetAlert2 Custom Styling */
        .swal2-popup {
            border-radius: 15px !important;
        }
        .swal2-styled.swal2-confirm {
            background-color: var(--fire-red) !important;
        }

        .building-card {
            transition: transform 0.2s;
            height: 100%;
        }

        .building-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .compliance-meter {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            overflow: hidden;
            margin-top: 10px;
        }

        .compliance-fill {
            height: 100%;
            transition: width 0.5s;
        }

        .no-buildings {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        /* Multi-floor (2+): highlight Floors + Secondary exits + Rooms only (L-shape); not Min. Extinguishers */
        .multi-floor-l-shape {
            border: 2px solid rgba(220, 53, 69, 0.45) !important;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.12);
            border-radius: 0.5rem;
        }

        .no-buildings i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #adb5bd;
        }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-compliant { background-color: #28a745; }
        .status-needs-attention { background-color: #ffc107; }
        .status-non-compliant { background-color: #dc3545; }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        /* Ensure 100% opacity for dashboard icons */
        .dashboard-card i.opacity-25 {
            opacity: 1 !important;
        }
        /* Remove space between cards and school tab */
        .main-content > .row.mb-4 {
            margin-bottom: 0.5rem !important;
        }
        .dashboard-card.mb-4 {
            margin-bottom: 0.5rem !important;
        }
        .collapse-icon {
            transition: transform 0.2s ease;
        }

        button[aria-expanded="true"] .collapse-icon {
            transform: rotate(90deg);
        }
    </style>
@endsection

@section('content')
    <!-- Inspection Checklist Modal -->
    <div class="modal fade" id="inspectionChecklistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-tasks me-2"></i>
                        Inspection Checklist: <span id="checklistBuildingCode" class="ms-2 fw-bold text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="checklistContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-danger" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Analyzing building safety compliance...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-primary px-4 shadow-sm" id="btnGoToUpdate" onclick="">
                        <i class="fas fa-edit me-2"></i> Update Information
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if(!$activeSchool)
        <!-- Layout handles the "No Schools" alert -->
    @else
        @php $school = $activeSchool; @endphp

        <!-- Building Summary -->
        <div class="row mb-4">

                    @php
                        $compliantBuildings = $school->buildings->filter(function($b) {
                            return \App\Http\Controllers\FireSafetyController::calculateBuildingCompliance($b) >= 80;
                        })->count();
                        $nonCompliantBuildings = $school->buildings->filter(function($b) {
                            return \App\Http\Controllers\FireSafetyController::calculateBuildingCompliance($b) < 80;
                        })->count();
                    @endphp

                    <div class="row mb-4">
                        <!-- Total Buildings -->
                        <div class="col-xl col-md-4 col-6 mb-3">
                            <div class="card dashboard-card stat-card border-start border-primary border-4 h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Buildings</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $school->buildings->count() }}</div>
                                        </div>
                                        <div class="col-auto d-none d-md-block">
                                            <i class="fas fa-building fa-2x text-primary opacity-25"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Compliance Status -->
                        <div class="col-xl col-md-4 col-6 mb-3">
                            <div class="card dashboard-card stat-card border-start border-success border-4 h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-dark text-uppercase mb-1">Compliance</div>
                                            <div class="h5 mb-0 fw-bold">{{ $compliantBuildings }}/{{ $school->buildings->count() }}</div>
                                        </div>
                                        <div class="col-auto d-none d-md-block">
                                            <i class="fas fa-clipboard-check fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Functional Alarms -->
                        <div class="col-xl col-md-4 col-6 mb-3">
                            <div class="card dashboard-card stat-card border-start border-info border-4 h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Active Alarms</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800">
                                                {{ $school->alarmSystems()->whereIn('status', ['functional', 'online', 'active'])->count() }}
                                            </div>
                                        </div>
                                        <div class="col-auto d-none d-md-block">
                                            <i class="fas fa-bell fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Issues -->
                        <div class="col-xl col-md-4 col-6 mb-3">
                            <div class="card dashboard-card stat-card border-start border-warning border-4 h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-dark text-uppercase mb-1">Issues</div>
                                            <div class="h5 mb-0 fw-bold">
                                                {{ $school->alarmSystems()->whereNotIn('status', ['functional', 'online', 'active'])->count() }}
                                            </div>
                                        </div>
                                        <div class="col-auto d-none d-md-block">
                                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Last Tested -->
                        <div class="col-xl col-md-4 col-6 mb-3">
                            <div class="card dashboard-card stat-card border-start border-secondary border-4 h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Last Alarm Tested</div>
                                            @php
                                                $latestTested = $school->alarmSystems()
                                                    ->whereNotNull('last_test')
                                                    ->orderBy('last_test', 'desc')
                                                    ->first();
                                            @endphp
                                            <div class="h5 fw-bold mb-0">
                                                {{ $latestTested ? \Carbon\Carbon::parse($latestTested->last_test)->format('m/d') : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="col-auto d-none d-md-block">
                                            <i class="fas fa-calendar-alt fa-2x text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Buildings Grid -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-header py-3 d-flex align-items-center">
                                <button class="border-0 bg-transparent p-0 me-1 text-primary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#buildingsGridContent"
                                        aria-expanded="false">
                                    <i class="fas fa-chevron-right collapse-icon"></i>
                                </button>
                                <h6 class="m-0 fw-bold text-primary">
                                    <i class="fas fa-building me-1"></i> Buildings
                                </h6>
                                <div class="d-flex align-items-center flex-wrap gap-1 ms-auto">
                                    <!-- Filter Dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="buildingFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-filter me-1"></i> Filter
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="buildingFilterDropdown">
                                            <li><a class="dropdown-item" href="#" onclick="filterBuildings('all')">Show All</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="filterBuildings('compliant')"><i class="fas fa-check-circle text-success me-2"></i>Compliant Only</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="filterBuildings('non-compliant')"><i class="fas fa-exclamation-circle text-danger me-2"></i>Non-Compliant Only</a></li>
                                        </ul>
                                    </div>

                                     @if(auth()->user()->role !== 'viewer')
                                    <button class="btn btn-sm btn-primary add-building-btn"
                                            data-school-id="{{ $school->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addBuildingModal">
                                        <i class="fas fa-plus me-1"></i> Add Building
                                    </button>
                        
                                    @endif

                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#printOptionsModal">
                                        <i class="fas fa-print me-1"></i> Print
                                    </button>

                                    @if(auth()->user()->role !== 'viewer')
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#historyOptionsModal">
                                        <i class="fas fa-history me-1"></i> History
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <div class="collapse show" id="buildingsGridContent">
                                <div class="card-body">
                                    @if($school->buildings->count() > 0)
                                    <div class="row">
                                        @foreach($school->buildings as $building)
                                        @php
                                            $compliance = \App\Http\Controllers\FireSafetyController::calculateBuildingCompliance($building);
                                            $statusClass = $compliance >= 80 ? 'border-success' : ($compliance >= 60 ? 'border-warning' : 'border-danger');
                                            $statusBadge = $compliance >= 80 ? 'bg-success' : ($compliance >= 60 ? 'bg-warning' : 'bg-danger');
                                            $statusText = $compliance >= 80 ? 'Compliant' : ($compliance >= 60 ? 'Needs Attention' : 'Non-Compliant');
                                            $filterStatus = $compliance >= 80 ? 'compliant' : 'non-compliant';
                                        @endphp
                                        <div class="col-xl-3 col-lg-4 col-md-6 col-6 mb-3 building-item" data-status="{{ $filterStatus }}" data-building-id="{{ $building->id }}">
                                            <div class="card building-card {{ $statusClass }}">
                                                <div class="card-body mobile-card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="text-truncate" style="max-width: 60%;">
                                                            <h6 class="card-title mb-0 fw-bold text-truncate">{{ $building->building_no }}</h6>
                                                            <div class="text-muted small text-truncate mobile-tiny-text">{{ $building->building_name }}</div>
                                                        </div>
                                                        <span class="badge {{ $statusBadge }} py-1 px-2 mobile-badge">{{ $statusText }}</span>
                                                    </div>

                                                    <div class="building-stats mb-3">
                                                        <div class="d-flex justify-content-between">
                                                            <span>Floors: <strong>{{ $building->floors }}</strong></span>
                                                            <span>Rooms: <strong>{{ $building->rooms }}</strong></span>
                                                            <span>Min. FRXT.: <strong>{{ $building->required_extinguishers_count }}</strong></span>
                                                        </div>
                                                    </div>

                                                    <!-- Equipment Summary -->
                                                    <div class="mb-3 p-3 bg-light rounded">
                                                        <div class="mb-2">
                                                            @php
                                                                // Merge single-building and multi-building alarms
                                                                $alarms = $building->alarmSystems->merge($building->alarmSystemsMany)->unique('id');
                                                                $alarmCount = $alarms->count();
                                                                $extinguisherCount = $building->fireExtinguishers->count();
                                                            @endphp
                                                            <div class="small fw-bold mb-1"><i class="fas fa-bell text-info me-1"></i> Alarms:</div>
                                                            <div class="d-flex flex-wrap gap-1 building-alarms-badges">
                                                                @forelse($alarms as $alarm)
                                                                    @php
                                                                        $locationLabel = null;
                                                                        if ((int) $alarm->building_id === (int) $building->id) {
                                                                            $locationLabel = 'Installed Here';
                                                                        } elseif ($alarm->buildings && $alarm->buildings->contains('id', $building->id)) {
                                                                            $locationLabel = 'Covering';
                                                                        }
                                                                        $statusLabel = ucfirst(str_replace('_', ' ', $alarm->status));
                                                                    @endphp
                                                                    <span class="badge bg-white text-dark border small" style="font-size: 0.7rem;">
                                                                        {{ $alarm->code }} - {{ $alarm->alarm_type }} {{ $statusLabel }}@if($locationLabel === 'Covering') (Covering)@endif
                                                                        @if($locationLabel === 'Installed Here')
                                                                            <span class="badge bg-success ms-1">Installed Here</span>
                                                                        @endif
                                                                    </span>
                                                                @empty
                                                                    <span class="text-muted small">None</span>
                                                                @endforelse
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                                            <small class="text-truncate text-muted" title="Building Type">
                                                                <i class="fas fa-city me-1"></i> {{ $building->building_type ?? 'Unknown Type' }}
                                                            </small>
                                                            <small class="text-nowrap" title="Fire Extinguishers Count">
                                                                <i class="fas fa-fire-extinguisher text-primary me-1"></i> <strong>{{ $extinguisherCount }}</strong>
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <div class="mt-3 d-grid gap-2">
                                                        <button class="btn btn-sm btn-outline-primary view-building-btn"
                                                                data-building-id="{{ $building->id }}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#viewBuildingModal">
                                                            <i class="fas fa-eye me-2"></i> View Details / Update
                                                        </button>
                                                        @if(auth()->user()->role !== 'viewer')
                                                        <button class="btn btn-sm btn-outline-info manage-alarms-btn"
                                                                data-building-id="{{ $building->id }}"
                                                                data-building-name="{{ $building->building_name ?? $building->building_no }}"
                                                                onclick="openBuildingAlarms({{ $building->id }})">
                                                            <i class="fas fa-bell me-2"></i> Manage Alarms
                                                        </button>
                                                        <a href="{{ route('fire-safety.extinguishers') }}?building_id={{ $building->id }}" class="btn btn-sm btn-outline-primary shadow-sm mt-1">
                                                            <i class="fas fa-fire-extinguisher me-2"></i> Manage Fire Extinguisher
                                                        </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="no-buildings">
                                        <i class="fas fa-building"></i>
                                        <h4>No Buildings Found</h4>
                                        <p class="text-muted">This school doesn't have any buildings yet. Add your first building to get started.</p>
                                        @if(auth()->user()->role !== 'viewer')
                                        <button class="btn btn-primary add-building-btn"
                                                data-school-id="{{ $school->id }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addBuildingModal">
                                            <i class="fas fa-plus me-2"></i> Add First Building
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Section -->
                <div class="row mt-4" id="layouts">
                    <!-- Inspected Checklist History -->
                    <div class="col-lg-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">
                                    <i class="fas fa-history me-2"></i> Inspected Checklist History
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="inspectionsList-{{ $school->id }}">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        Loading history...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Alarm Tests (Moved from Modal) -->
                    <div class="col-lg-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header py-3 bg-white fw-bold text-primary border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">
                                    <i class="fas fa-calendar-alt me-2"></i> Upcoming Alarm Tests
                                </h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                                <ul class="list-group list-group-flush" id="mainUpcomingTestsList">
                                    <li class="list-group-item text-muted small text-center py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Loading upcoming tests...
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
    @endif
@endsection

@section('modals')
    <!-- Print Options Modal -->
    <div class="modal fade" id="printOptionsModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title mb-0"><i class="fas fa-print me-2"></i> Print Options</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <a href="{{ route('fire-safety.report.building-summary', $school->id) }}" class="btn btn-outline-primary w-100 mb-3" target="_blank" onclick="bootstrap.Modal.getInstance(document.getElementById('printOptionsModal')).hide()">
                        <i class="fas fa-file-alt me-2"></i> Print Building Reports
                    </a>
                    <a href="{{ route('fire-safety.report.alarm-details', $school->id) }}" class="btn btn-outline-info w-100" target="_blank" onclick="bootstrap.Modal.getInstance(document.getElementById('printOptionsModal')).hide()">
                        <i class="fas fa-bell me-2"></i> Print Alarm Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- History Options Modal -->
    <div class="modal fade" id="historyOptionsModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title mb-0"><i class="fas fa-history me-2"></i> History Options</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <button class="btn btn-outline-secondary w-100 mb-2" onclick="openBuildingHistoryModal({{ $school->id }}); bootstrap.Modal.getInstance(document.getElementById('historyOptionsModal')).hide()">
                        <i class="fas fa-layer-group me-2"></i> Removed Floor/Room
                    </button>
                    <button class="btn btn-outline-secondary w-100 mb-2" onclick="openAlarmHistoryModal({{ $school->id }}); bootstrap.Modal.getInstance(document.getElementById('historyOptionsModal')).hide()">
                        <i class="fas fa-bell me-2"></i> Removed Alarm System
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="openRemovedBuildingsHistoryModal({{ $school->id }}); bootstrap.Modal.getInstance(document.getElementById('historyOptionsModal')).hide()">
                        <i class="fas fa-building me-2"></i> Removed Buildings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Removed Buildings History Modal -->
    <div class="modal fade" id="removedBuildingsHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i> Removed Buildings History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="removedBuildingsHistoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Removed</th>
                                    <th>Building Code</th>
                                    <th>Building Name</th>
                                    <th>Reason</th>
                                    <th>Associated Data (Archived)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Building Modal -->
    <div class="modal fade" id="addBuildingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i> Add New Building
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addBuildingForm" action="{{ route('fire-safety.building.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="unified_school_id" id="buildingSchoolId">

                        <!-- Required Information Group -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Required Information</h6>

                            <div class="row" id="rowBuildingNoAndType">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Building Number/Code *</label>
                                    <input type="text" class="form-control border-left-primary" name="building_no" id="building_no" placeholder="e.g., BLDG-001" required>
                                </div>
                                <div class="col-md-6 mb-3" id="colBuildingType">
                                    <label class="form-label fw-bold">Building Type *</label>
                                    <select class="form-control" name="building_type" id="building_type_select" required>
                                        <option value="">Select Type</option>
                                        @foreach($buildingTypes->unique('name') as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }} {{ in_array(strtolower($type->name), ['gymnasium', 'cafeteria']) ? '(1 Floor/1 Room)' : '' }}</option>
                                        @endforeach
                                        <option value="Others">Others, Please Specify:</option>
                                    </select>
                                    <div id="other_building_type_container" style="display: none;" class="mt-2">
                                        <input type="text" class="form-control border-primary" name="other_building_type" id="other_building_type" placeholder="Enter building type...">
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="rowSecondaryAndMinExt">
                                <div class="col-md-6 mb-3" id="secondaryExitContainer" style="display: none;">
                                    <label class="form-label fw-bold">Secondary Exits Available (2–4 Storey Buildings) *</label>
                                    <select class="form-control" name="emergency_exits" id="emergencyExitsSelect">
                                        <option value="" disabled selected>Select option</option>
                                        <option value="1">No</option>
                                        <option value="2">Yes</option>
                                    </select>
                                    <small class="text-muted">For buildings with more than 1 floor, choose Yes or No.</small>
                                </div>
                                <div class="col-md-6 mb-3" id="fieldColMinExt">
                                    <label class="form-label fw-bold">Min. Required Fire Extinguishers *</label>
                                    <input type="number" class="form-control" name="required_extinguishers" id="buildingReqExt" min="0" value="0" required>
                                </div>
                            </div>

                            <div id="roomFloorInputs" class="mb-3">
                                <div id="multiFloorRows" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3 p-3 rounded bg-secondary bg-opacity-10" id="slotMultiFloors"></div>
                                        <div class="col-md-6 mb-3 p-3 rounded bg-secondary bg-opacity-10" id="slotMultiMinExt"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3 p-3 rounded bg-secondary bg-opacity-10" id="slotMultiSecondary"></div>
                                        <div class="col-md-6 mb-3 p-3 rounded bg-secondary bg-opacity-10" id="slotMultiRooms"></div>
                                    </div>
                                </div>
                                <div id="singleFloorRows" class="p-3 bg-light rounded">
                                    <div class="row">
                                    <div class="col-md-6 mb-3" id="fieldColFloors">
                                        <label class="form-label fw-bold">Number of Floors *</label>
                                        <div class="input-group">
                                            <button class="btn btn-outline-danger" type="button" id="btnDecFloors" onclick="openReduceFloorsModal()" style="display: none;"><i class="fas fa-minus"></i></button>
                                            <input type="number" class="form-control" name="floors" id="buildingFloorsInput" min="1" max="50" value="1" readonly required>
                                            <button class="btn btn-outline-secondary" type="button" id="btnIncFloors" onclick="incrementFloor()"><i class="fas fa-plus"></i></button>
                                        </div>
                                        <div id="floorActionMessage" class="small text-danger mt-1 fw-bold" style="display: none;"></div>
                                    </div>
                                    <div class="col-md-6 mb-3" id="fieldColRooms">
                                        <label class="form-label fw-bold">Total Rooms *</label>
                                        <div class="input-group">
                                            <button class="btn btn-outline-danger" type="button" id="btnDecRooms" onclick="decrementRoom()" style="display: none;"><i class="fas fa-minus"></i></button>
                                            <input type="number" class="form-control" name="rooms" id="buildingRoomsInput" min="1" value="1" readonly required>
                                            <button class="btn btn-outline-secondary" type="button" id="btnIncRooms" onclick="incrementRoom()"><i class="fas fa-plus"></i></button>
                                        </div>
                                        <div id="roomActionMessage" class="small text-danger mt-1 fw-bold" style="display: none;"></div>
                                    </div>
                                    </div>
                                </div>
                                <div id="roomReductionContainer" style="display: none;" class="mt-3 p-3 bg-white border border-danger rounded">
                                    <h6 class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Rooms to Remove</h6>
                                    <p class="small text-muted mb-2">You are reducing the total rooms by <span id="reduceRoomCountSpan" class="fw-bold">0</span>. Please select which specific room(s) to remove (if any are currently active).</p>
                                    <div class="list-group mb-2" id="inlineReduceRoomsList" style="max-height: 150px; overflow-y: auto;">
                                        <!-- populated via js -->
                                    </div>
                                    <label class="form-label fw-bold small text-danger mt-2">Reason for removal *</label>
                                    <textarea class="form-control" name="room_removal_reason" id="inlineRoomRemovalReason" rows="2" placeholder="Explain why these rooms are being removed..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Optional Information Group -->
                        <div class="mb-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3">Optional - Can be updated later</h6>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Building Name</label>
                                    <input type="text" class="form-control" name="building_name" id="building_name" placeholder="e.g., Science Building">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Year Constructed</label>
                                    <input type="number" class="form-control" name="year_constructed" min="1900" max="{{ date('Y') }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Last Renovation</label>
                                    <input type="number" class="form-control" name="last_renovation" min="1900" max="{{ date('Y') }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Building Description</label>
                                <textarea class="form-control" name="description" rows="2" placeholder="Describe the building features..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Safety Features Installed</label>
                                @if(isset($safetyFeatures) && $safetyFeatures->count() > 0)
                                    <div class="row">
                                        @foreach($safetyFeatures->values()->chunk(ceil($safetyFeatures->count() / 2)) as $chunk)
                                            <div class="col-md-6">
                                                @foreach($chunk as $feature)
                                                    @php
                                                        $featVal = $feature->code ? trim($feature->code) : \Illuminate\Support\Str::slug($feature->name, '_');
                                                        $featId = 'sf_feature_' . $feature->id;
                                                    @endphp
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="features[]" value="{{ $featVal }}" id="{{ $featId }}">
                                                        <label class="form-check-label" for="{{ $featId }}">{{ $feature->name }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">No safety features configured. Add them in Customization.</p>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-danger" id="btnRemoveBuilding" onclick="confirmRemoveBuilding()" style="display: none;">
                            <i class="fas fa-trash-alt me-1"></i> Remove 1st floor/building
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                        @if(auth()->user()->role !== 'viewer')
                        <button type="button" class="btn btn-primary" id="btnSaveBuilding" onclick="saveBuilding()">
                            <i class="fas fa-save me-2"></i> Save Building
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inspect Now Modal (Drill Management) -->
    <div class="modal fade" id="inspectNowModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check me-2"></i> Drill & Inspection Monitoring
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="inspectNowForm">
                        @csrf
                        <input type="hidden" name="unified_school_id" value="{{ $activeSchool->id ?? '' }}">

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Drill Type *</label>
                                <select class="form-select border-primary" name="drill_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Earthquake">Earthquake</option>
                                    <option value="Fire">Fire</option>
                                    <option value="Tsunami">Tsunami</option>
                                    <option value="Lockdown">Lockdown</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Date *</label>
                                <input type="date" class="form-control border-primary" name="inspection_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Time *</label>
                                <input type="time" class="form-control border-primary" name="inspection_time" value="{{ date('H:i') }}" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Time Started</label>
                                <input type="time" class="form-control" name="time_started">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Time Finished</label>
                                <input type="time" class="form-control" name="time_finished">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Elapsed Time (mm:ss)</label>
                                <input type="text" class="form-control" name="elapsed_time" placeholder="e.g. 05:30">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Exits</label>
                                <input type="number" class="form-control" name="no_of_exits" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Buildings</label>
                                <input type="number" class="form-control" name="no_of_buildings" value="{{ $activeSchool->buildings->count() }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Students</label>
                                <input type="number" class="form-control" name="no_of_students" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Personnel</label>
                                <input type="number" class="form-control" name="no_of_personnel" value="0">
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                                    <i class="fas fa-list-check me-2"></i> Safety Checklist
                                </h6>
                                <div class="checklist-items scroll-y" style="max-height: 250px; overflow-y: auto;">
                                    @if(isset($checklists) && count($checklists) > 0)
                                        @foreach($checklists as $item)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="checklist_data[]" value="{{ $item->name }}" id="check_{{ $item->id }}">
                                                <label class="form-check-label small" for="check_{{ $item->id }}">
                                                    {{ $item->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted small">No checklist items configured in customization.</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-users-viewfinder me-2"></i> Other Observers
                                </h6>
                                <div class="observer-items scroll-y" style="max-height: 250px; overflow-y: auto;">
                                    @if(isset($observers) && count($observers) > 0)
                                        @foreach($observers as $obs)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="observers_data[]" value="{{ $obs->name }}" id="obs_{{ $obs->id }}">
                                                <label class="form-check-label small" for="obs_{{ $obs->id }}">
                                                    {{ $obs->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="obs_others_chk" onchange="toggleOthersInput(this, 'obs_others_text_container')">
                                        <label class="form-check-label small" for="obs_others_chk">OTHERS: (Please specify)</label>
                                    </div>
                                    <div id="obs_others_text_container" style="display: none;" class="ms-4 mb-2">
                                        <input type="text" class="form-control form-control-sm border-primary" id="obs_others_text" placeholder="Specify other observers...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Remarks / Findings</label>
                            <textarea class="form-control border-primary" name="remarks" rows="3" placeholder="Enter any observations or findings during the drill..."></textarea>
                        </div>

                        <div class="row border-top pt-3 bg-light rounded p-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Monitored By:</label>
                                <input type="text" class="form-control mb-2" name="monitored_by" value="{{ auth()->user()->name }}" placeholder="Name" required>
                                <input type="text" class="form-control" name="monitored_by_position" placeholder="Position">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">School DRRM Coordinator</label>
                                <input type="text" class="form-control" name="coordinator_name" value="{{ $activeSchool->school_drrm_coordinator ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">School Head / Principal</label>
                                <input type="text" class="form-control" name="school_head_name" value="{{ $activeSchool->school_head ?? '' }}">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-success px-4" onclick="saveDrillInspection()">
                        <i class="fas fa-save me-2"></i> Save & Record Inspection
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('fire-safety.partials.inspection-modals')

    <!-- View Building Modal -->
    <div class="modal fade" id="viewBuildingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i> Building Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="buildingDetailsContent">
                        <!-- Building details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer" id="buildingModalFooter">
                    <!-- Footer buttons will be injected by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Floor and Room History Modal -->
    <div class="modal fade" id="buildingHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #6c757d; color: white;">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i>Floor and Room's History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="buildingHistoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Removed</th>
                                    <th>Type</th>
                                    <th>Building</th>
                                    <th>Identifer</th>
                                    <th>Reason to be removed</th>
                                    <th>Involved Items (Archives)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Building Alarms List Modal -->
    <div class="modal fade" id="buildingAlarmsModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

    <!-- No longer needed: Reduce Rooms Modal -->

    <!-- Reduce Floors Modal -->
    <div class="modal fade" id="reduceFloorsModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-layer-group me-2"></i> Confirm Floor Removal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        You are reducing the total floors by <strong id="reduceFloorsCountDisplay">X</strong>.
                        Floors are removed chronologically from the top.
                        <br><strong>Warning:</strong> All alarms, rooms, and extinguishers on these floors will be archived/deleted!
                    </div>
                    <div id="reduceFloorsList" class="mb-3 border rounded p-3 bg-light">
                        <!-- Listed floors to be removed -->
                        <div class="text-center text-muted">Calculating floors...</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-danger fw-bold">Reason for Removal *</label>
                        <textarea class="form-control border-danger" id="reduceFloorsReason" rows="2" placeholder="State reason to remove these floors..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cancelReduceFloors()">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmReduceFloors()">
                        Confirm Removal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Removed Buildings History Modal -->
    <div class="modal fade" id="removedBuildingsHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #6c757d; color: white;">
                    <h5 class="modal-title"><i class="fas fa-building me-2"></i>Removed Buildings History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="removedBuildingsHistoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Removed</th>
                                    <th>Building Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Required FEXT</th>
                                    <th>Details (Constructed/Renovated)</th>
                                    <th>Description & Features</th>
                                    <th>Reason to be removed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-bell me-2"></i> <span id="alarmsModalTitle">Building Alarms</span>
                    </h5>
                    <div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body bg-light">
                    <!-- Content removed and moved to main dashboard -->
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Alarm Management</h6>
                            <p class="mb-0 small">Upcoming tests and inspection history are now directly visible on the main dashboard for easier access.</p>
                        </div>
                    </div>

                    <!-- Alarms List -->
                    <div class="card shadow-sm mb-3" id="alarm-section">
                         <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-list me-2"></i> Installed Alarm Systems</h6>
                             @if(auth()->user()->role !== 'viewer')
                            <button class="btn btn-primary btn-sm" id="btnAddNewAlarmInList">
                                <i class="fas fa-plus me-2"></i> Add New Alarm
                            </button>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle" id="buildingAlarmsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Floor</th>
                                            <th>Last Test</th>
                                            <th>Next Test Due</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="8" class="text-center py-4">Loading alarms...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Alarm Modal -->
    <div class="modal fade" id="addAlarmModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i> Add New Alarm System
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAlarmForm">
                        @csrf
                        <input type="hidden" name="unified_school_id" id="addAlarmSchoolId">

                         <!-- Building Context -->
                        <div class="alert alert-light border mb-3">
                            <i class="fas fa-building me-2 text-primary"></i>
                            Adding alarm for: <strong id="addAlarmBuildingNameDisplay">...</strong>
                            <input type="hidden" name="building_id" id="addAlarmBuildingId">
                        </div>

                        <!-- Multi-Building Option -->
                        <div class="mb-3">
                             <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="coversMultiple" name="covers_multiple">
                                <label class="form-check-label" for="coversMultiple">
                                    Yes, it covers multiple buildings (Shared System)
                                </label>
                            </div>
                            <div id="multiBuildingSelectContainer" style="display:none;" class="mt-2">
                                <label class="form-label small fw-bold">Select Additional Buildings Covered:</label>
                                <select class="form-control" name="building_ids[]" id="addMultiBuildingSelect" multiple size="4">
                                    <!-- Populated via JS -->
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple.</small>
                            </div>
                        </div>

                        <!-- Required Information -->
                        <h6 class="mt-3">Required Information</h6>
                        <hr />
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Alarm Code *</label>
                                <input type="text" class="form-control" name="code" id="alarmCode" placeholder="e.g. ALARM-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Alarm Type *</label>
                                <select class="form-control" name="alarm_type" id="addAlarmType" required>
                                    <option value="" disabled selected>Select Type</option>
                                    @foreach($alarmTypes as $type)
                                        <option value="{{ $type->name }}" data-type-id="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3" id="floorSelectionRow">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Status *</label>
                                <select class="form-control" name="status" id="addStatusSelect" required>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Floor Level *</label>
                                <select class="form-control" name="floor_id" id="addFloorSelect" required>
                                    <option value="">Select Floor</option>
                                    <option value="all">All Floors</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-none" aria-hidden="true"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Test Date</label>
                                <input type="date" class="form-control" name="last_test" id="lastTestDate" max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Next Test Due *</label>
                                <input type="date" class="form-control" name="next_test_due" id="nextTestDue" required>
                            </div>
                        </div>

                        <!-- Optional Information -->
                        <h6 class="mt-4">Optional – Can be updated later</h6>
                        <hr />
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Specific Location</label>
                                <input type="text" class="form-control" name="location" id="alarmSpecificLocation" placeholder="e.g. Main Lobby, Hallway">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Manufacturer</label>
                                <input type="text" class="form-control" name="manufacturer">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Installation Date</label>
                                <input type="date" class="form-control" name="installation_date" id="installationDate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Notes/Remarks</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-primary" onclick="saveAlarmSystem()">
                        <i class="fas fa-save me-2"></i> Save Alarm System
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Update Alarm Modal -->
    <div class="modal fade" id="updateAlarmModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Update Alarm System</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateAlarmForm">
                        @csrf
                        <input type="hidden" id="updateAlarmId">
                        <input type="hidden" id="updateSchoolId" name="unified_school_id">
                        <input type="hidden" name="context_building_id" id="updateAlarmContextBuildingId" value="">
                        <input type="hidden" id="originalAlarmCode">

                        <!-- Multi-Building Option (Shared System) -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="updateCoversMultiple" name="covers_multiple">
                                <label class="form-check-label" for="updateCoversMultiple">
                                    Yes, it covers multiple buildings (Shared System)
                                </label>
                            </div>
                            <div id="updateMultiBuildingSelectContainer" style="display:none;" class="mt-3 p-3 bg-light border rounded">
                                <label class="form-label small fw-bold">
                                    Currently Assigned Buildings:
                                    <span id="updateAlarmBuildingNameDisplay" class="ms-1 text-primary"></span>
                                    <input type="hidden" name="building_id" id="updateAlarmBuildingId">
                                </label>
                                <div id="updateCurrentBuildingsList" class="mb-3 small">
                                    <!-- List of currently assigned buildings -->
                                </div>
                                <label class="form-label small fw-bold d-block mb-2">Select Additional Buildings to Cover:</label>
                                <select class="form-control" name="building_ids[]" id="updateMultiBuildingSelect" multiple size="4">
                                    <!-- Populated via JS -->
                                </select>
                                <small class="text-muted d-block mt-2">Hold Ctrl/Cmd to select multiple. (Primary building shown above)</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Alarm Code</label>
                                <input type="text" class="form-control" name="code" id="updateAlarmCode" required>
                            </div>
                             <div class="col-md-6">
                                <label class="form-label fw-bold">Alarm Type</label>
                                <input type="text" class="form-control bg-light" id="updateAlarmTypeDisplay" readonly disabled>
                                <!-- Alarm Type cannot be changed -->
                            </div>
                        </div>

                        <div class="row mb-3">
                             <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select class="form-control" name="status" id="updateStatusSelect" required>
                                    <!-- Populated via JS -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Location</label>
                                <input type="text" class="form-control" name="location" id="updateAlarmLocation">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Floor Level</label>
                                <select class="form-control" name="floor_id" id="updateFloorSelect" required>
                                    <option value="">Select Floor</option>
                                    <option value="all">All Floors</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Manufacturer</label>
                                <input type="text" class="form-control" name="manufacturer" id="updateManufacturer">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Installation Date</label>
                                <input type="date" class="form-control" name="installation_date" id="updateInstallationDate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Test Date</label>
                                <input type="date" class="form-control" name="last_test" id="updateLastTestDate">
                            </div>
                        </div>

                        <div class="row mb-3 align-items-start">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Next Test Due</label>
                                <input type="date" class="form-control" name="next_test_due" id="updateNextTestDue" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Notes</label>
                                <textarea class="form-control" name="notes" id="updateNotes" rows="3" style="min-height: 5.5rem;"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-info text-white" onclick="updateAlarmSystem()">
                        <i class="fas fa-save me-2"></i> Update Changes
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Alarm Removal Modal -->
    <div class="modal fade" id="alarmRemovalModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Remove Alarm System</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold">Are you sure you want to remove this alarm system?</p>
                    <p class="text-muted small">This action cannot be undone. All historical data for this alarm will be moved to the archives.</p>
                     <div class="mt-3">
                        <label class="form-label fw-bold">Reason to be removed *</label>
                        <textarea class="form-control" id="alarmRemovalReason" rows="3" placeholder="Enter reason for removal..." auto-focus></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmRemoveAlarm()">
                        <i class="fas fa-trash-alt me-2"></i> Yes, Remove It!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alarm History Modal -->
    <div class="modal fade" id="alarmHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i> Removed Alarm Systems History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                     <div class="table-responsive">
                        <table class="table table-hover table-sm" id="alarmHistoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Removed</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Last Location</th>
                                    <th>Reason</th>
                                    <th>Last Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Global variables
        const USER_ROLE = "{{ auth()->user()->role }}";
        let currentSchoolId = {{ $activeSchool->id ?? 'null' }};

        const SWAL_SUCCESS_ENTER = { confirmButtonText: 'OK', allowEnterKey: true, returnFocus: false };
        function swalFireSuccess(opts) {
            const o = opts || {};
            const reloadAfter = o.reloadAfter === true;
            const rest = Object.assign({}, o);
            delete rest.reloadAfter;
            if (reloadAfter) {
                return Swal.fire(Object.assign({
                    icon: 'success',
                    timer: 1600,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                }, rest)).then(() => {
                    location.reload();
                });
            }
            return Swal.fire(Object.assign({ icon: 'success' }, SWAL_SUCCESS_ENTER, rest));
        }

        const FIRE_SAFETY_ALARM_STORE_URL = @json(route('fire-safety.alarm.store'));

        const ALARM_STATUS_GENERAL = [
            { value: 'active', label: 'Active' },
            { value: 'broken', label: 'Broken' },
            { value: 'missing', label: 'Missing' },
            { value: 'not_installed', label: 'Not Installed' },
            { value: 'decommissioned', label: 'Decommissioned' },
        ];
        const ALARM_STATUS_DIGITAL_ONLY = [{ value: 'offline', label: 'Offline' }];

        function isDigitalAlarmType(typeName) {
            return (typeName || '').trim().toLowerCase() === 'digital';
        }

        function fillAlarmStatusSelect(selectEl, typeName, selectedValue) {
            if (!selectEl) return;
            const digital = isDigitalAlarmType(typeName);
            const prev = selectedValue != null ? selectedValue : selectEl.value;
            selectEl.innerHTML = '';
            const opts = ALARM_STATUS_GENERAL.concat(digital ? ALARM_STATUS_DIGITAL_ONLY : []);
            opts.forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                selectEl.appendChild(opt);
            });
            const raw = (prev || 'active').toString();
            const norm = raw.toLowerCase().replace(/\s+/g, '_');
            let match = Array.from(selectEl.options).find(o => o.value === norm);
            if (!match && (norm === 'functional' || norm === 'online')) {
                match = Array.from(selectEl.options).find(o => o.value === 'active');
            }
            if (!match && norm === 'maintenance') {
                match = Array.from(selectEl.options).find(o => o.value === 'broken');
            }
            selectEl.value = match ? match.value : 'active';
        }

        function applyBuildingModalFloorLayout() {
            const roomSec = document.getElementById('roomFloorInputs');
            if (!roomSec || roomSec.style.display === 'none') return;

            const floorsInput = document.getElementById('buildingFloorsInput');
            const multiRow = document.getElementById('multiFloorRows');
            const singleWrap = document.getElementById('singleFloorRows');
            const rowSecMin = document.getElementById('rowSecondaryAndMinExt');
            const slotF = document.getElementById('slotMultiFloors');
            const slotME = document.getElementById('slotMultiMinExt');
            const slotS = document.getElementById('slotMultiSecondary');
            const slotR = document.getElementById('slotMultiRooms');
            const colF = document.getElementById('fieldColFloors');
            const colR = document.getElementById('fieldColRooms');
            const colME = document.getElementById('fieldColMinExt');
            const colS = document.getElementById('secondaryExitContainer');
            if (!floorsInput || !multiRow || !singleWrap || !rowSecMin || !slotF || !slotME || !slotS || !slotR || !colF || !colR || !colME || !colS) return;

            [slotF, slotS, slotR].forEach(el => el.classList.remove('multi-floor-l-shape'));
            slotME.classList.remove('multi-floor-l-shape');

            const f = parseInt(floorsInput.value, 10) || 1;

            if (f > 1) {
                multiRow.style.display = '';
                singleWrap.classList.remove('bg-light', 'p-3', 'rounded');
                rowSecMin.style.display = 'none';
                slotF.appendChild(colF);
                slotME.appendChild(colME);
                slotS.appendChild(colS);
                slotR.appendChild(colR);
                slotF.classList.add('multi-floor-l-shape');
                slotS.classList.add('multi-floor-l-shape');
                slotR.classList.add('multi-floor-l-shape');
            } else {
                multiRow.style.display = 'none';
                singleWrap.classList.add('bg-light', 'p-3', 'rounded');
                rowSecMin.style.display = '';
                const innerRow = singleWrap.querySelector('.row');
                if (innerRow) {
                    innerRow.appendChild(colF);
                    innerRow.appendChild(colR);
                }
                if (colS.parentElement !== rowSecMin) rowSecMin.appendChild(colS);
                if (colME.parentElement !== rowSecMin) rowSecMin.appendChild(colME);
            }
            toggleSecondaryExits();
        }

        function focusBuildingFloorsInput() {
            const el = document.getElementById('buildingFloorsInput');
            if (!el || el.disabled) return;
            requestAnimationFrame(() => {
                el.focus();
                const v = el.value != null ? String(el.value) : '';
                try { el.setSelectionRange(v.length, v.length); } catch (e) { /* readonly number input */ }
            });
        }

        function renderBuildingAlarmBadgesHtml(building) {
            const primaryId = building.id;
            const direct = building.alarm_systems || building.alarmSystems || [];
            const many = building.alarm_systems_many || building.alarmSystemsMany || [];
            const anchored = building.anchored_alarm_systems || building.anchoredAlarmSystems || [];
            const map = new Map();
            [...direct, ...many, ...anchored].forEach(a => {
                if (a && a.id != null && !map.has(a.id)) map.set(a.id, a);
            });
            const alarms = [...map.values()];
            if (alarms.length === 0) return '<span class="text-muted small">None</span>';
            return alarms.map(alarm => {
                const st = (alarm.status || '').toString().replace(/_/g, ' ');
                const statusLabel = st.replace(/\b\w/g, l => l.toUpperCase());
                const bid = parseInt(alarm.building_id, 10);
                const pid = parseInt(primaryId, 10);
                const isAnchored = !alarm.building_id && parseInt(alarm.anchor_building_id, 10) === pid;
                const isPrimary = !isAnchored && bid === pid;
                let inner = `${alarm.code} - ${alarm.alarm_type} ${statusLabel}`;
                if (isAnchored) inner += ' <span class="badge bg-warning text-dark ms-1">Unassigned</span>';
                else if (!isPrimary) inner += ' (Covering)';
                if (isPrimary) inner += ' <span class="badge bg-success ms-1">Installed Here</span>';
                return `<span class="badge bg-white text-dark border small" style="font-size: 0.7rem;">${inner}</span>`;
            }).join('');
        }

        async function refreshSchoolBuildingAlarmCards(schoolId) {
            if (!schoolId) return;
            try {
                const response = await fetch(`/fire-safety/buildings/${schoolId}`);
                const buildings = await response.json();
                buildings.forEach(b => {
                    const wrap = document.querySelector(`.building-item[data-building-id="${b.id}"] .building-alarms-badges`);
                    if (wrap) wrap.innerHTML = renderBuildingAlarmBadgesHtml(b);
                });
                if (currentAlarmsBuildingId) loadBuildingAlarms(currentAlarmsBuildingId);
            } catch (e) {
                console.error(e);
            }
        }

        // Auto-scroll to section if hash is present
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);
                if (target) {
                    setTimeout(() => {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 500);
                }
            }
        });

        function checkViewerAccess(formId, buttonsId = null) {
            if (USER_ROLE === 'viewer') {
                const form = document.getElementById(formId);
                if (form) {
                    const elements = form.querySelectorAll('input, select, textarea, button:not([data-bs-dismiss="modal"])');
                    elements.forEach(el => el.disabled = true);
                }
                if (buttonsId) {
                    const buttons = document.getElementById(buttonsId);
                    if (buttons) buttons.style.display = 'none';
                }
            }
        }

        function toggleFloorRemovalReason() {
            const select = document.getElementById('removeFloorSelect');
            const section = document.getElementById('floorRemovalReasonSection');
            if (select && select.value) {
                section.style.display = 'block';
            } else if (section) {
                section.style.display = 'none';
                const reasonInput = document.getElementById('floorRemovalReason');
                if(reasonInput) reasonInput.value = '';
            }
        }

        function toggleRoomRemovalReason() {
            const select = document.getElementById('removeRoomSelect');
            const section = document.getElementById('roomRemovalReasonSection');
            if (select && select.value) {
                section.style.display = 'block';
            } else if (section) {
                section.style.display = 'none';
                const reasonInput = document.getElementById('roomRemovalReason');
                if(reasonInput) reasonInput.value = '';
            }
        }

        async function openBuildingHistoryModal(schoolId) {
            const modalEl = document.getElementById('buildingHistoryModal');
            if(!modalEl) return;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const tableBody = document.querySelector('#buildingHistoryTable tbody');
            if(!tableBody) return;
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
            modal.show();

            try {
                const resp = await fetch(`/fire-safety/building/history/${schoolId}`);
                const data = await resp.json();

                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No removed floors or rooms found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = '';
                data.forEach(item => {
                    const removedAt = new Date(item.removed_at).toLocaleString();
                    const row = `
                        <tr>
                            <td>${removedAt}</td>
                            <td><span class="badge bg-secondary">${item.type}</span></td>
                            <td>${item.item_data.building_name || 'N/A'}</td>
                            <td class="fw-bold text-danger">${item.item_code || 'N/A'}</td>
                            <td>${item.reason || 'No reason provided'}</td>
                            <td>
                                <small>
                                    ${item.item_data.involved_alarms ? `Alarms: ${item.item_data.involved_alarms}<br>` : ''}
                                    ${item.item_data.involved_extinguishers ? `Extinguishers: ${item.item_data.involved_extinguishers}<br>` : ''}
                                    ${item.item_data.involved_rooms ? `Rooms: ${item.item_data.involved_rooms}<br>` : ''}
                                </small>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } catch (e) {
                console.error(e);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load history.</td></tr>';
            }
        }

        async function openRemovedBuildingsHistoryModal(schoolId) {
            const modalEl = document.getElementById('removedBuildingsHistoryModal');
            if(!modalEl) return;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const tableBody = document.querySelector('#removedBuildingsHistoryTable tbody');
            if(!tableBody) return;
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
            modal.show();

            try {
                const resp = await fetch(`/fire-safety/building/removed-history/${schoolId}`);
                const data = await resp.json();

                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No removed buildings found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = '';
                data.forEach(item => {
                    const removedAt = new Date(item.removed_at).toLocaleString();
                    const row = `
                        <tr>
                            <td>${removedAt}</td>
                            <td class="fw-bold text-danger">${item.item_code || 'N/A'}</td>
                            <td>${item.item_data.building_name || 'N/A'}</td>
                            <td>${item.reason || 'No reason provided'}</td>
                            <td>
                                <small>
                                    ${item.item_data.floors ? `Floors: ${item.item_data.floors}<br>` : ''}
                                    ${item.item_data.rooms ? `Rooms: ${item.item_data.rooms}` : ''}
                                </small>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } catch (e) {
                console.error(e);
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load history.</td></tr>';
            }
        }

        function formatDate(dateString) {
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    return 'Invalid Date';
                }
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (error) {
                return 'Invalid Date';
            }
        }

        function getStatusClass(status) {
            const statusMap = {
                'scheduled': 'warning',
                'in_progress': 'info',
                'completed': 'success',
                'cancelled': 'secondary',
                'overdue': 'danger'
            };
            return statusMap[status] || 'secondary';
        }

        function getInspectionTypeText(type) {
            if (!type) return 'N/A';

            const typeMap = {
                // Inspection Types
                'routine': 'Routine Safety Audit',
                'quarterly': 'Quarterly Inspection',
                'annual': 'Annual Comprehensive',
                'fire_drill': 'Fire Drill',
                'emergency': 'Emergency Inspection',
                'preventive': 'Preventive Maintenance',

                // Status Types
                'scheduled': 'Scheduled',
                'in_progress': 'In Progress',
                'completed': 'Completed',
                'cancelled': 'Cancelled',
                'overdue': 'Overdue'
            };

            return typeMap[type.toLowerCase()] || type.charAt(0).toUpperCase() + type.slice(1);
        }

        // helper to convert floor identifier into human-readable label
        function formatFloorLabel(floor) {
            if (!floor || floor === 'N/A') return '-';
            if (floor === 'all' || floor === 'ALL' || floor === '0') return 'Entire Building';

            const num = Number(floor);
            if (isNaN(num)) return floor;

            const j = num % 10,
                k = num % 100;
            if (j === 1 && k !== 11) return num + "st Floor";
            if (j === 2 && k !== 12) return num + "nd Floor";
            if (j === 3 && k !== 13) return num + "rd Floor";
            return num + "th Floor";
        }

        // Store current school ID (already initialized above)

        // Open Inspection Checklist
        async function openInspectionChecklist(buildingId, buildingNo) {
            const modalEl = document.getElementById('inspectionChecklistModal');
            const modal = new bootstrap.Modal(modalEl);
            const content = document.getElementById('checklistContent');
            const codeSpan = document.getElementById('checklistBuildingCode');
            const updateBtn = document.getElementById('btnGoToUpdate');

            codeSpan.textContent = buildingNo;
            updateBtn.onclick = () => {
                modal.hide();
                editBuilding(buildingId);
            };

            modal.show();

            try {
                const response = await fetch(`/fire-safety/building/${buildingId}`);
                if (!response.ok) throw new Error('Failed to fetch building data');
                const building = await response.json();

                // Calculate missing things
                const alarmCount = building.alarm_systems_count || 0;
                const extinguisherCount = building.fire_extinguishers_count || 0;
                const reqExt = building.required_extinguishers || 0;
                const exits = building.emergency_exits || 0;

                // Score emulation (simple)
                let issues = [];
                if (alarmCount === 0) issues.push({ icon: 'fa-bell', text: 'No alarm systems installed', color: 'danger' });
                if (extinguisherCount === 0) issues.push({ icon: 'fa-fire-extinguisher', text: 'No fire extinguishers recorded', color: 'danger' });
                else if (extinguisherCount < reqExt) issues.push({ icon: 'fa-fire-extinguisher', text: `Only ${extinguisherCount}/${reqExt} required extinguishers present`, color: 'warning' });
                if (exits === 0) issues.push({ icon: 'fa-door-open', text: 'Zero emergency exits recorded', color: 'danger' });
                if (!building.features) issues.push({ icon: 'fa-shield-alt', text: 'No safety features (Sprinklers, Fire Doors, etc.) specified', color: 'warning' });

                let html = `
                    <div class="text-center mb-4">
                        <div class="display-6 fw-bold mb-1">${building.building_no}</div>
                        <div class="text-muted"><i class="fas fa-school me-1"></i> ${building.school?.school_name || 'School Information'}</div>
                    </div>
                `;

                if (issues.length === 0) {
                    html += `
                        <div class="alert alert-success d-flex align-items-center shadow-sm">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Building fully documented!</h6>
                                <small>Basic safety requirements are present in the system records.</small>
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded text-center">
                            <p class="mb-0 text-muted">Would you like to review or refine the building details?</p>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="alert alert-warning mb-4 shadow-sm border-0 d-flex align-items-center">
                            <i class="fas fa-lightbulb text-warning fa-2x me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Data Gap Detected</h6>
                                <p class="mb-0 small text-muted">The following items are missing or incomplete in your records:</p>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush mb-0 border rounded overflow-hidden shadow-sm">
                    `;

                    issues.forEach(issue => {
                        const softBg = issue.color === 'danger' ? '#fdecea' : '#fff9db';
                        html += `
                            <li class="list-group-item d-flex align-items-center py-3 border-bottom">
                                <span class="rounded-circle p-2 me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background-color: ${softBg}; color: var(--bs-${issue.color});">
                                    <i class="fas ${issue.icon}"></i>
                                </span>
                                <div>
                                    <div class="fw-bold text-dark">${issue.text}</div>
                                    <div class="small text-muted">Requires administrative attention</div>
                                </div>
                            </li>
                        `;
                    });

                    html += `
                        </ul>
                        <div class="mt-4 text-center">
                            <p class="text-muted small">Updating these records improves your <strong>Safety Compliance Score</strong>.</p>
                        </div>
                    `;
                }

                content.innerHTML = html;

            } catch (error) {
                console.error(error);
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load checklist. Please check your connection.
                    </div>
                `;
            }
        }

        // Initialize with first school
        document.addEventListener('DOMContentLoaded', function() {
            const firstTab = document.querySelector('#schoolTab button.active');
            if (firstTab) {
                currentSchoolId = firstTab.getAttribute('data-school-id');
                loadSchoolData(currentSchoolId);
            }

            // Set default date for inspection form to today
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.querySelector('input[name="inspection_date"]');
            if (dateInput) {
                dateInput.value = today;
                dateInput.min = today; // Prevent past dates
            }
        });

        // School tab switching
        document.querySelectorAll('#schoolTab button').forEach(button => {
            button.addEventListener('shown.bs.tab', function(event) {
                const schoolId = this.getAttribute('data-school-id');
                currentSchoolId = schoolId;
                loadSchoolData(schoolId);
            });
        });

        // Add Building button click
        document.querySelectorAll('.add-building-btn').forEach(button => {
            button.addEventListener('click', function() {
                const schoolId = this.getAttribute('data-school-id');
                document.getElementById('buildingSchoolId').value = schoolId;
            });
        });

        // Schedule Inspection button click
        document.querySelectorAll('.schedule-inspection-btn').forEach(button => {
            button.addEventListener('click', function() {
                const schoolId = this.getAttribute('data-school-id');
                document.getElementById('inspectionSchoolId').value = schoolId;
                loadBuildingsForInspection(schoolId);
            });
        });

        // Inspect Now button click (from building card)
        document.querySelectorAll('.inspect-building-btn').forEach(button => {
            button.addEventListener('click', function() {
                const buildingId = this.getAttribute('data-building-id');
                const buildingName = this.getAttribute('data-building-name');

                document.getElementById('inspectionSchoolId').value = currentSchoolId;

                // Set today's date as default
                const today = new Date().toISOString().split('T')[0];
                document.querySelector('input[name="inspection_date"]').value = today;

                // Pre-select the building
                const buildingSelect = document.getElementById('buildingSelect');
                buildingSelect.innerHTML = `<option value="${buildingId}" selected>${buildingName}</option>`;
            });
        });

        // View Building button click
        document.querySelectorAll('.view-building-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const buildingId = this.getAttribute('data-building-id');

                // Show loading in modal
                document.getElementById('buildingDetailsContent').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading building details...</p>
                    </div>
                `;

                try {
                    const response = await fetch(`/fire-safety/building/${buildingId}`);
                    if (!response.ok) {
                        const errorText = await response.text();
                        let errorMessage = 'Failed to load building details. Please try again.';
                        try {
                            const errorData = JSON.parse(errorText);
                            errorMessage = errorData.error || errorMessage;
                        } catch (e) {
                            console.error('Error parsing error response:', errorText);
                        }
                        throw new Error(errorMessage);
                    }
                    const building = await response.json();

                    let roomsWithoutSecondary = 0;
                    let classroomsCount = 0;
                    let labsCount = 0;
                    let adminCount = 0;
                    let actualRoomsCount = 0;

                    if (building.actual_rooms && Array.isArray(building.actual_rooms)) {
                        actualRoomsCount = building.actual_rooms.length;
                        building.actual_rooms.forEach(room => {
                            if (!room.has_secondary_exit || room.has_secondary_exit == '0') roomsWithoutSecondary++;

                            const type = (room.room_type || '').toLowerCase();
                            if (type.includes('classroom')) classroomsCount++;
                            if (type.includes('laboratory') || type.includes('lab')) labsCount++;
                            if (type.includes('administration') || type.includes('admin')) adminCount++;
                        });
                    }

                    const allEncoded = actualRoomsCount >= (building.rooms || 0);
                    const allEncodedText = allEncoded ? 'Yes' : 'No';

                    let html = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Building Code:</strong> ${building.building_no}</p>
                                <p><strong>Building Name:</strong> ${building.building_name || 'N/A'}</p>
                                <p><strong>Floors:</strong> ${building.floors || 0}</p>
                                <p><strong>Building Type:</strong> ${building.building_type || 'N/A'}</p>
                                <p><strong>Minimum Required Extinguisher:</strong> ${building.required_extinguishers_count || 1}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Numbers of Classrooms:</strong> ${classroomsCount}</p>
                                <p><strong>Numbers of Lab:</strong> ${labsCount}</p>
                                <p><strong>Numbers of administration office:</strong> ${adminCount}</p>
                                <p><strong>Number of Rooms without Secondary:</strong> ${roomsWithoutSecondary}</p>
                                <p><strong>Are rooms all encoded at this building?</strong> ${allEncodedText}
                                    ${!allEncoded ? `<a href="{{ route('fire-safety.extinguishers') }}" class="btn btn-sm btn-outline-danger ms-2"><i class="fas fa-arrow-right"></i> Add for Extinguisher Page</a>` : ''}
                                </p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Year Constructed:</strong> ${building.year_constructed || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Last Renovation:</strong> ${building.last_renovation || 'N/A'}</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p><strong>Description:</strong></p>
                            <div class="border rounded p-3">${building.description || 'No description available.'}</div>
                        </div>

                        <div class="mb-3">
                            <p><strong>Safety Features:</strong></p>
                            <div class="border rounded p-3">
                                ${(building.features && typeof building.features === 'string') ? building.features.split(',').map(feature =>
                                    `<span class="badge bg-info me-2 mb-2">${feature}</span>`
                                ).join('') : 'No safety features recorded.'}
                            </div>
                        </div>
                    `;

                    let footerHtml = '';
                    if (USER_ROLE !== 'viewer') {
                        html += `
                            <div class="alert alert-primary mt-3">
                                <i class="fas fa-question-circle me-2"></i>
                                <strong>Already Inspected building?</strong>
                            </div>
                        `;

                        // Check for alarms (building.alarm_systems comes from controller getBuilding)
                        const alarmCount = (building.alarm_systems && Array.isArray(building.alarm_systems)) ? building.alarm_systems.length : 0;
                        let alarmButton = '';

                        if (alarmCount > 0) {
                            alarmButton = `
                                <button type="button" class="btn btn-info me-2 text-white" onclick="openBuildingAlarms(${building.id})">
                                    <i class="fas fa-bell me-2"></i> Manage Alarms <span class="badge bg-white text-info ms-1">${alarmCount}</span>
                                </button>
                            `;
                        } else {
                            alarmButton = `
                                <button type="button" class="btn btn-outline-info me-2" onclick="openBuildingAlarms(${building.id}, true)">
                                    <i class="fas fa-plus me-2"></i> Add New Alarm
                                </button>
                            `;
                        }

                        footerHtml = `
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                            ${alarmButton}
                            <button type="button" class="btn btn-primary" onclick="editBuilding(${building.id})">
                                <i class="fas fa-edit me-2"></i> Update
                            </button>
                        `;
                    } else {
                        html += `
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                View-only mode enabled. Modifications are restricted.
                            </div>
                        `;
                        footerHtml = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        `;
                    }

                    document.getElementById('buildingDetailsContent').innerHTML = html;
                    document.getElementById('buildingModalFooter').innerHTML = footerHtml;

                } catch (error) {
                    console.error('Error loading building details:', error);
                    document.getElementById('buildingDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${error.message || 'Failed to load building details. Please try again.'}
                        </div>
                    `;
                    document.getElementById('buildingModalFooter').innerHTML = `
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    `;
                }
            });
        });

        // Edit Building functionality
        async function editBuilding(buildingId) {
            try {
                const response = await fetch(`/fire-safety/building/${buildingId}`);
                const building = await response.json();

                // Close view modal
                const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewBuildingModal'));
                if (viewModal) viewModal.hide();

                // Open add/edit modal but with update behavior
                const editModalEl = document.getElementById('addBuildingModal');
                const editModal = new bootstrap.Modal(editModalEl);

                // Update modal title
                editModalEl.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit me-2"></i> Update Building Information';

                // Set form action and method for update
                const form = document.getElementById('addBuildingForm');
                const originalAction = form.action;
                form.action = `/fire-safety/building/${buildingId}/update`;

                // Fill form data
                form.querySelector('[name="building_no"]').value = building.building_no;
                form.querySelector('[name="building_name"]').value = building.building_name || '';

                window.currentEditingBuilding = building; // Store for reduce modals

                const floorsInput = form.querySelector('#buildingFloorsInput');
                floorsInput.value = building.floors;
                floorsInput.min = building.floors;
                floorsInput.readOnly = true;
                document.getElementById('btnIncFloors').style.display = 'block';
                if (document.getElementById('btnDecFloors')) document.getElementById('btnDecFloors').style.display = 'block';

                const roomsInput = form.querySelector('#buildingRoomsInput');
                roomsInput.value = building.rooms;
                roomsInput.min = building.rooms;
                roomsInput.readOnly = true;

                // Set initial states for room reduction
                originalRoomsCount = building.rooms;
                roomActionType = null;
                document.getElementById('roomActionMessage').style.display = 'none';
                document.getElementById('roomReductionContainer').style.display = 'none';
                if (document.getElementById('inlineReduceRoomsList')) document.getElementById('inlineReduceRoomsList').innerHTML = '';
                document.getElementById('inlineRoomRemovalReason').value = '';

                document.getElementById('btnIncRooms').style.display = 'block';
                if (document.getElementById('btnDecRooms')) document.getElementById('btnDecRooms').style.display = 'block';

                const btnRemoveBuilding = document.getElementById('btnRemoveBuilding');
                if (btnRemoveBuilding) {
                    if (building.floors === 1) btnRemoveBuilding.style.display = 'block';
                    else btnRemoveBuilding.style.display = 'none';
                }

                // Initial show/hide for Secondary Exits based on current floors
                const secContainer = document.getElementById('secondaryExitContainer');
                if (parseInt(building.floors) > 1) {
                    secContainer.style.display = 'block';
                    document.getElementById('emergencyExitsSelect').required = true;
                } else {
                    secContainer.style.display = 'none';
                    document.getElementById('emergencyExitsSelect').required = false;
                }

                applyBuildingModalFloorLayout();

                form.querySelector('[name="year_constructed"]').value = building.year_constructed || '';
                form.querySelector('[name="last_renovation"]').value = building.last_renovation || '';
                form.querySelector('[name="emergency_exits"]').value = building.emergency_exits || 0;
                const typeSelect = form.querySelector('[name="building_type"]');
                const otherContainer = document.getElementById('other_building_type_container');
                const otherInput = document.getElementById('other_building_type');

                typeSelect.disabled = false;
                
                // Check if the building type exists in the predefined options
                let typeExists = false;
                for (let i = 0; i < typeSelect.options.length; i++) {
                    if (typeSelect.options[i].value === building.building_type) {
                        typeExists = true;
                        break;
                    }
                }

                if (typeExists || !building.building_type) {
                    typeSelect.value = building.building_type || '';
                    otherContainer.style.display = 'none';
                    otherInput.value = '';
                } else {
                    typeSelect.value = 'Others';
                    otherContainer.style.display = 'block';
                    otherInput.value = building.building_type;
                    // Ensure the listener logic for gym/canteen is applied if needed (though others won't trigger it)
                    document.getElementById('roomFloorInputs').style.display = 'block';
                }

                form.querySelector('[name="description"]').value = building.description || '';

                // Handle Gymnasium/Cafeteria restriction
                const isMiniBldg = ['gymnasium', 'cafeteria'].includes(building.building_type?.toLowerCase());
                if (isMiniBldg) {
                    document.getElementById('roomFloorInputs').style.display = 'none';
                    floorsInput.value = 1;
                    roomsInput.value = 1;
                } else {
                    document.getElementById('roomFloorInputs').style.display = 'block';
                }

                document.getElementById('buildingReqExt').value = building.required_extinguishers || 0;

                // Handle checkboxes
                const features = building.features ? building.features.split(',') : [];
                form.querySelectorAll('[name="features[]"]').forEach(cb => {
                    cb.checked = features.includes(cb.value);
                });

                // Update save button to update button
                const saveBtn = editModalEl.querySelector('#btnSaveBuilding');
                const originalOnClick = saveBtn.getAttribute('onclick');
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i> Update Information';
                saveBtn.setAttribute('onclick', `updateBuilding(${building.id}, '${building.building_no}', '${building.building_type}')`);

                // Reset modal on hide
                editModalEl.addEventListener('hidden.bs.modal', function() {
                    form.action = originalAction;
                    form.reset();
                    editModalEl.querySelector('.modal-title').innerHTML = '<i class="fas fa-plus me-2"></i> Add New Building';
                    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i> Save Building';
                    saveBtn.setAttribute('onclick', originalOnClick);
                    floorsInput.min = 1;
                    roomsInput.min = 1;
                    typeSelect.disabled = false;
                    floorActionType = null;
                    roomActionType = null;
                    document.getElementById('floorActionMessage').style.display = 'none';
                    document.getElementById('roomActionMessage').style.display = 'none';
                    document.getElementById('roomReductionContainer').style.display = 'none';
                    document.getElementById('roomFloorInputs').style.display = 'block';
                    if (document.getElementById('btnDecFloors')) document.getElementById('btnDecFloors').style.display = 'none';
                    if (document.getElementById('btnDecRooms')) document.getElementById('btnDecRooms').style.display = 'none';
                    if (document.getElementById('btnRemoveBuilding')) document.getElementById('btnRemoveBuilding').style.display = 'none';
                    document.getElementById('secondaryExitContainer').style.display = 'none';
                    applyBuildingModalFloorLayout();
                }, { once: true });

                editModal.show();

                // Enforce viewer role restrictions
                checkViewerAccess('addBuildingForm');

            } catch (error) {
                console.error('Error loading building for edit:', error);
                Swal.fire('Error', 'Failed to load building data for editing.', 'error');
            }
        }

        async function updateBuilding(buildingId, oldBuildingNo, oldBuildingType) {
            const form = document.getElementById('addBuildingForm');
            const formData = new FormData(form);
            const newBuildingNo = formData.get('building_no');
            
            let newBuildingType = formData.get('building_type');
            if (newBuildingType === 'Others') {
                newBuildingType = formData.get('other_building_type');
                formData.set('building_type', newBuildingType);
            }
            
            // Re-append the select value in case it is somehow disabled
            if (!newBuildingType) {
                 const typeSelect = form.querySelector('[name="building_type"]');
                 formData.set('building_type', typeSelect.value);
            }

            // Always ask for confirmation on edit
            const result = await Swal.fire({
                title: 'Confirm Update',
                text: "Are you sure you want to update this building's information?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#A8191F',
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'No, cancel!'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    swalFireSuccess({ title: 'Success!', text: data.message, confirmButtonColor: '#A8191F' }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to update building.',
                        icon: 'error',
                        confirmButtonColor: '#A8191F'
                    });
                }
            } catch (error) {
                console.error('Error updating building:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'An unexpected error occurred.',
                    icon: 'error',
                    confirmButtonColor: '#A8191F'
                });
            }
        }

        // Add Gymnasium/Cafeteria locking logic for Add Mode
        document.getElementById('building_type_select').addEventListener('change', function() {
            const isMiniBldg = ['gymnasium', 'cafeteria'].includes(this.value.toLowerCase());
            const roomsIn = document.getElementById('buildingRoomsInput');
            const floorsIn = document.getElementById('buildingFloorsInput');
            const btnIncRooms = document.getElementById('btnIncRooms');
            const btnIncFloors = document.getElementById('btnIncFloors');
            const otherContainer = document.getElementById('other_building_type_container');
            const otherInput = document.getElementById('other_building_type');

            if (this.value === 'Others') {
                otherContainer.style.display = 'block';
                otherInput.setAttribute('required', 'required');
                otherInput.focus();
            } else {
                otherContainer.style.display = 'none';
                otherInput.removeAttribute('required');
            }

            const roomFloorSec = document.getElementById('roomFloorInputs');

            if (isMiniBldg) {
                roomsIn.value = 1;
                floorsIn.value = 1;
                roomsIn.readOnly = true;
                floorsIn.readOnly = true;
                btnIncRooms.disabled = true;
                btnIncFloors.disabled = true;
                btnIncRooms.style.display = 'none';
                btnIncFloors.style.display = 'none';
                if (roomFloorSec) roomFloorSec.style.display = 'none';
            } else {
                roomsIn.readOnly = true; // Always readOnly because we use +/- buttons
                floorsIn.readOnly = true;
                roomsIn.disabled = false;
                floorsIn.disabled = false;
                btnIncRooms.disabled = false;
                btnIncFloors.disabled = false;
                btnIncRooms.style.display = 'block';
                btnIncFloors.style.display = 'block';
                if (roomFloorSec) roomFloorSec.style.display = 'block';
                applyBuildingModalFloorLayout();
                focusBuildingFloorsInput();
            }
        });

        // Load school data (inspections and stats)
        async function loadSchoolData(schoolId) {
            if (!schoolId) return;

            try {
                // Load inspections if container exists
                const inspectionsContainer = document.getElementById(`inspectionsList-${schoolId}`);
                if (inspectionsContainer) {
                    await loadInspections(schoolId);
                }

                // Load compliance stats if container exists
                const statsContainer = document.getElementById(`complianceStats-${schoolId}`);
                if (statsContainer) {
                    await loadComplianceStats(schoolId);
                }

                // Load sidebar stats if container exists
                const sidebarStats = document.getElementById('sidebarStats');
                if (sidebarStats) {
                    await loadSidebarStats(schoolId);
                }
            } catch (error) {
                console.error('Error loading school data:', error);
            }
        }

        function toggleOthersInput(chk, containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                container.style.display = chk.checked ? 'block' : 'none';
                if (chk.checked) {
                    const input = container.querySelector('input');
                    if (input) input.focus();
                }
            }
        }

        // Store inspection (Inspect Now)
        async function saveDrillInspection() {
            const form = document.getElementById('inspectNowForm');
            const formData = new FormData(form);

            // Handle "Others" observer
            const othersChk = document.getElementById('obs_others_chk');
            const othersText = document.getElementById('obs_others_text');
            if (othersChk && othersChk.checked && othersText && othersText.value.trim()) {
                formData.append('observers_data[]', 'Others: ' + othersText.value.trim());
            }

            // Validate required
            if (!formData.get('drill_type') || !formData.get('inspection_date') || !formData.get('inspection_time')) {
                Swal.fire('Required Fields', 'Please fill in Drill Type, Date, and Time.', 'warning');
                return;
            }

            try {
                Swal.fire({
                    title: 'Saving...',
                    text: 'Recording inspection data',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const response = await fetch('{{ route('fire-safety.inspection.store') }}', {
                    method: 'POST',
                    headers: { // This route is now handled by DrillMonitoringController
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    swalFireSuccess({
                        title: 'Success!',
                        text: 'Inspection record saved successfully.',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        const modalEl = document.getElementById('inspectNowModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        form.reset();
                        if (data.inspection && (data.inspection.unified_school_id || data.inspection.school_id)) {
                            loadInspections(data.inspection.unified_school_id || data.inspection.school_id);
                        }
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to save inspection.', 'error');
                }
            } catch (error) {
                console.error('Error saving inspection:', error);
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
            }
        }

        // Load inspections for a school
        async function loadInspections(schoolId) {
            const container = document.getElementById(`inspectionsList-${schoolId}`);
            if (!container) return;

            try {
                const response = await fetch(`/fire-safety/inspections/${schoolId}`);
                if (!response.ok) throw new Error('Failed to fetch');

                const data = await response.json();

                if (data.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-5 border rounded bg-light">
                            <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary opacity-25"></i>
                            <p>No inspection history found for this school.</p>
                            @if(auth()->user()->role !== 'viewer')
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#inspectNowModal">
                                <i class="fas fa-plus me-1"></i> Start First Inspection
                            </button>
                            @endif
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Date Inspected</th>
                                    <th>Drill Type</th>
                                    <th>Monitored By</th>
                                    <th>Remarks</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.forEach(item => {
                    const date = new Date(item.inspection_date).toLocaleDateString('en-US', {
                        year: 'numeric', month: 'long', day: 'numeric'
                    });
                    html += `
                        <tr class="inspection-row" data-id="${item.id}">
                            <td class="fw-bold">${date}<div class="small text-muted">${item.inspection_time || ''}</div></td>
                            <td><span class="badge ${getDrillBadgeClass(item.drill_type)}">${item.drill_type}</span></td>
                            <td>${item.monitored_by || 'N/A'}</td>
                            <td>
                                <textarea class="form-control bg-light border-1 small text-dark p-1" rows="2" style="max-width: 250px; resize: none; font-size: 0.8rem; height: 60px;" readonly>${item.remarks || 'No remarks'}</textarea>
                            </td>
                            <td class="text-center" style="width: 180px;">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewInspection(${item.id})">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                    <a href="/fire-safety/inspection/${item.id}/print" target="_blank" class="btn btn-sm btn-outline-dark">
                                        <i class="fas fa-print me-1"></i> Print
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;

            } catch (error) {
                console.error('Error loading history:', error);
                container.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i> Failed to load inspection history.
                    </div>
                `;
            }
        }

        function getDrillBadgeClass(type) {
            if (type === 'Earthquake') return 'bg-warning text-dark';
            if (type === 'Fire') return 'bg-danger';
            if (type === 'Both') return 'bg-primary';
            return 'bg-secondary';
        }

        let currentInspectionForUpdate = null;

        async function viewInspection(id) {
            try {
                const response = await fetch(`/fire-safety/inspection/${id}`);
                const data = await response.json();

                if (response.ok) {
                    currentInspectionForUpdate = data;
                    document.getElementById('viewInspectionIdSpan').textContent = id;

                    // Create an exact read-only copy of the inspectNowForm structure
                    let checkListHtml = '';
                    const allChecks = Array.from(document.querySelectorAll('.check-upd-list')).map(el => el.value);
                    const savedChecks = data.checklist_data || [];
                    allChecks.forEach(val => {
                        const isChecked = savedChecks.includes(val) ? 'checked' : '';
                        checkListHtml += `<div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" disabled ${isChecked}>
                            <label class="form-check-label small text-dark">${val}</label>
                        </div>`;
                    });

                    let obsListHtml = '';
                    const allObs = Array.from(document.querySelectorAll('.obs-upd-list')).map(el => el.value);
                    const savedObs = data.observers_data || [];
                    allObs.forEach(val => {
                        const isChecked = savedObs.includes(val) ? 'checked' : '';
                        obsListHtml += `<div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" disabled ${isChecked}>
                            <label class="form-check-label small text-dark">${val}</label>
                        </div>`;
                    });

                    // Add saved "Others" observers if any
                    savedObs.forEach(val => {
                        if (val.startsWith('Others: ')) {
                            obsListHtml += `<div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" disabled checked>
                                <label class="form-check-label small text-dark">${val}</label>
                            </div>`;
                        }
                    });

                    const html = `
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-bold small">Drill Type *</label>
                                <input type="text" class="form-control" value="${data.drill_type || ''}" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold small">Date *</label>
                                <input type="text" class="form-control" value="${data.inspection_date || ''}" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold small">Time *</label>
                                <input type="text" class="form-control" value="${data.inspection_time || ''}" readonly disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="small fw-bold">Time Started</label>
                                <input type="text" class="form-control" value="${data.time_started || ''}" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Time Finished</label>
                                <input type="text" class="form-control" value="${data.time_finished || ''}" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Elapsed Time (mm:ss)</label>
                                <input type="text" class="form-control" value="${data.elapsed_time || ''}" readonly disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="small fw-bold">No. of Exits</label>
                                <input type="text" class="form-control" value="${data.no_of_exits || 0}" readonly disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">No. of Buildings</label>
                                <input type="text" class="form-control" value="${data.no_of_buildings || 0}" readonly disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">No. of Students</label>
                                <input type="text" class="form-control" value="${data.no_of_students || 0}" readonly disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">No. of Personnel</label>
                                <input type="text" class="form-control" value="${data.no_of_personnel || 0}" readonly disabled>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3 border-bottom pb-2">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-success"><i class="fas fa-list-check me-2"></i> Safety Checklist</h6>
                                <div style="max-height: 200px; overflow-y: auto;">
                                    ${checkListHtml || '<p class="text-muted small">None configured.</p>'}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary"><i class="fas fa-users-viewfinder me-2"></i> Other Observers</h6>
                                <div style="max-height: 200px; overflow-y: auto;">
                                    ${obsListHtml || '<p class="text-muted small">None configured.</p>'}
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Remarks / Findings</label>
                            <textarea class="form-control text-dark" rows="3" readonly disabled>${data.remarks || ''}</textarea>
                        </div>
                        <div class="row bg-light rounded p-2 border">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">Monitored By:</label>
                                <input type="text" class="form-control mb-1" value="${data.monitored_by || ''}" readonly disabled>
                                <input type="text" class="form-control" value="${data.monitored_by_position || ''}" readonly disabled>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">School DRRM Coordinator</label>
                                <input type="text" class="form-control" value="${data.coordinator_name || ''}" readonly disabled>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">School Head / Principal</label>
                                <input type="text" class="form-control" value="${data.school_head_name || ''}" readonly disabled>
                            </div>
                        </div>
                    `;

                    document.getElementById('viewInspectionBody').innerHTML = html;
                    const viewModal = new bootstrap.Modal(document.getElementById('viewInspectionModal'));
                    viewModal.show();
                } else {
                    Swal.fire('Error', 'Failed to fetch inspection details.', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'An error occurred while fetching details.', 'error');
            }
        }

        function openUpdateInspectionModal() {
            if (!currentInspectionForUpdate) return;
            // Hide view modal
            const viewModalEl = document.getElementById('viewInspectionModal');
            const viewModal = bootstrap.Modal.getInstance(viewModalEl);
            if (viewModal) viewModal.hide();

            // Populate form
            const d = currentInspectionForUpdate;
            document.getElementById('updateInspId').value = d.id;
            document.getElementById('updateInspSchoolId').value = d.unified_school_id || d.school_id;
            document.getElementById('upd_drill_type').value = d.drill_type || '';
            document.getElementById('upd_inspection_date').value = d.inspection_date || '';
            document.getElementById('upd_inspection_time').value = d.inspection_time ? d.inspection_time.substring(0,5) : '';
            document.getElementById('upd_time_started').value = d.time_started ? d.time_started.substring(0,5) : '';
            document.getElementById('upd_time_finished').value = d.time_finished ? d.time_finished.substring(0,5) : '';
            document.getElementById('upd_elapsed_time').value = d.elapsed_time || '';
            document.getElementById('upd_no_of_exits').value = d.no_of_exits || 0;
            document.getElementById('upd_no_of_buildings').value = d.no_of_buildings || 0;
            document.getElementById('upd_no_of_students').value = d.no_of_students || 0;
            document.getElementById('upd_no_of_personnel').value = d.no_of_personnel || 0;
            document.getElementById('upd_remarks').value = d.remarks || '';
            document.getElementById('upd_monitored_by').value = d.monitored_by || '';
            document.getElementById('upd_monitored_by_position').value = d.monitored_by_position || '';
            document.getElementById('upd_coordinator_name').value = d.coordinator_name || '';
            document.getElementById('upd_school_head_name').value = d.school_head_name || '';

            // Checkboxes
            const savedChecks = d.checklist_data || [];
            document.querySelectorAll('.check-upd-list').forEach(chk => {
                chk.checked = false;
                if (savedChecks.includes(chk.value)) chk.checked = true;
            });
            const savedObs = d.observers_data || [];
            document.querySelectorAll('.obs-upd-list').forEach(chk => {
                chk.checked = false;
                if (savedObs.includes(chk.value)) chk.checked = true;
            });

            // Handle "Others" field in update modal
            const othersChkUpd = document.getElementById('upd_obs_others_chk');
            const othersTextUpd = document.getElementById('upd_obs_others_text');
            const othersContainer = document.getElementById('upd_obs_others_text_container');

            othersChkUpd.checked = false;
            othersTextUpd.value = '';
            othersContainer.style.display = 'none';

            savedObs.forEach(obs => {
                if (obs.startsWith('Others: ')) {
                    othersChkUpd.checked = true;
                    othersTextUpd.value = obs.replace('Others: ', '');
                    othersContainer.style.display = 'block';
                }
            });

            // Show updated modal
            const updModal = new bootstrap.Modal(document.getElementById('updateInspectionModal'));
            updModal.show();
        }

        async function updateDrillInspection() {
            const form = document.getElementById('updateInspectionForm');
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const id = document.getElementById('updateInspId').value;
            const formData = new FormData(form);

            // Handle "Others" observer for update
            const othersChkUpd = document.getElementById('upd_obs_others_chk');
            const othersTextUpd = document.getElementById('upd_obs_others_text');
            if (othersChkUpd && othersChkUpd.checked && othersTextUpd && othersTextUpd.value.trim()) {
                formData.append('observers_data[]', 'Others: ' + othersTextUpd.value.trim());
            }

            try {
                const response = await fetch(`/fire-safety/inspection/${id}/update`, {
                    method: 'POST', // Form handles PUT via _method parameter in FormData
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    swalFireSuccess({ title: 'Success', text: data.message }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Validation failed.', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'An error occurred during update.', 'error');
            }
        }

        // Load compliance statistics
        async function loadComplianceStats(schoolId) {
            try {
                const response = await fetch(`/fire-safety/compliance-stats/${schoolId}`);
                const stats = await response.json();

                const compliantCount = stats.compliant || 0;
                const needsAttentionCount = stats.needs_attention || 0;
                const nonCompliantCount = stats.non_compliant || 0;
                const total = compliantCount + needsAttentionCount + nonCompliantCount;
                const overallPercentage = total > 0 ? Math.round((compliantCount / total) * 100) : 0;

                let html = `
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <h3>${overallPercentage}% Overall</h3>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar ${overallPercentage >= 80 ? 'bg-success' : overallPercentage >= 60 ? 'bg-warning' : 'bg-danger'}"
                                     style="width: ${overallPercentage}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-4">
                            <h5 class="text-success">${compliantCount}</h5>
                            <small>Compliant</small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-warning">${needsAttentionCount}</h5>
                            <small>Needs Attention</small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-danger">${nonCompliantCount}</h5>
                            <small>Non-Compliant</small>
                        </div>
                    </div>
                `;

                // Add priorities if there are non-compliant buildings
                if (nonCompliantCount > 0) {
                    html += `
                        <hr>
                        <div class="mt-3">
                            <h6>Top Priorities:</h6>
                            <ol class="small">
                                <li>Address non-compliant buildings</li>
                                <li>Schedule immediate inspections</li>
                                <li>Review safety equipment</li>
                            </ol>
                        </div>
                    `;
                }

                document.getElementById(`complianceStats-${schoolId}`).innerHTML = html;

            } catch (error) {
                console.error('Error loading compliance stats:', error);
                document.getElementById(`complianceStats-${schoolId}`).innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load statistics.
                    </div>
                `;
            }
        }

        // Load sidebar stats
        async function loadSidebarStats(schoolId) {
            try {
                const response = await fetch(`/fire-safety/sidebar-stats/${schoolId}`);
                const stats = await response.json();

                let html = `
                    <div class="text-white mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <span>Compliant: <strong>${stats.compliant || 0}</strong></span>
                    </div>
                    <div class="text-white mb-2">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <span>Needs Attention: <strong>${stats.needs_attention || 0}</strong></span>
                    </div>
                    <div class="text-white mb-3">
                        <i class="fas fa-times-circle text-danger me-2"></i>
                        <span>Non-Compliant: <strong>${stats.non_compliant || 0}</strong></span>
                    </div>
                `;

                document.getElementById('sidebarStats').innerHTML = html;

            } catch (error) {
                console.error('Error loading sidebar stats:', error);
            }
        }

        // Load all inspections (for refresh button)
        async function loadAllInspections(schoolId) {
            await loadInspections(schoolId);
            Swal.fire({
                title: 'Refreshed!',
                text: 'Inspections have been reloaded.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }

        // Load buildings for inspection dropdown
        async function loadBuildingsForInspection(schoolId) {
            try {
                const response = await fetch(`/fire-safety/buildings-list/${schoolId}`);
                const buildings = await response.json();

                const select = document.getElementById('buildingSelect');
                select.innerHTML = '<option value="">Select Building</option>';

                buildings.forEach(building => {
                    const option = document.createElement('option');
                    option.value = building.id;
                    option.textContent = building.building_no + (building.building_name ? ` (${building.building_name})` : '');
                    select.appendChild(option);
                });

            } catch (error) {
                console.error('Error loading buildings:', error);
                Swal.fire('Error', 'Failed to load buildings. Please try again.', 'error');
            }
        }

       // Save Building - FIXED VERSION
        async function saveBuilding() {
            const form = document.getElementById('addBuildingForm');

            // Basic validation for required fields only (Building Name is Optional now)
            const buildingNo = form.querySelector('[name="building_no"]').value.trim();
            // const buildingName = form.querySelector('[name="building_name"]').value.trim(); // Optional

            if (!buildingNo) {
                Swal.fire('Validation Error', 'Building number is required.', 'warning');
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Get CSRF token - FIXED
            let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Alternative fallback if meta tag method fails
            if (!csrfToken) {
                csrfToken = document.querySelector('input[name="_token"]')?.value;
                if (!csrfToken) {
                    Swal.fire('Error', 'CSRF token missing. Please refresh the page and try again.', 'error');
                    return;
                }
            }

            const formData = new FormData(form);

            if (formData.get('building_type') === 'Others') {
                const otherVal = formData.get('other_building_type');
                if (!otherVal) {
                    Swal.fire('Validation Error', 'Please specify the building type.', 'warning');
                    return;
                }
                formData.set('building_type', otherVal);
            }

            const yearConstructed = parseInt(formData.get('year_constructed')) || 0;
            const lastRenovation = parseInt(formData.get('last_renovation')) || 0;

            // Validation
            if (yearConstructed && lastRenovation && lastRenovation < yearConstructed) {
                Swal.fire('Invalid Dates', 'Last renovation year cannot be earlier than the year constructed.', 'warning');
                return;
            }

            const floors = parseInt(formData.get('floors')) || 1;
            const rooms = parseInt(formData.get('rooms')) || 1;

            formData.set('floors', floors);
            formData.set('rooms', rooms);

            if (rooms < floors) {
                Swal.fire('Invalid Configuration', 'Total rooms cannot be less than total floors. Each floor must have at least one room.', 'warning');
                return;
            }

            if (roomActionType === 'reduce') {
                const reduceAmount = originalRoomsCount - parseInt(rooms);
                const listObj = document.getElementById('inlineReduceRoomsList');
                const checkedCount = listObj.querySelectorAll('.room-reduce-checkbox:checked').length;
                const availableRooms = listObj.querySelectorAll('.room-reduce-checkbox').length;

                // Only strictly require matching checkboxes if there are actually active rooms to remove
                const expectedChecks = Math.min(reduceAmount, availableRooms);

                if (checkedCount !== expectedChecks) {
                     Swal.fire('Validation Error', `You must select exactly ${expectedChecks} room(s) to remove based on your reduction.`, 'warning');
                     return;
                }

                const reason = document.getElementById('inlineRoomRemovalReason').value.trim();
                formData.set('room_removal_reason', reason); // Ensure it's in the payload

                if (!reason) {
                    Swal.fire('Validation Error', 'Please provide a reason for the room removal.', 'warning');
                    return;
                }
            }

            // Show loading state
            const saveButton = document.querySelector('#addBuildingModal .btn-primary');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
            saveButton.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addBuildingModal'));
                    if (modal) modal.hide();

                    swalFireSuccess({ title: 'Success!', text: 'Building added successfully!', confirmButtonColor: '#A8191F' }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to add building', 'error');
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;
                }

            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to add building. Please check your connection and try again.', 'error');
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }
        }

        // Save Inspection
        async function saveInspection() {
            const form = document.getElementById('scheduleInspectionForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    swalFireSuccess({ title: 'Success', text: 'Inspection scheduled successfully!' }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to schedule inspection', 'error');
                }

            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to schedule inspection', 'error');
            }
        }



        async function cancelInspection(inspectionId) {
            const result = await Swal.fire({
                title: 'Cancel Inspection?',
                text: "Are you sure you want to cancel this inspection? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/fire-safety/inspection/${inspectionId}/cancel`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    swalFireSuccess({ title: 'Cancelled', text: 'Inspection has been cancelled.' });
                    // Reload the current school's inspections
                    if (currentSchoolId) {
                        await loadInspections(currentSchoolId);
                    }
                } else {
                    Swal.fire('Error', data.message || 'Failed to cancel inspection', 'error');
                }

            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to cancel inspection. Please try again.', 'error');
            }
        }

        // Start Inspection function
        async function startInspection(inspectionId) {
            // Redirect to inspection checklist page
            window.location.href = `/fire-safety/inspection/${inspectionId}/checklist`;
        }

        // Generate building report
        function generateBuildingReport() {
            Swal.fire({
                title: 'Generate Report?',
                text: "Generate comprehensive building safety report for all schools?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Generate',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generating...',
                        text: 'Building safety report generation started... This may take a moment.',
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // In real implementation, this would generate PDF report
                }
            });
        }

        function toggleSecondaryExits() {
            const floorsInput = document.getElementById('buildingFloorsInput');
            const secondaryExitContainer = document.getElementById('secondaryExitContainer');
            if (!secondaryExitContainer) return;

            const emergencyExitsSelect = document.getElementById('emergencyExitsSelect');
            if (parseInt(floorsInput.value) > 1) {
                secondaryExitContainer.style.display = 'block';
                emergencyExitsSelect.required = true;
                const hidden = document.getElementById('hiddenEmergencyExits');
                if (hidden) hidden.remove();
            } else {
                secondaryExitContainer.style.display = 'none';
                emergencyExitsSelect.required = false;
                emergencyExitsSelect.value = '';
                if (!document.getElementById('hiddenEmergencyExits')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.id = 'hiddenEmergencyExits';
                    hidden.name = 'emergency_exits';
                    hidden.value = '0';
                    document.getElementById('addBuildingForm')?.appendChild(hidden);
                }
            }
        }

        function onBuildingFloorsChanged() {
            applyBuildingModalFloorLayout();
            focusBuildingFloorsInput();
        }
        document.getElementById('buildingFloorsInput')?.addEventListener('input', onBuildingFloorsChanged);
        document.getElementById('buildingFloorsInput')?.addEventListener('change', onBuildingFloorsChanged);

        let floorActionType = null;

        function incrementFloor() {
            if (floorActionType === 'reduce') return;
            const input = document.getElementById('buildingFloorsInput');

            if (window.currentEditingBuilding) {
                if (!floorActionType) {
                    floorActionType = 'add';
                    const decBtn = document.getElementById('btnDecFloors');
                    if (decBtn) decBtn.style.display = 'none';
                    const msgEl = document.getElementById('floorActionMessage');
                    if (msgEl) {
                        msgEl.textContent = 'You can only add floors right now. Apply this action first to reduce floors.';
                        msgEl.style.display = 'block';
                    }
                }
            }
            incrementValue('buildingFloorsInput');
            applyBuildingModalFloorLayout();
            focusBuildingFloorsInput();
        }

        function incrementValue(elementId) {
            const el = document.getElementById(elementId);
            const val = parseInt(el.value) || 0;
            const max = el.hasAttribute('max') ? parseInt(el.max) : null;
            if (max === null || val < max) {
                el.value = val + 1;
            }
        }


        // Reset form when opening Add Building modal in Add mode
        document.getElementById('addBuildingModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            // If button has data-school-id, it's the Add button (not Update)
            if (button && button.hasAttribute('data-school-id') && !button.hasAttribute('data-building-id')) {
                const form = document.getElementById('addBuildingForm');
                form.reset();
                form.action = "{{ route('fire-safety.building.store') }}";

                // Reset inputs to default
                // Reset inputs to default and unlock
                document.getElementById('buildingFloorsInput').value = 1;
                document.getElementById('buildingFloorsInput').min = 1;
                document.getElementById('buildingFloorsInput').readOnly = false;
                document.getElementById('btnIncFloors').style.display = 'none';

                document.getElementById('buildingRoomsInput').value = 1;
                document.getElementById('buildingRoomsInput').min = 1;
                document.getElementById('buildingRoomsInput').readOnly = false;
                document.getElementById('btnIncRooms').style.display = 'none';

                document.querySelector('#addBuildingModal .modal-title').innerHTML = '<i class="fas fa-plus me-2"></i> Add New Building';
                document.getElementById('roomFloorInputs').style.display = 'block';
                document.getElementById('buildingReqExt').value = 0;

                applyBuildingModalFloorLayout();

                // Reset states
                roomActionType = null;
                floorActionType = null;
                document.getElementById('roomActionMessage').style.display = 'none';
                document.getElementById('floorActionMessage').style.display = 'none';
                document.getElementById('roomReductionContainer').style.display = 'none';

                // Enable building type select
                document.getElementById('building_type_select').disabled = false;
                document.getElementById('other_building_type_container').style.display = 'none';
                document.getElementById('other_building_type').value = '';
                document.getElementById('other_building_type').removeAttribute('required');
            }
        });

        // Open Inspection Checklist Modal
        async function openInspectionChecklist(buildingId, buildingCode) {
            const modal = new bootstrap.Modal(document.getElementById('inspectionChecklistModal'));
            const contentDiv = document.getElementById('checklistContent');
            const codeSpan = document.getElementById('checklistBuildingCode');
            const updateBtn = document.getElementById('btnGoToUpdate');

            codeSpan.textContent = buildingCode;

            // Show loading state
            contentDiv.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Analyzing building safety compliance...</p>
                </div>
            `;

            modal.show();

            try {
                const response = await fetch(`/fire-safety/building/${buildingId}`);
                const building = await response.json();

                // Calculate compliance metrics
                const alarmCount = building.alarm_systems_count || 0;
                const extinguisherCount = building.fire_extinguishers_count || 0;
                const requiredExtinguishers = building.required_extinguishers_count || 0;
                const emergencyExits = building.emergency_exits || 0;
                const hasEvacuationPlan = building.has_evacuation_plan || false;

                // Build checklist HTML
                let html = `
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-building me-2 text-primary"></i>
                                <strong>Building:</strong> ${building.building_name || 'N/A'}
                            </div>
                            <span class="badge bg-secondary">${building.building_type || 'N/A'}</span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-layer-group me-2 text-info"></i>
                                <strong>Floors:</strong> ${building.floors || 0}
                            </div>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-door-closed me-2 text-warning"></i>
                                <strong>Rooms:</strong> ${building.rooms || 0}
                            </div>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center ${alarmCount > 0 ? 'bg-success-subtle' : 'bg-danger-subtle'}">
                            <div>
                                <i class="fas fa-bell me-2"></i>
                                <strong>Alarm Systems:</strong> ${alarmCount}
                            </div>
                            <span class="badge ${alarmCount > 0 ? 'bg-success' : 'bg-danger'}">
                                ${alarmCount > 0 ? 'Installed' : 'Missing'}
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center ${extinguisherCount >= requiredExtinguishers ? 'bg-success-subtle' : 'bg-warning-subtle'}">
                            <div>
                                <i class="fas fa-fire-extinguisher me-2"></i>
                                <strong>Fire Extinguishers:</strong> ${extinguisherCount} / ${requiredExtinguishers} required
                            </div>
                            <span class="badge ${extinguisherCount >= requiredExtinguishers ? 'bg-success' : 'bg-warning'}">
                                ${extinguisherCount >= requiredExtinguishers ? 'Compliant' : 'Needs More'}
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center ${emergencyExits >= 2 ? 'bg-success-subtle' : 'bg-warning-subtle'}">
                            <div>
                                <i class="fas fa-door-open me-2"></i>
                                <strong>Emergency Exits:</strong> ${emergencyExits}
                            </div>
                            <span class="badge ${emergencyExits >= 2 ? 'bg-success' : 'bg-warning'}">
                                ${emergencyExits >= 2 ? 'Adequate' : 'Needs Review'}
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center ${hasEvacuationPlan ? 'bg-success-subtle' : 'bg-danger-subtle'}">
                            <div>
                                <i class="fas fa-map-signs me-2"></i>
                                <strong>Evacuation Plan:</strong>
                            </div>
                            <span class="badge ${hasEvacuationPlan ? 'bg-success' : 'bg-danger'}">
                                ${hasEvacuationPlan ? 'Available' : 'Not Available'}
                            </span>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Click "Update Information" to modify building details or add missing safety equipment.
                    </div>
                `;

                contentDiv.innerHTML = html;

                // Wire up the Update button
                updateBtn.onclick = function() {
                    modal.hide();
                    // Trigger the view building button click to open update modal
                    setTimeout(() => {
                        const viewBtn = document.querySelector(`[data-building-id="${buildingId}"]`);
                        if (viewBtn) {
                            viewBtn.click();
                        }
                    }, 300);
                };

            } catch (error) {
                console.error('Error loading building data:', error);
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load building information. Please try again.
                    </div>
                `;
            }
        }
        // Open Building Alarms Modal
        async function openBuildingAlarms(buildingId, autoAdd = false) {
             // Close any open modals
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewBuildingModal'));
            if (viewModal) viewModal.hide();

            // Set current building context
            const response = await fetch(`/fire-safety/building/${buildingId}`);
            if (!response.ok) {
                Swal.fire('Error', 'Failed to load building data', 'error');
                return;
            }
            const building = await response.json();
            // Store current building context for update-alarm modal display
            window.currentAlarmModalBuildingId = buildingId;
            currentAlarmsBuildingId = buildingId;

            // Set context for Add Modal
            document.getElementById('addAlarmBuildingId').value = buildingId;
            document.getElementById('addAlarmBuildingNameDisplay').textContent = building.building_no + (building.building_name ? ` (${building.building_name})` : '');
            document.getElementById('addAlarmSchoolId').value = building.unified_school_id;

            // Populate Floor Select for Add Modal
            const floorSelect = document.getElementById('addFloorSelect');
            floorSelect.innerHTML = '<option value="">Select Floor</option><option value="all">All Floors</option>';
            for (let i = 1; i <= building.floors; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `Floor ${i}`;
                floorSelect.appendChild(opt);
            }

            // Populate Multi-Building Select
            loadMultiBuildingOptions(building.unified_school_id, buildingId, 'addMultiBuildingSelect');

            if (autoAdd) {
                // Open Add Modal Directly
                const addModal = new bootstrap.Modal(document.getElementById('addAlarmModal'));
                addModal.show();
            } else {
                // Open List Modal
                const listModal = new bootstrap.Modal(document.getElementById('buildingAlarmsModal'));
                document.getElementById('alarmsModalTitle').textContent = `Alarms for ${building.building_no}`;

                // Configure "Add New" button in List Modal
                const btnAdd = document.getElementById('btnAddNewAlarmInList');
                if (btnAdd) {
                    btnAdd.onclick = () => {
                        listModal.hide();
                        const addModal = new bootstrap.Modal(document.getElementById('addAlarmModal'));
                        addModal.show();
                         // Re-open list on close of add
                        document.getElementById('addAlarmModal').addEventListener('hidden.bs.modal', function () {
                            // Only re-open if not confirmed save (handled elsewhere) or if cancelled
                            // actually simpler to just let user re-open if needed, or reload list
                             listModal.show();
                             loadBuildingAlarms(buildingId); // Refresh list
                        }, { once: true });
                    };
                }

                // Print Button
                 const printBtn = document.getElementById('printAlarmsBtn');
                 if(printBtn) printBtn.href = `/fire-safety/alarms/print/${buildingId}`; // Hypothetical route

                listModal.show();
                loadBuildingAlarms(buildingId);
            }
        }

        let pendingAlarmFocusId = null;
        let currentAlarmsBuildingId = null;

        // Load Alarms for List
        async function loadBuildingAlarms(buildingId) {
            const tbody = document.querySelector('#buildingAlarmsTable tbody');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Loading alarms...</td></tr>';
            currentAlarmsBuildingId = buildingId;

            try {
                // We reuse the getBuilding endpoint which now includes alarmSystems
                const response = await fetch(`/fire-safety/building/${buildingId}`);
                const building = await response.json();
                const alarms = building.alarm_systems || [];

                if (alarms.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No alarms installed in this building yet.</td></tr>';
                } else {
                    let html = '';
                    alarms.forEach(alarm => {
                        const detail = (alarm.installation_location_detail || alarm.location || '').toString().trim() || '—';
                        const anchored = alarm.is_anchored_unassigned === true;
                        const coverageOnly = alarm.is_coverage_only === true;
                        const installedHere = !anchored && (alarm.is_installed_here === true || parseInt(alarm.building_id, 10) === parseInt(buildingId, 10));

                        let locationCell;
                        if (anchored) {
                            locationCell = `<span class="d-block">${detail}</span><span class="badge bg-warning text-dark mt-1">Unassigned</span>`;
                        } else if (installedHere) {
                            locationCell = `<span class="d-block">${detail}</span><span class="badge bg-success mt-1">Installed here</span>`;
                        } else {
                            const site = (alarm.installation_site_label || 'Unknown building').toString();
                            locationCell = `<span class="text-muted small d-block">Installed at: <strong>${site}</strong></span><span class="small">${detail}</span>`;
                        }

                        let extraBtns = '';
                        if (anchored) {
                            const aid = alarm.anchor_building_id != null ? alarm.anchor_building_id : buildingId;
                            extraBtns = `
                                <button type="button" class="btn btn-outline-danger" onclick="deleteAlarmSystem(${alarm.id}, ${aid})" title="Remove alarm"><i class="fas fa-trash"></i></button>
                                <button type="button" class="btn btn-outline-secondary" onclick="openUpdateAlarmModal(${alarm.id}, ${buildingId})" title="Reassign to a building"><i class="fas fa-random me-1"></i> Reassign</button>`;
                        } else if (installedHere) {
                            extraBtns = `<button type="button" class="btn btn-outline-danger" onclick="deleteAlarmSystem(${alarm.id}, ${alarm.building_id})" title="Remove alarm"><i class="fas fa-trash"></i></button>`;
                        } else if (coverageOnly) {
                            extraBtns = `<button type="button" class="btn btn-outline-warning" onclick="unassignAlarmFromBuilding(${alarm.id}, ${buildingId})" title="Unassign from this building"><i class="fas fa-unlink me-1"></i> Unassign</button>`;
                        }

                        html += `
                                <tr id="alarm-row-${alarm.id}">
                                    <td class="fw-bold text-primary">${alarm.code}</td>
                                    <td>${alarm.alarm_type}</td>
                                    <td>${locationCell}</td>
                                    <td><span class="badge ${getAlarmStatusBadge(alarm.status)}">${formatStatus(alarm.status)}</span></td>
                                    <td>${formatFloorLabel(alarm.floor || alarm.floor_no || alarm.floor_id || 'N/A')}</td>
                                    <td>${alarm.last_test ? formatDate(alarm.last_test) : '<span class="text-warning">Never</span>'}</td>
                                    <td>${alarm.next_test_due ? formatDate(alarm.next_test_due) : '-'}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" onclick="openUpdateAlarmModal(${alarm.id}, ${buildingId})" title="Update">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            ${extraBtns}
                                        </div>
                                    </td>
                                </tr>
                            `;
                    });
                    tbody.innerHTML = html;

                    if (pendingAlarmFocusId) {
                        const targetRow = document.getElementById(`alarm-row-${pendingAlarmFocusId}`);
                        if (targetRow) {
                            targetRow.classList.add('table-warning');
                            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            setTimeout(() => targetRow.classList.remove('table-warning'), 2500);
                        }
                        pendingAlarmFocusId = null;
                    }
                }

            } catch (error) {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger">Failed to load alarms.</td></tr>';
            }
        }

        function updateUpcomingTests(alarms, isMain = false) {
            const listId = isMain ? 'mainUpcomingTestsList' : 'upcomingTestsList';
            const list = document.getElementById(listId);
            if(!list) return;

            const todayIso = new Date().toISOString().split('T')[0];
            const toDateOnly = (dateValue) => {
                if (!dateValue) return null;
                if (typeof dateValue === 'string' && dateValue.length >= 10) {
                    return dateValue.slice(0, 10);
                }
                const d = new Date(dateValue);
                if (Number.isNaN(d.getTime())) return null;
                return d.toISOString().split('T')[0];
            };

            const upcoming = alarms
                .filter(a => {
                    const nextDue = a.next_test_due || a.next_test_date || a.next_due_date || null;
                    if (!nextDue) return false;
                    const nextDueIso = toDateOnly(nextDue);
                    if (!nextDueIso) return false;
                    a.next_test_due = nextDueIso;

                    const lastTestIso = toDateOnly(a.last_test);
                    // Hide alarms only when already tested for the current due cycle.
                    // If user updates next due to a future date, it appears again.
                    return !lastTestIso || nextDueIso > lastTestIso;
                })
                .sort((a,b) => a.next_test_due.localeCompare(b.next_test_due));

            if (upcoming.length === 0) {
                list.innerHTML = '<li class="list-group-item text-muted small text-center py-3">No upcoming tests scheduled.</li>';
                return;
            }

            let html = '';
            upcoming.forEach(a => {
                const diffDays = Math.floor((Date.parse(a.next_test_due) - Date.parse(todayIso)) / (1000 * 60 * 60 * 24));

                let badge = 'bg-success';
                let statusLabel = 'Upcoming';
                if (diffDays < 0) {
                    badge = 'bg-danger';
                    statusLabel = 'Due';
                } else if (diffDays === 0) {
                    badge = 'bg-warning text-dark';
                    statusLabel = 'Today';
                }

                const targetBuildingId = a.building_id || a.primary_building_id || '';
                const installedHere = [a.building_no, a.building_name].filter(Boolean).join(' - ');

                html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 upcoming-test-item"
                        role="button"
                        onclick="openUpcomingAlarmModal(${a.id}, ${targetBuildingId || 'null'})"
                        title="Open alarm details">
                        <div>
                            <div class="fw-bold text-primary">${a.code}</div>
                            <div class="small text-muted">${a.location || 'No location set'}</div>
                            <div class="small text-secondary">Installed here: ${installedHere || 'Unknown building'}</div>
                        </div>
                        <div class="text-end">
                            <div class="small fw-bold">${formatDate(a.next_test_due)}</div>
                            <span class="badge ${badge} rounded-pill">${statusLabel}</span>
                            ${diffDays <= 0 ? `
                                <div class="mt-2">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="event.stopPropagation(); quickTestAlarmFromUpcoming(${a.id})"
                                            title="Test Now">
                                        <i class="fas fa-check-circle me-1"></i> Test Now
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    </li>
                `;
            });
            list.innerHTML = html;
        }

        async function quickTestAlarmFromUpcoming(alarmId) {
            await testAlarm(alarmId, false);
        }

        function openUpcomingAlarmModal(alarmId, buildingId = null) {
            pendingAlarmFocusId = alarmId;

            if (buildingId) {
                openBuildingAlarms(buildingId);
            } else {
                openUpdateAlarmModal(alarmId);
            }
        }

        async function fetchSchoolUpcomingTests(schoolId) {
            try {
                const response = await fetch(`/fire-safety/buildings/${schoolId}`);
                const buildings = await response.json();
                let allAlarms = [];
                const alarmMap = new Map(); // To avoid duplicates

                buildings.forEach(b => {
                    const alarms = b.alarm_systems || b.alarmSystems || [];
                    const alarmsMany = b.alarm_systems_many || b.alarmSystemsMany || [];

                    [...alarms, ...alarmsMany].forEach(a => {
                        if (!alarmMap.has(a.id)) {
                            a.building_no = b.building_no;
                            a.building_name = b.building_name;
                            a.primary_building_id = b.id;
                            if (!a.building_id) a.building_id = b.id;
                            alarmMap.set(a.id, a);
                            allAlarms.push(a);
                        }
                    });
                });
                updateUpcomingTests(allAlarms, true);
            } catch(e) {
                console.error('Error fetching upcoming tests:', e);
                const list = document.getElementById('mainUpcomingTestsList');
                if (list) {
                    list.innerHTML = '<li class="list-group-item text-danger small text-center py-3">Failed to load upcoming tests.</li>';
                }
            }
        }

        async function loadMultiBuildingOptions(schoolId, excludeBuildingIds, selectId = 'addMultiBuildingSelect') {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.innerHTML = '';

            const excluded = Array.isArray(excludeBuildingIds)
                ? excludeBuildingIds.map(id => String(id))
                : [String(excludeBuildingIds)];

            try {
                const response = await fetch(`/fire-safety/buildings-list/${schoolId}`);
                const buildings = await response.json();

                buildings.forEach(b => {
                    if (!excluded.includes(String(b.id))) {
                        const opt = document.createElement('option');
                        opt.value = b.id;
                        opt.textContent = `${b.building_no} - ${b.building_name || 'No Name'}`;
                        select.appendChild(opt);
                    }
                });
            } catch(e) { console.error(e); }
        }

        function setAddAlarmFloorRequired(requireFloors) {
            const floorSel = document.getElementById('addFloorSelect');
            if (!floorSel) return;
            if (requireFloors) {
                floorSel.setAttribute('required', 'required');
            } else {
                floorSel.removeAttribute('required');
                floorSel.setCustomValidity('');
            }
        }

        // Toggle multi-building select
        document.getElementById('coversMultiple').addEventListener('change', function() {
            const container = document.getElementById('multiBuildingSelectContainer');
            const floorRow = document.getElementById('floorSelectionRow');
            if (this.checked) {
                container.style.display = 'block';
                floorRow.style.display = 'none';
                document.getElementById('addFloorSelect').value = ''; // Reset floor
                setAddAlarmFloorRequired(false);
            } else {
                container.style.display = 'none';
                floorRow.style.display = 'block';
                setAddAlarmFloorRequired(true);
            }
        });

        document.getElementById('addAlarmModal')?.addEventListener('show.bs.modal', function () {
            const cov = document.getElementById('coversMultiple');
            if (cov && cov.checked) {
                setAddAlarmFloorRequired(false);
            } else {
                setAddAlarmFloorRequired(true);
            }
        });

        // toggle for update modal multi-building
        const updateCoversChk = document.getElementById('updateCoversMultiple');
        if (updateCoversChk) {
            updateCoversChk.addEventListener('change', function() {
                const container = document.getElementById('updateMultiBuildingSelectContainer');
                const sel = document.getElementById('updateMultiBuildingSelect');
                if (this.checked) {
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                    if(sel) Array.from(sel.options).forEach(o=>o.selected = false);
                }
            });
        }

        async function unassignAlarmFromBuilding(alarmId, buildingId) {
            try {
                const res = await fetch(`/fire-safety/alarm/${alarmId}/unassign-building`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ building_id: buildingId }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    Swal.fire('Error', data.message || 'Could not unassign.', 'error');
                    return;
                }
                const sid = document.getElementById('addAlarmSchoolId')?.value || currentSchoolId;
                if (sid) await refreshSchoolBuildingAlarmCards(sid);
                await swalFireSuccess({ title: 'Updated', text: data.message || 'Unassigned.', reloadAfter: true });
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Failed to unassign.', 'error');
            }
        }

        // Save Alarm
        async function saveAlarmSystem() {
            const form = document.getElementById('addAlarmForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);

            try {
                const response = await fetch(FIRE_SAFETY_ALARM_STORE_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    Swal.fire('Error', 'Could not save alarm (invalid server response). Check your session or console.', 'error');
                    console.error('Alarm store response:', raw);
                    return;
                }

                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addAlarmModal'));
                    if (modal) modal.hide();
                    await swalFireSuccess({ title: 'Success', text: data.message || 'Alarm system added successfully!', reloadAfter: true });
                } else {
                    let msg = data.message || 'Could not save alarm.';
                    if (data.errors && typeof data.errors === 'object') {
                        msg = Object.values(data.errors).flat().join(' ') || msg;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Failed to save alarm system.', 'error');
            }
        }

        // Open Update Modal
        async function openUpdateAlarmModal(alarmId, contextBuildingId = null) {
             try {
                const response = await fetch(`/fire-safety/alarm/${alarmId}`);
                const alarm = await response.json();

                if (!alarm) throw new Error('Alarm not found');

                const modal = new bootstrap.Modal(document.getElementById('updateAlarmModal'));
                const form = document.getElementById('updateAlarmForm');

                document.getElementById('updateAlarmId').value = alarm.id;
                document.getElementById('updateSchoolId').value = alarm.unified_school_id;
                document.getElementById('originalAlarmCode').value = alarm.code;
                document.getElementById('updateAlarmContextBuildingId').value =
                    contextBuildingId || window.currentAlarmModalBuildingId || alarm.building_id || alarm.anchor_building_id || '';

                document.getElementById('updateAlarmBuildingId').value = alarm.building_id || '';
                const ctxId = contextBuildingId || window.currentAlarmModalBuildingId || alarm.building_id || alarm.anchor_building_id;
                try {
                    if (ctxId) {
                        const bresp = await fetch(`/fire-safety/building/${ctxId}`);
                        const building = await bresp.json();
                        const labelCode = building.building_no || building.building_name || 'Unknown';
                        document.getElementById('updateAlarmBuildingNameDisplay').textContent =
                            `${labelCode} (Current Building)`;
                    } else {
                        document.getElementById('updateAlarmBuildingNameDisplay').textContent = 'Select a building below';
                    }
                } catch(e) {
                    console.error('Failed to load building for update modal', e);
                    document.getElementById('updateAlarmBuildingNameDisplay').textContent = 'Unknown (Current Building)';
                }

                // determine already assigned buildings (excluding primary location)
                const assignedIds = alarm.buildings ? alarm.buildings.map(b => b.id) : [];

                // load multi-building options for update, excluding primary + already assigned
                await loadMultiBuildingOptions(
                    alarm.unified_school_id,
                    [alarm.building_id, ...assignedIds].filter(x => x != null && x !== ''),
                    'updateMultiBuildingSelect'
                );

                // determine multi status and show current list
                const isMulti = assignedIds.length > 0;
                const updateCheck = document.getElementById('updateCoversMultiple');
                if (updateCheck) updateCheck.checked = isMulti;
                const multiContainer = document.getElementById('updateMultiBuildingSelectContainer');
                const currentBuildingsList = document.getElementById('updateCurrentBuildingsList');

                if (isMulti) {
                    if (multiContainer) multiContainer.style.display = 'block';
                    // Display currently assigned buildings
                    if (currentBuildingsList && alarm.buildings) {
                        const buildingText = alarm.buildings
                            .map(b => `<span class="badge bg-primary me-2">${b.building_no}${b.building_name ? ' - ' + b.building_name : ''}</span>`)
                            .join('');
                        currentBuildingsList.innerHTML = `<div class="mb-2">${buildingText}</div>`;
                    }
                } else {
                    if (multiContainer) multiContainer.style.display = 'none';
                    if (currentBuildingsList) currentBuildingsList.innerHTML = '';
                }

                document.getElementById('updateAlarmCode').value = alarm.code;
                document.getElementById('updateAlarmTypeDisplay').value = alarm.alarm_type;
                document.getElementById('updateAlarmLocation').value = alarm.location;
                document.getElementById('updateManufacturer').value = alarm.manufacturer || '';
                document.getElementById('updateInstallationDate').value = alarm.installation_date || '';
                document.getElementById('updateLastTestDate').value = alarm.last_test ? alarm.last_test.split('T')[0] : '';
                document.getElementById('updateNextTestDue').value = alarm.next_test_due ? alarm.next_test_due.split('T')[0] : '';
                document.getElementById('updateNotes').value = alarm.notes || '';

                // Populate Floor Select for Update Modal
                const floorSelect = document.getElementById('updateFloorSelect');
                try {
                    const floorBid = alarm.building_id || alarm.anchor_building_id || ctxId || null;
                    const b = alarm.building || (floorBid ? await fetch(`/fire-safety/building/${floorBid}`).then(r => r.json()) : { floors: 1 });
                    floorSelect.innerHTML = '<option value="">Select Floor</option><option value="all">All Floors</option>';
                    const floors = b.floors || 1;
                    for (let i = 1; i <= floors; i++) {
                        const opt = document.createElement('option');
                        opt.value = i;
                        opt.textContent = `Floor ${i}`;
                        floorSelect.appendChild(opt);
                    }
                    // Pre-select current floor
                    if (alarm.floor_id) {
                        floorSelect.value = alarm.floor_id;
                    }
                } catch(e) {
                    console.error('Failed to populate floors', e);
                }

                const statusSelect = document.getElementById('updateStatusSelect');
                fillAlarmStatusSelect(statusSelect, alarm.alarm_type, alarm.status);

                // Hide Alarm List modal temporarily
                const listModal = bootstrap.Modal.getInstance(document.getElementById('buildingAlarmsModal'));
                if(listModal) listModal.hide();

                modal.show();

                // On close, re-open list
                document.getElementById('updateAlarmModal').addEventListener('hidden.bs.modal', function () {
                     const buildingId = alarm.building_id; // Need to know which building to re-open
                     // But alarm might belong to multiple.
                     // We can rely on global or just fail gracefully.
                     // Better: check if buildingAlarmsModal was open before.
                     // For now, let's just let user navigate back.
                }, { once: true });

            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Failed to load alarm details.', 'error');
            }
        }

        async function updateAlarmSystem() {
            const id = document.getElementById('updateAlarmId').value;
            const form = document.getElementById('updateAlarmForm');
             if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Convert form values to plain object, preserving arrays (e.g. building_ids[])
            const formData = new FormData(form);
            const formObj = {};
            for (const [key, value] of formData.entries()) {
                if (formObj.hasOwnProperty(key)) {
                    if (Array.isArray(formObj[key])) {
                        formObj[key].push(value);
                    } else {
                        formObj[key] = [formObj[key], value];
                    }
                } else {
                    formObj[key] = value;
                }
            }

            // Ensure building_id is included
            if (!formObj.building_id || formObj.building_id === '') {
                formObj.building_id = document.getElementById('updateAlarmBuildingId').value;
            }

            const covChk = document.getElementById('updateCoversMultiple');
            formObj.covers_multiple = !!(covChk && covChk.checked);

            try {
                const response = await fetch(`/fire-safety/alarm/${id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formObj)
                });
                const rawUpd = await response.text();
                let data;
                try {
                    data = JSON.parse(rawUpd);
                } catch (e) {
                    Swal.fire('Error', 'Invalid response while updating alarm.', 'error');
                    console.error(rawUpd);
                    return;
                }

                 if (data.success) {
                    const updateModal = bootstrap.Modal.getInstance(document.getElementById('updateAlarmModal'));
                    if (updateModal) updateModal.hide();
                    await swalFireSuccess({ title: 'Success', text: data.message || 'Alarm system updated!', reloadAfter: true });
                } else {
                    let msg = data.message || 'Update failed.';
                    if (data.errors && typeof data.errors === 'object') {
                        msg = Object.values(data.errors).flat().join(' ') || msg;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Failed to update alarm.', 'error');
            }
        }

        // Remove Alarm
        let alarmIdToRemove = null;
        let alarmRemovePrimaryBuildingId = null;
        function deleteAlarmSystem(id, primaryBuildingId) {
            alarmIdToRemove = id;
            alarmRemovePrimaryBuildingId = primaryBuildingId != null ? String(primaryBuildingId) : '';
            const modal = new bootstrap.Modal(document.getElementById('alarmRemovalModal'));

            // Hide list modal
            const listModal = bootstrap.Modal.getInstance(document.getElementById('buildingAlarmsModal'));
            if(listModal) listModal.hide();

            modal.show();
        }

        async function confirmRemoveAlarm() {
            if(!alarmIdToRemove) return;

            const reason = document.getElementById('alarmRemovalReason').value;
             if (!reason.trim()) {
                Swal.fire('Reason Required', 'Please provide a reason for removal.', 'warning');
                return;
            }

            try {
                const response = await fetch(`/fire-safety/alarm/${alarmIdToRemove}/remove`, {
                    method: 'POST',
                     headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reason: reason,
                        from_building_id: alarmRemovePrimaryBuildingId ? parseInt(alarmRemovePrimaryBuildingId, 10) : null,
                    })
                });

                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    Swal.fire('Error', 'Invalid response while removing alarm.', 'error');
                    return;
                }
                 if (data.success) {
                    const removalModal = bootstrap.Modal.getInstance(document.getElementById('alarmRemovalModal'));
                    if (removalModal) removalModal.hide();
                    document.getElementById('alarmRemovalReason').value = '';
                    alarmIdToRemove = null;
                    alarmRemovePrimaryBuildingId = null;
                    await swalFireSuccess({ title: 'Removed', text: data.message || 'Alarm system removed and archived.', reloadAfter: true });
                } else {
                    Swal.fire('Error', data.message || 'Remove failed', 'error');
                }
            } catch (error) {
                 Swal.fire('Error', 'Failed to remove alarm.', 'error');
            }
        }

        async function testAlarm(id, silent = false) {
             try {
                const response = await fetch(`/fire-safety/alarm/${id}/test`, {
                    method: 'POST',
                     headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if(data.success) {
                    if (!silent) {
                        Swal.fire({
                            title: 'Tested!',
                            text: 'Alarm test recorded successfully.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                    location.reload();
                }
             } catch(e) {
                 if (!silent) Swal.fire('Error', 'Failed to record test.', 'error');
             }
        }

        function getAlarmStatusBadge(status) {
            status = (status || '').toLowerCase();
            if (status === 'active' || status === 'functional' || status === 'online') return 'bg-success';
            if (status === 'offline') return 'bg-secondary';
            if (status === 'broken' || status === 'maintenance' || status === 'under-repair') return 'bg-warning text-dark';
            if (status === 'missing' || status === 'not_installed' || status === 'decommissioned') return 'bg-danger';
            return 'bg-danger';
        }

        function formatStatus(status) {
            return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        async function openAlarmHistoryModal(schoolId) {
            // Close Alarms List if open
            const listModal = bootstrap.Modal.getInstance(document.getElementById('buildingAlarmsModal'));
            if(listModal) listModal.hide();

            const modalEl = document.getElementById('alarmHistoryModal');
            const modal = new bootstrap.Modal(modalEl);
            const tbody = document.querySelector('#alarmHistoryTable tbody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading history...</td></tr>';

            modal.show();

            try {
                const response = await fetch(`/fire-safety/alarm/history/${schoolId}`);
                const history = await response.json();

                if (history.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No history found.</td></tr>';
                } else {
                    let html = '';
                    history.forEach(item => {
                        let statusBad = 'bg-secondary';
                        let statusText = item.item_data.status || 'Unknown';

                        html += `
                            <tr>
                                <td>${formatDate(item.removed_at)}</td>
                                <td class="fw-bold text-danger">${item.item_code}</td>
                                <td>${item.item_data.alarm_type || 'N/A'}</td>
                                <td>${item.item_data.building_name || 'N/A'}</td>
                                <td>${item.reason}</td>
                                <td><span class="badge ${statusBad}">${statusText}</span></td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                }
            } catch (error) {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load history.</td></tr>';
            }
        }

        function filterBuildings(status) {
            const cards = document.querySelectorAll('.building-item');

            cards.forEach(card => {
                if (status === 'all') {
                    card.style.display = 'block';
                } else {
                    if (card.dataset.status === status) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });

            // Update filter button text
            const btn = document.getElementById('buildingFilterDropdown');
            if(btn) {
                if(status === 'all') btn.innerHTML = '<i class="fas fa-filter me-1"></i> Show All';
                else if(status === 'compliant') btn.innerHTML = '<i class="fas fa-check-circle me-1 border-success text-success"></i> Compliant Only';
                else if(status === 'non-compliant') btn.innerHTML = '<i class="fas fa-exclamation-circle me-1 border-danger text-danger"></i> Non-Compliant Only';
            }
        }

        // Initialize school-wide alarm tests and inspections on load
        if (currentSchoolId) {
            fetchSchoolUpcomingTests(currentSchoolId);
            loadInspections(currentSchoolId);
        }

        function wireAddAlarmTypeStatus() {
            const typeSel = document.getElementById('addAlarmType');
            const statusSel = document.getElementById('addStatusSelect');
            if (!typeSel || !statusSel) return;
            typeSel.addEventListener('change', function() {
                const typeName = this.options[this.selectedIndex]?.value || '';
                fillAlarmStatusSelect(statusSel, typeName, statusSel.value);
            });
            fillAlarmStatusSelect(statusSel, typeSel.value || '', 'active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            wireAddAlarmTypeStatus();
        });

        // ==========================================
        // JS Logic for Building Updates (Reductions)
        // ==========================================



        // ==========================================
        // JS Logic for Building Updates (Reductions)
        // ==========================================

        let roomActionType = null;
        let originalRoomsCount = 0;

        function incrementRoom() {
            if (roomActionType === 'reduce') return;
            const input = document.getElementById('buildingRoomsInput');
            const incBtn = document.getElementById('btnIncRooms');
            const decBtn = document.getElementById('btnDecRooms');
            const msgEl = document.getElementById('roomActionMessage');

            input.value = parseInt(input.value) + 1;

            if (!roomActionType) {
                roomActionType = 'add';
                if (decBtn) decBtn.style.display = 'none';
                msgEl.textContent = 'You can only add rooms right now. Save or reload to reduce rooms.';
                msgEl.style.display = 'block';
            }
        }

        function decrementRoom() {
            if (roomActionType === 'add') return;
            const input = document.getElementById('buildingRoomsInput');
            const incBtn = document.getElementById('btnIncRooms');
            const decBtn = document.getElementById('btnDecRooms');
            const msgEl = document.getElementById('roomActionMessage');
            const building = window.currentEditingBuilding;

            if (!building) return;

            if (parseInt(input.value) <= 1) {
                Swal.fire('Limit Reached', 'A building must have at least 1 room.', 'warning');
                return;
            }

            input.value = parseInt(input.value) - 1;

            if (!roomActionType) {
                roomActionType = 'reduce';
                if (incBtn) incBtn.style.display = 'none';
                msgEl.textContent = 'You can only reduce rooms right now. Save or reload to add rooms.';
                msgEl.style.display = 'block';
                document.getElementById('roomReductionContainer').style.display = 'block';
                populateInlineReduceRoomsList(building);
            }

            // Update UI
            const reduceAmount = originalRoomsCount - parseInt(input.value);
            document.getElementById('reduceRoomCountSpan').textContent = reduceAmount;

            // Limit checkbox selection
            updateRoomReduceCheckboxes(reduceAmount);
        }

        function populateInlineReduceRoomsList(building) {
            const listObj = document.getElementById('inlineReduceRoomsList');
            listObj.innerHTML = '';

            if (building.rooms_list && building.rooms_list.length > 0) {
                building.rooms_list.forEach(room => {
                    const roomName = room.room_name || room.room_no;
                    const isCenterLabel = room.is_center_room ? ' <span class="badge bg-warning text-dark"><i class="fas fa-fire-extinguisher"></i> Assumes FEXT</span>' : '';
                    listObj.innerHTML += `
                        <label class="list-group-item">
                            <input class="form-check-input me-1 room-reduce-checkbox" type="checkbox" name="removed_room_id[]" value="${room.id}">
                            ${roomName} (Floor ${room.floor_no}) ${isCenterLabel}
                        </label>
                    `;
                });

                const checkboxes = listObj.querySelectorAll('.room-reduce-checkbox');
                checkboxes.forEach(cb => {
                    cb.addEventListener('change', () => {
                        const input = document.getElementById('buildingRoomsInput');
                        const reduceAmount = originalRoomsCount - parseInt(input.value);
                        updateRoomReduceCheckboxes(reduceAmount);
                    });
                });
            } else {
                listObj.innerHTML = `<div class="text-center text-muted p-2">No active rooms available to remove. Just confirming count reduction.</div>`;
            }
        }

        function updateRoomReduceCheckboxes(maxAllowed) {
            const listObj = document.getElementById('inlineReduceRoomsList');
            const checkboxes = listObj.querySelectorAll('.room-reduce-checkbox');
            const checkedCount = listObj.querySelectorAll('.room-reduce-checkbox:checked').length;

            checkboxes.forEach(box => {
                if (!box.checked) box.disabled = (checkedCount >= maxAllowed);
            });
        }

        function openReduceFloorsModal() {
            if (floorActionType === 'add') return;

            const building = window.currentEditingBuilding;
            if (!building) return;
            if (building.floors <= 1) {
                Swal.fire('Limit Reached', 'A building must have at least 1 floor.', 'warning');
                return;
            }

            // Lock input mechanism logic applies FIRST
            if (!floorActionType) {
                floorActionType = 'reduce';
                document.getElementById('btnIncFloors').style.display = 'none';
                const msgEl = document.getElementById('floorActionMessage');
                msgEl.textContent = 'You can only reduce floors right now. Apply this action first to add floors.';
                msgEl.style.display = 'block';
            }

            document.getElementById('reduceFloorsCountDisplay').textContent = "1";
            document.getElementById('reduceFloorsReason').value = "";
            const listObj = document.getElementById('reduceFloorsList');

            // Just simulate removing the top floor
            const topFloor = building.floors;
            listObj.innerHTML = `
                <div class="text-danger fw-bold"><i class="fas fa-layer-group me-2"></i> Floor ${topFloor}</div>
                <div class="small mt-1 text-muted">Items to be archived/removed: Alarms (if specific to floor), Rooms, and Extinguishers on this floor.</div>
            `;

            const reduceModal = new bootstrap.Modal(document.getElementById('reduceFloorsModal'));
            reduceModal.show();
        }

        function cancelReduceFloors() {
            bootstrap.Modal.getInstance(document.getElementById('reduceFloorsModal')).hide();
        }

        async function confirmReduceFloors() {
            const building = window.currentEditingBuilding;
            if (!building) return;

            const reason = document.getElementById('reduceFloorsReason').value;
            if (!reason.trim()) {
                Swal.fire('Reason Required', 'Please provide a reason to remove this floor.', 'warning');
                return;
            }

            const topFloor = parseInt(building.floors);

            try {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('removed_floor', topFloor);
                formData.append('floor_removal_reason', reason);
                formData.append('building_no', building.building_no);

                // Use the update endpoint which we already configured to handle floor removal
                const response = await fetch(`/fire-safety/building/${building.id}/update`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                if (data.success) {
                    swalFireSuccess({ title: 'Success', text: 'Floor successfully removed.' }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Failed to remove floor.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
            }
        }

        async function confirmRemoveBuilding() {
            const building = window.currentEditingBuilding;
            if (!building) return;

            const { value: reason } = await Swal.fire({
                title: 'Remove 1st Floor / Building',
                text: 'Removing the final floor will completely delete the building and all its contents (alarms, extinguishers). State the reason:',
                input: 'textarea',
                icon: 'warning',
                inputPlaceholder: 'Reason for building removal...',
                showCancelButton: true,
                confirmButtonColor: '#A8191F',
                confirmButtonText: 'Permanently Delete Building',
                target: document.getElementById('addBuildingModal') || document.body,
                inputValidator: (value) => {
                    if (!value) return 'You need to write a reason!'
                }
            });

            if (reason) {
                try {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    formData.append('reason', reason);

                    const response = await fetch(`/fire-safety/building/${building.id}/remove`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });

                    const data = await response.json();
                    if (data.success) {
                        swalFireSuccess({ title: 'Deleted!', text: 'The building has been removed.' }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete building.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'An unexpected error occurred.', 'error');
                }
            }
        }

        async function openRemovedBuildingsHistoryModal(schoolId) {
            const modalEl = document.getElementById('removedBuildingsHistoryModal');
            const modal = new bootstrap.Modal(modalEl);
            const tbody = document.querySelector('#removedBuildingsHistoryTable tbody');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading history...</td></tr>';

            modal.show();

            try {
                const response = await fetch(`/fire-safety/building/removed-history/${schoolId}`);
                const history = await response.json();

                if (history.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No building history found.</td></tr>';
                } else {
                    let html = '';
                    history.forEach(item => {
                        html += `
                            <tr>
                                <td>${formatDate(item.removed_at)}</td>
                                <td class="fw-bold">${item.item_code}</td>
                                <td>${item.item_data.building_name || 'N/A'}</td>
                                <td>${item.item_data.type || 'N/A'}</td>
                                <td>${item.item_data.required_fext || '0'}</td>
                                <td>Const: ${item.item_data.year_constructed || 'N/A'}<br>Renov: ${item.item_data.last_renovation || 'N/A'}</td>
                                <td>
                                    <strong>Desc:</strong> ${item.item_data.description || 'N/A'}<br>
                                    <strong>Feat:</strong> ${item.item_data.safety_features || 'N/A'}
                                </td>
                                <td class="text-danger">${item.reason}</td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                }
            } catch (error) {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load history.</td></tr>';
            }
        }
    </script>
@endsection
