<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Models\AdPosition;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Ads\AdsManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 广告位置管理程序
 */
class AdPositionController extends InitController
{
    protected $commonRepository;
    protected $adsManageService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $storeCommonService;
    protected $baseRepository;

    public function __construct(
        CommonRepository $commonRepository,
        BaseRepository $baseRepository,
        AdsManageService $adsManageService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->adsManageService = $adsManageService;
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        $this->dscRepository->helpersLang('ads', 'seller');

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "ads");
        /* act操作项的初始化 */

        $act = addslashes(request()->input('act', 'list'));
        $act = $act ? $act : 'list';

        $this->smarty->assign('lang', $GLOBALS['_LANG']);
        $exc = new Exchange($this->dsc->table("ad_position"), $this->db, 'position_id', 'position_name');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('menu_select', ['action' => '05_banner', 'current' => 'ad_position']);

        /*------------------------------------------------------ */
        //-- 广告位置列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['05_banner']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ad_position']);
            if ($adminru['ru_id'] == 0) {//限制商家不可操作添加广告位 liu
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['position_add'], 'href' => 'ad_position.php?act=add']);
            }
            $this->smarty->assign('full_page', 1);

            $position_list = $this->adsManageService->adPositionList();

            $this->smarty->assign('position_list', $position_list['position']);
            $this->smarty->assign('filter', $position_list['filter']);
            $this->smarty->assign('record_count', $position_list['record_count']);
            $this->smarty->assign('page_count', $position_list['page_count']);
            $this->smarty->assign('current', 'ad_position');
            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);
            // 分页
            $page = (int)request()->input('page', 1);
            $page_count_arr = seller_page($position_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return $this->smarty->display('ad_position_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            $page = (int)request()->input('page', 1);

            $position_list = $this->adsManageService->adPositionList();

            $this->smarty->assign('position_list', $position_list['position']);
            $this->smarty->assign('filter', $position_list['filter']);
            $this->smarty->assign('record_count', $position_list['record_count']);
            $this->smarty->assign('page_count', $position_list['page_count']);
            //分页
            $page_count_arr = seller_page($position_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);
            $this->smarty->assign('current', 'ad_position');
            return make_json_result(
                $this->smarty->fetch('ad_position_list.dwt'),
                '',
                ['filter' => $position_list['filter'], 'page_count' => $position_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加广告位页面
        /*------------------------------------------------------ */
        elseif ($act == 'add') {
            admin_priv('ad_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['05_banner']);
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['position_add']);
            $this->smarty->assign('form_act', 'insert');

            $this->smarty->assign('action_link', ['href' => 'ad_position.php?act=list', 'text' => $GLOBALS['_LANG']['ad_position']]);
            $this->smarty->assign('posit_arr', ['position_style' => '<table cellpadding="0" cellspacing="0">' . "\n" . '{foreach from=$ads item=ad}' . "\n" . '<tr><td>{$ad}</td></tr>' . "\n" . '{/foreach}' . "\n" . '</table>']);


            return $this->smarty->display('ad_position_info.htm');
        } elseif ($act == 'insert') {
            admin_priv('ad_manage');

            /* 对POST上来的值进行处理并去除空格 */

            $position_name = addslashes(trim(request()->input('position_name', '')));
            $position_model = addslashes(trim(request()->input('position_model', '')));
            $position_desc = nl2br(htmlspecialchars(request()->input('position_desc', '')));

            $ad_width = (int)request()->input('ad_width', 0);
            $ad_height = (int)request()->input('ad_height', 0);
            //ecmoban模板堂 --zhuo
            $is_public = (int)request()->input('is_public', 0);

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
                    'position_style' => addslashes(request()->input('position_style', '')),
                    'user_id' => $adminru['ru_id'],
                    'is_public' => $is_public,
                    'theme' => $GLOBALS['_CFG']['template']
                ];
                $insert_id = AdPosition::insertGetId($other);

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
        elseif ($act == 'edit') {
            admin_priv('ad_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['05_banner']);
            $id = (int)request()->input('id', 0);

            /* 获取广告位数据 */
            $posit_arr = AdPosition::where('position_id', $id);
            $posit_arr = $this->baseRepository->getToArrayFirst($posit_arr);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['position_edit']);
            $this->smarty->assign('action_link', ['href' => 'ad_position.php?act=list', 'text' => $GLOBALS['_LANG']['ad_position']]);
            $this->smarty->assign('posit_arr', $posit_arr);
            $this->smarty->assign('form_act', 'update');

            return $this->smarty->display('ad_position_info.htm');
        } elseif ($act == 'update') {
            admin_priv('ad_manage');

            /* 对POST上来的值进行处理并去除空格 */
            $position_name = addslashes(trim(request()->input('position_name', '')));
            $position_model = addslashes(trim(request()->input('position_model', '')));
            $position_desc = nl2br(htmlspecialchars(request()->input('position_desc', '')));

            $ad_width = (int)request()->input('ad_width', 0);
            $ad_height = (int)request()->input('ad_height', 0);
            $position_id = (int)request()->input('id', 0);
            //ecmoban模板堂 --zhuo
            $is_public = (int)request()->input('is_public', 0);


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
                    'position_style' => addslashes(request()->input('position_style', ''))
                ];
                $res = AdPosition::where('position_id', $position_id)->update($thoer);
                if ($res) {
                    /* 记录管理员操作 */
                    admin_log($position_name, 'edit', 'ads_position');

                    /* 清除缓存 */
                    clear_cache_files();

                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['back_position_list'], 'href' => 'ad_position.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . stripslashes($position_name) . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
                }
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['posit_name_exist'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑广告位置名称
        /*------------------------------------------------------ */
        elseif ($act == 'edit_position_name') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);

            $position_name = json_str_iconv(trim(request()->input('val', '')));
            $template = $GLOBALS['_CFG']['template'];

            /* 检查名称是否重复 */
            if ($exc->num("position_name", $position_name, $id, "theme='$template'") != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['posit_name_exist'], $position_name));
            } else {
                if ($exc->edit("position_name = '$position_name', theme='$template'", $id)) {
                    admin_log($position_name, 'edit', 'ads_position');
                    return make_json_result(stripslashes($position_name));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['brandedit_fail'], $position_name));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑广告位宽高
        /*------------------------------------------------------ */
        elseif ($act == 'edit_ad_width') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $ad_width = json_str_iconv(trim(request()->input('val', '')));
            /* 宽度值必须是数字 */
            if (!preg_match("/^[\.0-9]+$/", $ad_width)) {
                return make_json_error($GLOBALS['_LANG']['width_number']);
            }

            /* 广告位宽度应在1-1024之间 */
            if ($ad_width > 1024000 || $ad_width < 1) {
                return make_json_error($GLOBALS['_LANG']['width_value']);
            }

            if ($exc->edit("ad_width = '$ad_width'", $id)) {
                clear_cache_files(); // 清除模版缓存
                admin_log($ad_width, 'edit', 'ads_position');
                return make_json_result(stripslashes($ad_width));
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑广告位宽高
        /*------------------------------------------------------ */
        elseif ($act == 'edit_ad_height') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $ad_height = json_str_iconv(trim(request()->input('val', '')));

            /* 高度值必须是数字 */
            if (!preg_match("/^[\.0-9]+$/", $ad_height)) {
                return make_json_error($GLOBALS['_LANG']['height_number']);
            }

            /* 广告位宽度应在1-1024之间 */
            if ($ad_height > 1024000 || $ad_height < 1) {
                return make_json_error($GLOBALS['_LANG']['height_value']);
            }

            if ($exc->edit("ad_height = '$ad_height'", $id)) {
                clear_cache_files(); // 清除模版缓存
                admin_log($ad_height, 'edit', 'ads_position');
                return make_json_result(stripslashes($ad_height));
            } else {
                return make_json_error($this->db->error());
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

            $id = (int)request()->input('id', 0);

            /* 查询广告位下是否有广告存在 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('ad') . " WHERE position_id = '$id'";

            if ($this->db->getOne($sql) > 0) {
                return make_json_error($GLOBALS['_LANG']['not_del_adposit']);
            } else {
                $exc->drop($id);
                admin_log('', 'remove', 'ads_position');
            }

            $url = 'ad_position.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }


}
