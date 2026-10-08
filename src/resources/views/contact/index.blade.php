@extends('layouts.tenant')

@section('title', '事務局へのお問い合わせ - MESHu')

@section('styles')
<style>
    .step-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
        gap: 1rem;
    }
    .step-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #94a3b8;
        font-size: 0.875rem;
        font-weight: 600;
    }
    .step-item.active {
        color: #2563eb;
    }
    .step-num {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    .step-item.active .step-num {
        background-color: #2563eb;
        color: #ffffff;
    }
</style>
@endsection

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
            <li class="breadcrumb-item active" aria-current="page">事務局へのお問い合わせ</li>
        </ol>
    </nav>
    <h1 class="h3 m-0 fw-bold text-dark">
        <i class="bi bi-headset me-2 text-primary"></i>事務局へのお問い合わせ
    </h1>
    <p class="text-muted small mb-0 mt-1">
        MESHuプラットフォームの操作方法、連携、その他ご不明な点がございましたらお気軽にお問い合わせください。
    </p>
</div>

<!-- ステップインジケータ -->
<div class="step-indicator">
    <div class="step-item active">
        <div class="step-num">1</div>
        <span>入力</span>
    </div>
    <div class="step-item">
        <i class="bi bi-chevron-right text-muted small"></i>
    </div>
    <div class="step-item">
        <div class="step-num">2</div>
        <span>確認</span>
    </div>
    <div class="step-item">
        <i class="bi bi-chevron-right text-muted small"></i>
    </div>
    <div class="step-item">
        <div class="step-num">3</div>
        <span>完了</span>
    </div>
</div>

<div class="row g-4">
    <!-- 左側：お問い合わせ入力フォーム -->
    <div class="col-12 col-lg-8">
        <div class="card card-custom bg-white p-4">
            <form action="{{ route('tenant.contact.confirm') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- 送信元情報（自動補完固定） -->
                <div class="p-3 bg-light rounded mb-4 border">
                    <div class="row g-2 small text-muted">
                        <div class="col-md-6">
                            <i class="bi bi-building me-1"></i><strong>企業名:</strong> {{ $company->name }}
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-person me-1"></i><strong>送信者:</strong> {{ $user->user_name }} 様 ({{ $user->role === 'admin' ? '管理者' : '一般社員' }})
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-envelope me-1"></i><strong>返信先メール:</strong> {{ $user->email }}
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-telephone me-1"></i><strong>連絡先電話番号:</strong> {{ $company->tel ?: '未登録' }}
                        </div>
                    </div>
                </div>

                @php
                    $selectedType = old('contact_type', $formData['contact_type'] ?? '');
                    $subjectVal   = old('subject', $formData['subject'] ?? '');
                    $messageVal   = old('message', $formData['message'] ?? '');
                @endphp

                <!-- お問い合わせ種別 -->
                <div class="mb-4">
                    <label for="contactType" class="form-label fw-bold text-dark">
                        お問い合わせ種別 <span class="badge bg-danger ms-1" style="font-size: 0.7rem;">必須</span>
                    </label>
                    <select class="form-select @error('contact_type') is-invalid @enderror" id="contactType" name="contact_type" required>
                        <option value="" disabled {{ empty($selectedType) ? 'selected' : '' }}>選択してください</option>
                        @foreach ($contactTypes as $key => $label)
                            <option value="{{ $key }}" {{ $selectedType === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('contact_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 件名 -->
                <div class="mb-4">
                    <label for="subject" class="form-label fw-bold text-dark">
                        件名 <span class="badge bg-danger ms-1" style="font-size: 0.7rem;">必須</span>
                    </label>
                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ $subjectVal }}" placeholder="例：取引先との連携手順について" required>
                    @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- お問い合わせ内容 -->
                <div class="mb-4">
                    <label for="message" class="form-label fw-bold text-dark">
                        お問い合わせ内容 <span class="badge bg-danger ms-1" style="font-size: 0.7rem;">必須</span>
                    </label>
                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" placeholder="具体的にお困りの状況、発生した事象、操作手順などを分かりやすくご記入ください。" required>{{ $messageVal }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text small text-muted">※エラーの場合は、画面に表示されたメッセージや発生時のURLを併せてご記入いただけますとスムーズに回答可能です。</div>
                </div>

                <!-- ファイル添付 (任意) -->
                <div class="mb-4">
                    <label for="attachment" class="form-label fw-bold text-dark">
                        スクリーンショット・参考ファイルの添付 <span class="badge bg-secondary ms-1" style="font-size: 0.7rem;">任意</span>
                    </label>
                    <input type="file" class="form-control @error('attachment') is-invalid @enderror" id="attachment" name="attachment" accept=".png,.jpg,.jpeg,.pdf,.zip">
                    @error('attachment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text small text-muted">対応形式: PNG, JPG, PDF, ZIP (最大10MB)</div>
                    @if (!empty($formData['original_file_name']))
                        <div class="small text-primary mt-1">
                            <i class="bi bi-paperclip me-1"></i>選択中: {{ $formData['original_file_name'] }}（再選択しない場合はこのファイルが保持されます）
                        </div>
                    @endif
                </div>

                <!-- 同意確認 -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input @error('agree_privacy') is-invalid @enderror" type="checkbox" id="agreePrivacy" name="agree_privacy" value="1" {{ (old('agree_privacy') || !empty($formData['agree_privacy'])) ? 'checked' : '' }} required>
                        <label class="form-check-label small text-muted" for="agreePrivacy">
                            プライバシーポリシーに同意し、事務局へ送信します。
                        </label>
                        @error('agree_privacy')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- ボタンエリア -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-arrow-left me-1"></i>キャンセル
                    </a>
                    <button type="submit" class="btn btn-primary px-5 fw-bold">
                        入力内容を確認する <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- 右側：FAQ＆サポート情報 -->
    <div class="col-12 col-lg-4">
        <div class="card card-custom bg-white p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-patch-question me-2 text-primary"></i>よくあるご質問 (FAQ)
            </h5>
            <p class="small text-muted mb-3">
                お問い合わせの前に、以下のサポート情報で解決できる場合がございます。
            </p>
            <div class="list-group list-group-flush border-top border-bottom mb-3">
                <a href="#" class="list-group-item list-group-item-action py-3 px-0 d-flex justify-content-between align-items-center">
                    <span class="small fw-semibold text-dark"><i class="bi bi-question-circle me-2 text-muted"></i>取引先連携の承認待ちが続く場合</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
                <a href="#" class="list-group-item list-group-item-action py-3 px-0 d-flex justify-content-between align-items-center">
                    <span class="small fw-semibold text-dark"><i class="bi bi-question-circle me-2 text-muted"></i>従業員の担当割当の変更手順</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
                <a href="#" class="list-group-item list-group-item-action py-3 px-0 d-flex justify-content-between align-items-center">
                    <span class="small fw-semibold text-dark"><i class="bi bi-question-circle me-2 text-muted"></i>通知メールが届かない場合の確認</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
            </div>
            <a href="#" class="btn btn-outline-primary btn-sm w-100 fw-semibold">
                ヘルプ・操作マニュアルを見る <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
        </div>

        <div class="card card-custom bg-light p-4">
            <h6 class="fw-bold text-dark mb-2">
                <i class="bi bi-clock-history me-2 text-primary"></i>お問い合わせ対応について
            </h6>
            <p class="small text-muted mb-2">
                <strong>受付時間:</strong> 平日 9:00 〜 18:00（土日祝・年末年始を除く）
            </p>
            <p class="small text-muted mb-0">
                ※お問い合わせ受領後、原則1〜2営業日以内にご指定のメールアドレス宛にご回答を差し上げます。
            </p>
        </div>
    </div>
</div>
@endsection
