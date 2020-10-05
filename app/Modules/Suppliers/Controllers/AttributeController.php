<?php

namespace App\Modules\Suppliers\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Common\CommonManageService;
use App\Services\Goods\AttributeManageService;
use App\Services\Goods\AttributeService;

/**
 * 属性规格管理
 */
class AttributeController extends InitController
{
    protected $dscRepository;
    protected $commonManageService;
    protected $attributeManageService;
    protected $config;
    protected $attributeService;

    public function __construct(
        CommonManageService $commonManageService,
        AttributeManageService $attributeManageService,
        DscRepository $dscRepository,
        AttributeService $attributeService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->commonManageService = $commonManageService;
        $this->attributeManageService = $attributeManageService;
        $this->config = $this->dscRepository->dscConfig();
        $this->attributeService = $attributeService;
    }

    public function index()
    {
        $seller = $this->commonManageService->getAdminIdSeller();

        /* act操作项的初始化 */
        $act = request()->input('act', 'list');

        $this->smarty->assign('menus', session('menus', ''));

        $this->smarty->assign('current', basename(PHP_SELF, '.php'));
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);

        $this->smarty->assign('menu_select', array('action' => '01_suppliers_goods', 'current' => '08_goods_type'));

        /*------------------------------------------------------ */
        //-- 属性列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');

