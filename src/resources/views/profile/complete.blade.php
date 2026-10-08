@extends('layouts.tenant')

@section('title', 'プロフィール更新完了 | MESHu')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="card card-custom bg-white p-5 text-center shadow border-0" style="max-width: 500px; width: 100%;">
        <div class="text-success mb-3" style="font-size: 4rem;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h3 class="fw-bold mb-3">更新が完了しました</h3>
        <p class="text-muted mb-4">プロフィールの変更内容が正常に保存されました。</p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary px-4">ダッシュボードへ戻る</a>
    </div>
</div>
@endsection
