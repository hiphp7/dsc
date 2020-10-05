<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Region;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\GroupBuyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GroupBuyController extends Controller
{
    protected $groupBuyService;
    protected $dscRepository;
    protected $commonService;

    public function __construct(
        GroupBuyService $groupBuyService,
        DscRepository $dscRepository
    )
    {
        $this->groupBuyService = $groupBuyService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 团购列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'sort' => 'required|string',
            'order' => 'required|string',
        ]);

        $keywords = $request->input('keywords', '');
        $order = $request->input('order');
        $page = $request->input('page');
        $size = $request->input('size');
        $sort = $request->input('sort');

        $list = $this->groupBuyService->getGroupBuyList('', $keywords, '', '', $size, $page, $sort, $order);

        foreach ($list as $key => $val) {
            $list[$key]['get_goods']['goods_img'] = $this->dscRepository->getImagePath($val['get_goods']['goods_img']);
            $list[$key]['get_goods']['goods_thumb'] = $this->dscRepository->getImagePath($val['get_goods']['goods_thumb']);
            $list[$key]['activity_thumb'] = $list[$key]['get_goods']['goods_thumb'];
        }

        return $this->succeed($list);
    }

    /**
     * 团购商品详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'group_buy_id' => 'required|integer',
        ]);

        $group_buy_id = $request->input('group_buy_id', 0);

        $where = [
            'group_buy_id' => $group_buy_id,
            'user_id' => $this->uid,
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
        ];
        $list = $this->groupBuyService->getGroupBuyInfo($where);

        if ($list) {
            $list['get_goods']['goods_img'] = $list['goods']['goods_img'];
            $list['get_goods']['goods_thumb'] = $list['goods']['goods_thumb'];

            // 只有PC详情
            if (empty($list['goods']['desc_mobile']) && !empty($list['goods']['goods_desc'])) {
                $desc_preg = $this->dscRepository->descImagesPreg($list['goods']['goods_desc']);
                $list['goods']['goods_desc'] = $desc_preg['goods_desc'];
            }
            // 手机端详情
            if (!empty($list['goods']['desc_mobile'])) {
                $desc_preg = $this->dscRepository->descImagesPreg($list['goods']['desc_mobile'], 'desc_mobile', 1);
                $list['goods']['goods_desc'] = $desc_preg['desc_mobile'];
            }

            $list['is_collect'] = $list['goods']['is_collect'];

            $sellerInfo = isset($list['goods']['seller_shopinfo']) && $list['goods']['seller_shopinfo'] ? $list['goods']['seller_shopinfo'] : [];

            $list['basic_info'] = $sellerInfo;
            if ($sellerInfo) {
                $province = Region::where('region_id', $sellerInfo['province'])->value('region_name');
                $province = $province ? $province : '';

                $city = Region::where('region_id', $sellerInfo['city'])->value('region_name');
                $city = $city ? $city : '';

                $list['basic_info']['province_name'] = $province;
                $list['basic_info']['city_name'] = $city;
            }

            //判断是否支持退货服务
            $is_return_service = 0;
            if (isset($list['goods']['goods_cause']) && $list['goods']['goods_cause']) {
                $goods_cause = explode(',', $list['goods']['goods_cause']);

                $fruit1 = [1, 2, 3]; //退货，换货，仅退款
                $intersection = array_intersect($fruit1, $goods_cause); //判断商品是否设置退货相关
                if (!empty($intersection)) {
                    $is_return_service = 1;
                }
            }
            //判断是否设置包退服务  如果设置了退换货标识，没有设置包退服务  那么修正包退服务为已选择
            if ($is_return_service == 1 && isset($list['goods']['goods_extend']['is_return']) && !$list['goods']['goods_extend']['is_return']) {
                $list['goods']['goods_extend']['is_return'] = 1;
            }
        }

        return $this->succeed($list);
    }

    /**
     * 立即团
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function buy(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'group_buy_id' => 'required|integer',
            'number' => 'required|integer',
        ]);

        if (empty($this->uid)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        /* 查询：取得参数：团购活动id */
        $groupbuyid = $request->input('group_buy_id', 0);
        if ($groupbuyid <= 0) {
            return $this->setStatusCode(400)->failed("fail");
        }

        /* 查询：取得数量 */
        $number = $request->input('number', 1);
        $specs = $request->input('spec', '');

        $data = $this->groupBuyService->getGroupBuy($this->uid, $groupbuyid, $number, $specs, $this->warehouse_id, $this->area_id, $this->area_city);

        return $this->succeed($data);
    }
}
