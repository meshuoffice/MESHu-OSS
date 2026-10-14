<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionComment;
use App\Models\TransactionHistory;
use App\Models\TransactionItem;
use App\Services\TransactionNotificationService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellController extends Controller
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
     * 1. 販売取引一覧画面 (sell/index)
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

        $data = Transaction::getSellerPaginatedList($companyId, $filters, $perPage, $page, $assignedUserId);
        $data['title']      = '販売 (売手) 取引一覧';
        $data['filters']    = $filters;
        $data['activeMenu'] = 'sell';

        return view('tenant.sell.index', $data);
    }

    /**
     * 2. 販売取引詳細画面 (sell/detail)
     */
    public function detail(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'seller') {
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
            'activeMenu'  => 'sell',
        ];

        return view('tenant.sell.detail', $data);
    }

    /**
     * 3. 明細・金額編集画面 (sell/edit)
     */
    public function edit(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'seller') {
            abort(403, '取引データが存在しないか、閲覧権限がありません。');
        }

        // ロック判定
        if (in_array($tx['status'], ['order_accepted', 'estimate_declined', 'order_declined'], true)) {
            return redirect("/sell/detail/{$id}")->with('error', 'この取引は完了または終了しているため編集できません。');
        }

        $items         = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();
        $attachments   = TransactionAttachment::getAttachmentsMetaByTransactionId($id);
        $sellerCompany = Company::find($companyId);

        $data = [
            'title'         => "明細・金額編集 #{$id}",
            'tx'            => $tx,
            'items'         => $items,
            'attachments'   => $attachments,
            'sellerCompany' => $sellerCompany,
            'activeMenu'    => 'sell',
        ];

        return view('tenant.sell.edit', $data);
    }

    /**
     * 4. 明細・金額編集の下書き保存実行 (sell/edit/{id} / sell/save/{id})
     */
    public function saveEdit(Request $request, int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();

        if (!Transaction::canAccess($id, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        $title = trim((string)$request->input('title', ''));
        if ($title === '') {
            return redirect()->back()
                ->withInput()
                ->withErrors(['title' => '件名 / 案件名は必須項目です。']);
        }
        if (mb_strlen($title) > 255) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['title' => '件名 / 案件名は255文字以内で入力してください。']);
        }

        $request->validate([
            'title' => 'required|string|max:255',
        ], [
            'title.required' => '件名 / 案件名は必須項目です。',
            'title.max'      => '件名 / 案件名は255文字以内で入力してください。',
        ]);

        $postData            = $request->all();
        $items               = (array)($postData['items'] ?? []);
        $deleteAttachmentIds = (array)($postData['delete_attachments'] ?? []);

        // 新規ファイルアップロード
        $newAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $newAttachments[] = [
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                        'file_size' => $file->getSize(),
                        'file_data' => file_get_contents($file->getRealPath()),
                    ];
                }
            }
        }

        try {
            $this->transactionService->saveSellerEdit(
                $id,
                $companyId,
                $userId,
                $postData,
                $items,
                $newAttachments,
                $deleteAttachmentIds
            );

            // アクション分岐
            if ($request->input('action') === 'to_reply') {
                return redirect("/sell/reply/{$id}");
            }
            if ($request->input('action') === 'to_accept') {
                return redirect("/sell/order-accept/{$id}");
            }

            return redirect("/sell/edit/{$id}")->with('success', '明細および金額を下書き保存しました。');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * 5. 見積回答確認画面 (sell/reply/{id})
     */
    public function replyConfirm(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'seller') {
            abort(403, '取引データが存在しないか、閲覧権限がありません。');
        }

        $items = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();

        $data = [
            'title'      => "見積回答の確認 #{$id}",
            'tx'         => $tx,
            'items'      => $items,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.estimate_reply', $data);
    }

    /**
     * 6. 見積回答送信実行 (sell/reply/{id})
     */
    public function submitReply(Request $request, int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $remarks        = $request->input('seller_remarks');

        if (!Transaction::canAccess($id, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        try {
            $this->transactionService->submitEstimateReply($id, $companyId, $userId, $remarks);
            return redirect("/sell/reply-complete/{$id}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 7. 見積回答完了画面 (sell/reply-complete/{id})
     */
    public function completeReply(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        $data = [
            'title'      => '見積回答完了',
            'tx'         => $tx,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.estimate_reply_complete', $data);
    }

    /**
     * 8. 注文回答（受注承諾）確認画面 (sell/order-accept/{id})
     */
    public function acceptConfirm(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'seller') {
            abort(403, '指定された取引データが存在しないか、閲覧権限がありません。');
        }
        if ($tx['status'] !== 'order_placed') {
            return redirect('/sell')->with('error', '受注確認が可能なステータスではありません。');
        }

        $items = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();

        $data = [
            'title'      => "注文回答（受注承諾）の確認 #{$id}",
            'tx'         => $tx,
            'items'      => $items,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.order_accept', $data);
    }

    /**
     * 9. 受注承諾送信実行 (sell/order-accept/{id})
     */
    public function acceptOrder(Request $request, int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $remarks        = $request->input('accept_remarks');

        if (!Transaction::canAccess($id, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        try {
            $this->transactionService->acceptOrder($id, $companyId, $userId, $remarks);
            return redirect("/sell/order-accept-complete/{$id}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 10. 受注承諾完了画面 (sell/order-accept-complete/{id})
     */
    public function completeAccept(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        $data = [
            'title'      => '受注承諾完了（取引成立）',
            'tx'         => $tx,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.order_accept_complete', $data);
    }

    /**
     * 11. 見積辞退確認画面 (sell/estimate-decline/{id})
     */
    public function declineEstimateConfirm(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'seller') {
            abort(403, '取引データが存在しないか、閲覧権限がありません。');
        }

        $items = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();

        $data = [
            'title'      => "見積辞退の確認 #{$id}",
            'tx'         => $tx,
            'items'      => $items,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.estimate_decline', $data);
    }

    /**
     * 12. 見積辞退送信実行 (sell/estimate-decline/{id})
     */
    public function submitDeclineEstimate(Request $request, int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $category       = (string)$request->input('decline_category');
        $reason         = (string)$request->input('decline_reason_detail');

        if (!Transaction::canAccess($id, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        try {
            $this->transactionService->declineEstimate($id, $companyId, $userId, $category, $reason);
            return redirect("/sell/estimate-decline-complete/{$id}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 13. 見積辞退完了画面 (sell/estimate-decline-complete/{id})
     */
    public function completeDeclineEstimate(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        $data = [
            'title'      => '見積辞退完了',
            'tx'         => $tx,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.estimate_decline_complete', $data);
    }

    /**
     * 14. 注文辞退確認画面 (sell/order-decline/{id})
     */
    public function declineOrderConfirm(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        if (!$tx || $tx['my_role'] !== 'seller') {
            abort(403, '取引データが存在しないか、閲覧権限がありません。');
        }

        $items = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();

        $data = [
            'title'      => "注文辞退の確認 #{$id}",
            'tx'         => $tx,
            'items'      => $items,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.order_decline', $data);
    }

    /**
     * 15. 注文辞退送信実行 (sell/order-decline/{id})
     */
    public function submitDeclineOrder(Request $request, int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $userId         = $this->getMyUserId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $category       = (string)$request->input('order_decline_category');
        $reason         = (string)$request->input('order_decline_reason_detail');

        if (!Transaction::canAccess($id, $companyId, $assignedUserId)) {
            abort(403, '権限がありません。');
        }

        try {
            $this->transactionService->declineOrder($id, $companyId, $userId, $category, $reason);
            return redirect("/sell/order-decline-complete/{$id}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 16. 注文辞退完了画面 (sell/order-decline-complete/{id})
     */
    public function completeDeclineOrder(int $id)
    {
        $companyId      = $this->getMyCompanyId();
        $assignedUserId = $this->getAssignedUserIdFilter();
        $tx             = Transaction::getTransactionDetail($id, $companyId, $assignedUserId);

        $data = [
            'title'      => '注文辞退完了',
            'tx'         => $tx,
            'activeMenu' => 'sell',
        ];

        return view('tenant.sell.order_decline_complete', $data);
    }

    /**
     * 17. 取引メッセージ・コメント投稿 (sell/comment/{id})
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

        return redirect("/sell/detail/{$id}#comments");
    }

    /**
     * 18. 取引メッセージ・コメント削除 (sell/comment/delete/{id})
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

        return redirect("/sell/detail/{$txId}#comments")->with('success', 'メッセージを削除しました。');
    }

    /**
     * 19. 添付ファイルダウンロード配信 (sell/attachment/{id})
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
