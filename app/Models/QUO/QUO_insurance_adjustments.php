<?php

namespace App\Models\QUO;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QUO_insurance_adjustments extends Model
{
    use SoftDeletes;
    protected $table = 'quo_insurance_adjustments';
    protected $fillable = ['object_risk_id', 'type', 'value_type', 'value', 'rate', 'reason', 'notes', 'rate_id'];
    protected $casts = ['value' => 'decimal:2'];
    public function objectRisk()
    {
        return $this->belongsTo(QUO_object_risk::class, 'object_risk_id');
    }
}
