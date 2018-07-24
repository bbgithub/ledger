<?php

// +----------------------------------------------------------------------
// | 台账管理系统-Ledger
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | QQ:15516026
// +----------------------------------------------------------------------

namespace service;

use think\Db;
use think\db\Query;
use think\facade\Cache;

/**
 * 基础数据服务
 * Class DataService
 * @package service
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2017/03/22 15:32
 */
class DataService
{

    /**
     * 删除指定序号
     * @param string $sequence
     * @param string $type
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function deleteSequence($sequence, $type = 'SYSTEM')
    {
        $data = ['sequence' => $sequence, 'type' => strtoupper($type)];
        return Db::name('SystemSequence')->where($data)->delete();
    }

    /**
     * 生成唯一序号 (失败返回 NULL )
     * @param int $length 序号长度
     * @param string $type 序号顾类型
     * @return string
     */
    public static function createSequence($length = 10, $type = 'SYSTEM')
    {
        $times = 0;
        while ($times++ < 10) {
            list($i, $sequence) = [0, ''];
            while ($i++ < $length) {
                $sequence .= ($i <= 1 ? rand(1, 9) : rand(0, 9));
            }
            $data = ['sequence' => $sequence, 'type' => strtoupper($type)];
            if (Db::name('SystemSequence')->where($data)->count() < 1 && Db::name('SystemSequence')->insert($data) !== false) {
                return $sequence;
            }
        }
        return null;
    }

    /**
     * 数据增量保存
     * @param Query|string $dbQuery 数据查询对象
     * @param array $data 需要保存或更新的数据
     * @param string $key 条件主键限制
     * @param array $where 其它的where条件
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function save($dbQuery, $data, $key = 'id', $where = [])
    {
        $db = is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery;
        list($table, $map) = [$db->getTable(), [$key => isset($data[$key]) ? $data[$key] : '']];

        if (Db::table($table)->where($where)->where($map)->count() > 0) {
            $db2 = ($table == 'goods') ? Db::table($table)->cache('goods_all') : Db::table($table);
            return $db2->strict(false)->where($where)->where($map)->update($data) !== false;
        }

        if (Db::table($table)->strict(false)->insert($data) !== false){
            if($table == 'goods') Cache::rm('goods_all');
            return true;
        }
        return false;

//        return Db::table($table)->strict(false)->insert($data) !== false;
    }

    /**
     * 更新数据表内容
     * @param Query|string $dbQuery 数据查询对象
     * @param array $where 额外查询条件
     * @return bool|null
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function update(&$dbQuery, $where = [])
    {
        $request = app('request');
        $db = is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery;
        list($pk, $table, $map) = [$db->getPk(), $db->getTable(), []];
        list($field, $value) = [$request->post('field', ''), $request->post('value', '')];
        $map[] = [empty($pk) ? 'id' : $pk, 'in', explode(',', $request->post('id', ''))];

        $db2 = ($table == 'goods') ? Db::table($table)->cache('goods_all') : Db::table($table);

        // 删除模式，如果存在 is_deleted 字段使用软删除
        if ($field === 'delete') {
            if (method_exists($db, 'getTableFields') && in_array('is_deleted', $db->getTableFields())) {
                return $db2->where($where)->where($map)->update(['is_deleted' => '1']) !== false;
            }
            return $db2->where($where)->where($map)->delete() !== false;
        }
        // 更新模式，更新指定字段内容
        return $db2->where($where)->where($map)->update([$field => $value]) !== false;
    }

}
