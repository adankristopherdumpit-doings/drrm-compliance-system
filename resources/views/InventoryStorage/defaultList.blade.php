<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>School Safety & Emergency Inventory</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{
    --drrm-teal:#0D7377;
    --drrm-teal-hover:#0a5a5d;
    --drrm-teal-light:#e6f1f1;
}

body{
    background:#f4f7f6;
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
}

/* Sidebar */
.sidebar{
    min-height:100vh;
    background:var(--drrm-teal);
    color:white;
    box-shadow:2px 0 5px rgba(0,0,0,.1);
}

.sidebar-heading{
    font-size:1.1rem;
    font-weight:bold;
    text-transform:uppercase;
    padding:1.5rem;
    border-bottom:1px solid rgba(255,255,255,.1);
}

.sidebar .nav-link{
    color:rgba(255,255,255,.75);
    padding:.9rem 1.5rem;
    transition:.3s;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active{
    color:white;
    background:var(--drrm-teal-hover);
    border-left:4px solid white;
}

/* Cards */
.card{
    border:none;
    border-radius:12px;
    box-shadow:0 .125rem .25rem rgba(0,0,0,.075);
}

.stat-card{
    border-left:4px solid var(--drrm-teal);
}

.text-teal{
    color:var(--drrm-teal);
}

.btn-teal{
    background:var(--drrm-teal);
    color:white;
}

.btn-teal:hover{
    background:var(--drrm-teal-hover);
    color:white;
}

.table thead{
    background:var(--drrm-teal-light);
}

.form-control:focus{
    border-color:var(--drrm-teal);
    box-shadow:0 0 0 .2rem rgba(13,115,119,.15);
}

.section-badge{
    background:var(--drrm-teal);
    color:white;
    padding:.4rem .75rem;
    border-radius:6px;
    font-weight:600;
}

.inventory-icon{
    color:var(--drrm-teal);
}
</style>
</head>

<body>

<div class="container-fluid">
<div class="row">
<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block sidebar px-0">

    <div class="sidebar-heading text-center">
        <i class="fa-solid fa-boxes-stacked me-2"></i>
        Inventory Manager
    </div>

    <div class="position-sticky pt-3">

        <ul class="nav flex-column">

            <li class="nav-item">
                <a class="nav-link" href="{{ route('inventory-storage.dashboard') }}">
                    <i class="fa-solid fa-gauge-high me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('inventory-storage.default-list') }}">
                    <i class="fa-solid fa-list me-2"></i>
                    Default List
                </a>
            </li>

        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white-50"
            style="font-size:0.75rem; border:none;">
            <span>Saved Reports</span>
        </h6>

        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fa-solid fa-file-lines me-2"></i>
                    Monthly Summary
                </a>
            </li>
        </ul>

    </div>

</nav>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center pt-4 pb-3 mb-4 border-bottom">

            <div>
                <h2 class="fw-bold text-teal">
                    Default Inventory List
                </h2>
                <p class="text-muted mb-0">
                    Supplies and equipment provided by DepEd and/or Partners
                </p>
            </div>

                    <button type="button" class="btn btn-sm btn-teal" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fa-solid fa-plus me-1"></i> Add New Item
                    </button>

        </div>


        <!-- Inventory Table -->
        <div class="card">

            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

                <div>
                    <span class="section-badge me-2">A</span>
                    <span class="fw-semibold text-teal">
                        Emergency Supplies and Equipment
                    </span>
                </div>

                <input
                    type="text"
                    class="form-control w-auto"
                    placeholder="Search Item..."
                >

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="text-center">Source</th>
                        <th class="text-center">Date checked</th>
                    </tr>
                    </thead>

                    <tbody>               
                        <td>
                            <div class="d-flex align-items-center">
                                <!-- Image element added here -->
                                <img src="{{ asset('images/aluminumstretcher.jpg') }}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 4px;">
                                <span>2-fold Aluminum Stretcher</span>
                            </div>
                        </td>
                        <td class="text-center">

                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>

                    </td>
                        <td class="text-center">
                            <input type="date" class="form-control border-primary" name="dateChecked" value="{{ date('Y-m-d') }}" required>
                        </td>
                    </tr>

                    <tr>
                        <div><td>Cadaver bag</td><div>
                                                   <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="date" class="form-control border-primary" name="dateChecked" value="{{ date('Y-m-d') }}" required></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>C-Collars</td><div>
                                                   <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="date" class="form-control border-primary" name="dateChecked" value="{{ date('Y-m-d') }}" required></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Cot (Battlefield Bed)</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="date" class="form-control border-primary" name="dateChecked" value="{{ date('Y-m-d') }}" required></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>CPR board</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="date" class="form-control border-primary" name="dateChecked" value="{{ date('Y-m-d') }}" required></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Emergency Head Lamp</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="date" class="form-control border-primary" name="dateChecked" value="{{ date('Y-m-d') }}" required></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Emergency Whistle</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Fire Extinguisher</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Go bag with Multi-Tool for each learner</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Go bag with Multi-Tool for each personnel</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Handheld / Base Radios</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>LED search light, 850 lumens</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Life Vest / Life Jacket</td><div>
                                                        <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Medical cushion</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Plastic Spine Board with Safety Belts</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div><td>Portable P.A. system</td><div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div> <td>Safety Coat</td><div>
                                                        <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Safety Helmet</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>safety Shoes</td>
                        <div>
                                                   <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Splinter</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Steel boxes</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Steel Cabinets</td>
                        <div>
                                                   <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Traffic Vest</td>
                        <div>
                                                <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Transport Bags, 45L</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Trauma Bag with contents for 20–25 persons</td>
                        <div>
                                                   <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Universal head immobilizer</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>

            </tbody>

                </table>

            </div>

        </di
        <!-- Response and Rescue Supplies Table --!>
<div class="card mt-4">

    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

        <div>
            <span class="section-badge me-2">B</span>
            <span class="fw-semibold text-teal">
                Response and Rescue Supplies
            </span>
        </div>

        <input
            type="text"
            class="form-control w-auto"
            placeholder="Search Item..."
        >

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="text-center">Sources</th>
                    <th class="text-center">Date Checked</th>
                </tr>
            </thead>

            <tbody>
                    <tr>
                        <div>
                        <td>Bicycle</td>
                        <div>
                            <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Fire Hose</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Motor Banca</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
                    <tr>
                        <div>
                        <td>Power Sprayer</td>
                        <div>
                                                    <td class="text-center">                  
                        <select class="form-select form-select-sm mx-auto mb-2 source-select" style="width:160px;">
                            <option value="">Select Source</option>
                            <option value="deped">DepEd</option>
                            <option value="partner">Partner</option>
                        </select>

                        <select class="form-select form-select-sm deped-options d-none" style="width:160px;">
                            <option value="">Select DepEd Source</option>
                            <option value="GAA">GAA</option>
                            <option value="Special Purpose Fund">Special Purpose Fund</option>
                            <option value="Other DepEd Sources">Other DepEd Sources</option>
                            <option value="Others">Others</option>
                        </select>

                        <select class="form-select form-select-sm partner-options d-none" style="width:160px;">
                            <option value="">Select Partner Source</option>
                            <option value="NGO">NGO</option>
                            <option value="LGU">LGU</option>
                            <option value="Private Sector">Private Sector</option>
                            <option value="Others">Others</option>
                        </select>
</td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto text-center" style="width: 70px;" min="0"></td>
                        <td class="text-center text-muted small">-</td>
                    </tr>
            </tbody>

        </table>

    </div>

</div>
    </main>

</div>
</div>


<script>
document.querySelectorAll('.source-select').forEach(function(select){
  select.addEventListener('change',function(){
    const td=this.parentElement;
    const deped=td.querySelector('.deped-options');
    const partner=td.querySelector('.partner-options');
    deped.classList.add('d-none');
    partner.classList.add('d-none');
    if(this.value==='deped') deped.classList.remove('d-none');
    if(this.value==='partner') partner.classList.remove('d-none');
  });
});
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
