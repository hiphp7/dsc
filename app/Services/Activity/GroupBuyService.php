<?php

namespace App\Services\Activity;

use App\Libraries\Shop;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\OrderInfo;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommentService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;
use App\Services\User\UserRankService;

class GroupBuyService
{
    protected $timeRepository;
    protected $dscRepository;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $cartCommonService;
    protected $config;
    protected $shop;
    protected $userRankService;
    protected $goodsGalleryService;
    protected $goodsCommonService;
    protected $goodsCommentService;
    protected $merchantCommonService;
    protected $goodsWarehouseService;
    protected $orderGoodsService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        CartCommonService $cartCommonService,
        Shop $shop,
        UserRankService $userRankService,
        GoodsGalleryService $goodsGalleryService,
        GoodsCommonService $goodsCommonService,
        GoodsCommentService $goodsCommentService,
        MerchantCommonService $merchantCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        OrderGoodsService $orderGoodsService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->cartCommonService = $cartCommonService;
        $this->shop = $shop;
        $this->userRankService = $userRankService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsCommentService = $goodsCommentService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderGoodsService = $orderGoodsService;
    }

    /**
     * 取得团购活动总数
     * @param array $children 分类
     * @param String $keywords 关键字
     * @return  array
     */
    public function getGroupBuyCount($children = [], $keywords = '')
    {
        $now = $this->timeRepository->getGmTime();

        $param = [
            'children' => $children,
            'keywords' => $keywords
        ];

        $count = GoodsActivity::where('act_type', GAT_GROUP_BUY)
            ->where('start_time', '<=', $now)
            ->where('is_finished', '<', 3)
            ->where('review_status', 3);

        $count = $count->whereHas('getGoods', function ($query) use ($param) {
            $query = $query->where('is_delete', 0);

            if ($param['children']) {
                $query = $query->whereIn('cat_id', $param['children']);
            }

            if ($param['children']) {
                $query = $query->orWhere(function ($query) use ($param) {
                    $query->whereHas('getGoodsCat', function ($query) use ($param) {
                        $query->whereIn('cat_id', $param['children']);
                    });
                });
            }

            if ($param['keywords']) {
                $query->where('goods_name', 'like', '%' . $param['keywords'] . '%');
            }
        });

        if ($keywords) {
            $count = $count->where('act_name', 'like', '%' . $keywords . '%');
        }

        $count = $count->count();

        return $count;
    }

    /**
     * 取得某页的所有团购活动
     * @param array $children 分类
     * @param String $keywords 关键字
     * @param int $type 类型
     * @param int $size 每页记录数
     * @param int $page 当前页
     * @return  array
     */
    public function getGroupBuyList($children = [], $keywords = '', $type = '', $goods_num = 0, $size = 10, $page = 1, $sort = 'act_id', $order = 'DESC')
    {
        /* 取得团购活动 */
        $now = $this->timeRepository->getGmTime();

        $param = [
            'children' => $children,
            'keywords' => $keywords,
            'sort' => $sort,
            'order' => $order
        ];

        $res = GoodsActivity::where('act_type', GAT_GROUP_BUY)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('is_finished', '<', 3)
            ->where('review_status', 3);

        $res = $res->whereHas('getGoods', function ($query) use ($param) {
            $query = $query->where('is_delete', 0);

            if ($param['children']) {
                $query = $query->whereIn('cat_id', $param['children']);
            }

            if ($param['children']) {
                $query = $query->orWhere(function ($query) use ($param) {
                    $query->whereHas('getGoodsCat', function ($query) use ($param) {
                        $query->whereIn('cat_id', $param['children']);
                    });
                });
            }

            if ($param['keywords']) {
                $query->where('goods_name', 'like', '%' . $param['keywords'] . '%');
            }
        });

        if ($keywords) {
            $res = $res->where('act_name', 'like', '%' . $keywords . '%');
        }

        if ($type == "new") {
            $res = $res->where('is_new', 1);
        } elseif ($type == "hot") {
            $res = $res->where('is_hot', 1);
        }

        $res = $res->with([
            'getGoods' => function ($query) use ($param) {
                if ($param['sort'] == 'comments_number') {
                    $query->orderBy($param['sort'], $param['order']);
                }
            }
        ]);

        if ($sort != 'comments_number') {
            $res = $res->orderBy($sort, $order);
        }

        //瀑布流加载分类商品 by wu
        if ($goods_num) {
            $start = $goods_num;
        } else {
            $start = ($page - 1) * $size;
        }

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $gb_list = [];
        if ($res) {
            foreach ($res as $group_buy) {
                $goods = $group_buy['get_goods'];

                $group_buy['group_buy_id'] = $group_buy['act_id'];
                $group_buy['start_date'] = $group_buy['start_time'];
                $group_buy['end_date'] = $group_buy['end_time'];

                $ext_info = unserialize($group_buy['ext_info']);
                $group_buy = array_merge($group_buy, $ext_info);

                /* 格式化时间 */
                $group_buy['formated_start_date'] = $this->timeRepository->getLocalDate($this->config['time_format'], $group_buy['start_date']);
                $group_buy['formated_end_date'] = $this->timeRepository->getLocalDate($this->config['time_format'], $group_buy['end_date']);
                $group_buy['is_end'] = $now > $group_buy['end_date'] ? 1 : 0;

                /* 格式化保证金 */
                $group_buy['formated_deposit'] = $this->dscRepository->getPriceFormat($group_buy['deposit'], false);

                /* 处理价格阶梯 */
                $price_ladder = $group_buy['price_ladder'];
                if (!is_array($price_ladder) || empty($price_ladder)) {
                    $price_ladder = [
                        [
                            'amount' => 0,
                            'price' => 0,
                            'formated_amount' => $this->dscRepository->getPriceFormat(0),
                            'formated_price' => $this->dscRepository->getPriceFormat(0)
                        ]
                    ];
                } else {
                    foreach ($price_ladder as $key => $amount_price) {
                        $price_ladder[$key]['formated_price'] = $this->dscRepository->getPriceFormat($amount_price['price']);
                    }
                }

                $group_buy['price_ladder'] = $price_ladder;

                /* 团购节省和折扣计算 by ecmoban start */
                $price = $goods['market_price']; //原价
                $nowprice = $group_buy['price_ladder'][0]['price'] ?? 1; //现价

                /* 处理被除值为0，赋值为1 */
                $nowprice = $nowprice == 0 ? 1 : $nowprice;
                $zhekou_price = ($price / $nowprice) == 0 ? 1 : ($price / $nowprice);

                $group_buy['jiesheng'] = $price - $nowprice; //节省金额
                if ($nowprice > 0) {
                    $group_buy['zhekou'] = round(10 / $zhekou_price, 1);
                } else {
                    $group_buy['zhekou'] = 0;
                }

                $stat = $this->getGroupBuyStat($group_buy['act_id'], $ext_info['deposit']);
                $group_buy['cur_amount'] = $stat['valid_goods'];         // 当前数量

                $group_buy['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);

                /* 处理链接 */
                $group_buy['url'] = $this->dscRepository->buildUri('group_buy', ['gbid' => $group_buy['group_buy_id']]);

                $group_buy['end_date_day'] = $this->timeRepository->getBuyDate($group_buy['end_date']);
                $group_buy['price'] = $this->dscRepository->getPriceFormat($nowprice);

                /* 加入数组 */
                $gb_list[] = $group_buy;
            }
        }

        return $gb_list;
    }

    /**
     * 取得团购活动信息
     * @param int $group_buy_id 团购活动id
     * @param int $current_num 本次购买数量（计算当前价时要加上的数量）
     * @return  array
     *                  status          状态：
     */
    //$group_buy_id, $current_num = 0, $path = ''
    public function getGroupBuyInfo($where = [])
    {

        /* 取得团购活动信息 */
        $res = GoodsActivity::where('act_type', GAT_GROUP_BUY);

        if (isset($where['group_buy_id'])) {
            $res = $res->where('act_id', $where['group_buy_id']);
        }

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_delete', 0);
        });

        if (!isset($where['path'])) {
            $res = $res->where('review_status', 3);
        }

        $user_rank = 1;
        $discount = 1;
        if (isset($where['user_id']) && $where['user_id']) {
            $user_rank = $this->userRankService->getUserRankInfo($where['user_id']);
            if ($user_rank) {
                $user_rank = $user_rank['user_rank'] ?? 1;
                $discount = $user_rank['discount'] ?? 1;
            }
        }

        $where['user_rank'] = $user_rank;

        $res = $res->with([
            'getGoods' => function ($query) use ($where) {
                if (isset($where['warehouse_id']) && isset($where['area_id'])) {
                    $query->with([
                        'getMemberPrice' => function ($query) use ($where) {
                            $query->where('user_rank', $where['user_rank']);
                        },
                        'getWarehouseGoods' => function ($query) use ($where) {
                            $query->where('region_id', $where['warehouse_id']);
                        },
                        'getWarehouseAreaGoods' => function ($query) use ($where) {
                            $query->where('region_id', $where['area_id']);
                        },
                        'getSellerShopinfo',
                        'getGoodsExtend'
                    ]);
                }
            },
        ]);

        $group_buy = $this->baseRepository->getToArrayFirst($res);

        /* 如果为空，返回空数组 */
        if (empty($group_buy)) {
            return [];
        }

        $goods = $group_buy['get_goods'] ?? [];

        if ($goods) {
            $price = [
                'model_price' => isset($goods['model_price']) ? $goods['model_price'] : 0,
                'user_price' => isset($goods['get_member_price']['user_price']) ? $goods['get_member_price']['user_price'] : 0,
                'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                'warehouse_price' => isset($goods['get_warehouse_goods']['warehouse_price']) ? $goods['get_warehouse_goods']['warehouse_price'] : 0,
                'region_price' => isset($goods['get_warehouse_area_goods']['region_price']) ? $goods['get_warehouse_area_goods']['region_price'] : 0,
                'shop_price' => isset($goods['shop_price']) ? $goods['shop_price'] : 0,
                'warehouse_promote_price' => isset($goods['get_warehouse_goods']['warehouse_promote_price']) ? $goods['get_warehouse_goods']['warehouse_promote_price'] : 0,
                'region_promote_price' => isset($goods['get_warehouse_area_goods']['region_promote_price']) ? $goods['get_warehouse_area_goods']['region_promote_price'] : 0,
                'promote_price' => isset($goods['promote_price']) ? $goods['promote_price'] : 0,
                'wg_number' => isset($goods['get_warehouse_goods']['region_number']) ? $goods['get_warehouse_goods']['region_number'] : 0,
                'wag_number' => isset($goods['get_warehouse_area_goods']['region_number']) ? $goods['get_warehouse_area_goods']['region_number'] : 0,
                'goods_number' => isset($goods['goods_number']) ? $goods['goods_number'] : 0
            ];

            $price = $this->goodsCommonService->getGoodsPrice($price, $discount, $row);

            $goods['shop_price'] = $price['shop_price'];
            $goods['promote_price'] = $price['promote_price'];
            $goods['goods_number'] = $price['goods_number'];

            if ($goods['promote_price'] > 0) {
                $promote_price = $this->goodsCommonService->getBargainPrice($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $collect = 0;
            if (isset($where['user_id']) && $where['user_id']) {
                $collect = CollectGoods::where('goods_id', $goods['goods_id'])
                    ->where('user_id', $where['user_id'])
                    ->count();
            }

            $goods['user_collect'] = $collect;
            $goods['is_collect'] = empty($collect) ? 0 : 1;

            $info = [
                'goods_id' => $goods['goods_id']
            ];
            $goods['gallery_list'] = $this->goodsGalleryService->getGalleryList($info);

            /*获取商品规格参数*/
            $goods['attr_parameter'] = $this->goodsAttrService->goodsAttrParameter($goods['goods_id']);

            $goods['group_buy_attr'] = $this->goodsAttrService->goodsAttr($goods['goods_id']);

            $attr_str = [];
            if ($goods['group_buy_attr']) {
                $goods['attr_name'] = '';
                foreach ($goods['group_buy_attr'] as $k => $v) {
                    $select_key = 0;
                    foreach ($v['attr_key'] as $key => $val) {
                        if ($val['attr_checked'] == 1) {
                            $select_key = $key;
                            break;
                        }
                    }
                    //默认选择第一个属性为checked
                    if ($select_key == 0) {
                        $goods['group_buy_attr'][$k]['attr_key'][0]['attr_checked'] = 1;
                    }

                    if ($goods['attr_name']) {
                        $goods['attr_name'] = $goods['attr_name'] . '' . $v['attr_key'][$select_key]['attr_value'];
                    } else {
                        $goods['attr_name'] = $v['attr_key'][$select_key]['attr_value'];
                    }

                    $attr_str[] = $v['attr_key'][$select_key]['goods_attr_id'];
                }
            }


            if ($attr_str) {
                sort($attr_str);
            }

            //默认第一个属性的库存
            $goods['ru_id'] = $goods['user_id'] ?? 0;
            if (!empty($attr_str)) {
                $where['warehouse_id'] = $where['warehouse_id'] ?? 0;
                $where['area_id'] = $where['area_id'] ?? 0;
                $where['area_city'] = $where['area_city'] ?? 0;
                $goods['goods_number'] = $this->goodsWarehouseService->goodsAttrNumber($goods['goods_id'], $attr_str, $where['warehouse_id'], $where['area_id'], $where['area_city']);
            }

            $goods['short_name'] = $this->config['goods_name_length'] > 0 ?
                $this->dscRepository->subStr($goods['goods_name'], $this->config['goods_name_length']) : $goods['goods_name'];
            $goods['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
            $goods['goods_img'] = $this->dscRepository->getImagePath($goods['goods_img']);
            $goods['market_price'] = $this->dscRepository->getPriceFormat($goods['market_price']);
            $goods['shop_price'] = $this->dscRepository->getPriceFormat($goods['shop_price']);
            $goods['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
            $goods['url'] = $this->dscRepository->buildUri('goods', ['gid' => $goods['goods_id']], $goods['goods_name']);

            $goods['goods_extend'] = $goods['get_goods_extend'] ?? [];
            $goods['seller_shopinfo'] = $goods['get_seller_shopinfo'] ?? [];
        }

        $group_buy['merchant_group'] = $this->getMerchantGroupGoods($where['group_buy_id'], $goods['user_id']);
        $group_buy['group_buy_id'] = $group_buy['act_id'];
        $group_buy['start_date'] = $group_buy['start_time'];
        $group_buy['end_date'] = $group_buy['end_time'];
        $group_buy['group_buy_desc'] = $group_buy['act_desc'];
        $group_buy['groupby_review'] = $group_buy['review_content'];
        $group_buy['groupby_status'] = $group_buy['review_status'];
        $group_buy['act_hot'] = $group_buy['is_hot'];
        $group_buy['act_new'] = $group_buy['is_new'];

        $ext_info = unserialize($group_buy['ext_info']);
        $group_buy = array_merge($group_buy, $ext_info);

        /* 格式化时间 */
        $group_buy['formated_start_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $group_buy['start_time']);
        $group_buy['formated_end_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $group_buy['end_time']);
        $group_buy['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $goods['add_time']);

        $now = $this->timeRepository->getGmTime();
        $group_buy['is_end'] = $now > $group_buy['end_time'] ? 1 : 0;

        $group_buy['xiangou_start_date'] = $group_buy['start_time'];
        $group_buy['xiangou_end_date'] = $group_buy['end_time'];
        /* 格式化保证金 */
        $group_buy['formated_deposit'] = $this->dscRepository->getPriceFormat($group_buy['deposit'], false);

        /* 处理价格阶梯 */
        $price_ladder = $group_buy['price_ladder'];
        if (!is_array($price_ladder) || empty($price_ladder)) {
            $price_ladder = [
                [
                    'amount' => 0,
                    'price' => 0,
                    'formated_amount' => $this->dscRepository->getPriceFormat(0),
                    'formated_price' => $this->dscRepository->getPriceFormat(0)
                ]
            ];
        } else {
            foreach ($price_ladder as $key => $amount_price) {
                $price_ladder[$key]['formated_price'] = $this->dscRepository->getPriceFormat($amount_price['price'], false);
            }
        }
        $group_buy['price_ladder'] = $price_ladder;

        /* 统计信息 */
        $stat = $this->getGroupBuyStat($where['group_buy_id'], $group_buy['deposit']);
        $group_buy = array_merge($group_buy, $stat);

        $current_num = isset($where['current_num']) && !empty($where['current_num']) ? $where['current_num'] : 0;

        /* 计算当前价 */
        $cur_price = $price_ladder[0]['price']; // 初始化
        $cur_amount = $stat['valid_goods'] + $current_num; // 当前数量
        foreach ($price_ladder as $amount_price) {
            if ($cur_amount >= $amount_price['amount']) {
                $cur_price = $amount_price['price'];
            } else {
                break;
            }
        }

        /* 商品详情 */
        $group_buy['goods_desc'] = $goods['goods_desc'];

        $group_buy['cur_price'] = $cur_price;
        $group_buy['formated_cur_price'] = $this->dscRepository->getPriceFormat($cur_price, false);

        /* 团购节省和折扣计算 start */
        $price = empty($group_buy['market_price']) ? 1 : $group_buy['market_price']; //原价
        $nowprice = empty($group_buy['cur_price']) ? 1 : $group_buy['cur_price']; //现价
        $group_buy['jiesheng'] = $price - $nowprice; //节省金额

        if ($nowprice > 0 && $price > 0) {
            $group_buy['zhekou'] = round(10 / ($price / $nowprice), 1);
        } else {
            $group_buy['zhekou'] = 0;
        }
        /* 团购节省和折扣计算 end */

        /* 最终价 */
        $group_buy['trans_price'] = $group_buy['cur_price'];
        $group_buy['formated_trans_price'] = $group_buy['formated_cur_price'];
        $group_buy['trans_amount'] = $group_buy['valid_goods'];

        /* 视频路径格式化 */
        $group_buy['goods_video_path'] = $this->dscRepository->getImagePath($goods['goods_video']);

        /* 状态 */
        $group_buy['status'] = $this->getGroupBuyStatus($group_buy);
        if (isset($GLOBALS['_LANG']['gbs'][$group_buy['status']])) {
            $group_buy['status_desc'] = $GLOBALS['_LANG']['gbs'][$group_buy['status']];
        }

        $group_buy['start_time'] = $group_buy['formated_start_date'];
        $group_buy['end_time'] = $group_buy['formated_end_date'];

        $group_buy['current_time'] = $this->timeRepository->getGmTime();
        $group_buy['order_number'] = 0;

        if (isset($where['user_id']) && $where['user_id']) {
            $num = $this->orderGoodsService->getForPurchasingGoods($group_buy['start_date'], $group_buy['end_date'], $group_buy['goods_id'], $where['user_id'], 'group_buy', '', $group_buy['act_id']);
            $group_buy['order_number'] = $num['goods_number'];
        }

        if ($group_buy['user_id']) {
            $group_buy['rz_shopName'] = $this->merchantCommonService->getShopName($group_buy['user_id'], 1); //店铺名称

            $build_uri = [
                'urid' => $group_buy['user_id'],
                'append' => $group_buy['rz_shopName']
            ];

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($group_buy['user_id'], $build_uri);
            $group_buy['store_url'] = $domain_url['domain_name'];

            $group_buy['shopinfo'] = $this->merchantCommonService->getShopName($group_buy['user_id'], 2);
            $group_buy['shopinfo']['brand_thumb'] = str_replace(['../'], '', $group_buy['shopinfo']['brand_thumb']);
            $group_buy['shopinfo']['brand_thumb'] = $this->dscRepository->getImagePath($group_buy['shopinfo']['brand_thumb']);
        }

        //买家印象
        if ($goods['goods_product_tag']) {
            $tag = [];
            $impression_list = explode(',', $goods['goods_product_tag']);
            foreach ($impression_list as $kk => $vv) {
                $tag[$kk]['txt'] = $vv;
                //印象数量
                $tag[$kk]['num'] = $this->goodsCommentService->commentGoodsTagNum($group_buy['goods_id'], $vv);
            }
            $group_buy['impression_list'] = $tag;
        }

        $group_buy['collect_count'] = $goods['user_collect'] ?? 0;

        if ($group_buy['user_id'] == 0) {
            $group_buy['brand'] = $this->get_brand_url($goods['brand_id']);
        }

        /* 修正重量显示 */
        $group_buy['goods_weight'] = (intval($goods['goods_weight']) > 0) ?
            $goods['goods_weight'] . lang('goods.kilogram') :
            ($goods['goods_weight'] * 1000) . lang('goods.gram');

        if ($this->config['open_oss'] == 1) {
            $bucket_info = $this->dscRepository->getBucketInfo();
            $endpoint = $bucket_info['endpoint'];
        } else {
            $endpoint = url('/');
        }

        if ($group_buy['goods_desc']) {
            $desc_preg = get_goods_desc_images_preg($endpoint, $group_buy['goods_desc']);
            $group_buy['goods_desc'] = $desc_preg['goods_desc'];
            $goods['goods_desc'] = $group_buy['goods_desc'];
        }

        $group_buy['goods'] = $goods;

        return $group_buy;
    }

    /*
     * 取得某团购活动统计信息
     * @param   int     $group_buy_id   团购活动id
     * @param   float   $deposit        保证金
     * @return  array   统计信息
     *                  total_order     总订单数
     *                  total_goods     总商品数
     *                  valid_order     有效订单数
     *                  valid_goods     有效商品数
     */

    public function getGroupBuyStat($group_buy_id, $deposit = 0, $type = 0)
    {
        $deposit = floatval($deposit);

        /* 取得团购活动商品ID */
        $goods_id = GoodsActivity::where('review_status', 3)
            ->where('act_id', $group_buy_id)
            ->where('act_type', GAT_GROUP_BUY)
            ->value('goods_id');

        /* 取得总订单数和总商品数 */
        $param = [
            'os_confirmed' => OS_CONFIRMED,
            'os_unconfirmed' => OS_UNCONFIRMED,
            'goods_id' => $goods_id
        ];

        $select = '';
        if ($type == 1) {
            $select = ', (money_paid + surplus) AS mspaid';
        }

        $stat = OrderInfo::select('order_id')->selectRaw('COUNT(*) AS total_order' . $select)
            ->where('extension_code', 'group_buy')
            ->where('extension_id', $group_buy_id);

        if ($deposit && $type == 1) {
            $stat = $stat->having('mspaid', '>=', $deposit);
        }

        $stat = $stat->where(function ($query) use ($param) {
            $query->where('order_status', $param['os_confirmed'])
                ->orWhere('order_status', $param['os_unconfirmed']);
        });

        $stat = $stat->whereHas('getOrderGoods', function ($query) use ($param) {
            $query->where('goods_id', $param['goods_id']);
        });

        $stat = $stat->with(['getOrderGoodsList']);

        $stat = $stat->first();

        $stat = $stat ? $stat->toArray() : [];

        $stat['total_goods'] = 0;

        if (isset($stat['get_order_goods_list'])) {
            foreach ($stat['get_order_goods_list'] as $key => $row) {
                $stat['total_goods'] += $row['goods_number'];
            }

            if ($stat['total_order'] == 0) {
                $stat['total_goods'] = 0;
            }
        } else {
            $stat['valid_order'] = 0;
            $stat['valid_goods'] = 0;
        }

        if ($stat && $type == 0) {

            /* 取得有效订单数和有效商品数 */
            if ($deposit > 0 && $stat['total_order'] > 0 && $type == 0) {
                $row = $this->getGroupBuyStat($group_buy_id, $deposit, 1);

                $stat['valid_order'] = ($row && isset($row['total_order'])) ? $row['total_order'] : 0;
                if ($stat['valid_order'] == 0) {
                    $stat['valid_goods'] = 0;
                } else {
                    $stat['valid_goods'] = $row['total_goods'];
                }
            } else {
                $stat['valid_order'] = $stat['total_order'];
                $stat['valid_goods'] = $stat['total_goods'];
            }
        }

        return $stat;
    }

    /**
     * 获得团购的状态
     *
     * @access  public
     * @param array
     * @return  integer
     */
    public function getGroupBuyStatus($group_buy)
    {
        $now = $this->timeRepository->getGmTime();
        if ($group_buy['is_finished'] == 0) {
            /* 未处理 */
            if ($now < $group_buy['start_time']) {
                $status = GBS_PRE_START;
            } elseif ($now > $group_buy['end_time']) {
                $status = GBS_FINISHED;
            } else {
                if ($group_buy['restrict_amount'] == 0 || $group_buy['valid_goods'] < $group_buy['restrict_amount']) {
                    $status = GBS_UNDER_WAY;
                } else {
                    $status = GBS_FINISHED;
                }
            }
        } elseif ($group_buy['is_finished'] == GBS_SUCCEED) {
            /* 已处理，团购成功 */
            $status = GBS_SUCCEED;
        } elseif ($group_buy['is_finished'] == GBS_FAIL) {
            /* 已处理，团购失败 */
            $status = GBS_FAIL;
        }

        return $status;
    }

    /**
     * 获得店铺团购商品排行榜
     *
     * @access  public
     * @param int $group_buy_id
     * @param int $seller_id
     * @return  integer
     */
    public function getTopGroupGoods($order, $user_id = 0)
    {
        $res = GoodsActivity::where('user_id', $user_id)
            ->where('review_status', 3)
            ->where('act_type', GAT_GROUP_BUY);

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_delete', 0);
        });

        $res = $res->take(5);

        $res = $res->with(['getGoods']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $look_top_list_1 = [];
        if ($res) {
            foreach ($res as $key => $look_top) {
                $goods = $look_top['get_goods'];

                $ext_info = unserialize($look_top['ext_info']);
                $look_top = array_merge($look_top, $ext_info);

                $look_top['ext_info'] = $ext_info;

                // 处理价格阶梯
                $price_ladder = $look_top['ext_info']['price_ladder'];
                if (!is_array($price_ladder) || empty($price_ladder)) {
                    $price_ladder = [['amount' => 0, 'price' => 0]];
                } else {
                    foreach ($price_ladder as $k => $amount_price) {
                        $price_ladder[$k]['formated_price'] = $this->dscRepository->getPriceFormat($amount_price['price'], false);
                    }
                }
                $look_top['ext_info']['price_ladder'] = $price_ladder;

                // 计算当前价
                $cur_price = $price_ladder[0]['price']; // 初始化

                /* 统计信息 */
                $stat = $this->getGroupBuyStat($look_top['act_id'], $look_top['deposit']);
                $look_top = array_merge($look_top, $stat);

                $cur_amount = $stat['valid_goods']; // 当前数量
                foreach ($price_ladder as $amount_price) {
                    if ($cur_amount >= $amount_price['amount']) {
                        $cur_price = $amount_price['price'];
                    } else {
                        break;
                    }
                }

                $look_top['click_count'] = $goods['click_count'];
                $look_top['sales_volume'] = $goods['sales_volume'];
                $look_top['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);

                $look_top['ext_info']['cur_price'] = $this->dscRepository->getPriceFormat($cur_price, false); //现价
                $look_top_list_1[$key] = $look_top;
            }

            $look_top_list_1 = collect($look_top_list_1)->sortByDesc($order);
            $look_top_list_1 = $look_top_list_1->values()->all();
        }

        return $look_top_list_1;
    }

    /**
     * 获得店铺团购商品
     *
     * @access  public
     * @param int $group_buy_id
     * @param int $seller_id
     * @return  integer
     */
    public function getMerchantGroupGoods($group_buy_id = 0, $seller_id = 0)
    {
        $res = GoodsActivity::where('user_id', $seller_id)
            ->where('review_status', 3)
            ->where('act_id', '<>', $group_buy_id)
            ->where('act_type', GAT_GROUP_BUY);

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_delete', 0);
        });

        $res = $res->take(4);

        $res = $res->with(['getGoods']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $goods = $row['get_goods'];

                $ext_info = unserialize($row['ext_info']);
                $row = array_merge($row, $ext_info);
                $res[$key]['cur_price'] = isset($row['ext_info']['cur_price']) ? $row['ext_info']['cur_price'] : 0;

                /* 处理价格阶梯 */
                $price_ladder = $row['price_ladder'];
                if (!is_array($price_ladder) || empty($price_ladder)) {
                    $price_ladder = [['amount' => 0, 'price' => 0]];
                } else {
                    foreach ($price_ladder as $k => $amount_price) {
                        $price_ladder[$k]['formated_price'] = $this->dscRepository->getPriceFormat($amount_price['price'], false);
                    }
                }

                $res[$key]['shop_price'] = $price_ladder[0]['formated_price'];
                $res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
            }
        }

        return $res;
    }

    /**
     * 获得时间段内下单团购商品
     *
     * @access  public
     * @param int $group_buy_id
     * @param int $first_month_day
     * @param int $last_month_day
     * @return  integer
     */
    public function getMonthDayStartEndGoods($group_buy_id, $first_month_day = 0, $last_month_day = 0)
    {
        $res = GoodsActivity::where('review_status', 3)
            ->where('act_id', '<>', $group_buy_id)
            ->where('act_type', GAT_GROUP_BUY);

        $param = [
            'first_month_day' => $first_month_day,
            'last_month_day' => $last_month_day,
            'os_confirmed' => OS_CONFIRMED,
            'os_unconfirmed' => OS_UNCONFIRMED
        ];

        $res = $res->whereHas('getGoods', function ($query) use ($param) {
            $query->whereHas('getOrderGoods', function ($query) use ($param) {
                $query->whereHas('getOrder', function ($query) use ($param) {
                    $query = $query->where('add_time', '>=', $param['first_month_day'])
                        ->where('add_time', '<=', $param['last_month_day']);
                    $query = $query->where(function ($query) use ($param) {
                        $query->where('order_status', $param['os_confirmed'])
                            ->orWhere('order_status', $param['os_unconfirmed']);
                    });

                    $query->where('extension_code', 'group_buy');
                });
            });
        });

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->with(['getOrderGoodsList']);
            }
        ]);

        $res = $res->take(10);

        $res = $res->orderBy('act_id', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $goods = $row['get_goods'];

                $ext_info = unserialize($row['ext_info']);
                $row = array_merge($row, $ext_info);

                $arr[] = $row;
                $arr[$key]['ext_info'] = $ext_info;

                // 处理价格阶梯
                $price_ladder = $arr[$key]['ext_info']['price_ladder'];
                if (!is_array($price_ladder) || empty($price_ladder)) {
                    $price_ladder = [['amount' => 0, 'price' => 0]];
                } else {
                    foreach ($price_ladder as $k => $amount_price) {
                        $price_ladder[$k]['formated_price'] = $this->dscRepository->getPriceFormat($amount_price['price'], false);
                    }
                }
                $arr[$key]['price_ladder'] = $price_ladder;

                // 计算当前价
                $cur_price = $price_ladder[0]['price']; // 初始化

                /* 统计信息 */
                $stat = $this->getGroupBuyStat($group_buy_id, $ext_info['deposit']);
                $row = array_merge($row, $stat);

                $cur_amount = $stat['valid_goods']; // 当前数量
                foreach ($price_ladder as $amount_price) {
                    if ($cur_amount >= $amount_price['amount']) {
                        $cur_price = $amount_price['price'];
                    } else {
                        break;
                    }
                }

                $arr[$key]['cur_price'] = $this->dscRepository->getPriceFormat($cur_price, false); //现价

                /* 团购节省和折扣计算 by ecmoban start */
                $arr[$key]['market_price'] = $this->dscRepository->getPriceFormat($goods['market_price'], false); //原价
                $price = $goods['market_price']; //原价
                $nowprice = $cur_price; //现价
                $arr[$key]['jiesheng'] = $this->dscRepository->getPriceFormat($price - $nowprice, false); //节省金额
                if ($nowprice > 0) {
                    $arr[$key]['zhekou'] = round(10 / ($price / $nowprice), 1);
                } else {
                    $arr[$key]['zhekou'] = 0;
                }
                /* 团购节省和折扣计算 by ecmoban end */

                $row['v_goods_number'] = 0;
                $order_goods = $goods['get_order_goods_list'];
                if ($order_goods) {
                    foreach ($order_goods as $k => $v) {
                        $row['v_goods_number'] += $v['goods_number'];
                    }
                }

                $arr[$key]['valid_goods'] = $row['v_goods_number'];
                $arr[$key]['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
            }

            $arr = collect($arr)->sortByDesc('v_goods_number');
            $arr = $arr->values()->all();
        }

        return $arr;
    }

    /**
     * 品牌信息
     */
    public function get_brand_url($brand_id = 0)
    {
        $res = Brand::where('brand_id', $brand_id)->first();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            $res['url'] = $this->dscRepository->buildUri('brand', ['bid' => $res['brand_id']], $res['brand_name']);
            $res['brand_logo'] = !empty($res['brand_logo']) ? $this->dscRepository->getImagePath($this->shop->data_dir() . '/brandlogo/' . $res['brand_logo']) : '';
        }

        return $res;
    }

    /**
     * 立即团
     *
     * @param $uid
     * @param $group_buy_id
     * @param int $number
     * @param string $specs
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     * @throws \Exception
     */
    public function getGroupBuy($uid, $group_buy_id, $number = 1, $specs = '', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        /* 查询：取得团购活动信息 */
        $where = [
            'group_buy_id' => $group_buy_id,
            'user_id' => $uid,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'current_num' => $number
        ];
        $group_buy = $this->getGroupBuyInfo($where);
        if (empty($group_buy)) {
            $result = [
                'error' => 1,
                'mesg' => $GLOBALS['_LANG']['gb_error_status'],
            ];
            return $result;
        }

        /* 查询：检查团购活动是否是进行中 */
        if ($group_buy['status'] != GBS_UNDER_WAY) {
            $result = [
                'error' => 1,
                'mesg' => $GLOBALS['_LANG']['gb_error_status'],
            ];
            return $result;
        }

        /* 查询：取得团购商品信息 */
        /* 取得商品信息 */
        $goods = Goods::where('goods_id', $group_buy['goods_id']);
        $goods = $this->baseRepository->getToArrayFirst($goods);

        if (empty($goods)) {
            $result = [
                'error' => 1,
                'mesg' => $GLOBALS['_LANG']['gb_error_status'],
            ];
            return $result;
        }

        $start_date = $group_buy['xiangou_start_date'];
        $end_date = $group_buy['xiangou_end_date'];
        $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $group_buy['goods_id'], $uid, 'group_buy', '', $group_buy['act_id']);

        $restrict_amount = $number + $order_goods['goods_number'];

        /* 查询：判断数量是否足够 */
        if ($group_buy['restrict_amount'] > 0 && $restrict_amount > $group_buy['restrict_amount']) {
            $result = [
                'error' => 1,
                'mesg' => $GLOBALS['_LANG']['gb_error_restrict_amount'],
            ];
            return $result;
        }
        if ($group_buy['restrict_amount'] > 0 && ($number > ($group_buy['restrict_amount'] - $group_buy['valid_goods']))) {
            $result = [
                'error' => 1,
                'mesg' => $GLOBALS['_LANG']['gb_error_goods_lacking'],
            ];
            return $result;
        }

        /* 查询：如果商品有规格则取规格商品信息 配件除外 */
        if ($specs) {
            $_specs = is_array($specs) ? $specs : explode(',', $specs);
            $product_info = $this->goodsAttrService->getProductsInfo($goods['goods_id'], $_specs, $warehouse_id, $area_id, $area_city);
        }

        empty($product_info) ? $product_info = ['product_number' => 0, 'product_id' => 0] : '';

        if ($goods['model_attr'] == 1) {
            $prod = ProductsWarehouse::where('goods_id', $goods['goods_id'])->where('warehouse_id', $warehouse_id);
        } elseif ($goods['model_attr'] == 2) {
            $prod = ProductsArea::where('goods_id', $goods['goods_id'])->where('area_id', $area_id);

            if ($this->config['area_pricetype'] == 1) {
                $prod = $prod->where('city_id', $area_city);
            }
        } else {
            $prod = Products::where('goods_id', $goods['goods_id']);
        }

        $prod = $this->baseRepository->getToArrayFirst($prod);

        /* 检查：库存 */
        if ($GLOBALS['_CFG']['use_storage'] == 1) {
            /* 查询：判断指定规格的货品数量是否足够 */
            if ($prod) {
                if ($number > $product_info['product_number']) {
                    $result['error'] = 1;
                    $result['message'] = lang('group_buy.storage_short');
                    return $result;
                }
            } else {
                /* 查询：判断数量是否足够 */
                if ($number > $goods['goods_number']) {
                    $result['error'] = 1;
                    $result['message'] = lang('group_buy.storage_short');
                    return $result;
                }
            }
        }

        //库存
        $goods_attr_id = !is_array($specs) ? explode(",", $specs) : $specs;
        $attr_number = $this->goodsWarehouseService->goodsAttrNumber($goods['goods_id'], $goods_attr_id, $warehouse_id, $area_id, $area_city);
        if ($number > $attr_number) {
            $result = [
                'error' => 1,
                'msg' => $GLOBALS['_LANG']['gb_error_goods_lacking']
            ];
            return $result;
        }

        /* 查询：查询规格名称和值，不考虑价格 */
        $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($specs, 'pice', $warehouse_id, $area_id, $area_city);

        /* 更新：清空购物车中所有团购商品 */
        $this->cartCommonService->clearCart($uid, CART_GROUP_BUY_GOODS);

        /* 更新：加入购物车 */
        $goods_price = $group_buy['deposit'] > 0 ? $group_buy['deposit'] : $group_buy['cur_price'];
        sort($goods_attr_id);
        $specs = implode(',', $goods_attr_id);
        $cart = [
            'user_id' => $uid,
            'session_id' => '',
            'goods_id' => $group_buy['goods_id'],
            'product_id' => $product_info['product_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_price' => $goods_price,
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $specs,
            'ru_id' => $goods['user_id'],
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'is_real' => $goods['is_real'],
            'extension_code' => addslashes($goods['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_GROUP_BUY_GOODS,
            'is_gift' => 0,
            'freight' => $goods['freight'],
            'shipping_fee' => $goods['shipping_fee'],
            'tid' => $goods['tid']
        ];

        Cart::insertGetId($cart);

        /* 更新：记录购物流程类型：团购 */
        return $result = [
            'flow_type' => CART_GROUP_BUY_GOODS,
            'extension_code' => 'group_buy',
            'extension_id' => $group_buy_id,
        ];
    }
}
