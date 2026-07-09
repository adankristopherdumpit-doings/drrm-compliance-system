@extends('layouts.app')

@push('styles')
<style>
    :root {
        --drill-orange: #FF6F00;
        --drill-orange-light: #FFF3E0;
    }
    .notif-card {
        border-left: 5px solid var(--drill-orange);
        transition: transform 0.2s;
    }
    .notif-card:hover {
        transform: scale(1.01);
    }
    .announcement-card {
        border-left: 5px solid #007bff;
    }
    .badge-drill {
        background-color: var(--drill-orange);
        color: white;
    }
    .text-drill-orange { color: var(--drill-orange) !important; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="h4 mb-0 text-gray-800"><i class="fas fa-bell text-drill-orange me-2"></i>Drill Notifications</h2>
        <div class="d-flex gap-2">
            <form action="{{ route('drill-monitoring.notifications') }}" method="GET" class="d-flex gap-2">
                <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ $activeSchool->id == $school->id ? 'selected' : '' }}>
                            {{ $school->school_name }}
                        </option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('drill-monitoring.dashboard') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Drills Section -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-drill-orange">
                        <i class="fas fa-calendar-alt me-2"></i>Upcoming Scheduled Drills
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($upcomingDrills as $drill)
                        <div class="card notif-card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title fw-bold text-dark mb-1">
                                            {{ $drill->drill_type }} Drill
                                        </h5>
                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-clock me-1"></i> 
                                            Scheduled on {{ \Carbon\Carbon::parse($drill->drill_date)->format('F d, Y') }} 
                                            @if($drill->start_time) at {{ $drill->start_time }} @endif
                                        </p>
                                        <div class="mb-2">
                                            <span class="badge badge-drill">Scheduled</span>
                                            <span class="badge bg-info text-white">{{ $activeSchool->school_name }}</span>
                                        </div>
                                        <p class="mb-0 text-dark small">{{ $drill->remarks ?: 'No additional details provided.' }}</p>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-running fa-2x text-light opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-calendar-times fa-3x mb-3 opacity-25"></i>
                            <p>No upcoming drills scheduled at this time.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- New Events / Announcements Section -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bullhorn me-2"></i>DRRM Announcements
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($announcements as $announcement)
                        <div class="card announcement-card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    @if($announcement->image_path)
                                        <img src="{{ asset('storage/' . $announcement->image_path) }}" 
                                             class="rounded me-3 shadow-sm" 
                                             style="width: 70px; height: 70px; object-fit: cover;">
                                    @endif
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="card-title fw-bold text-dark mb-0">{{ $announcement->what }}</h6>
                                            <small class="text-muted">{{ $announcement->when ? $announcement->when->diffForHumans() : '' }}</small>
                                        </div>
                                        <div class="mb-2 text-primary x-small fw-bold" style="font-size: 0.75rem;">
                                            <i class="fas fa-map-marker-alt me-1"></i> {{ $announcement->where }}
                                        </div>
                                        <p class="card-text text-dark small mb-0">{{ Str::limit($announcement->why, 150) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-comment-slash fa-3x mb-3 opacity-25"></i>
                            <p>No new announcements at this time.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection