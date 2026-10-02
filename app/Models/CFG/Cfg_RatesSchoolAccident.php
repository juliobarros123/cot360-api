<?php

namespace App\Models\CFG;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cfg_RatesSchoolAccident extends Model
{
    use SoftDeletes;

    protected $table = 'cfg_rates_school_accident';

    protected $fillable = [
        'insurance_branch_id',
        'coverage_id',
        'type',
        'package',
        'premium_value',
        'active',
        'slug',
    ];

    protected $casts = [
        'premium_value' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function insuranceBranch()
    {
        return $this->belongsTo(\App\Models\CFG\CFG_insurance_branches::class, 'insurance_branch_id');
    }

    public function coverage()
    {
        return $this->belongsTo(\App\Models\CFG\CFG_coverages::class, 'coverage_id');
    }
}