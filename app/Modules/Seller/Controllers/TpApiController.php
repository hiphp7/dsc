<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Services\Message\TpApiManageService;

/**
 * 第三方服务
 */
class TpApiController extends InitController
{
    protected $tpApiManageService;

    public function __construct(
        TpApiManageService $tpApiManageService
    )
    {
        $this->tpApiManageService = $tpApiManageService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();


        //默认
        if (empty($_REQUEST['act'])) {
            return 'Error';
        } //快递鸟打印
        elseif ($_REQUEST['act'] == 'kdniao_print') {
            load_helper('order');
            load_helper('goods');

            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $order_ids = [];
            if (!empty($order_id)) {
                $order_ids[] = $order_id;
            }
            if (!empty($order_sn)) {
                $sql = " SELECT order_id FROM " . $this->dsc->table('order_info') . " WHERE order_sn " . db_create_in($order_sn);
                $ids = $this->db->getCol($sql);
                $order_ids = array_merge($order_ids, $ids);
            }

            $link[] = ['text' => $GLOBALS['_LANG']['close_window'], 'href' => 'javascript:window.close()'];

            //判断订单
            if (empty($order_ids)) {
                return sys_msg($GLOBALS['_LANG']['no_select_order'], 1, $link);
            }

            //判断快递是否一样
            $sql = " SELECT shipping_id FROM " . $this->dsc->table('order_info') . " WHERE order_id " . db_create_in($order_ids);
            $shipping_ids = $this->db->getCol($sql);
            $shipping_ids = array_unique($shipping_ids);
            if (count($shipping_ids) > 1) {
                return sys_msg($GLOBALS['_LANG']['select_express_same_batch_print'], 1, $link);
            }

            //处理数据
            $batch_html = [];
            $batch_error = [];
            if ($order_ids && $order_ids[0]) {
                $order_info = order_info($order_ids[0]);

                //识别快递
                $shipping_info = get_shipping_info($order_info['shipping_id'], $adminru['ru_id']);
                $shipping_spec = get_shipping_spec($shipping_info['shipping_code']);

                $GLOBALS['smarty']->assign('shipping_info', $shipping_info);
                $GLOBALS['smarty']->assign('shipping_spec', $shipping_spec);

                foreach ($order_ids as $order_id) {
                    $result = get_kdniao_print_content($order_id, $shipping_spec, $shipping_info);

                    //判断是否成功
                    if ($result["ResultCode"] != "100") {
                        $batch_error[] = $GLOBALS['_LANG']['04_order'] . "（" . $order_id . "）：" . $GLOBALS['_LANG']['dzmd_order_fail'] . "：{$result['Reason']}";
                        continue;
                    }

                    //输出打印模板
                    if (!empty($result['PrintTemplate'])) {
                        $batch_html[] = $result['PrintTemplate'];
                    } else {
                        $batch_error[] = $GLOBALS['_LANG']['04_order'] . "（" . $order_id . "）：" . $GLOBALS['_LANG']['no_print_tpl'];
                        continue;
                    }

                    //将物流单号填入系统
                    if (isset($result['Order']['LogisticCode'])) {
                        $sql = " UPDATE " . $this->dsc->table('order_info') . " SET invoice_no = '{$result['Order']['LogisticCode']}' WHERE order_id = '$order_id' ";
                        $this->db->query($sql);
                    }
                }
            }

            $this->smarty->assign('batch_html', $batch_html);
            $this->smarty->assign('batch_error', implode(',', $batch_error));
            $this->smarty->assign('kdniao_printer', get_table_date('seller_shopinfo', "ru_id='{$adminru['ru_id']}'", ['kdniao_printer'], 2));

            return $this->smarty->display('kdniao_print.dwt');
        }

        /*------------------------------------------------------ */
        //-- 电子面单列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'order_print_setting') {
            admin_priv('order_print_setting');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => 'order_print_setting']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_print_setting']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['order_print_setting_add'], 'href' => 'tp_api.php?act=order_print_setting_add']);
            $this->smarty->assign('full_page', 1);

