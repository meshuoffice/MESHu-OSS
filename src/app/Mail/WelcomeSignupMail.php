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
        public string $email,
        public bool $isImmediatelyActive = false
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isImmediatelyActive
            ? '【MESHu】アカウント登録が完了しました（即時ご利用いただけます）'
            : '【MESHu】アカウント登録申請の受付完了';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        // プレーンテキストメールとして送信（改行を完全保持）
        return new Content(
            text: 'emails.welcome_signup',
            with: [
                'loginUrl'            => url('/' . $this->urlCode),
                'isImmediatelyActive' => $this->isImmediatelyActive,
            ],
        );
    }
}
