<?php

namespace App\Services;

use App\Mail\ChatMessageSentMail;
use App\Models\ChatMessage;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChatNotificationService
{
    /**
     * チャット新着メッセージ通知メールを相手先関係者に送信
     */
    public function notifyNewMessage(ChatMessage $message): void
    {
        try {
            $link = CompanyLink::find($message->company_link_id);
            if (!$link) {
                return;
            }

            $senderUser = User::find($message->user_id);
            $senderCompany = Company::find($message->company_id);
            if (!$senderUser || !$senderCompany) {
                return;
            }

            // 相手先企業（送信元企業ではない側）を特定
            $targetCompanyId = ((int)$senderCompany->id === (int)$link->buyer_company_id)
                ? (int)$link->seller_company_id
                : (int)$link->buyer_company_id;

            $recipientCompany = Company::find($targetCompanyId);
            if (!$recipientCompany) {
                return;
            }

            // 相手先企業の送信対象者を抽出
            $recipients = $this->getEligibleRecipients(
                (int)$link->id,
                $targetCompanyId,
                (int)$senderUser->id
            );

            if (empty($recipients)) {
                return;
            }

            $hasAttachments = $message->attachments()->exists();
            $chatUrl = "chat/{$link->id}";

            foreach ($recipients as $recipient) {
                if (!empty($recipient['email'])) {
                    Mail::to($recipient['email'])->send(new ChatMessageSentMail(
                        recipient: $recipient,
                        recipientCompany: $recipientCompany,
                        senderCompany: $senderCompany,
                        senderUser: $senderUser,
                        companyLink: $link,
                        messageBody: $message->message_body,
                        hasAttachments: $hasAttachments,
                        chatUrl: $chatUrl
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error("Chat notification error for message #{$message->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 担当アクセス制御および通知設定に基づく送信対象ユーザー一覧の抽出
     *
     * 【配信マトリクス】
     * 1. 相手先企業にその連携(company_link)に紐づく担当者(user_assigned_links)が存在する場合:
     *    -> 割り当てられた担当者(notify_chat=true, status='active')のみに送信
     * 2. 相手先企業に担当者が1人も割り当てられていない場合:
     *    -> 相手先企業の全担当者(is_all_assigned=1 または admin, notify_chat=true, status='active')に送信
     * 3. メッセージ送信者本人(excludeUserId)および自社メンバーには送信しない
     */
    public function getEligibleRecipients(
        int $companyLinkId,
        int $targetCompanyId,
        ?int $excludeUserId = null
    ): array {
        // 相手先企業で、この連携に個別割当されたアクティブユーザーが存在するか確認
        $hasAssignedUsers = DB::table('user_assigned_links as ual')
            ->join('users as u', 'u.id', '=', 'ual.user_id')
            ->where('ual.company_link_id', $companyLinkId)
            ->where('u.company_id', $targetCompanyId)
            ->where('u.status', 'active')
            ->exists();

        $query = DB::table('users as u')
            ->select('u.id', 'u.user_name', 'u.email', 'u.company_id', 'u.role', 'u.is_all_assigned')
            ->where('u.company_id', $targetCompanyId)
            ->where('u.status', 'active')
            ->where('u.notify_chat', true);

        if ($hasAssignedUsers) {
            // ① 割当担当者が存在する場合: 個別割当された担当者のみ
            $query->whereExists(function ($sub) use ($companyLinkId) {
                $sub->select(DB::raw(1))
                    ->from('user_assigned_links as ual')
                    ->whereColumn('ual.user_id', 'u.id')
                    ->where('ual.company_link_id', $companyLinkId);
            });
        } else {
            // ② 担当者が1人も割り当てられていない場合: 全担当者(is_all_assigned = 1 または admin)
            $query->where(function ($q) {
                $q->where('u.is_all_assigned', 1)
                  ->orWhere('u.role', 'admin');
            });
        }

        if ($excludeUserId !== null) {
            $query->where('u.id', '!=', $excludeUserId);
        }

        return $query->get()->map(fn($item) => (array)$item)->all();
    }
}
