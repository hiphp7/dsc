<?php

namespace App\Services\Erp;

use App\Models\OrderCloud;
use App\Models\OrderGoods;
use App\Models\PayLog;
use App\Models\Products;
use App\Models\Region;
use App\Models\ReturnImages;
use App\Models\UsersVatInvoicesInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 贡云商品管理
 * Class JigonService
 * @package App\Services\Erp
 */
class JigonManageService
{
    protected $jigonService;
    protected $dscRepository;
    protected $baseRepository;
    protected $config;

    public function __construct(
        JigonService $jigonService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->jigonService = $jigonService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 检查库存
     *
     * @param array $where
     * @return bool|int
     */
    public function jigonGoodsNumber($where = [])
    {
        $is_open = $this->jigonIsOpen();

        if ($is_open == 0) {
            return false;
        }

        $goods_number = 0;
        $productIds = [];

        if (isset($where['product_id'])) {
            $productIds = Products::select('cloud_product_id')->where('product_id', $where['product_id']);
            $productIds = $this->baseRepository->getToArrayGet($productIds);
            $productIds = $this->baseRepository->getKeyPluck($productIds, 'cloud_product_id');
        } elseif (isset($where['cloud_product_id'])) {
            $productIds = [$where['cloud_product_id']];
        } else {
            return $goods_number;
        }

        $cloud_prod = $this->jigonService->query($productIds);
        $cloud_prod = dsc_decode($cloud_prod, true);

        if ($cloud_prod['code'] == 10000) {
            $cloud_product = $cloud_prod['data'];
            if ($cloud_product) {
                foreach ($cloud_product as $k => $v) {
                    if (in_array($v['productId'], $productIds)) {
                        if ($v['hasTax'] == 1) {
                            $goods_number = $v['taxNum'];
                        } else {
                            $goods_number = $v['noTaxNum'];
                        }

                        break;
                    }
                }
            }
        }

        return $goods_number;
    }

    /**
     * 贡云商品推送
     */
    public function pushJigonOrderGoods($cart_goods = [], $order = [], $type = 'pc') //0、pc返回  1、 api返回
    {
        $is_open = $this->jigonIsOpen();

        if ($is_open == 0) {
            return false;
        }

        $request = [];

        //判断是否填写回调接口appkey，如果没有返回失败
        if (!$this->config['cloud_dsc_appkey']) {
            return $request;
        }

        $order_request = [];
        $order_detaillist = [];
        foreach ($cart_goods as $cart_goods_key => $cart_goods_val) {
            if (isset($cart_goods_val['cloud_id']) && $cart_goods_val['cloud_id'] > 0) {
                $arr = [];
                $arr['goodName'] = $cart_goods_val['cloud_goodsname']; //商品名称
                $arr['goodId'] = $cart_goods_val['cloud_id']; //商品id
                //获取货品id，库存id
                if ($cart_goods_val['goods_attr_id']) {
                    $goods_attr_id = explode(',', $cart_goods_val['goods_attr_id']);

                    //获取货品信息
                    $products_info = Products::select('cloud_product_id', 'inventoryid')->where('goods_id', $cart_goods_val['goods_id']);

                    foreach ($goods_attr_id as $key => $val) {
                        $products_info = $products_info->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                    }

                    $products_info = $this->baseRepository->getToArrayFirst($products_info);

                    $arr['inventoryId'] = $products_info['inventoryid']; //库存id
                    $arr['productId'] = $products_info['cloud_product_id']; //货品id
                    $arr['productPrice'] = ''; //new
                }
                $arr['quantity'] = $cart_goods_val['goods_number']; //购买数量
                $arr['deliveryWay'] = '3'; //快递方式 3为快递送  上门自提不支持
                $arr['brandId'] = 0; //new
                $arr['channel'] = 0; //new
                $arr['navigateImg1'] = ''; //new
                $arr['salePrice'] = 0; //new
                $arr['storeId'] = 0; //new

                $order_detaillist[] = $arr;
            }
        }

        //初始化数据
        if (!empty($order_detaillist)) {
            $order_request['orderDetailList'] = $order_detaillist;
            $order_request['address'] = $order['address']; //地址
            $order_request['area'] = Region::where('region_id', $order['district'])->value('region_name'); //地区
            $order_request['city'] = Region::where('region_id', $order['city'])->value('region_name'); //城市
            $order_request['province'] = Region::where('region_id', $order['province'])->value('region_name'); //城市
            $order_request['remark'] = $order['postscript']; //备注
            $order_request['mobile'] = intval($order['mobile']); //电话
            $order_request['payType'] = 99; //支付方式 统一用99
            $order_request['linkMan'] = $order['consignee']; //收件人
            $order_request['billType'] = !empty($order['invoice_type']) ? 2 : 1; //发票类型 2:公司，1、个人
            $order_request['billHeader'] = $order['inv_payee']; //发票抬头
            $order_request['isBill'] = 0; //是否开发票 根据开票规则 不直接开票给用户 所以默认传0
            $order_request['taxNumber'] = ''; //税号

            if ($order_request['billType'] == 2) {
                $users_vat_invoices_info = UsersVatInvoicesInfo::select('company_name', 'tax_id')
                    ->where('user_id', $order['user_id']);
                $users_vat_invoices_info = $this->baseRepository->getToArrayFirst($users_vat_invoices_info);

                if ($users_vat_invoices_info) {
                    $order_request['billHeader'] = $users_vat_invoices_info['company_name'];
                    $order_request['taxNumber'] = $users_vat_invoices_info['tax_id'];
                }
            }

            $request = $this->jigonService->push($order_request, $order);
            $request = dsc_decode($request, true);

            if ($request['code'] != '10000') {
                if ($type == 'pc') { // pc 返回
                    return show_message($request['message'], '', '', 'info', true);
                } elseif ($type == 'api') { // api 返回
                    return ['error' => 1, 'msg' => $request['message']];
                } else { // 异常返回
                    return [];
                }
            }
        }

        //录入贡云订单信息
        if (isset($request['data']['orderDetailList']) && !empty($request['data']['orderDetailList'])) {
            foreach ($request['data']['orderDetailList'] as $k => $v) {
                $cloud_order = [];
                $cloud_order['apiordersn'] = trim($v['apiOrderSn']); //订单编号 对应贡云子订单号
                $cloud_order['parentordersn'] = trim($request['data']['result']); //主订单号
                $cloud_order['goods_id'] = intval($v['goodId']); //商品id 对应的是贡云的商品id
                $cloud_order['user_id'] = $order['user_id']; //下单会员id
                $cloud_order['cloud_orderid'] = $v['orderId']; //贡云字订单id
                $cloud_order['cloud_detailed_id'] = $v['id']; //贡云订单明细id

                //处理价格
                $totalprice = !empty($v['totalPrice']) ? trim($v['totalPrice']) : 0;

                //分转换为元
                if ($totalprice > 0) {
                    $totalprice = $totalprice / 100;
                }

                $totalprice = floatval($totalprice);
                $cloud_order['totalprice'] = $totalprice; //总价 对应贡云价格

                $cloud_order['rec_id'] = OrderGoods::where('order_id', $order['order_id'])
                    ->where('user_id', $order['user_id'])
                    ->whereHas('getGoods', function ($query) use ($v) {
                        $query->where('cloud_id', $v['goodId']);
                    })->value('rec_id');

                OrderCloud::insert($cloud_order);
            }
        }
    }

    /**
     * 确认订单 推送给贡云
     */
    public function jigonConfirmOrder($order_id)
    {
        $is_open = $this->jigonIsOpen();

        if ($is_open == 0) {
            return false;
        }

        if ($order_id > 0) {
            //获取贡云服订单号  和上次订单总额
            $cloud_order = OrderCloud::select('rec_id', 'parentordersn AS orderSn')
                ->whereHas('getOrderGoods', function ($query) use ($order_id) {
                    $query->where('order_id', $order_id);
                });

            $cloud_order = $cloud_order->with([
                'getOrderGoods' => function ($query) {
                    $query->select('rec_id')->selectRaw("SUM(goods_number * goods_price) AS paymentFee");
                }
            ]);

            $cloud_order = $this->baseRepository->getToArrayFirst($cloud_order);

            if ($cloud_order) {
                $cloud_order['paymentFee'] = $cloud_order['get_order_goods'] ? floatval($cloud_order['get_order_goods']['paymentFee'] * 100) : 0;

                //获取支付流水号
                $payId = PayLog::where('order_id', $order_id)->where('order_type', PAY_ORDER)->value('log_id');
                $cloud_order['payId'] = $payId ? $payId : 0;

                $cloud_order['payType'] = 99; //支付方式  默认99

                $cloud_order['notifyUrl'] = $this->dscRepository->dscUrl("api.php?app_key=" . $this->config['cloud_dsc_appkey'] . "&method=dsc.order.confirmorder.post&format=json&interface_type=1");

                $this->jigonService->confirm($cloud_order);
            }
        }
    }

    /*
    * 退换货
    */
    public function jigonAfterSales($where = [])
    {
        $is_open = $this->jigonIsOpen();

        if ($is_open == 0) {
            return false;
        }

        $aftersn = 0; //贡云售后单号

        //获取售后订单贡云扩展信息
        $order_cloud = OrderCloud::where('rec_id', $where['rec_id']);
        $order_cloud = $this->baseRepository->getToArrayFirst($order_cloud);

        if (!empty($order_cloud)) {
            //贡云商品不支持换货与维修
            if ($where['return_type'] == 0 || $where['return_type'] == 2) {
                return show_message(lang('common.jigong.apply_for_failed'), '', '', 'info', true);
            }
            $isRefund = 1;
            if ($where['return_type'] == 3) {
                $isRefund = 2;
            }
            $order_return_request = [
                'isRefund' => intval($isRefund),
                'orderDetailId' => intval($order_cloud['cloud_detailed_id']),
                'orderInfoId' => intval($order_cloud['cloud_orderid']),
                'refundNum' => intval($where['return_number']),
                'userReason' => trim($where['return_brief']),
                'imgProof1' => '',
                'imgProof2' => '',
                'imgProof3' => ''
            ];
            //获取凭证图片列表
            $images_list = ReturnImages::select('img_file')->where('rec_id', $where['rec_id'])->where('user_id', $where['user_id']);
            $images_list = $this->baseRepository->getToArrayGet($images_list);
            $images_list = $this->baseRepository->getKeyPluck($images_list, 'img_file');

            if (!empty($images_list)) {
                foreach ($images_list as $k => $v) {
                    if ($v) {
                        $img = $this->dscRepository->getImagePath($v);
                        if (!empty($img) && (strpos($img, 'http://') === false && strpos($img, 'https://') === false && strpos($img, 'errorImg.png') === false)) {
                            $img = asset('/') . $img;
                        }
                        $i = $k + 1;
                        $order_return_request['imgProof' . $i] = $img;
                    }
                }
            }

            $request = $this->jigonService->saveAfterSales($order_return_request);

            if ($request) {
                $request = dsc_decode($request, true);
                //返回失败信息
                if ($request['code'] != '10000') {
                    if ($where['type'] == 'pc') { // pc 返回
                        return show_message($request['message'], '', '', 'info', true);
                    } elseif ($where['type'] == 'api') { // api 返回
                        return ['code' => 1, 'msg' => $request['message']];
                    } else {
                        return [];
                    }
                } else {
                    $aftersn = $request['data']['afterSn'];
                }
            } else {
                if ($where['type'] == 'pc') { // pc 返回
                    return show_message(lang('common.jigong.handle_failed'), '', '', 'info', true);
                } elseif ($where['type'] == 'api') { // api 返回
                    return ['code' => 1, 'msg' => lang('common.jigong.handle_failed')];
                } else {
                    return [];
                }
            }
        }

        return $aftersn;
    }

    /*
    * 退换货收货地址
    */
    public function jigonRefundAddress($where = [])
    {
        $is_open = $this->jigonIsOpen();

        if ($is_open == 0) {
            return false;
        }

        $return_info = [];

        $requ = $this->jigonService->getAfterSalesAddress($where);
        $requ = dsc_decode($requ, true);

        if ($requ['code'] = 10000) {
            $return_info = $requ['data'];
        }

        return $return_info;
    }

    /*
    * 校验是否开启贡云
    */
    private function jigonIsOpen()
    {
        return $this->config['cloud_is_open'];
    }
}
