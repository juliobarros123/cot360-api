<?php

namespace App\Models\CFG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_rates_rc_options extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_rates_rc_options';
    protected $fillable = ['rc_base', 'rc_fac', 'perc_rate', 'slug'];
    protected $casts = ['perc_rate' => 'decimal:6'];
    public static function boot()
    {
        parent::boot();
        static::creating(function ($rateRcOpt) {
            $rateRcOpt->slug = slug_gerar();
        });
    }
}
