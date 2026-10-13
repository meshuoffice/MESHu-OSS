@extends('layouts.tenant')

@section('title', 'お知らせ内容確認 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.notice.manage.index') }}" class="text-decoration-none">お知らせ配信管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ内容確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ内容確認</h1>
    </div>
</div>

    <!-- 確認カード -->
    <div class="card card-custom bg-white border-0 shadow-sm">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold">以下の内容で登録・配信を確定します</h5>
            @if (($postData['is_published'] ?? '') === '1')
                <span class="badge bg-success-subtle text-success">ステータス: 公開</span>
            @else
                <span class="badge bg-secondary-subtle text-secondary">ステータス: 下書き</span>
            @endif
        </div>
        <div class="card-body p-4">
            
            @if (($postData['is_published'] ?? '') === '1')
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"></i>
                    <div>
                        確定ボタンを押すと、<strong>つながり（MESHu）連携企業の全ユーザーへ即時に配信・公開</strong>されます。内容に誤りがないかご確認ください。
                    </div>
                </div>
            @else
                <div class="alert alert-secondary d-flex align-items-center gap-2 mb-4 small" role="alert">
                    <i class="bi bi-info-circle-fill text-secondary fs-5 flex-shrink-0"></i>
                    <div>
                        「下書き」として保存されます。他企業へはまだ配信・公開されません。
                    </div>
                </div>
            @endif

            <table class="table table-bordered align-middle mb-4">
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">配信先</th>
                    <td>
                        <span class="badge bg-primary-subtle text-primary">つながり承認企業（連携取引先）の全ユーザー</span>
                    </td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">タイトル</th>
                    <td class="fw-bold fs-6">{{ $postData['title'] }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold align-top pt-3">本文</th>
                    <td style="white-space: pre-wrap; line-height: 1.7;">{{ $postData['content'] }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">添付ファイル</th>
                    <td>
                        @if (!empty($attachmentInfo))
                            <div class="d-flex align-items-center gap-2 text-primary fw-semibold">
                                <i class="bi bi-paperclip fs-5"></i>
                                <span>{{ $attachmentInfo['file_name'] }}</span>
                                <span class="text-muted small">({{ number_format(round($attachmentInfo['file_size'] / 1024)) }} KB - 新規アップロード)</span>
                            </div>
                        @elseif (!empty($existingNotice) && $existingNotice->attachments->isNotEmpty() && empty($postData['delete_attachment']))
                            <div class="d-flex align-items-center gap-2 text-dark">
                                <i class="bi bi-paperclip fs-5 text-secondary"></i>
                                <span>{{ $existingNotice->attachments->first()->file_name }}</span>
                                <span class="text-muted small">({{ number_format(round($existingNotice->attachments->first()->file_size / 1024)) }} KB - 既存のファイルを保持)</span>
                            </div>
                        @elseif (!empty($postData['delete_attachment']) && $postData['delete_attachment'] === '1')
                            <span class="text-danger small"><i class="bi bi-trash me-1"></i>既存の添付ファイルを削除します</span>
                        @else
                            <span class="text-muted small">なし</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">公開ステータス</th>
                    <td>
                        @if (($postData['is_published'] ?? '') === '1')
                            <span class="badge bg-success-subtle text-success">公開</span>（保存後直ちに配信）
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">下書き</span>（非公開）
                        @endif
                    </td>
                </tr>
            </table>

            <!-- 送信フォーム -->
            @php
                $actionUrl = ($mode === 'edit')
                    ? route('tenant.notice.manage.update', $existingNotice->id)
                    : route('tenant.notice.manage.create');
            @endphp
            <form action="{{ $actionUrl }}" method="POST">
                @csrf
                <input type="hidden" name="title" value="{{ $postData['title'] }}">
                <input type="hidden" name="content" value="{{ $postData['content'] }}">
                <input type="hidden" name="is_published" value="{{ $postData['is_published'] }}">
                @if (!empty($postData['delete_attachment']))
                    <input type="hidden" name="delete_attachment" value="{{ $postData['delete_attachment'] }}">
                @endif

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary px-4" onclick="history.back();">修正する</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">
                        @if (($postData['is_published'] ?? '') === '1')
                            <i class="bi bi-send-fill me-1"></i>この内容で公開・配信する
                        @else
                            <i class="bi bi-file-earmark-check-fill me-1"></i>下書きとして保存する
                        @endif
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection
