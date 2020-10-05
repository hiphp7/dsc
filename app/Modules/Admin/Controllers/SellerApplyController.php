<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminUser;
use App\Models\EntryCriteria;
use App\Models\GiftGardLog;
use App\Models\MerchantsGrade;
use App\Models\MerchantsPrivilege;
use App\Models\Payment;
use App\Models\SellerApplyInfo;
use App\Models\SellerGrade;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\SellerApply\SellerApplyManageService;

class SellerApplyController extends InitController
{
    protected $merchantCommonService;
    protected $commonRepository;
    protected $baseRepository;
    protected $sellerApplyManageService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        CommonRepository $commonRepository,
        BaseRepository $baseRepository,
        SellerApplyManageService $sellerApplyManageService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->commonRepository = $commonRepository;
        $this->baseRepository = $baseRepository;
        $this->sellerApplyManageService = $sellerApplyManageService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        get_invalid_apply();//过期申请失效处理
        $this->smarty->assign('ru_id', $adminru['ru_id']);
        /*判断是不是主管理员*/
        if (session('action_list') == 'all') {
            $pre_admin = 0;
        } else {
            $pre_admin = 1;
        }
        $this->smarty->assign('pre_admin', $pre_admin);

        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_apply');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '11_seller_apply']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['11_seller_apply']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['apply_export'], 'href' => 'seller_apply.php?act=exprod']);

