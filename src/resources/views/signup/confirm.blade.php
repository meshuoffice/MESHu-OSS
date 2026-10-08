<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu（メッシュ）のアカウント登録確認画面です。入力内容をご確認ください。">
    <title>登録内容の確認 - MESHu</title>
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
            <div class="header-actions">
                <a href="{{ url('/') }}" class="btn btn-secondary btn-sm">トップに戻る</a>
            </div>
        </div>
    </header>

    <main>
        <section class="register-section">
            <div class="container">
                <div class="form-wrapper">
                    <h1 class="form-title">登録内容の確認</h1>

                    <!-- ステップインジケータ -->
                    <div class="step-bar step-2">
                        <div class="step-bar-progress"></div>
                        <div class="step-node completed">
                            <div class="step-circle">1</div>
                            <span class="step-label">情報の入力</span>
                        </div>
                        <div class="step-node active">
                            <div class="step-circle">2</div>
                            <span class="step-label">入力内容の確認</span>
                        </div>
                        <div class="step-node">
                            <div class="step-circle">3</div>
                            <span class="step-label">登録完了</span>
                        </div>
                    </div>

                    <!-- 招待企業案内バナー -->
                    @if (!empty($inviterCompanyName))
                        <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 1.25rem; border-radius: 8px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; line-height: 1;">🤝</div>
                            <div>
                                <div style="font-size: 0.85rem; font-weight: 700; color: #2563eb; margin-bottom: 0.25rem;">取引先企業からのご招待</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b;">
                                    <strong>{{ $inviterCompanyName }}</strong> 様からのご招待によるアカウント登録です。
                                </div>
                                <div style="font-size: 0.825rem; color: #64748b; margin-top: 0.25rem;">
                                    登録完了時に、自動的に {{ $inviterCompanyName }} 様との取引連携（パートナーシップ）が有効になります。
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- 確認表示用テーブル -->
                    <table class="confirm-table">
                        <!-- 企業情報 -->
                        <tr>
                            <th colspan="2" style="background-color: var(--color-bg-light); border-bottom: 2px solid var(--color-border); padding: 0.75rem 1.5rem; text-align: left; font-size: 1.1rem; color: var(--color-secondary);">
                                企業情報
                            </th>
                        </tr>
                        <tr>
                            <th>企業名（法人格含む）</th>
                            <td>{{ $data['companyName'] }}</td>
                        </tr>
                        <tr>
                            <th>企業名略称</th>
                            <td>{{ $data['companyNameShort'] }}</td>
                        </tr>
                        <tr>
                            <th>企業名カナ</th>
                            <td>{{ $data['companyNameKana'] }}</td>
                        </tr>
                        <tr>
                            <th>法人番号</th>
                            <td>{{ !empty($data['corporateNumber']) ? $data['corporateNumber'] : '未入力' }}</td>
                        </tr>
                        <tr>
                            <th>電話番号</th>
                            <td>{{ $data['phone'] }}</td>
                        </tr>
                        <tr>
                            <th>郵便番号</th>
                            <td>{{ $data['zipCode'] }}</td>
                        </tr>
                        <tr>
                            <th>都道府県</th>
                            <td>{{ $data['prefecture'] }}</td>
                        </tr>
                        <tr>
                            <th>市区町村</th>
                            <td>{{ $data['city'] }}</td>
                        </tr>
                        <tr>
                            <th>番地建物</th>
                            <td>{{ $data['address'] }}</td>
                        </tr>
                        
                        <!-- 管理者情報 -->
                        <tr>
                            <th colspan="2" style="background-color: var(--color-bg-light); border-bottom: 2px solid var(--color-border); padding: 0.75rem 1.5rem; text-align: left; font-size: 1.1rem; color: var(--color-secondary); padding-top: 2rem;">
                                管理者情報
                            </th>
                        </tr>
                        <tr>
                            <th>部署名</th>
                            <td>{{ !empty($data['department']) ? $data['department'] : '未入力' }}</td>
                        </tr>
                        <tr>
                            <th>役職名</th>
                            <td>{{ !empty($data['position']) ? $data['position'] : '未入力' }}</td>
                        </tr>
                        <tr>
                            <th>管理者氏名（漢字）</th>
                            <td>{{ $data['adminName'] }}</td>
                        </tr>
                        <tr>
                            <th>管理者氏名フリガナ</th>
                            <td>{{ $data['adminNameKana'] }}</td>
                        </tr>
                        <tr>
                            <th>メールアドレス</th>
                            <td>{{ $data['adminEmail'] }}</td>
                        </tr>
                    </table>

                    <!-- アクションボタン（確定POSTフォーム） -->
                    <form action="{{ route('signup.register') }}" method="post">
                        @csrf
                        <div class="form-buttons">
                            <!-- セッションにデータが残っているため、普通に遷移すれば前回の入力が復元されます -->
                            <a href="{{ route('signup', array_filter(['invitation_code' => $invitationCode])) }}" class="btn btn-outline btn-lg">修正する</a>
                            <button type="submit" class="btn btn-primary btn-lg">この内容で登録する</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- フッター -->
    <footer class="footer">
        <div class="container footer-container">
            <div class="footer-brand">
                <span class="logo-text">MESH<span class="logo-accent">u</span></span>
                <p>生命の営みに準拠した網目構造 of B2Bプラットフォーム</p>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 MESHu. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>