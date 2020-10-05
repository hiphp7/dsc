<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\Goods;
use App\Models\GoodsCat;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Article\ArticleCatService;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsCommonService;

/**
 * 商城会员
 * Class User
 * @package App\Services
 */
class NewsService
{
    protected $articleCatService;
    protected $categoryService;
    protected $config;
    protected $goodsCommonService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        ArticleCatService $articleCatService,
        CategoryService $categoryService,
        GoodsCommonService $goodsCommonService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->articleCatService = $articleCatService;
        $this->categoryService = $categoryService;
        $this->goodsCommonService = $goodsCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获得文章分类下的文章列表
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getNewsCatArticles($where = [])
    {

        //取出所有非0的文章
        $res = Article::where('is_open', 1);

        if (isset($where['cat_id'])) {
            if ($where['cat_id'] == '-1') {
                $res = $res->where('cat_id', '>', 0);
            } else {
                $cat = $this->articleCatService->getCatListChildren($where['cat_id']);
                $res = $res->whereIn('cat_id', $cat);
            }
        }

        if (isset($where['article_type'])) {
            $res = $res->where('article_type', $where['article_type']);
        }

        $res = $res->orderBy('article_type', 'desc')
            ->orderBy('article_id', 'desc');

        if (isset($where['page']) && isset($where['size'])) {
            $start = ($where['page'] - 1) * $where['size'];

            if ($start > 0) {
                $res = $res->skip($start);
            }

            if ($where['size'] > 0) {
                $res = $res->take($where['size']);
            }
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $article_id = $row['article_id'];

                $arr[$article_id]['id'] = $article_id;
                $arr[$article_id]['title'] = $row['title'];
                $arr[$article_id]['short_title'] = $this->config['article_title_length'] > 0 ? $this->dscRepository->subStr($row['title'], $this->config['article_title_length']) : $row['title'];
                $arr[$article_id]['author'] = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $this->config['shop_name'] : $row['author'];
                $arr[$article_id]['url'] = $row['open_type'] != 1 ? $this->dscRepository->buildUri('article', ['aid' => $article_id], $row['title']) : trim($row['file_url']);
                $arr[$article_id]['add_time'] = $this->timeRepository->getLocalDate('Y.m.d', $row['add_time']);
                $arr[$article_id]['description'] = trim($row['description']);
                $arr[$article_id]['file_url'] = $row['file_url'];
            }
        }

