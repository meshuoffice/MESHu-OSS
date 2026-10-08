<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * 各種設定ポータル画面
     */
    public function index(): View
    {
        $user = Auth::guard('web')->user();

        // 管理者権限（role=admin）のみアクセス許可
        if (!$user || $user->role !== 'admin') {
            abort(403, '管理者権限が必要です。');
        }

        return view('settings.index', [
            'activeMenu'     => 'settings',
            'currentCompany' => $user->company,
            'user'           => $user,
        ]);
    }
}
