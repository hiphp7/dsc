<?php

namespace App\Modules\Admin\Controllers;

use App\Models\OssConfigure;
use App\Repositories\Common\BaseRepository;
use App\Services\Oss\OssConfigureManageService;

/**
 * 商品分类管理程序
 */
class OssConfigureController extends InitController
{

    protected $baseRepository;
    protected $ossConfigureManageService;

    public function __construct(
        BaseRepository $baseRepository,
        OssConfigureManageService $ossConfigureManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->ossConfigureManageService = $ossConfigureManageService;
    }

    public function index()
    {
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 检查权限 */
        admin_priv('oss_configure');

        $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => 'oss_configure']);
        /*------------------------------------------------------ */
        //-- OSS Bucket列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_oss_add'], 'href' => 'oss_configure.php?act=add']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['oss_configure']);
            $this->smarty->assign('form_act', 'insert');

            $bucket_list = $this->ossConfigureManageService->bucketList();

            $this->smarty->assign('bucket_list', $bucket_list['bucket_list']);
            $this->smarty->assign('filter', $bucket_list['filter']);
            $this->smarty->assign('record_count', $bucket_list['record_count']);
            $this->smarty->assign('page_count', $bucket_list['page_count']);
            $this->smarty->assign('full_page', 1);

            /* 列表页面 */

            return $this->smarty->display('oss_configure_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回Bucket列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $bucket_list = $this->ossConfigureManageService->bucketList();

            $this->smarty->assign('bucket_list', $bucket_list['bucket_list']);
            $this->smarty->assign('filter', $bucket_list['filter']);
            $this->smarty->assign('record_count', $bucket_list['record_count']);
            $this->smarty->assign('page_count', $bucket_list['page_count']);

            $sort_flag = sort_flag($bucket_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('oss_configure_list.dwt'), '', ['filter' => $bucket_list['filter'], 'page_count' => $bucket_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- OSS 添加Bucket
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_oss_list'], 'href' => 'oss_configure.php?act=list']);

            $bucket['regional'] = 'shanghai';
            $this->smarty->assign('bucket', $bucket);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['oss_configure']);
            $this->smarty->assign('form_act', 'insert');

            /* 列表页面 */

            return $this->smarty->display('oss_configure_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- OSS 编辑Bucket
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_oss_list'], 'href' => 'oss_configure.php?act=list']);

            $date = ['*'];
            $where = "id = '$id'";
            $bucket_info = get_table_date('oss_configure', $where, $date);
            $this->smarty->assign('bucket', $bucket_info);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['oss_configure']);
            $this->smarty->assign('form_act', 'update');

            /* 列表页面 */

            return $this->smarty->display('oss_configure_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- OSS 添加Bucket
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);


            $other['bucket'] = empty($_POST['bucket']) ? '' : trim($_POST['bucket']);
            $other['keyid'] = empty($_POST['keyid']) ? '' : trim($_POST['keyid']);
            $other['keysecret'] = empty($_POST['keysecret']) ? '' : trim($_POST['keysecret']);
            $other['is_cname'] = empty($_POST['is_cname']) ? '' : intval($_POST['is_cname']);
            $other['endpoint'] = empty($_POST['endpoint']) ? '' : trim($_POST['endpoint']);
            $other['regional'] = empty($_POST['regional']) ? '' : trim($_POST['regional']);
            $other['is_use'] = empty($_POST['is_use']) ? '' : intval($_POST['is_use']);

            $date = ['bucket'];
            $where = "bucket = '" . $other['bucket'] . "'";
            $where .= !empty($id) ? " AND id <> '$id'" : '';
            $bucket_info = get_table_date('oss_configure', $where, $date);

            if ($bucket_info) {
                return sys_msg($GLOBALS['_LANG']['add_failure'], 1);
            }

            if ($other['is_use'] == 1) {
                $data = ['is_use' => 0];
                OssConfigure::whereRaw('1')->update($data);
            }

            if (cache()->has('oss_bucket_info')) {
                cache()->forget('oss_bucket_info');
            }

            if ($id) {
                OssConfigure::where('id', $id)->update($other);
                $href = 'oss_configure.php?act=edit&id=' . $id;

                $lang_name = $GLOBALS['_LANG']['edit_success'];
            } else {
                OssConfigure::insert($other);
                $href = 'oss_configure.php?act=list';
                $lang_name = $GLOBALS['_LANG']['add_success'];
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => $href];
            return sys_msg(sprintf($lang_name, htmlspecialchars(stripslashes($other['bucket']))), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- OSS 批量删除Bucket
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove') {
            if (isset($_REQUEST['checkboxes'])) {

                $checkboxes = $this->baseRepository->getExplode($_REQUEST['checkboxes']);
                OssConfigure::whereIn('id', $checkboxes);

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'oss_configure.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
            } else {

                /* 提示信息 */
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'oss_configure.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_user'], 0, $lnk);
            }
        }

        /*------------------------------------------------------ */
        //-- OSS 删除Bucket
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $bucket = OssConfigure::where('id', $id)->value('bucket');
            $bucket = $bucket ? $bucket : '';

            OssConfigure::where('id', $id)->delete();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'oss_configure.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['remove_success'], $bucket), 0, $link);
        }
    }
}
