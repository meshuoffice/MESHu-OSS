<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CompanyLink;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionComment;
use App\Models\TransactionHistory;
use App\Models\TransactionItem;
use App\Services\TransactionNotificationService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyController extends Controller
{
    protected TransactionService $transactionService;
    protected TransactionNotificationService $notificationService;

    public function __construct(
        TransactionService $transactionService,
        TransactionNotificationService $notificationService
    ) {
        $this->transactionService  = $transactionService;
        $this->notificationService = $notificationService;
    }

    protected function getMyCompanyId(): int
    {
        $user = Auth::guard('web')->user();
        if (!$user || !$user->company_id) {
            abort(403, '自社企業情報が存在しません。');
        }
        return (int)$user->company_id;
    }

    protected function getMyUserId(): int
    {
        return (int)Auth::guard('web')->id();
    }

    protected function getAssignedUserIdFilter(): ?int
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return null;
        }
        if ($user->role === 'admin' || (int)($user->is_all_assigned ?? 1) === 1) {
            return null;
        }
        return (int)$user->id;
    }

    /**
     * 1. 購入取引一覧画面 (buy/index)
     */
    public function index(Request $request)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $page           = (int)($request->get('page', 1));
        $perPage        = 10;

        $filters = [
            'status'  => $request->get('status'),
            'keyword' => trim((string)$request->get('keyword', '')),
        ];

        $data = Transaction::getBuyerPaginatedList($companyId, $filters, $perPage, $page, $assignedUserId);
        $data['title']      = '購入 (買手) 取引一覧';
        $data['filters']    = $filters;
        $data['activeMenu'] = 'buy';

        return view('tenant.buy.index', $data);
    }

    /**
     * 2. 購入取引詳細画面 (buy/detail)
     */
    public function detail(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'buyer') {
            abort(403, '指定された取引データが存在しないか、閲覧権限がありません。');
        }

        $items       = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();
        $attachments = TransactionAttachment::getAttachmentsMetaByTransactionId($id);
        $comments    = TransactionComment::getCommentsWithAuthors($id);
        $histories   = TransactionHistory::getHistoriesWithUser($id);

        $data = [
            'title'       => "取引詳細 #{$id} - {$tx['title']}",
            'tx'          => $tx,
            'items'       => $items,
            'attachments' => $attachments,
            'comments'    => $comments,
            'histories'   => $histories,
            'myCompanyId' => $companyId,
            'myUserId'    => $this->getMyUserId(),
            'activeMenu'  => 'buy',
        ];

        if (in_array($tx['status'], ['estimate_declined', 'order_declined'], true)) {
            return view('tenant.buy.detail_declined', $data);
        }

        return view('tenant.buy.detail', $data);
    }

    /**
     * 3. 新規見積依頼作成フォーム (buy/estimate/new)
     */
    public function newEstimate()
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();

        $partnerLinks = CompanyLink::getLinksForCompany($companyId, [
            'my_role' => 'buyer',
            'status'  => 'approved',
        ], $assignedUserId);

        $data = [
            'title'        => '新規見積依頼作成',
            'partnerLinks' => $partnerLinks,
            'activeMenu'   => 'buy',
        ];

        return view('tenant.buy.estimate_new', $data);
    }

    /**
     * 4. 見積依頼確認画面 (buy/estimate/confirm)
     */
    public function confirmEstimate(Request $request)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $postData       = $request->all();

        $selectedLinkIds = (array)($postData['link_ids'] ?? []);
        if (empty($selectedLinkIds)) {
            return redirect()->back()->withInput()->with('error', '見積依頼先の販売企業を1社以上選択してください。');
        }

        // 担当権限チェック
        foreach ($selectedLinkIds as $linkId) {
            if (!CompanyLink::canUserAccessLink((int)$linkId, $companyId, $assignedUserId)) {
                return redirect()->back()->withInput()->with('error', '担当外の企業が含まれています。');
            }
        }

        $items = (array)($postData['items'] ?? []);
        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', '明細を1行以上入力してください。');
        }

        // 一時添付ファイルの処理
        $tempAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $tempAttachments[] = [
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                        'file_size' => $file->getSize(),
                        'file_data' => base64_encode(file_get_contents($file->getRealPath())),
                    ];
                }
            }
        }
        session(['temp_estimate_attachments' => $tempAttachments]);

        $allPartners = CompanyLink::getLinksForCompany($companyId, [
            'my_role' => 'buyer',
            'status'  => 'approved',
        ], $assignedUserId);
        $selectedPartners = array_filter($allPartners, fn($p) => in_array((int)$p['link_id'], array_map('intval', $selectedLinkIds), true));

        $data = [
            'title'            => '見積依頼内容の確認',
            'txData'           => $postData,
            'selectedLinkIds'  => $selectedLinkIds,
            'selectedPartners' => $selectedPartners,
            'items'            => $items,
            'uploadedFiles'    => $tempAttachments,
            'activeMenu'       => 'buy',
        ];

        return view('tenant.buy.estimate_confirm', $data);
    }

    /**
     * 5. 見積依頼送信実行 (buy/estimate/create)
     */
    public function createEstimate(Request $request)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $postData       = $request->all();

        $linkIds = (array)($postData['link_ids'] ?? []);
        $items   = (array)($postData['items'] ?? []);

        foreach ($linkIds as $linkId) {
            if (!CompanyLink::canUserAccessLink((int)$linkId, $companyId, $assignedUserId)) {
                return redirect('/buy/estimate/new')->withInput()->with('error', '担当外の企業への依頼は送信できません。');
            }
        }

        $attachments = [];
        $tempAttachments = session('temp_estimate_attachments', []);
        foreach ($tempAttachments as $t) {
            $attachments[] = [
                'file_name' => $t['file_name'],
                'mime_type' => $t['mime_type'],
                'file_size' => $t['file_size'],
                'file_data' => base64_decode($t['file_data']),
            ];
        }
        session()->forget('temp_estimate_attachments');

        try {
            $createdTxIds = $this->transactionService->createEstimateRequests(
                $companyId,
                $userId,
                $linkIds,
                $postData,
                $items,
                $attachments
            );

            session()->flash('created_tx_ids', $createdTxIds);
            return redirect('/buy/estimate/complete');
        } catch (\Exception $e) {
            return redirect('/buy/estimate/new')->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * 6. 見積依頼完了画面 (buy/estimate/complete)
     */
    public function completeEstimate()
    {
        $createdTxIds = session('created_tx_ids', []);
        $data = [
            'title'        => '見積依頼完了',
            'createdTxIds' => $createdTxIds,
            'activeMenu'   => 'buy',
        ];
        return view('tenant.buy.estimate_complete', $data);
    }

    /**
     * 7. 新規直接注文作成フォーム (buy/order/new)
     */
    public function newDirectOrder()
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $partnerLinks   = CompanyLink::getLinksForCompany($companyId, [
            'my_role' => 'buyer',
            'status'  => 'approved',
        ], $assignedUserId);

        $data = [
            'title'        => '新規注文（直接発注）作成',
            'partnerLinks' => $partnerLinks,
            'activeMenu'   => 'buy',
        ];

        return view('tenant.buy.order_new', $data);
    }

    /**
     * 8. 直接注文確認画面 (buy/order/confirm)
     */
    public function confirmDirectOrder(Request $request)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $postData       = $request->all();
        $linkId         = (int)($postData['company_link_id'] ?? 0);
        $items          = (array)($postData['items'] ?? []);

        if ($linkId <= 0) {
            return redirect()->back()->withInput()->with('error', '発注先企業を選択してください。');
        }

        if (!CompanyLink::canUserAccessLink($linkId, $companyId, $assignedUserId)) {
            return redirect()->back()->withInput()->with('error', '担当外の企業への発注はできません。');
        }

        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', '注文明細を1行以上入力してください。');
        }

        $tempAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $tempAttachments[] = [
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                        'file_size' => $file->getSize(),
                        'file_data' => base64_encode(file_get_contents($file->getRealPath())),
                    ];
                }
            }
        }
        session(['temp_order_attachments' => $tempAttachments]);

        $allPartners = CompanyLink::getLinksForCompany($companyId, [
            'my_role' => 'buyer',
            'status'  => 'approved',
        ], $assignedUserId);
        $selectedPartner = null;
        foreach ($allPartners as $p) {
            if ((int)$p['link_id'] === $linkId) {
                $selectedPartner = $p;
                break;
            }
        }

        $data = [
            'title'           => '注文内容の確認',
            'txData'          => $postData,
            'linkId'          => $linkId,
            'selectedPartner' => $selectedPartner,
            'items'           => $items,
            'uploadedFiles'   => $tempAttachments,
            'activeMenu'      => 'buy',
        ];

        return view('tenant.buy.order_confirm', $data);
    }

    /**
     * 9. 直接注文送信実行 (buy/order/create)
     */
    public function createDirectOrder(Request $request)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $postData       = $request->all();

        $linkId = (int)($postData['company_link_id'] ?? 0);
        $items  = (array)($postData['items'] ?? []);

        if (!CompanyLink::canUserAccessLink($linkId, $companyId, $assignedUserId)) {
            return redirect('/buy/order/new')->withInput()->with('error', '担当外の企業への発注はできません。');
        }

        $attachments = [];
        $tempAttachments = session('temp_order_attachments', []);
        foreach ($tempAttachments as $t) {
            $attachments[] = [
                'file_name' => $t['file_name'],
                'mime_type' => $t['mime_type'],
                'file_size' => $t['file_size'],
                'file_data' => base64_decode($t['file_data']),
            ];
        }
        session()->forget('temp_order_attachments');

        try {
            $txId = $this->transactionService->createDirectOrder(
                $companyId,
                $userId,
                $linkId,
                $postData,
                $items,
                $attachments
            );

            session()->flash('created_tx_id', $txId);
            return redirect('/buy/order/complete');
        } catch (\Exception $e) {
            return redirect('/buy/order/new')->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * 10. 直接注文完了画面 (buy/order/complete)
     */
    public function completeDirectOrder()
    {
        $txId = session('created_tx_id');
        $data = [
            'title'      => '注文完了',
            'txId'       => $txId,
            'activeMenu' => 'buy',
        ];
        return view('tenant.buy.order_complete', $data);
    }

    /**
     * 11. 見積回答経由の注文確認画面 (buy/order-confirm/{id} / buy/order/estimate/{id})
     */
    public function confirmOrderFromEstimate(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'buyer' || $tx['status'] !== 'estimate_replied') {
            return redirect('/buy')->with('error', '指定された取引データが存在しないか、見積回答済みの取引のみ注文確認へ進めます。');
        }

        $items = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();

        $data = [
            'title'      => "注文確認 #{$id} (見積回答受領後)",
            'tx'         => $tx,
            'items'      => $items,
            'activeMenu' => 'buy',
        ];

        return view('tenant.buy.order_confirm_estimate', $data);
    }

    /**
     * 12. 見積回答経由の注文送信実行 (buy/order-place/{id} / buy/order/estimate/{id})
     */
    public function placeOrderFromEstimate(Request $request, int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $remarks        = $request->input('buyer_remarks');

        if (!Transaction::canAccess($id, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        try {
            $this->transactionService->placeOrderFromEstimate($id, $companyId, $userId, $remarks);
            return redirect("/buy/order-complete/{$id}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 13. 見積回答経由の注文完了画面 (buy/order-complete/{id})
     */
    public function completeOrderFromEstimate(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        $data = [
            'title'      => '発注完了',
            'tx'         => $tx,
            'activeMenu' => 'buy',
        ];

        return view('tenant.buy.order_complete_estimate', $data);
    }

    /**
     * 14. 取引メッセージ・コメント投稿 (buy/comment/{id})
     */
    public function postComment(Request $request, int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $comment        = trim((string)$request->input('comment', ''));

        if (!Transaction::canAccess($id, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        if ($comment !== '') {
            TransactionComment::create([
                'transaction_id' => $id,
                'company_id'     => $companyId,
                'user_id'        => $userId,
                'comment'        => $comment,
            ]);

            // 相手先関係者へのメール通知
            $this->notificationService->notifyNewComment($id, $userId, $comment);
        }

        return redirect("/buy/detail/{$id}#comments");
    }

    /**
     * 15. 取引メッセージ・コメント削除 (buy/comment/delete/{id})
     */
    public function deleteComment(int $commentId)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();

        $comment = TransactionComment::find($commentId);
        if (!$comment) {
            abort(404, '指定されたコメントが存在しません。');
        }

        $txId = (int)$comment->transaction_id;

        if (!Transaction::canAccess($txId, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        if ((int)$comment->user_id !== $userId) {
            abort(403, 'ご自身が投稿したメッセージのみ削除できます。');
        }

        $comment->delete();

        return redirect("/buy/detail/{$txId}#comments")->with('success', 'メッセージを削除しました。');
    }

    /**
     * 16. 添付ファイルダウンロード配信 (buy/attachment/{id})
     */
    public function downloadAttachment(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $attachment     = TransactionAttachment::getAttachmentBinary($id);

        if (!$attachment || !Transaction::canAccess((int)$attachment['transaction_id'], $companyId, $assignedUserId)) {
            abort(404, '添付ファイルが見つかりません。');
        }

        return response($attachment['file_data'], 200, [
            'Content-Type'        => $attachment['mime_type'] ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($attachment['file_name']) . '"',
            'Content-Length'      => (string)strlen($attachment['file_data']),
        ]);
    }
}
