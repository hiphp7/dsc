<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\WholesaleProducts;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsManageService;
use App\Services\Store\StoreCommonService;
use App\Services\Wholesale\GoodsManageService as WholesaleGoodsManage;
use App\Services\Wholesale\GoodsService;

/**
 * 地区切换程序
 */
class WholesaleController extends InitController
{
    protected $wholesaleGoodsManage;
    protected $dscRepository;
    protected $goodsManageService;
    protected $goodsService;
    protected $storeCommonService;

    public function __construct(
        WholesaleGoodsManage $wholesaleGoodsManage,
        DscRepository $dscRepository,
        GoodsManageService $goodsManageService,
        GoodsService $goodsService,
        StoreCommonService $storeCommonService
    )
    {
        $this->wholesaleGoodsManage = $wholesaleGoodsManage;
        $this->dscRepository = $dscRepository;
        $this->goodsManageService = $goodsManageService;
        $this->goodsService = $goodsService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper(['wholesale', 'visual', 'goods']);
        load_helper(['goods'], 'admin');

        $this->dscRepository->helpersLang(['goods'], 'admin');

        $exc = new Exchange($this->dsc->table('wholesale'), $this->db, 'goods_id', 'goods_name');

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $admin_id = get_admin_id();
        /*------------------------------------------------------ */
        //-- 活动列表页
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            $standard_goods = !empty($_REQUEST['standard_goods']) ? intval($_REQUEST['standard_goods']) : 0;

            if ($standard_goods == 1) {
                admin_priv('standard_goods_lib');
            } else {
                admin_priv('suppliers_goods_list');
            }

            $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wholesale_goods_attr') . " WHERE goods_id = 0 AND admin_id = '" . session('admin_id') . "'";
            $GLOBALS['db']->query($sql);

            /* 模板赋值 */
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_wholesale']);
            $this->smarty->assign('action_link', array('href' => 'wholesale.php?act=add', 'text' => $GLOBALS['_LANG']['add_wholesale']));
            $this->smarty->assign('action_link2', array('href' => 'wholesale.php?act=batch_add', 'text' => $GLOBALS['_LANG']['add_batch_wholesale']));

            $list = $this->wholesaleGoodsManage->getWholesaleList();
            $this->smarty->assign('goods_list', $list['goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('standard_goods', $standard_goods);

            $intro_list = $this->goodsManageService->getIntroList();
            $this->smarty->assign('intro_list', $intro_list);
            set_default_filter(0, 0, 0, 0, 'wholesale_cat'); //设置默认筛选

            /* 供货商名 */
            $suppliers_list_name = suppliers_list_name();
            $suppliers_exists = 1;
            if (empty($suppliers_list_name)) {
                $suppliers_exists = 0;
            }
            $this->smarty->assign('suppliers_exists', $suppliers_exists);
            $this->smarty->assign('suppliers_list_name', $suppliers_list_name);
            $this->smarty->assign('action', 'wholesale');

            /* 显示商品列表页面 */
            if ($standard_goods > 0) {
                $html = "goods_lib_list.dwt";
            } else {
                $html = "wholesale_list.dwt";
            }

            return $this->smarty->display($html);
        }

        /*------------------------------------------------------ */
        //-- 分页、排序、查询
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $list = $this->wholesaleGoodsManage->getWholesaleList();
            $this->smarty->assign('goods_list', $list['goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            if ($list['filter']['standard_goods'] > 0) {
                $html = "goods_lib_list.dwt";
            } else {
                $html = "wholesale_list.dwt";
            }

            return make_json_result($this->smarty->fetch($html), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 修改商品名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_name') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_name = json_str_iconv(trim($_POST['val']));

            if ($exc->edit("goods_name = '$goods_name', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result(stripslashes($goods_name));
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_price') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_price = floatval($_POST['val']);

            if ($goods_price < 0 || $goods_price == 0 && $_POST['val'] != "$goods_price") {
                return make_json_error($GLOBALS['_LANG']['shop_price_invalid']);
            } else {
                if ($exc->edit("goods_price = '$goods_price', last_update=" . gmtime(), $goods_id)) {
                    clear_cache_files();
                    return make_json_result(number_format($goods_price, 2, '.', ''));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品库存数量
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_number') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_num = intval($_POST['val']);

            if ($goods_num < 0 || $goods_num == 0 && $_POST['val'] != "$goods_num") {
                return make_json_error($GLOBALS['_LANG']['goods_number_error']);
            }

            $object = WholesaleProducts::whereRaw(1);
            $exist = $this->goodsManageService->checkGoodsProductExist($object, $goods_id);
            if ($exist == 1) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_goods_number']);
            }

            if ($exc->edit("goods_number = '$goods_num', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($goods_num);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $sort_order = intval($_POST['val']);

            if ($exc->edit("sort_order = '$sort_order', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($sort_order);
            }
        }
        /*------------------------------------------------------ */
        //-- 修改商品最小起订量
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_moq') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $moq = intval($_POST['val']);

            if ($exc->edit("moq = '$moq', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($moq);
            }
        }
        /*------------------------------------------------------ */
        //-- 商品设置分享
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'standard_goods') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['id']);
            if ($exc->edit("standard_goods = 1", $goods_id)) {
                clear_cache_files();
                $url = 'wholesale.php?act=query&' . str_replace('act=standard_goods', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }
        /*------------------------------------------------------ */
        //-- 商品取消分享
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'no_standard_goods') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['id']);

            if ($exc->edit("standard_goods = 0", $goods_id)) {
                clear_cache_files();
                $url = 'wholesale.php?act=query&' . str_replace('act=no_standard_goods', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }
        /*------------------------------------------------------ */
        //-- 修改上架状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_sale') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            if ($exc->edit("enabled = '$on_sale', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($on_sale);
            }
        }
        /*------------------------------------------------------ */
        //-- 修改上架状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_review_status') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $other['review_status'] = isset($_REQUEST['review_status']) ? intval($_REQUEST['review_status']) : 2;
            $other['review_content'] = !empty($_REQUEST['review_content']) ? addslashes(trim($_REQUEST['review_content'])) : '';

            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('wholesale'), $other, "UPDATE", "goods_id = '$goods_id'");

            $result['goods_id'] = $goods_id;

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 修改上架状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_sale') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            if ($exc->edit("enabled = '$on_sale', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($on_sale);
            }
        }

        /*------------------------------------------------------ */
        //-- 放入回收站
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            $goods_id = intval($_REQUEST['id']);

            /* 检查权限 */
            $check_auth = check_authz_json('whole_sale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if ($exc->edit("is_delete = 1", $goods_id)) {
                clear_cache_files();
                $exc->edit("review_status = 1", $goods_id);
                $url = 'wholesale.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }
        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

            /* 取得要操作的商品编号 */
            $goods_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;

            if (isset($_POST['type'])) {

                /* 检查权限 */
                admin_priv('whole_sale');

                /* 放入回收站 */
                if ($_POST['type'] == 'trash') {
                    $other = [
                        'is_delete' => 1
                    ];
                } /* 上架 */
                elseif ($_POST['type'] == 'on_sale') {
                    $other = [
                        'enabled' => 1
                    ];
                } /* 下架 */
                elseif ($_POST['type'] == 'not_on_sale') {
                    $other = [
                        'enabled' => 0
                    ];
                } /* 转移到分类 */
                elseif ($_POST['type'] == 'move_to') {
                    $other = [
                        'cat_id' => $_POST['target_cat']
                    ];
                } /* 审核商品 */
                elseif ($_POST['type'] == 'review_to') {
                    $other = [
                        'review_status' => $_POST['review_status'],
                        'review_content' => $_POST['review_content']
                    ];
                } // 批量设置分享
                elseif ($_POST['type'] == 'standard_goods') {
                    $other = [
                        'standard_goods' => 1
                    ];
                } // 批量取消分享
                elseif ($_POST['type'] == 'no_standard_goods') {
                    $other = [
                        'standard_goods' => 0
                    ];
                }

                $this->wholesaleGoodsManage->updateWholesaleGoods($goods_id, $other);
            }

            /* 清除缓存 */
            clear_cache_files();
            $link[] = $this->list_link(true, $code);
            return sys_msg($GLOBALS['_LANG']['batch_handle_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 批量添加
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_add') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            $this->smarty->assign('form_action', 'batch_add_insert');

            /* 初始化、取得批发活动信息 */
            $wholesale = array(
                'act_id' => 0,
                'goods_id' => 0,
                'goods_name' => $GLOBALS['_LANG']['pls_search_goods'],
                'enabled' => '1',
                'shipping_fee' => '0.00',
                'price_list' => array()
            );

            $wholesale['price_list'] = array(
                array(
                    'attr' => array(),
                    'qp_list' => array(
                        array('quantity' => 0, 'price' => 0)
                    )
                )
            );
            $this->smarty->assign('wholesale', $wholesale);

            /* 取得用户等级 */
            $user_rank_list = array();
            $sql = "SELECT rank_id, rank_name FROM " . $this->dsc->table('user_rank') .
                " ORDER BY special_rank, min_points";
            $res = $this->db->query($sql);
            while ($rank = $this->db->fetchRow($res)) {
                if (!empty($wholesale['rank_ids']) && strpos($wholesale['rank_ids'], $rank['rank_id']) !== false) {
                    $rank['checked'] = 1;
                }
                $user_rank_list[] = $rank;
            }
            $this->smarty->assign('user_rank_list', $user_rank_list);

            set_default_filter(); //设置默认筛选

            /* 显示模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_wholesale']);
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            $href = 'wholesale.php?act=list';
            $this->smarty->assign('action_link', array('href' => $href, 'text' => $GLOBALS['_LANG']['wholesale_list']));

            return $this->smarty->display('wholesale_batch_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 批量添加入库
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_add_insert') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            /* 取得goods */
            $_POST['dst_goods_lists'] = array();
            if (!empty($_POST['goods_ids'])) {
                $_POST['dst_goods_lists'] = $_POST['goods_ids'];
            }

            if (!empty($_POST['dst_goods_lists']) && is_array($_POST['dst_goods_lists'])) {
                foreach ($_POST['dst_goods_lists'] as $dst_key => $dst_goods) {
                    $dst_goods = intval($dst_goods);
                    if ($dst_goods == 0) {
                        unset($_POST['dst_goods_lists'][$dst_key]);
                    }
                }
            } elseif (!empty($_POST['dst_goods_lists'])) {
                $_POST['dst_goods_lists'] = array(intval($_POST['dst_goods_lists']));
            } else {
                return sys_msg($GLOBALS['_LANG']['pls_search_goods']);
            }
            $dst_goods = implode(',', $_POST['dst_goods_lists']);


            $sql = "SELECT goods_name, goods_id FROM " . $this->dsc->table('goods') .
                " WHERE goods_id IN ($dst_goods)";
            $goods_name = $this->db->getAll($sql);
            if (!empty($goods_name)) {
                $goods_rebulid = array();
                foreach ($goods_name as $goods_value) {
                    $goods_rebulid[$goods_value['goods_id']] = addslashes($goods_value['goods_name']);
                }
            }
            if (empty($goods_rebulid)) {
                return sys_msg('invalid goods id: All');
            }

            /* 会员等级 */
            if (!isset($_POST['rank_id'])) {
                return sys_msg($GLOBALS['_LANG']['pls_set_user_rank']);
            }

            /* 同一个商品，会员等级不能重叠 */
            /* 一个批发方案只有一个商品 一个产品最多支持count(rank_id)个批发方案 */
            if (isset($_POST['rank_id'])) {
                $dst_res = array();
                foreach ($_POST['rank_id'] as $rank_id) {
                    $sql = "SELECT COUNT(act_id) AS num, goods_id FROM " . $this->dsc->table('wholesale') .
                        " WHERE goods_id IN ($dst_goods) " .
                        " AND CONCAT(',', rank_ids, ',') LIKE CONCAT('%,', '$rank_id', ',%')
                      GROUP BY goods_id";
                    if ($dst_res = $this->db->getAll($sql)) {
                        foreach ($dst_res as $dst) {
                            $key = array_search($dst['goods_id'], $_POST['dst_goods_lists']);
                            if ($key != null && $key !== false) {
                                unset($_POST['dst_goods_lists'][$key]);
                            }
                        }
                    }
                }
            }
            if (empty($_POST['dst_goods_lists'])) {
                return sys_msg($GLOBALS['_LANG']['pls_search_goods']);
            }

            /* 提交值 */
            $wholesale = array(
                'rank_ids' => isset($_POST['rank_id']) ? join(',', $_POST['rank_id']) : '',
                'prices' => '',
                'enabled' => empty($_POST['enabled']) ? 0 : 1,
                'review_status' => 3,
                'user_id' => $adminru['ru_id']
            );

            foreach ($_POST['dst_goods_lists'] as $goods_value) {
                $_wholesale = $wholesale;
                $_wholesale['goods_id'] = $goods_value;
                $_wholesale['goods_name'] = $goods_rebulid[$goods_value];

                /* 保存数据 */
                $this->db->autoExecute($this->dsc->table('wholesale'), $_wholesale, 'INSERT');

                /* 记日志 */
                admin_log($goods_rebulid[$goods_value], 'add', 'wholesale');
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $links = array(
                array('href' => 'wholesale.php?act=list', 'text' => $GLOBALS['_LANG']['back_wholesale_list']),
                array('href' => 'wholesale.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_wholesale'])
            );
            return sys_msg($GLOBALS['_LANG']['add_wholesale_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wholesale_goods_attr') . " WHERE goods_id = 0 AND admin_id = '" . session('admin_id') . "'";
            $GLOBALS['db']->query($sql);

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'add';
            $this->smarty->assign('form_action', $is_add ? 'insert' : 'update');
            /* 初始化、取得批发活动信息 */
            if ($is_add) {
                $wholesale = array(
                    'act_id' => 0,
                    'goods_id' => 0,
                    'goods_name' => $GLOBALS['_LANG']['pls_search_goods'],
                    'enabled' => '1',
                    'price_list' => array(),
                    'shipping_fee' => '0.00',
                    'goods_extend' => array('is_delivery' => 0, 'is_return' => 0, 'is_free' => 0),
                    'start_time' => strtotime("1 day"),
                    'end_time' => strtotime("3 day")
                );
            } else {
                if (empty($_GET['id'])) {
                    return sys_msg('invalid param');
                }
                $id = intval($_GET['id']);
                $wholesale = wholesale_info($id);

                if (empty($wholesale)) {
                    return sys_msg($GLOBALS['_LANG']['wholesale_not_exist']);
                }
            }
            /* 取得用户等级 */
            $user_rank_list = array();
            $sql = "SELECT rank_id, rank_name FROM " . $this->dsc->table('user_rank') .
                " ORDER BY special_rank, min_points";
            $res = $this->db->query($sql);
            while ($rank = $this->db->fetchRow($res)) {
                if (!empty($wholesale['rank_ids']) && strpos($wholesale['rank_ids'], $rank['rank_id']) !== false) {
                    $rank['checked'] = 1;
                }
                $user_rank_list[] = $rank;
            }
            $this->smarty->assign('user_rank_list', $user_rank_list);
            /* 如果不是限时批发，处理限时批发日期 */
            if (isset($wholesale['is_promote']) && $wholesale['is_promote'] == '0') {
                unset($wholesale['start_time']);
                unset($wholesale['end_time']);
            } else {
                $wholesale['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $wholesale['start_time']);
                $wholesale['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $wholesale['end_time']);
            }

            $attribute = get_goods_specifications_list($wholesale['goods_id']);

            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][$attribute_value['goods_attr_id']] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }

            if (count($_attribute) > 1) {
                array_pop($_attribute);
            }

            if (empty($wholesale['price_list'])) {
                $wholesale['price_list'] = array(
                    array(
                        'qp_list' => array(
                            array('quantity' => 0, 'price' => 0)
                        )
                    )
                );
            }

            $wholesale['goods_extend'] = $this->goodsService->getWholesaleExtend($wholesale['goods_id']);

            $this->smarty->assign('wholesale', $wholesale);
            $this->smarty->assign('price_list', $wholesale['price_list']['0']);

            $cat_select = wholesale_cat_list(0, 0, false, 0, true, '', 1);

            /* 简单处理缩进 */
            foreach ($cat_select as $k => $v) {
                if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
                }
            }

            $this->smarty->assign('cat_select', $cat_select);

            /* 取得用户等级 */
            $user_rank_list = array();
            $sql = "SELECT rank_id, rank_name FROM " . $this->dsc->table('user_rank') .
                " ORDER BY special_rank, min_points";
            $res = $this->db->query($sql);
            while ($rank = $this->db->fetchRow($res)) {
                if (!empty($wholesale['rank_ids']) && strpos($wholesale['rank_ids'], $rank['rank_id']) !== false) {
                    $rank['checked'] = 1;
                }
                $user_rank_list[] = $rank;
            }
            $this->smarty->assign('user_rank_list', $user_rank_list);

            set_default_filter(); //设置默认筛选

            /* 显示模板 */
            if ($is_add) {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_wholesale']);
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_wholesale']);
            }
            $href = 'wholesale.php?act=list';
            if (!$is_add) {
                $href .= '&' . list_link_postfix();
            }

            //商品属性类型
            $this->smarty->assign('goods_type_list', goods_type_list(0, $wholesale['goods_id'], 'array'));
            $this->smarty->assign('goods_type_name', $GLOBALS['db']->getOne(" SELECT cat_name FROM " . $GLOBALS['dsc']->table('goods_type') . " WHERE cat_id = '0' "));

            //阶梯设置
            $volume_price_list = $this->wholesaleGoodsManage->getWholesaleVolumePriceList($wholesale['goods_id']);
            $this->smarty->assign('volume_price_list', $volume_price_list);

            $this->smarty->assign('action_link', array('href' => $href, 'text' => $GLOBALS['_LANG']['wholesale_list']));
            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign('transport_list', get_table_date("goods_transport", "ru_id='$adminru[ru_id]'", array('tid, title'), 1)); //商品运费 by wu

            return $this->smarty->display('wholesale_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑后提交
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('suppliers_goods_list');

            //运费获取运费参数
            $freight = !empty($_REQUEST['freight']) ? intval($_REQUEST['freight']) : 0;
            $shipping_fee = !empty($_REQUEST['shipping_fee']) ? intval($_REQUEST['shipping_fee']) : 0.00;
            $is_recommend = !empty($_REQUEST['is_recommend']) ? intval($_REQUEST['is_recommend']) : 0;
            $tid = !empty($_REQUEST['tid']) ? intval($_REQUEST['tid']) : 0;

            /*是否为限时批发*/
            if (!isset($_POST['is_promote'])) {
                $is_promote = 0;
            } else {
                $is_promote = $_POST['is_promote'];
            }

            /*获取限时批发时间*/
            $promote_start_date = ($is_promote && !empty($_POST['promote_start_date'])) ? local_strtotime($_POST['promote_start_date']) : 0;
            $promote_end_date = ($is_promote && !empty($_POST['promote_end_date'])) ? local_strtotime($_POST['promote_end_date']) : 0;

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'insert';


            /* 取得goods */
            $goods_id = intval($_POST['goods_id']);
            if ($goods_id <= 0) {
                return sys_msg($GLOBALS['_LANG']['pls_search_goods']);
            }

            $sql = "SELECT goods_name FROM " . $this->dsc->table('goods') .
                " WHERE goods_id = '$goods_id'";
            $goods_name = $this->db->getOne($sql);
            $goods_name = addslashes($goods_name);
            if (is_null($goods_name)) {
                return sys_msg('invalid goods id: ' . $goods_id);
            }

            //判断是否存在商品
            $sql = "SELECT goods_id FROM " . $this->dsc->table('wholesale') . " WHERE goods_id = '$goods_id'";
            $is_goods = $this->db->query($sql);
            $have_goods = $this->db->num_rows($is_goods);

            /* 取得cat_id */
            $cat_id = intval($_POST['cat_id']);
            if ($cat_id <= 0) {
                return sys_msg($GLOBALS['_LANG']['pls_choice_cat']);
            }

            /* 同一个商品，会员等级不能重叠 */
            if (isset($_POST['rank_id'])) {
                foreach ($_POST['rank_id'] as $rank_id) {
                    $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('wholesale') .
                        " WHERE goods_id = '$goods_id' " .
                        " AND CONCAT(',', rank_ids, ',') LIKE CONCAT('%,', '$rank_id', ',%')";
                    if (!$is_add) {
                        $sql .= " AND act_id <> '$_POST[act_id]'";
                    }
                    if ($this->db->getOne($sql) > 0) {
                        return sys_msg($GLOBALS['_LANG']['user_rank_exist']);
                    }
                }
            }

            $attribute = get_goods_specifications_list($goods_id);

            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][$attribute_value['goods_attr_id']] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }

            if (count($_attribute) > 1) {
                array_pop($_attribute);
            }

            if ($goods_id) {
                //商品扩展信息
                $is_delivery = !empty($_POST['is_delivery']) ? intval($_POST['is_delivery']) : 0;
                $is_return = !empty($_POST['is_return']) ? intval($_POST['is_return']) : 0;
                $is_free = !empty($_POST['is_free']) ? intval($_POST['is_free']) : 0;
                $extend = $this->db->getOne("select count(goods_id) from " . $this->dsc->table('wholesale_extend') . " where goods_id='$goods_id'");
                if ($extend > 0) {
                    //跟新商品扩展信息
                    $extend_sql = "update " . $this->dsc->table('wholesale_extend') . " SET `is_delivery`='$is_delivery',`is_return`='$is_return',`is_free`='$is_free' WHERE goods_id='$goods_id'";
                } else {
                    //插入商品扩展信息
                    $extend_sql = "INSERT INTO " . $this->dsc->table('wholesale_extend') . "(`goods_id`, `is_delivery`, `is_return`, `is_free`) VALUES ('$goods_id','$is_delivery','$is_return','$is_free')";
                }
                $this->db->query($extend_sql);

                get_updel_goods_attr($goods_id);
            }

            /* 处理优惠价格 */
            if (intval($_POST['price_model']) && isset($_POST['volume_number']) && isset($_POST['volume_price'])) {
                $this->wholesaleGoodsManage->handleWholesaleVolumePrice($goods_id, intval($_POST['price_model']), $_POST['volume_number'], $_POST['volume_price'], $_POST['id']);
            }

            /* 提交值 */
            $wholesale = array(
                'act_id' => intval($_POST['act_id']),
                'goods_id' => $goods_id,
                'wholesale_cat_id' => $cat_id,
                'goods_name' => $goods_name,
                'rank_ids' => isset($_POST['rank_id']) ? join(',', $_POST['rank_id']) : '',
                'review_status' => 3,
                'is_recommend' => $is_recommend,
                'is_promote' => $is_promote,
                'freight' => $freight,//运费模式
                'shipping_fee' => $shipping_fee,//固定运费
                'tid' => $tid,//运费模板id
                'enabled' => empty($_POST['enabled']) ? 0 : 1,
                'price_model' => intval($_POST['price_model']),
                'goods_type' => intval($_POST['goods_type']),
                'goods_price' => floatval($_POST['goods_price']),
                'moq' => intval($_POST['moq']),
                'goods_number' => intval($_POST['goods_number']),
                'start_time' => $promote_start_date,
                'end_time' => $promote_end_date
            );

            /* 保存数据 */
            if ($is_add) {
                if ($have_goods) {
                    return sys_msg($GLOBALS['_LANG']['have_goods']);
                } else {
                    $wholesale['user_id'] = $adminru['ru_id'];
                    $this->db->autoExecute($this->dsc->table('wholesale'), $wholesale, 'INSERT');
                    $wholesale['act_id'] = $this->db->insert_id();
                }
            } else {
                if (isset($_POST['review_status'])) {
                    $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                    $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                    $wholesale['review_status'] = $review_status;
                    $wholesale['review_content'] = $review_content;
                }

                $this->db->autoExecute($this->dsc->table('wholesale'), $wholesale, 'UPDATE', "act_id = '$wholesale[act_id]'");
            }

            /** ************* 处理货品数据 start ************** */
            $where_products = "";
            $goods_model = isset($_POST['goods_model']) && !empty($_POST['goods_model']) ? intval($_POST['goods_model']) : 0;
            $warehouse = isset($_POST['warehouse']) && !empty($_POST['warehouse']) ? intval($_POST['warehouse']) : 0;
            $region = isset($_POST['region']) && !empty($_POST['region']) ? intval($_POST['region']) : 0;


            /* 处理属性 */
            if ((isset($_POST['attr_id_list']) && isset($_POST['attr_value_list'])) || (empty($_POST['attr_id_list']) && empty($_POST['attr_value_list']))) {
                // 取得原有的属性值
                $goods_attr_list = array();

                $sql = "SELECT attr_id, attr_index FROM " . $this->dsc->table('attribute') . " WHERE cat_id = '0'";
                $attr_res = $this->db->query($sql);

                $attr_list = array();
                while ($row = $this->db->fetchRow($attr_res)) {
                    $attr_list[$row['attr_id']] = $row['attr_index'];
                }

                $sql = "SELECT ga.*, a.attr_type
                FROM " . $this->dsc->table('wholesale_goods_attr') . " AS ga
                    LEFT JOIN " . $this->dsc->table('attribute') . " AS a
                        ON a.attr_id = ga.attr_id
                WHERE ga.goods_id = '$goods_id'";

                $res = $this->db->query($sql);

                while ($row = $this->db->fetchRow($res)) {
                    $goods_attr_list[$row['attr_id']][$row['attr_value']] = array('sign' => 'delete', 'goods_attr_id' => $row['goods_attr_id']);
                }

                // 循环现有的，根据原有的做相应处理
                if (isset($_POST['attr_id_list'])) {
                    foreach ($_POST['attr_id_list'] as $key => $attr_id) {
                        $attr_value = $_POST['attr_value_list'][$key];
                        $attr_sort = isset($_POST['attr_sort_list'][$key]) ? $_POST['attr_sort_list'][$key] : ''; //ecmoban模板堂 --zhuo
                        if (!empty($attr_value)) {
                            if (isset($goods_attr_list[$attr_id][$attr_value])) {
                                // 如果原来有，标记为更新
                                $goods_attr_list[$attr_id][$attr_value]['sign'] = 'update';
                                $goods_attr_list[$attr_id][$attr_value]['attr_sort'] = $attr_sort;
                            } else {
                                // 如果原来没有，标记为新增
                                $goods_attr_list[$attr_id][$attr_value]['sign'] = 'insert';
                                $goods_attr_list[$attr_id][$attr_value]['attr_sort'] = $attr_sort;
                            }
                        }
                    }
                }

                // 循环现有的，根据原有的做相应处理
                if (isset($_POST['gallery_attr_id'])) {
                    foreach ($_POST['gallery_attr_id'] as $key => $attr_id) {
                        $gallery_attr_value = $_POST['gallery_attr_value'][$key];
                        $gallery_attr_sort = $_POST['gallery_attr_sort'][$key];
                        if (!empty($gallery_attr_value)) {
                            if (isset($goods_attr_list[$attr_id][$gallery_attr_value])) {
                                // 如果原来有，标记为更新
                                $goods_attr_list[$attr_id][$gallery_attr_value]['sign'] = 'update';
                                $goods_attr_list[$attr_id][$gallery_attr_value]['attr_sort'] = $gallery_attr_sort;
                            } else {
                                // 如果原来没有，标记为新增
                                $goods_attr_list[$attr_id][$gallery_attr_value]['sign'] = 'insert';
                                $goods_attr_list[$attr_id][$gallery_attr_value]['attr_sort'] = $gallery_attr_sort;
                            }
                        }
                    }
                }

                /* 插入、更新、删除数据 */
                foreach ($goods_attr_list as $attr_id => $attr_value_list) {
                    foreach ($attr_value_list as $attr_value => $info) {
                        if ($info['sign'] == 'insert') {
                            $sql = "INSERT INTO " . $this->dsc->table('wholesale_goods_attr') . " (attr_id, goods_id, attr_value, attr_sort)" .
                                "VALUES ('$attr_id', '$goods_id', '$attr_value', '$info[attr_sort]')";
                        } elseif ($info['sign'] == 'update') {
                            $sql = "UPDATE " . $this->dsc->table('wholesale_goods_attr') . " SET attr_sort = '$info[attr_sort]' WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1";
                        } else {
                            $table = 'products';

                            $where = " AND goods_id = '$goods_id'";
                            $this->dsc->get_del_find_in_set($info['goods_attr_id'], $where, $table, 'goods_attr', '|');

                            $sql = "DELETE FROM " . $this->dsc->table('wholesale_goods_attr') . " WHERE goods_attr_id = '" . $info['goods_attr_id'] . "' LIMIT 1";
                        }
                        $this->db->query($sql);
                    }
                }
            }


            $table = "wholesale_products";
            $products_extension_insert_name = "";
            $products_extension_insert_value = "";

            $product['goods_id'] = $goods_id;
            $product['attr'] = isset($_POST['attr']) ? $_POST['attr'] : array();
            $product['product_id'] = isset($_POST['product_id']) ? $_POST['product_id'] : array();
            $product['product_sn'] = isset($_POST['product_sn']) ? $_POST['product_sn'] : array();
            $product['product_number'] = isset($_POST['product_number']) ? $_POST['product_number'] : array();
            $product['product_price'] = isset($_POST['product_price']) ? $_POST['product_price'] : array(); //货品价格
            $product['product_market_price'] = isset($_POST['product_market_price']) ? $_POST['product_market_price'] : array(); //货品市场价格
            $product['product_warn_number'] = isset($_POST['product_warn_number']) ? $_POST['product_warn_number'] : array(); //警告库存
            $product['bar_code'] = isset($_POST['product_bar_code']) ? $_POST['product_bar_code'] : array(); //货品条形码

            /* 是否存在商品id */
            if (empty($product['goods_id'])) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, array(), false);
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price, model_inventory, model_attr FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id' LIMIT 1";
            $goods = $this->db->getRow($sql);

            /* 货号 */
            if (empty($product['product_sn'])) {
                $product['product_sn'] = array();
            }

            foreach ($product['product_sn'] as $key => $value) {
                //过滤
                $product['product_number'][$key] = trim($product['product_number'][$key]); //库存
                $product['product_id'][$key] = isset($product['product_id'][$key]) && !empty($product['product_id'][$key]) ? intval($product['product_id'][$key]) : 0; //货品ID

                $logs_other = array(
                    'goods_id' => $goods_id,
                    'order_id' => 0,
                    'admin_id' => session('admin_id'),
                    'model_inventory' => $goods['model_inventory'],
                    'model_attr' => $goods['model_attr'],
                    'add_time' => gmtime()
                );

                if ($goods_model == 1) {
                    $logs_other['warehouse_id'] = $warehouse;
                    $logs_other['area_id'] = 0;
                } elseif ($goods_model == 2) {
                    $logs_other['warehouse_id'] = 0;
                    $logs_other['area_id'] = $region;
                } else {
                    $logs_other['warehouse_id'] = 0;
                    $logs_other['area_id'] = 0;
                }

                if ($product['product_id'][$key]) {

                    /* 货品库存 */
                    $goods_product = $this->wholesaleGoodsManage->getWholesaleProductInfo($product['product_id'][$key]);

                    if ($goods_product['product_number'] != $product['product_number'][$key]) {
                        if ($goods_product['product_number'] > $product['product_number'][$key]) {
                            $number = $goods_product['product_number'] - $product['product_number'][$key];
                            $number = "- " . $number;
                            $logs_other['use_storage'] = 10;
                        } else {
                            $number = $product['product_number'][$key] - $goods_product['product_number'];
                            $number = "+ " . $number;
                            $logs_other['use_storage'] = 11;
                        }

                        $logs_other['number'] = $number;
                        $logs_other['product_id'] = $product['product_id'][$key];
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_inventory_logs'), $logs_other, 'INSERT');
                    }

                    $sql = "UPDATE " . $GLOBALS['dsc']->table($table) . " SET product_number = '" . $product['product_number'][$key] . "'" .
                        " WHERE product_id = '" . $product['product_id'][$key] . "'";
                    $GLOBALS['db']->query($sql);
                } else {
                    //获取规格在商品属性表中的id
                    foreach ($product['attr'] as $attr_key => $attr_value) {
                        /* 检测：如果当前所添加的货品规格存在空值或0 */
                        if (empty($attr_value[$key])) {
                            continue 2;
                        }

                        $is_spec_list[$attr_key] = 'true';

                        $value_price_list[$attr_key] = $attr_value[$key] . chr(9) . ''; //$key，当前

                        $id_list[$attr_key] = $attr_key;
                    }

                    $goods_attr_id = $this->wholesaleGoodsManage->handleWholesaleGoodsAttr($product['goods_id'], $id_list, $is_spec_list, $value_price_list);

                    /* 是否为重复规格的货品 */
                    $goods_attr = $this->wholesaleGoodsManage->sortWholesaleGoodsAttrIdArray($goods_attr_id);

                    if (!empty($goods_attr['sort'])) {
                        $goods_attr = implode('|', $goods_attr['sort']);
                    } else {
                        $goods_attr = "";
                    }

                    if ($this->wholesaleGoodsManage->checkWholesaleGoodsAttrExist($goods_attr, $product['goods_id'])) {
                        continue;
                    }

                    /* 插入货品表 */
                    $sql = "INSERT INTO " . $GLOBALS['dsc']->table($table) .
                        " (goods_id, goods_attr, product_sn, product_number " . $products_extension_insert_name . ") VALUES " .
                        " ('" . $product['goods_id'] . "', '$goods_attr', '$value', '" . $product['product_number'][$key] . "'" . $products_extension_insert_value . ")";
                    if (!$GLOBALS['db']->query($sql)) {
                        continue;
                    } else {
                        $product_id = $GLOBALS['db']->insert_id();

                        //货品号为空 自动补货品号
                        if (empty($value)) {
                            $sql = "UPDATE " . $GLOBALS['dsc']->table($table) . "
                                SET product_sn = '" . $goods['goods_sn'] . "g_p" . $GLOBALS['db']->insert_id() . "'
                                WHERE product_id = '$product_id'";
                            $GLOBALS['db']->query($sql);
                        }

                        //库存日志
                        $number = "+ " . $product['product_number'][$key];
                        $logs_other['use_storage'] = 9;
                        $logs_other['product_id'] = $product_id;
                        $logs_other['number'] = $number;
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_inventory_logs'), $logs_other, 'INSERT');
                    }
                }
            }
            /*************** 处理货品数据 end ***************/

            /* 记日志 */
            if ($is_add) {
                admin_log($wholesale['goods_name'], 'add', 'wholesale');
            } else {
                admin_log($wholesale['goods_name'], 'edit', 'wholesale');
            }

            /* 清除缓存 */
            clear_cache_files();

            if ($is_add) {
                $links = array(
                    array('href' => 'wholesale.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_wholesale']),
                    array('href' => 'wholesale.php?act=list', 'text' => $GLOBALS['_LANG']['back_wholesale_list'])
                );
                return sys_msg($GLOBALS['_LANG']['add_wholesale_ok'], 0, $links);
            } else {
                $links = array(
                    array('href' => 'wholesale.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_wholesale_list'])
                );
                return sys_msg($GLOBALS['_LANG']['edit_wholesale_ok'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_goods') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode($_GET['JSON']);
            $arr = get_goods_list($filter);
            if (empty($arr)) {
                $arr[0] = array(
                    'goods_id' => 0,
                    'goods_name' => $GLOBALS['_LANG']['search_result_empty']
                );
            }

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 取得商品信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_goods_info') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = array('content' => '', 'error' => 0);
            $goods_id = intval($_REQUEST['goods_id']);
            $goods_type = $this->db->getOne(" SELECT goods_type FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id' ");
            $attribute = get_goods_specifications_list($goods_id);

            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][$attribute_value['goods_attr_id']] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }

            if (count($_attribute) > 1) {
                array_pop($_attribute);
            }

            //商品属性类型
            $this->smarty->assign('goods_type_list', goods_type_list($goods_type, $goods_id, 'array'));
            $this->smarty->assign('goods_type_name', $GLOBALS['db']->getOne(" SELECT cat_name FROM " . $GLOBALS['dsc']->table('goods_type') . " WHERE cat_id = '$goods_type' "));
            $result['content'] = $GLOBALS['smarty']->fetch('library/wholesale_attr_list.lbi');

            return response()->json($result);
        }
        /*--------------------------------------------------------*/
        // 切换商品类型
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'get_attribute') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_type = empty($_REQUEST['goods_type']) ? 0 : intval($_REQUEST['goods_type']);
            $model = !isset($_REQUEST['modelAttr']) ? -1 : intval($_REQUEST['modelAttr']);
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $attribute = $this->wholesaleGoodsManage->setWholesaleGoodsAttribute($goods_type, $goods_id, $model);

