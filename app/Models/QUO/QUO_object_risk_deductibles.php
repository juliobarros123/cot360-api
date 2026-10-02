<?php

namespace App\Models\QUO;

use App\Models\CFG\CFG_deductibles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QUO_object_risk_deductibles extends Model {
    use SoftDeletes;
    protected $table = 'quo_object_risk_deductibles';
    protected $fillable = ['object_risk_id','deductible_id','applied_value','applied_percent','slug'];
    protected $casts = ['applied_value' => 'decimal:2', 'applied_percent' => 'decimal:2'];
    public function objectRisk() { return $this->belongsTo(QUO_object_risk::class, 'object_risk_id'); }
    public function deductible() {
        return $this->belongsTo(CFG_deductibles::class, 'deductible_id');
    }
}