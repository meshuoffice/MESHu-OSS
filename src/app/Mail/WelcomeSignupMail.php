<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeSignupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $userName,
        public string $urlCode,
        public string $rawPassword,
        public string $email
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【MESHu】アカウント登録申請の受付完了',
        );
    }

    public function content(): Content
    {
        // プレーンテキストメールとして送信（改行を完全保持）
        return new Content(
            text: 'emails.welcome_signup',
            with: [
                'loginUrl' => url('/' . $this->urlCode),
            ],
        );
    }
}
