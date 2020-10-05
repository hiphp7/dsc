<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Common\AreaService;
use App\Services\OfflineStore\OfflineStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;

class OfflineStoreController extends Controller
{
    /**
     * @var OfflineStoreService
     */
    protected $offlinestoreService;

    /**
     * @var AreaService
     */
    protected $areaService;

    /**
     * OfflineStoreController constructor.
     * @param AreaService $areaService
     * @param OfflineStoreService $offlinestoreService
     */
    public function __construct(
        AreaService $areaService,
        OfflineStoreService $offlinestoreService
    )
    {
        $this->offlinestoreService = $offlinestoreService;
        $this->areaService = $areaService;
    }

    /**
     * 返回线下门店列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'province_id' => 'required|integer',
            'city_id' => 'required|integer',
            'district_id' => 'required|integer',
            'goods_id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer|max:50'
        ]);

        //接收参数
        $province = $request->get('province_id', 0);
        $city = $request->get('city_id', 0);
        $district = $request->get('district_id', 0);
        $goods_id = $request->get('goods_id', 0);
        $spec_arr = $request->get('spec_arr', '');
        $rec_id = $request->input('rec_id', '');  // 1.4.2新增，选中的购物车rec_id

        $num = $request->get('num', 1);
        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        /* 生成仓库地区缓存 */
        $this->areaService->setWarehouseCache($this->uid, $province, $city, $district);

        $list = $this->offlinestoreService->listOfflineStore($province, $city, $district, '', $goods_id, $spec_arr, $page, $size, $num, $rec_id);

        $userMobile = $this->offlinestoreService->getUserMobile($this->uid);

        return $this->succeed(['list' => $list, 'phone' => $userMobile]);
    }
}
