<?php

namespace App\Http\Controllers;

use App\Models\ArticleCat;
use App\Models\Category;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCatService;
use App\Services\Article\ArticleCommonService;
use App\Services\Category\CategoryService;
use App\Services\Cms\NewsService;

class NewsController extends InitController
{
    protected $articleCatService;
    protected $newsService;
    protected $config;
    protected $articleCommonService;
    protected $dscRepository;
    protected $categoryService;

    public function __construct(
        ArticleCatService $articleCatService,
        NewsService $newsService,
        ArticleCommonService $articleCommonService,
        DscRepository $dscRepository,
        CategoryService $categoryService
    )
    {
        $this->articleCatService = $articleCatService;
        $this->newsService = $newsService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->dscRepository = $dscRepository;
        $this->categoryService = $categoryService;
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

        $dir = storage_public('data/cms_templates/' . $this->config['template']);
        $preview = (int)request()->input('preview', 0);

        if ($preview > 0 && file_exists($dir . "/temp/pc_html.php")) {
            $dir = $dir . "/temp";
        }
        if (file_exists($dir . "/pc_html.php")) {
            load_helper('visual');
            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['cms_here']);
            $this->smarty->assign('page_title', $position['title']);     // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);   // 当前位置
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            /*重写图片链接*/
            $replace_data = [
                'http://localhost/ecmoban_dsc2.0.5_20170518/',
                'http://localhost/ecmoban_dsc2.2.6_20170727/',
                'http://localhost/ecmoban_dsc2.3/'
            ];

            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            $page = get_html_file($dir . "/pc_html.php");

            if ($page) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $page);
                $page = $desc_preg['goods_desc'];
            }

            $page = str_replace($replace_data, url('/') . '/', $page);
            $this->smarty->assign('page', $page);

            return $this->smarty->display('news.dwt');
        } else {
            /************************** 配置区域 **************************/
            $custom_cata_id = 1; //请配置商品分类ID
            $custom_catb_id = 6; //请配置商品分类ID
            /************************** 配置区域 **************************/

            //************************** 以下代码无需修改 **************************/

            $cat_id = 1; //文章频道分类ID
            $video_cat_id = 12; //文章频道视频栏目ID
            $custom_catb_right_id = 858; //商品分类2右侧文章栏目id

            /*------------------------------------------------------ */
            //-- PROCESSOR
            /*------------------------------------------------------ */

            /* 获得页面的缓存ID */
            $cache_id = sprintf('%X', crc32('news-' . $this->config['lang']));

            if (!$this->smarty->is_cached('news.dwt', $cache_id)) {
                /* 如果页面没有被缓存则重新获得页面的内容 */

                assign_template('a', [$cat_id]);
                $position = assign_ur_here($cat_id);
                $this->smarty->assign('page_title', $position['title']);     // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);   // 当前位置

                $this->smarty->assign('article_categories', $this->articleCatService->getArticleCategoriesTree()); //文章分类树

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助

                $categories_pro = get_category_tree_leve_one();
                $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

                /* Meta */
                $meta = ArticleCat::where('cat_id', $cat_id)->first();
                $meta = $meta ? $meta->toArray() : [];

                if ($meta === false || empty($meta)) {
                    /* 如果没有找到任何记录则返回首页 */
                    return dsc_header("Location: ./\n");
                }

                $this->smarty->assign('cat_id', $cat_id);
                $this->smarty->assign('custom_cata_id', $custom_cata_id);
                $this->smarty->assign('custom_catb_id', $custom_catb_id);

                $this->smarty->assign('cat_name', htmlspecialchars($meta['cat_name']));
                $this->smarty->assign('keywords', htmlspecialchars($meta['keywords']));
                $this->smarty->assign('description', htmlspecialchars($meta['cat_desc']));
                $this->smarty->assign('themes_path', 'themes/' . $this->config['template']);

                //banner广告位 by wang
                $notic_down_ad = '';
                $article_channel_left_ad = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $article_channel_left_ad .= "'article_channel_left_ad" . $i . ","; //首页导航下方左侧广告
                    $notic_down_ad .= "'notic_down_ad" . $i . ","; //商城公告下方广告
                }

                $this->smarty->assign('article_channel_left_ad', $article_channel_left_ad);
                $this->smarty->assign('notic_down_ad', $notic_down_ad);

                //最新、推荐的文章、商城公告

                $where = [
                    'cat_id' => $cat_id,
                    'page' => 1,
                    'size' => 2,
                    'article_type' => 1
                ];
                $this->smarty->assign('top_articles', $this->newsService->getNewsCatArticles($where));

                $where = [
                    'cat_id' => $cat_id,
                    'page' => 1,
                    'size' => 5,
                    'article_type' => 0
                ];
                $this->smarty->assign('new_articles', $this->newsService->getNewsCatArticles($where));

                $where = [
                    'cat_id' => 13,
                    'page' => 1,
                    'size' => 6
                ];
                $this->smarty->assign('notice_articles', $this->newsService->getNewsCatArticles($where));//商城公告

                $where = [
                    'cat_id' => 5,
                    'page' => 1,
                    'size' => 6
                ];
                $this->smarty->assign('new_articles', $this->newsService->getNewsCatArticles($where));//新手上路

                $where = [
                    'cat_id' => 8,
                    'page' => 1,
                    'size' => 6
                ];
                $this->smarty->assign('serve_articles', $this->newsService->getNewsCatArticles($where));//服务保证

                $where = [
                    'cat_id' => 7,
                    'page' => 1,
                    'size' => 6
                ];
                $this->smarty->assign('pay_articles', $this->newsService->getNewsCatArticles($where));//配送与支付


                //频道分类
                $cat_child_list = article_cat_list($cat_id, 0, false);

                //文章列表
                if (is_array($cat_child_list)) {
                    foreach ($cat_child_list as $key => $vo) {
                        if ($vo['parent_id'] == $cat_id) {
                            $articles_list[$key] = $this->newsService->getNewArticles($key, 5);
                        } else {
                            unset($cat_child_list[$key]);
                        }
                    }
                }

                $this->smarty->assign('cat_child_list', $cat_child_list);
                $this->smarty->assign('articles_list', $articles_list);

                $cat = Category::catInfo($cat_id)->first();
                $cat = $cat ? $cat->toArray() : [];

                //自定义栏目2
                $this->smarty->assign('custom_catb_info', $cat);
                $this->smarty->assign('cat_childb_list', $this->categoryService->getChildTree($custom_catb_id));

                $hot_goods = $this->newsService->getHotGoodsList($custom_catb_id, $warehouse_id, $area_id, $area_city, 9);
                $this->smarty->assign('hot_goods', $hot_goods);//左侧

                $best_goods = $this->newsService->getBestGoodsList($custom_catb_id, $warehouse_id, $area_id, $area_city, 8);
                $this->smarty->assign('best_goods', $best_goods);//中间

                $new_articles_2_info = $this->articleCatService->getCatInfo(10);
                $this->smarty->assign('new_articles_2_info', $new_articles_2_info);
                $this->smarty->assign('new_articles_2', $this->newsService->getNewArticles($custom_catb_right_id, 9));//右侧

                //视频栏目
                $this->smarty->assign('video_cat_info', $this->articleCatService->getCatInfo($video_cat_id));
                $this->smarty->assign('cat_id_articles_video', $this->newsService->getNewArticles($video_cat_id, 11));//视频栏目

                /*     * 小图 start* */
                $news_banner_small_left = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $news_banner_small_left .= "'news_banner_small_left" . $i . ","; //新闻 左侧
                }

                $news_banner_small_right = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $news_banner_small_right .= "'news_banner_small_right" . $i . ","; //新闻 右侧
                }

                $this->smarty->assign('news_banner_small_left', $news_banner_small_left);
                $this->smarty->assign('news_banner_small_right', $news_banner_small_right);

                assign_dynamic('news');
            }

            return $this->smarty->display('news.dwt', $cache_id);
        }
    }
}
