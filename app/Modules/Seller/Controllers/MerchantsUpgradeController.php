<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Image;
use App\Repositories\Common\DscRepository;
use App\Services\Wholesale\SuppliersService;
use Illuminate\Support\Str;

class MerchantsUpgradeController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        load_helper('order');
        load_helper('payment');
        load_helper('clips');

        $adminru = get_admin_ru_id();
        $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '09_merchants_upgrade']);
        get_invalid_apply();//过期申请失效处理
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);

        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_apply');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_merchants_upgrade']);
            if ($adminru['ru_id'] > 0) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seller_upgrade_list'], 'href' => 'seller_apply.php?act=list&ru_id=' . $adminru['ru_id'], 'class' => 'icon-book']);
            }
            /*获取商家当前等级*/
            $seller_grader = get_seller_grade($adminru['ru_id']);
            $grade_id = $seller_grader['grade_id'] ?? 0;
            $this->smarty->assign("grade_id", $grade_id);

            //判断是否到期
            $this->smarty->assign('is_expiry', judge_seller_grade_expiry($adminru['ru_id']));

            $seller_garde = $this->get_pzd_list();
            $this->smarty->assign('garde_list', $seller_garde['pzd_list']);
            $this->smarty->assign('filter', $seller_garde['filter']);
            $this->smarty->assign('record_count', $seller_garde['record_count']);
            $this->smarty->assign('page_count', $seller_garde['page_count']);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display("merchants_upgrade.dwt");
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            admin_priv('seller_apply');
            /*获取商家当前等级*/
            $seller_grader = get_seller_grade($adminru['ru_id']);
            $grade_id = $seller_grader['grade_id'] ?? 0;
            $this->smarty->assign("grade_id", $grade_id);

            $seller_garde = $this->get_pzd_list();
            $this->smarty->assign('garde_list', $seller_garde['pzd_list']);
            $this->smarty->assign('filter', $seller_garde['filter']);
            $this->smarty->assign('record_count', $seller_garde['record_count']);
            $this->smarty->assign('page_count', $seller_garde['page_count']);
            //跳转页面
            return make_json_result($this->smarty->fetch('merchants_upgrade.dwt'), '', ['filter' => $seller_garde['filter'], 'page_count' => $seller_garde['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加或编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'application_grade' || $_REQUEST['act'] == 'edit') {
            admin_priv('seller_apply');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['application_grade']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['09_merchants_upgrade'], 'href' => 'merchants_upgrade.php?act=list']);
            $grade_id = !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : 0;

            $this->smarty->assign('grade_id', $grade_id);
            $this->smarty->assign('act', $_REQUEST['act']);

            if ($_REQUEST['act'] == 'edit') {
                $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;

                /*获取申请信息*/
                $seller_apply_info = $this->db->getRow("SELECT * FROM" . $this->dsc->table('seller_apply_info') . " WHERE apply_id = '$apply_id' LIMIT 1");
                $apply_criteria = unserialize($seller_apply_info['entry_criteria']);
                if ($seller_apply_info['pay_id'] > 0 && $seller_apply_info['is_paid'] == 0 && $seller_apply_info['pay_status'] == 0) {
                    load_helper('payment');
                    load_helper('clips');

                    /*在线支付按钮*/

                    //支付方式信息
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

                            $apply['log_id'] = get_paylog_id($seller_apply_info['apply_id'], $pay_type = PAY_APPLYGRADE);
                            $amount = $seller_apply_info['total_amount'];
                            $apply['order_sn'] = $seller_apply_info['apply_sn'];
                            $apply['user_id'] = $seller_apply_info['ru_id'];
                            $apply['surplus_amount'] = $amount;
                            //计算支付手续费用
                            $payment_info['pay_fee'] = pay_fee($seller_apply_info['pay_id'], $apply['surplus_amount'], 0);
                            //计算此次预付款需要支付的总金额
                            $apply['order_amount'] = $amount + $payment_info['pay_fee'];
                            /* 调用相应的支付方式文件 */
                            include_once(plugin_path('Payment/' . ucfirst($payment_info['pay_code']) . '/' . ucfirst($payment_info['pay_code']) . '.php'));

                            /* 取得在线支付方式的支付按钮 */

                            if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                                /* 取得在线支付方式的支付按钮 */
                                $pay_name = Str::studly($payment_info['pay_code']);
                                $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                                if (!is_null($pay_obj)) {
                                    $seller_apply_info['pay_online'] = $pay_obj->get_code($apply, unserialize_config($payment_info['pay_config']));
                                }
                            }


                        }
                    }
                }
                foreach ($apply_criteria as $k => $v) {
                    if (stripos($v, 'images') === 0) {
                        $apply_criteria[$k] = $this->dscRepository->getImagePath($v);
                    }
                }
                $this->smarty->assign('apply_criteria', $apply_criteria);
                $this->smarty->assign('seller_apply_info', $seller_apply_info);
            } else {
                /*判断是否存在未支付未失效申请*/
                $sql = "SELECT apply_id FROM " . $this->dsc->table('seller_apply_info') . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND apply_status = 0 AND is_paid = 0 LIMIT 1";
                if ($this->db->getRow($sql)) {
                    return sys_msg($GLOBALS['_LANG']['invalid_apply']);
                }
            }

            $seller_grade = get_seller_grade($adminru['ru_id']);    //获取商家等级
            if ($seller_grade) {
                $seller_grade['end_time'] = local_date('Y', $seller_grade['add_time']) + $seller_grade['year_num'] . '-' . date('m-d H:i:s', $seller_grade['add_time']);
                $seller_grade['addtime'] = local_date('Y-m-d H:i:s', $seller_grade['add_time']);

                /*如果是付费等级，根据剩余时间计算剩余价钱*/
                if ($seller_grade['amount'] > 0) {
                    $rest = (gmtime() - $seller_grade['add_time']) / (strtotime($seller_grade['end_time']) - $seller_grade['add_time']);//换算剩余时间比例
                    $seller_grade['refund_price'] = round($seller_grade['amount'] - $seller_grade['amount'] * $rest, 2);//按比例计算剩余金额
                }
                $this->smarty->assign('seller_grade', $seller_grade);
            }
            $grade_info = $this->db->getRow("SELECT entry_criteria,grade_name FROM " . $this->dsc->table('seller_grade') . " WHERE id = '$grade_id'");
            $entry_criteriat_info = $this->get_entry_criteria($grade_info['entry_criteria']);//获取等级入驻标准
            $this->smarty->assign('entry_criteriat_info', $entry_criteriat_info);
            $this->smarty->assign("grade_name", $grade_info['grade_name']);

            $pay = available_payment_list(0);//获取支付方式
            $this->smarty->assign("pay", $pay);
            $this->smarty->assign("action", $_REQUEST['act']);

            //防止重复提交
            session()->forget('grade_reload.' . session('user_id'));

            set_prevent_token("grade_cookie");

            return $this->smarty->display("merchants_application_grade.dwt");
        }

        /*------------------------------------------------------ */
        //-- 提交、更新
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_submit' || $_REQUEST['act'] == 'update_submit') {
            admin_priv('seller_apply');

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
            $payment_info = payment_info($pay_id);
            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($pay_id, $payable_amount, 0);
            $apply_info['order_amount'] = $payable_amount + $payment_info['pay_fee'];

            /*图片上传处理*/
            $php_maxsize = ini_get('upload_max_filesize');
            $htm_maxsize = '2M';
            $img_url = [];
            /*验证图片*/
            if ($_FILES['value']) {
                foreach ($_FILES['value']['error'] as $key => $value) {
                    if ($value == 0) {
                        if (!$image->check_img_type($_FILES['value']['type'][$key])) {
                            return sys_msg(sprintf($GLOBALS['_LANG']['invalid_img_val'], $key + 1), 1);
                        } else {
                            $goods_pre = 1;
                        }
                    } elseif ($value == 1) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $php_maxsize), 1);
                    } elseif ($_FILES['img_url']['error'] == 2) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $htm_maxsize), 1);
                    }
                }
                if ($goods_pre == 1) {
                    $res = $this->upload_apply_file($_FILES['value'], $file_id, $file_url);
                    if ($res != false) {
                        $img_url = $res;
                    }
                } else {
                    $img_url = $file_url;
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

                $key = "(`ru_id`,`grade_id`,`apply_sn`,`total_amount`,`pay_fee`,`fee_num`,`entry_criteria`,`add_time`,`pay_id`,`refund_price`,`back_price`,`payable_amount`)";
                $value = "('" . $adminru['ru_id'] . "','" . $grade_id . "','" . $apply_sn . "','" . $all_count_charge . "','" . $payment_info['pay_fee'] . "','" . $fee_num . "','" . $valus . "','" . $time . "','" . $pay_id . "','" . $refund_price . "','$back_price','$payable_amount')";
                $sql = 'INSERT INTO' . $this->dsc->table("seller_apply_info") . $key . " VALUES" . $value;
                $this->db->query($sql);
                $apply_id = $this->db->insert_id();
                $apply_info['log_id'] = insert_pay_log($apply_id, $apply_info['order_amount'], $type = PAY_APPLYGRADE, 0);
            } else {
                $apply_sn = !empty($_REQUEST['apply_sn']) ? $_REQUEST['apply_sn'] : 0;
                $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;

                //判断订单是否已支付
                if ($_REQUEST['act'] == 'update_submit') {
                    $sql = "SELECT pay_status FROM" . $this->dsc->table("seller_apply_info") . "WHERE apply_id = '$apply_id' limit 1";
                    if ($this->db->getOne($sql) == 1) {
                        return show_message($GLOBALS['_LANG']['apply_complete_pay_cant_operate']);
                    }
                }

                $sql = "UPDATE" . $this->dsc->table('seller_apply_info') . " SET payable_amount = '$payable_amount', back_price = '$back_price', total_amount = '$all_count_charge',pay_fee='$payment_info[pay_fee]',fee_num = '$fee_num',entry_criteria='$valus',pay_id='$pay_id' WHERE apply_id = '$apply_id' AND apply_sn = '$apply_sn'";
                $this->db->query($sql);

                $apply_info['log_id'] = get_paylog_id($apply_id, $pay_type = PAY_APPLYGRADE);
            }
            /*支付按钮*/
            if ($pay_id > 0 && $payable_amount > 0) {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['grade_done']);
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['application_grade'], 'href' => 'merchants_upgrade.php?act=list']);
                $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                $this->smarty->assign('amount', price_format($payable_amount, false));
                $payment = unserialize_config($payment_info['pay_config']);
                $apply_info['order_sn'] = $apply_sn;
                $apply_info['user_id'] = $adminru['ru_id'];
                $apply_info['surplus_amount'] = $payable_amount;

                if ($payment_info) {
                    if ($payment_info['pay_code'] == 'balance') {
                        //查询出当前用户的剩余余额;
                        $user_money = $this->db->getOne("SELECT user_money FROM " . $this->dsc->table('users') . " WHERE user_id='" . $adminru['ru_id'] . "'");
                        //如果用户余额足够支付订单;
                        if ($user_money > $payable_amount) {
                            /*修改申请的支付状态 */
                            $sql = " UPDATE " . $this->dsc->table('seller_apply_info') . " SET is_paid = 1 ,pay_time = '" . gmtime() . "' ,pay_status = 1 WHERE apply_id= '" . $apply_id . "'";
                            $this->db->query($sql);

                            //记录支付log
                            $sql = "UPDATE " . $this->dsc->table('pay_log') . "SET is_paid = 1 WHERE order_id = '" . $apply_id . "' AND order_type = '" . PAY_APPLYGRADE . "'";
                            $this->db->query($sql);
                            log_account_change($adminru['ru_id'], $payable_amount * (-1), 0, 0, 0, $GLOBALS['_LANG']['record_id'] . $apply_sn . $GLOBALS['_LANG']['seller_level_apply_pay']);
                        } else {
                            return sys_msg($GLOBALS['_LANG']['balance_no_enough_select_other_payment']);
                        }
                    } else {
                        if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                            /* 调用相应的支付方式文件 */
                            $pay_name = Str::studly($payment_info['pay_code']);
                            $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                            if (!is_null($pay_obj)) {


                                /* 取得在线支付方式的支付按钮 */
                                $payment_info['pay_button'] = $pay_obj->get_code($apply_info, $payment);
                            }
                        }
                    }
                }

                $this->smarty->assign('payment', $payment_info);
                $this->smarty->assign('order', $apply_info);
                $this->smarty->assign('grade_type', 1);
                $this->smarty->assign('apply_id', $apply_id);

                $grade_reload['apply_id'] = $apply_id;

                session()->put('grade_reload.' . $adminru['ru_id'], $grade_reload);

                set_prevent_token("grade_cookie");

                return $this->smarty->display('seller_done.dwt');
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['return_apply_list'], 'href' => 'merchants_upgrade.php?act=list'];

                return sys_msg($GLOBALS['_LANG']['success'], '', $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 微信支付改变状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'checkorder') {
            $apply_id = isset($_GET['apply_id']) ? intval($_GET['apply_id']) : 0;
            $sql = "SELECT pay_status, pay_id FROM " . $this->dsc->table('seller_apply_info') . " WHERE apply_id = '$apply_id' LIMIT 1";
            $order_info = $this->db->getRow($sql);
            //已付款
            if ($order_info && $order_info['pay_status'] == 1) {
                $json = ['code' => 1];
                return response()->json($json);
            } else {
                $json = ['code' => 0];
                return response()->json($json);
            }
        }

        /*------------------------------------------------------ */
        //-- 会员等级刷新重复提交
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'grade_load') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['grade_done']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['application_grade'], 'href' => 'merchants_upgrade.php?act=list']);

            $apply_id = session()->get('grade_reload.' . $adminru['ru_id'] . '.apply_id');

            if ($apply_id > 0) {
                $sql = "SELECT apply_sn,pay_fee,pay_id,payable_amount FROM " . $this->dsc->table('seller_apply_info')
                    . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND apply_id = '$apply_id'";
                $seller_apply_info = $this->db->getRow($sql);
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
                        $apply_info['user_id'] = $adminru['ru_id'];
                        $apply_info['surplus_amount'] = $seller_apply_info['payable_amount'];
                        if ($payment_info['pay_code'] == 'balance') {
                            //查询出当前用户的剩余余额;
                            $user_money = $this->db->getOne("SELECT user_money FROM " . $this->dsc->table('users') . " WHERE user_id='" . $adminru['ru_id'] . "'");
                            //如果用户余额足够支付订单;
                            if ($user_money > $seller_apply_info['payable_amount']) {
                                /* 修改申请的支付状态 */
                                $sql = " UPDATE " . $this->dsc->table('seller_apply_info') . " SET is_paid = 1 ,pay_time = '" . gmtime() . "' ,pay_status = 1 WHERE apply_id= '" . $apply_id . "'";
                                $this->db->query($sql);

                                //记录支付log
                                $sql = "UPDATE " . $this->dsc->table('pay_log') . "SET is_paid = 1 WHERE order_id = '" . $apply_id . "' AND order_type = '" . PAY_APPLYGRADE . "'";
                                $this->db->query($sql);

                                log_account_change($adminru['ru_id'], $seller_apply_info['payable_amount'] * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['seller_apply'], $seller_apply_info['apply_sn']));
                            } else {
                                $links[] = ['text' => $GLOBALS['_LANG']['return_apply_list'], 'href' => 'merchants_upgrade.php?act=list'];

                                return sys_msg($GLOBALS['_LANG']['balance_insufficient'], '', $links);
                            }
                        } else {
                            /* 调用相应的支付方式文件 */
                            include_once(plugin_path('Payment/' . ucfirst($payment_info['pay_code']) . '/' . ucfirst($payment_info['pay_code']) . '.php'));
                            /* 取得在线支付方式的支付按钮 */
                            $pay_obj = new $payment_info['pay_code'];
                            $payment_info['pay_button'] = $pay_obj->get_code($apply_info, $payment);
                        }

                        $this->smarty->assign('payment', $payment_info);
                        $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                        $this->smarty->assign('amount', price_format($seller_apply_info['payable_amount'], false));
                        $this->smarty->assign('order', $apply_info);

                        return $this->smarty->display('seller_done.dwt');
                    } else {
                        $links[] = ['text' => $GLOBALS['_LANG']['return_apply_list'], 'href' => 'merchants_upgrade.php?act=list'];

                        return sys_msg($GLOBALS['_LANG']['success'], '', $links);
                    }
                } else {
                    $links[] = ['text' => $GLOBALS['_LANG']['return_apply_list'], 'href' => 'merchants_upgrade.php?act=list'];

                    return sys_msg($GLOBALS['_LANG']['system_error_wait_retry'], '', $links);
                }
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['return_apply_list'], 'href' => 'merchants_upgrade.php?act=list'];

                return sys_msg($GLOBALS['_LANG']['system_error_wait_retry'], '', $links);
            }
        }

        /* ------------------------------------------------------ */
        //-- 供应商入驻
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'suppliers_apply') {
            /* 权限判断 */
            admin_priv('supplier_apply');

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_apply_suppliers']);
            $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '12_apply_suppliers']);

            $suppliers = app(SuppliersService::class)->suppliersInfo($adminru['ru_id']);

            if (!empty($suppliers)) {
                $this->smarty->assign('supplier', $suppliers);
                $this->smarty->assign('form_action', 'update');

                $region_level = get_region_level($suppliers['region_id']);
                $region_level[0] = $region_level[0] ?? 1;
                $region_level[1] = $region_level[1] ?? 0;
                $region_level[2] = $region_level[2] ?? 0;

                $country_list = $this->get_regions_log(0, 0);
                $province_list = $this->get_regions_log(1, $region_level[0]);
                $city_list = $this->get_regions_log(2, $region_level[1]);
                $district_list = $this->get_regions_log(3, $region_level[2]);
                $this->smarty->assign('region_level', $region_level);
                $this->smarty->assign('countries', $country_list);
                $this->smarty->assign('provinces', $province_list);
                $this->smarty->assign('cities', $city_list);
                $this->smarty->assign('districts', $district_list);
            } else {
                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->smarty->assign('countries', get_regions());
                $this->smarty->assign('provinces', get_regions(1, 1));
                $this->smarty->assign('cities', get_regions(2, 2));
                $this->smarty->assign('districts', get_regions(3, 3));

                $this->smarty->assign('form_action', 'insert');
            }

            return $this->smarty->display('suppliers_apply.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 供应商入驻信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'supplier_info') {

            // 保存供应商入驻信息
            $action = addslashes(trim($_POST['form_action']));
            $supplier_info['user_id'] = $user_id = $adminru['ru_id'];
            $supplier_info['suppliers_name'] = addslashes(trim($_POST['suppliers_name']));
            $supplier_info['suppliers_desc'] = !empty($_POST['suppliers_desc']) ? addslashes(trim($_POST['suppliers_desc'])) : '';
            $supplier_info['real_name'] = addslashes(trim($_POST['real_name']));
            $supplier_info['mobile_phone'] = addslashes(trim($_POST['mobile_phone']));
            $supplier_info['email'] = addslashes(trim($_POST['email']));
            $supplier_info['self_num'] = addslashes(trim($_POST['self_num'])); // 身份证号
            $supplier_info['company_name'] = addslashes(trim($_POST['company_name']));
            $supplier_info['company_address'] = addslashes(trim($_POST['company_address']));
            $supplier_info['mobile_phone'] = addslashes(trim($_POST['mobile_phone']));
            $supplier_info['kf_qq'] = empty($_POST['kf_qq']) ? '' : addslashes(trim($_POST['kf_qq']));
            $supplier_info['region_id'] = addslashes(trim($_POST['district']));

            $supplier_info['add_time'] = gmtime();
            $supplier_info['review_status'] = 1;

            $textfile_zheng = addslashes(trim($_POST['front_textfile']));
            $textfile_fan = addslashes(trim($_POST['reverse_textfile']));
            $textfile_logo = addslashes(trim($_POST['logo_textfile']));

            /* 身份证正反面 */
            if (empty($_FILES['front_of_id_card']['size'])) {
                $supplier_info['front_of_id_card'] = str_replace("../", "", $textfile_zheng);
            } else {
                $supplier_info['front_of_id_card'] = $image->upload_image($_FILES['front_of_id_card'], 'idcard');
                $this->dscRepository->getOssAddFile([$supplier_info['front_of_id_card']]);
            }

            if (empty($_FILES['reverse_of_id_card']['size'])) {
                $supplier_info['reverse_of_id_card'] = str_replace("../", "", $textfile_fan);
            } else {
                $supplier_info['reverse_of_id_card'] = $image->upload_image($_FILES['reverse_of_id_card'], 'idcard');
                $this->dscRepository->getOssAddFile([$supplier_info['reverse_of_id_card']]);
            }

            /* 供应商LOGO */
            if (empty($_FILES['suppliers_logo']['size'])) {
                $supplier_info['suppliers_logo'] = str_replace("../", "", $textfile_logo);
            } else {
                $supplier_info['suppliers_logo'] = $image->upload_image($_FILES['suppliers_logo'], 'idcard');
                $this->dscRepository->getOssAddFile([$supplier_info['suppliers_logo']]);
            }

            $count_suppliers = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('suppliers') . " WHERE user_id = '$user_id' ");
            if ($count_suppliers && $action == 'update') {
                if ($this->db->autoExecute($this->dsc->table('suppliers'), $supplier_info, 'UPDATE', "user_id='$user_id' ")) {
                    $link[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'merchants_upgrade.php?act=suppliers_apply'];
                    return sys_msg($GLOBALS['_LANG']['info_edit_success_wait'], 0, $link);
                }
            } elseif ($action == 'insert') {
                if ($this->db->autoExecute($this->dsc->table('suppliers'), $supplier_info, 'INSERT')) {
                    $link[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'merchants_upgrade.php?act=suppliers_apply'];
                    return sys_msg($GLOBALS['_LANG']['apply_success_wait'], 0, $link);
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['attradd_failed'], 1);
            }
        }
    }

    /*分页*/
    private function get_pzd_list()
    {
        $result = get_filter();
        if ($result === false) {
            $filter['keywords'] = isset($_REQUEST['keywords']) ? $_REQUEST['keywords'] : '';

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('seller_grade') . " WHERE is_open = 1";
            $filter['record_count'] = $this->db->getOne($sql);
            $filter = page_and_size($filter);
            /* 获活动数据 */
            $sql = "SELECT * FROM" . $this->dsc->table('seller_grade') . " WHERE is_open = 1  ORDER BY id ASC LIMIT " . $filter['start'] . "," . $filter['page_size'];
            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $row = $this->db->getAll($sql);
        foreach ($row as $k => $v) {
            if ($v['entry_criteria']) {
                $entry_criteria = unserialize($v['entry_criteria']);
                $criteria = '';
                foreach ($entry_criteria as $key => $val) {
                    $sql = "SELECT criteria_name FROM" . $this->dsc->table('entry_criteria') . " WHERE id = '" . $val . "'";
                    $criteria_name = $this->db->getOne($sql);
                    if ($criteria_name) {
                        $entry_criteria[$key] = $criteria_name;
                    }
                }

                $row[$k]['entry_criteria'] = implode(" , ", $entry_criteria);
                $row[$k]['grade_img'] = get_image_path($v['grade_img']);
            }
        }

        $arr = ['pzd_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }


    /*获取申请等级的入驻标准*/
    private function get_entry_criteria($entry_criteria = '')
    {
        $entry_criteria = unserialize($entry_criteria);//反序列化等级入驻标准
        $rel = [];
        if (!empty($entry_criteria)) {
            $sql = " SELECT id,criteria_name FROM" . $this->dsc->table('entry_criteria') . " WHERE id " . db_create_in($entry_criteria);
            $rel = $this->db->getAll($sql);

            if ($rel) {
                $count_charge = 0;
                $no_cumulative_price = 0;
                foreach ($rel as $k => $v) {
                    $child = $this->db->getAll(" SELECT * FROM" . $this->dsc->table("entry_criteria") . " WHERE parent_id = '" . $v['id'] . "'");
                    foreach ($child as $key => $val) {
                        if ($val['type'] == 'select' && $val['option_value'] != '') {
                            $child[$key]['option_value'] = explode(',', $val['option_value']);
                        }

                        $count_charge += $val['charge'];

                        if ($val['is_cumulative'] == 0) {
                            $no_cumulative_price += $val['charge'];
                        }
                    }

                    $rel[$k]['child'] = $child;
                }

                $rel['count_charge'] = $count_charge;
                $rel['no_cumulative_price'] = $no_cumulative_price;
            }
        }

        return $rel;
    }

    /**
     * 保存申请时的上传图片
     *
     * @access  public
     * @param int $image_files 上传图片数组
     * @param int $file_id 图片对应的id数组
     * @return  void
     */
    private function upload_apply_file($image_files = [], $file_id = [], $url = [])
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        /* 是否成功上传 */
        foreach ($file_id as $v) {
            $flag = false;
            if (isset($image_files['error'])) {
                if ($image_files['error'][$v] == 0) {
                    $flag = true;
                }
            } else {
                if ($image_files['tmp_name'][$v] != 'none' && $image_files['tmp_name'][$v]) {
                    $flag = true;
                }
            }
            if ($flag) {
                /*生成上传信息的数组*/
                $upload = [
                    'name' => $image_files['name'][$v],
                    'type' => $image_files['type'][$v],
                    'tmp_name' => $image_files['tmp_name'][$v],
                    'size' => $image_files['size'][$v],
                ];
                if (isset($image_files['error'])) {
                    $upload['error'] = $image_files['error'][$v];
                }

                $img_original = $image->upload_image($upload);
                if ($img_original === false) {
                    return sys_msg($image->error_msg(), 1, [], false);
                }
                $img_url[$v] = $img_original;
                /*删除原文件*/
                if (!empty($url[$v])) {
                    @unlink(storage_public($url[$v]));
                    unset($url[$v]);
                }
            }
        }
        $return_file = [];
        if (!empty($url) && !empty($img_url)) {
            $return_file = $url + $img_url;
        } elseif (!empty($url)) {
            $return_file = $url;
        } elseif (!empty($img_url)) {
            $return_file = $img_url;
        }
        if (!empty($return_file)) {
            return $return_file;
        } else {
            return false;
        }
    }

    /**
     * 获得指定国家的所有省份
     *
     * @access      public
     * @param int     country    国家的编号
     * @return      array
     */
    private function get_regions_log($type = 0, $parent = 0)
    {
        $sql = 'SELECT region_id, region_name FROM ' . $GLOBALS['dsc']->table('region') .
            " WHERE region_type = '$type' AND parent_id = '$parent'";

        return $GLOBALS['db']->GetAll($sql);
    }
}
