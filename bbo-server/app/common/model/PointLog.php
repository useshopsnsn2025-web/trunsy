<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Lang;

/**
 * 积分流水模型
 */
class PointLog extends Model
{
    protected $table = 'point_logs';
    protected $autoWriteTimestamp = false;

    // 来源类型常量
    const SOURCE_GAME = 'game';         // 游戏
    const SOURCE_ORDER = 'order';       // 购物
    const SOURCE_TASK = 'task';         // 任务
    const SOURCE_CHECKIN = 'checkin';   // 签到
    const SOURCE_EXCHANGE = 'exchange'; // 兑换
    const SOURCE_ADMIN = 'admin';       // 管理员调整
    const SOURCE_INVITE = 'invite';     // 邀请
    const SOURCE_REVIEW = 'review';     // 评价

    // 翻译映射表（不依赖 lang() 函数）
    private static $translationMap = [
        'zh-tw' => [
            'points.xPoints' => ':value 積分',
            'points.dayXCheckinReward' => '第 :day 天簽到獎勵',
            'points.wheelSpinX' => '轉盤抽獎 x:count',
            'points.dailyLoginReward' => '每日登入獎勵',
            'points.orderReward' => '訂單獎勵',
            'points.reviewReward' => '評價獎勵',
            'points.inviteReward' => '邀請獎勵',
            'points.taskReward' => '任務獎勵',
            'points.adminAdjustment' => '管理員調整',
            'points.pointsDeduction' => '積分抵扣',
            'points.checkoutDeduction' => '結帳抵扣',
        ],
        'ja-jp' => [
            'points.xPoints' => ':value ポイント',
            'points.dayXCheckinReward' => ':day 日目チェックイン報酬',
            'points.wheelSpinX' => 'ルーレット x:count',
            'points.dailyLoginReward' => '毎日ログイン報酬',
            'points.orderReward' => '注文報酬',
            'points.reviewReward' => 'レビュー報酬',
            'points.inviteReward' => '招待報酬',
            'points.taskReward' => 'タスク報酬',
            'points.adminAdjustment' => '管理者調整',
            'points.pointsDeduction' => 'ポイント控除',
            'points.checkoutDeduction' => '決済控除',
        ],
        'en-us' => [
            'points.xPoints' => ':value Points',
            'points.dayXCheckinReward' => 'Day :day check-in reward',
            'points.wheelSpinX' => 'Wheel spin x:count',
            'points.dailyLoginReward' => 'Daily login reward',
            'points.orderReward' => 'Order reward',
            'points.reviewReward' => 'Review reward',
            'points.inviteReward' => 'Invite reward',
            'points.taskReward' => 'Task reward',
            'points.adminAdjustment' => 'Admin adjustment',
            'points.pointsDeduction' => 'Points deduction',
            'points.checkoutDeduction' => 'Checkout deduction',
        ],
        'ko-kr' => [
            'points.xPoints' => ':value 포인트',
            'points.dayXCheckinReward' => ':day일차 출석 보상',
            'points.wheelSpinX' => '룰렛 x:count',
            'points.dailyLoginReward' => '일일 로그인 보상',
            'points.orderReward' => '주문 보상',
            'points.reviewReward' => '리뷰 보상',
            'points.inviteReward' => '초대 보상',
            'points.taskReward' => '미션 보상',
            'points.adminAdjustment' => '관리자 조정',
            'points.pointsDeduction' => '포인트 차감',
            'points.checkoutDeduction' => '결제 차감',
        ],
        'id-id' => [
            'points.xPoints' => ':value Poin',
            'points.dayXCheckinReward' => 'Hadiah check-in hari ke-:day',
            'points.wheelSpinX' => 'Putar roda x:count',
            'points.dailyLoginReward' => 'Hadiah login harian',
            'points.orderReward' => 'Hadiah pesanan',
            'points.reviewReward' => 'Hadiah ulasan',
            'points.inviteReward' => 'Hadiah undangan',
            'points.taskReward' => 'Hadiah tugas',
            'points.adminAdjustment' => 'Penyesuaian admin',
            'points.pointsDeduction' => 'Pengurangan poin',
            'points.checkoutDeduction' => 'Pengurangan pembayaran',
        ],
        'ms-my' => [
            'points.xPoints' => ':value Mata',
            'points.dayXCheckinReward' => 'Ganjaran daftar masuk hari ke-:day',
            'points.wheelSpinX' => 'Pusingan roda x:count',
            'points.dailyLoginReward' => 'Ganjaran log masuk harian',
            'points.orderReward' => 'Ganjaran pesanan',
            'points.reviewReward' => 'Ganjaran ulasan',
            'points.inviteReward' => 'Ganjaran jemputan',
            'points.taskReward' => 'Ganjaran tugas',
            'points.adminAdjustment' => 'Pelarasan admin',
            'points.pointsDeduction' => 'Potongan mata',
            'points.checkoutDeduction' => 'Potongan pembayaran',
        ],
        'th-th' => [
            'points.xPoints' => ':value คะแนน',
            'points.dayXCheckinReward' => 'รางวัลเช็คอินวันที่ :day',
            'points.wheelSpinX' => 'หมุนวงล้อ x:count',
            'points.dailyLoginReward' => 'รางวัลเข้าสู่ระบบประจำวัน',
            'points.orderReward' => 'รางวัลคำสั่งซื้อ',
            'points.reviewReward' => 'รางวัลรีวิว',
            'points.inviteReward' => 'รางวัลเชิญชวน',
            'points.taskReward' => 'รางวัลภารกิจ',
            'points.adminAdjustment' => 'การปรับปรุงโดยผู้ดูแล',
            'points.pointsDeduction' => 'หักคะแนน',
            'points.checkoutDeduction' => 'หักจากการชำระเงิน',
        ],
        'fr-fr' => [
            'points.xPoints' => ':value Points',
            'points.dayXCheckinReward' => 'Récompense de connexion jour :day',
            'points.wheelSpinX' => 'Tour de roue x:count',
            'points.dailyLoginReward' => 'Récompense de connexion quotidienne',
            'points.orderReward' => 'Récompense de commande',
            'points.reviewReward' => 'Récompense d\'avis',
            'points.inviteReward' => 'Récompense d\'invitation',
            'points.taskReward' => 'Récompense de mission',
            'points.adminAdjustment' => 'Ajustement administrateur',
            'points.pointsDeduction' => 'Déduction de points',
            'points.checkoutDeduction' => 'Déduction au paiement',
        ],
    ];

