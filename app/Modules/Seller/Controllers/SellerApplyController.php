<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Repositories\Common\CommonRepository;
use App\Services\Merchant\MerchantCommonService;

class SellerApplyController extends InitController
{
    protected $merchantCommonService;
    protected $commonRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        CommonRepository $commonRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        get_invalid_apply();//过期申请失效处理
        $this->smarty->assign('ru_id', $adminru['ru_id']);
        $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '09_merchants_upgrade']);
        $exc = new Exchange($this->dsc->table("seller_apply_info"), $this->db, 'apply_id', 'apply_sn');
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_merchants_upgrade']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['09_merchants_upgrade'], 'href' => 'merchants_upgrade.php?act=list', 'class' => 'icon-reply']);
            $apply_list = $this->get_pzd_list();
            $page_count_arr = [];
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($apply_list, $page);
            $this->smarty->assign('apply_list', $apply_list['pzd_list']);
            $this->smarty->assign('filter', $apply_list['filter']);
            $this->smarty->assign('record_count', $apply_list['record_count']);
            $this->smarty->assign('page_count', $apply_list['page_count']);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('seller_apply.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $apply_list = $this->get_pzd_list();
            $page_count_arr = [];
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($apply_list, $page);
            $this->smarty->assign('apply_list', $apply_list['pzd_list']);
            $this->smarty->assign('filter', $apply_list['filter']);
            $this->smarty->assign('record_count', $apply_list['record_count']);
            $this->smarty->assign('page_count', $apply_list['page_count']);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            return make_json_result(
                $this->smarty->fetch('seller_apply.dwt'),
                '',
                ['filter' => $apply_list['filter'], 'page_count' => $apply_list['page_count']]
            );
        } /*详情*/
        elseif ($_REQUEST['act'] == 'info') {
//    admin_priv('seller_apply');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['apply_info']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['apply_list'], 'href' => 'seller_apply.php?act=list']);

            $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;
            $grade_id = !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : 0;

            /*获取申请信息*/
            $seller_apply_info = $this->db->getRow("SELECT * FROM" . $this->dsc->table('seller_apply_info') . " WHERE apply_id = '$apply_id' LIMIT 1");
            if ($seller_apply_info['pay_id'] > 0) {
                $seller_apply_info['pay_name'] = $this->db->getOne("SELECT pay_name FROM " . $this->dsc->table('payment') . " WHERE pay_id = '" . $seller_apply_info['pay_id'] . "'");
            }
            $apply_criteria = unserialize($seller_apply_info['entry_criteria']);

            /*获取商家等级信息*/
            $seller_grade = get_seller_grade($seller_apply_info['ru_id']);    //获取商家等级
            if ($seller_grade) {
                $seller_grade['end_time'] = local_date('Y', $seller_grade['add_time']) + $seller_grade['year_num'] . '-' . date('m-d H:i:s', $seller_grade['add_time']);
                $seller_grade['addtime'] = local_date('Y-m-d H:i:s', $seller_grade['add_time']);
            }
            /*获取标准信息*/
            $entry_criteria = $this->db->getOne("SELECT entry_criteria FROM " . $this->dsc->table('seller_grade') . " WHERE id = '$grade_id'");
            $entry_criteriat_info = $this->get_entry_criteria($entry_criteria);//获取等级入驻标准

            $this->smarty->assign('entry_criteriat_info', $entry_criteriat_info);
            $this->smarty->assign('apply_criteria', $apply_criteria);
            $this->smarty->assign('seller_grade', $seller_grade);
            $this->smarty->assign('seller_apply_info', $seller_apply_info);
            return $this->smarty->display('seller_apply_info.htm');
        } /*操作*/
        elseif ($_REQUEST['act'] == 'operation') {
            $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;
            $grade_id = !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : 0;

            $apply_status = !empty($_REQUEST['apply_status']) ? $_REQUEST['apply_status'] : 0;
            $reply_seller = !empty($_REQUEST['reply_seller']) ? $_REQUEST['reply_seller'] : '';
            $is_confirm = !empty($_REQUEST['apply_status']) ? $_REQUEST['apply_status'] : 0;
            $ru_id = !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;
            $total_amount = !empty($_REQUEST['total_amount']) ? $_REQUEST['total_amount'] : 0.00;
            $year_num = !empty($_REQUEST['year_num']) ? $_REQUEST['year_num'] : 0;
            $is_paid = !empty($_REQUEST['is_paid']) ? intval($_REQUEST['is_paid']) : 0;
            $confirm_time = 0;

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
                    $shopinfo = $this->db->getRow("SELECT shop_name,seller_email FROM " . $this->dsc->table('seller_shopinfo') . " WHERE ru_id = '$ru_id' LIMIT 1 ");
                    $grade['shop_name'] = $shopinfo['shop_name'];
                    $grade['email'] = $shopinfo['seller_email'];
                    $grade['grade_name'] = $this->db->getOne("SELECT grade_name FROM" . $this->dsc->table('seller_grade') . " WHERE id = '" . $_REQUEST['garde_id'] . "'");
                    $tpl = get_mail_template('merchants_allpy_grade');
                    $this->smarty->assign('grade', $grade);
                    $this->smarty->assign('send_time', local_date($GLOBALS['_CFG']['time_format']));
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                    $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                    $this->commonRepository->sendEmail($grade['shop_name'], $grade['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                }
                $apply = $this->db->getRow("SELECT pay_status , payable_amount ,back_price, apply_sn FROM " . $this->dsc->table('seller_apply_info') . " WHERE apply_id = '$apply_id'");//
                /*编辑商家等级*/
                if ($is_confirm == 1) {

                    /*更新商家权限*/
                    $action_list = $this->db->getRow("SELECT action_list FROM" . $this->dsc->table('merchants_privilege') . " WHERE grade_id = '$grade_id' LIMIT 1");
                    $sql = "UPDATE" . $this->dsc->table('admin_user') . " SET action_list = " . $action_list['action_list'] . " WHERE ru_id = '$ru_id'";
                    $this->db->query($sql);

                    /*判断是否已经存在等级，是则修改，不是则插入*/
                    $sql = " SELECT id FROM " . $this->dsc->table('merchants_grade') . " WHERE ru_id = '$ru_id'";
                    if ($this->db->getOne($sql) > 0) {
                        $this->db->query("UPDATE " . $this->dsc->table('merchants_grade') . " SET grade_id = '$grade_id',add_time = '" . gmtime() . "' ,amount = '$total_amount' , year_num = '$year_num' WHERE ru_id = '$ru_id'");
                    } else {
                        $this->db->query("INSERT INTO  " . $this->dsc->table('merchants_grade') . " (`ru_id`,`grade_id`,`add_time`,`amount`,`year_num`) VALUES ('$ru_id','$grade_id','" . gmtime() . "','$total_amount','$year_num')");
                    }
                    /*退款*/
                    if (isset($apply['back_price']) && $apply['back_price'] > 0) {
                        log_account_change($ru_id, $apply['back_price'], 0, 0, 0, $GLOBALS['_LANG']['record_id'] . $apply['apply_sn'] . $GLOBALS['_LANG']['seller_level_prepay_refund']);
                    }
                } else {
                    /*如果审核不通过或者失效处理时，删除商家等级*/
                    if ($is_confirm == 2 || $is_confirm == 3) {
                        $sql = "DELETE FROM" . $this->dsc->table("merchants_grade") . " WHERE ru_id = '$ru_id' AND grade_id = '$grade_id'";
                        $this->db->table($sql);
                    }

                    /*如果已支付，则退款*/
                    if ($apply['pay_status'] == 1 && $apply['payable_amount'] > 0 && $is_confirm != 0) {
                        log_account_change($ru_id, $apply['payable_amount'], 0, 0, 0, $GLOBALS['_LANG']['record_id'] . $apply['apply_sn'] . $GLOBALS['_LANG']['seller_level_not_pass_refund']);
                    }
                }
                if ($is_confirm != 0) {
                    $confirm_time = gmtime();
                }
            }
            /*修改申请状态*/
            $sql = 'UPDATE' . $this->dsc->table('seller_apply_info') . " SET apply_status = '$is_confirm' , confirm_time = '$confirm_time',reply_seller= '$reply_seller',is_paid = '$is_paid' ,pay_status = '$is_paid' , pay_time = '" . gmtime() . "'  WHERE apply_id = '$apply_id'";
            if ($this->db->query($sql) == true) {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'seller_apply.php?act=list';
                return sys_msg($GLOBALS['_LANG']['operation_succeed'], 0, $link);
            }
        } /*删除*/
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            /* 判断标准类型，如果上传文件，删除文件 */
            $entry_criteria_info = $this->db->getRow("SELECT entry_criteria FROM " . $this->dsc->table('seller_apply_info') . " WHERE apply_id = '$id' LIMIT 1");

            $entry_criteria = !empty($entry_criteria_info) ? unserialize($entry_criteria_info['entry_criteria']) : '';//获取标准
            if (!empty($entry_criteria)) {
                foreach ($entry_criteria as $k => $v) {
                    $type = $this->db->getOne(" SELECT type FROM" . $this->dsc->table('entry_criteria') . " WHERE id = '$k'");//获取标准类型
                    /*如果是文件上传，删除文件*/
                    if ($type == 'file' && $v != '') {
                        @unlink(storage_public($v));
                    }
                }
            }

            /*删除*/
            $exc->drop($id);
            $url = 'seller_apply.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
    }

    /*分页*/
    private function get_pzd_list()
    {
        $result = get_filter();
        $adminru = get_admin_ru_id();
        if ($result === false) {
            /*筛选信息*/
            $filter['apply_sn'] = empty($_REQUEST['apply_sn']) ? '' : trim($_REQUEST['apply_sn']);
            $grade_name = empty($_REQUEST['grade_name']) ? '' : $_REQUEST['grade_name'];
            $filter['pay_starts'] = isset($_REQUEST['pay_starts']) ? intval($_REQUEST['pay_starts']) : -1;
            $filter['apply_starts'] = isset($_REQUEST['apply_starts']) ? intval($_REQUEST['apply_starts']) : -1;
            $filter['ru_id'] = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : $adminru['ru_id'];
            $filter['keywords'] = isset($filter['keywords']) ? $filter['keywords'] : '';

            if ($grade_name) {
                $filter['grade_id'] = $this->db->getOne("SELECT id FROM" . $this->dsc->table('seller_grade') . " WHERE grade_name LIKE '%" . $grade_name . "%'");
            }

            /*拼装筛选*/
            $where = ' WHERE 1 ';
            if (isset($filter['apply_sn']) && $filter['apply_sn']) {
                $where .= " AND apply_sn LIKE '%" . mysql_like_quote($filter['apply_sn']) . "%'";
            }
            if (isset($filter['grade_id']) && $filter['grade_id']) {
                $where .= " AND grade_id = '" . $filter['grade_id'] . "'";
            }
            if (isset($filter['pay_starts']) && $filter['pay_starts'] != -1) {
                $where .= " AND pay_status = '" . $filter['pay_starts'] . "'";
            }
            if (isset($filter['apply_starts']) && $filter['apply_starts'] != -1) {
                $where .= " AND apply_status = '" . $filter['apply_starts'] . "'";
            }
            if (isset($filter['ru_id']) && $filter['ru_id'] > 0) {
                $where .= " AND ru_id = '" . $filter['ru_id'] . "'";
            }
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('seller_apply_info') . $where;
            $filter['record_count'] = $this->db->getOne($sql);
            $filter = page_and_size($filter);
            /* 获活动数据 */
            $sql = "SELECT * FROM" . $this->dsc->table('seller_apply_info') . $where . "  ORDER BY add_time ASC LIMIT " . $filter['start'] . "," . $filter['page_size'];
            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $row = $this->db->getAll($sql);
        foreach ($row as $k => $v) {
            $row[$k]['shop_name'] = $this->merchantCommonService->getShopName($v['ru_id'], 1);
            $row[$k]['grade_name'] = $this->db->getOne("SELECT grade_name FROM " . $this->dsc->table('seller_grade') . " WHERE id = '" . $v['grade_id'] . "'");
            $row[$k]['add_time'] = local_date('Y-m-d H:i:s', $v['add_time']);
            if ($v['pay_id'] > 0) {
                $row[$k]['pay_name'] = $this->db->getOne("SELECT pay_name FROM " . $this->dsc->table('payment') . " WHERE pay_id = '" . $v['pay_id'] . "'");
            }

            /*判断支付状态*/
            switch ($v['pay_status']) {
                case '0':
                    $row[$k]['status_paid'] = $GLOBALS['_LANG']['no_paid'];
                    break;
                case '1':
                    $row[$k]['status_paid'] = $GLOBALS['_LANG']['paid'];
                    break;
            }
            /*判断申请状态*/
            switch ($v['apply_status']) {
                case '0':
                    $row[$k]['status_apply'] = $GLOBALS['_LANG']['not_audited'];
                    break;
                case '1':
                    $row[$k]['status_apply'] = $GLOBALS['_LANG']['audited_adopt'];
                    break;
                case '2':
                    $row[$k]['status_apply'] = $GLOBALS['_LANG']['audited_not_adopt'];
                    break;
                case '3':
                    $row[$k]['status_apply'] = "<span style='color:red'>" . $GLOBALS['_LANG']['invalid'] . "</span>";
                    break;
            }
        }
        $arr = ['pzd_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    /*获取申请等级的入驻标准*/
    private function get_entry_criteria($entry_criteria = '')
    {
        $entry_criteria = unserialize($entry_criteria);//反序列化等级入驻标准
        $rel = '';
        if (!empty($entry_criteria)) {
            $sql = " SELECT id,criteria_name FROM" . $this->dsc->table('entry_criteria') . " WHERE id " . db_create_in($entry_criteria);
            $rel = $this->db->getAll($sql);
            foreach ($rel as $k => $v) {
                $child = $this->db->getAll(" SELECT * FROM" . $this->dsc->table("entry_criteria") . " WHERE parent_id = '" . $v['id'] . "'");
                foreach ($child as $key => $val) {
                    if ($val['type'] == 'select' && $val['option_value'] != '') {
                        $child[$key]['option_value'] = explode(',', $val['option_value']);
                    }
                }
                $rel[$k]['child'] = $child;
            }
        }
        return $rel;
    }
}
