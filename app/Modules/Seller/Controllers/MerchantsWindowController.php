<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;

/**
 * 管理中心入驻商家店铺橱窗管理
 */
class MerchantsWindowController extends InitController
{
    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "index");
        $exc = new Exchange($this->dsc->table("seller_shopwindow"), $this->db, 'id', 'win_name');
        //获取店铺模板
        $adminru = get_admin_ru_id();
        $sql = "select * from " . $this->dsc->table('seller_shopinfo') . " where ru_id='" . $adminru['ru_id'] . "'";
        $seller_shopinfo = $this->db->getRow($sql);

        $seller_theme = $seller_shopinfo['seller_theme'];
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
        $sql = "select count(*) from " . $this->dsc->table('seller_shopinfo') . " where ru_id = '" . $adminru['ru_id'] . "'";
        $shop_id = $this->db->getOne($sql);
        if ($shop_id < 1) {
            $lnk[] = ['text' => $GLOBALS['_LANG']['set_shop_info'], 'href' => 'index.php?act=merchants_first'];
            return sys_msg($GLOBALS['_LANG']['please_set_shop_basic_info'], 0, $lnk);
        }
        $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '07_merchants_window']);
        /*------------------------------------------------------ */
        //-- 店铺橱窗列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['07_merchants_window'], 'href' => 'merchants_window.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['06_merchants_custom'], 'href' => 'merchants_custom.php?act=list'];
            $this->smarty->assign('tab_menu', $tab_menu);

            //页面分菜单 by wu end
            admin_priv('seller_store_other');//by kong
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['shop_windows_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_shop_window'], 'href' => 'merchants_window.php?act=add', 'class' => 'icon-plus']);
            $this->smarty->assign('full_page', 1);

            $win_list = $this->get_seller_window($seller_theme);

            $this->smarty->assign('win_list', $win_list);

            $this->smarty->assign('current', 'merchants_window');
            return $this->smarty->display('merchants_window_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {

            /* 创建 百度编辑器 wang 商家入驻 */
            create_ueditor_editor('win_custom', $seller_win['win_custom'], 586);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_shop_window']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['shop_windows_list'], 'href' => 'merchants_window.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('form_action', 'insert');

            $sql = "SELECT seller_theme FROM " . $this->dsc->table('seller_shopinfo') . " WHERE ru_id='" . $adminru['ru_id'] . "'";
            $seller_win = $this->db->GetRow($sql);
            $seller_win['win_type'] = 1;

            $this->smarty->assign('seller_win', $seller_win);

            $type_list = $this->win_goods_type_list($seller_shopinfo['win_goods_type']);
            $this->smarty->assign('type_list', $type_list);


            $this->smarty->assign('current', 'merchants_window');
            return $this->smarty->display('merchants_window_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            //检查名称是否重复
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('seller_shopwindow') . " WHERE win_name = '$_POST[winname]' and ru_id='" . $adminru['ru_id'] . "'";
            $number = $this->db->getOne($sql);
            if ($number > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['window_name_exist'], stripslashes($_POST['winname'])), 1);
            }

            $is_show = isset($_REQUEST['isshow']) ? intval($_REQUEST['isshow']) : 0;

            $win_name = !empty($_POST['winname']) ? stripslashes($_POST['winname']) : '';

            $win_img_link = !empty($_POST['winimglink']) ? stripslashes($_POST['winimglink']) : '#';

            $win_order = isset($_POST['winorder']) ? intval($_POST['winorder']) : 0;

            $win_type = isset($_REQUEST['wintype']) ? intval($_REQUEST['wintype']) : 0;

            $win_goods_type = isset($_REQUEST['win_goods_type']) ? intval($_REQUEST['win_goods_type']) : 1;

            $win_color = isset($_REQUEST['wincolor']) ? stripslashes($_REQUEST['wincolor']) : '';

            $preg = "/<script[\s\S]*?<\/script>/i";

            $win_custom = isset($_REQUEST['win_custom']) ? preg_replace($preg, "", stripslashes($_REQUEST['win_custom'])) : '';

            /*插入数据*/
            if ($win_name) {
                $sql = "INSERT INTO " . $this->dsc->table('seller_shopwindow') . "(win_type,win_goods_type,win_order,win_name, is_show, win_color, win_img,win_img_link,ru_id,win_custom,seller_theme) " .
                    "VALUES ('$win_type', '$win_goods_type', '$win_order', '$win_name', '$is_show', '$win_color', '$win_img','$win_img_link','" . $adminru['ru_id'] . "','$win_custom','$seller_theme')";
                $this->db->query($sql);
                $id = $this->db->insert_id();

                admin_log($_POST['winname'], 'add', 'seller_shopwindow');

                /* 清除缓存 */
                clear_cache_files();

                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'merchants_window.php?act=add';

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[1]['href'] = 'merchants_window.php?act=list';

                $link[2]['text'] = $GLOBALS['_LANG']['add_window_goods'];
                $link[2]['href'] = 'merchants_window.php?act=add_win_goods&id=' . $id;

                return sys_msg($GLOBALS['_LANG']['window_add_success'], 0, $link);
            } else {
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'merchants_window.php?act=add';

                return sys_msg($GLOBALS['_LANG']['window_add_fail'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */

            //admin_priv('brand_manage');

            $sql = "SELECT * FROM " . $this->dsc->table('seller_shopwindow') . " WHERE id='$_REQUEST[id]' and ru_id='" . $adminru['ru_id'] . "'";
            $seller_win = $this->db->GetRow($sql);

            /* 创建 百度编辑器 wang 商家入驻 */
            create_ueditor_editor('win_custom', $seller_win['win_custom'], 586);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['shop_window_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['shop_windows_list'], 'href' => 'merchants_window.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('seller_win', $seller_win);
            $this->smarty->assign('form_action', 'update');

            $type_list = $this->win_goods_type_list($seller_shopinfo['win_goods_type']);
            $this->smarty->assign('type_list', $type_list);


            $this->smarty->assign('current', 'merchants_window');
            return $this->smarty->display('merchants_window_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            //admin_priv('brand_manage');

            $is_show = isset($_REQUEST['isshow']) ? intval($_REQUEST['isshow']) : 0;

            $win_name = !empty($_POST['winname']) ? stripslashes($_POST['winname']) : '';

            $win_img_link = !empty($_POST['winimglink']) ? stripslashes($_POST['winimglink']) : '#';

            $win_order = isset($_POST['winorder']) ? intval($_POST['winorder']) : 0;

            $win_type = isset($_REQUEST['wintype']) ? intval($_REQUEST['wintype']) : 0;

            $win_goods_type = isset($_REQUEST['win_goods_type']) ? intval($_REQUEST['win_goods_type']) : 1;

            $win_color = isset($_REQUEST['wincolor']) ? stripslashes($_REQUEST['wincolor']) : '';

            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";

            $win_custom = isset($_REQUEST['win_custom']) ? preg_replace($preg, "", stripslashes($_REQUEST['win_custom'])) : '';


            /*处理图片*/
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';

            if ($_FILES['winimg']) {
                $file = $_FILES['winimg'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $ext = array_pop(explode('.', $file['name']));
                        $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_win_img/seller_' . $adminru['ru_id']);
                        if (!is_dir($file_dir)) {
                            mkdir($file_dir);
                        }
                        $file_name = $file_dir . "/win_" . gmtime() . '.' . $ext;
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $win_img = $file_name;
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], $file_dir));
                        }
                    }
                }
            }

            $param = "win_type='$win_type',win_goods_type='$win_goods_type', win_order='$win_order', is_show='$is_show',win_color='$win_color',win_img_link='$win_img_link',win_custom='$win_custom',seller_theme='$seller_theme' ";
            if (!empty($win_img)) {
                //有图片上传
                $param .= " ,win_img = '$win_img' ";
            }

            $is_only = $exc->is_only('win_name', $_POST['winname']);

            if ($is_only) {
                $param .= " ,win_name = '$win_name'";
            }

            if ($exc->edit($param, $_POST['id'])) {
                /* 清除缓存 */
                clear_cache_files();

                admin_log($_POST['winname'], 'edit', 'seller_shopwindow');

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'merchants_window.php?act=list';
                return sys_msg($GLOBALS['_LANG']['shop_window_edit_success'], 0, $link);
            } else {
                return $this->db->error();
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑橱窗名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_win_name') {
            $id = intval($_POST['id']);
            $name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否重复 */
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('seller_shopwindow') . " WHERE win_name = '$name' and ru_id='" . session('ru_id') . "'";

            $number = $this->db->getOne($sql);
            if ($number > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['window_exist'], $name));
            } else {
                if ($exc->edit("win_name = '$name'", $id)) {
                    admin_log($name, 'edit', 'seller_shopwindow');
                    return make_json_result(stripslashes($name));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['s_edit_fail'], $name));
                }
            }
        }
        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $id = intval($_POST['id']);
            $order = intval($_POST['val']);
            $name = $exc->get_name($id);

            if ($exc->edit("win_order = '$order'", $id)) {
                return make_json_result($order);
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['s_edit_fail'], $name));
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("is_show='$val'", $id);

            return make_json_result($val);
        }
        /*------------------------------------------------------ */
        //-- 删除橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            /* 删除该橱窗的图标 */
            $sql = "SELECT win_img FROM " . $this->dsc->table('seller_shopwindow') . " WHERE id = '$id' and ru_id='" . $adminru['ru_id'] . "'";
            $win_img = $this->db->getOne($sql);
            if (!empty($win_img)) {
                @unlink($win_img);
            }

            $exc->drop($id);


            $url = 'merchants_window.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $win_list = $this->get_seller_window($seller_theme);
            $this->smarty->assign('win_list', $win_list);

            $this->smarty->assign('current', 'merchants_window');
            return make_json_result($this->smarty->fetch('merchants_window_list.dwt'), '');
        } elseif ($_REQUEST['act'] == 'add_win_goods') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_merchants_window'], 'href' => 'merchants_window.php?act=list', 'class' => 'icon-reply']);
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $win_goods = $this->get_win_goods($id);

            if ($win_goods == 'no_cc') {
                return sys_msg($GLOBALS['_LANG']['illegal_date_no_access']);
            } else {
                $win_info = $this->get_win_info($id);

                $this->smarty->assign('win_info', $win_info);
                set_default_filter(0, 0, $adminru['ru_id']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_window_goods']);

                $this->smarty->assign('goods_count', count($win_goods));
                $this->smarty->assign('filter_result', $win_goods);

                $this->smarty->assign('current', 'merchants_window');
                return $this->smarty->display('add_win_goods.dwt');
            }
        } elseif ($_REQUEST['act'] == 'update_win_goods') {
            $id = !empty($_POST['win_id']) ? intval($_POST['win_id']) : 0;

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'merchants_window.php?act=add_win_goods&id=' . $id;
            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'merchants_window.php?act=list';
            return sys_msg($GLOBALS['_LANG']['window_goods_edit_success'], 0, $link);
        }
        /*------------------------------------------------------ */
        //-- 把商品加�        �商品柜
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_win_goods') {

            //$check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $linked_array = dsc_decode($_GET['add_ids'], true);
            $linked_window = dsc_decode($_GET['JSON'], true);
            $id = $linked_window[0];
            $win_goods = $this->db->getOne("select win_goods from " . $this->dsc->table('seller_shopwindow') . " where id='$id'");
            foreach ($linked_array as $val) {
                if (!strstr($win_goods, $val) && !empty($val)) {
                    $win_goods .= !empty($win_goods) ? ',' . $val : $val;
                }
            }

            $sql = "update " . $this->dsc->table('seller_shopwindow') . " set win_goods='$win_goods' where id='$id'";
            $this->db->query($sql);

            $win_goods = $this->get_win_goods($id);
            $options = [];

            foreach ($win_goods as $val) {
                $options[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($options);
        }

        /*------------------------------------------------------ */
        //-- 删除�        �联商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_win_goods') {

            //$check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = dsc_decode($_GET['drop_ids'], true);

            $linked_window = dsc_decode($_GET['JSON'], true);
            $id = $linked_window[0];

            $win_goods = $this->db->getOne("select win_goods from " . $this->dsc->table('seller_shopwindow') . " where id='$id'");
            $win_goods_arr = explode(',', $win_goods);

            foreach ($drop_goods as $val) {
                if (strstr($win_goods, $val) && !empty($val)) {
                    $key = array_search($val, $win_goods_arr);
                    if ($key !== false) {
                        array_splice($win_goods_arr, $key, 1);
                    }
                }
            }
            $new_win_goods = '';
            foreach ($win_goods_arr as $val) {
                if (!strstr($new_win_goods, $val) && !empty($val)) {
                    $new_win_goods .= !empty($new_win_goods) ? ',' . $val : $val;
                }
            }

            $sql = "update " . $this->dsc->table('seller_shopwindow') . " set win_goods='$new_win_goods' where id='$id'";
            $this->db->query($sql);

            $win_goods = $this->get_win_goods($id);
            $options = [];

            foreach ($win_goods as $val) {
                $options[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($options);
        } elseif ($_REQUEST['act'] = "batch") {
            $checkboxes = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : [];
            $type = isset($_POST['type']) ? intval($_POST['type']) : 0;

            if ($checkboxes) {
                if ($type == 1) {
                    $id = implode(',', $checkboxes);

                    $sql = "DELETE FROM " . $this->dsc->table('seller_shopwindow') . " WHERE win_type = 1 AND id in($id)";
                    $this->db->query($sql);
                }
            }

            $link[0]['text'] = $GLOBALS['_LANG']['back_shop_custom_list'];
            $link[0]['href'] = 'merchants_window.php?act=list';

            return sys_msg($GLOBALS['_LANG']['delete_success_alt'], 0, $link);
        }
    }

    /**
     * 获取橱窗列表
     *
     * @access  public
     * @return  array
     */
    private function get_seller_window($seller_theme)
    {
        $adminru = get_admin_ru_id();
        $sql = "SELECT * FROM " . $this->dsc->table('seller_shopwindow') . ' WHERE ru_id = \'' . $adminru['ru_id'] . '\' AND seller_theme = \'' . $seller_theme . '\' AND	win_type = 1';

        $win_list = $this->db->getAll($sql);

        foreach ($win_list as $key => $val) {
            $win_list[$key]['seller_theme'] = $GLOBALS['_LANG']['template_mall'] . "NO." . substr($val['seller_theme'], -1);
            $win_list[$key]['win_type_name'] = $val['win_type'] > 0 ? $GLOBALS['_LANG']['merchandise_cabinet'] : $GLOBALS['_LANG']['custom_content'];
        }

        return $win_list;
    }

    //获取某橱窗信息
    private function get_win_info($id)
    {
        $adminru = get_admin_ru_id();
        $sql = "select * from " . $this->dsc->table('seller_shopwindow') . " where id='$id' and ru_id='" . $adminru['ru_id'] . "'";

        return $this->db->getRow($sql);
    }

    //获取橱窗商品
    private function get_win_goods($id)
    {
        $adminru = get_admin_ru_id();
        $sql = "select id,win_goods from " . $this->dsc->table('seller_shopwindow') . " where id='$id' and ru_id='" . $adminru['ru_id'] . "'";

        $win_info = $this->db->getRow($sql);

        if ($win_info['id'] > 0) {
            $goods_ids = $win_info['win_goods'];
            $goods = [];
            if ($goods_ids) {
                $sql = "select goods_id,goods_name from " . $this->dsc->table('goods') . " where user_id='" . $adminru['ru_id'] . "' and goods_id in ($goods_ids)";
                $goods = $this->db->getAll($sql);
            }

            $opt = []; //by wu

            foreach ($goods as $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }
            return $opt;
        } else {
            return 'no_cc';
        }
    }

    //店铺橱窗N种样式
    private function win_goods_type_list($type = 0)
    {
        $arr = [];
        for ($i = 1; $i <= $type; $i++) {
            $arr[$i]['value'] = $i;
            $arr[$i]['name'] = $GLOBALS['_LANG']['style'] . $i;
        }

        return $arr;
    }
}
