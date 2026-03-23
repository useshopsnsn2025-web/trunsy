<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 语言配置模型
 */
class Language extends Model
{
    // 指定表名（复数形式）
    protected $table = 'languages';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 类型转换
    protected $type = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * 获取所有启用的语言代码
     */
    public static function getActiveCodes(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->column('code');
    }

    /**
     * 获取默认语言代码
     */
    public static function getDefaultCode(): string
    {
        $default = self::where('is_default', 1)->value('code');
        return $default ?: 'zh-tw';
    }

    /**
     * 获取所有启用的语言（用于下拉选择）
     */
    public static function getActiveOptions(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->field('id, code, name, native_name, flag')
            ->select()
            ->toArray();
    }

    /**
     * 获取所有语言（包括禁用的）
     */
    public static function getAllOptions(): array
    {
        return self::order('sort', 'asc')
            ->field('id, code, name, native_name, flag, is_active, is_default')
            ->select()
            ->toArray();
    }

    /**
     * 设置为默认语言
     */
    public function setAsDefault(): bool
    {
        // 先取消其他默认语言
        self::where('is_default', 1)->update(['is_default' => 0]);
        // 设置当前语言为默认
        $this->is_default = 1;
        $this->is_active = 1; // 默认语言必须启用
        return $this->save();
    }

    /**
     * 检查语言代码是否已存在
     */
    public static function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = self::where('code', $code);
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }

    /**
     * 获取语言代码到名称的映射
     */
    public static function getCodeNameMap(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->column('name', 'code');
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'native_name' => $this->native_name,
            'flag' => $this->flag,
            'sort' => $this->sort,
            'is_default' => (bool)$this->is_default,
            'is_active' => (bool)$this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
