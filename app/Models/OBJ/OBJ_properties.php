<?php

namespace App\Models\OBJ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OBJ_properties extends Model
{
    use SoftDeletes;
    protected $table = 'obj_properties';
    protected $fillable = [
        'object_id',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'property_type',
        'plan_name',
        'risk_class',
        'insured_role',
        'residence_type',
        'total_area',
        'number_of_floors',
        'construction_year',
        'fire_protection',
        'security_system',
        'theft_protection',
        'building_capital',
        'content_capital',
        'exploration_capital',
        'slug'
    ];
    protected $casts = [
        'fire_protection' => 'boolean',
        'security_system' => 'boolean',
        'theft_protection' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'building_capital' => 'decimal:2',
        'content_capital' => 'decimal:2',
        'exploration_capital' => 'decimal:2',
    ];
    public function object()
    {
        return $this->belongsTo(OBJ_objects::class, 'object_id');
    }
}
