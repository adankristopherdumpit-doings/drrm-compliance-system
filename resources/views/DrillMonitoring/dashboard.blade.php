@extends('layouts.app')

@push('styles')
<style>
    :root {
        --drill-orange: #FF6F00;
        --drill-orange-hover: #E65100;
        --drill-orange-light: #FFF3E0;
        --drill-orange-muted: #FB8C00;
    }
    .text-drill-orange { color: var(--drill-orange) !important; }
    .drill-orange-border { border-left: 0.25rem solid var(--drill-orange) !important; }
    .drill-orange-border-top { border-top: 4px solid var(--drill-orange) !important; }
    
    .btn-drill-orange { 
        background-color: var(--drill-orange); 
        border-color: var(--drill-orange); 
        color: white; 
    }
    .btn-drill-orange:hover { 
        background-color: var(--drill-orange-hover); 
        border-color: var(--drill-orange-hover); 
        color: white;
    }
    .drill-table-header {
        background-color: var(--drill-orange-light);
        color: var(--drill-orange-hover);
    }
    .card-drill-header {
        border-bottom: 2px solid var(--drill-orange-light);
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="h4 mb-0 text-gray-800"><i class="fas fa-bell text-drill-orange me-2"></i>Drill Monitoring Dashboard: {{ $activeSchool->school_name }}</h2>
        <div class="d-flex gap-2">
            <form action="{{ route('drill-monitoring.dashboard') }}" method="GET" class="d-flex gap-2">
                <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ $activeSchool->id == $school->id ? 'selected' : '' }}>
                            {{ $school->school_name }}
                        </option>
                    @endforeach
                </select>
            </form>
            <button class="btn btn-drill-orange btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#logMonitoringModal">
                <i class="fas fa-plus"></i> Log New Drill Monitoring
            </button>
            <div class="modal fade" id="logMonitoringModal" tabindex="-1">
                <div class="modal-dialog modal-lg"> <!-- Changed to modal-lg for better wide-row formatting -->
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Log New Drill Monitoring</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="logMonitoringForm" action="{{ route('drill-monitoring.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="unified_school_id" value="{{ $activeSchool->id }}">


                                
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Drill Type *</label>
                                        <select class="form-select border-primary" name="drill_type" required>
                                            <option value="">Select Type</option>
                                            <option value="Earthquake">Earthquake</option>
                                            <option value="Fire">Fire</option>
                                            <option value="Tsunami">Tsunami</option>
                                            <option value="Lockdown">Lockdown</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Date *</label>
                                        <input type="date" class="form-control border-primary" name="inspection_date" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Time *</label>
                                        <input type="time" class="form-control border-primary" name="inspection_time" value="{{ date('H:i') }}" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Time Started</label>
                                        <input type="time" class="form-control" name="time_started">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Time Finished</label>
                                        <input type="time" class="form-control" name="time_finished">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Elapsed Time (mm:ss)</label>
                                        <input type="text" class="form-control" name="elapsed_time" placeholder="e.g. 05:30">
                                    </div>
                                </div>

                                <!-- New Row: Number of Students and Personnel -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Number of Students</label>
                                        <input type="number" class="form-control" name="no_of_students" min="0" value="0" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Number of Personnel</label>
                                        <input type="number" class="form-control" name="no_of_personnel" min="0" value="0">
                                    </div>
                                </div>

                                <!-- New Row: Monitored By and Coordinator -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Monitored By</label>
                                        <input type="text" class="form-control" name="monitored_by" placeholder="Name of monitor/inspector">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Coordinator</label>
                                        <input type="text" class="form-control" name="coordinator_name" placeholder="Name of drill coordinator">
                                    </div>
                                </div>
                                <!-- Row: Findings and Remarks Consolidated -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Findings and Remarks</label>
                                        <textarea class="form-control" name="findings_remarks" rows="4" placeholder="Enter issues observed, equipment conditions, recommendations, or corrective actions here..."></textarea>
                                    </div>
                                </div>

