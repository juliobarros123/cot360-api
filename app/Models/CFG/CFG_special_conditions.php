<?php

namespace App\Models\CFG;

use App\Models\CFG\CFG_insurance_branches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFG_special_conditions extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_special_conditions';
    protected $fillable = [
        'insurance_branch_id',
        'code',
        'condition_type',
        'short_text',
        'long_text',
        'active',
        'slug'
    ];
    protected $casts = ['active' => 'boolean'];
    public function branch()
    {
        return $this->belongsTo(CFG_insurance_branches::class, 'insurance_branch_id');
    }
}
