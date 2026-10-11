@extends('layouts.tenant')

@section('title', 'マイプロフィール確認 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.profile') }}" class="text-decoration-none">マイプロフィール</a></li>
                <li class="breadcrumb-item active" aria-current="page">更新内容の確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">更新内容の確認</h1>
    </div>
</div>

<form action="{{ route('tenant.profile.update') }}" method="POST" class="card card-custom bg-white p-4">
        @csrf

        <input type="hidden" name="email" value="{{ $postData['email'] }}">
        <input type="hidden" name="password" value="{{ $postData['password'] ?? '' }}">
        <input type="hidden" name="user_name" value="{{ $postData['user_name'] }}">
        <input type="hidden" name="user_name_kana" value="{{ $postData['user_name_kana'] ?? '' }}">
        <input type="hidden" name="department" value="{{ $postData['department'] ?? '' }}">
        <input type="hidden" name="position" value="{{ $postData['position'] ?? '' }}">
        <input type="hidden" name="notify_trade" value="{{ !empty($postData['notify_trade']) ? '1' : '0' }}">
        <input type="hidden" name="notify_comment" value="{{ !empty($postData['notify_comment']) ? '1' : '0' }}">
        <input type="hidden" name="notify_chat" value="{{ !empty($postData['notify_chat']) ? '1' : '0' }}">

        <table class="table table-bordered align-middle">
            <tbody>
                <tr>
                    <th class="bg-light" style="width: 30%;">ログインID (メールアドレス)</th>
                    <td>{{ $postData['email'] }}</td>
                </tr>
                <tr>
                    <th class="bg-light">パスワード</th>
                    <td>{{ !empty($postData['password']) ? '******** (変更あり)' : '******** (変更なし)' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">名前</th>
                    <td>{{ $postData['user_name'] }}</td>
                </tr>
                <tr>
                    <th class="bg-light">名前カナ</th>
                    <td>{{ $postData['user_name_kana'] ?? '未設定' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">部署</th>
                    <td>{{ $postData['department'] ?? '未設定' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">役職</th>
                    <td>{{ $postData['position'] ?? '未設定' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">お知らせメール受信設定</th>
                    <td>
                        <span class="me-3">取引: {!! !empty($postData['notify_trade']) ? '<span class="badge bg-success">受信する</span>' : '<span class="badge bg-secondary">受信しない</span>' !!}</span>
                        <span class="me-3">コメント: {!! !empty($postData['notify_comment']) ? '<span class="badge bg-success">受信する</span>' : '<span class="badge bg-secondary">受信しない</span>' !!}</span>
                        <span>チャット: {!! !empty($postData['notify_chat']) ? '<span class="badge bg-success">受信する</span>' : '<span class="badge bg-secondary">受信しない</span>' !!}</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-between mt-4">
            <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4"><i class="bi bi-arrow-left me-1"></i> 修正する</button>
            <button type="submit" class="btn btn-success px-4">この内容で更新する <i class="bi bi-check-lg ms-1"></i></button>
        </div>
    </form>
@endsection
