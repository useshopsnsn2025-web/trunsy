<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 快捷回复模型
 */
class QuickReply extends Model
{
    protected $name = 'quick_replies';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'group_id' => 'integer',
        'service_id' => 'integer',
        'use_count' => 'integer',
        'sort' => 'integer',
        'is_enabled' => 'integer',
    ];

    /**
     * 关联分组
     */
    public function group()
    {
        return $this->belongsTo(QuickReplyGroup::class, 'group_id', 'id');
    }

    /**
     * 关联客服
     */
    public function service()
    {
        return $this->belongsTo(CustomerService::class, 'service_id', 'id');
    }

    /**
     * 关联翻译
     */
    public function translations()
    {
        return $this->hasMany(QuickReplyTranslation::class, 'reply_id', 'id');
    }

    /**
     * 获取本地化标题
     */
    public function getLocalizedTitle(string $locale = 'zh-tw'): string
    {
        $translation = $this->translations()->where('locale', $locale)->find();
        if ($translation && !empty($translation->title)) {
            return $translation->title;
        }
        // 降级到繁体中文
        if ($locale !== 'zh-tw') {
            $translation = $this->translations()->where('locale', 'zh-tw')->find();
            if ($translation && !empty($translation->title)) {
                return $translation->title;
            }
        }
        // 再降级到简体中文（兼容旧数据）
        if ($locale !== 'zh-cn') {
            $translation = $this->translations()->where('locale', 'zh-cn')->find();
            if ($translation && !empty($translation->title)) {
                return $translation->title;
            }
        }
        return $this->title ?? '';
    }

    /**
     * 获取本地化内容
     */
    public function getLocalizedContent(string $locale = 'zh-tw'): string
    {
        $translation = $this->translations()->where('locale', $locale)->find();
        if ($translation && !empty($translation->content)) {
            return $translation->content;
        }
        // 降级到繁体中文
        if ($locale !== 'zh-tw') {
            $translation = $this->translations()->where('locale', 'zh-tw')->find();
            if ($translation && !empty($translation->content)) {
                return $translation->content;
            }
        }
        // 再降级到简体中文（兼容旧数据）
        if ($locale !== 'zh-cn') {
            $translation = $this->translations()->where('locale', 'zh-cn')->find();
            if ($translation && !empty($translation->content)) {
                return $translation->content;
            }
        }
        return $this->content ?? '';
    }
}
