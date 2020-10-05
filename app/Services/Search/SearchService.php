<?php

namespace App\Services\Search;

use App\Models\Attribute;
use App\Models\Coupons;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\GoodsType;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\CouponsService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 商城搜索
 * Class CrowdFund
 * @package App\Services
 */
class SearchService
{
    protected $couponsService;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $categoryService;
    protected $config;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $goodsWarehouseService;
    protected $dscRepository;
    protected $city = 0;
    protected $timeRepository;

    public function __construct(
        CouponsService $couponsService,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        CategoryService $categoryService,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        GoodsWarehouseService $goodsWarehouseService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->couponsService = $couponsService;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->categoryService = $categoryService;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;

        /* 获取地区缓存 */
        $area_cookie = app(AreaService::class)->areaCookie();
        $this->city = $area_cookie['city'];

        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * @param $value
     * @return bool
     */
    public function isNotNull($value)
    {
        if (is_array($value)) {
            return (!empty($value['from'])) || (!empty($value['to']));
        } else {
            return !empty($value);
        }
    }

    /**
     * 获得可以检索的属性
     *
     * @param int $cat_id
     * @return array
     */
    public function getSeachableAttributes($cat_id = 0)
    {
        /* 获得可用的商品类型 */
        $attributes = [
            'cate' => [],
            'attr' => []
        ];

        $cat = GoodsType::where('enabled', 1);
        $cat = $cat->whereHas('getGoodsAttribute', function ($query) {
            $query->where('attr_index', '>', 0);
        });

        $cat = $cat->get();

        $cat = $cat ? $cat->toArray() : [];

        /* 获取可以检索的属性 */
        if (!empty($cat)) {
            foreach ($cat as $val) {
                $attributes['cate'][$val['cat_id']] = $val['cat_name'];
            }

            $res = Attribute::where('attr_index', '>', 0);

            if ($cat_id > 0) {
                $res = $res->where('cat_id', $cat_id)->where('cat_id', $cat[0]['cat_id']);
            }

            $res = $res->orderBy('cat_id')->orderBy('sort_order');

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    if ($row['attr_index'] == 1 && $row['attr_input_type'] == 1) {
                        $row['attr_values'] = str_replace("\r", '', $row['attr_values']);
                        $options = explode("\n", $row['attr_values']);

                        $attr_value = [];
                        foreach ($options as $opt) {
                            $attr_value[$opt] = $opt;
                        }
                        $attributes['attr'][] = [
                            'id' => $row['attr_id'],
                            'attr' => $row['attr_name'],
                            'options' => $attr_value,
                            'type' => 3
                        ];
                    } else {
                        $attributes['attr'][] = [
                            'id' => $row['attr_id'],
                            'attr' => $row['attr_name'],
                            'type' => $row['attr_index']
                        ];
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * 搜索页检索的属性
     *
     * @param array $attr_list
     * @param int $pickout
     * @return array
     */
    public function getGoodsAttrListGoods($attr_list = [], $pickout = 0)
    {
        $res = GoodsAttr::select('goods_id')->whereRaw(1);

        $attr_num = 0;
        $attr_arg = [];

        $where = [
            'pickout' => $pickout,
            'attr_url' => ''
        ];

        if ($attr_list && $pickout = 0) {
            foreach ($attr_list as $key => $val) {
                if ($this->isNotNull($val) && is_numeric($key)) {
                    $attr_num++;

                    $where['val'] = $val;
                    $where['attr_id'] = $key;

                    $res = $res->orWhere(function ($query) use ($where) {
                        $query = $query->whereRaw(1);

                        if (is_array($where['val'])) {
                            $query = $query->where('attr_id', $where['attr_id']);
                            if (!empty($where['val']['from'])) {
                                if (is_numeric($where['val']['from'])) {
                                    $query = $query->where('attr_value', '>=', floatval($where['val']['from']));
                                } else {
                                    $query = $query->where('attr_value', '>=', $where['val']['from']);
                                }

                                $attr_arg["attr[" . $where['attr_id'] . "][from]"] = $where['val']['from'];
                                $where['attr_url'] .= "&amp;attr[" . $where['attr_id'] . "][from]=" . $where['val']['from'];
                            }

                            if (!empty($where['val']['to'])) {
                                if (is_numeric($where['val']['val']['to'])) {
                                    $query->where('attr_value', '<=', floatval($where['val']['to']));
                                } else {
                                    $query->where('attr_value', '<=', $where['val']['to']);
                                }

                                $attr_arg["attr[" . $where['attr_id'] . "][to]"] = $where['val']['to'];
                                $where['attr_url'] .= "&amp;attr[" . $where['attr_id'] . "][to]=[to]";
                            }
                        } else {
                            /* 处理选购中心过来的链接 */
                            if ($where['pickout']) {
                                $query->where('attr_id', $where['attr_id'])
                                    ->where('attr_value', $where['val']);
                            } else {
                                $query->where('attr_id', $where['attr_id'])
                                    ->where('attr_value', 'like', '%' . $this->dscRepository->mysqlLikeQuote($where['val']) . '%');
                            }

                            $where['attr_url'] .= "&amp;attr[" . $where['attr_id'] . "]=" . $where['val'];
                            $attr_arg["attr[" . $where['attr_id'] . "]"] = $where['val'];
                        }
                    });
                }
            }
        }

        $res = $res->groupBy('goods_id');
        $res = $res->get();
        $res = $res ? $res->toArray() : [];

        $res = $res ? collect($res)->flatten()->all() : [];

        $arr = [
            'res' => $res,
            'attr_url' => $where['attr_url'],
            'attr_arg' => $attr_arg,
            'attr_num' => $attr_num
        ];

        return $arr;
    }

    /**
     * 搜索页商品列表
     *
     * @param int $cat_id
     * @param int $brands_id
     * @param array $children
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param string $display
     * @param int $min
     * @param int $max
     * @param array $filter_attr
     * @param array $where_ext
     * @param array $goods_ids
     * @param array $keywords
     * @param string $intro
     * @param int $outstock
     * @param array $attr_in
     * @param array $spec_goods_ids
     * @param int $cou_list
     * @param int $goods_num
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public function getSearchGoodsList($cat_id = 0, $brands_id = 0, $children = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $display = '', $min = 0, $max = 0, $filter_attr = [], $where_ext = [], $goods_ids = [], $keywords = [], $intro = '', $outstock = 0, $attr_in = [], $spec_goods_ids = [], $cou_list = 0, $goods_num = 0, $size = 10, $page = 1, $sort = 'goods_id', $order = 'desc')
    {
        $user_cou = isset($_REQUEST['user_cou']) && !empty($_REQUEST['user_cou']) ? intval($_REQUEST['user_cou']) : 0;

        /* 查询扩展分类数据 */
        $extension_goods = [];
        if ($cat_id > 0) {
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children)->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];
        }

        $goodsParam = [
            'cat_id' => $cat_id,
            'children' => $children,
            'extension_goods' => $extension_goods,
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->where(function ($query) use ($goodsParam) {
                if ($goodsParam['cat_id'] > 0) {
                    $query = $query->whereIn('cat_id', $goodsParam['children']);
                }

                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query->whereIn('goods_id', $goodsParam['extension_goods']);
                    });
                }
            });

