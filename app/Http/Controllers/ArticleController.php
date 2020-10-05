<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCatService;
use App\Services\Article\ArticleCommonService;
use App\Services\Article\ArticleGoodsService;
use App\Services\Article\ArticleService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsService;

/**
 * 文章内容
 */
class ArticleController extends InitController
{
    protected $areaService;
    protected $articleService;
    protected $articleCatService;
    protected $goodsService;
    protected $dscRepository;
    protected $config;
    protected $articleCommonService;
    protected $articleGoodsService;
    protected $commentService;

    public function __construct(
        DscRepository $dscRepository,
        ArticleService $articleService,
        ArticleCatService $articleCatService,
        GoodsService $goodsService,
        ArticleCommonService $articleCommonService,
        ArticleGoodsService $articleGoodsService,
        CommentService $commentService
    )
    {
        $this->articleService = $articleService;
        $this->articleCatService = $articleCatService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->articleGoodsService = $articleGoodsService;
        $this->commentService = $commentService;
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

        /*------------------------------------------------------ */
        //-- INPUT
        /*------------------------------------------------------ */
        $user_id = session('user_id', 0);
        $act = addslashes(trim(request()->input('act', '')));
        $article_id = intval(request()->input('id', 0));
        $cat_id = intval(request()->input('cat_id', 0));

        if ($cat_id < 0) {
            $article_id = Article::where('cat_id', $cat_id)->value('article_id');
        }

        if (($act == "get_ajax_content")) {
            $article = $this->articleService->getArticleInfo($article_id);

            $this->smarty->assign('article', $article);
            $html = $this->smarty->fetch('article.dwt');
            $result = ['error' => 0, 'message' => '', 'content' => $html];
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 文章评论
        /*------------------------------------------------------ */
        elseif ($act == 'add_comment') {
            $article_id = intval(request()->input('article_id', 0));
            $content = trim(request()->input('content', ''));

            $ip = $this->dscRepository->dscIp();
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($user_id > 0) {
                $comment_id = Comment::where('comment_type', 1)->where('id_value', $article_id)->where('user_id', $user_id)->value('comment_id');

                if (!$comment_id) {
                    //因为平台后台设置,如果需要审核 comment_check值为1
                    //status:是否被管理员批准显示，1，是；0，未批准
                    $status = $this->config['comment_check'] == 1 ? 0 : 1;

                    $other = [
                        'comment_type' => 1,
                        'id_value' => $article_id,
                        'email' => session('email', ''),
                        'user_name' => session('user_name', ''),
                        'content' => $content,
                        'add_time' => gmtime(),
                        'ip_address' => $ip,
                        'status' => $status,
                        'user_id' => $user_id
                    ];

                    $comment_id = Comment::insertGetId($other);

                    if ($comment_id) {
                        if ($status == 1) {
                            $result['message'] = $GLOBALS['_LANG']['comment_success'];
                        } else {
                            $result['message'] = $GLOBALS['_LANG']['comment_article_success'];
                        }
                    }
                } else {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['is_comment_article'];
                }
            } else {
                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['please_login_member'];
            }

            return response()->json($result);
        }

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        $cache_id = sprintf('%X', crc32($article_id . "-" . $user_id . $warehouse_id . "-" . $area_id . "-" . $area_city . '-' . session('user_rank', 0) . '_' . $this->config['lang']));

        /* 文章详情 */
        $article = $this->articleService->getArticleInfo($article_id);

        if (empty($article)) {
            return dsc_header("Location: ./\n");
        }

        /* 跳转H5 start */
        $Loaction = dsc_url('/#/articleDetail/' . $article_id);
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        if (!empty($article['link']) && $article['link'] != 'http://' && $article['link'] != 'https://') {
            return dsc_header("location:$article[link]\n");
        }

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

        $sys_categories = $this->articleCatService->getArticleCategoriesTree(0, 2);
        $this->smarty->assign('sys_categories', $sys_categories); //系统保留文章分类树by wang

        $custom_categories = $this->articleCatService->getArticleCategoriesTree(0, 1);
        $this->smarty->assign('custom_categories', $custom_categories); //自定义文章分类树by wang

        $new_article = $this->articleService->getNewArticle(5);
        $this->smarty->assign('new_article', $new_article); // 网店帮助

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

        $where = [
            'article_id' => $article_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'review_goods' => $this->config['review_goods'],
            'open_area_goods' => $this->config['open_area_goods']
        ];
        $related_goods = $this->articleGoodsService->getArticleRelatedGoods($where);

        $this->smarty->assign('related_goods', $related_goods);  // 文章关联商品
        $this->smarty->assign('id', $article_id);
        $this->smarty->assign('username', session('user_name'));
        $this->smarty->assign('email', session('email'));
        $this->smarty->assign('type', '1');

        //文章对应的分类信息
        $cat_info = $this->articleService->getArticleCatInfo($article_id);
        $this->smarty->assign('cat_info', $cat_info);

        /* 验证码相关设置 */
        if ((intval($this->config['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
            $this->smarty->assign('enabled_captcha', 1);
            $this->smarty->assign('rand', mt_rand());
        }

        $this->smarty->assign('article', $article);
        $this->smarty->assign('keywords', htmlspecialchars($article['keywords']));
        $this->smarty->assign('description', htmlspecialchars($article['description']));

        $catlist = [];

        $article_parent_cats = get_article_parent_cats($article['cat_id']);

        if ($article_parent_cats) {
            foreach ($article_parent_cats as $k => $v) {
                $catlist[] = $v['cat_id'];
            }
        }

        assign_template('a', $catlist);

        $position = assign_ur_here($article['cat_id'], $article['title']);
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
        $this->smarty->assign('comment_type', 1);

        /* 上一篇下一篇文章 */
        $next_article = Article::where('article_id', '>', $article_id)
            ->where('cat_id', $article['cat_id'])
            ->where('is_open', 1)
            ->orderBy('article_id')
            ->first();

        $next_article = $next_article ? $next_article->toArray() : [];

        if (!empty($next_article)) {
            $next_article['url'] = $this->dscRepository->buildUri('article', ['aid' => $next_article['article_id']], $next_article['title']);
            $this->smarty->assign('next_article', $next_article);
        }

        $prev_article = Article::where('article_id', '<', $article_id)
            ->where('cat_id', $article['cat_id'])
            ->where('is_open', 1)
            ->orderBy('article_id', 'desc')
            ->first();

        $prev_article = $prev_article ? $prev_article->toArray() : [];

        if (!empty($prev_article)) {
            $prev_aid = $prev_article['article_id'];

            $prev_article = Article::where('article_id', $prev_aid)->first();

            $prev_article = $prev_article ? $prev_article->toArray() : [];

            $prev_article['url'] = $prev_article ? $this->dscRepository->buildUri('article', ['aid' => $prev_article['article_id']], $prev_article['title']) : '';
            $this->smarty->assign('prev_article', $prev_article);
        }

        $this->smarty->assign('full_page', 1);
        assign_dynamic('article');

        //文章评论
        $article_comment = $this->commentService->getAssignArticleComment($article_id, 1);
        if ($article_comment) {
            $this->smarty->assign('article_comment', $article_comment['comments']);
            $this->smarty->assign('pager', $article_comment['pager']);
            $this->smarty->assign('count', $article_comment['count']);
            $this->smarty->assign('size', $article_comment['size']);
            $this->smarty->assign('article_id', $article_id);
        }

        //获取seo start
        $seo = get_seo_words('article_content');
        if ($seo) {
            $cat_info['cat_name'] = $cat_info['cat_name'] ?? '';
            $article['keywords'] = $article['keywords'] ?? '';
            foreach ($seo as $key => $value) {
                $seo[$key] = str_replace(['{sitename}', '{key}', '{name}', '{description}', '{article_class}'], [$this->config['shop_name'], $article['keywords'], $article['title'], $article['description'], $cat_info['cat_name']], $value);
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

        if (isset($article) && $article['cat_id'] > 2) {
            return $this->smarty->display('article.dwt');
        } else {
            return $this->smarty->display('article_pro.dwt');
        }
    }
}
