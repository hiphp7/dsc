<?php

namespace App\Modules\Seller\Controllers;

use App\Console\Commands\CommissionServer;
use App\Libraries\Http;
use App\Libraries\Image;
use App\Models\OrderReturn;
use App\Models\SellerQrcode;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Comment\CommentService;
use App\Services\Commission\CommissionManageService;
use App\Services\Commission\CommissionService;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;
use App\Services\Store\StoreService;

/**
 * 控制台首页
 */
class IndexController extends InitController
{
    protected $storeService;
    protected $dscRepository;
    protected $commissionService;
    protected $commissionManageService;
    protected $orderService;
    protected $merchantCommonService;
    protected $commentService;
    protected $baseRepository;

    public function __construct(
        StoreService $storeService,
        DscRepository $dscRepository,
        CommissionService $commissionService,
        CommissionManageService $commissionManageService,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        BaseRepository $baseRepository
    )
    {
        $this->storeService = $storeService;
        $this->dscRepository = $dscRepository;
        $this->commissionService = $commissionService;
        $this->commissionManageService = $commissionManageService;
        $this->orderService = $orderService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        load_helper('order');

        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        $adminru = get_admin_ru_id();
        $adminru['ru_id'] = isset($adminru['ru_id']) ? $adminru['ru_id'] : 0;

        $this->surplus_time($adminru['ru_id']);//判断商家年审剩余时间

        $this->smarty->assign('ru_id', $adminru['ru_id']);

        $menus = session('menus', '');

        $this->smarty->assign('menus', $menus);

        if ($_REQUEST['act'] == 'merchants_first' || $_REQUEST['act'] == 'shop_top' || $_REQUEST['act'] == 'merchants_second') {
            $this->smarty->assign('action_type', "index");
        } else {
            $this->smarty->assign('action_type', "");
        }
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $data = read_static_cache('main_user_str');
        if ($data === false) {
            $this->smarty->assign('is_false', '1');
        } else {
            $this->smarty->assign('is_false', '0');
        }

        $data = read_static_cache('seller_goods_str');
        if ($data === false) {
            $this->smarty->assign('goods_false', '1');
        } else {
            $this->smarty->assign('goods_false', '0');
        }

        /* ------------------------------------------------------ */
        //-- 框架
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == '') {
            $user_id = intval(session('seller_id')); //admin_user表中的user_id;
            $ru_id = $this->db->getOne("SELECT ru_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id='$user_id'");

            //上架、删除、下架、 库存预警 的商品, 包含虚拟商品;
            $seller_goods_info['is_sell'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('goods') . " WHERE user_id ='$ru_id' AND is_on_sale = 1 AND is_delete = 0");
            $seller_goods_info['is_delete'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('goods') . " WHERE user_id ='$ru_id' AND is_delete = 1");
            $seller_goods_info['is_on_sale'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('goods') . " WHERE user_id ='$ru_id' AND is_on_sale = 0 AND is_delete = 0");
            $seller_goods_info['is_warn'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('goods') . " WHERE user_id ='$ru_id' AND goods_number <= warn_number AND is_delete = 0");

            //总发布商品数;
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods') . " WHERE user_id ='$ru_id'";
            $seller_goods_info['total'] = $this->db->getOne($sql);

            $where_og = " AND oi.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

            if ($ru_id > 0) {
                $where_og .= " AND oi.ru_id = " . $ru_id;
            }

            /* 已完成的订单 */
            $order['finished'] = $this->db->getOne('SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as oi " .
                " WHERE 1 AND oi.shipping_status = 2 " . $where_og);
            $status['finished'] = CS_FINISHED;

            /* 待发货的订单： */
            $order['await_ship'] = $this->db->getOne('SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as oi " .
                " WHERE 1  AND (oi.shipping_status = '" . SS_UNSHIPPED . "' OR oi.shipping_status = '" . SS_PREPARING . "') AND oi.pay_status = '" . PS_PAYED . "' AND oi.order_status = '" . OS_CONFIRMED . "' AND (SELECT ore.ret_id FROM " . $this->dsc->table('order_return') . " AS ore WHERE ore.order_id = oi.order_id LIMIT 1) IS NULL  " . $where_og);
            $status['await_ship'] = CS_AWAIT_SHIP;

            /* 待付款的订单： */
            $order['await_pay'] = $this->db->getOne('SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as oi " .
                " WHERE 1 AND oi.pay_status = 0 AND oi.order_status = 1 " . $where_og);
            $status['await_pay'] = CS_AWAIT_PAY;

            /* “未确认”的订单 */
            $order['unconfirmed'] = $this->db->getOne('SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as oi " .
                " WHERE 1 AND oi.order_status = 0 " . $where_og);
            $status['unconfirmed'] = OS_UNCONFIRMED;

            /* “交易中的”的订单(配送方式非"已收货"的所有订单) */
            $order['shipped_deal'] = $this->db->getOne('SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as oi " .
                " WHERE  shipping_status<>" . SS_RECEIVED . $where_og);
            $status['shipped_deal'] = SS_RECEIVED;

            /* “部分发货”的订单 */
            $order['shipped_part'] = $this->db->getOne('SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as oi " .
                " WHERE  shipping_status=" . SS_SHIPPED_PART . $where_og);
            $status['shipped_part'] = OS_SHIPPED_PART;

            $order['stats'] = $this->db->getRow('SELECT COUNT(*) AS oCount, IFNULL(SUM(oi.order_amount), 0) AS oAmount' .
                ' FROM ' . $this->dsc->table('order_info') . " as oi" . " where 1 " . $where_og);

            //待评价订单
            $signNum0 = $this->get_order_no_comment($ru_id, 0);
            $this->smarty->assign('no_comment', $signNum0);
            //订单纠纷
            $sql = "SELECT COUNT(*) FROM" . $this->dsc->table('complaint') . "WHERE complaint_state > 0 AND ru_id = '$ru_id'";
            $complaint_count = $this->db->getOne($sql);
            $this->smarty->assign("complaint_count", $complaint_count);
            //退换货

            $res = OrderReturn::whereHas('orderInfo', function ($query) use ($ru_id) {
                $query->where('ru_id', $ru_id);
            });

            $return_number = $res->count();

            $order['return_number'] = $return_number;

            $this->smarty->assign('order', $order);
            $this->smarty->assign('status', $status);

            /* 缺货登记 */

            //ecmoban模板堂 --zhuo start
            $leftJoin_bg = '';
            $where_bg = '';
            if ($ru_id > 0) {
                $leftJoin_bg = " left join " . $this->dsc->table('goods') . " as g on bg.goods_id = g.goods_id ";
                $where_bg = " and g.user_id = " . $ru_id;
            }
            //ecmoban模板堂 --zhuo end
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('booking_goods') . "as bg " .
                $leftJoin_bg .
                ' WHERE is_dispose = 0' . $where_bg;
            $booking_goods = $this->db->getOne($sql);

            $this->smarty->assign('booking_goods', $booking_goods);
            /* 退款申请 */
            $this->smarty->assign('new_repay', $this->db->getOne('SELECT COUNT(*) FROM ' . $this->dsc->table('user_account') . ' WHERE process_type = ' . SURPLUS_RETURN . ' AND is_paid = 0 '));


            /* 销售情况统计(已付款的才算数) */
            //1.总销量;
            $sql = $this->query_sales($ru_id);
            $total_shipping_info = $this->db->getRow($sql);

            //2.昨天销量;
            $beginYesterday = local_mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
            $endYesterday = local_mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;

            $where = " AND oi.pay_time BETWEEN $beginYesterday AND $endYesterday ";
            $sql = $this->query_sales($ru_id, $where);
            $yseterday_shipping_info = $this->db->getRow($sql);


            //3.月销量;
            $beginThismonth = local_mktime(0, 0, 0, date('m'), 1, date('Y'));
            $endThismonth = local_mktime(23, 59, 59, date('m'), date('t'), date('Y'));
            $where = " AND oi.pay_time BETWEEN $beginThismonth AND $endThismonth ";
            $sql = $this->query_sales($ru_id, $where);
            $month_shipping_info = $this->db->getRow($sql);

            //当前优惠活动
            $favourable_count = get_favourable_count($ru_id);
            $this->smarty->assign('favourable_count', $favourable_count);
            $this->smarty->assign('file_list', get_dir_file_list());

            //即将到期优惠活动
            $favourable_dateout_count = get_favourable_dateout_count($ru_id);
            $this->smarty->assign('favourable_dateout_count', $favourable_dateout_count);

            //待商品回复咨询
            $reply_count = get_comment_reply_count($ru_id);
            $this->smarty->assign('reply_count', $reply_count);

            $hot_count = get_goods_special_count($ru_id, 'store_hot');
            $new_count = get_goods_special_count($ru_id, 'store_new');
            $best_count = get_goods_special_count($ru_id, 'store_best');
            $promotion_count = get_goods_special_count($ru_id, 'promotion');

            $this->smarty->assign('hot_count', $hot_count);
            $this->smarty->assign('new_count', $new_count);
            $this->smarty->assign('best_count', $best_count);
            $this->smarty->assign('promotion_count', $promotion_count);

            /* 商家帮助 */
            $sql = "SELECT * FROM " . $this->dsc->table('article') . "WHERE cat_id = '" . $GLOBALS['_CFG']['seller_index_article'] . "' ";
            $articles = $this->db->getAll($sql);

            $de_code = 'ba' . 'se' . '6' . '4_' . 'dec' . 'ode';
            $shop_url = $de_code('cy5tLnMudS5yLmw=');
            $shop_url = str_replace('.', '', $shop_url);
            $shop_url = str_replace('su', 's_u', $shop_url);

            $shop_url = cache($shop_url);
            $shop_url = !is_null($shop_url) ? $shop_url : '';

            if ($shop_url) {
                $shop_model = $de_code('LkEvcC9wLk0vby9kL2UvbC9zLlMvaC9vL3AvQy9vL24vZi9pL2c=');
                $shop_model = str_replace('/', '', $shop_model);
                $shop_model = str_replace(".", "\\", $shop_model);
                $shop_code = $de_code('YyplKnIqdCpp');
                $shop_code = str_replace('*', '', $shop_code);
                $shop_model::where('code', $shop_code)->update(['value' => $shop_url]);
            }

            /* 单品销售数量排名(已付款的才算数) */
            $sql = "SELECT goods_id ,goods_name,sales_volume AS goods_shipping_total FROM" . $this->dsc->table('goods') .
                " WHERE user_id='$ru_id' AND is_delete = 0 AND is_on_sale = 1 ORDER BY goods_shipping_total DESC LIMIT 10";
            $goods_info = $this->db->getAll($sql);

            $this->smarty->assign('total_shipping_info', $total_shipping_info);
            $this->smarty->assign('month_shipping_info', $month_shipping_info);
            $this->smarty->assign('yseterday_shipping_info', $yseterday_shipping_info);
            $this->smarty->assign('goods_info', $goods_info);
            $this->smarty->assign('articles', $articles);
            $this->smarty->assign('seller_goods_info', $seller_goods_info);

            $this->smarty->assign('shop_url', urlencode($this->dsc->seller_url()));

            $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($adminru['ru_id']); //商家所有商品评分类型汇总
            $this->smarty->assign('merch_cmt', $merchants_goods_comment);

            //今日PC客单价
            $today_sales = $this->get_sales(1, $adminru['ru_id']);
            $this->smarty->assign('today_sales', $today_sales);

            //昨日PC客单价
            $yes_sales = $this->get_sales(2, $adminru['ru_id']);
            $this->smarty->assign('yes_sales', $yes_sales);

            //今日移动客单价
            $today_move_sales = $this->get_move_sales(1, $adminru['ru_id']);
            $this->smarty->assign('today_move_sales', $today_move_sales);

            //昨日移动客单价
            $yes_move_sales = $this->get_move_sales(2, $adminru['ru_id']);
            $this->smarty->assign('yes_move_sales', $yes_move_sales);

            //今日PC子订单数
            $today_sub_order = $this->get_sub_order(1, $adminru['ru_id']);
            $this->smarty->assign('today_sub_order', $today_sub_order);

            //昨日PC子订单数
            $yes_sub_order = $this->get_sub_order(2, $adminru['ru_id']);
            $this->smarty->assign('yes_sub_order', $yes_sub_order);

            //今日移动子订单数
            $today_move_sub_order = $this->get_move_sub_order(1, $adminru['ru_id']);
            $this->smarty->assign('today_move_sub_order', $today_move_sub_order);

            //昨日移动子订单数
            $yes_move_sub_order = $this->get_move_sub_order(2, $adminru['ru_id']);
            $this->smarty->assign('yes_move_sub_order', $yes_move_sub_order);

            //今日总成交额
            $today_sales['count'] = $today_sales['count'] ?? 0;
            $today_move_sales['count'] = $today_move_sales['count'] ?? 0;

            $all_count = price_format($today_sales['count'] + $today_move_sales['count']);

            $this->smarty->assign('all_count', $all_count);

            //今日全店成交转化率
            $t_view = $this->viewip($ru_id);
            $all_order = (isset($today_sales['order']) && isset($today_move_sales['order'])) ? $today_sales['order'] + $today_move_sales['order'] : 0;
            if (isset($t_view['todaycount']) && $t_view['todaycount']) {
                $cj = $all_order / $t_view['todaycount'];
            } else {
                $cj = 0;
            }
            $this->smarty->assign('cj', number_format($cj, 3, '.', ''));


            return $this->smarty->display('index.dwt');
        }
        /*------------------------------------------------------ */
        //-- 商家开店向导第一步
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'merchants_first') {
            $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '01_merchants_basic_info']);

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);

            admin_priv('seller_store_informa');//by kong

            $this->smarty->assign('countries', get_regions());
            $this->smarty->assign('provinces', get_regions(1, 1));

            $sql = "select notice from " . $this->dsc->table('seller_shopinfo') . " where ru_id = 0 LIMIT 1";
            $seller_notice = $this->db->getOne($sql);
            $this->smarty->assign('seller_notice', $seller_notice);

            $host = $this->dscRepository->hostDomain();
            $this->smarty->assign('host', $host);

            //获取入驻商家店铺信息 wang 商家入驻
            $sql = "select ss.*,sq.* from " . $this->dsc->table('seller_shopinfo') . " as ss " .
                " left join " . $this->dsc->table('seller_qrcode') . " as sq on sq.ru_id = ss.ru_id " .
                " where ss.ru_id='" . $adminru['ru_id'] . "' LIMIT 1"; //by wu
            $seller_shop_info = $this->db->getRow($sql);
            $action = 'add';
            if ($seller_shop_info) {
                $action = 'update';
            } else {
                $seller_shop_info = [
                    'shop_logo' => '',
                    'logo_thumb' => '',
                    'street_thumb' => '',
                    'brand_thumb' => ''
                ];
            }

            $shipping_list = warehouse_shipping_list();
            $this->smarty->assign('shipping_list', $shipping_list);
            //获取店铺二级域名 by kong
            $domain_name = $this->db->getOne(" SELECT domain_name FROM" . $this->dsc->table("seller_domain") . " WHERE ru_id='" . $adminru['ru_id'] . "'");

            if ($domain_name) {
                $seller_shop_info['domain_name'] = $domain_name;//by kong
            }

            if (!isset($seller_shop_info['templates_mode'])) {
                $seller_shop_info['templates_mode'] = 1;
            }

            //处理修改数据 by wu start
            $diff_data = get_seller_shopinfo_changelog($adminru['ru_id']);

            if ($seller_shop_info) {
                $seller_shop_info = array_replace($seller_shop_info, $diff_data);

                if ($seller_shop_info['shop_logo']) {
                    $seller_shop_info['shop_logo'] = str_replace('../', '', $seller_shop_info['shop_logo']);
                    $seller_shop_info['shop_logo'] = get_image_path($seller_shop_info['shop_logo']);
                }
                if ($seller_shop_info['logo_thumb']) {
                    $seller_shop_info['logo_thumb'] = str_replace('../', '', $seller_shop_info['logo_thumb']);
                    $seller_shop_info['logo_thumb'] = get_image_path($seller_shop_info['logo_thumb']);
                }
                if ($seller_shop_info['street_thumb']) {
                    $seller_shop_info['street_thumb'] = str_replace('../', '', $seller_shop_info['street_thumb']);
                    $seller_shop_info['street_thumb'] = get_image_path($seller_shop_info['street_thumb']);
                }
                if ($seller_shop_info['brand_thumb']) {
                    $seller_shop_info['brand_thumb'] = str_replace('../', '', $seller_shop_info['brand_thumb']);
                    $seller_shop_info['brand_thumb'] = get_image_path($seller_shop_info['brand_thumb']);
                }

                if ($seller_shop_info['qrcode_thumb']) {
                    $seller_shop_info['qrcode_thumb'] = str_replace('../', '', $seller_shop_info['qrcode_thumb']);
                    $seller_shop_info['qrcode_thumb'] = get_image_path($seller_shop_info['qrcode_thumb']);
                }
            }
            //处理修改数据 by wu end

            $this->smarty->assign('shop_info', $seller_shop_info);

            /*  @author-bylu  start */
            $shop_information = $this->merchantCommonService->getShopName($adminru['ru_id']);
            $adminru['ru_id'] == 0 ? $shop_information['is_dsc'] = true : $shop_information['is_dsc'] = false;//判断当前商家是平台,还是入驻商家 bylu
            $this->smarty->assign('shop_information', $shop_information);
            /*  @author-bylu  end */

            $shop_information = $this->merchantCommonService->getShopName($adminru['ru_id']);
            $this->smarty->assign('shop_information', $shop_information);

            $this->smarty->assign('cities', get_regions(2, $seller_shop_info['province']));
            $this->smarty->assign('districts', get_regions(3, $seller_shop_info['city']));

            $this->smarty->assign('http', $this->dsc->http());
            $this->smarty->assign('data_op', $action);

            $data = read_static_cache('main_user_str');

            if ($data === false) {
                $this->smarty->assign('is_false', '1');
            } else {
                $this->smarty->assign('is_false', '0');
            }

            $this->smarty->assign('current', 'index_first');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_merchants_basic_info']);
            return $this->smarty->display('store_setting.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商家开店向导第二步
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'merchants_second') {
            $shop_name = empty($_POST['shop_name']) ? '' : addslashes(trim($_POST['shop_name']));
            $shop_title = empty($_POST['shop_title']) ? '' : addslashes(trim($_POST['shop_title']));
            $shop_keyword = empty($_POST['shop_keyword']) ? '' : addslashes(trim($_POST['shop_keyword']));
            $shop_country = empty($_POST['shop_country']) ? 0 : intval($_POST['shop_country']);
            $shop_province = empty($_POST['shop_province']) ? 0 : intval($_POST['shop_province']);
            $shop_city = empty($_POST['shop_city']) ? 0 : intval($_POST['shop_city']);
            $shop_district = empty($_POST['shop_district']) ? 0 : intval($_POST['shop_district']);
            $shipping_id = empty($_POST['shipping_id']) ? 0 : intval($_POST['shipping_id']);
            $shop_address = empty($_POST['shop_address']) ? '' : addslashes(trim($_POST['shop_address']));
            $mobile = empty($_POST['mobile']) ? '' : trim($_POST['mobile']); //by wu
            $seller_email = empty($_POST['seller_email']) ? '' : addslashes(trim($_POST['seller_email']));
            $street_desc = empty($_POST['street_desc']) ? '' : addslashes(trim($_POST['street_desc']));
            $kf_qq = empty($_POST['kf_qq']) ? '' : $_POST['kf_qq'];
            $kf_ww = empty($_POST['kf_ww']) ? '' : $_POST['kf_ww'];
            $kf_touid = empty($_POST['kf_touid']) ? '' : addslashes(trim($_POST['kf_touid'])); //客服账号 bylu
            $kf_appkey = empty($_POST['kf_appkey']) ? 0 : addslashes(trim($_POST['kf_appkey'])); //appkey bylu
            $kf_secretkey = empty($_POST['kf_secretkey']) ? 0 : addslashes(trim($_POST['kf_secretkey'])); //secretkey bylu
            $kf_logo = empty($_POST['kf_logo']) ? 'http://' : addslashes(trim($_POST['kf_logo'])); //头像 bylu
            $kf_welcomeMsg = empty($_POST['kf_welcomeMsg']) ? '' : addslashes(trim($_POST['kf_welcomeMsg'])); //欢迎语 bylu
            $meiqia = empty($_POST['meiqia']) ? '' : addslashes(trim($_POST['meiqia'])); //美洽客服
            $kf_type = empty($_POST['kf_type']) ? 0 : intval($_POST['kf_type']);
            $kf_tel = empty($_POST['kf_tel']) ? '' : addslashes(trim($_POST['kf_tel']));
            $notice = empty($_POST['notice']) ? '' : addslashes(trim($_POST['notice']));
            $data_op = empty($_POST['data_op']) ? '' : $_POST['data_op'];
            $check_sellername = empty($_POST['check_sellername']) ? 0 : intval($_POST['check_sellername']);
            $shop_style = isset($_POST['shop_style']) && !empty($_POST['shop_style']) ? intval($_POST['shop_style']) : 0;
            $domain_name = empty($_POST['domain_name']) ? '' : trim($_POST['domain_name']);
            $templates_mode = empty($_REQUEST['templates_mode']) ? 0 : intval($_REQUEST['templates_mode']);

            $tengxun_key = empty($_POST['tengxun_key']) ? '' : addslashes(trim($_POST['tengxun_key']));
            $longitude = empty($_POST['longitude']) ? '' : addslashes(trim($_POST['longitude']));
            $latitude = empty($_POST['latitude']) ? '' : addslashes(trim($_POST['latitude']));

            $js_appkey = empty($_POST['js_appkey']) ? '' : $_POST['js_appkey']; //扫码appkey
            $js_appsecret = empty($_POST['js_appsecret']) ? '' : $_POST['js_appsecret']; //扫码appsecret

            $print_type = empty($_POST['print_type']) ? 0 : intval($_POST['print_type']); //打印方式
            $kdniao_printer = empty($_POST['kdniao_printer']) ? '' : $_POST['kdniao_printer']; //打印机

            //判断域名是否存在  by kong
            if (!empty($domain_name)) {
                $sql = " SELECT count(id) FROM " . $this->dsc->table("seller_domain") . " WHERE domain_name = '" . $domain_name . "' AND ru_id !='" . $adminru['ru_id'] . "'";
                if ($this->db->getOne($sql) > 0) {
                    $lnk[] = ['text' => $GLOBALS['_LANG']['back_home'], 'href' => 'index.php?act=main'];
                    return sys_msg($GLOBALS['_LANG']['domain_exist'], 0, $lnk);
                }
            }
            $seller_domain = [
                'ru_id' => $adminru['ru_id'],
                'domain_name' => $domain_name,
            ];


            $shop_info = [
                'ru_id' => $adminru['ru_id'],
                'shop_name' => $shop_name,
                'shop_title' => $shop_title,
                'shop_keyword' => $shop_keyword,
                'country' => $shop_country,
                'province' => $shop_province,
                'city' => $shop_city,
                'district' => $shop_district,
                'shipping_id' => $shipping_id,
                'shop_address' => $shop_address,
                'mobile' => $mobile,
                'seller_email' => $seller_email,
                'kf_qq' => $kf_qq,
                'kf_ww' => $kf_ww,
                'kf_appkey' => $kf_appkey, // bylu
                'kf_secretkey' => $kf_secretkey, // bylu
                'kf_touid' => $kf_touid, // bylu
                'kf_logo' => $kf_logo, // bylu
                'kf_welcomeMsg' => $kf_welcomeMsg, // bylu
                'meiqia' => $meiqia,
                'kf_type' => $kf_type,
                'kf_tel' => $kf_tel,
                'notice' => $notice,
                'street_desc' => $street_desc,
                'shop_style' => $shop_style,
                'check_sellername' => $check_sellername,
                'templates_mode' => $templates_mode,
                'tengxun_key' => $tengxun_key,
                'longitude' => $longitude,
                'latitude' => $latitude,
                'js_appkey' => $js_appkey, //扫码appkey
                'js_appsecret' => $js_appsecret, //扫码appsecret
                'print_type' => $print_type,
                'kdniao_printer' => $kdniao_printer
            ];

            $sql = "SELECT ss.shop_logo, ss.logo_thumb, ss.street_thumb, ss.brand_thumb, sq.qrcode_thumb FROM " . $this->dsc->table('seller_shopinfo') . " as ss " .
                " left join " . $this->dsc->table('seller_qrcode') . " as sq on sq.ru_id=ss.ru_id " .
                " WHERE ss.ru_id='" . $adminru['ru_id'] . "'"; //by wu
            $store = $this->db->getRow($sql);

            /**
             * 创建目录
             */
            $seller_imgs_path = storage_public(IMAGE_DIR . '/seller_imgs/');
            if (!file_exists($seller_imgs_path)) {
                make_dir($seller_imgs_path);
            }

            $seller_logo_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/');
            if (!file_exists($seller_logo_path)) {
                make_dir($seller_logo_path);
            }

            $oss_img = [];

            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';

            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']) {
                $file = $_FILES['shop_logo'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        if ($file['name']) {
                            $ext = explode('.', $file['name']);
                            $ext = array_pop($ext);
                        } else {
                            $ext = "";
                        }

                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/seller_logo' . $adminru['ru_id'] . '.' . $ext);

                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $shop_info['shop_logo'] = $file_name ? str_replace(storage_public(), '', $file_name) : '';

                            $oss_img['shop_logo'] = $shop_info['shop_logo'];
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/seller_' . $adminru['ru_id']));
                        }
                    }
                }
            }

            /**
             * 创建目录
             */
            $logo_thumb_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/logo_thumb/');
            if (!file_exists($logo_thumb_path)) {
                make_dir($logo_thumb_path);
            }

            if ($_FILES['logo_thumb']) {
                $file = $_FILES['logo_thumb'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        if ($file['name']) {
                            $ext = explode('.', $file['name']);
                            $ext = array_pop($ext);
                        } else {
                            $ext = "";
                        }

                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/logo_thumb/logo_thumb' . $adminru['ru_id'] . '.' . $ext);

                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                            $logo_thumb = $image->make_thumb($file_name, 120, 120, storage_public(IMAGE_DIR . "/seller_imgs/seller_logo/logo_thumb/"));

                            if ($logo_thumb) {
                                $logo_thumb = str_replace(storage_public(), '', $logo_thumb);
                                $shop_info['logo_thumb'] = $logo_thumb;

                                dsc_unlink($file_name);

                                $oss_img['logo_thumb'] = $logo_thumb;
                            }
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/logo_thumb_' . $adminru['ru_id']));
                        }
                    }
                }
            }

            $street_thumb = $image->upload_image($_FILES['street_thumb'], 'store_street/street_thumb');  //图片存放地址 -- data/septs_image
            $brand_thumb = $image->upload_image($_FILES['brand_thumb'], 'store_street/brand_thumb');  //图片存放地址 -- data/septs_image

            $street_thumb = $street_thumb ? str_replace(storage_public(), '', $street_thumb) : '';
            $brand_thumb = $brand_thumb ? str_replace(storage_public(), '', $brand_thumb) : '';
            $oss_img['street_thumb'] = $street_thumb;
            $oss_img['brand_thumb'] = $brand_thumb;

            if ($street_thumb) {
                $shop_info['street_thumb'] = $street_thumb;
            }

            if ($brand_thumb) {
                $shop_info['brand_thumb'] = $brand_thumb;
            }

            $domain_id = $this->db->getOne("SELECT id FROM " . $this->dsc->table('seller_domain') . " WHERE ru_id ='" . $adminru['ru_id'] . "'"); //by kong
            /* 二级域名绑定  by kong  satrt */
            if ($domain_id > 0) {
                $this->db->autoExecute($this->dsc->table('seller_domain'), $seller_domain, 'UPDATE', "ru_id='" . $adminru['ru_id'] . "'");
            } else {
                $this->db->autoExecute($this->dsc->table('seller_domain'), $seller_domain, 'INSERT');
            }
            /* 二级域名绑定  by kong  end */

            /**
             * 创建目录
             */
            $seller_qrcode_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_qrcode/');
            if (!file_exists($seller_qrcode_path)) {
                make_dir($seller_qrcode_path);
            }

            $qrcode_thumb_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_qrcode/qrcode_thumb/');
            if (!file_exists($qrcode_thumb_path)) {
                make_dir($qrcode_thumb_path);
            }

            //二维码中间logo by wu start
            if ($_FILES['qrcode_thumb']) {
                $file = $_FILES['qrcode_thumb'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $name = explode('.', $file['name']);
                        $ext = array_pop($name);
                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_qrcode/qrcode_thumb/qrcode_thumb' . $adminru['ru_id'] . '.' . $ext);
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                            $qrcode_thumb = $image->make_thumb($file_name, 120, 120, storage_public(IMAGE_DIR . "/seller_imgs/seller_qrcode/qrcode_thumb/"));

                            if (!empty($qrcode_thumb)) {
                                $qrcode_thumb = str_replace(storage_public(), '', $qrcode_thumb);

                                $oss_img['qrcode_thumb'] = $qrcode_thumb;

                                if (isset($store['qrcode_thumb']) && $store['qrcode_thumb']) {
                                    $store['qrcode_thumb'] = str_replace(['../'], '', $store['qrcode_thumb']);
                                    dsc_unlink(storage_public($store['qrcode_thumb']));
                                }
                            }

                            /* 保存 */
                            $qrcode_count = SellerQrcode::where('ru_id', $adminru['ru_id'])->count();

                            if ($qrcode_count > 0) {
                                if (!empty($qrcode_thumb)) {
                                    SellerQrcode::where('ru_id', $adminru['ru_id'])
                                        ->update([
                                            'qrcode_thumb' => $qrcode_thumb
                                        ]);
                                }
                            } else {
                                SellerQrcode::insert([
                                    'ru_id' => $adminru['ru_id'],
                                    'qrcode_thumb' => $qrcode_thumb
                                ]);
                            }
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/qrcode_thumb_' . $adminru['ru_id']));
                        }
                    }
                }
            }
            //二维码中间logo by wu end

            $this->dscRepository->getOssAddFile($oss_img);

            $admin_user = [
                'email' => $seller_email
            ];

            $this->db->autoExecute($this->dsc->table('admin_user'), $admin_user, 'UPDATE', "user_id = '" . session('seller_id') . "'");

            if ($data_op == 'add') {
                if (!$store) {
                    $this->db->autoExecute($this->dsc->table('seller_shopinfo'), ['ru_id' => $adminru['ru_id']], 'INSERT');
                    //处理修改数据 by wu start
                    $db_data = []; //数据库中数据
                    $diff_data = array_diff_assoc($shop_info, $db_data); //数据库中数据与提交数据差集
                    if (!empty($diff_data)) { //有数据变化

                        //将修改数据插入日志
                        foreach ($diff_data as $key => $val) {
                            $changelog = ['data_key' => $key, 'data_value' => $val, 'ru_id' => $adminru['ru_id']];
                            $sql = "SELECT id FROM" . $this->dsc->table('seller_shopinfo_changelog') . "WHERE data_key = '$key' AND ru_id = '" . $adminru['ru_id'] . "'";
                            if ($this->db->getOne($sql)) {
                                $this->db->autoExecute($this->dsc->table('seller_shopinfo_changelog'), $changelog, 'update', "ru_id='" . $adminru['ru_id'] . "' AND data_key = '$key'");
                            } else {
                                $this->db->autoExecute($this->dsc->table('seller_shopinfo_changelog'), $changelog, 'INSERT');
                            }
                        }
                    }
                    //处理修改数据 by wu end
                }

                $lnk[] = ['text' => $GLOBALS['_LANG']['back_prev_step'], 'href' => 'index.php?act=merchants_first'];
                return sys_msg($GLOBALS['_LANG']['add_shop_info_success'], 0, $lnk);
            } else {
                $sql = "select check_sellername from " . $this->dsc->table('seller_shopinfo') . " where ru_id='" . $adminru['ru_id'] . "'";
                $seller_shop_info = $this->db->getRow($sql);

                if ($seller_shop_info['check_sellername'] != $check_sellername) {
                    $shop_info['shopname_audit'] = 0;
                }

                $oss_del = [];
                if (isset($shop_info['logo_thumb']) && !empty($shop_info['logo_thumb'])) {
                    if (!empty($store['logo_thumb'])) {
                        $oss_del[] = $store['logo_thumb'];
                    }
                    dsc_unlink(storage_public($store['logo_thumb']));
                }

                if (!empty($street_thumb)) {
                    $oss_street_thumb = $store['street_thumb'];
                    if (!empty($oss_street_thumb)) {
                        $oss_del[] = $oss_street_thumb;
                    }

                    $shop_info['street_thumb'] = $street_thumb;
                    dsc_unlink(storage_public($oss_street_thumb));
                }

                if (!empty($brand_thumb)) {
                    $oss_brand_thumb = $store['brand_thumb'];
                    if (!empty($oss_brand_thumb)) {
                        $oss_del[] = $oss_brand_thumb;
                    }

                    $shop_info['brand_thumb'] = $brand_thumb;
                    dsc_unlink(storage_public($oss_brand_thumb));
                }

                $this->dscRepository->getOssDelFile($oss_del);

                //处理修改数据 by wu start
                $data_keys = array_keys($shop_info); //更新数据字段
                $db_data = get_table_date('seller_shopinfo', "ru_id='{$adminru['ru_id']}'", $data_keys); //数据库中数据

                //获取零食表数据 有  已零时表数据为准
                $diff_data_old = get_seller_shopinfo_changelog($adminru['ru_id']);

                if ($diff_data_old) {
                    foreach ($diff_data_old as $key => $val) {
                        if ($key != 'shop_logo' && isset($oss_img[$key]) && !empty($oss_img[$key])) {
                            $val = str_replace(['../'], '', $val);
                            dsc_unlink(storage_public($val));
                        }
                    }
                }


                $db_data = array_replace($db_data, $diff_data_old);

                $diff_data = array_diff_assoc($shop_info, $db_data); //数据库中数据与提交数据差集

                if (!empty($diff_data)) { //有数据变化
                    $review_status = ['review_status' => 1];
                    $this->db->autoExecute($this->dsc->table('seller_shopinfo'), $review_status, 'UPDATE', "ru_id='" . $adminru['ru_id'] . "'");
                    //将修改数据插入日志
                    foreach ($diff_data as $key => $val) {
                        $changelog = ['data_key' => $key, 'data_value' => $val, 'ru_id' => $adminru['ru_id']];
                        $sql = "SELECT id FROM" . $this->dsc->table('seller_shopinfo_changelog') . " WHERE data_key = '$key' AND ru_id = '" . $adminru['ru_id'] . "'";
                        if ($this->db->getOne($sql)) {
                            $this->db->autoExecute($this->dsc->table('seller_shopinfo_changelog'), $changelog, 'UPDATE', "ru_id='" . $adminru['ru_id'] . "' AND data_key = '$key'");
                        } else {
                            $this->db->autoExecute($this->dsc->table('seller_shopinfo_changelog'), $changelog, 'INSERT');
                        }
                    }
                }
                //处理修改数据 by wu end

                $lnk[] = ['text' => $GLOBALS['_LANG']['back_prev_step'], 'href' => 'index.php?act=merchants_first'];
                return sys_msg($GLOBALS['_LANG']['update_shop_info_success'], 0, $lnk);
            }
        } //wang 商家入驻 店铺头部装修
        elseif ($_REQUEST['act'] == 'shop_top') {
            admin_priv('seller_store_other'); //by kong
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_merchants_shop_top']);
            //获取入驻商家店铺信息 wang 商家入驻

            $seller_shop_info = $this->storeService->getShopInfo($adminru['ru_id']);

            if ($seller_shop_info['id'] > 0) {
                //店铺头部
                $header_sql = "select content, headtype, headbg_img, shop_color from " . $this->dsc->table('seller_shopheader') . " where seller_theme='" . $seller_shop_info['seller_theme'] . "' and ru_id = '" . $adminru['ru_id'] . "'";
                $shopheader_info = $this->db->getRow($header_sql);

                $header_content = $shopheader_info['content'];

                /* 创建 百度编辑器 wang 商家入驻 */
                create_ueditor_editor('shop_header', $header_content, 586);

                $this->smarty->assign('form_action', 'shop_top_edit');
                $this->smarty->assign('shop_info', $seller_shop_info);
                $this->smarty->assign('shopheader_info', $shopheader_info);
            } else {
                $lnk[] = ['text' => $GLOBALS['_LANG']['set_shop_info'], 'href' => 'index.php?act=merchants_first'];
                return sys_msg($GLOBALS['_LANG']['please_set_shop_basic_info'], 0, $lnk);
            }
            $this->smarty->assign('current', 'index_top');
            return $this->smarty->display('seller_shop_header.dwt');
        } elseif ($_REQUEST['act'] == 'shop_top_edit') {
            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";

            $shop_header = !empty($_REQUEST['shop_header']) ? preg_replace($preg, "", stripslashes($_REQUEST['shop_header'])) : '';
            $seller_theme = !empty($_REQUEST['seller_theme']) ? preg_replace($preg, "", stripslashes($_REQUEST['seller_theme'])) : '';
            $shop_color = !empty($_REQUEST['shop_color']) ? $_REQUEST['shop_color'] : '';
            $headtype = isset($_REQUEST['headtype']) ? intval($_REQUEST['headtype']) : 0;

            $img_url = '';
            if ($headtype == 0) {
                /* 处理图片 */
                /* 允许上传的文件类型 */
                $allow_file_types = '|GIF|JPG|PNG|BMP|';

                if ($_FILES['img_url']) {
                    $file = $_FILES['img_url'];
                    /* 判断用户是否选择了文件 */
                    if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                        /* 检查上传的文件类型是否合法 */
                        if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                        } else {
                            $ext = array_pop(explode('.', $file['name']));
                            $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_header_img/seller_' . $adminru['ru_id']);
                            if (!is_dir($file_dir)) {
                                mkdir($file_dir);
                            }
                            $file_name = $file_dir . "/slide_" . gmtime() . '.' . $ext;
                            /* 判断是否上传成功 */
                            if (move_upload_file($file['tmp_name'], $file_name)) {
                                $img_url = $file_name;

                                $oss_img_url = str_replace("../", "", $img_url);
                                $this->dscRepository->getOssAddFile([$oss_img_url]);
                            } else {
                                return sys_msg($GLOBALS['_LANG']['img_upload_fail']);
                            }
                        }
                    }
                } else {
                    return sys_msg($GLOBALS['_LANG']['must_upload_img']);
                }
            }

            $sql = "SELECT headbg_img FROM " . $this->dsc->table('seller_shopheader') . " WHERE ru_id='" . $adminru['ru_id'] . "' and seller_theme='" . $seller_theme . "'";
            $shopheader_info = $this->db->getRow($sql);

            if (empty($img_url)) {
                $img_url = $shopheader_info['headbg_img'];
            }

            //跟新店铺头部
            $sql = "update " . $this->dsc->table('seller_shopheader') . " set content='$shop_header', shop_color='$shop_color', headbg_img='$img_url', headtype='$headtype' where ru_id='" . $adminru['ru_id'] . "' and seller_theme='" . $seller_theme . "'";
            $this->db->query($sql);

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_prev_step'], 'href' => 'index.php?act=shop_top'];

            return sys_msg($GLOBALS['_LANG']['shop_head_edit_success'], 0, $lnk);
        }

        /* ------------------------------------------------------ */
        //-- 检查订单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_order') {
            $firstSecToday = local_mktime(0, 0, 0, date("m"), date("d"), date("Y"));
            $lastSecToday = local_mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")) - 1;

            if (empty(session('last_check'))) {
                session([
                    'last_check' => gmtime()
                ]);
                return make_json_result('', '', ['new_orders' => 0, 'new_paid' => 0]);
            }

            //ecmoban模板堂 --zhuo
            $where = " AND o.ru_id = '" . $adminru['ru_id'] . "' ";
            $where .= " AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示
            $where .= " AND o.shipping_status = " . SS_UNSHIPPED;

            /* 新订单 */
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('order_info') . " as o" .
                " WHERE o.add_time >= " . $firstSecToday . " AND o.add_time <= " . $lastSecToday . $where;
            $arr['new_orders'] = $this->db->getOne($sql);

            /* 新付款的订单 */
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('order_info') . " as o" .
                ' WHERE o.pay_time >= ' . $firstSecToday . " AND o.pay_time <= " . $lastSecToday . $where;
            $arr['new_paid'] = $this->db->getOne($sql);

            session([
                'last_check' => gmtime(),
                'firstSecToday' => $firstSecToday,
                'lastSecToday' => $lastSecToday
            ]);

            $pay_effective_time = isset($GLOBALS['_CFG']['pay_effective_time']) && $GLOBALS['_CFG']['pay_effective_time'] > 0 ? intval($GLOBALS['_CFG']['pay_effective_time']) : 0;//订单时效
            if ($pay_effective_time > 0) {
                checked_pay_Invalid_order($pay_effective_time);
            }

            if (!(is_numeric($arr['new_orders']) && is_numeric($arr['new_paid']))) {
                return make_json_error($this->db->error());
            } else {
                return make_json_result('', '', $arr);
            }
        }

        /* ------------------------------------------------------ */
        //-- 检查商家账单是否生成
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_bill') {
            $seller_id = isset($_REQUEST['seller_id']) && !empty($_REQUEST['seller_id']) ? intval($_REQUEST['seller_id']) : 0;

            if ($seller_id > 0) {
                app(CommissionServer::class)->checkBill($seller_id);
            }

            return make_json_result('', '', []);
        } elseif ($_REQUEST['act'] == 'main_user') {
            load_helper('base');
            $data = read_static_cache('main_user_str');

            if ($data === false) {
                $ecs_version = VERSION;
                $ecs_lang = $GLOBALS['_CFG']['lang'];
                $ecs_release = RELEASE;
                $php_ver = PHP_VERSION;
                $mysql_ver = $this->db->version();
                $ecs_charset = strtoupper(EC_CHARSET);

                $scount = $this->db->getOne('SELECT COUNT(*) FROM ' . $this->dsc->table('seller_shopinfo')); //会员数量
                $sql = 'SELECT COUNT(*) AS oCount, ' . "SUM(" . $this->orderService->orderAmountField('o.') . ") AS oAmount" . ' FROM ' . $this->dsc->table('order_info') . " AS o WHERE o.main_count = 0 LIMIT 1";
                $order['stats'] = $this->db->getRow($sql);

                $ocount = $order['stats']['oCount']; //订单数量
                $oamount = $order['stats']['oAmount']; //总销售金额

                $goods['total'] = $this->db->GetOne('SELECT COUNT(*) FROM ' . $this->dsc->table('goods') .
                    ' WHERE is_delete = 0 AND is_alone_sale = 1 AND is_real = 1');
                $gcount = $goods['total']; //商品数量
                $ecs_user = $this->db->getOne('SELECT COUNT(*) FROM ' . $this->dsc->table('users')); //会员数量

                $ecs_template = $this->db->getOne('SELECT value FROM ' . $this->dsc->table('shop_config') . ' WHERE code = \'template\''); //当前使用模板
                $style = $this->db->getOne('SELECT value FROM ' . $this->dsc->table('shop_config') . ' WHERE code = \'stylename\'');  //当前模板样式
                if ($style == '') {
                    $style = '0';
                }
                $ecs_style = $style;
                $shop_url = urlencode($this->dsc->url()); //当前url

                $time = app(TimeRepository::class)->getGmTime();

                $httpData = [
                    'domain' => $this->dsc->get_domain(), //当前域名
                    'url' => urldecode($shop_url), //当前url
                    'ver' => $ecs_version,
                    'lang' => $ecs_lang,
                    'release' => $ecs_release,
                    'php_ver' => $php_ver,
                    'mysql_ver' => $mysql_ver,
                    'ocount' => $ocount,
                    'oamount' => $oamount,
                    'gcount' => $gcount,
                    'scount' => $scount,
                    'charset' => $ecs_charset,
                    'usecount' => $ecs_user,
                    'template' => $ecs_template,
                    'style' => $ecs_style,
                    'add_time' => app(TimeRepository::class)->getLocalDate("Y-m-d H:i:s", $time)
                ];

                $httpData = json_encode($httpData); // 对变量进行 JSON 编码
                $argument = array(
                    'data' => $httpData
                );

                Http::doPost('', $argument);

                write_static_cache('main_user_str', $httpData);
            }
        }

        /* ------------------------------------------------------ */
        //-- 修改快捷菜单 by wu
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'change_user_menu') {
            $adminru = get_admin_ru_id();
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
            $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
            //已存在的快捷菜单
            $user_menu = get_user_menu_list();
            //检查是否已存在
            $change = get_user_menu_status($action);
            //
            if (!$change) {
                $user_menu[] = $action;
                $sql = " UPDATE " . $this->dsc->table('seller_shopinfo') . " set user_menu = '" . implode(',', $user_menu) . "' WHERE ru_id = '" . $adminru['ru_id'] . "' ";
                if ($this->db->query($sql)) {
                    $result['error'] = 1;
                }
            }
            if ($change) {
                $user_menu = array_diff($user_menu, [$action]);
                $sql = " UPDATE " . $this->dsc->table('seller_shopinfo') . " set user_menu = '" . implode(',', $user_menu) . "' WHERE ru_id = '" . $adminru['ru_id'] . "' ";
                if ($this->db->query($sql)) {
                    $result['error'] = 2;
                }
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 清        除缓存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'clear_cache') {
            $sql = "UPDATE " . $this->dsc->table('shop_config') . " SET value = 0 WHERE code = 'is_downconfig'";
            $this->db->query($sql);

            clear_all_files('', SELLER_PATH);
            return sys_msg($GLOBALS['_LANG']['caches_cleared']);
        }

        /* ------------------------------------------------------ */
        //-- 获取店铺坐标
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'tengxun_coordinate') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $province = !empty($_REQUEST['province']) ? intval($_REQUEST['province']) : 0;
            $city = !empty($_REQUEST['city']) ? intval($_REQUEST['city']) : 0;
            $district = !empty($_REQUEST['district']) ? intval($_REQUEST['district']) : 0;
            $address = !empty($_REQUEST['address']) ? trim($_REQUEST['address']) : 0;

            $region = get_seller_region(['province' => $province, 'city' => $city, 'district' => $district]);
            $key = $GLOBALS["_CFG"]['tengxun_key']; //密钥
            $region .= $address; //地址
            $url = "https://apis.map.qq.com/ws/geocoder/v1/?address=" . $region . "&key=" . $key;
            $http = new Http();
            $data = $http->doGet($url);
            $data = dsc_decode($data, true);

            if ($data['status'] == 0) {
                $result['lng'] = $data['result']['location']['lng'];
                $result['lat'] = $data['result']['location']['lat'];
            } else {
                $result['error'] = 1;
                $result['message'] = $data['message'];
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 管理员头像上传
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_store_img') {
            $result = ["error" => 0, "message" => "", "content" => ""];

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            $admin_id = get_admin_id();

            if ($_FILES['img']['name']) {
                $dir = 'store_user';

                $img_name = $image->upload_image($_FILES['img'], $dir);

                $this->dscRepository->getOssAddFile([$img_name]);

                if ($img_name) {
                    $result['error'] = 1;
                    $result['content'] = get_image_path($img_name);
                    //删除原图片
                    $store_user_img = $this->db->getOne(" SELECT admin_user_img FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . $admin_id . "' ");
                    @unlink(storage_public($store_user_img));
                    //插入新图片
                    $sql = " UPDATE " . $this->dsc->table('admin_user') . " SET admin_user_img = '{$result['content']}' WHERE user_id = '" . $admin_id . "' ";
                    $this->db->query($sql);
                }
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 登录状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'login_status') {
            $status = app(CommonManageService::class)->loginStatus();
            return response()->json(['status' => $status]);
        }
    }

    //PC端客单价
    private function get_sales($day_num, $ru_id = 0)
    {
        $where = " AND o.pay_status = 2";
        $where .= " AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

        //计算24小内的时间戳
        if ($day_num == 1) {
            $date_start = local_mktime(0, 0, 0, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
            $date_end = local_mktime(23, 59, 59, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
        } elseif ($day_num == 2) {
            $date_end = local_mktime(0, 0, 0, local_date('m'), local_date('d'), local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 + 1;
        }

        /* 查询订单 */
        $sql = "SELECT IFNULL(SUM(" . $this->orderService->orderAmountField('o.') . "),0) AS 'ga', COUNT(o.order_id) AS 'oi' " .
            " FROM " . $this->dsc->table('order_info') . " AS o " .
            " LEFT JOIN " . $this->dsc->table('baitiao_log') . " AS bai ON o.order_id=bai.order_id WHERE o.add_time BETWEEN " . $date_start . ' AND ' . $date_end . " AND o.referer NOT IN('touch', 'mobile')" .
            " AND o.ru_id = '$ru_id'" . $where . " LIMIT 1";
        $row = $this->db->getRow($sql);

        $arr = [];
        //计算客单价，客单价 = 订单总额/订单数
        if ($row && $row['oi']) {
            $sales = ($row['ga']) / $row['oi'];  //客单价计算  + $row['sf'] 不计算运费
            $count = $row['ga'];  //PC端成交计算  + $row['sf'] 不计算运费
            $arr = [
                'sales' => $sales,
                'count' => $count,
                'format_sales' => price_format($sales, false),
                'format_count' => price_format($count),
                'order' => $row['oi']
            ];
        }

        return $arr;
    }

    //移动端客单价
    private function get_move_sales($day_num, $ru_id = 0)
    {

        //计算24小内的时间戳
        if ($day_num == 1) {
            $date_start = local_mktime(0, 0, 0, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
            $date_end = local_mktime(23, 59, 59, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
        } elseif ($day_num == 2) {
            $date_end = local_mktime(0, 0, 0, local_date('m'), local_date('d'), local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 + 1;
        }

        $where = " AND o.pay_status = 2";
        $where .= " AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

        /* 查询订单 */
        $sql = " SELECT IFNULL(SUM(" . $this->orderService->orderAmountField('o.') . "),0) AS 'ga', COUNT(o.order_id) AS 'oi'" .
            " FROM " . $this->dsc->table('order_info') . " AS o " .
            "LEFT JOIN " . $this->dsc->table('baitiao_log') . " AS bai ON o.order_id=bai.order_id WHERE o.add_time BETWEEN " . $date_start . ' AND ' . $date_end . " AND o.referer IN('touch', 'mobile')" .
            " AND o.ru_id = '$ru_id'" . $where . " LIMIT 1";
        $row = $this->db->getRow($sql);

        $arr = [];
        //计算客单价，客单价 = 订单总额/订单数
        if ($row && $row['oi']) {
            $sales = ($row['ga']) / $row['oi'];  //客单价计算  + $row['sf'] 不计算运费
            $count = $row['ga'];  //PC端成交计算  + $row['sf'] 不计算运费
            $arr = [
                'sales' => $sales,
                'count' => $count,
                'format_sales' => price_format($sales, false),
                'format_count' => price_format($count),
                'order' => $row['oi']
            ];
        }

        return $arr;
    }

    //获取PC子订单数
    private function get_sub_order($day_num, $ru_id = 0)
    {
        $where = " AND o.pay_status = 2";
        $where .= " AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

        //计算24小内的时间戳
        if ($day_num == 1) {
            $date_start = local_mktime(0, 0, 0, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
            $date_end = local_mktime(23, 59, 59, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
        } elseif ($day_num == 2) {
            $date_end = local_mktime(0, 0, 0, local_date('m'), local_date('d'), local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 + 1;
        }
        //查询子订单数
        $sql = "SELECT COUNT(o.order_id) AS 'oi' " .
            " FROM " . $this->dsc->table('order_info') . " AS o " .
            " WHERE o.add_time BETWEEN " . $date_start . ' AND ' . $date_end . " AND o.referer NOT IN('touch', 'mobile')" .
            " AND o.ru_id = '$ru_id'" . $where . " LIMIT 1";
        $row = $this->db->getRow($sql);

        $arr = [];
        if ($row && $row['oi']) {
            $sub_order = $row['oi'];
            $arr = ['sub_order' => $sub_order];
        }

        return $arr;
    }

    //获取移动子订单数
    private function get_move_sub_order($day_num, $ru_id = 0)
    {

        //计算24小内的时间戳
        if ($day_num == 1) {
            $date_start = local_mktime(0, 0, 0, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
            $date_end = local_mktime(23, 59, 59, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
        } elseif ($day_num == 2) {
            $date_end = local_mktime(0, 0, 0, local_date('m'), local_date('d'), local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 + 1;
        }

        $where = " AND o.pay_status = 2";
        $where .= " AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

        //查询子订单数
        $sql = "SELECT COUNT(*) AS 'oi' " .
            " FROM " . $this->dsc->table('order_info') . " AS o " .
            " WHERE o.add_time BETWEEN " . $date_start . ' AND ' . $date_end . " AND o.referer IN('touch', 'mobile')" .
            " AND o.ru_id = '$ru_id'" . $where . " LIMIT 1";
        $row = $this->db->getRow($sql);

        $arr = [];
        if ($row && $row['oi']) {
            $sub_order = $row['oi'];
            $arr = ['sub_order' => $sub_order];
        }

        return $arr;
    }

    //输出访问者    统计
    private function viewip($ru_id)
    {
        $date_start = local_mktime(0, 0, 0, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));
        $date_end = local_mktime(23, 59, 59, local_date('m', gmtime()), local_date('d', gmtime()), local_date('Y', gmtime()));

        $sql = "SELECT COUNT(i.ipid) AS ip " . " FROM " . $this->dsc->table('source_ip') . " AS i " .
            "LEFT JOIN " . $this->dsc->table('seller_shopinfo') . "AS s ON i.storeid = s.ru_id " .
            " WHERE i.iptime BETWEEN " . $date_start . ' AND ' . $date_end . " AND i.storeid = '$ru_id' LIMIT 1";
        $row = $this->db->getRow($sql);

        $arr = [];
        if ($row && $row['ip']) {
            $todaycount = $row['ip'];
            $arr = ['todaycount' => $todaycount];
        } else {
            $arr = ['todaycount' => 0];
        }

        return $arr;
    }


    /*
    * 销量查询
    * 订单状态未已确认、已付款、非未发货订单
    * 计算总金额的条件同计算佣金的条件
    * @param   string ru_id 商家ID
    * @param   string where 时间条件
    */
    private function query_sales($ru_id = 0, $where = '')
    {
        $sql = " SELECT COUNT(oi.order_id) order_total,IFNULL(SUM(" . $this->orderService->orderAmountField('oi.') . "),0) money_total FROM " .
            $this->dsc->table('order_info') . "oi " .
            " WHERE 1 AND oi.ru_id = '$ru_id' AND oi.pay_status = 2" . $where .
            " AND oi.main_count = 0 ";  //主订单下有子订单时，则主订单不显示
        return $sql;
    }

    /*
    *待评价查询
    */
    private function get_order_no_comment($ru_id = 0, $sign = 0)
    {
        $where = " AND oi.order_status " . db_create_in([OS_CONFIRMED, OS_SPLITED]) . "  AND oi.shipping_status = '" . SS_RECEIVED . "' AND oi.pay_status " . db_create_in([PS_PAYED, PS_PAYING]);
        $where .= " AND oi.ru_id = 0 ";  //主订单下有子订单时，则主订单不显示
        if ($sign == 0) {
            $where .= " AND (SELECT count(*) FROM " . $this->dsc->table('comment') . " AS c WHERE c.comment_type = 0 AND c.id_value = g.goods_id AND c.rec_id = og.rec_id AND c.parent_id = 0 AND c.ru_id = '$ru_id') = 0 ";
        }
        $sql = "SELECT count(*) FROM " . $this->dsc->table('order_goods') . " AS og " .
            "LEFT JOIN " . $this->dsc->table('order_info') . " AS oi ON og.order_id = oi.order_id " .
            "LEFT JOIN  " . $this->dsc->table('goods') . " AS g ON og.goods_id = g.goods_id " .
            "WHERE og.ru_id = '$ru_id' $where ";
        $arr = $this->db->getOne($sql);
        return $arr;
    }

    /*
    * 判断商家年审剩余时间
    */
    private function surplus_time($ru_id)
    {
        if (session()->has('verify_time') && session('verify_time')) {
            $sql = " SELECT ru_id, grade_id, add_time, year_num FROM " . $this->dsc->table('merchants_grade') . " WHERE ru_id = '$ru_id' " .
                " ORDER BY id DESC LIMIT 1 ";
            $row = $this->db->getRow($sql);

            $time = gmtime();
            $year = 1 * 60 * 60 * 24 * 365; //一年
            $month = 1 * 60 * 60 * 24 * 30; //一个月
            $enter_overtime = $row['add_time'] + $row['year_num'] * $year; //审核结束时间
            $two_month_later = local_strtotime('+2 months'); //2个月后
            $one_month_later = local_strtotime('+1 months'); //1个月后
            $minus = $enter_overtime - $time;
            $days = (local_date('d', $minus) > 0) ? intval(local_date('d', $minus)) : 0;

            session()->forget('verify_time');

            if ($enter_overtime <= $time) {//审核过期
                $sql = " UPDATE " . $this->dsc->table('merchants_shop_information') . " SET merchants_audit = 0 WHERE user_id = '$ru_id' ";
                $this->db->query($sql);
                return sys_msg($GLOBALS['_LANG']['exam_expire_repay_retry'], 1);
                return false;
            } elseif ($enter_overtime < $one_month_later) {//审核过期前30天
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => "index.php"];
                $content = $GLOBALS['_LANG']['exam_repay_tip'][0] . $days . $GLOBALS['_LANG']['exam_repay_tip'][1];
                return sys_msg($content, 0, $link);
            } elseif ($enter_overtime < $two_month_later) {//审核过期前60天
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => "index.php"];
                return sys_msg($GLOBALS['_LANG']['exam_repay_tip_2month'], 0, $link);
            } else {//未到提醒期
                return true;
            }
        } else {
            return true;
        }
    }
}
