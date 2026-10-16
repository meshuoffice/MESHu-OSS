@extends('layouts.tenant')

@section('title', 'ヘルプ・操作マニュアル')

@section('content')
<div class="container-fluid p-0">
    <!-- パンくずナビゲーション -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ダッシュボード</a></li>
            <li class="breadcrumb-item active" aria-current="page">ヘルプ・操作マニュアル</li>
        </ol>
    </nav>

    <!-- ヒーロー・検索エリア -->
    <div class="card card-custom border-0 shadow-sm bg-gradient text-white mb-5" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
        <div class="card-body p-4 p-md-5 text-center">
            <span class="badge bg-white text-primary fw-bold px-3 py-2 mb-3 rounded-pill">
                <i class="bi bi-book-half me-1"></i> MESHu ガイド＆サポート
            </span>
            <h1 class="h2 fw-bold text-white mb-3">MESHu ヘルプ・操作マニュアル</h1>
            <p class="text-light lead mx-auto mb-4" style="max-width: 680px; opacity: 0.95;">
                企業間取引、つながり連携、チャット、各種設定の手順やよくある質問をご確認いただけます。
            </p>

            <!-- 検索フォーム -->
            <form action="{{ route('tenant.manual.index') }}" method="GET" class="mx-auto" style="max-width: 640px;">
                <div class="input-group input-group-lg shadow-sm">
                    <span class="input-group-text bg-white border-0 text-muted ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-0 py-3" placeholder="キーワードでマニュアル・FAQを検索（例: 注文, 見積, 承認, 担当割当）" value="{{ $keyword }}" aria-label="マニュアル検索">
                    @if ($keyword !== '')
                        <a href="{{ route('tenant.manual.index') }}" class="btn btn-white bg-white border-0 text-muted d-flex align-items-center" title="クリア">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                    <button class="btn btn-warning fw-bold px-4 text-dark" type="submit">検索</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 検索結果表示エリア -->
    @if ($keyword !== '')
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 fw-bold text-dark m-0">
                    <i class="bi bi-search text-primary me-2"></i>「{{ $keyword }}」の検索結果（{{ count($searchResults) }}件）
                </h2>
                <a href="{{ route('tenant.manual.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i>検索をクリア
                </a>
            </div>

            @if (count($searchResults) > 0)
                <div class="list-group shadow-sm border-0">
                    @foreach ($searchResults as $item)
                        <a href="{{ $item['url'] }}" class="list-group-item list-group-item-action p-4 border mb-2 rounded">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if ($item['type'] === 'manual')
                                    <span class="badge bg-primary-subtle text-primary fw-bold">操作マニュアル</span>
                                @else
                                    <span class="badge bg-success-subtle text-success fw-bold">よくある質問 (FAQ)</span>
                                @endif
                                <span class="badge bg-secondary-subtle text-secondary">{{ $item['category_title'] }}</span>
                            </div>
                            <h3 class="h5 fw-bold text-dark mb-2">{{ $item['title'] }}</h3>
                            <p class="text-muted small mb-0">{{ $item['excerpt'] }}</p>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="card card-custom bg-white p-5 text-center shadow-sm">
                    <i class="bi bi-search fs-1 text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark">該当する項目が見つかりませんでした</h5>
                    <p class="text-muted small mb-3">
                        別のキーワードで検索するか、カテゴリ一覧またはFAQからご確認ください。
                    </p>
                    <div>
                        <a href="{{ route('tenant.manual.index') }}" class="btn btn-outline-primary btn-sm">マニュアル一覧に戻る</a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- カテゴリ別マニュアル一覧 -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 fw-bold text-dark m-0">
                    <i class="bi bi-grid-fill text-primary me-2"></i>機能別 操作マニュアル
                </h2>
                <p class="text-muted small m-0 mt-1">機能ごとに詳しい操作手順と活用ポイントを掲載しています。</p>
            </div>
        </div>

        <div class="row g-4">
            @foreach ($categories as $catKey => $cat)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-custom h-100 bg-white border-0 shadow-sm p-4 d-flex flex-column justify-content-between transition-hover">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-primary-subtle text-primary rounded-3 p-3 d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                    <i class="bi {{ $cat['icon'] }} fs-3"></i>
                                </div>
                                <span class="badge bg-light text-secondary border">{{ $cat['badge'] }}</span>
                            </div>
                            <h3 class="h5 fw-bold text-dark mb-2">
                                <a href="{{ route('tenant.manual.category', ['category' => $catKey]) }}" class="text-decoration-none text-dark hover-primary">
                                    {{ $cat['title'] }}
                                </a>
                            </h3>
                            <p class="text-muted small mb-3">{{ $cat['description'] }}</p>

                            <ul class="list-unstyled small mb-4">
                                @foreach ($cat['sections'] as $sec)
                                    <li class="mb-2 text-truncate">
                                        <a href="{{ route('tenant.manual.category', ['category' => $catKey]) }}#{{ $sec['id'] }}" class="text-secondary text-decoration-none">
                                            <i class="bi bi-chevron-right text-primary me-1" style="font-size: 0.75rem;"></i>{{ $sec['title'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <a href="{{ route('tenant.manual.category', ['category' => $catKey]) }}" class="btn btn-outline-primary btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                                <span>マニュアルを見る</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- おすすめFAQ＆リンクエリア -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-lg-8">
            <div class="card card-custom bg-white border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold text-dark m-0">
                        <i class="bi bi-question-circle-fill text-primary me-2"></i>よくあるご質問 (FAQ) ピックアップ
                    </h2>
                    <a href="{{ route('tenant.manual.faq') }}" class="btn btn-sm btn-outline-primary fw-semibold">
                        FAQ全件を見る <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                </div>
                <div class="list-group list-group-flush border-top">
                    @foreach ($faqList as $faq)
                        <div class="list-group-item py-3 px-0">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-secondary-subtle text-secondary mt-1">{{ $faq['category_title'] }}</span>
                                <div class="flex-grow-1">
                                    <a href="{{ route('tenant.manual.faq') }}#{{ $faq['id'] }}" class="fw-bold text-dark text-decoration-none d-block mb-1 hover-primary">
                                        Q. {{ $faq['question'] }}
                                    </a>
                                    <p class="text-muted small mb-0 text-truncate" style="max-width: 600px;">
                                        {{ $faq['answer'] }}
                                    </p>
                                </div>
                                <i class="bi bi-chevron-right text-muted small ms-2 mt-1"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 事務局サポート案内 -->
        <div class="col-12 col-lg-4">
            <div class="card card-custom bg-light border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-headset fs-3 text-primary"></i>
                        <h2 class="h5 fw-bold text-dark m-0">事務局サポート窓口</h2>
                    </div>
                    <p class="small text-muted mb-3">
                        操作方法でご不明な点がある場合や、不具合・認証バッジの申請などは、事務局へ直接お問い合わせください。
                    </p>
                    <div class="p-3 bg-white rounded border mb-3 small">
                        <div class="mb-1"><strong>受付時間:</strong> 平日 9:00 〜 18:00</div>
                        <div class="text-muted">土日祝・年末年始を除く</div>
                    </div>
                </div>
                <a href="{{ route('tenant.contact') }}" class="btn btn-primary w-100 fw-bold">
                    <i class="bi bi-envelope-fill me-1"></i> 事務局へ問い合わせる
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.transition-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.transition-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
.hover-primary:hover {
    color: #2563eb !important;
}
</style>
@endsection
