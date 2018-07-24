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

namespace app\store\service;

use service\DataService;

/**
 * 会员数据初始化
 * Class MemberService
 * @package app\store\service
 */
class MemberService
{
    /**
     * 创建会员数据
     * @param array $data 会员数据
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function create($data)
    {
        return DataService::save('Member', $data, 'id');
    }
}