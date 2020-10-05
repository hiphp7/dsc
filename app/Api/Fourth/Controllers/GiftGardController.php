<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Region;
use App\Models\UserGiftGard;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\GiftGardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftGardController extends Controller
{
    protected $dscRepository;
    protected $giftGardService;
    protected $timeRepository;

    public function __construct(
        DscRepository $dscRepository,
        GiftGardService $giftGardService,
        TimeRepository $timeRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->giftGardService = $giftGardService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 验证是否存在礼品卡
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request)
    {
        // 返回用户ID
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $cache_id = 'gift_gard' . '_' . $user_id;
        $gift_sn = cache($cache_id);

        if (!is_null($gift_sn)) {
            $result = [
                'error' => 0,
                'msg' => lang('gift_gard.gift_login_success')
            ];
        } else {
            $result = [
                'error' => 1,
                'msg' => lang('gift_gard.not_gift_gard_null')
            ];
        }

        return $this->succeed($result);
    }

    /**
     * 礼品卡查询
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function checkGift(Request $request)
    {
        $gift_card = $request->input('gift_card', '');
        $gift_pwd = $request->input('gift_pwd', '');
        // 返回用户ID
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = $this->giftGardService->getWepCheckGiftLogin($gift_card, $gift_pwd, $user_id);

        return $this->succeed($result);
    }

    /**
     * 礼品卡兑换列表
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function giftList(Request $request)
    {
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        // 缓存
        $cache_id = 'gift_gard' . '_' . $this->uid;
        $gift_sn = cache($cache_id);
        if (is_null($gift_sn)) {
            $result = [
                'error' => 1, // 礼品卡登录已过期
                'msg' => lang('gift_gard.gift_logout_overdue')
            ];
            return $this->succeed($result);
        }

        $goodslist = $this->giftGardService->giftGetGoods($this->warehouse_id, $this->area_id, $this->area_city, $size, $page, $this->uid);
        $result['goods'] = $goodslist;
        $result['gif'] = $gift_sn;

        return $this->succeed($result);
    }

    /**
     * 退出礼品卡
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function exitGift(Request $request)
    {
        // 返回用户ID
        $user_id = $this->authorization();

        $cache_id = 'gift_gard' . '_' . $user_id;
        cache()->forget($cache_id);
        $result = [
            'error' => 0,
            'msg' => lang('gift_gard.gift_logout_success')
        ];
        return $this->succeed($result);
    }

    /**
     * 提货
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function checkTake(Request $request)
    {
        $goods_id = $request->input('goods_id', 0);
        $country = $request->input('country', 0);
        $province = $request->input('province', 0);
        $city = $request->input('city', 0);
        $district = $request->input('district', 0);
        $street = $request->input('street', 0);
        $desc_address = $request->input('address', '');
        $consignee = $request->input('consignee', '');
        $mobile = $request->input('mobile', '');
        $shipping_time = $request->input('shipping_time', '');

        $user_id = $this->authorization();

        // 缓存
        $cache_id = 'gift_gard' . '_' . $user_id;
        $gift_sn = cache($cache_id);

        if ($gift_sn) {
            $pwd = UserGiftGard::where('gift_sn', $gift_sn)->where('is_delete', 1)->first();
            $pwd = $pwd ? $pwd->toArray() : [];

            $gift = $this->giftGardService->getWepCheckGiftLogin($gift_sn, $pwd['gift_password'], $user_id);

            if ($pwd && $gift['error'] != 0) {
                // 清除缓存
                $cache_id = 'gift_gard' . '_' . $user_id;
                cache()->forget($cache_id);

                $result = [
                    'error' => 1, // 礼品卡已使用
                    'msg' => lang('gift_gard.gift_gard_used')
                ];
                return $this->succeed($result);
            }
        } else {
            // 清除缓存
            $cache_id = 'gift_gard' . '_' . $user_id;
            cache()->forget($cache_id);

            $result = [
                'error' => 1, // 礼品卡已过期
                'msg' => lang('gift_gard.gift_gard_overdue')
            ];
            return $this->succeed($result);
        }

        $user_time = $this->timeRepository->getGmTime();

        $country_name = Region::where('region_id', $country)->value('region_name');
        $province_name = Region::where('region_id', $province)->value('region_name');
        $city_name = Region::where('region_id', $city)->value('region_name');
        $district_name = Region::where('region_id', $district)->value('region_name');
        $street_name = Region::where('region_id', $street)->value('region_name');

        $address = "[" . $country_name . ' ' . $province_name . ' ' . $city_name . ' ' . $district_name . ' ' . ' ' . $street_name . '] ' . $desc_address;
        if (empty($country_name) || empty($province_name) || empty($city_name) || empty($district_name) || empty($desc_address) || empty($consignee) || empty($mobile)) {
            $result = [
                'error' => 1, //请填写完整的收获信息
                'msg' => lang('gift_gard.delivery_Prompt')
            ];
            return $this->succeed($result);
        }
        $gardOther = [
            'user_id' => $user_id,
            'goods_id' => $goods_id,
            'user_time' => $user_time,
            'address' => $address,
            'consignee_name' => $consignee,
            'mobile' => $mobile,
            'shipping_time' => $shipping_time,
            'status' => 1,
        ];

        $res = UserGiftGard::where('gift_sn', $gift_sn)->update($gardOther);

        if ($res) {
            // 清除缓存
            $cache_id = 'gift_gard' . '_' . $user_id;
            cache()->forget($cache_id);
            $result = [
                'error' => 0,  // 提货成功
                'msg' => lang('gift_gard.delivery_Success')
            ];
        } else {
            $result = [
                'error' => 1, // 提货失败
                'msg' => lang('gift_gard.delivery_fail')
            ];
        }


        return $this->succeed($result);
    }

    /**
     * 我的提货
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function takeList(Request $request)
    {
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        // 返回用户ID
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $list = $this->giftGardService->getTakeList($page, $size, $user_id);

        return $this->succeed($list);
    }

    /**
     * 确认收货
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function confimGoods(Request $request)
    {
        $take_id = $request->input('take_id', 0);
        $up_id = UserGiftGard::where('gift_gard_id', $take_id)->update(['status' => 3]);
        if ($up_id) {
            $result = [
                'error' => 0, // 收货成功
                'msg' => lang('gift_gard.receipt_Success')
            ];
        } else {
            $result = [
                'error' => 1, // 收货失败
                'msg' => lang('gift_gard.receipt_fail')
            ];
        }

        return $this->succeed($result);
    }
}
