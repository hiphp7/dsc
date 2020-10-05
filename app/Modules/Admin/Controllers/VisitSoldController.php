<?php

namespace App\Modules\Admin\Controllers;

use App\Exports\VisitSoldExport;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Order\OrderCommonService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 访问购买比例
 */
class VisitSoldController extends InitController
{
    protected $orderCommonService;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        OrderCommonService $orderCommonService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->orderCommonService = $orderCommonService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('statistic', 'admin');
        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        admin_priv('client_flow_stats');

        /*------------------------------------------------------ */
        //--访问购买比例
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list' || $_REQUEST['act'] == 'download') {
            /* 变量的初始化 */
            $cat_id = (!empty($_REQUEST['cat_id'])) ? intval($_REQUEST['cat_id']) : 0;
            $brand_id = (!empty($_REQUEST['brand_id'])) ? intval($_REQUEST['brand_id']) : 0;
            $show_num = (!empty($_REQUEST['show_num'])) ? intval($_REQUEST['show_num']) : 15;

            /* 下载报表 */
            if ($_REQUEST['act'] == "download") {
                $filename = 'visit_sold_' . $this->timeRepository->getLocalDate('Y-m-d H:i:s');
                return Excel::download(new VisitSoldExport, $filename . '.xlsx');
            } else {
                /* 获取访问购买的比例数据 */
                $click_sold_info = $this->orderCommonService->clickSoldInfo($adminru['ru_id'], $cat_id, $brand_id, $show_num);

                /* 赋值到模板 */
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['visit_buy_per']);

                $this->smarty->assign('show_num', $show_num);

                if ($brand_id > 0) {
                    $sql = "SELECT brand_name FROM" . $this->dsc->table('brand') . " WHERE brand_id = '$brand_id'";
                    $brand_name = $this->db->getOne($sql);
                    $this->smarty->assign('brand_name', $brand_name);
                }
                $this->smarty->assign('brand_id', $brand_id);
                $this->smarty->assign('click_sold_info', $click_sold_info);

                $this->smarty->assign('filter_category_list', get_category_list($cat_id)); //分类列表
                $this->smarty->assign('filter_brand_list', search_brand_list());
                //分类导航
                if ($cat_id > 0) {
                    $parent_cat_list = get_select_category($cat_id, 1, true);
                    $filter_category_navigation = get_array_category_info($parent_cat_list);
                    $this->smarty->assign('filter_category_navigation', $filter_category_navigation);
                    if (!empty($filter_category_navigation)) {
                        $cat_val = '';
                        foreach ($filter_category_navigation as $k => $v) {
                            $cat_val .= $v['cat_name'] . ">";
                        }
                    }
                    if ($cat_val) {
                        $cat_val = substr($cat_val, 0, -1);
                        $this->smarty->assign('cat_val', $cat_val);
                    }
                }
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['download_visit_buy'], 'href' => 'visit_sold.php?act=download&show_num=' . $show_num . '&cat_id=' . $cat_id . '&brand_id=' . $brand_id . '&show_num=' . $show_num]);

                /* 显示页面 */

                return $this->smarty->display('visit_sold.dwt');
            }
        }
    }
}
