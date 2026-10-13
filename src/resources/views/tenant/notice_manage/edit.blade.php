@extends('layouts.tenant')

@section('title', 'お知らせ編集 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.notice.manage.index') }}" class="text-decoration-none">お知らせ配信管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.notice.manage.show', $notice->id) }}" class="text-decoration-none">詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">編集</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ編集</h1>
    </div>
</div>

    <!-- エラー表示 -->
    @if ($errors->any())
        <div class="alert alert-danger mb-4 shadow-sm border-0" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>入力内容に不備があります</h6>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 入力フォームカード -->
    <div class="card card-custom bg-white border-0 shadow-sm">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold">お知らせ内容編集 (NT-{{ str_pad((string)$notice->id, 4, '0', STR_PAD_LEFT) }})</h5>
            <span class="badge bg-secondary-subtle text-secondary">配信先: つながり承認企業のみ</span>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('tenant.notice.manage.confirm_update', $notice->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- 配信対象範囲案内 -->
                <div class="alert alert-light border d-flex align-items-center gap-2 mb-4 text-muted small">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <span>このお知らせは、現在「つながり（MESHu）」で連携されているすべての承認済み取引先企業へ配信されます。</span>
                </div>

                <!-- タイトル -->
                <div class="mb-4">
                    <label for="title" class="form-label fw-bold small text-secondary">お知らせタイトル <span class="badge bg-danger ms-1">必須</span></label>
                    <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title" name="title" value="{{ old('title', $formData['title'] ?? $notice->title) }}" required maxlength="100">
                    <div class="form-text text-muted small">100文字以内で簡潔かつ分かりやすく入力してください。</div>
                </div>

                <!-- 本文 -->
                <div class="mb-4">
                    <label for="content" class="form-label fw-bold small text-secondary">本文 <span class="badge bg-danger ms-1">必須</span></label>
                    <textarea class="form-control font-monospace {{ $errors->has('content') ? 'is-invalid' : '' }}" id="content" name="content" rows="10" required style="line-height: 1.6;">{{ old('content', $formData['content'] ?? $notice->content) }}</textarea>
                    <div class="form-text text-muted small">改行はそのまま画面に反映されます。</div>
                </div>

                <!-- 添付ファイル（1件・最大10MB） -->
                <div class="mb-4">
                    <label for="attachment" class="form-label fw-bold small text-secondary">添付ファイル <span class="badge bg-secondary ms-1">任意</span></label>
                    <div class="input-group">
                        <input type="file" class="form-control {{ $errors->has('attachment') ? 'is-invalid' : '' }}" id="attachment" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                    </div>
                    <div class="form-text text-muted small">対応形式: PDF, Word, Excel, 画像 / 最大容量: 10MB（新しいファイルを指定すると上書きされます）</div>
                    
                    <!-- 既存添付ファイルがある場合のプレビュー -->
                    @if ($notice->attachments->isNotEmpty())
                        @php $firstAttachment = $notice->attachments->first(); @endphp
                        <div class="mt-2 p-2 bg-light rounded border d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2 small">
                                <i class="bi bi-file-earmark text-primary fs-5"></i>
                                <span class="fw-bold">{{ $firstAttachment->file_name }}</span>
                                <span class="text-muted">({{ number_format($firstAttachment->file_size / 1024, 1) }} KB)</span>
                            </div>
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="checkbox" id="delete_attachment" name="delete_attachment" value="1">
                                <label class="form-check-label text-danger small fw-semibold" for="delete_attachment">この添付ファイルを削除する</label>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 公開ステータス設定 -->
                <div class="mb-4 pt-3 border-top">
                    <label class="form-label fw-bold small text-secondary">公開ステータス <span class="badge bg-danger ms-1">必須</span></label>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_published" id="status_published" value="1" {{ old('is_published', $formData['is_published'] ?? ($notice->is_published ? '1' : '0')) === '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="status_published">
                                <span class="badge bg-success-subtle text-success me-1">公開</span> 保存後に配信・公開する
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_published" id="status_draft" value="0" {{ old('is_published', $formData['is_published'] ?? ($notice->is_published ? '1' : '0')) === '0' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="status_draft">
                                <span class="badge bg-secondary-subtle text-secondary me-1">下書き</span> まだ配信・公開しない（後から編集・削除可能）
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ボタンエリア -->
                <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                    <a href="{{ route('tenant.notice.manage.show', $notice->id) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-arrow-left me-1"></i>キャンセル
                    </a>
                    <button type="submit" class="btn btn-primary px-5 fw-semibold shadow-sm">
                        確認画面へ進む <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection
