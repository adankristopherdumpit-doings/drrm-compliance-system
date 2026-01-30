<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyExtinguisher extends Model
{
    protected $table = 'firesafety_fire_extinguishers';

    protected $fillable = [
        'school_id',
        'building_id',
        'room_id', // center room
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

    public function centerRoom()
    {
        return $this->belongsTo(FireSafetyRoom::class, 'room_id');
    }

    public function coveredRooms()
    {
        return $this->belongsToMany(
            FireSafetyRoom::class,
            'fire_safety_extinguisher_room_coverage',
            'extinguisher_id',
            'room_id'
        )->withTimestamps();
    }
}
