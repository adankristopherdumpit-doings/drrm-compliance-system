<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alarm Systems - Fire Safety</title>
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

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .alarm-status {
            font-size: 1rem;
            font-weight: 500;
        }

        .status-functional { color: #28a745; }
        .status-online { color: #28a745; }
        .status-broken { color: #dc3545; }
        .status-offline { color: #dc3545; }
        .status-jammed { color: #ffc107; }
        .status-under-repair { color: #ffc107; }
        .status-maintenance { color: #ffc107; }
        .status-missing { color: #6c757d; }
        .status-not-installed { color: #6c757d; }
        .status-system-error { color: #dc3545; }
        .status-decommissioned { color: #6c757d; }

        .test-overdue {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        /* Update existing nav-tabs styles */
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            color: #495057;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-bottom: none;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
            margin-bottom: -1px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            color: white;
            background-color: #8A1217;
            border-color: #8A1217 #8A1217 #dee2e6;
        }

        .nav-tabs .nav-link.active {
            color: white !important;
            background-color: #8A1217 !important;
            border-color: #8A1217 #8A1217 #8A1217 !important;
            position: relative;
            z-index: 1;
        }

        .nav-tabs .nav-link:not(.active):not(:hover) {
            background-color: #f8f9fa;
        }

        .nav-tabs .nav-link:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(168, 25, 31, 0.25);
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
                    <h4 class="text-white mb-0">Alarm System Management</h4>
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
                    <a class="nav-link" href="{{ route('fire-safety.buildings') }}">
                        <span class="nav-icon"><i class="fas fa-building"></i></span>
                        <span>Buildings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('fire-safety.alarm-systems') }}">
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
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fire-safety.settings') }}">
                        <span class="nav-icon"><i class="fas fa-cog"></i></span>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>

            <hr class="bg-white my-4">

            <!-- Quick Actions -->
            <div class="mt-4">
                <h6 class="text-white mb-3">Quick Actions</h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addAlarmModal">
                        <i class="fas fa-plus me-2"></i> Add Alarm System
                    </button>
                    <button class="btn btn-light btn-sm" id="simulateAlarmBtn">
                        <i class="fas fa-bell me-2"></i> Simulate Alarm Test
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- THIS IS WHERE THE INSTRUCTION APPLIES (around line 130) -->
        @if($schools->isEmpty())
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
        <!-- School Tabs (ALWAYS SHOWN) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="schoolTab">
                            @foreach($schools as $school)
                            <li class="nav-item">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                        data-bs-toggle="tab"
                                        data-bs-target="#school-{{ $school->id }}"
                                        data-school-id="{{ $school->id }}">
                                    {{ $school->school_name }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tab Content -->
        <div class="tab-content" id="schoolTabContent">
            @foreach($schools as $school)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="school-{{ $school->id }}">
                <!-- System Overview Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-success h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                            Functional/Online
                                        </div>
                                        <div class="h2 mb-0 fw-bold text-gray-800">
                                            {{ $school->alarmSystems()->whereIn('status', ['functional', 'online'])->count() }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-danger h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">
                                            Issues
                                        </div>
                                        <div class="h2 mb-0 fw-bold text-gray-800">
                                            {{ $school->alarmSystems()->whereNotIn('status', ['functional', 'online'])->count() }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-danger"></i>
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
                                            Needs Testing
                                        </div>
                                        <div class="h2 mb-0 fw-bold text-gray-800">
                                            {{ $school->alarmSystems()->where('next_test_due', '<', now())->count() }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-warning"></i>
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
                                            Last Test Date
                                        </div>
                                        <div class="h5 mb-0 fw-bold text-gray-800">
                                            @php
                                                $lastTest = $school->alarmSystems()->max('last_test');
                                                echo $lastTest ? \Carbon\Carbon::parse($lastTest)->format('Y-m-d') : 'Never';
                                            @endphp
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alarm Systems Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">
                                    <i class="fas fa-list me-2"></i> Alarm Systems List - {{ $school->school_name }}
                                </h6>
                                <button class="btn btn-primary btn-sm add-alarm-btn"
                                        data-school-id="{{ $school->id }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addAlarmModal">
                                    <i class="fas fa-plus me-2"></i> Add New System
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Code</th>
                                                <th>Building</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Last Test</th>
                                                <th>Next Test Due</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($school->alarmSystems as $alarm)
                                            @php
                                                $isOverdue = false; // Temporarily disable overdue check
                                                $building = $alarm->building ?? null;
                                            @endphp
                                            <tr class="{{ $isOverdue ? 'test-overdue' : '' }}">
                                                <td>{{ $alarm->code }}</td>
                                                <td>{{ $building ? $building->building_no : 'N/A' }}</td>
                                                <td>{{ $alarm->alarm_type }}</td>
                                                <td>
                                                    @php
                                                        $statusClass = 'status-' . str_replace(' ', '-', strtolower($alarm->status));
                                                    @endphp
                                                    <span class="alarm-status {{ $statusClass }}">
                                                        <i class="fas fa-circle"></i> {{ ucfirst($alarm->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $alarm->last_test ? \Carbon\Carbon::parse($alarm->last_test)->format('Y-m-d') : 'Never' }}</td>
                                                <td>
                                                    {{ $alarm->next_test_due ? \Carbon\Carbon::parse($alarm->next_test_due)->format('Y-m-d') : 'Not set' }}
                                                    @if($isOverdue)
                                                        <span class="badge bg-danger ms-2">Overdue</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary test-now-btn"
                                                            data-alarm-id="{{ $alarm->id }}"
                                                            data-alarm-code="{{ $alarm->code }}">
                                                        <i class="fas fa-play"></i> Test Now
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info update-alarm-btn"
                                                            data-alarm-id="{{ $alarm->id }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#updateAlarmModal">
                                                        <i class="fas fa-edit"></i> Update
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary view-details-btn"
                                                            data-alarm-id="{{ $alarm->id }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#viewAlarmModal">
                                                        <i class="fas fa-eye"></i> Details
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testing Schedule -->
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card dashboard-card">
                            <div class="card-header py-3">
                                <h6 class="m-0 fw-bold text-primary">
                                    <i class="fas fa-calendar-check me-2"></i> Upcoming Tests - {{ $school->school_name }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                @foreach($school->alarmSystems->where('next_test_due', '>=', now())->sortBy('next_test_due')->take(5) as $alarm)
                                @php
                                    $nextTest = \Carbon\Carbon::parse($alarm->next_test_due);
                                    $borderClass = $nextTest->diffInDays(now()) <= 7 ? 'border-warning' : 'border-info';
                                @endphp
                                <div class="col-md-4 mb-3">
                                    <div class="card {{ $borderClass }}">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $alarm->code }}</h6>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">Building: {{ $alarm->building->building_no ?? 'N/A' }}</small>
                                            </p>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">Type: {{ $alarm->alarm_type }}</small>
                                            </p>
                                            <p class="card-text">
                                                <strong>Due: {{ \Carbon\Carbon::parse($alarm->next_test_due)->format('Y-m-d') }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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

    <!-- Add Alarm System Modal -->
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
                    <form id="addAlarmForm" action="{{ route('fire-safety.alarm.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="school_id" id="modalSchoolId">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Alarm Code *</label>
                        <input type="text" class="form-control" name="code" id="alarmCode" placeholder="e.g., ALM-001" required onblur="checkAlarmCode(this.value)">
                        <div class="invalid-feedback" id="codeError">Alarm code already exists</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Building *</label>
                        <select class="form-control" name="building_id" id="buildingSelect" required>
                            <option value="">Select Building</option>
                            <!-- Buildings will be populated by JavaScript -->
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Location in Building *</label>
                        <input type="text" class="form-control" name="location"
                            placeholder="e.g., 1st Floor Hallway, Room 101, Near Main Entrance, etc." required>
                    </div>
                </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alarm Type *</label>
                                <select class="form-control" name="alarm_type" id="alarmTypeSelect" required>
                                    <option value="">Select Type</option>
                                    <option value="Bell">Bell</option>
                                    <option value="Mechanical">Mechanical</option>
                                    <option value="Digital">Digital</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-control" name="status" id="statusSelect" required>
                                    <option value="">Select Status</option>
                                    <!-- Options will be populated based on alarm type -->
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Manufacturer (Optional)</label>
                                <input type="text" class="form-control" name="manufacturer" placeholder="Manufacturer name">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Installation Date</label>
                                <input type="date" class="form-control" name="installation_date" id="installationDate">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Last Test Date</label>
                                <input type="date" class="form-control" name="last_test" id="lastTestDate">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Next Test Due *</label>
                                <input type="date" class="form-control" name="next_test_due" id="nextTestDue" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes/Remarks</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional information..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAlarmSystem()">
                        <i class="fas fa-save me-2"></i> Save Alarm System
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Alarm Modal -->
    <div class="modal fade" id="updateAlarmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i> Update Alarm System
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateAlarmForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="alarm_id" id="updateAlarmId">
                        <input type="hidden" id="updateInstallationDate">
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-control" name="status" id="updateStatusSelect" required>
                                <!-- Options will be populated based on alarm type -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Installation Date</label>
                            <p class="form-control-plaintext" id="displayInstallationDate">Not set</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Test Date</label>
                            <input type="date" class="form-control" name="last_test" id="updateLastTest">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Next Test Due *</label>
                            <input type="date" class="form-control" name="next_test_due" id="updateNextTestDue" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes/Remarks</label>
                            <textarea class="form-control" name="notes" id="updateNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateAlarmSystem()">
                        <i class="fas fa-save me-2"></i> Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Alarm Details Modal -->
    <div class="modal fade" id="viewAlarmModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i> Alarm System Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Alarm Code:</strong> <span id="detailCode"></span></p>
                            <p><strong>Building:</strong> <span id="detailBuilding"></span></p>
                            <p><strong>School:</strong> <span id="detailSchool"></span></p>
                            <p><strong>Alarm Type:</strong> <span id="detailType"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <span id="detailStatus"></span></p>
                            <p><strong>Manufacturer:</strong> <span id="detailManufacturer"></span></p>
                            <p><strong>Installation Date:</strong> <span id="detailInstallation"></span></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Last Test:</strong> <span id="detailLastTest"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Next Test Due:</strong> <span id="detailNextTest"></span></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p><strong>Notes/Remarks:</strong></p>
                        <div class="border rounded p-3" id="detailNotes"></div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        To remove this alarm system, click the "Remove Alarm" button below.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="removeAlarmSystem()" id="removeAlarmBtn">
                        <i class="fas fa-trash me-2"></i> Remove Alarm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Status options based on alarm type
        const statusOptions = {
            'Bell': ['Functional', 'Broken', 'Missing', 'Not Installed'],
            'Mechanical': ['Functional', 'Missing', 'Jammed', 'Under Repair', 'Not Installed'],
            'Digital': ['Online', 'Offline', 'Missing', 'Not Installed', 'System Error', 'Under Maintenance', 'Decommissioned']
        };

        // Store current school and alarm data
        let currentSchoolId = null;
        let currentAlarmId = null;
        let currentAlarmType = null;

        // School tab switching
        document.querySelectorAll('#schoolTab button').forEach(button => {
            button.addEventListener('click', function() {
                currentSchoolId = this.getAttribute('data-school-id');
                console.log('School changed to:', currentSchoolId);
            });
        });

        // Set initial school
        const firstTab = document.querySelector('#schoolTab button.active');
        if (firstTab) {
            currentSchoolId = firstTab.getAttribute('data-school-id');
        }

        // Add Alarm button click
        document.querySelectorAll('.add-alarm-btn').forEach(button => {
            button.addEventListener('click', function() {
                const schoolId = this.getAttribute('data-school-id');
                document.getElementById('modalSchoolId').value = schoolId;

                // Load buildings for this school
                loadBuildings(schoolId);
            });
        });

        // Alarm type change handler for Add modal
        document.getElementById('alarmTypeSelect').addEventListener('change', function() {
            const type = this.value;
            const statusSelect = document.getElementById('statusSelect');

            statusSelect.innerHTML = '<option value="">Select Status</option>';

            if (type && statusOptions[type]) {
                statusOptions[type].forEach(status => {
                    const option = document.createElement('option');
                    option.value = status.toLowerCase().replace(' ', '_');
                    option.textContent = status;
                    statusSelect.appendChild(option);
                });
            }
        });

        // Update Alarm button click - ADD DATE VALIDATION
        document.querySelectorAll('.update-alarm-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const alarmId = this.getAttribute('data-alarm-id');
                currentAlarmId = alarmId;

                try {
                    const response = await fetch(`/fire-safety/alarm/${alarmId}`);
                    const alarm = await response.json();

                    currentAlarmType = alarm.alarm_type;

                    // Populate form
                    document.getElementById('updateAlarmId').value = alarmId;
                    document.getElementById('updateLastTest').value = alarm.last_test || '';
                    document.getElementById('updateInstallationDate').value = alarm.installation_date || '';
                    document.getElementById('displayInstallationDate').textContent = alarm.installation_date ? alarm.installation_date : 'Not set';
                    document.getElementById('updateNextTestDue').value = alarm.next_test_due || '';
                    document.getElementById('updateNotes').value = alarm.notes || '';

                    // Store installation date for validation
                    document.getElementById('updateAlarmId').dataset.installationDate = alarm.installation_date || '';

                    // Populate status options
                    const statusSelect = document.getElementById('updateStatusSelect');
                    statusSelect.innerHTML = '<option value="">Select Status</option>';

                    if (statusOptions[alarm.alarm_type]) {
                        statusOptions[alarm.alarm_type].forEach(status => {
                            const option = document.createElement('option');
                            const statusValue = status.toLowerCase().replace(' ', '_');
                            option.value = statusValue;
                            option.textContent = status;
                            if (alarm.status === statusValue) {
                                option.selected = true;
                            }
                            statusSelect.appendChild(option);
                        });
                    }

                } catch (error) {
                    console.error('Error loading alarm data:', error);
                    alert('Failed to load alarm data');
                }
            });
        });

        // View Details button click
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const alarmId = this.getAttribute('data-alarm-id');
                currentAlarmId = alarmId;

                try {
                    const response = await fetch(`/fire-safety/alarm/${alarmId}`);
                    const alarm = await response.json();

                    // Populate details
                    document.getElementById('detailCode').textContent = alarm.code;
                    document.getElementById('detailBuilding').textContent = alarm.building ? alarm.building.building_no : 'N/A';
                    document.getElementById('detailSchool').textContent = alarm.school.school_name;
                    document.getElementById('detailType').textContent = alarm.alarm_type;

                    // Format status
                    const statusText = alarm.status.replace(/_/g, ' ');
                    document.getElementById('detailStatus').textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);

                    document.getElementById('detailManufacturer').textContent = alarm.manufacturer || 'N/A';
                    document.getElementById('detailInstallation').textContent = alarm.installation_date || 'N/A';
                    document.getElementById('detailLastTest').textContent = alarm.last_test || 'Never';
                    document.getElementById('detailNextTest').textContent = alarm.next_test_due || 'Not set';
                    document.getElementById('detailNotes').textContent = alarm.notes || 'No notes';

                } catch (error) {
                    console.error('Error loading alarm details:', error);
                    alert('Failed to load alarm details');
                }
            });
        });

        // Test Now button click
        document.querySelectorAll('.test-now-btn').forEach(button => {
            button.addEventListener('click', function() {
                const alarmId = this.getAttribute('data-alarm-id');
                const alarmCode = this.getAttribute('data-alarm-code');

                if (confirm(`Test alarm ${alarmCode} now? This will update the last test date to today.`)) {
                    testAlarmSystem(alarmId);
                }
            });
        });

        // Simulate Alarm Button
        document.getElementById('simulateAlarmBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to simulate an alarm test? This will trigger test alerts.')) {
                alert('Alarm test simulation started! Testing all functional/online alarm systems...');
                // In real implementation, this would trigger API call
            }
        });

        // Load buildings for a school
        async function loadBuildings(schoolId) {
            try {
                const response = await fetch(`/fire-safety/buildings/${schoolId}`);
                const buildings = await response.json();

                const select = document.getElementById('buildingSelect');
                select.innerHTML = '<option value="">Select Building</option>';

                buildings.forEach(building => {
                    const option = document.createElement('option');
                    option.value = building.id;
                    option.textContent = building.building_no;
                    select.appendChild(option);
                });

            } catch (error) {
                console.error('Error loading buildings:', error);
                alert('Failed to load buildings. Please check if buildings are added.');
            }
        }
        // Check if alarm code already exists
        async function checkAlarmCode(code) {
            if (!code) return;

            try {
                const response = await fetch(`/fire-safety/check-alarm-code/${encodeURIComponent(code)}`);
                const data = await response.json();

                const codeInput = document.getElementById('alarmCode');
                const errorDiv = document.getElementById('codeError');

                if (data.exists) {
                    codeInput.classList.add('is-invalid');
                    errorDiv.textContent = 'Alarm code already exists. Please use a different code.';
                    return false;
                } else {
                    codeInput.classList.remove('is-invalid');
                    return true;
                }
            } catch (error) {
                console.error('Error checking alarm code:', error);
                return true;
            }
        }

        // Date validation
        function validateDates() {
            const installationDate = document.getElementById('installationDate').value;
            const lastTestDate = document.getElementById('lastTestDate').value;
            const nextTestDue = document.getElementById('nextTestDue').value;

            let isValid = true;

            // Check last test not before installation
            if (installationDate && lastTestDate && lastTestDate < installationDate) {
                alert('Last test date cannot be before installation date.');
                isValid = false;
            }

            // Check next test not before installation
            if (installationDate && nextTestDue && nextTestDue < installationDate) {
                alert('Next test due date cannot be before installation date.');
                isValid = false;
            }

            // Check next test not before last test
            if (lastTestDate && nextTestDue && nextTestDue < lastTestDate) {
                alert('Next test due date cannot be before last test date.');
                isValid = false;
            }

            return isValid;
        }

        // Save Alarm System
        async function saveAlarmSystem() {
            const form = document.getElementById('addAlarmForm');

            // Validate dates
            if (!validateDates()) {
                return;
            }

            // Validate alarm code
            const code = document.getElementById('alarmCode').value;
            const codeValid = await checkAlarmCode(code);
            if (!codeValid) {
                alert('Please fix the alarm code error.');
                return;
            }

            // Check if building exists for this school
            const buildingSelect = document.getElementById('buildingSelect');
            if (buildingSelect.options.length <= 1) { // Only "Select Building" option
                alert('No buildings found for this school. Please add buildings first.');
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Get CSRF token - multiple ways
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                // Try from form
                csrfToken = form.querySelector('input[name="_token"]')?.value;

                if (!csrfToken) {
                    // Try Laravel's default CSRF field
                    csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

                    if (!csrfToken) {
                        console.error('CSRF token not found anywhere');
                        alert('Security token missing. Please refresh the page and try again.');
                        return;
                    }
                }
            }

            console.log('CSRF Token found:', csrfToken ? 'Yes' : 'No');

            const formData = new FormData(form);

            // Log what we're sending for debugging
            console.log('Form action:', form.action);
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                });

                console.log('Response status:', response.status);

                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    alert('Alarm system added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to add alarm system'));
                    if (data.errors) {
                        console.log('Validation errors:', data.errors);
                    }
                }

            } catch (error) {
                console.error('Error details:', error);
                alert('Failed to add alarm system. Check console (F12) for details.');
            }
        }

        // Update Alarm System
        async function updateAlarmSystem() {
            const form = document.getElementById('updateAlarmForm');
            const alarmId = document.getElementById('updateAlarmId').value;

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Get dates
            const lastTest = document.getElementById('updateLastTest').value;
            const nextTestDue = document.getElementById('updateNextTestDue').value;
            const installationDate = document.getElementById('updateAlarmId').dataset.installationDate;

            // Validate dates
            if (installationDate) {
                // Check last test not before installation
                if (lastTest && lastTest < installationDate) {
                    alert('Last test date cannot be before installation date.');
                    return;
                }

                // Check next test not before installation
                if (nextTestDue && nextTestDue < installationDate) {
                    alert('Next test due date cannot be before installation date.');
                    return;
                }
            }

            // Check next test not before last test
            if (lastTest && nextTestDue && nextTestDue < lastTest) {
                alert('Next test due date cannot be before last test date.');
                return;
            }

            const formData = new FormData(form);

            try {
                const response = await fetch(`/fire-safety/alarm/${alarmId}`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Alarm system updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update alarm system'));
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update alarm system');
            }
        }

        // Test Alarm System
        async function testAlarmSystem(alarmId) {
            try {
                const response = await fetch(`/fire-safety/alarm/${alarmId}/test`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Alarm test completed successfully! Last test date updated.');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to test alarm system'));
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Failed to test alarm system');
            }
        }

        // Remove Alarm System
        async function removeAlarmSystem() {
            if (!confirm('Are you sure you want to remove this alarm system? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/fire-safety/alarm/${currentAlarmId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Alarm system removed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to remove alarm system'));
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Failed to remove alarm system');
            }
        }


    </script>
</body>
</html>
