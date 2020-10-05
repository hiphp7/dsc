<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Services\Goods\AttributeManageService;

/**
 * 属性规格管理
 */
class AttributeController extends InitController
{
    protected $attributeManageService;

    public function __construct(
        AttributeManageService $attributeManageService
    )
    {
        $this->attributeManageService = $attributeManageService;
    }

    public function index()
    {

        /* act操作项的初始化 */
        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $exc = new Exchange($this->dsc->table("attribute"), $this->db, 'attr_id', 'attr_name');

        $this->smarty->assign('current', basename(PHP_SELF, '.php'));
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
        /*------------------------------------------------------ */
        //-- 属性列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '08_goods_type']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_type.php?act=manage', 'class' => 'icon-reply']);
            $goods_type = (int)request()->input('goods_type', 0);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_attribute_list']);
            $this->smarty->assign('goods_type_list', goods_type_list($goods_type)); // 取得商品类型
            $this->smarty->assign('full_page', 1);

            $list = $this->get_attrlist();

            $this->smarty->assign('attr_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('action_link', ['href' => 'attribute.php?act=add&goods_type=' . $goods_type, 'text' => $GLOBALS['_LANG']['10_attribute_add'], 'class' => 'icon-plus']);
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('action_link', ['href' => 'attribute.php?act=add&goods_type=' . $goods_type, 'text' => $GLOBALS['_LANG']['10_attribute_add'], 'class' => 'icon-plus']);
                $this->smarty->assign('attr_set_up', 1);
            }
            //ecmoban模板堂 --zhuo end

            /* 显示模板 */

            return $this->smarty->display('attribute_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、翻页
        /*------------------------------------------------------ */

        elseif ($act == 'query') {
            $list = $this->get_attrlist();

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('attr_set_up', 1);
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('attr_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('attribute_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑属性
        /*------------------------------------------------------ */
        elseif ($act == 'add' || $act == 'edit') {
            /* 检查权限 */
            admin_priv('attr_manage');
            $attr_id = (int)request()->input('attr_id', 0);

            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '08_goods_type']);

            /* 添加还是编辑的标识 */
            $is_add = $act == 'add';
            $this->smarty->assign('form_act', $is_add ? 'insert' : 'update');

            /* 取得属性信息 */
            if ($is_add) {
                $add_edit_cenetent = $GLOBALS['_LANG']['no_power_add_attr'];
                $goods_type = (int)request()->input('goods_type', 0);
                $attr = [
                    'attr_id' => 0,
                    'cat_id' => $goods_type,
                    'attr_cat_type' => 0, //by zhang
                    'attr_name' => '',
                    'attr_input_type' => 0,
                    'attr_index' => 0,
                    'attr_values' => '',
                    'attr_type' => 0,
                    'is_linked' => 0,
                ];
            } else {
                $add_edit_cenetent = $GLOBALS['_LANG']['no_power_edit_attr'];
                $sql = "SELECT * FROM " . $this->dsc->table('attribute') . " WHERE attr_id = '$attr_id'";
                $attr = $this->db->getRow($sql);
            }

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] > 0) {
                    $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                    return sys_msg($add_edit_cenetent, 0, $links);
                }
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('attr', $attr);
            $this->smarty->assign('attr_groups', get_attr_groups($attr['cat_id']));

            /* 取得商品分类列表 */
            $this->smarty->assign('goods_type_list', goods_type_list($attr['cat_id']));

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $is_add ? $GLOBALS['_LANG']['10_attribute_add'] : $GLOBALS['_LANG']['52_attribute_add']);
            $this->smarty->assign('action_link', ['href' => 'attribute.php?act=list', 'text' => $GLOBALS['_LANG']['09_attribute_list'], 'class' => 'icon-reply']);

            /* 显示模板 */

