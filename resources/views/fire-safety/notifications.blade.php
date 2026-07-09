@extends('layouts.fire-safety')

@section('title', 'Notifications - Fire Safety')
@section('page_title', 'Notifications')

@section('styles')
<style>
    .notif-card {
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    .notif-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    .notif-card.unread {
        background-color: #f0f4ff;
        border-left-color: var(--fire-red);
    }
    .notif-card.read {
        opacity: 0.85;
    }
    .notif-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .notif-actions .btn {
        font-size: 0.75rem;
        padding: 3px 10px;
    }
    .notif-type-badge {
        font-size: 0.65rem;
        padding: 2px 8px;
    }
    .filter-btn.active {
        background-color: var(--fire-red) !important;
        border-color: var(--fire-red) !important;
        color: white !important;
    }
    .school-filter-btn.active {
        background-color: var(--fire-red) !important;
        border-color: var(--fire-red) !important;
        color: white !important;
    }
    .school-badge {
        font-size: 0.7rem;
        font-weight: 600;
    }
    .notif-user {
        font-size: 0.7rem;
        color: #6c757d;
    }

    /* Custom Pagination Styling */
    .pagination {
        gap: 5px;
    }
    .pagination .page-link {
        color: var(--fire-red);
        border-radius: 6px;
        border: 1px solid #dee2e6;
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pagination .page-item.active .page-link {
        background-color: var(--fire-red);
        border-color: var(--fire-red);
        color: white;
    }
    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        font-weight: 600;
        font-size: 0.8rem;
        padding-left: 12px;
        padding-right: 12px;
    }
    /* Small arrows */
    .pagination .page-link i, 
    .pagination .page-link svg {
        width: 10px;
        height: 10px;
    }
    .pagination .page-item:hover:not(.active):not(.disabled) .page-link {
        background-color: var(--fire-dark-red);
        border-color: var(--fire-dark-red);
        color: white;
    }
    .pagination .page-item.disabled .page-link {
        color: #adb5bd;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1 fw-bold"><i class="fas fa-bell me-2 text-danger"></i>All Notifications</h5>
                            <small class="text-muted">View and manage all fire safety notifications</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" id="markAllReadBtn">
                                <i class="fas fa-check-double me-1"></i> Mark All as Read
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- School Filter Tabs -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small fw-bold me-1"><i class="fas fa-school me-1"></i>School:</span>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('fire-safety.notifications.page') }}"
                       class="btn btn-sm {{ !$filterSchoolId || $filterSchoolId === 'all' ? 'btn-danger' : 'btn-outline-secondary' }} school-filter-btn {{ !$filterSchoolId || $filterSchoolId === 'all' ? 'active' : '' }}">
                        <i class="fas fa-globe me-1"></i> All Schools
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-sm {{ $filterSchoolId && $filterSchoolId !== 'all' ? 'btn-danger' : 'btn-outline-secondary' }} dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            @if($filterSchoolId && $filterSchoolId !== 'all')
                                @php $selectedSchool = $filterSchools->firstWhere('id', $filterSchoolId); @endphp
                                {{ $selectedSchool ? $selectedSchool->school_name : 'Choose Another School...' }}
                            @else
                                Choose Another School...
                            @endif
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($filterSchools as $fs)
                                <li>
                                    <a class="dropdown-item {{ $filterSchoolId == $fs->id ? 'active' : '' }}"
                                       href="{{ route('fire-safety.notifications.page', ['school_id' => $fs->id]) }}">
                                        {{ $fs->school_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    @foreach($filterSchools as $fs)
                        <span class="btn btn-sm btn-danger school-filter-btn active">
                            {{ $fs->school_name }}
                        </span>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Type Filter Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">
                    <i class="fas fa-list me-1"></i> All
                </button>
                <button class="btn btn-sm btn-outline-danger filter-btn" data-filter="extinguisher_inspection">
                    <i class="fas fa-fire-extinguisher me-1"></i> Extinguisher Inspections
                </button>
                <button class="btn btn-sm btn-outline-warning filter-btn" data-filter="alarm_due,alarm_update">
                    <i class="fas fa-bell me-1"></i> Alarm
                </button>
                <button class="btn btn-sm btn-outline-info filter-btn" data-filter="room_approval,room_update">
                    <i class="fas fa-door-open me-1"></i> Room
                </button>
                <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="inspection">
                    <i class="fas fa-clipboard-check me-1"></i> Inspections
                </button>
                <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="building_update">
                    <i class="fas fa-building me-1"></i> Building
                </button>
                <button class="btn btn-sm btn-outline-info filter-btn" data-filter="evacuation_plan">
                    <i class="fas fa-map-signs me-1"></i> Evacuation Plans
                </button>
                <button class="btn btn-sm btn-outline-danger filter-btn" data-filter="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i> Alerts
                </button>
                <button class="btn btn-sm btn-outline-success filter-btn" data-filter="event">
                    <i class="fas fa-calendar-alt me-1"></i> Events
                </button>
                <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="general">
                    <i class="fas fa-info-circle me-1"></i> General
                </button>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="row">
        <div class="col-12">
            @forelse($notifications as $notif)
                @php
                    $iconMap = [
                        'inspection' => ['icon' => 'fa-clipboard-check', 'bg' => 'bg-primary-subtle', 'text' => 'text-primary', 'label' => 'Inspection'],
                        'alarm_due' => ['icon' => 'fa-bell', 'bg' => 'bg-warning-subtle', 'text' => 'text-warning', 'label' => 'Alarm Due'],
                        'alarm_update' => ['icon' => 'fa-bell', 'bg' => 'bg-warning-subtle', 'text' => 'text-warning', 'label' => 'Alarm Update'],
                        'room_approval' => ['icon' => 'fa-door-open', 'bg' => 'bg-info-subtle', 'text' => 'text-info', 'label' => 'Room Approval'],
                        'room_update' => ['icon' => 'fa-door-open', 'bg' => 'bg-info-subtle', 'text' => 'text-info', 'label' => 'Room Update'],
                        'extinguisher_inspection' => ['icon' => 'fa-fire-extinguisher', 'bg' => 'bg-danger-subtle', 'text' => 'text-danger', 'label' => 'Extinguisher'],
                        'building_update' => ['icon' => 'fa-building', 'bg' => 'bg-dark bg-opacity-10', 'text' => 'text-dark', 'label' => 'Building Update'],
                        'alert' => ['icon' => 'fa-exclamation-triangle', 'bg' => 'bg-danger-subtle', 'text' => 'text-danger', 'label' => 'Alert'],
                        'event' => ['icon' => 'fa-calendar-alt', 'bg' => 'bg-success-subtle', 'text' => 'text-success', 'label' => 'Event'],
                        'evacuation_plan' => ['icon' => 'fa-map-signs', 'bg' => 'bg-info-subtle', 'text' => 'text-info', 'label' => 'Evacuation Plan'],
                        'general' => ['icon' => 'fa-info-circle', 'bg' => 'bg-secondary-subtle', 'text' => 'text-secondary', 'label' => 'General'],
                    ];
                    $style = $iconMap[$notif->type] ?? $iconMap['general'];
                    $actionData = $notif->action_data ?? [];
                    $badgeColorMap = [
                        'text-primary' => 'primary', 'text-danger' => 'danger', 'text-warning' => 'warning',
                        'text-info' => 'info', 'text-success' => 'success', 'text-dark' => 'dark', 'text-secondary' => 'secondary',
                    ];
                    $badgeColor = $badgeColorMap[$style['text']] ?? 'secondary';
                @endphp
                <div class="card dashboard-card mb-2 notif-card {{ $notif->is_read ? 'read' : 'unread' }}" data-notif-type="{{ $notif->type }}" data-notif-id="{{ $notif->id }}">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-start">
                            <!-- Icon -->
                            <div class="notif-icon {{ $style['bg'] }} {{ $style['text'] }} me-3">
                                <i class="fas {{ $style['icon'] }}"></i>
                            </div>

                            <!-- Content -->
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <h6 class="mb-0 fw-bold">
                                            {{ $notif->title }}
                                            @if(!$notif->is_read)
                                                <span class="badge bg-danger ms-2" style="font-size: 0.6rem;">NEW</span>
                                            @endif
                                        </h6>
                                        <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                            <small class="text-muted">
                                                {{ $notif->created_at->diffForHumans() }}
                                            </small>
                                            @if($notif->school)
                                                <span class="badge bg-primary-subtle text-primary school-badge">
                                                    <i class="fas fa-school me-1"></i>{{ $notif->school->school_name }}
                                                </span>
                                            @endif
                                            @if($notif->user)
                                                <span class="notif-user">
                                                    <i class="fas fa-user me-1"></i>{{ $notif->user->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge notif-type-badge bg-{{ $badgeColor }}-subtle {{ $style['text'] }}">
                                        {{ $style['label'] }}
                                    </span>
                                </div>
                                <p class="mb-2 text-dark small">{{ $notif->message }}</p>

                                <!-- Action Buttons -->
                                <div class="notif-actions d-flex gap-2 flex-wrap">
                                    @if($notif->action_type === 'see_inspection')
                                        <button class="btn btn-outline-primary btn-sm notif-action-btn"
                                                onclick="navigateToInspection({{ json_encode($actionData) }})">
                                            <i class="fas fa-eye me-1"></i> See Inspection
                                        </button>
                                    @elseif($notif->action_type === 'update_now')
                                        <button class="btn btn-outline-danger btn-sm notif-action-btn"
                                                onclick="navigateToExtinguisher({{ json_encode($actionData) }})">
                                            <i class="fas fa-sync-alt me-1"></i> Update Now
                                        </button>
                                    @elseif($notif->action_type === 'go_test')
                                        <button class="btn btn-outline-warning btn-sm notif-action-btn"
                                                onclick="navigateToAlarmTest({{ json_encode($actionData) }})">
                                            <i class="fas fa-vial me-1"></i> Go Test Now
                                        </button>
                                    @endif

                                    @if(!$notif->is_read)
                                        <button class="btn btn-outline-secondary btn-sm mark-read-btn" data-id="{{ $notif->id }}">
                                            <i class="fas fa-check me-1"></i> Mark as Read
                                        </button>
                                    @else
                                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">
                                            <i class="fas fa-check-double me-1"></i> Read
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card dashboard-card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No notifications yet</h5>
                        <p class="text-muted small">Notifications will appear here when actions are taken in the fire safety system.</p>
                    </div>
                </div>
            @endforelse

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Filter notifications by type (supports comma-separated types)
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            const filterTypes = filter.split(',');

            document.querySelectorAll('.notif-card').forEach(card => {
                if (filter === 'all' || filterTypes.includes(card.dataset.notifType)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Mark single notification as read
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const notifId = this.dataset.id;
            const card = this.closest('.notif-card');

            fetch(`/fire-safety/notification/${notifId}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    card.classList.remove('unread');
                    card.classList.add('read');
                    const badge = card.querySelector('.badge.bg-danger');
                    if (badge) badge.remove();
                    this.outerHTML = '<span class="badge bg-light text-muted border" style="font-size: 0.7rem;"><i class="fas fa-check-double me-1"></i> Read</span>';
                }
            });
        });
    });

    // Mark all as read
    document.getElementById('markAllReadBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Mark all as read?',
            text: 'All notifications will be marked as read.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#A8191F',
            confirmButtonText: 'Yes, mark all'
        }).then(result => {
            if (result.isConfirmed) {
                fetch('/fire-safety/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        });
    });
});

// Navigation functions for notification action buttons
function navigateToInspection(data) {
    const schoolId = data.school_id;
    if ("{{ auth()->user()->role }}" === 'admin' && schoolId) {
        switchSchoolAndRedirect(schoolId, '/fire-safety/buildings#layouts');
    } else {
        window.location.href = '/fire-safety/buildings#layouts';
    }
}

function navigateToExtinguisher(data) {
    const schoolId = data.school_id;
    if ("{{ auth()->user()->role }}" === 'admin' && schoolId) {
        switchSchoolAndRedirect(schoolId, '/fire-safety/extinguishers#extinguisher-section');
    } else {
        window.location.href = '/fire-safety/extinguishers#extinguisher-section';
    }
}

function navigateToAlarmTest(data) {
    const schoolId = data.school_id;
    if ("{{ auth()->user()->role }}" === 'admin' && schoolId) {
        switchSchoolAndRedirect(schoolId, '/fire-safety/buildings#alarm-section');
    } else {
        window.location.href = '/fire-safety/buildings#alarm-section';
    }
}
</script>
@endsection
