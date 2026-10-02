<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ENT_entity_role_ibans extends Model
{
    use SoftDeletes;
    protected $table = 'ent_entity_role_ibans';
    protected $fillable = ['entity_role_id', 'iban', 'bank_name', 'swift', 'currency', 'is_default'];
    protected $casts = ['is_default' => 'boolean'];
    public function entityRole()
    {
        return $this->belongsTo(ENT_entity_roles::class, 'entity_role_id');
    }
}
