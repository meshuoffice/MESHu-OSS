<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NoticeController extends Controller
{
    /**
     * 受信お知らせ一覧表示（事務局発信＋つながり承認企業発信＋自社発信）
     */
    public function index(Request $request): View
    {
        $user = Auth::guard('web')->user();
        $company = $user->company;
        if (!$company) {
            abort(403, '所属企業情報が見つかりません。');
        }

        $isAuthenticated = $company->isAuthenticated();

        $keyword     = trim((string)$request->input('keyword', ''));
        $senderType  = (string)$request->input('sender_type', '');
        $isImportant = $request->boolean('is_important');

        $query = Notice::with(['company', 'attachments'])
            ->forCompany($company->id, $isAuthenticated);

        // キーワード検索（タイトル・本文）
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        // 発信元絞り込み
        if ($senderType !== '') {
            $query->where('sender_type', $senderType);
        }

        // 重要フラグ絞り込み
        if ($isImportant) {
            $query->where('is_important', true);
        }

        // 重要フラグ最優先、公開日時・作成日時の降順
        $notices = $query->orderByDesc('is_important')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('tenant.notice.index', [
            'user'        => $user,
            'userRole'    => $user->role,
            'company'     => $company,
            'companyName' => $company->name,
            'userName'    => $user->user_name,
            'activeMenu'  => 'notice',
            'notices'     => $notices,
            'keyword'     => $keyword,
            'senderType'  => $senderType,
            'isImportant' => $isImportant,
        ]);
    }

    /**
     * お知らせ詳細表示（一般企業側閲覧 & 閲覧数インクリメント）
     */
    public function show(int $id): View
    {
        $user = Auth::guard('web')->user();
        $company = $user->company;
        if (!$company) {
            abort(403, '所属企業情報が見つかりません。');
        }

        $isAuthenticated = $company->isAuthenticated();

        $notice = Notice::with(['company', 'createdByAdmin', 'createdByUser', 'attachments'])
            ->forCompany($company->id, $isAuthenticated)
            ->find($id);

        if (!$notice) {
            abort(404, '指定されたお知らせは見つからないか、閲覧権限がありません。');
        }

        // 閲覧数をインクリメント
        $notice->increment('view_count');
        $notice->refresh();

        return view('tenant.notice.show', [
            'user'        => $user,
            'userRole'    => $user->role,
            'company'     => $company,
            'companyName' => $company->name,
            'userName'    => $user->user_name,
            'activeMenu'  => 'notice',
            'notice'      => $notice,
        ]);
    }

    /**
     * 添付ファイルダウンロード
     */
    public function downloadAttachment(int $id): Response
    {
        $user = Auth::guard('web')->user();
        $company = $user->company;
        if (!$company) {
            abort(403, '所属企業情報が見つかりません。');
        }

        $isAuthenticated = $company->isAuthenticated();

        $attachment = NoticeAttachment::findOrFail($id);

        // お知らせ自体の閲覧権限チェック
        $canAccess = Notice::forCompany($company->id, $isAuthenticated)
            ->where('id', $attachment->notice_id)
            ->exists();

        if (!$canAccess) {
            abort(403, 'この添付ファイルをダウンロードする権限がありません。');
        }

        $fileData = is_resource($attachment->file_data)
            ? stream_get_contents($attachment->file_data)
            : $attachment->file_data;

        return response($fileData, 200, [
            'Content-Type'        => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($attachment->file_name) . '"',
            'Content-Length'      => (string)strlen($fileData),
        ]);
    }
}
