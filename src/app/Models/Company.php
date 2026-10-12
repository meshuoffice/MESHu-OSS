<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    public const PREFECTURES = [
        1 => '北海道', 2 => '青森県', 3 => '岩手県', 4 => '宮城県', 5 => '秋田県', 6 => '山形県', 7 => '福島県',
        8 => '茨城県', 9 => '栃木県', 10 => '群馬県', 11 => '埼玉県', 12 => '千葉県', 13 => '東京都', 14 => '神奈川県',
        15 => '新潟県', 16 => '富山県', 17 => '石川県', 18 => '福井県', 19 => '山梨県', 20 => '長野県', 21 => '岐阜県',
        22 => '静岡県', 23 => '愛知県', 24 => '三重県', 25 => '滋賀県', 26 => '京都府', 27 => '大阪府', 28 => '兵庫県',
        29 => '奈良県', 30 => '和歌山県', 31 => '鳥取県', 32 => '島根県', 33 => '岡山県', 34 => '広島県', 35 => '山口県',
        36 => '徳島県', 37 => '香川県', 38 => '愛媛県', 39 => '高知県', 40 => '福岡県', 41 => '佐賀県', 42 => '長崎県',
        43 => '熊本県', 44 => '大分県', 45 => '宮崎県', 46 => '鹿児島県', 47 => '沖縄県',
    ];

    protected $table = 'companies';

    protected $fillable = [
        'parent_id',
        'invitation_company_id',
        'url_code',
        'corporate_number',
        'name',
        'abbreviated_name',
        'abbreviated_kana',
        'sub_name',
        'zip_code',
        'prefecture_code',
        'city',
        'address',
        'homepage',
        'tel',
        'tax_rate',
        'amount_rounding',
        'tax_rounding',
        'status',
    ];

    protected $casts = [
        'prefecture_code' => 'integer',
        'tax_rate'        => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function badge(): HasOne
    {
        return $this->hasOne(CompanyBadge::class, 'company_id');
    }

    public function buyerLinks(): HasMany
    {
        return $this->hasMany(CompanyLink::class, 'buyer_company_id');
    }

    public function sellerLinks(): HasMany
    {
        return $this->hasMany(CompanyLink::class, 'seller_company_id');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class, 'company_id');
    }

    /**
     * 都道府県コードに対応する都道府県名を取得
     */
    public function getPrefectureName(): string
    {
        $code = (int)($this->prefecture_code ?? 0);
        return self::PREFECTURES[$code] ?? '';
    }

    /**
     * 郵便番号・都道府県・市区町村を含むフル住所を取得
     */
    public function getFullAddress(): string
    {
        $zip  = !empty($this->zip_code) ? '〒' . $this->zip_code . ' ' : '';
        $pref = $this->getPrefectureName();
        $city = $this->city ?? '';
        $addr = $this->address ?? '';

        return trim($zip . $pref . $city . ' ' . $addr);
    }

    /**
     * 都道府県と所在地（市区町村・番地）を取得（郵便番号なし）
     */
    public function getPrefecturalAddress(): string
    {
        $pref = $this->getPrefectureName();
        $city = $this->city ?? '';
        $addr = $this->address ?? '';

        return trim($pref . $city . ' ' . $addr);
    }

    /**
     * 認証バッジ付き企業であるか判定
     */
    public function isAuthenticated(): bool
    {
        if (isset($this->attributes['is_authenticated'])) {
            return (bool)$this->attributes['is_authenticated'];
        }

        return $this->badge && $this->badge->status === 'active';
    }
}