            $apply_list = $this->sellerApplyManageService->getPzdList();
            $this->smarty->assign('apply_list', $apply_list['pzd_list']);
            $this->smarty->assign('filter', $apply_list['filter']);
            $this->smarty->assign('record_count', $apply_list['record_count']);
            $this->smarty->assign('page_count', $apply_list['page_count']);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('seller_apply_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            admin_priv('seller_apply');
            $apply_list = $this->sellerApplyManageService->getPzdList();
            $this->smarty->assign('apply_list', $apply_list['pzd_list']);
            $this->smarty->assign('filter', $apply_list['filter']);
            $this->smarty->assign('record_count', $apply_list['record_count']);
            $this->smarty->assign('page_count', $apply_list['page_count']);
            return make_json_result(
                $this->smarty->fetch('seller_apply_list.dwt'),
                '',
                ['filter' => $apply_list['filter'], 'page_count' => $apply_list['page_count']]
            );
        } /*详情*/
        elseif ($_REQUEST['act'] == 'info') {
            admin_priv('seller_apply');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['apply_info']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['apply_list'], 'href' => 'seller_apply.php?act=list']);

            $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;
            $grade_id = !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : 0;


            /*获取申请信息*/
            $res = SellerApplyInfo::where('apply_id', $apply_id);
            $seller_apply_info = $this->baseRepository->getToArrayFirst($res);

            if ($seller_apply_info['pay_id'] > 0) {
                $seller_apply_info['pay_name'] = Payment::where('pay_id', $seller_apply_info['pay_id'])->value('pay_name');
                $seller_apply_info['pay_name'] = $seller_apply_info['pay_name'] ? $seller_apply_info['pay_name'] : '';
            }
            $apply_criteria = unserialize($seller_apply_info['entry_criteria']);

            /*获取商家等级信息*/
            $seller_grade = get_seller_grade($seller_apply_info['ru_id']);    //获取商家等级
            if ($seller_grade) {
                $seller_grade['end_time'] = local_date('Y', $seller_grade['add_time']) + $seller_grade['year_num'] . '-' . local_date('m-d H:i:s', $seller_grade['add_time']);
                $seller_grade['addtime'] = local_date('Y-m-d H:i:s', $seller_grade['add_time']);
            }
            /*获取标准信息*/
            $res = SellerGrade::where('id', $grade_id);
            $grade_info = $this->baseRepository->getToArrayFirst($res);

            $entry_criteriat_info = $this->sellerApplyManageService->getEntryCriteria($grade_info['entry_criteria'] ?? '');//获取等级入驻标准

            $this->smarty->assign("grade_name", $grade_info['grade_name'] ?? '');
            /*获取操作日志*/
            $res = GiftGardLog::where('gift_gard_id', $apply_id)->where('handle_type', 'grade_log');
            $apply_log = $this->baseRepository->getToArrayGet($res);

            if ($apply_log) {
                foreach ($apply_log as $k => $v) {
                    if ($v['delivery_status']) {
                        $delivery_status = unserialize($v['delivery_status']);
                        switch ($delivery_status['apply_status']) {
                            case 0:
                                $apply_log[$k]['apply_status'] = $GLOBALS['_LANG']['not_audited'];
                                break;
                            case 1:
                                $apply_log[$k]['apply_status'] = $GLOBALS['_LANG']['audited_adopt'];
                                break;
                            case 2:
                                $apply_log[$k]['apply_status'] = $GLOBALS['_LANG']['audited_not_adopt'];
                                break;
                            case 3:
                                $apply_log[$k]['apply_status'] = $GLOBALS['_LANG']['invalid'];
                                break;
                        }
                        if ($delivery_status['is_paid'] == 0) {
                            $apply_log[$k]['is_paid'] = $GLOBALS['_LANG']['pay_no'];
                        } else {
                            $apply_log[$k]['is_paid'] = $GLOBALS['_LANG']['pay_yes'];
                        }
                    }

                    $apply_log[$k]['action_time'] = local_date("Y-m-d H:i:s", $v['addtime']);
                    $apply_log[$k]['action_name'] = AdminUser::where('user_id', $v['admin_id'])->value('user_name');
                    $apply_log[$k]['action_name'] = $apply_log[$k]['action_name'] ? $apply_log[$k]['action_name'] : '';
                }
            }

            $this->smarty->assign('apply_log', $apply_log);
            $this->smarty->assign('entry_criteriat_info', $entry_criteriat_info);
            $this->smarty->assign('apply_criteria', $apply_criteria);
            $this->smarty->assign('seller_grade', $seller_grade);
            $this->smarty->assign('seller_apply_info', $seller_apply_info);
            return $this->smarty->display('seller_apply_info.dwt');
        } /*操作*/
        elseif ($_REQUEST['act'] == 'operation') {
            admin_priv('seller_apply');
            $garde_id = !empty($_REQUEST['garde_id']) ? intval($_REQUEST['garde_id']) : 0;
            $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;
            $grade_id = !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : 0;
            $apply_status = !empty($_REQUEST['apply_status']) ? $_REQUEST['apply_status'] : 0;
            $reply_seller = !empty($_REQUEST['reply_seller']) ? $_REQUEST['reply_seller'] : '';
            $is_confirm = !empty($_REQUEST['apply_status']) ? $_REQUEST['apply_status'] : 0;
            $ru_id = !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;
            $total_amount = !empty($_REQUEST['total_amount']) ? $_REQUEST['total_amount'] : 0.00;
            $year_num = !empty($_REQUEST['year_num']) ? $_REQUEST['year_num'] : 0;
            $is_paid = !empty($_REQUEST['is_paid']) ? intval($_REQUEST['is_paid']) : 0;
            $pay_time = 0;
            $valid = 0;

            if ($is_confirm == 1) {
                $is_paid = 1;
            }
            if ($is_paid == 1) {
                $pay_time = gmtime();
            }

            $confirm_time = 0;
            $where = '';

            if ($is_confirm != 0) {
                $cfg = $GLOBALS['_CFG']['send_ship_email'];
                /*发送邮件*/
                if ($cfg == '1') {
                    if ($is_confirm == 1) {
                        $grade['confirm'] = $GLOBALS['_LANG']['yes_through'];
                    }
                    if ($is_confirm == 2) {
                        $grade['confirm'] = $GLOBALS['_LANG']['not_through_alt'];
                    }
                    if ($is_confirm == 3) {
                        $grade['confirm'] = $GLOBALS['_LANG']['invalid'];
                    }
                    $grade['merchants_message'] = $reply_seller;

                    $res = SellerShopinfo::where('ru_id', $ru_id);
                    $shopinfo = $this->baseRepository->getToArrayFirst($res);

                    $grade['shop_name'] = $shopinfo['shop_name'] ?? '';
                    $grade['email'] = $shopinfo['seller_email'] ?? '';

                    $grade['grade_name'] = SellerGrade::where('id', $garde_id)->value('grade_name');
                    $grade['grade_name'] = $grade['grade_name'] ? $grade['grade_name'] : '';

                    $tpl = get_mail_template('merchants_allpy_grade');
                    $this->smarty->assign('grade', $grade);
                    $this->smarty->assign('send_time', local_date($GLOBALS['_CFG']['time_format']));
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                    $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                    $this->commonRepository->sendEmail($grade['shop_name'], $grade['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                }

                $res = SellerApplyInfo::where('apply_id', $apply_id);
                $apply = $this->baseRepository->getToArrayFirst($res);

                /*编辑商家等级*/
                if ($is_confirm == 1) {
                    $valid = 1;
                    /*更新商家权限*/
                    $action_list = MerchantsPrivilege::where('grade_id', $grade_id)->value('action_list');
                    $action_list = $action_list ? $action_list : '';

                    $data = ['action_list' => $action_list];
                    AdminUser::where('ru_id', $ru_id)->update($data);

                    /*判断是否已经存在等级，是则修改，不是则插入*/
                    $res = MerchantsGrade::where('ru_id', $ru_id)->count();
                    if ($res > 0) {
                        $data = [
                            'grade_id' => $grade_id,
                            'add_time' => gmtime(),
                            'amount' => $total_amount,
                            'year_num' => $year_num
                        ];
                        MerchantsGrade::where('ru_id', $ru_id)->update($data);

                    } else {
                        $data = [
                            'ru_id' => $ru_id,
                            'grade_id' => $grade_id,
                            'add_time' => gmtime(),
                            'amount' => $total_amount,
                            'year_num' => $year_num
                        ];
                        MerchantsGrade::insert($data);

                    }
                    /*退款*/
                    if ($apply['back_price'] > 0 && $GLOBALS['_CFG']['apply_options'] == 1) {
                        log_account_change($ru_id, $apply['back_price'], 0, 0, 0, $GLOBALS['_LANG']['record_id'] . $apply['apply_sn'] . $GLOBALS['_LANG']['seller_grade_return_notic']);
                    }
                } else {
                    /*如果审核不通过或者失效处理时，删除商家等级*/
                    if ($is_confirm == 2 || $is_confirm == 3) {
                        MerchantsGrade::where('ru_id', $ru_id)
                            ->where('grade_id', $grade_id)
                            ->delete();
                    }

                    /*如果已支付，则退款*/
                    if ($apply['pay_status'] == 1 && $apply['payable_amount'] > 0 && $is_confirm != 0) {
                        log_account_change($ru_id, $apply['payable_amount'], 0, 0, 0, $GLOBALS['_LANG']['record_id'] . $apply['apply_sn'] . $GLOBALS['_LANG']['seller_grade_return_notic']);
                    }
                }
                if ($is_confirm != 0) {
                    $confirm_time = gmtime();
                }
            }
            /*修改申请状态*/
            $data = [
                'apply_status' => $is_confirm,
                'confirm_time' => $confirm_time,
                'reply_seller' => $reply_seller,
                'is_paid' => $is_paid,
                'pay_status' => $is_paid,
                'pay_time' => $pay_time,
                'valid' => $valid
            ];
            $res = SellerApplyInfo::where('apply_id', $apply_id)->update($data);

            if ($res > 0) {
                /*操作记录*/
                $on_sale = [];
                $on_sale['apply_status'] = $is_confirm;
                $on_sale['is_paid'] = $is_paid;
                $on_sale = serialize($on_sale);

                //操作日志
                $data = [
                    'admin_id' => session('admin_id'),
                    'gift_gard_id' => $apply_id,
                    'delivery_status' => $on_sale,
                    'addtime' => gmtime(),
                    'handle_type' => 'grade_log'
                ];
                GiftGardLog::insert($data);

                //修改其他当前商家申请状态
                "UPDATE" . $this->dsc->table('seller_apply_info') . "SET valid = 0 WHERE ru_id = '$ru_id' AND apply_id != '$apply_id'";
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'seller_apply.php?act=list';
                return sys_msg($GLOBALS['_LANG']['operation_succeed'], 0, $link);
            }
        } /*删除*/
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            $check_auth = check_authz_json('seller_apply');
            if ($check_auth !== true) {
                return $check_auth;
            }
            /* 判断标准类型，如果上传文件，删除文件 */
            $res = SellerApplyInfo::where('apply_id', $id);
            $res = $this->baseRepository->getToArrayFirst($res);

            $entry_criteria = unserialize($res['entry_criteria']);//获取标准
            foreach ($entry_criteria as $k => $v) {
                //获取标准类型
                $type = EntryCriteria::where('id', $k)->value('type');
                $type = $type ? $type : '';

                /*如果是文件上传，删除文件*/
                if ($type == 'file' && $v != '') {
                    @unlink(storage_public($v));
                }
            }
            /*删除*/
            SellerApplyInfo::where('apply_id', $id)->delete();
            /* 记录管理员操作 */
            admin_log($res['apply_sn'], 'remove', 'apply_sn');
            $url = 'seller_apply.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        } elseif ($_REQUEST['act'] == 'exprod') {
            admin_priv('seller_apply');
            setlocale(LC_ALL, 'en_US.UTF-8');
            $filename = local_date('YmdHis') . ".csv";
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=" . $filename);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');

            $apply_list = $this->sellerApplyManageService->getPzdList();
            echo $this->sellerApplyManageService->downloadApplyList($apply_list['pzd_list']);
        }
    }
}
