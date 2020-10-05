<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\CommentBaseline;
use App\Repositories\Common\BaseRepository;
use App\Services\Comment\CommentManageService;
use App\Services\CommentSeller\CommentSellerManageService;
use App\Services\Store\StoreCommonService;

/**
 * 用户评论管理程序
 */
class CommentSellerController extends InitController
{
    protected $commentManageService;
    protected $baseRepository;
    protected $storeCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        CommentManageService $commentManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->commentManageService = $commentManageService;
        $this->baseRepository = $baseRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {

        /*初始化数据交换对象 */
        $exc = new Exchange($this->dsc->table("comment_seller"), $this->db, 'sid', 'order_id');

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

        /*------------------------------------------------------ */
        //-- 满意度列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('comment_seller');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '13_comment_seller_rank']);
            $this->smarty->assign('action_link', ['href' => 'comment_seller.php?act=baseline', 'text' => $GLOBALS['_LANG']['seller_industry_baseline']]);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['comment_seller_rank']);
            $this->smarty->assign('full_page', 1);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $list = $this->commentManageService->commentSellerList();

            $this->smarty->assign('rank_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('comment_seller_rank.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、搜索、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            admin_priv('comment_seller');

            $list = $this->commentManageService->commentSellerList();

            $this->smarty->assign('rank_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('comment_seller_rank.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 设置商家评分基线
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'baseline') {
            /* 检查权限 */
            admin_priv('comment_seller');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '13_comment_seller_rank']);

            $this->smarty->assign('action_link', ['href' => 'comment_seller.php?act=list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['baseline_goods_alt'] . $GLOBALS['_LANG']['seller_industry_baseline']);
            $baseline = CommentBaseline::first();
            $this->smarty->assign('baseline', $baseline);
            $this->smarty->assign('form_action', 'insert_update');


            return $this->smarty->display('comment_baseline.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑商家评分基线
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_update') {
            /* 检查权限 */
            admin_priv('comment_seller');

            $other['goods'] = !empty($_REQUEST['goods_baseline']) ? trim($_REQUEST['goods_baseline']) : '';
            $other['service'] = !empty($_REQUEST['service_baseline']) ? trim($_REQUEST['service_baseline']) : '';
            $other['shipping'] = !empty($_REQUEST['shipping_baseline']) ? trim($_REQUEST['shipping_baseline']) : '';
            $res = CommentBaseline::first();
            if ($res) {
                CommentBaseline::where('id', $res->id)->update($other);
            } else {
                CommentBaseline::insert($other);
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'comment_seller.php?act=baseline'];
            return sys_msg($GLOBALS['_LANG']['success'], 0, $link);


            return $this->smarty->display('comment_baseline.dwt');
        }

        /*------------------------------------------------------ */
        //-- 删除满意度
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('comment_seller');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            if ($exc->drop($id)) {
                admin_log($id, 'remove', 'comment_seller');
                clear_cache_files();
            }

            $url = 'comment_seller.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
