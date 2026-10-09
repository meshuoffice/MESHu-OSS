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
                <li class="breadcrumb-item active" aria-current="page">ステータス設定・削除確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">ステータス設定・削除確認</h1>
    </div>
</div>

<!-- 削除確認警告エリア -->
<div class="card card-custom bg-white max-width-800 mx-auto">
    <div class="card-header bg-danger text-white py-3 border-0">
        <h5 class="m-0 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>対象アカウント：{{ $owner->name }}</h5>
    </div>
    <div class="card-body p-4">
        
        <div class="alert alert-warning" role="alert">
            <h6 class="fw-bold mb-2">事務局アカウント状態の変更</h6>
            <p class="mb-0 small">
                アカウントステータスを「有効」「一時停止」「削除状態」に変更できます。<br>
                アカウントを停止または削除すると、該当ユーザーは事務局管理画面へのアクセスが遮断されます。
            </p>
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-secondary">対象アカウントの情報</h6>
        <table class="table table-bordered align-middle mb-4">
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">アカウントID</th>
                <td class="font-monospace fw-bold">AD-{{ sprintf('%04d', $owner->id) }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">管理者氏名</th>
                <td class="fw-bold text-dark">{{ $owner->name }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">メールアドレス</th>
                <td class="font-monospace">{{ $owner->email }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">現在の権限</th>
                <td>
                    @if ($owner->role === 'admin')
                        <span class="badge bg-danger">最高管理者</span>
                    @elseif ($owner->role === 'reviewer')
                        <span class="badge bg-primary">審査担当者</span>
                    @else
                        <span class="badge bg-secondary">閲覧のみ</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">現在のステータス</th>
                <td>
                    @if ($owner->status === 'active')
                        <span class="badge bg-success-subtle text-success">有効 (稼働)</span>
                    @elseif ($owner->status === 'suspended')
                        <span class="badge bg-warning-subtle text-warning-emphasis">停止中</span>
                    @else
                        <span class="badge bg-secondary">{{ $owner->status }}</span>
                    @endif
                </td>
            </tr>
        </table>

        <form action="{{ route('admin.owner.delete', $owner->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label fw-bold">変更後のステータスを選択してください</label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" {{ $owner->status === 'active' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-success" for="statusActive">
                        <i class="bi bi-check-circle me-1"></i>有効 (ログインを許可)
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="status" id="statusSuspended" value="suspended" {{ $owner->status === 'suspended' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-warning-emphasis" for="statusSuspended">
                        <i class="bi bi-pause-circle me-1"></i>一時停止 (ログイン遮断)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="statusDeleted" value="deleted" {{ $owner->status === 'deleted' ? 'checked' : '' }} {{ $owner->status === 'active' ? 'disabled' : '' }}>
                    <label class="form-check-label fw-semibold {{ $owner->status === 'active' ? 'text-muted' : 'text-danger' }}" for="statusDeleted">
                        <i class="bi bi-trash me-1"></i>削除状態にする (アカウント無効化)
                        @if ($owner->status === 'active')
                            <span class="badge bg-secondary-subtle text-secondary ms-2 fw-normal">※稼働中のアカウントは一旦「一時停止」にした後で削除可能です</span>
                        @endif
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.owner.show', $owner->id) }}" class="btn btn-outline-secondary">キャンセルして詳細に戻る</a>
                <button type="submit" class="btn btn-danger px-4" onclick="return confirm('本当にステータスを変更・実行してよろしいですか？');">実行する <i class="bi bi-check-lg ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
