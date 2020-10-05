<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Phpzip;
use App\Models\AdminUser;
use App\Models\BackOrder;
use App\Models\DeliveryOrder;
use App\Models\GiftGardLog;
use App\Models\Goods;
use App\Models\MerchantsAccountLog;
use App\Models\MerchantsPercent;
use App\Models\MerchantsServer;
use App\Models\MerchantsShopInformation;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\OrderSettlementLog;
use App\Models\SellerAccountLog;
use App\Models\SellerBillOrder;
use App\Models\SellerCommissionBill;
use App\Models\SellerNegativeBill;
use App\Models\SellerNegativeOrder;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Merchant\MerchantsCommissionManageService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心服务站管理
 */
class MerchantsCommissionController extends InitController
{
    protected $commissionService;
    protected $orderService;
    protected $commonManageService;
    protected $dscRepository;
    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $merchantsCommissionManageService;
    protected $storeCommonService;
    protected $orderCommonService;

    public function __construct(
        CommissionService $commissionService,
        OrderService $orderService,
        CommonManageService $commonManageService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        MerchantsCommissionManageService $merchantsCommissionManageService,
        StoreCommonService $storeCommonService,
        OrderCommonService $orderCommonService
    )
    {
        $this->commissionService = $commissionService;
        $this->orderService = $orderService;
        $this->commonManageService = $commonManageService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->merchantsCommissionManageService = $merchantsCommissionManageService;
        $this->storeCommonService = $storeCommonService;
        $this->orderCommonService = $orderCommonService;
    }

    public function index()
    {
        load_helper('order');

        define('SUPPLIERS_ACTION_LIST', 'delivery_view,back_view');

        $admin_id = get_admin_id();
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /* ------------------------------------------------------ */
        //-- 服务站列表
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '03_merchants_commission']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brokerage_amount_list']); // 当前导航
            $this->smarty->assign('action_link3', ['href' => 'javascript:download_list();', 'text' => $GLOBALS['_LANG']['export_all_suppliers']]);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $result = $this->merchantsCommissionManageService->merchantsCommissionList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('merchants_commission_list', $result['result']);
            $this->smarty->assign('admin_commission', $result['admin_commission']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_commission_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 获得该管理员的权限 */
            $priv_str = AdminUser::where('user_id', session('admin_id'))->value('action_list');
            $priv_str = $priv_str ? $priv_str : '';

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str != 'all') {
                $this->smarty->assign('no_all', 0);
                $ser_name = $GLOBALS['_LANG']['suppliers_list_server'];
            } else {
                $this->smarty->assign('no_all', 1);
            }

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            /* 查询 */
            $result = $this->merchantsCommissionManageService->merchantsCommissionList();

