<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Article;
use App\Models\ArticleCat;
use App\Models\Nav;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCatManageService;

/**
 * 文章分类管理程序
 */
class ArticlecatController extends InitController
{
    protected $articleCatManageService;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        ArticleCatManageService $articleCatManageService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->articleCatManageService = $articleCatManageService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        /* act操作项的初始化 */
        $_REQUEST['act'] = trim($_REQUEST['act']);
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        }

        $cat_id = request()->input('cat_id', 0);

        /*------------------------------------------------------ */
        //-- 分类列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $articlecat = $this->articleCatManageService->getArticleCatList();
            $this->smarty->assign('articlecat', $articlecat['result']);
            $this->smarty->assign('filter', $articlecat['filter']);
            $this->smarty->assign('record_count', $articlecat['record_count']);
            $this->smarty->assign('page_count', $articlecat['page_count']);
            $this->smarty->assign('cat_back', $articlecat['cat_back']);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_articlecat_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['articlecat_add'], 'href' => 'articlecat.php?act=add']);

            if ($cat_id > 0) {
                $parent_id = ArticleCat::where('cat_id', $cat_id)->value('parent_id');
                $parent_id = $parent_id ? $parent_id : 0;
                $this->smarty->assign('action_link1', ['text' => $GLOBALS['_LANG']['return_to_superior'], 'href' => 'articlecat.php?act=list&cat_id=' . $parent_id]);
            }

            return $this->smarty->display('articlecat_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $articlecat = $this->articleCatManageService->getArticleCatList();
            $this->smarty->assign('articlecat', $articlecat['result']);
            $this->smarty->assign('filter', $articlecat['filter']);
            $this->smarty->assign('record_count', $articlecat['record_count']);
            $this->smarty->assign('page_count', $articlecat['page_count']);
            $this->smarty->assign('cat_back', $articlecat['cat_back']);

            return make_json_result(
                $this->smarty->fetch('articlecat_list.dwt'),
                '',
                [
                    'filter' => $articlecat['filter'],
                    'page_count' => $articlecat['page_count'],
                    'data' => $this->smarty->get_template_vars()
                ]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('article_cat');

            if ($cat_id > 0) {
                $cat_name = ArticleCat::where('cat_id', $cat_id)->value('cat_name');
                $cat_name = $cat_name ? $cat_name : '';

                $this->smarty->assign('cat_name', $cat_name);
                $this->smarty->assign('cat_id', $cat_id);
            }

            $this->smarty->assign('cat_select', article_cat_list_new(0));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['articlecat_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_articlecat_list'], 'href' => 'articlecat.php?act=list']);
            $this->smarty->assign('form_action', 'insert');

            return $this->smarty->display('articlecat_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('article_cat');

            $cat_name = isset($_POST['cat_name']) && !empty($_POST['cat_name']) ? addslashes(trim($_POST['cat_name'])) : '';
            $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

            /*检查分类名是否重复*/
            $is_only = ArticleCat::where('cat_name', $cat_name)->count();

            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['catname_exist'], stripslashes($_POST['cat_name'])), 1);
            }

            $cat_type = 1;
            if ($_POST['parent_id'] > 0) {
                $p_cat_type = ArticleCat::where('cat_id', $parent_id)->value('cat_type');
                $p_cat_type = $p_cat_type ? $p_cat_type : 0;

                if ($p_cat_type == 2 || $p_cat_type == 3 || $p_cat_type == 5) {
                    return sys_msg($GLOBALS['_LANG']['not_allow_add'], 0);
                } elseif ($p_cat_type == 4) {
                    $cat_type = 5;
                }
            }

            $other = [
                'cat_name' => $_POST['cat_name'] ?? '',
                'cat_type' => $cat_type,
                'cat_desc' => $_POST['cat_desc'] ?? '',
                'keywords' => $_POST['keywords'] ?? '',
                'parent_id' => $parent_id,
                'sort_order' => $_POST['sort_order'] ?? 0,
                'show_in_nav' => $_POST['show_in_nav'] ?? 0
            ];
            $cat_id = ArticleCat::insertGetId($other);

            if ($_POST['show_in_nav'] == 1) {
                $vieworder = Nav::where('type', 'middle')->max('vieworder');
                $vieworder += 2;

                //显示在自定义导航栏中
                $url = $this->dscRepository->buildUri('article_cat', ['acid' => $cat_id], $_POST['cat_name']);
                $other = [
                    'name' => $_POST['cat_name'],
                    'ctype' => 'a',
                    'cid' => $cat_id,
                    'ifshow' => 1,
                    'vieworder' => $vieworder,
                    'opennew' => 0,
                    'url' => $url,
                    'type' => 'middle'
                ];
                Nav::insert($other);
            }

            admin_log($_POST['cat_name'], 'add', 'articlecat');

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'articlecat.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'articlecat.php?act=list&cat_id=' . $_POST['parent_id'];
            clear_cache_files();
            return sys_msg($_POST['cat_name'] . $GLOBALS['_LANG']['catadd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑文章分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('article_cat');

            $cat_id = request()->get('id', 0);

            $cat = ArticleCat::where('cat_id', $cat_id);
            $cat = $this->baseRepository->getToArrayFirst($cat);

            if ($cat) {
                if ($cat['cat_type'] == 2 || $cat['cat_type'] == 3 || $cat['cat_type'] == 4) {
                    $this->smarty->assign('disabled', 1);
                }

                if ($cat['parent_id'] > 0) {
                    $cat_name = ArticleCat::where('cat_id', $cat['parent_id'])->value('cat_name');
                    $cat_name = $cat_name ? $cat_name : '';

                    $this->smarty->assign('cat_name', $cat_name);
                }
            }

            $options = article_cat_list_new(0);

            $this->smarty->assign('cat', $cat);
            $this->smarty->assign('cat_select', $options);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['articlecat_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_articlecat_list'], 'href' => 'articlecat.php?act=list']);
            $this->smarty->assign('form_action', 'update');


            return $this->smarty->display('articlecat_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('article_cat');

            $cat_name = isset($_POST['cat_name']) && !empty($_POST['cat_name']) ? addslashes(trim($_POST['cat_name'])) : '';
            $old_catname = isset($_POST['old_catname']) && !empty($_POST['old_catname']) ? addslashes(trim($_POST['old_catname'])) : '';
            $cat_id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

            /*检查重名*/
            if ($cat_name != $old_catname) {
                $count = ArticleCat::where('cat_name', $cat_name)->where('cat_id', '<>', $cat_id)->count();

                if ($count > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['catname_exist'], stripslashes($cat_name)), 1);
                }
            }

            if (!isset($_POST['parent_id'])) {
                $parent_id = 0;
            }

            $row = ArticleCat::where('cat_id', $cat_id);
            $row = $this->baseRepository->getToArrayFirst($row);

            $cat_type = $row['cat_type'] ?? 0;
            if ($cat_type == 3 || $cat_type == 4) {
                $parent_id = $row['parent_id'];
            }

            /* 检查设定的分类的父分类是否合法 */
            if ($cat_id == $parent_id) {
                return sys_msg(sprintf($GLOBALS['_LANG']['parent_id_err'], stripslashes($_POST['cat_name'])), 1);
            }

            if ($cat_type == 1 || $cat_type == 5) {
                if ($_POST['parent_id'] > 0) {
                    $p_cat_type = ArticleCat::where('cat_id', $parent_id)->value('cat_type');
                    $p_cat_type = $p_cat_type ? $p_cat_type : 0;

                    if ($p_cat_type == 4) {
                        $cat_type = 5;
                    } else {
                        $cat_type = 1;
                    }
                } else {
                    $cat_type = 1;
                }
            }

            $dat = ArticleCat::where('cat_id', $cat_id);
            $dat = $this->baseRepository->getToArrayFirst($dat);

            $other = [
                'cat_name' => $_POST['cat_name'],
                'cat_desc' => $_POST['cat_desc'],
                'keywords' => $_POST['keywords'],
                'parent_id' => intval($_POST['parent_id']),
                'cat_type' => $cat_type,
                'sort_order' => intval($_POST['sort_order']),
                'show_in_nav' => intval($_POST['show_in_nav'])
            ];

            $res = ArticleCat::where('cat_id', $cat_id)->update($other);

            if ($res) {
                if (!empty($dat['cat_name'])) {
                    if ($cat_name != $dat['cat_name']) {
                        //如果分类名称发生了改变
                        Nav::where('ctype', 'a')
                            ->where('cid', $cat_id)
                            ->where('type', 'middle')
                            ->update([
                                'name' => $cat_name
                            ]);
                    }
                }
                if (!empty($dat['show_in_nav'])) {
                    if ($_POST['show_in_nav'] != $dat['show_in_nav']) {
                        if ($_POST['show_in_nav'] == 1) {
                            //显示
                            $nid = Nav::where('ctype', 'a')
                                ->where('cid', $cat_id)
                                ->where('type', 'middle')
                                ->value('id');
                            $nid = $nid ? $nid : 0;
                            if (empty($nid)) {
                                $vieworder = Nav::where('type', 'middle')->max('vieworder');
                                $vieworder = $vieworder ? $vieworder : 0;
                                $vieworder += 2;
                                $uri = $this->dscRepository->buildUri('article_cat', ['acid' => $cat_id], $cat_name);

                                //不存在
                                $other = [
                                    'name' => $cat_name,
                                    'ctype' => 'a',
                                    'cid' => $cat_id,
                                    'ifshow' => 1,
                                    'vieworder' => $vieworder,
                                    'opennew' => 0,
                                    'url' => $uri,
                                    'type' => 'middle'
                                ];
                                Nav::insert($other);
                            } else {
                                Nav::where('ctype', 'a')
                                    ->where('cid', $cat_id)
                                    ->where('type', 'middle')
                                    ->update([
                                        'ifshow' => 1
                                    ]);
                            }
                        } else {
                            //去除
                            Nav::where('ctype', 'a')
                                ->where('cid', $cat_id)
                                ->where('type', 'middle')
                                ->update([
                                    'ifshow' => 0
                                ]);
                        }
                    }
                }

                admin_log($_POST['cat_name'], 'edit', 'articlecat');
            }

            if ($_POST['parent_id'] > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'articlecat.php?act=list&cat_id=' . intval($_POST['parent_id'])];
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'articlecat.php?act=list&uselastfilter=1'];
            }

            $note = sprintf($GLOBALS['_LANG']['catedit_succed'], $cat_name);

            clear_cache_files();
            return sys_msg($note, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑文章分类的排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('article_cat');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            /* 检查输入的值是否合法 */
            if (!preg_match("/^[0-9]+$/", $order)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['enter_int'], $order));
            } else {
                ArticleCat::where('cat_id', $id)
                    ->update([
                        'sort_order' => $order
                    ]);

                clear_cache_files();
                return make_json_result(stripslashes($order));
            }
        }
        /*------------------------------------------------------ */
        //-- 删除文章分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('article_cat');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            $cat_type = ArticleCat::where('cat_id', $id)->value('cat_type');
            $cat_type = $cat_type ? $cat_type : 0;

            if ($cat_type == 2 || $cat_type == 3 || $cat_type == 4) {
                /* 系统保留分类，不能删除 */
                return make_json_error($GLOBALS['_LANG']['not_allow_remove']);
            }

            $count = ArticleCat::where('parent_id', $id)->count();
            if ($count > 0) {
                /* 还有子分类，不能删除 */
                return make_json_error($GLOBALS['_LANG']['is_fullcat']);
            }

            /* 非空的分类不允许删除 */
            $count = Article::where('cat_id', $id)->count();
            if ($count > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['not_emptycat']));
            } else {
                $cat_name = ArticleCat::where('cat_id', $id)->value('cat_name');
                $cat_name = $cat_name ? $cat_name : '';
                ArticleCat::where('cat_id', $id)->delete();

                Nav::where('ctype', 'a')
                    ->where('cid', $id)
                    ->where('type', 'middle')
                    ->delete();

                clear_cache_files();
                admin_log($cat_name, 'remove', 'category');
            }

            $url = 'articlecat.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 切换是否显示在导航栏
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_show_in_nav') {
            $check_auth = check_authz_json('cat_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $res = ArticleCat::where('cat_id', $id)
                ->update(['show_in_nav' => $val]);

            if ($res) {
                if ($val == 1) {
                    //显示
                    $count = Nav::where('ctype', 'a')
                        ->where('cid', $id)
                        ->where('type', 'middle')
                        ->count();

                    if ($count > 0) {
                        Nav::where('ctype', 'a')
                            ->where('cid', $id)
                            ->where('type', 'middle')
                            ->update([
                                'ifshow' => 1
                            ]);
                    } else {
                        //不存在
                        $vieworder = Nav::where('type', 'middle')->max('vieworder');
                        $vieworder = $vieworder ? $vieworder : 0;
                        $vieworder += 2;
                        $catname = ArticleCat::where('cat_id', $id)->value('cat_name');
                        $catname = $catname ? $catname : '';
                        $uri = $this->dscRepository->buildUri('article_cat', ['acid' => $id], $catname);

                        $other = [
                            'name' => $catname,
                            'ctype' => 'a',
                            'cid' => $id,
                            'ifshow' => 1,
                            'vieworder' => $vieworder,
                            'opennew' => 0,
                            'url' => $uri,
                            'type' => 'middle'
                        ];
                        Nav::insert($other);
                    }
                } else {
                    //去除
                    Nav::where('ctype', 'a')
                        ->where('cid', $id)
                        ->where('type', 'middle')
                        ->update([
                            'ifshow' => 0
                        ]);
                }
            }

            clear_cache_files();
            return make_json_result($val);
        }
    }
}
