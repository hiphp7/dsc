<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminAction;
use App\Models\AdminUser;
use App\Models\MerchantsGrade;
use App\Models\MerchantsPrivilege;
use App\Models\SellerGrade;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 管理员信息以及权限管理程序
 */
class MerchantsPrivilegeController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'allot';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        /*------------------------------------------------------ */
        //-- 为管理员分�        �权限
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'allot') {
            $this->dscRepository->helpersLang('priv_action', 'admin');

            admin_priv('users_merchants_priv');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '03_users_merchants_priv']);

            /* 获取默认等级ID */
            $default_grade_id = SellerGrade::where('is_default', 1)->value('id');
            $default_grade_id = $default_grade_id ? $default_grade_id : 0;

            $grade_id = !empty($_REQUEST['grade_id']) ? $_REQUEST['grade_id'] : $default_grade_id;
            $this->smarty->assign("grade_id", $grade_id);

            /* 获取全部等级 */
            $res = SellerGrade::whereRaw(1);
            $seller_grade = $this->baseRepository->getToArrayGet($res);
            $this->smarty->assign("seller_grade", $seller_grade);

            $priv_str = MerchantsPrivilege::where('grade_id', $grade_id)->value('action_list');
            $priv_str = $priv_str ? $priv_str : '';

            /* 获取权限的分组数据 */
            $res = AdminAction::where('parent_id', 0)->where('seller_show', 1);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $rows) {

                // 微信通
                if (!file_exists(MOBILE_WECHAT) && $rows['action_code'] == 'wechat') {
                    continue;
                }

                // 微分销
                if (!file_exists(MOBILE_DRP) && $rows['action_code'] == 'drp') {
                    continue;
                }

                // 微信小程序
                if (!file_exists(MOBILE_WXAPP) && $rows['action_code'] == 'wxapp') {
                    continue;
                }

                // 拼团
                if (!file_exists(MOBILE_TEAM) && $rows['action_code'] == 'team') {
                    continue;
                }

                // 砍价
                if (!file_exists(MOBILE_BARGAIN) && $rows['action_code'] == 'bargain_manage') {
                    continue;
                }

                $priv_arr[$rows['action_id']] = $rows;
            }

            if ($priv_arr) {
                $db_create_in = array_keys($priv_arr);
            } else {
                $db_create_in = '';
            }

            /* 按权限组查询底级的权限名称 */
            $db_create_in = $this->baseRepository->getExplode($db_create_in);
            $res = AdminAction::whereIn('parent_id', $db_create_in)->where('seller_show', 1);
            $result = $this->baseRepository->getToArrayGet($res);
            foreach ($result as $priv) {
                $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
            }

            if ($priv_arr) {
                // 将同一组的权限使用 "," 连接起来，供JS全选 ecmoban模板堂 --zhuo
                foreach ($priv_arr as $action_id => $action_group) {
                    if (isset($action_group['priv'])) {
                        $priv = @array_keys($action_group['priv']);
                        $priv_arr[$action_id]['priv_list'] = join(',', $priv);
                        if (!empty($action_group['priv'])) {
                            foreach ($action_group['priv'] as $key => $val) {
                                $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                            }
                        }
                    }
                }
            } else {
                $priv_arr = [];
            }

            /* 赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['allot_priv']);
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('form_act', 'update_allot');

            /* 显示页面 */

            return $this->smarty->display('merchants_privilege_allot.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员的权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_allot') {
            admin_priv('users_merchants_priv');

            $initialize_allot = isset($_POST['initialize_allot']) ? intval($_POST['initialize_allot']) : 0;
            $grade_id = !empty($_REQUEST['grade_id']) ? $_REQUEST['grade_id'] : 0;

            if ($_POST['action_code']) {
                $act_list = implode(",", $_POST['action_code']);

                $action_list = MerchantsPrivilege::where('grade_id', $grade_id)->value('action_list');
                $action_list = $action_list ? $action_list : '';

                if ($action_list) {
                    $data = ['action_list' => $act_list];
                    MerchantsPrivilege::where('grade_id', $grade_id)->update($data);
                } else {
                    $data = [
                        'action_list' => $act_list,
                        'grade_id' => $grade_id
                    ];
                    MerchantsPrivilege::insert($data);
                }

                //初始化所有商家管理权限
                if ($initialize_allot == 1) {
                    $res = MerchantsGrade::select('ru_id')->where('grade_id', $grade_id);
                    $grade_ru = $this->baseRepository->getToArrayGet($res);
                    $grade_ru = $this->baseRepository->getFlatten($grade_ru);

                    $data = ['action_list' => $act_list];
                    AdminUser::where('ru_id', '>', 0)
                        ->where('suppliers_id', 0)
                        ->whereIn('ru_id', $grade_ru)
                        ->update($data);
                }
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_privilege.php?act=allot&grade_id=' . $grade_id];
            return sys_msg($GLOBALS['_LANG']['action_succeed'], 0, $link);
        }
    }
}
