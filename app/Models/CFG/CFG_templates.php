<?php

namespace App\Models\CFG;

use App\Models\CFG\CFG_insurance_branches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_templates extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_templates';
    protected $fillable = [
        'insurance_branch_id',
        'code',
        'sequence',
        'name',
        'description',
        'package',
        'object_type',
        'qty_objects',
        'active',
        'slug',
        'created_by',
        'updated_by'
    ];
    protected $casts = ['active' => 'boolean', 'qty_objects' => 'integer', 'sequence' => 'integer'];
    public function branch()
    {
        return $this->belongsTo(CFG_insurance_branches::class, 'insurance_branch_id');
    }
    public function coverages()
    {
        return $this->belongsToMany(CFG_coverages::class, 'cfg_template_coverage', 'template_id', 'coverage_id')
            ->withPivot('is_mandatory', 'display_order')->withTimestamps();
    }
}
