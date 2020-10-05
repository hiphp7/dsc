<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Models\GoodsTransportExtend;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use Illuminate\Support\Str;

/**
 * 管理中心商品运费模板
 */
class GoodsTransportController extends InitController
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $exc = new Exchange($this->dsc->table('goods_transport'), $this->db, 'tid', 'title');
        $exc_extend = new Exchange($this->dsc->table('goods_transport_extend'), $this->db, 'id', 'tid');
        $exc_express = new Exchange($this->dsc->table('goods_transport_express'), $this->db, 'id', 'tid');
        $adminru = get_admin_ru_id();
        $admin_id = get_admin_id();
        $this->smarty->assign('menu_select', ['action' => '11_system', 'current' => '03_shipping_list']);
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);

        /*------------------------------------------------------ */
        //-- 列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['03_shipping_list'], 'href' => 'shipping.php?act=list'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['shipping_transport'], 'href' => 'goods_transport.php?act=list'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $transport_list = $this->get_transport_list($adminru['ru_id']);
            $this->smarty->assign('transport_list', $transport_list['list']);
            $this->smarty->assign('filter', $transport_list['filter']);
            $this->smarty->assign('record_count', $transport_list['record_count']);
            $this->smarty->assign('page_count', $transport_list['page_count']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($transport_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_transport'], 'href' => 'goods_transport.php?act=add', 'class' => 'icon-plus']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['goods_transport']);


            return $this->smarty->display('goods_transport_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $transport_list = $this->get_transport_list($adminru['ru_id']);
            $this->smarty->assign('transport_list', $transport_list['list']);
            $this->smarty->assign('filter', $transport_list['filter']);
            $this->smarty->assign('record_count', $transport_list['record_count']);
            $this->smarty->assign('page_count', $transport_list['page_count']);
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($transport_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return make_json_result($this->smarty->fetch('goods_transport_list.dwt'), '', ['filter' => $transport_list['filter'], 'page_count' => $transport_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);

            $shipping_tpl = [];
            if ($_REQUEST['act'] == 'add') {
                $form_action = 'insert';

                $sql = "DELETE FROM" . $this->dsc->table("goods_transport_tpl") . "WHERE tid = 0 AND admin_id = '$admin_id'";
                $this->db->query($sql);
            } else {
                $form_action = 'update';
                if ($tid > 0) {
                    $shipping_tpl = get_transport_shipping_list($tid, $adminru['ru_id']);
                }
            }

            $this->smarty->assign('shipping_tpl', $shipping_tpl);
            $sql = " SELECT * FROM " . $this->dsc->table('goods_transport') . " WHERE tid = '$tid' ";
            $transport_info = $this->db->getRow($sql);
            $this->smarty->assign('form_action', $form_action);
            $this->smarty->assign('tid', $tid);
            $this->smarty->assign('transport_info', $transport_info);
            $area = $this->get_transport_area($tid);
            foreach ($area as $v) {
                if (empty($v['top_area_id']) || empty($v['area_id'])) {
                    $exc_extend->drop($v['id']);
                }
            }
            $express = $this->get_transport_express($tid);
            foreach ($express as $v) {
                if (empty($v['shipping_id'])) {
                    $exc_express->drop($v['id']);
                }
            }
            $this->smarty->assign('transport_area', $this->get_transport_area($tid));
            $this->smarty->assign('transport_express', $this->get_transport_express($tid));

            //快递列表
            $shipping_list = shipping_list();
            foreach ($shipping_list as $key => $val) {
                //剔除手机快递
                if (substr($val['shipping_code'], 0, 5) == 'ship_') {
                    unset($shipping_list[$key]);
                    continue;
                }
                //剔除上门自提
                if ($val['shipping_code'] == 'cac') {
                    unset($shipping_list[$key]);
                }
            }
            $this->smarty->assign('shipping_list', $shipping_list);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['transport_info']);
            $this->smarty->assign('action_link', ['href' => 'goods_transport.php?act=list', 'text' => $GLOBALS['_LANG']['goods_transport'], 'class' => 'icon-reply']);

            return $this->smarty->display('goods_transport_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $data = [];
            $data['tid'] = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $data['ru_id'] = $adminru['ru_id'];
            $data['type'] = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);
            $data['title'] = empty($_REQUEST['title']) ? '' : trim($_REQUEST['title']);
            $data['freight_type'] = empty($_REQUEST['freight_type']) ? 0 : intval($_REQUEST['freight_type']);
            $data['update_time'] = gmtime();
            $data['free_money'] = empty($_REQUEST['free_money']) ? 0 : floatval($_REQUEST['free_money']);
            $data['shipping_title'] = empty($_REQUEST['shipping_title']) ? 0 : trim($_REQUEST['shipping_title']);

            $s_tid = $data['tid'];

            //处理模板数据
            if ($_REQUEST['act'] == 'update') {
                $msg = "编辑成功";
                $this->db->autoExecute($this->dsc->table('goods_transport'), $data, "UPDATE", "tid = '$data[tid]'");
                $tid = $s_tid;

                $where = " tid = '$tid'";
            } else {
                $msg = "添加成功";
                $this->db->autoExecute($this->dsc->table('goods_transport'), $data, 'INSERT');
                $tid = $this->db->insert_id();
                $this->db->autoExecute($this->dsc->table('goods_transport_extend'), ['tid' => $tid], "UPDATE", "tid = '0' AND admin_id = '$admin_id' ");
                $this->db->autoExecute($this->dsc->table('goods_transport_express'), ['tid' => $tid], "UPDATE", "tid = '0' AND admin_id = '$admin_id' ");

                $where = " admin_id = '$admin_id' AND tid = 0";
            }

            //处理运费模板
            if ($data['freight_type'] > 0) {
                if (session()->has($s_tid . '.tpl_id') && empty(session()->get($s_tid . '.tpl_id'))) {
                    $sql = "SELECT GROUP_CONCAT(id) AS id FROM " . $this->dsc->table('goods_transport_tpl') . " WHERE " . $where;
                    $tpl_id = $this->db->getOne($sql);
                } else {
                    $tpl_id = session()->get($s_tid . '.tpl_id');
                }

                if (!empty($tpl_id)) {
                    $sql = "UPDATE" . $this->dsc->table("goods_transport_tpl") . " SET tid = '$tid' WHERE admin_id = '$admin_id' AND tid = 0 AND id " . db_create_in($tpl_id);
                    $this->db->query($sql);

                    session()->forget($s_tid . '.tpl_id');
                }
            }

            $_REQUEST['sprice'] = isset($_REQUEST['sprice']) && $_REQUEST['sprice'] ? $_REQUEST['sprice'] : [];
            $_REQUEST['shipping_fee'] = isset($_REQUEST['shipping_fee']) && $_REQUEST['shipping_fee'] ? $_REQUEST['shipping_fee'] : [];

            //处理地区数据
            if (count($_REQUEST['sprice']) > 0) {
                foreach ($_REQUEST['sprice'] as $key => $val) {
                    $info = [];
                    $info['sprice'] = $val;
                    $this->db->autoExecute($this->dsc->table('goods_transport_extend'), $info, "UPDATE", "id = '$key'");
                }
            }

            //处理快递数据
            if (count($_REQUEST['shipping_fee']) > 0) {
                foreach ($_REQUEST['shipping_fee'] as $key => $val) {
                    $info = [];
                    $info['shipping_fee'] = $val;
                    $this->db->autoExecute($this->dsc->table('goods_transport_express'), $info, "UPDATE", "id = '$key'");
                }
            }

            $links = [
                ['href' => 'goods_transport.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
            ];
            return sys_msg($msg, 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            //$check_auth = check_authz_json('goods_manage');
            if (isset($check_auth) && $check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['id']);
            $exc->drop($id);

            //删除拓展数据
            $sql = " DELETE FROM " . $this->dsc->table('goods_transport_extend') . " WHERE tid = '$id' ";
            $this->db->query($sql);

            $sql = " DELETE FROM " . $this->dsc->table('goods_transport_express') . " WHERE tid = '$id' ";
            $this->db->query($sql);

            $sql = " DELETE FROM " . $this->dsc->table('goods_transport_tpl') . " WHERE tid = '$id' ";
            $this->db->query($sql);

            $sql = " UPDATE " . $this->dsc->table('goods') . " SET tid = 0 WHERE tid = '$id' ";
            $this->db->query($sql);

            $url = 'goods_transport.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_drop') {
            if (isset($_POST['checkboxes'])) {
                $del_count = 0;
                foreach ($_POST['checkboxes'] as $key => $id) {
                    $id = !empty($id) ? intval($id) : 0;

                    $exc->drop($id);

                    //删除拓展数据
                    $sql = " DELETE FROM " . $this->dsc->table('goods_transport_extend') . " WHERE tid = '$id' ";
                    $this->db->query($sql);

                    $sql = " DELETE FROM " . $this->dsc->table('goods_transport_express') . " WHERE tid = '$id' ";
                    $this->db->query($sql);

                    $sql = " DELETE FROM " . $this->dsc->table('goods_transport_tpl') . " WHERE tid = '$id' ";
                    $this->db->query($sql);

                    $sql = " UPDATE " . $this->dsc->table('goods') . " SET tid = 0 WHERE tid = '$id' ";
                    $this->db->query($sql);

                    $del_count++;
                }
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_transport.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_transport.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_group_buy'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改标题
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_title') {

            $id = intval($_POST['id']);
            $title = json_str_iconv(trim($_POST['val']));

            if ($exc->edit("title = '$title', update_time=" . gmtime(), $id)) {
                return make_json_result(stripslashes($title));
            }
        }

        /*------------------------------------------------------ */
        //-- 添加地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_area') {
            $data = [];
            $data['tid'] = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $data['ru_id'] = $adminru['ru_id'];
            $data['admin_id'] = $admin_id;
            $this->db->autoExecute($this->dsc->table('goods_transport_extend'), $data, 'INSERT');

            $this->smarty->assign('transport_area', $this->get_transport_area($data['tid']));
            $html = $this->smarty->fetch('library/goods_transport_area.lbi');
            return make_json_result($html);
        }

        /*------------------------------------------------------ */
        //-- 删除地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_area') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = get_table_date("goods_transport_extend", "id='$id'", ['tid'], 2);
            $exc_extend->drop($id);

            $this->smarty->assign('transport_area', $this->get_transport_area($tid));
            $html = $this->smarty->fetch('library/goods_transport_area.lbi');
            return make_json_result($html);
        }

        /*------------------------------------------------------ */
        //-- 编辑地区运费
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_area_fee') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $sprice = empty($_REQUEST['fee']) ? 0 : (float)$_REQUEST['fee'];

            if ($exc_extend->edit("sprice = '$sprice'", $id)) {
                clear_cache_files();
                return make_json_result($sprice);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_area') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = get_table_date("goods_transport_extend", "id='$id'", ['tid'], 2);

            //已选省份
            $province_selected = get_table_date("goods_transport_extend", "id='$id'", ['top_area_id'], 2);
            $province_selected = explode(',', $province_selected);
            //已选城市
            $city_selected = get_table_date("goods_transport_extend", "id='$id'", ['area_id'], 2);
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
                    $city[$k]['is_selected'] = in_array($v['region_id'], $city_selected) ? 1 : 0;
                    $city[$k]['is_disabled'] = in_array($v['region_id'], $city_disabled) ? 1 : 0;
                    $child_num += in_array($v['region_id'], $city_selected) ? 1 : 0;
                    $other_num += in_array($v['region_id'], $city_disabled) ? 1 : 0;
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

        /*------------------------------------------------------ */
        //-- 保存地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_area') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = get_table_date("goods_transport_extend", "id='$id'", ['tid'], 2);

            $data = [];
            $data['area_id'] = empty($_REQUEST['area_id']) ? '' : trim($_REQUEST['area_id']);
            $data['top_area_id'] = empty($_REQUEST['top_area_id']) ? '' : trim($_REQUEST['top_area_id']);
            $this->db->autoExecute($this->dsc->table('goods_transport_extend'), $data, "UPDATE", "id = '$id'");
            $this->smarty->assign('transport_area', $this->get_transport_area($tid));
            $html = $this->smarty->fetch('library/goods_transport_area.lbi');
            return make_json_result($html);
        }

        /*------------------------------------------------------ */
        //-- 添加快递
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_express') {
            $data = [];
            $data['tid'] = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $data['ru_id'] = $adminru['ru_id'];
            $data['admin_id'] = $admin_id;
            $this->db->autoExecute($this->dsc->table('goods_transport_express'), $data, 'INSERT');

            $this->smarty->assign('transport_express', $this->get_transport_express($data['tid']));
            $html = $this->smarty->fetch('library/goods_transport_express.lbi');
            return make_json_result($html);
        }

        /*------------------------------------------------------ */
        //-- 删除快递
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_express') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = get_table_date("goods_transport_express", "id='$id'", ['tid'], 2);
            $exc_express->drop($id);

            $this->smarty->assign('transport_express', $this->get_transport_express($tid));
            $html = $this->smarty->fetch('library/goods_transport_express.lbi');
            return make_json_result($html);
        }

        /*------------------------------------------------------ */
        //-- 删除快递
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_express_fee') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $shipping_fee = empty($_REQUEST['fee']) ? 0 : (float)$_REQUEST['fee'];

            if ($exc_express->edit("shipping_fee = '$shipping_fee'", $id)) {
                clear_cache_files();
                return make_json_result($shipping_fee);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑快递
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_express') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = get_table_date("goods_transport_express", "id='$id'", ['tid'], 2);

            //已选快递
            $express_selected = get_table_date("goods_transport_express", "id='$id'", ['shipping_id'], 2);
            $express_selected = explode(',', $express_selected);
            //除自己以外的被选快递
            $where = "";
            if ($tid == 0) {
                $where .= " AND admin_id = '$admin_id' ";
            }
            $express_disabled = '';
            $sql = " SELECT shipping_id FROM " . $this->dsc->table('goods_transport_express') . " WHERE tid='$tid' $where AND id!='$id' ";
            $express_disabled = $this->db->getCol($sql);
            if ($express_disabled) {
                $express_disabled = implode(',', $express_disabled);
            }
            $express_disabled = explode(',', $express_disabled);

            //快递列表
            $shipping_list = shipping_list();
            foreach ($shipping_list as $k => $v) {

                /* 剔除上门自提 */
                if ($v['shipping_code'] == 'cac') {
                    unset($shipping_list[$k]);
                    continue;
                }

                $shipping_list[$k]['is_selected'] = in_array($v['shipping_id'], $express_selected) ? 1 : 0;
                $shipping_list[$k]['is_disabled'] = in_array($v['shipping_id'], $express_disabled) ? 1 : 0;
            }

            $this->smarty->assign('id', $id);
            $this->smarty->assign('shipping_list', $shipping_list);
            $html = $this->smarty->fetch('library/goods_transport_express_list.lbi');
            return make_json_result($html);
        }

        /*------------------------------------------------------ */
        //-- 保存快递
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_express') {
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $tid = get_table_date("goods_transport_express", "id='$id'", ['tid'], 2);

            $data = [];
            $data['shipping_id'] = empty($_REQUEST['shipping_id']) ? '' : trim($_REQUEST['shipping_id']);
            $this->db->autoExecute($this->dsc->table('goods_transport_express'), $data, "UPDATE", "id = '$id'");

            $this->smarty->assign('transport_express', $this->get_transport_express($tid));
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

            if (!empty($id)) {
                $where = "id = '$id'";
            } else {
                $where = "b.tid = '$tid' AND b.shipping_id = '$shipping_id' AND b.user_id = '" . $adminru['ru_id'] . "' AND id = 0";
            }

            //处理配置信息
            $sql = "SELECT a.shipping_name, a.shipping_code, a.support_cod, b.* " .
                " FROM " . $this->dsc->table('goods_transport_tpl') . " AS b " .
                " left join " . $this->dsc->table('shipping') . " AS a on a.shipping_id=b.shipping_id " .
                " WHERE $where LIMIT 1";
            $row = $this->db->getRow($sql);

            if (!empty($row)) {
                if ($row['shipping_code']) {
                    $modules = plugin_path('Shipping/' . Str::studly($row['shipping_code']) . '/config.php');
                    include_once($modules);
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
                $shipping = $this->db->getRow("SELECT shipping_name, shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id='$shipping_id'");

                $shipping_name = Str::studly($shipping['shipping_code']);
                $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');

                if (file_exists($modules)) {
                    $modules = include_once($modules);
                } else {
                    $modules = [];
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
                $sql = " SELECT region_id,region_name from " . $this->dsc->table('region') . " where region_id in (" . $row['region_id'] . ") ";
                $res = $this->db->query($sql);
                foreach ($res as $arr) {
                    $regions[$arr['region_id']] = $arr['region_name'];
                }
            }

            $this->smarty->assign('shipping_info', shipping_info($shipping_id, ['shipping_name']));
            $this->smarty->assign('countries', get_regions());
            $Province_list = get_regions(1, 1);
            $this->smarty->assign('province_all', $Province_list);
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

            $sql = "SELECT GROUP_CONCAT(region_id) AS region_id FROM " . $this->dsc->table('goods_transport_tpl') . " WHERE user_id = '" . $adminru['ru_id'] . "'" .
                " AND tid = '$tid' AND shipping_id = '$shipping_id'";
            $region_list = $this->db->getOne($sql);
            $region_list = !empty($region_list) ? explode(",", $region_list) : [];

            $sql = "SELECT GROUP_CONCAT(region_id) AS region_id FROM " . $this->dsc->table('region') . " WHERE 1";
            $region = $this->db->getOne($sql);
            $region = !empty($region) ? explode(",", $region) : [];

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

            $tpl_id = [];
            if ($shipping_id == 0 || empty($regionId)) {
                $result['error'] = 1;
                $result['message'] = lang('seller/goods_transport.incomplete_information');
                return response()->json($result);
            } else {
                $shipping_code = $this->db->getOne("SELECT shipping_code FROM " . $this->dsc->table('shipping') .
                    " WHERE shipping_id='$shipping_id'");

                $shipping_name = Str::studly($shipping_code);
                $plugin = plugin_path('Shipping/' . $shipping_name . '/' . $shipping_name . '.php');

                if (!file_exists($plugin)) {
                    $add_to_mess = $GLOBALS['_LANG']['not_find_plugin'];
                    $result['error'] = 1;
                    $result['message'] = $add_to_mess;
                    return response()->json($result);
                } else {
                    include_once($plugin);
                }

                $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');

                if (file_exists($modules)) {
                    $modules = include_once($modules);
                }

                $config = [];
                if ($modules) {
                    foreach ($modules['configure'] as $key => $val) {
                        $config[$key]['name'] = $val['name'];
                        $config[$key]['value'] = $_POST[$val['name']];
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
                }

                $other['tid'] = $tid;
                $other['shipping_id'] = $shipping_id;
                $other['region_id'] = $regionId;
                $other['configure'] = $config ? serialize($config) : '';
                $other['user_id'] = $adminru['ru_id'];
                $other['tpl_name'] = isset($_REQUEST['tpl_name']) && !empty($_REQUEST['tpl_name']) ? addslashes($_REQUEST['tpl_name']) : '';
                $sql = "SELECT count(*) FROM " . $this->dsc->table('goods_transport_tpl') . " WHERE id = '$id'";
                $res = $this->db->getOne($sql);
                if ($res > 0) {
                    $this->db->autoExecute($this->dsc->table('goods_transport_tpl'), $other, 'UPDATE', "id = '$id'");
                } else {
                    $other['admin_id'] = $admin_id;
                    $this->db->autoExecute($this->dsc->table('goods_transport_tpl'), $other, 'INSERT');
                    $tpl_id[] = $this->db->insert_id();
                }
                if ($regionId) {
                    $result['region_list'] = $this->get_area_list($regionId);
                }
            }

            if ($tpl_id && session()->has($tid . '.tpl_id') && !empty(session($tid . '.tpl_id'))) {
                $tpl_id = array_merge($tpl_id, session($tid . '.tpl_id'));
            }

            session()->put($tid . '.tpl_id', $tpl_id);

            $shipping_tpl = get_transport_shipping_list($tid, $adminru['ru_id']);
            $this->smarty->assign('shipping_tpl', $shipping_tpl);
            $html = $this->smarty->fetch('library/goods_transport_tpl.lbi');
            $result['content'] = $html;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除运费快递模板�        �送方式
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_shipping') {
            $result = ['error' => 0, 'message' => ''];

            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $sql = "DELETE FROM" . $this->dsc->table("goods_transport_tpl") . "WHERE id = '$id'";
            $this->db->query($sql);

            $shipping_tpl = get_transport_shipping_list($tid, $adminru['ru_id']);
            $this->smarty->assign('shipping_tpl', $shipping_tpl);
            $html = $this->smarty->fetch('library/goods_transport_tpl.lbi');
            $result['content'] = $html;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 查询运费快递模板�        �送方式地区是否存在
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

            $sql = "SELECT GROUP_CONCAT(region_id) AS region_id FROM " . $this->dsc->table('goods_transport_tpl') . " WHERE user_id = '" . $adminru['ru_id'] . "'" .
                " AND tid = '$tid' AND shipping_id = '$shipping_id'";
            $region_list = $this->db->getOne($sql);

            $region = !empty($region) ? explode(",", $region) : [];
            $region_list = !empty($region_list) ? explode(",", $region_list) : [];

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

    /* 模板列表 */
    private function get_transport_list($ru_id = 0)
    {
        /* 检查参数 */
        $where = " WHERE ru_id = '$ru_id' ";

        /* 初始化分页参数 */
        $filter = [
            'ru_id' => $ru_id
        ];

        /* 查询记录总数，计算分页数 */
        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods_transport') . $where;
        $filter['record_count'] = $this->db->getOne($sql);
        $filter = page_and_size($filter);

        /* 查询记录 */
        $sql = "SELECT * FROM " . $this->dsc->table('goods_transport') . $where .
            " ORDER BY tid DESC";
        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        $arr = [];
        foreach ($res as $row) {
            $row['update_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['update_time']);
            $arr[] = $row;
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /* 地区列表 */
    private function get_transport_area($tid = 0)
    {
        $admin_id = get_admin_id();

        $where = "";
        if ($tid == 0) {
            $where .= " AND admin_id = '$admin_id' ";
        }
        $sql = " SELECT * FROM " . $this->dsc->table('goods_transport_extend') . " WHERE tid = '$tid' $where ORDER BY id DESC ";
        $transport_area = $this->db->getAll($sql);
        foreach ($transport_area as $key => $val) {
            if (!empty($val['top_area_id']) && !empty($val['area_id'])) {
                $area_map = [];
                $top_area_arr = explode(',', $val['top_area_id']);
                foreach ($top_area_arr as $k => $v) {
                    $top_area = get_table_date("region", "region_id='$v'", ['region_name'], 2);
                    $sql = " SELECT region_name FROM " . $this->dsc->table('region') . " WHERE parent_id = '$v' AND region_id IN ($val[area_id]) ";
                    $area_arr = $this->db->getCol($sql);
                    $area_list = $area_arr ? implode(',', $area_arr) : '';
                    $area_map[$k]['top_area'] = $top_area;
                    $area_map[$k]['area_list'] = $area_list;
                }
                $transport_area[$key]['area_map'] = $area_map;
            }
        }
        return $transport_area;
    }

    /* 获取地区 */
    private function get_area_list($area_id = '')
    {
        $area_list = '';
        if (!empty($area_id)) {
            $sql = " SELECT region_name FROM " . $this->dsc->table('region') . " WHERE region_id IN ($area_id) ";
            $area_list = $this->db->getCol($sql);
            $area_list = implode(',', $area_list);
        }
        return $area_list;
    }

    /* 快递列表 */
    private function get_transport_express($tid = 0)
    {
        $admin_id = get_admin_id();

        $where = "";
        if ($tid == 0) {
            $where .= " AND admin_id = '$admin_id' ";
        }
        $sql = " SELECT * FROM " . $this->dsc->table('goods_transport_express') . " WHERE tid = '$tid' $where ORDER BY id DESC ";
        $transport_express = $this->db->getAll($sql);
        foreach ($transport_express as $key => $val) {
            $transport_express[$key]['express_list'] = $this->get_express_list($val['shipping_id']);
        }
        return $transport_express;
    }

    /* 获取快递 */
    private function get_express_list($shipping_id = '')
    {
        $express_list = '';
        if (!empty($shipping_id)) {
            $sql = " SELECT shipping_name FROM " . $this->dsc->table('shipping') . " WHERE shipping_id IN ($shipping_id) ";
            $express_list = $this->db->getCol($sql);
            $express_list = implode(',', $express_list);
        }
        return $express_list;
    }
}
