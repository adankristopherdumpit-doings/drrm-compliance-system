<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'school_id_number',
        'school_name',
        'address',
        'school_head',
        'drrm_coordinator',
        'contact_number',
        'contact_number_2',
        'district',
        'division',
        'region',
        'evacuation_map_layout',
        'attached_evacuation_map',
        'fire_safety_status',
        'alerts',
        'events',
        'replies',
        'identification',
        'evacuation_identification',
        'evacuation_location',
        'evacuation_capacity',
        'number_students',
        'number_personnel',
        'number_gates',
        'engineer_last_inspection_date',
        'operational_status',
        'evacuation_status',
        'occupancy_safety',
        'emergency_resources',
        'emergency_resources_status',
        'needs_summary',
        'monitoring_status',
        'reports_status',
        'incident_count',
        'last_incident_date',
    ];

    protected $casts = [
        'evacuation_map_layout' => 'json',
        'alerts' => 'array',
        'events' => 'array',
        'replies' => 'array',
        'last_incident_date' => 'date',
        'evacuation_capacity' => 'integer',
        'number_students' => 'integer',
        'number_personnel' => 'integer',
        'number_gates' => 'integer',
        'engineer_last_inspection_date' => 'date',
        'incident_count' => 'integer',
    ];

    /**
     * Get module-specific information
     */
    public function specifics()
    {
        return $this->hasMany(SchoolSpecificsInformation::class);
    }

    /**
     * Fire Safety Module Relationships (canonical names)
     */
    public function fireSafetyBuildings()
    {
        return $this->hasMany(FireSafetyBuilding::class, 'unified_school_id');
    }

    public function fireSafetyAlarms()
    {
        return $this->hasMany(FireSafetyAlarmSystem::class, 'unified_school_id');
    }

    public function fireSafetyExtinguishers()
    {
        return $this->hasMany(FireSafetyExtinguisher::class, 'unified_school_id');
    }

    public function fireSafetyRooms()
    {
        return $this->hasMany(FireSafetyRoom::class, 'unified_school_id');
    }

    public function fireSafetyInspections()
    {
        return $this->hasMany(FireSafetyInspection::class, 'unified_school_id');
    }

    public function fireSafetyEvacuationPlans()
    {
        return $this->hasMany(FireSafetyEvacuationPlan::class, 'unified_school_id');
    }

    public function fireSafetyDrills()
    {
        return $this->hasMany(FireSafetyEvacuationDrill::class, 'unified_school_id');
    }

    /**
     * Backward-compatible alias relationships
     * (used by FireSafetyController eager loads)
     */
    public function buildings()
    {
        return $this->hasMany(FireSafetyBuilding::class, 'unified_school_id');
    }

    public function alarmSystems()
    {
        return $this->hasMany(FireSafetyAlarmSystem::class, 'unified_school_id');
    }

    public function extinguishers()
    {
        return $this->hasMany(FireSafetyExtinguisher::class, 'unified_school_id');
    }

    public function rooms()
    {
        return $this->hasMany(FireSafetyRoom::class, 'unified_school_id');
    }

    public function evacuationPlans()
    {
        return $this->hasMany(FireSafetyEvacuationPlan::class, 'unified_school_id');
    }

    public function schoolEvacuationPlan()
    {
        return $this->hasOne(FireSafetyEvacuationPlan::class, 'unified_school_id')
            ->whereNull('building_id')
            ->where('status', 'active');
    }

    public function inspections()
    {
        return $this->hasMany(FireSafetyInspection::class, 'unified_school_id');
    }

    public function drills()
    {
        return $this->hasMany(FireSafetyEvacuationDrill::class, 'unified_school_id');
    }

    /**
     * Helper attribute methods for evacuation plans
     */
    public function getBuildingsWithPlansCountAttribute(): int
    {
        if ($this->schoolEvacuationPlan) {
            return $this->buildings()->count();
        }

        return $this->buildings()->whereHas('evacuationPlan', function($query) {
            $query->where('status', 'active');
        })->count();
    }

    public function getTotalEmergencyExitsAttribute(): int
    {
        return $this->buildings()->sum('emergency_exits');
    }

    public function getTotalFunctionalAlarmsAttribute(): int
    {
        return $this->alarmSystems()->whereIn('status', ['active', 'functional', 'online'])->count();
    }

    public function getTotalActiveExtinguishersAttribute(): int
    {
        return $this->extinguishers()->where('status', 'active')->count();
    }

    public function getEvacuationCoveragePercentageAttribute(): float
    {
        if ($this->schoolEvacuationPlan) {
            return 100.0;
        }

        $totalBuildings = $this->buildings()->count();
        if ($totalBuildings === 0) return 0;

        return round(($this->buildingsWithPlansCount / $totalBuildings) * 100, 1);
    }

    public function getCoverageStatusAttribute(): string
    {
        $percentage = $this->evacuationCoveragePercentage;

        if ($percentage >= 80) return 'good';
        if ($percentage >= 50) return 'fair';
        return 'poor';
    }

    public function getCoverageStatusColorAttribute(): string
    {
        return match($this->coverageStatus) {
            'good' => 'success',
            'fair' => 'warning',
            'poor' => 'danger',
            default => 'secondary'
        };
    }

    public function getSchoolStatusLabelAttribute(): string
    {
        return match($this->fire_safety_status) {
            'passed' => 'PASSED',
            'unconfigured' => 'UNCONFIGURED',
            'warning', 'failed' => 'ONGOING IMPROVEMENT',
            default => 'UNKNOWN'
        };
    }

    public function getSchoolStatusColorAttribute(): string
    {
        return match($this->fire_safety_status) {
            'passed' => 'success',
            'unconfigured' => 'secondary',
            'warning', 'failed' => 'warning',
            default => 'info'
        };
    }

    public function getFormattedAddressAttribute(): string
    {
        return nl2br(e($this->address ?? ''));
    }

    /**
     * Get a specific module value
     */
    public function getModuleSpecific($module, $key)
    {
        return $this->specifics()
            ->where('module', $module)
            ->where('key', $key)
            ->value('value');
    }

    /* ── Comprehensive School Safety (cmpr_*) ─────────────────────────── */

    public function assessments()
    {
        return $this->hasMany(ComprehensiveAssessment::class, 'school_id');
    }

    public function facilities()
    {
        return $this->hasMany(ComprehensiveFacility::class, 'school_id');
    }

    public function students()
    {
        return $this->hasMany(ComprehensiveStudent::class, 'school_id');
    }

    public function summaryFindings()
    {
        return $this->hasMany(ComprehensiveSummaryFinding::class, 'school_id');
    }

    public function storageItems()
    {
        return $this->hasMany(ComprehensiveStorage::class, 'school_id');
    }

    public function damageReport()
    {
        return $this->hasMany(DamageReport::class, 'school_id');
    }

    /* ── Hazard Mapping (hzd_map_*) ─────────────────────────────────────── */

    public function hazardMappings()
    {
        return $this->hasMany(HazardMapping::class, 'school_id');
    }

    /* ── Presentation aliases for legacy views / APIs ───────────────────── */

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['school_name'] ?? '');
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['fire_safety_status'] ?? null;
    }

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['fire_safety_status'] = $value;
    }

    public function getSchoolDrrmCoordinatorAttribute(): ?string
    {
        return $this->drrm_coordinator;
    }

    public function setSchoolDrrmCoordinatorAttribute(?string $value): void
    {
        $this->attributes['drrm_coordinator'] = $value;
    }

    /**
     * Typhoon module: mirrors old TypFldEvacuationCenter field names in Blade/API.
     */
    public function getUsageStatusAttribute(): ?string
    {
        return $this->attributes['evacuation_status'] ?? null;
    }

    public function setUsageStatusAttribute(?string $value): void
    {
        $this->attributes['evacuation_status'] = $value;
    }

    public function getLocationAttribute(): ?string
    {
        if (! empty($this->attributes['evacuation_location'])) {
            return $this->attributes['evacuation_location'];
        }

        return $this->attributes['address'] ?? null;
    }

    public function setLocationAttribute(?string $value): void
    {
        $this->attributes['evacuation_location'] = $value;
    }

    public function getCapacityAttribute(): int
    {
        return (int) ($this->attributes['evacuation_capacity'] ?? 0);
    }

    public function setCapacityAttribute($value): void
    {
        $this->attributes['evacuation_capacity'] = (int) $value;
    }

    public function getSchoolAttribute(): self
    {
        return $this;
    }

    public function getEmergencyResourcesUsageStatusAttribute(): ?string
    {
        return $this->attributes['emergency_resources_status'] ?? null;
    }

    public function typFldFamilies()
    {
        return $this->hasMany(TypFldFamily::class, 'school_id');
    }
}
