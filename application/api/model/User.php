<?php

namespace app\api\model;

use think\Model;

/**
 * @SWG\Definition(
 *   type="object"
 * )
 */
class User extends Model
{
    /**
     * @SWG\Property(
     *     type="integer",
     *     description="用户ID"
     * )
     */
    public $id;

    /**
     * @SWG\Property(
     *     type="string",
     *     description="区域编码"
     * )
     */
    public $region_code;

    /**
     * @SWG\Property(
     *     type="integer",
     *     description="角色ID"
     * )
     */
    public $role_id;

    /**
     * @SWG\Property(
     *     type="string",
     *     description="用户名"
     * )
     */
    public $username;

    /**
     * @SWG\Property(
     *     type="string",
     *     description="密码"
     * )
     */
    public $password;

    /**
     * @SWG\Property(
     *     type="integer",
     *     description="状态"
     * )
     */
    public $status;

    /**
     * @SWG\Property(
     *     type="string",
     *     description="令牌"
     * )
     */
    public $token;
}
