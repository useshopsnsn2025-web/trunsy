<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\User;

/**
 * 检查用户在线状态命令
 * 将心跳超时的用户标记为离线
 */
class CheckUserOnline extends Command
{
    /**
     * 配置命令
     */
    protected function configure()
    {
        $this->setName('user:check-online')
            ->setDescription('检查用户在线状态，将超时用户标记为离线');
    }

    /**
     * 执行命令
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('<info>开始检查用户在线状态...</info>');

        try {
            // 获取当前在线人数
            $beforeCount = User::getOnlineCount();
            $output->writeln("当前在线用户数: {$beforeCount}");

            // 标记超时用户为离线
            $markedCount = User::markTimeoutUsersOffline();

            // 获取更新后在线人数
            $afterCount = User::getOnlineCount();

            $output->writeln("<info>已将 {$markedCount} 个超时用户标记为离线</info>");
            $output->writeln("更新后在线用户数: {$afterCount}");

            return 0;
        } catch (\Exception $e) {
            $output->writeln('<error>检查失败: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
