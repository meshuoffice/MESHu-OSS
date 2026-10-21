<?php

use App\Http\Middleware\CompanyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // リバースプロキシ（Nginx / ALB / Cloudflare等）背後での HTTPS 判定を有効化
        $middleware->trustProxies(at: '*');

        $aliases = [
            'tenant' => CompanyTenant::class,
        ];

        // 事務局モジュールが存在する場合のみIP制限ミドルウェアをエイリアス登録
        if (class_exists(\Modules\Admin\Middleware\AdminIpRestriction::class)) {
            $aliases['admin.ip'] = \Modules\Admin\Middleware\AdminIpRestriction::class;
        }

        $middleware->alias($aliases);

        // 認証後のリダイレクト先や未ログイン時のリダイレクト先カスタマイズ
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is(env('ADMIN_URL_PATH', 'admin') . '/*') || $request->is('own*')) {
                return route('admin.login');
            }
            $urlCode = $request->route('url_code');
            if ($urlCode) {
                return route('tenant.login', ['url_code' => $urlCode]);
            }
            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
