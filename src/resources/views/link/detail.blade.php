@extends('layouts.tenant')

@section('title', 'MESHu - ' . $title)

@section('content')
<!-- パンくずリスト & タイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.link.index') }}" class="text-decoration-none">つながり管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">取引先企業詳細</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">取引先企業詳細</h1>
    </div>
    <div>
        <a href="{{ route('tenant.link.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>
            <span>つながり一覧へ戻る</span>
        </a>
    </div>
</div>

<!-- 企業ヘッダー概要カード -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-3 bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 64px; height: 64px; font-size: 1.8rem;">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h2 class="h4 fw-bold text-dark m-0">{{ $partnerCompany->name }}</h2>
                        @if ($partnerCompany->isAuthenticated())
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 align-middle" style="font-size: 0.75rem;" title="国税庁公表の法人番号に基づくMESHu認証企業">
                                <i class="bi bi-patch-check-fill text-primary me-1"></i>MESHu認証企業
                            </span>
                        @endif
                    </div>
                    <p class="text-muted small mb-0">
                        <span class="me-3"><i class="bi bi-building-gear me-1"></i>法人番号: <span class="font-monospace">{{ $partnerCompany->corporate_number }}</span></span>
                        <span><i class="bi bi-geo-alt me-1"></i>{{ $partnerCompany->getFullAddress() }}</span>
                    </p>
                </div>
            </div>

            <!-- 連携状態バッジ -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @if ($myRole === 'buyer')
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6"><i class="bi bi-cart-fill me-1"></i>自社：購入側(買)</span>
                @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6"><i class="bi bi-receipt-cutoff me-1"></i>自社：販売側(売)</span>
                @endif

                @if ($link->isApproved())
                    <span class="badge bg-success text-white px-3 py-2 fs-6"><i class="bi bi-check-circle-fill me-1"></i>連携中</span>
                @elseif ($link->isPending())
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6"><i class="bi bi-clock-history me-1"></i>承認待ち</span>
                @else
                    <span class="badge bg-danger text-white px-3 py-2 fs-6"><i class="bi bi-x-circle-fill me-1"></i>否認（停止中）</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- メインレイアウト (左8カラム / 右4カラム) -->
