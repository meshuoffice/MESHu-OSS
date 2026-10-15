<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChatMessageSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $recipient,
        public Company $recipientCompany,
        public Company $senderCompany,
        public User $senderUser,
        public CompanyLink $companyLink,
        public string $messageBody,
        public bool $hasAttachments,
        public string $chatUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[MESHu] 【チャット新着】{$this->senderCompany->name} 様より新着メッセージ",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.chat_message_sent',
        );
    }
}
