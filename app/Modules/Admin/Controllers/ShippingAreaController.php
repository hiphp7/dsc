<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use Illuminate\Support\Str;

/**
 * 配送区域管理程序
 */
class ShippingAreaController extends InitController
{
    public function index()
    {
        $exc = new Exchange($this->dsc->table('shipping_area'), $this->db, 'shipping_area_id', 'shipping_area_name');
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        $adminru = get_admin_ru_id();
        /*------------------------------------------------------ */
        //-- �        �送区域列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $shipping_id = intval($_REQUEST['shipping']);
            //上门取货
            $sql = "SELECT shipping_code FROM " . $this->dsc->table("shipping") . " WHERE shipping_id='$shipping_id'";
            $shipping_code = $this->db->getOne($sql);

            $list = $this->get_shipping_area_list($shipping_id, $adminru['ru_id']);

            /* 自提点名称 */
            if (!empty($list) && $shipping_code == "cac") {
                foreach ($list as $key => $val) {
                    $sql = "SELECT name FROM " . $this->dsc->table("shipping_point") . " WHERE shipping_area_id='" . $val['shipping_area_id'] . "'";
                    $list[$key]['name'] = $this->db->getAll($sql);
                }
            }
            $this->smarty->assign('areas', $list);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_shipping_list'] . ' - ' . $GLOBALS['_LANG']['shipping_area_list']);
            $this->smarty->assign('action_link', ['href' => 'shipping_area.php?act=add&shipping=' . $shipping_id,
                'text' => $GLOBALS['_LANG']['new_area']]);
            $this->smarty->assign('action_link1', ['href' => 'shipping.php?act=list', 'text' => $GLOBALS['_LANG']['area_shipping']]);
            $this->smarty->assign('full_page', 1);


            $this->smarty->assign('shipping_code', $shipping_code);
            return $this->smarty->display('shipping_area_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 新建送区域
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' && !empty($_REQUEST['shipping'])) {
            admin_priv('shiparea_manage');

            $shipping = $this->db->getRow("SELECT shipping_name, shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id='$_REQUEST[shipping]'");

            $modules = include_once(plugin_path('Shipping/' . Str::studly($shipping['shipping_code']) . '/config.php'));

            $fields = [];
            if (isset($modules['configure']) && $modules['configure']) {
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

            $this->smarty->assign('ur_here', $shipping['shipping_name'] . ' - ' . $GLOBALS['_LANG']['new_area']);
            $this->smarty->assign('shipping_area', ['shipping_id' => $_REQUEST['shipping'], 'shipping_code' => $shipping['shipping_code']]);
            $this->smarty->assign('fields', $fields);
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('countries', get_regions());
            $this->smarty->assign('province_all', get_regions(1, 1));
            $this->smarty->assign('default_country', $GLOBALS['_CFG']['shop_country']);

            return $this->smarty->display('shipping_area_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('shiparea_manage');

            $shipping_id = isset($_POST['shipping']) && !empty($_POST['shipping']) ? intval($_POST['shipping']) : 0;

            /* 检查同类型的配送方式下有没有重名的配送区域 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table("shipping_area") .
                " WHERE shipping_id='$_POST[shipping]' AND shipping_area_name='$_POST[shipping_area_name]' and ru_id='" . $adminru['ru_id'] . "'";
            if ($this->db->getOne($sql) > 0) {
                return sys_msg($GLOBALS['_LANG']['repeat_area_name'], 1);
            } else {
                $shipping_code = $this->db->getOne("SELECT shipping_code FROM " . $this->dsc->table('shipping') .
                    " WHERE shipping_id='" . intval($_POST['shipping']) . "'");

                $shipping_name = Str::studly($shipping_code);
                $plugin = plugin_path('Shipping/' . $shipping_name . '/config.php');

                if (!file_exists($plugin)) {
                    return sys_msg($GLOBALS['_LANG']['not_find_plugin'], 1);
                } else {
                    $modules = include_once($plugin);
                }

                $config = [];
                if ($modules) {
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
                if (isset($modules['cod']) && $modules['cod']) {
                    $count++;
                    $config[$count]['name'] = 'pay_fee';
                    $config[$count]['value'] = make_semiangle(empty($_POST['pay_fee']) ? '' : $_POST['pay_fee']);
                }

                $sql = "INSERT INTO " . $this->dsc->table('shipping_area') .
                    " (shipping_area_name, shipping_id, configure,ru_id) " .
                    "VALUES" .
                    " ('$_POST[shipping_area_name]', '$_POST[shipping]', '" . serialize($config) . "','" . $adminru['ru_id'] . "')";

                $this->db->query($sql);

                $new_id = $this->db->insert_Id();

                if ($shipping_code == "cac") {
                    //上门取货添加所辖区域
                    $district = isset($_POST['district']) ? intval($_POST['district']) : 0;

                    if ($district == 0) {
                        return sys_msg($GLOBALS['_LANG']['select_jurisdiction_region'], 1);
                    }

                    $sql = "INSERT INTO " . $this->dsc->table('area_region') . " (shipping_area_id, region_id) VALUES ('$new_id', '$district')";
                    $this->db->query($sql);
                } else {
                    /* 添加选定的城市和地区 */
                    if (isset($_POST['regions']) && is_array($_POST['regions'])) {
                        foreach ($_POST['regions'] as $key => $val) {
                            $sql = "INSERT INTO " . $this->dsc->table('area_region') . " (shipping_area_id, region_id,ru_id) VALUES ('$new_id', '$val','" . $adminru['ru_id'] . "')";
                            $this->db->query($sql);
                        }
                    }
                }

                /* 自提点名称，地址，电话 */
                $point_name = isset($_POST['point_name']) ? $_POST['point_name'] : [];
                $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : [];
                $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : [];
                $address = isset($_POST['address']) ? $_POST['address'] : [];
                $anchor = isset($_POST['anchor']) ? $_POST['anchor'] : [];
                $line = isset($_POST['line']) ? $_POST['line'] : [];

                if ($point_name) {
                    foreach ($point_name as $key => $val) {
                        if (empty($val)) {
                            continue;
                        }

                        $upload = [
                            'name' => $_FILES['img_url']['name'][$key],
                            'type' => $_FILES['img_url']['type'][$key],
                            'tmp_name' => $_FILES['img_url']['tmp_name'][$key],
                            'size' => $_FILES['img_url']['size'][$key],
                        ];
                        if (isset($_FILES['img_url']['error'])) {
                            $upload['error'] = $_FILES['img_url']['error'][$key];
                        }

                        $map_img = $image->upload_image($upload, 'map_img');

                        $sql = "INSERT INTO " . $this->dsc->table('shipping_point') . " (shipping_area_id, name, user_name, mobile,address,img_url,anchor,line) " .
                            " VALUES ('{$new_id}', '{$val}' , '{$user_name[$key]}' ,'{$mobile[$key]}' , '{$address[$key]}', '{$map_img}', '{$anchor[$key]}', '{$line[$key]}')";
                        $this->db->query($sql);
                    }

                    admin_log($_POST['shipping_area_name'], 'add', 'shipping_area');
                }

                $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'shipping_area.php?act=list&shipping=' . $shipping_id];
                $lnk[] = ['text' => $GLOBALS['_LANG']['add_continue'], 'href' => 'shipping_area.php?act=add&shipping=' . $shipping_id];
                return sys_msg($GLOBALS['_LANG']['add_area_success'], 0, $lnk, true, true);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑�        �送区域
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('shiparea_manage');

            $sql = "SELECT a.shipping_name, a.shipping_code, a.support_cod, b.* " .
                "FROM " . $this->dsc->table('shipping') . " AS a, " . $this->dsc->table('shipping_area') . " AS b " .
                "WHERE b.shipping_id=a.shipping_id AND b.shipping_area_id='$_REQUEST[id]' and b.ru_id='" . $adminru['ru_id'] . "'";
            $row = $this->db->getRow($sql);

            /* 自提点信息 */
            if (!empty($row) && $row['shipping_code'] == "cac") {
                $sql = "SELECT * FROM " . $this->dsc->table('shipping_point') . " WHERE shipping_area_id='{$row['shipping_area_id']}'";
                $row['point'] = $this->db->getAll($sql);
                $row['count_point'] = count($row['point']);

                //格式化地图图片
                foreach ($row['point'] as $key => $val) {
                    $row['point'][$key]['img_url'] = get_image_path($val['img_url']);
                }
            }

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

            /* 获得该区域下的所有地区 */
            $regions = [];

            $sql = "SELECT a.region_id, r.region_name " .
                "FROM " . $this->dsc->table('area_region') . " AS a, " . $this->dsc->table('region') . " AS r " .
                "WHERE r.region_id=a.region_id AND a.shipping_area_id='$_REQUEST[id]' and a.ru_id='" . $adminru['ru_id'] . "'";
            $res = $this->db->query($sql);
            foreach ($res as $arr) {
                $regions[$arr['region_id']] = $arr['region_name'];
            }
            //省份城市区域---上门取货
            $sql = "SELECT region_id FROM " . $this->dsc->table('area_region') . " WHERE shipping_area_id = '$_REQUEST[id]'";
            $region_id = $this->db->getOne($sql);
            if ($region_id && $row['shipping_code'] == "cac") {
                //区域
                $sql = "SELECT * FROM " . $this->dsc->table('region') . " WHERE region_id = '$region_id'";
                $district = $this->db->getRow($sql);

                $sql = "SELECT * FROM " . $this->dsc->table('region') . " WHERE parent_id = '$district[parent_id]'";
                $district_all = $this->db->getAll($sql);
                //城市
                $sql = "SELECT * FROM " . $this->dsc->table('region') . " WHERE region_id = '$district[parent_id]'";
                $city = $this->db->getRow($sql);

                $sql = "SELECT * FROM " . $this->dsc->table('region') . " WHERE parent_id = '$city[parent_id]'";
                $city_all = $this->db->getAll($sql);
                //省份
                $sql = "SELECT * FROM " . $this->dsc->table('region') . " WHERE region_id = '$city[parent_id]'";
                $province = $this->db->getRow($sql);

                $sql = "SELECT * FROM " . $this->dsc->table('region') . " WHERE parent_id = '$province[parent_id]'";
                $province_all = $this->db->getAll($sql);
            }

            $this->smarty->assign('ur_here', $row['shipping_name'] . ' - ' . $GLOBALS['_LANG']['edit_area']);
            $this->smarty->assign('id', $_REQUEST['id']);
            $this->smarty->assign('fields', $fields);
            $this->smarty->assign('shipping_area', $row);
            $this->smarty->assign('regions', $regions);
            $this->smarty->assign('form_action', 'update');
            $this->smarty->assign('countries', get_regions());
            $this->smarty->assign('district', $district);
            $this->smarty->assign('district_all', $district_all);
            $this->smarty->assign('city', $city);
            $this->smarty->assign('city_all', $city_all);
            $this->smarty->assign('province', $province);
            $this->smarty->assign('province_all', $province_all);
            $this->smarty->assign('default_country', 1);
            return $this->smarty->display('shipping_area_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            admin_priv('shiparea_manage');

            $shipping_area_name = isset($_POST['shipping_area_name']) && !empty($_POST['shipping_area_name']) ? addslashes($_POST['shipping_area_name']) : '';
            $shipping_area_id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $shipping_id = isset($_POST['shipping']) && !empty($_POST['shipping']) ? intval($_POST['shipping']) : 0;

            /* 检查同类型的配送方式下有没有重名的配送区域 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table("shipping_area") .
                " WHERE shipping_id='$shipping_id' AND " .
                "shipping_area_name='$shipping_area_name' AND " .
                "shipping_area_id <> '$shipping_area_id' and ru_id='" . $adminru['ru_id'] . "'";
            if ($this->db->getOne($sql) > 0) {
                return sys_msg($GLOBALS['_LANG']['repeat_area_name'], 1);
            } else {
                $shipping_code = $this->db->getOne("SELECT shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id='" . intval($_POST['shipping']) . "'");
                $shipping_name = Str::studly($shipping_code);
                $plugin = plugin_path('Shipping/' . $shipping_name . '/config.php');

                $modules = [];
                if (!file_exists($plugin)) {
                    return sys_msg($GLOBALS['_LANG']['not_find_plugin'], 1);
                } else {
                    $modules = include_once($plugin);
                }

                $config = [];
                if ($modules) {
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
                if (isset($modules['cod']) && $modules['cod']) {
                    $count++;
                    $config[$count]['name'] = 'pay_fee';
                    $config[$count]['value'] = make_semiangle(empty($_POST['pay_fee']) ? '' : $_POST['pay_fee']);
                }

                $sql = "UPDATE " . $this->dsc->table('shipping_area') .
                    " SET shipping_area_name='$shipping_area_name', " .
                    "configure='" . serialize($config) . "' " .
                    "WHERE shipping_area_id='$shipping_area_id' and ru_id='" . $adminru['ru_id'] . "'";

                $this->db->query($sql);

                /* 自提点名称，地址，电话 */
                $point_id = isset($_POST['point_id']) && !empty($_POST['point_id']) ? addslashes_deep($_POST['point_id']) : [];
                $point_name = isset($_POST['point_name']) ? $_POST['point_name'] : [];
                $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : [];
                $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : [];
                $address = isset($_POST['address']) ? $_POST['address'] : [];
                $old_map_img = isset($_POST['map_img']) ? $_POST['map_img'] : [];
                $anchor = isset($_POST['anchor']) ? $_POST['anchor'] : [];
                $line = isset($_POST['line']) ? $_POST['line'] : [];

                if ($point_name && $mobile && $address) {
                    foreach ($point_name as $key => $val) {
                        if (empty($val)) {
                            continue;
                        }

                        $upload = [
                            'name' => $_FILES['img_url']['name'][$key],
                            'type' => $_FILES['img_url']['type'][$key],
                            'tmp_name' => $_FILES['img_url']['tmp_name'][$key],
                            'size' => $_FILES['img_url']['size'][$key],
                        ];
                        if (isset($_FILES['img_url']['error'])) {
                            $upload['error'] = $_FILES['img_url']['error'][$key];
                        }

                        $map_img = $image->upload_image($upload, 'map_img');
                        if (!$map_img && $old_map_img[$key]) {
                            $map_img = $old_map_img[$key];
                        }
                        if ($_POST['point_id'][$key]) {
                            $sql = "UPDATE " . $this->dsc->table('shipping_point') . "SET name='$point_name[$key]' , user_name='$user_name[$key]' ," .
                                " mobile='$mobile[$key]' , address='$address[$key]', img_url='$map_img', anchor='$anchor[$key]', line='$line[$key]'" .
                                "WHERE id='$point_id[$key]'";
                        } else {
                            $sql = "INSERT INTO " . $this->dsc->table('shipping_point') . " (shipping_area_id, name, user_name, mobile, address, img_url, anchor, line) " .
                                " VALUES ('$shipping_area_id', '$point_name[$key]' , '$user_name[$key]' ,'$mobile[$key]' , '$address[$key]', '$map_img', '$anchor[$key]', '$line[$key]')";
                        }

                        $this->db->query($sql);
                    }
                }

                admin_log($_POST['shipping_area_name'], 'edit', 'shipping_area');

                /* 过滤掉重复的region */
                $selected_regions = [];
                if (isset($_POST['regions'])) {
                    foreach ($_POST['regions'] as $region_id) {
                        $selected_regions[$region_id] = $region_id;
                    }
                }

                // 查询所有区域 region_id => parent_id
                $sql = "SELECT region_id, parent_id FROM " . $this->dsc->table('region');
                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    $region_list[$row['region_id']] = $row['parent_id'];
                }

                // 过滤掉上级存在的区域
                foreach ($selected_regions as $region_id) {
                    $id = $region_id;
                    while ($region_list[$id] != 0) {
                        $id = $region_list[$id];
                        if (isset($selected_regions[$id])) {
                            unset($selected_regions[$region_id]);
                            break;
                        }
                    }
                }

                /* 清除原有的城市和地区 */
                $this->db->query("DELETE FROM " . $this->dsc->table("area_region") . " WHERE shipping_area_id='$shipping_area_id' and ru_id='" . $adminru['ru_id'] . "'");

                if ($shipping_code == "cac") {
                    //上门取货添加所辖区域
                    $district = isset($_POST['district']) ? intval($_POST['district']) : 0;

                    if ($district == 0) {
                        return sys_msg(lang('admin/shipping_area.selection_area'), 1);
                    }

                    $sql = "INSERT INTO " . $this->dsc->table('area_region') . " (shipping_area_id, region_id) VALUES ('$shipping_area_id', '$district')";
                    $this->db->query($sql);
                } else {
                    /* 添加选定的城市和地区 */
                    foreach ($selected_regions as $key => $val) {
                        $sql = "INSERT INTO " . $this->dsc->table('area_region') . " (shipping_area_id, region_id,ru_id) VALUES ('$shipping_area_id', '$val','" . $adminru['ru_id'] . "')";
                        $this->db->query($sql);
                    }
                }

                $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'shipping_area.php?act=list&shipping=' . $shipping_id];

                return sys_msg($GLOBALS['_LANG']['edit_area_success'], 0, $lnk, true, true);
            }
        }

        /*------------------------------------------------------ */
        //-- 批量删除�        �送区域
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'multi_remove') {
            admin_priv('shiparea_manage');

            if (isset($_POST['checkboxes']) && count($_POST['checkboxes']) > 0) {
                $i = 0;
                foreach ($_POST['checkboxes'] as $v) {
                    $this->db->query("DELETE FROM " . $this->dsc->table('shipping_area') . " WHERE shipping_area_id='$v' and ru_id='" . $adminru['ru_id'] . "'");
                    $i++;
                }

                /* 记录管理员操作 */
                admin_log('', 'batch_remove', 'shipping_area');
            }
            /* 返回 */
            $links[0] = ['href' => 'shipping_area.php?act=list&shipping=' . intval($_REQUEST['shipping']), 'text' => $GLOBALS['_LANG']['go_back']];
            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 编辑�        �送区域名称
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_area') {
            /* 检查权限 */
            $check_auth = check_authz_json('shiparea_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 取得参数 */
            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 取得该区域所属的配送id */
            $shipping_id = $exc->get_name($id, 'shipping_id');

            /* 检查是否有重复的配送区域名称 */
            if (!$exc->is_only('shipping_area_name', $val, $id, "shipping_id = '$shipping_id' and ru_id='" . $adminru['ru_id'] . "'")) {
                return make_json_error($GLOBALS['_LANG']['repeat_area_name']);
            }

            /* 更新名称 */
            $exc->edit("shipping_area_name = '$val'", $id);

            /* 记录日志 */
            admin_log($val, 'edit', 'shipping_area');

            /* 返回 */
            return make_json_result(stripcslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 删除�        �送区域
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove_area') {
            $check_auth = check_authz_json('shiparea_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            $name = $exc->get_name($id);
            $shipping_id = $exc->get_name($id, 'shipping_id');

            $exc->drop($id);
            $this->db->query('DELETE FROM ' . $this->dsc->table('shipping_area') . ' WHERE shipping_area_id=' . $id . " and ru_id='" . $adminru['ru_id'] . "'");
            $this->db->query('DELETE FROM ' . $this->dsc->table('area_region') . ' WHERE shipping_area_id=' . $id . " and ru_id='" . $adminru['ru_id'] . "'");

            admin_log($name, 'remove', 'shipping_area');

            //上门取货
            $sql = "SELECT shipping_code FROM " . $this->dsc->table("shipping") . " WHERE shipping_id=" . $shipping_id;
            $shipping_code = $this->db->getOne($sql);

            $list = $this->get_shipping_area_list($shipping_id, $adminru['ru_id']);

            /* 自提点名称 */
            if (!empty($list) && $shipping_code == "cac") {
                foreach ($list as $key => $val) {
                    $sql = "SELECT name FROM " . $this->dsc->table("shipping_point") . " WHERE shipping_area_id=" . $val['shipping_area_id'];
                    $list[$key]['name'] = $this->db->getAll($sql);
                }
            }
            $this->smarty->assign('areas', $list);
            $this->smarty->assign('shipping_code', $shipping_code);
            return make_json_result($this->smarty->fetch('shipping_area_list.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 删除自提点
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove_point') {
            $check_auth = check_authz_json('shiparea_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $id = intval($_GET['id']);
            $name = $exc->get_name($id);
            $sql = "DELETE FROM " . $this->dsc->table('shipping_point') . " WHERE id='$id'";
            if ($this->db->query($sql)) {
                $data = ['error' => 2, 'message' => $GLOBALS['_LANG']['carddrop_succeed'], 'content' => ''];
                admin_log($name, 'remove', 'shipping_area');
            } else {
                $data = ['error' => 0, 'message' => $GLOBALS['_LANG']['carddrop_fail'], 'content' => ''];
            }
            return response()->json($data);
        }
    }

    /**
     * 取得配送区域列表
     * @param int $shipping_id 配送id
     */
    private function get_shipping_area_list($shipping_id, $ru_id)
    {
        $sql = "SELECT * FROM " . $this->dsc->table('shipping_area') . " where ru_id='$ru_id' ";
        if ($shipping_id > 0) {
            $sql .= " and shipping_id = '$shipping_id'";
        }
        $res = $this->db->query($sql);
        $list = [];
        foreach ($res as $row) {
            $sql = "SELECT r.region_name " .
                "FROM " . $this->dsc->table('area_region') . " AS a, " .
                $this->dsc->table('region') . " AS r " .
                "WHERE a.region_id = r.region_id " .
                "AND a.shipping_area_id = '$row[shipping_area_id]'";
            $regions = $this->db->getCol($sql);
            $regions = $regions ? join(', ', $regions) : '';

            $row['shipping_area_regions'] = empty($regions) ?
                '<a href="shipping_area.php?act=edit&amp;id=' . $row['shipping_area_id'] .
                '" style="color:red">' . $GLOBALS['_LANG']['empty_regions'] . '</a>' : $regions;
            $list[] = $row;
        }

        return $list;
    }
}
