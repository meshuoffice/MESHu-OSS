<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * マイプロフィール入力画面
     */
    public function edit(): View|RedirectResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return redirect()->route('dashboard')->with('error', 'ユーザー情報が見つかりません。');
        }

        $company = $user->company;
        $assignedCompanies = [];

        if ((int)($user->is_all_assigned ?? 1) === 0) {
            $assignedLinks = UserAssignedLink::with(['link.buyerCompany', 'link.sellerCompany'])
                ->where('user_id', $user->id)
                ->get();

            foreach ($assignedLinks as $assigned) {
                $link = $assigned->link;
                if ($link) {
                    $partnerComp = ((int)$link->buyer_company_id === (int)$company->id)
                        ? $link->sellerCompany
                        : $link->buyerCompany;

                    if ($partnerComp) {
                        $assignedCompanies[] = [
                            'id'   => $partnerComp->id,
                            'name' => $partnerComp->name,
                        ];
                    }
                }
            }
        }

        return view('profile.edit', [
            'activeMenu'        => 'profile',
            'companyName'       => $company->name ?? '',
            'userName'          => $user->user_name,
            'user'              => $user,
            'assignedCompanies' => $assignedCompanies,
        ]);
    }

    /**
     * マイプロフィール確認画面（バリデーション実行）
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return redirect()->route('dashboard')->with('error', 'ユーザー情報が見つかりません。');
        }

        $rules = [
            'email'          => ['required', 'string', 'email:filter', 'max:255', "unique:users,email,{$user->id}"],
            'user_name'      => ['required', 'string', 'max:100'],
            'user_name_kana' => ['required', 'string', 'regex:/^[ァ-ヶー\s]+$/u'],
            'department'     => ['nullable', 'string', 'max:100'],
            'position'       => ['nullable', 'string', 'max:100'],
        ];

        $messages = [
            'email.required'          => 'ログインID(メールアドレス)は必須項目です。',
            'email.email'             => '有効なメールアドレス形式で入力してください。',
            'email.unique'            => '入力されたメールアドレスは既に使用されています。',
            'user_name.required'      => '名前は必須項目です。',
            'user_name_kana.required' => '名前カナは必須項目です。',
            'user_name_kana.regex'    => '名前カナは全角カタカナで入力してください。',
        ];

        $currentPassword = $request->input('current_password');
        $newPassword     = $request->input('password');
        $passwordConfirm = $request->input('password_confirm');

        if (!empty($currentPassword) || !empty($newPassword) || !empty($passwordConfirm)) {
            $rules['current_password'] = ['required'];
            $rules['password']         = ['required', 'string', 'min:8'];
            $rules['password_confirm'] = ['required', 'same:password'];

            $messages['current_password.required'] = 'パスワードを変更する場合は、現在のパスワードを入力してください。';
            $messages['password.required']         = '新しいパスワードを入力してください。';
            $messages['password.min']              = '新しいパスワードは8文字以上で入力してください。';
            $messages['password_confirm.required'] = '新しいパスワード(確認)を入力してください。';
            $messages['password_confirm.same']     = '新しいパスワード(確認)が一致しません。';
        }

        $request->validate($rules, $messages);

        if (!empty($currentPassword) || !empty($newPassword)) {
            if (!Hash::check($currentPassword, $user->password_hash)) {
                return redirect()->back()->withInput()->withErrors(['current_password' => '現在のパスワードが正しくありません。']);
            }
        }

        $postData = $request->all();

        return view('profile.confirm', [
            'activeMenu'  => 'profile',
            'companyName' => $user->company->name ?? '',
            'userName'    => $user->user_name,
            'postData'    => $postData,
            'user'        => $user,
        ]);
    }

    /**
     * マイプロフィール更新処理（DB更新）
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return redirect()->route('dashboard')->with('error', 'ユーザーが見つかりません。');
        }

        $postData = $request->all();

        $updateData = [
            'email'          => $postData['email'],
            'user_name'      => $postData['user_name'],
            'user_name_kana' => !empty($postData['user_name_kana']) ? $postData['user_name_kana'] : null,
            'department'     => !empty($postData['department']) ? $postData['department'] : null,
            'position'       => !empty($postData['position']) ? $postData['position'] : null,
            'notify_trade'   => !empty($postData['notify_trade']) && $postData['notify_trade'] == '1' ? 1 : 0,
            'notify_comment' => !empty($postData['notify_comment']) && $postData['notify_comment'] == '1' ? 1 : 0,
            'notify_chat'    => !empty($postData['notify_chat']) && $postData['notify_chat'] == '1' ? 1 : 0,
        ];

        if (!empty($postData['password'])) {
            $updateData['password_hash'] = Hash::make($postData['password']);
        }

        $user->update($updateData);

        session([
            'user_name'  => $user->user_name,
            'user_email' => $user->email,
        ]);

        return redirect()->route('tenant.profile.complete');
    }

    /**
     * マイプロフィール完了画面
     */
    public function complete(): View
    {
        $user = Auth::guard('web')->user();

        return view('profile.complete', [
            'activeMenu'  => 'profile',
            'companyName' => $user->company->name ?? '',
            'userName'    => $user->user_name ?? '',
        ]);
    }
}
