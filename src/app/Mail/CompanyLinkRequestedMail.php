<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyLinkRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $applicantCompany,
        public Company $targetCompany,
        public User $adminUser
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【MESHu】{$this->applicantCompany->name} 様より取引連携依頼が届きました",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.company_link_requested',
        );
    }
}
