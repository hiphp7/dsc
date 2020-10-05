<?php

namespace App\Services\User;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\OrderReturnExtend;
use App\Models\ReturnAction;
use App\Models\ReturnCause;
use App\Models\ReturnGoods;
use App\Models\ReturnImages;
use App\Models\SellerBillOrder;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\UserBonus;
use App\Plugins\UserRights\Discount\Services\DiscountRightsService;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Erp\JigonManageService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderRefoundService;
use App\Services\Package\PackageGoodsService;

/**
 * 退换货
 * Class RefoundService
 * @package App\Services\User
 */
class RefoundService
{
    protected $timeRepository;
    protected $goodsService;
    protected $config;
    protected $baseRepository;
    protected $commonRepository;
    protected $jigonManageService;
    protected $dscRepository;
    protected $merchantCommonService;
    private $lang;
    protected $goodsCommonService;
    protected $orderRefoundService;
    protected $orderCommonService;
    protected $packageGoodsService;

    public function __construct(
        TimeRepository $timeRepository,
        GoodsMobileService $goodsService,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        JigonManageService $jigonManageService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        GoodsCommonService $goodsCommonService,
        OrderRefoundService $orderRefoundService,
        OrderCommonService $orderCommonService,
        PackageGoodsService $packageGoodsService
    )
    {
        //加载外部类
        $files = [
            'clips',
            'common',
            'time',
            'main',
            'order',
            'function',
            'base',
            'goods',
            'ecmoban'
        ];
        load_helper($files);
        $this->timeRepository = $timeRepository;
        $this->goodsService = $goodsService;
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->jigonManageService = $jigonManageService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsCommonService = $goodsCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderRefoundService = $orderRefoundService;
        $this->orderCommonService = $orderCommonService;
        $this->packageGoodsService = $packageGoodsService;

        /* 语言包 */
        $common = lang('common');
        $user = lang('user');
        $lang = array_merge($common, $user);

        $this->lang = $lang;
    }

    /**
     * 商品列表
     * Class User
     * @package App\Services
     */
    public function getGoodsOrder($order_id)
    {
        load_helper('transaction');
        /* 订单信息 */
        $order = order_info($order_id);

        /* 订单商品 */
        $goods_list = order_goods($order_id);

        if ($goods_list) {
            $order['all_refound'] = 0;
            foreach ($goods_list as $key => $value) {
                if (isset($value['extension_code']) && $value['extension_code'] == 'package_buy') {
                    $is_package_buy = 1;
                } else {
                    $is_package_buy = 0;
                }

                if ($is_package_buy == 0) {
                    $order['goods'][$key]['goods_name'] = $value['get_goods']['goods_name'] ?? '';
                    $order['goods'][$key]['goods_id'] = $value['get_goods']['goods_id'] ?? 0;
                    $order['goods'][$key]['goods_thumb'] = $this->dscRepository->getImagePath($value['get_goods']['goods_thumb'] ?? '');
                    $order['goods'][$key]['goods_cause'] = $this->goodsCommonService->getGoodsCause($value['get_goods']['goods_cause'] ?? '');

                    $price[] = $value['subtotal'];
                    $order['goods'][$key]['market_price'] = $this->dscRepository->getPriceFormat($value['market_price'], false);
                    $order['goods'][$key]['goods_number'] = $value['goods_number'];
                    $order['goods'][$key]['goods_price'] = $this->dscRepository->getPriceFormat($value['goods_price'], false);
                    $order['goods'][$key]['subtotal'] = $this->dscRepository->getPriceFormat($value['subtotal'], false);
                    $order['goods'][$key]['is_refound'] = get_is_refound($value['rec_id']);   //判断是否退换货过
                    $order['goods'][$key]['goods_attr'] = str_replace(' ', '&nbsp;&nbsp;&nbsp;&nbsp;', $value['goods_attr']);
                    $order['goods'][$key]['rec_id'] = $value['rec_id'];

                    $order['goods'][$key]['extension_code'] = $value['extension_code'];

                    if ($value['is_gift'] > 0) {
                        $order['all_refound'] = 1;
                    }
                }
            }
        }

        return $order;
    }


