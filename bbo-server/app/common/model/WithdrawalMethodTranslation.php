<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 提现方式翻译模型
 */
class WithdrawalMethodTranslation extends Model
{
    protected $table = 'withdrawal_method_translations';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $field = [
        'id',
        'method_id',
        'locale',
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * 关联提现方式
     */
    public function method()
    {
        return $this->belongsTo(WithdrawalMethod::class, 'method_id');
    }

    /**
     * 根据提现方式ID数组获取翻译
     */
    public static function getTranslationsByMethodIds(array $methodIds, string $locale = 'en-us'): array
    {
        if (empty($methodIds)) {
            return [];
        }

        // 先获取指定语言的翻译
        $translations = self::whereIn('method_id', $methodIds)
            ->where('locale', $locale)
            ->column('name', 'method_id');

        // 如果不是英文，获取英文作为降级
        if ($locale !== 'en-us') {
            $enTranslations = self::whereIn('method_id', $methodIds)
                ->where('locale', 'en-us')
                ->column('name', 'method_id');

            // 合并，优先使用指定语言
            foreach ($enTranslations as $methodId => $name) {
                if (!isset($translations[$methodId])) {
                    $translations[$methodId] = $name;
                }
            }
        }

        return $translations;
    }

    /**
     * 保存翻译
     */
    public static function saveTranslation(int $methodId, string $locale, string $name): void
    {
        $existing = self::where('method_id', $methodId)
            ->where('locale', $locale)
            ->find();

        if ($existing) {
            $existing->name = $name;
            $existing->save();
        } else {
            self::create([
                'method_id' => $methodId,
                'locale' => $locale,
                'name' => $name,
            ]);
        }
    }

    /**
     * 删除提现方式的所有翻译
     */
    public static function deleteByMethodId(int $methodId): void
    {
        self::where('method_id', $methodId)->delete();
    }
}
