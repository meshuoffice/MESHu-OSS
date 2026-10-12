@extends('layouts.tenant')

@section('title', 'MESHu - 従業員編集')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.users.index') }}" class="text-decoration-none">従業員管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.users.show', ['user' => $targetUser->id]) }}" class="text-decoration-none">従業員詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">編集</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">従業員情報の編集</h1>
    </div>
</div>

<!-- エラー表示メッセージ -->
@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong>入力内容にエラーがあります。</strong> 下記の項目をご確認ください。
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<!-- 編集フォーム -->
<div class="card card-custom bg-white shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>対象：{{ $targetUser->user_name }} 様</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('tenant.users.confirm_update', ['user' => $targetUser->id]) }}" method="POST" novalidate>
            @csrf

            <!-- 基本情報 -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="user_name" class="form-label fw-semibold text-dark">名前 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" name="user_name" class="form-control @error('user_name') is-invalid @enderror" id="user_name" value="{{ old('user_name', $targetUser->user_name) }}" required placeholder="例：山田 太郎">
                    @error('user_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="user_name_kana" class="form-label fw-semibold text-dark">名前カナ <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="text" name="user_name_kana" class="form-control @error('user_name_kana') is-invalid @enderror" id="user_name_kana" value="{{ old('user_name_kana', $targetUser->user_name_kana) }}" required placeholder="例：ヤマダ タロウ">
                    @error('user_name_kana')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">全角カタカナで入力してください。</div>
                </div>
            </div>

            <!-- ログインID & 連絡先 -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold text-dark">ログインID (メールアドレス) <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email', $targetUser->email) }}" required placeholder="例：yamada@sample-next.co.jp">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">ログインIDとして使用するメールアドレスを入力します。</div>
                </div>
                <div class="col-md-3">
                    <label for="department" class="form-label fw-semibold text-dark">部署 <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                    <input type="text" name="department" class="form-control" id="department" value="{{ old('department', $targetUser->department) }}" placeholder="例：営業部">
                </div>
                <div class="col-md-3">
                    <label for="position" class="form-label fw-semibold text-dark">役職 <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                    <input type="text" name="position" class="form-control" id="position" value="{{ old('position', $targetUser->position) }}" placeholder="例：課長">
                </div>
            </div>

            <!-- 権限 & ステータス -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="role" class="form-label fw-semibold text-dark">アクセス権限 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" id="role" required>
                        <option value="staff" {{ old('role', $targetUser->role) === 'staff' ? 'selected' : '' }}>一般ユーザー（受発注・取引閲覧のみ）</option>
                        <option value="admin" {{ old('role', $targetUser->role) === 'admin' ? 'selected' : '' }}>自社管理者（企業情報・各種設定・全操作可能）</option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">「自社管理者」権限を選択すると【各種設定】ポータル画面へのアクセスが可能になります。</div>
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label fw-semibold text-dark">アカウントステータス <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                        <option value="active" {{ old('status', $targetUser->status) === 'active' ? 'selected' : '' }}>有効（ログイン許可）</option>
                        <option value="suspended" {{ old('status', $targetUser->status) === 'suspended' ? 'selected' : '' }}>停止中（ログイン遮断）</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- 担当区分 -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold text-dark">担当区分 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                    <div class="d-flex flex-column flex-md-row gap-3 border rounded p-3 bg-light">
                        <div class="form-check me-4">
                            <input class="form-check-input" type="radio" name="is_all_assigned" id="is_all_assigned_1" value="1" {{ (int)old('is_all_assigned', $targetUser->is_all_assigned) === 1 ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="is_all_assigned_1">
                                すべての取引先を担当
                                <span class="d-block small text-muted fw-normal">自社が連携するすべての取引先企業と取引・閲覧が可能です。</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_all_assigned" id="is_all_assigned_0" value="0" {{ (int)old('is_all_assigned', $targetUser->is_all_assigned) === 0 ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="is_all_assigned_0">
                                個別担当分け
                                <span class="d-block small text-muted fw-normal">割り当てられた特定の取引先企業とのみ取引・閲覧が可能です。</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- パスワード再発行についての案内 -->
            <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-info-circle text-primary fs-5"></i>
                <div class="small text-muted">
                    パスワードの変更・再発行は、従業員詳細画面の **「パスワードの再発行」** ボタンより実行可能です。
                </div>
            </div>

            <!-- ボタンエリア -->
            <div class="d-flex justify-content-between border-top pt-4 mt-4">
                <a href="{{ route('tenant.users.show', ['user' => $targetUser->id]) }}" class="btn btn-outline-secondary px-4">キャンセル</a>
                <button type="submit" class="btn btn-primary px-4">確認画面へ</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const isAllAssigned1 = document.getElementById('is_all_assigned_1');
        
        if (roleSelect && isAllAssigned1) {
            roleSelect.addEventListener('change', function() {
                if (this.value === 'admin') {
                    isAllAssigned1.checked = true;
                }
            });
        }
    });
</script>
@endsection
