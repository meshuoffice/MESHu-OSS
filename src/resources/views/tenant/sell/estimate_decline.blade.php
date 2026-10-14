@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '見積辞退の確認'))

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/sell') }}" class="text-decoration-none">販売 (売手)</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/sell/detail/' . $tx['id']) }}" class="text-decoration-none">取引詳細 #{{ $tx['id'] }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">見積辞退の確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>見積辞退の確認</h1>
    </div>
</div>

<!-- アラート -->
<div class="alert alert-danger d-flex align-items-center gap-3 mb-4 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-3 text-danger flex-shrink-0"></i>
    <div>
        <strong>【見積辞退について】</strong><br>
        この見積依頼を辞退すると、ステータスが「<strong>見積辞退</strong>」に変更され、<strong>取引は終了・ロック（再編集不可）</strong>となります。<br>
        相手企業（買手: <strong>{{ $tx['partner_name'] }}</strong>）へ辞退通知および辞退理由が送信されます。
    </div>
</div>

<form method="post" action="{{ url('/sell/estimate-decline/' . $tx['id']) }}" onsubmit="return confirm('本当にこの見積依頼を辞退しますか？\n辞退後は再編集や取引の再開はできません。');">
    @csrf

    <div class="row g-4">
        <div class="col-lg-12">
            <!-- 辞退理由入力 -->
            <div class="card card-custom bg-white shadow-sm mb-4 border-danger border-opacity-50">
                <div class="card-header bg-danger-subtle py-3 border-bottom border-danger-subtle">
                    <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-pencil-square me-2"></i>辞退理由の入力 <span class="badge bg-danger ms-1">必須</span></h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="declineCategory" class="form-label fw-bold text-dark">辞退理由種別 <span class="text-danger">*</span></label>
                        <select name="decline_category" id="declineCategory" class="form-select" required>
                            <option value="">辞退理由を選択してください</option>
                            <option value="希望納期での対応が困難なため">希望納期での対応が困難なため</option>
                            <option value="在庫・部材の調達が困難なため">在庫・部材の調達が困難なため</option>
                            <option value="仕様・技術要件への対応が困難なため">仕様・技術要件への対応が困難なため</option>
                            <option value="製造キャパシティ（生産枠）の上限超過のため">製造キャパシティ（生産枠）の上限超過のため</option>
                            <option value="価格・採算面での合意が難しいため">価格・採算面での合意が難しいため</option>
                            <option value="その他">その他（詳細を下記に入力）</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="declineReasonDetail" class="form-label fw-bold text-dark">辞退理由の詳細・メッセージ（買手企業へ通知されます）</label>
                        <textarea name="decline_reason_detail" id="declineReasonDetail" class="form-control" rows="4" placeholder="辞退に至った背景や、納期の再調整が可能かどうかの補足情報があれば入力してください..."></textarea>
                    </div>
                </div>
            </div>

            <!-- 依頼概要確認 -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>対象の依頼内容</h5>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-muted">取引番号</dt>
                        <dd class="col-sm-9 font-monospace fw-bold">#{{ $tx['id'] }}</dd>

                        <dt class="col-sm-3 text-muted">件名 / 案件名</dt>
                        <dd class="col-sm-9 fw-bold fs-6">{{ $tx['title'] }}</dd>

                        <dt class="col-sm-3 text-muted">購入元企業 (買手)</dt>
                        <dd class="col-sm-9 fw-bold text-dark">{{ $tx['partner_name'] }}</dd>

                        <dt class="col-sm-3 text-muted">希望納期</dt>
                        <dd class="col-sm-9 font-monospace">
                            {{ !empty($tx['desired_delivery_date']) ? date('Y/m/d', strtotime($tx['desired_delivery_date'])) : '指定なし' }}
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- 送信ボタン -->
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ url('/sell/detail/' . $tx['id']) }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>詳細へ戻る
                </a>
                <button type="submit" class="btn btn-danger px-5 py-2 fw-bold shadow-sm">
                    <i class="bi bi-x-circle-fill me-1"></i>見積を辞退する（取引終了）
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
