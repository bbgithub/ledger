<?php

// +----------------------------------------------------------------------
// | 台账管理系统-Ledger
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | QQ:15516026
// +----------------------------------------------------------------------

namespace service;

use think\Db;
use think\db\Query;

/**
 * 操作日志服务
 * Class LogService
 * @package service
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2017/03/24 13:25
 */
class LogService
{

    /**
     * 获取数据操作对象
     * @return Query
     */
    protected static function db()
    {
        return Db::name('Log');
    }

    /**
     * 写入操作日志
     * @param string $action
     * @param string $content
     * @return bool
     */
    public static function write($action = '行为', $content = "内容描述", $username = '', $detail = '')
    {
        $request = app('request');
        $node = strtolower(join('/', [$request->module(), $request->controller(), $request->action()]));
        $username = $username ? $username : session('user.username') . '';
        $data = [
            'ip'       => $request->ip(),
            'node'     => $node,
            'action'   => $action,
            'content'  => $content,
            'username' => $username,
            'detail'   => $detail,
        ];
        return self::db()->insert($data) !== false;
    }

}