    /**
     * 获取用户积分流水
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param string $locale 语言
     */
    public static function getUserLogs(int $userId, int $page = 1, int $pageSize = 20, string $locale = 'en-us'): array
    {
        $query = self::where('user_id', $userId)
            ->order('created_at', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return [
            'list' => array_map(function ($item) use ($locale) {
                return [
                    'id' => $item['id'],
                    'type' => $item['type'],
                    'amount' => (int)$item['amount'],
                    'balance_after' => (int)$item['balance_after'],
                    'source' => $item['source'],
                    'description' => self::translateDescription($item['description'], $locale),
                    'created_at' => $item['created_at'],
                ];
            }, $list),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取翻译文本
     * @param string $key 翻译键
     * @param array $params 替换参数
     * @param string $locale 语言
     * @return string
     */
    private static function getTranslation(string $key, array $params, string $locale): string
    {
        // 获取翻译模板
        $template = self::$translationMap[$locale][$key] ?? self::$translationMap['en-us'][$key] ?? $key;

        // 替换参数
        foreach ($params as $name => $value) {
            $template = str_replace(':' . $name, (string)$value, $template);
        }

        return $template;
    }

    /**
     * 翻译描述文本
     * 将英文描述转换为对应语言
     */
    public static function translateDescription(string $description, string $locale = 'en-us'): string
    {
        // 如果是英文，直接返回
        if ($locale === 'en-us') {
            return $description;
        }

        // 匹配 "X Points" 格式
        if (preg_match('/^(\d+)\s*Points?$/i', $description, $matches)) {
            $points = $matches[1];
            return self::getTranslation('points.xPoints', ['value' => $points], $locale);
        }

        // 匹配 "Day X check-in reward" 格式
        if (preg_match('/^Day\s*(\d+)\s*check-?in\s*reward$/i', $description, $matches)) {
            $day = $matches[1];
            return self::getTranslation('points.dayXCheckinReward', ['day' => $day], $locale);
        }

        // 匹配 "Wheel spin xN" 格式
        if (preg_match('/^Wheel\s*spin\s*x(\d+)$/i', $description, $matches)) {
            $count = $matches[1];
            return self::getTranslation('points.wheelSpinX', ['count' => $count], $locale);
        }

        // 其他预定义的描述翻译
        $simpleTranslations = [
            'Daily login reward' => 'points.dailyLoginReward',
            'Order reward' => 'points.orderReward',
            'Review reward' => 'points.reviewReward',
            'Invite reward' => 'points.inviteReward',
            'Task reward' => 'points.taskReward',
            'Admin adjustment' => 'points.adminAdjustment',
            'Points deduction' => 'points.pointsDeduction',
            'Checkout deduction' => 'points.checkoutDeduction',
        ];

        foreach ($simpleTranslations as $pattern => $key) {
            if (strcasecmp($description, $pattern) === 0) {
                return self::getTranslation($key, [], $locale);
            }
        }

        // 无匹配，返回原始描述
        return $description;
    }

    /**
     * 获取来源标签
     */
    public static function getSourceLabel(string $source): string
    {
        $labels = [
            self::SOURCE_GAME => 'Game',
            self::SOURCE_ORDER => 'Order',
            self::SOURCE_TASK => 'Task',
            self::SOURCE_CHECKIN => 'Check-in',
            self::SOURCE_EXCHANGE => 'Exchange',
            self::SOURCE_ADMIN => 'Admin',
            self::SOURCE_INVITE => 'Invite',
            self::SOURCE_REVIEW => 'Review',
        ];
        return $labels[$source] ?? $source;
    }
}
