<?php

namespace App\Models\QUO;


use App\Models\CFG\CFG_rates_cae;
use App\Models\ENT\ENT_entity_roles;
use App\Models\ENT\ENT_sales_channels;
use App\Models\QUO\QUO_object_risk;
use App\Models\QUO\QUO_premiums;
use App\Models\QUO\QUO_special_conditions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QUO_quotation_versions extends Model
{
    use SoftDeletes;
    protected $table = 'quo_quotation_versions';
    protected $appends = ['unique_objects_count'];
    protected $fillable = [
        'quotation_id',
        'version',
        'table_code',
        'commercial_agent_role_id',
        'sales_channel_id',
        'template_code',
        'cae_rate_id',
        'total_premium',
        'discount_percent',
        'currency',
        'exchange_rate',
        'exchange_rate_usd_aoa',
        'total_premium_usd',
        'status',
        'created_date',
        'valid_until',
        'policy_id',
        'snapshot_json',
        'created_by',
        'updated_by'
    ];
    protected $casts = [
        'snapshot_json' => 'array',
        'created_date' => 'date',
        'valid_until' => 'date',
        'total_premium' => 'double',
        'discount_percent' => 'double',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_usd_aoa' => 'decimal:6',
        'total_premium_usd' => 'decimal:2',
    ];
    // CORRETO
    public function quotation()
    {
        return $this->belongsTo(\App\Models\QUO\QUO_quotations::class, 'quotation_id');
    }
    public function commercialAgentRole()
    {
        return $this->belongsTo(ENT_entity_roles::class, 'commercial_agent_role_id');
    }
    public function salesChannel()
    {
        return $this->belongsTo(ENT_sales_channels::class, 'sales_channel_id');
    }
    public function caeRate()
    {
        return $this->belongsTo(CFG_rates_cae::class, 'cae_rate_id');
    }
    public function objectRisks()
    {
        return $this->hasMany(QUO_object_risk::class, 'quotation_version_id');
    }
    public function premiums()
    {
        return $this->hasMany(QUO_premiums::class, 'quotation_version_id');
    }
    public function specialConditions()
    {
        return $this->hasMany(QUO_special_conditions::class, 'quotation_version_id');
    }

    public function getUniqueObjectsCountAttribute(): int
    {
        if ($this->relationLoaded('objectRisks')) {
            return $this->objectRisks
                ->pluck('object_id')
                ->filter()
                ->unique()
                ->count();
        }

        return (int) $this->objectRisks()
            ->whereNotNull('object_id')
            ->distinct('object_id')
            ->count('object_id');
    }
}
