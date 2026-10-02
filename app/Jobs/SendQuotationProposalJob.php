<?php

namespace App\Jobs;

use App\Models\QUO\QUO_quotation_proposals;
use App\Mail\QuotationProposalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendQuotationProposalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $proposal;
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [60, 180, 300];

    public function __construct(QUO_quotation_proposals $proposal)
    {
        $this->proposal = $proposal;
    }

    public function handle(): void
    {
        try {
            // Verificar se o email do cliente existe
            if (empty($this->proposal->client_email)) {
                Log::warning('Tentativa de envio sem email', ['proposal_id' => $this->proposal->id]);
                return;
            }

            // Enviar email
            Mail::to($this->proposal->client_email)
                ->send(new QuotationProposalEmail($this->proposal));
            
            Log::info('Email de proposta enviado com sucesso', [
                'proposal_id' => $this->proposal->id,
                'client_email' => $this->proposal->client_email,
                'quotation_version_id' => $this->proposal->quotation_version_id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de proposta', [
                'proposal_id' => $this->proposal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de envio de proposta falhou', [
            'proposal_id' => $this->proposal->id,
            'error' => $exception->getMessage()
        ]);
    }
}
