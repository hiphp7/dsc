<?php

namespace App\Services\User;

use App\Libraries\Pager;
use App\Models\Comment;
use App\Models\CommentSeller;
use App\Models\DeliveryOrder;
use App\Models\OrderAction;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\SellerShopinfo;
use App\Models\StoreOrder;
use App\Models\UserOrderNum;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;

/**
 * Class UserOrderService
 * @package App\Services\User
 */
class UserOrderService
{
    protected $timeRepository;
    protected $dscRepository;
    protected $config;
    protected $baseRepository;
    protected $paymentService;
    protected $commonRepository;
    protected $merchantCommonService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        PaymentService $paymentService,
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->paymentService = $paymentService;
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 订单列表获取订单数量
     *
     * @param array $where
     * @return mixed
     */
    public function getOrderWhereCount($where = [])
    {
        $res = OrderInfo::where(function ($query) {
            $query->whereRaw("IF(pay_status < 2, main_order_id = 0 AND main_count = 0, main_count = 0)")
                ->orWhere(function ($query) {
                    $query->where('main_count', '>', 0)
                        ->where('main_pay', 1)
                        ->where('pay_status', '<', 2);
                });
        });

        /* 会员ID */
        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        /* 订单删除状态 0 否， 1 是 */
        if (isset($where['show_type'])) {
            $res = $res->where('is_delete', $where['show_type']);
        }

        /* 是否众筹订单 0 否， 1是 */
        if (isset($where['is_zc_order'])) {
            $res = $res->where('is_zc_order', $where['is_zc_order']);
        }

        /* 订单状态 */
        if (isset($where['order_status'])) {
            if (is_array($where['order_status'])) {
                $res = $res->whereIn('order_status', $where['order_status']);
            } else {
                $res = $res->where('order_status', $where['order_status']);
            }
        }

        /* 订单支付状态 */
        if (isset($where['pay_status'])) {
            if (is_array($where['pay_status'])) {
                $res = $res->whereIn('pay_status', $where['pay_status']);
            } else {
                $res = $res->where('pay_status', $where['pay_status']);
            }
        }

        /* 订单配送状态 */
        if (isset($where['shipping_status']) && isset($where['pay_id'])) {
            $res = $res->where(function ($query) use ($where) {
                if (is_array($where['shipping_status'])) {
                    $query = $query->whereIn('shipping_status', $where['shipping_status']);
                } else {
                    $query = $query->where('shipping_status', $where['shipping_status']);
                }

                if (is_array($where['pay_id'])) {
                    $query->orWhereIn('pay_id', $where['pay_id']);
                } else {
                    $query->orWhere('pay_id', $where['pay_id']);
                }
            });
        } else {
            if (isset($where['shipping_status'])) {
                if (is_array($where['shipping_status'])) {
                    $res = $res->whereIn('shipping_status', $where['shipping_status']);
                } else {
                    $res = $res->where('shipping_status', $where['shipping_status']);
                }
            }
        }

        /* 订单类型：夺宝骑兵、积分商城、团购等 */
        if (isset($where['action'])) {
            $res = $res->where('extension_code', $where['action']);
        }

        /*未收货订单兼容货到付款*/
        if (isset($where['is_cob']) && $where['is_cob'] > 0) {
            $data['cod'] = $where['is_cob'];
            $data['user_id'] = $where['user_id'];
            $res = $res->orWhere(function ($query) use ($data) {
                $query->where('main_order_id', 0)
                    ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                    ->whereIn('shipping_status', [SS_UNSHIPPED, SS_SHIPPED, SS_SHIPPED_PART])
                    ->where('pay_status', PS_UNPAYED);
                if ($data['cod']) {
                    $query->where('pay_id', $data['cod']);
                }

                if ($data['user_id']) {
                    $query->where('user_id', $data['user_id']);
                }
            });
        }

        $res = $res->count();

        return $res;
    }

    /**
     * 处理主订单配送方式显示
     *
     * @param array $order
     * @return array
     */
    public function mainShipping($order = [])
    {
        if ($order['main_count'] > 0 && $order['shipping_name']) {
            $order['shipping_name'] = '';
        }

        return $order;
    }

