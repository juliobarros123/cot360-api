<?php

namespace App\Models\QUO;

use Illuminate\Database\Eloquent\Model;

class QUO_quotation_discount_controls extends Model
{
    protected $table = 'quo_quotation_discount_controls';

    protected $fillable = [
        'quotation_id',
        'quotation_version_id',
        'version_code',
        'table_code',
        'discount_percent',
        'status',
        'applied_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:4',
        'applied_at' => 'datetime',
    ];
}

