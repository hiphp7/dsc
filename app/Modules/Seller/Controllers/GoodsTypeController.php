<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Models\GoodsType;
use App\Models\GoodsTypeCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 商品类型管理程序
 */
class GoodsTypeController extends InitController
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "goods");
        $exc = new Exchange($this->dsc->table("goods_type"), $this->db, 'cat_id', 'cat_name');
        $exc_cat = new Exchange($this->dsc->table("goods_type_cat"), $this->db, 'cat_id', 'cat_name');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('current', basename(PHP_SELF, '.php'));

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '08_goods_type']);

        /*------------------------------------------------------ */
        //-- 管理界面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'manage') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['08_goods_type']);
            $this->smarty->assign('full_page', 1);

            $good_type_list = $this->get_goodstype($adminru['ru_id']);
            $good_in_type = '';

            $this->smarty->assign('goods_type_arr', $good_type_list['type']);
            $this->smarty->assign('filter', $good_type_list['filter']);
            $this->smarty->assign('record_count', $good_type_list['record_count']);
            $this->smarty->assign('page_count', $good_type_list['page_count']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($good_type_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $query = $this->db->query("SELECT a.cat_id FROM " . $this->dsc->table('attribute') . " AS a RIGHT JOIN " . $this->dsc->table('goods_attr') . " AS g ON g.attr_id = a.attr_id GROUP BY a.cat_id ORDER BY a.sort_order, a.attr_id, g.goods_attr_id");
            foreach ($query as $row) {
                if ($row['cat_id']) {
                    $good_in_type[$row['cat_id']] = 1;
                }
            }
            $this->smarty->assign('good_in_type', $good_in_type);

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['new_goods_type'], 'href' => 'goods_type.php?act=add']);
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['new_goods_type'], 'href' => 'goods_type.php?act=add', 'class' => 'icon-plus']);
                $this->smarty->assign('attr_set_up', 1);
            }
            //ecmoban模板堂 --zhuo end

            //属性分类
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['08_goods_type'], 'href' => 'goods_type.php?act=manage'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['type_cart'], 'href' => 'goods_type.php?act=cat_list'];

            $this->smarty->assign('tab_menu', $tab_menu);
            $this->smarty->assign('act_type', $_REQUEST['act']);

            return $this->smarty->display('goods_type.dwt');
        }

        /*------------------------------------------------------ */
        //-- 获得列表
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $good_type_list = $this->get_goodstype($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($good_type_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            //ecmoban模板堂 --zhuo start
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

            $this->smarty->assign('goods_type_arr', $good_type_list['type']);
            $this->smarty->assign('filter', $good_type_list['filter']);
            $this->smarty->assign('record_count', $good_type_list['record_count']);
            $this->smarty->assign('page_count', $good_type_list['page_count']);

            return make_json_result(
                $this->smarty->fetch('goods_type.dwt'),
                '',
                ['filter' => $good_type_list['filter'], 'page_count' => $good_type_list['page_count']]
            );
        }
        /*------------------------------------------------------ */
        //-- 属性分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_list') {
            admin_priv('goods_type');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['type_cart']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('full_page', 1);

            $level = empty($_REQUEST['level']) ? 1 : intval($_REQUEST['level']) + 1;

            $good_type_cat = get_typecat($level);

            /* 商家可设置属性分类开关 */
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                $this->smarty->assign('attr_set_up', 0);
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['type_cart_add'], 'href' => 'goods_type.php?act=cat_add', 'class' => 'icon-plus']);
                $this->smarty->assign('attr_set_up', 1);
            }

            $this->smarty->assign('goods_type_arr', $good_type_cat['type']);
            $this->smarty->assign('filter', $good_type_cat['filter']);
            $this->smarty->assign('record_count', $good_type_cat['record_count']);
            $this->smarty->assign('page_count', $good_type_cat['page_count']);
            //属性分类
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['08_goods_type'], 'href' => 'goods_type.php?act=manage'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['type_cart'], 'href' => 'goods_type.php?act=cat_list'];

            $this->smarty->assign('tab_menu', $tab_menu);
            $this->smarty->assign('act_type', $_REQUEST['act']);
            $this->smarty->assign('level', $level);

            return $this->smarty->display('goods_type_cat.dwt');
        }
        /*------------------------------------------------------ */
        //-- 属性分类AJAX
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_list_query') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $level = empty($_REQUEST['level']) ? 1 : intval($_REQUEST['level']);
            $good_type_cat = get_typecat($level);
            $this->smarty->assign('goods_type_arr', $good_type_cat['type']);
            $this->smarty->assign('filter', $good_type_cat['filter']);
            $this->smarty->assign('record_count', $good_type_cat['record_count']);
            $this->smarty->assign('page_count', $good_type_cat['page_count']);
            $this->smarty->assign('level', $level);

            /* 商家可设置属性分类开关 */
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                $this->smarty->assign('attr_set_up', 0);
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('attr_set_up', 1);
            }

            return make_json_result(
                $this->smarty->fetch('goods_type_cat.dwt'),
                '',
                ['filter' => $good_type_cat['filter'], 'page_count' => $good_type_cat['page_count']]
            );
        }
        /*------------------------------------------------------ */
        //-- 属性分类添加
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_add' || $_REQUEST['act'] == 'cat_edit') {
            admin_priv('goods_type');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['type_cart']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

            if ($_REQUEST['act'] == 'cat_add') {
                $type_cat['parent_id'] = $cat_tree['checked_id'] = !empty($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
                $this->smarty->assign("type_cat", $type_cat);
                $this->smarty->assign('cat_tree', $cat_tree);
            }

            //获取全部一级的分类
            $cat_level = get_type_cat_arr();
            $this->smarty->assign("cat_level", $cat_level);
            if ($cat_id > 0) {
                $sql = "SELECT cat_id ,cat_name ,parent_id ,sort_order ,level FROM" . $this->dsc->table('goods_type_cat') . "WHERE cat_id = '$cat_id' LIMIT 1";
                $type_cat = $this->db->getRow($sql);
                $cat_tree = get_type_cat_arr($type_cat['parent_id'], 2);
                $this->smarty->assign("cat_tree", $cat_tree);
                $this->smarty->assign("type_cat", $type_cat);
                $this->smarty->assign("form_act", "cat_update");
            } else {
                $this->smarty->assign("form_act", "cat_insert");
            }

            return $this->smarty->display('goods_type_cat_info.dwt');
        }
        /*------------------------------------------------------ */
        //-- 属性分类�        �库
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_insert' || $_REQUEST['act'] == 'cat_update') {
            $cat_name = !empty($_REQUEST['cat_name']) ? trim($_REQUEST['cat_name']) : '';
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $sort_order = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            //获取入库分类的层级
            if ($parent_id > 0) {
                $sql = "SELECT level FROM" . $this->dsc->table("goods_type_cat") . " WHERE cat_id = '$parent_id' LIMIT 1";
                $level = ($this->db->getOne($sql)) + 1;
            } else {
                $level = 1;
            }
            //处理入库数组
            $cat_info = [
                'cat_name' => $cat_name,
                'parent_id' => $parent_id,
                'level' => $level,
                'user_id' => $adminru['ru_id'],
                'sort_order' => $sort_order
            ];

            if ($_REQUEST['act'] == 'cat_insert') {

                /*检查是否重复*/
                $is_only = GoodsTypeCat::where('cat_name', $cat_name)
                    ->where('user_id', $adminru['ru_id'])
                    ->where('suppliers_id', 0)
                    ->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['exist_cat'], stripslashes($cat_name)), 1);
                }

                $this->db->autoExecute($this->dsc->table('goods_type_cat'), $cat_info, 'INSERT');
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'goods_type.php?act=cat_add';

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[1]['href'] = 'goods_type.php?act=cat_list';

                return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
            } else {
                /*检查是否重复*/
                $is_only = GoodsTypeCat::where('cat_name', $cat_name)
                    ->where('user_id', $adminru['ru_id'])
                    ->where('cat_id', '<>', $cat_id)
                    ->where('suppliers_id', 0)
                    ->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['exist_cat'], stripslashes($cat_name)), 1);
                }
                $this->db->autoExecute($this->dsc->table('goods_type_cat'), $cat_info, 'UPDATE', "cat_id = '$cat_id'");
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'goods_type.php?act=cat_list';
                return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
            }
        }
        /*------------------------------------------------------ */
        //-- 删除类型分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_cat') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_GET['id']);
            //判断是否存在下级
            $sql = "SELECT COUNT(*) FROM" . $this->dsc->table('goods_type_cat') . "WHERE parent_id = '$id'";
            $cat_count = $this->db->getOne($sql);
            //判断分类下是否存在类型
            $sql = "SELECT COUNT(*) FROM" . $this->dsc->table('goods_type') . "WHERE c_id = '$id'";
            $type_count = $this->db->getOne($sql);
            //如果存在下级 ，或者分类下存在类型，则不能删除
            if ($cat_count > 0 || $type_count > 0) {
                return make_json_error($GLOBALS['_LANG']['remove_prompt']);
            } else {
                $exc_cat->drop($id);
            }
            $url = 'goods_type.php?act=cat_list_query&' . str_replace('act=remove_cat', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc_cat->edit("sort_order = '$val'", $id);
            clear_cache_files();

            return make_json_result($val);
        }
        /*------------------------------------------------------ */
        //-- 修改商品类型名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_type_name') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $type_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type_name = !empty($_POST['val']) ? json_str_iconv(trim($_POST['val'])) : '';

            /* 检查名称是否重复 */
            $is_only = $exc->is_only('cat_name', $type_name, $type_id);

            if ($is_only) {
                $exc->edit("cat_name='$type_name'", $type_id);

                admin_log($type_name, 'edit', 'goods_type');

                return make_json_result(stripslashes($type_name));
            } else {
                return make_json_error($GLOBALS['_LANG']['repeat_type_name']);
            }
        }

        /*------------------------------------------------------ */
        //-- 切换启用状态
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'toggle_enabled') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("enabled='$val'", $id);

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 添加商品类型
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('goods_type');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] > 0) {
                    $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                    return sys_msg($GLOBALS['_LANG']['no_add_attr_power'], 0, $links);
                }
            }
            //ecmoban模板堂 --zhuo end

            $cat_level = get_type_cat_arr();
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['new_goods_type']);
            $this->smarty->assign('action_link', ['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['goods_type_list'], 'class' => 'icon-reply']);
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('goods_type', ['enabled' => 1]);
            $this->smarty->assign('cat_level', $cat_level);


            return $this->smarty->display('goods_type_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入商品类型
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            $goods_type['cat_name'] = $this->dscRepository->subStr($_POST['cat_name'], 60);
            $goods_type['attr_group'] = $this->dscRepository->subStr($_POST['attr_group'], 255);
            $goods_type['enabled'] = intval($_POST['enabled']);
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $goods_type['c_id'] = $parent_id;
            $goods_type['user_id'] = $adminru['ru_id'];

            /* 检查名称是否重复 */
            $is_only = GoodsType::where('cat_name', $goods_type['cat_name'])
                ->where('user_id', $adminru['ru_id'])
                ->where('suppliers_id', 0)
                ->count();

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['repeat_type_name'], 1);
            }

            $id = GoodsType::insertGetId($goods_type);

            if ($id) {
                $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                return sys_msg($GLOBALS['_LANG']['add_goodstype_success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['add_goodstype_failed'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑商品类型
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            $goods_type = $this->get_goodstype_info(intval($_GET['cat_id']));

            if (empty($goods_type)) {
                return sys_msg($GLOBALS['_LANG']['cannot_found_goodstype'], 1);
            }

            admin_priv('goods_type');

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] > 0) {
                    $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                    return sys_msg($GLOBALS['_LANG']['no_edit_attr_power'], 0, $links);
                }
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_goods_type']);
            $this->smarty->assign('action_link', ['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['goods_type_list'], 'class' => 'icon-reply']);
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('goods_type', $goods_type);

            //获取分类数组
            $cat_level = get_type_cat_arr();
            $this->smarty->assign('cat_level', $cat_level);
            $cat_tree = get_type_cat_arr($goods_type['c_id'], 2);
            $cat_tree1 = ['checked_id' => $cat_tree['checked_id']];
            if ($cat_tree['checked_id'] > 0) {
                $cat_tree1 = get_type_cat_arr($cat_tree['checked_id'], 2);
            }
            $this->smarty->assign("cat_tree", $cat_tree);
            $this->smarty->assign("cat_tree1", $cat_tree1);


            return $this->smarty->display('goods_type_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新商品类型
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $goods_type['c_id'] = $parent_id;
            $goods_type['cat_name'] = $this->dscRepository->subStr($_POST['cat_name'], 60);
            $goods_type['attr_group'] = $this->dscRepository->subStr($_POST['attr_group'], 255);
            $goods_type['enabled'] = intval($_POST['enabled']);
            $cat_id = intval($_POST['cat_id']);
            $old_groups = get_attr_groups($cat_id);

            /* 检查名称是否重复 */
            $is_only = GoodsType::where('cat_name', $goods_type['cat_name'])
                ->where('user_id', $adminru['ru_id'])
                ->where('cat_id', '<>', $cat_id)
                ->where('suppliers_id', 0)
                ->count();

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['repeat_type_name'], 1);
            }

            $res = GoodsType::where('cat_id', $cat_id)
                ->update($goods_type);

            if ($res) {
                /* 对比原来的分组 */
                $new_groups = explode("\n", str_replace("\r", '', $goods_type['attr_group']));  // 新的分组

                foreach ($old_groups as $key => $val) {
                    $found = array_search($val, $new_groups);

                    if ($found === null || $found === false) {
                        /* 老的分组没有在新的分组中找到 */
                        $this->update_attribute_group($cat_id, $key, 0);
                    } else {
                        /* 老的分组出现在新的分组中了 */
                        if ($key != $found) {
                            $this->update_attribute_group($cat_id, $key, $found); // 但是分组的key变了,需要更新属性的分组
                        }
                    }
                }

                $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                return sys_msg($GLOBALS['_LANG']['edit_goodstype_success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['edit_goodstype_failed'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除商品类型
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $name = $exc->get_name($id);

            if ($exc->drop($id)) {
                admin_log(addslashes($name), 'remove', 'goods_type');

                /* 清除该类型下的所有属性 */
                $sql = "SELECT attr_id FROM " . $this->dsc->table('attribute') . " WHERE cat_id = '$id'";
                $arr = $this->db->getCol($sql);

                $this->db->query("DELETE FROM " . $this->dsc->table('attribute') . " WHERE attr_id " . db_create_in($arr));
                $this->db->query("DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE attr_id " . db_create_in($arr));

                $url = 'goods_type.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            } else {
                return make_json_error($GLOBALS['_LANG']['remove_failed']);
            }
        } //获取下级分类
        elseif ($_REQUEST['act'] == 'get_childcat') {
            $result = ['content' => '', 'error' => ''];

            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $level = !empty($_REQUEST['level']) ? intval($_REQUEST['level']) + 1 : 0;
            $type = !empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $typeCat = !empty($_REQUEST['typeCat']) ? intval($_REQUEST['typeCat']) : 0;
            $child_cat = get_type_cat_arr($cat_id);
            if (!empty($child_cat)) {
                $result['error'] = 0;
                $this->smarty->assign('child_cat', $child_cat);
                $this->smarty->assign('level', $level);
                $this->smarty->assign('type', $type);
                $this->smarty->assign('typeCat', $typeCat);
                $result['content'] = $this->smarty->fetch("library/type_cat.lbi");
            } else {
                $result['error'] = 1;
            }
            return response()->json($result);
        } //获取指定分类下的类型
        elseif ($_REQUEST['act'] == 'get_childtype') {
            $result = ['content' => '', 'error' => ''];

            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $typeCat = !empty($_REQUEST['typeCat']) ? intval($_REQUEST['typeCat']) : 0;
            $where = "WHERE 1 ";

            if ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $where .= " AND user_id = '" . $adminru['ru_id'] . "' ";
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                $where .= " AND user_id = 0 ";
            }

            if ($cat_id > 0) {
                $cat_keys = get_type_cat_arr($cat_id, 1, 1);//获取指定分类下的所有下级分类
                $where .= " AND c_id in ($cat_keys) AND c_id != 0 ";
            }
            $sql = "SELECT cat_id,cat_name FROM" . $this->dsc->table("goods_type") . $where;
            $type_list = $this->db->getAll($sql);
            //获取分类数组下的所有类型
            $result['error'] = 0;
            $this->smarty->assign('goods_type_list', $type_list);
            $this->smarty->assign('type_html', 1);
            $this->smarty->assign('typeCat', $typeCat);
            $result['content'] = $this->smarty->fetch("library/type_cat.lbi");

            return response()->json($result);
        }
    }

    /**
     * 获得所有商品类型
     *
     * @access  public
     * @return  array
     */
    private function get_goodstype($ru_id)
    {
        //ecmoban模板堂 --zhuo start
        $where = ' WHERE 1 ';

        if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            $where .= " AND t.user_id = 0 ";
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
            $where .= " AND t.user_id = '$ru_id'";
        }
        //ecmoban模板堂 --zhuo end

        $result = get_filter();
        if ($result === false) {
            /* 过滤信息 */
            if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
                $_REQUEST['keyword'] = json_str_iconv($_REQUEST['keyword']);
            }
            $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'cat_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

            if ($filter['cat_id'] > 0) {
                $cat_keys = get_type_cat_arr($filter['cat_id'], 1, 1);
                $where .= " AND t.c_id in ($cat_keys) ";
            }
            if ($filter['keyword']) {
                $where .= " AND t.cat_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%' ";
            }

            /* 记录总数以及页数 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods_type') . " AS t " .
                "LEFT JOIN " . $this->dsc->table('attribute') . " AS a ON a.cat_id=t.cat_id " .
                $where . "GROUP BY t.cat_id ";
            $filter['record_count'] = count($this->db->getAll($sql));

            $filter = page_and_size($filter);

            /* 查询记录 */
            $sql = "SELECT t.*, COUNT(a.cat_id) AS attr_count ,gt.cat_name as gt_cat_name " .
                "FROM " . $this->dsc->table('goods_type') . " AS t " .
                "LEFT JOIN " . $this->dsc->table('attribute') . " AS a ON a.cat_id=t.cat_id " .
                "LEFT JOIN " . $this->dsc->table('goods_type_cat') . " AS gt ON gt.cat_id=t.c_id " .
                $where . "GROUP BY t.cat_id " .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                'LIMIT ' . $filter['start'] . ',' . $filter['page_size'];
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $all = $this->db->getAll($sql);

        foreach ($all as $key => $val) {
            $all[$key]['attr_group'] = strtr($val['attr_group'], ["\r" => '', "\n" => ", "]);
            $all[$key]['user_name'] = $this->merchantCommonService->getShopName($val['user_id'], 1);
        }

        return ['type' => $all, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 获得指定的商品类型的详情
     *
     * @param integer $cat_id 分类ID
     *
     * @return  array
     */
    private function get_goodstype_info($cat_id)
    {
        $sql = "SELECT * FROM " . $this->dsc->table('goods_type') . " WHERE cat_id='$cat_id'";

        return $this->db->getRow($sql);
    }

    /**
     * 更新属性的分组
     *
     * @param integer $cat_id 商品类型ID
     * @param integer $old_group
     * @param integer $new_group
     *
     * @return  void
     */
    private function update_attribute_group($cat_id, $old_group, $new_group)
    {
        $sql = "UPDATE " . $this->dsc->table('attribute') .
            " SET attr_group='$new_group' WHERE cat_id='$cat_id' AND attr_group='$old_group'";
        $this->db->query($sql);
    }
}
