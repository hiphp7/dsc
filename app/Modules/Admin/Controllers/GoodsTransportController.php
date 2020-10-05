<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Goods;
use App\Models\GoodsTransport;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\GoodsTransportTpl;
use App\Models\Region;
use App\Models\Shipping;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsTransportManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;
use Illuminate\Support\Str;

/**
 * 管理中心商品运费模板
 */
class GoodsTransportController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $goodsTransportManageService;
    protected $dscRepository;
    protected $storeCommonService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GoodsTransportManageService $goodsTransportManageService,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->goodsTransportManageService = $goodsTransportManageService;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('order');

        $admin_id = get_admin_id();
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $tid = isset($_REQUEST['tid']) && !empty($_REQUEST['tid']) ? intval($_REQUEST['tid']) : 0;
        if ($tid) {
            $trow = get_goods_transport($tid);
            $adminru['ru_id'] = $trow['ru_id'];
        }

        /* ------------------------------------------------------ */
        //-- 列表
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => '04_shipping_transport']);

            $ru_id = $adminru['ru_id'];
            $transport_list = $this->goodsTransportManageService->getTransportList($ru_id);
            $this->smarty->assign('transport_list', $transport_list['list']);
            $this->smarty->assign('filter', $transport_list['filter']);
            $this->smarty->assign('record_count', $transport_list['record_count']);
            $this->smarty->assign('page_count', $transport_list['page_count']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['goods_transport']);

            return $this->smarty->display('goods_transport_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $ru_id = $adminru['ru_id'];
            $transport_list = $this->goodsTransportManageService->getTransportList($ru_id);
            $this->smarty->assign('transport_list', $transport_list['list']);
            $this->smarty->assign('filter', $transport_list['filter']);
            $this->smarty->assign('record_count', $transport_list['record_count']);
            $this->smarty->assign('page_count', $transport_list['page_count']);

            return make_json_result($this->smarty->fetch('goods_transport_list.dwt'), '', ['filter' => $transport_list['filter'], 'page_count' => $transport_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 添加、编辑
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);

            $ru_id = GoodsTransport::where('tid', $tid)->value('ru_id');
            $ru_id = $ru_id ? $ru_id : 0;

            if (empty($tid)) {
                $ru_id = $adminru['ru_id'];
            }

            $transport_info = [];
            $shipping_tpl = [];
            if ($_REQUEST['act'] == 'add') {
                $form_action = 'insert';

                GoodsTransportTpl::where('tid', 0)->where('admin_id', $admin_id)->delete();
            } else {
                $form_action = 'update';
                if ($tid > 0) {
                    $transport_info = $trow;
                    $shipping_tpl = get_transport_shipping_list($tid, $ru_id);
                }
            }

            $this->smarty->assign('shipping_tpl', $shipping_tpl);
            $this->smarty->assign('form_action', $form_action);
            $this->smarty->assign('tid', $tid);
            $this->smarty->assign('transport_info', $transport_info);
            $this->smarty->assign('transport_area', $this->goodsTransportManageService->getTransportArea($tid));
            $this->smarty->assign('transport_express', $this->goodsTransportManageService->getTransportExpress($tid));

            $row = [
                'shipping_code' => ''
            ];

            //快递列表
            $shipping_list = shipping_list();
            foreach ($shipping_list as $key => $val) {
                //剔除手机快递
                if (substr($row['shipping_code'], 0, 5) == 'ship_') {
                    unset($shipping_list[$key]);
                    continue;
                }
                /* 剔除上门自提 */
                if ($val['shipping_code'] == 'cac') {
                    unset($shipping_list[$key]);
                }
            }

            $this->smarty->assign('shipping_list', $shipping_list);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['transport_info']);
            $this->smarty->assign('action_link', ['href' => 'goods_transport.php?act=list', 'text' => $GLOBALS['_LANG']['goods_transport']]);

            return $this->smarty->display('goods_transport_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 处理
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $data = [];
            $data['tid'] = !isset($_REQUEST['tid']) && empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $data['ru_id'] = $adminru['ru_id'];
            $data['type'] = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);
            $data['title'] = empty($_REQUEST['title']) ? '' : trim($_REQUEST['title']);
            $data['freight_type'] = empty($_REQUEST['freight_type']) ? 0 : intval($_REQUEST['freight_type']);
            $data['update_time'] = gmtime();
            $data['free_money'] = empty($_REQUEST['free_money']) ? 0 : floatval($_REQUEST['free_money']);
            $data['shipping_title'] = empty($_REQUEST['shipping_title']) ? 0 : trim($_REQUEST['shipping_title']);

            $s_tid = $data['tid'];

            //处理模板数据
            $res = GoodsTransportTpl::select('id');
            if ($_REQUEST['act'] == 'update') {
                $msg = lang('admin/goods_transport.edit_success');
                GoodsTransport::where('tid', $data['tid'])->update($data);
                $tid = $s_tid;

                $res = $res->where('tid', $tid);
            } else {
                $msg = lang('admin/goods_transport.add_success');
                $tid = GoodsTransport::insertGetId($data);

                $gte_data = ['tid' => $tid];
                GoodsTransportExtend::where('tid', 0)->where('admin_id', $admin_id)->update($gte_data);
                GoodsTransportExpress::where('tid', 0)->where('admin_id', $admin_id)->update($gte_data);

                $res = $res->where('admin_id', $admin_id)->where('tid', 0);
            }

            //处理运费模板
            if ($data['freight_type'] > 0) {

                if (!session()->has($s_tid . '.tpl_id') && empty(session($s_tid . '.tpl_id'))) {
                    $tpl_id = $this->baseRepository->getToArrayGet($res);
                    $tpl_id = $this->baseRepository->getFlatten($tpl_id);
                } else {
                    $tpl_id = session($s_tid . '.tpl_id');
                }

                if (!empty($tpl_id)) {
                    $tpl_id = $this->baseRepository->getExplode($tpl_id);
                    $gtt_data = ['tid' => $tid];
                    GoodsTransportTpl::where('admin_id', $admin_id)->where('tid', 0)->whereIn('id', $tpl_id)->update($gtt_data);

                    session()->forget($s_tid . '.tpl_id');
                }
            }

            $_REQUEST['sprice'] = isset($_REQUEST['sprice']) && $_REQUEST['sprice'] ? $_REQUEST['sprice'] : [];
            $_REQUEST['shipping_fee'] = isset($_REQUEST['shipping_fee']) && $_REQUEST['shipping_fee'] ? $_REQUEST['shipping_fee'] : [];

            //处理地区数据
            if ($_REQUEST['sprice'] && count($_REQUEST['sprice']) > 0) {
                foreach ($_REQUEST['sprice'] as $key => $val) {
                    $info = [];
                    $info['sprice'] = $val;
                    GoodsTransportExtend::where('id', $key)->update($info);
                }
            }

            //处理快递数据
            if ($_REQUEST['shipping_fee'] && count($_REQUEST['shipping_fee']) > 0) {
                foreach ($_REQUEST['shipping_fee'] as $key => $val) {
                    $info = [];
                    $info['shipping_fee'] = $val;
                    GoodsTransportExpress::where('id', $key)->update($info);
                }
            }

            $links = [
                ['href' => 'goods_transport.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
            ];
            return sys_msg($msg, 0, $links);
        }

        /* ------------------------------------------------------ */
        //-- 删除
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /*$check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }*/

            $id = intval($_REQUEST['id']);
            GoodsTransport::where('tid', $id)->delete();

            //删除拓展数据
            GoodsTransportExtend::where('tid', $id)->delete();

            GoodsTransportExpress::where('tid', $id)->delete();

            GoodsTransportTpl::where('tid', $id)->delete();

            $data = ['tid' => 0];
            Goods::where('tid', $id)->update($data);

            $url = 'goods_transport.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /* ------------------------------------------------------ */
        //-- 批量删除
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_drop') {
            if (isset($_POST['checkboxes'])) {
                $del_count = 0;
                foreach ($_POST['checkboxes'] as $key => $id) {
                    $id = !empty($id) ? intval($id) : 0;

                    GoodsTransport::where('tid', $id)->delete();

                    //删除拓展数据
                    GoodsTransportExtend::where('tid', $id)->delete();

                    GoodsTransportExpress::where('tid', $id)->delete();

                    GoodsTransportTpl::where('tid', $id)->delete();

                    $data = ['tid' => 0];
                    Goods::where('tid', $id)->update($data);

                    $del_count++;
                }
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_transport.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_transport.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_group_buy'], 0, $links);
            }
        }

        /* ------------------------------------------------------ */
        //-- 修改标题
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_title') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $title = json_str_iconv(trim($_POST['val']));

            $data = [
                'title' => $title,
                'update_time' => gmtime()
            ];
            $res = GoodsTransport::where('tid', $id)->update($data);
            if ($res > 0) {
                return make_json_result(stripslashes($title));
            }
        }

        /* ------------------------------------------------------ */
        //-- 添加地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_area') {
            $data = [];
            $data['tid'] = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $data['ru_id'] = $adminru['ru_id'];
            $data['admin_id'] = $admin_id;

            GoodsTransportExtend::insert($data);

            $this->smarty->assign('transport_area', $this->goodsTransportManageService->getTransportArea($data['tid']));
            $html = $this->smarty->fetch('library/goods_transport_area.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 删除地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_area') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = GoodsTransportExtend::where('id', $id)->value('tid');
            $tid = $tid ? $tid : 0;

            GoodsTransportExtend::where('id', $id)->delete();

            $this->smarty->assign('transport_area', $this->goodsTransportManageService->getTransportArea($tid));
            $html = $this->smarty->fetch('library/goods_transport_area.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 编辑地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_area') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $tid = GoodsTransportExtend::where('id', $id)->value('tid');
            $tid = $tid ? $tid : 0;
            //已选省份
            $province_selected = GoodsTransportExtend::where('id', $id)->value('top_area_id');
            $province_selected = $province_selected ? $province_selected : '';

            $province_selected = explode(',', $province_selected);
            //已选城市
            $city_selected = GoodsTransportExtend::where('id', $id)->value('area_id');
            $city_selected = $city_selected ? $city_selected : '';
            $city_selected = explode(',', $city_selected);
            //除自己以外的被选城市

            $city_disabled = GoodsTransportExtend::where('tid', $tid)
                ->where('id', '<>', $id);

            if ($tid == 0) {
                $city_disabled = $city_disabled->where('admin_id', $admin_id);
            }

            $city_disabled = $this->baseRepository->getToArrayGet($city_disabled);
            $city_disabled = $this->baseRepository->getKeyPluck($city_disabled, 'area_id');

            $list = [];
            foreach ($city_disabled as $key => $row) {
                $list[] = $row ? explode(',', $row) : [];
            }

            $city_disabled = $this->baseRepository->getFlatten($list);
            $city_disabled = $city_disabled ? array_unique($city_disabled) : [];

            //地区列表
            $province = get_regions(1, 1); //省
            foreach ($province as $key => $val) {
                $child_num = 0; //自选城市
                $other_num = 0; //他选城市
                $province[$key]['is_selected'] = in_array($val['region_id'], $province_selected) ? 1 : 0;
                $city = get_regions(2, $val['region_id']); //市
                foreach ($city as $k => $v) {
                    $city[$k]['is_selected'] = $city_selected && in_array($v['region_id'], $city_selected) ? 1 : 0;
                    $city[$k]['is_disabled'] = $city_disabled && in_array($v['region_id'], $city_disabled) ? 1 : 0;
                    $child_num += $city_selected && in_array($v['region_id'], $city_selected) ? 1 : 0;
                    $other_num += $city_disabled && in_array($v['region_id'], $city_disabled) ? 1 : 0;
                }
                $province[$key]['child'] = $city;
                $province[$key]['child_num'] = $child_num;
                $province[$key]['is_disabled'] = (count($city) == ($child_num + $other_num)) ? 1 : 0;
            }

            $this->smarty->assign('id', $id);
            $this->smarty->assign('area_map', $province);
            $html = $this->smarty->fetch('library/goods_transport_area_list.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 保存地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_area') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = GoodsTransportExtend::where('id', $id)->value('tid');
            $tid = $tid ? $tid : 0;
            $data = [];
            $data['area_id'] = empty($_REQUEST['area_id']) ? '' : trim($_REQUEST['area_id']);
            $data['top_area_id'] = empty($_REQUEST['top_area_id']) ? '' : trim($_REQUEST['top_area_id']);

            GoodsTransportExtend::where('id', $id)->update($data);

            $this->smarty->assign('transport_area', $this->goodsTransportManageService->getTransportArea($tid));
            $html = $this->smarty->fetch('library/goods_transport_area.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 保存价格
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_sprice') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $data = [];
            $data['sprice'] = empty($_REQUEST['sprice']) ? '' : trim($_REQUEST['sprice']);

            $return = GoodsTransportExtend::where('id', $id)->update($data);

            return make_json_result($return);
        }

        /* ------------------------------------------------------ */
        //-- 保存快递方式价格
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_shipping_fee') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $data = [];
            $data['shipping_fee'] = empty($_REQUEST['sprice']) ? '' : trim($_REQUEST['sprice']);

            $return = GoodsTransportExpress::where('id', $id)->update($data);

            return make_json_result($return);
        }

        /* ------------------------------------------------------ */
        //-- 添加快递
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_express') {
            $data = [];
            $data['tid'] = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $data['ru_id'] = $adminru['ru_id'];
            $data['admin_id'] = $admin_id;

            GoodsTransportExpress::insert($data);

            $this->smarty->assign('transport_express', $this->goodsTransportManageService->getTransportExpress($data['tid']));
            $html = $this->smarty->fetch('library/goods_transport_express.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 删除快递
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_express') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = GoodsTransportExpress::where('id', $id)->value('tid');
            $tid = $tid ? $tid : 0;

            GoodsTransportExpress::where('id', $id)->delete();

            $this->smarty->assign('transport_express', $this->goodsTransportManageService->getTransportExpress($tid));
            $html = $this->smarty->fetch('library/goods_transport_express.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 编辑快递
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_express') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $transportExpress = GoodsTransportExpress::where('id', $id)->first();

            $tid = $transportExpress['tid'] ?? 0;
            $seller_id = $transportExpress['ru_id'] ?? 0;

            //已选快递
            $express_selected = $transportExpress['shipping_id'] ?? '';
            $express_selected = $express_selected ? explode(',', $express_selected) : [];

            //除自己以外的被选快递
            $res = GoodsTransportExpress::select('shipping_id')
                ->where('tid', $tid)
                ->where('id', '<>', $id);
            if ($tid == 0) {
                $res = $res->where('admin_id', $admin_id);
            }

            $express_disabled = $this->baseRepository->getToArrayGet($res);
            $express_disabled = $this->baseRepository->getFlatten($express_disabled);

            //快递列表
            $is_cac = true;
            $shipping_list = shipping_list($is_cac);
            foreach ($shipping_list as $k => $v) {

                if ($seller_id > 0 && $is_cac == true) {
                    /* 剔除上门自提 */
                    if ($v['shipping_code'] == 'cac') {
                        unset($shipping_list[$k]);
                        continue;
                    }
                }

                $shipping_list[$k]['is_selected'] = $express_selected && in_array($v['shipping_id'], $express_selected) ? 1 : 0;
                $shipping_list[$k]['is_disabled'] = $express_disabled && in_array($v['shipping_id'], $express_disabled) ? 1 : 0;
            }

            $this->smarty->assign('id', $id);
            $this->smarty->assign('shipping_list', $shipping_list);
            $html = $this->smarty->fetch('library/goods_transport_express_list.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 保存快递
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_express') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = GoodsTransportExpress::where('id', $id)->value('tid');
            $tid = $tid ? $tid : 0;
            $data = [];
            $data['shipping_id'] = empty($_REQUEST['shipping_id']) ? '' : trim($_REQUEST['shipping_id']);

            GoodsTransportExpress::where('id', $id)->update($data);

            $this->smarty->assign('transport_express', $this->goodsTransportManageService->getTransportExpress($tid));
            $html = $this->smarty->fetch('library/goods_transport_express.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 获取快递模板信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_shipping_tem') {
            $shipping_id = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $res = GoodsTransportTpl::whereRaw(1);
            if (!empty($id)) {
                $res = $res->where('id', $id);
            } else {
                $res = $res->where('tid', $tid)
                    ->where('shipping_id', $shipping_id)
                    ->where('user_id', $adminru['ru_id'])
                    ->where('id', 0);
            }

            //处理配置信息
            $res = $res->with(['getShipping' => function ($query) {
                $query->select('shipping_id', 'shipping_name', 'shipping_code', 'support_cod');
            }]);

            $row = $this->baseRepository->getToArrayFirst($res);

            if (!empty($row)) {

                $row['shipping_name'] = '';
                $row['shipping_code'] = '';
                $row['support_cod'] = '';
                if (isset($row['get_shipping']) && !empty($row['get_shipping'])) {
                    $row['shipping_name'] = $row['get_shipping']['shipping_name'];
                    $row['shipping_code'] = $row['get_shipping']['shipping_code'];
                    $row['support_cod'] = $row['get_shipping']['support_cod'];
                }

                if ($row['shipping_code']) {
                    include_once(plugin_path('Shipping/' . Str::studly($row['shipping_code']) . '/config.php'));
                }

                $fields = unserialize($row['configure']);
                /* 如果配送方式支持货到付款并且没有设置货到付款支付费用，则加入货到付款费用 */
                if ($row['support_cod'] && $fields[count($fields) - 1]['name'] != 'pay_fee') {
                    $fields[] = ['name' => 'pay_fee', 'value' => 0];
                }

                foreach ($fields as $key => $val) {
                    /* 替换更改的语言项 */
                    if ($val['name'] == 'basic_fee') {
                        $val['name'] = 'base_fee';
                    }
                    if ($val['name'] == 'item_fee') {
                        $item_fee = 1;
                    }
                    if ($val['name'] == 'fee_compute_mode') {
                        $this->smarty->assign('fee_compute_mode', $val['value']);
                        unset($fields[$key]);
                    } else {
                        $fields[$key]['name'] = $val['name'];
                        $fields[$key]['label'] = $GLOBALS['_LANG'][$val['name']];
                    }
                }

                if (empty($item_fee)) {
                    $field = ['name' => 'item_fee', 'value' => '0', 'label' => empty($GLOBALS['_LANG']['item_fee']) ? '' : $GLOBALS['_LANG']['item_fee']];
                    array_unshift($fields, $field);
                }
                $this->smarty->assign('shipping_area', $row);
            } else {
                $res = Shipping::where('shipping_id', $shipping_id);
                $shipping = $this->baseRepository->getToArrayFirst($res);
                $modules = [];
                if ($shipping['shipping_code']) {
                    $modules = include_once(plugin_path('Shipping/' . Str::studly($shipping['shipping_code']) . '/config.php'));
                }

                $fields = [];
                if ($modules && $modules['configure']) {
                    foreach ($modules['configure'] as $key => $val) {
                        $fields[$key]['name'] = $val['name'];
                        $fields[$key]['value'] = $val['value'];
                        $fields[$key]['label'] = $GLOBALS['_LANG'][$val['name']];
                    }
                }

                $count = count($fields);
                $fields[$count]['name'] = "free_money";
                $fields[$count]['value'] = "0";
                $fields[$count]['label'] = $GLOBALS['_LANG']["free_money"];

                /* 如果支持货到付款，则允许设置货到付款支付费用 */
                if ($modules && $modules['cod']) {
                    $count++;
                    $fields[$count]['name'] = "pay_fee";
                    $fields[$count]['value'] = "0";
                    $fields[$count]['label'] = $GLOBALS['_LANG']['pay_fee'];
                }

                $shipping_area['shipping_id'] = 0;
                $shipping_area['free_money'] = 0;
                $this->smarty->assign('shipping_area', ['shipping_id' => $_REQUEST['shipping_id'], 'shipping_code' => $shipping['shipping_code']]);
            }
            $this->smarty->assign('fields', $fields);

            $return_data = isset($return_data) ? $return_data : '';

            $this->smarty->assign('return_data', $return_data);
            /* 获得该区域下的所有地区 */
            $regions = [];
            if (!empty($row['region_id'])) {
                $region_id = $this->baseRepository->getExplode($row['region_id']);
                $res = Region::whereIn('region_id', $region_id);
                $res = $this->baseRepository->getToArrayGet($res);
                foreach ($res as $arr) {
                    $regions[$arr['region_id']] = $arr['region_name'];
                }
            }

            $this->smarty->assign('shipping_info', shipping_info($shipping_id, ['shipping_name']));
            $Province_list = get_regions(1, 1);
            $this->smarty->assign('Province_list', $Province_list);
            $this->smarty->assign('regions', $regions);
            $this->smarty->assign('tpl_info', $row);
            $this->smarty->assign('tid', $tid);
            $this->smarty->assign('shipping_id', $shipping_id);
            $this->smarty->assign('id', $id);
            $html = $this->smarty->fetch('library/shipping_tab.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 获取地区列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'the_national') {
            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $shipping_id = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);

            $regions = get_the_national();

            $res = GoodsTransportTpl::select('region_id')
                ->where('user_id', $adminru['ru_id'])
                ->where('tid', $tid)
                ->where('shipping_id', $shipping_id);
            $region_list = $this->baseRepository->getToArrayGet($res);
            $region_list = $this->baseRepository->getFlatten($region_list);
            $region_list = $region_list ? $region_list : [];

            $res = Region::select('region_id');
            $region = $this->baseRepository->getToArrayGet($res);
            $region = $this->baseRepository->getFlatten($region);
            $region = $region ? $region : [];

            $assoc = [];
            if ($region && $region_list) {
                $assoc = array_intersect($region, $region_list);
            }

            if ($assoc) {
                $regions = [];
            }

            $this->smarty->assign('regions', $regions);
            $html = $this->smarty->fetch('library/shipping_the_national.lbi');
            return make_json_result($html);
        }

        /* ------------------------------------------------------ */
        //-- 添加运费快递模板地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_shipping_tpl') {
            $result = ['error' => 0, 'message' => ''];
            $rId = empty($_REQUEST['regions']) ? '' : implode(',', $_REQUEST['regions']);
            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $regionId = $rId;
            $shipping_id = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);

            $ru_id = GoodsTransport::where('tid', $tid)->value('ru_id');
            $ru_id = $ru_id ? $ru_id : 0;

            if (empty($tid)) {
                $ru_id = $adminru['ru_id'];
            }

            $tpl_id = [];
            if ($shipping_id == 0 || empty($regionId)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['info_fill_complete'];
                return response()->json($result);
            } else {
                $shipping_code = Shipping::where('shipping_id', $shipping_id)->value('shipping_code');
                $shipping_code = $shipping_code ? $shipping_code : '';

                $shipping_code = Str::studly($shipping_code);
                $plugin = plugin_path('Shipping/' . $shipping_code . "/config.php");

                if (!file_exists($plugin)) {
                    $add_to_mess = $GLOBALS['_LANG']['not_find_plugin'];
                    $result['error'] = 1;
                    $result['message'] = $add_to_mess;
                    return response()->json($result);
                } else {
                    $modules = include_once($plugin);
                }
                $config = [];

                if ($modules && $modules['configure']) {
                    foreach ($modules['configure'] as $key => $val) {
                        $config[$key]['name'] = $val['name'];
                        $config[$key]['value'] = $_POST[$val['name']];
                    }
                }

                $count = count($config);
                $config[$count]['name'] = 'free_money';
                $config[$count]['value'] = empty($_POST['free_money']) ? '' : $_POST['free_money'];
                $count++;
                $config[$count]['name'] = 'fee_compute_mode';
                $config[$count]['value'] = empty($_POST['fee_compute_mode']) ? '' : $_POST['fee_compute_mode'];
                /* 如果支持货到付款，则允许设置货到付款支付费用 */
                if ($modules['cod']) {
                    $count++;
                    $config[$count]['name'] = 'pay_fee';
                    $config[$count]['value'] = make_semiangle(empty($_POST['pay_fee']) ? '' : $_POST['pay_fee']);
                }

                $other['tid'] = $tid;
                $other['shipping_id'] = $shipping_id;
                $other['region_id'] = $regionId;
                $other['configure'] = serialize($config);
                $other['user_id'] = $ru_id;
                $other['tpl_name'] = isset($_REQUEST['tpl_name']) && !empty($_REQUEST['tpl_name']) ? addslashes($_REQUEST['tpl_name']) : '';

                $res = GoodsTransportTpl::where('id', $id)->count();
                if ($res > 0) {
                    GoodsTransportTpl::where('id', $id)->update($other);
                } else {
                    $other['admin_id'] = $admin_id;
                    $tpl_id[] = GoodsTransportTpl::insertGetId($other);
                }
                if ($regionId) {
                    $result['region_list'] = $this->goodsTransportManageService->getAreaList($regionId);
                }
            }

            if ($tpl_id && session()->has($tid . '.tpl_id') && !empty(session($tid . '.tpl_id'))) {
                $tpl_id = array_merge($tpl_id, session($tid . '.tpl_id'));
            }

            session()->put($tid . '.tpl_id', $tpl_id);

            $shipping_tpl = get_transport_shipping_list($tid, $ru_id);
            $this->smarty->assign('shipping_tpl', $shipping_tpl);
            $html = $this->smarty->fetch('library/goods_transport_tpl.lbi');
            $result['content'] = $html;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除运费快递模板配送方式
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_shipping') {
            $result = ['error' => 0, 'message' => ''];

            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            GoodsTransportTpl::where('id', $id)->delete();

            $shipping_tpl = get_transport_shipping_list($tid, $adminru['ru_id']);
            $this->smarty->assign('shipping_tpl', $shipping_tpl);
            $html = $this->smarty->fetch('library/goods_transport_tpl.lbi');
            $result['content'] = $html;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 查询运费快递模板配送方式地区是否存在
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'select_area') {
            $result = ['error' => 0, 'message' => ''];

            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $shipping_id = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
            $region_id = empty($_REQUEST['region_id']) ? 0 : intval($_REQUEST['region_id']);

            $parent_id = region_parent($region_id);
            $region_children = region_children($region_id);

            $region = $region_id . "," . $parent_id . "," . $region_children;
            $region = $this->dscRepository->delStrComma($region);

            $res = GoodsTransportTpl::select('region_id')
                ->where('user_id', $adminru['ru_id'])
                ->where('tid', $tid)
                ->where('shipping_id', $shipping_id);
            $region_list = $this->baseRepository->getToArrayGet($res);
            $region_list = $this->baseRepository->getFlatten($region_list);
            $region = !empty($region) ? explode(",", $region) : [];

            $assoc = [];
            if ($region && $region_list) {
                $assoc = array_intersect($region, $region_list);
            }

            if ($assoc) {
                $result['error'] = 1;
            }

            return response()->json($result);
        }
    }
}