            $this->smarty->assign('merchants_commission_list', $result['result']);
            $this->smarty->assign('admin_commission', $result['admin_commission']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('merchants_commission_list.dwt'), '', ['filter' => $result['filter'], 'page_count' => $result['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 查询单页总金额
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'commission_amount') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $seller = isset($_REQUEST['seller']) && !empty($_REQUEST['seller']) ? addslashes($_REQUEST['seller'], 1) : 0;
            $seller = !empty($seller) ? explode(",", $seller) : [];

            /* 当页还是所有 */
            $type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $cycle = !isset($_REQUEST['cycle']) ? '-1' : intval($_REQUEST['cycle']);

            $store_num = 0;
            $total_amount = 0;
            if ($type == 'all') {
                $res = MerchantsShopInformation::select('user_id')->where('merchants_audit', 1);

                if ($cycle != -1) {
                    $res = $res->whereHas('getMerchantsServer', function ($query) use ($cycle) {
                        $query->where('cycle', $cycle);
                    });
                }
                $seller = $this->baseRepository->getToArrayGet($res);
                $seller = $this->baseRepository->getFlatten($seller);

                /* 计算销售总额 */
                $total_amount = 0;
                $store_num = $seller ? count($seller) : 0;

                if ($store_num > 0) {
                    $total_amount = OrderInfo::selectRaw("SUM(" . order_commission_field('') . ") AS total_amount ")
                        ->whereIn('ru_id', $seller);

                    $total_amount = $this->orderCommonService->orderQuerySelect($total_amount, 'finished');

                    $total_amount = $total_amount->value('total_amount');

                    $total_amount = $total_amount ? $total_amount : 0;

                    $total_amount = $total_amount > 0 ? $total_amount : 0;
                }
            }

            $admin_commission = [
                'gain_no_settlement' => !empty($seller) ? $this->commissionService->getSettlementPrice($seller, 0, 0, 'gain_amount') : 0, //收取未结算佣金金额
                'gain_is_settlement' => !empty($seller) ? $this->commissionService->getSettlementPrice($seller, 0, 1, 'gain_amount') : 0, //收取已结算佣金金额
                'no_settlement' => !empty($seller) ? $this->commissionService->getSettlementPrice($seller, 0, 0, 'actual_amount') : 0, //未结算佣金金额
                'is_settlement' => !empty($seller) ? $this->commissionService->getSettlementPrice($seller, 0, 1, 'actual_amount') : 0, //已结算佣金金额
            ];

            /* 当页还是所有 */
            if ($type == 'all') {
                $admin_all = [];
                $admin_all['store_num'] = $store_num;
                $admin_all['total_amount'] = $total_amount;

                $admin_all['gain_no_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['gain_no_settlement']);
                $admin_all['gain_is_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['gain_is_settlement']);
                $admin_all['is_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['is_settlement']);
                $admin_all['no_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['no_settlement']);
                $result['admin_all'] = $admin_all;
            }

            $admin_commission['gain_no_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['gain_no_settlement']);
            $admin_commission['gain_is_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['gain_is_settlement']);
            $admin_commission['is_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['is_settlement'], false);
            $admin_commission['no_settlement'] = $this->dscRepository->getPriceFormat($admin_commission['no_settlement'], false);
            $result['commission'] = $admin_commission;

            $result['count'] = count($seller);

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 列表页编辑名称
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_suppliers_ser_name') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $name = json_str_iconv(trim($_POST['val']));

            /* 判断名称是否重复 */
            $res = MerchantsServer::where('suppliers_name', $name)->where('suppliers_ser_id', '<>', $id)->count();
            if ($res > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['suppliers_name_exist'], $name));
            } else {
                /* 保存服务站信息 */
                $data = ['suppliers_name' => $name];
                $res = MerchantsServer::where('suppliers_ser_id', $id)->update($data);
                if ($res > 0) {
                    /* 记日志 */
                    admin_log($name, 'edit', 'suppliers_ser');

                    clear_cache_files();

                    return make_json_result(stripslashes($name));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['agency_edit_fail'], $name));
                }
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除服务站
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['id']);

            MerchantsServer::where('server_id', $id)->delete();

            /* 清除缓存 */
            clear_cache_files();

            $url = 'merchants_commission.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /* ------------------------------------------------------ */
        //-- 批量操作
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            $nowTime = $this->timeRepository->getGmTime();
            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                /* 检查权限 */
                admin_priv('merchants_commission');

                $ids = $_POST['checkboxes'];

                if (isset($_POST['remove'])) {
                    $ids = $this->baseRepository->getExplode($ids);
                    $res = MerchantsServer::whereIn('suppliers_ser_id', $ids);
                    $suppliers = $this->baseRepository->getToArrayGet($res);

                    foreach ($suppliers as $key => $value) {
                        /* 判断服务站是否存在订单 */
                        $suppliers_ser_id = $value['suppliers_ser_id'];
                        $order_exists = OrderInfo::whereHas('getOrderGoods', function ($query) use ($suppliers_ser_id) {
                            $query->whereHas('getGoods', function ($query) use ($suppliers_ser_id) {
                                $query->where('suppliers_ser_id', $suppliers_ser_id);
                            });
                        })->count();
                        if ($order_exists > 0) {
                            unset($suppliers[$key]);
                        }

                        /* 判断服务站是否存在商品 */
                        $goods_exists = Goods::where('suppliers_ser_id', $value['suppliers_ser_id'])->count();
                        if ($goods_exists > 0) {
                            unset($suppliers[$key]);
                        }
                    }
                    if (empty($suppliers)) {
                        return sys_msg($GLOBALS['_LANG']['batch_drop_no']);
                    }

                    MerchantsServer::whereIn('suppliers_ser_id', $ids)->delete();

                    /* 更新管理员、发货单关联、退货单关联和订单关联的服务站 */
                    AdminUser::whereIn('suppliers_ser_id', $ids)->delete();
                    DeliveryOrder::whereIn('suppliers_ser_id', $ids)->delete();
                    BackOrder::whereIn('suppliers_ser_id', $ids)->delete();

                    /* 记日志 */
                    $suppliers_names = '';
                    foreach ($suppliers as $value) {
                        $suppliers_names .= $value['suppliers_name'] . '|';
                    }
                    admin_log($suppliers_names, 'remove', 'suppliers_ser');

                    /* 清除缓存 */
                    clear_cache_files();

                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok']);
                }
                if ($_POST['type'] == 'button_remove') {// begin liu
                    return sys_msg($GLOBALS['_LANG']['is not supported']);
                } elseif ($_POST['type'] == 'button_closed') {
                    $ids = $_POST['checkboxes'];
                    $result = $this->merchantsCommissionManageService->merchantsOrderListChecked($ids);
                    $settlement = intval(1);
                    if (empty($result)) {
                        return sys_msg($GLOBALS['_LANG']['no_order']);
                    } else {
                        /* 操作日志  by kong */
                        foreach ($ids as $k => $v) {
                            if (!empty($v)) {
                                /* 插入操作日志  by kong   start */
                                $data = [
                                    'admin_id' => session('admin_id'),
                                    'gift_gard_id' => $v,
                                    'delivery_status' => $settlement,
                                    'addtime' => $nowTime,
                                    'handle_type' => 'toggle_on_settlement'
                                ];
                                GiftGardLog::insert($data);
                                /* by kong end */

                                $order_goods = get_order_seller_id($v);
                                $amount = $this->commissionService->getSellerSettlementAmount($v, $order_goods['ru_id']);

                                $other['admin_id'] = session('admin_id');
                                $other['ru_id'] = $order_goods['ru_id'];
                                $other['order_id'] = $v;
                                $other['amount'] = $amount; //结算金额
                                $other['add_time'] = $nowTime;
                                $other['log_type'] = 2;
                                $other['is_paid'] = 1;

                                SellerAccountLog::insert($other);

                                SellerShopinfo::where('ru_id', $order_goods['ru_id'])->increment('seller_money', $amount);
                            }
                        }

                        $ids = $this->baseRepository->getExplode($ids);
                        $data = ['is_settlement' => $settlement];
                        $query = OrderInfo::whereIn('order_id', $ids)->update($data);

                        if ($query) {
                            /* 清除缓存 */
                            clear_cache_files();
                            /* 提示信息 */
                            return sys_msg($GLOBALS['_LANG']['batch_closed_success']);
                        }
                    }
                } else {
                    if (empty($_POST['type'])) {
                        return sys_msg($GLOBALS['_LANG']['choose_batch']);
                    }
                }                     //end liu
            }
        }

        /* ------------------------------------------------------ */
        //-- 添加/编辑佣金设置
        /* ------------------------------------------------------ */
        elseif (in_array($_REQUEST['act'], ['add', 'edit'])) {
            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'suppliers_list_server']);
            $this->smarty->assign('action_link', ['href' => 'merchants_commission.php?act=list', 'text' => $GLOBALS['_LANG']['suppliers_list_server']]);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['suppliers_list_server']); // 当前导航

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            if (empty($id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            $res = MerchantsServer::where('user_id', $id);
            $server = $this->baseRepository->getToArrayFirst($res);

            $res = MerchantsPercent::orderBy('sort_order');
            $percent_list = $this->baseRepository->getToArrayGet($res);

            if ($server) {

                if ($server['bill_time']) {
                    $server['bill_time'] = local_date("Y-m-d", $server['bill_time']);
                }

                $this->smarty->assign('form_action', 'update');
            } else {
                $this->smarty->assign('form_action', 'insert');
            }

            $res = SellerShopinfo::where('ru_id', $id);
            $seller_shopinfo = $this->baseRepository->getToArrayFirst($res);

            $this->smarty->assign('seller_shopinfo', $seller_shopinfo);
            $this->smarty->assign('user_id', $id);
            $this->smarty->assign('server', $server);
            $this->smarty->assign('percent_list', $percent_list);
            $this->smarty->assign('nowtime', local_date("Y-m-d", $this->timeRepository->getGmTime()));

            $shop_name = $this->merchantCommonService->getShopName($id, 1);
            $this->smarty->assign('shop_name', $shop_name);


            return $this->smarty->display('merchants_commission_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 插入/更新佣金
        /* ------------------------------------------------------ */
        elseif (in_array($_REQUEST['act'], ['insert', 'update'])) {
            /* 检查权限 */
            admin_priv('merchants_commission');

            $user_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $commission_model = isset($_REQUEST['commission_model']) && !empty($_REQUEST['commission_model']) ? intval($_POST['commission_model']) : 0;
            $settlement_cycle = isset($_REQUEST['settlement_cycle']) && !empty($_REQUEST['settlement_cycle']) ? intval($_POST['settlement_cycle']) : 0;

            $day_number = !empty($_POST['day_number']) ? intval($_POST['day_number']) : 0;
            $bill_time = !empty($_POST['bill_time']) ? local_strtotime(addslashes(trim($_POST['bill_time']))) : '';
            $bill_freeze_day = !empty($_POST['bill_freeze_day']) ? intval($_POST['bill_freeze_day']) : 0;

            /* 商家信用额度 start */
            $credit_money = !empty($_POST['credit_money']) ? floatval($_POST['credit_money']) : 0;

            $data = ['credit_money' => $credit_money];
            SellerShopinfo::where('ru_id', $user_id)->update($data);
            /* 商家信用额度 end */

            if ($settlement_cycle != 7) {
                $bill_time = '';
            }

            /* 提交值 */
            $server = [
                'commission_model' => $commission_model,
                'cycle' => $settlement_cycle,
                'bill_freeze_day' => $bill_freeze_day,
                'suppliers_percent' => intval($_POST['suppliers_percent']),
                'day_number' => $day_number,
                'bill_time' => $bill_time,
                'suppliers_desc' => addslashes(trim($_POST['suppliers_desc']))
            ];

            if ($_REQUEST['act'] == 'insert') {
                $server['user_id'] = $user_id;
                MerchantsServer::insert($server);
            } else {
                MerchantsServer::where('user_id', $user_id)->update($server);
            }

            //删除账单商家缓存文件
            cache()->forget('seller_list');
            $this->commissionService->getCacheSellerList();

            /* 提示信息 */
            $links[] = ['href' => 'merchants_commission.php?act=edit&id=' . $user_id, 'text' => $GLOBALS['_LANG']['back_suppliers_server_list']];
            return sys_msg($GLOBALS['_LANG']['suppliers_server_ok'], 0, $links);
        }

        /* ------------------------------------------------------ */
        //-- 商家订单别表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_list') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            cache()->forget('merchants_download_content_' . $admin_id);

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'brokerage_order_list']);

            $user_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            if (empty($user_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            $server = $this->commissionService->getSellerCommissionInfo($user_id);
            $percent_value = isset($server['percent_value']) ? $server['percent_value'] . '%' : 0 . '%';

            $this->smarty->assign('percent_value', $percent_value);

            /* 模板赋值 */
            $this->smarty->assign('action_link', ['href' => 'javascript:order_downloadList();', 'text' => $GLOBALS['_LANG']['export_merchant_commission']]); //liu
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brokerage_order_list']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $commission_model = $server ? $server['commission_model'] : 0;
            $this->smarty->assign('commission_model', $commission_model); // 翻页参数

            $order_list = $this->merchantsCommissionManageService->merchantsOrderList();

            $this->smarty->assign('user_id', $user_id); //liu  供批量结算用
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('brokerage_amount', $order_list['brokerage_amount']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('server_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_order_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $this->smarty->assign('action_link', ['href' => 'javascript:order_downloadList();', 'text' => $GLOBALS['_LANG']['export_merchant_commission']]); //liu

            $order_list = $this->merchantsCommissionManageService->merchantsOrderList();
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('brokerage_amount', $order_list['brokerage_amount']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            return make_json_result($this->smarty->fetch('merchants_order_list.dwt'), '', ['filter' => $order_list['filter'], 'page_count' => $order_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 修改纠正已结算订单店铺佣金比例
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_percent_value') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_id = intval($_POST['id']);
            $percent_value = floatval($_POST['val']);

            $data = ['percent_value' => $percent_value];
            SellerAccountLog::where('order_id', $order_id)->where('log_type', 2)->update($data);

            clear_cache_files();
            return make_json_result($percent_value);
        }

        /* ------------------------------------------------------ */
        //-- 修改结算状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_settlement') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            /* 检查是否重复 */
            $order_exc = OrderInfo::where('order_id', $order_id);
            $order_exc = $this->baseRepository->getToArrayFirst($order_exc);

            if ($order_exc && $on_sale > 0) {
                if ($order_exc['is_frozen']) {
                    return make_json_error($GLOBALS['_LANG']['toggle_on_settlement_prompt_one']);
                }
                if ($order_exc['is_settlement']) {
                    return make_json_error($GLOBALS['_LANG']['not_settlement']);
                }

                $nowTime = $this->timeRepository->getGmTime();

                $order_goods = get_order_seller_id($order_id);
                $amount = $this->commissionService->getSellerSettlementAmount($order_id, $order_goods['ru_id']);

                $commission_info = $this->commissionService->getSellerCommissionInfo($order_goods['ru_id']);

                if ($commission_info && $commission_info['percent_value']) {
                    $percent_value = $commission_info['percent_value'];
                } else {
                    $percent_value = 1;
                }

                $other['admin_id'] = session('admin_id');
                $other['ru_id'] = $order_goods['ru_id'];
                $other['order_id'] = $order_id;
                $other['amount'] = $amount; //结算金额
                $other['add_time'] = $nowTime; //结算时间
                $other['log_type'] = 2;
                $other['is_paid'] = 1;
                $other['percent_value'] = $percent_value;

                OrderInfo::where('order_id', $order_id)->update([
                    'is_settlement' => $on_sale
                ]);

                SellerAccountLog::insert($other);

                $list = [
                    'seller_money' => "seller_money + " . $amount
                ];
                $otherGoods = $this->baseRepository->getDbRaw($list);
                SellerShopinfo::where('ru_id', $order_goods['ru_id'])->update($otherGoods);

                OrderSettlementLog::where('order_id', $order_id)->update([
                    'is_settlement' => 1,
                    'type' => 1,
                    'update_time' => $this->timeRepository->getGmTime()
                ]);

                $change_desc = sprintf($GLOBALS['_LANG']['01_admin_settlement'], session('admin_name'));
                $user_account_log = [
                    'user_id' => $order_goods['ru_id'],
                    'user_money' => $amount,
                    'change_time' => $this->timeRepository->getGmTime(),
                    'change_desc' => $change_desc,
                    'change_type' => 2
                ];
                MerchantsAccountLog::insert($user_account_log);

                $gitLog = [
                    'admin_id' => session('admin_id'),
                    'gift_gard_id' => $order_id,
                    'delivery_status' => $on_sale,
                    'addtime' => $nowTime,
                    'handle_type' => 'toggle_on_settlement'
                ];
                GiftGardLog::insert($gitLog);

                if ($order_exc['chargeoff_status'] == 1) {
                    $bill_id = SellerBillOrder::where('order_id', $order_id)->value('bill_id');
                    $bill_id = $bill_id ? $bill_id : 0;

                    $bill_info = SellerCommissionBill::where('id', $bill_id);
                    $bill_info = $this->baseRepository->getToArrayFirst($bill_info);

                    if ($bill_info) {

                        $bill_info['commission_model'] = $commission_info['commission_model'] ?? 0;
                        $bill_info['bill_freeze_day'] = $commission_info['bill_freeze_day'] ?? 0;

                        $bill_detail = $this->commissionService->getBillAmountDetail($bill_info['id'], $bill_info['seller_id'], $bill_info['proportion'], $bill_info['start_time'], $bill_info['end_time'], $bill_info['chargeoff_status'], $bill_info['commission_model']);

                        $bill_other['order_amount'] = $bill_detail['order_amount'];
                        $bill_other['shipping_amount'] = $bill_detail['shipping_amount'];
                        $bill_other['return_amount'] = $bill_detail['return_amount'];
                        $bill_other['return_shippingfee'] = $bill_detail['return_shippingfee'];
                        $bill_other['gain_commission'] = $bill_detail['gain_commission'];
                        $bill_other['should_amount'] = $bill_detail['should_amount'];
                        $bill_other['drp_money'] = $bill_detail['drp_money'];
                        $bill_other['commission_model'] = $bill_detail['commission_model'];
                        $bill_other['chargeoff_time'] = $this->timeRepository->getGmTime();

                        SellerCommissionBill::where('id', $bill_info['id'])->update($bill_other);
                    }
                }
            }

            clear_cache_files();
            return make_json_result($on_sale);
        }
        /* ------------------------------------------------------ */
        //-- 修改结算状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_frozen') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            $nowTime = $this->timeRepository->getGmTime();

            $data = ['is_frozen' => $on_sale];
            OrderInfo::where('order_id', $order_id)->update($data);
            if ($on_sale == 1) {
                $type = 3;
            } else {
                $type = 2;
            }
            /* 插入操作日志  by kong   start */
            $data = [
                'admin_id' => session('admin_id'),
                'gift_gard_id' => $order_id,
                'delivery_status' => $type,
                'addtime' => $nowTime,
                'handle_type' => 'toggle_on_settlement'
            ];
            GiftGardLog::insert($data);
            /* by kong end */
            clear_cache_files();
            return make_json_result($on_sale);
        }
        /* ------------------------------------------------------ */
        //-- 查询商家店铺
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query_merchants_info') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);

            $date = ['shoprz_brandName, shopNameSuffix'];
            $user = get_table_date('merchants_shop_information', "user_id = '$user_id'", $date);
            $user['user_id'] = $user_id;

            clear_cache_files();
            return make_json_result($user);
        }
        /* ------------------------------------------------------ */
        //--Excel文件下载 所有商家
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'commission_download') {
            $filename = local_date('YmdHis') . ".csv";
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=" . $filename);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');

            $commission_list = $this->merchantsCommissionManageService->merchantsCommissionList();

            echo $this->merchantsCommissionManageService->commissionDownloadList($commission_list['result']);
        }

        /* ------------------------------------------------------ */
        //--Excel文件下载 商家佣金明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_download') {
            $result = ['is_stop' => 0];

            $page = !empty($_REQUEST['page_down']) ? intval($_REQUEST['page_down']) : 0;//处理的页数
            $page_count = !empty($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0;//总页数
            $merchants_order_list = $this->merchantsCommissionManageService->merchantsOrderList($page);//获取订单数组

            $merchants_download_content = cache("merchants_download_content_" . $admin_id);
            $merchants_download_content = !is_null($merchants_download_content) ? $merchants_download_content : [];

            $merchants_download_content[] = $merchants_order_list;

            cache()->forever("merchants_download_content_" . $admin_id, $merchants_download_content);

            $result['page'] = $page;
            if ($page < $page_count) {
                $result['is_stop'] = 1;//未结算标识
                $result['next_page'] = $page + 1;
            }
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //--Excel文件下载 商家佣金明细
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'merchant_download') {
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $shop_name = $this->merchantCommonService->getShopName($id, 1);
            $filename = $shop_name . "-" . date('YmdHis') . ".zip";

            // 获取所有商家的下载数据 按照商家分组

            $merchants_download_content = cache("merchants_download_content_" . $admin_id);
            $merchants_download_content = !is_null($merchants_download_content) ? $merchants_download_content : [];

            $zip = new Phpzip;
            if (!empty($merchants_download_content['orders'])) {
                foreach ($merchants_download_content['orders'] as $k => $merchants_order_list) {
                    $k++;
                    $content = $this->merchantsCommissionManageService->orderDownloadList($merchants_order_list['orders']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['commission_total']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $merchants_order_list['brokerage_amount']['all_price']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['is_settlement_amount']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $merchants_order_list['brokerage_amount']['is_settlement_price']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['no_settlement_amount']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $merchants_order_list['brokerage_amount']['no_settlement_price']) . "\t\n";
                    $zip->add_file($content, date('YmdHis') . '-' . $k . '.csv');
                }
            }

            /* 清除缓存 */
            cache()->forget('merchants_download_content_' . $admin_id);

            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        }
        /* ------------------------------------------------------ */
        //--商家订单列表  操作日志 by kong
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'handle_log') {
            /* 权限判断 */
            admin_priv('merchants_commission');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['handle_log']);
            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['brokerage_order_list'], 'href' => 'merchants_commission.php?act=order_list&id=' . $user_id]);

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $gift_gard_log = $this->commonManageService->getGiftGardLog($id, 'toggle_on_settlement');

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('gift_gard_log', $gift_gard_log['pzd_list']);
            $this->smarty->assign('filter', $gift_gard_log['filter']);
            $this->smarty->assign('record_count', $gift_gard_log['record_count']);
            $this->smarty->assign('page_count', $gift_gard_log['page_count']);
            $this->smarty->assign('order_id', $id);

            return $this->smarty->display("merchants_order_log.dwt");
        }
        /* ------------------------------------------------------ */
        //-- 搜索用户
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_users') {
            $keywords = json_str_iconv(trim($_GET['keywords']));

