<?php

namespace App\Services\Article;

use App\Libraries\Image;
use App\Models\Article;
use App\Models\GoodsArticle;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class ArticleManageService
{
    protected $articleCatService;
    protected $baseRepository;
    protected $config;
    protected $timeRepository;
    protected $image;

    public function __construct(
        ArticleCatService $articleCatService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        Image $image
    )
    {
        $this->articleCatService = $articleCatService;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->image = $image;

        /* 商城配置信息 */
        $shopConfig = cache('shop_config');
        $shopConfig = !is_null($shopConfig) ? $shopConfig : false;
        if ($shopConfig === false) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    /**
     * 获得文章列表
     *
     * @return array
     */
    public function getArticlesList()
    {
        $filter = [];
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'article_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = Article::whereRaw(1);

        if (!empty($filter['keyword'])) {
            $row = $row->where('title', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        if ($filter['cat_id']) {
            $children = $this->articleCatService->getCatListChildren($filter['cat_id']);
            $row = $row->whereIn('cat_id', $children);
        }

        $res = $record_count = $row;

        /* 文章总数 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $filter['keyword'] = stripslashes($filter['keyword']);

        $res = $res->with([
            'getArticleCat'
        ]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $rows) {
                $rows['cat_name'] = $rows['get_article_cat']['cat_name'] ?? '';

                $rows['date'] = $this->timeRepository->getLocalDate($this->config['time_format'], $rows['add_time']);

                $arr[] = $rows;
            }
        }

        return ['arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 取得文章关联商品
     *
     * @param int $article_id
     * @return array
     */
    public function getArticleGoods($article_id = 0)
    {
        $list = GoodsArticle::where('article_id', $article_id)
            ->whereHas('getGoods');

        $list = $list->with([
            'getGoods'
        ]);

        $list = $this->baseRepository->getToArrayGet($list);

        $arr = [];
        if ($list) {
            foreach ($list as $key => $row) {
                $arr[$key]['goods_id'] = $row['get_goods']['goods_id'];
                $arr[$key]['goods_name'] = $row['get_goods']['goods_name'];
            }
        }

        return $arr;
    }

    /**
     * 上传文件
     *
     * @param $upload
     * @return bool|string
     */
    public function uploadArticleFile($upload)
    {
        $file_dir = storage_public(DATA_DIR . "/article");
        if (!file_exists($file_dir)) {
            if (!make_dir($file_dir)) {
                /* 创建目录失败 */
                return false;
            }
        }

        $filename = $this->image->random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = storage_public(DATA_DIR . "/article/" . $filename);

        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/article/" . $filename;
        } else {
            return false;
        }
    }
}
