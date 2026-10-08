<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactAutoReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public User $user,
        public string $contactTypeName,
        public string $contactSubject,
        public string $contentMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【MESHu】お問い合わせを受領いたしました',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact_auto_reply',
            with: [
                'company'         => $this->company,
                'user'            => $this->user,
                'contactTypeName' => $this->contactTypeName,
                'subject'         => $this->contactSubject,
                'contentMessage'  => $this->contentMessage,
            ],
        );
    }
}
