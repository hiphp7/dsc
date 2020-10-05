<?php

namespace App\Modules\Seller\Controllers;

use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsManageService;
use App\Services\Store\StoreCommonService;
use App\Services\Wholesale\GoodsManageService as WholesaleGoodsManage;

/**
 * 记录管理员操作日志
 */
class GoodsLibController extends InitController
{
    protected $wholesaleGoodsManage;
    protected $goodsManageService;
    protected $commonRepository;
    protected $dscRepository;
    protected $storeCommonService;

    public function __construct(
        WholesaleGoodsManage $wholesaleGoodsManage,
        GoodsManageService $goodsManageService,
        CommonRepository $commonRepository,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->wholesaleGoodsManage = $wholesaleGoodsManage;
        $this->goodsManageService = $goodsManageService;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper(['goods'], 'seller');

        /* 管理员ID */
        $admin_id = get_admin_id();

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        $ru_id = $adminru['ru_id'];
        $this->smarty->assign('review_goods', $GLOBALS['_CFG']['review_goods']);

        /*------------------------------------------------------ */
        //-- 商品列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            admin_priv('goods_lib_list');

            //清楚商品零时货品表数据
            $sql = "DELETE FROM" . $GLOBALS['dsc']->table('products_changelog') . "WHERE admin_id = '" . $admin_id . "'";
            $GLOBALS['db']->query($sql);

            //清楚商品属性
            $sql = "DELETE FROM" . $GLOBALS['dsc']->table('goods_attr') . "WHERE admin_id = '" . $admin_id . "' AND goods_id = 0";
            $GLOBALS['db']->query($sql);

            get_del_goodsimg_null();
            get_del_goods_gallery();
            get_updel_goods_attr();

            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

            //页面分菜单 by wu start
            $tab_menu = array();
            $tab_menu[] = array('curr' => 1, 'text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=list');

            $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();

            /* 批发权限 */
            if ($supplierEnabled) {
                $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['standard_goods_lib'], 'href' => 'goods_lib.php?act=lib_list&standard_goods=1');
            }
            $this->smarty->assign('tab_menu', $tab_menu);

            //页面分菜单 by wu end

