<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fire Extinguishers - Fire Safety</title>
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

        .extinguisher-card {
            transition: transform 0.2s;
        }

        .extinguisher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .gauge {
            width: 100px;
            height: 100px;
            position: relative;
            margin: 0 auto;
        }

        .gauge-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
        }

        .gauge-fill {
            position: absolute;
            height: 50%;
            width: 100%;
            bottom: 0;
            transition: height 0.5s;
        }

        .gauge-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            font-size: 1.2rem;
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
                    <h4 class="text-white mb-0">Fire Extinguishers Management</h4>
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
                    <a class="nav-link" href="{{ route('fire-safety.buildings') }}">
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
                    <a class="nav-link active" href="{{ route('fire-safety.extinguishers') }}">
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

            <!-- Status Overview -->
            <div class="mt-4">
                <h6 class="text-white mb-3">Status Overview</h6>
                <div class="text-white mb-2">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <span>Passed: <strong>18</strong></span>
                </div>
                <div class="text-white mb-2">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    <span>Failed: <strong>4</strong></span>
                </div>
                <div class="text-white mb-3">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    <span>Refill Needed: <strong>3</strong></span>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addExtinguisherModal">
                        <i class="fas fa-plus me-2"></i> Add Inspection
                    </button>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#refillModal">
                        <i class="fas fa-gas-pump me-2"></i> Request Refill
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-success h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                    Operational
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">42</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
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
                                    Needs Refill
                                </div>
                                <div class="h2 mb-0 fw-bold text-gray-800">3</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
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
                                    Expired
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
                <div class="card dashboard-card border-left-info h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                    Next Inspection Due
                                </div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Feb 28, 2024</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-alt fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fire Extinguishers Grid -->
        <div class="row">
            <!-- Extinguisher Cards -->
            <div class="col-12 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-fire-extinguisher me-2"></i> Fire Extinguishers by Building
                        </h6>
                        <div>
                            <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addExtinguisherModal">
                                <i class="fas fa-plus me-2"></i> Add New
                            </button>
                            <button class="btn btn-success btn-sm" onclick="generateReport()">
                                <i class="fas fa-file-pdf me-2"></i> Generate Report
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Tabs -->
                        <ul class="nav nav-tabs mb-4" id="buildingTab">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all-buildings">
                                    All Buildings
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#san-isidro">
                                    San Isidro NHS
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#santa-clara">
                                    Santa Clara NHS
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tapinac">
                                    Tapinac ES
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="all-buildings">
                                <div class="row">
                                    <!-- Extinguisher Card Template -->
                                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                        <div class="card extinguisher-card border-success">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-fire-extinguisher fa-3x text-success"></i>
                                                </div>
                                                <h5 class="card-title">EXT-001</h5>
                                                <p class="card-text text-muted">
                                                    <i class="fas fa-building me-1"></i> San Isidro NHS<br>
                                                    <i class="fas fa-map-marker-alt me-1"></i> Main Entrance
                                                </p>

                                                <!-- Pressure Gauge -->
                                                <div class="gauge mb-3">
                                                    <div class="gauge-circle border border-3 border-success">
                                                        <div class="gauge-fill bg-success" style="height: 85%;"></div>
                                                    </div>
                                                    <div class="gauge-text">85%</div>
                                                </div>

                                                <div class="mb-3">
                                                    <span class="badge bg-success">Operational</span>
                                                    <span class="badge bg-info">ABC Type</span>
                                                </div>

                                                <div class="small text-muted mb-3">
                                                    Last Inspected: Jan 15, 2024<br>
                                                    Expires: Dec 31, 2024
                                                </div>

                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="inspectExtinguisher('EXT-001')">
                                                        <i class="fas fa-clipboard-check"></i> Inspect
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="refillExtinguisher('EXT-001')">
                                                        <i class="fas fa-gas-pump"></i> Refill Request
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- More extinguisher cards would go here -->
                                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                        <div class="card extinguisher-card border-warning">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-fire-extinguisher fa-3x text-warning"></i>
                                                </div>
                                                <h5 class="card-title">EXT-002</h5>
                                                <p class="card-text text-muted">
                                                    <i class="fas fa-building me-1"></i> Santa Clara NHS<br>
                                                    <i class="fas fa-map-marker-alt me-1"></i> Science Lab
                                                </p>

                                                <div class="gauge mb-3">
                                                    <div class="gauge-circle border border-3 border-warning">
                                                        <div class="gauge-fill bg-warning" style="height: 30%;"></div>
                                                    </div>
                                                    <div class="gauge-text">30%</div>
                                                </div>

                                                <div class="mb-3">
                                                    <span class="badge bg-warning">Low Pressure</span>
                                                    <span class="badge bg-info">CO2 Type</span>
                                                </div>

                                                <div class="small text-muted mb-3">
                                                    Last Inspected: Jan 10, 2024<br>
                                                    <span class="text-danger">Expires: Mar 15, 2024</span>
                                                </div>

                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Urgent Action
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add more cards as needed -->
                                </div>
                            </div>
                            <!-- Other tab panes would have similar content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inspection Records -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-history me-2"></i> Recent Inspections
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Extinguisher ID</th>
                                        <th>Location</th>
                                        <th>Inspector</th>
                                        <th>Pressure</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2024-01-15</td>
                                        <td>EXT-001</td>
                                        <td>San Isidro NHS - Main Entrance</td>
                                        <td>John Doe</td>
                                        <td>85%</td>
                                        <td><span class="badge bg-success">Passed</span></td>
                                        <td>All good</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2024-01-10</td>
                                        <td>EXT-002</td>
                                        <td>Santa Clara NHS - Science Lab</td>
                                        <td>Jane Smith</td>
                                        <td>30%</td>
                                        <td><span class="badge bg-danger">Failed</span></td>
                                        <td>Needs refill, expires soon</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-edit"></i> Update
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
    </div>

    <!-- Add Extinguisher Modal -->
    <div class="modal fade" id="addExtinguisherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i> Add New Fire Extinguisher
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addExtinguisherForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Extinguisher ID *</label>
                                <input type="text" class="form-control" placeholder="e.g., EXT-001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type *</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option>ABC Dry Chemical</option>
                                    <option>CO2</option>
                                    <option>Water</option>
                                    <option>Foam</option>
                                    <option>Wet Chemical</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location *</label>
                                <select class="form-control" required>
                                    <option value="">Select Building</option>
                                    <option>San Isidro NHS - Main Entrance</option>
                                    <option>San Isidro NHS - Library</option>
                                    <option>San Isidro NHS - Cafeteria</option>
                                    <option>Santa Clara NHS - Science Lab</option>
                                    <option>Santa Clara NHS - Gym</option>
                                    <option>Tapinac ES - Classroom 1</option>
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
                                <label class="form-label">Expiration Date *</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Initial Pressure (%)</label>
                                <input type="number" class="form-control" min="0" max="100" value="100">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" step="0.1" placeholder="e.g., 5.0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes/Remarks</label>
                            <textarea class="form-control" rows="3" placeholder="Additional information..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveExtinguisher()">
                        <i class="fas fa-save me-2"></i> Save Extinguisher
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Refill Request Modal -->
    <div class="modal fade" id="refillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--fire-red); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-gas-pump me-2"></i> Request Refill
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="refillForm">
                        <div class="mb-3">
                            <label class="form-label">Select Extinguisher *</label>
                            <select class="form-control" required>
                                <option value="">Select Extinguisher</option>
                                <option>EXT-002 - Santa Clara NHS (Low Pressure)</option>
                                <option>EXT-005 - Tapinac ES (Expired)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Urgency Level *</label>
                            <select class="form-control" required>
                                <option value="">Select Urgency</option>
                                <option>Low - Schedule within 30 days</option>
                                <option>Medium - Schedule within 7 days</option>
                                <option>High - Schedule within 24 hours</option>
                                <option>Critical - Immediate attention needed</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Refill *</label>
                            <textarea class="form-control" rows="3" placeholder="Describe the issue..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estimated Cost (Optional)</label>
                            <input type="number" class="form-control" placeholder="0.00">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="submitRefillRequest()">
                        <i class="fas fa-paper-plane me-2"></i> Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Function to inspect extinguisher
        function inspectExtinguisher(extId) {
            if (confirm(`Record inspection for ${extId}?`)) {
                // In real implementation, this would open inspection form
                alert(`Opening inspection form for ${extId}`);
            }
        }

        // Function to request refill
        function refillExtinguisher(extId) {
            $('#refillModal').modal('show');
            // In real implementation, pre-fill the form with extinguisher ID
        }

        // Save extinguisher
        function saveExtinguisher() {
            const form = document.getElementById('addExtinguisherForm');
            if (form.checkValidity()) {
                alert('Fire extinguisher added successfully!');
                $('#addExtinguisherModal').modal('hide');
                form.reset();
            } else {
                form.reportValidity();
            }
        }

        // Submit refill request
        function submitRefillRequest() {
            const form = document.getElementById('refillForm');
            if (form.checkValidity()) {
                alert('Refill request submitted successfully!');
                $('#refillModal').modal('hide');
                form.reset();
            } else {
                form.reportValidity();
            }
        }

        // Generate report
        function generateReport() {
            if (confirm('Generate PDF report of all fire extinguishers?')) {
                alert('Report generation started... This might take a moment.');
                // In real implementation, this would trigger PDF generation
            }
        }

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
