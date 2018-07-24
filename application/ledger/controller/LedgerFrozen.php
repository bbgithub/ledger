<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace app\ledger\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use service\ToolsService;
use service\RegionService;
use service\CustomerService;
use service\SalesModeService;
use service\UserService;
use service\LedgerService;
use service\LedgerDetailService;
use service\LedgerHistoryService;
use think\exception\HttpResponseException;

/**
 * 台账冻结管理
 * Class Brand
 * @package app\store\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2018/06/08 14:43
 */
class LedgerFrozen extends BasicAdmin
{

    /**
     * 定义当前操作表名
     * @var string
     */
    public $table = 'ledger_detail';

    /**
     * 台账明细列表
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if ($this->request->isPost()){ // 冻结台账
            $post = $this->request->post();

            if (!$post['id']) $this->error('请选择要冻结的台账');

            if ($post['field'] == 1){ // 有台账明细数据
                $ledger_detail_row = Db::name($this->table)
                    ->where('id', 'in', $post['id'])
                    ->select();
                if (!$ledger_detail_row || !count($ledger_detail_row)) $this->error('没有找到台账信息');
            }
            else{ // 无台账明细数据
                $ledger_detail_row = Db::name('customer')
                    ->where('id', 'in', $post['id'])
                    ->select();
                if (!$ledger_detail_row || !count($ledger_detail_row)) $this->error('没有找到客户信息');
                foreach($ledger_detail_row as $k => $v){
                    $ledger_detail_row[$k]['cust_id'] = $v['id'];
                    $ledger_detail_row[$k]['ledger_date'] = $post['value'];
                }
            }

            foreach($ledger_detail_row as $v){
                $v['ledger_date'] = date('Y-m-d', strtotime($v['ledger_date']));
                /**
                 * 汇总表中查询当前客户、当前台账日期唯一记录
                 *      存在，则检查状态
                 *          状态为未冻结
                 *              修改数据，修改状态为已冻结
                 *          状态为已冻结或已审核
                 *              禁止操作，退出
                 *      不存在，则检查上一个月记录
                 *          有记录
                 *              状态为已审核
                 *                  新增冻结记录
                 *              状态为未冻结或已冻结
                 *                  禁止操作，退出
                 *          无记录
                 *              检查是否为客户的首次冻结提交记录
                 *                  是
                 *                      新增冻结记录
                 *                  否
                 *                      禁止操作，退出
                 */
                $ledger = LedgerService::get_ledger($v['cust_id'], $v['ledger_date']);
                if ($ledger){ // 汇总表中查询当前客户、当前台账日期唯一记录：存在，则检查状态
                    if ($ledger['status'] == 0){ // 状态为未冻结：修改数据，修改状态为已冻结
                        // 计算收退款与发退货金额
                        $ledger_detail = LedgerDetailService::get_ledger_detail($v['cust_id'], $v['ledger_date']);
                        $receipt_refund = LedgerDetailService::calc_receipt_refund($ledger_detail);
                        $amount = LedgerDetailService::calc_amount($ledger_detail);
                        // 上月台账审核记录
                        $last_ledger = LedgerService::get_ledger($v['cust_id'], $v['ledger_date'], 1);

                        $ledger['receipt_refund'] = $receipt_refund;
                        $ledger['amount'] = $amount;
                        $ledger['last_carry_down'] = $last_ledger['cust_balance'] ? $last_ledger['cust_balance'] : 0;
                        $ledger['cust_balance'] = $ledger['last_carry_down'] + $ledger['amount'] + $ledger['receipt_refund'];
                        $ledger['status'] = 1;

                        if (LedgerService::edit($ledger)){
                            LedgerHistoryService::add($ledger_detail, 1, $ledger['id']);
//                            $this->success('台账冻结成功', '');
                        }
                        else{
                            $this->error('台账冻结失败（台账日期：'.$v['ledger_date'].'，客户名：'.$v['cust_name'].')');;
                        }
                    }
                    else{ // 状态为已冻结或已审核：禁止操作，退出
                        $this->error('当前记录已冻结或已审核，不可再冻结（台账日期：'.$v['ledger_date'].'，客户名：'.$v['cust_name'].')');;
                    }
                }
                else{ // 汇总表中查询当前客户、当前台账日期唯一记录：不存在，则检查上一个月记录
                    $last_ledger = LedgerService::get_ledger($v['cust_id'], $v['ledger_date'], 1);
                    if ($last_ledger){ // 上一个月有记录
                        if ($last_ledger['status'] == 2){ // 状态为已审核：新增冻结记录
                            // 计算收退款与发退货金额
                            $ledger_detail = LedgerDetailService::get_ledger_detail($v['cust_id'], $v['ledger_date']);
                            $receipt_refund = LedgerDetailService::calc_receipt_refund($ledger_detail);
                            $amount = LedgerDetailService::calc_amount($ledger_detail);
                            // 上月台账审核记录
                            $last_ledger = LedgerService::get_ledger($v['cust_id'], $v['ledger_date'], 1);
                            $ledger['cust_id'] = $v['cust_id'];
                            $ledger['ledger_date'] = $v['ledger_date'];
                            $ledger['receipt_refund'] = $receipt_refund;
                            $ledger['amount'] = $amount;
                            $ledger['last_carry_down'] = $last_ledger['cust_balance'] ? $last_ledger['cust_balance'] : 0;
                            $ledger['cust_balance'] = $ledger['last_carry_down'] + $ledger['amount'] + $ledger['receipt_refund'];
                            $ledger['status'] = 1;

                            if ($ledger_add = LedgerService::add($ledger)){
                                LedgerHistoryService::add($ledger_detail, 0);
//                                $this->success('台账冻结成功', '');
                            }
                            else{
                                $this->error('台账冻结失败（台账日期：'.$v['ledger_date'].'，客户名：'.$v['cust_name'].')');;
                            }
                        }
                        else{ // 状态为未冻结或已冻结：禁止操作，退出
                            $this->error('当前月的上月台账未审核，不可冻结（台账日期：'.$v['ledger_date'].'，客户名：'.$v['cust_name'].')');
                        }
                    }
                    else{ // 上一个月无记录
                        if (LedgerService::check_first_ledger($v['cust_id']) == 0){ // 检查是否为客户的首次冻结提交记录：是，新增冻结记录
                            // 计算收退款与发退货金额
                            $ledger_detail = LedgerDetailService::get_ledger_detail($v['cust_id'], $v['ledger_date']);
                            $receipt_refund = LedgerDetailService::calc_receipt_refund($ledger_detail);
                            $amount = LedgerDetailService::calc_amount($ledger_detail);

                            $ledger['cust_id'] = $v['cust_id'];
                            $ledger['ledger_date'] = $v['ledger_date'];
                            $ledger['receipt_refund'] = $receipt_refund;
                            $ledger['amount'] = $amount;
                            $ledger['last_carry_down'] = $last_ledger['cust_balance'] ? $last_ledger['cust_balance'] : 0;
                            $ledger['cust_balance'] = $ledger['last_carry_down'] + $ledger['amount'] + $ledger['receipt_refund'];
                            $ledger['status'] = 1;

                            if ($ledger_add = LedgerService::add($ledger)){
                                LedgerHistoryService::add($ledger_detail, 0);
//                                $this->success('台账冻结成功', '');
                            }
                            else{
                                $this->error('台账冻结失败（台账日期：'.$v['ledger_date'].'，客户名：'.$v['cust_name'].')');
                            }
                        }
                        else{ // 检查是否为客户的首次冻结提交记录：否，禁止操作，退出
                            $this->error('上月台账没有数据，不可冻结（台账日期：'.$v['ledger_date'].'，客户名：'.$v['cust_name'].')');
                        }
                    }
                }
            }
            $this->success('台账冻结成功', '');
        }
        else{ // 台账冻结列表
            $this->title = '台账冻结';
            list($get, $db) = [$this->request->get(), Db::name($this->table)->alias('ld')];
            foreach (['ledger_date', 'cust_id'] as $key) {
                (isset($get[$key]) && $get[$key] !== '') && $db->whereLike('ld.'.$key, "%{$get[$key]}%");
                if (isset($get['ledger_date']) && $get['ledger_date']) $get['ledger_date'] = date('Y-m-d', strtotime($get['ledger_date']));
            }

            $db->where('ld.cust_region_code', 'like', session('user.region_code').'%');
            // 搜索：台账日期必选
            if (isset($get['ledger_date']) && $get['ledger_date']){
                if (isset($get['has_detail']) && $get['has_detail'] == 2){ // 无台账明细
                    if (isset($get['cust_id']) && $get['cust_id']){
                        $db = Db::name('customer')
                                ->alias('c')
                                ->field('c.*, u.real_name, ld.ledger_date')
                                ->where('c.id', $get['cust_id'])
                                ->leftJoin('user u', 'c.sales_id=u.id')
                                ->leftJoin('ledger_detail ld', 'c.id=ld.cust_id AND ld.ledger_date="'.$get['ledger_date'].'"')
                                ->whereNull('ld.ledger_date')
                                ;
                    }
                    else{
                        $where = 'c.region_code REGEXP "^' . session('user.region_code') . '.*$"';
                        $where = 'l.status=0 OR ISNULL(l.status) AND c.region_code REGEXP "^' . session('user.region_code') . '.*$"';
                        $db = Db::name('customer')
                            ->alias('c')
                            ->field('c.*, l.ledger_date, l.status, ld.cust_id, u.real_name')
                            ->leftJoin('ledger l', 'c.id=l.cust_id AND l.ledger_date="'.$get['ledger_date'].'"')
                            ->leftJoin('ledger_detail ld', 'c.id=ld.cust_id AND ld.ledger_date="'.$get['ledger_date'].'"')
                            ->leftJoin('user u', 'c.sales_id=u.id')
                            ->whereRaw($where)
                            ->whereNull('ld.cust_id')
                        ;

//                        echo '<pre>';
//                        $db->select();
//                        var_dump($db->select());
//                        exit;
                    }
                    $db->order('l.status');
                    return parent::_list($db);
                }
                else{ // 有台账明细
                    $get['ledger_date'] = date('Y-m-d', strtotime($get['ledger_date']));
                    $db
                        ->field('
                            ld.*,
                            l.status,
                            l.last_carry_down,
                            l.cust_balance,
                            SUM(IF(ld.ledger_type_id=1||ld.ledger_type_id=2, ld.amount, ld.receipt_refund)) as amount_total,
                            SUM(qty) as qty_total,
                            SUM(sales_fee) as sales_fee_total,
                            SUM(profit_amount) as profit_amount_total
                        ')
                        ->leftJoin('ledger l', 'l.ledger_date=ld.ledger_date AND l.cust_id=ld.cust_id')
                        ->group('ld.ledger_date, ld.cust_id')
                    ;

                    if (isset($get['status']) && $get['status'] !== '10'){
                        if ($get['status'] == 0){
                            $db->whereRaw('l.status=0 OR ISNULL(l.status)');
                        }
                        else{
                            $db->where('l.status', $get['status']);
                        }
                    }

                    $db->order('l.status');
//                    echo (Db::name($this->table)->getLastSql());exit;

//                        $db
//                            ->field('
//                            ld.*,
//                            l.status,
//                            SUM(IF(ld.ledger_type_id=1||ld.ledger_type_id=2, ld.amount, ld.receipt_refund)) as amount_total,
//                            SUM(qty) as qty_total,
//                            SUM(sales_fee) as sales_fee_total,
//                            SUM(profit_amount) as profit_amount_total
//                        ')
//                            ->leftJoin('ledger l', 'l.ledger_date=ld.ledger_date AND l.cust_id=ld.cust_id')
//                            ->whereRaw('l.status=0 OR ISNULL(l.status)')
//                            ->group('ld.ledger_date, ld.cust_id')
//                        ;
                }
            }
            else{
                $db->where('1',0);
            }

//            $data->select();
//            echo '<pre>';
//            var_dump(Db::name('ledger_detail')->getLastSql());
//            exit;

            return parent::_list($db);
        }
    }

    /**
     * 数据处理
     * @param array $data
     */
    protected function _data_filter(&$data)
    {
//        if ($this->request->get('has_detail') == 2){ // 无台账明细
//            foreach($data as $k => $v){
//                if ($v['cust_id']) unset($data[$k]);
//            }
//        }
//        echo '<pre>';
//        var_dump($data);
//        exit;

        $result = Db::name('sales_mode')->select();
        $this->assign([
            'custs'  => CustomerService::customers(), // 客户列表
            'sales_modes'  => SalesModeService::salesModes(), // 销售方式列表
            'cates'  => $result,
        ]);
    }

    /**
     * 表单提交数据处理
     * @param array $data
     */
    protected function _form_filter(&$data)
    {
        if ($this->request->isPost()) {
//            if (isset($data['id'])) {
//                unset($data['cust_name']);
//            } elseif (Db::name($this->table)->where(['cust_name' => $data['cust_name']])->count() > 0) {
//                $this->error('客户账号已经存在，请使用其它账号！');
//            }
        } else {
            // 取客户ID列表
            $customers = CustomerService::customers();
            $customer_ids = [];
            foreach($customers as $v){
                $customer_ids[] = $v['id'];
            }
            // 客户与业务员关系列表
            $customer_saleses = CustomerService::salesesByCustomers($customer_ids);
//            $ledger_date = isset($data['ledger_date']) ? $data['ledger_date'] : date('Y-m-d');
            $this->assign([
//                'customers'  => $customers, // 客户所属业务员列表
                'customer_saleses'  => $customer_saleses, // 客户与业务员关系列表
//                'ledger_date'  => $ledger_date,
            ]);
        }
    }

    /**
     * 添加成功回跳处理
     * @param bool $result
     */
    protected function _form_result($result)
    {
        if ($result !== false) {
            list($base, $spm, $url) = [url('@admin'), $this->request->get('spm'), url('store/sales_mode/index')];
            $this->success('数据保存成功！', "{$base}#{$url}?spm={$spm}");
        }
    }

}
