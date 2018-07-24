<?php

// +----------------------------------------------------------------------
// | 台账管理系统-Ledger
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | QQ:15516026
// +----------------------------------------------------------------------

/**
 * 模块路由及配置检测并加载
 * @include module/init.php
 * @author Anyon<zoujingli@qq.com>
 */
use think\facade\Route;

//Route::resource('api/user','api/User');
Route::rule('api/user/login', 'api/user/login');

foreach (scandir(env('app_path')) as $dir) {
    if ($dir[0] !== '.') {
        $filename = realpath(env('app_path') . "{$dir}/init.php");
        $filename && file_exists($filename) && include($filename);
    }
}

return [];