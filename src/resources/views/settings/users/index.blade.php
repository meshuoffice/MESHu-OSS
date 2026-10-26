@extends('layouts.tenant')

@section('title', 'MESHu - 従業員管理')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">従業員管理</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">従業員管理</h1>
    </div>
    <div class="mt-2 mt-sm-0">
        <a href="{{ route('tenant.users.create') }}" class="btn btn-primary d-flex align-items-center gap-1 fw-semibold px-3">
            <i class="bi bi-plus-lg"></i>
            <span>新規従業員を登録</span>
        </a>
    </div>
</div>

<!-- アラート通知 -->
@if (session('error'))
    <div class="alert alert-danger mb-4" role="alert">
        {{ session('error') }}
    </div>
@endif
@if (session('success'))
    <div class="alert alert-success mb-4" role="alert">
        {{ session('success') }}
    </div>
@endif

<!-- 検索フィルター -->
<div class="card card-custom bg-white mb-4 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-search me-1 text-primary"></i>絞り込み検索</h6>
        <form action="{{ route('tenant.users.index') }}" method="GET" class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">名前 / フリガナ / メールアドレス</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ $searchWord }}" placeholder="キーワードを入力">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">権限レベル</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="admin" {{ $selectedRole === 'admin' ? 'selected' : '' }}>自社管理者</option>
                    <option value="staff" {{ $selectedRole === 'staff' ? 'selected' : '' }}>一般ユーザー</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">ステータス</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>有効</option>
                    <option value="suspended" {{ $selectedStatus === 'suspended' ? 'selected' : '' }}>無効・停止</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <a href="{{ route('tenant.users.index') }}" class="btn btn-secondary btn-sm w-50 text-center text-decoration-none"><i class="bi bi-arrow-counterclockwise me-1"></i>クリア</a>
                <button type="submit" class="btn btn-primary btn-sm w-50"><i class="bi bi-search me-1"></i>検索</button>
            </div>
        </form>
    </div>
</div>

<!-- 従業員一覧テーブル -->
<div class="card card-custom bg-white shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill me-2 text-primary"></i>自社アカウント一覧</h5>
        <span class="badge bg-light text-dark border">全 {{ count($users) }} 件</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">名前 / フリガナ</th>
                        <th>メールアドレス (ログインID)</th>
                        <th>部署 / 役職</th>
                        <th>権限レベル</th>
                        <th>ステータス</th>
                        <th>最終ログイン</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">
                                    <a href="{{ route('tenant.users.show', ['user' => $u->id]) }}" class="text-primary text-decoration-none">
                                        {{ $u->user_name }}
                                    </a>
                                </div>
                                <div class="text-muted small">{{ $u->user_name_kana }}</div>
                            </td>
                            <td class="font-monospace">{{ $u->email }}</td>
                            <td>
                                {{ $u->department ?? '-' }} 
                                @if (!empty($u->position)) / {{ $u->position }} @endif
                            </td>
                            <td>
                                @if ($u->role === 'admin')
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">自社管理者</span>
                                @else
                                    <span class="badge bg-light text-dark border">一般ユーザー</span>
                                @endif
                            </td>
                            <td>
                                @if ($u->status === 'active')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">有効</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">停止中</span>
                                @endif
                            </td>
                            <td class="font-monospace small">
                                {{ $u->last_login_at ? $u->last_login_at->format('Y-m-d H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">条件に該当する従業員は見つかりませんでした。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
