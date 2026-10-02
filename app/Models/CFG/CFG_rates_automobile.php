<?php

namespace App\Models\CFG;

use App\Models\CFG\CFG_insurance_branches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_rates_automobile extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_rates_automobile';
    protected $fillable = [
        'insurance_branch_id',
        'code',
        'description',
        'vehicle_type',
        'category',
        'engine_min',
        'engine_max',
        'seat_min',
        'seat_max',
        'weight_min',
        'weight_max',
        'min_rc_legal_ucf',
        'min_rc_limit_kz',
        'rc_premium_kz',
        'ccc_percent',
        'ire_percent',
        'fr_percent',
        'av_percent',
        'fn_percent',
        'gtaop_percent',
        'active',
        'slug'
    ];
    protected $casts = ['active' => 'boolean'];
    public function branch()
    {
        return $this->belongsTo(CFG_insurance_branches::class, 'insurance_branch_id');
    }
}
