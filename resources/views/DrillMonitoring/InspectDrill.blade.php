INSPECT NOW MODAL
   
    <!-- Inspect Now Modal (Drill Management) -->
    <div class="modal fade" id="inspectNowModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check me-2"></i> Drill & Inspection Monitoring
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="inspectNowForm">
                        @csrf
                        <input type="hidden" name="unified_school_id" value="{{ $activeSchool->id ?? '' }}">

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

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Exits</label>
                                <input type="number" class="form-control" name="no_of_exits" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Buildings</label>
                                <input type="number" class="form-control" name="no_of_buildings" value="{{ $activeSchool->buildings->count() }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Students</label>
                                <input type="number" class="form-control" name="no_of_students" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">No. of Personnel</label>
                                <input type="number" class="form-control" name="no_of_personnel" value="0">
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                                    <i class="fas fa-list-check me-2"></i> Safety Checklist
                                </h6>
                                <div class="checklist-items scroll-y" style="max-height: 250px; overflow-y: auto;">
                                    @if(isset($checklists) && count($checklists) > 0)
                                        @foreach($checklists as $item)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="checklist_data[]" value="{{ $item->name }}" id="check_{{ $item->id }}">
                                                <label class="form-check-label small" for="check_{{ $item->id }}">
                                                    {{ $item->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted small">No checklist items configured in customization.</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-users-viewfinder me-2"></i> Other Observers
                                </h6>
                                <div class="observer-items scroll-y" style="max-height: 250px; overflow-y: auto;">
                                    @if(isset($observers) && count($observers) > 0)
                                        @foreach($observers as $obs)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="observers_data[]" value="{{ $obs->name }}" id="obs_{{ $obs->id }}">
                                                <label class="form-check-label small" for="obs_{{ $obs->id }}">
                                                    {{ $obs->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="obs_others_chk" onchange="toggleOthersInput(this, 'obs_others_text_container')">
                                        <label class="form-check-label small" for="obs_others_chk">OTHERS: (Please specify)</label>
                                    </div>
                                    <div id="obs_others_text_container" style="display: none;" class="ms-4 mb-2">
                                        <input type="text" class="form-control form-control-sm border-primary" id="obs_others_text" placeholder="Specify other observers...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Remarks / Findings</label>
                            <textarea class="form-control border-primary" name="remarks" rows="3" placeholder="Enter any observations or findings during the drill..."></textarea>
                        </div>

                        <div class="row border-top pt-3 bg-light rounded p-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Monitored By:</label>
                                <input type="text" class="form-control mb-2" name="monitored_by" value="{{ auth()->user()->name }}" placeholder="Name" required>
                                <input type="text" class="form-control" name="monitored_by_position" placeholder="Position">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">School DRRM Coordinator</label>
                                <input type="text" class="form-control" name="coordinator_name" value="{{ $activeSchool->school_drrm_coordinator ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">School Head / Principal</label>
                                <input type="text" class="form-control" name="school_head_name" value="{{ $activeSchool->school_head ?? '' }}">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if(auth()->user()->role !== 'viewer')
                    <button type="button" class="btn btn-success px-4" onclick="saveDrillInspection()">
                        <i class="fas fa-save me-2"></i> Save & Record Inspection
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('fire-safety.partials.inspection-modals')