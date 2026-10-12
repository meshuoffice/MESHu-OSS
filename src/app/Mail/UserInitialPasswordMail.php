<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInitialPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $initialPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【MESHu】アカウント登録と初期パスワードのお知らせ',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.user_initial_password',
        );
    }
}
