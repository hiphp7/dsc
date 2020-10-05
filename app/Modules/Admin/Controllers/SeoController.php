<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Category;
use App\Models\Seo;
use App\Repositories\Common\BaseRepository;
use App\Services\Seo\SeoManageService;

/**
 * 管理SEO程序文件
 */
class SeoController extends InitController
{
    protected $baseRepository;
    protected $seoManageService;

    public function __construct(
        BaseRepository $baseRepository,
        SeoManageService $seoManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->seoManageService = $seoManageService;
    }

    public function index()
    {
        $get_seo = $this->seoManageService->getSeo();

        /* ------------------------------------------------------ */
        //-- 首页
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'index') {
            admin_priv('seo');
            $is_index = "index";
            $this->smarty->assign('seo', $get_seo);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('is_index', $is_index);
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'index']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 团购
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'group') {
            admin_priv('seo');
            $is_group = "group";
            $this->smarty->assign('seo', $get_seo);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('is_group', $is_group);
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'group']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 品牌
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'brand') {
            admin_priv('seo');
            $is_brand = "brand";
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('seo', $get_seo);
            $this->smarty->assign('is_brand', $is_brand);
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'brand']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 积分商城
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'exchage') {
            admin_priv('seo');
            $is_exchage = "exchage";
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('seo', $get_seo);
            $this->smarty->assign('is_exchage', $is_exchage);
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'exchage']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 文章
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'article') {
            admin_priv('seo');
            $is_article = "article";
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('seo', $get_seo);
            $this->smarty->assign('is_article', $is_article);
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'article']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 店铺
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'store') {
            admin_priv('seo');
            $is_store = "store";
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('seo', $get_seo);
            $this->smarty->assign('is_store', $is_store);
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'store']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods') {
            admin_priv('seo');
            $is_goods = "goods";
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('seo', $get_seo);
            $this->smarty->assign('is_goods', $is_goods);
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'goods']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 商品分类
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_cat') {
            admin_priv('seo');
            $cat_info = $this->seoManageService->getSeoCatInfo();
            $cat_id = isset($cat_info['cat_id']) ? $cat_info['cat_id'] : 0;
            $this->smarty->assign('filter_category_list', get_category_list($cat_id));
            $is_goods_cat = "goods_cat";
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('cat_info', $cat_info);
            $this->smarty->assign('is_goods_cat', $is_goods_cat);
            $this->smarty->assign('parent_category', get_every_category($cat_id));
            $this->smarty->assign('menu_select', ['action' => '06_seo', 'current' => 'goods_cat']);
            return $this->smarty->display('seo.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 信息提交
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'setting') {
            $seo = empty($_POST['seo']) ? '' : $_POST['seo'];
            if (is_array($seo)) {
                foreach ($seo as $key => $value) {
                    Seo::where('type', $key)->update($value);

                    if ($key == 'group_content') {
                        $key = 'group';
                    } elseif ($key == 'brand_list') {
                        $key = 'brand';
                    } elseif ($key == 'change_content' || $key == 'change') {
                        $key = 'exchage';
                    } elseif ($key == 'article_content') {
                        $key = 'article';
                    } elseif ($key == 'shop') {
                        $key = 'store';
                    }
                    $url = '?act=' . $key;
                }
            }

            $links = [
                ['text' => $GLOBALS['_LANG']['back_list'], 'href' => $url],
            ];

            clear_cache_files();

            return sys_msg($GLOBALS['_LANG']['update_Success'], 0, $links);
        }

        /* ------------------------------------------------------ */
        //-- 分类信息提交
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cate_setting') {
            $categroy = [];
            $category_id = empty($_POST['category_id']) ? '' : intval($_POST['category_id']);
            $categroy['cate_title'] = empty($_POST['cate_title']) ? '' : $_POST['cate_title'];
            $categroy['cate_keywords'] = empty($_POST['cate_keywords']) ? '' : $_POST['cate_keywords'];
            $categroy['cate_description'] = empty($_POST['cate_description']) ? '' : $_POST['cate_description'];

            $result = Category::where('cat_id', $category_id)->update($categroy);

            $links = [
                ['text' => $GLOBALS['_LANG']['back_list'], 'href' => '?act=goods_cat'],
            ];

            clear_cache_files();

            if ($result) {
                return sys_msg($GLOBALS['_LANG']['update_Success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['Submit_fail'], 0, $links);
            }
        }
    }
}
