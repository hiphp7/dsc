<?php

namespace App\Modules\Seller\Controllers;

use App\Services\Cron\CronService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 程序说明
 */
class GoodsAutoController extends InitController
{
    protected $merchantCommonService;
    protected $cronService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        CronService $cronService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->cronService = $cronService;
    }

    public function index()
    {
        admin_priv('goods_auto');

        $this->smarty->assign('thisfile', 'goods_auto.php');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "goods");
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => 'goods_auto']);
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('current', 'goods_auto_list');
            $goodsdb = $this->get_auto_goods($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($goodsdb, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $crons_enable = $this->cronService->getManageOpen();

            $this->smarty->assign('crons_enable', $crons_enable);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['goods_auto']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);

            return $this->smarty->display('goods_auto.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $goodsdb = $this->get_auto_goods($adminru['ru_id']);

            //分页
            $page_count_arr = seller_page($goodsdb, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);

            $sort_flag = sort_flag($goodsdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('goods_auto.dwt'), '', ['filter' => $goodsdb['filter'], 'page_count' => $goodsdb['page_count']]);
        } elseif ($_REQUEST['act'] == 'del') {
            $goods_id = (int)$_REQUEST['goods_id'];
            $sql = "DELETE FROM " . $this->dsc->table('auto_manage') . " WHERE item_id = '$goods_id' AND type = 'goods'";
            $this->db->query($sql);
            $links[] = ['text' => $GLOBALS['_LANG']['goods_auto'], 'href' => 'goods_auto.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        } elseif ($_REQUEST['act'] == 'edit_starttime') {
            $check_auth = check_authz_json('goods_auto');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_POST['val']))) {
                return make_json_error('');
            }

            $id = intval($_POST['id']);
            $time = local_strtotime(trim($_POST['val']));
            if ($id <= 0 || $_POST['val'] == '0000-00-00' || $time <= 0) {
                return make_json_error('');
            }

            $this->db->autoReplace($this->dsc->table('auto_manage'), ['item_id' => $id, 'type' => 'goods',
                'starttime' => $time], ['starttime' => (string)$time]);

            clear_cache_files();
            return make_json_result(stripslashes($_POST['val']), '', ['act' => 'goods_auto', 'id' => $id]);
        } elseif ($_REQUEST['act'] == 'edit_endtime') {
            $check_auth = check_authz_json('goods_auto');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_POST['val']))) {
                return make_json_error('');
            }

            $id = intval($_POST['id']);
            $time = local_strtotime(trim($_POST['val']));
            if ($id <= 0 || $_POST['val'] == '0000-00-00' || $time <= 0) {
                return make_json_error('');
            }

            $this->db->autoReplace($this->dsc->table('auto_manage'), ['item_id' => $id, 'type' => 'goods',
                'endtime' => $time], ['endtime' => (string)$time]);

            clear_cache_files();
            return make_json_result(stripslashes($_POST['val']), '', ['act' => 'goods_auto', 'id' => $id]);
        } //批量上架
        elseif ($_REQUEST['act'] == 'batch_start') {
            admin_priv('goods_auto');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_goods'], 1);
            }

            if ($_POST['date'] == '0000-00-00') {
                $_POST['date'] = 0;
            } else {
                $_POST['date'] = local_strtotime(trim($_POST['date']));
            }

            foreach ($_POST['checkboxes'] as $id) {
                $this->db->autoReplace($this->dsc->table('auto_manage'), ['item_id' => $id, 'type' => 'goods',
                    'starttime' => $_POST['date']], ['starttime' => (string)$_POST['date']]);
            }

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_auto.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['batch_start_succeed'], 0, $lnk);
        } //批量下架
        elseif ($_REQUEST['act'] == 'batch_end') {
            admin_priv('goods_auto');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_goods'], 1);
            }

            if ($_POST['date'] == '0000-00-00') {
                $_POST['date'] = 0;
            } else {
                $_POST['date'] = local_strtotime(trim($_POST['date']));
            }

            foreach ($_POST['checkboxes'] as $id) {
                $this->db->autoReplace($this->dsc->table('auto_manage'), ['item_id' => $id, 'type' => 'goods',
                    'endtime' => $_POST['date']], ['endtime' => (string)$_POST['date']]);
            }

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_auto.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['batch_end_succeed'], 0, $lnk);
        }
    }

    private function get_auto_goods($ru_id)
    {
        $where = ' WHERE g.is_delete <> 1 ';

        //ecmoban模板堂 --zhuo start
        if ($ru_id > 0) {
            $where .= " and g.user_id = '$ru_id' ";
        }
        //ecmoban模板堂 --zhuo end

        if (!empty($_POST['goods_name'])) {
            $goods_name = trim($_POST['goods_name']);
            $where .= " AND g.goods_name LIKE '%$goods_name%'";
            $filter['goods_name'] = $goods_name;
        }

        $result = get_filter();

        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'last_update' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods') . " g" . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT g.*,a.starttime,a.endtime FROM " . $this->dsc->table('goods') . " g LEFT JOIN " . $this->dsc->table('auto_manage') . " a ON g.goods_id = a.item_id AND a.type='goods'" . $where .
                " ORDER by goods_id, " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $query = $this->db->query($sql);

        $goodsdb = [];

        foreach ($query as $rt) {
            if (!empty($rt['starttime'])) {
                $rt['starttime'] = local_date('Y-m-d', $rt['starttime']);
            }
            if (!empty($rt['endtime'])) {
                $rt['endtime'] = local_date('Y-m-d', $rt['endtime']);
            }

            $rt['user_name'] = $this->merchantCommonService->getShopName($rt['user_id'], 1);

            $goodsdb[] = $rt;
        }

        $arr = ['goodsdb' => $goodsdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
