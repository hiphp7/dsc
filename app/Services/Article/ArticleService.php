<?php

namespace App\Services\Article;

use App\Models\Article;
use App\Models\ArticleCat;
use App\Models\ArticleExtend;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Comment\CommentService;

/**
 * 商城文章
 * Class Article
 * @package App\Services
 */
class ArticleService
{
    protected $commentService;
    protected $timeRepository;
    protected $config;
    protected $dscRepository;
    protected $baseRepository;
    protected $articleCatService;

    public function __construct(
        CommentService $commentService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        ArticleCatService $articleCatService
    )
    {
        $this->commentService = $commentService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->articleCatService = $articleCatService;
    }

    /**
     * 获得指定的文章的详细信息
     *
     * @access  public
     * @param integer $article_id
     * @return  array
     */
    public function getArticleInfo($article_id = 0)
    {
        /* 获得文章的信息 */
        $row = Article::where('is_open', 1)
            ->where('article_id', $article_id);

        $row = $row->with([
            'getComment' => function ($query) {
                $query->selectRaw("id_value, IFNULL(AVG(comment_rank), 0) AS comment_rank")
                    ->where('comment_type', 1);
            }
        ]);

        $row = $row->with([
            'getArticleExtend' => function ($query) {
                $query->select('article_id', 'likenum');
            }
        ]);

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        if ($row) {
            $row['comment_rank'] = $row['get_comment']['comment_rank'] ?? 0;
            $row['comment_rank'] = ceil($row['comment_rank']);                              // 用户评论级别取整
            $row['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
            $row['likenum'] = $row['get_article_extend']['likenum']; // 点赞人数

            /* 作者信息如果为空，则用网站名称替换 */
            if (empty($row['author']) || $row['author'] == '_SHOPHELP') {
                $row['author'] = $this->config['shop_name'];
            }

            if ($row['content']) {
                $row['content'] = html_out($row['content']);
                // 过滤样式 手机自适应
                $row['content'] = $this->dscRepository->contentStyleReplace($row['content']);
                // 显示文章详情图片 （本地或OSS）
                $row['content'] = $this->dscRepository->getContentImgReplace($row['content']);
            }

            // 评论列表
            $comment = $this->commentService->getArticleCommentList($article_id, 1, 6);
            $row['comment'] = $this->comment_format($comment);

            // 用于微信 jsdk 分享内容
            // 文章描述
            $row['description'] = empty($row['description']) ? $this->dscRepository->subStr(strip_tags(html_out($row['content'])), 100) : $row['description'];
            // 分享图片
            if (!empty($article['file_url'])) {
                $article_img = $this->dscRepository->getImagePath($row['file_url']);
            } else {
                $article_img = $row['album'][0] ?? ''; // 文章内容第一张图片
            }
            $row['file_url'] = $article_img;
            // 文章链接
            $row['url'] = dsc_url('/#/articleDetail/' . $row['article_id']);
            $row['app_page'] = config('route.article.detail') . $row['article_id'];
        }

        /*点击量*/
        $res = ArticleExtend::where('article_id', $article_id)
            ->count();
        /*有就+1无则插入*/
        if ($res) {
            ArticleExtend::where('article_id', $article_id)->increment('click', 1);
        } else {
            $click = [
                'article_id' => $article_id,
                'click' => 1,
                'likenum' => 0,
                'hatenum' => 0,
            ];
            ArticleExtend::insert($click);
        }

        return $row;
    }

    /**
     * 格式化评论(区分评论内容与评论回复)
     * @param array $conment
     * @return array
     */
    public function comment_format($conment = [])
    {
        if (empty($conment)) {
            return [];
        }
        $all_comment_parent = [];
        foreach ($conment as $key => $val) {
            $val['reply_content'] = [];
            $all_comment_parent[$val['comment_id']] = $val;
        }
        foreach ($all_comment_parent as $k => $v) {
            if (!empty($v['parent_id']) && isset($all_comment_parent[$v['parent_id']])) {
                //该评论有上级评论
                $all_comment_parent[$v['parent_id']]['reply_content'][] = $v;
                unset($all_comment_parent[$k]);
            }
        }
        return array_values($all_comment_parent);
    }

    /**
     * 获得指定文章分类信息
     * @param int $article_id
     * @return array
     */
    public function getArticleCatInfo($article_id = 0)
    {
        $cat_info = Article::where('article_id', $article_id);

        $cat_info = $cat_info->with(['getArticleCat'])->first();

        $cat_info = $cat_info ? $cat_info->toArray() : [];

        if (empty($cat_info)) {
            return [];
        }

        $cat_info = $cat_info && $cat_info['get_article_cat'] ? array_merge($cat_info, $cat_info['get_article_cat']) : $cat_info;

        return $cat_info;
    }

    /**
     * 获得最新文章列表
     * @param int $num
     * @return array
     */
    public function getNewArticle($num = 0)
    {
        $articles = Article::where('is_open', 1)->orderBy('add_time', 'desc')->take($num)->get();

        $articles = $articles ? $articles->toArray() : [];

        if ($articles) {
            foreach ($articles as $key => $val) {
                $articles[$key]['url'] = $this->dscRepository->buildUri('article', ['aid' => $val['article_id']], $val['title']);
                $articles[$key]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['add_time']);
            }
        }

        return $articles;
    }

