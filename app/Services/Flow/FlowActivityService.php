<?php

namespace App\Services\Flow;

use App\Models\BonusType;
use App\Models\Cart;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Cart\CartGoodsService;

class FlowActivityService
{
    protected $dscRepository;
    protected $sessionRepository;
    protected $timeRepository;

    public function __construct(
        DscRepository $dscRepository,
        SessionRepository $sessionRepository,
        TimeRepository $timeRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获得用户的可用积分
     *
     * @param $cart_value
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return float|int
     * @throws \Exception
     */
    public function getFlowAvailablePoints($cart_value = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $where = [
            'is_gift' => 0,
            'rec_id' => $cart_value,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        $res = app(CartGoodsService::class)->getGoodsCartList($where);

        $val = 0;
        if ($res) {
            foreach ($res as $key => $row) {
                $val += $row['integral_total'];
            }
        }

        $val = $this->dscRepository->getIntegralOfValue($val);

        return $val;
    }

    /**
     * 取得当前用户应该得到的红包总额
     *
     * @param int $user_id
     * @return float
     */
    public function getTotalBonus($user_id = 0)
    {
        $session_id = '';
        if (empty($user_id)) {
            $user_id = session('user_id', 0);
            $session_id = $this->sessionRepository->realCartMacIp();
        }

        $day = $this->timeRepository->getLocalGetDate();
        $today = $this->timeRepository->getLocalMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        /* 按商品发的红包 */
        $goods_total = Cart::where('is_gift', 0)->where('rec_type', CART_GENERAL_GOODS);

        $where = [
            'send_type' => SEND_BY_GOODS,
            'today' => $today
        ];
        $goods_total = $goods_total->whereHas('getGoods', function ($query) use ($where) {
            $query->whereHas('getBonusType', function ($query) use ($where) {
                $query->where('send_type', $where['send_type'])
                    ->where('send_start_date', '<=', $where['today'])
                    ->where('send_end_date', '>=', $where['today']);
            });
        });

        if (!empty($user_id)) {
            $goods_total = $goods_total->where('user_id', $user_id);
        } else {
            $goods_total = $goods_total->where('session_id', $session_id);
        }

        $goods_total = $goods_total->with([
            'getGoods' => function ($query) {
                $query->bonusTypeInfo();
            }
        ]);

        $goods_total = $goods_total->get();

        $goods_total = $goods_total ? $goods_total->toArray() : [];

        $total = 0;
        if ($goods_total) {
            foreach ($goods_total as $key => $row) {
                $get_bonus_type = isset($goods_total['get_goods']['get_bonus_type']) && $goods_total['get_goods']['get_bonus_type'] ? $goods_total['get_goods']['get_bonus_type'] : [];
                $row['type_money'] = $get_bonus_type ? $get_bonus_type['type_money'] : 0;

                $total += $row['goods_number'] + $row['type_money'];
            }
        }

        $goods_total = floatval($total);

        /* 取得购物车中非赠品总金额 */
        $amount = Cart::selectRaw("SUM(goods_price * goods_number) as total")
            ->where('is_gift', 0)
            ->where('rec_type', CART_GENERAL_GOODS);

        if (!empty($user_id)) {
            $amount = $amount->where('user_id', $user_id);
        } else {
            $amount = $amount->where('session_id', $session_id);
        }

        $amount = $amount->first();
        $amount = $amount ? $amount->toArray() : [];
        $amount = $amount ? $amount['total'] : 0;
        $amount = floatval($amount);

        /* 按订单发的红包 */
        $order_total = BonusType::selectRaw("FLOOR('$amount' / min_amount) * type_money")
            ->where('send_type', SEND_BY_ORDER)
            ->where('send_start_date', '<=', $today)
            ->where('send_end_date', '>=', $today)
            ->where('min_amount', '>', 0);

        $order_total = $order_total->first();
        $order_total = $order_total ? $order_total->toArray() : [];
        $order_total = isset($order_total['type_money']) ? $order_total['type_money'] : 0;
        $order_total = floatval($order_total);

        return $goods_total + $order_total;
    }
}