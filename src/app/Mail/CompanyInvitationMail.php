<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $inviterCompany,
        public Invitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【MESHu】{$this->inviterCompany->name} 様よりMESHu取引連携のご案内",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.company_invitation',
        );
    }
}
