@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '明細・金額編集'))

@push('styles')
<style>
    .table-items th {
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .amount-summary-table td {
        padding: 0.4rem 0.75rem;
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
                <li class="breadcrumb-item"><a href="{{ url('/sell') }}" class="text-decoration-none">販売 (売手)</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/sell/detail/' . $tx['id']) }}" class="text-decoration-none">取引詳細 #{{ $tx['id'] }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">明細・金額編集</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">取引明細・金額の編集</h1>
    </div>
</div>

<!-- ガイドアラート -->
<div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-shield-lock-fill fs-4 text-primary"></i>
    <div>
        <strong>【金額の開示について】</strong><br>
        ステータスが「見積」の間に入力・下書き保存した金額は、<strong>買手側には完全非表示</strong>となります。<br>
        「見積回答へ進む」から回答を正式送信することで初めて買手へ開示されます。
    </div>
</div>

<form action="{{ url('/sell/edit/' . $tx['id']) }}" method="post" enctype="multipart/form-data" id="sellEditForm" novalidate>
    @csrf

    <!-- 1. 取引基本情報カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle-fill me-2 text-primary"></i>取引基本情報・納期</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small">購入元企業 (買手)</label>
                    <div class="form-control-plaintext fw-bold fs-6">
                        {{ $tx['partner_name'] }}
                        @if (!empty($tx['partner_badge_status']) && $tx['partner_badge_status'] === 'approved')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">認証済</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small">買手希望納期</label>
                    <div class="form-control-plaintext font-monospace">
                        {{ !empty($tx['desired_delivery_date']) ? date('Y年m月d日', strtotime($tx['desired_delivery_date'])) : '指定なし' }}
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="titleInput" class="form-label fw-semibold text-dark">件名 / 案件名 <span class="badge bg-danger ms-1">必須</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="titleInput" name="title" value="{{ old('title', $tx['title']) }}" required maxlength="255">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="deliveryDateInput" class="form-label fw-semibold text-dark">確定納期（提示納期）</label>
                    <input type="date" class="form-control" id="deliveryDateInput" name="delivery_date" value="{{ old('delivery_date', $tx['delivery_date'] ?? ($tx['desired_delivery_date'] ?? '')) }}">
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 取引明細カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-primary"></i>取引明細・単価積算</h5>
            <div class="small text-muted">
                端数処理（金額/税）: <strong>{{ \App\Models\Transaction::getRoundingLabel($tx['amount_rounding'] ?? ($sellerCompany->amount_rounding ?? null)) }} / {{ \App\Models\Transaction::getRoundingLabel($tx['tax_rounding'] ?? ($sellerCompany->tax_rounding ?? null)) }}</strong>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-items align-middle mb-3" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">No.</th>
                            <th>品名・型番・仕様 <span class="badge bg-danger ms-1">必須</span></th>
                            <th style="width: 120px;">数量 <span class="badge bg-danger ms-1">必須</span></th>
                            <th style="width: 80px;">単位</th>
                            <th style="width: 150px;">単価 (税抜)</th>
                            <th style="width: 150px;" class="text-end">金額 (税抜)</th>
                            <th style="width: 60px;" class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        @if (empty($items))
                            <tr class="item-row">
                                <td class="text-center font-monospace row-number text-muted">1</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm item-name" name="items[0][item_name]" placeholder="品名・型番・詳細仕様を入力" required>
                                </td>
                                <td>
                                    <input type="number" step="any" min="0.01" class="form-control form-control-sm text-end font-monospace item-qty" name="items[0][quantity]" value="1.00" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-center item-unit" name="items[0][unit]" value="個">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">¥</span>
                                        <input type="number" step="any" min="0" class="form-control text-end font-monospace item-price" name="items[0][unit_price]" value="0.00">
                                    </div>
                                </td>
                                <td class="text-end font-monospace fw-bold item-amount">¥0</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-delete-row" title="行を削除" disabled>
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        @else
                            @foreach ($items as $idx => $item)
                                <tr class="item-row">
                                    <td class="text-center font-monospace row-number text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm item-name" name="items[{{ $idx }}][item_name]" value="{{ $item['item_name'] }}" placeholder="品名・型番・詳細仕様を入力" required>
                                        <input type="text" class="form-control form-control-sm mt-1 item-spec" name="items[{{ $idx }}][specification]" value="{{ $item['specification'] ?? '' }}" placeholder="仕様・規格（任意）">
                                    </td>
                                    <td>
                                        <input type="number" step="any" min="0.01" class="form-control form-control-sm text-end font-monospace item-qty" name="items[{{ $idx }}][quantity]" value="{{ number_format((float)($item['quantity'] ?? 1), 2, '.', '') }}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm text-center item-unit" name="items[{{ $idx }}][unit]" value="{{ $item['unit'] ?? '個' }}">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">¥</span>
                                            <input type="number" step="any" min="0" class="form-control text-end font-monospace item-price" name="items[{{ $idx }}][unit_price]" value="{{ number_format((float)($item['unit_price'] ?? 0), 2, '.', '') }}">
                                        </div>
                                    </td>
                                    <td class="text-end font-monospace fw-bold item-amount">
                                        ¥{{ number_format((float)($item['amount'] ?? 0)) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-delete-row" title="行を削除">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- 行追加ボタン -->
            <div class="d-flex justify-content-start mb-4">
                <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" id="btnAddRow">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>明細行を追加</span>
                </button>
            </div>

            <!-- 諸費用・税率・計算設定 -->
            <div class="row g-4 pt-3 border-top">
                <div class="col-md-6">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders me-2 text-primary"></i>諸費用・税計算設定</h6>

                    <div class="row g-2 align-items-center mb-3">
                        <label for="shippingFee" class="col-sm-4 col-form-label col-form-label-sm fw-semibold">送料 (+)</label>
                        <div class="col-sm-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control text-end font-monospace" id="shippingFee" name="shipping_fee" value="{{ $tx['shipping_fee'] ?? 0 }}">
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-3">
                        <label for="discountAmount" class="col-sm-4 col-form-label col-form-label-sm fw-semibold">値引き (-)</label>
                        <div class="col-sm-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control text-end font-monospace" id="discountAmount" name="discount_amount" value="{{ $tx['discount_amount'] ?? 0 }}">
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-3">
                        <label for="taxRate" class="col-sm-4 col-form-label col-form-label-sm fw-semibold">適用消費税率</label>
                        <div class="col-sm-8">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control text-end font-monospace" id="taxRate" name="tax_rate" value="{{ $tx['tax_rate'] ?? ($sellerCompany->tax_rate ?? 10) }}" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="isTaxExempt" name="is_tax_exempt" value="1" {{ !empty($tx['is_tax_exempt']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small text-dark" for="isTaxExempt">
                            消費税を含まない（非課税・税計算迂回）
                        </label>
                    </div>

                    <div class="mt-4">
                        <label for="sellerRemarksInput" class="form-label fw-semibold text-dark">売手備考 / 連絡事項</label>
                        <textarea name="seller_remarks" id="sellerRemarksInput" class="form-control" rows="3" placeholder="買手への連絡事項や見積有効期限などを入力してください...">{{ $tx['seller_remarks'] ?? '' }}</textarea>
                    </div>
                </div>

                <!-- 右側：リアルタイム合計プレビュー -->
                <div class="col-md-6 d-flex justify-content-end align-items-end">
                    <div class="p-3 bg-light rounded border w-100" style="max-width: 360px;">
                        <table class="amount-summary-table w-100">
                            <tr>
                                <td class="text-muted small">明細合計 (税抜)</td>
                                <td class="text-end font-monospace fw-semibold" id="sumItemsAmount">¥0</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">送料 (+)</td>
                                <td class="text-end font-monospace" id="dispShippingFee">¥0</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">値引き (-)</td>
                                <td class="text-end font-monospace" id="dispDiscount">¥0</td>
                            </tr>
                            <tr class="border-top">
                                <td class="fw-bold small">税抜小計</td>
                                <td class="text-end font-monospace fw-bold" id="dispSubtotal">¥0</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">消費税額 (<span id="dispTaxRate">10</span>%)</td>
                                <td class="text-end font-monospace" id="dispTaxAmount">¥0</td>
                            </tr>
                            <tr class="border-top border-2 border-dark">
                                <td class="fw-bold fs-6 text-dark">合計金額 (税込)</td>
                                <td class="text-end font-monospace fw-bold fs-4 text-primary" id="dispTotalAmount">¥0</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. 添付ファイル カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-paperclip me-2 text-primary"></i>添付ファイル</h5>
        </div>
        <div class="card-body p-4">
            @if (!empty($attachments))
                <div class="mb-4">
                    <div class="fw-semibold small text-dark mb-2">登録済み添付ファイル ({{ count($attachments) }} 件)</div>
                    <div class="list-group list-group-flush border rounded bg-white">
                        @foreach ($attachments as $att)
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-arrow-down-fill text-primary fs-5"></i>
                                    <div>
                                        <div class="fw-semibold text-dark small">{{ $att['file_name'] }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            {{ round($att['file_size'] / 1024, 1) }} KB
                                            @if (!empty($att['created_at']))
                                                ・ {{ date('Y/m/d H:i', strtotime($att['created_at'])) }}
                                            @endif
                                            @if (!empty($att['company_name']))
                                                ({{ $att['company_name'] }}{{ !empty($att['user_name']) ? ': ' . $att['user_name'] : '' }})
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ url('/sell/attachment/' . $att['id']) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <label class="d-inline-flex align-items-center gap-1 m-0 bg-danger-subtle px-2 py-1 rounded border border-danger-subtle text-danger small" style="cursor: pointer;" for="delAtt{{ $att['id'] }}">
                                        <input class="form-check-input m-0 border-danger" style="cursor: pointer;" type="checkbox" name="delete_attachments[]" value="{{ $att['id'] }}" id="delAtt{{ $att['id'] }}">
                                        <span>削除</span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="form-label fw-semibold small text-dark mb-2">新規ファイル追加</label>
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
    </div>

    <!-- 送信ボタンエリア -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="{{ url('/sell/detail/' . $tx['id']) }}" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-left me-1"></i>詳細へ戻る
        </a>
        <div class="d-flex gap-2">
            <button type="submit" name="action" value="save" class="btn btn-outline-primary px-4 fw-bold">
                <i class="bi bi-floppy me-1"></i>下書き保存する
            </button>
            @if ($tx['status'] === 'estimate_request')
                <button type="submit" name="action" value="to_reply" class="btn btn-primary px-5 fw-bold shadow-sm">
                    見積回答の確認へ進む <i class="bi bi-arrow-right ms-1"></i>
                </button>
            @elseif ($tx['status'] === 'order_placed')
                <button type="submit" name="action" value="to_accept" class="btn btn-success px-5 fw-bold shadow-sm">
                    受注承諾の確認へ進む <i class="bi bi-arrow-right ms-1"></i>
                </button>
            @endif
        </div>
    </div>

</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('itemsTableBody');
        const btnAddRow = document.getElementById('btnAddRow');
        const shippingFeeInput = document.getElementById('shippingFee');
        const discountAmountInput = document.getElementById('discountAmount');
        const taxRateInput = document.getElementById('taxRate');
        const isTaxExemptCheck = document.getElementById('isTaxExempt');

        const sumItemsAmountEl = document.getElementById('sumItemsAmount');
        const dispShippingFeeEl = document.getElementById('dispShippingFee');
        const dispDiscountEl = document.getElementById('dispDiscount');
        const dispSubtotalEl = document.getElementById('dispSubtotal');
        const dispTaxRateEl = document.getElementById('dispTaxRate');
        const dispTaxAmountEl = document.getElementById('dispTaxAmount');
        const dispTotalAmountEl = document.getElementById('dispTotalAmount');

        function calculateTotals() {
            let itemsTotal = 0;
            const rows = tbody.querySelectorAll('.item-row');
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                const amount = Math.round(qty * price);
                row.querySelector('.item-amount').textContent = '¥' + amount.toLocaleString();
                itemsTotal += amount;
            });

            const shipping = parseFloat(shippingFeeInput.value) || 0;
            const discount = parseFloat(discountAmountInput.value) || 0;
            const subtotal = Math.max(0, itemsTotal + shipping - discount);

            const isExempt = isTaxExemptCheck.checked;
            const rate = isExempt ? 0 : (parseFloat(taxRateInput.value) || 0);
            const tax = isExempt ? 0 : Math.round(subtotal * (rate / 100));
            const total = subtotal + tax;

            sumItemsAmountEl.textContent = '¥' + itemsTotal.toLocaleString();
            dispShippingFeeEl.textContent = '¥' + shipping.toLocaleString();
            dispDiscountEl.textContent = '¥' + discount.toLocaleString();
            dispSubtotalEl.textContent = '¥' + subtotal.toLocaleString();
            dispTaxRateEl.textContent = rate;
            dispTaxAmountEl.textContent = '¥' + tax.toLocaleString();
            dispTotalAmountEl.textContent = '¥' + total.toLocaleString();
        }

        function updateRowNumbers() {
            const rows = tbody.querySelectorAll('.item-row');
            rows.forEach((row, idx) => {
                row.querySelector('.row-number').textContent = idx + 1;
                row.querySelectorAll('input').forEach(input => {
                    const name = input.getAttribute('name');
                    if (name) {
                        input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${idx}]`));
                    }
                });
                const delBtn = row.querySelector('.btn-delete-row');
                if (delBtn) {
                    delBtn.disabled = rows.length <= 1;
                }
            });
            calculateTotals();
        }

        btnAddRow.addEventListener('click', function () {
            const rowCount = tbody.querySelectorAll('.item-row').length;
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td class="text-center font-monospace row-number text-muted">${rowCount + 1}</td>
                <td>
                    <input type="text" class="form-control form-control-sm item-name" name="items[${rowCount}][item_name]" placeholder="品名・型番・詳細仕様を入力" required>
                    <input type="text" class="form-control form-control-sm mt-1 item-spec" name="items[${rowCount}][specification]" placeholder="仕様・規格（任意）">
                </td>
                <td>
                    <input type="number" step="any" min="0.01" class="form-control form-control-sm text-end font-monospace item-qty" name="items[${rowCount}][quantity]" value="1.00" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm text-center item-unit" name="items[${rowCount}][unit]" value="個">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">¥</span>
                        <input type="number" step="any" min="0" class="form-control text-end font-monospace item-price" name="items[${rowCount}][unit_price]" value="0.00">
                    </div>
                </td>
                <td class="text-end font-monospace fw-bold item-amount">¥0</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-delete-row" title="行を削除">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateRowNumbers();
        });

        tbody.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-delete-row');
            if (btn && !btn.disabled) {
                const tr = btn.closest('.item-row');
                if (tr) {
                    tr.remove();
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
            if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
                formatTwoDecimals(e.target);
                calculateTotals();
            }
        });

        tbody.addEventListener('blur', function (e) {
            if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
                formatTwoDecimals(e.target);
            }
        }, true);

        tbody.addEventListener('input', calculateTotals);
        shippingFeeInput.addEventListener('input', calculateTotals);
        discountAmountInput.addEventListener('input', calculateTotals);
        taxRateInput.addEventListener('input', calculateTotals);
        isTaxExemptCheck.addEventListener('change', calculateTotals);

        // --- ファイルドロップゾーン・ピル操作 ---
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

        calculateTotals();
    });
</script>
@endpush
