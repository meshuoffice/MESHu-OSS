@php
$user = Auth::guard('web')->user();
$company = $currentCompany ?? ($user ? $user->company : null);
$companyName = $company->name ?? '所属企業';
$userName = $user->user_name ?? 'ユーザー';
$userRole = $user->role ?? 'staff';
$urlCode = $company->url_code ?? '';
$activeMenu = $activeMenu ?? '';
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu（メッシュ） - ユーザー企業向けポータル">
    <title>@yield('title', 'MESHu')</title>

    <!-- MESHu 統一 Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets_core/css/core_custom.css') }}">
    @yield('styles')
</head>
<body>

    <!-- ヘッダー (上部ナビゲーション) -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top px-3">
        <div class="container-fluid p-0">
            <button class="btn btn-link d-lg-none me-2 text-dark" type="button" id="sidebarToggle" aria-label="メニューを開く">
                <i class="bi bi-list fs-3"></i>
            </button>

            <a class="brand-logo me-auto" href="{{ route('dashboard') }}">
                MESH<span class="logo-accent">u</span>
            </a>

            <div class="d-flex align-items-center ms-auto">
                <div class="me-3 d-none d-md-flex align-items-center gap-2">
                    <span class="badge bg-secondary-subtle text-secondary m-0 font-monospace">自社</span>
                    <span class="fw-semibold text-dark">{{ $companyName }}</span>
                </div>

                <!-- ユーザーメニュー -->
                <div class="dropdown">
                    <button class="btn btn-link text-dark p-0 border-0 d-flex align-items-center gap-2 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <span class="fw-bold small">{{ mb_substr($userName, 0, 2) }}</span>
                        </div>
                        <span class="d-none d-lg-inline text-muted small">{{ $userName }} 様</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="{{ url('/profile') }}"><i class="bi bi-person me-2"></i>マイプロフィール</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="{{ route('logout') }}"><i class="bi bi-box-arrow-right me-2"></i>ログアウト</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- サイドバーメニュー -->
    <aside id="sidebarMenu" class="sidebar-custom">
        <div class="px-3 mb-3 d-md-none">
            <div class="p-2 bg-light rounded">
                <div class="fw-bold small text-truncate">{{ $companyName }}</div>
            </div>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link-custom {{ $activeMenu === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>ダッシュボード</span>
            </a>
            <a class="nav-link-custom {{ $activeMenu === 'chat' ? 'active' : '' }}" href="{{ url('/chat') }}">
                <i class="bi bi-chat-dots-fill"></i>
                <span>チャット</span>
            </a>
            <a class="nav-link-custom {{ $activeMenu === 'buy' ? 'active' : '' }}" href="{{ url('/buy') }}">
                <i class="bi bi-cart-fill"></i>
                <span>購入 (買手)</span>
            </a>
            <a class="nav-link-custom {{ $activeMenu === 'sell' ? 'active' : '' }}" href="{{ url('/sell') }}">
                <i class="bi bi-receipt-cutoff"></i>
                <span>販売 (売手)</span>
            </a>
            <a class="nav-link-custom {{ $activeMenu === 'notice' ? 'active' : '' }}" href="{{ url('/notice') }}">
                <i class="bi bi-megaphone-fill"></i>
                <span>お知らせ</span>
            </a>
            <hr class="mx-3 my-2 text-muted">
            @if ($userRole === 'admin')
                <a class="nav-link-custom {{ $activeMenu === 'settings' ? 'active' : '' }}" href="{{ url('/settings') }}">
                    <i class="bi bi-gear-fill"></i>
                    <span>各種設定</span>
                </a>
            @endif
            <a class="nav-link-custom {{ $activeMenu === 'profile' ? 'active' : '' }}" href="{{ url('/profile') }}">
                <i class="bi bi-person-fill"></i>
                <span>マイプロフィール</span>
            </a>
            <a class="nav-link-custom {{ $activeMenu === 'contact' ? 'active' : '' }}" href="{{ url('/contact') }}">
                <i class="bi bi-headset"></i>
                <span>事務局へ問合せ</span>
            </a>
            <hr class="mx-3 my-2 text-muted">
            <a class="nav-link-custom text-danger" href="{{ route('logout') }}">
                <i class="bi bi-box-arrow-right"></i>
                <span>ログアウト</span>
            </a>
        </nav>
    </aside>

    <!-- メインコンテンツ -->
    <main class="main-content">
        <div class="container-fluid p-4">
            @if (session('status'))
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- フッター -->
    <footer class="text-center py-3 text-muted small border-top bg-white">
        &copy; 2026 MESHu Platform. All rights reserved.
    </footer>

    <!-- Bootstrap 5 JS Bundle (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets_core/js/core_app.js') }}"></script>
    @yield('scripts')
</body>
</html>
