<?php

namespace App\Models\POL;

use App\Models\ENT\ENT_entities;
use App\Models\ENT\QUO_quotation_versions;
use App\Models\QUO\QUO_quotations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class POL_policies extends Model {
    use SoftDeletes;
    protected $table = 'pol_policies';
    protected $fillable = [
        'policy_number','quotation_id','quotation_version_id','insurance_branch_id',
        'customer_id','agent_id','previous_policy_id','renewal_count','original_policy_id',
        'inception_date','current_expiry_date','status','slug','created_by','updated_by'
    ];
    protected $casts = [
        'inception_date' => 'date', 'current_expiry_date' => 'date',
        'renewal_count' => 'integer',
    ];
    public function quotation() { return $this->belongsTo(QUO_quotations::class, 'quotation_id'); }
    public function quotationVersion() { return $this->belongsTo(QUO_quotation_versions::class, 'quotation_version_id'); }
    public function branch() { return $this->belongsTo(\App\Models\CFG\CFG_insurance_branches::class, 'insurance_branch_id'); }
    public function customer() { return $this->belongsTo(ENT_entities::class, 'customer_id'); }
    public function agent() { return $this->belongsTo(ENT_entities::class, 'agent_id'); }
    public function previousPolicy() { return $this->belongsTo(POL_policies::class, 'previous_policy_id'); }
    public function originalPolicy() { return $this->belongsTo(POL_policies::class, 'original_policy_id'); }
    public function versions() { return $this->hasMany(POL_policy_versions::class, 'policy_id'); }
    public function activeVersion() {
        return $this->hasOne(POL_policy_versions::class, 'policy_id')->where('status', 'Active')->latestOfMany('version');
    }
    public function movements() { return $this->hasMany(POL_movements::class, 'policy_id'); }
}
