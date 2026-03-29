<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\User;
use app\common\service\FcmService;

class FcmCheckOffline extends Command
{
    protected function configure()
    {
        $this->setName('fcm:check-offline')
            ->setDescription('检测离线用户并通过 FCM 推送唤醒');
    }

    protected function execute(Input $input, Output $output)
    {
        $fcmService = new FcmService();

        // 查找心跳超时 3 分钟且有 FCM token 的用户
        // 且 10 分钟内没有发送过 FCM 唤醒推送
        $offlineUsers = User::where('last_heartbeat_at', '<', date('Y-m-d H:i:s', strtotime('-3 minutes')))
            ->where('fcm_token', '<>', '')
            ->whereNotNull('fcm_token')
            ->where(function ($query) {
                $query->whereNull('last_fcm_sent_at')
                    ->whereOr('last_fcm_sent_at', '<', date('Y-m-d H:i:s', strtotime('-10 minutes')));
            })
            ->select();

        $sentCount = 0;
        $failCount = 0;

        foreach ($offlineUsers as $user) {
            $success = $fcmService->sendDataMessage($user->fcm_token, [
                'user_id' => $user->id,
            ]);

            if ($success) {
                $user->last_fcm_sent_at = date('Y-m-d H:i:s');
                $user->save();
                $sentCount++;
            } else {
                $failCount++;
            }
        }

        if ($sentCount > 0 || $failCount > 0) {
            $output->writeln("[FCM] Sent: {$sentCount}, Failed: {$failCount}");
        }

        return 0;
    }
}
