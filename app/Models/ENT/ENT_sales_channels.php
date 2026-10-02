<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ENT_sales_channels extends Model
{
    use SoftDeletes;
    
    protected $table = 'ent_sales_channels';
    
    protected $fillable = [
        'channel_type', 
        'name', 
        'description', 
        'responsible', 
        'active', 
        'slug',
        'parent_id' // Adicionar este campo se quiser suporte a hierarquia
    ];
    
    protected $casts = [
        'active' => 'boolean'
    ];

    /**
     * Relacionamento com o canal pai
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relacionamento com os canais filhos
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}