<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ENT_personal_entities extends Model {
    use SoftDeletes;
    protected $table = 'ent_personal_entities';
    protected $fillable = [
        'entity_id','date_of_birth','gender','id_type','id_expiry',
        'nationality','marital_status','profession','driver_license_date','personal_notes','slug'
    ];
    protected $casts = ['date_of_birth' => 'date', 'id_expiry' => 'date', 'driver_license_date' => 'date'];
    public function entity() { return $this->belongsTo(ENT_entities::class, 'entity_id'); }
}

