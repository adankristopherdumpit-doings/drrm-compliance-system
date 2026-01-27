{{-- resources/views/incidents/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Incidents Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-warning">
                <i class="fas fa-arrow-left"></i> Back to Main Dashboard
            </a>
        </div>
        <div>
            <h1 class="h3 mb-0" style="color: #F2C94C;">
                <i class="fas fa-clipboard-list"></i> Incidents Compliance Dashboard
            </h1>
            <p class="text-muted">Incident recording, victim management, and compliance checklist tracking</p>
        </div>
        <div>
            <button class="btn" style="background-color: #F2C94C;">
                <i class="fas fa-plus"></i> Report New Incident
            </button>
        </div>
    </div>

    <!-- Incident Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #F2C94C;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Incidents</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-exclamation-circle fa-2x" style="color: #F2C94C;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #17a2b8;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Active Cases</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-clock fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #28a745;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Resolved</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow h-100" style="border-top: 4px solid #dc3545;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Victims Involved</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="fas fa-user-injured fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incident Types Breakdown -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header" style="background-color: #F2C94C;">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Incident Types Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Incident Type</th>
                                    <th>Count</th>
                                    <th>Last Occurrence</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <i class="fas fa-tornado text-info me-2"></i>
                                        Tropical Cyclone
                                    </td>
                                    <td>0</td>
                                    <td>N/A</td>
                                    <td><span class="badge bg-secondary">No records</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-mountain text-danger me-2"></i>
                                        Earthquake
                                    </td>
                                    <td>0</td>
                                    <td>N/A</td>
                                    <td><span class="badge bg-secondary">No records</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-car-crash text-warning me-2"></i>
                                        Violence/Accidents
                                    </td>
                                    <td>0</td>
                                    <td>N/A</td>
                                    <td><span class="badge bg-secondary">No records</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-landslide text-success me-2"></i>
                                        Landslide/Flooding
                                    </td>
                                    <td>0</td>
                                    <td>N/A</td>
                                    <td><span class="badge bg-secondary">No records</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checklist and Victim Management -->
        <div class="col-lg-4">
            <!-- Compliance Checklist -->
            <div class="card shadow mb-4">
                <div class="card-header" style="background-color: #F2C94C;">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-check"></i> Compliance Checklist
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <div class="list-group-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check1">
                                <label class="form-check-label" for="check1">
                                    Incident properly categorized
                                </label>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check2">
                                <label class="form-check-label" for="check2">
                                    Victim information recorded
                                </label>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check3">
                                <label class="form-check-label" for="check3">
                                    Date and time documented
                                </label>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check4">
                                <label class="form-check-label" for="check4">
                                    Remarks and notes added
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Victim Entry -->
            <div class="card shadow">
                <div class="card-header" style="background-color: #F2C94C;">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus"></i> Quick Victim Entry
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Victim Name</label>
                        <input type="text" class="form-control" placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Incident Type</label>
                        <select class="form-control">
                            <option>Tropical Cyclone</option>
                            <option>Earthquake</option>
                            <option>Violence/Accident</option>
                            <option>Landslide/Flooding</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" rows="2" placeholder="Add remarks"></textarea>
                    </div>
                    <button class="btn w-100" style="background-color: #F2C94C;">
                        <i class="fas fa-save"></i> Save Victim Record
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Incidents -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header" style="background-color: #F2C94C;">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Recent Incidents
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No recent incidents recorded. Start by reporting a new incident.
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <button class="btn btn-primary">
                            <i class="fas fa-file-alt me-2"></i> View All Reports
                        </button>
                        <button class="btn btn-success">
                            <i class="fas fa-chart-bar me-2"></i> Generate Statistics
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-print me-2"></i> Print Checklist
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
