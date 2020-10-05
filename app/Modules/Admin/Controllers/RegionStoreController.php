<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminUser;
use App\Models\RegionStore;
use App\Models\RsRegion;
use App\Repositories\Common\BaseRepository;
use App\Services\Region\RegionStoreManageService;

/**
 * 卖场
 */
class RegionStoreController extends InitController
{

    protected $baseRepository;
    protected $regionStoreManageService;

    public function __construct(
        BaseRepository $baseRepository,
        RegionStoreManageService $regionStoreManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->regionStoreManageService = $regionStoreManageService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        //admin_priv('');

        //默认
        if (empty($_REQUEST['act'])) {
            return 'Error';
        }

        /*------------------------------------------------------ */
        //-- 列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('region_store_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_region_store_manage']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['region_store_add'], 'href' => 'region_store.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $region_store = $this->regionStoreManageService->regionStoreList($adminru['ru_id']);

            $this->smarty->assign('list', $region_store['list']);
            $this->smarty->assign('filter', $region_store['filter']);
            $this->smarty->assign('record_count', $region_store['record_count']);
            $this->smarty->assign('page_count', $region_store['page_count']);

            $sort_flag = sort_flag($region_store['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('region_store_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('region_store_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $region_store = $this->regionStoreManageService->regionStoreList($adminru['ru_id']);

            $this->smarty->assign('list', $region_store['list']);
            $this->smarty->assign('filter', $region_store['filter']);
            $this->smarty->assign('record_count', $region_store['record_count']);
            $this->smarty->assign('page_count', $region_store['page_count']);

            $sort_flag = sort_flag($region_store['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('region_store_list.dwt'),
                '',
                ['filter' => $region_store['filter'], 'page_count' => $region_store['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('region_store_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            RegionStore::where('rs_id', $id)->delete();
            //删除关联地区和管理员
            RsRegion::where('rs_id', $id)->delete();

            $data = ['rs_id' => 0];
            AdminUser::where('rs_id', $id)->update($data);

            //clear_cache_files();
            $url = 'region_store.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_rs_name') {
            $check_auth = check_authz_json('region_store_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = trim($_POST['val']);

            $data = ['rs_name' => $val];
            RegionStore::where('rs_id', $id)->update($data);

            //clear_cache_files();
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('region_store_manage');

            $rs_id = !empty($_REQUEST['rs_id']) ? intval($_REQUEST['rs_id']) : 0;
            if ($rs_id > 0) {
                $region_store = $this->regionStoreManageService->getRegionStoreInfo($rs_id);
                if ($region_store['region_id']) {
                    $region_level = get_region_level($region_store['region_id']);
                    $region_store['region_level'] = $region_level;
                }
                $this->smarty->assign('region_store', $region_store);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit']);
                $this->smarty->assign('form_action', 'update');
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add']);
                $this->smarty->assign('form_action', 'insert');
            }
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_region_store_manage'], 'href' => 'region_store.php?act=list']);

            //区域
            $this->smarty->assign('country_all', get_regions());
            $this->smarty->assign('province_all', get_regions(1, 1));

            //管理员
            $this->smarty->assign('region_admin', $this->regionStoreManageService->getRegionAdmin());


            return $this->smarty->display('region_store_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            admin_priv('region_store_manage');

            $store_data = [];
            $region_data = [];
            $admin_data = [];
            $rs_id = !empty($_REQUEST['rs_id']) ? intval($_REQUEST['rs_id']) : 0;
            $store_data['rs_name'] = !empty($_REQUEST['rs_name']) ? trim($_REQUEST['rs_name']) : '';
            $region_data['region_id'] = !empty($_REQUEST['city']) ? intval($_REQUEST['city']) : 0;
            $admin_data['user_id'] = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $region_data['rs_id'] = $rs_id;
            $admin_data['rs_id'] = $rs_id;

            /*检查卖场是否重复*/
            $is_only = RegionStore::where('rs_name', $store_data['rs_name'])
                ->where('rs_id', '<>', $rs_id)
                ->value('rs_id');
            $is_only = $is_only ? $is_only : 0;

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['region_store_exist'], 1);
            }
            /*插入、更新*/
            if ($rs_id > 0) {
                RegionStore::where('rs_id', $rs_id)->update($store_data);

                $msg = $GLOBALS['_LANG']['edit_success'];
            } else {
                $rs_id = RegionStore::insertGetId($store_data);
                $msg = $GLOBALS['_LANG']['add_success'];

                $region_data['rs_id'] = $rs_id;
                $admin_data['rs_id'] = $rs_id;
            }

            /*检查城市是否重复*/
            $is_only = RsRegion::where('region_id', $region_data['region_id'])
                ->where('rs_id', '<>', $rs_id)
                ->value('id');
            $is_only = $is_only ? $is_only : 0;

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['rs_region_exist'], 1);
            }
            /*检查地区是否设置*/
            $is_exist = RsRegion::where('rs_id', $rs_id)->value('id');
            $is_exist = $is_exist ? $is_exist : 0;
            /*插入、更新*/
            if ($is_exist) {
                RsRegion::where('rs_id', $rs_id)->update($region_data);
            } else {
                RsRegion::insert($region_data);
            }

            /*检查管理员是否重复*/
            $is_only = AdminUser::where('user_id', $admin_data['user_id'])
                ->where('rs_id', '<>', $rs_id)
                ->value('rs_id');
            $is_only = $is_only ? $is_only : 0;

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['rs_admin_exist'], 1);
            }
            //解除绑定
            $data = ['rs_id' => 0];
            AdminUser::where('rs_id', $rs_id)->update($data);

            //绑定新值
            AdminUser::where('user_id', $admin_data['user_id'])->update($admin_data);

            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'region_store.php?act=list'];
            return sys_msg($msg, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 刷新管理员
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'admin_update') {
            $check_auth = check_authz_json('region_store_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $this->smarty->assign('region_admin', $this->regionStoreManageService->getRegionAdmin());
            $content = $this->smarty->fetch('library/region_admin.lbi');

            //clear_cache_files();
            return make_json_result($content);
        }
    }
}
