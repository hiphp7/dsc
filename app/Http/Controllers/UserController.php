<?php

namespace App\Http\Controllers;

use App\Libraries\CaptchaVerify;
use App\Libraries\Image;
use App\Models\AccountLog;
use App\Models\AdminUser;
use App\Models\AffiliateLog;
use App\Models\BookingGoods;
use App\Models\ConnectUser;
use App\Models\EmailList;
use App\Models\EntryCriteria;
use App\Models\GiftGardType;
use App\Models\Goods;
use App\Models\GoodsReport;
use App\Models\GoodsReportImg;
use App\Models\GoodsReportTitle;
use App\Models\GoodsReportType;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\PayLog;
use App\Models\RegExtendInfo;
use App\Models\SellerApplyInfo;
use App\Models\SellerGrade;
use App\Models\SellerShopinfo;
use App\Models\Sessions;
use App\Models\Shipping;
use App\Models\StoreOrder;
use App\Models\UserAccount;
use App\Models\UserGiftGard;
use App\Models\Users;
use App\Models\UsersLog;
use App\Models\UsersPaypwd;
use App\Models\UsersReal;
use App\Models\UsersVatInvoicesInfo;
use App\Models\ValueCard;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Common\AreaService;
use App\Services\Common\CommonService;
use App\Services\Goods\GoodsGuessService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use App\Services\User\UserCollectGoodsService;
use App\Services\User\UserCommonService;
use App\Services\User\UserMerchantService;
use App\Services\User\UserOrderService;
use App\Services\User\UserService;

/**
 * 会员中心
 */
class UserController extends InitController
{
    protected $dscRepository;
    protected $areaService;
    protected $goodsService;
    protected $orderService;
    protected $paymentService;
    protected $userService;
    protected $commonService;
    protected $http;
    protected $baseRepository;
    protected $commonRepository;
    protected $merchantCommonService;
    protected $userMerchantService;
    protected $userOrderService;
    protected $config;
    protected $articleCommonService;
    protected $userCommonService;
    protected $cartCommonService;
    protected $goodsGuessService;
    protected $userCollectGoodsService;

    public function __construct(
        DscRepository $dscRepository,
        AreaService $areaService,
        GoodsService $goodsService,
        OrderService $orderService,
        PaymentService $paymentService,
        UserService $userService,
        CommonService $commonService,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        UserMerchantService $userMerchantService,
        UserOrderService $userOrderService,
        ArticleCommonService $articleCommonService,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService,
        GoodsGuessService $goodsGuessService,
        UserCollectGoodsService $userCollectGoodsService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->areaService = $areaService;
        $this->goodsService = $goodsService;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->userService = $userService;
        $this->commonService = $commonService;
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->userMerchantService = $userMerchantService;
        $this->userOrderService = $userOrderService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->goodsGuessService = $goodsGuessService;
        $this->userCollectGoodsService = $userCollectGoodsService;
    }

    public function index()
    {
        /* 跳转H5 start */
        $Loaction = url('mobile/#/user');
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

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

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        load_helper(['code', 'wholesale']);
        load_helper('goods', 'admin');

        $image = new Image(['bgcolor' => $this->config['bgcolor']]);

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        $user_id = session('user_id', 0);
        $action = addslashes(trim(request()->input('act', 'default')));
        $action = $action ? $action : 'default';

        $affiliate = $this->config['affiliate'] ? unserialize($this->config['affiliate']) : [];
        $this->smarty->assign('affiliate', $affiliate);
        $back_act = '';

        $this->smarty->assign('use_value_card', $this->config['use_value_card']); //获取是否使用储值卡
        $this->smarty->assign('user_passport', ['login', 'register', 'get_password', 'reset_password']); //登录注册页面

        $not_login_arr = $this->userCommonService->notLoginArr();

        $ui_arr = $this->userCommonService->uiArr();

        /* 未登录处理 */
        $requireUser = $this->userCommonService->requireLogin(session('user_id'), $action, $not_login_arr, $ui_arr);
        $action = $requireUser['action'];
        $is_back_act = $requireUser['is_back_act'];
        $require_login = $requireUser['require_login'];

        if ($require_login == 1) {
            //未登录提交数据。非正常途径提交数据！
            return dsc_header('location:' . $this->dscRepository->dscUrl('user.php'));
        }

        /* 区分登录注册底部样式 */
        $footer = $this->userCommonService->userFooter();
        if (in_array($action, $footer)) {
            $this->smarty->assign('footer', 1);
        }

        $is_apply = $this->userCommonService->merchantsIsApply($user_id);
        $this->smarty->assign('is_apply', $is_apply);

        $user_default_info = $this->userCommonService->getUserDefault($user_id);
        $this->smarty->assign('user_default_info', $user_default_info);

        /* 如果是显示页面，对页面进行相应赋值 */
        if (in_array($action, $ui_arr)) {
            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['user_core']);
            $this->smarty->assign('page_title', $position['title']); // 页面标题
            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版
            $this->smarty->assign('ur_here', $position['ur_here']);

            $this->smarty->assign('car_off', $this->config['anonymous_buy']);

            /* 是否显示积分兑换 */
            if (!empty($this->config['points_rule']) && unserialize($this->config['points_rule'])) {
                $this->smarty->assign('show_transform_points', 1);
            }
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
            $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录
            $this->smarty->assign('action', $action);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $info = $user_default_info;

            if ($user_id) {
                //验证邮箱
                if (isset($info['is_validated']) && !$info['is_validated'] && $this->config['user_login_register'] == 1) {
                    $Location = url('/') . '/' . 'user.php?act=user_email_verify';
                    return dsc_header('location:' . $Location);
                }
            }

            $count = AdminUser::where('ru_id', session('user_id'))->count();
            if ($count) {
                $is_merchants = 1;
            } else {
                $is_merchants = 0;
            }

            $this->smarty->assign('is_merchants', $is_merchants);
            $this->smarty->assign('shop_reg_closed', $this->config['shop_reg_closed']);

            $this->smarty->assign('filename', 'user');
        }

        $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();
        $wholesaleUse = $this->commonService->judgeWholesaleUse(session('user_id'), 1);
        $wholesale_use = $supplierEnabled && $wholesaleUse ? 1 : 0;

        $this->smarty->assign('wholesale_use', $wholesale_use);

