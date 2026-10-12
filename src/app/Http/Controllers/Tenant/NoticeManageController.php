<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NoticeManageController extends Controller
{
    /**
     * 管理者権限のチェックと所属企業の取得
     */
    protected function authorizeAdmin(): Company
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            abort(401, 'ログインしてください。');
        }

        if ($user->role !== 'admin') {
            abort(403, 'この機能を利用するには管理者権限が必要です。');
        }

        $company = $user->company;
        if (!$company) {
            abort(403, '所属企業情報が見つかりません。');
        }

        return $company;
    }

    /**
     * 1. 自社発信お知らせ一覧（配信管理ポータル）
     */
    public function index(Request $request): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $keyword = $request->input('keyword', '');
        $status  = $request->input('status', '');

        $query = Notice::where('sender_type', 'company')
            ->where('company_id', $company->id)
            ->with(['attachments', 'createdByUser'])
            ->orderBy('created_at', 'desc');

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        $notices = $query->paginate(15)->withQueryString();

        return view('tenant.notice_manage.index', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'notices'        => $notices,
            'keyword'        => $keyword,
            'status'         => $status,
            'total'          => $notices->total(),
            'page'           => $notices->currentPage(),
            'perPage'        => $notices->perPage(),
            'totalPages'     => $notices->lastPage(),
        ]);
    }

    /**
     * 2. 自社発信お知らせ詳細表示（プレビュー・管理）
     */
    public function show(int $id): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $notice = Notice::where('id', $id)
            ->where('company_id', $company->id)
            ->where('sender_type', 'company')
            ->with(['attachments', 'createdByUser'])
            ->first();

        if (!$notice) {
            abort(404, '指定された自社お知らせは見つかりません。');
        }

        return view('tenant.notice_manage.show', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'notice'         => $notice,
        ]);
    }

    /**
     * 3. 新規お知らせ作成フォーム
     */
    public function new(): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $formData = session('formData') ?? [
            'title'        => '',
            'content'      => '',
            'is_published' => '1',
        ];

        return view('tenant.notice_manage.registration', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'formData'       => $formData,
        ]);
    }

    /**
     * 4. 新規作成確認
     */
    public function confirm(Request $request)
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $request->validate([
            'title'        => 'required|string|max:100',
            'content'      => 'required|string',
            'is_published' => 'required|in:0,1',
            'attachment'   => 'nullable|file|max:10240',
        ], [
            'title.required'        => 'お知らせタイトルは必須です。',
            'title.max'             => 'お知らせタイトルは100文字以内で入力してください。',
            'content.required'      => '本文は必須です。',
            'is_published.required' => '公開ステータスは必須です。',
            'attachment.max'        => '添付ファイル容量は最大10MBまでです。',
        ]);

        $postData = $request->only(['title', 'content', 'is_published']);

        $attachmentInfo = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file = $request->file('attachment');
            $fileData = file_get_contents($file->getRealPath());

            session(['temp_notice_manage_attachment' => [
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
                'data' => base64_encode($fileData),
            ]]);

            $attachmentInfo = [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ];
        }

        return view('tenant.notice_manage.confirm', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'postData'       => $postData,
            'attachmentInfo' => $attachmentInfo,
            'mode'           => 'create',
        ]);
    }

    /**
     * 5. 新規登録実行
     */
    public function create(Request $request): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $request->validate([
            'title'        => 'required|string|max:100',
            'content'      => 'required|string',
            'is_published' => 'required|in:0,1',
        ]);

        $isPublished = ($request->input('is_published') === '1');
        $now = now();

        $notice = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $company->id,
            'created_by_admin_id' => null,
            'created_by_user_id'  => $user->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => $request->input('title'),
            'content'             => $request->input('content'),
            'is_important'        => false,
            'is_published'        => $isPublished,
            'view_count'          => 0,
            'published_at'        => $isPublished ? $now : null,
        ]);

        // 添付ファイルの保存
        $tempAttachment = session('temp_notice_manage_attachment');
        if ($tempAttachment && $notice->id) {
            NoticeAttachment::create([
                'notice_id'  => $notice->id,
                'file_name'  => $tempAttachment['name'],
                'mime_type'  => $tempAttachment['mime'],
                'file_size'  => (int)$tempAttachment['size'],
                'file_data'  => base64_decode($tempAttachment['data']),
                'created_at' => $now,
            ]);
            session()->forget('temp_notice_manage_attachment');
        }

        return redirect()->route('tenant.notice.manage.complete', [
            'id'   => $notice->id,
            'mode' => 'create',
        ]);
    }

    /**
     * 6. 編集フォーム表示
     */
    public function edit(int $id): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $notice = Notice::where('id', $id)
            ->where('company_id', $company->id)
            ->where('sender_type', 'company')
            ->with(['attachments'])
            ->first();

        if (!$notice) {
            abort(404, '指定された自社お知らせは見つかりません。');
        }

        $formData = session('formData') ?? [
            'title'        => $notice->title,
            'content'      => $notice->content,
            'is_published' => $notice->is_published ? '1' : '0',
        ];

        return view('tenant.notice_manage.edit', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'notice'         => $notice,
            'formData'       => $formData,
        ]);
    }

    /**
     * 7. 編集確認
     */
    public function confirmUpdate(Request $request, int $id)
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $notice = Notice::where('id', $id)
            ->where('company_id', $company->id)
            ->where('sender_type', 'company')
            ->with(['attachments'])
            ->first();

        if (!$notice) {
            abort(404, '指定された自社お知らせは見つかりません。');
        }

        $request->validate([
            'title'        => 'required|string|max:100',
            'content'      => 'required|string',
            'is_published' => 'required|in:0,1',
            'attachment'   => 'nullable|file|max:10240',
        ], [
            'title.required'        => 'お知らせタイトルは必須です。',
            'title.max'             => 'お知らせタイトルは100文字以内で入力してください。',
            'content.required'      => '本文は必須です。',
            'is_published.required' => '公開ステータスは必須です。',
            'attachment.max'        => '添付ファイル容量は最大10MBまでです。',
        ]);

        $postData = $request->only(['title', 'content', 'is_published', 'delete_attachment']);

        $attachmentInfo = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file = $request->file('attachment');
            $fileData = file_get_contents($file->getRealPath());

            session(['temp_notice_manage_attachment' => [
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
                'data' => base64_encode($fileData),
            ]]);

            $attachmentInfo = [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ];
        }

        return view('tenant.notice_manage.confirm', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'postData'       => $postData,
            'attachmentInfo' => $attachmentInfo,
            'existingNotice' => $notice,
            'mode'           => 'edit',
        ]);
    }

    /**
     * 8. 更新実行
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $company = $this->authorizeAdmin();

        $notice = Notice::where('id', $id)
            ->where('company_id', $company->id)
            ->where('sender_type', 'company')
            ->first();

        if (!$notice) {
            abort(404, '指定された自社お知らせは見つかりません。');
        }

        $request->validate([
            'title'        => 'required|string|max:100',
            'content'      => 'required|string',
            'is_published' => 'required|in:0,1',
        ]);

        $isPublished = ($request->input('is_published') === '1');
        $now = now();
        $publishedAt = $notice->published_at;
        if ($isPublished && empty($publishedAt)) {
            $publishedAt = $now;
        }

        $notice->update([
            'title'        => $request->input('title'),
            'content'      => $request->input('content'),
            'is_published' => $isPublished,
            'published_at' => $publishedAt,
        ]);

        // 添付ファイル削除指示
        if ($request->input('delete_attachment') === '1') {
            NoticeAttachment::where('notice_id', $id)->delete();
        }

        // 新規添付ファイル保存
        $tempAttachment = session('temp_notice_manage_attachment');
        if ($tempAttachment) {
            NoticeAttachment::where('notice_id', $id)->delete();
            NoticeAttachment::create([
                'notice_id'  => $id,
                'file_name'  => $tempAttachment['name'],
                'mime_type'  => $tempAttachment['mime'],
                'file_size'  => (int)$tempAttachment['size'],
                'file_data'  => base64_decode($tempAttachment['data']),
                'created_at' => $now,
            ]);
            session()->forget('temp_notice_manage_attachment');
        }

        return redirect()->route('tenant.notice.manage.complete', [
            'id'   => $id,
            'mode' => 'edit',
        ]);
    }

    /**
     * 9. 完了画面表示
     */
    public function complete(Request $request, int $id): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $notice = Notice::where('id', $id)
            ->where('company_id', $company->id)
            ->where('sender_type', 'company')
            ->first();

        if (!$notice) {
            return view('tenant.notice_manage.index');
        }

        $mode = $request->input('mode', 'create');

        return view('tenant.notice_manage.complete', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'notice'         => $notice,
            'mode'           => $mode,
        ]);
    }

    /**
     * 10. 削除確認画面（下書き時のみ）
     */
    public function deleteConfirm(int $id)
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $notice = Notice::where('id', $id)
            ->where('company_id', $company->id)
            ->where('sender_type', 'company')
            ->with(['attachments'])
            ->first();

        if (!$notice) {
            abort(404, '指定された自社お知らせは見つかりません。');
        }

        // 安全制御：公開中のお知らせは削除不可
        if ($notice->is_published) {
            return redirect()->route('tenant.notice.manage.show', $id)
                ->with('error', '公開中のお知らせは削除できません。下書きに変更してから削除してください。');
        }

        return view('tenant.notice_manage.delete', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'notice'         => $notice,
        ]);
    }

    /**
     * 11. 削除実行（下書き時のみ）
     */
    public function destroy(int $id): RedirectResponse
    {
        $company = $this->authorizeAdmin();

        $notice = Notice::where('id', $id)
            ->where('company_id', $company->id)
            ->where('sender_type', 'company')
            ->first();

        if (!$notice) {
            abort(404, '指定された自社お知らせは見つかりません。');
        }

        // 安全制御：公開中のお知らせは削除不可
        if ($notice->is_published) {
            return redirect()->route('tenant.notice.manage.show', $id)
                ->with('error', '公開中のお知らせは削除できません。下書きに変更してから削除してください。');
        }

        NoticeAttachment::where('notice_id', $id)->delete();
        $notice->delete();

        $code = 'NT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        return redirect()->route('tenant.notice.manage.index')
            ->with('success', "下書きお知らせ（{$code}）を削除しました。");
    }
}
