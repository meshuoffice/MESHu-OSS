<?php

use App\Http\Middleware\AdminIpRestriction;
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
        // カスタムミドルウェアのエイリアス登録
        $middleware->alias([
            'tenant'   => CompanyTenant::class,
            'admin.ip' => AdminIpRestriction::class,
        ]);

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
