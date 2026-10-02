<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ENT_contacts extends Model
{
    use SoftDeletes;
    protected $table = 'ent_contacts';
    protected $fillable = [
        'entity_id',
        'contact_type',
        'name',
        'role',
        'email',
        'phone',
        'mobile',
        'is_default',
        'slug'
    ];
    protected $casts = ['is_default' => 'boolean'];
    public function entity()
    {
        return $this->belongsTo(ENT_entities::class, 'entity_id');
    }
}
