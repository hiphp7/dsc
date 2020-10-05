<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Article\ArticleCommonService;

/**
 * 首页文件
 */
class MerchantsController extends InitController
{
    protected $articleCommonService;

    public function __construct(
        ArticleCommonService $articleCommonService
    )
    {
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        $article_id = (int)request()->input('id', $GLOBALS['_CFG']['marticle_id']);

        /* 缓存编号 */
        $cache_id = sprintf('%X', crc32(session('user_rank', 0) . '-' . $GLOBALS['_CFG']['lang']));

        if (!$this->smarty->is_cached('merchants.dwt', $cache_id)) {
            assign_template();

            $position = assign_ur_here();
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $marticle = explode(',', $GLOBALS['_CFG']['marticle']);

            $article_menu1 = get_merchants_article_menu($marticle[0] ?? 0);
            $article_menu2 = get_merchants_article_menu($marticle[1] ?? 0);

            $article_info = get_merchants_article_info($article_id);

            $ad_arr = '';
            for ($i = 1; $i <= $GLOBALS['_CFG']['auction_ad']; $i++) {
                $ad_arr .= "'merch" . $i . ",";
            }

            $merchants_index_top = '';
            $merchants_index_category_ad = '';
            $merchants_index_case_ad = '';
            for ($i = 1; $i <= $GLOBALS['_CFG']['auction_ad']; $i++) {
                $merchants_index_top .= "'merchants_index_top" . $i . ","; //入驻首页头部广告
                $merchants_index_category_ad .= "'merchants_index_category_ad" . $i . ","; //入驻首页类目广告
                $merchants_index_case_ad .= "'merchants_index_case_ad" . $i . ","; //入驻首页类目广告
            }

            $this->smarty->assign('merchants_index_case_ad', $merchants_index_case_ad);
            $this->smarty->assign('merchants_index_category_ad', $merchants_index_category_ad);
            $this->smarty->assign('merchants_index_top', $merchants_index_top); // 分类广告位
            if (isset($GLOBALS['_CFG']['marticle_index']) && !empty($GLOBALS['_CFG']['marticle_index'])) {
                $marticle_index = !is_array($GLOBALS['_CFG']['marticle_index']) ? explode(",", $GLOBALS['_CFG']['marticle_index']) : $GLOBALS['_CFG']['marticle_index'];
                $articles_imp = Article::whereIn('article_id', $marticle_index)->where('is_open', 1)->get();
                $articles_imp = $articles_imp ? $articles_imp->toArray() : [];

                $this->smarty->assign('articles_imp', $articles_imp);
            }

            $this->smarty->assign('adarr', $ad_arr); // 分类广告位
            $this->smarty->assign('article', $article_info);  // 文章内容
            $this->smarty->assign('article_menu1', $article_menu1);  // 文章列表
            $this->smarty->assign('article_menu2', $article_menu2);  // 文章列表
            $this->smarty->assign('article_id', $article_id);  // 文章ID
            $this->smarty->assign('marticle', $marticle[0]);
            $this->smarty->assign('user_id', session('user_id'));

            /* 区分入驻页面样式 */
            $this->smarty->assign('footer', 2);
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

            /* 页面中的动态内容 */
            assign_dynamic('merchants');
        }

        return $this->smarty->display('merchants.dwt');
    }
}
