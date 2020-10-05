<?php

namespace App\Modules\Admin\Controllers;

use App\Models\MerchantsRegionArea;
use App\Models\MerchantsRegionInfo;
use App\Repositories\Common\BaseRepository;
use App\Services\Common\AreaService;
use App\Services\Region\RegionAreaManageService;

/**
 * 会员管理程序
 */
class RegionAreaController extends InitController
{
    protected $areaService;
    protected $baseRepository;
    protected $regionAreaManageService;

    public function __construct(
        AreaService $areaService,
        BaseRepository $baseRepository,
        RegionAreaManageService $regionAreaManageService
    )
    {
        $this->areaService = $areaService;
        $this->baseRepository = $baseRepository;
        $this->regionAreaManageService = $regionAreaManageService;
    }

    public function index()
    {

        /*------------------------------------------------------ */
        //-- 区域列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('region_area');

            $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => '09_region_area_management']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['region_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_region'], 'href' => 'region_area.php?act=add']);

            $region_list = $this->regionAreaManageService->regionAreaList();

            $this->smarty->assign('region_list', $region_list['region_list']);
            $this->smarty->assign('filter', $region_list['filter']);
            $this->smarty->assign('record_count', $region_list['record_count']);
            $this->smarty->assign('page_count', $region_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');


            return $this->smarty->display('region_area_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回区域列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $region_list = $this->regionAreaManageService->regionAreaList();

            $this->smarty->assign('region_list', $region_list['region_list']);
            $this->smarty->assign('filter', $region_list['filter']);
            $this->smarty->assign('record_count', $region_list['record_count']);
            $this->smarty->assign('page_count', $region_list['page_count']);

            $sort_flag = sort_flag($region_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('region_area_list.dwt'), '', ['filter' => $region_list['filter'], 'page_count' => $region_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加区域帐号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('region_area');

            $province_list = $this->areaService->getWarehouseProvince('admin');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_region']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['region_list'], 'href' => 'region_area.php?act=list']);
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('province_list', $province_list);


            return $this->smarty->display('region_area_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加区域帐号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 检查权限 */
            admin_priv('region_area');
            $ra_name = empty($_POST['ra_name']) ? '' : trim($_POST['ra_name']);
            $ra_sort = empty($_POST['ra_sort']) ? '' : trim($_POST['ra_sort']);
            $area_list = !isset($_POST['area_list']) ? [] : $_POST['area_list'];

            $where = "ra_name = '$ra_name'";
            $date = ['ra_id'];
            $ra_id = get_table_date('merchants_region_area', $where, $date);

            if ($ra_id > 0 || empty($ra_name)) {
                $href = "region_area.php?act=add";
                $add_info = $GLOBALS['_LANG']['add_failed'];
            } else {
                $href = "region_area.php?act=list";
                $add_info = $GLOBALS['_LANG']['add_success'];

                /* 更新区域的其它信息 */
                $other = [];
                $other['ra_name'] = $ra_name;
                $other['ra_sort'] = $ra_sort;
                $other['add_time'] = gmtime();

                $ra_id = MerchantsRegionArea::insertGetId($other);

                $this->regionAreaManageService->getAreaAddBacth($ra_id, $area_list);

                /* 记录管理员操作 */
                admin_log($ra_name, 'add', 'merchants_region_area');
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => $href];
            return sys_msg(sprintf($add_info, htmlspecialchars(stripslashes($ra_name))), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑区域
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('region_area');

            $ra_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $where = "ra_id = '$ra_id'";
            $date = ['ra_id', 'ra_name', 'ra_sort'];
            $region_info = get_table_date('merchants_region_area', $where, $date);

            $province_list = $this->areaService->getWarehouseProvince('admin', $ra_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_region']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['region_list'], 'href' => 'region_area.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('region_info', $region_info);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('form_action', 'update');

            return $this->smarty->display('region_area_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新区域
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('region_area');
            $ra_name = empty($_POST['ra_name']) ? '' : trim($_POST['ra_name']);
            $ra_sort = empty($_POST['ra_sort']) ? '' : trim($_POST['ra_sort']);
            $area_list = !isset($_POST['area_list']) ? [] : $_POST['area_list'];

            $where = "ra_name = '$ra_name' and ra_id <> '" . $_POST['id'] . "'";
            $date = ['ra_id'];
            $ra_id = get_table_date('merchants_region_area', $where, $date);

            if ($ra_id > 0 || empty($ra_name)) {
                $update_info = $GLOBALS['_LANG']['update_failed'];
            } else {
                $update_info = $GLOBALS['_LANG']['update_success'];

                /* 更新会员的其它信息 */
                $other = [];
                $other['ra_name'] = $ra_name;
                $other['ra_sort'] = $ra_sort;
                $other['up_titme'] = gmtime();

                MerchantsRegionArea::where('ra_id', $_POST['id'])->update($other);

                $this->regionAreaManageService->getAreaAddBacth($_POST['id'], $area_list);

                /* 记录管理员操作 */
                admin_log($ra_name, 'edit', 'merchants_region_area');
            }

            /* 提示信息 */
            $links[0]['text'] = $GLOBALS['_LANG']['goto_list'];
            $links[0]['href'] = 'region_area.php?act=list&' . list_link_postfix();
            $links[1]['text'] = $GLOBALS['_LANG']['go_back'];
            $links[1]['href'] = "region_area.php?act=edit&id=" . $_POST['id'];

            return sys_msg($update_info, 0, $links);
        } elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('region_area');
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            MerchantsRegionArea::where('ra_id', $id)->delete();

            MerchantsRegionInfo::where('ra_id', $id)->delete();

            /* 提示信息 */
            $links[0]['text'] = $GLOBALS['_LANG']['goto_list'];
            $links[0]['href'] = 'region_area.php?act=list&' . list_link_postfix();

            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 批量删除区域帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch_remove') {
            /* 检查权限 */
            admin_priv('region_area');

            $checkboxes = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : [];

            if (count($checkboxes) > 0) {
                for ($i = 0; $i < count($checkboxes); $i++) {
                    MerchantsRegionArea::where('ra_id', $checkboxes[$i])->delete();

                    MerchantsRegionInfo::where('ra_id', $checkboxes[$i])->delete();
                }
            }

            /* 提示信息 */
            $links[0]['text'] = $GLOBALS['_LANG']['goto_list'];
            $links[0]['href'] = 'region_area.php?act=list&' . list_link_postfix();

            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $links);
        } /* 编辑区域名称 */
        elseif ($_REQUEST['act'] == 'edit_ra_name') {
            /* 检查权限 */
            $check_auth = check_authz_json('region_area');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $ra_id = intval($_POST['id']);
            $ra_name = $_POST['val'];

            $data = ['ra_name' => $ra_name];
            $res = MerchantsRegionArea::where('ra_id', $ra_id)->update($data);

            if ($res) {
                clear_cache_files();
                return make_json_result($ra_name);
            }
        } /* 编辑区域排序 */
        elseif ($_REQUEST['act'] == 'edit_ra_sort') {
            /* 检查权限 */
            $check_auth = check_authz_json('region_area');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $ra_id = intval($_POST['id']);
            $ra_sort = $_POST['val'];

            $data = ['ra_sort' => $ra_sort];
            $res = MerchantsRegionArea::where('ra_id', $ra_id)->update($data);

            if ($res) {
                clear_cache_files();
                return make_json_result($ra_sort);
            }
        }
    }
}
