<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * 事務局トップダッシュボード画面表示
     */
    public function index(): View
    {
        $currentOwner = Auth::guard('admin')->user();

        // ダッシュボード統計情報の集計
        $totalCompanies   = Company::count();
        $activeCompanies  = Company::where('status', 'active')->count();
        $pendingCompanies = Company::where('status', 'suspended')->count();
        $totalUsers       = User::count();

        return view('admin::dashboard', [
            'activeMenu'       => 'dashboard',
            'title'            => 'ダッシュボード | MESHu 事務局管理',
            'currentOwner'     => $currentOwner,
            'totalCompanies'   => $totalCompanies,
            'activeCompanies'  => $activeCompanies,
            'pendingCompanies' => $pendingCompanies,
            'totalUsers'       => $totalUsers,
        ]);
    }
}
