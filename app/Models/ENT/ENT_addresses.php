<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ENT_addresses extends Model
{
    use SoftDeletes;

    protected $table = 'ent_addresses';
    protected $fillable = [
        'entity_id',
        'address_type',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'is_default'
    ];
    protected $casts = ['is_default' => 'boolean'];
    public function entity()
    {
        return $this->belongsTo(ENT_entities::class, 'entity_id');
    }
}
