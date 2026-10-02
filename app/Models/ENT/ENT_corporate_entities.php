<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ENT_corporate_entities extends Model
{
    use SoftDeletes;
    protected $table = 'ent_corporate_entities';
    protected $fillable = [
        'entity_id',
        'legal_name',
        'trade_name',
        'registration_number',
        'registration_date',
        'cae_code',
        'industry_type',
        'tax_classification',
        'slug'
    ];
    protected $casts = ['registration_date' => 'date'];
    public function entity()
    {
        return $this->belongsTo(ENT_entities::class, 'entity_id');
    }
}
