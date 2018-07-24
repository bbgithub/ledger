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
use service\LogService;
use think\exception\HttpResponseException;

/**
 * 台账明细管理
 * Class Brand
 * @package app\store\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2018/06/08 14:43
 */
class LedgerDetail extends BasicAdmin
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
        if ($this->request->isGet()){ // 台账明细列表
            $this->title = '台账明细';
            list($get, $db) = [$this->request->get(), Db::name($this->table)->alias('ld')];
            foreach (['cust_id', 'cust_type', 'ledger_type_id'] as $key) {
                (isset($get[$key]) && $get[$key] !== '') && $db->whereLike('ld.'.$key, "%{$get[$key]}%");
            }

            if ((isset($get['ledger_date']) && $get['ledger_date'] === '') || (isset($get['cust_id']) && $get['cust_id'] === '')){
                $this->error('台账日期和客户必选');
            }

            if (isset($get['ledger_date']) && $get['ledger_date']){ // 搜索时:台账日期、客户必选
                $ld = preg_split('/[\s\+]-[\s\+]/', $get['ledger_date']);
                $start = $ld[0];
                $end = $ld[1];
//                list($start, $end) = explode(' - ', $get['ledger_date']);
                $start_day = date('Y-m-d', strtotime($start));
                $end_day = date('Y-m-d', strtotime($end));
                $db->whereBetween('ld.ledger_date', "$start_day, $end_day");

                $months = round((strtotime($end_day) - strtotime($start_day)) / 2592000) + 1;

                $ledger = [];
                $cur_ledger_date = $start_day;
                for($i=1; $i<=$months; $i++){
                    $ledger[$cur_ledger_date] = LedgerService::get_ledger($get['cust_id'], $cur_ledger_date);
                    $cur_ledger_date = date('Y-m-d', strtotime(' +1 months', strtotime($cur_ledger_date)));
                }

                $customer = CustomerService::customer($get['cust_id']);

                $this->assign([
                    'ledger'  => $ledger,
                    'customer'  => $customer,
                ]);
            }
            else{ // 首页
                $db->where('1','0');
            }

            // 搜索：台账日期、客户必选
            $db->where('ld.cust_region_code', 'like', session('user.region_code').'%')
                ->order('ld.ledger_date desc, ld.order_no');

            if (isset($get['status']) && $get['status'] !== ''){
                $db
                    ->field('ld.*, l.status')
                ;
                if ($get['status'] == 0){
                    $db
                        ->leftJoin('ledger l', 'l.ledger_date=ld.ledger_date AND l.cust_id=ld.cust_id')
                        ->whereRaw('l.status=0 OR ISNULL(l.status)')
                    ;
                }
                else{
                    $db
                        ->join('ledger l', 'l.status='.$get['status'].' AND l.ledger_date=ld.ledger_date AND l.cust_id=ld.cust_id')
                    ;
                }
            }

//            echo '<pre>';
//            var_dump($db->select());
//            exit;

            $data = parent::_list($db);

            return $data;
        }
    }

    /**
     * 数据处理
     * @param array $data
     */
    protected function _data_filter(&$data)
    {
        $ledger_detail = [];

        foreach($data as $k => $v){
            if (isset($v['ledger_date'])){
                $ledger_detail[$v['ledger_date']][] = $v;
            }
            else{
                break;
            }
        }

        $result = Db::name('sales_mode')->select();
        $this->assign([
            'custs'  => CustomerService::customers(), // 客户列表
            'sales_modes'  => SalesModeService::salesModes(), // 销售方式列表
            'cates'  => $result,
            'ledger_detail'  => $ledger_detail,
        ]);
    }

    /**
     * 添加台账明细
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function add()
    {
        $this->title = '添加台账明细';
        $this->assign('title', $this->title);
        if ($this->request->isPost()){ // 添加台账明细
            $post = $this->request->post();
//            echo '<pre>';
//            var_dump($post);
//            exit;
            $post['ledger_date'] = date('Y-m-d', strtotime($post['ledger_date']));
            /**
             * 汇总表中查询当前客户、当前台账日期唯一记录
             *      存在，则检查状态
             *          状态为未冻结
             *              新增台账记录
             *          状态为已冻结或已审核
             *              禁止操作，退出
             *      不存在，则检查上一个月记录
             *          有记录
             *              状态为已审核
             *                  新增台账记录
             *              状态为未冻结或已冻结
             *                  禁止操作，退出
             *          无记录
             *              检查是否为客户的首次冻结提交记录
             *                  是
             *                      新增台账记录
             *                  否
             *                      禁止操作，退出
             */
            $ledger = LedgerService::get_ledger($post['cust_id'], $post['ledger_date']);
            if ($ledger){ // 存在，则检查状态
                if ($ledger['status'] == 0){ // 新增台账数据
                    try {
                        if (LedgerDetailService::add_ledger_detail($post)) $this->success('添加台账成功', '');
                        $this->error('添加台账失败', '');
                    } catch (HttpResponseException $exception) {
                        return $exception->getResponse();
                    } catch (\Exception $e) {
                        $this->error($e->getMessage());
                    }
                }
                else{
                    $this->error('当前月台账已冻结或已审核，不可添加台账');
                }
            }
            else{ // 不存在，则检查上一个月记录
                $last_ledger = LedgerService::get_ledger($post['cust_id'], $post['ledger_date'], 1);
                if ($last_ledger){ // 有记录
                    if ($last_ledger['status'] == 2){ // 状态为已审核，新增台账记录
                        try {
                            if (LedgerDetailService::add_ledger_detail($post)) $this->success('添加台账成功', '');
                            $this->error('添加台账失败', '');
                        } catch (HttpResponseException $exception) {
                            return $exception->getResponse();
                        } catch (\Exception $e) {
                            $this->error($e->getMessage());
                        }
                    }
                    else{ // 状态为未冻结或已冻结,禁止操作，退出
                        $this->error('当前月的上月台账未审核，不可添加台账');
                    }
                }
                else{ // 无记录
                    if (LedgerService::check_first_ledger($post['cust_id']) == 0){ // 客户在汇总表为首条记录，新增台账记录
                        try {
                            if (LedgerDetailService::add_ledger_detail($post)) $this->success('添加台账成功', '');
                            $this->error('添加台账失败', '');
                        } catch (HttpResponseException $exception) {
                            return $exception->getResponse();
                        } catch (\Exception $e) {
                            $this->error($e->getMessage().$e->getCode());
                        }
                    }
                    else{ // 非首条记录，禁止操作，退出
                        $this->error('未查到当前月的上月台账，不可添加当前月台账');
                    }
                }
            }
        }
        else{ // 显示提交台账的页面
            return $this->_form($this->table, 'form');
        }
    }

    /**
     * 编辑台账明细
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function edit()
    {
        $this->title = '编辑台账明细';
        $this->assign('title', $this->title);

        if ($this->request->isPost()){
            $post = $this->request->post();
            $post['ledger_date'] = date('Y-m-d', strtotime($post['ledger_date']));
            /**
             * 查询当前台账明细，检查汇总表状态
             *      未冻结
             *          提交修改
             *      已冻结或已审核
             *          拒绝修改
             */
            try {
                $ledger_detail_edit_flag = LedgerDetailService::check_edit($post['cust_id'], $post['ledger_date']);
                if (!$ledger_detail_edit_flag) $this->error('当前台账状态为已冻结或已审核，不可编辑');
                if ($rs = LedgerDetailService::edit_ledger_detail($post)) $this->success('更新台账成功', '');
                $this->error('更新台账失败', '');
            } catch (HttpResponseException $exception) {
                return $exception->getResponse();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            /**
             * 汇总表中查询当前客户、当前台账日期唯一记录
             *      存在，则检查状态
             *          状态为未冻结
             *              更新台账记录
             *          状态为已冻结或已审核
             *              禁止操作，退出
             *      不存在，则检查上一个月记录
             *          有记录
             *              状态为已审核
             *                  更新台账记录
             *              状态为未冻结或已冻结
             *                  禁止操作，退出
             *          无记录
             *              检查是否为客户的首次冻结提交记录
             *                  是
             *                      更新台账记录
             *                  否
             *                      禁止操作，退出
             */
//            $ledger = LedgerService::get_ledger($post['cust_id'], $post['ledger_date']);
//            if ($ledger['status'] == 1 || $ledger['status'] == 2){
//                $this->error('已冻结或已审核的台账明细不可修改');
//            }
//            if ($ledger){
//                if ($ledger['status'] == 0){ // 更新台账数据
//                    try {
//                        if (LedgerDetailService::edit_ledger_detail($post)) $this->success('更新台账成功', '');
//                        $this->error('更新台账失败', '');
//                    } catch (HttpResponseException $exception) {
//                        return $exception->getResponse();
//                    } catch (\Exception $e) {
//                        $this->error($e->getMessage());
//                    }
//                }
//                else{
//                    $this->error('上月未审核，不可更新台账');
//                }
//            }
//            else{
//                $last_ledger = LedgerService::get_ledger($post['cust_id'], $post['ledger_date'], 1);
//                if ($last_ledger){
//                    if ($last_ledger['status'] == 2){ // 更新台账记录
//                        try {
//                            if (LedgerDetailService::edit_ledger_detail($post)) $this->success('更新台账成功', '');
//                            $this->error('更新台账失败', '');
//                        } catch (HttpResponseException $exception) {
//                            return $exception->getResponse();
//                        } catch (\Exception $e) {
//                            $this->error($e->getMessage());
//                        }
//                    }
//                    else{
//                        $this->error('上月未审核，不可更新台账');
//                    }
//                }
//                else{
//                    if (LedgerService::check_first_ledger($post['cust_id']) == 0){ // 客户在汇总表为首条记录，新增台账记录
//                        try {
//                            if (LedgerDetailService::edit_ledger_detail($post)) $this->success('更新台账成功', '');
//                            $this->error('更新台账失败', '');
//                        } catch (HttpResponseException $exception) {
//                            return $exception->getResponse();
//                        } catch (\Exception $e) {
//                            $this->error($e->getMessage().$e->getCode());
//                        }
//                    }
//                    else{
//                        $this->error('未查到当前月的上月台账，不可更新当前月台账');
//                    }
//                }
//            }
        }
        else{
            return $this->_form($this->table, 'edit');
        }
    }

    private function get_profits(){
        $profit = Db::name('profit')
            ->field('content')
            ->find();
        return $profit['content'];
    }

    /**
     * AJAX提成参数
     */
    public function profits(){
        $data = [
            'code' => 0,
            'profit' => $this->get_profits(),
        ];

        echo json_encode($data);
        exit;
    }

    /**
     * 选择商品
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function goods()
    {
//        file_put_contents(__DIR__ .'/../../../runtime/log/ledger.log', 'a',FILE_APPEND);
//        echo 'sssss';
//        exit;

        if ($this->request->isAjax() && $this->request->isPost()){ // 选中商品，确定后
            $post = $this->request->post();
            $ids = explode(',', $post['ids']);
            $goods = Db::name('goods')
                ->field('g.*, s.sales_mode_name,s.tun_per_price')
                ->alias('g')
                ->where('g.id', 'in', $ids)
                ->leftJoin('sales_mode s', 's.id=g.sales_mode_id')
                ->order('g.goods_name')
                ->select();

//            $profit = Db::name('profit')
//                ->field('content')
//                ->find();

            $data = [
                'code' => 0,
                'goods' => $goods,
                'profit' => $this->get_profits(),
            ];

            echo json_encode($data);
            exit;

//            list($post, $data) = [$post, []];

//            var_dump($post, $data);exit;
//            foreach ($post['ids'] as $vo) {
//                if (!empty($vo['node'])) {
//                    $data['node'] = $vo['node'];
//                    $data[$vo['name']] = $vo['value'];
//                }
//            }
//            !empty($data) && DataService::save($this->table, $data, 'node');
//            $this->success('参数保存成功！', '');
//            $this->error('访问异常，请重新进入...');
//            echo '<pre>';
//            var_dump($this->request->post());exit;
//            $this->title = '添加台账';
//            $ids = $this->request->post('ids');
//            var_dump($ids);exit;
//            $this->success('恭喜, 数据保存成功!', '', '', 0);
//            return $this->_form($this->table, 'goods');
        }
        else{
            $this->title = '';
            list($get, $db) = [$this->request->get(), Db::name('goods')];

            $db
                ->field('g.*, s.sales_mode_name')
                ->alias('g')
                ->order('g.goods_name')
                ->leftJoin('sales_mode s', 'g.sales_mode_id = s.id')
                ->where('g.status', 1)
            ;

            foreach (['goods_name', 'goods_no'] as $key) {
                (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
            }
            if (
                (!isset($get['goods_name']) || empty($get['goods_name']))
                && (!isset($get['goods_no']) || empty($get['goods_no']))
            ) $db->cache('goods_all');

            return parent::_list($db, false);
        }
    }

    /**
     * 选择商品
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function goods2()
    {
        $result = Db::name('sales_mode')->select();
        $this->assign([
            'custs'  => CustomerService::customers(), // 客户列表
            'sales_modes'  => SalesModeService::salesModes(), // 销售方式列表
            'cates'  => $result,
        ]);


//        $this->title = '';
//        list($get, $db) = [$this->request->get(), Db::name('goods')];
//        foreach (['goods_name', 'goods_no', 'sales_mode_id'] as $key) {
//            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
//        }
//
//        $data = $db
//            ->field('g.*, s.sales_mode_name')
//            ->alias('g')
//            ->order('g.id desc')
//            ->leftJoin('sales_mode s', 'g.sales_mode_id = s.id')
//        ;
//
//        return $this->fetch('goods2', ['vo' => $data]);
//        return parent::_list($data);

//        exit('ssss');
        $dbQuery = $this->table;
        $tplFile = 'goods2';
        $pkField = '';
        $where = [];
        $extendData = [];

        $db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
        $pk = empty($pkField) ? ($db->getPk() ? $db->getPk() : 'id') : $pkField;
        $pkValue = $this->request->request(
            $pk,
            isset($where[$pk])
                ? $where[$pk]
                : (
            isset($extendData[$pk])
                ? $extendData[$pk]
                : null
            )
        );
        // 非POST请求, 获取数据并显示表单页面
//        var_dump($this->request->isPost());exit;
        if (!$this->request->isPost()) {
//            echo '<pre>';
//            var_dump($this->request->get());
//            exit;
            $vo = ($pkValue !== null) ? array_merge((array)$db->where($pk, $pkValue)->where($where)->find(), $extendData) : $extendData;
            if (false !== $this->_callback('_form_filter', $vo, [])) {
                return $this->fetch($tplFile, ['vo' => $vo]);
            }
            return $vo;
        }
        else{
        }
        // POST请求, 数据自动存库
//        $data = array_merge($this->request->post(), $extendData);
//        if (false !== $this->_callback('_form_filter', $data, [])) {
//            $result = DataService::save($db, $data, $pk, $where);
//            if (false !== $this->_callback('_form_result', $result, $data)) {
//                if ($result !== false) {
//                    $this->success('恭喜, 数据保存成功!', '', '', 0);
//                }
//                $this->error('数据保存失败, 请稍候再试!', '', '', 0);
//            }
//        }
//
//        return $this->_form($this->table, 'goods2');
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

    /**
     * 删除台账明细
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del()
    {
        $ledger = Db::name($this->table)
            ->alias('ld')
            ->field('ld.*, l.status')
            ->where('ld.id', $this->request->get('id'))
            ->leftJoin('ledger l', 'ld.cust_id=l.cust_id AND ld.ledger_date=l.ledger_date')
            ->find()
        ;

        if ($ledger['status'] == 1 || $ledger['status'] == 2){
            $this->error("已冻结或已审核的台账明细不可删除");
        }
        if (DataService::update($this->table)) {
            LogService::write(request()->url(), '删除台账明细', '', json_encode($ledger, JSON_FORCE_OBJECT));
            $this->success("台账明细删除成功！", '');
        }
        $this->error("台账明细删除失败，请稍候再试！");
    }

    /**
     * 台账明细禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("台账明细禁用成功！", '');
        }
        $this->error("台账明细禁用失败，请稍候再试！");
    }

    /**
     * 台账明细签禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("台账明细启用成功！", '');
        }
        $this->error("台账明细启用失败，请稍候再试！");
    }

}
