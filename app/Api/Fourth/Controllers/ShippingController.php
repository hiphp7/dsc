<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\DscRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Common\AreaService;
use App\Services\Flow\FlowUserService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Shipping\ShippingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class ShippingController
 * @package App\Api\Fourth\Controllers
 */
class ShippingController extends Controller
{
    protected $areaService;
    protected $shippingService;
    protected $dscRepository;
    protected $flowUserService;
    protected $cartCommonService;

    public function __construct(
        ShippingService $shippingService,
        AreaService $areaService,
        DscRepository $dscRepository,
        FlowUserService $flowUserService,
        CartCommonService $cartCommonService
    )
    {
        //加载配置文件
        $this->shippingService = $shippingService;
        $this->areaService = $areaService;
        $this->dscRepository = $dscRepository;
        $this->flowUserService = $flowUserService;
        $this->cartCommonService = $cartCommonService;
    }

    protected function initialize()
    {
        parent::initialize();

        //加载外部类
        $files = [
            'common',
            'time',
            'order',
            'function',
            'ecmoban',
            'goods',
            'base',
        ];

        load_helper($files);

        //加载语言包
        $this->dscRepository->helpersLang('user');
    }

    /**
     * 配送列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $user_id = $this->authorization();
        $rec_ids = $request->get('rec_ids', '');
        $ru_id = $request->get('ru_id', 0);
        $consignee = $request->get('consignee', '');
        $consignee = dsc_decode($consignee, true);
        $whereCart = [];
        $whereCart['flow_type'] = $request->get('flow_type', 100);
        $whereCart['flow_consignee'] = $consignee;

        $ru_shipping = $this->shippingService->getRuShippngInfo($rec_ids, $user_id, $ru_id, $consignee, $whereCart);

        $arr['shipping'] = $ru_shipping['shipping_list'];
        $arr['is_freight'] = $ru_shipping['is_freight'];
        $arr['shipping_rec'] = $ru_shipping['shipping_rec'];

        $arr['shipping_count'] = !empty($arr['shipping']) ? count($arr['shipping']) : 0;
        if (!empty($arr['shipping'])) {
            $arr['tmp_shipping_id'] = isset($arr['shipping'][0]['shipping_id']) ? $arr['shipping'][0]['shipping_id'] : 0; //默认选中第一个配送方式
            foreach ($arr['shipping'] as $kk => $vv) {
                if (isset($vv['default']) && $vv['default'] == 1) {
                    $arr['tmp_shipping_id'] = $vv['shipping_id'];
                    continue;
                }
            }
        }

        return $this->succeed($arr);
    }

    /**
     * 配送价格
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function amount(Request $request)
    {
        $result = ['error' => 0, 'massage' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1];

        $user_id = $this->authorization();
        $ru_id = $request->get('ru_id', 0);
        $shipping_type = $request->get('type', 0);
        $tmp_shipping_id = $request->get('shipping_id', []);
        $shipping = $request->get('select_shipping', '');
        $rec_ids = $request->get('rec_ids', '');
        $order['shipping_type'] = $request->get('shipping_type', 0);
        $order['shipping_code'] = $request->get('shipping_code', '');
        $store_id = $request->get('store_id', 0);

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        /* 取得购物类型 */
        $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

        /* 获得收货人信息 */
        $consignee = $this->flowUserService->getConsignee($user_id);

        /* 对商品信息赋值 */
        $cart_goods_list = cart_goods($flow_type, $rec_ids, 1, $this->warehouse_id, $this->area_id, $this->area_city, $consignee, $store_id, $user_id);
        if (empty($cart_goods_list) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type, $user_id)) {
            if (empty($cart_goods_list)) {
                $result['error'] = 1;
            } elseif (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                $result['error'] = 2;
            }
        } else {

            /* 取得订单信息 */
            $order = flow_order_info($user_id);
            /* 保存 session */
            $order['shipping_id'] = $tmp_shipping_id;

            if ($shipping_type == 1) {
                if (session('shipping_type_ru_id') && is_array(session('shipping_type_ru_id'))) {
                    session()->put('shipping_type_ru_id.' . $ru_id, $ru_id);
                }
            } else {
                if (session()->has('shipping_type_ru_id.' . $ru_id)) {
                    session()->forget('shipping_type_ru_id.' . $ru_id, $ru_id);
                }
            }

            if ($tmp_shipping_id) {
                $tmp_shipping_id_arr = $tmp_shipping_id;
            } else {
                $tmp_shipping_id_arr = [];
            }

            //ecmoban模板堂 --zhuo start
            $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $rec_ids, $user_id);

            $this->assign('cart_goods_number', $cart_goods_number);
            $consignee['province_name'] = get_goods_region_name($consignee['province']);
            $consignee['city_name'] = get_goods_region_name($consignee['city']);
            $consignee['district_name'] = get_goods_region_name($consignee['district']);
            $consignee['street'] = get_goods_region_name($consignee['street']);//街道
            $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['address'] . $consignee['street'];

            $this->assign('consignee', $consignee);

            //切换配送方式 by kong
            foreach ($cart_goods_list as $key => $val) {
                foreach ($tmp_shipping_id_arr as $k => $v) {
                    if ($v[1] > 0 && $val['ru_id'] == $v[0]) {
                        $cart_goods_list[$key]['tmp_shipping_id'] = $v[1];
                    }
                }
            }

            $type = array(
                'type' => 0,
                'shipping_list' => $shipping,
                'step' => 0,
            );
            /* 计算订单的费用 */
            $cart_goods = $this->shippingService->get_new_group_cart_goods($cart_goods_list); // 取得商品列表，计算合计
            $total = order_fee($order, $cart_goods, $consignee, $type, $rec_ids, 0, $cart_goods_list, 0, 0, $store_id, '', $user_id);

            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS) {
                $result['is_group_buy'] = 1;
            }
            $result['amount'] = $total['amount_formated'];
            $result['order'] = $order;
            $result['total'] = $total;
        }

        return $this->succeed($result);
    }

    /**
     * 配送价格
     *
     * @param Request $request
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function goodsShippingFee(Request $request)
    {
        $goods_id = $request->get('goods_id', 0);
        $attr_id = $request->get('goods_attr_id', ''); //商品详情点击触发
        $is_price = $request->get('is_price', 0); //商品详情点击触发
        $position = $request->get('position', '');
        $position = dsc_decode($position, true);
        $province_id = isset($position['province_id']) ? intval($position['province_id']) : 0;
        $city_id = isset($position['city_id']) ? intval($position['city_id']) : 0;
        $district_id = isset($position['district_id']) ? intval($position['district_id']) : 0;
        $street = isset($position['street']) ? intval($position['street']) : 0;

        /* 生成仓库地区缓存 */
        $warehouseCache = $this->areaService->setWarehouseCache($this->uid, $province_id, $city_id, $district_id);

        if ($goods_id > 0) {
            $region = [1, $province_id, $city_id, $district_id, $street];
            $shippingFee = goodsShippingFee($goods_id, $warehouseCache['warehouse_id'], $warehouseCache['area_id'], $warehouseCache['area_city'], $region);

            if ($is_price == 1) {
                $shippingFee['goods'] = app(GoodsMobileService::class)->goodsPropertiesPrice($this->uid, $goods_id, $attr_id, 1, $warehouseCache['warehouse_id'], $warehouseCache['area_id'], $warehouseCache['area_city']);
            }
        } else {
            $shippingFee = 0;
        }

        return $this->succeed($shippingFee);
    }
}
