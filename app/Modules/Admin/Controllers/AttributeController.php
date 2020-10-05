<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Attribute;
use App\Models\GoodsAttr;
use App\Repositories\Common\BaseRepository;
use App\Services\Goods\AttributeManageService;

/**
 * 属性规格管理
 */
class AttributeController extends InitController
{
    protected $attributeManageService;
    protected $baseRepository;

    public function __construct(
        AttributeManageService $attributeManageService,
        BaseRepository $baseRepository
    )
    {
        $this->attributeManageService = $attributeManageService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        /* act操作项的初始化 */
        $_REQUEST['act'] = trim($_REQUEST['act']);
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        }

        $adminru = get_admin_ru_id();

        /*------------------------------------------------------ */
        //-- 属性列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $goods_type = isset($_GET['goods_type']) ? intval($_GET['goods_type']) : 0;

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_attribute_list']);
            $this->smarty->assign('goods_type', $goods_type); // 取得商品类型id
            $this->smarty->assign('goods_type_list', goods_type_list($goods_type)); // 取得商品类型
            $this->smarty->assign('full_page', 1);

            $list = $this->attributeManageService->getAttrlist();

            $this->smarty->assign('attr_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('action_link', ['href' => 'attribute.php?act=add&goods_type=' . $goods_type, 'text' => $GLOBALS['_LANG']['10_attribute_add']]);
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('action_link', ['href' => 'attribute.php?act=add&goods_type=' . $goods_type, 'text' => $GLOBALS['_LANG']['10_attribute_add']]);
                $this->smarty->assign('attr_set_up', 1);
            }

            /* 显示模板 */

            return $this->smarty->display('attribute_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、翻页
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->attributeManageService->getAttrlist();

            $this->smarty->assign('attr_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('attr_set_up', 1);
            }

            return make_json_result(
                $this->smarty->fetch('attribute_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑属性
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('attr_manage');

            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;

            /* 添加还是编辑的标识 */
            $is_add = $_REQUEST['act'] == 'add';
            $this->smarty->assign('form_act', $is_add ? 'insert' : 'update');

            /* 取得属性信息 */
            if ($is_add) {
                $add_edit_cenetent = $GLOBALS['_LANG']['temporary_not_attr_power'];
                $goods_type = isset($_GET['goods_type']) ? intval($_GET['goods_type']) : 0;
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
                $add_edit_cenetent = $GLOBALS['_LANG']['temporary_not_attr_power'];

                $attr = Attribute::where('attr_id', $attr_id);
                $attr = $this->baseRepository->getToArrayFirst($attr);
            }

            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] > 0) {
                    $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                    return sys_msg($add_edit_cenetent, 0, $links);
                }
            }

            $this->smarty->assign('attr', $attr);
            $this->smarty->assign('attr_groups', get_attr_groups($attr['cat_id']));