<!-- Checklists -->
<div class="mb-4">
    <label class="form-label fw-bold mb-2">Drill Evaluation Checklist</label>
    <div class="row g-3">
        
        <!-- Left Column (Items 1 - 3) -->
        <div class="col-md-6">
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3" style="width: 55%;">Evaluation Item</th>
                            <th style="width: 15%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Good</small></th>
                            <th style="width: 15%;"><i class="far fa-circle text-warning"></i><br><small class="text-muted" style="font-size: 0.75rem;">Partial</small></th>
                            <th style="width: 15%;"><i class="fas fa-times text-danger"></i><br><small class="text-muted" style="font-size: 0.75rem;">Bad</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Item 1 -->
                        <tr>
                            <td class="text-start ps-3">Alarm</td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[alarm_audible]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[alarm_audible]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[alarm_audible]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        </tr>
                        <!-- Item 2 -->
                        <tr>
                            <td class="text-start ps-3">Evacuation plan(updated)</td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[routes_clear]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[routes_clear]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[routes_clear]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        </tr>
                        <!-- Item 3 -->
                        <tr>
                            <td class="text-start ps-3">DRRM team</td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[participants_calm]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[participants_calm]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[participants_calm]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        </tr>
                    <!-- Item 7 -->
                    <tr>
                        <td class="text-start ps-3">Hotline Numbers</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[hotline_numbers]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[hotline_numbers]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[hotline_numbers]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 8 -->
                    <tr>
                        <td class="text-start ps-3">Duck Cover and Hold (ER)</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[duck_cover_and_hold]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[duck_cover_and_hold]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[duck_cover_and_hold]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 9 -->
                    <tr>
                        <td class="text-start ps-3">Command Center</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[command_center]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[command_center]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[command_center]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 10 -->
                    <tr>
                        <td class="text-start ps-3">Student Release Form</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[student_release_form]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[student_release_form]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[student_release_form]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 14 -->
                    <tr>
                        <td class="text-start ps-3">Exit Signage</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[exit_signage]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[exit_signage]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[exit_signage]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 15 -->
                    <tr>
                        <td class="text-start ps-3">BERT/SERT</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[bert_sert]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[bert_sert]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[bert_sert]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 16 -->
                    <tr>
                        <td class="text-start ps-3">Wearing of IDs</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[wear_ids]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[wear_ids]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[wear_ids]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 17 -->
                    <tr>
                        <td class="text-start ps-3">Walked Casually</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[walk_casually]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[walk_casually]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[walk_casually]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column (Items 4 - 6) -->
        <div class="col-md-6">
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3" style="width: 55%;">Evaluation Item</th>
                            <th style="width: 15%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Good</small></th>
                            <th style="width: 15%;"><i class="far fa-circle text-warning"></i><br><small class="text-muted" style="font-size: 0.75rem;">Partial</small></th>
                            <th style="width: 15%;"><i class="fas fa-times text-danger"></i><br><small class="text-muted" style="font-size: 0.75rem;">Bad</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Item 4 -->
                        <tr>
                            <td class="text-start ps-3">First Aid Kit</td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[first_aid_kit]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[first_aid_kit]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[first_aid_kit]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        </tr>
                        <!-- Item 5 -->
                        <tr>
                            <td class="text-start ps-3">Actual headcount</td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[actual_headcount]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[actual_headcount]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[actual_headcount]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        </tr>
                        <!-- Item 6 -->
                        <tr>
                            <td class="text-start ps-3">Directional Arrows</td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[directional_arrows]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[directional_arrows]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                            <td><input class="form-check-input" type="radio" name="checklist_data[directional_arrows]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        </tr>
                    <!-- Item 11 -->
                    <tr>
                        <td class="text-start ps-3">Attendance Sheet</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[attendance_sheet]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[attendance_sheet]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[attendance_sheet]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 12 -->
                    <tr>
                        <td class="text-start ps-3">Megaphone</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[megaphone]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[megaphone]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[megaphone]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 13 -->
                    <tr>
                        <td class="text-start ps-3">Group Signage</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[group_signage]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[group_signage]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[group_signage]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>   
                    <!-- Item 18 -->
                    <tr>
                        <td class="text-start ps-3">Guard on Duty</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[guard_on_duty]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[guard_on_duty]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[guard_on_duty]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 19 -->
                    <tr>
                        <td class="text-start ps-3">School ID of personnel</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[school_id]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[school_id]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[school_id]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 20 -->
                    <tr>
                        <td class="text-start ps-3">Open Doors(EQ)</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[open_doors]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[open_doors]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[open_doors]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    <!-- Item 21 -->                
                    <tr>
                        <td class="text-start ps-3">Closed Doors(Fire)</td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[closed_doors]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[closed_doors]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                        <td><input class="form-check-input" type="radio" name="checklist_data[closed_doors]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Other Observers -->