            $result['goods_attribute'] = $attribute['goods_attribute'];
            $result['goods_attr_gallery'] = $attribute['goods_attr_gallery'];
            $result['model'] = $model;
            $result['goods_id'] = $goods_id;
            $result['is_spec'] = $attribute['is_spec'];

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除属性
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_product') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $group_attr = empty($_REQUEST['group_attr']) ? '' : $_REQUEST['group_attr'];
            $group_attr = dsc_decode($group_attr, true);
            $product_id = empty($_REQUEST['product_id']) ? 0 : intval($_REQUEST['product_id']);

            //地区模式
            if ($group_attr['goods_model'] == 1) {
                $table = 'products_warehouse';
            } elseif ($group_attr['goods_model'] == 2) {
                $table = 'products_area';
            } else {
                $table = 'wholesale_products';
            }


            /* 删除数据 */
            $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE product_id = '$product_id' LIMIT 1";
            $GLOBALS['db']->query($sql);

            clear_cache_files();
            return make_json_result_too($product_id, 0, '', $group_attr);
        }

        /*--------------------------------------------------------*/
        // 设置属性表格
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'set_attribute_table' || $_REQUEST['act'] == 'wholesale_attribute_query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_type = empty($_REQUEST['goods_type']) ? 0 : intval($_REQUEST['goods_type']);
            $attr_id_arr = empty($_REQUEST['attr_id']) ? array() : explode(',', $_REQUEST['attr_id']);
            $attr_value_arr = empty($_REQUEST['attr_value']) ? array() : explode(',', $_REQUEST['attr_value']);
            $goods_model = empty($_REQUEST['goods_model']) ? 0 : intval($_REQUEST['goods_model']); //商品模式
            $region_id = empty($_REQUEST['region_id']) ? 0 : intval($_REQUEST['region_id']); //地区id
            $search_attr = !empty($_REQUEST['search_attr']) ? trim($_REQUEST['search_attr']) : '';
            $result = array('error' => 0, 'message' => '', 'content' => '');

            /* ajax分页 start */
            $filter['goods_id'] = $goods_id;
            $filter['goods_type'] = $goods_type;
            $filter['attr_id'] = $_REQUEST['attr_id'];
            $filter['attr_value'] = $_REQUEST['attr_value'];
            $filter['goods_model'] = $goods_model;
            $filter['region_id'] = $region_id;
            $filter['search_attr'] = $search_attr;
            /* ajax分页 end */
            if ($search_attr) {
                $search_attr = explode(',', $search_attr);
            } else {
                $search_attr = array();
            }
            $group_attr = array(
                'goods_id' => $goods_id,
                'goods_type' => $goods_type,
                'attr_id' => empty($attr_id_arr) ? '' : implode(',', $attr_id_arr),
                'attr_value' => empty($attr_value_arr) ? '' : implode(',', $attr_value_arr),
                'goods_model' => $goods_model,
                'region_id' => $region_id,
            );

            $result['group_attr'] = json_encode($group_attr);

            //商品模式
            if ($goods_model == 0) {
                $model_name = "";
            } elseif ($goods_model == 1) {
                $model_name = lang('admin/common.warehouse');
            } elseif ($goods_model == 2) {
                $model_name = lang('admin/common.area');
            }
            $region_name = $GLOBALS['db']->getOne(" SELECT region_name FROM " . $GLOBALS['dsc']->table('region_warehouse') . " WHERE region_id ='$region_id' ");
            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('goods_model', $goods_model);
            $this->smarty->assign('model_name', $model_name);

            //商品基本信息
            $goods_info = $GLOBALS['db']->getRow(" SELECT market_price, shop_price, model_attr FROM " . $GLOBALS['dsc']->table("goods") . " WHERE goods_id = '$goods_id' ");
            $this->smarty->assign('goods_info', $goods_info);

            //将属性归类
            foreach ($attr_id_arr as $key => $val) {
                $attr_arr[$val][] = $attr_value_arr[$key];
            }

            $attr_spec = array();
            $attribute_array = array();

            if (count($attr_arr) > 0) {
                //属性数据
                $i = 0;
                foreach ($attr_arr as $key => $val) {
                    $sql = "SELECT attr_name, attr_type FROM " . $GLOBALS['dsc']->table('attribute') . " WHERE attr_id ='$key' LIMIT 1";
                    $attr_info = $GLOBALS['db']->getRow($sql);

                    $attribute_array[$i]['attr_id'] = $key;
                    $attribute_array[$i]['attr_name'] = $attr_info['attr_name'];
                    $attribute_array[$i]['attr_value'] = $val;
                    /* 处理属性图片 start */
                    $attr_values_arr = array();
                    foreach ($val as $k => $v) {
                        $data = $this->wholesaleGoodsManage->getWholesaleGoodsAttrId(['attr_id' => $key, 'attr_value' => $v, 'goods_id' => $goods_id], [1, 2], 1);
                        if (!$data) {
                            $sql = "SELECT MAX(goods_attr_id) AS goods_attr_id FROM " . $GLOBALS['dsc']->table('wholesale_goods_attr') . " WHERE 1 ";
                            $max_goods_attr_id = $GLOBALS['db']->getOne($sql);
                            $attr_sort = $max_goods_attr_id + 1;

                            $sql = " INSERT INTO " . $GLOBALS['dsc']->table('wholesale_goods_attr') . " (goods_id, attr_id, attr_value, attr_sort, admin_id) " .
                                " VALUES " .
                                " ('$goods_id', '$key', '$v', '$attr_sort', '" . session('admin_id') . "') ";
                            $GLOBALS['db']->query($sql);
                            $data['goods_attr_id'] = $GLOBALS['db']->insert_id();
                            $data['attr_type'] = $attr_info['attr_type'];
                            $data['attr_sort'] = $attr_sort;
                        }
                        $data['attr_id'] = $key;
                        $data['attr_value'] = $v;
                        $data['is_selected'] = 1;
                        $attr_values_arr[] = $data;
                    }

                    $attr_spec[$i] = $attribute_array[$i];
                    $attr_spec[$i]['attr_values_arr'] = $attr_values_arr;

                    $attribute_array[$i]['attr_values_arr'] = $attr_values_arr;

                    if ($attr_info['attr_type'] == 2) {
                        unset($attribute_array[$i]);
                    }
                    /* 处理属性图片 end */
                    $i++;
                }

                $attr_arr = get_goods_unset_attr($goods_id, $attr_arr);

                //将属性组合
                if (count($attr_arr) == 1) {
                    foreach (reset($attr_arr) as $key => $val) {
                        $attr_group[][] = $val;
                    }
                } else {
                    $attr_group = attr_group($attr_arr);
                }
                //搜索筛选
                if (!empty($attr_group) && !empty($search_attr)) {
                    foreach ($attr_group as $k => $v) {
                        $array_intersect = array_intersect($search_attr, $v);//获取查询出的属性与搜索数组的差集
                        if (empty($array_intersect)) {
                            unset($attr_group[$k]);
                        }
                    }
                }
                /* ajax分页 start */
                $filter['page'] = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
                $filter['page_size'] = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 15;
                $products_list = $this->dsc->page_array($filter['page_size'], $filter['page'], $attr_group, 0, $filter);

                $filter = $products_list['filter'];
                $attr_group = $products_list['list'];
                /* ajax分页 end */

                //取得组合补充数据
                foreach ($attr_group as $key => $val) {
                    $group = array();

                    //货品信息
                    $product_info = $this->wholesaleGoodsManage->getWholesaleProductInfoByAttr($goods_id, $val);
                    if (!empty($product_info)) {
                        $group = $product_info;
                    }
                    //组合信息
                    foreach ($val as $k => $v) {
                        if ($v) {
                            $group['attr_info'][$k]['attr_id'] = $attribute_array[$k]['attr_id'];
                            $group['attr_info'][$k]['attr_value'] = $v;
                        }
                    }

                    if ($group) {
                        $attr_group[$key] = $group;
                    } else {
                        $attr_group = array();
                    }
                }
                $this->smarty->assign('attr_group', $attr_group);
                $this->smarty->assign('attribute_array', $attribute_array);

                /* ajax分页 start */
                $this->smarty->assign('filter', $filter);

                $page_count_arr = seller_page($products_list, $filter['page']);
                $this->smarty->assign('page_count_arr', $page_count_arr);
                if ($_REQUEST['act'] == 'set_attribute_table') {
                    $this->smarty->assign('full_page', 1);
                } else {
                    $this->smarty->assign('group_attr', $result['group_attr']);
                    $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);
                    return make_json_result($this->smarty->fetch('library/wholesale_attribute_query.lbi'), '', array('filter' => $products_list['filter'], 'page_count' => $products_list['page_count']));
                }
                /* ajax分页 end */
            }

            $this->smarty->assign('group_attr', $result['group_attr']);
            $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

            $GLOBALS['smarty']->assign('goods_id', $goods_id);
            $GLOBALS['smarty']->assign('goods_type', $goods_type);

            $result['content'] = $this->smarty->fetch('library/wholesale_attribute_table.lbi');

            /* 处理属性图片 start */
            $this->smarty->assign('attr_spec', $attr_spec);
            $this->smarty->assign('spec_count', count($attr_spec));
            $result['goods_attr_gallery'] = $this->smarty->fetch('library/wholesale_goods_attr_gallery.lbi');
            /* 处理属性图片 end */

            return response()->json($result);
        }

        /*--------------------------------------------------------*/
        //商品模块弹窗
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'goods_info') {
            $result = array('content' => '', 'mode' => '');
            /*处理数组*/
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $goods_type = isset($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
            $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if (!empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode(stripslashes($_REQUEST['spec_attr']), true);
            }
            $spec_attr['is_title'] = isset($spec_attr['is_title']) ? $spec_attr['is_title'] : 0;
            $spec_attr['itemsLayout'] = isset($spec_attr['itemsLayout']) ? $spec_attr['itemsLayout'] : 'row4';
            $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';

            //取得商品列表
            if ($spec_attr['goods_ids']) {
                $goods_info = explode(',', $spec_attr['goods_ids']);
                foreach ($goods_info as $k => $v) {
                    if (!$v) {
                        unset($goods_info[$k]);
                    }
                }
                if (!empty($goods_info)) {
                    $where = " WHERE g.is_on_sale=1 AND g.user_id=0 AND g.is_delete=0 AND g.goods_id" . db_create_in($goods_info);

                    //ecmoban模板堂 --zhuo start
                    if ($GLOBALS['_CFG']['review_goods'] == 1) {
                        $where .= ' AND g.review_status > 2 ';
                    }
                    //ecmoban模板堂 --zhuo end

                    $sql = "SELECT g.goods_name,g.goods_id,g.goods_thumb,g.original_img,g.shop_price FROM " . $this->dsc->table('goods') . " AS g " . $where;
                    $goods_list = $this->db->getAll($sql);

                    foreach ($goods_list as $k => $v) {
                        $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                    }

                    $this->smarty->assign('goods_list', $goods_list);
                    $this->smarty->assign('goods_count', count($goods_list));
                }
            }
            /* 取得分类列表 */

            //获取下拉列表 by wu start
            set_default_filter(0, $cat_id); //设置默认筛选
            $this->smarty->assign('parent_category', get_every_category($cat_id)); //上级分类导航
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('arr', $spec_attr);
            $this->smarty->assign("goods_type", $goods_type);
            $this->smarty->assign("mode", $result['mode']);
            $this->smarty->assign("cat_id", $cat_id);
            $this->smarty->assign("lift", $lift);
            $result['content'] = $GLOBALS['smarty']->fetch('library/add_wholesale_goods.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品模块
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'changedgoods') {
            $result = array('error' => 0, 'message' => '', 'content' => '');
            $spec_attr = array();
            $result['lift'] = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $result['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if ($_REQUEST['spec_attr']) {
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
                }
            }
            $sort_order = isset($_REQUEST['sort_order']) ? $_REQUEST['sort_order'] : 1;
            $cat_id = isset($_REQUEST['cat_id']) ? explode('_', $_REQUEST['cat_id']) : array();
            $brand_id = isset($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
            $keyword = isset($_REQUEST['keyword']) ? addslashes($_REQUEST['keyword']) : '';
            $goodsAttr = isset($spec_attr['goods_ids']) ? explode(',', $spec_attr['goods_ids']) : '';
            $goods_ids = isset($_REQUEST['goods_ids']) ? explode(',', $_REQUEST['goods_ids']) : '';
            $result['goods_ids'] = !empty($goodsAttr) ? $goodsAttr : $goods_ids;
            $result['cat_desc'] = isset($spec_attr['cat_desc']) ? addslashes($spec_attr['cat_desc']) : '';
            $result['cat_name'] = isset($spec_attr['cat_name']) ? addslashes($spec_attr['cat_name']) : '';
            $result['align'] = isset($spec_attr['align']) ? addslashes($spec_attr['align']) : '';
            $result['is_title'] = isset($spec_attr['is_title']) ? intval($spec_attr['is_title']) : 0;
            $result['itemsLayout'] = isset($spec_attr['itemsLayout']) ? addslashes($spec_attr['itemsLayout']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $temp = isset($_REQUEST['temp']) ? $_REQUEST['temp'] : 'goods_list';
            $resetRrl = isset($_REQUEST['resetRrl']) ? intval($_REQUEST['resetRrl']) : 0;

            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $this->smarty->assign('temp', $temp);
            $where = "WHERE g.is_on_sale=1 AND g.user_id = 0 AND g.is_delete=0 ";

            //已参加活动的商品不在列表内
            $sql = " SELECT goods_id FROM " . $this->dsc->table('wholesale') . " WHERE user_id = '" . $adminru['ru_id'] . "' ";
            $goods_ids = $this->db->getCol($sql);
            if (!empty($goods_ids)) {
                $goods_ids_str = implode(',', $goods_ids);
                $where .= " AND g.goods_id NOT IN($goods_ids_str) ";
            }

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['review_goods'] == 1) {
                $where .= ' AND g.review_status > 2 ';
            }
            //ecmoban模板堂 --zhuo end
            if ($cat_id[0] > 0) {
                $where .= " AND " . get_children($cat_id[0]);
            }
            if ($brand_id > 0) {
                $where .= " AND g.brand_id = '$brand_id'";
            }
            if ($keyword) {
                $where .= " AND g.goods_name  LIKE '%$keyword%'";
            }
            if ($result['goods_ids'] && $type == '0') {
                $where .= " AND g.goods_id" . db_create_in($result['goods_ids']);
            }
            $sort = '';
            switch ($sort_order) {
                case '1':
                    $sort .= " ORDER BY g.add_time ASC";
                    break;

                case '2':
                    $sort .= " ORDER BY g.add_time DESC";
                    break;

                case '3':
                    $sort .= " ORDER BY g.sort_order ASC";
                    break;

                case '4':
                    $sort .= " ORDER BY g.sort_order DESC";
                    break;

                case '5':
                    $sort .= " ORDER BY g.goods_name ASC";
                    break;

                case '6':
                    $sort .= " ORDER BY g.goods_name DESC";
                    break;
            }
            if ($type == 1) {
                $list = getGoodslist($where, $sort);
                $goods_list = $list['list'];
                $filter = $list['filter'];
                $filter['cat_id'] = $cat_id[0];
                $filter['sort_order'] = $sort_order;
                $filter['keyword'] = $keyword;
                $this->smarty->assign('filter', $filter);
            } else {
                $sql = "SELECT g.promote_start_date, g.promote_end_date, g.promote_price, g.goods_name, g.goods_id, g.goods_thumb, g.shop_price, g.market_price, g.original_img FROM " .
                    $this->dsc->table('goods') . " AS g " . $where . $sort;
                $goods_list = $this->db->getAll($sql);
            }

            if (!empty($goods_list)) {
                foreach ($goods_list as $k => $v) {
                    $goods_list[$k]['goods_thumb'] = get_image_path($v['goods_id'], $v['goods_thumb']);
                    $goods_list[$k]['original_img'] = get_image_path($v['goods_id'], $v['original_img']);
                    $goods_list[$k]['url'] = $this->dscRepository->buildUri('goods', array('gid' => $v['goods_id']), $v['goods_name']);
                    $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                    if ($v['promote_price'] > 0) {
                        $goods_list[$k]['promote_price'] = bargain_price($v['promote_price'], $v['promote_start_date'], $v['promote_end_date']);
                    } else {
                        $goods_list[$k]['promote_price'] = 0;
                    }
                    if ($v['goods_id'] > 0 && in_array($v['goods_id'], $result['goods_ids']) && !empty($result['goods_ids'])) {
                        $goods_list[$k]['is_selected'] = 1;
                    }
                }
            }
            $this->smarty->assign("is_title", $result['is_title']);
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('goods_count', count($goods_list));
            $this->smarty->assign('attr', $spec_attr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/wholesale_goods_list.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改货品库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_number') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $product_number = intval($_POST['val']);

            /* 修改货品库存 */
            $sql = "UPDATE " . $this->dsc->table('wholesale_products') . " SET product_number = '$product_number' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_price') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $product_price = floatval($_POST['val']);

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('wholesale_products') . " SET product_price = '$product_price' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($product_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改属性排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_attr_sort') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_attr_id = intval($_REQUEST['id']);
            $attr_sort = intval($_POST['val']);

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('wholesale_goods_attr') . " SET attr_sort = '$attr_sort' WHERE goods_attr_id = '$goods_attr_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($attr_sort);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改属性价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_attr_price') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_attr_id = intval($_REQUEST['id']);
            $attr_price = floatval($_POST['val']);

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('wholesale_goods_attr') . " SET attr_price = '$attr_price' WHERE goods_attr_id = '$goods_attr_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($attr_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_sn') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);

            $product_sn = json_str_iconv(trim($_POST['val']));
            $product_sn = ($GLOBALS['_LANG']['n_a'] == $product_sn) ? '' : $product_sn;

            $exist = $this->wholesaleGoodsManage->checkWholsaleProductSnExist($product_sn, $product_id, $adminru['ru_id']);
            if ($exist) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_product_sn']);
            }

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('wholesale_products') . " SET product_sn = '$product_sn' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($product_sn);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改属性排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_attr_sort') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_attr_id = intval($_REQUEST['id']);
            $attr_sort = intval($_POST['val']);

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('wholesale_goods_attr') . " SET attr_sort = '$attr_sort' WHERE goods_attr_id = '$goods_attr_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($attr_sort);
            }
        }

        /*------------------------------------------------------ */
        //-- 商品单选复选属性手工录入
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'attr_input_type') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = array('content' => '', 'sgs' => '');

            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('goods_id', $goods_id);

            $goods_attr = $this->wholesaleGoodsManage->dialogWholesaleGoodsAttrType($attr_id, $goods_id);
            $this->smarty->assign('goods_attr', $goods_attr);

            $result['content'] = $GLOBALS['smarty']->fetch('library/attr_input_type.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品单选复选属性手工录入
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_attr_input') {
            $result = array('content' => '', 'sgs' => '');

            $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : array();
            $attr_value_list = isset($_REQUEST['attr_value_list']) ? $_REQUEST['attr_value_list'] : array();

            if ($goods_id) {
                $where = " AND goods_id = '$goods_id'";
            } else {
                $where = " AND goods_id = 0 AND admin_id = '$admin_id'";
            }

            /* 插入、更新、删除数据 */
            foreach ($attr_value_list as $key => $attr_value) {
                if ($attr_value) {
                    if ($goods_attr_id[$key]) {
                        $sql = "UPDATE " . $this->dsc->table('wholesale_goods_attr') . " SET attr_value = '$attr_value' WHERE goods_attr_id = '" . $goods_attr_id[$key] . "' LIMIT 1";
                    } else {
                        $sql = "SELECT MAX(attr_sort) AS attr_sort FROM " . $GLOBALS['dsc']->table('wholesale_goods_attr') . " WHERE attr_id = '$attr_id'" . $where;
                        $max_attr_sort = $GLOBALS['db']->getOne($sql);

                        if ($max_attr_sort) {
                            $key = $max_attr_sort + 1;
                        } else {
                            $key += 1;
                        }

                        $sql = "INSERT INTO " . $this->dsc->table('wholesale_goods_attr') . " (attr_id, goods_id, attr_value, attr_sort, admin_id)" .
                            "VALUES ('$attr_id', '$goods_id', '$attr_value', '$key', '$admin_id')";
                    }

                    $this->db->query($sql);
                }
            }

            $result['attr_id'] = $attr_id;
            $result['goods_id'] = $goods_id;

            $goods_attr = $this->wholesaleGoodsManage->dialogWholesaleGoodsAttrType($attr_id, $goods_id);
            $this->smarty->assign('goods_attr', $goods_attr);
            $this->smarty->assign('attr_id', $attr_id);

            $result['content'] = $GLOBALS['smarty']->fetch('library/attr_input_type_list.lbi');

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除商品勾选属性 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_goods_attr') {
            $result = array('error' => 0, 'message' => '', 'content' => '');
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $attr_value = isset($_REQUEST['attr_value']) && !empty($_REQUEST['attr_value']) ? addslashes($_REQUEST['attr_value']) : '';

            if ($goods_attr_id) {
                $where = "goods_attr_id = '$goods_attr_id'";
            } else {
                $where = "goods_id = '$goods_id' AND attr_value = '$attr_value' AND attr_id = '$attr_id' AND admin_id = '$admin_id'";
            }

            $sql = "DELETE FROM " . $GLOBALS['dsc']->table("wholesale_goods_attr") . " WHERE $where";
            $GLOBALS['db']->query($sql);

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加属性图片 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_attr_img') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_name = !empty($_REQUEST['goods_name']) ? trim($_REQUEST['goods_name']) : '';
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $goods_date = array('goods_name');
            $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);
            if (!isset($goods_info['goods_name'])) {
                $goods_info['goods_name'] = $goods_name;
            }

            $goods_attr_date = array('attr_img_flie, attr_img_site, attr_checked, attr_gallery_flie');
            $goods_attr_info = get_table_date('wholesale_goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);

            $attr_date = array('attr_name');
            $attr_info = get_table_date('attribute', "attr_id = '$attr_id'", $attr_date);

            $this->smarty->assign('goods_info', $goods_info);
            $this->smarty->assign('attr_info', $attr_info);
            $this->smarty->assign('goods_attr_info', $goods_attr_info);
            $this->smarty->assign('goods_attr_name', $goods_attr_name);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('goods_attr_id', $goods_attr_id);
            $this->smarty->assign('form_action', 'insert_attr_img');

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_attr_img_info.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加属性图片插入数据 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_attr_img') {
            $result = array('error' => 0, 'message' => '', 'content' => '', 'is_checked' => 0);

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';
            $img_url = !empty($_REQUEST['img_url']) ? $_REQUEST['img_url'] : '';

            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|JEPG|PNG|';

            if (!empty($_FILES['attr_img_flie'])) {
                $other['attr_img_flie'] = get_upload_pic('attr_img_flie');
                $this->dscRepository->getOssAddFile(array($other['attr_img_flie']));
            } else {
                $other['attr_img_flie'] = '';
            }

            $goods_attr_date = array('attr_img_flie, attr_img_site');
            $goods_attr_info = get_table_date('wholesale_goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);

            if (empty($other['attr_img_flie'])) {
                $other['attr_img_flie'] = $goods_attr_info['attr_img_flie'];
            } else {
                dsc_unlink(storage_public($goods_attr_info['attr_img_flie']));
            }

            $other['attr_img_site'] = !empty($_REQUEST['attr_img_site']) ? $_REQUEST['attr_img_site'] : '';
            $other['attr_checked'] = !empty($_REQUEST['attr_checked']) ? intval($_REQUEST['attr_checked']) : 0;
            $other['attr_gallery_flie'] = $img_url;

            if ($other['attr_checked'] == 1) {
                $this->db->autoExecute($this->dsc->table('wholesale_goods_attr'), array('attr_checked' => 0), 'UPDATE', 'attr_id = ' . $attr_id . ' and goods_id = ' . $goods_id);
                $result['is_checked'] = 1;
            }

            $this->db->autoExecute($this->dsc->table('wholesale_goods_attr'), $other, 'UPDATE', 'goods_attr_id = ' . $goods_attr_id . ' and attr_id = ' . $attr_id . ' and goods_id = ' . $goods_id);

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除属性图片 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_attr_img') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;

            $sql = "select attr_img_flie from " . $this->dsc->table('wholesale_goods_attr') . " where goods_attr_id = '$goods_attr_id'";
            $attr_img_flie = $this->db->getOne($sql);

            $this->dscRepository->getOssDelFile(array($attr_img_flie));

            dsc_unlink(storage_public($attr_img_flie));
            $other['attr_img_flie'] = '';
            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('wholesale_goods_attr'), $other, "UPDATE", "goods_attr_id = '$goods_attr_id'");

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 选择属性图片 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'choose_attrImg') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_attr_id = empty($_REQUEST['goods_attr_id']) ? 0 : intval($_REQUEST['goods_attr_id']);

            $sql = "SELECT attr_gallery_flie FROM " . $GLOBALS['dsc']->table('wholesale_goods_attr') . " WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
            $attr_gallery_flie = $GLOBALS['db']->getOne($sql);

            /* 删除数据 */
            $sql = "SELECT img_id, thumb_url, img_url FROM " . $GLOBALS['dsc']->table('goods_gallery') . " WHERE goods_id = '$goods_id'";
            $img_list = $GLOBALS['db']->getAll($sql);

            $str = "<ul>";
            foreach ($img_list as $idx => $row) {
                if ($attr_gallery_flie == $row['img_url']) {
                    $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')" class="on"><img src="../' . $row['thumb_url'] . '" width="87" /><i><img src="images/yes.png"></i></li>';
                } else {
                    $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')"><img src="../' . $row['thumb_url'] . '" width="87" /><i><img src="images/gallery_yes.png" width="30" height="30"></i></li>';
                }
            }
            $str .= "</ul>";

            $result['content'] = $str;

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 选择属性图片 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_gallery_attr') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = intval($_REQUEST['goods_id']);
            $goods_attr_id = intval($_REQUEST['goods_attr_id']);
            $gallery_id = intval($_REQUEST['gallery_id']);

            if (!empty($gallery_id)) {
                $sql = "SELECT img_id, img_url FROM " . $this->dsc->table('goods_gallery') . "WHERE img_id='$gallery_id'";
                $img = $this->db->getRow($sql);
                $result['img_id'] = $img['img_id'];
                $result['img_url'] = $img['img_url'];

                $sql = "UPDATE " . $this->dsc->table('wholesale_goods_attr') . " SET attr_gallery_flie = '" . $img['img_url'] . "' WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
                $this->db->query($sql);
            } else {
                $result['error'] = 1;
            }

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @param string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true, $extension_code = '')
    {
        $href = 'wholesale.php?act=list';
        if (!empty($extension_code)) {
            $href .= '&extension_code=' . $extension_code;
        }

        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        $text = $GLOBALS['_LANG']['01_goods_list'];

        return array('href' => $href, 'text' => $text);
    }
}
