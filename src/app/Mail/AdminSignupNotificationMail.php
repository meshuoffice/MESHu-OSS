<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminSignupNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $signupData,
        public string $urlCode
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【MESHu】新規アカウント登録申請のお知らせ',
        );
    }

    public function content(): Content
    {
        // プレーンテキストメールとして送信（改行を完全保持）
        return new Content(
            text: 'emails.admin_signup_notification',
            with: [
                'data' => $this->signupData,
            ],
        );
    }
}
