@extends('layouts.tenant')

@section('title', 'MESHu - ' . $title)

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.link.index') }}" class="text-decoration-none">つながり管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">企業検索</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">企業検索（新しいつながり）</h1>
    </div>
    <div>
        <a href="{{ route('tenant.link.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>
            <span>つながり一覧へ戻る</span>
        </a>
    </div>
</div>

<!-- ドメインルール注意喚起 -->
<div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-3 mb-4">
    <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0 mt-1"></i>
    <div class="small">
        <strong>【連携申請のルール】</strong><br>
        MESHuでは連携申請は常に<strong>購入側（買手）から販売側（売手）企業に対してのみ</strong>行います。<br>
        検索した相手企業に対して連携申請を送信すると、自社が購入側、相手企業が販売側として「回答待ち（pending）」となります。
    </div>
</div>

<!-- 検索条件指定フォーム -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-search me-2 text-primary"></i>検索条件の指定</h5>
    </div>
    <div class="card-body p-4">
        <form method="GET" action="{{ route('tenant.link.search') }}">
            <div class="row g-3">
                <!-- 企業名・キーワード -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">企業名 / 略称 / カナ</label>
                    <input type="text" name="keyword" class="form-control" placeholder="例: ロジスティクス、サプライ" value="{{ $keyword }}">
                </div>

                <!-- 法人番号 -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">法人番号 (13桁)</label>
                    <input type="text" name="corporate_number" class="form-control font-monospace" placeholder="例: 1234567890123" value="{{ $corporateNumber }}">
                </div>

                <!-- 都道府県 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label fw-semibold small">都道府県</label>
                    <select name="prefecture_code" class="form-select">
                        <option value="">すべての都道府県</option>
                        @php
                        $prefs = [
                            1=>'北海道',2=>'青森県',3=>'岩手県',4=>'宮城県',5=>'秋田県',6=>'山形県',7=>'福島県',
                            8=>'茨城県',9=>'栃木県',10=>'群馬県',11=>'埼玉県',12=>'千葉県',13=>'東京都',14=>'神奈川県',
                            15=>'新潟県',16=>'富山県',17=>'石川県',18=>'福井県',19=>'山梨県',20=>'長野県',21=>'岐阜県',
                            22=>'静岡県',23=>'愛知県',24=>'三重県',25=>'滋賀県',26=>'京都府',27=>'大阪府',28=>'兵庫県',
                            29=>'奈良県',30=>'和歌山県',31=>'鳥取県',32=>'島根県',33=>'岡山県',34=>'広島県',35=>'山口県',
                            36=>'徳島県',37=>'香川県',38=>'愛媛県',39=>'高知県',40=>'福岡県',41=>'佐賀県',42=>'長崎県',
                            43=>'熊本県',44=>'大分県',45=>'宮崎県',46=>'鹿児島県',47=>'沖縄県'
                        ];
                        @endphp
                        @foreach ($prefs as $code => $pName)
                            <option value="{{ $code }}" {{ (string)$prefectureCode === (string)$code ? 'selected' : '' }}>{{ $pName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 市区町村 -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label fw-semibold small">市区町村</label>
                    <input type="text" name="city" class="form-control" placeholder="例: 千代田区、港区" value="{{ $city }}">
                </div>

                <!-- 認証ステータス -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold small">認証ステータス</label>
                    <select name="is_verified" class="form-select">
                        <option value="">すべての企業</option>
                        <option value="1" {{ (string)$isVerified === '1' || $isVerified === 'verified' ? 'selected' : '' }}>認証済み企業のみ</option>
                        <option value="0" {{ (string)$isVerified === '0' ? 'selected' : '' }}>未認証企業のみ</option>
                    </select>
                </div>
            </div>

            <hr class="my-4 text-muted">

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('tenant.link.search') }}" class="btn btn-light px-4">クリア</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-search me-1"></i>検索実行</button>
            </div>
        </form>
    </div>
