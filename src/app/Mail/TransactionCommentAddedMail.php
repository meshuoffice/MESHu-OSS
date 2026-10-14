<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionCommentAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $recipient,
        public Company $recipientCompany,
        public Company $actionCompany,
        public User $actionUser,
        public Transaction $tx,
        public Company $buyerCompany,
        public Company $sellerCompany,
        public string $commentBody,
        public string $detailUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[MESHu] 【メッセージ着信】{$this->tx->title} (取引ID: #{$this->tx->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.transaction_comment_added',
        );
    }
}
