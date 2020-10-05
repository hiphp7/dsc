<?php

namespace App\Modules\Admin\Controllers;

use App\Models\BaitiaoLog;
use App\Models\BiaotiaoLogChange;
use App\Repositories\Common\DscRepository;
use App\Services\User\UserBaitiaoService;

/**
 * 白条管理程序
 */
class UserBaitiaoLogController extends InitController
{
    protected $userBaitiaoService;
    protected $dscRepository;

    public function __construct(
        UserBaitiaoService $userBaitiaoService,
        DscRepository $dscRepository
    )
    {
        $this->userBaitiaoService = $userBaitiaoService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* ------------------------------------------------------ */
        //-- 用户帐号列表
        /* ------------------------------------------------------ */
        //白条信息
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            $baitiao_list = $this->baitiao_list();

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bt_list']); //@模板堂-bylu 语言-会员白条列表;
            $this->smarty->assign('baitiao_list', $baitiao_list['baitiao_list']);
            $this->smarty->assign('filter', $baitiao_list['filter']);
            $this->smarty->assign('record_count', $baitiao_list['record_count']);
            $this->smarty->assign('page_count', $baitiao_list['page_count']);
            $this->smarty->assign('full_page', 1);


            return $this->smarty->display('baitiao_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 白条消费记录
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'log_list') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

            //会员白条信息
            $bt_other = array(
                'user_id' => $user_id
            );
            $bt_info = $this->userBaitiaoService->getBaitiaoInfo($bt_other);
            $bt_info['format_amount'] = isset($bt_info['amount']) ? price_format($bt_info['amount'], false) : 0;

            //所有待还款白条的总额和条数
            $sql = "SELECT SUM(b.stages_one_price * (b.stages_total - b.yes_num)) AS total_amount, COUNT(log_id) AS numbers , SUM(b.stages_one_price * b.yes_num) AS already_amount FROM " . $this->dsc->table('baitiao_log') . " AS b " .
                " WHERE b.user_id = '$user_id' AND b.is_repay = 0 AND b.is_refund = 0 LIMIT 1";
            $repay_bt = $this->db->getRow($sql);

            $repay_bt['format_total_amount'] = price_format($repay_bt['total_amount'], false);
            $repay_bt['format_already_amount'] = price_format($repay_bt['already_amount'], false);

            $bt_log = $this->get_baitiao_list($user_id);

            $remain_amount = isset($bt_info['amount']) ? floatval($bt_info['amount']) - floatval($repay_bt['total_amount']) : 0 - floatval($repay_bt['total_amount']);
            $format_remain_amount = price_format($remain_amount, false);

            $this->smarty->assign('remain_amount', $remain_amount);
            $this->smarty->assign('format_remain_amount', $format_remain_amount);
            $this->smarty->assign('bt_info', $bt_info);
            $this->smarty->assign('repay_bt', $repay_bt);

            $bt_amount = isset($bt_amount) ? $bt_amount : '';
            $this->smarty->assign('bt_amount', $bt_amount);

            $this->smarty->assign('bt_logs', $bt_log['bt_log']);
            $this->smarty->assign('filter', $bt_log['filter']);
            $this->smarty->assign('record_count', $bt_log['record_count']);
            $this->smarty->assign('page_count', $bt_log['page_count']);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['bt_list'], 'href' => 'user_baitiao_log.php?act=list']);
            $this->smarty->assign('baitiao_page', ['total' => isset($total) ? $total : 0, 'page_total' => isset($page_total) ? $page_total : 0, 'page_one_num' => isset($page_one_num) ? $page_one_num : 0]);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bt_details']); //@模板堂-bylu 语言-白条详情;

            return $this->smarty->display('baitiao_log_list.dwt');
        } elseif ($_REQUEST['act'] == 'log_list_query') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            $baitiao_id = isset($_REQUEST['bt_id']) ? intval($_REQUEST['bt_id']) : 0;
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            //会员白条信息
            $bt = "SELECT * FROM " . $this->dsc->table('baitiao') . " WHERE user_id='$user_id'";

            $bt_info = $this->db->getRow($bt);
            $bt_info['format_amount'] = isset($bt_info['amount']) ? price_format($bt_info['amount'], false) : 0;
            //待还款总额(不含分期金额)
            $bt_sun = "SELECT SUM(o.order_amount) FROM " . $this->dsc->table('baitiao_log') . " AS b LEFT JOIN " . $this->dsc->table('order_info') . "  AS o ON b.order_id=o.order_id WHERE b.user_id='$user_id' AND b.is_repay=0 AND  b.is_stages<>1  AND is_refund=0 ";
            $repay_sun_amount = $this->db->getOne($bt_sun);

            //所有待还款白条的总额和条数
            $bt_repay = "SELECT SUM(o.order_amount) AS total_amount,COUNT(log_id) AS numbers , SUM(b.stages_one_price * b.yes_num) AS already_amount FROM " . $this->dsc->table('baitiao_log') . " AS b " .
                "LEFT JOIN " . $this->dsc->table('order_info') . "  AS o ON b.order_id=o.order_id " .
                " WHERE b.user_id='$user_id' AND b.is_repay=0 AND is_refund=0";
            $repay_bt = $this->db->getRow($bt_repay);

            $repay_bt['format_total_amount'] = price_format($repay_bt['total_amount'], false);
            $repay_bt['format_already_amount'] = price_format($repay_bt['already_amount'], false);

            $bt_log = $this->get_baitiao_list($user_id);

            $remain_amount = isset($bt_info['amount']) ? floatval($bt_info['amount']) - floatval($repay_bt['total_amount']) : 0 - floatval($repay_bt['total_amount']);
            $format_remain_amount = price_format($remain_amount, false);
            $this->smarty->assign('remain_amount', $remain_amount);
            $this->smarty->assign('format_remain_amount', $format_remain_amount);
            $this->smarty->assign('bt_info', $bt_info);
            $this->smarty->assign('repay_sun_amount', $repay_sun_amount);
            $this->smarty->assign('repay_bt', $repay_bt);

            $bt_amount = isset($bt_amount) ? $bt_amount : '';
            $this->smarty->assign('bt_amount', $bt_amount);

            $this->smarty->assign('bt_logs', $bt_log['bt_log']);
            $this->smarty->assign('filter', $bt_log['filter']);
            $this->smarty->assign('record_count', $bt_log['record_count']);
            $this->smarty->assign('page_count', $bt_log['page_count']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['bt_list'], 'href' => 'user_baitiao_log.php?act=list']);
            $this->smarty->assign('baitiao_page', ['total' => isset($total) ? $total : 0, 'page_total' => isset($page_total) ? $page_total : 0, 'page_one_num' => isset($page_one_num) ? $page_one_num : 0]);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bt_details']); //@模板堂-bylu 语言-白条详情;
            return make_json_result($this->smarty->fetch('baitiao_log_list.dwt'), '', ['filter' => $bt_log['filter'], 'page_count' => $bt_log['page_count']]);
        } /* --------wang 白条---------- */

        elseif ($_REQUEST['act'] == 'bt_add_tp') {

            /* 检查权限 */
            admin_priv('baitiao_manage');

            $user_id = empty($_REQUEST['user_id']) ? '' : trim($_REQUEST['user_id']);
            if ($user_id > 0) {
                //会员信息
                $user_sql = "SELECT user_name,user_id FROM " . $this->dsc->table('users') . " WHERE user_id='$user_id'";
                $user_info = $this->db->getRow($user_sql);

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && isset($user_info['user_name'])) {
                    $user_info['user_name'] = $this->dscRepository->stringToStar($user_info['user_name']);
                }

                $bt_sql = "SELECT b.*,u.user_name,u.user_id FROM " . $this->dsc->table('baitiao') . " AS b LEFT JOIN " . $this->dsc->table('users') . " AS u ON u.user_id=b.user_id WHERE b.user_id='$user_id'";
                $bt_info = $this->db->getRow($bt_sql);

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && isset($bt_info['user_name'])) {
                    $bt_info['user_name'] = $this->dscRepository->stringToStar($bt_info['user_name']);
                }

                $this->smarty->assign('action_link2', ['href' => 'users.php?act=list', 'text' => $GLOBALS['_LANG']['03_users_list']]);
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bt_ur_here']);
            $this->smarty->assign('action_link', ['href' => 'baitiao_batch.php?act=add', 'text' => $GLOBALS['_LANG']['baitiao_batch_set']]);
            $this->smarty->assign("user_id", $user_id);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('form_action', 'bt_edit');
            $this->smarty->assign('user_info', $user_info);
            $this->smarty->assign('bt_info', $bt_info);
            return $this->smarty->display('user_list_edit.dwt');
        } /* 设置白条金额 */
        elseif ($_REQUEST['act'] == 'bt_edit') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            $user_id = empty($_POST['user_id']) ? 0 : trim($_POST['user_id']);
            $amount = empty($_POST['amount']) ? 0 : floatval(trim($_POST['amount']));
            $repay_term = empty($_POST['repay_term']) ? 0 : intval($_POST['repay_term']);
            $over_repay_trem = empty($_POST['over_repay_trem']) ? 0 : intval($_POST['over_repay_trem']);
            if ($user_id > 0) {
                $bt_sql = "SELECT baitiao_id FROM " . $this->dsc->table('baitiao') . " WHERE user_id='$user_id'";
                $bt_info = $this->db->getOne($bt_sql);
                if ($bt_info) {
                    $bt_up_sql = "update " . $this->dsc->table('baitiao') . " set amount='$amount',repay_term='$repay_term',over_repay_trem='$over_repay_trem',add_time=" . gmtime() . " where baitiao_id='$bt_info.baitiao_id'";
                    $up_ok = $this->db->query($bt_up_sql);
                    if ($up_ok) {
                        $links[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_baitiao_log.php?act=bt_add_tp&user_id=' . $user_id];
                        return sys_msg($GLOBALS['_LANG']['baitiao_update_success'], 0, $links);
                    }
                } else {
                    $bt_insert_sql = "INSERT INTO " . $this->dsc->table('baitiao') . " (user_id,amount,repay_term,over_repay_trem,add_time) VALUES ('$user_id','$amount','$repay_term','$over_repay_trem'," . gmtime() . ")";
                    $in_ok = $this->db->query($bt_insert_sql);
                    if ($in_ok) {
                        $links[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_baitiao_log.php?act=bt_add_tp&user_id=' . $user_id];
                        return sys_msg($GLOBALS['_LANG']['baitiao_set_success'], 0, $links);
                    }
                }
            }
        }
        /* --------wang 白条---------- */

        /* ------------------------------------------------------ */
        //-- ajax返回用户列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            //检查权限
            $check_auth = check_authz_json('baitiao_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $baitiao_list = $this->baitiao_list();

            $this->smarty->assign('baitiao_list', $baitiao_list['baitiao_list']);
            $this->smarty->assign('filter', $baitiao_list['filter']);
            $this->smarty->assign('record_count', $baitiao_list['record_count']);
            $this->smarty->assign('page_count', $baitiao_list['page_count']);

            $sort_flag = sort_flag($baitiao_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('baitiao_list.dwt'), '', ['filter' => $baitiao_list['filter'], 'page_count' => $baitiao_list['page_count']]);
        }
        /* ------------------------------------------------------ */
        //-- 批量删除白条
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }

            $sql = "DELETE FROM " . $this->dsc->table('baitiao') .
                " WHERE baitiao_id " . db_create_in(join(',', $_POST['checkboxes']));
            $del_ok = $this->db->query($sql);
            if ($del_ok) {
                $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'user_baitiao_log.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['baitiao_remove_success'], 0, $lnk);
            }
        }
        /* ------------------------------------------------------ */
        //-- 批量删除白条消费记录
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove_log') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
            $sql = "SELECT log_id,is_repay,baitiao_id,user_id FROM " . $this->dsc->table('baitiao_log') . " WHERE log_id " . db_create_in(join(',', $_POST['checkboxes']));
            $bt_log = $this->db->getAll($sql);

            if ($bt_log) {
                $no_del_num = 0;
                foreach ($bt_log as $key => $val) {
                    if ($val['is_repay']) {
                        $del_sql = "DELETE FROM " . $this->dsc->table('baitiao_log') .
                            " WHERE is_repay=1 and log_id =" . $val['log_id'];

                        $del_ok = $this->db->query($del_sql);
                    } else {
                        $no_del_num++;
                    }
                }

                if ($no_del_num > 0) {
                    $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'user_baitiao_log.php?act=log_list&bt_id=' . $bt_log[0]['baitiao_id'] . '&user_id=' . $bt_log[0]['user_id']];
                    return sys_msg($GLOBALS['_LANG']['you'] . $no_del_num . $GLOBALS['_LANG']['no_del_num_notic'], 0, $lnk);
                } else {
                    $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'user_baitiao_log.php?act=log_list&bt_id=' . $bt_log[0]['baitiao_id'] . '&user_id=' . $bt_log[0]['user_id']];
                    return sys_msg($GLOBALS['_LANG']['remove_consume_success'], 0, $lnk);
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
        }
        /* ------------------------------------------------------ */
        //-- 删除会员白条
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            $sql = "SELECT baitiao_id FROM " . $this->dsc->table('baitiao') . " WHERE baitiao_id = '" . $_GET['baitiao_id'] . "'";
            $baitiao_id = $this->db->getOne($sql);
            if ($baitiao_id > 0) {
                $sql = "delete from " . $this->dsc->table('baitiao') . " where baitiao_id = '" . $baitiao_id . "'";
                $del_ok = $this->db->query($sql);
                if ($del_ok) {
                    /* 记录管理员操作 */
                    admin_log(addslashes($baitiao_id), 'remove', 'baitiao');

                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_baitiao_log.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['baitiao_remove_success'], 0, $link);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 修改白条分期金额
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_stages_one_price') {
            $check_auth = check_authz_json('baitiao_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $log_id = intval($_POST['id']);
            $stages_one_price = floatval($_POST['val']);
            $stages_one_price = number_format($stages_one_price, 2, '.', '');

            $original_price = BaitiaoLog::where('log_id', $log_id)->value('stages_one_price');
            $original_price = $original_price ? $original_price : 0;

            BaitiaoLog::where('log_id', $log_id)->update([
                'stages_one_price' => $stages_one_price
            ]);

            if ($stages_one_price != $original_price) {
                BiaotiaoLogChange::insert([
                    'log_id' => $log_id,
                    'original_price' => $original_price,
                    'chang_price' => $stages_one_price,
                    'add_time' => gmtime()
                ]);
            }

            return make_json_result($stages_one_price);
        }

        /* ------------------------------------------------------ */
        //-- 删除会员白条消费记录
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_log') {
            /* 检查权限 */
            admin_priv('baitiao_manage');

            $sql = "SELECT log_id,is_repay,baitiao_id,user_id,is_refund FROM " . $this->dsc->table('baitiao_log') . " WHERE log_id = '" . $_GET['log_id'] . "'";
            $bt_log = $this->db->getRow($sql);
            if ($bt_log['log_id'] > 0) {
                if ($bt_log['is_repay'] || $bt_log['is_refund']) {//已退款白条订单页可以删除;
                    $sql = "DELETE FROM " . $this->dsc->table('baitiao_log') . " WHERE is_repay=1 OR is_refund=1 AND log_id = '" . $bt_log['log_id'] . "'";
                    $del_ok = $this->db->query($sql);
                    $log_id = $this->db->insert_id();

                    if ($del_ok) {
                        /* 记录管理员操作 */
                        admin_log(addslashes($log_id), 'remove', 'baitiao_log');

                        /* 提示信息 */
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_baitiao_log.php?act=log_list&bt_id=' . $bt_log['baitiao_id'] . '&user_id=' . $bt_log['user_id']];
                        return sys_msg($GLOBALS['_LANG']['biao_remove_consume_success'], 0, $link);
                    }
                } else {
                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_baitiao_log.php?act=log_list&bt_id=' . $bt_log['baitiao_id'] . '&user_id=' . $bt_log['user_id']];
                    return sys_msg($GLOBALS['_LANG']['pay_not_consume_remove'], 0, $link);
                }
            }
        }
    }

    /**
     *  返回会员白条列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function baitiao_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'baitiao_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $ex_where = ' WHERE 1 ';
            if ($filter['keywords']) {
                $ex_where .= " AND u.user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
            }
            $filter['record_count'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('baitiao') . "AS b LEFT JOIN " . $this->dsc->table('users') . " AS u ON b.user_id=u.user_id " . $ex_where);

            /* 分页大小 */
            $filter = page_and_size($filter);
            $sql = "SELECT b.*,u.user_name " .
                " FROM " . $this->dsc->table('baitiao') . "as b left join " . $this->dsc->table('users') . " as u on b.user_id=u.user_id " . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $baitiao_list = $this->db->getAll($sql);

        if ($baitiao_list) {
            foreach ($baitiao_list as $key => $row) {
                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $baitiao_list[$key]['user_name'] = $this->dscRepository->stringToStar($row['user_name']);
                }
            }
        }

        $arr = ['baitiao_list' => $baitiao_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    private function get_baitiao_list($user_id = 0)
    {
        $result = get_filter();
        if ($result === false) {
            if ($user_id > 0) {
                $filter['user_id'] = $user_id;
            }
            $ex_where = ' WHERE 1 ';
            if ($filter['user_id'] > 0) {
                $ex_where .= " AND b.user_id = '" . mysql_like_quote($filter['user_id']) . "'";
            }
            $filter['record_count'] = $this->db->getOne("SELECT COUNT(b.log_id) AS total FROM " . $this->dsc->table('baitiao_log') . " AS b LEFT JOIN " . $this->dsc->table('order_info') . "  AS o ON b.order_id=o.order_id  $ex_where ORDER BY b.log_id DESC");

            /* 分页大小 */
            $filter = page_and_size($filter);
            $sql = "SELECT b.*,o.order_sn, o.money_paid AS order_amount FROM " . $this->dsc->table('baitiao_log') . " AS b LEFT JOIN " . $this->dsc->table('order_info') . "  AS o ON b.order_id=o.order_id  $ex_where ORDER BY b.log_id DESC" .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            $filter['keywords'] = isset($filter['keywords']) ? stripslashes($filter['keywords']) : '';
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $bt_log = $this->db->getAll($sql);
        if ($bt_log) {
            foreach ($bt_log as $key => $val) {
                $bt_log[$key]['use_date'] = local_date($GLOBALS['_CFG']['time_format'], $bt_log[$key]['use_date']);
                //如果是白条分期付款商品;
                if ($val['is_stages'] == 1) {
                    //分期付款的还款日期;
                    $bt_log[$key]['repay_date'] = unserialize($bt_log[$key]['repay_date']);
                } else {
                    $bt_log[$key]['repay_date'] = local_date($GLOBALS['_CFG']['time_format'], $bt_log[$key]['repay_date']);
                }
                if ($bt_log[$key]['repayed_date']) {
                    $bt_log[$key]['repayed_date'] = local_date($GLOBALS['_CFG']['time_format'], $bt_log[$key]['repayed_date']);
                }
            }
        }
        $arr = ['bt_log' => $bt_log, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
