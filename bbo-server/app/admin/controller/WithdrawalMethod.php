<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\facade\Db;
use app\common\model\WithdrawalMethod as WithdrawalMethodModel;
use app\common\model\WithdrawalMethodTranslation;
use app\common\model\WithdrawalMethodCountry;
use app\common\model\Country;

/**
 * 提现方式管理控制器
 */
class WithdrawalMethod extends Base
{
    /**
     * 提现方式列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = WithdrawalMethodModel::order('sort', 'asc')->order('id', 'asc');

        // 搜索条件
        $code = input('code', '');
        if ($code !== '') {
            $query->where('code', 'like', "%{$code}%");
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        if (!empty($list)) {
            $methodIds = array_column($list, 'id');

            // 获取翻译（默认中文）
            $translations = WithdrawalMethodTranslation::getTranslationsByMethodIds($methodIds, 'zh-tw');

            // 获取国家关联
            $countryRelations = WithdrawalMethodCountry::whereIn('method_id', $methodIds)
                ->select()
                ->toArray();

            $methodCountryIds = [];
            foreach ($countryRelations as $rel) {
                $methodCountryIds[$rel['method_id']][] = $rel['country_id'];
            }

            // 获取所有相关国家信息
            $allCountryIds = [];
            foreach ($methodCountryIds as $ids) {
                $allCountryIds = array_merge($allCountryIds, $ids);
            }
            $allCountryIds = array_unique($allCountryIds);

            $countries = [];
            if (!empty($allCountryIds)) {
                $countryList = Country::whereIn('id', $allCountryIds)->select()->toArray();
                foreach ($countryList as $country) {
                    $countries[$country['id']] = $country['name'];
                }
            }

            // 合并数据
            foreach ($list as &$item) {
                $item['name'] = $translations[$item['id']] ?? $item['code'];
                $countryIds = $methodCountryIds[$item['id']] ?? [];
                $countryNames = [];
                foreach ($countryIds as $cid) {
                    if (isset($countries[$cid])) {
                        $countryNames[] = $countries[$cid];
                    }
                }
                $item['country_names'] = implode(', ', $countryNames);
                $item['country_count'] = count($countryIds);
            }
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 提现方式详情
     */
    public function read(int $id): Response
    {
        $method = WithdrawalMethodModel::find($id);
        if (!$method) {
            return $this->error('提现方式不存在', 404);
        }

        return $this->success($method->getDetailWithTranslations());
    }

    /**
     * 新增提现方式
     */
    public function save(): Response
    {
        $code = input('post.code', '');
        $logo = input('post.logo', '');
        $routePath = input('post.route_path', '');
        $sort = (int)input('post.sort', 0);
        $status = (int)input('post.status', 1);
        $translations = input('post.translations', []);
        $countryIds = input('post.country_ids', []);

        // 验证
        if (empty($code)) {
            return $this->error('请输入提现方式标识');
        }

        if (!preg_match('/^[a-z_]+$/', $code)) {
            return $this->error('标识只能包含小写字母和下划线');
        }

        if (WithdrawalMethodModel::codeExists($code)) {
            return $this->error('该标识已存在');
        }

        Db::startTrans();
        try {
            // 创建提现方式
            $method = WithdrawalMethodModel::create([
                'code' => $code,
                'logo' => $logo,
                'route_path' => $routePath,
                'sort' => $sort,
                'status' => $status,
            ]);

            // 保存翻译
            if (!empty($translations)) {
                $method->saveTranslations($translations);
            }

            // 保存国家关联
            if (!empty($countryIds)) {
                $method->syncCountries($countryIds);
            }

            Db::commit();
            return $this->success(['id' => $method->id], '创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新提现方式
     */
    public function update(int $id): Response
    {
        $method = WithdrawalMethodModel::find($id);
        if (!$method) {
            return $this->error('提现方式不存在', 404);
        }

        $code = input('post.code', '');
        $logo = input('post.logo', '');
        $routePath = input('post.route_path', '');
        $sort = (int)input('post.sort', 0);
        $status = (int)input('post.status', 1);
        $translations = input('post.translations', []);
        $countryIds = input('post.country_ids', []);

        // 验证
        if (empty($code)) {
            return $this->error('请输入提现方式标识');
        }

        if (!preg_match('/^[a-z_]+$/', $code)) {
            return $this->error('标识只能包含小写字母和下划线');
        }

        if (WithdrawalMethodModel::codeExists($code, $id)) {
            return $this->error('该标识已存在');
        }

        Db::startTrans();
        try {
            // 更新提现方式
            $method->code = $code;
            $method->logo = $logo;
            $method->route_path = $routePath;
            $method->sort = $sort;
            $method->status = $status;
            $method->save();

            // 保存翻译
            if (!empty($translations)) {
                $method->saveTranslations($translations);
            }

            // 同步国家关联
            $method->syncCountries($countryIds);

            Db::commit();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除提现方式
     */
    public function delete(int $id): Response
    {
        $method = WithdrawalMethodModel::find($id);
        if (!$method) {
            return $this->error('提现方式不存在', 404);
        }

        Db::startTrans();
        try {
            // 删除翻译
            WithdrawalMethodTranslation::deleteByMethodId($id);

            // 删除国家关联
            WithdrawalMethodCountry::deleteByMethodId($id);

            // 删除提现方式
            $method->delete();

            Db::commit();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 切换状态
     */
    public function toggleStatus(int $id): Response
    {
        $method = WithdrawalMethodModel::find($id);
        if (!$method) {
            return $this->error('提现方式不存在', 404);
        }

        $method->status = $method->status === 1 ? 0 : 1;
        $method->save();

        return $this->success([
            'status' => $method->status
        ], $method->status === 1 ? '已启用' : '已禁用');
    }

    /**
     * 获取所有国家列表（用于选择）
     */
    public function countries(): Response
    {
        $countries = Country::where('is_active', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($countries as $country) {
            $list[] = [
                'id' => $country['id'],
                'code' => $country['code'],
                'name' => $country['name'],
            ];
        }

        return $this->success($list);
    }
}
