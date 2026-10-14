@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '注文完了'))

@section('content')
<div class="row justify-content-center">
    <div class="col-12" style="max-width: 680px;">
        <div class="card card-custom bg-white shadow-sm text-center p-5 my-4 mx-auto">
            <div class="mb-4">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-cart-check-fill fs-1"></i>
                </div>
            </div>

            <h2 class="h3 fw-bold text-dark mb-2">注文を送信しました</h2>
            <p class="text-muted mb-4">
                販売先企業へ直接注文書（注文依頼）を送信しました。<br>
                相手企業による受注承諾（注文回答）をお待ちください。
            </p>

            @if (!empty($createdTxId))
                <div class="card bg-light border-0 p-3 mb-4 text-center">
                    <div class="fw-bold small text-muted mb-2">作成された取引番号</div>
                    <a href="{{ url('/buy/detail/' . $createdTxId) }}" class="btn btn-primary font-monospace fw-bold">
                        #{{ $createdTxId }} 取引詳細を確認する
                    </a>
                </div>
            @endif

            <div class="d-flex justify-content-center gap-3">
                <a href="{{ url('/buy') }}" class="btn btn-outline-primary px-4 fw-semibold">
                    購入取引一覧へ戻る
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4">
                    ダッシュボードへ
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
