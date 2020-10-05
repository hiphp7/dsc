<?php

use App\Libraries\Pager;
use App\Models\Article;
use App\Models\BookingGoods;
use App\Models\CollectBrand;
use App\Models\CollectStore;
use App\Models\Comment;
use App\Models\Feedback;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\MerchantsShopInformation;
use App\Models\OrderGoods;
use App\Models\PayLog;
use App\Models\Payment;
use App\Models\SellerShopinfo;
use App\Models\Tag;
use App\Models\UserAccount;
use App\Models\UserAccountFields;
use App\Models\UserAddress;
use App\Models\UserBonus;
use App\Models\Users;
use App\Models\ValueCard;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\AuctionService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreService;

/**
 * 获取指定用户的收藏品牌列表
 *
 * @param $user_id
 * @param $record_count
 * @param $page
 * @param $pageFunc
 * @param int $size
 * @param int $warehouse_id
 * @param int $area_id
 * @param int $area_city
 * @return array
 */
function get_collection_brands($user_id, $record_count, $page, $pageFunc, $size = 5, $warehouse_id = 0, $area_id = 0, $area_city = 0)
{

    $pagerParams = [
        'total' => $record_count,
        'listRows' => $size,
        'page' => $page,
        'funName' => $pageFunc,
        'pageType' => 1
    ];
    $collection = new Pager($pagerParams);
    $pager = $collection->fpage([0, 4, 5, 6, 9]);

    $res = CollectBrand::where('user_id', $user_id)
        ->whereHas('getBrand');

    $res = $res->with([
        'getBrand' => function ($query) {
            $query->select('brand_id', 'brand_name', 'brand_logo');
        }
    ]);

    $res = $res->withCount([
        'getBrand as collect_count'
    ]);

    $res = $res->orderBy('rec_id', 'desc');

    $start = ($page - 1) * $size;
    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $brand_list = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_brand'] ? array_merge($row, $row['get_brand']) : $row;

            $brand_list[$row['rec_id']]['rec_id'] = $row['rec_id'];
            $brand_list[$row['rec_id']]['brand_id'] = $row['brand_id'];
            $brand_list[$row['rec_id']]['brand_name'] = $row['brand_name'];
            $brand_list[$row['rec_id']]['url'] = app(DscRepository::class)->buildUri('brandn', ['bid' => $row['brand_id'], 'act' => 'index'], $row['brand_name']);
            $brand_list[$row['rec_id']]['brand_logo'] = get_image_path('data/brandlogo/' . $row['brand_logo']);
            $brand_list[$row['rec_id']]['add_time'] = local_date("Y-m-d H:i:s", $row['add_time']);
            $brand_list[$row['rec_id']]['ru_id'] = $row['ru_id'];
            $brand_list[$row['rec_id']]['collect_count'] = $row['collect_count'];
            $brand_list[$row['rec_id']]['is_collect'] = $brand_list[$row['rec_id']]['collect_count'];

            // 品牌商品
            $brand_id = $row['brand_id'];

            $self = empty($row['ru_id']) ? 1 : 0;
            $cat = 0;
            $goods_size = 10;
            $goods_page = 1;
            $sort = 'sales_volume';
            $order = 'DESC';

            $brand_list[$row['rec_id']]['brand_goods'] = brand_get_goods($brand_id, $cat, $goods_size, $goods_page, $sort, $order, $warehouse_id, $area_id, $area_city, '', 0, 0, $self);
        }
    }

    $arr = ['brand_list' => $brand_list, 'record_count' => $record_count, 'pager' => $pager, 'size' => $size];

    return $arr;
}

/**
 * 获取品牌商品
 *
 * @param $brand_id
 * @param $cate
 * @param $size
 * @param $page
 * @param $sort
 * @param $order
 * @param int $warehouse_id
 * @param int $area_id
 * @param int $area_city
 * @param string $ship
 * @param int $price_min
 * @param int $price_max
 * @param int $self
 * @return array
 */
