<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evacuation Plans - Fire Safety</title>
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

        .evacuation-card {
            transition: transform 0.2s;
            height: 100%;
        }

        .evacuation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .map-container {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 8px;
            padding: 15px;
            color: white;
            text-align: center;
            margin-bottom: 15px;
        }

        .route-step {
            padding: 8px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
            border-left: 4px solid var(--fire-red);
            border-radius: 4px;
        }

        .assembly-area {
            background-color: #e7f5ff;
            border: 2px dashed #0d6efd;
            border-radius: 8px;
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
                    <h4 class="text-white mb-0">Evacuation Plans Management</h4>
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
                    <a class="nav-link" href="{{ route('fire-safety.buildings') }}">
                        <span class="nav-icon"><i class="fas fa-building"></i></span>
                        <span>Buildings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('fire-safety.evacuation-plans') }}">
                        <span class="nav-icon"><i class="fas fa-map-signs"></i></span>
                        <span>Evacuation Plans</span>
                    </a>
                </li>
            </ul>

            <hr class="bg-white my-4">

            <!-- Quick Stats -->
            <div class="mt-4">
                <h6 class="text-white mb-3">Evacuation Status</h6>
                <div class="text-white mb-2">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <span>Plans Updated: <strong>4</strong></span>
                </div>
                <div class="text-white mb-2">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    <span>Needs Review: <strong>1</strong></span>
                </div>
                <div class="text-white mb-3">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    <span>No Plan: <strong>1</strong></span>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                        <i class="fas fa-plus me-2"></i> Add Plan
                    </button>
                    <button class="btn btn-light btn-sm" onclick="printAllPlans()">
                        <i class="fas fa-print me-2"></i> Print All Plans
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Evacuation Plan Overview -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-success h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                    Active Plans
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">4</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-map-marked-alt fa-2x text-success"></i>
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
                                    Assembly Areas
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">12</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-primary"></i>
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
                                    Last Drill Date
                                </div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Jan 15, 2024</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-alt fa-2x text-warning"></i>
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
                                    Evacuation Routes
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">28</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-route fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evacuation Plans Grid -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-map me-2"></i> Building Evacuation Plans
                        </h6>
                        <div>
                            <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                                <i class="fas fa-plus me-2"></i> Add Plan
                            </button>
                            <button class="btn btn-success btn-sm" onclick="conductDrill()">
                                <i class="fas fa-bullhorn me-2"></i> Schedule Drill
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- San Isidro NHS Plan -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card evacuation-card border-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">San Isidro NHS</h5>
                                                <p class="text-muted mb-0">Main Building</p>
                                            </div>
                                            <span class="status-badge bg-success">Active</span>
                                        </div>

                                        <!-- Map Preview -->
                                        <div class="map-container mb-3">
                                            <i class="fas fa-map fa-3x mb-2"></i>
                                            <h6>Floor Plan Available</h6>
                                            <small>Click to view full map</small>
                                        </div>

                                        <!-- Quick Info -->
                                        <div class="mb-3">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <h6 class="mb-0">4</h6>
                                                    <small>Exits</small>
                                                </div>
                                                <div class="col-4">
                                                    <h6 class="mb-0">2</h6>
                                                    <small>Routes</small>
                                                </div>
                                                <div class="col-4">
                                                    <h6 class="mb-0">3</h6>
                                                    <small>Areas</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Assembly Area -->
                                        <div class="assembly-area">
                                            <small class="d-block fw-bold">Primary Assembly Area:</small>
                                            <small>Front Parking Lot</small>
                                            <small class="d-block mt-1">
                                                <i class="fas fa-users"></i> Capacity: 1200 persons
                                            </small>
                                        </div>

                                        <div class="mt-3 d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewPlan('San Isidro NHS')">
                                                <i class="fas fa-eye me-2"></i> View Plan
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-edit me-2"></i> Edit Routes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Santa Clara NHS Plan -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card evacuation-card border-warning">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">Santa Clara NHS</h5>
                                                <p class="text-muted mb-0">Annex Building</p>
                                            </div>
                                            <span class="status-badge bg-warning">Needs Update</span>
                                        </div>

                                        <!-- Map Preview -->
                                        <div class="map-container mb-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                            <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
                                            <h6>Plan Outdated</h6>
                                            <small>Last updated: 2023-06-15</small>
                                        </div>

                                        <!-- Issues -->
                                        <div class="mb-3">
                                            <div class="alert alert-warning py-2">
                                                <small>
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    Routes blocked by construction
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Assembly Area -->
                                        <div class="assembly-area">
                                            <small class="d-block fw-bold">Primary Assembly Area:</small>
                                            <small>Football Field</small>
                                            <small class="d-block mt-1">
                                                <i class="fas fa-users"></i> Capacity: 800 persons
                                            </small>
                                        </div>

                                        <div class="mt-3 d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-sync me-2"></i> Update Plan
                                            </button>
                                            <button class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-history me-2"></i> View History
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tapinac ES Plan -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card evacuation-card border-danger">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">Tapinac ES</h5>
                                                <p class="text-muted mb-0">Elementary Building</p>
                                            </div>
                                            <span class="status-badge bg-danger">No Plan</span>
                                        </div>

                                        <!-- Map Preview -->
                                        <div class="map-container mb-3" style="background: linear-gradient(135deg, #868f96 0%, #596164 100%);">
                                            <i class="fas fa-times-circle fa-3x mb-2"></i>
                                            <h6>No Evacuation Plan</h6>
                                            <small>Critical safety issue</small>
                                        </div>

                                        <!-- Warning -->
                                        <div class="mb-3">
                                            <div class="alert alert-danger py-2">
                                                <small>
                                                    <i class="fas fa-times-circle me-1"></i>
                                                    No evacuation plan exists for this building
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Action Required -->
                                        <div class="assembly-area" style="background-color: #ffeaea; border-color: #dc3545;">
                                            <small class="d-block fw-bold text-danger">ACTION REQUIRED:</small>
                                            <small>Create evacuation plan immediately</small>
                                        </div>

                                        <div class="mt-3 d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                                                <i class="fas fa-plus-circle me-2"></i> Create Plan
                                            </button>
                                            <button class="btn btn-sm btn-outline-dark">
                                                <i class="fas fa-flag me-2"></i> Report Issue
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

        <!-- Evacuation Routes Details -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card dashboard-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-route me-2"></i> Evacuation Routes - San Isidro NHS
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Primary Route (Green)</h6>
                                <div class="route-step">1. Exit through main doors</div>
                                <div class="route-step">2. Turn left to hallway</div>
                                <div class="route-step">3. Proceed to stairwell A</div>
                                <div class="route-step">4. Exit to front parking lot</div>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Estimated time: 2 minutes
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6>Secondary Route (Red)</h6>
                                <div class="route-step">1. Exit through back doors</div>
                                <div class="route-step">2. Turn right to corridor</div>
                                <div class="route-step">3. Proceed to stairwell B</div>
                                <div class="route-step">4. Exit to sports field</div>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Use if primary route blocked
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-12">
                                <h6>Assembly Areas</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Area</th>
                                                <th>Location</th>
                                                <th>Capacity</th>
                                                <th>Responsible Person</th>
                                                <th>Contact</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Area A</td>
                                                <td>Front Parking Lot</td>
                                                <td>1200</td>
                                                <td>John Doe</td>
                                                <td>0917-123-4567</td>
                                            </tr>
                                            <tr>
                                                <td>Area B</td>
                                                <td>Sports Field</td>
                                                <td>800</td>
                                                <td>Jane Smith</td>
                                                <td>0918-987-6543</td>
                                            </tr>
                                            <tr>
                                                <td>Area C</td>
                                                <td>Gymnasium</td>
                                                <td>500</td>
                                                <td>Mike Johnson</td>
                                                <td>0919-555-1212</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card dashboard-card">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 fw-bold">
                            <i class="fas fa-bullhorn me-2"></i> Drill Schedule
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>Next Scheduled Drill</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-calendar-check me-2"></i>
                                <strong>February 15, 2024</strong><br>
                                <small>10:00 AM - San Isidro NHS</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6>Drill History</h6>
                            <div class="list-group small">
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Jan 15, 2024</span>
                                        <span class="badge bg-success">Completed</span>
                                    </div>
                                    <small class="text-muted">San Isidro NHS - 2.5 min avg</small>
                                </div>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Dec 10, 2023</span>
                                        <span class="badge bg-warning">Delayed</span>
                                    </div>
                                    <small class="text-muted">Santa Clara NHS - 3.1 min avg</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="conductDrill()">
                                <i class="fas fa-play me-2"></i> Start Drill Now
                            </button>
                            <button class="btn btn-outline-primary" onclick="scheduleDrill()">
                                <i class="fas fa-calendar-plus me-2"></i> Schedule New Drill
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Plan Modal -->
    <div class="modal fade" id="addPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i> Create Evacuation Plan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPlanForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building *</label>
                                <select class="form-control" required>
                                    <option value="">Select Building</option>
                                    <option>San Isidro NHS</option>
                                    <option>Santa Clara NHS</option>
                                    <option>Tapinac ES</option>
                                    <option>New Building</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Plan Version *</label>
                                <input type="text" class="form-control" placeholder="e.g., v2.1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Effective Date</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Review Date</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Primary Assembly Area *</label>
                            <input type="text" class="form-control" placeholder="e.g., Front Parking Lot" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Secondary Assembly Area</label>
                            <input type="text" class="form-control" placeholder="e.g., Sports Field">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Emergency Contacts</label>
                            <textarea class="form-control" rows="3" placeholder="List emergency contacts..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Special Instructions</label>
                            <textarea class="form-control" rows="3" placeholder="Any special evacuation instructions..."></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="uploadMap">
                            <label class="form-check-label" for="uploadMap">
                                Upload floor plan map (PDF/Image)
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePlan()">
                        <i class="fas fa-save me-2"></i> Save Plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // View evacuation plan
        function viewPlan(buildingName) {
            alert(`Opening evacuation plan for ${buildingName}`);
            // In real implementation, this would open detailed plan view
        }

        // Save evacuation plan
        function savePlan() {
            const form = document.getElementById('addPlanForm');
            if (form.checkValidity()) {
                alert('Evacuation plan created successfully!');
                $('#addPlanModal').modal('hide');
                form.reset();
            } else {
                form.reportValidity();
            }
        }

        // Conduct drill
        function conductDrill() {
            if (confirm('Start evacuation drill simulation?')) {
                alert('Evacuation drill started! Notifications sent to all personnel.');
                // In real implementation, this would trigger drill mode
            }
        }

        // Schedule drill
        function scheduleDrill() {
            const building = prompt('Enter building for drill:');
            if (building) {
                const date = prompt('Enter drill date (YYYY-MM-DD):');
                const time = prompt('Enter drill time (HH:MM):');
                if (date && time) {
                    alert(`Drill scheduled for ${building} on ${date} at ${time}`);
                }
            }
        }

        // Print all plans
        function printAllPlans() {
            if (confirm('Print all evacuation plans?')) {
                alert('Printing evacuation plans...');
                // In real implementation, this would trigger print dialog
            }
        }

        // Initialize plan cards
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to map containers
            document.querySelectorAll('.map-container').forEach(map => {
                map.addEventListener('click', function() {
                    const building = this.closest('.card').querySelector('.card-title').textContent;
                    alert(`Opening detailed floor plan for ${building}`);
                });
            });

            // Make assembly areas clickable
            document.querySelectorAll('.assembly-area').forEach(area => {
                area.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const location = this.querySelector('small').textContent;
                    alert(`Assembly area: ${location}`);
                });
            });
        });
    </script>
</body>
</html>
