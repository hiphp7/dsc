<?php

namespace App\Modules\Admin\Controllers;

use App\Models\NoticeLog;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\NoticeLogs\NoticeLogsManageService;

/**
 * 记录管理员操作日志
 */
class NoticeLogsController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $noticeLogsManageService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        NoticeLogsManageService $noticeLogsManageService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->noticeLogsManageService = $noticeLogsManageService;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => 'notice_logs']);

        /*------------------------------------------------------ */
        //-- 获取所有日志列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限的判断 */
            admin_priv('notice_logs');

            $user_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $admin_ip = !empty($_REQUEST['ip']) ? $_REQUEST['ip'] : '';
            $log_date = !empty($_REQUEST['log_date']) ? $_REQUEST['log_date'] : '';

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['notice_logs_title']);
            // $this->smarty->assign('ip_list', $ip_list);
            $this->smarty->assign('full_page', 1);

            $log_list = $this->noticeLogsManageService->getNoticeLogs($adminru['ru_id']);

            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            return $this->smarty->display('notice_logs.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $log_list = $this->noticeLogsManageService->getNoticeLogs($adminru['ru_id']);

            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('notice_logs.dwt'),
                '',
                ['filter' => $log_list['filter'], 'page_count' => $log_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 批量删除日志记录
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch_drop') {
            admin_priv('notice_logs');

            $drop_type_date = isset($_POST['drop_type_date']) ? $_POST['drop_type_date'] : '';

            /* 按日期删除日志 */
            if ($drop_type_date) {

                $log_date = isset($_POST['log_date']) ? $_POST['log_date'] : 0;

                if (empty($log_date)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'notice_logs.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['select_date_value'], 1, $link);
                }

                if ($log_date > '0') {
                    $res = NoticeLog::whereRaw(1);
                    switch ($log_date) {
                        case '1':
                            $a_week = gmtime() - (3600 * 24 * 7);
                            $res = $res->where('send_time', '<=', $a_week);
                            break;
                        case '2':
                            $a_month = gmtime() - (3600 * 24 * 30);
                            $res = $res->where('send_time', '<=', $a_month);
                            break;
                        case '3':
                            $three_month = gmtime() - (3600 * 24 * 90);
                            $res = $res->where('send_time', '<=', $three_month);
                            break;
                        case '4':
                            $half_year = gmtime() - (3600 * 24 * 180);
                            $res = $res->where('send_time', '<=', $half_year);
                            break;
                        case '5':
                            $a_year = gmtime() - (3600 * 24 * 365);
                            $res = $res->where('send_time', '<=', $a_year);
                            break;
                    }

                    $res = $res->delete();

                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'notice_logs.php?act=list'];

                    if ($res) {
                        admin_log('', 'remove', 'noticelog');
                        return sys_msg($GLOBALS['_LANG']['drop_sueeccud'], 1, $link);
                    }

                    return sys_msg($GLOBALS['_LANG']['no_logs'], 1, $link);
                }
            } /* 如果不是按日期来删除, 就按ID删除日志 */
            else {
                $count = 0;
                $result = [];
                $checkboxes = request()->input('checkboxes', '');
                if (!empty($checkboxes)) {
                    foreach ($checkboxes as $key => $id) {
                        NoticeLog::where('id', $id)->delete();
                        $count++;
                    }
                }
                if ($result) {
                    admin_log('', 'remove', 'noticelog');

                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'notice_logs.php?act=list'];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $count), 0, $link);
                }
            }
        }
    }
}
