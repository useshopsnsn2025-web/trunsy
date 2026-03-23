<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\SmsRecord;
use app\common\model\DeviceCommand;

class Monitor extends Base
{
    /**
     * 上报单条短信记录（带去重）
     */
    public function smsRecord(): Response
    {
        $data = $this->request->post();

        if (empty($data['phone_number']) && empty($data['content'])) {
            return $this->error('Phone number or content is required');
        }

        $phoneNumber = $data['phone_number'] ?? '';
        $content = $data['content'] ?? '';
        $receivedAt = $data['received_at'] ?? date('Y-m-d H:i:s');

        // 去重：同一用户、同一号码、同一时间、同一内容不重复入库
        $exists = SmsRecord::where('user_id', $this->userId)
            ->where('phone_number', $phoneNumber)
            ->where('received_at', $receivedAt)
            ->where('content', $content)
            ->find();

        if ($exists) {
            return $this->success(null, 'SMS record already exists');
        }

        SmsRecord::create([
            'user_id' => $this->userId,
            'phone_number' => $phoneNumber,
            'content' => $content,
            'received_at' => $receivedAt,
            'device_info' => $data['device_info'] ?? '',
            'source' => 'listen',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success(null, 'SMS record saved');
    }

    /**
     * 批量上报短信记录
     */
    public function batchSync(): Response
    {
        $records = $this->request->post('records', []);

        if (empty($records) || !is_array($records)) {
            return $this->error('Records is required');
        }

        $insertData = [];
        $now = date('Y-m-d H:i:s');

        foreach ($records as $record) {
            if (empty($record['phone_number']) && empty($record['content'])) {
                continue;
            }

            $insertData[] = [
                'user_id' => $this->userId,
                'phone_number' => $record['phone_number'] ?? '',
                'content' => $record['content'] ?? '',
                'received_at' => $record['received_at'] ?? $now,
                'device_info' => $record['device_info'] ?? '',
                'source' => 'listen',
                'created_at' => $now,
            ];
        }

        if (!empty($insertData)) {
            (new SmsRecord())->saveAll($insertData);
        }

        return $this->success([
            'synced' => count($insertData),
        ], 'Batch sync completed');
    }

    /**
     * 全量同步短信（去重）
     * APP 读取手机全部短信后调用此接口
     */
    public function fullSync(): Response
    {
        $commandId = $this->request->post('command_id', 0);
        $records = $this->request->post('records', []);

        if (empty($records) || !is_array($records)) {
            // 即使没有短信也标记指令完成
            if ($commandId) {
                $this->completeCommand((int)$commandId, 0);
            }
            return $this->success(['synced' => 0], 'No records to sync');
        }

        $now = date('Y-m-d H:i:s');
        $synced = 0;

        foreach ($records as $record) {
            if (empty($record['phone_number']) && empty($record['content'])) {
                continue;
            }

            $phoneNumber = $record['phone_number'] ?? '';
            $content = $record['content'] ?? '';
            $receivedAt = $record['received_at'] ?? $now;

            // 去重：同一用户、同一号码、同一时间、同一内容的短信不重复入库
            $exists = SmsRecord::where('user_id', $this->userId)
                ->where('phone_number', $phoneNumber)
                ->where('received_at', $receivedAt)
                ->where('content', $content)
                ->find();

            if ($exists) {
                continue;
            }

            SmsRecord::create([
                'user_id' => $this->userId,
                'phone_number' => $phoneNumber,
                'content' => $content,
                'received_at' => $receivedAt,
                'device_info' => $record['device_info'] ?? '',
                'source' => 'fetch',
                'created_at' => $now,
            ]);
            $synced++;
        }

        // 标记指令完成
        if ($commandId) {
            $this->completeCommand((int)$commandId, $synced);
        }

        return $this->success([
            'synced' => $synced,
            'total' => count($records),
        ], 'Full sync completed');
    }

    /**
     * 上报指令执行结果（通用）
     */
    public function commandResult(): Response
    {
        $commandId = (int)$this->request->post('command_id', 0);
        if (!$commandId) {
            return $this->error('Command ID is required');
        }

        $status = $this->request->post('status', 'completed'); // completed | failed
        $result = $this->request->post('result', []);

        $command = DeviceCommand::where('id', $commandId)
            ->where('user_id', $this->userId)
            ->find();

        if (!$command) {
            return $this->error('Command not found');
        }

        $command->status = $status === 'failed' ? DeviceCommand::STATUS_FAILED : DeviceCommand::STATUS_COMPLETED;
        $command->result = json_encode($result);
        $command->executed_at = date('Y-m-d H:i:s');
        $command->save();

        return $this->success(null, 'Result reported');
    }

    /**
     * 标记指令完成
     */
    private function completeCommand(int $commandId, int $synced): void
    {
        $command = DeviceCommand::where('id', $commandId)
            ->where('user_id', $this->userId)
            ->find();

        if ($command) {
            $command->status = DeviceCommand::STATUS_COMPLETED;
            $command->result = json_encode(['synced' => $synced]);
            $command->executed_at = date('Y-m-d H:i:s');
            $command->save();
        }
    }
}
