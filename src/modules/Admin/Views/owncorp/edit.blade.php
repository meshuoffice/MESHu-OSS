@extends('admin::layouts.admin')

@section('title', $title ?? ($company->name . ' 編集 | MESHu 事務局管理'))

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.index') }}" class="text-decoration-none">企業管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.show', ['id' => $company->id]) }}" class="text-decoration-none">企業詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">企業編集</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">企業編集</h1>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong>入力内容にエラーがあります。</strong> 下記の項目をご確認ください。
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<!-- フォームエリア -->
<div class="card card-custom bg-white max-width-800 mx-auto">
    <div class="card-header bg-light py-3 border-0">
        <h5 class="m-0 fw-bold">企業情報変更</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.corp.confirm') }}" method="POST" class="h-adr" novalidate>
            @csrf
            <input type="hidden" name="id" value="{{ $company->id }}">
            <input type="hidden" class="p-country-name" value="Japan">
            
            <!-- 企業基本情報 -->
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">基本情報</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="name" class="form-label small fw-bold">企業名・法人名 <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name" required value="{{ old('name', $company->name) }}">
                    @if ($errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="abbreviated_kana" class="form-label small fw-bold">企業名カナ（全角） <span class="text-danger">*</span></label>
                    <input type="text" name="abbreviated_kana" class="form-control {{ $errors->has('abbreviated_kana') ? 'is-invalid' : '' }}" id="abbreviated_kana" required pattern="^[^\u3041-\u3096\u4E00-\u9FFF\u3400-\u4DBF]+$" title="ひらがな・漢字は入力できません。カタカナ等で入力してください。" value="{{ old('abbreviated_kana', $company->abbreviated_kana) }}">
                    <div class="form-text small text-muted">※ひらがな・漢字は入力できません。</div>
                    @if ($errors->has('abbreviated_kana'))<div class="invalid-feedback">{{ $errors->first('abbreviated_kana') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="abbreviated_name" class="form-label small fw-bold">法人名略称 <span class="text-danger">*</span></label>
                    <input type="text" name="abbreviated_name" class="form-control {{ $errors->has('abbreviated_name') ? 'is-invalid' : '' }}" id="abbreviated_name" required value="{{ old('abbreviated_name', $company->abbreviated_name) }}">
                    @if ($errors->has('abbreviated_name'))<div class="invalid-feedback">{{ $errors->first('abbreviated_name') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="sub_name" class="form-label small fw-bold">拠点名・区分名</label>
                    <input type="text" name="sub_name" class="form-control {{ $errors->has('sub_name') ? 'is-invalid' : '' }}" id="sub_name" value="{{ old('sub_name', $company->sub_name) }}">
                    @if ($errors->has('sub_name'))<div class="invalid-feedback">{{ $errors->first('sub_name') }}</div>@endif
                </div>

                <div class="col-12 col-md-6">
                    <label for="url_code" class="form-label small fw-bold">専用ログインURL識別子 (`url_code`) <span class="text-danger">*</span></label>
                    <input type="text" name="url_code" class="form-control font-monospace {{ $errors->has('url_code') ? 'is-invalid' : '' }}" id="url_code" required value="{{ old('url_code', $company->url_code) }}">
                    @if ($errors->has('url_code'))<div class="invalid-feedback">{{ $errors->first('url_code') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="corporate_number" class="form-label small fw-bold">13桁 法人番号</label>
                    <input type="text" name="corporate_number" class="form-control font-monospace {{ $errors->has('corporate_number') ? 'is-invalid' : '' }}" id="corporate_number" maxlength="13" value="{{ old('corporate_number', $company->corporate_number) }}">
                    @if ($errors->has('corporate_number'))<div class="invalid-feedback">{{ $errors->first('corporate_number') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="invoice_number" class="form-label small fw-bold">適格請求書登録番号 (T番号)</label>
                    <input type="text" name="invoice_number" class="form-control font-monospace {{ $errors->has('invoice_number') ? 'is-invalid' : '' }}" id="invoice_number" maxlength="14" placeholder="例: T1234567890123" value="{{ old('invoice_number', $company->invoice_number) }}">
                    @if ($errors->has('invoice_number'))<div class="invalid-feedback">{{ $errors->first('invoice_number') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="tel" class="form-label small fw-bold">電話番号 <span class="text-danger">*</span></label>
                    <input type="tel" name="tel" class="form-control font-monospace {{ $errors->has('tel') ? 'is-invalid' : '' }}" id="tel" required value="{{ old('tel', $company->tel) }}">
                    @if ($errors->has('tel'))<div class="invalid-feedback">{{ $errors->first('tel') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="homepage" class="form-label small fw-bold">ホームページURL</label>
                    <input type="url" name="homepage" class="form-control {{ $errors->has('homepage') ? 'is-invalid' : '' }}" id="homepage" value="{{ old('homepage', $company->homepage) }}">
                    @if ($errors->has('homepage'))<div class="invalid-feedback">{{ $errors->first('homepage') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label small fw-bold">稼働ステータス <span class="text-danger">*</span></label>
                    <select name="status" class="form-select {{ $errors->has('status') ? 'is-invalid' : '' }}" id="status">
                        <option value="active" {{ old('status', $company->status) === 'active' ? 'selected' : '' }}>稼働 (有効)</option>
                        <option value="suspended" {{ old('status', $company->status) === 'suspended' ? 'selected' : '' }}>停止中</option>
                    </select>
                    @if ($errors->has('status'))<div class="invalid-feedback">{{ $errors->first('status') }}</div>@endif
                </div>
            </div>

            <!-- 所在地情報 -->
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">所在地情報</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label for="zip_code" class="form-label small fw-bold">郵便番号 <span class="text-danger">*</span></label>
                    <input type="text" name="zip_code" class="form-control font-monospace p-postal-code {{ $errors->has('zip_code') ? 'is-invalid' : '' }}" id="zip_code" required value="{{ old('zip_code', $company->zip_code) }}">
                    @if ($errors->has('zip_code'))<div class="invalid-feedback">{{ $errors->first('zip_code') }}</div>@endif
                </div>
                <div class="col-12 col-md-4">
                    <label for="prefecture_code" class="form-label small fw-bold">都道府県 <span class="text-danger">*</span></label>
                    <select name="prefecture_code" class="form-select p-region {{ $errors->has('prefecture_code') ? 'is-invalid' : '' }}" id="prefecture_code" required>
                        <option value="">選択してください</option>
                        @foreach ($prefectures as $code => $pName)
                            <option value="{{ $code }}" {{ (string)old('prefecture_code', $company->prefecture_code) === (string)$code ? 'selected' : '' }}>{{ $pName }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('prefecture_code'))<div class="invalid-feedback">{{ $errors->first('prefecture_code') }}</div>@endif
                </div>
                <div class="col-12 col-md-4">
                    <label for="city" class="form-label small fw-bold">市区町村 <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control p-locality {{ $errors->has('city') ? 'is-invalid' : '' }}" id="city" required value="{{ old('city', $company->city) }}">
                    @if ($errors->has('city'))<div class="invalid-feedback">{{ $errors->first('city') }}</div>@endif
                </div>
                <div class="col-12">
                    <label for="address" class="form-label small fw-bold">番地・建物名など <span class="text-danger">*</span></label>
                    <input type="text" name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" id="address" required value="{{ old('address', $company->address) }}">
                    @if ($errors->has('address'))<div class="invalid-feedback">{{ $errors->first('address') }}</div>@endif
                </div>
            </div>

            <!-- 管理設定 (バッジ) -->
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">管理設定</h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="grant_badge" value="1" id="grant_badge" {{ old('grant_badge', (!empty($badge) && $badge->status === 'active') ? '1' : '0') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="grant_badge">
                            <i class="bi bi-star-fill text-warning me-1"></i>MESHu認証バッジを付与する
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.corp.show', ['id' => $company->id]) }}" class="btn btn-outline-secondary">詳細に戻る</a>
                <button type="submit" class="btn btn-dark px-4">確認へ進む <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

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
