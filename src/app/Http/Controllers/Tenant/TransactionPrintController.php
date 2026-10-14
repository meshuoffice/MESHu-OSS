<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

class TransactionPrintController extends Controller
{
    protected function getMyCompanyId(): int
    {
        $user = Auth::guard('web')->user();
        if (!$user || !$user->company_id) {
            abort(403, '自社企業情報が存在しません。');
        }
        return (int)$user->company_id;
    }

    /**
     * 帳票印刷プレビュー画面の表示
     */
    public function print(int $id, string $docType = 'auto')
    {
        $myCompanyId = $this->getMyCompanyId();
        $tx = Transaction::getTransactionDetail($id, $myCompanyId);

        if (!$tx) {
            abort(403, '指定された取引データが存在しないか、閲覧権限がありません。');
        }

        // auto の場合は現在のステータスとロールから帳票種別を自動判定
        if ($docType === 'auto') {
            $docType = $this->resolveAutoDocType($tx['status'], $tx['my_role']);
        }

        $validDocTypes = ['estimate_request', 'estimate', 'order', 'order_acceptance'];
        if (!in_array($docType, $validDocTypes, true)) {
            return redirect()->back()->with('error', '無効な帳票種別が指定されました。');
        }

        $items = TransactionItem::where('transaction_id', $id)->orderBy('line_no')->get()->map(fn($i) => (array)$i->getAttributes())->all();

        $buyerCompany  = Company::find($tx['buyer_company_id']);
        $sellerCompany = Company::find($tx['seller_company_id']);

        $docConfig = $this->getDocConfiguration($docType, $buyerCompany, $sellerCompany, $tx);

        $data = [
            'docType'       => $docType,
            'docTitle'      => $docConfig['title'],
            'docNumber'     => $docConfig['docNumber'],
            'issueDate'     => date('Y年m月d日', strtotime((string)($tx['updated_at'] ?? $tx['created_at']))),
            'issuer'        => $docConfig['issuer'],
            'recipient'     => $docConfig['recipient'],
            'tx'            => $tx,
            'items'         => $items,
            'buyerCompany'  => $buyerCompany,
            'sellerCompany' => $sellerCompany,
            'myRole'        => $tx['my_role'],
            'printRemarks'  => $docConfig['remarks'],
        ];

        return view('tenant.transaction.print_sheet', $data);
    }

    /**
     * ステータスとロールに応じたデフォルト帳票の自動解決
     */
    protected function resolveAutoDocType(string $status, string $role): string
    {
        return match ($status) {
            'estimate_request' => 'estimate_request',
            'estimate_replied' => 'estimate',
            'order_placed'     => 'order',
            'order_accepted'   => 'order_acceptance',
            default            => ($role === 'buyer') ? 'estimate_request' : 'estimate',
        };
    }

    /**
     * 帳票種別ごとの設定（タイトル・文書番号・発行元・宛先・備考）を取得
     */
    protected function getDocConfiguration(string $docType, ?Company $buyer, ?Company $seller, array $tx): array
    {
        return match ($docType) {
            'estimate_request' => [
                'title'     => '御 見 積 依 頼 書',
                'docNumber' => sprintf('REQ-%06d', $tx['id']),
                'issuer'    => $buyer,
                'recipient' => $seller,
                'remarks'   => $tx['buyer_remarks'] ?? '',
            ],
            'estimate' => [
                'title'     => '御 見 積 書',
                'docNumber' => sprintf('EST-%06d', $tx['id']),
                'issuer'    => $seller,
                'recipient' => $buyer,
                'remarks'   => $tx['seller_remarks'] ?? '',
            ],
            'order' => [
                'title'     => '御 注 文 書',
                'docNumber' => sprintf('ORD-%06d', $tx['id']),
                'issuer'    => $buyer,
                'recipient' => $seller,
                'remarks'   => $tx['buyer_remarks'] ?? '',
            ],
            'order_acceptance' => [
                'title'     => '注 文 請 書',
                'docNumber' => sprintf('ACK-%06d', $tx['id']),
                'issuer'    => $seller,
                'recipient' => $buyer,
                'remarks'   => $tx['seller_remarks'] ?? '',
            ],
        };
    }
}
