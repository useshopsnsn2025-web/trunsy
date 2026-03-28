<?php
declare(strict_types=1);

namespace app\common\traits;

use think\facade\Lang;

/**
 * 可翻译特性
 * 用于支持多语言的模型
 */
trait Translatable
{
    /**
     * 翻译模型类名
     * 子类需要定义此属性
     * @var string
     */
    // protected $translationModel = GoodsTranslation::class;

    /**
     * 翻译字段（由子类定义）
     * @var array
     */
    // protected $translatable = ['title', 'description'];

    // 注意：$translationForeignKey 由使用该 trait 的类定义
    // 避免在 trait 中定义默认值导致属性冲突

    /**
     * 获取可翻译字段
     * @return array
     */
    protected function getTranslatableFields(): array
    {
        return $this->translatable ?? ['title', 'description'];
    }

    /**
     * 获取翻译模型类
     * @return string
     */
    protected function getTranslationModel(): string
    {
        if (property_exists($this, 'translationModel')) {
            return $this->translationModel;
        }
        // 默认: Goods -> GoodsTranslation
        return get_class($this) . 'Translation';
    }

    /**
     * 获取外键名
     * @return string
     */
    protected function getTranslationForeignKey(): string
    {
        if (property_exists($this, 'translationForeignKey') && !empty($this->translationForeignKey)) {
            return $this->translationForeignKey;
        }
        // 默认: Goods -> goods_id
        $className = class_basename($this);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . '_id';
    }

    /**
     * 获取指定语言的翻译
     * @param string|null $locale 语言代码，默认当前语言
     * @return mixed
     */
    public function translation(?string $locale = null)
    {
        $locale = $locale ?? Lang::getLangSet();
        $model = $this->getTranslationModel();
        $foreignKey = $this->getTranslationForeignKey();

        return (new $model)
            ->where($foreignKey, $this->id)
            ->where('locale', $locale)
            ->find();
    }

    /**
     * 获取所有翻译
     * @return \think\Collection
     */
    public function translations()
    {
        $model = $this->getTranslationModel();
        $foreignKey = $this->getTranslationForeignKey();

        return (new $model)
            ->where($foreignKey, $this->id)
            ->select();
    }

    /**
     * 获取翻译后的属性值（带降级逻辑）
     * 降级顺序：请求语言 → en-us → 原始值
     * @param string $key 属性名
     * @param string|null $locale 语言代码
     * @return mixed
     */
    public function getTranslated(string $key, ?string $locale = null)
    {
        $locale = $locale ?? Lang::getLangSet();

        // 尝试获取请求语言的翻译
        $translation = $this->translation($locale);

        // 使用 getData() 而不是魔术属性访问，避免与 Model::$name 属性冲突
        if ($translation) {
            try {
                $value = $translation->getData($key);
                if (!empty($value)) {
                    return $value;
                }
            } catch (\InvalidArgumentException $e) {
                // 字段不存在，继续降级
            }
        }

        // 降级到 en-us
        if ($locale !== 'en-us') {
            $translation = $this->translation('en-us');
            if ($translation) {
                try {
                    $value = $translation->getData($key);
                    if (!empty($value)) {
                        return $value;
                    }
                } catch (\InvalidArgumentException $e) {
                    // 字段不存在，继续降级
                }
            }
        }

        // 回退到原始值
        try {
            return $this->getData($key);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * 保存翻译
     * @param string $locale 语言代码
     * @param array $data 翻译数据
     * @param bool $isOriginal 是否原始语言
     * @param bool $isAutoTranslated 是否自动翻译
     * @return mixed
     */
    public function saveTranslation(string $locale, array $data, bool $isOriginal = false, bool $isAutoTranslated = false)
    {
        $model = $this->getTranslationModel();
        $foreignKey = $this->getTranslationForeignKey();

        $translation = (new $model)
            ->where($foreignKey, $this->id)
            ->where('locale', $locale)
            ->find();

        if (!$translation) {
            $translation = new $model;
            $translation->$foreignKey = $this->id;
            $translation->locale = $locale;
        }

        // 设置翻译字段
        foreach ($this->getTranslatableFields() as $field) {
            if (isset($data[$field])) {
                $translation->$field = $data[$field];
            }
        }

        $translation->is_original = $isOriginal ? 1 : 0;
        $translation->is_auto_translated = $isAutoTranslated ? 1 : 0;

        if ($isAutoTranslated) {
            $translation->translated_at = date('Y-m-d H:i:s');
        }

        $translation->save();

        return $translation;
    }

    /**
     * 批量附加翻译到列表（带降级逻辑）
     * 降级顺序：请求语言 → en-us → 原始值
     * @param array $items 项目列表
     * @param string|null $locale 语言代码
     * @return array
     */
    public static function appendTranslations(array $items, ?string $locale = null): array
    {
        if (empty($items)) {
            return $items;
        }

        $locale = $locale ?? Lang::getLangSet();
        $instance = new static;
        $model = $instance->getTranslationModel();
        $foreignKey = $instance->getTranslationForeignKey();
        $translatable = $instance->getTranslatableFields();

        // 获取所有ID
        $ids = array_column($items, 'id');

        // 批量查询请求语言的翻译
        $translations = (new $model)
            ->where($foreignKey, 'in', $ids)
            ->where('locale', $locale)
            ->column('*', $foreignKey);

        // 如果请求的不是 en-us，也查询 en-us 作为降级
        $fallbackTranslations = [];
        if ($locale !== 'en-us') {
            $fallbackTranslations = (new $model)
                ->where($foreignKey, 'in', $ids)
                ->where('locale', 'en-us')
                ->column('*', $foreignKey);
        }

        // 附加翻译到项目
        foreach ($items as &$item) {
            $id = is_array($item) ? $item['id'] : $item->id;

            foreach ($translatable as $field) {
                $value = null;

                // 优先使用请求语言的翻译
                if (isset($translations[$id][$field]) && !empty($translations[$id][$field])) {
                    $value = $translations[$id][$field];
                }
                // 降级到 en-us
                elseif (isset($fallbackTranslations[$id][$field]) && !empty($fallbackTranslations[$id][$field])) {
                    $value = $fallbackTranslations[$id][$field];
                }
                // 保持原始值（使用 getData 避免 property not exists 异常）
                else {
                    if (is_array($item)) {
                        $value = $item[$field] ?? '';
                    } else {
                        try {
                            $value = $item->getData($field) ?? '';
                        } catch (\InvalidArgumentException $e) {
                            $value = '';
                        }
                    }
                }

                if (is_array($item)) {
                    $item[$field] = $value;
                } else {
                    // 直接设置属性，避免 data() 清空所有字段
                    $item->$field = $value;
                }
            }
        }

        return $items;
    }
}
