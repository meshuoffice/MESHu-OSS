@extends('admin::layouts.admin')

@section('title', $title ?? '新規企業登録 | MESHu 事務局管理')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.index') }}" class="text-decoration-none">企業管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">新規企業登録</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">新規企業登録</h1>
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
        <h5 class="m-0 fw-bold">企業・管理者情報入力</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.corp.confirm') }}" method="POST" class="h-adr" novalidate>
            @csrf
            
            <!-- 企業基本情報 -->
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">企業基本情報</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="name" class="form-label small fw-bold">企業名・法人名（正式名称） <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name" placeholder="例: 株式会社サンプルネクスト" required value="{{ old('name', $formData['name'] ?? '') }}">
                    @if ($errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="abbreviated_kana" class="form-label small fw-bold">企業名カナ（全角） <span class="text-danger">*</span></label>
                    <input type="text" name="abbreviated_kana" class="form-control {{ $errors->has('abbreviated_kana') ? 'is-invalid' : '' }}" id="abbreviated_kana" placeholder="例: サンプルネクスト" required pattern="^[^\u3041-\u3096\u4E00-\u9FFF\u3400-\u4DBF]+$" title="ひらがな・漢字は入力できません。カタカナ等で入力してください。" value="{{ old('abbreviated_kana', $formData['abbreviated_kana'] ?? '') }}">
                    <div class="form-text small text-muted">※ひらがな・漢字は入力できません。</div>
                    @if ($errors->has('abbreviated_kana'))<div class="invalid-feedback">{{ $errors->first('abbreviated_kana') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="abbreviated_name" class="form-label small fw-bold">法人名略称 <span class="text-danger">*</span></label>
                    <input type="text" name="abbreviated_name" class="form-control {{ $errors->has('abbreviated_name') ? 'is-invalid' : '' }}" id="abbreviated_name" placeholder="例: サンプル" required value="{{ old('abbreviated_name', $formData['abbreviated_name'] ?? '') }}">
                    @if ($errors->has('abbreviated_name'))<div class="invalid-feedback">{{ $errors->first('abbreviated_name') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="sub_name" class="form-label small fw-bold">拠点名・区分名</label>
                    <input type="text" name="sub_name" class="form-control" id="sub_name" placeholder="例: 東京本社、大阪支店" value="{{ old('sub_name', $formData['sub_name'] ?? '') }}">
                    <div class="form-text small text-muted">※同一企業で複数拠点を登録する場合に入力</div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="url_code" class="form-label small fw-bold">専用ログインURL識別子 (URLコード) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text small text-muted">{{ url('/') }}/</span>
                        <input type="text" name="url_code" class="form-control font-monospace {{ $errors->has('url_code') ? 'is-invalid' : '' }}" id="url_code" placeholder="sample-corp" required value="{{ old('url_code', $formData['url_code'] ?? '') }}">
                    </div>
                    <div class="form-text small text-muted">※半角英数字・ハイフン3文字以上（後から変更不可）</div>
                    @if ($errors->has('url_code'))<div class="invalid-feedback d-block">{{ $errors->first('url_code') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="corporate_number" class="form-label small fw-bold">13桁 法人番号</label>
                    <input type="text" name="corporate_number" class="form-control font-monospace {{ $errors->has('corporate_number') ? 'is-invalid' : '' }}" id="corporate_number" placeholder="例: 1234567890123" maxlength="13" value="{{ old('corporate_number', $formData['corporate_number'] ?? '') }}">
                    @if ($errors->has('corporate_number'))<div class="invalid-feedback">{{ $errors->first('corporate_number') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="tel" class="form-label small fw-bold">電話番号 <span class="text-danger">*</span></label>
                    <input type="tel" name="tel" class="form-control font-monospace {{ $errors->has('tel') ? 'is-invalid' : '' }}" id="tel" placeholder="03-1234-5678" required value="{{ old('tel', $formData['tel'] ?? '') }}">
                    @if ($errors->has('tel'))<div class="invalid-feedback">{{ $errors->first('tel') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="homepage" class="form-label small fw-bold">ホームページURL</label>
                    <input type="url" name="homepage" class="form-control {{ $errors->has('homepage') ? 'is-invalid' : '' }}" id="homepage" placeholder="https://sample.com" value="{{ old('homepage', $formData['homepage'] ?? '') }}">
                    @if ($errors->has('homepage'))<div class="invalid-feedback">{{ $errors->first('homepage') }}</div>@endif
                </div>
            </div>

            <!-- 所在地情報 -->
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">所在地情報</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label for="zip_code" class="form-label small fw-bold">郵便番号 (ハイフンなし) <span class="text-danger">*</span></label>
                    <input type="text" name="zip_code" class="form-control font-monospace p-postal-code {{ $errors->has('zip_code') ? 'is-invalid' : '' }}" id="zip_code" placeholder="1000001" maxlength="7" required value="{{ old('zip_code', $formData['zip_code'] ?? '') }}">
                    @if ($errors->has('zip_code'))<div class="invalid-feedback">{{ $errors->first('zip_code') }}</div>@endif
                </div>
                <div class="col-12 col-md-4">
                    <label for="prefecture_code" class="form-label small fw-bold">都道府県 <span class="text-danger">*</span></label>
                    <select name="prefecture_code" class="form-select p-region {{ $errors->has('prefecture_code') ? 'is-invalid' : '' }}" id="prefecture_code" required>
                        <option value="">選択してください</option>
                        @foreach ($prefectures as $code => $pName)
                            <option value="{{ $code }}" {{ (string)old('prefecture_code', $formData['prefecture_code'] ?? '') === (string)$code ? 'selected' : '' }}>{{ $pName }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('prefecture_code'))<div class="invalid-feedback">{{ $errors->first('prefecture_code') }}</div>@endif
                </div>
                <div class="col-12 col-md-4">
                    <label for="city" class="form-label small fw-bold">市区町村 <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control p-locality {{ $errors->has('city') ? 'is-invalid' : '' }}" id="city" placeholder="例: 千代田区" required value="{{ old('city', $formData['city'] ?? '') }}">
                    @if ($errors->has('city'))<div class="invalid-feedback">{{ $errors->first('city') }}</div>@endif
                </div>
                <div class="col-12">
                    <label for="address" class="form-label small fw-bold">番地・建物名など <span class="text-danger">*</span></label>
                    <input type="text" name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" id="address" placeholder="例: 永田町1-1 サンプルビル 5F" required value="{{ old('address', $formData['address'] ?? '') }}">
                    @if ($errors->has('address'))<div class="invalid-feedback">{{ $errors->first('address') }}</div>@endif
                </div>
            </div>

            <!-- 初期管理者アカウント情報 -->
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">初期管理者アカウント設定</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="user_name" class="form-label small fw-bold">管理者担当者名 <span class="text-danger">*</span></label>
                    <input type="text" name="user_name" class="form-control {{ $errors->has('user_name') ? 'is-invalid' : '' }}" id="user_name" placeholder="例: 山田 太郎" required value="{{ old('user_name', $formData['user_name'] ?? '') }}">
                    @if ($errors->has('user_name'))<div class="invalid-feedback">{{ $errors->first('user_name') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="email" class="form-label small fw-bold">メールアドレス (ログインID) <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email" placeholder="admin@sample.com" required value="{{ old('email', $formData['email'] ?? '') }}">
                    @if ($errors->has('email'))<div class="invalid-feedback">{{ $errors->first('email') }}</div>@endif
                </div>
                <div class="col-12 col-md-6">
                    <label for="department" class="form-label small fw-bold">所属部署</label>
                    <input type="text" name="department" class="form-control" id="department" placeholder="例: 経営企画部" value="{{ old('department', $formData['department'] ?? '') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label for="position" class="form-label small fw-bold">役職</label>
                    <input type="text" name="position" class="form-control" id="position" placeholder="例: 部長" value="{{ old('position', $formData['position'] ?? '') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label for="password" class="form-label small fw-bold">初期パスワード <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" id="password" placeholder="8文字以上" required>
                    @if ($errors->has('password'))<div class="invalid-feedback">{{ $errors->first('password') }}</div>@endif
                </div>
            </div>

            <!-- 管理設定 (バッジ) -->
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">管理設定</h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="grant_badge" value="1" id="grant_badge" {{ old('grant_badge', $formData['grant_badge'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="grant_badge">
                            <i class="bi bi-star-fill text-warning me-1"></i>MESHu認証バッジを同時付与する
                        </label>
                        <div class="form-text text-muted ps-4 small mt-1">
                            チェックを入れると、登録完了時に「★認証済」バッジが付与されます。
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.corp.index') }}" class="btn btn-outline-secondary">一覧に戻る</a>
                <button type="submit" class="btn btn-dark px-4">確認へ進む <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

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
