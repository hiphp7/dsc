<?php

namespace App\Services\Order;

use App\Models\AdminUser;
use App\Models\BackGoods;
use App\Models\BackOrder;
use App\Models\DeliveryGoods;
use App\Models\DeliveryOrder;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsExtend;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\PackageGoods;
use App\Models\Payment;
use App\Models\Region;
use App\Models\ReturnCause;
use App\Models\Suppliers;
use App\Models\UserAddress;
use App\Models\ZcGoods;
use App\Models\ZcProject;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Commission\CommissionService;
use App\Services\Common\CommonManageService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserAddressService;

class OrderManageService
{
    protected $goodsWarehouseService;
    protected $baseRepository;
    protected $commonRepository;
    protected $commissionService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $commonManageService;
    protected $orderRefoundService;
    protected $orderCommonService;
    protected $userAddressService;
    protected $timeRepository;

    public function __construct(
        GoodsWarehouseService $goodsWarehouseService,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        CommissionService $commissionService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        CommonManageService $commonManageService,
        OrderRefoundService $orderRefoundService,
        OrderCommonService $orderCommonService,
        UserAddressService $userAddressService,
        TimeRepository $timeRepository
    )
    {
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->commissionService = $commissionService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->commonManageService = $commonManageService;
        $this->orderRefoundService = $orderRefoundService;
        $this->orderCommonService = $orderCommonService;
        $this->userAddressService = $userAddressService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 退换货描述类型
     *
     * @param $c_id
     * @return array
     */
    public function causeInfo($c_id)
    {
        $res = ReturnCause::where('cause_id', $c_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        if ($res) {
            return $res;
        } else {
            return [];
        }
    }

    /**
     * 取得状态列表
     *
     * @param string $type
     * @return array
     */
    public function getStatusList($type = 'all')
    {
        $list = [];

        if ($type == 'all' || $type == 'order') {
            $pre = $type == 'all' ? 'os_' : '';
            foreach ($GLOBALS['_LANG']['os'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'shipping') {
            $pre = $type == 'all' ? 'ss_' : '';
            foreach ($GLOBALS['_LANG']['ss'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'payment') {
            $pre = $type == 'all' ? 'ps_' : '';
            foreach ($GLOBALS['_LANG']['ps'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }
        return $list;
    }

    /**
     * 更新订单总金额
     * @param int $order_id 订单id
     * @return  bool
     */
    public function updateOrderAmount($order_id)
    {
        load_helper('order');
        //更新订单总金额

        $res = OrderInfo::selectRaw(order_due_field() . ' as order_amount')->where('order_id', $order_id)->value('order_amount');
        $res = $res ? $res : 0;
        $data = ['order_amount' => $res];
        $res = OrderInfo::where('order_id', $order_id)->update($data);

        return $res;
    }

    /**
     * 返回某个订单可执行的操作列表，包括权限判断
     * @param array $order 订单信息 order_status, shipping_status, pay_status
     * @param bool $is_cod 支付方式是否货到付款
     * @return  array   可执行的操作  confirm, pay, unpay, prepare, ship, unship, receive, cancel, invalid, return, drop
     * 格式 array('confirm' => true, 'pay' => true)
     */
    public function operableList($order)
    {
        /* 取得订单状态、发货状态、付款状态 */
        $os = $order['order_status'];
        $ss = $order['shipping_status'];
        $ps = $order['pay_status'];

        /* 佣金账单状态 0 未出账 1 出账 2 结账 */
        $chargeoff_status = $order['chargeoff_status'];

        /* 取得订单操作权限 */
        $actions = session('action_list');
        if ($actions == 'all') {
            $priv_list = ['os' => true, 'ss' => true, 'ps' => true, 'edit' => true];
        } else {
            $actions = ',' . $actions . ',';
            $priv_list = [
                'os' => strpos($actions, ',order_os_edit,') !== false,
                'ss' => strpos($actions, ',order_ss_edit,') !== false,
                'ps' => strpos($actions, ',order_ps_edit,') !== false,
                'edit' => strpos($actions, ',order_edit,') !== false
            ];
        }

        /* 取得订单支付方式是否货到付款 */
        $payment = payment_info($order['pay_id']);

        if (isset($payment['is_cod']) && $payment['is_cod'] == 1) {
            $is_cod = true;
        } else {
            $is_cod = false;
        }

        /* 根据状态返回可执行操作 */
        $list = [];
        if (OS_UNCONFIRMED == $os) {
            /* 状态：未确认 => 未付款、未发货 */
            if ($priv_list['os']) {
                $list['confirm'] = true; // 确认
                $list['invalid'] = true; // 无效
                $list['cancel'] = true; // 取消
                if ($is_cod) {
                    /* 货到付款 */
                    if ($priv_list['ss']) {
                        $list['prepare'] = true; // 配货
                        $list['split'] = true; // 分单
                    }
                } else {
                    /* 不是货到付款 */
                    if ($priv_list['ps']) {
                        $list['pay'] = true;  // 付款
                    }
                }
            }
        } elseif (OS_RETURNED_PART == $os || (SS_RECEIVED != $ss && $chargeoff_status > 0)) {

            /* 状态：未付款 */
            if ($priv_list['ps'] < 2) {
                $list['pay'] = true; // 付款
            }

            if ($ss != SS_RECEIVED) {
                /* 状态：部分退货 */
                $list['receive'] = true; // 收货确认
            }
        } elseif (OS_CONFIRMED == $os || OS_SPLITED == $os || OS_SPLITING_PART == $os) {
            /* 状态：已确认 */
            if (PS_UNPAYED == $ps || PS_PAYED_PART == $ps) {
                /* 状态：已确认、未付款 */
                if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss) {
                    /* 状态：已确认、未付款、未发货（或配货中） */
                    if ($priv_list['os']) {
                        $list['cancel'] = true; // 取消
                        $list['invalid'] = true; // 无效
                    }
                    if ($is_cod) {
                        /* 货到付款 */
                        if ($priv_list['ss']) {
                            if (SS_UNSHIPPED == $ss) {
                                $list['prepare'] = true; // 配货
                            }
                            $list['split'] = true; // 分单
                        }
                    } else {
                        /* 不是货到付款 */
                        if ($priv_list['ps']) {
                            $list['pay'] = true; // 付款
                        }
                    }
                } /* 状态：已确认、未付款、发货中 */
                elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss) {
                    // 部分分单
                    if (OS_SPLITING_PART == $os) {
                        $list['split'] = true; // 分单
                    }
                    $list['to_delivery'] = true; // 去发货
                } else {
                    /* 状态：已确认、未付款、已发货或已收货 => 货到付款 */
                    if ($priv_list['ps']) {
                        $list['pay'] = true; // 付款
                    }
                    if ($priv_list['ss']) {
                        if (SS_SHIPPED == $ss) {
                            $list['receive'] = true; // 收货确认
                        }
                        $list['unship'] = true; // 设为未发货
                        if ($priv_list['os']) {
                            $list['return'] = true; // 退货
                        }
                    }
                }
            } else {
                /* 状态：已确认、已付款和付款中 */
                if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss) {
                    /* 状态：已确认、已付款和付款中、未发货（配货中） => 不是货到付款 */
                    if ($priv_list['ss']) {
                        if (SS_UNSHIPPED == $ss) {
                            $list['prepare'] = true; // 配货
                        }
                        $list['split'] = true; // 分单
                    }
                    if ($priv_list['ps']) {
                        $list['unpay'] = true; // 设为未付款
                        if ($priv_list['os']) {
                            //$list['cancel'] = true; // 取消  暂时注释 liu
                        }
                    }
                } /* 状态：已确认、未付款、发货中 */
                elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss) {
                    // 部分分单
                    if (OS_SPLITING_PART == $os) {
                        $list['split'] = true; // 分单
                    }
                    $list['to_delivery'] = true; // 去发货
                } else {
                    /* 状态：已确认、已付款和付款中、已发货或已收货 */
                    if ($priv_list['ss']) {
                        if (SS_SHIPPED == $ss) {
                            $list['receive'] = true; // 收货确认
                        }
                        if (!$is_cod && $ss != SS_RECEIVED) {
                            $list['unship'] = true; // 设为未发货
                        }
                    }
                    if ($priv_list['ps'] && $is_cod) {
                        $list['unpay'] = true; // 设为未付款
                    }
                    if ($priv_list['os'] && $priv_list['ss'] && $priv_list['ps']) {
                        $list['return'] = true; // 退货（包括退款）
                    }
                }
            }
        } elseif (OS_CANCELED == $os) {
            /* 状态：取消 */
            if ($priv_list['os']) {
                // $list['confirm'] = true; 暂时注释 liu
            }
            if ($priv_list['edit']) {
                $list['remove'] = true;
            }
        } elseif (OS_INVALID == $os) {
            /* 状态：无效 */
            if ($priv_list['os']) {
                //$list['confirm'] = true; 暂时注释 liu
            }
            if ($priv_list['edit']) {
                $list['remove'] = true;
            }
        } elseif (OS_RETURNED == $os) {
            /* 状态：退货 */
            if ($priv_list['os']) {
                $list['confirm'] = true;
            }
        }

        if ((OS_CONFIRMED == $os || OS_SPLITED == $os || OS_SHIPPED_PART == $os) && PS_PAYED == $ps && (SS_UNSHIPPED == $ss || SS_SHIPPED_PART == $ss)) {
            /* 状态：（已确认、已分单）、已付款和未发货 */
            if ($priv_list['os'] && $priv_list['ss'] && $priv_list['ps']) {
                $list['return'] = true; // 退货（包括退款）
            }
        }

        /* 修正发货操作 */
        if (!empty($list['split'])) {
            /* 如果是团购活动且未处理成功，不能发货 */
            if ($order['extension_code'] == 'group_buy') {
                $where = [
                    'group_buy_id' => intval($order['extension_id'])
                ];
                $group_buy = app(GroupBuyService::class)->getGroupBuyInfo($where);

                if ($group_buy['status'] != GBS_SUCCEED) {
                    unset($list['split']);
                    unset($list['to_delivery']);
                }
            }

            /* 如果部分发货 不允许 取消 订单 */
            if ($this->orderDeliveryed($order['order_id'])) {
                $list['return'] = true; // 退货（包括退款）
                unset($list['cancel']); // 取消
            }
        }

        /**
         * 同意申请
         */
        $list['after_service'] = true;
        $list['receive_goods'] = true;
        $list['agree_apply'] = true;
        $list['refound'] = true;
        $list['swapped_out_single'] = true;
        $list['swapped_out'] = true;
        $list['complete'] = true;
        $list['refuse_apply'] = true;
        /*
     * by Leah
     */

        return $list;
    }

    /**
     * 处理编辑订单时订单金额变动
     * @param array $order 订单信息
     * @param array $msgs 提示信息
     * @param array $links 链接信息
     */
    public function handleOrderMoneyChange($order, &$msgs, &$links)
    {
        $order_id = $order['order_id'];
        if ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING) {
            /* 应付款金额 */
            $money_dues = $order['order_amount'];
            if ($money_dues > 0) {
                /* 修改订单为未付款 */
                update_order($order_id, ['pay_status' => PS_UNPAYED, 'pay_time' => 0]);
                $msgs[] = $GLOBALS['_LANG']['amount_increase'];
                $links[] = ['text' => lang('admin/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
            } elseif ($money_dues < 0) {
                $anonymous = $order['user_id'] > 0 ? 0 : 1;
                $msgs[] = $GLOBALS['_LANG']['amount_decrease'];
                $links[] = ['text' => lang('admin/order.refund'), 'href' => 'order.php?act=process&func=load_refund&anonymous=' .
                    $anonymous . '&order_id=' . $order_id . '&refund_amount=' . abs($money_dues)];
            }
        }
    }

    /**
     * 获取订单列表信息
     *
     * @param int $page
     * @return array
     * @throws \Exception
     */
    public function orderList($page = 0)
    {
        $adminru = get_admin_ru_id();

        /* 过滤信息 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);

        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
            $filter['order_sn'] = json_str_iconv($filter['order_sn']);
            $filter['consignee'] = json_str_iconv($filter['consignee']);
            $filter['address'] = json_str_iconv($filter['address']);
        }

        $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
        $filter['zipcode'] = empty($_REQUEST['zipcode']) ? '' : trim($_REQUEST['zipcode']);
        $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
        $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : trim($_REQUEST['mobile']);
        $filter['country'] = empty($_REQUEST['order_country']) ? 0 : intval($_REQUEST['order_country']);
        $filter['province'] = empty($_REQUEST['order_province']) ? 0 : intval($_REQUEST['order_province']);
        $filter['city'] = empty($_REQUEST['order_city']) ? 0 : intval($_REQUEST['order_city']);
        $filter['district'] = empty($_REQUEST['order_district']) ? 0 : intval($_REQUEST['order_district']);
        $filter['street'] = empty($_REQUEST['order_street']) ? 0 : intval($_REQUEST['order_street']);
        $filter['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
        $filter['pay_id'] = empty($_REQUEST['pay_id']) ? 0 : intval($_REQUEST['pay_id']);
        $filter['order_status'] = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
        $filter['pay_status'] = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
        $filter['order_type'] = isset($_REQUEST['order_type']) ? intval($_REQUEST['order_type']) : 0;
        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;
        $filter['group_buy_id'] = isset($_REQUEST['group_buy_id']) ? intval($_REQUEST['group_buy_id']) : 0;
        $filter['presale_id'] = isset($_REQUEST['presale_id']) ? intval($_REQUEST['presale_id']) : 0; // 预售id
        $filter['store_id'] = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0; // 门店id
        $filter['order_cat'] = isset($_REQUEST['order_cat']) ? trim($_REQUEST['order_cat']) : '';

        /**
         * 0 : 自营
         * 1 : 店铺
         * 3 : 主订单
         */
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? intval($_REQUEST['seller_list']) : 0;

        $filter['order_referer'] = isset($_REQUEST['order_referer']) ? trim($_REQUEST['order_referer']) : '';

        $filter['source'] = empty($_REQUEST['source']) ? '' : trim($_REQUEST['source']); //来源起始页

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['start_time']) : $_REQUEST['start_time']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['end_time']) : $_REQUEST['end_time']);

        //确认收货时间 bylu:
        $filter['start_take_time'] = empty($_REQUEST['start_take_time']) ? '' : (strpos($_REQUEST['start_take_time'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['start_take_time']) : $_REQUEST['start_take_time']);
        $filter['end_take_time'] = empty($_REQUEST['end_take_time']) ? '' : (strpos($_REQUEST['end_take_time'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['end_take_time']) : $_REQUEST['end_take_time']);

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';
        $filter['store_type'] = isset($_REQUEST['store_type']) ? trim($_REQUEST['store_type']) : '';

        //众筹订单 by wu

        $filter['pid'] = !isset($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);
        $filter['gid'] = !isset($_REQUEST['gid']) ? 0 : intval($_REQUEST['gid']);

        $filter['serch_type'] = !isset($_REQUEST['serch_type']) ? -1 : intval($_REQUEST['serch_type']);

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        $res = OrderInfo::whereRaw(1);

        if ($filter['keywords']) {

            $res = $res->where(function ($query) use ($filter) {
                $query = $query->where('order_sn', 'LIKE', '%' . $filter['keywords'] . '%');

                $query = $query->orWhere(function ($query) use ($filter) {
                    $query->whereHas('getOrderGoods', function ($query) use ($filter) {
                        $query->where(function ($query) use ($filter) {
                            $query->where('goods_name', 'LIKE', '%' . $filter['keywords'] . '%')
                                ->orWhere('goods_sn', 'LIKE', '%' . $filter['keywords'] . '%');
                        });
                    });
                });

                if ($filter['seller_list'] == 3) {
                    $query->orWhere(function ($query) use ($filter) {
                        $query->whereHas('getMainOrderId', function ($query) use ($filter) {
                            $query->where('order_sn', 'LIKE', '%' . $filter['keywords'] . '%');
                        });
                    });

                }

            });

        }

        if ($GLOBALS['_CFG']['region_store_enabled']) {
            //卖场
            $res = $res->where(function ($query) {
                $query->whereHas('getOrderGoods');
            });

            $res = $this->dscRepository->getWhereRsid($res, $filter['rs_id']);
        }

        $store_search = -1;

        if ($filter['store_search'] > -1) {
            if ($adminru['ru_id'] == 0) {
                if ($filter['store_search'] > 0) {

                    if ($filter['store_search'] == 1) {
                        $res = $res->where('ru_id', $filter['merchant_id']);
                    }

                    if ($filter['store_search'] > 1) {
                        $res = $res->where(function ($query) use ($filter) {
                            $query->whereHas('getMerchantsShopInformation', function ($query) use ($filter) {
                                if ($filter['store_search'] == 2) {
                                    $query->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                } elseif ($filter['store_search'] == 3) {
                                    $query = $query->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                    if ($filter['store_type']) {
                                        $query->where('shopNameSuffix', $filter['store_type']);
                                    }
                                }
                            });
                        });

                    }
                } else {
                    $store_search = 0;
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        if ($filter['store_id'] > 0) {
            $res = $res->where(function ($query) use ($filter) {
                $query->whereHas('getStoreOrder', function ($query) use ($filter) {
                    $query->where('store_id', $filter['store_id']);
                });
            });

        }

        if ($filter['order_sn']) {
            $res = $res->where('order_sn', 'LIKE', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }
        if ($filter['consignee']) {
            $res = $res->where('consignee', 'LIKE', '%' . mysql_like_quote($filter['consignee']) . '%');
        }
        if ($filter['email']) {
            $res = $res->where('email', 'LIKE', '%' . mysql_like_quote($filter['email']) . '%');
        }
        if ($filter['address']) {
            $res = $res->where('address', 'LIKE', '%' . mysql_like_quote($filter['address']) . '%');
        }
        if ($filter['zipcode']) {
            $res = $res->where('zipcode', 'LIKE', '%' . mysql_like_quote($filter['zipcode']) . '%');
        }
        if ($filter['tel']) {
            $res = $res->where('tel', 'LIKE', '%' . mysql_like_quote($filter['tel']) . '%');
        }
        if ($filter['mobile']) {
            $res = $res->where('mobile', 'LIKE', '%' . mysql_like_quote($filter['mobile']) . '%');
        }
        if ($filter['country']) {
            $res = $res->where('country', $filter['country']);
        }
        if ($filter['province']) {
            $res = $res->where('province', $filter['province']);
        }
        if ($filter['city']) {
            $res = $res->where('city', $filter['city']);
        }
        if ($filter['district']) {
            $res = $res->where('district', $filter['district']);
        }
        if ($filter['street']) {
            $res = $res->where('street', $filter['street']);
        }
        if ($filter['shipping_id']) {
            $res = $res->where('shipping_id', $filter['shipping_id']);
        }
        if ($filter['pay_id']) {
            $res = $res->where('pay_id', $filter['pay_id']);
        }
        if ($filter['order_status'] != -1) {
            $res = $res->where('order_status', $filter['order_status']);
        }
        if ($filter['shipping_status'] != -1) {
            $res = $res->where('shipping_status', $filter['shipping_status']);
        }
        if ($filter['pay_status'] != -1) {
            $res = $res->where('pay_status', $filter['pay_status']);
        }
        if ($filter['user_id']) {
            $res = $res->where('user_id', $filter['user_id']);
        }
        if ($filter['user_name']) {
            $res = $res->where(function ($query) use ($filter) {
                $query->whereHas('getUsers', function ($query) use ($filter) {
                    $query->where('user_name', 'LIKE', '%' . mysql_like_quote($filter['user_name']) . '%');
                });
            });
        }
        if ($filter['start_time']) {
            $res = $res->where('add_time', '>=', $filter['start_time']);
        }
        if ($filter['end_time']) {
            $res = $res->where('add_time', '<=', $filter['end_time']);
        }

        if (isset($filter['order_referer']) && $filter['order_referer']) {
            if ($filter['order_referer'] == 'pc') {
                $res = $res->whereNotIn('referer', ['mobile', 'H5', 'ecjia-cashdesk']);
            } else {
                $res = $res->where('referer', $filter['order_referer']);
            }
        }

        if ($filter['order_cat']) {
            switch ($filter['order_cat']) {
                case 'stages':
                    $res = $res->where(function ($query) {
                        $query->whereHas('getBaitiaoLog');
                    });
                    break;
                case 'zc':
                    $res = $res->where('is_zc_order', 1);
                    break;
                case 'store':
                    $res = $res->where(function ($query) {
                        $query->whereHas('getStoreOrder');
                    });
                    break;
                case 'other':
                    $res = $res->where('extension_code', '<>', '');
                    break;
                case 'dbdd':
                    $res = $res->where('extension_code', 'snatch');
                    break;
                case 'msdd':
                    $res = $res->where('extension_code', 'seckill');
                    break;
                case 'tgdd':
                    $res = $res->where('extension_code', 'group_buy');
                    break;
                case 'pmdd':
                    $res = $res->where('extension_code', 'auction');
                    break;
                case 'jfdd':
                    $res = $res->where('extension_code', 'exchange_goods');
                    break;
                case 'ysdd':
                    $res = $res->where('extension_code', 'presale');
                    break;
                default:
            }
        }

        $firstSecToday = $this->timeRepository->getLocalMktime(0, 0, 0, $this->timeRepository->getLocalDate("m"), $this->timeRepository->getLocalDate("d"), $this->timeRepository->getLocalDate("Y"));
        $lastSecToday = $this->timeRepository->getLocalMktime(0, 0, 0, $this->timeRepository->getLocalDate("m"), $this->timeRepository->getLocalDate("d") + 1, $this->timeRepository->getLocalDate("Y")) - 1;

        //综合状态
        switch ($filter['composite_status']) {
            case CS_AWAIT_PAY:
                $res = $this->orderCommonService->orderQuerySelect($res, 'await_pay');
                break;

            case CS_AWAIT_SHIP:
                $res = $this->orderCommonService->orderQuerySelect($res, 'await_ship');
                $res = $res->doesntHave('getOrderReturn');

                break;

            case CS_FINISHED:
                $res = $this->orderCommonService->orderQuerySelect($res, 'finished');
                break;
            case CS_CONFIRM_TAKE:
                $res = $this->orderCommonService->orderQuerySelect($res, 'confirm_take');
                break;

            case PS_PAYING:
                if ($filter['composite_status'] != -1) {
                    $res = $res->where('pay_status', $filter['composite_status']);
                }
                break;
            case OS_SHIPPED_PART:
                if ($filter['composite_status'] != -1) {
                    $res = $res->where('shipping_status', $filter['composite_status'] - 2);
                }
                break;
            case CS_NEW_ORDER:
                $res = $res->where('add_time', '>=', $firstSecToday)
                    ->where('add_time', '<=', $lastSecToday)
                    ->where('shipping_status', SS_UNSHIPPED);
                break;
            case CS_NEW_PAID_ORDER:
                $res = $res->where('pay_time', '>=', $firstSecToday)
                    ->where('pay_time', '<=', $lastSecToday);
                break;
            default:
                if ($filter['composite_status'] != -1) {
                    $res = $res->where('order_status', $filter['composite_status']);
                }
        }

        /* 团购订单 */
        if ($filter['group_buy_id']) {
            $res = $res->where('extension_code', 'group_buy')
                ->where('extension_id', $filter['group_buy_id']);
        }

        /* 预售订单 */
        if ($filter['presale_id']) {
            $res = $res->where('extension_code', 'presale')
                ->where('extension_id', $filter['presale_id']);
        }

        /* 众筹订单 by wu */
        if ($filter['pid']) {
            $zg_res = ZcGoods::select('id')->where('pid', $filter['pid']);
            $zg_res = $this->baseRepository->getToArrayGet($zg_res);
            $goods_ids = $this->baseRepository->getFlatten($zg_res);

            $res = $res->where('is_zc_order', 1)
                ->whereIn('zc_goods_id', $goods_ids);
        }

        if ($filter['gid']) {
            $res = $res->where('is_zc_order', 1)
                ->where('zc_goods_id', $filter['gid']);
        }

        if ($filter['seller_list'] == 3) {
            //显示主订单
            $res = $res->where('main_count', '>', 0);
        } else {
            if ($filter['seller_list']) {
                $res = $res->where('ru_id', '>', 0);
            } else {
                $res = $res->where(function ($query) {
                    $query->where('ru_id', 0)
                        ->orWhere(function ($query) {
                            $query->where('is_zc_order', '>', 0);
                        });
                });
            }

            $res = $res->where('main_count', 0);
        }

        $admin_id = $this->commonManageService->getAdminId();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的订单 */
        $agency_id = AdminUser::where('user_id', $admin_id)
            ->where('action_list', '<>', 'all')
            ->value('agency_id');
        $agency_id = $agency_id ? $agency_id : 0;

        if ($agency_id > 0) {
            $res = $res->where('agency_id', $agency_id);
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
        if ($page > 0) {
            $filter['page'] = $page;
        }

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        if (empty($filter['start_take_time']) || empty($filter['end_take_time'])) {
            if ($store_search == 0 && $adminru['ru_id'] == 0) {
                $res = $res->where('ru_id', 0)->where('main_count', 0);
            }
        }

        if (!empty($filter['start_take_time']) || !empty($filter['end_take_time'])) {
            $res = $res->where(function ($query) use ($filter) {
                $query->whereHas('getOrderAction', function ($query) use ($filter) {
                    if ($filter['start_take_time']) {
                        $query = $query->where('log_time', '>=', $filter['start_take_time']);
                    }
                    if ($filter['end_take_time']) {
                        $query = $query->where('log_time', '<=', $filter['end_take_time']);
                    }

                    $oqsql = order_take_query_sql('finished', "");
                    $oqsql = preg_replace('/ AND/', '', $oqsql, 1);
                    $query->whereRaw($oqsql);
                });
            });

        }

        //判断订单筛选条件
        switch ($filter['serch_type']) {
            case 0:
                //待确认
                $res = $res->where('order_status', OS_UNCONFIRMED);
                break;
            case 1:
                //待付款
                $res = $res->where('pay_status', PS_UNPAYED)
                    ->where('order_status', OS_CONFIRMED);
                break;
            case 2:
                //待收货
                $res = $res->where('shipping_status', SS_SHIPPED);
                break;
            case 3:
                //已完成
                $res = $res->where('shipping_status', SS_RECEIVED);
                break;
            case 4:
                //付款中
                $res = $res->where('pay_status', PS_PAYING);
                break;
            case 5:
                //取消
                $res = $res->where('order_status', OS_CANCELED);
                break;
            case 6:
                //无效
                $res = $res->where('order_status', OS_INVALID);
                break;
            case 7:
                //退货
                $res = $res->whereNotIn('order_status', ["'" . OS_CANCELED . "', '" . OS_INVALID . "', '" .
                    OS_RETURNED . "', '" . OS_RETURNED_PART . "', '" . OS_ONLY_REFOUND . "'"]);

                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderReturn', function ($query) {
                        $query->whereIn('return_type', [1, 3])
                            ->where('refound_status', 0)
                            ->where('return_status', '<>', 6);
                    });
                });

                break;
            case 8:
                //待发货,--191012 update：后台部分发货加入待发货列表，前台部分发货加入待收货列表
                $res = $this->orderCommonService->orderQuerySelect($res, 'await_ship');
                $res = $res->doesntHave('getOrderReturn');
                break;
        }

        /* 记录总数 */
        $filter['record_count'] = $res->count();

        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        if (CROSS_BORDER === true) {
            $res = $res->select('rel_name', 'id_num', 'rate_fee');
        }

        /* 查询 */
        $res = $res->selectRaw("extension_code as oi_extension_code, order_id, main_order_id, order_sn, add_time, order_status, shipping_status, pay_status, order_amount, money_paid, is_delete," .
            "shipping_fee, insure_fee, pay_fee, pack_fee, card_fee, surplus,tax, integral_money, bonus, discount, coupons," .
            "shipping_time, auto_delivery_time, consignee, address, email, tel, mobile, extension_code as o_extension_code, extension_id, is_zc_order, zc_goods_id, pay_id, " .
            "pay_name, referer, froms, user_id, chargeoff_status, confirm_take_time, shipping_id, shipping_name, goods_amount, ru_id, " .
            "country, province, city, district, street, " .
            "(" . $this->orderCommonService->orderTotalField() . ") AS total_fee, (goods_amount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee - discount) AS total_fee_order ");

        $res = $res->withCount('getStoreOrder as is_store_order');

        if (file_exists(MOBILE_DRP)) {
            $res = $res->withCount([
                'getOrderGoods as is_drp_order' => function ($query) {
                    $query->where('is_distribution', 1)->where('drp_money', '>', 0);
                }
            ]);
        }

        $res = $res->with([
            'getBaitiaoLog',
            'getSellerBillOrder' => function ($query) {
                $query->with([
                    'getSellerCommissionBill'
                ]);
            },
            'getUsers',
            'getOrderReturn',
            'getValueCardRecord',
            'getMainOrderChild' => function ($query) {
                $query->select('order_id', 'main_order_id', 'order_sn');
            },
            'getMerchantsShopInformation',
            'getOrderGoodsList' => function ($query) {
                $query->with([
                    'getOrderReturn',
                    'getGoods' => function ($query) {
                        $query->with([
                            'getBrand'
                        ]);
                    }
                ]);
            },
            'getRegionCountry' => function ($query) {
                $query->select('region_id', 'region_name');
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

        foreach (['order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name'] as $val) {
            $filter[$val] = stripslashes($filter[$val]);
        }

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);

        $row = $this->baseRepository->getToArrayGet($res);

        if ($row) {
            foreach ($row as $key => $value) {
                $value['is_stages'] = 0;
                $row[$key]['is_stages'] = 0;
                if (isset($value['get_baitiao_log']) && !empty($value['get_baitiao_log'])) {
                    $value['is_stages'] = $value['get_baitiao_log']['is_stages'];
                    $row[$key]['is_stages'] = $value['get_baitiao_log']['is_stages'];
                }


                if (!$value['is_stages']) {
                    $row[$key]['is_stages'] = 0;
                }

                //账单编号
                $bill = $value['get_seller_bill_order']['get_seller_commission_bill'] ?? [];

                if ($bill) {
                    $row[$key]['bill_id'] = $bill['id'];
                    $row[$key]['bill_sn'] = $bill['bill_sn'];
                    $row[$key]['seller_id'] = $bill['seller_id'];
                    $row[$key]['proportion'] = $bill['proportion'];
                    $row[$key]['commission_model'] = $bill['commission_model'];
                }

                //查会员名称
                $value['buyer'] = $value['get_users']['user_name'] ?? '';

                $row[$key]['buyer'] = !empty($value['buyer']) ? $value['buyer'] : lang('admin/order.anonymous');

                //判断是否为门店订单
                $is_store_order = $value['is_store_order'] ?? 0;
                $row[$key]['is_store_order'] = empty($is_store_order) ? 0 : 1;

                //判断是否为退换货订单
                $is_order_return = $value['get_order_return']['ret_id'] ?? 0;
                $row[$key]['is_order_return'] = $is_order_return ? $is_order_return : '';

                //判断是否分销订单
                $row[$key]['is_drp_order'] = $value['is_drp_order'] ?? 0;

                $row[$key]['ru_id'] = $value['ru_id'];

                $row[$key]['formated_order_amount'] = $this->dscRepository->getPriceFormat($value['order_amount']);
                $row[$key]['formated_money_paid'] = $this->dscRepository->getPriceFormat($value['money_paid']);
                $row[$key]['formated_total_fee'] = $this->dscRepository->getPriceFormat($value['total_fee']);
                $row[$key]['old_shipping_fee'] = $value['shipping_fee'];
                $row[$key]['shipping_fee'] = $this->dscRepository->getPriceFormat($value['shipping_fee']);
                $row[$key]['short_order_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $value['add_time']);

                $value_card = $value['get_value_card_record']['use_val'] ?? 0;
                $row[$key]['value_card'] = $value_card;
                $row[$key]['formated_value_card'] = $this->dscRepository->getPriceFormat($value_card);

                $row[$key]['formated_total_fee_order'] = $this->dscRepository->getPriceFormat($value['total_fee_order']);

                /* 取得区域名 */
                $country = $value['get_region_country']['region_name'] ?? '';
                $province = $value['get_region_province']['region_name'] ?? '';
                $city = $value['get_region_city']['region_name'] ?? '';
                $district = $value['get_region_district']['region_name'] ?? '';
                $street = $value['get_region_street']['region_name'] ?? '';

                $row[$key]['region'] = $country . ' ' . $province . ' ' . $city . ' ' . $district . ' ' . $street;

                //查询子订单
                $row[$key]['child_list'] = !is_null($value['get_main_order_child']) ? $value['get_main_order_child'] : [];
                $row[$key]['order_child'] = count($row[$key]['child_list']);

                if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
                    /* 如果该订单为无效或取消则显示删除链接 */
                    $row[$key]['can_remove'] = 1;
                } else {
                    $row[$key]['can_remove'] = 0;
                }

                if (isset($value['is_zc_order']) && $value['is_zc_order'] == 1) {
                    $zc_goods = ZcGoods::where('id', $value['zc_goods_id']);
                    $zc_goods = $this->baseRepository->getToArrayFirst($zc_goods);
                    $project_id = !empty($zc_goods) ? $zc_goods['pid'] : 0;
                    $zc_project = ZcProject::where('id', $project_id);
                    $zc_project = $this->baseRepository->getToArrayFirst($zc_project);
                    $zcg = [
                        'goods_name' => $zc_project['title'],
                        'goods_thumb' => $this->dscRepository->getImagePath($zc_project['title_img']),
                        'goods_price' => !empty($zc_goods) ? $zc_goods['price'] : 0,
                        'goods_number' => 1
                    ];
                    $row[$key]['goods_list'][] = $zcg;
                } else {

                    $goods_list = $value['get_order_goods_list'] ?? [];

                    if ($goods_list) {
                        $iog_extension_codes = [];
                        foreach ($goods_list as $idx => $goods) {

                            if ($goods['extension_code']) {
                                $iog_extension_codes[$idx] = $goods['extension_code'];
                            }

                            $goods_list[$idx]['goods_thumb'] = isset($goods['get_goods']['goods_thumb']) ? $this->dscRepository->getImagePath($goods['get_goods']['goods_thumb']) : '';
                            $goods_list[$idx]['goods_img'] = isset($goods['get_goods']['goods_img']) ? $this->dscRepository->getImagePath($goods['get_goods']['goods_img']) : '';
                            $goods_list[$idx]['original_img'] = isset($goods['get_goods']['original_img']) ? $this->dscRepository->getImagePath($goods['get_goods']['original_img']) : '';

                            $goods_list[$idx]['format_goods_price'] = $this->dscRepository->getPriceFormat($goods['goods_price']);

                            $goods_list[$idx]['brand_name'] = $goods['get_goods']['get_brand']['brand_name'] ?? '';

                            $trade_id = $this->orderCommonService->getFindSnapshot($value['order_sn'], $goods['goods_id']);

                            if ($trade_id) {
                                $goods_list[$idx]['trade_url'] = $this->dscRepository->dscUrl("trade_snapshot.php?act=trade&user_id=" . $value['user_id'] . "&tradeId=" . $trade_id . "&snapshot=true");
                            }

                            $goods_list[$idx]['ret_id'] = $goods['get_order_return']['ret_id'] ?? 0;
                            $goods_list[$idx]['back_order'] = return_order_info($goods_list[$idx]['ret_id']);


                            //超值礼包图片
                            if ($goods['extension_code'] == 'package_buy') {
                                $activity = get_goods_activity_info($goods['goods_id'], ['act_id', 'activity_thumb']);
                                $goods_list[$idx]['goods_thumb'] = $activity['goods_thumb'] ?? '';
                            }
                        }

                        $row[$key]['iog_extension_codes'] = array_unique($iog_extension_codes);
                    }

                    $row[$key]['goods_list'] = $goods_list;

                    $value['self_run'] = $value['get_merchants_shop_information']['self_run'] ?? '';

                    $row[$key]['user_name'] = $this->merchantCommonService->getShopName($value['ru_id'], 1);
                    $row[$key]['self_run'] = $value['self_run'];

                    if (!empty($child_list)) {
                        $row[$key]['shop_name'] = lang('manage/order.to_order_sn2');
                    } else {
                        $row[$key]['shop_name'] = $row[$key]['user_name'];
                    }
                }

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row[$key]['mobile'] = $this->dscRepository->stringToStar($row[$key]['mobile']);
                    $row[$key]['buyer'] = $this->dscRepository->stringToStar($row[$key]['buyer']);
                    $row[$key]['tel'] = $this->dscRepository->stringToStar($row[$key]['tel']);
                    $row[$key]['email'] = $this->dscRepository->stringToStar($row[$key]['email']);
                }
            }
        }

        $arr = ['orders' => $row, 'filter' => $filter, 'page_count' => intval($filter['page_count']), 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得供货商列表
     * @return array    二维数组
     */
    public function getSuppliersList()
    {
        $res = Suppliers::where('is_check', 1)->orderBy('suppliers_name');
        $res = $this->baseRepository->getToArrayGet($res);
        if (!is_array($res)) {
            $res = [];
        }

        return $res;
    }

    /**
     * 取得订单商品
     * @param array $order 订单数组
     * @return array
     */
    public function getOrderGoods($order)
    {
        $goods_list = [];
        $goods_attr = [];

        $res = OrderGoods::where('order_id', $order['order_id']);
        $res = $res->with(['getProducts', 'getOrder']);
        $res = $res->with(['getGoods' => function ($query) {
            $query->with(['getBrand']);
        }]);
        $res = $this->baseRepository->getToArrayGet($res);

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $rate_price = 0;
        }

        foreach ($res as $row) {

            $row['product_sn'] = '';

            if (isset($row['get_products']) && !empty($row['get_products'])) {
                $row['product_sn'] = $row['get_products']['product_sn'];
            }

            $row['iog_extension_code'] = $row['extension_code'];

            $row['model_inventory'] = '';
            $row['model_attr'] = '';
            $row['suppliers_id'] = '';
            $row['storage'] = '';
            $row['goods_thumb'] = '';
            $row['goods_img'] = '';
            $row['bar_code'] = '';
            $row['goods_unit'] = '';

            $row['brand_name'] = '';

            if (isset($row['get_goods']) && !empty($row['get_goods'])) {
                $row['model_inventory'] = $row['get_goods']['model_inventory'];
                $row['model_attr'] = $row['get_goods']['model_attr'];
                $row['suppliers_id'] = $row['get_goods']['suppliers_id'];
                $row['storage'] = $row['get_goods']['goods_number'];
                $row['goods_thumb'] = $row['get_goods']['goods_thumb'];
                $row['goods_img'] = $row['get_goods']['goods_img'];
                $row['bar_code'] = $row['get_goods']['bar_code'];
                $row['goods_unit'] = $row['get_goods']['goods_unit'];

                if (isset($row['get_goods']['get_brand']) && !empty($row['get_goods']['get_brand'])) {
                    $row['brand_name'] = $row['get_goods']['get_brand']['brand_name'];
                }
            }

            $row['order_sn'] = '';
            $row['oi_extension_code'] = '';
            $row['extension_id'] = '';

            if (isset($row['get_order']) && !empty($row['get_order'])) {
                $row['order_sn'] = $row['get_order']['order_sn'];
                $row['oi_extension_code'] = $row['get_order']['extension_code'];
                $row['extension_id'] = $row['get_order']['extension_id'];
            }

            //ecmoban模板堂 --zhuo start
            if ($row['product_id'] > 0) {
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $row['model_attr']);
                $row['goods_storage'] = isset($products['product_number']) ? $products['product_number'] : 0;
            } else {
                if ($row['model_inventory'] == 1) {
                    $row['storage'] = get_warehouse_area_goods($row['warehouse_id'], $row['goods_id'], 'warehouse_goods');
                } elseif ($row['model_inventory'] == 2) {
                    $row['storage'] = get_warehouse_area_goods($row['area_id'], $row['goods_id'], 'warehouse_area_goods');
                }
            }
            //ecmoban模板堂 --zhuo end

            //图片显示
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);

            $row['formated_subtotal'] = $this->dscRepository->getPriceFormat($row['goods_price'] * $row['goods_number']);
            $row['formated_goods_price'] = $this->dscRepository->getPriceFormat($row['goods_price']);

            $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

            if ($row['extension_code'] == 'package_buy') {
                $row['storage'] = '';
                $row['brand_name'] = '';
                $row['package_goods_list'] = $this->getPackageGoodsList($row['goods_id']);

                $activity = get_goods_activity_info($row['goods_id'], ['act_id', 'activity_thumb']);
                if ($activity) {
                    $row['goods_thumb'] = $this->dscRepository->getImagePath($activity['activity_thumb']);
                }
            }

            $ge_res = GoodsExtend::where('goods_id', $row['goods_id']);
            $goods_extend = $this->baseRepository->getToArrayFirst($ge_res);
            if (!empty($goods_extend)) {
                if ($row['is_reality'] == -1 && $goods_extend) {
                    $row['is_reality'] = $goods_extend['is_reality'];
                }
                if ($goods_extend['is_return'] == -1 && $goods_extend) {
                    $row['is_return'] = $goods_extend['is_return'];
                }
                if ($goods_extend['is_fast'] == -1 && $goods_extend) {
                    $row['is_fast'] = $goods_extend['is_fast'];
                }
            }
            //获得退货表数据
            $row['ret_id'] = OrderReturn::where('rec_id', $row['rec_id'])->value('ret_id');
            $row['ret_id'] = $row['ret_id'] ? $row['ret_id'] : 0;

            $row['back_order'] = return_order_info($row['ret_id']);

            $trade_id = $this->orderCommonService->getFindSnapshot($row['order_sn'], $row['goods_id']);
            if ($trade_id) {
                $row['trade_url'] = $this->dscRepository->dscUrl("trade_snapshot.php?act=trade&user_id=" . $row['user_id'] . "&tradeId=" . $trade_id . "&snapshot=true");
            }

            //处理货品id
            $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];

            if (CROSS_BORDER === true) // 跨境多商户
            {
                if ($row['rate_price'] > 0) {
                    $rate_price += $row['rate_price'];
                }
            }
            $goods_list[] = $row;
        }

        $attr = [];
        $arr = [];
        if ($goods_attr) {
            foreach ($goods_attr as $index => $array_val) {

                $array_val = $this->baseRepository->getExplode($array_val);

                if ($array_val) {
                    foreach ($array_val as $value) {
                        $arr = explode(':', $value);//以 : 号将属性拆开
                        $attr[$index][] = @['name' => $arr[0], 'value' => $arr[1]];
                    }
                }
            }
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            return ['goods_list' => $goods_list, 'attr' => $attr, 'rate_price' => $rate_price];
        } else {
            return ['goods_list' => $goods_list, 'attr' => $attr];
        }
    }

    /**
     * 取得礼包列表
     * @param integer $package_id 订单商品表礼包类商品id
     * @return array
     */
    public function getPackageGoodsList($package_id)
    {
        $res = PackageGoods::where('package_id', $package_id);
        $res = $res->with(['getGoods', 'getProducts']);
        $resource = $this->baseRepository->getToArrayGet($res);


        if (!$resource) {
            return [];
        }

        $row = [];

        /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
        $good_product_str = '';
        if (!empty($resource)) {
            foreach ($resource as $_row) {
                $_row['order_goods_number'] = $_row['goods_number'];

                $_row['goods_name'] = '';
                $_row['goods_number'] = '';
                $_row['goods_sn'] = '';
                $_row['is_real'] = '';
                if (isset($_row['get_goods']) && !empty($_row['get_goods'])) {
                    $_row['goods_name'] = $_row['get_goods']['goods_name'];
                    if ($_row['product_id'] < 1) {
                        $_row['goods_number'] = $_row['get_goods']['goods_number'];
                    }
                    $_row['goods_sn'] = $_row['get_goods']['goods_sn'];
                    $_row['is_real'] = $_row['get_goods']['is_real'];
                }

                $_row['goods_attr'] = '';
                $_row['product_sn'] = '';
                if (isset($_row['get_products']) && !empty($_row['get_products'])) {
                    if ($_row['product_id'] > 0) {
                        $_row['goods_number'] = $_row['get_products']['product_number'];
                    }
                    $_row['goods_attr'] = $_row['get_products']['goods_attr'];
                    $_row['product_id'] = $_row['get_products']['product_id'];
                    $_row['product_sn'] = $_row['get_products']['product_sn'];
                }
                $_row['product_id'] = $_row['product_id'] ? $_row['product_id'] : 0;

                if ($_row['product_id'] > 0) {
                    /* 取存商品id */
                    $good_product_str .= ',' . $_row['goods_id'];

                    /* 组合商品id与货品id */
                    $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
                } else {
                    /* 组合商品id与货品id */
                    $_row['g_p'] = $_row['goods_id'];
                }

                //生成结果数组
                $row[] = $_row;
            }
        }
        $good_product_str = trim($good_product_str, ',');

        /* 释放空间 */
        unset($resource, $_row, $sql);

        /* 取商品属性 */
        if ($good_product_str != '') {
            $good_product_str = $this->baseRepository->getExplode($good_product_str);
            $res = GoodsAttr::whereIn('goods_id', $good_product_str);
            $res = $res->with(['getGoodsAttribute' => function ($query) {
                $query->where('attr_type', 1);
            }]);

            $res = $res->orderBy('goods_attr_id');

            $result_goods_attr = $this->baseRepository->getToArrayGet($res);

            $_goods_attr = [];
            foreach ($result_goods_attr as $value) {
                $value['attr_name'] = '';
                if (isset($value['get_goods_attribute']) && !empty($value['get_goods_attribute'])) {
                    $value['attr_name'] = $value['get_goods_attribute']['attr_name'];
                }

                $_goods_attr[$value['goods_attr_id']] = $value;
            }
        }

        /* 过滤货品 */
        $format[0] = "%s:%s[%d] <br>";
        $format[1] = "%s--[%d]";
        foreach ($row as $key => $value) {
            if ($value['goods_attr'] != '') {
                $goods_attr_array = explode('|', $value['goods_attr']);

                $goods_attr = [];
                foreach ($goods_attr_array as $_attr) {
                    $goods_attr[] = sprintf($format[0], $_goods_attr[$_attr]['attr_name'], $_goods_attr[$_attr]['attr_value'], $_goods_attr[$_attr]['attr_price']);
                }

                $row[$key]['goods_attr_str'] = implode('', $goods_attr);
            }

            $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['order_goods_number']);
        }

        return $row;
    }

    /**
     * 订单单个商品或货品的已发货数量
     *
     * @param int $order_id 订单 id
     * @param int $goods_id 商品 id
     * @param int $product_id 货品 id
     *
     * @return  int
     */
    public function orderDeliveryNum($order_id, $goods_id, $product_id = 0)
    {
        $res = DeliveryGoods::where('goods_id', $goods_id)->where(function ($query) {
            $query->whereNull('extension_code')
                ->orWhere('extension_code', '<>', 'package_buy');
        });

        if ($product_id > 0) {
            $res = $res->where('product_id', $product_id);
        }

        $sum = $res->whereHas('getDeliveryOrder', function ($query) use ($order_id) {
            $query->where('status', 0)->where('order_id', $order_id);
        })->sum('send_number');

        if (empty($sum)) {
            $sum = 0;
        }

        return $sum;
    }

    /**
     * 判断订单是否已发货（含部分发货）
     * @param int $order_id 订单 id
     * @return  int     1，已发货；0，未发货
     */
    public function orderDeliveryed($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        $sum = DeliveryOrder::where('order_id', $order_id)->where('status', 0)->count();

        if ($sum) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 更新订单商品信息
     * @param int $order_id 订单 id
     * @param array $_sended Array(‘商品id’ => ‘此单发货数量’)
     * @param array $goods_list
     * @return  Bool
     */
    public function updateOrderGoods($order_id, $_sended, $goods_list = [])
    {
        if (!is_array($_sended) || empty($order_id)) {
            return false;
        }

        foreach ($_sended as $key => $value) {
            // 超值礼包
            if (is_array($value)) {
                if (!is_array($goods_list)) {
                    $goods_list = [];
                }

                foreach ($goods_list as $goods) {
                    if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list']))) {
                        continue;
                    }

                    $goods['package_goods_list'] = $this->packageGoods($goods['package_goods_list'], $goods['goods_number'], $goods['order_id'], $goods['extension_code'], $goods['goods_id']);
                    $pg_is_end = true;

                    foreach ($goods['package_goods_list'] as $pg_key => $pg_value) {
                        if ($pg_value['order_send_number'] != $pg_value['sended']) {
                            $pg_is_end = false; // 此超值礼包，此商品未全部发货

                            break;
                        }
                    }

                    // 超值礼包商品全部发货后更新订单商品库存
                    if ($pg_is_end) {
                        $goods_number = OrderGoods::where('order_id', $order_id)
                            ->where('goods_id', $goods['goods_id'])
                            ->value('goods_number');
                        $goods_number = $goods_number ? $goods_number : '';

                        $data = ['send_number' => $goods_number];
                        OrderGoods::where('order_id', $order_id)
                            ->where('goods_id', $goods['goods_id'])
                            ->update($data);
                    }
                }
            } // 商品（实货）（货品）
            elseif (!is_array($value)) {
                /* 检查是否为商品（实货）（货品） */
                foreach ($goods_list as $goods) {
                    if ($goods['rec_id'] == $key && $goods['is_real'] == 1) {
                        OrderGoods::where('order_id', $order_id)
                            ->where('rec_id', $key)
                            ->increment('send_number', $value);

                        break;
                    }
                }
            }
        }

        return true;
    }

    /**
     * 更新订单虚拟商品信息
     * @param int $order_id 订单 id
     * @param array $_sended Array(‘商品id’ => ‘此单发货数量’)
     * @param array $virtual_goods 虚拟商品列表
     * @return  Bool
     */
    public function updateOrderVirtualGoods($order_id, $_sended, $virtual_goods)
    {
        if (!is_array($_sended) || empty($order_id)) {
            return false;
        }
        if (empty($virtual_goods)) {
            return true;
        } elseif (!is_array($virtual_goods)) {
            return false;
        }

        foreach ($virtual_goods as $goods) {
            $res = OrderGoods::where('order_id', $order_id)
                ->where('goods_id', $goods['goods_id'])
                ->increment('send_number', $goods['num']);

            if ($res < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * 订单中的商品是否已经全部发货
     * @param int $order_id 订单 id
     * @return  int     1，全部发货；0，未全部发货
     */
    public function getOrderFinish($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        $sum = OrderGoods::where('order_id', $order_id)
            ->whereColumn('goods_number', '>', 'send_number')
            ->count('rec_id');

        $sum = $sum ? $sum : 0;

        if (empty($sum)) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 判断订单的发货单是否全部发货
     * @param int $order_id 订单 id
     * @return  int     1，全部发货；0，未全部发货；-1，部分发货；-2，完全没发货；
     */
    public function getAllDeliveryFinish($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        /* 未全部分单 */
        if (!$this->getOrderFinish($order_id)) {
            return $return_res;
        } /* 已全部分单 */
        else {
            // 是否全部发货
            $sum = DeliveryOrder::where('order_id', $order_id)->where('status', 2)->count();
            $sum = $sum ? $sum : 0;

            // 全部发货
            if (empty($sum)) {
                $return_res = 1;
            } // 未全部发货
            else {
                /* 订单全部发货中时：当前发货单总数 */
                $_sum = DeliveryOrder::where('order_id', $order_id)->where('status', '<>', 1)->count();
                $_sum = $_sum ? $_sum : 0;

                if ($_sum == $sum) {
                    $return_res = -2; // 完全没发货
                } else {
                    $return_res = -1; // 部分发货
                }
            }
        }
        return $return_res;
    }

    /**
     * 删除发货单(不包括已退货的单子)
     * @param int $order_id 订单 id
     * @return  int     1，成功；0，失败
     */
    public function delOrderDelivery($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        $delivery_id = DeliveryOrder::where('order_id', $order_id)
            ->where('status', 0)
            ->value('delivery_id');
        $delivery_id = $delivery_id ? $delivery_id : 0;

        $query = DeliveryOrder::where('order_id', $order_id)
            ->where('status', 0)
            ->delete();
        DeliveryGoods::where('delivery_id', $delivery_id)->delete();


        if ($query) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 删除订单所有相关单子
     * @param int $order_id 订单 id
     * @param int $action_array 操作列表 Array('delivery', 'back', ......)
     * @return  int     1，成功；0，失败
     */
    public function delDelivery($order_id, $action_array)
    {
        $return_res = 0;

        if (empty($order_id) || empty($action_array)) {
            return $return_res;
        }

        $query_delivery = 1;
        $query_back = 1;
        if (in_array('delivery', $action_array)) {
            $delivery_id = DeliveryOrder::where('order_id', $order_id)->value('delivery_id');
            $delivery_id = $delivery_id ? $delivery_id : 0;

            $query_delivery = DeliveryOrder::where('order_id', $order_id)->delete();
            DeliveryGoods::where('delivery_id', $delivery_id)->delete();

        }
        if (in_array('back', $action_array)) {
            $back_id = BackOrder::where('order_id', $order_id)->value('back_id');
            $back_id = $back_id ? $back_id : 0;

            $query_back = BackOrder::where('order_id', $order_id)->delete();
            BackGoods::where('back_id', $back_id)->delete();

        }

        if ($query_delivery && $query_back) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     *  获取发货单列表信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function deliveryList()
    {
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();

        //ecmoban模板堂 --zhuo end

        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['delivery_sn'] = empty($_REQUEST['delivery_sn']) ? '' : trim($_REQUEST['delivery_sn']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['order_id'] = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
        $filter['goods_id'] = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
        if ($aiax == 1 && !empty($_REQUEST['consignee'])) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }

        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['status'] = isset($_REQUEST['status']) ? $_REQUEST['status'] : -1;
        $filter['order_referer'] = isset($_REQUEST['order_referer']) ? trim($_REQUEST['order_referer']) : '';

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'update_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        $res = DeliveryOrder::whereRaw(1);

        if ($filter['order_sn']) {
            $res = $res->where('order_sn', 'LIKE', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }
        if ($filter['goods_id']) {
            $goods_id = $filter['goods_id'];
            $res = $res->where(function ($query) use ($goods_id) {
                $query->whereHas('getDeliveryGoods', function ($query) use ($goods_id) {
                    $query->where('goods_id', $goods_id);
                });
            });
        }
        if ($filter['consignee']) {
            $res = $res->where('consignee', 'LIKE', '%' . mysql_like_quote($filter['consignee']) . '%');
        }
        if ($filter['status'] >= 0) {
            $res = $res->where('status', 'LIKE', '%' . mysql_like_quote($filter['status']) . '%');
        }
        if ($filter['delivery_sn']) {
            $res = $res->where('delivery_sn', 'LIKE', '%' . mysql_like_quote($filter['delivery_sn']) . '%');
        }

        /* 获取管理员信息 */
        $admin_info = admin_info();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0) {
            $res = $res->where('agency_id', $admin_info['agency_id']);
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0) {
            $res = $res->where('suppliers_id', $admin_info['suppliers_id']);
        }

        if ($GLOBALS['_CFG']['region_store_enabled']) {
            //卖场
            $res = $res->where(function ($query) {
                $query->whereHas('getOrderGoods');
            });
            $res = $this->dscRepository->getWhereRsid($res, $filter['rs_id']);

        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        $ru_id = $adminru['ru_id'];
        $res = $res->whereHas('getOrderInfo', function ($query) use ($ru_id) {
            if ($ru_id > 0) {
                $query->where('ru_id', $ru_id);
            }
        });

        if ($filter['seller_list']) {
            $res = $res->whereHas('getOrderInfo', function ($query) use ($ru_id) {
                $query->where('ru_id', '>', 0);
            });

        } else {
            $res = $res->whereHas('getOrderInfo', function ($query) use ($ru_id) {
                $query->where('ru_id', 0);
            });
        }
        if ($filter['order_referer']) {
            if ($filter['order_referer'] == 'pc') {
                $res = $res->whereHas('getOrderInfo', function ($query) {
                    $query->whereNotIn('referer', ['mobile', 'touch', 'ecjia-cashdesk']);
                });
            } else {
                $order_referer = $filter['order_referer'];
                $res = $res->whereHas('getOrderInfo', function ($query) use ($order_referer) {
                    $query->where('referer', $order_referer);
                });
            }
        }

        /* 记录总数 */
        $filter['record_count'] = $res->distinct('back_id')->count();

        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 获取供货商列表 */
        $suppliers_list = $this->getSuppliersList();
        $_suppliers_list = [];
        foreach ($suppliers_list as $value) {
            $_suppliers_list[$value['suppliers_id']] = $value['suppliers_name'];
        }

        /* 查询 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);

        $row = $this->baseRepository->getToArrayGet($res);

        /* 格式化数据 */
        foreach ($row as $key => $value) {

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$key]['email'] = $this->dscRepository->stringToStar($value['email']);
                $row[$key]['mobile'] = $this->dscRepository->stringToStar($value['mobile']);
                $row[$key]['consignee'] = $this->dscRepository->stringToStar($value['consignee']);
                $row[$key]['tel'] = $this->dscRepository->stringToStar($value['tel']);
            }

            $row[$key]['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $value['add_time']);
            $row[$key]['update_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $value['update_time']);
            if ($value['status'] == 1) {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
            } elseif ($value['status'] == 2) {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][2];
            } else {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
            }
            $row[$key]['suppliers_name'] = isset($_suppliers_list[$value['suppliers_id']]) ? $_suppliers_list[$value['suppliers_id']] : '';

            $_goods_thumb = isset($row[$key]['goods_thumb']) && $row[$key]['goods_thumb'] ? $this->dscRepository->getImagePath($row[$key]['goods_thumb']) : '';
            $row[$key]['goods_thumb'] = $_goods_thumb;

            $ru_id = OrderGoods::where('order_id', $value['order_id'])->value('ru_id');
            $ru_id = $ru_id ? $ru_id : 0;

            $row[$key]['ru_name'] = $this->merchantCommonService->getShopName($ru_id, 1); //ecmoban模板堂 --zhuo
        }

        $arr = ['delivery' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     *  获取退货单列表信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function backList()
    {
        $adminru = get_admin_ru_id();

        //取消获取cookie信息
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['delivery_sn'] = empty($_REQUEST['delivery_sn']) ? '' : trim($_REQUEST['delivery_sn']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['order_id'] = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
        if ($aiax == 1 && !empty($_REQUEST['consignee'])) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }

        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['order_referer'] = isset($_REQUEST['order_referer']) ? trim($_REQUEST['order_referer']) : '';
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'update_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end
        $res = BackOrder::whereRaw(1);

        if ($filter['order_sn']) {
            $res = $res->where('order_sn', 'LIKE', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }
        if ($filter['consignee']) {
            $res = $res->where('consignee', 'LIKE', '%' . mysql_like_quote($filter['consignee']) . '%');
        }
        if ($filter['delivery_sn']) {
            $res = $res->where('delivery_sn', 'LIKE', '%' . mysql_like_quote($filter['delivery_sn']) . '%');
        }

        $ru_id = $adminru['ru_id'];
        $res = $res->whereHas('getOrderInfo', function ($query) use ($ru_id) {
            if ($ru_id > 0) {
                $query->where('ru_id', $ru_id);
            }
        });

        if ($filter['order_referer']) {
            if ($filter['order_referer'] == 'pc') {
                $res = $res->whereHas('getOrderInfo', function ($query) {
                    $query->whereNotIn('referer', ['mobile', 'touch', 'ecjia-cashdesk']);
                });
            } else {
                $order_referer = $filter['order_referer'];
                $res = $res->whereHas('getOrderInfo', function ($query) use ($order_referer) {
                    $query->where('referer', $order_referer);
                });
            }
        }

        /* 获取管理员信息 */
        $admin_info = admin_info();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0) {
            $res = $res->where('agency_id', $admin_info['agency_id']);
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0) {
            $res = $res->where('suppliers_id', $admin_info['suppliers_id']);
        }

        if ($GLOBALS['_CFG']['region_store_enabled']) {
            //卖场
            $res = $res->where(function ($query) {
                $query->with(['getOrderGoods']);
            });

            $res = $this->dscRepository->getWhereRsid($res, $filter['rs_id']);
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        if ($filter['seller_list']) {
            $res = $res->whereHas('getOrderInfo', function ($query) {
                $query->where('ru_id', '>', 0);
            });
        } else {
            $res = $res->whereHas('getOrderInfo', function ($query) {
                $query->where('ru_id', 0);
            });
        }

        /* 记录总数 */
        $filter['record_count'] = $res->distinct('back_id')->count();

        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);

        $row = $this->baseRepository->getToArrayGet($res);
        /* 格式化数据 */
        foreach ($row as $key => $value) {
            $row[$key]['return_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $value['return_time']);
            $row[$key]['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $value['add_time']);
            $row[$key]['update_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $value['update_time']);
            if ($value['status'] == 1) {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
            } else {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
            }
        }
        $arr = ['back' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得发货单信息
     * @param int $delivery_order 发货单id（如果delivery_order > 0 就按id查，否则按sn查）
     * @param string $delivery_sn 发货单号
     * @return  array   发货单信息（金额都有相应格式化的字段，前缀是formated_）
     */
    public function deliveryOrderInfo($delivery_id, $delivery_sn = '')
    {
        $return_order = [];
        if (empty($delivery_id) || !is_numeric($delivery_id)) {
            return $return_order;
        }

        $res = DeliveryOrder::whereRaw(1);
        /* 获取管理员信息 */
        $admin_info = admin_info();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0) {
            $res = $res->where('agency_id', $admin_info['agency_id']);
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0) {
            $res = $res->where('suppliers_id', $admin_info['suppliers_id']);
        }

        if ($delivery_id > 0) {
            $res = $res->where('delivery_id', $delivery_id);
        } else {
            $res = $res->where('delivery_sn', $delivery_sn);
        }

        $res = $res->with([
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

        $delivery = $this->baseRepository->getToArrayFirst($res);

        if ($delivery) {

            /* 取得区域名 */
            $province = $delivery['get_region_province']['region_name'] ?? '';
            $city = $delivery['get_region_city']['region_name'] ?? '';
            $district = $delivery['get_region_district']['region_name'] ?? '';
            $street = $delivery['get_region_street']['region_name'] ?? '';
            $delivery['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

            /* 格式化金额字段 */
            $delivery['formated_insure_fee'] = $this->dscRepository->getPriceFormat($delivery['insure_fee'], false);
            $delivery['formated_shipping_fee'] = $this->dscRepository->getPriceFormat($delivery['shipping_fee'], false);

            /* 格式化时间字段 */
            $delivery['formated_add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $delivery['add_time']);
            $delivery['formated_update_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $delivery['update_time']);

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $delivery['email'] = $this->dscRepository->stringToStar($delivery['email']);
                $delivery['tel'] = $this->dscRepository->stringToStar($delivery['tel']);
                $delivery['mobile'] = $this->dscRepository->stringToStar($delivery['mobile']);
                $delivery['consignee'] = $this->dscRepository->stringToStar($delivery['consignee']);
            }

            $return_order = $delivery;
        }

        return $return_order;
    }

    /**
     *  取得退货单信息
     *
     * @param int $back_id 退货单 id（如果 back_id > 0 就按 id 查，否则按 sn 查）
     * @return array
     */
    public function backOrderInfo($back_id = 0)
    {
        $return_order = [];
        if (empty($back_id) || !is_numeric($back_id)) {
            return $return_order;
        }

        $res = BackOrder::where('back_id', $back_id);

        /* 获取管理员信息 */
        $admin_info = admin_info();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0) {
            $res = $res->where('agency_id', $admin_info['agency_id']);
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0) {
            $res = $res->where('suppliers_id', $admin_info['suppliers_id']);
        }

        $res = $res->with([
            'getOrderInfo',
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

        $back = $this->baseRepository->getToArrayFirst($res);

        if ($back) {

            /* 取得区域名 */
            $province = $back['get_region_province']['region_name'] ?? '';
            $city = $back['get_region_city']['region_name'] ?? '';
            $district = $back['get_region_district']['region_name'] ?? '';
            $street = $back['get_region_street']['region_name'] ?? '';
            $back['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

            /* 格式化金额字段 */
            $back['formated_insure_fee'] = $this->dscRepository->getPriceFormat($back['insure_fee'], false);
            $back['formated_shipping_fee'] = $this->dscRepository->getPriceFormat($back['shipping_fee'], false);

            $order = $back['get_order_info'] ?? [];

            $back['is_zc_order'] = $order['is_zc_order'] ?? 0;

            /* 格式化时间字段 */
            $back['formated_add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $back['add_time']);
            $back['formated_update_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $back['update_time']);
            $back['formated_return_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $back['return_time']);

            $return_order = $back;
        }

        return $return_order;
    }

    /**
     * 超级礼包发货数处理
     * @param array   超级礼包商品列表
     * @param int     发货数量
     * @param int     订单ID
     * @param varchar 虚拟代码
     * @param int     礼包ID
     * @return  array   格式化结果
     */
    public function packageGoods(&$package_goods, $goods_number, $order_id, $extension_code, $package_id)
    {
        $return_array = [];

        if (count($package_goods) == 0 || !is_numeric($goods_number)) {
            return $return_array;
        }

        foreach ($package_goods as $key => $value) {
            $return_array[$key] = $value;
            $return_array[$key]['order_send_number'] = $value['order_goods_number'] * $goods_number;
            $return_array[$key]['sended'] = $this->packageSended($package_id, $value['goods_id'], $order_id, $extension_code, $value['product_id']);
            $return_array[$key]['send'] = ($value['order_goods_number'] * $goods_number) - $return_array[$key]['sended'];
            $return_array[$key]['storage'] = $value['goods_number'];

            if ($return_array[$key]['send'] <= 0) {
                $return_array[$key]['send'] = lang('admin/order.act_good_delivery');
                $return_array[$key]['readonly'] = 'readonly="readonly"';
            }

            /* 是否缺货 */
            if ($return_array[$key]['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1') {
                $return_array[$key]['send'] = lang('admin/order.act_good_vacancy');
                $return_array[$key]['readonly'] = 'readonly="readonly"';
            }
        }
        return $return_array;
    }

    /**
     * 获取超级礼包商品已发货数
     *
     * @param int $package_id 礼包ID
     * @param int $goods_id 礼包的产品ID
     * @param int $order_id 订单ID
     * @param varchar $extension_code 虚拟代码
     * @param int $product_id 货品id
     *
     * @return  int     数值
     */
    public function packageSended($package_id, $goods_id, $order_id, $extension_code, $product_id = 0)
    {
        if (empty($package_id) || empty($goods_id) || empty($order_id) || empty($extension_code)) {
            return false;
        }

        $res = DeliveryGoods::where('parent_id', $package_id)
            ->where('goods_id', $goods_id)
            ->where('extension_code', $extension_code);
        if ($product_id > 0) {
            $res = $res->where('product_id', $product_id);
        }

        $res = $res->whereHas('getDeliveryOrder', function ($query) use ($order_id) {
            $query->whereIn('status', [0, 2])->where('order_id', $order_id);
        });

        $send = $res->sum('send_number');
        return empty($send) ? 0 : $send;
    }

    /**
     * 改变订单中商品库存
     * @param int $order_id 订单 id
     * @param array $_sended Array(‘商品id’ => ‘此单发货数量’)
     * @param array $goods_list
     * @return  Bool
     */
    public function changeOrderGoodsStorageSplit($order_id, $_sended, $goods_list = [])
    {
        /* 参数检查 */
        if (!is_array($_sended) || empty($order_id)) {
            return false;
        }

        foreach ($_sended as $key => $value) {
            // 商品（超值礼包）
            if (is_array($value)) {
                if (!is_array($goods_list)) {
                    $goods_list = [];
                }
                foreach ($goods_list as $goods) {
                    if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list']))) {
                        continue;
                    }

                    // 超值礼包无库存，只减超值礼包商品库存
                    foreach ($goods['package_goods_list'] as $package_goods) {
                        if (!isset($value[$package_goods['goods_id']])) {
                            continue;
                        }

                        // 减库存：商品（超值礼包）（实货）、商品（超值礼包）（虚货）
                        Goods::where('goods_id', $package_goods['goods_id'])->decrement('goods_number', $value[$package_goods['goods_id']]);
                    }
                }
            } // 商品（实货）
            elseif (!is_array($value)) {
                /* 检查是否为商品（实货） */
                foreach ($goods_list as $goods) {
                    if ($goods['rec_id'] == $key && $goods['is_real'] == 1) {
                        Goods::where('goods_id', $goods['goods_id'])->decrement('goods_number', $value);
                        break;
                    }
                }
            }
        }

        return true;
    }

    /**
     * 删除发货单时进行退货
     *
     * @access   public
     * @param int $delivery_id 发货单id
     * @param array $delivery_order 发货单信息数组
     *
     * @return  void
     */
    public function deliveryReturnGoods($delivery_id, $delivery_order)
    {
        /* 查询：取得发货单商品 */

        $res = DeliveryGoods::where('delivery_id', $delivery_order['delivery_id']);
        $goods_list = $this->baseRepository->getToArrayGet($res);

        /* 更新： */
        foreach ($goods_list as $key => $val) {
            OrderGoods::where('order_id', $delivery_order['order_id'])
                ->where('goods_id', $goods_list[$key]['goods_id'])
                ->decrement('send_number', $goods_list[$key]['send_number']);

        }
        $data = [
            'shipping_status' => 0,
            'order_status' => 1
        ];
        OrderInfo::where('order_id', $delivery_order['order_id'])->update($data);

    }

    /**
     * 删除发货单时删除其在订单中的发货单号
     *
     * @access   public
     * @param int $order_id 定单id
     * @param string $delivery_invoice_no 发货单号
     *
     * @return  void
     */
    public function delOrderInvoiceNo($order_id, $delivery_invoice_no)
    {
        /* 查询：取得订单中的发货单号 */
        $order_invoice_no = OrderInfo::where('order_id', $order_id)->value('invoice_no');
        $order_invoice_no = $order_invoice_no ? $order_invoice_no : '';

        /* 如果为空就结束处理 */
        if (empty($order_invoice_no)) {
            return;
        }

        /* 去除当前发货单号 */
        $order_array = explode(',', $order_invoice_no);
        $delivery_array = explode(',', $delivery_invoice_no);

        foreach ($order_array as $key => $invoice_no) {
            if ($ii = array_search($invoice_no, $delivery_array)) {
                unset($order_array[$key], $delivery_array[$ii]);
            }
        }

        $arr['invoice_no'] = implode(',', $order_array);
        update_order($order_id, $arr);
    }

    //ecmoban模板堂 --zhuo start
    public function downloadOrderList($result)
    {
        if (empty($result)) {
            return $this->commonManageService->i(lang('admin/common.not_fuhe_date'));
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $data = $this->commonManageService->i(lang('admin/common.cbec_download_orderlist') . "\n");
        } else {
            $data = $this->commonManageService->i(lang('admin/common.download_orderlist_notic') . "\n");
        }

        $lang_goods = lang('admin/common.download_goods');

        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            // 订单商品信息
            $goods_info = '';
            $goods_sn = '';
            if (!empty($result[$i]['goods_list'])) {
                foreach ($result[$i]['goods_list'] as $j => $g) {
                    if (!empty($g['goods_attr'])) {
                        $g['goods_unit'] = $g['goods_unit'] ?? '';
                        $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ' ' . rtrim($g['goods_attr']) . ")" . "\r\n";
                    } else {
                        $g['goods_unit'] = $g['goods_unit'] ?? '';
                        $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ")" . "\r\n";
                    }
                    $goods_sn .= $g['goods_sn'] . "\r\n";
                }
                $goods_info = rtrim($goods_info); // 去除最末位换行符
                $goods_info = "\"$goods_info\""; // 转义字符是关键 不然不是表格内换行
                $goods_sn = rtrim($goods_sn); // 去除最末位换行符
                $goods_sn = "\"$goods_sn\""; // 转义字符是关键 不然不是表格内换行
            }

            $order_sn = $this->commonManageService->i('#' . $result[$i]['order_sn']); //订单号前加'#',避免被四舍五入 by wu
            $order_user = $this->commonManageService->i($result[$i]['buyer']);
            $order_time = $this->commonManageService->i($result[$i]['short_order_time']);
            $consignee = $this->commonManageService->i($result[$i]['consignee']);
            $tel = !empty($result[$i]['mobile']) ? $this->commonManageService->i($result[$i]['mobile']) : $this->commonManageService->i($result[$i]['tel']);
            $address = $this->commonManageService->i(addslashes(str_replace(",", "，", "[" . $result[$i]['region'] . "] " . $result[$i]['address'])));
            $goods_info = $this->commonManageService->i($goods_info); // 商品信息
            $goods_sn = $this->commonManageService->i($goods_sn); // 商品货号
            $order_amount = $this->commonManageService->i($result[$i]['order_amount']);
            $goods_amount = $this->commonManageService->i($result[$i]['goods_amount']);
            $shipping_fee = $this->commonManageService->i($result[$i]['old_shipping_fee']);//配送费用
            $insure_fee = $this->commonManageService->i($result[$i]['insure_fee']);//保价费用
            $pay_fee = $this->commonManageService->i($result[$i]['pay_fee']);//支付费用
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $rate_fee = $this->commonManageService->i($result[$i]['rate_fee']);//综合税费
                $rel_name = $this->commonManageService->i($result[$i]['rel_name']);//真实姓名
                $id_num = $this->commonManageService->i('#' . $result[$i]['id_num']);//身份证号
            }
            $surplus = $this->commonManageService->i($result[$i]['surplus']);//余额费用
            $money_paid = $this->commonManageService->i($result[$i]['money_paid']);//已付款金额
            $integral_money = $this->commonManageService->i($result[$i]['integral_money']);//积分金额
            $bonus = $this->commonManageService->i($result[$i]['bonus']);//红包金额
            $tax = $this->commonManageService->i($result[$i]['tax']);//发票税额
            $discount = $this->commonManageService->i($result[$i]['discount']);//折扣金额
            $coupons = $this->commonManageService->i($result[$i]['coupons']);//优惠券金额
            $value_card = $this->commonManageService->i($result[$i]['value_card']); // 储值卡
            $order_status = $this->commonManageService->i(preg_replace("/\<.+?\>/", "", $GLOBALS['_LANG']['os'][$result[$i]['order_status']])); //去除标签
            $seller_name = isset($result[$i]['user_name']) ? $this->commonManageService->i($result[$i]['user_name']) : ''; //商家名称
            $pay_status = $this->commonManageService->i($GLOBALS['_LANG']['ps'][$result[$i]['pay_status']]);
            $shipping_status = $this->commonManageService->i($GLOBALS['_LANG']['ss'][$result[$i]['shipping_status']]);
            $froms = $this->commonManageService->i($result[$i]['referer']);

            if ($froms == 'touch') {
                $froms = "WAP";
            } elseif ($froms == 'mobile') {
                $froms = "APP";
            } elseif ($froms == 'H5') {
                $froms = "H5";
            } elseif ($froms == 'wxapp') {
                $froms = $GLOBALS['_LANG']['wxapp'];
            } elseif ($froms == 'ecjia-cashdesk') {
                $froms = $GLOBALS['_LANG']['cashdesk'];
            } else {
                $froms = "PC";
            }

            $pay_name = $this->commonManageService->i($result[$i]['pay_name']);
            $total_fee = $this->commonManageService->i($result[$i]['total_fee']); // 总金额
            $total_fee_order = $this->commonManageService->i($result[$i]['total_fee_order']); // 订单总金额
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $data .= $order_sn . ',' . $seller_name . ',' . $order_user . ',' . $rel_name . ',' . $id_num . ',' .
                    $order_time . ',' . $consignee . ',' . $tel . ',' .
                    $address . ',' . $goods_info . ',' . $goods_sn . ',' . $goods_amount . ',' . $tax . ',' .
                    $shipping_fee . ',' . $insure_fee . ',' .
                    $pay_fee . ',' . $rate_fee . ',' . $total_fee . ',' . $discount . ',' . $total_fee_order . ',' .
                    $surplus . ',' . $integral_money . ',' . $bonus . ',' .
                    $coupons . ',' . $value_card . ',' . $money_paid . ',' . $order_amount . ',' .
                    $order_status . ',' . $pay_status . ',' . $shipping_status . ',' . $froms . ',' . $pay_name . "\n";
            } else {
                $data .= $order_sn . ',' . $seller_name . ',' . $order_user . ',' .
                    $order_time . ',' . $consignee . ',' . $tel . ',' .
                    $address . ',' . $goods_info . ',' . $goods_sn . ',' . $goods_amount . ',' . $tax . ',' .
                    $shipping_fee . ',' . $insure_fee . ',' .
                    $pay_fee . ',' . $total_fee . ',' . $discount . ',' . $total_fee_order . ',' .
                    $surplus . ',' . $integral_money . ',' . $bonus . ',' .
                    $coupons . ',' . $value_card . ',' . $money_paid . ',' . $order_amount . ',' .
                    $order_status . ',' . $pay_status . ',' . $shipping_status . ',' . $froms . ',' . $pay_name . "\n";
            }

        }
        return $data;
    }

    /**
     * 输出导入数据
     * @access   public
     * @param array $result
     * @return  void
     */
    public function downloadOrderListContent($result)
    {

        $lang_goods = lang('admin/common.download_goods');

        $count = count($result);
        $order_list = [];
        for ($i = 0; $i < $count; $i++) {
            // 订单商品信息
            $goods_info = '';
            $goods_sn = '';
            if (!empty($result[$i]['goods_list'])) {
                foreach ($result[$i]['goods_list'] as $j => $g) {
                    if (!empty($g['goods_attr'])) {
                        $g['goods_unit'] = $g['goods_unit'] ?? '';
                        $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ' ' . rtrim($g['goods_attr']) . ")" . "\r\n";
                    } else {
                        $g['goods_unit'] = $g['goods_unit'] ?? '';
                        $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ")" . "\r\n";
                    }
                    $goods_sn .= $g['goods_sn'] . "\r\n";
                }
                $goods_info = rtrim($goods_info); // 去除最末位换行符
                $row['goods_info'] = "\"$goods_info\""; // 转义字符是关键 不然不是表格内换行
                $goods_sn = rtrim($goods_sn); // 去除最末位换行符

            }

            $row['order_sn'] = $result[$i]['order_sn']; //订单号前加'#',避免被四舍五入 by wu
            $row['order_user'] = $result[$i]['buyer'];
            $row['order_time'] = $result[$i]['short_order_time'];
            $row['consignee'] = $result[$i]['consignee'];
            $row['tel'] = !empty($result[$i]['mobile']) ? $result[$i]['mobile'] : $result[$i]['tel'];
            $row['address'] = addslashes(str_replace(",", "，", "[" . $result[$i]['region'] . "] " . $result[$i]['address']));
            $row['goods_info'] = $goods_info; // 商品信息
            $row['goods_sn'] = $goods_sn; // 商品货号
            $row['order_amount'] = $result[$i]['order_amount'];
            $row['goods_amount'] = $result[$i]['goods_amount'];
            $row['shipping_fee'] = $result[$i]['old_shipping_fee'];//配送费用
            $row['insure_fee'] = $result[$i]['insure_fee'];//保价费用
            $row['pay_fee'] = $result[$i]['pay_fee'];//支付费用
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $row['rate_fee'] = $result[$i]['rate_fee'];//综合税费
                $row['rel_name'] = $result[$i]['rel_name'];//真实姓名
                $row['id_num'] = $result[$i]['id_num'];//身份证号
            }
            $row['surplus'] = $result[$i]['surplus'];//余额费用
            $row['money_paid'] = $result[$i]['money_paid'];//已付款金额
            $row['integral_money'] = $result[$i]['integral_money'];//积分金额
            $row['bonus'] = $result[$i]['bonus'];//红包金额
            $row['tax'] = $result[$i]['tax'];//发票税额
            $row['discount'] = $result[$i]['discount'];//折扣金额
            $row['coupons'] = $result[$i]['coupons'];//优惠券金额
            $row['value_card'] = $result[$i]['value_card']; // 储值卡
            $row['order_status'] = preg_replace("/\<.+?\>/", "", $GLOBALS['_LANG']['os'][$result[$i]['order_status']]); //去除标签
            $row['seller_name'] = isset($result[$i]['user_name']) ? $result[$i]['user_name'] : ''; //商家名称
            $row['pay_status'] = $GLOBALS['_LANG']['ps'][$result[$i]['pay_status']];
            $row['shipping_status'] = $GLOBALS['_LANG']['ss'][$result[$i]['shipping_status']];
            $froms = $result[$i]['referer'];

            if ($froms == 'touch') {
                $row['froms'] = "WAP";
            } elseif ($froms == 'mobile') {
                $row['froms'] = "APP";
            } elseif ($froms == 'H5') {
                $row['froms'] = "H5";
            } elseif ($froms == 'wxapp') {
                $row['froms'] = $GLOBALS['_LANG']['wxapp'];
            } elseif ($froms == 'ecjia-cashdesk') {
                $row['froms'] = $GLOBALS['_LANG']['cashdesk'];
            } else {
                $row['froms'] = "PC";
            }

            $row['pay_name'] = $result[$i]['pay_name'];
            $row['total_fee'] = $result[$i]['total_fee']; // 总金额
            $row['total_fee_order'] = $result[$i]['total_fee_order']; // 订单总金额

            $order_list[] = $row;
        }

        return $order_list;
    }

    //ecmoban模板堂 --zhuo end
    public function getCauseCatLevel($parent_id = 0)
    {
        $res = [];
        $level = isset($level) ? $level : 0;

        $res = ReturnCause::where('parent_id', $parent_id)
            ->withCount(['getReturnCauseChild as has_children'])
            ->groupBy('cause_id')
            ->orderBy('parent_id')
            ->orderBy('sort_order');
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $row) {
                $res[$k]['level'] = $level;
            }
        }

        return $res;
    }

    /* 通过订单号获取订单ID */
    public function orderId($sn)
    {
        $one = OrderInfo::where('order_sn', $sn)->value('order_id');
        $one = $one ? $one : 0;
        return $one;
    }

    //ecmoban模板堂 --zhuo start

    /**
     * 取得收货人地址列表
     * @param int $user_id 用户编号
     * @return  array
     */
    public function getConsigneeLog($address_id = 0, $user_id = 0)
    {
        $res = UserAddress::where('user_id', $user_id)->where('address_id', $address_id);
        $user_address = $this->baseRepository->getToArrayFirst($res);

        return $user_address;
    }

    /**
     * 获得指定国家的所有省份
     *
     * @access      public
     * @param int     country    国家的编号
     * @return      array
     */
    public function getRegionsLog($type = 0, $parent = 0)
    {
        $res = Region::where('region_type', $type)->where('parent_id', $parent);
        $region_list = $this->baseRepository->getToArrayGet($res);

        return $region_list;
    }

    public function getOrderPayment($pay_id)
    {
        //获取支付方式code
        $pay_code = Payment::where('pay_id', $pay_id)->value('pay_code');
        $pay_code = $pay_code ? $pay_code : 0;

        return $pay_code;
    }

    /**
     * 后台批量添加订单
     * 状态分组
     *
     * @return array
     */
    public function BatchAddOrderStatus()
    {
        $this->dscRepository->helpersLang(['order'], 'admin');

        $arr = [
            [
                //已确认，未付款，未发货
                'status' => [
                    'order_status' => OS_CONFIRMED,
                    'pay_status' => PS_UNPAYED,
                    'shipping_status' => SS_UNSHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_CONFIRMED] . ',' . $GLOBALS['_LANG']['ps'][PS_UNPAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_UNSHIPPED]
            ],
            [
                //已分单，未付款，已发货
                'status' => [
                    'order_status' => OS_SPLITED,
                    'pay_status' => PS_UNPAYED,
                    'shipping_status' => SS_SHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_SPLITED] . ',' . $GLOBALS['_LANG']['ps'][PS_UNPAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_SHIPPED]
            ],
            [
                //已确认，已付款，收货确认
                'status' => [
                    'order_status' => OS_CONFIRMED,
                    'pay_status' => PS_PAYED,
                    'shipping_status' => SS_RECEIVED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_CONFIRMED] . ',' . $GLOBALS['_LANG']['ps'][PS_PAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_RECEIVED]
            ],
            [
                //已确认，已付款，未发货
                'status' => [
                    'order_status' => OS_CONFIRMED,
                    'pay_status' => PS_PAYED,
                    'shipping_status' => SS_UNSHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_CONFIRMED] . ',' . $GLOBALS['_LANG']['ps'][PS_PAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_UNSHIPPED]
            ],
            [
                //已分单，已付款，已发货
                'status' => [
                    'order_status' => OS_SPLITED,
                    'pay_status' => PS_PAYED,
                    'shipping_status' => SS_SHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_SPLITED] . ',' . $GLOBALS['_LANG']['ps'][PS_PAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_SHIPPED]
            ],
            [
                //已分单，已付款，收货确认
                'status' => [
                    'order_status' => OS_SPLITED,
                    'pay_status' => PS_PAYED,
                    'shipping_status' => SS_RECEIVED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_SPLITED] . ',' . $GLOBALS['_LANG']['ps'][PS_PAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_RECEIVED]
            ],
            [
                //取消，未付款，未发货
                'status' => [
                    'order_status' => OS_CANCELED,
                    'pay_status' => PS_UNPAYED,
                    'shipping_status' => SS_UNSHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_CANCELED] . ',' . $GLOBALS['_LANG']['ps'][PS_UNPAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_UNSHIPPED]
            ],
            [
                //无效，未付款，未发货
                'status' => [
                    'order_status' => OS_INVALID,
                    'pay_status' => PS_UNPAYED,
                    'shipping_status' => SS_UNSHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_INVALID] . ',' . $GLOBALS['_LANG']['ps'][PS_UNPAYED] . ',' . $GLOBALS['_LANG']['ss'][SS_UNSHIPPED]
            ],
            [
                //退货，已退款，已发货
                'status' => [
                    'order_status' => OS_RETURNED,
                    'pay_status' => PS_REFOUND,
                    'shipping_status' => SS_SHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_RETURNED] . ',' . $GLOBALS['_LANG']['ps'][PS_REFOUND] . ',' . $GLOBALS['_LANG']['ss'][SS_SHIPPED]
            ],
            [
                //退货，已退款，未发货
                'status' => [
                    'order_status' => OS_RETURNED,
                    'pay_status' => PS_REFOUND,
                    'shipping_status' => SS_UNSHIPPED,
                ],
                'lang' => $GLOBALS['_LANG']['os'][OS_RETURNED] . ',' . $GLOBALS['_LANG']['ps'][PS_REFOUND] . ',' . $GLOBALS['_LANG']['ss'][SS_UNSHIPPED]
            ]
        ];

        return $arr;
    }

    /**
     * 更新主订单状态
     *
     * @param array $order 子订单信息
     * @param int $type [1|订单基础状态，2|支付状态， 3|订单配送状态]
     * @param string $action_note
     */
    public function updateMainOrder($order = [], $type = 0, $action_note = '')
    {

        $other = [];
        if ($order['main_order_id'] > 0 && $order['main_count'] == 0 && $type > 0) {

            $mainOrder = OrderInfo::select('order_sn', 'order_status', 'shipping_status', 'pay_status', 'main_pay')->where('order_id', $order['main_order_id']);
            $mainOrder = $this->baseRepository->getToArrayFirst($mainOrder);

            if (!empty($mainOrder)) {
                $order_status = $mainOrder['order_status'];
                $shipping_status = $mainOrder['shipping_status'];
                $pay_status = $mainOrder['pay_status'];
                $main_pay = $mainOrder['main_pay'];

                $childCount = OrderInfo::where('main_order_id', $order['main_order_id']);
                if ($type == 1) {
                    $childCount = $childCount->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED]);
                } elseif ($type == 2) {
                    $childCount = $childCount->whereIn('pay_status', [PS_UNPAYED, PS_PAYING]);
                } elseif ($type == 3) {
                    $childCount = $childCount->whereIn('shipping_status', [SS_UNSHIPPED, SS_PREPARING]);
                } elseif ($type == 4) {
                    $childCount = $childCount->where('shipping_status', '<>', SS_RECEIVED);
                }

                $childCount = $childCount->where('order_id', '<>', $order['order_id'])->count();

                if ($type == 1) {
                    if ($childCount > 0) {
                        $order_status = OS_SPLITING_PART;
                    } else {
                        $order_status = OS_SPLITED;
                    }

                    $other = [
                        'order_status' => $order_status
                    ];
                } elseif ($type == 2) {
                    if ($childCount > 0) {
                        $pay_status = PS_MAIN_PAYED_PART;
                    } else {
                        $pay_status = PS_PAYED;
                        $main_pay = 2;
                    }

                    $other = [
                        'main_pay' => $main_pay,
                        'pay_status' => $pay_status
                    ];
                } elseif ($type == 3) {
                    if ($childCount > 0) {
                        $shipping_status = SS_SHIPPED_PART;
                    } else {
                        $shipping_status = SS_SHIPPED;
                    }

                    $other = [
                        'shipping_status' => $shipping_status
                    ];
                } elseif ($type == 4) {
                    if ($childCount > 0) {
                        $shipping_status = SS_PART_RECEIVED;
                    } else {
                        $shipping_status = SS_RECEIVED;
                    }

                    $other = [
                        'shipping_status' => $shipping_status
                    ];
                }

                OrderInfo::where('order_id', $order['main_order_id'])->update($other);

                OrderInfo::where('main_order_id', $order['main_order_id'])
                    ->where('order_id', '<>', $order['order_id'])
                    ->update([
                        'child_show' => 1
                    ]);

                $action_note = "【" . $GLOBALS['_LANG']['action_child_order'] . "：" . $order['order_sn'] . "】" . $action_note;

                /* 更新记录 */
                $this->orderCommonService->orderAction($mainOrder['order_sn'], $order_status, $shipping_status, $pay_status, $action_note, session('admin_name'), 0, $this->timeRepository->getGmTime());
            }
        }
    }
}
