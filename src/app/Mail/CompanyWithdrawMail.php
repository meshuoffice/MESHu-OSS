<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyWithdrawMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        $replyEmail = config('mail.office.reply', env('MAIL_OFFICE_REPLY', 'officem@meshu.org'));

        return new Envelope(
            subject: '【MESHu】退会申請受付完了のお知らせ（30日間の保持期間について）',
            replyTo: [new Address($replyEmail, 'MESHu運営事務局')],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.company_withdraw',
            with: [
                'company'    => $this->company,
                'user'       => $this->user,
                'replyEmail' => config('mail.office.reply', env('MAIL_OFFICE_REPLY', 'officem@meshu.org')),
            ],
        );
    }
}
