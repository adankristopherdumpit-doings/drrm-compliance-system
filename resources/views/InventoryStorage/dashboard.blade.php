<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management Dashboard | DRRM Compliance</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --drrm-teal: #0D7377;
            --drrm-teal-hover: #0a5a5d;
            --drrm-teal-light: #e6f1f1;
            --drrm-teal-border: #20a39e;
        }

        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styling */
        .sidebar {
            min-height: 100vh;
            background-color: var(--drrm-teal);
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 0.8rem 1.5rem;
            transition: all 0.3s;
            border-radius: 0;
        }

        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: white;
            background-color: var(--drrm-teal-hover);
            border-left: 4px solid #fff;
        }

        .sidebar-heading {
            font-size: 1.1rem;
            font-weight: bold;
            text-transform: uppercase;
            padding: 1.5rem;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #edf2f2;
            font-weight: 600;
            color: var(--drrm-teal);
        }

        .stat-card {
            border-left: 4px solid var(--drrm-teal);
        }

        .btn-teal {
            background-color: var(--drrm-teal);
            color: white;
        }

        .btn-teal:hover {
            background-color: var(--drrm-teal-hover);
            color: white;
        }

        .map-container {
            height: 450px;
            background-color: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed var(--drrm-teal);
            color: var(--drrm-teal);
        }

        .text-teal {
            color: var(--drrm-teal) !important;
        }

        .badge-hazard {
            padding: 0.5em 0.8em;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse px-0">
            <div class="sidebar-heading text-center">
                <i class="fa-solid fa-boxes-stacked me-2"></i> Inventory Manager
            </div>
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fa-solid fa-gauge-high me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('inventory-storage.default-list') }}">
                            <i class="fa-solid fa-list me-2"></i> Default List
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white-50" style="font-size: 0.75rem; border: none;">
                    <span>Saved Reports</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fa-solid fa-file-lines me-2"></i> Monthly Summary
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Inventory & Resource Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                    <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
</a>
                    <button type="button" class="btn btn-sm btn-teal" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fa-solid fa-plus me-1"></i> Add New Item
                    </button>
                </div>
            </div>

            <!-- Recent Logs Table -->
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <i class="fa-solid fa-list-check me-2"></i> Inventory View
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Unit</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Fund Source</th>
                                    <th>Date Received</th>
                                    <th>Date Checked</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>{{ $item->item_name }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td><span class="badge bg-info text-dark">{{ str_replace('_', ' ', $item->status) }}</span></td>
                                        <td>{{ $item->location }}</td>
                                        <td>{{ $item->fund_source }}</td>
                                        <td>{{ $item->date_received ? \Carbon\Carbon::parse($item->date_received)->format('M d, Y') : 'N/A' }}</td>
                                        <td>{{ $item->date_checked ? \Carbon\Carbon::parse($item->date_checked)->format('M d, Y') : 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No inventory items found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: var(--drrm-teal);">
                <h5 class="modal-title" id="addItemModalLabel"><i class="fa-solid fa-box-open me-2"></i>Add New Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('inventory-storage.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Item Name</label>
                            <input type="text" class="form-control" name="item_name" placeholder="Enter item name..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Unit</label>
                    <select class="form-select" name="fund_source" id="fundSourceSelect">
                        <option value="" disabled selected hidden>Select Unit type</option>
                        <option value="Boxes">Boxes</option>
                        <option value="Sets">Sets</option>
                        <option value="Pieces">Pieces</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                    <div class="col-md-12 d-none" id="otherFundSourceWrapper">
    <label class="form-label fw-bold">Other Unit type</label>
    <input type="text" class="form-control" name="Unit_type_other" id="UnitOtherInput" placeholder="crates, sets, pallets, etc.">
</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="0" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="working">Working</option>
                                <option value="needs_attention">Needs Attention</option>
                                <option value="for_repair">For Repair</option>
                                <option value="defective">Defective</option>
                                <option value="missing">Missing</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Location</label>
                            <input type="text" class="form-control" name="location" placeholder="Storage area">
                        </div>
<div class="col-12">
    <label class="form-label fw-bold">Fund Source</label>
    <!-- Added id="fundSourceSelect" -->
    <select class="form-select" name="fund_source" id="fundSourceSelect">
        <option value="" disabled selected hidden>Select Funding Source</option>
        <option value="school_Mooe">School MOOE</option>
        <option value="SEF">SEF</option>
        <option value="Division_office">Division Office</option>
        <option value="Regional_office">Regional Office</option>
        <option value="Central_office">Central Office</option>
        <option value="FTPA_PTA_GPTA">FTPA / PTA / GPTA</option>
        <option value="Others">Others (Specify below)</option>
    </select>
</div>

<!-- Added id="otherFundSourceWrapper" and Bootstrap's d-none class to hide it by default -->
<div class="col-md-12 d-none" id="otherFundSourceWrapper">
    <label class="form-label fw-bold">Other Fund Source (If applicable)</label>
    <input type="text" class="form-control" name="fund_source_other" id="fundSourceOtherInput" placeholder="e.g., donation, specific project">
</div>

<script>
    document.getElementById('fundSourceSelect').addEventListener('change', function() {
        const otherWrapper = document.getElementById('otherFundSourceWrapper');
        const otherInput = document.getElementById('fundSourceOtherInput');
        
        if (this.value === 'Others') {
            // Remove d-none to show the input field
            otherWrapper.classList.remove('d-none');
            // Make it a required field if "Others" is selected
            otherInput.setAttribute('required', 'required');
        } else {
            // Add d-none back to hide it
            otherWrapper.classList.add('d-none');
            // Remove the required attribute so the form can submit normally
            otherInput.removeAttribute('required');
            // Clear the text box so old typed data isn't accidentally submitted
            otherInput.value = '';
        }
    });
</script>

                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Date Received (Base your answer on Inventory custodian slip a deed of donation)</label>
                        <input type="date" class="form-control" name="date_received" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Date Checked</label>
                        <input type="date" class="form-control" name="date_checked" value="{{ date('Y-m-d') }}">
                    </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>