@extends('layouts.tenant')

@section('title', 'MESHu - ' . $title)

@section('content')
<!-- パンくずリスト & タイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.link.index') }}" class="text-decoration-none">つながり管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">招待送信</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">未登録企業の招待送信</h1>
    </div>
    <div>
        <a href="{{ route('tenant.link.search') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>
            <span>検索画面へ戻る</span>
        </a>
    </div>
</div>

<!-- 招待フォームカード -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-envelope-plus me-2 text-primary"></i>新規招待メールの送信</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('tenant.link.send_invite') }}" method="POST">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-dark">招待先企業名 <span class="badge bg-danger ms-1">必須</span></label>
                    <input type="text" name="invitee_name" class="form-control" placeholder="例: 株式会社◯◯" required value="{{ old('invitee_name') }}">
                    @error('invitee_name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-dark">招待先メールアドレス <span class="badge bg-danger ms-1">必須</span></label>
                    <input type="email" name="invitee_email" class="form-control font-monospace" placeholder="example@partner.co.jp" required value="{{ old('invitee_email') }}">
                    @error('invitee_email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-dark">招待先担当者名 <span class="badge bg-secondary-subtle text-secondary ms-1">任意</span></label>
                    <input type="text" name="invitee_person_name" class="form-control" placeholder="例: 山田 太郎" value="{{ old('invitee_person_name') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold text-dark">招待完了時の自社属性 <span class="badge bg-danger ms-1">必須</span></label>
                    <select name="inviter_role" class="form-select">
                        <option value="buyer" {{ old('inviter_role', 'buyer') === 'buyer' ? 'selected' : '' }}>自社が「購入側（買手）」として自動接続する</option>
                        <option value="seller" {{ old('inviter_role') === 'seller' ? 'selected' : '' }}>自社が「販売側（売手）」として自動接続する</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-dark">招待メッセージ <span class="badge bg-secondary-subtle text-secondary ms-1">任意</span></label>
                <textarea name="message" class="form-control" rows="3" placeholder="相手企業へのご挨拶や取引案内のメッセージをご入力ください。">{{ old('message') }}</textarea>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                    <i class="bi bi-send me-1"></i>招待メールを送信する
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 招待送信履歴テーブル -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold fs-6">送信済み招待履歴 (全 {{ count($invitations) }} 件)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 140px;">送信日時</th>
                    <th scope="col">招待先企業名</th>
                    <th scope="col">送信先メールアドレス</th>
                    <th scope="col">自社属性</th>
                    <th scope="col">ステータス</th>
                </tr>
            </thead>
            <tbody>
                @if ($invitations->isEmpty())
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">送信済みの招待履歴はありません。</td>
                    </tr>
                @else
                    @foreach ($invitations as $inv)
                        <tr>
                            <td class="font-monospace small text-muted">{{ $inv->created_at->format('Y-m-d H:i') }}</td>
                            <td class="fw-bold text-dark">{{ $inv->invitee_name }}</td>
                            <td class="font-monospace small">{{ $inv->invitee_email }}</td>
                            <td>
                                @if ($inv->inviter_role === 'buyer')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">購入側(買)</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">販売側(売)</span>
                                @endif
                            </td>
                            <td>
                                @if ($inv->isRegistered())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>登録完了（連携済）</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-clock me-1"></i>会員登録待ち</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
