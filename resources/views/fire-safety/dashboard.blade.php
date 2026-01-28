{{-- resources/views/fire-safety/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fire Safety Dashboard - DRRM Compliance</title>
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
                <button class="btn btn-light btn-sm">
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
            <div class="col-lg-8">
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

                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="all">
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
                                            <tr>
                                                <td>San Isidro NHS</td>
                                                <td><span class="status-badge bg-success">PASSED</span></td>
                                                <td>None</td>
                                                <td>2024-01-15</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Santa Clara NHS</td>
                                                <td><span class="status-badge bg-danger">FAILED</span></td>
                                                <td>2 issues found</td>
                                                <td>2024-01-10</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i> Fix Issues
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Tapinac ES</td>
                                                <td><span class="status-badge bg-warning">WARNING</span></td>
                                                <td>1 issue found</td>
                                                <td>2024-01-12</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-info-circle"></i> Details
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- Other tab contents would go here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side Alerts and Events -->
            <div class="col-lg-4">
                <!-- Alerts Panel -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header py-3 bg-danger text-white">
                        <h6 class="m-0 fw-bold">
                            <i class="fas fa-exclamation-circle me-2"></i> Alerts
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Fire drill overdue</strong><br>
                            <small>School 02 - Last drill: 90 days ago</small>
                        </div>
                        <div class="alert alert-warning alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>1 extinguisher expired</strong><br>
                            <small>Building A, Floor 2</small>
                        </div>
                        <div class="alert alert-warning alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Alarm system not tested this month</strong><br>
                            <small>Required monthly test overdue</small>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card dashboard-card">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 fw-bold">
                            <i class="fas fa-calendar-alt me-2"></i> Upcoming Events
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-calendar-check text-primary fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Inspection</h6>
                                <p class="text-muted mb-0">Feb 10, 2026 – San Isidro NHS</p>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-calendar-alt text-info fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Fire Drill</h6>
                                <p class="text-muted mb-0">Feb 15, 2026 – Santa Clara NHS</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-center gap-3">
                            <button class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Add Inspection
                            </button>
                            <button class="btn btn-warning">
                                <i class="fas fa-bell me-2"></i> Simulate Alarm
                            </button>
                            <button class="btn btn-info">
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Mobile sidebar toggle (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Add active class to current nav item
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');

            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });

            // Simple tab functionality
            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Alert dismiss buttons
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                const dismissBtn = document.createElement('button');
                dismissBtn.className = 'btn-close';
                dismissBtn.setAttribute('data-bs-dismiss', 'alert');
                alert.appendChild(dismissBtn);
            });
        });
    </script>
</body>
</html>
