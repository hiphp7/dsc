<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\WholesalePurchase;
use App\Models\WholesalePurchaseGoods;
use App\Repositories\Common\BaseRepository;
use App\Services\Wholesale\PurchaseService;
use App\Services\Wholesale\WholesalePurchaseManageService;

/**
 * 地区切换程序
 */
class WholesalePurchaseController extends InitController
{
    protected $purchaseService;
    protected $wholesalePurchaseManageService;
    protected $baseRepository;

    public function __construct(
        PurchaseService $purchaseService,
        WholesalePurchaseManageService $wholesalePurchaseManageService,
        BaseRepository $baseRepository
    )
    {
        $this->purchaseService = $purchaseService;
        $this->wholesalePurchaseManageService = $wholesalePurchaseManageService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        /*------------------------------------------------------ */
        //-- 求购列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('suppliers_purchase');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_wholesale_purchase']);
            $this->smarty->assign('full_page', 1);

            $purchase_list = $this->wholesalePurchaseManageService->purchaseList();

            $this->smarty->assign('purchase_list', $purchase_list['purchase_list']);
            $this->smarty->assign('filter', $purchase_list['filter']);
            $this->smarty->assign('record_count', $purchase_list['record_count']);
            $this->smarty->assign('page_count', $purchase_list['page_count']);

            $sort_flag = sort_flag($purchase_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('purchase_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_purchase');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $purchase_list = $this->wholesalePurchaseManageService->purchaseList();

            $this->smarty->assign('purchase_list', $purchase_list['purchase_list']);
            $this->smarty->assign('filter', $purchase_list['filter']);
            $this->smarty->assign('record_count', $purchase_list['record_count']);
            $this->smarty->assign('page_count', $purchase_list['page_count']);

            $sort_flag = sort_flag($purchase_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('purchase_list.dwt'),
                '',
                array('filter' => $purchase_list['filter'], 'page_count' => $purchase_list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 求购信息页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('suppliers_purchase');

            $purchase_id = empty($_REQUEST['purchase_id']) ? 0 : intval($_REQUEST['purchase_id']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['purchase_info']);
            $this->smarty->assign('action_link', array('href' => 'wholesale_purchase.php?act=list', 'text' => $GLOBALS['_LANG']['01_wholesale_purchase']));
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');

            $purchase_info = $this->purchaseService->getPurchaseInfo($purchase_id);
            $this->smarty->assign('purchase_info', $purchase_info);


            return $this->smarty->display('purchase_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 广告编辑的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */

            /* 提示信息 */
            $href[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'wholesale_purchase.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $href);
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('suppliers_purchase');

            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                /* 检查权限 */
                admin_priv('whole_sale');

                $ids = addslashes_deep($_POST['checkboxes']);

                if (isset($_POST['drop'])) {

                    $ids = $this->baseRepository->getExplode($ids);

                    /* 删除记录 */
                    WholesalePurchase::whereIn('purchase_id', $ids)->delete();

                    /* 记日志 */
                    admin_log('', 'batch_remove', 'wholesale_purchase');

                    $links[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'wholesale_purchase.php?act=list&' . list_link_postfix());
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                }
            }
        }


        /*------------------------------------------------------ */
        //-- 删除求购信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('suppliers_purchase');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            WholesalePurchase::where('purchase_id', $id)->delete();

            //删除商品信息和图片
            $goods_list = WholesalePurchaseGoods::where('purchase_id', $id);
            $goods_list = $this->baseRepository->getToArrayGet($goods_list);

            if ($goods_list) {
                foreach ($goods_list as $key => $val) {
                    if (!empty($val['goods_img'])) {
                        $goods_img = unserialize($val['goods_img']);
                        foreach ($goods_img as $k => $v) {
                            dsc_unlink(storage_public($v));
                        }
                    }

                    WholesalePurchaseGoods::where('goods_id', $val['goods_id'])->delete();
                }
            }

            $url = 'wholesale_purchase.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 切换审核状态
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'toggle_review_status') {
            $check_auth = check_authz_json('suppliers_purchase');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            WholesalePurchase::where('purchase_id', $id)->update([
                'review_status' => $val
            ]);

            return make_json_result($val);
        }
    }
}
