<?php

/**
 * 管理中心菜单数组
 */


$modules['04_order']['11_order_detection'] = 'order.php?act=order_detection';
$modules['04_order']['11_add_order'] = 'mc_order.php';
$modules['08_members']['11_users_add'] = 'mc_user.php';
$modules['04_order']['12_back_apply'] = 'order.php?act=return_list';
$modules['04_order']['11_order_delayed'] = 'order_delay.php?act=list';
$modules['02_cat_and_goods']['discuss_circle'] = 'discuss_circle.php?act=list';
$modules['17_merchants']['02_merchants_users_list'] = 'merchants_users_list.php?act=list';    // 入驻商家列表
$modules['17_merchants']['03_merchants_commission'] = 'merchants_commission.php?act=list';    // 商家商品佣金结算
$modules['17_merchants']['04_create_seller_grade'] = 'merchants_users_list.php?act=create_seller_grade';  // 入驻商家评分

if (!isset($_REQUEST['act_type'])) {
    $modules['17_merchants']['10_account_manage'] = 'merchants_account.php?act=account_manage&act_type=account';
} else {
    $address_account = '';
    if (isset($_REQUEST['log_id'])) {
        $address_account = "&log_id=" . $_REQUEST['log_id'];
    }

    $modules['17_merchants']['10_account_manage'] = 'merchants_account.php?act=account_manage&act_type=' . $_REQUEST['act_type'] . $address_account;
}

$modules['11_system']['09_warehouse_management'] = 'warehouse.php?act=list'; // 仓库
$modules['11_system']['09_region_area_management'] = 'region_area.php?act=list'; // 地区所属区域
$modules['19_merchants_store']['01_merchants_basic_info'] = 'index.php?act=merchants_first';         // 店铺基本信息设置

$modules['19_merchants_store']['09_merchants_upgrade'] = 'merchants_upgrade.php?act=list';         // 店铺升级  by kong grade

$modules['19_merchants_store']['10_visual_editing'] = 'visual_editing.php?act=templates';         // 店铺可视化装修


$modules['19_merchants_store']['11_touch_dashboard'] = 'touch_visual'; // 手机端可视化

//$modules['02_cat_and_goods']['warehouse_batch'] = 'goods_warehouse_batch.php?act=add'; // 仓库库存批量上传
//$modules['02_cat_and_goods']['area_batch'] = 'goods_area_batch.php?act=add'; // 商品地区价格批量上传
//$modules['02_cat_and_goods']['area_attr_batch'] = 'goods_area_attr_batch.php?act=add'; // 商品地区属性价格批量上传
$modules['02_cat_and_goods']['07_merchants_brand'] = 'merchants_brand.php?act=list';

$modules['02_cat_and_goods']['03_store_category_list'] = 'category_store.php?act=list'; //店铺分类
$modules['08_members']['12_user_address_list'] = 'user_address_log.php?act=list'; //店铺分类
$modules['04_order']['13_goods_inventory_logs'] = 'goods_inventory_logs.php?act=list';         // 申请流程列表
$modules['21_cloud']['01_cloud_services'] = 'index.php?act=cloud_services';

$modules['02_cat_and_goods']['01_goods_list'] = 'goods.php?act=list';         // 商品列表
$modules['02_cat_and_goods']['04_goods_lib_list'] = 'goods_lib.php?act=list';
$modules['02_cat_and_goods']['05_comment_manage'] = 'comment_manage.php?act=list';
$modules['02_cat_and_goods']['06_goods_brand_list'] = 'brand.php?act=list';
$modules['02_cat_and_goods']['08_goods_type'] = 'goods_type.php?act=manage';
$modules['02_cat_and_goods']['15_batch_edit'] = 'goods_batch.php?act=select'; // 商品批量修改
$modules['02_cat_and_goods']['16_goods_script'] = 'gen_goods_script.php?act=setup';
$modules['02_cat_and_goods']['17_tag_manage'] = 'tag_manage.php?act=list';
$modules['02_cat_and_goods']['goods_auto'] = 'goods_auto.php?act=list';
$modules['11_system']['website'] = 'website.php?act=list';//ecmoban
$modules['02_cat_and_goods']['gallery_album'] = 'gallery_album.php?act=list';//by kong
$modules['02_promotion']['02_snatch_list'] = 'snatch.php?act=list';
$modules['02_promotion']['03_seckill_list'] = 'seckill.php?act=list';
$modules['02_promotion']['04_bonustype_list'] = 'bonus.php?act=list';
$modules['02_promotion']['08_group_buy'] = 'group_buy.php?act=list';
$modules['02_promotion']['09_topic'] = 'topic.php?act=list';
$modules['02_promotion']['10_auction'] = 'auction.php?act=list';
$modules['02_promotion']['12_favourable'] = 'favourable.php?act=list';
$modules['02_promotion']['14_package_list'] = 'package.php?act=list';
$modules['02_promotion']['15_exchange_goods'] = 'exchange_goods.php?act=list';
$modules['02_promotion']['17_coupons'] = 'coupons.php?act=list';

$modules['02_promotion']['gift_gard_list'] = 'gift_gard.php?act=list';
$modules['02_promotion']['16_presale'] = 'presale.php?act=list';


