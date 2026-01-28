<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alarm Systems - Fire Safety</title>
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
            font-size: 1.2rem;
            font-weight: bold;
        }

        .alarm-online {
            color: #28a745;
        }

        .alarm-offline {
            color: #dc3545;
        }

        .test-btn {
            width: 120px;
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
                        <i class="fas fa-bell me-2"></i>
                        <span class="fw-bold">Alarm Systems</span>
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
        <!-- System Overview Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-success h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                    Online Systems
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">8</div>
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
                                    Offline Systems
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">2</div>
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
                                <div class="h2 mb-0 fw-bold text-gray-800">3</div>
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
                                <div class="h5 mb-0 fw-bold text-gray-800">2024-01-15</div>
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
                            <i class="fas fa-list me-2"></i> Alarm Systems List
                        </h6>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAlarmModal">
                            <i class="fas fa-plus me-2"></i> Add New System
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Last Test</th>
                                        <th>Next Test Due</th>
                                        <th>Battery Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>ALM-001</td>
                                        <td>San Isidro NHS - Main Building</td>
                                        <td>Smoke Detector</td>
                                        <td>
                                            <span class="alarm-status alarm-online">
                                                <i class="fas fa-circle"></i> Online
                                            </span>
                                        </td>
                                        <td>2024-01-15</td>
                                        <td>2024-02-15</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-success" style="width: 85%"></div>
                                            </div>
                                            <small>85%</small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary test-btn">
                                                <i class="fas fa-play"></i> Test Now
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ALM-002</td>
                                        <td>Santa Clara NHS - Gym</td>
                                        <td>Heat Detector</td>
                                        <td>
                                            <span class="alarm-status alarm-offline">
                                                <i class="fas fa-circle"></i> Offline
                                            </span>
                                        </td>
                                        <td>2024-01-10</td>
                                        <td>2024-02-10</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-danger" style="width: 15%"></div>
                                            </div>
                                            <small>15%</small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-wrench"></i> Repair
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ALM-003</td>
                                        <td>Tapinac ES - Classroom 1</td>
                                        <td>Manual Call Point</td>
                                        <td>
                                            <span class="alarm-status alarm-online">
                                                <i class="fas fa-circle"></i> Online
                                            </span>
                                        </td>
                                        <td>2024-01-12</td>
                                        <td>2024-02-12</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-warning" style="width: 45%"></div>
                                            </div>
                                            <small>45%</small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary test-btn">
                                                <i class="fas fa-play"></i> Test Now
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ALM-004</td>
                                        <td>San Isidro NHS - Library</td>
                                        <td>Smoke Detector</td>
                                        <td>
                                            <span class="alarm-status alarm-online">
                                                <i class="fas fa-circle"></i> Online
                                            </span>
                                        </td>
                                        <td>2024-01-18</td>
                                        <td>2024-02-18</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-success" style="width: 92%"></div>
                                            </div>
                                            <small>92%</small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary test-btn">
                                                <i class="fas fa-play"></i> Test Now
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testing Schedule -->
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card dashboard-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-calendar-check me-2"></i> Upcoming Tests
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">San Isidro NHS - Main Building</h6>
                                    <small class="text-muted">ALM-001 - Smoke Detector</small>
                                </div>
                                <span class="badge bg-warning">Due: Feb 15</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Santa Clara NHS - Gym</h6>
                                    <small class="text-muted">ALM-002 - Heat Detector (OFFLINE)</small>
                                </div>
                                <span class="badge bg-danger">Urgent</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Tapinac ES - Classroom 1</h6>
                                    <small class="text-muted">ALM-003 - Manual Call Point</small>
                                </div>
                                <span class="badge bg-info">Due: Feb 12</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card dashboard-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-chart-bar me-2"></i> System Health
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div class="mb-3">
                                <h3>80% Operational</h3>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: 80%">8/10 Systems</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="p-3">
                                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                        <h5>8</h5>
                                        <p class="text-muted mb-0">Online</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3">
                                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                        <h5>2</h5>
                                        <p class="text-muted mb-0">Offline</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                    <form id="addAlarmForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alarm ID *</label>
                                <input type="text" class="form-control" placeholder="e.g., ALM-001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location *</label>
                                <select class="form-control" required>
                                    <option value="">Select Location</option>
                                    <option>San Isidro NHS - Main Building</option>
                                    <option>Santa Clara NHS - Gym</option>
                                    <option>Tapinac ES - Classroom 1</option>
                                    <option>San Isidro NHS - Library</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alarm Type *</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option>Smoke Detector</option>
                                    <option>Heat Detector</option>
                                    <option>Manual Call Point</option>
                                    <option>Fire Alarm Panel</option>
                                    <option>Sprinkler System</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" class="form-control" placeholder="Manufacturer name">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Installation Date</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Next Test Due</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes/Remarks</label>
                            <textarea class="form-control" rows="3" placeholder="Additional information..."></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="markOnline">
                            <label class="form-check-label" for="markOnline">
                                Mark as Online/Operational
                            </label>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Simulate Alarm Button
        document.getElementById('simulateAlarmBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to simulate an alarm test? This will trigger test alerts.')) {
                alert('Alarm test simulation started!');
                // In real implementation, this would trigger API call
            }
        });

        // Test Alarm buttons
        document.querySelectorAll('.test-btn').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const alarmId = row.cells[0].textContent;
                const location = row.cells[1].textContent;

                if (confirm(`Test alarm ${alarmId} at ${location}?`)) {
                    alert(`Testing ${alarmId}... Test results will be recorded.`);
                    // In real implementation, this would trigger actual test
                }
            });
        });

        // Save Alarm System
        function saveAlarmSystem() {
            const form = document.getElementById('addAlarmForm');
            if (form.checkValidity()) {
                alert('Alarm system added successfully!');
                $('#addAlarmModal').modal('hide');
                form.reset();
            } else {
                form.reportValidity();
            }
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
