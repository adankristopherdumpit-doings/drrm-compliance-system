<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyEvacuationDrill extends Model
{
    protected $fillable = [
        'unified_school_id',
        'drill_type',
        'drill_date',
        'start_time',
        'end_time',
        'participants_count',
        'evacuation_time_minutes',
        'status',
        'remarks',
        'coordinator',
        'notes',
    ];

    public function buildings()
    {
        return $this->belongsToMany(FireSafetyBuilding::class, 'fire_safety_drill_building', 'drill_id', 'building_id');
    }

    /**
     * Get the monitoring records associated with this scheduled drill.
     */
    public function monitorings()
    {
        return $this->hasMany(DrillMonitoring::class, 'drill_id');
    }
}
