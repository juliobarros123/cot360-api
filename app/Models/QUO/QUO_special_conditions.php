<?php

namespace App\Models\QUO;

use App\Models\CFG\CFG_special_conditions;
use App\Models\QUO\QUO_quotation_versions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QUO_special_conditions extends Model
{
    use SoftDeletes;
    protected $table = 'quo_special_conditions';
    protected $fillable = ['quotation_version_id', 'special_condition_id', 'custom_text', 'display_order'];
    protected $casts = ['display_order' => 'integer'];
    public function version()
    {
        return $this->belongsTo(QUO_quotation_versions::class, 'quotation_version_id');
    }
    public function condition()
    {
        return $this->belongsTo(CFG_special_conditions::class, 'special_condition_id');
    }
}