            $this->smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => '04_goods_lib_list'));
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['20_goods_lib']);

            $action_link = array('href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']);
            $this->smarty->assign('action_link', $action_link);
            $this->smarty->assign('code', $code);
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('store_brand', get_store_brand_list()); //商家品牌

            $intro_list = $this->goodsManageService->getIntroList();
            $this->smarty->assign('intro_list', $intro_list);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('list_type', $_REQUEST['act'] == 'list' ? 'goods' : 'trash');
            $this->smarty->assign('use_storage', empty($_CFG['use_storage']) ? 0 : 1);

            $goods_list = lib_goods_list();
            $this->smarty->assign('goods_list', $goods_list['goods']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);
            $this->smarty->assign('full_page', 1);

            /* 排序标记 */
            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 获取商品类型存在规格的类型 */
            $specifications = get_goods_type_specifications();
            $this->smarty->assign('specifications', $specifications);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('nowTime', gmtime());

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($goods_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            set_default_filter(); //设置默认筛选

            $this->smarty->assign('cfg', $GLOBALS['_CFG']);
            return $this->smarty->display('goods_lib_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('goods_lib_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $goods_list = lib_goods_list();
            $this->smarty->assign('code', $code);
            $this->smarty->assign('goods_list', $goods_list['goods']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);
            $this->smarty->assign('use_storage', empty($_CFG['use_storage']) ? 0 : 1);

            /* 排序标记 */
            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 获取商品类型存在规格的类型 */
            $specifications = get_goods_type_specifications();
            $this->smarty->assign('specifications', $specifications);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('nowTime', gmtime());

            //分页
            $page_count_arr = seller_page($goods_list, $_REQUEST['page']);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            set_default_filter(); //设置默认筛选

            return make_json_result(
                $this->smarty->fetch('goods_lib_list.dwt'),
                '',
                array('filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count'])
            );
        }
        /*------------------------------------------------------ */
        //-- 标准产品库列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'lib_list') {

            admin_priv('goods_lib_list');

            load_helper(['wholesale']);

            //清楚商品零时货品表数据
            $sql = "DELETE FROM" . $GLOBALS['dsc']->table('products_changelog') . "WHERE admin_id = '" . $admin_id . "'";
            $GLOBALS['db']->query($sql);

            //清楚商品属性
            $sql = "DELETE FROM" . $GLOBALS['dsc']->table('goods_attr') . "WHERE admin_id = '" . $admin_id . "' AND goods_id = 0";
            $GLOBALS['db']->query($sql);

            $standard_goods = !empty($_REQUEST['standard_goods']) ? intval($_REQUEST['standard_goods']) : 0;

            //商家ID，商家等级赋值
            $sql = " SELECT grade_id FROM " . $this->dsc->table('merchants_grade') . " WHERE ru_id = '$ru_id' ";
            $grade_id = $this->db->getOne($sql);


            //页面分菜单 by wu start
            $tab_menu = array();
            $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=list');
            $tab_menu[] = array('curr' => 1, 'text' => $GLOBALS['_LANG']['standard_goods_lib'], 'href' => 'goods_lib.php?act=lib_list&standard_goods=1');
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $this->smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => '04_goods_lib_list'));
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);

            /* 模板赋值 */
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['standard_goods_lib']);

            $seller = array();
            $seller['grade_id'] = $grade_id;
            $seller['ru_id'] = $ru_id;

            $list = $this->wholesaleGoodsManage->getWholesaleList(0, $seller);

            $this->smarty->assign('goods_list', $list['goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

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
            $this->smarty->assign('action', 'wholesale_list');

            /* 显示商品列表页面 */
            return $this->smarty->display("goods_lib_list.dwt");
        }
        /*------------------------------------------------------ */
        //-- 标准产品库排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'lib_query') {
            $standard_goods = !empty($_REQUEST['standard_goods']) ? intval($_REQUEST['standard_goods']) : 0;

            //商家ID，商家等级赋值
            $sql = " SELECT grade_id FROM " . $this->dsc->table('merchants_grade') . " WHERE ru_id = '$ru_id' ";
            $grade_id = $this->db->getOne($sql);
            $seller = array();
            $seller['grade_id'] = $grade_id;
            $seller['ru_id'] = $ru_id;

            $list = $this->wholesaleGoodsManage->getWholesaleList(0, $seller);
            $this->smarty->assign('goods_list', $list['goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $this->smarty->assign('standard_goods', $standard_goods);
            set_default_filter(); //设置默认筛选

            /* 显示商品列表页面 */
            return make_json_result(
                $this->smarty->fetch('goods_lib_list.dwt'),
                '',
                array('filter' => $list['filter'], 'page_count' => $list['page_count'])
            );
        }

        /* ------------------------------------------------------ */
        //-- 商家导入商品库商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'seller_import') {
            $check_auth = check_authz_json('goods_lib_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            //清楚商品零时货品表数据
            $sql = "DELETE FROM" . $GLOBALS['dsc']->table('products_changelog') . "WHERE admin_id = '" . $admin_id . "'";
            $GLOBALS['db']->query($sql);

            //清楚商品零时货品表数据
            $sql = "DELETE FROM" . $GLOBALS['dsc']->table('goods_attr') . "WHERE admin_id = '" . $admin_id . "' AND goods_id = 0";
            $GLOBALS['db']->query($sql);

            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $standard = !empty($_REQUEST['standard']) ? intval($_REQUEST['standard']) : 0;//标准库标识 1为标准库

            //初始化查询表，查询目标,商品值
            $goods = array();
            $table = "goods_lib";
            $select = ' ,shop_price';
            if ($standard == 1) {
                $table = 'wholesale';
                $sql = " SELECT is_delivery,is_return,is_free  FROM " . $this->dsc->table('wholesale_extend') . " WHERE goods_id  = '$goods_id' ";
                $goods = $this->db->getRow($sql);

                $select = ',goods_cause,retail_price as shop_price';
            }
            $sql = " SELECT goods_name,cat_id,brand_id $select FROM " . $this->dsc->table($table) . " WHERE goods_id  = '$goods_id' ";
            $table_info = $this->db->getRow($sql);
            $goods['shop_price'] = $table_info['shop_price'] ?? 0;
            $goods['goods_name'] = $table_info['goods_name'] ?? '';
            $goods['goods_cause'] = $table_info['goods_cause'] ?? '';
            $goods['cat_id'] = $table_info['cat_id'] ?? 0;
            $goods['brand_id'] = $table_info['brand_id'] ?? 0;
            /*退换货标志列表*/
            $cause_list = array('0', '1', '2', '3');

            /* 判断商品退换货理由 */
            if (!is_null($goods['goods_cause'])) {
                $res = array_intersect(explode(',', $goods['goods_cause']), $cause_list);
            } else {
                $res = array();
            }

            if ($res) {
                $this->smarty->assign('is_cause', $res);
            } else {
                $res = array();
                $this->smarty->assign('is_cause', $res);
            }

            $goods['goods_id'] = $goods_id;

            $goods['store_hot'] = 1;
            $goods['store_new'] = 1;
            $goods['store_best'] = 1;
            set_seller_default_filter(0, 0, $adminru['ru_id']); //by wu
            set_default_filter(0, 0, $adminru['ru_id']); //设置默认筛选

            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('standard', $standard);
            $this->smarty->assign('transport_list', get_table_date("goods_transport", "ru_id = '" . $adminru['ru_id'] . "'", array('tid, title'), 1)); //商品运费 by wu
            $result['content'] = $GLOBALS['smarty']->fetch('library/seller_import_list.lbi');

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 商家导入商品库商品执行程序
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_import_action') {
            admin_priv('goods_lib_list');
            $standard = !empty($_REQUEST['standard']) ? intval($_REQUEST['standard']) : 0; //标准库标识 1为标准库

            $lib_goods_id = isset($_POST['lib_goods_id']) ? intval($_POST['lib_goods_id']) : 0;
            $goods_sn = isset($_POST['goods_sn']) ? trim($_POST['goods_sn']) : 0;
            $goods_number = isset($_POST['goods_number']) ? intval($_POST['goods_number']) : 0;
            $store_best = isset($_POST['store_best']) ? intval($_POST['store_best']) : 0; //精品
            $store_new = isset($_POST['store_new']) ? intval($_POST['store_new']) : 0; //新品
            $store_hot = isset($_POST['store_hot']) ? intval($_POST['store_hot']) : 0; //热销
            $is_reality = isset($_POST['is_reality']) ? intval($_POST['is_reality']) : 0; //正品保证
            $is_return = isset($_POST['is_return']) ? intval($_POST['is_return']) : 0; //包退服务
            $is_fast = isset($_POST['is_fast']) ? intval($_POST['is_fast']) : 0; //闪速配送
            $is_shipping = isset($_POST['is_shipping']) ? intval($_POST['is_shipping']) : 0; //免运费
            $is_on_sale = isset($_POST['is_on_sale']) ? intval($_POST['is_on_sale']) : 0; //上下架
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0; //分类
            $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0; //品牌
            $new_goods_type = isset($_POST['new_goods_type']) ? intval($_POST['new_goods_type']) : 0; //属性
            $goods_name = !empty($_POST['goods_name']) ? trim($_POST['goods_name']) : '';
            $shop_price = !empty($_POST['shop_price']) ? trim($_POST['shop_price']) : 0;
            $shop_price = floatval($shop_price);
            $user_cat = !empty($_POST['user_cat']) ? intval($_POST['user_cat']) : 0;//商家分类

            $review_status = 1;
            if ($GLOBALS['_CFG']['review_goods'] == 0) {
                $review_status = 5;
            } else {
                if ($adminru['ru_id'] > 0) {
                    $sql = "SELECT review_goods FROM " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
                    $review_goods = $this->db->getOne($sql, true); //判断
                    if ($review_goods == 0) {
                        $review_status = 5;
                    }
                } else {
                    $review_status = 5;
                }
            }
            /* 检查是否选择分类 */
            if ($category_id == 0) {
                return sys_msg($GLOBALS['_LANG']['category_null'], 1, array(), false);
            }
            /* 商品运费 by wu start */
            $freight = empty($_POST['freight']) ? 0 : intval($_POST['freight']);
            $shipping_fee = !empty($_POST['shipping_fee']) && $freight == 1 ? floatval($_POST['shipping_fee']) : '0.00';
            $tid = !empty($_POST['tid']) && $_POST['freight'] == 2 ? intval($_POST['tid']) : 0;
            /* 商品运费 by wu end */

            //退货标识
            $goods_cause = "";
            $cause = !empty($_REQUEST['return_type']) ? $_REQUEST['return_type'] : [];

            if ($cause) {
                for ($i = 0; $i < count($cause); $i++) {
                    if ($i == 0) {
                        $goods_cause = $cause[$i];
                    } else {
                        $goods_cause = $goods_cause . "," . $cause[$i];
                    }
                }
            }

            /* 检查货号是否重复 */
            if ($_POST['goods_sn']) {
                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods') .
                    " WHERE goods_sn = '$_POST[goods_sn]' AND is_delete = 0 ";
                if ($this->db->getOne($sql) > 0) {
                    return sys_msg($GLOBALS['_LANG']['goods_sn_exists'], 1, array(), false);
                }
            }

            /* 如果没有输入商品货号则自动生成一个商品货号 */
            if (empty($_POST['goods_sn'])) {
                $max_id = intval($_REQUEST['lib_goods_id']);
                $goods_sn = $this->goodsManageService->generateGoodSn($max_id);
            } else {
                $goods_sn = trim($_POST['goods_sn']);
            }
            if ($standard == 1) {
                $sql = " SELECT * FROM " . $this->dsc->table('wholesale') . " WHERE goods_id = '$lib_goods_id' ";
                $goods = $this->db->getRow($sql);
                if (empty($goods_name)) {
                    $goods_name = $goods['goods_name'];
                }
                $goods_thumb = $this->copy_img($goods['goods_thumb'], 'goods_thumb', $lib_goods_id);
                $goods_img = $this->copy_img($goods['goods_img'], 'goods', $lib_goods_id);
                $original_img = $this->copy_img($goods['original_img'], 'goods', $lib_goods_id);
                //注释：批发商品建议零售价对应零售商品的市场价和商品价   商品价格对应成本价
                $sql = "INSERT INTO " . $this->dsc->table('goods') .
                    "(cat_id, user_id, goods_sn, bar_code, goods_name,  brand_id, goods_weight, market_price," .
                    " cost_price, shop_price, keywords, goods_brief, goods_desc, desc_mobile, goods_thumb, goods_img, original_img, add_time, " .
                    " goods_number, store_best, store_new, store_hot, is_shipping, is_on_sale, " .
                    " is_real,  sort_order, goods_type,last_update,  pinyin_keyword, goods_cause ,warn_number,goods_product_tag,goods_unit,freight,shipping_fee,tid,user_cat,review_status) " .
                    " SELECT '$category_id','$ru_id','$goods_sn',bar_code,'$goods_name', '$brand_id',goods_weight,retail_price,goods_price" .
                    ",'$shop_price',keywords,goods_brief,goods_desc,desc_mobile,'$goods_thumb','$goods_img','$original_img','" . gmtime() . "'," .
                    "'$goods_number', '$store_best', '$store_new', '$store_hot', '$is_shipping', '$is_on_sale',1,sort_order,'$new_goods_type','" . gmtime() . "'," .
                    "pinyin_keyword,'$goods_cause',warn_number,goods_product_tag,goods_unit,'$freight','$shipping_fee','$tid','$user_cat', '$review_status' FROM" . $this->dsc->table('wholesale') . "WHERE goods_id = '$lib_goods_id'";
                $this->db->query($sql);
                $goods_id = $this->db->insert_id();

                //同步扩展信息
                $sql = "INSERT INTO" . $this->dsc->table('goods_extend') . "(goods_id,width,height,depth,origincountry,originplace,assemblycountry,barcodetype,"
                    . "catena,isbasicunit,packagetype,grossweight,netweight,netcontent,licensenum,healthpermitnum) SELECT '$goods_id',width,height,depth,origincountry,originplace,assemblycountry,barcodetype,"
                    . "catena,isbasicunit,packagetype,grossweight,netweight,netcontent,licensenum,healthpermitnum FROM" . $this->dsc->table('wholesale_extend') . "WHERE goods_id = '$lib_goods_id'";
                $this->db->query($sql);
                //获取标准产品库商品相册
                $res = $this->db->getAll(" SELECT img_desc, img_url, thumb_url, img_original FROM " . $this->dsc->table('suppliers_goods_gallery') . " WHERE goods_id = '$lib_goods_id' ");
            } else {
                $sql = " SELECT * FROM " . $this->dsc->table('goods_lib') . " WHERE goods_id = '$lib_goods_id' ";
                $goods = $this->db->getRow($sql);
                if (empty($goods_name)) {
                    $goods_name = $goods['goods_name'];
                }
                $goods['goods_from'] = $goods['goods_from'] ?? 0;
                if (!($this->db->getOne(" SELECT goods_id FROM " . $this->dsc->table('goods') . " WHERE goods_id ='" . $goods['lib_goods_id'] . "' AND user_id = '$ru_id' "))) {
                    $goods_thumb = $this->copy_img($goods['goods_thumb'], 'goods_thumb', $lib_goods_id);
                    $goods_img = $this->copy_img($goods['goods_img'], 'goods', $lib_goods_id);
                    $original_img = $this->copy_img($goods['original_img'], 'goods', $lib_goods_id);

                    $sql = "INSERT INTO " . $this->dsc->table('goods') .
                        "(cat_id, user_id, goods_sn, bar_code, goods_name, goods_name_style, brand_id, goods_weight, market_price," .
                        " cost_price, shop_price, keywords, goods_brief, goods_desc, desc_mobile, goods_thumb, goods_img, original_img, add_time, " .
                        " goods_number, store_best, store_new, store_hot, is_shipping, is_on_sale, " .
                        " is_real, extension_code, sort_order, goods_type, is_check, largest_amount, pinyin_keyword, from_seller,goods_cause,freight,shipping_fee,tid,user_cat,review_status) " .
                        " VALUES " .
                        "('$category_id', '$ru_id', '$goods_sn', '$goods[bar_code]', '$goods_name', '$goods[goods_name_style]', '$brand_id', '$goods[goods_weight]', '$goods[market_price]', " .
                        " '$goods[cost_price]', '$shop_price', '$goods[keywords]', '$goods[goods_brief]', '$goods[goods_desc]', '$goods[desc_mobile]', '$goods_thumb', '$goods_img', '$original_img', '" . gmtime() . "', " .
                        " '$goods_number', '$store_best', '$store_new', '$store_hot', '$is_shipping', '$is_on_sale', " .
                        " '$goods[is_real]', '$goods[extension_code]', '$goods[sort_order]', '$new_goods_type', '$goods[is_check]', '$goods[largest_amount]', '$goods[pinyin_keyword]', '$goods[goods_from]','$goods_cause','$freight','$shipping_fee','$tid' ,'$user_cat', '$review_status')";
                    $this->db->query($sql);
                    $goods_id = $this->db->insert_id();
                    //获取本地产品库商品相册
                    $res = $this->db->getAll(" SELECT img_desc, img_url, thumb_url, img_original FROM " . $this->dsc->table('goods_lib_gallery') . " WHERE goods_id = '$lib_goods_id' ");
                } else {
                    $link[] = array('text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=list&' . list_link_postfix());
                    return sys_msg(lang('seller/goods_lib.no_import_goods'), 0, $link);
                }
            }

            //商品属性处理
            $sql = "UPDATE" . $this->dsc->table('goods_attr') . "SET goods_id = '$goods_id' WHERE goods_id = 0 AND admin_id = '$admin_id'";
            $this->db->query($sql);

            $sql = "SELECT goods_attr,product_sn,bar_code,product_number,product_price,product_market_price,product_promote_price,product_warn_number,warehouse_id,area_id,admin_id FROM " .
                $this->dsc->table('products_changelog') . " WHERE 1 AND admin_id = '" . session('seller_id') . "' AND goods_id = 0";
            $products_changelog = $this->db->getAll($sql);

            if (!empty($products_changelog)) {
                foreach ($products_changelog as $k => $v) {
                    if (check_goods_attr_exist($v['goods_attr'], $goods_id, 0, 0)) { //检测货品是否存在
                        continue;
                    }

                    $logs_other = array(
                        'goods_id' => $goods_id,
                        'order_id' => 0,
                        'admin_id' => session('seller_id'),
                        'model_attr' => 0,
                        'add_time' => gmtime()
                    );
                    $table = "products";

                    /* 插入货品表 */
                    $sql = "INSERT INTO " . $GLOBALS['dsc']->table($table) .
                        " (goods_id, goods_attr, product_sn, product_number, product_price, product_market_price, product_promote_price, product_warn_number, bar_code ) VALUES " .
                        " ('" . $goods_id . "', '" . $v['goods_at tr'] . "', '" . $v['product_sn'] . "', '" .
                        $v['product_number'] . "', '" . $v['product_price'] . "', '" . $v['product_market_price'] . "', '" . $v['product_promote_price'] . "', '" . $v['product_warn_number'] . "', '" . $v['bar_code'] . "')";
                    if (!$GLOBALS['db']->query($sql)) {
                        continue;
                    } else {
                        $product_id = $GLOBALS['db']->insert_id();

                        //货品号为空 自动补货品号
                        if (empty($v['product_sn'])) {
                            $sql = "UPDATE " . $GLOBALS['dsc']->table($table) . "
                                SET product_sn = '" . $goods_sn . "g_p" . $product_id . "'
                                WHERE product_id = '$product_id'";
                            $GLOBALS['db']->query($sql);
                        }

                        //库存日志
                        $number = "+ " . $v['product_number'];
                        $logs_other['use_storage'] = 9;
                        $logs_other['product_id'] = $product_id;
                        $logs_other['number'] = $number;
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_inventory_logs'), $logs_other, 'INSERT');
                    }
                }
            }

            //清楚商品零时货品表数据
            $sql = "DELETE FROM" . $this->dsc->table('products_changelog') . " WHERE goods_id = 0 AND admin_id = '$admin_id'";
            $this->db->query($sql);

            //插入商品扩展信息
            $extend_sql = "INSERT INTO " . $this->dsc->table('goods_extend') . "(`goods_id`, `is_reality`, `is_return`, `is_fast`) VALUES ('$goods_id','$is_reality','$is_return','$is_fast')";
            $this->db->query($extend_sql);

            //相册入库
            if ($res) {
                foreach ($res as $k => $v) {
                    $img_url = $this->copy_img($v['img_url'], 'gallery', $goods_id);
                    $thumb_url = $this->copy_img($v['thumb_url'], 'gallery_thumb', $goods_id);
                    $img_original = $this->copy_img($v['img_original'], 'gallery', $goods_id);
                    $sql = " INSERT INTO " . $this->dsc->table('goods_gallery') .
                        " ( goods_id, img_desc, img_url, thumb_url, img_original ) " .
                        " VALUES " .
                        " ( '$goods_id', '$v[img_desc]', '$img_url', '$thumb_url', '$img_original' ) ";
                    $this->db->query($sql);
                }
            }
            if ($standard == 1) {
                $link[] = array('text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=lib_list&standard_goods=1&' . list_link_postfix());
            } else {
                $link[] = array('text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=list&' . list_link_postfix());
            }
            $link[] = array('text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list');
            return sys_msg($GLOBALS['_LANG']['import_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 商品库商品批量导入
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'batch') {
            admin_priv('goods_lib_list');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => '04_goods_lib_list'));
            $standard_goods = !empty($_REQUEST['standard_goods']) ? intval($_REQUEST['standard_goods']) : 0;//1为标准库，0为本地库
            /* 模板赋值 */

            if ($standard_goods == 1) {
                $table = 'wholesale';
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['standard_goods_lib']);
            } else {
                $table = 'goods_lib';
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['20_goods_lib']);
            }

            if ($_POST['checkboxes']) {
                $sql = " SELECT goods_id, goods_name FROM " . $this->dsc->table($table) . " WHERE goods_id " . db_create_in($_POST['checkboxes']);
                $goods_list = $this->db->getAll($sql);
                $this->smarty->assign('goods_list', $goods_list);
            }
            set_default_filter(); //设置默认筛选
            $this->smarty->assign('standard_goods', $standard_goods);
            return $this->smarty->display('goods_lib_batch.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 商家导入商品库商品执行程序
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_import') {
            admin_priv('goods_lib_list');
            $standard_goods = !empty($_REQUEST['standard_goods']) ? intval($_REQUEST['standard_goods']) : 0;//1为标准库，0为本地库

            $error = 0;
            // 循环更新每个商品
            if (!empty($_POST['goods_id'])) {
                //检测填写的订单号是否有重复
                $array = array_values($_POST['goods_sn']);
                foreach ($array as $key => $val) {
                    unset($array[$key]);
                    if (in_array($val, $array)) {
                        return sys_msg(lang('seller/goods_lib.repeat_order_sn'), 1, array(), false);
                    }
                }

                foreach ($_POST['goods_id'] as $goods_id) {
                    $lib_goods_id = isset($goods_id) ? intval($goods_id) : 0;
                    $goods_sn = isset($_POST['goods_sn'][$goods_id]) ? trim($_POST['goods_sn'][$goods_id]) : 0;
                    $goods_number = isset($_POST['goods_number'][$goods_id]) ? intval($_POST['goods_number'][$goods_id]) : 0;
                    $is_shipping = isset($_POST['is_shipping'][$goods_id]) ? intval($_POST['is_shipping'][$goods_id]) : 0; //免运费
                    $is_on_sale = isset($_POST['is_on_sale'][$goods_id]) ? intval($_POST['is_on_sale'][$goods_id]) : 0; //上下架

                    /* 检查货号是否重复 */
                    if ($goods_sn) {
                        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods') .
                            " WHERE goods_sn = '$goods_sn' AND is_delete = 0 AND goods_id <> '$goods_id'";
                        if ($this->db->getOne($sql) > 0) {
                            return sys_msg($GLOBALS['_LANG']['goods_sn_exists'], 1, array(), false);
                        }
                    }

                    /* 如果没有输入商品货号则自动生成一个商品货号 */
                    if (empty($goods_sn)) {
                        $max_id = $goods_id;
                        $goods_sn = $this->goodsManageService->generateGoodSn($max_id);
                    } else {
                        $goods_sn = trim($goods_sn);
                    }

                    $review_status = 1;
                    if ($GLOBALS['_CFG']['review_goods'] == 0) {
                        $review_status = 5;
                    } else {
                        if ($adminru['ru_id'] > 0) {
                            $sql = "SELECT review_goods FROM " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
                            $review_goods = $this->db->getOne($sql, true); //判断
                            if ($review_goods == 0) {
                                $review_status = 5;
                            }
                        } else {
                            $review_status = 5;
                        }
                    }

                    if ($standard_goods == 1) {
                        $sql = " SELECT * FROM " . $this->dsc->table('wholesale') . " WHERE goods_id = '$lib_goods_id' ";
                        $goods = $this->db->getRow($sql);
                        $goods_thumb = $this->copy_img($goods['goods_thumb'], 'goods_thumb', $lib_goods_id);
                        $goods_img = $this->copy_img($goods['goods_img'], 'goods', $lib_goods_id);
                        $original_img = $this->copy_img($goods['original_img'], 'goods', $lib_goods_id);
                        //注释：批发商品建议零售价对应零售商品的市场价和商品价   商品价格对应成本价
                        $sql = "INSERT INTO " . $this->dsc->table('goods') .
                            "(user_id, goods_sn, bar_code, goods_name, goods_weight, market_price," .
                            " cost_price, shop_price, keywords, goods_brief, goods_desc, desc_mobile, goods_thumb, goods_img, original_img, add_time, " .
                            " goods_number, is_shipping, is_on_sale, " .
                            " is_real,  sort_order, last_update,  pinyin_keyword ,warn_number,goods_product_tag,goods_unit,review_status) " .
                            " SELECT '$ru_id','$goods_sn',bar_code,goods_name,goods_weight,retail_price,goods_price" .
                            ",retail_price AS shop_price,keywords,goods_brief,goods_desc,desc_mobile,'$goods_thumb','$goods_img','$original_img','" . gmtime() . "'," .
                            "'$goods_number','$is_shipping', '$is_on_sale',1,sort_order,'" . gmtime() . "'," .
                            "pinyin_keyword,warn_number,goods_product_tag,goods_unit, '$review_status' FROM" . $this->dsc->table('wholesale') . "WHERE goods_id = '$lib_goods_id'";
                        if (!$this->db->query($sql)) {
                            $error += 1;
                        }
                        $goods_id = $this->db->insert_id();
                        //同步扩展信息
                        $sql = "INSERT INTO" . $this->dsc->table('goods_extend') . "(goods_id,width,height,depth,origincountry,originplace,assemblycountry,barcodetype,"
                            . "catena,isbasicunit,packagetype,grossweight,netweight,netcontent,licensenum,healthpermitnum) SELECT '$goods_id',width,height,depth,origincountry,originplace,assemblycountry,barcodetype,"
                            . "catena,isbasicunit,packagetype,grossweight,netweight,netcontent,licensenum,healthpermitnum FROM" . $this->dsc->table('wholesale_extend') . "WHERE goods_id = '$lib_goods_id'";
                        $this->db->query($sql);
                        //获取标准产品库商品相册
                        $res = $this->db->getAll(" SELECT img_desc, img_url, thumb_url, img_original FROM " . $this->dsc->table('suppliers_goods_gallery') . " WHERE goods_id = '$lib_goods_id' ");

                        if ($res) {
                            foreach ($res as $k => $v) {
                                $img_url = $this->copy_img($v['img_url'], 'gallery', $goods_id);
                                $thumb_url = $this->copy_img($v['thumb_url'], 'gallery_thumb', $goods_id);
                                $img_original = $this->copy_img($v['img_original'], 'gallery', $goods_id);

                                $sql = " INSERT INTO " . $this->dsc->table('goods_gallery') .
                                    " ( goods_id, img_desc, img_url, thumb_url, img_original ) " .
                                    " VALUES " .
                                    " ( '$goods_id', '$v[img_desc]', '$img_url', '$thumb_url', '$img_original' ) ";
                                $this->db->query($sql);
                            }
                        }
                    } else {
                        $sql = " SELECT * FROM " . $this->dsc->table('goods_lib') . " WHERE goods_id = '$lib_goods_id' ";
                        $goods = $this->db->getRow($sql);

                        if (!($this->db->getOne(" SELECT goods_id FROM " . $this->dsc->table('goods') . " WHERE goods_id ='" . $goods['lib_goods_id'] . "' AND user_id = '$ru_id' "))) {
                            $goods_thumb = $this->copy_img($goods['goods_thumb'], 'goods_thumb', $lib_goods_id);
                            $goods_img = $this->copy_img($goods['goods_img'], 'goods', $lib_goods_id);
                            $original_img = $this->copy_img($goods['original_img'], 'goods', $lib_goods_id);
                            $sql = "INSERT INTO " . $this->dsc->table('goods') .
                                "(cat_id, user_id, goods_sn, bar_code, goods_name, goods_name_style, brand_id, goods_weight, market_price," .
                                " cost_price, shop_price, keywords, goods_brief, goods_desc, desc_mobile, goods_thumb, goods_img, original_img, add_time, " .
                                " goods_number, is_shipping, is_on_sale, " .
                                " is_real, extension_code, sort_order, goods_type, is_check, largest_amount, pinyin_keyword, review_status) " .
                                " VALUES " .
                                "('$goods[cat_id]', '$ru_id', '$goods_sn', '$goods[bar_code]', '$goods[goods_name]', '$goods[goods_name_style]', '$goods[brand_id]', '$goods[goods_weight]', '$goods[market_price]', " .
                                " '$goods[cost_price]', '$goods[shop_price]', '$goods[keywords]', '$goods[goods_brief]', '$goods[goods_desc]', '$goods[desc_mobile]', '$goods_thumb', '$goods_img', '$original_img', '" . gmtime() . "', " .
                                " '$goods_number', '$is_shipping', '$is_on_sale', " .
                                " '$goods[is_real]', '$goods[extension_code]', '$goods[sort_order]', '$goods[goods_type]', '$goods[is_check]', '$goods[largest_amount]', '$goods[pinyin_keyword]', '$review_status')";
                            if (!$this->db->query($sql)) {
                                $error += 1;
                            }
                            $goods_id = $this->db->insert_id();

                            $res = $this->db->getAll(" SELECT img_desc, img_url, thumb_url, img_original FROM " . $this->dsc->table('goods_lib_gallery') . " WHERE goods_id = '$lib_goods_id' ");
                            if ($res) {
                                foreach ($res as $k => $v) {
                                    $img_url = $this->copy_img($v['img_url'], 'gallery', $goods_id);
                                    $thumb_url = $this->copy_img($v['thumb_url'], 'gallery_thumb', $goods_id);
                                    $img_original = $this->copy_img($v['img_original'], 'gallery', $goods_id);

                                    $sql = " INSERT INTO " . $this->dsc->table('goods_gallery') .
                                        " ( goods_id, img_desc, img_url, thumb_url, img_original ) " .
                                        " VALUES " .
                                        " ( '$goods_id', '$v[img_desc]', '$img_url', '$thumb_url', '$img_original' ) ";
                                    $this->db->query($sql);
                                }
                            }
                        }
                    }
                }
            }
            if ($standard_goods == 1) {
                $link[] = array('text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=lib_list&standard_goods=1&' . list_link_postfix());
            } else {
                $link[] = array('text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=list&' . list_link_postfix());
            }

            $link[] = array('text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list');
            if ($error > 0) {
                return sys_msg($GLOBALS['_LANG']['import_success'] . lang('seller/goods_lib.error_data') . $error . lang('seller/goods_lib.error_strip'), 0, $link);
            } else {
                return sys_msg($GLOBALS['_LANG']['import_success'], 0, $link);
            }
        } elseif ($_REQUEST['act'] == 'set_import_attr') {
            $result = array('error' => 0, 'content' => '');
            $this->smarty->assign('goods_type_list', goods_type_list(0, 0, 'array'));
            $new_goods_type = isset($_REQUEST['new_goods_type']) ? intval($_REQUEST['new_goods_type']) : 0;

            $this->smarty->assign('new_goods_type', $new_goods_type);
            $result['content'] = $GLOBALS['smarty']->fetch('library/set_import_attr.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改货品市场价
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_market_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $market_price = floatval($_POST['val']);
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;

            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;
            if ($changelog == 1) {
                $table = "products_changelog";
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                } else {
                    $table = "products";
                }
            }

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table($table) . " SET product_market_price = '$market_price' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($market_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_POST['id']);
            $product_price = floatval($_POST['val']);
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;
            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;

            if ($changelog == 1) {
                $table = "products_changelog";
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                } else {
                    $table = "products";
                }
            }

            if ($GLOBALS['_CFG']['goods_attr_price'] == 1 && $changelog == 0) {
                $sql = "SELECT goods_id FROM " . $this->dsc->table($table) . " WHERE product_id = '$product_id'";
                $goods_id = $this->db->getOne($sql, true);

                $goods_other = array(
                    'product_table' => $table,
                    'product_price' => $product_price,
                );
                $this->db->autoExecute($this->dsc->table('goods'), $goods_other, 'UPDATE', "goods_id = '$goods_id' AND product_id = '$product_id' AND product_table = '$table'");
            }

            /* 修改货品库存 */
            $sql = "UPDATE " . $this->dsc->table($table) . " SET product_price = '$product_price' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品促销价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_promote_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $promote_price = floatval($_POST['val']);
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;
            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;

            if ($changelog == 1) {
                $table = "products_changelog";
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                } else {
                    $table = "products";
                }
            }

            if ($GLOBALS['_CFG']['goods_attr_price'] == 1 && $changelog == 0) {
                $sql = "SELECT goods_id FROM " . $this->dsc->table($table) . " WHERE product_id = '$product_id'";
                $goods_id = $this->db->getOne($sql, true);

                $goods_other = array(
                    'product_table' => $table,
                    'product_promote_price' => $promote_price,
                );
                $this->db->autoExecute($this->dsc->table('goods'), $goods_other, 'UPDATE', "goods_id = '$goods_id' AND product_id = '$product_id' AND product_table = '$table'");
            }

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table($table) . " SET product_promote_price = '$promote_price' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($promote_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_number') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_POST['id']);
            $product_number = intval($_POST['val']);
            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;
            /* 货品库存 */
            $product = get_product_info($product_id, 'product_number, goods_id');

            if ($product['product_number'] != $product_number && $changelog == 0) {
                if ($product['product_number'] > $product_number) {
                    $number = $product['product_number'] - $product_number;
                    $number = "- " . $number;
                    $log_use_storage = 10;
                } else {
                    $number = $product_number - $product['product_number'];
                    $number = "+ " . $number;
                    $log_use_storage = 11;
                }

                $goods = get_admin_goods_info($product['goods_id']);

                //库存日志
                $logs_other = array(
                    'goods_id' => $product['goods_id'],
                    'order_id' => 0,
                    'use_storage' => $log_use_storage,
                    'admin_id' => session('seller_id'),
                    'number' => $number,
                    'model_inventory' => $goods['model_inventory'],
                    'model_attr' => $goods['model_attr'],
                    'product_id' => $product_id,
                    'warehouse_id' => 0,
                    'area_id' => 0,
                    'add_time' => gmtime()
                );

                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_inventory_logs'), $logs_other, 'INSERT');
            }
            if ($changelog == 1) {
                $table = "products_changelog";
            } else {
                $table = "products";
            }
            /* 修改货品库存 */
            $sql = "UPDATE " . $this->dsc->table($table) . " SET product_number = '$product_number' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品预警库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_warn_number') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_POST['id']);
            $product_warn_number = intval($_POST['val']);
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;
            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;

            if ($changelog == 1) {
                $table = "products_changelog";
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                } else {
                    $table = "products";
                }
            }

            /* 修改货品库存 */
            $sql = "UPDATE " . $this->dsc->table($table) . " SET product_warn_number = '$product_warn_number' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_warn_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品货号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_POST['id']);
            $product_sn = json_str_iconv(trim($_POST['val']));
            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;
            $warehouse_id = isset($_REQUEST['warehouse_id']) && !empty($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;
            $area_id = isset($_REQUEST['area_id']) && !empty($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
            $goods_model = isset($_REQUEST['goods_model']) && !empty($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;
            $area_city = isset($_REQUEST['area_city']) && !empty($_REQUEST['area_city']) ? intval($_REQUEST['area_city']) : 0;

            if (check_product_sn_exist($product_sn, $product_id, $adminru['ru_id'], $goods_model, $warehouse_id, $area_id, $area_city)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_product_sn']);
            }

            if ($changelog == 1) {
                $table = "products_changelog";
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                } else {
                    $table = "products";
                }
            }

            /* 修改货品库存 */
            $sql = "UPDATE " . $this->dsc->table($table) . " SET product_sn = '$product_sn' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_sn);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品条形码
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_bar_code') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $bar_code = json_str_iconv(trim($_POST['val']));
            $bar_code = ($GLOBALS['_LANG']['n_a'] == $bar_code) ? '' : $bar_code;
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;
            $warehouse_id = isset($_REQUEST['warehouse_id']) && !empty($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;
            $area_id = isset($_REQUEST['area_id']) && !empty($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
            $area_city = isset($_REQUEST['area_city']) && !empty($_REQUEST['area_city']) ? intval($_REQUEST['area_city']) : 0;

            if (!empty($bar_code)) {
                if (check_product_bar_code_exist($bar_code, $product_id, $adminru['ru_id'], $goods_model, $warehouse_id, $area_id, $area_city)) {
                    make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_bar_code']);
                }

                $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;

                if ($changelog == 1) {
                    $table = "products_changelog";
                } else {
                    if ($goods_model == 1) {
                        $table = "products_warehouse";
                    } elseif ($goods_model == 2) {
                        $table = "products_area";
                    } else {
                        $table = "products";
                    }
                }

                /* 修改 */
                $sql = "UPDATE " . $this->dsc->table($table) . " SET bar_code = '$bar_code' WHERE product_id = '$product_id'";
                $result = $this->db->query($sql);
                if ($result) {
                    clear_cache_files();
                    return make_json_result($bar_code);
                }
            }
        }
    }

    /*
    * 复制商品图片
    */
    private function copy_img($image = '', $type = 'goods', $goods_id)
    {
        if (stripos($image, "http://") !== false || stripos($image, "https://") !== false) {
            return $image;
        }
        $newname = '';

        $img_ext = substr($image, strrpos($image, '.'));
        $rand_name = gmtime() . sprintf("%03d", mt_rand(1, 999));
        switch ($type) {
            case 'goods':
                $img_name = $goods_id . '_G_' . $rand_name;
                break;
            case 'goods_thumb':
                $img_name = $goods_id . '_thumb_G_' . $rand_name;
                break;
            case 'gallery':
                $img_name = $goods_id . '_P_' . $rand_name;
                break;
            case 'gallery_thumb':
                $img_name = $goods_id . '_thumb_P_' . $rand_name;
                break;
            default:
                $img_name = $rand_name;
                break;
        }
        if ($image) {
            $img = storage_public($image);
            $pos = strpos(basename($img), '.');
            $newname = dirname($img) . '/' . $img_name . $img_ext;

            //开启OSS 则先下载图片 否则拷贝图片
            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $url = $bucket_info['endpoint'] . $image;
                if (!file_exists(dirname($img))) {
                    make_dir(dirname($img));
                }
                get_http_basename($url, $newname, 1);
            } elseif (!@copy($img, $newname)) {
                return;
            }
        }

        $new_name = str_replace(storage_public(), '', $newname);
        $this->dscRepository->getOssAddFile([$new_name]);
        return $new_name;
    }
}
