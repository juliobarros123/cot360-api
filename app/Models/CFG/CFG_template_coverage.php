<?php

namespace App\Models\CFG;

use Illuminate\Database\Eloquent\Model;

class CFG_template_coverage extends Model
{
    protected $table = 'cfg_template_coverage';
    protected $fillable = ['template_id', 'coverage_id', 'is_mandatory', 'display_order'];
    protected $casts = ['is_mandatory' => 'boolean'];
    public function template()
    {
        return $this->belongsTo(CFG_templates::class, 'template_id');
    }
    public function coverage()
    {
        return $this->belongsTo(CFG_coverages::class, 'coverage_id');
    }
}
