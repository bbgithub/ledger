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
use service\LedgerHistoryService;

/**
 * 台账明细
 */
class LedgerDetailService
{
    public static $table = 'LedgerDetail';

    /**
     * 取台账明细记录
     * @param $cust_id
     * @param $ledger_date
     */
    public static function get_ledger_detail($cust_id, $ledger_date){
        $ledger_date = date('Y-m-d', strtotime($ledger_date));
        $ledger_detail = Db::name(self::$table)
            ->where([
                'cust_id' => $cust_id,
                'ledger_date' => $ledger_date,
            ])
            ->select();

        return $ledger_detail;
    }

    /**
     * 检查是否有台账明细
     * @param $cust_id
     * @param $ledger_date
     * @return bool
     */
    public static function check_ledger_detail_exists($cust_id, $ledger_date){
        return count(self::get_ledger_detail($cust_id, $ledger_date)) > 0;
    }

    /**
     * 计算收退款总金额（收款为正，退款为负）
     * @param $ledger_detail
     */
    public static function calc_receipt_refund($ledger_detail){
        $receipt_refund = 0;
        foreach($ledger_detail as $v){
            if ($v['ledger_type_id'] == 4){
                $v['receipt_refund'] = -$v['receipt_refund'];
            }
            $receipt_refund += $v['receipt_refund'];
        }
        return $receipt_refund;
    }

    /**
     * 计算发退货总金额（发货为负，退货为正）
     * @param $ledger_detail
     */
    public static function calc_amount($ledger_detail){
        $amount = 0;
        foreach($ledger_detail as $v){
            if ($v['ledger_type_id'] == 1){
                $v['amount'] = -$v['amount'];
            }
            $amount += $v['amount'];
        }
        return $amount;
    }

