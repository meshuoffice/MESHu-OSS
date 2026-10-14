@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '新規見積依頼作成'))

@push('styles')
<style>
    .table-items th {
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .file-dropzone {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        background-color: #f8f9fa;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .file-dropzone:hover {
        border-color: #0d6efd;
        background-color: #f0f7ff;
    }
    .file-dropzone.dragover {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    .file-item-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        background-color: #e9ecef;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 0.25rem;
    }
    .seller-list-box {
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        background-color: #fff;
    }
    .seller-item {
        transition: background-color 0.15s ease;
        cursor: pointer;
    }
    .seller-item:hover {
        background-color: #f8f9fa;
    }
    .selected-company-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        background-color: #e7f1ff;
        color: #0d6efd;
        border: 1px solid #b6d4fe;
        border-radius: 16px;
        font-size: 0.8rem;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/buy') }}" class="text-decoration-none">購入 (買手)</a></li>
                <li class="breadcrumb-item active" aria-current="page">新規見積依頼</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">新規見積依頼の作成 <span class="badge bg-primary-subtle text-primary fs-6 fw-normal ms-2">相見積もり対応</span></h1>
    </div>
</div>

<!-- 見積作成フォーム -->
<form method="post" action="{{ url('/buy/estimate/confirm') }}" enctype="multipart/form-data" id="estimateForm">
    @csrf

    <div class="row g-4">
        <!-- 左カラム: 取引基本情報 & 販売企業選択 (相見積もり) -->
        <div class="col-lg-12">
            <!-- 1. 見積依頼先（相見積もり企業選択）カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-buildings-fill me-2 text-primary"></i>見積依頼先企業（相見積もり先） <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">1社以上必須</span></h5>
                    <div class="small text-muted">
                        選択中: <strong id="selectedCount" class="text-primary font-monospace fs-6">0</strong> 社
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3 py-2 px-3 small" role="alert">
                        <i class="bi bi-info-circle-fill fs-5 text-primary flex-shrink-0"></i>
                        <div>
                            複数の企業を選択して<strong>相見積もり（一括見積依頼）</strong>を行うことができます。各社には個別に依頼が届き、他社の見積先や回答内容はお互いに非公開（ブラインド）となります。
                        </div>
                    </div>

                    <!-- 選択中企業チップ表示エリア -->
                    <div class="mb-3 p-2 bg-light rounded d-flex flex-wrap gap-2 align-items-center" id="selectedChipsContainer">
                        <span class="small text-muted me-1"><i class="bi bi-check2-circle me-1"></i>依頼先:</span>
                    </div>

                    @if (empty($partnerLinks))
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>承認済みの販売連携企業が登録されていません。「つながり管理」から企業を連携してください。
                        </div>
                    @else
                        <!-- 企業検索 & 全選択/解除バー -->
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="searchSellerInput" placeholder="企業名・所在地で絞り込み...">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAll">全選択</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">選択解除</button>
                            </div>
                        </div>

                        <!-- つながり企業一覧 -->
                        <div class="seller-list-box p-2" id="sellerListBox">
                            @foreach ($partnerLinks as $idx => $p)
                                <label class="seller-item list-group-item d-flex align-items-center gap-3 p-2 border-0 rounded {{ $idx > 0 ? 'border-top' : '' }}">
                                    <input class="form-check-input seller-checkbox m-0" type="checkbox" name="link_ids[]" value="{{ $p['link_id'] }}" data-name="{{ $p['partner_name'] }}" {{ in_array((string)$p['link_id'], (array)old('link_ids', []), true) ? 'checked' : '' }}>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark">{{ $p['partner_name'] }}</div>
                                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $p['partner_full_address'] ?? ($p['partner_address'] ?? '住所未登録') }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-text small text-muted mt-2">※複数社を選択すると、同一の依頼明細で各社に個別の見積依頼が作成されます。</div>
                    @endif
                </div>
            </div>

            <!-- 2. 取引基本情報カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle-fill me-2 text-primary"></i>基本情報</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-1">
                        <div class="col-md-8">
                            <label for="titleInput" class="form-label fw-semibold text-dark">件名 / 案件名 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                            <input type="text" name="title" id="titleInput" class="form-control" placeholder="例: 2026年秋季 OA機器・オフィス部材一括見積" value="{{ old('title') }}" required maxlength="255">
                            <div class="form-text text-muted">各社へ共通で送信される取引件名です。</div>
                        </div>
                        <div class="col-md-4">
                            <label for="desiredDeliveryInput" class="form-label fw-semibold text-dark">希望納期 <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                            <input type="date" name="desired_delivery_date" id="desiredDeliveryInput" class="form-control" value="{{ old('desired_delivery_date') }}">
                            <div class="form-text text-muted">納品を希望する期日を指定します。</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 明細テーブル -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-primary"></i>見積依頼明細 <span class="text-danger">*</span></h5>
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold" id="addItemBtn">
                        <i class="bi bi-plus-lg me-1"></i>明細行を追加
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-items align-middle mb-0" id="itemsTable">
                            <thead>
                                 <tr>
                                    <th style="width: 50px;" class="text-center">No.</th>
                                    <th>品名・品目名 <span class="text-danger">*</span></th>
                                    <th style="width: 280px;">型番・仕様・規格</th>
                                    <th style="width: 130px;" class="text-end">数量 <span class="text-danger">*</span></th>
                                    <th style="width: 90px;" class="text-center">単位</th>
                                    <th style="width: 60px;" class="text-center">削除</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTbody">
                                <tr class="item-row">
                                    <td class="text-center font-monospace row-no">1</td>
                                    <td>
                                        <input type="text" name="items[0][item_name]" class="form-control form-control-sm" placeholder="例: 高耐久LANケーブル Cat6A" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][specification]" class="form-control form-control-sm" placeholder="例: 10m / 青 / 難燃仕様">
                                    </td>
                                    <td>
                                        <input type="number" step="any" min="0.01" name="items[0][quantity]" class="form-control form-control-sm text-end" value="1.00" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][unit]" class="form-control form-control-sm text-center" value="個">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1 delete-item-btn" title="行削除" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. 添付ファイル カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-paperclip me-2 text-primary"></i>添付ファイル</h5>
                </div>
                <div class="card-body p-4">
                    <div class="file-dropzone mb-3" id="dropzoneArea">
                        <i class="bi bi-cloud-arrow-up fs-2 text-primary d-block mb-2"></i>
                        <div class="fw-semibold text-dark">ここにファイルをドラッグ＆ドロップ、またはクリックして選択</div>
                        <div class="small text-muted mt-1">対応形式: PDF, Excel, Word, 画像, ZIP 等</div>
                        <input type="file" name="attachments[]" id="fileInput" multiple style="display: none;">
                    </div>

                    <!-- 添付済みファイル一覧表示エリア -->
                    <div id="fileListContainer" class="d-flex flex-wrap align-items-center"></div>
                </div>
            </div>

            <!-- 5. 備考・特記事項カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-left-text-fill me-2 text-primary"></i>備考・特記事項</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="remarksInput" class="form-label fw-semibold text-dark">備考 / メモ <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                            <textarea name="buyer_remarks" id="remarksInput" class="form-control" rows="3" placeholder="納品場所の希望、見積回答期日、その他各社への連絡事項があれば入力してください。">{{ old('buyer_remarks') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 送信アクション -->
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ url('/buy') }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                    確認画面へ進む <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>

        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- 1. 相見積もり先企業の選択UI制御 ---
        const sellerCheckboxes = document.querySelectorAll('.seller-checkbox');
        const selectedCount = document.getElementById('selectedCount');
        const chipsContainer = document.getElementById('selectedChipsContainer');
        const searchSellerInput = document.getElementById('searchSellerInput');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const btnDeselectAll = document.getElementById('btnDeselectAll');

        function updateSelectedChips() {
            if (!chipsContainer || !selectedCount) return;
            chipsContainer.innerHTML = '<span class="small text-muted me-1"><i class="bi bi-check2-circle me-1"></i>依頼先:</span>';
            let count = 0;

            sellerCheckboxes.forEach(cb => {
                if (cb.checked) {
                    count++;
                    const chip = document.createElement('span');
                    chip.className = 'selected-company-chip';
                    chip.innerHTML = `
                        <span>${cb.dataset.name}</span>
                        <i class="bi bi-x-circle-fill ms-1" style="cursor: pointer;" title="解除"></i>
                    `;
                    chip.querySelector('i').addEventListener('click', () => {
                        cb.checked = false;
                        updateSelectedChips();
                    });
                    chipsContainer.appendChild(chip);
                }
            });

            selectedCount.textContent = count;
            if (count === 0) {
                const emptyMsg = document.createElement('span');
                emptyMsg.className = 'small text-danger fw-semibold';
                emptyMsg.textContent = '※依頼先企業を1社以上選択してください';
                chipsContainer.appendChild(emptyMsg);
            }
        }

        if (sellerCheckboxes.length > 0) {
            sellerCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedChips);
            });

            if (btnSelectAll) {
                btnSelectAll.addEventListener('click', () => {
                    sellerCheckboxes.forEach(cb => {
                        const item = cb.closest('.seller-item');
                        if (item && !item.classList.contains('d-none')) {
                            cb.checked = true;
                        }
                    });
                    updateSelectedChips();
                });
            }

            if (btnDeselectAll) {
                btnDeselectAll.addEventListener('click', () => {
                    sellerCheckboxes.forEach(cb => cb.checked = false);
                    updateSelectedChips();
                });
            }

            if (searchSellerInput) {
                searchSellerInput.addEventListener('input', function () {
                    const keyword = this.value.toLowerCase().trim();
                    document.querySelectorAll('.seller-item').forEach(item => {
                        const text = item.textContent.toLowerCase();
                        const isMatch = keyword === '' || text.includes(keyword);
                        item.classList.toggle('d-none', !isMatch);
                    });
                });
            }

            updateSelectedChips();
        }

        const tbody = document.getElementById('itemsTbody');
        const addBtn = document.getElementById('addItemBtn');

        function updateRowNumbers() {
            const rows = tbody.querySelectorAll('.item-row');
            rows.forEach((row, idx) => {
                row.querySelector('.row-no').textContent = idx + 1;
                const inputs = row.querySelectorAll('input');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    if (name) {
                        input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${idx}]`));
                    }
                });
                const delBtn = row.querySelector('.delete-item-btn');
                if (delBtn) {
                    delBtn.disabled = rows.length <= 1;
                }
            });
        }

        addBtn.addEventListener('click', function () {
            const rowCount = tbody.querySelectorAll('.item-row').length;
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td class="text-center font-monospace row-no">${rowCount + 1}</td>
                <td>
                    <input type="text" name="items[${rowCount}][item_name]" class="form-control form-control-sm" placeholder="品名・品目名" required>
                </td>
                <td>
                    <input type="text" name="items[${rowCount}][specification]" class="form-control form-control-sm" placeholder="型番・仕様・規格">
                </td>
                <td>
                    <input type="number" step="any" min="0.01" name="items[${rowCount}][quantity]" class="form-control form-control-sm text-end" value="1.00" required>
                </td>
                <td>
                    <input type="text" name="items[${rowCount}][unit]" class="form-control form-control-sm text-center" value="個">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 delete-item-btn" title="行削除">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateRowNumbers();
        });

        tbody.addEventListener('click', function (e) {
            const delBtn = e.target.closest('.delete-item-btn');
            if (delBtn) {
                const row = delBtn.closest('.item-row');
                if (tbody.querySelectorAll('.item-row').length > 1) {
                    row.remove();
                    updateRowNumbers();
                }
            }
        });

        function formatTwoDecimals(el) {
            if (el.value !== '' && !isNaN(el.value)) {
                el.value = parseFloat(el.value).toFixed(2);
            }
        }

        tbody.addEventListener('change', function (e) {
            if (e.target.type === 'number') {
                formatTwoDecimals(e.target);
            }
        });

        tbody.addEventListener('blur', function (e) {
            if (e.target.type === 'number') {
                formatTwoDecimals(e.target);
            }
        }, true);

        // --- 3. ファイルドロップゾーン・ピル操作 ---
        const dropzone = document.getElementById('dropzoneArea');
        const fileInput = document.getElementById('fileInput');
        const fileListContainer = document.getElementById('fileListContainer');
        const dt = new DataTransfer();

        if (dropzone && fileInput && fileListContainer) {
            dropzone.addEventListener('click', () => fileInput.click());

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragover');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    addFiles(e.dataTransfer.files);
                }
            });

            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    addFiles(this.files);
                }
            });

            function addFiles(files) {
                for (let i = 0; i < files.length; i++) {
                    dt.items.add(files[i]);
                }
                fileInput.files = dt.files;
                renderFileList();
            }

            function renderFileList() {
                fileListContainer.innerHTML = '';
                for (let i = 0; i < dt.files.length; i++) {
                    const file = dt.files[i];
                    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    let iconClass = 'bi-file-earmark-arrow-up-fill text-primary';
                    if (file.type.includes('pdf')) {
                        iconClass = 'bi-file-earmark-pdf-fill text-danger';
                    } else if (file.type.includes('image')) {
                        iconClass = 'bi-file-earmark-image-fill text-success';
                    } else if (file.name.endsWith('.zip') || file.name.endsWith('.rar')) {
                        iconClass = 'bi-file-earmark-zip-fill text-warning';
                    }

                    const pill = document.createElement('div');
                    pill.className = 'file-item-pill';
                    pill.innerHTML = `
                        <i class="bi ${iconClass}"></i>
                        <span class="fw-semibold">${file.name}</span>
                        <span class="text-muted small">(${sizeMB} MB)</span>
                        <button type="button" class="btn-close btn-close-sm ms-1" aria-label="削除"></button>
                    `;
                    const fileIndex = i;
                    pill.querySelector('.btn-close').addEventListener('click', (e) => {
                        e.stopPropagation();
                        removeFile(fileIndex);
                    });
                    fileListContainer.appendChild(pill);
                }
            }

            function removeFile(index) {
                dt.items.remove(index);
                fileInput.files = dt.files;
                renderFileList();
            }
        }
    });
</script>
@endpush
