@extends('layouts.tenant')

@section('title', $currentCategory['title'] . ' - 操作マニュアル')

@section('content')
<div class="container-fluid p-0">
    <!-- パンくずナビゲーション -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ダッシュボード</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.manual.index') }}" class="text-decoration-none">操作マニュアル</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $currentCategory['title'] }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- 左側：カテゴリ一覧ナビゲーション -->
        <div class="col-12 col-lg-3">
            <div class="card card-custom bg-white border-0 shadow-sm p-3 sticky-top" style="top: 80px; z-index: 10;">
                <h6 class="fw-bold text-muted small text-uppercase mb-3 px-2">
                    <i class="bi bi-journal-bookmark me-1"></i> マニュアル目次
                </h6>
                <div class="nav flex-column nav-pills">
                    @foreach ($categories as $catKey => $cat)
                        <a href="{{ route('tenant.manual.category', ['category' => $catKey]) }}" 
                           class="nav-link py-2 px-3 rounded mb-1 d-flex align-items-center gap-2 {{ $catKey === $currentCategory['id'] ? 'active bg-primary text-white fw-bold' : 'text-dark hover-bg-light' }}">
                            <i class="bi {{ $cat['icon'] }}"></i>
                            <span class="small">{{ $cat['title'] }}</span>
                        </a>
                    @endforeach
                </div>

                <hr class="my-3 text-muted">

                <div class="px-2">
                    <a href="{{ route('tenant.manual.faq') }}?category={{ $currentCategory['id'] }}" class="btn btn-outline-secondary btn-sm w-100 text-start mb-2">
                        <i class="bi bi-question-circle me-1"></i> このカテゴリのFAQ
                    </a>
                    <a href="{{ route('tenant.manual.index') }}" class="btn btn-light btn-sm w-100 text-start">
                        <i class="bi bi-arrow-left me-1"></i> マニュアル一覧へ
                    </a>
                </div>
            </div>
        </div>

        <!-- 右側：マニュアル詳細コンテンツ -->
        <div class="col-12 col-lg-9">
            <!-- カテゴリヘッダーカード -->
            <div class="card card-custom bg-white border-0 shadow-sm p-4 mb-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="bg-primary-subtle text-primary rounded-3 p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                        <i class="bi {{ $currentCategory['icon'] }} fs-2"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-secondary">{{ $currentCategory['badge'] }}</span>
                        </div>
                        <h1 class="h3 fw-bold text-dark mb-2">{{ $currentCategory['title'] }}</h1>
                        <p class="text-muted m-0 lead" style="font-size: 1rem;">{{ $currentCategory['description'] }}</p>
                    </div>
                </div>

                <!-- ページ内目次アンカー -->
                <div class="mt-4 pt-3 border-top">
                    <div class="small fw-bold text-muted mb-2">このページの目次:</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($currentCategory['sections'] as $sec)
                            <a href="#{{ $sec['id'] }}" class="badge bg-light text-dark border text-decoration-none py-2 px-3 fw-normal hover-primary">
                                {{ $sec['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 各セクション詳細 -->
            @foreach ($currentCategory['sections'] as $sec)
                <div class="card card-custom bg-white border-0 shadow-sm p-4 p-md-5 mb-4 scroll-margin-top" id="{{ $sec['id'] }}">
                    <h2 class="h4 fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle text-primary"></i>
                        <span>{{ $sec['title'] }}</span>
                    </h2>

                    <div class="text-secondary leading-relaxed mb-4" style="line-height: 1.8; font-size: 1.05rem;">
                        {!! nl2br(e($sec['content'])) !!}
                    </div>

                    <!-- TIPS / 注意点ボックス -->
                    @if (!empty($sec['tips']))
                        <div class="alert alert-primary bg-primary-subtle border-0 rounded-3 p-3 mb-4 d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill text-primary fs-5 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block text-primary small fw-bold mb-1">ワンポイントアドバイス</strong>
                                <span class="small text-dark">{{ $sec['tips'] }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- 関連機能へのクイックリンクボタン判定 -->
                    <div class="pt-2 d-flex flex-wrap gap-2">
                        @if ($currentCategory['id'] === 'getting-started')
                            @if ($sec['id'] === 'gs-profile')
                                <a href="{{ route('tenant.profile') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-person-fill me-1"></i> マイプロフィールを確認する
                                </a>
                            @elseif ($sec['id'] === 'gs-dashboard')
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-grid-1x2-fill me-1"></i> ダッシュボードを開く
                                </a>
                            @endif
                        @elseif ($currentCategory['id'] === 'links')
                            @if ($sec['id'] === 'link-search')
                                <a href="{{ route('tenant.link.search') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-search me-1"></i> 企業を検索してつながる
                                </a>
                            @elseif ($sec['id'] === 'link-approve')
                                <a href="{{ route('tenant.link.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-share me-1"></i> つながり管理を開く
                                </a>
                            @elseif ($sec['id'] === 'link-invite')
                                <a href="{{ route('tenant.invitation.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-envelope-plus me-1"></i> 招待先管理を開く
                                </a>
                            @endif
                        @elseif ($currentCategory['id'] === 'transactions')
                            @if (str_contains($sec['id'], 'buy'))
                                <a href="{{ route('tenant.buy.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-cart me-1"></i> 購入ポータルを開く
                                </a>
                            @else
                                <a href="{{ route('tenant.sell.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-receipt-cutoff me-1"></i> 販売ポータルを開く
                                </a>
                            @endif
                        @elseif ($currentCategory['id'] === 'chat-notices')
                            @if (str_contains($sec['id'], 'chat'))
                                <a href="{{ route('tenant.chat.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-chat-dots-fill me-1"></i> チャット一覧を開く
                                </a>
                            @else
                                <a href="{{ route('tenant.notice.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-megaphone-fill me-1"></i> お知らせ一覧を開く
                                </a>
                            @endif
                        @elseif ($currentCategory['id'] === 'company-users')
                            @if ($sec['id'] === 'cu-company')
                                <a href="{{ url('/settings/company') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-building me-1"></i> 自社情報管理を開く
                                </a>
                            @else
                                <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-people-fill me-1"></i> 従業員管理を開く
                                </a>
                            @endif
                        @elseif ($currentCategory['id'] === 'security-contact')
                            <a href="{{ route('tenant.contact') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-headset me-1"></i> 事務局へ問い合わせる
                            </a>
                        @endif

                        <a href="#top" class="btn btn-link btn-sm text-muted ms-auto">
                            <i class="bi bi-arrow-up"></i> ページ上部へ
                        </a>
                    </div>
                </div>
            @endforeach

            <!-- ページ下部サポート導線カード -->
            <div class="card card-custom bg-light border-0 p-4 mb-4 text-center">
                <h5 class="fw-bold text-dark mb-2">お探しの情報は見つかりましたか？</h5>
                <p class="text-muted small mb-3">
                    解決しない疑問がある場合は、よくあるご質問（FAQ）を確認するか、事務局サポート窓口へ直接お問い合わせください。
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('tenant.manual.faq') }}?category={{ $currentCategory['id'] }}" class="btn btn-outline-primary fw-semibold">
                        <i class="bi bi-question-circle me-1"></i> よくあるご質問 (FAQ)
                    </a>
                    <a href="{{ route('tenant.contact') }}" class="btn btn-primary fw-semibold">
                        <i class="bi bi-envelope-fill me-1"></i> 事務局へ問い合わせる
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scroll-margin-top {
    scroll-margin-top: 90px;
}
.hover-bg-light:hover {
    background-color: #f8fafc;
}
.hover-primary:hover {
    color: #2563eb !important;
    border-color: #2563eb !important;
}
</style>
@endsection
