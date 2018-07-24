<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | github开源项目：
// +----------------------------------------------------------------------

namespace WePay;

use WeChat\Contracts\BasicPay;

/**
 * 微信商户退款
 * Class Refund
 * @package WePay
 */
class Refund extends BasicPay
{

    /**
     * 创建退款订单
     * @param array $options
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function create(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 查询退款
     * @param array $options
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function query(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';
        return $this->callPostApi($url, $options);
    }

}