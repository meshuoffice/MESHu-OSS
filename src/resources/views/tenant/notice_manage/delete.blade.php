@extends('layouts.tenant')

@section('title', 'お知らせ削除確認 | MESHu')

@section('content')
<div class="container-fluid p-4">

    <!-- パンくずリスト & ページタイトル -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/settings') }}" class="text-decoration-none">各種設定</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenant.notice.manage.index') }}" class="text-decoration-none">お知らせ配信管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">削除確認</li>
                </ol>
            </nav>
            <h1 class="h3 m-0 fw-bold">お知らせ削除確認</h1>
        </div>
    </div>

    <!-- 削除確認カード -->
    <div class="card card-custom bg-white mx-auto border-0 shadow-sm" style="max-width: 760px;">
        <div class="card-header bg-danger-subtle text-danger py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>お知らせの削除</h5>
            <span class="badge bg-secondary-subtle text-secondary">ステータス: 下書き</span>
        </div>
        <div class="card-body p-4">
            
            <div class="alert alert-danger mb-4 small" role="alert">
                このお知らせ（下書きデータおよび添付ファイル）を完全に削除します。この操作は取り消せません。本当によろしいですか？
            </div>

            <table class="table table-bordered align-middle mb-4">
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">お知らせID</th>
                    <td class="font-monospace fw-bold">NT-{{ str_pad((string)$notice->id, 4, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">タイトル</th>
                    <td class="fw-bold">{{ $notice->title }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">ステータス</th>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary">下書き（未公開）</span>
                    </td>
                </tr>
            </table>

            <form action="{{ route('tenant.notice.manage.delete', $notice->id) }}" method="POST">
                @csrf
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('tenant.notice.manage.show', $notice->id) }}" class="btn btn-outline-secondary px-4">キャンセル</a>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="bi bi-trash me-1"></i>完全に削除する
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
