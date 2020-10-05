<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\SellerApplyInfo;
use App\Models\SellerGrade;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantsUpgradeManageService;
use Illuminate\Support\Str;

class MerchantsUpgradeController extends InitController
{
    protected $baseRepository;
    protected $merchantsUpgradeManageService;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantsUpgradeManageService $merchantsUpgradeManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantsUpgradeManageService = $merchantsUpgradeManageService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        load_helper('order');
        load_helper('payment');
        load_helper('clips');

        $adminru = get_admin_ru_id();
        get_invalid_apply();//过期申请失效处理
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_store_other');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_merchants_upgrade']);
            if ($adminru['ru_id'] > 0) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seller_upgrade_list'], 'href' => 'seller_apply.php?act=list&ru_id=' . $adminru['ru_id']]);
            }

            /*获取商家当前等级*/
            $seller_grader = get_seller_grade($adminru['ru_id']);
            $this->smarty->assign("grade_id", $seller_grader['grade_id']);

            $seller_garde = $this->merchantsUpgradeManageService->getPzdList();
            $this->smarty->assign('garde_list', $seller_garde['pzd_list']);
            $this->smarty->assign('filter', $seller_garde['filter']);
            $this->smarty->assign('record_count', $seller_garde['record_count']);
            $this->smarty->assign('page_count', $seller_garde['page_count']);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display("merchants_upgrade.htm");
        } elseif ($_REQUEST['act'] == 'query') {
            admin_priv('seller_store_other');

            /*获取商家当前等级*/
            $seller_grader = get_seller_grade($adminru['ru_id']);
            $this->smarty->assign("grade_id", $seller_grader['grade_id']);

            $seller_garde = $this->merchantsUpgradeManageService->getPzdList();
            $this->smarty->assign('garde_list', $seller_garde['pzd_list']);
            $this->smarty->assign('filter', $seller_garde['filter']);
            $this->smarty->assign('record_count', $seller_garde['record_count']);
            $this->smarty->assign('page_count', $seller_garde['page_count']);
            //跳转页面
            return make_json_result($this->smarty->fetch('merchants_upgrade.htm'), '', ['filter' => $seller_garde['filter'], 'page_count' => $seller_garde['page_count']]);
        } elseif ($_REQUEST['act'] == 'application_grade' || $_REQUEST['act'] == 'edit') {
            admin_priv('seller_store_other');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['application_grade']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['09_merchants_upgrade'], 'href' => 'merchants_upgrade.php?act=list']);
            $grade_id = !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : 0;
            $this->smarty->assign('grade_id', $grade_id);
            $this->smarty->assign('act', $_REQUEST['act']);

            if ($_REQUEST['act'] == 'edit') {
                $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;

                /*获取申请信息*/
                $res = SellerApplyInfo::where('apply_id', $apply_id);
                $seller_apply_info = $this->baseRepository->getToArrayFirst($res);
                $apply_criteria = unserialize($seller_apply_info['entry_criteria']);
                if ($seller_apply_info['pay_id'] > 0 && $seller_apply_info['is_paid'] == 0 && $seller_apply_info['pay_status'] == 0) {
                    load_helper('payment');
                    load_helper('clips');

                    /*在线支付按钮*/

                    //支付方式信息
                    $payment_info = payment_info($seller_apply_info['pay_id']);

                    //无效支付方式
                    if (empty($payment_info)) {
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
                            $payment_info['pay_fee'] = pay_fee($payment_info['pay_id'], $apply['surplus_amount'], 0);

                            //计算此次预付款需要支付的总金额
                            $apply['order_amount'] = $amount + $payment_info['pay_fee'];

                            if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                                /* 调用相应的支付方式文件 */
                                $pay_name = Str::studly($payment_info['pay_code']);
                                $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                                if (!is_null($pay_obj)) {
                                    /* 取得在线支付方式的支付按钮 */
                                    $seller_apply_info['pay_online'] = $pay_obj->get_code($apply, $payment);
                                }
                            }
                        }
                    }
                }
                $this->smarty->assign('apply_criteria', $apply_criteria);
                $this->smarty->assign('seller_apply_info', $seller_apply_info);
            } else {
                /*判断是否存在未支付未失效申请*/
                $res = SellerApplyInfo::where('ru_id', $adminru['ru_id'])
                    ->where('apply_status', '<>', 2)
                    ->where('is_paid', 0)
                    ->count();
                if ($res > 0) {
                    return sys_msg($GLOBALS['_LANG']['invalid_apply']);
                }
            }

            $seller_grade = get_seller_grade($adminru['ru_id']);    //获取商家等级
            if ($seller_grade) {
                $seller_grade['end_time'] = local_date('Y', $seller_grade['add_time']) + $seller_grade['year_num'] . '-' . local_date('m-d H:i:s', $seller_grade['add_time']);
                $seller_grade['addtime'] = local_date('Y-m-d H:i:s', $seller_grade['add_time']);

                /*如果是付费等级，根据剩余时间计算剩余价钱*/
                if ($seller_grade['amount'] > 0) {
                    $rest = (gmtime() - $seller_grade['add_time']) / (strtotime($seller_grade['end_time']) - $seller_grade['add_time']);//换算剩余时间比例
                    $seller_grade['refund_price'] = round($seller_grade['amount'] - $seller_grade['amount'] * $rest, 2);//按比例计算剩余金额
                }
                $this->smarty->assign('seller_grade', $seller_grade);
            }
            $entry_criteria = SellerGrade::where('id', $grade_id)->value('entry_criteria');
            $entry_criteria = $entry_criteria ? $entry_criteria : '';
            $entry_criteriat_info = $this->merchantsUpgradeManageService->getEntryCriteria($entry_criteria);//获取等级入驻标准
            $this->smarty->assign('entry_criteriat_info', $entry_criteriat_info);

            $pay = available_payment_list(0);//获取支付方式
            $this->smarty->assign("pay", $pay);

            return $this->smarty->display("merchants_application_grade.htm");
        } elseif ($_REQUEST['act'] == 'insert_submit' || $_REQUEST['act'] == 'update_submit') {
            admin_priv('seller_store_other');
            $grade_id = !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : 0;
            $pay_id = !empty($_REQUEST['pay_id']) ? intval($_REQUEST['pay_id']) : 0;
            $entry_criteria = !empty($_REQUEST['value']) ? $_REQUEST['value'] : [];
            $file_id = !empty($_REQUEST['file_id']) ? $_REQUEST['file_id'] : [];
            $fee_num = !empty($_REQUEST['fee_num']) ? intval($_REQUEST['fee_num']) : 1;
            $all_count_charge = !empty($_REQUEST['all_count_charge']) ? round($_REQUEST['all_count_charge'], 2) : 0.00;
            $refund_price = !empty($_REQUEST['refund_price']) ? $_REQUEST['refund_price'] : 0.00;
            $file_url = !empty($_REQUEST['file_url']) ? $_REQUEST['file_url'] : [];

            $apply_info = [];
            $back_price = 0.00;
            $payable_amount = 0.00;
            //计算此次预付款需要支付的总金额
            if ($refund_price > 0) {
                if ($GLOBALS['_CFG']['apply_options'] == 1) {
                    if ($refund_price > $all_count_charge) {
                        $payable_amount = 0.00;
                        $back_price = $refund_price - $all_count_charge;
                    } else {
                        $payable_amount = $all_count_charge - $refund_price;
                    }
                } elseif ($GLOBALS['_CFG']['apply_options'] == 2) {
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


            /*获取支付信息*/
            $payment_info = [];
            $payment_info = payment_info($pay_id);
            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($pay_id, $payable_amount, 0);
            $apply_info['order_amount'] = $payable_amount + $payment_info['pay_fee'];

            /*图片上传处理*/
            $php_maxsize = ini_get('upload_max_filesize');
            $htm_maxsize = '2M';

            /*验证图片*/
            if ($_FILES['value']) {
                foreach ($_FILES['value']['error'] as $key => $value) {
                    if ($value == 0) {
                        if (!$image->check_img_type($_FILES['value']['type'][$key])) {
                            $result['error'] = '1';
                            $result['massege'] = sprintf($GLOBALS['_LANG']['invalid_img_val'], $key + 1);
                        } else {
                            $goods_pre = 1;
                        }
                    } elseif ($value == 1) {
                        $result['error'] = '1';
                        $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $php_maxsize);
                    } elseif ($_FILES['img_url']['error'] == 2) {
                        $result['error'] = '1';
                        $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $htm_maxsize);
                    }
                }
                if ($goods_pre == 1) {
                    $res = $this->merchantsUpgradeManageService->uploadApplyFile($_FILES['value'], $file_id, $file_url);
                    if ($res != false) {
                        $img_url = $res;
                    }
                }
            }
            if ($img_url) {
                $valus = serialize($entry_criteria + $img_url);
            } else {
                $valus = serialize($entry_criteria);
            }
            if ($_REQUEST['act'] == 'insert_submit') {
                $apply_sn = get_order_sn(); //获取新订单号
                $time = gmtime();

                $data = [
                    'ru_id' => $adminru['ru_id'],
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
                $apply_id = SellerApplyInfo::insertGetId($data);

                $apply_info['log_id'] = insert_pay_log($apply_id, $apply_info['order_amount'], $type = PAY_APPLYGRADE, 0);
            } else {
                $apply_sn = !empty($_REQUEST['apply_sn']) ? $_REQUEST['apply_sn'] : 0;
                $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;

                $data = [
                    'payable_amount' => $payable_amount,
                    'back_price' => $back_price,
                    'total_amount' => $all_count_charge,
                    'pay_fee' => $payment_info['pay_fee'],
                    'fee_num' => $fee_num,
                    'entry_criteria' => $valus,
                    'pay_id' => $pay_id
                ];
                SellerApplyInfo::where('apply_id', $apply_id)
                    ->where('apply_sn', $apply_sn)
                    ->update($data);

                $apply_info['log_id'] = get_paylog_id($apply_id, $pay_type = PAY_APPLYGRADE);
            }
            /*支付按钮*/
            if ($pay_id > 0 && $payable_amount > 0) {
                $payment = unserialize_config($payment_info['pay_config']);
                $apply_info['order_sn'] = $apply_sn;
                $apply_info['user_id'] = $adminru['ru_id'];
                $apply_info['surplus_amount'] = $payable_amount;

                if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                    /* 调用相应的支付方式文件 */
                    $pay_name = Str::studly($payment_info['pay_code']);
                    $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                    if (!is_null($pay_obj)) {
                        /* 取得在线支付方式的支付按钮 */
                        $payment_info['pay_button'] = $pay_obj->get_code($apply_info, $payment);
                    }
                }

                $this->smarty->assign('payment', $payment_info);
                $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                $this->smarty->assign('amount', price_format($payable_amount, false));
                $this->smarty->assign('order', $apply_info);
                return $this->smarty->display('seller_done.htm');
            } else {
                return sys_msg($GLOBALS['_LANG']['success']);
            }
        }
    }
}
