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

/**
 * 客户
 * Class RegionService
 */
class CustomerService
{
    public static $table = 'Customer';

    /**
     * 客户列表
     * @param $regionCode
     */
    public static function customers($region_code = ''){
        $region_code = $region_code ? $region_code : session('user.region_code');
        $where = $region_code ? 'region_code REGEXP "^'.$region_code.'.*$"' : [];
        return Db::name(self::$table)
            ->field('id,cust_name,concat_name')
            ->whereRaw($where)
            ->order('cust_name')
            ->select();
    }

    /**
     * 客户
     * @param $regionCode
     */
    public static function customer($id){
        return Db::name(self::$table)
            ->alias('c')
            ->field('c.*, u.real_name')
            ->where('c.id', $id)
            ->leftJoin('user u', 'c.sales_id=u.id')
            ->find();
    }

    /**
     * 客户所属业务员
     * @param $customer_id
     */
    public static function salesByCustomer($customer_id){
        $data =  Db::name(self::$table)
            ->alias('c')
            ->field('c.id cust_id,c.sales_id,u.id user_id,u.username,u.real_name')
            ->where('id', $customer_id)
            ->leftJoin('User u', ['c.sales_id' => 'u.id'])
            ->order('u.real_name')
            ->find();

        return $data ? $data : [];
    }

    /**
     * 多个客户所属业务员，关联
     * @param $customer_id
     */
    public static function salesesByCustomers($customer_ids = []){
        $data =  Db::name(self::$table)
            ->alias('c')
            ->field('c.id,c.sales_id,c.cust_name,c.concat_name,c.cust_type,u.id user_id,u.username,u.real_name')
            ->where('c.id', 'in', $customer_ids)
            ->leftJoin('user u', 'c.sales_id = u.id')
            ->order('c.cust_name')
            ->select();

        return $data;
    }

}