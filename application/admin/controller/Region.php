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

namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use service\ToolsService;
use service\RegionService;

/**
 * 区域管理
 * Class Cate
 * @package app\store\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2018/06/08 14:43
 */
class Region extends BasicAdmin
{

    /**
     * 定义当前操作表名
     * @var string
     */
    public $table = 'Region';

    /**
     * 区域列表
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '区域管理';
        $db = Db::name($this->table)->where(['status' => '1']);
        return parent::_list($db->order('sort asc,id asc'), false);
    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_data_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['ids'] = join(',', ToolsService::getArrSubIds($data, $vo['id']));
        }
        $data = ToolsService::arr2table($data);
    }

    /**
     * 添加菜单
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        return $this->_form($this->table, 'form');
    }

    /**
     * 编辑菜单
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        return $this->_form($this->table, 'form');
    }

    /**
     * 表单数据前缀方法
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _form_filter(&$data)
    {
        if ($this->request->isGet()) {
            $this->assign([
                'cates' => RegionService::regionTable($data), // 全部区域列表
            ]);
        }
        elseif ($this->request->isPost()){
            $new_parent_region_code = $this->request->post('parent_region_code');
            if (
                (isset($data['id']) && substr($data['region_code'], 0, -2) != $new_parent_region_code) // 修改且改了上级区域
                || !isset($data['id']) // 添加
            ){
                $region = Db::name($this->table)
                    ->field('id')
                    ->where('region_code', $new_parent_region_code)
                    ->find();

                $data['pid'] = $region ? $region['id'] : 0;
                $data['region_code'] = RegionService::newRegionCode($new_parent_region_code);
            }
        }
    }

    /**
     * 删除区域
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del()
    {
        if (DataService::update($this->table)) {
            $this->success("区域删除成功！", '');
        }
        $this->error("区域失败，请稍候再试！");
    }

    /**
     * 区域禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("区域禁用成功！", '');
        }
        $this->error("区域禁用失败，请稍候再试！");
    }

    /**
     * 区域启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("区域启用成功！", '');
        }
        $this->error("区域启用失败，请稍候再试！");
    }

}
