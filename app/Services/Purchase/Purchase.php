<?php

namespace App\Services\Purchase;

use App\Models\AdminUser;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Goods;
use App\Models\GoodsType;
use App\Models\Keywords;
use App\Models\SellerShopinfo;
use App\Models\TouchAd;
use App\Models\Users;
use App\Models\Wholesale;
use App\Models\wholesaleCart;
use App\Models\WholesaleCat;
use App\Models\WholesaleExtend;
use App\Models\WholesaleGoodsAttr;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleProducts;
use App\Models\WholesaleVolumePrice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsAttributeImgService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\GoodsService as WholesaleGoodsService;
use App\Services\Wholesale\OrderService;

class Purchase
{
    protected $baseRepository;
    protected $orderService;
    protected $categoryService;
    protected $wholesaleGoodsService;
    protected $dscRepository;
    protected $commonRepository;
    protected $goodsAttrService;
    protected $merchantCommonService;
    protected $sessionRepository;
    protected $goodsAttributeImgService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        CategoryService $categoryService,
        WholesaleGoodsService $wholesaleGoodsService,
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        GoodsAttrService $goodsAttrService,
        MerchantCommonService $merchantCommonService,
        SessionRepository $sessionRepository,
        GoodsAttributeImgService $goodsAttributeImgService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->orderService = $orderService;
        $this->categoryService = $categoryService;
        $this->wholesaleGoodsService = $wholesaleGoodsService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->merchantCommonService = $merchantCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->goodsAttributeImgService = $goodsAttributeImgService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 轮播图
     */
    public function get_banner($id, $num)
    {
        $time = $this->timeRepository->getGmTime();

        $arr = [
            'id' => $id,
            'num' => $num
        ];

        $res = TouchAd::select('ad_id', 'position_id', 'media_type', 'ad_link', 'ad_code', 'ad_name');
        $res = $res->with([
            'getTouchAdPosition' => function ($query) {
                $query->select('ad_width', 'ad_height', 'position_style');
            }
        ]);
        $res = $res->where('enabled', 1)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->where('position_id', $arr['id'])
            ->limit($arr['num'])
            ->get()
            ->toArray();

        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['position_id'] != $arr['id']) {
                    continue;
                }

