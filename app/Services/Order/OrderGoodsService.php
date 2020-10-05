<?php

namespace App\Services\Order;

use App\Models\GoodsAttr;
use App\Models\OrderGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class OrderGoodsService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获取订单商品列表
     *
     * @access  public
     * @param  $where
     * @return  array
     */
    public function getOrderGoodsList($where = [])
    {
        $res = OrderGoods::selectRaw("*, goods_number AS num")
            ->whereRaw(1);

        if (isset($where['order_id']) && !empty($where['order_id'])) {
            $where['order_id'] = !is_array($where['order_id']) ? explode(",", $where['order_id']) : $where['order_id'];

            $res = $res->whereIn('order_id', $where['order_id']);
        }

        if (isset($where['is_real'])) {
            $res = $res->where('is_real', $where['is_real']);
        }

        if (isset($where['extension_code'])) {
            $res = $res->where('extension_code', $where['extension_code']);
        }

        if (isset($where['sort']) && isset($where['order'])) {
            $res = $res->orderBy($where['sort'], $where['order']);
        }

        if (isset($where['size'])) {
            if (isset($where['page'])) {
                $start = ($where['page'] - 1) * $where['size'];

                if ($start > 0) {
                    $res = $res->skip($start);
                }
            }

            if ($where['size'] > 0) {
                $res = $res->take($where['size']);
            }
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     * 查询限购商品已购买数量
     *
     * @param int $start_date
     * @param int $end_date
     * @param int $goods_id
     * @param int $user_id
     * @param string $extension_code
     * @param string $attr_id
     * @param int $group_by_id
     * @return array
     */
    public function getForPurchasingGoods($start_date = 0, $end_date = 0, $goods_id = 0, $user_id = 0, $extension_code = '', $attr_id = '', $group_by_id = 0)
    {
        $param = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user_id' => $user_id,
            'extension_code' => $extension_code,
            'extension_id' => $group_by_id
        ];

        $res = OrderGoods::selectRaw('SUM(goods_number) AS goods_number')->where('goods_id', $goods_id);

        if ($attr_id) {
            $res = $res->where('goods_attr_id', $attr_id);
        }

        $res = $res->whereHas('getOrder', function ($query) use ($param) {
            $query = $query->where('main_count', 0)->where('order_status', '<>', OS_CANCELED);

            if ($param['extension_id']) {
                $query = $query->where('extension_id', $param['extension_id']);
            }

            if ($param['extension_code']) {
                $query = $query->where('extension_code', $param['extension_code']);
            }

            if ($param['extension_code'] != 'group_buy') {
                $query = $query->where('user_id', $param['user_id']);
            }

            if ($param['start_date'] && $param['end_date']) {
                $query->where('add_time', '>', $param['start_date'])
                    ->where('add_time', '<', $param['end_date']);
            }
        });

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        $goods_number = $res && $res['goods_number'] ? $res['goods_number'] : 0;

        return [
            'goods_number' => $goods_number
        ];
    }

    /**
     * 购买商品的属性
     *
     * @param $goods_id
     * @param $user_id
     * @param $order_id
     * @return mixed
     */
    public function getUserBuyGoodsOrder($goods_id, $user_id, $order_id)
    {
        $orderWhere = [
            'user_id' => $user_id,
            'order_id' => $order_id,
        ];
        $buy_goods = OrderGoods::select('order_id', 'goods_attr_id')->where('goods_id', $goods_id)
            ->whereHas('getOrder', function ($query) use ($orderWhere) {
                $query->where('user_id', $orderWhere['user_id'])
                    ->where('order_id', $orderWhere['order_id']);
            });

        $buy_goods = $buy_goods->with([
            'getOrder' => function ($query) {
                $query->select('order_id', 'add_time');
            }
        ]);

        $buy_goods = $this->baseRepository->getToArrayFirst($buy_goods);

        $buy_goods = isset($buy_goods['get_oder']) ? $this->baseRepository->getArrayMerge($buy_goods, $buy_goods['get_oder']) : $buy_goods;

        $buy_goods['goods_attr'] = isset($buy_goods['goods_attr_id']) ? $this->getGoodsAttrOrder($buy_goods['goods_attr_id']) : '';
        $buy_goods['add_time'] = !empty($buy_goods['add_time']) ? $this->timeRepository->getLocalDate($this->config['time_format'], $buy_goods['add_time']) : '';

        return $buy_goods;
    }

    /**
     * 查询属性名称
     *
     * @param $goods_attr_id
     * @return mixed|string
     */
    public function getGoodsAttrOrder($goods_attr_id)
    {
        $attr = '';
        if ($goods_attr_id) {
            if (!empty($goods_attr_id)) {
                $fmt = "%s：%s <br/>";

                $goods_attr_id = $this->baseRepository->getExplode($goods_attr_id);

                $res = GoodsAttr::select('goods_attr_id', 'attr_id', 'attr_value')
                    ->whereIn('goods_attr_id', $goods_attr_id);

                $res = $res->with([
                    'getGoodsAttribute' => function ($query) {
                        $query->select('attr_id', 'attr_name', 'sort_order');
                    }
                ]);

                $res = $this->baseRepository->getToArrayGet($res);

                if ($res) {
                    foreach ($res as $key => $row) {
                        $row = $row['get_goods_attribute'] ? array_merge($row, $row['get_goods_attribute']) : $row;

                        $res[$key] = $row;
                    }

                    $res = $this->baseRepository->getSortBy($res, 'sort_order');

                    if ($res) {
                        foreach ($res as $row) {
                            $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], '');
                        }

                        $attr = str_replace('[0]', '', $attr);
                    }
                }
            }
        }

        return $attr;
    }
}