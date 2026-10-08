<?php

namespace Modules\Admin;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // 事務局専用ルーティングを 'web' ミドルウェアグループ（セッション・CSRF適用）配下に読み込み
        $routesPath = __DIR__ . '/Routes/web.php';
        if (file_exists($routesPath)) {
            Route::middleware('web')->group($routesPath);
        }

        // 事務局専用Bladeビュー（admin::名前空間）の登録
        $viewsPath = __DIR__ . '/Views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'admin');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
