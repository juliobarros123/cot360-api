<?php

namespace App\Models\OBJ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OBJ_student_counts extends Model
{
    protected $table = 'obj_student_counts';

    protected $fillable = [
        'object_id',
        'student_count',
        'slug'
    ];

    protected $casts = [
        'student_count' => 'integer'
    ];

    public function object(): BelongsTo
    {
        return $this->belongsTo(OBJ_objects::class, 'object_id');
    }
}