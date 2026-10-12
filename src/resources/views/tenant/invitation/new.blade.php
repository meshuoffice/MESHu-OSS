@extends('layouts.tenant')

@section('title', '招待メール作成 | MESHu')

@section('content')
<!-- パンくずリスト & 戻るボタン -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.invitation.index') }}">招待先管理</a></li>
                <li class="breadcrumb-item active">招待メール作成</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">未登録企業の招待メール作成</h1>
    </div>
        <div>
            <a href="{{ route('tenant.invitation.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>招待一覧へ戻る</span>
            </a>
        </div>
    </div>

    <!-- エラー表示 -->
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 自動連携ルールの案内コールアウト -->
    <div class="alert alert-primary border-0 shadow-sm d-flex align-items-start gap-3 mb-4">
        <i class="bi bi-envelope-paper-heart-fill text-primary fs-4 flex-shrink-0 mt-1"></i>
        <div>
            <h6 class="fw-bold mb-1">招待メールからの自動つながり構築機能</h6>
            <p class="small text-muted mb-0">
                招待メールに専用の登録URLが含まれます。招待された企業がそのURLから新規アカウント登録を完了すると、<strong>自社と相手企業との取引連携（パートナーシップ）が自動的に生成</strong>されます。
            </p>
        </div>
    </div>

    <!-- 招待メール作成フォーム -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-send-plus-fill me-2 text-primary"></i>招待メールの送信フォーム</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('tenant.invitation.confirm') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <!-- 招待先 企業名 -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">招待先 企業名 <span class="badge bg-danger">必須</span></label>
                        <input type="text" class="form-control" name="company_name" value="{{ old('company_name') }}" placeholder="例: 株式会社パートナーテクノロジー" required>
                    </div>

                    <!-- 招待先 法人番号 -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">招待先 法人番号 (13桁) <span class="badge bg-secondary">任意</span></label>
                        <input type="text" class="form-control font-monospace {{ $errors->has('corporate_number') ? 'is-invalid' : '' }}" name="corporate_number" value="{{ old('corporate_number') }}" maxlength="13" placeholder="例: 1234567890123 (半角数字13桁)">
                        @if ($errors->has('corporate_number'))
                            <div class="invalid-feedback fw-semibold">{{ $errors->first('corporate_number') }}</div>
                        @endif
                        <div class="form-text small">※事前に相手企業の法人番号が分かっている場合に入力してください。</div>
                    </div>

                    <!-- 招待先 担当者名 -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">招待先 担当者名 <span class="badge bg-secondary">任意</span></label>
                        <input type="text" class="form-control" name="user_name" value="{{ old('user_name') }}" placeholder="例: 田中 太郎 様">
                    </div>

                    <!-- 招待先 メールアドレス -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">招待先 メールアドレス <span class="badge bg-danger">必須</span></label>
                        <input type="email" class="form-control font-monospace" name="email" value="{{ old('email') }}" placeholder="example@partner.co.jp" required>
                    </div>

                    <!-- 登録時の連携属性 -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">登録時の連携属性 <span class="badge bg-danger">必須</span></label>
                        <select class="form-select" name="buysell" required>
                            <option value="buyer" {{ old('buysell', 'buyer') === 'buyer' ? 'selected' : '' }}>自社が「購入側 (買手)」として相手企業から購入する</option>
                            <option value="seller" {{ old('buysell') === 'seller' ? 'selected' : '' }}>自社が「販売側 (売手)」として相手企業へ販売する</option>
                        </select>
                        <div class="form-text small">招待登録完了時、この属性に沿って取引先連携が自動生成されます。</div>
                    </div>

                    <!-- 招待メール件名 -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">招待メール件名 <span class="badge bg-danger">必須</span></label>
                        <input type="text" class="form-control" name="mail_subject" value="{{ old('mail_subject', $defaultSubject) }}" required>
                    </div>

                    <!-- 招待メッセージ本文 -->
                    <div class="col-12">
                        <label class="form-label fw-semibold small">招待メッセージ本文 <span class="badge bg-danger">必須</span></label>
                        <textarea class="form-control" name="mail_detail" rows="6" placeholder="相手企業へのあいさつ文や補足メッセージをご記入ください..." required>{{ old('mail_detail', $defaultDetail) }}</textarea>
                        <div class="form-text small text-muted">※実際の送信メールには、本文の末尾にシステムが自動生成する専用登録URL（一意の招待コード付き）が追記されます。</div>
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('tenant.invitation.index') }}" class="btn btn-outline-secondary px-4">
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
