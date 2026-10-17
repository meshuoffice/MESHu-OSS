@extends('layouts.tenant')

@section('title', 'MESHu - 企業情報メンテナンス（入力）')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">企業情報メンテナンス</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">企業情報メンテナンス</h1>
    </div>
</div>

<!-- 編集フォーム -->
<div class="card card-custom bg-white shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-building-fill me-2 text-primary"></i>企業基本情報の編集</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('tenant.company.confirm', ['url_code' => $company->url_code]) }}" method="POST" novalidate class="h-adr">
            @csrf
            <span class="p-country-name" style="display:none;">Japan</span>

            <!-- 企業名エリア -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold text-dark">企業名 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $company->name) }}" required placeholder="例：株式会社サンプルネクスト">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">法人格（株式会社等）を含めて入力してください。</div>
                </div>
                <div class="col-md-6">
                    <label for="abbreviated_name" class="form-label fw-semibold text-dark">企業名略称 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" class="form-control @error('abbreviated_name') is-invalid @enderror" id="abbreviated_name" name="abbreviated_name" value="{{ old('abbreviated_name', $company->abbreviated_name) }}" required placeholder="例：サンプルネクスト">
                    @error('abbreviated_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">法人格を除いて入力してください。</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="abbreviated_kana" class="form-label fw-semibold text-dark">企業名カナ <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" class="form-control @error('abbreviated_kana') is-invalid @enderror" id="abbreviated_kana" name="abbreviated_kana" value="{{ old('abbreviated_kana', $company->abbreviated_kana) }}" required placeholder="例：カブシキガイシャサンプルネクスト">
                    @error('abbreviated_kana')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">全角カナで入力してください。</div>
                </div>
                <div class="col-md-4">
                    <label for="corporate_number" class="form-label fw-semibold text-dark">法人番号 <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                    <input type="text" class="form-control font-monospace @error('corporate_number') is-invalid @enderror" id="corporate_number" name="corporate_number" value="{{ old('corporate_number', $company->corporate_number) }}" placeholder="例：1234567890123" maxlength="13">
                    @error('corporate_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">半角数字13桁で入力してください。</div>
                </div>
                <div class="col-md-4">
                    <label for="invoice_number" class="form-label fw-semibold text-dark">適格請求書登録番号 (T番号) <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                    <input type="text" class="form-control font-monospace @error('invoice_number') is-invalid @enderror" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $company->invoice_number) }}" placeholder="例：T1234567890123" maxlength="14">
                    @error('invoice_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">T＋半角数字13桁（帳票に印字されます）。</div>
                </div>
            </div>

            <!-- 専用URLコードエリア -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label for="url_code" class="form-label fw-semibold text-dark">専用URLコード <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <div class="input-group">
                        <span class="input-group-text text-muted font-monospace small bg-light">{{ rtrim(url('/'), '/') }}/</span>
                        <input type="text" class="form-control font-monospace @error('url_code') is-invalid @enderror" id="url_code" name="url_code" value="{{ old('url_code', $company->url_code) }}" required placeholder="例：sample-next" pattern="^[a-zA-Z0-9_-]+$">
                        <span class="input-group-text text-muted font-monospace small bg-light">/login</span>
                        @error('url_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text text-muted">半角英数字、ハイフン(-)、アンダースコア(_)で自社専用のログインURLを設定します。</div>
                </div>
            </div>

            <!-- 連絡先エリア -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="tel" class="form-label fw-semibold text-dark">電話番号 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="tel" class="form-control @error('tel') is-invalid @enderror" id="tel" name="tel" value="{{ old('tel', $company->tel) }}" required placeholder="例：03-1234-5678">
                    @error('tel')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">ハイフンを含めて入力してください。</div>
                </div>
                <div class="col-md-4">
                    <label for="homepage" class="form-label fw-semibold text-dark">ホームページ <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                    <input type="url" class="form-control font-monospace @error('homepage') is-invalid @enderror" id="homepage" name="homepage" value="{{ old('homepage', $company->homepage) }}" placeholder="例：https://example.co.jp">
                    @error('homepage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">URL（https://...）を入力してください。</div>
                </div>
                <div class="col-md-4">
                    <label for="zip_code" class="form-label fw-semibold text-dark">郵便番号 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" class="form-control p-postal-code @error('zip_code') is-invalid @enderror" id="zip_code" name="zip_code" value="{{ old('zip_code', $company->zip_code) }}" required placeholder="例：100-0001" maxlength="8">
                    @error('zip_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">数字入力で都道府県・市区町村を自動補完します。</div>
                </div>
            </div>

            <!-- 住所エリア -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="prefecture_code" class="form-label fw-semibold text-dark">都道府県 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <select class="form-select p-region-id @error('prefecture_code') is-invalid @enderror" id="prefecture_code" name="prefecture_code" required>
                        <option value="">選択してください</option>
                        @foreach ($prefectures as $code => $prefName)
                            <option value="{{ $code }}" {{ (int)old('prefecture_code', $company->prefecture_code) === $code ? 'selected' : '' }}>{{ $prefName }}</option>
                        @endforeach
                    </select>
                    @error('prefecture_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="city" class="form-label fw-semibold text-dark">市区町村 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" class="form-control p-locality p-street-address @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $company->city) }}" required placeholder="例：千代田区千代田">
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-5">
                    <label for="address" class="form-label fw-semibold text-dark">番地建物 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $company->address) }}" required placeholder="例：1-1 サンプルビル 5F">
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- 取引・税・端数処理設定エリア -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="tax_rate" class="form-label fw-semibold text-dark">標準消費税率 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control text-end font-monospace @error('tax_rate') is-invalid @enderror" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $company->tax_rate ?? 10) }}" min="0" max="100" required placeholder="10">
                        <span class="input-group-text">%</span>
                        @error('tax_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text text-muted">見積・取引時のデフォルト消費税率（半角数字）</div>
                </div>
                <div class="col-md-4">
                    <label for="amount_rounding" class="form-label fw-semibold text-dark">金額端数処理 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <select class="form-select @error('amount_rounding') is-invalid @enderror" id="amount_rounding" name="amount_rounding" required>
                        @foreach ($roundingTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('amount_rounding', $company->amount_rounding ?? 'round') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('amount_rounding')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">単価×数量計算時の端数処理（円未満）</div>
                </div>
                <div class="col-md-4">
                    <label for="tax_rounding" class="form-label fw-semibold text-dark">消費税端数処理 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <select class="form-select @error('tax_rounding') is-invalid @enderror" id="tax_rounding" name="tax_rounding" required>
                        @foreach ($roundingTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('tax_rounding', $company->tax_rounding ?? 'round') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('tax_rounding')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">消費税額計算時の端数処理（円未満）</div>
                </div>
            </div>

            <!-- ボタンエリア -->
            <div class="d-flex justify-content-between align-items-center border-top pt-4 mt-4">
                <a href="{{ route('tenant.settings') }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary px-5 fw-semibold shadow-sm">
                    確認画面へ進む <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 危険ゾーン（退会手続き）: 管理者のみ表示 -->
@if (Auth::user() && Auth::user()->role === 'admin')
    <div class="card border-danger-subtle bg-white mt-5 shadow-sm">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="text-danger fw-bold mb-1">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>MESHuの退会手続き
                </h5>
                <p class="text-muted small mb-0">
                    自社企業のアカウントを一時停止し、MESHuプラットフォームからの退会申請を行います。
                </p>
            </div>
            <a href="{{ route('tenant.company.withdraw', ['url_code' => $company->url_code]) }}" class="btn btn-outline-danger px-3 fw-semibold">
                退会手続きへ進む <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    </div>
@endif
@endsection

@section('scripts')
<!-- 郵便番号自動住所補完スクリプト (事務局・CI4共通仕様) -->
<script src="https://yubinbango.github.io/yubinbango/yubinbango.js" charset="UTF-8" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const zipInput = document.getElementById('zip_code');
        const prefSelect = document.getElementById('prefecture_code');
        const cityInput = document.getElementById('city');

        if (!zipInput || !prefSelect || !cityInput) return;

        function searchZipcode(zip) {
            const cleanedZip = zip.replace(/[^0-9]/g, '');
            if (cleanedZip.length !== 7) return;

            fetch('https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + cleanedZip)
                .then(response => response.json())
                .then(data => {
                    if (data && data.results && data.results[0]) {
                        const res = data.results[0];
                        if (res.prefcode) {
                            prefSelect.value = parseInt(res.prefcode, 10);
                        }
                        if (res.address2 || res.address3) {
                            cityInput.value = (res.address2 || '') + (res.address3 || '');
                        }
                    }
                })
                .catch(err => console.error('郵便番号検索エラー:', err));
        }

        zipInput.addEventListener('input', function () {
            searchZipcode(this.value);
        });
        zipInput.addEventListener('change', function () {
            searchZipcode(this.value);
        });
    });
</script>
@endsection
