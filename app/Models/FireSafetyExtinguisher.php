<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyExtinguisher extends Model
{
    protected $table = 'firesafety_fire_extinguishers';

    protected $fillable = [
        'school_id',
        'building_id',  // Add this
        'code',
        'status',
        'date_checked',
        'evaluation_result'
    ];

    // Add relationship methods
    public function building()
    {
        return $this->belongsTo(FireSafetyBuilding::class, 'building_id');
    }

    public function school()
    {
        return $this->belongsTo(FireSafetySchool::class, 'school_id');
    }
}
