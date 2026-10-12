{{ $invitation->invitee_name }}
@if($invitation->user_name)
{{ $invitation->user_name }} 様
@else
ご担当者様
@endif

いつも大変お世話になっております。「{{ $inviterCompany->name }}」様より、
企業間取引プラットフォーム「MESHu」での取引連携の招待が届きました。
@if($invitation->message)

【メッセージ】
{{ $invitation->message }}
@endif

以下のURLより企業登録および連携手続きを行ってください。
{{ route('signup') }}?invite={{ $invitation->token }}

----------------------------------------
MESHu プラットフォーム
