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
use service\ToolsService;
use service\RegionService;
use service\LedgerDetailService;
use service\LedgerHistoryService;
use service\UserService;
use think\Db;
use WeOpen\Service;

/**
 * 台账审核
 * Class Brand
 * @package app\store\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2018/06/08 14:43
 */
class Ledger extends BasicAdmin
{

    /**
     * 定义当前操作表名
     * @var string
     */
    public $table = 'ledger';

    /**
     * 台账列表
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '台账审核';
        list($get, $db) = [$this->request->get(), Db::name($this->table)];

        foreach (['ledger_date'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "{$get[$key]}%");
        }

        $data = $db
            ->field('l.*, c.region_code, c.cust_name, c.cust_type, c.sales_id, u.real_name')
            ->alias('l')
            ->leftJoin('customer c', 'l.cust_id = c.id')
            ->leftJoin('user u', 'c.sales_id = u.id')
            ->where('c.region_code', 'like', session('user.region_code').'%')
            ->where('l.status', '<>', 0)
            ->order('l.status, l.ledger_date desc')
        ;

        $saleses = UserService::saleses();
        $this->assign([
            'saleses'  => $saleses,
        ]);

        (isset($get['region_code']) && $get['region_code'] !== '') && $data->where('c.region_code', 'like', $get['region_code'].'%');
        (isset($get['status']) && $get['status'] !== '') && $data->where('l.status', $get['status']);
        (isset($get['cust_name']) && $get['cust_name'] !== '') && $data->whereLike('c.cust_name', "%{$get['cust_name']}%");
        (isset($get['sales_id']) && $get['sales_id'] !== '') && $data->where('u.id', "{$get['sales_id']}");

        return parent::_list($data);
    }

    /**
     * 添加台账
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function add()
    {
        $this->title = '添加台账';
        $this->assign('title', $this->title);
        return $this->_form($this->table, 'form');
    }

    /**
     * 编辑台账
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function edit()
    {
        $this->title = '台账详情';
        $this->assign('title', $this->title);

        return $this->_form($this->table, 'form');
    }

    /**
     * 表单提交数据处理
     * @param array $data
     */
    protected function _form_filter($data)
    {
        if ($this->request->isPost()){

        }
        else{
//            var_dump($data['cust_id'],$data['ledger_date']);exit;
            $this->assign([
                'details' => LedgerDetailService::get_ledger_detail($data['cust_id'], $data['ledger_date']),
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
            list($base, $spm, $url) = [url('@admin'), $this->request->get('spm'), url('ledger/ledger/index')];
            $this->success('操作成功！', "{$base}#{$url}?spm={$spm}");
        }
    }

    /**
     * 数据处理
     * @param array $data
     */
    protected function _data_filter(&$data)
    {
        $result = RegionService::regionTable($data);
        $this->assign([
            'cates'  => ToolsService::arr2table($result),
        ]);
    }

    /**
     * 审核
     */
    public function audit(){
        $get = $this->request->get();
        $post = $this->request->post();
        if (isset($post['id']) && isset($post['field']) && $post['field'] == 'audit'){ // 批量审核、拒绝
            if ($post['value'] != 0 && $post['value'] != 2){
                $this->error('审核状态错误');
            }
            $status = $post['value'];
            $rs = Db::name($this->table)
                ->where('id', 'in', $post['id'])
                ->where('status', 1)
                ->update(['status' => $status]);
//            if ($status == 2){
//                $rs = Db::name($this->table)
//                    ->where('id', 'in', $post['id'])
//                    ->where('status', 1)
//                    ->update(['status' => $status]);
//            }
//            else{
//                $rs = Db::name($this->table)
//                    ->where('id', 'in', $post['id'])
//                    ->where('status', 1)
//                    ->delete();
//            }
            if ($rs){
                LedgerHistoryService::add($post, $post['value'], $post['id']);
                $this->success('审核操作成功', '');
            }
            $this->error('审核操作失败，请检查原审核状态');
        }
//        else{ // 单条审核、拒绝
//            if ($get['status'] != 0 && $get['status'] != 2){
//                $this->error('审核状态错误');
//            }
//            $rs = Db::name($this->table)
//                ->where([
//                    'id' => $get['id'],
//                    'status' => 1,
//                ])
//                ->update(['status' => $get['status']]);
//            if ($rs){
//                LedgerHistoryService::add($get, $get['status'], $get['id']);
//                $this->success('审核操作成功', '');
//            }
//            $this->error('审核操作失败，请检查原审核状态');
//        }
    }

    /**
     * 删除台账
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del()
    {
        if (DataService::update($this->table)) {
            $this->success("台账删除成功！", '');
        }
        $this->error("台账删除失败，请稍候再试！");
    }

    /**
     * 台账禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("台账禁用成功！", '');
        }
        $this->error("台账禁用失败，请稍候再试！");
    }

    /**
     * 台账启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("台账启用成功！", '');
        }
        $this->error("台账启用失败，请稍候再试！");
    }

}
