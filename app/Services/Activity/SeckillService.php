<?php

namespace App\Services\Activity;

use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\OrderGoods;
use App\Models\Seckill;
use App\Models\SeckillGoods;
use App\Models\SeckillTimeBucket;
use App\Models\TouchAdPosition;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Ads\AdsService;
use App\Services\Cart\CartCommonService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;

/**
 * Class SeckillService
 * @package App\Services\Activity
 */
class SeckillService
{
    protected $timeRepository;
    protected $goodsAttrService;
    protected $adsService;
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $goodsGalleryService;
    protected $orderGoodsService;
    protected $cartCommonService;

    public function __construct(
        TimeRepository $timeRepository,
        GoodsAttrService $goodsAttrService,
        AdsService $adsService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        GoodsGalleryService $goodsGalleryService,
        OrderGoodsService $orderGoodsService,
        CartCommonService $cartCommonService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->adsService = $adsService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->orderGoodsService = $orderGoodsService;
        $this->cartCommonService = $cartCommonService;
    }

    /**
     * 取得秒杀活动商品详情
     *
     * @param int $uid
     * @param int $seckill_id
     * @param int $tomorrow
     * @return array
     * @throws \Exception
     */
    public function seckill_detail($uid = 0, $seckill_id = 0, $tomorrow = 0)
    {
        $seckill = $this->seckill_info($seckill_id, $tomorrow);

        if ($seckill) {
            $seckill['goods_thumb'] = $this->dscRepository->getImagePath($seckill['goods_thumb']);
            $seckill['goods_img'] = $this->dscRepository->getImagePath($seckill['goods_img']);

            // 商品相册
            $seckill['pictures'] = $this->goodsGalleryService->getGoodsGallery($seckill['goods_id']);

            /*获取商品属性*/
            $seckill['attr'] = $this->goodsAttrService->goodsAttr($seckill['goods_id']);
            if ($seckill['attr']) {
                $seckill['attr_name'] = '';
                foreach ($seckill['attr'] as $k => $v) {
                    if ($seckill['attr_name']) {
                        $seckill['attr_name'] = $seckill['attr_name'] . '' . $v['attr_key'][0]['attr_value'];
                    } else {
                        $seckill['attr_name'] = $v['attr_key'][0]['attr_value'];
                    }
                }
            }

            /*获取商品规格参数*/
            $seckill['attr_parameter'] = $this->goodsAttrService->goodsAttrParameter($seckill['goods_id']);

            if (empty($seckill['desc_mobile']) && !empty($seckill['goods_desc'])) {
                $desc_preg = $this->dscRepository->descImagesPreg($seckill['goods_desc']);
                $seckill['goods_desc'] = $desc_preg['goods_desc'];

            }
            if (!empty($seckill['desc_mobile'])) {
                // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
                $desc_preg = $this->dscRepository->descImagesPreg($seckill['desc_mobile'], 'desc_mobile', 1);
                $seckill['goods_desc'] = $desc_preg['desc_mobile'];
            }

            $start_date = $this->timeRepository->getLocalStrtoTime($seckill['begin_time']);
            $end_date = $this->timeRepository->getLocalStrtoTime($seckill['end_time']);
            $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $seckill['goods_id'], $uid, 'seckill');

            if ($order_goods) {
                $seckill['order_number'] = $order_goods['goods_number'] ?? 0;
            }

            if ($uid > 0) {
                /*会员关注状态*/
                $collect_goods = CollectGoods::where('user_id', $uid)
                    ->where('goods_id', $seckill['goods_id'])
                    ->count();
                if ($collect_goods > 0) {
                    $seckill['is_collect'] = 1;
                } else {
                    $seckill['is_collect'] = 0;
                }
            } else {
                $seckill['is_collect'] = 0;
            }
        }

