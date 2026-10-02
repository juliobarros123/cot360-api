<?php

namespace App\Models\ENT;

use Illuminate\Database\Eloquent\Model;

class ENT_compliance_documents extends Model
{
    protected $table = 'ent_compliance_documents';
    protected $fillable = [
        'entity_id',
        'document_type',
        'file_path',
        'file_name',
        'status',
        'issue_date',
        'expiry_date',
        'reviewed_by',
        'reviewed_at',
        'review_notes'
    ];
    protected $casts = ['issue_date' => 'date', 'expiry_date' => 'date', 'reviewed_at' => 'datetime'];
    public function entity()
    {
        return $this->belongsTo(ENT_entities::class, 'entity_id');
    }
}