    /**
     * 退换货列表
     *
     * @param int $user_id
     * @param int $order_id
     * @param int $start
     * @param int $size
     * @return array
     * @throws \Exception
     */
    public function getRefoundList($user_id = 0, $order_id = 0, $start = 1, $size = 10)
    {
        //判断是否支持激活
        $activation_number_type = $this->config['activation_number_type'];
        $activation_number_type = (intval($activation_number_type) > 0) ? intval($activation_number_type) : 2;

        $res = OrderReturn::where('user_id', $user_id)/*->where('refound_status', 0)*/
        ;//列表显示全部退换货订单
        //refound_status(0：未退款，1：已退款，2：已换货，3：已维修，4：未换货，5：未维修)

        if ($order_id > 0) {
            $res = $res->where('order_id', $order_id);
        }

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'goods_name');
            },
            'getOrderGoods' => function ($query) {
                $query->select('rec_id', 'extension_code');
            },
            'getReturnGoods' => function ($query) {
                $query->select('ret_id', 'return_number');
            }
        ]);

        // 检测商品是否存在
        $res = $res->whereHas('getGoods', function ($query) {
            $query->select('goods_id')->where('goods_id', '>', 0);
        });

        $start = ($start - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }
        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->orderBy('ret_id', 'desc')->get();

        $res = $res ? $res->toArray() : [];

        $goods_list = [];

        if ($res) {
            $_lang = lang('user');
            foreach ($res as $row) {
                $row['goods_name'] = $row['get_goods']['goods_name'];
                $row['goods_id'] = $row['get_goods']['goods_id'];
                $row['apply_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['apply_time']);
                $row['should_return'] = $this->dscRepository->getPriceFormat($row['should_return']);

                $row['order_status'] = '';
                if ($row['return_status'] == 0 && $row['refound_status'] == 0) {
                    //  提交退换货后的状态 由用户寄回
                    $row['order_status'] .= "<span>" . $_lang['user_return'] . "</span>";
                    $row['refound_cancel'] = true;
                } elseif ($row['return_status'] == 1) {
                    //退换商品收到
                    $row['order_status'] .= "<span>" . $_lang['get_goods'] . "</span>";
                } elseif ($row['return_status'] == 2) {
                    //换货商品寄出 （分单）
                    $row['order_status'] .= "<span>" . $_lang['send_alone'] . "</span>";
                } elseif ($row['return_status'] == 3) {
                    //换货商品寄出
                    $row['order_status'] .= "<span>" . $_lang['send'] . "</span>";
                } elseif ($row['return_status'] == 4) {
                    //完成
                    $row['order_status'] .= "<span>" . $_lang['complete'] . "</span>";
                } elseif ($row['return_status'] == 6) {
                    //被拒
                    $row['order_status'] .= "<span>" . $_lang['rf'][$row['return_status']] . "</span>";
                } else {
                    //其他
                }

                // 0 维修- 1 退货（款）-2 换货 3 仅退款状态
                if ($row['return_type'] == 0) {
                    if ($row['return_status'] == 4) {
                        $row['reimburse_status'] = $_lang['ff'][FF_MAINTENANCE];
                    } else {
                        $row['reimburse_status'] = $_lang['ff'][FF_NOMAINTENANCE];
                    }
                } elseif ($row['return_type'] == 1) {
                    if ($row['refound_status'] == 1) {
                        $row['reimburse_status'] = $_lang['ff'][FF_REFOUND];
                    } else {
                        $row['reimburse_status'] = $_lang['ff'][FF_NOREFOUND];
                    }
                } elseif ($row['return_type'] == 2) {
                    if ($row['return_status'] == 4) {
                        $row['reimburse_status'] = $_lang['ff'][FF_EXCHANGE];
                    } else {
                        $row['reimburse_status'] = $_lang['ff'][FF_NOEXCHANGE];
                    }
                } elseif ($row['return_type'] == 3) {
                    if ($row['refound_status'] == 1) {
                        $row['reimburse_status'] = $_lang['ff'][FF_REFOUND];
                    } else {
                        $row['reimburse_status'] = $_lang['ff'][FF_NOREFOUND];
                    }
                }

                if ($row['return_status'] == 6) {
                    $row['reimburse_status'] = $_lang['rf'][$row['return_status']];
                }

                $row['activation_type'] = 0;
                //判断是否支持激活
                if ($row['return_status'] == 6) {
                    if ($row['activation_number'] < $activation_number_type) {
                        $row['activation_type'] = 1;
                    }
                    $row['agree_apply'] = -1; // 可激活时 不显示待同意 状态
                }

                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['get_goods']['goods_thumb']);

                if (isset($row['get_goods']['extension_code']) && $row['get_goods']['extension_code'] == 'package_buy') {
                    $is_package_buy = 1;
                } else {
                    $is_package_buy = 0;
                }

                if ($is_package_buy == 0) {
                    $goods_list[] = $row;
                }
            }
        }

        return $goods_list;
    }


    /**
     * 退换货申请
     * @param $user_id
     * @param $order_id
     * @param $rec_id
     * @param int $warehouse_id
     * @param int $area_id
     * @return array
     */
    public function applyReturn($user_id, $order_id, $rec_ids)
    {
        /* 根据订单id或订单号查询订单信息 */
        $order = order_info($order_id);

        $info['order'] = $order;

        /* 退货权限：订单状态 已发货、未退货 */
        $info['return_allowable'] = OrderInfo::where('order_id', $order_id)
            ->where('shipping_status', '>', SS_UNSHIPPED)
            ->where('order_status', '<>', OS_RETURNED)
            ->count();

        $info['consignee'] = [
            'country' => $order['country'],
            'province' => $order['province'],
            'city' => $order['city'],
            'district' => $order['district'],
            'address' => $order['address'],
            'mobile' => $order['mobile'],
            'consignee' => $order['consignee'],
            'user_id' => $order['user_id'],
            'region' => $order['region']
        ];

        $info['return_goods_num'] = 0;

        // 退换货原因
        $info['parent_cause'] = $this->getReturnCause(0, 1);

        //第一个购买成为分销商订单产品ID
        $rec_id_drp = $this->getOrderGoodsDrp($user_id);
        $buy_drp_show = 0;

        $info['img_list'] = [];

        //支持多商品申请退换货
        $cause_arr = [];
        foreach ($rec_ids as $k => $rec_id) {
            // 判断退换货是否已申请
            $is_refound = get_is_refound($rec_id);

            if ($is_refound == 1) {
                return $info = [
                    'msg' => str_replace(['[', ']'], '', lang('user.Have_applied'))
                ];
            }

            /* 订单商品 */
            $goods_info = $this->rec_goods($rec_id);

            $info['goods_list'][] = $goods_info;

            $info['return_goods_num'] += OrderGoods::where('goods_id', $goods_info['goods_id'])->where('order_id', $order_id)->value('goods_number');

            if ($rec_id_drp == $rec_id) {
                $buy_drp_show = 1;
            }


            /* 退换货标志列表 */
            $cause_arr[] = $this->goodsCommonService->getGoodsCause($goods_info['goods_cause'], $buy_drp_show);

            //图片列表
            $where = [
                'user_id' => $user_id,
                'rec_id' => $rec_id
            ];

            $img_list = $this->orderRefoundService->getReturnImagesList($where);
            if (!empty($img_list)) {
                array_push($info['img_list'], $img_list);
            }
        }

        // 未发货 不显示维修、换货、退货，只显示 仅退款
        $goods_cause = [];
        foreach ($cause_arr as $k => $v) {
            foreach ($v as $key => $value) {
                if ($order['shipping_status'] == 0) {
                    if ($value['cause'] == 3) {
                        $value['is_checked'] = 1;
                        $v[$key] = $value;
                    } else {
                        unset($v[$key]);
                        continue;
                    }
                }
                $goods_cause[$key] = $value;
            }
        }
        //服务类型
        $info['goods_cause'] = collect($goods_cause)->values()->all();

        return $info;
    }

    /**
     * 获取订单里某个商品 信息
     *
     * @param $rec_id
     * @return array
     */
    public function rec_goods($rec_id)
    {
        $res = OrderGoods::where('rec_id', $rec_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        if (empty($res)) {
            return [];
        }

        $res['subtotal'] = $res['goods_price'] * $res['goods_number'];

        if ($res['extension_code'] == 'package_buy') {
            $res['package_goods_list'] = $this->packageGoodsService->getPackageGoods($res['goods_id']);
        }
        $res['market_price'] = $this->dscRepository->getPriceFormat($res['market_price'], false);

        $res['goods_price1'] = $res['goods_price'];
        $res['goods_price'] = $this->dscRepository->getPriceFormat($res['goods_price'], false);
        $res['shop_price_formated'] = $this->dscRepository->getPriceFormat($res['goods_price1'], false);
        $res['subtotal'] = $this->dscRepository->getPriceFormat($res['subtotal'], false);
        $res['attr_name'] = $res['goods_attr'];

        $res['formated_goods_coupons'] = $this->dscRepository->getPriceFormat($res['goods_coupons'], false);
        $res['formated_goods_bonus'] = $this->dscRepository->getPriceFormat($res['goods_bonus'], false);

        $goods = Goods::where('goods_id', $res['goods_id']);
        $goods = $this->baseRepository->getToArrayFirst($goods);

        $res['goods_cause'] = $goods['goods_cause'] ?? '';

        $res['user_name'] = $this->merchantCommonService->getShopName($goods['user_id'], 1);
        $res['shop_name'] = $res['user_name'];

        $basic_info = SellerShopinfo::where('ru_id', $goods['user_id']);
        $basic_info = $this->baseRepository->getToArrayFirst($basic_info);

        $chat = $this->dscRepository->chatQq($basic_info);
        $res['kf_type'] = $chat['kf_type'];
        $res['kf_qq'] = $chat['kf_qq'];
        $res['kf_ww'] = $chat['kf_ww'];

        /* 修正商品图片 */
        $res['goods_img'] = $this->dscRepository->getImagePath($goods['goods_img']);
        $res['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);

        return $res;
    }

    /**
     * 获取退换货详情
     *
     * @param $user_id
     * @param $ret_id
     * @return array
     */
    public function returnDetail($user_id = 0, $ret_id = 0)
    {

        $order = $this->return_order_info($ret_id, '', 0, $user_id);

        if (is_null($order)) {
            return [];
        }

        /* 对发货号处理 */
        if (!empty($order['out_invoice_no'])) {
            // 商家寄出地址
            if ($order['out_shipping_name'] == '999') {
                // 其他快递
                $order['out_invoice_no_btn'] = "https://m.kuaidi100.com/result.jsp?nu=" . $order['out_invoice_no'];
            } else {
                $shipping_code = Shipping::where(['shipping_id' => $order['out_shipping_name']])->value('shipping_code');

                $shipping = $this->commonRepository->shippingInstance($shipping_code);
                if (!is_null($shipping)) {
                    $code_name = $shipping->get_code_name();
                    $order['out_invoice_no_btn'] = route('tracker', ['type' => $code_name, 'postid' => $order['out_invoice_no']]);
                }
            }
        }
        if (!empty($order['back_invoice_no'])) {
            // 用户寄出地址
            if ($order['back_shipping_name'] == '999') {
                // 其他快递
                $order['back_invoice_no_btn'] = "https://m.kuaidi100.com/result.jsp?nu=" . $order['back_invoice_no'];
            } else {
                $shipping_code = Shipping::where(['shipping_id' => $order['back_shipping_name']])->value('shipping_code');

                $shipping = $this->commonRepository->shippingInstance($shipping_code);
                if (!is_null($shipping)) {
                    $code_name = $shipping->get_code_name();
                    $order['back_invoice_no_btn'] = route('tracker', ['type' => $code_name, 'postid' => $order['back_invoice_no']]);
                }
            }
        }

        $shippinOrderInfo = OrderGoods::where('order_id', $order['order_id']);
        $shippinOrderInfo = $this->baseRepository->getToArrayFirst($shippinOrderInfo);

        //快递公司
        $region_id_list = [
            $order['country'], $order['province'], $order['city'], $order['district']
        ];
        $shipping_list = available_shipping_list($region_id_list, $shippinOrderInfo);

        if ($shipping_list) {
            foreach ($shipping_list as $key => $val) {
                $shipping_cfg = unserialize_config($val['configure']);
                $shipping_fee = 0;

                $shipping_list[$key]['format_shipping_fee'] = $this->dscRepository->getPriceFormat($shipping_fee);
                $shipping_list[$key]['shipping_fee'] = $shipping_fee;
                $shipping_list[$key]['free_money'] = $this->dscRepository->getPriceFormat($shipping_cfg['free_money']);
                if (isset($val['insure'])) {
                    $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ? $this->dscRepository->getPriceFormat($val['insure']) : $val['insure'];
                }
            }

            $order['shipping_list'] = $shipping_list ? array_values($shipping_list) : [];
        }

        $order['status'] = $order['return_status'];
        $order['refound'] = $order['refound_status'];

        //获取退换货扩展信息
        $aftersn = OrderReturnExtend::where('ret_id', $ret_id)->value('aftersn');

        //获取退换货扩展信息 如果存在贡云退换货信息  获取退换货地址
        $order['cloud_return_info'] = !empty($aftersn) ? $this->jigonManageService->jigonRefundAddress(['afterSn' => $aftersn]) : [];

        // 在线退款查询
        $this->refundCheck($order['return_sn']);

        return $order;
    }

    /**
     * 退货单信息
     *
     * @param int $ret_id
     * @param string $order_sn
     * @param int $order_id
     * @param int $user_id
     * @return mixed
     * @throws \Exception
     */
    public function return_order_info($ret_id = 0, $order_sn = '', $order_id = 0, $user_id = 0)
    {
        $ret_id = intval($ret_id);
        if ($ret_id > 0) {

            $where = [
                'ret_id' => $ret_id,
                'user_id' => $user_id
            ];

            $select = ['rec_id', 'ret_id', 'goods_id', 'return_number', 'refound'];
            if (file_exists(MOBILE_DRP)) {
                array_push($select, 'membership_card_discount_price');
            }

            $res = ReturnGoods::select($select)
                ->whereHas('getOrderReturn', function ($query) use ($where) {
                    $query = $query->where('ret_id', $where['ret_id']);

                    if ($where['user_id'] > 0) {
                        $query->where('user_id', $where['user_id']);
                    }
                });

            $res = $res->with([
                'getOrderReturn',
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'goods_thumb', 'goods_name', 'shop_price', 'user_id AS ru_id');
                },
                'getOrderReturnExtend' => function ($query) {
                    $query->select('ret_id', 'return_number');
                }
            ]);

            $res = $res->orderBy('rg_id', 'DESC');

            $res = $this->baseRepository->getToArrayFirst($res);
            $res = $this->baseRepository->getArrayMerge($res, $res['get_order_return']);
            $res = $this->baseRepository->getArrayMerge($res, $res['get_goods']);
            $res = $this->baseRepository->getArrayMerge($res, $res['get_order_return_extend']);

            if ($res) {
                $order = OrderInfo::select('order_id', 'order_sn', 'add_time', 'chargeoff_status', 'goods_amount', 'discount', 'chargeoff_status as order_chargeoff_status', 'is_zc_order', 'country', 'province', 'city', 'district', 'street')
                    ->where('order_id', $res['order_id'])
                    ->with([
                        'getDeliveryOrder' => function ($query) {
                            $query->select('delivery_id', 'order_id', 'delivery_sn', 'update_time', 'how_oos', 'shipping_fee', 'insure_fee', 'invoice_no');
                        },
                        'getRegionProvince' => function ($query) {
                            $query->select('region_id', 'region_name');
                        },
                        'getRegionCity' => function ($query) {
                            $query->select('region_id', 'region_name');
                        },
                        'getRegionDistrict' => function ($query) {
                            $query->select('region_id', 'region_name');
                        },
                        'getRegionStreet' => function ($query) {
                            $query->select('region_id', 'region_name');
                        }
                    ]);

                $order = $this->baseRepository->getToArrayFirst($order);
                $order = $this->baseRepository->getArrayMerge($order, $order['get_delivery_order']);

                $res = $this->baseRepository->getArrayMerge($res, $order);

                if ($res && $res['chargeoff_status'] != 0) {
                    $res['chargeoff_status'] = $res['order_chargeoff_status'] ? $res['order_chargeoff_status'] : 0;
                }
            }

            $order = $res;
        } else {
            $order = OrderReturn::whereRaw(1);
            if ($order_id) {
                $order = $order->where('order_id', $order_id);
            } else {
                $order = $order->where('order_sn', $order_sn);
            }

            if ($user_id > 0) {
                $order = $order->where('user_id', $user_id);
            }

            $order = $order->with([
                'getReturnGoods' => function ($query) {
                    $query->select('ret_id', 'return_number', 'refound');
                },
                'getRegionProvince' => function ($query) {
                    $query->select('region_id', 'region_name');
                },
                'getRegionCity' => function ($query) {
                    $query->select('region_id', 'region_name');
                },
                'getRegionDistrict' => function ($query) {
                    $query->select('region_id', 'region_name');
                },
                'getRegionStreet' => function ($query) {
                    $query->select('region_id', 'region_name');
                }
            ]);
            $order = $this->baseRepository->getToArrayFirst($order);
        }

        if ($order) {

            if (!isset($order['goods_coupons'])) {
                $order['goods_coupons'] = $order['get_order_return']['goods_coupons'] ?? 0;
            }

            if (!isset($order['goods_bonus'])) {
                $order['goods_bonus'] = $order['get_order_return']['goods_bonus'] ?? 0;
            }

            $order['formated_goods_coupons'] = $this->dscRepository->getPriceFormat($order['goods_coupons']);
            $order['formated_goods_bonus'] = $this->dscRepository->getPriceFormat($order['goods_bonus']);

            if ($order['discount'] > 0) {
                $discount_percent = $order['discount'] / $order['goods_amount'];
                $order['discount_percent_decimal'] = number_format($discount_percent, 2, '.', '');
                $order['discount_percent'] = $order['discount_percent_decimal'] * 100;
            } else {
                $order['discount_percent_decimal'] = 0;
                $order['discount_percent'] = 0;
            }

            $order['attr_val'] = is_null($order['attr_val']) ? '' : (is_string($order['attr_val']) ? $order['attr_val'] : unserialize($order['attr_val']));
            $order['apply_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['apply_time']);
            //$order['formated_update_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['update_time']);
            $order['formated_return_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['return_time']);
            $order['formated_add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['add_time']);
            $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

            $return_goods = $order['get_return_goods'] ?? [];
            $return_number = $return_goods['return_number'] ?? 0;
            $order['return_number'] = $return_goods['return_number'] ?? 0;

            //获取订单商品总数
            $all_goods_number = OrderGoods::selectRaw("SUM(goods_number) AS goods_number")->where('order_id', $order['order_id'])->value('goods_number');

            //如果订单只有一个商品  折扣金额为全部折扣  否则按折扣比例计算
            if ($return_number == $all_goods_number) {
                $order['discount_amount'] = number_format($order['discount']);
            } else {
                $order['discount_amount'] = number_format($order['should_return'] * $order['discount_percent_decimal'], 2, '.', ''); //折扣金额
            }
            $order['should_return1'] = number_format($order['should_return'] - $order['discount_amount'], 2, '.', '');

            $return_amount = $order['should_return'] + $order['return_shipping_fee'] - $order['discount_amount'];
            $should_return = $order['should_return'] - $order['discount_amount'];

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $order['formated_return_rate_price'] = $this->dscRepository->getPriceFormat($order['return_rate_price'], false);
                $should_return = $order['should_return'] + $order['return_rate_price'] - $order['discount_amount'];
                $return_amount = $order['should_return'] + $order['return_shipping_fee'] + $order['return_rate_price'] - $order['discount_amount'];
            }

            // 订单退款 不退开通会员权益卡购买金额
            if (file_exists(MOBILE_DRP) && $ret_id > 0) {
                $order['membership_card_discount_price_formated'] = $this->dscRepository->getPriceFormat($order['membership_card_discount_price'], false);
            }
            $order['formated_goods_amount'] = $this->dscRepository->getPriceFormat($order['should_return']);
            $order['formated_discount_amount'] = $this->dscRepository->getPriceFormat($order['discount_amount']);
            $order['formated_should_return'] = $this->dscRepository->getPriceFormat($should_return);
            $order['formated_return_shipping_fee'] = $this->dscRepository->getPriceFormat($order['return_shipping_fee']);
            $order['formated_return_amount'] = $this->dscRepository->getPriceFormat($return_amount);
            $order['formated_actual_return'] = $this->dscRepository->getPriceFormat($order['actual_return']);

            $order['return_status1'] = $order['return_status'];
            if ($order['return_status'] < 0) {
                $order['return_status1'] = lang('user.only_return_money');
            } else {
                $order['return_status1'] = lang('user.rf.' . $order['return_status']);
            }
            $order['shop_price'] = $this->dscRepository->getPriceFormat($order['shop_price']);

            //修正退货单状态
            if ($order['return_type'] == 0) {
                if ($order['return_status'] == 4) {
                    $order['refound_status'] = FF_MAINTENANCE;
                } else {
                    $order['refound_status'] = FF_NOMAINTENANCE;
                }
            } elseif ($order['return_type'] == 1) {
                if ($order['refound_status'] == 1) {
                    $order['refound_status'] = FF_REFOUND;
                } else {
                    $order['refound_status'] = FF_NOREFOUND;
                }
            } elseif ($order['return_type'] == 2) {
                if ($order['return_status'] == 4) {
                    $order['refound_status'] = FF_EXCHANGE;
                } else {
                    $order['refound_status'] = FF_NOEXCHANGE;
                }
            } elseif ($order['return_type'] == 3) {
                if ($order['refound_status'] == 1) {
                    $order['refound_status'] = FF_REFOUND;
                } else {
                    $order['refound_status'] = FF_NOREFOUND;
                }
            }
            $order['refound_status1'] = lang('user.ff.' . $order['refound_status']);

            /* 取得区域名 */
            $province = $order['get_region_province']['region_name'] ?? '';
            $city = $order['get_region_city']['region_name'] ?? '';
            $district = $order['get_region_district']['region_name'] ?? '';
            $street = $order['get_region_street']['region_name'] ?? '';
            $order['address_detail'] = $province . ' ' . $city . ' ' . $district . ' ' . $street . ' ' . $order['address'];

            $order['goods_thumb'] = $this->dscRepository->getImagePath($order['goods_thumb']);

            // 退换货原因
            $parent_id = ReturnCause::where('cause_id', $order['cause_id'])->value('parent_id');
            $parent = ReturnCause::where('cause_id', $parent_id)->value('cause_name');

            $child = ReturnCause::where('cause_id', $order['cause_id'])
                ->value('cause_name');
            if ($parent) {
                $order['return_cause'] = $parent . "-" . $child;
            } else {
                $order['return_cause'] = $child;
            }

            if ($order['return_status'] == REFUSE_APPLY) {
                $order['action_note'] = ReturnAction::where('ret_id', $order['ret_id'])
                    ->where('return_status', REFUSE_APPLY)
                    ->orderBy('log_time', 'DESC')
                    ->value('action_note');
            }

            if ($order['back_shipping_name']) {
                if ($order['back_shipping_name'] == '999') {
                    $order['back_shipp_shipping'] = $order['back_other_shipping'];
                } else {
                    $order['back_shipp_shipping'] = get_shipping_name($order['back_shipping_name']);
                }
            }
            if ($order['out_shipping_name']) {
                if ($order['out_shipping_name'] == '999') {
                    $order['out_shipp_shipping'] = '其他快递';
                } else {
                    $order['out_shipp_shipping'] = get_shipping_name($order['out_shipping_name']);
                }
            }

            //下单，商品单价
            $goods_price = OrderGoods::where('order_id', $order['order_id'])
                ->where('goods_id', $order['goods_id'])
                ->value('goods_price');
            $order['goods_price'] = $this->dscRepository->getPriceFormat($goods_price);

            // 取得退换货商品客户上传图片凭证
            $where = [
                'user_id' => $order['user_id'],
                'rec_id' => $order['rec_id']
            ];
            $order['img_list'] = $this->orderRefoundService->getReturnImagesList($where);

            $order['img_count'] = count($order['img_list']);

            //IM or 客服
            if ($this->config['customer_service'] == 0) {
                $ru_id = 0;
            } else {
                $ru_id = $order['ru_id'];
            }

            $shop_information = $this->merchantCommonService->getShopName($ru_id); //通过ru_id获取到店铺信息;
            $order['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : 0; //平台是否允许商家使用"在线客服";

            $order['shop_name'] = $this->merchantCommonService->getShopName($ru_id, 1);
        }

        return $order;
    }

    /**
     * 提交退换货
     *
     * @param int $user_id
     * @param array $rec_ids
     * @param string $last_option
     * @param string $return_remark
     * @param string $return_brief
     * @param int $chargeoff_status
     * @param array $info
     * @return array
     */
    public function submitReturn($user_id = 0, $rec_ids = [], $last_option = '', $return_remark = '', $return_brief = '', $chargeoff_status = 0, $info = [])
    {
        $rec_count = count($rec_ids); // 批量退换货
        $error = 0;

        foreach ($rec_ids as $rec_id) {
            if ($rec_id > 0) {
                $num = OrderReturn::where('rec_id', $rec_id)->count();
                if ($num > 0) {
                    $res = [
                        'code' => 1,
                        'msg' => $this->lang['Repeated_submission'],
                    ];
                    return $res;
                }
            } else {
                $res = [
                    'code' => 1,
                    'msg' => $this->lang['Return_abnormal'],
                ];
                return $res;
            }

            $order_goods = $this->getReturnOrderGoods($rec_id);

            if ($order_goods['user_id'] != $user_id) {
                return ['code' => 1, 'msg' => $this->lang['Apply_Abnormal']];
            }
            if ($rec_count > 1) {
                $return_number = empty($order_goods['goods_number']) ? 1 : intval($order_goods['goods_number']);
            } else {
                $return_number = empty($info['return_number']) ? 1 : intval($info['return_number']); //商品数量
                $return_number = $return_number > $order_goods['goods_number'] ? $order_goods['goods_number'] : $return_number;//最大不超过购买数量
            }

            $return_type = $info['return_type'] ?? 0; //退换货类型

            $maintain = 0;
            $return_status = 0;
            if ($return_type == 1) {
                $back = 1;
                $exchange = 0;
            } elseif ($return_type == 2) {
                $back = 0;
                $exchange = 2;
            } elseif ($return_type == 3) {
                $back = 0;
                $exchange = 0;
                $return_status = -1;
            } else {
                $back = 0;
                $exchange = 0;
            }

            // 贡云售后信息
            $jigonWhere = [
                'user_id' => $user_id,
                'rec_id' => $rec_id,
                'return_type' => $return_type,
                'return_number' => $return_number,
                'return_brief' => $return_brief,
                'type' => 'api',
            ];
            $aftersn = $this->jigonManageService->jigonAfterSales($jigonWhere);

            $attr_val = '';
            $return_attr_id = '';
            if ($rec_count == 1) {
                $attr_val = isset($info['attr_val']) ? $info['attr_val'] : []; //获取属性ID数组
                $return_attr_id = !empty($attr_val) ? implode(',', $attr_val) : '';
                // 换回商品属性
                $attr_val = get_goods_attr_info_new($attr_val, 'pice');
            }

            $order_return = [
                'rec_id' => $rec_id,
                'goods_id' => $order_goods['goods_id'],
                'order_id' => $order_goods['order_id'],
                'order_sn' => $order_goods['order_sn'],
                'chargeoff_status' => $chargeoff_status, // 账单 0 未结账 1 已出账 2 已结账单
                'return_type' => $return_type, //唯一标识
                'maintain' => $maintain, //维修标识
                'back' => $back, //退货标识
                'exchange' => $exchange, //换货标识
                'user_id' => $user_id,
                'goods_attr' => $order_goods['goods_attr'],   //换出商品属性
                'attr_val' => $attr_val,
                'return_brief' => $return_brief,
                'remark' => $return_remark ?? '',
                'credentials' => !isset($info['credentials']) ? 0 : intval($info['credentials']),
                'country' => !isset($info['country']) ? 0 : intval($info['country']),
                'province' => !isset($info['province']) ? 0 : intval($info['province']),
                'city' => !isset($info['city']) ? 0 : intval($info['city']),
                'district' => !isset($info['district']) ? 0 : intval($info['district']),
                'street' => !isset($info['street']) ? 0 : intval($info['street']),
                'cause_id' => $last_option, //退换货原因
                'apply_time' => $this->timeRepository->getGmTime(),
                'actual_return' => '',
                'address' => !isset($info['return_address']) ? '' : addslashes(trim($info['return_address'])),
                'zipcode' => !isset($info['code']) ? '' : intval($info['code']),
                'addressee' => !isset($info['addressee']) ? '' : addslashes(trim($info['addressee'])),
                'phone' => !isset($info['mobile']) ? '' : addslashes(trim($info['mobile'])),
                'return_status' => $return_status,
                'goods_bonus' => $order_goods['goods_bonus'],
                'goods_coupons' => $order_goods['goods_coupons']
            ];

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $order_return['return_rate_price'] = $order_goods['rate_price'] / $order_goods['goods_number'] * $return_number;
            }

            // 1 退货、3 退款
            if (in_array($return_type, [1, 3])) {
                $return_info = get_return_refound($order_return['order_id'], $order_return['rec_id'], $return_number);
                $order_return['should_return'] = $return_info['return_price'];
                $order_return['return_shipping_fee'] = $return_info['return_shipping_fee'];
            } else {
                $order_return['should_return'] = 0;
                $order_return['return_shipping_fee'] = 0;
            }

            // 订单退款 不退开通会员权益卡购买金额
            if (file_exists(MOBILE_DRP) && $order_return['should_return'] > 0) {
                $order_return['should_return'] = $order_return['should_return'] - $order_goods['membership_card_discount_price'] / $order_goods['goods_number'] * $return_number;
            }

            /* 插入订单表 */
            $order_return['return_sn'] = $this->orderCommonService->getOrderSn(); //获取新订单号

            $ret_id = OrderReturn::insertGetId($order_return);

            if ($ret_id) {

                app(OrderCommonService::class)->getUserOrderNumServer($user_id);

                /* 记录log */
                return_action($ret_id, $this->lang['Apply_refund'], 0, $order_return['remark'], $this->lang['buyer']);

                $return_goods['rec_id'] = $order_return['rec_id'];
                $return_goods['ret_id'] = $ret_id;
                $return_goods['goods_id'] = $order_goods['goods_id'];
                $return_goods['goods_name'] = $order_goods['goods_name'];
                $return_goods['brand_name'] = $order_goods['brand_name'];
                $return_goods['product_id'] = $order_goods['product_id'];
                $return_goods['goods_sn'] = $order_goods['goods_sn'];
                $return_goods['is_real'] = $order_goods['is_real'];
                $return_goods['goods_attr'] = $attr_val;  //换货的商品属性名称
                $return_goods['attr_id'] = $return_attr_id; //换货的商品属性ID值
                $return_goods['refound'] = $order_goods['goods_price'];

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $return_goods['rate_price'] = $order_goods['rate_price'] / $order_goods['goods_number'];
                }

                // 订单退款 不退开通会员权益卡购买金额
                if (file_exists(MOBILE_DRP)) {
                    $return_goods['membership_card_discount_price'] = $order_goods['membership_card_discount_price'];
                }

                //添加到退换货商品表中
                $return_goods['return_type'] = $return_type; //退换货
                $return_goods['return_number'] = $return_number; //退换货数量

                if ($return_type == 1) { //退货
                    $return_goods['out_attr'] = '';
                } elseif ($return_type == 2) { //换货
                    $return_goods['out_attr'] = $attr_val;
                    $return_goods['return_attr_id'] = $return_attr_id;
                } else {
                    $return_goods['out_attr'] = '';
                }

                ReturnGoods::insert($return_goods);

                // 保存退换货图片
                if (isset($info['return_images']) && !empty($info['return_images'])) {
                    $time = $this->timeRepository->getGmTime();
                    foreach ($info['return_images'] as $k => $v) {
                        if (stripos(substr($v, 0, 4), 'http') !== false) {
                            $v = str_replace(asset('/'), '', $v);
                        }
                        $img_file = str_replace('storage/', '', ltrim($v, '/'));
                        $data = [
                            'rec_id' => $rec_id,
                            'rg_id' => $order_goods['goods_id'],
                            'user_id' => $user_id,
                            'img_file' => $img_file,
                            'add_time' => $time
                        ];
                        ReturnImages::insert($data);
                    }
                }

                //退货数量插入退货表扩展表  by kong
                $order_return_extend = [
                    'ret_id' => $ret_id,
                    'return_number' => $return_number,
                    'aftersn' => $aftersn
                ];

                OrderReturnExtend::insert($order_return_extend);

                $address_detail = $order_goods['region'] . ' ' . $order_return['address'];
                $order_return['address_detail'] = $address_detail;
                $order_return['apply_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $order_return['apply_time']);
            } else {
                $error += 1; // 异常提交申请的数量
            }
        }

        if ($error > 0) {
            return [
                'code' => 1,
                'msg' => $this->lang['Apply_abnormal'],
            ];
        } else {
            return [
                'code' => 0,
                'msg' => $this->lang['Apply_Success_Prompt'],
            ];
        }
    }

    /**
     * 在线退款查询（第三方在线支付 微信支付）
     *
     * @param string $return_sn
     * @return bool
     */
    public function refundCheck($return_sn = '')
    {
        if (empty($return_sn)) {
            return false;
        }

        /**
         * 已支付 未退款的 可以手动查询、 退款申请时有异步通知
         */
        $model = OrderReturn::where('return_sn', $return_sn);
        $model = $model->with([
            'orderInfo' => function ($query) {
                $query->select('order_id', 'order_sn', 'pay_id', 'pay_status', 'money_paid', 'referer');
            }
        ]);
        $model = $model->first();
        $return_order = $model ? $model->toArray() : [];

        if ($return_order) {
            $return_order = collect($return_order)->merge($return_order['order_info'])->except('order_info')->all();
            if ($return_order['pay_status'] == PS_PAYED && $return_order['refound_status'] == 0) {
                $payment_info = payment_info($return_order['pay_id']);

                if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                    $payObject = $this->commonRepository->paymentInstance($payment_info['pay_code']);
                    if (!is_null($payObject) && is_callable([$payObject, 'refundQuery'])) {
                        // 退款查询参数 $return_order['order_id']

                        $res = $payObject->refundQuery($return_order);
                        if ($res) {
                            // 查询退款状态 如果退款成功 则更新退款订单、退换货订单状态为 已退款
                            $this->refund_paid($return_order['order_sn'], 2, lang('order.return_online'), $return_order['should_return']);
                        }
                    }
                }
            }
        }
    }

    /**
     * 退换货订单商品
     * @param int $rec_id
     * @return array
     */
    protected function getReturnOrderGoods($rec_id = 0)
    {
        $model = OrderGoods::where('rec_id', $rec_id);

        $model = $model->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'goods_sn', 'brand_id');
            },
            'getOrder' => function ($query) {
                $query = $query->select('order_id', 'order_sn', 'user_id', 'consignee', 'mobile', 'country', 'province', 'city', 'district', 'street');
                $query->with([
                    'getRegionProvince' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionCity' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionDistrict' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionStreet' => function ($query) {
                        $query->select('region_id', 'region_name');
                    }
                ]);
            }
        ]);

        $res = $model->first();

        $res = $res ? $res->toArray() : [];

        if ($res) {

            /* 取得区域名 */
            $province = $res['get_order']['get_region_province']['region_name'] ?? '';
            $city = $res['get_order']['get_region_city']['region_name'] ?? '';
            $district = $res['get_order']['get_region_district']['region_name'] ?? '';
            $street = $res['get_order']['get_region_street']['region_name'] ?? '';
            $res['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

            $res = collect($res)->merge($res['get_goods'])->except('get_goods')->all();
            $res = collect($res)->merge($res['get_order'])->except('get_order')->all();

            $res['brand_name'] = isset($res['brand_id']) ? Brand::where('brand_id', $res['brand_id'])->value('brand_name') : '';
        }

        return $res;
    }

    /**
     * 编辑退换货快递信息
     * @param $user_id
     * @param $info
     * @return array
     */
    public function editExpress($user_id, $info)
    {
        $ret_id = empty($info['ret_id']) ? '' : intval($info['ret_id']);
        if ($ret_id) {
            $back_shipping_name = empty($info['shipping_id']) ? '' : $info['shipping_id']; // 配送id
            $back_other_shipping = empty($info['express_name']) ? '' : $info['express_name'];
            $back_invoice_no = empty($info['express_sn']) ? '' : $info['express_sn'];

            $other = [
                'back_shipping_name' => $back_shipping_name,
                'back_other_shipping' => $back_other_shipping,
                'back_invoice_no' => $back_invoice_no
            ];
            OrderReturn::where('ret_id', $ret_id)->where('user_id', $user_id)->update($other);

            return [
                'code' => 0,
                'msg' => $this->lang['edit_shipping_success'],
            ];
        } else {
            return ['code' => 1];
        }
    }

    /**
     * 取消退换货
     *
     * @param $user_id
     * @param $ret_id
     * @return array|bool
     */
    public function cancle_return($user_id, $ret_id)
    {
        // 退换货订单信息
        $order = OrderReturn::where('ret_id', $ret_id)
            ->where('user_id', $user_id)
            ->first();

        $order = $order ? $order->toArray() : [];

        if (is_null($order)) {
            return [];
        }
        // 如果用户ID大于0，检查订单是否属于该用户
        if ($user_id > 0 && $order['user_id'] != $user_id) {
            return ['code' => 1, 'msg' => $this->lang['no_priv']];
        }
        // 订单状态只能是用户寄回和未退款状态
        if ($order['return_status'] != RF_APPLICATION && $order['refound_status'] != FF_NOREFOUND) {
            return ['code' => 1, 'msg' => $this->lang['return_not_unconfirmed']];
        }
        //一旦由商家收到退换货商品，不允许用户取消
        if ($order['return_status'] == RF_RECEIVE) {
            return ['code' => 1, 'msg' => $this->lang['current_os_already_receive']];
        }
        // 商家已发送退换货商品
        if ($order['return_status'] == RF_SWAPPED_OUT_SINGLE || $order['return_status'] == RF_SWAPPED_OUT) {
            return ['code' => 1, 'msg' => $this->lang['already_out_goods']];
        }
        // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
        if ($order['refound_status'] == FF_REFOUND) {
            return ['code' => 1, 'msg' => $this->lang['have_refound']];
        }

        //将用户订单设置为取消
        OrderReturn::where('ret_id', $ret_id)->where('user_id', $user_id)->delete();

        // 删除退换货商品
        ReturnGoods::where('rec_id', $order['rec_id'])->delete();

        $where = [
            'user_id' => $order['user_id'],
            'rec_id' => $order['rec_id']
        ];
        $img_list = $this->orderRefoundService->getReturnImagesList($where);

        if ($img_list) {
            // 删除退换货图片
            foreach ($img_list as $ikey => $row) {
                @unlink(storage_public($row['img_file']));
            }
            ReturnImages::where('user_id', $user_id)
                ->where('rec_id', $order['rec_id'])
                ->delete();
        }
        /* 删除扩展记录  by kong */
        OrderReturnExtend::where('ret_id', $ret_id)->delete();

        /* 记录log */
        return_action($ret_id, $this->lang['cancel'], '', $this->lang['cancel'], $this->lang['buyer'], '');

        return ['code' => 0, 'msg' => 'success'];
    }

    /**
     * 退换货订单确认收货
     */
    public function AffirmReceivedOrderReturn($user_id, $ret_id = 0)
    {
        $data = ['return_status' => 4];
        $res = OrderReturn::where('user_id', $user_id)->where('ret_id', $ret_id)->update($data);

        if ($res) {
            /* 记录log */
            return_action($ret_id, $this->lang['received'], '', $this->lang['received'], $this->lang['buyer']);

            return ['code' => 0, 'msg' => $this->lang['update_Success']];
        }
        return ['code' => 1, 'msg' => 'fail'];
    }

    /**
     * 激活退换货订单
     *
     * @param $user_id
     * @param int $ret_id
     * @return mixed
     */
    public function ActivationReturnOrder($user_id, $ret_id = 0)
    {
        $active_num = $this->config['activation_number_type'];
        $activation_number_type = ($active_num > 0) ? $active_num : 2;

        $activation_number = OrderReturn::where(['ret_id' => $ret_id, 'user_id' => $user_id])->value('activation_number');

        if ($activation_number_type > $activation_number) {
            $model = OrderReturn::where('ret_id', $ret_id)->where('user_id', $user_id)->first();

            $model->return_status = 0;
            $model->activation_number = $activation_number + 1;
            $model->save();

            return ['code' => 0, 'msg' => $this->lang['update_Success']];
        } else {
            return ['code' => 1, 'msg' => sprintf($this->lang['activation_number_msg'], $activation_number_type)];
        }
    }

    /**
     * 删除已完成退换货订单
     */
    public function DeleteReturnOrder($user_id, $ret_id = 0)
    {
        if ($ret_id > 0) {
            // 删除退换货订单
            OrderReturn::where(['ret_id' => $ret_id, 'user_id' => $user_id])->delete();
            // 删除退换货商品
            ReturnGoods::where('ret_id', $ret_id)->delete();

            /* 删除扩展记录  by kong */
            OrderReturnExtend::where('ret_id', $ret_id)->delete();
            return ['code' => 0, 'msg' => $this->lang['delete_success']];
        }

        return ['code' => 1, 'msg' => 'fail'];
    }

    /**
     * 退换货原因
     * @param int $parent_id
     * @return array
     */
    protected function getReturnCause($parent_id = 0, $level = 0)
    {
        $res = ReturnCause::where('parent_id', $parent_id)
            ->where('is_show', 1)
            ->orderBy('sort_order')
            ->get();

        $res = $res ? $res->toArray() : [];

        $three_arr = [];
        foreach ($res as $k => $row) {
            $three_arr[$k]['cause_id'] = $row['cause_id'];
            $three_arr[$k]['cause_name'] = $row['cause_name'];
            $three_arr[$k]['parent_id'] = $row['parent_id'];
            $three_arr[$k]['haschild'] = 0;

            $three_arr[$k]['level'] = $level;
            //$three_arr[$k]['select'] = str_repeat('&nbsp;', $three_arr[$k]['level'] * 4);

            if (isset($row['cause_id']) && $level > 0) {
                $child_tree = $this->getReturnCause($row['cause_id'], $level + 1);
                if ($child_tree) {
                    $three_arr[$k]['child_tree'] = $child_tree;
                    $three_arr[$k]['haschild'] = 1;
                }
            }
        }

        return $three_arr;
    }

    /**
     * 订单退款(在线支付自动处理 不经过后台操作)
     * @param string $order_sn 订单号
     * @param int $action_type 操作方式 1 同意申请 agree_apply = 1  2. 更新退款状态 refound_status = 1
     * @param string $refund_note 退款说明
     * @param float $refund_amount 退款金额（如果为0，取订单已付款金额）
     * @return
     */
    public function refund_paid($order_sn, $action_type = 0, $refund_note = '', $refund_amount = 0, $vc_id = 0, $refound_vcard = 0)
    {
        $return_order = return_order_info(0, $order_sn);
        $order_id = $return_order['order_id'] ?? 0;

        $order = OrderInfo::where('order_id', $order_id)->first();
        $order = $order ? $order->toArray() : [];
        if (is_null($order)) {
            return false;
        }

        /* 检查参数 */
        $ret_id = $return_order['ret_id'];
        $rec_id = $return_order['rec_id'];
        $user_id = $return_order['user_id'] ?? 0;
        $order_id = $return_order['order_id'];

        if ($user_id == 0) {
            return ['status' => 1, 'msg' => 'anonymous, cannot return to account balance'];
        }

        $amount = $refund_amount > 0 ? $refund_amount : $order['money_paid'];

        if ($action_type == 2) {
            $amount = $return_order['should_return1'] ?? 0;
        }

        if ($amount <= 0) {
            return true;
        }

        /* 备注信息 */
        if ($refund_note) {
            $change_desc = $refund_note;
        } else {
            $change_desc = sprintf('订单退款：%s', $return_order['order_sn']);
        }

        /* 处理退款 */
        if (1 == $action_type) {
            // 同意申请 更新标记order_return 表
            OrderReturn::where('rec_id', $return_order['rec_id'])->update(['agree_apply' => 1]);

            /* 记录log TODO_LOG */
            return_action($ret_id, RF_AGREE_APPLY, '', $change_desc, lang('payment.buyer'));

            return true;
        } elseif (2 == $action_type) {
            /*  ------------参考 PC 后台admin/order.php 退款部分 start------------------ */
            load_helper('publicfunc');

            $admin_id = AdminUser::where(['parent_id' => 0, 'ru_id' => 0])->value('user_id'); // 默认平台管理员ID

            // 退还
            $order_goods = order_goods($return_order['order_id']);  //订单商品

            // 判断退货单订单中是否只有一个商品   如果只有一个则退订单的全部积分   如果多个则按商品积分的比例来退  by kong
            if (count($order_goods) > 1) {
                foreach ($order_goods as $k => $v) {
                    $all_goods_id[] = $v['goods_id'];
                }

                //获取该订单商品的全部可用积分
                $count_integral = Goods::whereIn('goods_id', $all_goods_id)->sum('integral');
                //退货商品的可用积分
                $model = OrderReturn::where('ret_id', $ret_id)
                    ->with([
                        'getGoods' => function ($query) {
                            $query->select('goods_id', 'integral');
                        }
                    ])->first();

                $result = $model ? $model->toArray() : [];
                if ($result) {
                    $result = collect($result)->merge($result['get_goods'])->except('get_goods')->all();
                }

                $return_integral = $result['integral'] ?? 0;
                $count_integral = !empty($count_integral) ? $count_integral : 1;
                $return_ratio = $return_integral / $count_integral; //退还积分比例
                $return_price = (empty($order['pay_points']) ? '' : $order['pay_points']) * $return_ratio;//那比例最多返还的积分
            } else {
                $return_price = empty($order['pay_points']) ? '' : $order['pay_points'];//by kong 赋值支付积分
            }

            //获取该商品的订单数量
            $goods_number = OrderGoods::where('rec_id', $rec_id)->value('goods_number');
            //获取退货数量
            $return_number = OrderReturnExtend::where('ret_id', $ret_id)->value('return_number');

            //*如果退货数量小于订单商品数量   则按比例返还*/
            if ($return_number < $goods_number) {
                $refound_pay_points = intval($return_price * ($return_number / $goods_number));
            } else {
                $refound_pay_points = intval($return_price);
            }

            //退款运费金额
            $is_shipping_money = false; // 默认不退运费
            $shippingFee = ($is_shipping_money && isset($order['shipping_fee'])) ? $order['shipping_fee'] : 0;

            /* todo 处理退款 */
            if ($order['pay_status'] != PS_UNPAYED) {
                $return_goods = get_return_order_goods1($rec_id); //退换货商品
                $return_info = return_order_info($ret_id);        //退换货订单
                // $order_goods = get_order_goods($order);             //订单商品

                $refund_amount = $amount + $shippingFee;

                /* 标记订单为“退货”、“未付款”、“未发货” */
                $get_order_arr = get_order_arr($return_info['return_number'], $return_info['rec_id'], $order_goods, $order);
                update_order($order['order_id'], $get_order_arr);

                //退款 标记order_return 表
                $return_status = [
                    'refound_status' => 1,
                    'agree_apply' => 1,
                    'actual_return' => $refund_amount,
                    'return_shipping_fee' => $shippingFee,
                    'refund_type' => 6,
                    'return_time' => $this->timeRepository->getGmTime()
                ];
                OrderReturn::where('rec_id', $rec_id)->update($return_status);

                //原商品销量减去退款数量
                Goods::where('goods_id', $return_goods['goods_id'])->where('sales_volume', '>=', $return_info['return_number'])->decrement('sales_volume', $return_info['return_number']);

                //退款更新账单
                SellerBillOrder::where('order_id', $order_id)->increment('return_amount', $refund_amount, ['return_shippingfee' => $shippingFee]);

                // 更新订单操作记录log
                order_action($order['order_sn'], OS_RETURNED_PART, $order['shipping_status'], $order['pay_status'], $change_desc, $admin_id);
            }

            $is_whole = 0;
            $is_diff = get_order_return_rec($order_id);
            if ($is_diff) {
                //整单退换货
                $return_count = return_order_info_byId($order_id, false);
                if ($return_count == 1) {
                    //退还红包
                    if (isset($order['bonus']) && $order['bonus']) {
                        UserBonus::where('order_id', $order_id)->update(['used_time' => '', 'order_id' => '']);
                    }

                    /*  @author-bylu 退还优惠券 start */
                    unuse_coupons($order_id);

                    $is_whole = 1;
                }
            }

            /*判断是否需要退还积分  如果需要 则跟新退还日志   by kong*/
            if ($refound_pay_points > 0) {
                log_account_change($return_order['user_id'], 0, 0, 0, $refound_pay_points, lang('order.order_return_prompt') . $return_order['order_sn'] . lang('order.buy_integral'));
            }

            if ($is_whole == 1) {
                return_card_money($order_id, $ret_id, $return_order['return_sn']);
            } else {
                /* 退回订单消费储值卡金额 */
                $vc_id = isset($vc_id) ? $vc_id : 0;
                $refound_vcard = isset($refound_vcard) ? $refound_vcard : 0;
                get_return_vcard($order_id, $vc_id, $refound_vcard, $return_order['return_sn'], $ret_id);
            }

            /*  退回订单赠送的积分*/
            return_integral_rank($ret_id, $return_order['user_id'], $return_order['order_sn'], $rec_id, $refound_pay_points);

            /* 如果使用库存，则增加库存（不论何时减库存都需要） */
            if ($this->config['use_storage'] == '1') {
                if ($this->config['stock_dec_time'] == SDT_SHIP) {
                    change_order_goods_storage($order_id, false, SDT_SHIP, 6, $admin_id);
                } elseif ($this->config['stock_dec_time'] == SDT_PLACE) {
                    change_order_goods_storage($order_id, false, SDT_PLACE, 6, $admin_id);
                } elseif ($this->config['stock_dec_time'] == SDT_PAID) {
                    change_order_goods_storage($order_id, false, SDT_PAID, 6, $admin_id);
                }
            }

            /* 更新退换货订单操作记录log */
            return_action($ret_id, RF_COMPLETE, FF_REFOUND, $refund_note, lang('payment.buyer'));

            /*  ------------参考 PC 后台admin/order.php 退款部分 end------------------ */
            return true;
        } else {
            return true;
        }
    }

    /**
     * 通过user_id获取用户购买的第一个分销商商品的rec_id
     * @param int $user_id
     * @return int
     */
    public function getOrderGoodsDrp($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }
        //buy_drp_show 升级分销商商品标记
        $model = OrderGoods::where('buy_drp_show', 1);
        $model = $model->whereHas('getOrder', function ($query) {
            $query->where('pay_status', PS_PAYED);
        });
        $model = $model->where('user_id', $user_id);
        $res = $model->orderBy('rec_id', 'asc')->value('rec_id');
        return $res ?? 0;
    }
}