<div class="mb-4">
    <label class="form-label fw-bold mb-2">Other Observers Presence</label>
    <div class="row g-3">
        
        <!-- Left Column (Observers 1 - 4) -->
        <div class="col-md-6">
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3" style="width: 75%;">Observer Group</th>
                            <th style="width: 25%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Present</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Observer 1 -->
                        <tr>
                            <td class="text-start ps-3">Local Barangay</td>
                            <td><input class="form-check-input" type="radio" name="observers_data[local_barangay]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td>
                        </tr>
                        <!-- Observer 2 -->
                        <tr>
                            <td class="text-start ps-3">Philippine National Police (PNP)</td>
                            <td><input class="form-check-input" type="radio" name="observers_data[pnp]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td>
                        </tr>
                        <!-- Observer 3 -->
                        <tr>
                            <td class="text-start ps-3">City DRRM</td>
                            <td><input class="form-check-input" type="radio" name="observers_data[city_drrm]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td>
                        </tr>
                        <!-- Observer 4 -->
                        <tr>
                            <td class="text-start ps-3">Bureau of Fire Protection(BFP)</td>
                            <td><input class="form-check-input" type="radio" name="observers_data[bfp]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column (Observers 5 - 7 + Dynamic Input) -->
        <div class="col-md-6">
            <div class="table-responsive border rounded mb-2">
                <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3" style="width: 75%;">Observer Group</th>
                            <th style="width: 25%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Present</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Observer 5 -->
                        <tr>
                            <td class="text-start ps-3">PTA / Parent Observers</td>
                            <td><input class="form-check-input" type="radio" name="observers_data[pta_parents]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td>
                        </tr>
                        <!-- Observer 6 -->
                        <tr>
                            <td class="text-start ps-3">OTMPS</td>
                            <td><input class="form-check-input" type="radio" name="observers_data[otmps]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td>
                        </tr>
                        <!-- Observer 7 (Others Option) -->
                        <tr>
                            <td class="text-start ps-3 fw-bold text-secondary">Others (Please specify)</td>
                            <td>
                                <input class="form-check-input" type="radio" id="observerOthersRadio" name="observers_data[others_present]" value="present" onclick="toggleObserverOthersInput(this, 'observerOthersInputWrapper')">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Dynamic Specification Box -->
            <div id="observerOthersInputWrapper" class="d-none animate-fade-in">
                <input type="text" class="form-control form-control-sm border-primary" name="observers_data[others_specified]" placeholder="Type other agency or observer name here..." disabled>
            </div>
        </div>

    </div>
</div>

