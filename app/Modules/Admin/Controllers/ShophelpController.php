<?php

namespace App\Modules\Admin\Controllers;

/**
 * 帮助信息管理程序
 */
class ShophelpController extends InitController
{
    public function index()
    {

        /*初始化数据交换对象 */
        $exc_article = new Exchange($this->dsc->table("article"), $this->db, 'article_id', 'title');
        $exc_cat = new Exchange($this->dsc->table("article_cat"), $this->db, 'cat_id', 'cat_name');

        /*------------------------------------------------------ */
        //-- 列出所有文章分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_cat') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['article_add'], 'href' => 'shophelp.php?act=add']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['cat_list']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('list', $this->get_shophelp_list());


            return $this->smarty->display('shophelp_cat_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 分类下的文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_article') {
            $cat_id = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['article_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['article_add'], 'href' => 'shophelp.php?act=add&cat_id=' . $cat_id]);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('cat', article_cat_list($cat_id, true, 'cat_id', 0, "onchange=\"location.href='?act=list_article&cat_id='+this.value\""));
            $this->smarty->assign('list', $this->shophelp_article_list($cat_id));


            return $this->smarty->display('shophelp_article_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 查询分类下的文章
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query_art') {
            $cat_id = intval($_GET['cat']);

            $this->smarty->assign('list', $this->shophelp_article_list($cat_id));
            return make_json_result($this->smarty->fetch('shophelp_article_list.htm'));
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $this->smarty->assign('list', $this->get_shophelp_list());

            return make_json_result($this->smarty->fetch('shophelp_cat_list.htm'));
        }

        /*------------------------------------------------------ */
        //-- 添加文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            $cat_id = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

            /* 创建 html editor */
            create_html_editor('FCKeditor1');

            if (empty($cat_id)) {
                $selected = 0;
            } else {
                $selected = $cat_id;
            }
            $cat_list = article_cat_list($selected, true, 'cat_id', 0);
            $cat_list = str_replace('select please', $GLOBALS['_LANG']['select_plz'], $cat_list);
            $this->smarty->assign('cat_list', $cat_list);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['article_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['cat_list'], 'href' => 'shophelp.php?act=list_cat']);
            $this->smarty->assign('form_action', 'insert');
            return $this->smarty->display('shophelp_info.htm');
        }
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            $title = isset($_POST['title']) && !empty($_POST['title']) ? addslashes($_POST['title']) : '';
            $cat_id = isset($_POST['cat_id']) && !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $article_type = isset($_POST['article_type']) && !empty($_POST['article_type']) ? intval($_POST['article_type']) : 0;
            $editor = isset($_POST['FCKeditor1']) && !empty($_POST['FCKeditor1']) ? addslashes($_POST['FCKeditor1']) : '';

            /* 判断是否重名 */
            $exc_article->is_only('title', $_POST['title'], $GLOBALS['_LANG']['title_exist']);

            /* 插入数据 */
            $add_time = gmtime();
            $sql = "INSERT INTO " . $this->dsc->table('article') . "(title, cat_id, article_type, content, add_time, author) VALUES('$title', '$cat_id', '$article_type','$editor','$add_time', '_SHOPHELP' )";
            $this->db->query($sql);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'shophelp.php?act=list_article&cat_id=' . $cat_id;
            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'shophelp.php?act=add&cat_id=' . $cat_id;

            /* 清除缓存 */
            clear_cache_files();

            admin_log($_POST['title'], 'add', 'shophelp');
            return sys_msg($GLOBALS['_LANG']['articleadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            $article_id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;

            /* 取文章数据 */
            $sql = "SELECT article_id,title, cat_id, article_type, is_open, author, author_email, keywords, content FROM " . $this->dsc->table('article') . " WHERE article_id='$article_id'";
            $article = $this->db->GetRow($sql);

            /* 创建 html editor */
            create_html_editor('FCKeditor1', $article['content']);

            $this->smarty->assign('cat_list', article_cat_list($article['cat_id'], true, 'cat_id', 0));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['article_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['article_list'], 'href' => 'shophelp.php?act=list_article&cat_id=' . $article['cat_id']]);
            $this->smarty->assign('article', $article);
            $this->smarty->assign('form_action', 'update');


            return $this->smarty->display('shophelp_info.htm');
        }
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            $id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $title = isset($_POST['title']) && !empty($_POST['title']) ? addslashes($_POST['title']) : '';
            $old_title = isset($_POST['old_title']) && !empty($_POST['title']) ? addslashes($_POST['old_title']) : '';
            $cat_id = isset($_POST['cat_id']) && !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $article_type = isset($_POST['article_type']) && !empty($_POST['article_type']) ? intval($_POST['article_type']) : 0;
            $editor = isset($_POST['FCKeditor1']) && !empty($_POST['FCKeditor1']) ? addslashes($_POST['FCKeditor1']) : '';

            /* 检查重名 */
            if ($title != $old_title) {
                $exc_article->is_only('title', $title, $GLOBALS['_LANG']['articlename_exist'], $id);
            }
            /* 更新 */
            if ($exc_article->edit("title = '$title', cat_id = '$cat_id', article_type = '$article_type', content = '$editor'", $id)) {
                /* 清除缓存 */
                clear_cache_files();

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'shophelp.php?act=list_article&cat_id=' . $cat_id;

                return sys_msg(sprintf($GLOBALS['_LANG']['articleedit_succeed'], $title), 0, $link);
                admin_log($_POST['title'], 'edit', 'shophelp');
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑分类的名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_catname') {
            $check_auth = check_authz_json('shophelp_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $cat_name = json_str_iconv(trim($_POST['val']));

            /* 检查分类名称是否重复 */
            if ($exc_cat->num("cat_name", $cat_name, $id) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['catname_exist'], $cat_name));
            } else {
                if ($exc_cat->edit("cat_name = '$cat_name'", $id)) {
                    clear_cache_files();
                    admin_log($cat_name, 'edit', 'shophelpcat');
                    return make_json_result(stripslashes($cat_name));
                } else {
                    return make_json_error($this->db->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑分类的排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_cat_order') {
            $check_auth = check_authz_json('shophelp_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            /* 检查输入的值是否合法 */
            if (!preg_match("/^[0-9]+$/", $order)) {
                return make_json_result('', sprintf($GLOBALS['_LANG']['enter_int'], $order));
            } else {
                if ($exc_cat->edit("sort_order = '$order'", $id)) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('shophelp_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 非空的分类不允许删除 */
            if ($exc_article->num('cat_id', $id) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['not_emptycat']));
            } else {
                $exc_cat->drop($id);
                clear_cache_files();
                admin_log('', 'remove', 'shophelpcat');
            }

            $url = 'shophelp.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除分类下的某文章
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_art') {
            $check_auth = check_authz_json('shophelp_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            $cat_id = $this->db->getOne('SELECT cat_id FROM ' . $this->dsc->table('article') . " WHERE article_id='$id'");

            if ($exc_article->drop($id)) {
                /* 清除缓存 */
                clear_cache_files();
                admin_log('', 'remove', 'shophelp');
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['remove_fail']));
            }

            $url = 'shophelp.php?act=query_art&cat=' . $cat_id . '&' . str_replace('act=remove_art', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 添加一个新分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_catname') {
            $check_auth = check_authz_json('shophelp_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $cat_name = trim($_POST['cat_name']);

            if (!empty($cat_name)) {
                if ($exc_cat->num("cat_name", $cat_name) != 0) {
                    return make_json_error($GLOBALS['_LANG']['catname_exist']);
                } else {
                    $sql = "INSERT INTO " . $this->dsc->table('article_cat') . " (cat_name, cat_type) VALUES ('$cat_name', 0)";
                    $this->db->query($sql);

                    admin_log($cat_name, 'add', 'shophelpcat');

                    return dsc_header("Location: shophelp.php?act=query\n");
                }
            } else {
                return make_json_error($GLOBALS['_LANG']['js_languages']['no_catname']);
            }

            return dsc_header("Location: shophelp.php?act=list_cat\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑文章标题
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_title') {
            $check_auth = check_authz_json('shophelp_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $title = json_str_iconv(trim($_POST['val']));

            /* 检查文章标题是否有重名 */
            if ($exc_article->num('title', $title, $id) == 0) {
                if ($exc_article->edit("title = '$title'", $id)) {
                    clear_cache_files();
                    admin_log($title, 'edit', 'shophelp');
                    return make_json_result(stripslashes($title));
                }
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['articlename_exist'], $title));
            }
        }
    }

    /* 获得网店帮助文章分类 */
    private function get_shophelp_list()
    {
        $list = [];
        $sql = 'SELECT cat_id, cat_name, sort_order' .
            ' FROM ' . $this->dsc->table('article_cat') .
            ' WHERE cat_type = 0 ORDER BY sort_order';
        $res = $this->db->query($sql);
        foreach ($res as $rows) {
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('article') . " WHERE cat_id = '" . $rows['cat_id'] . "'";
            $rows['num'] = $this->db->getOne($sql);

            $list[] = $rows;
        }

        return $list;
    }

    /* 获得网店帮助某分类下的文章 */
    private function shophelp_article_list($cat_id)
    {
        $list = [];
        $sql = 'SELECT article_id, title, article_type , add_time' .
            ' FROM ' . $this->dsc->table('article') .
            " WHERE cat_id = '$cat_id' ORDER BY article_type DESC";
        $res = $this->db->query($sql);
        foreach ($res as $rows) {
            $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

            $list[] = $rows;
        }

        return $list;
    }
}
