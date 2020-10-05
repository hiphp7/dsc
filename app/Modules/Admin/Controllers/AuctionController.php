<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\AuctionLog;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Other\AuctionManageService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心拍卖活动管理
 */
class AuctionController extends InitController
{
    protected $auctionManageService;
    protected $baseRepository;
    protected $dscRepository;
    protected $storeCommonService;
    protected $goodsProdutsService;

    public function __construct(
        AuctionManageService $auctionManageService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService,
        GoodsProdutsService $goodsProdutsService
    )
    {
        $this->auctionManageService = $auctionManageService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
        $this->goodsProdutsService = $goodsProdutsService;
    }

    public function index()
    {
        load_helper('goods');

        $exc = new Exchange($this->dsc->table('goods_activity'), $this->db, 'act_id', 'act_name');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 活动列表页
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('auction');

            /* 模板赋值 */
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['auction_list']);
            $this->smarty->assign('action_link', ['href' => 'auction.php?act=add', 'text' => $GLOBALS['_LANG']['add_auction']]);

            $list = $this->auctionManageService->getAuctionList($adminru['ru_id']);

            $this->smarty->assign('auction_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            /* 显示商品列表页面 */

            return $this->smarty->display('auction_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 分页、排序、查询
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->auctionManageService->getAuctionList($adminru['ru_id']);

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
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('auction');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $auctionInfo = $this->auctionManageService->getAuctionInfo($id, false, "seller");

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
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            $check_auth = check_authz_json('auction');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
            $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    /* 查询哪些拍卖活动已经有人出价 */
                    $res = AuctionLog::whereIn('act_id', $ids);
                    $res = $this->baseRepository->getToArrayGet($res);
                    $res = $this->baseRepository->getKeyPluck($res, 'act_id');
                    $res = $res ? array_unique($res) : [];
                    $ids = $res ? array_diff($ids, $res) : $ids;

                    if (!empty($ids)) {
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
                } // 审核
                elseif ($_POST['type'] == 'review_to') {

                    // review_status = 3审核通过 2审核未通过
                    $review_status = isset($_POST['review_status']) && !empty($_POST['review_status']) ? intval($_POST['review_status']) : 0;

                    GoodsActivity::whereIn('act_id', $ids)
                        ->update([
                            'review_status' => $review_status
                        ]);

                    $lnk[] = ['text' => $GLOBALS['_LANG']['back_auction_list'], 'href' => 'auction.php?act=list&seller_list=1&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['auction_audited_set_ok'], 0, $lnk);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 查看出价记录
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'view_log') {
            /* 检查权限 */
            admin_priv('auction');

            /* 参数 */
            if (empty($_GET['id'])) {
                return sys_msg('invalid param');
            }

            $id = intval($_GET['id']);
            $auctionInfo = $this->auctionManageService->getAuctionInfo($id, false, "seller");

            if (empty($auctionInfo)) {
                return sys_msg($GLOBALS['_LANG']['auction_not_exist']);
            }
            $this->smarty->assign('auction', $auctionInfo);

            /* 出价记录 */
            $this->smarty->assign('auction_log', auction_log($id, 0, 0));

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['auction_log']);
            $this->smarty->assign('action_link', ['href' => 'auction.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['auction_list']]);

            return $this->smarty->display('auction_log.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('auction');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'add';
            $this->smarty->assign('form_action', $is_add ? 'insert' : 'update');
            $standard_time = local_date("Y-m-d H:i:s", strtotime("-1 day"));
            $this->smarty->assign("standard_time", $standard_time);
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
                if (empty($_GET['id'])) {
                    return sys_msg('invalid param');
                }

                $id = intval($_GET['id']);
                $auctionInfo = $this->auctionManageService->getAuctionInfo($id, true, "seller");

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

            set_default_filter(); //设置默认筛选

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

            $this->smarty->assign('action_link', $this->auctionManageService->getListLink($is_add));
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            return $this->smarty->display('auction_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑后提交
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('auction');

            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";

            $act_desc = isset($_POST['act_desc']) ? preg_replace($preg, "", stripslashes(trim($_POST['act_desc']))) : '';
            $act_promise = isset($_POST['act_promise']) ? preg_replace($preg, "", stripslashes(trim($_POST['act_promise']))) : '';
            $dact_ensure = isset($_POST['act_ensure']) ? preg_replace($preg, "", stripslashes(trim($_POST['act_ensure']))) : '';
            $is_hot = isset($_POST['is_hot']) && !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'insert';

            /* 检查是否选择了商品 */
            $goods_id = intval($_POST['goods_id']);
            if ($goods_id <= 0) {
                return sys_msg($GLOBALS['_LANG']['pls_select_goods']);
            }

            $row = Goods::where('goods_id', $goods_id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if (empty($row)) {
                return sys_msg($GLOBALS['_LANG']['goods_not_exist']);
            }

            $goods_name = $row['goods_name'] ?? '';

            $adminru = get_admin_ru_id();

            /* 提交值 */
            $auctionInfo = [
                'act_id' => intval($_POST['id']),
                'act_name' => empty($_POST['act_name']) ? $goods_name : $this->dscRepository->subStr($_POST['act_name'], 255, false),
                'act_desc' => $act_desc,
                'act_promise' => $act_promise,
                'act_ensure' => $dact_ensure,
                'act_type' => GAT_AUCTION,
                'goods_id' => $goods_id,
                'product_id' => empty($_POST['product_id']) ? 0 : $_POST['product_id'],
                'goods_name' => $goods_name,
                'start_time' => local_strtotime($_POST['start_time']),
                'end_time' => local_strtotime($_POST['end_time']),
                'review_status' => 3,
                'is_hot' => $is_hot,
                'ext_info' => serialize([
                    'deposit' => round(floatval($_POST['deposit']), 2),
                    'start_price' => round(floatval($_POST['start_price']), 2),
                    'end_price' => empty($_POST['no_top']) ? round(floatval($_POST['end_price']), 2) : 0,
                    'amplitude' => round(floatval($_POST['amplitude']), 2),
                    //by wang start修改
                    'no_top' => !empty($_POST['no_top']) ? intval($_POST['no_top']) : 0,
                    'is_hot' => $is_hot
                    //by wang end修改
                ])
            ];

            /* 保存数据 */
            if ($is_add) {
                $auctionInfo['user_id'] = $adminru['ru_id'];
                $auctionInfo['is_finished'] = 0;

                $auctionInfo['act_id'] = GoodsActivity::insertGetId($auctionInfo);

                /* 记日志 */
                admin_log($auctionInfo['act_name'], 'add', 'auction');
            } else {
                if (isset($_POST['review_status'])) {
                    $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                    $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                    $auctionInfo['review_status'] = $review_status;
                    $auctionInfo['review_content'] = $review_content;
                }

                GoodsActivity::where('act_id', $auctionInfo['act_id'])->update($auctionInfo);

                /* 记日志 */
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
        elseif ($_REQUEST['act'] == 'toggle_hot') {
            $check_auth = check_authz_json('auction');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            //获得序列化数据,修改其中的is_hot，并更新
            $ext_info = GoodsActivity::where('act_id', $id)->value('ext_info');
            $ext_info = $ext_info ? unserialize($ext_info) : [];

            $ext_info['is_hot'] = $val;
            $ext_info = serialize($ext_info);

            GoodsActivity::where('act_id', $id)
                ->update([
                    'is_hot' => $val,
                    'ext_info' => $ext_info
                ]);

            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 处理冻结资金
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'settle_money') {
            /* 检查权限 */
            admin_priv('auction');

            /* 检查参数 */
            if (empty($_POST['id'])) {
                return sys_msg('invalid param');
            }

            $id = intval($_POST['id']);
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
            GoodsActivity::where('act_id', $id)
                ->update([
                    'is_finished' => 2
                ]);

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

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {
            $check_auth = check_authz_json('auction');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $filter = dsc_decode($_GET['JSON']);
            $arr['goods'] = get_goods_list($filter);

            if (!empty($arr['goods'][0]['goods_id'])) {
                $arr['products'] = $this->goodsProdutsService->getGoodProducts($arr['goods'][0]['goods_id']);
            }

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 搜索货品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_products') {

            $filters = dsc_decode($_GET['JSON']);

            if (!empty($filters->goods_id)) {
                $arr['products'] = $this->goodsProdutsService->getGoodProducts($filters->goods_id);
            }

            return make_json_result($arr);
        }
    }
}