<!-- JavaScript for Dynamic "Others" Field Toggle -->
<script>
function toggleObserverOthersInput(radioElement, wrapperId) {
    const wrapper = document.getElementById(wrapperId);
    const inputField = wrapper.querySelector('input');

    // Custom untoggle behavior matching your other rows
    if (radioElement.wasChecked) {
        radioElement.checked = false;
        radioElement.wasChecked = false;
        
        // Hide and clear input
        wrapper.classList.add('d-none');
        inputField.setAttribute('disabled', 'disabled');
        inputField.removeAttribute('required');
        inputField.value = '';
    } else {
        radioElement.wasChecked = true;
        
        // Show and activate input
        wrapper.classList.remove('d-none');
        inputField.removeAttribute('disabled');
        inputField.setAttribute('required', 'required');
        inputField.focus();
    }
}
</script>


                                <div class="modal-footer px-0 pb-0">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="saveLogBtn">Save Log</button>
                                        </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card drill-orange-border shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-drill-orange text-uppercase mb-1">Total Drills Monitored</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_monitored'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Participants</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['avg_participants'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- History Monitoring Table --}}
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 d-flex justify-content-between bg-white card-drill-header">
            <h6 class="m-0 font-weight-bold text-drill-orange">Recent Drill Monitoring Records (Newest to Oldest)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" width="100%" cellspacing="0">
                    <thead class="drill-table-header">
                        <tr>
                            <th>Date</th>
                            <th>Drill Type</th>
                            <th>Time</th>
                            <th>Participants</th>
                            <th>Monitored By</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monitorings as $log)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($log->inspection_date)->format('M d, Y') }}</td>
                            <td>{{ $log->drill_type }}</td>
                            <td>{{ $log->inspection_time }}</td>
                            <td>{{ $log->no_of_students + $log->no_of_personnel }}</td>
                            <td>{{ $log->monitored_by }}</td>
                            <td><span class="badge bg-{{ strtolower($log->status) == 'completed' ? 'success' : 'warning' }}">{{ $log->status }}</span></td>
                            <td class="text-end d-flex gap-2 justify-content-end">
                                <button class="btn btn-outline-primary btn-sm shadow-sm" onclick="viewDrillDetails({{ $log->id }})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
