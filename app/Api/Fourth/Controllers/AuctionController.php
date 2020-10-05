<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Goods;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\AuctionService;
use App\Services\Cart\CartCommonService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * 拍卖活动
 * Class AuctionController
 * @package App\Api\Fourth\Controllers
 */
class AuctionController extends Controller
{
    protected $auctionService;
    protected $timeRepository;
    protected $cartCommonService;
    protected $goodsMobileService;
    protected $goodsAttrService;
    protected $accountService;
    protected $goodsGalleryService;
    protected $dscRepository;
    protected $merchantCommonService;

    public function __construct(
        AuctionService $auctionService,
        TimeRepository $timeRepository,
        CartCommonService $cartCommonService,
        GoodsMobileService $goodsMobileService,
        GoodsAttrService $goodsAttrService,
        AccountService $accountService,
        GoodsGalleryService $goodsGalleryService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->auctionService = $auctionService;
        $this->timeRepository = $timeRepository;
        $this->cartCommonService = $cartCommonService;
        $this->goodsMobileService = $goodsMobileService;
        $this->goodsAttrService = $goodsAttrService;
        $this->accountService = $accountService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 拍卖--列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer|max:50',
        ]);

        // 返回用户ID
        $user_id = $this->authorization();

        // 接收参数
        $sort = $request->get('sort');
        $order = $request->get('order');
        $keyword = $request->get('keyword', '');
        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        $list = $this->auctionService->auctionList($user_id, $keyword, $sort, $order, $page, $size);

        return $this->succeed($list);
    }

    /**
     * 拍卖--详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        // 返回用户ID
        $user_id = $this->authorization();

        $result = [
            'goods_img' => '',         //商品相册
            'auction' => '',           //拍卖信息
            'auction_goods' => '',     //拍卖商品信息
            'auction_log' => '',       //出价记录
            'hot_goods' => '',         //推荐拍品
            'products_info' => '',      //取货品信息
            'auction_count' => ''      //出价人数
        ];

        //接收参数
        $id = $request->get('id');

        /* 取得拍卖活动信息 */
        $auction = $this->auctionService->getAuctionInfo($id);
        if (empty($auction)) {
            $result['error'] = 1;
            $result['msg'] = lang('common.no_auction');
            return $this->succeed($result);
        }

        //是否收藏
        $collect = $this->auctionService->findOne($auction['goods_id'], $user_id);
        $auction['is_collect'] = empty($collect) ? 0 : 1;

        $auction['is_winner'] = 0;
        if (isset($auction['last_bid']['bid_user']) && $auction['last_bid']['bid_user']) {
            if ($auction['status_no'] == FINISHED && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                $auction['is_winner'] = 1;
            }
        }
        $auction['price_times'] = intval($auction['current_price_int'] / $auction['amplitude'] + 1);

        // 拍卖信息
        $result['auction'] = $auction;

        //拍卖商品信息
        $goods = $this->goodsMobileService->goodsInfo($auction['goods_id']);
        if ($goods) {
            $goods['goods_img'] = $this->dscRepository->getImagePath($goods['goods_img']);
            $goods['rz_shopName'] = $this->merchantCommonService->getShopName($goods['user_id'], 1); //店铺名称
        }
        $result['auction_goods'] = $goods;

        // 店铺信息
        $seller_info = $this->auctionService->getSellerShopinfo($auction['user_id']);

        $chat = $this->dscRepository->chatQq($seller_info);
        $seller_info['kf_qq'] = $chat['kf_qq'];
        $seller_info['kf_ww'] = $chat['kf_ww'];

        $result['seller_info'] = $seller_info;

        //取货品信息
        if ($auction['product_id'] > 0) {
            $goods_specifications = $this->auctionService->get_specifications_list($auction['goods_id']);
            $good_products = $this->auctionService->getProducts($auction['goods_id'], $auction['product_id']);
            $_good_products = explode('|', $good_products[0]['goods_attr']);

            $products_info = '';
            foreach ($_good_products as $value) {
                $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
            }
            $result['products_info'] = $products_info;
        }

        // 出价记录
        $result['auction_log'] = $this->auctionService->auction_log($id);

        // 出价人数
        $result['auction_count'] = $this->auctionService->auction_log($id, 1);

        // 推荐拍品
        $result['hot_goods'] = $this->auctionService->recommend_goods('hot');

        // 商品相册
        $goodsGallery = $this->goodsGalleryService->getGalleryList(['goods_id' => $auction['goods_id']]);
        foreach ($goodsGallery as $k => $v) {
            $goodsGallery[$k] = $this->dscRepository->getImagePath($v['img_url']);
        }
        $result['goods_img'] = $goodsGallery;

        return $this->succeed($result);
    }

    /**
     * 拍卖--记录
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function log(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        //接收参数
        $id = $request->get('id');

        $result = [
            'auction_log' => '',
            'auction_count' => 0,
        ];
        // 出价记录
        $result['auction_log'] = $this->auctionService->auction_log($id);

        // 出价人数
        $result['auction_count'] = $this->auctionService->auction_log($id, 1);

        return $this->succeed($result);
    }

    /**
     * 拍卖--出价
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function bid(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'price_times' => 'required|integer',
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = [
            'error' => 0,
            'msg' => '',
        ];

        //接收参数
        $id = $request->get('id');
        $price_times = $request->get('price_times');

        /* 取得拍卖活动信息 */
        $auction = $this->auctionService->getAuctionInfo($id);

        if (empty($auction)) {
            $result['error'] = 1;
            $result['msg'] = lang('common.no_auction');
            return $this->succeed($result);
        }
        $goods_number = Goods::where('goods_id', $auction['goods_id'])->value('goods_number');

        if ($goods_number <= 0) {

            $result['error'] = 1;
            $result['msg'] = lang('common.gb_error_goods_lacking');
            return $this->succeed($result);

        }
        /* 活动是否正在进行 */
        if ($auction['status_no'] != UNDER_WAY) {
            $result['error'] = 1;
            $result['msg'] = lang('common.au_not_under_way');
            return $this->succeed($result);
        }

        /* 取得出价 */
        $bid_price = $price_times ? round(floatval($price_times + $auction['amplitude']), 2) : 0;
        if ($bid_price <= 0) {
            $result['error'] = 1;
            $result['msg'] = lang('common.au_bid_price_error');
            return $this->succeed($result);
        }

        /* 如果有一口价且出价大于等于一口价，则按一口价算 */
        $is_ok = false; // 出价是否ok
        if ($auction['end_price'] > 0) {
            if ($bid_price >= $auction['end_price']) {
                $bid_price = $auction['end_price'];
                $is_ok = true;
            }
        }

        /* 出价是否有效：区分第一次和非第一次 */
        if (!$is_ok) {
            if ($auction['bid_user_count'] == 0) {
                /* 第一次要大于等于起拍价 */
                $min_price = $auction['start_price'];
            } else {
                /* 非第一次出价要大于等于最高价加上加价幅度，但不能超过一口价 */
                $min_price = $auction['last_bid']['bid_price'] + $auction['amplitude'];
                if ($auction['end_price'] > 0) {
                    $min_price = min($min_price, $auction['end_price']);
                }
            }

            if ($bid_price < $min_price) {
                $result['error'] = 1;
                $result['msg'] = sprintf(lang('common.au_your_lowest_price'), $min_price);
                return $this->succeed($result);
            }
        }

        /* 检查联系两次拍卖人是否相同 */
        if ($auction['bid_user_count'] > 0) {
            if ($auction['last_bid']['bid_user'] == $user_id && $bid_price != $auction['end_price']) {
                $result['error'] = 1;
                $result['msg'] = lang('common.au_bid_repeat_user');
                return $this->succeed($result);
            }
        }
        $user = $this->auctionService->userInfo($user_id);

        /* 是否需要保证金 */
        if ($auction['deposit'] > 0) {
            /* 可用资金够吗 */
            if ($user['user_money'] < $auction['deposit']) {
                $result['error'] = 1;
                $result['msg'] = lang('common.au_user_money_short');
                return $this->succeed($result);
            }
            /* 如果不是第一个出价，解冻上一个用户的保证金 */
            if ($auction['bid_user_count'] > 0) {
                $info = sprintf(lang('common.au_unfreeze_deposit'), $auction['act_name']);
                $this->accountService->logAccountChange($auction['last_bid']['bid_user'], $auction['deposit'], -$auction['deposit'], 0, 0, $info, ACT_TRANSFERRED);
            }
            /* 冻结当前用户的保证金 */
            $info = sprintf(lang('common.au_freeze_deposit'), $auction['act_name']);
            $this->accountService->logAccountChange($user_id, -$auction['deposit'], $auction['deposit'], 0, 0, $info, ACT_TRANSFERRED);
        }

        /* 插入出价记录 */
        $auction_log = [
            'act_id' => $id,
            'bid_user' => $user_id,
            'bid_price' => $bid_price,
            'bid_time' => $this->timeRepository->getGmTime()
        ];

        $this->auctionService->addAuctionLog($auction_log);

        /* 出价是否等于一口价 */
        if ($bid_price == $auction['end_price']) {
            /* 结束拍卖活动 */
            $this->auctionService->updateGoodsActivity($id);
        }

        $result['error'] = 0;
        $result['msg'] = lang('common.button_bid_succeed');

        return $this->succeed($result);
    }

    /**
     * 拍卖--出价
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function buy(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //接收参数
        $id = $request->get('id');

        $result = [
            'error' => 0,
            'msg' => '',
        ];

        /* 取得拍卖活动信息 */
        $auction = $this->auctionService->getAuctionInfo($id);
        if (empty($auction)) {
            $result['error'] = 1;
            $result['msg'] = lang('common.no_auction');
            return $this->succeed($result);
        }
        /* 查询：活动是否已结束 */
        if ($auction['status_no'] != FINISHED) {
            $result['error'] = 1;
            $result['msg'] = lang('common.au_not_finished');
            return $this->succeed($result);
        }

        /* 查询：有人出价吗 */
        if ($auction['bid_user_count'] <= 0) {
            $result['error'] = 1;
            $result['msg'] = lang('common.au_no_bid');
            return $this->succeed($result);
        }

        /* 查询：是否已经有订单 */
        if ($auction['order_count'] > 0) {
            $result['error'] = 1;
            $result['msg'] = lang('common.au_order_placed');
            return $this->succeed($result);
        }

        /* 查询：最后出价的是该用户吗 */
        if ($auction['last_bid']['bid_user'] != $user_id) {
            $result['error'] = 1;
            $result['msg'] = lang('common.au_final_bid_not_you');
            return $this->succeed($result);
        }

        /* 查询：取得商品信息 */
        $goods = $this->goodsMobileService->goodsInfo($auction['goods_id']);

        /* 查询：处理规格属性 */
        $attrNameStr = '';
        $goods_attr_id = '';
        if ($auction['product_id'] > 0) {
            $good_products = $this->auctionService->getProducts($auction['goods_id'], $auction['product_id']);
            $goods_attr_id = str_replace('|', ',', $good_products[0]['goods_attr']);

            $attr_id = explode('|', $good_products[0]['goods_attr']);

            // 商品属性文字输出
            $attrName = $this->goodsAttrService->getAttrNameById($attr_id);

            foreach ($attrName as $v) {
                $attrNameStr .= $v['attr_name'] . ':' . $v['attr_value'] . " \n";
            }
        } else {
            $auction['product_id'] = 0;
        }

        // 更新：清空当前会员购物车中拍卖商品
        $this->cartCommonService->clearCart($user_id, CART_AUCTION_GOODS);

        /* 加入购物车 */
        $cart = [
            'user_id' => $user_id,
            'goods_id' => $auction['goods_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'product_id' => $auction['product_id'],
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_price' => $auction['last_bid']['bid_price'],
            'goods_number' => 1,
            'goods_attr' => $attrNameStr,
            'goods_attr_id' => $goods_attr_id,
            'warehouse_id' => 0,
            'area_id' => 0,
            'add_time' => $this->timeRepository->getGmTime(),
            'is_real' => $goods['is_real'],
            'ru_id' => $goods['user_id'],
            'extension_code' => addslashes($goods['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_AUCTION_GOODS,
            'is_gift' => 0,
            'is_checked' => '1',
        ];

        $rec_id = $this->auctionService->addGoodsToCart($cart);

        if ($rec_id) {
            $result['flow_type'] = CART_AUCTION_GOODS;
            $result['extension_code'] = 'auction';
            $result['extension_id'] = $id;
            $result['direct_shopping'] = 2;
        }

        return $this->succeed($result);
    }

    /**
     * 参与拍卖列表
     * @param Request $request
     * @return JsonResponse
     */
    public function auctionList(Request $request)
    {
        $page = $request->input('page', 0);
        $size = $request->input('size', 0);
        $auction = $request->input('auction', '');
        $auction = dsc_decode($auction);
        $user_id = $this->authorization();

        //获取会员竞拍的全部拍卖
        $count['all_auction'] = $this->auctionService->getAllAuction($user_id);
        //获取进行中的拍卖
        $count['is_going'] = $this->auctionService->getAllAuction($user_id, 1);
        //获取结束的拍卖
        $count['is_finished'] = $this->auctionService->getAllAuction($user_id, 2);
        $result['count'] = $count;

        //获取全部拍卖列表
        $result['list'] = $this->auctionService->getAuctionBidGoodsList($user_id, $page, $auction, $size);

        return $this->succeed($result);
    }
}
