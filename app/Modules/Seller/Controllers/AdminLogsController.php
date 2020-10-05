<?php

namespace App\Modules\Seller\Controllers;

use App\Models\AdminLog;
use App\Repositories\Common\BaseRepository;
use App\Services\Log\AdminLogManageService;

/**
 * 记录管理员操作日志
 */
class AdminLogsController extends InitController
{

    protected $adminLogManageService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        AdminLogManageService $adminLogManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->adminLogManageService = $adminLogManageService;
    }

    public function index()
    {

        $menus = session()->has('menus') ? session('menus') : '';
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "privilege");

        /* act操作项的初始化 */
        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        $this->smarty->assign('menu_select', ['action' => '10_priv_admin', 'current' => 'admin_logs']);

        /*------------------------------------------------------ */
        //-- 获取所有日志列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            /* 权限的判断 */
            admin_priv('logs_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);

            $page = request()->get('page', 1);

            /* 查询IP地址列表 */
            $ip_list = [];
            $res = AdminLog::select("ip_address");
            $res = $res->distinct($res);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $row) {
                $ip_list[$row['ip_address']] = $row['ip_address'];
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_logs']);
            $this->smarty->assign('ip_list', $ip_list);
            $this->smarty->assign('full_page', 1);

            $log_list = $this->adminLogManageService->getAdminLogs();

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
        elseif ($act == 'query') {
            $page = request()->get('page', 1);

            $log_list = $this->adminLogManageService->getAdminLogs();
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
                ['filter' => $log_list['filter'], 'page_count' => $log_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 批量删除日志记录
        /*------------------------------------------------------ */
        if ($act == 'batch_drop') {
            admin_priv('logs_drop');

            $drop_type_date = request()->get('drop_type_date', '');

            /* 按日期删除日志 */
            if ($drop_type_date) {
                $log_date = (int)request()->input('log_date', 0);
                if ($log_date == '0') {
                    return dsc_header("Location: admin_logs.php?act=list\n");
                } elseif ($log_date > '0') {
                    $res = AdminLog::whereRaw(1);

                    switch ($log_date) {
                        case '1':
                            $a_week = gmtime() - (3600 * 24 * 7);
                            $res = $res->where('log_time', '<=', $a_week);
                            break;
                        case '2':
                            $a_month = gmtime() - (3600 * 24 * 30);
                            $res = $res->where('log_time', '<=', $a_month);
                            break;
                        case '3':
                            $three_month = gmtime() - (3600 * 24 * 90);
                            $res = $res->where('log_time', '<=', $three_month);
                            break;
                        case '4':
                            $half_year = gmtime() - (3600 * 24 * 180);
                            $res = $res->where('log_time', '<=', $half_year);
                            break;
                        case '5':
                            $a_year = gmtime() - (3600 * 24 * 365);
                            $res = $res->where('log_time', '<=', $a_year);
                            break;
                    }
                    $res = $res->delete();

                    if ($res) {
                        admin_log('', 'remove', 'adminlog');

                        $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'admin_logs.php?act=list'];
                        return sys_msg($GLOBALS['_LANG']['drop_sueeccud'], 0, $link);
                    }
                }
            } /* 如果不是按日期来删除, 就按ID删除日志 */
            else {
                $count = 0;
                $checkboxes = request()->input('checkboxes', []);
                foreach ($checkboxes as $key => $id) {

                    $result = AdminLog::where('log_id', $id)->delete();
                    $count++;
                }
                if ($result) {
                    admin_log('', 'remove', 'adminlog');

                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'admin_logs.php?act=list'];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $count), 0, $link);
                }
            }
        }
    }

}
