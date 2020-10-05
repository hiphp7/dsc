
### 分销二次开发修改文档


### 后台主要功能：

一、 分销商条件
> 平台后台 设置分销商条件：  
1. 无条件申请，消费金额，购买商品（商品支持多个商品任选其一）
2. 分销商购买条件：按金额购买或消费积分兑换 （要么金额购买 要么用消费积分兑换 不存在部分抵扣的情况）
3. 后台可手动设置会员成为分销商

二、分销产品设置
> 平台后台 产品自定义分销金额
1. 商品分销金额原先按比例，增加按金额数字
2. 分销商品详情， 分销商查看 显示分享佣金（商品佣金*一级分销比例）


三、分销等级晋升
> 平台后台新增晋升规则
> 后台新增分销商活动





四、分销商提现
> 后台管理员审核分销商提现申请，支持微信企业付款到银行卡、到零钱
> 后台查看提现申请记录


五、分红统计





### 前台主流程：

1. 会员申请分销商 依据后台开启条件，例如 开启购买商品 成为分销商，如果满足条件，进入填写申请资料页面，提交后成功进入。
2. 后台 若开启了购买成为分销商，且填写了消费积分兑换值，则前台出现 消费积分兑换 按钮，点击兑换成功，积分不足则失败。

3.

4. 前端分销中心=》可提现佣金：分销商可发起提现申请，转出至银行卡、转至微信零钱、或转出余额。申请成功，等待管理员审核。


### 开发



### 数据表

drp_config 增加配置记录
新增购买商品成为分销商 配置 多选 code = 'buy_goods'
新增购买分销商  按金额购买或消费积分兑换 code = 'buy_pay_point'

分销产品设置：
修改 goods 表 is_distribution 是否参与分销 dis_commission 分销佣金百分比
新增字段 dis_commission_type 分销佣金值类型 0 百分比，1 数值

分销商提现：
修改 drp_transfer_log  分销商佣金转出（提现）记录表
修改 drp_shop 表 增加字段 frozen_money 冻结资金

> 新建数据库
1. drp_upgrade_condition  分销商升级条件
2. drp_upgrade_values 分销商升级的值
3. drp_reward_log 分销商活动/升级奖励记录
4. drp_activity_detailes 分销商活动表


### API 接口 (新增)


api/distribute/deposit_apply
api/distribute/deposit_apply_list
api/distribute/cash_pay_point


