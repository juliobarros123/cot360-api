<?php

namespace App\Models\CFG;

use App\Models\CFG\CFG_insurance_branches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_coverages extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_coverages';
    protected $fillable = [
        'insurance_branch_id',
        'group_id',
        'seq',
        'gis_cod',
        'type_cov',
        'short_name',
        'name',
        'description',
        'obj_type',
        'capital_value',
        'capital_origin',
        'rate_type',
        'risk_rate',
        'risk_rate_origin',
        'cov_opt',
        'type_covt',
        'cov_perc',
        'active',
        'slug'
    ];
    protected $casts = ['active' => 'boolean', 'capital_value' => 'double', 'risk_rate' => 'double', 'cov_perc' => 'decimal:2'];
    public function branch()
    {
        return $this->belongsTo(CFG_insurance_branches::class, 'insurance_branch_id');
    }
    public function parent()
    {
        return $this->belongsTo(CFG_coverages::class, 'group_id');
    }
    public function children()
    {
        return $this->hasMany(CFG_coverages::class, 'group_id');
    }
    public function deductibles()
    {
        return $this->hasMany(CFG_deductibles::class, 'coverage_id');
    }
    public function ratesHealth()
    {
        return $this->hasMany(CFG_rates_health::class, 'coverage_id');
    }
    public function templates()
    {
        return $this->belongsToMany(CFG_templates::class, 'cfg_template_coverage', 'coverage_id', 'template_id')
            ->withPivot('is_mandatory', 'display_order')->withTimestamps();
    }
}
