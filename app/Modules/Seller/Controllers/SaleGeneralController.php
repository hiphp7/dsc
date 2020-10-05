<?php

namespace App\Modules\Seller\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 销售概况
 */
class SaleGeneralController extends InitController
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

        $this->dscRepository->helpersLang('statistic', 'seller');

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('lang', $GLOBALS['_LANG']);
        /* 权限判断 */
        admin_priv('sale_order_stats');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act']) || !in_array($_REQUEST['act'], ['list', 'download', 'query'])) {
            $_REQUEST['act'] = 'list';
        }
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['06_stats']);

        $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'report_sell']);

        /*------------------------------------------------------ */
        //-- 显示统计信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('current', 'sale_general_list');
            $start_time = local_mktime(0, 0, 0, local_date('m'), 1, local_date('Y')); //本月第一天
            $end_time = local_mktime(0, 0, 0, local_date('m'), local_date('t'), local_date('Y')) + 24 * 60 * 60 - 1; //本月最后一天
            $start_time = local_date($GLOBALS['_CFG']['time_format'], $start_time);
            $end_time = local_date($GLOBALS['_CFG']['time_format'], $end_time);

            $this->smarty->assign('start_time', $start_time);
            $this->smarty->assign('end_time', $end_time);

            /* 载入订单状态、付款状态、发货状态 */
            $this->smarty->assign('os_list', $this->get_status_list('order'));
            $this->smarty->assign('ss_list', $this->get_status_list('shipping'));

            $data = $this->get_data_list(1);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($data, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);

            $this->smarty->assign('date_start_time', $data['filter']['date_start_time']);
            $this->smarty->assign('date_end_time', $data['filter']['date_end_time']);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_order_time', '<img src="__TPL__/images/sort_desc.gif">');

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['down_sales_stats'],
                'href' => 'sale_general.php?act=download&start_time=' . $start_time . '&end_time=' . $end_time, 'class' => 'icon-download-alt']);

            /* 显示模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['report_sell']);

            return $this->smarty->display('sale_general.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $data = $this->get_data_list(1);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($data, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);

            $sort_flag = sort_flag($data['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('sale_general.dwt'), '', ['filter' => $data['filter'], 'page_count' => $data['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 下载EXCEL报表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download') {
            $data = $this->get_data_list(1);
            $data_list = $data['data_list'];

            /* 文件名 */
            $filename = str_replace(" ", "-", local_date($GLOBALS['_CFG']['time_format'], gmtime())) . "_" . rand(0, 1000);

            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=$filename.xls");

            /* 文件标题 */
            echo dsc_iconv(EC_CHARSET, 'GB2312', $filename . $GLOBALS['_LANG']['sales_statistics']) . "\t\n";

            /* 订单数量, 销售出商品数量, 销售金额 */
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_steps_name']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_name']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['pro_code']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['category']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['amount']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['unit_price']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['total_amount']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sale_date']) . "\t\n";

            foreach ($data_list as $data) {
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['shop_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_sn']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['cat_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_number']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_price']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['total_fee']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['add_time']) . "\t";
                echo "\n";
            }
        }
    }

    private function get_data_list($type = 0)
    {
        $adminru = get_admin_ru_id();

        if ($type != 0) {
            $result = get_filter();

            if ($result === false) {
                /* 过滤信息 */
                $filter['keyword'] = !isset($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
                if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
                    $filter['keyword'] = !empty($filter['keyword']) ? json_str_iconv($filter['keyword']) : '';
                }

                $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_number' : trim($_REQUEST['sort_by']);
                $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

                $filter['time_type'] = isset($_REQUEST['time_type']) ? intval($_REQUEST['time_type']) : 1;

                $filter['date_start_time'] = !empty($_REQUEST['date_start_time']) ? trim($_REQUEST['date_start_time']) : '';
                $filter['date_end_time'] = !empty($_REQUEST['date_end_time']) ? trim($_REQUEST['date_end_time']) : '';
                $filter['cat_name'] = !empty($_REQUEST['cat_name']) ? trim($_REQUEST['cat_name']) : '';

                $filter['order_status'] = isset($_REQUEST['order_status']) ? $_REQUEST['order_status'] : -1;
                $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? $_REQUEST['shipping_status'] : -1;

                $goods_where = 1;
                if (!empty($filter['cat_name'])) {
                    $sql = "SELECT cat_id FROM " . $this->dsc->table('category') . " WHERE cat_name = '" . $filter['cat_name'] . "'";
                    $cat_id = $this->db->getOne($sql);
                    $goods_where .= " AND g.cat_id = '$cat_id'";
                }

                $where_order = 1;
                if ($filter['date_start_time'] == '' && $filter['date_end_time'] == '') {
                    $start_time = local_mktime(0, 0, 0, local_date('m'), 1, local_date('Y')); //本月第一天
                    $end_time = local_mktime(0, 0, 0, local_date('m'), local_date('t'), local_date('Y')) + 24 * 60 * 60 - 1; //本月最后一天
                } else {
                    $start_time = local_strtotime($filter['date_start_time']);
                    $end_time = local_strtotime($filter['date_end_time']);
                }

                if (!empty($filter['cat_name'])) {
                    $where_order .= " AND (SELECT g.cat_id FROM " . $this->dsc->table('goods') . " AS g WHERE g.goods_id = og.goods_id LIMIT 1) = '$cat_id'";
                }

                if ($filter['time_type'] == 1) {
                    $where_order .= " AND o.add_time >= '$start_time' AND o.add_time <= '$end_time'";
                } else {
                    $where_order .= " AND o.shipping_time >= '$start_time' AND o.shipping_time <= '$end_time'";
                }

                if ($filter['order_status'] > -1) { //多选
                    $order_status = $filter['order_status'];
                    $where_order .= " AND o.order_status IN($order_status)";
                }

                if ($filter['shipping_status'] > -1) { //多选
                    $shipping_status = $filter['shipping_status'];
                    $where_order .= " AND o.shipping_status IN($shipping_status)";
                }

                if ($adminru['ru_id'] > 0) {
                    $where_order .= " AND og.ru_id = '" . $adminru['ru_id'] . "'";
                }

                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods') . " AS g WHERE " . $goods_where .
                    " AND (SELECT og.goods_id FROM " . $this->dsc->table('order_goods') . " AS og " .
                    " LEFT JOIN " . $this->dsc->table('order_info') . " AS o ON og.order_id = o.order_id" .
                    " WHERE o.main_count = 0 AND " . $where_order . " AND og.goods_id = g.goods_id LIMIT 1) > 0";

                $filter['record_count'] = $this->db->getOne($sql);

                /* 分页大小 */
                $filter = page_and_size($filter);

                $sql = "SELECT og.goods_id, og.order_id, og.goods_id, og.goods_name, og.ru_id, og.goods_sn, og.goods_price, o.add_time, o.shipping_time, " .
                    "SUM(og.goods_price * og.goods_number) AS total_fee, SUM(og.goods_number) AS goods_number, GROUP_CONCAT(o.order_id) AS order_id " .
                    " FROM " . $this->dsc->table('order_goods') . " AS og " .
                    " LEFT JOIN " . $this->dsc->table('goods') . " AS g " . " ON g.goods_id = og.goods_id " .
                    " LEFT JOIN " . $this->dsc->table('order_info') . " AS o " . " ON o.order_id = og.order_id " .
                    " WHERE " . $goods_where . " AND o.main_count = 0 AND " . $where_order . " GROUP BY og.goods_id" .
                    " ORDER BY $filter[sort_by] $filter[sort_order] " .
                    " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

                set_filter($filter, $sql);
            } else {
                $sql = $result['sql'];
                $filter = $result['filter'];
            }
        }

        $data_list = $this->db->getAll($sql);

        if ($type != 0) {
            for ($i = 0; $i < count($data_list); $i++) {
                $data_list[$i]['order_id'] = explode(",", $data_list[$i]['order_id']);
                $data_list[$i]['order_id'] = array_unique($data_list[$i]['order_id']);

                $data_list[$i]['shop_name'] = $this->merchantCommonService->getShopName($data_list[$i]['ru_id'], 1); //ecmoban模板堂 --zhuo

                $data_list[$i]['cat_name'] = $this->db->getOne("SELECT c.cat_name FROM " . $this->dsc->table('category') . " AS c, " .
                    $this->dsc->table('goods') . " AS g" . " WHERE c.cat_id = g.cat_id AND g.goods_id = '" . $data_list[$i]['goods_id'] . "' ");

                if ($filter['time_type'] == 1) {
                    $data_list[$i]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $data_list[$i]['add_time']);
                } else {
                    $data_list[$i]['shipping_time'] = local_date($GLOBALS['_CFG']['time_format'], $data_list[$i]['shipping_time']);
                }
            }

            if ($filter['sort_by'] == 'goods_number') {
                $data_list = get_array_sort($data_list, 'goods_number', 'DESC');
            }

            $arr = ['data_list' => $data_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
            return $arr;
        }
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
