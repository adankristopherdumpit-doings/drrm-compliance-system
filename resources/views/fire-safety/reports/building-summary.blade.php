<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School's Summarization of Fire Safety - Buildings</title>
    <style>
        @page {
            size: landscape;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        .header-container {
            margin-bottom: 20px;
        }
        .header-title {
            text-align: center;
            margin-bottom: 15px;
        }
        .header-title h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px;
        }
        .info-item {
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid black;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .vertical-th {
            height: 140px;
            white-space: nowrap;
            vertical-align: bottom;
            padding-bottom: 10px;
            text-align: center;
        }
        .vertical-th div {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            display: inline-block;
            word-break: break-word;
            white-space: pre-line;
            max-width: 60px;
            line-height: 1.2;
            text-align: center;
            padding: 5px 0;
        }
        .center-text {
            text-align: center;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                -webkit-print-color-adjust: exact;
                margin: 1cm;
            }
        }
        .btn-print {
            background: #A8191F;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f8f9fa; border-bottom: 1px solid #ddd; padding: 10px; text-align: center;">
        <button class="btn-print" onclick="window.print()">Print Report</button>
        <button class="btn-print" style="background: #6c757d;" onclick="window.close()">Close</button>
        <p class="small text-muted mb-0 mt-2">For a clean print without URL, date, or page numbers, turn off &quot;Headers and footers&quot; in the print dialog.</p>
    </div>

    <div class="header-container" style="position: relative; height: 80px; display: flex; align-items: center; margin-bottom: 10px;">
        <!-- Logo and Agency Name (absolute left) -->
        <div style="position: absolute; left: 0; top: 0; display: flex; align-items: center;">
            <img src="{{ asset('images/Layer-0-1.png') }}" alt="Logo 1" style="height: 60px; margin-right: 10px;">
            <img src="{{ asset('images/What-Is-the-Difference-Between-DepEd-Seal-and-DepEd-Logo.png') }}" alt="Logo 2" style="height: 60px; margin-right: 10px;">
            <img src="{{ asset('images/drrmis-logo-2.png') }}" alt="Logo 3" style="height: 60px; margin-right: 15px;">
            <div style="text-align: left;">
                <h2 style="margin: 0; font-size: 16px; font-weight: bold; text-transform: uppercase;">DepEd DRRM</h2>
            </div>
        </div>

        <!-- Main Title (centered) -->
        <div style="width: 100%; text-align: center;">
            <h1 style="margin: 0; font-size: 14px; font-weight: normal; text-transform: uppercase;">School's Summarization of Fire Safety – Buildings</h1>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item"><strong>Name of School:</strong> {{ $school->school_name }}</div>
        <div class="info-item"><strong>Name of School Head:</strong> {{ $school->school_head }}</div>
        <div class="info-item"><strong>School ID:</strong> {{ $school->school_id }}</div>
        <div class="info-item"><strong>Name of School DRRM Coordinator:</strong> {{ $school->school_drrm_coordinator }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">BUILDING NUMBER</th>
                <th style="width: 10%; text-align: center;">BUILDING NAME</th>
                <th class="vertical-th"><div>NUMBER OF FLOOR</div></th>
                <th class="vertical-th"><div>IS THE BUILDING<br>WITH SECONDARY<br>EXIT FOR<br>(2-4 STOREY<br>BUILDING)</div></th>
                <th class="vertical-th"><div>NUMBER OF CLASSROOMS</div></th>
                <th class="vertical-th"><div>NUMBER OF <br>ROOMS WITHOUT <br>SECONDARY EXIT</div></th>
                <th class="vertical-th"><div>NUMBER OF LABORATORIES</div></th>
                <th class="vertical-th"><div>NUMBER OF ADMINISTRATIVE<br> OFFICE</div></th>
                <th class="vertical-th" style="min-width: 60px;"><div>NUMBER OF REQUIRED<br> FIRE EXTINGUISHER</div></th>
                <th class="vertical-th" style="min-width: 80px;"><div>NUMBER OF ACTIVE<br> FIRE EXTINGUISHER</div></th>
                <th class="vertical-th"><div>SMOKE DETECTOR</div></th>
                <th class="vertical-th" style="min-width: 80px;"><div>ALARMS</div></th>
                <th style="width: 10%; text-align: center;">REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($school->buildings as $building)
                @php
                    $rooms = $building->actualRooms;

                    // Count classrooms - looking for room_type = 'classroom' or room types with 'classroom' in name
                    $classrooms = $rooms->filter(function($room) {
                        return strtolower($room->room_type) === 'classroom' ||
                               (isset($room->roomTypeConfig->name) && stripos($room->roomTypeConfig->name, 'classroom') !== false);
                    })->count();

                    // Count laboratories - looking for room_type = 'laboratory' or room types with 'lab' in name
                    $laboratories = $rooms->filter(function($room) {
                        return strtolower($room->room_type) === 'laboratory' ||
                               (isset($room->roomTypeConfig->name) && stripos($room->roomTypeConfig->name, 'lab') !== false);
                    })->count();

                    // Count administrative rooms - looking for room_type = 'office' or 'administrative' or room types with 'office' or 'admin' in name
                    $adminRooms = $rooms->filter(function($room) {
                        $typeName = strtolower($room->room_type ?? '');
                        $configName = isset($room->roomTypeConfig->name) ? strtolower($room->roomTypeConfig->name) : '';
                        return strpos($typeName, 'administration') !== false ||
                               strpos($typeName, 'office') !== false ||
                               strpos($typeName, 'administrative') !== false ||
                               strpos($configName, 'administration') !== false ||
                               strpos($configName, 'office') !== false ||
                               strpos($configName, 'admin') !== false;
                    });
                    $offices = $adminRooms->count();

                    // Count rooms without secondary exit using room-level flag
                    $roomsWithoutSecondaryExit = $rooms->filter(function($room) {
                        return !$room->has_secondary_exit || $room->has_secondary_exit == '0';
                    })->count();

                    // Get required fire extinguishers
                    $requiredExtinguishers = $building->required_extinguishers ?? $building->requiredExtinguishersCount;

                    // Processing Extinguishers
                    $allExtinguishers = $building->fireExtinguishers;
                    $activeCount = 0;
                    $extinguisherIssues = [];

                    foreach($allExtinguishers as $ext) {
                        $status = strtolower($ext->status ?? '');
                        if (in_array($status, ['active', 'ok', 'operational', 'okay'])) {
                            $activeCount++;
                        } else {
                            // Map status to code
                            $code = match($status) {
                                'purchase', 'for_purchase' => 'FP',
                                'maintenance', 'refill' => 'FR',
                                'decommissioned', 'broken' => 'DC',
                                'missing' => 'MS',
                                'expired' => 'FR',
                                default => 'FR'
                            };
                            $extinguisherIssues[] = $ext->code . ' ' . $code;
                        }
                    }

                    // Extinguisher Column Logic
                    $extinguisherBg = ($activeCount >= $requiredExtinguishers) ? '#90EE90' : '#e20707';
                    $extinguisherContent = empty($extinguisherIssues) ? $activeCount : implode(', ', $extinguisherIssues);

                    // Secondary Exit Logic for 2-4 Storey
                    $secExitBg = '';
                    $secExitContent = 'N/A';

                    if ($building->floors >= 2 && $building->floors <= 4) {
                        // Interpret emergency_exits status:
                        // 0 = N/A, 1 = No, 2+ = Yes
                        if (($building->emergency_exits ?? 0) >= 2) {
                            $secExitContent = 'Yes';
                            $secExitBg = '#90EE90';
                        } else {
                            $secExitContent = 'No';
                            $secExitBg = '#e20707';
                        }
                    }

                    // Alarms Logic
                    $alarms = $building->alarmSystemsMany;
                    if ($alarms->isEmpty()) {
                         $alarms = $building->alarmSystems;
                    }

                    $alarmBg = '#e20707'; // Default Red (Missing)
                    $alarmContentParts = [];
                    $hasBroken = false;
                    $hasMulti = false;
                    $hasAlarm = false;

                    if ($alarms->count() > 0) {
                        $hasAlarm = true;
                        foreach($alarms as $alarm) {
                            $status = strtolower($alarm->status ?? '');
                            $isFunctional = in_array($status, ['active', 'functional', 'ok', 'online']);

                            if (!$isFunctional) {
                                $hasBroken = true;
                            }

                            if ($alarm->buildings->count() > 1) {
                                $hasMulti = true;
                            }

                            $typeChar = 'O';
                            if (stripos($alarm->alarm_type, 'Bell') !== false) $typeChar = 'B';
                            elseif (stripos($alarm->alarm_type, 'Mechanical') !== false) $typeChar = 'M';
                            elseif (stripos($alarm->alarm_type, 'Digital') !== false) $typeChar = 'D';
                            else {
                                $typeChar = strtoupper(substr($alarm->alarm_type, 0, 2));
                            }

                            $alarmContentParts[] = $alarm->code . ' ' . $typeChar;
                        }

                        if ($hasBroken) {
                            $alarmBg = '#add8e6'; // Blue (Existing but bad status)
                        } elseif ($hasMulti) {
                            $alarmBg = '#FFFF00'; // Yellow (Active + Multi)
                        } else {
                            $alarmBg = '#90EE90'; // Green (Active + Single)
                        }
                    } else {
                         $alarmBg = '#e20707'; // Red (Missing)
                    }

                    $alarmContent = $hasAlarm ? implode(', ', $alarmContentParts) : '';

                    // Smoke Detector Logic for Administrative/Office Rooms
                    $adminRoomsCount = $adminRooms->count();
                    $smokeDetectorCount = $adminRooms->where('has_smoke_detector', true)->count();

                    if ($adminRoomsCount === 0) {
                        $smokeDetectorContent = 'N/A';
                        $smokeDetectorBg = '';
                    } else if ($smokeDetectorCount === $adminRoomsCount) {
                        $smokeDetectorContent = $smokeDetectorCount;
                        $smokeDetectorBg = '#90EE90'; // Green
                    } else {
                        $smokeDetectorContent = $smokeDetectorCount;
                        $smokeDetectorBg = '#e20707'; // Red
                    }

                @endphp
                <tr>
                    <td class="center-text" style="padding: 4px;">{{ $building->building_no }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $building->building_name }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $building->floors }}</td>
                    <td class="center-text" style="padding: 4px; background-color: {{ $secExitBg }};">{{ $secExitContent }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $classrooms }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $roomsWithoutSecondaryExit }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $laboratories }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $offices }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $requiredExtinguishers }}</td>
                    <td class="center-text" style="padding: 4px; background-color: {{ $extinguisherBg }};">{{ $extinguisherContent }}</td>
                    <td class="center-text" style="padding: 4px; background-color: {{ $smokeDetectorBg }};">{{ $smokeDetectorContent }}</td>
                    <td class="center-text" style="padding: 4px; background-color: {{ $alarmBg }};">{{ $alarmContent }}</td>
                    <td class="center-text" style="padding: 4px;">{{ $building->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; display: flex; justify-content: space-between; padding: 0 50px;">
        <div style="text-align: center;">
            <p>Prepared by:</p>
            <br><br>
            <p><strong>{{ $school->school_drrm_coordinator }}</strong></p>
            <p>School DRRM Coordinator</p>
        </div>
        <div style="text-align: center;">
            <p>Certified Correct:</p>
            <br><br>
            <p><strong>{{ $school->school_head }}</strong></p>
            <p>School Head / Principal</p>
        </div>
    </div>
</body>
</html>
