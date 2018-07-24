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

namespace controller;

use service\ApiService;
use service\LogService;
use service\UserService;

class BasicApi
{

    /**
     * 当前请求对象
     * @var \think\Request
     */
    protected $request;

    /**
     * 构造方法
     * BasicApi constructor.
     */
    public function __construct()
    {
        ApiService::corsOptionsHandler();
        $this->request = app('request');
    }

    /**
     * 返回成功的操作
     * @param string $msg 消息内容
     * @param array $data 返回数据
     */
    protected function success($msg, $data = [])
    {
        $token = $this->request->header('token');
        $username = $token ? UserService::get_user('', $token)['username'] : '';
        LogService::write($this->request->url(), $msg, $username, json_encode($data, JSON_FORCE_OBJECT));
        ApiService::success($msg, $data, 200);
    }

    /**
     * 返回失败的请求
     * @param string $msg 消息内容
     * @param array $data 返回数据
     */
    protected function error($msg, $code, $data = [])
    {
        ApiService::error($msg, $data, $code);
    }

    /**
     * 检查权限
     */
    protected function check_auth(array $fixed_role_ids, $method = 'get'){
        $token = $this->request->header('token');
        if (!$token){
            $this->error('请检查token', 101901);
        }
        if (!UserService::check_token($token)){
            $this->error('token无效', 101902);
        }
        if (!UserService::check_auth($fixed_role_ids, '', $token)){
            $this->error('权限错误', 101903);
        }
        switch ($method){
            case 'get':
                if (!$this->request->isGet()) $this->error('请求方法错误', 101101);
                $this->input = $this->request->get();
                break;

            case 'post':
                if (!$this->request->isPost()) $this->error('请求方法错误', 101101);
                $this->input = $this->request->post();
                break;

            case 'put':
                if (!$this->request->isPut()) $this->error('请求方法错误', 101101);
                $this->input = $this->request->put();
                break;

            default:
                break;
        }
    }

    /**
     * 检查输入参数
     * @param $expect
     */
    protected function check_params($expect){
        $no_params = implode(', ', array_flip(array_diff_key(array_flip($expect), $this->input)));
        if ($no_params) $this->error('缺少参数：' . $no_params, 101102);
        if (isset($this->input['ledger_date'])) {
            $this->input['ledger_date'] = date('Y-m-d', strtotime($this->input['ledger_date']));
        }
        $this->input['page'] = isset($this->input['page']) && $this->input['page']
            ? $this->input['page']
            : 1;
        $this->input['page_size'] = isset($this->input['page_size']) && $this->input['page_size']
            ? $this->input['page_size']
            : 20;
        $this->input['offset'] = ($this->input['page'] - 1) * $this->input['page_size'];
    }

}