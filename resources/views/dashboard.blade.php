{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard - DRRM Compliance')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-shield-alt text-primary"></i> DRRM Compliance Dashboard
            </h1>
            <p class="text-muted">Welcome, {{ Auth::user()->name }}! Select a compliance system to manage.</p>
        </div>
        <div>
            <span class="badge bg-info">
                <i class="fas fa-user"></i> {{ Auth::user()->role ?? 'Administrator' }}
            </span>
        </div>
    </div>

    <!-- Main System Cards -->
    <div class="row justify-content-center">
        <!-- Fire Safety Compliance -->
        <div class="col-md-4 mb-4">
            <a href="{{ route('fire-safety.dashboard') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #D12428;">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-fire fa-4x" style="color: #D12428;"></i>
                        </div>
                        <h3 class="card-title fw-bold" style="color: #D12428;">Fire Safety</h3>
                        <p class="card-text text-muted">
                            Alarm systems, fire extinguishers, building inspections, and evacuation plans management
                        </p>
                    </div>
                    <div class="card-footer bg-transparent text-center">
                        <span class="btn" style="background-color: #D12428; color: white;">
                            <i class="fas fa-arrow-right"></i> Enter
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Typhoon/Flooding Compliance -->
        <div class="col-md-4 mb-4">
            <a href="{{ route('typhoon.dashboard') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #1B4C6D;">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-umbrella fa-4x" style="color: #1B4C6D;"></i>
                        </div>
                        <h3 class="card-title fw-bold" style="color: #1B4C6D;">Typhoon/Flooding</h3>
                        <p class="card-text text-muted">
                            Casualty tracking, evacuation centers, evacuee management, and real-time monitoring
                        </p>
                    </div>
                    <div class="card-footer bg-transparent text-center">
                        <span class="btn" style="background-color: #1B4C6D; color: white;">
                            <i class="fas fa-arrow-right"></i> Enter
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Incidents Compliance -->
        <div class="col-md-4 mb-4">
            <a href="{{ route('incidents.dashboard') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #F2C94C;">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-clipboard-list fa-4x" style="color: #F2C94C;"></i>
                        </div>
                        <h3 class="card-title fw-bold" style="color: #F2C94C;">Incidents</h3>
                        <p class="card-text text-muted">
                            Incident recording, victim management, compliance checklists, and remarks tracking
                        </p>
                    </div>
                    <div class="card-footer bg-transparent text-center">
                        <span class="btn" style="background-color: #F2C94C; color: #333;">
                            <i class="fas fa-arrow-right"></i> Enter
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> System Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="text-primary">0</h2>
                                <p class="text-muted mb-0">Total Active Alerts</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="text-success">0</h2>
                                <p class="text-muted mb-0">Completed Today</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="text-warning">0</h2>
                                <p class="text-muted mb-0">Pending Actions</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="text-info">0</h2>
                                <p class="text-muted mb-0">Total Records</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
