{{-- resources/views/fire-safety/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fire Safety Dashboard - DRRM Compliance</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --fire-red: #A8191F;
            --fire-dark-red: #8A1217;
            --fire-light-red: #F8D7DA;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Top Navigation */
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

        /* Sidebar */
        .sidebar {
            background-color: var(--fire-red);
            width: 250px;
            position: fixed;
            top: 60px; /* Below top nav */
            left: 0;
            bottom: 0;
            z-index: 1020;
            overflow-y: auto;
            transition: all 0.3s;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
            background-color: #f8f9fa;
        }

        /* Sidebar Navigation Items */
        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            margin-bottom: 2px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
        }

        .nav-link.active {
            background-color: var(--fire-dark-red);
            color: white;
            border-left: 4px solid white;
        }

        .nav-icon {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }

        /* Quick Actions in Sidebar */
        .quick-actions {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 0 20px;
        }

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

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                margin-left: 0;
            }
        }

        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="top-nav">
        <div class="container-fluid h-100">
            <div class="row h-100 align-items-center">
                <!-- Left: Logo and Back Button -->
                <div class="col-auto">
                    <a href="{{ route('dashboard') }}" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        <i class="fas fa-fire me-2"></i>
                        <span class="fw-bold">Fire Safety Checklist System</span>
                    </a>
                </div>

                <!-- Center: Title -->
                <div class="col text-center">
                    <h4 class="text-white mb-0">Dashboard</h4>
                </div>

                <!-- Right: User Menu and Notifications -->
                <div class="col-auto">
                    <div class="d-flex align-items-center">
                        <!-- Notifications -->
                        <div class="dropdown me-3">
                            <a href="#" class="text-white position-relative" data-bs-toggle="dropdown">
                                <i class="fas fa-bell fa-lg"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    0
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">Notifications</h6>
                                <div class="dropdown-item text-muted">No new notifications</div>
                            </div>
                        </div>

                        <!-- User Profile -->
                        <div class="dropdown">
                            <a href="#" class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle fa-lg me-2"></i>
                                <span>{{ Auth::user()->name }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user-cog me-2"></i> Profile Settings
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
        <!-- Sidebar Navigation -->
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('fire-safety.dashboard') }}">
                        <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                        <span>Dashboard</span>
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
                    <a class="nav-link" href="{{ route('fire-safety.buildings') }}">
                        <span class="nav-icon"><i class="fas fa-building"></i></span>
                        <span>Buildings</span>
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
        </div>

        <!-- Quick Actions at Bottom of Sidebar -->
        <div class="quick-actions">
            <h6 class="text-white mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <button class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-2"></i> Add Inspection
                </button>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#alarmInfoModal">
                    <i class="fas fa-bell me-2"></i> Simulate Alarm
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-success h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                    Passed Inspections
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">0</div>
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
                                    Failed Inspections
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">0</div>
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
                                    Extinguishers Needing Action
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-fire-extinguisher fa-2x text-warning"></i>
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
                                    Alarm System Status
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">0 Offline</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bell fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- School Safety Status Section -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card dashboard-card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">School Safety Status</h6>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="schoolTab">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all">
                                    All Schools
                                </button>
                            </li>
                            @foreach($schools as $school)
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#school-{{ $school->id }}">
                                    {{ $school->name }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                        <div class="tab-content mt-3">
                            <!-- All Schools Tab -->
                            <div class="tab-pane fade show active" id="all">
                                <div class="row">
                                    <div class="col-lg-8">
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
                                                <tbody>
                                                    @forelse($schools as $school)
                                                        <tr>
                                                            <td>{{ $school->school_name }}</td>
                                                                <td>
                                                                    @if($school->status === 'passed')
                                                                        <span class="status-badge bg-success">PASSED</span>
                                                                    @elseif($school->status === 'failed')
                                                                        <span class="status-badge bg-danger">FAILED</span>
                                                                    @elseif($school->status === 'unconfigured')
                                                                        <span class="status-badge bg-warning">UNCONFIGURED</span>
                                                                    @else
                                                                        <span class="status-badge bg-warning">WARNING</span>
                                                                    @endif
                                                                </td>
                                                            <td>
                                                                @if($school->status === 'unconfigured')
                                                                    Setup Needed
                                                                @elseif ($school->issues_count > 0)
                                                                    {{$school->issues_count}} issues found
                                                                @else
                                                                    None
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($school->last_inspection_date && $school->last_inspection_date !== 'Never')
                                                                    {{ \Carbon\Carbon::parse($school->last_inspection_date)->format('Y-m-d') }}
                                                                @else
                                                                    Never
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($school->status === 'passed')
                                                                    <button class="btn btn-sm btn-outline-primary view-school-btn"
                                                                            data-school-id="{{ $school->id }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#viewSchoolModal">
                                                                        <i class="fas fa-eye"></i> View
                                                                    </button>
                                                                @else
                                                                    <button class="btn btn-sm btn-outline-warning details-btn"
                                                                            data-school-id="{{ $school->id }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#issuesModal">
                                                                        <i class="fas fa-info-circle"></i> Details
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted py-4">
                                                                No schools found. Click "Add Inspection" to add a school.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                                                            <!-- Bottom Action Bar -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="card dashboard-card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-center gap-3">
                                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInspectionModal">
                                                            <i class="fas fa-plus me-2"></i> Add Inspection
                                                        </button>
                                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#alarmInfoModal">
                                                            <i class="fas fa-bell me-2"></i> Simulate Alarm
                                                        </button>
                                                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#evacInfoModal">
                                                            <i class="fas fa-map-signs me-2"></i> View Evacuation Plans
                                                        </button>
                                                        <button class="btn btn-success">
                                                            <i class="fas fa-file-pdf me-2"></i> Generate Report
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <!-- Alerts for All Schools -->
                                        <div class="card dashboard-card mb-4">
                                            <div class="card-header py-3 bg-danger text-white">
                                                <h6 class="m-0 fw-bold">
                                                    <i class="fas fa-exclamation-circle me-2"></i> Alerts - All Schools
                                                </h6>
                                            </div>
                                            <div class="card-body" id="allAlerts">
                                                <div class="text-center text-muted py-3">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Select a school to see alerts
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Events for All Schools -->
                                        <div class="card dashboard-card">
                                            <div class="card-header py-3 bg-primary text-white">
                                                <h6 class="m-0 fw-bold">
                                                    <i class="fas fa-calendar-alt me-2"></i> Events - All Schools
                                                </h6>
                                            </div>
                                            <div class="card-body" id="allEvents">
                                                <div class="text-center text-muted py-3">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Select a school to see events
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Individual School Tabs -->
                                @foreach($schools as $school)
                                <div class="tab-pane fade" id="school-{{ $school->id }}">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <!-- School-specific data will go here -->
                                        </div>
                                        <div class="col-lg-4">
                                            <!-- Dynamic alerts/events -->
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- <- School Safety Status Section -->



        <!-- View School Modal (for PASSED status) -->
        <div class="modal fade" id="viewSchoolModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="schoolNameTitle">School Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- School Information -->
                        <div class="school-info mb-4">
                            <h6 class="border-bottom pb-2">School Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>School Name:</strong> <span id="modalSchoolName"></span></p>
                                    <p><strong>School ID:</strong> <span id="modalSchoolId"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>School Head:</strong> <span id="modalSchoolHead"></span></p>
                                    <p><strong>DRRM Coordinator:</strong> <span id="modalDrrmCoordinator"></span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Equipment Summary -->
                        <div class="equipment-summary">
                            <h6 class="border-bottom pb-2">Equipment Summary</h6>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 id="fireExtinguishersCount">0</h5>
                                            <p class="mb-0">Fire Extinguishers</p>
                                            <button class="btn btn-sm btn-link view-equipment"
                                                    data-type="extinguishers">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 id="alarmSystemsCount">0</h5>
                                            <p class="mb-0">Alarm Systems</p>
                                            <button class="btn btn-sm btn-link view-equipment"
                                                    data-type="alarm-systems">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 id="evacuationPlansCount">0</h5>
                                            <p class="mb-0">Evacuation Plans</p>
                                            <button class="btn btn-sm btn-link view-equipment"
                                                    data-type="evacuation-plans">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 id="buildingsCount">0</h5>
                                            <p class="mb-0">Buildings</p>
                                            <button class="btn btn-sm btn-link view-equipment"
                                                    data-type="buildings">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Issues Modal (for FAILED/WARNING status) -->
        <div class="modal fade" id="issuesModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Issues Found</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6 id="issuesSchoolName" class="mb-3"></h6>
                        <div id="issuesList">
                            <!-- Issues will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Add Inspection Modal -->
        <div class="modal fade" id="addInspectionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New School Inspection</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="addSchoolForm" action="{{ route('fire-safety.school.store') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>School Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label>School ID *</label>
                                <input type="text" class="form-control" name="school_id" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>School Head *</label>
                                    <input type="text" class="form-control" name="school_head" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>DRRM Coordinator *</label>
                                    <input type="text" class="form-control" name="drrm_coordinator" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Add School</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Alarm Info Modal -->
        <div class="modal fade" id="alarmInfoModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                        <h5>Alarm Systems</h5>
                        <p>View and manage all fire alarm systems, test schedules, and maintenance records.</p>
                        <a href="{{ route('fire-safety.alarm-systems') }}" class="btn btn-warning">Go to Alarm Systems</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evacuation Plans Info Modal -->
        <div class="modal fade" id="evacInfoModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <i class="fas fa-map-signs fa-3x text-info mb-3"></i>
                        <h5>Evacuation Plans</h5>
                        <p>View evacuation routes, assembly points, and emergency procedures.</p>
                        <a href="{{ route('fire-safety.evacuation-plans') }}" class="btn btn-info">View All Plans</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching - load school-specific alerts/events
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            const schoolSlug = target.replace('#', '');

            // Remove active from all
            tabButtons.forEach(btn => btn.classList.remove('active'));
            // Add active to clicked
            this.classList.add('active');

            // Load school data if needed
            if (schoolSlug !== 'all') {
                // You can load specific school data here
                console.log(`Loading data for ${schoolSlug}`);
            }
        });
    });

    // View School Modal Handler
    document.querySelectorAll('.view-school-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const schoolId = this.getAttribute('data-school-id');
            loadSchoolDetails(schoolId);
        });
    });

    // Issues Modal Handler
    document.querySelectorAll('.details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const schoolId = this.getAttribute('data-school-id');
            loadSchoolIssues(schoolId);
        });
    });

    // Equipment View Buttons (in modal)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-equipment')) {
            const type = e.target.getAttribute('data-type');
            window.location.href = `/fire-safety/${type}`;
        }
    });

    function loadSchoolDetails(schoolId) {
        fetch(`/fire-safety/school/${schoolId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalSchoolName').textContent = data.name;
                document.getElementById('modalSchoolId').textContent = data.school_id;
                document.getElementById('modalSchoolHead').textContent = data.school_head;
                document.getElementById('modalDrrmCoordinator').textContent = data.drrm_coordinator;
                document.getElementById('fireExtinguishersCount').textContent = data.fire_extinguishers_count;
                document.getElementById('alarmSystemsCount').textContent = data.alarm_systems_count;
                document.getElementById('evacuationPlansCount').textContent = data.evacuation_plans_count;
                document.getElementById('buildingsCount').textContent = data.buildings_count;

                // Update modal title
                document.getElementById('schoolNameTitle').textContent = `${data.name} Details`;
            })
            .catch(error => {
                console.error('Error loading school details:', error);
                alert('Failed to load school details. Please try again.');
            });
    }

function loadSchoolIssues(schoolId) {
    fetch(`/fire-safety/school/${schoolId}/issues`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('issuesSchoolName').textContent = data.school_name;

            let issuesHtml = '';
            if(data.issues.length === 0) {
                issuesHtml = '<div class="alert alert-success">No issues found!</div>';
            } else {
                data.issues.forEach(issue => {
                    const alertClass = issue.type === 'danger' ? 'alert-danger' : 'alert-warning';

                    if (issue.link) {
                        // Clickable issue with link
                        issuesHtml += `
                            <a href="${issue.link}" class="alert ${alertClass} d-block text-decoration-none" onclick="event.preventDefault(); window.location.href='${issue.link}'">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>${issue.title}</strong><br>
                                <small>${issue.description}</small>
                                <div class="text-end mt-2">
                                    <span class="badge bg-dark"><i class="fas fa-external-link-alt me-1"></i> Configure</span>
                                </div>
                            </a>`;
                    } else {
                        // Non-clickable issue
                        issuesHtml += `
                            <div class="alert ${alertClass}">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>${issue.title}</strong><br>
                                <small>${issue.description}</small>
                            </div>`;
                    }
                });
            }
            document.getElementById('issuesList').innerHTML = issuesHtml;
        })
        .catch(error => {
            console.error('Error loading school issues:', error);
            // Show a more user-friendly message
            document.getElementById('issuesList').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    School configuration setup needed. Please visit each section to configure:
                    <div class="mt-2">
                        <a href="/fire-safety/alarm-systems" class="btn btn-sm btn-warning me-2">Alarm Systems</a>
                        <a href="/fire-safety/extinguishers" class="btn btn-sm btn-danger me-2">Fire Extinguishers</a>
                        <a href="/fire-safety/buildings" class="btn btn-sm btn-primary me-2">Buildings</a>
                        <a href="/fire-safety/evacuation-plans" class="btn btn-sm btn-success">Evacuation Plans</a>
                    </div>
                </div>`;
        });
}

    // Initialize with some data
    const firstTab = document.querySelector('[data-bs-target="#all"]');
    if (firstTab) {
        firstTab.click();
    }
    // Add School Form Submission
    const addSchoolForm = document.getElementById('addSchoolForm');
    if (addSchoolForm) {
        addSchoolForm.addEventListener('submit', function(e) {
            e.preventDefault();

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    name: this.name.value,
                    school_id: this.school_id.value,
                    school_head: this.school_head.value,
                    drrm_coordinator: this.drrm_coordinator.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('School added successfully!');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addInspectionModal'));
                    modal.hide();
                    // Reload page
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add school. Please try again.');
            });
        });
    }
});

</script>
</body>
</html>
