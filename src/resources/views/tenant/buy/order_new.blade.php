@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '新規直接注文作成'))

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
                <li class="breadcrumb-item active" aria-current="page">新規注文依頼</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">新規注文依頼の作成 <span class="badge bg-success-subtle text-success fs-6 fw-normal ms-2">直接発注</span></h1>
    </div>
</div>

<!-- 注文作成フォーム -->
<form method="post" action="{{ url('/buy/order/confirm') }}" enctype="multipart/form-data" id="orderForm">
    @csrf

    <div class="row g-4">
        <div class="col-lg-12">
            <!-- 1. 発注先企業（単一選択）カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-building-fill me-2 text-primary"></i>発注先企業 (売手) <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-light border d-flex align-items-center gap-2 mb-3 py-2 px-3 small text-muted">
                        <i class="bi bi-info-circle-fill text-primary fs-6"></i>
                        <div>定期発注・合意済み単価品などの<strong>直接注文（1社指定）</strong>を行います。受注承諾（注文回答）をもって取引成立となります。</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="sellerSelect" class="form-label fw-semibold text-dark">発注先企業を選択 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                            @if (empty($partnerLinks))
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>承認済みの販売連携企業が登録されていません。「つながり管理」から企業を連携してください。
                                </div>
                            @else
                                <select name="company_link_id" id="sellerSelect" class="form-select" required>
                                    <option value="">発注先企業を選択してください</option>
                                    @foreach ($partnerLinks as $p)
                                        <option value="{{ $p['link_id'] }}" {{ (string)old('company_link_id') === (string)$p['link_id'] ? 'selected' : '' }}>
                                            {{ $p['partner_name'] }} {{ !empty($p['partner_city']) ? '[' . $p['partner_city'] . ']' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">承認済みのつながり（MESH）企業から1社を選択します。</div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label for="desiredDeliveryInput" class="form-label fw-semibold text-dark">希望納期 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                            <input type="date" name="desired_delivery_date" id="desiredDeliveryInput" class="form-control" value="{{ old('desired_delivery_date') }}" required>
                            <div class="form-text text-muted">納品を希望する期日を指定します。</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. 取引基本情報カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle-fill me-2 text-primary"></i>基本情報</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="titleInput" class="form-label fw-semibold text-dark">件名 / 発注案件名 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                            <input type="text" name="title" id="titleInput" class="form-control" placeholder="例: 2026年9月度 OAサプライ消耗品 定期発注" value="{{ old('title') }}" required maxlength="255">
                            <div class="form-text text-muted">発注内容が特定できる件名を入力してください。</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. 注文明細カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-cart-check-fill me-2 text-primary"></i>注文明細 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></h5>
                    </div>
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
                                        <input type="text" name="items[0][item_name]" class="form-control form-control-sm" placeholder="例: 高白色コピー用紙 A4 (500枚×5冊/箱)" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][specification]" class="form-control form-control-sm" placeholder="型番・仕様・規格">
                                    </td>
                                    <td>
                                        <input type="number" step="any" min="0.01" name="items[0][quantity]" class="form-control form-control-sm text-end" value="1.00" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][unit]" class="form-control form-control-sm text-center" value="箱">
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

                    <div id="fileListContainer" class="d-flex flex-wrap align-items-center"></div>
                </div>
            </div>

            <!-- 5. 備考・特記事項カード -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-left-text-fill me-2 text-primary"></i>備考・発注特記事項</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="remarksInput" class="form-label fw-semibold text-dark">備考 / メモ <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.75rem;">任意</span></label>
                            <textarea name="buyer_remarks" id="remarksInput" class="form-control" rows="3" placeholder="納品場所、希望配送時間帯、請求書の送付先などがあれば入力してください。">{{ old('buyer_remarks') }}</textarea>
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

        // ファイルドロップゾーン
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
