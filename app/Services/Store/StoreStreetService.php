<?php

namespace App\Services\Store;

use App\Libraries\Pager;
use App\Models\CollectStore;
use App\Models\Comment;
use App\Models\Goods;
use App\Models\MerchantsShopInformation;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 商城店铺街
 * Class Store
 * @package App\Services
 */
class StoreStreetService
{
    protected $goodsService;
    protected $baseRepository;
    protected $config;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 查询所有商家的顶级分类
     *
     * @access  public
     * @param int $cat_id
     * @return  array
     */
    public function getCatStoreList($cat_id = 0)
    {
        $res = MerchantsShopInformation::selectRaw('user_shopMain_category AS user_cat, user_id')->where('user_shopMain_category', '<>', '')->where('merchants_audit', 1)->get();
        $res = $res ? $res->toArray() : [];

        $user_id = '';
        if ($res) {
            $arr = [];
            foreach ($res as $key => $row) {
                $row['cat_str'] = '';
                $row['user_cat'] = explode('-', $row['user_cat']);

                foreach ($row['user_cat'] as $uck => $ucrow) {
                    if ($ucrow) {
                        $row['user_cat'][$uck] = explode(':', $ucrow);
                        if (!empty($row['user_cat'][$uck][0])) {
                            $row['cat_str'] .= $row['user_cat'][$uck][0] . ",";
                        }
                    }
                }

                if ($row['cat_str']) {
                    $row['cat_str'] = substr($row['cat_str'], 0, -1);
                    $row['cat_str'] = explode(',', $row['cat_str']);
                    if (in_array($cat_id, $row['cat_str']) || $cat_id == 0) {
                        $user_id .= $row['user_id'] . ",";
                    }
                }

                $arr[] = $row;
            }

            if ($user_id) {
                $user_id = substr($user_id, 0, -1);
            }
        }

        return $user_id;
    }

    /**
     * 店铺搜索数量
     *
     * @access  public
     * @param string $keywords
     * @param string $sort
     * @param int $store_province
     * @param int $store_city
     * @param int $store_district
     * @param string $store_user
     * @param int $libType
     * @return  Number
     */
    public function getStoreShopCount($keywords = '', $sort = 'shop_id', $store_province = 0, $store_city = 0, $store_district = 0, $store_user = '', $libType = 0)
    {
        $res = MerchantsShopInformation::where('merchants_audit', 1)->where('shop_close', 1);

        $keywords = !empty($keywords) ? addslashes(trim($keywords)) : '';
        if (!empty($keywords)) {
            $keywords = mysql_like_quote($keywords);

            /* 店铺名称 start */
            $shop_list = MerchantsShopInformation::selectRaw("GROUP_CONCAT(user_id) AS user_id")
                ->where(function ($query) use ($keywords) {
                    $query->where('shoprz_brandName', 'like', "%$keywords%")
                        ->orWhere('shopNameSuffix', 'like', "%$keywords%")
                        ->orWhere('rz_shopName', 'like', "%$keywords%")
                        ->orWhereRaw("CONCAT(shoprz_brandName, shopNameSuffix) LIKE '%$keywords%'");
                });

            $shop_list = $shop_list->first();
            $shop_list = $shop_list ? $shop_list->toArray() : [];
            $shop_list = $shop_list ? $shop_list['user_id'] : 0;

            if ($shop_list) {
                $shop_list = explode(",", $shop_list);
                $shop_list = array_unique($shop_list);
            }
            /* 店铺名称 end */

            /* 店铺商品名称 start */
            $scws_res = scws($keywords, 5); //这里可以把关键词分词：诺基亚，耳机
            $arr = explode(',', $scws_res);

            $arr1[] = $keywords;

            if ($arr1 && is_array($arr)) {
                $arr = array_merge($arr1, $arr);
            }

            $operator = " OR ";
            $goods_keywords = '(';
            $goods_ids = [];
            foreach ($arr as $key => $val) {
                $val = !empty($val) ? addslashes($val) : '';

                if ($val) {
                    if ($key > 0 && $key < count($arr) && count($arr) > 1) {
                        $goods_keywords .= $operator;
                    }

                    $val = mysql_like_quote(trim($val));
                    $goods_keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%')";
                }
            }
            $goods_keywords .= ')';

            $goods_user = Goods::selectRaw("GROUP_CONCAT(user_id) AS user_id")->where('user_id', '>', 0)
                ->whereRaw($goods_keywords);

            if ($this->config['review_goods'] == 1) {
                $goods_user = $goods_user->where('review_status', '>', 2);
            }

            $goods_user = $goods_user->first();
            $goods_user = $goods_user ? $goods_user->toArray() : [];
            $goods_user = $goods_user ? $goods_user['user_id'] : 0;

            if ($goods_user) {
                $goods_user = explode(",", $goods_user);
                $goods_user = array_unique($goods_user);
            }
            /* 店铺商品名称 end */

            $user_list = [];
            if ($shop_list && $goods_user) {
                $user_list = array_merge($user_list, $shop_list, $goods_user);
            } elseif ($shop_list) {
                $user_list = $shop_list;
            } elseif ($goods_user) {
                $user_list = $goods_user;
            }

            $user_list = !empty($user_list) ? array_unique($user_list) : '';
            $user_list = !empty($user_list) ? implode(",", $user_list) : '';

            if (!empty($user_list)) {
                $user_list = $this->dscRepository->delStrComma($user_list);

                $user_list = !is_array($user_list) ? explode(",", $user_list) : [];
                $res = $res->whereIn('user_id', $user_list);
            } else {
                $res = $res->where('user_id', '>', 0);
            }
        } else {
            if ($store_user) {
                $store_user = $this->dscRepository->delStrComma($store_user);
                $store_user = !is_array($store_user) ? explode(",", $store_user) : [];

                $res = $res->whereIn('user_id', $store_user);
            }
        }

        $shop_where = [
            'store_province' => $store_province,
            'store_city' => $store_city,
            'store_district' => $store_district,
        ];

        if ($store_province > 0 || $store_city > 0 || $store_district > 0) {
            $res = $res->whereHas('getSellerShopinfo', function ($query) use ($shop_where) {
                if ($shop_where['store_province'] > 0) {
                    $query->where('province', $shop_where['store_province']);
                }

                if ($shop_where['store_city'] > 0) {

                    $query->where('city', $shop_where['store_city']);
                }

                if ($shop_where['store_district'] > 0) {

                    $query->where('district', $shop_where['store_district']);
                }
            });
        }

        if ($libType == 0) {
            $res = $res->where('is_street', 1);
        }

        $count = $res->count();

        return $count;
    }

