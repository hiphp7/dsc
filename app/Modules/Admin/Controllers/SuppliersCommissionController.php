<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Phpzip;
use App\Repositories\Common\DscRepository;
use App\Services\Wholesale\OrderManageService;
use App\Services\Wholesale\WholesaleService;

class SuppliersCommissionController extends InitController
{
    protected $wholesaleService;
    protected $orderManageService;
    protected $dscRepository;

    public function __construct(
        WholesaleService $wholesaleService,
        OrderManageService $orderManageService,
        DscRepository $dscRepository
    )
    {
        $this->wholesaleService = $wholesaleService;
        $this->orderManageService = $orderManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper(['wholesale', 'suppliers']);

        /*------------------------------------------------------ */
        //-- 办事处列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {

            /* 检查权限 */
            admin_priv('suppliers_commission');

            $this->smarty->assign('action_link', array('href' => 'javascript:download_list();', 'text' => $GLOBALS['_LANG']['export_all_suppliers']));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_suppliers_commission']);
            $this->smarty->assign('full_page', 1);

            $commission_list = $this->getSuppliersCommissionList();
            $this->smarty->assign('commission_list', $commission_list['list']);
            $this->smarty->assign('filter', $commission_list['filter']);
            $this->smarty->assign('record_count', $commission_list['record_count']);
            $this->smarty->assign('page_count', $commission_list['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($commission_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return $this->smarty->display('suppliers_commission_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $commission_list = $this->getSuppliersCommissionList();
            $this->smarty->assign('commission_list', $commission_list['list']);
            $this->smarty->assign('filter', $commission_list['filter']);
            $this->smarty->assign('record_count', $commission_list['record_count']);
            $this->smarty->assign('page_count', $commission_list['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($commission_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('suppliers_commission_list.dwt'),
                '',
                array('filter' => $commission_list['filter'], 'page_count' => $commission_list['page_count'])
            );
        }

        /* ------------------------------------------------------ */
        //--Excel文件下载 所有商家
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'commission_download') {

            /* 检查权限 */
            admin_priv('suppliers_commission');

            $filename = local_date('YmdHis') . ".csv";
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=" . $filename);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');

            $commission_list = $this->getSuppliersCommissionList();

            echo $this->getCommissionDownloadList($commission_list['list']);
            exit;
        }

        /*------------------------------------------------------ */
        //-- 供应商订单列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'suppliers_order_list') {
            /* 检查权限 */
            admin_priv('suppliers_commission');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['suppliers_order_list']);
            $this->smarty->assign('action_link', array('href' => 'javascript:order_downloadList();', 'text' => $GLOBALS['_LANG']['export_merchant_commission'])); //liu
            $this->smarty->assign('full_page', 1);
            $suppliers_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $order_list = $this->getSuppliersCommissionOrderList($suppliers_id);
            $this->smarty->assign('order_list', $order_list['list']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $valid = $this->wholesaleService->getSuppliersOrderValidRefund($suppliers_id); //订单有效总额
            $this->smarty->assign('valid', $valid);
            $this->smarty->assign('suppliers_id', $suppliers_id);

            return $this->smarty->display('suppliers_order_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $suppliers_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $order_list = $this->getSuppliersCommissionOrderList($suppliers_id);
            $this->smarty->assign('order_list', $order_list['list']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('action_link', array('href' => 'javascript:order_downloadList();', 'text' => $GLOBALS['_LANG']['export_merchant_commission'])); //liu

            $valid = $this->wholesaleService->getSuppliersOrderValidRefund($suppliers_id); //订单有效总额
            $this->smarty->assign('valid', $valid);

            return make_json_result(
                $this->smarty->fetch('suppliers_order_list.dwt'),
                '',
                array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count'])
            );
        }

        /* ------------------------------------------------------ */
        //-- 修改结算状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_settlement') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            /* 检查是否重复 */

            $order_exc = $this->orderManageService->getSuppliersSettlementAmount($order_id);
            if ($order_exc['is_settlement']) {
                return make_json_error($GLOBALS['_LANG']['not_settlement']);
            }

            $nowTime = gmtime();

            $other['admin_id'] = session('admin_id');
            $other['suppliers_id'] = $order_exc['suppliers_id'];
            $other['order_id'] = $order_id;
            $other['amount'] = $order_exc['brokerage_amount']; //结算金额
            $other['add_time'] = $nowTime; //结算时间
            $other['log_type'] = 2;
            $other['is_paid'] = 1;
            $other['suppliers_percent'] = $order_exc['suppliers_percent'];//记录结算的佣金比例

            $sql = "UPDATE " . $this->dsc->table('wholesale_order_info') . " SET is_settlement = '$on_sale' WHERE order_id = '$order_id'";
            $this->db->query($sql);

            $this->db->autoExecute($this->dsc->table('suppliers_account_log_detail'), $other, 'INSERT');

            log_suppliers_account_change($order_exc['suppliers_id'], $order_exc['brokerage_amount']);//更新供应商余额

            $change_desc = sprintf($GLOBALS['_LANG']['01_admin_settlement'], session('admin_name'), $order_exc['order_sn']);

            suppliers_account_log($order_exc['suppliers_id'], $order_exc['brokerage_amount'], 0, $change_desc, 2);//更新供应商余额变动日志

            clear_cache_files();
            return make_json_result($on_sale);
        }

        /* ------------------------------------------------------ */
        //--Excel文件下载 商家佣金明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_download') {
            $result = array('is_stop' => 0);
            $suppliers_id = empty($_REQUEST['suppliers_id']) ? 0 : intval($_REQUEST['suppliers_id']);
            $page = !empty($_REQUEST['page_down']) ? intval($_REQUEST['page_down']) : 0;//处理的页数
            $page_count = !empty($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0;//总页数
            $merchants_order_list = $this->getSuppliersCommissionOrderList($suppliers_id, $page);//获取订单数组

            $admin_id = get_admin_id();

            $merchants_download_content = cache("merchants_suppliers_download_content_" . $admin_id . $suppliers_id);

            $merchants_download_content = !is_null($merchants_download_content) ? $merchants_download_content : [];

            $merchants_download_content[] = $merchants_order_list['list'];


            cache()->forever("merchants_suppliers_download_content_" . $admin_id . $suppliers_id, $merchants_download_content);

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

            $suppliers_id = empty($_REQUEST['suppliers_id']) ? 0 : intval($_REQUEST['suppliers_id']);

            $suppliers_name = get_table_date('suppliers', "suppliers_id='$suppliers_id'", array('suppliers_name'), 2);

            $filename = $suppliers_name . date('YmdHis') . ".zip";

            // 获取所有商家的下载数据 按照商家分组

            $admin_id = get_admin_id();

            $merchants_download_content = cache("merchants_suppliers_download_content_" . $admin_id . $suppliers_id);

            $merchants_download_content = !is_null($merchants_download_content) ? $merchants_download_content : false;

            $valid = $this->wholesaleService->getSuppliersOrderValidRefund($suppliers_id); //订单有效总额
            $zip = new PHPZip;

            if (!empty($merchants_download_content)) {
                foreach ($merchants_download_content as $k => $merchants_order_list) {
                    $k++;
                    $content = $this->getOrderDownloadList($merchants_order_list);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', lang('admin/suppliers_commission.total_sum'));
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $valid['brokerage_amount']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', lang('admin/suppliers_commission.settled_s'));
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $valid['formated_is_settlement_amout']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', lang('admin/suppliers_commission.unsettled_s'));
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $valid['formated_no_settlement_amout']) . "\t\n";
                    $zip->add_file($content, date('YmdHis') . '-' . $k . '.csv');
                }
            }

            /* 清除缓存 */
            cache()->forget("merchants_suppliers_download_content_" . $admin_id . $suppliers_id);

            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        }
    }

