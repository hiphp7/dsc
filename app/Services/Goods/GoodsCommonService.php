<?php

namespace App\Services\Goods;

use App\Models\Goods;
use App\Models\GoodsCat;
use App\Models\VolumePrice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class GoodsCommonService
{
    protected $timeRepository;
    protected $config;
    protected $baseRepository;
    protected $goodsWarehouseService;
    protected $goodsAttrService;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        GoodsWarehouseService $goodsWarehouseService,
        GoodsAttrService $goodsAttrService,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->goodsAttrService = $goodsAttrService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 商品URL地址
     *
     * @param $params
     * @return string
     */
    public function goodsUrl($params)
    {
        return route('goods', $params);
    }

    /**
     * 添加商品名样式
     * @param string $goods_name 商品名称
     * @param string $style 样式参数
     * @return  string
     */
    public function addStyle($goods_name, $style)
    {
        $goods_style_name = $goods_name;

        $arr = explode('+', $style);

        $font_color = !empty($arr[0]) ? $arr[0] : '';
        $font_style = !empty($arr[1]) ? $arr[1] : '';

        if ($font_color != '') {
            $goods_style_name = '<font style="color:' . $font_color . '; font-size:inherit;">' . $goods_style_name . '</font>';
        }
        if ($font_style != '') {
            $goods_style_name = '<' . $font_style . '>' . $goods_style_name . '</' . $font_style . '>';
        }
        return $goods_style_name;
    }

    /**
     * 获取商品判断最终价格
     *
     * @param array $price
     * @param int $discount
     * @param array $goods
     * @return array
     */
    public function getGoodsPrice($price = [], $discount = 0, $goods = [])
    {
        // 商家商品禁用会员权益折扣
        if (isset($goods['user_id']) && $goods['user_id'] > 0 && isset($goods['is_discount']) && $goods['is_discount'] == 0) {
            $discount = 1;
        }

        /* 本店价 */
        if (isset($price['user_price']) && $price['user_price'] > 0) {
            // 会员价格
            if (isset($price['percentage']) && $price['percentage'] == 1) {
                $shop_price = $price['shop_price'] * $price['user_price'] / 100; // 百分比
            } else {
                $shop_price = $price['user_price']; // 固定价格
            }
        } else {
            if (isset($price['warehouse_price']) && $price['model_price'] == 1) {
                $shop_price = $price['warehouse_price'] * $discount;
            } elseif (isset($price['region_price']) && $price['model_price'] == 2) {
                $shop_price = $price['region_price'] * $discount;
            } else {
                $shop_price = $price['shop_price'] * $discount;
            }
        }
        $shop_price = number_format($shop_price, 2, '.', '');

        /* 促销价 */
        if (isset($price['warehouse_promote_price']) && $price['model_price'] == 1) {
            $promote_price = $price['warehouse_promote_price'];
        } elseif (isset($price['region_promote_price']) && $price['model_price'] == 2) {
            $promote_price = $price['region_promote_price'];
        } else {
            $promote_price = $price['promote_price'];
        }
        $promote_price = number_format($promote_price, 2, '.', '');

        /* 消费积分 */
        if (isset($price['wpay_integral']) && $price['model_price'] == 1) {
            $integral = $price['wpay_integral'];
        } elseif (isset($price['apay_integral']) && $price['model_price'] == 2) {
            $integral = $price['apay_integral'];
        } else {
            $integral = isset($price['integral']) ? $price['integral'] : 0;
        }

        /* 库存 */
        if (isset($price['wg_number']) && $price['model_price'] == 1) {
            $goods_number = $price['wg_number'];
        } elseif (isset($price['wag_number']) && $price['model_price'] == 2) {
            $goods_number = $price['wag_number'];
        } else {
            $goods_number = isset($price['goods_number']) ? $price['goods_number'] : 0;
        }

        $price['shop_price'] = number_format($price['shop_price'], 2, '.', '');
        $price['promote_price'] = number_format($price['promote_price'], 2, '.', '');

        $arr = [
            'shop_price' => $shop_price > 0 ? $shop_price : $price['shop_price'] * $discount,
            'promote_price' => $promote_price > 0 ? $promote_price : $price['promote_price'],
            'integral' => $integral,
            'goods_number' => $goods_number,
            'model_price' => $price['model_price'] ?? 0,
            'percentage' => $price['percentage'] ?? 0
        ];

        return $arr;
    }

    /**
     * 判断某个商品是否正在特价促销期
     *
     * @access  public
     * @param float $price 促销价格
     * @param string $start 促销开始日期
     * @param string $end 促销结束日期
     * @return  float   如果还在促销期则返回促销价，否则返回0
     */
    public function getBargainPrice($price, $start, $end)
    {
        if ($price == 0) {
            return 0;
        } else {
            $time = $this->timeRepository->getGmTime();
            if ($time >= $start && $time <= $end) {
                return $price;
            } else {
                return 0;
            }
        }
    }

    /**
     * 取得商品最终使用价格
     *
     * @param string $goods_id 商品编号
     * @param string $goods_num 购买数量
     * @param boolean $is_spec_price 是否加入规格价格
     * @param mix $spec 规格ID的数组或者逗号分隔的字符串
     * @param intval $add_tocart 0,1  1代表非购物车进入该方法（SKU价格）
     * @param intval $show_goods 0,1  商品详情页ajax，1代表SKU价格开启（SKU价格）
     *
     * @return string 商品最终购买价格
     */
    public function getFinalPrice($goods_id, $goods_num = '1', $is_spec_price = false, $spec = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $type = 0, $presale = 0, $add_tocart = 1, $show_goods = 0, $product_promote_price = 0, $userrank = [])
    {
        $spec_price = 0;

        $warehouse_area['warehouse_id'] = $warehouse_id;
        $warehouse_area['area_id'] = $area_id;
        $warehouse_area['area_city'] = $area_city;

        if ($is_spec_price) {
            if (!empty($spec)) {
                $spec_price = $this->goodsAttrService->specPrice($spec, $goods_id, $warehouse_area);
            }
        }

        $final_price = '0'; //商品最终购买价格
        $volume_price = '0'; //商品优惠价格
        $promote_price = '0'; //商品促销价格
        $user_price = '0'; //商品会员价格
        if ($userrank) {
            $user_rank = isset($userrank['rank_id']) && $userrank['rank_id'] ? $userrank['rank_id'] : 1;
        } else {
            $user_rank = session('user_rank', 1); //用户等级
        }

        //取得商品优惠价格列表
        $price_list = $this->getVolumePriceList($goods_id);

        if (!empty($price_list)) {
            foreach ($price_list as $value) {
                if ($goods_num >= $value['number']) {
                    $volume_price = $value['price'];
                }
            }
        }

        //预售条件---预售没有会员价、、折扣价

        $is_presale = Goods::where('goods_id', $goods_id)->where('is_on_sale', 0)->where('is_alone_sale', 1)->where('is_delete', 0);

        $is_presale = $is_presale->whereHas('getPresaleActivity', function ($query) {
            $query->where('review_status', 3);
        });

        $is_presale = $is_presale->count();

        if ($is_presale > 0 || $presale == 1) {
            $user_rank = 1;
            $discount = 1; //会员折扣
        } else {
            if ($userrank) {
                $discount = isset($userrank['discount']) && $userrank['discount'] ? $userrank['discount'] : 1;
            } else {
                $discount = session('discount', 1); //会员折扣
            }
        }

        $now_promote = 0;
        $promote_price = 0;
        if ($presale != CART_PACKAGE_GOODS) {
            /* 取得商品信息 */
            $goods = Goods::where('goods_id', $goods_id);

            $where = [
                'area_id' => $area_id,
                'area_city' => $area_city,
                'area_pricetype' => $this->config['area_pricetype']
            ];

            $goods = $goods->with([
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
            ]);

            $goods = $this->baseRepository->getToArrayFirst($goods);

            if ($goods) {
                $goods['user_price'] = $goods['get_member_price'] ? $goods['get_member_price']['user_price'] : 0;

                $price = [
                    'model_price' => isset($goods['model_price']) ? $goods['model_price'] : 0,
                    'user_price' => isset($goods['get_member_price']['user_price']) ? $goods['get_member_price']['user_price'] : 0,
                    'percentage' => isset($goods['get_member_price']['percentage']) ? $goods['get_member_price']['percentage'] : 0,
                    'warehouse_price' => isset($goods['get_warehouse_goods']['warehouse_price']) ? $goods['get_warehouse_goods']['warehouse_price'] : 0,
                    'region_price' => isset($goods['get_warehouse_area_goods']['region_price']) ? $goods['get_warehouse_area_goods']['region_price'] : 0,
                    'shop_price' => isset($goods['shop_price']) ? $goods['shop_price'] : 0,
                    'warehouse_promote_price' => isset($goods['get_warehouse_goods']['warehouse_promote_price']) ? $goods['get_warehouse_goods']['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($goods['get_warehouse_area_goods']['region_promote_price']) ? $goods['get_warehouse_area_goods']['region_promote_price'] : 0,
                    'promote_price' => isset($goods['promote_price']) ? $goods['promote_price'] : 0,
                    'wg_number' => isset($goods['get_warehouse_goods']['region_number']) ? $goods['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($goods['get_warehouse_area_goods']['region_number']) ? $goods['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($goods['goods_number']) ? $goods['goods_number'] : 0
                ];

                $price = $this->getGoodsPrice($price, $discount, $goods);

                $goods['shop_price'] = $price['shop_price'];
                $goods['promote_price'] = $price['promote_price'];
                $goods['goods_number'] = $price['goods_number'];
            }

            $goods['user_id'] = isset($goods['user_id']) ? $goods['user_id'] : 0;

            if ($this->config['add_shop_price'] == 0 && $product_promote_price <= 0) {
                $product_spec = !empty($spec) && is_array($spec) ? implode(",", $spec) : '';
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $product_spec, $warehouse_id, $area_id, $area_city);
                $product_promote_price = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;
            }

            $time = $this->timeRepository->getGmTime();

            //当前商品正在促销时间内
            if (isset($goods['promote_start_date']) && isset($goods['promote_end_date'])) {
                if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date'] && $goods['is_promote']) {
                    $now_promote = 1;
                }
            }

            if ($this->config['add_shop_price'] == 0 && $now_promote == 1 && $spec) {
                $goods['promote_price'] = $product_promote_price;
            }

            /* 计算商品的促销价格 */
            if (isset($goods['promote_price']) && $goods['promote_price'] > 0) {
                $promote_price = $this->getBargainPrice($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $goods['shop_price'] = isset($goods['shop_price']) ? $goods['shop_price'] : 0;
            $goods['user_price'] = isset($goods['user_price']) ? $goods['user_price'] : 0;
        } else {
            $goods['user_price'] = 0;
            $goods['shop_price'] = 0;
        }
        //取得商品促销价格列表

        //取得商品会员价格列表
        if (!empty($spec) && $this->config['add_shop_price'] == 0) {
            /* 会员等级价格 */
            if ($goods['user_price'] > 0 && $goods['user_price'] < $spec_price) {
                if (isset($price['percentage']) && $price['percentage'] == 1) {
                    $user_price = $goods['shop_price'] * $price['user_price'] / 100;
                } else {
                    $user_price = $goods['user_price'];
                }
            } else {
                if ($now_promote == 1) {
                    $user_price = $promote_price;
                } else {
                    $user_price = $spec_price * $discount;
                }
            }

            /* SKU价格 */
            if ($show_goods == 1) {
                /* 会员等级价格 */
                if (!empty($goods['user_price'])) {
                    $spec_price = $goods['user_price'];
                } else {
                    $spec_price = $spec_price * $discount;
                }
            }
        } else {
            $user_price = $goods['shop_price'];
        }

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && $now_promote == 0) {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        } elseif (!empty($volume_price) && $now_promote == 0) {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        } elseif (empty($volume_price) && $now_promote == 1) {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        } elseif (!empty($volume_price) && $now_promote == 1) {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        } else {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($spec)) {
                if ($type == 0) {
                    if ($add_tocart == 1) {
                        $final_price += $spec_price;
                    }
                }
            }
        }

        if ($this->config['add_shop_price'] == 1) {
            if ($type == 1 && $now_promote == 0) {
                //返回商品属性价
                $final_price = $spec_price;
            }
        }

        //返回商品最终购买价格
        return $final_price;
    }

    /**
     * 取得商品优惠价格列表
     *
     * @param $goods_id
     * @param int $price_type
     * @param int $is_pc
     * @return array
     */
    public function getVolumePriceList($goods_id, $price_type = 1, $is_pc = 0)
    {
        $res = VolumePrice::where('goods_id', $goods_id)
            ->where('price_type', $price_type);

        $res = $res->orderBy('volume_number');

        $res = $this->baseRepository->getToArrayGet($res);

        $res_count = count($res);
        $volume_price = [];

        if ($res) {
            foreach ($res as $k => $v) {
                $volume_price[$k]['id'] = $v['id'];
                $volume_price[$k]['price'] = $v['volume_price'];
                $volume_price[$k]['format_price'] = $this->dscRepository->getPriceFormat($v['volume_price']);
                //pc前台显示区分阶梯价格
                if ($is_pc > 0) {
                    if (($res_count - 1) > $k) {
                        $volume_price[$k]['number'] = $v['volume_number'] . '-' . ($res[$k + 1]['volume_number'] - 1);
                    } else {
                        $volume_price[$k]['number'] = $v['volume_number'] . lang('common.and_more');
                    }
                } else {
                    $volume_price[$k]['number'] = $v['volume_number'];
                }
            }
        }

        return $volume_price;
    }

    /**
     * 商品退换货标识
     *
     * @param $goods_cause
     * @param int $buy_drp_show
     * @return array
     */
    public function getGoodsCause($goods_cause, $buy_drp_show = 0)
    {
        $arr = array();

        if ($goods_cause) {
            $goods_cause = explode(',', $goods_cause);
            foreach ($goods_cause as $key => $row) {
                $arr[$key]['cause'] = $row;
                $arr[$key]['lang'] = $GLOBALS['_LANG']['order_return_type'][$row];

                if ($key == 0) {
                    $arr[$key]['is_checked'] = 1;
                } else {
                    $arr[$key]['is_checked'] = 0;
                }

                // 购买成为分销商品不显示退货、退款
                if ($buy_drp_show == 1 && in_array($row, [1, 3])) {
                    unset($arr[$key]);
                }
            }
        }

        return $arr;
    }

    /**
     * 商品限购
     *
     * @param int $goods_id
     * @return mixed
     */
    public function getPurchasingGoodsInfo($goods_id = 0)
    {
        $row = Goods::select('is_xiangou', 'xiangou_num', 'xiangou_start_date', 'xiangou_end_date', 'goods_name')
            ->where('goods_id', $goods_id);

        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * Ajax楼层分类商品列表
     *
     * @param array $children 分类列表
     * @param int $num 查询数量
     * @param int $warehouse_id 仓库ID
     * @param int $area_id 仓库地区
     * @param int $area_city 仓库地区城市
     * @param string $goods_ids 商品ID
     * @param int $ru_id 店铺ID
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getFloorAjaxGoods($children = [], $num = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $goods_ids = '', $ru_id = 0)
    {
        if ($children) {
            $childrenCache = is_array($children) ? implode(',', $children) : $children;
        } else {
            $childrenCache = '';
        }

        if ($goods_ids) {
            $goodsidsCache = is_array($goods_ids) ? implode(',', $goods_ids) : $goods_ids;
        } else {
            $goodsidsCache = '';
        }

        $cache_name = "get_floor_ajax_goods_" . $childrenCache . '_' . $num . '_' . $warehouse_id . '_' . $area_id . '_' . $area_city . '_' . $goodsidsCache . '_' . $ru_id;

        /* 查询扩展分类数据 */
        $goodsParam = [];
        if (!empty($children)) {

            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
            $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
            $extension_goods = $this->baseRepository->getFlatten($extension_goods);

            $goodsParam = [
                'children' => $children,
                'extension_goods' => $extension_goods
            ];
        }

        $goods_res = cache($cache_name);

        if (is_null($goods_res)) {
            $goods_res = Goods::where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0)
                ->where('is_show', 1)
                ->where(function ($query) use ($goodsParam) {
                    if (isset($goodsParam['children']) && $goodsParam['children']) {
                        $query = $query->whereIn('cat_id', $goodsParam['children']);
                    }

                    if (isset($goodsParam['extension_goods']) && $goodsParam['extension_goods']) {
                        $query->orWhere(function ($query) use ($goodsParam) {
                            $query->whereIn('goods_id', $goodsParam['extension_goods']);
                        });
                    }
                });

            if ($ru_id > 0) {
                $goods_res = $goods_res->where('user_id', $ru_id);
            }

            if (!empty($goods_ids)) {
                $goods_ids = $this->baseRepository->getExplode($goods_ids);
                $goods_res = $goods_res->whereIn('goods_id', $goods_ids);
            }

            $goods_res = $this->dscRepository->getAreaLinkGoods($goods_res, $area_id, $area_city);

            if ($this->config['review_goods'] == 1) {
                $goods_res = $goods_res->where('review_status', '>', 2);
            }

            $where = [
                'area_id' => $area_id,
                'area_city' => $area_city,
                'area_pricetype' => $this->config['area_pricetype']
            ];

            $user_rank = session('user_rank');
            $goods_res = $goods_res->with(['getMemberPrice' => function ($query) use ($user_rank) {
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

            if ($num > 0) {
                $goods_res = $goods_res->take($num);
            }

            $goods_res = $goods_res->orderByRaw('sort_order, goods_id desc');

            $goods_res = $this->baseRepository->getToArrayGet($goods_res);

            if ($goods_res) {
                foreach ($goods_res as $idx => $row) {
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

                    $price = $this->getGoodsPrice($price, session('discount'), $row);

                    $row['shop_price'] = $price['shop_price'];
                    $row['promote_price'] = $price['promote_price'];
                    $row['goods_number'] = $price['goods_number'];

                    $goods_res[$idx] = $row;

                    if ($row['promote_price'] > 0) {
                        $promote_price = $this->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    $goods_res[$idx]['is_promote'] = $row['is_promote'];
                    $goods_res[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                    $goods_res[$idx]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $goods_res[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                    $goods_res[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    $goods_res[$idx]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                    $goods_res[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    $goods_res[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                    $goods_res[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                }
            }

            cache()->forever($cache_name, $goods_res);
        }

        return $goods_res;
    }
}
