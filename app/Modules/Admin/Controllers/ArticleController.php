<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Article;
use App\Models\ArticleCat;
use App\Models\Comment;
use App\Models\GoodsArticle;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleManageService;

/**
 * 管理中心文章处理程序文件
 */
class ArticleController extends InitController
{
    protected $baseRepository;
    protected $articleManageService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        ArticleManageService $articleManageService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->articleManageService = $articleManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|';

        /*------------------------------------------------------ */
        //-- 文章列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);

            /* 取得过滤条件 */
            $filter = [];
            $this->smarty->assign('cat_select', article_cat_list_new(0));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_article_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['article_add'], 'href' => 'article.php?act=add']);

            if ($cat_id > 0) {
                $parent_id = ArticleCat::where('cat_id', $cat_id)->value('parent_id');
                $parent_id = $parent_id ? $parent_id : 0;

                $back_url = "articlecat.php?act=list&cat_id=" . $parent_id;
                $this->smarty->assign('back_url', $back_url);
            }

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('filter', $filter);

            $article_list = $this->articleManageService->getArticlesList();

            $this->smarty->assign('article_list', $article_list['arr']);
            $this->smarty->assign('filter', $article_list['filter']);
            $this->smarty->assign('record_count', $article_list['record_count']);
            $this->smarty->assign('page_count', $article_list['page_count']);

            $sort_flag = sort_flag($article_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('article_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('article_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $article_list = $this->articleManageService->getArticlesList();

            $this->smarty->assign('article_list', $article_list['arr']);
            $this->smarty->assign('filter', $article_list['filter']);
            $this->smarty->assign('record_count', $article_list['record_count']);
            $this->smarty->assign('page_count', $article_list['page_count']);

            $sort_flag = sort_flag($article_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $this->smarty->assign('cat_select', article_cat_list_new(0));

            return make_json_result(
                $this->smarty->fetch('article_list.dwt'),
                '',
                ['filter' => $article_list['filter'], 'page_count' => $article_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('article_manage');

            $id = isset($_GET['id']) && $_GET['id'] ? intval($_GET['id']) : 0;

            /*初始化*/
            $article = [];
            $article['is_open'] = 1;

            /* 取得分类、品牌 */
            set_default_filter(); //设置默认筛选

            /* 清理关联商品 */
            GoodsArticle::where('article_id', 0)->delete();

            $this->smarty->assign('filter_category_list', get_category_list());

            if ($id) {
                $this->smarty->assign('cur_id', $id);
            }

            $this->smarty->assign('article', $article);
            $this->smarty->assign('cat_select', article_cat_list_new(0));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['article_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['03_article_list'], 'href' => 'article.php?act=list']);
            $this->smarty->assign('form_action', 'insert');

            /* 创建 html editor */
            create_html_editor('content');

            return $this->smarty->display('article_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('article_manage');

            $target_select = !empty($_REQUEST['target_select']) ? $_REQUEST['target_select'] : [];

            $title = isset($_POST['title']) && !empty($_POST['title']) ? trim($_POST['title']) : '';
            $article_cat = isset($_POST['article_cat']) && !empty($_POST['article_cat']) ? intval($_POST['article_cat']) : '';

            $is_only = Article::where('title', $title)
                ->where('cat_id', $article_cat)
                ->count();

            /*检查是否重复*/
            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_POST['title'])), 1);
            }

            //检查是否是最下级分类
            $cat_count = ArticleCat::where('parent_id', $article_cat)->count();

            if ($cat_count > 0) {
                return sys_msg($GLOBALS['_LANG']['not_min_cat'], 1);
            }

            /* 取得文件地址 */
            $file_url = '';
            if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {
                // 检查文件格式
                if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['invalid_file']);
                }

                // 复制文件
                $res = $this->articleManageService->uploadArticleFile($_FILES['file']);
                if ($res != false) {
                    $file_url = $res;
                }
            }

            if ($file_url == '') {
                $file_url = isset($_POST['file_url']) ? $_POST['file_url'] : '';
            } else {
                $this->dscRepository->getOssAddFile([$file_url]);
            }

            /*插入数据*/
            $add_time = gmtime();
            if (empty($_POST['cat_id'])) {
                $_POST['cat_id'] = 0;
            }

            $sort_order = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;//文章排序

            $title = isset($_POST['title']) && $_POST['title'] ? addslashes(trim($_POST['title'])) : '';
            $article_cat = isset($_POST['article_cat']) && $_POST['article_cat'] ? intval($_POST['article_cat']) : '';
            $article_type = isset($_POST['article_type']) && $_POST['article_type'] ? intval($_POST['article_type']) : '';
            $is_open = isset($_POST['is_open']) && $_POST['is_open'] ? intval($_POST['is_open']) : '';
            $author = isset($_POST['author']) && $_POST['author'] ? addslashes(trim($_POST['author'])) : '';
            $author_email = isset($_POST['author_email']) && $_POST['author_email'] ? addslashes(trim($_POST['author_email'])) : '';
            $keywords = isset($_POST['keywords']) && $_POST['keywords'] ? addslashes(trim($_POST['keywords'])) : '';
            $content = isset($_POST['content']) && $_POST['content'] ? trim($_POST['content']) : '';
            $link_url = isset($_POST['link_url']) && $_POST['link_url'] ? addslashes(trim($_POST['link_url'])) : '';
            $description = isset($_POST['description']) && $_POST['description'] ? addslashes(trim($_POST['description'])) : '';

            /* 计算文章打开方式 */
            if ($file_url == '') {
                $open_type = 0;
            } else {
                $open_type = $content ? 1 : 2;
            }

            $other = [
                'title' => $title,
                'cat_id' => $article_cat,
                'article_type' => $article_type,
                'is_open' => $is_open,
                'author' => $author,
                'author_email' => $author_email,
                'keywords' => $keywords,
                'file_url' => $file_url,
                'open_type' => $open_type,
                'content' => $content,
                'link' => $link_url,
                'description' => $description,
                'sort_order' => $sort_order,
                'add_time' => $add_time
            ];

            $article_id = Article::insertGetId($other);

            /* 处理关联商品 */
            if (!empty($target_select)) {
                foreach ($target_select as $k => $val) {
                    $target_other = [
                        'goods_id' => $val,
                        'article_id' => $article_id
                    ];

                    GoodsArticle::insert($target_other);
                }
            }

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'article.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'article.php?act=list';

            if ($article_id) {
                admin_log($title, 'add', 'article');
            }

            clear_cache_files(); // 清除相关的缓存文件
            return sys_msg($GLOBALS['_LANG']['articleadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('article_manage');

            $article_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 取文章数据 */
            $article = Article::where('article_id', $article_id);
            $article = $this->baseRepository->getToArrayFirst($article);

            $article['file_url'] = $article && $article['file_url'] ? get_image_path($article['file_url']) : '';

            /* 取得关联商品 */
            $goods_list = $this->articleManageService->getArticleGoods($article_id);
            $this->smarty->assign('goods_list', $goods_list);

            if ($article && $article['cat_id'] > 0) {
                $cat_name = ArticleCat::where('cat_id', $article['cat_id'])->value('cat_name');
                $this->smarty->assign('cat_name', $cat_name);
            }

            if ($article['content']) {
                // 显示文章详情图片 （本地或OSS）
                $article['content'] = html_out($article['content']);
                $article['content'] = $this->dscRepository->getContentImgReplace($article['content']);
            }

            $this->smarty->assign('article', $article);
            $this->smarty->assign('cat_select', article_cat_list_new(0));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['article_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['03_article_list'], 'href' => 'article.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('form_action', 'update');

            /* 创建 html editor */
            $content = isset($article['content']) ? $article['content'] : '';
            create_html_editor('content', $content);

            /* 取得分类、品牌 */
            set_default_filter(); //设置默认筛选


            return $this->smarty->display('article_info.dwt');
        }

        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('article_manage');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $article_cat = isset($_POST['article_cat']) && !empty($_POST['article_cat']) ? intval($_POST['article_cat']) : 0;
            $title = isset($_POST['title']) && !empty($_POST['title']) ? addslashes($_POST['title']) : 0;

            $target_select = !empty($_REQUEST['target_select']) ? $_REQUEST['target_select'] : [];

            /*检查文章名是否相同*/
            $is_only = Article::where('title', $title)
                ->where('cat_id', $article_cat)
                ->where('article_id', '<>', $id)
                ->count();

            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($title)), 1);
            }

            //检查是否是最下级分类
            $cat_count = ArticleCat::where('parent_id', $article_cat)->count();
            if ($cat_count > 0) {
                return sys_msg($GLOBALS['_LANG']['not_min_cat'], 1);
            }

            if (empty($_POST['cat_id'])) {
                $_POST['cat_id'] = 0;
            }

            /* 取得文件地址 */
            $file_url = '';
            if (empty($_FILES['file']['error']) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {
                // 检查文件格式
                if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['invalid_file']);
                }

                // 复制文件
                $res = $this->articleManageService->uploadArticleFile($_FILES['file']);
                if ($res != false) {
                    $file_url = $res;
                }
            }

            if ($file_url == '') {
                $file_url = isset($_POST['file_url']) ? $_POST['file_url'] : '';
            } else {
                $this->dscRepository->getOssAddFile([$file_url]);
            }

            /* 计算文章打开方式 */
            if ($file_url == '') {
                $open_type = 0;
            } else {
                $open_type = isset($_POST['FCKeditor1']) && $_POST['FCKeditor1'] == '' ? 1 : 2;
            }

            /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
            $old_url = Article::where('article_id', $id)->value('file_url');
            $old_url = $old_url ? $old_url : '';

            if ($file_url != '' && $old_url != '' && $old_url != $file_url && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                dsc_unlink(storage_public($old_url));
            }

            /* 如果没有上传图片则使用原图 如果编辑时需要删除图片这里要删除 */
            if ($file_url == '') {
                $file_url = $old_url;
            }

            $_POST['FCKeditor1'] = isset($_POST['FCKeditor1']) ? $_POST['FCKeditor1'] : '';

            $sort_order = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;//文章排序

            $title = isset($_POST['title']) && $_POST['title'] ? addslashes(trim($_POST['title'])) : '';
            $article_cat = isset($_POST['article_cat']) && $_POST['article_cat'] ? intval($_POST['article_cat']) : '';
            $article_type = isset($_POST['article_type']) && $_POST['article_type'] ? intval($_POST['article_type']) : '';
            $is_open = isset($_POST['is_open']) && $_POST['is_open'] ? intval($_POST['is_open']) : '';
            $author = isset($_POST['author']) && $_POST['author'] ? addslashes(trim($_POST['author'])) : '';
            $author_email = isset($_POST['author_email']) && $_POST['author_email'] ? addslashes(trim($_POST['author_email'])) : '';
            $keywords = isset($_POST['keywords']) && $_POST['keywords'] ? addslashes(trim($_POST['keywords'])) : '';
            $content = isset($_POST['content']) && $_POST['content'] ? trim($_POST['content']) : '';
            $link_url = isset($_POST['link_url']) && $_POST['link_url'] ? addslashes(trim($_POST['link_url'])) : '';
            $description = isset($_POST['description']) && $_POST['description'] ? addslashes(trim($_POST['description'])) : '';

            $other = [
                'title' => $title,
                'cat_id' => $article_cat,
                'article_type' => $article_type,
                'is_open' => $is_open,
                'author' => $author,
                'author_email' => $author_email,
                'keywords' => $keywords,
                'file_url' => $file_url,
                'open_type' => $open_type,
                'content' => $content,
                'link' => $link_url,
                'description' => $description,
                'sort_order' => $sort_order
            ];

            $res = Article::where('article_id', $id)->update($other);

            $goods = GoodsArticle::where('article_id', $id);
            $goods = $this->baseRepository->getToArrayGet($goods);
            $goods_ids = $this->baseRepository->getKeyPluck($goods, 'goods_id');

            if (!empty($target_select)) {
                foreach ($target_select as $k => $val) {
                    if (!in_array($val, $goods_ids)) {
                        GoodsArticle::insert([
                            'goods_id' => $val,
                            'article_id' => $id
                        ]);
                    }
                }

                /*清楚删除的关联商品*/
                GoodsArticle::where('article_id', $id)
                    ->whereNotIn('goods_id', $target_select)
                    ->delete();
            }

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'article.php?act=list&' . list_link_postfix();
            $link[1]['text'] = $GLOBALS['_LANG']['back_cat_list'];
            $link[1]['href'] = 'article.php?act=list&cat_id=' . $_POST['article_cat'];

            $note = sprintf($GLOBALS['_LANG']['articleedit_succeed'], stripslashes($title));

            if ($res) {
                admin_log($_POST['title'], 'edit', 'article');
            }

            clear_cache_files();

            return sys_msg($note, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $check_auth = check_authz_json('article_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            Article::where('article_id', $id)
                ->update([
                    'is_open' => $val
                ]);

            clear_cache_files();
            return make_json_result($val);
        }


        /*------------------------------------------------------ */
        //-- 删除文章主题
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('article_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 删除原来的文件 */
            $old_url = Article::where('article_id', $id)->value('file_url');

            if ($old_url != '' && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                $this->dscRepository->getOssDelFile([$old_url]);
                dsc_unlink(storage_public($old_url));
            }

            $name = Article::where('article_id', $id)->value('title');
            $res = Article::where('article_id', $id)->delete();

            if ($res) {
                Comment::where('comment_type', 1)
                    ->where('id_value', $id)
                    ->delete();

                GoodsArticle::where('article_id', $id)->delete();

                admin_log(addslashes($name), 'remove', 'article');
                clear_cache_files();
            }

            $url = 'article.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除上传文件
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_file') {
            $article_id = isset($_GET['article_id']) ? intval($_GET['article_id']) : 0;

            $old_url = Article::where('article_id', $article_id)->value('file_url');
            if ($old_url != '' && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                dsc_unlink(storage_public($old_url));
            }

            Article::where('article_id', $article_id)->update([
                'file_url' => ''
            ]);

            /* 清除缓存 */
            clear_all_files();

            $links[] = ['text' => $GLOBALS['_LANG']['back_edit_article_content'], 'href' => "article.php?act=edit&id=$article_id"];
            return sys_msg($GLOBALS['_LANG']['delete_upload_file_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 将商品加�        ��        �联
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_link_goods') {

            $check_auth = check_authz_json('article_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $add_ids = dsc_decode($_GET['add_ids'], true);
            $args = dsc_decode($_GET['JSON'], true);
            $article_id = $args[0];

            if ($article_id == 0) {
                $article_id = Article::max('article_id');
                $article_id = $article_id ? $article_id + 1 : 1;
            }

            if ($add_ids) {
                foreach ($add_ids as $key => $val) {
                    GoodsArticle::insert([
                        'goods_id' => $val,
                        'article_id' => $article_id
                    ]);
                }
            }

            /* 重新载入 */
            $arr = $this->articleManageService->getArticleGoods($article_id);

            $opt = [];
            if ($arr) {
                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => ''];
                }
            }

            return make_json_result($opt);
        }

        /* ------------------------------------------------------ */
        //-- 编辑排序序号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('article_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            Article::where('article_id', $id)
                ->update([
                    'sort_order' => $order
                ]);

            clear_cache_files();
            return make_json_result(stripslashes($order));
        }

        /*------------------------------------------------------ */
        //-- 将商品删除�        �联
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_link_goods') {

            $check_auth = check_authz_json('article_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = dsc_decode($_GET['drop_ids'], true);
            $arguments = dsc_decode($_GET['JSON'], true);
            $article_id = $arguments[0];

            if ($article_id == 0) {
                $article_id = Article::max('article_id');
                $article_id = $article_id ? $article_id + 1 : 1;
            }

            if ($drop_goods) {
                $drop_goods = $this->baseRepository->getExplode($drop_goods);
                GoodsArticle::where('article_id', $article_id)
                    ->whereIn('goods_id', $drop_goods)
                    ->delete();
            }

            /* 重新载入 */
            $arr = $this->articleManageService->getArticleGoods($article_id);

            $opt = [];
            if ($arr) {
                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => ''];
                }
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'get_goods_list') {

            $filters = dsc_decode($_GET['JSON']);

            $arr = get_goods_list($filters);

            $opt = [];
            if ($arr) {
                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => $val['shop_price']];
                }
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {

            /* 批量删除 */
            if (isset($_POST['type']) && !empty($_POST['type'])) {
                if ($_POST['type'] == 'button_remove') {
                    admin_priv('article_manage');

                    if (!isset($_POST['checkboxes']) || empty($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    /* 删除原来的文件 */
                    $res = Article::whereIn('article_id', $_POST['checkboxes'])
                        ->where('file_url', '<>', '');

                    $res = $this->baseRepository->getToArrayGet($res);

                    if ($res) {
                        foreach ($res as $row) {
                            $old_url = $row['file_url'];
                            if (strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                                $this->dscRepository->getOssDelFile([$old_url]);
                                dsc_unlink(storage_public($old_url));
                            }
                        }
                    }

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        $name = Article::where('article_id', $id)->value('title');
                        $res = Article::where('article_id', $id)->delete();

                        if ($res) {
                            admin_log(addslashes($name), 'remove', 'article');
                        }
                    }
                }

                /* 批量隐藏 */
                if ($_POST['type'] == 'button_hide') {
                    $check_auth = check_authz_json('article_manage');
                    if ($check_auth !== true) {
                        return $check_auth;
                    }

                    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        Article::where('article_id', $id)
                            ->update([
                                'is_open' => 0
                            ]);
                    }
                }

                /* 批量显示 */
                if ($_POST['type'] == 'button_show') {
                    $check_auth = check_authz_json('article_manage');
                    if ($check_auth !== true) {
                        return $check_auth;
                    }
                    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        Article::where('article_id', $id)
                            ->update([
                                'is_open' => 1
                            ]);
                    }
                }

                /* 批量移动分类 */
                if ($_POST['type'] == 'move_to') {
                    $check_auth = check_authz_json('article_manage');
                    if ($check_auth !== true) {
                        return $check_auth;
                    }
                    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    if (!$_POST['target_cat']) {
                        return sys_msg($GLOBALS['_LANG']['no_select_act'], 1);
                    }

                    $article_id = $this->baseRepository->getExplode($_POST['checkboxes']);

                    if ($article_id) {
                        Article::whereIn('article_id', $article_id)
                            ->update([
                                'cat_id' => $_POST['target_cat'] ?? 0
                            ]);
                    }
                }
                $level = 0;
                $msg = $GLOBALS['_LANG']['batch_handle_ok'];
            } else {
                $level = 2;
                $msg = $GLOBALS['_LANG']['no_select_type'];
            }

            /* 清除缓存 */
            clear_cache_files();
            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'article.php?act=list'];
            return sys_msg($msg, $level, $lnk);
        }
    }
}