    /**
     * @param array $result
     * @return string
     */
    private function getOrderDownloadList($result = [])
    {
        if (empty($result)) {
            return $this->i(lang('admin/suppliers_commission.data_null'));
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
                $is_settlement = $this->i(lang('admin/suppliers_commission.settled'));
            } else {
                $is_settlement = $this->i(lang('admin/suppliers_commission.unsettled'));
            }

            if ($consignee) {
                $consignee = str_replace(array(",", "，"), "_", $consignee);
            }

            $data .= $order_sn . ',' . $short_order_time . ',' . $consignee . ',' . $total_fee . ',' .
                $brokerage_amount_price . ',' .
                $brokerage_amount . ',' . $is_settlement . "\n";
        }

        return $data;
    }

    private function i($strInput)
    {
        return iconv('utf-8', 'gb2312', $strInput);//页面编码为utf-8时使用，否则导出的中文为乱码
    }

    /**
     * 供货商结算订单列表
     * @return  array
     */
    private function getSuppliersCommissionOrderList($suppliers_id = 0, $page = 0)
    {
        $result = get_filter();

        if ($result === false) {
            /* 初始化分页参数 */
            $filter = array();
            $filter['suppliers_id'] = $suppliers_id;
            $filter['state'] = isset($_REQUEST['state']) ? trim($_REQUEST['state']) : '-1'; //结算状态
            $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : local_strtotime(trim($_REQUEST['start_time']));
            $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : local_strtotime(trim($_REQUEST['end_time']));
            $filter['order_sn'] = !isset($_REQUEST['order_sn']) && empty($_REQUEST['order_sn']) ? '' : addslashes($_REQUEST['order_sn']);
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'oi.order_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = ' WHERE 1';
            if ($filter['suppliers_id'] > 0) {
                $where .= " AND oi.suppliers_id = '" . $filter['suppliers_id'] . "'";
            }
            if ($filter['state'] > -1) {
                $where .= " AND oi.is_settlement = '" . $filter['state'] . "' ";
            }
            if (!empty($filter['start_time'])) {
                $where .= " AND oi.add_time >= '" . $filter['start_time'] . "' ";
            }

            if (!empty($filter['end_time'])) {
                $where .= " AND oi.add_time <= '" . $filter['end_time'] . "' ";
            }

            if ($filter['order_sn']) {
                $where .= " AND oi.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
            }

            //有主订单事  去除主订单
            $where .= " AND (select count(*) from " . $GLOBALS['dsc']->table('wholesale_order_info') . " as oi2 where oi2.main_order_id = oi.order_id limit 0, 1) = 0";

            $where .= ' AND  ((oi.pay_status =2 AND oi.order_status = 1) OR (oi.pay_status = 5 AND oi.order_status = 4)) ';//查询已支付，一完成的订单 或者部分退款订单

            /* 查询记录总数，计算分页数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi" . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);
            $filter = page_and_size($filter);
            if ($page > 0) {
                $filter['page'] = $page;
            }
            /* 查询记录 */
            $sql = "SELECT oi.return_amount,oi.add_time,oi.suppliers_id,oi.consignee,oi.address,oi.user_id,order_amount,oi.order_sn,oi.order_id,oi.is_settlement FROM " .
                $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi" . $where .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $res = $GLOBALS['db']->getAll($sql);

        $arr = array();
        $suppliers_percent = get_table_date('suppliers', "suppliers_id='" . $suppliers_id . "'", array('suppliers_percent'), 2);//获取供应商当前结算比例
        $suppliers_percent = empty($suppliers_percent) ? 1 : $suppliers_percent / 100;//如果比例为0的话 默认为1

        if ($res) {
            foreach ($res as $key => $rows) {
                $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
                $rows['user_name'] = get_table_date('users', "user_id='" . $rows['user_id'] . "'", array('user_name'), 2);

                $effective_amount_into = $rows['order_amount'];//有效结算
                $brokerage_amount = ($effective_amount_into - $rows['return_amount']) * $suppliers_percent;//应结金额
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
            return array('list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
        }
    }

    /**
     * 供货商结算列表
     * @return  array
     */
    private function getSuppliersCommissionList()
    {
        $result = get_filter();
        if ($result === false) {
            /* 初始化分页参数 */
            $filter = array();
            $filter['suppliers_id'] = empty($_REQUEST['suppliers_id']) ? 0 : intval($_REQUEST['suppliers_id']);
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'suppliers_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            /* 查询记录总数，计算分页数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('suppliers');
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            $filter = page_and_size($filter);

            /* 查询记录 */
            $sql = "SELECT suppliers_id,suppliers_name,company_name,company_address,mobile_phone FROM " . $GLOBALS['dsc']->table('suppliers') .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $res = $GLOBALS['db']->getAll($sql);

        $arr = array();
        foreach ($res as $key => $rows) {
            $valid = $this->wholesaleService->getSuppliersOrderValidRefund($rows['suppliers_id']); //订单有效总额
            //导出数据
            $rows['download_order_amount'] = $valid['order_amount'];
            $rows['download_return_amount'] = $valid['return_amount'];
            $rows['download_is_settlement_amout'] = $valid['is_settlement_amout'];
            $rows['download_no_settlement_amout'] = $valid['no_settlement_amout'];

            $rows['order_valid_total'] = price_format($valid['order_amount']);//订单总额
            $rows['is_settlement_amout'] = $valid['formated_is_settlement_amout'];//已结算总金额
            $rows['no_settlement_amout'] = $valid['formated_no_settlement_amout'];//未结算总金额
            $rows['formated_return_amount'] = $valid['formated_return_amount'];//退款金额

            $suppliers_percent = get_table_date('suppliers', "suppliers_id='" . $rows['suppliers_id'] . "'", array('suppliers_percent'), 2);
            $rows['suppliers_percent'] = empty($suppliers_percent) ? 100 : $suppliers_percent;

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $rows['mobile_phone'] = $this->dscRepository->stringToStar($rows['mobile_phone']);
            }

            $arr[] = $rows;
        }

        return array('list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    }

    private function getCommissionDownloadList($result)
    {
        if (empty($result)) {
            return $this->i(lang('admin/suppliers_commission.data_null'));
        }

        $data = $this->i(lang('admin/suppliers_commission.title_attr') . "\n");
        $count = count($result);

        for ($i = 0; $i < $count; $i++) {
            $user_name = $this->i($result[$i]['suppliers_name']);
            $companyName = $this->i($result[$i]['company_name']);
            $company_adress = $this->i($result[$i]['company_address']);
            $company_contactTel = $this->i($result[$i]['mobile_phone']);
            $company_percent = $this->i($result[$i]['suppliers_percent'] . '%');
            $order_valid_total = $this->i(isset($result[$i]['download_order_amount']) ? $result[$i]['download_order_amount'] : 0.00);
            $order_return_amount = $this->i(isset($result[$i]['download_return_amount']) ? $result[$i]['download_return_amount'] : 0.00);

            $is_settlement = $this->i(isset($result[$i]['download_is_settlement_amout']) ? $result[$i]['download_is_settlement_amout'] : 0.00);
            $no_settlement = $this->i(isset($result[$i]['download_no_settlement_amout']) ? $result[$i]['download_no_settlement_amout'] : 0.00);

            if ($company_adress) {
                $company_adress = str_replace(array(",", "，"), "_", $company_adress);
            }

            $data .= $user_name . ',' . $companyName . ',' .
                $company_adress . ',' . $company_contactTel . ',' . $company_percent . ',' .
                $order_valid_total . ',' . $order_return_amount . ',' .
                $is_settlement . ',' . $no_settlement . "\n";
        }

        return $data;
    }
}
