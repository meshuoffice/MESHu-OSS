@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.notice.index') }}" class="text-decoration-none">お知らせ管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.notice.show', $notice->id) }}" class="text-decoration-none">お知らせ詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ編集</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ編集</h1>
    </div>
</div>

<!-- フォームエリア -->
<div class="card card-custom bg-white mx-auto" style="max-width: 860px;">
    <div class="card-header bg-light py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold">掲載内容編集</h5>
        <span class="small font-monospace text-muted">ID: NT-{{ sprintf('%04d', $notice->id) }}</span>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.notice.confirm_update', $notice->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label for="noticeDate" class="form-label small fw-bold">掲載開始日時 <span class="text-danger">*</span></label>
                    @php
                        $publishedAtVal = $notice->published_at 
                            ? $notice->published_at->format('Y-m-d\TH:i') 
                            : now()->format('Y-m-d\TH:i');
                    @endphp
                    <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" id="noticeDate" name="published_at" required value="{{ old('published_at', $publishedAtVal) }}">
                    @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="noticeCategory" class="form-label small fw-bold">カテゴリ <span class="text-danger">*</span></label>
                    <select class="form-select @error('category') is-invalid @enderror" id="noticeCategory" name="category" required>
                        <option value="important" {{ old('category', $notice->category) === 'important' ? 'selected' : '' }}>重要</option>
                        <option value="maintenance" {{ old('category', $notice->category) === 'maintenance' ? 'selected' : '' }}>メンテナンス</option>
                        <option value="info" {{ old('category', $notice->category) === 'info' ? 'selected' : '' }}>お知らせ</option>
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="noticeTitle" class="form-label small fw-bold">タイトル <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" id="noticeTitle" name="title" required value="{{ old('title', $notice->title) }}">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">開示対象範囲 <span class="text-danger">*</span></label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="target_scope" id="targetAll" value="all" {{ old('target_scope', $notice->target_scope) === 'all' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="targetAll">全員</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="target_scope" id="targetVerified" value="verified" {{ old('target_scope', $notice->target_scope) === 'verified' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="targetVerified">認証企業のみ</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="target_scope" id="targetUnverified" value="unverified" {{ old('target_scope', $notice->target_scope) === 'unverified' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="targetUnverified">非認証企業のみ</label>
                    </div>
                </div>
                @error('target_scope')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="noticeBody" class="form-label small fw-bold">本文 <span class="text-danger">*</span></label>
                <textarea class="form-control @error('content') is-invalid @enderror" id="noticeBody" name="content" rows="10" required>{{ old('content', $notice->content) }}</textarea>
                @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <!-- 添付ファイル管理 -->
            <div class="mb-4">
                <label class="form-label small fw-bold">添付ファイル</label>
                
                @if ($notice->attachments->isNotEmpty())
                    @php $attachment = $notice->attachments->first(); @endphp
                    <div class="p-3 bg-light rounded border mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-arrow-down text-primary fs-5"></i>
                            <span class="fw-semibold small">{{ $attachment->file_name }}</span>
                            <span class="text-muted small">({{ number_format($attachment->file_size / 1024, 1) }} KB)</span>
                        </div>
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" name="delete_attachment" value="1" id="deleteAttachment">
                            <label class="form-check-label small text-danger fw-bold" for="deleteAttachment">
                                <i class="bi bi-trash me-1"></i>添付ファイルを削除する
                            </label>
                        </div>
                    </div>
                    <label for="noticeAttachment" class="form-label small text-muted">ファイルを差し替える場合は新しいファイルを選択してください（上限10MB）</label>
                @else
                    <div class="text-muted small mb-2">現在添付ファイルはありません。</div>
                @endif
                
                <input class="form-control form-control-sm @error('attachment_file') is-invalid @enderror" type="file" id="noticeAttachment" name="attachment_file">
                @error('attachment_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_important" id="noticeImportantFlag" value="1" {{ old('is_important', $notice->is_important) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-bold text-danger" for="noticeImportantFlag">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>重要フラグを付与する（ダッシュボード最上部に固定表示）
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold">公開状態 <span class="text-danger">*</span></label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="statusPublished" value="published" {{ old('status', $notice->is_published ? 'published' : 'draft') === 'published' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="statusPublished">公開する</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="statusDraft" value="draft" {{ old('status', $notice->is_published ? 'published' : 'draft') === 'draft' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="statusDraft">下書き保存（非公開）</label>
                    </div>
                </div>
                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.notice.show', $notice->id) }}" class="btn btn-outline-secondary">キャンセル</a>
                <button type="submit" class="btn btn-dark px-4">確認へ進む <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
