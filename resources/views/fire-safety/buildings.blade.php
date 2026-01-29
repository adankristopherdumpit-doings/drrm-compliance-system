<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buildings - Fire Safety</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --fire-red: #A8191F;
            --fire-dark-red: #8A1217;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .top-nav {
            background-color: var(--fire-red);
            height: 60px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sidebar {
            background-color: var(--fire-red);
            width: 250px;
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            z-index: 1020;
            overflow-y: auto;
        }

        .main-content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
            background-color: #f8f9fa;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            display: flex;
            align-items: center;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--fire-dark-red);
            color: white;
            text-decoration: none;
        }

        .nav-link.active {
            border-left: 4px solid white;
        }

        .nav-icon {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }

        .dashboard-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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

        .school-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .school-tab-btn {
            color: #495057;
            background-color: transparent;
            border: 1px solid transparent;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
            margin-bottom: -2px;
        }

        .school-tab-btn:hover {
            color: white;
            background-color: #8A1217;
            border-color: #8A1217 #8A1217 #dee2e6;
        }

        .school-tab-btn.active {
            color: white !important;
            background-color: #8A1217 !important;
            border-color: #8A1217 #8A1217 #8A1217 !important;
            position: relative;
            z-index: 1;
        }

        .school-tab-btn:not(.active):not(:hover) {
            color: #495057;
            background-color: #f8f9fa;
            border-color: #dee2e6 #dee2e6 #dee2e6;
        }

        .school-tab-btn:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(168, 25, 31, 0.25);
        }

        .no-buildings {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
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
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="top-nav">
        <div class="container-fluid h-100">
            <div class="row h-100 align-items-center">
                <div class="col-auto">
                    <a href="{{ route('fire-safety.dashboard') }}" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        <i class="fas fa-fire me-2"></i>
                        <span class="fw-bold">Fire Safety Checklist System</span>
                    </a>
                </div>

                <div class="col text-center">
                    <h4 class="text-white mb-0">Building Management</h4>
                </div>

                <div class="col-auto">
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <a href="#" class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle fa-lg me-2"></i>
                                <span>{{ Auth::user()->name ?? 'User' }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('fire-safety.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                   <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fire-safety.dashboard') }}">
                        <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('fire-safety.buildings') }}">
                        <span class="nav-icon"><i class="fas fa-building"></i></span>
                        <span>Buildings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fire-safety.alarm-systems') }}">
                        <span class="nav-icon"><i class="fas fa-bell"></i></span>
                        <span>Alarm Systems</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fire-safety.extinguishers') }}">
                        <span class="nav-icon"><i class="fas fa-fire-extinguisher"></i></span>
                        <span>Fire Extinguishers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fire-safety.evacuation-plans') }}">
                        <span class="nav-icon"><i class="fas fa-map-signs"></i></span>
                        <span>Evacuation Plans</span>
                    </a>
                </li>
            </ul>

            <hr class="bg-white my-4">

            <!-- Quick Stats -->
            <div class="mt-4">
                <h6 class="text-white mb-3">Building Safety Overview</h6>
                <div id="sidebarStats">
                    <!-- Stats will be loaded via JavaScript -->
                    <div class="text-center text-white py-3">
                        <i class="fas fa-spinner fa-spin"></i> Loading stats...
                    </div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                        <i class="fas fa-plus me-2"></i> Add Building
                    </button>
                    <button class="btn btn-light btn-sm" onclick="generateBuildingReport()">
                        <i class="fas fa-file-pdf me-2"></i> Safety Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @if($schools->isEmpty())
        <!-- No Schools Found Message -->
        <div class="row">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-school fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted mb-3">No Schools Found</h4>
                        <p class="text-muted mb-4">You need to add a school that will be under inspection first.</p>
                        <a href="{{ route('fire-safety.dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Go to Dashboard to Add School
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- School Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-body p-0">
                        <div class="school-tabs">
                            <nav>
                                <div class="nav nav-tabs border-0" id="schoolTab" role="tablist">
                                    @foreach($schools as $school)
                                    <button class="nav-link school-tab-btn {{ $loop->first ? 'active' : '' }}"
                                            id="school-tab-{{ $school->id }}"
                                            data-bs-toggle="tab"
                                            data-bs-target="#school-{{ $school->id }}"
                                            type="button"
                                            role="tab"
                                            aria-controls="school-{{ $school->id }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                            data-school-id="{{ $school->id }}">
                                        {{ $school->school_name }}
                                    </button>
                                    @endforeach
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="schoolTabContent">
            @foreach($schools as $school)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="school-{{ $school->id }}">
                <!-- Building Summary -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-success h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                            Total Buildings
                                        </div>
                                        <div class="h2 mb-0 fw-bold text-gray-800">
                                            {{ $school->buildings->count() }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-primary h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                            Total Floors
                                        </div>
                                        <div class="h2 mb-0 fw-bold text-gray-800">
                                            {{ $school->buildings->sum('floors') }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-layer-group fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-warning h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                            Total Rooms
                                        </div>
                                        <div class="h2 mb-0 fw-bold text-gray-800">
                                            {{ $school->buildings->sum('rooms') }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-door-closed fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-info h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                            Minimum Fire Extinguishers
                                        </div>
                                        <div class="h2 mb-0 fw-bold text-gray-800">
                                            {{ $school->buildings->sum(fn ($b) => max(1, (int) ceil(($b->rooms ?? 0) / 3))) }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-fire-extinguisher fa-2x text-info"></i>
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
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">
                                    <i class="fas fa-building me-2"></i> Buildings - {{ $school->school_name }}
                                </h6>
                                <div>
                                    <button class="btn btn-primary btn-sm me-2 add-building-btn"
                                            data-school-id="{{ $school->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addBuildingModal">
                                        <i class="fas fa-plus me-2"></i> Add Building
                                    </button>
                                    <button class="btn btn-success btn-sm schedule-inspection-btn"
                                            data-school-id="{{ $school->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#scheduleInspectionModal">
                                        <i class="fas fa-calendar-plus me-2"></i> Schedule Inspection
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                @if($school->buildings->count() > 0)
                                <div class="row">
                                    @foreach($school->buildings as $building)
                                    @php
                                        $compliance = \App\Http\Controllers\FireSafetyController::calculateBuildingCompliance($building);
                                        $statusClass = $compliance >= 80 ? 'border-success' : ($compliance >= 60 ? 'border-warning' : 'border-danger');
                                        $statusBadge = $compliance >= 80 ? 'bg-success' : ($compliance >= 60 ? 'bg-warning' : 'bg-danger');
                                        $statusText = $compliance >= 80 ? 'Compliant' : ($compliance >= 60 ? 'Needs Attention' : 'Non-Compliant');
                                    @endphp
                                    <div class="col-xl-4 col-lg-6 mb-4">
                                        <div class="card building-card {{ $statusClass }}">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1">{{ $building->building_no }}</h5>
                                                        <p class="text-muted mb-0">
                                                            <i class="fas fa-map-marker-alt me-1"></i> {{ $school->school_name }}
                                                        </p>
                                                    </div>
                                                    <span class="badge {{ $statusBadge }}">{{ $statusText }}</span>
                                                </div>

                                                <div class="building-stats mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Floors: <strong>{{ $building->floors }}</strong></span>
                                                        <span>Rooms: <strong>{{ $building->rooms }}</strong></span>
                                                        <span>Minimum Fire Extinguishers: <strong>{{ max(1, (int) ceil(($building->rooms ?? 0) / 3)) }}</strong></span>
                                                    </div>
                                                </div>

                                                <!-- Equipment Summary -->
                                                <div class="mb-3 p-3 bg-light rounded">
                                                    <small class="d-block mb-2">
                                                        @php
                                                            $alarmCount = $building->alarmSystems->count();
                                                            $extinguisherCount = $building->fireExtinguishers->count();
                                                        @endphp
                                                        <i class="fas fa-bell text-info me-1"></i> Alarms: <strong>{{ $alarmCount }}</strong>
                                                    </small>
                                                    <small class="d-block mb-2">
                                                        <i class="fas fa-fire-extinguisher text-danger me-1"></i> Extinguishers: <strong>{{ $extinguisherCount }}</strong>
                                                    </small>
                                                    <small class="d-block">
                                                        <i class="fas fa-door-open text-warning me-1"></i> Exits: <strong>{{ $building->emergency_exits ?? 0 }}</strong>
                                                    </small>
                                                </div>

                                                <div class="mt-3 d-grid gap-2">
                                                    <button class="btn btn-sm btn-outline-primary view-building-btn"
                                                            data-building-id="{{ $building->id }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#viewBuildingModal">
                                                        <i class="fas fa-eye me-2"></i> View Details
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success inspect-building-btn"
                                                            data-building-id="{{ $building->id }}"
                                                            data-building-name="{{ $building->building_no }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#scheduleInspectionModal">
                                                        <i class="fas fa-clipboard-check me-2"></i> Inspect Now
                                                    </button>
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
                                    <button class="btn btn-primary add-building-btn"
                                            data-school-id="{{ $school->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addBuildingModal">
                                        <i class="fas fa-plus me-2"></i> Add First Building
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Building Inspection Schedule -->
                <div class="row mt-4">
                    <div class="col-lg-8">
                        <div class="card dashboard-card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">
                                    <i class="fas fa-calendar-alt me-2"></i> Upcoming Inspections - {{ $school->school_name }}
                                </h6>
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="loadAllInspections({{ $school->id }})">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="inspectionsList-{{ $school->id }}">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        Loading inspections...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card dashboard-card">
                            <div class="card-header py-3 bg-primary text-white">
                                <h6 class="m-0 fw-bold">
                                    <i class="fas fa-chart-pie me-2"></i> Compliance Statistics - {{ $school->school_name }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="complianceStats-{{ $school->id }}">
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin"></i> Loading statistics...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
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
                        <input type="hidden" name="school_id" id="buildingSchoolId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building Number/Code</label>
                                <input type="text" class="form-control" name="building_no" placeholder="e.g., BLDG-001, Main Building" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building Name</label>
                                <input type="text" class="form-control" name="building_name" placeholder="e.g., Science Building, Gymnasium" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Number of Floors</label>
                                <input type="number" class="form-control" name="floors" min="1" max="50" value="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Total Rooms</label>
                                <input type="number" class="form-control" name="rooms" min="1" value="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Maximum Capacity</label>
                                <input type="number" class="form-control" name="capacity" placeholder="e.g., 500" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year Constructed</label>
                                <input type="number" class="form-control" name="year_constructed" min="1900" max="{{ date('Y') }}" placeholder="e.g., 1990">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Renovation</label>
                                <input type="number" class="form-control" name="last_renovation" min="1900" max="{{ date('Y') }}" placeholder="e.g., 2020">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Number of Emergency Exits</label>
                                <input type="number" class="form-control" name="emergency_exits" min="0" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building Type</label>
                                <select class="form-control" name="building_type">
                                    <option value="">Select Type</option>
                                    <option value="classroom">Classroom Building</option>
                                    <option value="administrative">Administrative Building</option>
                                    <option value="library">Library</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="gymnasium">Gymnasium</option>
                                    <option value="cafeteria">Cafeteria</option>
                                    <option value="dormitory">Dormitory</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Building Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe the building features, location, etc..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Safety Features Installed</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="fire_alarms" id="fireAlarms">
                                        <label class="form-check-label" for="fireAlarms">Fire Alarm System</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="sprinklers" id="sprinklers">
                                        <label class="form-check-label" for="sprinklers">Sprinkler System</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="emergency_lights" id="emergencyLights">
                                        <label class="form-check-label" for="emergencyLights">Emergency Lighting</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="fire_extinguishers" id="fireExtinguishers">
                                        <label class="form-check-label" for="fireExtinguishers">Fire Extinguishers</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="exit_signs" id="exitSigns">
                                        <label class="form-check-label" for="exitSigns">Exit Signs</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="fire_doors" id="fireDoors">
                                        <label class="form-check-label" for="fireDoors">Fire Doors</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveBuilding()">
                        <i class="fas fa-save me-2"></i> Save Building
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Inspection Modal -->
    <div class="modal fade" id="scheduleInspectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus me-2"></i> Schedule Inspection
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleInspectionForm" action="{{ route('fire-safety.inspection.schedule') }}" method="POST">
                        @csrf
                        <input type="hidden" name="school_id" id="inspectionSchoolId">

                        <div class="mb-3">
                            <label class="form-label">Inspection Date *</label>
                            <input type="date" class="form-control" name="inspection_date" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Building *</label>
                            <select class="form-control" name="building_id" id="buildingSelect" required>
                                <option value="">Select Building</option>
                                <!-- Buildings will be populated by JavaScript -->
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Inspection Type *</label>
                            <select class="form-control" name="inspection_type" required>
                                <option value="">Select Type</option>
                                <option value="routine">Routine Safety Audit</option>
                                <option value="quarterly">Quarterly Inspection</option>
                                <option value="annual">Annual Comprehensive</option>
                                <option value="fire_drill">Fire Drill</option>
                                <option value="emergency">Emergency Inspection</option>
                                <option value="preventive">Preventive Maintenance</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Inspector *</label>
                            <input type="text" class="form-control" name="inspector" value="{{ Auth::user()->name }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes/Remarks</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional instructions or notes..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveInspection()">
                        <i class="fas fa-calendar-check me-2"></i> Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Helper functions
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

        // Store current school ID
        let currentSchoolId = null;

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

                try {
                    const response = await fetch(`/fire-safety/building/${buildingId}`);
                    const building = await response.json();

                    let html = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Building Code:</strong> ${building.building_no}</p>
                                <p><strong>Building Name:</strong> ${building.building_name || 'N/A'}</p>
                                <p><strong>School:</strong> ${building.school?.school_name || 'N/A'}</p>
                                <p><strong>Building Type:</strong> ${building.building_type || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Floors:</strong> ${building.floors}</p>
                                <p><strong>Rooms:</strong> ${building.rooms}</p>
                                <p><strong>Number of Families:</strong> ${building.capacity ?? 'N/A'}</p>
                                <p><strong>Minimum Fire Extinguishers:</strong> ${Math.max(1, Math.ceil((Number(building.rooms) || 0) / 3))}</p>
                                <p><strong>Emergency Exits:</strong> ${building.emergency_exits || 'N/A'}</p>
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
                                ${building.features ? building.features.split(',').map(feature =>
                                    `<span class="badge bg-info me-2 mb-2">${feature}</span>`
                                ).join('') : 'No safety features recorded.'}
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            To update building information or remove this building, please contact the system administrator.
                        </div>
                    `;

                    document.getElementById('buildingDetailsContent').innerHTML = html;

                } catch (error) {
                    console.error('Error loading building details:', error);
                    document.getElementById('buildingDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Failed to load building details. Please try again.
                        </div>
                    `;
                }
            });
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

        // Load inspections for a school
        async function loadInspections(schoolId) {
            console.log('Loading inspections for school:', schoolId);

            const container = document.getElementById(`inspectionsList-${schoolId}`);
            if (!container) {
                console.error('Container not found for school:', schoolId);
                return;
            }

            try {
                const response = await fetch(`/fire-safety/inspections/${schoolId}`);
                console.log('Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const inspections = await response.json();
                console.log('Inspections loaded:', inspections);

                if (!inspections || inspections.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-2x mb-3"></i>
                            <p>No upcoming inspections scheduled.</p>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleInspectionModal">
                                <i class="fas fa-calendar-plus me-1"></i> Schedule One Now
                            </button>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Building</th>
                                    <th>Type</th>
                                    <th>Inspector</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                inspections.forEach(inspection => {
                    const date = formatDate(inspection.inspection_date);
                    const statusClass = getStatusClass(inspection.status);
                    const statusText = getInspectionTypeText(inspection.status);
                    const typeText = getInspectionTypeText(inspection.inspection_type);

                    html += `
                        <tr>
                            <td>${date}</td>
                            <td>${inspection.building_name || 'N/A'}</td>
                            <td>${typeText}</td>
                            <td>${inspection.inspector || 'N/A'}</td>
                            <td>
                                <span class="badge bg-${statusClass}">${statusText}</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="viewInspection(${inspection.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${inspection.status === 'scheduled' ? `
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelInspection(${inspection.id})">
                                    <i class="fas fa-times"></i>
                                </button>
                                ` : `
                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                    <i class="fas fa-times"></i>
                                </button>
                                `}
                            </td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                container.innerHTML = html;

            } catch (error) {
                console.error('Error loading inspections:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load inspections. Please try again.
                        <button class="btn btn-sm btn-light ms-3" onclick="loadInspections(${schoolId})">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>
                `;
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
            alert('Inspections refreshed!');
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
                alert('Failed to load buildings. Please try again.');
            }
        }

        // Save Building
        async function saveBuilding() {
            const form = document.getElementById('addBuildingForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Get CSRF token
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {
                csrfToken = document.querySelector('input[name="_token"]')?.value;

                if (!csrfToken) {
                    alert('CSRF token missing. Please refresh the page and try again.');
                    return;
                }
            }

            const formData = new FormData(form);

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
                    alert('Building added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to add building'));
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Failed to add building. Please try again.');
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
                    alert('Inspection scheduled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to schedule inspection'));
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Failed to schedule inspection');
            }
        }

        // View Inspection Details
        async function viewInspection(inspectionId) {
            try {
                const response = await fetch(`/fire-safety/inspection/${inspectionId}`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const inspection = await response.json();

                // Create and show modal
                const modalHtml = `
                    <div class="modal fade" id="viewInspectionModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                                    <h5 class="modal-title">
                                        <i class="fas fa-clipboard-check me-2"></i> Inspection #${inspection.id}
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <strong>Inspection Date:</strong><br>
                                                <span class="text-muted">${formatDate(inspection.inspection_date)}</span>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Building:</strong><br>
                                                <span class="text-muted">${inspection.building_name || 'N/A'}</span>
                                            </div>
                                            <div class="mb-3">
                                                <strong>School:</strong><br>
                                                <span class="text-muted">${inspection.school?.school_name || 'N/A'}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <strong>Inspection Type:</strong><br>
                                                <span class="badge bg-primary">${getInspectionTypeText(inspection.inspection_type)}</span>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Status:</strong><br>
                                                <span class="badge bg-${getStatusClass(inspection.status)}">${getInspectionTypeText(inspection.status)}</span>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Inspector:</strong><br>
                                                <span class="text-muted">${inspection.inspector || 'N/A'}</span>
                                            </div>
                                        </div>
                                    </div>

                                    ${inspection.notes ? `
                                    <div class="mb-4">
                                        <strong>Notes:</strong>
                                        <div class="border rounded p-3 bg-light mt-1">${inspection.notes}</div>
                                    </div>
                                    ` : ''}

                                    ${inspection.findings ? `
                                    <div class="mb-4">
                                        <strong>Findings:</strong>
                                        <div class="border rounded p-3 bg-light mt-1">${inspection.findings}</div>
                                    </div>
                                    ` : ''}

                                    ${inspection.recommendations ? `
                                    <div class="mb-4">
                                        <strong>Recommendations:</strong>
                                        <div class="border rounded p-3 bg-light mt-1">${inspection.recommendations}</div>
                                    </div>
                                    ` : ''}

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Created on: ${formatDate(inspection.created_at)}
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    ${inspection.status === 'scheduled' ? `
                                    <button type="button" class="btn btn-primary" onclick="startInspection(${inspection.id})">
                                        <i class="fas fa-play me-2"></i> Start Inspection
                                    </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if any
                const existingModal = document.getElementById('viewInspectionModal');
                if (existingModal) existingModal.remove();

                // Add modal to body and show it
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('viewInspectionModal'));
                modal.show();

            } catch (error) {
                console.error('Error loading inspection details:', error);
                alert('Failed to load inspection details. Please try again.');
            }
        }

        // Cancel Inspection with confirmation and API call
        async function cancelInspection(inspectionId) {
            if (!confirm('Are you sure you want to cancel this inspection? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/fire-safety/inspection/${inspectionId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ _method: 'DELETE' })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Inspection cancelled successfully!');
                    // Reload the current school's inspections
                    if (currentSchoolId) {
                        await loadInspections(currentSchoolId);
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to cancel inspection'));
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Failed to cancel inspection. Please try again.');
            }
        }

        // Start Inspection function
        async function startInspection(inspectionId) {
            // Redirect to inspection checklist page
            window.location.href = `/fire-safety/inspection/${inspectionId}/checklist`;
        }

        // Generate building report
        function generateBuildingReport() {
            if (confirm('Generate comprehensive building safety report for all schools?')) {
                alert('Building safety report generation started... This may take a moment.');
                // In real implementation, this would generate PDF report
                window.open('/fire-safety/generate-report', '_blank');
            }
        }
    </script>
</body>
</html>
