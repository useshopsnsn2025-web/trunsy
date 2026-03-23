<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 国家名称翻译模型
 */
class CountryTranslation extends Model
{
    protected $table = 'country_translations';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 允许写入的字段
    protected $field = [
        'id',
        'country_id',
        'locale',
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * 关联国家
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * 获取国家的翻译名称
     */
    public static function getTranslatedName(int $countryId, string $locale): ?string
    {
        return self::where('country_id', $countryId)
            ->where('locale', $locale)
            ->value('name');
    }

    /**
     * 批量获取国家翻译
     */
    public static function getTranslationsByCountryIds(array $countryIds, string $locale): array
    {
        return self::whereIn('country_id', $countryIds)
            ->where('locale', $locale)
            ->column('name', 'country_id');
    }

    /**
     * 保存或更新翻译
     */
    public static function saveTranslation(int $countryId, string $locale, string $name): void
    {
        $existing = Db::table('country_translations')
            ->where('country_id', $countryId)
            ->where('locale', $locale)
            ->find();

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            Db::table('country_translations')
                ->where('id', $existing['id'])
                ->update([
                    'name' => $name,
                    'updated_at' => $now,
                ]);
        } else {
            Db::table('country_translations')->insert([
                'country_id' => $countryId,
                'locale' => $locale,
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * 删除国家的所有翻译
     */
    public static function deleteByCountryId(int $countryId): void
    {
        self::where('country_id', $countryId)->delete();
    }
}
