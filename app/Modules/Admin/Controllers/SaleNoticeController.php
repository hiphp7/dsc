<?php

namespace App\Modules\Admin\Controllers;

use App\Models\SaleNotice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Sale\SaleNoticeManageService;

/**
 * 降价通知管理程序
 */
class SaleNoticeController extends InitController
{
    protected $commonRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $baseRepository;
    protected $saleNoticeManageService;

    public function __construct(
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        SaleNoticeManageService $saleNoticeManageService
    )
    {
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->saleNoticeManageService = $saleNoticeManageService;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => 'sale_notice']);

        /*------------------------------------------------------ */
        //-- 获取列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('sale_notice');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sale_notice']);
            $this->smarty->assign('full_page', 1);

            $list = $this->saleNoticeManageService->saleNoticeList($adminru['ru_id']);

            foreach ($list['item'] as $key => $val) {
                $list['item'][$key]['goods_link'] = $this->dsc->url() . "goods.php?id=" . $val['goods_id'];
            }

            $this->smarty->assign('notice_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            return $this->smarty->display('sale_notice_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、搜索、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {

            $list = $this->saleNoticeManageService->saleNoticeList($adminru['ru_id']);

            if (!empty($list['item'])) {
                foreach ($list['item'] as $key => $val) {
                    $list['item'][$key]['goods_link'] = $this->dsc->url() . "goods.php?id=" . $val['goods_id'];
                }
            }

            $this->smarty->assign('notice_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('sale_notice_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- (通知详情)
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'view') {
            /* 检查权限 */
            admin_priv('sale_notice');

            $id = request()->input('id', 0);
            $send_ok = request()->input('send_ok', 0);

            $send_fail = !empty($send_ok) ? $send_ok : '';

            /* 获取评论详细信息并进行字符处理 */
            $res = SaleNotice::where('id', $id);
            $res = $res->with(['getUsers', 'getGoods']);
            $detail = $this->baseRepository->getToArrayFirst($res);

            if (!empty($detail)) {
                $detail['user_name'] = $detail['get_users']['user_name'] ?? '';
                $detail['goods_name'] = $detail['get_goods']['goods_name'] ?? '';

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $detail['user_name'] = $this->dscRepository->stringToStar($detail['user_name']);
                    $detail['cellphone'] = $this->dscRepository->stringToStar($detail['cellphone']);
                    $detail['email'] = $this->dscRepository->stringToStar($detail['email']);
                }

                $detail['user_name'] = htmlspecialchars($detail['user_name']);
                $detail['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $detail['add_time']);
            }

            /* 模板赋值 */
            $this->smarty->assign('detail', $detail);
            $this->smarty->assign('send_fail', $send_fail);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sale_notice']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['depreciate_notice_list'], 'href' => 'sale_notice.php?act=list']);

            /* 页面显示 */

            return $this->smarty->display('sale_notice_info.dwt');
        }
        /*------------------------------------------------------ */
        //-- 处理 降价通知
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'action') {

            admin_priv('sale_notice');

            $id = request()->input('id', 0);
            $mark = addslashes(request()->input('mark', ''));

            /* 获取详细信息 */
            $res = SaleNotice::where('id', $id);
            $res = $res->with(['getUsers', 'getGoods']);
            $detail = $this->baseRepository->getToArrayFirst($res);

            $detail['user_name'] = $detail['get_users']['user_name'] ?? '';
            $detail['goods_name'] = $detail['get_goods']['goods_name'] ?? '';
            $detail['shop_price'] = $detail['get_goods']['shop_price'] ?? 0;

            if (!empty($mark)) {
                $data = ['mark' => $mark];
                SaleNotice::where('id', $id)->update($data);
            }

            /* 邮件通知处理流程 */
            $email = request()->input('email', '');
            if (!empty($email) && !empty($detail['email'])) {

                /* 设置留言回复模板所需要的内容信息 */
                $template = get_mail_template('sale_notice');

                $this->smarty->assign('user_name', $_POST['user_name']);
                $this->smarty->assign('goods_name', $detail['goods_name']);
                $this->smarty->assign('goods_link', $this->dsc->url() . "goods.php?id=" . $detail['goods_id']);
                $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                $content = $this->smarty->fetch('str:' . $template['template_content']);
                $send_type = 1;
                /* 发送邮件 */
                if ($this->commonRepository->sendEmail($detail['user_name'], $detail['email'], $template['template_subject'], $content, $template['is_html'])) {
                    $data = [
                        'status' => 1,
                        'send_type' => 1
                    ];
                    SaleNotice::where('id', $id)->update($data);

                    $send_ok = 1;
                    notice_log($detail['goods_id'], $detail['email'], $send_ok, $send_type);
                } else {
                    $data = [
                        'status' => 3,
                        'send_type' => 1
                    ];
                    SaleNotice::where('id', $id)->update($data);

                    $send_ok = 0;
                    notice_log($detail['goods_id'], $detail['email'], $send_ok, $send_type);
                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['back_depreciate_list'], 'href' => 'sale_notice.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['send_fail'], 0, $link);
                }
            }

            /* 短信通知处理流程 */
            $resms = request()->input('resms', '');
            if (!empty($resms) && !empty($detail['cellphone']) && $GLOBALS['_CFG']['sms_price_notice'] == 1) {

                //阿里大鱼短信接口参数
                $smsParams = [
                    'user_name' => $detail['user_name'],
                    'username' => $detail['user_name'],
                    'goodsname' => $this->dscRepository->subStr($detail['goods_name'], 20),
                    'goodsprice' => $detail['shop_price'],
                    'mobile_phone' => $detail['cellphone'] ? $detail['cellphone'] : '',
                    'mobilephone' => $detail['cellphone'] ? $detail['cellphone'] : ''
                ];

                $res = $this->commonRepository->smsSend($detail['cellphone'], $smsParams, 'sms_price_notic', false);

                //记录日志
                $send_type = 2;
                if ($res === true) {
                    $data = [
                        'status' => 1,
                        'send_type' => 2
                    ];
                    SaleNotice::where('id', $id)->update($data);

                    $send_ok = 1;
                    notice_log($detail['goods_id'], $detail['cellphone'], $send_ok, $send_type);

                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['back_depreciate_list'], 'href' => 'sale_notice.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['send_success'], 0, $link);
                } else {
                    $data = [
                        'status' => 3,
                        'send_type' => 2
                    ];
                    SaleNotice::where('id', $id)->update($data);

                    $send_ok = 0;
                    notice_log($detail['goods_id'], $detail['cellphone'], $send_ok, $send_type);
                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['back_depreciate_list'], 'href' => 'sale_notice.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['send_fail'], 0, $link);
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 记录管理员操作 */
            admin_log($GLOBALS['_LANG']['handle_depreciate_notice'], 'edit', 'sale_notice');

            return dsc_header("Location: sale_notice.php?act=list");
        }

        /*------------------------------------------------------ */
        //-- 批量删除降价通知申请
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch') {
            admin_priv('sale_notice');

            $action = request()->input('sel_action', 'deny');

            $checkboxes = request()->input('checkboxes', '');
            if (!empty($checkboxes)) {
                switch ($action) {
                    case 'remove':
                        $checkboxes = $this->baseRepository->getExplode($checkboxes);
                        SaleNotice::whereIn('id', $checkboxes)->delete();
                        break;

                    default:
                        break;
                }

                clear_cache_files();
                $action = ($action == 'remove') ? 'remove' : 'edit';
                admin_log('', $action, 'adminlog');

                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'sale_notice.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($checkboxes)), 0, $link);
            } else {
                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'sale_notice.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['back_depreciate_list'], 0, $link);
            }
        }
    }
}
