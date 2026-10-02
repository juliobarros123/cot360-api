<?php

namespace App\Models\QUO;

use App\Models\OBJ\OBJ_objects;
use Illuminate\Database\Eloquent\Model;

class QUO_object_risk_capitals extends Model
{
    protected $table = 'quo_object_risk_capitals';
    public $timestamps = true;
    protected $fillable = ['obj_object_id', 'capital_type', 'capital'];
    protected $casts = ['capital' => 'decimal:2'];
    public function object()
    {
        return $this->belongsTo(OBJ_objects::class, 'obj_object_id');
    }
    public function risks()
    {
        return $this->hasMany(QUO_object_risk::class, 'object_risk_capital_id');
    }
}
