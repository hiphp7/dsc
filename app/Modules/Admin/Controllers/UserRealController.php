<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 实名认证
 */
class UserRealController extends InitController
{
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /*------------------------------------------------------ */
        //-- 实名认证用户帐号列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('users_real_manage');

            $users_real_list = $this->users_real_list();

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['16_users_real']);//@模板堂-bylu 语言-会员白条列表;
            $this->smarty->assign('users_real_list', $users_real_list['users_real_list']);
            $this->smarty->assign('filter', $users_real_list['filter']);
            $this->smarty->assign('record_count', $users_real_list['record_count']);
            $this->smarty->assign('page_count', $users_real_list['page_count']);
            $this->smarty->assign('full_page', 1);

            $user_type = empty($_REQUEST['user_type']) ? 0 : intval($_REQUEST['user_type']);
            $this->smarty->assign('user_type', $user_type);

            if ($user_type == 1) {
                $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '16_seller_users_real']);
            } else {
                $this->smarty->assign('menu_select', ['action' => '08_members', 'current' => '16_users_real']);
            }


            return $this->smarty->display('users_real_list.dwt');
        } elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('users_real_manage');

            $real_id = empty($_REQUEST['real_id']) ? 0 : trim($_REQUEST['real_id']);
            $user_type = empty($_REQUEST['user_type']) ? 0 : trim($_REQUEST['user_type']);

            $sql = "SELECT ur.*, u.user_name, u.user_id FROM " . $this->dsc->table('users_real') . " AS ur "
                . " JOIN " . $this->dsc->table('users') . " AS u ON ur.user_id = u.user_id "
                . " WHERE ur.real_id = '$real_id'";
            $user_real_info = $this->db->getRow($sql);

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && isset($user_real_info['user_name'])) {
                $user_real_info['bank_mobile'] = $this->dscRepository->stringToStar($user_real_info['bank_mobile']);
                $user_real_info['user_name'] = $this->dscRepository->stringToStar($user_real_info['user_name']);
            }

            if ($user_real_info) {
                if ($user_real_info['front_of_id_card']) {
                    $user_real_info['front_of_id_card'] = get_image_path($user_real_info['front_of_id_card']);
                }
                if ($user_real_info['reverse_of_id_card']) {
                    $user_real_info['reverse_of_id_card'] = get_image_path($user_real_info['reverse_of_id_card']);
                }
            }
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['users_real_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['16_users_real'], 'href' => 'user_real.php?act=list&' . list_link_postfix()]);

            $this->smarty->assign('user_type', $user_type);
            $this->smarty->assign('user_real_info', $user_real_info);
            return $this->smarty->display('users_real_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('users_real_manage');

            $user_id = empty($_POST['user_id']) ? 0 : trim($_POST['user_id']);
            $real_name = empty($_POST['real_name']) ? '' : trim($_POST['real_name']);
            $self_num = empty($_POST['self_num']) ? '' : trim($_POST['self_num']);
            $bank_name = empty($_POST['bank_name']) ? '' : trim($_POST['bank_name']);
            $bank_card = empty($_POST['bank_card']) ? '' : trim($_POST['bank_card']);
            $review_status = empty($_POST['review_status']) ? '' : trim($_POST['review_status']);
            $review_content = empty($_POST['review_content']) ? '' : trim($_POST['review_content']);
            $user_type = empty($_POST['user_type']) ? 0 : intval($_POST['user_type']);
            $post_user_real = [
                'user_id' => $user_id,
                'bank_name' => $bank_name,
                'real_name' => $real_name,
                'self_num' => $self_num,
                'review_status' => $review_status,
                'review_content' => $review_content,
                'bank_card' => $bank_card
            ];

            $type = '';
            if ($user_type) {
                $type = "&user_type=" . $user_type;
            }

            if ($user_id > 0) {
                $sql = "SELECT real_id FROM " . $this->dsc->table('users_real') . " WHERE user_id = '$user_id' AND user_type = '$user_type'";
                $real_id = $this->db->getOne($sql);
                if ($real_id) {
                    $this->db->autoExecute($this->dsc->table('users_real'), $post_user_real, 'UPDATE', "real_id = '$real_id'");
                    $links[] = ['text' => $GLOBALS['_LANG']['16_users_real'], 'href' => 'user_real.php?act=list' . $type];
                    return sys_msg($GLOBALS['_LANG']['user_real_update_success'], 0, $links);
                } else {
                    $post_user_real['add_time'] = gmtime();

                    $this->db->autoExecute($this->dsc->table('users_real'), $post_user_real, 'INSERT');
                    $links[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_real.php?act=list' . $type];
                    return sys_msg($GLOBALS['_LANG']['user_real_set_success'], 0, $links);
                }
            }
        }


        /*------------------------------------------------------ */
        //-- ajax返回用户列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            //检查权限
            $check_auth = check_authz_json('users_real_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $users_real_list = $this->users_real_list();

            $this->smarty->assign('users_real_list', $users_real_list['users_real_list']);
            $this->smarty->assign('filter', $users_real_list['filter']);
            $this->smarty->assign('record_count', $users_real_list['record_count']);
            $this->smarty->assign('page_count', $users_real_list['page_count']);

            $sort_flag = sort_flag($users_real_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('users_real_list.dwt'), '', ['filter' => $users_real_list['filter'], 'page_count' => $users_real_list['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 批量操作实名信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('users_real_manage');
            $user_type = empty($_REQUEST['user_type']) ? 0 : intval($_REQUEST['user_type']);

            $type = '';
            if ($user_type) {
                $type = "&user_type=" . $user_type;
            }

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
            $real_id_arr = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;

            if (isset($_POST['type'])) {
                // 删除实名
                if ($_POST['type'] == 'batch_remove') {
                    $sql = "DELETE FROM " . $this->dsc->table('users_real') .
                        " WHERE real_id " . db_create_in($real_id_arr);

                    if ($this->db->query($sql)) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'user_real.php?act=list' . $type];
                        return sys_msg($GLOBALS['_LANG']['remove_user_real_info_success'], 0, $lnk);
                    }
                    /* 记录日志 */
                    admin_log('', 'batch_trash', 'users_real');
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 0未审核 1审核通过 2审核未通过
                    $time = gmtime();
                    $review_status = $_POST['review_status'];
                    $review_content = !empty($_POST['review_content']) ? trim($_POST['review_content']) : '';

                    $sql = "UPDATE " . $this->dsc->table('users_real') . " SET review_status = '$review_status', review_content = '$review_content', review_time = '$time' "
                        . " WHERE real_id " . db_create_in($real_id_arr);

                    if ($this->db->query($sql)) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'user_real.php?act=list' . $type];
                        return sys_msg($GLOBALS['_LANG']['user_real_info_adopt_status_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除会员实名认证
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('users_real_manage');
            $real_id = !empty($_REQUEST['real_id']) ? intval($_REQUEST['real_id']) : 0;

            $user_type = empty($_REQUEST['user_type']) ? 0 : intval($_REQUEST['user_type']);

            $type = '';
            if ($user_type) {
                $type = "&user_type=" . $user_type;
            }

            if ($real_id > 0) {
                $sql = "DELETE FROM " . $this->dsc->table('users_real') . " WHERE real_id = '$real_id'";
                if ($this->db->query($sql)) {
                    /* 记录管理员操作 */
                    admin_log(addslashes($real_id), 'remove', 'users_real');

                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['16_users_real'], 'href' => 'user_real.php?act=list' . $type];
                    return sys_msg($GLOBALS['_LANG']['remove_user_real_success'], 0, $link);
                }
            }
        }
    }

    /**
     *  实名认证信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function users_real_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            $filter['review_status'] = !isset($_REQUEST['review_status']) ? -1 : intval($_REQUEST['review_status']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
                $filter['review_status'] = json_str_iconv($filter['review_status']);
            }

            $filter['user_type'] = isset($_REQUEST['user_type']) ? intval($_REQUEST['user_type']) : 0;
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'real_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $ex_where = ' WHERE 1 ';
            if ($filter['keywords']) {
                if ($filter['user_type'] == 1) {
                    $store_where = " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
                    $ex_where .= " AND (SELECT msi.user_id FROM " . $GLOBALS['ecs']->table('merchants_shop_information') . ' as msi ' .
                        " WHERE msi.user_id = ur.user_id $store_where) > 0 ";
                } else {
                    $ex_where .= " AND u.user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
                }
            }

            if ($filter['review_status'] != -1) {
                $ex_where .= " AND ur.review_status = '$filter[review_status]'";
            }

            $ex_where .= " AND ur.user_type = '$filter[user_type]'";

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('users_real') . "AS ur "
                . " JOIN " . $this->dsc->table('users') . " AS u ON ur.user_id = u.user_id " . $ex_where;
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);
            $sql = "SELECT ur.*,u.user_name " .
                " FROM " . $this->dsc->table('users_real') . "as ur "
                . " JOIN " . $this->dsc->table('users') . " AS u ON ur.user_id = u.user_id "
                . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $users_real_list = $this->db->getAll($sql);

        for ($i = 0; $i < count($users_real_list); $i++) {
            if ($users_real_list[$i]['user_type']) {
                $users_real_list[$i]['user_name'] = $this->merchantCommonService->getShopName($users_real_list[$i]['user_id'], 1);
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $users_real_list[$i]['bank_mobile'] = $this->dscRepository->stringToStar($users_real_list[$i]['bank_mobile']);
                $users_real_list[$i]['user_name'] = $this->dscRepository->stringToStar($users_real_list[$i]['user_name']);
            }
        }

        $arr = ['users_real_list' => $users_real_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
