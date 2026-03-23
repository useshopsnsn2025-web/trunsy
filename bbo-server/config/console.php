<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        \app\command\UpdateExchangeRate::class,
        \app\command\ScheduleRun::class,
        \app\command\CheckUserOnline::class,
        \app\command\ImportUiTranslations::class,
        \app\command\TestNotification::class,
        \app\command\GoodsExport::class,
    ],
];
