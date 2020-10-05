<?php

/**
 * 管理中心菜单数组
 */

$modules['01_system']['user_keywords_list'] = 'keywords_manage.php?act=list';
$modules['08_members']['12_user_address_list'] = 'user_address_log.php?act=list'; //店铺分类
$modules['02_cat_and_goods']['sale_notice'] = 'sale_notice.php?act=list'; //降价通知
$modules['02_cat_and_goods']['discuss_circle'] = 'discuss_circle.php?act=list';
$modules['02_cat_and_goods']['001_goods_setting'] = 'goods.php?act=step_up'; // 商品设置
$modules['02_cat_and_goods']['01_goods_list'] = 'goods.php?act=list';         // 商品列表
$modules['02_cat_and_goods']['03_category_manage'] = 'category.php?act=list';
$modules['02_cat_and_goods']['05_comment_manage'] = 'comment_manage.php?act=list';
$modules['02_cat_and_goods']['06_goods_brand'] = 'brand.php?act=list';
$modules['02_cat_and_goods']['08_goods_type'] = 'goods_type.php?act=manage';
$modules['02_cat_and_goods']['15_batch_edit'] = 'goods_batch.php?act=select'; // 商品批量修改
$modules['02_cat_and_goods']['gallery_album'] = 'gallery_album.php?act=list';//by kong
$modules['02_cat_and_goods']['goods_report'] = 'goods_report.php?act=report_conf';//by kong 商品举报
$modules['02_cat_and_goods']['20_goods_lib'] = 'goods_lib.php?act=list';//by liu
$modules['02_cat_and_goods']['21_goods_lib_cat'] = 'goods_lib_cat.php?act=list';//by liu
$modules['02_promotion']['02_marketing_center'] = 'favourable.php?act=marketing_center';
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
$modules['02_promotion']['18_value_card'] = 'value_card.php?act=list';// VC liu
$modules['02_promotion']['gift_gard_list'] = 'gift_gard.php?act=list';
$modules['02_promotion']['16_presale'] = 'presale.php?act=list';
$modules['03_goods_storage']['01_goods_storage_put'] = 'goods_inventory_logs.php?act=list&step=put';
$modules['03_goods_storage']['02_goods_storage_out'] = 'goods_inventory_logs.php?act=list&step=out';
$modules['04_order']['02_order_list'] = 'order.php?act=list';
$modules['04_order']['06_undispose_booking'] = 'goods_booking.php?act=list_all';
$modules['04_order']['08_add_order'] = 'order.php?act=add';
$modules['04_order']['09_delivery_order'] = 'order.php?act=delivery_list';
$modules['04_order']['10_back_order'] = 'order.php?act=back_list';
$modules['04_order']['13_complaint'] = 'complaint.php?act=complaint_conf';
$modules['04_order']['11_order_detection'] = 'order.php?act=order_detection';
$modules['04_order']['11_add_order'] = 'mc_order.php';
$modules['04_order']['11_back_cause'] = 'order.php?act=back_cause_list';
$modules['04_order']['12_back_apply'] = 'order.php?act=return_list';
$modules['04_order']['11_order_delayed'] = 'order_delay.php?act=list'; //延迟收货

$modules['05_banner']['ad_position'] = 'ad_position.php?act=list';
$modules['05_banner']['ad_list'] = 'ads.php?act=list';

/*统计*/
$modules['06_stats']['report_guest'] = 'guest_stats.php?act=list';           //客户统计
$modules['06_stats']['report_order'] = 'order_stats.php?act=list';           //订单统计
$modules['06_stats']['sale_list'] = 'sale_list.php?act=list';             //销售明细
$modules['06_stats']['report_users'] = 'users_order.php?act=order_num';      //会员排行
$modules['06_stats']['visit_buy_per'] = 'visit_sold.php?act=list';            //访问购买率
$modules['06_stats']['exchange_count'] = 'exchange_detail.php?act=detail';     //积分明细

$modules['06_stats']['01_shop_stats'] = 'shop_stats.php?act=new';     //店铺统计
$modules['06_stats']['02_user_stats'] = 'user_stats.php?act=new';     //会员统计
$modules['06_stats']['03_sell_analysis'] = 'sell_analysis.php?act=sales_volume';     //销售分析
$modules['06_stats']['04_industry_analysis'] = 'industry_analysis.php?act=list';     //行业分析

