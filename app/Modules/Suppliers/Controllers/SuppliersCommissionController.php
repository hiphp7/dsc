<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Phpzip;
use App\Models\Users;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Services\Wholesale\WholesaleService;

/**
 * 记录管理员操作日志
 */
class SuppliersCommissionController extends InitController
{
    protected $wholesaleService;
    protected $baseRepository;

    public function __construct(
        WholesaleService $wholesaleService,
        BaseRepository $baseRepository
    ) {
        $this->wholesaleService = $wholesaleService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();
        $this->smarty->assign('suppliers_id', $adminru['suppliers_id']);
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['03_suppliers']);

        $this->smarty->assign('menu_select', array('action' => '03_suppliers', 'current' => '02_suppliers_commission'));

        /*------------------------------------------------------ */
        //-- 供应商订单列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'order_list') {
            admin_priv('suppliers_commission');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_suppliers_commission']);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('action_link', array('href' => 'javascript:order_downloadList();', 'text' => $GLOBALS['_LANG']['export_merchant_commission']));

            $order_list = $this->suppliers_commission_order_list($adminru['suppliers_id']);
            $this->smarty->assign('order_list', $order_list['list']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $valid = $this->wholesaleService->getSuppliersOrderValidRefund($adminru['suppliers_id']); //订单有效总额
            $this->smarty->assign('valid', $valid);

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);


