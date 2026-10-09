@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.owner.index') }}" class="text-decoration-none">事務局アカウント管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">アカウント詳細</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">アカウント詳細</h1>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
        <a href="{{ route('admin.owner.edit', $owner->id) }}" class="btn btn-dark btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-pencil"></i>
            <span>編集する</span>
        </a>
        @if ((int)$owner->id !== (int)Auth::guard('admin')->id() && Auth::guard('admin')->user()->role === 'admin')
            @if ($owner->status === 'active')
                <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" disabled title="稼働中のアカウントは一時停止後のみ削除できます">
                    <i class="bi bi-trash"></i>
                    <span>削除する</span>
                </button>
            @else
                <a href="{{ route('admin.owner.delete.confirm', $owner->id) }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-trash"></i>
                    <span>削除する</span>
                </a>
            @endif
        @endif
    </div>
</div>

<!-- 詳細内容エリア -->
<div class="card card-custom bg-white max-width-800 mx-auto">
    <div class="card-header bg-light py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold">アカウントプロファイル</h5>
        @if ($owner->status === 'active')
            <span class="badge bg-success-subtle text-success fs-6">有効 (稼働)</span>
        @elseif ($owner->status === 'suspended')
            <span class="badge bg-warning-subtle text-warning-emphasis fs-6">停止中</span>
        @else
            <span class="badge bg-secondary fs-6">{{ $owner->status }}</span>
        @endif
    </div>
    <div class="card-body p-4">
        
        <table class="table table-bordered align-middle mb-4">
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">アカウントID</th>
                <td class="font-monospace fw-bold">AD-{{ sprintf('%04d', $owner->id) }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">管理者氏名</th>
                <td class="fw-bold">{{ $owner->name }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">メールアドレス</th>
                <td class="font-monospace">{{ $owner->email }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">権限ロール</th>
                <td>
                    @if ($owner->role === 'admin')
                        <span class="badge bg-danger">最高管理者</span> (システム管理者)
                    @elseif ($owner->role === 'reviewer')
                        <span class="badge bg-primary">審査担当者</span>
                    @else
                        <span class="badge bg-secondary">閲覧のみ</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">最終ログイン日時</th>
                <td class="font-monospace">{{ $owner->last_login_at ? $owner->last_login_at->format('Y-m-d H:i:s') : '未ログイン' }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">登録日時</th>
                <td class="font-monospace">{{ $owner->created_at ? $owner->created_at->format('Y-m-d H:i:s') : '-' }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">最終更新日時</th>
                <td class="font-monospace">{{ $owner->updated_at ? $owner->updated_at->format('Y-m-d H:i:s') : '-' }}</td>
            </tr>
        </table>

        <div class="border-top pt-4 text-center">
            <a href="{{ route('admin.owner.index') }}" class="btn btn-outline-secondary px-4">一覧に戻る</a>
        </div>
    </div>
</div>

@endsection
