<?php

namespace App\Services\Article;


use App\Models\Article;
use App\Models\ArticleCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class ArticleCommonService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $articleCatService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        ArticleCatService $articleCatService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->articleCatService = $articleCatService;
    }

    /**
     * 分配帮助信息
     *
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getShopHelp()
    {

        $cache_name = 'get_shop_help';
        $arr = cache($cache_name);
        $arr = !is_null($arr) ? $arr : false;

        if ($arr === false) {
            $res = ArticleCat::where('cat_type', 5)->whereHas('getArticleList', function ($query) {
                $query->where('is_open', 1);
            })->with(['getArticleList']);

            $res = $res->orderBy('sort_order');

            $res = $this->baseRepository->getToArrayGet($res);

            $arr = [];
            if ($res) {
                foreach ($res as $key => $row) {
                    $arr[$row['cat_id']]['cat_id'] = $this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);
                    $arr[$row['cat_id']]['cat_name'] = $row['cat_name'];

                    if ($row['get_article_list']) {
                        foreach ($row['get_article_list'] as $k => $article) {
                            $arr[$row['cat_id']]['article'][$k]['article_id'] = $article['article_id'];
                            $arr[$row['cat_id']]['article'][$k]['title'] = $article['title'];
                            $arr[$row['cat_id']]['article'][$k]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
                                $this->dscRepository->subStr($article['title'], $GLOBALS['_CFG']['article_title_length']) : $article['title'];
                            $arr[$row['cat_id']]['article'][$k]['url'] = $article['open_type'] != 1 ?
                                $this->dscRepository->buildUri('article', ['aid' => $article['article_id']], $article['title']) : trim($article['file_url']);
                        }
                    }
                }

                while (count($arr) > 10) {
                    array_pop($arr);
                }
            }

            cache()->forever($cache_name, $arr);
        }

        return $arr;
    }

    /**
     * 帮助信息列表
     *
     * @return array
     * @throws \Exception
     */
    public function helpinfo()
    {
        $res = ArticleCat::select('cat_id', 'cat_name')->where('parent_id', 3)->orderBy('sort_order', 'asc')->orderBy('cat_id', 'asc')->get();
        $articles = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($articles as $key => $value) {
                $cats = $this->articleCatService->getCatListChildren($value['cat_id']);
                $article = Article::select('article_id', 'title')->where('is_open', 1)
                    ->whereIn('cat_id', $cats)
                    ->orderBy('add_time', 'desc')
                    ->get();
                $articles[$key]['list'] = $article ? $article->toArray() : [];
            }
        }
        return $articles;
    }
}