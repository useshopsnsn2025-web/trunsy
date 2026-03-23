<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\Currency as CurrencyModel;
use think\Response;
use think\facade\Db;

/**
 * 货币管理控制器
 */
class Currency extends Base
{
    /**
     * 货币列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = Db::table('currencies')->order('sort', 'asc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->whereRaw("code LIKE ? OR name LIKE ? OR symbol LIKE ?", [
                "%{$keyword}%", "%{$keyword}%", "%{$keyword}%"
            ]);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        $data = [];
        foreach ($list as $row) {
            $data[] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'symbol' => $row['symbol'],
                'decimals' => (int)$row['decimals'],
                'sort' => (int)$row['sort'],
                'is_active' => (bool)$row['is_active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $this->paginate($data, $total, $page, $pageSize);
    }

    /**
     * 获取货币详情
     */
    public function read($id): Response
    {
        $row = Db::table('currencies')->where('id', $id)->find();
        if (!$row) {
            return $this->error('Currency not found');
        }

        return $this->success([
            'id' => $row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'symbol' => $row['symbol'],
            'decimals' => (int)$row['decimals'],
            'sort' => (int)$row['sort'],
            'is_active' => (bool)$row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ]);
    }

    /**
     * 新增货币
     */
    public function create(): Response
    {
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true) ?: [];
        $data = array_merge(input(), $jsonData);

        // 验证必填字段
        if (empty($data['code']) || empty($data['name']) || empty($data['symbol'])) {
            return $this->error('Currency code, name and symbol are required');
        }

        // 验证货币代码格式（3位大写字母）
        $code = strtoupper($data['code']);
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            return $this->error('Invalid currency code format. Use 3 uppercase letters like: USD, EUR');
        }

        // 检查货币代码是否已存在
        $exists = Db::table('currencies')->where('code', $code)->find();
        if ($exists) {
            return $this->error('Currency code already exists');
        }

        // 获取最大排序值
        $maxSort = Db::table('currencies')->max('sort') ?: 0;

        // 直接使用 Db 插入，避免模型问题
        $insertData = [
            'code' => $code,
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'decimals' => (int)($data['decimals'] ?? 2),
            'sort' => $data['sort'] ?? ($maxSort + 1),
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = Db::table('currencies')->insertGetId($insertData);

        // 为新货币创建默认汇率配置（默认值为 1）
        $rateKey = 'rate_' . $code;
        $existingRate = Db::table('system_configs')->where('key', $rateKey)->find();
        if (!$existingRate) {
            Db::table('system_configs')->insert([
                'group' => 'currency',
                'key' => $rateKey,
                'value' => '1',
                'type' => 'number',
                'name' => $code . ' Exchange Rate',
                'description' => 'Exchange rate for ' . $code . ' (relative to USD)',
                'sort' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $currency = Db::table('currencies')->where('id', $id)->find();

        return $this->success([
            'id' => $currency['id'],
            'code' => $currency['code'],
            'name' => $currency['name'],
            'symbol' => $currency['symbol'],
            'decimals' => (int)$currency['decimals'],
            'sort' => (int)$currency['sort'],
            'is_active' => (bool)$currency['is_active'],
            'created_at' => $currency['created_at'],
            'updated_at' => $currency['updated_at'],
        ], 'Currency created');
    }

    /**
     * 更新货币
     */
    public function update($id): Response
    {
        $id = (int)$id;
        $currency = CurrencyModel::find($id);
        if (!$currency) {
            return $this->error('Currency not found');
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: [];
        $data = array_merge(input(), $data);

        // 验证货币代码格式
        if (!empty($data['code'])) {
            $code = strtoupper($data['code']);
            if (!preg_match('/^[A-Z]{3}$/', $code)) {
                return $this->error('Invalid currency code format. Use 3 uppercase letters like: USD, EUR');
            }
            if (CurrencyModel::codeExists($code, $id)) {
                return $this->error('Currency code already exists');
            }
            $currency->code = $code;
        }

        if (isset($data['name'])) {
            $currency->name = $data['name'];
        }
        if (isset($data['symbol'])) {
            $currency->symbol = $data['symbol'];
        }
        if (isset($data['decimals'])) {
            $currency->decimals = (int)$data['decimals'];
        }
        if (isset($data['sort'])) {
            $currency->sort = (int)$data['sort'];
        }
        if (isset($data['is_active'])) {
            $currency->is_active = $data['is_active'] ? 1 : 0;
        }

        $currency->force()->save();

        // 返回最新数据
        $freshData = Db::table('currencies')->where('id', $id)->find();

        return $this->success([
            'id' => $freshData['id'],
            'code' => $freshData['code'],
            'name' => $freshData['name'],
            'symbol' => $freshData['symbol'],
            'decimals' => (int)$freshData['decimals'],
            'sort' => (int)$freshData['sort'],
            'is_active' => (bool)$freshData['is_active'],
            'created_at' => $freshData['created_at'],
            'updated_at' => $freshData['updated_at'],
        ], 'Currency updated');
    }

    /**
     * 删除货币
     */
    public function delete($id): Response
    {
        $currency = CurrencyModel::find($id);
        if (!$currency) {
            return $this->error('Currency not found');
        }

        // 检查是否有国家使用此货币
        $countryCount = Db::table('countries')
            ->where('currency_code', $currency->code)
            ->count();

        if ($countryCount > 0) {
            return $this->error("Cannot delete currency. {$countryCount} countries are using this currency.");
        }

        $currency->delete();

        return $this->success(null, 'Currency deleted');
    }

    /**
     * 切换启用状态
     */
    public function updateStatus($id): Response
    {
        $currency = CurrencyModel::find($id);
        if (!$currency) {
            return $this->error('Currency not found');
        }

        // 检查是否有国家使用此货币
        if ($currency->is_active) {
            $countryCount = Db::table('countries')
                ->where('currency_code', $currency->code)
                ->where('is_active', 1)
                ->count();

            if ($countryCount > 0) {
                return $this->error("Cannot disable currency. {$countryCount} active countries are using this currency.");
            }
        }

        $currency->is_active = $currency->is_active ? 0 : 1;
        $currency->save();

        return $this->success([
            'id' => $currency->id,
            'is_active' => (bool)$currency->is_active
        ], 'Status updated');
    }

    /**
     * 获取所有启用的货币（用于下拉选择）
     */
    public function options(): Response
    {
        $list = CurrencyModel::getOptions();
        return $this->success($list);
    }

    /**
     * 更新排序
     */
    public function updateSort(): Response
    {
        $ids = input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('Invalid ids');
        }

        foreach ($ids as $sort => $id) {
            CurrencyModel::where('id', $id)->update(['sort' => $sort + 1]);
        }

        return $this->success(null, 'Sort updated');
    }
}
