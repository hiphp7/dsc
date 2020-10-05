<?php

namespace App\Services\Other;

use App\Models\Article;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class ArticleAutoManageService
{
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
    }

    public function getAutoGoods()
    {
        $row = Article::whereRaw(1);

        if (!empty($_POST['goods_name'])) {
            $goods_name = trim($_POST['goods_name']);
            $row = $row->where('title', 'like', '%' . $goods_name . '%');
            $filter['goods_name'] = $goods_name;
        }

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        $res = $res->with([
            'getArticleCat',
            'getAutoManage'
        ]);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        $res = $res->offset($filter['start'])->limit($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($res);

        $goodsdb = [];
        if ($res) {
            foreach ($res as $key => $rt) {
                $rt['starttime'] = $rt['get_auto_manage']['starttime'] ?? 0;
                $rt['endtime'] = $rt['get_auto_manage']['endtime'] ?? 0;
                $rt['cat_name'] = $rt['get_Article_cat']['cat_name'] ?? '';

                if (!empty($rt['starttime'])) {
                    $rt['starttime'] = $this->timeRepository->getLocalDate('Y-m-d', $rt['starttime']);
                }

                if (!empty($rt['endtime'])) {
                    $rt['endtime'] = $this->timeRepository->getLocalDate('Y-m-d', $rt['endtime']);
                }

                $rt['goods_id'] = $rt['article_id'];
                $rt['goods_name'] = $rt['title'];

                $goodsdb[] = $rt;
            }
        }

        $arr = ['goodsdb' => $goodsdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }
}