        if (isset($where_ext['self']) && $where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)->orWhere(function ($query) {
                    $query->whereHas('getShopInfo', function ($query) {
                        $query->where('self_run', 1);
                    });
                });
            });
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }

        if (isset($where_ext['have']) && $where_ext['have'] == 1) {
            $res = $res->where('goods_number', '>', 0);
        }

        if (isset($where_ext['ship']) && ($where_ext['ship'] == 1 || $where_ext['ship'] == 'is_shipping')) {
            $res = $res->where('is_shipping', 1);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        /* 关联地区 */
        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $goods_arr = [
            'goods_ids' => $goods_ids,
            'brand_id' => $brands_id,
            'keywords' => $keywords,
            'min' => $min,
            'max' => $max,
            'time' => $this->timeRepository->getGmTime()
        ];

        $res = $res->where(function ($query) use ($goods_arr) {
            if ($goods_arr['goods_ids']) {
                $query = $query->whereIn('goods_id', $goods_arr['goods_ids']);
            }

            $query->orWhere(function ($query) use ($goods_arr) {
                if ($goods_arr['brand_id']) {
                    if ($goods_arr['brand_id'] && !is_array($goods_arr['brand_id'])) {
                        $goods_arr['brand_id'] = explode(',', $goods_arr['brand_id']);
                    }

                    $query = $query->whereIn('brand_id', $goods_arr['brand_id']);
                }

                if ($goods_arr['keywords']) {
                    $query->where(function ($query) use ($goods_arr) {
                        foreach ($goods_arr['keywords'] as $key => $val) {
                            $query->orWhere(function ($query) use ($val) {
                                $val = $this->dscRepository->mysqlLikeQuote(trim($val));

                                $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');
                                $query->orWhere('keywords', 'like', '%' . $val . '%');
                            });
                        }

                        $query->orWhere('goods_sn', 'like', '%' . $goods_arr['keywords'][0] . '%');
                    });
                }
            });
        });

        if ($goods_arr['keywords'] && ((!(isset($where_ext['self']) && $where_ext['self'] == 1)) && (!(isset($where_ext['have']) && $where_ext['have'] == 1)) && (!(isset($where_ext['ship']) && $where_ext['ship'] == 'is_shipping')))) {
            $res = $res->orWhere(function ($query) use ($goods_arr) {
                $query->whereHas('getPresaleActivity', function ($query) use ($goods_arr) {
                    $query->where('start_time' ,'<' ,$goods_arr['time'])->where('end_time', '>', $goods_arr['time']);
                    $query->where(function ($query) use ($goods_arr) {
                        foreach ($goods_arr['keywords'] as $key => $val) {
                            $query->where(function ($query) use ($val) {
                                $val = $this->dscRepository->mysqlLikeQuote(trim($val));
                                $query->orWhere('goods_name', 'like', '%' . $val . '%');
                            });
                        }
                    });
                });

                if ($goods_arr['min'] > 0) {
                    $query = $query->where('shop_price', '>=', $goods_arr['min']);
                }

                if ($goods_arr['max'] > 0) {
                    $query = $query->where('shop_price', '<=', $goods_arr['max']);
                }

                if ($goods_arr['brand_id']) {
                    if ($goods_arr['brand_id'] && !is_array($goods_arr['brand_id'])) {
                        $goods_arr['brand_id'] = explode(',', $goods_arr['brand_id']);
                    }

                    $query->whereIn('brand_id', $goods_arr['brand_id']);
                }
            });
        }

        if ($intro) {
            switch ($_REQUEST['intro']) {
                case 'best':
                    $res = $res->where('is_best', 1);
                    break;
                case 'new':
                    $res = $res->where('is_new', 1);
                    break;
                case 'hot':
                    $res = $res->where('is_hot', 1);
                    break;
                case 'promotion':
                    $time = $this->timeRepository->getGmTime();
                    $res = $res->where('promote_price', '>', 0)
                        ->where('promote_start_date', '<=', $time)
                        ->where('promote_end_date', '>=', $time);
                    break;
            }
        }

        if ($outstock) {
            $res = $res->where('goods_number', '>', 0);
        }

        /* 如果检索条件都是无效的，就不用检索 */
        if (isset($attr_in['attr_num']) && $attr_in['attr_num'] > 0) {
            $res = $res->whereIn('goods_id', $attr_in['res']);
        }

        /* 会员中心储值卡  分类跳转 */
        $spec_goods_ids = $spec_goods_ids && !is_array($spec_goods_ids) ? explode(",", $spec_goods_ids) : [];
        if ($spec_goods_ids) {
            $res = $res->whereIn('goods_id', $spec_goods_ids);
        }

        if ($cou_list['cou_id'] > 0) {
            $cou_data = coupons::where('cou_id', $cou_list['cou_id'])->first();
            $cou_data = $cou_data ? $cou_data->toArray() : [];

            if ($cou_data) {
                //如果是购物送(任务集市)
                if ($cou_data['cou_type'] == VOUCHER_SHOPING && empty($user_cou)) {
                    if ($user_cou) {
                        $res = $res->where('user_id', $cou_data['ru_id']);

                        if ($cou_data['cou_ok_goods']) {
                            $res = $res->whereIn('goods_id', $cou_data['cou_ok_goods']);
                        } elseif ($cou_data['cou_ok_cat']) {
                            $cou_children = $this->couponsService->getCouChildren($cou_data['cou_ok_cat']);
                            $cou_children = $this->baseRepository->getExplode($cou_children);
                            if ($cou_children) {
                                $res = $res->whereIn('cat_id', $cou_children);
                            }
                        }
                    } else {
                        $res = $res->where('user_id', $cou_data['ru_id']);

                        if ($cou_data['cou_goods']) {
                            $cou_data['cou_goods'] = !is_array($cou_data['cou_goods']) ? explode(",", $cou_data['cou_goods']) : [];
                            $res = $res->whereIn('goods_id', $cou_data['cou_goods']);
                        } elseif ($cou_data['spec_cat']) {
                            $cou_children = $this->couponsService->getCouChildren($cou_data['spec_cat']);
                            $cou_children = $this->baseRepository->getExplode($cou_children);
                            if ($cou_children) {
                                $res = $res->whereIn('cat_id', $cou_children);
                            }
                        }
                    }
                } else {
                    $res = $res->where('user_id', $cou_data['ru_id']);

                    if ($cou_data['cou_goods']) {
                        $cou_data['cou_goods'] = !is_array($cou_data['cou_goods']) ? explode(",", $cou_data['cou_goods']) : [];
                        $res = $res->whereIn('goods_id', $cou_data['cou_goods']);
                    } elseif ($cou_data['spec_cat']) {
                        $cou_children = $this->couponsService->getCouChildren($cou_data['spec_cat']);
                        $cou_children = $this->baseRepository->getExplode($cou_children);
                        if ($cou_children) {
                            $res = $res->whereIn('cat_id', $cou_children);
                        }
                    }
                }
            }
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $attrList = GoodsAttr::whereIn('goods_attr_id', $filter_attr);
            $attrList = $this->baseRepository->getToArrayGet($attrList);
            $attr_value = $this->baseRepository->getKeyPluck($attrList, 'attr_value');

            $goodsList = GoodsAttr::whereIn('attr_value', $attr_value);
            $goodsList = $this->baseRepository->getToArrayGet($goodsList);
            $goodsList = $this->baseRepository->getKeyPluck($goodsList, 'goods_id');

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        /* 查询仅自营和标识自营店铺的商品 */
        if (isset($where_ext['self']) && $where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)->orWhere(function ($query) {
                    $query->whereHas('getShopInfo', function ($query) {
                        $query->where('self_run', 1);
                    });
                });
            });
        }

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
            },
            'getShopInfo',
            'getPresaleActivity',
            'getProductsWarehouse' => function ($query) use ($warehouse_id) {
                $query->where('warehouse_id', $warehouse_id);
            },
            'getProductsArea' => function ($query) use ($where) {
                $query = $query->where('area_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getProducts',
            'getSellerShopInfo'
        ]);

        $res = $res->withCount([
            'getComment as review_count' => function ($query) {
                $query->where('status', 1)
                    ->where('parent_id', 0)
                    ->whereIn('comment_rank', [1, 2, 3, 4, 5]);
            },
            'getCollectGoods as is_collect'
        ]);

        //瀑布流加载分类商品 by wu
        if ($goods_num) {
            $start = $goods_num;
        } else {
            $start = ($page - 1) * $size;
        }

        if (strpos($sort, 'goods_id') !== false) {
            $sort = "sort_order";
            $res = $res->orderBy($sort, $order)->orderBy('goods_id', $order);
        } else {
            $res = $res->orderBy($sort, $order);
        }

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $idx = 0;
        $arr = [];
        $now = $this->timeRepository->getGmTime();

        if ($res) {
            $arr_keyword = $keywords;

            if (isset($arr_keyword) && $arr_keyword) {
                $arr_keyword = array_values($arr_keyword);

                $built_key = "<font style='color:#ec5151;'></font>"; //高亮显示HTML
                //过滤掉高亮显示HTML可以匹配上的项，防止页面html错乱
                foreach ($arr_keyword as $key => $val_keyword) {
                    if (strpos($built_key, $val_keyword) !== false || empty($val_keyword)) {
                        unset($arr_keyword[$key]);
                    }
                }
            }

            foreach ($res as $row) {
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

                $arr[$idx] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $presale = !empty($row['get_presale_activity']) ? $row['get_presale_activity'] : '';

                /* 预售商品 start */
                if ($presale && $presale['act_id']) {
                    $arr[$idx]['presale'] = lang('common.presell');
                    $arr[$idx]['act_id'] = $presale['act_id'];
                    $arr[$idx]['act_name'] = $presale['act_name'];
                    $arr[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $arr[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                    $arr[$idx]['purl'] = $this->dscRepository->buildUri('presale', ['act' => 'view', 'presaleid' => $presale['act_id']], $presale['goods_name']);
                    $arr[$idx]['rz_shopName'] = isset($row['get_shop_info']['rz_shopName']) ? $row['get_shop_info']['rz_shopName'] : ''; //店铺名称

                    $build_uri = [
                        'urid' => $row['user_id'],
                        'append' => $arr[$idx]['rz_shopName']
                    ];

                    $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                    $arr[$idx]['pshop_url'] = $domain_url['domain_name'];

                    $arr[$idx]['start_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $presale['start_time']);
                    $arr[$idx]['end_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $presale['end_time']);

                    //@Author guan 关键字高亮显示 start
                    $act_name_keyword = "<span>" . $presale['act_name'] . "</span>";
                    foreach ($arr_keyword as $key => $val_keyword) {
                        $act_name_keyword = preg_replace("/(>.*)($val_keyword)(.*<)/Ui", "$1<font style='color:#ec5151;'>$val_keyword</font>\$3", $act_name_keyword);
                    }
                    $arr[$idx]['act_name_keyword'] = $act_name_keyword;
                    //@Author guan 关键字高亮显示 end

                    if ($presale['start_time'] >= $now) {
                        $arr[$idx]['no_start'] = 1;
                    }
                    if ($presale['end_time'] <= $now) {
                        $arr[$idx]['already_over'] = 1;
                    }
                }
                /* 预售商品 end */

                // 最小起订量
                if ($row['is_minimum'] == 1 && $now > $row['minimum_start_date'] && $now < $row['minimum_end_date']) {
                    $arr[$idx]['is_minimum'] = 1;
                } else {
                    $arr[$idx]['is_minimum'] = 0;
                    $arr[$idx]['minimum'] = 0;
                }

                /* 处理商品水印图片 */
                $watermark_img = '';

                if ($promote_price > 0) {
                    $watermark_img = "watermark_promote_small";
                } elseif ($row['is_new'] != 0) {
                    $watermark_img = "watermark_new_small";
                } elseif ($row['is_best'] != 0) {
                    $watermark_img = "watermark_best_small";
                } elseif ($row['is_hot'] != 0) {
                    $watermark_img = 'watermark_hot_small';
                }

                if ($watermark_img != '') {
                    $arr[$idx]['watermark_img'] = $watermark_img;
                }

                $arr[$idx]['goods_id'] = $row['goods_id'];

                /* 商品仓库货品 */
                if ($row['model_price'] == 1) {
                    $prod = $row['get_products_warehouse'] ?? [];
                } elseif ($row['model_price'] == 2) {
                    $prod = $row['get_products_area'] ?? [];
                } else {
                    $prod = $row['get_products'] ?? [];
                }

                if (empty($prod)) { //当商品没有属性库存时
                    $arr[$idx]['prod'] = 1;
                } else {
                    $arr[$idx]['prod'] = 0;
                }

                if ($display == 'grid') {
                    //@Author guan 关键字高亮显示 start
                    $row['goods_name'] = $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']);
                    $goods_name_keyword = "<span>" . $row['goods_name'] . "</span>";
                    foreach ($arr_keyword as $key => $val_keyword) {
                        if (preg_match("/(>.*)($val_keyword)(.*<)/Ui", $goods_name_keyword)) {
                            $goods_name_keyword = preg_replace("/(>.*)($val_keyword)(.*<)/Ui", "$1<font style='color:#ec5151;'>$val_keyword</font>\$3", $row['goods_name']);
                        }
                    }
                    //
                    $arr[$idx]['goods_name_keyword'] = $this->config['goods_name_length'] > 0 ? $goods_name_keyword : $goods_name_keyword;
                    //模版页面样式错误，为模版页面的的goods_name改为goods_name2。以防止样式错误。
                    $arr[$idx]['goods_name'] = $this->config['goods_name_length'] > 0 ? $row['goods_name'] : $row['goods_name'];
                    //@Author guan 关键字高亮显示 end
                } else {
                    //@Author guan 关键字高亮显示 start
                    $goods_name_keyword = "<span>" . $row['goods_name'] . "</span>";

                    if (isset($arr_keyword) && $arr_keyword) {
                        foreach ($arr_keyword as $key => $val_keyword) {
                            if (preg_match("/(>.*)($val_keyword)(.*<)/Ui", $goods_name_keyword)) {
                                $goods_name_keyword = preg_replace("/(>.*)($val_keyword)(.*<)/Ui", "$1<font style='color:#ec5151;'>$val_keyword</font>\$3", $goods_name_keyword);
                            }
                        }
                    }

                    $arr[$idx]['goods_name_keyword'] = $goods_name_keyword;
                    $arr[$idx]['goods_name'] = $row['goods_name'];
                    //@Author guan 关键字高亮显示 end
                }

                $arr[$idx]['goods_number'] = $row['goods_number'];
                $arr[$idx]['type'] = $row['goods_type'];
                $arr[$idx]['is_promote'] = $row['is_promote'];
                $arr[$idx]['sales_volume'] = $row['sales_volume'];
                $arr[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$idx]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $arr[$idx]['goods_brief'] = $row['goods_brief'];
                $arr[$idx]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $arr[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $arr[$idx]['is_shipping'] = $row['is_shipping'];
                $arr[$idx]['review_count'] = $row['review_count'];
                $arr[$idx]['pictures'] = $this->goodsGalleryService->getGoodsGallery($row['goods_id'], 6); // 商品相册

                $shop_information = $this->merchantCommonService->getShopName($row['user_id']); //通过ru_id获取到店铺信息;
                $shop_information = $shop_information ? $shop_information : [];
                $arr[$idx]['rz_shopName'] = isset($shop_information['shop_name']) ? $shop_information['shop_name'] : ''; //店铺名称

                if ($this->config['customer_service'] == 0) {
                    $seller_id = 0;
                } else {
                    $seller_id = $row['user_id'];
                }

                $shop_information = $this->merchantCommonService->getShopName($seller_id); //通过ru_id获取到店铺信息;
                $shop_information = $shop_information ? $shop_information : [];

                $arr[$idx]['user_id'] = $row['user_id'];

                $build_uri = [
                    'urid' => $row['user_id'],
                    'append' => $arr[$idx]['rz_shopName']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                $arr[$idx]['store_url'] = $domain_url['domain_name'];

                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                $arr[$idx]['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : ''; //平台是否允许商家使用"在线客服";
                //判断当前商家是平台,还是入驻商家 bylu
                if ($seller_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                    if ($kf_im_switch) {
                        $arr[$idx]['is_dsc'] = true;
                    } else {
                        $arr[$idx]['is_dsc'] = false;
                    }
                } else {
                    $arr[$idx]['is_dsc'] = false;
                }
                /*  @author-bylu  end */

                $build_uri = [
                    'urid' => $row['user_id'],
                    'append' => $arr[$idx]['rz_shopName']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                $arr[$idx]['store_url'] = $domain_url['domain_name'];

                $arr[$idx]['is_new'] = $row['is_new'];
                $arr[$idx]['is_best'] = $row['is_best'];
                $arr[$idx]['is_hot'] = $row['is_hot'];
                $arr[$idx]['user_id'] = $row['user_id'];
                $arr[$idx]['self_run'] = $row['get_shop_info'] ? $row['get_shop_info']['self_run'] : 0;

                $basic_info = $row['get_seller_shop_info'] ?? [];

                $chat = $this->dscRepository->chatQq($basic_info);
                $arr[$idx]['kf_type'] = $chat['kf_type'];
                $arr[$idx]['kf_qq'] = $chat['kf_qq'];
                $arr[$idx]['kf_ww'] = $chat['kf_ww'];

                $arr[$idx]['is_collect'] = $row['is_collect'];

                $idx++;
            }
        }

        if ($display == 'grid') {
            if (count($arr) % 2 != 0) {
                $arr[] = [];
            }
        }

        /* 返回商品总数 */
        return $arr;
    }

    /**
     * 搜索页商品数量
     *
     * @param int $cat_id
     * @param int $brands_id
     * @param array $children
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $min
     * @param int $max
     * @param array $filter_attr
     * @param array $where_ext
     * @param array $goods_ids
     * @param array $keywords
     * @param string $intro
     * @param int $outstock
     * @param array $attr_in
     * @param array $spec_goods_ids
     * @param int $cou_list
     * @return mixed
     */
    public function getSearchGoodsCount($cat_id = 0, $brands_id = 0, $children = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $min = 0, $max = 0, $filter_attr = [], $where_ext = [], $goods_ids = [], $keywords = [], $intro = '', $outstock = 0, $attr_in = [], $spec_goods_ids = [], $cou_list = 0)
    {
        $user_cou = isset($_REQUEST['user_cou']) && !empty($_REQUEST['user_cou']) ? intval($_REQUEST['user_cou']) : 0;

        /* 查询扩展分类数据 */
        $extension_goods = [];
        if ($cat_id > 0) {
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children)->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];
        }

        $goodsParam = [
            'cat_id' => $cat_id,
            'children' => $children,
            'extension_goods' => $extension_goods,
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->where(function ($query) use ($goodsParam) {
                if ($goodsParam['cat_id'] > 0) {
                    $query = $query->whereIn('cat_id', $goodsParam['children']);
                }

                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query->whereIn('goods_id', $goodsParam['extension_goods']);
                    });
                }
            });

        if (isset($where_ext['self']) && $where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)->orWhere(function ($query) {
                    $query->whereHas('getShopInfo', function ($query) {
                        $query->where('self_run', 1);
                    });
                });
            });
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }

        if (isset($where_ext['have']) && $where_ext['have'] == 1) {
            $res = $res->where('goods_number', '>', 0);
        }

        if (isset($where_ext['ship']) && ($where_ext['ship'] == 1 || $where_ext['ship'] == 'is_shipping')) {
            $res = $res->where('is_shipping', 1);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        /* 关联地区 */
        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $goods_arr = [
            'goods_ids' => $goods_ids,
            'brand_id' => $brands_id,
            'keywords' => $keywords,
            'min' => $min,
            'max' => $max
        ];

        $res = $res->where(function ($query) use ($goods_arr) {
            if ($goods_arr['goods_ids']) {
                $query = $query->whereIn('goods_id', $goods_arr['goods_ids']);
            }

            $query->orWhere(function ($query) use ($goods_arr) {
                if ($goods_arr['brand_id']) {
                    if ($goods_arr['brand_id'] && !is_array($goods_arr['brand_id'])) {
                        $goods_arr['brand_id'] = explode(',', $goods_arr['brand_id']);
                    }

                    $query = $query->whereIn('brand_id', $goods_arr['brand_id']);
                }

                if ($goods_arr['keywords']) {
                    $query->where(function ($query) use ($goods_arr) {
                        foreach ($goods_arr['keywords'] as $key => $val) {
                            $query->orWhere(function ($query) use ($val) {
                                $val = $this->dscRepository->mysqlLikeQuote(trim($val));

                                $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');
                                $query->orWhere('keywords', 'like', '%' . $val . '%');
                            });
                        }

                        $query->orWhere('goods_sn', 'like', '%' . $goods_arr['keywords'][0] . '%');
                    });
                }
            });
        });

        if ($goods_arr['keywords'] && ((!(isset($where_ext['self']) && $where_ext['self'] == 1)) && (!(isset($where_ext['have']) && $where_ext['have'] == 1)) && (!(isset($where_ext['ship']) && $where_ext['ship'] == 'is_shipping')))) {
            $res = $res->orWhere(function ($query) use ($goods_arr) {
                $query->whereHas('getPresaleActivity', function ($query) use ($goods_arr) {
                    $query->where(function ($query) use ($goods_arr) {
                        foreach ($goods_arr['keywords'] as $key => $val) {
                            $query->where(function ($query) use ($val) {
                                $val = $this->dscRepository->mysqlLikeQuote(trim($val));

                                $query->orWhere('goods_name', 'like', '%' . $val . '%');
                            });
                        }
                    });
                });

                if ($goods_arr['min'] > 0) {
                    $query = $query->where('shop_price', '>=', $goods_arr['min']);
                }

                if ($goods_arr['max'] > 0) {
                    $query = $query->where('shop_price', '<=', $goods_arr['max']);
                }

                if ($goods_arr['brand_id']) {
                    if ($goods_arr['brand_id'] && !is_array($goods_arr['brand_id'])) {
                        $goods_arr['brand_id'] = explode(',', $goods_arr['brand_id']);
                    }

                    $query->whereIn('brand_id', $goods_arr['brand_id']);
                }
            });
        }

        if ($intro) {
            switch ($_REQUEST['intro']) {
                case 'best':
                    $res = $res->where('is_best', 1);
                    break;
                case 'new':
                    $res = $res->where('is_new', 1);
                    break;
                case 'hot':
                    $res = $res->where('is_hot', 1);
                    break;
                case 'promotion':
                    $time = $this->timeRepository->getGmTime();
                    $res = $res->where('promote_price', '>', 0)
                        ->where('promote_start_date', '<=', $time)
                        ->where('promote_end_date', '>=', $time);
                    break;
            }
        }

        if ($outstock) {
            $res = $res->where('goods_number', '>', 0);
        }

        /* 如果检索条件都是无效的，就不用检索 */
        if (isset($attr_in['attr_num']) && $attr_in['attr_num'] > 0) {
            $res = $res->whereIn('goods_id', $attr_in['res']);
        }

        /* 会员中心储值卡  分类跳转 */
        $spec_goods_ids = $spec_goods_ids && !is_array($spec_goods_ids) ? explode(",", $spec_goods_ids) : [];
        if ($spec_goods_ids) {
            $res = $res->whereIn('goods_id', $spec_goods_ids);
        }

        if ($cou_list['cou_id'] > 0) {
            $cou_data = coupons::where('cou_id', $cou_list['cou_id'])->first();
            $cou_data = $cou_data ? $cou_data->toArray() : [];

            if ($cou_data) {
                //如果是购物送(任务集市)
                if ($cou_data['cou_type'] == VOUCHER_SHOPING && empty($user_cou)) {
                    if ($user_cou) {
                        $res = $res->where('user_id', $cou_data['ru_id']);

                        if ($cou_data['cou_ok_goods']) {
                            $res = $res->whereIn('goods_id', $cou_data['cou_ok_goods']);
                        } elseif ($cou_data['cou_ok_cat']) {
                            $cou_children = $this->couponsService->getCouChildren($cou_data['cou_ok_cat']);
                            $cou_children = $this->baseRepository->getExplode($cou_children);
                            if ($cou_children) {
                                $res = $res->whereIn('cat_id', $cou_children);
                            }
                        }
                    } else {
                        $res = $res->where('user_id', $cou_data['ru_id']);

                        if ($cou_data['cou_goods']) {
                            $cou_data['cou_goods'] = !is_array($cou_data['cou_goods']) ? explode(",", $cou_data['cou_goods']) : [];
                            $res = $res->whereIn('goods_id', $cou_data['cou_goods']);
                        } elseif ($cou_data['spec_cat']) {
                            $cou_children = $this->couponsService->getCouChildren($cou_data['spec_cat']);
                            $cou_children = $this->baseRepository->getExplode($cou_children);
                            if ($cou_children) {
                                $res = $res->whereIn('cat_id', $cou_children);
                            }
                        }
                    }
                } else {
                    $res = $res->where('user_id', $cou_data['ru_id']);

                    if ($cou_data['cou_goods']) {
                        $cou_data['cou_goods'] = !is_array($cou_data['cou_goods']) ? explode(",", $cou_data['cou_goods']) : [];
                        $res = $res->whereIn('goods_id', $cou_data['cou_goods']);
                    } elseif ($cou_data['spec_cat']) {
                        $cou_children = $this->couponsService->getCouChildren($cou_data['spec_cat']);
                        $cou_children = $this->baseRepository->getExplode($cou_children);
                        if ($cou_children) {
                            $res = $res->whereIn('cat_id', $cou_children);
                        }
                    }
                }
            }
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $attrList = GoodsAttr::whereIn('goods_attr_id', $filter_attr);
            $attrList = $this->baseRepository->getToArrayGet($attrList);
            $attr_value = $this->baseRepository->getKeyPluck($attrList, 'attr_value');

            $goodsList = GoodsAttr::whereIn('attr_value', $attr_value);
            $goodsList = $this->baseRepository->getToArrayGet($goodsList);
            $goodsList = $this->baseRepository->getKeyPluck($goodsList, 'goods_id');

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        /* 查询仅自营和标识自营店铺的商品 */
        if (isset($where_ext['self']) && $where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)->orWhere(function ($query) {
                    $query->whereHas('getShopInfo', function ($query) {
                        $query->where('self_run', 1);
                    });
                });
            });
        }

        $res = $res->count();

        /* 返回商品总数 */
        return $res;
    }
}
