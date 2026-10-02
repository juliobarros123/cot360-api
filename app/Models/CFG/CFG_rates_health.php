<?php

namespace App\Models\CFG;

use App\Models\CFG\CFG_insurance_branches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_rates_health extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_rates_health';
    protected $fillable = [
        'insurance_branch_id',
        'coverage_id',
        'age_min',
        'age_max',
        'gender',
        'type',
        'package',
        'premium_value',
        'currency',
        'active',
        'slug'
    ];
    protected $casts = ['active' => 'boolean', 'premium_value' => 'decimal:2'];
    public function branch()
    {
        return $this->belongsTo(CFG_insurance_branches::class, 'insurance_branch_id');
    }
    public function coverage()
    {
        return $this->belongsTo(CFG_coverages::class, 'coverage_id');
    }
}