            return $this->smarty->display('attribute_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入/更新属性
        /*------------------------------------------------------ */

        elseif ($act == 'insert' || $act == 'update') {
            /* 检查权限 */
            admin_priv('attr_manage');

            /* 插入还是更新的标识 */
            $is_insert = $act == 'insert';

            $cat_id = (int)request()->input('cat_id', 0);
            $sort_order = (int)request()->input('sort_order', 0);
            $attr_name = request()->input('attr_name', '');
            $attr_cat_type = request()->input('attr_cat_type', '');
            $attr_index = request()->input('attr_index', '');
            $attr_input_type = request()->input('attr_input_type', '');
            $is_linked = request()->input('is_linked', '');
            $attr_values = request()->input('attr_values', '');
            $attr_type = (int)request()->input('attr_type', 0);
            $attr_group = (int)request()->input('attr_group', 0);

            /* 检查名称是否重复 */
            $exclude = (int)request()->input('attr_id', 0);
            if (!$exc->is_only('attr_name', $attr_name, $exclude, " cat_id = '$cat_id'")) {
                return sys_msg($GLOBALS['_LANG']['name_exist'], 1);
            }

            /* 取得属性信息 */
            $attr = [
                'cat_id' => $cat_id,
                'attr_name' => $attr_name,
                'attr_cat_type' => $attr_cat_type, //by zhang
                'attr_index' => $attr_index,
                'sort_order' => $sort_order,
                'attr_input_type' => $attr_input_type,
                'is_linked' => $is_linked,
                'attr_values' => $attr_values,
                'attr_type' => $attr_type,
                'attr_group' => $attr_group
            ];

            $attr['attr_values'] = $this->attributeManageService->filterTextblank($attr['attr_values']);

            /* 入库、记录日志、提示信息 */
            if ($is_insert) {
                $this->db->autoExecute($this->dsc->table('attribute'), $attr, 'INSERT');
                $attr_id = $this->db->insert_id();

                $sql = "SELECT MAX(sort_order) AS sort FROM " . $this->dsc->table('attribute') . " WHERE cat_id = '$cat_id'";
                $sort = $this->db->getOne($sql);

                if (empty($attr['sort_order']) && !empty($sort)) {
                    $attr = [
                        'sort_order' => $attr_id,
                    ];
                    $this->db->autoExecute($this->dsc->table('attribute'), $attr, 'UPDATE', "attr_id = '$attr_id'");
                }

                admin_log($attr_name, 'add', 'attribute');
                $links = [
                    ['text' => $GLOBALS['_LANG']['add_next'], 'href' => '?act=add&goods_type=' . $cat_id],
                    ['text' => $GLOBALS['_LANG']['back_list'], 'href' => '?act=list&goods_type=' . $cat_id],
                ];
                return sys_msg(sprintf($GLOBALS['_LANG']['add_ok'], $attr['attr_name']), 0, $links);
            } else {
                $this->db->autoExecute($this->dsc->table('attribute'), $attr, 'UPDATE', "attr_id = '$exclude'");
                admin_log($attr_name, 'edit', 'attribute');
                $links = [
                    ['text' => $GLOBALS['_LANG']['back_list'], 'href' => '?act=list&amp;goods_type=' . $cat_id . ''],
                ];
                return sys_msg(sprintf($GLOBALS['_LANG']['edit_ok'], $attr['attr_name']), 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 设置属性颜色  查询现有颜色 by zhang start
        /*------------------------------------------------------ */
        if ($act == 'set_gcolor') {
            $attr_id = (int)request()->input('attr_id', 0);

            $SQL = "SELECT color_values FROM " . $this->dsc->table('attribute') . " WHERE attr_id='$attr_id'";

            $list = $this->db->getOne($SQL); // 查询颜色

            $list2 = [];
            if (!empty($list)) {
                $list = explode("\n", trim($list));

                for ($i = 0; $i < count($list); $i++) {
                    if (!stripos($list[$i], "_#")) {
                        $list[$i] = trim($list[$i]) . "_#FFFFFF";
                    }
                    $color = explode("_", $list[$i]);
                    $list2[$i] = $color;
                }
            }

            $attr_values = get_add_attr_values($attr_id, 1, $list2);

            $this->smarty->assign('attr_values', $attr_values);
            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_gcolor']);
            $this->smarty->assign('form_act', 'gcolor_insert');

            /* 显示模板 */

            return $this->smarty->display('set_gcolor.dwt');
        }

        /*------------------------------------------------------ */
        //-- 设置属性颜色 修改属性
        /*------------------------------------------------------ */
        if ($act == 'gcolor_insert') {
            $attr_id = (int)request()->input('attr_id', 0);

            $request = request()->all();
            unset($request['attr_id']);
            unset($request['act']);
            $str = '';

            foreach ($request as $key_c => $value_c) {
                if (empty($value_c)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'attribute.php?act=set_gcolor&attr_id=' . $attr_id];
                    return sys_msg(lang('seller/attribute.select_color'), 0, $link);
                }
            }

            foreach ($request as $k => $v) {
                $sql = "UPDATE " . $this->dsc->table('goods_attr') . " SET color_value = '$v'" . " WHERE attr_id = '$attr_id' AND attr_value = '$k'";
                $this->db->query($sql);
            }

            foreach ($request as $k => $v) {
                $str .= $k . "_#" . $v . "\n";
            }
            $str = strtoupper(trim($str));

            $update_color = "UPDATE " . $this->dsc->table('attribute') . " SET `color_values` = '$str' WHERE `attr_id` = '$attr_id'";
            $this->db->query($update_color); // 修改颜色
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'attribute.php?act=set_gcolor&attr_id=' . $attr_id];
            return sys_msg($GLOBALS['_LANG']['edit_success_attr_color'], 0, $link);
        }
        //by zhuo end

        /*------------------------------------------------------ */
        //-- 插�        �/更新属性图片
        /*------------------------------------------------------ */

        elseif ($act == 'add_img') {
            /* 检查权限 */
            admin_priv('attr_manage');

            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '08_goods_type']);
            $attr_id = (int)request()->input('attr_id', 0);
            $attr_name = addslashes(trim(request()->input('attr_name', '')));

            $attr_values = get_add_attr_values($attr_id);
            $this->smarty->assign('attr_values', $attr_values);
            $this->smarty->assign('attr_name', $attr_name);
            $this->smarty->assign('attr_id', $attr_id);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_attr_img']);
            $this->smarty->assign('action_link2', ['href' => 'attribute.php?act=edit&attr_id=' . $attr_id, 'text' => $GLOBALS['_LANG']['go_back']]);
            $this->smarty->assign('action_link', ['href' => 'attribute.php?act=list', 'text' => $GLOBALS['_LANG']['09_attribute_list']]);
            $this->smarty->assign('form_act', 'insert_img');

            /* 显示模板 */

            return $this->smarty->display('attribute_img.dwt');
        } elseif ($act == 'insert_img') {
            /* 检查权限 */
            admin_priv('attr_manage');
            $attr_id = (int)request()->input('attr_id', 0);
            $attr_name = addslashes(trim(request()->input('attr_name', '')));

            $attr_values = get_add_attr_values($attr_id);

            get_attrimg_insert_update($attr_id, $attr_values);

            $link[0] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => "attribute.php?act=add_img&attr_id=" . $attr_id . "&attr_name=" . $attr_name];
            $link[1] = ['text' => $GLOBALS['_LANG']['return_this_attr'], 'href' => 'attribute.php?act=edit&attr_id=' . $attr_id];
            $link[2] = ['text' => $GLOBALS['_LANG']['09_attribute_list'], 'href' => 'attribute.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除属性(一个或多个)
        /*------------------------------------------------------ */
        elseif ($act == 'batch') {
            /* 检查权限 */
            admin_priv('attr_manage');

            /* 取得要操作的编号 */
            if (request()->exists('checkboxes')) {
                $checkboxes = request()->input('checkboxes', []);
                $count = count($checkboxes);
                $ids = !empty($checkboxes) ? join(',', $checkboxes) : 0;

                $sql = "DELETE FROM " . $this->dsc->table('attribute') . " WHERE attr_id " . db_create_in($ids);
                $this->db->query($sql);

                $sql = "DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE attr_id " . db_create_in($ids);
                $this->db->query($sql);

                /* 记录日志 */
                admin_log('', 'batch_remove', 'attribute');
                clear_cache_files();

                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'attribute.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['drop_ok'], $count), 0, $link);
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'attribute.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_arrt'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑属性名称
        /*------------------------------------------------------ */

        elseif ($act == 'edit_attr_name') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $val = json_str_iconv(trim(request()->input('val', '')));

            /* 取得该属性所属商品类型id */
            $cat_id = $exc->get_name($id, 'cat_id');

            /* 检查属性名称是否重复 */
            if (!$exc->is_only('attr_name', $val, $id, " cat_id = '$cat_id'")) {
                return make_json_error($GLOBALS['_LANG']['name_exist']);
            }

            $exc->edit("attr_name='$val'", $id);

            admin_log($val, 'edit', 'attribute');

            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        elseif ($act == 'edit_sort_order') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $val = (int)request()->input('val', 0);

            $exc->edit("sort_order='$val'", $id);

            admin_log(addslashes($exc->get_name($id)), 'edit', 'attribute');
            clear_all_files();

            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 删除商品属性
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);

            $this->db->query("DELETE FROM " . $this->dsc->table('attribute') . " WHERE attr_id='$id'");
            $this->db->query("DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE attr_id='$id'");

            $url = 'attribute.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 获取某属性商品数量
        /*------------------------------------------------------ */
        elseif ($act == 'get_attr_num') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('attr_id', 0);

            $sql = "SELECT COUNT(*) " .
                " FROM " . $this->dsc->table('goods_attr') . " AS a, " .
                $this->dsc->table('goods') . " AS g " .
                " WHERE g.goods_id = a.goods_id AND g.is_delete = 0 AND attr_id = '$id' ";

            $goods_num = $this->db->getOne($sql);

            if ($goods_num > 0) {
                $drop_confirm = sprintf($GLOBALS['_LANG']['notice_drop_confirm'], $goods_num);
                return json_encode(['error' => 1, 'message' => $drop_confirm]);
            } else {
                $drop_confirm = $GLOBALS['_LANG']['drop_confirm'];
            }

            return make_json_result(['attr_id' => $id, 'drop_confirm' => $drop_confirm]);
        }

        /*------------------------------------------------------ */
        //-- 获得指定商品类型下的所有属性分组
        /*------------------------------------------------------ */

        elseif ($act == 'get_attr_groups') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $cat_id = (int)request()->input('cat_id', 0);
            $groups = get_attr_groups($cat_id);

            return make_json_result($groups);
        }
    }
    /*------------------------------------------------------ */
    //-- PRIVATE FUNCTIONS
    /*------------------------------------------------------ */

    /**
     * 获取属性列表
     *
     * @return  array
     */
    private function get_attrlist()
    {
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        $ruCat = '';

        if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            if ($adminru['ru_id'] > 0) {
                $ruCat .= " and t.user_id = 0 ";
            }
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
            if ($adminru['ru_id'] > 0) {
                $ruCat = " and t.user_id = '" . $adminru['ru_id'] . "'";
            }
        }
        //ecmoban模板堂 --zhuo end

        /* 查询条件 */
        $filter = [];
        $filter['goods_type'] = (int)request()->input('goods_type', 0);
        $filter['sort_by'] = addslashes(trim(request()->input('sort_by', 'attr_id')));
        $filter['sort_order'] = addslashes(trim(request()->input('sort_order', 'ASC')));

        if ((!empty($filter['goods_type']))) {
            $where = " WHERE a.cat_id = '$filter[goods_type]' ";
        } else {
            $ruCat = " where t.user_id = '" . $adminru['ru_id'] . "'";
        }
        $where = (!empty($filter['goods_type'])) ? " WHERE a.cat_id = '$filter[goods_type]' " : '';
        $where .= $ruCat;

        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('attribute') . " AS a " . " LEFT JOIN " . $this->dsc->table('goods_type') . " AS t ON a.cat_id = t.cat_id " . " $where";
        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT a.*, t.cat_name " .
            " FROM " . $this->dsc->table('attribute') . " AS a " .
            " LEFT JOIN " . $this->dsc->table('goods_type') . " AS t ON a.cat_id = t.cat_id " . $where .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . $filter['start'] . ", $filter[page_size]";
        $row = $this->db->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['attr_input_type_desc'] = $GLOBALS['_LANG']['value_attr_input_type'][$val['attr_input_type']];
            $row[$key]['attr_values'] = str_replace("\n", ", ", $val['attr_values']);
        }

        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
