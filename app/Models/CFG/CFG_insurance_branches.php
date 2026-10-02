<?php
// ============================================================
// MODELS — MÓDULO CFG
// App\Models\CFG\
// ============================================================

// ==================== CFG_insurance_branches.php ====================
namespace App\Models\CFG;

use App\Models\CFG\CFG_coverages;
use App\Models\CFG\CFG_rates_automobile;
use App\Models\CFG\CFG_rates_cae;
use App\Models\CFG\CFG_rates_health;
use App\Models\CFG\CFG_special_conditions;
use App\Models\CFG\CFG_templates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CFG_insurance_branches extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'cfg_insurance_branches';
    protected $fillable = [
        'code','name','description','requires_coordinates',
        'default_validity_days','policy_number_prefix','active',
        'slug','created_by','updated_by'
    ];
    protected $casts = [
        'requires_coordinates' => 'boolean',
        'active' => 'boolean',
        'default_validity_days' => 'integer',
    ];
    public function templates() { return $this->hasMany(CFG_templates::class, 'insurance_branch_id'); }
    public function coverages() { return $this->hasMany(CFG_coverages::class, 'insurance_branch_id'); }
    public function specialConditions() { return $this->hasMany(CFG_special_conditions::class, 'insurance_branch_id'); }
    public function ratesAutomobile() { return $this->hasMany(CFG_rates_automobile::class, 'insurance_branch_id'); }
    public function ratesHealth() { return $this->hasMany(CFG_rates_health::class, 'insurance_branch_id'); }
    public function ratesCae() { return $this->hasMany(CFG_rates_cae::class, 'insurance_branch_id'); }
}