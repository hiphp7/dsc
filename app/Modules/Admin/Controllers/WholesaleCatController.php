<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\WholesaleCat;
use App\Libraries\Pinyin;
use App\Repositories\Common\BaseRepository;

/**
 * 地区切换程序
 */
class WholesaleCatController extends InitController
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
        load_helper(['wholesale']);


        $exc = new Exchange($this->dsc->table("wholesale_cat"), $this->db, 'cat_id', 'cat_name');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 商品分类列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
            $level = isset($_REQUEST['level']) ? $_REQUEST['level'] + 1 : 0;

            if ($parent_id) {
                $cat_list = $this->wholesale_child_cat($parent_id);
                $this->smarty->assign('parent_id', $parent_id);
            } else {
                $cat_list = wholesale_cat_list(0, 0, false, 0, true, 'admin');
            }
            /* 获取分类列表 */

            $adminru = get_admin_ru_id();
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('action_link', array('href' => 'wholesale_cat.php?act=add', 'text' => $GLOBALS['_LANG']['add_wholesale_cat']));
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['wholesale_cat']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('level', $level);
            $this->smarty->assign('cat_info', $cat_list);

            //区分自营和店铺
            self_seller(basename(request()->server('PHP_SELF')));

            /* 列表页面 */
            return $this->smarty->display('wholesale_cat_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $cat_list = wholesale_cat_list(0, 0, false);
            $this->smarty->assign('cat_info', $cat_list);

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            $this->smarty->assign('ru_id', $adminru['ru_id']);
            //ecmoban模板堂 --zhuo end

            return make_json_result($this->smarty->fetch('wholesale_cat_list.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加商品分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_wholesale_cat']);
            $this->smarty->assign('action_link', array('href' => 'wholesale_cat.php?act=list', 'text' => $GLOBALS['_LANG']['wholesale_cat_list']));

            $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

            set_default_filter(0, 0, 0, 0, 'wholesale_cat'); //设置默认筛选

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('cat_info', array('is_show' => 1, 'parent_id' => $parent_id));

            $adminru = get_admin_ru_id();
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            /* 显示页面 */
            return $this->smarty->display('wholesale_cat_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商品分类添加时的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            /* 初始化变量 */
            $cat['parent_id'] = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
            $cat['keywords'] = !empty($_POST['keywords']) ? trim($_POST['keywords']) : '';
            $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
            $cat['cat_alias_name'] = !empty($_POST['cat_alias_name']) ? trim($_POST['cat_alias_name']) : '';
            $cat['show_in_nav'] = !empty($_POST['show_in_nav']) ? intval($_POST['show_in_nav']) : 0;
            $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;
            $cat['style_icon'] = !empty($_POST['style_icon']) ? trim($_POST['style_icon']) : 'other'; //分类菜单图标

            $pinyin = app(Pinyin::class)->Pinyin($cat['cat_name'], 'UTF8');
            $cat['pinyin_keyword'] = $pinyin;

            $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
            if ($this->cname_exists($cat['cat_name'], $cat['parent_id'])) {
                /* 同级别下不能有重复的分类名称 */
                return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
            }

            //上传分类菜单图标 begin
            if (!empty($_FILES['cat_icon']['name'])) {
                if ($_FILES["cat_icon"]["size"] > 200000) {
                    return sys_msg(lang('admin/wholesale_cat.img_size_upper_limit'), 0, $link);
                }

                $icon_name = explode('.', $_FILES['cat_icon']['name']);
                $key = count($icon_name);
                $type = $icon_name[$key - 1];

                if ($type != 'jpg' && $type != 'png' && $type != 'gif') {
                    return sys_msg(lang('admin/wholesale_cat.img_type_upper_limit'), 0, $link);
                }
                $imgNamePrefix = time() . mt_rand(1001, 9999);
                //文件目录
                $imgDir = storage_public("images/cat_icon");
                if (!file_exists($imgDir)) {
                    mkdir($imgDir);
                }
                //保存文件
                $imgName = $imgDir . "/" . $imgNamePrefix . '.' . $type;
                $saveDir = "images/cat_icon" . "/" . $imgNamePrefix . '.' . $type;
                move_uploaded_file($_FILES["cat_icon"]["tmp_name"], $imgName);
                $cat['cat_icon'] = $saveDir;
            }
            //上传分类菜单图标 end

            /* 入库的操作 */
            if ($this->db->autoExecute($this->dsc->table('wholesale_cat'), $cat) !== false) {
                $this->db->insert_id();

                admin_log($_POST['cat_name'], 'add', 'wholesale_cat');   // 记录管理员操作
                clear_cache_files();    // 清除缓存

                /*添加链接*/
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'wholesale_cat.php?act=add';

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[1]['href'] = 'wholesale_cat.php?act=list';

                cache()->flush();

                return sys_msg($GLOBALS['_LANG']['catadd_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑商品分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            $cat_id = intval($_REQUEST['cat_id']);

            $cat_info = WholesaleCat::where('cat_id', $cat_id);
            $cat_info = $this->baseRepository->getToArrayFirst($cat_info);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_wholesale_cat']);
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['wholesale_cat_list'], 'href' => 'wholesale_cat.php?act=list'));

            //ecmoban模板堂 --zhuo start
            $this->smarty->assign('cat_id', $cat_id);

            $adminru = get_admin_ru_id();
            $this->smarty->assign('ru_id', $adminru['ru_id']);
            //ecmoban模板堂 --zhuo end
            $this->smarty->assign('cat_info', $cat_info);
            $this->smarty->assign('form_act', 'update');

            $this->smarty->assign('parent_category', get_every_category($cat_info['parent_id'], 'wholesale_cat')); //上级分类导航
            $this->smarty->assign('cat_info', $cat_info);
            $this->smarty->assign('form_act', 'update');
            set_default_filter(0, $cat_info['parent_id'], 0, 0, 'wholesale_cat'); //设置默认筛选

            /* 显示页面 */
            return $this->smarty->display('wholesale_cat_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑商品分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            /* 初始化变量 */
            $old_cat_name = $_POST['old_cat_name'];
            $cat_id = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;

            $cat['parent_id'] = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
            $cat['keywords'] = !empty($_POST['keywords']) ? trim($_POST['keywords']) : '';
            $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
            $cat['cat_alias_name'] = !empty($_POST['cat_alias_name']) ? trim($_POST['cat_alias_name']) : '';
            $cat['show_in_nav'] = !empty($_POST['show_in_nav']) ? intval($_POST['show_in_nav']) : 0;
            $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;
            $cat['style_icon'] = !empty($_POST['style_icon']) ? trim($_POST['style_icon']) : 'other'; //分类菜单图标

            $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
            /* 判断分类名是否重复 */
            if ($cat['cat_name'] != $old_cat_name) {
                if ($this->wholesale_cat_exists($cat['cat_name'], $cat['parent_id'], $cat_id)) {
                    return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
                }
            }

            $pinyin = app(Pinyin::class)->Pinyin($cat['cat_name'], 'UTF8');
            $cat['pinyin_keyword'] = $pinyin;

            //上传分类菜单图标 begin
            if (!empty($_FILES['cat_icon']['name'])) {
                if ($_FILES["cat_icon"]["size"] > 200000) {
                    return sys_msg(lang('admin/wholesale_cat.img_size_upper_limit'), 0, $link);
                }

                $icon_name = explode('.', $_FILES['cat_icon']['name']);
                $key = count($icon_name);
                $type = $icon_name[$key - 1];

                if ($type != 'jpg' && $type != 'png' && $type != 'gif') {
                    return sys_msg(lang('admin/wholesale_cat.img_type_upper_limit'), 0, $link);
                }
                $imgNamePrefix = time() . mt_rand(1001, 9999);
                //文件目录
                $imgDir = storage_public("images/cat_icon");
                if (!file_exists($imgDir)) {
                    mkdir($imgDir);
                }
                //保存文件
                $imgName = $imgDir . "/" . $imgNamePrefix . '.' . $type;
                $saveDir = "images/cat_icon" . "/" . $imgNamePrefix . '.' . $type;
                move_uploaded_file($_FILES["cat_icon"]["tmp_name"], $imgName);
                $cat['cat_icon'] = $saveDir;
            }
            //上传分类菜单图标 end

            if ($this->db->autoExecute($this->dsc->table('wholesale_cat'), $cat, 'UPDATE', "cat_id = '$cat_id'")) {
                clear_cache_files(); // 清除缓存
                admin_log($_POST['cat_name'], 'edit', 'wholesale_cat'); // 记录管理员操作

                cache()->flush();

                /* 提示信息 */
                $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'wholesale_cat.php?act=list');
                return sys_msg($GLOBALS['_LANG']['catedit_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_sort_order') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($this->cat_update($id, array('sort_order' => $val))) {
                clear_cache_files(); // 清除缓存
                return make_json_result($val);
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $cat_id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("is_show = '$val'", $cat_id);
            clear_cache_files();
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 删除商品分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 初始化分类ID并取得分类名称 */
            $cat_id = intval($_GET['id']);
            $cat_name = $this->db->getOne('SELECT cat_name FROM ' . $this->dsc->table('wholesale_cat') . " WHERE cat_id = '$cat_id'");

            /* 当前分类下是否有子分类 */
            $cat_count = $this->db->getOne('SELECT COUNT(*) FROM ' . $this->dsc->table('wholesale_cat') . " WHERE parent_id = '$cat_id'");

            /* 当前分类下是否存在商品 */
            $goods_count = $this->db->getOne('SELECT COUNT(*) FROM ' . $this->dsc->table('wholesale') . " WHERE cat_id = '$cat_id'");

            /* 如果不存在下级子分类和商品，则删除 */
            if ($cat_count == 0 && $goods_count == 0) {
                /* 删除分类 */
                $sql = 'DELETE FROM ' . $this->dsc->table('wholesale_cat') . " WHERE cat_id = '$cat_id'";
                if ($this->db->query($sql)) {
                    clear_cache_files();
                    admin_log($cat_name, 'remove', 'wholesale_cat');
                }
            } else {
                return make_json_error($cat_name . ' ' . $GLOBALS['_LANG']['cat_isleaf']);
            }

            cache()->flush();

            $url = 'wholesale_cat.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

    /*------------------------------------------------------ */
    //-- PRIVATE FUNCTIONS
    /*------------------------------------------------------ */

    /**
     * 检查分类是否已经存在
     *
     * @param string $cat_name 分类名称
     * @param integer $parent_cat 上级分类
     * @param integer $exclude 排除的分类ID
     *
     * @return  boolean
     */
    private function wholesale_cat_exists($cat_name, $parent_cat, $exclude = 0)
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_cat') .
            " WHERE parent_id = '$parent_cat' AND cat_name = '$cat_name' AND cat_id <> '$exclude'";
        return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
    }

    /**
     * 添加商品分类
     *
     * @param integer $cat_id
     * @param array $args
     *
     * @return  mix
     */
    private function cat_update($cat_id, $args)
    {
        if (empty($args) || empty($cat_id)) {
            return false;
        }

        return $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('presale_cat'), $args, 'update', "cat_id='$cat_id'");
    }

    /**
     * 检查分类是否已经存在
     *
     * @param string $cat_name 分类名称
     * @param integer $parent_cat 上级分类
     * @param integer $exclude 排除的分类ID
     *
     * @return  boolean
     */
    private function cname_exists($cat_name, $parent_cat, $exclude = 0)
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_cat') .
            " WHERE parent_id = '$parent_cat' AND cat_name = '$cat_name' AND cat_id <> '$exclude'";
        return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
    }

    /*预售商品下级分类*/
    private function wholesale_child_cat($pid)
    {
        $sql = " SELECT cat_id,is_show, cat_name, parent_id, sort_order FROM " . $GLOBALS['dsc']->table('wholesale_cat') . " WHERE parent_id = '$pid' ";
        return $GLOBALS['db']->getAll($sql);
    }
}
