<?php

namespace App\Services\Merchant;

use App\Models\MerchantsServer;
use App\Models\MerchantsShopInformation;
use App\Models\OrderInfo;
use App\Models\Region;
use App\Models\SellerAccountLog;
use App\Models\SellerBillOrder;
use App\Models\SellerCommissionBill;
use App\Models\SellerNegativeBill;
use App\Models\SellerNegativeOrder;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Common\CommonManageService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderRefoundService;


class MerchantsCommissionManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $commissionService;
    protected $dscRepository;
    protected $commonManageService;
    protected $orderCommonService;
    protected $orderRefoundService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        CommissionService $commissionService,
        DscRepository $dscRepository,
        CommonManageService $commonManageService,
        OrderCommonService $orderCommonService,
        OrderRefoundService $orderRefoundService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
        $this->commonManageService = $commonManageService;
        $this->orderCommonService = $orderCommonService;
        $this->orderRefoundService = $orderRefoundService;
        $this->timeRepository = $timeRepository;
    }

    public function orderDownloadList($result)
    {
        if (empty($result)) {
            return $this->commonManageService->i($GLOBALS['_LANG']['not_fuhe_date']);
        }

        $downLang = $GLOBALS['_LANG']['down']['order_sn'] . "," .
            $GLOBALS['_LANG']['down']['short_order_time'] . "," .
            $GLOBALS['_LANG']['down']['consignee_address'] . "," .
            $GLOBALS['_LANG']['down']['total_fee'] . "," .
            $GLOBALS['_LANG']['down']['shipping_fee'] . "," .
            $GLOBALS['_LANG']['down']['discount'] . "," .
            $GLOBALS['_LANG']['down']['coupons'] . "," .
            $GLOBALS['_LANG']['down']['integral_money'] . "," .
            $GLOBALS['_LANG']['down']['bonus'] . "," .
            $GLOBALS['_LANG']['down']['return_amount_price'] . "," .
            $GLOBALS['_LANG']['down']['brokerage_amount_price'] . "," .
            $GLOBALS['_LANG']['down']['effective_amount_price'] . "," .
            $GLOBALS['_LANG']['down']['settlement_status'] . "," .
            $GLOBALS['_LANG']['down']['ordersTatus'];

        $data = $this->commonManageService->i($downLang . "\n");
        $count = count($result);

        for ($i = 0; $i < $count; $i++) {
            $order_sn = $this->commonManageService->i('#' . $result[$i]['order_sn']);//订单号前加'#',避免被四舍五入 by wu
            $short_order_time = $this->commonManageService->i($result[$i]['short_order_time']);
            $consignee = $this->commonManageService->i($result[$i]['consignee']) . "" . $this->commonManageService->i($result[$i]['address']);
            $total_fee = $this->commonManageService->i($result[$i]['order_total_fee']);
            $shipping_fee = $this->commonManageService->i($result[$i]['shipping_fee']);
            $discount = $this->commonManageService->i($result[$i]['discount']);
            $coupons = $this->commonManageService->i($result[$i]['coupons']);
            $integral_money = $this->commonManageService->i($result[$i]['integral_money']);
            $bonus = $this->commonManageService->i($result[$i]['bonus']);
            $return_amount_price = $this->commonManageService->i($result[$i]['return_amount_price']);
            $brokerage_amount_price = $this->commonManageService->i($result[$i]['brokerage_amount_price']);
            $effective_amount_price = $this->commonManageService->i($result[$i]['effective_amount_price'] + $result[$i]['shipping_fee']);
            $is_settlement = $this->commonManageService->i($result[$i]['settlement_status']);
            $status = $this->commonManageService->i($result[$i]['ordersTatus']);
            $is_frozen = $this->commonManageService->i($result[$i]['is_frozen']);

            if ($consignee) {
                $consignee = str_replace([",", "，"], "_", $consignee);
            }

            $data .= $order_sn . ',' . $short_order_time . ',' . $consignee . ',' . $total_fee . ',' . $shipping_fee . ',' .
                $discount . ',' . $coupons . ',' . $integral_money . ',' . $bonus . ',' . $return_amount_price . ',' . $brokerage_amount_price . ',' .
                $status . ',' . $effective_amount_price . ',' . $is_settlement . ',' . $is_frozen . "\n";
        }
        return $data;
    }

    /**
     * 获取商家佣金列表
     *
     * @return array
     * @throws \Exception
     */
    public function merchantsCommissionList()
    {
        $adminru = get_admin_ru_id();

        $ajax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        $filter['user_name'] = !isset($_REQUEST['user_name']) && empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);

        if (!empty($ajax) && $ajax == 1) {
            $filter['user_name'] = json_str_iconv($filter['user_name']);
        }

        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['start_time']) : $_REQUEST['start_time']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['end_time']) : $_REQUEST['end_time']);
        $filter['cycle'] = !isset($_REQUEST['cycle']) ? '-1' : intval($_REQUEST['cycle']);

        $res = MerchantsShopInformation::whereRaw(1);

        if ($adminru['ru_id'] > 0) {
            $res = $res->where('user_id', $adminru['ru_id']);
        }

        if (!empty($filter['start_time'])) {
            $res = $res->where('add_time', '>=', $filter['start_time']);
        }

        if (!empty($filter['end_time'])) {
            $res = $res->where('add_time', '<=', $filter['end_time']);
        }

        if ($filter['user_name']) {
            $u_res = Users::select('user_id')
                ->where('user_name', 'LIKE', '%' . mysql_like_quote($filter['user_name']) . '%');
            $user_list = $this->baseRepository->getToArrayGet($u_res);
            $user_list = $this->baseRepository->getFlatten($user_list);

            $res = $res->whereIn('user_id', $user_list);
        }

        if ($filter['cycle'] != -1) {
            $cycle = $filter['cycle'];
            $res = $res->whereHas('getMerchantsServer', function ($query) use ($cycle) {
                $query->where('cycle', $cycle);
            });
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0 && $adminru['ru_id'] == 0) {
            $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

            if ($filter['store_search'] == 1) {
                $res = $res->where('user_id', $filter['merchant_id']);
            } elseif ($filter['store_search'] == 2) {
                $res = $res->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
            } elseif ($filter['store_search'] == 3) {
                $res = $res->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                if ($store_type) {
                    $res = $res->where('shopNameSuffix', $store_type);
                }
            }
        }

        $res = $res->where('merchants_audit', 1);
        //管理员查询的权限 -- 店铺查询 end

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

        /* 记录总数 */
        $filter['record_count'] = $res->count();
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */  //ecmoban模板堂 -zhuo suppliers_sn

        $res = $res->with(['getMerchantsStepsFields' => function ($query) {
            $query->select('user_id', 'companyName', 'company_adress', 'company_contactTel');
        }]);
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);
        $row = $this->baseRepository->getToArrayGet($res);

        //计算平台佣金
        $admin_commission = ['is_settlement' => 0, 'no_settlement' => 0];
        for ($i = 0; $i < count($row); $i++) {
            $row[$i]['companyName'] = '';
            $row[$i]['company_adress'] = '';
            $row[$i]['company_contactTel'] = '';
            if (isset($row[$i]['get_merchants_steps_fields']) && !empty($row[$i]['get_merchants_steps_fields'])) {
                $row[$i]['companyName'] = $row[$i]['get_merchants_steps_fields']['companyName'];
                $row[$i]['company_adress'] = $row[$i]['get_merchants_steps_fields']['company_adress'];
                $row[$i]['company_contactTel'] = $row[$i]['get_merchants_steps_fields']['company_contactTel'];
            }

            $res = MerchantsServer::where('user_id', $row[$i]['user_id']);
            $server_info = $this->baseRepository->getToArrayFirst($res);

            if ($server_info) {
                $row[$i]['server_id'] = $server_info['server_id'];
                $row[$i]['suppliers_desc'] = $server_info['suppliers_desc'];
                $row[$i]['suppliers_percent'] = $server_info['suppliers_percent'];
            } else {
                $row[$i]['server_id'] = 0;
                $row[$i]['suppliers_desc'] = '';
                $row[$i]['suppliers_percent'] = '';
            }

            $row[$i]['user_name'] = Users::where('user_id', $row[$i]['user_id'])->value('user_name');
            $row[$i]['user_name'] = $row[$i]['user_name'] ? $row[$i]['user_name'] : '';

            $valid = $this->commissionService->merchantsOrderValidRefund($row[$i]['user_id']); //订单有效总额

            if ($valid) {
                $row[$i]['order_valid_total'] = $this->dscRepository->getPriceFormat($valid['total_fee']);
                $row[$i]['is_goods_rate'] = $valid['is_goods_rate'];

                $row[$i]['order_total_fee'] = $valid['order_total_fee'];
                $row[$i]['goods_total_fee'] = $valid['goods_total_fee'];

                /* 微分销 */
                if (file_exists(MOBILE_DRP)) {
                    $row[$i]['order_drp_commission'] = $this->dscRepository->getPriceFormat($valid['drp_money']); //dis liu
                }
            }

            $row[$i]['order_refund_total'] = $this->dscRepository->getPriceFormat($valid['return_amount']);
            $row[$i]['store_name'] = $this->merchantCommonService->getShopName($row[$i]['user_id'], 1);

            $row[$i]['total_fee_price'] = number_format($valid['total_fee'], 2, '.', '');

            $res = SellerShopinfo::where('ru_id', $row[$i]['user_id']);
            $seller_shopinfo = $this->baseRepository->getToArrayFirst($res);
            if (!empty($seller_shopinfo)) {
                $province = Region::where('region_id', $seller_shopinfo['province'])->value('region_name');
                $province = $province ? $province : '';

                $city = Region::where('region_id', $seller_shopinfo['city'])->value('region_name');
                $city = $city ? $city : '';

                $district = Region::where('region_id', $seller_shopinfo['district'])->value('region_name');
                $district = $district ? $district : '';

                $seller_shopinfo['region'] = $province . '  ' . $city . '  ' . $district;
            } else {
                $seller_shopinfo['region'] = '';
            }


            if ($seller_shopinfo['shop_name']) {
                $row[$i]['companyName'] = $seller_shopinfo['shop_name'];
                $row[$i]['company_adress'] = "[" . $seller_shopinfo['region'] . "] " . $seller_shopinfo['shop_address'];
            }

            if ($seller_shopinfo['mobile']) {
                $row[$i]['company_contactTel'] = $seller_shopinfo['mobile'];
            } else {
                $row[$i]['company_contactTel'] = isset($row[$i]['contactPhone']) ? $row[$i]['contactPhone'] : '';
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$i]['user_name'] = $this->dscRepository->stringToStar($row[$i]['user_name']);
            }
        }

        $admin_commission['is_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['is_settlement']);
        $admin_commission['no_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['no_settlement']);

        $arr = ['result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count'], 'admin_commission' => $admin_commission];
        return $arr;
    }

    /**
     * 获取商家佣金列表
     *
     * @param int $page
     * @return array
     * @throws \Exception
     */
    public function merchantsOrderList($page = 0)
    {

        /* 过滤信息 */
        $filter['id'] = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

        $filter['order_sn'] = !isset($_REQUEST['order_sn']) && empty($_REQUEST['order_sn']) ? '' : addslashes($_REQUEST['order_sn']);
        $filter['consignee'] = !isset($_REQUEST['consignee']) && empty($_REQUEST['consignee']) ? '' : addslashes($_REQUEST['consignee']);
        $filter['order_cat'] = !isset($_REQUEST['order_cat']) && empty($_REQUEST['order_cat']) ? '' : addslashes($_REQUEST['order_cat']);

        $filter['order_status'] = !isset($_REQUEST['order_status']) && empty($_REQUEST['order_status']) ? 0 : intval($_REQUEST['order_status']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'order_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : $this->timeRepository->getLocalStrtoTime(trim($_REQUEST['start_time']));
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : $this->timeRepository->getLocalStrtoTime(trim($_REQUEST['end_time']));
        $filter['state'] = isset($_REQUEST['state']) ? trim($_REQUEST['state']) : ''; //liu 新增状态

        $_REQUEST['start_time'] = isset($_REQUEST['start_time']) ? $_REQUEST['start_time'] : '';
        $_REQUEST['end_time'] = isset($_REQUEST['end_time']) ? $_REQUEST['end_time'] : '';

        if (is_numeric(trim($_REQUEST['start_time']))) {//时间戳则不转换
            $filter['start_time'] = trim($_REQUEST['start_time']);
        }
        if (is_numeric(trim($_REQUEST['end_time']))) {//时间戳则不转换
            $filter['end_time'] = trim($_REQUEST['end_time']);
        }

        $commission_info = $this->commissionService->getSellerCommissionInfo($filter['id']);
        $commission_basic = $commission_info;

        if (isset($commission_info['percent_value'])) {
            $percent_value = $commission_info['percent_value'] / 100;
        } else {
            $percent_value = 1;
        }

        $filter['commission_model'] = $commission_info ? $commission_info['commission_model'] : 0;

        $res = OrderInfo::whereRaw(1);

        if ($filter['order_sn']) {
            $res = $res->where('order_sn', 'LIKE', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }

        if ($filter['consignee']) {
            $res = $res->where('consignee', 'LIKE', '%' . mysql_like_quote($filter['consignee']) . '%');
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

        if (isset($filter['state']) && $filter['state'] > -1 && !empty($filter['state'])) {// liu  判断是否有结算状态查询
            $res = $res->where('is_settlement', $filter['state']);
        }

        if (!empty($filter['start_time'])) {
            $res = $res->where('add_time', '>=', $filter['start_time']);
        }

        if (!empty($filter['end_time'])) {
            $res = $res->where('add_time', '<=', $filter['end_time']);
        }

        $res = $res->with([
            'getSellerBillOrder' => function ($query) {
                $query->with([
                    'getSellerCommissionBill'
                ]);
            },
            'getOrderReturn' => function ($query) {
                $query->with([
                    'getSellerNegativeOrder'
                ]);
            }
        ]);

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

        //主订单下有子订单时，则主订单不显示
        $res = $this->orderCommonService->orderQuerySelect($res, 'confirm_take');
        $res = $res->where('main_count', 0);
        $res = $res->where('ru_id', $filter['id']);

        /* 记录总数 */
        $filter['record_count'] = $res->count();
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $res = $res->selectRaw("*,(" . $this->commissionService->orderCommissionTotalField() . ") AS order_total_fee," .
            "(" . order_commission_field('') . ") AS total_fee," .
            "(" . order_activity_field_add('') . ") AS activity_fee ");
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);

        $row = $this->baseRepository->getToArrayGet($res);

        $count = count($row);

        for ($i = 0; $i < $count; $i++) {

            /* 查询已结算比例值 start */
            $res = SellerAccountLog::where('order_id', $row[$i]['order_id'])->where('log_type', 2);
            $account_log = $this->baseRepository->getToArrayFirst($res);

            $row[$i]['is_rectified'] = 0;
            if ($account_log && $account_log['percent_value']) {
                $percent_value = $account_log['percent_value'] / 100;
            } else {

                if ($row[$i]['chargeoff_status'] > 0) {

                    $bill = $row[$i]['get_seller_bill_order']['get_seller_commission_bill'] ?? [];

                    if ($bill) {
                        $commission_info['commission_model'] = $bill['commission_model'];
                        $commission_info['percent_value'] = $bill['proportion'];
                        $row[$i]['bill_sn'] = $bill['bill_sn'];
                    } else {
                        $commission_info['commission_model'] = '';
                        $commission_info['percent_value'] = '';
                        $row[$i]['bill_sn'] = '';
                    }

                    $percent_value = $commission_info['percent_value'];

                    if (!empty($percent_value)) {
                        if ($percent_value > 1) {
                            $percent_value = $percent_value / 100;
                        }
                    }

                } else {
                    $row[$i]['is_rectified'] = 1;
                    $commission_info = $commission_basic;
                }
            }

            if (!is_numeric($percent_value)) {
                $percent_value = 0;
            }

            $row[$i]['commission_model'] = isset($commission_info['commission_model']) ? $commission_info['commission_model'] : 0;

            $row[$i]['account_log'] = $account_log;
            $row[$i]['percent_value'] = $percent_value * 100;
            /* 查询已结算比例值 end */

            //查会员名称
            $row[$i]['buyer'] = Users::where('user_id', $row[$i]['user_id'])->value('user_name');
            $row[$i]['buyer'] = !empty($row[$i]['buyer']) ? $row[$i]['buyer'] : $GLOBALS['_LANG']['anonymous'];

            $filter['percent_value'] = $percent_value * 100;

            $row[$i]['formated_order_amount'] = $this->dscRepository->getPriceFormat($row[$i]['order_amount'], true);
            $row[$i]['formated_money_paid'] = $this->dscRepository->getPriceFormat($row[$i]['money_paid'], true);
            $row[$i]['formated_total_fee'] = $this->dscRepository->getPriceFormat($row[$i]['total_fee'], true);
            $row[$i]['short_order_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row[$i]['add_time']);
            $row[$i]['ordersTatus'] = lang('order.os.' . $row[$i]['order_status']) . "|" . lang('order.ps.' . $row[$i]['pay_status']) . "|" . lang('order.ss.' . $row[$i]['shipping_status']);
            $row[$i]['formated_discount'] = $this->dscRepository->getPriceFormat($row[$i]['discount'], true);
            $row[$i]['formated_coupons'] = $this->dscRepository->getPriceFormat($row[$i]['coupons'], true);
            $row[$i]['formated_integral_money'] = $this->dscRepository->getPriceFormat($row[$i]['integral_money'], true);
            $row[$i]['formated_bonus'] = $this->dscRepository->getPriceFormat($row[$i]['bonus'], true);
            $row[$i]['formated_order_total_fee'] = $this->dscRepository->getPriceFormat($row[$i]['order_total_fee'] - $row[$i]['discount'], true);
            $row[$i]['formated_order_amount_field'] = $this->dscRepository->getPriceFormat($row[$i]['total_fee'] + $row[$i]['shipping_fee'], true);
            $row[$i]['formated_shipping_fee'] = $this->dscRepository->getPriceFormat($row[$i]['shipping_fee'], true);
            if ($row[$i]['is_settlement']) {
                $row[$i]['settlement_status'] = $GLOBALS['_LANG']['is_settlement_amount'];
            } else {
                $row[$i]['settlement_status'] = $GLOBALS['_LANG']['no_settlement_amount'];
            }
            $row[$i]['settlement_frozen'] = "";
            if ($row[$i]['is_frozen']) {
                $row[$i]['settlement_frozen'] = $GLOBALS['_LANG']['frozen'];
            }

            $row[$i]['consignee'] = "【" . $row[$i]['consignee'] . "】";

            $return_amount_info = $this->orderRefoundService->orderReturnAmount($row[$i]['order_id']);
            $row[$i]['return_amount'] = $return_amount_info['return_amount'];
            $row[$i]['return_amount'] = !empty($row[$i]['return_amount']) ? $row[$i]['return_amount'] : "0.00";

            $row[$i]['formated_return_amount'] = $this->dscRepository->getPriceFormat($row[$i]['return_amount'], true);

            $row[$i]['is_goods_rate'] = 0;

            $order = [
                'goods_amount' => $row[$i]['goods_amount'],
                'activity_fee' => isset($row[$i]['activity_fee']) ? $row[$i]['activity_fee'] : 0
            ];

            /* 商品佣金比例 start */
            $goods_rate = $this->commissionService->getAloneGoodsRate($row[$i]['order_id'], 0, $order);
            $row[$i]['goods_rate'] = $goods_rate;

            if ($goods_rate) {
                if ($goods_rate['should_amount'] > 0) {
                    $row[$i]['is_goods_rate'] = 1;
                }
            }
            /* 商品佣金比例 end */

            $orderReturn = $row[$i]['get_order_return'] ?? [];
            $orderReturn['return_shipping_fee'] = $orderReturn['return_shipping_fee'] ?? 0;
            $negativeOrder = $orderReturn['get_seller_negative_order'] ?? [];

            if (empty($negativeOrder) && $row[$i]['shipping_fee'] > 0) {
                if ($orderReturn['return_shipping_fee'] > 0 && $row[$i]['shipping_fee'] >= $orderReturn['return_shipping_fee']) {
                    $row[$i]['shipping_fee'] = $row[$i]['shipping_fee'] - $orderReturn['return_shipping_fee'];
                }
            }

            /* 微分销 */
            if (file_exists(MOBILE_DRP)) {
                $brokerage_amount = $this->commissionService->getOrderDrpMoney($row[$i]['total_fee'], $filter['id'], $row[$i]['order_id'], $order);

                $total_return_amount = 0;
                if ($brokerage_amount['total_fee'] > 0) {
                    if (empty($negativeOrder)) {
                        if ($brokerage_amount['total_fee'] >= $row[$i]['return_amount']) {
                            $total_return_amount = $brokerage_amount['total_fee'] - $row[$i]['return_amount'];
                        }
                    } else {
                        $total_return_amount = $brokerage_amount['total_fee'];
                    }
                }

                /* 佣金比率 by wu start */
                if (isset($commission_info['commission_model']) && $commission_info['commission_model']) {
                    $order_goods_commission = $this->commissionService->getOrderGoodsCommission($row[$i]['order_id']);

                    if ($row[$i]['goods_amount'] <= 0) {
                        $row[$i]['goods_amount'] = 1;
                    }

                    if ($order_goods_commission['should_amount'] > 0) {
                        $order_commission = $order_goods_commission['should_amount'] * $total_return_amount / ($order_goods_commission['goods_amount'] - $brokerage_amount['rate_activity']) + $brokerage_amount['should_amount'];
                    } else {
                        $order_commission = $total_return_amount + $brokerage_amount['should_amount'];
                    }

                    $row[$i]['formated_brokerage_amount'] = $this->dscRepository->getPriceFormat($order_commission + $row[$i]['shipping_fee'], true);

                    $effective_amount_price = $order_commission;
                    $row[$i]['effective_amount_price'] = $this->dscRepository->changeFloat($effective_amount_price);

                    if ($order_goods_commission['gain_commission'] > 0) {
                        $row[$i]['gain_commission'] = $order_goods_commission['gain_commission'] * $total_return_amount / ($order_goods_commission['goods_amount'] - $brokerage_amount['rate_activity']) + $brokerage_amount['gain_commission'];
                    } else {
                        $row[$i]['gain_commission'] = $total_return_amount + $brokerage_amount['gain_commission'];
                    }

                } else {
                    $row[$i]['formated_brokerage_amount'] = $this->dscRepository->getPriceFormat($total_return_amount * $percent_value + $row[$i]['shipping_fee'] + $brokerage_amount['should_amount'], true);

                    $effective_amount_price = $total_return_amount * $percent_value + $brokerage_amount['should_amount'];
                    $row[$i]['effective_amount_price'] = $this->dscRepository->changeFloat($effective_amount_price);

                    $gain_commission = $total_return_amount * (1 - $percent_value) + $brokerage_amount['gain_commission'];
                    $row[$i]['gain_commission'] = $this->dscRepository->changeFloat($gain_commission);
                }

                if ($brokerage_amount['should_amount'] > 0) {
                    $row[$i]['is_goods_rate'] = 1;
                }

                /* 佣金比率 by wu end */
                $row[$i]['formated_effective_amount'] = $this->dscRepository->getPriceFormat($total_return_amount, true);
                $row[$i]{'formated_drp_commission'} = isset($row[$i]['drp_money']) ? $this->dscRepository->getPriceFormat($row[$i]['drp_money'], true) : $this->dscRepository->getPriceFormat(0);
                $row[$i]['brokerage_amount_price'] = $total_return_amount;
                $row[$i]['formated_drp_commission'] = $this->dscRepository->getPriceFormat($brokerage_amount['drp_money'], true);

                $row[$i]['total_fee'] = $brokerage_amount['total_fee'];
            } else {

                /* 商品佣金比例 start */
                if ($goods_rate) {

                    /**
                     * 减去商品单独佣金比例的商品总金额
                     * 剩余有效订单参与店铺佣金的金额
                     */
                    $row[$i]['total_fee'] = $row[$i]['total_fee'] - $goods_rate['total_fee'];

                    /**
                     * 扣除单独设置商品佣金比例的商品总金额
                     */
                    if ($goods_rate['total_fee']) {
                        if ($row[$i]['total_fee'] <= 0) {
                            $row[$i]['is_goods_rate'] = 1;
                        }

                        if ($row[$i]['total_fee'] < 0) {
                            $row[$i]['total_fee'] = 0;
                        }
                    }
                }
                /* 商品佣金比例 end */

                $total_return_amount = 0;
                if ($row[$i]['total_fee'] > 0) {

                    $negativeOrder = $row[$i]['get_order_return']['get_seller_negative_order'] ?? [];

                    if (empty($negativeOrder)) {
                        if ($row[$i]['total_fee'] >= $row[$i]['return_amount']) {
                            $total_return_amount = $row[$i]['total_fee'] - $row[$i]['return_amount'];
                        }
                    } else {
                        $total_return_amount = $row[$i]['total_fee'];
                    }
                }

                $goods_rate['should_amount'] = $goods_rate['should_amount'] ?? 0;

                /* 佣金比率 by wu start */
                if ($commission_info['commission_model']) {
                    $order_goods_commission = $this->commissionService->getOrderGoodsCommission($row[$i]['order_id']);

                    if ($row[$i]['goods_amount'] <= 0) {
                        $row[$i]['goods_amount'] = 1;
                    }

                    if ($order_goods_commission['should_amount'] > 0) {
                        $order_commission = $order_goods_commission['should_amount'] * $total_return_amount / $order_goods_commission['goods_amount'] + $goods_rate['should_amount'];
                    } else {
                        $order_commission = $total_return_amount + $goods_rate['should_amount'];
                    }

                    $row[$i]['formated_brokerage_amount'] = $this->dscRepository->getPriceFormat($order_commission + $row[$i]['shipping_fee'], true);

                    $effective_amount_price = $order_commission;
                    $row[$i]['effective_amount_price'] = number_format($effective_amount_price, 2, '.', '');

                    if ($order_goods_commission['gain_commission'] > 0) {
                        $row[$i]['gain_commission'] = $order_goods_commission['gain_commission'] * $total_return_amount / ($order_goods_commission['goods_amount'] - $goods_rate['rate_activity']) + $goods_rate['gain_commission'];
                    } else {
                        $row[$i]['gain_commission'] = $total_return_amount + $goods_rate['gain_commission'];
                    }
                } else {
                    $row[$i]['formated_brokerage_amount'] = $this->dscRepository->getPriceFormat($total_return_amount * $percent_value + $row[$i]['shipping_fee'] + $goods_rate['should_amount'], true);

                    $effective_amount_price = $total_return_amount * $percent_value + $goods_rate['should_amount'];
                    $row[$i]['effective_amount_price'] = number_format($effective_amount_price, 2, '.', '');

                    $gain_commission = $total_return_amount * (1 - $percent_value) + $goods_rate['gain_commission'];
                    $row[$i]['gain_commission'] = $this->dscRepository->changeFloat($gain_commission);
                }

                /* 佣金比率 by wu end */
                $row[$i]['formated_effective_amount'] = $this->dscRepository->getPriceFormat($total_return_amount, true);
                $row[$i]['brokerage_amount_price'] = $total_return_amount;
            }

            $row[$i]['formated_effective_amount_price'] = $this->dscRepository->getPriceFormat($row[$i]['effective_amount_price'], true);

            $row[$i]['total_fee_price'] = $row[$i]['total_fee'];
            $row[$i]['return_amount_price'] = $row[$i]['return_amount'];


            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$i]['mobile'] = $this->dscRepository->stringToStar($row[$i]['mobile']);
                $row[$i]['buyer'] = $this->dscRepository->stringToStar($row[$i]['buyer']);
                $row[$i]['tel'] = $this->dscRepository->stringToStar($row[$i]['tel']);
                $row[$i]['email'] = $this->dscRepository->stringToStar($row[$i]['email']);
            }
        }

        $settlemen = $this->commissionService->sellerOrderSettlementLog($filter['id']);

        $brokerage_amount['is_settlement_price'] = $settlemen['is_settlement'];
        $brokerage_amount['no_settlement_price'] = $settlemen['no_settlement'];
        $brokerage_amount['is_settlement'] = $this->dscRepository->getPriceFormat($settlemen['is_settlement'], true);
        $brokerage_amount['no_settlement'] = $this->dscRepository->getPriceFormat($settlemen['no_settlement'], true);

        $brokerage_amount['all_price'] = $settlemen['settlement_all'];
        $brokerage_amount['all'] = $this->dscRepository->getPriceFormat($settlemen['settlement_all'], true);

        $arr = ['orders' => $row, 'brokerage_amount' => $brokerage_amount, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获取选中的订单佣金列表
     *
     * @param $ids
     * @return mixed
     */
    public function merchantsOrderListChecked($ids)
    {
        //主订单下有子订单时，则主订单不显示
        $ids = $this->baseRepository->getExplode($ids);
        $res = OrderInfo::where('is_settlement', 0)
            ->whereIn('order_id', $ids);
        $res = $res->where('main_count', 0);
        $res = $this->orderCommonService->orderQuerySelect($res, 'finished');

        /* 查询 */
        $row = $this->baseRepository->getToArrayGet($res);

        return $row;
    }

    /*
     * 获得字符串内的数字
     */

    public function findNum($str = '')
    {
        $str = trim($str);
        if (empty($str)) {
            return '';
        }
        $temp = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $result = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (in_array($str[$i], $temp)) {
                $result .= $str[$i];
            }
        }
        if ($result == '000') {
            $result = 0;
        }
        return $result;
    }

    /*
     *  字符串转换已结算未结算
     */

    public function changeSettlement($str)
    {
        if ($str == '0') {
            $str = $GLOBALS['_LANG']['no_settlement_amount'];
        } else {
            $str = $GLOBALS['_LANG']['is_settlement_amount'];
        }
        return $str;
    }

    public function commissionDownloadList($result)
    {
        if (empty($result)) {
            return $this->commonManageService->i(lang('admin/merchants_commission.data_null'));
        }

        $data = $this->commonManageService->i(lang('admin/merchants_commission.title_attr') . "\n");
        $count = count($result);

        for ($i = 0; $i < $count; $i++) {
            $user_name = $this->commonManageService->i($result[$i]['user_name']);
            $store_name = $this->commonManageService->i($result[$i]['store_name']);
            $companyName = $this->commonManageService->i($result[$i]['companyName']);
            $company_adress = $this->commonManageService->i($result[$i]['company_adress']);
            $company_contactTel = $this->commonManageService->i($result[$i]['company_contactTel']);
            $order_valid_total = $this->commonManageService->i($result[$i]['total_fee_price']);
            $order_refund_total = $this->commonManageService->i($result[$i]['order_refund_total']);
            $is_settlement = $this->commonManageService->i(isset($result[$i]['is_settlement_price']['all_brokerage_amount']) ? $result[$i]['is_settlement_price']['all_brokerage_amount'] : 0.00);
            $no_settlement = $this->commonManageService->i(isset($result[$i]['no_settlement_price']['all_brokerage_amount']) ? $result[$i]['no_settlement_price']['all_brokerage_amount'] : 0.00);

            if ($is_settlement == '0.00') {
                $is_settlement = $this->commonManageService->i(session()->has('commission_list.' . $i . '.is_settlement') ? str_replace('¥', '', session()->get('commission_list.' . $i . '.is_settlement')) : 0.00);
            }
            if ($no_settlement == '0.00') {
                $no_settlement = $this->commonManageService->i(session()->has('commission_list.' . $i . '.no_settlement') ? str_replace('¥', '', session()->get('commission_list.' . $i . '.no_settlement')) : 0.00);
            }

            if ($company_adress) {
                $company_adress = str_replace([",", "，"], "_", $company_adress);
            }

            $data .= $user_name . ',' . $store_name . ',' . $companyName . ',' .
                $company_adress . ',' . $company_contactTel . ',' . $order_valid_total . ',' .
                $order_refund_total . ',' . $is_settlement . ',' . $no_settlement . "\n";
        }
        return $data;
    }

    /**
     * 负账单列表
     *
     * @return array
     */
    public function negativeBillList()
    {
        /* 过滤信息 */
        $filter['id'] = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $filter['bill_sn'] = isset($_REQUEST['bill_sn']) && !empty($_REQUEST['bill_sn']) ? dsc_addslashes($_REQUEST['bill_sn']) : '';
        $filter['commission_bill_sn'] = isset($_REQUEST['commission_bill_sn']) && !empty($_REQUEST['commission_bill_sn']) ? dsc_addslashes($_REQUEST['commission_bill_sn']) : '';
        $filter['commission_bill_id'] = isset($_REQUEST['commission_bill_id']) && !empty($_REQUEST['commission_bill_id']) ? intval($_REQUEST['commission_bill_id']) : 0;
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'end_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = SellerNegativeBill::where('seller_id', $filter['id']);

        if ($filter['bill_sn']) {
            $row = $row->where('bill_sn', 'like', '%' . mysql_like_quote($filter['bill_sn']) . '%');
        }

        if ($filter['commission_bill_id']) {
            $row = $row->where('commission_bill_id', $filter['commission_bill_id']);
        }

        if ($filter['commission_bill_sn']) {
            $row = $row->where('commission_bill_sn', 'like', '%' . mysql_like_quote($filter['commission_bill_sn']) . '%');
        }

        $res = $record_count = $row;

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0) {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        } else {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();

        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $start = ($filter['page'] - 1) * $filter['page_size'];
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $res[$key]['format_return_amount'] = $this->dscRepository->getPriceFormat($val['return_amount']);
                $res[$key]['format_return_shippingfee'] = $this->dscRepository->getPriceFormat($val['return_shippingfee']);
                $res[$key]['format_return_rate_price'] = $this->dscRepository->getPriceFormat($val['return_rate_price']);
                $res[$key]['format_return_total'] = $this->dscRepository->getPriceFormat($val['return_amount'] + $val['return_shippingfee']);
                $res[$key]['start_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $val['start_time']);
                $res[$key]['end_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $val['end_time']);
                $res[$key]['chargeoff_status'] = $GLOBALS['_LANG']['negative_chargeoff_status'][$val['chargeoff_status']];
            }
        }

        $arr = [
            'bill_list' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }


    /**
     * 负账单列表
     *
     * @return array
     */
    public function negativeOrderList()
    {
        /* 过滤信息 */
        $filter['seller_id'] = isset($_REQUEST['seller_id']) && !empty($_REQUEST['seller_id']) ? intval($_REQUEST['seller_id']) : 0;
        $filter['negative_id'] = isset($_REQUEST['negative_id']) && !empty($_REQUEST['negative_id']) ? intval($_REQUEST['negative_id']) : 0;
        $filter['return_sn'] = isset($_REQUEST['return_sn']) && !empty($_REQUEST['return_sn']) ? dsc_addslashes($_REQUEST['return_sn']) : '';
        $filter['order_sn'] = isset($_REQUEST['order_sn']) && !empty($_REQUEST['order_sn']) ? dsc_addslashes($_REQUEST['order_sn']) : '';
        $filter['start_time'] = isset($_REQUEST['start_time']) && !empty($_REQUEST['start_time']) ? dsc_addslashes($_REQUEST['start_time']) : '';
        $filter['end_time'] = isset($_REQUEST['end_time']) && !empty($_REQUEST['end_time']) ? dsc_addslashes($_REQUEST['end_time']) : '';
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = SellerNegativeOrder::where('seller_id', $filter['seller_id'])
            ->where('negative_id', $filter['negative_id']);

        if ($filter['return_sn']) {
            $row = $row->where('return_sn', 'like', '%' . mysql_like_quote($filter['return_sn']) . '%');
        }

        if ($filter['order_sn']) {
            $row = $row->where('order_sn', 'like', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }

        $res = $record_count = $row;

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0) {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        } else {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();

        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $start = ($filter['page'] - 1) * $filter['page_size'];
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $filter['format_return_amount'] = 0;
        $filter['format_return_shippingfee'] = 0;
        $filter['format_return_rate_price'] = 0;
        if ($res) {
            foreach ($res as $key => $val) {
                $res[$key]['format_return_amount'] = $this->dscRepository->getPriceFormat($val['return_amount']);
                $res[$key]['format_return_shippingfee'] = $this->dscRepository->getPriceFormat($val['return_shippingfee']);
                $res[$key]['format_return_rate_price'] = $this->dscRepository->getPriceFormat($val['return_rate_price']);
                $res[$key]['add_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $val['add_time']);

                if ($key == 0) {
                    $bill_sn = SellerNegativeBill::where('id', $val['negative_id'])->value('bill_sn');
                    $filter['bill_sn'] = $bill_sn ? $bill_sn : '';
                }

                $filter['format_return_amount'] += $val['return_amount'];
                $filter['format_return_shippingfee'] += $val['return_shippingfee'];
                $filter['format_return_rate_price'] += $val['return_rate_price'];
            }
        }

        $filter['format_return_amount'] = $this->dscRepository->getPriceFormat($filter['format_return_amount']);
        $filter['format_return_shippingfee'] = $this->dscRepository->getPriceFormat($filter['format_return_shippingfee']);
        $filter['format_return_rate_price'] = $this->dscRepository->getPriceFormat($filter['format_return_rate_price']);

        $arr = [
            'order_list' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }
}
