<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Company $company,
        public string $rawToken
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【MESHu】パスワード再設定のご案内',
        );
    }

    public function content(): Content
    {
        // プレーンテキストメールとして送信（改行を完全保持）
        return new Content(
            text: 'emails.password_reset',
            with: [
                'resetUrl' => url("/{$this->company->url_code}/reset-password?token={$this->rawToken}"),
            ],
        );
    }
}
