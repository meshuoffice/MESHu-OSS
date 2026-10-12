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

    public string $signupUrl;

    public function __construct(
        public Company $inviterCompany,
        public Invitation $invitation
    ) {
        $this->signupUrl = url('/signup?invitation_code=' . $this->invitation->invitation_code);
    }

    public function envelope(): Envelope
    {
        $subject = !empty($this->invitation->mail_subject)
            ? $this->invitation->mail_subject
            : "【MESHu】{$this->inviterCompany->name}より取引連携のご招待";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.company_invitation',
            with: [
                'signupUrl' => $this->signupUrl,
            ],
        );
    }
}

