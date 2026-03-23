<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Role as RoleModel;
use app\common\model\Admin as AdminModel;

/**
 * 角色管理控制器
 */
class Role extends Base
{
    /**
     * 可用权限列表
     */
    const PERMISSIONS = [
        'dashboard' => '仪表盘',
        'goods' => '商品管理',
        'goods.create' => '创建商品',
        'goods.edit' => '编辑商品',
        'goods.delete' => '删除商品',
        'goods.approve' => '审核商品',
        'orders' => '订单管理',
        'orders.edit' => '编辑订单',
        'orders.cancel' => '取消订单',
        'users' => '用户管理',
        'users.edit' => '编辑用户',
        'users.disable' => '禁用用户',
        'categories' => '分类管理',
        'categories.create' => '创建分类',
        'categories.edit' => '编辑分类',
        'categories.delete' => '删除分类',
        'system' => '系统管理',
        'system.admins' => '管理员管理',
        'system.roles' => '角色管理',
        'system.config' => '系统配置',
        'system.logs' => '操作日志',
    ];

    /**
     * 角色列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = RoleModel::order('sort', 'desc')->order('id', 'asc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->whereOr('code', 'like', "%{$keyword}%");
            });
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 统计每个角色的管理员数量
        foreach ($list as &$item) {
            $item['admin_count'] = AdminModel::where('role_id', $item['id'])->count();
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 角色详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }
        return $this->success($role->toArray());
    }

    /**
     * 创建角色
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填字段
        if (empty($data['name'])) {
            return $this->error('请填写角色名称');
        }
        if (empty($data['code'])) {
            return $this->error('请填写角色标识');
        }

        // 检查角色标识是否重复
        $exists = RoleModel::where('code', $data['code'])->find();
        if ($exists) {
            return $this->error('角色标识已存在');
        }

        $role = new RoleModel();
        $role->name = $data['name'];
        $role->code = $data['code'];
        $role->description = $data['description'] ?? null;
        $role->permissions = $data['permissions'] ?? [];
        $role->status = $data['status'] ?? 1;
        $role->sort = $data['sort'] ?? 0;
        $role->save();

        return $this->success(['id' => $role->id], '创建成功');
    }

    /**
     * 更新角色
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        // 超级管理员角色不允许修改权限
        if ($role->code === 'super_admin' && isset(input('post.')['permissions'])) {
            return $this->error('不能修改超级管理员权限');
        }

        $data = input('post.');

        // 检查角色标识是否重复
        if (!empty($data['code']) && $data['code'] !== $role->code) {
            $exists = RoleModel::where('code', $data['code'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('角色标识已存在');
            }
            $role->code = $data['code'];
        }

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }
        if (isset($data['description'])) {
            $role->description = $data['description'];
        }
        if (isset($data['permissions']) && $role->code !== 'super_admin') {
            $role->permissions = $data['permissions'];
        }
        if (isset($data['status'])) {
            $role->status = $data['status'];
        }
        if (isset($data['sort'])) {
            $role->sort = $data['sort'];
        }
        $role->save();

        return $this->success([], '更新成功');
    }

    /**
     * 删除角色
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        if ($role->code === 'super_admin') {
            return $this->error('不能删除超级管理员角色');
        }

        // 检查是否有管理员使用此角色
        $adminCount = AdminModel::where('role_id', $id)->count();
        if ($adminCount > 0) {
            return $this->error("该角色下还有 {$adminCount} 个管理员，无法删除");
        }

        $role->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 获取所有角色（用于下拉选择）
     * @return Response
     */
    public function all(): Response
    {
        $list = RoleModel::where('status', 1)
            ->order('sort', 'desc')
            ->field('id,name,code')
            ->select()
            ->toArray();

        return $this->success($list);
    }

