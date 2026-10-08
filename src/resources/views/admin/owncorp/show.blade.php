@extends('layouts.admin')

@section('title', $title ?? ($company->name . ' 詳細 | MESHu 事務局管理'))

@section('content')
@php
    $owner = Auth::guard('admin')->user();
    $ownerRole = $owner->role ?? session('admin_owner_role') ?? 'viewer';
@endphp

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.index') }}" class="text-decoration-none">企業管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">企業詳細</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">企業詳細</h1>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
        @if (in_array($ownerRole, ['admin', 'reviewer'], true))
            <a href="{{ route('admin.corp.edit', ['id' => $company->id]) }}" class="btn btn-dark btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-pencil"></i>
                <span>編集する</span>
            </a>
        @endif
        @if ($ownerRole === 'admin')
            <a href="{{ route('admin.corp.delete.confirm', ['id' => $company->id]) }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-gear"></i>
                <span>ステータス設定・削除</span>
            </a>
        @endif
    </div>
</div>

<!-- 詳細内容エリア -->
<div class="card card-custom bg-white max-width-800 mx-auto">
    <div class="card-header bg-light py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold">企業プロファイル情報</h5>
        @if ($company->status === 'active')
            <span class="badge bg-success-subtle text-success fs-6">稼働 (有効)</span>
        @elseif ($company->status === 'suspended')
            <span class="badge bg-warning-subtle text-warning-emphasis fs-6">停止中</span>
        @else
            <span class="badge bg-secondary fs-6">{{ $company->status }}</span>
        @endif
    </div>
    <div class="card-body p-4">
        
        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">基本情報</h6>
        <table class="table table-bordered align-middle mb-4">
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">企業ID</th>
                <td class="font-monospace fw-bold">CO-{{ sprintf('%06d', $company->id) }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">企業名（正式名称）</th>
                <td class="fw-bold fs-6">{{ $company->name }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">企業名カナ</th>
                <td>{{ $company->abbreviated_kana ?? '-' }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">法人名略称</th>
                <td>{{ $company->abbreviated_name ?? '-' }}</td>
            </tr>
            @if (!empty($company->sub_name))
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">拠点名・区分名</th>
                    <td>{{ $company->sub_name }}</td>
                </tr>
            @endif
            @if (!empty($parentCompany))
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">所属親企業 (本社)</th>
                    <td>
                        <a href="{{ route('admin.corp.show', ['id' => $parentCompany->id]) }}" class="text-decoration-none fw-semibold">
                            <i class="bi bi-building me-1"></i>{{ $parentCompany->name }}
                        </a>
                        <span class="text-muted small ms-2">(企業ID: {{ $parentCompany->id }})</span>
                    </td>
                </tr>
            @endif
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">専用ログインURL識別子</th>
                <td class="font-monospace">
                    <span class="badge bg-light text-dark border me-2">{{ $company->url_code }}</span>
                    <a href="{{ url($company->url_code . '/login') }}" target="_blank" class="text-decoration-none small">
                        ログイン画面 <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">法人番号</th>
                <td class="font-monospace">{{ $company->corporate_number ?? '-' }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">電話番号</th>
                <td class="font-monospace">{{ $company->tel ?? '-' }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">ホームページ</th>
                <td>
                    @if ($company->homepage)
                        <a href="{{ $company->homepage }}" target="_blank" rel="noopener noreferrer">{{ $company->homepage }} <i class="bi bi-box-arrow-up-right small ms-1"></i></a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">招待元企業</th>
                <td>
                    @if (!empty($invitationCompany))
                        <a href="{{ route('admin.corp.show', ['id' => $invitationCompany->id]) }}" class="text-decoration-none fw-semibold">
                            <i class="bi bi-building me-1"></i>{{ $invitationCompany->name }}
                        </a>
                        <span class="text-muted small ms-2">(企業ID: {{ $invitationCompany->id }})</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
        </table>

        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">所在地</h6>
        <table class="table table-bordered align-middle mb-4">
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">郵便番号</th>
                <td class="font-monospace">{{ $company->zip_code ?? '-' }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">住所</th>
                <td>
                    {{ $prefectures[$company->prefecture_code] ?? '' }}
                    {{ $company->city ?? '' }}
                    {{ $company->address ?? '' }}
                </td>
            </tr>
        </table>

        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary d-flex justify-content-between align-items-center">
            <span>MESHu認証バッジ設定</span>
            @if (in_array($ownerRole, ['admin', 'reviewer'], true))
                <form action="{{ route('admin.corp.toggle_badge', ['id' => $company->id]) }}" method="POST" class="m-0">
                    @csrf
                    @if (!empty($badge) && $badge->status === 'active')
                        <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('認証バッジを解除しますか？');">
                            <i class="bi bi-x-circle me-1"></i>バッジ解除
                        </button>
                    @else
                        <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark" onclick="return confirm('認証バッジを付与しますか？');">
                            <i class="bi bi-star-fill me-1"></i>バッジ付与
                        </button>
                    @endif
                </form>
            @endif
        </h6>
        <table class="table table-bordered align-middle mb-4">
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">バッジステータス</th>
                <td>
                    @if (!empty($badge) && $badge->status === 'active')
                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>★認証済</span>
                        <div class="small text-muted mt-1">付与日時: {{ $badge->created_at ? $badge->created_at->format('Y-m-d H:i') : '-' }}</div>
                    @else
                        <span class="badge bg-light text-muted border">未認証</span>
                    @endif
                </td>
            </tr>
        </table>

        <!-- 所属拠点一覧 -->
        @if ($childCompanies->isNotEmpty())
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">所属拠点・支社一覧</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>拠点ID</th>
                            <th>拠点名・区分名</th>
                            <th>所在地</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($childCompanies as $child)
                            <tr>
                                <td class="font-monospace">CO-{{ sprintf('%06d', $child->id) }}</td>
                                <td>{{ $child->name }} ({{ $child->sub_name ?? '拠点' }})</td>
                                <td>{{ $prefectures[$child->prefecture_code] ?? '' }} {{ $child->city }}</td>
                                <td><a href="{{ route('admin.corp.show', ['id' => $child->id]) }}" class="btn btn-outline-secondary btn-sm py-0">詳細</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- 所属ユーザー一覧 -->
        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">所属ユーザーアカウント一覧 ({{ $users->count() }}名)</h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ユーザーID</th>
                        <th>担当者名</th>
                        <th>メールアドレス</th>
                        <th>所属 / 役職</th>
                        <th>社内権限</th>
                        <th>状態</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($users->isEmpty())
                        <tr><td colspan="6" class="text-center text-muted py-2">登録ユーザーなし</td></tr>
                    @else
                        @foreach ($users as $u)
                            <tr>
                                <td class="font-monospace">USR-{{ sprintf('%06d', $u->id) }}</td>
                                <td class="fw-bold">{{ $u->user_name }}</td>
                                <td class="font-monospace">{{ $u->email }}</td>
                                <td>{{ $u->department ?? '-' }} / {{ $u->position ?? '-' }}</td>
                                <td>
                                    @if ($u->role === 'admin')
                                        <span class="badge bg-primary">管理者</span>
                                    @else
                                        <span class="badge bg-secondary">一般社員</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($u->status === 'active')
                                        <span class="badge bg-success-subtle text-success">稼働</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $u->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="border-top pt-4 text-center">
            <a href="{{ route('admin.corp.index') }}" class="btn btn-outline-secondary px-4">一覧に戻る</a>
        </div>
    </div>
</div>

@endsection
