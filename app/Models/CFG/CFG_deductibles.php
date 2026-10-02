<?php

namespace App\Models\CFG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_deductibles extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_deductibles';
    protected $fillable = [
        'coverage_id',
        'deduct_type',
        'deduct_object',
        'deduct_percent',
        'deduct_value',
        'deduct_min',
        'deduct_max',
        'discount',
        'details',
        'slug'
    ];
    protected $casts = ['deduct_percent' => 'float', 'deduct_value' => 'float'];
    public function coverage()
    {
        return $this->belongsTo(CFG_coverages::class, 'coverage_id');
    }
}
