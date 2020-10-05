<?php

namespace App\Services\Flow;


use App\Models\Cart;
use App\Models\OrderGoods;
use App\Models\Region;
use App\Repositories\Common\SessionRepository;
use App\Services\User\UserAddressService;

class FlowUserService
{
    protected $userAddressService;
    protected $sessionRepository;

    public function __construct(
        UserAddressService $userAddressService,
        SessionRepository $sessionRepository
    )
    {
        $this->userAddressService = $userAddressService;
        $this->sessionRepository = $sessionRepository;
    }

    /**
     * 取得收货人信息
     * @param int $user_id 用户编号
     * @return  array
     */
    public function getConsignee($user_id)
    {
        if (empty($user_id)) {
            $user_id = session('user_id', 0);
        }

        if (session()->has('flow_consignee') && $user_id <= 0) {
            /* 如果存在session，则直接返回session中的收货人信息 */

            if (!(session('flow_consignee.user_id') == $user_id)) {
                session([
                    'flow_consignee' => ''
                ]);
            }

            return session('flow_consignee');
        } else {
            /* 如果不存在，则取得用户的默认收货人信息 */
            $arr = [];

            /* 取默认地址 */
            if ($user_id > 0) {
                $arr = $this->userAddressService->getUserAddressInfo(0, $user_id);
            }

            return $arr;
        }
    }

    /**
     * 检查收货人信息是否完整
     *
     * @param array $consignee
     * @param int $flow_type
     * @param int $user_id
     * @return bool
     * @throws \Exception
     */
    public function checkConsigneeInfo($consignee = [], $flow_type = 0, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = session('user_id', 0);
        }

        cache();

        if ($this->existRealGoods(0, $flow_type, '', $user_id)) {
            /* 如果存在实体商品 */
            $res = (isset($consignee['consignee']) && !empty($consignee['consignee'])) &&
                //!empty($consignee['country']) &&
                ((isset($consignee['tel']) && !empty($consignee['tel'])) || (isset($consignee['mobile']) && !empty($consignee['mobile'])));

            if ($res) {
                if (isset($consignee['province']) && empty($consignee['province'])) {
                    /* 没有设置省份，检查当前国家下面有没有设置省份 */
                    $pro = Region::where('region_type', 1)->where('parent_id', $consignee['country'])->get();
                    $pro = $pro ? $pro->toArray() : [];

                    $res = empty($pro);
                } elseif (isset($consignee['city']) && empty($consignee['city'])) {
                    /* 没有设置城市，检查当前省下面有没有城市 */
                    $city = Region::where('region_type', 2)->where('parent_id', $consignee['province'])->get();
                    $city = $city ? $city->toArray() : [];

                    $res = empty($city);
                } elseif (isset($consignee['district']) && empty($consignee['district'])) {
                    $dist = Region::where('region_type', 3)->where('parent_id', $consignee['district'])->get();
                    $dist = $dist ? $dist->toArray() : [];

                    $res = empty($dist);
                }
            }

            return $res;
        } else {
            /* 如果不存在实体商品 */
            return (isset($consignee['consignee']) && !empty($consignee['consignee'])) &&
                //!empty($consignee['email']) && //by wu
                ((isset($consignee['tel']) && !empty($consignee['tel'])) || (isset($consignee['mobile']) && !empty($consignee['mobile'])));
        }
    }

    /**
     * 查询购物车（订单id为0）或订单中是否有实体商品
     *
     * @param int $order_id
     * @param int $flow_type 购物流程类型
     * @param string $cart_value
     * @param int $user_id
     * @return bool
     */
    public function existRealGoods($order_id = 0, $flow_type = CART_GENERAL_GOODS, $cart_value = '', $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = session('user_id', 0);
        }

        if ($order_id <= 0) {
            $res = Cart::where('is_real', 1)->where('rec_type', $flow_type);

            if ($cart_value) {
                $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;

                $res = $res->whereIn('rec_id', $cart_value);
            }

            if ($user_id) {
                $res = $res->where('user_id', $user_id);
            } else {
                $session_id = $this->sessionRepository->realCartMacIp();

                $res = $res->where('session_id', $session_id);
            }
        } else {
            $res = OrderGoods::where('order_id', $order_id)->where('is_real', 1);
        }

        $count = $res->count();

        return $count > 0;
    }
}