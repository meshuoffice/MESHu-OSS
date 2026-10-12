@extends('layouts.tenant')

@section('title', '招待詳細閲覧 | MESHu')

@section('content')
<div class="container-fluid p-4">

    <!-- パンくずリスト & 戻るボタン -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">ホーム</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/settings') }}">各種設定</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenant.invitation.index') }}">招待先管理</a></li>
                    <li class="breadcrumb-item active">招待詳細閲覧</li>
                </ol>
            </nav>
            <h1 class="h3 m-0 fw-bold">招待詳細の閲覧</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tenant.invitation.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> 一覧へ戻る
            </a>
            <a href="{{ route('tenant.invitation.delete_confirm', $invitation->id) }}" class="btn btn-outline-danger d-inline-flex align-items-center gap-1">
                <i class="bi bi-trash"></i> 招待を削除
            </a>
        </div>
    </div>

    <!-- 招待先情報カード -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-building me-2 text-primary"></i>招待先企業・登録情報</h5>
        </div>
        <div class="card-body p-4">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th class="bg-light w-25 text-nowrap">招待先 企業名</th>
                        <td class="fw-bold fs-6">{{ $invitation->company_name }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待先 法人番号</th>
                        <td class="font-monospace">{!! !empty($invitation->corporate_number) ? e($invitation->corporate_number) : '<span class="text-muted">（未入力）</span>' !!}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待先 担当者名</th>
                        <td>{!! !empty($invitation->user_name) ? e($invitation->user_name) : '<span class="text-muted">（未入力）</span>' !!}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">送信先 メールアドレス</th>
                        <td class="font-monospace text-primary fw-semibold">{{ $invitation->email }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">登録時の連携属性</th>
                        <td>
                            @if ($invitation->buysell === 'buyer')
                                <div class="fw-bold text-dark">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 me-2">購入 (買手)</span>
                                    {{ $companyName }} (買手) が {{ $invitation->company_name }} (売手) 様から購入する
                                </div>
                            @else
                                <div class="fw-bold text-dark">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 me-2">販売 (売手)</span>
                                    {{ $companyName }} (売手) が {{ $invitation->company_name }} (買手) 様へ販売する
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待送信者</th>
                        <td>{{ $senderName }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">送信日時</th>
                        <td>{{ $invitation->created_at ? $invitation->created_at->format('Y-m-d H:i:s') : '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待コード (一意)</th>
                        <td><span class="font-monospace bg-light p-1 rounded border text-muted small">{{ $invitation->invitation_code }}</span></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">専用登録URL</th>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace bg-light" id="invitationUrl" value="{{ url('signup?invitation_code=' . $invitation->invitation_code) }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('invitationUrl').value); alert('登録URLをクリップボードにコピーしました');">
                                    <i class="bi bi-clipboard"></i> コピー
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 送信メッセージ内容カード -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-envelope-open me-2 text-primary"></i>送信したメール内容</h5>
        </div>
        <div class="card-body p-4">
            <div class="mb-3">
                <span class="text-muted small d-block">メール件名:</span>
                <div class="fw-bold fs-6">{{ $invitation->mail_subject }}</div>
            </div>
            <div>
                <span class="text-muted small d-block mb-1">メッセージ本文:</span>
                <div class="p-3 bg-light rounded border" style="white-space: pre-wrap; font-size: 0.925rem; line-height: 1.6;">{{ $invitation->mail_detail }}</div>
            </div>
        </div>
    </div>

</div>
@endsection
