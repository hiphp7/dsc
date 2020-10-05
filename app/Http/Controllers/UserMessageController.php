<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\CommentImg;
use App\Models\CommentSeller;
use App\Models\Feedback;
use App\Models\SellerShopinfo;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Comment\CommentService;
use App\Services\Common\CommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;
use App\Services\User\UserCommonService;
use App\Services\User\UserService;

class UserMessageController extends InitController
{
    protected $dscRepository;
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;
    protected $commonRepository;
    protected $commonService;
    protected $orderService;
    protected $userService;
    protected $merchantCommonService;
    protected $commentService;

    public function __construct(
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CommonRepository $commonRepository,
        CommonService $commonService,
        OrderService $orderService,
        UserService $userService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userCommonService = $userCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->articleCommonService = $articleCommonService;
        $this->commonRepository = $commonRepository;
        $this->commonService = $commonService;
        $this->orderService = $orderService;
        $this->userService = $userService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
    }

    public function index()
    {
        $this->dscRepository->helpersLang(['user']);

        $user_id = session('user_id', 0);

        $action = addslashes(trim(request()->input('act', 'default')));
        $action = $action ? $action : 'default';

        $not_login_arr = $this->userCommonService->notLoginArr('message');

        $ui_arr = $this->userCommonService->uiArr('message');

        /* 未登录处理 */
        $requireUser = $this->userCommonService->requireLogin(session('user_id'), $action, $not_login_arr, $ui_arr);
        $action = $requireUser['action'];
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
        } else {
            if (!in_array($action, $not_login_arr) || $user_id == 0) {
                $referer = '?back_act=' . urlencode(request()->server('REQUEST_URI'));
                $back_act = $this->dscRepository->dscUrl('user.php' . $referer);
                return dsc_header('location:' . $back_act);
            }
        }

        $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();
        $wholesaleUse = $this->commonService->judgeWholesaleUse(session('user_id'), 1);
        $wholesale_use = $supplierEnabled && $wholesaleUse ? 1 : 0;

        $this->smarty->assign('wholesale_use', $wholesale_use);

        /* ------------------------------------------------------ */
        //-- 显示留言列表
        /* ------------------------------------------------------ */
        if ($action == 'message_list') {
            load_helper('clips');

            $this->smarty->assign('user_info', $this->userCommonService->getUserDefault(session('user_id')));
            $this->smarty->assign('upload_size_limit', upload_size_limit(1));

            $is_order = (int)request()->input('is_order', 0);
            $page = (int)request()->input('page', 1);
            $order_id = (int)request()->input('order_id', 0);

            $order_info = [];

            /* 获取用户留言的数量 */
            if ($is_order) {
                $where = [
                    'order_id' => $order_id,
                    'user_id' => $user_id
                ];
                $order_info = $this->orderService->getOrderInfo($where);

                $order_info['url'] = 'user_order.php?act=order_detail&order_id=' . $order_id;
                $record_count = Feedback::where('parent_id', 0)->where('user_id', $user_id)->where('msg_type', 5)->count();
            } else {
                $record_count = Feedback::where('parent_id', 0)->where('user_id', $user_id)->where('order_id', 0)->where('msg_type', 5)->count();
            }

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_MESSAGE) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            //判断是否有订单留言
            $this->smarty->assign('is_have_order', $record_count);

            if ($is_order) {
                $act = ['act' => $action . '&is_order=1'];
            } else {
                $act = ['act' => $action];
            }

            if ($order_id != '') {
                $act['order_id'] = $order_id;
            }


            $pager = get_pager('user.php', $act, $record_count, $page, 5);
            $this->smarty->assign('is_order', $is_order);
            $message_list = get_message_list($user_id, $pager['size'], $pager['start'], $order_id, $is_order);
            $this->smarty->assign('message_list', $message_list);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('order_info', $order_info);
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 显示评论列表
        /* ------------------------------------------------------ */
        elseif ($action == 'comment_list') {
            load_helper('clips');
            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }
            //评论标识
            $sign = (int)request()->input('sign', 0);

            $page = (int)request()->input('page', 1);
            $size = 10;

            $where = [
                'user_id' => $user_id,
                'comment_id' => 0
            ];
            $img_list = $this->userService->getCommentImgList($where);

            if ($img_list) {
                foreach ($img_list as $key => $val) {
                    $this->dscRepository->getOssDelFile([$val['comment_img'], $val['img_thumb']]);
                    dsc_unlink(storage_public($val['comment_img']));
                    dsc_unlink(storage_public($val['img_thumb']));
                }
            }

            //剔除未保存晒单图
            CommentImg::where('user_id', $user_id)->where('comment_id', 0)->delete();