            /* 取得商品分类列表 */
            $this->smarty->assign('goods_type_list', goods_type_list($attr['cat_id']));

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $is_add ? $GLOBALS['_LANG']['10_attribute_add'] : $GLOBALS['_LANG']['52_attribute_add']);
            $this->smarty->assign('action_link', ['href' => 'attribute.php?act=list&goods_type=' . $attr['cat_id'], 'text' => $GLOBALS['_LANG']['09_attribute_list']]);

            /* 显示模板 */

            return $this->smarty->display('attribute_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入/更新属性
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('attr_manage');

            /* 插入还是更新的标识 */
            $is_insert = $_REQUEST['act'] == 'insert';
            $cat_id = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $sort_order = isset($_POST['sort_order']) && !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0;
            $attr_name = isset($_POST['attr_name']) && !empty($_POST['attr_name']) ? trim($_POST['attr_name']) : '';

            /* 检查名称是否重复 */
            $exclude = empty($_POST['attr_id']) ? 0 : intval($_POST['attr_id']);

            $is_only = Attribute::where('attr_name', $attr_name)
                ->where('cat_id', $cat_id);

            if ($exclude > 0) {
                $is_only = $is_only->where('attr_id', '<>', $exclude);
            }

            $is_only = $is_only->count();

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['name_exist'], 1);
            }

            /* 取得属性信息 */
            $attr = [
                'cat_id' => $cat_id,
                'attr_name' => $_POST['attr_name'],
                'attr_cat_type' => $_POST['attr_cat_type'], //by zhang
                'attr_index' => $_POST['attr_index'],
                'sort_order' => $sort_order,
                'attr_input_type' => $_POST['attr_input_type'],
                'is_linked' => $_POST['is_linked'],
                'attr_values' => isset($_POST['attr_values']) ? $_POST['attr_values'] : '',
                'attr_type' => empty($_POST['attr_type']) ? '0' : intval($_POST['attr_type']),
                'attr_group' => isset($_POST['attr_group']) ? intval($_POST['attr_group']) : 0
            ];

            $attr['attr_values'] = $this->attributeManageService->filterTextblank($attr['attr_values']);

            /* 入库、记录日志、提示信息 */
            if ($is_insert) {
                $attr_id = Attribute::insertGetId($attr);

                $sort = Attribute::where('cat_id', $cat_id)->max('sort_order');
                $sort = $sort ? $sort : 0;

                if (empty($attr['sort_order']) && !empty($sort)) {
                    $attr_sort = [
                        'sort_order' => $attr_id,
                    ];

                    Attribute::where('attr_id', $attr_id)
                        ->update($attr_sort);
                }

                admin_log($_POST['attr_name'], 'add', 'attribute');

                $links = [
                    ['text' => $GLOBALS['_LANG']['add_next'], 'href' => '?act=add&goods_type=' . $cat_id],
                    ['text' => $GLOBALS['_LANG']['back_list'], 'href' => '?act=list&goods_type=' . $cat_id],
                ];
                return sys_msg(sprintf($GLOBALS['_LANG']['add_ok'], $attr['attr_name']), 0, $links);
            } else {
                Attribute::where('attr_id', $exclude)->update($attr);

                admin_log($_POST['attr_name'], 'edit', 'attribute');
                $links = [
                    ['text' => $GLOBALS['_LANG']['back_list'], 'href' => '?act=list&amp;goods_type=' . $cat_id . ''],
                ];
                return sys_msg(sprintf($GLOBALS['_LANG']['edit_ok'], $attr['attr_name']), 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 设置属性颜色  查询现有颜色 by zhang start
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'set_gcolor') {
            $attr_id = empty($_REQUEST['attr_id']) ? 0 : intval($_REQUEST['attr_id']);

            $attribute = Attribute::where('attr_id', $attr_id);
            $attribute = $this->baseRepository->getToArrayFirst($attribute);

            $attribute['attr_input_type'] = isset($attribute['attr_input_type']) ? $attribute['attr_input_type'] : 0;

            $attr_values = isset($attribute['attr_values']) && !empty($attribute['attr_values']) ? $attribute['attr_values'] : '';
            $color_values = isset($attribute['color_values']) && !empty($attribute['color_values']) ? $attribute['color_values'] : '';
            $cat_id = isset($attribute['cat_id']) && !empty($attribute['cat_id']) ? intval($attribute['cat_id']) : 0;

            $list2 = [];
            if (!empty($color_values)) {
                if ($attribute['attr_input_type'] == 1) {
                    $list = explode("\n", trim($color_values));
                } else {
                    $list = GoodsAttr::where('attr_id', $attr_id)
                        ->whereHas('getGoodsAttribute');
                    $list = $this->baseRepository->getToArrayGet($list);
                    $list = $this->baseRepository->getKeyPluck($list, 'attr_value');
                }

                if (empty($attr_values) && !empty($color_values)) {
                    $list = explode("\n", trim($color_values));
                }

                for ($i = 0; $i < count($list); $i++) {
                    if (!stripos($list[$i], "_#")) {
                        $list[$i] = trim($list[$i]) . "_#FFFFFF";
                    }
                    $color = explode("_", $list[$i]);
                    $list2[$i] = $color;
                }
            }

            $attr_values = get_add_attr_values($attr_id, 1, $list2, $attribute['attr_input_type']);

            $this->smarty->assign('attr_values', $attr_values);
            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('cat_id', $cat_id);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_gcolor']);
            $this->smarty->assign('form_act', 'gcolor_insert');

            $this->smarty->assign('action_link', ['href' => 'attribute.php?act=list&goods_type=' . $cat_id, 'text' => $GLOBALS['_LANG']['back_list']]);

            /* 显示模板 */

            return $this->smarty->display('set_gcolor.dwt');
        }

        /*------------------------------------------------------ */
        //-- 设置属性颜色 修改属性
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'gcolor_insert') {
            $attr_id = empty($_REQUEST['attr_id']) ? 0 : intval($_REQUEST['attr_id']);
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);

            $str = '';
            if ($_POST) {
                if (isset($_POST['attr_id'])) {
                    unset($_POST['attr_id']);
                }

                if (isset($_POST['cat_id'])) {
                    unset($_POST['cat_id']);
                }

                if (isset($_POST['act'])) {
                    unset($_POST['act']);
                }

                foreach ($_POST as $key_c => $value_c) {
                    if (empty($value_c)) {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'attribute.php?act=set_gcolor&attr_id=' . $attr_id];
                        return sys_msg($GLOBALS['_LANG']['select_color'], 0, $link);
                    }
                }

                foreach ($_POST as $k => $v) {
                    if (strpos($v, "#") !== false) {
                        $v = substr($v, 1);
                    }

                    GoodsAttr::where('attr_id', $attr_id)
                        ->where('attr_value', $k)
                        ->update([
                            'color_value' => $v
                        ]);
                }

                foreach ($_POST as $k => $v) {
                    if (strpos($v, "#") !== false) {
                        $v = substr($v, 1);
                    }

                    $str .= $k . "_#" . $v . "\n";
                }
            }

            $str = $str ? strtoupper(trim($str)) : '';
            Attribute::where('attr_id', $attr_id)
                ->update([
                    'color_values' => $str
                ]);

            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'attribute.php?act=list&goods_type=' . $cat_id];
            $link[] = ['text' => $GLOBALS['_LANG']['edit_attr'], 'href' => 'attribute.php?act=edit&attr_id=' . $attr_id];
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'attribute.php?act=set_gcolor&attr_id=' . $attr_id];
            return sys_msg($GLOBALS['_LANG']['attr_color_edit_success'], 0, $link);
        }
        //by zhuo end

        /*------------------------------------------------------ */
        //-- 插�        �/更新属性图片
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_img') {
            /* 检查权限 */
            admin_priv('attr_manage');

            $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $attr_name = isset($_REQUEST['attr_name']) ? $_REQUEST['attr_name'] : '';

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
        } elseif ($_REQUEST['act'] == 'insert_img') {
            /* 检查权限 */
            admin_priv('attr_manage');
            $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $attr_name = isset($_REQUEST['attr_name']) ? $_REQUEST['attr_name'] : 0;
            $attr_values = get_add_attr_values($attr_id);

            get_attrimg_insert_update($attr_id, $attr_values);

            $link[0] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => "attribute.php?act=add_img&attr_id=" . $attr_id . "&attr_name=" . $attr_name];
            $link[1] = ['text' => $GLOBALS['_LANG']['back_attr'], 'href' => 'attribute.php?act=edit&attr_id=' . $attr_id];
            $link[2] = ['text' => $GLOBALS['_LANG']['09_attribute_list'], 'href' => 'attribute.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除属性(一个或多个)
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('attr_manage');

            /* 取得要操作的编号 */
            if (isset($_POST['checkboxes'])) {
                $count = count($_POST['checkboxes']);

                $ids = $_POST['checkboxes'] ? $_POST['checkboxes'] : [];
                if ($ids) {
                    $ids = $this->baseRepository->getExplode($ids);

                    $this->attributeManageService->getAttributeDelete($ids);
                    $this->attributeManageService->getGoodsAttrDelete($ids);

                    /* 记录日志 */
                    admin_log('', 'batch_remove', 'attribute');
                }

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

        elseif ($_REQUEST['act'] == 'edit_attr_name') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 取得该属性所属商品类型id */
            $cat_id = $this->attributeManageService->getAttributeCatId($id);

            /* 检查属性名称是否重复 */
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

            if ($is_only > 0) {
                return make_json_error($GLOBALS['_LANG']['name_exist']);
            }

            $res = Attribute::where('attr_id', $id)->update([
                'attr_name' => $val
            ]);

            if ($res) {
                admin_log($val, 'edit', 'attribute');
            }

            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $res = Attribute::where('attr_id', $id)->update([
                'sort_order' => $val
            ]);

            if ($res) {
                $attr_name = $this->attributeManageService->getAttributeAttrName($id);
                admin_log(addslashes($attr_name), 'edit', 'attribute');
            }

            clear_all_files();
            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 删除商品属性
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $this->attributeManageService->getAttributeDelete($id);
            $this->attributeManageService->getGoodsAttrDelete($id);

            $url = 'attribute.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 获取某属性商品数量
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_attr_num') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['attr_id']);

            $goods_num = $this->attributeManageService->getGoodsAttrNum($id);

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
        elseif ($_REQUEST['act'] == 'get_attr_groups') {
            $check_auth = check_authz_json('attr_manage');

            if ($check_auth !== true) {
                return $check_auth;
            }

            $cat_id = intval($_GET['cat_id']);
            $groups = get_attr_groups($cat_id);

            return make_json_result($groups);
        }
    }
}
