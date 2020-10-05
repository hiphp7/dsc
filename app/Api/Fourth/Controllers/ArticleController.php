<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Article\ArticleCatService;
use App\Services\Article\ArticleGoodsService;
use App\Services\Article\ArticleService;
use App\Services\Comment\CommentService;
use App\Services\Wechat\WechatMediaService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class ArticleController
 * @package App\Api\Fourth\Controllers
 */
class ArticleController extends Controller
{
    protected $articleService;
    protected $articleCatService;
    protected $wechatMediaService;
    protected $commentService;
    protected $articleGoodsService;

    public function __construct(
        ArticleService $articleService,
        ArticleCatService $articleCatService,
        WechatMediaService $wechatMediaService,
        CommentService $commentService,
        ArticleGoodsService $articleGoodsService
    )
    {
        $this->articleService = $articleService;
        $this->articleCatService = $articleCatService;
        $this->wechatMediaService = $wechatMediaService;
        $this->commentService = $commentService;
        $this->articleGoodsService = $articleGoodsService;
    }

    /**
     * 文章分类导航
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);

        $data = $this->articleCatService->articleCategoryAll($cat_id);

        return $this->succeed($data);
    }

    /**
     * 文章列表
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function list(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer|max:50'
        ]);

        $arr = [
            'keywords' => $request->input('keywords', ''),
            'cat_id' => $request->input('cat_id', 0),
            'page' => $request->input('page', 1),
            'size' => $request->input('size', 10),
        ];

        $data = $this->articleService->getCatArticles($arr);

        return $this->succeed($data);
    }

    /**
     * 文章详情
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function show(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $article_id = $request->input('id', 0);

        $data = $this->articleService->getArticleInfo($article_id);

        $user_id = $this->authorization();

        $data['is_like'] = 0;
        $cache_id = 'article_likenum' . '_' . $user_id . '_' . $article_id;
        $result = cache($cache_id);
        if ($result) {
            $data['is_like'] = 1;
        }

        // 文章关联商品
        $where = [
            'uid' => $user_id,
            'article_id' => $article_id,
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'review_goods' => $GLOBALS['_CFG']['review_goods'],
            'open_area_goods' => $GLOBALS['_CFG']['open_area_goods']
        ];
        $data['related_goods'] = $this->articleGoodsService->getArticleRelatedGoods($where);

        return $this->succeed($data);
    }

    /**
     * 文章分类详情
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function category(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'category_id' => 'required|integer',
        ]);

        $article_id = $request->input('category_id', 0);

        $data = $this->articleService->getArticleCatInfo($article_id);

        return $this->succeed($data);
    }

    /**
     * 评论文章
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function comment(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $article_id = $request->input('id', 0);
        $parent_id = $request->input('cid', 0); // 父级id
        $content = $request->input('content', '');

        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->commentService->submitComment($article_id, $parent_id, $content, $user_id);

        return $this->succeed($data);
    }

    /**
     * 文章评论列表
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function commentlist(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'article_id' => 'required|integer',     // 文章id
            'page' => 'required|integer',
            'size' => 'required|integer|max:50'
        ]);

        $article_id = $request->get('article_id', 0);
        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        $data = $this->commentService->getArticleCommentList($article_id, $page, $size);

        return $this->succeed($data);
    }

    /**
     * 点赞
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function like(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'article_id' => 'required|integer'     // 文章id
        ]);

        $article_id = $request->get('article_id', 0);

        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->articleService->articleLike($article_id, $user_id);

        return $this->succeed($data);
    }

    /**
     * 微信素材文章
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function wechat_media(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'media_id' => 'required|integer'     // 微信素材id
        ]);

        $media_id = $request->input('media_id', 0);

        if (!file_exists(MOBILE_WECHAT)) {
            return $this->setStatusCode(403)->failed('not allowd');
        }

        $data = $this->wechatMediaService->WechatMediaDetail($media_id);

        return $this->succeed($data);
    }
}
