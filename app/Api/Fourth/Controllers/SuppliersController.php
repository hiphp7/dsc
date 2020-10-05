<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Suppliers;
use App\Models\Wholesale;
use App\Models\WholesaleCart;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Payment\PaymentService;
use App\Services\Wholesale\CartService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\GoodsService;
use App\Services\Wholesale\WholesaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SuppliersController extends Controller
{
    protected $wholesaleService;
    protected $categoryService;
    protected $goodsService;
    protected $baseRepository;
    protected $cartService;
    protected $paymentService;
    protected $commonRepository;
    protected $merchantCommonService;
    protected $sessionRepository;
    protected $config;
    protected $dscRepository;
    protected $orderCommonService;
    protected $commonService;

    public function __construct(
        WholesaleService $wholesaleService,
        CategoryService $categoryService,
        GoodsService $goodsService,
        BaseRepository $baseRepository,
        CartService $cartService,
        PaymentService $paymentService,
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        SessionRepository $sessionRepository,
        DscRepository $dscRepository,
        OrderCommonService $orderCommonService,
        CommonService $commonService
    )
    {
        //加载外部类
        $files = [
            'suppliers'
        ];
        load_helper($files);
        $this->wholesaleService = $wholesaleService;
        $this->categoryService = $categoryService;
        $this->goodsService = $goodsService;
        $this->baseRepository = $baseRepository;
        $this->cartService = $cartService;
        $this->paymentService = $paymentService;
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderCommonService = $orderCommonService;
        $this->commonService = $commonService;
    }

    /**
     * 供求列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [

        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->failed(lang('user.not_login'));
        }
        $allow = $this->commonService->judgeWholesaleUse($user_id);

        return $this->succeed($allow);
    }

    /**
     * 首页数据
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show(Request $request)
    {
        //数据验证
        $this->validate($request, []);
        // 首页轮播图
        $banner = $this->wholesaleService->get_banner('supplier', 3);
        $date['banner'] = $banner;

        // 批发分类
        $wholesale_cat = $this->categoryService->getCategoryList();
        $date['wholesale_cat'] = $wholesale_cat;

        // 限时采购
        $wholesale_limit = $this->wholesaleService->getWholesaleLimit();
        $date['wholesale_limit'] = $wholesale_limit;

        // 供求首页商品
        $goodsList = $this->goodsService->getWholesaleCatGoods();
        $date['wholesale_list'] = $goodsList;

        return $this->succeed($date);
    }

    /**
     * 搜索
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function search(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        $search_list = $this->goodsService->getSearchList($request->get('keyword'), $request->get('page'), $request->get('size'));

        return $this->succeed($search_list);
    }

    /**
     * 供求限时抢购
     * @param Request $request
     * @return JsonResponse
     */
    public function getlimit(Request $request)
    {
        $wholesale_limit = $this->wholesaleService->getWholesaleLimit();

        return $this->succeed($wholesale_limit);
    }

    /**
     * 供求首页商品
     * @param Request $request
     * @return JsonResponse
     */
    public function catgoods(Request $request)
    {
        $wholesale_cat = $this->goodsService->getWholesaleCatGoods();

        return $this->succeed($wholesale_cat);
    }

    /**
     * 批发分类
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function category(Request $request)
    {
        //数据验证
        $this->validate($request, [

        ]);
        $parent_id = isset($request['parent_id']) && empty($request['parent_id']) ? $request['parent_id'] : 0;

        $category_list = $this->categoryService->getCategoryList($parent_id);

        return $this->succeed($category_list);
    }

    /**
     * 供应商家首页
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function supplierhome(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'suppliers_id' => 'required|integer'
        ]);

        $suppliers_id = $request->get('suppliers_id', 0);

        $supplier = $this->goodsService->getSupplierHome($suppliers_id);

        return $this->succeed($supplier);
    }

    /**
     * 供应商家商品列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function homelist(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'suppliers_id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $page = $request->get('page');
        $size = $request->get('size');
        $suppliers_id = $request->get('suppliers_id', 0);
        $goods_list = $this->goodsService->getSuppliersList($suppliers_id, $size, $page, 'goods_id', 'DESC');

        return $this->succeed($goods_list);
    }

    /**
     * 取得某页的批发商品
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function goodslist(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'cat_id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $page = $request->get('page');
        $size = $request->get('size');
        $cat_id = $request->get('cat_id', 0);

        $goods_list = $this->goodsService->getWholesaleList($cat_id, $size, $page, 'goods_id', 'DESC');

        return $this->succeed($goods_list);
    }

    /**
     * 批发商品详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer',
        ]);
        $user_id = $this->authorization();

        $goods_id = $request->get('goods_id');
        // 商品信息
        $goods = $this->goodsService->getWholesaleGoodsInfo($goods_id);

        if (empty($goods)) {
            return $this->responseNotFound();
        }

        // 购物车商品数量
        $goods['cart_number'] = $this->cartService->getHasCartGoods($user_id);

        // 商品属性
        $properties = $this->goodsService->getWholesaleGoodsProperties($goods['goods_id']);  // 获得商品的规格和属性
        $goods['properties'] = $properties;

        $goods['goods_pro'] = '';
        // 获得商品的规格
        if ($properties['pro']) {
            $pro = collect($properties['pro'])->values()->all();
            foreach ($pro as $val) {
                $goods_pro = $val;
            }
            $goods['goods_pro'] = $goods_pro;
        }

        // 商品相册
        $goodsGallery = $this->goodsService->getGalleryList($goods_id);
        $goods['goods_img'] = $goodsGallery;

        return $this->succeed($goods);
    }

    /**
     * 获取属性库存
     * @param Request $request
     * @return JsonResponse
     */
    public function changenum(Request $request)
    {
        //处理数据
        $goods_id = $request->input('goods_id', 0);
        $attr_id = $request->input('attr') ? $request->input('attr') : [];
        $main_attr_list = $this->goodsService->getWholesaleMainAttrList($goods_id, $attr_id);

        return $this->succeed($main_attr_list);
    }

    /**
     * 改变属性、数量时重新计算商品价格
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function changeprice(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer',
        ]);
        //处理数据
        $goods_id = $request->get('goods_id');
        //判断商品是否设置属性
        $goods_type = Wholesale::where('goods_id', $goods_id)->value('goods_type');
        // 获取属性
        $properties = $this->goodsService->getWholesaleGoodsProperties($goods_id);

        if ($goods_type > 0 && $properties['spe']) { //有属性的时候
            $attr_array = $request->input('attr_array', []); //dsc_decode($request->input('attr_array', []));
            $num_array = $request->input('num_array', []); //dsc_decode($request->input('num_array', []));

            $result['total_number'] = array_sum($num_array);

            //格式化属性数组
            $attr_num_array = array();

            if ($attr_array) {
                foreach ($attr_array as $key => $val) {
                    $arr = array();
                    $arr['attr'] = $val;
                    $arr['num'] = $num_array[$key];
                    $attr_num_array[] = $arr;
                }
            }
            //生成记录表格
            $record_data = $this->goodsService->getSelectRecordData($goods_id, $attr_num_array);
            $result['record_data'] = $record_data;
        } else {
            //无属性的时候
            $goods_number = $request->input('goods_number', 0);
            $result['total_number'] = $goods_number;
        }

        //计算价格
        $data = $this->goodsService->calculateGoodsPrice($goods_id, $result['total_number']);
        $result['data'] = $data;

        return $this->succeed($result);
    }

    /**
     * 批发addtocart
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addtocart(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer',
        ]);
        $user_id = $this->authorization();
        if (empty($user_id)) {
            $session_id = $this->sessionRepository->realCartMacIp();
        }
        //处理数据
        $goods_id = $request->get('goods_id');
        //判断商品是否设置属性
        $goods_type = Wholesale::where('goods_id', $goods_id)->value('goods_type');
        // 获取属性
        $properties = $this->goodsService->getWholesaleGoodsProperties($goods_id);
        if ($goods_type > 0 && $properties['spe']) { //有属性的时候
            $attr_array = $request->input('attr_array', []); //dsc_decode($request->input('attr_array', []));
            $num_array = $request->input('num_array', []); //dsc_decode($request->input('num_array', []));
            $result['total_number'] = array_sum($num_array);

            //格式化属性数组
            $attr_num_array = array();

            if ($attr_array) {
                foreach ($attr_array as $key => $val) {
                    $arr = array();
                    $arr['attr'] = $val;
                    $arr['num'] = $num_array[$key];
                    $attr_num_array[] = $arr;
                }
            }
            //生成记录表格
            $record_data = $this->goodsService->getSelectRecordData($goods_id, $attr_num_array);
        } else {
            //无属性的时候
            $goods_number = $request->input('goods_number', 0);
            $result['total_number'] = $goods_number;
        }
        //计算价格
        $price_info = $this->goodsService->calculateGoodsPrice($goods_id, $result['total_number']);

        //商品信息
        $goods_info = Wholesale::where('goods_id', $goods_id);
        $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

        //通用数据
        $common_data = array();
        $common_data['user_id'] = $user_id;
        $common_data['session_id'] = $session_id ?? 0;
        $common_data['goods_id'] = $goods_id;
        $common_data['goods_sn'] = $goods_info['goods_sn'];
        $common_data['goods_name'] = $goods_info['goods_name'];
        $common_data['goods_price'] = $price_info['unit_price'];
        $common_data['goods_number'] = 0;
        $common_data['goods_attr_id'] = '';
        $common_data['suppliers_id'] = $goods_info['suppliers_id'];
        $common_data['add_time'] = gmtime();

        //加入购物车
        if ($properties && $goods_type > 0 && $properties['spe']) {
            foreach ($attr_array as $key => $val) {

                //货品信息
                $attr = explode(',', $val);

                //处理数据
                $data = $common_data;
                $data['goods_attr'] = $data['goods_attr'] ?? '';

                $gooda_attr = $this->goodsService->getGoodsAttrArray($goods_id, $val);
                foreach ($gooda_attr as $v) {
                    $data['goods_attr'] .= $v['attr_name'] . ":" . $v['attr_value'] . "\n";
                }
                if ($num_array[$key] == 0) {
                    continue;
                }
                $data['goods_attr_id'] = $val;
                $data['goods_number'] = $num_array[$key];

                //货品数据
                $product_info = $this->goodsService->getWholesaleProductInfo($goods_id, $attr);

                $data['product_id'] = $product_info['product_id'] ?? 0;

                //判断是更新还是插入
                $rec_id = $this->cartService->getFlowAddToCartRecId($goods_id, $attr, $user_id);

                if (!empty($rec_id)) {
                    $goods_number = $data['goods_number'];
                    unset($data['goods_number']);

                    WholesaleCart::where('rec_id', $rec_id)->increment('goods_number', $goods_number, $data);
                } else {
                    WholesaleCart::insert($data);
                }
            }
        } else {
            $data = $common_data;
            $data['goods_number'] = $goods_number;
            if ($goods_number == 0) {
                $result['error'] = 1;
                $result['msg'] = lang('suppliers.goods_number_must');
                return $this->succeed($result);
            }
            //判断是更新还是插入
            $rec_id = $this->cartService->getFlowAddToCartRecId($goods_id, '', $user_id);

            if (!empty($rec_id)) {
                $goods_number = $data['goods_number'];
                unset($data['goods_number']);

                WholesaleCart::where('rec_id', $rec_id)->increment('goods_number', $goods_number, $data);
            } else {
                WholesaleCart::insert($data);
            }
        }

        //重新计算价格并更新价格
        $this->cartService->calculateCartGoodsPrice($goods_id, '', $user_id);
        // 购物车商品数量
        $result['cart_number'] = $this->cartService->getHasCartGoods($user_id);

        return $this->succeed($result);
    }


    /**
     * 购物车列表
     */
    public function cart(Request $request)
    {
        $user_id = $this->authorization();
        $goods_id = $request->input('goods_id', 0);

        session([
            'user_id' => $user_id
        ]);

        $cart_info = $this->cartService->wholesaleCartGoods($goods_id);
        return $this->succeed($cart_info);
    }

    /**
     * 选中购物车商品
     */
    public function checked(Request $request)
    {
        $rec_ids = $request->input('rec_id') ? $request->input('rec_id') : [];

        $user_id = $this->authorization();

        session([
            'user_id' => $user_id
        ]);

        $rec_ids = $rec_ids ? implode(',', $rec_ids) : '';

        //获取购物车商品数量和价格
        $cart_info = $this->cartService->wholesaleCartInfo(0, $rec_ids);
        $result['cart_info'] = $cart_info;

        return $this->succeed($result);
    }

    /*
     **批发商品详情
    */
    public function updatecart(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'rec_id' => 'required|integer',
            'rec_num' => 'required|integer',
        ]);
        $user_id = $this->authorization();

        session([
            'user_id' => $user_id
        ]);

        $rec_ids = $request->input('rec_ids', []);
        $rec_id = $request->input('rec_id', '');
        $rec_num = $request->input('rec_num', '');
        //查询库存
        $cart_info = WholesaleCart::where('rec_id', $rec_id);
        $cart_info = $this->baseRepository->getToArrayFirst($cart_info);


        $result = array('error' => 1, 'message' => '', 'content' => '');
        if (empty($cart_info)) {
            return json_encode($result);
        }

        if ($cart_info['product_id']) {
            $goods_number = WholesaleProducts::where('goods_id', $cart_info['goods_id'])
                ->where('product_id', $cart_info['product_id'])->value('product_number');
        } else {
            $goods_number = Wholesale::where('goods_id', $cart_info['goods_id'])->value('goods_number');
        }

        $goods_number = $goods_number ? $goods_number : 0;
        $result['goods_number'] = $goods_number;

        if ($goods_number < $rec_num) {
            $result['error'] = 1;
            $result['message'] = sprintf(lang('suppliers.goods_number_limit'), $goods_number);
            $rec_num = $goods_number;
        }

        WholesaleCart::where('rec_id', $rec_id)->update(['goods_number' => $rec_num]);
        /*
         * 返回购物车
         */
        $rec_ids = $rec_ids ? implode(',', $rec_ids) : '';
        //商品信息
        $cart_goods = $this->cartService->wholesaleCartGoods(0, $rec_ids);

        $goods_list = [];
        if ($cart_goods) {
            foreach ($cart_goods as $key => $val) {
                foreach ($val['goods_list'] as $k => $g) {
                    //商品数据
                    $goods_list[$g['goods_id']] = $g;
                }
            }
        }

        $result['goods_list'] = $goods_list;

        //订单信息
        $cart_info = $this->cartService->wholesaleCartInfo(0, $rec_ids);
        $result['cart_info'] = $cart_info;

        return $this->succeed($result);
    }

    /**
     *清空购物车
     */
    public function clearcart(Request $request)
    {
        $user_id = $this->authorization();
        $goods_id = $request->input('goods_id', 0);
        $rec_id = $request->input('rec_id', 0);
        $rec_ids = $request->input('rec_ids') ? $request->input('rec_ids') : [];
        $rec_ids = $rec_ids ? implode(',', $rec_ids) : '';

        $res = WholesaleCart::whereRaw(1);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('session_id', $session_id);
        }
        if (!empty($rec_id)) {
            $res = $res->where('rec_id', $rec_id);
        }

        if (!empty($rec_ids)) {
            $rec_ids = $this->baseRepository->getExplode($rec_ids);
            $res = $res->whereIn('rec_id', $rec_ids);
        }

        if (!empty($goods_id)) {
            $res = $res->where('goods_id', $goods_id);
        }
        $res->delete();

        // 购物车列表
        session([
            'user_id' => $user_id
        ]);

        $cart_info = $this->cartService->wholesaleCartGoods($goods_id);

        $result = [
            'error' => 0,
            'message' => lang('suppliers.cart_move_success'),
            'cart_info' => $cart_info
        ];
        return $this->succeed($result);
    }

    /*
     **批发订单提交页面
    */
    public function flow(Request $request)
    {

        //数据验证
        $cart_value = $request->input('rec_ids', []);

        $cart_value = isset($cart_value) && !empty($cart_value) ? implode(',', addslashes_deep($cart_value)) : '';

        $user_id = $this->uid;

        if ($cart_value && count(explode(",", $cart_value)) == 1) {
            $cart_value = intval($cart_value);
        }

        session([
            'user_id' => $user_id
        ]);

        /* 对商品信息赋值 */
        $result['cart_goods'] = $this->cartService->wholesaleCartGoods(0, $cart_value);
        // 检测最小起订量
        foreach ($result['cart_goods'] as $key => $val) {
            foreach ($val['goods_list'] as $ke => $row) {
                if ($row['total_number'] < $row['moq']) {
                    $date = [
                        'error' => 1,
                        'message' => $row['goods_name'] . sprintf(lang('suppliers.must_limit_number'), $row['moq'])
                    ];
                    return $this->succeed($date);
                    break;
                }
            }
        }
        /*
         ** 计算订单的费用
        */
        $result['total'] = $this->cartService->wholesaleCartInfo(0, $cart_value);

        $result['consignee'] = $this->cartService->getConsignee($user_id);
        //发票内容

        if ($this->config['can_invoice'] == 1) {
            $result['invoice_content'] = explode("\n", str_replace("\r", '', $this->config['invoice_content']));
        }
        $payment_list = $this->paymentService->availablePaymentList(1, 0);
        if ($payment_list) {
            foreach ($payment_list as $key => $payment) {
                if ($payment ['pay_code'] == 'cod') {
                    unset($payment_list [$key]);
                }
            }
            $result['payment_list'] = $payment_list;
        }

        return $this->succeed($result);
    }

    /*
      **批发订单提交
     */
    public function done(Request $request)
    {
        $common_data = [
            'done_cart_value' => $request->post('done_cart_value', []),
            'inv_type' => $request->post('inv_type', 0),
            'postscript' => $request->post('postscript', ''),
            'inv_payee' => $request->post('inv_payee', '个人'),
            'inv_content' => $request->post('inv_content', '不开发票'),
            'invoice_type' => $request->post('invoice_type', 0),
            'tax_id' => $request->post('tax_id', 0),
            'invoice' => $request->post('invoice', 0),
            'pay_id' => $request->post('pay_id', 0),
        ];
        $is_surplus = $request->post('is_surplus', 0);
        //当支付方式为余额支付的时候，pay_id查询
        if ($is_surplus == 1) {
            $paymentinfo = $this->paymentService->getPaymentInfo(['pay_code' => 'balance']);
            $common_data['pay_id'] = $paymentinfo['pay_id'];
        }

        $common_data['done_cart_value'] = implode(',', addslashes_deep($common_data['done_cart_value']));

        if (count(explode(",", $common_data['done_cart_value'])) == 1) {
            $common_data['done_cart_value'] = intval($common_data['done_cart_value']);
        }
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $consignee = $this->cartService->getConsignee($user_id);
        $common_data['consignee'] = $consignee['consignee'];
        $common_data['email'] = $consignee['email'];
        $common_data['mobile'] = $consignee['mobile'];
        $common_data['tel'] = $consignee['tel'];
        $common_data['country'] = $consignee['country'];
        $common_data['province'] = $consignee['province'];
        $common_data['city'] = $consignee['city'];
        $common_data['district'] = $consignee['district'];
        $common_data['street'] = $consignee['street'];
        $common_data['address'] = $consignee['address'];
        $common_data['zipcode'] = $consignee['zipcode'];
        $common_data['best_time'] = $consignee['best_time'];
        $common_data['sign_building'] = $consignee['sign_building'];

        session([
            'user_id' => $user_id
        ]);

        $rec_ids = $this->baseRepository->getExplode($common_data['done_cart_value']);

        //检查下架商品
        if ($rec_ids) {
            $goods_ids = WholesaleCart::whereIn('rec_id', $rec_ids);
            $goods_ids = $this->baseRepository->getToArrayGet($goods_ids);
            $goods_ids = $this->baseRepository->getKeyPluck($goods_ids, 'goods_id');

            $goods_ids = array_unique($goods_ids);
            foreach ($goods_ids as $key => $value) {
                $enabled = get_table_date('wholesale', "goods_id='$value'", array('enabled'), 2);
                if (empty($enabled)) {
                    return $result = [
                        'code' => 1,
                        'msg' => lang('suppliers.ordergoods_not_on_sale')
                    ];
                }
            }
        }

        //内部数据
        load_helper(['order', 'clips']);
        $main_order = $common_data;
        $main_order['order_sn'] = $this->orderCommonService->getOrderSn(); //获取订单号
        $main_order['main_order_id'] = 0; //主订单
        $main_order['add_time'] = gmtime();
        $main_order['goods_amount'] = 0;
        $main_order['order_amount'] = 0;
        $main_order['user_id'] = session('user_id', 0);

        /* 获取表字段 插入主订单 */
        $other_order = $this->baseRepository->getArrayfilterTable($main_order, 'wholesale_order_info');

        $main_order_id = WholesaleOrderInfo::insertGetId($other_order);

        //开始分单 start
        $suppliers_id = [];
        if ($rec_ids) {
            $suppliers_id = WholesaleCart::whereIn('rec_id', $rec_ids)->where('user_id', $main_order['user_id']);
            $suppliers_id = $this->baseRepository->getToArrayGet($suppliers_id);
            $suppliers_id = $this->baseRepository->getKeyPluck($suppliers_id, 'suppliers_id');

            $suppliers_id = array_unique($suppliers_id);
        }

        if ($suppliers_id) {
            foreach ($suppliers_id as $key => $val) {
                //内部数据
                $child_order = $common_data;
                $child_order['order_sn'] = $this->orderCommonService->getOrderSn(); //获取订单号
                $child_order['main_order_id'] = $main_order_id; //主订单
                $child_order['user_id'] = $main_order['user_id'];
                $child_order['add_time'] = gmtime();
                $child_order['goods_amount'] = 0;
                $child_order['order_amount'] = 0;
                $child_order['suppliers_id'] = $val;

                /* 获取表字段 */
                $other_order = $this->baseRepository->getArrayfilterTable($child_order, 'wholesale_order_info');

                //插入子订单
                $child_order_id = WholesaleOrderInfo::insertGetId($other_order);

                //购物车商品数据
                $cart_goods = WholesaleCart::where('suppliers_id', $val)
                    ->whereIn('rec_id', $rec_ids)
                    ->where('user_id', $main_order['user_id']);
                $cart_goods = $this->baseRepository->getToArrayGet($cart_goods);

                if ($cart_goods) {
                    foreach ($cart_goods as $k => $v) {
                        unset($v['rec_id']);

                        //插入订单商品表
                        $v['order_id'] = $child_order_id;

                        /* 获取表字段 */
                        $other_v = $this->baseRepository->getArrayfilterTable($v, 'wholesale_order_goods');

                        WholesaleOrderGoods::insert($other_v);

                        //统计子订单金额
                        $child_order['goods_amount'] += $v['goods_price'] * $v['goods_number'];
                        $child_order['order_amount'] = $child_order['goods_amount'];
                    }
                }

                //更新子订单数据

                /* 获取表字段 */
                $other_order = $this->baseRepository->getArrayfilterTable($child_order, 'wholesale_order_info');

                WholesaleOrderInfo::where('order_id', $child_order_id)->update($other_order);

                insert_pay_log($child_order_id, $child_order['order_amount'], PAY_WHOLESALE);//更新子订单支付日志

                //统计主订单金额
                $main_order['goods_amount'] += $child_order['goods_amount'];
                $main_order['order_amount'] = $main_order['goods_amount'];

                $suppliers = Suppliers::where('suppliers_id', $val);
                $suppliers = $this->baseRepository->getToArrayFirst($suppliers);

                if ($suppliers && $GLOBALS['_CFG']['sms_order_placed'] == '1' && $suppliers['mobile_phone'] != '') {
                    $shop_name = $this->merchantCommonService->getShopName($suppliers['user_id'], 1);
                    $order_region = $this->wholesaleService->getFlowWholesaleUserRegion($child_order_id);

                    //普通订单->短信接口参数
                    $smsParams = array(
                        'shop_name' => $shop_name,
                        'shopname' => $shop_name,
                        'order_sn' => $child_order['order_sn'],
                        'ordersn' => $child_order['order_sn'],
                        'consignee' => $child_order['consignee'],
                        'order_region' => $order_region,
                        'orderregion' => $order_region,
                        'address' => $child_order['address'],
                        'order_mobile' => $child_order['mobile'],
                        'ordermobile' => $child_order['mobile'],
                        'mobile_phone' => $suppliers['mobile_phone'],
                        'mobilephone' => $suppliers['mobile_phone']
                    );

                    $this->commonRepository->smsSend($suppliers['mobile_phone'], $smsParams, 'sms_order_placed');
                }
            }
        }

        /* 获取表字段 */
        $other_order = $this->baseRepository->getArrayfilterTable($main_order, 'wholesale_order_info');

        WholesaleOrderInfo::where('order_id', $main_order_id)->update($other_order);

        $order_amount = WholesaleOrderInfo::where('order_id', $main_order_id)->value('order_amount');

        $order_amount = $order_amount ? $order_amount : 0;

        insert_pay_log($main_order_id, $order_amount, PAY_WHOLESALE);//更新主订单支付日志
        //开始分单 end


        //插入数据完成后删除购物车订单
        WholesaleCart::whereIn('rec_id', $rec_ids)
            ->where('user_id', $main_order['user_id'])
            ->delete();
        $main_order_sn = WholesaleOrderInfo::where('order_id', $main_order_id)->value('order_sn');
        if ($is_surplus == 1) {
            $this->wholesaleService->Balance($user_id, $main_order_sn);
            //如果使用库存，且下订单付款时减库存，则减少库存
            if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PAID) {
                suppliers_change_order_goods_storage($main_order_id, true, SDT_PAID);
            }
        }

        //如果使用库存，且下订单时减库存，则减少库存
        if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PLACE) {
            suppliers_change_order_goods_storage($main_order_id, true, SDT_PLACE);
        }

        return $result = [
            'code' => 0,
            'msg' => lang('suppliers.order_success'),
            'main_order_id' => $main_order_id,
            'main_order_sn' => $main_order_sn,
        ];
    }

    /**
     * 支付
     * @param Request $request
     * @return JsonResponse
     */
    public function PayCheck(Request $request)
    {
        $this->validate($request, [
            'order_sn' => 'required'
        ]);

        $order_sn = $request->input('order_sn', '');

        $uid = $this->authorization();

        if (empty($uid)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->wholesaleService->PayCheck($uid, $order_sn);

        return $this->succeed($data);
    }

    /**
     * 切换支付方式
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function change_payment(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'pay_id' => 'required|integer',
            'order_id' => 'required|integer',
        ]);

        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $order_id = $request->input('order_id');
        $pay_id = $request->input('pay_id');

        $paymentCode = $this->wholesaleService->change_payment($order_id, $pay_id, $user_id);

        return $this->succeed($paymentCode);
    }

    /**
     * 余额支付
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function balance(Request $request)
    {
        $this->validate($request, [
            'order_sn' => 'required|integer',
        ]);
        $order_sn = $request->input('order_sn', '');

        $uid = $this->authorization();


        if (empty($uid)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->wholesaleService->Balance($uid, $order_sn);

        return $this->succeed($data);
    }
}
