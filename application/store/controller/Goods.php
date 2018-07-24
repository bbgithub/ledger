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

namespace app\store\controller;

use controller\BasicAdmin;
use think\Db;
use service\DataService;
use service\ToolsService;
use service\RegionService;
use service\SalesModeService;
use yidas\phpSpreadsheet\Helper AS Excel;

/**
 * 系统商品管理控制器
 * Class User
 * @package app\admin\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2017/02/15 18:12
 */
class Goods extends BasicAdmin
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'Goods';

    /**
     * 商品列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function index($is_export = false)
    {
        $this->title = '商品管理';
        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['goods_name', 'goods_no', 'sales_mode_id'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        $data = $db
            ->field('g.*, s.sales_mode_name,s.tun_per_price')
            ->alias('g')
            ->order('g.goods_name')
            ->leftJoin('sales_mode s', 'g.sales_mode_id = s.id')
        ;

        if ($is_export) return $data->select(); // 导出数据
        $this->assign('url', http_build_query($this->request->get()));

//        echo '<pre>';
//        var_dump($data);
//        exit;

        return parent::_list($data);
    }

    /**
     * 添加
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function add()
    {
        return $this->_form($this->table, 'form');
    }

    /**
     * 编辑
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function edit()
    {
//        echo '<pre>';
//        var_dump($this->request->post());
//        exit;

        return $this->_form($this->table, 'form');
    }

    /**
     * 数据处理（index|list）
     * @param array $data
     */
    protected function _data_filter(&$data)
    {
        $result = Db::name('sales_mode')->select();
        $this->assign([
            'cates'  => $result,
        ]);
    }


    /**
     * 表单数据默认处理(add|edit)
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _form_filter(&$data)
    {
        if ($this->request->isPost()) {
            if (isset($data['id'])) {
//                unset($data['goods_no']);
                $goods = Db::name($this->table)->where(['id' => $data['id']])->find();
                if ($goods && $goods['goods_no'] != $data['goods_no'] && Db::name($this->table)->where(['goods_no' => $data['goods_no']])->count() > 0){
                    $this->error('商品编码已存在');
                }
            } elseif (Db::name($this->table)->where(['goods_no' => $data['goods_no']])->count() > 0) {
                $this->error('商品编码已存在');
            }
        } else {
            $this->assign([
                'cates'  => SalesModeService::salesModes(), // 销售方式列表
            ]);
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del()
    {
        if (DataService::update($this->table)) {
            $this->success("商品删除成功！", '');
        }
        $this->error("商品删除失败，请稍候再试！");
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("禁用成功！", '');
        }
        $this->error("商品禁用失败，请稍候再试！");
    }

    /**
     * 启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("商品启用成功！", '');
        }
        $this->error("商品启用失败，请稍候再试！");
    }

    /**
     * 导出
     */
    public function export(){
        $data = $this->index(true);
        $header = [
            'id' => 'ID',
            'goods_name' => '商品名',
            'goods_no' => '商品编码',
            'sales_mode_name' => '销售方式',
            'tun_per_price' => '标准吨价',
            'unit_price' => '单价',
            'spread_price' => '包材品种差价',
            'market_fee' => '市场费用',
            'salt_spread_price' => '原盐差价',
            'create_at' => '创建时间',
        ];

//        $keys = array_diff_key($out_data[0], $data[0]);
//        echo '<pre>';
//        var_dump($keys);
//        exit;

        $out_data = [];
        foreach($data as $k => $v){
            foreach($header as $k2 => $v2){
                if ($k2 == 'status' && $v2 == 0) continue;
                if (isset($header[$k2])) $out_data[$k][$k2] = $data[$k][$k2];
            }
        }
        Excel::newSpreadsheet()
            ->setSheet(0, '商品列表')
            ->addRow($header)
            ->addRows($out_data)
            ->output('商品列表');
    }

}
