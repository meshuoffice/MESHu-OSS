{{ $invitation->company_name }}
@if(!empty($invitation->user_name))
{{ $invitation->user_name }} 様
@else
御中
@endif

{{ $invitation->mail_detail }}

--------------------------------------------------
▼ アカウント登録専用URL
{{ $signupUrl }}
--------------------------------------------------
※このURLからご登録いただくことで、{{ $inviterCompany->name }}との取引連携が自動的に有効になります。
