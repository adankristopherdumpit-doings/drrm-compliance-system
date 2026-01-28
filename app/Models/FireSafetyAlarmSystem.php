<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyAlarmSystem extends Model
{
    protected $table = 'firesafety_alarm_systems';
    protected $fillable = ['school_id', 'code', 'location', 'alarm_type', 'status', 'last_test'];
}
