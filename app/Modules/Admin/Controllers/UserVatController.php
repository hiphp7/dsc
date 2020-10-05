<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\UsersVatInvoicesInfo;
use App\Repositories\Common\DscRepository;
use App\Services\User\InvoiceService;

/**
 * 会员增票资质的处理
 */
class UserVatController extends InitController
{
    protected $invoiceService;
    protected $dscRepository;

    public function __construct(
        InvoiceService $invoiceService,
        DscRepository $dscRepository
    )
    {
        $this->invoiceService = $invoiceService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 初始化$exc对象 */
        $exc = new Exchange($this->dsc->table('users_vat_invoices_info'), $this->db, 'id', 'user_id');

        $adminru = get_admin_ru_id();

        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /* ------------------------------------------------------ */
        //-- 增票资质审核列表页面
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('user_vat_manage');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['vat_audit_list']);
            $this->smarty->assign('full_page', 1);

            $list = $this->vat_list();

            $this->smarty->assign('vat_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('vat_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 翻页、排序
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            admin_priv('user_vat_manage');
            $list = $this->vat_list();
            $this->smarty->assign('vat_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('vat_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 增票资质审核列表页面
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'view') {
            admin_priv('user_vat_manage');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['vat_view']);
            $info = $this->vat_info();

            $new_vat_consignee_list = $this->get_vat_consignee_list($_REQUEST['id']);

            $this->smarty->assign('new_vat_consignee_list', $new_vat_consignee_list);
            $this->smarty->assign('vat_info', $info);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action_link', ['href' => 'user_vat.php?act=list', 'text' => $GLOBALS['_LANG']['vat_audit_list']]);

            return $this->smarty->display('vat_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 审核资质改变状态
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            admin_priv('user_vat_manage');
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $other['company_name'] = isset($_REQUEST['company_name']) ? trim($_REQUEST['company_name']) : '';
            $other['tax_id'] = isset($_REQUEST['tax_id']) ? trim($_REQUEST['tax_id']) : '';
            $other['company_address'] = isset($_REQUEST['company_address']) ? trim($_REQUEST['company_address']) : '';
            $other['company_telephone'] = isset($_REQUEST['company_telephone']) ? trim($_REQUEST['company_telephone']) : '';
            $other['bank_of_deposit'] = isset($_REQUEST['bank_of_deposit']) ? trim($_REQUEST['bank_of_deposit']) : '';
            $other['bank_account'] = isset($_REQUEST['bank_account']) ? trim($_REQUEST['bank_account']) : '';
            $other['consignee_name'] = isset($_REQUEST['consignee_name']) ? trim($_REQUEST['consignee_name']) : '';
            $other['consignee_mobile_phone'] = isset($_REQUEST['consignee_mobile_phone']) ? trim($_REQUEST['consignee_mobile_phone']) : '';
            $other['consignee_address'] = isset($_REQUEST['consignee_address']) ? trim($_REQUEST['consignee_address']) : '';
            $other['audit_status'] = isset($_REQUEST['audit_status']) ? intval($_REQUEST['audit_status']) : 0;

            UsersVatInvoicesInfo::where('id', $id)->update($other);

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'user_vat.php?act=list';

            return sys_msg(lang('admin/user_vat.audit_success'), 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 增票删除
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            admin_priv('user_vat_manage');

            $id = intval($_GET['id']);

            $exc->drop($id);

            $url = 'user_vat.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

    /**
     * 增票资质申请列表
     * @access  public
     * @return void
     */
    private function vat_list()
    {
        /* 过滤条件 */
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['audit_status'] = isset($_REQUEST['audit_status']) && !empty($_REQUEST['audit_status']) ? intval($_REQUEST['audit_status']) : 0;

        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = " WHERE 1 ";

        $where .= (!empty($filter['keyword'])) ? " AND (company_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')" : '';

        if (isset($_REQUEST['audit_status'])) {
            $where .= (isset($filter['audit_status'])) ? " AND audit_status = '" . $filter['audit_status'] . "' " : '';
        }

        $sql = " SELECT COUNT(*) FROM " . $this->dsc->table('users_vat_invoices_info') . " AS t " . $where;
        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT * FROM " . $this->dsc->table('users_vat_invoices_info') . " $where ORDER BY " . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $row['add_time'] = local_date("Y-m-d H:i:s", $row['add_time']);
                switch ($row['audit_status']) {
                    case 0:
                        $row['audit_status'] = $GLOBALS['_LANG']['not_audited'];
                        break;
                    case 1:
                        $row['audit_status'] = $GLOBALS['_LANG']['audited_adopt'];
                        break;
                    case 2:
                        $row['audit_status'] = $GLOBALS['_LANG']['audited_not_adopt'];
                        break;
                }

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row['company_telephone'] = $this->dscRepository->stringToStar($row['company_telephone']);
                    $row['consignee_mobile_phone'] = $this->dscRepository->stringToStar($row['consignee_mobile_phone']);
                }

                $arr[] = $row;
            }
        }

        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    private function vat_info()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $sql = " SELECT * FROM " . $this->dsc->table('users_vat_invoices_info') . " WHERE id = '$id' ";
        $row = $this->db->getRow($sql);

        if ($row && isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
            $row['company_telephone'] = $this->dscRepository->stringToStar($row['company_telephone']);
            $row['consignee_mobile_phone'] = $this->dscRepository->stringToStar($row['consignee_mobile_phone']);
        }

        return $row;
    }

    //获取增值发票地址
    private function get_vat_consignee_list($vat_id)
    {
        $sql = "SELECT * FROM " . $this->dsc->table('users_vat_invoices_info') .
            " WHERE id = '$vat_id'";
        $res = $this->db->getRow($sql);

        $arr = [];
        $region = $this->invoiceService->userVatConsigneeRegion($res['id']);
        $arr['region'] = $region['region'] ?? '';

        return $arr;
    }
}