        return $seckill;
    }

    /**
     * 取得秒杀活动详情
     *
     * @param int $seckill_id
     * @param int $tomorrow
     * @return array
     * @throws \Exception
     */
    public function seckill_info($seckill_id = 0, $tomorrow = 0)
    {
        $seckill_id = intval($seckill_id);

        $seckill = SeckillGoods::where('id', $seckill_id);

        $seckill = $seckill->whereHas('getSeckill', function ($query) {
            $query->where('is_putaway', 1)->where('review_status', 3);
        });

        $seckill = $seckill->with([
            'getGoods' => function ($query) {
                $query->where('is_delete', 0);
            },
            'getSeckill',
            'getSeckillTimeBucket' => function ($query) {
                $query->select('id', 'begin_time', 'end_time');
            }
        ]);

        $seckill = $seckill->first();

        $seckill = $seckill ? $seckill->toArray() : [];

        if (is_null($seckill)) {
            return [];
        }

        if (isset($seckill['get_goods'])) {
            $seckill = collect($seckill)->merge($seckill['get_goods'])->except('get_goods')->all();
        }
        if (isset($seckill['get_seckill'])) {
            $seckill = collect($seckill)->merge($seckill['get_seckill'])->except('get_seckill')->all();
        }
        if (isset($seckill['get_seckill_time_bucket'])) {
            $seckill = collect($seckill)->merge($seckill['get_seckill_time_bucket'])->except('get_seckill_time_bucket')->all();
        }

        $now = $time = $this->timeRepository->getGmTime();
        $tmr = 0;
        if ($tomorrow == 1) {
            $tmr = 86400;
        }
        $begin_time = $this->timeRepository->getLocalStrtoTime($seckill['begin_time']) + $tmr;
        $end_time = $this->timeRepository->getLocalStrtoTime($seckill['end_time']) + $tmr;

        if ($begin_time < $now && $end_time > $now) {
            $seckill['status'] = true;
        } else {
            $seckill['status'] = false;
        }
        $seckill['is_end'] = $now > $end_time ? 1 : 0;

        $stat = $this->secGoodsStats($seckill_id, $begin_time, $end_time);
        $seckill = $this->baseRepository->getArrayMerge($seckill, $stat);

        $seckill['rz_shopName'] = $this->merchantCommonService->getShopName($seckill['user_id'], 1); //店铺名称

        // 格式化时间 如果活动没有开始那么计算的时间是按照开始时间来计算
        if (!$seckill['is_end'] && !$seckill['status']) {
            $end_time = $begin_time;
        }

        /* 格式化时间 */
        $seckill['formated_start_date'] = $begin_time;
        $seckill['formated_end_date'] = $end_time;
        $seckill['current_time'] = $now;

        $seckill['sec_price_format'] = $this->dscRepository->getPriceFormat($seckill['sec_price']);

        if (!$seckill['sec_num']) {
            $seckill['is_end'] = 1;
            $seckill['status'] = false;
        }

        return $seckill;
    }

    /**
     * 秒杀日期内的商品
     * @param $id
     * @param int $page
     * @param int $size
     * @param int $tomorrow
     * @return mixed
     */
    public function seckill_goods_results($id, $page = 1, $size = 10, $tomorrow = 0)
    {
        $begin = ($page - 1) * $size;
        $day = 24 * 60 * 60;
        $date_begin = ($tomorrow == 1) ? $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate('Ymd')) + $day : $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate('Ymd'));
        $seckill = Seckill::select("sec_id")
            ->where('begin_time', '<=', $date_begin)
            ->where('acti_time', '>', $date_begin);

        $seckill = $this->baseRepository->getToArrayGet($seckill);
        $seckill = $this->baseRepository->getKeyPluck($seckill, 'sec_id');

        $res = SeckillGoods::select('id', 'tb_id', 'sec_id', 'goods_id', 'sec_price', 'sec_num', 'sec_limit')
            ->whereHas('getSeckillTimeBucket', function ($query) use ($id) {
                $query->where('id', $id);
            });

        $where = [
            'begin_time' => $date_begin,
            'sec_id' => $seckill
        ];
        $res = $res->whereHas('getSeckill', function ($query) use ($where) {
            $query->where('is_putaway', 1)
                ->where('review_status', 3)
                ->where('begin_time', '<=', $where['begin_time'])
                ->whereIn('sec_id', $where['sec_id']);
        });

        $res = $res->whereHas('getGoods');

        $res = $res->with([
            'getSeckillTimeBucket' => function ($query) {
                $query->select('id', 'begin_time', 'end_time');
            },
            'getSeckill' => function ($query) {
                $query->select('sec_id', 'acti_title', 'acti_time');
            },
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'shop_price', 'market_price', 'goods_name');
            }
        ]);

        $res = $res->withCount([
            'getSeckill as begin_time' => function ($query) {
                $query->select('begin_time');
            }
        ]);

        $res = $res->offset($begin)
            ->limit($size)
            ->orderBy('goods_id', 'DESC')
            ->orderBy('begin_time', 'ASC');

        $res = $this->baseRepository->getToArrayGet($res);

        $now = $time = $this->timeRepository->getGmTime();
        $tmr = 86400;

        if ($res) {
            foreach ($res as $k => $v) {

                /* 删除冲突ID */
                if ($v['get_seckill_time_bucket']) {
                    unset($v['get_seckill_time_bucket']['id']);
                }

                $v = $this->baseRepository->getArrayCollapse([$v, $v['get_seckill_time_bucket'], $v['get_seckill'], $v['get_goods']]);
                $v = $this->baseRepository->getArrayExcept($v, ['get_seckill_time_bucket', 'get_seckill', 'get_goods']);

                $res[$k] = $v;

                $res[$k]['current_time'] = $now;
                $res[$k]['begin_time'] = $this->timeRepository->getLocalStrtoTime($v['begin_time']);

                if ($tomorrow == 1) {
                    $res[$k]['begin_time'] = $this->timeRepository->getLocalStrtoTime($v['begin_time']) + $tmr;
                    $res[$k]['end_time'] = $this->timeRepository->getLocalStrtoTime($v['end_time']) + $tmr;
                } else {
                    $res[$k]['begin_time'] = $this->timeRepository->getLocalStrtoTime($v['begin_time']);
                    $res[$k]['end_time'] = $this->timeRepository->getLocalStrtoTime($v['end_time']);
                }
                if ($res[$k]['begin_time'] < $now && $res[$k]['end_time'] > $now) {
                    $res[$k]['status'] = true;
                }
                if ($res[$k]['end_time'] < $now) {
                    $res[$k]['is_end'] = true;
                }
                if ($res[$k]['begin_time'] > $now) {
                    $res[$k]['soon'] = true;
                }

                $res[$k]['data_end_time'] = $this->timeRepository->getLocalDate('H:i:s', $res[$k]['begin_time']);
                $res[$k]['sec_price_formated'] = $this->dscRepository->getPriceFormat($v['sec_price']);
                $res[$k]['market_price_formated'] = $this->dscRepository->getPriceFormat($v['market_price']);
                $res[$k]['sales_volume'] = $this->secGoodsStats($v['id'], $res[$k]['begin_time'], $res[$k]['end_time']);
                $res[$k]['valid_goods'] = $res[$k]['sales_volume']['valid_goods'];

                $total_num = $v['sec_num'] + $res[$k]['sales_volume']['valid_goods'];
                $res[$k]['percent'] = (isset($total_num) && $total_num > 0) ? ceil($res[$k]['valid_goods'] / $total_num * 100) : 100;
                $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                $res[$k]['url'] = dsc_url('/#/seckill/detail') . '?' . http_build_query(['seckill_id' => $v['id'], 'tomorrow' => $tomorrow], '', '&');
                $res[$k]['app_page'] = config('route.seckill.detail') . $v['id'] . '&tomorrow=' . $tomorrow;
            }
        }

        return $res;
    }

    /**
     * 取得秒杀活动的时间表
     * @return array
     */
    public function getSeckillTime()
    {
        $now = $time = $this->timeRepository->getGmTime();
        $day = 24 * 60 * 60;

        $localData = $this->timeRepository->getLocalDate('Ymd');
        $date_begin = $this->timeRepository->getLocalStrtoTime($localData);
        $date_next = $this->timeRepository->getLocalStrtoTime($localData) + $day;

        $stb = SeckillTimeBucket::select('id', 'title', 'begin_time', 'end_time')
            ->orderBy('begin_time', 'ASC')
            ->get();
        $stb = $stb ? $stb->toArray() : [];

        $sec_id_today = Seckill::selectRaw('GROUP_CONCAT(sec_id) AS sec_id')
            ->where('begin_time', '<=', $date_begin)
            ->where('acti_time', '>', $date_begin)
            ->where('is_putaway', 1)
            ->where('review_status', 3)
            ->orderBy('acti_time', 'ASC')
            ->first();
        $sec_id_today = $sec_id_today ? $sec_id_today->toArray() : [];
        $arr = [];
        if ($stb) {
            foreach ($stb as $k => $v) {
                $v['local_end_time'] = $this->timeRepository->getLocalStrtoTime($v['end_time']);
                if ($v['local_end_time'] > $now && $sec_id_today) {
                    $arr[$k]['id'] = $v['id'];
                    $arr[$k]['title'] = $v['title'];
                    $arr[$k]['status'] = false;
                    $arr[$k]['is_end'] = false;
                    $arr[$k]['soon'] = false;
                    $arr[$k]['begin_time'] = $begin_time = $this->timeRepository->getLocalStrtoTime($v['begin_time']);
                    $arr[$k]['end_time'] = $end_time = $this->timeRepository->getLocalStrtoTime($v['end_time']);
                    $arr[$k]['frist_end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime($v['end_time']));
                    if ($begin_time < $now && $end_time > $now) {
                        $arr[$k]['status'] = true;
                    }
                    if ($end_time < $now) {
                        $arr[$k]['is_end'] = true;
                    }
                    if ($begin_time > $now) {
                        $arr[$k]['soon'] = true;
                    }
                }
            }
            $sec_id_tomorrow = Seckill::selectRaw('GROUP_CONCAT(sec_id) AS sec_id')
                ->where('begin_time', '<=', $date_next)
                ->where('acti_time', '>', $date_next)
                ->where('is_putaway', 1)
                ->where('review_status', 3)
                ->orderBy('acti_time', 'ASC')
                ->first();
            $sec_id_tomorrow = $sec_id_tomorrow ? $sec_id_tomorrow->toArray() : [];

            if (count($arr) > 4) {
                $arr = array_slice($arr, 0, 4);
            }
            if (count($arr) < 4) {
                if (count($arr) == 0) {
                    $stb = array_slice($stb, 0, 4);
                }
                if (count($arr) == 1) {
                    $stb = array_slice($stb, 0, 3);
                }
                if (count($arr) == 2) {
                    $stb = array_slice($stb, 0, 2);
                }
                if (count($arr) == 3) {
                    $stb = array_slice($stb, 0, 1);
                }
                foreach ($stb as $k => $v) {
                    if ($sec_id_tomorrow) {
                        $arr['tmr' . $k]['id'] = $v['id'];
                        $arr['tmr' . $k]['title'] = $v['title'];
                        $arr['tmr' . $k]['status'] = false;
                        $arr['tmr' . $k]['is_end'] = false;
                        $arr['tmr' . $k]['soon'] = true;
                        $arr['tmr' . $k]['begin_time'] = $this->timeRepository->getLocalStrtoTime($v['begin_time']) + $day;
                        $arr['tmr' . $k]['end_time'] = $this->timeRepository->getLocalStrtoTime($v['end_time']) + $day;
                        $arr['tmr' . $k]['frist_end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime($v['end_time']) + $day);
                        $arr['tmr' . $k]['tomorrow'] = 1;
                    }
                }
            }

            $arr = collect($arr)->values()->all();
        }
        return $arr;
    }

    /**
     * 获取秒杀商品
     *
     * @return array
     */
    public function getTopSeckillGoods()
    {
        $date_begin = $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate('Ymd'));
        $res = SeckillGoods::whereHas('getGoods')
            ->whereHas('getSeckill', function ($query) use ($date_begin) {
                $query->where('acti_time', '>=', $date_begin);
            })
            ->with('getSeckillTimeBucket');

        $res = $res->take(5);

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'shop_price', 'sales_volume', 'goods_thumb');
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $look_top) {
                $look_top = collect($look_top)->merge($look_top['get_goods'])->except('get_goods')->all();

                $look_top['goods_thumb'] = $this->dscRepository->getImagePath($look_top['goods_thumb']);
                $look_top['url'] = $this->dscRepository->buildUri('seckill', ['act' => "view", 'secid' => $look_top['id']], $look_top['goods_name']);

                $res[$key] = $look_top;
            }
        }

        return $res;
    }

    /**
     * 获取商家秒杀商品
     *
     * @param int $sec_goods_id
     * @param int $ru_id
     * @return array
     */
    public function getMerchantSeckillGoods($sec_goods_id = 0, $ru_id = 0)
    {
        $date_begin = $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate('Ymd'));
        $res = SeckillGoods::whereHas('getGoods', function ($query) use ($ru_id) {
            $query->where('user_id', $ru_id);
        })
            ->whereHas('getSeckill', function ($query) use ($date_begin) {
                $query->where('acti_time', '>=', $date_begin);
            })
            ->with('getSeckillTimeBucket');

        $res = $res->take(4);

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'shop_price', 'sales_volume', 'goods_thumb');
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        foreach ($res as $key => $row) {
            $row = collect($row)->merge($row['get_goods'])->except('get_goods')->all();

            $row['shop_price'] = $this->dscRepository->getPriceFormat($row['sec_price'], false);
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['url'] = $this->dscRepository->buildUri('seckill', ['act' => "view", 'secid' => $row['id']], $row['goods_name']);

            $res[$key] = $row;
        }

        return $res;
    }


    /**
     * 秒杀商品加入购物车
     *
     * @param int $uid
     * @param $seckill_id
     * @param int $number
     * @param string $specs
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     * @throws \Exception
     */
    public function getSeckillBuy($uid = 0, $seckill_id, $number = 1, $specs = '', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        /* 查询：取得秒杀活动信息 */
        $seckill = $this->seckill_info($seckill_id);

        $start_date = $this->timeRepository->getLocalStrtoTime($seckill['begin_time']);
        $end_date = $this->timeRepository->getLocalStrtoTime($seckill['end_time']);
        $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $seckill['goods_id'], $uid, 'seckill');

        /* 秒杀限购 start */
        $restrict_amount = $seckill['sec_limit'];
        $order_number = $order_goods['goods_number'] + $number;
        if ($order_goods['goods_number'] > 0 && $order_goods['goods_number'] >= $restrict_amount) {
            $result = [
                'error' => 1,
                'mesg' => lang('js_languages.js_languages.common.Already_buy') . $order_goods['goods_number'] . lang('js_languages.js_languages.common.Already_buy_two')
            ];
            return $result;
        } elseif ($order_goods['goods_number'] > 0 && $order_number > $restrict_amount) {
            $buy_num = $restrict_amount - $order_goods['goods_number'];

            $result = [
                'error' => 1,
                'mesg' => lang('js_languages.js_languages.common.Already_buy') . $buy_num . lang('js_languages.js_languages.common.jian')
            ];
            return $result;
        } elseif ($number > $restrict_amount) {
            $result = [
                'error' => 1,
                'mesg' => lang('js_languages.js_languages.common.Purchase_quantity')
            ];
            return $result;
        }
        /* 秒杀限购 end */

        /* 查询：检查秒杀活动是否是进行中 */
        if (!$seckill['status']) {
            $result = [
                'error' => 1,
                'mesg' => lang('seckill.gb_error_status')
            ];
            return $result;
        }
        $attr_list = [];
        $products = [];
        if ($specs) {
            $products = $this->goodsAttrService->getProductsInfo($seckill['goods_id'], $specs, $warehouse_id, $area_id, $area_city);

            $attr_list = $this->goodsAttrService->getAttrNameById($specs);
        }
        $goods_attr = !empty(join(chr(13) . chr(10), $attr_list)) ? join(chr(13) . chr(10), $attr_list) : '';

        /* 更新：清空购物车中所有秒杀商品 */
        $this->cartCommonService->clearCart($uid, CART_SECKILL_GOODS);

        $time = $this->timeRepository->getGmTime();

        $goods_price = isset($seckill['sec_price']) && $seckill['sec_price'] > 0 ? $seckill['sec_price'] : $seckill['shop_price'];
        $cart = [
            'user_id' => $uid,
            'goods_id' => $seckill['goods_id'],
            'product_id' => isset($products['product_id']) ? $products['product_id'] : 0,
            'goods_sn' => addslashes($seckill['goods_sn']),
            'goods_name' => addslashes($seckill['goods_name']),
            'market_price' => $seckill['market_price'],
            'goods_price' => $goods_price,
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $specs,
            'ru_id' => $seckill['user_id'],
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'is_real' => $seckill['is_real'],
            'extension_code' => 'seckill' . $seckill_id,
            'parent_id' => 0,
            'rec_type' => CART_SECKILL_GOODS,
            'is_gift' => 0,
            'add_time' => $time,
            'freight' => $seckill['freight'],
            'tid' => $seckill['tid'],
            'shipping_fee' => $seckill['shipping_fee'],
            'is_shipping' => $seckill['is_shipping'] ?? 0,
        ];

        Cart::insertGetId($cart);

        /* 更新：记录购物流程类型：秒杀 */
        $result = [
            'flow_type' => CART_SECKILL_GOODS,
            'extension_code' => 'seckill',
            'extension_id' => $seckill_id
        ];

        return $result;
    }

    /**
     * 秒杀广告位
     * @param string $ad_type
     * @param int $num
     * @return array|string
     */
    public function seckill_ads($ad_type = 'seckill', $num = 6)
    {
        $position = TouchAdPosition::where(['ad_type' => $ad_type, 'tc_type' => 'banner'])->orderBy('position_id', 'desc')->first();
        $banner_ads = [];
        if (!empty($position)) {
            $banner_ads = $this->adsService->getTouchAds($position->position_id, $num);
        }

        return $banner_ads;
    }

    /**
     * 取得秒杀活动商品统计信息
     *
     * @param int $sec_id 秒杀活动ID
     * @param string $begin_time 开始时间
     * @param string $end_time 结束时间
     * @return mixed
     */
    public function secGoodsStats($sec_id = 0, $begin_time = '', $end_time = '')
    {
        $sec_id = intval($sec_id);

        /* 取得秒杀活动商品ID */
        $goods_id = SeckillGoods::where('id', $sec_id)->value('goods_id');

        /* 取得总订单数和总商品数 */
        $stat = OrderGoods::selectRaw("COUNT(*) AS total_order, SUM(goods_number) AS total_goods")
            ->where('extension_code', 'seckill' . $sec_id)
            ->where('goods_id', $goods_id);

        $where = [
            'order_status' => [OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART],
            'begin_time' => $begin_time,
            'end_time' => $end_time
        ];
        $stat = $stat->whereHas('getOrder', function ($query) use ($where) {
            $query = $query->whereIn('order_status', $where['order_status']);

            if ($where['begin_time'] && $where['end_time']) {
                $query->whereBetween('pay_time', [$where['begin_time'], $where['end_time']]);
            }
        });

        $stat = $this->baseRepository->getToArrayFirst($stat);

        if ($stat['total_order'] == 0) {
            $stat['total_goods'] = 0;
        }

        $stat['valid_order'] = $stat['total_order'];
        $stat['valid_goods'] = $stat['total_goods'];

        return $stat;
    }
}