function brand_get_goods($brand_id, $cate, $size, $page, $sort, $order, $warehouse_id = 0, $area_id = 0, $area_city = 0, $ship = '', $price_min = 0, $price_max = 0, $self = 0)
{
    /* 获得商品列表 */
    $res = Goods::where('is_on_sale', 1)
        ->where('is_alone_sale', 1)
        ->where('is_delete', 0)
        ->where('brand_id', $brand_id);

    if ($cate > 0) {
        $children = app(CategoryService::class)->getCatListChildren($cate);

        $res = $res->whereIn('cat_id', $children);
    }

    $res = app(DscRepository::class)->getAreaLinkGoods($res, $area_id, $area_city);

    if ($GLOBALS['_CFG']['review_goods'] == 1) {
        $res = $res->where('review_status', '>', 2);
    }

    if ($ship == 1) {
        $res = $res->where('is_shipping', 1);
    }

    if ($self == 1) {
        $res = $res->where('user_id', 0);
    }

    if ($price_min) {
        $res = $res->where('shop_price', '>=', $price_min);
    }

    if ($price_max) {
        $res = $res->where('shop_price', '<=', $price_max);
    }

    $where = [
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype'],
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city
    ];

    $user_rank = session('user_rank');
    $res = $res->with([
        'getMemberPrice' => function ($query) use ($user_rank) {
            $query->where('user_rank', $user_rank);
        },
        'getWarehouseGoods' => function ($query) use ($where) {
            $query->where('region_id', $where['warehouse_id']);
        },
        'getWarehouseAreaGoods' => function ($query) use ($where) {
            $query = $query->where('region_id', $where['area_id']);

            if ($where['area_pricetype'] == 1) {
                $query->where('city_id', $where['area_city']);
            }
        }
    ]);

    $res = $res->orderBy($sort, $order);

    $start = ($page - 1) * $size;
    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $row) {
            $price = [
                'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
            ];

            $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount'), $row);

            $row['shop_price'] = $price['shop_price'];
            $row['promote_price'] = $price['promote_price'];
            $row['goods_number'] = $price['goods_number'];

            $arr[$row['goods_id']] = $row;

            if ($row['promote_price'] > 0) {
                $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
            if (isset($GLOBALS['display']) == 'grid') {
                $arr[$row['goods_id']]['goods_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            } else {
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
            }

            $arr[$row['goods_id']]['sales_volume'] = $row['sales_volume'];
            $arr[$row['goods_id']]['is_promote'] = $row['is_promote'];
            $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
            $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
            $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $arr[$row['goods_id']]['goods_brief'] = $row['goods_brief'];
            $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_thumb']);
            $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_img']);
            $arr[$row['goods_id']]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

            $basic_info = app(StoreService::class)->getShopInfo($row['user_id']);

            $chat = app(DscRepository::class)->chatQq($basic_info);
            $arr[$row['goods_id']]['kf_type'] = $chat['kf_type'];
            $arr[$row['goods_id']]['kf_ww'] = $chat['kf_ww'];
            $arr[$row['goods_id']]['kf_qq'] = $chat['kf_qq'];

            $arr[$row['goods_id']]['rz_shopName'] = app(MerchantCommonService::class)->getShopName($row['user_id'], 1); //店铺名称

            $build_uri = [
                'urid' => $row['user_id'],
                'append' => $arr[$row['goods_id']]['rz_shopName']
            ];

            $domain_url = app(MerchantCommonService::class)->getSellerDomainUrl($row['user_id'], $build_uri);
            $arr[$row['goods_id']]['store_url'] = $domain_url['domain_name'];

            $arr[$row['goods_id']]['review_count'] = Comment::where('id_value', $row['goods_id'])
                ->where('status', 1)
                ->where('parent_id', 0)
                ->count();
        }
    }

    return $arr;
}

/**
 * 获取指定用户的收藏店铺列表
 *
 * @param $user_id
 * @param $record_count
 * @param $page
 * @param $pageFunc
 * @param int $size
 * @param int $warehouse_id
 * @param int $area_id
 * @param int $area_city
 * @return array
 */
function get_collection_store($user_id, $record_count, $page, $pageFunc, $size = 5, $warehouse_id = 0, $area_id = 0, $area_city = 0)
{

    $pagerParams = [
        'total' => $record_count,
        'listRows' => $size,
        'page' => $page,
        'funName' => $pageFunc,
        'pageType' => 1
    ];
    $collection = new Pager($pagerParams);
    $pager = $collection->fpage([0, 4, 5, 6, 9]);

    $res = CollectStore::select('ru_id', 'add_time')
        ->where('user_id', $user_id)
        ->whereHas('getSellerShopinfo');

    $res = $res->with('getSellerShopinfo');

    $res = $res->orderBy('rec_id', 'desc');

    $start = ($page - 1) * $size;
    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $store_list = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_seller_shopinfo'] ? array_merge($row, $row['get_seller_shopinfo']) : $row;

            $store_list[$key]['shop_id'] = isset($row['shop_id']) ? $row['shop_id'] : 0;
            $store_list[$key]['store_name'] = app(MerchantCommonService::class)->getShopName($row['ru_id'], 1); //店铺名称
            $store_list[$key]['shop_logo'] = str_replace('../', '', $row['shop_logo']);
            $store_list[$key]['count_store'] = CollectStore::where('ru_id', $row['ru_id'])->count();
            $store_list[$key]['add_time'] = local_date("Y-m-d", $row['add_time']);
            $store_list[$key]['kf_type'] = $row['kf_type'];
            $store_list[$key]['kf_tel'] = $row['kf_tel'];

            //IM or 客服
            if ($GLOBALS['_CFG']['customer_service'] == 0) {
                $ru_id = 0;
            } else {
                $ru_id = $row['ru_id'];
            }

            $is_IM = MerchantsShopInformation::where('user_id', $row['ru_id'])->value('is_IM');
            $store_list[$key]['is_IM'] = $is_IM; //平台是否允许商家使用"在线客服";

            if ($ru_id == 0) {
                //判断平台是否开启了IM在线客服
                $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                if ($kf_im_switch) {
                    $store_list[$key]['is_dsc'] = true;
                } else {
                    $store_list[$key]['is_dsc'] = false;
                }
            } else {
                $store_list[$key]['is_dsc'] = false;
            }

            $chat = app(DscRepository::class)->chatQq($row);
            $store_list[$key]['kf_qq'] = $chat['kf_qq'];
            $store_list[$key]['kf_ww'] = $chat['kf_ww'];

            $store_list[$key]['ru_id'] = $row['ru_id'];
            $store_list[$key]['brand_thumb'] = get_image_path($row['brand_thumb']);

            $build_uri = [
                'urid' => $row['ru_id'],
                'append' => $store_list[$key]['store_name']
            ];

            $domain_url = app(MerchantCommonService::class)->getSellerDomainUrl($row['ru_id'], $build_uri);
            $store_list[$key]['url'] = $domain_url['domain_name'];
            $store_list[$key]['merch_cmt'] = app(CommentService::class)->getMerchantsGoodsComment($row['ru_id']); //商家所有商品评分类型汇总
            $store_list[$key]['hot_goods'] = get_user_store_goods_list($row['ru_id'], $warehouse_id, $area_id, $area_city, 'store_hot');
            $store_list[$key]['new_goods'] = get_user_store_goods_list($row['ru_id'], $warehouse_id, $area_id, $area_city, 'store_new');
            $store_list[$key]['common_goods'] = get_user_store_goods_list($row['ru_id'], $warehouse_id, $area_id, $area_city);
            $store_list[$key]['new_goods_count'] = count($store_list[$key]['new_goods']);
        }
    }


    $arr = ['store_list' => $store_list, 'record_count' => $record_count, 'pager' => $pager, 'size' => $size];

    return $arr;
}

