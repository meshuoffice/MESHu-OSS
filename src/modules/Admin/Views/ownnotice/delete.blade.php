@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.notice.index') }}" class="text-decoration-none">お知らせ管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.notice.show', $notice->id) }}" class="text-decoration-none">お知らせ詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ削除確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ削除確認</h1>
    </div>
</div>

<!-- 削除確認警告エリア -->
<div class="card card-custom bg-white mx-auto" style="max-width: 800px;">
    <div class="card-header bg-danger text-white py-3 border-0">
        <h5 class="m-0 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>警告：お知らせの削除</h5>
    </div>
    <div class="card-body p-4">
        
        <div class="alert alert-danger" role="alert">
            <h6 class="fw-bold mb-2">この操作は取り消せません。</h6>
            <p class="mb-0 small">
                お知らせを削除すると、関連する添付ファイルも含めてデータベースから完全に削除されます。<br>
                本当に以下のお知らせを削除してよろしいですか？
            </p>
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-secondary">削除対象お知らせの情報</h6>
        <table class="table table-bordered align-middle mb-4">
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">お知らせID</th>
                <td class="font-monospace fw-bold">NT-{{ sprintf('%04d', $notice->id) }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">発信元</th>
                <td>
                    @if ($notice->sender_type === 'admin')
                        事務局
                    @else
                        {{ $notice->company->name ?? '企業' }}
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">カテゴリ</th>
                <td>
                    @if ($notice->category === 'important')
                        <span class="badge bg-danger">重要</span>
                    @elseif ($notice->category === 'maintenance')
                        <span class="badge bg-warning text-dark">メンテナンス</span>
                    @else
                        <span class="badge bg-info text-dark">お知らせ</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">タイトル</th>
                <td class="fw-bold text-danger">{{ $notice->title }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">掲載開始日時</th>
                <td class="font-monospace">
                    {{ $notice->published_at ? $notice->published_at->format('Y-m-d H:i') : '-' }}
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">ステータス</th>
                <td>
                    <span class="badge bg-secondary-subtle text-secondary">下書き（非公開）</span>
                </td>
            </tr>
        </table>

        <form action="{{ route('admin.notice.delete', $notice->id) }}" method="POST">
            @csrf
            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.notice.show', $notice->id) }}" class="btn btn-outline-secondary">キャンセルして戻る</a>
                <button type="submit" class="btn btn-danger px-4">削除を実行する <i class="bi bi-trash ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
