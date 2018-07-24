<?php

namespace app\api\controller;

use controller\BasicApi;
use service\NodeService;
use service\ApiService;
use service\DataService;
use service\UserService;
use think\Db;
use think\Request;


class Ledger extends BasicApi
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
     *   path="/ledger/region",
     *   tags={"ledger"},
     *   summary="业务经理关联区域客户台账",
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
     *     name="region_code",
     *     in="formData",
     *     description="用户的区域编码",
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
     *     description="区域台账查询",
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
    public function region(){
        $this->check_auth([2]);
        $this->check_params(['ledger_date', 'region_code']);

        // 明细
        $sql_list = "
            SELECT 
                ld.sales_id,
                ld.sales_region_name,
                ld.sales_id,
                ld.sales_real_name,
                IFNULL(l.cust_balance, 0) AS cust_balance,
                COUNT(DISTINCT ld.cust_id) AS cust_number,
                SUM(l.last_carry_down) AS last_carry_down_balance,
                SUM(ld.qty) AS qty_total,
                SUM(IF(ld.ledger_type_id=4, -ld.receipt_refund, ld.receipt_refund)) AS receipt_refund_total,
                SUM(IF(ld.ledger_type_id=1, -ld.amount, ld.amount)) AS amount
            FROM ledger_detail ld
            LEFT JOIN ledger l
                ON (l.cust_id=ld.cust_id AND l.ledger_date=ld.ledger_date)
            WHERE (
                ld.ledger_date='{$this->input['ledger_date']}' 
                AND ld.sales_region_code LIKE '{$this->input['region_code']}%'
            )
            GROUP BY ld.sales_id
            LIMIT {$this->input['offset']}, {$this->input['page_size']}
        ";

        $data['list'] = Db::query($sql_list);

        // 合计
        $sql_total = "
            SELECT 
                COUNT(DISTINCT ld.cust_id) AS cust_number,
                IFNULL(l.cust_balance, 0) AS cust_balance,
                SUM(l.last_carry_down) AS last_carry_down_balance,
                SUM(ld.qty) AS qty_total,
                SUM(IF(ld.ledger_type_id=4, -ld.receipt_refund, ld.receipt_refund)) AS receipt_refund_total,
                SUM(IF(ld.ledger_type_id=1, -ld.amount, ld.amount)) AS amount
            FROM ledger_detail ld
            LEFT JOIN ledger l
                ON (l.cust_id=ld.cust_id AND l.ledger_date=ld.ledger_date)
            WHERE (
                ld.ledger_date='{$this->input['ledger_date']}' 
                AND ld.sales_region_code LIKE '{$this->input['region_code']}%'
            )
            GROUP BY ld.sales_id
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
     *   path="/ledger/sales",
     *   tags={"ledger"},
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
     *     description="业务员台账查询",
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
                IFNULL(l.cust_balance, 0) AS cust_balance,
                SUM(l.last_carry_down) AS last_carry_down_balance,
                SUM(ld.qty) AS qty_total,
                SUM(IF(ld.ledger_type_id=4, -ld.receipt_refund, ld.receipt_refund)) AS receipt_refund_total,
                SUM(IF(ld.ledger_type_id=1, -ld.amount, ld.amount)) AS amount
            FROM ledger_detail ld
            LEFT JOIN ledger l
                ON (l.cust_id=ld.cust_id AND l.ledger_date=ld.ledger_date)
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
                COUNT(DISTINCT ld.cust_id) AS cust_number,
                IFNULL(l.cust_balance, 0) AS cust_balance,
                SUM(l.last_carry_down) AS last_carry_down_balance,
                SUM(ld.qty) AS qty_total,
                SUM(IF(ld.ledger_type_id=4, -ld.receipt_refund, ld.receipt_refund)) AS receipt_refund_total,
                SUM(IF(ld.ledger_type_id=1, -ld.amount, ld.amount)) AS amount
            FROM ledger_detail ld
            LEFT JOIN ledger l
                ON (l.cust_id=ld.cust_id AND l.ledger_date=ld.ledger_date)
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
                ld.created_at,
                ld.goods_name,
                ld.goods_no,
                ld.unit_price,
                ld.qty,
                ld.ledger_type_id,
                (
                  CASE ld.ledger_type_id
                      WHEN 1 THEN '发货'
                      WHEN 2 THEN '退货'
                      WHEN 3 THEN '收款'
                      ELSE '退款'
                  END
                ) AS ledger_type_name,
                ld.amount,
                ld.receipt_refund,
                ld.trans_fee
            FROM ledger_detail ld
            LEFT JOIN ledger l
                ON (l.cust_id=ld.cust_id AND l.ledger_date=ld.ledger_date)
            WHERE (
                ld.ledger_date='{$this->input['ledger_date']}' 
                AND ld.sales_id={$this->input['sales_id']} 
                AND ld.cust_id={$this->input['cust_id']}
            )
            LIMIT {$this->input['offset']}, {$this->input['page_size']}
        ";

        $data['list'] = Db::query($sql_list);

        // 合计
        $sql_total = "
            SELECT 
                COUNT(ld.id) AS page_count,
                SUM(ld.qty) AS qty_total,
                SUM(
                  CASE ld.ledger_type_id
                      WHEN 1 THEN -ld.amount
                      WHEN 2 THEN ld.amount
                      ELSE 0
                  END
                ) AS amount,
                SUM(
                  CASE ld.ledger_type_id
                      WHEN 3 THEN ld.receipt_refund
                      WHEN 4 THEN -ld.receipt_refund
                      ELSE 0
                  END
                ) AS receipt_refund,
                (SUM(
                  CASE ld.ledger_type_id
                      WHEN 1 THEN -ld.amount
                      WHEN 2 THEN ld.amount
                      ELSE 0
                  END
                ) +
                SUM(
                  CASE ld.ledger_type_id
                      WHEN 3 THEN ld.receipt_refund
                      WHEN 4 THEN -ld.receipt_refund
                      ELSE 0
                  END
                )) AS amount_total,
                l.last_carry_down,
                l.status,
                (
                  CASE l.status
                      WHEN 1 THEN '已冻结'
                      WHEN 2 THEN '已审核'
                      ELSE '未冻结'
                  END
                ) AS status_name,
                IFNULL(l.cust_balance, 0) AS cust_balance
            FROM ledger_detail ld
            LEFT JOIN ledger l
                ON (l.cust_id=ld.cust_id AND l.ledger_date=ld.ledger_date)
            WHERE (
                ld.ledger_date='{$this->input['ledger_date']}' 
                AND ld.sales_id={$this->input['sales_id']} 
                AND ld.cust_id={$this->input['cust_id']}
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
