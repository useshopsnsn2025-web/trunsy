<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 蛋分级翻译模型
 */
class EggTierTranslation extends Model
{
    protected $table = 'egg_tier_translations';
    protected $autoWriteTimestamp = false;
}