function get_user_store_goods_list($user_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $type = '', $sort = 'last_update', $order = 'DESC', $size = 10)
{
    $res = Goods::where('is_on_sale', 1)
        ->where('is_alone_sale', 1)
        ->where('is_delete', 0)
        ->where('user_id', $user_id);

    if ($type == 'store_hot') {
        $res = $res->where('store_hot', 1);
    } elseif ($type == 'store_new') {
        $res = $res->where('store_new', 1);
    }

    $res = app(DscRepository::class)->getAreaLinkGoods($res, $area_id, $area_city);

    if ($GLOBALS['_CFG']['review_goods'] == 1) {
        $res = $res->where('review_status', '>', 2);
    }

    $where = [
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype'],
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city
    ];

    $user_rank = session('user_rank');
    $res = $res->with([
        'getMemberPrice' => function ($query) use ($user_rank) {
            $query->where('user_rank', $user_rank);
        },
        'getWarehouseGoods' => function ($query) use ($where) {
            $query->where('region_id', $where['warehouse_id']);
        },
        'getWarehouseAreaGoods' => function ($query) use ($where) {
            $query = $query->where('region_id', $where['area_id']);

            if ($where['area_pricetype'] == 1) {
                $query->where('city_id', $where['area_city']);
            }
        }
    ]);

    $res = $res->orderBy($sort, $order);

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $goods_list = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $price = [
                'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
            ];

            $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount'), $row);

            $row['shop_price'] = $price['shop_price'];
            $row['promote_price'] = $price['promote_price'];
            $row['goods_number'] = $price['goods_number'];

            $goods_list[$row['goods_id']] = $row;

            if ($row['promote_price'] > 0) {
                $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $goods_list[$row['goods_id']]['goods_id'] = $row['goods_id'];
            $goods_list[$row['goods_id']]['goods_name'] = $row['goods_name'];
            $goods_list[$row['goods_id']]['market_price'] = price_format($row['market_price']);
            $goods_list[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
            $goods_list[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $goods_list[$row['goods_id']]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $goods_list[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_thumb']);
        }
    }

    return $goods_list;
}

/**
 *  查看此商品是否已进行过缺货登记
 *
 * @access  public
 * @param int $user_id 用户ID
 * @param int $goods_id 商品ID
 *
 * @return  int
 */
function get_booking_rec($user_id, $goods_id)
{
    $count = BookingGoods::where('user_id', $user_id)
        ->where('goods_id', $goods_id)
        ->where('is_dispose', 0);

    $count = $count->count();

    return $count;
}

/**
 *  获取指定用户的留言
 *
 * @access  public
 * @param int $user_id 用户ID
 * @param int $user_name 用户名
 * @param int $num 列表最大数量
 * @param int $start 列表其实位置
 * @return  array   $msg            留言及回复列表
 * @return  string  $order_id       订单ID
 */
function get_message_list($user_id, $size = 0, $start = 0, $order_id = 0, $is_order = 0)
{
    /* 获取留言数据 */
    $res = Feedback::where('parent_id', 0)
        ->where('msg_status', 0)
        ->where('user_id', $user_id);

    if ($order_id) {
        $res = $res->where('order_id', $order_id);
        $res = $res->whereHas('getOrder');

        $res = $res->with(['getOrder' => function ($query) {
            $query->select('order_id', 'order_sn');
        }]);
    } else {
        if ($is_order) {
            $res = $res->where('order_id', '>', 0);
            $res = $res->whereHas('getOrder', function ($query) {
                $query->where('main_count', 0);
            });

            $res = $res->with([
                'getOrder' => function ($query) {
                    $query->select('order_id', 'order_sn');
                }]);
        } else {
            $res = $res->where('order_id', 0);
        }
    }

    $res = $res->orderBy('msg_time', 'desc');

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $msg = [];
    if ($res) {
        foreach ($res as $rows) {
            $rows['get_order'] = (isset($rows['get_order']) && !empty($rows['get_order'])) ? $rows['get_order'] : [];
            $rows = app(BaseRepository::class)->getArrayMerge($rows, $rows['get_order']);

            $reply = Feedback::where('parent_id', $rows['msg_id']);
            $reply = app(BaseRepository::class)->getToArrayFirst($reply);

            if ($reply) {
                $msg[$rows['msg_id']]['re_user_name'] = $reply['user_name'];
                $msg[$rows['msg_id']]['re_user_email'] = $reply['user_email'];
                $msg[$rows['msg_id']]['re_msg_time'] = local_date($GLOBALS['_CFG']['time_format'], $reply['msg_time']);
                $msg[$rows['msg_id']]['re_msg_content'] = nl2br(htmlspecialchars($reply['msg_content']));
            }

            $msg[$rows['msg_id']]['msg_content'] = nl2br(htmlspecialchars($rows['msg_content']));
            $msg[$rows['msg_id']]['msg_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['msg_time']);
            $msg[$rows['msg_id']]['msg_type'] = nl2br(htmlspecialchars($rows['msg_type']));
            $msg[$rows['msg_id']]['msg_title'] = nl2br(htmlspecialchars($rows['msg_title']));
            //判断上传的文件类型
            $message_type = pathinfo($rows['message_img'], PATHINFO_EXTENSION);
            if (in_array($message_type, ['gif', 'jpg', 'png'])) {
                $msg[$rows['msg_id']]['message_type'] = 1;
            }
            $rows['message_img'] = isset($rows['message_img']) && !empty($rows['message_img']) ? DATA_DIR . "/feedbackimg/" . $rows['message_img'] : '';
            $msg[$rows['msg_id']]['message_img'] = !empty($rows['message_img']) ? get_image_path($rows['message_img']) : '';
            $msg[$rows['msg_id']]['order_id'] = $rows['order_id'];
            $msg[$rows['msg_id']]['order_sn'] = $rows['order_sn'] ?? '';
        }
    }

    return $msg;
}

/**
 *  添加留言函数
 *
 * @access  public
 * @param array $message
 *
 * @return  boolen      $bool
 */
function add_message($message)
{
    $upload_size_limit = $GLOBALS['_CFG']['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $GLOBALS['_CFG']['upload_size_limit'];
    $status = 1 - $GLOBALS['_CFG']['message_check'];

    $last_char = strtolower($upload_size_limit{strlen($upload_size_limit) - 1});

    switch ($last_char) {
        case 'm':
            $upload_size_limit *= 1024 * 1024;
            break;
        case 'k':
            $upload_size_limit *= 1024;
            break;
    }

    if ($message['upload']) {
        if ($_FILES['message_img']['size'] / 1024 > $upload_size_limit) {
            $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['upload_file_limit'], $upload_size_limit));
            return false;
        }
        $img_name = upload_file($_FILES['message_img'], 'feedbackimg');
        if ($img_name === false) {
            return false;
        }
        $oss_img_name = DATA_DIR . '/' . 'feedbackimg/' . $img_name;
        app(DscRepository::class)->getOssAddFile([$oss_img_name]);
    } else {
        $img_name = '';
    }

    if (empty($message['msg_title'])) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['msg_title_empty']);

        return false;
    }
    $message['msg_area'] = isset($message['msg_area']) ? intval($message['msg_area']) : 0;

    $other = [
        'parent_id' => 0,
        'user_id' => $message['user_id'],
        'user_name' => $message['user_name'],
        'user_email' => $message['user_email'],
        'msg_title' => $message['msg_title'],
        'msg_type' => $message['msg_type'],
        'msg_status' => $status,
        'msg_content' => $message['msg_content'],
        'msg_time' => gmtime(),
        'message_img' => $img_name,
        'order_id' => $message['order_id'],
        'msg_area' => $message['msg_area']
    ];

    $msg_id = Feedback::insertGetId($other);

    return $msg_id;
}

