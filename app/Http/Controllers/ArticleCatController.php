<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCat;
use App\Services\Article\ArticleCatService;
use App\Services\Article\ArticleCommonService;
use App\Services\Article\ArticleService;
use App\Services\Goods\GoodsService;

/**
 * 文章分类
 */
class ArticleCatController extends InitController
{
    protected $goodsService;
    protected $articleCatService;
    protected $config;
    protected $articleCommonService;
    protected $articleService;

    public function __construct(
        GoodsService $goodsService,
        ArticleCatService $articleCatService,
        ArticleCommonService $articleCommonService,
        ArticleService $articleService
    )
    {
        $this->goodsService = $goodsService;
        $this->articleCatService = $articleCatService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->articleService = $articleService;
    }

    public function index()
    {

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();
        $id = intval(request()->input('id', 0));
        $category = intval(request()->input('category', 0));
        $article_id = intval(request()->input('article_id', 0));

        /*------------------------------------------------------ */
        //-- INPUT
        /*------------------------------------------------------ */

        /* 获得指定的分类ID */
        if (!empty($id)) {
            $cat_id = $id;
        } elseif (!empty($category)) {
            $cat_id = $category;
        } elseif (!empty($article_id)) {
            $cat_id = Article::where('article_id', $article_id)->value('cat_id');

            if (!$cat_id) {
                return redirect("/");
            }
        } else {
            return redirect('/');
        }

        /* 获得当前页码 */
        $page = intval(request()->input('page', 1));
        /* 获得文章总数 */
        $size = isset($this->config['article_page_size']) && intval($this->config['article_page_size']) > 0 ? intval($this->config['article_page_size']) : 20;

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        /* 获得页面的缓存ID */
        $keywords = addslashes(htmlspecialchars(urldecode(trim((request()->input('keywords', ''))))));

        $cache_id = sprintf('%X', crc32($keywords . '-' . $cat_id . '-' . $page . '-' . $cat_id . '-' . $warehouse_id . '-' . $area_id . '-' . $area_city . '-' . $size . '-' . $page . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
        $content = cache()->remember('article_cat.dwt.' . $cache_id, $this->config['cache_time'], function () use ($cat_id, $warehouse_id, $area_id, $area_city, $size, $page) {
            /* 如果页面没有被缓存则重新获得页面的内容 */
            assign_template('a', [$cat_id]);
            $position = assign_ur_here($cat_id);
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            $this->smarty->assign('ur_here', $position['ur_here']);   // 当前位置

            $sys_categories = $this->articleCatService->getArticleCategoriesTree(0, 2);
            $custom_categories = $this->articleCatService->getArticleCategoriesTree(0, 1);

            $this->smarty->assign('sys_categories', $sys_categories); //系统保留文章分类树by wang
            $this->smarty->assign('custom_categories', $custom_categories); //自定义文章分类树by wang

            /**
             * Start
             *
             * 商品推荐
             * 【'best' ：精品, 'new' ：新品, 'hot'：热销】
             */

            $where = [
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];

            /* 最新商品 */
            $where['type'] = 'new';
            $new_goods = $this->goodsService->getRecommendGoods($where);

            /* 推荐商品 */
            $where['type'] = 'best';
            $best_goods = $this->goodsService->getRecommendGoods($where);

            /* 热卖商品 */
            $where['type'] = 'hot';
            $hot_goods = $this->goodsService->getRecommendGoods($where);

            $this->smarty->assign('new_goods', $new_goods);
            $this->smarty->assign('best_goods', $best_goods);
            $this->smarty->assign('hot_goods', $hot_goods);

            /* 特价商品 */
            $promotion_goods = $this->goodsService->getPromoteGoods($where);
            $this->smarty->assign('promotion_goods', $promotion_goods);
            /* End */

            /* 分章分类信息 */
            $ArticleCatInfo = ArticleCat::select('keywords', 'cat_name', 'cat_desc', 'cat_type')->where('cat_id', $cat_id)->first();

            if ($ArticleCatInfo === false || empty($ArticleCatInfo)) {
                /* 如果没有找到任何记录则返回首页 */
                return redirect('/');
            }

            $ArticleCatInfo = $ArticleCatInfo->toArray();

            $this->smarty->assign('cat_info', $ArticleCatInfo);
            $this->smarty->assign('keywords', htmlspecialchars($ArticleCatInfo['keywords']));
            $this->smarty->assign('description', htmlspecialchars($ArticleCatInfo['cat_desc']));
            $this->smarty->assign('cat_name', $ArticleCatInfo['cat_name']);

            $pager['search']['id'] = $cat_id;
            $keywords = '';
            $goon_keywords = ''; //继续传递的搜索关键词

            /* 获得文章列表 */
            if (request()->exists('keywords')) {
                $keywords = addslashes(htmlspecialchars(urldecode(trim((request()->input('keywords', ''))))));

                $pager['search']['keywords'] = $keywords;
                $cur_url = request()->input('cur_url', '');

                $search_url = substr(strrchr($cur_url, '/'), 1);

                $this->smarty->assign('search_value', stripslashes(stripslashes($keywords)));
                $this->smarty->assign('search_url', $search_url);
                $goon_keywords = urlencode(request()->input('keywords', ''));
            }

            $where = [
                'keywords' => $keywords
            ];
            //区别关键字查询与分类列表
            if (empty($keywords)) {
                $where['cat_id'] = $cat_id;
            }

            $count = $this->articleService->getArticleCount($where);
            $pages = ($count > 0) ? ceil($count / $size) : 1;

            if ($page > $pages) {
                $page = $pages;
            }

            $where = [
                'keywords' => $keywords,
                'page' => $page,
                'size' => $size
            ];
            //区别关键字查询与分类列表
            if (empty($keywords)) {
                $where['cat_id'] = $cat_id;
            }
            $artciles_list = $this->articleService->getCatArticles($where);

            $this->smarty->assign('artciles_list', $artciles_list);
            $this->smarty->assign('cat_id', $cat_id);
            /* 分页 */
            assign_pager('article_cat', $cat_id, $count, $size, '', '', $page, $goon_keywords);
            assign_dynamic('article_cat');

            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-typearticle_cat" . $cat_id . ".xml" : 'feed.php?type=article_cat' . $cat_id); // RSS URL

            //获取seo start
            $seo = get_seo_words('article');

            if ($seo) {
                foreach ($seo as $key => $value) {
                    $seo[$key] = str_replace(['{sitename}', '{key}', '{name}', '{description}', '{article_class}'], [$this->config['shop_name'], $ArticleCatInfo['keywords'], $ArticleCatInfo['cat_name'], $ArticleCatInfo['cat_desc'], $ArticleCatInfo['cat_name']], $value);
                }
            }

            if (isset($seo['keywords']) && !empty($seo['keywords'])) {
                $this->smarty->assign('keywords', htmlspecialchars($seo['keywords']));
            } else {
                $this->smarty->assign('keywords', htmlspecialchars($this->config['shop_keywords']));
            }

            if (isset($seo['description']) && !empty($seo['description'])) {
                $this->smarty->assign('description', htmlspecialchars($seo['description']));
            } else {
                $this->smarty->assign('description', htmlspecialchars($this->config['shop_desc']));
            }

            if (isset($seo['title']) && !empty($seo['title'])) {
                $this->smarty->assign('page_title', htmlspecialchars($seo['title']));
            } else {
                $this->smarty->assign('page_title', $position['title']);
            }
            //获取seo end

            return $this->smarty->display('article_cat.dwt');
        });

        return $content;
    }
}
