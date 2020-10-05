<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Suppliers;
use App\Repositories\Common\DscRepository;

class SuppliersSaleListController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper(['order']);

        $this->dscRepository->helpersLang(['statistic'], 'admin');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $this->smarty->assign('menu_select', array('action' => '06_stats', 'current' => 'sale_list'));

        if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' || $_REQUEST['act'] == 'download')) {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_sale_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            if (strstr($_REQUEST['start_date'], '-') === false) {
                $_REQUEST['start_date'] = local_date('Y-m-d H:i:s', $_REQUEST['start_date']);
                $_REQUEST['end_date'] = local_date('Y-m-d H:i:s', $_REQUEST['end_date']);
            }

            /* ------------------------------------------------------ */
            //--Excel文件下载
            /* ------------------------------------------------------ */
            if ($_REQUEST['act'] == 'download') {
                $file_name = str_replace(" ", "--", $_REQUEST['start_date'] . '_' . $_REQUEST['end_date'] . '_sale');
                $goods_sales_list = $this->get_sale_list(false);

                header("Content-type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=$file_name.xls");

                /* 文件标题 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $_REQUEST['start_date'] . $GLOBALS['_LANG']['to'] . $_REQUEST['end_date'] . $GLOBALS['_LANG']['sales_list']) . "\t\n";

                /* 商品名称,订单号,商品数量,销售价格,销售日期 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', lang('admin/suppliers_sale_list.supplier_name')) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', lang('admin/suppliers_sale_list.number')) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['order_sn']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['amount']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sell_price']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', lang('admin/suppliers_sale_list.sum_price')) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sell_date']) . "\t\n";

                foreach ($goods_sales_list['sale_list_data'] as $key => $value) {
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['shop_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_sn']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', '[ ' . $value['order_sn'] . ' ]') . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_num']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['sales_price']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['total_fee']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['sales_time']) . "\t";
                    echo "\n";
                }
                exit;
            }

            $sale_list_data = $this->get_sale_list();

            $this->smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
            $this->smarty->assign('filter', $sale_list_data['filter']);
            $this->smarty->assign('record_count', $sale_list_data['record_count']);
            $this->smarty->assign('page_count', $sale_list_data['page_count']);

            return make_json_result($this->smarty->fetch('suppliers_sale_list.dwt'), '', array('filter' => $sale_list_data['filter'], 'page_count' => $sale_list_data['page_count']));
        }

        /* ------------------------------------------------------ */
        //--商品明细列表
        /* ------------------------------------------------------ */
        else {
            /* 权限判断 */
            admin_priv('suppliers_sale_list');
            /* 时间参数 */
            if (!isset($_REQUEST['start_date'])) {
                $start_date = local_strtotime('-7 days');
            }
            if (!isset($_REQUEST['end_date'])) {
                $end_date = local_strtotime('today');
            }

            $suppliers_list = suppliers_list_name();//获取供货商列表
            $this->smarty->assign('suppliers_list', $suppliers_list);

            $sale_list_data = $this->get_sale_list();
            /* 赋值到模板 */
            $this->smarty->assign('filter', $sale_list_data['filter']);
            $this->smarty->assign('record_count', $sale_list_data['record_count']);
            $this->smarty->assign('page_count', $sale_list_data['page_count']);
            $this->smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sell_stats']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('start_date', local_date('Y-m-d H:i:s', $start_date));
            $this->smarty->assign('end_date', local_date('Y-m-d H:i:s', $end_date));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sale_list']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['down_sales'], 'href' => '#download'));

            /* 载入订单状态、付款状态、发货状态 */
            $os_list = array(0 => lang('admin/suppliers_sale_list.incomplete'), 1 => lang('admin/suppliers_sale_list.completed'));
            $this->smarty->assign('os_list', $os_list);
            $ss_list = array(0 => lang('admin/suppliers_sale_list.unshipped'), 1 => lang('admin/suppliers_sale_list.delivery'), 2 => lang('admin/suppliers_sale_list.shipped'));
            $this->smarty->assign('ss_list', $ss_list);

            /* 显示页面 */
            return $this->smarty->display('suppliers_sale_list.dwt');
        }
    }

    /**
     * 取得销售明细数据信息
     * @param   bool $is_pagination 是否分页
     * @return  array   销售明细数据
     */
    private function get_sale_list($is_pagination = true)
    {

        /* 时间参数 */
        $filter['start_date'] = empty($_REQUEST['start_date']) ? local_strtotime('-7 days') : local_strtotime($_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? local_strtotime('today') : local_strtotime($_REQUEST['end_date']);
        $filter['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'og.goods_number' : trim($_REQUEST['sort_by']);

        $filter['suppliers_id'] = !empty($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : 0;
        $filter['order_status'] = !empty($_REQUEST['order_status']) ? explode(',', $_REQUEST['order_status']) : '';
        $filter['shipping_status'] = !empty($_REQUEST['shipping_status']) ? explode(',', $_REQUEST['shipping_status']) : '';
        $filter['time_type'] = !empty($_REQUEST['time_type']) ? intval($_REQUEST['time_type']) : 0;

        $where = " WHERE 1 ";

        $where .= " and (SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi2 WHERE oi2.main_order_id = oi.order_id) = 0 AND oi.order_id = og.order_id ";  //主订单下有子订单时，则主订单不显示

        $leftJoin = '';
        if ($filter['suppliers_id'] > 0) {
            $where .= " and oi.suppliers_id = '" . $filter['suppliers_id'] . "'";
        }

        if ($filter['goods_sn']) {
            $where .= " AND og.goods_sn = '" . $filter['goods_sn'] . "'";
        }

        if ($filter['time_type'] == 1) {
            $where .= " AND oi.add_time >= '" . $filter['start_date'] . "' AND oi.add_time < '" . ($filter['end_date'] + 86400) . "'";
        } else {
            $where .= " AND oi.shipping_time >= '" . $filter['start_date'] . "' AND oi.shipping_time <= '" . ($filter['end_date'] + 86400) . "'";
        }

        if (!empty($filter['order_status'])) { //多选
            $where .= " AND oi.order_status " . db_create_in($filter['order_status']);
        }

        if (!empty($filter['shipping_status'])) { //多选
            $where .= " AND oi.shipping_status " . db_create_in($filter['shipping_status']);
        }

        $sql = "SELECT COUNT(og.goods_id) FROM " .
            $GLOBALS['dsc']->table('wholesale_order_info') . ' AS oi,' .
            $GLOBALS['dsc']->table('wholesale_order_goods') . ' AS og ' . $leftJoin .
            $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = 'SELECT og.goods_id, og.goods_sn, og.goods_name, og.goods_number AS goods_num, oi.suppliers_id, og.goods_price ' .
            'AS sales_price, oi.add_time AS sales_time, oi.order_id, oi.order_sn, (og.goods_number * og.goods_price) AS total_fee ' .
            "FROM " . $GLOBALS['dsc']->table('wholesale_order_goods') . " AS og, " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi " . $leftJoin .
            $where . " ORDER BY $filter[sort_by] DESC";

        if ($is_pagination) {
            $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
        }
        $sale_list_data = $GLOBALS['db']->getAll($sql);

        foreach ($sale_list_data as $key => $item) {
            $suppliers_name = Suppliers::where('suppliers_id', $item['suppliers_id'])->value('suppliers_name');
            $suppliers_name = $suppliers_name ? $suppliers_name : '';

            $sale_list_data[$key]['shop_name'] = $suppliers_name;
            $sale_list_data[$key]['sales_price'] = $sale_list_data[$key]['sales_price'];
            $sale_list_data[$key]['total_fee'] = $sale_list_data[$key]['total_fee'];
            $sale_list_data[$key]['sales_time'] = local_date($GLOBALS['_CFG']['time_format'], $sale_list_data[$key]['sales_time']);
        }
        $arr = array('sale_list_data' => $sale_list_data, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
        return $arr;
    }
}
