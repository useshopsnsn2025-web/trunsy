<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Admin as AdminModel;
use app\common\model\Role;

/**
 * 管理员管理控制器
 */
class Admin extends Base
{
    /**
     * 管理员列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = AdminModel::with(['role'])
            ->order('id', 'asc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                    ->whereOr('nickname', 'like', "%{$keyword}%")
                    ->whereOr('email', 'like', "%{$keyword}%");
            });
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $roleId = input('role_id', '');
        if ($roleId !== '') {
            $query->where('role_id', (int)$roleId);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 管理员详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $admin = AdminModel::with(['role'])->find($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }
        return $this->success($admin->toArray());
    }

    /**
     * 创建管理员
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填字段
        if (empty($data['username'])) {
            return $this->error('请填写用户名');
        }
        if (empty($data['password'])) {
            return $this->error('请填写密码');
        }
        if (strlen($data['password']) < 6) {
            return $this->error('密码长度不能少于6位');
        }

        // 检查用户名是否重复
        $exists = AdminModel::where('username', $data['username'])->find();
        if ($exists) {
            return $this->error('用户名已存在');
        }

        $admin = new AdminModel();
        $admin->username = $data['username'];
        $admin->password = $data['password'];
        $admin->nickname = $data['nickname'] ?? $data['username'];
        $admin->avatar = $data['avatar'] ?? null;
        $admin->email = $data['email'] ?? null;
        $admin->phone = $data['phone'] ?? null;
        $admin->role_id = $data['role_id'] ?? null;
        $admin->status = $data['status'] ?? 1;
        $admin->save();

        return $this->success(['id' => $admin->id], '创建成功');
    }

    /**
     * 更新管理员
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $admin = AdminModel::find($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        $data = input('post.');

        // 检查用户名是否重复
        if (!empty($data['username']) && $data['username'] !== $admin->username) {
            $exists = AdminModel::where('username', $data['username'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('用户名已存在');
            }
            $admin->username = $data['username'];
        }

        if (isset($data['nickname'])) {
            $admin->nickname = $data['nickname'];
        }
        if (isset($data['avatar'])) {
            $admin->avatar = $data['avatar'];
        }
        if (isset($data['email'])) {
            $admin->email = $data['email'];
        }
        if (isset($data['phone'])) {
            $admin->phone = $data['phone'];
        }
        if (isset($data['role_id'])) {
            $admin->role_id = $data['role_id'];
        }
        if (isset($data['status'])) {
            $admin->status = $data['status'];
        }
        $admin->save();

        return $this->success([], '更新成功');
    }

    /**
     * 删除管理员
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        if ($id === 1) {
            return $this->error('不能删除超级管理员');
        }

        $admin = AdminModel::find($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        $admin->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 重置密码
     * @param int $id
     * @return Response
     */
    public function resetPassword(int $id): Response
    {
        $admin = AdminModel::find($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        $password = input('post.password', '');
        if (strlen($password) < 6) {
            return $this->error('密码长度不能少于6位');
        }

        $admin->password = $password;
        $admin->save();

        return $this->success([], '密码已重置');
    }

    /**
     * 启用/禁用管理员
     * @param int $id
     * @return Response
     */
    public function toggleStatus(int $id): Response
    {
        if ($id === 1) {
            return $this->error('不能禁用超级管理员');
        }

        $admin = AdminModel::find($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        $admin->status = $admin->status === 1 ? 0 : 1;
        $admin->save();

        return $this->success([], $admin->status === 1 ? '已启用' : '已禁用');
    }
}
