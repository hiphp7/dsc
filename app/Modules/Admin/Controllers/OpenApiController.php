<?php

namespace App\Modules\Admin\Controllers;

use App\Models\OpenApi;
use App\Plugins\Dscapi\config\ApiConfig;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\OpenApi\OpenApiManageService;

/**
 * 商品分类管理程序
 */
class OpenApiController extends InitController
{
    protected $apiConfig;
    protected $baseRepository;
    protected $openApiManageService;
    protected $dscRepository;
    protected $config;

    public function __construct(
        ApiConfig $apiConfig,
        BaseRepository $baseRepository,
        OpenApiManageService $openApiManageService,
        DscRepository $dscRepository
    )
    {
        $this->apiConfig = $apiConfig->getConfig();
        $this->baseRepository = $baseRepository;
        $this->openApiManageService = $openApiManageService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
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
        admin_priv('open_api');

        $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => 'open_api']);
        /*------------------------------------------------------ */
        //-- OSS Bucket列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_openapi_add'], 'href' => 'open_api.php?act=add']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['open_api']);
            $this->smarty->assign('form_act', 'insert');

            $open_api_list = $this->openApiManageService->openApiList();

            $this->smarty->assign('open_api_list', $open_api_list['open_api_list']);
            $this->smarty->assign('filter', $open_api_list['filter']);
            $this->smarty->assign('record_count', $open_api_list['record_count']);
            $this->smarty->assign('page_count', $open_api_list['page_count']);
            $this->smarty->assign('full_page', 1);

            /* 列表页面 */

            return $this->smarty->display('openapi_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回Bucket列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $open_api_list = $this->openApiManageService->openApiList();

            $this->smarty->assign('open_api_list', $open_api_list['open_api_list']);
            $this->smarty->assign('filter', $open_api_list['filter']);
            $this->smarty->assign('record_count', $open_api_list['record_count']);
            $this->smarty->assign('page_count', $open_api_list['page_count']);

            $sort_flag = sort_flag($open_api_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('openapi_list.dwt'), '', ['filter' => $open_api_list['filter'], 'page_count' => $open_api_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- OSS 添加Bucket
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_openapi_list'], 'href' => 'open_api.php?act=list']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['open_api']);
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('api_list', $this->apiConfig[$this->config['lang']]);

            /* 列表页面 */

            return $this->smarty->display('openapi_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- OSS 编辑Bucket
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_openapi_list'], 'href' => 'open_api.php?act=list']);

            $date = ['*'];
            $where = "id = '$id'";
            $api = get_table_date('open_api', $where, $date);
            $this->smarty->assign('api', $api);

            $action_code = isset($api['action_code']) && !empty($api['action_code']) ? explode(",", $api['action_code']) : '';

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['open_api']);
            $this->smarty->assign('form_act', 'update');

            $api_data = $this->openApiManageService->getApiData($this->apiConfig[$this->config['lang']], $action_code);
            $this->smarty->assign('api_list', $api_data);

            /* 列表页面 */

            return $this->smarty->display('openapi_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- OSS 添加Bucket
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $other['name'] = empty($_POST['name']) ? '' : trim($_POST['name']);
            $other['app_key'] = empty($_POST['app_key']) ? '' : trim($_POST['app_key']);
            $other['is_open'] = empty($_POST['is_open']) ? 0 : intval($_POST['is_open']);
            $other['action_code'] = empty($_POST['action_code']) ? '' : implode(",", $_POST['action_code']);

            if ($id) {
                OpenApi::where('id', $id)->update($other);
                $href = 'open_api.php?act=edit&id=' . $id;

                $lang_name = $GLOBALS['_LANG']['edit_success'];
            } else {
                $other['add_time'] = gmtime();
                OpenApi::insert($other);
                $href = 'open_api.php?act=list';
                $lang_name = $GLOBALS['_LANG']['add_success'];
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => $href];
            return sys_msg(sprintf($lang_name, htmlspecialchars(stripslashes($other['name']))), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- OSS 批量删除Bucket
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove') {
            if (isset($_REQUEST['checkboxes'])) {
                $checkboxes = $this->baseRepository->getExplode($_REQUEST['checkboxes']);
                OpenApi::whereIn('id', $checkboxes)->delete();

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'open_api.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
            } else {

                /* 提示信息 */
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'open_api.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_user'], 0, $lnk);
            }
        }

        /*------------------------------------------------------ */
        //-- OSS 删除Bucket
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $name = OpenApi::where('id', $id)->value('name');
            $name = $name ? $name : '';

            OpenApi::where('id', $id)->delete();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'open_api.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['remove_success'], $name), 0, $link);
        } elseif ($_REQUEST['act'] == 'app_key') {
            $check_auth = check_authz_json('open_api');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $name = empty($_REQUEST['name']) ? '' : trim($_REQUEST['name']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $guid = sc_guid();
            $result['app_key'] = $guid;

            return response()->json($result);
        }
    }
}
