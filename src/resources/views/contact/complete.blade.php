@extends('layouts.tenant')

@section('title', 'お問い合わせ送信完了 - MESHu')

@section('content')
<div class="container p-4" style="max-width: 680px;">
    <div class="card card-custom bg-white p-5 text-center mt-4 shadow border-0">
        <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle" style="width: 80px; height: 80px;">
                <i class="bi bi-check-lg fs-1"></i>
            </div>
        </div>

        <h2 class="h3 fw-bold text-dark mb-3">お問い合わせを送信いたしました</h2>
        
        <p class="text-muted mb-4">
            お問い合わせいただき誠にありがとうございます。<br>
            ご入力いただいたメールアドレス（{{ $user->email }}）宛てに受付確認メールをお送りいたしました。<br>
            内容を確認のうえ、担当者より1〜2営業日以内に順次ご連絡を差し上げます。
        </p>

        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-grid-1x2-fill me-2"></i>ダッシュボードへ戻る
            </a>
            <a href="{{ route('tenant.contact') }}" class="btn btn-outline-secondary px-4 fw-semibold">
                <i class="bi bi-question-circle me-2"></i>ヘルプ・FAQを見る
            </a>
        </div>
    </div>
</div>
@endsection
