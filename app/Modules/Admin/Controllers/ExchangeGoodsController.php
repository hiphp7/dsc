<?php

namespace App\Modules\Admin\Controllers;

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
    protected $baseRepository;
    protected $exchangeGoodsManageService;
    protected $storeCommonService;

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
        //$image = new Image();

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 商品列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            /* 取得过滤条件 */
            $filter = [];
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['15_exchange_goods_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['exchange_goods_add'], 'href' => 'exchange_goods.php?act=add']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('filter', $filter);

            $goods_list = $this->exchangeGoodsManageService->getExchangeGoodslist($adminru['ru_id']);

            $this->smarty->assign('goods_list', $goods_list['arr']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));


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

            $goods_list = $this->exchangeGoodsManageService->getExchangeGoodslist($adminru['ru_id']);

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

            /*初始化*/
            $goods = [];
            $goods['is_exchange'] = 1;
            $goods['is_hot'] = 0;
            $goods['option'] = '<option value="0">' . $GLOBALS['_LANG']['make_option'] . '</option>';

            set_default_filter(); //设置默认筛选

            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['exchange_goods_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list']);
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
                'user_id' => $adminru['ru_id'],
                'review_status' => 3
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
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
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
            $res = $res->where('goods_id', $id);
            $res = $res->with(['getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name');
            }]);
            $goods = $this->baseRepository->getToArrayFirst($res);
            if (isset($goods) && !empty($goods['get_goods'])) {
                $goods['goods_name'] = $goods['get_goods']['goods_name'];
            }

            //$goods['li']  = "<li><a href='javascript:;' selectid='".$goods['goods_id']."'  class='ftx-01'>".$goods['goods_name']."</a></li>";

            set_default_filter(); //设置默认筛选

            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_new_goods']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list&' . list_link_postfix()]);
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

            if (isset($_POST['review_status'])) {
                $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                $record['review_status'] = $review_status;
                $record['review_content'] = $review_content;
            }

            /* 更新数据 */
            $record = [
                'goods_id' => $goods_id,
                'exchange_integral' => $exchange_integral,
                'market_integral' => $market_integral,
                'is_exchange' => $is_exchange,
                'is_hot' => $is_hot,
                'is_best' => $is_best
            ];

            if (isset($_POST['review_status'])) {
                $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                $record['review_status'] = $review_status;
                $record['review_content'] = $review_content;
            }

            ExchangeGoods::where('goods_id', $goods_id)->update($record);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'exchange_goods.php?act=list&' . list_link_postfix();

            admin_log($goods_id, 'edit', 'exchange_goods');

            clear_cache_files();
            return sys_msg($GLOBALS['_LANG']['articleedit_succeed'], 0, $link);
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
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('exchange_goods');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_date'], 1);
            }
            $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    $count = 0;
                    foreach ($ids as $key => $id) {
                        $res = ExchangeGoods::where('eid', $id)->delete();
                        if ($res > 0) {
                            admin_log($id, 'remove', 'exchange_goods');
                            $count++;
                        }
                    }

                    $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'exchange_goods.php?act=list'];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_remove_succeed'], $count), 0, $lnk);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];

                    $data = ['review_status' => $review_status];
                    $ids = $this->baseRepository->getExplode($ids);
                    $res = ExchangeGoods::whereIn('eid', $ids)->update($data);
                    if ($res > 0) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'exchange_goods.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['integral_goods_adopt_status_success'], 0, $lnk);
                    }
                }
            }
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
            $res = ExchangeGoods::where('eid', $id)->delete();
            if ($res > 0) {
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