    /**
     * 文章点赞
     *
     * @param int $article_id
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function articleLike($article_id = 0, $user_id = 0)
    {
        // 缓存
        $cache_id = 'article_likenum' . '_' . $user_id . '_' . $article_id;
        $result = cache($cache_id);

        if (is_null($result)) {
            $count = ArticleExtend::where('article_id', $article_id)->count();

            if ($count > 0) { //修改
                ArticleExtend::where('article_id', $article_id)->increment('likenum', 1);
            } else {// 添加
                ArticleExtend::insert(['article_id' => $article_id, 'likenum' => 1]);
            }
            //写入缓存
            $result = ['article_id' => $article_id, 'isislike' => 1];
            cache()->forever($cache_id, $result);
            $likenum = ArticleExtend::where('article_id', $article_id)->value('likenum');
            return [
                'error' => 0,
                'like_num' => $likenum,
                'is_like' => 1,
                'article_id' => $article_id
            ];
        } else {
            $likenum = ArticleExtend::where('article_id', $article_id)->value('likenum');

            return [
                'error' => 0,
                'like_num' => $likenum,
                'is_like' => 1,
                'msg' => lang('article.is_like_article_succeed')
            ];
        }
    }

    /**
     * 获得文章内容
     * @param int $article_id
     * @return array
     */
    public function getArticleContent($article_id = 0)
    {
        if (empty($article_id)) {
            return [];
        }

        /* 获得文章的信息 */
        $row = Article::where('article_id', $article_id);

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        if ($row) {
            if ($row['content']) {
                // 过滤样式 手机自适应
                $row['content'] = $this->dscRepository->contentStyleReplace($row['content']);
                // 显示文章详情图片 （本地或OSS）
                $row['content'] = $this->dscRepository->getContentImgReplace($row['content']);
            }
        }

        return $row;
    }

    /**
     * 获得跨境文章内容文章内容
     * @return array
     */
    public function getCrossBorderArticleList()
    {
        $cross_border_article_list = [];

        $cross_border_article_config = $this->config['cross_border_article_id'] ?? 0;

        if (isset($cross_border_article_config) && $cross_border_article_config != 0) {
            $cross_border_article_config = explode(',', $cross_border_article_config);

            foreach ($cross_border_article_config as $val => $key) {

                $cross_border_article_list[] = $this->getArticleInfo($key);
            }
        }

        return $cross_border_article_list;
    }

