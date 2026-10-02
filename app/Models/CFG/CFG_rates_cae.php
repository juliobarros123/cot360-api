<?php

namespace App\Models\CFG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_rates_cae extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_rates_cae';
    protected $fillable = [
        'cae',
        'description',
        'rate_mre_building',
        'rate_mre_content',
        'work_accident',
        'active',
        'slug'
    ];
    protected $casts = ['active' => 'boolean'];
}
