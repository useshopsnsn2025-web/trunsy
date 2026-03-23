<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\ExchangeRateService;

/**
 * 更新汇率命令
 *
 * 使用方式:
 *   php think exchange:update
 *
 * 可设置 cron 定时任务每天自动执行:
 *   0 8 * * * php /path/to/think exchange:update
 */
class UpdateExchangeRate extends Command
{
    protected function configure()
    {
        $this->setName('exchange:update')
            ->setDescription('從 API 更新貨幣匯率');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('開始更新匯率...');

        $service = new ExchangeRateService();
        $result = $service->updateRates();

        if ($result['success']) {
            $output->writeln('<info>' . $result['message'] . '</info>');

            if (!empty($result['rates'])) {
                $output->writeln('更新的匯率:');
                foreach ($result['rates'] as $currency => $rate) {
                    $output->writeln("  {$currency}: {$rate}");
                }
            }
        } else {
            $output->writeln('<error>' . $result['message'] . '</error>');
        }

        return $result['success'] ? 0 : 1;
    }
}
