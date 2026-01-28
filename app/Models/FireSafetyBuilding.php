<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyBuilding extends Model
{
    protected $table = 'firesafety_buildings';
    protected $fillable = ['school_id', 'building_no', 'floors', 'rooms', 'capacity'];
}
