<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Activity\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class ActivityController
 * @package App\Api\Fourth\Controllers
 */
class ActivityController extends Controller
{
    /**
     * @var DiscountService
     */
    protected $discountService;

    /**
     * ActivityController constructor.
     * @param DiscountService $discountService
     */
    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * 优惠活动 - 活动列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //验证参数
        $this->validate($request, []);

        $list = $this->discountService->activityList();

        return $this->succeed($list);
    }

    /**
     * 优惠活动 - 活动详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'act_id' => 'required|integer'
        ]);

        //优惠活动信息
        $info = $this->discountService->activityDetail($request->get('act_id'));

        return $this->succeed($info);
    }

    /**
     * 优惠活动 - 活动商品
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function goods(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer|max:50',
            'act_id' => 'required|integer',
            'sort' => 'required|integer',  // 排序 0综合， 1价格，2销量
            'order' => 'required|string'    // 排序方式 desc acs
        ]);

        $user_id = $this->authorization(); // 会员id

        //优惠活动信息
        $row = $this->discountService->activityDetail($request->get('act_id'));

        $filter = [];

        if ($row) {
            //优惠范围类型、内容
            if ($row['actRange'] != FAR_ALL && !empty($row['act_range_ext'])) {
                if ($row['actRange'] == FAR_CATEGORY) {
                    $cat_str = '';
                    $cat_rows = explode(',', $row['act_range_ext']);

                    if ($cat_rows) {
                        foreach ($cat_rows as $v) {
                            $cat_children = array_unique(array_merge([$v], $this->discountService->arr_foreach($this->discountService->catList($v))));
                            if ($cat_children) {
                                $cat_str .= implode(',', $cat_children) . ',';
                            }
                        }
                    }
                    if ($cat_str) {
                        $cat_str = substr($cat_str, 0, -1);
                    }
                    $filter['cat_ids'] = $cat_str;
                } elseif ($row['actRange'] == FAR_BRAND) {
                    $filter['brand_ids'] = $row['act_range_ext'];
                } else {
                    $filter['goods_ids'] = $row['act_range_ext'];
                }
            }

            // 自主或全场使用
            if (isset($row['userFav_type']) && $row['userFav_type'] == 0) {
                $filter['user_id'] = $row['user_id'];
            }
        }

        $list = $this->discountService->activityGoods($filter, $request->get('sort'), $request->get('order'), $request->get('page'), $request->get('size'), $user_id);

        return $this->succeed($list);
    }

    /**
     * 优惠活动 - 活动商品凑单
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function coudan(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'act_id' => 'required|integer'
        ]);

        $act_id = $request->get('act_id');

        $user_id = $this->authorization(); // 会员id

        // 凑单活动类型 满减-满换-打折
        $result['activity_type'] = $this->discountService->getActivityType($user_id, $act_id);

        // 查询活动中 已加入购物车的商品
        $cart_fav_goods = $this->discountService->cartFavourableGoods($user_id, $act_id);

        $cart_fav_num = 0;
        $cart_fav_total = 0;
        if ($cart_fav_goods) {
            foreach ($cart_fav_goods as $key => $row) {
                $cart_fav_num += $row['goods_number'];
                $cart_fav_total += $row['shop_price'] * $row['goods_number'];
            }
        }
        $result['num'] = $cart_fav_num;
        $result['total'] = $cart_fav_total;
        $result['act_id'] = $act_id;

        return $this->succeed($result);
    }
}
