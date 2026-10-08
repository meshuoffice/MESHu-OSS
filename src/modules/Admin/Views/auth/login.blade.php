<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu 事務局管理システム ログイン">
    <title>ログイン | MESHu 事務局管理システム</title>
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
        body {
            font-family: 'Inter', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #0f172a; /* ダークネイビー */
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 440px;
        }
        .brand-logo {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 2rem;
            color: #ffffff;
            text-decoration: none;
        }
        .brand-logo .logo-accent {
            color: #38bdf8; /* ライトブルー */
        }
        .form-control {
            background-color: #0f172a;
            border-color: #334155;
            color: #ffffff;
        }
        .form-control:focus {
            background-color: #0f172a;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }
    </style>
</head>
<body>

    <div class="container p-3">
        <div class="login-card mx-auto p-4 p-md-5">
            <div class="text-center mb-4">
                <a class="brand-logo" href="#">
                    MESH<span class="logo-accent">u</span>
                </a>
                <div class="text-muted small mt-2">事務局管理システム</div>
            </div>

            <h1 class="h5 text-center mb-4 fw-bold text-white">ログイン</h1>

            @if (session('error'))
                <div class="alert alert-danger py-2 mb-3 text-center small">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2 mb-3 text-center small">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success py-2 mb-3 text-center small">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success py-2 mb-3 text-center small">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label text-white-50 small">アカウントID (メールアドレス)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-secondary-subtle border-0 text-dark"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" id="email" placeholder="admin@meshu.org" required value="{{ old('email') }}">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label text-white-50 small">パスワード</label>
                    <div class="input-group">
                        <span class="input-group-text bg-secondary-subtle border-0 text-dark"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" id="password" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold" style="background-color: #38bdf8; border-color: #38bdf8; color: #0f172a;">
                        ログイン
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="{{ url('/') }}" class="text-white-50 small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>一般向けポータルへ戻る</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
