<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 商品状态值模型
 */
class GoodsConditionValue extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'goods_condition_values';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var bool
     */
    protected $updateTime = false;

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'goods_id' => 'integer',
        'group_id' => 'integer',
        'option_id' => 'integer',
    ];

    /**
     * 关联商品
     * @return \think\model\relation\BelongsTo
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }

    /**
     * 关联状态组
     * @return \think\model\relation\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(CategoryConditionGroup::class, 'group_id', 'id');
    }

    /**
     * 关联选项
     * @return \think\model\relation\BelongsTo
     */
    public function option()
    {
        return $this->belongsTo(CategoryConditionOption::class, 'option_id', 'id');
    }

    /**
     * 获取商品的状态值列表
     * @param int $goodsId
     * @param string|null $locale
     * @return array
     */
    public static function getGoodsConditions(int $goodsId, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLang();

        $values = self::where('goods_id', $goodsId)
            ->select()
            ->toArray();

        if (empty($values)) {
            return [];
        }

        // 获取关联的组和选项信息
        $groupIds = array_column($values, 'group_id');
        $optionIds = array_column($values, 'option_id');

        $groups = CategoryConditionGroup::whereIn('id', $groupIds)
            ->select()
            ->toArray();
        $groups = CategoryConditionGroup::appendTranslations($groups, $locale);
        $groupsMap = array_column($groups, null, 'id');

        $options = CategoryConditionOption::whereIn('id', $optionIds)
            ->select()
            ->toArray();
        $options = CategoryConditionOption::appendTranslations($options, $locale);
        $optionsMap = array_column($options, null, 'id');

        // 组装结果
        $result = [];
        foreach ($values as $value) {
            $group = $groupsMap[$value['group_id']] ?? null;
            $option = $optionsMap[$value['option_id']] ?? null;

            if ($group && $option) {
                $result[] = [
                    'group_id' => $value['group_id'],
                    'group_name' => $group['name'],
                    'group_icon' => $group['icon'] ?? null,
                    'option_id' => $value['option_id'],
                    'option_name' => $option['name'],
                    'impact_level' => $option['impact_level'],
                ];
            }
        }

        return $result;
    }

    /**
     * 保存商品状态值
     * @param int $goodsId
     * @param array $conditions 格式：[group_id => option_id, ...]
     * @return bool
     */
    public static function saveGoodsConditions(int $goodsId, array $conditions): bool
    {
        // 删除旧数据
        self::where('goods_id', $goodsId)->delete();

        // 插入新数据
        $data = [];
        foreach ($conditions as $groupId => $optionId) {
            $data[] = [
                'goods_id' => $goodsId,
                'group_id' => $groupId,
                'option_id' => $optionId,
            ];
        }

        if (!empty($data)) {
            (new self())->saveAll($data);
        }

        return true;
    }
}
