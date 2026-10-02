<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;

class POL_object_risk_capitals extends Model
{
    protected $table = 'pol_object_risk_capitals';
    public $timestamps = true;
    protected $fillable = ['object_risk_id', 'capital_type', 'capital'];
    protected $casts = ['capital' => 'decimal:2'];
    public function objectRisk()
    {
        return $this->belongsTo(POL_object_risk::class, 'object_risk_id');
    }
}
