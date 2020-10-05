<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ZcTopic;
use App\Repositories\Common\BaseRepository;
use App\Services\Message\ZcManageService;

/**
 * 众筹话题管理
 */
class ZcTopicController extends InitController
{
    protected $zcManageService;
    protected $baseRepository;

    public function __construct(
        ZcManageService $zcManageService,
        BaseRepository $baseRepository
    )
    {
        $this->zcManageService = $zcManageService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        $this->smarty->assign('act', $_REQUEST['act']);
        /*------------------------------------------------------ */
        //-- 列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限检查 */
            admin_priv('zc_topic_manage');

            /* 列表类型：父子话题 */
            if (isset($_REQUEST['parent_id']) && !empty($_REQUEST['parent_id'])) {
                $this->smarty->assign('child_list', 1);
                $this->smarty->assign('action_link', ['href' => 'zc_topic.php?act=list', 'text' => $GLOBALS['_LANG']['zc_parent_list']]);
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_topic_list']);
            $list = $this->zcManageService->zcTopicList();
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('list', $list['topic_list']);
            return $this->smarty->display('zc_topic_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->zcManageService->zcTopicList();
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('list', $list['topic_list']);   //  把结果赋值给页面
            return make_json_result(
                $this->smarty->fetch('zc_topic_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 修改显示状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_display') {
            $check_auth = check_authz_json('zc_topic_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $topic_id = intval($_POST['id']);
            $topic_status = intval($_POST['val']);

            ZcTopic::where('topic_id', $topic_id)->update([
                'topic_status' => $topic_status
            ]);

            return make_json_result($topic_status);
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            /* 权限检查 */
            admin_priv('zc_topic_manage');

            $topic_id = intval($_REQUEST['id']);

            $child_topic_num = ZcTopic::where('parent_topic_id', $topic_id)->count();

            if ($child_topic_num > 0) {
                $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
                $links[0]['href'] = 'javascript:history.go(-1)';
                return sys_msg($GLOBALS['_LANG']['zc_child_exist'], 0, $links);
            }

            ZcTopic::where('topic_id', $topic_id)->delete();

            return dsc_header('Location:zc_topic.php?act=list');
        }

        /*------------------------------------------------------ */
        //-- 批量删除用户评论
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('zc_topic_manage');

            $action = isset($_POST['sel_action']) ? trim($_POST['sel_action']) : 'deny';
            $topic_id = $this->baseRepository->getExplode($_POST['checkboxes']);

            $note = "";
            if (isset($_POST['checkboxes'])) {
                switch ($action) {
                    case 'remove':
                        $zt = 0;
                        foreach ($_POST['checkboxes'] as $key => $val) {

                            $child_topic_num = ZcTopic::where('parent_topic_id', $val)->count();

                            if ($child_topic_num > 0) {
                                $zt++;
                                unset($_POST['checkboxes'][$key]);
                            }
                        }

                        if ($zt > 0) {
                            $note = sprintf($GLOBALS['_LANG']['batch_drop_note'], $zt);
                        }

                        ZcTopic::whereIn('topic_id', $topic_id)->delete();
                        break;

                    case 'allow':
                        ZcTopic::whereIn('topic_id', $topic_id)->update([
                            'topic_status' => 1
                        ]);
                        break;

                    case 'deny':
                        ZcTopic::whereIn('topic_id', $topic_id)->update([
                            'topic_status' => 0
                        ]);
                        break;

                    default:
                        break;
                }

                $link[] = ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'zc_topic.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($_POST['checkboxes'])) . $note, 0, $link);
            } else {
                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'zc_topic.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_topic'], 0, $link);
            }
        }
    }
}
