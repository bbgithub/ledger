<?php

namespace app\api\controller;

use controller\BasicApi;
use service\NodeService;
use service\ApiService;
use service\DataService;
use service\UserService;
use think\Db;
use think\Request;


class Profit extends BasicApi
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'LedgerDetail';
    public $input;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @SWG\Get(
     *   path="/profit/sales",
     *   tags={"profit"},
     *   summary="业务员关联客户台账",
     *   consumes={"application/x-www-form-urlencoded"},
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="ledger_date",
     *     in="formData",
     *     description="台账月份",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="sales_id",
     *     in="formData",
     *     description="业务员ID",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="page",
     *     in="formData",
     *     description="页码",
     *     required=false,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="page_size",
     *     in="formData",
     *     description="每页记录数",
     *     required=false,
     *     type="int"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="业务员关联客户销售提成汇总",
     *     ref="$/responses/Json",
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="data",
     *             ref="#/definitions/User"
     *         )
     *     )
     *   ),
     *   security={{
     *     "petstore_auth": {"write:pets", "read:pets"}
     *   }}
     * )
     */
    public function sales(){
        $this->check_auth([1,2]);
        $this->check_params(['ledger_date', 'sales_id']);

        // 明细
        $sql_list = "
            SELECT 
                ld.cust_id,
                ld.cust_name,
                ld.cust_region_code,
                ld.cust_region_name,
                ld.cust_type,
                (
                  CASE ld.cust_type
                      WHEN 1 THEN '仓储服务商'
                      WHEN 2 THEN '物流配送商'
                      WHEN 3 THEN '分公司'
                      WHEN 4 THEN 'KA'
                      ELSE '盐业公司'
                  END
                ) AS cust_type_name,
                SUM(ld.market_fee) AS market_fee_total,
                SUM(ld.sales_fee) AS sales_fee_total,
                SUM(ld.qty) AS qty_total,
                SUM(IF(ld.ledger_type_id=1, -ld.amount, ld.amount)) AS amount,
                SUM(IF(ld.ledger_type_id=2, -ld.profit_amount, ld.profit_amount)) AS profit_amount_total
            FROM ledger_detail ld
            WHERE (
              ld.ledger_date='{$this->input['ledger_date']}'
              AND ld.sales_id={$this->input['sales_id']}
            )
            GROUP BY ld.cust_id
            LIMIT {$this->input['offset']}, {$this->input['page_size']}
        ";

        $data['list'] = Db::query($sql_list);

        // 合计
        $sql_total = "
            SELECT 
                SUM(ld.qty) AS qty_total,
                SUM(IF(ld.ledger_type_id=1, -ld.amount, ld.amount)) AS amount,
                SUM(ld.market_fee) AS market_fee_total,
                SUM(ld.sales_fee) AS sales_fee_total,
                SUM(IF(ld.ledger_type_id=2, -ld.profit_amount, ld.profit_amount)) AS profit_amount_total
            FROM ledger_detail ld
            WHERE (
              ld.ledger_date='{$this->input['ledger_date']}'
              AND ld.sales_id={$this->input['sales_id']}
            )
            GROUP BY ld.cust_id
            WITH ROLLUP
        ";

        $total_rs = Db::query($sql_total);
        $total = (array)array_pop($total_rs);
        $total['ledger_date'] = date('Y-m', strtotime($this->input['ledger_date']));
        $total['page'] = $this->input['page'];
        $total['page_size'] = $this->input['page_size'];
        $total['page_count'] = count($total_rs);

        $data = array_merge($data, $total);

        $this->success('', $data);
    }

    /**
     * @SWG\Get(
     *   path="/ledger/customer",
     *   tags={"ledger"},
     *   summary="指定客户的台账明细",
     *   consumes={"application/x-www-form-urlencoded"},
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="ledger_date",
     *     in="formData",
     *     description="台账月份",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="sales_id",
     *     in="formData",
     *     description="业务员ID",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="cust_id",
     *     in="formData",
     *     description="客户ID",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="page",
     *     in="formData",
     *     description="页码",
     *     required=false,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="page_size",
     *     in="formData",
     *     description="每页记录数",
     *     required=false,
     *     type="int"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="客户台账查询",
     *     ref="$/responses/Json",
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="data",
     *             ref="#/definitions/User"
     *         )
     *     )
     *   ),
     *   security={{
     *     "petstore_auth": {"write:pets", "read:pets"}
     *   }}
     * )
     */
    public function customer(){
        $this->check_auth([1,2]);
        $this->check_params(['ledger_date', 'sales_id', 'cust_id']);

        // 明细
        $sql_list = "
            SELECT 
                ld.profit_rate,
                ld.created_at,
                ld.amount,
                ld.discount_price,
                ld.goods_name,
                ld.goods_no,
                ld.unit_price,
                ld.qty,
                ld.market_fee,
                ld.spread_price,
                ld.sales_fee,
                ld.sales_mode_id,
                ld.sales_mode_name,
                ld.trans_fee,
                ld.salt_spread_price,
                ld.rebate,
                ld.profit_amount,
                ld.ledger_type_id,
                (
                  CASE ld.ledger_type_id
                      WHEN 1 THEN '发货'
                      WHEN 2 THEN '退货'
                      WHEN 3 THEN '收款'
                      ELSE '退款'
                  END
                ) AS ledger_type_name
            FROM ledger_detail ld
            WHERE (
                ld.ledger_date='{$this->input['ledger_date']}' 
                AND ld.sales_id={$this->input['sales_id']} 
                AND ld.cust_id={$this->input['cust_id']}
                AND (ld.ledger_type_id=1 OR ld.ledger_type_id=2)
            )
            LIMIT {$this->input['offset']}, {$this->input['page_size']}
        ";

        $data['list'] = Db::query($sql_list);

        // 合计
        $sql_total = "
            SELECT 
                l.created_at,
                SUM(IF(ld.ledger_type_id=1, -ld.amount, ld.amount)) AS amount,
                SUM(ld.market_fee) AS market_fee,
                SUM(ld.sales_fee) AS sales_fee,
                SUM(ld.qty) AS qty_total,
                SUM(IF(ld.ledger_type_id=2, -ld.profit_amount, ld.profit_amount)) AS profit_amount,
                l.status,
                (
                  CASE l.status
                      WHEN 1 THEN '已冻结'
                      WHEN 2 THEN '已审核'
                      ELSE '未冻结'
                  END
                ) AS status_name,
                COUNT(ld.id) AS page_count
            FROM ledger_detail ld
            LEFT JOIN ledger l
                ON (l.cust_id=ld.cust_id AND l.ledger_date=ld.ledger_date)
            WHERE (
                ld.ledger_date='{$this->input['ledger_date']}' 
                AND ld.sales_id={$this->input['sales_id']} 
                AND ld.cust_id={$this->input['cust_id']}
                AND (ld.ledger_type_id=1 OR ld.ledger_type_id=2)
            )
        ";

        $total_rs = Db::query($sql_total);

        $total = (array)array_pop($total_rs);
        $total['ledger_date'] = date('Y-m', strtotime($this->input['ledger_date']));
        $total['page'] = $this->input['page'];
        $total['page_size'] = $this->input['page_size'];

        $data = array_merge($data, $total);

        $this->success('', $data);
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        exit(__METHOD__);
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        exit(__METHOD__);
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        exit(__METHOD__);
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        exit(__METHOD__);
        //
    }
}
