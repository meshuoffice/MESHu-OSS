@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.owner.index') }}" class="text-decoration-none">事務局アカウント管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.owner.show', $owner->id) }}" class="text-decoration-none">アカウント詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">アカウント編集</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">アカウント編集</h1>
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
        <h5 class="m-0 fw-bold">アカウント情報変更</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.owner.confirm') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="id" value="{{ $owner->id }}">
            
            <div class="mb-3">
                <label for="name" class="form-label small fw-bold">管理者氏名 <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" required value="{{ old('name', $owner->name) }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small fw-bold">メールアドレス (ログインID) <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" required value="{{ old('email', $owner->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-bold">新しいパスワード <span class="text-muted">(変更する場合のみ入力)</span></label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="••••••••">
                <div class="form-text text-muted small">変更しない場合は空欄のままにしてください。</div>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @php 
                $isAdmin = (Auth::guard('admin')->user()->role === 'admin'); 
                $isSelf  = ((int)$owner->id === (int)Auth::guard('admin')->id());
                $canChangeRole = ($isAdmin && !$isSelf);
            @endphp
            <div class="mb-4">
                <label class="form-label small fw-bold">権限ロール <span class="text-danger">*</span></label>
                @if (!$canChangeRole)
                    <input type="hidden" name="role" value="{{ $owner->role }}">
                    <div class="alert alert-light border small text-muted mb-2">
                        <i class="bi bi-info-circle me-1"></i>
                        @if ($isSelf)
                            自分自身のアカウントの権限ロールは変更できません。
                        @else
                            権限ロールの変更は最高管理者のみ実行可能です。
                        @endif
                    </div>
                @endif
                <div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="role" id="roleAdmin" value="admin" {{ old('role', $owner->role) === 'admin' ? 'checked' : '' }} {{ !$canChangeRole ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="roleAdmin">
                            <strong>最高管理者 (`admin`)</strong> - すべての権限を持ちます。
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="role" id="roleReviewer" value="reviewer" {{ old('role', $owner->role) === 'reviewer' ? 'checked' : '' }} {{ !$canChangeRole ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="roleReviewer">
                            <strong>審査担当者 (`reviewer`)</strong> - 企業登録、認証バッジの付与が可能です。
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="roleViewer" value="viewer" {{ old('role', $owner->role) === 'viewer' ? 'checked' : '' }} {{ !$canChangeRole ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="roleViewer">
                            <strong>閲覧のみ (`viewer`)</strong> - データの閲覧のみ可能です。
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch p-3 bg-light rounded border">
                    @if ($isSelf)
                        <input type="hidden" name="status" value="active">
                    @else
                        <input type="hidden" name="status" value="suspended">
                    @endif
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="status" value="active" id="status" {{ old('status', $owner->status) === 'active' ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }}>
                    <label class="form-check-label fw-bold text-dark" for="status">
                        アカウントを有効にする
                    </label>
                    @if ($isSelf)
                        <div class="form-text text-muted ps-4 small mt-1">
                            ※自分自身のアカウントのステータス（有効/停止）は変更できません。
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.owner.show', $owner->id) }}" class="btn btn-outline-secondary">詳細に戻る</a>
                <button type="submit" class="btn btn-dark px-4">確認へ進む <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
