<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\AccountLog;
use App\Models\MerchantsAccountLog;
use App\Models\SellerAccountLog;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantAccountManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心服务站管理
 */
class MerchantsAccountController extends InitController
{
    protected $merchantCommonService;
    protected $dscRepository;
    protected $baseRepository;
    protected $merchantAccountManageService;
    protected $storeCommonService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        MerchantAccountManageService $merchantAccountManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->merchantAccountManageService = $merchantAccountManageService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('order');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /* 检查权限 */
        admin_priv('seller_account');

        if (!isset($_REQUEST['act_type'])) {
            $_REQUEST['act_type'] = 'detail';
        }

        /*------------------------------------------------------ */
        //-- 账户管理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            if (isset($_REQUEST['ru_id'])) {
                $action_link = "&ru_id=" . $_REQUEST['ru_id'];
                $this->smarty->assign('ru_id', $_REQUEST['ru_id']);
            }
            $this->smarty->assign('action_link6', ['text' => $GLOBALS['_LANG']['fund_details'], 'href' => 'merchants_account.php?act=account_log_list']);
            $action_link = isset($action_link) ? $action_link : '';
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['presentation_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=4' . $action_link]);
            $this->smarty->assign('action_link1', ['text' => $GLOBALS['_LANG']['recharge_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=3' . $action_link]);
            $this->smarty->assign('action_link4', ['text' => $GLOBALS['_LANG']['settlement_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=2' . $action_link]);
            $this->smarty->assign('action_link5', ['text' => $GLOBALS['_LANG']['thawing_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=5' . $action_link]);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'merchants_account.php?act=list&act_type=account_log' . $action_link]);
            $this->smarty->assign('action_link3', ['text' => $GLOBALS['_LANG']['merchant_funds_list'], 'href' => 'merchants_account.php?act=list&act_type=merchants_seller_account' . $action_link]);
            $this->smarty->assign('full_page', 1);

            if ($_REQUEST['act_type'] == 'detail') {
                $log_type = isset($_REQUEST['log_type']) ? $_REQUEST['log_type'] : 4;
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']);
                $this->smarty->assign('log_type', $log_type);
                $list = get_account_log_list($adminru['ru_id'], [$log_type]);

                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'detail');


                return $this->smarty->display('merchants_detail.dwt');
            } elseif ($_REQUEST['act_type'] == 'account_log') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_seller_account_log']);
                $list = get_account_log_list($adminru['ru_id'], [1, 4, 5]);

                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'account_log');


                return $this->smarty->display('merchants_account_log.dwt');
            } elseif ($_REQUEST['act_type'] == 'merchants_seller_account') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['merchant_funds_list']);
                $list = $this->merchantAccountManageService->getMerchantsSellerAccount();
                $list['filter']['act_type'] = $_REQUEST['act_type'];
                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'merchants_seller_account');


                return $this->smarty->display('merchants_seller_account.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- ajax返回列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            if ($_REQUEST['act_type'] == 'detail') {
                $log_type = isset($_REQUEST['log_type']) ? $_REQUEST['log_type'] : 4;
                $this->smarty->assign('log_type', $log_type);
                $list = get_account_log_list($adminru['ru_id'], [$log_type]);
                $fetch = "merchants_detail";
            } elseif ($_REQUEST['act_type'] == 'account_log') {
                $list = get_account_log_list($adminru['ru_id'], [1, 4]);
                $fetch = "merchants_account_log";
            } elseif ($_REQUEST['act_type'] == 'merchants_seller_account') {
                $list = $this->merchantAccountManageService->getMerchantsSellerAccount();
                $list['filter']['act_type'] = $_REQUEST['act_type'];
                $fetch = "merchants_seller_account";
            }

            if ($_REQUEST['act_type'] == 'detail' || $_REQUEST['act_type'] == 'account_log' || $_REQUEST['act_type'] == 'merchants_seller_account') {
                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                $sort_flag = sort_flag($list['filter']);
                $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

                return make_json_result($this->smarty->fetch($fetch . '.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
            }
        }

        /*------------------------------------------------------ */
        //-- 查看
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check') {
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['04_seller_detail'], 'href' => 'merchants_account.php?act=list&act_type=detail']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'merchants_account.php?act=list&act_type=account_log']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['check']);
            $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;
            $act_type = isset($_REQUEST['act_type']) ? addslashes($_REQUEST['act_type']) : 0;

            $this->smarty->assign('log_id', $log_id);
            $this->smarty->assign('form_action', "update_check");

            $log_info = get_account_log_info($log_id);
            $this->smarty->assign('log_info', $log_info);
            $this->smarty->assign('act_type', $act_type);

            if ($log_info) {
                $seller_shopinfo = [
                    'seller_money' => $log_info['seller_money'],
                    'frozen_money' => $log_info['seller_frozen'],
                ];
            } else {
                $seller_shopinfo = [];
            }

            $this->smarty->assign('seller_shopinfo', $seller_shopinfo);

            $users_real = get_users_real($log_info['ru_id'], 1);
            $this->smarty->assign('real', $users_real);


            return $this->smarty->display('merchants_log_check.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查看
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_check') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            $log = [];
            $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;
            $log_reply = isset($_REQUEST['log_reply']) ? addslashes(trim($_REQUEST['log_reply'])) : 0;
            $log_status = isset($_REQUEST['log_status']) ? intval($_REQUEST['log_status']) : 0;
            $certificate_img = isset($_FILES['certificate_img']) ? $_FILES['certificate_img'] : [];
            $msg_type = 0;
            $log_info = get_account_log_info($log_id);

            if ($log_status > 0 && $log_status <= 2) {
                if ($log_info['log_type'] == 5) {
                    $log_type = 5;

                    if ($log_status == 1) {
                        /* 改变商家金额 */
                        SellerShopinfo::where('ru_id', $log_info['ru_id'])->increment('seller_money', $log_info['frozen_money']);

                        $handler = $GLOBALS['_LANG']['frozen_money_success'];

                        $log = [
                            'user_id' => $log_info['ru_id'],
                            'user_money' => $log_info['frozen_money'],
                            'change_time' => gmtime(),
                            'change_desc' => sprintf($GLOBALS['_LANG']['check_change_desc'], session('admin_name')),
                            'change_type' => 4
                        ];
                    } else {
                        $handler = $GLOBALS['_LANG']['frozen_money_failure'];

                        //商家资金变动
                        log_seller_account_change($log_info['ru_id'], 0, $log_info['frozen_money']);

                        //商家资金明细记录
                        merchants_account_log($log_info['ru_id'], 0, $log_info['frozen_money'], "【" . session('admin_name') . "】" . $GLOBALS['_LANG']['08_refuse_apply_for']);
                    }

                    $href = "merchants_account.php?act=list&act_type=account_log";
                    $text = $GLOBALS['_LANG']['05_seller_account_log'];

                    /* 改变商家资金记录 */
                    $data = [
                        'is_paid' => $log_status,
                        'admin_note' => $log_reply,
                        'log_type' => $log_type
                    ];
                    SellerAccountLog::where('log_id', $log_id)->update($data);
                } else {
                    if ($log_info['seller_frozen'] < $log_info['amount'] && $log_info['payment_info']['pay_code'] != 'bank') {
                        $handler = $GLOBALS['_LANG']['not_sufficient_funds'];
                        $msg_type = 1;
                        $text = $GLOBALS['_LANG']['go_back'];

                        if ($log_info['log_type'] == 3) {
                            $href = "merchants_account.php?act=check&log_id=" . $log_info['log_id'] . "&act_type=detail";
                        } elseif ($log_info['log_type'] == 1 || $log_info['log_type'] == 4) {
                            $href = "merchants_account.php?act=check&log_id=" . $log_info['log_id'] . "&act_type=account_log";
                        } else {
                            $href = "merchants_account.php?act=list&act_type=account_log";
                        }
                    } else {
                        $certificate = '';
                        if (isset($certificate_img['name']) && $certificate_img['name']) {
                            $certificate = $image->upload_image([], 'seller_account', '', 1, $certificate_img['name'], $certificate_img['type'], $certificate_img['tmp_name'], $certificate_img['error'], $certificate_img['size']);  //图片存放地址 -- data/seller_account
                            $this->dscRepository->getOssAddFile([$certificate]);
                        }

                        //银行转账
                        if (isset($log_info['payment_info']['pay_code']) && $log_info['payment_info']['pay_code'] == 'bank') {

                            /* 改变商家金额 */
                            SellerShopinfo::where('ru_id', $log_info['ru_id'])->increment('seller_money', $log_info['amount']);

                            $log_type = 3;
                            $handler = $GLOBALS['_LANG']['topup_account_ok'];
                            $href = "merchants_account.php?act=check&log_id=" . $log_id . "&act_type=detail";
                            $text = $GLOBALS['_LANG']['04_seller_detail'];
                            $log = [
                                'user_id' => $log_info['ru_id'],
                                'user_money' => $log_info['amount'],
                                'change_time' => gmtime(),
                                'change_desc' => sprintf($GLOBALS['_LANG']['07_seller_top_up'], session('admin_name')),
                                'change_type' => 1
                            ];
                        } else {

                            /* 转账至前台会员余额账户 start */
                            if ($log_info['deposit_mode'] == 1) {
                                /* 改变会员金额 */
                                Users::where('user_id', $log_info['ru_id'])->increment('user_money', $log_info['amount']);
                            }
                            /* 转账至前台会员余额账户 end */

                            /* 改变商家金额 */
                            SellerShopinfo::where('ru_id', $log_info['ru_id'])->decrement('frozen_money', $log_info['amount']);

                            $change_desc = sprintf($GLOBALS['_LANG']['06_seller_deposit'], session('admin_name'));

                            $log_type = 4;
                            $handler = $GLOBALS['_LANG']['deposit_account_ok'];
                            $href = "merchants_account.php?act=list&act_type=account_log";
                            $text = $GLOBALS['_LANG']['05_seller_account_log'];

                            /* 转账至前台会员余额账户 start */
                            if ($log_info['deposit_mode'] == 1) {
                                $user_account_log = [
                                    'user_id' => $log_info['ru_id'],
                                    'user_money' => "+" . $log_info['amount'],
                                    'change_desc' => $change_desc,
                                    'process_type' => 0,
                                    'payment' => '',
                                    'change_time' => gmtime(),
                                    'change_type' => 2,
                                ];

                                AccountLog::insert($user_account_log);
                            }
                            /* 转账至前台会员余额账户 end */

                            $log = [
                                'user_id' => $log_info['ru_id'],
                                'change_time' => gmtime(),
                                'change_desc' => $change_desc
                            ];

                            $log['frozen_money'] = "-" . $log_info['amount'];
                        }

                        /* 改变会员金额 */
                        $data = [
                            'is_paid' => $log_status,
                            'admin_note' => $log_reply,
                            'log_type' => $log_type
                        ];
                        if ($certificate) {
                            $data['certificate_img'] = $certificate;
                        }
                        SellerAccountLog::where('log_id', $log_id)->update($data);
                    }
                }
            } else {
                $handler = $GLOBALS['_LANG']['handler_failure'];
                $msg_type = 1;
                $text = $GLOBALS['_LANG']['go_back'];
                if ($log_info['payment_info']['pay_name'] == $GLOBALS['_LANG']['bank_remittance']) {
                    $href = "merchants_account.php?act=list";
                } else {
                    $href = "merchants_account.php?act=list&act_type=account_log";
                }
            }

            if (!empty($log)) {
                MerchantsAccountLog::insert($log);
            }

            $link[0] = ['href' => $href, 'text' => $text];
            return sys_msg($handler, $msg_type, $link);
        }
        /*------------------------------------------------------ */
        //-- 调节商家账户
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_seller') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['adjust_merchant_account']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['merchant_funds_list'], 'href' => 'merchants_account.php?act=list&act_type=merchants_seller_account']);

            $ru_id = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;

            $res = SellerShopinfo::where('ru_id', $ru_id);
            $res = $res->whereHas('MerchantsShopInformation');
            $seller_info = $this->baseRepository->getToArrayFirst($res);

            $seller_info['shop_name'] = $this->merchantCommonService->getShopName($ru_id, 1);
            $seller_info['formated_seller_money'] = price_format($seller_info['seller_money'], false);
            $seller_info['formated_frozen_money'] = price_format($seller_info['frozen_money'], false);
            $this->smarty->assign("seller_info", $seller_info);

            $sc_rand = rand(1000, 9999);
            $sc_guid = sc_guid();

            $seller_account_cookie = MD5($sc_guid . "-" . $sc_rand);
            cookie()->queue('seller_account_cookie', $seller_account_cookie, 60 * 24 * 30);

            $this->smarty->assign('sc_guid', $sc_guid);
            $this->smarty->assign('sc_rand', $sc_rand);

            return $this->smarty->display("seller_account_info.dwt");
        }

        /*------------------------------------------------------ */
        //-- 调节商家账户
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 检查参数 */
            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);

            if ($user_id <= 0) {
                return sys_msg('invalid param');
            }

            /* 提示信息 */
            $links = [
                ['href' => 'merchants_account.php?act=account_log_list&ru_id=' . $user_id, 'text' => $GLOBALS['_LANG']['account_list']],
                ['href' => 'merchants_account.php?act=edit_seller&ru_id=' . $user_id, 'text' => $GLOBALS['_LANG']['add_account']]
            ];

            /* 防止重复提交 start */
            $sc_rand = isset($_POST['sc_rand']) && !empty($_POST['sc_rand']) ? trim($_POST['sc_rand']) : '';
            $sc_guid = isset($_POST['sc_guid']) && !empty($_POST['sc_guid']) ? trim($_POST['sc_guid']) : '';

            $seller_account_cookie = MD5($sc_guid . "-" . $sc_rand);

            if (!empty($sc_guid) && !empty($sc_rand) && request()->hasCookie('seller_account_cookie')) {
                if (!empty(request()->cookie('seller_account_cookie'))) {
                    if (!(request()->cookie('seller_account_cookie') == $seller_account_cookie)) {
                        return sys_msg($GLOBALS['_LANG']['repeat_submit'], 0, $links);
                    }
                } else {
                    return sys_msg($GLOBALS['_LANG']['log_account_change_no'], 0, $links);
                }

                $res = SellerShopinfo::where('ru_id', $user_id);
                $seller_info = $this->baseRepository->getToArrayFirst($res);

                if (!$seller_info) {
                    return sys_msg($GLOBALS['_LANG']['user_not_exist']);
                }


                $money_status = intval($_POST['money_status']);
                $add_sub_user_money = floatval($_POST['add_sub_user_money']);  // 值：1（增加） 值：-1（减少）
                $add_sub_frozen_money = floatval($_POST['add_sub_frozen_money']); // 值：1（增加） 值：-1（减少）
                $change_desc = $this->dscRepository->subStr($_POST['change_desc'], 255, false);
                $user_money = isset($_POST['user_money']) && !empty($_POST['user_money']) ? $add_sub_user_money * abs(floatval($_POST['user_money'])) : 0;
                $frozen_money = isset($_POST['frozen_money']) && !empty($_POST['frozen_money']) ? $add_sub_frozen_money * abs(floatval($_POST['frozen_money'])) : 0;

                if ($money_status == 0 && abs($user_money) > $seller_info['seller_money'] && $add_sub_user_money < 0) {
                    return sys_msg($GLOBALS['_LANG']['money_status_prompt_one']);
                }
                if ($money_status == 1 && abs($frozen_money) > $seller_info['seller_money'] && $add_sub_frozen_money > 0) {
                    return sys_msg($GLOBALS['_LANG']['money_status_prompt_two']);
                } elseif ($money_status == 1 && abs($frozen_money) > $seller_info['frozen_money'] && $add_sub_frozen_money < 0) {
                    return sys_msg($GLOBALS['_LANG']['money_status_prompt_three']);
                }

                if ($user_money == 0 && $frozen_money == 0) {
                    return sys_msg($GLOBALS['_LANG']['no_account_change']);
                }


                if ($money_status == 1) {
                    if ($frozen_money > 0) {
                        $user_money = '-' . $frozen_money;
                    } else {
                        if (!empty($frozen_money) && !(strpos($frozen_money, "-") === false)) {
                            $user_money = substr($frozen_money, 1);
                        }
                    }
                }

                if ($seller_info) {
                    $user_money = get_return_money($user_money, $seller_info['seller_money']);
                    $frozen_money = get_return_money($frozen_money, $seller_info['frozen_money']);

                    if ($money_status == 1) {
                        if ($frozen_money == 0) {
                            $user_money = 0;
                        }
                    }
                }

                //更新商家资金
                log_seller_account_change($user_id, $user_money, $frozen_money);

                /* 记录明细 */
                $change_desc = sprintf($GLOBALS['_LANG']['seller_change_money'], session('admin_name')) . $change_desc;
                merchants_account_log($user_id, $user_money, $frozen_money, $change_desc, 3);

                //防止重复提交
                cookie()->queue('seller_account_cookie', '', 60 * 24 * 30);
            }
            /* 防止重复提交 end */

            return sys_msg($GLOBALS['_LANG']['merchant_funds_list'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 日志列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'account_log_list') {
            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']);
            $this->smarty->assign('action_link6', ['text' => $GLOBALS['_LANG']['fund_details'], 'href' => 'merchants_account.php?act=account_log_list']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['presentation_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=4']);
            $this->smarty->assign('action_link1', ['text' => $GLOBALS['_LANG']['recharge_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=3']);
            $this->smarty->assign('action_link4', ['text' => $GLOBALS['_LANG']['settlement_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=2']);
            $this->smarty->assign('action_link5', ['text' => $GLOBALS['_LANG']['thawing_record'], 'href' => 'merchants_account.php?act=list&act_type=detail&log_type=5']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'merchants_account.php?act=list&act_type=account_log']);
            $this->smarty->assign('action_link3', ['text' => $GLOBALS['_LANG']['merchant_funds_list'], 'href' => 'merchants_account.php?act=list&act_type=merchants_seller_account']);

            $this->smarty->assign('full_page', 1);
            $list = get_seller_account_log();
            $this->smarty->assign('log_list', $list['log_list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('act_type', 'account_log_list');


            return $this->smarty->display('account_log_list.dwt');
        } elseif ($_REQUEST['act'] == 'account_query') {
            $list = get_seller_account_log();
            $this->smarty->assign('log_list', $list['log_list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('act_type', 'account_log_list');


            return make_json_result($this->smarty->fetch('account_log_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 更新提现金额
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'change_amount') {
            $log_id = $_REQUEST['log_id'] ? intval($_REQUEST['log_id']) : 0;
            $result = [];
            $result['amount'] = !empty($_REQUEST['frozen_money']) ? floatval($_REQUEST['frozen_money']) : 0;

            $res = SellerAccountLog::where('log_id', $log_id)->update($result);
            if ($res > 0) {
                $result['error'] = 0;
            } else {
                $result['error'] = 1;
            }

            return response()->json($result);
        }
    }
}
