<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;

class POL_movements extends Model {
    protected $table = 'pol_movements';
    public $timestamps = false;
    protected $fillable = [
        'policy_id','from_version_id','to_version_id','movement_type','movement_subtype',
        'movement_date','effective_date','premium_difference','premium_difference_usd',
        'prorata_days','prorata_factor','reason','notes','status',
        'approved_by','approved_at','created_by','created_at'
    ];
    protected $casts = ['movement_date' => 'date', 'effective_date' => 'date', 'approved_at' => 'datetime'];
    public function policy() { return $this->belongsTo(POL_policies::class, 'policy_id'); }
    public function fromVersion() { return $this->belongsTo(POL_policy_versions::class, 'from_version_id'); }
    public function toVersion() { return $this->belongsTo(POL_policy_versions::class, 'to_version_id'); }
    public function details() { return $this->hasMany(POL_movement_details::class, 'movement_id'); }
}