<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\Users;
use App\Services\User\UserRankService;
use App\Services\UserRights\RightsCardManageService;

/**
 * 会员等级管理程序
 * 1.4.3功能已分离到UserRankNewController
 */
class UserRankController extends InitController
{
    protected $baseRepository;
    protected $userRankService;

    public function __construct(
        UserRankService $userRankService
    )
    {
        $this->userRankService = $userRankService;
    }

    public function index()
    {
        $exc = new Exchange($this->dsc->table("user_rank"), $this->db, 'rank_id', 'rank_name');
        $exc_user = new Exchange($this->dsc->table("users"), $this->db, 'user_rank', 'user_rank');

        /*------------------------------------------------------ */
        //-- 会员等级列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {

            //1.4.3 同步数据
            $this->userRankService->syncData();

            $this->smarty->assign('menu_select', ['action' => '08_members', 'current' => '05_user_rank_list']);

            $filter = [
//                'special_rank' => 0,//1.4.2 1.4.3修改为只显示普通等级
                'membership_card_display' => 'hide'//1.4.4 优化除分销权益卡绑定以外的等级都显示
            ];
            $ranks = $this->userRankService->getUserRankList($filter);
            $ranks = $ranks ?? [];

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_user_rank_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_user_rank'], 'href' => 'user_rank.php?act=add', 'text2' => $GLOBALS['_LANG']['user_rank_set']]);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('rank_count', count($ranks));
            $this->smarty->assign('user_ranks', $ranks);

            return $this->smarty->display('user_rank.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $ranks = [];
            $ranks = $this->db->getAll("SELECT * FROM " . $this->dsc->table('user_rank') . " WHERE special_rank = 0 order by rank_id");
            $this->smarty->assign('rank_count', count($ranks));
            $this->smarty->assign('user_ranks', $ranks);
            return make_json_result($this->smarty->fetch('user_rank.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加会员等级
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('user_rank');

            $rank['rank_id'] = 0;
            $rank['rank_special'] = 0;
            $rank['show_price'] = 1;
            $rank['max_points'] = 0;
            $rank['discount'] = 100;

//            $min_points = $this->db->getOne(" SELECT MAX(min_points) FROM " . $this->dsc->table('user_rank') . " WHERE special_rank = 0 ");

            $rank['min_points'] = 0;

            $form_action = 'insert';

            $this->smarty->assign('rank', $rank);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_user_rank']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['05_user_rank_list'], 'href' => 'user_rank.php?act=list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_user_rank']);
            $this->smarty->assign('form_action', $form_action);


            return $this->smarty->display('user_rank_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 增加会员等级到数据库
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('user_rank');

            $special_rank = isset($_POST['special_rank']) ? intval($_POST['special_rank']) : 0;
            $min_points = !empty($_POST['min_points']) ? intval($_POST['min_points']) : 0;
            $max_points = 0;
            $rank_name = empty($_POST['rank_name']) ? '' : trim($_POST['rank_name']);
            $show_price = isset($_POST['show_price']) ? intval($_POST['show_price']) : 0;
            $discount = 100;

            if (empty($rank_name)) {
                return sys_msg($GLOBALS['_LANG']['js_languages']['rank_name_empty'], 1);
            }

            /* 检查是否存在重名的会员等级 */
            if (!$exc->is_only('rank_name', $rank_name)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['rank_name_exists'], trim($_POST['rank_name'])), 1);
            }

            /* 非特殊会员组检查积分的上下限是否合理 */
//            if ($_POST['min_points'] >= $_POST['max_points']) {
//                return sys_msg($GLOBALS['_LANG']['js_languages']['integral_max_small'], 1);
//            }

            /* 检查下限制有无重复 */
            if (!$exc->is_only('min_points', $min_points, 0, 'special_rank = 0')) {
                return sys_msg(sprintf($GLOBALS['_LANG']['integral_min_exists'], intval($_POST['min_points'])));
            }

            /* 检查上限有无重复 */
//            if (!$exc->is_only('max_points', intval($_POST['max_points']), 0, 'special_rank = 0')) {
//                return sys_msg(sprintf($GLOBALS['_LANG']['integral_max_exists'], intval($_POST['max_points'])));
//            }


            $sql = "INSERT INTO " . $this->dsc->table('user_rank') . "( " .
                "rank_name, min_points, max_points, discount, special_rank, show_price" .
                ") VALUES (" .
                "'$rank_name', '" . $min_points . "', '" . $max_points . "', " .
                "'$discount', '$special_rank', '" . $show_price . "')";
            $this->db->query($sql);

            /* 管理员日志 */
            admin_log(trim($_POST['rank_name']), 'add', 'user_rank');
            clear_cache_files();

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'user_rank.php?act=list'];
            $lnk[] = ['text' => $GLOBALS['_LANG']['add_continue'], 'href' => 'user_rank.php?act=add'];
            return sys_msg($GLOBALS['_LANG']['add_rank_success'], 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 编辑会员等级
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            //1.4.3 废弃
//            admin_priv('user_rank');
//            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
//            $sql = "SELECT * FROM" . $this->dsc->table("user_rank") . " WHERE rank_id='$id'";
//            $rank = $this->db->getRow("$sql");
//
//            //权益
//
//            $rights_list = [];
//            $this->smarty->assign('rights_list', $rights_list);
//
//            $this->smarty->assign('rank', $rank);
//            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_user_rank']);
//            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['05_user_rank_list'], 'href' => 'user_rank.php?act=list']);
//            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_user_rank']);
//            $this->smarty->assign('form_action', "update");
//
//            return $this->smarty->display('user_rank_edit.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            //1.4.3 废弃
//            admin_priv('user_rank');
//
//            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
//            $min_points = !empty($_POST['min_points']) ? intval($_POST['min_points']) : 0;
//            $max_points = 0;
//            $special_rank = 0;
//            $rank_name = empty($_POST['rank_name']) ? '' : trim($_POST['rank_name']);
//            $show_price = isset($_POST['show_price']) ? intval($_POST['show_price']) : 0;
//            $discount = 0;
//
//            /* 检查是否存在重名的会员等级 */
//            if (!$exc->is_only('rank_name', $rank_name, '0', "rank_id != '$id'")) {
//                return sys_msg(sprintf($GLOBALS['_LANG']['rank_name_exists'], $rank_name), 1);
//            }
//
//            /* 非特殊会员组检查积分的上下限是否合理 */
////            if ($special_rank == 0 && $min_points >= $max_points) {
////                return sys_msg($GLOBALS['_LANG']['js_languages']['integral_max_small'], 1);
////            }
//
//            /* 特殊等级会员组不判断积分限制 */
//            /* 检查下限制有无重复 */
//            if (!$exc->is_only('min_points', $min_points, '0', "rank_id != '$id' AND special_rank = 0")) {
//                return sys_msg(sprintf($GLOBALS['_LANG']['integral_min_exists'], $min_points));
//            }
//
//            $old_max_points = get_table_date('user_rank', "rank_id = $id", ['max_points'], 2);
////            $next_max_points = get_table_date('user_rank', "min_points = ($old_max_points + 1) OR rank_id > '$id' ", ['max_points'], 2);
////
////            if ($special_rank == 0 && ($max_points + 1) >= $next_max_points) {
////                return sys_msg($GLOBALS['_LANG']['full_max_exists'], 1);
////            }
//
//            $sql = " UPDATE " . $this->dsc->table('user_rank') . " SET min_points = '$max_points' + 1 WHERE min_points = '$old_max_points' + 1 OR rank_id > '$id' LIMIT 1 ";
//            $this->db->query($sql);
//
//            $sql = "UPDATE" . $this->dsc->table('user_rank') . " SET rank_name = '$rank_name' "
//                . ", min_points = '$min_points' , max_points = '$max_points' ,"
//                . " discount = '$discount' , special_rank = '$special_rank' , show_price = '$show_price' WHERE rank_id = '$id'";
//            $this->db->query($sql);
//
//            /* 管理员日志 */
//            admin_log($rank_name, 'edit', 'user_rank');
//            clear_cache_files();
//
//            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'user_rank.php?act=list'];
//            return sys_msg($GLOBALS['_LANG']['edit'] . $GLOBALS['_LANG']['success'], 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 删除会员等级
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('user_rank');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $rank_id = intval($_GET['id']);

            if (file_exists(MOBILE_DRP) && $rank_id > 0) {
                // 检查会员等级是否绑定关联权益卡 且权益卡能否删除
                $can_delete = app(RightsCardManageService::class)->checkCard($rank_id);
                if ($can_delete == false) {
                    return make_json_error(lang('admin/users.user_rank_canot_remove'));
                }
            }

            //等级下有会员不可删除
            if (Users::where('user_rank', $rank_id)->count()) {
                return make_json_error(lang('admin/user_rank.user_rank_has_user'));
            }

            if ($exc->drop($rank_id)) {
                /* 更新会员表的等级字段 */
                $exc_user->edit("user_rank = 0", $rank_id);

                app(UserRankService::class)->deleteUserRankRights(0, $rank_id);

                $rank_name = $exc->get_name($rank_id);
                admin_log(addslashes($rank_name), 'remove', 'user_rank');
                clear_cache_files();
            }

            $url = 'user_rank.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 切换是否是特殊会员组
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_special') {
            $check_auth = check_authz_json('user_rank');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $rank_id = intval($_POST['id']);
            $is_special = intval($_POST['val']);

            if ($exc->edit("special_rank = '$is_special'", $rank_id)) {
                $rank_name = $exc->get_name($rank_id);
                admin_log(addslashes($rank_name), 'edit', 'user_rank');
                return make_json_result($is_special);
            } else {
                return make_json_error($this->db->error());
            }
        }
        /*------------------------------------------------------ */
        //-- 切换是否显示价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_showprice') {
            $check_auth = check_authz_json('user_rank');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $rank_id = intval($_POST['id']);
            $is_show = intval($_POST['val']);

            if ($exc->edit("show_price = '$is_show'", $rank_id)) {
                $rank_name = $exc->get_name($rank_id);
                admin_log(addslashes($rank_name), 'edit', 'user_rank');
                clear_cache_files();
                return make_json_result($is_show);
            } else {
                return make_json_error($this->db->error());
            }
        }
        /*------------------------------------------------------ */
        //-- 设置会员有效期
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_rank_set') {
            $check_auth = check_authz_json('user_rank');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $rank_config = [];
            //等级积分有效期设置
            $rank_config['open_user_rank_set'] = $GLOBALS['_CFG']['open_user_rank_set'] ?? 0;
            $rank_config['clear_rank_point'] = $GLOBALS['_CFG']['clear_rank_point'] ?? 12;
            $this->smarty->assign('rank_config', $rank_config);
            return make_json_result($this->smarty->fetch('library/user_rank_set.lbi'));
        }
        /*------------------------------------------------------ */
        //-- 更新会员有效期
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_user_rank_set') {
            $check_auth = check_authz_json('user_rank');
            if ($check_auth !== true) {
                return $check_auth;
            }
            //更新是否开启等级积分将会自动清零
            $open_user_rank['value'] = $_POST['open_user_rank'] ? intval($_POST['open_user_rank']) : 0;
            $this->db->autoExecute($this->dsc->table('shop_config'), $open_user_rank, 'update', "code='open_user_rank_set'");

            //更新是否开启等级积分将会自动清零有效期
            $clear_rank_point['value'] = $_POST['clear_rank_point'] ? intval($_POST['clear_rank_point']) : 12;
            $this->db->autoExecute($this->dsc->table('shop_config'), $clear_rank_point, 'update', "code='clear_rank_point'");
        }
    }

}
