<?php
/**
 * Created by PhpStorm.
 * User: duanpei
 * Date: 2019-04-30
 * Time: 16:46
 */

/*
 * 将的那个强请求的路由名称转换为css类名称
 * */
function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}

// 内网穿透
function ngrok_url($routeName, $parameters = [])
{
    if (app()->environment('local') && $url = config('app.ngrok_url')) {
        return $url.route($routeName, $parameters, false);
    }

    return route($routeName, $parameters);
}

/*
 *  默认的精度为小数点后两位
 * */
function big_number($number, $scale = 2)
{
    return new \Moontoast\Math\BigNumber($number, $scale);
}