    /**
     * 获取会员订单数量
     *
     * @param array $where
     * @param $order
     * @return mixed
     */
    public function getUserOrdersCount($where = [], $order)
    {
        $user_id = isset($where['user_id']) ? $where['user_id'] : 0;

        $res = OrderInfo::orderSelectCondition();

        if ($order && is_object($order)) {
            $res = $res->searchKeyword($order);
        }

        $action = is_object($order) && isset($order->action) ? $order->action : '';

        if (($order && !is_object($order) && $order == 'auction' || $order == 'auction_order_recycle') || $action == 'auction') {
            //拍卖订单
            if ($order == 'auction') {
                $res = $res->where('extension_code', $order);
            } else {
                $ext = $order->action;
                $res = $res->where('extension_code', $ext);
            }
        }

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        if (isset($where['show_type'])) {
            $res = $res->where('is_delete', $where['show_type']);
        }

        if (isset($where['is_zc_order'])) {
            $res = $res->where('is_zc_order', $where['is_zc_order']);
        }

        if (isset($order->idTxt) && $order->idTxt == 'signNum') {
            $res = $res->whereHas('getOrderGoods', function ($query) use ($user_id) {
                $query->goodsCommentCount($user_id);
            });
        }

        if (isset($order->type) && $order->type == 'text') { //订单编号、商品编号、商品名称模糊查询
            if ($order->keyword == $GLOBALS['_LANG']['user_keyword']) {
                $order->keyword = '';
            }

            if (isset($order->keyword) && !empty($order->keyword)) {
                $keyword = mysql_like_quote($order->keyword);

                $res = $res->where(function ($query) use ($keyword) {
                    $query->where('order_sn', 'like', "%$keyword%")
                        ->orWhere(function ($query) use ($keyword) {
                            $query->whereHas('getOrderGoods', function ($query) use ($keyword) {
                                $query->where('goods_name', 'like', "%$keyword%")
                                    ->orWhere('goods_sn', 'like', "%$keyword%");
                            });
                        });
                });
            }
        }

        $count = $res->count();

        return $count;
    }

    /**
     * 获取用户指定范围的订单列表
     *
     * @param array $where
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public function getUserOrdersList($where = [], $order = '')
    {
        $user_id = isset($where['user_id']) ? $where['user_id'] : 0;

        $record_count = isset($where['record_count']) ? $where['record_count'] : 0;
        $is_delete = isset($where['show_type']) ? $where['show_type'] : 0;

        $action = '';

        if ($order && is_object($order)) {
            $idTxt = isset($order->idTxt) ? $order->idTxt : '';
            $keyword = isset($order->keyword) ? $order->keyword : '';
            $action = isset($order->action) ? $order->action : '';
            $type = isset($order->type) ? $order->type : '';
            $status_keyword = isset($order->status_keyword) ? $order->status_keyword : '';
            $date_keyword = isset($order->date_keyword) ? $order->date_keyword : '';

            $id = '"';
            $id .= $user_id . "=";
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
            $id = $user_id;
        }

        $config = ['header' => $GLOBALS['_LANG']['pager_2'], "prev" => "<i><<</i>" . $GLOBALS['_LANG']['page_prev'], "next" => "" . $GLOBALS['_LANG']['page_next'] . "<i>>></i>", "first" => $GLOBALS['_LANG']['page_first'], "last" => $GLOBALS['_LANG']['page_last']];

        $pagerParams = [];
        if (isset($where['page']) && $where['size']) {
            $pagerParams = [
                'total' => $record_count,
                'listRows' => $where['size'],
                'type' => $is_delete,
                'act' => $is_delete,
                'id' => $id,
                'page' => $where['page'],
                'pageType' => 1,
                'config_zn' => $config
            ];

            if (($order && !is_object($order) && $order == 'auction' || $order == 'auction_order_recycle') || $action == 'auction') {

                $pagerParams['act'] = $order;

                //拍卖订单
                $pagerParams['funName'] = 'user_auction_order_gotoPage';
            } else {
                //所有订单
                $pagerParams['funName'] = 'user_order_gotoPage';
            }
        }

        $pager = [];
        if ($pagerParams) {
            $user_order = new Pager($pagerParams);
            $pager = $user_order->fpage([0, 4, 5, 6, 9]);
        }

        $res = OrderInfo::orderSelectCondition();

        $res = $res->where('is_delete', $is_delete);

        if ($order && is_object($order)) {

            $type = $type ?? '';

            $data = [
                'order' => $order,
                'type' => $type
            ];

            if ($type == 'toBe_confirmed') {
                $cod = Payment::where('pay_code', 'cod')->where('enabled', 1)->value('pay_id');
                $data['cod'] = $cod;
                $data['user_id'] = $user_id;
            }

            $res = $res->where(function ($query) use ($data) {
                $query = $query->searchKeyword($data['order']);
                if ($data['type'] == 'toBe_confirmed') {
                    $query->orWhere(function ($query) use ($data) {
                        $query->where('main_order_id', 0)
                            ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                            ->whereIn('shipping_status', [SS_UNSHIPPED, SS_SHIPPED, SS_SHIPPED_PART])
                            ->where('pay_status', PS_UNPAYED);
                        if ($data['cod']) {
                            $query->where('pay_id', $data['cod']);
                        }

                        if ($data['user_id']) {
                            $query->where('user_id', $data['user_id']);
                        }
                    });
                }
            });
        }

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        if (isset($where['is_zc_order'])) {
            $res = $res->where('is_zc_order', $where['is_zc_order']);
        }

        if (isset($where['page']) && $where['size']) {
            if (($order && !is_object($order) && $order == 'auction' || $order == 'auction_order_recycle') || $action == 'auction') {
                //拍卖订单
                if ($order == 'auction') {
                    $res = $res->where('extension_code', $order);
                } else {
                    $ext = isset($order->action) ? $order->action : '';
                    $res = $res->where('extension_code', $ext);
                }
            }
        }

        if (isset($order->idTxt) && $order->idTxt == 'signNum') {
            $res = $res->whereHas('getOrderGoods', function ($query) use ($user_id) {
                $query->goodsCommentCount($user_id);
            });
        }

        //订单编号、商品编号、商品名称模糊查询
        if (isset($order->type) && $order->type == 'text') {
            if (isset($order->keyword) && !empty($order->keyword)) {
                $val = mysql_like_quote($order->keyword);

                $res = $res->where(function ($query) use ($val) {
                    $query->where('order_sn', 'like', "%$val%")
                        ->orWhere(function ($query) use ($val) {
                            $query->whereHas('getOrderGoods', function ($query) use ($val) {
                                $query->where('goods_name', 'like', "%$val%")
                                    ->orWhere('goods_sn', 'like', "%$val%");
                            });
                        });
                });
            }
        }

        $res = $res->with([
            'getPayment',
            'getBaitiaoLog',
            'getOrderGoods'
        ]);

        $res = $res->withCount([
            'getStoreOrder as is_store_order'
        ]);

        $res = $res->orderBy('add_time', 'desc');

        if (isset($where['page']) && $where['size']) {
            $start = ($where['page'] - 1) * $where['size'];

            if ($start > 0) {
                $res = $res->skip($start);
            }
        }

        if (isset($where['size']) && $where['size'] > 0) {
            $res = $res->take($where['size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        /* 取得订单列表 */
        $arr = [];

