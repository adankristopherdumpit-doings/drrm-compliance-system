@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800"><i class="fas fa-clipboard-check me-2"></i>Inspection Detail</h1>
            <p class="text-muted small mb-0">Reviewing monitoring tool for {{ $inspection->school->school_name }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('drill-monitoring.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <a href="{{ route('fire-safety.inspection.print', $inspection->id) }}" target="_blank" class="btn btn-dark">
                <i class="fas fa-print me-1"></i> Print Monitoring Tool
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Drill Overview -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 fw-bold">Drill Overview</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <small class="text-xs text-uppercase fw-bold text-muted d-block">Drill Type</small>
                        <div class="h5 mb-0 fw-bold text-primary">{{ $inspection->drill_type }}</div>
                    </div>
                    <div class="mb-4">
                        <small class="text-xs text-uppercase fw-bold text-muted d-block">Scheduled Date/Time</small>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ date('F d, Y', strtotime($inspection->inspection_date)) }} at {{ $inspection->inspection_time }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-xs text-uppercase fw-bold text-muted d-block">Started</small>
                            <div>{{ $inspection->time_started ?: '—' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-xs text-uppercase fw-bold text-muted d-block">Finished</small>
                            <div>{{ $inspection->time_finished ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <small class="text-xs text-uppercase fw-bold text-muted d-block">Elapsed Time</small>
                            <div class="h4 mb-0 fw-bold text-success">{{ $inspection->elapsed_time ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Participants & Buildings</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ number_format($inspection->no_of_students ?: 0) }}</div>
                            <small class="text-muted">Students</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ number_format($inspection->no_of_personnel ?: 0) }}</div>
                            <small class="text-muted">Personnel</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $inspection->no_of_buildings ?: 0 }}</div>
                            <small class="text-muted">Buildings</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $inspection->no_of_exits ?: 0 }}</div>
                            <small class="text-muted">Exit Points</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitoring Tool Details -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">Monitoring Checklist Information</h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Checklist Results</h6>
                    <div class="list-group mb-4">
                        @php $checklist = $inspection->checklist_data ?? []; @endphp
                        @forelse($checklist as $item)
                        <div class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-3"></i>
                            <span>{{ $item }}</span>
                        </div>
                        @empty
                        <div class="list-group-item text-center py-4 text-muted">
                            No checklist items were specified for this drill.
                        </div>
                        @endforelse
                    </div>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Other Observers Present</h6>
                    <div class="list-group mb-4">
                        @php $observers = $inspection->observers_data ?? []; @endphp
                        @forelse($observers as $obs)
                        <div class="list-group-item d-flex align-items-center">
                            <i class="fas fa-user-shield text-info me-3"></i>
                            <span>{{ $obs }}</span>
                        </div>
                        @empty
                        <div class="list-group-item text-center py-4 text-muted">
                            No other observers recorded.
                        </div>
                        @endforelse
                    </div>

                    @if($inspection->remarks)
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Remarks / Observations</h6>
                    <div class="p-3 bg-light rounded border mb-4">
                        {{ $inspection->remarks }}
                    </div>
                    @endif

                    <div class="mt-4 p-4 rounded" style="background-color: #f8f9fc; border-left: 5px solid #4e73df;">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <small class="text-xs text-uppercase fw-bold text-muted d-block">Monitored By</small>
                                <div class="fw-bold">{{ $inspection->monitored_by }}</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-xs text-uppercase fw-bold text-muted d-block">Coordinator</small>
                                <div class="small fw-bold">{{ $inspection->coordinator_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-xs text-uppercase fw-bold text-muted d-block">School Head</small>
                                <div class="small fw-bold">{{ $inspection->school_head_name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
