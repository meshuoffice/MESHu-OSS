<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    protected function getAuthUser(): User
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            abort(401, 'ログインしてください。');
        }
        return $user;
    }

    protected function getCompanyId(): int
    {
        $user = $this->getAuthUser();
        if (!$user->company_id) {
            abort(403, '自社企業情報が見つかりません。');
        }
        return (int)$user->company_id;
    }

    /**
     * 該当連携チャットへのアクセス権限を検証
     */
    protected function canAccessLink(CompanyLink $link, User $user): bool
    {
        $companyId = (int)$user->company_id;

        // 自社が連携の buyer または seller であること
        if ((int)$link->buyer_company_id !== $companyId && (int)$link->seller_company_id !== $companyId) {
            return false;
        }

        // 連携ステータスが承認済み(approved)であること
        if ($link->status !== 'approved') {
            return false;
        }

        // 全担当フラグON または 管理者の場合はアクセス可能
        if ($user->role === 'admin' || (int)$user->is_all_assigned === 1) {
            return true;
        }

        // 個別割当があるかチェック
        return DB::table('user_assigned_links')
            ->where('user_id', $user->id)
            ->where('company_link_id', $link->id)
            ->exists();
    }

    /**
     * チャット一覧・タイムライン表示 (GET /chat/{link_id?})
     */
    public function index(Request $request, ?int $link_id = null)
    {
        $user = $this->getAuthUser();
        $companyId = $this->getCompanyId();
        $isAllAssigned = ($user->role === 'admin' || (int)$user->is_all_assigned === 1);

        // 1. 参加している承認済みのつながり一覧を取得
        $linksQuery = DB::table('company_links as cl')
            ->select([
                'cl.id as link_id',
                'cl.buyer_company_id',
                'cl.seller_company_id',
                'cl.status as link_status',
                'cl.created_at as link_created_at',
                'partner.id as partner_company_id',
                'partner.name as partner_name',
                'partner.abbreviated_name as partner_abbreviated_name',
            ])
            ->join('companies as partner', function ($join) use ($companyId) {
                $join->on('partner.id', '=', DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN cl.seller_company_id ELSE cl.buyer_company_id END"));
            })
            ->where(function ($q) use ($companyId) {
                $q->where('cl.buyer_company_id', $companyId)
                  ->orWhere('cl.seller_company_id', $companyId);
            })
            ->where('cl.status', 'approved')
            ->where('partner.status', 'active');

        // 非全担当者の場合は個別割当のみに絞り込み
        if (!$isAllAssigned) {
            $linksQuery->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                       ->where('ual.user_id', $user->id);
        }

        $linkRows = $linksQuery->get();

        // つながりごとの未読数および最新メッセージを取得
        $linkList = [];
        foreach ($linkRows as $row) {
            $lId = (int)$row->link_id;
            $unreadCount = $this->chatService->getUnreadCount($lId, (int)$user->id, $companyId);

            $lastMsg = ChatMessage::where('company_link_id', $lId)
                ->orderBy('id', 'desc')
                ->first();

            $linkList[] = [
                'link_id'                  => $lId,
                'partner_company_id'       => (int)$row->partner_company_id,
                'partner_name'             => $row->partner_name,
                'partner_abbreviated_name' => $row->partner_abbreviated_name ?: $row->partner_name,
                'unread_count'             => $unreadCount,
                'last_message_body'        => $lastMsg ? $lastMsg->message_body : null,
                'last_message_at'          => $lastMsg ? $lastMsg->created_at : null,
            ];
        }

        // 最新メッセージ順にソート
        usort($linkList, function ($a, $b) {
            $timeA = $a['last_message_at'] ? $a['last_message_at']->timestamp : 0;
            $timeB = $b['last_message_at'] ? $b['last_message_at']->timestamp : 0;
            return $timeB <=> $timeA;
        });

        // 検索・フィルタリング処理
        $keyword = trim((string)$request->input('keyword', ''));
        $filter = $request->input('filter', 'all'); // 'all' | 'unread'
        $totalUnreadCount = count(array_filter($linkList, fn($item) => (int)$item['unread_count'] > 0));

        $filteredList = $linkList;
        if ($keyword !== '') {
            $filteredList = array_values(array_filter($filteredList, function ($item) use ($keyword) {
                return (mb_stripos($item['partner_name'], $keyword) !== false)
                    || (mb_stripos($item['partner_abbreviated_name'], $keyword) !== false);
            }));
        }
        if ($filter === 'unread') {
            $filteredList = array_values(array_filter($filteredList, function ($item) {
                return (int)$item['unread_count'] > 0;
            }));
        }

        // 2. 選択中リンクの特定
        $activeLink = null;
        $activePartner = null;
        $messages = [];

        if ($link_id !== null) {
            $selectedLink = CompanyLink::find($link_id);
            if (!$selectedLink || !$this->canAccessLink($selectedLink, $user)) {
                abort(403, '指定されたチャットルームへのアクセス権限がありません。');
            }
            $activeLink = $selectedLink;
        } elseif (!empty($filteredList)) {
            // パラメータ未指定時はフィルタ後リスト先頭を選択
            $activeLink = CompanyLink::find($filteredList[0]['link_id']);
        } elseif (!empty($linkList)) {
            $activeLink = CompanyLink::find($linkList[0]['link_id']);
        }

        if ($activeLink) {
            $partnerId = ((int)$activeLink->buyer_company_id === $companyId)
                ? (int)$activeLink->seller_company_id
                : (int)$activeLink->buyer_company_id;
            $activePartner = Company::find($partnerId);

            // メッセージ履歴の取得（論理削除済みも含めて時系列順に取得）
            $messages = ChatMessage::withTrashed()
                ->with(['user', 'company', 'attachments'])
                ->where('company_link_id', $activeLink->id)
                ->orderBy('id', 'asc')
                ->get();

            // 閲覧と同時に既読を更新
            $this->chatService->markAsRead($activeLink->id, (int)$user->id);
        }

        return view('tenant.chat.index', [
            'activeMenu'        => 'chat',
            'user'              => $user,
            'myCompanyId'       => $companyId,
            'myUserId'          => (int)$user->id,
            'linkList'          => $filteredList,
            'allLinkCount'      => count($linkList),
            'totalUnreadCount'  => $totalUnreadCount,
            'keyword'           => $keyword,
            'filter'            => $filter,
            'activeLink'        => $activeLink,
            'activePartner'     => $activePartner,
            'messages'          => $messages,
        ]);
    }

    /**
     * メッセージ削除 (POST /chat/message/delete/{message_id})
     */
    public function deleteMessage(int $message_id)
    {
        $user = $this->getAuthUser();
        $companyId = $this->getCompanyId();

        $message = ChatMessage::find($message_id);
        if (!$message) {
            abort(404, 'メッセージが見つかりません。');
        }

        $link = CompanyLink::find($message->company_link_id);
        if (!$link || !$this->canAccessLink($link, $user)) {
            abort(403, 'アクセス権限がありません。');
        }

        // 投稿者本人（自社かつ同一ユーザー）のみ削除可能
        if ((int)$message->company_id !== $companyId || (int)$message->user_id !== (int)$user->id) {
            abort(403, 'ご自身が投稿したメッセージのみ削除できます。');
        }

        $message->delete();

        return back()->with('success', 'メッセージを削除しました。');
    }

    /**
     * メッセージ送信 (POST /chat/{link_id}/message)
     */
    public function sendMessage(Request $request, int $link_id)
    {
        $user = $this->getAuthUser();
        $companyId = $this->getCompanyId();

        $link = CompanyLink::find($link_id);
        if (!$link || !$this->canAccessLink($link, $user)) {
            abort(403, 'メッセージを送信する権限がありません。');
        }

        $request->validate([
            'message_body'  => 'nullable|string|max:5000',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpeg,png,jpg,gif,pdf,txt,csv,xlsx,xls,doc,docx,zip',
        ], [
            'message_body.max'    => 'メッセージは5,000文字以内で入力してください。',
            'attachments.*.max'   => '添付ファイルは1ファイルあたり最大10MBまでです。',
            'attachments.*.mimes' => '許可されていないファイル形式です。',
        ]);

        $messageBody = trim((string)$request->input('message_body', ''));
        $files = $request->file('attachments') ?? [];

        if ($messageBody === '' && empty($files)) {
            return back()->withErrors(['message_body' => 'メッセージ本文または添付ファイルを入力してください。'])->withInput();
        }

        $this->chatService->sendMessage(
            companyLinkId: $link->id,
            companyId: $companyId,
            userId: (int)$user->id,
            messageBody: $messageBody,
            attachments: is_array($files) ? $files : [$files]
        );

        return redirect()->route('tenant.chat.index', ['link_id' => $link->id])
            ->with('success', 'メッセージを送信しました。');
    }

    /**
     * 添付ファイルダウンロード (GET /chat/attachment/{attachment_id})
     */
    public function downloadAttachment(int $attachment_id)
    {
        $user = $this->getAuthUser();
        $attachment = ChatMessageAttachment::getAttachmentBinary($attachment_id);

        if (!$attachment) {
            abort(404, '添付ファイルが見つかりません。');
        }

        $link = CompanyLink::find($attachment['company_link_id']);
        if (!$link || !$this->canAccessLink($link, $user)) {
            abort(403, '添付ファイルへのアクセス権限がありません。');
        }

        return response($attachment['file_data'])
            ->header('Content-Type', $attachment['mime_type'])
            ->header('Content-Disposition', 'attachment; filename="' . rawurlencode($attachment['file_name']) . '"');
    }
}