    /**
     * 获得指定分类下的文章总数
     *
     * @param array $where
     * @return mixed
     * @throws \Exception
     */
    public function getArticleCount($where = [])
    {
        $count = Article::where('is_open', 1);

        if (isset($where['cat_id'])) {
            if ($where['cat_id'] == '-1') {
                $count = $count->where('cat_id', '>', 0);
            } else {
                $cat_list = $this->articleCatService->getCatListChildren($where['cat_id']);
                $count = $count->whereIn('cat_id', $cat_list);
            }
        }

        if ($where['keywords'] && $where['keywords'] != '') {
            $count = $count->where('title', 'like', "%" . $where['keywords'] . "%");
        }

        $count = $count->count();

        return $count;
    }

    /**
     * 获得文章分类下的文章列表
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getCatArticles($where = [])
    {
        $list = Article::where('is_open', 1);

        //增加搜索条件，如果有搜索内容就进行搜索
        if (isset($where['keywords']) && $where['keywords'] != '') {
            $list = $list->where('title', 'like', '%' . $where['keywords'] . '%');
        }

        //取出所有非0的文章
        if (isset($where['cat_id'])) {
            if ($where['cat_id'] == '-1') {
                $list = $list->where('cat_id', '>', 0);
            } else {
                if ($where['cat_id'] > 0) {
                    $cat_list = [$where['cat_id']];
                } else {
                    $cat_list = $this->articleCatService->getCatListChildren($where['cat_id']);
                }

                $list = $list->whereIn('cat_id', $cat_list);
            }
        }

        $list = $list->with([
            'getArticleExtend' => function ($query) {
                $query->select('article_id', 'click');
            }
        ]);

        $list = $list->orderByRaw('article_type, sort_order, article_id desc');

        if (isset($where['page']) && isset($where['size'])) {
            $start = ($where['page'] - 1) * $where['size'];

            $list = $list->skip($start)->take($where['size']);
        } elseif (isset($where['size'])) {
            $list = $list->take($where['size']);
        }

        $list = $this->baseRepository->getToArrayGet($list);

        $arr = [];
        if ($list) {
            foreach ($list as $key => $row) {
                //取编辑器内容中的三张图片开始
                preg_match_all('|src=(.*) |U', $row['content'], $the);
                $img_list = [];
                if (isset($the[1]) && !empty($the[1])) {
                    $the[1] = array_slice($the[1], 0, 3);
                    foreach ($the[1] as $value) {
                        $img_list[] = str_replace('\"', '', $value);
                    }
                }
                //取编辑器内容中的三张图片结束
                $arr[$key]['id'] = $row['article_id'];
                $arr[$key]['title'] = $row['title'];
                $arr[$key]['description'] = $row['description'];
                $arr[$key]['short_title'] = $this->config['article_title_length'] > 0 ? $this->dscRepository->subStr($row['title'], $this->config['article_title_length']) : $row['title'];
                $arr[$key]['author'] = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $this->config['shop_name'] : $row['author'];
                $arr[$key]['url'] = $row['open_type'] != 1 ? $this->dscRepository->buildUri('article', ['aid' => $row['article_id']], $row['title']) : trim($row['file_url']);
                $arr[$key]['add_time'] = $row['add_time'];
                $arr[$key]['click'] = $row['get_article_extend'] ? $row['get_article_extend']['click'] : 0;
                $arr[$key]['amity_time'] = $row['amity_time'];
                $arr[$key]['content_img_list'] = $img_list;
                $arr[$key]['file_url'] = $row['file_url'] ? $this->dscRepository->getImagePath($row['file_url']) : '';
            }
        }

        return $arr;
    }

    /**
     * 分配文章列表
     *
     * @param int $id 文章分类的编号
     * @param int $num 文章数量
     * @return mixed
     * @throws \Exception
     */
    public function getAssignArticles($id = 0, $num = 0)
    {
        $cat['id'] = $id;
        $cat['name'] = ArticleCat::where('cat_id', $id)->value('cat_name');
        $cat['url'] = $this->dscRepository->buildUri('article_cat', ['acid' => $id], $cat['name']);

        $articles['cat'] = $cat;

        $where = [
            'cat_id' => $id,
            'size' => $num
        ];
        $articles['arr'] = $this->getCatArticles($where);

        return $articles;
    }
}
