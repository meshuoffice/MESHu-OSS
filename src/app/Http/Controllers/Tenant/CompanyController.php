<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\Prefectures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * 端数処理区分マップ (round: 四捨五入, floor: 切り捨て, ceil: 切り上げ)
     */
    public const ROUNDING_TYPES = [
        'round' => '四捨五入',
        'floor' => '切り捨て',
        'ceil'  => '切り上げ',
    ];

    /**
     * テナント整合性および管理者権限の検証
     */
    private function authorizeAdmin(string $url_code): Company
    {
        $user = Auth::guard('web')->user();

        // 1. 管理者権限チェック (role === 'admin')
        if (!$user || $user->role !== 'admin') {
            abort(403, '管理者権限が必要です。');
        }

        $company = $user->company;

        // 2. 自社テナント隔離チェック (他社 url_code への越境アクセスを遮断)
        if (!$company || $company->url_code !== $url_code) {
            abort(403, '自社以外の企業情報にはアクセスできません。');
        }

        return $company;
    }

    /**
     * 企業情報編集画面表示
     */
    public function edit(string $url_code): View|RedirectResponse
    {
        $company = $this->authorizeAdmin($url_code);
        $user = Auth::guard('web')->user();

        return view('settings.company.edit', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'company'        => $company,
            'companyName'    => $company->name,
            'userName'       => $user->user_name,
            'prefectures'    => Prefectures::getList(),
            'roundingTypes'  => self::ROUNDING_TYPES,
        ]);
    }

    /**
     * 企業情報変更確認画面表示（バリデーション実行）
     */
    public function confirm(Request $request, string $url_code): View|RedirectResponse
    {
        $company = $this->authorizeAdmin($url_code);
        $user = Auth::guard('web')->user();

        $rules = [
            'name'             => ['required', 'string', 'max:255'],
            'abbreviated_name' => ['required', 'string', 'max:255'],
            'abbreviated_kana' => ['required', 'string', 'regex:/^[ァ-ヶー\s]+$/u'],
            'url_code'         => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('companies', 'url_code')->ignore($company->id),
            ],
            'corporate_number' => ['nullable', 'string', 'digits:13'],
            'invoice_number'   => ['nullable', 'string', 'regex:/^T\d{13}$/i'],
            'tel'              => ['required', 'string', 'max:20', 'regex:/^0\d{1,4}-?\d{1,4}-?\d{3,4}$/'],
            'homepage'         => ['nullable', 'url', 'max:255'],
            'zip_code'         => ['required', 'string', 'max:10', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture_code'  => ['required', 'integer', 'between:1,47'],
            'city'             => ['required', 'string', 'max:255'],
            'address'          => ['required', 'string', 'max:255'],
            'tax_rate'         => ['required', 'integer', 'min:0', 'max:100'],
            'amount_rounding'  => ['required', 'in:round,floor,ceil'],
            'tax_rounding'     => ['required', 'in:round,floor,ceil'],
        ];

        $messages = [
            'name.required'             => '企業名は必須項目です。',
            'abbreviated_name.required' => '企業名略称は必須項目です。',
            'abbreviated_kana.required' => '企業名カナは必須項目です。',
            'abbreviated_kana.regex'    => '企業名カナは全角カナで入力してください。',
            'url_code.required'         => '専用URLコードは必須項目です。',
            'url_code.min'              => '専用URLコードは3文字以上で入力してください。',
            'url_code.max'              => '専用URLコードは50文字以内で入力してください。',
            'url_code.regex'            => '専用URLコードは半角英数字、ハイフン(-)、アンダースコア(_)のみ使用できます。',
            'url_code.unique'           => 'この専用URLコードは既に他社で使用されています。',
            'corporate_number.digits'   => '法人番号は13桁の半角数字で入力してください。',
            'invoice_number.regex'      => '適格請求書発行事業者登録番号はTから始まる14桁の半角英数字（例：T1234567890123）で入力してください。',
            'tel.required'              => '電話番号は必須項目です。',
            'tel.regex'                 => '電話番号は正しい形式（例：03-1234-5678）で入力してください。',
            'homepage.url'              => '有効なURL形式で入力してください。',
            'zip_code.required'         => '郵便番号は必須項目です。',
            'zip_code.regex'            => '郵便番号は7桁の半角数字（例：100-0001）で入力してください。',
            'prefecture_code.required'  => '都道府県は必須項目です。',
            'city.required'             => '市区町村は必須項目です。',
            'address.required'          => '番地建物は必須項目です。',
            'tax_rate.required'         => '標準消費税率は必須項目です。',
            'tax_rate.integer'          => '標準消費税率は0以上の整数で入力してください。',
            'tax_rate.min'              => '標準消費税率は0以上の整数で入力してください。',
            'tax_rate.max'              => '標準消費税率は100以下の数値を入力してください。',
            'amount_rounding.required'  => '金額端数処理は必須項目です。',
            'amount_rounding.in'        => '有効な金額端数処理区分を選択してください。',
            'tax_rounding.required'     => '消費税端数処理は必須項目です。',
            'tax_rounding.in'           => '有効な消費税端数処理区分を選択してください。',
        ];

        $request->validate($rules, $messages);

        $postData = $request->all();

        return view('settings.company.confirm', [
            'activeMenu'          => 'settings',
            'currentCompany'      => $company,
            'companyName'         => $company->name,
            'userName'            => $user->user_name,
            'postData'            => $postData,
            'prefectures'         => Prefectures::getList(),
            'prefectureName'      => Prefectures::getNameByCode((int)($postData['prefecture_code'] ?? 0)) ?? '',
            'roundingTypes'       => self::ROUNDING_TYPES,
            'amountRoundingLabel' => self::ROUNDING_TYPES[$postData['amount_rounding'] ?? 'round'] ?? '四捨五入',
            'taxRoundingLabel'    => self::ROUNDING_TYPES[$postData['tax_rounding'] ?? 'round'] ?? '四捨五入',
        ]);
    }

    /**
     * 企業情報更新処理（DB更新）
     */
    public function update(Request $request, string $url_code): RedirectResponse
    {
        $company = $this->authorizeAdmin($url_code);

        $postData = $request->all();

        // バリデーション（直接POSTされた場合の二重安全策）
        $rules = [
            'name'             => ['required', 'string', 'max:255'],
            'abbreviated_name' => ['required', 'string', 'max:255'],
            'abbreviated_kana' => ['required', 'string', 'regex:/^[ァ-ヶー\s]+$/u'],
            'url_code'         => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('companies', 'url_code')->ignore($company->id),
            ],
            'corporate_number' => ['nullable', 'string', 'digits:13'],
            'invoice_number'   => ['nullable', 'string', 'regex:/^T\d{13}$/i'],
            'tel'              => ['required', 'string', 'max:20', 'regex:/^0\d{1,4}-?\d{1,4}-?\d{3,4}$/'],
            'homepage'         => ['nullable', 'url', 'max:255'],
            'zip_code'         => ['required', 'string', 'max:10', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture_code'  => ['required', 'integer', 'between:1,47'],
            'city'             => ['required', 'string', 'max:255'],
            'address'          => ['required', 'string', 'max:255'],
            'tax_rate'         => ['required', 'integer', 'min:0', 'max:100'],
            'amount_rounding'  => ['required', 'in:round,floor,ceil'],
            'tax_rounding'     => ['required', 'in:round,floor,ceil'],
        ];

        $request->validate($rules);

        $company->update([
            'name'             => $postData['name'],
            'abbreviated_name' => $postData['abbreviated_name'],
            'abbreviated_kana' => $postData['abbreviated_kana'],
            'url_code'         => $postData['url_code'],
            'corporate_number' => !empty($postData['corporate_number']) ? $postData['corporate_number'] : null,
            'invoice_number'   => !empty($postData['invoice_number']) ? strtoupper(trim($postData['invoice_number'])) : null,
            'tel'              => $postData['tel'],
            'homepage'         => !empty($postData['homepage']) ? $postData['homepage'] : null,
            'zip_code'         => $postData['zip_code'],
            'prefecture_code'  => (int)$postData['prefecture_code'],
            'city'             => $postData['city'],
            'address'          => $postData['address'],
            'tax_rate'         => (int)$postData['tax_rate'],
            'amount_rounding'  => $postData['amount_rounding'] ?? 'round',
            'tax_rounding'     => $postData['tax_rounding'] ?? 'round',
        ]);

        // セッション保持情報の更新
        session([
            'company_name'     => $company->name,
            'company_url_code' => $company->url_code,
        ]);

        return redirect()->route('tenant.company.complete', ['url_code' => $company->url_code]);
    }

    /**
     * 企業情報更新完了画面表示
     */
    public function complete(string $url_code): View
    {
        $company = $this->authorizeAdmin($url_code);
        $user = Auth::guard('web')->user();

        return view('settings.company.complete', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'userName'       => $user->user_name,
        ]);
    }
}
