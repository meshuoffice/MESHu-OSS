<?php

namespace Modules\Admin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminIpRestriction
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // テスト実行環境時はIP制限をスキップ
        if (app()->environment('testing')) {
            return $next($request);
        }

        $adminIp = env('ADMIN_ALLOWED_IPS') ?: env('ADMIN_IP');

        // IP制限が設定されていない場合は全許可
        if (empty($adminIp)) {
            return $next($request);
        }

        $allowedIps = array_map('trim', explode(',', $adminIp));
        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps, true)) {
            abort(403, '事務局アクセス権限がありません。(IP制限)');
        }

        return $next($request);
    }
}
