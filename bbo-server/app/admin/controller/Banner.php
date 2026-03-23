<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\facade\Cache;
use app\common\model\Banner as BannerModel;

/**
 * 广告Banner管理控制器
 */
class Banner extends Base
{
    /**
     * 支持的语言列表
     */
    protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

    /**
     * 清除Banner缓存（所有语言和位置）
     */
    protected function clearBannerCache(): void
    {
        $positions = [
            'home',  // 首页全部
            // 首页
            'home_top', 'home_middle', 'home_bottom',
            // 分类相关
            'category_top', 'search_top',
            // 购物相关
            'cart_top', 'cart_bottom',
            // 活动相关
            'promotion_top', 'coupon_top',
            // 用户中心
            'profile_top', 'wallet_top', 'favorites_top',
            // 卖家中心
            'sell_top', 'publish_top',
            // 其他
            'credit_top', 'splash',
        ];

        foreach ($this->supportedLocales as $locale) {
            foreach ($positions as $position) {
                Cache::delete('banners:' . $locale . ':' . $position);
            }
        }
    }

    /**
     * Banner列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = BannerModel::order('sort', 'desc')->order('id', 'desc');

        $position = input('position', '');
        if ($position) {
            $query->where('position', $position);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 附加翻译
        $list = BannerModel::appendTranslations($list, $locale);

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * Banner详情
     */
    public function read(int $id): Response
    {
        $banner = BannerModel::find($id);
        if (!$banner) {
            return $this->error('Banner不存在', 404);
        }

        $data = $banner->toArray();

        // 解析产品图片JSON
        if (!empty($data['product_images'])) {
            $data['product_images'] = json_decode($data['product_images'], true) ?: [];
        } else {
            $data['product_images'] = [];
        }

        // 获取所有翻译
        $data['translations'] = [];
        foreach ($this->supportedLocales as $locale) {
            $translation = $banner->translation($locale);
            $data['translations'][$locale] = $translation ? $translation->toArray() : [
                'title' => '',
                'subtitle' => '',
                'content' => '',
                'button_text' => ''
            ];
        }

        return $this->success($data);
    }

