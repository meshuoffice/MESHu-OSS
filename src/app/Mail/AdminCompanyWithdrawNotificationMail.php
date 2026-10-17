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

class AdminCompanyWithdrawNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public User $user,
        public int $activeTransactionsCount = 0
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【MESHu事務局通知】企業退会申請を受領しました（{$this->company->name}）",
            replyTo: [new Address($this->user->email, $this->user->user_name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.admin_company_withdraw_notification',
            with: [
                'company'                 => $this->company,
                'user'                    => $this->user,
                'activeTransactionsCount' => $this->activeTransactionsCount,
                'withdrawnAt'             => now()->format('Y年m月d日 H:i'),
                'deleteScheduledDate'     => now()->addDays(30)->format('Y年m月d日'),
                'adminCorpUrl'            => route('admin.corp.show', ['id' => $this->company->id]),
            ],
        );
    }
}
