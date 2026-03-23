<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\SchedulerService;

/**
 * 调度器运行命令
 *
 * 此命令需要在服务器上每分钟执行一次：
 * * * * * * php /path/to/think schedule:run >> /var/log/scheduler.log 2>&1
 */
class ScheduleRun extends Command
{
    protected function configure()
    {
        $this->setName('schedule:run')
            ->setDescription('運行計劃任務調度器');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('[' . date('Y-m-d H:i:s') . '] 調度器開始運行...');

        $scheduler = new SchedulerService();
        $results = $scheduler->runDueTasks();

        if (empty($results)) {
            $output->writeln('沒有需要執行的任務');
            return 0;
        }

        foreach ($results as $result) {
            if ($result['success']) {
                $output->writeln("<info>[成功] {$result['name']} - 耗時: {$result['duration']}s</info>");
            } else {
                $output->writeln("<error>[失敗] {$result['name']} - {$result['error']}</error>");
            }
        }

        $output->writeln('[' . date('Y-m-d H:i:s') . '] 調度器運行完成');

        return 0;
    }
}
