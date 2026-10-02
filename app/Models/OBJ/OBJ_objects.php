<?php

namespace App\Models\OBJ;

use App\Models\ENT\ENT_entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\QUO\QUO_object_risk;
use App\Models\POL\POL_object_risk;
use App\Models\OBJ\OBJ_student_counts;
// use 

class OBJ_objects extends Model
{
    use SoftDeletes;
    protected $table = 'obj_objects';
    protected $fillable = ['entity_id', 'type', 'description', 'slug', 'created_by', 'updated_by'];
    public function owner()
    {
        return $this->belongsTo(ENT_entities::class, 'entity_id');
    }
    public function vehicle()
    {
        return $this->hasOne(OBJ_vehicles::class, 'object_id');
    }
    public function person()
    {
        return $this->hasOne(OBJ_persons::class, 'object_id');
    }
    public function property()
    {
        return $this->hasOne(OBJ_properties::class, 'object_id');
    }
    public function salaryAggregate()
    {
        return $this->hasOne(OBJ_salary_aggregates::class, 'object_id');
    }
    public function studentCount()
    {
        return $this->hasOne(OBJ_student_counts::class, 'object_id');
    }
    public function quotationRisks()
    {
        return $this->hasMany(QUO_object_risk::class, 'object_id');
    }
    public function policyRisks()
    {
        return $this->hasMany(POL_object_risk::class, 'object_id');
    }
    // Retorna o sub-modelo com base no type
    public function getDetailAttribute()
    {
        return match ($this->type) {
            'Vehicle' => $this->vehicle,
            'Property' => $this->property,
            'Person' => $this->person,
            'SalaryAggregate' => $this->salaryAggregate,
            default => null
        };
    }
    // Adicione este método na classe OBJ_objects
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
