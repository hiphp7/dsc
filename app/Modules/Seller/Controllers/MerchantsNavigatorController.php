<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;

/**
 * 程序说明
 */
class MerchantsNavigatorController extends InitController
{
    protected $categoryService;
    protected $dscRepository;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository
    )
    {
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "index");
        //admin_priv('navigator');

        $exc = new Exchange($this->dsc->table("merchants_nav"), $this->db, 'id', 'name');

        $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '04_merchants_basic_nav']);

        $adminru = get_admin_ru_id();
        /*------------------------------------------------------ */
        //-- 自定义导航栏列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_store_other');//by kong
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'merchants_navigator.php?act=add', 'class' => 'icon-plus']);
            $this->smarty->assign('full_page', 1);

            $navdb = $this->get_nav();

            //ecmoban模板堂 --zhuo start
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            //ecmoban模板堂 --zhuo end

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($navdb, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('navdb', $navdb['navdb']);
            $this->smarty->assign('filter', $navdb['filter']);
            $this->smarty->assign('record_count', $navdb['record_count']);
            $this->smarty->assign('page_count', $navdb['page_count']);


            $this->smarty->assign('current', 'merchants_navigator');
            return $this->smarty->display('store_navigation.dwt');
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏列表Ajax
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $navdb = $this->get_nav();

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($navdb, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            //ecmoban模板堂 --zhuo start
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('navdb', $navdb['navdb']);
            $this->smarty->assign('filter', $navdb['filter']);
            $this->smarty->assign('record_count', $navdb['record_count']);
            $this->smarty->assign('page_count', $navdb['page_count']);

            $sort_flag = sort_flag($navdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $this->smarty->assign('current', 'merchants_navigator');
            return make_json_result($this->smarty->fetch('store_navigation.dwt'), '', ['filter' => $navdb['filter'], 'page_count' => $navdb['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏增加
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'add'];

                $sysmain = $this->get_sysnav();

                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'merchants_navigator.php?act=list', 'class' => 'icon-reply']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);

                $this->smarty->assign('sysmain', $sysmain);
                $this->smarty->assign('rt', $rt);
                $this->smarty->assign('current', 'merchants_navigator');
                return $this->smarty->display('store_navigation_add.dwt');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'];
                $item_url = $_REQUEST['item_url'];
                $item_ifshow = $_REQUEST['item_ifshow'];
                $item_opennew = $_REQUEST['item_opennew'];
                $item_type = $_REQUEST['item_type'];
                $item_catId = trim($_REQUEST['item_catId']);
                $item_catId = intval($item_catId);

                $vieworder = $this->db->getOne("SELECT max(vieworder) FROM " . $this->dsc->table('merchants_nav') . " WHERE type = '" . $item_type . "'");

                $item_vieworder = empty($_REQUEST['item_vieworder']) ? $vieworder + 1 : $_REQUEST['item_vieworder'];
                $sql = '';
                if ($item_ifshow == 1 && $item_type == 'middle') {
                    //如果设置为在中部显示

                    $arr = $this->analyse_uri($item_url);  //分析URI
                    if ($arr) {
                        //如果为分类
                        $this->set_show_in_nav($arr['type'], $arr['id'], 1);   //设置显示
                        $sql = "INSERT INTO " . $this->dsc->table('merchants_nav') . " (name,ctype,cid,ifshow,cat_id,vieworder,opennew,url,type,ru_id) VALUES('$item_name','" . $arr['type'] . "','" . $arr['id'] . "','$item_ifshow','$item_catId','$item_vieworder','$item_opennew','$item_url','$item_type', '" . $adminru['ru_id'] . "')";
                    }
                }

                if (empty($sql)) {
                    $sql = "INSERT INTO " . $this->dsc->table('merchants_nav') . " (name,ifshow,vieworder,opennew,url,type,ru_id) VALUES('$item_name','$item_ifshow','$item_vieworder','$item_opennew','$item_url','$item_type', '" . $adminru['ru_id'] . "')";
                }
                $this->db->query($sql);
                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'merchants_navigator.php?act=list'];
                $links[] = ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'merchants_navigator.php?act=add'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $id = $_REQUEST['id'];
            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'edit', 'id' => $id];
                $row = $this->db->getRow("SELECT * FROM " . $this->dsc->table('merchants_nav') . " WHERE id='$id'");
                $rt['item_name'] = $row['name'];
                $rt['item_url'] = $row['url'];
                $rt['item_vieworder'] = $row['vieworder'];
                $rt['item_ifshow_' . $row['ifshow']] = 'checked="true"';
                $rt['item_opennew_' . $row['opennew']] = 'checked="true"';
                $rt['item_type_' . $row['type']] = 'selected';
                $rt['item_catId'] = $row['cat_id'];

                $sysmain = $this->get_sysnav();

                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'merchants_navigator.php?act=list', 'class' => 'icon-reply']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);

                $this->smarty->assign('sysmain', $sysmain);
                $this->smarty->assign('rt', $rt);
                $this->smarty->assign('current', 'merchants_navigator');
                return $this->smarty->display('store_navigation_add.dwt');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'];
                $item_url = $_REQUEST['item_url'];
                $item_ifshow = $_REQUEST['item_ifshow'];
                $item_opennew = $_REQUEST['item_opennew'];
                $item_type = $_REQUEST['item_type'];
                $item_vieworder = (int)$_REQUEST['item_vieworder'];
                $item_catId = trim($_REQUEST['item_catId']);
                $item_catId = intval($item_catId);

                $row = $this->db->getRow("SELECT ctype,cid,ifshow,type FROM " . $this->dsc->table('merchants_nav') . " WHERE id = '$id'");
                $arr = $this->analyse_uri($item_url);

                if ($arr) {
                    //目标为分类
                    if ($row['ctype'] == $arr['type'] && $row['cid'] == $arr['id']) {
                        //没有修改分类
                        if ($item_type != 'middle') {
                            //位置不在中部
                            $this->set_show_in_nav($arr['type'], $arr['id'], 0);
                        }
                    } else {
                        //修改了分类
                        if ($row['ifshow'] == 1 && $row['type'] == 'middle') {
                            //原来在中部显示
                            $this->set_show_in_nav($row['ctype'], $row['cid'], 0); //设置成不显示
                        } elseif ($row['ifshow'] == 0 && $row['type'] == 'middle') {
                            //原来不显示
                        }
                    }

                    //分类判断
                    if ($item_ifshow != $this->is_show_in_nav($arr['type'], $arr['id']) && $item_type == 'middle') {
                        $this->set_show_in_nav($arr['type'], $arr['id'], $item_ifshow);
                    }
                    $sql = "UPDATE " . $this->dsc->table('merchants_nav') .
                        " SET name='$item_name',ctype='" . $arr['type'] . "',cid='" . $arr['id'] . "',ifshow='$item_ifshow',vieworder='$item_vieworder',opennew='$item_opennew',url='$item_url',type='$item_type' WHERE id='$id'";
                } else {
                    //目标不是分类
                    if ($row['ctype'] && $row['cid']) {
                        //原来是分类
                        $this->set_show_in_nav($row['ctype'], $row['cid'], 0);
                    }

                    $sql = "UPDATE " . $this->dsc->table('merchants_nav') .
                        " SET name='$item_name',ctype='',cid='',ifshow='$item_ifshow',cat_id='$item_catId',vieworder='$item_vieworder',opennew='$item_opennew',url='$item_url',type='$item_type' WHERE id='$id'";
                }


                $this->db->query($sql);
                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'merchants_navigator.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            $id = (int)$_GET['id'];
            $row = $this->db->getRow("SELECT ctype,cid,type FROM " . $this->dsc->table('merchants_nav') . " WHERE id = '$id' LIMIT 1");

            if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->set_show_in_nav($row['ctype'], $row['cid'], 0);
            }

            $sql = " DELETE FROM " . $this->dsc->table('merchants_nav') . " WHERE id='$id' LIMIT 1";
            $this->db->query($sql);
            clear_cache_files();
            return dsc_header("Location: merchants_navigator.php?act=list\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            /*$check_auth = check_authz_json('nav');
            if ($check_auth !== true) {
                return $check_auth;
            }*/

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            /* 检查输入的值是否合法 */
            if (!preg_match("/^[0-9]+$/", $order)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['enter_int'], $order));
            } else {
                if ($exc->edit("vieworder = '$order'", $id)) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error($this->db->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_ifshow') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $row = $this->db->getRow("SELECT type,ctype,cid FROM " . $this->dsc->table('merchants_nav') . " WHERE id = '$id' LIMIT 1");

            if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->set_show_in_nav($row['ctype'], $row['cid'], $val);
            }

            if ($this->nav_update($id, ['ifshow' => $val]) != false) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否新窗口
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_opennew') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($this->nav_update($id, ['opennew' => $val]) != false) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($this->db->error());
            }
        }
    }

    /**
     * 系统设置分类
     */
    private function get_nav()
    {
        $adminru = get_admin_ru_id();

        $where = '';
        if ($adminru['ru_id'] > 0) {
            $where .= ' where ru_id = ' . $adminru['ru_id'];
        }

        $result = get_filter();
        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'type DESC, vieworder' : 'type DESC, ' . trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT count(*) FROM " . $this->dsc->table('merchants_nav') . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT id, name, ifshow, vieworder, opennew, url, type, ru_id" .
                " FROM " . $this->dsc->table('merchants_nav') . $where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $navdb = $this->db->getAll($sql);

        $type = "";
        $navdb2 = [];
        foreach ($navdb as $k => $v) {
            if (!empty($type) && $type != $v['type']) {
                $navdb2[] = [];
            }
            $navdb2[] = $v;

            $data = ['shoprz_brandName', 'shop_class_keyWords', 'shopNameSuffix'];
            $shop_info = get_table_date('merchants_shop_information', "user_id = '" . $v['ru_id'] . "'", $data);
            $navdb2[$k]['user_name'] = $shop_info['shoprz_brandName'] . $shop_info['shopNameSuffix'];

            $type = $v['type'];
        }

        $arr = ['navdb' => $navdb2, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*------------------------------------------------------ */
    //-- 排序相�    �
    /*------------------------------------------------------ */
    private function sort_nav($a, $b)
    {
        return $a['vieworder'] > $b['vieworder'] ? 1 : -1;
    }

    /*------------------------------------------------------ */
    //-- 获得系统列表
    /*------------------------------------------------------ */
    private function get_sysnav()
    {
        $adminru = get_admin_ru_id();


        $catlist = $this->categoryService->catList(0, 0, 0, 'merchants_category', [], 0, $adminru['ru_id']);
        foreach ($catlist as $key => $val) {
            $val['url'] = $this->dscRepository->buildUri('merchants_store', ['cid' => $val['cat_id'], 'urid' => $adminru['ru_id']], $val['cat_name']);
            $sysmain[] = [
                'cat_id' => $val['cat_id'],
                'cat_name' => $val['cat_name'],
                'url' => $val['url']
            ];
        }

        return $sysmain;
    }

    /*------------------------------------------------------ */
    //-- 列表项修改
    /*------------------------------------------------------ */
    private function nav_update($id, $args)
    {
        if (empty($args) || empty($id)) {
            return false;
        }

        return $this->db->autoExecute($this->dsc->table('merchants_nav'), $args, 'update', "id='$id'");
    }

    /*------------------------------------------------------ */
    //-- 根据URI对导航栏项目进行分析，确定�    �为商品分类还是文章分类
    /*------------------------------------------------------ */
    private function analyse_uri($uri)
    {
        $uri = strtolower(str_replace('&amp;', '&', $uri));
        $arr = explode('-', $uri);
        switch ($arr[0]) {
            case 'category':
                return ['type' => 'c', 'id' => $arr[1]];
                break;
            case 'article_cat':
                return ['type' => 'a', 'id' => $arr[1]];
                break;
            default:

                break;
        }

        list($fn, $pm) = explode('?', $uri);

        if (strpos($uri, '&') === false) {
            $arr = [$pm];
        } else {
            $arr = explode('&', $pm);
        }
        switch ($fn) {
            case 'category.php':
                //商品分类
                foreach ($arr as $k => $v) {
                    list($key, $val) = explode('=', $v);
                    if ($key == 'id') {
                        return ['type' => 'c', 'id' => $val];
                    }
                }
                break;
            case 'article_cat.php':
                //文章分类
                foreach ($arr as $k => $v) {
                    list($key, $val) = explode('=', $v);
                    if ($key == 'id') {
                        return ['type' => 'a', 'id' => $val];
                    }
                }
                break;
            default:
                //未知
                return false;
                break;
        }
    }

    /*------------------------------------------------------ */
    //-- 是否显示
    /*------------------------------------------------------ */
    private function is_show_in_nav($type, $id)
    {
        if ($type == 'c') {
            $tablename = $this->dsc->table('category');
        } else {
            $tablename = $this->dsc->table('article_cat');
        }
        return $this->db->getOne("SELECT show_in_nav FROM $tablename WHERE cat_id = '$id'");
    }

    /*------------------------------------------------------ */
    //-- 设置是否显示
    /*------------------------------------------------------ */
    private function set_show_in_nav($type, $id, $val)
    {
        if ($type == 'c') {
            $tablename = $this->dsc->table('category');
        } else {
            $tablename = $this->dsc->table('article_cat');
        }
        $this->db->query("UPDATE $tablename SET show_in_nav = '$val' WHERE cat_id = '$id'");
        clear_cache_files();
    }
}
