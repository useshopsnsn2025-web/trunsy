<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * COD风险规则模型
 */
class CodRiskRule extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'cod_risk_rules';

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
        'risk_score' => 'integer',
        'is_enabled' => 'integer',
        'sort' => 'integer',
        'rule_config' => 'json',
    ];

    /**
     * 规则类型常量
     */
    const TYPE_NEW_USER = 'new_user';           // 新用户
    const TYPE_HIGH_AMOUNT = 'high_amount';     // 高金额
    const TYPE_HISTORY_REFUSED = 'history_refused'; // 拒收历史
    const TYPE_DAILY_LIMIT = 'daily_limit';     // 每日限制
    const TYPE_REGION = 'region';               // 地区限制

    /**
     * 动作常量
     */
    const ACTION_FLAG = 'flag';                 // 标记
    const ACTION_BLOCK = 'block';               // 阻止
    const ACTION_REQUIRE_PREAUTH = 'require_preauth'; // 要求预授权

    /**
     * 获取启用的规则
     * @return \think\Collection
     */
    public static function getEnabledRules()
    {
        return self::where('is_enabled', 1)
            ->order('sort', 'desc')
            ->select();
    }
}
