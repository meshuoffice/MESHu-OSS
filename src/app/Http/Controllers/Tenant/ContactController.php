<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\ContactAutoReplyMail;
use App\Mail\ContactInquiryMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContactController extends Controller
{
    protected array $contactTypes = [
        'operation' => 'システムの操作方法・使い方の相談',
        'link'      => '取引先との連携・申請・承認に関するお問い合わせ',
        'trouble'   => 'エラー・不具合のご報告',
        'account'   => 'アカウント・権限・登録情報の変更について',
        'system'    => '機能・仕様に関するお問い合わせ',
        'other'     => 'その他事務局へのお問い合わせ',
    ];

    /**
     * お問い合わせ入力画面
     */
    public function index(Request $request): View
    {
        $user    = Auth::guard('web')->user();
        $company = $user->company;

        $tempData = session('contact_form_data', []);

        return view('contact.index', [
            'activeMenu'   => 'contact',
            'company'      => $company,
            'user'         => $user,
            'contactTypes' => $this->contactTypes,
            'formData'     => $tempData,
        ]);
    }

    /**
     * お問い合わせ確認画面（バリデーション & 添付ファイル一時保存）
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        $user    = Auth::guard('web')->user();
        $company = $user->company;

        $request->validate([
            'contact_type'  => ['required', 'in:operation,link,trouble,account,system,other'],
            'subject'       => ['required', 'string', 'max:255'],
            'message'       => ['required', 'string', 'max:5000'],
            'agree_privacy' => ['required'],
            'attachment'    => ['nullable', 'file', 'max:10240', 'mimes:png,jpg,jpeg,pdf,zip'],
        ], [
            'contact_type.required'  => 'お問い合わせ種別を選択してください。',
            'contact_type.in'        => '正しいお問い合わせ種別を選択してください。',
            'subject.required'       => '件名を入力してください。',
            'subject.max'            => '件名は255文字以内で入力してください。',
            'message.required'       => 'お問い合わせ内容を入力してください。',
            'message.max'            => 'お問い合わせ内容は5000文字以内で入力してください。',
            'agree_privacy.required' => 'プライバシーポリシーへの同意が必要です。',
            'attachment.max'         => '添付ファイルは10MB以下にしてください。',
            'attachment.mimes'       => '対応しているファイル形式（PNG, JPG, PDF, ZIP）を選択してください。',
        ]);

        $contactType        = $request->input('contact_type');
        $subject            = trim((string)$request->input('subject'));
        $message            = trim((string)$request->input('message'));
        $existingData       = session('contact_form_data', []);
        $tempAttachmentPath = $existingData['temp_attachment_path'] ?? '';
        $originalFileName   = $existingData['original_file_name'] ?? '';
        $tempFileMime       = $existingData['temp_file_mime'] ?? '';

        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            if (!empty($tempAttachmentPath) && Storage::disk('local')->exists($tempAttachmentPath)) {
                Storage::disk('local')->delete($tempAttachmentPath);
            }
            $file               = $request->file('attachment');
            $originalFileName   = $file->getClientOriginalName();
            $tempFileMime       = $file->getClientMimeType() ?: 'application/octet-stream';
            $tempAttachmentPath = $file->store('temp_contact', 'local');
        }

        $formData = [
            'contact_type'         => $contactType,
            'subject'              => $subject,
            'message'              => $message,
            'agree_privacy'        => '1',
            'temp_attachment_path' => $tempAttachmentPath,
            'original_file_name'   => $originalFileName,
            'temp_file_mime'       => $tempFileMime,
        ];

        session(['contact_form_data' => $formData]);

        return view('contact.confirm', [
            'activeMenu'       => 'contact',
            'company'          => $company,
            'user'             => $user,
            'contactType'      => $contactType,
            'contactTypeName'  => $this->contactTypes[$contactType] ?? 'その他',
            'subject'          => $subject,
            'message'          => $message,
            'originalFileName' => $originalFileName,
            'hasAttachment'    => !empty($originalFileName),
        ]);
    }

    /**
     * お問い合わせ送信処理
     */
    public function send(): RedirectResponse
    {
        $user    = Auth::guard('web')->user();
        $company = $user->company;

        $formData = session('contact_form_data');
        if (!$formData) {
            return redirect()->route('tenant.contact')->with('error', 'セッションの有効期限が切れました。もう一度ご入力ください。');
        }

        $contactType        = $formData['contact_type'] ?? 'other';
        $subject            = $formData['subject'] ?? '';
        $message            = $formData['message'] ?? '';
        $tempAttachmentPath = $formData['temp_attachment_path'] ?? '';
        $originalFileName   = $formData['original_file_name'] ?? '';
        $tempFileMime       = $formData['temp_file_mime'] ?? '';
        $hasAttachment      = !empty($tempAttachmentPath) && Storage::disk('local')->exists($tempAttachmentPath);
        $fullAttachmentPath = $hasAttachment ? Storage::disk('local')->path($tempAttachmentPath) : null;

        $contactTypeName = $this->contactTypes[$contactType] ?? 'その他';
        $adminEmail      = config('mail.office.main');
        $officeSub       = config('mail.office.sub');

        // 1. 事務局宛て通知メール
        try {
            $inquiryMail = new ContactInquiryMail(
                company: $company,
                user: $user,
                contactTypeName: $contactTypeName,
                contactSubject: $subject,
                contentMessage: $message,
                attachmentPath: $fullAttachmentPath,
                originalFileName: $originalFileName,
                mimeType: $tempFileMime
            );

            $mailable = Mail::to($adminEmail);
            if (!empty($officeSub)) {
                $mailable->bcc($officeSub);
            }
            $mailable->send($inquiryMail);
        } catch (\Throwable $e) {
            Log::error('Failed to send contact inquiry to admin: ' . $e->getMessage());
        }

        // 2. ユーザー宛て自動返信メール
        try {
            Mail::to($user->email)->send(new ContactAutoReplyMail(
                company: $company,
                user: $user,
                contactTypeName: $contactTypeName,
                contactSubject: $subject,
                contentMessage: $message
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send contact auto-reply to user: ' . $e->getMessage());
        }

        // 添付ファイルのクリーンアップ
        if ($hasAttachment) {
            Storage::disk('local')->delete($tempAttachmentPath);
        }

        session()->forget('contact_form_data');

        return redirect()->route('tenant.contact.complete');
    }

    /**
     * お問い合わせ完了画面
     */
    public function complete(): View
    {
        $user    = Auth::guard('web')->user();
        $company = $user->company;

        return view('contact.complete', [
            'activeMenu' => 'contact',
            'company'    => $company,
            'user'       => $user,
        ]);
    }
}
