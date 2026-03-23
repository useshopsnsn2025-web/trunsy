<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\OperationLog as OperationLogModel;

/**
 * 操作日志控制器
 */
class OperationLog extends Base
{
    /**
     * 日志列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = OperationLogModel::order('id', 'desc');

        // 搜索条件
        $adminId = input('admin_id', '');
        if ($adminId !== '') {
            $query->where('admin_id', (int)$adminId);
        }

        $module = input('module', '');
        if ($module) {
            $query->where('module', $module);
        }

        $action = input('action', '');
        if ($action) {
            $query->where('action', 'like', "%{$action}%");
        }

        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('admin_name', 'like', "%{$keyword}%")
                    ->whereOr('url', 'like', "%{$keyword}%")
                    ->whereOr('ip', 'like', "%{$keyword}%");
            });
        }

        // 时间范围
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');
        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 日志详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $log = OperationLogModel::find($id);
        if (!$log) {
            return $this->error('日志不存在', 404);
        }
        return $this->success($log->toArray());
    }

    /**
     * 清理日志
     * @return Response
     */
    public function clear(): Response
    {
        $days = input('post.days', 30);
        if ($days < 7) {
            return $this->error('最少保留7天的日志');
        }

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $count = OperationLogModel::where('created_at', '<', $date)->delete();

        return $this->success(['count' => $count], "已清理 {$count} 条日志");
    }

    /**
     * 获取模块列表
     * @return Response
     */
    public function modules(): Response
    {
        $modules = OperationLogModel::distinct(true)->column('module');
        return $this->success($modules);
    }

    /**
     * 日志统计
     * @return Response
     */
    public function statistics(): Response
    {
        // 总日志数
        $totalLogs = OperationLogModel::count();

        // 今日日志数
        $today = date('Y-m-d');
        $todayLogs = OperationLogModel::whereDay('created_at', $today)->count();

        // 本周日志数
        $weekLogs = OperationLogModel::whereWeek('created_at')->count();

        // 按模块统计
        $moduleStats = OperationLogModel::field('module, count(*) as count')
            ->group('module')
            ->select()
            ->toArray();

        // 最近7天每日统计
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = OperationLogModel::whereDay('created_at', $date)->count();
            $dailyStats[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        return $this->success([
            'total_logs' => $totalLogs,
            'today_logs' => $todayLogs,
            'week_logs' => $weekLogs,
            'module_stats' => $moduleStats,
            'daily_stats' => $dailyStats,
        ]);
    }
}