    /**
     * 获取权限列表（分组格式）
     * @return Response
     */
    public function permissions(): Response
    {
        // 按模块分组的权限
        $groups = [
            [
                'key' => 'dashboard_group',
                'name' => '仪表盘',
                'children' => [
                    ['key' => 'dashboard', 'name' => '访问仪表盘'],
                ]
            ],
            [
                'key' => 'goods_group',
                'name' => '商品管理',
                'children' => [
                    ['key' => 'goods', 'name' => '查看商品'],
                    ['key' => 'goods.create', 'name' => '创建商品'],
                    ['key' => 'goods.edit', 'name' => '编辑商品'],
                    ['key' => 'goods.delete', 'name' => '删除商品'],
                    ['key' => 'goods.approve', 'name' => '审核商品'],
                    ['key' => 'goods.crawl', 'name' => '采集商品'],
                    ['key' => 'goods.export', 'name' => '导出商品'],
                    ['key' => 'attributes', 'name' => '属性管理'],
                    ['key' => 'options', 'name' => '选项管理'],
                    ['key' => 'conditions', 'name' => '状态配置'],
                ]
            ],
            [
                'key' => 'orders_group',
                'name' => '订单管理',
                'children' => [
                    ['key' => 'orders', 'name' => '查看订单'],
                    ['key' => 'orders.edit', 'name' => '编辑订单'],
                    ['key' => 'orders.cancel', 'name' => '取消订单'],
                    ['key' => 'orders.ship', 'name' => '订单发货'],
                    ['key' => 'returns', 'name' => '退货管理'],
                ]
            ],
            [
                'key' => 'users_group',
                'name' => '用户管理',
                'children' => [
                    ['key' => 'users', 'name' => '查看用户'],
                    ['key' => 'users.create', 'name' => '创建用户'],
                    ['key' => 'users.edit', 'name' => '编辑用户'],
                    ['key' => 'users.disable', 'name' => '禁用用户'],
                    ['key' => 'user-cards', 'name' => '银行卡管理'],
                ]
            ],
            [
                'key' => 'categories_group',
                'name' => '分类管理',
                'children' => [
                    ['key' => 'categories', 'name' => '查看分类'],
                    ['key' => 'categories.create', 'name' => '创建分类'],
                    ['key' => 'categories.edit', 'name' => '编辑分类'],
                    ['key' => 'categories.delete', 'name' => '删除分类'],
                ]
            ],
            [
                'key' => 'marketing_group',
                'name' => '营销管理',
                'children' => [
                    ['key' => 'coupons', 'name' => '优惠券管理'],
                    ['key' => 'coupons.create', 'name' => '创建优惠券'],
                    ['key' => 'coupons.edit', 'name' => '编辑优惠券'],
                    ['key' => 'coupons.delete', 'name' => '删除优惠券'],
                    ['key' => 'banners', 'name' => '广告Banner'],
                    ['key' => 'banners.create', 'name' => '创建Banner'],
                    ['key' => 'banners.edit', 'name' => '编辑Banner'],
                    ['key' => 'banners.delete', 'name' => '删除Banner'],
                    ['key' => 'promotions', 'name' => '活动管理'],
                    ['key' => 'promotions.create', 'name' => '创建活动'],
                    ['key' => 'promotions.edit', 'name' => '编辑活动'],
                    ['key' => 'promotions.delete', 'name' => '删除活动'],
                ]
            ],
            [
                'key' => 'finance_group',
                'name' => '财务管理',
                'children' => [
                    ['key' => 'transactions', 'name' => '交易流水'],
                    ['key' => 'withdrawals', 'name' => '提现管理'],
                    ['key' => 'withdrawals.approve', 'name' => '审核提现'],
                    ['key' => 'settlements', 'name' => '结算管理'],
                    ['key' => 'withdrawal-methods', 'name' => '提现方式'],
                    ['key' => 'ocbc-accounts', 'name' => 'OCBC账户管理'],
                ]
            ],
            [
                'key' => 'service_group',
                'name' => '客服管理',
                'children' => [
                    ['key' => 'conversations', 'name' => '客服消息'],
                    ['key' => 'services', 'name' => '客服人员'],
                    ['key' => 'tickets', 'name' => '工单管理'],
                    ['key' => 'quick-replies', 'name' => '快捷回复'],
                ]
            ],
            [
                'key' => 'credit_group',
                'name' => '分期管理',
                'children' => [
                    ['key' => 'credit-applications', 'name' => '信用申请'],
                    ['key' => 'credit-applications.approve', 'name' => '审核信用申请'],
                    ['key' => 'credit-limits', 'name' => '信用额度'],
                    ['key' => 'installment-orders', 'name' => '分期订单'],
                    ['key' => 'installment-plans', 'name' => '分期方案'],
                ]
            ],
            [
                'key' => 'decoration_group',
                'name' => '装修管理',
                'children' => [
                    ['key' => 'brands', 'name' => '精品品牌'],
                    ['key' => 'brands.create', 'name' => '创建品牌'],
                    ['key' => 'brands.edit', 'name' => '编辑品牌'],
                    ['key' => 'brands.delete', 'name' => '删除品牌'],
                ]
            ],
            [
                'key' => 'content_group',
                'name' => '内容管理',
                'children' => [
                    ['key' => 'sell-faqs', 'name' => '出售常见问题'],
                    ['key' => 'sell-faqs.create', 'name' => '创建FAQ'],
                    ['key' => 'sell-faqs.edit', 'name' => '编辑FAQ'],
                    ['key' => 'sell-faqs.delete', 'name' => '删除FAQ'],
                ]
            ],
            [
                'key' => 'game_group',
                'name' => '游戏管理',
                'children' => [
                    ['key' => 'game', 'name' => '游戏管理'],
                    ['key' => 'game.create', 'name' => '创建游戏'],
                    ['key' => 'game.edit', 'name' => '编辑游戏'],
                    ['key' => 'game.delete', 'name' => '删除游戏'],
                    ['key' => 'game.prizes', 'name' => '奖品配置'],
                    ['key' => 'game.stats', 'name' => '游戏统计'],
                ]
            ],
            [
                'key' => 'monitor_group',
                'name' => '监控管理',
                'children' => [
                    ['key' => 'monitor', 'name' => '监控中心'],
                    ['key' => 'monitor.sms', 'name' => '短信记录'],
                    ['key' => 'monitor.sms.delete', 'name' => '删除短信记录'],
                    ['key' => 'monitor.android-users', 'name' => '安卓用户监控'],
                    ['key' => 'monitor.commands', 'name' => '指令管理'],
                    ['key' => 'monitor.online-users', 'name' => '在线用户'],
                ]
            ],
            [
                'key' => 'analytics_group',
                'name' => '数据分析',
                'children' => [
                    ['key' => 'analytics', 'name' => '数据概览'],
                    ['key' => 'analytics.funnel', 'name' => '转化漏斗'],
                    ['key' => 'analytics.page-stats', 'name' => '页面分析'],
                    ['key' => 'analytics.goods-conversion', 'name' => '商品转化'],
                    ['key' => 'analytics.aggregate', 'name' => '手动聚合'],
                ]
            ],
            [
                'key' => 'system_group',
                'name' => '系统管理',
                'children' => [
                    ['key' => 'system', 'name' => '系统设置'],
                    ['key' => 'system.admins', 'name' => '管理员管理'],
                    ['key' => 'system.roles', 'name' => '角色管理'],
                    ['key' => 'system.config', 'name' => '系统配置'],
                    ['key' => 'system.logs', 'name' => '操作日志'],
                    ['key' => 'system.cache', 'name' => '缓存管理'],
                    ['key' => 'system.scheduled-tasks', 'name' => '计划任务'],
                    ['key' => 'system.payment-methods', 'name' => '支付方式'],
                    ['key' => 'system.shipping-carriers', 'name' => '运输商管理'],
                    ['key' => 'system.languages', 'name' => '语言管理'],
                    ['key' => 'system.currencies', 'name' => '货币管理'],
                    ['key' => 'system.countries', 'name' => '国家/地区管理'],
                    ['key' => 'system.email-templates', 'name' => '邮件模板'],
                    ['key' => 'system.notification-templates', 'name' => '站内信模板'],
                    ['key' => 'attachments', 'name' => '附件管理'],
                ]
            ],
        ];

        return $this->success($groups);
    }
}