    /**
     * 店铺搜索列表
     *
     * @access  public
     * @param int $libType
     * @param string $keywords
     * @param int $count
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $store_province
     * @param int $store_city
     * @param int $store_district
     * @param string $store_user
     * @return  array
     */
    public function getStoreShopList($libType = 0, $keywords = '', $count = 0, $size = 16, $page = 1, $sort = 'shop_id', $order = 'DESC', $warehouse_id = 0, $area_id = 0, $area_city = 0, $store_province = 0, $store_city = 0, $store_district = 0, $store_user = '', $goods_type = 0)
    {
        $id = '"';
        if ($keywords) {
            $id .= "keywords-" . $keywords . "|";
        }

        if ($warehouse_id) {
            $id .= "warehouse_id-" . $warehouse_id . "|";
        }

        if ($area_id) {
            $id .= "area_id-" . $area_id . "|";
        }

        if ($area_city) {
            $id .= "area_city-" . $area_city . "|";
        }

        if ($store_province) {
            $id .= "store_province-" . $store_province . "|";
        }

        if ($store_city) {
            $id .= "store_city-" . $store_city . "|";
        }

        if ($store_district) {
            $id .= "store_district-" . $store_district . "|";
        }

        if ($sort) {
            $id .= "sort-" . $sort . "|";
        }

        if ($order) {
            $id .= "order-" . $order . "|";
        }

        if ($store_user) {
            $id .= "store_user-" . $store_user . "|";
        }

        $substr = substr($id, -1);
        if ($substr == "|") {
            $id = substr($id, 0, -1);
        }

        $id .= '"';

        $pagerParams = [
            'total' => $count,
            'listRows' => $size,
            'id' => $id,
            'page' => $page,
            'funName' => 'store_shop_gotoPage',
            'pageType' => 1,
            'libType' => $libType
        ];
        $store_shop = new Pager($pagerParams);
        $limit = $store_shop->limit;
        $pager = $store_shop->fpage([0, 4, 5, 6, 9]);

        $res = MerchantsShopInformation::where('merchants_audit', 1)->where('shop_close', 1);

        $keywords = !empty($keywords) ? addslashes(trim($keywords)) : '';
        if (!empty($keywords)) {
            $keywords = mysql_like_quote($keywords);

            /* 店铺名称 start */
            $shop_list = MerchantsShopInformation::selectRaw("GROUP_CONCAT(user_id) AS user_id")
                ->where(function ($query) use ($keywords) {
                    $query->where('shoprz_brandName', 'like', "%$keywords%")
                        ->orWhere('shopNameSuffix', 'like', "%$keywords%")
                        ->orWhere('rz_shopName', 'like', "%$keywords%")
                        ->orWhereRaw("CONCAT(shoprz_brandName, shopNameSuffix) LIKE '%$keywords%'");
                });

            $shop_list = $shop_list->first();
            $shop_list = $shop_list ? $shop_list->toArray() : [];
            $shop_list = $shop_list ? $shop_list['user_id'] : 0;

            if ($shop_list) {
                $shop_list = explode(",", $shop_list);
                $shop_list = array_unique($shop_list);
            }
            /* 店铺名称 end */

            /* 店铺商品名称 start */
            $scws_res = scws($keywords, 5); //这里可以把关键词分词：诺基亚，耳机
            $arr = explode(',', $scws_res);

            $arr1[] = $keywords;

            if ($arr1 && is_array($arr)) {
                $arr = array_merge($arr1, $arr);
            }

            $operator = " OR ";
            $goods_keywords = '(';
            $goods_ids = [];
            foreach ($arr as $key => $val) {
                $val = !empty($val) ? addslashes($val) : '';

                if ($val) {
                    if ($key > 0 && $key < count($arr) && count($arr) > 1) {
                        $goods_keywords .= $operator;
                    }

                    $val = mysql_like_quote(trim($val));
                    $goods_keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%')";
                }
            }
            $goods_keywords .= ')';

            $goods_user = Goods::selectRaw("GROUP_CONCAT(user_id) AS user_id")->where('user_id', '>', 0)
                ->whereRaw($goods_keywords);

            if ($this->config['review_goods'] == 1) {
                $goods_user = $goods_user->where('review_status', '>', 2);
            }

            $goods_user = $goods_user->first();
            $goods_user = $goods_user ? $goods_user->toArray() : [];
            $goods_user = $goods_user ? $goods_user['user_id'] : 0;

            if ($goods_user) {
                $goods_user = explode(",", $goods_user);
                $goods_user = array_unique($goods_user);
            }
            /* 店铺商品名称 end */

            $user_list = [];
            if ($shop_list && $goods_user) {
                $user_list = array_merge($user_list, $shop_list, $goods_user);
            } elseif ($shop_list) {
                $user_list = $shop_list;
            } elseif ($goods_user) {
                $user_list = $goods_user;
            }

            $user_list = !empty($user_list) ? array_unique($user_list) : '';
            $user_list = !empty($user_list) ? implode(",", $user_list) : '';

            if (!empty($user_list)) {
                $user_list = $this->dscRepository->delStrComma($user_list);

                $user_list = !is_array($user_list) ? explode(",", $user_list) : [];
                $res = $res->whereIn('user_id', $user_list);
            } else {
                $res = $res->where('user_id', '>', 0);
            }
        } else {
            if ($store_user) {
                $store_user = $this->dscRepository->delStrComma($store_user);
                $store_user = !is_array($store_user) ? explode(",", $store_user) : [];

                $res = $res->whereIn('user_id', $store_user);
            }
        }

        $shop_where = [
            'store_province' => $store_province,
            'store_city' => $store_city,
            'store_district' => $store_district,
        ];

        if ($store_province > 0 || $store_city > 0 || $store_district > 0) {
            $res = $res->whereHas('getSellerShopinfo', function ($query) use ($shop_where) {
                if ($shop_where['store_province'] > 0) {
                    $query->where('province', $shop_where['store_province']);
                }

                if ($shop_where['store_city'] > 0) {
                    $query->where('city', $shop_where['store_city']);
                }

                if ($shop_where['store_district'] > 0) {
                    $query->where('district', $shop_where['store_district']);
                }
            });
        }

        if ($libType == 0) {
            $res = $res->where('is_street', 1);
        }

        if ($sort == 'sales_volume') {
        } elseif ($sort == 'goods_number') {
        } else {
            $res = $res->orderBy($sort, $order);
        }

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $arr[$row['shop_id']]['shop_id'] = $row['shop_id'];
                $arr[$row['shop_id']]['shoprz_brandName'] = $row['shoprz_brandName'];
                $arr[$row['shop_id']]['shopNameSuffix'] = $row['shopNameSuffix'];
                $arr[$row['shop_id']]['self_run'] = $row['self_run'];
                $arr[$row['shop_id']]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 3); //店铺名称
                $arr[$row['shop_id']]['shopName'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称
                $arr[$row['shop_id']]['brand_list'] = get_shop_brand_list($row['user_id']); //商家品牌
                $arr[$row['shop_id']]['address'] = get_shop_address_info($row['user_id']); //商家所在位置
                $arr[$row['shop_id']]['sales_volume'] = !empty($row['sales_volume']) ? $row['sales_volume'] : 0;

                $grade_info = get_seller_grade($row['user_id']);
                $arr[$row['shop_id']]['grade_img'] = $this->dscRepository->getImagePath($grade_info['grade_img']);
                $arr[$row['shop_id']]['grade_name'] = $this->dscRepository->getImagePath($grade_info['grade_name']);

                $shop_info = get_shop_info_content($row['user_id']);
                $arr[$row['shop_id']]['shop_logo'] = str_replace('../', '', $shop_info['shop_logo']); //商家logo
                $arr[$row['shop_id']]['logo_thumb'] = str_replace('../', '', $shop_info['logo_thumb']); //商家缩略图

                $arr[$row['shop_id']]['shop_logo'] = $this->dscRepository->getImagePath($arr[$row['shop_id']]['shop_logo']);
                $arr[$row['shop_id']]['logo_thumb'] = $this->dscRepository->getImagePath($arr[$row['shop_id']]['logo_thumb']);
                $arr[$row['shop_id']]['street_thumb'] = $this->dscRepository->getImagePath($shop_info['street_thumb']);
                $arr[$row['shop_id']]['brand_thumb'] = $this->dscRepository->getImagePath($shop_info['brand_thumb']);

                $arr[$row['shop_id']]['street_desc'] = $shop_info['street_desc']; //店铺街描述
                $arr[$row['shop_id']]['merch_cmt'] = $this->commentService->getMerchantsGoodsComment($row['user_id']); //商家总体评分
                $arr[$row['shop_id']]['shopNameSuffix'] = $row['shopNameSuffix'];
                $arr[$row['shop_id']]['ru_id'] = $row['user_id'];

                $build_uri = [
                    'urid' => $row['user_id'],
                    'append' => $arr[$row['shop_id']]['shop_name']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                $arr[$row['shop_id']]['shop_url'] = $domain_url['domain_name'];

                $arr[$row['shop_id']]['store_shop_url'] = $this->dscRepository->buildUri('merchants_store_shop', ['urid' => $row['user_id']], $arr[$row['shop_id']]['shop_name']);

                if ($goods_type == 0) {
                    $arr[$row['shop_id']]['goods_count'] = $this->getShopGoodsCountList($row['user_id'], $warehouse_id, $area_id, $area_city); //商品数量
                    $arr[$row['shop_id']]['goods_list'] = $this->getShopGoodsCountList($row['user_id'], $warehouse_id, $area_id, $area_city, 1); //商品数量
                }

                /* 获取是否关注 */
                $arr[$row['shop_id']]['collect_store'] = 0;
                if (session('user_id') > 0) {
                    $arr[$row['shop_id']]['collect_store'] = CollectStore::where('user_id', session('user_id'))->where('ru_id', $row['user_id'])->value('rec_id');
                }

                /* 处理客服相关代码 start */

                $basic_info = SellerShopinfo::where('ru_id', $row['user_id'])->first();
                $basic_info = $basic_info ? $basic_info->toArray() : [];

                $chat = $this->dscRepository->chatQq($basic_info);
                $arr[$row['shop_id']]['kf_type'] = $chat['kf_type'];
                $arr[$row['shop_id']]['kf_ww'] = $chat['kf_ww'];
                $arr[$row['shop_id']]['kf_qq'] = $chat['kf_qq'];

                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                $shop_information = $this->merchantCommonService->getShopName($row['user_id']); //通过ru_id获取到店铺信息;
                $shop_information = $shop_information ? $shop_information : [];

                $arr[$row['shop_id']]['is_IM'] = $shop_information ? $shop_information['is_IM'] : 0; //平台是否允许商家使用"在线客服";

                //判断当前商家是平台,还是入驻商家 bylu
                if ($row['user_id'] == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                    if ($kf_im_switch) {
                        $arr[$row['shop_id']]['is_dsc'] = true;
                    } else {
                        $arr[$row['shop_id']]['is_dsc'] = false;
                    }
                } else {
                    $arr[$row['shop_id']]['is_dsc'] = false;
                }
                /*  @author-bylu  end */

                /* 处理客服相关代码 end */
            }
        }

