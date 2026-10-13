@extends('layouts.tenant')

@section('title', 'MESHu - 企業情報メンテナンス（確認）')

@section('styles')
<style>
    .confirm-label {
        font-weight: 700;
        color: var(--color-text-muted, #555);
    }
    .confirm-value {
        padding: 0.375rem 0.75rem;
        background-color: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        min-height: 38px;
        display: flex;
        align-items: center;
    }
</style>
@endsection

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.company.edit', ['url_code' => $currentCompany->url_code]) }}" class="text-decoration-none">企業情報メンテナンス</a></li>
                <li class="breadcrumb-item active" aria-current="page">変更内容確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">変更内容確認</h1>
    </div>
</div>

<!-- 確認メッセージ -->
<div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-info-circle-fill fs-5"></i>
    <div>
        以下の内容で企業情報を更新します。よろしければ「この内容で更新する」ボタンをクリックしてください。
    </div>
</div>

<!-- 確認表示 フォーム -->
<form action="{{ route('tenant.company.update', ['url_code' => $currentCompany->url_code]) }}" method="POST">
    @csrf

    <input type="hidden" name="name" value="{{ $postData['name'] }}">
    <input type="hidden" name="abbreviated_name" value="{{ $postData['abbreviated_name'] }}">
    <input type="hidden" name="abbreviated_kana" value="{{ $postData['abbreviated_kana'] }}">
    <input type="hidden" name="url_code" value="{{ $postData['url_code'] }}">
    <input type="hidden" name="corporate_number" value="{{ $postData['corporate_number'] ?? '' }}">
    <input type="hidden" name="tel" value="{{ $postData['tel'] }}">
    <input type="hidden" name="homepage" value="{{ $postData['homepage'] ?? '' }}">
    <input type="hidden" name="zip_code" value="{{ $postData['zip_code'] }}">
    <input type="hidden" name="prefecture_code" value="{{ $postData['prefecture_code'] }}">
    <input type="hidden" name="city" value="{{ $postData['city'] }}">
    <input type="hidden" name="address" value="{{ $postData['address'] }}">
    <input type="hidden" name="tax_rate" value="{{ $postData['tax_rate'] }}">
    <input type="hidden" name="amount_rounding" value="{{ $postData['amount_rounding'] }}">
    <input type="hidden" name="tax_rounding" value="{{ $postData['tax_rounding'] }}">

    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-building-fill me-2 text-primary"></i>企業基本情報</h5>
        </div>
        <div class="card-body">
            <!-- 企業名 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">企業名</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $postData['name'] }}</div>
                </div>
            </div>

            <!-- 企業名略称 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">企業名略称</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $postData['abbreviated_name'] }}</div>
                </div>
            </div>

            <!-- 企業名カナ -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">企業名カナ</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $postData['abbreviated_kana'] }}</div>
                </div>
            </div>

            <!-- 専用ログインURL -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">専用ログインURL</div>
                <div class="col-md-9">
                    <div class="confirm-value font-monospace">
                        <span class="text-muted">{{ rtrim(url('/'), '/') }}/</span><strong class="text-primary">{{ $postData['url_code'] }}</strong><span class="text-muted">/login</span>
                    </div>
                </div>
            </div>

            <!-- 法人番号 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">法人番号</div>
                <div class="col-md-9">
                    <div class="confirm-value font-monospace">{{ $postData['corporate_number'] ?: '未設定' }}</div>
                </div>
            </div>

            <!-- 電話番号 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">電話番号</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $postData['tel'] }}</div>
                </div>
            </div>

            <!-- ホームページ -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">ホームページ</div>
                <div class="col-md-9">
                    <div class="confirm-value font-monospace">{{ $postData['homepage'] ?: '未設定' }}</div>
                </div>
            </div>

            <!-- 郵便番号 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">郵便番号</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $postData['zip_code'] }}</div>
                </div>
            </div>

            <!-- 都道府県 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">都道府県</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $prefectureName }}</div>
                </div>
            </div>

            <!-- 市区町村 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">市区町村</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $postData['city'] }}</div>
                </div>
            </div>

            <!-- 番地建物 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">番地建物</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $postData['address'] }}</div>
                </div>
            </div>

            <!-- 標準消費税率 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">標準消費税率</div>
                <div class="col-md-9">
                    <div class="confirm-value font-monospace">{{ $postData['tax_rate'] }} %</div>
                </div>
            </div>

            <!-- 金額端数処理 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">金額端数処理</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $amountRoundingLabel }}</div>
                </div>
            </div>

            <!-- 消費税端数処理 -->
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">消費税端数処理</div>
                <div class="col-md-9">
                    <div class="confirm-value">{{ $taxRoundingLabel }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 画面下部ボタン -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4 py-2">
            <i class="bi bi-arrow-left me-1"></i>修正する
        </button>
        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
            この内容で更新する<i class="bi bi-check-lg ms-1"></i>
        </button>
    </div>
</form>
@endsection