/*资金*/
$modules['31_fund']['01_summary_of_money'] = 'fund_stats.php?act=summary_of_money';
$modules['31_fund']['02_member_account'] = 'fund_stats.php?act=member_account';
$modules['31_fund']['05_finance_analysis'] = 'finance_analysis.php?act=settlement_stats';     //财务统计

$modules['07_content']['03_article_list'] = 'article.php?act=list';
$modules['07_content']['02_articlecat_list'] = 'articlecat.php?act=list';
$modules['07_content']['article_auto'] = 'article_auto.php?act=list';
$modules['07_content']['03_visualnews'] = 'visualnews.php?act=visual'; //cms频道可视化

$modules['08_members']['03_users_list'] = 'users.php?act=list';
$modules['08_members']['06_list_integrate'] = 'integrate.php?act=list';
$modules['08_members']['08_unreply_msg'] = 'user_msg.php?act=list_all';
$modules['08_members']['09_user_account'] = 'user_account.php?act=list';
$modules['08_members']['10_user_account_manage'] = 'user_account_manage.php?act=list';
$modules['08_members']['13_user_baitiao_info'] = 'user_baitiao_log.php?act=list'; //@author bylu 会员白条;
$modules['08_members']['15_user_vat_info'] = 'user_vat.php?act=list'; //liu
$modules['08_members']['16_reg_fields'] = 'reg_fields.php?act=list';

$modules['10_priv_admin']['admin_logs'] = 'admin_logs.php?act=list';
$modules['10_priv_admin']['01_admin_list'] = 'privilege.php?act=list';
$modules['10_priv_admin']['02_admin_seller'] = 'privilege_seller.php?act=list';//by kong
$modules['10_priv_admin']['admin_role'] = 'role.php?act=list';
$modules['10_priv_admin']['agency_list'] = 'agency.php?act=list';

$modules['10_priv_admin']['admin_message'] = 'message.php?act=list';   //管理员留言

$modules['01_system']['01_shop_config'] = 'shop_config.php?act=list_edit';
$modules['01_system']['02_payment_list'] = 'payment.php?act=list';
$modules['01_system']['03_area_shipping'] = 'shipping.php?act=list';
$modules['01_system']['07_cron_schcron'] = 'cron.php?act=list';
$modules['01_system']['08_friendlink_list'] = 'friend_link.php?act=list';
$modules['01_system']['09_partnerlink_list'] = 'friend_partner.php?act=list';
$modules['01_system']['sitemap'] = 'sitemap.php';
$modules['01_system']['check_file_priv'] = 'check_file_priv.php?act=check';
$modules['01_system']['captcha_manage'] = 'captcha_manage.php?act=main';
$modules['01_system']['ucenter_setup'] = 'integrate.php?act=setup&code=ucenter';
$modules['01_system']['navigator'] = 'navigator.php?act=list';
$modules['01_system']['seo'] = 'seo.php?act=index';
$modules['01_system']['10_filter_words'] = 'filter'; // 过滤词

if ($GLOBALS['_CFG']['openvisual'] == 1) {
    //可视化装修首页
    $modules['12_template']['01_visualhome'] = 'visualhome.php?act=list';
}

$modules['13_backup']['04_sql_query'] = 'sql.php?act=main';
$modules['13_backup']['09_clear_cache'] = 'index.php?act=clear_cache';//清除缓存 by kong
$modules['15_rec']['affiliate'] = 'affiliate.php?act=list';
$modules['15_rec']['affiliate_ck'] = 'affiliate_ck.php?act=list';
$modules['09_crowdfunding']['01_crowdfunding_list'] = 'zc_project.php?act=list';         // 众筹商品列表
$modules['09_crowdfunding']['02_crowdfunding_cat'] = 'zc_category.php?act=list';          // 众筹分类
$modules['09_crowdfunding']['03_project_initiator'] = 'zc_initiator.php?act=list';          // 项目发起人
$modules['09_crowdfunding']['04_topic_list'] = 'zc_topic.php?act=list';          // 众筹话题
$modules['24_sms']['01_sms_setting'] = 'sms_setting.php?act=step_up';          // 短信设置
$modules['24_sms']['dscsms_configure'] = 'dscsms_configure.php?act=list';          // 短信设置
$modules['25_file']['oss_configure'] = 'oss_configure.php?act=list';          // 阿里OSS
$modules['25_file']['obs_configure'] = 'obs_configure.php?act=list';          // 华为OBS
$modules['25_file']['01_cloud_setting'] = 'cloud_setting.php?act=step_up';    // 文件存储设置
$modules['26_login']['website'] = 'website.php?act=list';          // 第三方登录
$modules['27_interface']['open_api'] = 'open_api.php?act=list';          // 开放接口
$modules['21_cloud']['01_cloud_services'] = 'index.php?act=cloud_services';
$modules['21_cloud']['02_platform_recommend'] = 'index.php?act=platform_recommend';
$modules['21_cloud']['03_best_recommend'] = 'index.php?act=best_recommend';

