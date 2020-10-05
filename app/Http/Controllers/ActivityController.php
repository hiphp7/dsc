<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\DiscountService;
use App\Services\Article\ArticleCommonService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserRankService;

/**
 * 活动列表
 */
class ActivityController extends InitController
{
    protected $discountService;
    protected $goodsService;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $userRankService;
    protected $config;
    protected $articleCommonService;

    public function __construct(
        DiscountService $discountService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        UserRankService $userRankService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->discountService = $discountService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->userRankService = $userRankService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        load_helper('order');
        load_helper('transaction');

        $this->dscRepository->helpersLang(['shopping_flow', 'user']);

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        //判断是否有ajax请求
        $act = addslashes(trim(request()->input('act', 'default')));
        $act = $act ? $act : 'default';

        /* ------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        assign_template();
        assign_dynamic('activity');
        $position = assign_ur_here();
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
        $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-typeauction.xml" : 'feed.php?type=auction'); // RSS URL

        /* ------------------------------------------------------ */
        //-- 活动详情
        /* ------------------------------------------------------ */
        if ($act == 'view') {
            $act_id = intval(request()->input('act_id', 0));
            /* 跳转H5 start */
            $Loaction = dsc_url('/#/activity/detail/' . $act_id);
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            //商品列表
            $size = 8;
            $page = request()->input('page', 1);

            //模板缓存
            $cache_id = sprintf('%X', crc32($act_id . '_' . $warehouse_id . '_' . $area_id . '_' . $area_city . '_' . $size . '_' . $page . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
            $content = cache()->remember('activity_view.dwt.' . $cache_id, $this->config['cache_time'], function () use ($act_id, $warehouse_id, $area_id, $area_city, $size, $page) {

                $activity_top_banner = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $activity_top_banner .= "'activity_top_banner" . $i . ",";
                }
                $this->smarty->assign('activity_top_banner', $activity_top_banner);

                /* 取得用户等级 */
                $user_rank_list = $this->userRankService->getUserRank();

                $row = FavourableActivity::where('act_id', $act_id)->first();

                if (!$row) {
                    return redirect(route('activity'));
                } else {
                    $row = $row->toArray();
                }

                $row['start_time'] = local_date('Y-m-d H:i:s', $row['start_time']);
                $row['end_time'] = local_date('Y-m-d H:i:s', $row['end_time']);

                $row['activity_thumb'] = $this->dscRepository->getImagePath($row['activity_thumb']);

                //享受优惠会员等级
                $user_rank = explode(',', $row['user_rank']);
                $row['user_rank'] = [];
                foreach ($user_rank as $val) {
                    if (isset($user_rank_list[$val])) {
                        $row['user_rank'][] = $user_rank_list[$val];
                    }
                }

                if ($row['userFav_type']) {
                    $row['shop_name'] = $GLOBALS['_LANG']['His_general']; //商家名称
                } else {
                    $row['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称;

                    $build_uri = [
                        'urid' => $row['user_id'],
                        'append' => $row['shop_name']
                    ];

                    $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);

                    $row['shop_url'] = $domain_url['domain_name'];
                }

                $row['act_range_type'] = $row['act_range']; //优惠范围
                $filter = [];

                $act_range_ext = isset($row['act_range_ext']) && !empty($row['act_range_ext']) ? explode(",", $row['act_range_ext']) : '';

                //使用类型 by wu
                if ($row['userFav_type'] == 0) {
                    $filter['user_id'] = $row['user_id'];
                }

                //优惠范围类型、内容
                if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext'])) {
                    if ($row['act_range'] == FAR_CATEGORY) {
                        $filter['cat_ids'] = $row['act_range_ext']; //by wu
                        $row['act_range'] = $GLOBALS['_LANG']['far_category'];
                        $row['program'] = 'category.php?id=';

                        $act_range_ext = Category::select('cat_id AS id', 'cat_name AS name')->whereIn('cat_id', $act_range_ext)->get();
                        if ($act_range_ext) {
                            $act_range_ext = $act_range_ext->toArray();
                        } else {
                            $act_range_ext = [];
                        }
                    } elseif ($row['act_range'] == FAR_BRAND) {
                        $filter['brand_ids'] = $row['act_range_ext']; //by wu
                        $row['act_range'] = $GLOBALS['_LANG']['far_brand'];
                        $row['program'] = 'brand.php?id=';

                        $act_range_ext = Brand::select('brand_id AS id', 'brand_name AS name')->whereIn('brand_id', $act_range_ext)->get();
                        if ($act_range_ext) {
                            $act_range_ext = $act_range_ext->toArray();
                        } else {
                            $act_range_ext = [];
                        }
                    } else {
                        $filter['goods_ids'] = $row['act_range_ext']; //by wu
                        $row['act_range'] = $GLOBALS['_LANG']['far_goods'];
                        $row['program'] = 'goods.php?id=';

                        $act_range_ext = $this->discountService->getActRangeExt($act_range_ext, $warehouse_id, $area_id, $area_city);
                    }

                    $row['act_range_ext'] = $act_range_ext;
                } else {
                    $row['act_range'] = $GLOBALS['_LANG']['far_all'];
                }
                if ($this->config['region_store_enabled']) {
                    /* 设置的使用范围 卖场优惠活动 liu */
                    $filter['mer_ids'] = get_favourable_merchants($row['userFav_type'], $row['userFav_type_ext'], $row['rs_id']);
                }

                //优惠方式
                $row['actType'] = $row['act_type']; //优惠方式

                switch ($row['act_type']) {
                    case 0:
                        $row['act_type'] = $GLOBALS['_LANG']['fat_goods'];
                        $row['gift'] = unserialize($row['gift']);
                        if (is_array($row['gift'])) {
                            foreach ($row['gift'] as $k => $v) {
                                $goods = Goods::select('goods_thumb')->where('goods_id', $v['id'])->first();
                                if ($goods) {
                                    $goods = $goods->toArray();
                                    $row['gift'][$k]['thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
                                    $row['gift'][$k]['price'] = $this->dscRepository->getPriceFormat($v['price']);
                                }
                            }
                        }
                        break;
                    case 1:
                        $row['act_type'] = $GLOBALS['_LANG']['fat_price'];
                        $row['act_type_ext'] .= $GLOBALS['_LANG']['unit_yuan'];
                        $row['gift'] = [];
                        break;
                    case 2:
                        $row['act_type'] = $GLOBALS['_LANG']['fat_discount'];
                        $row['act_type_ext'] .= "%";
                        $row['gift'] = [];
                        break;
                }

                $filter_array = $this->goodsService->getFilterGoodsList($filter, '', $warehouse_id, $area_id, $area_city, $size, $page);
                $row['goods_list'] = $filter_array['goods_list'];
                $pager = get_pager('activity.php', ['act' => 'view', 'act_id' => $act_id], $filter_array['record_count'], $page, $size);
                $this->smarty->assign('pager', $pager);

                /* 引入其他文件语言包 */
                $common_lang = [];
                $common_lang['collect_to_flow'] = lang('shopping_flow.collect_to_flow');
                $this->smarty->assign('common_lang', $common_lang);

                $this->smarty->assign('activity', $row);
                return $this->smarty->display('activity_view.dwt');
            });

            return $content;
        }

        /* ------------------------------------------------------ */
        //-- 活动列表
        /* ------------------------------------------------------ */
        else {

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/activity');
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            //模板缓存
            $cache_id = sprintf('%X', crc32(session('user_rank', 0) . '_' . $this->config['lang']));
            $content = cache()->remember('activity.dwt.' . $cache_id, $this->config['cache_time'], function () {

                $user_rank_list = $this->userRankService->getUserRank();

                $this->discountService->user_rank_list = $user_rank_list;
                $activity_list = $this->discountService->getFavourableActivity();

                $this->smarty->assign('activity_list', $activity_list);
                $this->smarty->assign('lang', $GLOBALS['_LANG']);

                return $this->smarty->display('activity.dwt');
            });

            return $content;
        }
    }
}
