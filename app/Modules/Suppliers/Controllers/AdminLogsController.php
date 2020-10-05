<?php

namespace App\Modules\Suppliers\Controllers;

use App\Models\AdminLog;
use App\Services\Log\AdminLogManageService;

/**
 * 记录管理员操作日志
 */
class AdminLogsController extends InitController
{
    protected $adminLogManageService;

    /**
     * AdminLogsController constructor.
     * @param AdminLogManageService $adminLogManageService
     */
    public function __construct(
        AdminLogManageService $adminLogManageService
    ) {
        $this->adminLogManageService = $adminLogManageService;
    }

    public function index()
    {
        $this->smarty->assign('menus', session('menus', ''));
        $this->smarty->assign('action_type', "privilege");
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $this->smarty->assign('menu_select', array('action' => '10_priv_admin', 'current' => 'admin_logs'));

        /*------------------------------------------------------ */
        //-- 获取所有日志列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限的判断 */
            admin_priv('suppliers_logs_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);

            /* 查询IP地址列表 */


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_logs']);
            $this->smarty->assign('full_page', 1);

            $ip_list = $this->adminLogManageService->getLogIp();
            $this->smarty->assign('ip_list', $ip_list);

            $log_list = $this->adminLogManageService->getAdminLogs();

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($log_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            $this->smarty->assign('current', 'admin_logs');

            return $this->smarty->display('admin_logs.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('suppliers_logs_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $log_list = $this->adminLogManageService->getAdminLogs();

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($log_list, $page);

            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);
            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $this->smarty->assign('current', 'admin_logs');
            return make_json_result(
                $this->smarty->fetch('admin_logs.dwt'),
                '',
                array('filter' => $log_list['filter'], 'page_count' => $log_list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 批量删除日志记录
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch_drop') {
            admin_priv('suppliers_logs_manage');

            $drop_type_date = isset($_POST['drop_type_date']) && $_POST['drop_type_date'] ? addslashes($_POST['drop_type_date']) : '';
            $log_date = isset($_POST['log_date']) && $_POST['log_date'] ? intval($_POST['log_date']) : 0;

            /* 按日期删除日志 */
            if ($drop_type_date) {
                if ($log_date == 0) {
                    return dsc_header("Location: admin_logs.php?act=list\n");
                } elseif ($log_date > 0) {
                    $res = AdminLog::whereRaw(1);

                    switch ($log_date) {
                        case 1:
                            $a_week = gmtime() - (3600 * 24 * 7);
                            $res = $res->where('log_time', '<=', $a_week);
                            break;
                        case 2:
                            $a_month = gmtime() - (3600 * 24 * 30);
                            $res = $res->where('log_time', '<=', $a_month);
                            break;
                        case 3:
                            $three_month = gmtime() - (3600 * 24 * 90);
                            $res = $res->where('log_time', '<=', $three_month);
                            break;
                        case 4:
                            $half_year = gmtime() - (3600 * 24 * 180);
                            $res = $res->where('log_time', '<=', $half_year);
                            break;
                        case 5:
                            $a_year = gmtime() - (3600 * 24 * 365);
                            $res = $res->where('log_time', '<=', $a_year);
                            break;
                    }

                    $id = $res->delete();

                    if ($id) {
                        admin_log('', 'remove', 'adminlog');
                    }

                    $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'admin_logs.php?act=list');
                    return sys_msg($GLOBALS['_LANG']['drop_sueeccud'], 0, $link);
                }
            } /* 如果不是按日期来删除, 就按ID删除日志 */
            else {
                $count = 0;
                foreach ($_POST['checkboxes'] as $key => $id) {
                    $result = AdminLog::where('log_id', $id)->delete();

                    if ($result) {
                        $count++;
                    }
                }

                if ($count) {
                    admin_log('', 'remove', 'adminlog');
                }

                $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'admin_logs.php?act=list');
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $count), 0, $link);
            }
        }
    }
}
