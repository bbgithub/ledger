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
 * 微信红包支持
 * Class Redpack
 * @package WePay
 */
class Redpack extends BasicPay
{

    /**
     * 发放普通红包
     * @param array $options
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function create(array $options)
    {
        $this->params->offsetUnset('appid');
        $this->params->set('wxappid', $this->config->get('appid'));
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        return $this->callPostApi($url, $options, true, 'MD5', false);
    }

    /**
     * 发放裂变红包
     * @param array $options
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function groups(array $options)
    {
        $this->params->offsetUnset('appid');
        $this->params->set('wxappid', $this->config->get('appid'));
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack";
        return $this->callPostApi($url, $options, true, 'MD5', false);
    }

    /**
     * 查询红包记录
     * @param string $mchBillno 商户发放红包的商户订单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function query($mchBillno)
    {
        $this->params->offsetUnset('wxappid');
        $this->params->set('appid', $this->config->get('appid'));
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo";
        return $this->callPostApi($url, ['mch_billno' => $mchBillno, 'bill_type' => 'MCHT'], true, 'MD5', false);
    }

}