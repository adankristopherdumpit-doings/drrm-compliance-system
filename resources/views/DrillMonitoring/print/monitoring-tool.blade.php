<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drill Monitoring Tool Report</title>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.2; color: #333; margin: 0; padding: 10px; font-size: 11px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h3 { margin: 2px 0; text-transform: uppercase; }
        .header p { margin: 1px 0; }

        .monitoring-tool-title { text-align: center; font-weight: bold; font-size: 12px; margin: 10px 0; border: 1px solid #000; padding: 5px; }

        .data-grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .data-grid td { border: 1px solid #000; padding: 4px 6px; }
        .label { font-weight: bold; background-color: #f2f2f2; }

        .results-section { margin-top: 10px; }
        .results-section h4 { padding-bottom: 2px; margin-bottom: 5px; margin-top: 5px; font-size: 12px; }

        .checklist-table { width: 100%; border-collapse: collapse; }
        .checklist-table td { border: 1px solid #000; padding: 3px 5px; vertical-align: middle; }
        .check-box { width: 15px; height: 15px; border: 1px solid #000; display: inline-block; text-align: center; font-weight: bold; line-height: 15px; margin-right: 5px; }

        .signatures { width: 35%; margin-top: 15px; border-collapse: collapse; margin-left: 0; }
        .signatures td { text-align: center; padding-top: 15px; }
        .sig-line { border-bottom: 0.5pt solid #000; margin-bottom: 2px; min-width: 180px; padding-bottom: 1px; display: inline-block; width: 100%; }
        .sig-title { margin-top: 2px; font-weight: bold; }

        @media print {
            @page { 
                size: letter portrait;
                margin: 0; 
            }
            body { 
                padding: 0;
                margin: 1.5cm; 
            }
            .no-print { display: none; }
        }

        .no-print { 
            margin-bottom: 20px; 
            text-align: center; 
            padding: 15px; 
            background: #f8f9fa; 
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .print-btn { padding: 10px 20px; background: #2c3e50; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; text-decoration: none; }
        .back-btn { padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; text-decoration: none; }
        
        /* Fixed Header Layout */
        .print-header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 30%;
            text-align: left;
        }
        .header-center {
            display: table-cell;
            vertical-align: middle;
            width: 40%;
            text-align: center;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            width: 30%;
            text-align: right;
        }
        .header-logos {
            display: flex;
            align-items: center;
        }
        .header-logos img {
            height: 35px;
            margin-right: 4px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="print-btn">Print Document</button>
        <a href="{{ route('drill-monitoring.dashboard') }}" class="back-btn">Back to Dashboard</a>
    </div>

    <!-- Improved Header using Table-like behavior for Print Stability -->
    <div class="print-header">
        <div class="header-left">
            <div class="header-logos">
                <img src="{{ asset('images/Layer-0-1.png') }}" alt="Logo 1">
                <img src="{{ asset('images/What-Is-the-Difference-Between-DepEd-Seal-and-DepEd-Logo.png') }}" alt="Logo 2">
                <img src="{{ asset('images/drrmis-logo-2.png') }}" alt="Logo 3">
                <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; margin-left: 3px;">DepEd DRRM</div>
            </div>
        </div>
        <div class="header-center">
            <h3 style="margin: 0; font-size: 11px; font-weight: bold; text-transform: uppercase; line-height: 1.1;">
                MONITORING TOOL FOR EARTHQUAKE AND FIRE DRILLS IN SCHOOLS
            </h3>
        </div>
        <div class="header-right">
            <!-- Reserved for potential barcodes or document codes -->
        </div>
    </div>

    <!-- Custom Data Grid Layout -->
    <table class="data-grid">
        <colgroup>
            <col style="width: 8%;">
            <col style="width: 25.33%;">
            <col style="width: 8%;">
            <col style="width: 25.33%;">
            <col style="width: 8%;">
            <col style="width: 25.33%;">
        </colgroup>
        <tr>
            <td class="label" colspan="2">NAME OF SCHOOL</td>
            <td colspan="2"><strong>{{ $inspection->school->school_name ?? 'N/A' }}</strong></td>
            <td class="label" colspan="2" style="text-align: center;">TYPE OF DRILL</td>
        </tr>
        <tr>
            <td class="label" colspan="2">SCHOOL ID</td>
            <td colspan="2">{{ $inspection->school->school_id ?? 'N/A' }}</td>
            <td style="text-align: center; border-right: none;">
                <span class="check-box" style="margin: 0;">
                    {{ (in_array($inspection->drill_type, ['Earthquake', 'Both'])) ? '✓' : '' }}
                </span>
            </td>
            <td style="border-left: none; font-weight: bold;">EARTHQUAKE</td>
        </tr>
        <tr>
            <td class="label" colspan="2">DATE AND TIME OF DRILL</td>
            <td colspan="2">
                {{ date('F d, Y', strtotime($inspection->inspection_date)) }} 
                {{ $inspection->inspection_time ?? '' }}
            </td>
            <td style="text-align: center; border-right: none;">
                <span class="check-box" style="margin: 0;">
                    {{ (in_array($inspection->drill_type, ['Fire', 'Both'])) ? '✓' : '' }}
                </span>
            </td>
            <td style="border-left: none; font-weight: bold;">FIRE</td>
        </tr>
        <tr>
            <td class="label" colspan="2"></td>
            <td colspan="2"></td>
            <td style="text-align: center; border-right: none;">
                <span class="check-box" style="margin: 0;">
                    {{ ($inspection->drill_type === 'Tsunami') ? '✓' : '' }}
                </span>
            </td>
            <td style="border-left: none; font-weight: bold;">TSUNAMI</td>
        </tr>
        <tr>
            <td class="label" colspan="2"></td>
            <td colspan="2"></td>
            <td style="text-align: center; border-right: none;">
                <span class="check-box" style="margin: 0;">
                    {{ ($inspection->drill_type === 'Lockdown') ? '✓' : '' }}
                </span>
            </td>
            <td style="border-left: none; font-weight: bold;">LOCKDOWN</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold;">{{ $inspection->time_started ?? 'N/A' }}</td>
            <td class="label">TIME STARTED</td>
            <td style="text-align: center; font-weight: bold;">{{ number_format($inspection->no_of_students ?? 0) }}</td>
            <td class="label">NO. OF STUDENTS</td>
            <td style="text-align: center; font-weight: bold;">{{ $inspection->no_of_exits ?? 0 }}</td>
            <td class="label">NO. OF EXIT/S</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold;">{{ $inspection->time_finished ?? 'N/A' }}</td>
            <td class="label">TIME FINISHED</td>
            <td style="text-align: center; font-weight: bold;">{{ number_format($inspection->no_of_personnel ?? 0) }}</td>
            <td class="label">NO. OF PERSONNEL</td>
            <td style="text-align: center; font-weight: bold;">{{ $inspection->no_of_buildings ?? 0 }}</td>
            <td class="label">NO. OF BLDG/S</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background-color: #f9f9f9;">{{ $inspection->elapsed_time ?? 'N/A' }}</td>
            <td class="label">ELAPSED TIME</td>
            <td style="text-align: center; font-weight: bold; background-color: #f9f9f9;">{{ number_format(($inspection->no_of_students ?? 0) + ($inspection->no_of_personnel ?? 0)) }}</td>
            <td class="label">TOTAL COUNT</td>
            <td colspan="2" style="background-color: #f2f2f2;"></td>
        </tr>
    </table>

\    <!-- CHECKLIST ITEMS - 3 COLUMNS -->
    <div class="results-section">
        <h4>CHECKLIST ITEMS</h4>
        <div style="margin-bottom: 5px; font-style: italic; font-size: 9px;">
            Legend: ✓ Good | ○ Partial | ✘ Bad
        </div>
        <table class="checklist-table">
            @php
                $checklistData = $inspection->checklist_data ?? [];
                
                // Mapping of evaluation keys to their formal labels
                $evaluationMapping = [
                    'alarm_audible' => 'Alarm',
                    'routes_clear' => 'Evacuation plan(updated)',
                    'participants_calm' => 'DRRM team',
                    'hotline_numbers' => 'Hotline Numbers',
                    'duck_cover_and_hold' => 'Duck Cover and Hold (ER)',
                    'command_center' => 'Command Center',
                    'student_release_form' => 'Student Release Form',
                    'exit_signage' => 'Exit Signage',
                    'bert_sert' => 'BERT/SERT',
                    'wear_ids' => 'Wearing of IDs',
                    'walk_casually' => 'Walked Casually',
                    'first_aid_kit' => 'First Aid Kit',
                    'actual_headcount' => 'Actual headcount',
                    'directional_arrows' => 'Directional Arrows',
                    'attendance_sheet' => 'Attendance Sheet',
                    'megaphone' => 'Megaphone',
                    'group_signage' => 'Group Signage',
                    'guard_on_duty' => 'Guard on Duty',
                    'school_id' => 'School ID of personnel',
                    'open_doors' => 'Open Doors(EQ)',
                    'closed_doors' => 'Closed Doors(Fire)'
                ];

                $displayItems = [];
                $keys = array_keys($checklistData);
                $isEvaluationStyle = !empty($keys) && is_string($keys[0]);

                if ($isEvaluationStyle) {
                    // Handle structured evaluation data (from Log New Drill form)
                    foreach ($evaluationMapping as $key => $label) {
                        $val = $checklistData[$key] ?? '';
                        $symbol = '&nbsp;';
                        if ($val === 'check') $symbol = '✓';
                        elseif ($val === 'circle') $symbol = '○';
                        elseif ($val === 'x') $symbol = '✘';
                        
                        $displayItems[] = ['label' => $label, 'symbol' => $symbol];
                    }
                } else {
                    // Fallback for simple checkbox-style data (from Inspect Now modal)
                    foreach ($checklistData as $item) {
                        $displayItems[] = ['label' => $item, 'symbol' => '✓'];
                    }
                }
                $chunks = array_chunk($displayItems, 3);
            @endphp

            @forelse($chunks as $chunk)
            <tr>
                @foreach($chunk as $item)
                <td style="width: 33%;">
                    <span class="check-box">
                        {!! $item['symbol'] !!}
                    </span>
                    {{ $item['label'] }}
                </td>
                @endforeach

                @if(count($chunk) < 3)
                    @for($i = count($chunk); $i < 3; $i++)
                        <td style="width: 33%;">&nbsp;</td>
                    @endfor
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #888; padding: 15px;">
                    No checklist items configured or recorded.
                </td>
            </tr>
            @endforelse
        </table>
    </div>

    <!-- OTHER OBSERVERS PRESENT - 3 COLUMNS -->
    @if((isset($observers) && count($observers) > 0) || !empty($inspection->observers_data))
    <div class="results-section" style="margin-top: 5px;">
        <h4 style="margin-bottom: 3px;">OTHER OBSERVERS PRESENT</h4>
        <table class="checklist-table">
            @php
                $obsData = $inspection->observers_data ?? [];
                
                // Mapping for structured data from Log New Drill
                $observerMapping = [
                    'local_barangay' => 'Local Barangay',
                    'pnp'            => 'Philippine National Police (PNP)',
                    'city_drrm'      => 'City DRRM',
                    'bfp'            => 'Bureau of Fire Protection(BFP)',
                    'pta_parents'    => 'PTA / Parent Observers',
                    'otmps'          => 'OTMPS'
                ];

                $displayObservers = [];
                
                // Check if it's associative (structured) or simple array
                $keys = array_keys($obsData);
                $isStructured = !empty($keys) && is_string($keys[0]);

                if ($isStructured) {
                    // 1. Handle structured data (Log New Drill form)
                    foreach ($observerMapping as $key => $label) {
                        $isChecked = (isset($obsData[$key]) && $obsData[$key] === 'present');
                        $displayObservers[] = ['label' => $label, 'checked' => $isChecked];
                    }
                    
                    // 2. Handle "Others" from structured data
                    if (isset($obsData['others_present']) && $obsData['others_present'] === 'present') {
                        $label = 'Others: ' . ($obsData['others_specified'] ?? 'Not specified');
                        $displayObservers[] = ['label' => $label, 'checked' => true];
                    }
                } else {
                    // Fallback for simple array (Inspect Now) or DB configuration
                    // Get base list from DB if exists
                    $baseList = (isset($observers) && count($observers) > 0) 
                        ? $observers->pluck('name')->toArray() 
                        : [];
                    
                    // Merge current data items that aren't already in baseList and aren't custom "Others:"
                    foreach ($obsData as $item) {
                        if (strpos($item, 'Others: ') !== 0 && !in_array($item, $baseList)) {
                            $baseList[] = $item;
                        }
                    }

                    foreach ($baseList as $name) {
                        $displayObservers[] = ['label' => $name, 'checked' => in_array($name, $obsData)];
                    }

                    // Add custom "Others: [text]" items found in data
                    foreach ($obsData as $item) {
                        if (strpos($item, 'Others: ') === 0) {
                            $displayObservers[] = ['label' => $item, 'checked' => true];
                        }
                    }
                }

                $obsChunks = array_chunk($displayObservers, 3);
            @endphp

            @forelse($obsChunks as $chunk)
            <tr>
                @foreach($chunk as $observer)
                <td style="width: 33%;">
                    <span class="check-box">
                        @if($observer['checked'])
                            ✓
                        @else
                            &nbsp;
                        @endif
                    </span>
                    {{ $observer['label'] }}
                </td>
                @endforeach

                @if(count($chunk) < 3)
                    @for($i = count($chunk); $i < 3; $i++)
                        <td style="width: 33%;">&nbsp;</td>
                    @endfor
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #888; padding: 15px;">
                    No observers recorded.
                </td>
            </tr>
            @endforelse
        </table>
    </div>
    @endif

    <!-- REMARKS / OBSERVATIONS - MOVED BELOW OBSERVERS -->
    @if($inspection->remarks)
    <div class="results-section" style="margin-top: 5px;">
        <h4 style="margin-bottom: 3px;">REMARKS / OBSERVATIONS</h4>
        <div style="border: 1px solid #000; padding: 5px; min-height: 40px;">
            {{ $inspection->remarks }}
        </div>
    </div>
    @endif

    <!-- SIGNATURES - STACKED ON LEFT -->
    <table class="signatures">
        <tr>
            <td>
                <div style="text-align: left; font-weight: bold; margin-bottom: 5px;">Monitored By:</div>
                <div class="sig-line"><strong>{{ $inspection->monitored_by ?? '_____________________' }}</strong></div>
               
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <div style="text-align: left; font-weight: bold; margin-bottom: 5px;">Contents Noted:</div>
                <div class="sig-line"><strong>{{ $inspection->coordinator_name ?? '_____________________' }}</strong></div>
                <div class="sig-title">School DRRM Coordinator</div>
                
                <div style="margin-top: 15px;"></div>
                <!-- Changed underscores to a non-breaking space -->
                <div class="sig-line"><strong>{{ $inspection->school_head_name ?? '    ' }}</strong></div>
                <div class="sig-title">School Head / Principal</div>
            </td>
        </tr>
    </table>
</body>
</html>
