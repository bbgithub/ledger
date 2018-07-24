# ledger
台账管理系统

基于Thinkphp 5.1+ 和开源产品ThinkAdmin。
# 功能描述
- 功能：本系统主要用于记录客户台账（用于与客户对账），以及统计业务员提成（业务员可在APP端实时查询提成信息）。
- 业务流程
    - 后台
        - 管理员账号/密码：admin 123456
        - 系统管理员：角色权限分配、区域理、提成参数、商品、销售方式、用户维护。
        - 内务：维护客户、提交台账明细、冻结台账。
        - 财务：审核所有已冻结的台账。
    - APP前台
        - 业务员：登录查看自己的提成汇总、单笔提成明细。
        - 业务经理：拥有同业务员的功能，且可查看所属区域中所有业务员的提成汇总。
    - 截图
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/1.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/2.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/3.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/4.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/5.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/6.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/7.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/8.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/9.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/10.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/11.png)
        ![image](https://github.com/echobar/ledger/blob/master/static/theme/img/screenshot/12.png)
# 使用说明
- 本系统推荐使用PHP7.2 + MYSQL8.0 + REDIS4.0，基于ThinkPHP5.1开发，但数据库版本和缓存版本无硬性要求，推荐使用Docker部署。
- 创建ledger_test数据库，导入根目录下的ledger_test.sql
- /config/database.php里配置MYSQL账号密码
- /config/cache.php里配置redis及端口（注本系统默认端口），如不想安装redis,请解开上面几行的注释，改为文件缓存。本缓存主要用于添加台账时的商品查询，因商品为全量查询，因此缓存起来。在修改、删除、添加商品后，缓存自动会清除，获取全量时自动缓存最新数据。
- 注：导入sql后，请务必移除该文件，以免使用过程中被恶意下载导致信息泄露
# 模块
## 商品
- 商品资料：商品基础资料维护
- 销售方式：商品销售方式维护
## 台账
- 台账明细（内务）：按月记录客户台账。每个客户每月可有多条台账明细记录。台账明细维护可根据台账类型（收货，发货，收款，退款），各种价格关系、提成参数进行动态计算，具体算法见当页JS脚本。
- 台账冻结（内务）：按月冻结客户台账。每个客户每月只能有一条台账冻结记录（台账汇总记录），当月未冻结、未审核的台账，不能添加下月的台账明细记录。
- 台账审核（财务）：批量审核所有已冻结的台账。可审核通过或审核拒绝，审核拒绝的台账，由账务修改后重新提交冻结。
- 提成参数（管理员）：按销售方式，分为两种提成参数配置方式。提成方式按不重叠的商品数量范围来确定提成系数，此系数在内务维护台账明细时，由JS方式代入动态计算。
## 用户
- 用户信息维护。用户基本信息，和用户所属区域、所属角色管理。用户均可登录后台，根据不同角色拥有不同模块和不同操作权限；用户还可登录APP（未开源），APP所需接口由本后台的API模块提供。
## 客户
- 客户信息维护。客户基本信息，和客户所属区域、所属业务员（用户的一种角色，可不关联）。客户无登录权限，主要用于作对账主体。
## 角色权限
- 角色由业务员、业务经理、内务、账务、系统管理员组成。各角色权限初始已经配置，以下所有权限配置，均可在角色权限-角色授权-授权里去按需求分配。
- 业务员：完成对客户的销售业绩，记录提成主体。
- 业务经理：基本身既是业务员，也是业务经理。业务经理拥有查看其所在区域的业务员提成汇总及明细的权限，主体差别体现在APP统计汇总端。
- 内务：负责客户信息维护、台账明细维护、台账冻结。
- 财务：负责对已冻结台账的审核。
- 系统管理员：admin也是一个特殊的系统管理员，有全局权限。其余的系统管理员，负责区域维护、用户维护、商品维护、销售方式维护。
## 系统设置
- 区域管理：区域树型维护。用户均挂在区域之下，各用户只能操作其所属区域下的业务。区域由唯一的区域编码绑定，区域编码每个层级由10~99之间的数字组成，并自动产生，对于业务查询、关联有极大便利，于性能有显著提升，比如根层级为10，一级为1010，二级为101010，如用户所属为一级，则查询所属业务，SQL可用like '1010%'或REGEXP "^1010.*$"方式实现。
- 系统日志：记录操作日志和系统日志，及APP访问日志。日志查查看每次记录的JSON参数，便于查找问题。
- 后台菜单：可配置后台管理的主、子菜单。
- 系统参数：后台首页参数。因参数没有对业务显示的必要，因此首页已经替换能系统帮助信息。如需恢复，将application/admin/view/main2.html改加main.html即可。
