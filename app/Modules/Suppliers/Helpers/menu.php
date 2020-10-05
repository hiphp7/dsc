<?php

//商品
$modules['01_suppliers_goods']['01_goods_list'] = 'goods.php?act=list'; //商品列表
$modules['01_suppliers_goods']['08_goods_type'] = 'goods_type.php?act=manage'; //商品类型列表
$modules['01_suppliers_goods']['03_goods_transport'] = 'goods_transport.php?act=list'; //运费模板列表
$modules['01_suppliers_goods']['04_gallery_album'] = 'gallery_album.php?act=list'; //图片库

//订单
$modules['02_suppliers_order']['01_order_list'] = 'order.php?act=list';
$modules['02_suppliers_order']['09_delivery_order'] = 'order.php?act=delivery_list';
$modules['02_suppliers_order']['12_back_apply'] = 'order.php?act=return_list';

//商家
if (!isset($_REQUEST['act_type'])) {
    $modules['03_suppliers']['10_account_manage'] = 'suppliers_account.php?act=account_manage&act_type=account';
} else {
    $address_account = '';
    if (isset($_REQUEST['log_id'])) {
        $address_account = "&log_id=" .$_REQUEST['log_id'];
    }
    $modules['03_suppliers']['10_account_manage'] = 'suppliers_account.php?act=account_manage&act_type=' . $_REQUEST['act_type'] . $address_account;
}
$modules['03_suppliers']['02_suppliers_commission']         	= 'suppliers_commission.php?act=order_list';

//统计
$modules['04_suppliers_sale_order_stats']['suppliers_stats']         	= 'suppliers_stats.php?act=list';
$modules['04_suppliers_sale_order_stats']['suppliers_sale_list']         	= 'suppliers_sale_list.php?act=order_list';

//权限
$modules['10_priv_admin']['admin_logs']             = 'admin_logs.php?act=list';
$modules['10_priv_admin']['02_admin_seller']        = 'privilege_suppliers.php?act=list';
$modules['10_priv_admin']['privilege_seller']       = 'privilege.php?act=modif';

//系统
$modules['11_system']['order_print_setting'] = 'tp_api.php?act=order_print_setting';

$GLOBALS['modules'] = $modules;
