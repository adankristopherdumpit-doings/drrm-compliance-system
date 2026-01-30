<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FireSafetyBuilding extends Model
{
    protected $table = 'firesafety_buildings';

    protected $fillable = [
        'school_id',
        'building_no',
        'building_name',
        'floors',
        'rooms',
        'capacity',
        'year_constructed',
        'last_renovation',
        'emergency_exits',
        'building_type',
        'description',
        'features'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(FireSafetySchool::class, 'school_id');
    }

    public function alarmSystems(): HasMany
    {
        return $this->hasMany(FireSafetyAlarmSystem::class, 'building_id');
    }

    public function fireExtinguishers(): HasMany
    {
        // You'll need to create this relationship when you make the extinguisher model
        return $this->hasMany(FireSafetyExtinguisher::class, 'building_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(FireSafetyRoom::class, 'building_id');
    }
}
