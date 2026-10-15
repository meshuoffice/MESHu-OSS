<?php

$providers = [
    App\Providers\AppServiceProvider::class,
];

// 事務局モジュールが存在する場合のみ登録（OSS公開版など事務局モジュール除外環境との互換性を担保）
if (class_exists(\Modules\Admin\AdminServiceProvider::class)) {
    $providers[] = \Modules\Admin\AdminServiceProvider::class;
}

return $providers;
