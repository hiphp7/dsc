<?php

namespace App\Modules\Admin\Controllers;

use App\Models\GoodsInventoryLogs;
use App\Models\RegionWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsInventoryLogsManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 记录管理员操作日志
 */
class GoodsInventoryLogsController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $goodsInventoryLogsManageService;
    protected $storeCommonService;
    protected $goodsAttrService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        GoodsInventoryLogsManageService $goodsInventoryLogsManageService,
        BaseRepository $baseRepository,
        StoreCommonService $storeCommonService,
        GoodsAttrService $goodsAttrService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->goodsInventoryLogsManageService = $goodsInventoryLogsManageService;
        $this->storeCommonService = $storeCommonService;
        $this->goodsAttrService = $goodsAttrService;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $step = isset($_REQUEST['step']) ? addslashes($_REQUEST['step']) : '';

        /*------------------------------------------------------ */
        //-- 获取所有日志列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限的判断 */
            admin_priv('order_view');

            $storage = '';

            if ($step) {
                if ($step == 'put') {
                    $storage = "-" . $GLOBALS['_LANG']['01_goods_storage_put'];
                    $this->smarty->assign('step', 'put');
                } else {
                    $storage = "-" . $GLOBALS['_LANG']['02_goods_storage_out'];
                    $this->smarty->assign('step', 'out');
                }
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['13_goods_inventory_logs'] . $storage);
            $ip_list = isset($ip_list) ? $ip_list : '';
            $this->smarty->assign('ip_list', $ip_list);
            $this->smarty->assign('full_page', 1);

            $log_list = $this->get_goods_inventory_logs($adminru['ru_id']);

            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $warehouse_list = get_warehouse_list_goods();
            $this->smarty->assign('warehouse_list', $warehouse_list); //仓库列表

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('goods_inventory_logs.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $log_list = $this->get_goods_inventory_logs($adminru['ru_id']);

            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $warehouse_list = get_warehouse_list_goods();
            $this->smarty->assign('warehouse_list', $warehouse_list); //仓库列表

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('goods_inventory_logs.dwt'),
                '',
                ['filter' => $log_list['filter'], 'page_count' => $log_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 查询仓库地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_area') {
            $check_auth = check_authz_json('order_view');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $warehouse_id = intval($_REQUEST['warehouse_id']);

            $res = RegionWarehouse::where('region_type', 1)->where('parent_id', $warehouse_id);
            $region_list = $this->baseRepository->getToArrayGet($res);
            $select = '';
            $select .= '<div class="cite">' . $GLOBALS['_LANG']['please_select'] . '</div><ul>';
            if ($region_list) {
                foreach ($region_list as $key => $row) {
                    $select .= '<li><a href="javascript:;" data-value="' . $row['region_id'] . '" class="ftx-01">' . $row['region_name'] . '</a></li>';
                }
            }
            $select .= '</ul><input name="area_id" type="hidden" value="" id="area_id_val">';

            $result = $select;

            return make_json_result($result);
        }

        /*------------------------------------------------------ */
        //-- 批量删除日志记录
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_drop') {
            admin_priv('order_view');

            $drop_type_date = isset($_POST['drop_type_date']) ? $_POST['drop_type_date'] : '';


            $count = 0;
            foreach ($_POST['checkboxes'] as $key => $id) {
                $result = GoodsInventoryLogs::where('id', $id)->delete();

                $count++;
            }
            if ($result) {
                admin_log('', 'remove', 'goods_inventory_logs');

                if ($step) {
                    $step = '&step=' . $step;
                }

                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'goods_inventory_logs.php?act=list' . $step];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $count), 0, $link);
            }
        }
    }

    /* 获取管理员操作记录 */
    private function get_goods_inventory_logs($ru_id)
    {
        load_helper('order');

        /* 过滤条件 */
        $result = get_filter();
        if ($result === false) {
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
                $filter['order_sn'] = json_str_iconv($filter['order_sn']);
            }

            $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : trim($_REQUEST['start_time']);
            $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : trim($_REQUEST['end_time']);
            $filter['warehouse_id'] = !isset($_REQUEST['warehouse_id']) ? 0 : intval($_REQUEST['warehouse_id']);
            $filter['area_id'] = !isset($_REQUEST['end_time']) ? 0 : intval($_REQUEST['area_id']);

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'gil.id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['step'] = empty($_REQUEST['step']) ? '' : trim($_REQUEST['step']);
            $filter['operation_type'] = !isset($_REQUEST['operation_type']) ? -1 : intval($_REQUEST['operation_type']);

            //卖场 start
            $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
            $adminru = get_admin_ru_id();
            if ($adminru['rs_id'] > 0) {
                $filter['rs_id'] = $adminru['rs_id'];
            }
            //卖场 end

            //查询条件
            $where = " WHERE 1 ";

            if ($ru_id > 0) {
                $where .= " AND g.user_id = '$ru_id'";
            }

            /* 关键字 */
            if (!empty($filter['keyword'])) {
                $where .= " AND g.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }

            /* 订单号 */
            if (!empty($filter['order_sn'])) {
                $where .= " AND oi.order_sn = '" . $filter['order_sn'] . "'";
            }

            /* 操作时间 */
            if (!empty($filter['start_time']) || !empty($filter['end_time'])) {
                $start_time = local_strtotime($filter['start_time']);
                $end_time = local_strtotime($filter['end_time']);
                $where .= " AND gil.add_time > '" . $start_time . "' AND gil.add_time < '" . $end_time . "'";
            }

            /*仓库*/
            if ($filter['warehouse_id'] && empty($filter['area_id'])) {
                $where .= " AND (gil.model_inventory = 1 OR gil.model_attr = 1) AND gil.warehouse_id = '" . $filter['warehouse_id'] . "'";
            }


            if ($filter['area_id'] && $filter['warehouse_id']) {
                $where .= " AND (gil.model_inventory = 2 OR gil.model_attr = 2) AND gil.area_id = '" . $filter['area_id'] . "'";
            }

            //卖场
            $where .= get_rs_null_where('g.user_id', $filter['rs_id']);

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] != 0) {
                if ($ru_id == 0) {
                    if ($filter['store_search'] > 0) {
                        $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                        if ($store_type) {
                            $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                        }

                        if ($filter['store_search'] == 1) {
                            $where .= " AND g.user_id = '" . $filter['merchant_id'] . "' ";
                        } elseif ($filter['store_search'] == 2) {
                            $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                        } elseif ($filter['store_search'] == 3) {
                            $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                        }

                        if ($filter['store_search'] > 1) {
                            $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                                " WHERE msi.user_id = g.user_id $store_where) > 0 ";
                        }
                    } else {
                        $where .= " AND g.user_id = '" . $filter['store_search'] . "' ";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            if ($filter['operation_type'] == -1) {
                //出库
                if ($filter['step'] == 'out') {
                    $where .= " AND use_storage IN(0,1,4,8,10,15)";
                }

                //入库
                if ($filter['step'] == 'put') {
                    $where .= " AND use_storage IN(2,3,5,6,7,9,11,13)";
                }
            } else {
                $where .= " AND use_storage = '" . $filter['operation_type'] . "'";
            }

            /* 获得总记录数据 */
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('goods_inventory_logs') . " as gil " .
                " LEFT JOIN " . $this->dsc->table('goods') . " as g ON gil.goods_id = g.goods_id" .
                " LEFT JOIN " . $this->dsc->table('order_info') . " as oi ON gil.order_id = oi.order_id " .
                $where;
            $filter['record_count'] = $this->db->getOne($sql);

            $filter = page_and_size($filter);

            /* 获取管理员日志记录 */
            $list = [];
            $sql = 'SELECT gil.*, g.user_id,g.goods_id,g.goods_thumb,g.brand_id, g.goods_name, oi.order_sn, au.user_name AS admin_name, og.goods_attr FROM ' . $this->dsc->table('goods_inventory_logs') . " as gil " .
                " LEFT JOIN " . $this->dsc->table('goods') . " as g ON gil.goods_id = g.goods_id" .
                " LEFT JOIN " . $this->dsc->table('order_info') . " as oi ON gil.order_id = oi.order_id " .
                " LEFT JOIN " . $this->dsc->table('order_goods') . " as og ON gil.goods_id = og.goods_id AND gil.order_id = og.order_id " .
                " LEFT JOIN " . $this->dsc->table('admin_user') . " as au ON gil.admin_id = au.user_id " .
                $where . ' GROUP BY gil.id ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
            $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

            $filter['keyword'] = stripslashes($filter['keyword']);
            $param_str = isset($param_str) ? $param_str : '';
            set_filter($filter, $sql, $param_str);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        foreach ($res as $rows) {
            $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
            $rows['shop_name'] = $this->merchantCommonService->getShopName($rows['user_id'], 1);
            $rows['warehouse_name'] = $this->goodsInventoryLogsManageService->getInventoryRegion($rows['warehouse_id']);
            $rows['area_name'] = $this->goodsInventoryLogsManageService->getInventoryRegion($rows['area_id']);
            if (empty($rows['admin_name'])) {
                $rows['admin_name'] = $GLOBALS['_LANG']['reception_user_place_order'];
            }
            if ($rows['brand_id'] > 0) {
                $rows['brand_name'] = $this->db->getOne("SELECT brand_name  FROM" . $this->dsc->table("brand") . " WHERE brand_id = '" . $rows['brand_id'] . "'");
            }
            if ($rows['product_id']) {
                if ($rows['model_attr'] == 1) {
                    $table = "products_warehouse";
                } elseif ($rows['model_attr'] == 2) {
                    $table = "products_area";
                } else {
                    $table = "products";
                }

                $sql = "SELECT goods_attr FROM " . $this->dsc->table($table) . " WHERE product_id = '" . $rows['product_id'] . "' LIMIT 1";
                $spec = $this->db->getRow($sql);
                $spec['goods_attr'] = explode("|", $spec['goods_attr']);

                $rows['goods_attr'] = $this->goodsAttrService->getGoodsAttrInfo($spec['goods_attr'], 'pice', $rows['warehouse_id'], $rows['area_id']); //ecmoban模板堂 --zhuo
            }

            //图片显示
            $rows['goods_thumb'] = get_image_path($rows['goods_thumb']);

            $list[] = $rows;
        }
        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
