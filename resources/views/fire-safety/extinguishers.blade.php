@extends('layouts.fire-safety')

@section('title', 'Fire Extinguishers - Fire Safety')
@section('page_title', 'Fire Extinguisher & Rooms')

@section('styles')
    <style>
        .health-bar {
            height: 25px; /* Fatten/Large height */
            width: 100%;
            background-color: #e9ecef;
            border-radius: 12px;
            margin-top: 5px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
        }
        .health-bar-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .health-bar-text {
            position: absolute;
            width: 100%;
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 11px;
            font-weight: bold;
            color: #000;
            text-shadow: 0 0 2px rgba(255,255,255,0.8);
        }
        .health-good { background-color: #28a745; } /* OK */
        .health-warning { background-color: #ffc107; } /* For Preventive Maintenance */
        .health-danger { background-color: #dc3545; } /* Used/Missing */

        /* Card toggle styling */
        .card-header .toggle-icon {
            cursor: pointer;
            transition: transform 0.3s;
            margin-right: 10px;
        }

        .card-collapsed .card-body {
            display: none;
        }

        .card-collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        /* status carousel animation */
        #statusCarousel .status-slide {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            position: absolute;
            right: 0;
            left: 0;
        }
        #statusCarousel .status-slide.active {
            opacity: 1;
            position: relative;
        }
        #statusCarousel { position: relative; min-height: 2.5rem; }
    </style>
@endsection

@section('content')
    <!-- Main Content -->

    @if(!$activeSchool)
        <!-- Layout handles the "No Schools" alert -->
    @else
        @php
            $school = $activeSchool;
            $actualTotalRooms = $school->buildings->sum('rooms');
            $requiredExtinguishers = $school->buildings->sum('required_extinguishers_count');
            $allRoomsCollection = $school->buildings->flatMap(fn ($b) => $b->actualRooms);
            $allExts = $school->buildings->flatMap(fn ($b) => $b->fireExtinguishers);
            $coveredRoomIds = $allExts->flatMap(fn ($e) => $e->coveredRooms->pluck('id'))->unique();
            $uncoveredRoomsCount = max(0, $allRoomsCollection->count() - $coveredRoomIds->count()); // Based on created rooms
            $labRooms = $allRoomsCollection->where('room_type', 'laboratory');
            $labsCovered = $labRooms->filter(fn ($r) => $coveredRoomIds->contains($r->id))->count();

            // New Metrics
            $evaluationCount = $allExts->where('status', 'active')->count();
            $evaluationPassed = $requiredExtinguishers > 0 && $evaluationCount >= $requiredExtinguishers;
            $compliancePercent = $actualTotalRooms > 0 ? round(($coveredRoomIds->count() / $actualTotalRooms) * 100, 1) : 0;

            // Status Breakdown Metrics
            $activeCount = $allExts->where('status', 'active')->count();
            $maintenanceCount = $allExts->where('status', 'maintenance')->count();
            $usedCount = $allExts->where('status', 'expired')->count();
            $missingCount = $allExts->where('status', 'missing')->count();
            $totalExts = $allExts->count();
        @endphp

        <!-- Summary -->
        <div class="row mb-4">

                            <!-- Total Rooms -->
                            <div class="col-xl-3 col-lg-4 col-md-6 col-6 mb-4">
                                <div class="card dashboard-card h-100 border-left-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Rooms</div>
                                                <div class="h2 mb-0 fw-bold text-gray-800">{{ $allRoomsCollection->count() }}</div>
                                            </div>
                                            <i class="fas fa-door-closed fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Room Coverage (with Compliance %) -->
                            <div class="col-xl-3 col-lg-4 col-md-6 col-6 mb-4">
                                <div class="card dashboard-card h-100 border-left-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div style="width: 100%;">
                                                <div class="text-xs fw-bold text-dark text-uppercase mb-2">Room Coverage Status</div>
                                                <div class="mb-3 fw-bold">
                                                    <span class="text-success">{{ $coveredRoomIds->count() }} Covered<i class="bi bi-check-circle-fill ms-1"></i></span>
                                                    <span class="text-muted mx-1">|</span>
                                                    <span class="text-danger">{{ $uncoveredRoomsCount }} Uncovered X</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-xs text-muted text-uppercase fw-bold">School Coverage Compliance</span>
                                                    <span class="h5 mb-0 fw-bold text-info">{{ $compliancePercent }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing/Needed Extinguishers with Status Breakdown -->
                            <div class="col-xl-3 col-lg-4 col-md-6 col-6 mb-4">
                                <div class="card dashboard-card h-100 border-left-warning">
                                    <div class="card-body">
                                        <div>
                                            <div class="text-xs fw-bold text-warning text-uppercase mb-3">Existing / Needed</div>
                                            <div class="h2 mb-1 fw-bold text-gray-800">{{ $totalExts }} / {{ $requiredExtinguishers }}</div>
                                        </div>
                                        <div class="mt-3" style="position: relative;">
                                            <div id="statusCarousel" class="small">
                                                <div class="status-slide active mb-2">
                                                    <span class="badge bg-success d-inline-block">Active</span>
                                                    <span class="fw-bold ms-2">{{ $activeCount }}</span>
                                                </div>
                                                <div class="status-slide mb-2">
                                                    <span class="badge bg-warning d-inline-block">For Preventive Maintenance</span>
                                                    <span class="fw-bold ms-2">{{ $maintenanceCount }}</span>
                                                </div>
                                                <div class="status-slide mb-2">
                                                    <span class="badge bg-info d-inline-block">Used</span>
                                                    <span class="fw-bold ms-2">{{ $usedCount }}</span>
                                                </div>
                                                <div class="status-slide">
                                                    <span class="badge bg-danger d-inline-block">Missing</span>
                                                    <span class="fw-bold ms-2">{{ $missingCount }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Evaluation Status -->
                            <div class="col-xl-3 col-lg-4 col-md-6 col-6 mb-4">
                                <div class="card dashboard-card h-100 border-left-info">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-xs fw-bold text-info text-uppercase mb-1">Passed / Needed</div>
                                                <div class="h2 mb-0 fw-bold text-gray-800">
                                                    {{ $evaluationCount }} / {{ $requiredExtinguishers }}
                                                    <span class="text-xs text-muted fw-normal">{{ $evaluationPassed ? 'Passed' : 'Failed' }}</span>
                                                </div>
                                            </div>
                                            <i class="fas fa-clipboard-check fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($labRooms->count() > 0 && $labsCovered < $labRooms->count())
                            <div class="alert alert-warning">
                                <strong>Laboratory coverage:</strong>
                                {{ $labsCovered }}/{{ $labRooms->count() }} Dedicated rooms currently have an assigned extinguisher coverage.
                            </div>
                        @endif

                        <!-- Buildings -->
                        <div class="row">
                            <div class="col-12 mb-4">
                                <div class="card dashboard-card" id="room-ext-card-{{ $school->id }}">
                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 fw-bold text-primary">
                                            <i class="fas fa-chevron-down toggle-icon" onclick="toggleDivision(this, 'room-ext-card-{{ $school->id }}')"></i>
                                            <i class="fas fa-list me-2"></i> Room-Based Extinguishers
                                        </h6>
                                        <div>
                                            @if(auth()->user()->role !== 'viewer')
                                            <button class="btn btn-outline-primary btn-sm me-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addRoomModal"
                                                    data-school-id="{{ $school->id }}">
                                                <i class="fas fa-door-open me-2"></i> Add Room
                                            </button>
                                            <button class="btn btn-primary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addExtModal"
                                                    data-school-id="{{ $school->id }}">
                                                <i class="fas fa-plus me-2"></i> Add Extinguisher
                                            </button>
                                            <button class="btn btn-sm ms-2"
                                                    style="background-color: #e9ecef; color: #495057; border: 1px solid #ced4da;"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#historyOptionsModal">
                                                <i class="fas fa-history me-1"></i> History
                                            </button>
                                            @endif
                                            <a href="{{ route('fire-safety.report.extinguisher-details', $school->id) }}" target="_blank"
                                                    class="btn btn-sm ms-2"
                                                    style="background-color: #e9ecef; color: #495057; border: 1px solid #ced4da;">
                                                <i class="fas fa-print me-1"></i> Print Details
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($school->buildings->isEmpty())
                                            <div class="no-data">
                                                <i class="fas fa-building"></i>
                                                <h5>No Buildings Found</h5>
                                                <p class="text-muted mb-0">Add buildings first in the Buildings page.</p>
                                            </div>
                                        @else
                                            <div class="accordion" id="buildingAccordion-{{ $school->id }}">
                                                @foreach($school->buildings as $building)
                                                    @php
                                                        $coverageMap = [];
                                                        foreach ($building->fireExtinguishers as $ext) {
                                                            foreach ($ext->coveredRooms as $r) {
                                                                $coverageMap[$r->id] = $ext;
                                                            }
                                                        }
                                                    @endphp

                                                    <div class="accordion-item mb-2">
                                                        <h2 class="accordion-header" id="heading-{{ $school->id }}-{{ $building->id }}">
                                                            <button class="accordion-button collapsed" type="button"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse-{{ $school->id }}-{{ $building->id }}"
                                                                    aria-expanded="false"
                                                                    aria-controls="collapse-{{ $school->id }}-{{ $building->id }}">
                                                                <strong class="me-2">{{ $building->building_no }}</strong>
                                                                <span class="text-muted">{{ $building->building_name }}</span>
                                                            </button>
                                                        </h2>
                                                            <div id="collapse-{{ $school->id }}-{{ $building->id }}"
                                                                class="accordion-collapse collapse"
                                                                aria-labelledby="heading-{{ $school->id }}-{{ $building->id }}"
                                                                data-bs-parent="#buildingAccordion-{{ $school->id }}">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-lg-7 col-md-6 mb-4">
                                                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                                                            <h6 class="fw-bold mb-0"><i class="fas fa-door-closed me-2"></i>Rooms</h6>
                                                                            <div class="bg-light border rounded px-3 py-1 shadow-sm d-flex align-items-center">
                                                                                <span class="text-muted small fw-bold me-2 text-uppercase" style="font-size: 0.7rem;">Current / Total Rooms:</span>
                                                                                <span class="fw-bold text-primary">{{ $building->actualRooms->count() }} / {{ $building->rooms }}</span>
                                                                            </div>
                                                                        </div>
                                                                        @if($building->actualRooms->isEmpty())
                                                                            <button class="btn btn-outline-secondary w-100 mb-0 py-3 border-dashed"
                                                                                    onclick="openAddRoomForBuilding({{ $school->id }}, {{ $building->id }}, '{{ $building->building_no }} - {{ $building->building_name }}')">
                                                                                <i class="fas fa-door-open me-2"></i> No rooms defined yet for this building. Add Room?
                                                                            </button>
                                                                        @else
                                                                            <div class="table-responsive">
                                                                                <table class="table table-sm table-hover align-middle compact-mobile-table">
                                                                                    <thead class="table-light">
                                                                                        <tr>
                                                                                            <th>Room</th>
                                                                                            <th>Type</th>
                                                                                            <th>Floor</th>
                                                                                            <th>Covered By</th>
                                                                                            <th>Remarks</th>
                                                                                            <th class="text-end">Action</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        @foreach($building->actualRooms as $room)
                                                                                            @php $ext = $coverageMap[$room->id] ?? null; @endphp
                                                                                            <tr data-room-id="{{ $room->id }}">
                                                                                                <td>
                                                                                                    <div class="fw-semibold">{{ $room->room_code }}</div>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <span class="badge bg-{{ $room->room_type === 'laboratory' ? 'danger' : ($room->room_type === 'clinic' ? 'info' : 'secondary') }}">
                                                                                                        {{ ucfirst($room->room_type) }}
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td>{{ $room->floor_no ?? 'â€”' }}</td>
                                                                                                <td>
                                                                                                    <span class="coverage-badges" data-room-id="{{ $room->id }}">
                                                                                                        @if($ext)
                                                                                                            <span class="badge bg-success">{{ $ext->code }}</span>
                                                                                                            @if($ext->room_id === $room->id)
                                                                                                                <span class="badge bg-primary">Center</span>
                                                                                                            @endif
                                                                                                        @else
                                                                                                            <span class="badge bg-warning text-dark">Uncovered</span>
                                                                                                        @endif
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <span class="text-muted small">{{ $room->remarks ?: '—' }}</span>
                                                                                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                                                                                        <span class="badge {{ $room->has_secondary_exit ? 'bg-success' : 'bg-danger' }}">
                                                                                                            <i class="fas {{ $room->has_secondary_exit ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                                                                                                            Secondary Exit: {{ $room->has_secondary_exit ? 'Yes' : 'No' }}
                                                                                                        </span>
                                                                                                        @if(Str::contains(strtolower($room->room_type), ['administration']))
                                                                                                            <span class="badge {{ $room->has_smoke_detector ? 'bg-success' : 'bg-danger' }}">
                                                                                                                <i class="fas {{ $room->has_smoke_detector ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                                                                                                                Smoke Detector: {{ $room->has_smoke_detector ? 'Yes' : 'No' }}
                                                                                                            </span>
                                                                                                        @endif
                                                                                                    </div>
                                                                                                </td>
                                                                                                <td class="text-end">
                                                                                                    <button class="btn btn-sm btn-outline-primary" onclick="openUpdateRoomModal({{ $room->id }})">
                                                                                                        <i class="fas fa-search-plus me-1"></i> {{ auth()->user()->role === 'viewer' ? 'View Details' : 'Inspect & Update' }}
                                                                                                    </button>
                                                                                                </td>
                                                                                            </tr>
                                                                                        @endforeach
                                                                                    </tbody>
                                                                                    @if($building->actualRooms->count() < $building->rooms && auth()->user()->role !== 'viewer')
                                                                                    <tfoot class="border-top-0">
                                                                                        <tr>
                                                                                            <td colspan="6" class="p-0">
                                                                                                <button class="btn btn-primary w-100 rounded-0 py-2 fw-bold"
                                                                                                        onclick="openAddRoomForBuilding({{ $school->id }}, {{ $building->id }}, '{{ $building->building_no }} - {{ $building->building_name }}')">
                                                                                                    <i class="fas fa-plus-circle me-1"></i> Add Room for {{ $building->building_no }}
                                                                                                </button>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tfoot>
                                                                                    @endif
                                                                                </table>
                                                                            </div>
                                                                        @endif
                                                                    </div>

                                                                    <div class="col-lg-5 col-md-6 mb-4">
                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                            <h6 class="fw-bold mb-0"><i class="fas fa-fire-extinguisher me-2"></i>Extinguishers & Details</h6>
                                                                            @php
                                                                                $compliance = \App\Http\Controllers\FireSafetyController::calculateBuildingCompliance($building);
                                                                                $evalText = $compliance >= 80 ? 'Compliant' : 'Non‑Compliant';
                                                                                $evalColor = $compliance >= 80 ? 'success' : 'danger';
                                                                                $existingExts = $building->fireExtinguishers->count();
                                                                                $neededExts = $building->requiredExtinguishersCount;
                                                                                $hasEnoughExts = $existingExts >= $neededExts;
                                                                                $extStatusText = $hasEnoughExts ? 'Okay' : 'Failed';
                                                                                $extStatusColor = $hasEnoughExts ? 'success' : 'danger';
                                                                            @endphp
                                                                            <span class="badge bg-{{ $extStatusColor }}">
                                                                                Existing / Needed: {{ $existingExts }} / {{ $neededExts }} — {{ $extStatusText }}
                                                                            </span>
                                                                        </div>
                                                                        @if($building->fireExtinguishers->isEmpty())
                                                                            <button class="btn btn-outline-secondary w-100 mb-0 py-4 border-dashed"
                                                                                    onclick="openAddExtinguisherForBuilding({{ $school->id }}, {{ $building->id }}, '{{ $building->building_no }} - {{ $building->building_name }}')">
                                                                                <i class="fas fa-fire-extinguisher me-2"></i> No extinguishers recorded yet for this building. Add Extinguisher?
                                                                            </button>
                                                                        @else
                                            <div class="table-responsive">
                                                @foreach($building->fireExtinguishers as $ext)
                                                    @php
                                                        $pressure = $ext->pressure_level ?? 100;
                                                        $statusLabel = 'OK';
                                                        $healthClass = 'health-good';
                                                        $badgeClass = 'success';

                                                            if ($ext->status === 'maintenance') {
                                                                $statusLabel = 'For Preventive Maintenance';
                                                                $healthClass = 'health-warning';
                                                                $badgeClass = 'warning';
                                                            } elseif ($ext->status === 'expired' || $ext->status === 'missing') {
                                                                $statusLabel = $ext->status === 'expired' ? 'Used' : 'Missing';
                                                                $healthClass = 'health-danger';
                                                                $badgeClass = 'danger';
                                                            } elseif ($ext->status === 'purchase') {
                                                                $statusLabel = 'For Purchase';
                                                                $healthClass = 'health-secondary';
                                                                $badgeClass = 'dark';
                                                            } elseif ($ext->status === 'decommissioned') {
                                                                $statusLabel = 'Decommissioned';
                                                                $healthClass = 'health-danger';
                                                                $badgeClass = 'danger';
                                                            }
                                                    @endphp
                                                    <div class="mb-4 border rounded shadow-sm overflow-hidden bg-white">
                                                        <table class="table table-bordered table-sm mb-0 compact-mobile-table">
                                                            <!-- Row 1 -->
                                                            <tr>
                                                                <td colspan="3" class="align-middle ps-3 py-2">
                                                                    <strong>Status & Pressure:</strong>
                                                                    <span class="badge bg-{{ $badgeClass }} ms-1">{{ $statusLabel }}</span>
                                                                </td>
                                                                <td colspan="2" class="align-middle ps-3 py-2">
                                                                    <strong>Type:</strong>
                                                                    <span class="badge bg-secondary ms-1">{{ $ext->type }}</span>
                                                                </td>
                                                            </tr>
                                                            <!-- Row 2 -->
                                                            <tr>
                                                                <td colspan="5" class="p-3">
                                                                    <div class="health-bar" style="height: 30px; background-color: #e9ecef; border-radius: 4px; position: relative;" title="Pressure: {{ $pressure }}%">
                                                                        <div class="health-bar-fill {{ $healthClass }}" style="width: {{ $pressure }}%; border-radius: 4px;"></div>
                                                                        <div class="health-bar-text" style="line-height: 30px; font-size: 14px; font-weight: bold; color: #333;">{{ $pressure }}%</div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <!-- Row 4 -->
                                                            <tr class="table-light text-center small">
                                                                <th>Code</th>
                                                                <th>As Of</th>
                                                                <th>Location</th>
                                                                <th>Covering</th>
                                                                <th>Action</th>
                                                            </tr>
                                                            <!-- Row 5 -->
                                                            <tr class="text-center align-middle">
                                                                <td class="fw-bold">{{ $ext->code }}</td>
                                                                <td>{{ $ext->date_checked ? \Carbon\Carbon::parse($ext->date_checked)->format('m-d-Y') : 'N/A' }}</td>
                                                                <td class="{{ !$ext->centerRoom ? 'bg-light' : '' }}">{{ $ext->centerRoom->room_code ?? 'Unassigned' }}</td>
                                                                <td>{{ $ext->coveredRooms->count() }} Rooms</td>
                                                                <td class="p-2">
                                                                    <button class="btn btn-sm btn-primary w-100"
                                                                            onclick="openUpdateModal({{ $ext->id }}, '{{ $ext->code }}', '{{ $ext->status }}', {{ $pressure }})">
                                                                        <i class="fas fa-edit me-1"></i> {{ auth()->user()->role === 'viewer' ? 'View Details' : 'Update' }}
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                @endforeach

                                                @if($building->fireExtinguishers->count() < $building->requiredExtinguishersCount && auth()->user()->role !== 'viewer')
                                                <div class="mt-2">
                                                    <button class="btn btn-primary w-100 py-2 fw-bold shadow-sm"
                                                            onclick="openAddExtinguisherForBuilding({{ $school->id }}, {{ $building->id }}, '{{ $building->building_no }} - {{ $building->building_name }}')">
                                                        <i class="fas fa-plus-circle me-1"></i> Add Extinguisher for {{ $building->building_no }}
                                                        <div class="small opacity-75 fw-normal">Current: {{ $building->fireExtinguishers->count() }} / Required: {{ $building->requiredExtinguishersCount }}</div>
                                                    </button>
                                                </div>
                                                @endif
                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Inspections -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card dashboard-card" id="recent-inspections-card-{{ $school->id }}">
                                    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
                                        <h6 class="m-0 fw-bold text-dark">
                                            <i class="fas fa-chevron-down toggle-icon" onclick="toggleDivision(this, 'recent-inspections-card-{{ $school->id }}')"></i>
                                            <i class="fas fa-history me-2"></i> Recent Updates & Inspections - {{ $school->school_name }}
                                        </h6>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshRecentData({{ $school->id }})">
                                            <i class="fas fa-sync-alt"></i> Refresh All
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <!-- FIRE EXTINGUISHER INSPECTIONS -->
                                        <div class="mb-4">
                                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-fire-extinguisher me-2"></i>Recent Extinguisher Inspections</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover border" id="inspectionsTable-{{ $school->id }}">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Extinguisher Code</th>
                                                            <th>Location</th>
                                                            <th>Inspector</th>
                                                            <th>Status</th>
                                                            <th>Pressure</th>
                                                            <th>Notes / Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($school->recent_inspections_data ?? [] as $item)
                                                            @php
                                                                $badgeClass = 'secondary';
                                                                $statusLabel = $item->status;
                                                                if ($item->status === 'active') { $badgeClass = 'success'; $statusLabel = 'OK'; }
                                                                elseif ($item->status === 'maintenance') { $badgeClass = 'warning'; $statusLabel = 'For Preventive Maintenance'; }
                                                                elseif ($item->status === 'expired') { $badgeClass = 'danger'; $statusLabel = 'Used'; }
                                                                elseif ($item->status === 'missing') { $badgeClass = 'danger'; $statusLabel = 'Missing'; }
                                                                elseif ($item->status === 'purchase') { $badgeClass = 'dark'; $statusLabel = 'For Purchase'; }
                                                                elseif ($item->status === 'decommissioned') { $badgeClass = 'danger'; $statusLabel = 'Decommissioned'; }
                                                            @endphp
                                                            <tr>
                                                                <td>{{ \Carbon\Carbon::parse($item->inspection_date)->format('Y-m-d') }}</td>
                                                                <td class="fw-bold">{{ $item->extinguisher->code }}</td>
                                                                <td>{{ ($item->extinguisher->building->building_no ?? '?') }} - {{ ($item->extinguisher->centerRoom->room_name ?? '?') }}</td>
                                                                <td>{{ $item->inspector->name ?? 'Unknown' }}</td>
                                                                <td><span class="badge bg-{{ $badgeClass }}">{{ $statusLabel }}</span></td>
                                                                <td>{{ $item->pressure_level }}%</td>
                                                                <td>{{ $item->notes ?? '-' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="7" class="text-center text-muted">No recent inspections found.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <!-- ROOM UPDATES -->
                                        <div class="mb-2">
                                            <h6 class="fw-bold text-info mb-3"><i class="fas fa-door-open me-2"></i>Recent Room Updates</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover border" id="roomsUpdatesTable-{{ $school->id }}">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Room Code/No</th>
                                                            <th>Room Name</th>
                                                            <th>Floor Level</th>
                                                            <th>Building Code</th>
                                                            <th>Nearest Extinguisher Room</th>
                                                            <th>Inspector</th>
                                                            <th>Remarks</th>
                                                            <th class="text-end">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($school->recent_room_updates_data ?? [] as $item)
                                                            @php
                                                                $nearest = 'None / Uncovered';
                                                                if ($item->hostedExtinguisher) {
                                                                    $nearest = 'HOST ROOM';
                                                                } elseif ($item->nearestExtinguisherRoom) {
                                                                    $nearest = $item->nearestExtinguisherRoom->room_code;
                                                                }

                                                                $remarks = $item->remarks ?? '-';
                                                                if ($item->approval_status === 'pending') {
                                                                    $remarks .= ' <span class="badge bg-warning text-dark">(Pending)</span>';
                                                                } elseif ($item->approval_status === 'approved' && $item->lastInspector && $item->lastInspector->role === 'contributor') {
                                                                    $remarks .= ' <span class="badge bg-success text-white">(Approve)</span>';
                                                                } elseif ($item->approval_status === 'rejected') {
                                                                    $remarks .= ' <span class="badge bg-danger text-white">(Not Approve)</span>';
                                                                }
                                                            @endphp
                                                            <tr>
                                                                <td class="fw-bold">{{ $item->room_code ?? 'N/A' }}</td>
                                                                <td>{{ $item->room_name ?? '-' }}</td>
                                                                <td>{{ $item->floor_label }}</td>
                                                                <td>{{ $item->building->building_no ?? '?' }}</td>
                                                                <td>{{ $nearest }}</td>
                                                                <td>{{ $item->lastInspector->name ?? 'Unknown' }}</td>
                                                                <td>{!! $remarks !!}</td>
                                                                <td class="text-end">
                                                                    <button class="btn btn-sm btn-outline-primary" onclick="openUpdateRoomModal({{ $item->id }})">
                                                                        <i class="fas fa-search"></i> Inspect
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="8" class="text-center text-muted">No recent room updates found.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
        </div>
    @endif

@endsection

@section('modals')
    <!-- Update Extinguisher Status Modal -->
    <div class="modal fade" id="updateExtModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Extinguisher</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateExtForm">
                        @csrf
                        <input type="hidden" id="updateExtId" name="extinguisher_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Extinguisher Code</label>
                            <input type="text" class="form-control bg-light" id="updateExtCode" readonly>
                        </div>

                        <div class="alert alert-light border mb-3">
                            <i class="fas fa-building me-2 text-primary"></i>
                            Located in: <strong id="updateExtBuildingNameDisplay">...</strong> | Floor <strong id="updateExtFloorDisplay">—</strong> | Room <strong id="updateExtRoomDisplay">—</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Center Room</label>
                            <select class="form-control" name="room_id" id="updateCenterRoomSelect" onchange="handleUpdateCenterRoomChange()">
                                <option value="">Select Center Room</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Covered Rooms (Select up to 3)</label>
                            <select class="form-control" id="updateCoveredRoomsSelect" name="covered_room_ids[]" multiple size="5"></select>
                            <div class="form-text small">Use Ctrl/Cmd + Click to select multiple. Laboratory rule applies.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status *</label>
                                <select class="form-control" name="status" id="updateExtStatus" required onchange="handleUpdateStatusChange()">
                                    <option value="active">OK (Active)</option>
                                    <option value="maintenance">For Preventive Maintenance</option>
                                    <option value="expired">Used</option>
                                    <option value="missing">Missing</option>
                                    <option value="purchase">For Purchase</option>
                                    <option value="decommissioned">Decommissioned</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Pressure (0-100%) *</label>
                                <input type="number" class="form-control" name="pressure_level" id="updateExtPressure" min="0" max="100" required>
                            </div>
                        </div>

                        <div class="mb-3" id="updateExtNotesContainer">
                            <label class="form-label fw-bold">Notes / Remarks *</label>
                            <textarea class="form-control" name="notes" id="updateExtNotes" rows="3" placeholder="Reason for update..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="updateExtCloseBtn">Close</button>
                    @if(auth()->user()->role !== 'viewer')
                    <!-- Remove button -->
                    <button type="button" class="btn btn-outline-danger" id="removeExtBtn" style="display: none;" onclick="showExtRemovalReason()">
                        <i class="fas fa-trash-alt me-2"></i>Remove
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveExtinguisherStatus()" id="updateExtSaveBtn">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                    @endif
                </div>
                <!-- Reason for Removal section -->
                <div class="card-footer bg-light border-top-0 d-none" id="extRemovalReasonSection">
                    <div class="p-3">
                        <label class="form-label fw-bold text-danger">Reason for Removal *</label>
                        <textarea class="form-control border-danger" id="extRemovalReason" rows="2" placeholder="State reason for decommissioning and removal..."></textarea>
                        <div class="mt-2 text-end">
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmRemoveExtinguisher()">
                                <i class="fas fa-check me-2"></i>Yes, Remove It!
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inspect & Update Room Modal -->
    <div class="modal fade" id="updateRoomModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header shadow-sm" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title"><i class="fas fa-search-plus me-2"></i>Inspect & Update Room</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="updateRoomForm">
                        @csrf
                        <input type="hidden" id="updateRoomId" name="room_id">

                        <!-- 1st row: Room Code/No., Room Name, & Floor Level -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Room Code/No.</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-tag text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" name="room_code" id="updateRoomCode" placeholder="e.g., Rm-101">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Room Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-door-open text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" name="room_name" id="updateRoomName" placeholder="e.g., Science Lab">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Floor Level</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-layer-group text-muted"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0" id="updateRoomFloor" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- 2nd row: Room Type, Calculated Priority & Nearest Extinguisher Room -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Room Type</label>
                                <select class="form-select" name="room_type_config_id" id="updateRoomTypeSelect" onchange="onUpdateRoomTypeChange()">
                                    <option value="">— Unchanged —</option>
                                    @foreach(($roomTypes ?? collect()) as $rt)
                                        @php $p = ($calculatedPriorities ?? collect())->firstWhere('id', $rt->parent_id); @endphp
                                        <option value="{{ $rt->id }}"
                                                data-priority-label="{{ $p->name ?? '' }}"
                                                data-max-rooms="{{ $p->max_rooms_covered ?? '' }}">
                                            {{ $rt->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Calculated Priority</label>
                                <input type="text" class="form-control bg-light" id="update_room_priority" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Nearest Extinguisher</label>
                                <select class="form-select" name="nearest_extinguisher_room_id" id="updateRoomNearest">
                                    <option value="">None / Self-Covered</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-warning d-none py-2 px-3 mb-4 small shadow-sm" id="roomTypeChangeWarning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Changing the room type will <strong>clear all extinguisher assignments</strong> for this room.
                        </div>

                        <!-- 3rd row: Yes, a smoke detector required in this room & Has Secondary Exit? -->
                        <div class="row mb-4">
                            <div class="col-md-6" id="updateRoomSmokeDetectorRow">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2 px-3">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="smoke_detector_required" id="updateRoomSmokeDetectorRequired" value="1">
                                            <label class="form-check-label fw-bold text-dark mb-0" for="updateRoomSmokeDetectorRequired">
                                                Yes, a smoke detector required in this room
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2 px-3">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="has_secondary_exit" id="updateRoomSecondaryExit" value="1">
                                            <label class="form-check-label fw-bold text-dark mb-0" for="updateRoomSecondaryExit">
                                                Has Secondary Exit?
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conditional 4th row: Does this have a smoke detector installed? -->
                        <div class="row mb-4 d-none" id="smokeDetectorInstalledRow">
                            <div class="col-12">
                                <div class="card border-primary" style="background-color: rgba(13, 110, 253, 0.05);">
                                    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                                        <span class="fw-bold text-primary"><i class="fas fa-info-circle me-2"></i>Does this have a smoke detector installed?</span>
                                        <div class="d-flex gap-4">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input smoke-installed-toggle" type="checkbox" id="updateRoomSmokeDetectorYes" value="1">
                                                <label class="form-check-label fw-bold" for="updateRoomSmokeDetectorYes">Yes</label>
                                            </div>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input smoke-installed-toggle" type="checkbox" id="updateRoomSmokeDetectorNo" value="0">
                                                <label class="form-check-label fw-bold" for="updateRoomSmokeDetectorNo">No</label>
                                            </div>
                                        </div>
                                        <!-- Hidden field for actual submission -->
                                        <input type="hidden" name="has_smoke_detector" id="updateRoomSmokeDetector" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4th row (becomes 5th): Remarks & the interchangeable Secondary Exit Details / Remarks for No Secondary Exit -->
                        <div class="row g-3">
                            <div class="col-md-6 px-1">
                                <label class="form-label fw-bold small text-muted text-uppercase">General Remarks</label>
                                <textarea class="form-control" name="remarks" id="updateRoomRemarks" rows="3" placeholder="Enter general room remarks..." style="resize: none;"></textarea>
                            </div>
                            <div class="col-md-6 px-1">
                                <label class="form-label fw-bold small text-muted text-uppercase" id="updateSecondaryExitRemarksLabel">Remarks for No Secondary Exit</label>
                                <textarea class="form-control" name="secondary_exit_remarks" id="updateSecondaryExitRemarks" rows="3" placeholder="Enter exit-related details..." style="resize: none;"></textarea>
                            </div>
                        </div>

                        <!-- Approval Section (Admin only) -->
                        <div id="roomApprovalSection" class="d-none border-top pt-3 mt-3">
                            <h6 class="fw-bold text-danger mb-2"><i class="fas fa-user-shield me-2"></i>Approval Action</h6>
                            <p class="small text-muted mb-2">This update was submitted by a <strong id="roomInspectorRole">Contributor</strong> and requires your approval.</p>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Rejection Reason (if applicable)</label>
                                <textarea class="form-control form-control-sm" id="roomRejectionReason" rows="2" placeholder="State reason for rejection..."></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="approveRoomAction()">
                                    <i class="fas fa-check-circle me-1"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger btn-sm w-100" onclick="rejectRoomAction()">
                                    <i class="fas fa-times-circle me-1"></i> Not Approve
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-outline-danger" id="removeRoomBtn" style="display: none;" onclick="showRoomRemovalReason()">
                        <i class="fas fa-trash-alt me-2"></i>Remove Room
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveRoomUpdate()" id="saveRoomUpdateBtn">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    @endif
                </div>
                <!-- Reason for Removal section -->
                <div class="card-footer bg-light border-top-0 d-none" id="roomRemovalReasonSection">
                    <div class="p-3">
                        <label class="form-label fw-bold text-danger">Reason for Removal *</label>
                        <textarea class="form-control border-danger" id="roomRemovalReason" rows="2" placeholder="State reason for removing this room..."></textarea>
                        <div class="mt-2 text-end">
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmRemoveRoom()">
                                <i class="fas fa-check me-2"></i>Yes, Remove It!
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header shadow-sm" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title"><i class="fas fa-door-open me-2"></i>Add Room</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addRoomForm">
                        @csrf
                        <input type="hidden" name="school_id" id="roomSchoolId">

                        <!-- 1st row: Building, Floor, Room Code, Room Name -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Building *</label>
                                <select class="form-select" name="building_id" id="roomBuildingSelect" required>
                                    <option value="">Select Building</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Floor No. *</label>
                                <select class="form-select" name="floor_no" id="roomFloorSelect" required disabled>
                                    <option value="">Select Building First</option>
                                </select>
                                <small id="roomFloorGuidance" class="text-warning d-none">Do one room per floor first</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Room Code</label>
                                <input type="text" class="form-control" name="room_code" placeholder="e.g., Rm-101">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Room Name</label>
                                <input type="text" class="form-control" name="room_name" placeholder="e.g., Science Lab">
                            </div>
                        </div>

                        <!-- 2nd row: Room Type, Calculated Priority -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Room Type *</label>
                                <select class="form-select" name="room_type_config_id" id="room_type_select" required onchange="updateRoomPriority()">
                                    <option value="">Select room type</option>
                                    @foreach(($roomTypes ?? collect()) as $rt)
                                        @php $p = ($calculatedPriorities ?? collect())->firstWhere('id', $rt->parent_id); @endphp
                                        <option value="{{ $rt->id }}"
                                                data-priority-label="{{ $p->name ?? '' }}"
                                                data-max-rooms="{{ $p->max_rooms_covered ?? '' }}">
                                            {{ $rt->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Calculated Priority</label>
                                <input type="text" class="form-control bg-light" id="calculated_priority" readonly>
                            </div>
                        </div>

                        <!-- 3rd row: Yes, a smoke detector required in this room & Has Secondary Exit? -->
                        <div class="row mb-4">
                            <div class="col-md-6" id="addRoomSmokeDetectorRow">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2 px-3">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="smoke_detector_required" id="addRoomSmokeDetectorRequired" value="1">
                                            <label class="form-check-label fw-bold text-dark mb-0" for="addRoomSmokeDetectorRequired">
                                                Yes, a smoke detector required in this room
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2 px-3">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="has_secondary_exit" id="addRoomSecondaryExit" value="1">
                                            <label class="form-check-label fw-bold text-dark mb-0" for="addRoomSecondaryExit">
                                                Has Secondary Exit?
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conditional 4th row: Does this have a smoke detector installed? -->
                        <div class="row mb-4 d-none" id="addSmokeDetectorInstalledRow">
                            <div class="col-12">
                                <div class="card border-primary" style="background-color: rgba(13, 110, 253, 0.05);">
                                    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                                        <span class="fw-bold text-primary"><i class="fas fa-info-circle me-2"></i>Does this have a smoke detector installed?</span>
                                        <div class="d-flex gap-4">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input add-smoke-installed-toggle" type="checkbox" id="addRoomSmokeDetectorYes" value="1">
                                                <label class="form-check-label fw-bold" for="addRoomSmokeDetectorYes">Yes</label>
                                            </div>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input add-smoke-installed-toggle" type="checkbox" id="addRoomSmokeDetectorNo" value="0">
                                                <label class="form-check-label fw-bold" for="addRoomSmokeDetectorNo">No</label>
                                            </div>
                                        </div>
                                        <!-- Hidden field for actual submission -->
                                        <input type="hidden" name="has_smoke_detector" id="addRoomSmokeDetector" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 px-1">
                                <label class="form-label fw-bold small text-muted text-uppercase">General Remarks</label>
                                <textarea class="form-control" name="remarks" id="addRoomRemarks" rows="3" placeholder="Enter general room remarks..." style="resize: none;"></textarea>
                            </div>
                            <div class="col-md-6 px-1">
                                <label class="form-label fw-bold small text-muted text-uppercase" id="addSecondaryExitRemarksLabel">Remarks for No Secondary Exit</label>
                                <textarea class="form-control" name="secondary_exit_remarks" id="addSecondaryExitRemarks" rows="3" placeholder="Enter exit-related details..." style="resize: none;"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-primary" onclick="saveRoom()">
                        <i class="fas fa-save me-2"></i>Save Room
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add Extinguisher Modal -->
    <div class="modal fade" id="addExtModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Extinguisher (Room-Based)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addExtForm">
                        @csrf
                        <input type="hidden" name="school_id" id="extSchoolId">

                        <!-- Required Information -->
                        <h6 class="fw-bold">Required Information</h6>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Code *</label>
                                <input type="text" class="form-control" name="code" placeholder="e.g., FRXT-001" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Type *</label>
                                <select class="form-control" name="type" id="ext_type_select" required onchange="handleExtTypeChange()">
                                    <option value="ABC">ABC (Dry Chemical)</option>
                                    <option value="CO2">CO2</option>
                                    <option value="Water">Water</option>
                                    <option value="Foam">Foam</option>
                                    <option value="Other">Other, Please Specify...</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-none" id="otherTypeContainer">
                                <label class="form-label fw-bold text-danger">Specify Type *</label>
                                <input type="text" class="form-control border-danger" id="other_type_input" placeholder="Enter type...">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Status *</label>
                                <select class="form-control" name="status" id="addExtStatus" required onchange="handleAddStatusChange()">
                                    <option value="active">Active</option>
                                    <option value="maintenance">For Preventive Maintenance</option>
                                    <option value="expired">Used</option>
                                    <option value="missing">Missing</option>
                                    <option value="purchase">For Purchase</option>
                                    <option value="decommissioned">Decommissioned</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Pressure (0-100%) *</label>
                                <input type="number" class="form-control" name="pressure_level" id="addExtPressure" min="0" max="100" value="100" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Building *</label>
                                <select class="form-control" name="building_id" id="extBuildingSelect" required onchange="handleExtBuildingChange()">
                                    <option value="">Select Building</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <!-- Optional Information (can be updated later) -->
                        <h6 class="fw-bold">Optional Information (can update later)</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Floor</label>
                                <select class="form-control" id="extFloorSelect" onchange="handleExtFloorChange()" disabled>
                                    <option value="">Select Floor</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Center Room *</label>
                                <select class="form-control" name="room_id" id="centerRoomSelect" onchange="handleCenterRoomChange()">
                                    <option value="">Select Center Room</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">
                                    Covered Rooms <span id="coveredRoomsLimitLabel" class="text-muted small">(Up to 3)</span>
                                </label>
                                <select class="form-control" id="coveredRoomsSelect" name="covered_room_ids[]" multiple size="5">
                                </select>
                                <div class="form-text small">Use Ctrl/Cmd + Click to select multiple. Laboratory rule applies.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Date Checked</label>
                                <input type="date" class="form-control" name="date_checked">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Evaluation Result</label>
                                <select class="form-control" name="evaluation_result">
                                    <option value="Passed">Passed</option>
                                    <option value="Failed">Failed</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Remarks</label>
                                <input type="text" class="form-control" name="remarks" placeholder="Optional remarks...">
                            </div>
                        </div>

                        <div class="alert alert-info mb-0 py-2 small">
                            <i class="fas fa-info-circle me-2"></i><strong>Note:</strong> Coverage limit is determined by the center room's priority set in customization.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-primary" onclick="saveExtinguisher()">
                        <i class="fas fa-save me-2"></i>Save Extinguisher
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Fire Extinguisher's History Modal -->
    <div class="modal fade" id="extHistoryModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #6c757d; color: white;">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i>Fire Extinguisher's History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="extHistoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Removed</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Last Location</th>
                                    <th>Reason to be removed</th>
                                    <th>Last Recorded Data</th>
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

    <!-- History Options Modal -->
    <div class="modal fade" id="historyOptionsModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title mb-0"><i class="fas fa-history me-2"></i> History Options</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <button class="btn btn-outline-secondary w-100 mb-3" onclick="openExtHistoryModal({{ $school->id ?? 'null' }}); bootstrap.Modal.getInstance(document.getElementById('historyOptionsModal')).hide()">
                        <i class="fas fa-fire-extinguisher me-2"></i> Fire Extinguisher History
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="openRoomHistoryModal({{ $school->id ?? 'null' }}); bootstrap.Modal.getInstance(document.getElementById('historyOptionsModal')).hide()">
                        <i class="fas fa-door-open me-2"></i> Removed Room History
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Room's History Modal -->
    <div class="modal fade" id="roomHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #6c757d; color: white;">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i>Room's History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="roomHistoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Removed</th>
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
@endsection

@section('scripts')
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const schools = @json($schools);
        const userRole = "{{ auth()->user()->role }}";
        let currentBuildingRooms = [];

        function checkViewerAccess(formId, buttonsId = null) {
            if (userRole === 'viewer') {
                const form = document.getElementById(formId);
                if (form) {
                    const elements = form.querySelectorAll('input, select, textarea');
                    elements.forEach(el => el.disabled = true);
                }
                if (buttonsId) {
                    const buttons = document.getElementById(buttonsId);
                    if (buttons) buttons.style.display = 'none';
                }
            }
        }

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        // Handle status change in Add Extinguisher modal - enforce pressure ranges
        function handleAddStatusChange() {
            const status = document.getElementById('addExtStatus').value;
            const pressureInput = document.getElementById('addExtPressure');
            updatePressureConstraints(status, pressureInput);
        }

        // Handle status change in Update Extinguisher modal - enforce pressure ranges
        function handleUpdateStatusChange() {
            const status = document.getElementById('updateExtStatus').value;
            const pressureInput = document.getElementById('updateExtPressure');
            updatePressureConstraints(status, pressureInput);
        }

        function updatePressureConstraints(status, pressureInput) {
            switch(status) {
                case 'active':
                    pressureInput.min = 70;
                    pressureInput.max = 100;
                    // Reset if out of range, or clamp? User said "range of percentage are only 70-100%"
                    if (pressureInput.value < 70) pressureInput.value = 70;
                    if (pressureInput.value > 100) pressureInput.value = 100;
                    break;
                case 'maintenance': // For Preventive Maintenance
                    pressureInput.min = 20;
                    pressureInput.max = 69;
                    if (pressureInput.value < 20) pressureInput.value = 20;
                    if (pressureInput.value > 69) pressureInput.value = 69;
                    break;
                case 'expired': // Empty
                    pressureInput.min = 0;
                    pressureInput.max = 19;
                    if (pressureInput.value > 19) pressureInput.value = 19;
                    break;
                case 'missing':
                case 'purchase':
                case 'decommissioned':
                    pressureInput.min = 0;
                    pressureInput.max = 100;
                    break;
            }
        }

        function setTodayIfEmpty(dateInput) {
            if (!dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
        }

        // Populate building selects for a school
        function populateBuildingsForSchool(schoolId, preSelectedId = null, preSelectedText = '') {
            const school = schools.find(s => String(s.id) === String(schoolId));
            const buildings = (school && school.buildings) ? school.buildings : [];

            const roomBuildingSelect = document.getElementById('roomBuildingSelect');
            const extBuildingSelect = document.getElementById('extBuildingSelect');

            const createOption = (b, isLocked = false) => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = isLocked ? `Building: ${preSelectedText} (Already Selected)` : b.building_no + (b.building_name ? ` (${b.building_name})` : '');
                opt.dataset.floors = b.floors || 0;
                opt.dataset.type = b.building_type || '';
                opt.dataset.rooms_limit = b.rooms || 0;
                return opt;
            };

            const resetSelect = (select) => {
                select.innerHTML = '';
                if (preSelectedId) {
                    const building = buildings.find(b => String(b.id) === String(preSelectedId));
                    if (building) {
                        select.appendChild(createOption(building, true));
                    } else {
                        const defaultOpt = document.createElement('option');
                        defaultOpt.value = preSelectedId;
                        defaultOpt.textContent = `Building: ${preSelectedText} (Locked)`;
                        select.appendChild(defaultOpt);
                    }
                } else {
                    const defaultOpt = document.createElement('option');
                    defaultOpt.value = "";
                    defaultOpt.textContent = "Select Building";
                    select.appendChild(defaultOpt);
                }
            };

            resetSelect(roomBuildingSelect);
            resetSelect(extBuildingSelect);

            buildings.forEach(b => {
                if (preSelectedId && String(b.id) === String(preSelectedId)) return;
                // Always allow adding rooms to any building in this school
                roomBuildingSelect.appendChild(createOption(b));

                // For extinguishers, we still allow adding beyond minimum required,
                // so do NOT hide buildings here (the minimum is used as a baseline only).
                extBuildingSelect.appendChild(createOption(b));
            });
        }

        // Named function for room floor population so it can be called/awaited explicitly
        async function updateRoomFloors(buildingId) {
            const floorSelect = document.getElementById('roomFloorSelect');
            const floorGuidance = document.getElementById('roomFloorGuidance');
            floorSelect.innerHTML = '<option value="">Select Floor</option>';
            floorSelect.disabled = true;
            if (floorGuidance) floorGuidance.classList.add('d-none');

            if (!buildingId) return;

            // Find building data from global schools array
            let building = null;
            for (const s of schools) {
                building = s.buildings.find(b => String(b.id) === String(buildingId));
                if (building) break;
            }

            if (!building) return;

            const type = building.building_type || '';
            const totalRequiredRooms = parseInt(building.rooms) || 0;
            const totalFloors = parseInt(building.floors) || 1;

            // Restriction for Gymnasium and Cafeteria
            if (type.toLowerCase() === 'gymnasium' || type.toLowerCase() === 'cafeteria or canteens' || type.toLowerCase().includes('cafeteria')) {
                Swal.fire({
                    title: 'Building Restriction',
                    text: 'Gymnasium & Cafeteria buildings have only 1 room. You cannot add more rooms to them.',
                    icon: 'warning'
                });
                return;
            }

            try {
                // Fetch current rooms to calculate distribution
                const resp = await fetch(`/fire-safety/rooms/${buildingId}`);
                const existingRooms = await resp.json();

                const currentTotalCount = existingRooms.length;
                const roomsByFloor = {};
                const normalizeFloorNo = (value) => {
                    if (value === null || value === undefined) return null;
                    const n = parseInt(String(value).match(/\d+/)?.[0] ?? '', 10);
                    return Number.isFinite(n) ? n : null;
                };

                for (let i = 1; i <= totalFloors; i++) {
                    roomsByFloor[i] = existingRooms.filter(r => normalizeFloorNo(r.floor_no) === i).length;
                }

                const remainingSlots = totalRequiredRooms - currentTotalCount;

                const getOrdinal = (n) => {
                    const s = ["th", "st", "nd", "rd"];
                    const v = n % 100;
                    return n + (s[(v - 20) % 10] || s[v] || s[0]);
                };

                floorSelect.innerHTML = '<option value="">Select Floor</option>';
                // Rule: every floor should have at least 1 room before adding more to the same floor.
                const floorsWithRooms = Object.keys(roomsByFloor).filter(f => roomsByFloor[f] > 0).length;
                const floorsWithoutRooms = totalFloors - floorsWithRooms;

                if (floorGuidance) {
                    if (floorsWithoutRooms > 0) {
                        floorGuidance.classList.remove('d-none');
                    } else {
                        floorGuidance.classList.add('d-none');
                    }
                }

                if (totalRequiredRooms > 0 && remainingSlots <= 0) {
                    Swal.fire('Limit Reached', 'No more rooms can be added without violating building room limit.', 'warning');
                    floorSelect.disabled = true;
                    return;
                }

                let candidateFloors = [];
                if (floorsWithoutRooms > 0) {
                    candidateFloors = Array.from({ length: totalFloors }, (_, idx) => idx + 1)
                        .filter(floorNo => (roomsByFloor[floorNo] || 0) === 0);
                } else {
                    // Once every floor has at least one room, allow manual floor choice
                    // so users can continue encoding in chronological order.
                    candidateFloors = Array.from({ length: totalFloors }, (_, idx) => idx + 1);
                }

                for (const i of candidateFloors) {
                    const floorIsEmpty = (roomsByFloor[i] === 0 || !roomsByFloor[i]);

                    const opt = document.createElement('option');
                    opt.value = i;
                    opt.textContent = getOrdinal(i) + " Floor" + (floorIsEmpty ? " (No Rooms yet)" : "");
                    floorSelect.appendChild(opt);
                }

                if (floorSelect.options.length <= 1) {
                    floorSelect.disabled = true;
                } else {
                    floorSelect.disabled = false;
                }

            } catch (e) {
                console.error('Error in updateRoomFloors:', e);
                floorSelect.disabled = false; // Fallback
            }
        }

        // Handle Building Selection in Add Room
        document.getElementById('roomBuildingSelect').addEventListener('change', function() {
            updateRoomFloors(this.value);
        });

        // Hook modal open events to set school_id and populate buildings
        document.getElementById('addRoomModal').addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return; // Ignore if opened manually via JS (which handles its own population)

            const schoolId = btn.getAttribute('data-school-id');
            document.getElementById('roomSchoolId').value = schoolId || '';
            populateBuildingsForSchool(schoolId);

            // Enforce viewer role restrictions
            checkViewerAccess('addRoomForm');
        });

        document.getElementById('addExtModal').addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return; // Ignore if opened manually

            const schoolId = btn.getAttribute('data-school-id');
            document.getElementById('extSchoolId').value = schoolId || '';
            populateBuildingsForSchool(schoolId);

            // reset room selects
            document.getElementById('centerRoomSelect').innerHTML = '<option value="">Select Center Room</option>';
            document.getElementById('coveredRoomsSelect').innerHTML = '';

            setTodayIfEmpty(document.querySelector('#addExtForm input[name="date_checked"]'));

            // Enforce viewer role restrictions
            checkViewerAccess('addExtForm');
        });

        async function saveRoom() {
            const form = document.getElementById('addRoomForm');
            if (!form) return;

            // Temporarily enable disabled fields so they are included in FormData
            const disabledFields = Array.from(form.querySelectorAll(':disabled'));
            disabledFields.forEach(el => el.disabled = false);

            if (!form.checkValidity()) {
                form.reportValidity();
                Swal.fire({
                    title: 'Missing Information',
                    text: 'Please fill in all required fields marked with *',
                    icon: 'warning',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
                disabledFields.forEach(el => el.disabled = true);
                return;
            }

            const formData = new FormData(form);
            // Restore disabled state
            disabledFields.forEach(el => el.disabled = true);

            try {
                // Show loading state
                const saveBtn = document.querySelector('button[onclick="saveRoom()"]');
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                }

                const resp = await fetch(`{{ route('fire-safety.room.store') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await resp.json();

                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Room';
                }

                if (!resp.ok || !data.success) {
                    Swal.fire('Error', data.message || 'Failed to add room', 'error');
                    return;
                }

                Swal.fire('Success', 'Room added successfully!', 'success').then(() => {
                    location.reload();
                });
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Failed to add room. Please try again.', 'error');
                const saveBtn = document.querySelector('button[onclick="saveRoom()"]');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Room';
                }
            }
        }

        // inspectRoom function replaced by openUpdateRoomModal below

        // Room Inspection logic moved to the end of script block for cleaner organization


        function updateRoomPriority() {
            const typeSelect = document.getElementById('room_type_select');
            const priorityInput = document.getElementById('calculated_priority');
            const opt = typeSelect?.selectedOptions?.[0];
            const label = opt?.dataset?.priorityLabel || '';
            const maxRooms = opt?.dataset?.maxRooms || '';

            if (opt && opt.value !== '') {
                priorityInput.value = maxRooms ? `${label} (Up to ${maxRooms} rooms)` : label;

                const roomTypeName = opt.textContent.trim().toLowerCase();
                const smokeRow = document.getElementById('addRoomSmokeDetectorRow');
                const smokeReqCb = document.getElementById('addRoomSmokeDetectorRequired');
                const smokeInstalledRow = document.getElementById('addSmokeDetectorInstalledRow');

                if (smokeRow && smokeReqCb) {
                    if (roomTypeName.includes('administration')) {
                        smokeRow.classList.remove('d-none');
                    } else {
                        smokeRow.classList.add('d-none');
                        smokeReqCb.checked = false;
                        if (smokeInstalledRow) smokeInstalledRow.classList.add('d-none');
                    }
                }
            } else {
                priorityInput.value = '';
            }
        }

        async function handleExtBuildingChange() {
            const buildingSelect = document.getElementById('extBuildingSelect');
            const floorSelect = document.getElementById('extFloorSelect');
            const centerSelect = document.getElementById('centerRoomSelect');
            const coveredSelect = document.getElementById('coveredRoomsSelect');

            const buildingId = buildingSelect.value;

            // Reset selects
            floorSelect.innerHTML = '<option value="">Select Floor</option>';
            centerSelect.innerHTML = '<option value="">Select Center Room</option>';
            coveredSelect.innerHTML = '';

            if (!buildingId) {
                floorSelect.disabled = true;
                return;
            }

            // Populate Floors
            const selectedOption = buildingSelect.options[buildingSelect.selectedIndex];
            const floors = parseInt(selectedOption.dataset.floors) || 1;

            for (let i = 1; i <= floors; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `Floor ${i}`;
                floorSelect.appendChild(opt);
            }
            floorSelect.disabled = false;

            // Fetch and store rooms
            try {
                const resp = await fetch(`/fire-safety/building/${buildingId}/rooms-with-coverage`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                currentBuildingRooms = data.rooms || [];
                window.currentBuildingCoveredRoomIds = data.covered_room_ids || [];
                window.currentBuildingHostRoomIds = data.host_room_ids || [];

                // If user hasn't selected a floor yet, we don't show rooms.
                // Or should we? User request said "depending on what floor was chosen... show the rooms".
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Failed to load rooms.', 'error');
            }
        }

        function isDedicatedOrLimitedPriority(priorityLabel) {
            const raw = (priorityLabel || '').toString().trim().toLowerCase();
            const norm = raw.replace(/[_-]/g, ' ').replace(/\s+/g, ' ').trim();
            return norm === 'dedicated' || norm === 'limited shared';
        }

        function handleExtFloorChange() {
            const floorSelect = document.getElementById('extFloorSelect');
            const centerSelect = document.getElementById('centerRoomSelect');
            const coveredSelect = document.getElementById('coveredRoomsSelect');

            const selectedFloor = floorSelect.value;

            centerSelect.innerHTML = '<option value="">Select Center Room</option>';
            coveredSelect.innerHTML = '';

            if (!selectedFloor) return;

            // Filter rooms by floor
            const filteredRooms = currentBuildingRooms.filter(r => String(r.floor_no) === String(selectedFloor));

            filteredRooms.forEach(r => {
                const label = `${r.room_code || ('Room ' + r.id)} - ${r.room_type}`;

                const hostIds = (window.currentBuildingHostRoomIds || []);
                const isHostRoom = hostIds.includes(Number(r.id)) || r.is_host_room === true;
                const isCoveredByAny = (r.is_covered === true) || (window.currentBuildingCoveredRoomIds || []).includes(Number(r.id));

                // Center room: allow rooms even if currently covered, but do NOT allow host rooms
                if (!isHostRoom) {
                    const optCenter = document.createElement('option');
                    optCenter.value = r.id;
                    optCenter.textContent = label;
                    optCenter.dataset.roomType = r.room_type;
                    // Laboratory / clinic (Dedicated / Limited) can only cover up to 2 rooms
                    const baseMax = r.max_rooms || 3;
                    const hardMax = isDedicatedOrLimitedPriority(r.priority_label) ? 2 : baseMax;
                    optCenter.dataset.maxRooms = hardMax;
                    optCenter.dataset.priorityLabel = r.priority_label || '';
                    centerSelect.appendChild(optCenter);
                }

                // Covered rooms:
                // - exclude host rooms
                // - exclude Dedicated / Limited Shared rooms
                // - exclude rooms already covered by ANY extinguisher
                if (!isHostRoom && !isDedicatedOrLimitedPriority(r.priority_label) && !isCoveredByAny) {
                    const optCovered = document.createElement('option');
                    optCovered.value = r.id;
                    optCovered.textContent = label;
                    optCovered.dataset.roomType = r.room_type;
                    optCovered.dataset.maxRooms = r.max_rooms || 2;
                    optCovered.dataset.priorityLabel = r.priority_label || '';
                    coveredSelect.appendChild(optCovered);
                }
            });
        }

        async function handleUpdateExtTypeChange() {
            // reuse same logic as add function if ever needed
            handleExtTypeChange();
        }

        // ---------- update modal helpers ----------
        async function handleUpdateExtBuildingChange() {
            const buildingSelect = document.getElementById('updateExtBuildingSelect');
            const floorSelect = document.getElementById('updateExtFloorSelect');
            const centerSelect = document.getElementById('updateCenterRoomSelect');
            const coveredSelect = document.getElementById('updateCoveredRoomsSelect');

            const buildingId = buildingSelect.value;

            floorSelect.innerHTML = '<option value="">Select Floor</option>';
            centerSelect.innerHTML = '<option value="">Select Center Room</option>';
            // Always include Remove Assignment option
            const removeOpt = document.createElement('option');
            removeOpt.value = '__remove__';
            removeOpt.textContent = 'Remove Assignment (Unlink all rooms)';
            removeOpt.style.color = '#dc3545';
            removeOpt.style.fontWeight = 'bold';
            centerSelect.appendChild(removeOpt);
            coveredSelect.innerHTML = '';
            if (!buildingId) {
                floorSelect.disabled = true;
                return;
            }

            // Populate floors
            const selectedOption = buildingSelect.options[buildingSelect.selectedIndex];
            const floors = parseInt(selectedOption.dataset.floors) || 1;
            for (let i = 1; i <= floors; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `Floor ${i}`;
                floorSelect.appendChild(opt);
            }
            floorSelect.disabled = false;

            // fetch rooms
            try {
                const resp = await fetch(`/fire-safety/building/${buildingId}/rooms-with-coverage`, { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();
                currentBuildingRooms = data.rooms || [];
                window.currentBuildingCoveredRoomIds = data.covered_room_ids || [];
                window.currentBuildingHostRoomIds = data.host_room_ids || [];
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Failed to load rooms.', 'error');
            }
        }

        function handleUpdateExtFloorChange() {
            const floorSelect = document.getElementById('updateExtFloorSelect');
            const centerSelect = document.getElementById('updateCenterRoomSelect');
            const coveredSelect = document.getElementById('updateCoveredRoomsSelect');

            const selectedFloor = floorSelect.value;
            centerSelect.innerHTML = '<option value="">Select Center Room</option>';
            // Always include Remove Assignment option (floor changes previously removed it)
            const removeOpt = document.createElement('option');
            removeOpt.value = '__remove__';
            removeOpt.textContent = 'Remove Assignment (Unlink all rooms)';
            removeOpt.style.color = '#dc3545';
            removeOpt.style.fontWeight = 'bold';
            centerSelect.appendChild(removeOpt);
            coveredSelect.innerHTML = '';
            if (!selectedFloor) return;

            const filteredRooms = currentBuildingRooms.filter(r => String(r.floor_no) === String(selectedFloor));
            filteredRooms.forEach(r => {
                const label = `${r.room_code || ('Room ' + r.id)} - ${r.room_type}`;

                const hostIds = (window.currentBuildingHostRoomIds || []);
                const isHostRoom = hostIds.includes(Number(r.id)) || r.is_host_room === true;
                const isCoveredByAny = (r.is_covered === true) || (window.currentBuildingCoveredRoomIds || []).includes(Number(r.id));

                // Center room: allow rooms even if currently covered, but do NOT allow host rooms
                if (!isHostRoom) {
                    const optCenter = document.createElement('option');
                    optCenter.value = r.id;
                    optCenter.textContent = label;
                    optCenter.dataset.roomType = r.room_type;
                    optCenter.dataset.maxRooms = r.max_rooms || 2;
                    optCenter.dataset.priorityLabel = r.priority_label || '';
                    centerSelect.appendChild(optCenter);
                }

                // Covered rooms: exclude host rooms, Dedicated/Limited Shared priority, and already-covered rooms
                if (!isHostRoom && !isDedicatedOrLimitedPriority(r.priority_label) && !isCoveredByAny) {
                    const optCovered = document.createElement('option');
                    optCovered.value = r.id;
                    optCovered.textContent = label;
                    optCovered.dataset.roomType = r.room_type;
                    optCovered.dataset.maxRooms = r.max_rooms || 2;
                    optCovered.dataset.priorityLabel = r.priority_label || '';
                    coveredSelect.appendChild(optCovered);
                }
            });
        }



        function handleCenterRoomChange() {
            const centerSelect = document.getElementById('centerRoomSelect');
            const coveredSelect = document.getElementById('coveredRoomsSelect');
            const centerId = centerSelect.value;
            if (!centerId) return;

            let found = false;
            Array.from(coveredSelect.options).forEach(o => {
                if (String(o.value) === String(centerId)) {
                    o.selected = true;
                    found = true;
                }
            });
            if (!found) {
                const centerOpt = centerSelect.selectedOptions[0];
                const injected = document.createElement('option');
                injected.value = centerId;
                injected.textContent = (centerOpt?.textContent || `Room ${centerId}`) + ' (Center)';
                injected.selected = true;
                coveredSelect.appendChild(injected);
            }

            const selected = centerSelect.selectedOptions[0];
            const baseMax = parseInt(selected?.dataset?.maxRooms) || 3;
            const priorityLabel = selected?.dataset?.priorityLabel || '';
            const effectiveMax = isDedicatedOrLimitedPriority(priorityLabel) ? 2 : baseMax;

            const helpText = document.querySelector('#addExtModal .form-text');
            if (helpText) helpText.innerHTML = `Use Ctrl/Cmd + Click to select multiple. Current limit: <b>${effectiveMax}</b> rooms for its priority.`;

            const limitLabel = document.getElementById('coveredRoomsLimitLabel');
            if (limitLabel) {
                limitLabel.textContent = `(Up to ${effectiveMax})`;
            }
        }

        function handleUpdateCenterRoomChange() {
            const centerSelect = document.getElementById('updateCenterRoomSelect');
            const coveredSelect = document.getElementById('updateCoveredRoomsSelect');
            const centerId = centerSelect.value;
            if (!centerId) return;

            let found = false;
            Array.from(coveredSelect.options).forEach(o => {
                if (String(o.value) === String(centerId)) {
                    o.selected = true;
                    found = true;
                }
            });
            if (!found) {
                const centerOpt = centerSelect.selectedOptions[0];
                const injected = document.createElement('option');
                injected.value = centerId;
                injected.textContent = (centerOpt?.textContent || `Room ${centerId}`) + ' (Center)';
                injected.selected = true;
                coveredSelect.appendChild(injected);
            }

            const max = parseInt(centerSelect.selectedOptions[0]?.dataset?.maxRooms) || 3;
            const helpText = document.querySelector('#updateExtModal .form-text');
            if (helpText) helpText.innerHTML = `Use Ctrl/Cmd + Click to select multiple. Current limit: <b>${max}</b> rooms for its priority.`;
        }

        async function saveExtinguisher() {
            const form = document.getElementById('addExtForm');
            if (!form) return;

            // Temporarily enable disabled fields so they are included in FormData
            const disabledFields = Array.from(form.querySelectorAll(':disabled'));
            disabledFields.forEach(el => el.disabled = false);

            // Ensure a valid date is sent (older DB schema requires non-null date_checked)
            setTodayIfEmpty(document.querySelector('#addExtForm input[name="date_checked"]'));

            if (!form.checkValidity()) {
                form.reportValidity();
                Swal.fire({
                    title: 'Missing Information',
                    text: 'Please fill in all required fields marked with *',
                    icon: 'warning',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
                disabledFields.forEach(el => el.disabled = true);
                return;
            }

            const formData = new FormData(form);
            // Restore disabled state
            disabledFields.forEach(el => el.disabled = true);

            const typeSelect = document.getElementById('ext_type_select');
            const otherInput = document.getElementById('other_type_input');

            if (typeSelect && typeSelect.value === 'Other') {
                if (!otherInput.value.trim()) {
                    Swal.fire('Required', 'Please specify the extinguisher type.', 'warning');
                    otherInput.focus();
                    return;
                }
                formData.set('type', otherInput.value.trim());
            }

            const centerId = document.getElementById('centerRoomSelect').value;
            const coveredSelect = document.getElementById('coveredRoomsSelect');
            const covered = Array.from(coveredSelect.selectedOptions).map(o => o.value);

            if (centerId && covered.length > 0 && !covered.includes(centerId)) {
                Swal.fire('Inconsistent Selection', 'Covered rooms must include the center room.', 'warning');
                return;
            }

            const selectedCenter = document.getElementById('centerRoomSelect').selectedOptions[0];
            const maxLimit = selectedCenter ? (parseInt(selectedCenter.dataset.maxRooms) || 3) : 3;

            if (centerId && covered.length > maxLimit) {
                Swal.fire('Constraint Error', `This center room type can only cover up to ${maxLimit} rooms.`, 'warning');
                return;
            }

            try {
                // Show loading state
                const saveBtn = document.querySelector('button[onclick="saveExtinguisher()"]');
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                }

                const resp = await fetch(`{{ route('fire-safety.extinguisher.store') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await resp.json();

                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Extinguisher';
                }

                if (!resp.ok || !data.success) {
                    Swal.fire('Error', data.message || 'Failed to add extinguisher', 'error');
                    return;
                }

                Swal.fire('Success', 'Extinguisher added successfully!', 'success').then(() => {
                    location.reload();
                });
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Failed to add extinguisher. Please try again.', 'error');
                const saveBtn = document.querySelector('button[onclick="saveExtinguisher()"]');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Extinguisher';
                }
            }
        }

        document.addEventListener('change', function (e) {
            const t = e.target;
            if (t && (t.id === 'coveredRoomsSelect' || t.id === 'updateCoveredRoomsSelect')) {
                const centerId = t.id === 'coveredRoomsSelect' ? 'centerRoomSelect' : 'updateCenterRoomSelect';
                const centerSelect = document.getElementById(centerId);
                const max = parseInt(centerSelect.selectedOptions[0]?.dataset?.maxRooms) || 3;

                const selected = Array.from(t.selectedOptions);
                if (selected.length > max) {
                    selected.slice(max).forEach(o => o.selected = false);
                    Swal.fire('Limit Reached', `This host room's type can only cover up to ${max} rooms based on its priority.`, 'info');
                }
            }
        });

    async function openUpdateModal(id, code, status, pressure) {
        document.getElementById('updateExtId').value = id;
        document.getElementById('updateExtCode').value = code;
        document.getElementById('updateExtStatus').value = status;
        document.getElementById('updateExtPressure').value = pressure;
        document.getElementById('updateExtNotes').value = '';

        // Reset removal logic
        if (document.getElementById('removeExtBtn')) document.getElementById('removeExtBtn').style.display = 'none';
        if (document.getElementById('extRemovalReasonSection')) document.getElementById('extRemovalReasonSection').classList.add('d-none');
        if (document.getElementById('extRemovalReason')) document.getElementById('extRemovalReason').value = '';

        // Clear displays
        document.getElementById('updateExtBuildingNameDisplay').textContent = 'Loading...';
        document.getElementById('updateExtFloorDisplay').textContent = '—';
        const roomSpan = document.getElementById('updateExtRoomDisplay');
        if (roomSpan) roomSpan.textContent = '—';

        // Clear room selects
        const centerSelect = document.getElementById('updateCenterRoomSelect');
        const coveredSelect = document.getElementById('updateCoveredRoomsSelect');
        centerSelect.innerHTML = '<option value="">Loading rooms...</option>';
        coveredSelect.innerHTML = '';
        centerSelect.disabled = true;
        coveredSelect.disabled = true;

        try {
            // Fetch extinguisher details
            const resp = await fetch(`/fire-safety/extinguisher/${id}`);
            if (!resp.ok) throw new Error('Failed to fetch extinguisher');
            const ext = await resp.json();

            console.log('Extinguisher data:', ext); // Debug log

            // Populate floor and room display only (remove building info)
            if (ext.floor_no) {
                document.getElementById('updateExtFloorDisplay').textContent = ext.floor_no;
            } else {
                document.getElementById('updateExtFloorDisplay').textContent = '—';
            }

            if (roomSpan && ext.room) {
                roomSpan.textContent = ext.room.room_code || `#${ext.room.id}`;
            } else {
                roomSpan.textContent = '—';
            }

            // Optionally, you can also hide or set a static message for the building label
            // The building name display element can be set to empty or hidden
            document.getElementById('updateExtBuildingNameDisplay').textContent = '';

            // Fetch building rooms with coverage status
            if (ext.building_id) {
                const roomsResp = await fetch(`/fire-safety/building/${ext.building_id}/rooms-with-coverage`);
                if (!roomsResp.ok) throw new Error('Failed to fetch rooms');
                const roomsData = await roomsResp.json();

                console.log('Rooms data:', roomsData); // Debug log

                // Get current extinguisher's covered rooms
                const currentCoveredIds = ext.covered_rooms ? ext.covered_rooms.map(cr => Number(cr.id)) : [];
                const currentCenterId = ext.room_id ? Number(ext.room_id) : null;

                const hostRoomIds = roomsData.host_room_ids || [];

                // For CENTER ROOM choices:
                // - allow rooms even if currently covered by another extinguisher (they can host their own extinguisher)
                // - do NOT allow rooms that already host another extinguisher
                const roomsForCenter = roomsData.rooms.filter(r => {
                    const roomId = Number(r.id);
                    const isCurrentCenter = currentCenterId === roomId;
                    const isCurrentCovered = currentCoveredIds.includes(roomId);
                    const sameFloor = ext.floor_no ? Number(r.floor_no) === Number(ext.floor_no) : true;
                    const isHostRoom = !!r.is_host_room || hostRoomIds.includes(roomId);
                    return (sameFloor || isCurrentCenter || isCurrentCovered) && (!isHostRoom || isCurrentCenter);
                });

                // For COVERED ROOMS choices:
                // - exclude host rooms (they have their own extinguisher) except this extinguisher's own center room
                // - exclude Dedicated / Limited Shared rooms
                // - exclude rooms covered by other extinguishers (no overlapping coverage)
                const roomsForCovered = roomsData.rooms.filter(r => {
                    const roomId = Number(r.id);
                    const isCurrentCenter = currentCenterId === roomId;
                    const isCurrentCovered = currentCoveredIds.includes(roomId);
                    const sameFloor = ext.floor_no ? Number(r.floor_no) === Number(ext.floor_no) : true;
                    const coveredByOthers = roomsData.covered_room_ids.includes(roomId) && !isCurrentCenter && !isCurrentCovered;
                    const isHostRoom = (!!r.is_host_room || hostRoomIds.includes(roomId)) && !isCurrentCenter;
                    const isDedicatedOrLimited = isDedicatedOrLimitedPriority(r.priority_label);
                    return (sameFloor || isCurrentCenter || isCurrentCovered) && !coveredByOthers && !isHostRoom && (!isDedicatedOrLimited || isCurrentCenter);
                });

                // Populate selects
                centerSelect.innerHTML = '<option value="">Select Center Room</option>';
                // Always include Remove Assignment at the top
                const removeOpt = document.createElement('option');
                removeOpt.value = '__remove__';
                removeOpt.textContent = 'Remove Assignment (Unlink all rooms)';
                removeOpt.style.color = '#dc3545';
                removeOpt.style.fontWeight = 'bold';
                centerSelect.appendChild(removeOpt);
                coveredSelect.innerHTML = '';

                roomsForCenter.forEach(r => {
                    const displayName = r.room_code || `Room #${r.id}`;

                    // Add to center select
                    const optCenter = document.createElement('option');
                    optCenter.value = r.id;
                    optCenter.textContent = `${displayName} - Floor ${r.floor_no} (${r.room_type || 'Unknown'})`;
                    optCenter.dataset.roomType = r.room_type || '';
                    optCenter.dataset.maxRooms = r.max_rooms || 2;
                    optCenter.dataset.priorityLabel = r.priority_label || '';
                    if (currentCenterId === Number(r.id)) {
                        optCenter.selected = true;
                    }
                    centerSelect.appendChild(optCenter);
                });

                roomsForCovered.forEach(r => {
                    const displayName = r.room_code || `Room #${r.id}`;
                    const optCovered = document.createElement('option');
                    optCovered.value = r.id;
                    optCovered.textContent = `${displayName} - Floor ${r.floor_no} (${r.room_type || 'Unknown'})`;
                    optCovered.dataset.roomType = r.room_type || '';
                    optCovered.dataset.maxRooms = r.max_rooms || 2;
                    optCovered.dataset.priorityLabel = r.priority_label || '';
                    if (currentCoveredIds.includes(Number(r.id))) {
                        optCovered.selected = true;
                    }
                    coveredSelect.appendChild(optCovered);
                });

                // Ensure current center room appears in Covered Rooms (even if filtered out),
                // because backend requires the center room to be part of coverage.
                if (currentCenterId) {
                    const exists = Array.from(coveredSelect.options).some(o => Number(o.value) === Number(currentCenterId));
                    if (!exists) {
                        const centerRoom = roomsData.rooms.find(r => Number(r.id) === Number(currentCenterId));
                        const displayName = centerRoom?.room_code || `Room #${currentCenterId}`;
                        const injected = document.createElement('option');
                        injected.value = currentCenterId;
                        injected.textContent = `${displayName} - Floor ${centerRoom?.floor_no || ext.floor_no || '?'} (${centerRoom?.room_type || 'Unknown'}) (Center)`;
                        injected.selected = true;
                        coveredSelect.appendChild(injected);
                    }
                }

                // If no rooms available on the same floor, show message
                if (roomsForCenter.length === 0) {
                    // Keep the Remove Assignment option, just add a disabled info option
                    const noRoomsOpt = document.createElement('option');
                    noRoomsOpt.value = '';
                    noRoomsOpt.textContent = 'No available rooms on this floor';
                    noRoomsOpt.disabled = true;
                    centerSelect.insertBefore(noRoomsOpt, centerSelect.querySelector('option[value="__remove__"]'));
                    coveredSelect.innerHTML = '<option value="">No available rooms on this floor</option>';
                }

                // Enable selects for editing
                centerSelect.disabled = false;
                coveredSelect.disabled = false;
                centerSelect.required = true;

                // Remove any existing generated hidden inputs
                document.querySelectorAll('#updateExtForm input[data-generated]').forEach(i => i.remove());

                // Trigger center room change to apply laboratory rules if needed
                if (centerSelect.value) {
                    setTimeout(() => handleUpdateCenterRoomChange(), 100);
                }
            }

            const modalEl = document.getElementById('updateExtModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            handleUpdateStatusChange();
            checkViewerAccess('updateExtForm');

        } catch (e) {
            console.error('Failed to fetch extinguisher details:', e);
            Swal.fire('Error', 'Failed to load extinguisher details. Please try again.', 'error');

            // Show empty selects on error
            document.getElementById('updateCenterRoomSelect').innerHTML = '<option value="">Error loading rooms</option>';
            document.getElementById('updateCoveredRoomsSelect').innerHTML = '';
        }
    }

        function handleUpdateStatusChange() {
            const statusSelect = document.getElementById('updateExtStatus');
            if(!statusSelect) return;
            const status = statusSelect.value;
            const pressureInput = document.getElementById('updateExtPressure');

            // Show/Hide Remove button if status is Decommissioned
            const removeBtn = document.getElementById('removeExtBtn');
            const removalSection = document.getElementById('extRemovalReasonSection');
            if (removeBtn) {
                if (status === 'decommissioned') {
                    removeBtn.style.display = 'inline-block';
                } else {
                    removeBtn.style.display = 'none';
                    if (removalSection) {
                        removalSection.classList.add('d-none');
                        // Ensure other fields are shown if we switch away from decommissioned
                        document.getElementById('updateExtNotesContainer').style.display = 'block';
                        document.getElementById('updateExtCloseBtn').style.display = 'inline-block';
                        document.getElementById('updateExtSaveBtn').style.display = 'inline-block';
                    }
                }
            }

            if (status === 'active') {
                pressureInput.min = 70;
                pressureInput.max = 100;
                if (pressureInput.value < 70) pressureInput.value = 70;
            } else if (status === 'maintenance') {
                pressureInput.min = 0;
                pressureInput.max = 69;
                if (pressureInput.value >= 70) pressureInput.value = 69;
            } else if (status === 'expired') {
                pressureInput.min = 0;
                pressureInput.max = 19;
                if (pressureInput.value > 19) pressureInput.value = 19;
            } else {
                pressureInput.min = 0;
                pressureInput.max = 100;
            }
        }

        function showExtRemovalReason() {
            const section = document.getElementById('extRemovalReasonSection');
            if(!section) return;

            section.classList.toggle('d-none');
            const isVisible = !section.classList.contains('d-none');

            // Toggle visibility of other elements per request
            const notesContainer = document.getElementById('updateExtNotesContainer');
            const closeBtn = document.getElementById('updateExtCloseBtn');
            const saveBtn = document.getElementById('updateExtSaveBtn');
            const removeBtn = document.getElementById('removeExtBtn');

            if (isVisible) {
                if(notesContainer) notesContainer.style.display = 'none';
                if(closeBtn) closeBtn.style.display = 'none';
                if(saveBtn) saveBtn.style.display = 'none';
                if(removeBtn) {
                    removeBtn.innerHTML = '<i class="fas fa-arrow-left me-2"></i>Cancel Removal';
                    removeBtn.className = 'btn btn-outline-secondary';
                }
                const input = document.getElementById('extRemovalReason');
                if(input) input.focus();
            } else {
                if(notesContainer) notesContainer.style.display = 'block';
                if(closeBtn) closeBtn.style.display = 'inline-block';
                if(saveBtn) saveBtn.style.display = 'inline-block';
                if(removeBtn) {
                    removeBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Remove';
                    removeBtn.className = 'btn btn-outline-danger';
                }
            }
        }

        async function confirmRemoveExtinguisher() {
            const id = document.getElementById('updateExtId').value;
            const code = document.getElementById('updateExtCode').value;
            const reasonInput = document.getElementById('extRemovalReason');
            const reason = reasonInput ? reasonInput.value : '';

            if (!reason.trim()) {
                Swal.fire('Reason Required', 'Please provide a reason for removal.', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: 'Are you sure?',
                text: `You are about to remove Fire Extinguisher ${code}. This will move it to archive.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Remove It!'
            });

            if (result.isConfirmed) {
                try {
                    const resp = await fetch(`/fire-safety/extinguisher/${id}/remove`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ reason: reason })
                    });

                    const data = await resp.json();
                    if (data.success) {
                        Swal.fire('Removed!', 'Fire extinguisher has been archived.', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to remove extinguisher', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire('Error', 'Network error during removal.', 'error');
                }
            }
        }

        async function openExtHistoryModal(schoolId) {
            const modalEl = document.getElementById('extHistoryModal');
            if(!modalEl) return;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const tableBody = document.querySelector('#extHistoryTable tbody');
            if(!tableBody) return;
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
            modal.show();

            try {
                const resp = await fetch(`/fire-safety/extinguisher/history/${schoolId}`);
                const data = await resp.json();

                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No removed extinguishers found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = '';
                data.forEach(item => {
                    const removedAt = new Date(item.removed_at).toLocaleString();
                    const row = `
                        <tr>
                            <td>${removedAt}</td>
                            <td class="fw-bold text-danger">${item.item_code || 'N/A'}</td>
                            <td>${item.item_data.type || 'N/A'}</td>
                            <td>${item.item_data.building_name || 'N/A'}, Floor ${item.item_data.floor_no || '?'}</td>
                            <td>${item.reason || 'No reason provided'}</td>
                            <td>
                                <small>
                                    Status: ${item.item_data.status}<br>
                                    Pressure: ${item.item_data.pressure_level}%
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

        function showRoomRemovalReason() {
            const btn = document.getElementById('removeRoomBtn');
            const section = document.getElementById('roomRemovalReasonSection');
            if (btn && section) {
                btn.style.display = 'none';
                section.classList.remove('d-none');
            }
        }

        // Add event listener to reset the remove room reason UI when the modal opens
        document.addEventListener('DOMContentLoaded', () => {
            const updateRoomModalEl = document.getElementById('updateRoomModal');
            if (updateRoomModalEl) {
                updateRoomModalEl.addEventListener('show.bs.modal', () => {
                    const btn = document.getElementById('removeRoomBtn');
                    const section = document.getElementById('roomRemovalReasonSection');
                    const reasonInput = document.getElementById('roomRemovalReason');
                    if (btn) btn.style.display = 'inline-block';
                    if (section) section.classList.add('d-none');
                    if (reasonInput) reasonInput.value = '';
                });
            }
        });

        async function confirmRemoveRoom() {
            const roomId = document.getElementById('updateRoomId').value;
            const code = document.getElementById('updateRoomCode').value || roomId;
            const reasonInput = document.getElementById('roomRemovalReason');
            const reason = reasonInput ? reasonInput.value : '';

            if (!reason.trim()) {
                Swal.fire('Reason Required', 'Please provide a reason for removal.', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: 'Are you sure?',
                text: `You are about to remove Room ${code}. This will move it to archive and may unassign extinguishers.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Remove It!'
            });

            if (result.isConfirmed) {
                try {
                    const resp = await fetch(`/fire-safety/room/${roomId}/remove`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ reason: reason })
                    });

                    const data = await resp.json();
                    if (data.success) {
                        Swal.fire('Removed!', 'Room has been archived.', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to remove room', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire('Error', 'Network error during removal.', 'error');
                }
            }
        }

        async function openRoomHistoryModal(schoolId) {
            const modalEl = document.getElementById('roomHistoryModal');
            if(!modalEl) return;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const tableBody = document.querySelector('#roomHistoryTable tbody');
            if(!tableBody) return;
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
            modal.show();

            try {
                const resp = await fetch(`/fire-safety/room/history/${schoolId}`);
                const data = await resp.json();

                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No removed rooms found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = '';
                data.forEach(item => {
                    const removedAt = new Date(item.removed_at).toLocaleString();
                    const reason = item.reason || 'Manual removal';
                    let archivesHtml = '';
                    if (item.cascaded_archives && item.cascaded_archives.length > 0) {
                        archivesHtml = `<ul>` + item.cascaded_archives.map(a => `<li>${a.type} - ${a.item_code}</li>`).join('') + `</ul>`;
                    } else {
                        archivesHtml = '<span class="text-muted">None</span>';
                    }

                    const row = `
                        <tr>
                            <td>${removedAt}</td>
                            <td>${item.item_data.building_name || 'N/A'}</td>
                            <td class="fw-bold text-danger">${item.item_code || 'N/A'} - Floor ${item.item_data.floor_no || '?'}</td>
                            <td>${reason}</td>
                            <td>${archivesHtml}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } catch (e) {
                console.error(e);
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load history.</td></tr>';
            }
        }

        async function saveExtinguisherStatus() {
            const id = document.getElementById('updateExtId').value;
            const form = document.getElementById('updateExtForm');

            const centerId = document.getElementById('updateCenterRoomSelect').value;

            // Handle "Remove Assignment" option
            if (centerId === '__remove__') {
                const confirm = await Swal.fire({
                    title: 'Remove Assignment?',
                    text: 'This will unlink all rooms from this extinguisher. The extinguisher will no longer cover any rooms.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, Remove'
                });
                if (!confirm.isConfirmed) return;

                try {
                    const resp = await fetch(`/fire-safety/extinguisher/${id}/unassign`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    });
                    const data = await resp.json();
                    if (data.success) {
                        Swal.fire('Done', 'Extinguisher assignment removed.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message || 'Failed to remove assignment.', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire('Error', 'Network error.', 'error');
                }
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const covered = Array.from(document.getElementById('updateCoveredRoomsSelect').selectedOptions).map(o => o.value);
            const centerOpt = document.getElementById('updateCenterRoomSelect').selectedOptions[0];
            const maxLimit = centerOpt ? parseInt(centerOpt.dataset.maxRooms) : 3;

            if (centerId && covered.length > maxLimit) {
                Swal.fire('Constraint Error', `This host room's type can only cover up to ${maxLimit} rooms based on its priority.`, 'warning');
                return;
            }

            const formData = new FormData(form);

            try {
                const resp = await fetch(`/fire-safety/extinguisher/${id}/update`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await resp.json();
                if(data.success) {
                    Swal.fire('Updated', 'Extinguisher status updated successfully!', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Error updating extinguisher', 'error');
                }
            } catch(e) {
                console.error(e);
                Swal.fire('Network Error', 'Failed to update extinguisher status.', 'error');
            }
        }

        async function refreshRecentData(schoolId) {
            await Promise.all([
                loadRecentInspections(schoolId),
                loadRecentRoomUpdates(schoolId)
            ]);
        }

        async function loadRecentInspections(schoolId) {
            const tableBody = document.querySelector(`#inspectionsTable-${schoolId} tbody`);
            if (!tableBody) return;

            try {
                const resp = await fetch(`/fire-safety/extinguisher/inspections/${schoolId}`);
                const data = await resp.json();

                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No recent inspections found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = '';
                data.forEach(item => {
                    let badgeClass = 'secondary';
                    let statusLabel = item.status;
                    if (item.status === 'active') { badgeClass = 'success'; statusLabel = 'OK'; }
                    else if (item.status === 'maintenance') { badgeClass = 'warning'; statusLabel = 'For Preventive Maintenance'; }
                    else if (item.status === 'expired') { badgeClass = 'danger'; statusLabel = 'Used'; }
                    else if (item.status === 'missing') { badgeClass = 'danger'; statusLabel = 'Missing'; }
                    else if (item.status === 'purchase') { badgeClass = 'dark'; statusLabel = 'For Purchase'; }
                    else if (item.status === 'decommissioned') { badgeClass = 'danger'; statusLabel = 'Decommissioned'; }

                    const row = `
                        <tr>
                            <td>${item.date}</td>
                            <td class="fw-bold">${item.code}</td>
                            <td>${item.location}</td>
                            <td>${item.inspector}</td>
                            <td><span class="badge bg-${badgeClass}">${statusLabel}</span></td>
                            <td>${item.pressure_level}%</td>
                            <td>${item.notes || '-'}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } catch(e) {
                console.error(e);
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load data.</td></tr>';
            }
        }



        async function loadRecentRoomUpdates(schoolId) {
            const tableBody = document.querySelector(`#roomsUpdatesTable-${schoolId} tbody`);
            if (!tableBody) return;

            try {
                const resp = await fetch(`/fire-safety/recent-room-updates/${schoolId}`);
                const data = await resp.json();

                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No recent room updates found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = '';
                data.forEach(item => {
                    const row = `
                        <tr>
                            <td class="fw-bold">${item.room_code || 'N/A'}</td>
                            <td>${item.room_name || '-'}</td>
                            <td>${item.floor_level}</td>
                            <td>${item.building_code}</td>
                            <td>${item.nearest_extinguisher}</td>
                            <td>${item.inspector || 'Unknown'}</td>
                            <td>${item.remarks || '-'}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" onclick="openUpdateRoomModal(${item.room_id})">
                                    <i class="fas fa-search"></i> Inspect
                                </button>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } catch(e) {
                console.error(e);
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load data.</td></tr>';
            }
        }

        // Initialize all components on page load
        document.addEventListener('DOMContentLoaded', () => {
            // Restore card expansion states
            const cardStates = JSON.parse(localStorage.getItem('fireSafetyExtCardStates') || '{}');
            Object.keys(cardStates).forEach(cardId => {
                const card = document.getElementById(cardId);
                if (card && cardStates[cardId] === 'collapsed') {
                    card.classList.add('card-collapsed');
                }
            });

            // Initialize other components
            if (typeof initBuildingAccordionPersistence === 'function') initBuildingAccordionPersistence();
            if (typeof initStatusCarousel === 'function') initStatusCarousel();

            // Initial load of data for the active tab or active school
            const activeTabPane = document.querySelector('.tab-pane.show.active');
            let initialSchoolId = '{{ $activeSchool->id ?? "" }}';

            if(activeTabPane) {
                initialSchoolId = activeTabPane.id.replace('school-', '');
            }

            if(initialSchoolId) {
                refreshRecentData(initialSchoolId);
            }

            // Listener for tab changes
            const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabEls.forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', function (event) {
                    const targetId = event.target.getAttribute('data-bs-target');
                    const sId = targetId.replace('#school-', '');
                    refreshRecentData(sId);
                });
            });

            // Add Room Modal Smoke logic
            const addSmokeRequired = document.getElementById('addRoomSmokeDetectorRequired');
            const addSmokeInstalledRow = document.getElementById('addSmokeDetectorInstalledRow');
            if (addSmokeRequired && addSmokeInstalledRow) {
                addSmokeRequired.addEventListener('change', function() {
                    if (this.checked) {
                        addSmokeInstalledRow.classList.remove('d-none');
                        const yes = document.getElementById('addRoomSmokeDetectorYes');
                        const no = document.getElementById('addRoomSmokeDetectorNo');
                        if (!yes.checked && !no.checked) {
                            no.checked = true;
                            document.getElementById('addRoomSmokeDetector').value = 0;
                        }
                    } else {
                        addSmokeInstalledRow.classList.add('d-none');
                        document.getElementById('addRoomSmokeDetectorYes').checked = false;
                        document.getElementById('addRoomSmokeDetectorNo').checked = false;
                        document.getElementById('addRoomSmokeDetector').value = 0;
                    }
                });
            }

            document.querySelectorAll('.add-smoke-installed-toggle').forEach(chk => {
                chk.addEventListener('change', function() {
                    if (this.checked) {
                        document.querySelectorAll('.add-smoke-installed-toggle').forEach(other => {
                            if (other !== this) other.checked = false;
                        });
                        document.getElementById('addRoomSmokeDetector').value = this.value;
                    } else {
                        document.getElementById('addRoomSmokeDetector').value = 0;
                    }
                });
            });

            // Secondary Exit Checkbox logic (Add Room)
            const addSecondaryCheck = document.getElementById('addRoomSecondaryExit');
            if (addSecondaryCheck) {
                addSecondaryCheck.addEventListener('change', function() {
                    const label = document.getElementById('addSecondaryExitRemarksLabel');
                    if (label) label.textContent = this.checked ? 'Secondary Exit Details' : 'Remarks for No Secondary Exit';
                });
            }

            // Update modal UI logic (Update Room)
            const smokeRequired = document.getElementById('updateRoomSmokeDetectorRequired');
            const smokeInstalledRow = document.getElementById('smokeDetectorInstalledRow');
            if (smokeRequired && smokeInstalledRow) {
                smokeRequired.addEventListener('change', function() {
                    if (this.checked) {
                        smokeInstalledRow.classList.remove('d-none');
                        // Default to No if nothing selected yet
                        const smokeYes = document.getElementById('updateRoomSmokeDetectorYes');
                        const smokeNo = document.getElementById('updateRoomSmokeDetectorNo');
                        if (!smokeYes.checked && !smokeNo.checked) {
                            smokeNo.checked = true;
                            document.getElementById('updateRoomSmokeDetector').value = 0;
                        }
                    } else {
                        smokeInstalledRow.classList.add('d-none');
                        document.getElementById('updateRoomSmokeDetectorYes').checked = false;
                        document.getElementById('updateRoomSmokeDetectorNo').checked = false;
                        document.getElementById('updateRoomSmokeDetector').value = 0;
                    }
                });
            }

            // Radio-like behavior for smoke detector installed checkboxes
            document.querySelectorAll('.smoke-installed-toggle').forEach(chk => {
                chk.addEventListener('change', function() {
                    if (this.checked) {
                        document.querySelectorAll('.smoke-installed-toggle').forEach(other => {
                            if (other !== this) other.checked = false;
                        });
                        document.getElementById('updateRoomSmokeDetector').value = this.value;
                    } else {
                        // Keep at least one checked if possible, or just set to 0
                        document.getElementById('updateRoomSmokeDetector').value = 0;
                    }
                });
            });

            const updateSecondaryCheck = document.getElementById('updateRoomSecondaryExit');
            if (updateSecondaryCheck) {
                updateSecondaryCheck.addEventListener('change', function() {
                    const label = document.getElementById('updateSecondaryExitRemarksLabel');
                    if (label) label.textContent = this.checked ? 'Secondary Exit Details' : 'Remarks for No Secondary Exit';
                });
            }
        });


        function onUpdateRoomTypeChange() {
            const sel = document.getElementById('updateRoomTypeSelect');
            const priorityInput = document.getElementById('update_room_priority');
            const warning = document.getElementById('roomTypeChangeWarning');
            const opt = sel.selectedOptions[0];

            if (opt && opt.value !== '') {
                const lbl = opt.dataset.priorityLabel;
                const max = opt.dataset.maxRooms;
                priorityInput.value = lbl ? `${lbl} (Up to ${max} rooms)` : '';

                // Smoke detector question is only for Administration rooms
                const typeText = (opt.textContent || '').trim().toLowerCase();
                const smokeRow = document.getElementById('updateRoomSmokeDetectorRow');
                const smokeReqCb = document.getElementById('updateRoomSmokeDetectorRequired');
                const smokeInstalledRow = document.getElementById('smokeDetectorInstalledRow');

                if (smokeRow && smokeReqCb) {
                    if (typeText.includes('administration')) {
                        smokeRow.classList.remove('d-none');
                    } else {
                        smokeRow.classList.add('d-none');
                        smokeReqCb.checked = false;
                        if (smokeInstalledRow) smokeInstalledRow.classList.add('d-none');
                    }
                }

                // Show warning only if the type actually changed from original
                if (opt.value !== (sel.dataset.original || '')) {
                    warning.classList.remove('d-none');
                } else {
                    warning.classList.add('d-none');
                }
            } else {
                priorityInput.value = '';
                warning.classList.add('d-none');
            }
        }

        async function openUpdateRoomModal(roomId) {
            try {
                const resp = await fetch(`/fire-safety/room/${roomId}`);
                if (!resp.ok) throw new Error('Room not found');
                const data = await resp.json();

                document.getElementById('updateRoomId').value = data.id;
                document.getElementById('updateRoomCode').value = data.room_code || '';
                document.getElementById('updateRoomName').value = data.room_name || '';
                document.getElementById('updateRoomFloor').value = data.floor_no + " Floor";
                document.getElementById('updateRoomRemarks').value = data.remarks || '';

                // Populate room type
                const updateTypeSelect = document.getElementById('updateRoomTypeSelect');
                const updatePriorityInput = document.getElementById('update_room_priority');
                updateTypeSelect.value = data.room_type_config_id || '';
                // find the selected option's priority label
                const selOpt = updateTypeSelect.querySelector(`option[value="${data.room_type_config_id}"]`);
                if (selOpt) {
                    const lbl = selOpt.dataset.priorityLabel;
                    const max = selOpt.dataset.maxRooms;
                    updatePriorityInput.value = lbl ? `${lbl} (Up to ${max} rooms)` : '';
                } else {
                    updatePriorityInput.value = '';
                }
                // Store original type id for comparison
                updateTypeSelect.dataset.original = data.room_type_config_id || '';
                document.getElementById('roomTypeChangeWarning').classList.add('d-none');

                const hasSecondary = !!data.has_secondary_exit;
                document.getElementById('updateRoomSecondaryExit').checked = hasSecondary;
                document.getElementById('updateSecondaryExitRemarks').value = data.secondary_exit_remarks || '';
                document.getElementById('updateSecondaryExitRemarksLabel').textContent = hasSecondary ? 'Secondary Exit Details' : 'Remarks for No Secondary Exit';

                // Populate candidates for nearest extinguisher
                const candidatesResp = await fetch(`/fire-safety/room/${roomId}/candidates`);
                const candidates = await candidatesResp.json();

                const select = document.getElementById('updateRoomNearest');
                select.innerHTML = ''; // Start fresh

                if (data.is_center_room) {
                    select.disabled = true;
                    const opt = document.createElement('option');
                    opt.value = "";
                    opt.textContent = "HOST ROOM (Hosts own extinguisher)";
                    opt.selected = true;
                    select.appendChild(opt);
                    select.classList.add('bg-light');
                } else {
                    select.disabled = false;
                    select.classList.remove('bg-light');

                    // Add "None / Self-Covered" option
                    const noneOpt = document.createElement('option');
                    noneOpt.value = "";
                    noneOpt.textContent = "None / Uncovered";
                    noneOpt.selected = (!data.host_room_id);
                    select.appendChild(noneOpt);

                    // If it's covered by a room NOT in candidates (e.g. extinguisher full), still show it
                    const currentHostInCandidates = candidates.some(c => c.id == data.host_room_id);
                    if (data.host_room_id && !currentHostInCandidates) {
                        const hostOpt = document.createElement('option');
                        hostOpt.value = data.host_room_id;
                        hostOpt.textContent = `${data.host_room_code || ('Room #' + data.host_room_id)} (Current)`;
                        hostOpt.selected = true;
                        select.appendChild(hostOpt);
                    }

                    candidates.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = `${c.room_code || ('Room #' + c.id)}`;
                        if (data.host_room_id == c.id) opt.selected = true;
                        select.appendChild(opt);
                    });
                }

                // Smoke detector logic
                const smokeDetectorRow = document.getElementById('updateRoomSmokeDetectorRow');
                const smokeReqCb = document.getElementById('updateRoomSmokeDetectorRequired');
                const smokeInstalledRow = document.getElementById('smokeDetectorInstalledRow');
                const smokeInstalledHidden = document.getElementById('updateRoomSmokeDetector');
                const smokeYes = document.getElementById('updateRoomSmokeDetectorYes');
                const smokeNo = document.getElementById('updateRoomSmokeDetectorNo');

                const typeName = (data.room_type || '').toLowerCase();
                if (typeName.includes('administration')) {
                    smokeDetectorRow.classList.remove('d-none');
                    const isRequired = !!data.smoke_detector_required;
                    smokeReqCb.checked = isRequired;

                    if (isRequired) {
                        smokeInstalledRow.classList.remove('d-none');
                        const isInstalled = !!data.has_smoke_detector;
                        smokeInstalledHidden.value = isInstalled ? 1 : 0;
                        smokeYes.checked = isInstalled;
                        smokeNo.checked = !isInstalled;
                    } else {
                        smokeInstalledRow.classList.add('d-none');
                        smokeInstalledHidden.value = 0;
                        smokeYes.checked = false;
                        smokeNo.checked = false;
                    }
                } else {
                    smokeDetectorRow.classList.add('d-none');
                    smokeReqCb.checked = false;
                    smokeInstalledRow.classList.add('d-none');
                    smokeInstalledHidden.value = 0;
                    smokeYes.checked = false;
                    smokeNo.checked = false;
                }

                const modalEl = document.getElementById('updateRoomModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                // Approval Section Visibility (Admin only)
                const approvalSection = document.getElementById('roomApprovalSection');
                const saveBtn = document.getElementById('saveRoomUpdateBtn');

                if (approvalSection) {
                    if (userRole === 'admin' && data.approval_status === 'pending' && data.last_inspector_role === 'contributor') {
                        approvalSection.classList.remove('d-none');
                        if (saveBtn) saveBtn.classList.add('d-none'); // Hide save changes during approval flow
                    } else {
                        approvalSection.classList.add('d-none');
                        if (saveBtn) saveBtn.classList.remove('d-none');
                    }
                }

                // Enforce viewer role restrictions
                checkViewerAccess('updateRoomForm');

            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Failed to load room details.', 'error');
            }
        }

        async function saveRoomUpdate() {
            const id = document.getElementById('updateRoomId').value;
            const form = document.getElementById('updateRoomForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            // Removed _method PUT as suggested - using POST directly


            try {
                const resp = await fetch(`/fire-safety/room/${id}/update`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await resp.json();
                if (data.success) {
                    if (data.type_changed) {
                        // Update visible coverage badges immediately (before reload), so the room list reflects "Uncovered"
                        const affected = Array.isArray(data.affected_room_ids) ? data.affected_room_ids : [];
                        affected.forEach(rid => {
                            const el = document.querySelector(`.coverage-badges[data-room-id="${rid}"]`);
                            if (el) el.innerHTML = '<span class="badge bg-warning text-dark">Uncovered</span>';
                        });
                        Swal.fire({
                            icon: 'success',
                            title: 'Room Type Changed',
                            text: 'Room updated successfully. All extinguisher assignments for this room have been cleared. Please re-assign them via Update Extinguisher.',
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Updated', 'Room details updated successfully!', 'success').then(() => {
                            location.reload();
                        });
                    }
                } else {
                    Swal.fire('Error', data.message || 'Failed to update room', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Network error during room update.', 'error');
            }
        }

        async function approveRoomAction() {
            const id = document.getElementById('updateRoomId').value;
            try {
                const resp = await fetch(`/fire-safety/room/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                    }
                });
                const data = await resp.json();
                if (data.success) {
                    Swal.fire('Approved', 'Room update approved successfully!', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to approve', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Network error during approval.', 'error');
            }
        }

        async function rejectRoomAction() {
            const id = document.getElementById('updateRoomId').value;
            const reason = document.getElementById('roomRejectionReason').value;

            if (!reason) {
                Swal.fire('Required', 'Please provide a reason for rejection.', 'warning');
                return;
            }

            try {
                const resp = await fetch(`/fire-safety/room/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reason: reason })
                });
                const data = await resp.json();
                if (data.success) {
                    Swal.fire('Rejected', 'Room update has been rejected.', 'info').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to reject', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Network error during rejection.', 'error');
            }
        }

        function inspectRoom(roomId) {
            openUpdateRoomModal(roomId);
        }



        // Toggle division function
        function toggleDivision(icon, cardId) {
            const card = icon.closest('.card');
            card.id = cardId;
            card.classList.toggle('card-collapsed');

            const cardStates = JSON.parse(localStorage.getItem('fireSafetyExtCardStates') || '{}');
            cardStates[cardId] = card.classList.contains('card-collapsed') ? 'collapsed' : 'expanded';
            localStorage.setItem('fireSafetyExtCardStates', JSON.stringify(cardStates));
        }

        // Building accordion persistence helpers (manual collapse)
        function initBuildingAccordionPersistence() {
            // Clear any existing saved states to start fresh
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                let key = localStorage.key(i);
                if (key && key.startsWith('fireSafetyOpenBuildings_')) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(key => localStorage.removeItem(key));

            // Ensure all buildings are closed by default
            document.querySelectorAll('[id^="collapse-"]').forEach(el => {
                el.classList.remove('show');
                const btn = document.querySelector(`.accordion-button[data-bs-target="#${el.id}"]`);
                if (btn) {
                    btn.classList.add('collapsed');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // helper to update localStorage when manually toggling
        function updateOpenBuildings(id, add) {
            const parts = id.replace('#','').split('-');
            const schoolId = parts[1];
            const key = `fireSafetyOpenBuildings_${schoolId}`;
            let list = JSON.parse(localStorage.getItem(key) || '[]');
            if (add) {
                if (!list.includes(id.replace('#','')) ) list.push(id.replace('#',''));
            } else {
                list = list.filter(x => x !== id.replace('#',''));
            }
            localStorage.setItem(key, JSON.stringify(list));
        }

        // Status carousel helpers
        function initStatusCarousel() {
            const slides = document.querySelectorAll('#statusCarousel .status-slide');
            if (slides.length === 0) return;
            let current = 0;
            slides.forEach((s,i)=>{
                if(i!==0) s.classList.remove('active');
            });
            setInterval(() => {
                slides[current].classList.remove('active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('active');
            }, 3000);
        }

        document.getElementById('addRoomModal').addEventListener('hidden.bs.modal', function () {
            const bSelect = document.getElementById('roomBuildingSelect');
            if (bSelect) {
                bSelect.disabled = false;
                bSelect.options[0].text = 'Select Building';
                bSelect.value = "";
                document.getElementById('roomSchoolId').value = "";
            }
        });

        document.getElementById('addExtModal').addEventListener('hidden.bs.modal', function () {
            const bSelect = document.getElementById('extBuildingSelect');
            if (bSelect) {
                bSelect.disabled = false;
                bSelect.options[0].text = 'Select Building';
                bSelect.value = "";
                document.getElementById('extSchoolId').value = "";
            }
        });

        async function openAddRoomForBuilding(schoolId, buildingId, buildingName) {
            console.log("Opening Add Room Modal for Building:", buildingId);

            // Set values
            const schoolIdInput = document.getElementById('roomSchoolId');
            if (schoolIdInput) schoolIdInput.value = schoolId;

            // Clean buildingName (remove extra info if already has "(Locked)")
            const cleanName = buildingName.replace(' (Locked)', '').replace('Building: ', '');

            // Populate buildings and pre-select/lock the target one
            populateBuildingsForSchool(schoolId, buildingId, cleanName);

            const bSelect = document.getElementById('roomBuildingSelect');
            bSelect.value = buildingId;
            bSelect.disabled = true;

            // Load floors for this building
            await updateRoomFloors(buildingId);

            const modalEl = document.getElementById('addRoomModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        async function openAddExtinguisherForBuilding(schoolId, buildingId, buildingName) {
            console.log("Opening Add Extinguisher Modal for Building:", buildingId);

            const schoolIdInput = document.getElementById('extSchoolId');
            if (schoolIdInput) schoolIdInput.value = schoolId;

            const cleanName = buildingName.replace(' (Locked)', '').replace('Building: ', '');
            populateBuildingsForSchool(schoolId, buildingId, cleanName);

            const bSelect = document.getElementById('extBuildingSelect');
            bSelect.value = buildingId;
            bSelect.disabled = true;

            // Trigger floor and room population for extinguisher
            await handleExtBuildingChange();

            const modalEl = document.getElementById('addExtModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    </script>
@endsection
