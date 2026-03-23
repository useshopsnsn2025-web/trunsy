<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 宝箱奖品翻译模型
 */
class TreasureBoxPrizeTranslation extends Model
{
    protected $table = 'treasure_box_prize_translations';
    protected $autoWriteTimestamp = false;
}
