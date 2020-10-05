<?php

namespace App\Http\Controllers;

use App\Models\CollectStore;
use App\Models\Coupons;
use App\Models\CouponsUser;
use App\Models\UserRank;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\CouponsService;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 优惠券
 */
class CouponsController extends InitController
{
    protected $couponsService;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $config;
    protected $articleCommonService;
    protected $commonService;

    public function __construct(
        CouponsService $couponsService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        ArticleCommonService $articleCommonService,
        CommonService $commonService
    )
    {
        $this->couponsService = $couponsService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->commonService = $commonService;
    }

    public function index()
    {
        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        $user_id = session('user_id', 0);
        $this->smarty->assign('user_id', $user_id);    // 免邮券

        $act = addslashes(request()->input('act', ''));
        /*  @author-bylu 优惠券 start */

        assign_template();
        $position = assign_ur_here();
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
        $categories_pro = get_category_tree_leve_one();
        $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

        /* 跳转H5 start */
        $Loaction = dsc_url('/#/coupon');
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            $cou_id = (int)request()->input('id', 0);
            if ($cou_id > 0) {
                $cou_type = Coupons::where('cou_id', $cou_id)->value('cou_type');
                if (!empty($cou_type)) {
                    $status = -1;
                    switch ($cou_type) {
                        case VOUCHER_ALL:
                            $status = 0;
                            break;
                        case VOUCHER_USER:
                            $status = 1;
                            break;
                        case VOUCHER_SHIPPING:
                            $status = 2;
                            break;
                    }
                    if ($status > -1) {
                        /* 跳转H5 start */
                        $Loaction = dsc_url('/#/coupon?status=' . $status . '&cou_id=' . $cou_id);
                        $uachar = $this->dscRepository->getReturnMobile($Loaction);
                    }
                }

            }
            return $uachar;
        }
        /* 跳转H5 end */


        /* ------------------------------------------------------ */
        //-- 领券中心-首页
        /* ------------------------------------------------------ */
        if ($act == 'coupons_index') {
            $coupons_index = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $coupons_index .= "'coupons_index" . $i . ","; //顶部广告轮播图
            }
            $this->smarty->assign('coupons_index', $coupons_index);

            //取出各条优惠券剩余总数(注册送、购物送除外)
            $cou_surplus = $this->couponsService->getCouponsSurplus([1, 2, 6], 6);

            //取出所有优惠券(注册送、购物送除外)
            $cou_data = $this->couponsService->getCouponsData([1, 2, 5, 6], 6, $cou_surplus);
            $cou_data = $this->couponsService->getFromatCoupons($cou_data, $user_id);

            //秒杀券
            $seckill = $cou_data;

            $sort_arr = [];
            if ($seckill) {
                foreach ($seckill as $k => $v) {
                    if ($v['cou_goods']) {
                        $sort_arr[] = $v['cou_order'];
                    } else {
                        $seckill[$k]['cou_goods_name'][0]['goods_thumb'] = get_image_path('images/coupons_default.png'); //默认商品图片
                    }
                }
            }

            if (count($sort_arr) == count($seckill)) {
                array_multisort($sort_arr, SORT_DESC, $seckill);
            }

            $seckill = array_slice($seckill, 0, 4);

            //任务集市(限购物券(购物满额返券))
            $cou_goods = $this->couponsService->getCouponsGoods([2, 6], 4);

            //免邮神券
            $cou_shipping = $this->couponsService->getCouponsShipping([5, 6], 4, $cou_surplus);
            $cou_shipping = $this->couponsService->getFromatCoupons($cou_shipping, $user_id);

            //好券集市(用户登入了的话,重新获取用户优惠券的使用情况)
            if ($user_id > 0) {
                if ($cou_data) {
                    foreach ($cou_data as $k => $v) {
                        $cou_data[$k]['is_use'] = CouponsUser::where('cou_id', $v['cou_id'])->where('user_id', $user_id)->value('is_use');
                    }
                }

                if ($cou_shipping) {
                    foreach ($cou_shipping as $k => $v) {
                        $cou_shipping[$k]['is_use'] = CouponsUser::where('cou_id', $v['cou_id'])->where('user_id', $user_id)->value('is_use');
                    }
                }
            }

            $this->smarty->assign('cou_shipping', $cou_shipping);    // 免邮券
            $this->smarty->assign('seckill', $seckill);    // 秒杀券
            $this->smarty->assign('cou_goods', $cou_goods);    // 任务集市
            $this->smarty->assign('cou_data', $cou_data);    //   好券集市
            $this->smarty->assign('page_title', $GLOBALS['_LANG']['page_title_Coupon']);    // 页面标题

