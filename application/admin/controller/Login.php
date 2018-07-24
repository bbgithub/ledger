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
use service\LogService;
use service\NodeService;
use think\Db;

/**
 * 系统登录控制器
 * class Login
 * @package app\admin\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2017/02/10 13:59
 */
class Login extends BasicAdmin
{

    /**
     * 控制器基础方法
     */
    public function initialize()
    {
        if (session('user.id') && $this->request->action() !== 'out') {
            $this->redirect('@admin');
        }
    }

    /**
     * 用户登录
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        if ($this->request->isGet()) {
            return $this->fetch('', ['title' => '用户登录']);
        }
        // 输入数据效验
        $username = $this->request->post('username', '', 'trim');
        $password = $this->request->post('password', '', 'trim');
        strlen($username) < 4 && $this->error('登录账号长度不能少于4位有效字符!');
        strlen($password) < 4 && $this->error('登录密码长度不能少于4位有效字符!');
        // 用户信息验证
        $user = Db::name('User')->where('is_deleted', '0')->where('username', $username)->find();
        empty($user) && $this->error('登录账号不存在，请重新输入!');
        $user['region'] = Db::name('Region')->where('region_code', $user['region_code'])->find();
        ($user['password'] !== md5($password)) && $this->error('登录密码与账号不匹配，请重新输入!');
        empty($user['is_deleted']) || $this->error('账号已经被删除，请联系管理！');
        empty($user['status']) && $this->error('账号已经被禁用，请联系管理!');
        // 更新登录信息
//        $data = ['login_at' => Db::raw('now()'), 'login_num' => Db::raw('login_num+1')];
//        Db::name('User')->where(['id' => $user['id']])->update($data);
        session('user', $user);

        !empty($user['role']) && NodeService::applyAuthNode();
        LogService::write('系统管理', '用户登录系统成功');
        $this->success('登录成功，正在进入系统...', '@admin');
    }

    /**
     * 退出登录
     */
    public function out()
    {
        session('user') && LogService::write('系统管理', '用户退出系统成功');
        !empty($_SESSION) && $_SESSION = [];
        [session_unset(), session_destroy()];
        $this->success('退出登录成功！', '@admin/login');
    }

}
