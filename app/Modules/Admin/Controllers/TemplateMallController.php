<?php

namespace App\Modules\Admin\Controllers;

use App\Services\Store\StoreCommonService;

/**
 * 商家模板
 */
class TemplateMallController extends InitController
{
    protected $storeCommonService;

    public function __construct(
        StoreCommonService $storeCommonService
    )
    {
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('visual');

        get_invalid_apply(1);//未支付模板订单失效处理

        //商家模板列表
        if ($_REQUEST['act'] == 'list') {
            admin_priv('10_visual_editing');
            //页面赋值
            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'template_mall']);
            $this->smarty->assign("ur_here", $GLOBALS['_LANG']['template_mall']);
            $template_mall_list = template_mall_list();
            $this->smarty->assign('available_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);

            $this->smarty->assign('template_type', 'seller');
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign("act_type", $_REQUEST['act']);


            return $this->smarty->display("visualhome_list.dwt");
        }
        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $template_mall_list = template_mall_list();
            $this->smarty->assign('available_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);
            $this->smarty->assign('template_type', 'seller');

            return make_json_result(
                $this->smarty->fetch('visualhome_list.dwt'),
                '',
                ['filter' => $template_mall_list['filter'], 'page_count' => $template_mall_list['page_count']]
            );
        }
        //模板支付使用记录
        if ($_REQUEST['act'] == 'template_apply_list') {
            //页面赋值
            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => 'template_apply_list']);
            $this->smarty->assign("ur_here", $GLOBALS['_LANG']['template_apply_list']);

            //获取商家列表
            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            //获取数据
            $template_mall_list = get_template_apply_list();
            $this->smarty->assign('available_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign("act_type", $_REQUEST['act']);


            return $this->smarty->display("template_apply_list.dwt");
        }
        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'apply_query') {
            $template_mall_list = get_template_apply_list();
            $this->smarty->assign('available_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);

            return make_json_result($this->smarty->fetch('template_apply_list.dwt'), '', ['filter' => $template_mall_list['filter'], 'page_count' => $template_mall_list['page_count']]);
        }
        /* ------------------------------------------------------ */
        //-- 确认付款操作
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'confirm_operation') {
            $apply_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            //获取订单信息
            $sql = "SELECT ru_id,temp_id,temp_code FROM" . $this->dsc->table("seller_template_apply") . "WHERE apply_id = '$apply_id'";
            $seller_template_apply = $this->db->getRow($sql);

            //导入已付款的模板
            $new_suffix = get_new_dir_name($seller_template_apply['ru_id']);//获取新的模板
            Import_temp($seller_template_apply['temp_code'], $new_suffix, $seller_template_apply['ru_id']);

            //更新模板使用数量
            $sql = "UPDATE" . $this->dsc->table('template_mall') . "SET sales_volume = sales_volume+1 WHERE temp_id = '" . $seller_template_apply['temp_id'] . "'";
            $this->db->query($sql);

            /*修改申请的支付状态 */
            $sql = " UPDATE " . $this->dsc->table('seller_template_apply') . " SET pay_status = 1 ,pay_time = '" . gmtime() . "' , apply_status = 1 WHERE apply_id= '$apply_id'";
            $this->db->query($sql);

            /* 修改此次支付操作的状态为已付款 */
            $sql = "UPDATE " . $this->dsc->table('pay_log') . "SET is_paid = 1 WHERE order_id = '" . $apply_id . "' AND order_type = '" . PAY_APPLYTEMP . "'";
            $this->db->query($sql);
            $url = 'template_mall.php?act=apply_query&' . str_replace('act=confirm_operation', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        } //删除模板订单
        elseif ($_REQUEST['act'] == 'remove') {
            $apply_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $sql = "DELETE FROM" . $this->dsc->table('seller_template_apply') . "WHERE apply_id = '$apply_id' AND pay_status = 0";
            $this->db->query($sql);
            $url = 'template_mall.php?act=apply_query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
    }
}
