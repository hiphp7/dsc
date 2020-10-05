<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Models\RegionWarehouse;
use App\Repositories\Common\BaseRepository;
use Illuminate\Support\Str;

/**
 * 地区列表管理文件
 */
class WarehouseController extends InitController
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        $exc = new Exchange($this->dsc->table('region_warehouse'), $this->db, 'region_id', 'region_name');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "warehouse");
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        $adminru = get_admin_ru_id();

        $this->smarty->assign('menu_select', ['action' => '11_system', 'current' => '09_warehouse_management']);
        /* ------------------------------------------------------ */
        //-- 列出某地区下的所有地区列表
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('warehouse_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);

            /* 取得参数：上级地区id */
            $region_id = empty($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);
            $this->smarty->assign('parent_id', $region_id);

            $regionWarehouse = RegionWarehouse::select('region_type', 'region_name', 'parent_id')
                ->where('region_id', $region_id);
            $regionWarehouse = $this->baseRepository->getToArrayFirst($regionWarehouse);

            /* 取得列表显示的地区的类型 */
            if ($region_id == 0) {
                $region_type = 0;
            } else {
                $regionWarehouse['region_type'] = $regionWarehouse['region_type'] ?? 0;
                $region_type = $regionWarehouse['region_type'] + 1;
            }

            $this->smarty->assign('region_type', $region_type);

            /* 获取地区列表 */
            $list = $this->getWarehouseList($region_id, $region_type);
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('region_arr', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('freight_model', $GLOBALS['_CFG']['freight_model']);

            /* 当前的地区名称 */
            if ($region_id > 0) {
                $area_name = $regionWarehouse['region_name'] ?? 0;
                $area = '[ ' . $area_name . ' ] ';
            } else {
                $area = $GLOBALS['_LANG']['country'];
            }

            $this->smarty->assign('area_here', $area);

            /* 返回上一级的链接 */
            if ($region_id > 0) {
                $parent_id = $regionWarehouse['parent_id'] ?? 0;
                $action_link = ['text' => $GLOBALS['_LANG']['back_page'], 'href' => 'warehouse.php?act=list&pid=' . $parent_id, 'class' => 'icon-reply'];
            } else {
                $action_link = '';
            }
            $this->smarty->assign('action_link', $action_link);

            /* 赋值模板显示 */

            $lang_area_list = $GLOBALS['_LANG']['05_area_list_01'];

            if ($region_id > 0) {
                $lang_area_list .= '&nbsp;&nbsp;--&nbsp;&nbsp;' . $area;
            }
            $this->smarty->assign('ur_here', $lang_area_list);

            $this->smarty->assign('current', 'warehouse');
            return $this->smarty->display('warehouse_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $region_id = empty($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);

            $regionWarehouse = RegionWarehouse::select('region_type', 'region_name', 'parent_id')
                ->where('region_id', $region_id);
            $regionWarehouse = $this->baseRepository->getToArrayFirst($regionWarehouse);

            /* 取得列表显示的地区的类型 */
            if ($region_id == 0) {
                $region_type = 0;
            } else {
                $regionWarehouse['region_type'] = $regionWarehouse['region_type'] ?? 0;
                $region_type = $regionWarehouse['region_type'] + 1;
            }

            /* 获取地区列表 */
            $list = $this->getWarehouseList($region_id, $region_type);
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('region_arr', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('freight_model', $GLOBALS['_CFG']['freight_model']);

            /* 当前的地区名称 */
            if ($region_id > 0) {
                $area_name = $exc->get_name($region_id);
                $area = '[ ' . $area_name . ' ] ';
            } else {
                $area = $GLOBALS['_LANG']['country'];
            }
            $this->smarty->assign('area_here', $area);

            return make_json_result(
                $this->smarty->fetch('warehouse_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //-- �        �送方式列表 by wu
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ship_list') {
            /* 检查权限 */
            admin_priv('warehouse_manage');
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['09_warehouse_management'], 'href' => 'warehouse.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            /* 获取商家设置的配送方式 by wu */
            $sql = " select ru_id, shipping_id from " . $this->dsc->table("seller_shopinfo") . " where ru_id='" . $adminru['ru_id'] . "' ";
            $seller_shopinfo = $this->db->getRow($sql);
            $this->smarty->assign('seller_shopinfo', $seller_shopinfo);

            //获取配送方式列表
            $shipping_list = warehouse_shipping_list();
            foreach ($shipping_list as $key => $val) {
                $sql = "SELECT shipping_desc, insure, support_cod FROM " . $this->dsc->table('shipping') . " WHERE shipping_id = '$val[shipping_id]' ";
                $shipping_info = $this->db->getRow($sql);
                $shipping_list[$key]['shipping_desc'] = $shipping_info['shipping_desc'];
            }
            $this->smarty->assign('shipping_list', $shipping_list);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['warehouse_shipping_tpl']);
            $this->smarty->assign('current', 'warehouse');
            return $this->smarty->display('warehouse_shipping_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 删除运费模板 by wu
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_tpl') {
            $id = intval($_REQUEST['id']);
            $sql = "DELETE FROM " . $this->dsc->table('warehouse_freight_tpl') . " WHERE id='$id'";
            if ($this->db->query($sql)) {
                $data = $GLOBALS['_LANG']['delete_success_alt'];
            } else {
                $data = $GLOBALS['_LANG']['js_languages']['confirm_delete_fail'];
            }
            return $data;
        } elseif ($_REQUEST['act'] == 'multi_remove') {
            $ids = implode(',', $_REQUEST['checkboxes']);
            $sql = "DELETE FROM " . $this->dsc->table('warehouse_freight_tpl') . " WHERE id in (" . $ids . ")";
            if ($this->db->query($sql)) {
                $data = $GLOBALS['_LANG']['remove_success'];
            } else {
                $data = $GLOBALS['_LANG']['remove_fail'];
            }
            $links[0] = ['href' => 'warehouse.php?act=tpl_list&shipping_id=' . intval($_REQUEST['shipping_id']), 'text' => $GLOBALS['_LANG']['go_back']];
            return sys_msg($data, 0, $links);
        }

        /* ------------------------------------------------------ */
        //-- 运费模板列表 by wu
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'tpl_list') {
            $shipping_id = intval($_REQUEST['shipping_id']);
            //上门取货
            $sql = "SELECT shipping_code,shipping_name FROM " . $this->dsc->table("shipping") . " WHERE shipping_id=" . $shipping_id;
            $shipping = $this->db->getRow($sql);
            $shipping_code = $shipping['shipping_code'];

            $list = get_ship_tpl_list($shipping_id, $adminru['ru_id']);

            $this->smarty->assign('areas', $list);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['warehouse_shipping_tpl_list'] . ' - ' . $shipping['shipping_name']);
            $this->smarty->assign('action_link2', ['href' => 'warehouse.php?act=ship_tpl&shipping_id=' . $shipping_id,
                'text' => $GLOBALS['_LANG']['new_warehouse_shipping_tpl']]);
            $this->smarty->assign('action_link', ['href' => 'warehouse.php?act=ship_list', 'text' => $GLOBALS['_LANG']['return_delivery_list']]);
            $this->smarty->assign('full_page', 1);


            $this->smarty->assign('current', 'warehouse');
            $this->smarty->assign('shipping_id', $shipping_id);
            $this->smarty->assign('shipping_code', $shipping_code);
            return $this->smarty->display('warehouse_shipping_tpl_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 运费模板编辑 by wu
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ship_tpl') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            $shipping_id = isset($_REQUEST['shipping_id']) ? $_REQUEST['shipping_id'] : 0;
            $this->smarty->assign('shipping_id', $shipping_id);
            $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            //$region_id = $id;
            //处理配置信息
            $sql = "SELECT a.shipping_name, a.shipping_code, a.support_cod, b.* " .
                " FROM " . $this->dsc->table('warehouse_freight_tpl') . " AS b " .
                " left join " . $this->dsc->table('shipping') . " AS a on a.shipping_id=b.shipping_id " .
                " WHERE b.id='" . $id . "' and b.shipping_id='" . $shipping_id . "' and b.user_id='" . $adminru['ru_id'] . "'";
            $row = $this->db->getRow($sql);

            if (!empty($row)) {
                $shipping_name = $row['shipping_name'];
                //插入一条记录

                /* 自提点信息 */
                if (!empty($row) && $row['shipping_code'] == "cac") {
                    $sql = "SELECT * FROM " . $this->dsc->table('shipping_point') . " WHERE shipping_area_id='{$row['shipping_area_id']}'";
                    $row['point'] = $this->db->getAll($sql);
                }

                $shipping_name = Str::studly($row['shipping_code']);
                $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');
                include_once($modules);

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
                $this->smarty->assign('shipping_area', $row);
            } else {
                $shipping = $this->db->getRow("SELECT shipping_name, shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id='$shipping_id'");
                $shipping_name = $shipping['shipping_name'];

                $modules = plugin_path('Shipping/' . Str::studly($row['shipping_code']) . '/config.php');
                $modules = include_once($modules);

                $fields = [];
                if ($modules['configure']) {
                    foreach ($modules['configure'] as $key => $val) {
                        $fields[$key]['name'] = $val['name'];
                        $fields[$key]['value'] = $val['value'];
                        $fields[$key]['label'] = $GLOBALS['_LANG'][$val['name']];
                    }
                }

                $count = count($fields);
                $fields[$count]['name'] = "free_money";
                $fields[$count]['value'] = "0";
                $fields[$count]['label'] = $GLOBALS['_LANG']["free_money"];

                /* 如果支持货到付款，则允许设置货到付款支付费用 */
                if ($modules['cod']) {
                    $count++;
                    $fields[$count]['name'] = "pay_fee";
                    $fields[$count]['value'] = "0";
                    $fields[$count]['label'] = $GLOBALS['_LANG']['pay_fee'];
                }

                $shipping_area['shipping_id'] = 0;
                $shipping_area['free_money'] = 0;
                $this->smarty->assign('shipping_area', ['shipping_id' => $_REQUEST['shipping_id'], 'shipping_code' => $shipping['shipping_code']]);
            }
            //处理配置信息

            /* 仓库运费模板 by wu */
            $this->smarty->assign('action_link', ['href' => 'warehouse.php?act=tpl_list&shipping_id=' . $shipping_id, 'text' => lang('seller/warehouse.back_models_list'), 'class' => 'icon-reply']);

            $warehouse_list = get_warehouse_list_goods();

            //设置仓库状态 by wu
            $sql = " SELECT warehouse_id from " . $this->dsc->table('warehouse_freight_tpl') . " where id='$id' and shipping_id='$shipping_id' and user_id='" . $adminru['ru_id'] . "' ";
            $warehouses = $this->db->getOne($sql);
            foreach ($warehouse_list as $key => $value) {
                if (!empty($warehouses)) {
                    if (in_array($value['region_id'], explode(',', $warehouses))) {
                        $warehouse_list[$key]['check_status'] = 1;
                    }
                }
            }
            $this->smarty->assign('warehouse_list', $warehouse_list);
            $this->smarty->assign('warehouse_count', count($warehouse_list) + 1); //每增加一个表单，值加1

            /* $sql = "select region_name from " .$this->dsc->table('region_warehouse'). " where region_id = '$region_id'";
              $region_name = $this->db->getOne($sql);

              $this->smarty->assign('region_name',  $region_name);
              $this->smarty->assign('region_id',  $region_id); */

            $this->smarty->assign('form_action', 'freight_tpl_insert');

            $shipping_list = warehouse_shipping_list();
            $this->smarty->assign('shipping_list', $shipping_list);

            /* $sql = "select regionId from " .$this->dsc->table('region_warehouse'). " where region_id = '$region_id'";
              $regionId = $this->db->getOne($sql);

              $freight_list = get_warehouse_freight_type($regionId);

              $this->smarty->assign('freight_list',  $freight_list);

              $this->smarty->assign('regionId',  $regionId); */

            /* 获得该区域下的所有地区 */
            $regions = [];

            $sql = " SELECT region_id from " . $this->dsc->table('warehouse_freight_tpl') . " where id='$id' and shipping_id='$shipping_id' and user_id='" . $adminru['ru_id'] . "' ";
            $region_list = $res = $this->db->getOne($sql);
            if (!empty($region_list)) {
                $sql = " SELECT region_id,region_name from " . $this->dsc->table('region') . " where region_id in (" . $region_list . ") ";
                $res = $this->db->query($sql);
                foreach ($res as $arr) {
                    $regions[$arr['region_id']] = $arr['region_name'];
                }
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['warehouse_shipping_tpl_list'] . ' - ' . $shipping_name);
            $this->smarty->assign('current', 'warehouse');
            $this->smarty->assign('fields', $fields);
            $this->smarty->assign('countries', get_regions());
            $this->smarty->assign('regions', $regions);
            return $this->smarty->display('warehouse_shipping_tpl_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 查询类目列表 by wu
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'freight_tpl_insert') {
            $warehouse_id = empty($_REQUEST['warehouse_id']) ? '' : implode(',', $_REQUEST['warehouse_id']);
            $shipping_id = empty($_REQUEST['shipping_id']) ? '' : intval($_REQUEST['shipping_id']);
            $tpl_name = empty($_REQUEST['tpl_name']) ? '' : trim($_REQUEST['tpl_name']);
            $id = empty($_REQUEST['id']) ? '' : intval($_REQUEST['id']);
            $rId = empty($_REQUEST['regions']) ? '' : implode(',', $_REQUEST['regions']);
            $regionId = $rId;

            if ($shipping_id == 0 || empty($tpl_name) || empty($warehouse_id) || empty($regionId)) {
                $add_to_mess = $GLOBALS['_LANG']['please_input_info_complete'];
                $add_edit = "act=ship_tpl&shipping_id=" . $shipping_id;
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse.php?' . $add_edit];
                return sys_msg($add_to_mess, 0, $link);
            } else {
                $add_to_mess = $GLOBALS['_LANG']['freight_add_success'];
            }

            $adminru = get_admin_ru_id();

            if (!empty($id)) {
                $where = " and id <> $id ";
            } else {
                $where = "";
            }

            $sql = "select warehouse_id,region_id from " . $this->dsc->table('warehouse_freight_tpl') . " where shipping_id = '$shipping_id' and user_id = '" . $adminru['ru_id'] . "'" . $where;
            $res = $this->db->getAll($sql);
            foreach ($res as $key => $val) {
                $warehouse_state = array_intersect(explode(',', $val['warehouse_id']), explode(',', $warehouse_id));
                $region_state = array_intersect(explode(',', $val['region_id']), explode(',', $rId));
                if ($warehouse_state && $region_state) {
                    $add_to_mess = $GLOBALS['_LANG']['tpl_arrive_area_exist'];
                    $add_edit = "act=tpl_list&shipping_id=" . $shipping_id;
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse.php?' . $add_edit];
                    return sys_msg($add_to_mess, 0, $link);
                }
            }

            $shipping_code = $this->db->getOne("SELECT shipping_code FROM " . $this->dsc->table('shipping') .
                " WHERE shipping_id='$shipping_id'");

            $modules = plugin_path('Shipping/' . Str::studly($shipping_code) . '/config.php');

            if (!file_exists($modules)) {
                return sys_msg($GLOBALS['_LANG']['not_find_plugin'], 1);
            } else {
                $modules = include_once($modules);
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
            /* 如果支持货到付款，则允许设置货到付款支付费用 */
            if ($modules['cod']) {
                $count++;
                $config[$count]['name'] = 'pay_fee';
                $config[$count]['value'] = make_semiangle(empty($_POST['pay_fee']) ? '' : $_POST['pay_fee']);
            }

            $other['tpl_name'] = $tpl_name;
            $other['warehouse_id'] = $warehouse_id;
            $other['shipping_id'] = $shipping_id;
            $other['region_id'] = $regionId;
            $other['configure'] = serialize($config);
            $other['user_id'] = $adminru['ru_id'];

            $sql = " select * from " . $this->dsc->table('warehouse_freight_tpl') . " where shipping_id='$shipping_id' and user_id='" . $adminru['ru_id'] . "' ";
            $tpl_status = $this->db->getRow($sql);
            if (empty($tpl_status) || empty($id)) {
                $this->db->autoExecute($this->dsc->table('warehouse_freight_tpl'), $other, 'INSERT');
                $add_to_mess = $GLOBALS['_LANG']['tpl_add_success'];
            } else {
                $this->db->autoExecute($this->dsc->table('warehouse_freight_tpl'), $other, 'UPDATE', ' id= ' . $id . ' and user_id = ' . $adminru['ru_id'] . ' and shipping_id= ' . $shipping_id);
                $add_to_mess = $GLOBALS['_LANG']['tpl_edit_success'];
            }

            $add_edit = "act=tpl_list&shipping_id=" . $shipping_id;
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse.php?' . $add_edit];
            return sys_msg($add_to_mess, 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 添加新的地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_area') {
            $check_auth = check_authz_json('warehouse_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $parent_id = intval($_POST['parent_id']);
            $region_name = json_str_iconv(trim($_POST['region_name']));
            $region_type = intval($_POST['region_type']);

            //ecmoban模板堂 --zhuo start
            $regionId = intval($_POST['regionId']);
            if ($regionId > 0) {
                $region_name = $this->get_region_name_area($regionId);
            }
            //ecmoban模板堂 --zhuo end

            if (empty($region_name)) {
                return make_json_error($GLOBALS['_LANG']['region_name_empty']);
            }

            /* 查看区域是否重复 */
            $sql = "select region_id from " . $this->dsc->table('region_warehouse') . " where regionId = '$regionId' ";
            $res = $this->db->getOne($sql);

            if ($res > 0 && $regionId) {
                return make_json_error($GLOBALS['_LANG']['region_name_exist']);
            } else {
                $sql = "select region_id from " . $this->dsc->table('region_warehouse') . " where region_name = '$region_name' AND region_type <> 1";
                $res = $this->db->getOne($sql);

                if ($res > 0) {
                    return make_json_error($GLOBALS['_LANG']['region_name_exist']);
                }
            }

            $sql = "INSERT INTO " . $this->dsc->table('region_warehouse') . " (regionId, parent_id, region_name, region_type) " .
                "VALUES ('$regionId', '$parent_id', '$region_name', '$region_type')";
            if ($this->db->query($sql, 'SILENT')) {
                admin_log($region_name, 'add', 'area');

                /* 获取地区列表 */
                $region_arr = area_warehouse_list($parent_id);
                $this->smarty->assign('region_arr', $region_arr);

                $adminru = get_admin_ru_id();
                $ruCat = '';
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('priv_ru', 1);
                } else {
                    $this->smarty->assign('priv_ru', 0);
                }

                $this->smarty->assign('region_type', $region_type);
                $this->smarty->assign('current', 'warehouse');
                return make_json_result($this->smarty->fetch('warehouse_list.dwt'));
            } else {
                return make_json_error($GLOBALS['_LANG']['add_area_error']);
            }
        }

        /* ------------------------------------------------------ */
        //-- 编辑区域名称
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_area_name') {
            $check_auth = check_authz_json('warehouse_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $region_name = json_str_iconv(trim($_POST['val']));

            if (empty($region_name)) {
                return make_json_error($GLOBALS['_LANG']['region_name_empty']);
            }

            $msg = '';

            /* 查看区域是否重复 */
            $parent_id = $exc->get_name($id, 'parent_id');
            if (!$exc->is_only('region_name', $region_name, $id, "parent_id = '$parent_id'")) {
                return make_json_error($GLOBALS['_LANG']['region_name_exist']);
            }

            if ($exc->edit("region_name = '$region_name'", $id)) {
                admin_log($region_name, 'edit', 'area');
                return make_json_result(stripslashes($region_name));
            } else {
                return make_json_error($this->db->error());
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除区域
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_area') {
            $check_auth = check_authz_json('warehouse_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['id']);

            $sql = "SELECT * FROM " . $this->dsc->table('region_warehouse') . " WHERE region_id = '$id'";
            $region = $this->db->getRow($sql);

            if ($region['parent_id'] > 0) {
                $area_name = $exc->get_name($region['parent_id']);
                $area = '[ ' . $area_name . ' ] ';
            } else {
                $area = $GLOBALS['_LANG']['country'];
            }
            $this->smarty->assign('area_here', $area);
            $this->smarty->assign('freight_model', $GLOBALS['_CFG']['freight_model']);

            $region_type = $region['region_type'];
            $delete_region[] = $id;
            $new_region_id = $id;
            if ($region_type < 6) {
                for ($i = 1; $i < 6 - $region_type; $i++) {
                    $new_region_id = $this->new_region_id($new_region_id);
                    if (count($new_region_id)) {
                        $delete_region = array_merge($delete_region, $new_region_id);
                    } else {
                        continue;
                    }
                }
            }
            $sql = "DELETE FROM " . $this->dsc->table("region_warehouse") . "WHERE region_id" . db_create_in($delete_region);
            $this->db->query($sql);
            if ($exc->drop($id)) {
                admin_log(addslashes($region['region_name']), 'remove', 'area');

                /* 获取地区列表 */
                $region_arr = area_warehouse_list($region['parent_id']);
                $this->smarty->assign('region_arr', $region_arr);
                $this->smarty->assign('region_type', $region['region_type']);

                //ecmoban模板堂 --zhuo start
                $adminru = get_admin_ru_id();
                $ruCat = '';
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('priv_ru', 1);
                } else {
                    $this->smarty->assign('priv_ru', 0);
                }
                //ecmoban模板堂 --zhuo end
                $this->smarty->assign('current', 'warehouse');
                return make_json_result($this->smarty->fetch('warehouse_list.dwt'));
            } else {
                return make_json_error($this->db->error());
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除区域
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'freight') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            /* 获取商家设置的配送方式 by wu */
            $sql = " select ru_id, shipping_id from " . $this->dsc->table("seller_shopinfo") . " where ru_id='" . $adminru['ru_id'] . "' ";
            $seller_shopinfo = $this->db->getRow($sql);
            $this->smarty->assign('seller_shopinfo', $seller_shopinfo);

            $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            $region_id = $id;

            $parent = $this->get_freight_warehouse_id($region_id);
            $parent = $this->get_parent_freight($parent);
            $parent = $this->array_switch($parent);
            $parent_id = $parent[0];

            $this->smarty->assign('parent_id', $parent_id);

            $warehouse_list = get_warehouse_list_goods();
            $this->smarty->assign('warehouse_list', $warehouse_list);

            $sql = "select region_name from " . $this->dsc->table('region_warehouse') . " where region_id = '$region_id'";
            $region_name = $this->db->getOne($sql);

            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('region_id', $region_id);

            $this->smarty->assign('form_action', 'freight_insert');

            $shipping_list = warehouse_shipping_list();
            $this->smarty->assign('shipping_list', $shipping_list);

            $sql = "select regionId from " . $this->dsc->table('region_warehouse') . " where region_id = '$region_id'";
            $regionId = $this->db->getOne($sql);

            $freight_list = get_warehouse_freight_type($regionId);

            $this->smarty->assign('freight_list', $freight_list);

            $this->smarty->assign('regionId', $regionId);


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['freight_guanli'] . '：' . $region_name);
            $this->smarty->assign('current', 'warehouse');
            return $this->smarty->display('warehouse_freight.dwt');
        } //查询类目列表
        elseif ($_REQUEST['act'] == 'freight_insert') {
            $return_data = empty($_REQUEST['return_data']) ? 0 : intval($_REQUEST['return_data']);
            $warehouse_id = empty($_REQUEST['warehouse_id']) ? 0 : intval($_REQUEST['warehouse_id']);
            $shipping_id = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $region_id = $id;
            $rId = empty($_REQUEST['rId']) ? 0 : intval($_REQUEST['rId']);
            $regionId = $rId;

            if ($shipping_id == 0) {
                $add_to_mess = $GLOBALS['_LANG']['js_languages']['jl_select_deliver'];
                $add_edit = "act=freight&region_id=" . $region_id;
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse.php?' . $add_edit];
                return sys_msg($add_to_mess, 0, $link);
            }

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            if ($adminru['ru_id'] > 0) {
                $ru_id = $adminru['ru_id'];
            } else {
                $ru_id = 0;
            }

            $ruCat = " AND user_id = '$ru_id'";
            //ecmoban模板堂 --zhuo end

            $shipping_code = $this->db->getOne("SELECT shipping_code FROM " . $this->dsc->table('shipping') .
                " WHERE shipping_id='$shipping_id'");
            $plugin = '../includes/modules/shipping/' . $shipping_code . ".php";

            if (!file_exists($plugin)) {
                return sys_msg($GLOBALS['_LANG']['not_find_plugin'], 1);
            } else {
                $set_modules = 1;
                include_once($plugin);
            }

            $config = [];
            foreach ($modules[0]['configure'] as $key => $val) {
                $config[$key]['name'] = $val['name'];
                $config[$key]['value'] = $_POST[$val['name']];
            }

            $count = count($config);
            $config[$count]['name'] = 'free_money';
            $config[$count]['value'] = empty($_POST['free_money']) ? '' : $_POST['free_money'];
            $count++;
            $config[$count]['name'] = 'fee_compute_mode';
            $config[$count]['value'] = empty($_POST['fee_compute_mode']) ? '' : $_POST['fee_compute_mode'];
            /* 如果支持货到付款，则允许设置货到付款支付费用 */
            if ($modules[0]['cod']) {
                $count++;
                $config[$count]['name'] = 'pay_fee';
                $config[$count]['value'] = make_semiangle(empty($_POST['pay_fee']) ? '' : $_POST['pay_fee']);
            }

            $sql = "select regionId from " . $this->dsc->table('region_warehouse') . " where regionId = '$regionId'";
            $regionId = $this->db->getOne($sql);

            $adminru = get_admin_ru_id();

            $other['warehouse_id'] = $warehouse_id;
            $other['shipping_id'] = $shipping_id;
            $other['region_id'] = $regionId;
            $other['configure'] = serialize($config);
            $other['user_id'] = $adminru['ru_id'];

            $sql = "SELECT id FROM " . $this->dsc->table('warehouse_freight') . " WHERE warehouse_id = '$warehouse_id' and shipping_id = '$shipping_id' and region_id = '$regionId'" . $ruCat;
            $id = $this->db->getOne($sql);

            if ($id) {
                $this->db->autoExecute($this->dsc->table('warehouse_freight'), $other, 'UPDATE', "id='$id'");
                $add_to_mess = $GLOBALS['_LANG']['freight_edit_success'];
            } else {
                $this->db->autoExecute($this->dsc->table('warehouse_freight'), $other, 'INSERT');
                $add_to_mess = $GLOBALS['_LANG']['freight_add_success'];
            }


            $add_edit = "act=freight&id=" . $region_id;
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'warehouse.php?' . $add_edit];
            return sys_msg($add_to_mess, 0, $link);
        } //查询运费模式
        elseif ($_REQUEST['act'] == 'get_freight_area') {
            $check_auth = check_authz_json('warehouse_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $shipping_id = !empty($_GET['shipping_id']) ? intval($_GET['shipping_id']) : 0;
            $warehouse_id = !empty($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;
            $region_id = !empty($_GET['region_id']) ? intval($_GET['region_id']) : 0;

            $sql = "SELECT s.*, wf.id, wf.configure, rw1.region_name as region_name1, rw2.region_name as region_name2 FROM " . $this->dsc->table('warehouse_freight') . " AS wf" .
                " LEFT JOIN " . $this->dsc->table('shipping') . " AS s ON wf.shipping_id = s.shipping_id" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " AS rw1 ON wf.warehouse_id = rw1.region_id" .
                " LEFT JOIN " . $this->dsc->table('region_warehouse') . " AS rw2 ON wf.region_id = rw2.regionId" .
                " WHERE wf.shipping_id = '$shipping_id' AND wf.warehouse_id = '$warehouse_id' AND wf.user_id = '" . $adminru['ru_id'] . "' AND wf.region_id = '$region_id'";

            $shipping = $this->db->getRow($sql);

            if ($shipping) {

                $shipping_name = Str::studly($shipping['shipping_code']);
                $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');

                if (file_exists($modules)) {
                    include_once($modules);
                }

                $fields = unserialize($shipping['configure']);

                /* 如果配送方式支持货到付款并且没有设置货到付款支付费用，则加入货到付款费用 */
                if ($shipping['support_cod'] && $fields[count($fields) - 1]['name'] != 'pay_fee') {
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

                $return_data = 1;
            } else {
                $sql = "SELECT shipping_name, shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id='$shipping_id'";
                $shipping = $this->db->getRow($sql);

                $shipping_name = Str::studly($shipping['shipping_code']);
                $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');

                if (file_exists($modules)) {
                    $modules = include_once($modules);
                }

                $fields = [];
                if ($modules['configure']) {
                    foreach ($modules['configure'] as $key => $val) {
                        $fields[$key]['name'] = $val['name'];
                        $fields[$key]['value'] = $val['value'];
                        $fields[$key]['label'] = $GLOBALS['_LANG'][$val['name']];
                    }
                }

                $count = count($fields);
                $fields[$count]['name'] = "free_money";
                $fields[$count]['value'] = "0";
                $fields[$count]['label'] = $GLOBALS['_LANG']["free_money"];

                // 如果支持货到付款，则允许设置货到付款支付费用
                if ($modules['cod']) {
                    $count++;
                    $fields[$count]['name'] = "pay_fee";
                    $fields[$count]['value'] = "0";
                    $fields[$count]['label'] = $GLOBALS['_LANG']['pay_fee'];
                }

                $return_data = 0;
            }

            $this->smarty->assign('shipping_area', ['shipping_id' => $_REQUEST['shipping_id'], 'shipping_code' => $shipping['shipping_code']]);
            $this->smarty->assign('fields', $fields);
            $this->smarty->assign('return_data', $return_data);

            return make_json_result($this->smarty->fetch('warehouse_freight_area.dwt'));
        }
    }

    private function getWarehouseList($region_id = 0, $region_type = 0)
    {
        $filter['region_id'] = isset($_REQUEST['region_id']) && !empty($_REQUEST['region_id']) ? intval($_REQUEST['region_id']) : $region_id;
        $filter['region_type'] = isset($_REQUEST['region_type']) && !empty($_REQUEST['region_type']) ? intval($_REQUEST['region_type']) : $region_type;
        $filter['pid'] = isset($_REQUEST['pid']) && !empty($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;

        $region = $count = RegionWarehouse::where('parent_id', $filter['region_id'])
            ->where('region_type', $filter['region_type']);

        $filter['record_count'] = $count->count();

        $region = $region->orderBy('region_id');

        /* 分页大小 */
        $filter = page_and_size($filter);

        if ($filter['start'] > 0) {
            $region = $region->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $region = $region->take($filter['page_size']);
        }

        $list = $this->baseRepository->getToArrayGet($region);

        $arr = ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    private function new_region_id($region_id)
    {
        $regions_id = [];
        if (empty($region_id)) {
            return $regions_id;
        }
        $sql = "SELECT region_id FROM " . $this->dsc->table("region_warehouse") . "WHERE parent_id " . db_create_in($region_id);
        $result = $this->db->getAll($sql);
        foreach ($result as $val) {
            $regions_id[] = $val['region_id'];
        }
        return $regions_id;
    }

    //查找出一级地区列表 值为1  ecs_region表
    private function get_region_type_area($type = 1)
    {
        $sql = "select region_id, region_name from " . $this->dsc->table('region') . " where region_type = '$type'";
        $res = $this->db->getAll($sql);

        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key] = $row;
            $region_id = get_table_date('region_warehouse', "regionId = '" . $row['region_id'] . "'", ['region_id'], 2);
            if ($region_id > 0) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    //查找仓库地区名称
    private function get_region_name_area($region_id = 0)
    {
        $sql = "select region_name from " . $this->dsc->table('region') . " where region_id = '$region_id'";
        return $this->db->getOne($sql);
    }

    //查找所属仓库 start
    private function get_freight_warehouse_id($region_id)
    {
        $sql = "select region_id, parent_id, region_name from " . $this->dsc->table('region_warehouse') . " where region_id = '$region_id'";
        $res = $this->db->getAll($sql);

        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key]['region_id'] = $row['region_id'];
            $arr[$key]['parent_id'] = $row['parent_id'];
            $arr[$key]['region_name'] = $row['region_name'];
            $arr[$key]['parent'] = $this->get_freight_warehouse_id($row['parent_id']);

            if ($arr[$key]['parent_id'] == 0) {
                $arr[$key]['parent'] = $row['region_id'];
            }
        }

        return $arr;
    }

    private function get_parent_freight($parent)
    {
        $arr = [];
        for ($i = 0; $i < count($parent); $i++) {
            if (is_array($parent[$i]['parent'])) {
                $arr[$i]['parent'] = $this->get_parent_freight($parent[$i]['parent']);
            } else {
                $arr[$i]['parent'] = $parent[$i]['parent'];
            }
        }

        return $arr;
    }

    //查找所属仓库 end

    /* * ***多维数组转换一维数组*************************** */
    private function array_switch($array)
    {
        static $result_array = [];
        if (count($array) == 0) {
            return false;
        }
        foreach ($array as $value) {
            if (is_array($value)) {
                $this->array_switch($value);
            } else {
                $result_array[] = $value;
            }
        }
        return $result_array;
    }
}
