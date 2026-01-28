<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FireSafetySchool extends Model
{
    protected $table = 'firesafety_school_information';
    protected $fillable = ['school_name', 'school_id', 'school_head', 'school_drrm_coordinator', 'status'];

    // Relationships
    public function extinguishers(): HasMany
    {
        return $this->hasMany(FireSafetyExtinguisher::class, 'school_id');
    }

    public function alarmSystems(): HasMany
    {
        return $this->hasMany(FireSafetyAlarmSystem::class, 'school_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(FireSafetyBuilding::class, 'school_id');
    }

    public function evacuationPlans(): HasMany
    {
        return $this->hasMany(FireSafetyEvacuationPlan::class, 'school_id');
    }
}
