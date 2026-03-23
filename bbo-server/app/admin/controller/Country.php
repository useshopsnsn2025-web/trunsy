<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\Country as CountryModel;
use app\common\model\CountryTranslation;
use app\common\model\Currency as CurrencyModel;
use app\common\model\Language;
use think\Response;
use think\facade\Db;

/**
 * 国家/地区管理控制器
 */
class Country extends Base
{
    /**
     * 国家列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = Db::table('countries')->order('sort', 'asc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->whereRaw("code LIKE ? OR name LIKE ? OR currency_code LIKE ?", [
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
                'currency_code' => $row['currency_code'],
                'flag' => $row['flag'],
                'locale' => $row['locale'],
                'sort' => (int)$row['sort'],
                'is_active' => (bool)$row['is_active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $this->paginate($data, $total, $page, $pageSize);
    }

    /**
     * 获取国家详情（含所有翻译）
     */
    public function read($id): Response
    {
        $country = CountryModel::find($id);
        if (!$country) {
            return $this->error('Country not found');
        }

        return $this->success($country->getDetailWithTranslations());
    }

    /**
     * 新增国家
     */
    public function create(): Response
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: [];
        $data = array_merge(input(), $data);

        // 验证必填字段
        if (empty($data['code']) || empty($data['name']) || empty($data['currency_code'])) {
            return $this->error('Country code, name and currency code are required');
        }

        // 验证国家代码格式（2位大写字母）
        $code = strtoupper($data['code']);
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            return $this->error('Invalid country code format. Use 2 uppercase letters like: US, TW');
        }

        // 检查国家代码是否已存在
        if (CountryModel::codeExists($code)) {
            return $this->error('Country code already exists');
        }

        // 检查货币是否存在
        $currencyCode = strtoupper($data['currency_code']);
        if (!CurrencyModel::where('code', $currencyCode)->find()) {
            return $this->error('Currency not found');
        }

        // 获取最大排序值
        $maxSort = CountryModel::max('sort') ?: 0;

        $country = new CountryModel();
        $country->code = $code;
        $country->name = $data['name'];
        $country->currency_code = $currencyCode;
        $country->flag = $data['flag'] ?? strtolower($code);
        $country->locale = $data['locale'] ?? 'en-us';
        $country->sort = $data['sort'] ?? ($maxSort + 1);
        $country->is_active = $data['is_active'] ?? 1;
        $country->save();

        // 保存翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            $country->saveTranslations($data['translations']);
        }

        return $this->success($country->getDetailWithTranslations(), 'Country created');
    }

    /**
     * 更新国家
     */
    public function update($id): Response
    {
        $id = (int)$id;
        $country = CountryModel::find($id);
        if (!$country) {
            return $this->error('Country not found');
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: [];
        $data = array_merge(input(), $data);

        // 验证国家代码格式
        if (!empty($data['code'])) {
            $code = strtoupper($data['code']);
            if (!preg_match('/^[A-Z]{2}$/', $code)) {
                return $this->error('Invalid country code format. Use 2 uppercase letters like: US, TW');
            }
            if (CountryModel::codeExists($code, $id)) {
                return $this->error('Country code already exists');
            }
            $country->code = $code;
        }

        if (isset($data['name'])) {
            $country->name = $data['name'];
        }
        if (!empty($data['currency_code'])) {
            $currencyCode = strtoupper($data['currency_code']);
            if (!CurrencyModel::where('code', $currencyCode)->find()) {
                return $this->error('Currency not found');
            }
            $country->currency_code = $currencyCode;
        }
        if (isset($data['flag'])) {
            $country->flag = $data['flag'];
        }
        if (isset($data['locale'])) {
            $country->locale = $data['locale'];
        }
        if (isset($data['sort'])) {
            $country->sort = (int)$data['sort'];
        }
        if (isset($data['is_active'])) {
            $country->is_active = $data['is_active'] ? 1 : 0;
        }

        $country->force()->save();

        // 保存翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            $country->saveTranslations($data['translations']);
        }

        // 重新获取完整数据
        $country = CountryModel::find($id);

        return $this->success($country->getDetailWithTranslations(), 'Country updated');
    }

    /**
     * 删除国家
     */
    public function delete($id): Response
    {
        $country = CountryModel::find($id);
        if (!$country) {
            return $this->error('Country not found');
        }

        // 删除翻译
        CountryTranslation::deleteByCountryId($id);

        // 删除国家
        $country->delete();

        return $this->success(null, 'Country deleted');
    }

    /**
     * 切换启用状态
     */
    public function updateStatus($id): Response
    {
        $country = CountryModel::find($id);
        if (!$country) {
            return $this->error('Country not found');
        }

        $country->is_active = $country->is_active ? 0 : 1;
        $country->save();

        return $this->success([
            'id' => $country->id,
            'is_active' => (bool)$country->is_active
        ], 'Status updated');
    }

    /**
     * 获取所有启用的国家（用于下拉选择）
     */
    public function options(): Response
    {
        $locale = $this->locale;
        $list = CountryModel::getActiveCountries($locale);

        $options = [];
        foreach ($list as $country) {
            $options[] = [
                'id' => $country['id'],
                'code' => $country['code'],
                'name' => $country['translated_name'],
                'currency_code' => $country['currency_code'],
                'flag' => $country['flag'],
            ];
        }

        return $this->success($options);
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
            CountryModel::where('id', $id)->update(['sort' => $sort + 1]);
        }

        return $this->success(null, 'Sort updated');
    }

    /**
     * 获取可用语言列表（用于翻译编辑）
     */
    public function languages(): Response
    {
        $languages = Language::getActiveOptions();
        return $this->success($languages);
    }

    /**
     * 设置为默认国家
     */
    public function setDefault($id): Response
    {
        $country = CountryModel::find($id);
        if (!$country) {
            return $this->error('Country not found');
        }

        if (!$country->is_active) {
            return $this->error('Cannot set inactive country as default');
        }

        // 使用 SystemConfig 存储默认国家
        \app\common\model\SystemConfig::setConfig('default_country_code', $country->code);
        \app\common\model\SystemConfig::setConfig('default_locale', $country->locale ?: 'en-us');
        \app\common\model\SystemConfig::setConfig('default_currency', $country->currency_code);

        return $this->success([
            'code' => $country->code,
            'name' => $country->name,
            'locale' => $country->locale,
            'currency_code' => $country->currency_code,
        ], 'Default country set successfully');
    }

    /**
     * 获取默认国家
     */
    public function getDefault(): Response
    {
        $defaultCode = \app\common\model\SystemConfig::getConfig('default_country_code', 'TW');
        $country = CountryModel::where('code', $defaultCode)->find();

        if (!$country) {
            // 如果默认国家不存在，返回第一个启用的国家
            $country = CountryModel::where('is_active', 1)->order('sort', 'asc')->find();
        }

        if (!$country) {
            return $this->success(null);
        }

        return $this->success([
            'id' => $country->id,
            'code' => $country->code,
            'name' => $country->name,
            'locale' => $country->locale ?: 'en-us',
            'currency_code' => $country->currency_code,
            'flag' => $country->flag,
        ]);
    }
}