        return $arr;
    }

    /**
     * 获得最新的文章列表
     *
     * @param int $cat_id
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function getNewArticles($cat_id = 0, $limit = 12)
    {
        $cat = $this->articleCatService->getCatListChildren($cat_id);
        $res = Article::where('is_open', 1)->where('article_type', 0);

        if ($cat) {
            $res = $res->whereHas('getArticleCat', function ($query) use ($cat) {
                $query->whereIn('cat_id', $cat);
            });
        }

        $res = $res->orderBy('article_type', 'desc')
            ->orderBy('add_time', 'desc');

        $res = $res->with([
            'getArticleCat' => function ($query) {
                $query->select('cat_id', 'cat_name');
            }
        ]);

        $res = $res->take($limit);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $idx => $row) {
                $row = $row['get_article_cat'] ? array_merge($row, $row['get_article_cat']) : $row;

                $row['id'] = $row['article_id'];
                $row['short_title'] = $this->config['article_title_length'] > 0 ?
                    $this->dscRepository->subStr($row['title'], $this->config['article_title_length']) : $row['title'];
                $row['add_time'] = $this->timeRepository->getLocalDate('Y.m.d', $row['add_time']);
                $row['url'] = $row['open_type'] != 1 ?
                    $this->dscRepository->buildUri('article', ['aid' => $row['article_id']], $row['title']) : trim($row['file_url']);
                $row['cat_url'] = $this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);

                $res[$idx] = $row;
            }
        }

        return $res;
    }

    /**
     * 获得cat_id热门列表
     *
     * @param int $cat_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $num
     * @return array
     */
    public function getHotGoodsList($cat_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $num = 0)
    {
        $cats = $this->categoryService->getCatListChildren($cat_id);

        /* 查询扩展分类数据 */
        $extension_goods = [];
        if ($cats) {
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cats)->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];
        }

        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_hot', 1);

        $goodsParam = [
            'cats' => $cats,
            'extension_goods' => $extension_goods
        ];
        $res = $res->where(function ($query) use ($goodsParam) {
            if ($goodsParam['cats']) {
                $query = $query->whereIn('cat_id', $goodsParam['cats']);
            }

            if ($goodsParam['extension_goods']) {
                $query->orWhere(function ($query) use ($goodsParam) {
                    $query->whereIn('goods_id', $goodsParam['extension_goods']);
                });
            }
        });

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($where) {
                $query->where('region_id', $where['warehouse_id']);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $res->orderBy('goods_id', 'desc');

        if ($num > 0) {
            $res = $res->take($num);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $goods = [];
        if ($res) {
            foreach ($res as $idx => $row) {
                $price = [
                    'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                    'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                    'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                    'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                    'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                    'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                    'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                    'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                    'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                ];

                $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $goods[$idx] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['name'] = $row['goods_name'];
                $goods[$idx]['brief'] = $row['goods_brief'];
                $goods[$idx]['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);

                $goods[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $goods[$idx]['short_style_name'] = $this->goodsCommonService->addStyle($goods[$idx]['short_name'], $row['goods_name_style']);
                $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $goods[$idx]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $goods[$idx]['thumb'] = empty($row['goods_thumb']) ? $this->config['no_picture'] : $row['goods_thumb'];
                $goods[$idx]['goods_img'] = empty($row['goods_img']) ? $this->config['no_picture'] : $row['goods_img'];
                $goods[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $goods[$idx]['seller_note'] = $row['seller_note'];
                $goods[$idx]['add_time'] = $this->timeRepository->getLocalDate('Y.m.d', $row['add_time']);
                $goods[$idx]['last_update'] = $this->timeRepository->getLocalDate('Y.m', $row['last_update']);
            }
        }

        return $goods;
    }

    /**
     * 获得cat_id精品列表
     *
     * @access  public
     * @param integer
     * @return  array
     */
    public function getBestGoodsList($cat_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $num = 0)
    {
        $cats = $this->categoryService->getCatListChildren($cat_id);

        /* 查询扩展分类数据 */
        $extension_goods = [];
        if ($cats) {
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cats)->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];
        }

        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_best', 1);

        $goodsParam = [
            'cats' => $cats,
            'extension_goods' => $extension_goods
        ];
        $res = $res->where(function ($query) use ($goodsParam) {
            if ($goodsParam['cats']) {
                $query = $query->whereIn('cat_id', $goodsParam['cats']);
            }

            if ($goodsParam['extension_goods']) {
                $query->orWhere(function ($query) use ($goodsParam) {
                    $query->whereIn('goods_id', $goodsParam['extension_goods']);
                });
            }
        });

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $res->orderBy('goods_id', 'desc');

        if ($num > 0) {
            $res = $res->take($num);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $goods = [];
        if ($res) {
            foreach ($res as $idx => $row) {
                $price = [
                    'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                    'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                    'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                    'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                    'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                    'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                    'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                    'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                    'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                ];

                $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $goods[$idx] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['name'] = $row['goods_name'];
                $goods[$idx]['brief'] = $row['goods_brief'];
                $goods[$idx]['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);

                $goods[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $goods[$idx]['short_style_name'] = $this->goodsCommonService->addStyle($goods[$idx]['short_name'], $row['goods_name_style']);
                $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $goods[$idx]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $goods[$idx]['thumb'] = empty($row['goods_thumb']) ? $this->config['no_picture'] : $row['goods_thumb'];
                $goods[$idx]['goods_img'] = empty($row['goods_img']) ? $this->config['no_picture'] : $row['goods_img'];
                $goods[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $goods[$idx]['seller_note'] = $row['seller_note'];
                $goods[$idx]['add_time'] = $this->timeRepository->getLocalDate('Y.m', $row['add_time']);
                $goods[$idx]['last_update'] = $this->timeRepository->getLocalDate('Y.m', $row['last_update']);
            }
        }

        return $goods;
    }
}
