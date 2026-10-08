<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu（メッシュ） - パスワード再設定">
    <title>パスワード再設定 - {{ $currentCompany->name }} | MESHu</title>

    <!-- MESHu 統一 Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">

    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- 既存のLP用アセットCSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <style>
        .login-wrapper {
            max-width: 480px;
            width: 100%;
        }
        .tenant-indicator {
            background-color: var(--color-bg-light);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            text-align: center;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 1.5rem;
            display: block;
        }
        .page-intro {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .login-footer-links {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }
        .login-footer-links a {
            color: var(--color-text-muted);
            text-decoration: none;
            transition: var(--transition-smooth);
        }
        .login-footer-links a:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-logo .logo-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--color-secondary);
            text-decoration: none;
        }
        .login-logo .logo-accent {
            color: var(--color-primary);
        }
        .page-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--color-secondary);
            margin-bottom: 0.75rem;
            text-align: center;
        }
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

    <!-- ヘッダー -->
    <header class="header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo">
                <span class="logo-text">MESH<span class="logo-accent">u</span></span>
            </a>
        </div>
    </header>

    <main>
        <section class="register-section">
            <div class="container">
                <div class="form-wrapper login-wrapper">

                    <!-- ロゴ表示 -->
                    <div class="login-logo">
                        <a href="{{ url('/') }}" class="logo-text">
                            MESH<span class="logo-accent">u</span>
                        </a>
                    </div>

                    <!-- テナント識別インジケータ -->
                    <div class="tenant-indicator">
                        <span>{{ $currentCompany->name }}{{ !empty($currentCompany->sub_name) ? ' ' . $currentCompany->sub_name : '' }}</span>
                    </div>

                    <h1 class="page-title">パスワードの再設定</h1>

                    <p class="page-intro">
                        ご登録のログインID（メールアドレス）を入力してください。<br>
                        パスワード再設定ページのURLをメールでお送りします。
                    </p>

                    @if ($errors->any())
                        <div class="alert-error">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <!-- 送信フォーム -->
                    <form action="{{ route('tenant.password.email', ['url_code' => $url_code]) }}" method="POST" id="forgotPasswordForm">
                        @csrf

                        <!-- ログインID -->
                        <div class="form-group">
                            <label class="form-label" for="email">ログインID（メールアドレス）</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="example@sample-next.co.jp" required autocomplete="email" autofocus>
                        </div>

                        <!-- 送信ボタン -->
                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.875rem 0; font-size: 1rem; border-radius: 8px;">再設定用メールを送信する</button>
                        </div>

                        <!-- 戻るリンク -->
                        <div class="login-footer-links">
                            <a href="{{ route('tenant.login', ['url_code' => $url_code]) }}">← ログイン画面に戻る</a>
                        </div>

                    </form>

                </div>
            </div>
        </section>
    </main>

    <!-- フッター -->
    <footer class="py-3 text-center border-top bg-white" style="border-top: 1px solid var(--color-border); padding: 1.5rem 0; text-align: center; background-color: #ffffff;">
        <p style="color: var(--color-text-muted); font-size: 0.85rem; margin: 0;">&copy; 2026 MESHu Platform. All rights reserved.</p>
    </footer>

</body>
</html>
