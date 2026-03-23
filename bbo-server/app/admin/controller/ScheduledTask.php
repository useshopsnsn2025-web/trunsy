<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\ScheduledTask as ScheduledTaskModel;
use app\common\model\ScheduledTaskLog;
use app\common\service\SchedulerService;

/**
 * 计划任务控制器
 */
class ScheduledTask extends Base
{
    /**
     * 获取任务列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $status = input('status', '');
        $keyword = input('keyword', '');

        $query = ScheduledTaskModel::order('sort', 'desc')->order('id', 'desc');

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->whereOr('command', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 添加描述信息
        $scheduler = new SchedulerService();
        foreach ($list as &$item) {
            $item['cron_description'] = $scheduler->describeCronExpression($item['cron_expression']);
            $item['status_text'] = $item['status'] == 1 ? '启用' : '禁用';
            $item['last_result_text'] = $item['last_result'] === null ? '未执行' : ($item['last_result'] == 1 ? '成功' : '失败');
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取任务详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $data = $task->toArray();
        $scheduler = new SchedulerService();
        $data['cron_description'] = $scheduler->describeCronExpression($task->cron_expression);

        return $this->success($data);
    }

    /**
     * 创建任务
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        if (empty($data['name'])) {
            return $this->error('请填写任务名称');
        }
        if (empty($data['command'])) {
            return $this->error('请填写执行命令');
        }
        if (empty($data['cron_expression'])) {
            return $this->error('请填写Cron表达式');
        }

        // 验证 Cron 表达式
        $scheduler = new SchedulerService();
        if (!$scheduler->isValidCronExpression($data['cron_expression'])) {
            return $this->error('无效的Cron表达式');
        }

        $task = new ScheduledTaskModel();
        $task->name = $data['name'];
        $task->description = $data['description'] ?? '';
        $task->command = $data['command'];
        $task->cron_expression = $data['cron_expression'];
        $task->status = $data['status'] ?? 0;
        $task->timeout = $data['timeout'] ?? 300;
        $task->is_singleton = $data['is_singleton'] ?? 1;
        $task->sort = $data['sort'] ?? 0;

        // 计算下次执行时间
        if ($task->status == 1) {
            $task->next_run_at = $scheduler->getNextRunTime($task->cron_expression);
        }

        $task->save();

        return $this->success(['id' => $task->id], '创建成功');
    }

    /**
     * 更新任务
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $data = input('post.');

        // 验证 Cron 表达式
        if (!empty($data['cron_expression'])) {
            $scheduler = new SchedulerService();
            if (!$scheduler->isValidCronExpression($data['cron_expression'])) {
                return $this->error('无效的Cron表达式');
            }
            $task->cron_expression = $data['cron_expression'];

            // 重新计算下次执行时间
            if ($task->status == 1 || ($data['status'] ?? $task->status) == 1) {
                $task->next_run_at = $scheduler->getNextRunTime($task->cron_expression);
            }
        }

        if (isset($data['name'])) {
            $task->name = $data['name'];
        }
        if (isset($data['description'])) {
            $task->description = $data['description'];
        }
        if (isset($data['command'])) {
            $task->command = $data['command'];
        }
        if (isset($data['status'])) {
            $task->status = (int)$data['status'];
        }
        if (isset($data['timeout'])) {
            $task->timeout = (int)$data['timeout'];
        }
        if (isset($data['is_singleton'])) {
            $task->is_singleton = (int)$data['is_singleton'];
        }
        if (isset($data['sort'])) {
            $task->sort = (int)$data['sort'];
        }

        $task->save();

        return $this->success([], '更新成功');
    }

    /**
     * 删除任务
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        // 删除相关日志
        ScheduledTaskLog::where('task_id', $id)->delete();

        $task->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 启用任务
     * @param int $id
     * @return Response
     */
    public function enable(int $id): Response
    {
        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $scheduler = new SchedulerService();
        $task->status = 1;
        $task->next_run_at = $scheduler->getNextRunTime($task->cron_expression);
        $task->save();

        return $this->success([], '启用成功');
    }

    /**
     * 禁用任务
     * @param int $id
     * @return Response
     */
    public function disable(int $id): Response
    {
        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $task->status = 0;
        $task->next_run_at = null;
        $task->save();

        return $this->success([], '禁用成功');
    }

    /**
     * 立即执行任务
     * @param int $id
     * @return Response
     */
    public function run(int $id): Response
    {
        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $scheduler = new SchedulerService();
        $result = $scheduler->runTask($task);

        if ($result['success']) {
            return $this->success([
                'output' => $result['output'],
                'duration' => $result['duration'],
            ], '执行成功');
        }

        return $this->error('执行失败: ' . ($result['error'] ?? '未知错误'));
    }

    /**
     * 获取任务执行日志
     * @param int $id
     * @return Response
     */
    public function logs(int $id): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $query = ScheduledTaskLog::where('task_id', $id)
            ->order('id', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 添加状态文本
        $statusMap = [0 => '执行中', 1 => '成功', 2 => '失败'];
        foreach ($list as &$item) {
            $item['status_text'] = $statusMap[$item['status']] ?? '未知';
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 清除任务日志
     * @param int $id
     * @return Response
     */
    public function clearLogs(int $id): Response
    {
        $task = ScheduledTaskModel::find($id);
        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $days = (int)input('days', 30);
        $beforeDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $deleted = ScheduledTaskLog::where('task_id', $id)
            ->where('created_at', '<', $beforeDate)
            ->delete();

        return $this->success(['deleted' => $deleted], "已清除 {$deleted} 条日志");
    }

    /**
     * 获取可用命令列表
     * @return Response
     */
    public function commands(): Response
    {
        // 返回系统中可用的 think 命令
        $commands = [
            ['command' => 'exchange:update', 'name' => '更新汇率', 'description' => '从API获取最新汇率'],
            ['command' => 'schedule:run', 'name' => '运行调度器', 'description' => '执行所有到期的计划任务'],
        ];

        return $this->success($commands);
    }

    /**
     * 常用 Cron 表达式
     * @return Response
     */
    public function cronPresets(): Response
    {
        $presets = [
            ['expression' => '* * * * *', 'name' => '每分钟'],
            ['expression' => '*/5 * * * *', 'name' => '每5分钟'],
            ['expression' => '*/10 * * * *', 'name' => '每10分钟'],
            ['expression' => '*/30 * * * *', 'name' => '每30分钟'],
            ['expression' => '0 * * * *', 'name' => '每小时'],
            ['expression' => '0 */2 * * *', 'name' => '每2小时'],
            ['expression' => '0 */6 * * *', 'name' => '每6小时'],
            ['expression' => '0 */12 * * *', 'name' => '每12小时'],
            ['expression' => '0 0 * * *', 'name' => '每天 00:00'],
            ['expression' => '0 8 * * *', 'name' => '每天 08:00'],
            ['expression' => '0 12 * * *', 'name' => '每天 12:00'],
            ['expression' => '0 0 * * 0', 'name' => '每周日 00:00'],
            ['expression' => '0 0 * * 1', 'name' => '每周一 00:00'],
            ['expression' => '0 0 1 * *', 'name' => '每月1号 00:00'],
        ];

        return $this->success($presets);
    }
}
