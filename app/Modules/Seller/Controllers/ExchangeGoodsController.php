<?php

namespace App\Modules\Seller\Controllers;

use App\Models\ExchangeGoods;
use App\Repositories\Common\BaseRepository;
use App\Services\Exchange\ExchangeGoodsManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;


/**
 * 管理中心积分兑换商品程序文件
 */
class ExchangeGoodsController extends InitController
{
    protected $merchantCommonService;
    protected $storeCommonService;
    protected $baseRepository;
    protected $exchangeGoodsManageService;


    public function __construct(
        ExchangeGoodsManageService $exchangeGoodsManageService,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->exchangeGoodsManageService = $exchangeGoodsManageService;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "bonus");

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));

        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '15_exchange_goods']);

        /*------------------------------------------------------ */
        //-- 商品列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            /* 取得过滤条件 */
            $filter = [];
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['15_exchange_goods_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['exchange_goods_add'], 'href' => 'exchange_goods.php?act=add', 'class' => 'icon-plus']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('filter', $filter);

            $goods_list = $this->exchangeGoodsManageService->getExchangeGoodslist($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($goods_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('goods_list', $goods_list['arr']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('exchange_goods_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('exchange_goods');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $goods_list = $this->exchangeGoodsManageService->getExchangeGoodslist($adminru['ru_id']);

            //分页
            $page_count_arr = seller_page($goods_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('goods_list', $goods_list['arr']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);

            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('exchange_goods_list.dwt'),
                '',
                ['filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('exchange_goods');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            /*初始化*/
            $goods = [];
            $goods['is_exchange'] = 1;
            $goods['is_hot'] = 0;
            $goods['option'] = '<li><a href="javascript:;" data-value="0" class="ftx-01">' . $GLOBALS['_LANG']['make_option'] . '</a></li>';

            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['exchange_goods_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('ru_id', $adminru['ru_id']);


            return $this->smarty->display('exchange_goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            $goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;

            /*检查是否重复*/
            $is_only = ExchangeGoods::where('goods_id', $goods_id)->count();

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['goods_exist'], 1);
            }

            /* 插入数据 */
            $record = [
                'goods_id' => intval($_POST['goods_id']),
                'exchange_integral' => intval($_POST['exchange_integral']),
                'market_integral' => intval($_POST['market_integral']),
                'is_exchange' => intval($_POST['is_exchange']),
                'is_hot' => intval($_POST['is_hot']),
                'is_best' => intval($_POST['is_best']),
                'user_id' => $adminru['ru_id']
            ];

            ExchangeGoods::insert($record);

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'exchange_goods.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'exchange_goods.php?act=list';

            admin_log($_POST['goods_id'], 'add', 'exchange_goods');

            clear_cache_files(); // 清除相关的缓存文件

            return sys_msg($GLOBALS['_LANG']['articleadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('exchange_goods');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 取商品数据 */
            $res = ExchangeGoods::select('goods_id',
                'exchange_integral',
                'market_integral',
                'is_exchange',
                'is_hot',
                'is_best',
                'user_id',
                'review_status',
                'review_content'
            );
            $res = $res->where('eid', $id);
            $res = $res->with(['getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name');
            }]);
            $goods = $this->baseRepository->getToArrayFirst($res);
            if (!$goods) {
                return sys_msg($GLOBALS['_LANG']['not_select_date'], 1);
            }

            if (isset($goods) && !empty($goods['get_goods'])) {
                $goods['goods_name'] = $goods['get_goods']['goods_name'];
            }

            if (!empty($goods['user_id']) && $goods['user_id'] != $adminru['ru_id']) {
                $Loaction = "exchange_goods.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            $goods['option'] = '<option value="' . $goods['goods_id'] . '">' . $goods['goods_name'] . '</option>';
            $goods['option'] = '<li><a href="javascript:;" data-value="' . $goods['goods_id'] . '" class="ftx-01">' . $goods['goods_name'] . '</a></li>';
            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['exchange_goods_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list&' . list_link_postfix(), 'class' => 'icon-reply']);
            $this->smarty->assign('form_action', 'update');
            $this->smarty->assign('ru_id', $adminru['ru_id']);


            return $this->smarty->display('exchange_goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            $goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
            $exchange_integral = !empty($_POST['exchange_integral']) ? intval($_POST['exchange_integral']) : 0;
            $market_integral = !empty($_POST['market_integral']) ? intval($_POST['market_integral']) : 0;
            $is_exchange = !empty($_POST['is_exchange']) ? intval($_POST['is_exchange']) : 0;
            $is_hot = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
            $is_best = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;

            /* 更新数据 */
            $record = [
                'goods_id' => $goods_id,
                'exchange_integral' => $exchange_integral,
                'market_integral' => $market_integral,
                'is_exchange' => $is_exchange,
                'is_hot' => $is_hot,
                'is_best' => $is_best
            ];

            $record['review_status'] = 1;

            ExchangeGoods::where('goods_id', $goods_id)->update($record);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'exchange_goods.php?act=list&' . list_link_postfix();

            admin_log($goods_id, 'edit', 'exchange_goods');

            clear_cache_files();
            return sys_msg($GLOBALS['_LANG']['articleedit_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑使用积分值
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_exchange_integral') {
            $check_auth = check_authz_json('exchange_goods');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $exchange_integral = floatval($_POST['val']);

            if ($exchange_integral <= 0) {
                return make_json_error($GLOBALS['_LANG']['exchange_integral_invalid']);
            } else {
                $data = ['exchange_integral' => $exchange_integral];
                $res = ExchangeGoods::where('eid', $id)->update($data);
                if ($res) {
                    clear_cache_files();
                    admin_log($id, 'edit', 'exchange_goods');
                    return make_json_result(stripslashes($exchange_integral));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否�        �换
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_exchange') {
            $check_auth = check_authz_json('exchange_goods');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['is_exchange' => $val];
            ExchangeGoods::where('eid', $id)->update($data);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 切换是否热销
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_hot') {
            $check_auth = check_authz_json('exchange_goods');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['is_hot' => $val];
            ExchangeGoods::where('eid', $id)->update($data);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 切换是否精品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_best') {
            $check_auth = check_authz_json('exchange_goods');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['is_best' => $val];
            ExchangeGoods::where('eid', $id)->update($data);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 批量删除商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove') {
            admin_priv('exchange_goods');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_goods'], 1);
            }

            $count = 0;
            foreach ($_POST['checkboxes'] as $key => $id) {
                $res = ExchangeGoods::where('eid', $id)->delete();
                if ($res) {
                    admin_log($id, 'remove', 'exchange_goods');
                    $count++;
                }
            }

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'exchange_goods.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['batch_remove_succeed'], $count), 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 删除商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('exchange_goods');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 取商品数据 */
            $user_id = ExchangeGoods::where('eid', $id)->value('user_id');

            if ($user_id != $adminru['ru_id']) {
                $url = 'exchange_goods.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            $res = ExchangeGoods::where('eid', $id)->delete();
            if ($res) {
                admin_log($id, 'remove', 'exchange_goods');
                clear_cache_files();
            }

            $url = 'exchange_goods.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {

            $filters = dsc_decode($_GET['JSON']);

            $arr = get_goods_list($filters);

            return make_json_result($arr);
        }
    }
}
