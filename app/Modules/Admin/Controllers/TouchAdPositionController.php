<?php

namespace App\Modules\Admin\Controllers;

use App\Models\TouchAd;
use App\Models\TouchAdPosition;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Ads\TouchAdsManageService;
use App\Services\Common\CommonManageService;
use App\Services\Store\StoreCommonService;

/**
 * 广告位置管理程序
 */
class TouchAdPositionController extends InitController
{
    protected $dscRepository;
    protected $commonRepository;
    protected $storeCommonService;
    protected $commonManageService;
    protected $touchAdsManageService;

    public function __construct(
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        StoreCommonService $storeCommonService,
        CommonManageService $commonManageService,
        TouchAdsManageService $touchAdsManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->storeCommonService = $storeCommonService;
        $this->commonManageService = $commonManageService;
        $this->touchAdsManageService = $touchAdsManageService;
    }

    public function index()
    {
        $this->dscRepository->helpersLang('ads', 'admin');

        /* act操作项的初始化 */
        $act = request()->input('act', 'list');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $adminru = $this->commonManageService->getAdminIdSeller();
        $priv_ru = isset($adminru['ru_id']) && $adminru['ru_id'] == 0 ? 1 : 0;
        $this->smarty->assign('priv_ru', $priv_ru);

        $ad_type = addslashes(request()->input('ad_type', '')); // 广告位所属模块 如 wxapp、team、drp、seckill
        $this->smarty->assign('ad_type', $ad_type);

        /*------------------------------------------------------ */
        //-- 广告位置列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_touch_ad_position']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['position_add'], 'href' => 'touch_ad_position.php?act=add&ad_type=' . $ad_type]);
            $this->smarty->assign('full_page', 1);

            $position_list = $this->touchAdsManageService->touchAdPositionList($adminru['ru_id'], $ad_type);

            $this->smarty->assign('position_list', $position_list['position']);
            $this->smarty->assign('filter', $position_list['filter']);
            $this->smarty->assign('record_count', $position_list['record_count']);
            $this->smarty->assign('page_count', $position_list['page_count']);
            $this->smarty->assign('type', '1');

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return $this->smarty->display('touch_ad_position_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            $position_list = $this->touchAdsManageService->touchAdPositionList($adminru['ru_id'], $ad_type);

            $this->smarty->assign('position_list', $position_list['position']);
            $this->smarty->assign('filter', $position_list['filter']);
            $this->smarty->assign('record_count', $position_list['record_count']);
            $this->smarty->assign('page_count', $position_list['page_count']);
            $this->smarty->assign('type', '1');

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return make_json_result(
                $this->smarty->fetch('ad_position_list.dwt'), '', ['filter' => $position_list['filter'], 'page_count' => $position_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加广告位页面
        /*------------------------------------------------------ */
        elseif ($act == 'add') {
            admin_priv('touch_ad_position');

            /* 获取顶级频道 */
            if (file_exists(MOBILE_TEAM)) {
                $team_list = $this->touchAdsManageService->getTeamCategoryList();
                $this->smarty->assign('team_list', $team_list);
                $this->smarty->assign('is_team', 1);
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['position_add']);
            $this->smarty->assign('form_act', 'insert');

            $this->smarty->assign('action_link', ['href' => 'touch_ad_position.php?act=list&ad_type=' . $ad_type, 'text' => $GLOBALS['_LANG']['ad_position']]);
            $this->smarty->assign('posit_arr', ['position_style' => '{foreach $ads as $ad}' . "\n" . '<div class="swiper-slide">{$ad}</div>' . "\n" . '{/foreach}']);
            $this->smarty->assign('type', '1');


            return $this->smarty->display('touch_ad_position_info.dwt');
        } elseif ($act == 'insert') {
            admin_priv('touch_ad_position');

            /* 对POST上来的值进行处理并去除空格 */
            $position_name = !empty($_POST['position_name']) ? trim($_POST['position_name']) : '';
            $position_desc = !empty($_POST['position_desc']) ? nl2br(htmlspecialchars($_POST['position_desc'])) : '';
            $ad_width = !empty($_POST['ad_width']) ? intval($_POST['ad_width']) : 0;
            $ad_height = !empty($_POST['ad_height']) ? intval($_POST['ad_height']) : 0;
            $is_public = isset($_POST['is_public']) ? intval($_POST['is_public']) : 0;  //ecmoban模板堂 --zhuo

            /* 拼团 sty */
            if (file_exists(MOBILE_TEAM)) {
                $tc_id = !empty($_POST['tc_id']) ? intval($_POST['tc_id']) : 0; //频道id
                $tc_type = !empty($_POST['tc_type']) ? trim($_POST['tc_type']) : ''; //广告类型
            }

            $template = $GLOBALS['_CFG']['template'];

            $count = TouchAdPosition::where('position_name', $position_name)
                ->where('theme', $template)
                ->count();

            /* 查看广告位是否有重复 */
            if ($count == 0) {

                /* 将广告位置的信息插入数据表 */
                $other = [
                    'position_name' => $position_name,
                    'ad_width' => $ad_width,
                    'ad_height' => $ad_height,
                    'position_desc' => $position_desc,
                    'position_style' => addslashes($_POST['position_style']) ?? '',
                    'user_id' => $adminru['ru_id'],
                    'is_public' => $is_public,
                    'theme' => $template,
                    'ad_type' => $ad_type
                ];

                if (file_exists(MOBILE_TEAM)) {
                    $other['tc_id'] = $tc_id ?? 0;
                    $other['tc_type'] = $tc_type ?? '';
                }

                $insert_id = TouchAdPosition::insertGetId($other);

                /* 获取pid by wu */
                $pid = empty($insert_id) ? 0 : $insert_id;

                /* 记录管理员操作 */
                admin_log($position_name, 'add', 'ads_position');

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['ads_add'];
                $link[0]['href'] = 'touch_ads.php?act=add&ad_type=' . $ad_type . '&pid=' . $pid;

                $link[1]['text'] = $GLOBALS['_LANG']['continue_add_position'];
                $link[1]['href'] = 'touch_ad_position.php?act=add&ad_type=' . $ad_type;

                $link[2]['text'] = $GLOBALS['_LANG']['back_position_list'];
                $link[2]['href'] = 'touch_ad_position.php?act=list&ad_type=' . $ad_type;

                return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . stripslashes($position_name) . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['posit_name_exist'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 广告位编辑页面
        /*------------------------------------------------------ */
        elseif ($act == 'edit') {
            admin_priv('touch_ad_position');

            /* 获取顶级频道 */
            if (file_exists(MOBILE_TEAM)) {
                $team_list = $this->touchAdsManageService->getTeamCategoryList();
                $this->smarty->assign('team_list', $team_list);
                $this->smarty->assign('is_team', 1);
            }

            $id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

            /* 获取广告位数据 */
            $posit_arr = $this->touchAdsManageService->getPositionInfo($id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['position_edit']);
            $this->smarty->assign('action_link', ['href' => 'touch_ad_position.php?act=list&ad_type=' . $ad_type, 'text' => $GLOBALS['_LANG']['ad_position']]);
            $this->smarty->assign('posit_arr', $posit_arr);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('type', '1');


            return $this->smarty->display('touch_ad_position_info.dwt');
        } elseif ($act == 'update') {
            admin_priv('touch_ad_position');

            /* 对POST上来的值进行处理并去除空格 */
            $position_name = !empty($_POST['position_name']) ? trim($_POST['position_name']) : '';
            $position_desc = !empty($_POST['position_desc']) ? nl2br(htmlspecialchars($_POST['position_desc'])) : '';
            $ad_width = !empty($_POST['ad_width']) ? intval($_POST['ad_width']) : 0;
            $ad_height = !empty($_POST['ad_height']) ? intval($_POST['ad_height']) : 0;
            $position_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $is_public = isset($_POST['is_public']) ? intval($_POST['is_public']) : 0; //ecmoban模板堂 --zhuo

            /* 拼团 */
            if (file_exists(MOBILE_TEAM)) {
                $tc_id = !empty($_POST['tc_id']) ? intval($_POST['tc_id']) : 0; //频道id
                $tc_type = !empty($_POST['tc_type']) ? trim($_POST['tc_type']) : ''; //广告类型
            }

            /* 查看广告位是否与其它有重复 */
            $object = TouchAdPosition::query();

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
                $other = [
                    'position_name' => $position_name,
                    'ad_width' => $ad_width,
                    'ad_height' => $ad_height,
                    'position_desc' => $position_desc,
                    'is_public' => $is_public,
                    'position_style' => addslashes($_POST['position_style']) ?? '',
                    'ad_type' => $ad_type
                ];

                if (file_exists(MOBILE_TEAM)) {
                    $other['tc_id'] = $tc_id ?? 0;
                    $other['tc_type'] = $tc_type ?? '';
                }

                $res = TouchAdPosition::where('position_id', $position_id)->update($other);

                if ($res) {
                    /* 记录管理员操作 */
                    admin_log($position_name, 'edit', 'ads_position');
                }

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_position_list'], 'href' => 'touch_ad_position.php?act=list&ad_type=' . $ad_type];
                return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . stripslashes($position_name) . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['posit_name_exist'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除广告位置
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 查询广告位下是否有广告存在 */
            $count = TouchAd::where('position_id', $id)->count();

            if ($count > 0) {
                return make_json_error($GLOBALS['_LANG']['not_del_adposit']);
            } else {
                TouchAdPosition::where('position_id', $id)->delete();
                admin_log('', 'remove', 'ads_position');
            }

            $url = 'touch_ad_position.php?act=query&ad_type=' . $ad_type . '&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

}