    /**
     * 新增台账明细
     * @param $post
     * @param $customer
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function add_ledger_detail($post){
        $data = [];
        $customer = Db::name('customer')->where('id', $post['cust_id'])->find();
        if ($customer['sales_id']){
            $sales = Db::name('user')->where('id', $customer['sales_id'])->find();
            $post['sales_region_code'] = $sales['region_code'];
            $post['sales_region_name'] = RegionService::fullName($sales['region_code']);
            $post['sales_id'] = $customer['sales_id'];
            $post['sales_real_name'] = $sales['real_name'];
        }
        $post['cust_region_name'] = RegionService::fullName($customer['region_code']);
        if ($post['ledger_type_id'] == 1 || $post['ledger_type_id'] == 2){ // 发货、退货
            if (!isset($post['qty'])) throw new Exception('请选择商品');
            foreach($post['qty'] as $k => $v){
                $data[$k]['ledger_date'] = $post['ledger_date'];
                isset($post['sales_region_code']) ? $data[$k]['sales_region_code'] = $post['sales_region_code'] : null;
                isset($post['sales_region_name']) ? $data[$k]['sales_region_name'] = $post['sales_region_name'] : null;
                isset($post['sales_id']) ? $data[$k]['sales_id'] = $post['sales_id'] : null;
                isset($post['sales_real_name']) ? $data[$k]['sales_real_name'] = $post['sales_real_name'] : null;
                isset($post['order_no']) ? $data[$k]['order_no'] = $post['order_no'] : null;
                $data[$k]['cust_region_code'] = $customer['region_code'];
                $data[$k]['cust_region_name'] = $post['cust_region_name'];
                $data[$k]['cust_id'] = $post['cust_id'];
                $data[$k]['cust_name'] = $customer['cust_name'];
                $data[$k]['cust_type'] = $customer['cust_type'];
                $data[$k]['ledger_type_id'] = $post['ledger_type_id'];

                $data[$k]['goods_id'] = $post['goods_id'][$k];
                $data[$k]['goods_no'] = $post['goods_no'][$k];
                $data[$k]['goods_name'] = $post['goods_name'][$k];
                $data[$k]['tun_per_price'] = $post['tun_per_price'][$k];
                $data[$k]['qty'] = $post['qty'][$k];
                $data[$k]['unit_price'] = $post['unit_price'][$k];
                $data[$k]['amount'] = $post['amount'][$k];
                $data[$k]['trans_fee'] = $post['trans_fee'][$k];
                $data[$k]['sales_mode_id'] = $post['sales_mode_id'][$k];
                $data[$k]['sales_mode_name'] = $post['sales_mode_name'][$k];
                $data[$k]['spread_price'] = $post['spread_price'][$k];
//                $data[$k]['sys_spread_price'] = $post['sys_spread_price'][$k];
                $data[$k]['discount_price'] = $post['discount_price'][$k];
                $data[$k]['sys_discount_price'] = $post['sys_discount_price'][$k];
                $data[$k]['profit_rate'] = $post['profit_rate'][$k];
                $data[$k]['sys_profit_rate'] = $post['sys_profit_rate'][$k];
//                $data[$k]['profit_amount'] = $post['ledger_type_id'] == 2 ? (-$post['profit_amount'][$k]) : $post['profit_amount'][$k];
                $data[$k]['profit_amount'] = $post['profit_amount'][$k];
                $data[$k]['sys_profit_amount'] = $post['sys_profit_amount'][$k];
                $data[$k]['sales_fee'] = $post['sales_fee'][$k];
                $data[$k]['market_fee'] = $post['market_fee'][$k];
                $data[$k]['salt_spread_price'] = $post['salt_spread_price'][$k];
                $data[$k]['rebate'] = $post['rebate'][$k];
                $data[$k]['addr'] = $customer['addr'];
            }
        }
        elseif($post['ledger_type_id'] == 3 || $post['ledger_type_id'] == 4){ // 收款、退款
            $data[0]['ledger_date'] = $post['ledger_date'];
            isset($post['sales_region_code']) ? $data[0]['sales_region_code'] = $post['sales_region_code'] : null;
            isset($post['sales_region_name']) ? $data[0]['sales_region_name'] = $post['sales_region_name'] : null;
            isset($post['sales_id']) ? $data[0]['sales_id'] = $post['sales_id'] : null;
            isset($post['sales_real_name']) ? $data[0]['sales_real_name'] = $post['sales_real_name'] : null;
            isset($post['order_no']) ? $data[0]['order_no'] = $post['order_no'] : null;
            $data[0]['cust_region_code'] = $customer['region_code'];
            $data[0]['cust_region_name'] = $post['cust_region_name'];
            $data[0]['cust_id'] = $post['cust_id'];
            $data[0]['cust_name'] = $customer['cust_name'];
            $data[0]['cust_type'] = $customer['cust_type'];
            $data[0]['ledger_type_id'] = $post['ledger_type_id'];

//            $data[0]['receipt_refund'] = ($post['ledger_type_id'] == 3) ? (-$post['receipt_refund']) : $post['receipt_refund'];
            $data[0]['receipt_refund'] = $post['receipt_refund'];
        }
        else{
            throw new \Exception('台账类型无效');
        }

        Db::startTrans();
        try {
            Db::name(self::$table)->insertAll($data);
            LedgerService::update($post['cust_id'], $post['ledger_date']); // 添加、更新台账汇总
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        LedgerHistoryService::add($data, 0);
        return true;
    }

    /**
     * 修改台账明细
     * @param $post
     */
    public static function edit_ledger_detail($post){
        $data = [];
        if ($post['ledger_type_id'] == 1 || $post['ledger_type_id'] == 2 ){ // 发货、退货
            foreach($post as $k => $v){
                $data[$k] = (is_array($v)) ? $v[0] : $v;
            }
//            $data['profit_amount'] = $data['ledger_type_id'] == 2 ? (-$data['profit_amount']) : $data['profit_amount'];
        }
        else{ // 收款、退款
            $data = $post;
//            $data['receipt_refund'] = $data['ledger_type_id'] == 4 ? (-$data['receipt_refund']) : $data['receipt_refund'];
        }


        Db::startTrans();
        try {
            Db::name(self::$table)->update($data);
            LedgerService::update($post['cust_id'], $post['ledger_date']); // 添加、更新台账汇总
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

        LedgerHistoryService::add($data, 0, $post['id']);
        return true;
    }

    /**
     * 检查台账状态是否为未冻结
     * @param $cust_id
     * @param $ledger_date
     * @return bool
     * @throws Exception
     */
    public static function check_edit($cust_id, $ledger_date){
        $data = Db::name(self::$table)
            ->alias('ld')
            ->field('ld.id ld_id, l.status')
            ->join('ledger l', 'ld.cust_id=l.cust_id AND ld.ledger_date=l.ledger_date')
            ->where('ld.cust_id='.$cust_id.' AND ld.ledger_date="'.$ledger_date.'"')
            ->find();

//        if (!$data || !count($data)) throw new Exception('台账明细不存在');
        if ($data && ($data['status'] == 1 || $data['status'] == 2)) return false;

        return true;
    }
}