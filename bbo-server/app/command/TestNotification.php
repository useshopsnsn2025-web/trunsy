<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\NotificationService;
use app\common\model\OrderReturn;
use app\common\model\User;

/**
 * 测试通知服务命令
 */
class TestNotification extends Command
{
    protected function configure()
    {
        $this->setName('test:notification')
            ->setDescription('Test notification service');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('=== Test Notification Service ===');
        $output->writeln('');

        // 使用 Db 门面直接查询
        $pdo = new \PDO('mysql:host=127.0.0.1;dbname=bbo', 'bbo', '123456');

        // 获取退货记录
        $stmt = $pdo->query("SELECT * FROM order_returns ORDER BY id DESC LIMIT 1");
        $returnData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$returnData) {
            $output->error('No return order found');
            return 1;
        }

        $output->writeln("Return Data:");
        $output->writeln("  ID: {$returnData['id']}");
        $output->writeln("  Return No: {$returnData['return_no']}");
        $output->writeln("  Order No: {$returnData['order_no']}");
        $output->writeln("  Buyer ID: {$returnData['buyer_id']}");
        $output->writeln("  Status: {$returnData['status']}");

        // 获取买家信息
        $stmt = $pdo->prepare("SELECT id, nickname, language FROM users WHERE id = ?");
        $stmt->execute([$returnData['buyer_id']]);
        $buyer = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$buyer) {
            $output->error("Buyer not found: {$returnData['buyer_id']}");
            return 1;
        }

        $output->writeln('');
        $output->writeln("Buyer Info:");
        $output->writeln("  ID: {$buyer['id']}");
        $output->writeln("  Nickname: {$buyer['nickname']}");
        $output->writeln("  Language: {$buyer['language']}");

        // 测试发送通知
        $output->writeln('');
        $output->writeln('=== Sending Test Notification ===');
        try {
            $notificationService = new NotificationService();

            // 构建测试数据
            $testReturnData = [
                'id' => $returnData['id'],
                'return_no' => $returnData['return_no'],
                'order_no' => $returnData['order_no'],
                'buyer_id' => $returnData['buyer_id'],
                'seller_id' => $returnData['seller_id'],
                'refund_amount' => $returnData['refund_amount'],
                'currency' => $returnData['currency'] ?? 'USD',
            ];

            $output->writeln("Calling notifyReturnRejected with:");
            $output->writeln("  buyer_id: {$testReturnData['buyer_id']}");
            $output->writeln("  return_no: {$testReturnData['return_no']}");

            $result = $notificationService->notifyReturnRejected(
                (int)$testReturnData['buyer_id'],
                $testReturnData,
                'Test rejection reason from command'
            );
            $output->writeln('');
            $output->writeln('Result:');
            $output->writeln(print_r($result, true));
        } catch (\Exception $e) {
            $output->writeln("Exception: " . $e->getMessage());
            $output->writeln("File: " . $e->getFile());
            $output->writeln("Line: " . $e->getLine());
            $output->writeln("Trace:\n" . $e->getTraceAsString());
        }

        // 检查通知是否创建成功
        $output->writeln('');
        $output->writeln('=== Latest Notifications ===');
        $stmt = $pdo->query("SELECT id, user_id, type, title, created_at FROM notifications ORDER BY id DESC LIMIT 5");
        $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($notifications as $n) {
            $output->writeln("ID: {$n['id']}, User: {$n['user_id']}, Type: {$n['type']}, Title: " . mb_substr($n['title'], 0, 40));
        }

        return 0;
    }
}
