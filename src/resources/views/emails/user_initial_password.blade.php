@if($user->company)
{{ $user->company->name }}
@endif
{{ $user->user_name }} 様

MESHu のアカウント登録が完了いたしました。
以下の初期仮パスワードにてログインし、マイプロフィールよりパスワード変更を行ってください。

■ ログイン情報
・ログインID: {{ $user->email }}
・初期仮パスワード: {{ $initialPassword }}

----------------------------------------
MESHu プラットフォーム
