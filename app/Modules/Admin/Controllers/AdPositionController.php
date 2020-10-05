<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Ad;
use App\Models\AdPosition;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Ads\AdsManageService;
use App\Services\Store\StoreCommonService;

/**
 * 广告位置管理程序
 */
class AdPositionController extends InitController
{
    protected $adsManageService;
    protected $commonRepository;
    protected $dscRepository;
    protected $storeCommonService;

    public function __construct(
        AdsManageService $adsManageService,
        CommonRepository $commonRepository,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->adsManageService = $adsManageService;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {

        $this->dscRepository->helpersLang('ads', 'admin');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /*------------------------------------------------------ */
        //-- 广告位置列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            if ($adminru['ru_id'] == 0) {//限制商家不可操作添加广告位 liu
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ad_position']);
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['position_add'], 'href' => 'ad_position.php?act=add']);
            }
            $this->smarty->assign('full_page', 1);

            $position_list = $this->adsManageService->adPositionList();

            $this->smarty->assign('position_list', $position_list['position']);
            $this->smarty->assign('filter', $position_list['filter']);
            $this->smarty->assign('record_count', $position_list['record_count']);
            $this->smarty->assign('page_count', $position_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);


            return $this->smarty->display('ad_position_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $position_list = $this->adsManageService->adPositionList();
            $this->smarty->assign('position_list', $position_list['position']);
            $this->smarty->assign('filter', $position_list['filter']);
            $this->smarty->assign('record_count', $position_list['record_count']);
            $this->smarty->assign('page_count', $position_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return make_json_result(
                $this->smarty->fetch('ad_position_list.dwt'),
                '',
                ['filter' => $position_list['filter'], 'page_count' => $position_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加广告位页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('ad_manage');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['position_add']);
            $this->smarty->assign('form_act', 'insert');

            $this->smarty->assign('action_link', ['href' => 'ad_position.php?act=list', 'text' => $GLOBALS['_LANG']['ad_position']]);
            $this->smarty->assign('posit_arr', ['position_style' => '<table cellpadding="0" cellspacing="0">' . "\n" . '{foreach from=$ads item=ad}' . "\n" . '<tr><td>{$ad}</td></tr>' . "\n" . '{/foreach}' . "\n" . '</table>']);


            return $this->smarty->display('ad_position_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入广告位置信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('ad_manage');

            /* 对POST上来的值进行处理并去除空格 */
            $position_name = !empty($_POST['position_name']) ? trim($_POST['position_name']) : '';
            $position_model = !empty($_POST['position_model']) ? trim($_POST['position_model']) : '';
            $position_desc = !empty($_POST['position_desc']) ? nl2br(htmlspecialchars($_POST['position_desc'])) : '';
            $ad_width = !empty($_POST['ad_width']) ? intval($_POST['ad_width']) : 0;
            $ad_height = !empty($_POST['ad_height']) ? intval($_POST['ad_height']) : 0;
            $is_public = isset($_POST['is_public']) ? intval($_POST['is_public']) : 0;  //ecmoban模板堂 --zhuo

            $count = AdPosition::where('position_name', $position_name)
                ->where('theme', $GLOBALS['_CFG']['template'])
                ->count();

            /* 查看广告位是否有重复 */
            if ($count == 0) {

                /* 将广告位置的信息插入数据表 */
                $other = [
                    'position_name' => $position_name,
                    'ad_width' => $ad_width,
                    'ad_height' => $ad_height,
                    'position_model' => $position_model,
                    'position_desc' => $position_desc,
                    'position_style' => addslashes($_POST['position_style']) ?? '',
                    'user_id' => $adminru['ru_id'],
                    'is_public' => $is_public,
                    'theme' => $GLOBALS['_CFG']['template']
                ];
                $insert_id = AdPosition::insertGetId($other);

                /* 获取pid by wu */
                $pid = empty($insert_id) ? 0 : $insert_id;

                /* 记录管理员操作 */
                admin_log($position_name, 'add', 'ads_position');

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['ads_add'];
                $link[0]['href'] = 'ads.php?act=add' . '&pid=' . $pid;

                $link[1]['text'] = $GLOBALS['_LANG']['continue_add_position'];
                $link[1]['href'] = 'ad_position.php?act=add';

                $link[2]['text'] = $GLOBALS['_LANG']['back_position_list'];
                $link[2]['href'] = 'ad_position.php?act=list';

                return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . stripslashes($position_name) . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['posit_name_exist'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 广告位编辑页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('ad_manage');

            $id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

            /* 获取广告位数据 */
            $posit_arr = $this->adsManageService->getPositionInfo($id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['position_edit']);
            $this->smarty->assign('action_link', ['href' => 'ad_position.php?act=list', 'text' => $GLOBALS['_LANG']['ad_position']]);
            $this->smarty->assign('posit_arr', $posit_arr);
            $this->smarty->assign('form_act', 'update');


            return $this->smarty->display('ad_position_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新广告位置信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            admin_priv('ad_manage');

            /* 对POST上来的值进行处理并去除空格 */
            $position_name = !empty($_POST['position_name']) ? trim($_POST['position_name']) : '';
            $position_model = !empty($_POST['position_model']) ? trim($_POST['position_model']) : '';
            $position_desc = !empty($_POST['position_desc']) ? nl2br(htmlspecialchars($_POST['position_desc'])) : '';
            $ad_width = !empty($_POST['ad_width']) ? intval($_POST['ad_width']) : 0;
            $ad_height = !empty($_POST['ad_height']) ? intval($_POST['ad_height']) : 0;
            $position_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $is_public = isset($_POST['is_public']) ? intval($_POST['is_public']) : 0;

            /* 查看广告位是否与其它有重复 */
            $object = AdPosition::whereRaw(1);

            $where = [
                'position_name' => $position_name,
                'id' => [
                    'filed' => [
                        'position_id' => $position_id
                    ],
                    'condition' => '<>'
                ],
                'theme' => $GLOBALS['_CFG']['template']
            ];
            $is_only = $this->commonRepository->getManageIsOnly($object, $where);

            if (!$is_only) {
                $thoer = [
                    'position_name' => $position_name,
                    'ad_width' => $ad_width,
                    'ad_height' => $ad_height,
                    'position_model' => $position_model,
                    'position_desc' => $position_desc,
                    'is_public' => $is_public,
                    'position_style' => addslashes($_POST['position_style']) ?? ''
                ];
                $res = AdPosition::where('position_id', $position_id)->update($thoer);

                if ($res) {
                    /* 记录管理员操作 */
                    admin_log($position_name, 'edit', 'ads_position');
                }

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_position_list'], 'href' => 'ad_position.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . stripslashes($position_name) . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['posit_name_exist'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除广告位置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 查询广告位下是否有广告存在 */
            $count = Ad::where('position_id', $id)->count();

            if ($count > 0) {
                return make_json_error($GLOBALS['_LANG']['not_del_adposit']);
            } else {
                AdPosition::where('position_id', $id)->delete();
                admin_log('', 'remove', 'ads_position');
            }

            $url = 'ad_position.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
    }
}
