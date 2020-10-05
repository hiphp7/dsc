<?php

namespace App\Services\Common;

use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\CollectStore;
use App\Models\Coupons;
use App\Models\CouponsUser;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\MerchantsStepsFields;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\SellerShopinfo;
use App\Models\UserRank;
use App\Models\Users;
use App\Repositories\Common\DscRepository;

/**
 * 公共函数
 * Class Common
 * @package App\Services
 */
class CommonService
{
    protected $dscRepository;
    protected $config;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 判断能否访问供应链
     *
     * @param int $user_id
     * @return bool
     */
    public function judgeWholesaleUse($user_id = 0)
    {
        if ($user_id > 0) {
            if ($this->config['wholesale_user_rank'] == 0) {
                $is_seller = $this->getIsSeller($user_id);
                if ($is_seller == 0) {
                    return false;
                }
            }
        } else {

            return false;

        }

        return true;
    }

    /**
     * 判断会员是否是商家
     *
     * @param int $user_id
     * @return int
     */
    public function getIsSeller($user_id = 0)
    {
        $is_jurisdiction = 0;
        if ($user_id > 0) {
            //判断是否是商家
            $count = SellerShopinfo::where('ru_id', $user_id)->count();
            if ($count > 0) {
                $is_jurisdiction = 1;
            }

            //判断是否是厂商
            $count = MerchantsStepsFields::where('user_id', $user_id)
                ->where('company_type', '厂商')
                ->count();
            if ($count > 0) {
                $is_jurisdiction = 0;
            }
        }

        return $is_jurisdiction;
    }

    /**
     * 会员登录
     *
     * @return array
     * @throws \Exception
     */
    public function actLogin()
    {
        $_POST = get_request_filter($_POST, 1);

        $username = addslashes(request()->input('username', ''));

        $password = addslashes(request()->input('pwcode', ''));
        $password = base64_decode($password);

        $back_act = addslashes(request()->input('back_act', ''));
        $back_act = str_replace(["|", "&amp;"], '&', $back_act);

        $is_jsonp = $this->dscRepository->isJsonp($back_act, '&');

        if ($is_jsonp == 1 && strpos($back_act, 'merchants_store') !== false) {
            $seller_id = $this->dscRepository->isJsonp($back_act, '&', 'merchant_id');
            $back_act = $this->dscRepository->sellerUrl(intval($seller_id));
        } elseif (strpos($back_act, 'is_jsonp=') !== false) {
            $back_act = str_replace(["/&is_jsonp=1", "/&is_jsonp=0"], '/', $back_act);
        }

        $result = ['error' => 0, 'message' => '', 'url' => ''];

        $captcha = intval($this->config['captcha']);

        if (($captcha & CAPTCHA_LOGIN) && gd_version() > 0) {
            if ((!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2))) {
                $post_captcha = trim(request()->input('captcha', ''));
                if (empty($post_captcha)) {
                    $result['error'] = 1;
                    $result['message'] = lang('common.invalid_captcha');
                }

                $verify = app(\App\Libraries\CaptchaVerify::class);
                $captcha_code = $verify->check($post_captcha, 'captcha_login');

                if (!$captcha_code) {
                    $result['error'] = 1;
                    $result['message'] = lang('common.invalid_captcha');
                }
            }
            if ((!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') >= 2))) {
                //验证码
                $GLOBALS['smarty']->assign('enabled_captcha', 1);
                $GLOBALS['smarty']->assign('rand', mt_rand());
                $result['captcha'] = $GLOBALS['smarty']->fetch('library/captcha.lbi');
            }
        }

        if ($result['error'] == 0) {
            if ($GLOBALS['user']->login($username, $password, false)) {

                app(\App\Services\User\UserCommonService::class)->updateUserInfo();
                app(\App\Services\Cart\CartCommonService::class)->recalculatePriceCart();

                $info = Users::select('nick_name', 'is_validated')->where('user_id', session('user_id'))->first();
                $info = $info ? $info->toArray() : [];

                if (empty($info['nick_name'])) {
                    $nick_name = rand(1, 99999999) . "-" . rand(1, 999999);
                    $update_data['nick_name'] = $nick_name;

                    Users::where('user_id', session('user_id'))->update($update_data);
                }

                $ucdata = isset($GLOBALS['user']->ucdata) ? $GLOBALS['user']->ucdata : '';

                //by wang
                $back_act = !empty($back_act) ? $back_act : 'index.php';
                $result['url'] = $back_act;
                $result['ucdata'] = $ucdata;
                if ($this->config['user_login_register'] == 1) {
                    $result['is_validated'] = $info['is_validated']; //验证邮箱
                } else {
                    $result['is_validated'] = 1; //验证邮箱
                }

                //记录会员操作日志
                users_log_change(session('user_id'), USER_LOGIN);
            } else {
                session()->increment('login_fail');
                $result['error'] = 1;
                $result['message'] = lang('user.login_failure');
            }
        }

        return $result;
    }

    /**
     * 头部地区
     *
     * @param string $region_name
     * @return array
     * @throws \Exception
     */
    public function headerRegionName($region_name = '')
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        if (session('user_id') > 0) {
            if (session()->exists('nick_name')) {
                $result['nick_name'] = session('nick_name');
            } else {
                $result['nick_name'] = session('user_name');
            }
        } else {
            $result['nick_name'] = lang('common.please_login');
        }

        $result['content'] = $region_name;

