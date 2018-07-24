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

use think\Db;
use think\Request;

/**
 * 台账历史
 * Class RegionService
 */
class LedgerHistoryService
{
    public static $table = 'LedgerHistory';

    /**
     * 插入台账历史记录
     * @param $ledger_detail
     */
    public static function add($ledger_detail, $action, $ledger_id = ''){
        $ledger_id = empty($ledger_id) ? Db::name('ledger')->getLastInsID() : $ledger_id;
        $data = [
            'ledger_id' => $ledger_id,
            'action' => $action,
            'operator_id' => session('user.id'),
            'content' => json_encode($ledger_detail, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
        ];
        $ledger_history = Db::name(self::$table)
            ->insert($data);

        $data['content'] = $ledger_detail;
        LogService::write(request()->url(), '台账明细', '', json_encode($data, JSON_FORCE_OBJECT));
        return $ledger_history;
    }
}