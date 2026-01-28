<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyExtinguisher extends Model
{
    protected $table = 'firesafety_fire_extinguishers';
    protected $fillable = ['school_id', 'code', 'status', 'date_checked', 'evaluation_result'];
}