</div>

<!-- 検索結果テーブル -->
@if ($isSearched)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold fs-6">検索結果一覧 ( {{ count($searchResults) }} 件ヒット )</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 60px;">No.</th>
                        <th scope="col">企業名</th>
                        <th scope="col">法人番号</th>
                        <th scope="col">所在地</th>
                        <th scope="col">MESHu認証ステータス</th>
                        <th scope="col" class="text-end" style="width: 180px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @if (empty($searchResults))
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">該当する企業が見つかりませんでした。</td>
                        </tr>
                    @else
                        @foreach ($searchResults as $idx => $comp)
                            <tr>
                                <td class="font-monospace text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $comp['name'] }}</div>
                                </td>
                                <td class="font-monospace small">{{ $comp['corporate_number'] }}</td>
                                <td class="small">{{ $comp['prefectural_address'] ?? $comp['address'] }}</td>
                                <td>
                                    @if (!empty($comp['is_authenticated']))
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-patch-check-fill text-primary me-1"></i>MESHu認証企業
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-dash-circle me-1"></i>未認証
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.link.show', ['target_company_id' => $comp['id']]) }}" class="btn btn-sm btn-primary px-3 fw-semibold">
                                        詳細・申請へ
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- ページネーションフッター -->
        @php
            $totalPages = max(1, (int)ceil(($totalCount ?? count($searchResults)) / ($perPage ?? 10)));
            $startItem  = ($totalCount ?? count($searchResults)) > 0 ? ((($page ?? 1) - 1) * ($perPage ?? 10) + 1) : 0;
            $endItem    = min(($page ?? 1) * ($perPage ?? 10), ($totalCount ?? count($searchResults)));
            $queryParams = request()->query();
        @endphp
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                {{ $startItem }} - {{ $endItem }} 件 / 全 {{ $totalCount ?? count($searchResults) }} 件
            </div>
            @if ($totalPages > 1)
                <nav aria-label="Search navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- 前へ -->
                        <li class="page-item {{ ($page ?? 1) <= 1 ? 'disabled' : '' }}">
                            @php $queryParams['page'] = ($page ?? 1) - 1; @endphp
                            <a class="page-link" href="{{ route('tenant.link.search', $queryParams) }}">&laquo; 前へ</a>
                        </li>

                        <!-- ページ番号 -->
                        @for ($p = 1; $p <= $totalPages; $p++)
                            @php $queryParams['page'] = $p; @endphp
                            <li class="page-item {{ $p === ($page ?? 1) ? 'active' : '' }}">
                                <a class="page-link" href="{{ route('tenant.link.search', $queryParams) }}">{{ $p }}</a>
                            </li>
                        @endfor

                        <!-- 次へ -->
                        <li class="page-item {{ ($page ?? 1) >= $totalPages ? 'disabled' : '' }}">
                            @php $queryParams['page'] = ($page ?? 1) + 1; @endphp
                            <a class="page-link" href="{{ route('tenant.link.search', $queryParams) }}">次へ &raquo;</a>
                        </li>
                    </ul>
                </nav>
            @endif
        </div>
    </div>
@endif

<!-- 未登録企業の招待案内 -->
<div class="card border-primary border-opacity-25 bg-primary-subtle bg-opacity-10 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary text-white p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="bi bi-envelope-paper-heart-fill fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1 text-dark">お探しの企業が見つかりませんか？</h5>
                <p class="text-muted small mb-0">まだMESHuに登録していない取引先企業へ招待メールを送信し、自動でつながりを構築できます。</p>
            </div>
        </div>
        <div>
            <a href="{{ route('tenant.link.invite') }}" class="btn btn-outline-primary fw-semibold text-nowrap px-4 py-2">
                <i class="bi bi-send me-1"></i>企業を招待する
            </a>
        </div>
    </div>
</div>
@endsection
