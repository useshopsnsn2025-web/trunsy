<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 宝箱翻译模型
 */
class TreasureBoxTranslation extends Model
{
    protected $table = 'treasure_box_translations';
    protected $autoWriteTimestamp = false;
}
