<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Suppliers;
use App\Models\Wholesale;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleOrderReturn;
use App\Models\WholesaleOrderReturnExtend;
use App\Models\WholesalePurchase;
use App\Models\WholesalePurchaseGoods;
use App\Models\WholesaleReturnGoods;
use App\Models\WholesaleReturnImages;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\AreaService;
use App\Services\Common\CommonService;
use App\Services\Flow\FlowUserService;
use App\Services\Goods\GoodsCommonService;
use App\Services\User\UserCommonService;
use App\Services\Wholesale\GoodsService;
use App\Services\Wholesale\OrderManageService;
use App\Services\Wholesale\OrderService;
use App\Services\Wholesale\PurchaseService;

class UserWholesaleController extends InitController
{
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;
    protected $commonRepository;
    protected $commonService;
    protected $dscRepository;
    protected $purchaseService;
    protected $orderService;
    protected $orderManageService;
    protected $goodsCommonService;
    protected $flowUserService;
    protected $areaService;
    protected $baseRepository;
    protected $goodsService;

    public function __construct(
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CommonRepository $commonRepository,
        CommonService $commonService,
        DscRepository $dscRepository,
        PurchaseService $purchaseService,
        OrderService $orderService,
        OrderManageService $orderManageService,
        GoodsCommonService $goodsCommonService,
        FlowUserService $flowUserService,
        AreaService $areaService,
        BaseRepository $baseRepository,
        GoodsService $goodsService
    )
    {
        $this->userCommonService = $userCommonService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->commonRepository = $commonRepository;
        $this->commonService = $commonService;
        $this->dscRepository = $dscRepository;
        $this->purchaseService = $purchaseService;
        $this->orderService = $orderService;
        $this->orderManageService = $orderManageService;
        $this->goodsCommonService = $goodsCommonService;
        $this->flowUserService = $flowUserService;
        $this->areaService = $areaService;
        $this->baseRepository = $baseRepository;
        $this->goodsService = $goodsService;
    }

    public function index()
    {
        $this->dscRepository->helpersLang(['user']);

        $user_id = session('user_id', 0);
        $action = addslashes(trim(request()->input('act', 'default')));
        $action = $action ? $action : 'default';

        $not_login_arr = $this->userCommonService->notLoginArr();

        $ui_arr = $this->userCommonService->uiArr('wholesale');

        /* 未登录处理 */
        $requireUser = $this->userCommonService->requireLogin(session('user_id'), $action, $not_login_arr, $ui_arr);
        $action = $requireUser['action'];
        $require_login = $requireUser['require_login'];

        if ($require_login == 1) {
            //未登录提交数据。非正常途径提交数据！
            return dsc_header('location:' . $this->dscRepository->dscUrl('user.php'));
        }

        /* 区分登录注册底部样式 */
        $footer = $this->userCommonService->userFooter();
        if (in_array($action, $footer)) {
            $this->smarty->assign('footer', 1);
        }

        $is_apply = $this->userCommonService->merchantsIsApply($user_id);
        $this->smarty->assign('is_apply', $is_apply);

        $user_default_info = $this->userCommonService->getUserDefault($user_id);
        $this->smarty->assign('user_default_info', $user_default_info);

        /* 如果是显示页面，对页面进行相应赋值 */
        if (in_array($action, $ui_arr) || $user_id == 0) {
            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['user_core']);
            $this->smarty->assign('page_title', $position['title']); // 页面标题
            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版
            $this->smarty->assign('ur_here', $position['ur_here']);

            $this->smarty->assign('car_off', $this->config['anonymous_buy']);

            /* 是否显示积分兑换 */
            if (!empty($this->config['points_rule']) && unserialize($this->config['points_rule'])) {
                $this->smarty->assign('show_transform_points', 1);
            }

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
            $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录
            $this->smarty->assign('action', $action);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $info = $user_default_info;

            if ($user_id) {
                //验证邮箱
                if (isset($info['is_validated']) && !$info['is_validated'] && $this->config['user_login_register'] == 1) {
                    $Location = url('/') . '/' . 'user.php?act=user_email_verify';
                    return dsc_header('location:' . $Location);
                }
            }

            $count = AdminUser::where('ru_id', session('user_id'))->count();
            if ($count) {
                $is_merchants = 1;
            } else {
                $is_merchants = 0;
            }

            $this->smarty->assign('is_merchants', $is_merchants);
            $this->smarty->assign('shop_reg_closed', $this->config['shop_reg_closed']);

            $this->smarty->assign('filename', 'user');
        } else {
            return dsc_header('location:' . $this->dscRepository->dscUrl('user.php'));
        }

        $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();
        $wholesaleUse = $this->commonService->judgeWholesaleUse(session('user_id'), 1);
        $wholesale_use = $supplierEnabled && $wholesaleUse ? 1 : 0;

        $this->smarty->assign('wholesale_use', $wholesale_use);