/**
 *  获取用户的tags
 *
 * @access  public
 * @param int $user_id 用户ID
 *
 * @return array        $arr            tags列表
 */
function get_user_tags($user_id = 0)
{
    if (empty($user_id)) {
        $GLOBALS['error_no'] = 1;

        return false;
    }

    $tags = get_tags(0, $user_id);

    if (!empty($tags)) {
        color_tag($tags);
    }

    return $tags;
}

/**
 *  验证性的删除某个tag
 *
 * @access  public
 * @param int $tag_words tag的ID
 * @param int $user_id 用户的ID
 *
 * @return  boolen      bool
 */
function delete_tag($tag_words, $user_id)
{
    return Tag::where('tag_words', $tag_words)->where('user_id', $user_id)->delete();
}

/**
 *  获取某用户的缺货登记列表
 *
 * @access  public
 * @param int $user_id 用户ID
 * @param int $num 列表最大数量
 * @param int $start 列表其实位置
 *
 * @return  array   $booking
 */
function get_booking_list($user_id, $num, $start)
{
    $res = BookingGoods::where('user_id', $user_id);

    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_name', 'goods_thumb');
        }
    ]);

    $res = $res->orderBy('booking_time', 'desc');

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($num > 0) {
        $res = $res->take($num);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $booking = [];
    if ($res) {
        foreach ($res as $row) {
            $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;

            if (empty($row['dispose_note'])) {
                $row['dispose_note'] = 'N/A';
            }

            $booking[] = ['rec_id' => $row['rec_id'],
                'goods_name' => $row['goods_name'],
                'goods_number' => $row['goods_number'],
                'goods_thumb' => get_image_path($row['goods_thumb']),
                'booking_time' => local_date($GLOBALS['_CFG']['date_format'], $row['booking_time']),
                'dispose_note' => $row['dispose_note'],
                'url' => app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name'])];
        }
    }

    return $booking;
}

/**
 *  获取某用户的缺货登记列表
 *
 * @access  public
 * @param int $goods_id 商品ID
 *
 * @return  array   $info
 */
function get_goodsinfo($goods_id)
{
    $user_id = session('user_id', 0);

    $info = [];

    $goods_name = Goods::where('goods_id', $goods_id)->value('goods_name');
    $info['goods_name'] = $goods_name;
    $info['goods_number'] = 1;
    $info['id'] = $goods_id;

    if (!empty($user_id)) {
        $row = UserAddress::whereHas('getUsers', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        });

        $row = app(BaseRepository::class)->getToArrayFirst($row);

        $info['consignee'] = $row && !empty($row['consignee']) ? $row['consignee'] : '';
        $info['email'] = $row && !empty($row['email']) ? $row['email'] : '';
        $info['tel'] = $row && !empty($row['mobile']) ? $row['mobile'] : ($row && !empty($row['tel']) ? $row['tel'] : '');
    }

    return $info;
}

/**
 *  验证删除某个收藏商品
 *
 * @access  public
 * @param int $booking_id 缺货登记的ID
 * @param int $user_id 会员的ID
 * @return  boolen      $bool
 */
