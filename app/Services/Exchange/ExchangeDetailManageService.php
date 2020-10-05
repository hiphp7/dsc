<?php

namespace App\Services\Exchange;

use App\Models\Goods;
use App\Models\MerchantsShopInformation;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 积分管理
 * Class JigonService
 * @package App\Services\Erp
 */
class ExchangeDetailManageService
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 获取商家积分明细列表
     *
     * @param bool $is_pagination
     * @return array
     */
    public function getShopExchangeDetail($is_pagination = true)
    {
        //卖场
        $where = "";
        $adminru = get_admin_ru_id();
        $where .= get_rs_null_where('user_id', $adminru['rs_id']);

        //统计商家数量
        $filter['record_count'] = MerchantsShopInformation::where('merchants_audit', 1)->count();

        /* 分页大小 */
        $filter = page_and_size($filter);
        $res = MerchantsShopInformation::select('user_id')->where('merchants_audit', 1);

        if ($is_pagination) {
            $res = $res->offset($filter['start'])->limit($filter['page_size']);
        }

        $detail = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        foreach ($detail as $key => $row) {
            $row['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);

            $exchange = $this->getSellerGoodsExchange($row['user_id']);

            $row['give_integral'] = isset($exchange['give_integral']) ? $exchange['give_integral'] : '';
            $row['rank_integral'] = isset($exchange['rank_integral']) ? $exchange['rank_integral'] : '';

            $arr[] = $row;
        }

        $arr = ['detail' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获取赠送积分订单列表
     *
     * @return array
     */
    public function giveIntegralOrderList()
    {
        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'order_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['user_id'] = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

        $user_id = $filter['user_id'];

        //主订单下有子订单时，则主订单不显示
        $res = OrderInfo::where('main_count', 0);
        $res = $res->whereHas('getOrderGoods', function ($query) use ($user_id) {
            $query->where('ru_id', $user_id);
        });
        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);
        $arr = [];

        $res = $res->select('order_id', 'order_sn');
        $res = $res->with(['getOrderGoods' => function ($query) use ($user_id) {
            $query->select('order_id', 'goods_id', 'goods_name', 'goods_number', 'extension_code')->where('ru_id', $user_id);
        }]);
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $row) {
            if (isset($row['get_order_goods']) && !empty($row['get_order_goods'])) {
                $row['goods_name'] = $row['get_order_goods']['goods_name'];
                $row['goods_number'] = $row['get_order_goods']['goods_number'];
                $row['goods_id'] = $row['get_order_goods']['goods_id'];
                $row['extension_code'] = $row['get_order_goods']['extension_code'];
            }
            $res = Goods::select('model_price', 'rank_integral', 'give_integral', 'is_promote', 'promote_price', 'shop_price')
                ->where('goods_id', $row['goods_id']);
            $goods = $this->baseRepository->getToArrayFirst($res);

            if ($row['extension_code'] == '' && $goods['model_price'] == 1) {
                $res = WarehouseGoods::select('give_integral', 'rank_integral')->where('goods_id', $row['goods_id']);
                $warehouse_row = $this->baseRepository->getToArrayFirst($res);

                if ($row['extension_code'] != 'package_buy' && !empty($warehouse_row)) {
                    $row['give_integral'] = $warehouse_row['give_integral'] * $row['goods_number'];
                    $row['rank_integral'] = $warehouse_row['rank_integral'] * $row['goods_number'];
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            } elseif ($row['extension_code'] == '' && $goods['model_price'] == 2) {
                $res = WarehouseAreaGoods::select('give_integral', 'rank_integral')->where('goods_id', $row['goods_id']);
                $area_row = $this->baseRepository->getToArrayFirst($res);

                if ($row['extension_code'] != 'package_buy' && !empty($area_row)) {
                    $row['give_integral'] = $area_row['give_integral'] * $row['goods_number'];
                    $row['rank_integral'] = $area_row['rank_integral'] * $row['goods_number'];
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            } else {
                if ($row['extension_code'] != 'package_buy') {
                    if ($goods['give_integral'] == '-1') {
                        $row['give_integral'] = intval($goods['is_promote'] ? $goods['promote_price'] : $goods['shop_price']);
                        $row['give_integral'] = $row['give_integral'] * $row['goods_number'];
                    } else {
                        $row['give_integral'] = $goods['give_integral'] * $row['goods_number'];
                    }

                    if ($goods['rank_integral'] == '-1') {
                        $row['rank_integral'] = intval($goods['is_promote'] ? $goods['promote_price'] : $goods['shop_price']);
                        $row['rank_integral'] = $row['rank_integral'] * $row['goods_number'];
                    } else {
                        $row['rank_integral'] = $goods['rank_integral'] * $row['goods_number'];
                    }
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            }

            $arr[] = $row;
        }

        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*获取商品*/
    public function getExchangeList($user_id, $is_pagination = true)
    {
        //获取商品数量
        $res = OrderGoods::with(['getGoods' => function ($query) use ($user_id) {
            $query->where('give_integral', '<>', 0)->orWhere('rank_integral', '<>', 0);
            $query->with(['getShopInfo' => function ($sp_query) use ($user_id) {
                $sp_query->where('user_id', $user_id)->count();
            }]);
            $query->groupBy('goods_id');
        }]);
        $filter['record_count'] = $res->where('extension_code', '<>', 'package_buy')->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = OrderGoods::selectRaw('goods_id,extension_code, SUM(IFNULL(goods_number, 0)) AS goods_number');
        $res = $res->with(['getGoods' => function ($query) use ($user_id) {
            $query->select('goods_id',
                'goods_name',
                'goods_thumb',
                'give_integral',
                'rank_integral',
                'model_price',
                'is_promote',
                'promote_price',
                'shop_price',
                'user_id'
            );
            $query->where('give_integral', '<>', 0)->orWhere('rank_integral', '<>', 0);
            $query->with(['getShopInfo' => function ($sp_query) use ($user_id) {
                $sp_query->select('user_id')->where('user_id', $user_id);
            }]);
            $query->groupBy('goods_id');
        }]);

        if ($is_pagination) {
            $res = $res->offset($filter['start'])->limit($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);
        $arr = [];
        foreach ($res as $key => $row) {
            $row['goods_name'] = '';
            $row['goods_thumb'] = '';
            $row['give_integral'] = 0;
            $row['rank_integral'] = 0;
            $row['model_price'] = 0;
            $row['is_promote'] = 0;
            $row['promote_price'] = 0;
            $row['shop_price'] = 0;
            $row['user_id'] = 0;
            if ($row['get_goods']) {
                $row['goods_name'] = $row['get_goods']['goods_name'];
                $row['goods_thumb'] = $row['get_goods']['goods_thumb'];
                $row['give_integral'] = $row['get_goods']['give_integral'];
                $row['rank_integral'] = $row['get_goods']['rank_integral'];
                $row['model_price'] = $row['get_goods']['model_price'];
                $row['is_promote'] = $row['get_goods']['is_promote'];
                $row['shop_price'] = $row['get_goods']['shop_price'];
                if ($row['get_goods']['get_shop_info']) {
                    $row['user_id'] = $row['get_goods']['get_shop_info']['user_id'];
                }
            }
            if ($row['model_price'] == 1) {
                $res = WarehouseGoods::select('give_integral', 'rank_integral')->where('goods_id', $row['goods_id']);
                $warehouse_row = $this->baseRepository->getToArrayFirst($res);
                if ($row['extension_code'] != 'package_buy' & !empty($warehouse_row)) {
                    $row['give_integral'] = $warehouse_row['give_integral'] * $row['goods_number'];
                    $row['rank_integral'] = $warehouse_row['rank_integral'] * $row['goods_number'];
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            } elseif ($row['model_price'] == 2) {
                $res = WarehouseAreaGoods::select('give_integral', 'rank_integral')->where('goods_id', $row['goods_id']);
                $area_row = $this->baseRepository->getToArrayFirst($res);

                if ($row['extension_code'] != 'package_buy' && !empty($area_row)) {
                    $row['give_integral'] = $area_row['give_integral'] * $row['goods_number'];
                    $row['rank_integral'] = $area_row['rank_integral'] * $row['goods_number'];
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            } else {
                if ($row['extension_code'] != 'package_buy') {
                    if ($row['give_integral'] == '-1') {
                        $row['give_integral'] = intval($row['is_promote'] ? $row['promote_price'] : $row['shop_price']);
                        $row['give_integral'] = $row['give_integral'] * $row['goods_number'];
                    } else {
                        $row['give_integral'] = $row['give_integral'] * $row['goods_number'];
                    }

                    if ($row['rank_integral'] == '-1') {
                        $row['rank_integral'] = intval($row['is_promote'] ? $row['promote_price'] : $row['shop_price']);
                        $row['rank_integral'] = $row['rank_integral'] * $row['goods_number'];
                    } else {
                        $row['rank_integral'] = $row['rank_integral'] * $row['goods_number'];
                    }
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            }

            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);

            $arr[] = $row;
        }

        $arr = ['goods' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    /**
     * 获取商家商品
     *
     * @param int $user_id
     * @return array
     */
    public function getSellerGoodsExchange($user_id = 0)
    {
        $res = OrderGoods::select('goods_id', 'goods_number');
        $res = $res->where('extension_code', '<>', 'package_buy');
        $res = $res->with(['getGoods' => function ($query) use ($user_id) {
            $query->select('goods_id',
                'goods_name',
                'goods_thumb',
                'give_integral',
                'rank_integral',
                'model_price',
                'is_promote',
                'promote_price',
                'shop_price'
            );
            $query->where('user_id', $user_id);
        }]);
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [
            'give_integral' => 0,
            'rank_integral' => 0
        ];
        foreach ($res as $key => $row) {
            $row['goods_name'] = '';
            $row['goods_thumb'] = '';
            $row['give_integral'] = 0;
            $row['rank_integral'] = 0;
            $row['model_price'] = 0;
            $row['is_promote'] = 0;
            $row['promote_price'] = 0;
            $row['shop_price'] = 0;
            if ($row['get_goods']) {
                $row['goods_name'] = $row['get_goods']['goods_name'];
                $row['goods_thumb'] = $row['get_goods']['goods_thumb'];
                $row['give_integral'] = $row['get_goods']['give_integral'];
                $row['rank_integral'] = $row['get_goods']['rank_integral'];
                $row['model_price'] = $row['get_goods']['model_price'];
                $row['is_promote'] = $row['get_goods']['is_promote'];
                $row['shop_price'] = $row['get_goods']['shop_price'];
            }

            if ($row['model_price'] == 1) {
                $res = WarehouseGoods::selectRaw('SUM(give_integral) AS give_integral, SUM(rank_integral) AS rank_integral');
                $res = $res->where('goods_id', $row['goods_id']);
                $warehouse_row = $this->baseRepository->getToArrayFirst($res);
                if (!empty($warehouse_row)) {
                    $row['give_integral'] = $warehouse_row['give_integral'];
                    $row['rank_integral'] = $warehouse_row['rank_integral'];
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            } elseif ($row['model_price'] == 2) {
                $res = WarehouseAreaGoods::selectRaw('SUM(give_integral) AS give_integral, SUM(rank_integral) AS rank_integral');
                $res = $res->where('goods_id', $row['goods_id']);
                $area_row = $this->baseRepository->getToArrayFirst($res);
                if (!empty($area_row)) {
                    $row['give_integral'] = $area_row['give_integral'];
                    $row['rank_integral'] = $area_row['rank_integral'];
                } else {//先不处理礼包商品
                    $row['give_integral'] = 0;
                    $row['rank_integral'] = 0;
                }
            } else {
                if ($row['give_integral'] == '-1') {
                    $row['give_integral'] = intval($row['is_promote'] ? $row['promote_price'] : $row['shop_price']);
                }

                if ($row['rank_integral'] == '-1') {
                    $row['rank_integral'] = intval($row['is_promote'] ? $row['promote_price'] : $row['shop_price']);
                }
            }

            $give_integral = $row['give_integral'] * $row['goods_number'];
            $rank_integral = $row['rank_integral'] * $row['goods_number'];

            $arr['give_integral'] += $give_integral;
            $arr['rank_integral'] += $rank_integral;
        }

        return $arr;
    }

}
