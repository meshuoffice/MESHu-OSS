<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class CompanyTenant
{
    /**
     * テナント識別子（url_code）を検証し、企業コンテキストを設定
     */
    public function handle(Request $request, Closure $next): Response
    {
        $urlCode = $request->route('url_code');

        if (!$urlCode) {
            abort(404, 'テナント識別子が指定されていません。');
        }

        // status = 'active' の企業のみ許可
        $company = Company::where('url_code', $urlCode)
            ->where('status', 'active')
            ->first();

        if (!$company) {
            abort(404, '指定された企業ページは存在しないか、利用が停止されています。');
        }

        // リクエスト属性および全ビューに現在企業を共有
        $request->attributes->set('current_company', $company);
        View::share('currentCompany', $company);

        return $next($request);
    }
}
