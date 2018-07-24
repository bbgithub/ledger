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
use service\ToolsService;
use service\RegionService;
use think\Db;

/**
 * 系统用户管理控制器
 * Class User
 * @package app\admin\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2017/02/15 18:12
 */
class User extends BasicAdmin
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'User';

    /**
     * 用户列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function index()
    {
        $this->title = '用户管理';
        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['username', 'concat_phone'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        $data = $db
            ->alias('u')
            ->field('u.*, a.role_name')
            ->where(['u.is_deleted' => 0])
            ->order('u.real_name')
//            ->leftJoin('region r', 'u.region_code = r.region_code')
            ->leftJoin('role a', 'u.role_id = a.id')
        ;
        (isset($get['region_code']) && $get['region_code'] !== '') && $data->where('u.region_code', $get['region_code']);
        (isset($get['role_id']) && $get['role_id'] !== '') && $data->where('a.id', $get['role_id']);

        return parent::_list($data);
    }

    /**
     * 授权管理
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function auth()
    {
        return $this->_form($this->table, 'auth');
    }

    /**
     * 用户添加
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
     * 用户编辑
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
     * 用户密码修改
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function pass()
    {
        if ($this->request->isGet()) {
            $this->assign('verify', false);
            return $this->_form($this->table, 'pass');
        }
        $post = $this->request->post();
        if ($post['password'] !== $post['repassword']) {
            $this->error('两次输入的密码不一致！');
        }
        $data = ['id' => $post['id'], 'password' => md5($post['password'])];
        if (DataService::save($this->table, $data, 'id')) {
            $this->success('密码修改成功，下次请使用新密码登录！', '');
        }
        $this->error('密码修改失败，请稍候再试！');
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
            if (isset($data['role_id']) && is_array($data['role_id'])) {
                $data['role_id'] = join(',', $data['role_id']);
            } else {
                $data['role_id'] = '';
            }
            if (isset($data['id'])) {
                unset($data['username']);
            } elseif (Db::name($this->table)->where(['username' => $data['username']])->count() > 0) {
                $this->error('用户账号已经存在，请使用其它账号！');
            }
            $this->request->post('password', '') ? $data['password'] = md5($this->request->post('password')) : null;
//            $data['password'] = md5($this->request->post('password'));
        } else {
            // 角色列表
            $data['role_id'] = explode(',', isset($data['role_id']) ? $data['role_id'] : '');
            $this->assign('roles', Db::name('Role')->where(['status' => '1'])->select());

            $this->assign([
                'cates' => RegionService::regionTable($data), // 全部区域列表
            ]);
        }
    }

    /**
     * 删除用户
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止删除！');
        }
        if (DataService::update($this->table)) {
            $this->success("用户删除成功！", '');
        }
        $this->error("用户删除失败，请稍候再试！");
    }

    /**
     * 用户禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止操作！');
        }
        if (DataService::update($this->table)) {
            $this->success("用户禁用成功！", '');
        }
        $this->error("用户禁用失败，请稍候再试！");
    }

    /**
     * 用户禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("用户启用成功！", '');
        }
        $this->error("用户启用失败，请稍候再试！");
    }

}
