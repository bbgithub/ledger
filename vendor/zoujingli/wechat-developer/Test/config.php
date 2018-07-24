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

return [
    'token'          => 'test',
    'appid'          => 'wx60a43dd8161666d4',
    'appsecret'      => '71308e96a204296c57d7cd4b21b883e8',
    'encodingaeskey' => 'BJIUzE0gqlWy0GxfPp4J1oPTBmOrNDIGPNav1YFH5Z5',
    // 配置商户支付参数
    'mch_id'         => "1332187001",
    'mch_key'        => 'A82DC5BD1F3359081049C568D8502BC5',
    // 配置商户支付双向证书目录
    'ssl_key'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'apiclient_key.pem',
    'ssl_cer'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'apiclient_cert.pem',
    // 配置缓存目录，需要拥有写权限
    'cache_path'     => '',
];