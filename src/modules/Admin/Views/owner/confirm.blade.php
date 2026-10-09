@extends('admin::layouts.admin')

@section('content')

@php
$isEdit = !empty($isEdit);
$targetAction = $isEdit ? route('admin.owner.update', $ownerId) : route('admin.owner.complete');
@endphp

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.owner.index') }}" class="text-decoration-none">事務局アカウント管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'アカウント情報更新確認' : 'アカウント登録確認' }}</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">{{ $isEdit ? 'アカウント情報更新確認' : 'アカウント登録確認' }}</h1>
    </div>
</div>

<!-- 確認内容エリア -->
<div class="card card-custom bg-white max-width-800 mx-auto">
    <div class="card-header bg-light py-3 border-0">
        <h5 class="m-0 fw-bold text-center">以下の内容で{{ $isEdit ? '更新' : '登録' }}します。よろしいですか？</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ $targetAction }}" method="POST">
            @csrf

            @foreach ($postData as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach
            
            <table class="table table-bordered align-middle mb-4">
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">管理者氏名</th>
                    <td class="fw-bold">{{ $postData['name'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">メールアドレス</th>
                    <td class="font-monospace">{{ $postData['email'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">パスワード</th>
                    <td class="font-monospace">
                        @if (!empty($postData['password']))
                            •••••••• (入力された新しいパスワードに変更)
                        @else
                            <span class="text-muted">(変更なし)</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">権限レベル</th>
                    <td>
                        @php
                        $role = $postData['role'] ?? '';
                        @endphp
                        @if ($role === 'admin')
                            <span class="badge bg-danger">最高管理者 (admin)</span>
                        @elseif ($role === 'reviewer')
                            <span class="badge bg-primary">審査担当者 (reviewer)</span>
                        @elseif ($role === 'viewer')
                            <span class="badge bg-secondary">閲覧のみ (viewer)</span>
                        @else
                            {{ $role }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">ステータス</th>
                    <td>
                        @if (($postData['status'] ?? '') === 'active')
                            <span class="badge bg-success-subtle text-success">有効 (稼働)</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning-emphasis">停止中</span>
                        @endif
                    </td>
                </tr>
            </table>

            <div class="d-flex justify-content-between border-top pt-4">
                <button type="button" onclick="history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>修正する</button>
                <button type="submit" class="btn btn-primary px-4" style="background-color: #38bdf8; border-color: #38bdf8; color: #0f172a;">{{ $isEdit ? '更新を保存する' : '確定する' }} <i class="bi bi-check-lg ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
