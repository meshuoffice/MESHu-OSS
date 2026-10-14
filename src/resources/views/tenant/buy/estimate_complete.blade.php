@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '見積依頼完了'))

@section('content')
<div class="row justify-content-center">
    <div class="col-12" style="max-width: 680px;">
        <div class="card card-custom bg-white shadow-sm text-center p-5 my-4 mx-auto">
            <div class="mb-4">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-check-lg fs-1"></i>
                </div>
            </div>

            <h2 class="h3 fw-bold text-dark mb-2">見積依頼を送信しました</h2>
            <p class="text-muted mb-4">
                販売先企業への見積依頼の送信が完了しました。<br>
                相手企業が見積回答を登録するまでお待ちください。
            </p>

            @if (!empty($createdTxIds))
                <div class="card bg-light border-0 p-3 mb-4 text-start">
                    <div class="fw-bold small text-muted mb-2">起票された取引ID:</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($createdTxIds as $txId)
                            <a href="{{ url('/buy/detail/' . $txId) }}" class="btn btn-sm btn-outline-primary font-monospace">
                                #{{ $txId }} 詳細を見る
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-center gap-3">
                <a href="{{ url('/buy') }}" class="btn btn-primary px-4 fw-semibold">
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
