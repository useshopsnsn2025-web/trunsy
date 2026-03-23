<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;
use app\common\helper\UrlHelper;

/**
 * 广告Banner模型
 */
class Banner extends Model
{
    use Translatable;

    protected $name = 'banners';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 翻译模型
     */
    protected $translationModel = BannerTranslation::class;

    /**
     * 可翻译字段
     */
    protected $translatable = ['title', 'subtitle', 'content', 'button_text'];

    protected $type = [
        'id' => 'integer',
        'link_type' => 'integer',
        'display_type' => 'integer',
        'button_style' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'click_count' => 'integer',
    ];

    // 展示类型常量
    const DISPLAY_TYPE_IMAGE = 1;      // 图片型
    const DISPLAY_TYPE_COLOR = 2;      // 背景色型

    // 按钮样式常量
    const BUTTON_STYLE_SOLID_BLACK = 1;   // 实心黑
    const BUTTON_STYLE_SOLID_THEME = 2;   // 实心主题色
    const BUTTON_STYLE_OUTLINE = 3;       // 边框
    const BUTTON_STYLE_CUSTOM = 4;        // 自定义颜色

    // 广告位置常量 - 首页
    const POSITION_HOME_TOP = 'home_top';           // 首页顶部轮播
    const POSITION_HOME_MIDDLE = 'home_middle';     // 首页中部横幅
    const POSITION_HOME_BOTTOM = 'home_bottom';     // 首页底部

    // 广告位置常量 - 分类相关
    const POSITION_CATEGORY_TOP = 'category_top';   // 分类页顶部
    const POSITION_SEARCH_TOP = 'search_top';       // 搜索页顶部

    // 广告位置常量 - 购物相关
    const POSITION_CART_TOP = 'cart_top';           // 购物车顶部
    const POSITION_CART_BOTTOM = 'cart_bottom';     // 购物车底部（结算前）

    // 广告位置常量 - 活动相关
    const POSITION_PROMOTION_TOP = 'promotion_top'; // 活动列表顶部
    const POSITION_COUPON_TOP = 'coupon_top';       // 优惠券中心顶部

    // 广告位置常量 - 用户中心
    const POSITION_PROFILE_TOP = 'profile_top';     // 个人中心顶部
    const POSITION_WALLET_TOP = 'wallet_top';       // 钱包页顶部

    // 广告位置常量 - 卖家中心
    const POSITION_SELL_TOP = 'sell_top';           // 卖家中心顶部
    const POSITION_PUBLISH_TOP = 'publish_top';     // 发布商品页顶部

    // 广告位置常量 - 其他
    const POSITION_FAVORITES_TOP = 'favorites_top'; // 收藏夹顶部
    const POSITION_CREDIT_TOP = 'credit_top';       // 信用购页顶部
    const POSITION_SPLASH = 'splash';               // 启动页/开屏广告

    // 链接类型常量
    const LINK_NONE = 0;           // 无链接
    const LINK_GOODS = 1;          // 商品详情
    const LINK_CATEGORY = 2;       // 分类页
    const LINK_PROMOTION = 3;      // 活动详情
    const LINK_URL = 4;            // 外部链接
    const LINK_BRAND = 5;          // 品牌页
    const LINK_SEARCH = 6;         // 搜索结果
    const LINK_COUPON = 7;         // 优惠券中心
    const LINK_CART = 8;           // 购物车
    const LINK_WALLET = 9;         // 钱包
    const LINK_FAVORITES = 10;     // 收藏夹
    const LINK_ORDERS = 11;        // 订单列表
    const LINK_PUBLISH = 12;       // 发布商品
    const LINK_CREDIT = 13;        // 信用购
    const LINK_SELLER_CENTER = 14; // 卖家中心
    const LINK_PAGE = 15;          // 自定义页面路径

    public static function getLinkTypeNames(): array
    {
        return [
            self::LINK_NONE => '无链接',
            self::LINK_GOODS => '商品详情',
            self::LINK_CATEGORY => '分类页',
            self::LINK_PROMOTION => '活动详情',
            self::LINK_URL => '外部链接',
            self::LINK_BRAND => '品牌页',
            self::LINK_SEARCH => '搜索结果',
            self::LINK_COUPON => '优惠券中心',
            self::LINK_CART => '购物车',
            self::LINK_WALLET => '钱包',
            self::LINK_FAVORITES => '收藏夹',
            self::LINK_ORDERS => '订单列表',
            self::LINK_PUBLISH => '发布商品',
            self::LINK_CREDIT => '信用购',
            self::LINK_SELLER_CENTER => '卖家中心',
            self::LINK_PAGE => '自定义页面',
        ];
    }

    /**
     * 获取指定位置的启用Banner列表
     * @param string $position 位置
     * @param string $locale 语言
     * @return array
     */
    public static function getEnabledByPosition(string $position, string $locale = 'en-us'): array
    {
        $now = date('Y-m-d H:i:s');

        $query = self::where('position', $position)
            ->where('status', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_time')
                    ->whereOr('start_time', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_time')
                    ->whereOr('end_time', '>=', $now);
            })
            ->order('sort', 'desc')
            ->order('id', 'asc');

        $banners = $query->select()->toArray();

        // 附加翻译
        $banners = self::appendTranslations($banners, $locale);

        // 处理每个Banner
        foreach ($banners as &$banner) {
            // 转换图片URL
            $banner['image'] = UrlHelper::getFullUrl($banner['image'] ?? '');

            // 处理产品图片数组
            if (!empty($banner['product_images'])) {
                $productImages = json_decode($banner['product_images'], true) ?: [];
                $banner['product_images'] = array_map(function ($img) {
                    return UrlHelper::getFullUrl($img);
                }, $productImages);
            } else {
                $banner['product_images'] = [];
            }
        }

        return $banners;
    }

    /**
     * 获取所有首页Banner（供APP首页使用）
     * @param string $locale 语言
     * @return array
     */
    public static function getHomeBanners(string $locale = 'en-us'): array
    {
        return [
            'home_top' => self::getEnabledByPosition(self::POSITION_HOME_TOP, $locale),
            'home_middle' => self::getEnabledByPosition(self::POSITION_HOME_MIDDLE, $locale),
            'home_bottom' => self::getEnabledByPosition(self::POSITION_HOME_BOTTOM, $locale),
        ];
    }
}
