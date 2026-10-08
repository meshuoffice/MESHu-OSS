<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MESHu（メッシュ）のアカウント登録画面です。必要事項を入力して次へ進んでください。">
    <title>アカウント作成 - MESHu</title>
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
                    <h1 class="form-title">アカウント新規登録</h1>

                    <!-- ステップインジケータ -->
                    <div class="step-bar step-1">
                        <div class="step-bar-progress"></div>
                        <div class="step-node active">
                            <div class="step-circle">1</div>
                            <span class="step-label">情報の入力</span>
                        </div>
                        <div class="step-node">
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
                                    アカウント登録が完了すると、自動的に {{ $inviterCompanyName }} 様との取引連携（パートナーシップ）が有効になります。
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- システム全体のエラーメッセージ表示 -->
                    @if ($errors->has('system'))
                        <div style="background-color: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 1rem; border-radius: 6px; margin-bottom: 2rem;">
                            {{ $errors->first('system') }}
                        </div>
                    @endif

                    <!-- 入力フォーム（サーバーサイド制御 & yubinbango対応） -->
                    <form action="{{ route('signup.confirm') }}" method="post" class="h-adr" novalidate>
                        @csrf
                        <input type="hidden" name="invitation_code" value="{{ $invitationCode ?? '' }}">
                        <!-- 国名指定（yubinbangoの動作に必須） -->
                        <span class="p-country-name" style="display:none;">Japan</span>
                        
                        <!-- 企業情報 -->
                        <h3 style="margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--color-border); font-size: 1.2rem; color: var(--color-secondary);">企業情報</h3>
                        
                        <div class="form-group">
                            <label class="form-label" for="companyName">企業名（法人格含む）<span class="form-required">必須</span></label>
                            <input type="text" id="companyName" name="companyName" class="form-control {{ $errors->has('companyName') ? 'is-invalid' : '' }}" placeholder="例：株式会社メッシュコミュニケーションズ" value="{{ old('companyName', $input['companyName'] ?? '') }}" required>
                            <span class="error-text" style="{{ $errors->has('companyName') ? 'display:block;' : '' }}">{{ $errors->first('companyName') ?: '企業名（法人格含む）を入力してください。' }}</span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="companyNameShort">企業名略称（法人格含まず）<span class="form-required">必須</span></label>
                                <input type="text" id="companyNameShort" name="companyNameShort" class="form-control {{ $errors->has('companyNameShort') ? 'is-invalid' : '' }}" placeholder="例：メッシュコミュニケーションズ" value="{{ old('companyNameShort', $input['companyNameShort'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('companyNameShort') ? 'display:block;' : '' }}">{{ $errors->first('companyNameShort') ?: '企業名略称を入力してください。' }}</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="companyNameKana">企業名カナ（全角カナ）<span class="form-required">必須</span></label>
                                <input type="text" id="companyNameKana" name="companyNameKana" class="form-control {{ $errors->has('companyNameKana') ? 'is-invalid' : '' }}" placeholder="例：カブシキガイシャメッシュコミュニケーションズ" value="{{ old('companyNameKana', $input['companyNameKana'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('companyNameKana') ? 'display:block;' : '' }}">{{ $errors->first('companyNameKana') ?: '企業名カナを全角カタカナで入力してください。' }}</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="corporateNumber">法人番号</label>
                                <input type="text" id="corporateNumber" name="corporateNumber" class="form-control {{ $errors->has('corporateNumber') ? 'is-invalid' : '' }}" placeholder="13桁の半角数字" value="{{ old('corporateNumber', $input['corporateNumber'] ?? '') }}" maxlength="13">
                                <span class="error-text" style="{{ $errors->has('corporateNumber') ? 'display:block;' : '' }}">{{ $errors->first('corporateNumber') ?: '13桁の半角数字で法人番号を入力してください。' }}</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="phone">電話番号<span class="form-required">必須</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" placeholder="例：03-1234-5678" value="{{ old('phone', $input['phone'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('phone') ? 'display:block;' : '' }}">{{ $errors->first('phone') ?: '電話番号を入力してください。' }}</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="zipCode">郵便番号<span class="form-required">必須</span></label>
                                <input type="text" id="zipCode" name="zipCode" class="form-control p-postal-code {{ $errors->has('zipCode') ? 'is-invalid' : '' }}" placeholder="例：100-0004" value="{{ old('zipCode', $input['zipCode'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('zipCode') ? 'display:block;' : '' }}">{{ $errors->first('zipCode') ?: '郵便番号を入力してください。' }}</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="prefecture">都道府県<span class="form-required">必須</span></label>
                                <select id="prefecture" name="prefecture" class="form-control p-region {{ $errors->has('prefecture') ? 'is-invalid' : '' }}" required>
                                    <option value="" disabled {{ old('prefecture', $input['prefecture'] ?? '') ? '' : 'selected' }}>選択してください</option>
                                    @php
                                    $prefs = [
                                        '北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県',
                                        '茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県',
                                        '新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県',
                                        '静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県',
                                        '奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県',
                                        '徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県',
                                        '熊本県','大分県','宮崎県','鹿児島県','沖縄県'
                                    ];
                                    @endphp
                                    @foreach ($prefs as $pref)
                                        <option value="{{ $pref }}" {{ old('prefecture', $input['prefecture'] ?? '') === $pref ? 'selected' : '' }}>{{ $pref }}</option>
                                    @endforeach
                                </select>
                                <span class="error-text" style="{{ $errors->has('prefecture') ? 'display:block;' : '' }}">{{ $errors->first('prefecture') ?: '都道府県を選択してください。' }}</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="city">市区町村<span class="form-required">必須</span></label>
                                <input type="text" id="city" name="city" class="form-control p-locality p-street-address {{ $errors->has('city') ? 'is-invalid' : '' }}" placeholder="例：千代田区千代田" value="{{ old('city', $input['city'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('city') ? 'display:block;' : '' }}">{{ $errors->first('city') ?: '市区町村を入力してください。' }}</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="address">番地・建物名など<span class="form-required">必須</span></label>
                                <input type="text" id="address" name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" placeholder="例：1-1 メッシュビル 4F" value="{{ old('address', $input['address'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('address') ? 'display:block;' : '' }}">{{ $errors->first('address') ?: '番地・建物名などを入力してください。' }}</span>
                            </div>
                        </div>

                        <!-- 管理者情報 -->
                        <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--color-border); font-size: 1.2rem; color: var(--color-secondary);">初期管理者アカウント情報</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="adminName">管理者 氏名<span class="form-required">必須</span></label>
                                <input type="text" id="adminName" name="adminName" class="form-control {{ $errors->has('adminName') ? 'is-invalid' : '' }}" placeholder="例：山田 太郎" value="{{ old('adminName', $input['adminName'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('adminName') ? 'display:block;' : '' }}">{{ $errors->first('adminName') ?: '管理者 氏名を入力してください。' }}</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="adminNameKana">管理者 氏名カナ（全角カナ）<span class="form-required">必須</span></label>
                                <input type="text" id="adminNameKana" name="adminNameKana" class="form-control {{ $errors->has('adminNameKana') ? 'is-invalid' : '' }}" placeholder="例：ヤマダ タロウ" value="{{ old('adminNameKana', $input['adminNameKana'] ?? '') }}" required>
                                <span class="error-text" style="{{ $errors->has('adminNameKana') ? 'display:block;' : '' }}">{{ $errors->first('adminNameKana') ?: '管理者 氏名カナを全角カタカナで入力してください。' }}</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="department">部署名</label>
                                <input type="text" id="department" name="department" class="form-control" placeholder="例：営業部" value="{{ old('department', $input['department'] ?? '') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="position">役職</label>
                                <input type="text" id="position" name="position" class="form-control" placeholder="例：部長" value="{{ old('position', $input['position'] ?? '') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="adminEmail">メールアドレス（ログインID）<span class="form-required">必須</span></label>
                            <input type="email" id="adminEmail" name="adminEmail" class="form-control {{ $errors->has('adminEmail') ? 'is-invalid' : '' }}" placeholder="例：yamada@example.com" value="{{ old('adminEmail', $input['adminEmail'] ?? '') }}" required>
                            <span class="error-text" style="{{ $errors->has('adminEmail') ? 'display:block;' : '' }}">{{ $errors->first('adminEmail') ?: '有効なメールアドレスを入力してください。' }}</span>
                        </div>

                        <!-- 利用規約 -->
                        <h3 style="margin-top: 3rem; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--color-border); font-size: 1.2rem; color: var(--color-secondary);">利用規約への同意</h3>
                        
                        <div class="terms-box">
                            <h4>MESHu 利用規約</h4>
                            <p>この利用規約（以下，「本規約」といいます。）は，MESHu運営事務局（以下，「当事務局」といいます。）がこのウェブサイト上で提供するサービス（以下，「本サービス」といいます。）の利用条件を定めるものです。登録企業の皆さま（以下，「ユーザー」といいます。）には，本規約に従って，本サービスをご利用いただきます。</p>
                            <h5 style="margin-top: 1rem; margin-bottom: 0.5rem;">第1条（適用）</h5>
                            <p>本規約は，ユーザーと当事務局との間の本サービスの利用に関わる一切の関係に適用されるものとします。ユーザーは、本サービスを利用することにより、本規約のすべての内容に同意したものとみなされます。</p>
                            <h5 style="margin-top: 1rem; margin-bottom: 0.5rem;">第2条（登録およびアカウント管理）</h5>
                            <p>登録希望者が当事務局の定める方法によって利用登録を申請し，当事務局がこれを承認することによって，利用登録が完了するものとします。ユーザーは，自己の責任において，本サービスのユーザーIDおよびパスワードを適切に管理するものとします。</p>
                            <h5 style="margin-top: 1rem; margin-bottom: 0.5rem;">第3条（取引の自律性と責任）</h5>
                            <p>本サービスは、企業間の直接的なコミュニケーションおよびリンク構築の場を提供するプラットフォームです。ユーザー間で発生した見積もり、注文、契約およびその他のやり取りについては、当事者であるユーザー双方が一切の責任を負うものとし、当事務局は関与せず、一切の責任を負いません。</p>
                        </div>

                        <div class="form-group" style="text-align: center;">
                            <label class="form-check-label" style="justify-content: center;">
                                <input type="checkbox" id="agreeTerms" name="agreeTerms" value="1" class="form-check-input {{ $errors->has('agreeTerms') ? 'is-invalid' : '' }}" {{ old('agreeTerms', $input['agreeTerms'] ?? '') === '1' ? 'checked' : '' }} required>
                                利用規約に同意する
                            </label>
                            <div class="error-text" style="{{ $errors->has('agreeTerms') ? 'display:block;' : '' }}; text-align: center;">登録を進めるには利用規約への同意が必要です。</div>
                        </div>

                        <!-- アクションボタン -->
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary btn-lg">入力内容を確認する</button>
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
                <p>生命の営みに準拠した網目構造のB2Bプラットフォーム</p>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 MESHu. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- yubinbango.js による自動住所補完 -->
    <script src="https://yubinbango.github.io/yubinbango/yubinbango.js" charset="UTF-8" defer></script>
</body>
</html>