        return $result;
    }

    /**
     * 关注店铺
     *
     * @param int $user_id
     * @return array
     */
    public function ajaxStoreCollect($user_id = 0)
    {
        $result = ['error' => 0, 'content' => ''];

        if ($user_id > 0) {
            $ru_id = (int)request()->input('ru_id', 0);
            $type = (int)request()->input('type', 0);

            if ($type == 1) {
                CollectStore::where('user_id', $user_id)->where('ru_id', $ru_id)->delete();
            } else {
                //判断是否已经关注
                $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $ru_id)->value('rec_id');

                if ($rec_id) {
                    $result['error'] = 1;//已关注
                } else {
                    $is_attention = 1;

                    $other = [
                        'user_id' => $user_id,
                        'ru_id' => $ru_id,
                        'add_time' => gmtime(),
                        'is_attention' => $is_attention
                    ];
                    CollectStore::insert($other);
                    $cou_id = Coupons::where('cou_type', VOUCHER_SHOP_CONLLENT)->where('ru_id', $ru_id)->value('cou_id');

                    $rec_id = CouponsUser::where('user_id', $user_id)->where('cou_id', $cou_id)->value('uc_id');

                    if (isset($cou_id) && !empty($cou_id) && empty($rec_id)) {
                        app(\App\Services\Activity\CouponsService::class)->Couponsreceive($cou_id, $user_id);
                    }
                }
            }

            $result['type'] = $type;
        } else {
            $result['error'] = 0;
        }

        return $result;
    }

    /**
     * 右上角购物车
     *
     * @return array
     */
    public function cartNumber()
    {
        $where = [
            'stages_qishu' => -1
        ];

        $row = app(\App\Services\Cart\CartService::class)->getCartInfo($where);

        if ($row) {
            $number = intval($row['number']);
            $amount = floatval($row['amount']);
        } else {
            $number = 0;
            $amount = 0;
        }

        $result = [
            'number' => $number,
            'amount' => $amount
        ];

        return $result;
    }

    /**
     * 猜你喜欢
     *
     * @return array
     */
    public function getguessYouLike()
    {
        $result = ['error' => 0, 'content' => '', 'message' => ''];

        $goods_ids = addslashes_deep(trim(request()->input('goods_ids', '')));
        $warehouse_id = intval(request()->input('warehouse_id', 0));
        $area_id = intval(request()->input('area_id', 0));
        $area_city = intval(request()->input('area_city', 0));
        $type = trim(request()->input('type', ''));

        $goods_list = [];
        if ($goods_ids) {
            $goods_list = app(\App\Services\Goods\GoodsCommonService::class)->getFloorAjaxGoods([], 0, $warehouse_id, $area_id, $area_city, $goods_ids);
        }
        $GLOBALS['smarty']->assign('goods_list', $goods_list);
        $GLOBALS['smarty']->assign('type', $type);
        $result['content'] = $GLOBALS['smarty']->fetch('library/guessYouLike_list.lbi');

        return $result;
    }

    /**
     * @param int $user_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     * @throws \Exception
     */
    public function getContent($user_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $userCommonService = app(\App\Services\User\UserCommonService::class);
        $bonusService = app(\App\Services\Activity\BonusService::class);
        $userCollectGoodsService = app(\App\Services\User\UserCollectGoodsService::class);
        $userOrderService = app(\App\Services\User\UserOrderService::class);
        $couponsService = app(\App\Services\Activity\CouponsService::class);
        $merchantCommonService = app(\App\Services\Merchant\MerchantCommonService::class);

        load_helper('order');

        $result = ['error' => 0, 'message' => '', 'content' => ''];
        $data_type = addslashes(request()->input('data_type', ''));
        $page = (int)request()->input('page', 1);

        $urlHtmlKey = [
            'flow',
            'index',
        ];

        $urlHtml = $this->dscRepository->getUrlHtml($urlHtmlKey);
        $GLOBALS['smarty']->assign('urlHtml', $urlHtml);

        $GLOBALS['smarty']->assign('cart_pay_go', lang('get_ajax_content.cart_pay_go'));

        switch ($data_type) {
            case 'cart_list':
                $cart_info = insert_cart_info(2);
                $result['cart_num'] = $cart_info['number'];
                $GLOBALS['smarty']->assign('cart_info', $cart_info);
                $result['content'] = $GLOBALS['smarty']->fetch('library/right_float_cart_info.lbi');
                break;
            case 'mpbtn_total':
                $user_info = $userCommonService->getUserDefault($user_id);
                $bonus = $bonusService->getUserBounsNewList($user_id, $page, 0, 'bouns_available_gotoPage', 0, 7); //可用红包
                if ($user_info) {
                    foreach ($bonus['available_list'] as $key => $val) {
                        $bonus['available_list'][$key]['use_startdate'] = substr($val['use_startdate'], 0, 10);
                        $bonus['available_list'][$key]['use_enddate'] = substr($val['use_enddate'], 0, 10);
                    }
                    $user_info['bouns_list'] = $bonus['available_list'];
                    $user_info['bouns_num'] = count($bonus['available_list']);
                }
                $GLOBALS['smarty']->assign('user_info', $user_info);
                $result['content'] = $GLOBALS['smarty']->fetch('library/right_float_total_info.lbi');
                break;
            case 'mpbtn_history':
                $history_info = insert_history_info($warehouse_id, $area_id, $area_city, 10);
                $GLOBALS['smarty']->assign('history_info', $history_info);
                $result['content'] = $GLOBALS['smarty']->fetch('library/right_float_histroy_info.lbi');
                break;
            case 'mpbtn_collection':
                load_helper('clips');
                $operat = addslashes(trim(request()->input('type', '')));
                $collect_id = (int)request()->input('collection_id', 0);

                if (!empty($operat) && $operat == 'del' && $collect_id > 0) {
                    CollectGoods::where('rec_id', $collect_id)->where('user_id', $user_id)->delete();
                }

                $collection_goods = $userCollectGoodsService->getCollectionGoods($user_id, 10, 1, 'collection_goods_gotoPage', 10, $warehouse_id, $area_id, $area_city);
                $GLOBALS['smarty']->assign('goods_list', $collection_goods['goods_list']);
                $collection_store = get_collection_store($user_id, 5, 1, 'collection_store_gotoPage', 5, $warehouse_id, $area_id, $area_city);
                $GLOBALS['smarty']->assign('store_list', $collection_store['store_list']);
                $GLOBALS['smarty']->assign('More_attention_goods', lang('get_ajax_content.More_attention_goods'));
                $GLOBALS['smarty']->assign('enter_shop_more', lang('get_ajax_content.enter_shop_more'));
                $result['content'] = $GLOBALS['smarty']->fetch('library/right_float_collection_info.lbi');
                break;
            case 'mpbtn_order':

                $where = [
                    'user_id' => $user_id,
                    'show_type' => 0,
                    'is_zc_order' => 0,
                    'page' => 1,
                    'size' => 5
                ];
                $where['record_count'] = 5;
                $order_list = $userOrderService->getUserOrdersList($where);

                $GLOBALS['smarty']->assign('order_list', $order_list['order_list']);
                $result['content'] = $GLOBALS['smarty']->fetch('library/right_float_order_info.lbi');
                break;

            //优惠券侧边栏(有商品ID)
            case 'mpbtn_coupons':
                //@author-bylu 获取当前商品可使用的优惠券信息 start

                $goods_id = (int)request()->input('goods_id', 0);
                //获取当前商品所属商家
                $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');

                //获取当前商品可领取的优惠券
                $goods_coupons = get_goods_coupons_list($goods_id);

                //获取当前用户已领取的优惠券
                if ($user_id) {
                    $user_coupons = $couponsService->getUserCouponsList($user_id);
                } else {
                    $user_coupons = [];
                }

                $arr = [];
                if ($user_coupons) {
                    foreach ($user_coupons as $k => $v) {
                        //时间格式化
                        $user_coupons[$k]['cou_start_time'] = local_date('Y-m-d', $v['cou_start_time']);
                        $user_coupons[$k]['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                        //类型格式化
                        $user_coupons[$k]['cou_type'] = $v['cou_type'] == VOUCHER_ALL ? lang('coupons.vouchers_all') : ($v['cou_type'] == VOUCHER_USER ? lang('coupons.vouchers_user') : ($v['cou_type'] == VOUCHER_SHOPING ? lang('coupons.vouchers_shoping') : ($v['cou_type'] == VOUCHER_LOGIN ? lang('coupons.vouchers_login') : ($v['cou_type'] == VOUCHER_SHIPPING ? lang('coupons.vouchers_free') : lang('coupons.unknown')))));
                        //判断当前商品是否可用
                        if ((strpos(',' . $v['cou_goods'] . ',', ',' . $goods_id . ',') !== false || $v['cou_goods'] == 0) && $v['ru_id'] == $ru_id) {
                            $user_coupons[$k]['keyong'] = 1;
                        }
                        //剔除已使用的
                        if ($v['is_use'] == 1) {
                            unset($user_coupons[$k]);
                        }

                        $arr[] = $user_coupons[$k]['cou_id'];
                    }

                    $arr = array_filter($arr);
                }

                $kelingqu_coupons = [];
                //获取当前商品可领取且当前用户未领取的优惠券(用户未登入就显示所有当前商品可领取优惠券)
                if ($goods_coupons && $arr) {
                    foreach ($goods_coupons as $k => $v) {
                        if (!in_array($v['cou_id'], $arr)) {
                            //时间格式化
                            $v['cou_start_time'] = local_date('Y-m-d', $v['cou_start_time']);
                            $v['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                            $kelingqu_coupons[] = $v;
                        }
                    }
                }

                //可领取,已领取最多各显示4个
                if ($user_coupons) {
                    $GLOBALS['smarty']->assign('user_coupons', array_slice($user_coupons, 0, 4));
                }

                if ($kelingqu_coupons) {
                    $GLOBALS['smarty']->assign('kelingqu_coupons', array_slice($kelingqu_coupons, 0, 4));
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/right_float_yhq_info.lbi');
                break;

            //优惠券侧边栏(无商品ID)
            case 'mpbtn_yhq':
                $goods_id = (int)request()->input('goods_id', 0);
                //获取可领取的优惠券

                //获取当前商品所属商家
                $ru_id = 0;
                if ($goods_id > 0) {
                    $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');
                }

                $goods_coupons = $couponsService->getCouponsTypeInfoNoPage('3,4');

                $arr = [];
                $user_coupons = [];
                //获取当前用户已领取的优惠券
                if ($user_id) {
                    $user_coupons = $couponsService->getUserCouponsList($user_id);
                }

                if ($user_coupons) {
                    foreach ($user_coupons as $k => $v) {
                        $user_coupons[$k]['cou_type_name'] = $v['cou_type'];

                        //时间格式化
                        $user_coupons[$k]['cou_start_time'] = local_date('Y-m-d', $v['cou_start_time']);
                        $user_coupons[$k]['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                        //类型格式化
                        $user_coupons[$k]['cou_type'] = $v['cou_type'] == VOUCHER_ALL ? lang('coupons.vouchers_all') : ($v['cou_type'] == VOUCHER_USER ? lang('coupons.vouchers_user') : ($v['cou_type'] == VOUCHER_SHOPING ? lang('coupons.vouchers_shoping') : ($v['cou_type'] == VOUCHER_LOGIN ? lang('coupons.vouchers_login') : ($v['cou_type'] == VOUCHER_SHIPPING ? lang('coupons.vouchers_free') : lang('coupons.unknown')))));
                        //剔除已使用的
                        if ($v['is_use'] == 1) {
                            unset($user_coupons[$k]);
                        }
                    }

                    foreach ($user_coupons as $val) {
                        $arr[] = $val['cou_id'];
                    }
                }

                //获取当前可领取且当前用户未领取的优惠券(用户未登入就显示所有当前商品可领取优惠券)

                if ($arr) {
                    $arr = array_filter($arr);
                }

                $kelingqu_coupons = [];
                foreach ($goods_coupons as $k => $v) {
                    if (!in_array($v['cou_id'], $arr)) {
                        //时间格式化
                        $v['cou_start_time'] = local_date('Y-m-d', $v['cou_start_time']);
                        $v['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                        $v['shop_name'] = $merchantCommonService->getShopName($v['ru_id'], 1);
                        if ($ru_id > 0) {
                            if ($v['ru_id'] == $ru_id || $v['ru_id'] == 0) {
                                $kelingqu_coupons[] = $v;
                            }
                        } else {
                            $kelingqu_coupons[] = $v;
                        }
                    }
                }

                if ($user_coupons) {
                    $GLOBALS['smarty']->assign('user_coupons', array_slice($user_coupons, 0, 4));
                }

                if ($kelingqu_coupons) {
                    $GLOBALS['smarty']->assign('kelingqu_coupons', array_slice($kelingqu_coupons, 0, 4));
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/right_float_yhq_info.lbi');
                break;
            //@author-bylu  end
            default:
                break;
        }

        return $result;
    }

    /**
     * 右侧点击购物车
     *
     * @return array
     */
    public function domainCartInfo()
    {
        $result = ['error' => 0, 'content' => ''];

        $urlHtmlKey = [
            'flow'
        ];

        $urlHtml = $this->dscRepository->getUrlHtml($urlHtmlKey);
        $GLOBALS['smarty']->assign('urlHtml', $urlHtml);

        $result['content'] = insert_cart_info(4);

        return $result;
    }

    public function ajaxUpdateCart($user_id = 0, $session_id = '', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $cartService = app(\App\Services\Cart\CartService::class);
        $goodsService = app(\App\Services\Goods\GoodsService::class);
        $goodsCommonService = app(\App\Services\Goods\GoodsCommonService::class);
        $orderGoodsService = app(\App\Services\Order\OrderGoodsService::class);
        $JigonManageService = app(\App\Services\Erp\JigonManageService::class);
        $packageGoodsService = app(\App\Services\Package\PackageGoodsService::class);
        $crossBorderService = app(\App\Services\CrossBorder\CrossBorderService::class);
        $userCommonService = app(\App\Services\User\UserCommonService::class);

        $result = ['error' => 0, 'message' => ''];

        if (request()->exists('rec_id') && request()->exists('goods_number')) {

            $key = intval(request()->input('rec_id', 0));
            $val = intval(request()->input('goods_number', 0));

            $val = intval(make_semiangle($val));
            if ($val <= 0 || !is_numeric($key)) {
                $result['error'] = 1;
                $result['message'] = lang('common.invalid_number');
                return $result;
            }

            $where = [
                'rec_id' => $key
            ];
            $cartInfo = $cartService->getCartInfo($where);

            $where = [
                'goods_id' => $cartInfo['goods_id'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goodsInfo = $goodsService->getGoodsInfo($where);

            if (empty($cartInfo) || empty($goodsInfo)) {
                $result['error'] = 1;
                return $result;
            }

            $row = [
                'goods_id' => $goodsInfo['goods_id'],
                'goods_name' => $goodsInfo['goods_name'],
                'goods_number' => $goodsInfo['goods_number'],
                'model_price' => $goodsInfo['model_price'],
                'model_inventory' => $goodsInfo['model_inventory'],
                'model_attr' => $goodsInfo['model_attr'],
                'group_number' => $goodsInfo['group_number'],
                'freight' => $goodsInfo['freight'],
                'tid' => $goodsInfo['tid'],
                'shipping_fee' => $goodsInfo['shipping_fee'],
                'cloud_id' => $goodsInfo['cloud_id'],
                'goods_attr_id' => $cartInfo['goods_attr_id'],
                'product_id' => $cartInfo['product_id'],
                'extension_code' => $cartInfo['extension_code'],
                'warehouse_id' => $cartInfo['warehouse_id'],
                'area_id' => $cartInfo['area_id'],
                'area_city' => $cartInfo['area_city'],
                'ru_id' => $cartInfo['ru_id'],
                'group_id' => $cartInfo['group_id'],
                'act_name' => $cartInfo['goods_name'],
                'cost_price' => $goodsInfo['cost_price']
            ];

            $result['ru_id'] = $row['ru_id'];

            //ecmoban模板堂 --zhuo start 限购
            $nowTime = gmtime();
            $xiangouInfo = $goodsCommonService->getPurchasingGoodsInfo($row['goods_id']);
            $start_date = $xiangouInfo ? $xiangouInfo['xiangou_start_date'] : '';
            $end_date = $xiangouInfo ? $xiangouInfo['xiangou_end_date'] : '';
            $xiangouInfo['xiangou_num'] = isset($xiangouInfo['xiangou_num']) ? $xiangouInfo['xiangou_num'] : 0;
            $xiangouInfo['is_xiangou'] = isset($xiangouInfo['is_xiangou']) ? $xiangouInfo['is_xiangou'] : 0;

            if ($xiangouInfo['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                $orderGoods = $orderGoodsService->getForPurchasingGoods($start_date, $end_date, $row['goods_id'], $user_id);

                if ($orderGoods['goods_number'] >= $xiangouInfo['xiangou_num']) {
                    $result['message'] = sprintf(lang('flow.purchase_Prompt'), $row['goods_name']);

                    //更新购物车中的商品数量
                    Cart::where('rec_id', $key)->update(['goods_number' => 0]);

                    $result['error'] = 1;

                    return $result;
                } else {
                    if ($xiangouInfo['xiangou_num'] > 0) {
                        if ($xiangouInfo['is_xiangou'] == 1 && $orderGoods['goods_number'] + $val > $xiangouInfo['xiangou_num']) {
                            $result['message'] = sprintf(lang('flow.purchasing_prompt'), $row['goods_name']);

                            //更新购物车中的商品数量
                            $cart_Num = $xiangouInfo['xiangou_num'] - $orderGoods['goods_number'];
                            Cart::where('rec_id', $key)->update(['goods_number' => $cart_Num]);

                            $result['error'] = 1;
                            $result['cart_Num'] = $cart_Num;
                            $result['rec_id'] = $key;

                            return $result;
                        }
                    }
                }
            }
            //ecmoban模板堂 --zhuo end 限购

            // 最小起订量
            if ($goodsInfo['is_minimum'] = 1 && $nowTime > $goodsInfo['minimum_start_date'] && $nowTime < $goodsInfo['minimum_end_date']) {
                if ($goodsInfo['minimum'] > $val) {
                    $result['message'] = sprintf(lang('flow.purchasing_minimum'), $row['goods_name']);
                    $result['error'] = 1;
                    $result['cart_Num'] = $goodsInfo['minimum'];
                    $result['rec_id'] = $key;
                    return $result;
                }
            }

            //查询：系统启用了库存，检查输入的商品数量是否有效
            if (intval($this->config['use_storage']) > 0 && $row['extension_code'] != 'package_buy') {

                /* 是货品 */
                if (!empty($row['product_id'])) {
                    if ($row['model_attr'] == 1) {
                        $prod = ProductsWarehouse::where('goods_id', $row['goods_id'])->where('product_id', $row['product_id']);
                    } elseif ($row['model_attr'] == 2) {
                        $prod = ProductsArea::where('goods_id', $row['goods_id'])->where('product_id', $row['product_id']);
                    } else {
                        $prod = Products::where('goods_id', $row['goods_id'])->where('product_id', $row['product_id']);
                    }

                    $product_number = $prod->value('product_number');

                    //贡云商品 验证库存
                    if ($row['cloud_id'] > 0) {
                        $product_number = $JigonManageService->jigonGoodsNumber(['cloud_product_id' => $prod->value('cloud_product_id')]);
                    }

                    if ($product_number < $val) {
                        $result['error'] = 2;
                        $result['message'] = sprintf(lang('shopping_flow.stock_insufficiency'), $row['goods_name'], $product_number, $product_number);
                        return $result;
                    }
                } else {
                    if ($row['goods_number'] < $val) {
                        $result['error'] = 1;
                        $result['message'] = sprintf(lang('shopping_flow.stock_insufficiency'), $row['goods_name'], $row['goods_number'], $row['goods_number']);
                        return $result;
                    }
                }
            } elseif (intval($this->config['use_storage']) > 0 && $row['extension_code'] == 'package_buy') {
                if ($packageGoodsService->judgePackageStock($row['goods_id'], $val)) {
                    $result['error'] = 3;
                    $result['message'] = lang('shopping_flow.package_stock_insufficiency');
                    return $result;
                }
            }

            /* 查询：检查该项是否为基本件 以及是否存在配件 */
            /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_idgoods_number为1 */
            $where = [
                'rec_id' => $key,
                'extension_code' => 'package_buy'
            ];
            $offers_accessories_res = $cartService->getOffersAccessoriesList($where);

            //订货数量大于0
            if ($val > 0) {
                if ($row['group_number'] > 0 && $val > $row['group_number'] && !empty($row['group_id'])) {
                    $result['error'] = 1;
                    $result['message'] = sprintf(lang('shopping_flow.group_stock_insufficiency'), $row['goods_name'], $row['group_number'], $row['group_number']);
                    return $result;
                }

                //主配件更新数量时，子配件也跟着加数量
                for ($i = 0; $i < count($offers_accessories_res); $i++) {
                    Cart::where('rec_id', $offers_accessories_res[$i]['rec_id'])->update(['goods_number' => $val]);
                }

                /* 处理超值礼包 */
                if ($row['extension_code'] == 'package_buy') {
                    //更新购物车中的商品数量
                    Cart::where('rec_id', $key)->update(['goods_number' => $val]);
                } /* 处理普通商品或非优惠的配件 */
                else {

                    //更新购物车中的商品数量
                    $other = [
                        'goods_number' => $val,
                        'freight' => $row['freight'],
                        'tid' => $row['tid'],
                        'shipping_fee' => $row['shipping_fee'],
                        'cost_price' => $row['cost_price']
                    ];


                    if ($cartInfo['extension_code'] != 'package_buy' && $cartInfo['is_gift'] == 0 && $cartInfo['parent_id'] == 0) {
                        if ($this->config['add_shop_price'] == 1) {
                            $add_tocart = 1;
                        } else {
                            $add_tocart = 0;
                        }

                        $attr_id = empty($row['goods_attr_id']) ? [] : explode(',', $row['goods_attr_id']);
                        $goods_price = $goodsCommonService->getFinalPrice($row['goods_id'], $val, true, $attr_id, $warehouse_id, $area_id, $area_city, 0, 0, $add_tocart);

                        if ($goods_price > 0) {
                            $other['goods_price'] = $goods_price;
                            $result['goods_price'] = $this->dscRepository->getPriceFormat($goods_price);
                        }
                    }

                    Cart::where('rec_id', $key)->update($other);
                }
            } //订货数量等于0
            else {
                //主配件商品为零时，该底下所有子配件都将删除
                for ($i = 0; $i < count($offers_accessories_res); $i++) {
                    Cart::where('rec_id', $offers_accessories_res[$i]['rec_id'])->delete();
                }

                //删除主配件
                Cart::where('rec_id', $key)->delete();
            }

            $result['rec_id'] = $key;
            $result['goods_number'] = $val;
            $result['total_desc'] = '';

            //查询礼品包价格 ecmoban模板堂 --zhuo
            if ($row['extension_code'] == 'package_buy') {
                $result['ext_info'] = GoodsActivity::where('review_status', 3)->where('act_name', $row['act_name'])->value('ext_info');
                $ext_arr = $result['ext_info'] ? unserialize($result['ext_info']) : '';

                if ($ext_arr) {
                    unset($result['ext_info']);

                    $goods_price = $ext_arr['package_price'];
                } else {
                    $goods_price = 0;
                }

                $result['goods_price'] = $this->dscRepository->getPriceFormat($goods_price);
            }

            $result['cart_info'] = insert_cart_info(4);

            /* 计算合计 */
            $cValue = htmlspecialchars(request()->input('cValue', ''));

            $cart_goods = get_cart_goods($cValue, 0, $warehouse_id, $area_id, $area_city);

            $cart_total_rate = 0;
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $web = $crossBorderService->webExists();

                if (!empty($web)) {
                    $res = $web->getTotalRate($cart_goods);
                    $cart_goods = $res['cart_goods'];
                    $cart_total_rate = $res['cart_total_rate'];
                } else {
                    $result['error'] = 1;
                    $result['message'] = 'service not exists';
                    return $result;
                }
            }

            foreach ($cart_goods['goods_list'] as $goods) {
                if ($goods['rec_id'] == $key) {
                    if ($goods['dis_amount'] > 0) {
                        $result['goods_subtotal'] = $this->dscRepository->getPriceFormat($goods['subtotal']);
                        $result['discount_amount'] = $goods['discount_amount'];
                    } else {
                        $result['goods_subtotal'] = $this->dscRepository->getPriceFormat($goods['subtotal']);
                        $result['discount_amount'] = $this->dscRepository->getPriceFormat(0);
                    }

                    if (CROSS_BORDER === true) // 跨境多商户
                    {
                        $cart_goods['total']['goods_price'] = htmlspecialchars($cart_goods['total']['goods_price']);
                        $cart_goods['total']['goods_price'] = str_replace('¥', '', $cart_goods['total']['goods_price']);
                        $cart_goods['total']['goods_price'] = $cart_goods['total']['goods_price'] + $cart_total_rate;
                        $cart_goods['total']['goods_price'] = '<em>¥</em>' . $cart_goods['total']['goods_price'];

                        if ($goods['rate_price'] > 0) {
                            $rate_price = $this->dscRepository->getPriceFormat($goods['rate_price']);
                            $result['goods_subtotal'] = $result['goods_subtotal'] . "<span style='font-size:12px;color:#666;display: block;font-weight: normal;'>" . lang('common.tax_fee') . ":" . $rate_price . "</span>";
                        }
                    }

                    $result['rec_goods'] = $goods['goods_id'];

                    break;
                }
            }

            /* 计算折扣 */
            $discount = compute_discount(3, $cValue);
            $goods_discount_amount = get_cart_check_goods($cart_goods['goods_list'], $key, 1);

            /* 商品阶梯优惠 + 优惠活动金额 ($goods_discount_amount['save_amount'] + ) */
            $fav_amount = $discount['discount'];
            $result['save_total_amount'] = $this->dscRepository->getPriceFormat($fav_amount + $goods_discount_amount['save_amount']);

            //商品阶梯优惠
            $result['dis_amount'] = $goods_discount_amount['save_amount'];

            $result['group'] = [];
            $subtotal_number = 0;
            foreach ($cart_goods['goods_list'] as $goods) {
                $subtotal_number += $goods['goods_number'];

                if (isset($result['rec_goods']) && $goods['parent_id'] > 0 && $result['rec_goods'] == $goods['parent_id']) {
                    if ($goods['rec_id'] != $key) {
                        $result['group'][$goods['rec_id']]['rec_group'] = $goods['group_id'] . "_" . $goods['rec_id'];
                        $result['group'][$goods['rec_id']]['rec_group_number'] = $goods['goods_number'];

                        $result['group'][$goods['rec_id']]['rec_group_talId'] = $goods['group_id'] . "_" . $goods['rec_id'] . "_subtotal";
                        $result['group'][$goods['rec_id']]['rec_group_subtotal'] = $this->dscRepository->getPriceFormat($goods['goods_amount'], false);

                        if (CROSS_BORDER === true) // 跨境多商户
                        {
                            $web = $crossBorderService->webExists();

                            if (!empty($web)) {
                                $result['can_buy'] = $web->getCanBuy($subtotal_number, $cart_goods['total']['goods_amount']);
                            } else {
                                return show_message('service not exists');
                            }

                            $result['user_id'] = session('user_id');
                        }
                    }
                }
            }

            $result['subtotal_number'] = $subtotal_number;

            if ($result['group']) {
                $result['group'] = array_values($result['group']);
            }

            $goods_amount = $cart_goods['total']['goods_amount'] - $fav_amount;
            $total_goods_price = $this->dscRepository->getPriceFormat($goods_amount, false);


            if (CROSS_BORDER === true) {
                /* 返回购物车信息 */
                $result['flow_info'] = insert_flow_info($total_goods_price, $cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate'], $goods_amount, $cart_goods['total']['real_goods_count'], $cart_total_rate);
            } else {
                /* 返回购物车信息 */
                $result['flow_info'] = insert_flow_info($total_goods_price, $cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate'], $goods_amount, $cart_goods['total']['real_goods_count']);
            }

            // 返回订单总额
            $result['goods_amount'] = $goods_amount;
            $result['goods_amount_fromated'] = $this->dscRepository->getPriceFormat($goods_amount, false);

            $act_id = intval(request()->input('favourable_id', 0));

            $act_sel_id = addslashes(request()->input('sel_id', '')); // 被选中的优惠活动商品（这里是全部，点击购物车 加减号）

            // 被选中的优惠活动商品（这里是全部，点击购物车 加减号）
            $act_pro_sel_id = addslashes(request()->input('pro_sel_id', ''));

            $sel_flag = addslashes(request()->input('sel_flag', '')); // 标志flag
            $act_sel = ['act_sel_id' => $act_sel_id, 'act_pro_sel_id' => $act_pro_sel_id, 'act_sel' => $sel_flag];

            if ($act_id > 0) {
                // 当优惠活动商品不满足最低金额时-删除赠品
                $favourable = favourable_info($act_id);
                $favourable_available = favourable_available($favourable, $act_sel);

                if (!$favourable_available) {
                    $res = Cart::where('is_gift', $act_id)->where('ru_id', $row['ru_id']);

                    if (!empty($user_id)) {
                        $res = $res->where('user_id', $user_id);
                    } else {
                        $res = $res->where('session_id', $session_id);
                    }

                    $res->delete();
                }

                $user_rank = $userCommonService->getUserRankByUid($user_id);

                // 局部更新优惠活动
                $cart_fav_box = cart_favourable_box($act_id, $act_sel, $user_id, $user_rank['rank_id'], $warehouse_id, $area_id, $area_city);
                $GLOBALS['smarty']->assign('activity', $cart_fav_box);
                $GLOBALS['smarty']->assign('ru_id', $row['ru_id']);
                $result['favourable_box_content'] = $GLOBALS['smarty']->fetch("library/cart_favourable_box.lbi");
                $result['act_id'] = $act_id;
            }

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $cbec = $crossBorderService->cbecExists();

                if (!empty($cbec)) {
                    $result['can_buy'] = $cbec->check_kj_price2($cValue, $warehouse_id, $area_id, $area_city);
                } else {
                    $result = ['error' => 1, 'message' => 'service not exists'];
                    return $result;
                }
            }

            return $result;
        } else {
            $result['error'] = 100;
            $result['message'] = '';

            return $result;
        }
    }

    /**
     * 删除购物车商品
     *
     * @return array
     * @throws \Exception
     */
    public function ajaxDeleteCartGoods()
    {
        $sessionRepository = app(\App\Repositories\Common\SessionRepository::class);
        $cartGoodsService = app(\App\Services\Cart\CartGoodsService::class);
        $cartService = app(\App\Services\Cart\CartService::class);

        $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => '', 'index' => -1];
        $result['index'] = (int)request()->input('index', 0);
        $rec_id = (int)request()->input('id', 0);

        $session_id = $sessionRepository->realCartMacIp();
        $user_id = session('user_id', 0);

        $delete = Cart::where('rec_id', $rec_id);

        if ($user_id > 0) {
            $delete = $delete->where('user_id', $user_id);
        } else {
            $delete = $delete->where('session_id', $session_id);
        }

        $delete->delete();

        $where = [
            'rec_type' => CART_GENERAL_GOODS
        ];
        $arr = $cartGoodsService->getGoodsCartList($where);

        $row = $cartService->getCartInfo();

        if ($row) {
            $number = intval($row['number']);
            $amount = floatval($row['amount']);
        } else {
            $number = 0;
            $amount = 0;
        }

        $result['cart_num'] = $number;

        $GLOBALS['smarty']->assign('str', sprintf(lang('common.cart_info'), $number, $this->dscRepository->getPriceFormat($amount, false)));
        $GLOBALS['smarty']->assign('goods', $arr);

        $cart_info = ['goods_list' => $arr, 'number' => $number, 'amount' => $this->dscRepository->getPriceFormat($amount, false)];
        $GLOBALS['smarty']->assign('cart_info', $cart_info);

        $result['content'] = $GLOBALS['smarty']->fetch('library/cart_info.lbi');
        $result['cart_content'] = $GLOBALS['smarty']->fetch('library/cart_menu_info.lbi');

        return $result;
    }

    /**
     * 领取优惠券
     *
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function ajaxCouponsReceive($user_id = 0)
    {
        $res = [];

        $couponsService = app(\App\Services\Activity\CouponsService::class);

        //当前时间
        $time = gmtime();

        $cou_id = (int)request()->input('cou_id', 0);
        $result['is_over'] = 0;

        //取出当前优惠券信息(未过期,剩余总数大于0)
        $cou_data = $couponsService->getCouponsHaving($cou_id);

        //判断券是不是被领取完了
        if (!$cou_data) {
            $res = ['status' => 'error', 'msg' => lang('common.lang_coupons_receive_failure')];
            return $res;
        }

        //判断是否已经领取了,并且还没有使用(根据创建优惠券时设定的每人可以领取的总张数为准,防止超额领取)
        $cou_user_num = CouponsUser::where('user_id', $user_id)->where('cou_id', $cou_id)->count();

        if ($cou_data['cou_user_num'] <= $cou_user_num) {
            $res = ['status' => 'error', 'msg' => sprintf(lang('common.lang_coupons_user_receive'), $cou_data['cou_user_num'])];
            return $res;
        } else {
            $result['is_over'] = 1;
        }

        //判断当前会员等级能不能领取
        if (strpos(',' . $cou_data['cou_ok_user'] . ',', ',' . session('user_rank', 0) . ',') === false && $cou_data['cou_type'] != VOUCHER_ALL) {
            $cou_data['cou_ok_user'] = !is_array($cou_data['cou_ok_user']) ? explode(",", $cou_data['cou_ok_user']) : $cou_data['cou_ok_user'];
            $name = UserRank::selectRaw('GROUP_CONCAT(rank_name) as rank_name')->whereIn('rank_id', $cou_data['cou_ok_user'])->first();
            $name = $name ? $name->toArray() : [];

            $rank_name = $name ? $name['rank_name'] : '';

            $res = ['status' => 'error', 'msg' => sprintf(lang('common.lang_coupons_user_rank'), $rank_name)];
            return $res;
        }

        //判断当前会员是否已经关注店铺
        if ($cou_data['cou_type'] == VOUCHER_SHOP_CONLLENT) {
            $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $cou_data['ru_id'])->value('rec_id');
            if (empty($rec_id)) {
                //关注店铺
                $other = [
                    'user_id' => $user_id,
                    'ru_id' => $cou_data['ru_id'],
                    'add_time' => gmtime(),
                    'is_attention' => 1
                ];
                CollectStore::insert($other);
            }
        }
        //领券
        $uc_sn = $time . rand(10, 99);

        $userData = [
            'user_id' => $user_id,
            'cou_money' => $cou_data['cou_money'],
            'cou_id' => $cou_id,
            'uc_sn' => $uc_sn
        ];

        $uc_id = CouponsUser::insertGetId($userData);

        if ($uc_id) {
            //取出各条优惠券剩余总数(注册送、购物送除外)
            $cou_surplus = $couponsService->getCouponsSurplus([1, 2, 5], 6);

            //取出所有优惠券(注册送、购物送除外)
            $cou_data = $couponsService->getCouponsData([1, 2, 5], 6, $cou_surplus);
            $cou_data = $couponsService->getFromatCoupons($cou_data, $user_id);

            //秒杀券
            $seckill = $cou_data;
            $sort_arr = array();
            if ($seckill) {
                foreach ($seckill as $k => $v) {
                    if ($v['cou_goods']) {
                        $sort_arr[] = $v['cou_order'];
                    } else {
                        $seckill[$k]['cou_goods_name'][0]['goods_thumb'] = get_image_path("images/coupons_default.png"); //默认商品图片
                    }
                }
            }

            if ($sort_arr && $seckill && count($sort_arr) == count($seckill)) {
                array_multisort($sort_arr, SORT_DESC, $seckill);
            }

            $seckill = array_slice($seckill, 0, 4);

            //免邮神券
            $cou_shipping = $couponsService->getCouponsShipping([5], 4, $cou_surplus);
            $cou_shipping = $couponsService->getFromatCoupons($cou_shipping, $user_id);

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

            $GLOBALS['smarty']->assign('seckill', $seckill);    // 秒杀券

            $result['content_kill'] = $GLOBALS['smarty']->fetch('library/coupons_seckill.lbi');

            $cou_data = $couponsService->getFromatCoupons($cou_data, $user_id);

            $GLOBALS['smarty']->assign('cou_data', $cou_data);

            $result['content'] = $GLOBALS['smarty']->fetch('library/coupons_data.lbi');
            $cou_data = $cou_shipping;
            $GLOBALS['smarty']->assign('cou_data', $cou_data);
            $result['content_shipping'] = $GLOBALS['smarty']->fetch('library/coupons_data.lbi');

            $res = ['status' => 'ok', 'msg' => lang('common.lang_coupons_receive_succeed'), 'content' => $result['content'], 'content_kill' => $result['content_kill']];

            return $res;
        }

        return $res;
    }
}
