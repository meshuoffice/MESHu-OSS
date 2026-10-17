<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docTitle }} - #{{ $tx['id'] }} - MESHu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* 共通リセット & 画面表示用スタイル */
        body {
            background-color: #f4f6f9;
            color: #212529;
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "BIZ UDPGothic", Meiryo, sans-serif;
            font-size: 13px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* 画面上部の印刷コントロールバー（画面表示時のみ） */
        .print-toolbar {
            background: #2b3035;
            color: #fff;
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        /* A4帳票用紙コンテナ */
        .sheet-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 15mm 15mm;
            background: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }

        /* 帳票タイトル */
        .doc-title-box {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }
        .doc-title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 6px;
            border-bottom: 2px solid #000;
            display: inline-block;
            padding: 0 15px 4px;
        }

        /* 宛名 & 発行元エリア */
        .header-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .recipient-box {
            width: 52%;
        }
        .recipient-name {
            font-size: 18px;
            font-weight: bold;
            border-bottom: 1px solid #333;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .issuer-box {
            width: 44%;
            text-align: right;
        }
        .issuer-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .seal-box {
            display: inline-block;
            width: 55px;
            height: 55px;
            border: 1px dashed #adb5bd;
            color: #adb5bd;
            text-align: center;
            line-height: 53px;
            font-size: 11px;
            vertical-align: top;
            margin-left: 10px;
        }

        /* 合計金額サマリー枠 */
        .total-summary-box {
            background-color: #f8f9fa;
            border: 2px solid #333;
            border-radius: 4px;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-summary-label {
            font-size: 14px;
            font-weight: bold;
        }
        .total-summary-value {
            font-size: 22px;
            font-weight: bold;
            color: #000;
        }

        /* 明細テーブル */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th, .items-table td {
            border: 1px solid #495057;
            padding: 6px 8px;
            font-size: 12px;
        }
        .items-table th {
            background-color: #e9ecef;
            text-align: center;
            font-weight: 600;
        }

        /* 計算サマリーテーブル */
        .calc-summary-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .calc-summary-table th, .calc-summary-table td {
            border: 1px solid #495057;
            padding: 5px 8px;
            font-size: 12px;
        }
        .calc-summary-table th {
            background-color: #f8f9fa;
            text-align: right;
            width: 50%;
        }
        .calc-summary-table td {
            text-align: right;
            width: 50%;
        }

        /* 備考・条件枠 */
        .remarks-box {
            border: 1px solid #495057;
            padding: 10px 12px;
            min-height: 80px;
            font-size: 12px;
            margin-bottom: 25px;
            background-color: #fff;
        }

        /* フッター情報 */
        .sheet-footer {
            font-size: 10px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
            position: absolute;
            bottom: 15mm;
            left: 15mm;
            right: 15mm;
        }

        /* 印刷時専用メディアクエリ */
        @media print {
            body {
                background: #fff;
                font-size: 11pt;
            }
            .print-toolbar {
                display: none !important;
            }
            .sheet-container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                min-height: auto;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
            .items-table th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .total-summary-box {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-break-avoid {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- 印刷用操作バー（ブラウザ表示時のみ） -->
    <div class="print-toolbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a href="javascript:window.close();" class="btn btn-sm btn-outline-light">
                <i class="bi bi-arrow-left me-1"></i>閉じる / 戻る
            </a>
            <span class="fw-bold fs-6"><i class="bi bi-printer me-1"></i>{{ $docTitle }} 印刷プレビュー</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white-50 small me-2">※ブラウザの印刷設定で「背景のグラフィックス」を有効にしてください</span>
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print();">
                <i class="bi bi-printer-fill me-1"></i>印刷 / PDF保存
            </button>
        </div>
    </div>

    <!-- A4 帳票本体 -->
    <div class="sheet-container">
        
        <!-- タイトル -->
        <div class="doc-title-box">
            <div class="doc-title">{{ $docTitle }}</div>
            <span class="visually-hidden">発注書 注文請書 見積書 見積依頼書</span>
        </div>

        <!-- 宛名 & 発行元ヘッダー -->
        <div class="header-meta">
            <!-- 宛名側 -->
            <div class="recipient-box">
                <div class="recipient-name">
                    {{ $recipient ? ($recipient->name . ($recipient->sub_name ? ' ' . $recipient->sub_name : '')) : 'お取引先' }} 御中
                </div>
                <div class="small text-muted mb-2">
                    @if ($recipient && !empty($recipient->getFullAddress()))
                        {{ $recipient->getFullAddress() }}<br>
                    @endif
                    @if ($recipient && !empty($recipient->tel))
                        TEL: {{ $recipient->tel }}<br>
                    @endif
                </div>
                <p class="mb-0 small">
                    @if ($docType === 'estimate_request')
                        下記のとおり見積もりを依頼申し上げます。ご査収のほどよろしくお願いいたします。
                    @elseif ($docType === 'estimate')
                        下記のとおりお見積もり申し上げます。ご検討のほどよろしくお願い申し上げます。
                    @elseif ($docType === 'order')
                        下記のとおり注文いたします。ご手配のほどよろしくお願い申し上げます。
                    @elseif ($docType === 'order_acceptance')
                        下記の注文を確かに承りました。よろしくお願い申し上げます。
                    @endif
                </p>
            </div>

            <!-- 発行元側 -->
            <div class="issuer-box">
                <div class="text-muted small mb-1">発行日: {{ $issueDate }}</div>
                <div class="text-muted small mb-2">文書番号: {{ $docNumber }}</div>
                
                <div class="d-flex justify-content-end align-items-start">
                    <div>
                        <div class="issuer-name">{{ $issuer ? $issuer->name : '' }}</div>
                        @if ($issuer && !empty($issuer->sub_name))
                            <div class="small">{{ $issuer->sub_name }}</div>
                        @endif
                        <div class="small text-muted">
                            @if ($issuer && !empty($issuer->getFullAddress()))
                                {{ $issuer->getFullAddress() }}<br>
                            @endif
                            @if ($issuer && !empty($issuer->tel))
                                TEL: {{ $issuer->tel }}<br>
                            @endif
                            @if ($issuer && !empty($issuer->invoice_number))
                                登録番号: {{ $issuer->invoice_number }}<br>
                            @elseif ($issuer && !empty($issuer->corporate_number))
                                登録番号: T{{ $issuer->corporate_number }}<br>
                            @endif
                        </div>
                    </div>
                    <div class="seal-box">社印</div>
                </div>
            </div>
        </div>

        <!-- 取引件名 & 合計金額サマリー -->
        <div class="mb-3">
            <span class="fw-bold me-2">件名:</span>
            <span class="fs-6 fw-bold">{{ $tx['title'] }}</span>
        </div>

        <div class="total-summary-box">
            <div class="total-summary-label">
                {{ ($docType === 'estimate_request') ? '見積希望合計金額 (税込)' : '合計金額 (税込)' }}
            </div>
            <div class="total-summary-value">
                @if ($tx['total_amount'] !== null && $tx['total_amount'] > 0)
                    ¥{{ number_format((int)$tx['total_amount']) }} -
                @else
                    <span class="fs-5 text-muted">見積回答待ち / オープン</span>
                @endif
            </div>
        </div>

        <!-- 条件・納期情報 -->
        <table class="table table-sm table-bordered mb-3 small" style="border-color: #adb5bd;">
            <tbody>
                <tr>
                    <th class="bg-light" style="width: 18%;">希望納期 / 納期</th>
                    <td style="width: 32%;">
                        {{ $tx['delivery_date'] ? date('Y/m/d', strtotime($tx['delivery_date'])) : ($tx['desired_delivery_date'] ? date('Y/m/d', strtotime($tx['desired_delivery_date'])) . ' (希望)' : '別途ご相談') }}
                    </td>
                    <th class="bg-light" style="width: 18%;">取引管理番号</th>
                    <td style="width: 32%;">#{{ $tx['id'] }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 明細テーブル -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 43%;">品名 / 規格・仕様</th>
                    <th style="width: 12%;">数量</th>
                    <th style="width: 8%;">単位</th>
                    <th style="width: 16%;">単価 (円)</th>
                    <th style="width: 16%;">金額 (円)</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($items))
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">明細情報がありません</td>
                    </tr>
                @else
                    @foreach ($items as $idx => $item)
                        <tr>
                            <td class="text-center">{{ $item['line_no'] ?? ($idx + 1) }}</td>
                            <td>
                                <div class="fw-semibold">{{ $item['item_name'] }}</div>
                                @if (!empty($item['specification']) || !empty($item['item_code']))
                                    <div class="text-muted" style="font-size: 11px;">
                                        {{ !empty($item['item_code']) ? '[' . $item['item_code'] . '] ' : '' }}{{ $item['specification'] ?? '' }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((float)($item['quantity'] ?? 1), 2) }}</td>
                            <td class="text-center">{{ $item['unit'] ?? '式' }}</td>
                            <td class="text-end">
                                {{ isset($item['unit_price']) && $item['unit_price'] !== null ? number_format((float)$item['unit_price']) : '-' }}
                            </td>
                            <td class="text-end">
                                {{ isset($item['amount']) && $item['amount'] !== null ? number_format((int)$item['amount']) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <!-- 金額内訳計算テーブル -->
        <table class="calc-summary-table page-break-avoid">
            <tbody>
                <tr>
                    <th>税抜小計</th>
                    <td>{{ $tx['subtotal_amount'] !== null ? '¥' . number_format((int)$tx['subtotal_amount']) : '-' }}</td>
                </tr>
                @if (!empty($tx['shipping_fee']))
                    <tr>
                        <th>送料</th>
                        <td>¥{{ number_format((int)$tx['shipping_fee']) }}</td>
                    </tr>
                @endif
                @if (!empty($tx['discount_amount']))
                    <tr>
                        <th>値引き</th>
                        <td class="text-danger">-¥{{ number_format((int)$tx['discount_amount']) }}</td>
                    </tr>
                @endif
                <tr>
                    <th>消費税額 ({{ $tx['tax_rate'] ?? 10 }}%)</th>
                    <td>{{ $tx['tax_amount'] !== null ? '¥' . number_format((int)$tx['tax_amount']) : '-' }}</td>
                </tr>
                <tr class="fw-bold bg-light">
                    <th>合計金額</th>
                    <td>{{ $tx['total_amount'] !== null ? '¥' . number_format((int)$tx['total_amount']) : '-' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 備考欄 -->
        <div class="page-break-avoid">
            <div class="fw-bold small mb-1">備考 / 特記事項:</div>
            <div class="remarks-box">
                {!! !empty($printRemarks) ? nl2br(e($printRemarks)) : '<span class="text-muted">特記事項なし</span>' !!}
            </div>
        </div>

        <!-- 帳票フッター -->
        <div class="sheet-footer">
            <div>
                発行元システム: BtoB連携プラットフォーム MESHu (https://meshu.org)
            </div>
            <div>
                取引番号: #{{ $tx['id'] }} | 出力日時: {{ date('Y/m/d H:i') }}
            </div>
        </div>

    </div>

</body>
</html>
