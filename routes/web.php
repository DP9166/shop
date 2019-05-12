<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', 'PagesController@root')->name('root');

Route::redirect('/', '/products')->name('root');

Auth::routes(['verify' => true]);

Route::get('products', 'ProductsController@index')->name('products.index');
Route::get('products/{product}', 'ProductsController@show')->name('products.show')->where(['product' => '[0-9]+']);

Route::group(['middleware' => ['auth']], function() {
    Route::get('user_addresses', 'UserAddressesController@index')->name('user_addresses.index');
    Route::get('user_addresses/create', 'UserAddressesController@create')->name('user_addresses.create');
    Route::post('user_addresses', 'UserAddressesController@store')->name('user_addresses.store');
    Route::get('user_addresses/{user_address}', 'UserAddressesController@edit')->name('user_addresses.edit');
    Route::put('user_addresses/{user_address}', 'UserAddressesController@update')->name('user_addresses.update');
    Route::delete('user_addresses/{user_address}', 'UserAddressesController@destroy')->name('user_addresses.destroy');

    // 收藏
    Route::post('products/{product}/favorite', 'ProductsController@favor')->name('products.favor');
    Route::delete('products/{product}/favorite', 'ProductsController@disfavor')->name('products.disfavor');

    Route::get('products/favorites', 'ProductsController@favorites')->name('products.favorites');

    // 购物车
    Route::get('cart', 'CartController@index')->name('cart.index');
    Route::post('cart', 'CartController@add')->name('cart.add');
    Route::delete('cart/{sku}', 'CartController@remove')->name('cart.remove')->where(['sku' => '[0-9]+']);

    // 订单
    Route::get('orders', 'OrdersController@index')->name('orders.index');
    Route::post('orders', 'OrdersController@store')->name('orders.store');
    Route::get('orders/{order}', 'OrdersController@show')->name('orders.show')->where(['order' => '[0-9]+']);

    Route::post('orders/{order}/received', 'OrdersController@recevied')->name('orders.received')->where(['order' => '[0-9]+']);

    Route::get('orders/{order}/review', 'OrdersController@review')->name('orders.review')->where(['order' => '[0-9]+']); // 评分界面
    Route::post('orders/{order}/review', 'OrdersController@sendReview')->name('orders.review.store'); // 评分API

    // 支付
    Route::get('payment/alipay/return', 'PaymentController@alipayReturn')->name('payment.alipay.return');
    Route::get('payment/{order}/alipay', 'PaymentController@payByAlipay')->name('payment.alipay')->where(['order' => '[0-9]+']);
    Route::get('payment/{order}/wechat', 'PaymentController@payByWechat')->name('payment.wechat')->where(['order' => '[0-9]+']);


});

Route::post('payment/alipay/notify', 'PaymentController@alipayNotify')->name('payment.alipay.notify'); // 阿里云服务端回调
Route::post('payment/wechat/notify', 'PaymentController@wechatNotify')->name('payment.wechat.notify'); // 微信服务端回调
