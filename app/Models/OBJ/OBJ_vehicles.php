<?php

namespace App\Models\OBJ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OBJ_vehicles extends Model
{
    use SoftDeletes;
    protected $table = 'obj_vehicles';
    protected $fillable = [
        'object_id',
        'plate',
        'chassis_number',
        'brand',
        'model',
        'version',
        'color',
        'type',
        'subcategory',
        'cylinder_cc',
        'number_seats',
        'weight',
        'first_plate_date',
        'dp_capital',
        'dp_initial_capital',
        'rc_capital',
        'slug'
    ];
    protected $casts = [
        'first_plate_date' => 'date',
        'dp_capital' => 'decimal:2',
        'dp_initial_capital' => 'decimal:2',
        'rc_capital' => 'decimal:2',
    ];
    public function object()
    {
        return $this->belongsTo(OBJ_objects::class, 'object_id');
    }
}
