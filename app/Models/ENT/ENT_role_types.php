<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;

class ENT_role_types extends Model
{
    protected $table = 'ent_role_types';
    public $timestamps = true;
    protected $fillable = ['code', 'name', 'description', 'requires_nif', 'requires_kyc', 'active'];
    protected $casts = ['requires_nif' => 'boolean', 'requires_kyc' => 'boolean', 'active' => 'boolean'];
    public function subtypes()
    {
        return $this->hasMany(ENT_role_subtypes::class, 'role_type_id');
    }
    public function entityRoles()
    {
        return $this->hasMany(ENT_entity_roles::class, 'role_type_id');
    }
}
