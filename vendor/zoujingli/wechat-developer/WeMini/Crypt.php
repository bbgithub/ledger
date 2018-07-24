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

namespace WeMini;

use WeChat\Contracts\BasicWeChat;
use WeChat\Contracts\Tools;
use WeChat\Exceptions\InvalidDecryptException;
use WeChat\Exceptions\InvalidResponseException;


/**
 * 数据加密处理
 * Class Crypt
 * @package WeMini
 */
class Crypt extends BasicWeChat
{

    /**
     * 数据签名校验
     * @param string $iv
     * @param string $sessionKey
     * @param string $encryptedData
     * @return bool
     */
    public function decode($iv, $sessionKey, $encryptedData)
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'crypt' . DIRECTORY_SEPARATOR . 'wxBizDataCrypt.php';
        $pc = new \WXBizDataCrypt($this->config->get('appid'), $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return json_decode($data, true);
        }
        return false;
    }

    /**
     * 登录凭证校验
     * @param string $code 登录时获取的 code
     * @return array
     */
    public function session($code)
    {
        $appid = $this->config->get('appid');
        $secret = $this->config->get('appsecret');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        return json_decode(Tools::get($url), true);
    }

    /**
     * 换取用户信息
     * @param string $code 用户登录凭证（有效期五分钟）
     * @param string $iv 加密算法的初始向量
     * @param string $encryptedData 加密数据( encryptedData )
     * @return array
     * @throws InvalidDecryptException
     * @throws InvalidResponseException
     */
    public function userInfo($code, $iv, $encryptedData)
    {
        $result = $this->session($code);
        if (empty($result['session_key'])) {
            throw new InvalidResponseException('Code 换取 SessionKey 失败', 403);
        }
        $userinfo = $this->decode($iv, $result['session_key'], $encryptedData);
        if (empty($userinfo)) {
            throw  new InvalidDecryptException('用户信息解析失败', 403);
        }
        return array_merge($result, $userinfo);
    }
}