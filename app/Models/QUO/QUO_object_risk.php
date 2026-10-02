<?php

namespace App\Models\QUO;

use App\Models\CFG\CFG_coverages;
use App\Models\OBJ\OBJ_objects;
use App\Models\QUO\QUO_quotation_versions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QUO_object_risk extends Model
{
    use SoftDeletes;
    protected $table = 'quo_object_risk';
    protected $fillable = [
        'quotation_version_id',
        'object_id',
        'object_risk_capital_id',
        'coverage_id',
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
        'manual_rate',
        'slug'
    ];
    protected $casts = [
        'dp_capital' => 'decimal:2',
        'rc_capital' => 'decimal:2',
        'building_capital' => 'decimal:2',
        'content_capital' => 'decimal:2',
        'exploration_capital' => 'decimal:2',
        'capital' => 'decimal:2',
        'coverage_perc' => 'double',
        'applied_rate' => 'double',
        'coverage_premium' => 'double',
        'discount_percent' => 'decimal:2',
        'surcharge_percent' => 'decimal:2',
    ];
    public function version()
    {
        return $this->belongsTo(QUO_quotation_versions::class, 'quotation_version_id');
    }
    public function object()
    {
        return $this->belongsTo(OBJ_objects::class, 'object_id');
    }
    public function coverage()
    {
        return $this->belongsTo(CFG_coverages::class, 'coverage_id');
    }
    public function deductibles()
    {
        return $this->hasMany(QUO_object_risk_deductibles::class, 'object_risk_id');
    }
    public function capital()
    {
        return $this->belongsTo(QUO_object_risk_capitals::class, 'object_risk_capital_id');
    }
    public function capitals()
    {
        return $this->capital();
    }
    public function adjustments()
    {
        return $this->hasMany(QUO_insurance_adjustments::class, 'object_risk_id');
    }
    public function objectRiskCapitals()
    {
        return $this->capital();
    }
}
