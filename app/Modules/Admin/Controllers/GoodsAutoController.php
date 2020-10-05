<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AutoManage;
use App\Repositories\Common\BaseRepository;
use App\Services\Cron\CronService;
use App\Services\Goods\GoodsAutoManageService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 程序说明
 */
class GoodsAutoController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $goodsAutoManageService;
    protected $cronService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GoodsAutoManageService $goodsAutoManageService,
        CronService $cronService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->goodsAutoManageService = $goodsAutoManageService;
        $this->cronService = $cronService;
    }

    public function index()
    {
        admin_priv('goods_auto');
        $this->smarty->assign('thisfile', 'goods_auto.php');

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
            $goodsdb = $this->goodsAutoManageService->getAutoGoods($adminru['ru_id']);

            $crons_enable = $this->cronService->getManageOpen();

            $this->smarty->assign('crons_enable', $crons_enable);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['goods_auto']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);
            $this->smarty->assign('action', 'goods_auto');

            return $this->smarty->display('goods_auto.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $goodsdb = $this->goodsAutoManageService->getAutoGoods($adminru['ru_id']);
            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);
            $this->smarty->assign('action', 'goods_auto');

            $sort_flag = sort_flag($goodsdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('goods_auto.dwt'), '', ['filter' => $goodsdb['filter'], 'page_count' => $goodsdb['page_count']]);
        } elseif ($_REQUEST['act'] == 'del') {
            $goods_id = (int)$_REQUEST['goods_id'];
            AutoManage::where('item_id', $goods_id)->where('type', 'goods')->delete();
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
}
