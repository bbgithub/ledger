<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | github开源项目：
// +----------------------------------------------------------------------

try {

    // 1. 手动加载入口文件
    include "../include.php";

    // 2. 准备公众号配置参数
    $config = include "./config.php";

    // 3. 创建接口实例
    $wechat = new \WeChat\User($config);

    // 4. 获取用户列表
    $result = $wechat->getUserList();

    var_export($result);

    // 5. 批量获取用户资料
    foreach (array_chunk($result['data']['openid'], 100) as $item) {
        $userList = $wechat->getBatchUserInfo($item);
        var_export($userList);
    }

} catch (Exception $e) {

    // 出错啦，处理下吧
    echo $e->getMessage() . PHP_EOL;

}