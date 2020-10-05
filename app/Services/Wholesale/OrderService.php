<?php

namespace App\Services\Wholesale;

use App\Libraries\Pager;
use App\Models\Payment;
use App\Models\ReturnCause;
use App\Models\SellerShopinfo;
use App\Models\Suppliers;
use App\Models\Wholesale;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleOrderReturn;
use App\Models\WholesaleOrderReturnExtend;
use App\Models\WholesaleReturnAction;
use App\Models\WholesaleReturnGoods;
use App\Models\WholesaleReturnImages;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class OrderService
{
    protected $baseRepository;
    protected $wholesaleService;
    protected $dscRepository;
    protected $config;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $orderManageService;

    public function __construct(
        BaseRepository $baseRepository,
        WholesaleService $wholesaleService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        OrderManageService $orderManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->wholesaleService = $wholesaleService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderManageService = $orderManageService;
    }

    /**
     * 获取成交量
     *
     * @param int $goods_id
     * @return mixed
     */
    public function getGoodsOrderSale($goods_id = 0)
    {
        $res = WholesaleOrderGoods::where('goods_id', $goods_id);

        $res = $res->whereHas('getWholesaleOrderInfo', function ($query) {
            $query->whereHas('getMainOrderId', function ($query) {
                $query->selectRaw("count(*) as count")
                    ->Having('count', 0)
                    ->where('is_delete', 0);
            });
        });

        return $res->sum('goods_number');
    }

    /**
     * 确认一个用户订单
     *
     * @access  public
     * @param int $order_id 订单ID
     * @param int $user_id 用户ID
     *
     * @return  bool        $bool
     */
    public function wholesaleAffirmReceived($order_id, $user_id = 0)
    {
        /* 查询订单信息，检查状态 */
        $order = WholesaleOrderInfo::where('order_id', $order_id);
        $order = $this->baseRepository->getToArrayFirst($order);

        if (empty($order)) {
            return false;
        }

        // 如果用户ID大于 0 。检查订单是否属于该用户
        if ($user_id > 0 && $order['user_id'] != $user_id) {
            return false;
        } /* 检查订单 */
        elseif ($order['order_status'] == OS_CONFIRMED) {
            return false;
        } elseif ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_RETURNED || $order['order_status'] == OS_RETURNED_PART) {
            $res = WholesaleOrderInfo::where('order_id', $order_id)
                ->update([
                    'order_status' => OS_CONFIRMED
                ]);

            if ($res) {
                /* 记录日志 */
                $this->wholesaleService->wholesaleOrderAction($order['order_sn'], $order['order_status'], OS_CONFIRMED, $order['pay_status'], $GLOBALS['_LANG']['buyer']);
            }

            return true;
        }
    }

    /**
     *  获取订单里某个商品 信息 BY  Leah
     * @param type $rec_id
     * @return type
     */
    public function wholesaleRecGoods($rec_id)
    {
        $res = WholesaleOrderGoods::where('rec_id', $rec_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        if ($res) {
            $res['goods_price1'] = $res['goods_price'];
            $subtotal = $res['goods_price'] * $res['goods_number'];
            $res['goods_price'] = $this->dscRepository->getPriceFormat($res['goods_price'], false);
            $res['subtotal'] = $this->dscRepository->getPriceFormat($subtotal, false);

            $goods = Wholesale::where('goods_id', $res['goods_id']);
            $goods = $this->baseRepository->getToArrayFirst($goods);

            if ($goods) {
                $suppliers_info = Suppliers::where('suppliers_id', $goods['suppliers_id']);
                $suppliers_info = $this->baseRepository->getToArrayFirst($suppliers_info);

                $goods['user_id'] = $suppliers_info['user_id'] ?? 0;
                $res['suppliers_name'] = $suppliers_info['suppliers_name'] ?? '';

                $shopInfo = $this->merchantCommonService->getShopName($goods['user_id']);
                $res['user_name'] = $shopInfo['shop_name'] ?? '';

                $chat = $this->dscRepository->chatQq($shopInfo);
                $res['kf_type'] = $chat['kf_type'];
                $res['kf_qq'] = $chat['kf_qq'];
                $res['kf_ww'] = $chat['kf_ww'];

                /* 修正商品图片 */
                $res['goods_img'] = $this->dscRepository->getImagePath($goods['goods_img']);
                $res['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
            }

            $res['url'] = $this->dscRepository->buildUri('wholesale_goods', ['aid' => $res['goods_id']], $res['goods_name']);
        }

        return $res;
    }

    /**
     * 取得用户退换货商品
     * by  leah
     */
    public function wholesaleReturnOrder($size = 0, $start = 0)
    {
        $activation_number_type = (intval($this->config['activation_number_type']) > 0) ? intval($this->config['activation_number_type']) : 2;

        $user_id = session('user_id', 0);
        $res = WholesaleOrderReturn::where('user_id', $user_id);

        $res = $res->with([
            'getWholesale'
        ]);

        $res = $res->orderBy('ret_id', 'desc');

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $goods_list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $wholesale = $row['get_wholesale'];

                $row['goods_thumb'] = $wholesale['goods_thumb'];
                $row['goods_thumb'] = $row['goods_thumb'] ? $this->dscRepository->getImagePath($row['goods_thumb']) : '';

                $row['apply_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['apply_time']);
                $row['should_return'] = $this->dscRepository->getPriceFormat($row['should_return'], false);
                $row['edit_shipping'] = '<a href="user.php?act=return_detail&ret_id=' . $row['ret_id'] . "&order_id=" . $row['order_id'] . '" style="margin-left:5px;" >查看</a>';

                $row['order_status'] = '';
                if ($row['return_status'] == 0 && $row['refound_status'] == 0) {
                    //  提交退换货后的状态 由用户寄回
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['user_return'] . "</span>";
                    $row['handler'] = '<a href="user.php?act=cancel_return&ret_id=' . $row['ret_id'] . '" style="margin-left:5px;" onclick="if (!confirm(' . "'你确认取消该退换货申请吗？'" . ')) return false;"  >取消</a>';
                } elseif ($row['return_status'] == 1) {
                    //退换商品收到
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['get_goods'] . "</span>";
                } elseif ($row['return_status'] == 2) {
                    //换货商品寄出 （分单）
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['send_alone'] . "</span>";
                } elseif ($row['return_status'] == 3) {
                    //换货商品寄出
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['send'] . "</span>";
                } elseif ($row['return_status'] == 4) {
                    //完成
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['complete'] . "</span>";
                } elseif ($row['return_status'] == 6) {
                    //被拒
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['rf'][$row['return_status']] . "</span>";
                }

                //维修-退款-换货状态
                if ($row['return_type'] == 0) {
                    if ($row['return_status'] == 4) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_MAINTENANCE];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOMAINTENANCE];
                    }
                } elseif ($row['return_type'] == 1) {
                    if ($row['refound_status'] == 1) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_REFOUND];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOREFOUND];
                    }
                } elseif ($row['return_type'] == 2) {
                    if ($row['return_status'] == 4) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_EXCHANGE];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOEXCHANGE];
                    }
                } elseif ($row['return_type'] == 3) {
                    if ($row['refound_status'] == 1) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_REFOUND];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOREFOUND];
                    }
                }

                $row['activation_type'] = 0;

                //判断是否支持激活
                if ($row['return_status'] == 6) {
                    if ($row['activation_number'] < $activation_number_type) {
                        $row['activation_type'] = 1;
                    }
                }

                $goods_list[] = $row;
            }
        }

        return $goods_list;
    }

    /**
     * 退货单信息
     * by  leah
     */
    public function wholesaleReturnOrderInfo($ret_id = 0, $order_sn = '', $order_id = 0)
    {
        $ret_id = intval($ret_id);
        if ($ret_id > 0) {
            $order = WholesaleOrderReturn::whereRaw(1);

            $order = $order->with([
                'getWholesaleReturnGoods',
                'getWholesale',
                'getWholesaleOrderInfo' => function ($query) {
                    $query->with([
                        'getWholesaleDeliveryOrder',
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

            if ($ret_id > 0) {
                $order = $order->where('ret_id', $ret_id);
            } else {
                if ($order_id) {
                    $order = $order->where('order_id', $order_id);
                } else {
                    $order = $order->where('order_sn', $order_sn);
                }
            }

            $order = $this->baseRepository->getToArrayFirst($order);

            if ($order) {
                $wholesale = $order['get_wholesale'];
                $order['goods_thumb'] = isset($wholesale['goods_thumb']) ? $this->dscRepository->getImagePath($wholesale['goods_thumb']) : '';
                $order['goods_name'] = $wholesale['goods_name'] ?? '';
                $order['goods_price'] = $wholesale['goods_price'] ?? '';
                $order['suppliers_id'] = $wholesale['suppliers_id'] ?? '';

                $wholesale_order = $order['get_wholesale_order_info'];
                $order['order_sn'] = $wholesale_order['order_sn'] ?? '';
                $order['add_time'] = $wholesale_order['add_time'] ?? 0;
                $wholesale_order['chargeoff_status'] = $wholesale_order['chargeoff_status'] ?? 0;

                if ($order['chargeoff_status'] == 0) {
                    $order['chargeoff_status'] = $wholesale_order['chargeoff_status'];
                }

                $return_goods = $order['get_wholesale_return_goods'];
                $order['return_number'] = $return_goods['return_number'] ?? 0;

                $deliveryOrder = $wholesale_order['get_wholesale_delivery_order'] ?? [];
                $order['delivery_sn'] = $deliveryOrder['delivery_sn'] ?? '';
                $order['update_time'] = $deliveryOrder['update_time'] ?? 0;
                $order['how_oos'] = $deliveryOrder['how_oos'] ?? '';
                $order['shipping_fee'] = $deliveryOrder['shipping_fee'] ?? 0;
                $order['insure_fee'] = $deliveryOrder['insure_fee'] ?? 0;
                $order['invoice_no'] = $deliveryOrder['invoice_no'] ?? '';

                $suppliers_info = Suppliers::where('suppliers_id', $order['suppliers_id']);
                $suppliers_info = $this->baseRepository->getToArrayFirst($suppliers_info);
                $order['ru_id'] = $suppliers_info['user_id'] ?? 0;
                $order['suppliers_name'] = $suppliers_info['suppliers_name'] ?? '';

                $order['attr_val'] = unserialize($order['attr_val']);
                $order['apply_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['apply_time']);
                $order['formated_update_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['update_time']);
                $order['formated_return_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['return_time']);
                $order['formated_add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['add_time']);
                $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;
                $order['discount_amount'] = $order['discount_amount'] ?? 0; //折扣金额
                $order['should_return1'] = number_format($order['should_return'] - $order['discount_amount'], 2, '.', '');
                $order['formated_goods_amount'] = $this->dscRepository->getPriceFormat($order['should_return'], false);
                $order['formated_discount_amount'] = $this->dscRepository->getPriceFormat($order['discount_amount'], false);
                $order['formated_should_return'] = $this->dscRepository->getPriceFormat($order['should_return'] - $order['discount_amount'], false);
                $order['formated_return_shipping_fee'] = $this->dscRepository->getPriceFormat($order['return_shipping_fee'], false);
                $order['formated_return_amount'] = $this->dscRepository->getPriceFormat($order['should_return'] + $order['return_shipping_fee'] - $order['discount_amount'], false);
                $order['formated_actual_return'] = $this->dscRepository->getPriceFormat($order['actual_return'], false);
                $order['return_status1'] = $order['return_status'];
                if ($order['return_status'] < 0) {
                    $order['return_status'] = $GLOBALS['_LANG']['only_return_money'];
                } else {
                    $order['return_status'] = $GLOBALS['_LANG']['rf'][$order['return_status']];
                }
                $order['refound_status1'] = $order['refound_status'];
                $order['goods_price'] = $this->dscRepository->getPriceFormat($order['goods_price'], false);
                $order['refound_status'] = $GLOBALS['_LANG']['ff'][$order['refound_status']];

                /* 取得区域名 */
                $province = $order['get_wholesale_order_info']['get_region_province']['region_name'] ?? '';
                $city = $order['get_wholesale_order_info']['get_region_city']['region_name'] ?? '';
                $district = $order['get_wholesale_order_info']['get_region_district']['region_name'] ?? '';
                $street = $order['get_wholesale_order_info']['get_region_street']['region_name'] ?? '';
                $order['address_detail'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

                $parent_id = ReturnCause::where('cause_id', $order['cause_id'])->value('parent_id');
                $parent = ReturnCause::where('cause_id', $parent_id)->value('cause_name');
                $child = ReturnCause::where('cause_id', $order['cause_id'])->value('cause_name');
                $order['return_cause'] = $parent . " " . $child;

                if ($order['return_status1'] == REFUSE_APPLY) {
                    $order['action_note'] = WholesaleReturnAction::where('ret_id', $order['ret_id'])
                        ->where('return_status', REFUSE_APPLY)
                        ->orderBy('log_time', 'desc')
                        ->value('action_note');
                }

                if (!empty($order['back_other_shipping'])) {
                    $order['back_shipp_shipping'] = $order['back_other_shipping'];
                } else {
                    $order['back_shipp_shipping'] = get_shipping_name($order['back_shipping_name']);
                }

                if ($order['out_shipping_name']) {
                    $order['out_shipp_shipping'] = get_shipping_name($order['out_shipping_name']);
                }

                //下单，商品单价
                $goods_price = WholesaleOrderGoods::where('order_id', $order['order_id'])
                    ->where('goods_id', $order['goods_id'])
                    ->value('goods_price');
                $goods_price = $goods_price ? $goods_price : 0;

                $order['goods_price'] = $this->dscRepository->getPriceFormat($goods_price, false);

                // 取得退换货商品客户上传图片凭证
                $img_list = WholesaleReturnImages::where('user_id', $order['user_id'])
                    ->where('rec_id', $order['rec_id'])
                    ->orderBy('id', 'desc');
                $img_list = $this->baseRepository->getToArrayGet($img_list);

                if ($img_list) {
                    foreach ($img_list as $key => $val) {
                        $img_list[$key]['img_file'] = $this->dscRepository->getImagePath($val['img_file']);
                    }
                }

                $order['img_list'] = $img_list;
                $order['img_count'] = count($img_list);

                $order['url'] = $this->dscRepository->buildUri('wholesale_goods', ['aid' => $order['goods_id']], $order['goods_name']);

                //IM or 客服
                if ($this->config['customer_service'] == 0) {
                    $ru_id = 0;
                } else {
                    $ru_id = $order['ru_id'];
                }

                $shop_information = $this->merchantCommonService->getShopName($ru_id); //通过ru_id获取到店铺信息;
                $order['is_IM'] = $shop_information['is_IM'] ?? 0; //平台是否允许商家使用"在线客服";

                $order['shop_name'] = $this->merchantCommonService->getShopName($ru_id, 1);
                $order['shop_url'] = $this->dscRepository->buildUri('merchants_store', array('urid' => $ru_id), $order['shop_name']);

                if ($ru_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->count();

                    if ($kf_im_switch > 0) {
                        $order['is_dsc'] = true;
                    } else {
                        $order['is_dsc'] = false;
                    }
                } else {
                    $order['is_dsc'] = false;
                }

                $order['ru_id'] = $ru_id;

                $basic_info = SellerShopinfo::where('ru_id', $ru_id);
                $basic_info = $this->baseRepository->getToArrayFirst($basic_info);

                $chat = $this->dscRepository->chatQq($basic_info);

                $order['kf_type'] = $chat['kf_type'];
                $order['kf_ww'] = $chat['kf_ww'];
                $order['kf_qq'] = $chat['kf_qq'];
            }

            return $order;
        }
    }

    /**
     * 取消一个退换单
     *
     * @param int $ret_id
     * @param int $user_id
     * @return bool
     */
    public function wholesaleCancelReturn($ret_id = 0, $user_id = 0)
    {
        /* 查询订单信息，检查状态 */
        $order = WholesaleOrderReturn::where('ret_id', $ret_id);
        $order = $this->baseRepository->getToArrayFirst($order);

        if (empty($order)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['return_exist']);
            return false;
        }

        // 如果用户ID大于0，检查订单是否属于该用户
        if ($user_id > 0 && $order['user_id'] != $user_id) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
            return false;
        }

        // 订单状态只能是用户寄回和未退款状态
        if ($order['return_status'] != RF_APPLICATION && $order['refound_status'] != FF_NOREFOUND) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['return_not_unconfirmed']);
            return false;
        }

        //一旦由商家收到退换货商品，不允许用户取消
        if ($order['return_status'] == RF_RECEIVE) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['current_os_already_receive']);
            return false;
        }

        // 商家已发送退换货商品
        if ($order['return_status'] == RF_SWAPPED_OUT_SINGLE || $order['return_status'] == RF_SWAPPED_OUT) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['already_out_goods']);
            return false;
        }

        // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
        if ($order['refound_status'] == FF_REFOUND) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['have_refound']);
            return false;
        }

        //将用户订单设置为取消
        $delete = WholesaleOrderReturn::where('ret_id', $ret_id)->delete();

        if ($delete) {
            WholesaleReturnGoods::where('rec_id', $order['rec_id'])->delete();

            $user_id = session('user_id', 0);
            $img_list = WholesaleReturnImages::where('user_id', $user_id)
                ->where('rec_id', $order['rec_id']);
            $img_list = $this->baseRepository->getToArrayGet($img_list);
            $img_file = $this->baseRepository->getFlatten($img_list, 'img_file');

            if ($img_file) {
                dsc_unlink($img_file, storage_public());

                $this->dscRepository->getOssDelFile($img_file);

                WholesaleReturnImages::where('user_id', $user_id)
                    ->where('rec_id', $order['rec_id'])
                    ->delete();
            }

            /* 删除扩展记录 */
            WholesaleOrderReturnExtend::where('ret_id', $ret_id)->delete();

            /* 记录log */
            $this->wholesaleReturnAction($ret_id, '取消', '', '', '买家', '');

            return true;
        } else {
            die($GLOBALS['db']->errorMsg());
        }
    }

    /**
     * 记录订单操作记录
     *
     * @access  public
     * @param string $order_sn 订单编号
     * @param integer $order_status 订单状态
     * @param integer $shipping_status 配送状态
     * @param integer $pay_status 付款状态
     * @param string $note 备注
     * @param string $username 用户名，用户自己的操作则为 buyer
     * @return  void
     */
    public function wholesaleReturnAction($ret_id, $return_status, $refound_status, $note = '', $username = '', $place = 0)
    {
        if (empty($username)) {
            $username = get_admin_name();
        }

        $order = WholesaleOrderReturn::where('ret_id', $ret_id);
        $order = $this->baseRepository->getToArrayFirst($order);

        $log_time = $this->timeRepository->getGmTime();

        $other = [
            'ret_id' => $order['ret_id'] ?? 0,
            'action_user' => $username,
            'return_status' => $return_status,
            'refound_status' => $refound_status,
            'action_place' => $place,
            'action_note' => $note ?? '',
            'log_time' => $log_time
        ];
        WholesaleReturnAction::insert($other);
    }

    /**
     * 获得退换货操作log
     *
     * @param $ret_id
     * @return array
     */
    public function getWholesaleReturnAction($ret_id)
    {
        $res = WholesaleReturnAction::where('ret_id', $ret_id)
            ->orderBy('log_time', 'desc')
            ->orderBy('ret_id', 'desc');
        $res = $this->baseRepository->getToArrayGet($res);

        $act_list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $row['return_status'] = $GLOBALS['_LANG']['rf'][$row['return_status']];
                $row['refound_status'] = $GLOBALS['_LANG']['ff'][$row['refound_status']];
                $row['action_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['log_time']);

                $act_list[] = $row;
            }
        }

        return $act_list;
    }

    /**
     * 取的退换货表单里的商品
     * by Leah
     * @param type $rec_id
     * @return type
     */
    public function getWholesaleReturnOrderGoods($rec_id = 0)
    {
        $goods_list = WholesaleOrderGoods::where('rec_id', $rec_id);
        $goods_list = $goods_list->with([
            'getWholesale' => function ($query) {
                $query->with([
                    'getWholesaleBrand'
                ]);
            },
            'getWholesaleProducts'
        ]);

        $goods_list = $this->baseRepository->getToArrayGet($goods_list);

        if ($goods_list) {
            foreach ($goods_list as $key => $row) {
                $wholesale = $row['get_wholesale'];
                $goods_list[$key]['brand_name'] = $wholesale['get_wholesale_brand']['brand_name'] ?? '';

                //图片显示
                $goods_list[$key]['goods_thumb'] = $wholesale && $wholesale['goods_thumb'] ? $this->dscRepository->getImagePath($wholesale['goods_thumb']) : '';
                $goods_list[$key]['url'] = $this->dscRepository->buildUri('wholesale_goods', ['aid' => $row['goods_id']], $row['goods_name']);

                $products = $row['get_wholesale_products'];
                $goods_list[$key]['product_sn'] = $products['product_sn'] ?? '';
            }
        }

        return $goods_list;
    }

    /**
     * 获取用户指定范围的批发订单列表
     *
     * @param $record_count
     * @param $where
     * @param array $order
     * @return array
     * @throws \Exception
     */
    public function getWholesaleOrders($record_count, $where, $order = [])
    {
        $where['user_id'] = $where['user_id'] ?? 0;
        $keyword = $order->keyword ?? '';

        if ($order) {
            $idTxt = $order->idTxt ?? '';
            $action = $order->action ?? '';
            $type = $order->type ?? '';
            $status_keyword = $order->status_keyword ?? '';
            $date_keyword = $order->date_keyword ?? '';

            $id = '"';
            $id .= $where['user_id'] . "=";
            $id .= "idTxt@" . $idTxt . "|";
            $id .= "keyword@" . $keyword . "|";
            $id .= "action@" . $action . "|";
            $id .= "type@" . $type . "|";

            if ($status_keyword) {
                $id .= "status_keyword@" . $status_keyword . "|";
            }

            if ($date_keyword) {
                $id .= "date_keyword@" . $date_keyword;
            }

            $substr = substr($id, -1);
            if ($substr == "|") {
                $id = substr($id, 0, -1);
            }

            $id .= '"';
        } else {
            $id = $where['user_id'];
        }

        $config = [
            'header' => $GLOBALS['_LANG']['pager_2'],
            "prev" => "<i><<</i>" . $GLOBALS['_LANG']['page_prev'],
            "next" => "" . $GLOBALS['_LANG']['page_next'] . "<i>>></i>",
            "first" => $GLOBALS['_LANG']['page_first'],
            "last" => $GLOBALS['_LANG']['page_last']
        ];

        $pagerParams = [
            'total' => $record_count,
            'listRows' => $where['size'],
            'id' => $id,
            'page' => $where['page'],
            'funName' => 'wholesale_order_gotoPage',
            'pageType' => 1,
            'config_zn' => $config
        ];

        $user_order = new Pager($pagerParams);

        $pager = $user_order->fpage(array(0, 4, 5, 6, 9));

        /* 取得订单列表 */
        $res = WholesaleOrderInfo::mainOrderCount()
            ->where('user_id', $where['user_id'])
            ->where('is_delete', 0);

        if (isset($order->type) && $order->type == 'text') {
            $val = mysql_like_quote($order->keyword);

            $res = $res->where(function ($query) use ($val) {
                $query->whereRaw("order_sn LIKE '%$val%'")
                    ->orWhere(function ($query) use ($val) {
                        $query->whereHas('getWholesaleOrderGoods', function ($query) use ($val) {
                            $query->where('goods_name', 'like', '%' . $val . '%')
                                ->orWhere('goods_sn', 'like', '%' . $val . '%');
                        });
                    });
            });
        }

        if ($keyword != '-1') {
            $res = $res->searchKeyword($order);
        }

        $res = $res->orderBy('add_time', 'desc');

        $start = ($where['page'] - 1) * $where['size'];

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($where['size'] > 0) {
            $res = $res->take($where['size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $row['total_fee'] = $row['order_amount'];
                $row['pay_name'] = Payment::where('pay_id', $row['pay_id'])->value('pay_name');

                $row['user_order'] = $row['order_status'];

                if ($row['user_order'] == OS_UNCONFIRMED) { //未确认
                    $row['delete_yes'] = 0;
                } elseif ($row['user_order'] == OS_CONFIRMED) { //已确认
                    $row['delete_yes'] = 1;
                } else {
                    $row['delete_yes'] = 0;
                }

                $row['status'] = $row['order_status'];
                if ($row['order_status'] == OS_CONFIRMED) {
                    $row['order_status'] = lang('user.is_confirmed');
                } elseif ($row['order_status'] == OS_UNCONFIRMED && $row['shipping_status'] == SS_SHIPPED) {
                    $row['order_status'] = $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                } elseif ($row['order_status'] == OS_UNCONFIRMED) {
                    $row['order_status'] = lang('user.no_confirmed');
                } else {
                    $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                }

                $row['handler_return'] = '';
                $row['original_handler'] = lang('user.cancel');
                $row['original_handler_return'] = '';
                $row['handler_return_act'] = '';
                $row['handler_act'] = '';
                $row['handler_order_status'] = '';
                $row['handler'] = '';

                $order_over = 0;
                if ($row['user_order'] == OS_CONFIRMED) {
                    $order_over = 1; //订单完成
                    $row['order_status'] = lang('user.ss_received');

                    $row['handler_order_status'] = false;

                    $row['handler_act'] = 'commented_view';
                    $row['handler'] = "<a href=\"user.php?act=commented_view&order_id=" . $row['order_id'] . '">' . $row['original_handler'] . '</a><br/>';
                    $row['original_handler_return'] = lang('user.return');
                    $row['handler_return_act'] = 'goods_order';
                    $row['handler_return'] = "<a href=\"user.php?act=goods_order&order_id=" . $row['order_id'] . '" style="margin-left:5px;" >' . lang('user.return') . "</a><br/>";
                } else {
                    if ($row['user_order'] == OS_UNCONFIRMED) {
                        $order_over = 0; //订单完成
                        $row['handler_order_status'] = false;
                        $row['handler'] = '';
                    }
                }

                $ru_id = Suppliers::where('suppliers_id', $row['suppliers_id'])->value('user_id');
                $ru_id = $ru_id ? $ru_id : 0;

                $row['order_goods'] = $this->orderManageService->wholesaleOrderGoodsToInfo($row['order_id']);
                $row['order_goods_num'] = 0;
                if (isset($row['order_goods']) && $row['order_goods']) {
                    $row['order_goods_num'] = array_sum(array_column($row['order_goods'], 'goods_number'));
                }

                $row['order_goods_count'] = count($row['order_goods']);

                $order_child = WholesaleOrderInfo::where('main_order_id', $row['order_id'])->count();

                $order_count = WholesaleOrderInfo::where('main_order_id', $row['main_order_id'])
                    ->where('main_order_id', '>', 0)
                    ->count();

                $basic_info = SellerShopinfo::where('ru_id', $ru_id);
                $basic_info = $this->baseRepository->getToArrayFirst($basic_info);

                $province = get_order_region_name($row['province']);
                $city = get_order_region_name($row['city']);
                $district = get_order_region_name($row['district']);
                $province['region_name'] = $province['region_name'] ?? '';
                $city['region_name'] = $city['region_name'] ?? '';
                $district_name = $district['region_name'] ?? '';

                $address_detail = $province['region_name'] . "&nbsp;" . $city['region_name'] . "&nbsp;" . $district_name;

                $row['order_status'] = str_replace(array('<br>', '<br />'), array('', '，'), $row['order_status']);

                $row['shop_name'] = $this->merchantCommonService->getShopName($ru_id, 1);

                $build_uri = array(
                    'urid' => $ru_id,
                    'append' => $row['shop_name']
                );

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($ru_id, $build_uri);
                $row['shop_url'] = $domain_url['domain_name'];

                $chat = $this->dscRepository->chatQq($basic_info);

                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                $shop_information = $this->merchantCommonService->getShopName($ru_id); //通过ru_id获取到店铺信息;
                //判断当前商家是平台,还是入驻商家 bylu
                if ($ru_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                    if ($kf_im_switch) {
                        $row['is_dsc'] = true;
                    } else {
                        $row['is_dsc'] = false;
                    }
                } else {
                    $row['is_dsc'] = false;
                }

                /*  @author-bylu  end */
                $arr[] = [
                    'order_id' => $row['order_id'],
                    'order_sn' => $row['order_sn'],
                    'order_time' => $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']),
                    'sign' => $shop_information['is_IM'] ?? 0, //平台是否允许商家使用"在线客服";
                    'is_dsc' => $row['is_dsc'],
                    'order_status' => $row['order_status'],
                    'status' => $row['status'],
                    'consignee' => $row['consignee'],
                    'postscript' => $row['postscript'],
                    'inv_type' => $row['inv_type'],
                    'inv_payee' => $row['inv_payee'],
                    'tax_id' => $row['tax_id'],
                    'main_order_id' => $row['main_order_id'],
                    'shop_name' => $row['shop_name'], //店铺名称	,
                    'mobile' => $row['mobile'],
                    'shop_url' => $row['shop_url'], //店铺名称	,
                    'order_goods' => $row['order_goods'],
                    'order_goods_count' => $row['order_goods_count'],
                    'order_child' => $order_child,
                    'no_picture' => $this->config['no_picture'],
                    'delete_yes' => $row['delete_yes'],
                    'invoice_no' => $row['invoice_no'],
                    'shipping_name' => $row['shipping_name'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'address_detail' => $address_detail,
                    'tel' => $row['tel'],
                    'order_count' => $order_count,
                    'kf_type' => $chat['kf_type'],
                    'kf_ww' => $chat['kf_ww'],
                    'kf_qq' => $chat['kf_qq'],
                    'total_fee' => $this->dscRepository->getPriceFormat($row['total_fee'], false),
                    'handler_return' => $row['handler_return'],
                    'handler' => $row['handler'],
                    'original_handler' => $row['original_handler'],
                    'original_handler_return' => $row['original_handler_return'],
                    'handler_act' => $row['handler_act'],
                    'handler_return_act' => $row['handler_return_act'],
                    'handler_order_status' => $row['handler_order_status'] ? true : false,
                    'order_over' => $order_over,
                    'pay_time' => $this->timeRepository->getLocalDate($this->config['time_format'], $row['pay_time']),
                    'pay_status' => $row['pay_status'],
                    'pay_name' => $row['pay_name'],
                    'pay_fee' => $row['pay_fee'],
                    'is_refund' => $row['is_refund'],//是否申请退换货
                    'inv_content' => $row['inv_content'],
                    'invoice_type' => $row['invoice_type'],
                    'is_settlement' => $row['is_settlement'],
                    'order_goods_num' => $row['order_goods_num']
                ];
            }
        }

        $order_list = array('order_list' => $arr, 'pager' => $pager, 'record_count' => $record_count, 'page_count' => ceil($record_count / $where['size']));
        return $order_list;
    }
}
