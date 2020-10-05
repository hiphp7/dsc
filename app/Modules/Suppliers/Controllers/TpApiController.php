<?php

namespace App\Modules\Suppliers\Controllers;

use App\Models\AdminAction;
use App\Models\OrderInfo;
use App\Models\OrderPrintSetting;
use App\Models\OrderPrintSize;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\ShopConfig;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleOrderPrintSetting;
use App\Repositories\Common\BaseRepository;
use App\Services\Message\TpApiManageService;
use App\Services\Order\OrderManageService;
use Illuminate\Support\Str;

/**
 * 记录管理员操作日志
 */
class TpApiController extends InitController
{
    protected $baseRepository;
    protected $tpApiManageService;

    public function __construct(
        BaseRepository $baseRepository,
        TpApiManageService $tpApiManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->tpApiManageService = $tpApiManageService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        //默认
        if (empty($_REQUEST['act'])) {
            die('Error');
        }

        /* ------------------------------------------------------ */
        //--快递鸟打印
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'kdniao_print') {
            load_helper(['order', 'goods']);

            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $order_ids = array();

            if (!empty($order_id)) {
                $order_ids[] = $order_id;
            }

            if (!empty($order_sn)) {
                $order_sn = $this->baseRepository->getExplode($order_sn);
                $ids = OrderInfo::whereIn('order_sn', $order_sn);
                $ids = $this->baseRepository->getToArrayGet($ids);
                $ids = $this->baseRepository->getKeyPluck($ids, 'order_id');

                $order_ids = $ids ? array_merge($order_ids, $ids) : $order_ids;
                $order_ids = $order_ids ? array_unique($order_ids) : [];
            }

            $link[] = array('text' => lang('common.close_window'), 'href' => 'javascript:window.close()');

            //判断订单
            if (empty($order_ids)) {
                return sys_msg(lang('suppliers/tp_api.no_please_order'), 1, $link);
            }

            //判断快递是否一样
            $order_ids = $this->baseRepository->getExplode($order_ids);
            $shipping_ids = OrderInfo::whereIn('order_id', $order_ids);
            $shipping_ids = $this->baseRepository->getToArrayGet($shipping_ids);
            $shipping_ids = $this->baseRepository->getKeyPluck($shipping_ids, 'shipping_id');
            $shipping_ids = $shipping_ids ? array_unique($shipping_ids) : [];

            if (count($shipping_ids) > 1) {
                return sys_msg(lang('suppliers/tp_api.order_print'), 1, $link);
            }

            //处理数据
            $batch_html = array();
            $batch_error = array();
            if ($order_ids && $order_ids[0]) {
                $order_info = order_info($order_ids[0]);

                //识别快递
                $shipping_info = Shipping::where('shipping_id', $order_info['shipping_id']);
                $shipping_info = $this->baseRepository->getToArrayFirst($shipping_info);

                $shipping_spec = [];
                if ($shipping_info) {
                    $shipping_name = Str::studly($shipping_info['shipping_code']);
                    $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');

                    if (file_exists($modules)) {
                        include_once($modules);
                    }

                    $shipping_spec = get_shipping_spec($shipping_info['shipping_code']);
                }

                $GLOBALS['smarty']->assign('shipping_info', $shipping_info);
                $GLOBALS['smarty']->assign('shipping_spec', $shipping_spec);

                if ($order_ids) {
                    foreach ($order_ids as $order_id) {
                        $result = get_kdniao_print_content($order_id, $shipping_spec, $shipping_info);

                        //判断是否成功
                        if ($result["ResultCode"] != "100") {
                            $batch_error[] = sprintf(lang('suppliers/tp_api.order_remind.0'), $order_id, $result['Reason']);
                            continue;
                        }

                        //输出打印模板
                        if (!empty($result['PrintTemplate'])) {
                            $batch_html[] = $result['PrintTemplate'];
                        } else {
                            $batch_error[] = sprintf(lang('suppliers/tp_api.order_remind.1'), $order_id);
                            continue;
                        }

                        //将物流单号填入系统
                        if (isset($result['Order']['LogisticCode'])) {
                            OrderInfo::where('order_id', $order_id)
                                ->update([
                                    'invoice_no' => $result['Order']['LogisticCode']
                                ]);
                        }
                    }
                }
            }

            $this->smarty->assign('batch_html', $batch_html);
            $this->smarty->assign('batch_error', implode(',', $batch_error));

            $kdniao_printer = SellerShopinfo::where('ru_id', $adminru['ru_id'])->value('kdniao_printer');
            $kdniao_printer = $kdniao_printer ? $kdniao_printer : '';

            $this->smarty->assign('kdniao_printer', $kdniao_printer);

            return $this->smarty->display('kdniao_print.dwt');
        }

