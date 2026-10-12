<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyLinkApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $sellerCompany,
        public Company $buyerCompany,
        public User $adminUser
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【MESHu】{$this->sellerCompany->name} 様との取引連携が承認されました",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.company_link_approved',
        );
    }
}
