@extends('layouts.tenant')

@section('title', 'よくあるご質問 (FAQ) - 操作マニュアル')

@section('content')
<div class="container-fluid p-0">
    <!-- パンくずナビゲーション -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ダッシュボード</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.manual.index') }}" class="text-decoration-none">操作マニュアル</a></li>
            <li class="breadcrumb-item active" aria-current="page">よくあるご質問 (FAQ)</li>
        </ol>
    </nav>

    <!-- ヘッダーエリア -->
    <div class="card card-custom border-0 shadow-sm bg-white p-4 p-md-5 mb-4">
        <div class="row align-items-center">
            <div class="col-12 col-md-8">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 mb-2 rounded-pill">
                    <i class="bi bi-patch-question me-1"></i> FAQ & 解決サポート
                </span>
                <h1 class="h2 fw-bold text-dark mb-2">よくあるご質問 (FAQ)</h1>
                <p class="text-muted m-0">
                    ユーザー企業様から多く寄せられる質問とその回答をカテゴリ別に掲載しています。
                </p>
            </div>
            <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
                <a href="{{ route('tenant.manual.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-book me-1"></i> マニュアル目次へ戻る
                </a>
            </div>
        </div>

        <!-- 検索フォーム -->
        <form action="{{ route('tenant.manual.faq') }}" method="GET" class="mt-4 pt-3 border-top">
            @if ($targetCategory !== '')
                <input type="hidden" name="category" value="{{ $targetCategory }}">
            @endif
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="q" class="form-control border-start-0 py-2" placeholder="FAQを検索（キーワードを入力）" value="{{ $keyword }}" aria-label="FAQ検索">
                @if ($keyword !== '')
                    <a href="{{ route('tenant.manual.faq', $targetCategory ? ['category' => $targetCategory] : []) }}" class="btn btn-outline-secondary border-start-0 border-end-0" title="クリア">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                @endif
                <button class="btn btn-primary px-4 fw-semibold" type="submit">検索</button>
            </div>
        </form>

        <!-- カテゴリタブ切り替え -->
        <div class="mt-3 d-flex flex-wrap gap-2">
            <a href="{{ route('tenant.manual.faq', $keyword ? ['q' => $keyword] : []) }}" 
               class="badge {{ $targetCategory === '' ? 'bg-primary text-white' : 'bg-light text-dark border' }} text-decoration-none py-2 px-3 fw-normal hover-primary">
                すべて ({{ count(App\Http\Controllers\Tenant\ManualController::getFaqList()) }})
            </a>
            @foreach ($categories as $catKey => $cat)
                @php
                    $catCount = count(array_filter(App\Http\Controllers\Tenant\ManualController::getFaqList(), fn($f) => $f['category'] === $catKey));
                @endphp
                @if ($catCount > 0)
                    <a href="{{ route('tenant.manual.faq', array_merge(['category' => $catKey], $keyword ? ['q' => $keyword] : [])) }}" 
                       class="badge {{ $targetCategory === $catKey ? 'bg-primary text-white' : 'bg-light text-dark border' }} text-decoration-none py-2 px-3 fw-normal hover-primary">
                        {{ $cat['title'] }} ({{ $catCount }})
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- FAQアコーディオン一覧 -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-lg-8">
            @if (count($faqList) > 0)
                <div class="accordion shadow-sm" id="faqAccordion">
                    @foreach ($faqList as $index => $faq)
                        <div class="accordion-item border mb-3 rounded overflow-hidden scroll-margin-top" id="{{ $faq['id'] }}">
                            <h2 class="accordion-header" id="heading-{{ $faq['id'] }}">
                                <button class="accordion-button collapsed fw-bold text-dark py-3 px-4 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $faq['id'] }}" aria-expanded="false" aria-controls="collapse-{{ $faq['id'] }}">
                                    <div class="d-flex align-items-center gap-2 text-start flex-grow-1 pe-2">
                                        <span class="badge bg-secondary-subtle text-secondary small flex-shrink-0">{{ $faq['category_title'] }}</span>
                                        <span class="fs-6">Q. {{ $faq['question'] }}</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse-{{ $faq['id'] }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $faq['id'] }}" data-bs-parent="#faqAccordion">
                                <div class="accordion-body p-4 bg-light border-top">
                                    <div class="text-secondary leading-relaxed mb-3" style="line-height: 1.8;">
                                        {!! nl2br(e($faq['answer'])) !!}
                                    </div>

                                    @if (!empty($faq['related_manual']))
                                        <div class="pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <span class="small text-muted">関連する操作マニュアル:</span>
                                            <a href="{{ route('tenant.manual.category', ['category' => $faq['related_manual']]) }}#{{ $faq['related_section'] ?? '' }}" class="btn btn-outline-primary btn-sm fw-semibold">
                                                <i class="bi bi-book-half me-1"></i> 詳しい手順を確認する
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="card card-custom bg-white p-5 text-center shadow-sm">
                    <i class="bi bi-question-circle fs-1 text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark">該当する質問が見つかりませんでした</h5>
                    <p class="text-muted small mb-3">
                        別のキーワードで検索するか、すべてのカテゴリを表示してください。
                    </p>
                    <div>
                        <a href="{{ route('tenant.manual.faq') }}" class="btn btn-outline-primary btn-sm">FAQ一覧をリセット</a>
                    </div>
                </div>
            @endif
        </div>

        <!-- 右側：サポート窓口案内 -->
        <div class="col-12 col-lg-4">
            <div class="card card-custom bg-white border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="bi bi-headset text-primary me-2"></i>問題が解決しない場合
                </h5>
                <p class="small text-muted mb-3">
                    FAQや操作マニュアルで解決しないご質問や、システムトラブル、ご要望は事務局までお気軽にお問い合わせください。
                </p>
                <div class="p-3 bg-light rounded border mb-3 small">
                    <div class="mb-1"><strong>受付窓口:</strong> MESHu 運営事務局</div>
                    <div class="mb-1"><strong>対応時間:</strong> 平日 9:00 〜 18:00</div>
                    <div class="text-muted">（土日祝・年末年始を除く）</div>
                </div>
                <a href="{{ route('tenant.contact') }}" class="btn btn-primary w-100 fw-bold">
                    <i class="bi bi-envelope-fill me-1"></i> 事務局へ問い合わせる
                </a>
            </div>

            <div class="card card-custom bg-white border-0 shadow-sm p-4">
                <h6 class="fw-bold text-dark mb-2">
                    <i class="bi bi-bookmark-check text-success me-2"></i>よく見られているカテゴリ
                </h6>
                <ul class="list-unstyled small m-0">
                    @foreach ($categories as $catKey => $cat)
                        <li class="py-2 border-bottom d-flex justify-content-between align-items-center">
                            <a href="{{ route('tenant.manual.category', ['category' => $catKey]) }}" class="text-decoration-none text-dark hover-primary">
                                <i class="bi {{ $cat['icon'] }} me-1 text-primary"></i> {{ $cat['title'] }}
                            </a>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.scroll-margin-top {
    scroll-margin-top: 90px;
}
.hover-primary:hover {
    color: #2563eb !important;
    border-color: #2563eb !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // URLハッシュ（例: #faq-link-pending）がある場合、自動的に対象のアコーディオンを展開
    const hash = window.location.hash;
    if (hash) {
        const targetItem = document.querySelector(hash);
        if (targetItem) {
            const collapseElem = targetItem.querySelector('.accordion-collapse');
            const buttonElem = targetItem.querySelector('.accordion-button');
            if (collapseElem && buttonElem) {
                collapseElem.classList.add('show');
                buttonElem.classList.remove('collapsed');
                buttonElem.setAttribute('aria-expanded', 'true');
                targetItem.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }
});
</script>
@endsection
