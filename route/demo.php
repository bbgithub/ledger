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

use think\facade\Route;

// 不执行下面的路由
return [];

/*  测试环境禁止操作路由绑定 */
Route::post('admin/user/pass', function () {
    return json(['code' => 0, 'msg' => '测试环境禁修改用户密码！']);
});
Route::post('admin/index/pass', function () {
    return json(['code' => 0, 'msg' => '测试环境禁修改用户密码！']);
});
Route::post('admin/config/index', function () {
    return json(['code' => 0, 'msg' => '测试环境禁修改系统配置操作！']);
});
Route::post('admin/config/file', function () {
    return json(['code' => 0, 'msg' => '测试环境禁修改文件配置操作！']);
});
Route::post('admin/menu/index', function () {
    return json(['code' => 0, 'msg' => '测试环境禁排序菜单操作！']);
});
Route::post('admin/menu/add', function () {
    return json(['code' => 0, 'msg' => '测试环境禁添加菜单操作！']);
});
Route::post('admin/menu/edit', function () {
    return json(['code' => 0, 'msg' => '测试环境禁编辑菜单操作！']);
});
Route::post('admin/menu/forbid', function () {
    return json(['code' => 0, 'msg' => '测试环境禁止禁用菜单操作！']);
});
Route::post('admin/menu/del', function () {
    return json(['code' => 0, 'msg' => '测试环境禁止删除菜单操作！']);
});
Route::post('wechat/config/index', function () {
    return json(['code' => 0, 'msg' => '测试环境禁止修改微信配置操作！']);
});
Route::post('admin/node/save', function () {
    return json(['code' => 0, 'msg' => '测试环境禁止修改节点数据操作！']);
});
Route::post('wechat/menu/edit', function () {
    return json(['code' => 0, 'msg' => '测试环境禁止修改微信菜单操作！']);
});
Route::get('wechat/menu/cancel', function () {
    return json(['code' => 0, 'msg' => '测试环境禁止删除微信菜单操作！']);
});