<a href="{{ route('drill-monitoring.drill-monitoring.print.monitoring-tool', $log->id) }}" target="_blank" class="btn btn-outline-dark btn-sm shadow-sm" title="Print Monitoring Tool">

                                    <i class="fas fa-print"></i>
                                </a>

                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No monitoring records found for this school.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $monitorings->links() }}
            </div>
        </div>
    </div>

    {{-- Upcoming Scheduled Drills Table --}}
    <div class="card shadow mb-4 drill-orange-border-top">
        <div class="card-header py-3 bg-white card-drill-header">
            <h6 class="m-0 font-weight-bold text-drill-orange"><i class="fas fa-calendar-alt me-2"></i>Upcoming Scheduled Inspections</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Scheduled Date</th>
                            <th>Time</th>
                            <th>Drill Type</th>
                            <th>Expected Participants</th>
                            <th>Coordinator</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingDrills as $upcoming)
                        <tr>
                            <td class="fw-bold">{{ \Carbon\Carbon::parse($upcoming->inspection_date)->format('F d, Y') }}</td>
                            <td>{{ $upcoming->inspection_time }}</td>
                            <td>{{ $upcoming->drill_type }}</td>
                            <td>{{ ($upcoming->no_of_students ?? 0) + ($upcoming->no_of_personnel ?? 0) }}</td>
                            <td>{{ $upcoming->coordinator_name }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="viewDrillDetails({{ $upcoming->id }})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">No upcoming drills scheduled.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Monitoring Modal -->
    <div class="modal fade" id="viewMonitoringModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-drill-orange fw-bold">
                        <i class="fas fa-info-circle me-2"></i>Drill Record Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewModalContent">
                    <!-- Content loaded via JS -->
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" target="_blank" class="btn btn-dark d-none" id="printBtnInView">
                        <i class="fas fa-print me-1"></i> Print Report
                    </a>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-drill-orange" id="editBtnInView" onclick="openEditModalFromView()">
                        <i class="fas fa-edit me-1"></i> Edit Record
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Monitoring Modal -->
    <div class="modal fade" id="editMonitoringModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Drill Monitoring</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editMonitoringForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Drill Type *</label>
                                <select class="form-select" name="drill_type" id="edit_drill_type" required>
                                    <option value="Earthquake">Earthquake</option>
                                    <option value="Fire">Fire</option>
                                    <option value="Tsunami">Tsunami</option>
                                    <option value="Lockdown">Lockdown</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Date *</label>
                                <input type="date" class="form-control" name="inspection_date" id="edit_inspection_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Time *</label>
                                <input type="time" class="form-control" name="inspection_time" id="edit_inspection_time" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Time Started</label>
                                <input type="time" class="form-control" name="time_started" id="edit_time_started">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Time Finished</label>
                                <input type="time" class="form-control" name="time_finished" id="edit_time_finished">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Elapsed Time</label>
                                <input type="text" class="form-control" name="elapsed_time" id="edit_elapsed_time">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">No. of Students</label>
                                <input type="number" class="form-control" name="no_of_students" id="edit_no_of_students" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">No. of Personnel</label>
                                <input type="number" class="form-control" name="no_of_personnel" id="edit_no_of_personnel" min="0">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Monitored By</label>
                                <input type="text" class="form-control" name="monitored_by" id="edit_monitored_by" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Coordinator</label>
                                <input type="text" class="form-control" name="coordinator_name" id="edit_coordinator_name" required>
                            </div>
                        </div>

                        <!-- Drill Evaluation Checklist (Edit Modal) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">Drill Evaluation Checklist</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="table-responsive border rounded">
                                        <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start ps-3" style="width: 55%;">Evaluation Item</th>
                                                    <th style="width: 15%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Good</small></th>
                                                    <th style="width: 15%;"><i class="far fa-circle text-warning"></i><br><small class="text-muted" style="font-size: 0.75rem;">Partial</small></th>
                                                    <th style="width: 15%;"><i class="fas fa-times text-danger"></i><br><small class="text-muted" style="font-size: 0.75rem;">Bad</small></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="text-start ps-3">Alarm</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[alarm_audible]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[alarm_audible]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[alarm_audible]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Evacuation plan(updated)</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[routes_clear]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[routes_clear]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[routes_clear]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">DRRM team</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[participants_calm]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[participants_calm]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[participants_calm]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Hotline Numbers</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[hotline_numbers]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[hotline_numbers]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[hotline_numbers]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Duck Cover and Hold (ER)</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[duck_cover_and_hold]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[duck_cover_and_hold]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[duck_cover_and_hold]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Command Center</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[command_center]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[command_center]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[command_center]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Student Release Form</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[student_release_form]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[student_release_form]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[student_release_form]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Exit Signage</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[exit_signage]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[exit_signage]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[exit_signage]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">BERT/SERT</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[bert_sert]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[bert_sert]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[bert_sert]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Wearing of IDs</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[wear_ids]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[wear_ids]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[wear_ids]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Walked Casually</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[walk_casually]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[walk_casually]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[walk_casually]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive border rounded">
                                        <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start ps-3" style="width: 55%;">Evaluation Item</th>
                                                    <th style="width: 15%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Good</small></th>
                                                    <th style="width: 15%;"><i class="far fa-circle text-warning"></i><br><small class="text-muted" style="font-size: 0.75rem;">Partial</small></th>
                                                    <th style="width: 15%;"><i class="fas fa-times text-danger"></i><br><small class="text-muted" style="font-size: 0.75rem;">Bad</small></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="text-start ps-3">First Aid Kit</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[first_aid_kit]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[first_aid_kit]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[first_aid_kit]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Actual headcount</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[actual_headcount]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[actual_headcount]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[actual_headcount]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Directional Arrows</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[directional_arrows]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[directional_arrows]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[directional_arrows]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Attendance Sheet</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[attendance_sheet]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[attendance_sheet]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[attendance_sheet]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Megaphone</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[megaphone]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[megaphone]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[megaphone]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Group Signage</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[group_signage]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[group_signage]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[group_signage]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Guard on Duty</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[guard_on_duty]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[guard_on_duty]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[guard_on_duty]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">School ID of personnel</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[school_id]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[school_id]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[school_id]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Open Doors(EQ)</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[open_doors]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[open_doors]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[open_doors]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start ps-3">Closed Doors(Fire)</td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[closed_doors]" value="check" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[closed_doors]" value="circle" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                    <td><input class="form-check-input" type="radio" name="checklist_data[closed_doors]" value="x" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked; document.getElementsByName(this.name).forEach(r => r !== this ? r.wasChecked = false : null)"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Observers Presence (Edit Modal) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">Other Observers Presence</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="table-responsive border rounded">
                                        <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start ps-3" style="width: 75%;">Observer Group</th>
                                                    <th style="width: 25%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Present</small></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="text-start ps-3">Local Barangay</td><td><input class="form-check-input" type="radio" name="observers_data[local_barangay]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td></tr>
                                                <tr><td class="text-start ps-3">Philippine National Police (PNP)</td><td><input class="form-check-input" type="radio" name="observers_data[pnp]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td></tr>
                                                <tr><td class="text-start ps-3">City DRRM</td><td><input class="form-check-input" type="radio" name="observers_data[city_drrm]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td></tr>
                                                <tr><td class="text-start ps-3">Bureau of Fire Protection(BFP)</td><td><input class="form-check-input" type="radio" name="observers_data[bfp]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive border rounded mb-2">
                                        <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.875rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start ps-3" style="width: 75%;">Observer Group</th>
                                                    <th style="width: 25%;"><i class="fas fa-check text-success"></i><br><small class="text-muted" style="font-size: 0.75rem;">Present</small></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="text-start ps-3">PTA / Parent Observers</td><td><input class="form-check-input" type="radio" name="observers_data[pta_parents]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td></tr>
                                                <tr><td class="text-start ps-3">OTMPS</td><td><input class="form-check-input" type="radio" name="observers_data[otmps]" value="present" onclick="this.wasChecked ? (this.checked = false) : null; this.wasChecked = this.checked;"></td></tr>
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold text-secondary">Others (Please specify)</td>
                                                    <td><input class="form-check-input" type="radio" id="edit_observerOthersRadio" name="observers_data[others_present]" value="present" onclick="toggleObserverOthersInput(this, 'edit_observerOthersInputWrapper')"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="edit_observerOthersInputWrapper" class="d-none animate-fade-in">
                                        <input type="text" class="form-control form-control-sm border-primary" id="edit_observerOthersSpecified" name="observers_data[others_specified]" placeholder="Type other agency or observer name here..." disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Remarks / Findings</label>
                            <textarea class="form-control" name="remarks" id="edit_remarks" rows="3"></textarea>
                        </div>

                        <div class="modal-footer px-0 pb-0 mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="updateLogBtn">Update Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentViewedRecord = null;

async function viewDrillDetails(id) {
    try {
        const response = await fetch(`/drill-monitoring/${id}`);
        const record = await response.json();
        currentViewedRecord = record;
        
        const html = `
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Basic Information</label>
                    <p class="mb-1"><strong>Type:</strong> ${record.drill_type}</p>
                    <p class="mb-1"><strong>Date:</strong> ${new Date(record.inspection_date).toLocaleDateString('en-US', {month:'long', day:'numeric', year:'numeric'})}</p>
                    <p class="mb-1"><strong>Time:</strong> ${record.inspection_time}</p>
                    <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">${record.status}</span></p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Timing Details</label>
                    <p class="mb-1"><strong>Started:</strong> ${record.time_started || '—'}</p>
                    <p class="mb-1"><strong>Finished:</strong> ${record.time_finished || '—'}</p>
                    <p class="mb-1"><strong>Elapsed:</strong> <span class="text-primary fw-bold">${record.elapsed_time || '—'}</span></p>
                </div>
                <div class="col-md-6 border-top pt-3">
                    <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Participants</label>
                    <p class="mb-1"><strong>Students:</strong> ${record.no_of_students || 0}</p>
                    <p class="mb-1"><strong>Personnel:</strong> ${record.no_of_personnel || 0}</p>
                </div>
                <div class="col-md-6 border-top pt-3">
                    <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Monitors</label>
                    <p class="mb-1"><strong>Monitored By:</strong> ${record.monitored_by}</p>
                    <p class="mb-1"><strong>Coordinator:</strong> ${record.coordinator_name || 'N/A'}</p>
                </div>
                <div class="col-12 border-top pt-3">
                    <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Remarks / Observations</label>
                    <div class="p-3 bg-light rounded small">${record.remarks || 'No remarks recorded.'}</div>
                </div>
                <div class="col-md-7 border-top pt-3">
                    <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Drill Evaluation Results</label>
                    <div class="table-responsive border rounded bg-white" style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.8rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-start ps-3">Evaluation Item</th>
                                    <th style="width: 25%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${record.checklist_data ? (Array.isArray(record.checklist_data) ? 
                                    record.checklist_data.map(item => `<tr><td class="text-start ps-3">${item}</td><td><span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Good</span></td></tr>`).join('') :
                                    Object.entries(record.checklist_data).map(([key, val]) => {
                                        const labels = {
                                            'alarm_audible': 'Alarm', 'routes_clear': 'Evacuation plan(updated)', 'participants_calm': 'DRRM team',
                                            'hotline_numbers': 'Hotline Numbers', 'duck_cover_and_hold': 'Duck Cover and Hold (ER)', 'command_center': 'Command Center',
                                            'student_release_form': 'Student Release Form', 'exit_signage': 'Exit Signage', 'bert_sert': 'BERT/SERT',
                                            'wear_ids': 'Wearing of IDs', 'walk_casually': 'Walked Casually', 'first_aid_kit': 'First Aid Kit',
                                            'actual_headcount': 'Actual headcount', 'directional_arrows': 'Directional Arrows', 'attendance_sheet': 'Attendance Sheet',
                                            'megaphone': 'Megaphone', 'group_signage': 'Group Signage', 'guard_on_duty': 'Guard on Duty',
                                            'school_id': 'School ID of personnel', 'open_doors': 'Open Doors(EQ)', 'closed_doors': 'Closed Doors(Fire)'
                                        };
                                        let statusHtml = '';
                                        if (val === 'check') statusHtml = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Good</span>';
                                        else if (val === 'circle') statusHtml = '<span class="text-warning fw-bold"><i class="far fa-dot-circle me-1"></i>Partial</span>';
                                        else if (val === 'x') statusHtml = '<span class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i>Bad</span>';
                                        else return '';
                                        return `<tr><td class="text-start ps-3">${labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</td><td>${statusHtml}</td></tr>`;
                                    }).join('')
                                ) : '<tr><td colspan="2" class="text-muted py-3">No evaluation data recorded.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-5 border-top pt-3">
                    <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Observers Presence</label>
                    <div class="table-responsive border rounded bg-white" style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.8rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-start ps-3">Observer Group</th>
                                    <th style="width: 25%;">Present</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${record.observers_data ? (Array.isArray(record.observers_data) ? 
                                    record.observers_data.map(item => `<tr><td class="text-start ps-3">${item}</td><td><i class="fas fa-check text-success"></i></td></tr>`).join('') :
                                    Object.entries(record.observers_data).map(([key, val]) => {
                                        const labels = {
                                            'local_barangay': 'Local Barangay', 'pnp': 'Philippine National Police (PNP)', 'city_drrm': 'City DRRM',
                                            'bfp': 'Bureau of Fire Protection(BFP)', 'pta_parents': 'PTA / Parent Observers', 'otmps': 'OTMPS'
                                        };
                                        if (key === 'others_specified') return '';
                                        if (val !== 'present') return '';
                                        
                                        let name = labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                        if (key === 'others_present') {
                                            if (record.observers_data.others_specified) {
                                                name = `<strong>Others:</strong> ${record.observers_data.others_specified}`;
                                            } else {
                                                return ''; 
                                            }
                                        }
                                        return `<tr><td class="text-start ps-3">${name}</td><td><i class="fas fa-check text-success"></i></td></tr>`;
                                    }).join('')
                                ) : '<tr><td colspan="2" class="text-muted py-3">No observers recorded.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('viewModalContent').innerHTML = html;
        
        const printBtn = document.getElementById('printBtnInView');
        printBtn.href = `/drill-monitoring/monitoring-tool/${id}`;
        printBtn.classList.remove('d-none');
        
        const modal = new bootstrap.Modal(document.getElementById('viewMonitoringModal'));
        modal.show();
    } catch (error) {
        Swal.fire('Error', 'Could not load record details.', 'error');
    }
}

function openEditModalFromView() {
    if (!currentViewedRecord) return;
    
    // Close View Modal
    bootstrap.Modal.getInstance(document.getElementById('viewMonitoringModal')).hide();
    
    // Fill Edit Form
    const rec = currentViewedRecord;
    const form = document.getElementById('editMonitoringForm');
    
    document.getElementById('edit_id').value = rec.id;
    document.getElementById('edit_drill_type').value = rec.drill_type;
    document.getElementById('edit_inspection_date').value = rec.inspection_date;
    document.getElementById('edit_inspection_time').value = rec.inspection_time;
    document.getElementById('edit_time_started').value = rec.time_started || '';
    document.getElementById('edit_time_finished').value = rec.time_finished || '';
    document.getElementById('edit_elapsed_time').value = rec.elapsed_time || '';
    document.getElementById('edit_no_of_students').value = rec.no_of_students || 0;
    document.getElementById('edit_no_of_personnel').value = rec.no_of_personnel || 0;
    document.getElementById('edit_monitored_by').value = rec.monitored_by || '';
    document.getElementById('edit_coordinator_name').value = rec.coordinator_name || '';
    document.getElementById('edit_remarks').value = rec.remarks || '';

    // Reset and Populate Checklists in Edit Modal
    form.querySelectorAll('input[type="radio"]').forEach(r => {
        r.checked = false;
        r.wasChecked = false;
    });
    
    // Reset Others Input
    const observerWrapper = document.getElementById('edit_observerOthersInputWrapper');
    const observerInput = document.getElementById('edit_observerOthersSpecified');
    observerWrapper.classList.add('d-none');
    observerInput.disabled = true;
    observerInput.value = '';

    if (rec.checklist_data) {
        Object.entries(rec.checklist_data).forEach(([key, value]) => {
            const radio = form.querySelector(`input[name="checklist_data[${key}]"][value="${value}"]`);
            if (radio) {
                radio.checked = true;
                radio.wasChecked = true;
            }
        });
    }

    if (rec.observers_data) {
        Object.entries(rec.observers_data).forEach(([key, value]) => {
            if (key === 'others_specified') {
                observerInput.value = value;
            } else {
                const radio = form.querySelector(`input[name="observers_data[${key}]"][value="${value}"]`);
                if (radio) {
                    radio.checked = true;
                    radio.wasChecked = true;
                    if (key === 'others_present') {
                        observerWrapper.classList.remove('d-none');
                        observerInput.disabled = false;
                        observerInput.required = true;
                    }
                }
            }
        });
    }
    
    // Show Edit Modal
    const editModal = new bootstrap.Modal(document.getElementById('editMonitoringModal'));
    editModal.show();
}

document.getElementById('editMonitoringForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const id = document.getElementById('edit_id').value;
    const btn = document.getElementById('updateLogBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';
    
    try {
        const formData = new FormData(form);
        const response = await fetch(`/drill-monitoring/update/${id}`, {
            method: 'POST', // Using POST with _method PUT handled by FormData
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        Swal.fire('Update Failed', error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = 'Update Record';
    }
});

document.getElementById('logMonitoringForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = this;
    const btn = document.getElementById('saveLogBtn');
    const originalBtnHtml = btn.innerHTML;
    
    // Start Loading State
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    
    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Success Confirmation
            Swal.fire({
                icon: 'success',
                title: 'Record Saved!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message || 'Something went wrong.');
        }
    } catch (error) {
        // Error Handling
        Swal.fire({
            icon: 'error',
            title: 'Submission Failed',
            text: error.message
        });
        
        // Revert Loading State
        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
    }
});
</script>
@endpush