$modules['04_order']['02_order_list'] = 'order.php?act=list';
$modules['04_order']['06_undispose_booking'] = 'goods_booking.php?act=list_all';
$modules['04_order']['09_delivery_order'] = 'order.php?act=delivery_list';
$modules['04_order']['10_back_order'] = 'order.php?act=back_list';
$modules['04_order']['11_complaint'] = 'complaint.php?act=list';

$modules['05_banner']['ad_position'] = 'ad_position.php?act=list';
$modules['05_banner']['ad_list'] = 'ads.php?act=list';

$modules['06_stats']['flow_stats'] = 'flow_stats.php?act=view';
$modules['06_stats']['searchengine_stats'] = 'searchengine_stats.php?act=view';
$modules['06_stats']['z_clicks_stats'] = 'adsense.php?act=list';
$modules['06_stats']['report_order'] = 'order_stats.php?act=list';
$modules['06_stats']['report_sell'] = 'sale_general.php?act=list';
$modules['06_stats']['sale_list'] = 'sale_list.php?act=list';
$modules['06_stats']['sell_stats'] = 'sale_order.php?act=goods_num';
$modules['06_stats']['report_users'] = 'users_order.php?act=order_num';
$modules['06_stats']['visit_buy_per'] = 'visit_sold.php?act=list';

$modules['07_content']['03_article_list'] = 'article.php?act=list';
$modules['07_content']['02_articlecat_list'] = 'articlecat.php?act=list';
$modules['07_content']['vote_list'] = 'vote.php?act=list';
$modules['07_content']['article_auto'] = 'article_auto.php?act=list';


$modules['08_members']['03_users_list'] = 'users.php?act=list';
$modules['08_members']['04_users_add'] = 'users.php?act=add';
$modules['08_members']['05_user_rank_list'] = 'user_rank.php?act=list';
$modules['08_members']['06_list_integrate'] = 'integrate.php?act=list';
$modules['08_members']['08_unreply_msg'] = 'user_msg.php?act=list_all';
$modules['08_members']['09_user_account'] = 'user_account.php?act=list';
$modules['08_members']['10_user_account_manage'] = 'user_account_manage.php?act=list';
$modules['08_members']['13_user_baitiao_info'] = 'user_baitiao_log.php?act=list'; //@author bylu 会员白条;


$modules['10_priv_admin']['admin_logs'] = 'admin_logs.php?act=list';
$modules['10_priv_admin']['02_admin_seller'] = 'privilege_seller.php?act=list';//by kong

$modules['10_priv_admin']['privilege_seller'] = 'privilege.php?act=modif';//by kong
$modules['10_priv_admin']['admin_role'] = 'role.php?act=list';
$modules['10_priv_admin']['agency_list'] = 'agency.php?act=list';

$modules['10_priv_admin']['admin_message'] = 'message.php?act=list';   //管理员留言

$modules['11_system']['01_shop_config'] = 'shop_config.php?act=list_edit';
$modules['11_system']['02_payment_list'] = 'payment.php?act=list';
$modules['11_system']['03_shipping_list'] = 'shipping.php?act=list';
$modules['11_system']['shipping_date_list'] = 'shipping.php?act=date_list'; //自营指定配送时间
$modules['11_system']['05_area_list'] = 'area_manage.php?act=list';
$modules['11_system']['07_cron_schcron'] = 'cron.php?act=list';
$modules['11_system']['08_friendlink_list'] = 'friend_link.php?act=list';
$modules['11_system']['sitemap'] = 'sitemap.php';
$modules['11_system']['check_file_priv'] = 'check_file_priv.php?act=check';
$modules['11_system']['captcha_manage'] = 'captcha_manage.php?act=main';
$modules['11_system']['ucenter_setup'] = 'integrate.php?act=setup&code=ucenter';
$modules['11_system']['navigator'] = 'navigator.php?act=list';
$modules['11_system']['16_reg_fields'] = 'reg_fields.php?act=list';
$modules['11_system']['oss_configure'] = 'oss_configure.php?act=list';


$modules['12_template']['02_template_select'] = 'template.php?act=list';
$modules['12_template']['03_template_setup'] = 'template.php?act=setup';
$modules['12_template']['04_template_library'] = 'template.php?act=library';
$modules['12_template']['05_edit_languages'] = 'edit_languages.php?act=list';
$modules['12_template']['06_template_backup'] = 'template.php?act=backup_setting';

$modules['13_backup']['02_db_manage'] = 'database.php?act=backup';
$modules['13_backup']['03_db_optimize'] = 'database.php?act=optimize';
$modules['13_backup']['04_sql_query'] = 'sql.php?act=main';
$modules['13_backup']['convert'] = 'convert.php?act=main';

$modules['15_rec']['affiliate'] = 'affiliate.php?act=list';
$modules['15_rec']['affiliate_ck'] = 'affiliate_ck.php?act=list';

/*门店管理*/
$modules['10_offline_store']['12_offline_store'] = 'offline_store.php?act=list';         // 门店管理  by kong
$modules['10_offline_store']['2_order_stats'] = 'offline_store.php?act=order_stats';         // 门店订单统计  by kong

//快递鸟
$modules['19_merchants_store']['order_print_setting'] = 'tp_api.php?act=order_print_setting';

$GLOBALS['modules'] = $modules;
