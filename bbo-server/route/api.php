<?php
/**
 * API 路由配置
 * 注意：多应用模式下，此文件不生效，请使用 app/api/route/route.php
 */
use think\facade\Route;

// API 路由组
Route::group('api', function () {

    // 公开路由（无需认证）
    Route::group('', function () {
        // 认证
        Route::post('auth/register', 'api/Auth@register');
        Route::post('auth/login', 'api/Auth@login');
        Route::post('auth/login/social', 'api/Auth@socialLogin');
        Route::post('auth/refresh', 'api/Auth@refresh');

        // 商品（公开）
        Route::get('goods', 'api/Goods@index');
        Route::get('goods/:id', 'api/Goods@read');

        // 分类
        Route::get('categories', 'api/Category@index');

        // 系统
        Route::get('system/languages', 'api/System@languages');
        Route::get('system/config', 'api/System@config');
        Route::get('system/exchange-rates', 'api/System@exchangeRates');

    })->middleware([
        \app\api\middleware\Language::class,
    ]);

    // 需要认证的路由
    Route::group('', function () {
        // 认证
        Route::post('auth/logout', 'api/Auth@logout');

        // 用户
        Route::get('user/profile', 'api/User@profile');
        Route::put('user/profile', 'api/User@updateProfile');
        Route::put('user/language', 'api/User@updateLanguage');
        Route::get('user/addresses', 'api/User@addresses');
        Route::post('user/addresses', 'api/User@addAddress');
        Route::put('user/addresses/:id', 'api/User@updateAddress');
        Route::delete('user/addresses/:id', 'api/User@deleteAddress');

        // 商品（需要认证）
        Route::post('goods', 'api/Goods@create');
        Route::put('goods/:id', 'api/Goods@update');
        Route::delete('goods/:id', 'api/Goods@delete');
        Route::post('goods/:id/like', 'api/Goods@like');

        // 用户收藏和历史
        Route::get('user/favorites', 'api/User@favorites');
        Route::get('user/history', 'api/User@history');

    })->middleware([
        \app\api\middleware\Language::class,
        \app\api\middleware\Auth::class,
    ]);

})->allowCrossDomain();
