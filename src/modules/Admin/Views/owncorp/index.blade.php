@extends('admin::layouts.admin')

@section('title', $title ?? '企業管理 | MESHu 事務局管理')

@section('content')
@php
    $owner = Auth::guard('admin')->user();
    $ownerRole = $owner->role ?? 'viewer';
@endphp

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item active" aria-current="page">企業管理</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">企業管理</h1>
    </div>
    @if (in_array($ownerRole, ['admin', 'reviewer'], true))
        <div class="mt-2 mt-sm-0">
            <a href="{{ route('admin.corp.registration') }}" class="btn btn-primary d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i>
                <span>新規企業登録</span>
            </a>
        </div>
    @endif
</div>

<!-- 検索フィルター -->
<div class="card card-custom bg-white mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-search me-1"></i>検索条件</h6>
        <form action="{{ route('admin.corp.index') }}" method="GET" class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">企業名 / URLコード / 法人番号</label>
                <input type="text" name="keyword" class="form-control form-control-sm" placeholder="キーワード検索" value="{{ $keyword }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">都道府県</label>
                <select name="prefecture_code" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    @foreach ($prefectures as $code => $name)
                        <option value="{{ $code }}" {{ (string)$prefectureCode === (string)$code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">バッジステータス</label>
                <select name="badge_status" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="verified" {{ $badgeStatus === 'verified' ? 'selected' : '' }}>認証済のみ</option>
                    <option value="unverified" {{ $badgeStatus === 'unverified' ? 'selected' : '' }}>未認証のみ</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">稼働ステータス</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>稼働 (有効)</option>
                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <a href="{{ route('admin.corp.index') }}" class="btn btn-secondary btn-sm me-2"><i class="bi bi-arrow-counterclockwise me-1"></i>クリア</a>
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
                        <th class="ps-4">企業ID</th>
                        <th>企業名 / 拠点名</th>
                        <th>URLコード</th>
                        <th>法人番号</th>
                        <th>都道府県</th>
                        <th>MESHu認証バッジ</th>
                        <th>ステータス</th>
                        <th class="pe-4 text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($companies->isEmpty())
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">該当する企業は見つかりませんでした。</td>
                        </tr>
                    @else
                        @foreach ($companies as $comp)
                            <tr>
                                <td class="ps-4 font-monospace">CO-{{ sprintf('%06d', $comp->id) }}</td>
                                <td class="fw-bold">
                                    <a href="{{ route('admin.corp.show', ['id' => $comp->id]) }}" class="text-decoration-none">
                                        {{ $comp->name }}
                                        @if (!empty($comp->sub_name))
                                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $comp->sub_name }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td class="font-monospace"><span class="badge bg-light text-dark border">{{ $comp->url_code }}</span></td>
                                <td class="font-monospace">{{ $comp->corporate_number ?? '-' }}</td>
                                <td>{{ $prefectures[$comp->prefecture_code] ?? '-' }}</td>
                                <td>
                                    @if (($comp->badge_status ?? '') === 'active')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>★認証済</span>
                                    @else
                                        <span class="badge bg-light text-muted border">未認証</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($comp->status === 'active')
                                        <span class="badge bg-success-subtle text-success">稼働</span>
                                    @elseif ($comp->status === 'suspended')
                                        <span class="badge bg-warning-subtle text-warning-emphasis">停止中</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $comp->status }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('admin.corp.impersonate', ['id' => $comp->id]) }}" target="_blank" onclick="return confirm('この企業サイトに移動します。\nよろしいですか？');" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>移動
                                    </a>
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
        <span class="small text-muted">全 {{ number_format($companies->total()) }} 件中 {{ $companies->total() > 0 ? $companies->firstItem() . '-' . $companies->lastItem() : '0' }} 件を表示</span>
        @if ($companies->hasPages())
            <div>
                {{ $companies->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