function delete_booking($booking_id, $user_id)
{
    return BookingGoods::where('rec_id', $booking_id)->where('user_id', $user_id)->delete();
}

/**
 * 添加缺货登记记录到数据表
 * @access  public
 * @param array $booking
 *
 * @return void
 */
function add_booking($booking)
{
    $other = [
        'user_id' => session('user_id', 0),
        'email' => $booking['email'],
        'link_man' => $booking['linkman'],
        'tel' => $booking['tel'],
        'goods_id' => $booking['goods_id'],
        'goods_desc' => $booking['desc'],
        'goods_number' => $booking['goods_amount'],
        'booking_time' => gmtime()
    ];
    $rec_id = BookingGoods::insertGetId($other);

    return $rec_id;
}

/**
 *
 *
 * @access  public
 * @param array $surplus 会员余额信息
 * @param string $amount 余额
 *
 * @return  int
 */

/**
 * 插入会员账目明细
 *
 * @param array $surplus 会员余额信息
 * @param int $amount 余额
 * @param int $account_time 传值时间
 * @return mixed
 */
function insert_user_account($surplus = [], $amount = 0, $account_time = 0)
{
    $other = [
        'user_id' => isset($surplus['user_id']) ? $surplus['user_id'] : 0,
        'amount' => $amount,
        'add_time' => $account_time ? $account_time : gmtime(),
        'user_note' => isset($surplus['user_note']) ? $surplus['user_note'] : '',
        'process_type' => isset($surplus['process_type']) ? $surplus['process_type'] : 0,
        'payment' => isset($surplus['payment']) ? $surplus['payment'] : '',
        'pay_id' => $surplus['payment_id'] ?? 0,
        'deposit_fee' => isset($surplus['deposit_fee']) ? $surplus['deposit_fee'] : 0
    ];
    $id = UserAccount::insertGetId($other);

    return $id;
}


/**
 * 插入会员账目明细扩展字段by wang
 *
 * @access  public
 * @param array $user_account_fields 扩展字段数组
 * @return  int
 */
function insert_user_account_fields($user_account_fields)
{
    $other = [
        'user_id' => $user_account_fields['user_id'],
        'account_id' => $user_account_fields['account_id'],
        'bank_number' => $user_account_fields['bank_number'],
        'real_name' => $user_account_fields['real_name']
    ];
    $id = UserAccountFields::insertGetId($other);

    return $id;
}

/**
 * 更新会员账目明细
 *
 * @access  public
 * @param array $surplus 会员余额信息
 *
 * @return  int
 */
function update_user_account($surplus = [])
{
    $other = [
        'amount' => $surplus['amount'],
        'user_note' => $surplus['user_note'],
        'payment' => $surplus['payment'],
        'pay_id' => $surplus['payment_id'] ?? 0
    ];
    UserAccount::where('id', $surplus['rec_id'])->update($other);

    return $surplus['rec_id'];
}

/**
 * 将支付LOG插入数据表
 *
 * @access  public
 * @param integer $id 订单编号
 * @param float $amount 订单金额
 * @param integer $type 支付类型
 * @param integer $is_paid 是否已支付
 *
 * @return  int
 */
function insert_pay_log($id, $amount, $type = PAY_SURPLUS, $is_paid = 0)
{
    if ($id) {
        $pay_log = [
            'order_id' => $id,
            'order_amount' => $amount,
            'order_type' => $type,
            'is_paid' => $is_paid
        ];

        $log_id = PayLog::insertGetId($pay_log);
    } else {
        $log_id = 0;
    }

    return $log_id;
}

/**
 * 取得上次未支付的pay_lig_id
 *
 * @access  public
 * @param array $surplus_id 余额记录的ID
 * @param array $pay_type 支付的类型：预付款/订单支付
 *
 * @return  int
 */
function get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS)
{
    $log_id = PayLog::where('order_id', $surplus_id)->where('order_type', $pay_type)->where('is_paid', 0)->value('log_id');

    return $log_id;
}

/**
 * 根据ID获取当前余额操作信息
 *
 * @param int $surplus_id 会员余额的ID
 * @param int $user_id 会员ID
 * @return mixed
 */
