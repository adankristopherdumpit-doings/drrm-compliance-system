<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Checklist - Fire Safety</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Inspection Checklist</h4>
                <small class="text-muted">
                    School: {{ $inspection->school->school_name ?? 'N/A' }} |
                    Building: {{ $inspection->building->building_no ?? 'N/A' }} |
                    Date: {{ $inspection->inspection_date }}
                </small>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('fire-safety.buildings') }}">
                Back to Buildings
            </a>
        </div>

        <div class="alert alert-info">
            This checklist page is a placeholder so the route works. You can now open inspections without a 404.
        </div>
    </div>
</body>
</html>

