@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '注文辞退完了'))

@section('content')
<div class="row justify-content-center">
    <div class="col-12" style="max-width: 680px;">
        <div class="card card-custom bg-white shadow-sm text-center p-5 my-4 mx-auto">
            <div class="mb-4">
                <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-x-octagon-fill fs-1"></i>
                </div>
            </div>

            <h2 class="h3 fw-bold text-dark mb-2">注文を辞退しました</h2>
            <p class="text-muted mb-4">
                発注元企業へ注文辞退の通知を送信しました。<br>
                本取引は終了（ロック）となります。
            </p>

            <div class="card bg-light border-0 p-3 mb-4 text-center">
                <div class="fw-bold small text-muted mb-2">取引番号</div>
                <div class="font-monospace fw-bold fs-5 text-dark mb-2">#{{ $tx['id'] }} - {{ $tx['title'] }}</div>
                <div>
                    <a href="{{ url('/sell/detail/' . $tx['id']) }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                        取引詳細画面を確認する
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <a href="{{ url('/sell') }}" class="btn btn-primary px-4 fw-semibold">
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