### 修改文件
   
   # 后台 View
   
   1. app/modules/admin/view/user_list.dwt  添加一个设置分销商菜单
               <div class="tDiv a2">下
   ~~~
   <a href="/admin/distribute/set_drp?user_id={$list.user_id}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{$lang.set_drp}</a>
   ~~~
   2. app/modules/admin/view/goods_list.dwt  添加一个关联论坛按钮
   ~~~
   <a href="goods.php?act=view_log&id={$goods.goods_id}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{$lang.log}</a>
   ~~~
   下添加
   ~~~
   <a href="forum/forum_list?goods_id={$goods.goods_id}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{$lang.relevance_forum}</a>
   ~~~
       
   # 后台 Controller
   
   1. app/Modules/Seller/Languages/zh-CN/common_merchants.php  添加内容
   ~~~
   //拼团
   if (file_exists(MOBILE_TEAM)) {
       $_LANG['18_team'] = '拼团活动';
   }
   ~~~
   上 添加
   ~~~
   //分销商活动
   if (file_exists(app_path('Custom/Distribute'))) {
       $_LANG['seller_activity_list'] ='分销商活动';
   }
   ~~~
   
   
   ## 语言包
   1. app/Modules/Admin/Languages/zh-CN/users.php  添加内容
   ~~~
   $_LANG['set_drp'] = "设置分销商";
   ~~~
   
   2. app/Modules/Admin/Languages/zh-CN/common.php  添加内容
   ~~~
   $_LANG['relevance_forum'] = '关联论坛';
   ~~~
   
   
   # service 
   
   3. app/Service/Cart/CartService.php
    ~~~
    $query->select('goods_id', 'is_distribution', 'dis_commission'
    ~~~
       替换为
    ~~~
    $query->select('goods_id', 'is_distribution', 'dis_commission', 'dis_commission_type'
    ~~~
    
    3. app/Service/Cart/CartService.php
    ~~~
     $cartOther[$key]['is_distribution'] = $goodsInfo['is_distribution'] * $is_distribution;
    ~~~
     下
    去除
    ~~~
       $cartOther[$key]['drp_money'] = ($goodsInfo['dis_commission'] * $goodsInfo['is_distribution'] * $val['goods_price'] * $val['goods_number']) / 100 * $is_distribution;
    ~~~
    添加
    ~~~
       if (isset($goodsInfo['dis_commission_type']) && $goodsInfo['dis_commission_type'] == 1) {
           //商品佣金按照设定数额进行返利
           $cartOther[$key]['drp_money'] = ($goodsInfo['dis_commission'] * $goodsInfo['is_distribution'] * $val['goods_number']) * $is_distribution;
       } else {
           //商品佣金按照比例进行返利
           $cartOther[$key]['drp_money'] = ($goodsInfo['dis_commission'] * $goodsInfo['is_distribution'] * $val['goods_price'] * $val['goods_number']) / 100 * $is_distribution;
       }
    ~~~
    
    4.app/Service/Flow/FlowMobileService.php
    
    ~~~
    $order_goods['is_distribution'] = isset($v['is_distribution']) ? $v['is_distribution'] * $is_distribution : 0;
    ~~~
    下去除
    ~~~
    $order_goods['drp_money'] = !isset($v['dis_commission']) || !isset($v['is_distribution']) ? 0 : $v['dis_commission'] * $v['is_distribution'] * $v['goods_price'] * $v['goods_number'] / 100 * $is_distribution;
    ~~~
    添加
    ~~~
    $v['dis_commission'] = $v['dis_commission'] ?? 0;
    if (isset($v['dis_commission_type']) && $v['dis_commission_type'] == 1) {
        //商品佣金按照设定数额进行返利
        $order_goods['drp_money'] = $order_goods['is_distribution'] * ( $v['dis_commission'] * $v['goods_number']);
    }else{
        //商品佣金按照比例进行返利
        $order_goods['drp_money'] = $order_goods['is_distribution'] * ($v['dis_commission'] * $v['goods_price'] * $v['goods_number'] / 100);
    }
    ~~~
    
    5.  app/Service/Cart/CartService.php
    ~~~
    if (isset($drp_affiliate['on']) && $drp_affiliate['on'] == 1) {
    ~~~
    下将
    ~~~
    $parent_id = app(UserService::class)->get_affiliate($user_id);
    ~~~
    修改为
    ~~~
    $parent_id = app(UserService::class)->get_drp_affiliate($user_id);
    ~~~
    
    6. app/Service/User/UserService.php
    新建方法
    ~~~
        /**
         * 获取用户的 drp_parent_id
         * @param int $parent_id
         * @return int
         */
        public function get_drp_affiliate($user_id = 0)
        {
            if ($user_id > 0) {
                $user_id = Users::where('user_id', $user_id)->value('drp_parent_id');
                return $user_id ?? 0;
            }
            return 0;
        }
    
        /**
         * 获取用户的 parent_id
         * @param int $parent_id
         * @return int
         */
        public function get_parent_id($user_id = 0)
        {
            if ($user_id > 0) {
                $user_id = Users::where('user_id', $user_id)->value('parent_id');
                return $user_id ?? 0;
            }
            return 0;
        }
    ~~~
    
   # 前台  View
   
   1. resources/views/themes/ecmoban_dsc2017/distribute 直接上传
   
   ---
   # 前台 Controller
   1. app/Helpers/order.php
   ~~~
       'tid', 'shipping_fee', 'brand_id', 'cloud_id', 'cloud_goodsname', 'dis_commission', 'is_distribution'
   ~~~
   下添加
   ~~~
       if (file_exists(app_path('Custom/Distribute'))) 
           {
               // 开发
               array_push($goodsWhere['goods_select'], 'dis_commission_type');
           }
   ~~~
   
   ~~~
   $order_other = app(BaseRepository::class)->getArrayfilterTable($order, 'order_info');
   ~~~
   下
   ~~~
   if (isset($order_other['pay_status']) && $order_other['pay_status'] == 2) {
       //开发  获取订单信息,进行分销商活动处理
       app(\App\Custom\Distribute\Services\DistributeManageService::class)->pay_order_activity($order_id);
   }
   ~~~
   2. app/Helpers/payment.php
   ~~~
               $order_sale = [
                   'order_id' => $order_id,
                   'pay_status' => $pay_status,
                   'shipping_status' => $order['shipping_status']
               ];
   ~~~
   下添加
   ~~~
       //开发  获取订单信息,进行分销商活动处理
       pp(\App\Custom\Distribute\Services\DistributeManageService::class)->pay_order_activity($order_id);
   ~~~
   3. app/Helpers/transaction.php
   中
   ~~~
       if ($seller_id) {
           app(CommissionService::class)->getOrderBillLog($other);
       }
   ~~~
   下添加
   ~~~
       //订单确认收货 判断是否符合分销商升级条件
       if (file_exists(app_path('Custom/Distribute'))) {
           app(\App\Custom\Distribute\Services\DrpCommonService::class)->drp_upgrade_main_con($user_id, 1, $order_id);
       }
   ~~~
   
   4. app/Http/Controller/UserController.php
   中
   ~~~
    Ucenter::uc_user_register($username, $user_pass);
   ~~~
   上面添加
   ~~~
   //用户注册成功 判断是否符合分销商升级条件
   if (file_exists(MOBILE_DRP)) {
       app(DrpCommonService::class)->drp_upgrade_main_con($other['user_id'], 2);
   }
   ~~~
   
   5. app/Modules/Admin/Controllers/OrderController.php
   ~~~
   elseif ('receive' == $operation) {
   
   下
   
   if ($seller_id) {
                           $this->commissionService->getOrderBillLog($other);
                       }
   ~~~
   下面添加
   ~~~
       //订单确认收货 判断是否符合分销商升级条件
       if (file_exists(app_path('Custom/Distribute'))) {
           app(DrpCommonService::class)->drp_upgrade_main_con($order['user_id'], 1, $order_id);
       }
   ~~~
   ---















手机端 vue 文件修改：
- ps: vue根目录 resources/client/src/, 以下所有目录说明都基于根目录。

router.js

import Drp from '@/pages/Custom/Ump/Drp/Index'
import DrpRegister from '@/pages/Custom/Ump/Drp/Detail/Register'
import DrpPurchase from '@/pages/Custom/Ump/Drp/Detail/Purchase'




