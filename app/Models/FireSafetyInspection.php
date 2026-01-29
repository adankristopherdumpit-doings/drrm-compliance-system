<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyInspection extends Model
{
    protected $table = 'fire_safety_inspections';

    protected $fillable = [
        'school_id',
        'building_id',
        'inspection_date',
        'inspection_type',
        'inspector',
        'notes',
        'status'
    ];

    public function school()
    {
        return $this->belongsTo(FireSafetySchool::class, 'school_id');
    }

    public function building()
    {
        return $this->belongsTo(FireSafetyBuilding::class, 'building_id');
    }
}
