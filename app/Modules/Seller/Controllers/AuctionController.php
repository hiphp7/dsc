<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Models\AuctionLog;
use App\Models\GoodsActivity;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Other\AuctionManageService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心拍卖活动管理
 */
class AuctionController extends InitController
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $storeCommonService;
    protected $auctionManageService;
    protected $goodsProdutsService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        StoreCommonService $storeCommonService,
        AuctionManageService $auctionManageService,
        GoodsProdutsService $goodsProdutsService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
        $this->auctionManageService = $auctionManageService;
        $this->goodsProdutsService = $goodsProdutsService;
    }

    public function index()
    {
        load_helper('goods');

        $exc = new Exchange($this->dsc->table('goods_activity'), $this->db, 'act_id', 'act_name');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "bonus");
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));
        //ecmoban模板堂 --zhuo end

        $act = addslashes(trim(request()->input('act', '')));

        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '10_auction']);

        /*------------------------------------------------------ */
        //-- 活动列表页
        /*------------------------------------------------------ */

        if ($act == 'list') {
            /* 检查权限 */
            admin_priv('auction');

            /* 模板赋值 */
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['auction_list']);
            $this->smarty->assign('action_link', ['href' => 'auction.php?act=add', 'text' => $GLOBALS['_LANG']['add_auction'], 'class' => 'icon-plus']);

            $list = $this->auctionManageService->getAuctionList($adminru['ru_id']);

            //分页
            $page = (int)request()->input('page', 1);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('auction_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 显示商品列表页面 */

            return $this->smarty->display('auction_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 分页、排序、查询
        /*------------------------------------------------------ */

        elseif ($act == 'query') {
            $page = (int)request()->input('page', 1);
            $list = $this->auctionManageService->getAuctionList($adminru['ru_id']);

            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('auction_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('auction_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            $check_auth = check_authz_json('auction');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $auctionInfo = $this->auctionManageService->getAuctionInfo($id, false, "seller");

            if ($auctionInfo['user_id'] != $adminru['ru_id']) {
                $url = 'auction.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            if (empty($auctionInfo)) {
                return make_json_error($GLOBALS['_LANG']['auction_not_exist']);
            }
            if ($auctionInfo['bid_user_count'] > 0) {
                return make_json_error($GLOBALS['_LANG']['auction_cannot_remove']);
            }
            $name = $auctionInfo['act_name'];
            $exc->drop($id);

            /* 记日志 */
            admin_log($name, 'remove', 'auction');

            /* 清除缓存 */
            clear_cache_files();

            $url = 'auction.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($act == 'batch') {
            /* 取得要操作的记录编号 */
            $checkboxes = request()->input('checkboxes');
            if (empty($checkboxes)) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                /* 检查权限 */
                admin_priv('auction');

                $ids = $checkboxes;

                if (request()->exists('drop')) {
                    /* 查询哪些拍卖活动已经有人出价 */
                    $res = AuctionLog::whereIn('act_id', $ids);
                    $res = $this->baseRepository->getToArrayGet($res);
                    $res = $this->baseRepository->getKeyPluck($res, 'act_id');
                    $res = $res ? array_unique($res) : [];
                    $ids = $res ? array_diff($ids, $res) : $ids;
                    if (!empty($ids)) {
                        /* 删除记录 */
                        /* 删除记录 */
                        GoodsActivity::whereIn('act_id', $ids)
                            ->where('act_type', GAT_AUCTION)
                            ->delete();

                        /* 记日志 */
                        admin_log('', 'batch_remove', 'auction');

                        /* 清除缓存 */
                        clear_cache_files();
                    }
                    $links[] = ['text' => $GLOBALS['_LANG']['back_auction_list'], 'href' => 'auction.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 查看出价记录
        /*------------------------------------------------------ */

        elseif ($act == 'view_log') {
            /* 检查权限 */
            admin_priv('auction');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            /* 参数 */
            if (request()->exists('id')) {
                return sys_msg('invalid param');
            }

            $id = (int)request()->input('id', 0);
            $auctionInfo = $this->auctionManageService->getAuctionInfo($id, false, "seller");

            if (empty($auctionInfo)) {
                return sys_msg($GLOBALS['_LANG']['auction_not_exist']);
            }
            $this->smarty->assign('auction', $auctionInfo);

            /* 出价记录 */
            $this->smarty->assign('auction_log', auction_log($id));

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['auction_log']);
            $this->smarty->assign('action_link', ['href' => 'auction.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['auction_list'], 'class' => 'icon-reply']);

            return $this->smarty->display('auction_log.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */

        elseif ($act == 'add' || $act == 'edit') {
            /* 检查权限 */
            admin_priv('auction');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            /* 是否添加 */
            $is_add = $act == 'add';
            $this->smarty->assign('form_action', $is_add ? 'insert' : 'update');

            /* 初始化、取得拍卖活动信息 */
            if ($is_add) {
                $auctionInfo = [
                    'act_id' => 0,
                    'act_name' => '',
                    'act_desc' => '',
                    'act_promise' => '',
                    'act_ensure' => '',
                    'goods_id' => 0,
                    'product_id' => 0,
                    'goods_name' => $GLOBALS['_LANG']['pls_search_goods'],
                    'start_time' => date('Y-m-d H:i:s', time() + 86400),
                    'end_time' => date('Y-m-d H:i:s', time() + 4 * 86400),
                    'deposit' => 0,
                    'start_price' => 0,
                    'end_price' => 0,
                    'is_hot' => 0, //ecmoban模板堂 --zhuo
                    'amplitude' => 0
                ];
            } else {
                if (request()->has('id')) {
                    return sys_msg('invalid param');
                }

                $id = (int)request()->input('id', 0);
                $auctionInfo = $this->auctionManageService->getAuctionInfo($id, true, "seller");

                if ($auctionInfo['user_id'] != $adminru['ru_id']) {
                    $Loaction = "auction.php?act=list";
                    return dsc_header("Location: $Loaction\n");
                }

                if (empty($auctionInfo)) {
                    return sys_msg($GLOBALS['_LANG']['auction_not_exist']);
                }
                $auctionInfo['status'] = $GLOBALS['_LANG']['auction_status'][$auctionInfo['status_no']];
                $this->smarty->assign('bid_user_count', sprintf($GLOBALS['_LANG']['bid_user_count'], $auctionInfo['bid_user_count']));
            }
            /* 创建 html editor */
            create_html_editor2('act_desc', 'act_desc', $auctionInfo['act_desc']);
            create_html_editor2('act_promise', 'act_promise', $auctionInfo['act_promise']);
            create_html_editor2('act_ensure', 'act_ensure', $auctionInfo['act_ensure']);

            $this->smarty->assign('auction', $auctionInfo);

            /* 赋值时间控件的语言 */
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            /* 商品货品表 */
            $this->smarty->assign('good_products_select', get_good_products_select($auctionInfo['goods_id']));

            /* 显示模板 */
            if ($is_add) {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_auction']);
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_auction']);
            }
            $this->smarty->assign('action_link', $this->list_link($is_add));
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            return $this->smarty->display('auction_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑后提交
        /*------------------------------------------------------ */

        elseif ($act == 'insert' || $act == 'update') {
            /* 检查权限 */
            admin_priv('auction');

            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";
            $act_desc = request()->input('act_desc', '');
            $act_desc = $act_desc ? preg_replace($preg, "", stripslashes(trim($act_desc))) : '';
            $act_promise = request()->input('act_promise', '');
            $act_promise = $act_promise ? preg_replace($preg, "", stripslashes(trim($act_promise))) : '';
            $dact_ensure = request()->input('act_ensure', '');
            $dact_ensure = $dact_ensure ? preg_replace($preg, "", stripslashes(trim($dact_ensure))) : '';
            $act_name = request()->input('act_name');

            $is_hot = (int)request()->input('is_hot', 0);

            /* 是否添加 */
            $is_add = $act == 'insert';

            /* 检查是否选择了商品 */
            $goods_id = (int)request()->input('goods_id', 0);
            if ($goods_id <= 0) {
                return sys_msg($GLOBALS['_LANG']['pls_select_goods']);
            }
            $sql = "SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
            $row = $this->db->getRow($sql);
            if (empty($row)) {
                return sys_msg($GLOBALS['_LANG']['goods_not_exist']);
            }
            $goods_name = $row['goods_name'];

            /* 提交值 */
            $auctionInfo = [
                'act_id' => (int)request()->input('id', 0),
                'act_name' => empty($act_name) ? $goods_name : $this->dscRepository->subStr($act_name, 255, false),
                'act_desc' => $act_desc,
                'act_promise' => $act_promise,
                'act_ensure' => $dact_ensure,
                'act_type' => GAT_AUCTION,
                'goods_id' => $goods_id,
                'product_id' => (int)request()->input('product_id', 0),
                'user_id' => $adminru['ru_id'], //ecmoban模板堂 --zhuo
                'goods_name' => $goods_name,
                'start_time' => local_strtotime(request()->input('start_time', '')),
                'end_time' => local_strtotime(request()->input('end_time', '')),
                'user_id' => $adminru['ru_id'],
                'is_hot' => $is_hot,
                'ext_info' => serialize([
                    'deposit' => round(floatval(request()->input('deposit', 0)), 2),
                    'start_price' => round(floatval(request()->input('start_price', 0)), 2),
                    'end_price' => empty(request()->input('no_top', '')) ? round(floatval(request()->input('end_price', 0)), 2) : 0,
                    'amplitude' => round(floatval(request()->input('amplitude', 0)), 2),
                    'no_top' => request()->input('no_top', 0),
                    'is_hot' => $is_hot
                ])
            ];

            /* 保存数据 */
            if ($is_add) {
                $auctionInfo['is_finished'] = 0;
                $auctionInfo['act_id'] = GoodsActivity::insertGetId($auctionInfo);
            } else {
                $auctionInfo['review_status'] = 1;

                GoodsActivity::where('act_id', $auctionInfo['act_id'])->update($auctionInfo);
            }

            /* 记日志 */
            if ($is_add) {
                admin_log($auctionInfo['act_name'], 'add', 'auction');
            } else {
                admin_log($auctionInfo['act_name'], 'edit', 'auction');
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            if ($is_add) {
                $links = [
                    ['href' => 'auction.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_auction']],
                    ['href' => 'auction.php?act=list', 'text' => $GLOBALS['_LANG']['back_auction_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['add_auction_ok'], 0, $links);
            } else {
                $links = [
                    ['href' => 'auction.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_auction_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_auction_ok'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否热销
        /*------------------------------------------------------ */
        elseif ($act == 'toggle_hot') {
            $check_auth = check_authz_json('auction');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $val = (int)request()->input('val', 0);

            $exc->edit("is_hot = '$val'", $id);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 处理冻结资金
        /*------------------------------------------------------ */

        elseif ($act == 'settle_money') {
            /* 检查权限 */
            admin_priv('auction');

            /* 检查参数 */
            if (request()->has('id')) {
                return sys_msg('invalid param');
            }

            $id = (int)request()->input('id', 0);
            $auctionInfo = $this->auctionManageService->getAuctionInfo($id, false, "seller");

            if (empty($auctionInfo)) {
                return sys_msg($GLOBALS['_LANG']['auction_not_exist']);
            }

            $is_order = 0;
            if ($auctionInfo['status_no'] == SETTLED && $auctionInfo['order_count'] > 0) {
                $is_order = 1;
            }

            if ($auctionInfo['status_no'] != FINISHED && $is_order == 0) {
                return sys_msg($GLOBALS['_LANG']['invalid_status']);
            }

            if ($auctionInfo['deposit'] <= 0) {
                return sys_msg($GLOBALS['_LANG']['no_deposit']);
            }

            /* 处理保证金 */
            $exc->edit("is_finished = 2", $id); // 修改状态
            if (isset($_POST['unfreeze'])) {
                /* 解冻 */
                log_account_change(
                    $auctionInfo['last_bid']['bid_user'],
                    $auctionInfo['deposit'],
                    (-1) * $auctionInfo['deposit'],
                    0,
                    0,
                    sprintf($GLOBALS['_LANG']['unfreeze_auction_deposit'], $auctionInfo['act_name'])
                );
            } else {
                /* 扣除 */
                log_account_change(
                    $auctionInfo['last_bid']['bid_user'],
                    0,
                    (-1) * $auctionInfo['deposit'],
                    0,
                    0,
                    sprintf($GLOBALS['_LANG']['deduct_auction_deposit'], $auctionInfo['act_name'])
                );
            }

            /* 记日志 */
            admin_log($auctionInfo['act_name'], 'edit', 'auction');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'auction.php?act=edit&id=' . $id];
            return sys_msg($GLOBALS['_LANG']['settle_deposit_ok'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($act == 'search_goods') {
            $check_auth = check_authz_json('auction');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode(request()->input('JSON', ''));

            $arr['goods'] = get_goods_list($filter);

            if (!empty($arr['goods'][0]['goods_id'])) {
                $arr['products'] = $this->goodsProdutsService->getGoodProducts($arr['goods'][0]['goods_id']);
            }

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 搜索货品
        /*------------------------------------------------------ */

        elseif ($act == 'search_products') {

            $filters = dsc_decode(request()->input('JSON', ''));

            if (!empty($filters->goods_id)) {
                $arr['products'] = $this->goodsProdutsService->getGoodProducts($filters->goods_id);
            }

            return make_json_result($arr);
        }
    }


    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @param string $text 文字
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true, $text = '')
    {
        $href = 'auction.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }
        if ($text == '') {
            $text = $GLOBALS['_LANG']['auction_list'];
        }

        return ['href' => $href, 'text' => $text];
    }
}
