<?php

namespace App\Models\QUO;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QUO_quotation_proposals extends Model
{
    protected $table = 'quotation_proposals';

    protected $appends = [
        'document_urls',
    ];

    protected $fillable = [
        'quotation_version_id',
        'quotation_version',
        'client_email',
        'client_name',
        'message',
        'proposal_pdf_url',
        'proposal_form_url',
        'status',
        'tracking_token',
        'sent_at',
        'viewed_at',
        'completed_at',
        'client_data',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'client_data' => 'array',
    ];

    public function quotationVersion(): BelongsTo
    {
        return $this->belongsTo(QUO_quotation_versions::class, 'quotation_version_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->quotationVersion();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['sent', 'viewed']);
    }

    public function markAsViewed(): void
    {
        $this->update([
            'status' => 'viewed',
            'viewed_at' => now(),
        ]);
    }

    public function markAsCompleted(array $clientData = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'client_data' => $clientData,
        ]);
    }

    public static function generateTrackingToken(): string
    {
        do {
            $token = Str::random(60);
        } while (self::where('tracking_token', $token)->exists());

        return $token;
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->sent_at && $this->sent_at->diffInDays(now()) > 30) {
            $this->update(['status' => 'expired']);
            return true;
        }

        return false;
    }

    public function getDaysRemaining(): int
    {
        if (!$this->sent_at || $this->isExpired()) {
            return 0;
        }

        $days = 30 - $this->sent_at->diffInDays(now());
        return max(0, $days);
    }

    public static function generateUrls(
        int $quotationId,
        int $quotationVersion,
        string $token,
        ?string $proposalFormPath = null,
        ?string $proposalFormUrl = null
    ): array
    {
        $baseUrl = rtrim(config('app.api_url'), '/');
        $urlFront = rtrim(config('app.url_front'), '/');
        $normalizedPath = trim((string) $proposalFormPath, '/');
        $allowedPaths = [
            'proposal-form',
            'proposal-acidentes-pessoais',
            'proposal-acidentes-trabalho',
            'proposal-mr-empresas',
            'proposal-saude-familia',
            'proposal-saude-grupo',
        ];

        if (!empty($proposalFormUrl)) {
            $separator = str_contains($proposalFormUrl, '?') ? '&' : '?';
            $urlWithToken = str_contains($proposalFormUrl, 'token=')
                ? $proposalFormUrl
                : "{$proposalFormUrl}{$separator}token={$token}";
            return [
                'pdf_url' => "{$baseUrl}/api/pdf/quotations/{$quotationId}/reprint/{$quotationVersion}",
                'form_url' => $urlWithToken,
            ];
        }

        $resolvedPath = in_array($normalizedPath, $allowedPaths, true) ? $normalizedPath : 'proposal-form';

        return [
            'pdf_url' => "{$baseUrl}/api/pdf/quotations/{$quotationId}/reprint/{$quotationVersion}",
            'form_url' => "{$urlFront}/{$resolvedPath}?token={$token}",
        ];
    }

    public function getDocumentUrlsAttribute(): array
    {
        $baseUrl = rtrim(config('app.api_url'), '/');

        return [
            'main' => "{$baseUrl}/api/quo/quotation-proposals/pdf/print/{$this->id}",
            'pep' => "{$baseUrl}/api/quo/quotation-proposals/pdf/print/{$this->id}/pep",
            'beneficiario_efectivo' => "{$baseUrl}/api/quo/quotation-proposals/pdf/print/{$this->id}/beneficiario-efectivo",
            'origem_destino_fundos' => "{$baseUrl}/api/quo/quotation-proposals/pdf/print/{$this->id}/origem-destino-fundos",
        ];
    }
}
