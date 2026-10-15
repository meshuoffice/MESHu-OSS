<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\ChatReadStatus;
use App\Models\CompanyLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        protected ChatNotificationService $notificationService
    ) {}

    /**
     * メッセージ送信および添付ファイル保存・既読更新・メール通知
     *
     * @param int $companyLinkId
     * @param int $companyId
     * @param int $userId
     * @param string $messageBody
     * @param UploadedFile[] $attachments
     * @return ChatMessage
     */
    public function sendMessage(
        int $companyLinkId,
        int $companyId,
        int $userId,
        string $messageBody,
        array $attachments = []
    ): ChatMessage {
        return DB::transaction(function () use ($companyLinkId, $companyId, $userId, $messageBody, $attachments) {
            $message = ChatMessage::create([
                'company_link_id' => $companyLinkId,
                'company_id'      => $companyId,
                'user_id'         => $userId,
                'message_body'    => $messageBody,
            ]);

            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    ChatMessageAttachment::create([
                        'chat_message_id' => $message->id,
                        'file_name'       => $file->getClientOriginalName(),
                        'mime_type'       => $file->getClientMimeType() ?: 'application/octet-stream',
                        'file_size'       => $file->getSize(),
                        'file_data'       => file_get_contents($file->getRealPath()),
                        'created_at'      => now(),
                    ]);
                }
            }

            // 送信者自身の既読ステータスを最新メッセージで更新
            $this->markAsRead($companyLinkId, $userId, $message->id);

            // 相手先担当者へのメール通知を実行
            $this->notificationService->notifyNewMessage($message);

            return $message;
        });
    }

    /**
     * 対象連携チャットの既読位置を更新
     */
    public function markAsRead(int $companyLinkId, int $userId, ?int $messageId = null): void
    {
        if ($messageId === null) {
            $messageId = (int) ChatMessage::where('company_link_id', $companyLinkId)->max('id');
        }

        if ($messageId <= 0) {
            return;
        }

        DB::table('chat_read_statuses')->upsert(
            [
                'company_link_id'      => $companyLinkId,
                'user_id'              => $userId,
                'last_read_message_id' => $messageId,
                'read_at'              => now(),
            ],
            ['company_link_id', 'user_id'],
            ['last_read_message_id', 'read_at']
        );
    }

    /**
     * 対象連携における自社未読メッセージ数を取得
     */
    public function getUnreadCount(int $companyLinkId, int $userId, int $myCompanyId): int
    {
        $status = ChatReadStatus::where('company_link_id', $companyLinkId)
            ->where('user_id', $userId)
            ->first();

        $lastReadId = $status ? (int)$status->last_read_message_id : 0;

        return ChatMessage::where('company_link_id', $companyLinkId)
            ->where('company_id', '!=', $myCompanyId)
            ->where('id', '>', $lastReadId)
            ->count();
    }
}
