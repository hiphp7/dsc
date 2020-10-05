<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Repositories\Common\CommonRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Payment\PaymentService;

/**
 * 支付响应页面
 *
 * Class RespondController
 * @package App\Http\Controllers
 */
class RespondController extends InitController
{
    protected $paymentService;
    protected $commonRepository;
    protected $articleCommonService;

    public function __construct(
        PaymentService $paymentService,
        CommonRepository $commonRepository,
        ArticleCommonService $articleCommonService
    )
    {
        $this->paymentService = $paymentService;
        $this->commonRepository = $commonRepository;
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        load_helper('payment');
        load_helper('order');
        /* 支付方式代码 */
        $pay_code = trim(request()->input('code', ''));

        $msg = '';
        /* 参数是否为空 */
        if (empty($pay_code)) {
            $msg = lang('payment.pay_not_exist');
        } else {
            /* 检查code里面有没有问号 */
            if (strpos($pay_code, '?') !== false) {
                $arr1 = explode('?', $pay_code);
                $arr2 = explode('=', $arr1[1]);

                $_REQUEST['code'] = $arr1[0];
                $_REQUEST[$arr2[0]] = $arr2[1];
                $_GET['code'] = $arr1[0];
                $_GET[$arr2[0]] = $arr2[1];
                $pay_code = $arr1[0];
            }

            /* 判断是否启用 */
            $count = Payment::where('pay_code', $pay_code)->where('enabled', 1)->count();

            if ($count == 0) {
                $msg = lang('payment.pay_disabled');
            } else {
                if ($pay_code && strpos($pay_code, 'pay_') === false) {
                    $payObject = $this->commonRepository->paymentInstance($pay_code);
                    /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
                    if (!is_null($payObject)) {
                        /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
                        $msg = $payObject->respond() ? lang('payment.pay_success') : lang('payment.pay_fail');
                    } else {
                        $msg = lang('payment.pay_not_exist');
                    }
                }
            }
        }

        assign_template();
        $position = assign_ur_here();
        $this->smarty->assign('page_title', $position['title']);   // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']); // 当前位置
        $this->smarty->assign('page_title', $position['title']);   // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']); // 当前位置
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());      // 网店帮助

        $categories_pro = get_category_tree_leve_one();
        $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

        $this->smarty->assign('message', $msg);
        $this->smarty->assign('shop_url', url('/') . '/');

        return $this->smarty->display('respond.dwt');
    }

    /**
     * 支付异步通知
     *
     * @param string $code
     * @return bool
     */
    public function notify($code = '')
    {
        $payment = $this->paymentService->getPayment($code);
        if ($payment === false) {
            return false;
        }

        return $payment->notify();
    }

    /**
     * 退款异步通知
     *
     * @param string $code
     * @return array|bool
     */
    public function notify_refound($code = '')
    {
        $payment = $this->paymentService->getPayment($code);
        if ($payment === false) {
            return false;
        }

        return $payment->notify_refound();
    }

}
