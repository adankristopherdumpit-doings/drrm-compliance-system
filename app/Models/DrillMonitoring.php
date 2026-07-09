<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrillMonitoring extends Model
{
    protected $table = 'fire_safety_inspections';

    protected $fillable = [
        'unified_school_id',
        'drill_type',
        'inspection_date',
        'inspection_time',
        'time_started',
        'time_finished',
        'elapsed_time',
        'no_of_exits',
        'no_of_buildings',
        'no_of_students',
        'no_of_personnel',
        'monitored_by',
        'monitored_by_position',
        'checklist_data',
        'observers_data',
        'remarks',
        'coordinator_name',
        'school_head_name'
    ];

    protected $casts = [
        'checklist_data' => 'array',
        'observers_data' => 'array',
        'inspection_date' => 'date',
    ];

    /**
     * Get the school associated with this monitoring record.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'unified_school_id');
    }

    /**
     * Get the scheduled drill being monitored, if applicable.
     */
    public function scheduledDrill(): BelongsTo
    {
        return $this->belongsTo(FireSafetyEvacuationDrill::class, 'drill_id');
    }
}