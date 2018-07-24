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

namespace app\store\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

/**
 * 销售方式管理
 * Class Brand
 * @package app\store\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2018/06/08 14:43
 */
class SalesMode extends BasicAdmin
{

    /**
     * 定义当前操作表名
     * @var string
     */
    public $table = 'sales_mode';

    /**
     * 销售方式列表
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '销售方式';
        $get = $this->request->get();
        $db = Db::name($this->table);
        if (isset($get['sales_mode_name']) && $get['sales_mode_name'] !== '') {
            $db->whereLike('sales_mode_name', "%{$get['sales_mode_name']}%");
        }
        return parent::_list($db->order('id desc'));
    }

    /**
     * 添加销售方式
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function add()
    {
        $this->title = '添加销售方式';
        $this->assign('title', $this->title);
        return $this->_form($this->table, 'form');
    }

    /**
     * 编辑销售方式
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function edit()
    {
        $this->title = '编辑销售方式';
        $this->assign('title', $this->title);
        return $this->_form($this->table, 'form');
    }

    /**
     * 表单提交数据处理
     * @param array $data
     */
    protected function _form_filter($data)
    {
//        if ($this->request->isPost()) {
//            empty($data['brand_logo']) && $this->error('请上传销售方式Logo图片');
//            empty($data['brand_cover']) && $this->error('请上传销售方式封面图片');
//        }
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
     * 删除销售方式
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del()
    {
        if (DataService::update($this->table)) {
            $this->success("销售方式删除成功！", '');
        }
        $this->error("销售方式删除失败，请稍候再试！");
    }

    /**
     * 销售方式禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("销售方式禁用成功！", '');
        }
        $this->error("销售方式禁用失败，请稍候再试！");
    }

    /**
     * 销售方式签禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("销售方式启用成功！", '');
        }
        $this->error("销售方式启用失败，请稍候再试！");
    }

}
