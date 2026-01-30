<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyRoom extends Model
{
    protected $table = 'fire_safety_rooms';

    protected $fillable = [
        'school_id',
        'building_id',
        'room_code',
        'room_name',
        'room_type',
        'floor_no',
    ];

    public function school()
    {
        return $this->belongsTo(FireSafetySchool::class, 'school_id');
    }

    public function building()
    {
        return $this->belongsTo(FireSafetyBuilding::class, 'building_id');
    }

    public function extinguishersCoveringThisRoom()
    {
        return $this->belongsToMany(
            FireSafetyExtinguisher::class,
            'fire_safety_extinguisher_room_coverage',
            'room_id',
            'extinguisher_id'
        )->withTimestamps();
    }
}