<div class="row g-4">
    <div class="col-12 col-lg-8">
        <!-- 0. 過去6か月の取引推移 (商流の熱力学指標) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-graph-up me-2 text-primary"></i>過去6か月の取引推移</h5>
                <span class="badge bg-{{ $thermoMetrics['last_order_badge'] }} {{ $thermoMetrics['last_order_badge'] === 'warning' ? 'text-dark' : 'text-white' }} font-monospace px-2 py-1">
                    最終依頼日: {{ $thermoMetrics['last_order_date'] }}
                </span>
            </div>
            <div class="card-body p-4">
                <!-- KPIサマリーカード 3枚 -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="text-muted small fw-semibold mb-1">
                                <i class="bi bi-file-earmark-text me-1 text-primary"></i>取引件数 (全伝票/注文回答)
                            </div>
                            <div class="h3 fw-bold text-dark m-0">{{ number_format($thermoMetrics['grand_total_count']) }} <span class="fs-6 fw-normal text-muted">件</span> <span class="text-secondary fw-light">/</span> {{ number_format($thermoMetrics['grand_success_count']) }} <span class="fs-6 fw-normal text-muted">件</span></div>
                            <div class="small text-muted mt-1">起票総数 / 成約件数</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="text-muted small fw-semibold mb-1">
                                <i class="bi bi-cash-coin me-1 text-success"></i>取引金額 (成約完了分)
                            </div>
                            <div class="h3 fw-bold text-success m-0">¥{{ number_format($thermoMetrics['grand_total_amount']) }}</div>
                            <div class="small text-muted mt-1">注文回答完了分の合計金額</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="text-muted small fw-semibold mb-1">
                                <i class="bi bi-pie-chart me-1 text-info"></i>成約率
                            </div>
                            <div class="h3 fw-bold text-info m-0">{{ number_format($thermoMetrics['grand_success_rate'], 1) }} <span class="fs-6 fw-normal text-muted">%</span></div>
                            <div class="small text-muted mt-1">成約件数 / 起票総数</div>
                        </div>
                    </div>
                </div>

                <!-- 月別推移テーブル -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th scope="col">年月</th>
                                <th scope="col" class="text-center">取引件数 (全伝票/注文回答)</th>
                                <th scope="col" class="text-end">取引金額 (成約分)</th>
                                <th scope="col" class="text-center">成約率</th>
                                <th scope="col" style="width: 25%;">推移</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($thermoMetrics['monthly_stats'] as $ms)
                                <tr>
                                    <td class="fw-bold font-monospace">{{ $ms['year_month'] }}</td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-dark px-2 font-monospace">{{ $ms['total_count'] }}件 / {{ $ms['success_count'] }}件</span></td>
                                    <td class="text-end font-monospace fw-semibold {{ $ms['total_amount'] > 0 ? 'text-dark' : 'text-muted' }}">
                                        ¥{{ number_format($ms['total_amount']) }}
                                    </td>
                                    <td class="text-center">
                                        @if ($ms['total_count'] > 0)
                                            <span class="badge {{ $ms['success_rate'] >= 50 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted' }}">
                                                {{ number_format($ms['success_rate'], 1) }}%
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $ms['bar_percent'] }}%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. 企業プロフィール情報 -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-info-circle me-2 text-primary"></i>企業基本情報</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold" style="width: 200px;">企業名（正式名称）</th>
                            <td class="pe-4 py-3 fw-semibold">
                                {{ $partnerCompany->name }}
                                @if ($partnerCompany->isAuthenticated())
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2" style="font-size: 0.72rem;">
                                        <i class="bi bi-patch-check-fill me-1"></i>認証企業
                                    </span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">認証ステータス</th>
                            <td class="pe-4 py-3">
                                @if ($partnerCompany->isAuthenticated())
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                                        <i class="bi bi-patch-check-fill me-1"></i>MESHu公認・実在認証済み
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 fs-6">
                                        <i class="bi bi-dash-circle me-1"></i>未認証
                                    </span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">法人番号</th>
                            <td class="pe-4 py-3 font-monospace">{{ $partnerCompany->corporate_number }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">所在地</th>
                            <td class="pe-4 py-3">{{ $partnerCompany->getFullAddress() }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">電話番号</th>
                            <td class="pe-4 py-3 font-monospace">{{ $partnerCompany->tel ?? '未登録' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">ホームページ</th>
                            <td class="pe-4 py-3">
                                @if (!empty($partnerCompany->homepage))
                                    <a href="{{ $partnerCompany->homepage }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none"><i class="bi bi-globe me-1"></i>{{ $partnerCompany->homepage }}</a>
                                @else
                                    <span class="text-muted">未設定</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. 社内担当従業員カード (user_assigned_links) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold fs-6">
                    <i class="bi bi-person-workspace me-2 text-primary"></i>社内担当従業員
                </h5>
                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#assignEmployeeModal">
                    <i class="bi bi-person-gear me-1"></i>担当従業員を変更
                </button>
            </div>
            <div class="card-body p-4">
                <div class="p-3 bg-light rounded small text-muted mb-4">
                    <i class="bi bi-info-circle me-1"></i>本取引先を担当する従業員です。「全担当」権限を持つ社員は設定に関わらずアクセス可能です。
                </div>

                <div class="row g-3">
                    @if (empty($assignedUsers))
                        <div class="col-12"><p class="text-muted small mb-0">個別割り当てされた担当従業員はいません。</p></div>
                    @else
                        @foreach ($assignedUsers as $emp)
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded bg-white shadow-sm-hover h-100">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 40px; height: 40px;">
                                            {{ mb_substr($emp['user_name'], 0, 2) }}
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-bold text-dark d-flex align-items-center justify-content-between gap-1">
                                                <span>{{ $emp['user_name'] }}</span>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">個別担当</span>
                                            </div>
                                            <div class="small text-muted">{{ $emp['department'] ?? '所属未設定' }} / {{ $emp['position'] ?? '役職なし' }}</div>
                                            <div class="small text-muted font-monospace text-truncate">{{ $emp['email'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 右サイド操作エリア (4カラム) -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-gear me-2 text-primary"></i>連携操作・管理</h5>
            </div>
            <div class="card-body p-4">
                @if ($myRole === 'seller')
                    <p class="small text-muted mb-3">販売側企業として、取引許可の切替・否認を実行できます。</p>
                    @if ($link->isApproved())
                        <button type="button" class="btn btn-outline-danger w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#denyLinkModal">
                            <i class="bi bi-x-circle me-1"></i>この企業との連携を停止する
                        </button>
                    @else
                        <button type="button" class="btn btn-success w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#approveLinkModal">
                            <i class="bi bi-check-circle me-1"></i>連携を承認・再開する
                        </button>
                    @endif
                @else
                    <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>購入側(買)のステータス変更は相手販売側企業の操作により行われます。</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 担当従業員割り当て変更モーダル (user_assigned_links) -->
<div class="modal fade" id="assignEmployeeModal" tabindex="-1" aria-labelledby="assignEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('tenant.link.assign_users', ['link_id' => $link->id]) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title h6 fw-bold m-0" id="assignEmployeeModalLabel">
                        <i class="bi bi-person-gear text-primary me-2"></i>担当従業員の変更（{{ $partnerCompany->name }}）
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        本取引先を担当する社員（担当分け設定のユーザー）を選択してください。<br>
                        ※「全担当」権限を持つ従業員は設定に関わらず全取引先を参照可能です。
                    </p>

                    <!-- モーダル内リアルタイム検索窓 -->
                    <div class="input-group input-group-sm mb-3">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="modalEmpSearch" class="form-control" placeholder="社員名・部署名で検索...">
                    </div>

                    <div class="list-group list-group-flush border rounded overflow-y-auto" style="max-height: 300px;" id="modalEmpList">
                        @if ($companyUsers->isEmpty())
                            <div class="p-3 text-center text-muted small">
                                <i class="bi bi-info-circle me-1"></i>「個別担当分け」に設定されている従業員はいません。<br>（「全担当」設定の従業員は設定不要で全取引先を参照可能です）
                            </div>
                        @else
                            @php 
                            $assignedUserInts = array_map('intval', $assignedUserIds ?? []);
                            @endphp
                            @foreach ($companyUsers as $u)
                                @php
                                $isAssigned = in_array((int)$u->id, $assignedUserInts, true);
                                @endphp
                                <label class="list-group-item d-flex align-items-center justify-content-between py-2 px-3 emp-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input flex-shrink-0 m-0" type="checkbox" name="user_ids[]" value="{{ $u->id }}" {{ $isAssigned ? 'checked' : '' }}>
                                        <div>
                                            <div class="fw-bold small text-dark emp-name">{{ $u->user_name }}</div>
                                            <div class="text-muted emp-dept" style="font-size: 0.75rem;">{{ $u->department ?? '部署なし' }} / {{ $u->position ?? '役職なし' }} ({{ $u->email }})</div>
                                        </div>
                                    </div>
                                    @if ($isAssigned)
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
                        <i class="bi bi-check-lg me-1"></i>担当設定を保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 承認確認モーダル -->
<div class="modal fade" id="approveLinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('tenant.link.status', ['link_id' => $link->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="approved">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title h6 fw-bold m-0 text-success">
                        <i class="bi bi-check-circle-fill me-2"></i>連携承認の確認
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">
                        <strong>{{ $partnerCompany->name }}</strong> からの連携依頼を「承認」しますか？
                    </p>
                    <div class="p-3 bg-light rounded small text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>承認すると、相手企業（購入側）との相互の見積・注文取引が開始可能になります。
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i>承認を実行する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 否認・停止確認モーダル -->
<div class="modal fade" id="denyLinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('tenant.link.status', ['link_id' => $link->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="denied">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title h6 fw-bold m-0 text-danger">
                        <i class="bi bi-x-circle-fill me-2"></i>連携停止・否認の確認
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">
                        <strong>{{ $partnerCompany->name }}</strong> との連携を「否認（停止）」しますか？
                    </p>
                    <div class="p-3 bg-warning-subtle text-warning-emphasis rounded small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>連携を停止すると、新規の受発注取引やデータの参照が一時遮断されます。（※いつでも再開可能です）
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-danger px-4 fw-semibold">
                        <i class="bi bi-x-lg me-1"></i>否認・停止を実行
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // モーダル内リアルタイム検索スクリプト
    document.getElementById('modalEmpSearch')?.addEventListener('input', function(e) {
        const val = e.target.value.trim().toLowerCase();
        document.querySelectorAll('#modalEmpList .emp-item').forEach(item => {
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
