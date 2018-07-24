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

namespace service;

use think\Exception;
use think\facade\Log;

/**
 * Soap服务对象
 * Class SoapService
 * @package service
 */
class SoapService
{

    /**
     * SOAP实例对象
     * @var \SoapClient
     */
    protected $soap;

    /**
     * SoapService constructor.
     * @param string|null $wsdl WSDL连接参数
     * @param array $params Params连接参数
     * @throws \think\Exception
     */
    public function __construct($wsdl, $params)
    {
        set_time_limit(3600);
        if (!extension_loaded('soap')) {
            throw new Exception('Not support soap.');
        }
        $this->soap = new \SoapClient($wsdl, $params);
    }

    /**
     * @param string $name SOAP调用方法名
     * @param array|string $arguments SOAP调用参数
     * @return array|string|bool
     * @throws \think\Exception
     */
    public function __call($name, $arguments)
    {
        try {
            return $this->soap->__soapCall($name, $arguments);
        } catch (\Exception $e) {
            Log::error("Soap Error. Call {$name} Method --- " . $e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

}
