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
use service\UserService;

/**
 * 区域
 * Class RegionService
 *
 */
class RegionService
{
    public static $table = 'Region';

    /**
     * 全部区域列表
     * @return array
     */
    private static function _regions($type = 'table'){
        // 区域列表
        if (session('user.username') == 'admin'){
            $_cates = (array)Db::name(self::$table)
                ->where(['status' => '1'])
                ->order('region_name')
                ->select();
        }
        else{
            $token = request()->param('token', '');
            $region_code = $token
                ? UserService::get_user('', $token)['region_code'] // API访问
                : UserService::get_user(session('user.id'))['region_code']; // 后台访问
            $_cates = (array)Db::name(self::$table)
                ->where(['status' => '1'])
                ->where('region_code', 'like', $region_code.'%')
                ->order('region_name')
                ->select();
        }
        if ($type == 'table'){
            $cates = ToolsService::arr2table($_cates);
        }
        else{
            $cates = ToolsService::arr2tree($_cates);
        }
        return $cates;
    }

    /**
     * 区域树
     * @param $vo
     * @return array
     */
    public static function regionTree(&$vo){
        $cates = self::_regions('tree');
        foreach ($cates as $key => &$cate) {
            if (isset($vo['pid'])) {
                $path = "-{$vo['pid']}-{$vo['id']}";
                if ($vo['pid'] !== '' && (stripos("{$cate['path']}-", "{$path}-") !== false || $cate['path'] === $path)) {
                    unset($cates[$key]);
                }
            }
        }

        return $cates;
    }

    /**
     * 区域树表
     * @param $vo
     * @return array
     */
    public static function regionTable(&$vo){
        $cates = self::_regions();

        foreach ($cates as $key => &$cate) {
            if (isset($vo['pid'])) {
                $path = "-{$vo['pid']}-{$vo['id']}";
                if ($vo['pid'] !== '' && (stripos("{$cate['path']}-", "{$path}-") !== false || $cate['path'] === $path)) {
                    unset($cates[$key]);
                }
            }
        }

        return $cates;
    }

    /**
     * 区域全名
     * @param $region_code
     */
    public static function fullName($region_code = ''){
        $region_code = empty($region_code) ? session('user.region_code') : $region_code;
        $len = intval(strlen($region_code) / 2);
        $regionFullName = '';
        for($i = 0; $i < $len; $i++){
            $curRegionCode = substr($region_code, 0, ($i+1) * 2);
            $region = Db::name(self::$table)
                ->field('region_name')
                ->where('region_code', $curRegionCode)
                ->find();
            if ($region){
                $delimiter = $i == 0 ? '' : ' → ';
                $regionFullName .= $delimiter . $region['region_name'];
            }
        }

        return $regionFullName;
    }

    /**
     * 用上级区域编码生成本级区域编码
     * @param $parent_region_code
     * @return mixed
     */
    public static function newRegionCode($parent_region_code){
        $region = Db::name(self::$table)
            ->field("count(region_code) as region_code_count, max(region_code) as region_code_max")
            ->whereRaw('region_code REGEXP "^'.$parent_region_code.'.{2}$"')
            ->find()
        ;

        $region_code = $region['region_code_count'] > 0
            ? (int)$region['region_code_max'] + 1
            : $parent_region_code . '10';

        return $region_code;
    }

}