// 手机端菜单
$modules['20_ectouch']['01_oauth_admin'] = 'touch_oauth'; // 授权登录
$modules['20_ectouch']['03_touch_ads'] = 'touch_ads.php?act=list';
$modules['20_ectouch']['04_touch_ad_position'] = 'touch_ad_position.php?act=list';
$modules['20_ectouch']['05_touch_dashboard'] = 'touch_visual'; // 手机端可视化

/* 邮件管理 */
$modules['16_email_manage']['01_mail_settings'] = 'shop_config.php?act=mail_settings';
$modules['16_email_manage']['02_attention_list'] = 'attention_list.php?act=list';
$modules['16_email_manage']['03_email_list'] = 'email_list.php?act=list';
$modules['16_email_manage']['04_magazine_list'] = 'magazine_list.php?act=list';
$modules['16_email_manage']['05_view_sendlist'] = 'view_sendlist.php?act=list';
$modules['16_email_manage']['06_mail_template_manage'] = 'mail_template.php?act=list';

$modules['17_merchants']['01_seller_stepup'] = 'merchants_steps.php?act=step_up';         // 店铺设置
$modules['17_merchants']['02_merchants_users_list'] = 'merchants_users_list.php?act=list';    // 入驻商家列表
$modules['17_merchants']['03_merchants_commission'] = 'merchants_commission.php?act=list';    // 商家商品佣金结算
$modules['17_merchants']['09_seller_domain'] = 'seller_domain.php?act=list';         // 二级域名列表  by kong
$modules['17_merchants']['12_seller_account'] = 'merchants_account.php?act=list&act_type=merchants_seller_account'; //商家账户管理
$modules['17_merchants']['13_comment_seller_rank'] = 'comment_seller.php?act=list';
$modules['17_merchants']['12_seller_store'] = 'offline_store.php?act=list&type=1';
$modules['17_merchants']['16_users_real'] = 'user_real.php?act=list&user_type=1';

/* 自营信息 */
$modules['19_self_support']['01_self_offline_store'] = 'offline_store.php?act=list';
$modules['19_self_support']['02_self_order_stats'] = 'offline_store.php?act=order_stats';
$modules['19_self_support']['03_self_support_info'] = 'index.php?act=merchants_first';

//模板
$modules['12_template']['02_template_select'] = 'template.php?act=list';
$modules['12_template']['04_template_library'] = 'template.php?act=library';
$modules['12_template']['06_template_backup'] = 'template.php?act=backup_setting';

//短信群发
$modules['08_members']['17_mass_sms'] = 'mass_sms.php?act=list';
$modules['03_goods_storage']['suppliers_list'] = 'suppliers.php?act=list';

//快递鸟、电子面单
$modules['27_interface']['kdniao'] = 'tp_api.php?act=kdniao';
$modules['01_system']['order_print_setting'] = 'tp_api.php?act=order_print_setting';

//首页
$modules['00_home']['01_admin_core'] = 'index.php?act=main';
$modules['00_home']['02_operation_flow'] = 'index.php?act=operation_flow';
$modules['00_home']['03_novice_guide'] = 'index.php?act=novice_guide';

// 左侧菜单分类
$menu_top['home'] = '00_home';//首页
$menu_top['menuplatform'] = '05_banner,07_content,08_members,10_priv_admin,01_system,13_backup,16_email_manage,12_template,19_self_support';//平台
$menu_top['menushopping'] = '02_cat_and_goods,02_promotion,04_order,09_crowdfunding,15_rec,17_merchants,18_batch_manage,03_goods_storage,supply_and_demand,18_region_store';//商城
$menu_top['finance'] = '06_stats,06_seo';//财务
$menu_top['third_party'] = '24_sms,25_file,26_login,27_interface';//第三方服务
$menu_top['ectouch'] = '20_ectouch,22_wechat,23_drp,24_wxapp';//手机
$menu_top['menuinformation'] = '21_cloud';//资讯

$GLOBALS['modules'] = $modules;
$GLOBALS['menu_top'] = $menu_top;
