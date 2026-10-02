<?php

namespace App\Models\POL;

use Illuminate\Database\Eloquent\Model;

class POL_special_conditions extends Model
{
    protected $table = 'pol_special_conditions';
    public $timestamps = false;
    protected $fillable = [
        'policy_version_id',
        'special_condition_id',
        'condition_type',
        'short_text',
        'long_text',
        'display_order',
        'created_at'
    ];
    public function policyVersion()
    {
        return $this->belongsTo(POL_policy_versions::class, 'policy_version_id');
    }
    public function condition()
    {
        return $this->belongsTo(\App\Models\CFG\CFG_special_conditions::class, 'special_condition_id');
    }
}
