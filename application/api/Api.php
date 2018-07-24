<?php

/**
 * @SWG\Swagger(
 *     basePath="/api",
 *     host="localhost:8100",
 *     schemes={"http"},
 *     produces={"application/json"},
 *     consumes={"application/json"},
 *     @SWG\Info(
 *          version="1.0",
 *          title="台账管理系统API",
 *          description="台账管理系统API，主要为台账APP提供接口，用于业务员、业务经理查看提成数据。",
 *          @SWG\Contact(name="15516026@qq.com"),
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *   definition="Api"
 * )
 */

/**
 * @SWG\Response(
 *      response="Json",
 *      description="the basic response",
 *      @SWG\Schema(
 *          ref="$/definitions/Api",
 *          @SWG\Property(
 *              property="code",
 *              example=200,
 *              type="integer"
 *          ),
 *          @SWG\Property(
 *              property="msg",
 *              type="string"
 *          ),
 *          @SWG\Property(
 *              property="data",
 *              type="object"
 *          )
 *      )
 * )
 *
 */