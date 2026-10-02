<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;

class POL_coinsurance_shares extends Model
{
    protected $table = 'pol_coinsurance_shares';
    public $timestamps = false;
    protected $fillable = [
        'policy_version_id',
        'insurer_id',
        'is_leader',
        'share_percent',
        'share_premium',
        'share_premium_usd',
        'external_reference',
        'created_at'
    ];
    protected $casts = ['is_leader' => 'boolean', 'share_percent' => 'decimal:2', 'share_premium' => 'decimal:2'];
    public function policyVersion()
    {
        return $this->belongsTo(POL_policy_versions::class, 'policy_version_id');
    }
    public function insurer()
    {
        return $this->belongsTo(\App\Models\ENT\ENT_entities::class, 'insurer_id');
    }
}
