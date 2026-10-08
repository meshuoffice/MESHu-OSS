<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public User $user,
        public string $contactTypeName,
        public string $contactSubject,
        public string $contentMessage,
        public ?string $attachmentPath = null,
        public ?string $originalFileName = null,
        public ?string $mimeType = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【MESHuお問い合わせ】[{$this->contactTypeName}] {$this->contactSubject} ({$this->company->name})",
            replyTo: [$this->user->email],
        );
    }

    public function content(): Content
    {
        $hasAttachment = !empty($this->attachmentPath) && file_exists($this->attachmentPath);

        return new Content(
            text: 'emails.contact_inquiry',
            with: [
                'company'         => $this->company,
                'user'            => $this->user,
                'contactTypeName' => $this->contactTypeName,
                'subject'         => $this->contactSubject,
                'contentMessage'  => $this->contentMessage,
                'hasAttachment'   => $hasAttachment,
                'fileName'        => $this->originalFileName ?? '',
            ],
        );
    }

    public function attachments(): array
    {
        if (!empty($this->attachmentPath) && file_exists($this->attachmentPath)) {
            $attachment = Attachment::fromPath($this->attachmentPath);
            if (!empty($this->originalFileName)) {
                $attachment->as($this->originalFileName);
            }
            if (!empty($this->mimeType)) {
                $attachment->withMime($this->mimeType);
            }
            return [$attachment];
        }
        return [];
    }
}
