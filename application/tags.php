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

namespace think;

use service\NodeService;
use service\ApiService;
use think\exception\HttpResponseException;

return [
    // 控制器开始前，进行权限检查
    'action_begin' => function () {
        $request = app('request');
        list($module, $controller, $action) = [$request->module(), $request->controller(), $request->action()];
        if ($module == 'api'){ // 接口
            // 排除权限检查路由
            $noExpose = [
                'user/login',
                'user/logout',
                'user/code',
            ];
            if (!in_array(strtolower($controller . '/' . $action), $noExpose)){ // 访问权限检查
                $token = $this->request->header('token');
                $user = Db::name('User')->where('token', $token)->find();
                // 检查TOKEN
                if (!$user){
                    ApiService::error('请登录', [], 101101);
                }
                // 检查用户权限
                $roleids = explode(',', $user['role_id']);
                if (!in_array(1, $roleids) && !in_array(2, $roleids)){
                    ApiService::error('没有权限', [], 101102);
                }
            }
        }
        else{ // 后台
            $node = NodeService::parseNodeStr("{$module}/{$controller}/{$action}");
            $info = Db::name('Node')->cache(true, 30)->where(['node' => $node])->find();
            $access = ['is_menu' => intval(!empty($info['is_menu'])), 'is_auth' => intval(!empty($info['is_auth'])), 'is_login' => empty($info['is_auth']) ? intval(!empty($info['is_login'])) : 1];
            // 登录状态检查
            if (!empty($access['is_login']) && !session('user')) {
                $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('@admin/login')];
                throw new HttpResponseException($request->isAjax() ? json($msg) : redirect($msg['url']));
            }
            // 访问权限检查
            if (!empty($access['is_auth']) && !auth($node)) {
                throw new HttpResponseException(json(['code' => 0, 'msg' => '抱歉，您没有访问该模块的权限！']));
            }
            // 模板常量声明
            app('view')->init(config('template.'))->assign(['classuri' => NodeService::parseNodeStr("{$module}/{$controller}")]);

        }
    },
];
