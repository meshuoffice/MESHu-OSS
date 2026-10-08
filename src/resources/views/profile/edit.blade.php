@extends('layouts.tenant')

@section('title', 'マイプロフィール編集 | MESHu')

@section('content')
<div class="mx-auto" style="max-width: 900px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">ホーム</a></li>
            <li class="breadcrumb-item active">マイプロフィール編集</li>
        </ol>
    </nav>

    <h1 class="h3 fw-bold mb-4">マイプロフィール</h1>

    <form action="{{ route('tenant.profile.confirm') }}" method="POST" novalidate class="card card-custom bg-white p-4">
        @csrf

        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">アカウント・パスワード設定</h5>
        <div class="mb-3">
            <label class="form-label fw-semibold">ログインID (メールアドレス) <span class="badge bg-danger">必須</span></label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-12">
                <label class="form-label fw-semibold">現在のパスワード <small class="text-muted">（パスワード変更時のみ入力）</small></label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" placeholder="••••••••">
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">新しいパスワード <small class="text-muted">（8文字以上）</small></label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">新しいパスワード (確認)</label>
                <input type="password" class="form-control @error('password_confirm') is-invalid @enderror" name="password_confirm" placeholder="••••••••">
                @error('password_confirm')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2 text-primary">基本情報</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">名前 <span class="badge bg-danger">必須</span></label>
                <input type="text" class="form-control @error('user_name') is-invalid @enderror" name="user_name" value="{{ old('user_name', $user->user_name) }}" required>
                @error('user_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">名前カナ <small class="text-muted">（全角カナ）</small></label>
                <input type="text" class="form-control @error('user_name_kana') is-invalid @enderror" name="user_name_kana" value="{{ old('user_name_kana', $user->user_name_kana ?? '') }}" placeholder="ヤマダ タロウ">
                @error('user_name_kana')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">部署</label>
                <input type="text" class="form-control" name="department" value="{{ old('department', $user->department) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">役職</label>
                <input type="text" class="form-control" name="position" value="{{ old('position', $user->position) }}">
            </div>
        </div>

        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2 text-primary">通知設定</h5>
        @php
            $isNotifyTrade   = old('_token') ? (old('notify_trade') == '1') : (bool)$user->notify_trade;
            $isNotifyComment = old('_token') ? (old('notify_comment') == '1') : (bool)$user->notify_comment;
            $isNotifyChat    = old('_token') ? (old('notify_chat') == '1') : (bool)$user->notify_chat;
        @endphp
        <div class="mb-4">
            <label class="form-label fw-semibold text-dark mb-2">お知らせメール受信設定</label>
            <div class="d-flex gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="notify_trade" value="1" id="notifyTrade" {{ $isNotifyTrade ? 'checked' : '' }}>
                    <label class="form-check-label text-dark" for="notifyTrade">取引</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="notify_comment" value="1" id="notifyComment" {{ $isNotifyComment ? 'checked' : '' }}>
                    <label class="form-check-label text-dark" for="notifyComment">コメント</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="notify_chat" value="1" id="notifyChat" {{ $isNotifyChat ? 'checked' : '' }}>
                    <label class="form-check-label text-dark" for="notifyChat">チャット</label>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2 text-primary">所属・担当企業情報（表示のみ）</h5>
        <div class="mb-4">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="fw-semibold text-dark me-2">取引先担当指定:</span>
                @if ((int)($user->is_all_assigned ?? 1) === 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">全担当</span>
                @else
                    @if (empty($assignedCompanies))
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">割り当て企業なし</span>
                    @else
                        @foreach ($assignedCompanies as $comp)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">{{ $comp['name'] }}</span>
                        @endforeach
                    @endif
                @endif
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary px-4">確認画面へ進む <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </form>
</div>
@endsection