        /* ------------------------------------------------------ */
        //-- 批发中心-我的求购单
        /* ------------------------------------------------------ */
        if ($action == 'wholesale_purchase') {
            load_helper('transaction');

            $this->dscRepository->helpersLang('wholesale_purchase');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $user_id = session('user_id');
            $this->smarty->assign("action", $action);
            //求购单列表

            $keyword = addslashes(trim(request()->input('keyword', '')));
            $start_date = addslashes(trim(request()->input('start_date', '')));
            $end_date = addslashes(trim(request()->input('end_date', '')));

            //审核状态:-1全部;0未审核;1审核通过;2审核未通过
            $review_status = (int)request()->input('review_status', -1);

            $filter_array = [];
            $query_array = [];
            $query_array['act'] = 'wholesale_purchase';
            $filter_array['user_id'] = $user_id;
            if ($review_status != -1) {
                $query_array['review_status'] = $review_status;
                $filter_array['review_status'] = $review_status;
            }
            if (!empty($keyword)) {
                $query_array['keyword'] = $keyword;
                $filter_array['keyword'] = $keyword;
            }
            if (!empty($start_date)) {
                $query_array['start_date'] = $start_date;
                $filter_array['start_date'] = local_strtotime($start_date);
            }
            if (!empty($end_date)) {
                $query_array['end_date'] = $end_date;
                $filter_array['end_date'] = local_strtotime($end_date);
            }
            $size = 10;
            $page = (int)request()->input('page', 1);
            $purchase_list = $this->purchaseService->getPurchaseList($filter_array, $size, $page);
            $pager = get_pager('user.php', $query_array, $purchase_list['record_count'], $page, $size);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('purchase_list', $purchase_list['purchase_list']);
            //求购单不同审核状态数量统计
            $review_status_array = [];
            $review_status_array[-1] = get_table_date('wholesale_purchase', "user_id='$user_id'", ['COUNT(*)'], 2);
            $review_status_array[0] = get_table_date('wholesale_purchase', "user_id='$user_id' AND review_status=0", ['COUNT(*)'], 2);
            $review_status_array[1] = get_table_date('wholesale_purchase', "user_id='$user_id' AND review_status=1", ['COUNT(*)'], 2);
            $review_status_array[2] = get_table_date('wholesale_purchase', "user_id='$user_id' AND review_status=2", ['COUNT(*)'], 2);
            $this->smarty->assign('review_status_array', $review_status_array);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-我的求购单
        /* ------------------------------------------------------ */
        elseif ($action == 'purchase_info') {

            load_helper('transaction');

            $this->dscRepository->helpersLang('wholesale_purchase');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign("action", $action);

            //求购单信息
            $purchase_id = (int)request()->input('purchase_id', 0);
            $purchase_info = $this->purchaseService->getPurchaseInfo($purchase_id);
            $this->smarty->assign('purchase_info', $purchase_info);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-编辑求购单
        /* ------------------------------------------------------ */
        elseif ($action == 'purchase_edit') {
            $data = [];
            $data['purchase_id'] = (int)request()->input('purchase_id', 0);
            $data['supplier_company_name'] = addslashes(trim(request()->input('supplier_company_name', '')));
            $data['supplier_contact_phone'] = addslashes(trim(request()->input('supplier_contact_phone', '')));

            $data['status'] = 1;

            WholesalePurchase::where('purchase_id', $data['purchase_id'])->update($data);

            return show_message($GLOBALS['_LANG']['edit_wantbuy_info_success'], $GLOBALS['_LANG']['back_list'], 'user_wholesale.php?act=wholesale_purchase', 'info');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-删除求购单
        /* ------------------------------------------------------ */
        elseif ($action == 'purchase_delete') {
            $purchase_id = (int)request()->input('purchase_id', 0);
            //删除求购单信息
            WholesalePurchase::where('purchase_id', $purchase_id)->delete();

            //删除商品图片
            $goods_list = WholesalePurchaseGoods::where('purchase_id', $purchase_id)->get();
            $goods_list = $goods_list ? $goods_list->toArray() : [];

            if ($goods_list) {
                foreach ($goods_list as $key => $val) {
                    if (!empty($val['goods_img'])) {
                        $goods_img = unserialize($val['goods_img']);
                        foreach ($goods_img as $k => $v) {
                            @unlink(storage_public($v));
                        }
                    }
                }
            }

            //删除商品信息
            WholesalePurchaseGoods::where('purchase_id', $purchase_id)->delete();

            return show_message('删除求购单信息成功', '返回列表', 'user_wholesale.php?act=wholesale_purchase', 'info');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-我的采购单
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_buy') {
            load_helper('transaction');

            $this->dscRepository->helpersLang('wholesale');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $page = (int)request()->input('page', 1);

            $record_count = WholesaleOrderInfo::mainOrderCount()
                ->where('user_id', $user_id)
                ->count();

            $size = 10;
            $where = [
                'user_id' => $user_id,
                'page' => $page,
                'size' => $size
            ];

            $wholesale_orders = $this->orderService->getWholesaleOrders($record_count, $where);

            $this->smarty->assign('orders', $wholesale_orders);
            $this->smarty->assign("action", $action);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-我的采购单-确认完成
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_affirm_received') {
            load_helper('transaction');
            $order_id = (int)request()->input('order_id', 0);

            $received = $this->orderService->wholesaleAffirmReceived($order_id, $user_id);

            if ($received) {
                return dsc_header("Location: user_wholesale.php?act=wholesale_buy\n");
            } else {
                return $this->err->show($GLOBALS['_LANG']['order_list_lnk'], 'user_wholesale.php?act=wholesale_buy');
            }
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-我的采购单-删除采购单
        /* ------------------------------------------------------ */
        elseif ($action == 'delete_wholesale_order') {

            $order = strip_tags(urldecode(request()->input('order', '')));
            $order = json_str_iconv($order);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'order_id' => ''];

            $order = dsc_decode($order);

            $order_id = $order->order_id;
            $result['order_id'] = $order_id;

            $type = 1;
            $parent = [
                'is_delete' => $type
            ];

            WholesaleOrderInfo::where('order_id', $order_id)->update($parent);

            load_helper('transaction');

            $page = (int)request()->input('page', 1);

            $record_count = WholesaleOrderInfo::where('user_id', $user_id)->where('is_delete', 0)->count();

            $size = 10;
            $where = [
                'user_id' => $user_id,
                'page' => $page,
                'size' => $size
            ];

            $action = 'wholesale_buy';
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            $orders = $this->orderService->getWholesaleOrders($record_count, $where);

            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('orders', $orders);

            $insert_arr = [
                'act' => $action,
                'filename' => 'user'
            ];

            $this->smarty->assign('no_records', insert_get_page_no_records($insert_arr));

            $result['content'] = $this->smarty->fetch("library/user_wholesale_order_list.lbi");
            $result['page_content'] = $this->smarty->fetch("library/pages.lbi");

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 查询批发订单
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_order_to_query') {

            $this->dscRepository->helpersLang('wholesale');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $order = strip_tags(urldecode(request()->input('order', '')));
            $order = json_str_iconv($order);
            $result = ['error' => 0, 'message' => '', 'content' => '', 'order_id' => ''];

            $order = dsc_decode($order);

            $order->keyword = addslashes(trim($order->keyword));

            if (isset($order->order_id) && $order->order_id > 0) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $show_type = 0;
            load_helper('transaction');

            $page = (int)request()->input('page', 1);

            $record_count = WholesaleOrderInfo::mainOrderCount()
                ->where('user_id', $user_id)
                ->where('is_delete', $show_type)
                ->where('user_id', $user_id);

            if ($order->idTxt == 'signNum') {
                $record_count = $record_count->whereHas('getWholesaleOrderGoods', function ($query) use ($user_id) {
                    $query->goodsCommentCount($user_id);
                });
            }

            if ($order->keyword) {
                $record_count = $record_count->searchKeyword($order);
                $val = $order->keyword;

                $record_count = $record_count->where(function ($query) use ($val) {
                    $query->whereRaw("order_sn LIKE '%$val%'")
                        ->orWhere(function ($query) use ($val) {
                            $query->whereHas('getWholesaleOrderGoods', function ($query) use ($val) {
                                $query->where('goods_name', 'like', '%' . $val . '%')
                                    ->orWhere('goods_sn', 'like', '%' . $val . '%');
                            });
                        });
                });
            }

            $record_count = $record_count->count();

            $size = 10;
            $where = [
                'user_id' => $user_id,
                'show_type' => $show_type,
                'page' => $page,
                'size' => $size
            ];

            $orders = $this->orderService->getWholesaleOrders($record_count, $where, $order);

            $date_keyword = '';
            $status_keyword = '';
            if (isset($order->idTxt)) {
                if ($order->idTxt == 'submitDate') {
                    $date_keyword = $order->keyword;
                    $status_keyword = $order->status_keyword;
                } elseif ($order->idTxt == 'wholesale_status_list') {
                    $date_keyword = $order->date_keyword;
                    $status_keyword = $order->keyword;
                }
            }

            $result['date_keyword'] = $date_keyword;
            $result['status_keyword'] = $status_keyword;

            $this->smarty->assign('orders', $orders);
            $this->smarty->assign('wholesale_status_list', $GLOBALS['_LANG']['cs']);   // 订单状态
            $this->smarty->assign('date_keyword', $date_keyword);
            $this->smarty->assign('status_keyword', $status_keyword);

            $insert_arr = [
                'act' => $order->action,
                'filename' => 'user'
            ];

            $this->smarty->assign('no_records', insert_get_page_no_records($insert_arr));
            $this->smarty->assign('open_delivery_time', $this->config['open_delivery_time']);
            $this->smarty->assign('action', $order->action);

            $result['content'] = $this->smarty->fetch("library/user_wholesale_order_list.lbi");

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-批量申请退换货
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_batch_applied') {
            load_helper(['transaction', 'order', 'suppliers']);

            if (request()->exists('checkboxes')) {
                $order_id = (int)request()->input('order_id', 0);
                $order = $this->orderManageService->wholesaleOrderInfo($order_id);
                $this->err->or = 0;
                $cause_arr = '';

                $goods_info_arr = [];
                $suppliers_name = '';
                $rec_ids = [];
                $checkboxes = request()->input('checkboxes', []);
                if (!empty($checkboxes)) {
                    foreach ($checkboxes as $key => $val) {
                        $goods = $this->orderService->wholesaleRecGoods($val);
                        $goods_info = get_table_date('wholesale', "goods_id='{$goods['goods_id']}'", array('goods_cause'));
                        if (empty($cause_arr)) {
                            $cause_arr = explode(",", $goods_info['goods_cause']);
                        }
                        $cause_arr_next = explode(",", $goods_info['goods_cause']);

                        if (!$goods_info['goods_cause']) {
                            $this->err->or += 1;
                        } else {
                            if ($cause_arr) {
                                $cause_arr = array_intersect($cause_arr, $cause_arr_next); //比较数组返回交集
                            }
                            $goods_info_arr[$key] = $goods; //订单商品
                            $suppliers_name = $goods['suppliers_name'];
                            $rec_ids[] = $goods['rec_id'];
                        }
                    }
                }

                if ($this->err->or) {
                    return show_message($GLOBALS['_LANG']['nonsupport_return_goods'], '', '', 'info', true);
                } else {
                    $cause_str = implode(",", $cause_arr);
                    $goods_cause = $this->goodsCommonService->getGoodsCause($cause_str);
                }
            } else {
                return show_message($GLOBALS['_LANG']['please_select_goods'], '', '', 'info', true);
            }

            $parent_cause = get_parent_cause();

            $consignee = $this->flowUserService->getConsignee(session('user_id'));
            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('show_goods_thumb', $this->config['show_goods_in_cart']);      /* 增加是否在购物车里显示商品图 */
            $this->smarty->assign('show_goods_attribute', $this->config['show_attr_in_cart']);   /* 增加是否在购物车里显示商品属性 */
            $this->smarty->assign("goods", $goods_info_arr);
            $this->smarty->assign("goods_return", $goods_info_arr);
            $this->smarty->assign("suppliers_name", $suppliers_name);
            $this->smarty->assign("rec_ids", implode("-", $rec_ids));
            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('cause_list', $parent_cause);
            $this->smarty->assign('order_sn', $order['order_sn']);
            $this->smarty->assign('order', $order);

            $country_list = $this->areaService->getRegionsLog(0, 0);
            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            /* 退换货标志列表 */
            $this->smarty->assign('goods_cause', $goods_cause);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);
            $this->smarty->assign('sn', $sn);
            $this->smarty->assign('sessid', SESS_ID);
            $this->smarty->assign('return_pictures', $this->config['return_pictures']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-批量处理会员退货申请
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_submit_batch_return') {
            load_helper(['transaction', 'order']);

            if (request()->exists('return_rec_id')) {
                //表单传值
                $return_remark = addslashes(trim(request()->input('return_remark', '')));
                $return_brief = addslashes(trim(request()->input('return_brief', '')));
                $chargeoff_status = (int)request()->input('chargeoff_status', 0);
                $return_rec_id = request()->input('return_rec_id', []);
                foreach ($return_rec_id as $rec_id) {
                    //判断是否重复提交申请退换货
                    if ($rec_id > 0) {
                        $num = WholesaleOrderReturn::where('rec_id', $rec_id)->count();

                        if ($num > 0) {
                            return show_message($GLOBALS['_LANG']['Repeated_submission'], '', '', 'info', true);
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['Return_abnormal'], '', '', 'info', true);
                    }

                    $order_goods = WholesaleOrderGoods::where('rec_id', $rec_id)
                        ->with([
                            'getWholesale',
                            'getWholesaleOrderInfo' => function ($query) {
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
                    $order_goods = $this->baseRepository->getToArrayFirst($order_goods);

                    if ($order_goods) {
                        /* 取得区域名 */
                        $province = $order_goods['get_wholesale_order_nfo']['get_region_province']['region_name'] ?? '';
                        $city = $order_goods['get_wholesale_order_nfo']['get_region_city']['region_name'] ?? '';
                        $district = $order_goods['get_wholesale_order_nfo']['get_region_district']['region_name'] ?? '';
                        $street = $order_goods['get_wholesale_order_nfo']['get_region_street']['region_name'] ?? '';
                        $order_goods['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;
                    }

                    $wholesale = $order_goods['get_wholesale'] ?? [];

                    $order_goods['goods_sn'] = $wholesale['goods_sn'] ?? '';
                    $order_goods['brand_id'] = $wholesale['brand_id'] ?? 0;

                    $return_number = $goods_number = $order_goods['goods_number']; //批量退回所有商品

                    //退换货类型
                    $return_type = (int)request()->input('return_type', 0);
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

                    $order_return = array(
                        'rec_id' => $rec_id,
                        'goods_id' => $order_goods['goods_id'],
                        'order_id' => $order_goods['order_id'],
                        'order_sn' => $order_goods['goods_sn'],
                        'chargeoff_status' => $chargeoff_status,
                        'return_type' => $return_type, //唯一标识
                        'maintain' => $maintain, //维修标识
                        'back' => $back, //退货标识
                        'exchange' => $exchange, //换货标识
                        'user_id' => session('user_id'),
                        'return_brief' => $return_brief,
                        'remark' => $return_remark,
                        'credentials' => (int)request()->input('credentials', 0),
                        'country' => (int)request()->input('country', 0),
                        'province' => (int)request()->input('province', 0),
                        'city' => (int)request()->input('city', 0),
                        'district' => (int)request()->input('district', 0),
                        'street' => (int)request()->input('street', 0),
                        'apply_time' => gmtime(),
                        'actual_return' => '',
                        'address' => addslashes(trim(request()->input('return_address', ''))),
                        'zipcode' => request()->input('code'),
                        'addressee' => addslashes(trim(request()->input('addressee', ''))),
                        'phone' => addslashes(trim(request()->input('mobile', ''))),
                        'return_status' => $return_status
                    );

                    if (in_array($return_type, array(1, 3))) {
                        $return_info = get_wholesale_return_refound($order_return['order_id'], $order_return['rec_id'], $return_number);
                        $order_return['should_return'] = $return_info['return_price'];
                        $order_return['return_shipping_fee'] = $return_info['return_shipping_fee'];
                    } else {
                        $order_return['should_return'] = 0;
                        $order_return['return_shipping_fee'] = 0;
                    }

                    /* 插入订单表 */
                    $order_return['return_sn'] = get_order_sn(); //获取新订单号

                    $ret_id = 0;
                    $return_count = WholesaleOrderReturn::where('return_sn', $order_return['return_sn'])->count();
                    if ($return_count <= 0) {
                        /* 获取表字段 */
                        $other = $this->baseRepository->getArrayfilterTable($order_return, 'wholesale_order_return');

                        $ret_id = WholesaleOrderReturn::insertGetId($other);
                    }

                    if ($ret_id) {
                        /* 记录log */
                        $this->orderService->wholesaleReturnAction($ret_id, $GLOBALS['_LANG']['Apply_refund'], '', $order_return['remark'], $GLOBALS['_LANG']['buyer']);

                        $return_goods['rec_id'] = $order_return['rec_id'];
                        $return_goods['ret_id'] = $ret_id;
                        $return_goods['goods_id'] = $order_goods['goods_id'];
                        $return_goods['goods_name'] = $order_goods['goods_name'];

                        $brand_name = Brand::where('brand_id', $order_goods['brand_id'])->value('brand_name');
                        $return_goods['brand_name'] = $brand_name ? $brand_name : '';
                        $return_goods['product_id'] = $order_goods['product_id'];
                        $return_goods['goods_sn'] = $order_goods['goods_sn'];
                        $return_goods['refound'] = $order_goods['goods_price'];

                        //添加到退换货商品表中
                        $return_goods['return_type'] = $return_type; //退换货
                        $return_goods['return_number'] = $return_number; //退换货数量

                        /* 获取表字段 */
                        $goodsOther = $this->baseRepository->getArrayfilterTable($return_goods, 'wholesale_return_goods');

                        WholesaleReturnGoods::insertGetId($goodsOther);

                        $images_count = WholesaleReturnImages::where('rec_id', $rec_id)
                            ->where('user_id', $user_id)
                            ->count();

                        if ($images_count > 0) {
                            $images['rg_id'] = $order_goods['goods_id'];
                            WholesaleReturnImages::where('rec_id', $rec_id)
                                ->where('user_id', $user_id)
                                ->update($images);
                        }
                        //退货数量插入退货表扩展表  by kong
                        $order_return_extend = array(
                            'ret_id' => $ret_id,
                            'return_number' => $return_number
                        );

                        WholesaleOrderReturnExtend::insert($order_return_extend);

                        $address_detail = $order_goods['region'] . ' ' . $order_return['address'];
                        $order_return['address_detail'] = $address_detail;
                        $order_return['apply_time'] = local_date("Y-m-d H:i:s", $order_return['apply_time']);
                    } else {
                        return show_message($GLOBALS['_LANG']['Apply_abnormal'], '', '', 'info', true);
                    }
                }
                return show_message($GLOBALS['_LANG']['Apply_Success_Prompt'], $GLOBALS['_LANG']['See_Returnlist'], 'user_wholesale.php?act=wholesale_return_list', 'info', true, $order_return);
            }
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_return_list') {
            load_helper(['transaction', 'order']);

            $page = (int)request()->input('page', 1);
            $size = 10;

            $record_count = WholesaleOrderReturn::where('user_id', $user_id)->count();
            $pager = get_pager('user.php', array('act' => $action), $record_count, $page, $size);

            $return_list = $this->orderService->wholesaleReturnOrder($size, $pager['start']);

            $this->smarty->assign('orders', $return_list);
            $this->smarty->assign('pager', $pager);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-取消退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_cancel_return') {
            load_helper(['transaction', 'order']);
            $ret_id = (int)request()->input('ret_id', 0);

            $cancel_return = $this->orderService->wholesaleCancelReturn($ret_id, $user_id);
            if ($cancel_return) {
                return dsc_header("Location: user_wholesale.php?act=wholesale_return_list\n");
            } else {
                return $this->err->show($GLOBALS['_LANG']['return_list_lnk'], 'user_wholesale.php?act=wholesale_return_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-换货确认收货
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_return_delivery') {
            load_helper(['transaction', 'order']);

            $order_id = (int)request()->input('order_id', 0);

            if (wholesale_affirm_return_received($order_id, $user_id)) {
                return dsc_header("Location: user_wholesale.php?act=wholesale_return_list\n");
            } else {
                return $this->err->show($GLOBALS['_LANG']['return_list_lnk'], 'user_wholesale.php?act=wholesale_return_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_activation_return_order') {
            $res = array('err_msg' => '', 'result' => '', 'error' => 0);
            $ret_id = (int)request()->input('ret_id', 0);
            $activation_number_type = (intval($GLOBALS['_CFG']['activation_number_type']) > 0) ? intval($GLOBALS['_CFG']['activation_number_type']) : 2;

            $activation_number = WholesaleOrderReturn::where('ret_id', $ret_id)->value('activation_number');
            $activation_number = $activation_number ? $activation_number : 0;

            if ($activation_number_type > $activation_number) {
                WholesaleOrderReturn::where('ret_id', $ret_id)->increment('activation_number', 1, [
                    'return_status' => 0
                ]);
            } else {
                $res['error'] = 1;
                $res['err_msg'] = sprintf($GLOBALS['_LANG']['activation_number_msg'], $activation_number_type);
            }

            return response()->json($res);
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-退货订单详情
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_return_detail') {
            load_helper(['transaction', 'order', 'suppliers']);

            $ret_id = (int)request()->input('ret_id', 0);

            /* 订单详情 */

            $order = $this->orderService->wholesaleReturnOrderInfo($ret_id);

            if ($order === false) {
                return $this->err->show($GLOBALS['_LANG']['back_home_lnk'], './');
            }

            $shippinOrderInfo = WholesaleOrderGoods::where('order_id', $order['order_id']);
            $shippinOrderInfo = $this->baseRepository->getToArrayFirst($shippinOrderInfo);

            $shippinOrderInfo['ru_id'] = Suppliers::where('suppliers_id', $order['suppliers_id'])->value('user_id');
            $shippinOrderInfo['ru_id'] = $shippinOrderInfo['ru_id'] ? $shippinOrderInfo['ru_id'] : 0;

            //快递公司
            $region = array($order['country'], $order['province'], $order['city'], $order['district']);
            $shipping_list = available_shipping_list($region, $shippinOrderInfo);
            foreach ($shipping_list as $key => $val) {
                $shipping_cfg = unserialize_config($val['configure']);
                $shipping_fee = $this->dscRepository->shippingFee($val['shipping_code'], unserialize($val['configure']));

                $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
                $shipping_list[$key]['shipping_fee'] = $shipping_fee;
                $shipping_list[$key]['free_money'] = price_format($shipping_cfg['free_money'], false);
                $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];
            }

            //修正退货单状态
            if ($order['return_type'] == 0) {
                if ($order['return_status1'] == 4) {
                    $order['refound_status1'] = FF_MAINTENANCE;
                } else {
                    $order['refound_status1'] = FF_NOMAINTENANCE;
                }
            } elseif ($order['return_type'] == 1) {
                if ($order['refound_status1'] == 1) {
                    $order['refound_status1'] = FF_REFOUND;
                } else {
                    $order['refound_status1'] = FF_NOREFOUND;
                }
            } elseif ($order['return_type'] == 2) {
                if ($order['return_status1'] == 4) {
                    $order['refound_status1'] = FF_EXCHANGE;
                } else {
                    $order['refound_status1'] = FF_NOEXCHANGE;
                }
            }

            $this->smarty->assign('shipping_list', $shipping_list);

            $this->smarty->assign('goods', $order);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-编辑退换货快递信息
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_edit_express') {
            $ret_id = request()->input('ret_id', '');
            $order_id = request()->input('order_id', '');
            $back_shipping_name = request()->input('express_name', '');
            $back_other_shipping = request()->input('other_express', '');
            $back_invoice_no = request()->input('express_sn', '');

            if ($ret_id) {
                WholesaleOrderReturn::where('ret_id', $ret_id)
                    ->update([
                        'back_shipping_name' => $back_shipping_name,
                        'back_other_shipping' => $back_other_shipping,
                        'back_invoice_no' => $back_invoice_no
                    ]);
            }

            return show_message(lang('user.edit_shipping_success'), lang('user.return_info'), 'user_wholesale.php?act=wholesale_return_detail&order_id=' . $order_id . '&ret_id=' . $ret_id);
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-退换货申请订单-商品列表
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_goods_order') {
            load_helper(['transaction', 'order', 'suppliers']);

            /* 根据订单id或订单号查询订单信息 */
            if (request()->exists('order_id')) {
                $order_id = (int)request()->input('order_id', 0);
            } else {
                /* 如果参数不存在，退出 */
                die('invalid parameter');
            }

            /* 订单信息 */
            $order = $this->orderManageService->wholesaleOrderInfo($order_id);

            $price = [];
            /* 订单商品 */
            $goods_list = wholesale_order_goods($order_id);
            foreach ($goods_list as $key => $value) {
                if ($value['extension_code'] != 'package_buy') {
                    $price[] = $value['subtotal'];
                    $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
                    $goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
                    $goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
                    $goods_list[$key]['is_refound'] = get_is_refound($value['rec_id']);   //判断是否退换货过
                    $goods_list[$key]['goods_attr'] = str_replace(' ', '&nbsp;&nbsp;&nbsp;&nbsp;', $value['goods_attr']);
                    if (!empty($value['goods_id'])) {
                        $goods_info = $this->goodsService->getWholesaleGoodsInfo($value['goods_id']);
                        if ($goods_info) {
                            $goods_list[$key]['goods_cause'] = $this->goodsCommonService->getGoodsCause($goods_info['goods_cause']);
                        } else {
                            $goods_list[$key]['goods_cause'] = [];
                        }
                    }

                } else {
                    unset($goods_list[$key]);
                    $this->smarty->assign('package_buy', true);
                }
            }

            $formated_goods_amount = price_format(array_sum($price), false);
            $this->smarty->assign('formated_goods_amount', $formated_goods_amount);
            /* 取得订单商品及货品 */
            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('goods_list', $goods_list);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-退换货申请列表
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_apply_return') {
            load_helper(['transaction', 'order', 'suppliers']);

            /* 根据订单id或订单号查询订单信息 */
            if (request()->exists('rec_id')) {
                $recr_id = (int)request()->input('rec_id', 0);
            } else {
                /* 如果参数不存在，退出 */
                die('invalid parameter');
            }

            $order_id = (int)request()->input('order_id', 0);

            $order = $this->orderManageService->wholesaleOrderInfo($order_id);

            /* 退货权限 by wu */
            $sql = " SELECT order_id FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " WHERE order_id = '$order_id' AND shipping_status > 0 ";
            $return_allowable = $GLOBALS['db']->getOne($sql, true);
            $this->smarty->assign('return_allowable', $return_allowable);

            /* 订单商品 */
            $goods_info = $this->orderService->wholesaleRecGoods($recr_id);
            $parent_cause = get_parent_cause();

            $consignee = $this->flowUserService->getConsignee(session('user_id'));
            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('show_goods_thumb', $this->config['show_goods_in_cart']);      /* 增加是否在购物车里显示商品图 */
            $this->smarty->assign('show_goods_attribute', $this->config['show_attr_in_cart']);   /* 增加是否在购物车里显示商品属性 */
            $this->smarty->assign("goods", $goods_info);
            $this->smarty->assign("goods_return", $goods_info);

            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('cause_list', $parent_cause);
            $this->smarty->assign('order_sn', $order['order_sn']);
            $this->smarty->assign('order', $order);

            $country_list = $this->areaService->getRegionsLog();
            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            /* 退换货标志列表 */
            $goods_cause = Wholesale::where('goods_id', $goods_info['goods_id'])->value('goods_cause');
            $goods_cause = $goods_cause ? $goods_cause : '';
            $goods_cause = $this->goodsCommonService->getGoodsCause($goods_cause);

            $this->smarty->assign('goods_cause', $goods_cause);

            //图片列表
            $img_list = WholesaleReturnImages::where('user_id', $user_id)
                ->where('rec_id', $recr_id)
                ->orderBy('id', 'desc');
            $img_list = $this->baseRepository->getToArrayGet($img_list);

            $this->smarty->assign('img_list', $img_list);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);
            $this->smarty->assign('sn', $sn);
            $this->smarty->assign('return_pictures', $this->config['return_pictures']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-会员退货申请的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_submit_return') {
            load_helper(['transaction', 'order']);

            //判断是否重复提交申请退换货
            $rec_id = (int)request()->input('rec_id', 0);
            $last_option = request()->input('last_option', request()->input('parent_id'));

            $return_remark = addslashes(trim(request()->input('return_remark', '')));
            $return_brief = addslashes(trim(request()->input('return_brief', '')));
            $chargeoff_status = (int)request()->input('chargeoff_status', 0);

            if ($rec_id > 0) {
                $num = WholesaleOrderReturn::where('rec_id', $rec_id)->count();

                if ($num > 0) {
                    return show_message($GLOBALS['_LANG']['Repeated_submission'], '', '', 'info', true);
                    return show_message($GLOBALS['_LANG']['Repeated_submission'], '', '', 'info', true);
                }
            } else {
                return show_message($GLOBALS['_LANG']['Return_abnormal'], '', '', 'info', true);
            }

            $order_goods = WholesaleOrderGoods::where('rec_id', $rec_id)
                ->with([
                    'getWholesale',
                    'getWholesaleOrderInfo' => function ($query) {
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

            $order_goods = $this->baseRepository->getToArrayFirst($order_goods);

            if ($order_goods) {
                /* 取得区域名 */
                $province = $order_goods['get_wholesale_order_nfo']['get_region_province']['region_name'] ?? '';
                $city = $order_goods['get_wholesale_order_nfo']['get_region_city']['region_name'] ?? '';
                $district = $order_goods['get_wholesale_order_nfo']['get_region_district']['region_name'] ?? '';
                $street = $order_goods['get_wholesale_order_nfo']['get_region_street']['region_name'] ?? '';
                $order_goods['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;
            }

            $wholesale = $order_goods['get_wholesale'] ?? [];

            $order_goods['goods_sn'] = $wholesale['goods_sn'] ?? '';
            $order_goods['brand_id'] = $wholesale['brand_id'] ?? 0;

            //换货数量
            $maintain_number = (int)request()->input('maintain_number', 0);
            //换货数量
            $return_num = (int)request()->input('return_num', 0);
            //退货数量
            $back_number = (int)request()->input('attr_num', 0);
            //仅退款退换所有商品
            $goods_number = (int)request()->input('return_g_number', 0);
            //退换货类型
            $return_type = (int)request()->input('return_type', 0);

            $maintain = 0;
            $return_status = 0;

            if ($return_type == 1) {
                $back = 1;
                $exchange = 0;
                $return_number = $return_num;
            } elseif ($return_type == 2) {
                $back = 0;
                $exchange = 2;
                $return_number = $back_number;
            } elseif ($return_type == 3) {
                $back = 0;
                $exchange = 0;
                $return_number = $goods_number;
                $return_status = -1;
            } else {
                $back = 0;
                $exchange = 0;
                $return_number = $maintain_number;
            }

            //获取属性ID数组
            $attr_val = request()->input('attr_val', []);
            $return_attr_id = !empty($attr_val) ? implode(',', $attr_val) : '';
            $attr_val = get_wholesale_goods_attr_info_new($attr_val, 'pice');

            $order_return = array(
                'rec_id' => $rec_id,
                'goods_id' => $order_goods['goods_id'],
                'order_id' => $order_goods['order_id'],
                'order_sn' => $order_goods['goods_sn'],
                'chargeoff_status' => $chargeoff_status,
                'return_type' => $return_type, //唯一标识
                'maintain' => $maintain, //维修标识
                'back' => $back, //退货标识
                'exchange' => $exchange, //换货标识
                'user_id' => session('user_id'),
                'goods_attr' => $order_goods['goods_attr'] ?? '',   //换出商品属性
                'attr_val' => $attr_val,
                'return_brief' => $return_brief,
                'remark' => $return_remark,
                'credentials' => (int)request()->input('credentials', 0),
                'country' => (int)request()->input('country', 0),
                'province' => (int)request()->input('province', 0),
                'city' => (int)request()->input('city', 0),
                'district' => (int)request()->input('district', 0),
                'street' => (int)request()->input('street', 0),
                'cause_id' => $last_option, //退换货原因
                'apply_time' => gmtime(),
                'actual_return' => '',
                'address' => addslashes(trim(request()->input('return_address', ''))),
                'zipcode' => request()->input('code'),
                'addressee' => addslashes(trim(request()->input('addressee', ''))),
                'phone' => addslashes(trim(request()->input('mobile', ''))),
                'return_status' => $return_status
            );

            if (in_array($return_type, array(1, 3))) {
                $return_info = get_wholesale_return_refound($order_return['order_id'], $order_return['rec_id'], $return_number);
                $order_return['should_return'] = $return_info['return_price'];
                $order_return['return_shipping_fee'] = $return_info['return_shipping_fee'];
            } else {
                $order_return['should_return'] = 0;
                $order_return['return_shipping_fee'] = 0;
            }

            $order_return['return_sn'] = get_order_sn(); //获取新订单号

            $ret_id = 0;
            $return_count = WholesaleOrderReturn::where('return_sn', $order_return['return_sn'])->count();
            if ($return_count <= 0) {
                /* 获取表字段 */
                $other = $this->baseRepository->getArrayfilterTable($order_return, 'wholesale_order_return');

                $ret_id = WholesaleOrderReturn::insertGetId($other);
            }

            if ($ret_id) {

                /* 记录log */
                return_whole_action($ret_id, $GLOBALS['_LANG']['Apply_refund'], '', $order_return['remark'], $GLOBALS['_LANG']['buyer']);

                $brand_name = '';
                if (isset($order_goods['brand_id']) && $order_goods['brand_id']) {
                    $brand_name = Brand::where('brand_id', $order_goods['brand_id'])->value('brand_name');
                }

                $return_goods['rec_id'] = $order_return['rec_id'];
                $return_goods['ret_id'] = $ret_id;
                $return_goods['goods_id'] = $order_goods['goods_id'];
                $return_goods['goods_name'] = $order_goods['goods_name'];
                $return_goods['brand_name'] = $brand_name;
                $return_goods['product_id'] = $order_goods['product_id'];
                $return_goods['goods_sn'] = $order_goods['goods_sn'];
                $return_goods['is_real'] = $order_goods['is_real'];
                $return_goods['goods_attr'] = $attr_val;  //换货的商品属性名称
                $return_goods['attr_id'] = $return_attr_id; //换货的商品属性ID值
                $return_goods['refound'] = $order_goods['goods_price'];

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

                /* 获取表字段 */
                $goodsOther = $this->baseRepository->getArrayfilterTable($return_goods, 'wholesale_return_goods');

                WholesaleReturnGoods::insertGetId($goodsOther);

                $images_count = WholesaleReturnImages::where('rec_id', $rec_id)
                    ->where('user_id', $user_id)
                    ->count();

                if ($images_count > 0) {
                    $images['rg_id'] = $order_goods['goods_id'];
                    WholesaleReturnImages::where('rec_id', $rec_id)
                        ->where('user_id', $user_id)
                        ->update($images);
                }
                //退货数量插入退货表扩展表  by kong
                $order_return_extend = array(
                    'ret_id' => $ret_id,
                    'return_number' => $return_number
                );

                WholesaleOrderReturnExtend::insert($order_return_extend);

                $address_detail = $order_goods['region'] . ' ' . $order_return['address'];
                $order_return['address_detail'] = $address_detail;
                $order_return['apply_time'] = local_date("Y-m-d H:i:s", $order_return['apply_time']);
                return show_message($GLOBALS['_LANG']['Apply_Success_Prompt'], $GLOBALS['_LANG']['See_Returnlist'], 'user_wholesale.php?act=wholesale_return_list', 'info', true, $order_return);
            } else {
                return show_message($GLOBALS['_LANG']['Apply_abnormal'], '', '', 'info', true);
            }
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-删除已完成退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'wholesale_order_delete_return') {
            $order = strip_tags(urldecode(request()->input('order', '')));
            $order = json_str_iconv($order);

            $result = array('error' => 0, 'content' => '', 'order_id' => '', 'pager' => '');
            $order = dsc_decode($order);
            $order_id = $order->order_id;
            $result['order_id'] = $order_id;

            $delete = WholesaleOrderReturn::where('user_id', $user_id)
                ->where('ret_id', $result['order_id'])
                ->delete();

            if ($delete) {
                $return_list = $this->orderService->wholesaleReturnOrder();
                $this->smarty->assign('orders', $return_list);
                $page = (int)request()->input('page', 1);

                $record_count = WholesaleOrderReturn::where('user_id', $user_id)->count();

                $action = 'wholesale_return_list';
                $result['pager'] = get_pager('user.php', array('act' => $action), $record_count, $page);
                $result['content'] = $this->smarty->fetch("library/user_wholesale_return_order_list.lbi");

                return response()->json($result);
            }
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-我的采购单
        /* ------------------------------------------------------ */
        elseif ($action == 'purchase') {
            load_helper('transaction');

            $this->smarty->assign("action", $action);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批发中心-我的求购单
        /* ------------------------------------------------------ */
        elseif ($action == 'want_buy') {
            load_helper('transaction');

            $this->smarty->assign("action", $action);
            return $this->smarty->display('user_transaction.dwt');
        }

    }
}
