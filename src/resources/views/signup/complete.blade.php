<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu（メッシュ）のアカウント登録が完了しました。">
    <title>登録完了 - MESHu</title>
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
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
                <div class="form-wrapper">
                    
                    <!-- ステップインジケータ -->
                    <div class="step-bar step-3">
                        <div class="step-bar-progress"></div>
                        <div class="step-node completed">
                            <div class="step-circle">1</div>
                            <span class="step-label">情報の入力</span>
                        </div>
                        <div class="step-node completed">
                            <div class="step-circle">2</div>
                            <span class="step-label">入力内容の確認</span>
                        </div>
                        <div class="step-node active">
                            <div class="step-circle">3</div>
                            <span class="step-label">登録完了</span>
                        </div>
                    </div>

                    <!-- 完了メッセージエリア -->
                    <div class="complete-content">
                        <div class="complete-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h1 class="complete-title">アカウント登録申請を受け付けました</h1>
                        
                        <p class="complete-text">
                            ご登録ありがとうございます。<br>
                            管理者メールアドレス（<strong>{{ $email }}</strong>）宛てに、自動生成された仮パスワードなどを記載した受付確認メールをお送りしました。<br>
                            <br>
                            <strong>【重要】現在アカウントは「停止中（承認待ち）」です。</strong><br>
                            ただいま事務局にて企業の実在確認（審査）を行っております。<br>
                            承認が完了し、アカウントが活性化されましたらログインしてご利用いただけるようになります。承認完了のお知らせメールが届くまで今ばらくお待ちください。
                        </p>

                        <div class="form-buttons">
                            <a href="{{ url('/') }}" class="btn btn-primary btn-lg">トップページへ戻る</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- フッター -->
    <footer class="footer">
        <div class="container footer-container">
            <div class="footer-brand">
                <span class="logo-text">MESH<span class="logo-accent">u</span></span>
                <p>生命の営みに準拠した網目構造のB2Bプラットフォーム</p>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026{{ date('Y') > 2026 ? '-' . date('Y') : '' }} MESHu. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>