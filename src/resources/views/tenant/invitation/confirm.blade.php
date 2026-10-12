@extends('layouts.tenant')

@section('title', '招待内容の確認 | MESHu')

@section('content')
<div class="container-fluid p-4">

    <!-- パンくずリスト -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/settings') }}">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.invitation.index') }}">招待先管理</a></li>
                <li class="breadcrumb-item active">招待内容の確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">招待送信内容の確認</h1>
        <p class="text-muted small mt-1">以下の内容で招待メールを送信します。内容をご確認の上、「招待メールを送信する」をクリックしてください。</p>
    </div>

    <!-- 確認内容カード -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold fs-6 text-primary"><i class="bi bi-check-circle-fill me-2"></i>送信内容</h5>
        </div>
        <div class="card-body p-4">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th class="bg-light w-25 text-nowrap">招待先 企業名</th>
                        <td class="fw-bold fs-6">{{ $postData['company_name'] }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待先 法人番号</th>
                        <td class="font-monospace">{!! !empty($postData['corporate_number']) ? e($postData['corporate_number']) : '<span class="text-muted">（未入力）</span>' !!}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待先 担当者名</th>
                        <td>{!! !empty($postData['user_name']) ? e($postData['user_name']) : '<span class="text-muted">（未入力）</span>' !!}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">送信先 メールアドレス</th>
                        <td class="font-monospace text-primary fw-semibold">{{ $postData['email'] }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">登録時の連携属性</th>
                        <td>
                            @if ($postData['buysell'] === 'buyer')
                                <div class="fw-bold text-dark">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 me-2">購入 (買手)</span>
                                    {{ $companyName }} (買手) が {{ $postData['company_name'] }} (売手) 様から購入する
                                </div>
                            @else
                                <div class="fw-bold text-dark">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 me-2">販売 (売手)</span>
                                    {{ $companyName }} (売手) が {{ $postData['company_name'] }} (買手) 様へ販売する
                                </div>
                            @endif
                            <div class="small text-muted mt-1">※相手企業が登録すると、この属性で連携リンクが自動生成されます。</div>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待メール件名</th>
                        <td class="fw-bold">{{ $postData['mail_subject'] }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap align-top pt-3">招待メッセージ本文</th>
                        <td class="bg-light-subtle">
                            <div class="p-3 bg-white rounded border" style="white-space: pre-wrap; font-size: 0.925rem; line-height: 1.6;">{{ $postData['mail_detail'] }}</div>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-info-circle me-1"></i>実際のメールには、本文の後にシステム専用登録URL（一意の招待コード付き）が自動追記されます。
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ボタンエリア -->
    <form action="{{ route('tenant.invitation.send') }}" method="POST">
        @csrf
        <input type="hidden" name="company_name" value="{{ $postData['company_name'] }}">
        <input type="hidden" name="corporate_number" value="{{ $postData['corporate_number'] ?? '' }}">
        <input type="hidden" name="user_name" value="{{ $postData['user_name'] ?? '' }}">
        <input type="hidden" name="email" value="{{ $postData['email'] }}">
        <input type="hidden" name="buysell" value="{{ $postData['buysell'] }}">
        <input type="hidden" name="mail_subject" value="{{ $postData['mail_subject'] }}">
        <input type="hidden" name="mail_detail" value="{{ $postData['mail_detail'] }}">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="history.back();">
                <i class="bi bi-arrow-left me-1"></i>修正する
            </button>
            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                <i class="bi bi-send-fill me-2"></i>招待メールを送信する
            </button>
        </div>
    </form>

</div>
@endsection
