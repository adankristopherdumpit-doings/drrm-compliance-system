<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FireSafetyEvacuationPlan extends Model
{
    protected $table = 'firesafety_evacuationplans';
    protected $fillable = ['school_id', 'plan_no', 'exits', 'routes', 'areas', 'status'];
}
