<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 蛋分级奖品翻译模型
 */
class EggTierPrizeTranslation extends Model
{
    protected $table = 'egg_tier_prize_translations';
    protected $autoWriteTimestamp = false;
}
