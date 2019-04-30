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