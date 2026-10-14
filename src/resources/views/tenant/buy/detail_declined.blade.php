@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '取引詳細 (辞退)'))

@push('styles')
<style>
    .timeline-step {
        position: relative;
        flex: 1;
    }
    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        left: 50%;
        width: 100%;
        height: 2px;
        background-color: #dee2e6;
        z-index: 1;
    }
    .timeline-step.completed:not(:last-child)::after {
        background-color: #dc3545;
    }
    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        position: relative;
        z-index: 2;
        font-size: 1.1rem;
        color: #6c757d;
    }
    .timeline-step.completed .step-icon {
        background-color: #198754;
        border-color: #198754;
        color: #fff;
    }
    .timeline-step.declined .step-icon {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
    .table-items th {
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .comment-bubble {
        border-radius: 12px;
        padding: 1rem;
        position: relative;
    }
    .comment-bubble-mine {
        background-color: #e7f1ff;
        border: 1px solid #b6d4fe;
    }
    .comment-bubble-partner {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
    }
</style>
@endpush

@section('content')
<!-- パンくずリスト & ページタイトル & アクションボタン -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/buy') }}" class="text-decoration-none">購入 (買手)</a></li>
                <li class="breadcrumb-item active" aria-current="page">取引詳細 #{{ $tx['id'] }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 m-0 fw-bold">{{ $tx['title'] }}</h1>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1 fs-6">
                <i class="bi bi-x-circle me-1"></i>{{ $tx['status'] === 'estimate_declined' ? '見積辞退' : '注文辞退' }}
            </span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ url('/buy/estimate/new') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm px-3">
            <i class="bi bi-arrow-repeat"></i>
            <span>他社に見積を再依頼する</span>
        </a>
    </div>
</div>

<!-- 取引進捗ステッパー -->
<div class="card card-custom bg-white shadow-sm mb-4">
    <div class="card-body py-4 px-3">
        <div class="d-flex justify-content-between text-center">
            <div class="timeline-step completed">
                <div class="step-content w-100">
                    <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="fw-bold small">1. 見積依頼 / 注文</div>
                    <div class="text-muted" style="font-size: 0.75rem;">{{ date('Y/m/d H:i', strtotime($tx['created_at'])) }}</div>
                </div>
            </div>
            <div class="timeline-step declined">
                <div class="step-content w-100">
                    <div class="step-icon"><i class="bi bi-x-lg"></i></div>
                    <div class="fw-bold small text-danger">2. {{ $tx['status'] === 'estimate_declined' ? '見積辞退' : '注文辞退' }} (取引終了)</div>
                    <div class="text-danger fw-semibold" style="font-size: 0.75rem;">{{ date('Y/m/d H:i', strtotime($tx['updated_at'])) }}</div>
                </div>
            </div>
            <div class="timeline-step opacity-50">
                <div class="step-content w-100">
                    <div class="step-icon"><i class="bi bi-cart"></i></div>
                    <div class="fw-bold small text-muted">3. 注文 (発注)</div>
                    <div class="text-muted" style="font-size: 0.75rem;">-</div>
                </div>
            </div>
            <div class="timeline-step opacity-50">
                <div class="step-content w-100">
                    <div class="step-icon"><i class="bi bi-check-all"></i></div>
                    <div class="fw-bold small text-muted">4. 注文回答 (ゴール)</div>
                    <div class="text-muted" style="font-size: 0.75rem;">-</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 辞退理由通知カード -->
<div class="card card-custom bg-white shadow-sm mb-4 border-danger border-opacity-50">
    <div class="card-header bg-danger-subtle py-3 border-bottom border-danger-subtle d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
        <h5 class="mb-0 fw-bold text-danger">販売企業からの辞退通知</h5>
        <span class="badge bg-danger ms-auto">取引終了（ロック）</span>
    </div>
    <div class="card-body p-4">
        <div class="row mb-3">
            <div class="col-md-3 text-muted fw-bold">辞退理由種別</div>
            <div class="col-md-9">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 px-3 py-1">
                    {{ $tx['decline_category'] ?? '辞退理由の記載なし' }}
                </span>
            </div>
        </div>
        @if (!empty($tx['decline_reason']))
            <div class="row mb-3">
                <div class="col-md-3 text-muted fw-bold">販売企業からのメッセージ</div>
                <div class="col-md-9">
                    <div class="p-3 bg-light rounded border text-secondary small lh-base" style="white-space: pre-wrap;">{{ $tx['decline_reason'] }}</div>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-md-3 text-muted fw-bold">辞退日時</div>
            <div class="col-md-9 font-monospace small text-muted">
                {{ date('Y年m月d日 H:i', strtotime($tx['updated_at'])) }}
            </div>
        </div>
    </div>
</div>

<!-- メイン 2カラム構成 -->
<div class="row g-4">
    <!-- 左メインカラム (8) -->
    <div class="col-lg-8">

        <!-- 明細テーブル -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-secondary"></i>依頼明細内容</h5>
                <span class="badge bg-secondary-subtle text-secondary font-monospace">明細 {{ count($items ?? []) }} 件</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-items align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">No.</th>
                                <th>品名・型番・仕様</th>
                                <th style="width: 110px;" class="text-end">数量</th>
                                <th style="width: 70px;" class="text-center">単位</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (empty($items))
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">明細データがありません。</td>
                                </tr>
                            @else
                                @foreach ($items as $idx => $item)
                                    <tr>
                                        <td class="text-center font-monospace text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $item['item_name'] }}</div>
                                            @if (!empty($item['specification']))
                                                <small class="text-muted">{{ $item['specification'] }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace">{{ number_format((float)$item['quantity'], 2) }}</td>
                                        <td class="text-center">{{ $item['unit'] ?? '個' }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 添付ファイル -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-paperclip me-2 text-secondary"></i>添付ファイル</h5>
                <span class="badge bg-secondary-subtle text-secondary">{{ count($attachments ?? []) }} 件</span>
            </div>
            <div class="card-body p-3">
                @if (empty($attachments))
                    <div class="text-muted small py-2">添付されているファイルはありません。</div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($attachments as $att)
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-arrow-down-fill text-secondary fs-4"></i>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $att['file_name'] }}</div>
                                        <div class="small text-muted">
                                            {{ round($att['file_size'] / 1024, 1) }} KB ・ {{ date('Y/m/d H:i', strtotime($att['created_at'])) }}
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ url('/buy/attachment/' . $att['id']) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-download me-1"></i>ダウンロード
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- 取引コメント -->
        <div class="card card-custom bg-white shadow-sm mb-4" id="comments">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-dots-fill me-2 text-secondary"></i>取引メッセージ・コメント</h5>
                <span class="badge bg-secondary-subtle text-secondary">{{ count($comments ?? []) }} 件のメッセージ</span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3 mb-4">
                    @if (empty($comments))
                        <div class="text-muted small text-center py-3">コメントのやり取りはありません。</div>
                    @else
                        @foreach ($comments as $cmt)
                            @php
                                $isMine = (int)$cmt['company_id'] === (int)$myCompanyId;
                                $isAuthor = $isMine && (int)($cmt['user_id'] ?? 0) === (int)($myUserId ?? auth()->id());
                            @endphp
                            <div class="d-flex flex-column {{ $isMine ? 'align-items-end' : 'align-items-start' }}">
                                <div class="small text-muted mb-1 d-flex align-items-center gap-1">
                                    <span class="badge {{ $isMine ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} me-1">
                                        {{ $isMine ? '自社' : ($cmt['company_name'] ?? '相手企業') }}
                                    </span>
                                    {{ $cmt['user_name'] ?? '退職ユーザー' }} ・ {{ date('Y/m/d H:i', strtotime($cmt['created_at'])) }}
                                    @if ($isAuthor)
                                        <form method="post" action="{{ url('/buy/comment/delete/' . $cmt['id']) }}" class="d-inline ms-1" onsubmit="return confirm('このメッセージを削除しますか？');">
                                            @csrf
                                            <button type="submit" class="btn btn-link p-0 text-secondary text-danger-hover align-middle" title="メッセージを削除" style="font-size: 0.85rem; text-decoration: none;">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="comment-bubble {{ $isMine ? 'comment-bubble-mine' : 'comment-bubble-partner' }}" style="max-width: 80%; white-space: pre-wrap; text-align: left;">
                                    {{ trim($cmt['comment']) }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- 辞退後はコメント投稿フォームも利用可能（連絡用） -->
                <form method="post" action="{{ url('/buy/comment/' . $tx['id']) }}" class="border-top pt-3">
                    @csrf
                    <label for="commentInput" class="form-label fw-semibold small text-dark">新しいメッセージを投稿（確認・連絡用）</label>
                    <textarea name="comment" class="form-control mb-2" id="commentInput" rows="3" placeholder="取引先への確認事項や連絡内容を入力してください..." required></textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-secondary btn-sm px-3 fw-bold">
                            <i class="bi bi-send-fill me-1"></i>メッセージ送信
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- 右サイドカラム (4) -->
    <div class="col-lg-4">

        <!-- 取引概要 -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle-fill me-2 text-secondary"></i>取引情報</h5>
            </div>
            <div class="card-body p-3">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">取引ID</dt>
                    <dd class="col-sm-8 font-monospace fw-bold">#{{ $tx['id'] }}</dd>

                    <dt class="col-sm-4 text-muted">ステータス</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-secondary-subtle text-secondary border">
                            {{ $tx['status'] === 'estimate_declined' ? '見積辞退' : '注文辞退' }}
                        </span>
                    </dd>

                    <dt class="col-sm-4 text-muted">希望納期</dt>
                    <dd class="col-sm-8 font-monospace">
                        {{ !empty($tx['desired_delivery_date']) ? date('Y/m/d', strtotime($tx['desired_delivery_date'])) : '-' }}
                    </dd>

                    <dt class="col-sm-4 text-muted">起票日時</dt>
                    <dd class="col-sm-8">{{ date('Y/m/d H:i', strtotime($tx['created_at'])) }}</dd>

                    <dt class="col-sm-4 text-muted">辞退日時</dt>
                    <dd class="col-sm-8">{{ date('Y/m/d H:i', strtotime($tx['updated_at'])) }}</dd>
                </dl>
            </div>
        </div>

        <!-- 販売先企業情報 -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-building-fill me-2 text-secondary"></i>販売先企業 (売手)</h5>
            </div>
            <div class="card-body p-3 small">
                <div class="fw-bold text-secondary fs-6 mb-2">{{ $tx['partner_name'] }}</div>
                <div class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>{{ ($tx['partner_city'] ?? '') . ($tx['partner_address'] ?? '') }}</div>
                @if (!empty($tx['partner_tel']))
                    <div class="text-muted mb-1"><i class="bi bi-telephone me-1"></i>{{ $tx['partner_tel'] }}</div>
                @endif
                <a href="{{ url('/link/detail/' . $tx['company_link_id']) }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i>企業詳細プロフィールを見る
                </a>
            </div>
        </div>

        <!-- 買手備考 -->
        @if (!empty($tx['buyer_remarks']))
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-left-text me-2 text-secondary"></i>買手備考（自社指示事項）</h5>
                </div>
                <div class="card-body p-3 small text-secondary bg-light" style="white-space: pre-wrap;">{{ $tx['buyer_remarks'] }}</div>
            </div>
        @endif

        <!-- ステータス履歴 -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>ステータス履歴</h5>
            </div>
            <div class="card-body p-3">
                @if (empty($histories))
                    <div class="text-muted small">履歴情報はありません。</div>
                @else
                    <ul class="list-unstyled small mb-0">
                        @foreach ($histories as $h)
                            <li class="mb-2 pb-2 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <span class="badge bg-secondary-subtle text-secondary">{{ \App\Models\Transaction::getLabel($h['status']) }}</span>
                                    <span class="text-muted">{{ date('m/d H:i', strtotime($h['created_at'])) }}</span>
                                </div>
                                <div class="text-dark fw-semibold mt-1">
                                    {{ $h['company_name'] ?? '' }}: {{ $h['user_name'] ?? 'システム' }}
                                </div>
                                @if (!empty($h['note']))
                                    <div class="text-muted mt-1 small bg-light p-1 rounded">{{ $h['note'] }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
