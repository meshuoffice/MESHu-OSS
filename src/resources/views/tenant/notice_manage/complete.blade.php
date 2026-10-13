@extends('layouts.tenant')

@section('title', (($mode ?? '') === 'delete' || !empty($successInfo)) ? 'お知らせ削除完了 | MESHu' : 'お知らせ保存完了 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.notice.manage.index') }}" class="text-decoration-none">お知らせ配信管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">完了</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">{{ (($mode ?? '') === 'delete' || !empty($successInfo)) ? 'お知らせ削除完了' : 'お知らせ保存完了' }}</h1>
    </div>
</div>

<!-- 完了カード -->
<div class="card card-custom bg-white mx-auto border-0 shadow-sm text-center p-5" style="max-width: 680px;">
    <div class="card-body">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        </div>
        
        @if (($mode ?? '') === 'delete' || !empty($successInfo))
            <h3 class="h4 fw-bold mb-3">お知らせを削除しました</h3>
            @if (!empty($successInfo))
                <div class="alert alert-info d-inline-block text-start mb-4 px-4 py-3">
                    <i class="bi bi-info-circle-fill me-2"></i>{{ $successInfo }}
                </div>
            @else
                <p class="text-muted mb-4">
                    対象のお知らせは正常に削除されました。
                </p>
            @endif

            <div class="d-flex justify-content-center gap-3 flex-wrap pt-3">
                <a href="{{ route('tenant.notice.manage.index') }}" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-list-ul me-1"></i>お知らせ配信管理一覧へ戻る
                </a>
                <a href="{{ route('tenant.settings') }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-gear me-1"></i>各種設定ポータルへ戻る
                </a>
            </div>
        @elseif ($notice && $notice->is_published)
            <h3 class="h4 fw-bold mb-3">お知らせの登録・配信が完了しました</h3>
            <p class="text-muted mb-4">
                作成したお知らせは、つながり（MESHu）承認済みの取引先企業へ正常に公開・配信されました。<br>
                今後の閲覧状況や修正は配信管理一覧よりご確認いただけます。
            </p>

            <div class="d-flex justify-content-center gap-3 flex-wrap pt-3">
                <a href="{{ route('tenant.notice.manage.index') }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-list-ul me-1"></i>配信管理一覧へ
                </a>
                <a href="{{ route('tenant.notice.manage.show', $notice->id) }}" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-eye me-1"></i>作成したお知らせを見る
                </a>
            </div>
        @else
            <h3 class="h4 fw-bold mb-3">お知らせを下書き保存しました</h3>
            <p class="text-muted mb-4">
                下書きとして保存されました。他企業へはまだ公開されていません。<br>
                内容を公開する場合は、編集画面よりステータスを「公開」へ変更してください。
            </p>

            <div class="d-flex justify-content-center gap-3 flex-wrap pt-3">
                <a href="{{ route('tenant.notice.manage.index') }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-list-ul me-1"></i>配信管理一覧へ
                </a>
                @if ($notice)
                    <a href="{{ route('tenant.notice.manage.show', $notice->id) }}" class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-eye me-1"></i>作成したお知らせを見る
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
