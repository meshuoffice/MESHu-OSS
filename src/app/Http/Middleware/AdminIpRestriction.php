<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class AdminIpRestriction
{
    /**
     * 事務局管理画面へのアクセスIP制限（IP Whitelist / CIDR対応）
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIpsConfig = env('ADMIN_ALLOWED_IPS', '');

        // 未設定または空文字の場合は制限をスキップ（ローカル開発等の互換性を維持）
        if (trim($allowedIpsConfig) === '') {
            return $next($request);
        }

        $allowedIps = array_filter(array_map('trim', explode(',', $allowedIpsConfig)));
        $clientIp = $request->ip();

        // 許可IPリスト（CIDR範囲指定含む）と照合
        if (!IpUtils::checkIp($clientIp, $allowedIps)) {
            // 事務局画面の存在自体を隠蔽するため 404 Not Found を返却
            abort(404);
        }

        return $next($request);
    }
}
