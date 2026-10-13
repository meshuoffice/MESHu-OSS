@extends('layouts.tenant')

@section('title', '招待先管理 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item active">招待先管理</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">招待先管理</h1>
    </div>
    <div>
        <a href="{{ route('tenant.invitation.new') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-send-plus-fill"></i>
            <span>新規招待メール作成</span>
        </a>
    </div>
</div>

<!-- アラート案内 -->
<div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-3 mb-4">
    <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0 mt-1"></i>
    <div class="small">
            招待した相手企業がメール内の専用URLから登録を完了すると、<strong>自動的につながり（取引先）が生成</strong>され、本一覧から除外されます。<br>
            招待送信後に一定期間登録がない場合などは、詳細画面または一覧の削除ボタンより招待レコードの削除（物理削除）が可能です。
        </div>
    </div>

    <!-- 検索フィルターカード -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('tenant.invitation.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="keyword" value="{{ $keyword ?? '' }}" class="form-control border-start-0" placeholder="招待先企業名、メールアドレス、法人番号で検索...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="buysell" class="form-select form-select-sm">
                        <option value="">連携属性 (すべて)</option>
                        <option value="buyer" {{ ($buysell ?? '') === 'buyer' ? 'selected' : '' }}>自社: 購入側 (買手)</option>
                        <option value="seller" {{ ($buysell ?? '') === 'seller' ? 'selected' : '' }}>自社: 販売側 (売手)</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100"><i class="bi bi-filter me-1"></i>絞り込む</button>
                    @if (!empty($keyword) || !empty($buysell))
                        <a href="{{ route('tenant.invitation.index') }}" class="btn btn-sm btn-outline-secondary">解除</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- 招待一覧テーブルカード -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-envelope-paper me-2 text-primary"></i>送信済み招待一覧</h5>
            <span class="badge bg-light text-dark border">全 {{ number_format($total) }} 件</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">招待先 企業名</th>
                        <th scope="col">法人番号</th>
                        <th scope="col">招待先 管理者名</th>
                        <th scope="col">送信先メールアドレス</th>
                        <th scope="col">登録時の連携属性</th>
                        <th scope="col">送信者</th>
                        <th scope="col">送信日時</th>
                        <th scope="col" class="text-end pe-4" style="width: 160px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($invitations->isEmpty())
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                現在、送信済みの招待情報はありません。
                            </td>
                        </tr>
                    @else
                        @foreach ($invitations as $inv)
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <a href="{{ route('tenant.invitation.show', $inv->id) }}" class="text-decoration-none text-dark">
                                        {{ $inv->company_name }}
                                    </a>
                                </td>
                                <td class="font-monospace small">{!! !empty($inv->corporate_number) ? e($inv->corporate_number) : '<span class="text-muted">-</span>' !!}</td>
                                <td>{!! !empty($inv->user_name) ? e($inv->user_name) : '<span class="text-muted">-</span>' !!}</td>
                                <td class="font-monospace small text-primary">{{ $inv->email }}</td>
                                <td>
                                    @if ($inv->buysell === 'buyer')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">購入 (買手)</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">販売 (売手)</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $inv->user ? $inv->user->user_name : '-' }}</td>
                                <td class="small text-muted">{{ $inv->created_at ? $inv->created_at->format('Y-m-d H:i') : '-' }}</td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('tenant.invitation.show', $inv->id) }}" class="btn btn-outline-secondary" title="詳細閲覧"><i class="bi bi-eye"></i> 詳細</a>
                                        <a href="{{ route('tenant.invitation.delete_confirm', $inv->id) }}" class="btn btn-outline-danger" title="招待削除"><i class="bi bi-trash"></i> 削除</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        @if ($totalPages > 1)
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="small text-muted">全 {{ number_format($total) }} 件中 {{ (($page - 1) * $perPage + 1) }} - {{ min($page * $perPage, $total) }} 件を表示</span>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $invitations->previousPageUrl() ?: '#' }}">前へ</a>
                        </li>
                        @for ($p = 1; $p <= $totalPages; $p++)
                            <li class="page-item {{ $page === $p ? 'active' : '' }}">
                                <a class="page-link" href="{{ $invitations->url($p) }}">{{ $p }}</a>
                            </li>
                        @endfor
                        <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $invitations->nextPageUrl() ?: '#' }}">次へ</a>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    </div>
@endsection