            $record_count = get_user_order_comment_count($user_id, $sign);

            if ($sign > 0) {
                $action = $action . "&sign=" . $sign;
            }

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page, $size);

            $comment_list = get_user_order_comment_list($user_id, $sign, 0, $size, $pager['start']);

            //评价条数
            $signNum0 = get_user_order_comment_count($user_id, 0);
            $signNum1 = get_user_order_comment_count($user_id, 1);
            $signNum2 = get_user_order_comment_count($user_id, 2);

            $this->smarty->assign('comment_list', $comment_list);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('sign', $sign);
            $this->smarty->assign('signNum0', $signNum0);
            $this->smarty->assign('signNum1', $signNum1);
            $this->smarty->assign('signNum2', $signNum2);
            $this->smarty->assign('sessid', SESS_ID);

            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 查看晒单--满意度调查
        /* ------------------------------------------------------ */
        elseif ($action == 'commented_view') {
            load_helper('clips');

            $order_id = (int)request()->input('order_id', 0);
            $sign = (int)request()->input('sign', 0);

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            //剔除评论
            CommentImg::where('user_id', $user_id)->where('comment_id', 0)->delete();

            //订单商品晒单列表
            $order_goods = get_user_order_comment_list($user_id, $sign, $order_id);

            //店家基本信息
            $ru_id = empty($order_goods[0]['ru_id']) ? 0 : $order_goods[0]['ru_id'];
            if ($ru_id) {
                $shop_information = $this->merchantCommonService->getShopName($ru_id);
                $shop_info = $shop_information;

                if ($shop_info['logo_thumb']) {
                    $shop_info['logo_thumb'] = substr($shop_info['logo_thumb'], 3);
                }
                $shop_info['logo_thumb'] = get_image_path($shop_info['logo_thumb']);
                //商家总和评分
                $shop_info['seller_score'] = 5;
                $seller_row = CommentSeller::selectRaw("SUM(service_rank) + SUM(desc_rank) + SUM(delivery_rank) + SUM(sender_rank) AS sum_rank, count(*) as num")
                    ->where('ru_id', $ru_id)
                    ->first();
                $seller_row = $seller_row ? $seller_row->toArray() : [];

                if ($seller_row && $seller_row['num']) {
                    $shop_info['seller_score'] = ($seller_row['sum_rank'] / $seller_row['num']) / 4;
                }

                $shop_info['shop_name'] = $this->merchantCommonService->getShopName($shop_info['ru_id'], 1);

                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($ru_id); //商家所有商品评分类型汇总
                $build_uri = [
                    'urid' => $ru_id,
                    'append' => $shop_info['shop_name']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($ru_id, $build_uri);
                $merchants_goods_comment['store_url'] = $domain_url['domain_name'];
                /*  @author-bylu 判断当前商家是否允许"在线客服" start */


                //判断当前商家是平台,还是入驻商家 bylu
                if ($ru_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                    if ($kf_im_switch) {
                        $shop_information['is_dsc'] = true;
                    } else {
                        $shop_information['is_dsc'] = false;
                    }
                } else {
                    $shop_information['is_dsc'] = false;
                }
                $this->smarty->assign('shop_information', $shop_information);
                $this->smarty->assign('merch_cmt', $merchants_goods_comment);
                $this->smarty->assign('shop_info', $shop_info);
            }

            //用户此订单是否对商家提交满意度
            $degree_count = CommentSeller::where('order_id', $order_id)->where('user_id', $user_id)->count();

            $this->smarty->assign('order_goods', $order_goods);
            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('degree_count', $degree_count);

            $this->smarty->assign('sessid', SESS_ID);
            $this->smarty->assign('sign', $sign);
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 删除留言
        /* ------------------------------------------------------ */
        elseif ($action == 'del_msg') {
            $id = (int)request()->input('id', 0);

            $is_order = (int)request()->input('is_order', 0);
            $order_id = (int)request()->input('order_id', 0);

            if ($id > 0) {
                $row = Feedback::where('msg_id', $id)->first();
                $row = $row ? $row->toArray() : [];

                if ($row && $row['user_id'] == $user_id) {
                    /* 验证通过，删除留言，回复，及相应文件 */
                    if ($row['message_img']) {
                        $filename = storage_public(DATA_DIR . '/feedbackimg/' . $row['message_img']);
                        dsc_unlink($filename);
                    }

                    Feedback::where('msg_id', $id)->orWhere('parent_id', $id)->delete();
                }
            }
            if ($is_order) {
                return dsc_header("Location: user_message.php?act=message_list&is_order=1&order_id=$order_id\n");
            } else {
                return dsc_header("Location: user_message.php?act=message_list&order_id=$order_id\n");
            }
        }

    }

}
