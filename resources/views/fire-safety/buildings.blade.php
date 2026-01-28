<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buildings - Fire Safety</title>
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

        .building-stats {
            font-size: 0.9rem;
        }

        .floor-plan {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="top-nav">
        <div class="container-fluid h-100">
            <div class="row h-100 align-items-center">
                <div class="col-auto">
                    <a href="{{ route('dashboard') }}" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        <i class="fas fa-fire me-2"></i>
                        <span class="fw-bold">Fire Safety Checklist System</span>
                    </a>
                </div>

                <div class="col text-center">
                    <h4 class="text-white mb-0">Building Safety Management</h4>
                </div>

                <div class="col-auto">
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <a href="#" class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle fa-lg me-2"></i>
                                <span>{{ Auth::user()->name }}</span>
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
                    <a class="nav-link active" href="{{ route('fire-safety.buildings') }}">
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
            </ul>

            <hr class="bg-white my-4">

            <!-- Quick Stats -->
            <div class="mt-4">
                <h6 class="text-white mb-3">Building Safety Overview</h6>
                <div class="text-white mb-2">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <span>Compliant: <strong>3</strong></span>
                </div>
                <div class="text-white mb-2">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    <span>Needs Attention: <strong>2</strong></span>
                </div>
                <div class="text-white mb-3">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    <span>Non-Compliant: <strong>1</strong></span>
                </div>

                <div class="d-grid gap-2">
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
                                <div class="h2 mb-0 fw-bold text-gray-800">6</div>
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
                                <div class="h2 mb-0 fw-bold text-gray-800">24</div>
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
                                    Pending Inspections
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">3</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-warning"></i>
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
                                    Overall Compliance
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">83%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buildings Grid -->
        <div class="row">
            <!-- Building Cards -->
            <div class="col-12 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-building me-2"></i> Building List
                        </h6>
                        <div>
                            <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                                <i class="fas fa-plus me-2"></i> Add Building
                            </button>
                            <button class="btn btn-success btn-sm" onclick="scheduleInspection()">
                                <i class="fas fa-calendar-plus me-2"></i> Schedule Inspection
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- San Isidro NHS -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card building-card border-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">San Isidro NHS</h5>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i> Main Campus
                                                </p>
                                            </div>
                                            <span class="status-badge bg-success">Compliant</span>
                                        </div>

                                        <div class="building-stats mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Floors: <strong>4</strong></span>
                                                <span>Rooms: <strong>32</strong></span>
                                                <span>Capacity: <strong>1200</strong></span>
                                            </div>
                                        </div>

                                        <!-- Compliance Meter -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <small>Safety Compliance</small>
                                                <small><strong>95%</strong></small>
                                            </div>
                                            <div class="compliance-meter">
                                                <div class="compliance-fill bg-success" style="width: 95%;"></div>
                                            </div>
                                        </div>

                                        <!-- Equipment Summary -->
                                        <div class="floor-plan">
                                            <small class="d-block mb-2">
                                                <i class="fas fa-bell text-info me-1"></i> Alarms: 8
                                            </small>
                                            <small class="d-block mb-2">
                                                <i class="fas fa-fire-extinguisher text-danger me-1"></i> Extinguishers: 12
                                            </small>
                                            <small class="d-block">
                                                <i class="fas fa-door-open text-warning me-1"></i> Exits: 6
                                            </small>
                                        </div>

                                        <div class="mt-3 d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewBuildingDetails('San Isidro NHS')">
                                                <i class="fas fa-eye me-2"></i> View Details
                                            </button>
                                            <button class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-clipboard-check me-2"></i> Inspect Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Santa Clara NHS -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card building-card border-warning">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">Santa Clara NHS</h5>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i> Annex Building
                                                </p>
                                            </div>
                                            <span class="status-badge bg-warning">Needs Attention</span>
                                        </div>

                                        <div class="building-stats mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Floors: <strong>3</strong></span>
                                                <span>Rooms: <strong>24</strong></span>
                                                <span>Capacity: <strong>800</strong></span>
                                            </div>
                                        </div>

                                        <!-- Compliance Meter -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <small>Safety Compliance</small>
                                                <small><strong>65%</strong></small>
                                            </div>
                                            <div class="compliance-meter">
                                                <div class="compliance-fill bg-warning" style="width: 65%;"></div>
                                            </div>
                                        </div>

                                        <!-- Issues List -->
                                        <div class="floor-plan">
                                            <small class="d-block mb-2 text-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i> 2 Alarm systems offline
                                            </small>
                                            <small class="d-block mb-2 text-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i> 1 Extinguisher expired
                                            </small>
                                            <small class="d-block text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Exit sign missing
                                            </small>
                                        </div>

                                        <div class="mt-3 d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i> Fix Issues
                                            </button>
                                            <button class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-history me-2"></i> View History
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tapinac ES -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card building-card border-danger">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">Tapinac ES</h5>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i> Elementary Building
                                                </p>
                                            </div>
                                            <span class="status-badge bg-danger">Non-Compliant</span>
                                        </div>

                                        <div class="building-stats mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Floors: <strong>2</strong></span>
                                                <span>Rooms: <strong>16</strong></span>
                                                <span>Capacity: <strong>500</strong></span>
                                            </div>
                                        </div>

                                        <!-- Compliance Meter -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <small>Safety Compliance</small>
                                                <small><strong>40%</strong></small>
                                            </div>
                                            <div class="compliance-meter">
                                                <div class="compliance-fill bg-danger" style="width: 40%;"></div>
                                            </div>
                                        </div>

                                        <!-- Critical Issues -->
                                        <div class="floor-plan">
                                            <small class="d-block mb-2 text-danger">
                                                <i class="fas fa-times-circle me-1"></i> No fire alarm system
                                            </small>
                                            <small class="d-block mb-2 text-danger">
                                                <i class="fas fa-times-circle me-1"></i> Insufficient extinguishers
                                            </small>
                                            <small class="d-block text-danger">
                                                <i class="fas fa-times-circle me-1"></i> Blocked emergency exits
                                            </small>
                                        </div>

                                        <div class="mt-3 d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-wrench me-2"></i> Urgent Repair
                                            </button>
                                            <button class="btn btn-sm btn-outline-dark">
                                                <i class="fas fa-file-alt me-2"></i> Generate Report
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

        <!-- Building Inspection Schedule -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card dashboard-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-calendar-alt me-2"></i> Upcoming Inspections
                        </h6>
                    </div>
                    <div class="card-body">
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
                                    <tr>
                                        <td>2024-02-10</td>
                                        <td>San Isidro NHS</td>
                                        <td>Quarterly Safety Audit</td>
                                        <td>John Doe</td>
                                        <td><span class="badge bg-info">Scheduled</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Reschedule
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2024-02-15</td>
                                        <td>Santa Clara NHS</td>
                                        <td>Fire Drill</td>
                                        <td>Jane Smith</td>
                                        <td><span class="badge bg-warning">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="table-danger">
                                        <td>2024-01-30</td>
                                        <td>Tapinac ES</td>
                                        <td>Emergency Inspection</td>
                                        <td>Mike Johnson</td>
                                        <td><span class="badge bg-danger">Overdue</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Mark Urgent
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card dashboard-card">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 fw-bold">
                            <i class="fas fa-chart-pie me-2"></i> Compliance Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <h3>83% Overall</h3>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: 83%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row text-center">
                            <div class="col-4">
                                <h5 class="text-success">3</h5>
                                <small>Compliant</small>
                            </div>
                            <div class="col-4">
                                <h5 class="text-warning">2</h5>
                                <small>Needs Attention</small>
                            </div>
                            <div class="col-4">
                                <h5 class="text-danger">1</h5>
                                <small>Non-Compliant</small>
                            </div>
                        </div>

                        <hr>

                        <div class="mt-3">
                            <h6>Top Priorities:</h6>
                            <ol class="small">
                                <li>Install fire alarms in Tapinac ES</li>
                                <li>Fix offline alarms in Santa Clara NHS</li>
                                <li>Clear blocked exits in all buildings</li>
                            </ol>
                        </div>
                    </div>
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
                    <form id="addBuildingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building Name *</label>
                                <input type="text" class="form-control" placeholder="e.g., San Isidro NHS" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building Code *</label>
                                <input type="text" class="form-control" placeholder="e.g., SINHS-MAIN" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address *</label>
                                <input type="text" class="form-control" placeholder="Full address" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building Type *</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option>School Building</option>
                                    <option>Gymnasium</option>
                                    <option>Library</option>
                                    <option>Administration</option>
                                    <option>Cafeteria</option>
                                    <option>Dormitory</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Number of Floors *</label>
                                <input type="number" class="form-control" min="1" max="50" value="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Total Rooms</label>
                                <input type="number" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max Capacity</label>
                                <input type="number" class="form-control" placeholder="Maximum occupants">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year Built</label>
                                <input type="number" class="form-control" placeholder="e.g., 1990">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Renovation</label>
                                <input type="number" class="form-control" placeholder="e.g., 2020">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Building Description</label>
                            <textarea class="form-control" rows="3" placeholder="Describe the building features..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Safety Features (Check all that apply)</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="fireAlarms">
                                        <label class="form-check-label" for="fireAlarms">Fire Alarm System</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="sprinklers">
                                        <label class="form-check-label" for="sprinklers">Sprinkler System</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="emergencyLights">
                                        <label class="form-check-label" for="emergencyLights">Emergency Lighting</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="fireExtinguishers">
                                        <label class="form-check-label" for="fireExtinguishers">Fire Extinguishers</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="exitSigns">
                                        <label class="form-check-label" for="exitSigns">Exit Signs</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="fireDoors">
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // View building details
        function viewBuildingDetails(buildingName) {
            alert(`Viewing details for ${buildingName}`);
            // In real implementation, this would navigate to building detail page
        }

        // Save building
        function saveBuilding() {
            const form = document.getElementById('addBuildingForm');
            if (form.checkValidity()) {
                alert('Building added successfully!');
                $('#addBuildingModal').modal('hide');
                form.reset();
            } else {
                form.reportValidity();
            }
        }

        // Schedule inspection
        function scheduleInspection() {
            const buildingSelect = prompt('Enter building name for inspection:');
            if (buildingSelect) {
                const date = prompt('Enter inspection date (YYYY-MM-DD):');
                if (date) {
                    alert(`Inspection scheduled for ${buildingSelect} on ${date}`);
                }
            }
        }

        // Generate building report
        function generateBuildingReport() {
            if (confirm('Generate comprehensive building safety report?')) {
                alert('Building safety report generation started...');
                // In real implementation, this would generate PDF report
            }
        }

        // Initialize building cards interaction
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to building cards
            const buildingCards = document.querySelectorAll('.building-card');
            buildingCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.cursor = 'pointer';
                });
            });

            // Add click handlers to status badges
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const status = this.textContent;
                    const building = this.closest('.building-card').querySelector('.card-title').textContent;
                    alert(`Filtering by ${status} status for ${building}`);
                });
            });
        });
    </script>
</body>
</html>
