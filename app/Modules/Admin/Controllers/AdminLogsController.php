<?php

namespace App\Modules\Admin\Controllers;

use App\Services\Log\AdminLogManageService;

/**
 * 记录管理员操作日志
 */
class AdminLogsController extends InitController
{
    protected $adminLogManageService;

    public function __construct(
        AdminLogManageService $adminLogManageService
    )
    {
        $this->adminLogManageService = $adminLogManageService;
    }

    public function index()
    {
        /* act操作项的初始化 */
        $act = request()->input('act', 'list');
        $act = trim($act);

        /*------------------------------------------------------ */
        //-- 获取所有日志列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            /* 权限的判断 */
            admin_priv('logs_manage');

            /* 查询IP地址列表 */
            $ip_list = $this->adminLogManageService->getLogIp();

            $this->smarty->assign('ur_here', lang('admin/common.admin_logs'));
            $this->smarty->assign('ip_list', $ip_list);
            $this->smarty->assign('full_page', 1);

            $log_list = $this->adminLogManageService->getAdminLogs();

            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return $this->smarty->display('admin_logs.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            $log_list = $this->adminLogManageService->getAdminLogs();

            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

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
            // 权限
            admin_priv('logs_drop');

            $drop_type_date = request()->get('drop_type_date', '');

            //$drop_type_date = isset($_POST['drop_type_date']) ? $_POST['drop_type_date'] : '';

            /* 按日期删除日志 */
            if ($drop_type_date) {
                $log_date = request()->get('log_date', 0);
                if ($log_date <= 0) {
                    return dsc_header("Location: admin_logs.php?act=list\n");
                }
                $res = $this->adminLogManageService->getAdminLogAatchDrop($log_date);
                if ($res) {
                    admin_log('', 'remove', 'adminlog');
                }

                $link[] = ['text' => lang('admin/common.back_list'), 'href' => 'admin_logs.php?act=list'];
                return sys_msg(lang('admin/admin_logs.drop_sueeccud'), 0, $link);
            } else {
                /* 如果不是按日期来删除, 就按ID删除日志 */
                $count = 0;
                $checkboxes = request()->get('checkboxes', '');

                if (empty($checkboxes)) {
                    return dsc_header("Location: admin_logs.php?act=list\n");
                }

                if ($checkboxes) {
                    foreach ($checkboxes as $key => $id) {
                        $count++;
                    }

                    $this->adminLogManageService->getAdminLogIdDel($checkboxes);

                    if ($count > 0) {
                        admin_log('', 'remove', 'adminlog');
                    }
                    $link[] = ['text' => lang('admin/common.back_list'), 'href' => 'admin_logs.php?act=list'];
                    return sys_msg(sprintf(lang('admin/admin_logs.batch_drop_success'), $count), 0, $link);
                }
            }
        }
    }
}
