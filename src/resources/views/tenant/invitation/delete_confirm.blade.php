@extends('layouts.tenant')

@section('title', '招待削除の確認 | MESHu')

@section('content')
<!-- パンくずリスト -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">ホーム</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.invitation.index') }}">招待先管理</a></li>
            <li class="breadcrumb-item active">招待削除の確認</li>
        </ol>
    </nav>
        <h1 class="h3 m-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>招待レコードの削除確認</h1>
        <p class="text-muted small mt-1">以下の招待情報を削除します。内容を確認の上、削除を実行してください。</p>
    </div>

    <!-- 警告コールアウト -->
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start gap-3 mb-4">
        <i class="bi bi-shield-slash-fill text-danger fs-4 flex-shrink-0 mt-1"></i>
        <div>
            <h6 class="fw-bold mb-1">ご注意ください（物理削除の実行）</h6>
            <p class="small mb-0">
                招待レコードを削除すると、<strong>相手企業に送信済みの登録専用URLは即座に無効化</strong>され、そのURLからのアカウント登録および自動つながり生成は行えなくなります。<br>
                この操作を取り消すことはできません。
            </p>
        </div>
    </div>

    <!-- 対象情報カード -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold fs-6 text-dark"><i class="bi bi-trash3-fill me-2 text-danger"></i>削除対象の招待情報</h5>
        </div>
        <div class="card-body p-4">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th class="bg-light w-30 text-nowrap">招待先 企業名</th>
                        <td class="fw-bold">{{ $invitation->company_name }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待先 法人番号</th>
                        <td class="font-monospace">{!! !empty($invitation->corporate_number) ? e($invitation->corporate_number) : '<span class="text-muted">（未入力）</span>' !!}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">招待先 管理者名</th>
                        <td>{!! !empty($invitation->user_name) ? e($invitation->user_name) : '<span class="text-muted">（未入力）</span>' !!}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">送信先 メールアドレス</th>
                        <td class="font-monospace">{{ $invitation->email }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-nowrap">送信日時</th>
                        <td class="text-muted">{{ $invitation->created_at ? $invitation->created_at->format('Y-m-d H:i:s') : '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ボタンエリア -->
    <form action="{{ route('tenant.invitation.delete', $invitation->id) }}" method="POST">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="{{ route('tenant.invitation.index') }}" class="btn btn-outline-secondary px-4 py-2">
                <i class="bi bi-arrow-left me-1"></i>キャンセル
            </a>
            <button type="submit" class="btn btn-danger px-5 py-2 fw-bold shadow-sm">
                <i class="bi bi-trash-fill me-2"></i>招待レコードを削除する
            </button>
        </div>
    </form>
@endsection
