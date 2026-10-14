<?php

namespace App\Services;

use App\Mail\TransactionCommentAddedMail;
use App\Mail\TransactionStatusChangedMail;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionNotificationService
{
    /**
     * 取引ステータス変更通知メールを関係者に送信
     */
    public function notifyStatusChange(
        int $transactionId,
        string $newStatus,
        int $actionUserId,
        ?string $comment = null
    ): void {
        try {
            $tx = Transaction::find($transactionId);
            if (!$tx) {
                return;
            }

            $link = CompanyLink::find($tx->company_link_id);
            if (!$link) {
                return;
            }

            $buyerCompany  = Company::find($link->buyer_company_id);
            $sellerCompany = Company::find($link->seller_company_id);
            $actionUser    = User::find($actionUserId);

            if (!$buyerCompany || !$sellerCompany || !$actionUser) {
                return;
            }

            $isActionByBuyer = ((int)$actionUser->company_id === (int)$buyerCompany->id);
            $actionCompany   = $isActionByBuyer ? $buyerCompany : $sellerCompany;
            $targetCompany   = $isActionByBuyer ? $sellerCompany : $buyerCompany;
            $targetRole      = $isActionByBuyer ? 'seller' : 'buyer';

            $statusMeta = $this->getStatusMeta($newStatus, $isActionByBuyer);

            // 相手先企業の対象関係者を抽出
            $recipients = $this->getEligibleRecipients(
                (int)$tx->company_link_id,
                (int)$targetCompany->id,
                'trade',
                $actionUserId
            );

            if (empty($recipients)) {
                return;
            }

            $detailUrl = ($targetRole === 'buyer')
                ? "buy/detail/{$tx->id}"
                : "sell/detail/{$tx->id}";

            foreach ($recipients as $recipient) {
                if (!empty($recipient['email'])) {
                    Mail::to($recipient['email'])->send(new TransactionStatusChangedMail(
                        recipient: $recipient,
                        recipientCompany: $targetCompany,
                        actionCompany: $actionCompany,
                        actionUser: $actionUser,
                        actionTitle: $statusMeta['action_title'],
                        statusLabel: $statusMeta['status_label'],
                        tx: $tx,
                        buyerCompany: $buyerCompany,
                        sellerCompany: $sellerCompany,
                        userComment: $comment,
                        detailUrl: $detailUrl
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error("Transaction notification error for tx #{$transactionId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 取引メッセージ（コメント）投稿通知メールを相手先関係者に送信
     */
    public function notifyNewComment(
        int $transactionId,
        int $actionUserId,
        string $commentBody
    ): void {
        try {
            $tx = Transaction::find($transactionId);
            if (!$tx) {
                return;
            }

            $link = CompanyLink::find($tx->company_link_id);
            if (!$link) {
                return;
            }

            $buyerCompany  = Company::find($link->buyer_company_id);
            $sellerCompany = Company::find($link->seller_company_id);
            $actionUser    = User::find($actionUserId);

            if (!$buyerCompany || !$sellerCompany || !$actionUser) {
                return;
            }

            $isActionByBuyer = ((int)$actionUser->company_id === (int)$buyerCompany->id);
            $actionCompany   = $isActionByBuyer ? $buyerCompany : $sellerCompany;
            $targetCompany   = $isActionByBuyer ? $sellerCompany : $buyerCompany;
            $targetRole      = $isActionByBuyer ? 'seller' : 'buyer';

            // 相手先企業の対象関係者を抽出（notify_comment = true）
            $recipients = $this->getEligibleRecipients(
                (int)$tx->company_link_id,
                (int)$targetCompany->id,
                'comment',
                $actionUserId
            );

            if (empty($recipients)) {
                return;
            }

            $detailUrl = ($targetRole === 'buyer')
                ? "buy/detail/{$tx->id}#comments"
                : "sell/detail/{$tx->id}#comments";

            foreach ($recipients as $recipient) {
                if (!empty($recipient['email'])) {
                    Mail::to($recipient['email'])->send(new TransactionCommentAddedMail(
                        recipient: $recipient,
                        recipientCompany: $targetCompany,
                        actionCompany: $actionCompany,
                        actionUser: $actionUser,
                        tx: $tx,
                        buyerCompany: $buyerCompany,
                        sellerCompany: $sellerCompany,
                        commentBody: $commentBody,
                        detailUrl: $detailUrl
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error("Comment notification error for tx #{$transactionId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 担当アクセス制御および通知設定に基づく送信対象ユーザー一覧の抽出
     */
    public function getEligibleRecipients(
        int $companyLinkId,
        int $targetCompanyId,
        string $notifyType = 'trade',
        ?int $excludeUserId = null
    ): array {
        $query = DB::table('users as u')
            ->select('u.id', 'u.user_name', 'u.email', 'u.company_id', 'u.role', 'u.is_all_assigned')
            ->where('u.company_id', $targetCompanyId)
            ->where('u.status', 'active');

        // 通知受信設定フラグ
        if ($notifyType === 'comment') {
            $query->where('u.notify_comment', true);
        } else {
            $query->where('u.notify_trade', true);
        }

        // 担当区分判定（全担当 または 個別割当あり）
        $query->where(function ($q) use ($companyLinkId) {
            $q->where('u.is_all_assigned', 1)
              ->orWhereRaw("EXISTS (SELECT 1 FROM user_assigned_links ual WHERE ual.user_id = u.id AND ual.company_link_id = ?)", [$companyLinkId]);
        });

        if ($excludeUserId !== null) {
            $query->where('u.id', '!=', $excludeUserId);
        }

        return $query->get()->map(fn($item) => (array)$item)->all();
    }

    /**
     * ステータス表示名・アクション表示名の定義
     */
    public function getStatusMeta(string $status, bool $isActionByBuyer): array
    {
        return match ($status) {
            'estimate_request' => [
                'action_title' => '見積依頼',
                'status_label' => '見積回答待ち',
            ],
            'estimate_replied' => [
                'action_title' => '見積回答',
                'status_label' => '見積回答受領（要発注確認）',
            ],
            'estimate_declined' => [
                'action_title' => '見積辞退',
                'status_label' => '見積辞退',
            ],
            'order_placed' => [
                'action_title' => $isActionByBuyer ? '発注' : '直接注文',
                'status_label' => '受注承諾待ち',
            ],
            'order_accepted' => [
                'action_title' => '受注承諾（取引成立）',
                'status_label' => '取引成立（完了）',
            ],
            'order_declined' => [
                'action_title' => '注文辞退',
                'status_label' => '注文辞退',
            ],
            default => [
                'action_title' => 'ステータス更新',
                'status_label' => $status,
            ],
        };
    }
}
