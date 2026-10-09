<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNoticeController extends Controller
{
    /**
     * 書き込み権限チェック（viewerは閲覧のみ）
     */
    private function checkWriteAccess(): ?RedirectResponse
    {
        $currentOwner = Auth::guard('admin')->user();
        if (!$currentOwner) {
            return redirect()->route('admin.login');
        }

        if ($currentOwner->role === 'viewer') {
            return redirect()->route('admin.notice.index')
                ->with('error', '閲覧専用アカウントのため、この操作を実行する権限がありません。');
        }
        return null;
    }

    /**
     * お知らせ一覧表示・検索
     */
    public function index(Request $request): View
    {
        $keyword     = trim((string)$request->input('keyword', ''));
        $category    = $request->input('category');
        $targetScope = $request->input('target_scope');
        $status      = $request->input('status');
        $senderType  = $request->input('sender_type');

        $query = Notice::with(['company', 'createdByAdmin', 'createdByUser', 'attachments']);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($targetScope)) {
            $query->where('target_scope', $targetScope);
        }

        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        if (!empty($senderType)) {
            $query->where('sender_type', $senderType);
        }

        $query->orderBy('is_important', 'desc')
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc');

        $perPage = 15;
        $notices = $query->paginate($perPage)->withQueryString();

        return view('admin::ownnotice.index', [
            'title'       => 'お知らせ管理 | MESHu 事務局管理',
            'activeMenu'  => 'ownnotice',
            'notices'     => $notices,
            'keyword'     => $keyword,
            'category'    => $category,
            'targetScope' => $targetScope,
            'status'      => $status,
            'senderType'  => $senderType,
        ]);
    }

    /**
     * 新規作成フォーム表示
     */
    public function registration(): View|RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $formData = session('ownnotice_form_data', []);

        return view('admin::ownnotice.registration', [
            'title'      => 'お知らせ作成 | MESHu 事務局管理',
            'activeMenu' => 'ownnotice',
            'formData'   => $formData,
        ]);
    }

    /**
     * 新規登録 入力確認画面表示
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $post = $request->all();

        $rules = [
            'title'           => ['required', 'string', 'max:255'],
            'category'        => ['required', Rule::in(['important', 'maintenance', 'info'])],
            'target_scope'    => ['required', Rule::in(['all', 'verified', 'unverified'])],
            'content'         => ['required', 'string'],
            'published_at'    => ['required', 'date'],
            'status'          => ['required', Rule::in(['published', 'draft'])],
            'attachment_file' => ['nullable', 'file', 'max:10240'], // 10MB
        ];

        $messages = [
            'title.required'        => 'タイトルは必須項目です。',
            'category.required'     => 'カテゴリを選択してください。',
            'target_scope.required' => '開示対象範囲を選択してください。',
            'content.required'      => '本文は必須項目です。',
            'published_at.required' => '掲載開始日時を入力してください。',
            'status.required'       => '公開状態を選択してください。',
            'attachment_file.max'   => '添付ファイルのサイズは10MB以下にしてください。',
        ];

        $validator = Validator::make($post, $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('error', '入力内容に不備があります。確認してください。');
        }

        $file = $request->file('attachment_file');
        $attachmentMeta = null;

        if ($file && $file->isValid()) {
            $attachmentMeta = [
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize(),
                'data_b64'  => base64_encode(file_get_contents($file->getRealPath())),
            ];
        }

        $formData = [
            'title'        => $post['title'],
            'category'     => $post['category'],
            'target_scope' => $post['target_scope'],
            'content'      => $post['content'],
            'is_important' => !empty($post['is_important']),
            'is_published' => $post['status'] === 'published',
            'published_at' => str_replace('T', ' ', $post['published_at']),
            'attachment'   => $attachmentMeta,
        ];

        session(['ownnotice_confirm_data' => $formData]);

        return view('admin::ownnotice.confirm', [
            'title'      => 'お知らせ登録確認 | MESHu 事務局管理',
            'activeMenu' => 'ownnotice',
            'data'       => $formData,
            'isEdit'     => false,
            'noticeId'   => null,
        ]);
    }

    /**
     * 新規登録 完了処理
     */
    public function complete(Request $request): RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $formData = session('ownnotice_confirm_data');
        if (!$formData) {
            return redirect()->route('admin.notice.registration')->with('error', 'セッションがタイムアウトしました。');
        }

        $adminId = Auth::guard('admin')->id();

        DB::beginTransaction();
        try {
            $notice = Notice::create([
                'sender_type'         => 'admin',
                'company_id'          => null,
                'created_by_admin_id' => $adminId,
                'created_by_user_id'  => null,
                'category'            => $formData['category'],
                'target_scope'        => $formData['target_scope'],
                'title'               => $formData['title'],
                'content'             => $formData['content'],
                'is_important'        => !empty($formData['is_important']),
                'is_published'        => !empty($formData['is_published']),
                'published_at'        => $formData['published_at'],
            ]);

            if (!empty($formData['attachment']['data_b64'])) {
                NoticeAttachment::create([
                    'notice_id'  => $notice->id,
                    'file_name'  => $formData['attachment']['file_name'],
                    'mime_type'  => $formData['attachment']['mime_type'],
                    'file_size'  => $formData['attachment']['file_size'],
                    'file_data'  => base64_decode($formData['attachment']['data_b64']),
                    'created_at' => now(),
                ]);
            }

            DB::commit();
            session()->forget(['ownnotice_confirm_data', 'ownnotice_form_data']);

            return redirect()->route('admin.notice.complete.show', ['id' => $notice->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.notice.registration')->with('error', 'お知らせの登録に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 登録・更新 完了画面表示 (PRGパターン)
     */
    public function completeShow(Request $request, int|string $id): View
    {
        $id = (int)$id;
        $isEdit = $request->input('mode') === 'edit';

        return view('admin::ownnotice.complete', [
            'title'      => ($isEdit ? 'お知らせ更新完了' : 'お知らせ登録完了') . ' | MESHu 事務局管理',
            'activeMenu' => 'ownnotice',
            'noticeId'   => $id,
            'isEdit'     => $isEdit,
        ]);
    }

    /**
     * お知らせ詳細表示
     */
    public function show(int|string $id): View|RedirectResponse
    {
        $id = (int)$id;
        $notice = Notice::with(['company', 'createdByAdmin', 'createdByUser', 'attachments'])->find($id);

        if (!$notice) {
            return redirect()->route('admin.notice.index')->with('error', '該当のお知らせが見つかりません。');
        }

        return view('admin::ownnotice.show', [
            'title'      => 'お知らせ詳細 | MESHu 事務局管理',
            'activeMenu' => 'ownnotice',
            'notice'     => $notice,
        ]);
    }

    /**
     * お知らせ編集画面
     */
    public function edit(int|string $id): View|RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $id = (int)$id;
        $notice = Notice::with('attachments')->find($id);

        if (!$notice) {
            return redirect()->route('admin.notice.index')->with('error', '該当のお知らせが見つかりません。');
        }

        return view('admin::ownnotice.edit', [
            'title'      => 'お知らせ編集 | MESHu 事務局管理',
            'activeMenu' => 'ownnotice',
            'notice'     => $notice,
        ]);
    }

    /**
     * お知らせ編集 入力確認画面表示
     */
    public function confirmUpdate(Request $request, int|string $id): View|RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $id = (int)$id;
        $notice = Notice::with('attachments')->find($id);
        if (!$notice) {
            return redirect()->route('admin.notice.index')->with('error', '該当のお知らせが見つかりません。');
        }

        $post = $request->all();

        $rules = [
            'title'           => ['required', 'string', 'max:255'],
            'category'        => ['required', Rule::in(['important', 'maintenance', 'info'])],
            'target_scope'    => ['required', Rule::in(['all', 'verified', 'unverified'])],
            'content'         => ['required', 'string'],
            'published_at'    => ['required', 'date'],
            'status'          => ['required', Rule::in(['published', 'draft'])],
            'attachment_file' => ['nullable', 'file', 'max:10240'],
        ];

        $messages = [
            'title.required'        => 'タイトルは必須項目です。',
            'category.required'     => 'カテゴリを選択してください。',
            'target_scope.required' => '開示対象範囲を選択してください。',
            'content.required'      => '本文は必須項目です。',
            'published_at.required' => '掲載開始日時を入力してください。',
            'status.required'       => '公開状態を選択してください。',
            'attachment_file.max'   => '添付ファイルのサイズは10MB以下にしてください。',
        ];

        $validator = Validator::make($post, $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('error', '入力内容に不備があります。確認してください。');
        }

        $deleteAttachment = !empty($post['delete_attachment']) && $post['delete_attachment'] === '1';
        $file = $request->file('attachment_file');
        $attachmentMeta = null;
        $isNewAttachment = false;
        $existingAttachment = $notice->attachments->first();

        if ($file && $file->isValid()) {
            $attachmentMeta = [
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize(),
                'data_b64'  => base64_encode(file_get_contents($file->getRealPath())),
            ];
            $isNewAttachment = true;
            $deleteAttachment = false;
        } elseif (!$deleteAttachment && $existingAttachment) {
            $attachmentMeta = [
                'file_name' => $existingAttachment->file_name,
                'mime_type' => $existingAttachment->mime_type ?? 'application/octet-stream',
                'file_size' => $existingAttachment->file_size,
                'data_b64'  => null,
            ];
        }

        $formData = [
            'title'             => $post['title'],
            'category'          => $post['category'],
            'target_scope'      => $post['target_scope'],
            'content'           => $post['content'],
            'is_important'      => !empty($post['is_important']),
            'is_published'      => $post['status'] === 'published',
            'published_at'      => str_replace('T', ' ', $post['published_at']),
            'attachment'        => $attachmentMeta,
            'delete_attachment' => $deleteAttachment,
            'is_new_attachment' => $isNewAttachment,
        ];

        session(['ownnotice_edit_confirm_data_' . $id => $formData]);

        return view('admin::ownnotice.confirm', [
            'title'      => 'お知らせ編集確認 | MESHu 事務局管理',
            'activeMenu' => 'ownnotice',
            'data'       => $formData,
            'isEdit'     => true,
            'noticeId'   => $id,
        ]);
    }

    /**
     * お知らせ更新処理（セッションデータ確定）
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $id = (int)$id;
        $notice = Notice::find($id);
        if (!$notice) {
            return redirect()->route('admin.notice.index')->with('error', '該当のお知らせが見つかりません。');
        }

        $formData = session('ownnotice_edit_confirm_data_' . $id);
        if (!$formData) {
            return redirect()->route('admin.notice.edit', ['id' => $id])->with('error', 'セッションがタイムアウトしました。');
        }

        DB::beginTransaction();
        try {
            $notice->update([
                'category'     => $formData['category'],
                'target_scope' => $formData['target_scope'],
                'title'        => $formData['title'],
                'content'      => $formData['content'],
                'is_important' => !empty($formData['is_important']),
                'is_published' => !empty($formData['is_published']),
                'published_at' => $formData['published_at'],
            ]);

            if (!empty($formData['delete_attachment'])) {
                NoticeAttachment::where('notice_id', $id)->delete();
            }

            if (!empty($formData['is_new_attachment']) && !empty($formData['attachment']['data_b64'])) {
                NoticeAttachment::where('notice_id', $id)->delete();
                NoticeAttachment::create([
                    'notice_id'  => $id,
                    'file_name'  => $formData['attachment']['file_name'],
                    'mime_type'  => $formData['attachment']['mime_type'],
                    'file_size'  => $formData['attachment']['file_size'],
                    'file_data'  => base64_decode($formData['attachment']['data_b64']),
                    'created_at' => now(),
                ]);
            }

            DB::commit();
            session()->forget('ownnotice_edit_confirm_data_' . $id);

            return redirect()->route('admin.notice.complete.show', ['id' => $id, 'mode' => 'edit']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.notice.edit', ['id' => $id])->with('error', 'お知らせの更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 削除確認画面表示
     */
    public function deleteConfirm(int|string $id): View|RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $id = (int)$id;
        $notice = Notice::with(['company', 'createdByAdmin', 'createdByUser', 'attachments'])->find($id);
        if (!$notice) {
            return redirect()->route('admin.notice.index')->with('error', '該当のお知らせが見つかりません。');
        }

        // 安全仕様：公開中の場合は削除不可
        if ($notice->is_published) {
            return redirect()->route('admin.notice.show', ['id' => $id])
                ->with('error', '公開中のお知らせは削除できません。先にステータスを「下書き（非公開）」に変更してください。');
        }

        return view('admin::ownnotice.delete', [
            'title'      => 'お知らせ削除確認 | MESHu 事務局管理',
            'activeMenu' => 'ownnotice',
            'notice'     => $notice,
        ]);
    }

    /**
     * 削除実行処理（安全仕様：非公開のみ削除可）
     */
    public function delete(int|string $id): RedirectResponse
    {
        if ($redirect = $this->checkWriteAccess()) {
            return $redirect;
        }

        $id = (int)$id;
        $notice = Notice::find($id);
        if (!$notice) {
            return redirect()->route('admin.notice.index')->with('error', '該当のお知らせが見つかりません。');
        }

        if ($notice->is_published) {
            return redirect()->route('admin.notice.show', ['id' => $id])
                ->with('error', '公開中のお知らせは削除できません。ステータスを「下書き（非公開）」にしてから削除してください。');
        }

        $notice->delete();
        return redirect()->route('admin.notice.index')->with('success', 'お知らせを削除しました。');
    }

    /**
     * 添付ファイルダウンロード
     */
    public function downloadAttachment(int|string $id): Response|RedirectResponse
    {
        $id = (int)$id;
        $attachment = NoticeAttachment::find($id);
        if (!$attachment) {
            return redirect()->back()->with('error', '添付ファイルが見つかりません。');
        }

        $fileData = is_resource($attachment->file_data)
            ? stream_get_contents($attachment->file_data)
            : $attachment->file_data;

        return response($fileData)
            ->header('Content-Type', $attachment->mime_type)
            ->header('Content-Disposition', 'attachment; filename="' . rawurlencode($attachment->file_name) . '"');
    }
}