        /* ------------------------------------------------------ */
        //-- 用户中心欢迎页
        /* ------------------------------------------------------ */
        if ($action == 'default') {
            $page = (int)request()->input('page', 1);

            load_helper('clips');
            if ($rank = get_rank_info()) {
                $h = date('G');
                if ($h < 11) {
                    $rank['time_reminder'] = $GLOBALS['_LANG']['greet'] [0];
                } elseif ($h < 13) {
                    $rank['time_reminder'] = $GLOBALS['_LANG']['greet'] [1];
                } elseif ($h < 17) {
                    $rank['time_reminder'] = $GLOBALS['_LANG']['greet'] [2];
                } else {
                    $rank['time_reminder'] = $GLOBALS['_LANG']['greet'] [3];
                }
                $this->smarty->assign('rank', $rank);
                if (!empty($rank['next_rank_name'])) {
                    $this->smarty->assign('next_rank_name', sprintf($GLOBALS['_LANG']['next_level'], $rank['next_rank'], $rank['next_rank_name']));
                }
            }

            $order_list = $this->userOrderService->getDefaultUserOrders($user_id);


            //待评价 liu
            $signNum = get_user_order_comment_count($user_id, 0);
            $this->smarty->assign('signNum', $signNum);

            //账户安全 liu
            $res = Users::select('user_id', 'email', 'mobile_phone', 'is_validated as email_validate')->where('user_id', $user_id);

            $res = $res->with([
                'getUsersPaypwd' => function ($query) {
                    $query->select('user_id', 'paypwd_id');
                },
                'getUsersReal' => function ($query) {
                    $query->select('user_id', 'real_id', 'real_name', 'bank_card');
                }
            ]);

            $res = $res->first();

            $res = $res ? $res->toArray() : [];

            $res = $res && $res['get_users_paypwd'] ? array_merge($res, $res['get_users_paypwd']) : $res;
            $res = $res && $res['get_users_real'] ? array_merge($res, $res['get_users_real']) : $res;

            $this->smarty->assign('validate', $res);

            //优惠券
            $where = [
                'user_id' => $user_id
            ];
            $cou = $this->userService->getUserCouMoney($where);

            $cou['money'] = price_format($cou['money']);
            $this->smarty->assign('coupons', $cou);

            //储值卡
            $where = [
                'user_id' => $user_id
            ];
            $vc = $this->userService->getValueCardInfo($where);

            $vc['money'] = price_format($vc['money']);
            $this->smarty->assign('value_card', $vc);

            $this->smarty->assign('order_list', $order_list);

            $collection_goods = $this->userCollectGoodsService->getDefaultCollectionGoods($user_id, $warehouse_id, $area_id, $area_city);
            $this->smarty->assign('collection_goods', $collection_goods);

            /**
             * Start
             *
             * 猜你喜欢商品
             */
            $where = [
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                'user_id' => $user_id,
                'history' => 1,
                'page' => $page,
                'limit' => 7
            ];
            $guess_goods = $this->goodsGuessService->getGuessGoods($where);

            $this->smarty->assign('guess_goods', $guess_goods);
            /* End */

            $helpart_list = $this->userService->getUserHelpart();
            $this->smarty->assign('helpart_list', $helpart_list);

            $info = $this->userCommonService->getUserDefault($user_id);

            $user_default_info['user_picture'] = $info ? $info['user_picture'] : '';
            $this->smarty->assign('user_default_info', $user_default_info);

            //验证邮箱
            if (isset($info['is_validated']) && !$info['is_validated'] && $this->config['user_login_register'] == 1) {
                $Location = url('/') . '/' . 'user.php?act=user_email_verify';
                return dsc_header('location:' . $Location);
            }

            //待确认
            $where = [
                'user_id' => $user_id,
                'show_type' => 0,
                'order_status' => OS_UNCONFIRMED,
                'is_zc_order' => 0
            ];
            $unconfirmed = $this->userOrderService->getOrderWhereCount($where);
            $this->smarty->assign('unconfirmed', $unconfirmed);

            //待付款
            $pay_id = $this->paymentService->paymentIdList(false);
            $where = [
                'user_id' => $user_id,
                'show_type' => 0,
                'order_status' => [OS_CONFIRMED, OS_SPLITED],
                'pay_status' => [PS_UNPAYED, PS_PAYED_PART],
                'shipping_status' => [SS_SHIPPED, SS_RECEIVED],
                'pay_id' => $pay_id,
                'is_zc_order' => 0
            ];

            $pay_count = $this->userOrderService->getOrderWhereCount($where);
            $this->smarty->assign('pay_count', $pay_count);

            //已发货,用户尚未确认收货
            $pay_id = $this->paymentService->paymentIdList(true);
            if ($pay_id) {
                $cob_id = $pay_id[0] ?? 0;
            }
            $where = [
                'user_id' => $user_id,
                'show_type' => 0,
                'order_status' => [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART],
                'pay_status' => [PS_PAYED, PS_PAYING],
                'shipping_status' => [SS_SHIPPED, SS_UNSHIPPED, SS_SHIPPED_PART],
                'is_cob' => $cob_id ?? 0,//兼容货到付款
                'is_zc_order' => 0
            ];
            $to_confirm_order = $this->userOrderService->getOrderWhereCount($where);
            $this->smarty->assign('to_confirm_order', $to_confirm_order);

            //已发货，用户已确认收货
            $where = [
                'user_id' => $user_id,
                'show_type' => 0,
                'order_status' => [OS_CONFIRMED, OS_SPLITED],
                'pay_status' => [PS_PAYED, PS_PAYING],
                'shipping_status' => SS_RECEIVED,
                'is_zc_order' => 0
            ];
            $to_finished = $this->userOrderService->getOrderWhereCount($where);
            $this->smarty->assign('to_finished', $to_finished);
            //ecmoban模板堂 --zhuo end
            // 最新安全评级
            $this->smarty->assign("security_rating", $this->userService->SecurityRating());
            session()->forget('qqoath');
            session()->forget('weibooath');
            session()->forget('wechatoath');

            $this->smarty->assign('info', $info);
            $this->smarty->assign('user_notice', $this->config['user_notice']);
            $this->smarty->assign('prompt', get_user_prompt($user_id));
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 显示会员注册界面
        /* ------------------------------------------------------ */
        if ($action == 'register') {
            if ($this->config['shop_reg_closed'] == 1) {
                return dsc_header('location:' . $this->dscRepository->dscUrl());
            }

            if (empty($back_act)) {
                if (empty($back_act) && request()->server('HTTP_REFERER')) {
                    $back_act = request()->server('HTTP_REFERER');
                } else {
                    $back_act = route('user');
                }
            }

            /* 短信发送设置 */
            $sms_security_code = rand(1000, 9999);

            session([
                'sms_security_code' => $sms_security_code
            ]);

            $this->smarty->assign('sms_security_code', $sms_security_code);
            $this->smarty->assign('enabled_sms_signin', 1);
            $this->smarty->assign('cfg', $this->config);

            /* 取出注册扩展字段 */
            $where = [
                'type' => ['<', 2],
                'display' => 1,
                'sort' => ['dis_order', 'id'],
                'order' => 'asc',
            ];

            $extend_info_list = $this->userService->getRegFieldsList($where);
            $this->smarty->assign('extend_info_list', $extend_info_list);

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
            }
            $this->smarty->assign('rand', mt_rand());
            $this->smarty->assign('back_act', $back_act);

            /* 密码提示问题 */
            $this->smarty->assign('passwd_questions', $GLOBALS['_LANG']['passwd_questions']);

            /* 增加是否关闭注册 */
            $this->smarty->assign('shop_reg_closed', $this->config['shop_reg_closed']);
            $this->smarty->assign('sms_register', $this->config['sms_signin']); //短信动态码
            $this->smarty->assign('regist_banner', "regist_banner");

            // 注册协议文章
            $register_article_id = (isset($this->config['register_article_id']) && !empty($this->config['register_article_id'])) ? $this->config['register_article_id'] : '6';
            $register_url = $this->dscRepository->buildUri('article', ['aid' => $register_article_id], $GLOBALS['_LANG']['protocol_bind']);
            $this->smarty->assign('register_article_id', $register_url);

            return $this->smarty->display('user_passport.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 注册会员的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_register') {
            /* 增加是否关闭注册 */
            if ($this->config['shop_reg_closed']) {
                $this->smarty->assign('action', 'register');
                return $this->smarty->display('user_passport.dwt');
            } else {
                load_helper('passport');

                $request = get_request_filter(request()->all(), 1);

                $username = compile_str(trim(request()->input('mobile_phone', '')));
                $password = compile_str(trim(request()->input('password', '')));
                $email = compile_str(trim(request()->input('email', '')));

                $other['msn'] = compile_str(trim(request()->input('extend_field1', '')));
                $other['qq'] = compile_str(trim(request()->input('extend_field2', '')));
                $other['office_phone'] = compile_str(trim(request()->input('extend_field3', '')));

                $other['home_phone'] = compile_str(trim(request()->input('extend_field4', '')));
                $sel_question = compile_str(request()->input('sel_question', ''));
                $passwd_answer = compile_str(trim(request()->input('passwd_answer', '')));

                $other['mobile_phone'] = compile_str(trim(request()->input('mobile_phone', '')));
                //手机动态码
                $other['mobile_code'] = compile_str(trim(request()->input('mobile_code', '')));

                $back_act = compile_str(trim(request()->input('back_act', '')));
                //用户注册方式
                $register_mode = (int)request()->input('register_type', 0);

                // 昵称
                $other['nick_name'] = compile_str(trim(request()->input('nickname', '')));

                $js_strlen = $this->userService->DealJsStrlen($username);

                if ($js_strlen < 4) {
                    return show_message($GLOBALS['_LANG']['passport_js']['username_shorter']);
                }

                if ($js_strlen > 15) {
                    return show_message($GLOBALS['_LANG']['passport_js']['msg_un_length']);
                }

                if (strlen($password) < 6) {
                    return show_message($GLOBALS['_LANG']['passport_js']['password_shorter']);
                }

                if (strpos($password, ' ') > 0) {
                    return show_message($GLOBALS['_LANG']['passwd_balnk']);
                }

                /* 验证码检查 */
                if ((intval($this->config['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                    $captcha = request()->input('captcha', '');
                    if (empty($captcha)) {
                        return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['sign_up'], 'user.php?act=register', 'error');
                    }
                    $seKey = 'mobile_phone';

                    $verify = app(CaptchaVerify::class);
                    $captcha_code = $verify->check($captcha, $seKey);

                    if (!$captcha_code) {
                        return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['sign_up'], 'user.php?act=register', 'error');
                    }
                }

                //验证手机验证码
                if (request()->exists('mobile_code') && !empty($other['mobile_code']) && $other['mobile_code'] != session('sms_mobile_code')) {
                    return show_message($GLOBALS['_LANG']['msg_mobile_code_not_correct'], $GLOBALS['_LANG']['sign_up'], 'user.php?act=register', 'error');
                }
                // 邮箱
                $email = empty($email) ? '' : $email;

                if (register($username, $password, $email, $other, $register_mode) !== false) {
                    /*把新注册用户的扩展信息插入数据库*/
                    $where = [
                        'type' => 0,
                        'display' => 1,
                        'sort' => ['dis_order', 'id'],
                        'order' => 'asc',
                    ];

                    $fields_arr = $this->userService->getRegFieldsList($where);

                    //生成扩展字段的内容字符串
                    $arr_fields = [];
                    foreach ($fields_arr as $val) {
                        $extend_field_index = 'extend_field' . $val['id'];
                        if (!empty($request[$extend_field_index])) {
                            $temp_field_content = strlen($request[$extend_field_index]) > 100 ? mb_substr($request[$extend_field_index], 0, 99) : $request[$extend_field_index];
                            $arr_fields[] = [
                                'user_id' => session('user_id'),
                                'reg_field_id' => $val['id'],
                                'content' => compile_str($temp_field_content)
                            ];
                        }
                    }

                    //插入注册扩展数据
                    if ($arr_fields) {
                        RegExtendInfo::insert($arr_fields);
                    }

                    /* 写入密码提示问题和答案 */
                    if (!empty($passwd_answer) && !empty($sel_question)) {
                        $other = [
                            'passwd_question' => $sel_question,
                            'passwd_answer' => $passwd_answer
                        ];
                        Users::where('user_id', session('user_id'))->update($other);
                    }
                    /* 判断是否需要自动发送注册邮件 */
                    if ($this->config['member_email_validate'] && $this->config['send_verify_email']) {
                        send_regiter_hash(session('user_id'));
                    }
                    $ucdata = empty($GLOBALS['user']->ucdata) ? "" : $GLOBALS['user']->ucdata;

                    if (!$register_mode && $this->config['user_login_register'] == 1) {
                        return redirect("user.php?act=user_email_verify");
                    } else {
                        return redirect("user.php");
                    }
                } else {
                    return $this->err->show($GLOBALS['_LANG']['sign_up'], 'user.php?act=register');
                }
            }
        }

        /* ------------------------------------------------------ */
        //-- 注册会员的邮箱验证
        /* ------------------------------------------------------ */
        elseif ($action == 'user_email_verify') {
            assign_template();

            if (!$user_id) {
                return dsc_header("Location: " . url('/'));
            }

            $position = assign_ur_here(0, $GLOBALS['_LANG']['bind_login']);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
            $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录

            $info = $this->userCommonService->getUserDefault($user_id);
            $this->smarty->assign('info', $info);

            return $this->smarty->display('user_email_verify.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 注册会员的邮箱验证
        /* ------------------------------------------------------ */
        elseif ($action == 'user_email_send') {
            $res = ['result' => '', 'error' => 0];
            $email = addslashes(trim(request()->input('email', '')));

            $info = $this->userCommonService->getUserDefault($user_id);
            $type = (int)request()->input('type', 0);
            $info['email'] = $email ? $email : $info['email'];

            $is_error = 0;
            if (request()->exists('email') && $type == 1) {
                $res = Users::where('email', $email)->value('user_id');
                if ($res) {
                    $is_error = 1;
                }
            }

            $result = false;
            if ($info['email'] && $is_error == 0) {
                $user_email_verify = rand(1000, 9999);

                session([
                    'user_email_verify' => $user_email_verify
                ]);

                $info['username'] = isset($info['username']) ? $info['username'] : '';
                /* 发送邮件 */
                $template = get_mail_template('user_register');
                $this->smarty->assign('user_name', $info['username']);
                $this->smarty->assign('register_code', $user_email_verify);
                $content = $this->smarty->fetch('str:' . $template['template_content']);

                $result = $this->commonRepository->sendEmail($this->config['shop_name'], $info['email'], $template['template_subject'], $content, $template['is_html']);
            }

            if (!$result) {
                echo 'false';
            } else {
                echo 'ok';
            }
        }

        /* ------------------------------------------------------ */
        //-- 注册会员的邮箱验证
        /* ------------------------------------------------------ */
        elseif ($action == 'email_send_succeed') {
            $email = addslashes(trim(request()->input('email', '')));

            $other = [
                'is_validated' => 1,
                'email' => $email
            ];
            Users::where('user_id', $user_id)->update($other);

            return dsc_header('Location: ' . url('/') . '/' . "user.php");
        }

        /* ------------------------------------------------------ */
        //-- 验证用户注册邮件
        /* ------------------------------------------------------ */
        elseif ($action == 'validate_email') {
            $hash = addslashes(trim(request()->input('hash', '')));
            if ($hash) {
                load_helper('passport');
                $id = register_hash('decode', $hash);
                if ($id > 0) {
                    Users::where('user_id', $id)->update(['is_validated' => 1]);

                    $row = Users::select('user_name', 'email')->where('user_id', $id)->first();
                    $row = $row ? $row->toArray() : [];

                    $user_name = $row ? $row['user_name'] : '';
                    $email = $row ? $row['email'] : '';

                    return show_message(sprintf($GLOBALS['_LANG']['validate_ok'], $user_name, $email), $GLOBALS['_LANG']['profile_lnk'], 'user.php?act=account_safe&type=change_email&step=last');
                }
            }
            return show_message($GLOBALS['_LANG']['validate_fail']);
        }

        /* ------------------------------------------------------ */
        //-- 用户登录界面
        /* ------------------------------------------------------ */
        elseif ($action == 'login') {
            $dsc_token = get_dsc_token();
            $this->smarty->assign('dsc_token', $dsc_token);

            // 来源地址
            if (empty($back_act)) {
                if (request()->has('back_act')) {
                    $back_act =  urldecode(request()->get('back_act'));
                } else if (request()->server('HTTP_REFERER')) {
                    $back_act = request()->server('HTTP_REFERER');
                } else {
                    $back_act = route('user');
                }
            }

            $captcha = intval($this->config['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            $login_banner = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $login_banner .= "'login_banner" . $i . ","; // 登录页轮播广告 by wu
            }
            $this->smarty->assign('login_banner', $login_banner);

            /* 获取安装的社会化登录列表 */
            $WebsiteList = $this->userCommonService->getWebsiteList();

            $insert = [];
            foreach ($WebsiteList as $k => $v) {
                // 未安装不显示
                if ($v['install'] == 0) {
                    unset($WebsiteList[$k]);
                }
                if ($v['type'] == 'wechat') {
                    unset($WebsiteList[$k]); // 不显示微信授权登录（微信端）
                }
            }

            $app_client = config('app.app_client');
            $this->smarty->assign('app_client', urlencode($app_client)); // app扫码登录
            $this->smarty->assign('website_list', $WebsiteList);
            $this->smarty->assign('back_act', urlencode($back_act)); //url编码避免替换&act by wu
            return $this->smarty->display('user_passport.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 退出会员中心
        /* ------------------------------------------------------ */
        elseif ($action == 'logout') {
            if (empty($back_act)) {
                if (empty($back_act) && request()->server('HTTP_REFERER')) {
                    $back_act = request()->server('HTTP_REFERER');
                }
            }

            Sessions::where('userid', session('user_id'))->where('adminid', 0)->delete();

            $GLOBALS['user']->logout();
            $ucdata = empty($GLOBALS['user']->ucdata) ? "" : $GLOBALS['user']->ucdata;

            return redirect("user.php?act=login");
        }

        /* ------------------------------------------------------ */
        //-- 个人资料页面
        /* ------------------------------------------------------ */
        elseif ($action == 'profile') {
            load_helper('transaction');
            /* 短信发送设置 */ //wang
            if (intval($this->config['sms_signin']) > 0) {
                $sms_security_code = rand(1000, 9999);

                session([
                    'sms_security_code' => $sms_security_code
                ]);

                $this->smarty->assign('sms_security_code', $sms_security_code);
                $this->smarty->assign('enabled_sms_signin', 1);
            }

            $user_info = get_profile($user_id);

            /* 取出注册扩展字段 */
            $where = [
                'type' => ['<', 2],
                'display' => 1,
                'sort' => ['dis_order', 'id'],
                'order' => 'asc',
            ];

            $extend_info_list = $this->userService->getRegFieldsList($where);

            $extend_info_arr = RegExtendInfo::where('user_id', $user_id)->get();
            $extend_info_arr = $extend_info_arr ? $extend_info_arr->toArray() : [];

            $temp_arr = [];
            if ($extend_info_arr) {
                foreach ($extend_info_arr as $val) {
                    $temp_arr[$val['reg_field_id']] = $val['content'];
                }
            }

            if ($extend_info_list) {
                foreach ($extend_info_list as $key => $val) {
                    switch ($val['id']) {
                        case 1:
                            $extend_info_list[$key]['content'] = $user_info['msn'];
                            break;
                        case 2:
                            $extend_info_list[$key]['content'] = $user_info['qq'];
                            break;
                        case 3:
                            $extend_info_list[$key]['content'] = $user_info['office_phone'];
                            break;
                        case 4:
                            $extend_info_list[$key]['content'] = $user_info['home_phone'];
                            break;
                        case 5:
                            $extend_info_list[$key]['content'] = $user_info['mobile_phone'];
                            break;
                        default:
                            $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
                    }
                }
            }

            $select_date = [];
            $select_date['year'] = range(date("Y", strtotime("-60 year")), date("Y", strtotime("+1 year")));
            $select_date['month'] = range(1, 12);
            $select_date['day'] = range(1, 31);
            $this->smarty->assign("select_date", $select_date);
            if ($user_info['birthday']) {
                $birthday = explode('-', $user_info['birthday']);
                $user_info['year'] = intval($birthday[0]);
                $user_info['month'] = intval($birthday[1]);
                $user_info['day'] = intval($birthday[2]);
            }

            $user_info['passwd_question_cn'] = isset($user_info['passwd_question']) && $user_info['passwd_question'] ? $GLOBALS['_LANG']['passwd_questions'][$user_info['passwd_question']] : '';
            $this->smarty->assign('extend_info_list', $extend_info_list);

            /* 密码提示问题 */
            $this->smarty->assign('passwd_questions', $GLOBALS['_LANG']['passwd_questions']);

            //注册短信是否开启
            $this->smarty->assign('sms_register', $this->config['sms_signin']);
            $this->smarty->assign('profile', $user_info);

            $user_default_info['user_picture'] = $user_info ? $user_info['user_picture'] : '';
            $this->smarty->assign('user_default_info', $user_default_info);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员头像
        /* ------------------------------------------------------ */
        elseif ($action == 'user_picture') {
            $create = $this->userService->CreatePassword();

            $img_sir = "data/images_user/" . session('user_id');

            if (file_exists($img_sir . "_120.jpg")) {
                $img_sir = $img_sir . "_120.jpg";
            } else {
                $img_sir = "data/images_user/0_120.jpg";
            }

            $this->smarty->assign('create', $create);
            $this->smarty->assign('img_sir', get_image_path($img_sir));
            $this->smarty->assign('user_id', session('user_id'));

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 修改个人资料的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_edit_profile') {
            load_helper('transaction');
            $request = get_request_filter(request()->all(), 1);

            $birthdayYear = request()->input('birthdayYear', '');
            $birthdayMonth = request()->input('birthdayMonth', '');
            $birthdayDay = request()->input('birthdayDay', '');
            $birthday = trim($birthdayYear) . '-' . trim($birthdayMonth) . '-' . trim($birthdayDay);

            $email = addslashes(trim(request()->input('email', '')));

            $other['msn'] = $msn = addslashes(trim(request()->input('extend_field1', '')));
            $other['qq'] = $qq = addslashes(trim(request()->input('extend_field2', '')));
            $other['office_phone'] = $office_phone = addslashes(trim(request()->input('extend_field3', '')));
            $other['home_phone'] = $home_phone = addslashes(trim(request()->input('extend_field4', '')));

            $mobile_phone = addslashes(trim(request()->input('mobile_phone', '')));
            $sel_question = compile_str(trim(request()->input('sel_question', '')));
            $passwd_answer = compile_str(trim(request()->input('passwd_answer', '')));
            //手机动态码
            $mobile_code = trim(request()->input('mobile_code', ''));
            $nick_name = compile_str(request()->input('nick_name', ''));

            Users::where('user_id', $user_id)->update(['nick_name' => $nick_name]);

            /* 更新用户扩展字段的数据 */
            $where = [
                'type' => 0,
                'display' => 1,
                'sort' => ['dis_order', 'id'],
                'order' => 'asc',
            ];

            $fields_arr = $this->userService->getRegFieldsList($where);

            if ($fields_arr) {
                foreach ($fields_arr as $val) {       //循环更新扩展用户信息
                    $extend_field_index = 'extend_field' . $val['id'];
                    if (isset($request[$extend_field_index])) {
                        $temp_field_content = strlen($request[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($request[$extend_field_index]), 0, 99) : htmlspecialchars($request[$extend_field_index]);

                        $count = RegExtendInfo::where('reg_field_id', $val['id'])->where('user_id', $user_id)->count();

                        //如果之前没有记录，则插入
                        if ($count) {
                            RegExtendInfo::where('reg_field_id', $val['id'])->where('user_id', $user_id)->update(['content' => $temp_field_content]);
                        } else {
                            $other = [
                                'user_id' => $user_id,
                                'reg_field_id' => $val['id'],
                                'content' => $temp_field_content
                            ];
                            RegExtendInfo::insert($other);
                        }
                    }
                }
            }


            /* 写入密码提示问题和答案 */
            if (!empty($passwd_answer) && !empty($sel_question)) {
                $other = [
                    'passwd_question' => $sel_question,
                    'passwd_answer' => $passwd_answer
                ];
                Users::where('user_id', session('user_id'))->update($other);
            }

            if (!empty($msn) && !is_email($msn)) {
                return show_message($GLOBALS['_LANG']['passport_js']['msn_invalid']);
            }
            if (!empty($qq) && !preg_match('/^\d+$/', $qq)) {
                return show_message($GLOBALS['_LANG']['passport_js']['qq_invalid']);
            }
            if (!empty($mobile_phone) && !is_mobile($mobile_phone)) {
                return show_message($GLOBALS['_LANG']['passport_js']['mobile_phone_invalid']);
            }

            $profile = [
                'user_id' => $user_id,
                'email' => addslashes(request()->input('email', '')),
                'mobile_phone' => $mobile_phone,
                'mobile_code' => $mobile_code,
                'sex' => (int)request()->input('sex', 0),
                'birthday' => $birthday,
                'other' => isset($other) ? $other : []
            ];

            if (edit_profile($profile)) {

                //记录会员操作日志
                users_log_change(session('user_id'), USER_INFO);
                return show_message($GLOBALS['_LANG']['edit_profile_success'], $GLOBALS['_LANG']['profile_lnk'], 'user.php?act=profile', 'info');
            } else {
                if ($GLOBALS['user']->error == ERR_EMAIL_EXISTS) {
                    $msg = sprintf($GLOBALS['_LANG']['email_exist'], $profile['email']);
                } elseif ($GLOBALS['user']->error == ERR_PHONE_EXISTS) {
                    $msg = sprintf($GLOBALS['_LANG']['phone_exist'], $profile['mobile_phone']);
                } elseif ($this->err->error_no()) {
                    $msg = $GLOBALS['_LANG']['Mobile_code_error'];
                } else {
                    $msg = $GLOBALS['_LANG']['edit_profile_failed'];
                }
                return show_message($msg, '', '', 'info');
            }
        }

        /* ------------------------------------------------------ */
        //-- 密码找回-->修改密码界面
        /* ------------------------------------------------------ */
        elseif ($action == 'get_password') {
            $this->smarty->assign('cfg', $GLOBALS['_CFG']);

            load_helper('passport');

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            /* 短信发送设置 */
            if (intval($this->config['sms_code']) > 0) { // 这里
                $sms_security_code = rand(1000, 9999);

                session([
                    'sms_security_code' => $sms_security_code
                ]);

                $this->smarty->assign('sms_security_code', $sms_security_code);
                $this->smarty->assign('enabled_sms_signin', 1);
            } else {
                $this->smarty->assign('enabled_sms_signin', 0);
            }

            if (request()->exists('code')) { //从邮件处获得的act
                $code = addslashes(trim(request()->input('code', '')));
                $uid = (int)request()->input('uid', 0);

                /* 判断链接的合法性 */
                $user_info = $GLOBALS['user']->get_profile_by_id($uid);
                if (empty($user_info) || ($user_info && md5($user_info['user_id'] . $this->config['hash_code'] . $user_info['reg_time'] . $user_info['password']) != $code)) {
                    return show_message($GLOBALS['_LANG']['parm_error'], $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
                }

                $user_id = session(['pass_uid' => $uid]);

                $this->smarty->assign('code', $code);
                $this->smarty->assign('action', 'reset_password');
                return $this->smarty->display('user_passport.dwt');
            } else {

                /* 取出注册扩展字段 */
                $where = [
                    'type' => ['<', 2],
                    'display' => 1,
                    'sort' => ['dis_order', 'id'],
                    'order' => 'asc',
                ];

                $extend_info_list = $this->userService->getRegFieldsList($where);
                $this->smarty->assign('extend_info_list', $extend_info_list);

                /* 密码提示问题 */
                $this->smarty->assign('passwd_questions', $GLOBALS['_LANG']['passwd_questions']);

                //显示用户名和email表单
                return $this->smarty->display('user_passport.dwt');
            }
        }

        /* ------------------------------------------------------ */
        //-- 密码找回-->输入用户名界面
        /* ------------------------------------------------------ */
        elseif ($action == 'qpassword_name') {
            //显示输入要找回密码的账号表单
            return $this->smarty->display('user_passport.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 密码找回-->根据注册用户名取得密码提示问题界面
        /* ------------------------------------------------------ */
        elseif ($action == 'get_passwd_question') {
            $request = get_request_filter(request()->all(), 1);

            if (empty($request['user_name'])) {
                return show_message($GLOBALS['_LANG']['no_passwd_question'], $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
            } else {
                $user_name = trim($request['user_name']);
            }

            //取出会员密码问题和答案
            $user_question_arr = Users::where('user_name', $user_name)->first();
            $user_question_arr = $user_question_arr ? $user_question_arr->toArray() : [];

            //如果没有设置密码问题，给出错误提示
            if (empty($user_question_arr['passwd_answer'])) {
                return show_message($GLOBALS['_LANG']['no_passwd_question'], $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
            }

            session([
                'temp_user' => $user_question_arr['user_id'], //设置临时用户，不具有有效身份
                'temp_user_name' => $user_question_arr['user_name'], //设置临时用户，不具有有效身份
                'passwd_answer' => $user_question_arr['passwd_answer'] //存储密码问题答案，减少一次数据库访问
            ]);

            $captcha = intval($this->config['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            $this->smarty->assign('passwd_question', $GLOBALS['_LANG']['passwd_questions'][$user_question_arr['passwd_question']]);
            return $this->smarty->display('user_passport.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 密码找回-->根据提交的密码答案进行相应处理
        /* ------------------------------------------------------ */
        elseif ($action == 'check_answer') {
            $request = get_request_filter(request()->all(), 1);

            $user_name = empty($request['user_name']) ? '' : compile_str(trim($request['user_name']));
            $sel_question = empty($request['sel_question']) ? '' : compile_str($request['sel_question']);
            $passwd_answer = isset($request['passwd_answer']) ? compile_str(trim($request['passwd_answer'])) : '';
            $captcha_str = isset($request['captcha']) ? trim($request['captcha']) : '';

            if ((intval($this->config['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                $verify = app(CaptchaVerify::class);
                $captcha_code = $verify->check($captcha_str, 'psw_question');

                if (!$captcha_code) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_up_page'], 'user.php?act=get_password', 'error');
                }
            }

            //取出会员密码问题和答案
            $user_question_arr = Users::where('user_name', $user_name)->where('passwd_question', $sel_question)->where('passwd_answer', $passwd_answer)->first();
            $user_question_arr = $user_question_arr ? $user_question_arr->toArray() : [];

            if (empty($user_question_arr)) {
                return show_message($GLOBALS['_LANG']['wrong_passwd_answer'], '', 'user.php?act=get_password', 'info');
            } else {

                session([
                    'pass_uid' => $user_question_arr['user_id'],
                    'pass_user_name' => $user_question_arr['user_name']
                ]);

                $this->smarty->assign('action', 'reset_password');
                return $this->smarty->display('user_passport.dwt');
            }
        }

        /* ------------------------------------------------------ */
        //-- 发送密码修改确认邮件
        /* ------------------------------------------------------ */
        elseif ($action == 'send_pwd_email') {
            load_helper('passport');

            $request = get_request_filter(request()->all(), 1);

            /* 初始化会员用户名和邮件地址 */
            $user_name = !empty($request['user_name']) ? trim($request['user_name']) : '';
            $email = !empty($request['email']) ? trim($request['email']) : '';

            $captcha_str = isset($request['captcha']) ? trim($request['captcha']) : '';

            $verify = app(CaptchaVerify::class);
            $captcha_code = $verify->check($captcha_str, 'get_password');

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                if (!$captcha_code) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_up_page'], 'user.php?act=get_password', 'error');
                }
            }

            //用户名和邮件地址是否匹配
            $user_info = $GLOBALS['user']->get_user_info($user_name);

            if ($user_info && $user_info['email'] == $email) {

                //生成code
                $code = md5($user_info['user_id'] . $this->config['hash_code'] . $user_info['reg_time'] . $user_info['password']);

                //发送邮件的函数
                if (send_pwd_email($user_info['user_id'], $user_name, $email, $code)) {
                    return show_message($GLOBALS['_LANG']['send_success'] . $email, $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
                } else {
                    //发送邮件出错
                    return show_message($GLOBALS['_LANG']['fail_send_password'], $GLOBALS['_LANG']['back_page_up'], './', 'info');
                }
            } else {
                //用户名与邮件地址不匹配
                return show_message($GLOBALS['_LANG']['username_no_email'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
            }
        }

        /* ------------------------------------------------------ */
        //-- 密码找回-->根据提交手机号，手机验证码相应处理
        /* ------------------------------------------------------ */
        elseif ($action == 'get_pwd_mobile') {
            load_helper('passport');

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                $captcha_str = addslashes(trim(request()->input('captcha', '')));

                $verify = app(CaptchaVerify::class);
                $captcha_code = $verify->check($captcha_str, 'get_phone_password');

                if (!$captcha_code) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_up_page'], 'user.php?act=get_password', 'error');
                }
            }

            $request = get_request_filter(request()->all(), 1);

            $user_name = isset($request['user_name']) && !empty($request['user_name']) ? addslashes(trim($request['user_name'])) : '';
            $mobile_phone = isset($request['mobile_phone']) && !empty($request['mobile_phone']) ? addslashes(trim($request['mobile_phone'])) : '';
            $mobile_code = isset($request['mobile_code']) && !empty($request['mobile_code']) ? addslashes(trim($request['mobile_code'])) : '';

            $is_phone = $this->commonRepository->getMatchPhone($mobile_phone);

            if (!$is_phone) {
                return show_message($GLOBALS['_LANG']['Mobile_username'], $GLOBALS['_LANG']['back_retry_answer'], 'user.php?act=get_password', 'info');
            }

            if (empty($mobile_phone) || empty($mobile_code)) {
                return show_message($GLOBALS['_LANG']['Mobile_code_null'], $GLOBALS['_LANG']['back_retry_answer'], 'user.php?act=get_password', 'info');
            }

            if ($mobile_code != session('sms_mobile_code')) {
                return show_message($GLOBALS['_LANG']['Mobile_code_msg'], $GLOBALS['_LANG']['back_retry_answer'], 'user.php?act=get_password', 'info');
            }

            // 增加短信验证码校验次数


            $user_arr = Users::where('mobile_phone', $mobile_phone);

            if (!empty($user_name)) {
                $user_arr = $user_arr->where('user_name', $user_name);
            }

            $user_arr = $this->baseRepository->getToArrayFirst($user_arr);

            $code = md5($user_arr['user_id'] . $this->config['hash_code'] . $user_arr['reg_time'] . $user_arr['password']);

            session(['pass_uid' => $user_arr['user_id']]);

            $this->smarty->assign('action', 'reset_password');
            $this->smarty->assign('code', $code);
            return $this->smarty->display('user_passport.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 重置新密码
        /* ------------------------------------------------------ */
        elseif ($action == 'reset_password') {
            //显示重置密码的表单
            return $this->smarty->display('user_passport.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 修改会员密码
        /* ------------------------------------------------------ */
        elseif ($action == 'act_edit_password') {
            load_helper('passport');

            $request = get_request_filter(request()->all(), 1);

            $old_password = isset($request['old_password']) ? trim($request['old_password']) : '';
            $new_password = isset($request['new_password']) ? trim($request['new_password']) : '';
            $code = isset($request['code']) ? trim($request['code']) : '';
            $captcha_str = isset($request['captcha']) ? trim($request['captcha']) : '';

            if (request()->session()->exists('pass_uid')) {
                $user_id = session('pass_uid');
            }

            $verify = app(CaptchaVerify::class);
            $captcha_code = $verify->check($captcha_str, 'get_password');

            // 确认密码 qin
            $comfirm_password = isset($request['confirm_password']) ? trim($request['confirm_password']) : '';

            if (strlen($new_password) < 6) {
                return show_message($GLOBALS['_LANG']['passport_js']['password_shorter']);
            }
            if (strlen($new_password) !== strlen($comfirm_password)) {
                return show_message($GLOBALS['_LANG']['password_difference']);
            }

            $user_info = $GLOBALS['user']->get_profile_by_id($user_id); //论坛记录

            $is_true = false;
            $is_oldpwd = false;
            $oldpwd_type = 0;
            if (request()->session()->exists('pass_user_name') && $user_info) {
                $is_true = true;
                $is_oldpwd = true;
            }

            $user_name = $user_info['user_name'];

            if (!empty($old_password)) {
                $is_oldpwd = $GLOBALS['user']->check_user($user_name, $old_password);
                $oldpwd_type = 1;
            }

            if (($user_info && (!empty($code) && md5($user_info['user_id'] . $this->config['hash_code'] . $user_info['reg_time'] . $user_info['password']) == $code)) || ($is_true && $is_oldpwd)) {

                //老密码判断去除  empty($code) ? 0 : 1 无效， 直接改为 1
                if ($GLOBALS['user']->edit_user(['user_id' => $user_id, 'username' => $user_name, 'old_password' => $old_password, 'password' => $new_password], 1)) {

                    if (request()->session()->exists('pass_uid')) {
                        request()->session()->forget('pass_uid');
                    }

                    if (request()->session()->exists('pass_user_name')) {
                        request()->session()->forget('pass_user_name');
                    }

                    $GLOBALS['user']->logout();
                    return show_message($GLOBALS['_LANG']['edit_password_success'], $GLOBALS['_LANG']['relogin_lnk'], 'user.php?act=login', 'info');
                } else {
                    if (empty($code) && $oldpwd_type == 1) {
                        return show_message($GLOBALS['_LANG']['edit_password_oldpwd_failure'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
                    } else {
                        return show_message($GLOBALS['_LANG']['edit_password_failure'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
                    }
                }
            } else {
                if (empty($code) && $oldpwd_type == 1) {
                    return show_message($GLOBALS['_LANG']['edit_password_oldpwd_failure'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
                } else {
                    return show_message($GLOBALS['_LANG']['edit_password_failure'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
                }
            }
        }

        /* ------------------------------------------------------ */
        //-- 储值卡充值
        /* ------------------------------------------------------ */
        elseif ($action == 'to_paid') {
            $value_card_id = (int)request()->input('vid', 0);
            if ($value_card_id > 0) {
                $this->smarty->assign('action', $action);
                $this->smarty->assign('vid', $value_card_id);
                return $this->smarty->display('user_transaction.dwt');
            } else {
                return dsc_header("location:user.php?act=value_card");
            }
        }

        /* ------------------------------------------------------ */
        //-- 查看提货列表
        /* ------------------------------------------------------ */
        elseif ($action == 'take_list') {
            load_helper('transaction');

            $page = (int)request()->input('page', 1);

            $record_count = UserGiftGard::where('user_id', $user_id)->count();

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            $row = UserGiftGard::where('user_id', $user_id);

            $row = $row->orderBy('user_time', 'desc');

            if ($pager['start'] > 0) {
                $row = $row->skip($pager['start']);
            }

            if ($pager['size'] > 0) {
                $row = $row->take($pager['size']);
            }

            $row = $this->baseRepository->getToArrayGet($row);

            if ($row) {
                foreach ($row as $key => $val) {
                    $row[$key]['gift_name'] = GiftGardType::where('gift_id', $val['gift_id'])->value('gift_name');

                    $user = Users::select('user_name', 'email')->where('user_id', $val['user_id']);
                    $user = $this->baseRepository->getToArrayFirst($user);

                    $row[$key]['user_name'] = $user ? $user['user_name'] : '';
                    $row[$key]['email'] = $user ? $user['email'] : '';

                    $goods = Goods::select('goods_name', 'goods_thumb')->where('goods_id', $val['goods_id'])->first();
                    $goods = $goods ? $goods->toArray() : [];

                    $row[$key]['goods_name'] = $goods ? $goods['goods_name'] : '';
                    $row[$key]['goods_thumb'] = $goods ? get_image_path($goods['goods_thumb']) : '';

                    $row[$key]['user_time'] = local_date('Y-m-d H:i:s', empty($val['user_time']) ? '' : $val['user_time']);
                }
            }

            $this->smarty->assign('pager', $pager);

            $this->smarty->assign('take_list', $row);
            return $this->smarty->display('user_clips.dwt');
        } elseif ($action == 'confim_goods') {
            load_helper('transaction');

            $take_id = (int)request()->input('take_id', 1);

            $up_id = UserGiftGard::where('gift_gard_id', $take_id)->update(['status' => 3]);

            if ($up_id) {
                return dsc_header("Location: user.php?act=take_list\n");
            } else {
                return show_message($GLOBALS['_LANG']['receipt_fail'], $GLOBALS['_LANG']['back_receipt'], 'user.php?act=take_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 添加我的留言
        /* ------------------------------------------------------ */
        elseif ($action == 'act_add_message') {
            load_helper('clips');

            $request = get_request_filter(request()->all(), 1);

            $is_order = isset($request['is_order']) ? intval($request['is_order']) : 0;
            $message = [
                'user_id' => $user_id,
                'user_name' => session('user_name'),
                'user_email' => session('email', ''),
                'msg_type' => isset($request['msg_type']) ? intval($request['msg_type']) : 0,
                'msg_title' => isset($request['msg_title']) ? trim($request['msg_title']) : '',
                'msg_content' => isset($request['msg_content']) ? trim($request['msg_content']) : '',
                'order_id' => empty($request['order_id']) ? 0 : intval($request['order_id']),
                'upload' => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none')
                    ? $_FILES['message_img'] : []
            ];

            if (add_message($message)) {
                if ($is_order) {
                    return show_message($GLOBALS['_LANG']['add_message_success'], $GLOBALS['_LANG']['message_list_lnk'], 'user_message.php?act=message_list&is_order=1&order_id=' . $message['order_id'], 'info');
                } else {
                    return show_message($GLOBALS['_LANG']['add_message_success'], $GLOBALS['_LANG']['message_list_lnk'], 'user_message.php?act=message_list&order_id=' . $message['order_id'], 'info');
                }
            } else {
                return $this->err->show($GLOBALS['_LANG']['message_list_lnk'], 'javascript:history.go(-1);');
            }
        }

        /* ------------------------------------------------------ */
        //-- 标签云列表
        /* ------------------------------------------------------ */
        elseif ($action == 'tag_list') {
            load_helper('clips');

            $good_id = (int)request()->input('id', 0);

            $this->smarty->assign('tags', get_user_tags($user_id));
            $this->smarty->assign('tags_from', 'user');
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 删除标签云的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_del_tag') {
            load_helper('clips');

            $tag_words = addslashes(trim(request()->input('tag_words', '')));
            delete_tag($tag_words, $user_id);

            return dsc_header("Location: user.php?act=tag_list\n");
        }

        /* ------------------------------------------------------ */
        //-- 显示缺货登记列表
        /* ------------------------------------------------------ */
        elseif ($action == 'booking_list') {
            load_helper('clips');

            $page = (int)request()->input('page', 1);

            /* 获取缺货登记的数量 */
            $record_count = BookingGoods::where('user_id', $user_id)->count();

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            $this->smarty->assign('booking_list', get_booking_list($user_id, $pager['size'], $pager['start']));
            $this->smarty->assign('pager', $pager);
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 添加缺货登记页面
        /* ------------------------------------------------------ */
        elseif ($action == 'add_booking') {
            load_helper('clips');

            $goods_id = (int)request()->input('id', 0);
            if ($goods_id == 0) {
                return show_message($GLOBALS['_LANG']['no_goods_id'], $GLOBALS['_LANG']['back_page_up'], '', 'error');
            }

            /* 查询：取得规格 */
            $specs = htmlspecialchars(trim(request()->input('spec', '')));

            /* 根据规格属性获取货品规格信息 */
            $goods_attr = $this->goodsService->getGoodsAttrList($specs);

            $this->smarty->assign('goods_attr', $goods_attr);

            $this->smarty->assign('info', get_goodsinfo($goods_id));
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 添加缺货登记的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_add_booking') {
            load_helper('clips');

            $request = get_request_filter(request()->all(), 1);

            $booking = [
                'goods_id' => isset($request['id']) ? intval($request['id']) : 0,
                'goods_amount' => isset($request['number']) ? intval($request['number']) : 0,
                'desc' => isset($request['desc']) ? trim($request['desc']) : '',
                'linkman' => isset($request['linkman']) ? trim($request['linkman']) : '',
                'email' => isset($request['email']) ? trim($request['email']) : '',
                'tel' => isset($request['tel']) ? trim($request['tel']) : '',
                'booking_id' => isset($request['rec_id']) ? intval($request['rec_id']) : 0
            ];

            // 查看此商品是否已经登记过
            $rec_id = get_booking_rec($user_id, $booking['goods_id']);
            if ($rec_id > 0) {
                return show_message($GLOBALS['_LANG']['booking_rec_exist'], $GLOBALS['_LANG']['back_page_up'], '', 'error');
            }

            if (add_booking($booking)) {
                return show_message(
                    $GLOBALS['_LANG']['booking_success'],
                    $GLOBALS['_LANG']['back_booking_list'],
                    'user.php?act=booking_list',
                    'info'
                );
            } else {
                return $this->err->show($GLOBALS['_LANG']['booking_list_lnk'], 'user.php?act=booking_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除缺货登记
        /* ------------------------------------------------------ */
        elseif ($action == 'act_del_booking') {
            load_helper('clips');

            $id = (int)request()->input('id', 0);
            if ($id == 0 || $user_id == 0) {
                return dsc_header("Location: user.php?act=booking_list\n");
            }

            $result = delete_booking($id, $user_id);
            if ($result) {
                return dsc_header("Location: user.php?act=booking_list\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 会员退款申请界面
        /* ------------------------------------------------------ */
        elseif ($action == 'account_raply') {

            // 实名认证
            $validate_info = $this->userService->GetValidateInfo(session('user_id'));

            if ($validate_info['review_status'] != 1) {
                $Loaction = "user.php?act=account_safe&type=real_name&step=realname_ok";

                return dsc_header("Location: $Loaction\n");
            }

            /* 短信发送设置 */
            if (intval($this->config['sms_code']) > 0) { // 这里
                $sms_security_code = rand(1000, 9999);

                session([
                    'sms_security_code' => $sms_security_code
                ]);

                $this->smarty->assign('sms_security_code', $sms_security_code);
                $this->smarty->assign('enabled_sms_signin', 1);
            }

            $sc_rand = rand(1000, 9999);
            $sc_guid = sc_guid();

            $account_cookie = MD5($sc_guid . "-" . $sc_rand);
            cookie()->queue('user_account_cookie', $account_cookie, 60 * 24 * 30);

            $this->smarty->assign('sc_guid', $sc_guid);
            $this->smarty->assign('sc_rand', $sc_rand);

            $user_info = $this->userCommonService->getUserDefault(session('user_id'));

            // 实名认证
            $validate_info = $this->userService->GetValidateInfo(session('user_id'));
            $this->smarty->assign('validate_info', $validate_info);

            //会员提现手续费
            $this->smarty->assign('deposit_fee', $this->config['deposit_fee']);

            //会员提现最低限额
            $this->smarty->assign('buyer_cash', $this->config['buyer_cash']);

            $this->smarty->assign('user_info', $user_info);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员预付款界面
        /* ------------------------------------------------------ */
        elseif ($action == 'account_deposit') {
            load_helper('clips');

            $surplus_id = (int)request()->input('id', 0);
            $account = get_surplus_info($surplus_id, $user_id);
            $user_info = $this->userCommonService->getUserDefault(session('user_id'));

            // 实名认证
            $validate_info = $this->userService->GetValidateInfo(session('user_id'));
            $this->smarty->assign('validate_info', $validate_info);

            $this->smarty->assign('payment', get_online_payment_list(false));
            $this->smarty->assign('order', $account);
            $this->smarty->assign('user_info', $user_info);

            //会员充值最低限额
            $this->smarty->assign('buyer_recharge', $this->config['buyer_recharge']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员账目明细界面
        /* ------------------------------------------------------ */
        elseif ($action == 'account_detail' || $action == 'account_paypoints' || $action == 'account_rankpoints') {
            load_helper('clips');

            $page = (int)request()->input('page', 1);
            if ($action == 'account_detail') {
                $account_type = 'user_money';
                $size = 15;
            } elseif ($action == 'account_paypoints') {
                $account_type = 'pay_points';
                $size = 10;
            } elseif ($action == 'account_rankpoints') {
                $account_type = 'rank_points';
                $size = 10;
            }

            /* 获取记录条数 */
            $record_count = $this->userService->GetUserAccountLogCount($user_id, $account_type);

            //分页函数
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page, $size);

            //获取剩余余额
            $surplus_amount = Users::where('user_id', $user_id)->value('user_money');

            if (empty($surplus_amount)) {
                $surplus_amount = 0;
            }

            // 实名认证
            $validate_info = $this->userService->GetValidateInfo(session('user_id'));
            $this->smarty->assign('validate_info', $validate_info);

            $info = $this->userCommonService->getUserDefault($user_id);
            $this->smarty->assign('info', $info);

            //优惠券
            $cou = $this->userService->getCouponsUserTotal($user_id);

            $cou['money'] = price_format($cou['money']);
            $this->smarty->assign('coupons', $cou);

            //储值卡
            $vc = ValueCard::selectRaw("COUNT(*) AS num, SUM(card_money) AS money")->where('user_id', $user_id)->first();
            $vc = $vc ? $vc->toArray() : [];

            $vc['money'] = price_format($vc['money']);
            $this->smarty->assign('value_card', $vc);

            //获取余额记录
            $account_log = $this->userService->GetUserAccountLogList($user_id, $account_type, $pager);

            //模板赋值
            $this->smarty->assign('surplus_amount', price_format($surplus_amount, false));
            $this->smarty->assign('account_log', $account_log);
            $this->smarty->assign('pager', $pager);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员充值和提现申请记录
        /* ------------------------------------------------------ */
        elseif ($action == 'account_log') {
            load_helper('clips');

            $page = (int)request()->input('page', 1);
            /* 获取记录条数 */
            $record_count = UserAccount::where('user_id', $user_id)->whereIn('process_type', [SURPLUS_SAVE, SURPLUS_RETURN])->count();

            //分页函数
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            //获取剩余余额
            $surplus_amount = Users::where('user_id', $user_id)->value('user_money');

            if (empty($surplus_amount)) {
                $surplus_amount = 0;
            }

            //获取余额记录
            $account_log = get_account_log($user_id, $pager['size'], $pager['start']);
            $info = $this->userCommonService->getUserDefault($user_id);
            $this->smarty->assign('info', $info);

            //优惠券
            $cou = $this->userService->getCouponsUserTotal($user_id);

            $cou['money'] = price_format($cou['money']);
            $this->smarty->assign('coupons', $cou);

            //储值卡
            $vc = ValueCard::selectRaw("COUNT(*) AS num, SUM(card_money) AS money")->where('user_id', $user_id)->first();
            $vc = $vc ? $vc->toArray() : [];

            $vc['money'] = price_format($vc['money']);
            $this->smarty->assign('value_card', $vc);

            // 实名认证
            $validate_info = $this->userService->GetValidateInfo(session('user_id'));

            $validate_info['review_status'] = !empty($validate_info['review_status']) ? $validate_info['review_status'] : 0;

            if ($validate_info['review_status'] != 1) {
                $validate_info['real_name'] = '';
            }

            $this->smarty->assign('validate_info', $validate_info);

            //模板赋值
            $this->smarty->assign('surplus_amount', price_format($surplus_amount, false));
            $this->smarty->assign('account_log', $account_log);
            $this->smarty->assign('pager', $pager);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员充值申述添加
        /* ------------------------------------------------------ */
        elseif ($action == 'account_complaint') {
            $id = (int)request()->input('id', 0);

            load_helper('clips');
            $user_account = get_user_account_log($id);

            if (empty($id) || empty($user_account) || $user_account['is_paid'] == 1) {
                $Loaction = "user.php?act=account_log";
                return dsc_header("Location: $Loaction\n");
            }

            $this->smarty->assign('user_account', $user_account);
            $this->smarty->assign('operate', "account_complaint_insert");
            $this->smarty->assign('id', $id);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员充值申述添加
        /* ------------------------------------------------------ */
        elseif ($action == 'account_complaint_insert') {
            $id = (int)request()->input('id', 0);
            $complaint_imges = $image->upload_image($_FILES['complaint_imges'], 'complaint_imges');
            $complaint_details = addslashes(trim(request()->input('complaint_details', '')));

            $other = [
                'complaint_imges' => $complaint_imges,
                'complaint_details' => $complaint_details,
                'complaint_time' => gmtime()
            ];

            UserAccount::where('id', $id)->where('user_id', $user_id)->update($other);

            return show_message($GLOBALS['_LANG']['complaint_success'], $GLOBALS['_LANG']['back_up_page'], 'user.php?act=account_log', 'info');
        }

        /* ------------------------------------------------------ */
        //-- 对会员余额申请的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_account') {
            load_helper('clips');
            load_helper('order');

            $request = get_request_filter(request()->all(), 1);

            $amount = isset($request['amount']) ? floatval($request['amount']) : 0;
            if ($amount <= 0) {
                return show_message($GLOBALS['_LANG']['amount_gt_zero']);
            }

            /* 变量初始化 */
            $surplus = [
                'user_id' => $user_id,
                'rec_id' => !empty($request['rec_id']) ? intval($request['rec_id']) : 0,
                'process_type' => isset($request['surplus_type']) ? intval($request['surplus_type']) : 0,
                'payment_id' => isset($request['payment_id']) ? intval($request['payment_id']) : 0,
                'user_note' => isset($request['user_note']) ? trim($request['user_note']) : '',
                'amount' => $amount
            ];

            /* 退款申请的处理 */
            if ($surplus['process_type'] == 1) {
                $buyer_cash = !empty($this->config['buyer_cash']) ? intval($this->config['buyer_cash']) : 0;
                if ($amount < $buyer_cash) {
                    return show_message($GLOBALS['_LANG']['user_put_forward_notic'] . $buyer_cash . $GLOBALS['_LANG']['yuan']);
                }
                $deposit_fee = !empty($this->config['deposit_fee']) ? intval($this->config['deposit_fee']) : 0; // 提现手续费比例
                $deposit_money = 0;
                if ($deposit_fee > 0) {
                    $deposit_money = $amount * $deposit_fee / 100;
                }
                if (isset($request['mobile_code']) && !empty($request['mobile_code'])) {
                    if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                        return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                    }
                }

                /* 判断是否有足够的余额的进行退款的操作 */
                $sur_amount = get_user_surplus($user_id);
                //判断余额是否满足最小提现要求
                if (($amount - $deposit_money) > $sur_amount) {
                    $content = $GLOBALS['_LANG']['surplus_amount_error'];
                    return show_message($content, $GLOBALS['_LANG']['back_page_up'], '', 'info');
                }

                //判断手续费扣除模式，余额充足则从余额中扣除手续费，不足则在提现金额中扣除
                if (($amount + $deposit_money) > $sur_amount) {
                    $amount = $amount - $deposit_money;
                }

                $account_time = gmtime();

                $account_count = UserAccount::where('user_id', $surplus['user_id'])->where('add_time', $account_time)->count();
                if ($account_count > 0) {
                    $content = $GLOBALS['_LANG']['surplus_appl_submit'];
                    return show_message($content, $GLOBALS['_LANG']['back_account_log'], 'user.php?act=account_log', 'info');
                }

                //插入会员账目明细
                $surplus['deposit_fee'] = '-' . $deposit_money;
                //提现金额
                $frozen_money = $amount + $deposit_money;
                $amount = '-' . $amount;
                $surplus['payment'] = '';
                $surplus['rec_id'] = insert_user_account($surplus, $amount, $account_time);

                /* 如果成功提交 */
                if ($surplus['rec_id'] > 0) {
                    //by wang提现记录扩展信息start
                    $user_account_fields = [
                        'user_id' => $surplus['user_id'],
                        'account_id' => $surplus['rec_id'],
                        'bank_number' => !empty($request['bank_number']) ? trim($request['bank_number']) : '',
                        'real_name' => !empty($request['real_name']) ? trim($request['real_name']) : ''
                    ];

                    insert_user_account_fields($user_account_fields);
                    //by wang提现记录扩展信息end

                    /* 保存 */
                    log_account_change($user_id, $amount, $frozen_money, 0, 0, "【" . $GLOBALS['_LANG']['application_withdrawal'] . "】" . $surplus['user_note'], ACT_ADJUSTING, 0, $surplus['deposit_fee']);

                    $content = $GLOBALS['_LANG']['surplus_appl_submit'];
                    return show_message($content, $GLOBALS['_LANG']['back_account_log'], 'user.php?act=account_log', 'info');
                } else {
                    $content = $GLOBALS['_LANG']['process_false'];
                    return show_message($content, $GLOBALS['_LANG']['back_page_up'], '', 'info');
                }
            } /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
            else {
                $buyer_recharge = !empty($this->config['buyer_recharge']) ? intval($this->config['buyer_recharge']) : 0;
                if ($amount < $buyer_recharge) {
                    return show_message($GLOBALS['_LANG']['user_recharge_notic'] . $buyer_cash . $GLOBALS['_LANG']['yuan']);
                }
                if ($surplus['payment_id'] <= 0) {
                    return show_message($GLOBALS['_LANG']['select_payment_pls']);
                }

                load_helper('payment');

                //获取支付方式名称
                $payment_info = [];
                $payment_info = payment_info($surplus['payment_id']);
                $surplus['payment'] = $payment_info['pay_name'];


                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                if ($payment_info['pay_code'] == 'wxpay') {
                    if (!$payment['wxpay_appid']) {
                        return show_message($GLOBALS['_LANG']['user_pay_code_null']);
                    }
                }

                if ($surplus['rec_id'] > 0) {
                    //更新会员账目明细
                    $surplus['rec_id'] = update_user_account($surplus);
                } else {
                    //插入会员账目明细
                    $surplus['rec_id'] = insert_user_account($surplus, $amount);
                }

                //生成伪订单号, 不足的时候补0
                $order = [];
                $order['order_sn'] = $surplus['rec_id'];
                $order['user_name'] = session('user_name');
                $order['surplus_amount'] = $amount;
                $order['subject'] = lang('user.account_deposit');// 充值描述

                //计算支付手续费用
                $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

                //计算此次预付款需要支付的总金额
                $order['order_amount'] = $amount + $payment_info['pay_fee'];

                //记录支付log
                $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], PAY_SURPLUS, 0);

                if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                    /* 调用相应的支付方式文件 */
                    $payObject = $this->commonRepository->paymentInstance($payment_info['pay_code']);
                    /* 取得在线支付方式的支付按钮 */
                    if (!is_null($payObject)) {
                        $payment_info['pay_button'] = $payObject->get_code($order, $payment);
                    }
                }

                /* 模板赋值 */
                $this->smarty->assign('payment', $payment_info);
                $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                $this->smarty->assign('amount', price_format($amount, false));
                $this->smarty->assign('order', $order);

                return $this->smarty->display('user_transaction.dwt');
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除会员余额
        /* ------------------------------------------------------ */
        elseif ($action == 'cancel') {
            load_helper('clips');

            $id = (int)request()->input('id', 0);
            if ($id == 0 || $user_id == 0) {
                return dsc_header("Location: user.php?act=account_log\n");
            }

            $result = del_user_account($id, $user_id);

            if ($result) {

                //by wang删除账目详细扩展信息
                del_user_account_fields($id, $user_id);

                return dsc_header("Location: user.php?act=account_log\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 会员通过帐目明细列表进行再付款的操作
        /* ------------------------------------------------------ */
        elseif ($action == 'pay') {
            load_helper('clips');
            load_helper('payment');
            load_helper('order');

            //变量初始化
            $surplus_id = (int)request()->input('id', 0);
            $payment_id = (int)request()->input('pid', 0);

            if ($surplus_id == 0) {
                return dsc_header("Location: user.php?act=account_log\n");
            }

            //如果原来的支付方式已禁用或者已删除, 重新选择支付方式
            if ($payment_id == 0) {
                return dsc_header("Location: user.php?act=account_deposit&id=" . $surplus_id . "\n");
            }

            //获取单条会员帐目信息
            $order = [];
            $order = get_surplus_info($surplus_id, $user_id);

            //支付方式的信息
            $payment_info = [];
            $payment_info = payment_info($payment_id);

            /* 如果当前支付方式没有被禁用，进行支付的操作 */
            if (!empty($payment_info)) {
                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                //生成伪订单号
                $order['order_sn'] = $surplus_id;

                //获取需要支付的log_id
                $order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);

                $order['user_name'] = session('user_name');
                $order['surplus_amount'] = $order['amount'];

                //计算支付手续费用
                $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

                //计算此次预付款需要支付的总金额
                $order['order_amount'] = $order['surplus_amount'] + $payment_info['pay_fee'];

                //如果支付费用改变了，也要相应的更改pay_log表的order_amount
                $order_amount = PayLog::where('log_id', $order['log_id'])->value('order_amount');
                if ($order_amount != $order['order_amount']) {
                    PayLog::where('log_id', $order['log_id'])->update(['order_amount' => $order['order_amount']]);
                }

                if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                    /* 调用相应的支付方式文件 */
                    $payObject = $this->commonRepository->paymentInstance($payment_info['pay_code']);
                    /* 取得在线支付方式的支付按钮 */
                    if (!is_null($payObject)) {
                        $payment_info['pay_button'] = $payObject->get_code($order, $payment);
                    }
                }

                /* 模板赋值 */
                $this->smarty->assign('payment', $payment_info);
                $this->smarty->assign('order', $order);
                $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                $this->smarty->assign('amount', price_format($order['surplus_amount'], false));
                $this->smarty->assign('action', 'act_account');
                return $this->smarty->display('user_transaction.dwt');
            } /* 重新选择支付方式 */
            else {
                load_helper('clips');

                $this->smarty->assign('payment', get_online_payment_list());
                $this->smarty->assign('order', $order);
                $this->smarty->assign('action', 'account_deposit');
                return $this->smarty->display('user_transaction.dwt');
            }
        }

        /* ------------------------------------------------------ */
        //-- 添加标签(ajax)
        /* ------------------------------------------------------ */
        elseif ($action == 'add_tag') {
            load_helper('clips');

            $request = get_request_filter(request()->all(), 1);

            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $id = isset($request['id']) ? intval($request['id']) : 0;
            $tag = isset($request['tag']) ? json_str_iconv(trim($request['tag'])) : '';

            if ($user_id == 0) {
                /* 用户没有登录 */
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['tag_anonymous'];
            } else {
                add_tag($id, $tag); // 添加tag
                clear_cache_files('goods'); // 删除缓存

                /* 重新获得该商品的所有缓存 */
                $arr = get_tags($id);

                foreach ($arr as $row) {
                    $result['content'][] = ['word' => htmlspecialchars($row['tag_words']), 'count' => $row['tag_count']];
                }
            }


            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 编辑使用余额支付的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_edit_surplus') {
            $request = get_request_filter(request()->all(), 1);
            /* 检查是否登录 */
            if (session('user_id') <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 检查订单号 */
            $order_id = intval($request['order_id']);
            if ($order_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 检查余额 */
            $surplus = floatval($request['surplus']);
            if ($surplus <= 0) {
                return $this->err->add($GLOBALS['_LANG']['error_surplus_invalid']);
                return $this->err->show($GLOBALS['_LANG']['order_detail'], 'user_order.php?act=order_detail&order_id=' . $order_id);
            }

            load_helper('order');

            /* 取得订单 */
            $order = order_info($order_id);
            if (empty($order)) {
                return dsc_header("Location: ./\n");
            }

            /* 保存减余额前的金额 */
            $order['old_order_amount'] = $order['order_amount'];

            /*是否预售，检测结算时间是否超出尾款结束时间 liu */
            if ($request['pay_status'] == 'presale' && $order['pay_status'] == PS_PAYED_PART) {
                $result = $this->userService->PresaleSettleStatus($order['extension_id']);
                if ($result['settle_status'] == false) {
                    return dsc_header("Location: ./\n");
                }
            }

            /* 检查订单用户跟当前用户是否一致 */
            if (session('user_id') != $order['user_id']) {
                return dsc_header("Location: ./\n");
            }

            /* 检查订单是否未付款，检查应付款金额是否大于0 */
            if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0) {
                if ($order['pay_status'] != PS_PAYED_PART) {
                    return $this->err->add($GLOBALS['_LANG']['error_order_is_paid']);
                    return $this->err->show($GLOBALS['_LANG']['order_detail'], 'user_order.php?act=order_detail&order_id=' . $order_id);
                }
            }

            /* 计算应付款金额（减去支付费用） */
            if ($order['main_count'] == 0) {
                $order['order_amount'] -= $order['pay_fee'];
            }

            /* 余额是否超过了应付款金额，改为应付款金额 */
            if ($surplus > $order['order_amount']) {
                $surplus = $order['order_amount'];
            }

            /* 获取文本框输入的余额 */
            $order['request_surplus'] = $surplus;

            /* 取得用户信息 */
            $where = [
                'user_id' => $order['user_id']
            ];
            $user_info = $this->userService->userInfo($where);

            /* 用户帐户余额是否足够 */
            if ($surplus > $user_info['user_money'] + $user_info['credit_line']) {
                return $this->err->add($GLOBALS['_LANG']['error_surplus_not_enough']);
                return $this->err->show($GLOBALS['_LANG']['order_detail'], 'user_order.php?act=order_detail&order_id=' . $order_id);
            }

            /* 修改订单，重新计算支付费用 */
            $order['surplus'] += $surplus;
            $order['order_amount'] -= $surplus;

            /* 主订单不计算支付费用，容易出错 */
            if ($order['order_amount'] > 0 && $order['main_count'] == 0) {
                $cod_fee = 0;
                if ($order['shipping_id'] > 0) {
                    $regions = [$order['country'], $order['province'], $order['city'], $order['district']];
                    $shipping = shipping_info($order['shipping_id']);
                    if (isset($shipping['support_cod']) && $shipping['support_cod'] == '1') {
                        $cod_fee = isset($shipping['pay_fee']) ? $shipping['pay_fee'] : 0;
                    }
                }

                $pay_fee = 0;
                if ($order['pay_id'] > 0) {
                    $pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
                }

                $order['pay_fee'] = $pay_fee;
                $order['order_amount'] += $pay_fee;

                //余额支付后，待支付金额大于0，则改变订单号，防止支付备案冲突
                $new_order_sn = correct_order_sn($order['order_sn']);
                $order['order_sn'] = $new_order_sn;
            }

            $stores_sms = 0;
            /* 如果全部支付，设为已确认、已付款 | 预售商品设为已确认、部分付款 */
            if ($order['order_amount'] == 0) {
                $amount = $order['goods_amount'] + $order['shipping_fee'];
                $paid = $order['money_paid'] + $order['surplus'];
                if ($request['pay_status'] == 'presale' && $amount > $paid) {//判断是否是预售订金支付 liu
                    $order['pay_status'] = PS_PAYED_PART;
                    $order['order_amount'] = $amount - $paid;
                } else {
                    $order['pay_status'] = PS_PAYED;
                    $stores_sms = 1;
                }
                if ($order['order_status'] == OS_UNCONFIRMED) {
                    $order['order_status'] = OS_CONFIRMED;
                    $order['confirm_time'] = gmtime();
                }
                $order['pay_time'] = gmtime();

                if ($this->config['sales_volume_time'] == SALES_PAY) {
                    $order['is_update_sale'] = 1;
                }

                /* 更新商品销量 */
                $is_update_sale = is_update_sale($order_id);
                if ($this->config['sales_volume_time'] == SALES_PAY && $is_update_sale == 0) {
                    get_goods_sale($order_id);
                }

                /* 众筹状态的更改 by wu */
                update_zc_project($order_id);

                //付款成功创建快照
                create_snapshot($order_id);

                $store_id = StoreOrder::where('order_id', $order_id)->value('store_id');

                /* 如果使用库存，且付款时减库存，且订单金额为0，则减少库存 */
                if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PAID && $order['order_amount'] <= 0) {
                    change_order_goods_storage($order_id, true, SDT_PAID, $this->config['stock_dec_time'], 0, $store_id);
                }

                //付款成功后增加预售人数
                get_presale_num($order_id);
            }

            $payment = payment_info('balance', 1);
            $order['pay_id'] = $payment['pay_id'];
            $order['pay_name'] = $payment['pay_name'];

            $order = addslashes_deep($order);
            update_order($order_id, $order);

            //检查/改变主订单状态
            check_main_order_status($order_id);

            $store_result = [];
            //门店处理 付款成功发送短信
            if ($stores_sms == 1) {
                $where = [
                    'order_id' => $order_id
                ];
                $stores_order = $this->orderService->getStoreOrderInfo($where);

                if ($stores_order && $stores_order['store_id'] > 0) {
                    $where = [
                        'user_id' => $order['user_id']
                    ];
                    $orderUsers = $this->userService->userInfo($where);

                    if ($order['mobile']) {
                        $user_mobile_phone = $order['mobile'];
                    } else {
                        $user_mobile_phone = $orderUsers['mobile_phone'];
                    }

                    if (!empty($user_mobile_phone)) {
                        $pick_code = substr($order['order_sn'], -3) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                        StoreOrder::where('id', $stores_order['id'])->update(['pick_code' => $pick_code]);


                        /* 获取该商品有货门店 */
                        $stores_info = OfflineStore::where('id', $stores_order['store_id']);
                        $stores_info = $this->baseRepository->getToArrayFirst($stores_info);

                        $stores_info['province_id'] = $stores_info['province'] ?? 0;
                        $stores_info['city_id'] = $stores_info['city'] ?? 0;
                        $stores_info['district_id'] = $stores_info['district'] ?? 0;

                        $store_address = get_area_region_info($stores_info) . $stores_info['stores_address'];
                        $user_name = !empty($orderUsers['user_name']) ? $orderUsers['user_name'] : '';

                        //门店订单->短信接口参数
                        $store_smsParams = [
                            'user_name' => $user_name,
                            'order_sn' => $order['order_sn'],
                            'code' => $pick_code,
                            'store_address' => $store_address,
                            'mobile_phone' => $user_mobile_phone
                        ];

                        $this->commonRepository->smsSend($user_mobile_phone, $store_smsParams, 'store_order_code');
                    }
                }
            }

            /* 如果需要，发短信 */
            $where = [
                'order_id' => $order_id
            ];
            $order_goods = $this->orderService->getOrderGoodsInfo($where);

            $ru_id = $order_goods ? $order_goods['ru_id'] : 0;
            $stages_qishu = $order_goods ? $order_goods['stages_qishu'] : 0;

            $shop_name = $this->merchantCommonService->getShopName($ru_id, 1);

            if ($ru_id == 0) {
                $sms_shop_mobile = $this->config['sms_shop_mobile'];
            } else {
                $sms_shop_mobile = SellerShopinfo::where('ru_id', $ru_id)->value('mobile');
            }

            $order_result = [];
            if ($this->config['sms_order_payed'] == '1' && $sms_shop_mobile != '') {
                $order_region = $this->orderService->getOrderUserRegion($order_id);
                //阿里大鱼短信接口参数
                $smsParams = [
                    'shop_name' => $shop_name,
                    'shopname' => $shop_name,
                    'order_sn' => $order['order_sn'],
                    'ordersn' => $order['order_sn'],
                    'consignee' => $order['consignee'],
                    'order_region' => $order_region,
                    'orderregion' => $order_region,
                    'address' => $order['address'],
                    'order_mobile' => $order['mobile'],
                    'ordermobile' => $order['mobile'],
                    'mobile_phone' => $sms_shop_mobile,
                    'mobilephone' => $sms_shop_mobile
                ];

                $this->commonRepository->smsSend($sms_shop_mobile, $smsParams, 'sms_order_payed');
            }

            /* 将白条订单商品更新为普通订单商品 */
            if ($stages_qishu > 0) {
                OrderGoods::where('order_id', $order_id)->update(['stages_qishu' => '-1']);
            }

            /* 更新用户余额 */
            $change_desc = sprintf($GLOBALS['_LANG']['pay_order_by_surplus'], $order['order_sn']);
            log_account_change($user_info['user_id'], (-1) * $surplus, 0, 0, 0, $change_desc);

            /* 记录订单操作记录 */
            order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED, lang('common.main_order_pay'), lang('common.buyer'));

            /* 跳转 */
            if ($request['pay_status'] == 'auction') {
                return dsc_header('Location: user_order.php?act=auction_order_detail&order_id=' . $order_id . "\n");
            } else {
                return dsc_header('Location: user_order.php?act=order_detail&order_id=' . $order_id . "\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 编辑使用余额支付的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_edit_payment') {
            $request = get_request_filter(request()->all(), 1);

            /* 检查是否登录 */
            if (session('user_id') <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 检查支付方式 */
            $pay_id = intval($request['pay_id']);
            if ($pay_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            load_helper('order');
            $payment_info = payment_info($pay_id);
            if (empty($payment_info)) {
                return dsc_header("Location: ./\n");
            }

            /* 检查订单号 */
            $order_id = intval($request['order_id']);
            if ($order_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 取得订单 */
            $order = order_info($order_id);
            if (empty($order)) {
                return dsc_header("Location: ./\n");
            }

            /* 检查订单用户跟当前用户是否一致 */
            if (session('user_id') != $order['user_id']) {
                return dsc_header("Location: ./\n");
            }

            /* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变*/
            if (($order['pay_status'] != PS_UNPAYED && $order['pay_status'] != PS_PAYED_PART) || $order['shipping_status'] != SS_UNSHIPPED || $order['order_amount'] <= 0 || $order['pay_id'] == $pay_id) {
                return dsc_header("Location: user_order.php?act=order_detail&order_id=$order_id\n");
            }

            $order_amount = $order['order_amount'] - $order['pay_fee'];
            $pay_fee = pay_fee($pay_id, $order_amount);
            $order_amount += $pay_fee;

            $other = [
                'pay_id' => $pay_id,
                'pay_name' => $payment_info['pay_name'],
                'pay_fee' => $pay_fee,
                'order_amount' => $order_amount
            ];
            OrderInfo::where('order_id', $order_id)->update($other);

            /* 跳转 */
            return dsc_header("Location: user_order.php?act=order_detail&order_id=$order_id\n");
        }

        /* ------------------------------------------------------ */
        //-- 保存订单详情收货地址
        /* ------------------------------------------------------ */
        elseif ($action == 'save_order_address') {
            load_helper('transaction');

            $request = get_request_filter(request()->all(), 1);
            $time = gmtime();
            $address = [
                'consignee' => isset($request['consignee']) ? compile_str(trim($request['consignee'])) : '',
                'email' => isset($request['email']) ? compile_str(trim($request['email'])) : '',
                'address' => isset($request['address']) ? compile_str(trim($request['address'])) : '',
                'zipcode' => isset($request['zipcode']) ? compile_str(make_semiangle(trim($request['zipcode']))) : '',
                'tel' => isset($request['tel']) ? compile_str(trim($request['tel'])) : '',
                'mobile' => isset($request['mobile']) ? compile_str(trim($request['mobile'])) : '',
                'sign_building' => isset($request['sign_building']) ? compile_str(trim($request['sign_building'])) : '',
                'best_time' => isset($request['best_time']) ? compile_str(trim($request['best_time'])) : '',
                'update_time' => $time,
                'order_id' => isset($request['order_id']) ? intval($request['order_id']) : 0
            ];
            if (save_order_address($address, $user_id)) {
                return dsc_header('Location: user_order.php?act=order_detail&order_id=' . $address['order_id'] . "\n");
            } else {
                return $this->err->show($GLOBALS['_LANG']['order_list_lnk'], 'user_order.php?act=order_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 我的储值卡
        /* ------------------------------------------------------ */
        elseif ($action == 'value_card') {
            load_helper('transaction');

            $page = (int)request()->input('page', 1);
            $bind_vc = get_user_bind_vc_list($user_id, $page, 0, '', 1);

            $amount = ValueCard::where('user_id', $user_id)->count();

            $this->smarty->assign('amount', $amount);
            $this->smarty->assign('no_use', 1);
            $this->smarty->assign('bind_vc', $bind_vc);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 储值卡解绑短信验证
        /* ------------------------------------------------------ */
        elseif ($action == 'verify_mobilecode') {
            $vid = (int)request()->input('id', 0);

            // 异步 验证短信验证码
            if (!empty(request()->input('mobile_code'))) {
                load_helper('passport');
                $result = ['error' => 0, 'message' => ''];
                if (request()->input('mobile_code') != session('sms_mobile_code')) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['user_one_code'];
                }
                if (empty($result['message']) && empty(request()->input('error'))) {
                    $other = [
                        'user_id' => 0,
                        'bind_time' => 0
                    ];
                    ValueCard::where('vid', $vid)->update($other);
                }
                return response()->json($result);
            }
        }

        /* ------------------------------------------------------ */
        //-- 我的储值卡
        /* ------------------------------------------------------ */
        elseif ($action == 'value_card_info') {
            load_helper('transaction');

            $vid = (int)request()->input('vid', 0);
            $explain_info = $this->userService->GetExplain($vid);
            $explain = $explain_info['explain'];
            if (is_array($explain)) {
                $this->smarty->assign('explain', $explain['explain']);
                $this->smarty->assign('goods_ids', $explain['goods_ids']);
            } else {
                $this->smarty->assign('explain', $explain);
            }
            $value_card_info = value_card_use_info($vid);
            $this->smarty->assign('value_card_info', $value_card_info);
            $this->smarty->assign('rz_shopNames', $explain_info['rz_shopNames']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 我的团购列表
        /* ------------------------------------------------------ */
        elseif ($action == 'group_buy') {
            load_helper('transaction');

            //待议
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 团购订单详情
        /* ------------------------------------------------------ */
        elseif ($action == 'group_buy_detail') {
            load_helper('transaction');

            //待议
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 用户推荐页面
        /* ------------------------------------------------------ */
        elseif ($action == 'affiliate') {
            $goodsid = (int)request()->input('goodsid', 0);
            $shopurl = url('/') . '/';
            if (empty($goodsid)) {
                //我的推荐页面

                $page = (int)request()->input('page', 1);
                $size = !empty($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10;

                empty($affiliate) && $affiliate = [];

                if (empty($affiliate['config']['separate_by'])) {
                    //推荐注册分成
                    $affdb = [];
                    $num = count($affiliate['item']);
                    $up_uid = $user_id;
                    $all_uid = $user_id;
                    for ($i = 1; $i <= $num; $i++) {
                        $count = 0;
                        if ($up_uid) {
                            $up_uid_str = !is_array($up_uid) ? explode(",", $up_uid) : $up_uid;
                            $res = Users::whereIn('parent_id', $up_uid_str);
                            $res = $res->get();

                            $res = $res ? $res->toArray() : [];

                            $up_uid = '';

                            foreach ($res as $rt) {
                                $up_uid .= $up_uid ? ",$rt[user_id]" : "$rt[user_id]";
                                if ($i < $num) {
                                    $all_uid .= ",$rt[user_id]";
                                }
                                $count++;
                            }
                        }
                        $affdb[$i]['num'] = $count;
                        $affdb[$i]['point'] = $affiliate['item'][$i - 1]['level_point'];
                        $affdb[$i]['money'] = $affiliate['item'][$i - 1]['level_money'];
                    }
                    $this->smarty->assign('affdb', $affdb);

                    $where = [
                        'all_uid' => $all_uid,
                        'user_id' => $user_id
                    ];
                    $sqlcount = $this->userService->getUserOrderAffiliateCount($where);

                    $max_page = ($sqlcount > 0) ? ceil($sqlcount / $size) : 1;
                    if ($page > $max_page) {
                        $page = $max_page;
                    }

                    $res = $this->userService->getUserOrderAffiliateList($where, $page, $size);

                    /*
                      SQL解释：

                      订单、用户、分成记录关联
                      一个订单可能有多个分成记录

                      1、订单有效 o.user_id > 0
                      2、满足以下之一：
                      a.直接下线的未分成订单 u.parent_id IN ($all_uid) AND o.is_separate = 0
                      其中$all_uid为该ID及其下线(不包含最后一层下线)
                      b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

                     */

                    $affiliate_intro = nl2br(sprintf($GLOBALS['_LANG']['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $GLOBALS['_LANG']['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_register_all'], $affiliate['config']['level_register_up'], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
                } else {
                    //推荐订单分成

                    $where = [
                        'all_uid' => $user_id,
                        'user_id' => $user_id
                    ];
                    $sqlcount = $this->userService->getUserOrderAffiliateCount($where);

                    $max_page = ($sqlcount > 0) ? ceil($sqlcount / $size) : 1;
                    if ($page > $max_page) {
                        $page = $max_page;
                    }

                    $res = $this->userService->getUserOrderAffiliateList($where, $page, $size);

                    /*
                      SQL解释：

                      订单、用户、分成记录关联
                      一个订单可能有多个分成记录

                      1、订单有效 o.user_id > 0
                      2、满足以下之一：
                      a.订单下线的未分成订单 o.parent_id = '$user_id' AND o.is_separate = 0
                      b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

                     */

                    $affiliate_intro = nl2br(sprintf($GLOBALS['_LANG']['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $GLOBALS['_LANG']['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
                }

                $logdb = [];
                if ($res) {
                    foreach ($res as $rt) {
                        $rt['up'] = Users::where('user_id', $rt['user_id'])->value('parent_id');

                        $log = AffiliateLog::select('log_id', 'user_id as suid', 'user_name as auser', 'money', 'point', 'separate_type')
                            ->where('order_id', $rt['order_id'])->first();
                        $log = $log ? $log->toArray() : [];

                        $rt = $log ? array_merge($rt, $log) : $rt;

                        if (!empty($rt['suid'])) {
                            //在affiliate_log有记录
                            if ($rt['separate_type'] == -1 || $rt['separate_type'] == -2) {
                                //已被撤销
                                $rt['is_separate'] = 3;
                            }
                        }
                        $rt['order_sn'] = substr($rt['order_sn'], 0, strlen($rt['order_sn']) - 5) . "***" . substr($rt['order_sn'], -2, 2);
                        $logdb[] = $rt;
                    }
                }

                $url_format = "user.php?act=affiliate&page=";

                $pager = [
                    'page' => $page,
                    'size' => $size,
                    'sort' => '',
                    'order' => '',
                    'record_count' => $sqlcount,
                    'page_count' => $max_page,
                    'page_first' => $url_format . '1',
                    'page_prev' => $page > 1 ? $url_format . ($page - 1) : "javascript:;",
                    'page_next' => $page < $max_page ? $url_format . ($page + 1) : "javascript:;",
                    'page_last' => $url_format . $max_page,
                    'array' => []
                ];
                for ($i = 1; $i <= $max_page; $i++) {
                    $pager['array'][$i] = $i;
                }

                $this->smarty->assign('url_format', $url_format);
                $this->smarty->assign('pager', $pager);


                $this->smarty->assign('affiliate_intro', $affiliate_intro);
                $this->smarty->assign('affiliate_type', $affiliate['config']['separate_by']);

                $this->smarty->assign('logdb', $logdb);
            } else {
                //单个商品推荐
                $this->smarty->assign('userid', $user_id);
                $this->smarty->assign('goodsid', $goodsid);

                $types = [1, 2, 3, 4, 5];
                $this->smarty->assign('types', $types);

                $where = [
                    'goods_id' => $goodsid,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];
                $goods = $this->goodsService->getGoodsInfo($where);

                $goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? $shopurl . $goods['goods_img'] : $goods['goods_img'];
                $goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? $shopurl . $goods['goods_thumb'] : $goods['goods_thumb'];
                $goods['shop_price'] = price_format($goods['shop_price']);

                $this->smarty->assign('goods', $goods);
            }

            $this->smarty->assign('shopname', $this->config['shop_name']);
            $this->smarty->assign('userid', $user_id);
            $this->smarty->assign('shopurl', $shopurl);
            $this->smarty->assign('logosrc', 'themes/' . $this->config['template'] . '/images/logo.gif');

            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 首页邮件订阅ajax操做和验证操作
        /* ------------------------------------------------------ */
        elseif ($action == 'email_list') {
            $job = addslashes(trim(request()->input('job', '')));

            if ($job == 'add' || $job == 'del') {
                if (session()->has('last_email_query')) {
                    if (time() - session('last_email_query', 0) <= 30) {
                        return $GLOBALS['_LANG']['order_query_toofast'];
                    }
                }

                session([
                    'last_email_query' => gmtime()
                ]);
            }

            $email = htmlspecialchars(trim(request()->input('email', '')));

            if (!is_email($email)) {
                $info = sprintf($GLOBALS['_LANG']['email_invalid'], $email);
                return $info;
            }

            $ck = EmailList::where('email', $email)->first();
            $ck = $ck ? $ck->toArray() : [];

            if ($job == 'add') {
                if (empty($ck)) {
                    $hash = substr(md5(time()), 1, 10);
                    $other = [
                        'email' => $email,
                        'stat' => 0,
                        'hash' => $hash
                    ];
                    EmailList::insert($other);

                    $info = $GLOBALS['_LANG']['email_check'];
                    $url = url('/') . '/' . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
                    $this->commonRepository->sendEmail('', $email, $GLOBALS['_LANG']['check_mail'], sprintf($GLOBALS['_LANG']['check_mail_content'], $email, $this->config['shop_name'], $url, $url, $this->config['shop_name'], local_date('Y-m-d')), 1);
                } elseif ($ck['stat'] == 1) {
                    $info = sprintf($GLOBALS['_LANG']['email_alreadyin_list'], $email);
                } else {
                    $hash = substr(md5(time()), 1, 10);
                    EmailList::where('email', $email)->update(['hash' => $hash]);

                    $info = $GLOBALS['_LANG']['email_re_check'];
                    $url = url('/') . '/' . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
                    $this->commonRepository->sendEmail('', $email, $GLOBALS['_LANG']['check_mail'], sprintf($GLOBALS['_LANG']['check_mail_content'], $email, $this->config['shop_name'], $url, $url, $this->config['shop_name'], local_date('Y-m-d')), 1);
                }
                return $info;
            } elseif ($job == 'del') {
                if (empty($ck)) {
                    $info = sprintf($GLOBALS['_LANG']['email_notin_list'], $email);
                } elseif ($ck['stat'] == 1) {
                    $hash = substr(md5(time()), 1, 10);
                    EmailList::where('email', $email)->update(['hash' => $hash]);

                    $info = $GLOBALS['_LANG']['email_check'];
                    $url = url('/') . '/' . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
                    $this->commonRepository->sendEmail('', $email, $GLOBALS['_LANG']['check_mail'], sprintf($GLOBALS['_LANG']['check_mail_content'], $email, $this->config['shop_name'], $url, $url, $this->config['shop_name'], local_date('Y-m-d')), 1);
                } else {
                    $info = $GLOBALS['_LANG']['email_not_alive'];
                }
                return $info;
            } elseif ($job == 'add_check') {
                if (empty($ck)) {
                    $info = sprintf($GLOBALS['_LANG']['email_notin_list'], $email);
                } elseif ($ck['stat'] == 1) {
                    $info = $GLOBALS['_LANG']['email_checked'];
                } else {
                    if (request()->input('hash') == $ck['hash']) {
                        EmailList::where('email', $email)->update(['stat' => 1]);

                        $info = $GLOBALS['_LANG']['email_checked'];
                    } else {
                        $info = $GLOBALS['_LANG']['hash_wrong'];
                    }
                }
                return show_message($info, $GLOBALS['_LANG']['back_home_lnk'], 'index.php');
            } elseif ($job == 'del_check') {
                if (empty($ck)) {
                    $info = sprintf($GLOBALS['_LANG']['email_invalid'], $email);
                } elseif ($ck['stat'] == 1) {
                    if (request()->input('hash') == $ck['hash']) {
                        EmailList::where('email', $email)->delete();

                        $info = $GLOBALS['_LANG']['email_canceled'];
                    } else {
                        $info = $GLOBALS['_LANG']['hash_wrong'];
                    }
                } else {
                    $info = $GLOBALS['_LANG']['email_not_alive'];
                }
                return show_message($info, $GLOBALS['_LANG']['back_home_lnk'], 'index.php');
            }
        }

        /* ------------------------------------------------------ */
        //-- ajax 发送验证邮件
        /* ------------------------------------------------------ */
        elseif ($action == 'send_hash_mail') {
            load_helper('passport');

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($user_id == 0) {
                /* 用户没有登录 */
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_please'];
                return response()->json($result);
            }

            if (send_regiter_hash($user_id)) {
                $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                return response()->json($result);
            } else {
                $result['error'] = 1;
                $result['message'] = $this->err->last_message();
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 跟踪包裹
        /* ------------------------------------------------------ */
        elseif ($action == 'track_packages') {
            load_helper('transaction');
            load_helper('order');

            $page = (int)request()->input('page', 1);
            $size = 20;
            $orders = [];

            $record_count = $this->userService->getTrackPackagesCount($user_id);

            $res = $this->userService->getTrackPackagesList($user_id, $page, $size);

            if ($res) {
                foreach ($res as $item) {
                    $shipping = get_shipping_object($item['shipping_id']);

                    $item['formated_shipping_time'] = local_date($this->config['time_format'], $item['shipping_time']);
                    $item['shipping_status'] = $GLOBALS['_LANG']['ss'][$item['shipping_status']];

                    $query_link = $item['invoice_no'];

                    $is_callable = [$shipping, 'query'];

                    /* 判断类对象方法是否存在 */
                    if (is_object($shipping) && is_callable($is_callable)) {
                        $query_link = $shipping->query($item['invoice_no']);
                    }

                    if ($query_link != $item['invoice_no']) {
                        $item['query_link'] = $query_link;
                        $item['goods'] = get_order_goods_toInfo($item['order_id']);

                        $orders[] = $item;
                    }
                }
            }

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page, $size);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('orders', $orders);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 订单查询
        /* ------------------------------------------------------ */
        elseif ($action == 'order_query') {
            $order_sn = request()->input('order_sn', '');
            $order_sn = trim(substr($order_sn, 1));
            $order_sn = empty($order_sn) ? '' : addslashes($order_sn);

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if (session()->has('last_order_query')) {
                if (gmtime() - session('last_order_query', 0) <= 10) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['order_query_toofast'];
                    return response()->json($result);
                }
            }

            session([
                'last_order_query' => gmtime()
            ]);

            if (empty($order_sn)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_order_sn'];
                return response()->json($result);
            }

            $where = [
                'order_sn' => $order_sn
            ];
            $row = $this->orderService->getOrderInfo($where);

            if (empty($row)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_order_sn'];
                return response()->json($result);
            }

            $order_query = [];
            $order_query['order_sn'] = $order_sn;
            $order_query['order_id'] = $row['order_id'];
            $order_query['order_status'] = lang('user.os.' . $row['order_status']) . ',' . lang('user.ps.' . $row['pay_status']) . ',' . lang('user.ss.' . $row['shipping_status']);

            if ($row['invoice_no'] && $row['shipping_id'] > 0) {
                $order_query['invoice_no'] = (string)$row['invoice_no'];

                $shipping_code = Shipping::where('shipping_id', $row['shipping_id'])->value('shipping_code');
                if ($shipping_code) {
                    // 过滤ecjia配送方式
                    if (substr($shipping_code, 0, 5) == 'ship_') {
                        $shipping_code = str_replace('ship_', '', $shipping_code);
                    }

                    $shippingObject = $this->commonRepository->shippingInstance($shipping_code);
                    if (!is_null($shippingObject)) {
                        $order_query['invoice_no'] = $shippingObject->query($order_query['invoice_no']);
                    }
                }
            }

            $order_query['user_id'] = $row['user_id'];
            /* 如果是匿名用户显示发货时间 */
            if ($row['user_id'] == 0 && $row['shipping_time'] > 0) {
                $order_query['shipping_date'] = local_date($this->config['date_format'], $row['shipping_time']);
            }
            $this->smarty->assign('order_query', $order_query);
            $result['content'] = $this->smarty->fetch('library/order_query.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 积分转换
        /* ------------------------------------------------------ */
        elseif ($action == 'transform_points') {
            $rule = [];
            if (!empty($this->config['points_rule'])) {
                $rule = unserialize($this->config['points_rule']);
            }
            $cfg = [];
            if (!empty($this->config['integrate_config'])) {
                $cfg = unserialize($this->config['integrate_config']);
                $GLOBALS['_LANG']['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0]) ? $GLOBALS['_LANG']['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
                $GLOBALS['_LANG']['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0]) ? $GLOBALS['_LANG']['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
            }

            $where = [
                'user_id' => $user_id
            ];
            $row = $this->userService->userInfo($where);

            if ($this->config['integrate_code'] == 'ucenter') {
                $exchange_type = 'ucenter';
                $to_credits_options = [];
                $out_exchange_allow = [];
                foreach ($rule as $credit) {
                    $out_exchange_allow[$credit['appiddesc'] . '|' . $credit['creditdesc'] . '|' . $credit['creditsrc']] = $credit['ratio'];
                    if (!array_key_exists($credit['appiddesc'] . '|' . $credit['creditdesc'], $to_credits_options)) {
                        $to_credits_options[$credit['appiddesc'] . '|' . $credit['creditdesc']] = $credit['title'];
                    }
                }
                $this->smarty->assign('selected_org', $rule[0]['creditsrc']);
                $this->smarty->assign('selected_dst', $rule[0]['appiddesc'] . '|' . $rule[0]['creditdesc']);
                $this->smarty->assign('descreditunit', $rule[0]['unit']);
                $this->smarty->assign('orgcredittitle', $GLOBALS['_LANG']['exchange_points'][$rule[0]['creditsrc']]);
                $this->smarty->assign('descredittitle', $rule[0]['title']);
                $this->smarty->assign('descreditamount', round((1 / $rule[0]['ratio']), 2));
                $this->smarty->assign('to_credits_options', $to_credits_options);
                $this->smarty->assign('out_exchange_allow', $out_exchange_allow);
            } else {
                $exchange_type = 'other';

                $bbs_points_name = $GLOBALS['user']->get_points_name();
                $total_bbs_points = $GLOBALS['user']->get_points($row['user_name']);

                /* 论坛积分 */
                $bbs_points = [];
                foreach ($bbs_points_name as $key => $val) {
                    $bbs_points[$key] = ['title' => $GLOBALS['_LANG']['bbs'] . $val['title'], 'value' => $total_bbs_points[$key]];
                }

                /* 兑换规则 */
                $rule_list = [];
                foreach ($rule as $key => $val) {
                    $rule_key = substr($key, 0, 1);
                    $bbs_key = substr($key, 1);
                    $rule_list[$key]['rate'] = $val;
                    switch ($rule_key) {
                        case TO_P:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['pay_points'];
                            break;
                        case TO_R:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['rank_points'];
                            break;
                        case FROM_P:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['pay_points'];
                            $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            break;
                        case FROM_R:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['rank_points'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            break;
                    }
                }
                $this->smarty->assign('bbs_points', $bbs_points);
                $this->smarty->assign('rule_list', $rule_list);
            }
            $this->smarty->assign('shop_points', $row);
            $this->smarty->assign('exchange_type', $exchange_type);
            $this->smarty->assign('action', $action);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 积分转换
        /* ------------------------------------------------------ */
        elseif ($action == 'act_transform_points') {
            $rule_index = addslashes(trim(request()->input('rule_index', '')));
            $num = (int)request()->input('num', 0);

            if ($num <= 0 || $num != floor($num)) {
                return show_message($GLOBALS['_LANG']['invalid_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }

            $num = floor($num); //格式化为整数

            $bbs_key = substr($rule_index, 1);
            $rule_key = substr($rule_index, 0, 1);

            $max_num = 0;

            /* 取出用户数据 */
            $where = [
                'user_id' => $user_id
            ];
            $row = $this->userService->userInfo($where);

            $bbs_points = $GLOBALS['user']->get_points($row['user_name']);
            $points_name = $GLOBALS['user']->get_points_name();

            $rule = [];
            if ($this->config['points_rule']) {
                $rule = unserialize($this->config['points_rule']);
            }
            list($from, $to) = explode(':', $rule[$rule_index]);

            $max_points = 0;
            switch ($rule_key) {
                case TO_P:
                    $max_points = $bbs_points[$bbs_key];
                    break;
                case TO_R:
                    $max_points = $bbs_points[$bbs_key];
                    break;
                case FROM_P:
                    $max_points = $row['pay_points'];
                    break;
                case FROM_R:
                    $max_points = $row['rank_points'];
            }

            /* 检查积分是否超过最大值 */
            if ($max_points <= 0 || $num > $max_points) {
                return show_message($GLOBALS['_LANG']['overflow_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }

            switch ($rule_key) {
                case TO_P:
                    $result_points = floor($num * $to / $from);
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => 0 - $num]); //调整论坛积分
                    log_account_change($row['user_id'], 0, 0, 0, $result_points, $GLOBALS['_LANG']['transform_points'], ACT_OTHER);
                    return show_message(sprintf($GLOBALS['_LANG']['to_pay_points'], $num, $points_name[$bbs_key]['title'], $result_points), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');

                case TO_R:
                    $result_points = floor($num * $to / $from);
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => 0 - $num]); //调整论坛积分
                    log_account_change($row['user_id'], 0, 0, $result_points, 0, $GLOBALS['_LANG']['transform_points'], ACT_OTHER);
                    return show_message(sprintf($GLOBALS['_LANG']['to_rank_points'], $num, $points_name[$bbs_key]['title'], $result_points), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');

                case FROM_P:
                    $result_points = floor($num * $to / $from);
                    log_account_change($row['user_id'], 0, 0, 0, 0 - $num, $GLOBALS['_LANG']['transform_points'], ACT_OTHER); //调整商城积分
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => $result_points]); //调整论坛积分
                    return show_message(sprintf($GLOBALS['_LANG']['from_pay_points'], $num, $result_points, $points_name[$bbs_key]['title']), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');

                case FROM_R:
                    $result_points = floor($num * $to / $from);
                    log_account_change($row['user_id'], 0, 0, 0 - $num, 0, $GLOBALS['_LANG']['transform_points'], ACT_OTHER); //调整商城积分
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => $result_points]); //调整论坛积分
                    return show_message(sprintf($GLOBALS['_LANG']['from_rank_points'], $num, $result_points, $points_name[$bbs_key]['title']), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
        } elseif ($action == 'act_transform_ucenter_points') {
            $rule = [];
            if ($this->config['points_rule']) {
                $rule = unserialize($this->config['points_rule']);
            }
            $shop_points = [0 => 'rank_points', 1 => 'pay_points'];

            $where = [
                'user_id' => $user_id
            ];
            $row = $this->userService->userInfo($where);

            $request = get_request_filter(request()->all(), 1);

            $exchange_amount = intval($request['amount']);
            $fromcredits = intval($request['fromcredits']);
            $tocredits = trim($request['tocredits']);
            $cfg = unserialize($this->config['integrate_config']);
            if (!empty($cfg)) {
                $GLOBALS['_LANG']['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0]) ? $GLOBALS['_LANG']['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
                $GLOBALS['_LANG']['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0]) ? $GLOBALS['_LANG']['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
            }
            list($appiddesc, $creditdesc) = explode('|', $tocredits);
            $ratio = 0;

            if ($exchange_amount <= 0) {
                return show_message($GLOBALS['_LANG']['invalid_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
            if ($exchange_amount > $row[$shop_points[$fromcredits]]) {
                return show_message($GLOBALS['_LANG']['overflow_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
            foreach ($rule as $credit) {
                if ($credit['appiddesc'] == $appiddesc && $credit['creditdesc'] == $creditdesc && $credit['creditsrc'] == $fromcredits) {
                    $ratio = $credit['ratio'];
                    break;
                }
            }
            if ($ratio == 0) {
                return show_message($GLOBALS['_LANG']['exchange_deny'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
            $netamount = floor($exchange_amount / $ratio);
            load_helper('uc');
            $result = exchange_points($row['user_id'], $fromcredits, $creditdesc, $appiddesc, $netamount);
            if ($result === true) {
                $exchange = $shop_points[$fromcredits] - $exchange_amount;

                $other = [
                    $shop_points[$fromcredits] => $exchange
                ];
                Users::where('user_id', $row['user_id'])->update($other);

                $other = [
                    'user_id' => $row['user_id'],
                    $shop_points[$fromcredits] => "-$exchange_amount",
                    'change_time' => gmtime(),
                    'change_desc' => $cfg['uc_lang']['exchange'],
                    'change_type' => '98'
                ];
                AccountLog::insert($other);

                return show_message(sprintf($GLOBALS['_LANG']['exchange_success'], $exchange_amount, $GLOBALS['_LANG']['exchange_points'][$fromcredits], $netamount, $credit['title']), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            } else {
                return show_message($GLOBALS['_LANG']['exchange_error_1'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
        }

        /* ------------------------------------------------------ */
        //-- 清除商品浏览历史
        /* ------------------------------------------------------ */
        elseif ($action == 'clear_history') {

            $arr = [
                'ECS[history]',
                'ECS[list_history]'
            ];

            app(SessionRepository::class)->deleteCookie($arr);
        }

        /* ------------------------------------------------------ */
        //-- 商家等级入驻列表
        /* ------------------------------------------------------ */
        elseif ($action == 'merchants_upgrade') {
            $this->smarty->assign("action", $action);
            load_helper('transaction');
            $position = assign_ur_here(0, $GLOBALS['_LANG']['seller_garde']);
            get_invalid_apply(); //过期申请失效处理

            //判断是否到期
            $this->smarty->assign('is_expiry', judge_seller_grade_expiry($user_id));

            /* 获取商家当前等级 */
            $seller_grader = get_seller_grade($user_id);
            $this->smarty->assign("grade_id", $seller_grader['grade_id'] ?? 0);

            $this->smarty->assign('page_title', $position['title']); // 页面标题
            $page = (int)request()->input('page', 1);
            $this->smarty->assign("page", $page);

            $record_count = SellerGrade::where('is_open', 1)->count();

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $seller_grade = $this->userMerchantService->getSellerGradeInfo($pager['size'], $pager['start']);
            $this->smarty->assign("seller_grade", $seller_grade);
            $this->smarty->assign('pager', $pager);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 商家等级入驻列表
        /* ------------------------------------------------------ */
        elseif ($action == 'merchants_upgrade_log') {
            $this->smarty->assign("action", $action);
            load_helper('transaction');
            $position = assign_ur_here(0, $GLOBALS['_LANG']['merchants_upgrade_log']);
            get_invalid_apply();//过期申请失效处理

            /*获取商家当前等级*/
            $seller_grader = get_seller_grade($user_id);
            $this->smarty->assign("grade_id", $seller_grader['grade_id'] ?? 0);

            $this->smarty->assign('page_title', $position['title']); // 页面标题
            $page = (int)request()->input('page', 1);
            $this->smarty->assign("page", $page);

            $record_count = SellerApplyInfo::where('ru_id', $user_id)->count();

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $merchants_upgrade_log = $this->userMerchantService->getMerchantsUpGradeLog($user_id, $pager['size'], $pager['start']);
            $this->smarty->assign("merchants_upgrade_log", $merchants_upgrade_log);
            $this->smarty->assign('pager', $pager);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 编辑商家等级入驻
        /* ------------------------------------------------------ */
        elseif ($action == 'application_grade' || $action == 'application_grade_edit') {
            $this->smarty->assign("action", $action);
            load_helper('transaction');
            load_helper('payment');
            load_helper('order');
            $position = assign_ur_here(0, $GLOBALS['_LANG']['seller_garde']);
            $this->smarty->assign('page_title', $position['title']); // 页面标题

            $grade_id = (int)request()->input('grade_id', 0);
            $this->smarty->assign('grade_id', $grade_id);

            $seller_grade = get_seller_grade(session('user_id'));    //获取商家等级

            if ($seller_grade) {
                $seller_grade['end_time'] = local_date('Y', $seller_grade['add_time']) + $seller_grade['year_num'] . '-' . local_date('m-d H:i:s', $seller_grade['add_time']);
                $seller_grade['addtime'] = local_date('Y-m-d H:i:s', $seller_grade['add_time']);

                /*如果是付费等级，根据剩余时间计算剩余价钱*/
                if ($seller_grade['amount'] > 0) {
                    $rest = (gmtime() - $seller_grade['add_time']) / (strtotime($seller_grade['end_time']) - $seller_grade['add_time']);//换算剩余时间比例
                    $seller_grade['refund_price'] = round($seller_grade['amount'] - $seller_grade['amount'] * $rest, 2);//按比例计算剩余金额

                    $seller_grade['format_refund_price'] = price_format($seller_grade['refund_price']);
                }
                $this->smarty->assign('seller_grade', $seller_grade);
            }

            if ($action == 'application_grade_edit') {
                $apply_id = (int)request()->input('apply_id', 0);

                /*获取申请信息*/
                $seller_apply_info = SellerApplyInfo::where('apply_id', $apply_id)->first();
                $seller_apply_info = $seller_apply_info ? $seller_apply_info->toArray() : [];

                $apply_criteria = unserialize($seller_apply_info['entry_criteria']);
                if ($seller_apply_info['pay_id'] > 0 && $seller_apply_info['is_paid'] == 0 && $seller_apply_info['pay_status'] == 0) {
                    load_helper('clips');
                    /*在线支付按钮*/

                    //支付方式信息
                    $payment_info = [];
                    $payment_info = payment_info($seller_apply_info['pay_id']);

                    //无效支付方式
                    if ($payment_info === false) {
                        $seller_apply_info['pay_online'] = '';
                    } else {
                        //pc端如果使用的是app的支付方式，也不生成支付按钮
                        if (substr($payment_info['pay_code'], 0, 4) == 'pay_') {
                            $seller_apply_info['pay_online'] = '';
                        } else {
                            //取得支付信息，生成支付代码
                            $payment = unserialize_config($payment_info['pay_config']);

                            //获取需要支付的log_id
                            $apply['log_id'] = get_paylog_id($seller_apply_info['allpy_id'], $pay_type = PAY_APPLYGRADE);
                            $amount = $seller_apply_info['total_amount'];
                            $apply['order_sn'] = $seller_apply_info['apply_sn'];
                            $apply['user_id'] = $seller_apply_info['ru_id'];
                            $apply['surplus_amount'] = $amount;
                            //计算支付手续费用
                            $payment_info['pay_fee'] = pay_fee($pay_id, $apply['surplus_amount'], 0);
                            //计算此次预付款需要支付的总金额
                            $apply['order_amount'] = $amount + $payment_info['pay_fee'];

                            if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                                /* 调用相应的支付方式文件 */
                                $payObject = $this->commonRepository->paymentInstance($payment_info['pay_code']);
                                /* 取得在线支付方式的支付按钮 */
                                if (!is_null($payObject)) {
                                    $seller_apply_info['pay_online'] = $payObject->get_code($apply, $payment);
                                }
                            }
                        }
                    }
                }

                if (isset($seller_apply_info['refund_price'])) {
                    $seller_apply_info['format_refund_price'] = price_format($seller_apply_info['refund_price']);
                }

                $act = "update_submit";
                $this->smarty->assign('apply_criteria', $apply_criteria);
                $this->smarty->assign('seller_apply_info', $seller_apply_info);
            } else {
                $act = "confirm_inventory";
                /*判断是否存在未支付未失效申请*/
                $seller_apply_info = SellerApplyInfo::where('ru_id', $user_id)->where('apply_status', 0)->where('is_paid', 0)->first();
                $seller_apply_info = $seller_apply_info ? $seller_apply_info->toArray() : [];

                if ($seller_apply_info) {
                    return show_message($GLOBALS['_LANG']['invalid_apply']);
                }
            }

            $grade_info = SellerGrade::where('id', $grade_id)->first();
            $grade_info = $grade_info ? $grade_info->toArray() : [];

            $entry_criteriat_info = $grade_info ? $this->userMerchantService->getEntryCriteria($grade_info['entry_criteria']) : [];//获取等级入驻标准
            $this->smarty->assign('entry_criteriat_info', $entry_criteriat_info);
            $pay = available_payment_list(0);//获取支付方式
            $this->smarty->assign("pay", $pay);
            $this->smarty->assign("act", $act);

            //防止重复提交
            session()->forget('grade_reload.' . session('user_id'));

            set_prevent_token("grade_cookie");

            $this->smarty->assign("grade_name", $grade_info['grade_name']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 更新商家等级入驻
        /* ------------------------------------------------------ */
        elseif ($action == 'confirm_inventory' || $action == 'update_submit') {
            $this->smarty->assign("action", $action);
            load_helper('transaction');
            load_helper('payment');
            load_helper('order');
            load_helper('clips');

            $grade_id = (int)request()->input('grade_id', 0);
            $pay_id = (int)request()->input('pay_id', 0);
            $entry_criteria = request()->input('value', []);
            $file_id = request()->input('file_id', []);
            $fee_num = (int)request()->input('fee_num', 1);
            $all_count_charge = round(request()->input('all_count_charge', 0.00), 2);
            $refund_price = request()->input('refund_price', 0.00);
            $file_url = request()->input('file_url', []);

            $apply_info = [];
            $back_price = 0.00;
            $payable_amount = 0.00;
            //计算此次预付款需要支付的总金额
            if ($refund_price > 0) {
                if ($this->config['apply_options'] == 1) {
                    if ($refund_price > $all_count_charge) {
                        $payable_amount = 0.00;
                        $back_price = $refund_price - $all_count_charge;
                    } else {
                        $payable_amount = $all_count_charge - $refund_price;
                    }
                } elseif ($this->config['apply_options'] == 2) {
                    if ($refund_price > $all_count_charge) {
                        $payable_amount = 0.00;
                        $back_price = 0.00;
                    } else {
                        $payable_amount = $all_count_charge - $refund_price;
                    }
                }
            } else {
                $payable_amount = $all_count_charge;
            }


            /* 获取支付信息 */
            $payment_info = [];
            $payment_info = payment_info($pay_id);
            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($pay_id, $payable_amount, 0);
            $apply_info['order_amount'] = $payable_amount + $payment_info['pay_fee'];

            /* 图片上传处理 */
            $php_maxsize = ini_get('upload_max_filesize');
            $htm_maxsize = '2M';
            /* 验证图片 */
            $img_url = [];
            if ($_FILES['value']) {
                foreach ($_FILES['value']['error'] as $key => $value) {
                    if ($value == 0) {
                        if (!$image->check_img_type($_FILES['value']['type'][$key])) {
                            $massege = sprintf($GLOBALS['_LANG']['invalid_img_val'], $key + 1);
                        } else {
                            $goods_pre = 1;
                        }
                    } elseif ($value == 1) {
                        $massege = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $php_maxsize);
                    } elseif ($_FILES['img_url']['error'] == 2) {
                        $massege = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $htm_maxsize);
                    }
                    if ($massege) {
                        return show_message($massege);
                    }
                }
                if ($goods_pre == 1) {
                    $res = $this->userService->UploadApplyFile($_FILES['value'], $file_id, $file_url);
                    if ($res != false) {
                        $img_url = $res;
                    } else {
                        $img_url = $file_url;
                    }
                } else {
                    $img_url = $file_url;
                }
            } else {
                $img_url = $file_url;
            }

            if ($img_url) {
                $valus = serialize($entry_criteria + $img_url);
            } else {
                $valus = serialize($entry_criteria);
            }

            if ($action == 'confirm_inventory') {
                $apply_sn = get_order_sn(); //获取新订单号
                $time = gmtime();

                $other = [
                    'ru_id' => $user_id,
                    'grade_id' => $grade_id,
                    'apply_sn' => $apply_sn,
                    'total_amount' => $all_count_charge,
                    'pay_fee' => $payment_info['pay_fee'],
                    'fee_num' => $fee_num,
                    'entry_criteria' => $valus,
                    'add_time' => $time,
                    'pay_id' => $pay_id,
                    'refund_price' => $refund_price,
                    'back_price' => $back_price,
                    'payable_amount' => $payable_amount
                ];
                $apply_id = SellerApplyInfo::insertGetId($other);

                $apply_info['log_id'] = insert_pay_log($apply_id, $apply_info['order_amount'], $type = PAY_APPLYGRADE, 0);
            } else {
                $apply_sn = request()->input('apply_sn', 0);
                $apply_id = (int)request()->input('apply_id', 0);

                //判断订单是否已支付
                if ($action == 'update_submit') {
                    $pay_status = SellerApplyInfo::where('apply_id', $apply_id)->value('pay_status');
                    if ($pay_status == 1) {
                        return show_message($GLOBALS['_LANG']['pay_success_apply']);
                    }
                }

                $other = [
                    'payable_amount' => $payable_amount,
                    'back_price' => $back_price,
                    'total_amount' => $all_count_charge,
                    'pay_fee' => $payment_info['pay_fee'],
                    'fee_num' => $fee_num,
                    'entry_criteria' => $valus,
                    'pay_id' => $pay_id
                ];
                SellerApplyInfo::where('apply_id', $apply_id)->where('apply_sn', $apply_sn)->update($other);

                $apply_info['log_id'] = get_paylog_id($apply_id, $pay_type = PAY_APPLYGRADE);
            }

            if ($pay_id > 0 && $payable_amount > 0) {
                $payment = unserialize_config($payment_info['pay_config']);
                $apply_info['order_sn'] = $apply_sn;
                $apply_info['user_id'] = $user_id;
                $apply_info['surplus_amount'] = $payable_amount;
                if ($payment_info['pay_code'] == 'balance') {
                    //查询出当前用户的剩余余额;
                    $user_money = Users::where('user_id', $user_id)->value('user_money');

                    //如果用户余额足够支付订单;
                    if ($user_money > $payable_amount) {

                        /* 修改申请的支付状态 */
                        $other = [
                            'is_paid' => 1,
                            'pay_time' => gmtime(),
                            'pay_status' => 1
                        ];
                        SellerApplyInfo::where('apply_id', $apply_id)->update($other);

                        //更新记录支付
                        PayLog::where('order_id', $apply_id)->where('order_type', PAY_APPLYGRADE)->update(['is_paid' => 1]);

                        log_account_change($user_id, $payable_amount * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['seller_apply'], $apply_sn));
                    } else {
                        return show_message($GLOBALS['_LANG']['balance_insufficient']);
                    }
                } else {
                    if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                        /* 调用相应的支付方式文件 */
                        $payObject = $this->commonRepository->paymentInstance($payment_info['pay_code']);
                        /* 取得在线支付方式的支付按钮 */
                        if (!is_null($payObject)) {
                            $payment_info['pay_button'] = $payObject->get_code($apply_info, $payment);
                        }
                    }
                }

                $this->smarty->assign('payment', $payment_info);
                $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                $this->smarty->assign('amount', price_format($payable_amount, false));
                $this->smarty->assign('order', $apply_info);

                $grade_reload['apply_id'] = $apply_id;

                session()->put('grade_reload.' . session('user_id'), $grade_reload);

                set_prevent_token("grade_cookie");
                return $this->smarty->display('user_transaction.dwt');
            } else {
                return show_message($GLOBALS['_LANG']['apply_success']);
            }
        }

        /* ------------------------------------------------------ */
        //-- 加载商家等级入驻
        /* ------------------------------------------------------ */
        elseif ($action == 'grade_load') {
            $this->smarty->assign("action", $action);
            load_helper('transaction');
            load_helper('payment');
            load_helper('clips');
            load_helper('order');
            $position = assign_ur_here(0, $GLOBALS['_LANG']['seller_garde']);
            $this->smarty->assign('page_title', $position['title']); // 页面标题

            $apply_id = session()->get('grade_reload.' . session('user_id') . '.apply_id');

            if ($apply_id > 0) {
                $seller_apply_info = SellerApplyInfo::where('ru_id', $user_id)->where('apply_id', $apply_id)->first();
                $seller_apply_info = $seller_apply_info ? $seller_apply_info->toArray() : [];

                if (!empty($seller_apply_info)) {
                    if ($seller_apply_info['pay_id'] > 0 && $seller_apply_info['payable_amount'] > 0) {
                        /* 获取支付信息 */
                        $payment_info = [];
                        $payment_info = payment_info($seller_apply_info['pay_id']);
                        //计算支付手续费用
                        $payment_info['pay_fee'] = $seller_apply_info['pay_fee'];
                        $apply_info['order_amount'] = $seller_apply_info['payable_amount'] + $payment_info['pay_fee'];
                        $apply_info['log_id'] = get_paylog_id($apply_id, $pay_type = PAY_APPLYGRADE);
                        $payment = unserialize_config($payment_info['pay_config']);
                        $apply_info['order_sn'] = $seller_apply_info['apply_sn'];
                        $apply_info['user_id'] = $user_id;
                        $apply_info['surplus_amount'] = $seller_apply_info['payable_amount'];
                        if ($payment_info['pay_code'] == 'balance') {
                            //查询出当前用户的剩余余额;
                            $user_money = Users::where('user_id', $user_id)->value('user_money');

                            //如果用户余额足够支付订单;
                            if ($user_money > $seller_apply_info['payable_amount']) {

                                /* 修改申请的支付状态 */
                                $other = [
                                    'is_paid' => 1,
                                    'pay_time' => gmtime(),
                                    'pay_status' => 1
                                ];
                                SellerApplyInfo::where('apply_id', $apply_id)->update($other);

                                //更新记录支付
                                PayLog::where('order_id', $apply_id)->where('order_type', PAY_APPLYGRADE)->update(['is_paid' => 1]);

                                log_account_change($user_id, $seller_apply_info['payable_amount'] * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['seller_apply'], $seller_apply_info['apply_sn']));
                            } else {
                                return show_message($GLOBALS['_LANG']['balance_insufficient']);
                            }
                        } else {
                            if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                                /* 调用相应的支付方式文件 */
                                $payObject = $this->commonRepository->paymentInstance($payment_info['pay_code']);
                                /* 取得在线支付方式的支付按钮 */
                                if (!is_null($payObject)) {
                                    $payment_info['pay_button'] = $payObject->get_code($apply_info, $payment);
                                }
                            }
                        }

                        $this->smarty->assign('payment', $payment_info);
                        $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                        $this->smarty->assign('amount', price_format($seller_apply_info['payable_amount'], false));
                        $this->smarty->assign('order', $apply_info);

                        return $this->smarty->display('user_transaction.dwt');
                    } else {
                        return show_message($GLOBALS['_LANG']['apply_success']);
                    }
                } else {
                    return show_message($GLOBALS['_LANG']['system_error']);
                }
            } else {
                return show_message($GLOBALS['_LANG']['system_error']);
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除商家等级入驻
        /* ------------------------------------------------------ */
        elseif ($action == 'remove_apply_info') {
            $id = (int)request()->input('id', 0);

            /* 判断标准类型，如果上传文件，删除文件 */
            $entry_criteria = SellerApplyInfo::where('apply_id', $id)->value('entry_criteria');
            $entry_criteria = $entry_criteria ? unserialize($entry_criteria) : [];

            if ($entry_criteria) {
                foreach ($entry_criteria as $k => $v) {

                    //获取标准类型
                    $type = EntryCriteria::where('id', $k)->value('type');

                    /* 如果是文件上传，删除文件 */
                    if ($type == 'file' && $v != '') {
                        @unlink(storage_public($v));
                    }
                }
            }

            SellerApplyInfo::where('apply_id', $id)->delete();

            return show_message($GLOBALS['_LANG']['delete_success'], $GLOBALS['_LANG']['back'], 'user.php?act=merchants_upgrade_log');
        }

        /* ------------------------------------------------------ */
        //-- 账户安全
        /* ------------------------------------------------------ */
        elseif ($action == 'account_safe') {
            $verify = addslashes(request()->input('verify', ''));

            $request = get_request_filter(request()->all(), 1);

            $type = addslashes(trim(request()->input('type', 'default')));
            $step = addslashes(trim(request()->input('step', 'first')));

            $where = [
                'user_id' => $user_id
            ];
            $vali_info = $this->userService->userInfo($where);

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_SAFETY) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
            }

            // 判断有没有开通手机验证、邮箱验证、支付密码
            $validate_info = $this->userService->GetValidateInfo($user_id);
            if (empty($validate_info['is_validated']) && empty($validate_info['mobile_phone']) && empty($validate_info['pay_password'])) {
                if ($this->config['user_phone'] == 1 || $this->config['user_login_register'] == 0) {
                    $sign = 'mobile';
                } else {
                    $sign = 'email';
                }
            } else {
                // mobile email pay_pwd 三种验证标识
                $sign = '';
                if ($validate_info['mobile_phone'] && $type == "change_phone") {
                    $sign = 'mobile';
                } elseif ($validate_info['is_validated'] && $type == "change_email") {
                    $sign = 'email';
                } else {
                    if ($this->config['user_phone'] == 1 || $this->config['user_login_register'] == 0) {
                        $sign = 'mobile';
                    } else {
                        $sign = 'email';
                    }
                }
            }

            $sign = request()->input('sign', $sign);
            load_helper('transaction');
            // 获取用户的手机号 发送短信验证码
            $user_info = get_profile($user_id);
            $this->smarty->assign('user_info', $user_info);

            $this->smarty->assign('validate_info', $validate_info);

            // 验证邮件
            if ($type == 'validated_email') {
                $hash = addslashes(trim(request()->input('hash', '')));
                $mail_type = addslashes(trim(request()->input('mail_type', '')));
                if ($hash) {
                    load_helper('passport');
                    $id = register_hash('decode', $hash);
                    if ($id > 0) {
                        switch ($mail_type) {
                            case 'change_pwd':
                                $Loaction = "user.php?act=account_safe&type=change_password&step=second&sign=email&hash=$hash";
                                break;
                            case 'change_mail':
                                $Loaction = "user.php?act=account_safe&type=change_email&step=second&sign=email&hash=$hash";
                                break;
                            case 'change_mobile':
                                $Loaction = "user.php?act=account_safe&type=change_phone&step=second&sign=email&hash=$hash";
                                break;
                            case 'change_paypwd':
                                $Loaction = "user.php?act=account_safe&type=payment_password&step=second&sign=email&hash=$hash";
                                break;

                            case 'editmail':
                                // 验证session， hash 验证成功更新邮箱
                                $new_mail = session('new_email' . $user_id, '');
                                if (isset($new_mail) && empty($new_mail)) {
                                    $Loaction = "user.php?act=account_safe&type=change_email";
                                    return dsc_header("Location: $Loaction\n");
                                }

                                $validated = request()->input('validated', 0);
                                if (!empty($validated)) {
                                    $validated = "&validated=1";
                                } else {
                                    $validated = '';
                                }

                                $other = [
                                    'email' => $new_mail,
                                    'is_validated' => 1
                                ];
                                Users::where('user_id', $id)->update($other);

                                //记录会员操作日志
                                users_log_change(session('user_id'), USER_EMAIL);
                                $Loaction = "user.php?act=account_safe&type=change_email&step=last&sign=editmail_ok" . $validated . "&hash=" . $hash;
                                break;

                            default:
                                break;
                        }
                        return dsc_header("Location: $Loaction\n");
                    }
                }
                return show_message($GLOBALS['_LANG']['validate_fail']);
            }

            //登录密码
            if ($type == 'change_password') {
                if ($verify == 'authcode') {
                    // 异步验证 验证码
                    $authcode = addslashes(trim(request()->input('authCode', '')));

                    $seKey = 'change_password_f';
                    $verify = app(CaptchaVerify::class);
                    $captcha_code = $verify->check($authcode, $seKey, '', 'ajax');

                    load_helper('passport');
                    $this->err->or = true;

                    if (!$captcha_code) {
                        $this->err->or = false;
                    }
                    return json_encode($this->err->or);
                } elseif ($verify == 'mobilecode') {
                    // 异步 验证短信验证码
                    $mobile_code = addslashes(trim(request()->input('mobile_code', '')));
                    if (!empty($mobile_code)) {
                        load_helper('passport');
                        $this->err->or = true;
                        if ($mobile_code != session('sms_mobile_code')) {
                            $this->err->or = false;
                        }
                        return json_encode($this->err->or);
                    }
                } // 异步 验证支付密码
                elseif ($verify == 'pay_pwd') {
                    $pay_password = addslashes(request()->input('payPwd', ''));

                    if ($pay_password) {
                        load_helper('passport');
                        //$result = array('error' => 0, 'message' => ''); //验证码返回 by sunle
                        $this->err->or = true;

                        // 验证支付密码
                        $users_paypwd = $this->userService->getPaypwd($user_id);

                        // 加密因子
                        $ec_salt = $users_paypwd ? $users_paypwd['ec_salt'] : 0;
                        $new_password = md5(md5($pay_pwd) . $ec_salt);

                        if (isset($users_paypwd['pay_password']) && $new_password != $users_paypwd['pay_password']) {
                            //$result['error']   = 1;
                            //$result['message'] = $GLOBALS['_LANG']['pay_password_fail'];
                            $this->err->or = false;
                        }

                        return json_encode($this->err->or);
                    }
                }

                if ($step == 'first') {
                    if ($sign == 'mobile') {
                        if (empty($user_info['mobile_phone'])) {
                            $Loaction = "user.php?act=account_safe&type=change_phone";
                            return dsc_header("Location: $Loaction\n");
                        }

                        /* 短信发送设置 */
                        if (intval($this->config['sms_signin']) > 0) { // 这里
                            $sms_security_code = rand(1000, 9999);

                            session([
                                'sms_security_code' => $sms_security_code
                            ]);

                            $this->smarty->assign('sms_security_code', $sms_security_code);
                            $this->smarty->assign('enabled_sms_signin', 1);
                        }
                    } elseif ($sign == 'email') {
                        $is_validated = Users::where('user_id', $user_id)->value('is_validated');

                        if (empty($is_validated)) {
                            $Loaction = "user.php?act=account_safe&type=change_email";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } elseif ($sign == 'paypwd') {
                        $pay_password = UsersPaypwd::where('user_id', $user_id)->value('pay_password');

                        if (empty($pay_password)) {
                            $Loaction = "user.php?act=account_safe&type=payment_password";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } elseif ($sign == 'validate_mail_ok') {
                        /*
                         *  获取刚刚发送的邮箱地址
                         *  只能是验证邮箱地址------$user_info.mobile_phone
                         */
                    }
                } elseif ($step == 'second') {
                    // 判断邮箱验证还是短信验证还是支付密码验证
                    if ($sign == 'mobile') {
                        //手机号码 验证短信
                        if (!empty($request['mobile_phone'])) {
                            if (empty($request['mobile_code'])) {
                                return show_message($GLOBALS['_LANG']['Mobile_code_null'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $mobile = Users::where('user_id', $user_id)->value('mobile_phone');

                            if ($mobile == $request['mobile_phone'] && $this->config['sms_signin'] == 1) { // 这里是登录入口 $this->config['sms_signin'] 加入口不加
                                if (!empty($request['mobile_code'])) {
                                    if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                                        return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                    }
                                }
                            } elseif ($mobile != $request['mobile_phone'] && $this->config['sms_signin'] == 1) {
                                return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_one'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_two'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } elseif ($sign == 'email') {
                        // 验证一个hash值，不匹配的话 强制退出
                        $hash = addslashes(trim(request()->input('hash', '')));
                        if ($hash) {
                            load_helper('passport');
                            $id = register_hash('decode', $hash);

                            if ($id <= 0) {
                                return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                        }
                    } elseif ($sign == 'paypwd') {
                        // 验证码
                        if ((intval($this->config['captcha'])) && gd_version() > 0) {
                            $captcha = isset($request['authCode']) ? trim($request['authCode']) : '';

                            if (empty($captcha)) {
                                return show_message($GLOBALS['_LANG']['msg_identifying_code'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $seKey = 'change_password_f';
                            $verify = app(CaptchaVerify::class);
                            $captcha_code = $verify->check($captcha, $seKey);

                            if (!$captcha_code) {
                                return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        }

                        // 验证支付密码
                        $pay_password = addslashes(trim(request()->input('pay_password', '')));

                        $row = UsersPaypwd::where('user_id', $user_id)->first();
                        $row = $row ? $row->toArray() : [];

                        $new_password = md5(md5($pay_password) . $row['ec_salt']);

                        if ($new_password != $row['pay_password']) {
                            return show_message($GLOBALS['_LANG']['pay_password_packup_error'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['permissions_null'], 'index.php', '', 'error');
                    }
                } elseif ($step == 'last') {

                    // 验证码检查
                    if ((intval($this->config['captcha']) & CAPTCHA_SAFETY) && gd_version() > 0) {
                        if (empty($request['authCode'])) {
                            return show_message($GLOBALS['_LANG']['msg_identifying_code'], $GLOBALS['_LANG']['back_input'], '', 'error');
                        }

                        $captcha = isset($request['authCode']) ? trim($request['authCode']) : '';

                        $seKey = 'change_password_f';
                        $verify = app(CaptchaVerify::class);
                        $captcha_code = $verify->check($captcha, $seKey);

                        if (!$captcha_code) {
                            return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_input'], '', 'error');
                        }
                    }

                    //判断两个密码是否相同
                    if (!empty($request['new_password']) && (trim($request['new_password']) != trim($request['re_new_password']))) {
                        return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_three'], $GLOBALS['_LANG']['back_input'], '', 'error');
                    }

                    $username = Users::where('user_id', $user_id)->value('user_name');

                    // 修改密码
                    $cfg = [
                        'user_id' => $user_id,
                        'username' => $username,
                        'password' => trim($request['new_password'])
                    ];

                    if (!$GLOBALS['user']->edit_user($cfg, 1)) {
                        return show_message('DB ERROR', $GLOBALS['_LANG']['back'], '');
                    }

                    if (!empty($request['new_password'])) {
                        // 修改密码成功，重新登录 仿京东-暂时注释掉
                        $GLOBALS['user']->logout();
                        $ucdata = empty($GLOBALS['user']->ucdata) ? "" : $GLOBALS['user']->ucdata;
                        //记录会员操作日志
                        users_log_change($user_id, USER_LPASS);
                        return show_message($GLOBALS['_LANG']['pwd_modify_success'] . $ucdata, $GLOBALS['_LANG']['UserLogin'], 'user.php?act=login');

                        // 最新安全评级
                        $this->smarty->assign('security_rating', $this->userService->SecurityRating());
                    }
                }
            } //邮箱验证
            elseif ($type == 'change_email') {
                if ($step == 'first') {
                    if ($sign == 'mobile') {
                        if (empty($user_info['mobile_phone'])) {
                            $Loaction = "user.php?act=account_safe&type=change_phone";
                            return dsc_header("Location: $Loaction\n");
                        }

                        /* 短信发送设置 */
                        if (intval($this->config['sms_signin']) > 0) { // 这里
                            $sms_security_code = rand(1000, 9999);

                            session([
                                'sms_security_code' => $sms_security_code
                            ]);

                            $this->smarty->assign('sms_security_code', $sms_security_code);
                            $this->smarty->assign('enabled_sms_signin', 1);
                        }
                    } elseif ($sign == 'email') {
                        $is_validated = Users::where('user_id', $user_id)->value('is_validated');

                        if ($this->config['user_phone'] == 1) {
                            if (empty($is_validated)) {
                                $Loaction = "user.php?act=account_safe&type=change_email";
                                return dsc_header("Location: $Loaction\n");
                            }
                        } else {
                            $this->smarty->assign('is_validated', $is_validated);
                        }
                    } elseif ($sign == 'paypwd') {
                        $pay_password = UsersPaypwd::where('user_id', $user_id)->value('pay_password');

                        if (empty($pay_password)) {
                            $Loaction = "user.php?act=account_safe&type=payment_password";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } elseif ($sign == 'validate_mail_ok') {
                        /*
                         *  获取刚刚发送的邮箱地址
                         *  只能是验证邮箱地址------$user_info.mobile_phone
                         */
                    }
                } elseif ($step == 'second') {
                    // 判断邮箱验证还是短信验证还是支付密码验证
                    if ($sign == 'mobile') {
                        $user_email = Users::where('user_id', $user_id)->value('email');
                        $this->smarty->assign('user_email', $user_email);

                        //手机号码 验证短信
                        if (!empty($request['mobile_phone'])) {
                            if (empty($request['mobile_code'])) {
                                return show_message($GLOBALS['_LANG']['Mobile_code_null'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $mobile = Users::where('user_id', $user_id)->value('mobile_phone');

                            if ($mobile == $request['mobile_phone'] && $this->config['sms_signin'] == 1) { // 这里是登录入口 $this->config['sms_signin'] 加入口不加
                                if (!empty($request['mobile_code'])) {
                                    if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                                        return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                    }
                                }
                            } elseif ($mobile != $request['mobile_phone'] && $this->config['sms_signin'] == 1) {
                                return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_one'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_two'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } elseif ($sign == 'email') {
                        // 验证一个hash值，不匹配的话 强制退出
                        $hash = addslashes(trim(request()->input('hash', '')));
                        if ($hash) {
                            load_helper('passport');
                            $id = register_hash('decode', $hash);

                            if ($id <= 0) {
                                return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                        }
                    } elseif ($sign == 'paypwd') {
                        // 验证码
                        if ((intval($this->config['captcha'])) && gd_version() > 0) {
                            $captcha = isset($request['authCode']) ? trim($request['authCode']) : '';

                            if (empty($captcha)) {
                                return show_message($GLOBALS['_LANG']['msg_identifying_code'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $seKey = 'change_password_f';
                            $verify = app(CaptchaVerify::class);
                            $captcha_code = $verify->check($captcha, $seKey);

                            if (!$captcha_code) {
                                return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        }
                        // 验证支付密码
                        $pay_password = addslashes(trim(request()->input('pay_password', '')));
                        $row = UsersPaypwd::where('user_id', $user_id)->first();
                        $row = $row ? $row->toArray() : [];

                        $new_password = md5(md5($pay_password) . $row['ec_salt']);

                        if ($new_password != $row['pay_password']) {
                            return show_message($GLOBALS['_LANG']['pay_password_packup_error'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } elseif ($sign == 'edit_email_ok') {
                        // 第二部更换邮箱 -发送邮件成功-
                        // 获取刚刚发送的邮箱地址
                        if (empty(session('new_email' . $user_id))) {
                            return show_message($GLOBALS['_LANG']['msg_identifying_code'], $GLOBALS['_LANG']['back_input'], '', 'error');
                        } else {
                            $this->smarty->assign('validate_new_mail', session('new_email' . $user_id));
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['permissions_null'], 'index.php', '', 'error');
                    }
                } elseif ($step == 'last') {
                    if ($sign == 'editmail_ok') {
                        //识别是验证邮箱还是修改邮箱
                        $validated = request()->input('validated', 0);
                        $this->smarty->assign('validated', $validated);

                        // 安全等级
                        $this->smarty->assign('security_rating', $this->userService->SecurityRating());
                        // 更改邮件成功
                    }
                } elseif ($step == 'second_email_verify') {
                    // 邮件类型 修改密码(change_pwd)、邮箱认证方式(validate_mail)、更改邮箱(change_mail)、更改支付密码(change_paypwd)等
                    $mail_type = addslashes(trim(request()->input('mail_type', 'validate_mail')));
                    $validated = request()->input('validated', 0);
                    load_helper('passport');
                    $result = ['error' => 0, 'message' => '', 'content' => ''];

                    if ($user_id == 0) {
                        /* 用户没有登录 */
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['login_please'];
                        return response()->json($result);
                    }

                    // 判断邮箱是否已验证，已验证的话返回
                    $email = !empty($request['mail_address_data']) ? trim($request['mail_address_data']) : '';
                    if (!empty($email)) {
                        // 验证邮箱格式
                        $a = "/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)(\.[a-z]*)/i";  //邮箱匹配
                        if (!preg_match($a, $email)) {
                            // 返回json
                            $result['error'] = 1;
                            $result['message'] = $GLOBALS['_LANG']['msg_email_format'];
                            return response()->json($result);
                        }
                    } else {
                        // 返回json
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['msg_email_null'];
                        return response()->json($result);
                    }

                    switch ($mail_type) {
                        case 'change_pwd':
                            if (send_account_safe_hash($user_id, 'change_pwd')) {
                                $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                                return response()->json($result);
                            } else {
                                $result['error'] = 1;
                                $result['message'] = $this->err->last_message();
                            }
                            break;
                        case 'change_mail':
                            if (send_account_safe_hash($user_id, 'change_mail')) {
                                $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                                return response()->json($result);
                            } else {
                                $result['error'] = 1;
                                $result['message'] = $this->err->last_message();
                            }
                            break;
                        case 'change_mobile':
                            if (send_account_safe_hash($user_id, 'change_mobile')) {
                                $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                                return response()->json($result);
                            } else {
                                $result['error'] = 1;
                                $result['message'] = $this->err->last_message();
                            }
                            break;
                        case 'change_paypwd':
                            if (send_account_safe_hash($user_id, 'change_paypwd')) {
                                $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                                return response()->json($result);
                            } else {
                                $result['error'] = 1;
                                $result['message'] = $this->err->last_message();
                            }
                            break;

                        case 'validate_mail':
                            if (send_regiter_hash($user_id)) {
                                $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                                return response()->json($result);
                            } else {
                                $result['error'] = 1;
                                $result['message'] = $this->err->last_message();
                            }
                            break;
                        case 'edit_mail':
                            // 获取更改邮件地址 session 24小时
                            $new_email = !empty($request['mail_address_data']) ? trim($request['mail_address_data']) : '';
                            if (!empty($new_email)) {
                                // 验证邮箱格式
                                $a = "/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)(\.[a-z]*)/i";  //邮箱匹配
                                if (!preg_match($a, $email)) {
                                    // 返回json
                                    $result['error'] = 1;
                                    $result['message'] = $GLOBALS['_LANG']['msg_email_format'];
                                    return response()->json($result);
                                }

                                session()->put('new_email' . $user_id, $new_email);

                                if (send_account_safe_hash($user_id, 'editmail', $validated)) {
                                    $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                                    return response()->json($result);
                                } else {
                                    $result['error'] = 1;
                                    $result['message'] = $this->err->last_message();
                                }
                            } else {
                                $result['error'] = 1;
                                $result['message'] = $GLOBALS['_LANG']['msg_email_null'];
                            }
                            break;

                        default:
                            break;
                    }

                    return response()->json($result);
                }
            } //手机验证
            elseif ($type == 'change_phone') {
                if ($step == 'first') {
                    if ($sign == 'mobile') {
                        /* 短信发送设置 */
                        if (intval($this->config['sms_signin']) > 0) { // 这里
                            $sms_security_code = rand(1000, 9999);

                            session([
                                'sms_security_code' => $sms_security_code
                            ]);

                            $this->smarty->assign('sms_security_code', $sms_security_code);
                            $this->smarty->assign('enabled_sms_signin', 1);
                        }
                    } elseif ($sign == 'email') {
                        $is_validated = Users::where('user_id', $user_id)->value('is_validated');

                        if (empty($is_validated)) {
                            $Loaction = "user.php?act=account_safe&type=change_email";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } elseif ($sign == 'paypwd') {
                        $pay_password = UsersPaypwd::where('user_id', $user_id);

                        if (empty($pay_password)) {
                            $Loaction = "user.php?act=account_safe&type=payment_password";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } elseif ($sign == 'validate_mail_ok') {
                        /*
                         *  获取刚刚发送的邮箱地址
                         *  只能是验证邮箱地址------$user_info.mobile_phone
                         */
                    }
                } elseif ($step == 'second') {
                    $request['mobile_phone'] = isset($request['mobile_phone']) && !empty($request['mobile_phone']) ? addslashes($request['mobile_phone']) : '';

                    /* 短信发送设置 */
                    if (intval($this->config['sms_signin']) > 0) { // 这里要不要在后台添加一个入口
                        $sms_security_code = rand(1000, 9999);

                        session([
                            'sms_security_code' => $sms_security_code
                        ]);

                        $this->smarty->assign('sms_security_code', $sms_security_code);
                        $this->smarty->assign('enabled_sms_signin', 1);
                    }

                    // 判断邮箱验证还是短信验证还是支付密码验证
                    if ($sign == 'mobile') {
                        // 绑定该手机
                        if (!empty($request['bind'])) {
                            $this->smarty->assign('mobile_phone', $request['mobile_phone']);
                        }

                        //手机号码 验证短信
                        if (!empty($request['mobile_phone'])) {
                            if (empty($request['mobile_code'])) {
                                return show_message($GLOBALS['_LANG']['Mobile_code_null'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $mobile = Users::where('user_id', $user_id)->value('mobile_phone');

                            if (empty($mobile)) {
                                if (!empty($request['mobile_code'])) {
                                    if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                                        return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                    } else {
                                        Users::where('user_id', $user_id)->update(['mobile_phone' => $request['mobile_phone']]);

                                        $Loaction = "user.php?act=account_safe&type=change_phone&step=last&step=last&phone_first=1";
                                        return dsc_header("Location: $Loaction\n");
                                    }
                                }
                            } else {
                                if ($mobile == $request['mobile_phone'] && $this->config['sms_signin'] == 1) { // 这里是登录入口 $this->config['sms_signin'] 加入口不加
                                    if (!empty($request['mobile_code'])) {
                                        if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                                            return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                        }
                                    }
                                } elseif ($mobile != $request['mobile_phone'] && $this->config['sms_signin'] == 1) {
                                    return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_one'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                }
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_two'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } elseif ($sign == 'email') {
                        // 验证一个hash值，不匹配的话 强制退出
                        $hash = addslashes(trim(request()->input('hash', '')));
                        if ($hash) {
                            load_helper('passport');
                            $id = register_hash('decode', $hash);

                            if ($id <= 0) {
                                return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                        }
                    } elseif ($sign == 'paypwd') {
                        // 验证码
                        if ((intval($this->config['captcha'])) && gd_version() > 0) {
                            $captcha = isset($request['authCode']) ? trim($request['authCode']) : '';

                            if (empty($captcha)) {
                                return show_message($GLOBALS['_LANG']['msg_identifying_code'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $seKey = 'change_password_f';
                            $verify = app(CaptchaVerify::class);
                            $captcha_code = $verify->check($captcha, $seKey);

                            if (!$captcha_code) {
                                return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        }

                        // 验证支付密码
                        $pay_password = addslashes(trim(request()->input('pay_password', '')));

                        $row = UsersPaypwd::where('user_id', $user_id)->first();
                        $row = $row ? $row->toArray() : [];

                        $new_password = md5(md5($pay_password) . $row['ec_salt']);

                        if ($new_password != $row['pay_password']) {
                            return show_message($GLOBALS['_LANG']['pay_password_packup_error'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['permissions_null'], 'index.php', '', 'error');
                    }
                } elseif ($step == 'last') {
                    $phone_first = (int)request()->input('phone_first', 0);

                    //手机号码 验证短信 （$phone_first 第一次验证手机号）
                    if (!empty($request['mobile_phone']) && $this->config['sms_signin'] == 1 || $phone_first == 1) { // 这里是登录入口 $this->config['sms_signin'] 加入口不加
                        if (isset($request['mobile_code'])) {
                            if (!empty($request['mobile_code'])) {
                                if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                                    return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                }
                            } else {
                                return show_message($GLOBALS['_LANG']['Mobile_code_null'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        }

                        // 更新手机号
                        if ($phone_first == 0) {
                            $other = [
                                'mobile_phone' => $request['mobile_phone']
                            ];

                            Users::where('user_id', $user_id)->update($other);

                            //记录会员操作日志
                            users_log_change(session('user_id'), USER_PHONE);
                        }

                        // 最新安全评级
                        $this->smarty->assign('security_rating', $this->userService->SecurityRating());
                    } else {
                        return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_two'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                    }
                }
            } //支付密码
            elseif ($type == 'payment_password') {
                if ($step == 'first') {
                    if ($sign == 'mobile') {
                        //操作类型 0，启用支付密码，1支付密码找回
                        $password_type = (int)request()->input('password_type', 0);
                        $this->smarty->assign('password_type', $password_type);
                        if (empty($user_info['mobile_phone'])) {
                            $Loaction = "user.php?act=account_safe&type=change_phone";
                            return dsc_header("Location: $Loaction\n");
                        }

                        /* 短信发送设置 */
                        if (intval($this->config['sms_signin']) > 0) { // 这里
                            $sms_security_code = rand(1000, 9999);

                            session([
                                'sms_security_code' => $sms_security_code
                            ]);

                            $this->smarty->assign('sms_security_code', $sms_security_code);
                            $this->smarty->assign('enabled_sms_signin', 1);
                        }
                    } elseif ($sign == 'email') {
                        $is_validated = Users::where('user_id', $user_id)->value('is_validated');

                        if (empty($is_validated)) {
                            $Loaction = "user.php?act=account_safe&type=change_email";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } elseif ($sign == 'paypwd') {
                        $pay_password = UsersPaypwd::where('user_id', $user_id)->value('pay_password');

                        if (empty($pay_password)) {
                            $Loaction = "user.php?act=account_safe&type=payment_password";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } elseif ($sign == 'validate_mail_ok') {
                        /*
                         *  获取刚刚发送的邮箱地址
                         *  只能是验证邮箱地址------$user_info.mobile_phone
                         */
                    }
                } elseif ($step == 'second') {
                    // 查询该用户是否已开启
                    $user_paypwd = UsersPaypwd::where('user_id', $user_id)->first();
                    $user_paypwd = $user_paypwd ? $user_paypwd->toArray() : [];

                    $this->smarty->assign('user_paypwd', $user_paypwd);

                    // 判断邮箱验证还是短信验证还是支付密码验证
                    if ($sign == 'mobile') {
                        $password_type = (int)request()->input('password_type', 0);//操作类型 0，启用支付密码，1支付密码找回
                        $this->smarty->assign('password_type', $password_type);
                        // 绑定该手机
                        if (!empty($request['bind'])) {
                            $this->smarty->assign('mobile_phone', $request['mobile_phone']);
                        }

                        //手机号码 验证短信
                        if (!empty($request['mobile_phone'])) {
                            if (empty($request['mobile_code'])) {
                                return show_message($GLOBALS['_LANG']['Mobile_code_null'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $mobile = Users::where('user_id', $user_id)->value('mobile_phone');

                            if ($mobile == $request['mobile_phone'] && $this->config['sms_signin'] == 1) { // 这里是登录入口 $this->config['sms_signin'] 加入口不加
                                if (!empty($request['mobile_code'])) {
                                    if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                                        return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                    }
                                }
                            } elseif ($mobile != $request['mobile_phone'] && $this->config['sms_signin'] == 1) {
                                return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_one'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_two'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } elseif ($sign == 'email') {
                        // 验证一个hash值，不匹配的话 强制退出
                        $hash = addslashes(trim(request()->input('hash', '')));
                        if ($hash) {
                            load_helper('passport');
                            $id = register_hash('decode', $hash);

                            if ($id <= 0) {
                                return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                            }
                        } else {
                            return show_message($GLOBALS['_LANG']['validate_fail'], $GLOBALS['_LANG']['back'], 'index.php');
                        }
                    } elseif ($sign == 'paypwd') {
                        // 验证码
                        if ((intval($this->config['captcha'])) && gd_version() > 0) {
                            $captcha = isset($request['authCode']) ? trim($request['authCode']) : '';

                            if (empty($captcha)) {
                                return show_message($GLOBALS['_LANG']['msg_identifying_code'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            $seKey = 'change_password_f';
                            $verify = app(CaptchaVerify::class);
                            $captcha_code = $verify->check($captcha, $seKey);

                            if (!$captcha_code) {
                                return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                            }
                        }

                        // 验证支付密码
                        $pay_password = addslashes(trim(request()->input('pay_password', '')));

                        $row = UsersPaypwd::where('user_id', $user_id)->first();
                        $row = $row ? $row->toArray() : [];

                        $new_password = md5(md5($pay_password) . $row['ec_salt']);

                        if ($new_password != $row['pay_password']) {
                            return show_message($GLOBALS['_LANG']['pay_password_packup_error'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['permissions_null'], 'index.php', '', 'error');
                    }
                } elseif ($step == 'last') {
                    $password_type = (int)request()->input('password_type', 0);//操作类型 0，启用支付密码，1支付密码找回
                    $this->smarty->assign('password_type', $password_type);

                    // 存储支付密码设置项
                    $count = UsersPaypwd::where('user_id', $user_id)->count();

                    $pay_online = !empty($request['pay_online']) ? intval($request['pay_online']) : 0;
                    $user_surplus = !empty($request['user_surplus']) ? intval($request['user_surplus']) : 0;
                    $user_point = !empty($request['user_point']) ? intval($request['user_point']) : 0;
                    $baitiao = !empty($request['baitiao']) ? intval($request['baitiao']) : 0;
                    $gift_card = !empty($request['gift_card']) ? intval($request['gift_card']) : 0;

                    $pay_password = !empty($request['new_password']) ? trim($request['new_password']) : 0;
                    $re_pay_password = !empty($request['re_new_password']) ? trim($request['re_new_password']) : 0;

                    $real_user = [
                        'user_id' => $user_id,
                        'pay_online' => $pay_online,
                        'user_surplus' => $user_surplus,
                        'user_point' => $user_point,
                        'baitiao' => $baitiao,
                        'gift_card' => $gift_card,
                    ];

                    $this->smarty->assign('security_rating', $this->userService->SecurityRating());

                    if (!empty($pay_password) && !empty($re_pay_password)) {
                        // 支付密码长度限制6位数字
                        if (strlen($pay_password) != 6) {
                            return show_message($GLOBALS['_LANG']['paypwd_length_limit'], $GLOBALS['_LANG']['back_input'], '', 'error');
                        }
                        if ($re_pay_password != $pay_password) {
                            return show_message($GLOBALS['_LANG']['password_difference'], $GLOBALS['_LANG']['back_input'], '', 'error');
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['Real_name_password_null'], $GLOBALS['_LANG']['back_input'], '', 'error');
                    }

                    // 加密
                    $ec_salt = rand(1, 9999);
                    $new_password = md5(md5($pay_password) . $ec_salt);

                    $real_user['pay_password'] = $new_password;
                    $real_user['ec_salt'] = $ec_salt;

                    //记录会员操作日志
                    users_log_change($user_id, USER_PPASS);

                    if ($count == 1) {
                        $res = UsersPaypwd::where('user_id', $user_id)->update($real_user);

                        if (!$res) {
                            return show_message($GLOBALS['_LANG']['on_failure'], $GLOBALS['_LANG']['back_choose'], '', 'error');
                        }
                    } else {
                        $res = UsersPaypwd::insertGetId($real_user);

                        if (!$res) {
                            return show_message($GLOBALS['_LANG']['on_failure'], $GLOBALS['_LANG']['back_choose'], '', 'error');
                        }
                    }
                }
            } //实名认证
            elseif ($type == 'real_name') {
                // 获取实名信息
                $real_user = get_users_real($user_id);

                if ($step == 'first') {
                    $operate = addslashes(trim(request()->input('operate', '')));
                    $operate = $operate == 'edit' ? $operate : '';

                    if ($real_user && empty($operate)) {
                        $Loaction = "user.php?act=account_safe&type=real_name&step=realname_ok";
                        return dsc_header("Location: $Loaction\n");
                    }
                    if ($operate) {
                        $this->smarty->assign('real_user', $real_user);
                        $this->smarty->assign('operate', 'edit');
                    }

                    /* 短信发送设置 */
                    if (intval($this->config['sms_signin']) > 0) { // 这里要不要在后台添加一个入口
                        $sms_security_code = rand(1000, 9999);

                        session([
                            'sms_security_code' => $sms_security_code
                        ]);

                        $this->smarty->assign('sms_security_code', $sms_security_code);
                        $this->smarty->assign('enabled_sms_signin', 1);
                    }
                } elseif ($step == 'second') {
                    // 验证手机号是否为本人
                    // ..........................

                    //手机号码 验证短信
                    if (!empty($request['mobile_phone'])) {
                        if (intval($this->config['sms_signin']) > 0) {
                            if (empty($request['mobile_code'])) {
                                return show_message($GLOBALS['_LANG']['Mobile_code_null'], $GLOBALS['_LANG']['back_input'], '', 'error');
                            }

                            if (!empty($request['mobile_code'])) {
                                if ($request['mobile_phone'] != session('sms_mobile') || $request['mobile_code'] != session('sms_mobile_code')) {
                                    return show_message($GLOBALS['_LANG']['Mobile_code_fail'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                                }
                            }
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['Real_name_authentication_Mobile_two'], $GLOBALS['_LANG']['back_input_Code'], '', 'error');
                    }

                    // 保存实名认证信息
                    $real_user['user_id'] = $user_id;
                    $real_user['real_name'] = addslashes(trim($request['real_name']));
                    $real_user['self_num'] = addslashes(trim($request['self_num'])); // 身份证号
                    $real_user['bank_mobile'] = addslashes(trim($request['mobile_phone']));
                    $real_user['bank_name'] = addslashes(trim($request['bank_name']));
                    $real_user['bank_card'] = addslashes(trim($request['bank_card']));
                    $textfile_zheng = addslashes(trim($request['textfile_zheng']));
                    $textfile_fan = addslashes(trim($request['textfile_fan']));
                    $real_user['add_time'] = gmtime();
                    $real_user['review_status'] = 0;

                    /* 身份证正反面 */

                    if (empty($_FILES['front_of_id_card']['size'])) {
                        $front_name = $textfile_zheng;
                    } else {
                        $front_name = $image->upload_image($_FILES['front_of_id_card'], 'idcard');
                        $this->dscRepository->getOssAddFile([$front_name]);
                    }

                    if (empty($_FILES['reverse_of_id_card']['size'])) {
                        $reverse_name = $textfile_fan;
                    } else {
                        $reverse_name = $image->upload_image($_FILES['reverse_of_id_card'], 'idcard');
                        $this->dscRepository->getOssAddFile([$reverse_name]);
                    }

                    $real_user['front_of_id_card'] = $front_name;
                    $real_user['reverse_of_id_card'] = $reverse_name;

                    if (empty($real_user['real_name'])) {
                        return show_message($GLOBALS['_LANG']['Real_name_null'], $GLOBALS['_LANG']['back_Fill'], '', 'error');
                    }
                    if (empty($real_user['self_num'])) {
                        return show_message($GLOBALS['_LANG']['self_num_null'], $GLOBALS['_LANG']['back_Fill'], '', 'error');
                    }
                    if (empty($real_user['bank_name'])) {
                        return show_message($GLOBALS['_LANG']['bank_name_null'], $GLOBALS['_LANG']['back_Fill'], '', 'error');
                    }
                    if (empty($real_user['bank_card'])) {
                        return show_message($GLOBALS['_LANG']['bank_card_null'], $GLOBALS['_LANG']['back_Fill'], '', 'error');
                    }
                    if (empty($real_user['bank_mobile'])) {
                        return show_message($GLOBALS['_LANG']['bank_mobile_null'], $GLOBALS['_LANG']['back_Fill'], '', 'error');
                    }

                    $count_user = UsersReal::where('user_id', $user_id)->where('user_type', 0)->count();

                    if ($count_user) {
                        $res = UsersReal::where('user_id', $user_id)->where('user_type', 0)->update($real_user);

                        if ($res) {
                            $Loaction = "user.php?act=account_safe&type=real_name&step=realname_ok";
                            return dsc_header("Location: $Loaction\n");
                        }
                    } else {
                        $res = UsersReal::insertGetId($real_user);

                        if ($res) {
                            $Loaction = "user.php?act=account_safe&type=real_name&step=realname_ok";
                            return dsc_header("Location: $Loaction\n");
                        }
                    }
                } elseif ($step == 'realname_ok') {
                    if (!$real_user) {
                        //记录会员操作日志
                        users_log_change($user_id, USER_REAL);
                        $Loaction = "user.php?act=account_safe&type=real_name&step=first";
                        return dsc_header("Location: $Loaction\n");
                    }
                    $real_user['validate_time'] = local_date('Y-m-d H:i:s', empty($real_user['add_time']) ? '' : $real_user['add_time']);
                    $real_user['reverse_of_id_card'] = get_image_path($real_user['reverse_of_id_card']);
                    $real_user['front_of_id_card'] = get_image_path($real_user['front_of_id_card']);
                    $this->smarty->assign('real_user', $real_user);
                    $this->smarty->assign('edit_user', 'user.php?act=account_safe&type=real_name&step=first&operate=edit');

                    $mobile = Users::where('user_id', $user_id)->value('mobile_phone');
                    $this->smarty->assign('mobile_phone', $mobile);
                }
            } elseif ($type == 'default') {
                // 邮箱验证
                $res = Users::where('user_id', $user_id)->first();
                $res = $res ? $res->toArray() : [];

                if ($res) {
                    $res['email_validate'] = $res['is_validated'];
                    $res['paypwd_id'] = UsersPaypwd::where('user_id', $res['user_id'])->value('paypwd_id');
                    $UsersReal = UsersReal::select('real_id', 'real_name', 'bank_card')->where('user_id', $res['user_id'])->where('user_type', 0)->first();
                    $UsersReal = $UsersReal ? $UsersReal->toArray() : [];

                    $res = $UsersReal ? array_merge($res, $UsersReal) : $res;
                }

                $this->smarty->assign('validate', $res);
                $this->smarty->assign('security_rating', $this->userService->SecurityRating());
            }

            $this->smarty->assign('type', $type);
            $this->smarty->assign('step', $step);
            $this->smarty->assign('sign', $sign);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 账号绑定
        /* ------------------------------------------------------ */
        elseif ($action == 'account_bind') {

            $qq_info = $this->userCommonService->getConnectUser($user_id, 'qq');
            $weibo_info = $this->userCommonService->getConnectUser($user_id, 'weibo');
            $weixin_info = $this->userCommonService->getConnectUser($user_id, 'wechat');

            $this->smarty->assign('qq_info', $qq_info);
            $this->smarty->assign('weibo_info', $weibo_info);
            $this->smarty->assign('weixin_info', $weixin_info);

            $WebsiteList = $this->userCommonService->getWebsiteList();

            $insert = [];
            foreach ($WebsiteList as $k => $v) {
                if ($v['type'] == 'wechat') {
                    unset($WebsiteList[$k]); // 不显示微信授权登录（微信端）
                }
                if ($v['type'] == "qq") {
                    $insert['qq_install'] = $v['install'];
                }
                if ($v['type'] == "weibo") {
                    $insert['weibo_install'] = $v['install'];
                }
                // PC 微信扫码登录
                if ($v['type'] == "weixin") {
                    $insert['wechat_install'] = $v['install'];
                }
            }

            if (empty($back_act)) {
                if (empty($back_act) && request()->server('HTTP_REFERER')) {
                    $back_act = request()->server('HTTP_REFERER');
                } else {
                    $back_act = route('user');
                }
            }
            $this->smarty->assign('back_act', $back_act);

            $info = $this->userCommonService->getUserDefault($user_id);
            $this->smarty->assign('info', $info);
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('insert', $insert);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 解除会员绑定
        /* ------------------------------------------------------ */
        elseif ($action == 'oath_remove') {
            $result = ['error' => 0, 'message' => ''];

            $id = request()->input('id', 0); // 绑定id

            $username = request()->input('identity', ''); //用户名

            if ($id) {
                $user_id = Users::where('user_name', $username)->orWhere('mobile_phone', $username)->value('user_id');
                if ($user_id) {
                    ConnectUser::where('user_id', $user_id)->where('id', $id)->delete();
                }
                $result['id'] = $id;
                $result['identity'] = $username;
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 我的发票
        /* ------------------------------------------------------ */
        elseif ($action == 'invoice') {
            load_helper('transaction');

            $page = (int)request()->input('page', 1);

            $record_count = OrderInfo::where('main_count', 0)
                ->where('user_id', $user_id);

            $record_count = $record_count->count();

            $invoice_list = invoice_list($user_id, $record_count, $page);

            $this->smarty->assign('invoice_list', $invoice_list);
            $this->smarty->assign('action', $action);
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 我的增值税发票信息
        /* ------------------------------------------------------ */
        elseif ($action == 'vat_invoice_info') {
            load_helper('transaction');

            $where = [
                'user_id' => $user_id
            ];
            $res = $this->userService->getUsersVatInvoicesInfo($where);


            if ($res) {
                $new_vat_consignee_list = get_vat_consignee_list($res['id']);
                $this->smarty->assign('new_vat_consignee_list', $new_vat_consignee_list);

                $this->smarty->assign('submitted', true);
                $audit_status = $res['audit_status'];
                $this->smarty->assign('audit_status', $audit_status);
                $this->smarty->assign('vat_info', $res);
                $this->smarty->assign('vat_id', $res['id']);
            }

            $this->smarty->assign('action', $action);
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 我的增值税发票信息
        /* ------------------------------------------------------ */
        elseif ($action == 'vat_insert' || $action == 'vat_update') {
            load_helper('transaction');

            $vat_id = addslashes(trim(request()->input('vat_id', '')));
            $user_id = session('user_id');
            $status = addslashes(trim(request()->input('status', 'insert')));
            $company_name = addslashes(trim(request()->input('company_name', '')));
            $tax_id = addslashes(trim(request()->input('tax_id', '')));
            $company_address = addslashes(trim(request()->input('company_address', '')));
            $company_telephone = addslashes(trim(request()->input('company_telephone', '')));
            $bank_of_deposit = addslashes(trim(request()->input('bank_of_deposit', '')));
            $bank_account = addslashes(trim(request()->input('bank_account', '')));
            $audit_status = 0;


            $content = [
                'company_name' => $company_name,
                'user_id' => $user_id,
                'tax_id' => $tax_id,
                'company_address' => $company_address,
                'company_telephone' => $company_telephone,
                'bank_of_deposit' => $bank_of_deposit,
                'bank_account' => $bank_account,
                'audit_status' => $audit_status
            ];
            if ($action = 'vat_insert') {
                $content['add_time'] = gmtime();
            }

            if ($vat_id) {
                if ($status == 'update') {
                    $res = UsersVatInvoicesInfo::where('id', $vat_id)->update($content);

                    if ($res) {
                        $this->smarty->assign('submitted', true);
                    }
                }
                $this->smarty->assign('edit', 'vat_update');
                $this->smarty->assign('vat_id', $vat_id);
                $this->smarty->assign('status', 'update');
            } else {
                $vat_count = UsersVatInvoicesInfo::where('user_id', $user_id)->count();
                if (!$vat_count) {
                    $id = UsersVatInvoicesInfo::insertGetId($content);

                    $this->smarty->assign('submitted', true);
                    $this->smarty->assign('vat_id', $id);
                }
            }

            $where = [
                'user_id' => $user_id
            ];
            $res = $this->userService->getUsersVatInvoicesInfo($where);

            $new_vat_consignee_list = get_vat_consignee_list($res['id']);
            $this->smarty->assign('new_vat_consignee_list', $new_vat_consignee_list);

            if ($res) {
                $audit_status = $res['audit_status'];
                $this->smarty->assign('audit_status', $audit_status);
            }

            $this->smarty->assign('vat_info', $res);
            $this->smarty->assign('action', 'vat_invoice_info');
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 删除增值税发票
        /* ------------------------------------------------------ */
        elseif ($action == 'vat_remove') {
            load_helper('transaction');

            $vat_id = (int)request()->input('vat_id', 0);

            UsersVatInvoicesInfo::where('id', $vat_id)->delete();

            $this->smarty->assign('action', 'vat_invoice_info');
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 增票收票信息
        /* ------------------------------------------------------ */
        elseif ($action == 'vat_consignee') {
            load_helper('transaction');

            $vat_id = (int)request()->input('vat_id', 0);
            $status = addslashes(trim(request()->input('status', '')));

            if ($status == 'update') {
                $consignee_name = addslashes(trim(request()->input('consignee_name', '')));
                $consignee_mobile_phone = addslashes(trim(request()->input('consignee_mobile_phone', '')));
                $country = addslashes(trim(request()->input('country', '')));
                $province = addslashes(trim(request()->input('province', '')));
                $city = addslashes(trim(request()->input('city', '')));
                $district = addslashes(trim(request()->input('district', '')));
                $consignee_address = addslashes(trim(request()->input('consignee_address', '')));

                $content = [
                    'consignee_name' => $consignee_name,
                    'consignee_mobile_phone' => $consignee_mobile_phone,
                    'country' => $country,
                    'province' => $province,
                    'city' => $city,
                    'district' => $district,
                    'consignee_address' => $consignee_address,
                    'audit_status' => 0
                ];

                $vat_id = UsersVatInvoicesInfo::where('user_id', $user_id)->value('id');
                UsersVatInvoicesInfo::where('id', $vat_id)->where('user_id', $user_id)->update($content);
            }

            $where = [
                'user_id' => $user_id,
                'id' => $vat_id
            ];
            $vat_info = $this->userService->getUsersVatInvoicesInfo($where);

            $new_vat_consignee_list = [];
            if ($vat_info) {
                $new_vat_consignee_list = get_vat_consignee_list($vat_info['id']);
            }

            $this->smarty->assign('new_vat_consignee_list', $new_vat_consignee_list);

            if (empty($status)) {
                $this->smarty->assign('action', $action);
            } else {
                $this->smarty->assign('status', $status);
                $this->smarty->assign('submitted', true);
                $this->smarty->assign('action', 'vat_invoice_info');
            }

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            $country = $vat_info['country'] ?? 0;
            $province = $vat_info['province'] ?? 0;
            $city = $vat_info['city'] ?? 0;

            $country_list = $this->areaService->getRegionsLog(0, 0);
            $province_list = $this->areaService->getRegionsLog(1, $country);
            $city_list = $this->areaService->getRegionsLog(2, $province);
            $district_list = $this->areaService->getRegionsLog(3, $city);
            $this->smarty->assign('name_of_region', [$this->config['name_of_region_1'], $this->config['name_of_region_2'], $this->config['name_of_region_3'], $this->config['name_of_region_4']]);

            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);

            $audit_status = $vat_info['audit_status'] ?? 0;
            $this->smarty->assign('audit_status', $audit_status);
            $this->smarty->assign('vat_id', $vat_id);
            $this->smarty->assign('vat_info', $vat_info);

            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 提交增票资质信息
        /* ------------------------------------------------------ */
        elseif ($action == 'flow_inv_form') {
            load_helper('visual');
            load_helper('transaction');
            $result = ['error' => 0, 'content' => ''];

            $obj = dsc_decode(request()->input('msg', ''));
            $arr = $this->dscRepository->objectToArray($obj);

            $user_id = session('user_id');
            $status = addslashes(trim(request()->input('status', '')));

            $content = [
                'company_name' => $arr['company_name'],
                'user_id' => $user_id,
                'tax_id' => $arr['tax_id'],
                'company_address' => $arr['company_address'],
                'company_telephone' => $arr['company_telephone'],
                'bank_of_deposit' => $arr['bank_of_deposit'],
                'bank_account' => $arr['bank_account'],
                'consignee_name' => $arr['consignee_name'],
                'consignee_mobile_phone' => $arr['consignee_mobile_phone'],
                'country' => $arr['country'],
                'province' => $arr['province'],
                'city' => $arr['city'],
                'district' => $arr['district'],
                'consignee_address' => $arr['consignee_address'],
                'add_time' => gmtime(),
                'audit_status' => 0
            ];

            $vat_id = UsersVatInvoicesInfo::where('user_id', $user_id)->value('id');

            if ($vat_id) {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['invoice_aptitude_apply'];
            } else {
                $vat_id = UsersVatInvoicesInfo::insertGetId($content);

                if ($vat_id) {
                    $result['content'] = $GLOBALS['_LANG']['invoice_aptitude_examine'];
                }
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 会员操作日志
        /* ------------------------------------------------------ */
        elseif ($action == 'users_log') {
            $page = (int)request()->input('page', 1);

            $record_count = UsersLog::where('user_id', $user_id)->where('change_type', '<>', 9)->count();

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $user_log = $this->userService->GetUsersLogList($user_id, $pager['size'], $pager['start']);
            $this->smarty->assign("user_log", $user_log);
            $this->smarty->assign("page", $page);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign("action", $action);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 违规举报
        /* ------------------------------------------------------ */
        elseif ($action == 'illegal_report') {
            load_helper('transaction');
            $page = (int)request()->input('page', 1);

            //获取恶意举报冻结结束时间
            $report_time = Users::where('user_id', $user_id)->value('report_time');
            $report_time = local_date('Y-m-d H:i:s', $report_time);

            $record_count = GoodsReport::where('user_id', $user_id)->count();

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $goods_report = $this->userService->getGoodsReportList($pager['size'], $pager['start']);
            $this->smarty->assign("goods_report", $goods_report);
            $this->smarty->assign("page", $page);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('report_time', $report_time);
            $this->smarty->assign("action", $action);
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 违规举报录入
        /* ------------------------------------------------------ */
        elseif ($action == 'goods_report') {
            assign_template();

            $report_id = (int)request()->input('report_id', 0);
            $goods_id = (int)request()->input('goods_id', 0);

            $where = '';
            if ($goods_id > 0) {
                //判断会员举报权限冻结时间
                $new_time = gmtime();

                $report_time = Users::where('user_id', $user_id)->value('report_time');

                if ($report_time > $new_time) {
                    return show_message($GLOBALS['_LANG']['malice_report'], $GLOBALS['_LANG']['back_report_list'], 'user.php?act=illegal_report');
                }

                //判断是否重复举报 （举报，切状态为已处理的可以再次举报）
                $goods_report_count = GoodsReport::where('goods_id', $goods_id)->where('user_id', $user_id)->where('report_state', 0)->count();

                if ($goods_report_count > 0) {
                    return show_message($GLOBALS['_LANG']['repeat_report']);
                }

                $where = [
                    'goods_id' => $goods_id,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];
                $goods_info = $this->goodsService->getGoodsInfo($where);
                $goods_info['shop_name'] = $this->merchantCommonService->getShopName($goods_info['user_id'], 1);

                //下架商品不能举报
                if ($goods_info['is_on_sale'] == 0) {
                    return show_message($GLOBALS['_LANG']['offgoods_report']);
                }

                //获取投诉类型列表
                $report_type = get_goods_report_type();
                $report_title = [];
                if ($report_type) {
                    $report_title = get_goods_report_title($report_type[0]['type_id']);
                }
                $this->smarty->assign('sessid', SESS_ID);
                $this->smarty->assign('report_type', $report_type);
                $this->smarty->assign('report_title', $report_title);

                $where = [
                    'user_id' => $user_id,
                    'goods_id' => $goods_id,
                    'report_id' => 0
                ];
            } elseif ($report_id > 0) {
                //初始化数据
                $goods_report_info = [
                    'goods_id' => 0,
                    'goods_name' => '',
                    'goods_thumb' => ''
                ];

                $goods_report_info = GoodsReport::where('report_id', $report_id)->where('user_id', $user_id)->first();
                $goods_report_info = $goods_report_info ? $goods_report_info->toArray() : [];

                if ($goods_report_info) {
                    $reportType = GoodsReportType::select('type_name', 'type_desc')->where('type_id', $goods_report_info['type_id'])->first();
                    $reportType = $reportType ? $reportType->toArray() : [];

                    $goods_report_info = $reportType ? array_merge($goods_report_info, $reportType) : $goods_report_info;

                    $goods_report_info['title_name'] = GoodsReportTitle::where('title_id', $goods_report_info['title_id'])->value('title_name');
                }


                //商品赋值
                $goods_info['goods_id'] = $goods_report_info['goods_id'];
                $goods_info['goods_name'] = $goods_report_info['goods_name'];
                $goods_info['goods_thumb'] = get_image_path($goods_report_info['goods_image']);

                $seller_id = Goods::where('goods_id', $goods_report_info['goods_id'])->value('user_id');
                $seller_id = $seller_id ? $seller_id : 0;

                $goods_info['shop_name'] = $this->merchantCommonService->getShopName($seller_id, 1);

                $this->smarty->assign('goods_report_info', $goods_report_info);

                $where = [
                    'user_id' => $user_id,
                    'goods_id' => $goods_report_info['goods_id'],
                    'report_id' => $report_id
                ];
            }

            $goods_info['url'] = $this->dscRepository->buildUri('goods', ['gid' => $goods_info['goods_id']], $goods_info['goods_name']);

            //获取举报相册
            $img_list = $this->userService->ReportImagesList($where);

            //模板赋值
            $this->smarty->assign('report_id', $report_id);
            $this->smarty->assign('img_list', $img_list);
            $this->smarty->assign("goods_info", $goods_info);
            $this->smarty->assign("action", $action);
            $this->smarty->assign('page_title', $GLOBALS['_LANG']['report_goods']);  // 页面标题
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());     // 网店帮助
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 举报入库
        /* ------------------------------------------------------ */
        elseif ($action == 'goods_report_submit') {
            $goods_id = (int)request()->input('goods_id', 0);
            $goods_name = addslashes(trim(request()->input('goods_name', '')));
            $goods_image = addslashes(trim(request()->input('goods_image', '')));
            $title_id = (int)request()->input('title_id', 0);
            $type_id = (int)request()->input('type_id', 0);
            $inform_content = addslashes(trim(request()->input('inform_content', '')));

            if ($title_id == 0) {
                return show_message($GLOBALS['_LANG']['title_null']);
            } elseif ($type_id == 0) {
                return show_message($GLOBALS['_LANG']['type_null']);
            } elseif ($inform_content == '') {
                return show_message($GLOBALS['_LANG']['inform_content_null']);
            } else {
                $time = gmtime();
                //更新数据
                $other = [
                    'user_id' => $user_id,
                    'user_name' => session('user_name', ''),
                    'goods_id' => $goods_id,
                    'goods_name' => $goods_name,
                    'goods_image' => $goods_image,
                    'title_id' => $title_id,
                    'type_id' => $type_id,
                    'inform_content' => $inform_content,
                    'add_time' => $time,
                ];

                //入库处理
                $report_id = GoodsReport::insertGetId($other);

                //更新图片
                if ($report_id > 0) {
                    GoodsReportImg::where('user_id', $user_id)->where('goods_id', $goods_id)->where('report_id', 0)->update(['report_id' => $report_id]);
                }


                return show_message($GLOBALS['_LANG']['report_success'], $GLOBALS['_LANG']['back_report_list'], 'user.php?act=illegal_report');
            }
        }

    }
}
