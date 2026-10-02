<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;

class POL_reinsurance_cessions extends Model {
    protected $table = 'pol_reinsurance_cessions';
    public $timestamps = false;
    protected $fillable = [
        'policy_version_id','object_risk_id','reinsurer_id','treaty_reference','treaty_type',
        'ceded_percent','ceded_capital','ceded_premium','retention_percent','retention_capital','created_at'
    ];
    public function policyVersion() { return $this->belongsTo(POL_policy_versions::class, 'policy_version_id'); }
    public function objectRisk() { return $this->belongsTo(POL_object_risk::class, 'object_risk_id'); }
    public function reinsurer() { return $this->belongsTo(\App\Models\ENT\ENT_entities::class, 'reinsurer_id'); }
}