            $this->smarty->assign('action_link2', array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_type.php?act=manage', 'class' => 'icon-reply'));

            $goods_type = request()->input('goods_type', 0);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_attribute_list']);
            $this->smarty->assign('goods_type_list', goods_type_list($goods_type)); // 取得商品类型
            $this->smarty->assign('full_page', 1);
            $list = $this->attributeManageService->getAttrlist();

            $this->smarty->assign('attr_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            if ($this->config['attr_set_up'] == 0) {
                if ($seller['ru_id'] == 0) {
                    $this->smarty->assign('action_link', array('href' => 'attribute.php?act=add&goods_type=' . $goods_type, 'text' => $GLOBALS['_LANG']['10_attribute_add'], 'class' => 'icon-plus'));
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($this->config['attr_set_up'] == 1) {
                $this->smarty->assign('action_link', array('href' => 'attribute.php?act=add&goods_type=' . $goods_type, 'text' => $GLOBALS['_LANG']['10_attribute_add'], 'class' => 'icon-plus'));
                $this->smarty->assign('attr_set_up', 1);
            }

            /* 显示模板 */

            return $this->smarty->display('attribute_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、翻页
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            $check_auth = check_authz_json('suppliers_attr_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $list = $this->attributeManageService->getAttrlist();

            if ($this->config['attr_set_up'] == 0) {
                if ($seller['ru_id'] == 0) {
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($this->config['attr_set_up'] == 1) {
                $this->smarty->assign('attr_set_up', 1);
            }

            $this->smarty->assign('attr_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('attribute_list.dwt'),
                '',
                array('filter' => $list['filter'], 'page_count' => $list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑属性
        /*------------------------------------------------------ */
        elseif ($act == 'add' || $act == 'edit') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');
            
            $attr_id = request()->input('attr_id', 0);
            
            /* 添加还是编辑的标识 */
            $is_add = $act == 'add';
            $this->smarty->assign('form_act', $is_add ? 'insert' : 'update');
            
            $goods_type = request()->input('goods_type', 0);
            
            /* 取得属性信息 */
            if ($is_add) {
                $attr = array(
                    'attr_id' => 0,
                    'cat_id' => $goods_type,
                    'attr_cat_type' => 0, //by zhang
                    'attr_name' => '',
                    'attr_input_type' => 0,
                    'attr_index' => 0,
                    'attr_values' => '',
                    'attr_type' => 0,
                    'is_linked' => 0,
                );
            } else {
                $attr = $this->attributeService->getAttributeInfo($attr_id);
            }

            $add_edit_cenetent = lang('suppliers/attribute.no_permission');
            if ($this->config['attr_set_up'] == 0) {
                if ($seller['ru_id'] > 0) {
                    $links = array(array('href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']));
                    return sys_msg($add_edit_cenetent, 0, $links);
                }
            }

            $this->smarty->assign('attr', $attr);
            $this->smarty->assign('attr_groups', get_attr_groups($attr['cat_id']));

            /* 取得商品分类列表 */
            $this->smarty->assign('goods_type_list', goods_type_list($attr['cat_id']));

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $is_add ? $GLOBALS['_LANG']['10_attribute_add'] : $GLOBALS['_LANG']['52_attribute_add']);
            $this->smarty->assign('action_link', array('href' => 'attribute.php?act=list&goods_type=' . $goods_type, 'text' => $GLOBALS['_LANG']['09_attribute_list'], 'class' => 'icon-reply'));

            /* 显示模板 */

            return $this->smarty->display('attribute_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入/更新属性
        /*------------------------------------------------------ */
        elseif ($act == 'insert' || $act == 'update') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');

            /* 插入还是更新的标识 */
            $is_insert = $act == 'insert';
            $cat_id = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $sort_order = isset($_POST['sort_order']) && !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0;
            $attr_name = isset($_POST['attr_name']) && $_POST['attr_name'] ? addslashes($_POST['attr_name']) : '';

            /* 检查名称是否重复 */
            $attr_id = request()->input('attr_id', 0);
            
            $where = [
                'attr_name' => $attr_name,
                'id' => [
                    'filed' => [
                        'attr_id' => $attr_id
                    ],
                    'condition' => '<>'
                ],
                'cat_id' => $cat_id
            ];
            $is_only = $this->attributeManageService->getAttributeIsOnly($where);

            if ($is_only) {
                return sys_msg($GLOBALS['_LANG']['name_exist'], 1);
            }

            $attr_cat_type = request()->input('attr_cat_type', 0);
            $attr_index = request()->input('attr_index', 0);
            $attr_input_type = request()->input('attr_input_type', 0);
            $is_linked = request()->input('is_linked', 0);
            $attr_values = request()->input('attr_values', '');
            $attr_type = request()->input('attr_type', 0);
            $attr_group = request()->input('attr_group', 0);

            /* 取得属性信息 */
            $attr = array(
                'cat_id' => $cat_id,
                'attr_name' => $attr_name,
                'attr_cat_type' => $attr_cat_type, //by zhang
                'attr_index' => $attr_index,
                'sort_order' => $sort_order,
                'attr_input_type' => $attr_input_type,
                'is_linked' => $is_linked,
                'attr_values' => $attr_values,
                'attr_type' => intval($attr_type),
                'attr_group' => intval($attr_group)
            );

            $attr['attr_values'] = $this->attributeManageService->filterTextblank($attr['attr_values']);

            /* 入库、记录日志、提示信息 */
            if ($is_insert) {
                $attr_id = $this->attributeManageService->setAttributeInsert($attr);
                $sort = $this->attributeManageService->getAttributeMax($cat_id, 'sort_order');

                if (empty($attr['sort_order']) && !empty($sort)) {
                    $attr_other = [
                        'sort_order' => $attr_id
                    ];
                    $this->attributeManageService->setAttributeUpdate($attr_other, $attr_id);
                }
                admin_log($attr_name, 'add', 'attribute');

                $links = array(
                    array('text' => $GLOBALS['_LANG']['add_next'], 'href' => '?act=add&goods_type=' . $cat_id),
                    array('text' => $GLOBALS['_LANG']['back_list'], 'href' => '?act=list&goods_type=' . $cat_id),
                );

                return sys_msg(sprintf($GLOBALS['_LANG']['add_ok'], $attr_name), 0, $links);
            } else {
                $id = $this->attributeManageService->setAttributeUpdate($attr, $attr_id);
                if ($id) {
                    admin_log($attr_name, 'edit', 'attribute');
                }

                $links = [
                    [
                        'text' => $GLOBALS['_LANG']['back_list'], 'href' => '?act=list&goods_type=' . $cat_id . ''
                    ]
                ];
                return sys_msg(sprintf($GLOBALS['_LANG']['edit_ok'], $attr_name), 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 设置属性颜色  查询现有颜色
        /*------------------------------------------------------ */
        elseif ($act == 'set_gcolor') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');

            $attr_id = request()->input('attr_id', 0);

            $colorValues = $this->attributeManageService->getAttributeColorValues($attr_id);

            $list = [];
            if (!empty($colorValues)) {
                $res = $this->baseRepository->getExplode(trim($colorValues), "\n");

                if ($res) {
                    for ($i = 0; $i < count($res); $i++) {
                        if (!stripos($res[$i], "_#")) {
                            $res[$i] = trim($res[$i]) . "_#FFFFFF";
                        }
                        $color = $this->baseRepository->getExplode($res[$i], "_");
                        $list[$i] = $color;
                    }
                }
            }

            $attr_values = get_add_attr_values($attr_id, 1, $list);

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
        elseif ($act == 'gcolor_insert') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');

            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            unset($_GET['attr_id']);
            unset($_GET['act']);

            $str = '';

            if ($_GET) {
                foreach ($_GET as $key_c => $value_c) {
                    if (empty($value_c)) {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'attribute.php?act=set_gcolor&attr_id=' . $attr_id);
                        return sys_msg(lang('suppliers/attribute.please_select_color'), 0, $link);
                    }
                }
            }

            foreach ($_GET as $k => $v) {
                $this->attributeManageService->getGoodsAttrUpdateColorValue(['color_value' => $v], $attr_id, $k);
            }

            foreach ($_GET as $k => $v) {
                $str .= $k . "_#" . $v . "\n";
            }
            $str = strtoupper(trim($str));

            $this->attributeManageService->getAttributeUpdateColorValue(['color_values' => $str], $attr_id);

            $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'attribute.php?act=set_gcolor&attr_id=' . $attr_id);
            return sys_msg(lang('suppliers/attribute.edit_color_success'), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 插入/更新属性图片
        /*------------------------------------------------------ */
        elseif ($act == 'add_img') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');

            $attr_id = request()->input('attr_id', 0);
            $attr_name = request()->input('attr_name', '');

            $attr_values = get_add_attr_values($attr_id);
            $this->smarty->assign('attr_values', $attr_values);
            $this->smarty->assign('attr_name', $attr_name);
            $this->smarty->assign('attr_id', $attr_id);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', lang('suppliers/attribute.edit_img'));
            $this->smarty->assign('action_link2', array('href' => 'attribute.php?act=edit&attr_id=' . $attr_id, 'text' => $GLOBALS['_LANG']['go_back']));
            $this->smarty->assign('action_link', array('href' => 'attribute.php?act=list', 'text' => $GLOBALS['_LANG']['09_attribute_list']));
            $this->smarty->assign('form_act', 'insert_img');

            /* 显示模板 */

            return $this->smarty->display('attribute_img.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入/更新属性图片
        /*------------------------------------------------------ */
        elseif ($act == 'insert_img') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');

            $attr_id = request()->input('attr_id', 0);
            $attr_name = request()->input('attr_name', '');

            $goods_type = 0;
            if (!empty($attr_id)) {
                $attr_values = get_add_attr_values($attr_id);

                get_attrimg_insert_update($attr_id, $attr_values);

                $sql = "SELECT cat_id FROM" . $this->dsc->table('attribute') . "WHERE attr_id = '$attr_id'";
                $goods_type = $this->db->getOne($sql);
            }
            
            $link[0] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => "attribute.php?act=add_img&attr_id=" . $attr_id . "&attr_name=" . $attr_name);
            $link[1] = array('text' => lang('suppliers/attribute.back_attribution'), 'href' => 'attribute.php?act=edit&attr_id=' . $attr_id);
            $link[2] = array('text' => $GLOBALS['_LANG']['09_attribute_list'], 'href' => 'attribute.php?act=list&goods_type=' . $goods_type);
            return sys_msg(lang('suppliers/attribute.edit_success'), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除属性(一个或多个)
        /*------------------------------------------------------ */
        elseif ($act == 'batch') {
            /* 检查权限 */
            admin_priv('suppliers_attr_list');

            $checkboxes = request()->input('checkboxes', []);

            /* 取得要操作的编号 */
            if (!empty($checkboxes)) {
                $count = count($checkboxes);
                $ids = addslashes_deep($checkboxes);

                $this->attributeManageService->getAttributeDelete($ids);
                $this->attributeManageService->getGoodsAttrDelete($ids);

                /* 记录日志 */
                admin_log('', 'batch_remove', 'attribute');
                clear_cache_files();

                $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'attribute.php?act=list');
                return sys_msg(sprintf($GLOBALS['_LANG']['drop_ok'], $count), 0, $link);
            } else {
                $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'attribute.php?act=list');
                return sys_msg($GLOBALS['_LANG']['no_select_arrt'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑属性名称
        /*------------------------------------------------------ */
        elseif ($act == 'edit_attr_name') {
            $check_auth = check_authz_json('suppliers_attr_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->input('id', 0);
            $val = request()->input('val', '');
            $val = json_str_iconv(trim($val));

            /* 取得该属性所属商品类型id */
            $cat_id = $this->attributeManageService->getAttributeCatId($id);

            $where = [
                'attr_name' => $val,
                'id' => [
                    'filed' => [
                        'attr_id' => $id
                    ],
                    'condition' => '<>'
                ],
                'cat_id' => $cat_id
            ];
            $is_only = $this->attributeManageService->getAttributeIsOnly($where);

            /* 检查属性名称是否重复 */
            if ($is_only) {
                return make_json_error($GLOBALS['_LANG']['name_exist']);
            }

            $this->attributeManageService->setAttributeUpdate(['attr_name' => $val], $id);

            admin_log($val, 'edit', 'attribute');

            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($act == 'edit_sort_order') {
            $check_auth = check_authz_json('suppliers_attr_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            
            $id = request()->input('id', 0);
            $val = request()->input('val', '');

            $this->attributeManageService->setAttributeUpdate(['sort_order' => $val], $id);

            $attr_name = $this->attributeManageService->getAttributeAttrName($id);

            admin_log(addslashes($attr_name), 'edit', 'attribute');
            clear_all_files();

            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 删除商品属性
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            $check_auth = check_authz_json('suppliers_attr_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            
            $id = request()->input('id', 0);

            $this->attributeManageService->getAttributeDelete($id);
            $this->attributeManageService->getGoodsAttrDelete($id);

            $url = 'attribute.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 获取某属性商品数量
        /*------------------------------------------------------ */
        elseif ($act == 'get_attr_num') {
            $check_auth = check_authz_json('suppliers_attr_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            
            $id = request()->input('attr_id', 0);

            $goods_num = $this->attributeManageService->getGoodsAttrNum($id);

            if ($goods_num > 0) {
                $drop_confirm = sprintf($GLOBALS['_LANG']['notice_drop_confirm'], $goods_num);
            } else {
                $drop_confirm = $GLOBALS['_LANG']['drop_confirm'];
            }

            return make_json_result(array('attr_id' => $id, 'drop_confirm' => $drop_confirm));
        }

        /*------------------------------------------------------ */
        //-- 获得指定商品类型下的所有属性分组
        /*------------------------------------------------------ */
        elseif ($act == 'get_attr_groups') {
            $check_auth = check_authz_json('suppliers_attr_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            
            $cat_id = request()->input('cat_id', 0);
            $groups = get_attr_groups($cat_id);

            return make_json_result($groups);
        }
    }
}
