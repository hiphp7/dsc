<?php

namespace App\Services\Activity;

use App\Models\Cart;
use App\Models\GoodsActivity;
use App\Models\PackageGoods;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsActivityService;
use App\Services\User\UserCommonService;

class PackageService
{
    protected $timeRepository;
    protected $config;
    protected $userCommonService;
    protected $goodsActivityService;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        UserCommonService $userCommonService,
        GoodsActivityService $goodsActivityService,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->userCommonService = $userCommonService;
        $this->goodsActivityService = $goodsActivityService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得超值礼包列表
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getPackageList($where = [])
    {
        $now = $this->timeRepository->getGmTime();

        /**
         * 兼容API & Web TODO
         */
        if (isset($where['user_id'])) {
            $rank = $this->userCommonService->getUserRankByUid($where['user_id']);
            if ($rank) {
                $user_rank = $rank['rank_id'];
                $discount = $rank['discount'];
            } else {
                $user_rank = 1;
                $discount = 100;
            }
        } else {
            $user_rank = session('user_rank', 1);
            $discount = session('discount', 100);
        }

        $res = GoodsActivity::where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('act_type', GAT_PACKAGE)
            ->where('review_status', 3);

        $res = $res->orderBy('end_time')->get();

        $res = $res ? $res->toArray() : [];

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $row['activity_thumb'] = !empty($row['activity_thumb']) ? $this->dscRepository->getImagePath($row['activity_thumb']) : $this->dscRepository->dscUrl('themes/ecmoban_dsc2017/images/17184624079016pa.jpg');
                $row['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['start_time']);
                $row['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['end_time']);
                $ext_arr = unserialize($row['ext_info']);
                unset($row['ext_info']);
                if ($ext_arr) {
                    foreach ($ext_arr as $key => $val) {
                        $row[$key] = $val;
                    }
                }

                $goods_res = PackageGoods::from('package_goods as pg')
                    ->select('pg.package_id', 'pg.goods_id', 'pg.goods_number', 'pg.admin_id', 'g.goods_sn', 'g.goods_name', 'g.market_price', 'g.goods_thumb', 'mp.user_price', 'g.shop_price')
                    ->leftJoin('goods as g', 'g.goods_id', '=', 'pg.goods_id')
                    ->leftJoin('member_price as mp', function ($query) use ($user_rank) {
                        $query->on('mp.goods_id', '=', 'g.goods_id')->where('mp.user_rank', $user_rank);
                    })
                    ->where('pg.package_id', $row['act_id'])
                    ->orderBy('pg.goods_id')
                    ->get();

                $goods_res = is_null($goods_res) ? [] : $goods_res->toArray();

                $package_price = $row['package_price'];

                $subtotal = 0;
                $goods_number = 0;
                if ($goods_res) {
                    foreach ($goods_res as $key => $val) {
                        if (isset($val['user_price']) && $val['user_price'] > 0) {
                            $val['rank_price'] = $val['user_price'];
                        } else {
                            $val['rank_price'] = $val['shop_price'] * $discount / 100;
                        }
                        $goods_res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                        $goods_res[$key]['market_price'] = $this->dscRepository->getPriceFormat($val['market_price']);
                        $goods_res[$key]['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                        $goods_res[$key]['rank_price'] = $this->dscRepository->getPriceFormat($val['rank_price']);

                        $goods_res[$key]['package_amounte'] = $this->dscRepository->getPriceFormat($val['shop_price'] * $val['goods_number'] - $package_price);
                        $subtotal += $val['rank_price'] * $val['goods_number'];
                        $goods_number += $val['goods_number'];
                    }
                }

                $row['goods_list'] = $goods_res;
                $row['subtotal'] = $this->dscRepository->getPriceFormat($subtotal);
                $row['saving'] = $this->dscRepository->getPriceFormat(($subtotal - $row['package_price']));
                $row['package_price'] = $this->dscRepository->getPriceFormat($package_price);
                $row['package_amounte'] = $this->dscRepository->getPriceFormat($subtotal - $package_price);
                $row['package_number'] = $goods_number;

                $list[] = $row;
            }
        }

        return $list;
    }

    /**
     * 获得超值礼包列表
     *
     * @param int $package_id
     * @return array
     * @throws \Exception
     */
    public function getPackageInfo($package_id = 0)
    {
        // 超值礼包详情
        $package = $this->goodsActivityService->getGoodsActivity($package_id, GAT_PACKAGE);
        if ($package) {
            /* 将时间转成可阅读格式 */
            $now = $this->timeRepository->getGmTime();

            if ($package['start_time'] <= $now && $package['end_time'] >= $now) {
                $package['is_on_sale'] = "1";
            } else {
                $package['is_on_sale'] = "0";
            }

            $package['start_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $package['start_time']);
            $package['end_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $package['end_time']);
            $row = unserialize($package['ext_info']);

            unset($package['ext_info']);
            if ($row) {
                foreach ($row as $key => $val) {
                    $package[$key] = $val;
                }
            }

            $goods_res = PackageGoods::from('package_goods  AS pg')
                ->select('pg.package_id', 'pg.goods_id', 'pg.goods_number', 'pg.admin_id', 'g.goods_sn', 'g.goods_name', 'g.goods_number as product_number', 'g.market_price', 'g.goods_thumb', 'g.is_real', 'g.shop_price')
                ->leftjoin('goods as g', 'g.goods_id', '=', 'pg.goods_id')
                ->where('pg.package_id', $package_id)
                ->orderby('pg.package_id', 'desc')
                ->get();

            $goods_res = $goods_res ? $goods_res->toArray() : [];

            $market_price = 0;
            $real_goods_count = 0;
            $virtual_goods_count = 0;

            if ($goods_res) {
                foreach ($goods_res as $key => $val) {
                    $goods_res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                    $goods_res[$key]['market_price_format'] = $this->dscRepository->getPriceFormat($val['market_price']);
                    $goods_res[$key]['rank_price_format'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                    $market_price += $val['market_price'] * $val['goods_number'];
                    /* 统计实体商品和虚拟商品的个数 */
                    if ($val['is_real']) {
                        $real_goods_count++;
                    } else {
                        $virtual_goods_count++;
                    }
                }
            }

            if ($real_goods_count > 0) {
                $package['is_real'] = 1;
            } else {
                $package['is_real'] = 0;
            }

            $package['goods_list'] = $goods_res;
            $package['market_package'] = $market_price;
            $package['market_package_format'] = $this->dscRepository->getPriceFormat($market_price);
            $package['package_price_format'] = $this->dscRepository->getPriceFormat($package['package_price']);
        } else {
            //移除购物车中的无效超值礼包
            if ($package_id) {
                Cart::where([
                    'goods_id' => $package_id,
                    'extension_code' => 'package_buy'
                ])->delete();
            }
        }

        return $package;
    }
}
