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

namespace service;

use think\Db;
use think\Exception;

/**
 * 用户
 */
class UserService
{
    public static $table = 'User';

    /**
     * 指定区域业务员列表
     */
    public static function saleses($region_code = ''){
        $region_code = $region_code ? $region_code : session('user.region_code');
        return Db::name(self::$table)
            ->field('id,real_name,username,role_id')
            ->where('role_id', 'in', [1, 2])
            ->where('region_code', 'in', function($query) use($region_code){
                $query->name('Region')
                    ->field('region_code')
                    ->whereLike('region_code', $region_code . '%');
            })
            ->order('real_name')
            ->select();
    }

    /**
     * 获取用户
     * @param string $id
     * @param string $token
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public static function get_user($id = '', $token = ''){
        $where = $id ? ['id' => $id] : [];
        $where = $token ? ['token' => $token] : $where;
        return Db::name(self::$table)->where($where)->find();
    }

    /**
     * 检查角色权限是否匹配
     * @param $fixed_role_id
     * @param string $id
     * @param string $token
     * @return bool
     */
    public static function check_auth(array $fixed_role_ids, $id = '', $token = ''){
        $where = $id ? ['id' => $id] : [];
        $where = $token ? ['token' => $token] : $where;
        $where['is_deleted'] = 0;
        $where['status'] = 1;
        $user = Db::name(self::$table)->where($where)->find();
        if (!$user) return false;

        return (in_array($user['role_id'], $fixed_role_ids)) ? true : false;
    }

    /**
     * 检查token是否存在
     * @param $token
     * @return bool
     */
    public static function check_token($token){
        $user = Db::name(self::$table)->where([
            'token' => $token,
            'is_deleted' => 0,
            'status' => 1,
        ])->find();
        return $user ? true : false;
    }

}