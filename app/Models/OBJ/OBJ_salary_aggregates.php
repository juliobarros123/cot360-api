<?php

namespace App\Models\OBJ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OBJ_salary_aggregates extends Model
{
    use SoftDeletes;
    protected $table = 'obj_salary_aggregates';
    protected $fillable = [
        'object_id',
        'total_base_salary',
        'total_addons_salary',
        'total_avg_variable_salary',
        'number_of_months',
        'employee_count',
        'slug'
    ];
    protected $casts = [
        'total_base_salary' => 'decimal:2',
        'total_addons_salary' => 'decimal:2',
        'total_avg_variable_salary' => 'decimal:2',
    ];
    public function object()
    {
        return $this->belongsTo(OBJ_objects::class, 'object_id');
    }
}
