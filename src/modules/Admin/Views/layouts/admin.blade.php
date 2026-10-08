@php
$owner = Auth::guard('admin')->user();
$currentOwnerName = $owner->name ?? '事務局管理者';
$currentOwnerRole = $owner->role ?? 'admin';
$roleLabels = [
    'admin'    => 'システム管理者',
    'reviewer' => '審査担当者',
    'viewer'   => '閲覧のみ',
];
$roleLabel = $roleLabels[$currentOwnerRole] ?? '管理者';
$activeMenu = $activeMenu ?? '';
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MESHu 事務局管理')</title>
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 60px;
            --admin-dark: #0f172a;
            --admin-dark-hover: #1e293b;
            --admin-border: #334155;
        }
        body {
            font-family: 'Inter', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
        }
        /* ヘッダー */
        .navbar-custom {
            height: var(--header-height);
            background-color: var(--admin-dark);
            border-bottom: 1px solid var(--admin-border);
            z-index: 1030;
        }
        .brand-logo {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: #ffffff !important;
            text-decoration: none;
        }
        .brand-logo .logo-accent {
            color: #38bdf8;
        }
        /* サイドバー */
        .sidebar-custom {
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            position: fixed;
            top: var(--header-height);
            left: 0;
            bottom: 0;
            z-index: 100;
            padding: 1.5rem 0;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: var(--admin-dark);
            border-right: 1px solid var(--admin-border);
            transition: all 0.3s ease;
        }
        /* メインコンテンツ */
        .main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--header-height);
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        /* モバイル表示時の調整 */
        @media (max-width: 991.98px) {
            .sidebar-custom {
                left: calc(-1 * var(--sidebar-width));
            }
            .sidebar-custom.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
        /* ナビゲーションリンク */
        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #94a3b8;
            font-weight: 500;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
        }
        .nav-link-custom i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        .nav-link-custom:hover {
            color: #ffffff;
            background-color: var(--admin-dark-hover);
        }
        .nav-link-custom.active {
            color: #ffffff;
            background-color: var(--admin-dark-hover);
            border-left-color: #38bdf8;
            font-weight: 600;
        }
        .card-custom {
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-radius: 8px;
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- ヘッダー -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top px-3">
        <div class="container-fluid p-0">
            <button class="btn btn-link d-lg-none me-2 text-white" type="button" id="sidebarToggle" aria-label="メニューを開く">
                <i class="bi bi-list fs-3"></i>
            </button>

            <a class="brand-logo me-auto" href="{{ route('admin.dashboard') }}">
                MESH<span class="logo-accent">u</span> <span class="fs-6 fw-normal text-white-50 ms-2">事務局管理</span>
            </a>

            <div class="d-flex align-items-center ms-auto">
                <div class="me-3 d-none d-md-flex align-items-center gap-2">
                    <span class="badge bg-danger text-white m-0">{{ $roleLabel }}</span>
                    <span class="fw-semibold text-white">{{ $currentOwnerName }} 様</span>
                </div>

                <div class="dropdown">
                    <button class="btn btn-link text-white p-0 border-0 d-flex align-items-center gap-2 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <span class="fw-bold small">{{ mb_substr($currentOwnerName, 0, 2) }}</span>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="{{ url('owner/show/' . ($owner->id ?? '')) }}"><i class="bi bi-person me-2"></i>アカウント情報</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="{{ route('admin.ownlogout') }}"><i class="bi bi-box-arrow-right me-2"></i>ログアウト</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- サイドバー -->
    <aside id="sidebarMenu" class="sidebar-custom">
        <nav class="nav flex-column">
            <a class="nav-link-custom {{ $activeMenu === 'dashboard' ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2"></i>
                <span>ダッシュボード</span>
            </a>
            <a class="nav-link-custom {{ $activeMenu === 'owncorp' ? 'active' : '' }}" href="{{ url('/owncorp') }}">
                <i class="bi bi-building"></i>
                <span>企業管理</span>
            </a>
            <a class="nav-link-custom {{ $activeMenu === 'ownnotice' ? 'active' : '' }}" href="{{ url('/ownnotice') }}">
                <i class="bi bi-megaphone"></i>
                <span>お知らせ管理</span>
            </a>
            @if ($currentOwnerRole === 'admin')
                <a class="nav-link-custom {{ $activeMenu === 'owner' ? 'active' : '' }}" href="{{ url('/owner') }}">
                    <i class="bi bi-shield-lock"></i>
                    <span>事務局アカウント管理</span>
                </a>
            @endif
        </nav>
    </aside>

    <!-- メインコンテンツ -->
    <main class="main-content">
        <div class="container-fluid p-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarMenu = document.getElementById('sidebarMenu');
            if (sidebarToggle && sidebarMenu) {
                sidebarToggle.addEventListener('click', function () {
                    sidebarMenu.classList.toggle('show');
                });
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
