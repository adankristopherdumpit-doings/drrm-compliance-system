<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageReport extends Model
{
    protected $table = 'damage_reports';

    // PLACEHOLDER:replace and fix the tables(the columns)
    protected $fillable = [
        'school_id',
        'status',
        'remarks'
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
        return $this->belongsTo(FireSafetyEvacuationDrill::class, '');
        return $this->belongsTo(FireSafetyEvacuationDrill::class, 'drill_id');
    }
}