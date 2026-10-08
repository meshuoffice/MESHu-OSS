@extends('admin::layouts.admin')

@php
$isEdit = !empty($isEdit);
$targetAction = route('admin.corp.complete');
@endphp

@section('title', $title ?? ($isEdit ? '企業情報更新確認 | MESHu 事務局管理' : '企業登録確認 | MESHu 事務局管理'))

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.index') }}" class="text-decoration-none">企業管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? '企業情報更新確認' : '企業登録確認' }}</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">{{ $isEdit ? '企業情報更新確認' : '企業登録確認' }}</h1>
    </div>
</div>

<!-- 確認内容エリア -->
<div class="card card-custom bg-white max-width-800 mx-auto">
    <div class="card-header bg-light py-3 border-0">
        <h5 class="m-0 fw-bold text-center">以下の内容で{{ $isEdit ? '更新' : '登録' }}します。よろしいですか？</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ $targetAction }}" method="POST">
            @csrf

            <!-- POSTデータの保持 -->
            @foreach ($postData as $key => $val)
                @if (is_array($val))
                    @foreach ($val as $subVal)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">企業基本情報</h6>
            <table class="table table-bordered align-middle mb-4">
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">企業名・法人名</th>
                    <td>{{ $postData['name'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">企業名カナ</th>
                    <td>{{ $postData['abbreviated_kana'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">法人名略称</th>
                    <td>{{ $postData['abbreviated_name'] ?? '-' }}</td>
                </tr>
                @if (!empty($postData['sub_name']))
                    <tr>
                        <th class="bg-light text-muted w-25 small fw-bold">拠点名・区分名</th>
                        <td>{{ $postData['sub_name'] }}</td>
                    </tr>
                @endif
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">専用ログインURL識別子</th>
                    <td class="font-monospace"><span class="badge bg-light text-dark border">{{ $postData['url_code'] ?? '-' }}</span></td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">法人番号</th>
                    <td class="font-monospace">{{ $postData['corporate_number'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">電話番号</th>
                    <td class="font-monospace">{{ $postData['tel'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">ホームページ</th>
                    <td>{{ $postData['homepage'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">稼働ステータス</th>
                    <td>
                        @if (($postData['status'] ?? '') === 'active')
                            <span class="badge bg-success-subtle text-success">稼働</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning-emphasis">停止中</span>
                        @endif
                    </td>
                </tr>
            </table>

            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">所在地情報</h6>
            <table class="table table-bordered align-middle mb-4">
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">郵便番号</th>
                    <td class="font-monospace">{{ $postData['zip_code'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">都道府県 / 市区町村 / 住所</th>
                    <td>
                        {{ $prefectures[$postData['prefecture_code'] ?? ''] ?? '' }}
                        {{ $postData['city'] ?? '' }}
                        {{ $postData['address'] ?? '' }}
                    </td>
                </tr>
            </table>

            @if (!$isEdit)
                <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">初期管理者アカウント情報</h6>
                <table class="table table-bordered align-middle mb-4">
                    <tr>
                        <th class="bg-light text-muted w-25 small fw-bold">管理者担当者名</th>
                        <td>{{ $postData['user_name'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-muted w-25 small fw-bold">メールアドレス</th>
                        <td class="font-monospace">{{ $postData['email'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-muted w-25 small fw-bold">所属部署 / 役職</th>
                        <td>{{ $postData['department'] ?? '-' }} / {{ $postData['position'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light text-muted w-25 small fw-bold">初期パスワード</th>
                        <td class="font-monospace">•••••••• (セキュリティのため非表示)</td>
                    </tr>
                </table>
            @endif

            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">管理設定</h6>
            <table class="table table-bordered align-middle mb-4">
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">MESHu認証バッジ</th>
                    <td>
                        @if (!empty($postData['grant_badge']) && $postData['grant_badge'] == '1')
                            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>★認証済バッジを付与する</span>
                        @else
                            <span class="badge bg-light text-muted border">付与しない</span>
                        @endif
                    </td>
                </tr>
            </table>

            <div class="d-flex justify-content-between border-top pt-4">
                <button type="button" onclick="history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>修正する</button>
                <button type="submit" class="btn btn-primary px-4" style="background-color: #38bdf8; border-color: #38bdf8; color: #0f172a;">{{ $isEdit ? '更新を保存する' : '確定する' }} <i class="bi bi-check-lg ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
