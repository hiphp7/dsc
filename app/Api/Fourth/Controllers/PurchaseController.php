<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Goods;
use App\Models\LinkDescGoodsid;
use App\Models\Wholesale;
use App\Models\wholesaleCart;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Order\OrderCommonService;
use App\Services\Purchase\Purchase;
use App\Services\Wholesale\PurchaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class PurchaseController
 * @package App\Api\Fourth\Controllers
 */
class PurchaseController extends Controller
{
    protected $config;
    protected $purchaseService;
    protected $purchase;
    protected $dscRepository;
    protected $baseRepository;
    protected $goodsGalleryService;
    protected $sessionRepository;
    protected $orderCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        PurchaseService $purchaseService,
        Purchase $purchase,
        DscRepository $dscRepository,
        GoodsGalleryService $goodsGalleryService,
        SessionRepository $sessionRepository,
        OrderCommonService $orderCommonService
    )
    {
        $this->purchase = $purchase;
        $this->purchaseService = $purchaseService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderCommonService = $orderCommonService;
    }

    /**
     * 采购聚合页
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $banners = $this->purchase->get_banner(256, 3); //轮播图
        $wholesale_cat = $this->purchase->get_wholesale_child_cat(); //批发分类
        $wholesale_limit = $this->purchase->get_wholesale_limit(); //限时采购
        $goodsList = $this->purchase->get_wholesale_cat(); //批发商品

        //数据验证
        $this->validate($request, []);
        //返回用户ID
        $user_id = $this->authorization();

        $result = [];

        $result['page_title'] = lang('purchase.purchase_title');
        $result['action'] = 'index';
        $result['banners'] = $banners;
        $result['wholesale_cat'] = $wholesale_cat;
        $result['wholesale_limit'] = $wholesale_limit;
        $result['get_wholesale_cat'] = $goodsList;

        return $this->succeed($result);
    }

    /**
     * 采购列表页
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $cat_id = $request->get('cat_id', 0);
        $size = 10;
        $result = [];

        $result['page_title'] = lang('purchase.purchase_list');
        $result['action'] = 'list';

        //分类名
        if ($cat_id) {
            $result['cat_name'] = $this->purchase->getCatName($cat_id);
            $result['cat_id'] = $cat_id;
        }

        //分类列表
        $wholesale_cat = $this->purchase->get_wholesale_child_cat();
        $result['wholesale_cat'] = $wholesale_cat;

        return $this->succeed($result);
    }

    /**
     * 采购商品列表
     * @param Request $request
     * @return JsonResponse
     */
    public function goodsList(Request $request)
    {
        // 根据批发ID获取商品信息
        $act_id = $request->get('id', 0);
        $page = $request->get('page', 1);
        $size = 10;

        $result = $this->purchase->get_wholesale_list($act_id, $size, $page);

        return $this->succeed($result);
    }

    /**
     * 异步获取 搜索的商品
     * @param Request $request
     * @return JsonResponse
     */
    public function searchList(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $page = $request->get('page', 1);
        $size = !empty($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10;
        $list = '';// $list = $this->purchase->get_search_goods_list($keyword, $page, $size);
        return $this->succeed($list);
    }

    /**
     * 添加到购物车
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function addToCart(Request $request)
    {
        $user_id = $this->authorization();
        $goods_id = $request->get('goods_id', 0);
        $user_rank = session('user_rank', 1);
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //判断商品是否设置属性
        $goods_type = Wholesale::where('goods_id', $goods_id)->value('goods_type');

        if ($goods_type > 0) {
            $attr_array = $request->get('attr_array', []);
            $num_array = $request->get('num_array', []);
            $total_number = array_sum($num_array);
        } else {
            $goods_number = $request->get('num_array', [1]);
            $goods_number = $goods_number[0];
            $total_number = $goods_number;
        }

        $rank_ids = wholesale::where('goods_id', $goods_id)->value('rank_ids');
        $is_jurisdiction = 0;
        if ($user_id) {
            //判断是否是商家
            $seller_id = AdminUser::where('ru_id', $user_id)->value('user_id');
            if ($seller_id > 0) {
                $is_jurisdiction = 1;
            } else {
                //判断是否设置了普通会员
                if ($rank_ids) {
                    $rank_arr = explode(',', $rank_ids);
                    if (in_array($user_rank, $rank_arr)) {
                        $is_jurisdiction = 1;
                    }
                }
            }
        } else {
            //提示登陆
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //计算价格
        $price_info = calculate_goods_price($goods_id, $total_number);
        //商品信息
        $goods_info = Goods::select('goods_name', 'goods_sn', 'user_id')->where('goods_id', $goods_id)->first();
        $goods_info = $goods_info ? $goods_info->toArray() : [];
        //通用数据
        $common_data = [];
        $common_data['user_id'] = $user_id;
        $common_data['session_id'] = defined('SESS_ID') ? SESS_ID : '';
        $common_data['goods_id'] = $goods_id;
        $common_data['goods_sn'] = $goods_info['goods_sn'];
        $common_data['goods_name'] = $goods_info['goods_name'];
        $common_data['market_price'] = $price_info['market_price'];
        $common_data['goods_price'] = $price_info['unit_price'];
        $common_data['goods_number'] = 0;
        $common_data['goods_attr_id'] = '';
        $common_data['ru_id'] = $goods_info['user_id'];
        $common_data['add_time'] = gmtime();

        //加入购物车
        if ($user_id) {
            $sess_id = "user_id = '$user_id'";
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $sess_id = "session_id = '$session_id'";
        }

        if ($goods_type > 0) {
            foreach ($attr_array as $key => $val) {
                //货品信息
                $attr = explode(',', $val);
                //处理数据
                $data = $common_data;
                $gooda_attr = get_goods_attr_array($val);
                foreach ($gooda_attr as $v) {
                    $data['goods_attr'] .= $v['attr_name'] . ":" . $v['attr_value'] . "\n";
                }
                $data['goods_attr_id'] = $val;
                $data['goods_number'] = $num_array[$key];
                //货品数据
                $set = $this->purchase->get_find_in_set($attr, 'goods_attr', ',');
                $product_info = WholesaleProducts::whereRaw("goods_id = '$goods_id'" . $set)->first();
                $product_info = $product_info ? $product_info->toArray() : [];
                if ($product_info) {
                    $data['goods_sn'] = $product_info['product_sn'];
                }

                //判断是更新还是插入
                $set = $this->purchase->get_find_in_set($attr, 'goods_attr_id', ',');
                $rec_id = WholesaleCart::whereRaw($sess_id . $set)->where('goods_id', $goods_id)->value('rec_id');
                wholesaleCart::updateOrCreate(['rec_id' => $rec_id], $data);
            }
        } else {
            $data = $common_data;
            $data['goods_number'] = $goods_number;
            //判断是更新还是插入
            $rec_id = WholesaleCart::whereRaw($sess_id . " AND goods_id = '$goods_id'")->value('rec_id');
            wholesaleCart::updateOrCreate(['rec_id' => $rec_id], $data);
        }

        //重新计算价格并更新价格
        calculate_cart_goods_price($goods_id, '', $user_id);
        $goods_data = $this->purchase->get_count_cart($user_id);

        // 获取购物车商品数量
        $result['message'] = lang('purchase.purchase_addto_cart');
        $result['content'] = $goods_data;

        return $this->succeed($result);
    }

    /**
     * 采购详情
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function goods(Request $request)
    {
        $act_id = $request->get('act_id', 0);
        $province_id = $request->get('province_id', 0);
        //返回用户ID
        $user_id = $this->authorization();
        $result = [];

        $result['page_title'] = lang('purchase.purchase_goods_detail');
        $result['action'] = 'goods';

        // 根据批发ID获取商品信息
        $goods = $this->purchase->get_wholesale_goods_info($act_id);

        /** 商品相册 */
        $pictures = $this->goodsGalleryService->getGoodsGallery($goods['goods_id']);
        $result['pictures'] = $pictures;                  // 商品相册

        // 查询关联商品描述
        $link_desc = LinkDescGoodsid::where('goods_id', $goods['goods_id'])
            ->with(['getLinkGoodsDesc' => function ($query) {
                $query->select('goods_desc', 'd_id')->where('review_status', '>', 2);
            }]);

        $link_desc = $link_desc->first();
        $link_desc = $link_desc ? $link_desc->toArray() : [];

        if ($link_desc) {
            $goods['goods_desc'] = $link_desc['get_link_goods_desc']['goods_desc'];
        }

        $result['goods'] = $goods;

        // 最小起订量
        $min = 0;
        foreach ($goods['volume_price'] as $list) {
            if ($min == 0 || $min > $list['volume_number']) {
                $min = $list['volume_number'];
            }
        }
        $result['min'] = $min;

        /** 商品属性 */
        $properties = $this->purchase->getWholesaleGoodsProperties($goods['goods_id']);  // 获得商品的规格和属性
        $result['specification'] = $properties['spe'];      // 商品属性

        $main_attr_list = $this->purchase->getWholesaleMainAttrList($goods['goods_id']);
        $result['main_attr_list'] = $main_attr_list;
        $result['properties'] = $properties['pro'];       // 商品规格

        /** 判断用户是否有权购买 */
        //$is_jurisdiction = Purchase::isJurisdiction($goods, $user_id);暂时隐藏权限判断，pc端权限暂时去掉了
        //$this->assign('is_jurisdiction', $is_jurisdiction);
        $result['is_jurisdiction'] = 1;

        /** 购物车信息 */
        $cartInfo = $this->purchase->get_wholesale_cart_info($user_id);
        $result['cart_number'] = $cartInfo['number'];

        /** 登录返回地址 */
        $result['is_login'] = empty($user_id) ? 0 : 1;
        //$back_url = url('user/login/index', ['back_act' => urlencode(__SELF__)]);
        // $this->assign('back_url', $back_url);

        return $this->succeed($result);
    }

    /**
     * 改变单价
     * @param Request $request
     * @return void
     */
    public function changePrice(Request $request)
    {
        $num = $request->get('num', 1);
        $goods_id = $request->get('goods_id', 0);
        $price_info = calculate_goods_price($goods_id, $num);
        $price_info['unit_price'] = price_format($price_info['unit_price']);
        $this->succeed($price_info);
    }

    /**
     * 提交批发订单
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function down(Request $request)
    {
        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $now = gmtime();

        //公共数据
        $common_data['consignee'] = $request->get('consignee', '');
        $common_data['mobile'] = $request->get('mobile', '');
        $common_data['address'] = $request->get('address', '');
        $common_data['inv_type'] = $request->get('inv_type', 0);
        $common_data['pay_id'] = $request->get('pay_id', 0);
        $common_data['postscript'] = $request->get('postscript', '');
        $common_data['inv_payee'] = $request->get('inv_payee', '');
        $common_data['tax_id'] = $request->get('tax_id', '');
        //内部数据
        $main_order = $common_data;
        $main_order['order_sn'] = $this->orderCommonService->getOrderSn(); //获取订单号
        $main_order['main_order_id'] = 0; //主订单
        $main_order['user_id'] = $user_id;
        $main_order['add_time'] = $now;
        $main_order['order_amount'] = 0;

        $main_order_id = WholesaleOrderInfo::insertGetId($main_order); //主订单id

        //开始分单 start
        $rec_ids = $request->get('rec_ids', '');
        $where = " user_id = '$user_id' AND rec_id IN ('$rec_ids') ";
        if (empty($rec_ids)) {
            //报错
            return [];
        }

        $ru_ids = WholesaleCart::whereRaw($where)->pluck('ru_id');
        foreach ($ru_ids as $key => $val) {
            //内部数据
            $child_order = $common_data;
            $child_order['order_sn'] = $this->orderCommonService->getOrderSn(); //获取订单号
            $child_order['main_order_id'] = $main_order_id; //主订单
            $child_order['user_id'] = $user_id;
            $child_order['add_time'] = $now;
            $child_order['order_amount'] = 0;
            //插入子订单
            $child_order_id = WholesaleOrderInfo::insertGetId($child_order); //子订单id

            //购物车商品数据
            $cart_goods = WholesaleCart::whereRaw($where)->where('ru_id', $val)->get();
            $cart_goods = $cart_goods ? $cart_goods->toArray() : [];

            if ($cart_goods) {
                foreach ($cart_goods as $k => $v) {
                    //插入订单商品表
                    $v['order_id'] = $child_order_id;
                    WholesaleOrderGoods::insert($v);
                    //统计子订单金额
                    $child_order['order_amount'] += $v['goods_price'] * $v['goods_number'];
                }
            }

            //更新子订单数据
            WholesaleOrderInfo::where('order_id', $child_order_id)->update($child_order);
            //统计主订单金额
            $main_order['order_amount'] += $child_order['order_amount'];
            insert_pay_log($child_order_id, $child_order['order_amount'], PAY_WHOLESALE);
        }

        //更新主订单数据
        WholesaleOrderInfo::where('order_id', $main_order_id)->update($main_order);

        $order_amount = WholesaleOrderInfo::where('order_id', $main_order_id)->value('order_amount');
        insert_pay_log($main_order_id, $order_amount, PAY_WHOLESALE);//更新主订单支付日志
        //开始分单 end

        //插入数据完成后删除购物车订单
        WholesaleCart::whereRaw($where)->delete();

        $result = [
            'code' => 0,
            'message' => lang('purchase.order_success')
        ];
        return $this->succeed($result);
    }

    /**
     * 进货单（批发购物车）
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function cart(Request $request)
    {
        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result['page_title'] = lang('purchase.purchase_cart');
        $result['action'] = 'cart';

        $goods_id = $request->get('goods_id', 0);
        $rec_ids = $request->get('rec_ids', '');

        $goods_data = $this->purchase->wholesale_cart_goods($goods_id, $rec_ids, $user_id);
        $result['goods_data'] = $goods_data;

        return $this->succeed($result);
    }

    /**
     * 更新购物车数量
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateCartGoods(Request $request)
    {
        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $rec_id = $request->get('rec_id', 0);
        $rec_num = $request->get('rec_num', 0);
        $rec_ids = $request->get('rec_ids', '');
        $rec_ids = $rec_ids ? implode(',', $rec_ids) : [];

        //查询库存
        $cart_info = WholesaleCart::select('goods_id', 'goods_attr_id')->where('rec_id', $rec_id)->first();
        $cart_info = $cart_info ? $cart_info->toArray() : [];
        if (empty($cart_info['goods_attr_id'])) {
            $goods_number = Wholesale::where('goods_id', $cart_info['goods_id'])->value('goods_number');
        } else {
            $set = $this->purchase->get_find_in_set(explode(',', $cart_info['goods_attr_id']));
            $goods_number = WholesaleProducts::whereRaw("goods_id = '" . $cart_info['goods_id'] . "' " . $set)->value('product_number');
        }
        $result['goods_number'] = $goods_number;

        if ($goods_number < $rec_num) {
            $result['error'] = 1;
            $result['message'] = sprintf(lang('purchase.goods_number_limit'), $goods_number);
            $rec_num = $goods_number;
        }
        WholesaleCart::where('rec_id', $rec_id)->update(['goods_number' => $rec_num]);

        // 返回商品数量、价格
        $cart_goods = $this->purchase->wholesale_cart_goods(0, $rec_ids, $user_id);
        $goods_list = array();
        foreach ($cart_goods as $key => $val) {
            foreach ($val['goods_list'] as $k => $g) {
                //处理阶梯价格
                //商品数据
                $goods_list[$g['goods_id']] = $g;
            }
        }
        $result['goods_list'] = $goods_list;
        //订单信息
        $cart_info = $this->purchase->wholesale_cart_info(0, $rec_ids, $user_id);
        $result['cart_info'] = $cart_info;
        $result['goods'] = $this->purchase->cartInfo($rec_id, $user_id);

        $this->succeed($result);
    }

    /**
     * 联系方式
     * 提交 联系方式等信息
     * 在cart页面（弹层）
     * 异步处理提交
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function info(Request $request)
    {
        $result['title'] = lang('purchase.purchase_info');
        $result['action'] = 'info';

        return $this->succeed($result);
    }

    /**
     * 求购信息列表（求购单列表）
     * 目前只做显示 ********
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function show(Request $request)
    {
        // $user_id = $this->authorization();
        // if(empty($user_id)){
        //     return $this->setStatusCode(12)->failed(lang('user.not_login'));
        // }

        $result = [];
        $result['page_title'] = lang('purchase.purchase_info');
        $result['action'] = 'show';

        $is_finished = $request->get('is_finished', -1);
        $keyword = $request->get('keyword', '');
        $page = $request->get('page', 1);
        $size = 10;

        $filter_array = [];
        $filter_array['review_status'] = 1;
        $query_array = [];
        $query_array['act'] = 'list';
        if ($is_finished != -1) {
            $query_array['is_finished'] = $is_finished;
            $filter_array['is_finished'] = $is_finished;
        }
        if ($keyword) {
            $filter_array['keyword'] = $keyword;
            $query_array['keyword'] = $keyword;
        }

        if (defined('IS_AJAX')) {
            $purchase_list = $this->purchaseService->getPurchaseList($filter_array, $size, $page);
            $this->succeed(['list' => array_values($purchase_list['purchase_list']), 'totalPage' => $purchase_list['page_count']]);
        }
        $result['is_finished'] = $is_finished;

        return $this->succeed($result);
    }

    /**
     * 求购详细信息
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function showDetail(Request $request)
    {
        $result = [];
        $result['page_title'] = lang('purchase.purchase_detail');

        $purchase_id = $request->get('id', 0);
        $purchase_info = $this->purchaseService->getPurchaseInfo($purchase_id);
        $isSeller = $this->purchase->isSeller();
        if ($isSeller == 0) {
            $purchase_info['contact_phone'] = '******';
            $purchase_info['contact_email'] = '******';
            $purchase_info['contact_name'] = '******';
        }
        $result['purchase_info'] = $purchase_info;
        $result['isseller'] = $isSeller;

        return $this->succeed($result);
    }

    protected function initialize()
    {
        //加载外部类
        $files = [
            'common',
            'time',
            'insert',
            'base',
            'ecmoban',
            'goods',
            'wholesale',
            'order',
            'clips',
        ];
        load_helper($files);

        //加载语言包
        $this->dscRepository->helpersLang('purchase');
    }
}
