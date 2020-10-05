<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\AdminUser;
use App\Models\Agency;
use App\Models\Region;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Other\AgencyManageService;

/**
 * 管理中心办事处管理
 */
class AgencyController extends InitController
{
    protected $agencyManageService;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        AgencyManageService $agencyManageService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->agencyManageService = $agencyManageService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $exc = new Exchange($this->dsc->table('agency'), $this->db, 'agency_id', 'agency_name');

        /*------------------------------------------------------ */
        //-- 办事处列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['agency_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_agency'], 'href' => 'agency.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $agency_list = $this->agencyManageService->getAgencyList();
            $this->smarty->assign('agency_list', $agency_list['agency']);
            $this->smarty->assign('filter', $agency_list['filter']);
            $this->smarty->assign('record_count', $agency_list['record_count']);
            $this->smarty->assign('page_count', $agency_list['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($agency_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('agency_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $agency_list = $this->agencyManageService->getAgencyList();
            $this->smarty->assign('agency_list', $agency_list['agency']);
            $this->smarty->assign('filter', $agency_list['filter']);
            $this->smarty->assign('record_count', $agency_list['record_count']);
            $this->smarty->assign('page_count', $agency_list['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($agency_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('agency_list.dwt'),
                '',
                ['filter' => $agency_list['filter'], 'page_count' => $agency_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除办事处
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('agency_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            $name = $exc->get_name($id);
            $exc->drop($id);

            /* 更新管理员、配送地区、发货单、退货单和订单关联的办事处 */
            $other = [
                'agency_id' => 0
            ];
            $this->agencyManageService->agencyUpdate($id, $other);

            /* 记日志 */
            admin_log($name, 'remove', 'agency');

            /* 清除缓存 */
            clear_cache_files();

            $url = 'agency.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                /* 检查权限 */
                admin_priv('agency_manage');

                $ids = $this->baseRepository->getExplode($_POST['checkboxes']);

                if (isset($_POST['remove'])) {
                    Agency::whereIn('agency_id', $ids)->delete();

                    /* 更新管理员、配送地区、发货单、退货单和订单关联的办事处 */
                    $other = [
                        'agency_id' => 0
                    ];
                    $this->agencyManageService->agencyUpdate($ids, $other);

                    /* 记日志 */
                    admin_log('', 'batch_remove', 'agency');
                }

                /* 清除缓存 */
                clear_cache_files();
                $link[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'agency.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], '', $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑办事处
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('agency_manage');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'add';
            $this->smarty->assign('form_action', $is_add ? 'insert' : 'update');

            /* 初始化、取得办事处信息 */
            if ($is_add) {
                $agency = [
                    'agency_id' => 0,
                    'agency_name' => '',
                    'agency_desc' => '',
                    'region_list' => []
                ];
            } else {
                if (empty($_GET['id'])) {
                    return sys_msg('invalid param');
                }

                $id = intval($_GET['id']);
                $agency = Agency::where('agency_id', $id);
                $agency = $this->baseRepository->getToArrayFirst($agency);

                if (empty($agency)) {
                    return sys_msg('agency does not exist');
                }

                /* 关联的地区 */
                $region_list = Region::where('agency_id', $id);
                $region_list = $this->baseRepository->getToArrayGet($region_list);

                $agency['region_list'] = $region_list;
            }

            /* 取得所有管理员，标注哪些是该办事处的('this')，哪些是空闲的('free')，哪些是别的办事处的('other') */
            $admin_list = $this->agencyManageService->getAdminList($agency['agency_id']);
            $agency['admin_list'] = $admin_list;

            $this->smarty->assign('agency', $agency);

            /* 取得地区 */
            $Province_list = get_regions(1, 1);
            $this->smarty->assign('Province_list', $Province_list);

            /* 显示模板 */
            if ($is_add) {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_agency']);
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_agency']);
            }
            if ($is_add) {
                $href = 'agency.php?act=list';
            } else {
                $href = 'agency.php?act=list&' . list_link_postfix();
            }
            $this->smarty->assign('action_link', ['href' => $href, 'text' => $GLOBALS['_LANG']['agency_list']]);

            return $this->smarty->display('agency_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 提交添加、编辑办事处
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('agency_manage');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'insert';

            /* 提交值 */
            $agency = [
                'agency_id' => intval($_POST['id']),
                'agency_name' => $this->dscRepository->subStr($_POST['agency_name'], 255, false),
                'agency_desc' => $_POST['agency_desc']
            ];

            /* 判断名称是否重复 */
            if (!$exc->is_only('agency_name', $agency['agency_name'], $agency['agency_id'])) {
                return sys_msg($GLOBALS['_LANG']['agency_name_exist']);
            }

            /* 检查是否选择了地区 */
            if (empty($_POST['regions'])) {
                return sys_msg($GLOBALS['_LANG']['no_regions']);
            }

            /* 保存办事处信息 */
            if ($is_add) {
                $agency_id = Agency::insertGetId($agency);
                $agency['agency_id'] = $agency_id;
            } else {
                Agency::where('agency_id', $agency['agency_id'])
                    ->update($agency);
            }

            /* 更新管理员表和地区表 */
            if (!$is_add) {
                AdminUser::where('agency_id', $agency['agency_id'])
                    ->update([
                        'agency_id' => 0
                    ]);

                Region::where('agency_id', $agency['agency_id'])
                    ->update([
                        'agency_id' => 0
                    ]);
            }

            if (isset($_POST['admins']) && $_POST['admins']) {
                $admins = $this->baseRepository->getExplode($_POST['admins']);
                AdminUser::whereIn('user_id', $admins)
                    ->update([
                        'agency_id' => $agency['agency_id']
                    ]);
            }

            if (isset($_POST['regions']) && $_POST['regions']) {
                $regions = $this->baseRepository->getExplode($_POST['regions']);
                Region::where('region_id', $regions)
                    ->update([
                        'agency_id' => $agency['agency_id']
                    ]);
            }

            /* 记日志 */
            if ($is_add) {
                admin_log($agency['agency_name'], 'add', 'agency');
            } else {
                admin_log($agency['agency_name'], 'edit', 'agency');
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            if ($is_add) {
                $links = [
                    ['href' => 'agency.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_agency']],
                    ['href' => 'agency.php?act=list', 'text' => $GLOBALS['_LANG']['back_agency_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['add_agency_ok'], 0, $links);
            } else {
                $links = [
                    ['href' => 'agency.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_agency_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_agency_ok'], 0, $links);
            }
        }
    }
}
