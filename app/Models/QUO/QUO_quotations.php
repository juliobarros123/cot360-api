<?php

namespace App\Models\QUO;

use App\Models\ENT\ENT_entities;

use App\Models\POL\POL_policies;
use App\Models\QUO\QUO_quotation_versions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use App\Models\QUO\QUO_quotation_versions;

class QUO_quotations extends Model {
    use SoftDeletes;
    protected $table = 'quo_quotations';
    protected $fillable = [
        'quotation_number','customer_id','insurance_branch_id','agent_id','template_code',
        'opportunity_id','lead_source','status','slug','created_by','updated_by'
    ];
    public function customer() {
        // dd("oma");
        return $this->belongsTo(ENT_entities::class, 'customer_id');
    }
    public function agent() {
        return $this->belongsTo(ENT_entities::class, 'agent_id');
    }
    public function branch() {
        return $this->belongsTo(\App\Models\CFG\CFG_insurance_branches::class, 'insurance_branch_id');
    }
    public function versions() { return $this->hasMany(QUO_quotation_versions::class, 'quotation_id'); }
    public function latestVersion() {
        return $this->hasOne(QUO_quotation_versions::class, 'quotation_id')->latestOfMany();
    }
    public function policy() {
        return $this->hasOne(POL_policies::class, 'quotation_id');
    }
}
