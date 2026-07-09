@extends('layouts.app')

@section('title', 'Evacuation Center Intelligence')
@section('hide_main_nav', '1')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --bg-dark: #0a1128;
        --card-bg: #ffffff;
        --card-header-bg: #0f2154ff;
        --accent-blue: #00d2ff;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --glass-border: rgba(0, 0, 0, 0.05);
    }

    body {
        background-color: var(--bg-dark) !important;
        background-image: radial-gradient(circle at 50% 50%, #112240 0%, #0a1128 100%);
        color: var(--text-dark);
        font-family: 'Space Grotesk', 'Inter', sans-serif;
    }

    h1, h2, h3, h4, h5, .card-header-custom, .stat-value, .fw-bold {
        font-family: 'Rajdhani', sans-serif;
        letter-spacing: 0.5px;
    }

    .container-fluid {
        padding: 2rem;
    }

    .dashboard-card {
        background: var(--card-bg);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        transition: transform 0.2s ease;
        height: 100%;
        color: var(--text-dark);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .card-header-custom {
        background: var(--card-header-bg);
        color: #ffffff;
        padding: 1rem 1.5rem;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        border-bottom: 2px solid rgba(0,0,0,0.1);
    }

    .profile-property {
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 1px;
    }

    .profile-value {
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1.25rem;
    }

    .table-custom thead th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        padding: 1rem;
    }

    .table-custom tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }

    .stat-icon-small {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }

    /* System Status Pulse */
    .status-pulse {
        width: 12px;
        height: 12px;
        background: #22c55e;
        border-radius: 50%;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    
    .btn-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    /* Centered Navigation */
    .header-nav-center {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        background: rgba(255, 255, 255, 0.05);
        padding: 0.5rem 2rem;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
    }

    .nav-link-custom {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-family: 'Rajdhani', sans-serif;
        font-weight: 700;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        background: none;
        border: none;
        padding: 0.5rem 0.25rem;
    }

    .nav-link-custom:hover {
        color: var(--accent-blue);
    }

    .nav-link-custom.active {
        color: var(--accent-blue);
        text-shadow: 0 0 15px rgba(0, 210, 255, 0.5);
    }

    .notif-btn-custom {
        position: relative;
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.25rem;
        transition: all 0.3s ease;
    }

    .notif-btn-custom:hover, .notif-btn-custom.active {
        color: var(--accent-blue);
    }

    .school-btn-custom {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.25rem;
        transition: all 0.3s ease;
        background: none;
        border: none;
    }

    .school-btn-custom:hover, .school-btn-custom.active {
        color: var(--accent-blue);
    }

    .profile-menu-btn {
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        border-radius: 999px;
        padding: 0.45rem 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }

    .profile-menu-btn:hover,
    .profile-menu-btn:focus {
        color: var(--accent-blue);
        border-color: rgba(0, 210, 255, 0.55);
    }

    .profile-menu .dropdown-menu {
        background: #0f1b3f;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 12px;
        min-width: 210px;
    }

    .profile-menu .dropdown-item {
        color: #dbeafe;
        font-weight: 600;
    }

    .profile-menu .dropdown-item:hover {
        background: rgba(0, 210, 255, 0.15);
        color: #fff;
    }

    .header-action-btn {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Unified Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        {{-- Left Side --}}
        <div class="d-flex align-items-center" style="width: 30%;">
            <a href="{{ route('typhoon.dashboard') }}" class="btn btn-outline-info border-0 me-3 shadow-sm" style="background: rgba(255,255,255,0.05);">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div>
                <h1 class="h3 mb-0 fw-bold text-white">
                    {{ $ec->school_name ?? 'NOT DEFINED' }}
                </h1>
                <div class="small text-info opacity-75 fw-bold text-uppercase tracking-wider">EVACUATION HUB</div>
            </div>
        </div>

        {{-- Centered Navigation --}}
        <div class="header-nav-center">
            <a href="{{ route('typhoon.dashboard') }}" class="nav-link-custom">
                Dashboard
            </a>
            <a href="{{ route('typhoon.notifications') }}" class="notif-btn-custom" title="Notifications">
                <i class="fas fa-bell"></i>
                @php
                    $unreadCount = \App\Models\FireSafetyNotification::forCompliance('typhoon_flood')->unread()->count();
                @endphp
                @if($unreadCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.6rem; padding: 0.35em 0.65em;">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </a>
            <button type="button" class="school-btn-custom active" data-bs-toggle="modal" data-bs-target="#chooseSchoolModal" title="Choose Evacuation Center">
                <i class="fas fa-school"></i>
            </button>
        </div>

        {{-- Right Side --}}
        <div class="d-flex align-items-center gap-3 justify-content-end" style="width: 30%;">
            <button class="btn btn-success px-3 fw-bold shadow-lg header-action-btn me-1" onclick="document.getElementById('evacPrintModal').style.display='flex'" title="Print Evacuation Center">
                <i class="fas fa-print me-2"></i>PRINT REPORT
            </button>
            <button type="button" class="btn btn-primary px-3 fw-bold shadow-lg header-action-btn" data-bs-toggle="modal" data-bs-target="#updateCenterStatusModal">
                <i class="fas fa-edit me-2"></i>UPDATE SITE
            </button>
            <div class="dropdown profile-menu">
                <button class="btn profile-menu-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                    <span>{{ auth()->user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('users.index') }}">
                            <i class="fas fa-user-cog me-2"></i>User Account
                        </a>
                    </li>
                    @if(auth()->user()->role === 'admin')
                        <li>
                            <a class="dropdown-item" href="{{ route('activity-logs.index') }}">
                                <i class="fas fa-history me-2"></i>Logs
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- Profile Row (Full Width) --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="dashboard-card border-0 shadow-lg" style="background: linear-gradient(135deg, #0f2154 0%, #1a3a8a 100%); color: white; border-radius: 16px; overflow: hidden;">
                <div class="row g-0 align-items-stretch">
                    <div class="col-md-3 p-4 border-end border-white-50 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-primary bg-opacity-25 p-3 rounded-circle me-3">
                                <i class="fas fa-id-card-alt fa-2x text-info"></i>
                            </div>
                            <div>
                                <div class="profile-property text-white-50 mb-0" style="font-size: 0.75rem; letter-spacing: 1px;">Identification Code</div>
                                <div class="h5 mb-0 fw-bold text-white">{{ $ec->identification ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-danger bg-opacity-25 p-3 rounded-circle me-3">
                                <i class="fas fa-map-marker-alt fa-2x text-danger"></i>
                            </div>
                            <div>
                                <div class="profile-property text-white-50 mb-0" style="font-size: 0.75rem; letter-spacing: 1px;">Location / Address</div>
                                <div class="small mb-0 fw-bold text-white">{{ $ec->location ?? $ec->school->address ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 p-4 border-end border-white-50 d-flex flex-column justify-content-center text-center">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="profile-property text-white-50 mb-1" style="font-size: 0.8rem; letter-spacing: 1.5px;">Max Capacity</div>
                                <div class="h2 mb-0 fw-bold text-white">{{ $ec->capacity ?? 0 }} <small class="text-white-50 fs-6">Individuals</small></div>
                            </div>
                            <div class="col-6">
                                <div class="profile-property text-white-50 mb-1" style="font-size: 0.8rem; letter-spacing: 1.5px;">Current Load</div>
                                <div class="h2 mb-0 fw-bold text-info">{{ $currentOccupancy }} <small class="text-white-50 fs-6">Individuals</small></div>
                            </div>
                        </div>
                        <div class="px-4">
                            @php
                                $loadPercent = $ec->capacity > 0 ? min(round(($currentOccupancy / $ec->capacity) * 100), 100) : 0;
                            @endphp
                            <div class="progress" style="height: 14px; background: rgba(255,255,255,0.1); border-radius: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);">
                                <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" style="width: {{ $loadPercent }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 px-1">
                                <div class="small text-white-50 fw-bold text-uppercase" style="font-size: 0.65rem;">Resource Utilization</div>
                                <div class="small text-white-50 fw-bold text-uppercase" style="font-size: 0.65rem;">{{ $loadPercent }}% Capacity Limit</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 p-4 d-flex flex-column justify-content-center text-center bg-black bg-opacity-10">
                        <div class="profile-property text-white-50 mb-2" style="font-size: 0.8rem; letter-spacing: 1.5px;">Site Readiness Status</div>
                        @if($ec->usage_status === 'full')
                            <div class="badge bg-danger shadow-sm px-4 py-3 h5 mb-3 w-100 fw-bold" style="border: 1px solid rgba(255,255,255,0.2);">AT CAPACITY</div>
                        @elseif($ec->usage_status === 'occupied')
                            <div class="badge bg-primary shadow-sm px-4 py-3 h5 mb-3 w-100 fw-bold" style="border: 1px solid rgba(255,255,255,0.2);"> STANDBY</div>
                        @elseif($ec->usage_status === 'decamp')
                            <div class="badge shadow-sm px-4 py-3 h5 mb-3 w-100 fw-bold" style="background-color: #6f42c1; border: 1px solid rgba(255,255,255,0.2);">DECAMP / CLOSING</div>
                        @else
                            <div class="badge bg-success shadow-sm px-4 py-3 h5 mb-3 w-100 fw-bold" style="border: 1px solid rgba(255,255,255,0.2);">CLEARED / READY</div>
                        @endif
                        
                        <div class="bg-white bg-opacity-10 p-2 rounded small text-info fw-bold" style="border: 1px dashed rgba(255,255,255,0.2);">
                            <i class="fas fa-boxes me-2"></i> {{ Str::limit($ec->emergency_resources ?? 'No inventory data encoded', 45) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Current Occupants + Registry History --}}
        <div class="col-lg-12">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="dashboard-card shadow-lg border-0 h-100" style="min-height: 520px;">
                        <div class="card-header-custom py-3 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-house-user me-2"></i>Current Occupants</span>
                            <span class="badge bg-primary bg-opacity-10 text-info px-3 py-2" style="font-size: 0.7rem;">ACTIVE</span>
                        </div>
                        <div class="table-responsive" style="height: calc(100% - 55px); overflow-y: auto;">
                            <table class="table table-custom table-hover mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th class="ps-4">Family ID</th>
                                        <th>Head of Family</th>
                                        <th class="text-center">Members</th>
                                        <th class="pe-4">Needs</th>
                                        <th class="text-center pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($families->whereNull('checked_out_at') as $family)
                                        <tr>
                                            <td class="ps-4"><span class="badge bg-dark">#{{ $family->id }}</span></td>
                                            <td><div class="fw-bold fs-6 text-primary">{{ $family->head_family_name }}</div><small class="text-muted">{{ $family->created_at->format('M d, Y h:i A') }}</small></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border p-2" style="min-width: 35px;">{{ $family->members_count }}</span></td>
                                            <td class="pe-4"><small class="text-truncate d-block" style="max-width: 180px;" title="{{ $family->needs_summary }}">{{ $family->needs_summary ?: '—' }}</small></td>
                                            <td class="text-center pe-4">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateFamilyModal{{ $family->id }}">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <form id="decampFamilyForm{{ $family->id }}" method="POST" action="{{ route('typhoon.families.decamp', $family->id) }}" onsubmit="return confirm('Mark this family as decamped?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-person-walking-arrow-right"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5 opacity-50">
                                                <i class="fas fa-house-user fa-3x mb-3 text-muted"></i>
                                                <p class="h6 mb-0">No families currently taking cover.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="dashboard-card shadow-lg border-0 h-100" style="min-height: 520px;">
                        <div class="card-header-custom py-3 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-archive me-2"></i>Registry History</span>
                            <span class="badge bg-secondary bg-opacity-25 text-white px-3 py-2" style="font-size: 0.7rem;">DECAMPED</span>
                        </div>
                        <div class="table-responsive" style="height: calc(100% - 55px); overflow-y: auto;">
                            <table class="table table-custom table-hover mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th class="ps-4">Family ID</th>
                                        <th>Head of Family</th>
                                        <th class="text-center">Members</th>
                                        <th class="pe-4">Decamped At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($families->whereNotNull('checked_out_at') as $family)
                                        <tr>
                                            <td class="ps-4"><span class="badge bg-dark">#{{ $family->id }}</span></td>
                                            <td><div class="fw-bold fs-6 text-primary">{{ $family->head_family_name }}</div><small class="text-muted">{{ $family->created_at->format('M d, Y h:i A') }}</small></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border p-2" style="min-width: 35px;">{{ $family->members_count }}</span></td>
                                            <td class="pe-4"><small>{{ optional($family->checked_out_at)->format('M d, Y h:i A') }}</small></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5 opacity-50">
                                                <i class="fas fa-history fa-3x mb-3 text-muted"></i>
                                                <p class="h6 mb-0">No decamped family records yet.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@foreach($families as $family)
    @if(!$family->checked_out_at)
        <div class="modal fade" id="updateFamilyModal{{ $family->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('typhoon.families.update', $family->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-content shadow">
                        <div class="modal-header" style="background-color: var(--card-header-bg); color: white;">
                            <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-info"></i>UPDATE FAMILY DETAILS</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4 text-dark">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-muted small mb-0">HEAD OF FAMILY</label>
                                    <span class="badge bg-dark">Family ID #{{ $family->id }}</span>
                                </div>
                                <input type="text" name="head_family_name" class="form-control" value="{{ $family->head_family_name }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small">COLLECTIVE FAMILY NEEDS</label>
                                <div class="family-needs-builder" data-family-needs-builder="edit-{{ $family->id }}" data-need-options='@json($familyNeedOptions ?? [])' data-existing-needs='@json($family->needs->map(function ($need) { return ["need_name" => $need->need_name, "quantity" => $need->quantity, "is_custom" => $need->is_custom]; }))'></div>
                                <small class="text-muted d-block mt-2">Choose a need and quantity. Selecting <strong>Others Please Specify</strong> will reveal a custom need field.</small>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="has_pregnant" value="1" id="pregnant{{ $family->id }}" @checked($family->has_pregnant)><label class="form-check-label" for="pregnant{{ $family->id }}">Pregnant</label></div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="has_pwd" value="1" id="pwd{{ $family->id }}" @checked($family->has_pwd)><label class="form-check-label" for="pwd{{ $family->id }}">PWD</label></div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="has_senior" value="1" id="senior{{ $family->id }}" @checked($family->has_senior)><label class="form-check-label" for="senior{{ $family->id }}">Senior</label></div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="has_lactating" value="1" id="lactating{{ $family->id }}" @checked($family->has_lactating)><label class="form-check-label" for="lactating{{ $family->id }}">Lactating</label></div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="has_child_under5" value="1" id="child{{ $family->id }}" @checked($family->has_child_under5)><label class="form-check-label" for="child{{ $family->id }}">Child Under 5</label></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-danger fw-bold" onclick="if(confirm('Mark this family as decamped?')) { document.getElementById('decampFamilyForm{{ $family->id }}').submit(); }">DECAMP FAMILY</button>
                            <div>
                                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">CANCEL</button>
                                <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">SAVE CHANGES</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

{{-- Update status / reports modal --}}
<div class="modal fade" id="updateCenterStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('typhoon.evacuation-center.update', $ec->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-content shadow">
                <div class="modal-header" style="background-color: var(--card-header-bg); color: white;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-sync-alt me-2 text-info"></i>UPDATE SITE INTELLIGENCE</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-dark">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">SITE OPERATIONAL STATUS</label>
                        <select name="usage_status" class="form-select form-select-lg">
                            <option value="cleared" @selected($ec->usage_status === 'cleared')>CLEARED / STANDBY</option>
                            <option value="occupied" @selected($ec->usage_status === 'occupied')>OCCUPIED / ACTIVE</option>
                        </select>
                        <small class="text-muted">FULL and DECAMP are system-derived based on occupancy and family decamp records.</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">SITE CAPACITY</label>
                        <input type="number" name="capacity" class="form-control form-control-lg" min="0" value="{{ old('capacity', $ec->capacity) }}" placeholder="Enter evacuation capacity">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">RESOURCE INVENTORY SUMMARY</label>
                        <textarea name="emergency_resources" rows="2" class="form-control" placeholder="e.g. 50 Hygiene Kits, 100 Blankets">{{ old('emergency_resources', $ec->emergency_resources) }}</textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold text-muted small">LATEST SITUATION REPORT (SITREP)</label>
                        <textarea name="reports_status" rows="3" class="form-control" placeholder="Describe current issues, damages, or requests...">{{ old('reports_status', $ec->reports_status) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">SAVE CHANGES</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ===================== EVACUATION CENTER PRINT OVERLAY ===================== --}}
<div id="evacPrintModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.92); z-index:9999; flex-direction:column; align-items:center; justify-content:flex-start; overflow-y:auto; padding: 1.5rem 0;">

    {{-- ACTION BUTTONS --}}
    <div id="printActionBar" style="display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem; width:1100px; max-width:96vw; justify-content:flex-end;">
        <span style="color:#8892b0; font-size:0.82rem; margin-right:auto; font-family:'Space Grotesk',sans-serif;">
            <i class="fas fa-info-circle me-1"></i> Previewing Evacuation Center Report. Click <strong style="color:#00d2ff;">Print / Save as PDF</strong> to continue.
        </span>
        <button onclick="printEvacCard()" style="background:#00d2ff; border:none; color:#0a1128; border-radius:8px; padding:0.6rem 1.5rem; font-weight:800; font-size:0.95rem; cursor:pointer; letter-spacing:0.5px; display:flex; align-items:center; gap:0.5rem; box-shadow:0 0 20px rgba(0,210,255,0.4);">
            <i class="fas fa-file-pdf"></i> Print / Save as PDF
        </button>
        <button onclick="document.getElementById('evacPrintModal').style.display='none'" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.25); color:#fff; border-radius:8px; padding:0.6rem 1.25rem; font-weight:700; font-size:0.95rem; cursor:pointer; display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    {{-- PRINT CARD --}}
    <div id="printCard" style="background: linear-gradient(135deg, #0a1128 0%, #112240 55%, #0d2137 100%); color: #e2e8f0; width: 1100px; max-width: 96vw; border-radius: 16px; box-shadow: 0 0 80px rgba(0,210,255,0.15); border: 1px solid rgba(0,210,255,0.2); font-family: 'Rajdhani', 'Space Grotesk', sans-serif; padding: 2.5rem; margin-bottom: 2rem;">

        {{-- Header with Logos --}}
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.8rem; padding-bottom:1.5rem; border-bottom:1px solid rgba(0,210,255,0.2);">
            <div style="display:flex; align-items:center; gap:1.25rem;">
                <img src="{{ asset('images/drrmis-logo-2.png') }}" alt="DRRMIS" style="height:65px; object-fit:contain;">
                <img src="{{ asset('images/What-Is-the-Difference-Between-DepEd-Seal-and-DepEd-Logo.png') }}" alt="DepEd" style="height:65px; object-fit:contain;">
                <img src="{{ asset('images/Layer-0-1.png') }}" alt="Logo" style="height:65px; object-fit:contain;">
                <div style="margin-left:0.5rem; padding-left:1.25rem; border-left:2px solid rgba(0,210,255,0.3);">
                    <div style="font-size:0.65rem; color:#8892b0; text-transform:uppercase; letter-spacing:2px;">Department of Education</div>
                    <div style="font-size:0.85rem; color:#ffffff; font-weight:700; letter-spacing:1px;">EVACUATION CENTER INTELLIGENCE REPORT</div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:1.5rem; font-weight:800; color:#00d2ff; letter-spacing:1px; line-height:1;">{{ $ec->school_name ?? $ec->identification }}</div>
                <div style="font-size:0.8rem; color:#8892b0; margin-top:0.35rem;">{{ $ec->location ?? $ec->school->address }}</div>
                <div style="font-size:0.65rem; color:#00d2ff; margin-top:0.2rem; font-weight:700; letter-spacing:1px;">📅 {{ now()->format('F d, Y | h:i A') }}</div>
            </div>
        </div>

        {{-- Site Profile Stats --}}
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1.25rem; margin-bottom:2rem;">
            <div style="background:rgba(0,210,255,0.08); border:1px solid rgba(0,210,255,0.2); border-radius:12px; padding:1.25rem; text-align:center;">
                <div style="font-size:0.65rem; color:#8892b0; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:0.4rem;">Operational Status</div>
                <div style="font-size:1.5rem; font-weight:800; color:#00d2ff; text-transform:uppercase;">{{ $ec->usage_status }}</div>
            </div>
            <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:1.25rem; text-align:center;">
                <div style="font-size:0.65rem; color:#8892b0; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:0.4rem;">Max Capacity</div>
                <div style="font-size:1.8rem; font-weight:800; color:#ffffff;">{{ $ec->capacity ?? '0' }}</div>
            </div>
            <div style="background:rgba(0,210,255,0.12); border:1px solid rgba(0,210,255,0.3); border-radius:12px; padding:1.25rem; text-align:center; box-shadow:0 0 15px rgba(0,210,255,0.1);">
                <div style="font-size:0.65rem; color:#00d2ff; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:0.4rem; font-weight:700;">Current Load</div>
                <div style="font-size:1.8rem; font-weight:800; color:#00d2ff;">{{ $currentOccupancy }}</div>
            </div>
            <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:1.25rem; text-align:center;">
                <div style="font-size:0.65rem; color:#8892b0; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:0.4rem;">Registry Count</div>
                <div style="font-size:1.8rem; font-weight:800; color:#ffffff;">{{ count($families) }}</div>
            </div>
        </div>

        {{-- Situation Report & Resources --}}
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:1.25rem;">
                <div style="font-size:0.7rem; color:#00d2ff; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:0.8rem; font-weight:700;">📑 Latest Situation Narrative</div>
                <div style="font-size:0.9rem; color:#e2e8f0; line-height:1.5; min-height:80px;">
                    {{ $ec->reports_status ?: 'No situation report provided.' }}
                </div>
            </div>
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:1.25rem;">
                <div style="font-size:0.7rem; color:#00d2ff; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:0.8rem; font-weight:700;">📦 Resource Shortages / Needs</div>
                <div style="font-size:0.9rem; color:#e2e8f0; line-height:1.5; min-height:80px;">
                    {{ $ec->emergency_resources ?: 'No resource shortages reported.' }}
                </div>
            </div>
        </div>

        {{-- Registry Table --}}
        <div>
            <div style="font-size:0.75rem; color:#00d2ff; text-transform:uppercase; letter-spacing:2px; font-weight:700; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid rgba(0,210,255,0.2);">📋 Master Evacuation Registry (Recent)</div>
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                <thead>
                    <tr style="background:rgba(0,210,255,0.12);">
                        <th style="padding:0.6rem 0.75rem; text-align:left; color:#00d2ff; text-transform:uppercase; font-size:0.65rem; letter-spacing:1px;">Head of Family</th>
                        <th style="padding:0.6rem 0.75rem; text-align:center; color:#00d2ff; text-transform:uppercase; font-size:0.65rem; letter-spacing:1px;">Members</th>
                        <th style="padding:0.6rem 0.75rem; text-align:left; color:#00d2ff; text-transform:uppercase; font-size:0.65rem; letter-spacing:1px;">Vulnerabilities</th>
                        <th style="padding:0.6rem 0.75rem; text-align:left; color:#00d2ff; text-transform:uppercase; font-size:0.65rem; letter-spacing:1px;">Entry Date</th>
                        <th style="padding:0.6rem 0.75rem; text-align:left; color:#00d2ff; text-transform:uppercase; font-size:0.65rem; letter-spacing:1px;">Needs</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(collect($families)->take(12) as $family)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                        <td style="padding:0.7rem 0.75rem; font-weight:700; color:#e2e8f0;">{{ $family->head_family_name }}</td>
                        <td style="padding:0.7rem 0.75rem; text-align:center; color:#ffffff;">{{ $family->members_count }}</td>
                        <td style="padding:0.7rem 0.75rem; color:#f59e0b; font-size:0.75rem;">
                            @php $v = []; 
                                if($family->has_pregnant) $v[]='PRG'; if($family->has_pwd) $v[]='PWD'; 
                                if($family->has_senior) $v[]='SNR'; if($family->has_lactating) $v[]='LAC';
                                if($family->has_child_under5) $v[]='CHD'; 
                            @endphp
                            {{ implode(', ', $v) ?: '—' }}
                        </td>
                        <td style="padding:0.7rem 0.75rem; color:#8892b0;">{{ $family->created_at->format('M d, Y') }}</td>
                        <td style="padding:0.7rem 0.75rem; color:#8892b0; font-size:0.75rem;">{{ Str::limit($family->needs_summary, 40) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; color:#8892b0; padding:2rem;">No registered families in this center.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div style="margin-top:2rem; padding-top:1rem; border-top:1px solid rgba(0,210,255,0.12); display:flex; justify-content:space-between; align-items:center; font-size:0.7rem; color:#8892b0;">
            <span>Validated by: ________________________ (DRRM Center Lead)</span>
            <span>Electronic Copy • Printed: {{ now()->format('M d, Y h:i A') }}</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function initializeFamilyNeedsBuilder(builder) {
        if (!builder || builder.dataset.initialized === '1') {
            return;
        }

        const needOptions = JSON.parse(builder.dataset.needOptions || '[]');
        const existingNeeds = JSON.parse(builder.dataset.existingNeeds || '[]');
        let rowIndex = 0;

        const buildOptions = (selectedValue = '') => {
            const options = ['<option value="">-- Select need --</option>'];

            needOptions.forEach((need) => {
                const selected = need === selectedValue ? ' selected' : '';
                options.push(`<option value="${need}"${selected}>${need}</option>`);
            });

            if (!needOptions.includes('Others Please Specify')) {
                const selected = selectedValue === 'Others Please Specify' ? ' selected' : '';
                options.push(`<option value="Others Please Specify"${selected}>Others Please Specify</option>`);
            }

            return options.join('');
        };

        const addRow = (need = {}, shouldFocus = false) => {
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 align-items-start family-need-row';
            row.dataset.rowIndex = String(rowIndex++);

            const selectedNeed = need.need_name || '';
            const isCustom = !!need.is_custom || (selectedNeed && !needOptions.includes(selectedNeed));
            const customNeedValue = isCustom ? selectedNeed : (need.custom_need || '');
            const quantityValue = need.quantity || 1;

            row.innerHTML = `
                <div class="col-md-6">
                    <select class="form-select family-need-select" name="needs[${row.dataset.rowIndex}][need_name]" required>
                        ${buildOptions(selectedNeed && !isCustom ? selectedNeed : (isCustom ? 'Others Please Specify' : ''))}
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control family-need-quantity" name="needs[${row.dataset.rowIndex}][quantity]" min="1" max="999" value="${quantityValue}" placeholder="Qty" required>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-danger w-100 family-need-remove">Remove</button>
                </div>
                <div class="col-12 family-need-custom-wrap ${isCustom ? '' : 'd-none'}">
                    <input type="text" class="form-control mt-1 family-need-custom" name="needs[${row.dataset.rowIndex}][custom_need]" placeholder="Please specify other need" value="${customNeedValue}">
                </div>
            `;

            const select = row.querySelector('.family-need-select');
            const customWrap = row.querySelector('.family-need-custom-wrap');
            const customInput = row.querySelector('.family-need-custom');
            const removeBtn = row.querySelector('.family-need-remove');

            select.addEventListener('change', function () {
                const isOther = this.value === 'Others Please Specify';
                customWrap.classList.toggle('d-none', !isOther);
                customInput.required = isOther;
                if (!isOther) {
                    customInput.value = '';
                }

                if (this.value && row === builder.lastElementChild) {
                    addRow({}, false);
                }
            });

            customInput.addEventListener('input', function () {
                if (this.value && row === builder.lastElementChild) {
                    addRow({}, false);
                }
            });

            removeBtn.addEventListener('click', function () {
                if (builder.children.length <= 1) {
                    select.value = '';
                    customInput.value = '';
                    customWrap.classList.add('d-none');
                    customInput.required = false;
                    row.querySelector('.family-need-quantity').value = 1;
                    return;
                }

                row.remove();
            });

            builder.appendChild(row);

            if (selectedNeed) {
                if (isCustom) {
                    select.value = 'Others Please Specify';
                    customWrap.classList.remove('d-none');
                    customInput.required = true;
                } else {
                    select.value = selectedNeed;
                }
            }

            if (shouldFocus) {
                select.focus();
            }
        };

        builder.innerHTML = '';

        if (existingNeeds.length > 0) {
            existingNeeds.forEach((need, index) => addRow(need, index === 0));
            addRow({}, false);
        } else {
            addRow({}, true);
        }

        builder.dataset.initialized = '1';
    }

    document.querySelectorAll('[data-family-needs-builder]').forEach(initializeFamilyNeedsBuilder);

    function printEvacCard() {
        const card = document.getElementById('printCard');
        const html = `<html><head><title>Print Report</title>
            <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style>
                body { margin: 0; padding: 20px; background: #fff !important; color: #000 !important; font-family: 'Rajdhani', sans-serif; }
                #printCard { 
                    width: 1000px; margin: 0 auto; padding: 20px; border: 2px solid #000; color: #000 !important; 
                    background: none !important; box-shadow: none !important; 
                }
                div[style*="background:rgba"], div[style*="background:linear-gradient"] { 
                    background: none !important; border: 1px solid #ddd !important; color: #000 !important; 
                }
                span[style*="color:#00d2ff"], div[style*="color:#00d2ff"] { color: #000 !important; }
                table tr[style*="background:rgba"] { background: #f2f2f2 !important; }
                th { background: #eee !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
                td, th { border-bottom: 1px solid #ccc !important; }
                * { print-color-adjust: exact !important; -webkit-print-color-adjust: exact !important; }
                @media print { @page { margin: 1cm; size: landscape; } .btn-group, #printActionBar { display:none; } }
            </style>
        </head><body><div id="printCard">${card.innerHTML}</div>
        <script>window.onload=function(){window.print();setTimeout(function(){window.close();},500);};<\/script>
        </body></html>`;

        const printWin = window.open('', '_blank', 'width=1100,height=780');
        if (!printWin) {
            alert('Pop-up blocked! Please allow pop-ups for this page.');
            return;
        }
        printWin.document.write(html);
        printWin.document.close();
    }
</script>
@endpush
@include('typhoon.partials.choose-school-modal')
@endsection
