<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;

class POL_policy_versions extends Model
{
    protected $table = 'pol_policy_versions';
    public $timestamps = false;
    protected $fillable = [
        'policy_id',
        'version',
        'effective_date',
        'expiry_date',
        'commercial_agent_role_id',
        'sales_channel_id',
        'cae_rate_id',
        'net_premium',
        'total_taxes',
        'gross_premium',
        'currency',
        'exchange_rate',
        'gross_premium_usd',
        'commission_rate',
        'commission_value',
        'discount_percent',
        'status',
        'snapshot_json',
        'created_by',
        'created_at'
    ];
    protected $casts = [
        'snapshot_json' => 'array',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'net_premium' => 'decimal:2',
        'total_taxes' => 'decimal:2',
        'gross_premium' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];
    public function policy()
    {
        return $this->belongsTo(POL_policies::class, 'policy_id');
    }
    public function objectRisks()
    {
        return $this->hasMany(POL_object_risk::class, 'policy_version_id');
    }
    public function specialConditions()
    {
        return $this->hasMany(POL_special_conditions::class, 'policy_version_id');
    }
    public function coinsuranceShares()
    {
        return $this->hasMany(POL_coinsurance_shares::class, 'policy_version_id');
    }
    public function reinsuranceCessions()
    {
        return $this->hasMany(POL_reinsurance_cessions::class, 'policy_version_id');
    }
    public function movementsTo()
    {
        return $this->hasMany(POL_movements::class, 'to_version_id');
    }
}
