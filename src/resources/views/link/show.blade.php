@extends('layouts.tenant')

@section('title', 'MESHu - ' . $title)

@section('content')
<!-- パンくずリスト & 戻るボタン -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.link.index') }}" class="text-decoration-none">つながり管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.link.search') }}" class="text-decoration-none">企業検索</a></li>
                <li class="breadcrumb-item active" aria-current="page">連携申請の確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">連携申請の確認</h1>
    </div>
    <div>
        <a href="{{ route('tenant.link.search') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>
            <span>検索結果へ戻る</span>
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
                        <h2 class="h4 fw-bold text-dark m-0">{{ $targetCompany->name }}</h2>
                        @if ($targetCompany->isAuthenticated())
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 align-middle" style="font-size: 0.75rem;" title="事務局により実在が認定されたMESHu認証企業">
                                <i class="bi bi-patch-check-fill text-primary me-1"></i>MESHu認証企業
                            </span>
                        @endif
                    </div>
                    <p class="text-muted small mb-0">
                        <span class="me-3"><i class="bi bi-building-gear me-1"></i>法人番号: <span class="font-monospace">{{ $targetCompany->corporate_number }}</span></span>
                        <span><i class="bi bi-geo-alt me-1"></i>{{ $targetCompany->getFullAddress() }}</span>
                    </p>
                </div>
            </div>

            <!-- アクションエリア（連携を依頼するボタン） -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button type="button" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm fs-6" data-bs-toggle="modal" data-bs-target="#requestLinkModal">
                    <i class="bi bi-send-plus-fill me-2"></i>この企業に連携を依頼する
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 企業詳細情報テーブル -->
<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-info-circle me-2 text-primary"></i>企業基本情報</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold" style="width: 200px;">企業名（正式名称）</th>
                            <td class="pe-4 py-3 fw-semibold">{{ $targetCompany->name }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">認証ステータス</th>
                            <td class="pe-4 py-3">
                                @if ($targetCompany->isAuthenticated())
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
                            <td class="pe-4 py-3 font-monospace">{{ $targetCompany->corporate_number }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">所在地</th>
                            <td class="pe-4 py-3">{{ $targetCompany->getFullAddress() }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">代表電話番号</th>
                            <td class="pe-4 py-3 font-monospace">{{ $targetCompany->tel ?? '未設定' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light ps-4 py-3 text-muted fw-semibold">ホームページ</th>
                            <td class="pe-4 py-3">
                                @if (!empty($targetCompany->homepage))
                                    <a href="{{ $targetCompany->homepage }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none"><i class="bi bi-globe me-1"></i>{{ $targetCompany->homepage }}</a>
                                @else
                                    <span class="text-muted">未設定</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 右サイド: 申請ルール -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-primary me-2"></i>連携依頼について</h6>
                <ul class="small text-muted ps-3 mb-0 d-flex flex-column gap-2">
                    <li>連携申請を送信すると、自社は<strong>「購入側（買手）」</strong>、相手企業は<strong>「販売側（売手）」</strong>として登録されます。</li>
                    <li>申請後、相手企業管理者に承認依頼メールが送信されます。</li>
                    <li>相手企業が「承認」すると、相互の見積・注文取引が開始可能になります。</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 連携依頼送信 確認モーダル -->
<div class="modal fade" id="requestLinkModal" tabindex="-1" aria-labelledby="requestLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('tenant.link.request') }}" method="POST">
                @csrf
                <input type="hidden" name="target_company_id" value="{{ $targetCompany->id }}">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-header-title modal-title h6 fw-bold m-0" id="requestLinkModalLabel">
                        <i class="bi bi-send-plus-fill text-primary me-2"></i>連携依頼の送信確認
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">
                        <strong>{{ $targetCompany->name }}</strong> へ連携依頼を送信します。よろしいですか？
                    </p>
                    <div class="p-3 bg-light rounded small text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>送信後、相手企業の管理者へ案内メールが自動送信されます。
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-send me-1"></i>送信する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
