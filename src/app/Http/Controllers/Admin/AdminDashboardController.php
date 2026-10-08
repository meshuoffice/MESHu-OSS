<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\Notice;
use App\Models\Owner;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * 事務局ダッシュボード表示
     */
    public function index(): View
    {
        $totalCompanies = Company::where('status', '!=', 'deleted')->count();
        $totalBadges    = CompanyBadge::where('status', 'active')->count();
        $totalOwners    = Owner::where('status', 'active')->count();
        $totalNotices   = Notice::count();

        return view('admin.dashboard', [
            'activeMenu'     => 'dashboard',
            'title'          => 'ダッシュボード | MESHu 事務局管理',
            'totalCompanies' => $totalCompanies,
            'totalBadges'    => $totalBadges,
            'totalOwners'    => $totalOwners,
            'totalNotices'   => $totalNotices,
        ]);
    }
}
