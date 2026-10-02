<?php

namespace App\Models\QUO;

use App\Models\QUO\QUO_quotation_versions;
use Illuminate\Database\Eloquent\Model;

class QUO_premiums extends Model
{
    protected $table = 'quo_premiums';
    public $timestamps = true;
    protected $fillable = ['quotation_version_id', 'type', 'value', 'status'];
    protected $casts = ['value' => 'decimal:2', 'status' => 'boolean'];
    public function version()
    {
        return $this->belongsTo(QUO_quotation_versions::class, 'quotation_version_id');
    }
}
