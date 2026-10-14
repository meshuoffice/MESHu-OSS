{{ $recipientCompany->name }}
{{ $recipient['user_name'] }} 様

いつも MESHu をご利用いただき誠にありがとうございます。
MESHu 運営事務局です。

以下の取引について、{{ $actionCompany->name }} の {{ $actionUser->user_name }} 様より【{{ $actionTitle }}】が行われました。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
■ 取引概要
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
・取引ID: #{{ $tx->id }}
・取引名: {{ $tx->title }}
・発注元（買手）: {{ $buyerCompany->name }}
・受注先（売手）: {{ $sellerCompany->name }}
・現在のステータス: {{ $statusLabel }}
@if (!empty($tx->total_amount))
・合計金額（税込）: ￥{{ number_format((float)$tx->total_amount) }}
@endif
@if (!empty($tx->delivery_date))
・回答納期: {{ $tx->delivery_date->format('Y-m-d') }}
@elseif (!empty($tx->desired_delivery_date))
・希望納期: {{ $tx->desired_delivery_date->format('Y-m-d') }}
@endif
@if (!empty($userComment))

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
■ コメント / 連絡事項
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{{ $userComment }}
@endif
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

取引内容の確認および今後の対応は、下記URLよりログインの上ご確認ください。

▼ 取引詳細ページ
{{ url($detailUrl) }}

--------------------------------------------------------------------
※本メールは MESHu より自動送信されています。
※通知設定の変更は、ログイン後のマイプロフィール画面から行えます。
--------------------------------------------------------------------
