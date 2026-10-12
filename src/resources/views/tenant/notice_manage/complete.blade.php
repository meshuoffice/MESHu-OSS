@extends('layouts.tenant')

@section('title', 'お知らせ登録完了 | MESHu')

@section('content')
<div class="container-fluid p-4">

    <!-- 完了カード -->
    <div class="card card-custom bg-white mx-auto border-0 shadow-sm mt-4 text-center p-5" style="max-width: 680px;">
        <div class="card-body">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            </div>
            
            @if ($notice->is_published)
                <h3 class="h4 fw-bold mb-3">お知らせの登録・配信が完了しました</h3>
                <p class="text-muted mb-4">
                    作成したお知らせは、つながり（MESHu）承認済みの取引先企業へ正常に公開・配信されました。<br>
                    今後の閲覧状況や修正は配信管理一覧よりご確認いただけます。
                </p>
            @else
                <h3 class="h4 fw-bold mb-3">お知らせを下書き保存しました</h3>
                <p class="text-muted mb-4">
                    下書きとして保存されました。他企業へはまだ公開されていません。<br>
                    内容を公開する場合は、編集画面よりステータスを「公開」へ変更してください。
                </p>
            @endif

            <div class="d-flex justify-content-center gap-3 flex-wrap pt-3">
                <a href="{{ route('tenant.notice.manage.index') }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-list-ul me-1"></i>配信管理一覧へ
                </a>
                <a href="{{ route('tenant.notice.manage.show', $notice->id) }}" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-eye me-1"></i>作成したお知らせを見る
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
