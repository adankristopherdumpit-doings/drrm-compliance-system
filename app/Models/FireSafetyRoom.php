<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FireSafetyRoom extends Model
{
    protected $table = 'fire_safety_rooms';

    protected $fillable = [
        'unified_school_id',
        'building_id',
        'room_code',
        'room_name',
        'room_type',
        'room_type_config_id',
        'calculated_priority_label',
        'coverage_limit',
        'floor_no',
        'nearest_extinguisher_room_id',
        'engineer_last_inspection_date',
        'remarks',
        'has_smoke_detector',
        'smoke_detector_required',
        'has_secondary_exit',
        'is_evacuation_room',
        'Main_evac',
        'Buffer_evac',
        'secondary_exit_remarks',
        'evacuation_room_remarks',
        'last_inspector_id',
        'approval_status',
        'approval_message'
    ];

    protected $casts = [
        'floor_no' => 'integer',
        'engineer_last_inspection_date' => 'date'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'unified_school_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(FireSafetyBuilding::class, 'building_id');
    }

    public function nearestExtinguisherRoom(): BelongsTo
    {
        return $this->belongsTo(FireSafetyRoom::class, 'nearest_extinguisher_room_id');
    }

    public function roomTypeConfig(): BelongsTo
    {
        return $this->belongsTo(SystemConfiguration::class, 'room_type_config_id');
    }

    public function extinguishersCoveringThisRoom(): BelongsToMany
    {
        return $this->belongsToMany(
            FireSafetyExtinguisher::class,
            'fire_safety_extinguisher_room_coverage',
            'room_id',
            'extinguisher_id'
        )->withTimestamps();
    }

    public function hostedExtinguisher(): HasOne
    {
        return $this->hasOne(FireSafetyExtinguisher::class, 'room_id');
    }

    public function lastInspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_inspector_id');
    }
    
    // Helper methods
    public function getRoomTypeColorAttribute(): string
    {
        return match($this->room_type) {
            'laboratory' => 'danger',
            'clinic' => 'info',
            'classroom' => 'primary',
            'administration' => 'success',
            'storage' => 'warning',
            default => 'secondary'
        };
    }
    
    public function getRoomTypeLabelAttribute(): string
    {
        return ucfirst($this->room_type);
    }
    
    public function getFloorLabelAttribute(): string
    {
        if (!$this->floor_no) return 'N/A';
        
        $ordinals = ['th', 'st', 'nd', 'rd', 'th'];
        $v = $this->floor_no % 100;
        return $this->floor_no . ($ordinals[($v - 20) % 10] ?? $ordinals[$v] ?? $ordinals[0]) . ' Floor';
    }
    
    public function isCovered(): bool
    {
        return $this->extinguishersCoveringThisRoom()->exists();
    }
    
    public function getCoveringExtinguisherAttribute(): ?FireSafetyExtinguisher
    {
        return $this->extinguishersCoveringThisRoom()->first();
    }
    
    public function getCoverageInfoAttribute(): array
    {
        $extinguisher = $this->coveringExtinguisher;
        
        if (!$extinguisher) {
            return [
                'is_covered' => false,
                'extinguisher_code' => null,
                'is_center_room' => false
            ];
        }
        
        return [
            'is_covered' => true,
            'extinguisher_code' => $extinguisher->code,
            'is_center_room' => $extinguisher->room_id === $this->id
        ];
    }
}