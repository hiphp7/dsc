<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use App\Models\GoodsActivity;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Package\PackageGoodsService;
use App\Services\Store\StoreCommonService;

/**
 * 超值礼包管理程序
 */
class PackageController extends InitController
{
    protected $dscRepository;
    protected $merchantCommonService;
    protected $storeCommonService;
    protected $goodsProdutsService;
    protected $packageGoodsService;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        StoreCommonService $storeCommonService,
        GoodsProdutsService $goodsProdutsService,
        PackageGoodsService $packageGoodsService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->storeCommonService = $storeCommonService;
        $this->goodsProdutsService = $goodsProdutsService;
        $this->packageGoodsService = $packageGoodsService;
    }

    public function index()
    {
        $exc = new Exchange($this->dsc->table("goods_activity"), $this->db, 'act_id', 'act_name');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "bonus");

        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));

        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '14_package_list']);

        /*------------------------------------------------------ */
        //-- 活动列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['14_package_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['package_add'], 'href' => 'package.php?act=add', 'class' => 'icon-plus']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $packages = $this->get_packagelist($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($packages, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('package_list', $packages['packages']);
            $this->smarty->assign('filter', $packages['filter']);
            $this->smarty->assign('record_count', $packages['record_count']);
            $this->smarty->assign('page_count', $packages['page_count']);

            $sort_flag = sort_flag($packages['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('package_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询、翻页、排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $packages = $this->get_packagelist($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($packages, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('package_list', $packages['packages']);
            $this->smarty->assign('filter', $packages['filter']);
            $this->smarty->assign('record_count', $packages['record_count']);
            $this->smarty->assign('page_count', $packages['page_count']);

            $sort_flag = sort_flag($packages['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return make_json_result(
                $this->smarty->fetch('package_list.dwt'),
                '',
                ['filter' => $packages['filter'], 'page_count' => $packages['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('package_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            /* 组合商品 */
            $group_goods_list = [];
            $sql = "DELETE FROM " . $this->dsc->table('package_goods') .
                " WHERE package_id = 0 AND admin_id = '" . session('seller_id') . "'";

            $this->db->query($sql);

            /* 初始化信息 */
            $start_time = local_date('Y-m-d H:i:s');
            $end_time = local_date('Y-m-d H:i:s', strtotime('+1 month'));
            $package = ['package_price' => '', 'start_time' => $start_time, 'end_time' => $end_time, 'ru_id' => $adminru['ru_id']];

            $this->smarty->assign('package', $package);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['package_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list', 'class' => 'icon-reply']);
            //分类列表 by wu
            set_default_filter(0, 0, $adminru['ru_id']); //by wu
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('ru_id', $adminru['ru_id']);


            return $this->smarty->display('package_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('package_manage');

            $sql = "SELECT COUNT(*) " .
                " FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='" . $_POST['package_name'] . "' AND user_id = '" . $adminru['ru_id'] . "'";
            if ($this->db->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['package_exist'], $_POST['package_name']), 1);
            }


            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['package_price'])) {
                $_POST['package_price'] = 0;
            }

            $info = ['package_price' => $_POST['package_price']];

            $activity_thumb = $image->upload_image($_FILES['activity_thumb'], 'activity_thumb');  //图片存放地址

            $this->dscRepository->getOssAddFile([$activity_thumb]);

            /* 插入数据 */
            $record = [
                'act_name' => $_POST['package_name'], 'act_desc' => $_POST['desc'],
                'act_type' => GAT_PACKAGE, 'start_time' => $_POST['start_time'],
                'user_id' => $adminru['ru_id'],
                'activity_thumb' => $activity_thumb,  //ecmoban模板堂 --zhuo
                'end_time' => $_POST['end_time'], 'is_finished' => 0,
                'ext_info' => serialize($info)
            ];

            $this->db->AutoExecute($this->dsc->table('goods_activity'), $record, 'INSERT');

            /* 礼包编号 */
            $package_id = $this->db->insert_id();

            $this->handle_packagep_goods($package_id);

            admin_log($_POST['package_name'], 'add', 'package');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list'];
            $link[] = ['text' => $GLOBALS['_LANG']['continue_add'], 'href' => 'package.php?act=add'];
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('package_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            $act_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $package = get_package_info($act_id, 0, 0, 0, "seller");

            if ($package['ru_id'] != $adminru['ru_id']) {
                $Loaction = "package.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            $package_goods_list = $this->packageGoodsService->getPackageGoods($act_id, session('seller_id'), 1); // 礼包商品

            $this->smarty->assign('package', $package);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['package_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list&' . list_link_postfix(), 'class' => 'icon-reply']);
            //分类列表 by wu
            $select_category_html = '';
            $select_category_html .= insert_select_category(0, 0, 0, 'category', 1);
            $this->smarty->assign('select_category_html', $select_category_html);
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('form_action', 'update');
            $this->smarty->assign('package_goods_list', $package_goods_list);
            $this->smarty->assign('ru_id', $package['ru_id']);


            return $this->smarty->display('package_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('package_manage');

            $act_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;

            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['package_price'])) {
                $_POST['package_price'] = 0;
            }

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='" . $_POST['package_name'] . "' AND act_id <> '$act_id' AND user_id = '" . $adminru['ru_id'] . "'";
            if ($this->db->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['package_exist'], $_POST['package_name']), 1);
            }

            $activity_thumb = $image->upload_image($_FILES['activity_thumb'], 'activity_thumb');  //图片存放地址
            $this->dscRepository->getOssAddFile([$activity_thumb]);

            $info = ['package_price' => $_POST['package_price']];

            /* 更新数据 */
            $record = [
                'act_name' => $_POST['package_name'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'act_desc' => $_POST['desc'],
                'ext_info' => serialize($info)
            ];

            $record['review_status'] = 1;

            if (!empty($activity_thumb)) {
                $record['activity_thumb'] = $activity_thumb;
            }

            $this->db->autoExecute($this->dsc->table('goods_activity'), $record, 'UPDATE', "act_id = '$act_id' AND act_type = " . GAT_PACKAGE);

            admin_log($_POST['package_name'], 'edit', 'package');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除活动图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_thumb') {
            /* 权限判断 */
            admin_priv('package_manage');
            $act_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $ru_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

            /* 取得logo名称 */
            $sql = "SELECT activity_thumb FROM " . $this->dsc->table('goods_activity') . " WHERE act_id = '$act_id'";
            $activity_thumb = $this->db->getOne($sql);

            if (!empty($activity_thumb)) {
                $this->dscRepository->getDelBatch('', $act_id, ['activity_thumb'], 'act_id', GoodsActivity::whereRaw(1), 1); //删除图片

                $sql = "UPDATE " . $this->dsc->table('goods_activity') . " SET activity_thumb = '' WHERE act_id = '$act_id'";
                $this->db->query($sql);
            }
            $link = [['text' => $GLOBALS['_LANG']['edit_package'], 'href' => 'package.php?act=edit&id=' . $act_id], ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_package_thumb_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除指定的活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $package = get_package_info($id, 0, 0, 0, "seller");

            if ($package['ru_id'] != $adminru['ru_id']) {
                $url = 'package.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            $this->dscRepository->getDelBatch('', $id, ['activity_thumb'], 'act_id', GoodsActivity::whereRaw(1), 1); //删除图片

            $exc->drop($id);

            $sql = "DELETE FROM " . $this->dsc->table('package_goods') .
                " WHERE package_id='$id'";
            $this->db->query($sql);

            $url = 'package.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑活动名称
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_package_name') {
            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='$val' AND act_id <> '$id'";
            if ($this->db->getOne($sql)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['package_exist'], $val));
            }

            $exc->edit("act_name='$val'", $id);
            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {

            $filters = dsc_decode($_GET['JSON']);

            $arr = get_goods_list($filters);

            $opt = [];
            foreach ($arr as $key => $val) {
                $opt[$key] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => $val['shop_price']];

                $opt[$key]['products'] = $this->goodsProdutsService->getGoodProducts($val['goods_id']);
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 增加一个商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_package_goods') {
            $result = [];
            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0; // 0为多商品，1为单商品

            $goods_ids = !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : '';
            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $number = isset($_REQUEST['num']) ? intval($_REQUEST['num']) : 1;
            $pbtype = !empty($_REQUEST['pbtype']) ? trim($_REQUEST['pbtype']) : '';
            if ($goods_ids) {
                $goods_ids_arr = explode(',', $goods_ids);
                if (!empty($goods_ids_arr)) {
                    foreach ($goods_ids_arr as $v) {

                        $where = '';
                        if (empty($package_id)) {
                            $where = " AND admin_id = '" . session('seller_id') . "'";
                        }

                        $sql = "SELECT COUNT(*) FROM" . $this->dsc->table('package_goods') . "WHERE goods_id = '" . $v . "' AND package_id = '$package_id' $where LIMIT 1";
                        $goods_count = $this->db->getOne($sql);

                        if ($goods_count == 0) {
                            $sql = "INSERT INTO " . $this->dsc->table('package_goods') . " (package_id, goods_id, goods_number, admin_id) " .
                                "VALUES ('$package_id', '" . $v . "', '$number', '" . session('seller_id') . "')";
                            $this->db->query($sql, 'SILENT');
                        }
                    }
                }
            }

            $arr = $this->packageGoodsService->getPackageGoods($package_id, session('seller_id'), 1);
            $this->smarty->assign('pbtype', $pbtype);
            $this->smarty->assign('package_goods_list', $arr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/getsearchgoodsdiv.lbi');
            clear_cache_files();
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除一个商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'drop_package_goods') {
            $result = [];

            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('package_goods') .
                " WHERE package_id='$package_id' AND goods_id = '$goods_id' AND product_id = '$product_id'";
            if ($package_id == 0) {
                $sql .= " AND admin_id = '" . session('seller_id') . "'";
            }
            $this->db->query($sql);

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 编辑一个商品数量
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_package_nuber') {
            $result = ['error' => 0];

            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
            $num = isset($_REQUEST['num']) ? intval($_REQUEST['num']) : 0;
            $where = " WHERE package_id='$package_id' AND goods_id = '$goods_id' AND product_id = '$product_id'";
            if ($num > 0) {
                $sql = "UPDATE" . $this->dsc->table('package_goods') .
                    " SET goods_number = '$num' " . $where;

                $this->db->query($sql);
            } else {
                $sql = "SELECT goods_number FROM" . $this->dsc->table('package_goods') . $where;
                $goods_number = $this->db->getOne($sql);
                if ($goods_number > 0) {
                    $result['goods_number'] = $goods_number;
                } else {
                    $result['goods_number'] = 1;
                }

                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['goods_number_must_large_0_int'];
            }

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 编辑一个商品属性
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_package_product') {
            $result = ['error' => 0];

            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
            $old_product_id = isset($_REQUEST['old_product_id']) ? intval($_REQUEST['old_product_id']) : 0;
            $where = " WHERE package_id='$package_id' AND goods_id = '$goods_id'";
            if ($product_id > 0) {
                //判断改属性商品是否存在
                $sql = "SELECT COUNT(*) FROM" . $this->dsc->table('package_goods') . $where . " AND product_id = '$product_id'";
                $product_count = $this->db->getOne($sql);
                if ($product_count > 0) {
                    $result['error'] = 1;
                    $result['msg'] = $GLOBALS['_LANG']['goods_attr_exist_reselect'];
                } else {
                    $sql = "UPDATE" . $this->dsc->table('package_goods') .
                        " SET product_id = '$product_id' " . $where . "AND product_id = '$old_product_id'";
                    $this->db->query($sql);
                }
            } else {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['system_error_refresh_retry'];
            }

            return response()->json($result);
        }
    }

    /**
     * 获取活动列表
     *
     * @access  public
     *
     * @return void
     */
    private function get_packagelist($ru_id)
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

            $where = (!empty($filter['keywords'])) ? " AND ga.act_name like '%" . mysql_like_quote($filter['keywords']) . "%'" : '';

            //ecmoban模板堂 --zhuo start
            if ($ru_id > 0) {
                $where .= " and ga.user_id = '$ru_id'";
            }
            //ecmoban模板堂 --zhuo end

            if ($filter['review_status']) {
                $where .= " AND ga.review_status = '" . $filter['review_status'] . "' ";
            }

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
                        $where .= ' AND ga.user_id = 0';
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods_activity') . " AS ga " .
                " WHERE ga.act_type =" . GAT_PACKAGE . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            $filter = page_and_size($filter);

            /* 获活动数据 */
            $sql = "SELECT ga.act_id, ga.act_name AS package_name, ga.start_time, ga.end_time, ga.is_finished, ga.ext_info, ga.user_id, ga.goods_id, ga.review_status, ga.review_content " .
                " FROM " . $this->dsc->table('goods_activity') . " AS ga " .
                " WHERE ga.act_type = " . GAT_PACKAGE . $where .
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

            $row[$key]['ru_name'] = $this->merchantCommonService->getShopName($val['user_id'], 1); //ecmoban模板堂 --zhuo
        }

        $arr = ['packages' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 保存某礼包的商品
     * @param int $package_id
     * @return  void
     */
    private function handle_packagep_goods($package_id)
    {
        $sql = "UPDATE " . $this->dsc->table('package_goods') . " SET " .
            " package_id = '$package_id' " .
            " WHERE package_id = '0'" .
            " AND admin_id = '" . session('seller_id') . "'";
        $this->db->query($sql);
    }
}