            $res = MerchantsShopInformation::where('user_id', 'LIKE', '%' . mysql_like_quote($keywords) . '%');
            $res = $res->with(['getUsers' => function ($query) use ($keywords) {
                $query->select('user_id', 'user_name')->where('user_name', 'LIKE', '%' . mysql_like_quote($keywords) . '%');
            }]);
            $row = $this->baseRepository->getToArrayGet($res);
            foreach ($row as $key => $value) {
                $value['user_name'] = '';
                if (isset($value['get_users']) && !empty($value['get_users'])) {
                    $value['user_name'] = $value['get_users']['user_name'];
                }
                $row[$key] = $value;
            }

            return make_json_result($row);
        }
        /* ------------------------------------------------------ */
        //--商家订单列表  操作日志 ajax by kong
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'Ajax_handle_log') {
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $gift_gard_log = $this->commonManageService->getGiftGardLog($id, 'toggle_on_settlement');

            $this->smarty->assign('gift_gard_log', $gift_gard_log['pzd_list']);
            $this->smarty->assign('filter', $gift_gard_log['filter']);
            $this->smarty->assign('record_count', $gift_gard_log['record_count']);
            $this->smarty->assign('page_count', $gift_gard_log['page_count']);

            return make_json_result($this->smarty->fetch('merchants_order_log.dwt'), '', ['filter' => $gift_gard_log['filter'], 'page_count' => $gift_gard_log['page_count']]);
        }
        /* ------------------------------------------------------ */
        //--店铺佣金冻结
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'commission_bill') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'commission_bill']);

            $user_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $this->smarty->assign('user_id', $user_id);

            if (empty($user_id) && !isset($_REQUEST['bill_apply'])) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->commissionService->commissionBillList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            $bill_apply = isset($_REQUEST['bill_apply']) && !empty($_REQUEST['bill_apply']) ? intval($_REQUEST['bill_apply']) : 0;
            $this->smarty->assign('bill_apply', $bill_apply);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_commission_bill.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'commission_bill_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->commissionService->commissionBillList();

            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_commission_bill.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除账单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_remove') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['bill_id']);
            $seller_id = intval($_REQUEST['seller_id']);

            $res = SellerBillOrder::select('order_id')->where('bill_id', $id);
            $order_list = $this->baseRepository->getToArrayGet($res);
            $order_list = $this->baseRepository->getFlatten($order_list);

            SellerCommissionBill::where('id', $id)->delete();

            $data = [
                'bill_id' => 0,
                'chargeoff_status' => 0
            ];
            SellerBillOrder::where('bill_id', $id)->where('chargeoff_status', '<', 2)->update($data);

            if ($order_list) {
                $data = ['chargeoff_status' => 0];
                OrderInfo::whereIn('order_id', $order_list)
                    ->where('chargeoff_status', '<', 2)
                    ->update($data);;

                OrderReturn::whereIn('order_id', $order_list)
                    ->where('chargeoff_status', '<', 2)
                    ->update($data);;
            }

            SellerNegativeBill::where('commission_bill_id', $id)->update([
                'commission_bill_id' => 0,
                'commission_bill_sn' => ''
            ]);

            /* 记日志 */
            admin_log($GLOBALS['_LANG']['delete_bill'], 'remove', 'seller_commission_bill');

            /* 清除缓存 */
            clear_cache_files();

            $url = 'merchants_commission.php?act=commission_bill_query&id=' . $seller_id . '&' . str_replace('act=bill_remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /* ------------------------------------------------------ */
        //--店铺佣金账单明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_detail') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'commission_bill']);

            $user_id = isset($_REQUEST['seller_id']) && empty($_REQUEST['seller_id']) ? 0 : intval($_REQUEST['seller_id']);
            $this->smarty->assign('user_id', $user_id);

            if (empty($user_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill_detail']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->commissionService->billDetailList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            $bill = $this->commissionService->getBillDetail($result['filter']['bill_id']);
            $this->smarty->assign('bill', $bill);

            $this->smarty->assign('action_link', ['href' => 'merchants_commission.php?act=commission_bill&id=' . $bill['seller_id'], 'text' => $GLOBALS['_LANG']['commission_bill']]);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_detail.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_detail_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->commissionService->billDetailList();

            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_bill_detail.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //--店铺佣金账单明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_goods') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'commission_bill']);

            $user_id = !isset($_REQUEST['seller_id']) && empty($_REQUEST['seller_id']) ? 0 : intval($_REQUEST['seller_id']);
            $this->smarty->assign('user_id', $user_id);

            if (empty($user_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill_detail']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->commissionService->billGoodsList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('goods_list', $result['goods_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_goods.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_goods_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->commissionService->billGoodsList();

            $this->smarty->assign('goods_list', $result['goods_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_bill_goods.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //--账单未确认收货订单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_notake_order') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'commission_bill']);

            $user_id = !isset($_REQUEST['seller_id']) && empty($_REQUEST['seller_id']) ? 0 : intval($_REQUEST['seller_id']);
            $this->smarty->assign('user_id', $user_id);

            if (empty($user_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill_detail']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = bill_notake_order_list();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            $bill = $this->commissionService->getBillDetail($result['filter']['bill_id']);
            $this->smarty->assign('bill', $bill);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_notake_order.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_notake_order_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = bill_notake_order_list();

            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_bill_notake_order.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //--申请账单结算
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'apply_for') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'commission_bill']);

            $type = isset($_REQUEST['type']) && !empty($_REQUEST['type']) ? addslashes(trim($_REQUEST['type'])) : '';
            $this->smarty->assign('type', $type); // 冻结资金

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill']); // 当前导航

            $bill_id = isset($_REQUEST['bill_id']) && !empty($_REQUEST['bill_id']) ? intval($_REQUEST['bill_id']) : 0;
            $this->smarty->assign('form_act', 'bill_update');

            $bill = $this->commissionService->getBillDetail($bill_id);
            $this->smarty->assign('bill', $bill);
            $this->smarty->assign('user_id', $bill['seller_id']);
            $this->smarty->assign('action_link', ['href' => 'merchants_commission.php?act=commission_bill&id=' . $bill['seller_id'], 'text' => $GLOBALS['_LANG']['commission_bill']]);

            if ($bill['chargeoff_status'] == 0 && $type != 'frozen') {
                $link[0] = ['href' => 'merchants_commission.php?act=commission_bill&id=' . $bill['seller_id'], 'text' => $GLOBALS['_LANG']['commission_bill']];

                return sys_msg($sys_msg = $GLOBALS['_LANG']['apply_for_failure'], 1, $link);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_applyfor.dwt');
        }

        /* ------------------------------------------------------ */
        //--申请账单更新
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_update') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $bill_id = isset($_REQUEST['bill_id']) && !empty($_REQUEST['bill_id']) ? intval($_REQUEST['bill_id']) : 0;
            $seller_id = isset($_REQUEST['seller_id']) && !empty($_REQUEST['seller_id']) ? intval($_REQUEST['seller_id']) : $adminru['ru_id'];
            $reject_note = isset($_REQUEST['reject_note']) && !empty($_REQUEST['reject_note']) ? addslashes($_REQUEST['reject_note']) : '';
            $check_status = isset($_REQUEST['check_status']) && !empty($_REQUEST['check_status']) ? intval($_REQUEST['check_status']) : 0;

            //冻结账单 start
            $frozen_money = isset($_REQUEST['frozen_money']) && !empty($_REQUEST['frozen_money']) ? floatval($_REQUEST['frozen_money']) : 0;
            $frozen_data = isset($_REQUEST['frozen_data']) && !empty($_REQUEST['frozen_data']) ? intval($_REQUEST['frozen_data']) : 0;
            $add_sub_frozen_money = isset($_REQUEST['add_sub_frozen_money']) ? addslashes(trim($_REQUEST['add_sub_frozen_money'])) : 1;
            $add_sub_frozen_data = isset($_REQUEST['add_sub_frozen_data']) ? addslashes(trim($_REQUEST['add_sub_frozen_data'])) : 1;
            $type = isset($_REQUEST['type']) && !empty($_REQUEST['type']) ? addslashes(trim($_REQUEST['type'])) : '';
            //冻结账单 end

            if (empty($seller_id) || empty($bill_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            /* 结账时间 */
            $settleaccounts_time = 0;
            if ($check_status == 1) {
                $settleaccounts_time = $this->timeRepository->getGmTime();
                $chargeoff_status = 2;
            } else {
                $chargeoff_status = 3;
            }

            $bill = $this->commissionService->getBillDetail($bill_id);

            /* 扣除已单独结算的账单订单佣金 start */
            $bill['should_amount'] = $bill['should_amount'] - $bill['settle_accounts'];

            if ($bill['should_amount'] < 0 || $bill['should_amount'] == 0) {
                $bill['should_amount'] = 0;
            }
            /* 扣除已单独结算的账单订单佣金 end */

            $gmtime = $this->timeRepository->getGmTime();
            if ($type == 'bill_frozen' || $type == 'bill_unfreeze') {
                $frozen = [];//初始化

                //冻结账单金额
                if ($type == 'bill_frozen') {
                    if ($bill['chargeoff_status'] != 2) {
                        $frozen_money = $frozen_money * $add_sub_frozen_money;
                        $frozen_data = $frozen_data * $add_sub_frozen_data;

                        $frozen['should_amount'] = $bill['should_amount'] - $frozen_money;
                        $frozen['frozen_money'] = $bill['frozen_money'] + $frozen_money;
                        $frozen['frozen_data'] = $bill['frozen_data'] + $frozen_data;
                        $frozen['frozen_time'] = $this->timeRepository->getGmTime();

                        if ($frozen['frozen_data'] < 0) {
                            $frozen['frozen_data'] = 0;
                        }
                        $sys_msg = $GLOBALS['_LANG']['update_bill_success'];
                    } else {
                        $sys_msg = $GLOBALS['_LANG']['update_bill_failure'];
                    }
                } //解冻账单金额
                else {
                    if ($bill['chargeoff_status'] == 2) {
                        if ($frozen_money >= $bill['frozen_money']) {
                            $frozen_money = $bill['frozen_money'];
                            $frozen['frozen_data'] = 0;
                        }

                        $frozen['should_amount'] = $bill['should_amount'] + $frozen_money;
                        $frozen['frozen_money'] = $bill['frozen_money'] - $frozen_money;

                        SellerShopinfo::where('ru_id', $bill['seller_id'])->increment('seller_money', $frozen_money);

                        $change_desc = sprintf($GLOBALS['_LANG']['seller_bill_unfreeze'], session('admin_name'));
                        $user_account_log = [
                            'user_id' => $bill['seller_id'],
                            'user_money' => $frozen_money,
                            'change_time' => $gmtime,
                            'change_desc' => $change_desc,
                            'change_type' => 2
                        ];
                        MerchantsAccountLog::insert($user_account_log);

                        $sys_msg = $GLOBALS['_LANG']['update_bill_success'];
                    } else {
                        $sys_msg = $GLOBALS['_LANG']['update_bill_failure'];
                    }
                }

                SellerCommissionBill::where('id', $bill_id)->update($frozen);
            } else {

                //结算账单
                SellerCommissionBill::where('id', $bill['id'])->increment('actual_amount', $bill['should_amount']);

                if ($check_status == 1 && $bill['bill_apply'] == 1 && $bill['chargeoff_status'] <= 1) {
                    $other = [
                        'chargeoff_status' => $chargeoff_status,
                        'check_status' => $check_status,
                        'check_time' => $this->timeRepository->getGmTime(),
                        'settleaccounts_time' => $settleaccounts_time
                    ];

                    SellerCommissionBill::where('id', $bill_id)->update($other);

                    $sys_msg = $GLOBALS['_LANG']['update_bill_success'];

                    /* 佣金结算 start */
                    $apply_sn = sprintf($GLOBALS['_LANG']['seller_bill_account'], $bill['bill_sn']);
                    $other['apply_sn'] = $apply_sn;
                    $other['admin_id'] = session('admin_id');
                    $other['ru_id'] = $bill['seller_id'];
                    $other['order_id'] = 0;
                    $other['amount'] = $bill['should_amount']; //结算金额
                    $other['add_time'] = $gmtime; //结算时间
                    $other['log_type'] = 2;
                    $other['is_paid'] = 1;
                    $other['percent_value'] = $bill['proportion'];

                    $accountLogOther = $this->baseRepository->getArrayfilterTable($other, 'seller_account_log');

                    SellerAccountLog::insert($accountLogOther);

                    SellerShopinfo::where('ru_id', $bill['seller_id'])->increment('seller_money', $bill['should_amount']);

                    $change_desc = sprintf($GLOBALS['_LANG']['seller_bill_settlement'], session('admin_name') . "【" . $bill['bill_sn'] . "】");
                    $user_account_log = [
                        'user_id' => $bill['seller_id'],
                        'user_money' => $bill['should_amount'],
                        'change_time' => $gmtime,
                        'change_desc' => $change_desc,
                        'change_type' => 2
                    ];
                    MerchantsAccountLog::insert($user_account_log);
                    /* 佣金结算 end */

                    /* 更新负账单 start */
                    $negativeOther = [
                        'chargeoff_status' => 1
                    ];
                    SellerNegativeBill::where('seller_id', $bill['seller_id'])
                        ->where('commission_bill_id', $bill_id)
                        ->update($negativeOther);

                    $negative_id = SellerNegativeBill::select('id')
                        ->where('seller_id', $bill['seller_id'])
                        ->where('commission_bill_id', $bill_id);
                    $negative_id = $this->baseRepository->getToArrayGet($negative_id);
                    $negative_id = $this->baseRepository->getKeyPluck($negative_id, 'id');

                    if ($negative_id) {
                        $negativeOrderOther = [
                            'settle_accounts' => 1
                        ];
                        SellerNegativeOrder::whereIn('negative_id', $negative_id)->update($negativeOrderOther);
                    }
                    /* 更新负账单 end */

                    /* 更新订单已结算状态 start */
                    $order_other['chargeoff_status'] = 2;
                    SellerBillOrder::where('bill_id', $bill_id)->update($order_other);

                    $order = SellerBillOrder::where('bill_id', $bill_id);
                    $order = $this->baseRepository->getToArrayGet($order);
                    $order_id = $this->baseRepository->getKeyPluck($order, 'order_id');

                    $order_other['is_settlement'] = 1;
                    OrderInfo::whereIn('order_id', $order_id)->update($order_other);

                    OrderSettlementLog::whereIn('order_id', $order_id)
                        ->where('is_settlement', 0)
                        ->update([
                            'is_settlement' => 1,
                            'type' => 2,
                            'update_time' => $gmtime
                        ]);
                    /* 更新订单已结算状态 end */
                } else {
                    if ($check_status > 0) {
                        if ($bill['bill_apply'] == 1 && $bill['chargeoff_status'] <= 1) {
                            if ($check_status == 2) {
                                $other = [
                                    'check_status' => $check_status,
                                    'reject_note' => $reject_note,
                                ];

                                SellerCommissionBill::where('id', $bill_id)->update($other);
                            }

                            $sys_msg = $GLOBALS['_LANG']['bill_over'];
                        } else {
                            $sys_msg = $GLOBALS['_LANG']['update_bill_failure'];
                        }
                    } else {
                        $sys_msg = '';
                    }
                }
            }

            $link[0] = ['href' => 'merchants_commission.php?act=commission_bill&id=' . $seller_id, 'text' => $GLOBALS['_LANG']['commission_bill']];
            return sys_msg($sys_msg, 0, $link);
        }

        /* ------------------------------------------------------ */
        //--店铺负账单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_bill') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', array('action' => '17_merchants', 'current' => 'negative_bill'));

            $user_id = isset($_REQUEST['id']) && empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $this->smarty->assign('user_id', $user_id);

            if (empty($user_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return ecs_header("Location: $Loaction\n");
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['negative_bill_list']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->merchantsCommissionManageService->negativeBillList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            /* 显示模板 */
            return $this->smarty->display('merchants_negative_bill.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_query') {
            check_authz_json('merchants_commission');

            /* 查询 */
            $result = $this->merchantsCommissionManageService->negativeBillList();

            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('merchants_negative_bill.dwt'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
        }


        /* ------------------------------------------------------ */
        //--店铺负账单订单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_order') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $this->smarty->assign('menu_select', array('action' => '17_merchants', 'current' => 'negative_bill'));

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['negative_order_list']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->merchantsCommissionManageService->negativeOrderList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            $this->smarty->assign('user_id', $result['filter']['seller_id']);

            /* 显示模板 */
            return $this->smarty->display('negative_order_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_order_query') {
            check_authz_json('merchants_commission');

            /* 查询 */
            $result = $this->merchantsCommissionManageService->negativeOrderList();

            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('negative_order_list.dwt'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
        }
    }


}
