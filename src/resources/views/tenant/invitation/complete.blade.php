@extends('layouts.tenant')

@section('title', '招待完了 | MESHu')

@section('content')
<!-- 完了カード -->
<div class="card border-0 shadow-sm text-center p-5 my-5 mx-auto" style="max-width: 680px;">
    <div class="mb-4">
            <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="bi bi-check-lg fs-1"></i>
            </div>
        </div>

        <h2 class="h3 fw-bold text-dark mb-2">招待メールを送信しました</h2>
        <p class="text-muted mb-4">
            相手企業のメールアドレス宛に、MESHuへの招待および初期登録専用URLを記載したメールを送信いたしました。
        </p>

        @if (!empty($sentInfo))
            <div class="card bg-light border-0 p-3 text-start mb-4 mx-auto" style="max-width: 480px;">
                <div class="row g-2 small">
                    <div class="col-sm-4 text-muted">招待先 企業名:</div>
                    <div class="col-sm-8 fw-bold text-dark">{{ $sentInfo['company_name'] }}</div>
                    <div class="col-sm-4 text-muted">送信先 アドレス:</div>
                    <div class="col-sm-8 font-monospace text-dark">{{ $sentInfo['email'] }}</div>
                    <div class="col-sm-4 text-muted">登録時の連携属性:</div>
                    <div class="col-sm-8">
                        @if ($sentInfo['buysell'] === 'buyer')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">購入 (買手)</span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle">販売 (売手)</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- ボタンアクション -->
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('tenant.invitation.index') }}" class="btn btn-outline-secondary px-4 py-2">
                <i class="bi bi-list-ul me-1"></i>招待一覧へ戻る
            </a>
            <a href="{{ route('tenant.invitation.new') }}" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i>続けて別の企業を招待する
            </a>
        </div>
    </div>
@endsection
