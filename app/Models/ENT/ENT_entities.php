<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ENT_entities extends Model {
    use SoftDeletes;
    protected $table = 'ent_entities';
    protected $fillable = [
        'entity_type','name','nif','id_card','passport','temp_identifier',
        'identifier_status','kyc_status','kyc_approved_at','kyc_expires_at',
        'pep_status','us_person','status','active','slug','created_by','updated_by'
    ];
    protected $casts = [
        'us_person' => 'boolean',
        'active' => 'boolean',
        'kyc_approved_at' => 'datetime',
        'kyc_expires_at' => 'datetime',
    ];
    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->temp_identifier && !$model->nif && !$model->id_card && !$model->passport) {
                $model->temp_identifier = 'TMP-' . strtoupper(\Illuminate\Support\Str::uuid());
            }
        });
    }
    public function personalEntity() { return $this->hasOne(ENT_personal_entities::class, 'entity_id'); }
    public function corporateEntity() { return $this->hasOne(ENT_corporate_entities::class, 'entity_id'); }
    public function addresses() { return $this->hasMany(ENT_addresses::class, 'entity_id'); }
    public function contacts() { return $this->hasMany(ENT_contacts::class, 'entity_id'); }
    public function roles() { return $this->hasMany(ENT_entity_roles::class, 'entity_id'); }
    public function complianceDocuments() { return $this->hasMany(ENT_compliance_documents::class, 'entity_id'); }
    public function defaultAddress() { return $this->hasOne(ENT_addresses::class, 'entity_id')->where('is_default', true); }
    public function defaultContact() { return $this->hasOne(ENT_contacts::class, 'entity_id')->where('is_default', true); }
    public function agentBranchCommissions() {
        return $this->hasManyThrough(
            ENT_agent_branch_commissions::class,
            ENT_entity_roles::class,
            'entity_id', 'entity_role_id'
        );
    }
}
