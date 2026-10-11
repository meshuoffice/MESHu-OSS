@extends('layouts.tenant')

@section('title', 'MESHu - 従業員詳細')

@section('styles')
<style>
    .detail-label { font-weight: 700; color: #555; }
    .detail-value {
        padding: 0.375rem 0.75rem;
        background-color: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        min-height: 38px;
        display: flex;
        align-items: center;
    }
</style>
@endsection

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.users.index') }}" class="text-decoration-none">従業員管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">詳細</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">従業員詳細</h1>
    </div>
    <div class="mt-2 mt-sm-0 d-flex gap-2">
        <a href="{{ route('tenant.users.password_edit', ['user' => $targetUser->id]) }}" class="btn btn-outline-warning text-dark fw-semibold px-3 d-flex align-items-center gap-1">
            <i class="bi bi-key"></i>
            <span>パスワードの再発行</span>
        </a>
        <a href="{{ route('tenant.users.edit', ['user' => $targetUser->id]) }}" class="btn btn-primary d-flex align-items-center gap-1 fw-semibold px-3">
            <i class="bi bi-pencil-square"></i>
            <span>編集する</span>
        </a>
        @if ($targetUser->status === 'suspended')
            <a href="{{ route('tenant.users.delete_confirm', ['user' => $targetUser->id]) }}" class="btn btn-outline-danger d-flex align-items-center gap-1 fw-semibold px-3">
                <i class="bi bi-trash"></i>
                <span>削除する</span>
            </a>
        @else
            <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1 fw-semibold px-3" disabled title="停止中のアカウントのみ削除可能です">
                <i class="bi bi-trash"></i>
                <span>削除する</span>
            </button>
        @endif
    </div>
</div>

<!-- 詳細内容 -->
<div class="card card-custom bg-white shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-badge-fill me-2 text-primary"></i>{{ $targetUser->user_name }} 様のアカウント詳細</h5>
        @if ($targetUser->status === 'active')
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">有効アカウント</span>
        @else
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">停止中アカウント</span>
        @endif
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">名前</div>
            <div class="col-md-9"><div class="detail-value fw-bold">{{ $targetUser->user_name }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">名前カナ</div>
            <div class="col-md-9"><div class="detail-value">{{ $targetUser->user_name_kana }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">ログインID (メールアドレス)</div>
            <div class="col-md-9"><div class="detail-value font-monospace">{{ $targetUser->email }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">部署</div>
            <div class="col-md-9"><div class="detail-value">{{ $targetUser->department ?? '-' }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">役職</div>
            <div class="col-md-9"><div class="detail-value">{{ $targetUser->position ?? '-' }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">アクセス権限</div>
            <div class="col-md-9">
                <div class="detail-value">
                    @if ($targetUser->role === 'admin')
                        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle me-2">自社管理者</span>
                        <span>(各種設定ポータルおよびすべての機能が操作可能)</span>
                    @else
                        <span class="badge bg-light text-dark border me-2">一般ユーザー</span>
                        <span>(受発注・取引閲覧のみ)</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">担当区分</div>
            <div class="col-md-9">
                <div class="detail-value">
                    @if ((int)($targetUser->is_all_assigned ?? 1) === 1)
                        <span class="badge bg-success-subtle text-success border border-success-subtle me-2">すべての取引先を担当</span>
                        <span class="small text-muted">(自社が連携するすべての取引先企業と取引・閲覧が可能です)</span>
                    @else
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2">個別担当分け</span>
                        <span class="small text-muted">(割り当てられた特定の取引先企業とのみ取引・閲覧が可能です)</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 detail-label">登録日時</div>
            <div class="col-md-9"><div class="detail-value font-monospace">{{ $targetUser->created_at ? $targetUser->created_at->format('Y-m-d H:i:s') : '-' }}</div></div>
        </div>
    </div>
</div>

<!-- 担当取引先企業カード -->
@if ((int)($targetUser->is_all_assigned ?? 1) === 0)
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-building-check me-2 text-primary"></i>担当取引先企業（全 {{ count($assignedLinkIds ?? []) }} 件）
            </h5>
            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#assignCompanyModal">
                <i class="bi bi-gear me-1"></i>担当企業を変更・割り当て
            </button>
        </div>
        <div class="card-body p-4">
            <div class="p-3 bg-light rounded small text-muted mb-4">
                <i class="bi bi-info-circle me-1"></i>本アカウントは「個別担当分け」に設定されています。以下で割り当てられた取引先のデータのみアクセス可能です。
            </div>

            <!-- 担当企業一覧グリッド -->
            @php
                $assignedLinkInts  = array_map('intval', $assignedLinkIds ?? []);
                $assignedLinksList = array_filter($allApprovedLinks ?? [], function($item) use ($assignedLinkInts) {
                    return in_array((int)$item['link_id'], $assignedLinkInts, true);
                });
            @endphp

            @if (empty($assignedLinksList))
                <div class="text-center py-4 text-muted small">現在割り当てられている取引先企業はありません。上のボタンから割り当てを行ってください。</div>
            @else
                <div class="row g-3">
                    @foreach ($assignedLinksList as $item)
                        <div class="col-12 col-md-6">
                            <div class="p-3 border rounded bg-white d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-1 flex-wrap">
                                        <span>{{ $item['partner_name'] }}</span>
                                        @if (!empty($item['is_authenticated']))
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">認証済</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        自社属性: 
                                        @if ($item['my_role'] === 'buyer')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">購入側(買)</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">販売側(売)</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $item['partner_full_address'] ?? $item['partner_address'] }}</div>
                                </div>
                                <a href="{{ url('/link/detail/' . $item['link_id']) }}" class="btn btn-sm btn-outline-secondary">詳細</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif

<!-- ボタンエリア -->
<div class="d-flex justify-content-between align-items-center mb-5">
    <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary px-4 py-2">
        <i class="bi bi-arrow-left me-1"></i>一覧へ戻る
    </a>
    <div class="d-flex gap-2">
        <a href="{{ route('tenant.users.password_edit', ['user' => $targetUser->id]) }}" class="btn btn-outline-warning text-dark fw-semibold px-4 py-2">
            <i class="bi bi-key me-1"></i>パスワードの再発行
        </a>
        <a href="{{ route('tenant.users.edit', ['user' => $targetUser->id]) }}" class="btn btn-primary px-4 py-2 fw-bold">
            <i class="bi bi-pencil-square me-1"></i>編集する
        </a>
        @if ($targetUser->status === 'suspended')
            <a href="{{ route('tenant.users.delete_confirm', ['user' => $targetUser->id]) }}" class="btn btn-outline-danger px-4 py-2 fw-bold">
                <i class="bi bi-trash me-1"></i>削除する
            </a>
        @else
            <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold" disabled title="停止中のアカウントのみ削除可能です">
                <i class="bi bi-trash me-1"></i>削除する
            </button>
        @endif
    </div>
</div>

<!-- 担当取引先企業 割り当て変更モーダル -->
<div class="modal fade" id="assignCompanyModal" tabindex="-1" aria-labelledby="assignCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('tenant.users.assign_links', ['user' => $targetUser->id]) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title h6 fw-bold m-0" id="assignCompanyModalLabel">
                        <i class="bi bi-building-gear text-primary me-2"></i>担当取引先企業の変更（{{ $targetUser->user_name }} 様）
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        {{ $targetUser->user_name }} 様が担当する取引先企業にチェックを入れて保存してください。<br>
                        ※チェックされた取引先とのみ見積・注文・取引データのアクセスが可能になります。
                    </p>

                    <!-- モーダル内リアルタイム検索窓 -->
                    <div class="input-group input-group-sm mb-3">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="modalCompanySearch" class="form-control" placeholder="取引先企業名・所在地で検索...">
                    </div>

                    <div class="list-group list-group-flush border rounded overflow-y-auto" style="max-height: 320px;" id="modalCompanyList">
                        @if (empty($allApprovedLinks))
                            <div class="p-3 text-center text-muted small">自社の承認済み連携企業が存在しません。先に連携管理から企業とつながりを構築してください。</div>
                        @else
                            @php
                                $assignedLinkInts = array_map('intval', $assignedLinkIds ?? []);
                            @endphp
                            @foreach ($allApprovedLinks as $link)
                                @php
                                    $isCheck = in_array((int)$link['link_id'], $assignedLinkInts, true);
                                @endphp
                                <label class="list-group-item d-flex align-items-center justify-content-between py-2 px-3 company-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input flex-shrink-0 m-0" type="checkbox" name="company_link_ids[]" value="{{ $link['link_id'] }}" {{ $isCheck ? 'checked' : '' }}>
                                        <div>
                                            <div class="fw-bold small text-dark d-flex align-items-center gap-1">
                                                <span>{{ $link['partner_name'] }}</span>
                                                @if (!empty($link['is_authenticated']))
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">認証済</span>
                                                @endif
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                自社：{{ $link['my_role'] === 'buyer' ? '購入側(買)' : '販売側(売)' }} | {{ $link['partner_full_address'] ?? $link['partner_address'] }}
                                            </div>
                                        </div>
                                    </div>
                                    @if ($isCheck)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">割当中</span>
                                    @else
                                        <span class="badge bg-light text-muted border">未選択</span>
                                    @endif
                                </label>
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i>担当企業を保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // モーダル内リアルタイム検索スクリプト（d-none クラスの切替）
    document.getElementById('modalCompanySearch')?.addEventListener('input', function(e) {
        const val = e.target.value.trim().toLowerCase();
        document.querySelectorAll('#modalCompanyList .company-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            if (val === '' || text.includes(val)) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
    });
</script>
@endsection
