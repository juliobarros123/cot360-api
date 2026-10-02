<?php

namespace App\Models\OBJ;

use Illuminate\Database\Eloquent\Model;
use App\Models\ENT\ENT_entities;
use Illuminate\Database\Eloquent\SoftDeletes;

class OBJ_persons extends Model
{
    use SoftDeletes;
    protected $table = 'obj_persons';
    protected $fillable = [
        'object_id',
        'entity_id',
        'name',
        'gender',
        'birthdate',
        'age',
        'dependent',
        'relationship',
        'job_category',
        'salary',
        'slug'
    ];
    protected $casts = ['birthdate' => 'date', 'dependent' => 'boolean', 'salary' => 'decimal:2'];
    public function object()
    {
        return $this->belongsTo(OBJ_objects::class, 'object_id');
    }
    public function entity()
    {
        return $this->belongsTo(ENT_entities::class, 'entity_id');
    }
}
