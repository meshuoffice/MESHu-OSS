<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $tempPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【MESHu】仮パスワード再発行のお知らせ',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.user_reset_password',
        );
    }
}
