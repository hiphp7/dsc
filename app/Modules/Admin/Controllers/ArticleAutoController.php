<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AutoManage;
use App\Repositories\Common\BaseRepository;
use App\Services\Cron\CronService;
use App\Services\Other\ArticleAutoManageService;

/**
 * 程序说明
 */
class ArticleAutoController extends InitController
{
    protected $baseRepository;
    protected $articleAutoManageService;
    protected $cronService;

    public function __construct(
        BaseRepository $baseRepository,
        ArticleAutoManageService $articleAutoManageService,
        CronService $cronService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->articleAutoManageService = $articleAutoManageService;
        $this->cronService = $cronService;
    }

    public function index()
    {
        admin_priv('article_auto');
        $this->smarty->assign('thisfile', 'article_auto.php');
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('auto_type', 1);
            $goodsdb = $this->articleAutoManageService->getAutoGoods();

            $crons_enable = $this->cronService->getManageOpen();

            $this->smarty->assign('crons_enable', $crons_enable);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['article_auto']);
            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);
            $this->smarty->assign('article_type', 1);
            $this->smarty->assign('action', 'article_auto');

            return $this->smarty->display('goods_auto.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $goodsdb = $this->articleAutoManageService->getAutoGoods();
            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);

            $sort_flag = sort_flag($goodsdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $this->smarty->assign('action', 'article_auto');

            return make_json_result($this->smarty->fetch('goods_auto.dwt'), '', ['filter' => $goodsdb['filter'], 'page_count' => $goodsdb['page_count']]);
        } elseif ($_REQUEST['act'] == 'del') {
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            AutoManage::where('item_id', $goods_id)
                ->where('type', 'article')
                ->delete();


            $links[] = ['text' => $GLOBALS['_LANG']['article_auto'], 'href' => 'article_auto.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        } //批量发布
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
                $id = intval($id);

                AutoManage::updateOrCreate(['item_id' => $id, 'type' => 'article',
                    'starttime' => $_POST['date']], ['starttime' => (string)$_POST['date']]);
            }

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'article_auto.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['batch_start_succeed'], 0, $lnk);
        } //批量取消发布
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
                $id = intval($id);

                AutoManage::updateOrCreate(['item_id' => $id, 'type' => 'article',
                    'endtime' => $_POST['date']], ['endtime' => (string)$_POST['date']]);
            }

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'article_auto.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['batch_end_succeed'], 0, $lnk);
        }
    }
}
