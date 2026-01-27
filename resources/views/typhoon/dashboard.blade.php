{{-- resources/views/typhoon/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Typhoon/Flooding Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header with Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Main Dashboard
            </a>
        </div>
        <div>
            <h1 class="h3 mb-0" style="color: #1B4C6D;">
                <i class="fas fa-umbrella"></i> Typhoon/Flooding Compliance Dashboard
            </h1>
            <p class="text-muted">Casualty tracking, evacuation center management, and real-time monitoring</p>
        </div>
        <div class="text-end">
            <span class="badge bg-info">
                <i class="fas fa-clock"></i> Real-time Monitoring Active
            </span>
        </div>
    </div>

    <!-- Main Dashboard Cards -->
    <div class="row">
        <!-- Casualty Summary -->
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #dc3545;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Missing</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-search fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #ffc107;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Injured</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-user-injured fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #343a40;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Deceased</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-cross fa-2x text-dark"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #28a745;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Evacuees</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-users fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Evacuation Centers and Monitoring -->
    <div class="row">
        <!-- Evacuation Centers -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header" style="background-color: #1B4C6D; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-school"></i> Evacuation Centers Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Evacuation Center</th>
                                    <th>Status</th>
                                    <th>Capacity</th>
                                    <th>Current Occupancy</th>
                                    <th>Available</th>
                                    <th>Needs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>San Isidro NHS Gym</td>
                                    <td><span class="badge bg-success">Operational</span></td>
                                    <td>500</td>
                                    <td>0</td>
                                    <td>500</td>
                                    <td>None</td>
                                </tr>
                                <tr>
                                    <td>Santa Clara NHS Covered Court</td>
                                    <td><span class="badge bg-warning">Partial</span></td>
                                    <td>300</td>
                                    <td>0</td>
                                    <td>300</td>
                                    <td>Medical supplies</td>
                                </tr>
                                <tr>
                                    <td>Tapinac ES Classrooms</td>
                                    <td><span class="badge bg-danger">Closed</span></td>
                                    <td>200</td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>Structural damage</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Family/Individual Evacuees -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header" style="background-color: #1B4C6D; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Evacuee Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Total Families Evacuated: <strong>0</strong></h6>
                        <h6>Total Individuals: <strong>0</strong></h6>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>No active evacuation</strong><br>
                        <small>All evacuation centers are currently empty</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Register Evacuee
                        </button>
                        <button class="btn btn-outline-success">
                            <i class="fas fa-file-export"></i> Export Evacuee List
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Monitoring -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header" style="background-color: #1B4C6D; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marked-alt"></i> Real-time Monitoring
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Real-time monitoring system will be integrated here
                        <br>
                        <small>Features: Flood level monitoring, typhoon tracking, evacuation routes, live updates</small>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Flood Level Monitoring</h6>
                                    <p class="text-muted">River gauges, flood sensors</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Typhoon Tracking</h6>
                                    <p class="text-muted">PAGASA updates, wind speed</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Evacuation Routes</h6>
                                    <p class="text-muted">Safe pathways, blocked roads</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