    /**
     * 创建Banner
     */
    public function create(): Response
    {
        $data = input('post.');
        $translations = $data['translations'] ?? [];

        // 验证至少有一种语言的标题
        $hasTitle = false;
        foreach ($translations as $locale => $trans) {
            if (!empty($trans['title'])) {
                $hasTitle = true;
                break;
            }
        }
        if (!$hasTitle && empty($data['title'])) {
            return $this->error('请填写标题');
        }

        $displayType = (int)($data['display_type'] ?? 1);

        // 图片型必须上传图片
        if ($displayType === BannerModel::DISPLAY_TYPE_IMAGE && empty($data['image'])) {
            return $this->error('请上传图片');
        }
        // 背景色型必须设置背景色
        if ($displayType === BannerModel::DISPLAY_TYPE_COLOR && empty($data['bg_color']) && empty($data['bg_gradient'])) {
            return $this->error('请设置背景色或渐变色');
        }

        $banner = new BannerModel();
        $banner->title = $data['title'] ?? ($translations['zh-tw']['title'] ?? '');
        $banner->image = $data['image'] ?? '';
        $banner->link_type = $data['link_type'] ?? 0;
        $banner->link_value = $data['link_value'] ?? null;
        $banner->position = $data['position'] ?? 'home_top';
        $banner->display_type = $displayType;
        $banner->bg_color = $data['bg_color'] ?? null;
        $banner->bg_gradient = $data['bg_gradient'] ?? null;
        $banner->text_color = $data['text_color'] ?? '#ffffff';
        $banner->button_style = $data['button_style'] ?? 1;
        $banner->button_color = $data['button_color'] ?? null;
        $banner->product_images = !empty($data['product_images']) ? json_encode($data['product_images']) : null;
        $banner->sort = $data['sort'] ?? 0;
        $banner->start_time = $data['start_time'] ?? null;
        $banner->end_time = $data['end_time'] ?? null;
        $banner->status = $data['status'] ?? 1;
        $banner->save();

        // 保存翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $banner->saveTranslation($locale, $translations[$locale]);
            }
        }

        // 清除缓存
        $this->clearBannerCache();

        return $this->success(['id' => $banner->id], '创建成功');
    }

    /**
     * 更新Banner
     */
    public function update(int $id): Response
    {
        $banner = BannerModel::find($id);
        if (!$banner) {
            return $this->error('Banner不存在', 404);
        }

        $data = input('post.');
        $translations = $data['translations'] ?? [];

        $allowFields = ['title', 'image', 'link_type', 'link_value', 'position',
                        'display_type', 'bg_color', 'bg_gradient', 'text_color', 'button_style', 'button_color',
                        'sort', 'start_time', 'end_time', 'status'];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $banner->$field = $data[$field];
            }
        }

        // 特殊处理产品图片
        if (isset($data['product_images'])) {
            $banner->product_images = !empty($data['product_images']) ? json_encode($data['product_images']) : null;
        }

        $banner->save();

        // 更新翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $banner->saveTranslation($locale, $translations[$locale]);
            }
        }

        // 清除缓存
        $this->clearBannerCache();

        return $this->success([], '更新成功');
    }

    /**
     * 删除Banner
     */
    public function delete(int $id): Response
    {
        $banner = BannerModel::find($id);
        if (!$banner) {
            return $this->error('Banner不存在', 404);
        }

        $banner->delete();

        // 清除缓存
        $this->clearBannerCache();

        return $this->success([], '删除成功');
    }

    /**
     * 获取展示位置列表
     */
    public function positions(): Response
    {
        $positions = [
            // 首页
            ['key' => 'home_top', 'name' => '首页顶部轮播', 'group' => '首页'],
            ['key' => 'home_middle', 'name' => '首页中部横幅', 'group' => '首页'],
            ['key' => 'home_bottom', 'name' => '首页底部', 'group' => '首页'],

            // 分类相关
            ['key' => 'category_top', 'name' => '分类页顶部', 'group' => '分类相关'],
            ['key' => 'search_top', 'name' => '搜索页顶部', 'group' => '分类相关'],

            // 购物相关
            ['key' => 'cart_top', 'name' => '购物车顶部', 'group' => '购物相关'],
            ['key' => 'cart_bottom', 'name' => '购物车底部', 'group' => '购物相关'],

            // 活动相关
            ['key' => 'promotion_top', 'name' => '活动列表顶部', 'group' => '活动相关'],
            ['key' => 'coupon_top', 'name' => '优惠券中心顶部', 'group' => '活动相关'],

            // 用户中心
            ['key' => 'profile_top', 'name' => '个人中心顶部', 'group' => '用户中心'],
            ['key' => 'wallet_top', 'name' => '钱包页顶部', 'group' => '用户中心'],
            ['key' => 'favorites_top', 'name' => '收藏夹顶部', 'group' => '用户中心'],

            // 卖家中心
            ['key' => 'sell_top', 'name' => '卖家中心顶部', 'group' => '卖家中心'],
            ['key' => 'publish_top', 'name' => '发布商品页顶部', 'group' => '卖家中心'],

            // 其他
            ['key' => 'credit_top', 'name' => '信用购页顶部', 'group' => '其他'],
            ['key' => 'splash', 'name' => '启动页/开屏广告', 'group' => '其他'],
        ];
        return $this->success($positions);
    }

    /**
     * 更新状态
     */
    public function updateStatus(int $id): Response
    {
        $banner = BannerModel::find($id);
        if (!$banner) {
            return $this->error('Banner不存在', 404);
        }

        $status = input('post.status');
        if ($status === null) {
            return $this->error('缺少status参数');
        }

        $banner->status = (int)$status;
        $banner->save();

        // 清除缓存
        $this->clearBannerCache();

        return $this->success([], '状态更新成功');
    }

    /**
     * 手动清除Banner缓存（供管理员使用）
     */
    public function clearCache(): Response
    {
        $this->clearBannerCache();
        return $this->success([], '缓存已清除');
    }
}