function get_surplus_info($surplus_id = 0, $user_id = 0)
{
    $row = UserAccount::where('id', $surplus_id)
        ->where('user_id', $user_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    return $row;
}

/**
 * 取得已安装的支付方式(其中不包括线下支付的)
 * @param bool $include_balance 是否包含余额支付（冲值时不应包括）
 * @return  array   已安装的配送方式列表
 */
function get_online_payment_list($include_balance = true)
{
    $modules = Payment::where('enabled', 1)->where('is_cod', '<>', 1);

    if (!$include_balance) {
        //不包含"余额支付","白条支付","在线支付按钮"(充值时不应该包含);
        $modules = $modules->whereNotIn('pay_code', ['balance', 'chunsejinrong', 'onlinepay']);
    }

    $modules = app(BaseRepository::class)->getToArrayGet($modules);

    $arr = [];

    foreach ($modules as $key => $row) {
        $pay_code = substr($row['pay_code'], 0, 4);
        if ($pay_code != 'pay_') {
            $arr[$key]['pay_id'] = $row['pay_id'];
            $arr[$key]['pay_code'] = $row['pay_code'];
            $arr[$key]['pay_name'] = $row['pay_name'];
            $arr[$key]['pay_fee'] = $row['pay_fee'];
            $arr[$key]['pay_desc'] = $row['pay_desc'];
        }
    }

    return $arr;
}


/**
 * 查询会员余额的操作记录
 *
 * @access  public
 * @param int $user_id 会员ID
 * @param int $size 每页显示数量
 * @param int $start 开始显示的条数
 * @return  array
 */
function get_account_log($user_id, $size, $start)
{
    $res = UserAccount::where('user_id', $user_id)
        ->whereIn('process_type', [SURPLUS_SAVE, SURPLUS_RETURN]);

    $res = $res->orderBy('add_time', 'desc');

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $account_log = [];
    if ($res) {
        foreach ($res as $rows) {
            $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
            $rows['admin_note'] = nl2br(htmlspecialchars($rows['admin_note']));
            $rows['short_admin_note'] = ($rows['admin_note'] > '') ? app(DscRepository::class)->subStr($rows['admin_note'], 30) : 'N/A';
            $rows['user_note'] = nl2br(htmlspecialchars($rows['user_note']));
            $rows['short_user_note'] = ($rows['user_note'] > '') ? app(DscRepository::class)->subStr($rows['user_note'], 30) : 'N/A';
            $rows['pay_status'] = ($rows['is_paid'] == 0) ? $GLOBALS['_LANG']['un_confirmed'] : $GLOBALS['_LANG']['is_confirmed'];
            $rows['amount'] = price_format(abs($rows['amount']), false);

            /* 会员的操作类型： 冲值，提现 */
            if ($rows['process_type'] == 0) {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_0'];
            } else {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_1'];
            }

            /* 支付方式的ID */
            if ($rows['pay_id'] > 0) {
                $pid = $rows['pay_id'];
            } else {
                $pid = Payment::where('pay_name', $rows['payment'])->where('enabled')->value('pay_id');
            }

            /* 如果是预付款而且还没有付款, 允许付款 */
            if (($rows['is_paid'] == 0) && ($rows['process_type'] == 0)) {
                $rows['handle'] = '<a href="user.php?act=pay&id=' . $rows['id'] . '&pid=' . $pid . '" class="ftx-01">' . $GLOBALS['_LANG']['pay'] . '</a>';
            }

            $account_log[] = $rows;
        }
    }

    return $account_log;
}

/**
 * 查询会员余额的操作单条记录
 *
 * @access  public
 * @param int $id ID
 * @return  array
 */
function get_user_account_log($id, $user_id = 0)
{
    $user_id = empty($user_id) ? session('user_id', 0) : $user_id;

    $rows = UserAccount::where('id', $id)
        ->where('user_id', $user_id);

    $rows = app(BaseRepository::class)->getToArrayFirst($rows);

    if ($rows) {
        $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
        $rows['admin_note'] = nl2br(htmlspecialchars($rows['admin_note']));
        $rows['short_admin_note'] = ($rows['admin_note'] > '') ? app(DscRepository::class)->subStr($rows['admin_note'], 30) : 'N/A';
        $rows['user_note'] = nl2br(htmlspecialchars($rows['user_note']));
        $rows['short_user_note'] = ($rows['user_note'] > '') ? app(DscRepository::class)->subStr($rows['user_note'], 30) : 'N/A';
        $rows['pay_status'] = ($rows['is_paid'] == 0) ? $GLOBALS['_LANG']['un_confirmed'] : $GLOBALS['_LANG']['is_confirmed'];
        $rows['amount'] = price_format(abs($rows['amount']), false);
        $rows['complaint_imges'] = $rows['complaint_imges'] ? get_image_path($rows['complaint_imges']) : '';

        /* 会员的操作类型： 冲值，提现 */
        if ($rows['process_type'] == 0) {
            $rows['type'] = $GLOBALS['_LANG']['surplus_type_0'];
        } else {
            $rows['type'] = $GLOBALS['_LANG']['surplus_type_1'];
        }

        /* 支付方式的ID */
        if ($rows['pay_id'] > 0) {
            $pid = $rows['pay_id'];
        } else {
            $pid = Payment::where('pay_name', $rows['payment'])->where('enabled')->value('pay_id');
        }

        /* 如果是预付款而且还没有付款, 允许付款 */
        if (($rows['is_paid'] == 0) && ($rows['process_type'] == 0)) {
            $rows['handle'] = '<a href="user.php?act=pay&id=' . $rows['id'] . '&pid=' . $pid . '" class="ftx-01">' . $GLOBALS['_LANG']['pay'] . '</a>';
        }
    }

    return $rows;
}

/**
 *  删除未确认的会员帐目信息
 *
 * @access  public
 * @param int $rec_id 会员余额记录的ID
 * @param int $user_id 会员的ID
 * @return  boolen
 */
function del_user_account($rec_id, $user_id)
{
    return UserAccount::where('is_paid', 0)->where('id', $rec_id)->where('user_id', $user_id)->delete();
}

/**
 *  删除未确认的会员帐目的扩展信息
 *
 * @access  public
 * @param int $acount_id 会员余额记录的ID
 * @param int $user_id 会员的ID
 * @return  boolen
 */
function del_user_account_fields($acount_id, $user_id)
{
    return UserAccountFields::where('account_id', $acount_id)->where('user_id', $user_id)->delete();
}

/**
 * 查询会员余额的数量
 * @access  public
 * @param int $user_id 会员ID
 * @return  int
 */
function get_user_surplus($user_id)
{
    $user_money = Users::where('user_id', $user_id)->value('user_money');

    return $user_money;
}

/**
 * 添加商品标签
 *
 * @access  public
 * @param integer $id
 * @param string $tag
 * @return  void
 */
function add_tag($id, $tag)
{
    if (empty($tag)) {
        return;
    }

    $arr = explode(',', $tag);

    foreach ($arr as $val) {
        /* 检查是否重复 */
        $count = Tag::where('user_id', session('user_id'))->where('goods_id', $id)->where('tag_words', $val)->count();

        if ($count == 0) {
            $other = [
                'user_id' => session('user_id'),
                'goods_id' => $id,
                'tag_words' => $val
            ];
            Tag::insert($other);
        }
    }
}

/**
 * 标签着色
 *
 * @access   public
 * @param array
 * @return   none
 * @author   Xuan Yan
 *
 */
function color_tag(&$tags)
{
    $tagmark = [
        ['color' => '#666666', 'size' => '0.8em', 'ifbold' => 1],
        ['color' => '#333333', 'size' => '0.9em', 'ifbold' => 0],
        ['color' => '#006699', 'size' => '1.0em', 'ifbold' => 1],
        ['color' => '#CC9900', 'size' => '1.1em', 'ifbold' => 0],
        ['color' => '#666633', 'size' => '1.2em', 'ifbold' => 1],
        ['color' => '#993300', 'size' => '1.3em', 'ifbold' => 0],
        ['color' => '#669933', 'size' => '1.4em', 'ifbold' => 1],
        ['color' => '#3366FF', 'size' => '1.5em', 'ifbold' => 0],
        ['color' => '#197B30', 'size' => '1.6em', 'ifbold' => 1],
    ];

    $maxlevel = count($tagmark);
    $tcount = $scount = [];

    foreach ($tags as $val) {
        $tcount[] = $val['tag_count']; // 获得tag个数数组
    }
    $tcount = array_unique($tcount); // 去除相同个数的tag

    sort($tcount); // 从小到大排序

    $tempcount = count($tcount); // 真正的tag级数
    $per = $maxlevel >= $tempcount ? 1 : $maxlevel / ($tempcount - 1);

    foreach ($tcount as $key => $val) {
        $lvl = floor($per * $key);
        $scount[$val] = $lvl; // 计算不同个数的tag相对应的着色数组key
    }

    $rewrite = intval($GLOBALS['_CFG']['rewrite']) > 0;

    /* 遍历所有标签，根据引用次数设定字体大小 */
    foreach ($tags as $key => $val) {
        $lvl = $scount[$val['tag_count']]; // 着色数组key

        $tags[$key]['color'] = $tagmark[$lvl]['color'];
        $tags[$key]['size'] = $tagmark[$lvl]['size'];
        $tags[$key]['bold'] = $tagmark[$lvl]['ifbold'];
        if ($rewrite) {
            if (strtolower(EC_CHARSET) !== 'utf-8') {
                $tags[$key]['url'] = 'tag-' . urlencode(urlencode($val['tag_words'])) . '.html';
            } else {
                $tags[$key]['url'] = 'tag-' . urlencode($val['tag_words']) . '.html';
            }
        } else {
            $tags[$key]['url'] = 'search.php?keywords=' . urlencode($val['tag_words']);
        }
    }
    shuffle($tags);
}

/**
 *  获取用户参与活动信息
 *
 * @access  public
 * @param int $user_id 用户id
 *
 * @return  array
 */
function get_user_prompt($user_id)
{
    $prompt = [];
    $now = gmtime();
    /* 夺宝奇兵 */
    $res = GoodsActivity::where('act_type', GAT_SNATCH)
        ->where('review_status', 3)
        ->where(function ($query) use ($now) {
            $query->where('is_finished', 1)
                ->orWhere(function ($query) use ($now) {
                    $query->where('is_finished', 0)
                        ->where('end_time', '<=', $now);
                });
        });

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $row) {
            $act_id = $row['act_id'];
            $result = get_snatch_result($act_id);
            if (isset($result['order_count']) && $result['order_count'] == 0 && $result['user_id'] == $user_id) {
                $prompt[] = [
                    'text' => sprintf($GLOBALS['_LANG']['your_snatch'], $row['goods_name'], $row['act_id']),
                    'add_time' => $row['end_time']
                ];
            }
        }
    }

    /* 竞拍 */
    $res = GoodsActivity::where('act_type', GAT_AUCTION)
        ->where('review_status', 3)
        ->where(function ($query) use ($now) {
            $query->where('is_finished', 1)
                ->orWhere(function ($query) use ($now) {
                    $query->where('is_finished', 0)
                        ->where('end_time', '<=', $now);
                });
        });

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $row) {
            $auction = app(AuctionService::class)->getAuctionInfo($row['act_id']);

            if (isset($auction['last_bid']) && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                $prompt[] = [
                    'text' => sprintf($GLOBALS['_LANG']['your_auction'], $row['goods_name'], $row['act_id']),
                    'add_time' => $row['end_time']
                ];
            }
        }
    }

    /* 排序 */
    $cmp = function ($a, $b) {
        if ($a["add_time"] == $b["add_time"]) {
            return 0;
        }
        return $a["add_time"] < $b["add_time"] ? 1 : -1;
    };
    usort($prompt, $cmp);

    /* 格式化时间 */
    foreach ($prompt as $key => $val) {
        $prompt[$key]['formated_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
    }

    return $prompt;
}

