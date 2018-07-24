<?php

namespace app\api\controller;

use controller\BasicApi;
use service\NodeService;
use service\ApiService;
use service\DataService;
use think\Db;
use think\Request;


class User extends BasicApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @SWG\Post(
     *   path="/user/login",
     *   tags={"user"},
     *   summary="用户登录",
     *   consumes={"application/x-www-form-urlencoded"},
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="username",
     *     in="formData",
     *     description="用户名",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="password",
     *     in="formData",
     *     description="密码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="登录成功",
     *     ref="$/responses/Json",
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="data",
     *             ref="#/definitions/User"
     *         )
     *     )
     *   ),
     *   security={{
     *     "petstore_auth": {"write:pets", "read:pets"}
     *   }}
     * )
     */
    public function login(){
        if (!$this->request->isPost()) {
            $this->error('请求方法错误', 101001);
        }
        // 输入数据效验
        $username = $this->request->post('username', '', 'trim');
        $password = $this->request->post('password', '', 'trim');
        strlen($username) < 4 && $this->error('用户名长度不能小于4', 101005);
        strlen($password) < 6 && $this->error('密码长度不能小于6', 101006);
        // 用户信息验证
        $user = Db::name('user')
            ->alias('u')
            ->field('u.*, a.role_name')
            ->leftJoin('role a', 'u.role_id=a.id')
            ->where([
                'u.status' => 1,
                'u.username' => $username,
            ])
            ->find();

//        echo '<pre>';
//        var_dump($username, $user);exit;

        empty($user) && $this->error('用户名错误', 101007);
        ($user['password'] !== md5($password)) && $this->error('密码错误', 101002);
        empty($user['is_deleted']) || $this->error('账号不存在', 101003);
        $user['status'] == 1 || $this->error('账号禁用', 101004);

        // 更新登录信息
        if (!$user['token']){
            $data = ['token' => ApiService::getToken()];
            Db::name('User')->where(['id' => $user['id']])->update($data);
        }
        !empty($user['role_id']) && NodeService::applyAuthNode();
        unset($user['password'], $user['create_by']);

        $this->success('登录成功', $user);
    }

    /**
     * @SWG\Post(
     *   path="/user/info",
     *   tags={"user"},
     *   summary="用户登录",
     *   consumes={"application/x-www-form-urlencoded"},
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="username",
     *     in="formData",
     *     description="用户名",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="password",
     *     in="formData",
     *     description="密码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="登录成功",
     *     ref="$/responses/Json",
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="data",
     *             ref="#/definitions/User"
     *         )
     *     )
     *   ),
     *   security={{
     *     "petstore_auth": {"write:pets", "read:pets"}
     *   }}
     * )
     */
    public function info(){
        $this->check_auth([1,2], 'put');
        $this->check_params(['username', 'password', 'old_password']);

        if (strlen($this->input['username']) < 2)  $this->error('用户名长度不能少于2位', 101020);
        if (strlen($this->input['password']) < 6)  $this->error('密码长度不能少于6位', 101021);
        if (strlen($this->input['old_password']) < 6)  $this->error('原密码长度不能少于6位', 101022);
        $user = Db::name('user')
            ->where([
                'username' => $this->input['username'],
                'password' => md5($this->input['old_password']),
                'token' => $this->request->header('token'),
            ])
            ->find();
        if (!$user) $this->error('密码修改失败，请检查用户名、原密码和token', 101009);
        $user_update = Db::name('user')
            ->where([
                'username' => $this->input['username']
            ])
            ->update([
                'password' => md5($this->input['password']),
            ]);

        if ($user_update !== false){
            $this->success('密码修改成功');
        }
        $this->error('密码修改失败', 101010);
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        exit(__METHOD__);
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        exit(__METHOD__);
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        exit(__METHOD__);
        //
    }
}
