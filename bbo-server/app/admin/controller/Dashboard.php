<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\User;
use app\common\model\Goods;
use app\common\model\Order;
use app\common\model\DailyStatistics;

/**
 * 仪表盘控制器
 */
class Dashboard extends Base
{
    /**
     * 概览数据
     */
    public function overview(): Response
    {
        $today = date('Y-m-d');

        // 用户统计
        $totalUsers = User::count();
        $todayUsers = User::whereDay('created_at', $today)->count();
        $activeUsers = User::where('last_login_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))->count();

        // 商品统计
        $totalGoods = Goods::count();
        $todayGoods = Goods::whereDay('created_at', $today)->count();
        $onlineGoods = Goods::where('status', 1)->count();

        // 订单统计
        $totalOrders = Order::count();
        $todayOrders = Order::whereDay('created_at', $today)->count();
        $todayOrderAmount = Order::whereDay('created_at', $today)->sum('total_amount');

        // 本月订单金额
        $monthOrderAmount = Order::whereMonth('created_at')->sum('total_amount');

        return $this->success([
            'users' => [
                'total' => $totalUsers,
                'today' => $todayUsers,
                'active' => $activeUsers,
            ],
            'goods' => [
                'total' => $totalGoods,
                'today' => $todayGoods,
                'online' => $onlineGoods,
            ],
            'orders' => [
                'total' => $totalOrders,
                'today' => $todayOrders,
                'today_amount' => (float)$todayOrderAmount,
                'month_amount' => (float)$monthOrderAmount,
            ],
        ]);
    }

    /**
     * 趋势数据（最近7天/30天）
     */
    public function trend(): Response
    {
        $days = input('days', 7);
        $days = min(max($days, 7), 30);

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data[] = [
                'date' => $date,
                'users' => User::whereDay('created_at', $date)->count(),
                'goods' => Goods::whereDay('created_at', $date)->count(),
                'orders' => Order::whereDay('created_at', $date)->count(),
                'amount' => (float)Order::whereDay('created_at', $date)->sum('total_amount'),
            ];
        }

        return $this->success($data);
    }

    /**
     * 订单状态分布
     */
    public function orderStatus(): Response
    {
        $statusCounts = Order::field('status, count(*) as count')
            ->group('status')
            ->select()
            ->toArray();

        $statusNames = [
            0 => '待支付',
            1 => '待发货',
            2 => '待收货',
            3 => '已完成',
            4 => '已取消',
            5 => '已退款',
        ];

        $result = [];
        foreach ($statusCounts as $item) {
            $result[] = [
                'status' => $item['status'],
                'name' => $statusNames[$item['status']] ?? '未知',
                'count' => $item['count'],
            ];
        }

        return $this->success($result);
    }

    /**
     * 热门分类
     */
    public function hotCategories(): Response
    {
        $categories = Goods::alias('g')
            ->join('categories c', 'g.category_id = c.id')
            ->join('category_translations ct', 'c.id = ct.category_id AND ct.locale = "zh-cn"')
            ->field('c.id, ct.name, count(g.id) as goods_count')
            ->group('c.id')
            ->order('goods_count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        return $this->success($categories);
    }

    /**
     * 最新订单
     */
    public function recentOrders(): Response
    {
        $orders = Order::order('id', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        return $this->success($orders);
    }

    /**
     * 待处理事项
     */
    public function pending(): Response
    {
        // 待审核商品
        $pendingGoods = Goods::where('status', 0)->count();

        // 待处理订单
        $pendingOrders = Order::where('status', 1)->count(); // 待发货

        // 待审核提现
        $pendingWithdrawals = 0; // TODO: 需要提现模型

        return $this->success([
            'pending_goods' => $pendingGoods,
            'pending_orders' => $pendingOrders,
            'pending_withdrawals' => $pendingWithdrawals,
        ]);
    }
}
