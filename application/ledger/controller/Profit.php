<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace app\ledger\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

/**
 * 提成参数
 * Class Express
 * @package app\ledger\controller
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2018/06/08 14:43
 */
class Profit extends BasicAdmin
{

    /**
     * 定义当前操作表名
     * @var string
     */
    public $table = 'Profit';

    /**
     * 编辑
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
//        $profit = [
//            [
//                'sales_mode_id' => 1,
//                'list' => [
//                    [ 'sign' => '<', 'price' => 380.00, 'rate' => 0.1 ],
//                    [ 'sign' => '>=', 'price' => 390.00, 'rate' => 0.2 ],
//                ],
//            ],
//            [
//                'sales_mode_id' => 2,
//                'list' => [
//                    [ 'sign' => '<', 'price' => 750.00, 'rate' => 0.1 ],
//                    [ 'sign' => '>=', 'price' => 760.00, 'rate' => 0.2 ],
//                ],
//            ],
//        ];
//
//        echo json_encode($profit);
//        exit;

        $this->title = '提成参数';
        if (!$this->request->isPost()) {
            return $this->_form($this->table, 'form', 'id', ['id' => 1]);
        }
        try {
            $input = $this->request->post();

            $content = [];
            foreach ($input['sales_mode_id'] as $k => $v){
                $lines = preg_split("/\s*\n+\s*/", $input['content'][$k]);
                $content[$k]['sales_mode_id'] = $v;
                foreach($lines as $k2 => $v2){
                    $line = preg_replace('/\s*/', '', $v2);
                    list($sign, $price, $rate) = explode(',', $line);
                    $content[$k]['list'][] = [
                        'sign' => $sign,
                        'price' => number_format($price, 2),
                        'rate' => $rate,
                    ];
                }
            }

            $content = json_encode($content);
            Db::name($this->table)->where(['id' => $input['id']])->update(['content' => $content]);
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        } catch (\Exception $e) {
            $this->error('编辑失败，请稍候再试！' . $e->getMessage());
        }
        list($base, $spm, $url) = [url('@admin'), $this->request->get('spm'), url('ledger/profit/edit?r='.rand(1,1000))];
        $this->success('编辑成功！', "{$base}#{$url}?spm={$spm}");
    }

}
