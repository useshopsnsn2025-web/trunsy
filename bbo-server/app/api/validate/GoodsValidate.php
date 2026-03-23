<?php
declare(strict_types=1);

namespace app\api\validate;

use think\Validate;

/**
 * 商品验证器
 */
class GoodsValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'categoryId' => 'require|integer',
        'title' => 'require|max:200',
        'description' => 'max:5000',
        'condition' => 'in:1,2,3,4,5',
        'price' => 'require|float|gt:0',
        'originalPrice' => 'float|egt:0',
        'currency' => 'in:USD,CNY,HKD,TWD,EUR,GBP',
        'stock' => 'integer|egt:0',
        'images' => 'require|array|min:1',
        'video' => 'url',
        'locationCountry' => 'max:50',
        'locationCity' => 'max:50',
        'shippingFee' => 'float|egt:0',
        'freeShipping' => 'boolean',
        'isNegotiable' => 'boolean',
    ];

    /**
     * 错误信息
     * @var array
     */
    protected $message = [
        'categoryId.require' => 'Category is required',
        'categoryId.integer' => 'Invalid category',
        'title.require' => 'Title is required',
        'title.max' => 'Title is too long',
        'description.max' => 'Description is too long',
        'condition.in' => 'Invalid condition',
        'price.require' => 'Price is required',
        'price.float' => 'Invalid price',
        'price.gt' => 'Price must be greater than 0',
        'originalPrice.float' => 'Invalid original price',
        'currency.in' => 'Invalid currency',
        'stock.integer' => 'Invalid stock',
        'images.require' => 'At least one image is required',
        'images.array' => 'Invalid images',
        'images.min' => 'At least one image is required',
        'video.url' => 'Invalid video URL',
        'shippingFee.float' => 'Invalid shipping fee',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['categoryId', 'title', 'description', 'condition', 'price', 'originalPrice', 'currency', 'stock', 'images', 'video', 'shippingFee'],
        'update' => ['categoryId', 'title', 'description', 'condition', 'price', 'originalPrice', 'currency', 'stock', 'images', 'video', 'shippingFee'],
    ];
}
