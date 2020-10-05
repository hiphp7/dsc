<?php

namespace App\Modules\Admin\Controllers;

use App\Models\BookingGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Services\Goods\GoodsBookingManageService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 缺货处理管理程序
 */
class GoodsBookingController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $goodsBookingManageService;
    protected $commonRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GoodsBookingManageService $goodsBookingManageService,
        CommonRepository $commonRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->goodsBookingManageService = $goodsBookingManageService;
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {
        admin_priv('booking');
        /*------------------------------------------------------ */
        //-- 列出所有订购信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_all') {
            $seller_order = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['list_all']);
            $this->smarty->assign('full_page', 1);

            $list = $this->goodsBookingManageService->getBooKingList();

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            $ruCat = '';
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            //ecmoban模板堂 --zhuo end

            /* 订单内平台、店铺区分 */
            $this->smarty->assign('common_tabs', ['info' => $seller_order, 'url' => 'goods_booking.php?act=list_all']);
            $this->smarty->assign('booking_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('booking_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $list = $this->goodsBookingManageService->getBooKingList();

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            $ruCat = '';
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('booking_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('booking_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除缺货登记
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('booking');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            BookingGoods::where('rec_id', $id)->delete();
            $url = 'goods_booking.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 显示详情
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'detail') {
            $id = intval($_REQUEST['id']);

            $this->smarty->assign('send_fail', !empty($_REQUEST['send_ok']));
            $this->smarty->assign('booking', $this->goodsBookingManageService->getBooKingInfo($id));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['detail']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['06_undispose_booking'], 'href' => 'goods_booking.php?act=list_all']);
            return $this->smarty->display('booking_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理提交数据
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('booking');

            $rec_id = isset($_REQUEST['rec_id']) && !empty($_REQUEST['rec_id']) ? intval($_REQUEST['rec_id']) : 0;

            $dispose_note = !empty($_POST['dispose_note']) ? trim($_POST['dispose_note']) : '';

            $data = [
                'is_dispose' => 1,
                'dispose_note' => $dispose_note,
                'dispose_time' => gmtime(),
                'dispose_user' => session('admin_name')
            ];
            BookingGoods::where('rec_id', $rec_id)->update($data);

            $send_ok = 1;
            /* 邮件通知处理流程 */
            if (!empty($_POST['send_email_notice']) or isset($_POST['remail'])) {
                //获取邮件中的必要内容
                $res = BookingGoods::where('rec_id', $rec_id);
                $res = $res->with(['getGoods' => function ($query) {
                    $query->select('goods_id', 'goods_name');
                }]);
                $booking_info = $this->baseRepository->getToArrayFirst($res);
                if (isset($booking_info['get_goods']) && !empty($booking_info['get_goods'])) {
                    $booking_info['goods_name'] = $booking_info['get_goods']['goods_name'];
                } else {
                    $booking_info['goods_name'] = '';
                }

                /* 设置缺货回复模板所需要的内容信息 */
                $template = get_mail_template('goods_booking');
                $goods_link = $this->dsc->url() . 'goods.php?id=' . $booking_info['goods_id'];

                $this->smarty->assign('user_name', $booking_info['link_man']);
                $this->smarty->assign('goods_link', $goods_link);
                $this->smarty->assign('goods_name', $booking_info['goods_name']);
                $this->smarty->assign('dispose_note', $dispose_note);
                $this->smarty->assign('shop_name', "<a href='" . $this->dsc->url() . "'>" . $GLOBALS['_CFG']['shop_name'] . '</a>');
                $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                $content = $this->smarty->fetch('str:' . $template['template_content']);

                /* 发送邮件 */
                if ($this->commonRepository->sendEmail($booking_info['link_man'], $booking_info['email'], $template['template_subject'], $content, $template['is_html'])) {
                    $send_ok = 0;
                } else {
                    $send_ok = 1;
                }
            }

            return dsc_header("Location: goods_booking.php?act=detail&id=" . $rec_id . "&send_ok=$send_ok\n");
        }
    }
}
