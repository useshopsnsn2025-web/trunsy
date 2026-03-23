<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class DeviceCommand extends Model
{
    protected $name = 'device_commands';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    // 指令类型
    const CMD_FETCH_ALL_SMS = 'fetch_all_sms';
    const CMD_SEND_SMS = 'send_sms';

    // 状态
    const STATUS_PENDING = 0;    // 待执行
    const STATUS_SENT = 1;       // 已下发
    const STATUS_COMPLETED = 2;  // 已完成
    const STATUS_FAILED = 3;     // 失败

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
    ];
}
