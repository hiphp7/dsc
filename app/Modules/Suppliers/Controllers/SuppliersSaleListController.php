<?php

namespace App\Modules\Suppliers\Controllers;

use App\Exports\SuppliersSaleListExport;
use App\Models\WholesaleOrderGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 记录管理员操作日志
 */
class SuppliersSaleListController extends InitController
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper(['order']);

        $this->dscRepository->helpersLang(['statistic'], 'suppliers');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $this->smarty->assign('menu_select', array('action' => '04_suppliers_sale_order_stats', 'current' => 'suppliers_sale_list'));
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['04_suppliers_sale_order_stats']);

        if (isset($_REQUEST['act'])) {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_sale_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            if (isset($_REQUEST['start_date']) && strstr($_REQUEST['start_date'], '-') === false) {
                $_REQUEST['start_date'] = local_date('Y-m-d H:i:s', $_REQUEST['start_date']);
                $_REQUEST['end_date'] = local_date('Y-m-d H:i:s', $_REQUEST['end_date']);
            }

            /*------------------------------------------------------ */
            //-- Excel文件下载
            /*------------------------------------------------------ */
            if ($_REQUEST['act'] == 'download') {
                if ($_REQUEST['act'] == 'download') {
                    $file_name = str_replace(" ", "--", $_REQUEST['start_date'] . '_' . $_REQUEST['end_date'] . '_sale');

                    return Excel::download(new SuppliersSaleListExport, $file_name . '.xlsx');
                }
            }
            /*------------------------------------------------------ */
            //-- 排序、分页、查询
            /*------------------------------------------------------ */
            elseif ($_REQUEST['act'] == 'query') {
                /* 权限判断 */
                admin_priv('suppliers_sale_list');
                /* 时间参数 */
                if (!isset($_REQUEST['start_date'])) {
                    $start_date = local_strtotime('-7 days');
                }
                if (!isset($_REQUEST['end_date'])) {
                    $end_date = local_strtotime('today');
                }

                //$code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

                $sale_list_data = $this->get_sale_list();
                /* 赋值到模板 */
                $this->smarty->assign('filter', $sale_list_data['filter']);
                $this->smarty->assign('record_count', $sale_list_data['record_count']);
                $this->smarty->assign('page_count', $sale_list_data['page_count']);
                $this->smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
                $tpl = 'suppliers_sale_list.dwt';

                $this->smarty->assign('nowTime', gmtime());

                return make_json_result(
                    $this->smarty->fetch($tpl),
                    '',
                    array('filter' => $sale_list_data['filter'], 'page_count' => $sale_list_data['page_count'])
                );
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
                $os_list = array(0 => lang('suppliers/suppliers_sale_list.order_list.0'), 1 => lang('suppliers/suppliers_sale_list.order_list.1'));
                $this->smarty->assign('os_list', $os_list);
                $ss_list = array(0 => lang('suppliers/suppliers_sale_list.order_list.2'), 1 => lang('suppliers/suppliers_sale_list.order_list.3'), 2 => lang('suppliers/suppliers_sale_list.order_list.4'));
                $this->smarty->assign('ss_list', $ss_list);

                /* 显示页面 */

                return $this->smarty->display('suppliers_sale_list.dwt');
            }

        }
    }

    /**
     * 取得销售明细数据信息
     * @param   bool $is_pagination 是否分页
     * @return  array   销售明细数据
     */
    private function get_sale_list($is_pagination = true)
    {
        $adminru = get_admin_ru_id();
        /* 时间参数 */
        $filter['start_date'] = empty($_REQUEST['start_date']) ? local_strtotime('-7 days') : local_strtotime($_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? local_strtotime('today') : local_strtotime($_REQUEST['end_date']);
        $filter['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_number' : trim($_REQUEST['sort_by']);

        $filter['suppliers_id'] = !empty($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : $adminru['suppliers_id'];
        $filter['order_status'] = !empty($_REQUEST['order_status']) ? explode(',', $_REQUEST['order_status']) : '';
        $filter['shipping_status'] = !empty($_REQUEST['shipping_status']) ? explode(',', $_REQUEST['shipping_status']) : '';
        $filter['time_type'] = !empty($_REQUEST['time_type']) ? intval($_REQUEST['time_type']) : 0;

        $row = WholesaleOrderGoods::whereRaw(1);

        if ($filter['goods_sn']) {
            $row = $row->where('goods_sn', $filter['goods_sn']);
        }

        $row = $row->whereHas('getWholesaleOrderInfo', function ($query) use ($filter) {
            $query = $query->whereHas('getMainOrderId', function ($query) {
                $query->selectRaw("count(*) as count")->Having('count', 0);
            });

            if ($filter['suppliers_id'] > 0) {
                $query = $query->where('suppliers_id', $filter['suppliers_id']);
            }

            $time = $filter['end_date'] + 86400;

            if ($filter['time_type'] == 1) {
                $query = $query->where('add_time', '>=', $filter['start_date'])
                    ->where('add_time', '<=', $time);
            } else {
                $query = $query->where('shipping_time', '>=', $filter['start_date'])
                    ->where('shipping_time', '<=', $time);
            }

            if (!empty($filter['order_status'])) {
                $order_status = $this->baseRepository->getExplode($filter['order_status']);
                $query = $query->whereIn('order_status', $order_status);
            }

            if (!empty($filter['shipping_status'])) {
                $shipping_status = $this->baseRepository->getExplode($filter['shipping_status']);
                $query->whereIn('shipping_status', $shipping_status);
            }
        });

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with([
            'getWholesaleOrderInfo' => function ($query) {
                $query->with(['getSuppliers']);
            }
        ]);

        $res = $res->orderBy($filter['sort_by'], 'DESC');
        if ($is_pagination) {
            if ($filter['start'] > 0) {
                $res = $res->skip($filter['start']);
            }

            if ($filter['page_size'] > 0) {
                $res = $res->take($filter['page_size']);
            }
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $item) {
                $order = $item['get_wholesale_order_info'];
                $res[$key]['order_id'] = $order['order_id'];
                $res[$key]['order_sn'] = $order['order_sn'];
                $res[$key]['suppliers_id'] = $order['suppliers_id'];
                $suppliers_name = $order['get_suppliers']['suppliers_name'] ?? '';
                $res[$key]['shop_name'] = $suppliers_name;
                $res[$key]['goods_number'] = $item['goods_number'];
                $res[$key]['sales_price'] = $item['goods_price'];
                $res[$key]['total_fee'] = $item['goods_number'] * $item['goods_price'];
                $res[$key]['sales_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
            }
        }

        $arr = [
            'sale_list_data' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }
}
