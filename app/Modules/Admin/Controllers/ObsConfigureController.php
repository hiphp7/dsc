<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ObsConfigure;
use App\Repositories\Common\BaseRepository;

/**
 * 商品分类管理程序
 */
class ObsConfigureController extends InitController
{
    protected $baseRepository;
    protected $obsManageService;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
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

        /* 检查权限 */
        admin_priv('obs_configure');

        $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => 'obs_configure']);
        /*------------------------------------------------------ */
        //-- OSS Bucket列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_oss_add'], 'href' => 'obs_configure.php?act=add']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['obs_configure']);
            $this->smarty->assign('form_act', 'insert');

            $bucket_list = $this->bucketList();

            $this->smarty->assign('bucket_list', $bucket_list['bucket_list']);
            $this->smarty->assign('filter', $bucket_list['filter']);
            $this->smarty->assign('record_count', $bucket_list['record_count']);
            $this->smarty->assign('page_count', $bucket_list['page_count']);
            $this->smarty->assign('full_page', 1);

            /* 列表页面 */

            return $this->smarty->display('obs_configure_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回Bucket列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $bucket_list = $this->bucketList();

            $this->smarty->assign('bucket_list', $bucket_list['bucket_list']);
            $this->smarty->assign('filter', $bucket_list['filter']);
            $this->smarty->assign('record_count', $bucket_list['record_count']);
            $this->smarty->assign('page_count', $bucket_list['page_count']);

            $sort_flag = sort_flag($bucket_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('obs_configure_list.dwt'), '', ['filter' => $bucket_list['filter'], 'page_count' => $bucket_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- OSS 添加Bucket
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_oss_list'], 'href' => 'obs_configure.php?act=list']);

            $bucket['regional'] = 'shanghai';
            $this->smarty->assign('bucket', $bucket);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['obs_configure']);
            $this->smarty->assign('form_act', 'insert');

            /* 列表页面 */

            return $this->smarty->display('obs_configure_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- OSS 编辑Bucket
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_oss_list'], 'href' => 'obs_configure.php?act=list']);

            $date = ['*'];
            $where = "id = '$id'";
            $bucket_info = get_table_date('obs_configure', $where, $date);
            $this->smarty->assign('bucket', $bucket_info);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['obs_configure']);
            $this->smarty->assign('form_act', 'update');

            /* 列表页面 */

            return $this->smarty->display('obs_configure_info.dwt');
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
            $other['port'] = empty($_POST['port']) ? '' : trim($_POST['port']);
            $other['is_use'] = empty($_POST['is_use']) ? '' : intval($_POST['is_use']);

            $date = ['bucket'];
            $where = "bucket = '" . $other['bucket'] . "'";
            $where .= !empty($id) ? " AND id <> '$id'" : '';
            $bucket_info = get_table_date('obs_configure', $where, $date);

            if ($bucket_info) {
                return sys_msg($GLOBALS['_LANG']['add_failure'], 1);
            }

            if ($other['is_use'] == 1) {
                $data = ['is_use' => 0];
                ObsConfigure::whereRaw(1)->update($data);
            }

            if (cache()->has('obs_bucket_info')) {
                cache()->forget('obs_bucket_info');
            }

            if ($id) {
                ObsConfigure::where('id', $id)->update($other);
                $href = 'obs_configure.php?act=edit&id=' . $id;

                $lang_name = $GLOBALS['_LANG']['edit_success'];
            } else {
                ObsConfigure::insert($other);
                $href = 'obs_configure.php?act=list';
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
                ObsConfigure::whereIn('id', $checkboxes)->delete();

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'obs_configure.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
            } else {

                /* 提示信息 */
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'obs_configure.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_user'], 0, $lnk);
            }
        }

        /*------------------------------------------------------ */
        //-- OSS 删除Bucket
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $bucket = ObsConfigure::where('id', $id)->value('bucket');
            $bucket = $bucket ? $bucket : '';

            ObsConfigure::where('id', $id)->delete();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'obs_configure.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['remove_success'], $bucket), 0, $link);
        }
    }

    /**
     *  返回bucket列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function bucketList()
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = ObsConfigure::count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = ObsConfigure::orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $bucket_list = $this->baseRepository->getToArrayGet($res);

        $count = count($bucket_list);

        for ($i = 0; $i < $count; $i++) {
            $bucket_list[$i]['port'] = $bucket_list[$i]['port'] ?? '';

            if ($bucket_list[$i]['port']) {
                $port = ':' . $bucket_list[$i]['port'];
            } else {
                $port = '/';
            }

            $bucket_list[$i]['endpoint'] = $this->dsc->http() . $bucket_list[$i]['bucket'] . '.obs.' . $bucket_list[$i]['regional'] . '.myhuaweicloud.com' . $port;
            $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG'][$bucket_list[$i]['regional']];
        }

        $arr = ['bucket_list' => $bucket_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
