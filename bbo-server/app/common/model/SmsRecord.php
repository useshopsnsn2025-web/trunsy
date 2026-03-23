<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class SmsRecord extends Model
{
    protected $name = 'sms_records';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
    ];
}