            return $this->smarty->display('suppliers_order_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_query') {
            $check_auth = check_authz_json('suppliers_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_list = $this->suppliers_commission_order_list($adminru['suppliers_id']);
            $this->smarty->assign('order_list', $order_list['list']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $valid = $this->wholesaleService->getSuppliersOrderValidRefund($adminru['suppliers_id']); //订单有效总额
            $this->smarty->assign('valid', $valid);

            return make_json_result(
                $this->smarty->fetch('suppliers_order_list.dwt'),
                '',
                array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count'])
            );
        }

        /* ------------------------------------------------------ */
        //--Excel文件下载 商家佣金明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_download') {
            $check_auth = check_authz_json('suppliers_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = array('is_stop' => 0);

            $page = !empty($_REQUEST['page_down']) ? intval($_REQUEST['page_down']) : 0;//处理的页数
            $page_count = !empty($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0;//总页数
            $merchants_order_list = $this->suppliers_commission_order_list($adminru['suppliers_id'], $page);//获取订单数组
            $admin_id = get_admin_id();
            $merchants_download_content = read_static_cache("merchants_download_content_" . $admin_id);
            $merchants_download_content[] = $merchants_order_list['list'];
            write_static_cache("merchants_download_content_" . $admin_id, $merchants_download_content);

            $result['page'] = $page;
            if ($page < $page_count) {
                $result['is_stop'] = 1;//未结算标识
                $result['next_page'] = $page + 1;
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //--Excel文件下载 商家佣金明细
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'merchant_download') {
            header("Content-Disposition: attachment; filename=" . date('YmdHis') . ".zip");
            header("Content-Type: application/unknown");
            // 获取所有商家的下载数据 按照商家分组

            $admin_id = get_admin_id();
            $merchants_download_content = read_static_cache("merchants_download_content_" . $admin_id);
            $valid = $this->wholesaleService->getSuppliersOrderValidRefund($adminru['suppliers_id']); //订单有效总额
            $zip = new PHPZip;

            if (!empty($merchants_download_content)) {
                foreach ($merchants_download_content as $k => $merchants_order_list) {
                    $k++;
                    $content = $this->order_download_list($merchants_order_list);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', lang('suppliers/suppliers_commission.sum_price'));
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $valid['brokerage_amount']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', lang('suppliers/suppliers_commission.settled'));
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $valid['formated_is_settlement_amout']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', lang('suppliers/suppliers_commission.unsettled_accounts'));
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $valid['formated_no_settlement_amout']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', lang('suppliers/suppliers_commission.percent_value'));
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $valid['suppliers_percent'])."%" . "\t\n";
                    $zip->add_file($content, date('YmdHis') . '-' . $k . '.csv');
                }
            }

            /* 清除缓存 */
            $dir = storage_public('/temp/static_caches/merchants_download_content_' . $admin_id . ".php");
            if (is_file($dir)) {
                dsc_unlink($dir);
            }

            die($zip->file());
        }
    }

    /**
     * @param array $result
     * @return string
     */
    private function order_download_list($result = [])
    {
        if (empty($result)) {
            return i(lang('suppliers/suppliers_commission.empty_data'));
        }

        $downLang = $GLOBALS['_LANG']['down']['order_sn'] . "," .
            $GLOBALS['_LANG']['down']['short_order_time'] . "," .
            $GLOBALS['_LANG']['down']['consignee_address'] . "," .
            $GLOBALS['_LANG']['down']['total_fee'] . "," .
            $GLOBALS['_LANG']['down']['brokerage_amount_price'] . "," .
            $GLOBALS['_LANG']['down']['settlement_status'] . "," .
            $GLOBALS['_LANG']['down']['ordersTatus'];

        $data = $this->i($downLang . "\n");
        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            $order_sn = $this->i('#' . $result[$i]['order_sn']);
            $short_order_time = $this->i($result[$i]['add_time']);
            $consignee = $this->i($result[$i]['consignee']) . "" . $this->i($result[$i]['address']);
            $total_fee = $this->i($result[$i]['download_order_amount']);

            $brokerage_amount_price = $this->i($result[$i]['download_effective_amount_into']);
            $brokerage_amount = $this->i($result[$i]['download_brokerage_amount']);
            if ($result[$i]['is_settlement'] == 1) {
                $is_settlement = $this->i(lang('suppliers/suppliers_commission.settled_s'));
            } else {
                $is_settlement = $this->i(lang('suppliers/suppliers_commission.unsettled_accounts_s'));
            }

            if ($consignee) {
                $consignee = str_replace(array(",", "，"), "_", $consignee);
            }

            $data .= $order_sn . ',' . $short_order_time . ',' . $consignee . ',' . $total_fee . ',' .
                $brokerage_amount_price . ',' . $brokerage_amount . ',' . $is_settlement . "\n";
        }

        return $data;
    }

    private function i($strInput)
    {
        return iconv('utf-8', 'gb2312//ignore', $strInput);//页面编码为utf-8时使用，否则导出的中文为乱码
    }

    /**
     * 供货商结算订单列表
     * @return  array
     */
    private function suppliers_commission_order_list($suppliers_id = 0, $page = 0)
    {
        /* 初始化分页参数 */
        $filter = array();
        $filter['suppliers_id'] = $suppliers_id;
        $filter['state'] = isset($_REQUEST['state']) ? trim($_REQUEST['state']) : '-1'; //结算状态
        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : local_strtotime(trim($_REQUEST['start_time']));
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : local_strtotime(trim($_REQUEST['end_time']));
        $filter['order_sn'] = !isset($_REQUEST['order_sn']) && empty($_REQUEST['order_sn']) ? '' : addslashes($_REQUEST['order_sn']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'order_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = WholesaleOrderInfo::mainOrderCount();

        if ($filter['suppliers_id'] > 0) {
            $row = $row->where('suppliers_id', $filter['suppliers_id']);
        }

        if ($filter['state'] > -1) {
            $row = $row->where('is_settlement', $filter['state']);
        }

        if (!empty($filter['start_time'])) {
            $row = $row->where('add_time', '>', $filter['start_time']);
        }

        if (!empty($filter['end_time'])) {
            $row = $row->where('add_time', '<=', $filter['end_time']);
        }

        if ($filter['order_sn']) {
            $row = $row->where('add_time', 'like', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }

        //查询已支付，一完成的订单 或者部分退款订单
        $row = $row->where(function ($query) {
            $query = $query->where(function ($query) {
                $query->where('pay_status', PS_PAYED)->where('order_status', 1);
            });

            $query->orWhere(function ($query) {
                $query->where('pay_status', 5)->where('order_status', 4);
            });
        });

        $res = $record_count = $row;

        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = $record_count->count();
        $filter = page_and_size($filter);

        $res = $res->with([
            'getUser'
        ]);

        if ($page > 0) {
            $filter['page'] = $page;
        }

        /* 查询记录 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = array();
        $suppliers_percent = get_table_date('suppliers', "suppliers_id='" . $suppliers_id . "'", array('suppliers_percent'), 2);//获取供应商当前结算比例
        $suppliers_percent = empty($suppliers_percent) ? 1 : $suppliers_percent / 100;//如果比例为0的话 默认为1

        if ($res) {
            foreach ($res as $key => $rows) {
                $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

                $user_name = $rows['get_user']['user_name'] ?? '';
                $user_name = $user_name ? $user_name : '';

                $rows['user_name'] = $user_name;

                $effective_amount_into = $rows['order_amount'];//有效结算
                $brokerage_amount = ($effective_amount_into - $rows['return_amount']) * $suppliers_percent;//应结金额
                //
                //导出数据
                $rows['download_order_amount'] = $rows['order_amount'];
                $rows['download_brokerage_amount'] = $brokerage_amount;
                $rows['download_effective_amount_into'] = $effective_amount_into;
                //格式化金额
                $rows['effective_amount_into'] = price_format($effective_amount_into);
                $rows['brokerage_amount'] = price_format($brokerage_amount);
                $rows['order_amount'] = price_format($rows['order_amount']);//订单总金额
                $rows['formated_return_amount'] = price_format($rows['return_amount']);//退款金额
                $arr[] = $rows;
            }
        }

        $arr = [
            'list' => $arr,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }
}
