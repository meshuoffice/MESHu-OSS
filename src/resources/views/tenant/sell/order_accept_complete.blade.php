@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '受注承諾完了'))

@section('content')
<div class="row justify-content-center">
    <div class="col-12" style="max-width: 680px;">
        <div class="card card-custom bg-white shadow-sm text-center p-5 my-4 mx-auto">
            <div class="mb-4">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-check-lg fs-1"></i>
                </div>
            </div>

            <h2 class="h3 fw-bold text-dark mb-2">受注を承諾しました</h2>
            <p class="text-muted mb-4">
                注文請書が発行され、本取引が<strong>成立・完了</strong>しました。<br>
                買手企業へ受注承諾通知が送信されました。
            </p>

            <div class="card bg-light border-0 p-3 mb-4 text-center">
                <div class="fw-bold small text-muted mb-2">取引番号</div>
                <div class="font-monospace fw-bold fs-5 text-dark mb-2">#{{ $tx['id'] }} - {{ $tx['title'] }}</div>
                <div>
                    <a href="{{ url('/sell/detail/' . $tx['id']) }}" class="btn btn-primary btn-sm fw-semibold">
                        取引詳細画面を見る
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <a href="{{ url('/sell') }}" class="btn btn-outline-primary px-4 fw-semibold">
                    販売取引一覧へ戻る
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4">
                    ダッシュボードへ
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