            $print_setting = $this->get_order_print_setting($adminru['ru_id']);

            $this->smarty->assign('print_setting', $print_setting['list']);
            $this->smarty->assign('filter', $print_setting['filter']);
            $this->smarty->assign('record_count', $print_setting['record_count']);
            $this->smarty->assign('page_count', $print_setting['page_count']);

            $sort_flag = sort_flag($print_setting['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('order_print_setting.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_query') {
            $check_auth = check_authz_json('order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $print_setting = $this->get_order_print_setting($adminru['ru_id']);

            $this->smarty->assign('print_setting', $print_setting['list']);
            $this->smarty->assign('filter', $print_setting['filter']);
            $this->smarty->assign('record_count', $print_setting['record_count']);
            $this->smarty->assign('page_count', $print_setting['page_count']);

            $sort_flag = sort_flag($print_setting['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('order_print_setting.dwt'), '', ['filter' => $print_setting['filter'], 'page_count' => $print_setting['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_remove') {
            $check_auth = check_authz_json('order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $exc = new Exchange($this->dsc->table("order_print_setting"), $this->db, 'id', 'ru_id');
            $exc->drop($id);
            //clear_cache_files();

            $url = 'tp_api.php?act=order_print_setting_query&' . str_replace('act=order_print_setting_remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑打印机
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_order_printer') {
            $check_auth = check_authz_json('order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = trim($_POST['val']);

            $sql = " UPDATE " . $this->dsc->table('order_print_setting') . " SET printer = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
            $this->db->query($sql);

            //clear_cache_files();
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 编辑宽度
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_print_width') {
            $check_auth = check_authz_json('order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = trim($_POST['val']);

            $sql = " UPDATE " . $this->dsc->table('order_print_setting') . " SET width = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
            $this->db->query($sql);

            //clear_cache_files();
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 编辑打印机排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = trim($_POST['val']);

            $sql = " UPDATE " . $this->dsc->table('order_print_setting') . " SET sort_order = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
            $this->db->query($sql);

            //clear_cache_files();
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 切换默认
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_order_is_default') {
            $check_auth = check_authz_json('order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $sql = " UPDATE " . $this->dsc->table('order_print_setting') . " SET is_default = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
            $this->db->query($sql);

            if ($val) {
                $sql = " UPDATE " . $this->dsc->table('order_print_setting') . " SET is_default = '0' WHERE ru_id = '{$adminru['ru_id']}' AND id <> '$id' ";
                $this->db->query($sql);
            }

            //clear_cache_files();
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑电子面单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_add' || $_REQUEST['act'] == 'order_print_setting_edit') {
            admin_priv('order_print_setting');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => 'order_print_setting']);

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $print_size = get_table_date('order_print_size', "1", ['*'], 1);
            $this->smarty->assign('print_size', $print_size);
            if ($id > 0) {
                $print_setting = get_table_date('order_print_setting', "id='$id'", ['*']);
                $this->smarty->assign('print_setting', $print_setting);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_print_setting_edit']);
                $this->smarty->assign('form_action', 'order_print_setting_update');
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_print_setting_add']);
                $this->smarty->assign('form_action', 'order_print_setting_insert');
            }
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['order_print_setting'], 'href' => 'tp_api.php?act=order_print_setting']);


            return $this->smarty->display('order_print_setting_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑电子面单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_insert' || $_REQUEST['act'] == 'order_print_setting_update') {
            admin_priv('order_print_setting');

            $data = [];
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $data['ru_id'] = $adminru['ru_id'];
            $data['is_default'] = !empty($_REQUEST['is_default']) ? intval($_REQUEST['is_default']) : 0;
            $data['specification'] = !empty($_REQUEST['specification']) ? trim($_REQUEST['specification']) : '';
            $data['printer'] = !empty($_REQUEST['printer']) ? trim($_REQUEST['printer']) : '';
            $data['width'] = !empty($_REQUEST['width']) ? intval($_REQUEST['width']) : 0;
            if (empty($data['width'])) {
                $print_size = get_table_date('order_print_size', "specification='{$data['specification']}'", ['height', 'width']);
                $data['width'] = $print_size['width'];
            }

            /* 检查是否重复 */
            $sql = " SELECT id FROM " . $this->dsc->table('order_print_setting') . " WHERE ru_id = '{$adminru['ru_id']}' AND specification = '{$data['specification']}' AND id <> '$id' LIMIT 1 ";
            $is_only = $this->db->getOne($sql);
            if (!empty($is_only)) {
                return sys_msg($GLOBALS['_LANG']['specification_exist'], 1);
            }
            /* 插入、更新 */
            if ($id > 0) {
                $this->db->autoExecute($this->dsc->table('order_print_setting'), $data, 'UPDATE', "id = '$id'");
                $msg = $GLOBALS['_LANG']['edit_success'];
            } else {
                $this->db->autoExecute($this->dsc->table('order_print_setting'), $data, 'INSERT');
                $id = $this->db->insert_id();
                $msg = $GLOBALS['_LANG']['add_success'];
            }
            /* 默认设置 */
            if ($data['is_default']) {
                $this->db->autoExecute($this->dsc->table('order_print_setting'), ['is_default' => 0], 'UPDATE', "id <> '$id'");
            }

            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'tp_api.php?act=order_print_setting'];
            return sys_msg($msg, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 电子面单 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print') {
            /* 检查权限 */
            admin_priv('order_view');

            /* 打印数据 */
            $print_specification = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' AND is_default='1'", ["specification"], 2);
            if (empty($print_specification)) {
                $print_specification = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' ORDER BY sort_order, id", ["specification"], 2);
            }

            $print_size_info = get_table_date("order_print_size", "specification='$print_specification'", ["*"]);
            $print_size_list = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' ORDER BY sort_order, id", ["*"], 1);
            $print_spec_info = get_table_date("order_print_setting", "specification='$print_specification'", ["*"]);

            if (empty($print_size_list)) {
                $link[] = ['text' => $GLOBALS['_LANG']['back_set'], 'href' => 'tp_api.php?act=order_print_setting'];
                return sys_msg($GLOBALS['_LANG']['no_print_setting'], 1, $link);
            }

            $this->smarty->assign('print_specification', $print_specification);
            $this->smarty->assign('print_size_info', $print_size_info);
            $this->smarty->assign('print_size_list', $print_size_list);
            $this->smarty->assign('print_spec_info', $print_spec_info);

            /* 订单数据 */
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $order_type = empty($_REQUEST['order_type']) ? 'order' : trim($_REQUEST['order_type']);
            $action_id = get_table_date('admin_action', "action_code='supply_and_demand'", ['action_id'], 2); //判断是否安装供求模块
            if ($order_type == 'order' || empty($action_id)) {
                $table = $this->dsc->table('order_info');
            } else {
                $table = $this->dsc->table('wholesale_order_info');
            }
            $order_ids = [];
            if (!empty($order_id)) {
                $order_ids[] = $order_id;
            }
            if (!empty($order_sn)) {
                $sql = " SELECT order_id FROM " . $table . " WHERE order_sn " . db_create_in($order_sn);
                $ids = $this->db->getCol($sql);
                $order_ids = array_merge($order_ids, $ids);
            }

            $web_url = asset('assets/seller') . '/';
            $this->smarty->assign('web_url', $web_url);

            $this->smarty->assign('order_type', $order_type);

            $this->smarty->assign('shop_url', $this->dsc->seller_url());

            $sql = "SELECT value FROM " . $GLOBALS['dsc']->table('shop_config') . " WHERE code = 'order_print_logo'";
            $order_print_logo = strstr($GLOBALS['db']->getOne($sql), "images");
            $this->smarty->assign('order_print_logo', $this->dsc->seller_url() . SELLER_PATH . '/' . $order_print_logo);

            $part_html = [];
            foreach ($order_ids as $order_id) {
                $order_info = $this->tpApiManageService->printOrderInfo($order_id, $order_type);
                $this->smarty->assign('order_info', $order_info);
                $this->smarty->assign('order_sn', $order_info['order_sn']);
                $part_html[] = $this->smarty->fetch('library/order_print_part.lbi');
            }
            $this->smarty->assign('part_html', $part_html);

            /* 显示模板 */

            return $this->smarty->display('order_print.dwt');
        }

        /*------------------------------------------------------ */
        //-- 切换电子面单 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'change_order_print') {
            /* 检查权限 */
            $check_auth = check_authz_json('order_view');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 打印数据 */
            $print_specification = empty($_REQUEST['specification']) ? '' : trim($_REQUEST['specification']);

            $print_size_info = get_table_date("order_print_size", "specification='$print_specification'", ["*"]);
            $print_size_list = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' ORDER BY sort_order, id", ["*"], 1);
            $print_spec_info = get_table_date("order_print_setting", "specification='$print_specification'", ["*"]);

            $this->smarty->assign('print_specification', $print_specification);
            $this->smarty->assign('print_size_info', $print_size_info);
            $this->smarty->assign('print_size_list', $print_size_list);
            $this->smarty->assign('print_spec_info', $print_spec_info);

            /* 订单数据 */
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $order_type = empty($_REQUEST['order_type']) ? 'order' : trim($_REQUEST['order_type']);
            $action_id = get_table_date('admin_action', "action_code='supply_and_demand'", ['action_id'], 2); //判断是否安装供求模块
            if ($order_type == 'order' || empty($action_id)) {
                $table = $this->dsc->table('order_info');
            } else {
                $table = $this->dsc->table('wholesale_order_info');
            }
            $order_ids = [];
            if (!empty($order_id)) {
                $order_ids[] = $order_id;
            }
            if (!empty($order_sn)) {
                $sql = " SELECT order_id FROM " . $table . " WHERE order_sn " . db_create_in($order_sn);
                $ids = $this->db->getCol($sql);
                $order_ids = array_merge($order_ids, $ids);
            }

            $web_url = $this->dsc->url();
            $this->smarty->assign('web_url', $web_url);

            $this->smarty->assign('order_type', $order_type);

            $this->smarty->assign('shop_url', $this->dsc->seller_url());

            $sql = "SELECT value FROM " . $GLOBALS['dsc']->table('shop_config') . " WHERE code = 'order_print_logo'";
            $order_print_logo = strstr($GLOBALS['db']->getOne($sql), "images");
            $this->smarty->assign('order_print_logo', $this->dsc->seller_url() . SELLER_PATH . '/' . $order_print_logo);

            $part_html = [];
            foreach ($order_ids as $order_id) {
                $order_info = $this->tpApiManageService->printOrderInfo($order_id, $order_type);
                $this->smarty->assign('order_info', $order_info);
                $this->smarty->assign('order_sn', $order_info['order_sn']);
                $part_html[] = $this->smarty->fetch('library/order_print_part.lbi');
            }
            $this->smarty->assign('part_html', $part_html);

            /* 显示模板 */
            $content = $this->smarty->fetch('library/order_print.lbi');
            return make_json_result($content);
        }
    }

    /* 获取电子面单设置列表 */
    private function get_order_print_setting($ru_id)
    {
        /* 过滤查询 */
        $filter = [];

        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ops.sort_order' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1 ';

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $where .= " AND (ops.specification LIKE '%" . mysql_like_quote($filter['keyword']) . "%'" . " OR ops.printer LIKE '%" . mysql_like_quote($filter['keyword']) . "%'" . ")";
        }

        if ($ru_id > 0) {
            $where .= " AND ops.ru_id = '$ru_id' ";
        }

        /* 获得总记录数据 */
        $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('order_print_setting') . ' AS ops ' . $where;
        $filter['record_count'] = $this->db->getOne($sql);

        $filter = page_and_size($filter);

        /* 获得数据 */
        $arr = [];
        $sql = 'SELECT ops.* FROM ' . $this->dsc->table('order_print_setting') . 'AS ops ' .
            $where . 'ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];

        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        foreach ($res as $rows) {
            $arr[] = $rows;
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
