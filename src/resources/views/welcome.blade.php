<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu（メッシュ） - 企業と企業が自律的につながり、網の目のように広がる次世代のB2Bプラットフォーム。見積から注文までをシームレスに。">
    <title>MESHu - 自律的につながるB2Bプラットフォーム</title>
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
            <a href="#" class="logo">
                <span class="logo-text">MESH<span class="logo-accent">u</span></span>
            </a>
            <nav class="nav">
                <a href="#concept" class="nav-link">コンセプト</a>
                <a href="#features" class="nav-link">機能・仕様</a>
                <a href="#contact" class="nav-link">お問い合わせ</a>
            </nav>
            <div class="header-actions">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-sm">アカウント作成</a>
            </div>
        </div>
    </header>

    <main>
        <!-- ヒーローセクション -->
        <section class="hero">
            <div class="hero-bg-grid"></div>
            <div class="container hero-container">
                <div class="hero-content">
                    <span class="badge">Next Generation B2B Platform</span>
                    <h1 class="hero-title">
                        企業と企業が自律的につながる、<br>
                        新しいB2Bの<span class="text-gradient">「網の目」</span>
                    </h1>
                    <p class="hero-lead">
                        MESHuは、1社の利益だけを優先するピラミッド型ではなく、すべての参加企業の利益と生命の営みに準拠した「網目の構造体」を目指したプラットフォームです。
                    </p>
                    <div class="hero-buttons">
                        <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">無料でアカウント作成</a>
                        <a href="#concept" class="btn btn-outline btn-lg">コンセプトを見る</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="mesh-network">
                        <!-- CSSとSVGによる網の目イメージのビジュアル -->
                        <svg viewBox="0 0 400 400" class="mesh-svg">
                            <!-- 接続線 -->
                            <line x1="100" y1="100" x2="200" y2="80" stroke="rgba(99, 102, 241, 0.4)" stroke-width="2" />
                            <line x1="200" y1="80" x2="300" y2="150" stroke="rgba(99, 102, 241, 0.4)" stroke-width="2" />
                            <line x1="100" y1="100" x2="150" y2="250" stroke="rgba(99, 102, 241, 0.4)" stroke-width="2" />
                            <line x1="150" y1="250" x2="300" y2="280" stroke="rgba(99, 102, 241, 0.4)" stroke-width="2" />
                            <line x1="300" y1="150" x2="300" y2="280" stroke="rgba(99, 102, 241, 0.4)" stroke-width="2" />
                            <line x1="200" y1="80" x2="150" y2="250" stroke="rgba(99, 102, 241, 0.4)" stroke-width="2" />
                            <line x1="300" y1="150" x2="150" y2="250" stroke="rgba(99, 102, 241, 0.4)" stroke-width="2" />
                            
                            <!-- ノード（企業） -->
                            <circle cx="100" cy="100" r="14" class="node node-buy" />
                            <circle cx="200" cy="80" r="16" class="node node-sell" />
                            <circle cx="300" cy="150" r="12" class="node node-buy" />
                            <circle cx="150" cy="250" r="18" class="node node-sell" />
                            <circle cx="300" cy="280" r="14" class="node node-buy" />
                            
                            <!-- ノードラベル（属性） -->
                            <text x="100" y="104" text-anchor="middle" class="node-text">買</text>
                            <text x="200" y="84" text-anchor="middle" class="node-text">売</text>
                            <text x="300" y="154" text-anchor="middle" class="node-text">買</text>
                            <text x="150" y="254" text-anchor="middle" class="node-text">売</text>
                            <text x="300" y="284" text-anchor="middle" class="node-text">買</text>
                        </svg>
                    </div>
                </div>
            </div>
        </section>

        <!-- コンセプトセクション -->
        <section id="concept" class="concept-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tag">Concept</span>
                    <h2>生命の営みに準拠した網目構造</h2>
                    <p>従来のB2Bシステムは、自社の利益のみを前提とした閉じた設計でした。MESHuは自律的なつながりを生み出す、人類の共有物としてのプラットフォームです。</p>
                </div>
                
                <div class="concept-grid">
                    <div class="concept-card">
                        <div class="concept-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <h3>自律的な企業間リンク</h3>
                        <p>「売り」と「買い」の属性を持つ企業同士が直接つながり、取引関係を構築。リンクしていない企業との不要な情報の混線を防ぎます。</p>
                    </div>
                    
                    <div class="concept-card">
                        <div class="concept-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3>購入側主導の健全な取引</h3>
                        <p>関係性の構築（リンク申請）は必ず購入側から行います。販売側は随時「承認」「否認」を切り替え可能で、安全なサプライチェーンを維持できます。</p>
                    </div>

                    <div class="concept-card">
                        <div class="concept-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a19.94 19.94 0 01-3.128-1.5M12 26a3.5 3.5 0 003.5-3.5V5.5A3.5 3.5 0 0012 2H6.5A3.5 3.5 0 003 5.5v17A3.5 3.5 0 006.5 26H12z" stroke-dasharray="2 2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3>ダイレクトコミュニケーション</h3>
                        <p>関係の構築された企業間でスムーズなチャットが行えます。担当者の権限設定により、必要なメンバーのみに限定した取引会話も可能です。</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 特徴・仕様セクション -->
        <section id="features" class="features-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tag">Features</span>
                    <h2>MESHuの主な取引仕様</h2>
                    <p>見積もりから注文、回答まで。シンプルでありながら、実務を完全にカバーする仕様設計。</p>
                </div>

                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-content">
                            <h3>01. 見積・注文のスマートフロー</h3>
                            <p>取引の流れは「見積 → 見積回答 → 注文 → 注文回答」の4ステップ。もちろん注文から取引を直接開始することも可能です。</p>
                            <ul class="feature-bullets">
                                <li><strong>相見積対応:</strong> 見積時は複数企業を同時指定（チェックボックス）</li>
                                <li><strong>注文の限定:</strong> 注文時は1社のみに限定（ラジオボタン）</li>
                                <li><strong>権限の最適化:</strong> 購入側がデータを作成し、販売側が更新・削除を管理</li>
                            </ul>
                        </div>
                        <div class="feature-image">
                            <div class="flow-diagram">
                                <div class="flow-step">1. 見積依頼</div>
                                <div class="flow-arrow">↓</div>
                                <div class="flow-step">2. 見積回答</div>
                                <div class="flow-arrow">↓</div>
                                <div class="flow-step">3. 注文</div>
                                <div class="flow-arrow">↓</div>
                                <div class="flow-step flow-step-highlight">4. 注文回答（成立）</div>
                            </div>
                        </div>
                    </div>

                    <div class="feature-item feature-reverse">
                        <div class="feature-content">
                            <h3>02. 柔軟な組織・担当者マッピング</h3>
                            <p>本支店構造の親・子会社設定や、従業員ごとの担当取引先割り当てにより、大手企業から中小企業まで柔軟な運用が可能です。</p>
                            <ul class="feature-bullets">
                                <li><strong>親・子会社設定:</strong> 本社と拠点を階層化し、統合的な取引を支援</li>
                                <li><strong>担当者の割り当て:</strong> 従業員ごとに担当取引先をアサイン可能</li>
                                <li><strong>権限分離:</strong> 取引の全社共有、または担当者限定共有を切り替え可能</li>
                            </ul>
                        </div>
                        <div class="feature-image">
                            <div class="card-mockup">
                                <div class="mockup-header">
                                    <div class="mockup-dots"><span></span><span></span><span></span></div>
                                    <div class="mockup-title">担当者別取引先マッピング</div>
                                </div>
                                <div class="mockup-body">
                                    <div class="mockup-item"><span class="mockup-user">鈴木 担当</span> <span class="mockup-link">⇄ 取引先A社</span></div>
                                    <div class="mockup-item"><span class="mockup-user">田中 担当</span> <span class="mockup-link">⇄ 取引先B社, C社</span></div>
                                    <div class="mockup-item highlight"><span class="mockup-user">全社共有設定</span> <span class="mockup-link">⇄ 全取引先（管理者）</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTAセクション -->
        <section class="cta-section">
            <div class="container cta-container">
                <h2>MESHuで、新しい取引の網の目を始めましょう</h2>
                <p>初期費用・月額利用料は無料。今すぐアカウントを作成して、取引先とつながりましょう。</p>
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">無料でアカウント作成</a>
            </div>
        </section>
    </main>

    <!-- フッター -->
    <footer id="contact" class="footer">
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
