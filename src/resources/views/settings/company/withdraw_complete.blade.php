<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>退会申請の受付完了 - MESHu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --color-primary: #4f46e5;
            --color-primary-hover: #4338ca;
            --color-text-main: #1e293b;
            --color-text-muted: #64748b;
            --color-bg-light: #f8fafc;
            --color-border: #e2e8f0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--color-bg-light);
            color: var(--color-text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 0;
        }
        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--color-primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .logo-accent {
            color: #f59e0b;
        }
        .card-custom {
            border: 1px solid var(--color-border);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            background: #ffffff;
        }
        .footer {
            margin-top: auto;
            background-color: #ffffff;
            border-top: 1px solid var(--color-border);
            padding: 1.5rem 0;
            text-align: center;
            font-size: 0.85rem;
            color: var(--color-text-muted);
        }
    </style>
</head>
<body>

    <!-- ヘッダー -->
    <header class="header">
        <div class="container text-center">
            <a href="{{ url('/') }}" class="logo-text">
                MESH<span class="logo-accent">u</span>
            </a>
        </div>
    </header>

    <!-- メインコンテンツ -->
    <main class="container py-5 my-auto">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card card-custom p-4 p-md-5 text-center">
                    
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle" style="width: 72px; height: 72px;">
                            <i class="bi bi-pause-circle-fill fs-1 text-warning-emphasis"></i>
                        </div>
                    </div>

                    <h1 class="h4 fw-bold mb-3">退会申請を受け付けました</h1>
                    
                    <p class="text-secondary small leading-relaxed mb-4">
                        @if (!empty($companyName))
                            <strong>{{ $companyName }}</strong> の
                        @endif
                        MESHuアカウント退会申請手続きが完了いたしました。<br>
                        貴社および全従業員のアカウントは「一時停止」となり、ログインセッションを終了（ログアウト）しました。
                    </p>

                    <div class="alert alert-info text-start small mb-4" role="alert">
                        <h6 class="fw-bold mb-2 text-info-emphasis d-flex align-items-center gap-1">
                            <i class="bi bi-info-circle-fill"></i> 今後の流れとデータ保持期間
                        </h6>
                        <ul class="mb-2 ps-3 text-secondary">
                            <li>退会申請から<strong>30日間はデータ保持期間（一時停止）</strong>となります。</li>
                            <li>30日経過後に事務局にて完全削除（復旧不可）の確定処理が行われます。</li>
                            @if (!empty($adminEmail))
                                <li>管理者様宛（<code>{{ $adminEmail }}</code>）に受付確認メールを送信いたしました。</li>
                            @endif
                        </ul>
                    </div>

                    <div class="alert alert-light border text-start small mb-4" role="alert">
                        <h6 class="fw-bold mb-1 text-dark">
                            <i class="bi bi-envelope-fill text-primary me-1"></i> 誤操作・利用再開のご相談について
                        </h6>
                        <p class="mb-0 text-muted">
                            誤って退会された場合や利用再開・データ復元をご希望の場合は、送信された確認メールに<strong>【そのままご返信】</strong>いただくか、下記窓口までご連絡ください。<br>
                            事務局窓口: <code>{{ $replyEmail }}</code>
                        </p>
                    </div>

                    <div>
                        <a href="{{ url('/') }}" class="btn btn-primary px-4 py-2 fw-semibold">
                            MESHu トップページへ戻る
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- フッター -->
    <footer class="footer">
        <div class="container">
            <p class="mb-0">&copy; 2026{{ date('Y') > 2026 ? '-' . date('Y') : '' }} MESHu. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
