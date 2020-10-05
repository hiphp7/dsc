<?php

namespace App\Modules\Admin\Controllers;

use App\Models\GoodsLib;
use App\Models\GoodsLibCat;
use App\Repositories\Common\BaseRepository;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsLibCatManageService;

/**
 * 商品分类管理程序
 */
class GoodsLibCatController extends InitController
{
    protected $categoryService;
    protected $baseRepository;
    protected $goodsLibCatManageService;

    public function __construct(
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        GoodsLibCatManageService $goodsLibCatManageService
    )
    {
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->goodsLibCatManageService = $goodsLibCatManageService;
    }

    public function index()
    {
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $adminru = get_admin_ru_id();

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '03_category_list']);
        /*------------------------------------------------------ */
        //-- 商品分类列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('goods_lib_cat');
            $parent_id = !isset($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);

            //返回上一页 start
            if (isset($_REQUEST['back_level']) && $_REQUEST['back_level'] > 0) {
                $level = $_REQUEST['back_level'] - 1;
                $parent_id = GoodsLibCat::where('cat_id', $parent_id)->value('parent_id');
                $parent_id = $parent_id ? $parent_id : 0;
            } else {
                $level = isset($_REQUEST['level']) ? $_REQUEST['level'] + 1 : 0;
            }
            //返回上一页 end

            $this->smarty->assign('level', $level);
            $this->smarty->assign('parent_id', $parent_id);

            /* 获取分类列表 */
            $cat_list = $this->goodsLibCatManageService->libGetCatLevel($parent_id, $level);

            $this->smarty->assign('cat_info', $cat_list);
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('action_link', ['href' => 'goods_lib_cat.php?act=add', 'text' => $GLOBALS['_LANG']['04_category_add']]);
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['21_goods_lib_cat']);
            $this->smarty->assign('full_page', 1);

            $cat_level = [$GLOBALS['_LANG']['num_1'], $GLOBALS['_LANG']['num_2'], $GLOBALS['_LANG']['num_3'], $GLOBALS['_LANG']['num_4'], $GLOBALS['_LANG']['num_5'], $GLOBALS['_LANG']['num_6'], $GLOBALS['_LANG']['num_7'], $GLOBALS['_LANG']['num_8'], $GLOBALS['_LANG']['num_9'], $GLOBALS['_LANG']['num_10']];
            $this->smarty->assign('cat_level', $cat_level[$level]);

            /* 列表页面 */

            return $this->smarty->display('goods_lib_cat_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 获取分类列表 */
            $cat_list = $this->goodsLibCatManageService->libGetCatLevel();
            $this->smarty->assign('cat_info', $cat_list);
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            return make_json_result($this->smarty->fetch('goods_lib_cat_list.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加商品分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限检查 */
            admin_priv('goods_lib_cat');

            $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);
            if (!empty($parent_id)) {
                set_default_filter(0, $parent_id, 0, 0, 'goods_lib_cat'); //设置默认筛选
                $this->smarty->assign('parent_category', get_every_category($parent_id, 'goods_lib_cat')); //上级分类
                $this->smarty->assign('parent_id', $parent_id); //上级分类
            } else {
                set_default_filter(0, 0, 0, 0, 'goods_lib_cat'); //设置默认筛选
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_category_add']);
            $this->smarty->assign('action_link', ['href' => 'goods_lib_cat.php?act=list', 'text' => $GLOBALS['_LANG']['03_category_list']]);

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('cat_info', ['is_show' => 1]);
            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign('lib', 'lib');

            /* 显示页面 */

            return $this->smarty->display('goods_lib_cat_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商品分类添加时的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 权限检查 */
            admin_priv('goods_lib_cat');

            /* 初始化变量 */
            $cat['cat_id'] = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $cat['parent_id'] = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $level = count(get_select_category($cat['parent_id'], 1, true)) - 2;

            if ($level > 1 && $adminru['ru_id'] == 0) {
                $link[0]['text'] = $GLOBALS['_LANG']['go_back'];

                if ($cat['cat_id'] > 0) {
                    $link[0]['href'] = 'goods_lib_cat.php?act=edit&cat_id=' . $cat['cat_id'];
                } else {
                    $link[0]['href'] = 'goods_lib_cat.php?act=add&parent_id=' . $cat['parent_id'];
                }

                return sys_msg($GLOBALS['_LANG']['cat_prompt_notic_one'], 0, $link);
            }

            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
            $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;

            $cat_count = GoodsLibCat::where('parent_id', $cat['parent_id'])->where('cat_name', $cat['cat_name'])->count();
            $cat_count = $cat_count ? $cat_count : 0;

            if ($cat_count > 0) {
                /* 同级别下不能有重复的分类名称 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
            }

            /* 入库的操作 */
            $cat_id = GoodsLibCat::insertGetId($cat);
            if ($cat_id > 0) {
                admin_log($_POST['cat_name'], 'add', 'goods_lib_cat');   // 记录管理员操作
                clear_cache_files();    // 清除缓存
                /* 添加链接 */
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'goods_lib_cat.php?act=add&parent_id=' . $cat['parent_id'];

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[1]['href'] = 'goods_lib_cat.php?act=list&parent_id=' . $cat['parent_id'] . '&level=' . $level;

                return sys_msg($GLOBALS['_LANG']['catadd_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑商品分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            admin_priv('goods_lib_cat');  // 权限检查
            $cat_id = intval($_REQUEST['cat_id']);

            // 查询分类信息数据
            $res = GoodsLibCat::where('cat_id', $cat_id);
            $cat_info = $this->baseRepository->getToArrayFirst($res);

            //获取下拉列表 by wu start
            $this->smarty->assign('parent_id', $cat_info['parent_id']); //上级分类
            $this->smarty->assign('parent_category', get_every_category($cat_info['parent_id'], 'goods_lib_cat')); //上级分类导航
            set_default_filter(0, $cat_info['parent_id'], 0, 0, 'goods_lib_cat'); //设置默认筛选
            //获取下拉列表 by wu end

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['category_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['03_category_list'], 'href' => 'goods_lib_cat.php?act=list']);

            //ecmoban模板堂 --zhuo start
            $this->smarty->assign('cat_id', $cat_id);

            $this->smarty->assign('ru_id', $adminru['ru_id']);
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('cat_info', $cat_info);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('lib', 'lib');

            /* 显示页面 */

            return $this->smarty->display('goods_lib_cat_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑商品分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 权限检查 */
            admin_priv('goods_lib_cat');

            /* 初始化变量 */
            $cat_id = $cat['cat_id'] = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;

            $cat['parent_id'] = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $level = count(get_select_category($cat['parent_id'], 1, true)) - 2;
            $old_cat_name = isset($_REQUEST['old_cat_name']) ? $_REQUEST['old_cat_name'] : '';
            if ($level > 1 && $adminru['ru_id'] == 0) {
                $link[0]['text'] = $GLOBALS['_LANG']['go_back'];

                if ($cat['cat_id'] > 0) {
                    $link[0]['href'] = 'goods_lib_cat.php?act=edit&cat_id=' . $cat['cat_id'];
                } else {
                    $link[0]['href'] = 'goods_lib_cat.php?act=add&parent_id=' . $cat['parent_id'];
                }

                return sys_msg($GLOBALS['_LANG']['cat_prompt_notic_one'], 0, $link);
            }

            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
            $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;

            /* 判断分类名是否重复 */
            if ($cat['cat_name'] != $old_cat_name) {
                if (cat_exists($cat['cat_name'], $cat['parent_id'], $cat_id)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
                }
            }

            /* 判断上级目录是否合法 */
            $children = $this->categoryService->getArrayKeysCat($cat_id);     // 获得当前分类的所有下级分类
            if (in_array($cat['parent_id'], $children)) {
                /* 选定的父类是当前分类或当前分类的下级分类 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']["is_leaf_error"], 0, $link);
            }

            $res = GoodsLibCat::where('cat_id', $cat_id)->update($cat);
            if ($res > 0) {
                clear_cache_files(); // 清除缓存
                admin_log($_POST['cat_name'], 'edit', 'goods_lib_cat'); // 记录管理员操作

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_lib_cat.php?act=list&parent_id=' . $cat['parent_id'] . '&level=' . $level];
                return sys_msg($GLOBALS['_LANG']['catedit_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('goods_lib_cat');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $res = $this->goodsLibCatManageService->catUpdate($id, ['sort_order' => $val]);

            if ($res > 0) {
                clear_cache_files(); // 清除缓存
                $result['error'] = 0;
            } else {
                $result['error'] = 1;
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_is_show') {
            $check_auth = check_authz_json('goods_lib_cat');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $res = $this->goodsLibCatManageService->catUpdate($id, ['is_show' => $val]);

            if ($res > 0) {
                clear_cache_files();
                $result['error'] = 0;
            } else {
                $result['error'] = 1;
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除分类 ajax实现删除分类后页面不刷新 //ecmoban模板堂 --kong
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('goods_lib_cat');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['error' => 0, 'massege' => '', 'level' => ''];
            /* 初始化分类ID并取得分类名称 */
            $result['level'] = $_REQUEST['level'];
            $cat_id = intval($_GET['cat_id']);
            $result['cat_id'] = $cat_id;
            $cat_name = GoodsLibCat::where('cat_id', $cat_id)->value('cat_name');
            $cat_name = $cat_name ? $cat_name : '';

            /* 当前分类下是否有子分类 */
            $cat_count = GoodsLibCat::where('parent_id', $cat_id)->count();
            $cat_count = $cat_count ? $cat_count : 0;

            /* 当前分类下是否存在商品 */
            $goods_count = GoodsLib::where('cat_id', $cat_id)->count();
            $goods_count = $goods_count ? $goods_count : 0;
            /* 如果不存在下级子分类和商品，则删除之 */
            if ($cat_count == 0 && $goods_count == 0) {
                /* 删除分类 */
                $res = GoodsLibCat::where('cat_id', $cat_id)->delete();
                if ($res > 0) {
                    clear_cache_files();
                    admin_log($cat_name, 'remove', 'goods_lib_cat');
                    $result['error'] = 1;
                }
            } else {
                $result['error'] = 2;
                $result['massege'] = $cat_name . ' ' . $GLOBALS['_LANG']['cat_isleaf'];
            }
            return response()->json($result);
        }
    }
}