        $result = ['shop_list' => $arr, 'pager' => $pager];
        return $result;
    }

    /**
     * 店铺商品搜索数量
     *
     * @param $keywords
     * @param int $area_id
     * @param int $area_city
     * @return mixed
     */
    public function getStoreShopGoodsCount($keywords, $area_id = 0, $area_city = 0)
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_show', 1)
            ->where('is_delete', 0);

        $keywords = !empty($keywords) ? addslashes(trim($keywords)) : '';
        if (!empty($keywords)) {
            $keywords = mysql_like_quote($keywords);

            /* 店铺名称 start */
            $shop_list = MerchantsShopInformation::selectRaw("GROUP_CONCAT(user_id) AS user_id")
                ->where(function ($query) use ($keywords) {
                    $query->where('shoprz_brandName', 'like', "%$keywords%")
                        ->orWhere('shopNameSuffix', 'like', "%$keywords%")
                        ->orWhere('rz_shopName', 'like', "%$keywords%")
                        ->orWhereRaw("CONCAT(shoprz_brandName, shopNameSuffix) LIKE '%$keywords%'");
                });

            $shop_list = $shop_list->first();
            $shop_list = $shop_list ? $shop_list->toArray() : [];
            $shop_list = $shop_list ? $shop_list['user_id'] : 0;

            if ($shop_list) {
                $shop_list = explode(",", $shop_list);
                $shop_list = array_unique($shop_list);
            }
            /* 店铺名称 end */

            /* 店铺商品名称 start */
            $scws_res = scws($keywords, 5); //这里可以把关键词分词：诺基亚，耳机
            $arr = explode(',', $scws_res);

            $arr1[] = $keywords;

            if ($arr1 && is_array($arr)) {
                $arr = array_merge($arr1, $arr);
            }

            $operator = " OR ";
            $goods_keywords = '(';
            foreach ($arr as $key => $val) {
                $val = !empty($val) ? addslashes($val) : '';

                if ($val) {
                    if ($key > 0 && $key < count($arr) && count($arr) > 1) {
                        $goods_keywords .= $operator;
                    }

                    $val = mysql_like_quote(trim($val));
                    $goods_keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%')";
                }
            }
            $goods_keywords .= ')';

            $goods_user = Goods::selectRaw("GROUP_CONCAT(user_id) AS user_id")->where('user_id', '>', 0)
                ->whereRaw($goods_keywords);

            if ($this->config['review_goods'] == 1) {
                $goods_user = $goods_user->where('review_status', '>', 2);
            }

            $goods_user = $goods_user->first();
            $goods_user = $goods_user ? $goods_user->toArray() : [];
            $goods_user = $goods_user ? $goods_user['user_id'] : 0;

            if ($goods_user) {
                $goods_user = explode(",", $goods_user);
                $goods_user = array_unique($goods_user);
            }
            /* 店铺商品名称 end */

            $user_list = [];
            if ($shop_list && $goods_user) {
                $user_list = array_merge($user_list, $shop_list, $goods_user);
            } elseif ($shop_list) {
                $user_list = $shop_list;
            } elseif ($goods_user) {
                $user_list = $goods_user;
            }

            $user_list = !empty($user_list) ? array_unique($user_list) : '';
            $user_list = !empty($user_list) ? implode(",", $user_list) : '';

            if (!empty($user_list)) {
                $user_list = $this->dscRepository->delStrComma($user_list);
                $user_list = !is_array($user_list) ? explode(",", $user_list) : $user_list;

                $res = $res->whereIn('user_id', $user_list);
            } else {
                $res = $res->where('user_id', '>', 0);
            }
        } else {
            $res = $res->where('user_id', '>', 0);
        }

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        return $res->count();
    }

    /**
     * 店铺商品搜索列表
     *
     * @access  public
     * @param string $keywords
     * @param string $sort
     * @return  Number
     */
    public function getStoreShopGoodsList($keywords = '', $size = 20, $page = 1, $sort = 'goods_id', $order = 'desc', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $res = Goods::where('is_on_sale', 1)->where('is_alone_sale', 1)->where('is_delete', 0);

        $keywords = !empty($keywords) ? addslashes(trim($keywords)) : '';
        if (!empty($keywords)) {
            $keywords = mysql_like_quote($keywords);

            /* 店铺名称 start */
            $shop_list = MerchantsShopInformation::selectRaw("GROUP_CONCAT(user_id) AS user_id")
                ->where(function ($query) use ($keywords) {
                    $query->where('shoprz_brandName', 'like', "%$keywords%")
                        ->orWhere('shopNameSuffix', 'like', "%$keywords%")
                        ->orWhere('rz_shopName', 'like', "%$keywords%")
                        ->orWhereRaw("CONCAT(shoprz_brandName, shopNameSuffix) LIKE '%$keywords%'");
                });

            $shop_list = $shop_list->first();
            $shop_list = $shop_list ? $shop_list->toArray() : [];
            $shop_list = $shop_list ? $shop_list['user_id'] : 0;

            if ($shop_list) {
                $shop_list = explode(",", $shop_list);
                $shop_list = array_unique($shop_list);
            }
            /* 店铺名称 end */

            /* 店铺商品名称 start */
            $scws_res = scws($keywords, 5); //这里可以把关键词分词：诺基亚，耳机
            $arr = explode(',', $scws_res);

            $arr1[] = $keywords;

            if ($arr1 && is_array($arr)) {
                $arr = array_merge($arr1, $arr);
            }

            $operator = " OR ";
            $goods_keywords = '(';
            foreach ($arr as $key => $val) {
                $val = !empty($val) ? addslashes($val) : '';

                if ($val) {
                    if ($key > 0 && $key < count($arr) && count($arr) > 1) {
                        $goods_keywords .= $operator;
                    }

                    $val = mysql_like_quote(trim($val));
                    $goods_keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%')";
                }
            }
            $goods_keywords .= ')';

            $goods_user = Goods::selectRaw("GROUP_CONCAT(user_id) AS user_id")
                ->where('user_id', '>', 0)
                ->where('is_show', 1)
                ->whereRaw($goods_keywords);

            if ($this->config['review_goods'] == 1) {
                $goods_user = $goods_user->where('review_status', '>', 2);
            }

            $goods_user = $goods_user->first();
            $goods_user = $goods_user ? $goods_user->toArray() : [];
            $goods_user = $goods_user ? $goods_user['user_id'] : 0;

            if ($goods_user) {
                $goods_user = explode(",", $goods_user);
                $goods_user = array_unique($goods_user);
            }
            /* 店铺商品名称 end */

            $user_list = [];
            if ($shop_list && $goods_user) {
                $user_list = array_merge($user_list, $shop_list, $goods_user);
            } elseif ($shop_list) {
                $user_list = $shop_list;
            } elseif ($goods_user) {
                $user_list = $goods_user;
            }

            $user_list = !empty($user_list) ? array_unique($user_list) : '';
            $user_list = !empty($user_list) ? implode(",", $user_list) : '';

            if (!empty($user_list)) {
                $user_list = $this->dscRepository->delStrComma($user_list);
                $user_list = !is_array($user_list) ? explode(",", $user_list) : $user_list;

                $res = $res->whereIn('user_id', $user_list);
            } else {
                $res = $res->where('user_id', '>', 0);
            }
        } else {
            $res = $res->where('user_id', '>', 0);
        }

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $uid = session('user_id', 0);
        $res = $res->withCount([
            'getCollectGoods as is_collect' => function ($query) use ($uid) {
                $query->where('user_id', $uid);
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

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                if ($row) {
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

                    $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $row);

                    $row['shop_price'] = $price['shop_price'];
                    $row['promote_price'] = $price['promote_price'];
                    $row['goods_number'] = $price['goods_number'];

                    $arr[$row['goods_id']] = $row;

                    /* 自营标识 */
                    $self_run = MerchantsShopInformation::where('user_id', $row['user_id'])->value('self_run');
                    $arr[$row['goods_id']]['self_run'] = $self_run;

                    if ($row['promote_price'] > 0) {
                        $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    $arr[$row['goods_id']]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                    $arr[$row['goods_id']]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                    $arr[$row['goods_id']]['sales_volume'] = $row['sales_volume'];
                    $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
                    $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                    $arr[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $arr[$row['goods_id']]['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                    $arr[$row['goods_id']]['is_shipping'] = $row['is_shipping'];

                    $basic_info = SellerShopinfo::where('ru_id', $row['user_id'])->first();
                    $basic_info = $basic_info ? $basic_info->toArray() : [];

                    $chat = $this->dscRepository->chatQq($basic_info);
                    $arr[$row['goods_id']]['kf_type'] = $chat['kf_type'];
                    $arr[$row['goods_id']]['kf_ww'] = $chat['kf_ww'];
                    $arr[$row['goods_id']]['kf_qq'] = $chat['kf_qq'];

                    $arr[$row['goods_id']]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称

                    $build_uri = [
                        'urid' => $row['user_id'],
                        'append' => $arr[$row['goods_id']]['shop_name']
                    ];

                    $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                    $arr[$row['goods_id']]['shop_url'] = $domain_url['domain_name'];

                    $cmt_count = Comment::where('id_value', $row['goods_id'])->where('status', 1)->where('parent_id', 0)->count();
                    $arr[$row['goods_id']]['cmt_count'] = $cmt_count;

                    $arr[$row['goods_id']]['brand_list'] = get_shop_brand_list($row['user_id']); //商家品牌
                    $arr[$row['goods_id']]['is_collect'] = $row['is_collect'];
                    $arr[$row['goods_id']]['pictures'] = $this->goodsGalleryService->getGoodsGallery($row['goods_id'], 6); // 商品相册

                    $shop_information = $this->merchantCommonService->getShopName($row['user_id']); //通过ru_id获取到店铺信息;
                    $shop_information = $shop_information ? $shop_information : [];
                    $arr[$row['goods_id']]['is_IM'] = $shop_information ? $shop_information['is_IM'] : 0; //平台是否允许商家使用"在线客服";
                    //判断当前商家是平台,还是入驻商家 bylu
                    if ($row['user_id'] == 0) {
                        //判断平台是否开启了IM在线客服
                        $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                        if ($kf_im_switch) {
                            $arr[$row['goods_id']]['is_dsc'] = true;
                        } else {
                            $arr[$row['goods_id']]['is_dsc'] = false;
                        }
                    } else {
                        $arr[$row['goods_id']]['is_dsc'] = false;
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 商家商品数量
     *
     * @access  public
     * @param int $user_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $type
     * @param int $isType
     * @param string $show_type
     * @param int $limit
     * @return  Number
     */
    public function getShopGoodsCountList($user_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $type = 0, $isType = '', $show_type = 0, $limit = 0)
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1);

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', '2');
        }

        if ($isType == 'store_best') {
            $res = $res->where('store_best', 1)->where('user_id', '>', $user_id);
        } else {
            $res = $res->where('user_id', $user_id);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        if ($type == 1) {
            $user_rank = session('user_rank');
            $res = $res->with([
                'getMemberPrice' => function ($query) use ($user_rank) {
                    $query->where('user_rank', $user_rank);
                },
                'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                    $query->where('region_id', $warehouse_id);
                },
                'getWarehouseAreaGoods' => function ($query) use ($where) {
                    $query = $query->where('region_id', $where['area_id']);

                    if ($where['area_pricetype'] == 1) {
                        $query->where('city_id', $where['area_city']);
                    }
                }
            ]);

            $res = $res->orderBy('sort_order');

            if (!empty($limit)) {
                $res = $res->take($limit);
            } else {
                if ($show_type == 1) {
                    $res = $res->take(6);
                } else {
                    $res = $res->take(5);
                }
            }

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            $arr = [];
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

                    $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $row);

                    $row['shop_price'] = $price['shop_price'];
                    $row['promote_price'] = $price['promote_price'];
                    $row['goods_number'] = $price['goods_number'];

                    $arr[$key] = $row;

                    if ($row['promote_price'] > 0) {
                        $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    $arr[$key]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                    $arr[$key]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    $arr[$key]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';

                    $arr[$key]['goods_id'] = $row['goods_id'];
                    $arr[$key]['goods_name'] = $row['goods_name'];
                    $arr[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $arr[$key]['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                    $arr[$key]['sales_volume'] = $row['sales_volume']; //销量

                    $basic_info = get_shop_info_content($row['user_id']);

                    $chat = $this->dscRepository->chatQq($basic_info);
                    $arr[$key]['kf_type'] = $chat['kf_type'];
                    $arr[$key]['kf_ww'] = $chat['kf_ww'];
                    $arr[$key]['kf_qq'] = $chat['kf_qq'];

                    $arr[$key]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称
                    $arr[$key]['shop_url'] = $this->dscRepository->buildUri('merchants_store', ['cid' => 0, 'urid' => $row['user_id']], $arr[$key]['shop_name']);

                    $cmt_count = Comment::where('id_value', $row['goods_id'])->where('status', 1)->where('parent_id', 0)->count();
                    $arr[$key]['cmt_count'] = $cmt_count;
                }
            }


            return $arr;
        } else {
            return $res->count();
        }
    }

    /**
     * 商家商品数量
     *
     * @access  public
     * @param int $user_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $price_min
     * @param int $price_max
     * @param string $page
     * @param int $size
     * @param string $sort
     * @param string $order
     * @return  array
     */
    public function getShopGoodsCmtList($user_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $price_min = 0, $price_max = 0, $page = 1, $size = 20, $sort = 'goods_id', $order = 'DESC')
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('user_id', $user_id);

        if ($price_min > 0) {
            $res = $res->where('shop_price', '>=', $price_min);
        }

        if ($price_max > 0) {
            $res = $res->where('shop_price', '<=', $price_max);
        }

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', '2');
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $uid = session('user_id', 0);
        $res = $res->withCount([
            'getCollectGoods as is_collect' => function ($query) use ($uid) {
                $query->where('user_id', $uid);
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

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

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

                $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $arr[$row['goods_id']] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $arr[$row['goods_id']]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$row['goods_id']]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';

                $arr[$row['goods_id']]['sales_volume'] = $row['sales_volume'];
                $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $arr[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$row['goods_id']]['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $arr[$row['goods_id']]['user_id'] = $row['user_id'];

                $basic_info = get_shop_info_content($row['user_id']);
                $arr[$row['goods_id']]['kf_type'] = $basic_info['kf_type'];
                $arr[$row['goods_id']]['kf_ww'] = $basic_info['kf_ww'];
                $arr[$row['goods_id']]['kf_qq'] = $basic_info['kf_qq'];

                $arr[$row['goods_id']]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称

                $build_uri = [
                    'urid' => $row['user_id'],
                    'append' => $arr[$row['goods_id']]['shop_name']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                $arr[$row['goods_id']]['shop_url'] = $domain_url['domain_name'];

                $cmt_count = Comment::where('id_value', $row['goods_id'])->where('status', 1)->where('parent_id', 0)->count();
                $arr[$row['goods_id']]['cmt_count'] = $cmt_count;

                $arr[$row['goods_id']]['is_collect'] = $row['is_collect'];

                $shop_information = $this->merchantCommonService->getShopName($row['user_id']); //通过ru_id获取到店铺信息;
                $shop_information = $shop_information ? $shop_information : [];
                $arr[$row['goods_id']]['is_IM'] = $shop_information ? $shop_information['is_IM'] : 0; //平台是否允许商家使用"在线客服";

                $arr[$row['goods_id']]['pictures'] = $this->goodsGalleryService->getGoodsGallery($row['goods_id'], 6); // 商品相册
                //判断当前商家是平台,还是入驻商家 bylu
                if ($row['user_id'] == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                    if ($kf_im_switch) {
                        $arr[$row['goods_id']]['is_dsc'] = true;
                    } else {
                        $arr[$row['goods_id']]['is_dsc'] = false;
                    }
                } else {
                    $arr[$row['goods_id']]['is_dsc'] = false;
                }
            }
        }

        return $arr;
    }

    /**
     * 商家商品数量
     *
     * @param $user_id
     * @param int $area_id
     * @param int $area_city
     * @param $price_min
     * @param $price_max
     * @return mixed
     */
    public function getShopGoodsCmtCount($user_id, $area_id = 0, $area_city = 0, $price_min = 0, $price_max = 0)
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('user_id', $user_id);

        if ($price_min > 0) {
            $res = $res->where('shop_price', '>=', $price_min);
        }

        if ($price_max > 0) {
            $res = $res->where('shop_price', '<=', $price_max);
        }

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', '2');
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        return $res->count();
    }
}
