<?php

namespace App\Modules\Seller\Controllers;

use Illuminate\Support\Str;

/**
 * 会员管理程序
 */
class WarehouseShippingModeController extends InitController
{
    public function index()
    {
        $this->smarty->assign('menu_select', ['action' => '11_system', 'current' => '09_warehouse_management']);
        /* ------------------------------------------------------ */
        //-- 用户帐号列表
        /* ------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {

            /* 检查权限 */
            admin_priv('warehouse_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            $shipping_id = isset($_REQUEST['shipping_id']) ? intval($_REQUEST['shipping_id']) : 0;
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $region_id = $id;

            if ($shipping_id == 0 || $region_id == 0) {
                if (session()->has('admin_shipping_id') && session()->has('admin_region_id')) {
                    $shipping_id = session('admin_shipping_id');
                    $region_id = session('admin_region_id');
                } else {
                    $shipping_id = 0;
                    $region_id = 0;
                }
            }

            $sql = "select shipping_name from " . $this->dsc->table('shipping') . " where shipping_id = '$shipping_id'";
            $ur_here = $this->db->getOne($sql);

            session([
                'admin_shipping_id' => $shipping_id,
                'admin_region_id' => $region_id
            ]);

            $this->smarty->assign('ur_here', $ur_here);

            $shipping_list = $this->shipping_mode_list($shipping_id, $region_id);

            $this->smarty->assign('shipping_list', $shipping_list['shipping_list']);
            $this->smarty->assign('filter', $shipping_list['filter']);
            $this->smarty->assign('record_count', $shipping_list['record_count']);
            $this->smarty->assign('page_count', $shipping_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="__TPL__/images/sort_desc.gif">');

            $sql = "select region_id from " . $this->dsc->table('region_warehouse') . " where regionId = '$region_id'";
            $regionId = $this->db->getOne($sql);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_shipping_add'], 'href' => 'warehouse.php?act=freight&id=' . $regionId]);


            $this->smarty->assign('current', 'warehouse');
            return $this->smarty->display('warehouse_shipping_mode_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- ajax返回用户列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $shipping_id = session('admin_shipping_id');
            $region_id = session('admin_region_id');

            $shipping_list = $this->shipping_mode_list($shipping_id, $region_id);

            $this->smarty->assign('shipping_list', $shipping_list['shipping_list']);
            $this->smarty->assign('filter', $shipping_list['filter']);
            $this->smarty->assign('record_count', $shipping_list['record_count']);
            $this->smarty->assign('page_count', $shipping_list['page_count']);

            $sort_flag = sort_flag($shipping_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $this->smarty->assign('current', 'warehouse');
            return make_json_result($this->smarty->fetch('warehouse_shipping_mode_list.dwt'), '', ['filter' => $shipping_list['filter'], 'page_count' => $shipping_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 添加会员帐号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'freight') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            /* 检查权限 */
            admin_priv('warehouse_manage');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $sql = "select s.*, wf.id, wf.configure, rw1.region_name as region_name1, rw2.region_name as region_name2 from " . $this->dsc->table('warehouse_freight') . " as wf" .
                " LEFT JOIN " . $this->dsc->table('shipping') . " as s ON wf.shipping_id = s.shipping_id" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " as rw1 ON wf.warehouse_id = rw1.region_id" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " as rw2 ON wf.region_id = rw2.regionId" .
                " where wf.id = '$id'";
            $row = $this->db->getRow($sql);

            $plugin = plugin_path('Shipping/' . Str::studly($row['shipping_code']) . '/config.php');
            include_once($plugin);

            $fields = unserialize($row['configure']);
            /* 如果配送方式支持货到付款并且没有设置货到付款支付费用，则加入货到付款费用 */
            if ($row['support_cod'] && $fields[count($fields) - 1]['name'] != 'pay_fee') {
                $fields[] = ['name' => 'pay_fee', 'value' => 0];
            }

            foreach ($fields as $key => $val) {
                /* 替换更改的语言项 */
                if ($val['name'] == 'basic_fee') {
                    $val['name'] = 'base_fee';
                }

                if ($val['name'] == 'item_fee') {
                    $item_fee = 1;
                }
                if ($val['name'] == 'fee_compute_mode') {
                    $this->smarty->assign('fee_compute_mode', $val['value']);
                    unset($fields[$key]);
                } else {
                    $fields[$key]['name'] = $val['name'];
                    $fields[$key]['label'] = $GLOBALS['_LANG'][$val['name']];
                }
            }

            if (empty($item_fee)) {
                $field = ['name' => 'item_fee', 'value' => '0', 'label' => empty($GLOBALS['_LANG']['item_fee']) ? '' : $GLOBALS['_LANG']['item_fee']];
                array_unshift($fields, $field);
            }

            $this->smarty->assign('fields', $fields);
            $this->smarty->assign('region_name1', $row['region_name1']);
            $this->smarty->assign('region_name2', $row['region_name2']);
            $this->smarty->assign('shipping_area', $row);
            $this->smarty->assign('form_action', 'update');


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_shipping_edit']);
            $this->smarty->assign('current', 'warehouse');
            return $this->smarty->display('warehouse_shipping_mode_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 更新用户帐号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('warehouse_manage');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $sql = "select shipping_id from " . $this->dsc->table('warehouse_freight') . " where id = '$id'";
            $shipping = $this->db->getOne($sql);

            $shipping_code = $this->db->getOne("SELECT shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id='$shipping'");

            $plugin = plugin_path('Shipping/' . Str::studly($shipping_code) . '/config.php');

            if (!file_exists($plugin)) {
                return sys_msg($GLOBALS['_LANG']['not_find_plugin'], 1);
            } else {
                $modules = include_once($plugin);
            }

            $config = [];
            if ($modules['configure']) {
                foreach ($modules['configure'] as $key => $val) {
                    $config[$key]['name'] = $val['name'];
                    $config[$key]['value'] = $_POST[$val['name']];
                }
            }

            $count = count($config);
            $config[$count]['name'] = 'free_money';
            $config[$count]['value'] = empty($_POST['free_money']) ? '' : $_POST['free_money'];
            $count++;
            $config[$count]['name'] = 'fee_compute_mode';
            $config[$count]['value'] = empty($_POST['fee_compute_mode']) ? '' : $_POST['fee_compute_mode'];
            if ($modules['cod']) {
                $count++;
                $config[$count]['name'] = 'pay_fee';
                $config[$count]['value'] = make_semiangle(empty($_POST['pay_fee']) ? '' : $_POST['pay_fee']);
            }

            $sql = "UPDATE " . $this->dsc->table('warehouse_freight') .
                " SET configure='" . serialize($config) . "' " .
                "WHERE id='$id'";

            $this->db->query($sql);

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse_shipping_mode.php?act=freight&id=' . $id];
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 批量删除会员帐号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove') {
            /* 检查权限 */
            admin_priv('warehouse_manage');

            if (isset($_POST['checkboxes'])) {
                get_freight_batch_remove($_POST['checkboxes']);

                $count = count($_POST['checkboxes']);

                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse_shipping_mode.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_remove_success'], $count), 0, $lnk);
            } else {
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse_shipping_mode.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_user'], 0, $lnk);
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('warehouse_manage');

            $sql = "DELETE FROM " . $this->dsc->table('warehouse_freight') . " WHERE id = '" . intval($_GET['id']) . "'";
            $this->db->query($sql);

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse_shipping_mode.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['delete_success_alt'], 'warehouse_manage'), 0, $link);
        }
    }

    /**
     *  返回用户列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function shipping_mode_list($shipping_id, $region_id)
    {

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] > 0) {
            $ru_id = $adminru['ru_id'];
        } else {
            $ru_id = 0;
        }

        $ruCat = " and wf.user_id = '$ru_id' ";
        //ecmoban模板堂 --zhuo end

        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            }
            $filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
            $filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $ex_where = ' WHERE 1 ';

            $ex_where .= $ruCat;

            $ex_where .= " AND wf.region_id = '$region_id' AND wf.shipping_id = '$shipping_id' group by wf.id";

            $sql = "SELECT wf.id, rw1.region_name as region_name1, rw2.region_name as region_name2 " .
                " FROM " . $this->dsc->table('warehouse_freight') . " AS wf" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " as rw1 ON wf.warehouse_id = rw1.region_id" .
                " LEFT JOIN " . $this->dsc->table('shipping') . " as s ON wf.shipping_id = s.shipping_id" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " as rw2 ON wf.region_id = rw2.regionId" .
                $ex_where;
            $filter['record_count'] = count($this->db->getAll($sql));

            /* 分页大小 */
            $filter = page_and_size($filter);
            $sql = "SELECT wf.id, wf.shipping_id, rw2.region_id, rw1.region_name as region_name1, rw2.region_name as region_name2 " .
                " FROM " . $this->dsc->table('warehouse_freight') . " AS wf" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " as rw1 ON wf.warehouse_id = rw1.region_id" .
                " LEFT JOIN " . $this->dsc->table('shipping') . " as s ON wf.shipping_id = s.shipping_id" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " as rw2 ON wf.region_id = rw2.regionId" .
                $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $shipping_list = $this->db->getAll($sql);

        $count = count($shipping_list);
        for ($i = 0; $i < $count; $i++) {
            $user_list[$i]['region_name1'] = $shipping_list[$i]['region_name1'];
            $user_list[$i]['region_name2'] = $shipping_list[$i]['region_name2'];
        }

        $arr = ['shipping_list' => $shipping_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
