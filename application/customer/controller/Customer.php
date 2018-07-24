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

namespace app\customer\controller;

use controller\BasicAdmin;
use think\Db;
use service\DataService;
use service\ToolsService;
use service\RegionService;
use service\UserService;

/**
 * 系统客户管理控制器
 * Class User
 * @package app\admin\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2017/02/15 18:12
 */
class Customer extends BasicAdmin
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'Customer';

    /**
     * 客户列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function index()
    {
        $this->title = '客户管理';
        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['cust_name', 'concat_name', 'concat_phone', 'cust_type', 'region_code'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        $data = $db
            ->field('c.*, u.username, u.real_name')
            ->alias('c')
            ->where('c.region_code', 'like', session('user.region_code').'%')
            ->order('c.cust_name')
//            ->leftJoin('region r', 'c.region_code = r.region_code')
            ->leftJoin('user u', 'c.sales_id = u.id')
        ;

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
        return $this->_form($this->table, 'form');
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
     * 表单数据默认处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _form_filter(&$data)
    {
        if ($this->request->isPost()) {
            if (isset($data['id'])) {
                unset($data['cust_name']);
            } elseif (Db::name($this->table)->where(['cust_name' => $data['cust_name']])->count() > 0) {
                $this->error('客户账号已经存在，请使用其它账号！');
            }
        } else {
            $this->assign([
                'cates' => RegionService::regionTable($data), // 全部区域列表
                'sales'  => UserService::saleses(), // 客户所属业务员列表,
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
            $this->success("客户删除成功！", '');
        }
        $this->error("客户删除失败，请稍候再试！");
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("客户禁用成功！", '');
        }
        $this->error("客户禁用失败，请稍候再试！");
    }

    /**
     * 启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("客户启用成功！", '');
        }
        $this->error("客户启用失败，请稍候再试！");
    }

}
