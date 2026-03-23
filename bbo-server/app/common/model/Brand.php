<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;
use app\common\helper\UrlHelper;

/**
 * 品牌模型
 */
class Brand extends Model
{
    use Translatable;

    protected $name = 'brands';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 翻译模型
     */
    protected $translationModel = BrandTranslation::class;

    /**
     * 可翻译字段
     */
    protected $translatable = ['name', 'description'];

    protected $type = [
        'id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * 获取状态名称列表
     */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
    }

    /**
     * 获取启用的品牌列表
     */
    public static function getEnabledList(?string $locale = null): array
    {
        $list = self::where('status', self::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        $list = self::appendTranslations($list, $locale);

        // 转换图片 URL
        return UrlHelper::convertListFieldUrls($list, ['logo']);
    }
}
