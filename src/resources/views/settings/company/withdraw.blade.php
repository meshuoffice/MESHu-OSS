@extends('layouts.tenant')

@section('title', 'MESHuの退会手続き')

@section('content')
<div class="container-fluid p-0 max-width-900 mx-auto">
    <!-- パンくずナビゲーション -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.company.edit', ['url_code' => $company->url_code]) }}" class="text-decoration-none">企業情報メンテナンス</a></li>
            <li class="breadcrumb-item active" aria-current="page">退会手続き</li>
        </ol>
    </nav>

    <!-- ページ見出し -->
    <div class="mb-4">
        <h1 class="h3 fw-bold text-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i> MESHuの退会手続き
        </h1>
        <p class="text-muted">
            自社企業アカウントの利用を停止し、プラットフォームからの退会申請を行います。<br>
            取引関係書類の保管や取引先への影響等、下記の重要確認事項を必ずご確認・同意の上でお進みください。
        </p>
    </div>

    <!-- 進行中取引の警告（該当取引がある場合） -->
    @if ($activeTransactionsCount > 0)
        <div class="alert alert-warning border-warning d-flex align-items-start gap-3 p-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-octagon-fill fs-4 text-warning-emphasis flex-shrink-0 mt-1"></i>
            <div>
                <h6 class="fw-bold mb-1 text-warning-emphasis">進行中の取引が {{ $activeTransactionsCount }} 件あります</h6>
                <p class="mb-1 small">
                    現在、見積回答待ちや注文承諾待ち等の完了していない取引データが存在します。<br>
                    退会を行うと、取引相手側からもこれらの取引状況の確認・更新が行えなくなります。可能であれば取引を完了またはキャンセルした上での退会を推奨します。
                </p>
                <a href="{{ route('tenant.buy.index') }}" class="small fw-semibold text-warning-emphasis" target="_blank">
                    取引一覧を確認する <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </div>
    @endif

    <div class="card border-danger-subtle bg-white shadow-sm mb-5">
        <div class="card-header bg-danger text-white py-3">
            <h5 class="card-title m-0 fw-bold fs-6">
                <i class="bi bi-shield-exclamation me-1"></i> 退会にあたっての重要同意事項（全5項目）
            </h5>
        </div>
        <div class="card-body p-4 p-md-5">

            <form action="{{ route('tenant.company.withdraw.process', ['url_code' => $company->url_code]) }}" method="POST" id="withdrawForm">
                @csrf

                <!-- 同意項目1 -->
                <div class="p-3 mb-3 bg-light rounded border">
                    <div class="form-check">
                        <input class="form-check-input @error('agreement_partner') is-invalid @enderror" type="checkbox" name="agreement_partner" id="checkPartner" value="1" {{ old('agreement_partner') ? 'checked' : '' }} required>
                        <label class="form-check-label fw-bold text-dark" for="checkPartner">
                            1. 取引先への影響について（相手企業側の取引データ非表示の理解） <span class="badge bg-danger ms-1">必須</span>
                        </label>
                        <div class="small text-secondary mt-1 ps-1">
                            MESHuは1つの取引データを双方で共有しているため、自社が退会すると**取引相手側企業の画面からも該当の取引履歴・明細・チャット履歴が非表示（参照不可）**となります。取引先に重大な支障が出ないよう、事前に相手先企業へ退会の旨を連絡・合意済みであることを確認・承諾します。
                        </div>
                    </div>
                    @error('agreement_partner')
                        <div class="text-danger small mt-1 ps-4">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 同意項目2 -->
                <div class="p-3 mb-3 bg-light rounded border">
                    <div class="form-check">
                        <input class="form-check-input @error('agreement_transactions') is-invalid @enderror" type="checkbox" name="agreement_transactions" id="checkTransactions" value="1" {{ old('agreement_transactions') ? 'checked' : '' }} required>
                        <label class="form-check-label fw-bold text-dark" for="checkTransactions">
                            2. 進行中取引について（未完了ステータスの解決） <span class="badge bg-danger ms-1">必須</span>
                        </label>
                        <div class="small text-secondary mt-1 ps-1">
                            見積回答待ち、注文承諾待ちなど、現在進行中の取引が存在しないこと（またはすべてキャンセル・社内調整済みであること）を確認・承諾します。退会後は進行中取引の進捗更新や承諾・辞退操作は行えなくなります。
                        </div>
                    </div>
                    @error('agreement_transactions')
                        <div class="text-danger small mt-1 ps-4">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 同意項目3 -->
                <div class="p-3 mb-3 bg-light rounded border">
                    <div class="form-check">
                        <input class="form-check-input @error('agreement_backup') is-invalid @enderror" type="checkbox" name="agreement_backup" id="checkBackup" value="1" {{ old('agreement_backup') ? 'checked' : '' }} required>
                        <label class="form-check-label fw-bold text-dark" for="checkBackup">
                            3. 法令関係書類の保管について（電子帳簿保存法・インボイス制度対応） <span class="badge bg-danger ms-1">必須</span>
                        </label>
                        <div class="small text-secondary mt-1 ps-1">
                            電子帳簿保存法および税法上、取引関係書類（見積書・注文書・納品書・請求書等）は原則7年間の保存が義務付けられています。**退会後はMESHu上での帳票再発行・閲覧・ダウンロードが一切行えなくなります**。必要な帳票PDFや取引履歴をすべて自社環境へダウンロード・保存済みであることを確認・承諾します。
                        </div>
                    </div>
                    @error('agreement_backup')
                        <div class="text-danger small mt-1 ps-4">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 同意項目4 -->
                <div class="p-3 mb-3 bg-light rounded border">
                    <div class="form-check">
                        <input class="form-check-input @error('agreement_users') is-invalid @enderror" type="checkbox" name="agreement_users" id="checkUsers" value="1" {{ old('agreement_users') ? 'checked' : '' }} required>
                        <label class="form-check-label fw-bold text-dark" for="checkUsers">
                            4. 自社全従業員アカウントの即時停止について <span class="badge bg-danger ms-1">必須</span>
                        </label>
                        <div class="small text-secondary mt-1 ps-1">
                            退会申請完了と同時に、管理者を含む自社に所属する**すべての従業員アカウントが即座に利用停止（ログイン不能）**となることを確認・承諾します。
                        </div>
                    </div>
                    @error('agreement_users')
                        <div class="text-danger small mt-1 ps-4">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 同意項目5 -->
                <div class="p-3 mb-4 bg-light rounded border">
                    <div class="form-check">
                        <input class="form-check-input @error('agreement_period') is-invalid @enderror" type="checkbox" name="agreement_period" id="checkPeriod" value="1" {{ old('agreement_period') ? 'checked' : '' }} required>
                        <label class="form-check-label fw-bold text-dark" for="checkPeriod">
                            5. 30日間のデータ保持期間（一時停止）および完全削除について <span class="badge bg-danger ms-1">必須</span>
                        </label>
                        <div class="small text-secondary mt-1 ps-1">
                            退会申請を行うと、直ちに自社アカウントは**「一時停止」**となりログインできなくなります。退会後**30日間のデータ保持猶予期間**が経過した時点で**「完全削除」**となり、以降のデータ復元・利用再開は行えなくなります。誤操作や復元のご相談は退会通知メールへのご返信、または事務局窓口にて承ることを理解・承諾します。
                        </div>
                    </div>
                    @error('agreement_period')
                        <div class="text-danger small mt-1 ps-4">{{ $message }}</div>
                    @enderror
                </div>

                <!-- パスワード再確認 -->
                <div class="border-top pt-4 mb-4">
                    <label for="password" class="form-label fw-bold text-dark">
                        現在のログインパスワード <span class="badge bg-danger ms-1">必須</span>
                    </label>
                    <p class="text-muted small mb-2">ご本人様確認のため、現在ログイン中のパスワードを入力してください。</p>
                    <div class="col-md-6">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password" placeholder="現在のパスワード">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- アクションボタンエリア -->
                <div class="d-flex justify-content-between align-items-center border-top pt-4">
                    <a href="{{ route('tenant.company.edit', ['url_code' => $company->url_code]) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-arrow-left me-1"></i>キャンセルして戻る
                    </a>
                    <button type="submit" class="btn btn-danger px-4 py-2 fw-bold shadow-sm" id="btnSubmitWithdraw" onclick="return confirm('本当にすべての事項に同意し、退会申請を実行してよろしいですか？\n実行後は直ちにログアウトされ、ログインできなくなります。');">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> 同意して退会申請を実行する
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection
