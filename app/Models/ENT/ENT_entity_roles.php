<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ENT_entity_roles extends Model
{
    use SoftDeletes;
    protected $table = 'ent_entity_roles';
    protected $fillable = [
        'entity_id',
        'role_type_id',
        'role_subtype_id',
        'license_number',
        'license_expiry',
        'commission_default',
        'sales_channel_id',
        'active',
        'valid_from',
        'valid_until'
    ];
    protected $casts = ['active' => 'boolean', 'license_expiry' => 'date', 'valid_from' => 'date', 'valid_until' => 'date'];
    public function entity()
    {
        return $this->belongsTo(ENT_entities::class, 'entity_id');
    }
    public function roleType()
    {
        return $this->belongsTo(ENT_role_types::class, 'role_type_id');
    }
    public function roleSubtype()
    {
        return $this->belongsTo(ENT_role_subtypes::class, 'role_subtype_id');
    }
    public function salesChannel()
    {
        return $this->belongsTo(ENT_sales_channels::class, 'sales_channel_id');
    }
    public function ibans()
    {
        return $this->hasMany(ENT_entity_role_ibans::class, 'entity_role_id');
    }
    public function branchCommissions()
    {
        return $this->hasMany(ENT_agent_branch_commissions::class, 'entity_role_id');
    }
}
