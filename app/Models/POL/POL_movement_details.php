<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;

class POL_movement_details extends Model
{
    protected $table = 'pol_movement_details';
    public $timestamps = false;
    protected $fillable = [
        'movement_id',
        'change_type',
        'object_risk_id',
        'coverage_id',
        'field_name',
        'old_value',
        'new_value',
        'premium_impact',
        'created_at'
    ];
    protected $casts = ['premium_impact' => 'decimal:2'];
    public function movement()
    {
        return $this->belongsTo(POL_movements::class, 'movement_id');
    }
    public function objectRisk()
    {
        return $this->belongsTo(POL_object_risk::class, 'object_risk_id');
    }
}
