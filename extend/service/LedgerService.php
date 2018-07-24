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
 * 台账
 * Class RegionService
 */
class LedgerService
{
    public static $table = 'Ledger';

    /**
     * 检查客户当时台账日期是否允许录入台账
     * @param $cust_id
     * @param $ledger_date
     * @return bool
     */
    public static function check_add_ledger($cust_id, $ledger_date){
        $form_ledger_date = date('Y-m-d', strtotime($ledger_date));
        // 检查台账日期
        $ledger_count = Db::name(self::$table)
            ->field('ledger_date')
            ->where([
                'cust_id' => $cust_id,
            ])
            ->count()
        ;

        if ($ledger_count > 0){ // 不是首次录入、结转台账
            $cur_ledger_date = date('Y-m-d', strtotime($form_ledger_date));
            $last_ledger_date = date('Y-m-d', strtotime('-1 Month',strtotime($form_ledger_date)));
            $check_ledger = Db::name(self::$table)
                ->field('ledger_date')
                ->where([
                    'cust_id' => $cust_id,
                    'ledger_date' => $last_ledger_date,
                ])
                ->where('status', '<>', 2)
                ->whereOr('ledger_date', $cur_ledger_date)
                ->find()
            ;
            if ($check_ledger) return false;
        }

        return true;
    }

    /**
     * 取台账汇总记录
     * @param $cust_id
     * @param $ledger_date
     * @param $ledger_type 0:当月，1：上月
     */
    public static function get_ledger($cust_id, $ledger_date, $is_last_month = 0){
        $ledger_date = $is_last_month == 0
            ? date('Y-m-d', strtotime($ledger_date))
            : date('Y-m-d', strtotime('-1 Month', strtotime($ledger_date)));
        $ledger = Db::name(self::$table)
            ->where([
                'cust_id' => $cust_id,
                'ledger_date' => $ledger_date,
            ])
            ->find();

        return $ledger;
    }

    /**
     * 取最后一个月台账汇总记录
     * @param $cust_id
     */
    public static function get_before_ledger($cust_id){
        $ledger = Db::name(self::$table)
            ->where([
                'cust_id' => $cust_id,
            ])
            ->order('ledger_date desc')
            ->find();

        return $ledger;
    }

    /**
     * 取上月（如没有，则取最后一个月）台账汇总记录
     * @param $cust_id
     */
    public static function get_last_ledger($cust_id, $ledger_date, $is_last_month = 0){
        $ledger = self::get_ledger($cust_id, $ledger_date, $is_last_month);
        if ($ledger == null){
            $first_ledger = Db::name(self::$table)
                ->where('cust_id', $cust_id)
                ->where('ledger_date', '<', $ledger_date)
                ->find()
            ;
            if ($first_ledger) $ledger = self::get_before_ledger($cust_id);
        }

        return $ledger;
    }


    /**
     * 检查客户在汇总表中是否为首条记录
     */
    public static function check_first_ledger($cust_id){
        $count = Db::name(self::$table)->where([
            'cust_id' => $cust_id,
        ])->count();

        return $count;
    }

    /**
     * 取台账汇总记录，联合客户表和用户表
     * @param $cust_id
     * @param $ledger_date
     */
    public static function get_ledger_header($cust_id, $ledger_date){
        $ledger_date = date('Y-m-d', strtotime($ledger_date));
        $ledger = Db::name(self::$table)
            ->field('l.*, c.cust_name, c.cust_type, c.sales_id, c.addr, u.real_name')
            ->alias('l')
            ->where([
                'l.cust_id' => $cust_id,
                'l.ledger_date' => $ledger_date,
            ])
            ->leftJoin('customer c', 'l.cust_id=c.id')
            ->leftJoin('user u', 'c.sales_id=u.id')
            ->find();
        return $ledger;
    }

    /**
     * 添加台账汇总
     * @param $ledger
     * @return int|string
     */
    public static function add($ledger){
        // 检查台账明细是否存在
        $ledger['ledger_date'] = date('Y-m-d', strtotime($ledger['ledger_date']));
        $ledger_detail_exists = LedgerDetailService::check_ledger_detail_exists($ledger['cust_id'], $ledger['ledger_date']);
        if (!$ledger_detail_exists){ // 台账明细不存在，则插入一条空台账明细记录
            $ledger_detail = [
                'cust_id' => $ledger['cust_id'],
                'ledger_date' => $ledger['ledger_date'],
                'ledger_type_id' => 3,
                'receipt_refund' => 0,
            ];
            $ledger_detail_add = LedgerDetailService::add_ledger_detail($ledger_detail);
            if (!$ledger_detail_add) return false;
        }
        return Db::name('ledger')->insert($ledger);
    }

    /**
     * 更新台账汇总
     * @param $ledger
     * @return int|string
     */
    public static function edit($ledger){
        return Db::name('ledger')->update($ledger);
    }

    /**
     * 插入或更新台账汇总数据
     * @param $cust_id
     * @param $ledger_date
     * @return int|string
     */
    public static function update($cust_id, $ledger_date){
//        return;

        $ledger = self::get_ledger($cust_id, $ledger_date);
        $ledger_detail = LedgerDetailService::get_ledger_detail($cust_id, $ledger_date);
        $receipt_refund = LedgerDetailService::calc_receipt_refund($ledger_detail);
        $amount = LedgerDetailService::calc_amount($ledger_detail);

        $last_ledger = LedgerService::get_ledger($cust_id, $ledger_date, 1);
//        var_dump($receipt_refund, $amount);exit;

        if ($ledger){ // 台账汇总记录存在，则更新
            $ledger['last_carry_down'] = isset($last_ledger['cust_balance']) ? $last_ledger['cust_balance'] : 0;
            $ledger['receipt_refund'] = $receipt_refund;
            $ledger['amount'] = $amount;
            $ledger['cust_balance'] = $ledger['last_carry_down'] + $ledger['amount'] + $ledger['receipt_refund'];

            return self::edit($ledger);
        }
        else{ // 台账汇总记录不存在，则插入
            $ledger['last_carry_down'] = isset($last_ledger['cust_balance']) ? $last_ledger['cust_balance'] : 0;
            $ledger['receipt_refund'] = $receipt_refund;
            $ledger['amount'] = $amount;
            $ledger['cust_balance'] = $ledger['last_carry_down'] + $ledger['amount'] + $ledger['receipt_refund'];
            $ledger['cust_id'] = $cust_id;
            $ledger['ledger_date'] = $ledger_date;
            $ledger['status'] = 0;

            return self::add($ledger);
        }
    }
}