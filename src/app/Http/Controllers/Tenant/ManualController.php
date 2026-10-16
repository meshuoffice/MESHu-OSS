<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualController extends Controller
{
    /**
     * マニュアルのカテゴリ定義およびコンテンツ
     */
    public static function getCategories(): array
    {
        return [
            'getting-started' => [
                'id' => 'getting-started',
                'title' => 'はじめてのMESHu',
                'subtitle' => 'システムの基本概要、自社専用ログインURL、初期設定',
                'icon' => 'bi-rocket-takeoff',
                'badge' => '導入・基本',
                'description' => 'MESHuを使い始めるにあたって必要な基本設定やログイン方法、ダッシュボードの見方について説明します。',
                'sections' => [
                    [
                        'id' => 'gs-overview',
                        'title' => '1. MESHuの全体像と役割',
                        'content' => 'MESHuは、企業間の受発注・見積交渉・情報共有をシンプルかつセキュアに行うためのBtoB企業間連携プラットフォームです。購入側（買手）と販売側（売手）の双方向取引をスムーズに管理できます。',
                        'tips' => '取引を行うには、まず取引先企業と「つながり（MESH）」を構築する必要があります。',
                    ],
                    [
                        'id' => 'gs-login-url',
                        'title' => '2. 自社専用ログインURL',
                        'content' => 'MESHuでは各社ごとに一意の識別コード（企業コード / URLコード）が発行され、専用のログインURL（例: https://meshu.org/{url_code}）からアクセスします。ブラウザのブックマークに保存してご利用ください。',
                        'tips' => 'ログインURLが不明な場合は、自社の管理者または事務局にお問い合わせください。',
                    ],
                    [
                        'id' => 'gs-profile',
                        'title' => '3. 初期パスワード変更とマイプロフィール',
                        'content' => '初回ログイン後は、画面右上のユーザーメニューまたはサイドバーの「マイプロフィール」からパスワードを変更し、氏名カナや連絡先電話番号が正しく登録されているかご確認ください。',
                        'tips' => '担当者名は取引先とのチャットや見積・注文履歴に表示されます。',
                    ],
                    [
                        'id' => 'gs-dashboard',
                        'title' => '4. ダッシュボードの活用',
                        'content' => 'ログイン直後のダッシュボードには、「対応待ちタスク」「今月の取引サマリー」「最近のつながり企業」「新着お知らせ」が集約されています。日々の業務開始時にまずご確認ください。',
                        'tips' => '管理者と一般従業員で表示される権限と集計範囲（自社全体または自社担当分）が自動制御されます。',
                    ],
                ],
            ],
            'links' => [
                'id' => 'links',
                'title' => '取引先との「つながり」(MESH)',
                'subtitle' => '企業検索、連携申請・承認、未登録企業招待',
                'icon' => 'bi-share',
                'badge' => '連携管理',
                'description' => '取引を行うパートナー企業との「つながり」の検索・申請・承認および未登録企業の招待フローを解説します。',
                'sections' => [
                    [
                        'id' => 'link-search',
                        'title' => '1. パートナー企業の検索と申請',
                        'content' => '「各種設定」またはダッシュボードの「企業とつながる」から企業検索画面を開き、相手先の企業名・住所・キーワードで検索します。対象企業を見つけたら「連携申請」ボタンを押し、申請メッセージを添えて送信します。',
                        'tips' => '認証バッジが付与されている企業は、事務局による本人確認が完了した信頼性の高い企業です。',
                    ],
                    [
                        'id' => 'link-approve',
                        'title' => '2. 連携申請の承認・却下フロー',
                        'content' => '他社から連携申請が届くと、ダッシュボードや通知メールでお知らせされます。「つながり管理」一覧の「申請中」タブから相手先情報を確認し、「承認」または「却下」を選択します。',
                        'tips' => '承認が完了すると、即座に相互の見積依頼・注文・チャットが可能になります。',
                    ],
                    [
                        'id' => 'link-invite',
                        'title' => '3. まだMESHuに未登録の取引先を招待する',
                        'content' => 'MESHu未登録の取引先とも、「各種設定」>「招待先管理」より招待メールを送信できます。相手先企業が招待リンクから登録を完了すると、自動的につながり申請が完了した状態になります。',
                        'tips' => '招待メールの有効期限は送信から7日間です。必要に応じて再送信が可能です。',
                    ],
                ],
            ],
            'transactions' => [
                'id' => 'transactions',
                'title' => '取引管理（購入・販売・帳票）',
                'subtitle' => '見積依頼・回答、直接注文・承諾、再交渉、帳票印刷',
                'icon' => 'bi-cart-check',
                'badge' => '中核機能',
                'description' => '見積依頼から見積回答、注文確定、承諾・辞退、そして注文書・見積書のPDF/帳票印刷までの取引フローを網羅しています。',
                'sections' => [
                    [
                        'id' => 'tx-buy-estimate',
                        'title' => '1. 【購入側】見積依頼の作成と送信',
                        'content' => '「購入（買手）」ポータルから「新規見積依頼」を選択し、つながり企業から依頼先を指定します。品名・数量・希望納期・添付図面・仕様備考を入力して送信します。複数明細の一括登録も可能です。',
                        'tips' => '下書き保存を活用することで、社内確認後に送信することができます。',
                    ],
                    [
                        'id' => 'tx-sell-reply',
                        'title' => '2. 【販売側】見積回答と明細入力',
                        'content' => '見積依頼が届くと「販売（売手）」一覧に「回答待ち」として表示されます。各明細ごとの単価・納期・有効期限を入力し、必要に応じて備考を追記して回答を送信します。',
                        'tips' => '回答時に条件が合わない場合は、辞退理由を添えて辞退することも可能です。',
                    ],
                    [
                        'id' => 'tx-order-place',
                        'title' => '3. 【購入側】注文の確定（見積から注文 / 直接注文）',
                        'content' => '提示された見積回答を確認し、条件に問題がなければ「この内容で注文する」へ進みます。また、既知の取引であれば見積を挟まず直接「新規注文」を行うことも可能です。',
                        'tips' => '注文後は販売側の「承諾」をもって正式な契約・取引成立となります。',
                    ],
                    [
                        'id' => 'tx-order-accept',
                        'title' => '4. 【販売側】注文承諾・辞退とコメントやり取り',
                        'content' => '受領した注文の内容を確認し、「注文を承諾する」を実行します。製造キャパシティや在庫の都合で受けられない場合は「注文辞退」を選択し理由を通知します。取引詳細内の「コメント欄」で納期の微調整や相談も可能です。',
                        'tips' => 'コメント投稿時に関係担当者へ自動メール通知が送信されます。',
                    ],
                    [
                        'id' => 'tx-print',
                        'title' => '5. 見積書・注文書の印刷プレビュー',
                        'content' => '各取引詳細画面の「帳票印刷」ボタンから、ブラウザ印刷に最適化された帳票プレビュー画面を開くことができます。購入側・販売側双方の社印枠・明細・合計金額が網羅されています。',
                        'tips' => '印刷プレビュー画面でブラウザの印刷機能（Ctrl + P / Cmd + P）を実行し、PDFとして保存することも可能です。',
                    ],
                ],
            ],
            'chat-notices' => [
                'id' => 'chat-notices',
                'title' => 'チャットとお知らせ',
                'subtitle' => '取引先とのリアルタイムチャット、ファイル共有、お知らせ',
                'icon' => 'bi-chat-dots',
                'badge' => 'コミュニケーション',
                'description' => '日々の迅速なやり取りを行うチャット機能と、事務局や取引先からの重要なお知らせの確認方法を解説します。',
                'sections' => [
                    [
                        'id' => 'cn-chat-use',
                        'title' => '1. 取引先別チャットルーム',
                        'content' => '「チャット」メニューから、つながり企業ごとのチャットルームを開きます。テキストメッセージのほか、画像やPDFなどの資料ファイルを選択して添付送信できます。',
                        'tips' => 'チャットは自社の担当者および相手先担当者の双方がタイムライン形式で閲覧可能です。',
                    ],
                    [
                        'id' => 'cn-chat-read',
                        'title' => '2. 既読管理とメッセージ削除',
                        'content' => '相手先がメッセージを確認すると自動的に「既読」バッジが表示されます。誤って送信したメッセージは、一定条件下で送信取消（削除）が可能です（削除された旨の吹き出しが表示されます）。',
                        'tips' => '新着メッセージ受信時は、ログイン中のユーザー宛に未読通知メールが届きます。',
                    ],
                    [
                        'id' => 'cn-notices',
                        'title' => '3. お知らせの閲覧と添付ファイル',
                        'content' => '「お知らせ」メニューでは、事務局からのシステムメンテナンス・規約変更連絡や、つながり企業からの全体周知・営業案内を確認できます。重要なお知らせには添付ファイルが付属している場合があります。',
                        'tips' => '管理者権限を持つユーザーは「各種設定」>「お知らせ配信管理」から自社発信のお知らせを作成・配信できます。',
                    ],
                ],
            ],
            'company-users' => [
                'id' => 'company-users',
                'title' => '自社情報と従業員アカウント管理',
                'subtitle' => '企業プロフィールの更新、従業員追加、担当企業割り当て',
                'icon' => 'bi-people',
                'badge' => '管理者向け',
                'description' => '企業の基本情報（所在地、連絡先など）の維持管理と、社員アカウントの権限・担当範囲の設定方法です。',
                'sections' => [
                    [
                        'id' => 'cu-company',
                        'title' => '1. 企業情報のメンテナンス',
                        'content' => '「各種設定」>「企業情報メンテナンス」から、企業名、略称、代表者名、所在地（郵便番号・都道府県・市区町村・住所）、電話番号、FAX番号、ウェブサイトURL等を変更できます。',
                        'tips' => '企業情報を更新すると、自社専用ログイン画面や発行・印刷される見積書・注文書の社名・連絡先表示に自動反映されます。',
                    ],
                    [
                        'id' => 'cu-users',
                        'title' => '2. 従業員アカウントの登録と権限',
                        'content' => '「各種設定」>「従業員管理」から、新しい従業員アカウントを登録できます。役割として「管理者（admin）」または「一般社員（member）」を設定できます。登録時に初期パスワード通知メールが送信されます。',
                        'tips' => '管理者は各種設定や全従業員の管理が可能で、一般社員は日々の取引・チャット・プロフィール変更に限定されます。',
                    ],
                    [
                        'id' => 'cu-assignment',
                        'title' => '3. 担当割当（全担当 vs 個別企業担当）',
                        'content' => '従業員ごとに「全取引先を担当する（全担当）」か「指定した取引先のみ担当する（個別担当）」を選択できます。個別担当に設定した場合、担当企業との取引・チャット・お知らせのみが対象ユーザーの画面に表示されます。',
                        'tips' => '営業担当ごとに担当企業を分けたい場合に個別担当割当が有効です。',
                    ],
                ],
            ],
            'security-contact' => [
                'id' => 'security-contact',
                'title' => 'セキュリティと事務局問合せ',
                'subtitle' => 'パスワード再発行、認証バッジ、困ったときの事務局サポート',
                'icon' => 'bi-shield-check',
                'badge' => 'サポート・安全',
                'description' => '安全な運用のためのパスワード管理や認証バッジの意義、トラブル発生時の事務局問い合わせ窓口の活用方法です。',
                'sections' => [
                    [
                        'id' => 'sc-password-reset',
                        'title' => '1. パスワードを忘れた場合（リマインダー）',
                        'content' => '自社専用ログイン画面の「パスワードをお忘れの方はこちら」リンクから、登録メールアドレスを入力して再設定メールを受信してください。記載されたワンタイムURLから新しいパスワードを登録できます。',
                        'tips' => '管理者は従業員管理画面から該当従業員のパスワード再発行メールを代理送信することも可能です。',
                    ],
                    [
                        'id' => 'sc-badges',
                        'title' => '2. 認証バッジ（公式認証マーク）について',
                        'content' => '企業一覧や取引詳細に表示される緑色の「認証バッジ」は、MESHu運営事務局が登記簿謄本や実在確認等の厳正な審査を完了した企業であることを示します。安心して取引を行うための指標です。',
                        'tips' => '自社の認証バッジ取得をご希望の場合は、事務局へお問い合わせください。',
                    ],
                    [
                        'id' => 'sc-contact',
                        'title' => '3. 事務局への問合せ手順と対応時間',
                        'content' => '画面右上のメニューまたはサイドバーの「事務局へ問合せ」から、専用フォームよりお問い合わせいただけます。操作方法のご相談や不具合の報告、認証審査の依頼などに対応しています。平日9:00〜18:00受付で、原則1〜2営業日以内に回答いたします。',
                        'tips' => '画面キャプチャや関連資料（最大10MB）を添付して送信すると、よりスムーズな対応が可能です。',
                    ],
                ],
            ],
        ];
    }

    /**
     * よくある質問 (FAQ) リスト
     */
    public static function getFaqList(): array
    {
        return [
            [
                'id' => 'faq-link-pending',
                'category' => 'links',
                'category_title' => 'つながり管理',
                'question' => '取引先連携の申請を行いましたが、承認待ちが続いています。どうすればよいですか？',
                'answer' => '申請先の企業様がまだ申請を確認・承認していない可能性があります。先方の担当者様へ直接ご連絡いただき、MESHuダッシュボードの「つながり管理」から承認いただくようご案内ください。なお、申請が誤りであった場合は一度申請をキャンセルして再送することも可能です。',
                'related_manual' => 'links',
                'related_section' => 'link-approve',
            ],
            [
                'id' => 'faq-employee-assignment',
                'category' => 'company-users',
                'category_title' => '従業員・担当管理',
                'question' => '特定の従業員に特定の取引先企業だけを担当させたいのですが、どのように設定しますか？',
                'answer' => '管理者アカウントでログインし、「各種設定」>「従業員管理」から対象社員の詳細を開きます。「担当企業設定」で「個別担当」に切り替え、担当させたい企業にチェックを入れて保存してください。',
                'related_manual' => 'company-users',
                'related_section' => 'cu-assignment',
            ],
            [
                'id' => 'faq-notification-mail',
                'category' => 'getting-started',
                'category_title' => '通知・メール',
                'question' => '取引通知やチャットの通知メールが届かないのですが、確認ポイントはありますか？',
                'answer' => '1. 迷惑メールフォルダやプロモーションフォルダに振り分けられていないかご確認ください。' . "\n" .
                            '2. 「マイプロフィール」に登録されているメールアドレスに誤りがないかご確認ください。' . "\n" .
                            '3. 会社のメールサーバーで「@meshu.org」からのメールが迷惑メールフィルタやセキュリティソフトによってブロックされていないか、社内システム管理者にご確認ください。',
                'related_manual' => 'getting-started',
                'related_section' => 'gs-profile',
            ],
            [
                'id' => 'faq-direct-order',
                'category' => 'transactions',
                'category_title' => '取引管理',
                'question' => '見積依頼を経由せずに、直接発注（直接注文）することはできますか？',
                'answer' => 'はい、可能です。「購入（買手）」メニューの「新規直接注文」から、見積を経由せずに直接品名・金額・納期を指定して発注書を送信できます。相手先企業が「注文承諾」を行うことで取引確定となります。',
                'related_manual' => 'transactions',
                'related_section' => 'tx-order-place',
            ],
            [
                'id' => 'faq-company-info-edit',
                'category' => 'company-users',
                'category_title' => '企業情報設定',
                'question' => '自社の住所や電話番号、代表者名などを変更するにはどうすればよいですか？',
                'answer' => '管理者アカウントでログインし、「各種設定」>「企業情報メンテナンス」から情報を編集して保存してください。更新内容は自社専用ログイン画面や発行・印刷される見積書・注文書にも自動反映されます。',
                'related_manual' => 'company-users',
                'related_section' => 'cu-company',
            ],
            [
                'id' => 'faq-cancel-transaction',
                'category' => 'transactions',
                'category_title' => '取引管理',
                'question' => '送信済みの見積回答や注文内容を取り消したり、条件を変更して再交渉できますか？',
                'answer' => '注文承諾前であれば、取引詳細のコメント欄にて再交渉の旨を連絡し、販売側から再度見積回答を送るか、購入側から再注文することが可能です。承諾後のキャンセルは双方の合意が必要となりますので、コメントまたは直接ご連絡の上ご相談ください。',
                'related_manual' => 'transactions',
                'related_section' => 'tx-order-accept',
            ],
            [
                'id' => 'faq-login-url-loss',
                'category' => 'getting-started',
                'category_title' => 'ログイン・アカウント',
                'question' => '自社専用のログインURLが分からなくなってしまいました。',
                'answer' => '自社の管理者様にご確認いただくか、ご登録時の招待メール・アカウント作成通知メールに記載されているURLをご確認ください。それでも不明な場合は、事務局お問い合わせ窓口まで企業名とお名前を添えてご連絡ください。',
                'related_manual' => 'getting-started',
                'related_section' => 'gs-login-url',
            ],
            [
                'id' => 'faq-badge-get',
                'category' => 'security-contact',
                'category_title' => 'セキュリティ・認証',
                'question' => '自社にも「認証バッジ」を付与してもらいたいのですが、どのような手続きが必要ですか？',
                'answer' => '事務局による実在性および信用度確認の審査が必要となります。「事務局へ問合せ」から「認証バッジ審査の申請」を選択し、履歴事項全部証明書（登記簿謄本）などの必要書類を添付してお申し込みください。',
                'related_manual' => 'security-contact',
                'related_section' => 'sc-badges',
            ],
        ];
    }

    /**
     * マニュアルトップ（カテゴリ一覧＆検索・おすすめFAQ）
     */
    public function index(Request $request)
    {
        $categories = self::getCategories();
        $faqList = self::getFaqList();
        $keyword = trim((string)$request->query('q', ''));

        $searchResults = [];
        if ($keyword !== '') {
            $keywordLower = mb_strtolower($keyword);

            // カテゴリ・セクションからの検索
            foreach ($categories as $cat) {
                foreach ($cat['sections'] as $sec) {
                    if (str_contains(mb_strtolower($sec['title']), $keywordLower) ||
                        str_contains(mb_strtolower($sec['content']), $keywordLower) ||
                        str_contains(mb_strtolower($sec['tips']), $keywordLower)) {
                        $searchResults[] = [
                            'type' => 'manual',
                            'category_id' => $cat['id'],
                            'category_title' => $cat['title'],
                            'section_id' => $sec['id'],
                            'title' => $sec['title'],
                            'excerpt' => mb_substr(strip_tags($sec['content']), 0, 140) . '...',
                            'url' => route('tenant.manual.category', ['category' => $cat['id']]) . '#' . $sec['id'],
                        ];
                    }
                }
            }

            // FAQからの検索
            foreach ($faqList as $faq) {
                if (str_contains(mb_strtolower($faq['question']), $keywordLower) ||
                    str_contains(mb_strtolower($faq['answer']), $keywordLower)) {
                    $searchResults[] = [
                        'type' => 'faq',
                        'category_id' => $faq['category'],
                        'category_title' => $faq['category_title'],
                        'section_id' => $faq['id'],
                        'title' => 'Q. ' . $faq['question'],
                        'excerpt' => mb_substr(strip_tags($faq['answer']), 0, 140) . '...',
                        'url' => route('tenant.manual.faq') . '#' . $faq['id'],
                    ];
                }
            }
        }

        return view('manual.index', [
            'activeMenu' => 'manual',
            'categories' => $categories,
            'faqList' => array_slice($faqList, 0, 5), // おすすめFAQ 5件
            'keyword' => $keyword,
            'searchResults' => $searchResults,
        ]);
    }

    /**
     * カテゴリ別マニュアル詳細
     */
    public function category(string $category)
    {
        $categories = self::getCategories();

        if (!isset($categories[$category])) {
            abort(404, '指定された操作マニュアルカテゴリは見つかりませんでした。');
        }

        $currentCategory = $categories[$category];

        return view('manual.category', [
            'activeMenu' => 'manual',
            'categories' => $categories,
            'currentCategory' => $currentCategory,
        ]);
    }

    /**
     * よくある質問（FAQ）一覧
     */
    public function faq(Request $request)
    {
        $categories = self::getCategories();
        $faqList = self::getFaqList();
        $keyword = trim((string)$request->query('q', ''));
        $targetCategory = trim((string)$request->query('category', ''));

        $filteredFaqs = $faqList;

        if ($targetCategory !== '' && isset($categories[$targetCategory])) {
            $filteredFaqs = array_filter($filteredFaqs, fn($f) => $f['category'] === $targetCategory);
        }

        if ($keyword !== '') {
            $keywordLower = mb_strtolower($keyword);
            $filteredFaqs = array_filter($filteredFaqs, function ($faq) use ($keywordLower) {
                return str_contains(mb_strtolower($faq['question']), $keywordLower) ||
                       str_contains(mb_strtolower($faq['answer']), $keywordLower);
            });
        }

        return view('manual.faq', [
            'activeMenu' => 'manual',
            'categories' => $categories,
            'faqList' => $filteredFaqs,
            'keyword' => $keyword,
            'targetCategory' => $targetCategory,
        ]);
    }
}
