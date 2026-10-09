@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.owner.index') }}" class="text-decoration-none">事務局アカウント管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">新規アカウント作成</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">新規事務局アカウント作成</h1>
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
        <h5 class="m-0 fw-bold">アカウント情報入力</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.owner.confirm') }}" method="POST" novalidate>
            @csrf
            
            <div class="mb-3">
                <label for="name" class="form-label small fw-bold">管理者氏名 <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="例: 山田 太郎" required value="{{ old('name', $formData['name'] ?? '') }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small fw-bold">メールアドレス (ログインID) <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="例: admin@meshu.org" required value="{{ old('email', $formData['email'] ?? '') }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-bold">パスワード <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="8文字以上" required>
                <div class="form-text text-muted small">8文字以上の英数字を入力してください。</div>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @php $isAdmin = (Auth::guard('admin')->user()->role === 'admin'); @endphp
            <div class="mb-4">
                <label class="form-label small fw-bold">権限ロール <span class="text-danger">*</span></label>
                @if (!$isAdmin)
                    <input type="hidden" name="role" value="reviewer">
                    <div class="alert alert-light border small text-muted mb-2">
                        <i class="bi bi-info-circle me-1"></i>権限ロールの指定は最高管理者のみ実行可能です。（デフォルト: 審査担当者）
                    </div>
                @endif
                <div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="role" id="roleAdmin" value="admin" {{ old('role', $formData['role'] ?? 'admin') === 'admin' ? 'checked' : '' }} {{ !$isAdmin ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="roleAdmin">
                            <strong>最高管理者 (`admin`)</strong> - すべての権限（企業編集・削除、認証バッジ、アカウント追加・削除）を持ちます。
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="role" id="roleReviewer" value="reviewer" {{ old('role', $formData['role'] ?? 'reviewer') === 'reviewer' ? 'checked' : '' }} {{ !$isAdmin ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="roleReviewer">
                            <strong>審査担当者 (`reviewer`)</strong> - 企業登録、認証バッジの付与が可能です。
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="roleViewer" value="viewer" {{ old('role', $formData['role'] ?? '') === 'viewer' ? 'checked' : '' }} {{ !$isAdmin ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="roleViewer">
                            <strong>閲覧のみ (`viewer`)</strong> - データの閲覧のみ可能です。
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch p-3 bg-light rounded border">
                    <input type="hidden" name="status" value="suspended">
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="status" value="active" id="status" {{ old('status', $formData['status'] ?? 'active') === 'active' ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-dark" for="status">
                        アカウントを有効にする
                    </label>
                    <div class="form-text text-muted ps-4 small mt-1">
                        チェックを外すと、該当ユーザーは管理画面にログインできなくなります（停止中）。
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.owner.index') }}" class="btn btn-outline-secondary">一覧に戻る</a>
                <button type="submit" class="btn btn-dark px-4">確認へ進む <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
