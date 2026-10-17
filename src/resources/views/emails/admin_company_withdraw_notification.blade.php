MESHu 運営事務局 各位

企業ユーザーより「MESHu退会申請」を受け付けました。
対象企業および所属アカウントは自動的に「一時停止 (suspended)」に移行しています。

--------------------------------------------------
■ 退会申請企業情報
・企業ID: CO-{{ sprintf('%06d', $company->id) }}
・企業名: {{ $company->name }}（{{ $company->abbreviated_name }}）
・専用URLコード: {{ $company->url_code }}
・所在地: 〒{{ $company->zip_code }} {{ \App\Models\Company::PREFECTURES[$company->prefecture_code] ?? '' }}{{ $company->city }}{{ $company->address }}
・電話番号: {{ $company->tel }}

■ 申請者情報
・氏名: {{ $user->user_name }}
・メールアドレス: {{ $user->email }}
・申請日時: {{ $withdrawnAt }}

■ 状況・スケジュール
・現在のアカウント状態: 一時停止 (suspended)
・未完了取引件数: {{ $activeTransactionsCount }} 件
・完全削除予定日（30日後）: {{ $deleteScheduledDate }}
--------------------------------------------------

▼ 事務局管理画面（企業詳細）
{{ $adminCorpUrl }}

※本メールに直接返信することで、申請者（{{ $user->email }}）へ返信が可能です。
※このメールはシステムより自動送信されています。
