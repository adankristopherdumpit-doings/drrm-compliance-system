<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyAlarmSystem extends Model
{
    protected $table = 'firesafety_alarm_systems';

    protected $fillable = [
        'school_id',
        'building_id',
        'code',
        'location', // ← Make sure this is here
        'alarm_type',
        'status',
        'last_test',
        'next_test_due',
        'manufacturer',
        'installation_date',
        'notes'
    ];

    public function building()
    {
        return $this->belongsTo(FireSafetyBuilding::class, 'building_id');
    }
    public function school()
    {
        return $this->belongsTo(FireSafetySchool::class, 'school_id');
    }
}