            return $this->smarty->display('coupons_index.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 好券集市
        /* ------------------------------------------------------ */
        elseif ($act == 'coupons_list') {
            $type = addslashes(request()->input('type', ''));

            $sort = 'cou_id';
            if (request()->has('field')) {
                $get_sort = addslashes(request()->input('field'));
                if (in_array($get_sort, ['cou_end_time', 'cou_money'])) {
                    $sort = $get_sort;
                }
            }
            $order = request()->has('order') ? 'DESC' : 'ASC';

            //优惠券总数;
            $cou_row_total = $this->couponsService->getCouponsCount([1, 2, 6]);

            $row_num = 12;
            $page_total = ceil($cou_row_total / $row_num);

            $p = (int)request()->input('p', 1);
            $page = empty($p) || $page_total < $p ? 1 : $p;
            $offset = ($page - 1) * $row_num;

            //取出各条优惠券剩余总数(注册送、购物送除外)
            $cou_surplus = $this->couponsService->getCouponsSurplus([1, 2, 6], 6);

            //取出所有优惠券(注册送、购物送除外)
            $cou_data = $this->couponsService->getCouponsList([1, 2, 6], $type, $sort, $order, $offset, $row_num, $cou_surplus);
            $cou_data = $this->couponsService->getFromatCoupons($cou_data, $user_id);

            //好券集市(用户登入了的话,重新获取用户优惠券的使用情况)
            if ($user_id) {
                foreach ($cou_data as $k => $v) {
                    $cou_data[$k]['is_use'] = CouponsUser::where('cou_id', $v['cou_id'])->where('user_id', $user_id)->value('is_use');
                }
            }

            $page_total2 = [];
            if ($page_total) {
                for ($i = 1; $i <= $page_total; $i++) {
                    $page_total2[] = $i;
                }
            }

            $queryString = request()->server('QUERY_STRING');
            $page_url = strstr($queryString, '&p', true) ? strstr($queryString, '&p', true) : $queryString;
            $this->smarty->assign('page_total2', $page_total2);
            $this->smarty->assign('page_total', $page_total);
            $this->smarty->assign('page', $page);
            $this->smarty->assign('prev_page', $page == 1 ? 1 : $page - 1);
            $this->smarty->assign('next_page', $page == $page_total ? $page_total : $page + 1);
            $this->smarty->assign('page_url', $page_url);
            $this->smarty->assign('cou_data', $cou_data);    //   好券集市
            $this->smarty->assign('page_title', $GLOBALS['_LANG']['Coupon_redemption_task']);    // 页面标题

            return $this->smarty->display('coupons_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 任务集市
        /* ------------------------------------------------------ */
        elseif ($act == 'coupons_goods') {

            //任务集市数据总数(限购物券(购物满额后返的券))
            $cou_row_total = $this->couponsService->getCouponsCount([2], 'goods');

            $row_num = 10;
            $page_total = ceil($cou_row_total / $row_num);
            $p = (int)request()->input('p', 1);
            $page = empty($p) || $page_total < $p ? 1 : $p;
            $offset = ($page - 1) * $row_num;

            //任务集市(限购物券(购物满额后返的券))
            $cou_goods = $this->couponsService->getCouponsList([], 2, 'cou_id', '', $offset, $row_num);

            $page_total2 = [];
            if ($page_total > 0) {
                for ($i = 1; $i <= $page_total; $i++) {
                    $page_total2[] = $i;
                }
            }

            $queryString = request()->server('QUERY_STRING');
            $page_url = strstr($queryString, '&p', true) ? strstr($queryString, '&p', true) : $queryString;
            $this->smarty->assign('page_total2', $page_total2);
            $this->smarty->assign('page_total', $page_total);
            $this->smarty->assign('page', $page);
            $this->smarty->assign('prev_page', $page == 1 ? 1 : $page - 1);
            $this->smarty->assign('next_page', $page == $page_total ? $page_total : $page + 1);
            $this->smarty->assign('page_url', $page_url);
            $this->smarty->assign('cou_goods', $cou_goods);    // 任务集市
            $this->smarty->assign('page_title', $GLOBALS['_LANG']['Coupon_redemption_task']);    // 页面标题

            return $this->smarty->display('coupons_goods.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 优惠券领取
        /* ------------------------------------------------------ */
        elseif ($act == 'coupons_receive') {

            $result = $this->commonService->ajaxCouponsReceive($user_id);

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 优惠券领取页
        /* ------------------------------------------------------ */
        if ($act == 'coupons_info') {

            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL

            /* 获取数据 */
            $cou_id = (int)request()->input('id', 0);

            $cou_info = Coupons::where('cou_id', $cou_id)->whereIn('cou_type', [VOUCHER_ALL, VOUCHER_USER, VOUCHER_SHIPPING])->first();

            $cou_info = $cou_info ? $cou_info->toArray() : [];

            if ($cou_info) {
                $cou_info['cou_start_date'] = local_date('Y-m-d H:i:s', $cou_info['cou_start_time']);
                $cou_info['cou_end_date'] = local_date('Y-m-d H:i:s', $cou_info['cou_end_time']);
                $cou_info['type_money_formatted'] = price_format($cou_info['cou_money']);
                $cou_info['min_goods_amount_formatted'] = price_format($cou_info['cou_man']);
                $cou_info['shop_name'] = $this->merchantCommonService->getShopName($cou_info['ru_id'], 1); //店铺名称

                //获取免邮券不包邮地区
                if ($cou_info['cou_type'] == VOUCHER_SHIPPING) {
                    $cou_region_list = $this->couponsService->getCouponsRegionList($cou_info['cou_id']);
                    $cou_info['region_name'] = $cou_region_list['free_value_name'];
                }
                $this->smarty->assign('cou_info', $cou_info);
            }

            /* 是否领过 */
            if ($user_id) {
                $res = CouponsUser::selectRaw('COUNT(uc_id) AS user_num, cou_id')->where('cou_id', $cou_id)->where('user_id', $user_id)->first();
                $res = $res ? $res->toArray() : [];

                if ($res && $res['cou_id']) {
                    $num = Coupons::where('cou_id', $res['cou_id'])->value('cou_user_num');

                    if ($res['user_num'] >= $num) {
                        $this->smarty->assign('exist', true);
                    }
                }
            }

            /* 剩余数量 */
            $left = $this->couponsService->getRemainingNumber($cou_id);
            $this->smarty->assign('left', $left);

            /* 显示模板 */
            return $this->smarty->display('coupons.dwt');
        }
    }
}
