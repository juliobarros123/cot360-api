<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;

class ENT_role_subtypes extends Model
{
    protected $table = 'ent_role_subtypes';
    protected $fillable = ['role_type_id', 'code', 'name', 'description', 'active'];
    protected $casts = ['active' => 'boolean'];
    public function roleType()
    {
        return $this->belongsTo(ENT_role_types::class, 'role_type_id');
    }
    public function entityRoles()
    {
        return $this->hasMany(ENT_entity_roles::class, 'role_subtype_id');
    }
}
