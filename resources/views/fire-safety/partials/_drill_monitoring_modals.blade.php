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
                    <!-- ... [All Modal Form Fields from buildings.blade.php] ... -->
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

<script>
    function toggleOthersInput(chk, containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.style.display = chk.checked ? 'block' : 'none';
            if (chk.checked) {
                const input = container.querySelector('input');
                if (input) input.focus();
            }
        }
    }

    async function saveDrillInspection() {
        const form = document.getElementById('inspectNowForm');
        const formData = new FormData(form);
        // ... [JS Logic for saveDrillInspection moved here] ...
    }

    async function loadInspections(schoolId) {
        // ... [JS Logic for loadInspections moved here] ...
    }

    function getDrillBadgeClass(type) {
        if (type === 'Earthquake') return 'bg-warning text-dark';
        if (type === 'Fire') return 'bg-danger';
        if (type === 'Both') return 'bg-primary';
        return 'bg-secondary';
    }

    async function viewInspection(id) {
        // ... [JS Logic for viewInspection moved here] ...
    }

    function openUpdateInspectionModal() {
        // ... [JS Logic for openUpdateInspectionModal moved here] ...
    }

    async function updateDrillInspection() {
        // ... [JS Logic for updateDrillInspection moved here] ...
    }
</script>