                switch ($row['media_type']) {
                    case 0: // 图片广告
                        $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                            $this->dscRepository->getImagePath($row['ad_code']) : $row['ad_code'];

                        $ads[] = $src;
                        break;
                }
            }
        }

        return $ads;
    }

    /**
     * 获得指定分类下的子分类的数组
     * @param $cat_id
     * @param $type
     * @return mixed
     */
    public function get_wholesale_child_cat($cat_id = 0, $type = 0)
    {
        if ($cat_id > 0) {
            $parent_id = WholesaleCat::select('parent_id')
                ->where('cat_id', $cat_id)
                ->limit(1)
                ->first();
            if ($parent_id != []) {
                $parent_id = $parent_id->toArray();
                $parent_id = $parent_id['parent_id'];
            }
        } else {
            $parent_id = 0;
        }

        //
        $cat_id = WholesaleCat::select('cat_id')
            ->where('parent_id', $parent_id)
            ->where('is_show', 1)
            ->limit(1)
            ->first();
        if ($cat_id != []) {
            $cat_id = $cat_id->toArray();
            $cat_id = $cat_id['cat_id'];
        }
        //

        if (!empty($cat_id) || $parent_id == 0) {
            /* 获取当前分类及其子分类 */
            $res = WholesaleCat::select('cat_id', 'cat_name', 'parent_id', 'is_show', 'style_icon', 'cat_alias_name')
                ->where('parent_id', $parent_id)
                ->where('is_show', 1)
                ->orderby('sort_order', "ASC")
                ->orderby('cat_id', "ASC")
                ->get()
                ->toArray();

            $cat_arr = [];
            foreach ($res as $row) {
                $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                $cat_arr[$row['cat_id']]['name'] = !empty($row['cat_alias_name']) ? $row['cat_alias_name'] : $row['cat_name'];
                $cat_arr[$row['cat_id']]['style_icon'] = $row['style_icon'];

                $cat_arr[$row['cat_id']]['url'] = url('purchase/index/list', ['id' => $row['cat_id']]);

                if (isset($row['cat_id']) != null) {
                    $cat_arr[$row['cat_id']]['cat_id'] = self::get_wholesale_child_tree($row['cat_id']);
                }
            }
        }
        return $cat_arr;
    }

    /**
     * 获取子分类
     */
    private function get_wholesale_child_tree($tree_id = 0, $ru_id = 0)
    {
        $three_arr = [];
        $res = WholesaleCat::where('parent_id', $tree_id)
            ->where('is_show', 1)
            ->count();

        if (!empty($res) || $tree_id == 0) {
            $res = WholesaleCat::select('cat_id', 'cat_name', 'parent_id', 'is_show')
                ->where('parent_id', $tree_id)
                ->where('is_show', 1)
                ->orderby('sort_order', "ASC")
                ->orderby('cat_id', "ASC")
                ->get()
                ->toArray();

            foreach ($res as $row) {
                if ($row['is_show']) {
                    $three_arr[$row['cat_id']]['id'] = $row['cat_id'];
                }
                $three_arr[$row['cat_id']]['name'] = $row['cat_name'];

                if ($ru_id) {
                    $build_uri = [
                        'cid' => $row['cat_id'],
                        'urid' => $ru_id,
                        'append' => $row['cat_name']
                    ];

                    $domain_url = $this->merchantCommonService->getSellerDomainUrl($ru_id, $build_uri);

                    $three_arr[$row['cat_id']]['url'] = $domain_url['domain_name'];
                } else {
                    $three_arr[$row['cat_id']]['url'] = url('purchase/index/list', ['id' => $row['cat_id']]);
                }

                if (isset($row['cat_id']) != null) {
                    $three_arr[$row['cat_id']]['cat_id'] = self::get_wholesale_child_tree($row['cat_id']);
                }
            }
        }
        return $three_arr;
    }


    /**
     * 获取限时抢购
     */
    public function get_wholesale_limit()
    {
        $now = $this->timeRepository->getGmTime();
        $res = Wholesale::where('enabled', 1)
            ->where('review_status', 3)
            ->where('is_promote', 1)
            ->where('start_time', '>=', $now)
            ->where('end_time', '<=', $now);

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'goods_thumb', 'goods_img', 'goods_unit');
            },
            'getWholesaleVolumePrice' => function ($query) {
                $query->selectRaw("goods_id, MIN(volume_number) AS volume_number, MAX(volume_price) AS volume_price");
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['formated_end_date'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['date_format'], $row['end_time']);
                $res[$key]['small_time'] = $row['end_time'] - $now;
                $res[$key]['goods_name'] = $row['get_goods']['goods_name'];
                $res[$key]['goods_price'] = $row['goods_price'];
                $res[$key]['moq'] = $row['moq'];
                $res[$key]['volume_number'] = empty($row['get_wholesale_volume_price']['volume_number']) ? $row['moq'] : $row['get_wholesale_volume_price']['volume_number'];
                $res[$key]['volume_price'] = empty($row['get_wholesale_volume_price']['volume_price']) ? $row['goods_price'] : $row['get_wholesale_volume_price']['volume_price'];
                $res[$key]['goods_unit'] = $row['get_goods']['goods_unit'];
                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['get_goods']['goods_thumb']);
                $res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['get_goods']['goods_thumb']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['get_goods']['goods_img']);
                $res[$key]['url'] = url('purchase/index/goods', ['id' => $row['act_id']]);
            }
        }

        return $res;
    }

    /**
     * 获得批发分类商品
     *
     */
    public function get_wholesale_cat()
    {
        $cat_res = WholesaleCat::where('parent_id', 0)->orderBy('sort_order', 'ASC')->get();
        $cat_res = $cat_res ? $cat_res->toArray() : [];

        if ($cat_res) {
            foreach ($cat_res as $key => $row) {
                $cat_res[$key]['goods'] = self::get_business_goods($row['cat_id']);
                $cat_res[$key]['count_goods'] = count(self::get_business_goods($row['cat_id']));
                $cat_res[$key]['cat_url'] = url('purchase/index/list', ['id' => $row['cat_id']]);
            }
        }

        return $cat_res;
    }

    /**
     * 获取分类下批发商品，并且进行分组
     */
    public function get_business_goods($cat_id)
    {
        $children = $this->categoryService->getWholesaleCatListChildren($cat_id);

        $res = Wholesale::where('enabled', 1)
            ->where('review_status', 3)
            ->whereIn('cat_id', $children)
            ->groupBy('goods_id');
        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'goods_img', 'goods_unit');
            },
            'getWholesaleVolumePrice' => function ($query) {
                $query->selectRaw("goods_id, MIN(volume_number) AS volume_number, MAX(volume_price) AS volume_price");
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['goods_extend'] = $this->wholesaleGoodsService->getWholesaleExtend($row['goods_id']);
                $res[$key]['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
                $res[$key]['goods_price'] = $row['goods_price'];
                $res[$key]['moq'] = $row['moq'];
                $res[$key]['volume_number'] = $row['get_wholesale_volume_price']['volume_number'];
                $res[$key]['volume_price'] = $row['get_wholesale_volume_price']['volume_price'];
                $res[$key]['goods_unit'] = $row['get_goods']['goods_unit'];
                $res[$key]['goods_name'] = $row['goods_name'];
                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['get_goods']['goods_thumb']);
                $res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['get_goods']['goods_thumb']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['get_goods']['goods_img']);
                $res[$key]['url'] = url('goods', ['id' => $row['act_id']]);
            }
        }

        return $res;
    }
    /** 获取限时抢购结束 */


    /** 商品列表页开始 */

    /**
     * 获取分类名
     * @param $cat_id
     * @return mixed
     */
    public function getCatName($cat_id)
    {
        return WholesaleCat::where('cat_id', $cat_id)->value('cat_name');
    }

    /**
     * 取得某页的批发商品
     * @param int $cat_id 分类ID
     * @param int $size 每页记录数
     * @param int $page 当前页
     * @return  array
     */
    public function get_wholesale_list($cat_id, $size, $page = 1)
    {
        $children = $this->categoryService->getWholesaleCatListChildren($cat_id);

        $res = Wholesale::where('enabled', 1)
            ->where('review_status', 3);

        if ($cat_id) {
            $res->whereIn('cat_id', $children);
        }

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'user_id', 'goods_name', 'shop_price', 'market_price');
            },
            'getWholesaleVolumePrice' => function ($queryprice) {
                $queryprice->selectRaw("goods_id, MIN(volume_number) AS volume_number, MAX(volume_price) AS volume_price");
            }
        ]);

        $count = $res;

        $count = $count->count();

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['get_goods']['goods_thumb']); //处理图片地址

                /*  判断当前商家是否允许"在线客服" start  */
                $shop_information = $this->merchantCommonService->getShopName($row['get_goods']['user_id']); //通过ru_id获取到店铺信息;
                $row['is_IM'] = empty($shop_information['is_im']) ? 0 : (int)$shop_information['is_im']; //平台是否允许商家使用"在线客服";
                //判断当前商家是平台,还是入驻商家 bylu
                if ($row['get_goods']['user_id'] == 0) {
                    //判断平台是否开启了IM在线客服

                    if (SellerShopinfo::where('ru_id', 0)->value('kf_im_switch')) {
                        $row['is_dsc'] = true;
                    } else {
                        $row['is_dsc'] = false;
                    }
                } else {
                    $row['is_dsc'] = false;
                }
                /* end  */

                $row['goods_url'] = url('goods', ['id' => $row['act_id']]);
                $properties = $this->goodsAttrService->getGoodsProperties($row['goods_id']);

                $goods_id = $row['goods_id'];
                $row['goods_attr'] = $properties['pro'];
                $goods_sale = WholesaleOrderInfo::where('main_order_id', '>', 0)
                    ->where('is_delete', 0)
                    ->with([
                        'getWholesaleOrderGoods' => function ($query) use ($goods_id) {
                            $query->select('order_id')->where('goods_id', $goods_id)->sum('goods_number');
                        }
                    ])
                    ->first();

                $goods_sale = $goods_sale ? $goods_sale->toArray() : 0;
                if ($goods_sale) {
                    $goods_sale = $goods_sale['get_wholesale_order_goods']['goods_number'];
                }

                $row['goods_sale'] = $goods_sale;
                $extend = WholesaleExtend::where('goods_id', $row['goods_id'])->first();
                $row['goods_extend'] = $extend ? $extend->toArray() : []; //获取批发商品标识
                $row['rz_shopName'] = $this->merchantCommonService->getShopName($row['get_goods']['user_id'], 1); //店铺名称
                $build_uri = [
                    'urid' => $row['get_goods']['user_id'],
                    'append' => $row['rz_shopName']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['get_goods']['user_id'], $build_uri);
                $row['store_url'] = $domain_url['domain_name'];
                $row['shop_price'] = $this->dscRepository->getPriceFormat($row['get_goods']['shop_price']);
                $row['market_price'] = $this->dscRepository->getPriceFormat($row['get_goods']['market_price']);
                if ($row['get_wholesale_volume_price']) {
                    $row['volume_number'] = $row['get_wholesale_volume_price']['volume_number'];
                    $row['volume_price'] = $row['get_wholesale_volume_price']['volume_price'];
                }
                $list[] = $row;
            }
        }

        return ['list' => $list, 'totalPage' => ceil($count / $size)];
    }


    /**
     * 搜索
     */
    public function get_search_goods_list($keyword, $page = 1, $size = 10)
    {
        /* 初始化搜索条件 */
        $keywords = '';
        $tag_where = '';
        if (!empty($keyword)) {
            $scws = new Scws4();
            $scws_res = $scws->segmentate($_REQUEST['keywords'], true);//这里可以把关键词分词：诺基亚，耳机
            $arr = explode(',', $scws_res);

            $goods_ids = [];

            foreach ($arr as $key => $val) {
                $val = mysql_like_quote(trim($val));
                $keywords .= " AND w.goods_name LIKE '%$val%' OR w.goods_price LIKE '%$val%' ";

                $sql = 'SELECT DISTINCT goods_id FROM ' . $GLOBALS['dsc']->table('tag') . " WHERE tag_words LIKE '%$val%' ";
                $res = $GLOBALS['db']->query($sql);
                foreach ($res as $row) {
                    $goods_ids[] = $row['goods_id'];
                }

                $local_time = $this->timeRepository->getLocalDate('Y-m-d');
                $searchengine = 'ECTouch';
                $loacl_keyword = addslashes(str_replace('%', '', $val));

                $count = Keywords::where('date', $local_time)
                    ->where('searchengine', $searchengine)
                    ->where('keyword', $loacl_keyword)
                    ->count();

                if ($count <= 0) {
                    $keywordOther = [
                        'date' => $local_time,
                        'searchengine' => $searchengine,
                        'keyword' => $loacl_keyword,
                        'count' => 1
                    ];

                    Keywords::insert($keywordOther);
                } else {
                    Keywords::where('date', $local_time)
                        ->where('searchengine', $searchengine)
                        ->where('keyword', $loacl_keyword)
                        ->increment('count');
                }
            }

            $goods_ids = array_unique($goods_ids);
            $tag_where = implode(',', $goods_ids);
            if (!empty($tag_where)) {
                $tag_where = 'OR g.goods_id ' . db_create_in($tag_where);
            }
        }
        /* 获得符合条件的商品总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale') . " AS w " .
            "WHERE w.enabled = 1 AND w.review_status = 3 " .
            $keywords . $tag_where;
        $count = $GLOBALS['db']->getOne($sql);
        $max_page = ($count > 0) ? ceil($count / $size) : 1;
        if ($page > $max_page) {
            $page = $max_page;
        }
        /* 查询商品 */
        $sql = "SELECT w.*, g.goods_thumb, g.goods_img, MIN(wvp.volume_number) AS volume_number, wvp.volume_price " .
            "FROM " . $GLOBALS['dsc']->table('wholesale') . " AS w "
            . " LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON w.goods_id = g.goods_id "
            . " LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_volume_price') . " AS wvp ON wvp.goods_id = g.goods_id "
            . "WHERE w.enabled = 1 AND w.review_status = 3 " .
            $keywords . $tag_where .
            " GROUP BY w.goods_id ORDER BY w.goods_id DESC ";
        $res = $GLOBALS['db']->SelectLimit($sql, $size, ($page - 1) * $size);
        $arr = [];

        foreach ($res as $row) {
            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($watermark_img != '') {
                $arr[$row['goods_id']]['watermark_img'] = $watermark_img;
            }

            $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
            $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
            $arr[$row['goods_id']]['goods_extend'] = $this->wholesaleGoodsService->getWholesaleExtend($row['goods_id']);
            $arr[$row]['goods_price'] = $row['goods_price'];
            $arr[$row]['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
            $arr[$row]['moq'] = $row['moq'];
            $arr[$row]['volume_number'] = $row['volume_number'];
            $arr[$row]['volume_price'] = $row['volume_price'];
            $arr[$row['goods_id']]['rz_shopName'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称
            $build_uri = [
                'urid' => $row['user_id'],
                'append' => $row['rz_shopName']
            ];

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
            $arr[$row['goods_id']]['store_url'] = $domain_url['domain_name'];

            $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
            $arr[$row['goods_id']]['goods_price'] = $row['goods_price'];
            $arr[$row['goods_id']]['moq'] = $row['moq'];
            $arr[$row['goods_id']]['volume_number'] = $row['volume_number'];
            $arr[$row['goods_id']]['volume_price'] = $row['volume_price'];
            $arr[$row['goods_id']]['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
            $arr[$row['goods_id']]['price_model'] = $row['price_model'];
            $arr[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $arr[$row['goods_id']]['goods_img'] = '../' . $row['goods_img'];
            $arr[$row['goods_id']]['url'] = url('goods', ['id' => $row['act_id']]);
        }
        return ['list' => $arr, 'totalPage' => ceil($count / $size)];
    }


    /** 商品列表页结束 */

    /** 批发商品页面开始 */

    /**
     * 通过批发ID获取批发商品详情
     */

    public function get_wholesale_goods_info($act_id)
    {
        $row = Wholesale::select('goods_id', 'goods_name', 'goods_type', 'rank_ids', 'price_model', 'goods_price', 'goods_number', 'moq', 'wholesale_cat_id')->where('act_id', $act_id);
        $row = $row->with([
            'getSuppliers'
        ]);
        $row = $this->baseRepository->getToArrayFirst($row);

        if ($row !== false) {
            $suppliers = $row['get_suppliers'];

            if ($row['price_model'] > 0) {
                $row['goods_price'] = WholesaleVolumePrice::where('goods_id', $row['goods_id'])->max('volume_price');
            }
            $row['goods_price_formatted'] = $this->dscRepository->getPriceFormat($row['goods_price']);
            $row['volume_price'] = self::get_wholesale_volume_price($row['goods_id']);

            // 只有PC详情
            if (empty($row['desc_mobile']) && !empty($row['goods_desc'])) {
                $desc_preg = $this->dscRepository->descImagesPreg($row['goods_desc']);
                $row['goods_desc'] = $desc_preg['goods_desc'];
            }
            // 手机端详情
            if (!empty($row['desc_mobile'])) {
                $desc_preg = $this->dscRepository->descImagesPreg($row['desc_mobile'], 'desc_mobile', 1);
                $row['goods_desc'] = $desc_preg['desc_mobile'];
            }

            $row['goods_extend'] = $this->wholesaleGoodsService->getWholesaleExtend($row['goods_id']); //获取批发商品标识
            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            $row['shopinfo'] = $this->merchantCommonService->getShopName($suppliers['user_id'], 2);
            $row['shopinfo']['logo_thumb'] = str_replace(['../'], '', $row['shopinfo']['logo_thumb']);
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                $row['goods_weight'] . '千克' :
                ($row['goods_weight'] * 1000) . '克';
            $brand_info = self::get_brand_url($row['brand_id']);
            $row['goods_brand_url'] = !empty($brand_info) ? $brand_info['url'] : '';
            $row['brand_thumb'] = !empty($brand_info) ? $brand_info['brand_logo'] : '';
            $row['rz_shopName'] = $this->merchantCommonService->getShopName($suppliers['user_id'], 1); //店铺名称
            $row['goods_unit'] = $row['goods_unit'];
            $build_uri = [
                'urid' => $suppliers['user_id'],
                'append' => $row['rz_shopName']
            ];

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($suppliers['user_id'], $build_uri);
            $row['store_url'] = $domain_url['domain_name'];
            $row['shopinfo']['brand_thumb'] = $this->dscRepository->getImagePath($row['shopinfo']['brand_thumb']);
        }

        return $row;
    }

    /**
     * 获得商品的属性和规格
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getWholesaleGoodsProperties($goods_id, $goods_attr_id = '', $attr_type = 0)
    {
        $attr_array = [];
        if (!empty($goods_attr_id)) {
            $attr_array = explode(',', $goods_attr_id);
        }

        /* 对属性进行重新排序和分组 */
        $grp = GoodsType::whereRaw(1);

        $grp = $grp->whereHas('getGoods', function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id);
        });

        $grp = $grp->value('attr_group');

        if (!empty($grp)) {
            $groups = explode("\n", strtr($grp, "\r", ''));
        }

        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');

        /* 获得商品的规格 */
        $res = WholesaleGoodsAttr::where('goods_id', $goods_id);

        if ($attr_type == 1 && !empty($goods_attr_id)) {
            $goods_attr_id = !is_array($goods_attr_id) ? explode(",", $goods_attr_id) : $goods_attr_id;

            $res = $res->whereIn('goods_attr_id', $goods_attr_id);
        }

        $res = $res->with('getGoodsAttribute');

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $val) {
                $attribute = $val['get_goods_attribute'];

                $res[$key]['attr_id'] = $attribute['attr_id'];
                $res[$key]['attr_name'] = $attribute['attr_name'];
                $res[$key]['attr_group'] = $attribute['attr_group'];
                $res[$key]['is_linked'] = $attribute['is_linked'];
                $res[$key]['attr_type'] = $attribute['attr_type'];
                $res[$key]['sort_order'] = $attribute['sort_order'];
            }
        }

        $res = $this->baseRepository->getSortBy($res, 'sort_order');

        $arr['pro'] = [];     // 属性
        $arr['spe'] = [];     // 规格
        $arr['lnk'] = [];     // 关联的属性

        foreach ($res as $row) {
            $row['attr_value'] = str_replace("\n", '<br />', $row['attr_value']);

            if ($row['attr_type'] == 0) {
                $group = (isset($groups[$row['attr_group']])) ? $groups[$row['attr_group']] : $GLOBALS['_LANG']['goods_attr'];

                $arr['pro'][$group][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['pro'][$group][$row['attr_id']]['value'] = $row['attr_value'];
            } else {
                if ($model_attr == 1) {
                    $attr_price = $row['warehouse_attr_price'];
                } elseif ($model_attr == 2) {
                    $attr_price = $row['area_attr_price'];
                } else {
                    $attr_price = $row['attr_price'];
                }

                $img_site = [
                    'attr_img_flie' => $row['attr_img_flie'],
                    'attr_img_site' => $row['attr_img_site']
                ];

                $attr_info = $this->goodsAttributeImgService->getHasAttrInfo($row['attr_id'], $row['attr_value'], $img_site);
                if ($attr_info) {
                    $row['img_flie'] = !empty($attr_info['attr_img']) ? $this->dscRepository->getImagePath($attr_info['attr_img']) : '';
                    $row['img_site'] = $attr_info['attr_site'];
                } else {
                    $row['img_flie'] = '';
                    $row['img_site'] = '';
                }

                $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
                $arr['spe'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['spe'][$row['attr_id']]['values'][] = [
                    'label' => $row['attr_value'],
                    'img_flie' => $row['img_flie'],
                    'img_site' => $row['img_site'],
                    'checked' => $row['attr_checked'],
                    'attr_sort' => $row['attr_sort'],
                    'combo_checked' => $this->commonRepository->getComboGodosAttr($attr_array, $row['goods_attr_id']),
                    'price' => $attr_price,
                    'format_price' => $this->dscRepository->getPriceFormat(abs($attr_price), false),
                    'id' => $row['goods_attr_id']
                ];
            }

            if ($row['is_linked'] == 1) {
                /* 如果该属性需要关联，先保存下来 */
                $arr['lnk'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['lnk'][$row['attr_id']]['value'] = $row['attr_value'];
            }

            $arr['spe'][$row['attr_id']]['values'] = $this->baseRepository->getSortBy($arr['spe'][$row['attr_id']]['values'], 'attr_sort');
            $arr['spe'][$row['attr_id']]['is_checked'] = $this->commonRepository->getAttrValues($arr['spe'][$row['attr_id']]['values']);
        }

        return $arr;
    }

    /**
     * 获取批发阶梯价
     */

    public function get_wholesale_volume_price($goods_id = 0, $goods_number = 0)
    {
        $res = Wholesale::select('price_model', 'goods_price')->where('goods_id', $goods_id)->get()->first();
        $res = $res ? $res->toArray() : [];

        $res['volume_price'] = [];

        if ($res) {
            if ($res['price_model']) {
                $res['volume_price'] = WholesaleVolumePrice::select('volume_price', 'volume_number')
                    ->where('goods_id', $goods_id)
                    ->orderBy('volume_number', 'asc')
                    ->get()
                    ->toArray();
                //设置数量阶段
                if ($res['volume_price']) {
                    foreach ($res['volume_price'] as $key => $val) {
                        if ($key < count($res['volume_price']) - 1) {
                            $range_number = $res['volume_price'][$key + 1]['volume_number'] - 1;
                            $res['volume_price'][$key]['range_number'] = $range_number;
                        }
                        if ($goods_number >= $val['volume_number']) {
                            $res['volume_price'][$key]['is_reached'] = 1; //当前达成
                            if (isset($res['volume_price'][$key - 1]['is_reached'])) {
                                unset($res['volume_price'][$key - 1]['is_reached']);
                            }
                        }
                    }
                }
            }
        }


        return $res['volume_price'];
    }

    /**
     * 判断会员是否有购买权利
     */
    public function isJurisdiction($goods, $user_id = 0)
    {
        $is_jurisdiction = 0;

        if ($user_id > 0) {
            //判断是否是商家
            $seller_id = AdminUser::where('ru_id', $user_id)->value('user_id');
            if ($seller_id > 0) {
                $is_jurisdiction = 1;
            } else {
                //判断是否设置了普通会员
                if ($goods['rank_ids']) {
                    $rank_ids = explode(',', $goods['rank_ids']);
                    $user_rank = Users::where('user_id', $user_id)->value('user_rank');
                    if (in_array($user_rank, $rank_ids)) {
                        $is_jurisdiction = 1;
                    }
                }
            }
        } else {
            $is_jurisdiction = 1;
        }

        return $is_jurisdiction;
    }

    /**
     * 品牌信息
     */
    public function get_brand_url($brand_id = 0)
    {
        $res = Brand::select('brand_id', 'brand_name', 'brand_logo')->where('brand_id', $brand_id)->get()->first();
        $res = $res ? $res->toArray() : [];
        if ($res) {
            $res['url'] = $this->dscRepository->buildUri('brand', ['bid' => $res['brand_id']], $res['brand_name']);
            $res['brand_logo'] = empty($res['brand_logo']) ? str_replace(['../'], '', $GLOBALS['_CFG']['no_brand']) : '/brandlogo/' . $res['brand_logo'];
            //OSS文件存储ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $res['brand_logo'] = $bucket_info['endpoint'] . $res['brand_logo'];
            }
            //OSS文件存储ecmoban模板堂 --zhuo end
        }

        return $res;
    }

    //获取购物车商品数量和价格
    public function wholesale_cart_info($goods_id = 0, $rec_ids = '')
    {
        if (!empty(session('user_id'))) {
            $sess_id = " c.user_id = '" . session('user_id') . "' ";
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $sess_id = " c.session_id = '$session_id' ";
        }

        if (!empty($goods_id)) {
            $sess_id .= " AND c.goods_id = '$goods_id' ";
        }

        if (!empty($rec_ids)) {
            $sess_id .= " AND c.rec_id IN ($rec_ids) ";
        }

        $cart_info = array(
            'rec_count' => 0,
            'total_number' => 0,
            'total_price' => 0.00,
            'total_price_formatted' => ''
        );
        $sql = " SELECT goods_price, goods_number FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c WHERE $sess_id ";
        $data = $GLOBALS['db']->getAll($sql);
        foreach ($data as $key => $val) {
            $cart_info['rec_count'] += 1;
            $cart_info['total_number'] += $val['goods_number'];
            $total_price = $val['goods_number'] * $val['goods_price'];
            $cart_info['total_price'] += $total_price;
            $cart_info['goods_price'] = $val['goods_price'];
        }
        $cart_info['total_price_formatted'] = $this->dscRepository->getPriceFormat($cart_info['total_price']);
        return $cart_info;
    }

    /**
     * 购物车信息
     */
    public function get_wholesale_cart_info($user_id = 0)
    {
        $row = WholesaleCart::select('rec_id', 'goods_name', 'goods_attr_id', 'goods_price', 'goods_number', 'goods_price');

        if ($user_id) {
            $row = $row->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = $row->where('session_id', $session_id);
        }

        $row = $row->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            },
            'getWholesale' => function ($query) {
                $query->select('goods_id', 'act_id');
            }
        ]);

        $row = $row->get();

        $row = $row ? $row->toArray() : [];

        $arr = [];
        $cart_value = '';
        foreach ($row as $k => $v) {
            $arr[$k]['rec_id'] = $v['rec_id'];
            $arr[$k]['url'] = $this->dscRepository->buildUri('wholesale_goods', ['aid' => $v['get_wholesale']['act_id']], $v['goods_name']);
            $arr[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['get_goods']['goods_thumb']);
            $arr[$k]['goods_number'] = $v['goods_number'];
            $arr[$k]['goods_price'] = $v['goods_price'];
            $arr[$k]['goods_name'] = $v['goods_name'];
            @$arr[$k]['goods_attr'] = array_values(get_wholesale_attr_array($v['goods_attr_id']));
            $cart_value = !empty($cart_value) ? $cart_value . ',' . $v['rec_id'] : $v['rec_id'];
        }

        $row = WholesaleCart::selectRaw("COUNT(rec_id) AS cart_number, SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount");

        if ($user_id) {
            $row = $row->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = $row->where('session_id', $session_id);
        }

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        if ($row) {
            $cart_number = intval($row['cart_number']);
            $number = intval($row['number']);
            $amount = $this->dscRepository->getPriceFormat(floatval($row['amount']));
        } else {
            $cart_number = 0;
            $number = 0;
            $amount = 0;
        }

        return [
            'cart_number' => $cart_number,
            'cart_value' => $cart_value,
            'number' => $number,
            'amount' => $amount,
            'goods' => $arr
        ];
    }

    /**
     * 获取主属性列表
     */
    public function getWholesaleMainAttrList($goods_id = 0, $attr = [])
    {
        $goods_type = Wholesale::where('goods_id', $goods_id)->value('goods_type');
        $goods_type = $goods_type ? $goods_type : 0;

        //获取设置属性
        $attr_ids = WholesaleGoodsAttr::where('goods_id', $goods_id)->pluck('attr_id');
        $attr_ids = $attr_ids ? $attr_ids->toArray() : [];

        if (!empty($attr_ids)) {
            // $attr_ids = implode(',', $attr_ids);
            //获取主属性组
            $attr_id = Attribute::where('cat_id', $goods_type)->whereIn('attr_id', $attr_ids)->orderByRaw("sort_order DESC, attr_id DESC")->value('attr_id');
            $data = WholesaleGoodsAttr::select('goods_attr_id', 'attr_value')->where('goods_id', $goods_id)->where('attr_id', $attr_id)->orderBy('goods_attr_id');
            $data = $data->get();
            $data = $data ? $data->toArray() : [];

            //处理货品数据
            if ($data) {
                foreach ($data as $key => $val) {
                    $new_arr = array_merge($attr, [$val['goods_attr_id']]);
                    $data[$key]['attr_group'] = implode(',', $new_arr); //属性组合
                    $set = $this->get_find_in_set($new_arr);
                    $product_info = WholesaleProducts::select('product_number')->whereRaw("goods_id = '$goods_id' $set ")->first();
                    $product_info = $product_info ? $product_info->toArray() : [];
                    $data[$key] = array_merge($data[$key], $product_info);

                    if (empty($data[$key]) || empty($product_info)) {
                        unset($data[$key]);
                    }
                }
                return $data;
            }
        }

        return false;
    }

    /** 批发商品页面结束  */


    /** 批发购物车开始 */
    /**
     * 获取批发购物车商品列表
     * @param int $goods_id
     * @param string $rec_ids
     * @return array
     */
    public function wholesale_cart_goods($goods_id = 0, $rec_ids = '')
    {
        if (!empty(session('user_id'))) {
            $sess_id = " c.user_id = '" . session('user_id') . "' ";
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $sess_id = " c.session_id = '$session_id' ";
        }

        if (!empty($goods_id)) {
            $sess_id .= " AND c.goods_id = '$goods_id' ";
        }

        if (!empty($rec_ids)) {
            $sess_id .= " AND c.rec_id IN ($rec_ids) ";
        }

        $cart_goods = [];
        //区分商家
        $sql = " SELECT DISTINCT ru_id FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c WHERE $sess_id ";
        $ru_ids = $GLOBALS['db']->getCol($sql);
        foreach ($ru_ids as $key => $val) {
            $data = [];
            $data['ru_id'] = $val;
            $data['shop_name'] = $this->merchantCommonService->getShopName($val, 1);

            /* 客服部分 start */
            /*  @author-bylu 判断当前商家是否允许"在线客服" start */
            $shop_information = $this->merchantCommonService->getShopName($val); //通过ru_id获取到店铺信息;
            $data['is_IM'] = $shop_information['is_IM']; //平台是否允许商家使用"在线客服";
            //判断当前商家是平台,还是入驻商家 bylu
            if ($val == 0) {
                //判断平台是否开启了IM在线客服
                if ($GLOBALS['db']->getOne("SELECT kf_im_switch FROM " . $GLOBALS['dsc']->table('seller_shopinfo') . "WHERE ru_id = 0", true)) {
                    $data['is_dsc'] = true;
                } else {
                    $data['is_dsc'] = false;
                }
            } else {
                $data['is_dsc'] = false;
            }
            /*  @author-bylu  end */
            //自营有自提点--val=ru_id
            $sql = "select * from " . $GLOBALS['dsc']->table('seller_shopinfo') . " where ru_id='" . $val . "'";
            $basic_info = $GLOBALS['db']->getRow($sql);

            $chat = $this->dscRepository->chatQq($basic_info);
            $data['kf_type'] = $chat['kf_type'];
            $data['kf_ww'] = $chat['kf_ww'];
            $data['kf_qq'] = $chat['kf_qq'];

            //区分商品
            $sql = " SELECT DISTINCT goods_id FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c WHERE $sess_id AND c.ru_id = '$val' ";
            $goods_ids = $GLOBALS['db']->getCol($sql);
            foreach ($goods_ids as $a => $g) {
                //先更新购物车数据
                calculate_cart_goods_price($g, $rec_ids);
                //查询购物车数据
                $sql = " SELECT c.rec_id, c.goods_price, c.goods_number, c.goods_attr_id " .
                    " FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c " .
                    " WHERE $sess_id AND c.ru_id = '$val' AND c.goods_id = '$g' ORDER BY c.goods_attr_id"; //按属性序号排序
                $res = $GLOBALS['db']->getAll($sql);
                //商品属性数据
                $total_number = 0;
                $total_price = 0;
                foreach ($res as $k => $v) {
                    $res[$k]['goods_price_formatted'] = $this->dscRepository->getPriceFormat($v['goods_price']);
                    $res[$k]['total_price'] = $v['goods_price'] * $v['goods_number'];
                    $res[$k]['total_price_formatted'] = $this->dscRepository->getPriceFormat($res[$k]['total_price']);
                    $res[$k]['goods_attr'] = get_goods_attr_array($v['goods_attr_id']);
                    //统计数量和价格
                    $total_number += $v['goods_number'];
                    $total_price += $res[$k]['total_price'];
                }
                //补充商品数据
                $goods_data = get_table_date('wholesale', "goods_id='$g'", ['act_id', 'goods_id, goods_name, price_model, goods_price', 'moq', 'goods_number']);
                $sql = " select goods_thumb from " . $GLOBALS['dsc']->table('goods') . "  where goods_id='$goods_data[goods_id]'";
                $goods_thumb = $GLOBALS['db']->getOne($sql);
                $goods_data['goods_thumb'] = $this->dscRepository->getImagePath($goods_thumb);
                $goods_data['total_number'] = $total_number;
                $goods_data['total_price'] = $total_price;
                $goods_data['goods_number'] = empty($goods_data['goods_number']) ? 1 : $goods_data['goods_number'];
                if (empty($goods_data['price_model'])) {
                    if ($total_number >= $goods_data['moq']) {
                        $goods_data['is_reached'] = 1;
                    }
                } else {
                    $goods_data['volume_price'] = get_wholesale_volume_price($g, $total_number);
                }

                $volume_number = [];
                foreach ($goods_data['volume_price'] as $k => $v) {
                    array_push($volume_number, $v['volume_number']);
                }
                sort($volume_number);

                $goods_data['list'] = $res;
                $goods_data['min_number'] = $goods_data['moq'];  // 最小起批量
                $product_info = get_table_date('wholesale_products', "goods_id='$g'", ['product_number']);
                $goods_data['max_number'] = ($product_info['product_number'] > 0) ? $product_info['product_number'] : $goods_data['goods_number'];  // 最大起批量

                $goods_data['count'] = count($res); //记录数量
                $data['goods_list'][] = $goods_data;
            }
            $cart_goods[] = $data;
        }

        return $cart_goods;
    }

    /**
     * 查询购物车数据
     */
    public function cartInfo($rec_id)
    {
        if (!empty(session('user_id'))) {
            $sess_id = " c.user_id = '" . session('user_id') . "' ";
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $sess_id = " c.session_id = '$session_id' ";
        }
        if (!empty($goods_id)) {
            $sess_id .= " AND c.goods_id = '$goods_id' ";
        }

        if (!empty($rec_id)) {
            $sess_id .= " AND c.rec_id = {$rec_id} ";
        }

        //区分商家
        $sql = " SELECT DISTINCT ru_id FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c WHERE $sess_id ";
        $ru_ids = $GLOBALS['db']->getCol($sql);

        foreach ($ru_ids as $key => $val) {
            //区分商品
            $sql = " SELECT DISTINCT goods_id FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c WHERE $sess_id AND c.ru_id = '$val' ";
            $goods_ids = $GLOBALS['db']->getCol($sql);
            $goods_id = $goods_ids[0];

            //查询购物车数据
            $sql = " SELECT c.rec_id, c.goods_price, c.goods_number, c.goods_attr_id " .
                " FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c " .
                " WHERE $sess_id AND c.ru_id = '$val' AND c.goods_id = '$goods_id' ORDER BY c.goods_attr_id"; //按属性序号排序
            $res = $GLOBALS['db']->getAll($sql);

            //商品属性数据
            $total_number = 0;
            $total_price = 0;
            foreach ($res as $k => $v) {
                $res[$k]['goods_price_formatted'] = $this->dscRepository->getPriceFormat($v['goods_price']);
                $res[$k]['total_price'] = $v['goods_price'] * $v['goods_number'];
                $res[$k]['total_price_formatted'] = $this->dscRepository->getPriceFormat($res[$k]['total_price']);
                $res[$k]['goods_attr'] = get_goods_attr_array($v['goods_attr_id']);
                //统计数量和价格
                $total_number += $v['goods_number'];
                $total_price += $res[$k]['total_price'];
            }
        }

        if (empty($res)) {
            $list = [];
        } else {
            $list = [
                'rec_id' => $res[0]['rec_id'],
                'total_price' => $res[0]['total_price'],
                'total_price_formatted' => $res[0]['total_price_formatted'],
            ];
        }

        return $list;
    }

    /**
     * 统计购物车商品总数
     */
    public function get_count_cart()
    {
        if (!empty(session('user_id'))) {
            $sess_id = " c.user_id = '" . session('user_id') . "' ";
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $sess_id = " c.session_id = '$session_id' ";
        }

        $sql = " SELECT SUM(goods_number) " .
            " FROM " . $GLOBALS['dsc']->table('wholesale_cart') . " AS c " .
            " WHERE $sess_id  ORDER BY c.goods_attr_id"; //按属性序号排序
        $res = $GLOBALS['db']->getOne($sql);
        return $res;
    }

    /** 批发购物车结束 */

    //货品查询语句处理
    public function get_find_in_set($attr = [], $col = 'goods_attr', $sign = '|')
    {
        $set = "";
        foreach ($attr as $key => $val) {
            $set .= " AND FIND_IN_SET('$val', REPLACE($col, '$sign', ',')) ";
        }
        return $set;
    }
}
