<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Activity\SeckillService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class SeckillController
 * @package App\Api\Fourth\Controllers
 */
class SeckillController extends Controller
{
    protected $seckillService;

    /**
     * SeckillController constructor.
     * @param SeckillService $seckillService
     */
    public function __construct(
        SeckillService $seckillService
    )
    {
        $this->seckillService = $seckillService;
    }

    /**
     * 秒杀商品列表
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer',
            'tomorrow' => 'required|integer',
        ]);

        //接收参数
        $id = $request->get('id', 0);
        $page = $request->get('page', 1);
        $size = $request->get('size', 5);
        $tomorrow = $request->get('tomorrow', 0);

        $goodsList = $this->seckillService->seckill_goods_results($id, $page, $size, $tomorrow);

        return $this->succeed($goodsList);
    }

    /**
     * 返回时间列表
     * @param Request $request
     * @return mixed
     */
    public function time(Request $request)
    {
        // 时间列表
        $list['list'] = $this->seckillService->getSeckillTime();

        // 秒杀广告位
        $list['banner_ads'] = $this->seckillService->seckill_ads('seckill', 6);

        return $this->succeed($list);
    }

    /**
     * 返回秒杀详情
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'seckill_id' => 'required|integer'
        ]);
        $seckill_id = $request->input('seckill_id', 0);
        $tomorrow = $request->input('tomorrow', 0);
        $tomorrow = intval($tomorrow);

        $uid = $this->authorization();

        $data = $this->seckillService->seckill_detail($uid, $seckill_id, $tomorrow);

        return $this->succeed($data);
    }

    /**
     * 秒杀商品购买
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function buy(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'sec_goods_id' => 'required|integer',
            'number' => 'required|integer',
            'goods_spec' => 'string',
        ]);

        if (empty($this->uid)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $seckill_id = $request->input('sec_goods_id', 0);
        $number = $request->input('number', 1);

        /* 取得规格 */
        $specs = $request->input('goods_spec', '');

        $data = $this->seckillService->getSeckillBuy($this->uid, $seckill_id, $number, $specs, $this->warehouse_id, $this->area_id, $this->area_city);

        return $this->succeed($data);
    }
}
