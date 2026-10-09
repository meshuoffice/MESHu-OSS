@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item active" aria-current="page">事務局アカウント管理</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">事務局アカウント管理</h1>
    </div>
    <div class="mt-2 mt-sm-0">
        <a href="{{ route('admin.owner.registration') }}" class="btn btn-primary d-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i>
            <span>新規事務局アカウント作成</span>
        </a>
    </div>
</div>

<!-- 検索フィルター -->
<div class="card card-custom bg-white mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-search me-1"></i>検索条件</h6>
        <form action="{{ route('admin.owner.index') }}" method="GET" class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">管理者名 / メールアドレス</label>
                <input type="text" name="keyword" class="form-control form-control-sm" placeholder="例: 山田, admin@" value="{{ $keyword }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">権限レベル</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>最高管理者 (admin)</option>
                    <option value="reviewer" {{ $role === 'reviewer' ? 'selected' : '' }}>審査担当者 (reviewer)</option>
                    <option value="viewer" {{ $role === 'viewer' ? 'selected' : '' }}>閲覧のみ (viewer)</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">ステータス</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>有効 (稼働)</option>
                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <a href="{{ route('admin.owner.index') }}" class="btn btn-secondary btn-sm me-2"><i class="bi bi-arrow-counterclockwise me-1"></i>クリア</a>
                <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-search me-1"></i>検索する</button>
            </div>
        </form>
    </div>
</div>

<!-- 一覧テーブル -->
<div class="card card-custom bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>管理者名</th>
                        <th>メールアドレス</th>
                        <th>権限レベル</th>
                        <th>最終ログイン</th>
                        <th>ステータス</th>
                        <th class="pe-4 text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($owners->isEmpty())
                        <tr><td colspan="7" class="text-center py-4 text-muted">該当する事務局アカウントはありません。</td></tr>
                    @else
                        @foreach ($owners as $own)
                            <tr>
                                <td class="ps-4 font-monospace">AD-{{ sprintf('%04d', $own->id) }}</td>
                                <td class="fw-bold">
                                    <a href="{{ route('admin.owner.show', $own->id) }}" class="text-decoration-none">
                                        {{ $own->name }}
                                        @if ((int)$own->id === (int)Auth::guard('admin')->id())
                                            <span class="badge bg-info-subtle text-info ms-1">ログイン中</span>
                                        @endif
                                    </a>
                                </td>
                                <td class="font-monospace">{{ $own->email }}</td>
                                <td>
                                    @if ($own->role === 'admin')
                                        <span class="badge bg-danger">最高管理者</span>
                                    @elseif ($own->role === 'reviewer')
                                        <span class="badge bg-primary">審査担当者</span>
                                    @else
                                        <span class="badge bg-secondary">閲覧のみ</span>
                                    @endif
                                </td>
                                <td class="font-monospace">{{ $own->last_login_at ? $own->last_login_at->format('Y-m-d H:i:s') : '-' }}</td>
                                <td>
                                    @if ($own->status === 'active')
                                        <span class="badge bg-success-subtle text-success">有効</span>
                                    @elseif ($own->status === 'suspended')
                                        <span class="badge bg-warning-subtle text-warning-emphasis">停止中</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $own->status }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('admin.owner.show', $own->id) }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-eye"></i> 詳細</a>
                                    <a href="{{ route('admin.owner.edit', $own->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i> 編集</a>
                                    @if ((int)$own->id === (int)Auth::guard('admin')->id())
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="自身のアカウントは削除できません"><i class="bi bi-trash"></i> 削除</button>
                                    @elseif ($own->status === 'active')
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="稼働中のアカウントは一時停止後のみ削除できます"><i class="bi bi-trash"></i> 削除</button>
                                    @else
                                        <a href="{{ route('admin.owner.delete.confirm', $own->id) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> 削除</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ページネーション -->
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center py-3 px-4">
        <span class="small text-muted">全 {{ number_format($owners->total()) }} 件中 {{ $owners->total() > 0 ? $owners->firstItem() . '-' . $owners->lastItem() : '0' }} 件を表示</span>
        @if ($owners->hasPages())
            {{ $owners->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>

@endsection
