@extends('layouts.tenant')

@section('title', 'お問い合わせ内容の確認 - MESHu')

@section('styles')
<style>
    .step-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
        gap: 1rem;
    }
    .step-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #94a3b8;
        font-size: 0.875rem;
        font-weight: 600;
    }
    .step-item.active {
        color: #2563eb;
    }
    .step-num {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    .step-item.active .step-num {
        background-color: #2563eb;
        color: #ffffff;
    }
    .confirm-table th {
        width: 25%;
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .confirm-table td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
    }
</style>
@endsection

@section('content')
<div class="mx-auto" style="max-width: 960px;">
    <!-- パンくずリスト & ページタイトル -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.contact') }}" class="text-decoration-none">事務局へのお問い合わせ</a></li>
                <li class="breadcrumb-item active" aria-current="page">入力内容の確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold text-dark">
            <i class="bi bi-clipboard-check me-2 text-primary"></i>お問い合わせ内容の確認
        </h1>
        <p class="text-muted small mb-0 mt-1">
            入力内容をご確認いただき、問題がなければ「この内容で送信する」ボタンをクリックしてください。
        </p>
    </div>

    <!-- ステップインジケータ -->
    <div class="step-indicator">
        <div class="step-item">
            <div class="step-num"><i class="bi bi-check"></i></div>
            <span>入力</span>
        </div>
        <div class="step-item">
            <i class="bi bi-chevron-right text-muted small"></i>
        </div>
        <div class="step-item active">
            <div class="step-num">2</div>
            <span>確認</span>
        </div>
        <div class="step-item">
            <i class="bi bi-chevron-right text-muted small"></i>
        </div>
        <div class="step-item">
            <div class="step-num">3</div>
            <span>完了</span>
        </div>
    </div>

    <div class="card card-custom bg-white p-4">
        <table class="table confirm-table mb-4">
            <tbody>
                <tr>
                    <th>企業名</th>
                    <td>{{ $company->name }}</td>
                </tr>
                <tr>
                    <th>送信者</th>
                    <td>{{ $user->user_name }} 様 ({{ $user->role === 'admin' ? '管理者' : '一般社員' }})</td>
                </tr>
                <tr>
                    <th>返信先メール</th>
                    <td>{{ $user->email }}</td>
                </tr>
                <tr>
                    <th>連絡先電話番号</th>
                    <td>{{ $company->tel ?: '未登録' }}</td>
                </tr>
                <tr>
                    <th>お問い合わせ種別</th>
                    <td><span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">{{ $contactTypeName }}</span></td>
                </tr>
                <tr>
                    <th>件名</th>
                    <td class="fw-bold">{{ $subject }}</td>
                </tr>
                <tr>
                    <th>お問い合わせ内容</th>
                    <td style="white-space: pre-wrap; line-height: 1.7;">{{ $message }}</td>
                </tr>
                <tr>
                    <th>添付ファイル</th>
                    <td>
                        @if ($hasAttachment)
                            <i class="bi bi-paperclip text-primary me-1"></i>{{ $originalFileName }}
                        @else
                            <span class="text-muted">なし</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- 送信フォーム -->
        <form action="{{ route('tenant.contact.send') }}" method="POST">
            @csrf
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="{{ route('tenant.contact') }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>修正する
                </a>
                <button type="submit" class="btn btn-primary px-5 fw-bold">
                    <i class="bi bi-send me-2"></i>この内容で送信する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
