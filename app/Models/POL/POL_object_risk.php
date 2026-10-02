<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;
use App\Models\OBJ\OBJ_objects;
class POL_object_risk extends Model
{
    protected $table = 'pol_object_risk';
    public $timestamps = false;
    protected $fillable = [
        'policy_version_id',
        'object_id',
        'coverage_id',
        'source_quo_object_risk_id',
        'dp_capital',
        'rc_capital',
        'building_capital',
        'content_capital',
        'exploration_capital',
        'capital',
        'coverage_perc',
        'applied_rate',
        'coverage_premium',
        'rate_origin',
        'rate_id',
        'discount_percent',
        'surcharge_percent',
        'status',
        'effective_date',
        'end_date',
        'slug',
        'created_at'
    ];
    protected $casts = [
        'dp_capital' => 'decimal:2',
        'rc_capital' => 'decimal:2',
        'building_capital' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];
    public function policyVersion()
    {
        return $this->belongsTo(POL_policy_versions::class, 'policy_version_id');
    }
    public function object()
    {
        return $this->belongsTo(OBJ_objects::class, 'object_id');
    }
    public function coverage()
    {
        return $this->belongsTo(\App\Models\CFG\CFG_coverages::class, 'coverage_id');
    }
    public function sourceQuoObjectRisk()
    {
        return $this->belongsTo(\App\Models\QUO\QUO_object_risk::class, 'source_quo_object_risk_id');
    }
    public function capitals()
    {
        return $this->hasMany(POL_object_risk_capitals::class, 'object_risk_id');
    }
    public function movementDetails()
    {
        return $this->hasMany(POL_movement_details::class, 'object_risk_id');
    }
}
