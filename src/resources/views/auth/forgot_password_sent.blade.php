<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu（メッシュ） - パスワード再設定メール送信完了">
    <title>再設定メール送信完了 - {{ $currentCompany->name }} | MESHu</title>

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
            font-size: 0.95rem;
            color: var(--color-text-main);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            background-color: var(--color-bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 2rem;
            border: 2px solid var(--color-border);
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
            margin-bottom: 1rem;
            text-align: center;
        }
        .notice-box {
            background-color: #f8fafc;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.825rem;
            color: var(--color-text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
            text-align: left;
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

                    <div class="icon-circle">✉️</div>

                    <h1 class="page-title">案内メールを送信しました</h1>

                    <p class="page-intro">
                        ご入力いただいたメールアドレス宛に、パスワード再設定用ページのURLを送信しました。<br><br>
                        メールに記載されているリンクを開き、<strong>30分以内</strong>に新しいパスワードを設定してください。
                    </p>

                    <div class="notice-box">
                        <strong>※ メールが届かない場合</strong><br>
                        ・ご入力いただいたメールアドレスに誤りがないかご確認ください。<br>
                        ・迷惑メールフォルダやゴミ箱に振り分けられていないかご確認ください。<br>
                        ・セキュリティ対策のため、未登録のメールアドレスの場合はメールは送信されません。
                    </div>

                    <div>
                        <a href="{{ route('tenant.login', ['url_code' => $url_code]) }}" class="btn btn-secondary" style="display: block; width: 100%; padding: 0.875rem 0; font-size: 1rem; border-radius: 8px; text-align: center; text-decoration: none;">ログイン画面へ戻る</a>
                    </div>

                </div>
            </div>
        </section>
    </main>

    <!-- フッター -->
    <footer class="py-3 text-center border-top bg-white" style="border-top: 1px solid var(--color-border); padding: 1.5rem 0; text-align: center; background-color: #ffffff;">
        <p style="color: var(--color-text-muted); font-size: 0.85rem; margin: 0;">&copy; 2026{{ date('Y') > 2026 ? '-' . date('Y') : '' }} MESHu Platform. All rights reserved.</p>
    </footer>

</body>
</html>