/**
 *  获取用户评论
 *
 * @access  public
 * @param int $user_id 用户id
 * @param int $page_size 列表最大数量
 * @param int $start 列表起始页
 * @return  array
 */
function get_comment_list($user_id, $page_size, $start)
{
    $res = Comment::where('user_id', $user_id);

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($page_size > 0) {
        $res = $res->take($page_size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $comments = [];
    $to_article = [];

    if ($res) {
        foreach ($res as $row) {
            $goods_name = Goods::where('id_value', $row['goods_id'])->value('goods_name');
            $row['cmt_name'] = $goods_name;

            $comment = Comment::select('content', 'add_time')->where('parent_id', $row['comment_id'])
                ->where('parent_id', '>', 0)
                ->where('single_id', 0)
                ->where('dis_id', 0);

            $comment = app(BaseRepository::class)->getToArrayFirst($comment);

            $row['reply_content'] = $comment ? $comment['content'] : '';
            $row['reply_time'] = $comment ? $comment['add_time'] : '';

            $row['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            if ($row['reply_time']) {
                $row['formated_reply_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['reply_time']);
            }
            if ($row['comment_type'] == 1) {
                $to_article[] = $row["id_value"];
            }

            $row['goods_url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['id_value']], $row['goods_name']);
            $comments[] = $row;
        }
    }


    if ($to_article) {
        $arr = Article::whereIn('article_id', $to_article);
        $arr = app(BaseRepository::class)->getToArrayFirst($arr);

        $to_cmt_name = [];
        if ($arr) {
            foreach ($arr as $row) {
                $to_cmt_name[$row['article_id']] = $row['title'];
            }
        }

        if ($comments) {
            foreach ($comments as $key => $row) {
                if ($row['comment_type'] == 1) {
                    $comments[$key]['cmt_name'] = isset($to_cmt_name[$row['id_value']]) ? $to_cmt_name[$row['id_value']] : '';
                }
            }
        }
    }

    return $comments;
}

/**
 * 评论晒单数量
 *
 * @param $user_id
 * @param int $sign 0：带评论 1：追加图片 2:已评论
 * @param int $order_id
 * @return mixed
 */
function get_user_order_comment_count($user_id, $sign = 0, $order_id = 0)
{
    $where = [
        'user_id' => $user_id,
        'order_id' => $order_id,
        'order_status' => [OS_CONFIRMED, OS_SPLITED],
        'shipping_status' => SS_RECEIVED,
        'pay_status' => [PS_PAYED, PS_PAYING],
        'sign' => $sign
    ];

    $res = OrderGoods::whereHas('getOrder', function ($query) use ($where) {
        $query = $query->where('user_id', $where['user_id'])
            ->whereIn('order_status', $where['order_status'])
            ->where('shipping_status', $where['shipping_status'])
            ->whereIn('pay_status', $where['pay_status']);

        if (isset($where['order_id']) && $where['order_id']) {
            $query->where('order_id', $where['order_id']);
        } else {
            $query->where('main_count', 0);
        }
    });

    $res = $res->whereHas('getGoods');

    $res = $res->where(function ($query) use ($where) {
        $query->goodsCommentCount($where['user_id'], $where['order_id'], $where['sign']);
    });

    $count = $res->count();

    return $count;
}

/**
 * 评论晒单
 * @param type $user_id
 * @param type $type count,list标识
 * @param type $sign 0：带评论 1：追加图片 2:已评论
 * @param type $size
 * @param type $start
 * @return type
 */
function get_user_order_comment_list($user_id, $sign = 0, $order_id = 0, $size = 0, $start = 0)
{
    $where = [
        'user_id' => $user_id,
        'order_id' => $order_id,
        'order_status' => [OS_CONFIRMED, OS_SPLITED],
        'shipping_status' => SS_RECEIVED,
        'pay_status' => [PS_PAYED, PS_PAYING],
        'sign' => $sign
    ];

    $res = OrderGoods::whereHas('getOrder', function ($query) use ($where) {
        $query = $query->where('user_id', $where['user_id'])
            ->whereIn('order_status', $where['order_status'])
            ->where('shipping_status', $where['shipping_status'])
            ->whereIn('pay_status', $where['pay_status']);

        if (isset($where['order_id']) && $where['order_id']) {
            $query->where('order_id', $where['order_id']);
        } else {
            $query->where('main_count', 0);
        }
    });

    $res = $res->whereHas('getGoods');

    $res = $res->where(function ($query) use ($where) {
        $query->goodsCommentCount($where['user_id'], $where['order_id'], $where['sign']);
    });

    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_thumb', 'goods_product_tag');
        },
        'getOrder' => function ($query) {
            $query->select('order_id', 'order_sn', 'add_time');
        }
    ]);

    $res = $res->orderBy('rec_id', 'desc');

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_order']);

            $row['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            $row['goods_thumb'] = empty($row['goods_thumb']) ? '' : get_image_path($row['goods_thumb']);
            $row['impression_list'] = !empty($row['goods_product_tag']) ? explode(',', $row['goods_product_tag']) : [];
            $row['goods_url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $row['goods_price'] = price_format($row['goods_price']);

            //订单商品评论信息
            $row['comment'] = app(CommentService::class)->getOrderGoodsComment($row['goods_id'], $row['rec_id'], $user_id);
            $res[$key] = $row;
        }
    }

    return $res;
}

/**
 * 查询会员余额的数量
 * @access  public
 * @param int $user_id 会员ID
 * @return  int
 */
function get_row_user_account($user_id)
{
    $user = Users::where('user_id', $user_id)->first();

    //余额
    $row['user_money'] = $user->user_money;
    //冻结资金
    $row['frozen_money'] = $user->frozen_money;
    //积分数量
    $row['pay_points'] = $user->pay_points;

    // 红包数量
    $row['bonus_count'] = UserBonus::from('user_bonus as u')
        ->leftjoin('bonus_type as b', 'u.bonus_type_id', '=', 'b.type_id')
        ->where('u.user_id', $user_id)
        ->where('b.use_end_date', '>', gmtime())
        ->where('b.use_start_date', '<', gmtime())
        ->where('u.used_time', 0)
        ->where('u.bonus_type_id', '>', 0)
        ->where('u.order_id', 0)
        ->count();

    //储值卡数量&金额
    $row['value_card']['num'] = ValueCard::where('user_id', $user_id)->count();
    $row['value_card']['money'] = ValueCard::where('user_id', $user_id)->sum('card_money');

    return $row;
}
