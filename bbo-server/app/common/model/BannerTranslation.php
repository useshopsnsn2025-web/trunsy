<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * Banner翻译模型
 */
class BannerTranslation extends Model
{
    protected $name = 'banner_translations';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id' => 'integer',
        'banner_id' => 'integer',
    ];

    /**
     * 关联Banner
     */
    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id', 'id');
    }
}
