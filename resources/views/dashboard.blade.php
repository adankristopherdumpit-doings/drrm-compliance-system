{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard - DRRM Compliance')

@push('styles')
<style>
    .announcement-banner {
        position: relative;
        width: 100%;
        padding-top: 25%; /* Reduced height */
        overflow: hidden;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        margin-bottom: 2.5rem;
        background-color: #ffffff;
    }
    @media (max-width: 768px) {
        .announcement-banner {
            padding-top: 56.25%; /* 16:9 on mobile */
        }
    }
    .announcement-banner .carousel-inner {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    .announcement-banner img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain; /* Shows white spaces at sides if not landscape */
        transition: transform 0.5s ease;
    }
    .announcement-banner:hover img {
        transform: scale(1.02);
    }
    .announcement-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.85));
        color: white;
        padding: 40px;
        z-index: 2;
    }
    .announcement-content {
        max-width: 80% ;
    }
    .announcement-meta {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-bottom: 10px;
    }
    .announcement-title {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    .announcement-why {
        font-size: 1.1rem;
        line-height: 1.4;
        opacity: 0.95;
    }
    .delete-announcement-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 10;
        background: rgba(220, 53, 69, 0.8);
        border: none;
        color: white;
        padding: 5px 12px;
        border-radius: 5px;
        backdrop-filter: blur(5px);
    }
    .delete-announcement-btn:hover {
        background: #dc3545;
    }
    .carousel-indicators {
        margin-bottom: 2rem;
    }
    .carousel-indicators [data-bs-target] {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: rgba(255,255,255,0.5);
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    .carousel-indicators .active {
        background-color: #fff;
        transform: scale(1.2);
    }
    .carousel-control-prev, .carousel-control-next {
        width: 5%;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .announcement-banner:hover .carousel-control-prev,
    .announcement-banner:hover .carousel-control-next {
        opacity: 0.8;
    }
    .carousel-control-prev-icon, .carousel-control-next-icon {
        background-color: rgba(0,0,0,0.3);
        padding: 20px;
        border-radius: 50%;
        background-size: 50% 50%;
    }

    /* Remove gap between header and announcement */
    .dashboard-container {
        margin-top: -24px; /* Offsets the main py-4 padding-top */
    }
    .announcement-banner {
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
    /* Tab System Styling */
    .dashboard-tabs-wrapper {
        margin: 2rem 0;
        width: 100%;
    }
    .custom-tabs-container {
        display: flex;
        justify-content: center;
        background-color: transparent;
        padding: 0;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
    }
    .nav-tab-item {
        flex: 1;
        text-align: center;
        padding: 15px 30px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        outline: none;
    }
    .nav-tab-item.active {
        background-color: #212529 !important;
        color: #ffffff !important;
    }
    .nav-tab-item:not(.active) {
        background-color: #ffffff !important;
        color: #212529 !important;
        border: 1px solid #dee2e6;
    }
    .nav-tab-item:hover:not(.active) {
        background-color: #f8f9fa;
        color: #000;
    }

    /* Card Hover Animations (requested) */
    .module-card-link {
        display: block;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .module-card-link:hover {
        transform: translateY(-12px) scale(1.02);
    }
    .module-card-link .card {
        transition: box-shadow 0.3s ease, border-color 0.3s ease;
    }
    .module-card-link:hover .card {
        box-shadow: 0 25px 50px rgba(0,0,0,0.15) !important;
    }
    .module-card-link:hover .card-body i {
        animation: card-icon-float 2s ease-in-out infinite;
    }

    @keyframes card-icon-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    /* Modal Styling Adjustments */
    .modal-header.bg-dark {
        background-color: #212529 !important;
    }
    .school-detail-label {
        color: #6c757d;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .school-detail-value {
        font-weight: 600;
        color: #212529;
        margin-bottom: 12px;
    }
    .btn-module {
        transition: all 0.3s ease;
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 10px;
        border-radius: 8px;
    }
    .btn-module i {
        font-size: 1.1rem;
        margin-bottom: 5px;
        display: block;
    }
    .btn-module.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .school-info-modal-dialog {
        max-width: 980px;
    }
    #btn_fire_safety:hover { background-color: #D12428; border-color: #D12428; color: #fff; }
    #btn_typhoon:hover { background-color: #1B4C6D; border-color: #1B4C6D; color: #fff; }
    #btn_incident:hover { background-color: #F2C94C; border-color: #F2C94C; color: #212529; }
    #btn_comprehensive:hover { background-color: #5C4033; border-color: #5C4033; color: #fff; }
    #btn_hazard:hover { background-color: #0D7377; border-color: #0D7377; color: #fff; }
    #btn_inventory:hover { background-color: #17a2b8; border-color: #17a2b8; color: #fff; }
    #btn_damage_assessment:hover { background-color: #6f42c1; border-color: #6f42c1; color: #fff; }

    /* Margins for the layout as requested */
    .schools-tab-container {
        margin-left: 20px;
        margin-right: 20px;
    }
    @media (min-width: 1200px) {
        .schools-tab-container {
            margin-left: 60px;
            margin-right: 60px;
        }
    }

    /* Tab Content Transitions */
    .tab-content-active {
        animation: fadeIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="container-fluid dashboard-container">
    @php
        $user = auth()->user();
        $modules = $user?->module_access ?? [];
        $isAdmin = $user && $user->role === 'admin';
        $isContributor = $user && $user->role === 'contributor';
        $canShowSchoolTab = $isAdmin || $isContributor;
    @endphp
    @if($announcements->count() == 0)
        <div class="d-flex justify-content-between align-items-center mb-4">
            <p class="text-muted mb-0">Welcome back, <strong>{{ Auth::user()->name }}</strong>! Select a compliance system to manage.</p>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-triangle-exclamation me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($announcements->count() > 0)
        {{-- ... (Rest of Carousel remains same) --}}
        <div id="announcementCarousel" class="carousel slide announcement-banner mb-5" data-bs-ride="carousel" data-bs-interval="5000">
            @if($announcements->count() > 1)
                <div class="carousel-indicators">
                    @foreach($announcements as $index => $announcement)
                        <button type="button" data-bs-target="#announcementCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-label="Slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif

            <div class="carousel-inner h-100">
                @foreach($announcements as $index => $announcement)
                    <div class="carousel-item h-100 {{ $index === 0 ? 'active' : '' }}">
                        @if(Auth::user()->role === 'admin')
                            <button class="delete-announcement-btn" onclick="deleteAnnouncement({{ $announcement->id }})" title="Remove Announcement">
                                <i class="fas fa-times me-1"></i> Remove
                            </button>
                        @endif
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($announcement->image_path) }}" class="d-block w-100" alt="Announcement Poster">
                        <div class="announcement-overlay">
                            <div class="announcement-content">
                                <p class="announcement-meta">
                                    <i class="far fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($announcement->when)->format('F j, Y \a\t h:i A') }}
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-map-marker-alt me-1"></i> {{ $announcement->where }}
                                </p>
                                <h2 class="announcement-title">{{ $announcement->what }}</h2>
                                <p class="announcement-why mb-0">{{ $announcement->why }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($announcements->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#announcementCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#announcementCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            @endif
        </div>
    @endif

    @if($canShowSchoolTab)
        <!-- Dashboard Tabs Wrapper (Centered and covering full row) -->
        <div class="dashboard-tabs-wrapper mb-5 px-lg-5">
            <div class="custom-tabs-container shadow-sm border">
                <div id="complianceTabBtn" class="nav-tab-item active" onclick="switchDashboardTab('compliance')">
                    <i class="fas fa-th-large me-2"></i> Compliance
                </div>
                <div id="schoolsTabBtn" class="nav-tab-item" onclick="switchDashboardTab('schools')">
                    <i class="fas fa-university me-2"></i> {{ $isAdmin ? 'Schools' : 'School' }}
                </div>
            </div>
        </div>
    @endif

    <!-- Compliance Content Division -->
    <div id="complianceTabContent" class="tab-content-active">
        <div class="row justify-content-center">
            <!-- Fire Safety Compliance -->
            <div class="col-md-4 mb-4">
                @php $canAccessFire = $isAdmin || in_array('fire_safety', $modules); @endphp
                <a href="{{ route('fire-safety.dashboard') }}" class="text-decoration-none module-card-link"
                   data-module="fire_safety" data-can-access="{{ $canAccessFire ? '1' : '0' }}" data-theme-color="#D12428">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #D12428;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-fire fa-4x" style="color: #D12428;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #D12428;">Fire Safety</h3>
                            <p class="card-text text-muted">
                                Fire extinguishers, building inspections, and evacuation plans management
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #D12428; color: white;">
                                <i class="fas fa-arrow-right"></i> Enter
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Typhoon/Flooding Monitoring -->
            <div class="col-md-4 mb-4">
                @php $canAccessTyphoon = $isAdmin || in_array('typhoon_flood', $modules); @endphp
                <a href="{{ route('typhoon.dashboard') }}" class="text-decoration-none module-card-link"
                   data-module="typhoon_flood" data-can-access="{{ $canAccessTyphoon ? '1' : '0' }}" data-theme-color="#1B4C6D">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #1B4C6D;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-umbrella fa-4x" style="color: #1B4C6D;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #1B4C6D;">Evacuation Monitoring</h3>
                            <p class="card-text text-muted">
                                Evacuation centers, evacuee management, and monitoring Evacuation centers.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #1B4C6D; color: white;">
                                <i class="fas fa-arrow-right"></i> Enter
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Incidents Compliance -->
            <div class="col-md-4 mb-4">
                @php $canAccessIncidents = $isAdmin || in_array('incident_checklist', $modules); @endphp
                <a href="{{ route('incidents.dashboard') }}" class="text-decoration-none module-card-link"
                   data-module="incident_checklist" data-can-access="{{ $canAccessIncidents ? '1' : '0' }}" data-theme-color="#F2C94C">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #F2C94C;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-clipboard-list fa-4x" style="color: #F2C94C;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #F2C94C;">Incident Checklist</h3>
                            <p class="card-text text-muted">
                                Incident recording, victim management, compliance checklists, and remarks tracking
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #F2C94C; color: #333; {{ $canAccessIncidents ? '' : 'opacity: 0.7; cursor: not-allowed;' }}">
                                <i class="fas fa-arrow-right"></i> {{ $canAccessIncidents ? 'Enter' : 'Admin Only' }}
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Comprehensive School Safety -->
            <div class="col-md-4 mb-4">
                @php $canAccessSchoolSafety = $isAdmin || in_array('comprehensive_school_safety', $modules); @endphp
                <a href="{{ route('comprehensive-school-safety.dashboard') }}" class="text-decoration-none module-card-link"
                   data-module="comprehensive_school_safety" data-can-access="{{ $canAccessSchoolSafety ? '1' : '0' }}" data-theme-color="#5C4033">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #5C4033;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-school fa-4x" style="color: #5C4033;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #5C4033;">Comprehensive School Safety</h3>
                            <p class="card-text text-muted">
                                Evaluation compliance system assessing tools, disaster risk management, DRR in education, and safe learning facilities.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #5C4033; color: white;">
                                <i class="fas fa-arrow-right"></i> Enter
                            </span>
                        </div>
                    </div>
                </a>
            </div>

                        <!--Drill Monitoring -->
            <div class="col-md-4 mb-4">
                @php $canAccessDrillMonitoring = $isAdmin || in_array('drill_monitoring', $modules); @endphp
                <a href="{{ route('drill-monitoring.dashboard') }}" class="text-decoration-none module-card-link"
                   data-module="drill_monitoring" data-can-access="{{ $canAccessDrillMonitoring ? '1' : '0' }}" data-theme-color="#FF6F00">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #FF6F00;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-bell fa-4x" style="color: #FF6F00;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #FF6F00;">Drill Monitoring</h3>
                            <p class="card-text text-muted">
                                Track, log, and evaluate emergency evacuation drills, response times, and compliance metrics.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #FF6F00; color: white;">
                                <i class="fas fa-arrow-right"></i> Enter
                            </span>
                        </div>
                    </div>
                </a>
            </div>


            <!-- Hazard Mapping -->
            <div class="col-md-4 mb-4">
                @php $canAccessHazard = $isAdmin || in_array('hazard_mapping', $modules); @endphp
                     <a href="{{ route('hazard-mapping.dashboard') }}" class="text-decoration-none module-card-link"
                         data-module="hazard_mapping" data-can-access="{{ $canAccessHazard ? '1' : '0' }}" data-theme-color="#0D7377">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #0D7377;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-map-marked-alt fa-4x" style="color: #0D7377;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #0D7377;">Hazard Mapping</h3>
                            <p class="card-text text-muted">
                                Identify, assess, and map hazards affecting school sites and areas for risk reduction and preparedness planning.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #0D7377; color: white;">
                                <i class="fas fa-arrow-right"></i> Enter
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

                <!-- Second Row For Alignment -->
         <div class="row">
            <!-- Damage Report Module --> 
            <div class="col-md-6 col-lg-4 offset-lg-2 mb-4">
                @php $canAccessDamageReport = $isAdmin || in_array('damage_reports', $modules); @endphp
                <a href="#" class="text-decoration-none module-card-link" data-module="damage_reports" data-can-access="{{ $canAccessDamageReport ? '1' : '0' }}" data-theme-color="#6f42c1">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #6f42c1;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-user-injured fa-4x" style="color: #6f42c1;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #6f42c1;">Damage Reports</h3>
                            <p class="card-text text-muted">
                                Track damages of facilities and resources. Assess the severity of damage and make decisions for recovery and rehabilitation.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #6f42c1; color: white;">
                                <i class="fas fa-arrow-right"></i> Enter
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Inventory Module --> 
            <div class="col-md-6 col-lg-4 mb-4">
                @php $canAccessInventory = $isAdmin || in_array('inventory_storage', $modules); @endphp
                <a href="{{ route('inventory-storage.dashboard') }}" class="text-decoration-none module-card-link" data-module="inventory_storage" data-can-access="{{ $canAccessInventory ? '1' : '0' }}" data-theme-color="#17a2b8">
                    <div class="card border-0 shadow-lg h-100" style="border-top: 5px solid #17a2b8;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-boxes fa-4x" style="color: #17a2b8;"></i>
                            </div>
                            <h3 class="card-title fw-bold" style="color: #17a2b8;">Inventory</h3>
                            <p class="card-text text-muted">
                                Manage and track sources, gears and equipment inventory for disaster preparedness, response, and recovery operations.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0">
                            <span class="btn" style="background-color: #17a2b8; color: white;">
                                <i class="fas fa-arrow-right"></i> Enter
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        </div>


        <!-- Quick Stats -->
        <div class="row mt-5 mx-lg-5">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-light p-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i> System Overview</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3 border-end">
                                    <h2 class="text-primary fw-bold">{{ $totalSchoolsCount }}</h2>
                                    <p class="text-muted mb-0 small uppercase fw-bold">Total Schools</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border-end">
                                    <h2 class="text-success fw-bold">{{ $totalUsersCount }}</h2>
                                    <p class="text-muted mb-0 small uppercase fw-bold">Total Users</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border-end">
                                    <h2 class="text-warning fw-bold">{{ $overallComplianceRate }}%</h2>
                                    <p class="text-muted mb-0 small uppercase fw-bold">Compliance Rate</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h2 class="text-info fw-bold">{{ number_format($totalPopulationCount) }}</h2>
                                    <p class="text-muted mb-0 small uppercase fw-bold">Population Covered</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canShowSchoolTab)
        <!-- Schools Content Division -->
        <div id="schoolsTabContent" class="d-none">
            @if($isAdmin)
                @include('schools-tab')
            @else
                <div class="schools-tab-container mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 px-lg-5">
                        <h2 class="fw-bold mb-0"><i class="fas fa-school me-2"></i> My Assigned School</h2>
                    </div>

                    <div class="px-lg-5">
                        @if(isset($contributorAssignedSchool) && $contributorAssignedSchool)
                            <div class="card border-0 shadow-lg rounded-4">
                                <div class="card-header bg-dark text-white rounded-top-4">
                                            <div class="d-flex justify-content-between align-items-center gap-3">
                                                <h5 class="mb-0 fw-bold">{{ $contributorAssignedSchool->school_name ?? 'Assigned School' }}</h5>
                                                <a href="{{ route('fire-safety.report.full-school', $contributorAssignedSchool->id) }}" target="_blank" class="btn btn-sm btn-outline-light">
                                                    <i class="fas fa-print me-1"></i> Print Full Report
                                                </a>
                                            </div>
                                </div>
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold mb-0">Core Information</h5>
                                        @if(Auth::user()->role !== 'viewer')
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-dark btn-sm" id="contributorUnlockSchoolBtn">
                                                    <i class="fas fa-pen me-1"></i> Update Details
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="contributorCancelSchoolBtn">Cancel</button>
                                            </div>
                                        @endif
                                    </div>

                                    <form id="contributorSchoolForm">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-3">
                                            <div class="col-md-9">
                                                <label class="form-label fw-bold small">School Name</label>
                                                <input type="text" class="form-control contributor-school-input" name="school_name" value="{{ $contributorAssignedSchool->school_name }}" required disabled>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold small">School ID</label>
                                                <input type="text" class="form-control contributor-school-input" name="school_id" value="{{ $contributorAssignedSchool->school_id ?: ($contributorAssignedSchool->school_id_number ?: '') }}" disabled>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-bold small">Address</label>
                                                <textarea class="form-control contributor-school-input" name="address" rows="2" required disabled>{{ $contributorAssignedSchool->address }}</textarea>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small">District</label>
                                                <input type="text" class="form-control contributor-school-input" name="district" value="{{ $contributorAssignedSchool->district }}" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small">Division</label>
                                                <input type="text" class="form-control contributor-school-input" name="division" value="{{ $contributorAssignedSchool->division }}" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small">Region</label>
                                                <input type="text" class="form-control contributor-school-input" name="region" value="{{ $contributorAssignedSchool->region }}" disabled>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">School Head</label>
                                                <input type="text" class="form-control contributor-school-input" name="school_head" value="{{ $contributorAssignedSchool->school_head }}" disabled>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">DRRM Coordinator</label>
                                                <input type="text" class="form-control contributor-school-input" name="drrm_coordinator" value="{{ $contributorAssignedSchool->drrm_coordinator }}" disabled>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">Head Contact</label>
                                                <input type="text" class="form-control contributor-school-input" name="contact_number" value="{{ $contributorAssignedSchool->contact_number }}" disabled>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">Coordinator Contact</label>
                                                <input type="text" class="form-control contributor-school-input" name="contact_number_2" value="{{ $contributorAssignedSchool->contact_number_2 }}" disabled>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small">No. of Students</label>
                                                <input type="number" class="form-control contributor-school-input" name="number_students" min="0" value="{{ $contributorAssignedSchool->number_students ?? 0 }}" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small">No. of Personnel</label>
                                                <input type="number" class="form-control contributor-school-input" name="number_personnel" min="0" value="{{ $contributorAssignedSchool->number_personnel ?? 0 }}" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small">No. of Gates</label>
                                                <input type="number" class="form-control contributor-school-input" name="number_gates" min="0" value="{{ $contributorAssignedSchool->number_gates ?? 0 }}" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small">Engineer Last Inspection Date</label>
                                                <input type="date" class="form-control contributor-school-input" name="engineer_last_inspection_date" value="{{ optional($contributorAssignedSchool->engineer_last_inspection_date)->format('Y-m-d') }}" disabled>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-bold small">Emergency Resources</label>
                                                <textarea class="form-control contributor-school-input" name="emergency_resources" rows="2" disabled>{{ $contributorAssignedSchool->emergency_resources }}</textarea>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end mt-3">
                                            <button type="submit" class="btn btn-success btn-sm d-none" id="contributorSaveSchoolBtn">
                                                <i class="fas fa-save me-1"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>

                                    <div class="mt-4 pt-3 border-top">
                                        <h6 class="fw-bold mb-2">School Account Users</h6>
                                        <div class="small text-muted" id="contributorSchoolAccountUsersList">
                                            @if(isset($contributorSchoolAccountUsers) && $contributorSchoolAccountUsers->count() > 0)
                                                @foreach($contributorSchoolAccountUsers as $acctUser)
                                                    <div class="mb-1">{{ $acctUser->name }} ({{ $acctUser->email }})</div>
                                                @endforeach
                                            @else
                                                No assigned school account users.
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="card border-0 shadow rounded-4">
                                <div class="card-body p-4 text-center">
                                    <i class="fas fa-school text-muted fa-2x mb-3"></i>
                                    <h5 class="fw-bold">No School Assignment Yet</h5>
                                    <p class="text-muted mb-0">Your account has no assigned school. Please contact your administrator.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($isAdmin)

        <!-- View School Details Modal -->
        <div class="modal fade" id="viewSchoolModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg school-info-modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-dark text-white p-4">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-university me-2"></i> <span id="schoolDetailName">School Details</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <!-- Core Info -->
                            <div class="col-md-7">
                                <div class="mb-4">
                                    <h6 class="fw-bold border-bottom pb-2 mb-3">Core Information</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="school-detail-label">School ID</div>
                                            <div class="school-detail-value text-break" id="detail_id">-</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">Name</div>
                                            <div class="school-detail-value" id="detail_name">-</div>
                                        </div>
                                        <div class="col-12">
                                            <div class="school-detail-label">School Address</div>
                                            <div class="school-detail-value" id="detail_address">-</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">School Head</div>
                                            <div class="school-detail-value" id="detail_head">-</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">DRRM Coordinator</div>
                                            <div class="school-detail-value" id="detail_coordinator">-</div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h6 class="fw-bold border-bottom pb-2 mb-3">Additional & Module Info</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="school-detail-label">Head Contact</div>
                                            <div class="school-detail-value" id="detail_head_contact">-</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">Coord. Contact</div>
                                            <div class="school-detail-value" id="detail_coord_contact">-</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="school-detail-label">District</div>
                                            <div class="school-detail-value" id="detail_district">-</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="school-detail-label">Division</div>
                                            <div class="school-detail-value" id="detail_division">-</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="school-detail-label">Region</div>
                                            <div class="school-detail-value" id="detail_region">-</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">Number of Students</div>
                                            <div class="school-detail-value badge bg-light text-dark border p-2" id="detail_students">0</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">Number of Personnel</div>
                                            <div class="school-detail-value badge bg-light text-dark border p-2" id="detail_personnel">0</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">Number of Gates</div>
                                            <div class="school-detail-value badge bg-light text-dark border p-2" id="detail_gates">0</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="school-detail-label">Engineer Last Inspection Date</div>
                                            <div class="school-detail-value badge bg-light text-dark border p-2" id="detail_engineer_last_inspection_date">N/A</div>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <div class="school-detail-label">Emergency Resources</div>
                                            <div class="school-detail-value bg-light p-2 rounded small border" id="detail_resources">None specified</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Module Shortcuts -->
                            <div class="col-md-5 border-start">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">Module Connections</h6>
                                <p class="small text-muted mb-4">Redirection depends on registration status.</p>

                                <div class="d-grid gap-2">
                                    <button id="btn_fire_safety" class="btn btn-module text-start mb-2 py-3 border">
                                        <i class="fas fa-fire me-2 d-inline"></i> Fire Safety
                                    </button>
                                    <button id="btn_typhoon" class="btn btn-module text-start mb-2 py-3 border">
                                        <i class="fas fa-cloud-showers-heavy me-2 d-inline"></i> Typhoon/Flood
                                    </button>
                                    <button id="btn_incident" class="btn btn-module text-start mb-2 py-3 border">
                                        <i class="fas fa-clipboard-list me-2 d-inline"></i> Incidents
                                    </button>
                                    <button id="btn_comprehensive" class="btn btn-module text-start mb-2 py-3 border">
                                        <i class="fas fa-school me-2 d-inline"></i> CSS Assessment
                                    </button>
                                    <button id="btn_damage_assessment" class="btn btn-module text-start mb-2 py-3 border">
                                        <i class="fas fa-user-injured me-2 d-inline"></i> Damage Assessment
                                    </button>
                                    <button id="btn_hazard" class="btn btn-module text-start py-3 border">
                                        <i class="fas fa-map-marked-alt me-2 d-inline"></i> Hazard (Dev)
                                    </button>
                                </div>

                                <div class="mt-4 pt-3 border-top">
                                    <h6 class="fw-bold mb-2">Unassigned Contributors/Viewers</h6>
                                    <div id="availableSchoolUsersMenu" class="d-grid gap-2 small">
                                        <div class="text-muted small">No available users.</div>
                                    </div>

                                    <div class="small text-muted mt-2" id="schoolAssignmentHint">Only unassigned contributors/viewers are listed here.</div>
                                </div>

                                <div class="mt-3">
                                    <h6 class="fw-bold mb-2">School Account Users</h6>
                                    <div id="schoolAccountUsersList" class="small text-muted">No assigned school account users.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 p-4 rounded-bottom-4">
                        @if(Auth::user()->role !== 'viewer')
                        <button type="button" class="btn btn-dark px-4" id="editSchoolBtn">
                            <i class="fas fa-edit me-2"></i> Update Details
                        </button>
                        @endif
                        {{--
                        <button type="button" class="btn btn-outline-danger px-4" id="deleteSchoolBtn" style="display:none;">
                            <i class="fas fa-trash me-2"></i> Delete
                        </button>
                        --}}
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete School Password Modal -->
        <div class="modal fade" id="deleteSchoolPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-dark text-white p-4">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-trash me-2"></i> Delete School Confirmation
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-3 text-muted">
                            You are about to delete: <strong id="deleteSchoolName">-</strong>
                        </p>

                        <div class="mb-2">
                            <label class="form-label fw-bold small">Admin password required</label>
                            <input
                                type="password"
                                id="deleteSchoolPasswordInput"
                                class="form-control"
                                placeholder="Enter your account password"
                                autocomplete="current-password"
                                style="pointer-events:auto;"
                            >
                        </div>

                        <div id="deleteSchoolPasswordError" class="alert alert-danger py-2 px-3" style="display:none;">
                            Password is required.
                        </div>

                        <input type="hidden" id="deleteSchoolIdInput" value="">
                    </div>

                    <div class="modal-footer bg-light border-0 p-4">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger px-5 fw-bold" id="deleteSchoolConfirmBtn">
                            Delete now
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add School Modal -->
        <div class="modal fade" id="addSchoolModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-dark text-white p-4">
                        <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2 text-warning"></i> Add New School</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addSchoolForm">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">School Identification</label>
                                <input type="text" name="school_name" class="form-control mb-2 p-3" required placeholder="Official School Name">
                                <input type="text" name="school_id" class="form-control p-3" placeholder="DepEd School ID (Optional)">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">School's Address</label>
                                <textarea name="address" class="form-control p-3" rows="2" required placeholder="Complete School Address"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">School Head</label>
                                    <input type="text" name="school_head" class="form-control p-3" placeholder="Enter School Head Name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">DRRM Coordinator</label>
                                    <input type="text" name="drrm_coordinator" class="form-control p-3" placeholder="Enter DRRM Coordinator Name">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">District</label>
                                    <input type="text" name="district" class="form-control" placeholder="Dist.">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Division</label>
                                    <input type="text" name="division" class="form-control" placeholder="Div.">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Region</label>
                                    <input type="text" name="region" class="form-control" placeholder="Reg.">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Number of Students</label>
                                    <input type="number" name="number_students" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Number of Personnel</label>
                                    <input type="number" name="number_personnel" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Number of Gates</label>
                                    <input type="number" name="number_gates" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Engineer Last Inspection Date</label>
                                    <input type="date" name="engineer_last_inspection_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4 pt-0">
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-dark px-5 py-2 fw-bold rounded-pill">Save School</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit School Modal -->
        <div class="modal fade" id="editSchoolModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-dark text-white p-4">
                        <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> Update Information</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editSchoolForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-9 mb-2">
                                    <label class="form-label fw-bold small">Official School Name</label>
                                    <input type="text" name="school_name" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold small">School ID</label>
                                    <input type="text" name="school_id" class="form-control">
                                </div>
                                <div class="col-md-12 mb-2">
                                    <label class="form-label fw-bold small">School Address</label>
                                    <textarea name="address" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold small">School Head</label>
                                    <input type="text" name="school_head" class="form-control" placeholder="Enter School Head Name">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold small">Head Contact No.</label>
                                    <input type="text" name="contact_number" class="form-control">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold small">DRRM Coordinator</label>
                                    <input type="text" name="drrm_coordinator" class="form-control" placeholder="Enter DRRM Coordinator Name">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold small">Coord. Contact No.</label>
                                    <input type="text" name="contact_number_2" class="form-control">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold small">District</label>
                                    <input type="text" name="district" class="form-control">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold small">Division</label>
                                    <input type="text" name="division" class="form-control">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold small">Region</label>
                                    <input type="text" name="region" class="form-control">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold small">Number of Students</label>
                                    <input type="number" name="number_students" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold small">Number of Personnel</label>
                                    <input type="number" name="number_personnel" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold small">Number of Gates</label>
                                    <input type="number" name="number_gates" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold small">Engineer Last Inspection Date</label>
                                    <input type="date" name="engineer_last_inspection_date" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Emergency Resources</label>
                                    <textarea name="emergency_resources" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0 p-4 rounded-bottom-4">
                            <button type="button" class="btn btn-outline-secondary px-4 text-uppercase fw-bold small" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-dark px-5 text-uppercase fw-bold small">Update School</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($user && ($user->needs_fs_registration || $user->needs_tf_registration))
        <!-- Contributor School Registration Modal -->
        <div class="modal fade" id="registrationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="registrationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title" id="registrationModalLabel">
                            <i class="fas fa-school-flag me-2 text-warning"></i> Register Your School
                        </h5>
                    </div>
                    <form id="registrationForm">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Before you can access the compliance modules, you need to provide your school's official information. This will automatically link your account to your newly created school.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Official School Name <span class="text-danger">*</span></label>
                                    <input type="text" name="school_name" class="form-control" placeholder="e.g. San Isidro Central School" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">School ID / Code <span class="text-danger">*</span></label>
                                    <input type="text" name="school_id_number" class="form-control" placeholder="e.g. 106883" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">School Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control" rows="3" placeholder="Enter full address..." required></textarea>
                                </div>
                            </div>

                            <div class="mt-4 p-3 rounded bg-light border">
                                <h6 class="fw-bold mb-2"><i class="fas fa-check-double me-1 text-success"></i> Modules to activate:</h6>
                                <div class="d-flex gap-3">
                                    @if($user->needs_fs_registration)
                                        <span class="badge bg-white text-dark border p-2"><i class="fas fa-fire me-1 text-danger"></i> Fire Safety Compliance</span>
                                    @endif
                                    @if($user->needs_tf_registration)
                                        <span class="badge bg-white text-dark border p-2"><i class="fas fa-cloud-showers-heavy me-1 text-info"></i> Typhoon/Flooding</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4">
                            <a href="{{ route('logout') }}" class="btn btn-link text-muted" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Logout and Finish Later
                            </a>
                            <button type="submit" class="btn btn-dark px-4 py-2" id="submitReg">
                                <i class="fas fa-save me-2"></i> Register and Continue
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const regModal = new bootstrap.Modal(document.getElementById('registrationModal'));
                regModal.show();

                document.getElementById('registrationForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('submitReg');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Registering...';

                    fetch("{{ route('register-school') }}", {
                        method: 'POST',
                        body: new FormData(this),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                confirmButtonColor: '#212529'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-save me-2"></i> Register and Continue';
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        Swal.fire('Error', 'An unexpected error occurred.', 'error');
                        btn.disabled = false;
                    });
                });
            });
        </script>
    @endif

<!-- Incident Module Choice Modal -->
<div class="modal fade" id="incidentModuleChoiceModal" tabindex="-1" aria-labelledby="incidentModuleChoiceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #F2C94C 0%, #F2994A 100%);">
                <h5 class="modal-title" id="incidentModuleChoiceLabel">
                    <i class="fas fa-clipboard-list me-2"></i>Choose Incident Entry Type
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 text-muted">Open <strong id="incidentModuleChoiceSchoolName">Selected school</strong> in the Incident Checklist module, then choose the log type.</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-warning py-3 fw-semibold" id="incidentModuleChoiceIncidentBtn">
                        <i class="fas fa-exclamation-triangle me-2"></i> Log Incident
                    </button>
                    <button type="button" class="btn btn-outline-warning py-3 fw-semibold" id="incidentModuleChoiceComplianceBtn">
                        <i class="fas fa-calendar-check me-2"></i> Log Compliance Status / Event
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hazard Mapping Coming Soon Modal -->
<div class="modal fade" id="hazardModuleComingSoonModal" tabindex="-1" aria-labelledby="hazardModuleComingSoonLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0D7377;">
                <h5 class="modal-title" id="hazardModuleComingSoonLabel">
                    <i class="fas fa-tools me-2"></i>Hazard Mapping: Coming Soon
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    The Hazard Mapping system is currently under development and will be available in the upcoming days.
                </p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn text-white" data-bs-dismiss="modal" style="background-color: #0D7377; border-color: #0D7377;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- No Access Modal -->
<div class="modal fade" id="noModuleAccessModal" tabindex="-1" aria-labelledby="noModuleAccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" id="noModuleAccessModalHeader" style="background-color: #dc3545;">
                <h5 class="modal-title" id="noModuleAccessModalLabel">
                    <i class="fas fa-lock me-2"></i>Access denied
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    You don't currently have access to this module. Please contact your administrator if you need access for your role.
                </p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary" id="noModuleAccessCloseBtn" data-bs-dismiss="modal" style="border-color: transparent;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Announce Modal -->
<div class="modal fade" id="announceModal" tabindex="-1" aria-labelledby="announceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="announceModalLabel"><i class="fas fa-bullhorn me-2"></i> Create System Announcement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="announceForm" method="POST" action="{{ route('announcements.store', [], false) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">What is the event? (Title)</label>
                            <input type="text" name="what" class="form-control" placeholder="e.g. Annual Fire Safety Drill 2026" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">When? (Date & Time)</label>
                            <input type="datetime-local" name="when" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Where will it take place?</label>
                            <input type="text" name="where" class="form-control" placeholder="e.g. Main School Quadrangle" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Why? (Small Description)</label>
                            <textarea name="why" class="form-control" rows="3" placeholder="Explain the purpose of this announcement..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Upload Poster/Flyer (Panoramic/Widescreen 16:9 recommended)</label>
                            <p class="text-info small mb-2"><i class="fas fa-info-circle me-1"></i> Note: Upload a landscape image for best results (maximum file size: 2MB).</p>
                            <input type="file" name="image" class="form-control" accept="image/*" required onchange="previewImage(this)">
                            <div id="imagePreview" class="mt-3 d-none">
                                <p class="small text-muted mb-2">Image Preview:</p>
                                <div style="width: 100%; padding-top: 25%; position: relative; overflow: hidden; border-radius: 8px; border: 1px solid #ddd; background: #fff;">
                                    <img id="previewImg" src="#" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitAnnounce">
                        <i class="fas fa-paper-plane me-2"></i> Post Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentSchoolDetail = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderSchoolAssignmentPanels(data) {
        const availableMenu = document.getElementById('availableSchoolUsersMenu');
        const schoolAccountWrap = document.getElementById('schoolAccountUsersList');
        if (!availableMenu || !schoolAccountWrap) return;

        const availableUsers = Array.isArray(data.available_users) ? data.available_users : [];
        const schoolAccountUsers = Array.isArray(data.school_account_users) ? data.school_account_users : [];

        if (!availableUsers.length) {
            availableMenu.innerHTML = '<div class="text-muted small px-2 py-1">No available users.</div>';
        } else {
            availableMenu.innerHTML = availableUsers.map((u) => {
                const roleLabel = u.role === 'viewer' ? 'Viewer' : 'Contributor';
                return `
                    <div class="border rounded p-2">
                        <div class="fw-semibold">${escapeHtml(u.name)} <span class="text-muted">(${escapeHtml(roleLabel)})</span></div>
                        <div class="text-muted mb-2">${escapeHtml(u.email)}</div>
                        <button type="button" class="btn btn-sm btn-dark assign-school-user-btn" data-user-id="${u.id}">Assign</button>
                    </div>
                `;
            }).join('');
        }

        if (!schoolAccountUsers.length) {
            schoolAccountWrap.textContent = 'No assigned school account users.';
        } else {
            schoolAccountWrap.innerHTML = schoolAccountUsers.map((u) => `${escapeHtml(u.name)} (${escapeHtml(u.email)})`).join('<br>');
        }

        availableMenu.querySelectorAll('.assign-school-user-btn').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!currentSchoolDetail) return;
                const userId = btn.getAttribute('data-user-id');
                if (!userId) return;

                await assignSchoolUser(currentSchoolDetail.id, userId);
            });
        });
    }

    async function assignSchoolUser(schoolId, userId) {
        try {
            const resp = await fetch(`/schools/${schoolId}/users/${userId}/assign`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            });
            const data = await resp.json();
            if (!resp.ok || !data.success) {
                Swal.fire('Error', data.message || 'Failed to assign user.', 'error');
                return;
            }
            await viewSchoolDetails(schoolId, true);
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Failed to assign user.', 'error');
        }
    }

    async function removeSchoolAssignment(schoolId, userId) {
        try {
            const resp = await fetch(`/schools/${schoolId}/users/${userId}/assign`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (!resp.ok || !data.success) {
                Swal.fire('Error', data.message || 'Failed to remove assignment.', 'error');
                return;
            }
            await viewSchoolDetails(schoolId, true);
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Failed to remove assignment.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const links = document.querySelectorAll('a.module-card-link[data-module]');
        const modalEl = document.getElementById('noModuleAccessModal');
        const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        const headerEl = document.getElementById('noModuleAccessModalHeader');
        const closeBtn = document.getElementById('noModuleAccessCloseBtn');

        function applyThemeColor(color) {
            const safeColor = (typeof color === 'string' && color.trim()) ? color.trim() : '#dc3545';
            if (headerEl) headerEl.style.backgroundColor = safeColor;
            if (closeBtn) closeBtn.style.backgroundColor = safeColor;
        }

        links.forEach(a => {
            a.addEventListener('click', function (e) {
                const canAccess = this.getAttribute('data-can-access') === '1';
                if (canAccess) return;
                e.preventDefault();
                applyThemeColor(this.getAttribute('data-theme-color'));
                if (modal) modal.show();
            });
        });
    });

    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const maxBytes = 2 * 1024 * 1024; // 2MB

        if (input.files && input.files[0]) {
            if (input.files[0].size > maxBytes) {
                input.value = '';
                preview.classList.add('d-none');
                Swal.fire('File Too Large', 'Please upload an image that is 2MB or smaller.', 'warning');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.classList.remove('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const announceForm = document.getElementById('announceForm');
        if (!announceForm) {
            return;
        }

        announceForm.addEventListener('submit', function (event) {
            const fileInput = announceForm.querySelector('input[name="image"]');
            const submitBtn = document.getElementById('submitAnnounce');
            const maxBytes = 2 * 1024 * 1024; // 2MB

            if (fileInput && fileInput.files && fileInput.files[0] && fileInput.files[0].size > maxBytes) {
                event.preventDefault();
                Swal.fire('File Too Large', 'Please upload an image that is 2MB or smaller.', 'warning');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Posting...';
            }
        });
    });

    function deleteAnnouncement(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This announcement will be removed from the dashboard.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/announcements/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Removed!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    /* Tab Switching Logic (Phase 2) */
    function switchDashboardTab(tabName) {
        const complianceBtn = document.getElementById('complianceTabBtn');
        const schoolsBtn = document.getElementById('schoolsTabBtn');
        const complianceContent = document.getElementById('complianceTabContent');
        const schoolsContent = document.getElementById('schoolsTabContent');

        if (tabName === 'schools') {
            complianceBtn.classList.remove('active');
            schoolsBtn.classList.add('active');
            complianceContent.classList.add('d-none');
            complianceContent.classList.remove('tab-content-active');
            schoolsContent.classList.remove('d-none');
            schoolsContent.classList.add('tab-content-active');

            const schoolSearchInput = document.getElementById('schoolSearchInput');
            if (schoolSearchInput) {
                schoolSearchInput.value = '';
                schoolSearchInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            localStorage.setItem('activeDashboardTab', 'schools');
        } else {
            schoolsBtn.classList.remove('active');
            complianceBtn.classList.add('active');
            schoolsContent.classList.add('d-none');
            schoolsContent.classList.remove('tab-content-active');
            complianceContent.classList.remove('d-none');
            complianceContent.classList.add('tab-content-active');
            localStorage.setItem('activeDashboardTab', 'compliance');
        }
    }

    function focusSchoolCardInSchoolsTab(schoolId, shouldScroll, shouldHighlight) {
        const selector = `.school-item-col[data-school-id="${schoolId}"] .school-card`;
        const schoolCard = document.querySelector(selector);
        if (!schoolCard) return false;

        if (shouldScroll) {
            schoolCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (shouldHighlight) {
            schoolCard.classList.add('school-card-focus-highlight');
            setTimeout(() => schoolCard.classList.remove('school-card-focus-highlight'), 4800);
        }

        return true;
    }

    // Persist active tab on reload
    document.addEventListener('DOMContentLoaded', function() {
        const savedTab = localStorage.getItem('activeDashboardTab');
        if (savedTab === 'schools' && @json($canShowSchoolTab)) {
            switchDashboardTab('schools');
        }

        const params = new URLSearchParams(window.location.search);
        const requestedTab = params.get('tab');
        const targetSchoolId = params.get('school_id');
        const shouldHighlight = params.get('highlight') === '1';
        const shouldScroll = params.get('scroll') === '1';

        if (requestedTab === 'schools' && @json($canShowSchoolTab)) {
            switchDashboardTab('schools');
        }

        if (requestedTab === 'schools' && targetSchoolId) {
            [0, 160, 420, 860].forEach((delay) => {
                setTimeout(() => {
                    focusSchoolCardInSchoolsTab(targetSchoolId, shouldScroll, shouldHighlight);
                }, delay);
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('contributorSchoolForm');
        if (!form) return;

        const unlockBtn = document.getElementById('contributorUnlockSchoolBtn');
        const cancelBtn = document.getElementById('contributorCancelSchoolBtn');
        const saveBtn = document.getElementById('contributorSaveSchoolBtn');
        const inputs = Array.from(form.querySelectorAll('.contributor-school-input'));
        const initialData = {};

        inputs.forEach((input) => {
            initialData[input.name] = input.value;
        });

        const setEditable = (editable) => {
            inputs.forEach((input) => {
                input.disabled = !editable;
            });

            if (unlockBtn) unlockBtn.classList.toggle('d-none', editable);
            if (cancelBtn) cancelBtn.classList.toggle('d-none', !editable);
            if (saveBtn) saveBtn.classList.toggle('d-none', !editable);
        };

        if (unlockBtn) {
            unlockBtn.addEventListener('click', function () {
                setEditable(true);
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                inputs.forEach((input) => {
                    input.value = initialData[input.name] ?? '';
                });
                setEditable(false);
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const payload = Object.fromEntries(new FormData(form).entries());
            const submitButton = document.getElementById('contributorSaveSchoolBtn');
            if (submitButton) submitButton.disabled = true;

            fetch("{{ route('schools.assigned.update') }}", {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to update school information.');
                }

                inputs.forEach((input) => {
                    initialData[input.name] = input.value;
                });

                setEditable(false);
                Swal.fire('Updated', data.message || 'School information updated.', 'success');
            })
            .catch((error) => {
                Swal.fire('Error', error.message || 'Failed to update school information.', 'error');
            })
            .finally(() => {
                if (submitButton) submitButton.disabled = false;
            });
        });
    });

    /* Unified Schools Functionality (Phase 2) */
    function openAddSchoolModal() {
        const addModal = new bootstrap.Modal(document.getElementById('addSchoolModal'));
        addModal.show();
    }

    // Handle Add School Submission
    document.getElementById('addSchoolForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';

        fetch("{{ route('schools.unified-store') }}", {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire('Success!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // View Details Logic
    async function viewSchoolDetails(id, keepOpen = false) {
        const viewModalEl = document.getElementById('viewSchoolModal');
        const viewModal = new bootstrap.Modal(viewModalEl);

        // Show loading state or reset content
        document.getElementById('schoolDetailName').innerText = 'Loading...';

        try {
            const res = await fetch(`/schools/details/${id}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            const s = data.school;
            const m = data.modules;
            currentSchoolDetail = {
                ...s,
                school_account_user: data.school_account_user || null,
            };

            // Populate Basic Info
            document.getElementById('schoolDetailName').innerText = s.school_name;
            document.getElementById('detail_id').innerText = s.school_id || s.school_id_number || 'N/A';
            document.getElementById('detail_name').innerText = s.school_name;
            document.getElementById('detail_address').innerText = s.address;
            document.getElementById('detail_head').innerText = data.school_head_user?.name || s.school_head || '-';
            document.getElementById('detail_coordinator').innerText = data.school_drrm_user?.name || s.drrm_coordinator || s.school_drrm_coordinator || '-';

            // Additional Info
            document.getElementById('detail_head_contact').innerText = s.contact_number || 'N/A';
            document.getElementById('detail_coord_contact').innerText = s.contact_number_2 || 'N/A';
            document.getElementById('detail_district').innerText = s.district || 'N/A';
            document.getElementById('detail_division').innerText = s.division || 'N/A';
            document.getElementById('detail_region').innerText = s.region || 'N/A';
            document.getElementById('detail_students').innerText = (s.number_students ?? 0);
            document.getElementById('detail_personnel').innerText = (s.number_personnel ?? 0);
            document.getElementById('detail_gates').innerText = (s.number_gates ?? 0);
            document.getElementById('detail_engineer_last_inspection_date').innerText = formatSchoolDate(s.engineer_last_inspection_date);
            document.getElementById('detail_resources').innerText = s.emergency_resources || 'None specified';
            renderSchoolAssignmentPanels(data);

            // Configure Module Buttons
            configureModuleButton('btn_fire_safety', m.fire_safety, `/fire-safety/buildings?school_id=${s.id}`);
            configureModuleButton('btn_typhoon', m.typhoon_flood, `/typhoon/evacuation-center/${s.id}`);
            configureModuleButton('btn_incident', m.incident_checklist, null, () => showIncidentModuleChoice(s));
            configureModuleButton('btn_comprehensive', m.comprehensive_school_safety, `/comprehensive-school-safety/schools/${s.id}/assessments`);
            configureModuleButton('btn_damage_assessment', m.damage_assessment, `/damage-assessment/dashboard?school_id=${s.id}`);
            configureModuleButton('btn_hazard', m.hazard_mapping, `#`);

            // Setup Edit Button
            const editBtn = document.getElementById('editSchoolBtn');
            if (editBtn) {
                editBtn.onclick = () => openEditModal(s);
            }
            const delBtn = document.getElementById('deleteSchoolBtn');
            if (delBtn) {
                delBtn.style.display = @json($isAdmin) ? '' : 'none';
                delBtn.onclick = () => confirmDeleteSchool(s);
            }

            if (keepOpen) {
                const existing = bootstrap.Modal.getInstance(viewModalEl);
                if (!existing) viewModal.show();
            } else {
                viewModal.show();
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Failed to load school details.', 'error');
        }
    }

    function formatSchoolDate(value) {
        if (!value) {
            return 'N/A';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(date);
    }

    function configureModuleButton(id, isActive, url, action = null) {
        const btn = document.getElementById(id);
        if (isActive) {
            btn.classList.remove('disabled');
            btn.classList.add('btn-outline-dark');
            btn.classList.remove('btn-light', 'text-muted');
            if (typeof action === 'function') {
                btn.onclick = action;
            } else {
                btn.onclick = () => window.location.href = url;
            }
        } else {
            btn.classList.add('disabled');
            btn.classList.add('btn-light', 'text-muted');
            btn.classList.remove('btn-outline-dark');
            btn.onclick = (e) => e.preventDefault();
        }
    }

    function showIncidentModuleChoice(school) {
        const modalEl = document.getElementById('incidentModuleChoiceModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;
        const parentModalEl = document.getElementById('viewSchoolModal');

        const schoolNameEl = document.getElementById('incidentModuleChoiceSchoolName');
        const incidentBtn = document.getElementById('incidentModuleChoiceIncidentBtn');
        const complianceBtn = document.getElementById('incidentModuleChoiceComplianceBtn');
        const baseUrl = '{{ route('incidents.dashboard') }}';

        if (schoolNameEl) {
            schoolNameEl.textContent = school?.school_name || 'Selected school';
        }

        const openWithTab = (tabName) => {
            const params = new URLSearchParams({
                school_id: String(school.id),
                open_log: '1',
                log_tab: tabName,
            });
            window.location.href = `${baseUrl}?${params.toString()}`;
        };

        if (incidentBtn) {
            incidentBtn.onclick = () => openWithTab('incident');
        }

        if (complianceBtn) {
            complianceBtn.onclick = () => openWithTab('compliance');
        }

        const showChoiceModal = () => bootstrap.Modal.getOrCreateInstance(modalEl).show();

        if (parentModalEl) {
            const parentModal = bootstrap.Modal.getInstance(parentModalEl);
            if (parentModal) {
                parentModalEl.addEventListener('hidden.bs.modal', showChoiceModal, { once: true });
                parentModal.hide();
                return;
            }
        }

        showChoiceModal();
    }

    function openEditModal(school) {
        // Close view modal first
        bootstrap.Modal.getInstance(document.getElementById('viewSchoolModal')).hide();

        const form = document.getElementById('editSchoolForm');
        form.action = `/schools/update/${school.id}`;

        // Populate form
        form.querySelector('[name="school_name"]').value = school.school_name;
        form.querySelector('[name="school_id"]').value = school.school_id || '';
        form.querySelector('[name="address"]').value = school.address;
        const headInput = form.querySelector('[name="school_head"]');
        const drrmInput = form.querySelector('[name="drrm_coordinator"]');
        if (headInput) headInput.value = school.school_head || '';
        if (drrmInput) drrmInput.value = school.drrm_coordinator || '';
        form.querySelector('[name="district"]').value = school.district || '';
        form.querySelector('[name="division"]').value = school.division || '';
        form.querySelector('[name="region"]').value = school.region || '';
        form.querySelector('[name="contact_number"]').value = school.contact_number || '';
        form.querySelector('[name="contact_number_2"]').value = school.contact_number_2 || '';
        form.querySelector('[name="number_students"]').value = school.number_students || 0;
        form.querySelector('[name="number_personnel"]').value = school.number_personnel || 0;
        form.querySelector('[name="number_gates"]').value = school.number_gates || 0;
        form.querySelector('[name="engineer_last_inspection_date"]').value = school.engineer_last_inspection_date || '';
        form.querySelector('[name="emergency_resources"]').value = school.emergency_resources || '';

        new bootstrap.Modal(document.getElementById('editSchoolModal')).show();
    }

    async function confirmDeleteSchool(school) {
        const doOpenPasswordModal = () => {
            // Hide view modal first to avoid focus/overlay conflicts.
            const viewEl = document.getElementById('viewSchoolModal');
            if (viewEl && bootstrap?.Modal) {
                const viewInst = bootstrap.Modal.getInstance(viewEl);
                viewInst?.hide();
            }

            const modalEl = document.getElementById('deleteSchoolPasswordModal');
            const nameEl = document.getElementById('deleteSchoolName');
            const idEl = document.getElementById('deleteSchoolIdInput');
            const pwdEl = document.getElementById('deleteSchoolPasswordInput');
            const errEl = document.getElementById('deleteSchoolPasswordError');
            const confirmBtn = document.getElementById('deleteSchoolConfirmBtn');

            if (nameEl) nameEl.textContent = school.school_name || '—';
            if (idEl) idEl.value = school.id;
            if (pwdEl) pwdEl.value = '';
            if (errEl) errEl.style.display = 'none';
            if (confirmBtn) confirmBtn.disabled = false;

            if (modalEl && bootstrap?.Modal) {
                new bootstrap.Modal(modalEl).show();
                setTimeout(() => pwdEl?.focus(), 100);
            }
        };

        // Step 1: simple confirmation first (no typing), then open password modal.
        if (typeof Swal !== 'undefined') {
            const first = await Swal.fire({
                title: 'Delete this school?',
                text: `This will delete: ${school.school_name}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Continue'
            });
            if (!first.isConfirmed) return;
        }

        doOpenPasswordModal();
    }

    // One-time handler for modal confirm button
    (function () {
        const confirmBtn = document.getElementById('deleteSchoolConfirmBtn');
        const pwdEl = document.getElementById('deleteSchoolPasswordInput');
        const idEl = document.getElementById('deleteSchoolIdInput');
        const errEl = document.getElementById('deleteSchoolPasswordError');
        const modalEl = document.getElementById('deleteSchoolPasswordModal');

        if (!confirmBtn || !pwdEl || !idEl || !errEl || !modalEl) return;

        confirmBtn.addEventListener('click', async () => {
            const schoolId = idEl.value;
            const pwd = (pwdEl.value || '').trim();

            if (!pwd) {
                errEl.style.display = 'block';
                return;
            }

            confirmBtn.disabled = true;

            try {
                const resp = await fetch(`/schools/delete/${schoolId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password: pwd })
                });

                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    errEl.textContent = data.message || 'Failed to delete school.';
                    errEl.style.display = 'block';
                    confirmBtn.disabled = false;
                    return;
                }

                // Hide modal and refresh after success.
                const modalInst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInst.hide();
                location.reload();
            } catch (e) {
                console.error(e);
                errEl.textContent = 'Failed to delete school.';
                errEl.style.display = 'block';
                confirmBtn.disabled = false;
            }
        });
    })();

    document.getElementById('editSchoolForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;

        fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire('Updated!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.disabled = false;
            }
        });
    });
</script>
@endpush
@endsection
