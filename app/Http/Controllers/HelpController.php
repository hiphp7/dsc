<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Services\Article\ArticleCatService;
use App\Services\Article\ArticleCommonService;
use App\Services\Article\ArticleService;
use App\Services\Cart\CartService;

/**
 * 文章内容
 */
class HelpController extends InitController
{
    protected $articleService;
    protected $articleCatService;
    protected $cartService;
    protected $articleCommonService;

    public function __construct(
        ArticleService $articleService,
        ArticleCatService $articleCatService,
        CartService $cartService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->articleService = $articleService;
        $this->articleCatService = $articleCatService;
        $this->cartService = $cartService;
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {

        /*------------------------------------------------------ */
        //-- INPUT
        /*------------------------------------------------------ */
        $region_id = (int)request()->input('id', 18);
        $article_id = (int)request()->input('article_id', 0);

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        $cache_id = sprintf('%X', crc32($region_id . '-' . $GLOBALS['_CFG']['lang']));

        if (!$this->smarty->is_cached('help.dwt', $cache_id)) {

            //自提点部分
            $where = [
                'parent_id' => $region_id
            ];
            $self_point = $this->cartService->getSelfPointCart($where);

            //获取城市
            $region_name = Region::where('region_id', $region_id)->value('region_name');

            if (empty($self_point)) {
                return dsc_header("Location: ./\n");
            }

            $this->smarty->assign('self_point', $self_point);
            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('sys_categories', $this->articleCatService->getArticleCategoriesTree(2)); //系统保留文章分类树by wang
            $this->smarty->assign('custom_categories', $this->articleCatService->getArticleCategoriesTree(1)); //自定义文章分类树by wang

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp()); // 网店帮助

            $new_article = $this->articleService->getNewArticle(5);
            $this->smarty->assign('new_article', $new_article);

            $this->smarty->assign('id', $article_id);
            $this->smarty->assign('username', session('user_name'));
            $this->smarty->assign('email', session('email'));
            $this->smarty->assign('type', '1');

            /* 验证码相关设置 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }
            $article = $this->articleService->getArticleInfo($article_id);
            $kewords = $article['keywords'] ?? '';
            $description = $article['description'] ?? '';
            $article_title = $article['title'] ?? '';
            $cat_id = $article['cat_id'] ?? '';
            $this->smarty->assign('keywords', $kewords);
            $this->smarty->assign('description', $description);

            $position = assign_ur_here($cat_id, $article_title);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $region_name . $GLOBALS['_LANG']['Since_some']);  // 当前位置
            $this->smarty->assign('comment_type', 1);

            assign_dynamic('help');
            assign_template();
        }

        return $this->smarty->display('help.dwt', $cache_id);
    }
}
