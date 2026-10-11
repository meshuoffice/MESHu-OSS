<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * 各種設定ポータル画面
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::guard('web')->user();

        // 管理者権限（role=admin）のみアクセス許可（一般従業員はダッシュボードへリダイレクト）
        if (!$user || $user->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'このページへアクセスする権限がありません。（管理者専用機能）');
        }

        return view('settings.index', [
            'activeMenu'     => 'settings',
            'currentCompany' => $user->company,
            'user'           => $user,
        ]);
    }
}
