{{-- resources/views/fire-safety/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Fire Safety Dashboard')

@section('styles')
<style>
    :root {
        --fire-red: #AB161D;
        --fire-dark-red: #8A1217;
        --fire-light-red: #F8D7DA;
    }

    .sidebar {
        background-color: var(--fire-red);
        min-height: calc(100vh - 56px);
        color: white;
        width: 250px;
        position: fixed;
        left: 0;
        top: 56px;
        z-index: 100;
    }

    .main-content {
        margin-left: 250px;
        padding: 20px;
    }

    .nav-item.active {
        background-color: var(--fire-dark-red);
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 2px 8px;
    }
</style>
@endsection

@section('content')
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #AB161D;">
    <div class="container-fluid">
        <!-- Left: Logo and System Name -->
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="fas fa-arrow-left me-2"></i>
            <i class="fas fa-fire me-2"></i>
            Fire Safety Checklist System
        </a>

        <!-- Center: Title -->
        <div class="navbar-brand mx-auto">
            <h4 class="mb-0">Dashboard</h4>
        </div>

        <!-- Right: Icons -->
        <div class="navbar-nav">
            <div class="nav-item dropdown">
                <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="badge bg-danger">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <h6 class="dropdown-header">Notifications</h6>
                    <div class="dropdown-item text-muted">No new notifications</div>
                </div>
            </div>

            <div class="nav-item dropdown">
                <a class="nav-link" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i> {{ Auth::user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#"><i class="fas fa-user-circle"></i> Profile</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                       <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid p-0">
    <div class="row">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <div class="p-3">
                <!-- Menu Items -->
                <ul class="nav flex-column">
                    <li class="nav-item active">
                        <a class="nav-link text-white" href="#">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">
                            <i class="fas fa-bell me-2"></i> Alarm Systems
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">
                            <i class="fas fa-fire-extinguisher me-2"></i> Fire Extinguishers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">
                            <i class="fas fa-building me-2"></i> Buildings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">
                            <i class="fas fa-map-signs me-2"></i> Evacuation Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </li>
                </ul>

                <hr class="bg-white">

                <!-- Status Overview (Moved from sidebar to dashboard cards) -->
                <div class="mt-4">
                    <h6 class="text-white">Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <button class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> Add Inspection
                        </button>
                        <button class="btn btn-light btn-sm">
                            <i class="fas fa-bell"></i> Simulate Alarm
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Passed Inspections
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Failed Inspections
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Extinguishers Needing Action
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-fire-extinguisher fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Alarm System Status
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">0 Offline</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-bell fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- School Safety Status -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">School Safety Status</h6>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="schoolTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all">
                                        All Schools
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="san-isidro-tab" data-bs-toggle="tab" data-bs-target="#san-isidro">
                                        San Isidro NHS
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="santa-clara-tab" data-bs-toggle="tab" data-bs-target="#santa-clara">
                                        Santa Clara NHS
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tapinac-tab" data-bs-toggle="tab" data-bs-target="#tapinac">
                                        Tapinac ES
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content mt-3">
                                <div class="tab-pane fade show active" id="all">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>School</th>
                                                    <th>Status</th>
                                                    <th>Issues</th>
                                                    <th>Last Inspection</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>San Isidro NHS</td>
                                                    <td><span class="badge bg-success">PASSED</span></td>
                                                    <td>None</td>
                                                    <td>2024-01-15</td>
                                                </tr>
                                                <tr>
                                                    <td>Santa Clara NHS</td>
                                                    <td><span class="badge bg-danger">FAILED</span></td>
                                                    <td>2 issues found</td>
                                                    <td>2024-01-10</td>
                                                </tr>
                                                <tr>
                                                    <td>Tapinac ES</td>
                                                    <td><span class="badge bg-warning">WARNING</span></td>
                                                    <td>1 issue found</td>
                                                    <td>2024-01-12</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- Other tabs will have similar content -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side Alerts and Events -->
                <div class="col-lg-4">
                    <!-- Alerts Panel -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-danger">Alerts</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Fire drill overdue</strong><br>
                                <small>School 02 - Last drill: 90 days ago</small>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>1 extinguisher expired</strong><br>
                                <small>Building A, Floor 2</small>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Alarm system not tested this month</strong><br>
                                <small>Required monthly test overdue</small>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Upcoming Events</h6>
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
                    <div class="card shadow">
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
    </div>
</div>
@endsection
