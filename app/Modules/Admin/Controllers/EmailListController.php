<?php

namespace App\Modules\Admin\Controllers;

use App\Models\EmailList;
use App\Repositories\Common\BaseRepository;
use App\Services\Email\EmailListManageService;

/**
 * 邮件列表管理
 */
class EmailListController extends InitController
{
    protected $baseRepository;
    protected $emailListManageService;

    public function __construct(
        BaseRepository $baseRepository,
        EmailListManageService $emailListManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->emailListManageService = $emailListManageService;
    }

    public function index()
    {
        admin_priv('email_list');

        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['export'], 'href' => 'email_list.php?act=export']);
            $emaildb = $this->emailListManageService->getEmailList();
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_email_list']);
            $this->smarty->assign('emaildb', $emaildb['emaildb']);
            $this->smarty->assign('filter', $emaildb['filter']);
            $this->smarty->assign('record_count', $emaildb['record_count']);
            $this->smarty->assign('page_count', $emaildb['page_count']);

            return $this->smarty->display('email_list.dwt');
        } elseif ($_REQUEST['act'] == 'export') {
            $res = EmailList::where('stat', 1);
            $emails = $this->baseRepository->getToArrayGet($res);

            $out = '';
            foreach ($emails as $key => $val) {
                $out .= "$val[email]\n";
            }
            $contentType = 'text/plain';
            $len = strlen($out);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            header('Pragma: no-cache');
            header('Content-Encoding: none');
            header('Content-type: ' . $contentType);
            header('Content-Length: ' . $len);
            header('Content-Disposition: attachment; filename="email_list.txt"');
            echo $out;
        } elseif ($_REQUEST['act'] == 'query') {
            $emaildb = $this->emailListManageService->getEmailList();
            $this->smarty->assign('emaildb', $emaildb['emaildb']);
            $this->smarty->assign('filter', $emaildb['filter']);
            $this->smarty->assign('record_count', $emaildb['record_count']);
            $this->smarty->assign('page_count', $emaildb['page_count']);

            $sort_flag = sort_flag($emaildb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('email_list.dwt'),
                '',
                ['filter' => $emaildb['filter'], 'page_count' => $emaildb['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 批量删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove') {
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_email'], 1);
            }

            $res = EmailList::whereIn('id', $_POST['checkboxes'])->delete();

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'email_list.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['batch_remove_succeed'], $res), 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 批量恢复
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_unremove') {
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_email'], 1);
            }

            $data = ['stat' => 1];
            $res = EmailList::where('stat', '<>', 1)->whereIn('id', $_POST['checkboxes'])->update($data);

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'email_list.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['batch_unremove_succeed'], $res), 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 批量退订
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_exit') {
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_email'], 1);
            }

            $data = ['stat' => 2];
            $res = EmailList::where('stat', '<>', 2)->where('id', $_POST['checkboxes'])->update($data);

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'email_list.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['batch_exit_succeed'], $res), 0, $lnk);
        }
    }
}
