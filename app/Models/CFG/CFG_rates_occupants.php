<?php

namespace App\Models\CFG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_rates_occupants extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_rates_occupants';
    protected $fillable = ['seat_min', 'seat_max', 'package', 'mip', 'dt', 'df', 'premium', 'slug'];
    protected $casts = ['mip' => 'double', 'dt' => 'double', 'df' => 'double', 'premium' => 'double'];
}
