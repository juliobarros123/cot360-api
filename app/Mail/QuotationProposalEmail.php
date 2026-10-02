<?php

namespace App\Mail;

use App\Models\QUO\QUO_quotation_proposals;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationProposalEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $proposal;

    public function __construct(QUO_quotation_proposals $proposal)
    {
        $this->proposal = $proposal;
    }

    public function envelope(): Envelope
    {
        $subject = 'Proposta de Seguro - ' . config('app.name');
        
        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation-proposal',
            with: [
                'proposal' => $this->proposal,
                'companyName' => config('app.name'),
                'companyContact' => config('app.contact_email', 'contato@empresa.com'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