        /*------------------------------------------------------ */
        //-- 电子面单列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'order_print_setting') {
            admin_priv('suppliers_order_print_setting');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            $this->smarty->assign('menu_select', array('action' => '11_system', 'current' => 'order_print_setting'));

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_print_setting']);
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['order_print_setting_add'], 'href' => 'tp_api.php?act=order_print_setting_add'));
            $this->smarty->assign('full_page', 1);

            $print_setting = $this->get_order_print_setting($adminru['suppliers_id']);

            $this->smarty->assign('print_setting', $print_setting['list']);
            $this->smarty->assign('filter', $print_setting['filter']);
            $this->smarty->assign('record_count', $print_setting['record_count']);
            $this->smarty->assign('page_count', $print_setting['page_count']);

            $sort_flag = sort_flag($print_setting['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('order_print_setting.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_query') {
            $check_auth = check_authz_json('suppliers_order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $print_setting = $this->get_order_print_setting();

            $this->smarty->assign('print_setting', $print_setting['list']);
            $this->smarty->assign('filter', $print_setting['filter']);
            $this->smarty->assign('record_count', $print_setting['record_count']);
            $this->smarty->assign('page_count', $print_setting['page_count']);

            $sort_flag = sort_flag($print_setting['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('order_print_setting.dwt'), '', array('filter' => $print_setting['filter'], 'page_count' => $print_setting['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_remove') {
            $check_auth = check_authz_json('suppliers_order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            WholesaleOrderPrintSetting::where('id', $id)
                ->where('suppliers_id', $adminru['suppliers_id'])
                ->delete();

            $url = 'tp_api.php?act=order_print_setting_query&' . str_replace('act=order_print_setting_remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑打印机
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_order_printer') {
            $check_auth = check_authz_json('suppliers_order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = trim($_POST['val']);

            $res = WholesaleOrderPrintSetting::where('id', $id)
                ->where('suppliers_id', $adminru['suppliers_id'])
                ->update([
                    'printer' => $val
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑宽度
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_print_width') {
            $check_auth = check_authz_json('suppliers_order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = trim($_POST['val']);

            $res = WholesaleOrderPrintSetting::where('id', $id)
                ->where('suppliers_id', $adminru['suppliers_id'])
                ->update([
                    'width' => $val
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑打印机排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('suppliers_order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = trim($_POST['val']);

            $res = WholesaleOrderPrintSetting::where('id', $id)
                ->where('suppliers_id', $adminru['suppliers_id'])
                ->update([
                    'sort_order' => $val
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 切换默认
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_order_is_default') {
            $check_auth = check_authz_json('suppliers_order_print_setting');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $res = WholesaleOrderPrintSetting::where('id', $id)
                ->where('suppliers_id', $adminru['suppliers_id'])
                ->update([
                    'is_default' => $val
                ]);

            if ($res) {
                WholesaleOrderPrintSetting::where('id', '<>', $id)
                    ->where('suppliers_id', $adminru['suppliers_id'])
                    ->update([
                        'is_default' => 0
                    ]);

                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑电子面单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_add' || $_REQUEST['act'] == 'order_print_setting_edit') {
            admin_priv('suppliers_order_print_setting');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['11_system']);
            $this->smarty->assign('menu_select', array('action' => '11_system', 'current' => 'order_print_setting'));

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $print_size = OrderPrintSize::whereRaw(1);
            $print_size = $this->baseRepository->getToArrayGet($print_size);
            $this->smarty->assign('print_size', $print_size);

            if ($id > 0) {
                $print_setting = OrderPrintSetting::where('id', $id);
                $print_setting = $this->baseRepository->getToArrayFirst($print_setting);

                $this->smarty->assign('print_setting', $print_setting);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_print_setting_edit']);
                $this->smarty->assign('form_action', 'order_print_setting_update');
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_print_setting_add']);
                $this->smarty->assign('form_action', 'order_print_setting_insert');
            }
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['order_print_setting'], 'href' => 'tp_api.php?act=order_print_setting'));


            return $this->smarty->display('order_print_setting_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑电子面单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print_setting_insert' || $_REQUEST['act'] == 'order_print_setting_update') {
            admin_priv('suppliers_order_print_setting');

            $data = array();
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $data['suppliers_id'] = $adminru['suppliers_id'];
            $data['is_default'] = !empty($_REQUEST['is_default']) ? intval($_REQUEST['is_default']) : 0;
            $data['specification'] = !empty($_REQUEST['specification']) ? trim($_REQUEST['specification']) : '';
            $data['printer'] = !empty($_REQUEST['printer']) ? trim($_REQUEST['printer']) : '';
            $data['width'] = !empty($_REQUEST['width']) ? intval($_REQUEST['width']) : 0;

            if (empty($data['width'])) {
                $print_size = OrderPrintSize::where('specification', $data['specification']);
                $this->baseRepository->getToArrayFirst($print_size);
                $data['width'] = $print_size['width'] ?? 0;
            }

            /* 检查是否重复 */
            $is_only = WholesaleOrderPrintSetting::where('suppliers_id', $adminru['suppliers_id'])
                ->where('specification', $data['specification'])
                ->where('id', '<>', $id)
                ->count();

            if ($is_only) {
                return sys_msg($GLOBALS['_LANG']['specification_exist'], 1);
            }

            /* 插入、更新 */
            if ($id > 0) {
                WholesaleOrderPrintSetting::where('id', $id)->update($data);
                $msg = $GLOBALS['_LANG']['edit_success'];
            } else {
                $id = WholesaleOrderPrintSetting::insertGetId($data);
                $msg = $GLOBALS['_LANG']['add_success'];
            }
            /* 默认设置 */
            if ($data['is_default']) {
                WholesaleOrderPrintSetting::where('id', '<>', $id)->update([
                    'is_default' => 0
                ]);
            }

            $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'tp_api.php?act=order_print_setting');
            return sys_msg($msg, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 电子面单 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_print') {
            /* 检查权限 */
            admin_priv('suppliers_order_view');

            /* 打印数据 */
            $print_specification = WholesaleOrderPrintSetting::where('suppliers_id', $adminru['suppliers_id'])
                ->where('is_default', 1)
                ->value('specification');
            $print_specification = $print_specification ? $print_specification : '';

            if (empty($print_specification)) {
                $print_specification = WholesaleOrderPrintSetting::where('suppliers_id', $adminru['suppliers_id'])->orderByRaw('sort_order, id asc')->value('specification');
            }

            $print_size_info = OrderPrintSize::where('specification', $print_specification);
            $print_size_info = $this->baseRepository->getToArrayFirst($print_size_info);

            $print_size_list = WholesaleOrderPrintSetting::where('suppliers_id', $adminru['suppliers_id'])->orderByRaw('sort_order, id asc');
            $print_size_list = $this->baseRepository->getToArrayGet($print_size_list);

            $print_spec_info = WholesaleOrderPrintSetting::where('specification', $print_specification);
            $print_spec_info = $this->baseRepository->getToArrayFirst($print_spec_info);

            if (empty($print_size_list)) {
                $link[] = array('text' => $GLOBALS['_LANG']['back_set'], 'href' => 'tp_api.php?act=order_print_setting');
                return sys_msg($GLOBALS['_LANG']['no_print_setting'], 1, $link);
            }

            $this->smarty->assign('print_specification', $print_specification);
            $this->smarty->assign('print_size_info', $print_size_info);
            $this->smarty->assign('print_size_list', $print_size_list);
            $this->smarty->assign('print_spec_info', $print_spec_info);

            /* 订单数据 */
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $order_type = empty($_REQUEST['order_type']) ? 'order' : trim($_REQUEST['order_type']);

            $action_id = AdminAction::where('action_code', 'supply_and_demand')->value('action_id');
            $action_id = $action_id ? $action_id : 0;

            if ($order_type == 'order' || empty($action_id)) {
                $ids = OrderInfo::whereRaw(1);
            } else {
                $ids = WholesaleOrderInfo::whereRaw(1);
            }

            $order_ids = array();
            if (!empty($order_id)) {
                $order_ids[] = $order_id;
            }
            if (!empty($order_sn)) {
                $order_sn = $this->baseRepository->getExplode($order_sn);
                $ids = $ids->whereIn('order_sn', $order_sn);
                $ids = $this->baseRepository->getToArrayGet($ids);
                $ids = $this->baseRepository->getKeyPluck($ids, 'order_id');

                $order_ids = $ids ? array_merge($order_ids, $ids) : $order_ids;
                $order_ids = $order_ids ? array_unique($order_ids) : [];
            }

            $web_url = asset('assets/suppliers') . '/';
            $this->smarty->assign('web_url', $web_url);

            $this->smarty->assign('order_type', $order_type);

            $order_print_logo = ShopConfig::where('code', 'order_print_logo')->value('value');
            $order_print_logo = $order_print_logo ? strstr($order_print_logo, "images") : '';

            $this->smarty->assign('order_print_logo', $order_print_logo);
            $this->smarty->assign('order_print_logo', $this->dsc->seller_url('suppliers') . SUPPLLY_PATH . '/' . $order_print_logo);

            $part_html = array();
            if ($order_ids) {
                foreach ($order_ids as $order_id) {
                    $order_info = $this->tpApiManageService->printOrderInfo($order_id, $order_type);
                    $this->smarty->assign('order_info', $order_info);
                    $part_html[] = $this->smarty->fetch('library/order_print_part.lbi');
                }
            }

            $this->smarty->assign('part_html', $part_html);

            /* 显示模板 */
            return $this->smarty->display('order_print.dwt');
        }

        /*------------------------------------------------------ */
        //-- 切换电子面单 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'change_order_print') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_order_view');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 打印数据 */
            $print_specification = empty($_REQUEST['specification']) ? '' : trim($_REQUEST['specification']);

            $print_size_info = OrderPrintSize::where('specification', $print_specification);
            $print_size_info = $this->baseRepository->getToArrayFirst($print_size_info);

            $print_size_list = WholesaleOrderPrintSetting::where('suppliers_id', $adminru['suppliers_id'])->orderByRaw('sort_order, id asc');
            $print_size_list = $this->baseRepository->getToArrayGet($print_size_list);

            $print_spec_info = WholesaleOrderPrintSetting::where('specification', $print_specification);
            $print_spec_info = $this->baseRepository->getToArrayFirst($print_spec_info);

            $this->smarty->assign('print_specification', $print_specification);
            $this->smarty->assign('print_size_info', $print_size_info);
            $this->smarty->assign('print_size_list', $print_size_list);
            $this->smarty->assign('print_spec_info', $print_spec_info);

            /* 订单数据 */
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $order_type = empty($_REQUEST['order_type']) ? 'order' : trim($_REQUEST['order_type']);

            $action_id = AdminAction::where('action_code', 'supply_and_demand')->value('action_id');
            $action_id = $action_id ? $action_id : 0;

            if ($order_type == 'order' || empty($action_id)) {
                $ids = OrderInfo::whereRaw(1);
            } else {
                $ids = WholesaleOrderInfo::whereRaw(1);
            }

            $order_ids = array();
            if (!empty($order_id)) {
                $order_ids[] = $order_id;
            }
            if (!empty($order_sn)) {
                $order_sn = $this->baseRepository->getExplode($order_sn);
                $ids = $ids->whereIn('order_sn', $order_sn);
                $ids = $this->baseRepository->getToArrayGet($ids);
                $ids = $this->baseRepository->getKeyPluck($ids, 'order_id');

                $order_ids = $ids ? array_merge($order_ids, $ids) : $order_ids;
                $order_ids = $order_ids ? array_unique($order_ids) : [];
            }

            $this->smarty->assign('web_url', url('/') . '/');
            $this->smarty->assign('order_type', $order_type);

            $part_html = array();
            if ($order_ids) {
                foreach ($order_ids as $order_id) {
                    $order_info = $this->tpApiManageService->printOrderInfo($order_id, $order_type);
                    $this->smarty->assign('order_info', $order_info);
                    $part_html[] = $this->smarty->fetch('library/order_print_part.lbi');
                }
            }

            $this->smarty->assign('part_html', $part_html);

            /* 显示模板 */
            $content = $this->smarty->fetch('library/order_print.lbi');
            return make_json_result($content);
        }
    }

    /**
     * 获取电子面单设置列表
     *
     * @param int $suppliers_id
     * @return array
     */
    private function get_order_print_setting($suppliers_id = 0)
    {
        /* 过滤查询 */
        $filter = array();

        $filter['suppliers_id'] = isset($_REQUEST['suppliers_id']) && !empty($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : $suppliers_id;
        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sort_order' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $row = WholesaleOrderPrintSetting::whereRaw(1);

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $row = $row->where(function ($query) use ($filter) {
                $query->where('specification', 'like', '%' . mysql_like_quote($filter['keyword']) . '%')
                    ->orWhere('printer', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
            });
        }

        if ($suppliers_id > 0) {
            $row = $row->where('suppliers_id', $suppliers_id);
        }

        $res = $record_count = $row;

        /* 获得总记录数据 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [
            'list' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }
}
