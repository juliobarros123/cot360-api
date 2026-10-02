<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;

class ENT_agent_branch_commissions extends Model
{
    protected $table = 'ent_agent_branch_commissions';
    public $timestamps = true;
    protected $fillable = ['entity_role_id', 'insurance_branch_id', 'commission_rate', 'valid_from', 'valid_until'];
    protected $casts = ['valid_from' => 'date', 'valid_until' => 'date', 'commission_rate' => 'decimal:2'];
    public function entityRole()
    {
        return $this->belongsTo(ENT_entity_roles::class, 'entity_role_id');
    }
    public function branch()
    {
        return $this->belongsTo(\App\Models\CFG\CFG_insurance_branches::class, 'insurance_branch_id');
    }
}