        //发货日期起可退换货时间
        $sign_time = $this->config['sign'] ?? '';

        $time = $this->timeRepository->getGmTime();

        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['pay_status'] == PS_PAYED) {
                    $row['total_fee'] = $row['money_paid'] + $row['surplus'];
                    $row['is_pay'] = 1;
                } else {
                    $amount = $row['goods_amount'] + $row['insure_fee'] + $row['pay_fee'] + $row['pack_fee'] + $row['card_fee'] + $row['tax'];

                    if ($amount > $row['discount']) {
                        $amount -= $row['discount'];
                    } else {
                        $amount = 0;
                    }

                    if ($amount > $row['bonus']) {
                        $amount -= $row['bonus'];
                    } else {
                        $amount = 0;
                    }

                    if ($amount > $row['coupons']) {
                        $amount -= $row['coupons'];
                    } else {
                        $amount = 0;
                    }

                    if ($amount > $row['integral_money']) {
                        $amount -= $row['integral_money'];
                    } else {
                        $amount = 0;
                    }

                    $row['total_fee'] = $amount + $row['shipping_fee'];
                    $row['is_pay'] = 0;
                }

                $row['original_handler'] = '';

                $is_stages = $row['get_baitiao_log']['is_stages'] ?? 0;
                if ($is_stages) {
                    $row['is_stages'] = $is_stages;
                } else {
                    $row['is_stages'] = 0;
                }

                $order_goods = $row['get_order_goods'] ?? [];
                if ($order_goods) {
                    $row['sign1'] = Comment::where('id_value', $order_goods['goods_id'])
                        ->where('comment_type', 0)
                        ->where('rec_id', $order_goods['rec_id'])
                        ->where('parent_id', 0)
                        ->where('user_id', $user_id)
                        ->count();

                    $rec_id = $order_goods['rec_id'];
                    $row['sign2'] = Comment::where('id_value', $order_goods['goods_id'])
                        ->where('comment_type', 0)
                        ->where('parent_id', 0)
                        ->where('user_id', $user_id)
                        ->whereHas('getCommentImg', function ($query) use ($rec_id) {
                            $query->where('rec_id', $rec_id);
                        })
                        ->count();
                } else {
                    $row['sign1'] = 0;
                    $row['sign2'] = 0;
                }

                $row['ru_id'] = $order_goods ? $order_goods['ru_id'] : 0;
                $row['goods_id'] = $order_goods ? $order_goods['goods_id'] : 0;
                $row['extension_code'] = $order_goods ? $order_goods['extension_code'] : '';

                //检测订单是否支付超时
                $pay_effective_time = isset($this->config['pay_effective_time']) && $this->config['pay_effective_time'] > 0 ? intval($this->config['pay_effective_time']) : 0; //订单时效
                //订单时效大于零及开始时效性  且订单未付款未发货  支付方式为线上支付

                $pay_code = $row['get_payment']['pay_code'] ?? '';

                if ($pay_effective_time > 0 && $row['pay_status'] == PS_UNPAYED && in_array($row['order_status'], [OS_UNCONFIRMED, OS_CONFIRMED]) && in_array($row['shipping_status'], [SS_UNSHIPPED, SS_PREPARING]) && !in_array($pay_code, ['bank', 'cod', 'post'])) {
                    //计算时效性时间戳
                    $pay_effective_time = $pay_effective_time * 60;

                    //如果订单超出时间设为无效
                    if (($time - $row['add_time']) > $pay_effective_time) {
                        $store_order_id = StoreOrder::where('order_id', $row['order_id'])->value('store_id');

                        $store_id = ($store_order_id > 0) ? $store_order_id : 0;

                        /* 标记订单为“无效” */
                        update_order($row['order_id'], ['order_status' => OS_INVALID]);

                        /* 记录log */
                        order_action($row['order_sn'], OS_INVALID, SS_UNSHIPPED, PS_UNPAYED, $GLOBALS['_LANG']['pay_effective_Invalid']);

                        /* 如果使用库存，且下订单时减库存，则增加库存 */
                        if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PLACE) {
                            change_order_goods_storage($row['order_id'], false, SDT_PLACE, 2, 0, $store_id);
                        }
                        /* 退还用户余额、积分、红包 */
                        return_user_surplus_integral_bonus($row);
                        /* 更新会员订单数量 */
                        if (isset($row['user_id']) && !empty($row['user_id'])) {
                            $order_nopay = UserOrderNum::where('user_id', $row['user_id'])->value('order_nopay');
                            $order_nopay = $order_nopay ? intval($order_nopay) : 0;

                            if ($order_nopay > 0) {
                                $dbRaw = [
                                    'order_nopay' => "order_nopay - 1",
                                ];
                                $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                                UserOrderNum::where('user_id', $row['user_id'])->update($dbRaw);
                            }
                        }

                    }
                }

                $row['delay_day_time'] = '';
                $row['allow_order_delay'] = 0;
                $row['allow_order_delay_handler'] = '';

                $auto_delivery_time = 0;
                if ($row['shipping_status'] == SS_SHIPPED) {
                    $auto_delivery_time = $row['shipping_time'] + $row['auto_delivery_time'] * 86400; // 延迟收货截止天数
                    $order_delay_day = isset($this->config['order_delay_day']) && $this->config['order_delay_day'] > 0 ? intval($this->config['order_delay_day']) : 3;

                    if (($auto_delivery_time - $time) / 86400 < $order_delay_day) {
                        $row['allow_order_delay'] = 1;
                    }

                    $row['auto_delivery_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $auto_delivery_time);
                }

                $row['handler_return'] = "";

                $row['order_over'] = 0;
                $row['handler_order_status'] = false;
                if ($this->config['open_delivery_time'] == 1) {
                    if ($row['order_status'] == OS_SPLITED && $row['shipping_status'] == SS_SHIPPED && $row['pay_status'] == PS_PAYED) { //发货状态

                        if ($time >= $auto_delivery_time) { //自动确认发货操作
                            $row['order_over'] = 1;
                        }
                    }
                }

                $row['original_handler_return'] = '';
                if ($row['shipping_status'] == SS_UNSHIPPED && ($row['order_status'] == OS_UNCONFIRMED || ($row['order_status'] == OS_CONFIRMED && $row['pay_status'] == PS_UNPAYED))) {
                    $row['remind'] = lang('user.confirm_cancel');
                    $row['original_handler'] = lang('user.cancel');
                    $row['handler_act'] = 'cancel_order';
                    $row['handler'] = "<a href=\"user_order.php?act=cancel_order&order_id=" . $row['order_id'] . "\" onclick=\"if (!confirm('" . lang('user.confirm_cancel') . "')) return false;\">" . lang('user.cancel') . "</a>";
                } elseif ($row['order_status'] == OS_SPLITED || $row['order_status'] == OS_SPLITING_PART || $row['order_status'] == OS_CONFIRMED || $row['order_status'] == OS_RETURNED_PART || $row['order_status'] == OS_ONLY_REFOUND) {
                    // 对配送状态的处理
                    if ($row['shipping_status'] == SS_SHIPPED || $row['shipping_status'] == SS_SHIPPED_PART) {

                        //延迟收货
                        $row['allow_order_delay_handler'] = lang('user.allow_order_delay');

                        $row['remind'] = lang('user.confirm_received');
                        $row['original_handler'] = lang('user.received');
                        $row['handler_act'] = 'affirm_received';
                        $row['handler'] = "<a href=\"user_order.php?act=affirm_received&order_id=" . $row['order_id'] . "\" onclick=\"if (!confirm('" . lang('user.confirm_received') . "')) return false;\">" . lang('user.received') . "</a>";
                    } elseif ($row['shipping_status'] == SS_RECEIVED) {
                        $row['original_handler'] = lang('user.ss_received');
                        $row['handler'] = '<span style="color:red">' . lang('user.ss_received') . '</span>';
                    } else {
                        if ($row['pay_status'] == PS_UNPAYED || $row['pay_status'] == PS_PAYED_PART) {
                            if ($order == 'auction') {
                                $row['handler_act'] = 'auction_order_detail';
                            } else {
                                $row['handler_act'] = 'order_detail';
                            }

                            $row['handler'] = "<a href=\"user_order.php?act=order_detail&order_id=" . $row['order_id'] . '">' . lang('user.pay_money') . '</a>';
                        } else {
                            $row['original_handler'] = lang('user.view_order');
                            if ($order == 'auction') {
                                $row['handler_act'] = 'auction_order_detail';
                            } else {
                                $row['handler_act'] = '';
                            }
                            $row['handler'] = "<a href=\"user_order.php?act=order_detail&order_id=" . $row['order_id'] . '">' . lang('user.view_order') . '</a>';
                        }
                    }
                } else {
                    $row['handler_order_status'] = true;
                    $row['original_handler'] = lang('user.os.' . $row['order_status']);
                    $row['handler'] = '<span style="color:red">' . lang('user.os.' . $row['order_status']) . '</span>';
                    if ($row['pay_status'] == PS_UNPAYED && $row['shipping_status'] == SS_UNSHIPPED && $row['order_status'] != OS_CANCELED) {
                        $row['remind'] = lang('user.confirm_cancel');
                        $row['original_handler'] = lang('user.cancel');
                        $row['handler_act'] = 'cancel_order';
                        $row['handler'] = "<a href=\"user_order.php?act=cancel_order&order_id=" . $row['order_id'] . "\" onclick=\"if (!confirm('" . lang('user.confirm_cancel') . "')) return false;\">" . lang('user.cancel') . "</a>";
                    }
                }

                $row['user_order'] = $row['order_status'];
                $row['user_shipping'] = $row['shipping_status'];
                $row['user_pay'] = $row['pay_status'];

                if (($row['user_order'] == OS_UNCONFIRMED || $row['user_order'] == OS_CANCELED) && $row['user_shipping'] == SS_UNSHIPPED && $row['user_pay'] == PS_UNPAYED) {//已确认、未确认、已取消 未发货 未付款
                    $row['delete_yes'] = 0;
                } elseif ($row['user_order'] == OS_INVALID && $row['user_pay'] == PS_PAYED_PART && $row['user_shipping'] == SS_UNSHIPPED) {//无效 部分付款 未发货
                    $row['delete_yes'] = 1;
                } elseif ((($row['user_order'] == OS_CONFIRMED || $row['user_order'] == OS_SPLITED || $row['user_order'] == OS_SPLITING_PART) && $row['user_shipping'] != SS_RECEIVED && ($row['user_pay'] == PS_PAYED || $row['user_pay'] == PS_PAYED_PART)) || (($row['user_shipping'] == SS_SHIPPED || $row['user_shipping'] == SS_RECEIVED) && $row['user_pay'] == PS_UNPAYED)) {//已付款 未确认收货
                    $row['delete_yes'] = 1;
                } else {
                    $row['delete_yes'] = 0;
                }

                //判断是否已评论或晒单 start
                if ($row['sign1'] == 0) {
                    $row['sign'] = 0;
                } elseif ($row['sign1'] > 0 && $row['sign2'] == 0) {
                    $row['sign'] = 1;
                } elseif ($row['sign1'] > 0 && $row['sign2'] > 0) {
                    $row['sign'] = 2;
                }

                //判断是否已评论或晒单 end
                $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
                if ($row['order_status'] == OS_RETURNED) {
                    $row['order_status'] = lang('user.os.' . $row['order_status']) . '<br />' . lang('user.ps.' . $row['pay_status']);
                } elseif ($row['order_status'] == OS_ONLY_REFOUND) {
                    $row['order_status'] = lang('user.os.' . $row['order_status']) . '<br />' . lang('user.ps.' . $row['pay_status']);
                } else {
                    $row['order_status'] = lang('user.os.' . $row['order_status']) . '<br />' . lang('user.ps.' . $row['pay_status']) . '<br />' . lang('user.ss.' . $row['shipping_status']);
                }

                $br = '';
                $sign = '';
                if ($row['user_order'] == OS_SPLITED && $row['user_shipping'] == SS_RECEIVED && $row['user_pay'] == PS_PAYED) {
                    $row['order_status'] = lang('user.ss_received');
                    //添加晒单评价操作
                    $row['original_handler'] = lang('user.single_comment');
                    if ($row['sign'] > 0) {
                        $sign = "&sign=" . $row['sign'];
                        if ($row['ru_id'] > 0) {
                            $row['original_handler'] = lang('user.single_comment_on');
                        } else {
                            $row['is_my_shop'] = 'my_shop';
                        }
                    }

                    $row['handler_order_status'] = false;
                    if ($row['extension_code'] != 'package_buy') {
                        $row['handler_act'] = 'commented_view';
                    }
                    $row['handler'] = "<a href=\"user_message.php?act=commented_view&order_id=" . $row['order_id'] . $sign . '">' . $row['original_handler'] . '</a><br/>';
                    $row['original_handler_return'] = lang('user.return');
                    $row['handler_return_act'] = 'goods_order';
                    $row['handler_return'] = "<a href=\"user_order.php?act=goods_order&order_id=" . $row['order_id'] . '" style="margin-left:5px;" >' . lang('user.return') . "</a><br/>";
                } elseif ($row['user_order'] == OS_CANCELED && $row['user_shipping'] == SS_UNSHIPPED && $row['user_pay'] == PS_UNPAYED) {
                    $row['order_status'] = lang('user.os.' . OS_CANCELED);
                    $row['handler_order_status'] = false;
                    $row['handler'] = '';
                } elseif ($row['user_order'] == OS_CONFIRMED && $row['user_shipping'] == SS_RECEIVED && $row['user_pay'] == PS_PAYED) { //已确认，付款，收货 liu
                    $row['order_status'] = lang('user.ss_received');
                    //添加晒单评价操作
                    $row['original_handler'] = lang('user.single_comment');
                    if ($row['sign'] > 0) {
                        $sign = "&sign=" . $row['sign'];
                        $row['original_handler'] = lang('user.single_comment_on');
                    }

                    $row['handler_order_status'] = false;

                    if ($row['extension_code'] != 'package_buy') {
                        $row['handler_act'] = 'commented_view';
                    }
                    $row['handler'] = "<a href=\"user_message.php?act=commented_view&order_id=" . $row['order_id'] . $sign . '">' . $row['original_handler'] . '</a><br/>';
                    $row['original_handler_return'] = lang('user.return');
                    $row['handler_return_act'] = 'goods_order';
                    $row['handler_return'] = "<a href=\"user_order.php?act=goods_order&order_id=" . $row['order_id'] . '" style="margin-left:5px;" >' . lang('user.return') . "</a><br/>";
                } else {
                    if (!($row['user_order'] == OS_UNCONFIRMED && $row['user_shipping'] == SS_UNSHIPPED && $row['user_pay'] == PS_UNPAYED) && !($row['user_order'] == OS_CONFIRMED && $row['user_shipping'] == SS_SHIPPED && $row['user_pay'] == PS_PAYED)) {
                        $row['handler_order_status'] = false;
                        $row['handler'] = '';
                    } else {
                        $br = "<br/>";
                    }
                }

                $is_comment_again = CommentSeller::where('order_id', $row['order_id'])->value('sid');
                $row['is_comment_again'] = $is_comment_again;

                $row['sign_url'] = $sign;

                //判断发货日期起可退换货时间
                if ($sign_time > 0) {
                    $log_time = OrderAction::where('order_id', $row['order_id']);

                    if ($row['user_pay'] == PS_UNPAYED) {
                        $log_time = $log_time->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                            ->where('shipping_status', SS_RECEIVED)
                            ->where('pay_status', PS_UNPAYED);
                    } elseif ($row['user_pay'] == PS_PAYED) {
                        $log_time = $log_time->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                            ->whereIn('shipping_status', [SS_UNSHIPPED, SS_SHIPPED, SS_RECEIVED, SS_SHIPPED_PART, OS_SHIPPED_PART])
                            ->where('pay_status', PS_PAYED);
                    }

                    $log_time = $log_time->orderBy('action_id', 'desc')
                        ->value('log_time');

                    $order_status = [OS_CANCELED, OS_INVALID, OS_RETURNED];
                    if (!in_array($row['user_order'], $order_status)) {
                        if (!$log_time) {
                            $log_time = !empty($row['pay_time']) ? $row['pay_time'] : $row['add_time'];
                        }

                        $signtime = $log_time + $sign_time * 3600 * 24;
                        if ($time < $signtime && $row['user_pay'] == PS_PAYED) {
                            $row['original_handler_return'] = lang('user.return');
                            $row['handler_return_act'] = 'goods_order';
                            $row['handler_return'] = $br . "<a href=\"user_order.php?act=goods_order&order_id=" . $row['order_id'] . '" style="margin-left:5px;" >' . lang('user.return') . "</a>";
                        } else {
                            $row['original_handler_return'] = '';
                            $row['handler_return'] = '';
                        }
                    }

                    if ($row['handler_return']) {
                        $row['return_url'] = "user_order.php?act=goods_order&order_id=" . $row['order_id'];
                    }
                }

                $ru_id = $row['ru_id'];

                $row['order_goods'] = get_order_goods_toInfo($row['order_id']);
                $row['order_goods_count'] = count($row['order_goods']);


                $order_id = $row['order_id'];
                $date = ['order_id'];
                $order_child = count(get_table_date('order_info', "main_order_id='$order_id'", $date, 1));

                $order_count = OrderInfo::where('main_order_id', $row['main_order_id'])->where('main_order_id', '>', 0)->count();

                $delivery = DeliveryOrder::where('order_id', $row['order_id'])->first();
                $delivery = $delivery ? $delivery->toArray() : [];

                $province = get_order_region_name($row['province']);
                $city = get_order_region_name($row['city']);
                $district = get_order_region_name($row['district']);

                $province_name = $province ? $province['region_name'] : '';
                $city_name = $city ? $city['region_name'] : '';
                $district_name = $district ? $district['region_name'] : '';

                $address_detail = $province_name . "&nbsp;" . $city_name . "市" . "&nbsp;" . $district_name;

                $delivery['delivery_time'] = $delivery ? $this->timeRepository->getLocalDate($this->config['time_format'], $delivery['update_time']) : '';

                if ($is_delete == 1) {
                    $row['order_status'] = str_replace(['<br />'], '', $row['order_status']);
                }

                $row['order_status'] = str_replace(['<br>', '<br />'], ['', '，'], $row['order_status']);

                $row['shop_name'] = $this->merchantCommonService->getShopName($ru_id, 1);

                $build_uri = [
                    'urid' => $row['main_count'] > 0 ? 0 : $ru_id,
                    'append' => $row['shop_name']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($ru_id, $build_uri);
                $row['shop_url'] = $domain_url['domain_name'];

                $basic_info = SellerShopinfo::where('ru_id', $ru_id)->first();
                $basic_info = $basic_info ? $basic_info->toArray() : [];

                $chat = $this->dscRepository->chatQq($basic_info);

                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                if ($this->config['customer_service'] == 0) {
                    $ru_id = 0;
                } else {
                    $ru_id = $row['ru_id'];
                }

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

                if (!empty($row['invoice_no'])) {
                    $invoice_no_arr = explode(',', $row['invoice_no']);
                    $row['invoice_no'] = reset($invoice_no_arr);
                }

                $row['is_package'] = 0;

                //超值礼包是否存在
                if ($row['extension_code'] == 'package_buy') {
                    $activity = get_goods_activity_info($row['goods_id'], ['act_id']);
                    if ($activity) {
                        $row['is_package'] = $activity['act_id'];
                    }
                }

                $arr[] = [
                    'order_id' => $row['order_id'],
                    'order_sn' => $row['order_sn'],
                    'order_time' => $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']),
                    'sign' => $row['sign'],
                    'is_IM' => isset($shop_information['is_IM']) ? $shop_information['is_IM'] : '', //平台是否允许商家使用"在线客服";
                    'is_dsc' => $row['is_dsc'],
                    'order_status' => $row['order_status'],
                    'ru_id' => $row['ru_id'],
                    'consignee' => $row['consignee'],
                    'main_order_id' => $row['main_order_id'],
                    'shop_name' => $row['main_count'] > 0 ? $this->config['shop_name'] : $row['shop_name'], //店铺名称
                    'shop_url' => $row['shop_url'], //店铺名称	,
                    'order_goods' => $row['order_goods'],
                    'order_goods_count' => $row['order_goods_count'],
                    'order_child' => $order_child,
                    'no_picture' => $this->config['no_picture'],
                    'delete_yes' => $row['delete_yes'],
                    'invoice_no' => $row['invoice_no'],
                    'shipping_name' => $row['shipping_name'],
                    'pay_name' => $row['pay_name'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'address_detail' => $address_detail,
                    'tel' => $row['tel'],
                    'delivery_time' => $delivery['delivery_time'],
                    'order_count' => $order_count,
                    'kf_type' => $chat['kf_type'],
                    'kf_ww' => $chat['kf_ww'],
                    'kf_qq' => $chat['kf_qq'],
                    'total_fee' => $this->dscRepository->getPriceFormat($row['total_fee'], false),
                    'handler_return' => $row['handler_return'],
                    'handler' => $row['handler'],
                    'original_handler' => $row['original_handler'],
                    'is_my_shop' => isset($row['is_my_shop']) ? $row['is_my_shop'] : '',
                    'original_handler_return' => $row['original_handler_return'],
                    'handler_act' => isset($row['handler_act']) ? $row['handler_act'] : '',
                    'handler_return_act' => isset($row['handler_return_act']) ? $row['handler_return_act'] : '',
                    'return_url' => isset($row['return_url']) ? $row['return_url'] : '',
                    'remind' => isset($row['remind']) && $row['remind'] ? $row['remind'] : '',
                    'handler_order_status' => isset($row['handler_order_status']) && $row['handler_order_status'] ? true : false,
                    //@模板堂-bylu 是否为白条分期订单
                    'is_stages' => $row['is_stages'],
                    'order_over' => $row['order_over'],
                    'sign_url' => $row['sign_url'],
                    'delay_day_time' => $row['delay_day_time'],
                    'allow_order_delay' => $row['allow_order_delay'],
                    'auto_delivery_time' => $row['auto_delivery_time'],
                    'allow_order_delay_handler' => $row['allow_order_delay_handler'],
                    'is_comment_again' => $row['is_comment_again'],
                    'is_package' => $row['is_package'],
                    'is_pay' => $row['is_pay'],
                    'is_store_order' => $row['is_store_order'],
                    'order_confirm' => $row['order_status'] === lang('user.is_confirmed') ? 1 : 0,
                ];
            }
        }

        return ['order_list' => $arr, 'pager' => $pager, 'record_count' => $record_count];
    }

    /**
     * 获取用户指定范围的订单列表
     *
     * @param int $user_id 会员ID
     * @param int $is_zc_order 是否众筹 0|否  1|是
     * @return array
     * @throws \Exception
     */
    public function getDefaultUserOrders($user_id = 0, $is_zc_order = 0)
    {
        /* 取得订单列表 */
        $res = OrderInfo::selectRaw("*, (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee, extension_code as oi_extension_code")
            ->where('main_count', 0);

        $res = $res->where('user_id', $user_id);

        $res = $res->where('is_zc_order', $is_zc_order);

        $res = $res->where('is_delete', 0);

        $res = $res->with([
            'getOrderGoodsList' => function ($query) {
                $query = $query->select('order_id', 'goods_id', 'goods_name', 'extension_code as og_extension_code');

                $query->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_thumb');
                    }
                ]);
            }
        ]);

        $res = $res->orderBy('order_id', 'DESC');

        $res = $res->take(5);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            load_helper('order');
            foreach ($res as $key => $row) {
                $row['total_fee'] = $row['goods_amount'] + $row['shipping_fee'] + $row['insure_fee'] + $row['pay_fee'] + $row['pack_fee'] + $row['card_fee'] + $row['tax'] - $row['discount'];

                $arr[$key] = $row;

                if ($row['order_status'] == OS_RETURNED) {
                    $ret_id = OrderReturn::where('order_id', $row['order_id'])->value('ret_id');
                    $order = return_order_info($ret_id);
                    if ($order) {
                        $order['return_status'] = isset($order['return_status']) ? $order['return_status'] : ($order['return_status1'] < 0 ? $GLOBALS['_LANG']['only_return_money'] : $GLOBALS['_LANG']['rf'][RF_RECEIVE]);
                        $row['order_status'] = lang('user.os.' . $row['order_status']) . ',' . $order['return_status'] . ',' . $order['refound_status'];
                    } else {
                        $order['return_status'] = $GLOBALS['_LANG']['rf'][RF_RECEIVE];
                        $row['order_status'] = lang('user.os.' . $row['order_status']);
                    }
                } else {
                    $row['order_status'] = lang('user.os.' . $row['order_status']) . ',' .
                        $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' .
                        $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                }

                $arr[$key]['order_id'] = $row['order_id'];
                $arr[$key]['order_sn'] = $row['order_sn'];
                $arr[$key]['oi_extension_code'] = $row['oi_extension_code'];
                $arr[$key]['consignee'] = $row['consignee'];
                $arr[$key]['total_fee'] = $this->dscRepository->getPriceFormat($row['total_fee'], false);
                $arr[$key]['order_status'] = $row['order_status'];
                $arr[$key]['order_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['add_time']);

                if ($row['get_order_goods_list']) {
                    foreach ($row['get_order_goods_list'] as $idx => $order_goods) {
                        $arr[$key]['goods'][$idx]['goods_id'] = $order_goods['goods_id'];
                        $arr[$key]['goods'][$idx]['goods_name'] = $order_goods['goods_name'];
                        $arr[$key]['goods'][$idx]['og_extension_code'] = $order_goods['og_extension_code'];

                        $goods = $order_goods['get_goods'] ?? [];
                        if ($goods) {
                            $arr[$key]['goods'][$idx]['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
                        }

                        $arr[$key]['goods'][$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $order_goods['goods_id']], $order_goods['goods_name']);
                    }
                }
            }
        }

        return $arr;
    }
}
