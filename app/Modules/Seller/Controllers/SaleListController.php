<?php

namespace App\Modules\Seller\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 销售明细列表程序
 */
class SaleListController extends InitController
{
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('statistic', 'seller');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['06_stats']);

        $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'sale_list']);

        if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' || $_REQUEST['act'] == 'download')) {
            /* 检查权限 */
            $check_auth = check_authz_json('sale_order_stats');
            if ($check_auth !== true) {
                return $check_auth;
            }
            if (strstr($_REQUEST['start_date'], '-') === false) {
                $_REQUEST['start_date'] = local_date('Y-m-d H:i:s', $_REQUEST['start_date']);
                $_REQUEST['end_date'] = local_date('Y-m-d H:i:s', $_REQUEST['end_date']);
            }

            /*------------------------------------------------------ */
            //--Excel文件下载
            /*------------------------------------------------------ */
            if ($_REQUEST['act'] == 'download') {
                $file_name = str_replace(" ", "--", $_REQUEST['start_date'] . '_' . $_REQUEST['end_date'] . '_sale');
                $goods_sales_list = $this->get_sale_list(false);

                header("Content-type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=$file_name.xls");

                /* 文件标题 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $_REQUEST['start_date'] . $GLOBALS['_LANG']['to'] . $_REQUEST['end_date'] . $GLOBALS['_LANG']['sales_list']) . "\t\n";

                /* 商品名称,订单号,商品数量,销售价格,销售日期 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_steps_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['pro_code']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['order_sn']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['amount']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sell_price']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['total_amount']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sell_date']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['order_status']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['shipping_status']) . "\t\n";

                foreach ($goods_sales_list['sale_list_data'] as $key => $value) {
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['shop_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_sn']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', '[ ' . $value['order_sn'] . ' ]') . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_num']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['sales_price']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['total_fee']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['sales_time']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['order_status_format']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['shipping_status_format']) . "\t";
                    echo "\n";
                }
            } else {
                $sale_list_data = $this->get_sale_list();

                //分页
                $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($sale_list_data, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $this->smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
                $this->smarty->assign('filter', $sale_list_data['filter']);
                $this->smarty->assign('record_count', $sale_list_data['record_count']);
                $this->smarty->assign('page_count', $sale_list_data['page_count']);
                return make_json_result($this->smarty->fetch('sale_list.dwt'), '', ['filter' => $sale_list_data['filter'], 'page_count' => $sale_list_data['page_count']]);
            }
        }
        /*------------------------------------------------------ */
        //--商品明细列表
        /*------------------------------------------------------ */
        else {

            /* 权限判断 */
            admin_priv('sale_order_stats');

            $this->smarty->assign('current', 'sale_list');

            /* 时间参数 */
            if (!isset($_REQUEST['start_date'])) {
                $start_date = local_strtotime('-7 days');
            }
            if (!isset($_REQUEST['end_date'])) {
                $end_date = local_strtotime('today');
            }

            $sale_list_data = $this->get_sale_list();

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($sale_list_data, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            /* 赋值到模板 */
            $this->smarty->assign('filter', $sale_list_data['filter']);
            $this->smarty->assign('record_count', $sale_list_data['record_count']);
            $this->smarty->assign('page_count', $sale_list_data['page_count']);
            $this->smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sell_stats']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('start_date', local_date('Y-m-d H:i:s', $start_date ?? null));
            $this->smarty->assign('end_date', local_date('Y-m-d H:i:s', $end_date ?? null));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sale_list']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['down_sales'], 'href' => '#download', 'class' => 'icon-download-alt']);

            /* 载入订单状态、付款状态、发货状态 */
            $this->smarty->assign('os_list', $this->get_status_list('order'));
            $this->smarty->assign('ss_list', $this->get_status_list('shipping'));

            /* 显示页面 */

            return $this->smarty->display('sale_list.dwt');
        }
    }
    /*------------------------------------------------------ */
    //--获取销售明细需要的函数
    /*------------------------------------------------------ */
    /**
     * 取得销售明细数据信息
     * @param bool $is_pagination 是否分页
     * @return  array   销售明细数据
     */
    private function get_sale_list($is_pagination = true)
    {

        /* 时间参数 */
        $filter['start_date'] = empty($_REQUEST['start_date']) ? local_strtotime('-7 days') : local_strtotime($_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? local_strtotime('today') : local_strtotime($_REQUEST['end_date']);
        $filter['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'og.goods_number' : trim($_REQUEST['sort_by']);

        $filter['order_status'] = isset($_REQUEST['order_status']) && !($_REQUEST['order_status'] == '') ? explode(',', $_REQUEST['order_status']) : '';
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) && !($_REQUEST['shipping_status'] == '') ? explode(',', $_REQUEST['shipping_status']) : '';
        $filter['time_type'] = !empty($_REQUEST['time_type']) ? intval($_REQUEST['time_type']) : 0;
        $filter['order_referer'] = empty($_REQUEST['order_referer']) ? '' : trim($_REQUEST['order_referer']);

        /* 查询数据的条件 */
        $where = " WHERE 1 ";

        $where .= " and oi.main_count = 0 AND oi.order_id = og.order_id ";  //主订单下有子订单时，则主订单不显示

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        $leftJoin = '';
        if ($adminru['ru_id'] > 0) {
            $where .= " and og.ru_id = '" . $adminru['ru_id'] . "'";
        }

        if ($filter['goods_sn']) {
            $where .= " AND og.goods_sn = '" . $filter['goods_sn'] . "'";
        }
        //ecmoban模板堂 --zhuo end

        if ($filter['time_type'] == 1) {
            $where .= " AND oi.add_time >= '" . $filter['start_date'] . "' AND oi.add_time < '" . ($filter['end_date'] + 86400) . "'";
        } else {
            $where .= " AND oi.shipping_time >= '" . $filter['start_date'] . "' AND oi.shipping_time <= '" . ($filter['end_date'] + 86400) . "'";
        }

        if (!empty($filter['order_status'])) { //多选
            $where .= " AND oi.order_status " . db_create_in($filter['order_status']);
        }

        if (!empty($filter['shipping_status'])) { //多选
            $where .= " AND oi.shipping_status " . db_create_in($filter['shipping_status']);;
        }

        if ($filter['order_referer']) {
            if ($filter['order_referer'] == 'pc') {
                $where .= " AND oi.referer NOT IN ('mobile','touch','ecjia-cashdesk') ";
            } else {
                $where .= " AND oi.referer = '$filter[order_referer]' ";
            }
        }

        $sql = "SELECT COUNT(og.goods_id) FROM " .
            $this->dsc->table('order_info') . ' AS oi,' .
            $this->dsc->table('order_goods') . ' AS og ' . $leftJoin .
            $where;
        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = 'SELECT og.goods_id, og.goods_sn, og.goods_name, og.goods_number AS goods_num, og.ru_id, og.goods_price ' .
            'AS sales_price, oi.add_time AS sales_time, oi.order_id, oi.order_sn, (og.goods_number * og.goods_price) AS total_fee, oi.order_status, oi.shipping_status ' .
            "FROM " . $this->dsc->table('order_goods') . " AS og, " . $this->dsc->table('order_info') . " AS oi " . $leftJoin .
            $where . " ORDER BY $filter[sort_by] DESC";

        if ($is_pagination) {
            $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
        }

        $sale_list_data = $this->db->getAll($sql);

        foreach ($sale_list_data as $key => $item) {
            $sale_list_data[$key]['shop_name'] = $this->merchantCommonService->getShopName($sale_list_data[$key]['ru_id'], 1); //ecmoban模板堂 --zhuo
            $sale_list_data[$key]['sales_price'] = $sale_list_data[$key]['sales_price'];
            $sale_list_data[$key]['total_fee'] = $sale_list_data[$key]['total_fee'];
            $sale_list_data[$key]['sales_time'] = local_date($GLOBALS['_CFG']['time_format'], $sale_list_data[$key]['sales_time']);

            $sale_list_data[$key]['order_status_format'] = trim(strip_tags($GLOBALS['_LANG']['os'][$item['order_status']]));
            $sale_list_data[$key]['shipping_status_format'] = trim(strip_tags($GLOBALS['_LANG']['ss'][$item['shipping_status']]));
        }
        $arr = ['sale_list_data' => $sale_list_data, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    /**
     * 取得状态列表
     * @param string $type 类型：all | order | shipping | payment
     */
    private function get_status_list($type = 'all')
    {
        $list = [];

        if ($type == 'all' || $type == 'order') {
            $pre = $type == 'all' ? 'os_' : '';
            foreach ($GLOBALS['_LANG']['os'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'shipping') {
            $pre = $type == 'all' ? 'ss_' : '';
            foreach ($GLOBALS['_LANG']['ss'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'payment') {
            $pre = $type == 'all' ? 'ps_' : '';
            foreach ($GLOBALS['_LANG']['ps'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }
        return $list;
    }
}
