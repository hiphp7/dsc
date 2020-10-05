<?php

namespace App\Modules\Admin\Controllers;

use App\Models\MerchantsPercent;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantsPercentManageService;

/**
 * 管理中心奖励额度管理
 */
class MerchantsPercentController extends InitController
{

    protected $baseRepository;
    protected $merchantsPercentManageService;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantsPercentManageService $merchantsPercentManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantsPercentManageService = $merchantsPercentManageService;
    }

    public function index()
    {
        define('SUPPLIERS_ACTION_LIST', 'delivery_view,back_view');

        /*------------------------------------------------------ */
        //-- 奖励额度列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'add_suppliers_percent']);

            /* 检查权限 */
            admin_priv('merchants_percent');

            $user_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            if (empty($user_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            $result = $this->merchantsPercentManageService->percentList();

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['suppliers_percent_list']); // 当前导航
            $this->smarty->assign('action_link', ['href' => 'merchants_percent.php?act=add', 'text' => $GLOBALS['_LANG']['add_suppliers_percent']]);
            $this->smarty->assign('action_link2', ['href' => 'merchants_commission.php?act=list', 'text' => $GLOBALS['_LANG']['suppliers_list_server']]);

            $this->smarty->assign('full_page', 1); // 翻页参数

            $this->smarty->assign('percent_list', $result['result']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');
            $this->smarty->assign('user_id', $user_id);

            /* 显示模板 */

            return $this->smarty->display('merchants_percent_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('merchants_percent');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->merchantsPercentManageService->percentList();

            $this->smarty->assign('percent_list', $result['result']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_percent_list.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 列表页编辑名称 ajax
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_percent_value') {
            $check_auth = check_authz_json('merchants_percent');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $value = json_str_iconv(trim($_POST['val']));

            /* 判断名称是否重复 */
            $res = MerchantsPercent::where('percent_value', $value)->where('percent_id', '<>', $id)->count();
            if ($res > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['percent_name_exist'], $value));
            } else {
                /* 保存奖励额度信息 */
                $data = ['percent_value' => $value];
                $res = MerchantsPercent::where('percent_id', $id)->update($data);
                if ($res >= 0) {
                    /* 记日志 */
                    admin_log($value, 'edit', 'merchants_percent');

                    clear_cache_files();

                    return make_json_result(stripslashes($value));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['agency_edit_fail'], $value));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 列表页编辑排序 ajax
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('merchants_percent');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $value = json_str_iconv(trim($_POST['val']));

            /* 判断名称是否重复 */
            $res = MerchantsPercent::where('sort_order', $value)->where('percent_id', '<>', $id)->count();
            if ($res > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['percent_sort_exist'], $value));
            } else {
                /* 保存奖励额度信息 */
                $data = ['sort_order' => $value];
                $res = MerchantsPercent::where('percent_id', $id)->update($data);
                if ($res >= 0) {
                    /* 记日志 */
                    admin_log($value, 'edit', 'merchants_percent');

                    clear_cache_files();

                    return make_json_result(stripslashes($value));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['agency_edit_fail'], $value));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除奖励额度
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('merchants_percent');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['id']);

            MerchantsPercent::where('percent_id', $id)->delete();
            /* 记日志 */
            admin_log($id, 'remove', 'merchants_percent');

            /* 清除缓存 */
            clear_cache_files();

            $url = 'merchants_percent.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
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
                admin_priv('merchants_percent');

                $ids = $_POST['checkboxes'];
                $ids = $this->baseRepository->getExplode($ids);
                if (isset($_POST['remove'])) {

                    $res = MerchantsPercent::whereIn('percent_id', $ids);
                    $percent = $this->baseRepository->getToArrayGet($res);

                    if (empty($percent)) {
                        return sys_msg($GLOBALS['_LANG']['batch_drop_no']);
                    }

                    MerchantsPercent::whereIn('percent_id', $ids)->delete();

                    /* 记日志 */
                    $percent_value = '';
                    foreach ($percent as $value) {
                        $percent_value .= $value['percent_value'] . '|';
                    }
                    admin_log($percent_value, 'remove', 'suppliers');

                    /* 清除缓存 */
                    clear_cache_files();

                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok']);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑奖励额度
        /*------------------------------------------------------ */
        elseif (in_array($_REQUEST['act'], ['add', 'edit'])) {
            /* 检查权限 */
            admin_priv('merchants_percent');

            $this->smarty->assign('action_link', ['href' => 'merchants_percent.php?act=list', 'text' => $GLOBALS['_LANG']['suppliers_percent_list']]);

            if ($_REQUEST['act'] == 'add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_suppliers_percent']);


                $this->smarty->assign('form_action', 'insert');


                return $this->smarty->display('merchants_percent_info.dwt');
            } elseif ($_REQUEST['act'] == 'edit') {
                $suppliers = [];

                /* 取得奖励额度信息 */
                $id = $_REQUEST['id'];

                $res = MerchantsPercent::where('percent_id', $id);
                $percent = $this->baseRepository->getToArrayFirst($res);
                if (count($percent) <= 0) {
                    return sys_msg('suppliers_percent does not exist');
                }

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_suppliers_percent']);

                $this->smarty->assign('form_action', 'update');
                $this->smarty->assign('percent', $percent);


                return $this->smarty->display('merchants_percent_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 提交添加、编辑奖励额度
        /*------------------------------------------------------ */
        elseif (in_array($_REQUEST['act'], ['insert', 'update'])) {
            /* 检查权限 */
            admin_priv('merchants_percent');

            if ($_REQUEST['act'] == 'insert') {
                /* 提交值 */
                $suppliers = ['percent_value' => trim($_POST['percent_value']),
                    'sort_order' => trim($_POST['sort_order']),
                    'add_time' => gmtime()
                ];

                /* 判断名称是否重复 */

                $res = MerchantsPercent::where('percent_value', $suppliers['percent_value'])->count();
                if ($res > 0) {
                    return sys_msg($GLOBALS['_LANG']['percent_name_exist']);
                }

                $suppliers['percent_id'] = MerchantsPercent::insertGetId($suppliers);

                /* 记日志 */
                admin_log($suppliers['percent_value'], 'add', 'merchants_percent');

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [['href' => 'merchants_percent.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_percent']],
                    ['href' => 'merchants_percent.php?act=list', 'text' => $GLOBALS['_LANG']['back_percent_list']]
                ];

                return sys_msg($GLOBALS['_LANG']['add_percent_ok'], 0, $links);
            }

            if ($_REQUEST['act'] == 'update') {
                /* 提交值 */
                $percent = ['id' => trim($_POST['id'])];

                $percent['new'] = ['percent_value' => trim($_POST['percent_value']),
                    'sort_order' => trim($_POST['sort_order'])
                ];

                /* 取得奖励额度信息 */

                $res = MerchantsPercent::where('percent_id', $percent['id']);
                $percent['old'] = $this->baseRepository->getToArrayFirst($res);

                if (empty($percent['old']['percent_id'])) {
                    return sys_msg('suppliers_percent does not exist');
                }

                /* 判断名称是否重复 */
                $res = MerchantsPercent::where('percent_value', $percent['new']['percent_value'])
                    ->where('percent_id', '<>', $percent['id'])
                    ->count();
                if ($res > 0) {
                    return sys_msg($GLOBALS['_LANG']['percent_name_exist']);
                }

                /* 保存奖励额度信息 */
                MerchantsPercent::where('percent_id', $percent['id'])->update($percent['new']);

                /* 记日志 */
                admin_log($percent['old']['percent_value'], 'edit', 'merchants_percent');

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links[] = ['href' => 'merchants_percent.php?act=list', 'text' => $GLOBALS['_LANG']['back_percent_list']];
                return sys_msg($GLOBALS['_LANG']['edit_percent_ok'], 0, $links);
            }
        }
    }
}
