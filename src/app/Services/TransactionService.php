<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionHistory;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    protected TransactionNotificationService $notificationService;

    public function __construct(TransactionNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * 端数処理（round: 四捨五入, floor: 切り捨て, ceil: 切り上げ）
     */
    public function applyRounding(float $value, string $roundingType): int
    {
        return match ($roundingType) {
            'floor' => (int)floor($value),
            'ceil'  => (int)ceil($value),
            default => (int)round($value),
        };
    }

    /**
     * 明細データおよび付帯費用から合計金額・消費税額を計算
     */
    public function calculateAmounts(
        array $items,
        int $shippingFee,
        int $discountAmount,
        int $taxRate,
        bool $isTaxExempt,
        string $amountRounding = 'round',
        string $taxRounding = 'round'
    ): array {
        $subtotal = 0;
        $calculatedItems = [];

        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 1.00);
            $unitPrice = (isset($item['unit_price']) && $item['unit_price'] !== '') ? (float)$item['unit_price'] : null;

            $amount = null;
            if ($unitPrice !== null) {
                $amount = $this->applyRounding($qty * $unitPrice, $amountRounding);
                $subtotal += $amount;
            }

            $calculatedItems[] = array_merge($item, [
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
                'amount'     => $amount,
            ]);
        }

        // 税抜小計 = 明細合計 + 送料 - 値引き (負数は0)
        $finalSubtotal = max(0, $subtotal + $shippingFee - $discountAmount);

        // 消費税額
        $taxAmount = 0;
        if (!$isTaxExempt && $taxRate > 0) {
            $taxAmount = $this->applyRounding($finalSubtotal * ($taxRate / 100), $taxRounding);
        }

        // 税込合計金額
        $totalAmount = $finalSubtotal + $taxAmount;

        return [
            'items'           => $calculatedItems,
            'subtotal_amount' => $finalSubtotal,
            'shipping_fee'    => $shippingFee,
            'discount_amount' => $discountAmount,
            'tax_amount'      => $taxAmount,
            'total_amount'    => $totalAmount,
        ];
    }

    /**
     * 新規見積依頼の作成（相見積もり複数社対応）
     */
    public function createEstimateRequests(
        int $myCompanyId,
        int $userId,
        array $linkIds,
        array $txData,
        array $items,
        array $attachments = []
    ): array {
        return DB::transaction(function () use ($myCompanyId, $userId, $linkIds, $txData, $items, $attachments) {
            $createdTxIds = [];

            foreach ($linkIds as $linkId) {
                $link = CompanyLink::where('id', (int)$linkId)
                    ->where('buyer_company_id', $myCompanyId)
                    ->where('status', 'approved')
                    ->first();

                if (!$link) {
                    continue;
                }

                // 1. 取引ヘッダ作成
                $tx = Transaction::create([
                    'company_link_id'       => (int)$linkId,
                    'status'                => 'estimate_request',
                    'title'                 => trim((string)($txData['title'] ?? '無題の見積依頼')),
                    'desired_delivery_date' => !empty($txData['desired_delivery_date']) ? $txData['desired_delivery_date'] : null,
                    'buyer_remarks'         => !empty($txData['buyer_remarks']) ? trim((string)$txData['buyer_remarks']) : null,
                ]);

                // 2. 明細行作成
                $this->syncItems($tx->id, $items);

                // 3. 添付ファイル保存
                foreach ($attachments as $att) {
                    if (isset($att['file_data']) && $att['file_data'] !== null) {
                        TransactionAttachment::create([
                            'transaction_id' => $tx->id,
                            'company_id'     => $myCompanyId,
                            'user_id'        => $userId,
                            'file_name'      => $att['file_name'],
                            'mime_type'      => $att['mime_type'],
                            'file_size'      => $att['file_size'],
                            'file_data'      => $att['file_data'],
                            'created_at'     => now(),
                        ]);
                    }
                }

                // 4. 履歴記録
                TransactionHistory::create([
                    'transaction_id' => $tx->id,
                    'user_id'        => $userId,
                    'status'         => 'estimate_request',
                    'note'           => $txData['buyer_remarks'] ?? '見積依頼を起票しました。',
                    'created_at'     => now(),
                ]);

                $createdTxIds[] = $tx->id;
            }

            if (empty($createdTxIds)) {
                throw new \RuntimeException('見積依頼の作成に失敗しました（有効な連携企業が選択されていません）。');
            }

            // 5. 関係者へのメール通知
            foreach ($createdTxIds as $createdTxId) {
                $this->notificationService->notifyStatusChange(
                    $createdTxId,
                    'estimate_request',
                    $userId,
                    $txData['buyer_remarks'] ?? null
                );
            }

            return $createdTxIds;
        });
    }

    /**
     * 新規直接注文の作成（1社指定）
     */
    public function createDirectOrder(
        int $myCompanyId,
        int $userId,
        int $linkId,
        array $txData,
        array $items,
        array $attachments = []
    ): int {
        return DB::transaction(function () use ($myCompanyId, $userId, $linkId, $txData, $items, $attachments) {
            $link = CompanyLink::where('id', $linkId)
                ->where('buyer_company_id', $myCompanyId)
                ->where('status', 'approved')
                ->first();

            if (!$link) {
                throw new \InvalidArgumentException('有効な取引先リンクではありません。');
            }

            // 1. 取引ヘッダ作成
            $tx = Transaction::create([
                'company_link_id'       => $linkId,
                'status'                => 'order_placed',
                'title'                 => trim((string)($txData['title'] ?? '無題の注文')),
                'desired_delivery_date' => !empty($txData['desired_delivery_date']) ? $txData['desired_delivery_date'] : null,
                'buyer_remarks'         => !empty($txData['buyer_remarks']) ? trim((string)$txData['buyer_remarks']) : null,
            ]);

            // 2. 明細行作成
            $this->syncItems($tx->id, $items);

            // 3. 添付ファイル保存
            foreach ($attachments as $att) {
                if (isset($att['file_data']) && $att['file_data'] !== null) {
                    TransactionAttachment::create([
                        'transaction_id' => $tx->id,
                        'company_id'     => $myCompanyId,
                        'user_id'        => $userId,
                        'file_name'      => $att['file_name'],
                        'mime_type'      => $att['mime_type'],
                        'file_size'      => $att['file_size'],
                        'file_data'      => $att['file_data'],
                        'created_at'     => now(),
                    ]);
                }
            }

            // 4. 履歴記録
            TransactionHistory::create([
                'transaction_id' => $tx->id,
                'user_id'        => $userId,
                'status'         => 'order_placed',
                'note'           => $txData['buyer_remarks'] ?? '注文を送信しました。',
                'created_at'     => now(),
            ]);

            // 5. 関係者へのメール通知
            $this->notificationService->notifyStatusChange(
                $tx->id,
                'order_placed',
                $userId,
                $txData['buyer_remarks'] ?? null
            );

            return $tx->id;
        });
    }

    /**
     * 売手による明細・金額・納期の編集保存（下書き / 回答準備）
     */
    public function saveSellerEdit(
        int $transactionId,
        int $sellerCompanyId,
        int $userId,
        array $editData,
        array $items,
        array $newAttachments = [],
        array $deleteAttachmentIds = []
    ): void {
        DB::transaction(function () use ($transactionId, $sellerCompanyId, $userId, $editData, $items, $newAttachments, $deleteAttachmentIds) {
            $tx = Transaction::with('companyLink')->find($transactionId);
            if (!$tx || (int)$tx->companyLink->seller_company_id !== $sellerCompanyId) {
                throw new \InvalidArgumentException('取引データが見つからないか、編集権限がありません。');
            }

            // ロック判定
            if (in_array($tx->status, ['order_accepted', 'estimate_declined', 'order_declined'], true)) {
                throw new \DomainException('この取引は既に完了または終了（ロック）しているため編集できません。');
            }

            $sellerCompany = Company::find($sellerCompanyId);
            $amountRounding = $sellerCompany->amount_rounding ?? 'round';
            $taxRounding    = $sellerCompany->tax_rounding ?? 'round';
            $taxRate        = (int)($sellerCompany->tax_rate ?? 10);
            $isTaxExempt    = !empty($editData['is_tax_exempt']);

            // 金額計算
            $calc = $this->calculateAmounts(
                $items,
                (int)($editData['shipping_fee'] ?? 0),
                (int)($editData['discount_amount'] ?? 0),
                $taxRate,
                $isTaxExempt,
                $amountRounding,
                $taxRounding
            );

            // 1. 取引ヘッダ更新
            $updateData = [
                'delivery_date'   => !empty($editData['delivery_date']) ? $editData['delivery_date'] : null,
                'is_tax_exempt'   => $isTaxExempt ? 1 : 0,
                'tax_rate'        => $taxRate,
                'amount_rounding' => $amountRounding,
                'tax_rounding'    => $taxRounding,
                'subtotal_amount' => $calc['subtotal_amount'],
                'shipping_fee'    => $calc['shipping_fee'],
                'discount_amount' => $calc['discount_amount'],
                'tax_amount'      => $calc['tax_amount'],
                'total_amount'    => $calc['total_amount'],
                'seller_remarks'  => !empty($editData['seller_remarks']) ? trim((string)$editData['seller_remarks']) : null,
            ];

            if (isset($editData['title']) && trim((string)$editData['title']) !== '') {
                $updateData['title'] = trim((string)$editData['title']);
            }

            $tx->update($updateData);

            // 2. 明細行洗替更新
            $this->syncItems($transactionId, $calc['items']);

            // 3. 添付ファイル削除
            if (!empty($deleteAttachmentIds)) {
                TransactionAttachment::where('transaction_id', $transactionId)
                    ->whereIn('id', $deleteAttachmentIds)
                    ->delete();
            }

            // 4. 新規添付ファイル保存
            foreach ($newAttachments as $att) {
                if (isset($att['file_data']) && $att['file_data'] !== null) {
                    TransactionAttachment::create([
                        'transaction_id' => $transactionId,
                        'company_id'     => $sellerCompanyId,
                        'user_id'        => $userId,
                        'file_name'      => $att['file_name'],
                        'mime_type'      => $att['mime_type'],
                        'file_size'      => $att['file_size'],
                        'file_data'      => $att['file_data'],
                        'created_at'     => now(),
                    ]);
                }
            }
        });
    }

    /**
     * 売手による見積回答の送信（estimate_request -> estimate_replied）
     */
    public function submitEstimateReply(int $transactionId, int $sellerCompanyId, int $userId, ?string $replyRemarks = null): void
    {
        $tx = Transaction::with('companyLink')->find($transactionId);
        if (!$tx || (int)$tx->companyLink->seller_company_id !== $sellerCompanyId) {
            throw new \InvalidArgumentException('取引データが見つかりません。');
        }

        if ($tx->status !== 'estimate_request' && $tx->status !== 'estimate_replied') {
            throw new \DomainException('見積回答を送信できるステータスではありません。');
        }

        $tx->update([
            'status'         => 'estimate_replied',
            'seller_remarks' => $replyRemarks !== null ? trim($replyRemarks) : $tx->seller_remarks,
        ]);

        TransactionHistory::create([
            'transaction_id' => $transactionId,
            'user_id'        => $userId,
            'status'         => 'estimate_replied',
            'note'           => $replyRemarks ?: '見積回答を送信しました。',
            'created_at'     => now(),
        ]);

        // メール通知
        $this->notificationService->notifyStatusChange($transactionId, 'estimate_replied', $userId, $replyRemarks);
    }

    /**
     * 買手による見積回答経由の発注（estimate_replied -> order_placed）
     */
    public function placeOrderFromEstimate(int $transactionId, int $buyerCompanyId, int $userId, ?string $buyerRemarks = null): void
    {
        $tx = Transaction::with('companyLink')->find($transactionId);
        if (!$tx || (int)$tx->companyLink->buyer_company_id !== $buyerCompanyId) {
            throw new \InvalidArgumentException('取引データが見つかりません。');
        }

        if ($tx->status !== 'estimate_replied') {
            throw new \DomainException('見積回答済みの取引のみ注文を実行できます。');
        }

        $tx->update([
            'status'        => 'order_placed',
            'buyer_remarks' => $buyerRemarks !== null ? trim($buyerRemarks) : $tx->buyer_remarks,
        ]);

        TransactionHistory::create([
            'transaction_id' => $transactionId,
            'user_id'        => $userId,
            'status'         => 'order_placed',
            'note'           => $buyerRemarks ?: '見積回答に基づき注文を確定・送信しました。',
            'created_at'     => now(),
        ]);

        // メール通知
        $this->notificationService->notifyStatusChange($transactionId, 'order_placed', $userId, $buyerRemarks);
    }

    /**
     * 売手による受注承諾（注文回答・ゴール）（order_placed -> order_accepted）
     */
    public function acceptOrder(int $transactionId, int $sellerCompanyId, int $userId, ?string $acceptRemarks = null): void
    {
        $tx = Transaction::with('companyLink')->find($transactionId);
        if (!$tx || (int)$tx->companyLink->seller_company_id !== $sellerCompanyId) {
            throw new \InvalidArgumentException('取引データが見つかりません。');
        }

        if ($tx->status !== 'order_placed') {
            throw new \DomainException('注文受付中（order_placed）の取引のみ受注承諾が可能です。');
        }

        $tx->update([
            'status'         => 'order_accepted',
            'seller_remarks' => $acceptRemarks !== null ? trim($acceptRemarks) : $tx->seller_remarks,
        ]);

        TransactionHistory::create([
            'transaction_id' => $transactionId,
            'user_id'        => $userId,
            'status'         => 'order_accepted',
            'note'           => $acceptRemarks ?: '注文を承諾（受注承諾）しました。取引成立・確定',
            'created_at'     => now(),
        ]);

        // メール通知
        $this->notificationService->notifyStatusChange($transactionId, 'order_accepted', $userId, $acceptRemarks);
    }

    /**
     * 売手による見積辞退（estimate_request/estimate_replied -> estimate_declined）
     */
    public function declineEstimate(int $transactionId, int $sellerCompanyId, int $userId, string $category, string $reason): void
    {
        $tx = Transaction::with('companyLink')->find($transactionId);
        if (!$tx || (int)$tx->companyLink->seller_company_id !== $sellerCompanyId) {
            throw new \InvalidArgumentException('取引データが見つかりません。');
        }

        if (!in_array($tx->status, ['estimate_request', 'estimate_replied'], true)) {
            throw new \DomainException('見積フェーズの取引のみ見積辞退が可能です。');
        }

        $tx->update([
            'status'           => 'estimate_declined',
            'decline_category' => trim($category),
            'decline_reason'   => trim($reason),
        ]);

        TransactionHistory::create([
            'transaction_id' => $transactionId,
            'user_id'        => $userId,
            'status'         => 'estimate_declined',
            'note'           => "[{$category}] {$reason}",
            'created_at'     => now(),
        ]);

        // メール通知
        $this->notificationService->notifyStatusChange($transactionId, 'estimate_declined', $userId, "[辞退理由: {$category}] {$reason}");
    }

    /**
     * 売手による注文辞退（order_placed -> order_declined）
     */
    public function declineOrder(int $transactionId, int $sellerCompanyId, int $userId, string $category, string $reason): void
    {
        $tx = Transaction::with('companyLink')->find($transactionId);
        if (!$tx || (int)$tx->companyLink->seller_company_id !== $sellerCompanyId) {
            throw new \InvalidArgumentException('取引データが見つかりません。');
        }

        if ($tx->status !== 'order_placed') {
            throw new \DomainException('注文フェーズの取引のみ注文辞退が可能です。');
        }

        $tx->update([
            'status'           => 'order_declined',
            'decline_category' => trim($category),
            'decline_reason'   => trim($reason),
        ]);

        TransactionHistory::create([
            'transaction_id' => $transactionId,
            'user_id'        => $userId,
            'status'         => 'order_declined',
            'note'           => "[{$category}] {$reason}",
            'created_at'     => now(),
        ]);

        // メール通知
        $this->notificationService->notifyStatusChange($transactionId, 'order_declined', $userId, "[辞退理由: {$category}] {$reason}");
    }

    /**
     * 明細行の洗替同期
     */
    public function syncItems(int $transactionId, array $itemsData): void
    {
        TransactionItem::where('transaction_id', $transactionId)->delete();

        $lineNo = 1;
        foreach ($itemsData as $item) {
            if (empty(trim((string)($item['item_name'] ?? '')))) {
                continue;
            }

            TransactionItem::create([
                'transaction_id' => $transactionId,
                'line_no'        => $lineNo++,
                'item_name'      => trim((string)$item['item_name']),
                'item_code'      => !empty($item['item_code']) ? trim((string)$item['item_code']) : null,
                'spec'           => !empty($item['spec']) ? trim((string)$item['spec']) : (!empty($item['specification']) ? trim((string)$item['specification']) : null),
                'quantity'       => (float)($item['quantity'] ?? 1.00),
                'unit'           => !empty($item['unit']) ? trim((string)$item['unit']) : '個',
                'unit_price'     => (isset($item['unit_price']) && $item['unit_price'] !== '') ? (float)$item['unit_price'] : null,
                'amount'         => (isset($item['amount']) && $item['amount'] !== '') ? (int)$item['amount'] : null,
            ]);
        }
    }
}
