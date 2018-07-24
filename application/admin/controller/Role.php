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
use service\NodeService;
use service\ToolsService;
use think\Db;

/**
 * 系统角色管理控制器
 * Class Role
 * @package app\admin\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2017/02/15 18:13
 */
class Role extends BasicAdmin
{

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'Role';

    /**
     * 角色列表
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '系统角色管理';
        return parent::_list($this->table);
    }

    /**
     * 数据处理
     * @param array $data
     */
    protected function _data_filter(&$data)
    {
        foreach ($data as $k => $v){
            if ($v['is_deleted'] == 1) unset($data[$k]);
        }
    }

    /**
     * 角色授权
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function apply()
    {
        $this->title = '节点授权';
        $role_id = $this->request->get('id', '0');
        $method = '_apply_' . strtolower($this->request->get('action', '0'));
        if (method_exists($this, $method)) {
            return $this->$method($role_id);
        }
        return $this->_form($this->table, 'apply');
    }

    /**
     * 读取授权节点
     * @param string $role_id
     */
    protected function _apply_getnode($role_id)
    {
        $nodes = NodeService::get();
        $checked = Db::name('RoleNode')->where(['role_id' => $role_id])->column('node');
        foreach ($nodes as &$node) {
            $node['checked'] = in_array($node['node'], $checked);
        }
        $all = $this->_apply_filter(ToolsService::arr2tree($nodes, 'node', 'pnode', '_sub_'));
        $this->success('获取节点成功！', '', $all);
    }

    /**
     * 保存授权节点
     * @param string $role
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function _apply_save($role_id)
    {
        list($data, $post) = [[], $this->request->post()];
        foreach (isset($post['nodes']) ? $post['nodes'] : [] as $node) {
            $data[] = ['role_id' => $role_id, 'node' => $node];
        }
        Db::name('RoleNode')->where(['role_id' => $role_id])->delete();
        Db::name('RoleNode')->insertAll($data);
        $this->success('节点授权更新成功！', '');
    }

    /**
     * 节点数据拼装
     * @param array $nodes
     * @param int $level
     * @return array
     */
    protected function _apply_filter($nodes, $level = 1)
    {
        foreach ($nodes as $key => $node) {
            if (!empty($node['_sub_']) && is_array($node['_sub_'])) {
                $node[$key]['_sub_'] = $this->_apply_filter($node['_sub_'], $level + 1);
            }
        }
        return $nodes;
    }

    /**
     * 角色添加
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
     * 角色编辑
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
     * 角色禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("角色禁用成功！", '');
        }
        $this->error("角色禁用失败，请稍候再试！");
    }

    /**
     * 角色恢复
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("角色启用成功！", '');
        }
        $this->error("角色启用失败，请稍候再试！");
    }

    /**
     * 角色删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del()
    {
        if (DataService::update($this->table)) {
            $where = ['role_id' => $this->request->post('id')];
            Db::name('RoleNode')->where($where)->delete();
            $this->success("角色删除成功！", '');
        }
        $this->error("角色删除失败，请稍候再试！");
    }

}
