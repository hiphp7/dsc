<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ZcCategory;
use App\Models\ZcProject;
use App\Repositories\Common\BaseRepository;
use App\Services\CrowdFund\CategoryManageService;
use App\Services\CrowdFund\CategoryService;

/**
 * 众筹分类管理
 */
class ZcCategoryController extends InitController
{
    protected $categoryService;
    protected $categoryManageService;
    protected $baseRepository;

    public function __construct(
        CategoryService $categoryService,
        CategoryManageService $categoryManageService,
        BaseRepository $baseRepository
    )
    {
        $this->categoryService = $categoryService;
        $this->categoryManageService = $categoryManageService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 分类列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限检查 */
            admin_priv('zc_category_manage');

            $parent_id = !isset($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);

            //返回上一页 start
            if (isset($_REQUEST['back_level']) && $_REQUEST['back_level'] > 0) {
                $level = intval($_REQUEST['back_level']) - 1;
                $parent_id = ZcCategory::where('cat_id', $parent_id)->value('parent_id');
                $parent_id = $parent_id ? $parent_id : 0;
            } else {
                $level = isset($_REQUEST['level']) ? intval($_REQUEST['level']) + 1 : 0;
            }
            //返回上一页 end

            $this->smarty->assign('level', $level);
            $this->smarty->assign('parent_id', $parent_id);

            /* 获取分类列表 */
            $cat_list = $this->categoryManageService->getCatLevel($parent_id, $level, 'zc_category');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_crowdfunding_cat']);
            $this->smarty->assign('action_link', ['href' => 'zc_category.php?act=add', 'text' => $GLOBALS['_LANG']['add_zc_category']]);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('cat_info', $cat_list);

            /* 列表页面 */

            return $this->smarty->display('zc_category_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);

            $cat_list = ZcCategory::where('parent_id', $parent_id)
                ->orderBy('sort_order')
                ->orderBy('cat_id');
            $cat_list = $this->baseRepository->getToArrayGet($cat_list);

            $this->smarty->assign('cat_info', $cat_list);

            return make_json_result($this->smarty->fetch('zc_category_list.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限检查 */
            admin_priv('zc_category_manage');

            $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);
            if (!empty($parent_id)) {
                set_default_filter(0, $parent_id, 0, 0, 'zc_category'); //设置默认筛选
                $this->smarty->assign('parent_category', get_every_category($parent_id, 'zc_category')); //上级分类
                $this->smarty->assign('parent_id', $parent_id); //上级分类
            } else {
                set_default_filter(0, 0, 0, 0, 'zc_category'); //设置默认筛选
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_zc_category']);
            $this->smarty->assign('action_link', ['href' => 'zc_category.php?act=list', 'text' => $GLOBALS['_LANG']['02_crowdfunding_cat']]);

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('cat_info', ['is_show' => 1, 'is_group_show' => 0, 'is_search_show' => 0, 'is_search_show_layout' => 1]); //ecmoban模板堂 --zhuo
            $this->smarty->assign('table', 'zc_category');

            /* 显示页面 */

            return $this->smarty->display('zc_category_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理添加分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 权限检查 */
            admin_priv('zc_category_manage');

            $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']); //by wu

            /* 初始化变量 */
            $cat['cat_id'] = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $cat['parent_id'] = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';

            $count = ZcCategory::where('cat_name', $cat['cat_name'])->where('parent_id', $cat['parent_id'])->count();

            if ($count > 0) {
                /* 同级别下不能有重复的分类名称 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
            }

            $cat_id = ZcCategory::insertGetId($cat);

            /* 入库的操作 */
            if ($cat_id > 0) {
                admin_log($_POST['cat_name'], 'add', 'zc_category');   // 记录管理员操作
            }

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'zc_category.php?act=add&parent_id=' . $parent_id;

            $link[1]['text'] = $GLOBALS['_LANG']['go_list'];
            $link[1]['href'] = 'zc_category.php?act=list&parent_id=' . $parent_id;

            return sys_msg(lang('admin/zc_category.classify_add_success'), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限检查 */
            admin_priv('zc_category_manage');

            $cat_id = intval($_REQUEST['cat_id']);

            $cat_info = ZcCategory::catInfo($cat_id)->first();
            $cat_info = $cat_info ? $cat_info->toArray() : [];

            //获取下拉列表 by wu start
            $this->smarty->assign('parent_id', $cat_info['parent_id']); //上级分类

            $parent_category = get_every_category($cat_info['parent_id'], 'zc_category');
            $this->smarty->assign('parent_category', $parent_category); //上级分类导航
            set_default_filter(0, $cat_info['parent_id'], 0, 0, 'zc_category'); //设置默认筛选
            //获取下拉列表 by wu end

            $this->smarty->assign('table', 'zc_category');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_zc_category']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_crowdfunding_cat'], 'href' => 'zc_category.php?act=list']);

            $this->smarty->assign('cat_info', $cat_info);
            $this->smarty->assign('form_act', 'update');

            /* 显示页面 */

            return $this->smarty->display('zc_category_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 权限检查 */
            admin_priv('zc_category_manage');

            /* 初始化变量 */
            $cat_id = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $cat['parent_id'] = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';

            /* 判断分类名是否重复 */
            $old_cat_name = isset($old_cat_name) ? $old_cat_name : '';
            if ($cat['cat_name'] != $old_cat_name) {
                if (cat_exists($cat['cat_name'], $cat['parent_id'], $cat_id)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
                }
            }

            /* 判断上级目录是否合法 */
            $children = $this->categoryService->getZcCatListChildren($cat_id);     // 获得当前分类的所有下级分类
            if (in_array($cat['parent_id'], $children)) {
                /* 选定的父类是当前分类或当前分类的下级分类 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['prev_category_wrong'], 0, $link);
            }

            ZcCategory::where('cat_id', $cat_id)->update($cat);

            /* 更新分类信息成功 */
            admin_log($_POST['cat_name'], 'edit', 'zc_category'); // 记录管理员操作

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'zc_category.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('zc_category_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 初始化分类ID并取得分类名称 */
            $cat_id = intval($_GET['id']);
            $cat_name = ZcCategory::where('cat_id', $cat_id)->value('cat_name');
            $cat_name = $cat_name ? $cat_name : '';

            /* 当前分类下是否有子分类 */
            $cat_count = ZcCategory::where('parent_id', $cat_id)->count();

            /* 当前分类下是否存在商品 */
            $goods_count = ZcProject::where('cat_id', $cat_id)->count();

            /* 如果不存在下级子分类和商品，则删除之 */
            if ($cat_count == 0 && $goods_count == 0) {
                /* 删除分类 */
                ZcCategory::where('cat_id', $cat_id)->delete();
                admin_log($cat_name, 'remove', 'zc_category');
            } else {
                return make_json_error($cat_name . ' ' . $GLOBALS['_LANG']['cat_isleaf']);
            }

            $url = 'zc_category.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 分类排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('zc_category_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            ZcCategory::where('cat_id', $id)->update([
                'sort_order' => $val
            ]);

            return make_json_result($val);
        }
    }
}
