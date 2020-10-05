<?php

namespace App\Services\Activity;

use App\Models\CollectStore;
use App\Models\ExchangeGoods;
use App\Models\Goods;
use App\Models\GoodsCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\TemplateService;
use App\Services\Goods\GoodsCommentService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;

class ExchangeService
{
    protected $baseRepository;
    protected $goodsCommonService;
    protected $goodsCommentService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $templateService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        GoodsCommentService $goodsCommentService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        TemplateService $templateService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsCommentService = $goodsCommentService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->templateService = $templateService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获得分类下的积分商品
     *
     * @param array $children
     * @param int $min
     * @param int $max
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return array
     */
    public function getExchangeGetGoods($children = [], $min = 0, $max = 0, $size = 0, $page = 1, $sort = 'g.goods_id', $order = 'desc')
    {
        $res = ExchangeGoods::select('goods_id', 'exchange_integral', 'is_hot')->where('is_exchange', 1)
            ->where('review_status', 3);

        $where = [
            'children' => $children
        ];

        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            $query = $query->where('is_delete', 0);

            if ($where['children']) {
                $query->whereIn('cat_id', $where['children']);
            }
        });


        if ($min > 0) {
            $res = $res->where('exchange_integral', '>=', $min);
        }
        if ($max > 0) {
            $res = $res->where('exchange_integral', '<=', $max);
        }

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'goods_name_style', 'market_price', 'goods_type', 'goods_brief', 'goods_thumb', 'goods_img', 'sales_volume');
            }
        ]);

        /* 获取商品表销量字段的值进行排序 */
        $res = $res->withCount([
            'getGoods as sales_volume' => function ($query) {
                $query->select('sales_volume');
            }
        ]);

        if ($sort == 'exchange_integral') {
            $sort = 'exchange_integral';
        }

        if ($sort == 'sales_volume') {
            $sort = 'sales_volume';
        }

        $begin = ($page - 1) * $size;
        $res = $res->offset($begin)
            ->limit($size)
            ->orderBy($sort, $order);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {

                $row = $this->baseRepository->getArrayMerge($row, $row['get_goods']);

                unset($row['get_goods']);

                /* 处理商品水印图片 */
                $watermark_img = '';
                if ($row['is_hot'] != 0) {
                    $watermark_img = 'watermark_hot_small';
                }

                if ($watermark_img != '') {
                    $arr[$row['goods_id']]['watermark_img'] = $watermark_img;
                }

                $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $arr[$row['goods_id']]['name'] = $row['goods_name'];
                $arr[$row['goods_id']]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$row['goods_id']]['goods_brief'] = $row['goods_brief'];
                $arr[$row['goods_id']]['sales_volume'] = $row['sales_volume'];

                $arr[$row['goods_id']]['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);
                $arr[$row['goods_id']]['exchange_integral'] = $row['exchange_integral'];
                $arr[$row['goods_id']]['type'] = $row['goods_type'];
                $arr[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$row['goods_id']]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $arr[$row['goods_id']]['url'] = $this->dscRepository->buildUri('exchange_goods', ['gid' => $row['goods_id']], $row['goods_name']);
            }
        }

        return $arr;
    }

    /**
     * 获得分类下的商品总数
     *
     * @param array $children
     * @param int $min
     * @param int $max
     * @return mixed
     */
    public function getExchangeGoodsCount($children = [], $min = 0, $max = 0)
    {
        $res = ExchangeGoods::select('goods_id', 'exchange_integral', 'is_hot')->where('is_exchange', 1)
            ->where('review_status', 3);

        $where = [
            'children' => $children
        ];

        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            $query = $query->where('is_delete', 0);

            if ($where['children']) {
                $query->whereIn('cat_id', $where['children']);
            }
        });


        if ($min > 0) {
            $res = $res->where('exchange_integral', '>=', $min);
        }
        if ($max > 0) {
            $res = $res->where('exchange_integral', '<=', $max);
        }

        /* 返回商品总数 */
        return $res->count();
    }

    /**
     * 获得指定分类下的推荐商品
     *
     * @param array $where
     * @return array
     */
    public function getExchangeRecommendGoods($where = [])
    {
        $num = 0;
        if (isset($where['type'])) {
            $type2lib = ['best' => 'exchange_best', 'new' => 'exchange_new', 'hot' => 'exchange_hot'];
            $num = $this->templateService->getLibraryNumber($type2lib[$where['type']], 'exchange_list');
        }

        $res = Goods::select(['goods_id', 'goods_name', 'brand_id', 'market_price', 'goods_name_style', 'goods_brief', 'goods_thumb', 'goods_img'])
            ->where('is_delete', 0);

        /* 查询扩展分类数据 */
        if (isset($where['cats']) && $where['cats']) {
            $where['cats'] = $where['cats'] && !is_array($where['cats']) ? explode(",", $where['cats']) : $where['cats'];

            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $where['cats'])->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];

            $where['extension_goods'] = $extension_goods;

            $res = $res->where(function ($query) use ($where) {
                if (isset($where['cats'])) {
                    $query = $query->whereIn('cat_id', $where['cats']);
                }

                if ($where['extension_goods']) {
                    $query->orWhereIn('goods_id', $where['extension_goods']);
                }
            });
        }

        $res = $res->whereHas('getExchangeGoods', function ($query) use ($where) {
            $query = $query->where('is_exchange', 1)->where('review_status', 3);

            if (isset($where['type'])) {
                switch ($where['type']) {
                    case 'best':
                        $query = $query->where('is_best', 1);
                        break;
                    case 'new':
                        $query = $query->where('is_new', 1);
                        break;
                    case 'hot':
                        $query = $query->where('is_hot', 1);
                        break;
                }
            }

            if (isset($where['min']) && $where['min'] > 0) {
                $query = $query->where('exchange_integral', '>=', $where['min']);
            }

            if (isset($where['max']) && $where['max'] > 0) {
                $query->where('exchange_integral', '<=', $where['max']);
            }
        });

        $res = $res->with([
            'getExchangeGoods' => function ($query) {
                $query->select('goods_id', 'exchange_integral');
            },
            'getBrand' => function ($query) {
                $query->select('brand_id', 'brand_name');
            }
        ]);

        $order_type = $GLOBALS['_CFG']['recommend_order'];

        if ($order_type == 0) {
            $res = $res->orderByRaw('sort_order, last_update desc');
        } else {
            $res = $res->orderByRaw('RAND()');
        }

        $res = $res->take($num);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $idx = 0;
        $goods = [];
        if ($res) {
            foreach ($res as $row) {
                $exchange = $row['get_exchange_goods'];
                $brand = $row['get_brand'];

                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['name'] = $row['goods_name'];
                $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods[$idx]['brief'] = $row['goods_brief'];
                $goods[$idx]['brand_name'] = $brand ? $brand['brand_name'] : '';
                $goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $goods[$idx]['exchange_integral'] = $exchange ? $exchange['exchange_integral'] : '';
                $goods[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $goods[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $goods[$idx]['url'] = $this->dscRepository->buildUri('exchange_goods', ['gid' => $row['goods_id']], $row['goods_name']);

                $goods[$idx]['short_style_name'] = $this->goodsCommonService->addStyle($goods[$idx]['short_name'], $row['goods_name_style']);
                $idx++;
            }
        }

        return $goods;
    }

    /**
     * 获得积分兑换商品的详细信息
     *
     * @param int $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     * @throws \Exception
     */
    public function getExchangeGoodsInfo($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $res = Goods::where('goods_id', $goods_id)->where('is_delete', 0);

        $res = $res->whereHas('getExchangeGoods', function ($query) {
            $query->where('is_exchange', 1)->where('review_status', 3);
        });

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
        ];

        $res = $res->with([
            'getCategory' => function ($query) {
                $query->select('cat_id', 'measure_unit');
            },
            'getExchangeGoods' => function ($query) {
                $query->select('goods_id', 'exchange_integral', 'market_integral', 'is_exchange');
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getBrand' => function ($query) {
                $query->select('brand_id', 'brand_name');
            }
        ]);

        $res = $res->withCount([
            'getCollectGoods as collect_count'
        ]);

        $res = $res->first();

        $row = $res ? $res->toArray() : [];

        if ($row) {
            $exchange = isset($row['get_exchange_goods']) && $row['get_exchange_goods'] ? $row['get_exchange_goods'] : '';

            $row['exchange_integral'] = $exchange ? $exchange['exchange_integral'] : 0;
            $row['market_integral'] = $exchange ? $exchange['market_integral'] : 0;
            $row['is_exchange'] = $exchange ? $exchange['is_exchange'] : 0;

            $brand = isset($row['get_brand']) && $row['get_brand'] ? $row['get_brand'] : '';
            $row['goods_brand'] = $brand ? $brand['brand_name'] : '';

            $category = isset($row['get_category']) && $row['get_category'] ? $row['get_category'] : '';
            $row['measure_unit'] = $category ? $category['measure_unit'] : '';

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

            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($row['is_new'] != 0) {
                $watermark_img = "watermark_new";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot';
            }

            if ($watermark_img != '') {
                $row['watermark_img'] = $watermark_img;
            }

            /* 修正重量显示 */
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                $row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
                ($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];

            /* 修正上架时间显示 */
            $row['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['date_format'], $row['add_time']);

            if ($row['goods_desc']) {
                $desc_preg = $this->dscRepository->descImagesPreg($row['goods_desc']);
                $row['goods_desc'] = $desc_preg['goods_desc'];
            }

            $row['market_integral'] = !empty($row['market_integral']) ? $row['market_integral'] : 0; //市场积分

            /* 修正商品图片 */
            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);

            $row['marketPrice'] = $row['market_price'];
            $row['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
            $row['goods_price'] = $this->dscRepository->getPriceFormat($row['exchange_integral'] * $GLOBALS['_CFG']['integral_scale'] / 100);
            $row['rz_shopName'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称

            $build_uri = [
                'urid' => $row['user_id'],
                'append' => $row['rz_shopName']
            ];

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
            $row['store_url'] = $domain_url['domain_name'];

            $row['shopinfo'] = $this->merchantCommonService->getShopName($row['user_id'], 2);
            if ($row['shopinfo'] && $row['shopinfo']['brand_thumb']) {
                $row['shopinfo']['brand_thumb'] = str_replace(['../'], '', $row['shopinfo']['brand_thumb']);
            }

            //买家印象
            if ($row['goods_product_tag']) {
                $impression_list = !empty($row['goods_product_tag']) ? explode(',', $row['goods_product_tag']) : '';

                $tag = [];
                foreach ($impression_list as $kk => $vv) {
                    $tag[$kk]['txt'] = $vv;
                    //印象数量
                    $tag[$kk]['num'] = $this->goodsCommentService->commentGoodsTagNum($row['goods_id'], $vv);
                }
                $row['impression_list'] = $tag;
            }

            if ($row['brand_id'] > 0) {
                $row['brand'] = get_brand_url($row['brand_id']);
                $row['goods_brand_url'] = $row['brand'] ? $row['brand']['url'] : '';
            }

            $row['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);

            //是否收藏店铺
            $rec_id = CollectStore::where('user_id', session('user_id'))->where('ru_id', $row['user_id'])->value('rec_id');

            if ($rec_id > 0) {
                $row['error'] = 1;
            } else {
                $row['error'] = 2;
            }

            return $row;
        } else {
            return [];
        }
    }
}
