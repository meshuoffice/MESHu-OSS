@extends('admin::layouts.admin')

@section('title', $title ?? ($company->name . ' ステータス設定・削除確認 | MESHu 事務局管理'))

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.index') }}" class="text-decoration-none">企業管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.show', ['id' => $company->id]) }}" class="text-decoration-none">企業詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">ステータス設定・削除確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">ステータス設定・削除確認</h1>
    </div>
</div>

<!-- 削除確認警告エリア -->
<div class="card card-custom bg-white max-width-800 mx-auto">
    <div class="card-header bg-danger text-white py-3 border-0">
        <h5 class="m-0 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>対象企業：{{ $company->name }}</h5>
    </div>
    <div class="card-body p-4">
        
        <div class="alert alert-warning" role="alert">
            <h6 class="fw-bold mb-2">ステータス変更およびアカウント状態の管理</h6>
            <p class="mb-0 small">
                企業のアカウントステータスを「稼働」「一時停止」「削除状態」に変更できます。<br>
                削除状態に変更すると、企業および所属ユーザーのログイン・主要機能が停止されます。
            </p>
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-secondary">対象企業の情報</h6>
        <table class="table table-bordered align-middle mb-4">
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">企業ID</th>
                <td class="font-monospace fw-bold">CO-{{ sprintf('%06d', $company->id) }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">企業名（正式名称）</th>
                <td class="fw-bold text-dark">{{ $company->name }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">法人番号</th>
                <td class="font-monospace">{{ $company->corporate_number ?? '-' }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">現在のステータス</th>
                <td>
                    @if ($company->status === 'active')
                        <span class="badge bg-success-subtle text-success">稼働 (有効)</span>
                    @elseif ($company->status === 'suspended')
                        <span class="badge bg-warning-subtle text-warning-emphasis">停止中</span>
                    @else
                        <span class="badge bg-secondary">{{ $company->status }}</span>
                    @endif
                </td>
            </tr>
        </table>

        <form action="{{ route('admin.corp.delete', ['id' => $company->id]) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label fw-bold">変更後のステータスを選択してください</label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" {{ $company->status === 'active' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-success" for="statusActive">
                        <i class="bi bi-check-circle me-1"></i>稼働 (有効化する)
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="status" id="statusSuspended" value="suspended" {{ $company->status === 'suspended' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-warning-emphasis" for="statusSuspended">
                        <i class="bi bi-pause-circle me-1"></i>一時停止 (ログイン制限)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="statusDeleted" value="deleted" {{ $company->status === 'deleted' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-danger" for="statusDeleted">
                        <i class="bi bi-trash me-1"></i>削除状態にする (完全非表示化)
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ route('admin.corp.show', ['id' => $company->id]) }}" class="btn btn-outline-secondary">キャンセルして詳細に戻る</a>
                <button type="submit" class="btn btn-danger px-4" onclick="return confirm('本当にステータスを変更・実行してよろしいですか？');">実行する <i class="bi bi-check-lg ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
