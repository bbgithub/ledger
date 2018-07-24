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

use app\store\service\MemberService;
use service\WechatService;
use think\Controller;
use think\Db;

/**
 * 微信基础控制器
 * Class BasicWechat
 * @package controller
 */
class BasicWechat extends Controller
{

    /**
     * 当前粉丝用户OPENID
     * @var string
     */
    protected $openid;

    /**
     * 当前会员数据记录
     * @var array
     */
    protected $member = [];

    /**
     * 初始化会员数据记录
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @return array
     */
    protected function initMember()
    {
        $openid = $this->getOpenid();
        $this->member = Db::name('Member')->where(['openid' => $openid])->find();
        if (empty($this->member)) {
            MemberService::create(['openid' => $openid]);
            $this->member = Db::name('Member')->where(['openid' => $openid])->find();
        }
        return $this->member;
    }

    /**
     * 获取粉丝用户OPENID
     * @return bool|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function getOpenid()
    {
        $url = $this->request->url(true);
        return WechatService::webOauth($url, 0)['openid'];
    }

    /**
     * 获取微信粉丝信息
     * @return bool|array
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function getFansinfo()
    {
        $url = $this->request->url(true);
        return WechatService::webOauth($url, 1)['fansinfo'];
    }

}
