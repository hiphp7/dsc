<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 夺宝奇兵管理程序
 */
class SnatchController extends InitController
{
    protected $merchantCommonService;
    protected $storeCommonService;
    protected $goodsProdutsService;
    protected $dscRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        StoreCommonService $storeCommonService,
        GoodsProdutsService $goodsProdutsService,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->storeCommonService = $storeCommonService;
        $this->goodsProdutsService = $goodsProdutsService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $exc = new Exchange($this->dsc->table("goods_activity"), $this->db, 'act_id', 'act_name');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 活动列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_snatch_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['snatch_add'], 'href' => 'snatch.php?act=add']);

            $snatchs = $this->get_snatchlist($adminru['ru_id']);

            $this->smarty->assign('snatch_list', $snatchs['snatchs']);
            $this->smarty->assign('filter', $snatchs['filter']);
            $this->smarty->assign('record_count', $snatchs['record_count']);
            $this->smarty->assign('page_count', $snatchs['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($snatchs['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $this->smarty->assign('full_page', 1);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));


            return $this->smarty->display('snatch_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询、翻页、排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $snatchs = $this->get_snatchlist($adminru['ru_id']);

            $this->smarty->assign('snatch_list', $snatchs['snatchs']);
            $this->smarty->assign('filter', $snatchs['filter']);
            $this->smarty->assign('record_count', $snatchs['record_count']);
            $this->smarty->assign('page_count', $snatchs['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($snatchs['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('snatch_list.dwt'),
                '',
                ['filter' => $snatchs['filter'], 'page_count' => $snatchs['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            /* 初始化信息 */
            $start_time = local_date('Y-m-d H:i:s');
            $end_time = local_date('Y-m-d H:i:s', strtotime('+1 week'));
            $snatch = ['start_price' => '1.00', 'end_price' => '800.00', 'max_price' => '0', 'cost_points' => '1', 'start_time' => $start_time, 'end_time' => $end_time, 'option' => '<option value="0">' . $GLOBALS['_LANG']['make_option'] . '</option>'];

            /* 创建 html editor */
            $snatch['act_desc'] = isset($snatch['act_desc']) && !empty($snatch['act_desc']) ? $snatch['act_desc'] : '';
            $snatch['act_promise'] = isset($snatch['act_promise']) && !empty($snatch['act_promise']) ? $snatch['act_promise'] : '';
            $snatch['act_ensure'] = isset($snatch['act_ensure']) && !empty($snatch['act_ensure']) ? $snatch['act_ensure'] : '';

            create_html_editor2('act_desc', 'act_desc', $snatch['act_desc']);
            create_html_editor2('act_promise', 'act_promise', $snatch['act_promise']);
            create_html_editor2('act_ensure', 'act_ensure', $snatch['act_ensure']);

            $this->smarty->assign('snatch', $snatch);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['snatch_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_snatch_list'], 'href' => 'snatch.php?act=list']);
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            set_default_filter(); //设置默认筛选


            return $this->smarty->display('snatch_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            /* 检查商品是否存在 */
            $sql = "SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$_POST[goods_id]'";
            $_POST['goods_name'] = $this->db->GetOne($sql);
            if (empty($_POST['goods_name'])) {
                return sys_msg($GLOBALS['_LANG']['no_goods'], 1);
            }

            $sql = "SELECT COUNT(*) " .
                " FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_type='" . GAT_SNATCH . "' AND act_name='" . $_POST['snatch_name'] . "'";
            if ($this->db->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['snatch_name_exist'], $_POST['snatch_name']), 1);
            }

            $act_desc = isset($_POST['act_desc']) ? $_POST['act_desc'] : '';
            $act_promise = isset($_POST['act_promise']) ? $_POST['act_promise'] : '';
            $dact_ensure = isset($_POST['act_ensure']) ? $_POST['act_ensure'] : '';
            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['start_price'])) {
                $_POST['start_price'] = 0;
            }
            if (empty($_POST['end_price'])) {
                $_POST['end_price'] = 0;
            }
            if (empty($_POST['max_price'])) {
                $_POST['max_price'] = 0;
            }
            if (empty($_POST['cost_points'])) {
                $_POST['cost_points'] = 0;
            }
            if (isset($_POST['product_id']) && empty($_POST['product_id'])) {
                $_POST['product_id'] = 0;
            }

            $info = [
                'start_price' => $_POST['start_price'],
                'end_price' => $_POST['end_price'],
                'max_price' => $_POST['max_price'],
                'cost_points' => $_POST['cost_points']
            ];

            $_POST['desc'] = isset($_POST['desc']) && !empty($_POST['desc']) ? $_POST['desc'] : '';
            $_POST['product_id'] = isset($_POST['product_id']) && !empty($_POST['product_id']) ? $_POST['product_id'] : '';

            /* 插入数据 */
            $record = [
                'act_name' => $_POST['snatch_name'],
                'act_type' => GAT_SNATCH,
                'goods_id' => intval($_POST['goods_id']),
                'goods_name' => $_POST['goods_name'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'product_id' => intval($_POST['product_id']),
                'is_hot' => $_POST['is_hot'],
                'user_id' => $adminru['ru_id'],
                'act_desc' => $act_desc,
                'act_promise' => $act_promise,
                'act_ensure' => $dact_ensure,
                'review_status' => 3,
                'is_finished' => 0, 'ext_info' => serialize($info)
            ];

            $this->db->AutoExecute($this->dsc->table('goods_activity'), $record, 'INSERT');

            admin_log($_POST['snatch_name'], 'add', 'snatch');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'snatch.php?act=list'];
            $link[] = ['text' => $GLOBALS['_LANG']['continue_add'], 'href' => 'snatch.php?act=add'];
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
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

            $exc->edit("is_hot = '$val'", $id);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 编辑活动名称
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_snatch_name') {
            $check_auth = check_authz_json('snatch_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_type='" . GAT_SNATCH . "' AND act_name='$val' AND act_id <> '$id'";
            if ($this->db->getOne($sql)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['snatch_name_exist'], $val));
            }

            $exc->edit("act_name='$val'", $id);
            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 删除指定的活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('attr_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $exc->drop($id);

            $url = 'snatch.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            $act_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $snatch = $this->get_snatch_info($act_id);
            $snatch['option'] = '<option value="' . $snatch['goods_id'] . '">' . $snatch['goods_name'] . '</option>';
            $this->smarty->assign('snatch', $snatch);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['snatch_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_snatch_list'], 'href' => 'snatch.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('form_action', 'update');
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            /* 创建 html editor */
            create_html_editor2('act_desc', 'act_desc', $snatch['act_desc']);
            create_html_editor2('act_promise', 'act_promise', $snatch['act_promise']);
            create_html_editor2('act_ensure', 'act_ensure', $snatch['act_ensure']);
            /* 商品货品表 */
            $this->smarty->assign('good_products_select', get_good_products_select($snatch['goods_id']));

            set_default_filter(); //设置默认筛选


            return $this->smarty->display('snatch_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            $act_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $act_desc = isset($_POST['act_desc']) ? $_POST['act_desc'] : '';
            $act_promise = isset($_POST['act_promise']) ? $_POST['act_promise'] : '';
            $dact_ensure = isset($_POST['act_ensure']) ? $_POST['act_ensure'] : '';
            $_POST['goods_id'] = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;

            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['snatch_name'])) {
                $_POST['snatch_name'] = '';
            }
            if (empty($_POST['goods_id'])) {
                $_POST['goods_id'] = 0;
            } else {
                $_POST['goods_name'] = $this->db->getOne("SELECT goods_name FROM " . $this->dsc->table('goods') . "WHERE goods_id= '" . $_POST['goods_id'] . "'");
            }
            if (empty($_POST['start_price'])) {
                $_POST['start_price'] = 0;
            }
            if (empty($_POST['end_price'])) {
                $_POST['end_price'] = 0;
            }
            if (empty($_POST['max_price'])) {
                $_POST['max_price'] = 0;
            }
            if (empty($_POST['cost_points'])) {
                $_POST['cost_points'] = 0;
            }
            if (isset($_POST['product_id']) && empty($_POST['product_id'])) {
                $_POST['product_id'] = 0;
            }

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_type='" . GAT_SNATCH . "' AND act_name='" . $_POST['snatch_name'] . "' AND act_id <> '$act_id'";
            if ($this->db->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['snatch_name_exist'], $_POST['snatch_name']), 1);
            }

            $info = ['start_price' => $_POST['start_price'], 'end_price' => $_POST['end_price'], 'max_price' => $_POST['max_price'], 'cost_points' => $_POST['cost_points']];

            /* 更新数据 */
            $record = [
                'act_name' => $_POST['snatch_name'],
                'goods_id' => $_POST['goods_id'],
                'goods_name' => $_POST['goods_name'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'act_desc' => isset($_POST['desc']) ? $_POST['desc'] : '',
                'product_id' => isset($_POST['product_id']) ? $_POST['product_id'] : '',
                'is_hot' => $_POST['is_hot'],
                'act_desc' => $act_desc,
                'act_promise' => $act_promise,
                'act_ensure' => $dact_ensure,
                'ext_info' => serialize($info)
            ];

            if (isset($_POST['review_status'])) {
                $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                $record['review_status'] = $review_status;
                $record['review_content'] = $review_content;
            }

            $this->db->autoExecute($this->dsc->table('goods_activity'), $record, 'UPDATE', "act_id = '$act_id' AND act_type = " . GAT_SNATCH);

            admin_log($_POST['snatch_name'], 'edit', 'snatch');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'snatch.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 查看活动详情
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            $id = empty($_REQUEST['snatch_id']) ? 0 : intval($_REQUEST['snatch_id']);

            $bid_list = $this->get_snatch_detail();

            $this->smarty->assign('bid_list', $bid_list['bid']);
            $this->smarty->assign('filter', $bid_list['filter']);
            $this->smarty->assign('record_count', $bid_list['record_count']);
            $this->smarty->assign('page_count', $bid_list['page_count']);

            $sort_flag = sort_flag($bid_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            /* 赋值 */
            $this->smarty->assign('info', $this->get_snatch_info($id));
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('result', get_snatch_result($id));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['view_detail']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_snatch_list'], 'href' => 'snatch.php?act=list']);
            return $this->smarty->display('snatch_view.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、翻页活动详情
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query_bid') {
            $bid_list = $this->get_snatch_detail();

            $this->smarty->assign('bid_list', $bid_list['bid']);
            $this->smarty->assign('filter', $bid_list['filter']);
            $this->smarty->assign('record_count', $bid_list['record_count']);
            $this->smarty->assign('page_count', $bid_list['page_count']);

            $sort_flag = sort_flag($bid_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('snatch_view.dwt'),
                '',
                ['filter' => $bid_list['filter'], 'page_count' => $bid_list['page_count']]
            );
        }


        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('snatch_manage');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
            $ids = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
            $del_count = count($_POST['checkboxes']);

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    $sql = "DELETE FROM " . $this->dsc->table('goods_activity') .
                        " WHERE act_id " . db_create_in($ids);

                    if ($this->db->query($sql)) {
                        /*如果删除了夺宝活动，清除缓存*/
                        clear_cache_files();
                        $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'snatch.php?act=list'];
                        return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
                    }
                    /* 记日志 */
                    admin_log('', 'batch_remove', 'snatch_manage');
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];
                    $review_content = !empty($_POST['review_content']) ? trim($_POST['review_content']) : '';

                    $sql = "UPDATE " . $this->dsc->table('goods_activity') . " SET review_status = '$review_status' "
                        . " WHERE act_id " . db_create_in($ids);

                    if ($this->db->query($sql)) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'snatch.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['snatch_adopt_status_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {

            $filters = dsc_decode($_GET['JSON']);

            $arr['goods'] = get_goods_list($filters);

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

    /**
     * 获取活动列表
     *
     * @access  public
     *
     * @return array
     */
    private function get_snatchlist($ru_id)
    {
        $result = get_filter();
        if ($result === false) {
            /* 查询条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ga.act_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
            $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

            //卖场 start
            $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
            $adminru = get_admin_ru_id();
            if ($adminru['rs_id'] > 0) {
                $filter['rs_id'] = $adminru['rs_id'];
            }
            //卖场 end

            $where = (!empty($filter['keywords'])) ? " AND ga.act_name like '%" . mysql_like_quote($filter['keywords']) . "%'" : '';

            //ecmoban模板堂 --zhuo start
            if ($ru_id > 0) {
                $where .= " AND ga.user_id = '$ru_id' ";
            }
            //ecmoban模板堂 --zhuo end

            if ($filter['review_status']) {
                $where .= " AND ga.review_status = '" . $filter['review_status'] . "' ";
            }

            if ($filter['seller_list']) {
                $where .= " AND ga.user_id > 0 ";
            } else {
                $where .= " AND ga.user_id = 0 ";
            }

            //卖场
            $where .= get_rs_null_where('ga.user_id', $filter['rs_id']);

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] > -1) {
                if ($ru_id == 0) {
                    if ($filter['store_search'] > 0) {
                        $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                        if ($store_type) {
                            $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                        }

                        if ($filter['store_search'] == 1) {
                            $where .= " AND ga.user_id = '" . $filter['merchant_id'] . "' ";
                        } elseif ($filter['store_search'] == 2) {
                            $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                        } elseif ($filter['store_search'] == 3) {
                            $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                        }

                        if ($filter['store_search'] > 1) {
                            $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                                " WHERE msi.user_id = ga.user_id $store_where) > 0 ";
                        }
                    } else {
                        $where .= " AND ga.user_id = 0";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods_activity') . " AS ga " .
                " WHERE ga.act_type =" . GAT_SNATCH . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            $filter = page_and_size($filter);

            /* 获活动数据 */
            $sql = "SELECT ga.act_id, ga.act_name AS snatch_name, ga.goods_name, ga.start_time, ga.end_time, ga.is_finished, ga.ext_info, ga.product_id, ga.user_id, ga.is_hot, review_status, review_content " .
                " FROM " . $this->dsc->table('goods_activity') . " AS ga " .
                " WHERE ga.act_type = " . GAT_SNATCH . $where .
                " ORDER by $filter[sort_by] $filter[sort_order] LIMIT " . $filter['start'] . ", " . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $row = $this->db->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['start_time']);
            $row[$key]['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['end_time']);
            $info = unserialize($row[$key]['ext_info']);
            unset($row[$key]['ext_info']);
            if ($info) {
                foreach ($info as $info_key => $info_val) {
                    $row[$key][$info_key] = $info_val;
                }
            }

            $row[$key]['ru_name'] = $this->merchantCommonService->getShopName($val['user_id'], 1);
        }

        $arr = ['snatchs' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获取指定id snatch 的信息
     *
     * @access  public
     * @param int $id snatch_id
     *
     * @return array       array(snatch_id, snatch_name, goods_id,start_time, end_time, min_price, integral)
     */
    private function get_snatch_info($id)
    {
        $sql = "SELECT act_id, act_name AS snatch_name, user_id, goods_id, product_id, goods_name, start_time, end_time, act_desc, act_promise, act_ensure, ext_info, is_hot, review_status, review_content" .
            " FROM " . $this->dsc->table('goods_activity') .
            " WHERE act_id='$id' AND act_type = " . GAT_SNATCH;

        $snatch = $this->db->GetRow($sql);

        /* 将时间转成可阅读格式 */
        $snatch['start_time'] = local_date('Y-m-d H:i:s', $snatch['start_time']);
        $snatch['end_time'] = local_date('Y-m-d H:i:s', $snatch['end_time']);
        $row = unserialize($snatch['ext_info']);
        unset($snatch['ext_info']);
        if ($row) {
            foreach ($row as $key => $val) {
                $snatch[$key] = $val;
            }
        }

        return $snatch;
    }

    /**
     * 返回活动详细列表
     *
     * @access  public
     *
     * @return array
     */
    private function get_snatch_detail()
    {
        $filter['snatch_id'] = empty($_REQUEST['snatch_id']) ? 0 : intval($_REQUEST['snatch_id']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'bid_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = empty($filter['snatch_id']) ? '' : " WHERE snatch_id='$filter[snatch_id]'";

        /* 获得记录总数以及总页数 */
        $sql = "SELECT count(*) FROM " . $this->dsc->table('snatch_log') . $where;
        $filter['record_count'] = $this->db->getOne($sql);

        $filter = page_and_size($filter);

        /* 获得活动数据 */
        $sql = "SELECT s.log_id, u.user_name, s.bid_price, s.bid_time " .
            " FROM " . $this->dsc->table('snatch_log') . " AS s " .
            " LEFT JOIN " . $this->dsc->table('users') . " AS u ON s.user_id = u.user_id  " . $where .
            " ORDER by " . $filter['sort_by'] . " " . $filter['sort_order'] .
            " LIMIT " . $filter['start'] . ", " . $filter['page_size'];
        $row = $this->db->getAll($sql);


        foreach ($row as $key => $val) {
            $row[$key]['bid_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['bid_time']);

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$key]['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
            }
        }

        $arr = ['bid' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
