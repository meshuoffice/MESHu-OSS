@extends('layouts.tenant')

@section('title', 'チャット | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item active" aria-current="page">チャット</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">チャット</h1>
    </div>
</div>

<div class="card card-custom bg-white shadow-sm overflow-hidden" style="min-height: 680px;">
    <div class="row g-0 flex-grow-1" style="min-height: 680px;">
        <!-- 左ペイン: つながり企業一覧 -->
        <div class="col-12 col-md-5 col-lg-4 border-end bg-white d-flex flex-column">
            <!-- 検索 & フィルターエリア (mshdev 準拠: リアルタイム絞り込み) -->
            <div class="p-3 border-bottom bg-white">
                <!-- 企業名で検索フォーム (Enterでのページリロードを抑止しリアルタイムに絞り込み) -->
                <form onsubmit="return false;" class="mb-2" id="chatSearchForm">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               id="partnerSearchInput"
                               class="form-control border-start-0 ps-0"
                               placeholder="企業名で検索"
                               value="{{ $keyword ?? '' }}"
                               autocomplete="off">
                        <button class="btn btn-outline-secondary border-start-0 text-muted {{ empty($keyword) ? 'd-none' : '' }}"
                                type="button"
                                id="clearSearchBtn"
                                title="検索をクリア">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </form>

                <!-- フィルターボタン: すべて / 未読あり (リロードなしで即座に切り替え) -->
                <div class="d-flex gap-2" id="chatFilterGroup">
                    <button type="button"
                            class="btn btn-sm {{ ($filter ?? 'all') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3 fw-semibold d-inline-flex align-items-center filter-tab-btn"
                            data-filter="all">
                        <span>すべて</span>
                        <span class="badge {{ ($filter ?? 'all') === 'all' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' }} rounded-pill ms-1 font-monospace" id="allCountBadge">
                            {{ $allLinkCount ?? count($linkList) }}
                        </span>
                    </button>
                    <button type="button"
                            class="btn btn-sm {{ ($filter ?? 'all') === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3 fw-semibold d-inline-flex align-items-center filter-tab-btn"
                            data-filter="unread">
                        <span>未読あり</span>
                        <span class="badge bg-danger text-white rounded-pill ms-1 font-monospace {{ ($totalUnreadCount ?? 0) > 0 ? '' : 'd-none' }}" id="unreadCountBadge">
                            {{ $totalUnreadCount ?? 0 }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- 企業リスト -->
            <div class="overflow-auto flex-grow-1" id="chatLinkListContainer" style="max-height: 580px;">
                @forelse ($linkList as $item)
                    @php
                        $isActive = ($activeLink && (int)$activeLink->id === (int)$item['link_id']);
                    @endphp
                    <a href="{{ route('tenant.chat.index', ['link_id' => $item['link_id'], 'keyword' => $keyword ?? '', 'filter' => $filter ?? 'all']) }}"
                       class="d-block p-3 text-decoration-none chat-link-item {{ $isActive ? 'active' : '' }}"
                       data-partner-name="{{ mb_strtolower($item['partner_name']) }}"
                       data-partner-abbr="{{ mb_strtolower($item['partner_abbreviated_name']) }}"
                       data-unread="{{ (int)$item['unread_count'] }}">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle {{ $isActive ? 'bg-primary text-white' : 'bg-secondary text-white' }} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 0.9rem; font-weight: 600;">
                                {{ mb_substr($item['partner_abbreviated_name'], 0, 2) }}
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                    <span class="partner-name text-truncate {{ $isActive ? 'text-primary fw-bold' : 'text-dark fw-semibold' }}" style="max-width: 170px;">
                                        {{ $item['partner_name'] }}
                                    </span>
                                    @if ($item['last_message_at'])
                                        <small class="text-muted font-monospace" style="font-size: 0.75rem;">
                                            {{ $item['last_message_at']->format('m/d H:i') }}
                                        </small>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="small text-muted mb-0 text-truncate" style="max-width: 190px;">
                                        {{ $item['last_message_body'] ?: 'メッセージはまだありません' }}
                                    </p>
                                    @if ($item['unread_count'] > 0)
                                        <span class="badge bg-danger rounded-pill flex-shrink-0 ms-1 font-monospace unread-bubble">
                                            {{ $item['unread_count'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-muted" id="serverEmptyBlock">
                        <i class="bi bi-people fs-1 text-secondary mb-2 d-block"></i>
                        @if (!empty($keyword) || ($filter ?? 'all') === 'unread')
                            <p class="mb-2">該当する企業が見つかりませんでした。</p>
                            <a href="{{ route('tenant.chat.index') }}" class="btn btn-sm btn-outline-secondary">すべてのチャットを表示</a>
                        @else
                            <p class="mb-2">チャット可能なつながり企業がありません。</p>
                            <a href="{{ route('tenant.link.index') }}" class="btn btn-sm btn-outline-primary">企業とつながる</a>
                        @endif
                    </div>
                @endforelse
                <div class="p-4 text-center text-muted d-none" id="noMatchClientBlock">
                    <i class="bi bi-search fs-1 text-secondary mb-2 d-block"></i>
                    <p class="mb-0">一致する企業が見つかりません。</p>
                </div>
            </div>
        </div>

        <!-- 右ペイン: チャットタイムライン & メッセージ投稿 -->
        <div class="col-12 col-md-7 col-lg-8 d-flex flex-column bg-white">
            @if ($activeLink && $activePartner)
                <!-- チャット相手ヘッダー -->
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: bold; font-size: 0.85rem;">
                            {{ mb_substr($activePartner->abbreviated_name ?: $activePartner->name, 0, 2) }}
                        </div>
                        <div>
                            <h2 class="h6 mb-0 fw-bold text-dark">{{ $activePartner->name }}</h2>
                            <small class="text-muted font-monospace">連携ID: #{{ $activeLink->id }}</small>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('tenant.link.detail', ['link_id' => $activeLink->id]) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-info-circle"></i>
                            <span>企業詳細</span>
                        </a>
                    </div>
                </div>

                <!-- メッセージ履歴エリア -->
                <div class="p-3 overflow-auto flex-grow-1" id="chatTimeline" style="height: 480px; background-color: #f8fafc;">
                    @forelse ($messages as $msg)
                        @php
                            $isMine = ((int)$msg->company_id === (int)$myCompanyId);
                            $isAuthor = $isMine && ((int)$msg->user_id === (int)($myUserId ?? auth()->id()));
                            $isDeleted = $msg->trashed();
                        @endphp
                        <div class="d-flex {{ $isMine ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                            <div class="d-flex flex-column {{ $isMine ? 'align-items-end' : 'align-items-start' }}" style="max-width: 80%;">
                                <!-- メタ情報（名前・日時・削除ボタン） -->
                                <div class="small text-muted mb-1 px-1 d-flex align-items-center gap-1">
                                    @if (!$isMine)
                                        <span class="fw-semibold text-dark">{{ $msg->company->name ?? '相手先' }}</span>
                                        <span>({{ $msg->user->user_name ?? '担当者' }})</span>
                                    @else
                                        <span class="fw-semibold">{{ $msg->user->user_name ?? '自分' }}</span>
                                    @endif
                                    <span class="ms-1 font-monospace">{{ $msg->created_at->format('Y/m/d H:i') }}</span>

                                    @if ($isAuthor && !$isDeleted)
                                        <form method="post" action="{{ route('tenant.chat.delete_message', ['message_id' => $msg->id]) }}" class="d-inline ms-1" onsubmit="return confirm('このメッセージを削除しますか？');">
                                            @csrf
                                            <button type="submit" class="btn btn-link p-0 text-secondary text-danger-hover align-middle border-0 bg-transparent" title="メッセージを削除" style="font-size: 0.85rem; text-decoration: none; cursor: pointer;">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- 吹き出し本体 (mshdev 踏襲) -->
                                @if ($isDeleted)
                                    <!-- 削除後吹き出し (mshdev 踏襲: 薄色破線・斜体表示) -->
                                    <div class="comment-bubble comment-bubble-deleted">
                                        <span class="fst-italic text-muted small d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-slash-circle"></i>
                                            <span>メッセージは削除されました</span>
                                        </span>
                                    </div>
                                @else
                                    <!-- 通常吹き出し (本文と添付ファイルを内包) -->
                                    <div class="comment-bubble {{ $isMine ? 'comment-bubble-mine' : 'comment-bubble-partner' }}" style="line-height: 1.6;">
                                        @if (!empty($msg->message_body))
                                            <div style="white-space: pre-wrap; text-align: left; word-break: break-word;">{{ $msg->message_body }}</div>
                                        @endif

                                        <!-- 添付ファイル一覧（吹き出し内に統合） -->
                                        @if ($msg->attachments->isNotEmpty())
                                            <div class="{{ !empty($msg->message_body) ? 'border-top pt-2 mt-2' : '' }}">
                                                @foreach ($msg->attachments as $att)
                                                    @php
                                                        $isImage = str_starts_with($att->mime_type, 'image/');
                                                    @endphp
                                                    <!-- 添付ファイルダウンロードボックス -->
                                                    <div class="attachment-box p-2 rounded bg-white border d-flex justify-content-between align-items-center gap-2 mb-1">
                                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                                            <i class="bi {{ $isImage ? 'bi-file-earmark-image text-primary' : 'bi-file-earmark-arrow-down text-primary' }} fs-5 flex-shrink-0"></i>
                                                            <div class="min-w-0">
                                                                <div class="fw-semibold text-dark text-truncate small" style="max-width: 220px;">{{ $att->file_name }}</div>
                                                                <div class="text-muted font-monospace" style="font-size: 0.7rem;">
                                                                    {{ number_format(round($att->file_size / 1024, 1)) }} KB
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <a href="{{ route('tenant.chat.attachment', ['attachment_id' => $att->id]) }}"
                                                           class="btn btn-sm btn-outline-secondary py-1 px-2 d-inline-flex align-items-center gap-1 flex-shrink-0"
                                                           title="ダウンロード">
                                                            <i class="bi bi-download"></i>
                                                            <span class="d-none d-sm-inline small">保存</span>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="h-100 d-flex flex-column justify-content-center align-items-center text-muted">
                            <i class="bi bi-chat-square-dots fs-1 mb-2 text-secondary"></i>
                            <p class="mb-0">メッセージはまだありません。</p>
                            <small class="text-secondary">下のフォームからメッセージを送信してみましょう。</small>
                        </div>
                    @endforelse
                </div>

                <!-- メッセージ送信フォーム -->
                <div class="p-3 border-top bg-white">
                    <form method="post" action="{{ route('tenant.chat.send', ['link_id' => $activeLink->id]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label for="messageBodyInput" class="form-label fw-semibold small text-dark mb-1">新しいメッセージを投稿</label>
                            <textarea id="messageBodyInput" name="message_body" rows="3" class="form-control" placeholder="取引先への確認事項や連絡内容を入力してください..." style="resize: none;">{{ old('message_body') }}</textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <label for="attachments" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 cursor-pointer">
                                    <i class="bi bi-paperclip"></i>
                                    <span>ファイル添付</span>
                                </label>
                                <input type="file" id="attachments" name="attachments[]" class="d-none" multiple onchange="updateFileLabel(this)">
                                <span id="fileLabel" class="small text-muted text-truncate" style="max-width: 220px;"></span>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold d-inline-flex align-items-center gap-1">
                                <i class="bi bi-send-fill me-1"></i>
                                <span>メッセージ送信</span>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="h-100 d-flex flex-column justify-content-center align-items-center text-muted p-5">
                    <i class="bi bi-chat-dots fs-1 mb-3 text-secondary"></i>
                    <h5>チャットルームを選択してください</h5>
                    <p class="text-muted small">左の一覧からつながり企業を選択すると、メッセージの送受信が可能です。</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
/* mshdev 準拠: 左側リストアイテム */
.chat-link-item {
    border-bottom: 1px solid #e2e8f0 !important;
    border-left: 4px solid transparent !important;
    transition: all 0.2s ease;
    background-color: #ffffff;
    color: #0f172a;
}
.chat-link-item:hover {
    background-color: #f8fafc;
}
/* 選択中: 左側だけ青く、下線は通常の #e2e8f0 のまま */
.chat-link-item.active {
    background-color: #eef2ff !important;
    border-left-color: var(--bs-primary) !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

/* mshdev 準拠: 先方（相手先）の吹き出しは白色 */
.comment-bubble-partner {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

/* 削除済みメッセージ吹き出し (mshdev 踏襲: 薄いグレー背景・破線・斜体) */
.comment-bubble-deleted {
    background-color: #f8fafc !important;
    border: 1px dashed #cbd5e1 !important;
    color: #94a3b8 !important;
    padding: 0.75rem 1rem;
    border-radius: 12px;
}

/* ゴミ箱ホバー色 */
.text-danger-hover:hover {
    color: #dc3545 !important;
}

/* 吹き出し内添付ファイルボックス */
.attachment-box {
    border: 1px solid rgba(0, 0, 0, 0.08) !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.cursor-pointer {
    cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
function updateFileLabel(input) {
    const label = document.getElementById('fileLabel');
    if (input.files.length > 0) {
        if (input.files.length === 1) {
            label.textContent = input.files[0].name;
        } else {
            label.textContent = `${input.files.length} 件のファイル選択中`;
        }
    } else {
        label.textContent = '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // タイムラインを最下部に自動スクロール
    const timeline = document.getElementById('chatTimeline');
    if (timeline) {
        timeline.scrollTop = timeline.scrollHeight;
    }

    // mshdev 完全準拠: クライアントサイドでのリアルタイム即時絞り込み
    const searchInput   = document.getElementById('partnerSearchInput');
    const clearBtn      = document.getElementById('clearSearchBtn');
    const items         = document.querySelectorAll('.chat-link-item');
    const noMatchBlock  = document.getElementById('noMatchClientBlock');
    const filterButtons = document.querySelectorAll('.filter-tab-btn');
    let currentFilter   = '{{ $filter ?? "all" }}';

    function executeFilter() {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        let visibleCount = 0;

        // クリアボタン表示制御
        if (clearBtn) {
            if (query.length > 0) {
                clearBtn.classList.remove('d-none');
            } else {
                clearBtn.classList.add('d-none');
            }
        }

        // 各企業アイテムの絞り込み
        items.forEach(function (item) {
            const name   = (item.getAttribute('data-partner-name') || '').toLowerCase();
            const abbr   = (item.getAttribute('data-partner-abbr') || '').toLowerCase();
            const unread = parseInt(item.getAttribute('data-unread') || '0', 10);

            // 1. タブフィルター条件（すべて or 未読あり）
            const matchesFilter = (currentFilter === 'all') || (currentFilter === 'unread' && unread > 0);

            // 2. キーワード検索条件（企業名・略称に部分一致）
            const matchesQuery = (query === '') || name.includes(query) || abbr.includes(query);

            if (matchesFilter && matchesQuery) {
                item.style.setProperty('display', 'block', 'important');
                visibleCount++;
            } else {
                item.style.setProperty('display', 'none', 'important');
            }
        });

        // 該当なしメッセージの表示制御
        if (noMatchBlock) {
            if (visibleCount === 0 && items.length > 0) {
                noMatchBlock.classList.remove('d-none');
            } else {
                noMatchBlock.classList.add('d-none');
            }
        }
    }

    // 入力イベント（1文字入力・削除ごとにリアルタイムに即時絞り込み）
    if (searchInput) {
        searchInput.addEventListener('input', executeFilter);
        // Enterキーでの誤リロードを防止
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                executeFilter();
            }
        });
    }

    // クリアボタンクリック時の即時リセット
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            executeFilter();
        });
    }

    // フィルタータブ切り替え（すべて / 未読あり：ページ遷移なしで即時切り替え）
    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(function (b) {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline-secondary');
                const badge = b.querySelector('.badge');
                if (badge && b.getAttribute('data-filter') === 'all') {
                    badge.classList.remove('bg-white', 'text-primary');
                    badge.classList.add('bg-secondary-subtle', 'text-secondary');
                }
            });

            this.classList.add('active', 'btn-primary');
            this.classList.remove('btn-outline-secondary');
            const activeBadge = this.querySelector('.badge');
            if (activeBadge && this.getAttribute('data-filter') === 'all') {
                activeBadge.classList.add('bg-white', 'text-primary');
                activeBadge.classList.remove('bg-secondary-subtle', 'text-secondary');
            }

            currentFilter = this.getAttribute('data-filter');
            executeFilter();
        });
    });

    // 初期実行
    if (items.length > 0) {
        executeFilter();
    }
});
</script>
@endpush
@endsection
