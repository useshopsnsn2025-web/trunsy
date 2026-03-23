<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 货币配置模型
 */
class Currency extends Model
{
    protected $table = 'currencies';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 允许写入的字段
    protected $field = [
        'id',
        'code',
        'name',
        'symbol',
        'decimals',
        'sort',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $type = [
        'decimals' => 'integer',
        'sort' => 'integer',
        'is_active' => 'integer',
    ];

    /**
     * 获取所有启用的货币
     */
    public static function getActiveCurrencies(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取所有启用的货币代码
     */
    public static function getActiveCodes(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->column('code');
    }

    /**
     * 获取货币代码到符号的映射
     */
    public static function getSymbolMap(): array
    {
        return self::where('is_active', 1)
            ->column('symbol', 'code');
    }

    /**
     * 获取货币代码到小数位数的映射
     */
    public static function getDecimalsMap(): array
    {
        return self::where('is_active', 1)
            ->column('decimals', 'code');
    }

    /**
     * 检查货币代码是否已存在
     */
    public static function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = self::where('code', strtoupper($code));
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }

    /**
     * 获取所有货币（用于下拉选择）
     */
    public static function getOptions(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->field('id, code, name, symbol')
            ->select()
            ->toArray();
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
            'symbol' => $this->symbol,
            'decimals' => $this->decimals,
            'sort' => $this->sort,
            'is_active' => (bool)$this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
