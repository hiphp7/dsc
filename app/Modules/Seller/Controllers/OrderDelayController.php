<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;

/**
 * 延迟收货
 */
class OrderDelayController extends InitController
{
    public function index()
    {
        $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '11_order_delayed']);

        /*------------------------------------------------------ */
        //-- 延迟收货申请列表
        /*------------------------------------------------------ */
        $exc = new Exchange($this->dsc->table("order_delayed"), $this->db, 'delayed_id', 'apply_day', 'order_id');//
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('order_delayed');

            $order_delay_list = get_order_delayed_list();
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['04_order']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_delay_apply']);
            $this->smarty->assign('order_delay_list', $order_delay_list['order_delay_list']);
            $this->smarty->assign('filter', $order_delay_list['filter']);
            $this->smarty->assign('record_count', $order_delay_list['record_count']);
            $this->smarty->assign('page_count', $order_delay_list['page_count']);
            $this->smarty->assign('full_page', 1);

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_delay_list, $page);

            $this->smarty->assign('page_count_arr', $page_count_arr);

            return $this->smarty->display('order_delayed_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            //检查权限
            $check_auth = check_authz_json('order_delayed');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_delay_list = get_order_delayed_list();

//    print_arr($order_delay_list);
            $this->smarty->assign('order_delay_list', $order_delay_list['order_delay_list']);
            $this->smarty->assign('filter', $order_delay_list['filter']);
            $this->smarty->assign('record_count', $order_delay_list['record_count']);
            $this->smarty->assign('page_count', $order_delay_list['page_count']);

            $sort_flag = sort_flag($order_delay_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('order_delayed_list.dwt'), '', ['filter' => $order_delay_list['filter'], 'page_count' => $order_delay_list['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 批量操作 延迟收货
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('order_delayed');


            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_any_data'], 1);
            }
            $delay_id_arr = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : [];
            $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 0;
            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    $sql = "DELETE FROM " . $this->dsc->table('order_delayed') .
                        " WHERE delayed_id " . db_create_in($delay_id_arr);

                    if ($this->db->query($sql)) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'order_delay.php?act=list'];
                        return sys_msg($GLOBALS['_LANG']['delete_delay_info_success'], 0, $lnk);
                    }
                    /* 记录日志 */
                    admin_log('', 'batch_trash', 'users_real');
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 0未审核 1审核通过 2审核未通过

                    // 查询是否有已审核的订单
                    $sql = "SELECT od.review_status,od.apply_day,o.order_sn ,od.delayed_id FROM " . $this->dsc->table('order_delayed') . "AS od"
                        . " LEFT JOIN " . $this->dsc->table('order_info') . "AS o ON od.order_id = o.order_id WHERE od.delayed_id " . db_create_in($delay_id_arr);
                    $ald_review = $this->db->getAll($sql);
                    $msj_order = '';
                    foreach ($ald_review as $key => $value) {
                        //判断是否审核通过
                        if ($value['review_status'] > 0) {
                            $id_key = array_search($value['delayed_id'], $delay_id_arr);
                            unset($delay_id_arr[$id_key]);
                        }
                        //判断是否设置天数
                        if ($value['apply_day'] == 0 && $review_status == 1) {
                            if ($msj_order) {
                                $msj_order .= "," . $value['order_sn'];
                            } else {
                                $msj_order = $value['order_sn'];
                            }
                            $id_key = array_search($value['delayed_id'], $delay_id_arr);
                            unset($delay_id_arr[$id_key]);
                        }
                    }

                    $time = gmtime();

                    $sql = "UPDATE " . $this->dsc->table('order_delayed') . " SET review_status = '$review_status', review_time = '$time', review_admin = '" . session('seller_id') . "' "
                        . " WHERE delayed_id " . db_create_in($delay_id_arr);

                    if ($this->db->query($sql)) {
                        // 更新订单表的确认收货天数
                        $sql = "SELECT order_id, apply_day FROM " . $this->dsc->table('order_delayed') . " WHERE delayed_id " . db_create_in($delay_id_arr);
                        $order_id_list = $this->db->getAll($sql);
                        foreach ($order_id_list as $key => $value) {
                            $sql = "UPDATE " . $this->dsc->table('order_info') . "SET auto_delivery_time=auto_delivery_time+'$value[apply_day]' WHERE order_id='$value[order_id]'";
                            $this->db->query($sql);
                        }

                        $lnk[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'order_delay.php?act=list'];
                        $message = $GLOBALS['_LANG']['delay_exam_state_set_success'];
                        if ($msj_order) {
                            $message = $message . "," . $GLOBALS['_LANG']['delay_time_max_large_0'][0] . $msj_order . $GLOBALS['_LANG']['delay_time_max_large_0'][1];
                        }
                        return sys_msg($message, 0, $lnk);
                    }
                }
            }
        }
        /*------------------------------------------------------ */
        //-- 修改申请天数
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_apply_day') {
            $check_auth = check_authz_json('order_delayed');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            if ($exc->edit("apply_day = '$val'", $id)) {
                clear_cache_files();
                return make_json_result(stripslashes($val));
            }
        }
    }
}
