<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 新規企業登録時の初期ステータス (Company Initial Status)
    |--------------------------------------------------------------------------
    | 本番環境（事務局審査あり）: 'suspended'（デフォルト）
    | OSS版・検証環境（即時稼働）: 'active'
    | ※環境変数 COMPANY_INITIAL_STATUS で制御可能。未指定かつ事務局モジュール不在時は 'active' と判定。
    */
    'company_initial_status' => env('COMPANY_INITIAL_STATUS', class_exists(\Modules\Admin\AdminServiceProvider::class) ? 'suspended' : 'active'),
];
