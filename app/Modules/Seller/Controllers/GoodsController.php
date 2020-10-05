<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Http;
use App\Libraries\Image;
use App\Libraries\Pinyin;
use App\Models\Attribute;
use App\Models\Cart;
use App\Models\DrpConfig;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\GoodsInventoryLogs;
use App\Models\GroupGoods;
use App\Models\MemberPrice;
use App\Models\MerchantsCategory;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsChangelog;
use App\Models\ProductsWarehouse;
use App\Models\RegionWarehouse;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Brand\BrandService;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Common\CommonManageService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsManageService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Store\StoreCommonService;
use Illuminate\Support\Facades\Storage;

/**
 * 商品管理程序
 */
class GoodsController extends InitController
{
    protected $brandService;
    protected $goodsManageService;
    protected $categoryService;
    protected $baseRepository;
    protected $commonRepository;
    protected $commonManageService;
    protected $goodsAttrService;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $dscRepository;
    protected $storeCommonService;
    protected $cartCommonService;

    public function __construct(
        BrandService $brandService,
        GoodsManageService $goodsManageService,
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        CommonManageService $commonManageService,
        GoodsAttrService $goodsAttrService,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService,
        CartCommonService $cartCommonService
    )
    {
        $this->brandService = $brandService;
        $this->goodsManageService = $goodsManageService;
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->commonManageService = $commonManageService;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
        $this->cartCommonService = $cartCommonService;
    }

    public function index()
    {
        load_helper('goods', 'seller');

        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        $exc = new Exchange($this->dsc->table('goods'), $this->db, 'goods_id', 'goods_name');
        $exc_extend = new Exchange($this->dsc->table('goods_extend'), $this->db, 'goods_id', 'extend_id');

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "goods");

        /* 管理员ID */
        $admin_id = get_admin_id();

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        $ru_id = $adminru['ru_id'];
        $this->smarty->assign('review_goods', $GLOBALS['_CFG']['review_goods']);
        //ecmoban模板堂 --zhuo end

        //商品佣金设置权限
        $commission_setting = admin_priv('commission_setting', '', false);
        $this->smarty->assign('commission_setting', $commission_setting);

        /*------------------------------------------------------ */
        //-- 商品列表，商品回收站
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list' || $_REQUEST['act'] == 'trash' || $_REQUEST['act'] == 'no_comment') {
            admin_priv('goods_manage');

            get_del_goodsimg_null();
            get_del_goods_gallery();
            get_updel_goods_attr();
            get_del_goods_video();

            //清楚商品零时货品表数据
            ProductsChangelog::where('goods_id', 0)->where('admin_id', $admin_id)->delete();

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            if ($_REQUEST['act'] == 'list') {
                $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
                //页面分菜单 by wu start
                $tab_menu = [];
                $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list'];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['50_virtual_card_list'], 'href' => 'goods.php?act=list&extension_code=virtual_card'];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['11_goods_trash'], 'href' => 'goods.php?act=trash'];
                //$tab_menu[] = array('curr' => 0, 'text' => '待评价商品', 'href' => 'goods.php?act=no_comment');
                $this->smarty->assign('tab_menu', $tab_menu);
                //页面分菜单 by wu end
            }

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;

            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $suppliers_id = isset($_REQUEST['suppliers_id']) ? (empty($_REQUEST['suppliers_id']) ? '' : trim($_REQUEST['suppliers_id'])) : '';
            $is_on_sale = isset($_REQUEST['is_on_sale']) ? ((empty($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale'] === 0) ? '' : trim($_REQUEST['is_on_sale'])) : '';

            $handler_list = [];
            $handler_list['virtual_card'][] = ['url' => 'virtual_card.php?act=card', 'title' => $GLOBALS['_LANG']['card'], 'icon' => 'icon-credit-card'];
            $handler_list['virtual_card'][] = ['url' => 'virtual_card.php?act=replenish', 'title' => $GLOBALS['_LANG']['replenish'], 'icon' => 'icon-plus-sign'];
            $handler_list['virtual_card'][] = ['url' => 'virtual_card.php?act=batch_card_add', 'title' => $GLOBALS['_LANG']['batch_card_add'], 'icon' => 'icon-plus-sign'];

            if ($_REQUEST['act'] == 'list' && isset($handler_list[$code])) {
                $this->smarty->assign('add_handler', $handler_list[$code]);

                $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
                //页面分菜单 by wu start
                $tab_menu = [];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list'];
                $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['50_virtual_card_list'], 'href' => 'goods.php?act=list&extension_code=virtual_card'];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['11_goods_trash'], 'href' => 'goods.php?act=trash'];
                //$tab_menu[] = array('curr' => 0, 'text' => '待评价商品', 'href' => 'goods.php?act=no_comment');
                $this->smarty->assign('tab_menu', $tab_menu);
                //页面分菜单 by wu end
            }

            if ($_REQUEST['act'] == 'trash') {
                $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
                //页面分菜单 by wu start
                $tab_menu = [];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list'];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['50_virtual_card_list'], 'href' => 'goods.php?act=list&extension_code=virtual_card'];
                $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['11_goods_trash'], 'href' => 'goods.php?act=trash'];
                //$tab_menu[] = array('curr' => 0, 'text' => '待评价商品', 'href' => 'goods.php?act=no_comment');
                $this->smarty->assign('tab_menu', $tab_menu);
                //页面分菜单 by wu end
            }

            if ($_REQUEST['act'] == 'no_comment') {
                $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
                //页面分菜单 by wu start
                $tab_menu = [];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list'];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['50_virtual_card_list'], 'href' => 'goods.php?act=list&extension_code=virtual_card'];
                $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['11_goods_trash'], 'href' => 'goods.php?act=trash'];
                //$tab_menu[] = array('curr' => 1, 'text' => '待评价商品', 'href' => 'goods.php?act=no_comment');
                $this->smarty->assign('tab_menu', $tab_menu);
                //页面分菜单 by wu end
            }

            $this->smarty->assign('is_on_sale', $is_on_sale);
            $this->smarty->assign('suppliers_id', $suppliers_id);

            /* 供货商名 */
            $suppliers_list_name = suppliers_list_name();
            $suppliers_exists = empty($suppliers_list_name) ? 0 : 1;
            $this->smarty->assign('suppliers_exists', $suppliers_exists);
            $this->smarty->assign('suppliers_list_name', $suppliers_list_name);
            unset($suppliers_list_name, $suppliers_exists);

            /* 模板赋值 */
            $goods_ur = ['' => $GLOBALS['_LANG']['01_goods_list'], 'virtual_card' => $GLOBALS['_LANG']['50_virtual_card_list']];
            $ur_here = ($_REQUEST['act'] == 'list') ? $goods_ur[$code] : (($_REQUEST['act'] == 'no_comment') ? $GLOBALS['_LANG']['14_goods_nocom'] : $GLOBALS['_LANG']['11_goods_trash']);
            $this->smarty->assign('ur_here', $ur_here);

            $action_link = ($_REQUEST['act'] == 'list') ? $this->add_link($code) : ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list'], 'class' => 'icon-reply'];
            $this->smarty->assign('action_link', $action_link);

            $this->smarty->assign('code', $code);

            $this->smarty->assign('brand_list', get_brand_list());

            $intro_list = $this->goodsManageService->getIntroList();
            $this->smarty->assign('intro_list', $intro_list);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('list_type', $_REQUEST['act'] == 'list' ? 'goods' : 'trash');
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);

            $suppliers_list = suppliers_list_info(' is_check = 1 ');
            $suppliers_list_count = count($suppliers_list);
            $this->smarty->assign('suppliers_list', ($suppliers_list_count == 0 ? 0 : $suppliers_list)); // 取供货商列表

            $goods_list = goods_list($_REQUEST['act'] == 'list' ? 0 : 1, ($_REQUEST['act'] == 'list') ? (($code == '') ? 1 : 0) : -1);
            $this->smarty->assign('goods_list', $goods_list['goods']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);
            $this->smarty->assign('full_page', 1);

            //待评价商品
            $no_com = $this->get_order_no_comment_goods($ru_id, 0);
            $this->smarty->assign('no_com_goods', $no_com);

            //分页
            $page_count_arr = seller_page($goods_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            /* 排序标记 */
            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 获取商品类型存在规格的类型 */
            $specifications = get_goods_type_specifications();
            $this->smarty->assign('specifications', $specifications);

            $this->smarty->assign('nowTime', gmtime());

            $this->smarty->assign('user_id', $adminru['ru_id']);
            set_default_filter(0, 0, $adminru['ru_id']); //设置默认筛选

            $this->smarty->assign('transport_list', get_table_date("goods_transport", "ru_id='{$adminru['ru_id']}'", ['tid, title'], 1)); //商品运费 by wu

            /* 显示商品列表页面 */

            $htm_file = ($_REQUEST['act'] == 'list') ?
                'goods_list.dwt' : (($_REQUEST['act'] == 'trash') ? 'goods_trash.dwt' : (($_REQUEST['act'] == 'no_comment') ? 'goods_no_comment.dwt' : 'group_list.dwt'));
            return $this->smarty->display($htm_file);
        }

        /*------------------------------------------------------ */
        //-- 添加新商品 编辑商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit' || $_REQUEST['act'] == 'copy') {

            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            get_del_goodsimg_null();
            get_del_goods_gallery();
            get_del_update_goods_null(); //删除商品相关表goods_id值为0的信息
            get_del_goods_video();

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            /* 商家入驻分类 */
            if ($adminru['ru_id']) {
                $seller_shop_cat = seller_shop_cat($adminru['ru_id']);
            } else {
                $seller_shop_cat = [];
            }
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);

            //获取人气组合 by kong
            if ($GLOBALS['_CFG']['group_goods']) {
                $group_goods_arr = explode(',', $GLOBALS['_CFG']['group_goods']);
                $arr = [];
                foreach ($group_goods_arr as $k => $v) {
                    $arr[$k + 1] = $v;
                }
                $this->smarty->assign('group_goods_arr', $arr);
            }

            $is_add = $_REQUEST['act'] == 'add'; // 添加还是编辑的标识
            $is_copy = $_REQUEST['act'] == 'copy'; //是否复制
            $this->smarty->assign('is_copy', $is_copy);

            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $code == 'virtual_card' ? 'virtual_card' : '';

            $properties = empty($_REQUEST['properties']) ? 0 : intval($_REQUEST['properties']);
            $this->smarty->assign('properties', $properties);

            /* 删除未绑定仓库 by kong */
            WarehouseGoods::where('goods_id', 0)->orWhere('goods_id', '')->delete();
            /* 删除未绑定地区 by kong */
            WarehouseAreaGoods::where('goods_id', 0)->orWhere('goods_id', '')->delete();

            if ($code == 'virtual_card') {
                admin_priv('virualcard'); // 检查权限
            } else {
                admin_priv('goods_manage'); // 检查权限
            }

            /* 供货商名 */
            $suppliers_list_name = suppliers_list_name();
            $suppliers_exists = empty($suppliers_list_name) ? 0 : 1;
            $this->smarty->assign('suppliers_exists', $suppliers_exists);
            $this->smarty->assign('suppliers_list_name', $suppliers_list_name);
            unset($suppliers_list_name, $suppliers_exists);

            /* 如果是安全模式，检查目录是否存在 */
            if (ini_get('safe_mode') == 1 && (!file_exists('../' . IMAGE_DIR . '/' . date('Ym')) || !is_dir('../' . IMAGE_DIR . '/' . date('Ym')))) {
                if (@!mkdir('../' . IMAGE_DIR . '/' . date('Ym'), 0777)) {
                    $warning = sprintf($GLOBALS['_LANG']['safe_mode_warning'], '../' . IMAGE_DIR . '/' . date('Ym'));
                    $this->smarty->assign('warning', $warning);
                }
            } /* 如果目录存在但不可写，提示用户 */
            elseif (file_exists('../' . IMAGE_DIR . '/' . date('Ym')) && file_mode_info('../' . IMAGE_DIR . '/' . date('Ym')) < 2) {
                $warning = sprintf($GLOBALS['_LANG']['not_writable_warning'], '../' . IMAGE_DIR . '/' . date('Ym'));
                $this->smarty->assign('warning', $warning);
            }

            $grade_rank = get_seller_grade_rank($adminru['ru_id']);
            $this->smarty->assign('grade_rank', $grade_rank);
            $this->smarty->assign('integral_scale', $GLOBALS['_CFG']['integral_scale']);

            //清楚商品零时货品表数据
            $res = ProductsChangelog::where('goods_id', $goods_id);
            if (empty($goods_id)) {
                $res = $res->where('admin_id', $admin_id);
            }
            $res = $res->delete();

            /* 取得商品信息 */
            if ($is_add) {

                /*退换货标志列表*/
                $res = [];
                $this->smarty->assign('is_cause', $res);

                /*判断商家等级发布商品数量是否达到该等级上限 by kong grade*/
                if ($adminru['ru_id'] > 0) {
                    /*获取商家等级封顶商品数*/
                    if ($grade_rank['goods_sun'] != -1) {
                        /*获取商家商品总数*/
                        $sql = " SELECT COUNT(*) FROM" . $this->dsc->table("goods") . " WHERE user_id = '" . $adminru['ru_id'] . "'";
                        $goods_numer = $this->db->getOne($sql);

                        if ($goods_numer > $grade_rank['goods_sun']) {
                            return sys_msg($GLOBALS['_LANG']['on_goods_num']);
                        }
                    }
                }
                $goods = [
                    'goods_id' => 0,
                    'goods_desc' => '',
                    'goods_shipai' => '',
                    'goods_video' => '',
                    'freight' => 2,
                    'cat_id' => 0,
                    'brand_id' => 0,
                    'is_on_sale' => 1,
                    'is_alone_sale' => 1,
                    'is_show' => 1,
                    'is_shipping' => 0,
                    'other_cat' => [], // 扩展分类
                    'goods_type' => 0,       // 商品类型
                    'shop_price' => 0,
                    'promote_price' => 0,
                    'market_price' => 0,
                    'integral' => 0,
                    'goods_number' => $GLOBALS['_CFG']['default_storage'],
                    'warn_number' => 1,
                    'promote_start_date' => local_date($GLOBALS['_CFG']['time_format']),
                    'promote_end_date' => local_date($GLOBALS['_CFG']['time_format'], local_strtotime('+1 month')),
                    'goods_weight' => 0,
                    'give_integral' => 0,
                    'rank_integral' => 0,
                    'user_cat' => 0,
                    'goods_unit' => '个',
                    'goods_extend' => ['is_reality' => 0, 'is_return' => 0, 'is_fast' => 0]//by wang
                ];

                if ($code != '') {
                    $goods['goods_number'] = 0;
                }

                /* 关联商品 */
                $link_goods_list = [];
                $sql = "DELETE FROM " . $this->dsc->table('link_goods') .
                    " WHERE (goods_id = 0 OR link_goods_id = 0)" .
                    " AND admin_id = '" . session('seller_id') . "'";
                $this->db->query($sql);

                /* 组合商品 */
                $group_goods_list = [];
                $sql = "DELETE FROM " . $this->dsc->table('group_goods') .
                    " WHERE parent_id = 0 AND admin_id = '" . session('seller_id') . "'";
                $this->db->query($sql);

                /* 关联文章 */
                $goods_article_list = [];
                $sql = "DELETE FROM " . $this->dsc->table('goods_article') .
                    " WHERE goods_id = 0 AND admin_id = '" . session('seller_id') . "'";
                $this->db->query($sql);

                /* 属性 */
                $sql = "DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE goods_id = 0 AND admin_id = '$admin_id'";
                $this->db->query($sql);

                /* 图片列表 */
                $img_list = [];
            } else {
                /* 商品信息 */
                $goods = get_admin_goods_info($goods_id);
                if ($goods['user_id'] != $adminru['ru_id']) {
                    $Loaction = "goods.php?act=list";
                    return dsc_header("Location: $Loaction\n");
                }
                // print_arr($goods);die;

                /*退换货标志列表*/
                $cause_list = ['0', '1', '2', '3'];

                /* 判断商品退换货理由 */
                if (!is_null($goods['goods_cause'])) {
                    $res = array_intersect(explode(',', $goods['goods_cause']), $cause_list);
                } else {
                    $res = [];
                }

                if ($res) {
                    $this->smarty->assign('is_cause', $res);
                } else {
                    $res = [];
                    $this->smarty->assign('is_cause', $res);
                }

                //图片显示
                $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);

                /* 虚拟卡商品复制时, 将其库存置为0*/
                if ($is_copy && $code != '') {
                    $goods['goods_number'] = 0;
                }

                if (empty($goods) === true) {
                    /* 默认值 */
                    $goods = [
                        'goods_id' => 0,
                        'goods_desc' => '',
                        'goods_shipai' => '',
                        'goods_video' => '',
                        'cat_id' => 0,
                        'is_on_sale' => 1,
                        'is_alone_sale' => 1,
                        'is_show' => 1,
                        'is_shipping' => 0,
                        'other_cat' => [], // 扩展分类
                        'goods_type' => 0,       // 商品类型
                        'shop_price' => 0,
                        'promote_price' => 0,
                        'market_price' => 0,
                        'integral' => 0,
                        'goods_number' => 1,
                        'warn_number' => 1,
                        'promote_start_date' => local_date($GLOBALS['_CFG']['time_format']),
                        'promote_end_date' => local_date($GLOBALS['_CFG']['time_format'], local_strtotime('+1 month')),
                        'goods_weight' => 0,
                        'give_integral' => 0,
                        'rank_integral' => 0,
                        'user_cat' => 0,
                        'goods_extend' => ['is_reality' => 0, 'is_return' => 0, 'is_fast' => 0]
                    ];
                }

                $goods['goods_video_path'] = !empty($goods['goods_video']) ? get_image_path($goods['goods_video']) : '';
                $goods['goods_extend'] = get_goods_extend($goods['goods_id']);

                /* 获取商品类型存在规格的类型 */
                $specifications = get_goods_type_specifications();
                $goods['specifications_id'] = isset($specifications[$goods['goods_type']]) ? $specifications[$goods['goods_type']] : 0;
                $_attribute = get_goods_specifications_list($goods['goods_id']);
                $goods['_attribute'] = empty($_attribute) ? '' : 1;

                /* 根据商品重量的单位重新计算 */
                if ($goods['goods_weight'] > 0) {
                    $goods['goods_weight_by_unit'] = ($goods['goods_weight'] >= 1) ? $goods['goods_weight'] : ($goods['goods_weight'] / 0.001);
                }

                if (!empty($goods['goods_brief'])) {
                    $goods['goods_brief'] = $goods['goods_brief'];
                }
                if (!empty($goods['keywords'])) {
                    $goods['keywords'] = $goods['keywords'];
                }

                //ecmoban模板堂 --zhuo start 限购
                /* 如果不是限购，处理限购日期 */
                if (isset($goods['is_xiangou']) && $goods['is_xiangou'] == '0') {
                    unset($goods['xiangou_start_date']);
                    unset($goods['xiangou_end_date']);
                } else {
                    $goods['xiangou_start_date'] = local_date('Y-m-d H:i:s', $goods['xiangou_start_date']);
                    $goods['xiangou_end_date'] = local_date('Y-m-d H:i:s', $goods['xiangou_end_date']);
                }
                //ecmoban模板堂 --zhuo end 限购

                //如果不是最小起订量，处理起订量日期
                if (isset($goods['is_minimum']) && $goods['is_minimum'] == '0') {
                    unset($goods['minimum_start_date']);
                    unset($goods['minimum_end_date']);
                } else {
                    $goods['minimum_start_date'] = local_date('Y-m-d H:i:s', $goods['minimum_start_date']);
                    $goods['minimum_end_date'] = local_date('Y-m-d H:i:s', $goods['minimum_end_date']);
                }

                //@author guan 晒单评论 start
                if (!empty($goods['goods_product_tag'])) {
                    $goods['goods_product_tag'] = $goods['goods_product_tag'];
                }
                //@author guan  晒单评论 end

                //商品标签 liu
                if (!empty($goods['goods_tag'])) {
                    $goods['goods_tag'] = $goods['goods_tag'];
                }

                /* 如果不是促销，处理促销日期 */
                if (isset($goods['is_promote']) && $goods['is_promote'] == '0') {
                    unset($goods['promote_start_date']);
                    unset($goods['promote_end_date']);
                } else {
                    $goods['promote_start_date'] = local_date($GLOBALS['_CFG']['time_format'], $goods['promote_start_date']);
                    $goods['promote_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $goods['promote_end_date']);
                }

                //获取拓展分类id数组
                $other_cat_list1 = [];
                $sql = "SELECT ga.cat_id FROM " . $this->dsc->table('goods_cat') . " as ga " .
                    " WHERE ga.goods_id = '" . intval($goods_id) . "'";
                $other_cat1 = $this->db->getCol($sql);

                $other_catids = '';
                if (!empty($other_cat1)) {
                    foreach ($other_cat1 as $key => $val) {
                        $other_catids .= $val . ",";
                    }
                    $other_catids = substr($other_catids, 0, -1);
                }
                $this->smarty->assign('other_catids', $other_catids);

                /* 如果是复制商品，处理 */
                if ($_REQUEST['act'] == 'copy') {

                    /*判断商家等级发布商品数量是否达到该等级上限 by kong grade*/
                    if ($adminru['ru_id'] > 0) {
                        /*获取商家等级封顶商品数*/
                        if ($grade_rank['goods_sun'] != -1) {
                            /*获取商家商品总数*/
                            $sql = " SELECT COUNT(*) FROM" . $this->dsc->table("goods") . " WHERE user_id = '" . $adminru['ru_id'] . "'";
                            $goods_numer = $this->db->getOne($sql);

                            if ($goods_numer > $grade_rank['goods_sun']) {
                                return sys_msg($GLOBALS['_LANG']['on_goods_num']);
                            }
                        }
                    }

                    if ($goods['original_img']) {
                        $originalImg = explode('storage/', $goods['original_img']);
                        $goods['copy_original_img'] = count($originalImg) > 1 ? $originalImg[1] : $goods['original_img'];
                    }

                    if ($goods['goods_img']) {
                        $goodsImg = explode('storage/', $goods['goods_img']);
                        $goods['copy_goods_img'] = count($goodsImg) > 1 ? $goodsImg[1] : $goods['goods_img'];
                    }

                    if ($goods['goods_thumb']) {
                        $goodsThumb = explode('storage/', $goods['goods_thumb']);
                        $goods['copy_goods_thumb'] = count($goodsThumb) > 1 ? $goodsThumb[1] : $goods['goods_thumb'];
                    }

                    // 商品信息
                    $goods['goods_id'] = 0;
                    $goods['goods_sn'] = '';

                    // 扩展分类不变

                    // 关联商品
                    $sql = "DELETE FROM " . $this->dsc->table('link_goods') .
                        " WHERE (goods_id = 0 OR link_goods_id = 0)" .
                        " AND admin_id = '" . session('seller_id') . "'";
                    $this->db->query($sql);

                    $sql = "SELECT '0' AS goods_id, link_goods_id, is_double, '" . session('seller_id') . "' AS admin_id" .
                        " FROM " . $this->dsc->table('link_goods') .
                        " WHERE goods_id = '" . intval($_REQUEST['goods_id']) . "' ";
                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        $this->db->autoExecute($this->dsc->table('link_goods'), $row, 'INSERT');
                    }

                    $sql = "SELECT goods_id, '0' AS link_goods_id, is_double, '" . session('seller_id') . "' AS admin_id" .
                        " FROM " . $this->dsc->table('link_goods') .
                        " WHERE link_goods_id = '" . intval($_REQUEST['goods_id']) . "' ";
                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        $this->db->autoExecute($this->dsc->table('link_goods'), $row, 'INSERT');
                    }

                    // 配件
                    $sql = "DELETE FROM " . $this->dsc->table('group_goods') .
                        " WHERE parent_id = 0 AND admin_id = '" . session('seller_id') . "'";
                    $this->db->query($sql);

                    $sql = "SELECT 0 AS parent_id, goods_id, goods_price, '" . session('seller_id') . "' AS admin_id " .
                        "FROM " . $this->dsc->table('group_goods') .
                        " WHERE parent_id = '" . intval($_REQUEST['goods_id']) . "' ";
                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        $this->db->autoExecute($this->dsc->table('group_goods'), $row, 'INSERT');
                    }

                    // 关联文章
                    $sql = "DELETE FROM " . $this->dsc->table('goods_article') .
                        " WHERE goods_id = 0 AND admin_id = '" . session('seller_id') . "'";
                    $this->db->query($sql);

                    $sql = "SELECT 0 AS goods_id, article_id, '" . session('seller_id') . "' AS admin_id " .
                        "FROM " . $this->dsc->table('goods_article') .
                        " WHERE goods_id = '" . intval($_REQUEST['goods_id']) . "' ";
                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        $this->db->autoExecute($this->dsc->table('goods_article'), $row, 'INSERT');
                    }

                    // 商品属性
                    $sql = "DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE goods_id = 0 AND admin_id = '$admin_id'";
                    $this->db->query($sql);

                    $sql = "SELECT 0 AS goods_id, attr_id, attr_value, attr_price " .
                        "FROM " . $this->dsc->table('goods_attr') .
                        " WHERE goods_id = '" . intval($_REQUEST['goods_id']) . "' ";
                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        $this->db->autoExecute($this->dsc->table('goods_attr'), addslashes_deep($row), 'INSERT');
                    }
                }

                // 扩展分类
                $other_cat_list1 = [];
                $sql = "SELECT ga.cat_id FROM " . $this->dsc->table('goods_cat') . " as ga " .
                    " WHERE ga.goods_id = '" . intval($_REQUEST['goods_id']) . "'";
                $goods['other_cat1'] = $this->db->getCol($sql);

                if (!empty($goods['other_cat1'])) {
                    foreach ($goods['other_cat1'] as $cat_id) {
                        $other_cat_list1[$cat_id] = $this->categoryService->catList($cat_id);
                    }
                }

                $this->smarty->assign('other_cat_list1', $other_cat_list1);

                $link_goods_list = get_linked_goods($goods['goods_id']); // 关联商品
                $group_goods_list = get_group_goods($goods['goods_id']); // 配件
                $goods_article_list = get_goods_articles($goods['goods_id']);   // 关联文章

                /* 商品图片路径 */
                if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 10) && !empty($goods['original_img'])) {
                    $goods['goods_img'] = get_image_path($goods['goods_img']);
                    $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
                }

                /* 图片列表 */
                $sql = "SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id'";
                $img_list = $this->db->getAll($sql);

                /* 格式化相册图片路径 */
                if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                    foreach ($img_list as $key => $gallery_img) {
                        $img_list[$key] = $gallery_img;

                        if (!empty($gallery_img['external_url'])) {
                            $img_list[$key]['img_url'] = $gallery_img['external_url'];
                            $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                        } else {

                            //图片显示
                            $gallery_img['img_original'] = get_image_path($gallery_img['img_original']);

                            $img_list[$key]['img_url'] = $gallery_img['img_original'];

                            $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                        }
                    }
                } else {
                    foreach ($img_list as $key => $gallery_img) {
                        $img_list[$key] = $gallery_img;

                        if (!empty($gallery_img['external_url'])) {
                            $img_list[$key]['img_url'] = $gallery_img['external_url'];
                            $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                        } else {
                            $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                        }
                    }
                }
                $img_desc = [];
                foreach ($img_list as $k => $v) {
                    $img_desc[] = $v['img_desc'];
                }

                @$img_default = min($img_desc);
                $min_img_id = $this->db->getOne(" SELECT img_id   FROM " . $this->dsc->table("goods_gallery") . " WHERE goods_id = '" . $goods_id . "' AND img_desc = '$img_default' ORDER BY img_desc   LIMIT 1");
                $this->smarty->assign('min_img_id', $min_img_id);
            }
            //ecmoban模板堂 --zhuo start
            if (empty($goods['user_id'])) {
                $goods['user_id'] = $adminru['ru_id'];
            }

            $warehouse_list = get_warehouse_region();
            $this->smarty->assign('warehouse_list', $warehouse_list);
            $this->smarty->assign('count_warehouse', count($warehouse_list));

            $warehouse_goods_list = get_warehouse_goods_list($goods_id);
            $this->smarty->assign('warehouse_goods_list', $warehouse_goods_list);

            $warehouse_area_goods_list = get_warehouse_area_goods_list($goods_id);
            $this->smarty->assign('warehouse_area_goods_list', $warehouse_area_goods_list);

            $area_count = get_all_warehouse_area_count();
            $this->smarty->assign('area_count', $area_count);

            $areaRegion_list = $this->commonManageService->getAreaRegionList();
            $this->smarty->assign('areaRegion_list', $areaRegion_list);
            $this->smarty->assign('area_goods_list', get_area_goods($goods_id));

            $consumption_list = $this->cartCommonService->getGoodsConList($goods_id, 'goods_consumption'); //满减订单金额
            $this->smarty->assign('consumption_list', $consumption_list);

            $group_goods = get_cfg_group_goods();
            $this->smarty->assign('group_list', $group_goods);

            $this->smarty->assign('ru_id', $adminru['ru_id']);
            //ecmoban模板堂 --zhuo end


            /* 拆分商品名称样式 */
            $goods_name_style = explode('+', empty($goods['goods_name_style']) ? '+' : $goods['goods_name_style']);

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($goods['goods_desc']) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $goods['goods_desc']);
                $goods['goods_desc'] = $desc_preg['goods_desc'];
            }

            if (isset($goods['desc_mobile']) && !empty($goods['desc_mobile'])) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $goods['desc_mobile']);
                $goods['desc_mobile'] = $desc_preg['goods_desc'];
            }

            /* 创建 html editor */
            create_html_editor('goods_desc', $goods['goods_desc']);
            create_html_editor2('goods_shipai', 'goods_shipai', $goods['goods_shipai']);

            /*  @author-bylu 处理分期数据 start */
            $stages = '';
            if (!empty($goods['stages'])) {
                $stages = unserialize($goods['stages']);
            }
            /*  @author-bylu 处理分期数据 end */

            /* 模板赋值 */
            $this->smarty->assign('code', $code);
            $this->smarty->assign('ur_here', $is_add ? (empty($code) ? $GLOBALS['_LANG']['02_goods_add'] : $GLOBALS['_LANG']['51_virtual_card_add']) : ($_REQUEST['act'] == 'edit' ? $GLOBALS['_LANG']['edit_goods'] : $GLOBALS['_LANG']['copy_goods']));
            $this->smarty->assign('action_link', $this->list_link($is_add, $code));
            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('stages', $stages);//分期期数数据 bylu;
            $this->smarty->assign('goods_name_color', $goods_name_style[0]);
            $this->smarty->assign('goods_name_style', $goods_name_style[1]);

            $cat_list = [];

            if ($seller_shop_cat) {
                if ($is_add) {
                    $cat_list = $this->goodsManageService->catListOne(0, 0, $seller_shop_cat);
                } else {
                    $cat_list = $this->goodsManageService->catListOne($goods['cat_id'], 0, $seller_shop_cat);
                }
            }

            $this->smarty->assign('cat_list', $cat_list);

            $cat_list_new = $this->categoryService->catList($goods['cat_id']);
            $this->smarty->assign('cat_list_new', $cat_list_new);
            $this->smarty->assign('brand_list', get_brand_list($goods_id));

            $brand_info = $this->brandService->getBrandInfo($goods['brand_id']);
            $brand_name = $brand_info['brand_name'] ?? '';
            $this->smarty->assign('brand_name', $brand_name);

            $unit_list = $this->goodsManageService->getUnitList();
            $this->smarty->assign('unit_list', $unit_list);
            $this->smarty->assign('user_rank_list', get_user_rank_list());
            $this->smarty->assign('weight_unit', $is_add ? '1' : ($goods['goods_weight'] >= 1 ? '1' : '0.001'));
            $this->smarty->assign('cfg', $GLOBALS['_CFG']);
            $this->smarty->assign('form_act', $is_add ? 'insert' : ($_REQUEST['act'] == 'edit' ? 'update' : 'insert'));
            if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
                $this->smarty->assign('is_add', true);
            }
            if (!$is_add) {
                // 获取会员价格
                $member_price_list = $this->goodsManageService->get_member_price_list($goods_id);
                $this->smarty->assign('member_price_list', $member_price_list);
            }
            $this->smarty->assign('link_goods_list', $link_goods_list);
            $this->smarty->assign('group_goods_list', $group_goods_list);
            $this->smarty->assign('goods_article_list', $goods_article_list);
            $this->smarty->assign('img_list', $img_list);

            if ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $where = " AND user_id = '" . $adminru['ru_id'] . "' ";
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                $where = " AND user_id = 0 ";
            }

            //获取分类数组
            $type_c_id = $this->db->getOne("SELECT c_id FROM" . $this->dsc->table("goods_type") . "WHERE cat_id = '" . $goods['goods_type'] . "' " . $where . " LIMIT 1");//获取属性分类id
            $type_level = get_type_cat_arr();
            $this->smarty->assign('type_level', $type_level);
            $cat_tree = get_type_cat_arr($type_c_id, 2);
            $cat_tree1 = ['checked_id' => $cat_tree['checked_id']];

            $goods_type_list = goods_type_list($goods['goods_type'], $goods['goods_id'], 'array', $type_c_id);
            $this->smarty->assign('goods_type_list', $goods_type_list);

            if ($cat_tree['checked_id'] > 0) {
                $cat_tree1 = get_type_cat_arr($cat_tree['checked_id'], 2);
            }
            $this->smarty->assign("type_c_id", $type_c_id);
            $this->smarty->assign("cat_tree", $cat_tree);
            $this->smarty->assign("cat_tree1", $cat_tree1);

            $this->smarty->assign('gd', gd_version());
            $this->smarty->assign('thumb_width', $GLOBALS['_CFG']['thumb_width']);
            $this->smarty->assign('thumb_height', $GLOBALS['_CFG']['thumb_height']);
            $this->smarty->assign('goods_attr_html', build_attr_html($goods['goods_type'], $goods['goods_id']));
            $volume_price_list = '';
            if (isset($goods_id)) {
                $volume_price_list = $this->goodsCommonService->getVolumePriceList($goods_id);
            }
            if (empty($volume_price_list)) {
                $volume_price_list = [];
            }
            $this->smarty->assign('volume_price_list', $volume_price_list);

            $cat_info = MerchantsCategory::catInfo($goods['user_cat'])->first();
            $cat_info = $cat_info ? $cat_info->toArray() : [];

            $cat_info['is_show_merchants'] = $cat_info ? $cat_info['is_show'] : 0;

            /* 获取下拉列表 by wu start */
            //设置商品分类
            $level_limit = 3;
            $category_level = [];
            if (!empty($seller_shop_cat)) {
                if ($_REQUEST['act'] == 'add') {
                    for ($i = 1; $i <= $level_limit; $i++) {
                        $category_list = [];
                        if ($i == 1) {
                            $category_list = get_category_list(0, 0, $seller_shop_cat, $goods['user_id']);
                        }
                        $this->smarty->assign('cat_level', $i);
                        $this->smarty->assign('category_list', $category_list);
                        $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
                    }
                }
                if ($_REQUEST['act'] == 'edit' || $_REQUEST['act'] == 'copy') {
                    $parent_cat_list = get_select_category($goods['cat_id'], 1, true);

                    for ($i = 1; $i <= $level_limit; $i++) {
                        $category_list = [];
                        if (isset($parent_cat_list[$i])) {
                            $category_list = get_category_list($parent_cat_list[$i], 0, $seller_shop_cat, $goods['user_id'], $i);
                        } elseif ($i == 1) {
                            if ($goods['user_id']) {
                                $category_list = get_category_list(0, 0, $seller_shop_cat, $goods['user_id'], $i);
                            } else {
                                $category_list = get_category_list(0, 0, $seller_shop_cat, $adminru['ru_id']);
                            }
                        }
                        $this->smarty->assign('cat_level', $i);
                        $this->smarty->assign('category_list', $category_list);
                        $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
                    }
                }
            }

            $this->smarty->assign('category_level', $category_level);
            /* 获取下拉列表 by wu end */

            set_default_filter(0, 0, $adminru['ru_id']); //by wu
            set_seller_default_filter(0, $goods['user_cat'], $adminru['ru_id']); //by wu
            $user_cat_name = get_seller_every_category($goods['user_cat']);
            $this->smarty->assign('user_cat_name', $user_cat_name);

            if (file_exists(MOBILE_DRP)) {
                //判断是否分销商家
                $is_dis = is_distribution($adminru['ru_id']);
                $this->smarty->assign('is_dis', $is_dis);
                // 是否有分销模块
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            $this->smarty->assign('transport_list', get_table_date("goods_transport", "ru_id = '" . $goods['user_id'] . "'", ['tid, title'], 1)); //商品运费 by wu

            $this->smarty->assign('seller_stages', $GLOBALS['_CFG']['seller_stages']); //商家设置商品分期

            if (CROSS_BORDER === true) {
                // 跨境多商户
                $seller = app(CrossBorderService::class)->sellerExists();

                if (!empty($seller)) {
                    $seller->smartyAssign();
                }
            }
            /* 显示商品信息页面 */
            return $this->smarty->display('goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 获取分类列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_select_category_pro') {
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $cat_level = empty($_REQUEST['cat_level']) ? 0 : intval($_REQUEST['cat_level']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods = get_admin_goods_info($goods_id);
            $goods['user_id'] = !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];
            $seller_shop_cat = seller_shop_cat($goods['user_id']);

            $category_list = empty($seller_shop_cat) ? [] : get_category_list($cat_id, 2, $seller_shop_cat, $goods['user_id'], $cat_level + 1);

            $this->smarty->assign('cat_id', $cat_id);
            $this->smarty->assign('cat_level', $cat_level + 1);
            $this->smarty->assign('category_list', $category_list);
            $result['content'] = $this->smarty->fetch('library/get_select_category.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 设置常用分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_common_category_pro') {
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $level_limit = 3;
            $category_level = [];
            $parent_cat_list = get_select_category($cat_id, 1, true);

            for ($i = 1; $i <= $level_limit; $i++) {
                $category_list = [];
                if (isset($parent_cat_list[$i])) {
                    $category_list = get_category_list($parent_cat_list[$i]);
                } elseif ($i == 1) {
                    $category_list = get_category_list();
                }
                $this->smarty->assign('cat_level', $i);
                $this->smarty->assign('category_list', $category_list);
                $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
            }

            $this->smarty->assign('cat_id', $cat_id);
            $result['content'] = $category_level;
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 处理扩展分类删除或者添加
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'deal_extension_category') {
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $other_catids = empty($_REQUEST['other_catids']) ? '' : trim($_REQUEST['other_catids']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($type == "add") {
                // 插入记录
                $data = [
                    'goods_id' => $goods_id,
                    'cat_id' => $cat_id
                ];
                GoodsCat::insert($data);
                if ($other_catids == '') {
                    $other_catids = $cat_id;
                } else {
                    $other_catids = $other_catids . "," . $cat_id;
                }
            } elseif ($type == "delete") {
                GoodsCat::where('goods_id', $goods_id)->where('cat_id', $cat_id)->delete();
                $other_catids = str_replace(',' . $cat_id, '', $other_catids);
            }
            $result['content'] = $other_catids;
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取商品模式列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_model_list') {
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
            $model = empty($_REQUEST['model']) ? 0 : intval($_REQUEST['model']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($model == 1) {
                $warehouse_goods_list = get_warehouse_goods_list($goods_id);
                $this->smarty->assign('warehouse_goods_list', $warehouse_goods_list);
            } elseif ($model == 2) {
                $warehouse_area_goods_list = get_warehouse_area_goods_list($goods_id);
                $this->smarty->assign('warehouse_area_goods_list', $warehouse_area_goods_list);
            }

            $this->smarty->assign('area_pricetype', intval($GLOBALS['_CFG']['area_pricetype']));
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('model', $model);

            $result['content'] = $this->smarty->fetch('library/goods_model_list.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 切换商品类型
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_attribute') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_type = empty($_REQUEST['goods_type']) ? 0 : intval($_REQUEST['goods_type']);
            $model = !isset($_REQUEST['modelAttr']) ? -1 : intval($_REQUEST['modelAttr']);
            $warehouse_id = isset($_REQUEST['warehouse_id']) && !empty($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;
            $area_id = isset($_REQUEST['area_id']) && !empty($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
            $area_city = isset($_REQUEST['area_city']) && !empty($_REQUEST['area_city']) ? intval($_REQUEST['area_city']) : 0;

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $attribute = set_goods_attribute($goods_type, $goods_id, $model);

            $result['goods_attribute'] = $attribute['goods_attribute'];
            $result['goods_attr_gallery'] = $attribute['goods_attr_gallery'];
            $result['model'] = $model;
            $result['goods_id'] = $goods_id;
            $result['is_spec'] = $attribute['is_spec'];

            $result['region'] = [
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'city_id' => $area_city
            ];

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 设置属性表格
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_attribute_table' || $_REQUEST['act'] == 'goods_attribute_query') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_type = empty($_REQUEST['goods_type']) ? 0 : intval($_REQUEST['goods_type']);
            $attr_id_arr = empty($_REQUEST['attr_id']) ? [] : explode(',', $_REQUEST['attr_id']);
            $attr_value_arr = empty($_REQUEST['attr_value']) ? [] : explode(',', $_REQUEST['attr_value']);
            $goods_model = empty($_REQUEST['goods_model']) ? 0 : intval($_REQUEST['goods_model']); //商品模式
            $warehouse_id = empty($_REQUEST['warehouse_id']) ? 0 : intval($_REQUEST['warehouse_id']); //仓库id
            $region_id = empty($_REQUEST['region_id']) ? 0 : intval($_REQUEST['region_id']); //地区id
            $city_id = isset($_REQUEST['city_id']) && !empty($_REQUEST['city_id']) ? intval($_REQUEST['city_id']) : 0; //地区id
            $search_attr = !empty($_REQUEST['search_attr']) ? trim($_REQUEST['search_attr']) : '';

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            /* ajax分页 start */
            $filter['goods_id'] = $goods_id;
            $filter['goods_type'] = $goods_type;
            $filter['attr_id'] = $_REQUEST['attr_id'];
            $filter['attr_value'] = $_REQUEST['attr_value'];
            $filter['goods_model'] = $goods_model;
            $filter['search_attr'] = $search_attr;
            $filter['warehouse_id'] = $warehouse_id;
            $filter['region_id'] = $region_id;
            $filter['city_id'] = $city_id;

            /* ajax分页 end */
            if ($search_attr) {
                $search_attr = explode(',', $search_attr);
            } else {
                $search_attr = [];
            }

            $group_attr = [
                'goods_id' => $goods_id,
                'goods_type' => $goods_type,
                'attr_id' => empty($attr_id_arr) ? '' : implode(',', $attr_id_arr),
                'attr_value' => empty($attr_value_arr) ? '' : implode(',', $attr_value_arr),
                'goods_model' => $goods_model,
                'region_id' => $region_id,
                'city_id' => $city_id
            ];

            $result['group_attr'] = json_encode($group_attr, JSON_UNESCAPED_UNICODE);

            //商品模式
            if ($goods_model == 0) {
                $model_name = "";
            } elseif ($goods_model == 1) {
                $model_name = $GLOBALS['_LANG']['warehouse'];
            } elseif ($goods_model == 2) {
                $model_name = $GLOBALS['_LANG']['area'];
            }

            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $region_name = RegionWarehouse::where('region_id', $city_id)->value('region_name');
            } else {
                $region_name = RegionWarehouse::where('region_id', $region_id)->value('region_name');
            }
            $region_name = $region_name ? $region_name : '';
            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('goods_model', $goods_model);
            $this->smarty->assign('model_name', $model_name);

            //商品基本信息
            $res = Goods::select('market_price', 'shop_price', 'model_attr')->where('goods_id', $goods_id);
            $goods_info = $this->baseRepository->getToArrayFirst($res);
            $this->smarty->assign('goods_info', $goods_info);

            //将属性归类
            $attr_arr = [];
            if ($attr_id_arr) {
                foreach ($attr_id_arr as $key => $val) {
                    $attr_arr[$val][] = $attr_value_arr[$key];
                }
            }

            $attr_spec = [];
            $attribute_array = [];

            if (count($attr_arr) > 0) {
                //属性数据
                $i = 0;
                foreach ($attr_arr as $key => $val) {
                    $res = Attribute::select('attr_name', 'attr_type')->where('attr_id', $key);
                    $attr_info = $this->baseRepository->getToArrayFirst($res);

                    $attribute_array[$i]['attr_id'] = $key;
                    $attribute_array[$i]['attr_name'] = $attr_info['attr_name'];
                    $attribute_array[$i]['attr_value'] = $val;
                    /* 处理属性图片 start */
                    $attr_values_arr = [];
                    foreach ($val as $k => $v) {

                        $v = trim($v);

                        $where_select = [
                            'attr_id' => $key,
                            'attr_value' => $v,
                            'goods_id' => $goods_id
                        ];
                        $data = $this->goodsAttrService->getGoodsAttrId($where_select, [1, 2], 1);

                        if (!$data) {
                            $sql = "SELECT MAX(goods_attr_id) AS goods_attr_id FROM " . $this->dsc->table('goods_attr') . " WHERE 1 ";
                            $max_goods_attr_id = $this->db->getOne($sql);
                            $attr_sort = $max_goods_attr_id + 1;

                            $sql = " INSERT INTO " . $this->dsc->table('goods_attr') . " (goods_id, attr_id, attr_value, attr_sort, admin_id) " .
                                " VALUES " .
                                " ('$goods_id', '$key', '$v', '$attr_sort', '" . session('seller_id') . "') ";
                            $this->db->query($sql);
                            $data['goods_attr_id'] = $this->db->insert_id();
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

                //删除复选属性后重设键名
                $new_attribute_array = [];
                foreach ($attribute_array as $key => $val) {
                    $new_attribute_array[] = $val;
                }
                $attribute_array = $new_attribute_array;

                //删除复选属性
                $attr_arr = get_goods_unset_attr($goods_id, $attr_arr);

                //将属性组合
                if (count($attr_arr) == 1) {
                    foreach (reset($attr_arr) as $key => $val) {
                        $attr_group[][] = $val;
                    }
                } else {
                    $attr_group = attr_group($attr_arr);
                }
                delete_invalid_goods_attr($attr_group, $goods_id, $goods_model, $region_id, $city_id);//去除无效的属性  值针对属性零时表
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
                $filter['page'] = $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
                $filter['page_size'] = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 15;
                $products_list = $this->dsc->page_array($filter['page_size'], $filter['page'], $attr_group, 0, $filter);

                $filter = $products_list['filter'] ?? [];
                $attr_group = $products_list['list'] ?? [];
                /* ajax分页 end */

                //取得组合补充数据
                foreach ($attr_group as $key => $val) {
                    $group = [];

                    //组合信息
                    $attr_info = [];
                    foreach ($val as $k => $v) {
                        if ($v) {
                            $attr_info[$k]['attr_id'] = $attribute_array[$k]['attr_id'];

                            $where_select = [
                                'goods_id' => $goods_id,
                                'attr_id' => $attribute_array[$k]['attr_id'],
                                'attr_value' => $v,
                            ];

                            if (empty($goods_id)) {
                                $admin_id = get_admin_id();
                                $where_select['admin_id'] = $admin_id;
                            }

                            $goods_attr_info = $this->goodsAttrService->getGoodsAttrId($where_select, 1, 1);
                            $goods_attr_id = $goods_attr_info['goods_attr_id'] ?? 0;

                            $attr_info[$k]['goods_attr_id'] = $goods_attr_id;
                            $attr_info[$k]['attr_value'] = $v;
                        }
                    }

                    //货品信息
                    $product_info = get_product_info_by_attr($goods_id, $attr_info, $goods_model, $region_id, 0, $city_id);
                    if (!empty($product_info)) {
                        $group = $product_info;
                        $group['changelog'] = 0;
                    } else {
                        $product_info = get_product_info_by_attr($goods_id, $attr_info, $goods_model, $region_id, 1, $city_id); //获取属性表零时数据
                        if ($product_info) {
                            $group = $product_info;
                        } else {
                            $group = insert_attr_changelog($goods_id, $attr_info, $goods_model, $warehouse_id, $region_id, $city_id);//录入新的零时表数据，且取出
                        }
                        $group['changelog'] = 1;
                    }
                    $group['attr_info'] = $attr_info;

                    if ($group) {
                        $attr_group[$key] = $group;
                    } else {
                        $attr_group = [];
                    }
                }

                $this->smarty->assign('attr_group', $attr_group);
                $this->smarty->assign('attribute_array', $attribute_array);

                /* ajax分页 start */
                $this->smarty->assign('filter', $filter);

                $filter['page'] = $filter['page'] ?? 1;

                $page_count_arr = seller_page($products_list, $filter['page']);
                $this->smarty->assign('page_count_arr', $page_count_arr);
                if ($_REQUEST['act'] == 'set_attribute_table') {
                    $this->smarty->assign('full_page', 1);
                } else {
                    $this->smarty->assign('group_attr', $result['group_attr']);
                    $this->smarty->assign('add_shop_price', $GLOBALS['_CFG']['add_shop_price']);
                    $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);
                    return make_json_result($this->smarty->fetch('library/goods_attribute_query.lbi'), '', ['filter' => $products_list['filter'], 'page_count' => $products_list['page_count']]);
                }
                /* ajax分页 end */
            }

            $this->smarty->assign('group_attr', $result['group_attr']);
            $this->smarty->assign('add_shop_price', $GLOBALS['_CFG']['add_shop_price']);
            $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

            $GLOBALS['smarty']->assign('goods_id', $goods_id);
            $GLOBALS['smarty']->assign('goods_type', $goods_type);

            $result['content'] = $this->smarty->fetch('library/attribute_table.lbi');

            /* 处理属性图片 start */
            $this->smarty->assign('attr_spec', $attr_spec);
            $result['goods_attr_gallery'] = $this->smarty->fetch('library/goods_attr_gallery.lbi');
            /* 处理属性图片 end */

            $result['region'] = [
                'warehouse_id' => $warehouse_id,
                'area_id' => $region_id,
                'city_id' => $city_id
            ];

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 插入关联商品描述，多商品相同描述内容
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_desc') {
            admin_priv('goods_manage');

            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['same_goods_desc']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $action_link = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list'], 'class' => 'icon-reply'];
            $this->smarty->assign('action_link', $action_link);

            $sql = "DELETE FROM " . $this->dsc->table('link_desc_temporary') . " WHERE ru_id = '" . $adminru['ru_id'] . "'";
            $this->db->query($sql);

            //创建编辑器
            create_html_editor2('goods_desc', 'goods_desc', '');

            $desc_list = get_link_goods_desc_list($adminru['ru_id']);

            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('form_act', 'insert_link_desc');

            $this->smarty->assign('desc_list', $desc_list['desc_list']);
            $this->smarty->assign('filter', $desc_list['filter']);
            $this->smarty->assign('record_count', $desc_list['record_count']);
            $this->smarty->assign('page_count', $desc_list['page_count']);
            $this->smarty->assign('full_page', 1);

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['lab_add_desc'], 'href' => 'goods.php?act=add_desc', 'ext' => 'data-tab="linkgoods"'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['lab_desc_list'], 'href' => 'goods.php?act=desc_list', 'ext' => 'data-tab="linklist"'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end
            set_default_filter(0, 0, $adminru['ru_id']); //设置默认筛选

            /* 显示商品信息页面 */

            return $this->smarty->display('goods_desc.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插�        ��        �联商品描述，多商品�        �同描述内        容 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'desc_list') {
            admin_priv('goods_manage');

            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['same_goods_desc']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $action_link = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list'], 'class' => 'icon-reply'];
            $this->smarty->assign('action_link', $action_link);

            $desc_list = get_link_goods_desc_list($adminru['ru_id']);
            $this->smarty->assign('desc_list', $desc_list['desc_list']);
            $this->smarty->assign('filter', $desc_list['filter']);
            $this->smarty->assign('record_count', $desc_list['record_count']);
            $this->smarty->assign('page_count', $desc_list['page_count']);
            $this->smarty->assign('full_page', 1);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($desc_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['lab_add_desc'], 'href' => 'goods.php?act=add_desc', 'ext' => 'data-tab="linkgoods"'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['lab_desc_list'], 'href' => 'goods.php?act=desc_list', 'ext' => 'data-tab="linklist"'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            /* 显示商品信息页面 */

            return $this->smarty->display('goods_desc_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'desc_query') {
            $desc_list = get_link_goods_desc_list($adminru['ru_id']);
            $this->smarty->assign('desc_list', $desc_list['desc_list']);
            $this->smarty->assign('filter', $desc_list['filter']);
            $this->smarty->assign('record_count', $desc_list['record_count']);
            $this->smarty->assign('page_count', $desc_list['page_count']);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($desc_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return make_json_result(
                $this->smarty->fetch('goods_desc_list.dwt'),
                '',
                ['filter' => $desc_list['filter'], 'page_count' => $desc_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 插入关联商品描述数据，多商品同描述内容
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_link_desc') {
            admin_priv('goods_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['intro_edit']);

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('link_desc_temporary') . " WHERE ru_id = '" . $adminru['ru_id'] . "'";
            $this->db->query($sql);

            $action_link = ['href' => 'goods.php?act=add_desc', 'text' => $GLOBALS['_LANG']['go_back'], 'class' => 'icon-reply'];
            $this->smarty->assign('action_link', $action_link);

            $other = ['*'];
            $goods_desc = get_table_date('link_goods_desc', "id = '$id'", $other);

            $link_goods_list = get_linked_goods_desc($id);

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($goods_desc['goods_desc']) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $goods_desc['goods_desc']);
                $goods_desc['goods_desc'] = $desc_preg['goods_desc'];
            }

            //创建编辑器
            create_html_editor2('goods_desc', 'goods_desc', $goods_desc['goods_desc']);

            $this->smarty->assign('goods', $goods_desc);
            $this->smarty->assign('link_goods_list', $link_goods_list);

            $seller_shop_cat = seller_shop_cat($adminru['ru_id']);

            $cat_list = empty($seller_shop_cat) ? [] : $this->goodsManageService->catListOne(0, 0, $seller_shop_cat);
            $this->smarty->assign('cat_list', $cat_list);

            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('form_act', 'update_link_desc');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['lab_add_desc'], 'href' => 'goods.php?act=add_desc', 'ext' => 'data-tab="linkgoods"'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['lab_desc_list'], 'href' => 'goods.php?act=desc_list', 'ext' => 'data-tab="linklist"'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            /* 显示商品信息页面 */

            return $this->smarty->display('goods_desc.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插�        ��        �联商品描述，多商品�        �同描述内        容 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_link_desc') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $linked_array = dsc_decode($_GET['add_ids'], true);
            $linked_goods = dsc_decode($_GET['JSON'], true);
            $id = $linked_goods[0];

            get_add_edit_link_desc($linked_array, 0, $id);
            $linked_goods = get_linked_goods_desc();

            $options = [];
            foreach ($linked_goods as $val) {
                $options[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($options);
        }

        /*------------------------------------------------------ */
        //-- 删除�        �联商品描述，多商品�        �同描述内        容 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_link_desc') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = dsc_decode($_GET['drop_ids'], true);
            $linked_goods = dsc_decode($_GET['JSON'], true);
            $id = $linked_goods[0];

            get_add_edit_link_desc($drop_goods, 1, $id);
            $linked_goods = get_linked_goods_desc();

            $options = [];
            foreach ($linked_goods as $val) {
                $options[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            if (empty($linked_goods)) {
                $sql = "DELETE FROM " . $this->dsc->table('link_desc_temporary') . " WHERE ru_id = '" . $adminru['ru_id'] . "'";
                $this->db->query($sql);
            }

            clear_cache_files();
            return make_json_result($options);
        }

        /*------------------------------------------------------ */
        //-- 插�        ��        �联商品描述数据，多商品�        �同描述内        容 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_link_desc' || $_REQUEST['act'] == 'update_link_desc') {
            $desc_name = !empty($_REQUEST['desc_name']) ? trim($_REQUEST['desc_name']) : '';
            $goods_desc = !empty($_REQUEST['goods_desc']) ? $_REQUEST['goods_desc'] : '';
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $review_status = isset($_REQUEST['review_status']) ? intval($_REQUEST['review_status']) : 1;
            $review_content = !empty($_REQUEST['review_content']) ? trim($_REQUEST['review_content']) : '';

            $sql = "SELECT goods_id FROM " . $this->dsc->table('link_desc_temporary') . " WHERE 1 AND ru_id = '" . $adminru['ru_id'] . "'";
            $goods_id = $this->db->getOne($sql, true);

            $other = [
                'ru_id' => $adminru['ru_id'],
                'desc_name' => $desc_name,
                'goods_desc' => $goods_desc
            ];

            if ($goods_id) {
                $other['goods_id'] = $goods_id;
            }

            $goods_other = ['review_status'];
            $goods_desc = get_table_date('link_goods_desc', "id = '$id'", $goods_other);

            if (!empty($goods_desc) && $goods_desc['review_status'] == 3) {
                $goods_desc['review_status'] = 1;
            } else {
                $goods_desc['review_status'] = $review_status;
            }

            $other['review_status'] = $goods_desc['review_status'];

            if (!empty($desc_name)) {
                $sql = "DELETE FROM " . $this->dsc->table('link_desc_goodsid') . " WHERE d_id = '$id'";
                $this->db->query($sql);

                if ($id > 0) {
                    $this->db->autoExecute($this->dsc->table('link_goods_desc'), $other, "UPDATE", "id = '$id'");
                    $link_cnt = $GLOBALS['_LANG']['edit_success'];
                } else {
                    $this->db->autoExecute($this->dsc->table('link_goods_desc'), $other, "INSERT");
                    $id = $this->db->insert_id();
                    $link_cnt = $GLOBALS['_LANG']['add_success'];
                }
            } else {
                $link_cnt = $GLOBALS['_LANG']['intro_name_not_null'];
            }

            if (!empty($goods_id)) {
                get_add_desc_goodsId($goods_id, $id);
            }

            if ($id > 0) {
                $link[0] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => "goods.php?act=edit_link_desc&id=" . $id];
            }

            $link[1] = ['text' => $GLOBALS['_LANG']['add_related_goods_intro'], 'href' => "goods.php?act=add_desc"];
            $link[2] = ['text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list'];
            return sys_msg($link_cnt, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 插�        ��        �联商品描述数据，多商品�        �同描述内        容 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_link_desc') {
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('link_goods_desc') . " WHERE id = '$id'";
            $this->db->query($sql);

            $sql = "DELETE FROM " . $this->dsc->table('link_desc_goodsid') . " WHERE d_id = '$id'";
            $this->db->query($sql);

            $link[0] = ['text' => $GLOBALS['_LANG']['lab_add_desc'], 'href' => "goods.php?act=add_desc"];
            $link[1] = ['text' => $GLOBALS['_LANG']['lab_desc_list'], 'href' => "goods.php?act=desc_list"];
            $link[2] = ['text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['lab_dellink_desc'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 插�        �商品 更新商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $_POST['goods_sn'] = isset($_POST['goods_sn']) && !empty($_POST['goods_sn']) ? addslashes(trim($_POST['goods_sn'])) : '';

            /* 是否处理缩略图 */
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
            if ($code == 'virtual_card') {
                admin_priv('virualcard'); // 检查权限
            } else {
                admin_priv('goods_manage'); // 检查权限
            }

            /* 检查货号是否重复 */
            if ($_POST['goods_sn']) {
                $goods_id = intval($_POST['goods_id']);
                if ($goods_id) {
                    $goods = get_admin_goods_info($goods_id);
                    $seller_id = $goods['user_id'];
                } else {
                    $seller_id = $adminru['ru_id'];
                }

                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods') .
                    " WHERE goods_sn = '$_POST[goods_sn]' AND is_delete = 0 AND goods_id <> '$goods_id' AND user_id = '$seller_id'";
                if ($this->db->getOne($sql) > 0) {
                    return sys_msg($GLOBALS['_LANG']['goods_sn_exists'], 1, [], false);
                }
            }

            /* 插入还是更新的标识 */
            $is_insert = $_REQUEST['act'] == 'insert';

            $original_img = empty($_REQUEST['original_img']) ? '' : trim($_REQUEST['original_img']);
            $goods_img = empty($_REQUEST['goods_img']) ? '' : trim($_REQUEST['goods_img']);
            $goods_thumb = empty($_REQUEST['goods_thumb']) ? '' : trim($_REQUEST['goods_thumb']);

            /* 处理商品图片 */
            $is_img_url = empty($_REQUEST['is_img_url']) ? 0 : intval($_REQUEST['is_img_url']);
            $_POST['goods_img_url'] = isset($_POST['goods_img_url']) && !empty($_POST['goods_img_url']) ? trim($_POST['goods_img_url']) : '';

            // 如果上传了商品图片，相应处理
            if (!empty($_POST['goods_img_url']) && ($_POST['goods_img_url'] != 'http://') && (strpos($_POST['goods_img_url'], 'http://') !== false || strpos($_POST['goods_img_url'], 'https://') !== false) && $is_img_url == 1) {
                $admin_temp_dir = "seller";
                $admin_temp_dir = storage_public("temp" . '/' . $admin_temp_dir . '/' . "admin_" . $admin_id);

                if (!file_exists($admin_temp_dir)) {
                    make_dir($admin_temp_dir);
                }
                if (get_http_basename($_POST['goods_img_url'], $admin_temp_dir)) {
                    $original_img = $admin_temp_dir . "/" . basename($_POST['goods_img_url']);
                }
                if ($original_img === false) {
                    return sys_msg($image->error_msg(), 1, [], false);
                }

                $goods_img = $original_img;   // 商品图片

                /* 复制一份相册图片 */
                /* 添加判断是否自动生成相册图片 */
                if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                    $img = $original_img;   // 相册图片
                    $pos = strpos(basename($img), '.');
                    $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                    if (!copy($img, $newname)) {
                        return sys_msg('fail to copy file: ' . realpath('../' . $img), 1, [], false);
                    }
                    $img = $newname;

                    $gallery_img = $img;
                    $gallery_thumb = $img;
                }

                // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
                if ($proc_thumb && $image->gd_version() > 0 || $is_url_goods_img) {
                    if (empty($is_url_goods_img)) {
                        $img_wh = $image->get_width_to_height($goods_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                        $GLOBALS['_CFG']['image_width'] = isset($img_wh['image_width']) ? $img_wh['image_width'] : $GLOBALS['_CFG']['image_width'];
                        $GLOBALS['_CFG']['image_height'] = isset($img_wh['image_height']) ? $img_wh['image_height'] : $GLOBALS['_CFG']['image_height'];

                        // 如果设置大小不为0，缩放图片
                        $goods_img = $image->make_thumb(['img' => $goods_img, 'type' => 1], $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                        if ($goods_img === false) {
                            return sys_msg($image->error_msg(), 1, [], false);
                        }

                        $gallery_img = $image->make_thumb(['img' => $gallery_img, 'type' => 1], $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);

                        if ($gallery_img === false) {
                            return sys_msg($image->error_msg(), 1, [], false);
                        }

                        // 加水印
                        if (intval($GLOBALS['_CFG']['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                            if ($image->add_watermark($goods_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                return sys_msg($image->error_msg(), 1, [], false);
                            }
                            /* 添加判断是否自动生成相册图片 */
                            if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                if ($image->add_watermark($gallery_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                    return sys_msg($image->error_msg(), 1, [], false);
                                }
                            }
                        }
                    }

                    // 相册缩略图
                    /* 添加判断是否自动生成相册图片 */
                    if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                        if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                            $gallery_thumb = $image->make_thumb(['img' => $img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            if ($gallery_thumb === false) {
                                return sys_msg($image->error_msg(), 1, [], false);
                            }
                        }
                    }
                }

                // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
                if ($proc_thumb && !empty($original_img)) {
                    // 如果设置缩略图大小不为0，生成缩略图
                    if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                        $goods_thumb = $image->make_thumb(['img' => $original_img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                        if ($goods_thumb === false) {
                            return sys_msg($image->error_msg(), 1, [], false);
                        }
                    } else {
                        $goods_thumb = $original_img;
                    }
                }
            }
            /* 商品外链图 end */

            /* 如果没有输入商品货号则自动生成一个商品货号 */
            if (empty($_POST['goods_sn'])) {
                $max_id = $is_insert ? $this->db->getOne("SELECT MAX(goods_id) + 1 FROM " . $this->dsc->table('goods')) : $_REQUEST['goods_id'];
                $goods_sn = $this->goodsManageService->generateGoodSn($max_id);
            } else {
                $goods_sn = trim($_POST['goods_sn']);
            }

            /* 处理商品数据 */
            $shop_price = !empty($_POST['shop_price']) ? trim($_POST['shop_price']) : 0;
            $shop_price = floatval($shop_price);
            $market_price = !empty($_POST['market_price']) ? trim($_POST['market_price']) : 0;
            $market_price = floatval($market_price);
            $promote_price = !empty($_POST['promote_price']) ? trim($_POST['promote_price']) : 0;
            $promote_price = floatval($promote_price);
            $cost_price = !empty($_POST['cost_price']) ? trim($_POST['cost_price']) : 0;
            $cost_price = floatval($cost_price);
            //$is_promote = empty($promote_price) ? 0 : 1;

            //ecmoban模板堂 --zhuo satrt
            if (!isset($_POST['is_promote'])) {
                $is_promote = 0;
            } else {
                $is_promote = $_POST['is_promote'];
            }
            //ecmoban模板堂 --zhuo end

            $promote_start_date = ($is_promote && !empty($_POST['promote_start_date'])) ? local_strtotime($_POST['promote_start_date']) : 0;
            $promote_end_date = ($is_promote && !empty($_POST['promote_end_date'])) ? local_strtotime($_POST['promote_end_date']) : 0;
            $goods_weight = !empty($_POST['goods_weight']) ? $_POST['goods_weight'] * $_POST['weight_unit'] : 0;
            $is_best = isset($_POST['is_best']) && !empty($_POST['is_best']) ? 1 : 0;
            $is_new = isset($_POST['is_new']) && !empty($_POST['is_new']) ? 1 : 0;
            $is_hot = isset($_POST['is_hot']) && !empty($_POST['is_hot']) ? 1 : 0;
            $is_on_sale = isset($_POST['is_on_sale']) && !empty($_POST['is_on_sale']) ? 1 : 0;
            $is_show = isset($_POST['is_show']) && !empty($_POST['is_show']) ? 1 : 0;
            $is_alone_sale = isset($_POST['is_alone_sale']) && !empty($_POST['is_alone_sale']) ? 1 : 0;
            $is_shipping = isset($_POST['is_shipping']) && !empty($_POST['is_shipping']) ? 1 : 0;
            $goods_number = isset($_POST['goods_number']) && !empty($_POST['goods_number']) ? $_POST['goods_number'] : 0;
            $warn_number = isset($_POST['warn_number']) && !empty($_POST['warn_number']) ? $_POST['warn_number'] : 0;
            $goods_type = isset($_POST['goods_type']) && !empty($_POST['goods_type']) ? $_POST['goods_type'] : 0;
            $give_integral = isset($_POST['give_integral']) ? intval($_POST['give_integral']) : '-1';
            $rank_integral = isset($_POST['rank_integral']) ? intval($_POST['rank_integral']) : '-1';
            $suppliers_id = isset($_POST['suppliers_id']) ? intval($_POST['suppliers_id']) : 0;
            $commission_rate = isset($_POST['commission_rate']) && !empty($_POST['commission_rate']) ? floatval($_POST['commission_rate']) : 0;
            $old_commission_rate = isset($_POST['old_commission_rate']) && !empty($_POST['old_commission_rate']) ? floatval(trim($_POST['old_commission_rate'])) : 0;
            $goods_video = isset($_POST['goods_video']) && !empty($_POST['goods_video']) ? addslashes($_POST['goods_video']) : '';

            $is_volume = isset($_POST['is_volume']) && !empty($_POST['is_volume']) ? intval($_POST['is_volume']) : 0;
            $is_fullcut = isset($_POST['is_fullcut']) && !empty($_POST['is_fullcut']) ? intval($_POST['is_fullcut']) : 0;
            $goods_unit = isset($_POST['goods_unit']) ? trim($_POST['goods_unit']) : '个'; //商品单位

            $_POST['goods_desc'] = isset($_POST['goods_desc']) ? $_POST['goods_desc'] : '';
            $_POST['goods_shipai'] = isset($_POST['goods_shipai']) ? $_POST['goods_shipai'] : '';

            /* 微分销 */
            $is_distribution = isset($_POST['is_distribution']) && !empty($_POST['is_distribution']) ? intval($_POST['is_distribution']) : 0; //如果选择商品分销则判断分销佣金百分比是否在0-100之间 如果不是则设置无效 liu  dis
            $_POST['dis_commission'] = isset($_POST['dis_commission']) ? $_POST['dis_commission'] : 0;
            $dis_commission = ($_POST['dis_commission'] > 0 && $_POST['dis_commission'] <= 100) && $is_distribution == 1 ? intval($_POST['dis_commission']) : 0;
            $is_discount = (int)request()->input('is_discount', 0); // 是否参与会员特价权益
            $user_price = request()->input('user_price', []);
            $user_rank = request()->input('user_rank', []);

            $bar_code = isset($_POST['bar_code']) && !empty($_POST['bar_code']) ? trim($_POST['bar_code']) : '';
            $_POST['goods_name_color'] = isset($_POST['goods_name_color']) ? $_POST['goods_name_color'] : '';
            $_POST['goods_name_style'] = isset($_POST['goods_name_style']) ? $_POST['goods_name_style'] : '';
            $goods_name_style = $_POST['goods_name_color'] . '+' . $_POST['goods_name_style'];
            
            $other_catids = isset($_POST['other_catids']) ? trim($_POST['other_catids']) : '';

            $catgory_id = empty($_POST['cat_id']) ? '' : intval($_POST['cat_id']);

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $free_rate = empty($_POST['free_rate']) ? 0 : intval($_POST['free_rate']);
            }
            //常用分类 by wu
            if (empty($catgory_id) && !empty($_POST['common_category'])) {
                $catgory_id = intval($_POST['common_category']);
            }

            $brand_id = empty($_POST['brand_id']) ? '' : intval($_POST['brand_id']);

            //ecmoban模板堂 --zhuo
            $store_category = !empty($_POST['store_category']) ? intval($_POST['store_category']) : 0;
            if ($store_category > 0) {
                $catgory_id = $store_category;
            }

            $user_cat_arr = explode('_', $_POST['user_cat']);
            $user_cat = $user_cat_arr[0];

            /* ecmoban模板堂  序列化分期送期数数据   start bylu */
            if ($_POST['is_stages']) {
                $stages = serialize($_POST['stages_num']); //分期期数;
                $stages_rate = isset($_POST['stages_rate']) && !empty($_POST['stages_rate']) ? floatval($_POST['stages_rate']) : 0; //分期费率;
            } else {
                $stages = '';
                $stages_rate = '';
            }
            /* ecmoban模板堂  end bylu */

            $model_price = isset($_POST['model_price']) && !empty($_POST['model_price']) ? intval($_POST['model_price']) : 0;
            $model_inventory = isset($_POST['model_inventory']) && !empty($_POST['model_inventory']) ? intval($_POST['model_inventory']) : 0;
            $model_attr = isset($_POST['model_attr']) && !empty($_POST['model_attr']) ? intval($_POST['model_attr']) : 0;

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

            //ecmoban模板堂 --zhuo start 限购
            $xiangou_num = !empty($_POST['xiangou_num']) ? intval($_POST['xiangou_num']) : 0;
            $is_xiangou = empty($xiangou_num) ? 0 : 1;
            $xiangou_start_date = ($is_xiangou && !empty($_POST['xiangou_start_date'])) ? local_strtotime($_POST['xiangou_start_date']) : 0;
            $xiangou_end_date = ($is_xiangou && !empty($_POST['xiangou_end_date'])) ? local_strtotime($_POST['xiangou_end_date']) : 0;
            //ecmoban模板堂 --zhuo end 限购

            // 最小起订量
            $minimum = !empty($_POST['minimum']) ? intval($_POST['minimum']) : 0;
            $is_minimum = !empty($_POST['is_minimum']) ? intval($_POST['is_minimum']) : 0;
            $is_minimum = empty($minimum) || empty($is_minimum) ? 0 : 1;
            $minimum = empty($minimum) || empty($is_minimum) ? 0 : $minimum;
            $minimum_start_date = ($is_minimum && !empty($_POST['minimum_start_date'])) ? local_strtotime($_POST['minimum_start_date']) : 0;
            $minimum_end_date = ($is_minimum && !empty($_POST['minimum_end_date'])) ? local_strtotime($_POST['minimum_end_date']) : 0;


            //ecmoban模板堂 --zhuo start 促销满减
            $cfull = isset($_POST['cfull']) ? $_POST['cfull'] : [];
            $creduce = isset($_POST['creduce']) ? $_POST['creduce'] : [];
            $c_id = isset($_POST['c_id']) ? $_POST['c_id'] : [];

            $sfull = isset($_POST['sfull']) ? $_POST['sfull'] : [];
            $sreduce = isset($_POST['sreduce']) ? $_POST['sreduce'] : [];
            $s_id = isset($_POST['s_id']) ? $_POST['s_id'] : [];

            $goods_img_id = !empty($_REQUEST['img_id']) ? $_REQUEST['img_id'] : '';//相册ID
            $largest_amount = !empty($_POST['largest_amount']) ? trim($_POST['largest_amount']) : 0;
            $largest_amount = floatval($largest_amount);
            //ecmoban模板堂 --zhuo end 促销满减

            $group_number = !empty($_POST['group_number']) ? intval($_POST['group_number']) : 0;

            $store_new = isset($_POST['store_new']) && !empty($_POST['store_new']) ? 1 : 0;
            $store_hot = isset($_POST['store_hot']) && !empty($_POST['store_hot']) ? 1 : 0;
            $store_best = isset($_POST['store_best']) && !empty($_POST['store_best']) ? 1 : 0;

            $goods_name = trim($_POST['goods_name']);

            //by guan start
            $pin = new Pinyin();
            $pinyin = $pin->Pinyin($goods_name, 'UTF8');
            //by guan end

            $user_cat = !empty($_POST['user_cat']) ? intval($_POST['user_cat']) : 0;

            $freight = empty($_POST['freight']) ? 0 : intval($_POST['freight']);
            $shipping_fee = !empty($_POST['shipping_fee']) && $freight == 1 ? floatval($_POST['shipping_fee']) : '0.00';
            $tid = !empty($_POST['tid']) && $_POST['freight'] == 2 ? intval($_POST['tid']) : 0;

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

            $time = gmtime();

            $_POST['keywords'] = isset($_POST['keywords']) && !empty($_POST['keywords']) ? trim($_POST['keywords']) : '';
            $_POST['goods_brief'] = isset($_POST['goods_brief']) && !empty($_POST['goods_brief']) ? trim($_POST['goods_brief']) : '';
            $_POST['seller_note'] = isset($_POST['seller_note']) && !empty($_POST['seller_note']) ? trim($_POST['seller_note']) : '';
            $_POST['integral'] = isset($_POST['integral']) && !empty($_POST['integral']) ? trim($_POST['integral']) : '';
            $_POST['goods_desc'] = isset($_POST['goods_desc']) && !empty($_POST['goods_desc']) ? trim($_POST['goods_desc']) : '';
            $_POST['desc_mobile'] = isset($_POST['desc_mobile']) && !empty($_POST['desc_mobile']) ? trim($_POST['desc_mobile']) : '';
            $_POST['goods_product_tag'] = isset($_POST['goods_product_tag']) && !empty($_POST['goods_product_tag']) ? trim($_POST['goods_product_tag']) : '';
            $_POST['goods_tag'] = isset($_POST['goods_tag']) && !empty($_POST['goods_tag']) ? trim($_POST['goods_tag']) : '';
            $_POST['goods_shipai'] = isset($_POST['goods_shipai']) && !empty($_POST['goods_shipai']) ? trim($_POST['goods_shipai']) : '';

            /* 入库 */
            if ($is_insert) {
                $other = [
                    'goods_name' => $goods_name,
                    'goods_name_style' => $goods_name_style,
                    'goods_sn' => $goods_sn,
                    'goods_video' => $goods_video,
                    'bar_code' => $bar_code,
                    'cat_id' => $catgory_id,
                    'user_cat' => $user_cat,
                    'brand_id' => $brand_id,
                    'shop_price' => $shop_price,
                    'market_price' => $market_price,
                    'cost_price' => $cost_price,
                    'is_promote' => $is_promote,
                    'promote_price' => $promote_price,
                    'promote_start_date' => $promote_start_date,
                    'promote_end_date' => $promote_end_date,
                    'goods_img' => $goods_img,
                    'goods_thumb' => $goods_thumb,
                    'original_img' => $original_img,
                    'keywords' => $_POST['keywords'],
                    'goods_brief' => $_POST['goods_brief'],
                    'seller_note' => $_POST['seller_note'],
                    'goods_weight' => $goods_weight,
                    'goods_number' => $goods_number,
                    'warn_number' => $warn_number,
                    'integral' => $_POST['integral'],
                    'give_integral' => $give_integral,
                    'is_best' => $is_best,
                    'is_new' => $is_new,
                    'is_hot' => $is_hot,
                    'is_on_sale' => $is_on_sale,
                    'is_show' => $is_show,
                    'is_alone_sale' => $is_alone_sale,
                    'is_shipping' => $is_shipping,
                    'goods_desc' => $_POST['goods_desc'],
                    'desc_mobile' => $_POST['desc_mobile'],
                    'add_time' => $time,
                    'last_update' => $time,
                    'goods_type' => $goods_type,
                    'rank_integral' => $rank_integral,
                    'suppliers_id' => $suppliers_id,
                    'goods_shipai' => $_POST['goods_shipai'],
                    'user_id' => $adminru['ru_id'],
                    'model_price' => $model_price,
                    'model_inventory' => $model_inventory,
                    'model_attr' => $model_attr,
                    'review_status' => $review_status,
                    'commission_rate' => $commission_rate,
                    'group_number' => $group_number,
                    'store_new' => $store_new,
                    'store_hot' => $store_hot,
                    'store_best' => $store_best,
                    'goods_cause' => $goods_cause,
                    'goods_product_tag' => $_POST['goods_product_tag'],
                    'goods_tag' => $_POST['goods_tag'],
                    'is_volume' => $is_volume,
                    'is_fullcut' => $is_fullcut,
                    'is_xiangou' => $is_xiangou,
                    'xiangou_num' => $xiangou_num,
                    'xiangou_start_date' => $xiangou_start_date,
                    'xiangou_end_date' => $xiangou_end_date,
                    'largest_amount' => $largest_amount,
                    'pinyin_keyword' => $pinyin,
                    'stages' => $stages,
                    'stages_rate' => $stages_rate,
                    'goods_unit' => $goods_unit,
                    'freight' => $freight,
                    'shipping_fee' => $shipping_fee,
                    'tid' => $tid,
                    'is_minimum' => $is_minimum,
                    'minimum' => $minimum,
                    'minimum_start_date' => $minimum_start_date,
                    'minimum_end_date' => $minimum_end_date,
                    'is_discount' => $is_discount
                ];

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $other['free_rate'] = $free_rate;
                }

                if (file_exists(MOBILE_DRP)) {
                    $other['is_distribution'] = $is_distribution;
                    $other['dis_commission'] = $dis_commission;
                }

                if (!empty($code)) {
                    $other['is_real'] = 0;
                    $other['extension_code'] = $code;
                }

                $goods_id = Goods::insertGetId($other);

                //库存日志
                $not_number = !empty($goods_number) ? 1 : 0;
                $number = "+ " . $goods_number;
                $use_storage = 7;
            } else {
                $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

                $other = [
                    'goods_name' => $goods_name,
                    'goods_name_style' => $goods_name_style,
                    'goods_sn' => $goods_sn,
                    'bar_code' => $bar_code,
                    'cat_id' => $catgory_id,
                    'brand_id' => $brand_id,
                    'shop_price' => $shop_price,
                    'market_price' => $market_price,
                    'cost_price' => $cost_price,
                    'is_promote' => $is_promote,
                    'is_volume' => $is_volume,
                    'is_fullcut' => $is_fullcut,
                    'commission_rate' => $commission_rate,
                    'model_price' => $model_price,
                    'model_inventory' => $model_inventory,
                    'model_attr' => $model_attr,
                    'largest_amount' => $largest_amount,
                    'group_number' => $group_number,
                    'store_new' => $store_new,
                    'store_hot' => $store_hot,
                    'store_best' => $store_best,
                    'goods_unit' => $goods_unit,
                    'is_xiangou' => $is_xiangou,
                    'xiangou_num' => $xiangou_num,
                    'xiangou_start_date' => $xiangou_start_date,
                    'xiangou_end_date' => $xiangou_end_date,
                    'goods_product_tag' => $_POST['goods_product_tag'],
                    'goods_tag' => $_POST['goods_tag'],
                    'pinyin_keyword' => $pinyin,
                    'stages' => $stages,
                    'stages_rate' => $stages_rate,
                    'goods_cause' => $goods_cause,
                    'user_cat' => $user_cat,
                    'freight' => $freight,
                    'shipping_fee' => $shipping_fee,
                    'tid' => $tid,
                    'promote_price' => $promote_price,
                    'promote_start_date' => $promote_start_date,
                    'suppliers_id' => $suppliers_id,
                    'promote_end_date' => $promote_end_date,
                    'review_status' => $review_status,
                    'is_minimum' => $is_minimum,
                    'minimum' => $minimum,
                    'minimum_start_date' => $minimum_start_date,
                    'minimum_end_date' => $minimum_end_date,
                    'is_discount' => $is_discount
                ];

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $other['free_rate'] = $free_rate;
                }

                /* 微分销 */
                if (file_exists(MOBILE_DRP)) {
                    $other['dis_commission'] = $dis_commission;
                    $other['is_distribution'] = $is_distribution;
                }

                /* 如果有上传图片，需要更新数据库 */
                if ($goods_img) {
                    $other['goods_img'] = $goods_img;
                    $other['original_img'] = $original_img;
                }

                if ($goods_thumb) {
                    $other['goods_thumb'] = $goods_thumb;
                }

                if (!empty($code)) {
                    $other['is_real'] = 0;
                    $other['extension_code'] = $code;
                }

                $other['keywords'] = $_POST['keywords'];
                $other['goods_brief'] = $_POST['goods_brief'];
                $other['seller_note'] = $_POST['seller_note'];
                $other['goods_weight'] = $goods_weight;
                $other['goods_number'] = $goods_number;
                $other['warn_number'] = $warn_number;
                $other['integral'] = $_POST['integral'];
                $other['give_integral'] = $give_integral;
                $other['rank_integral'] = $rank_integral;
                $other['is_best'] = $is_best;
                $other['is_new'] = $is_new;
                $other['is_hot'] = $is_hot;
                $other['is_on_sale'] = $is_on_sale;
                $other['is_show'] = $is_show;
                $other['is_alone_sale'] = $is_alone_sale;
                $other['is_shipping'] = $is_shipping;
                $other['goods_desc'] = $_POST['goods_desc'];
                $other['desc_mobile'] = $_POST['desc_mobile'];
                $other['goods_shipai'] = $_POST['goods_shipai'];
                $other['last_update'] = $time;
                $other['goods_type'] = $goods_type;

                Goods::where('goods_id', $goods_id)->update($other);

                //库存日志
                $goodsInfo = get_admin_goods_info($goods_id);

                if ($goods_number > $goodsInfo['goods_number']) {
                    $not_number = $goods_number - $goodsInfo['goods_number'];
                    $not_number = !empty($not_number) ? 1 : 0;
                    $number = $goods_number - $goodsInfo['goods_number'];
                    $number = "+ " . $number;
                    $use_storage = 13;
                } else {
                    $not_number = $goodsInfo['goods_number'] - $goods_number;
                    $not_number = !empty($not_number) ? 1 : 0;
                    $number = $goodsInfo['goods_number'] - $goods_number;
                    $number = "- " . $number;
                    $use_storage = 8;
                }

                //商品操作日志	 更新前数据
                $goods_info = Goods::where('goods_id', $goods_id);
                $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

                $member_price_old = [];
                $member_price_arr = MemberPrice::where('goods_id', $goods_id);
                $member_price_arr = $member_price_arr->with([
                    'getUserRank'
                ]);
                $member_price_arr = $this->baseRepository->getToArrayGet($member_price_arr);

                if ($member_price_arr) {
                    foreach ($member_price_arr as $key => $val) {
                        $member_price_arr[$key]['min_points'] = $val['get_user_rank']['min_points'] ?? 0;
                        $member_price_arr[$key]['rank_id'] = $val['get_user_rank']['rank_id'] ?? 0;
                    }

                    $member_price_arr = $this->baseRepository->getSortBy($member_price_arr, 'min_points');
                    foreach ($member_price_arr as $v) {
                        $member_price_old[$v['user_rank']] = $v['user_price'];
                    }
                }

                $volume_price_sql = " SELECT * FROM " . $this->dsc->table('volume_price') . " WHERE goods_id = '$goods_id' ";
                $volume_price_arr = $this->db->getAll($volume_price_sql);
                if ($volume_price_arr) {
                    foreach ($volume_price_arr as $v) {
                        $volume_price_old[$v['volume_number']] = $v['volume_price'];
                    }
                } else {
                    $volume_price_old = [];
                }

                $logs_change_old = [
                    'goods_id' => $goods_id,
                    'shop_price' => $goods_info['shop_price'],
                    'shipping_fee' => $goods_info['shipping_fee'],
                    'promote_price' => $goods_info['promote_price'],
                    'member_price' => serialize($member_price_old),
                    'volume_price' => serialize($volume_price_old),
                    'give_integral' => $goods_info['give_integral'],
                    'rank_integral' => $goods_info['rank_integral'],
                    'goods_weight' => $goods_info['goods_weight'],
                    'is_on_sale' => $goods_info['is_on_sale'],
                    'user_id' => session('seller_id'),
                    'handle_time' => $time,
                    'old_record' => 1
                ];

                $this->db->autoExecute($this->dsc->table('goods_change_log'), $logs_change_old, 'INSERT');

                //商品操作日志	 更新后数据
                $member_price = [];
                if ($user_price) {
                    $member_price = array_combine($user_rank, $user_price);
                }

                $volume_price = '';
                if ($_POST['is_volume']) {
                    $volume_price = array_combine($_POST['volume_number'], $_POST['volume_price']);
                }

                $logs_change = [
                    'goods_id' => $goods_id,
                    'shop_price' => $shop_price,
                    'shipping_fee' => $shipping_fee,
                    'promote_price' => $promote_price,
                    'member_price' => $member_price ? serialize($member_price) : '',
                    'volume_price' => $volume_price ? serialize($volume_price) : '',
                    'give_integral' => $give_integral,
                    'rank_integral' => $rank_integral,
                    'goods_weight' => $goods_weight,
                    'is_on_sale' => $is_on_sale,
                    'user_id' => session('seller_id'),
                    'handle_time' => $time,
                    'old_record' => 0
                ];

                $this->db->autoExecute($this->dsc->table('goods_change_log'), $logs_change, 'INSERT');
            }

            /* 商品编号 */

            if ($is_insert) {
                if ($other_catids) {
                    $other_catids = $this->dscRepository->delStrComma($other_catids);
                    $sql = "UPDATE" . $this->dsc->table("goods_cat") . " SET goods_id='$goods_id' WHERE goods_id = 0 AND cat_id in ($other_catids)";
                    $this->db->query($sql);
                }

                /**
                 * 创建视频图片目录，并移动视频位置
                 */
                $video_path = storage_public(DATA_DIR . '/uploads/goods/' . $goods_id . "/");

                if (!file_exists($video_path)) {
                    make_dir($video_path);
                }

                $re_path = DATA_DIR . "/uploads/goods/0/";
                $new_goods_video = str_replace($re_path, '', $goods_video);

                if (strpos($new_goods_video, 'data/uploads/goods') !== false) {
                    $copy_goods_video = explode('/', $new_goods_video);
                    $new_goods_video = $copy_goods_video[count($copy_goods_video) - 1];
                }

                if (file_exists(storage_public($goods_video)) && $goods_video) {
                    if (move_upload_file(storage_public($goods_video), $video_path . $new_goods_video)) {
                        $goods_video = DATA_DIR . '/uploads/goods/' . $goods_id . "/" . $new_goods_video;
                        $sql = "UPDATE" . $this->dsc->table("goods") . " SET goods_video = '$goods_video' WHERE goods_id = '$goods_id'";
                        $this->db->query($sql);
                    }
                }

                $act_copy = isset($_REQUEST['act_copy']) && !empty($_REQUEST['act_copy']) ? intval($_REQUEST['act_copy']) : 0;

                if ($act_copy == 1) {
                    $images_dir = local_date('Ym');

                    $goodsCopy = [];
                    if ($original_img) {
                        $copy_original_img = explode('source_img/', $original_img);

                        if (count($copy_original_img) > 1 && is_file(storage_public($original_img))) {
                            if (!file_exists(storage_public(IMAGE_DIR . '/' . $images_dir . '/source_img/'))) {
                                Storage::makeDirectory(IMAGE_DIR . '/' . $images_dir . '/source_img/');
                            }

                            $copyoriginalimg = IMAGE_DIR . '/' . $images_dir . '/source_img/' . $goods_id . "_copy_" . $copy_original_img[1];

                            Storage::copy($original_img, $copyoriginalimg);

                            $goodsCopy['original_img'] = $copyoriginalimg;
                        }
                    }

                    if ($goods_img) {
                        $copy_goods_img = explode('goods_img/', $goods_img);

                        if (count($copy_goods_img) > 1 && is_file(storage_public($goods_img))) {
                            if (!file_exists(storage_public(IMAGE_DIR . '/' . $images_dir . '/goods_img/'))) {
                                Storage::makeDirectory(IMAGE_DIR . '/' . $images_dir . '/goods_img/');
                            }

                            $copygoodsimg = IMAGE_DIR . '/' . $images_dir . '/goods_img/' . $goods_id . "_copy_" . $copy_goods_img[1];

                            Storage::copy($goods_img, $copygoodsimg);

                            $goodsCopy['goods_img'] = $copygoodsimg;
                        }
                    }

                    if ($goods_thumb) {
                        $copy_goods_thumb = explode('thumb_img/', $goods_thumb);

                        if (count($copy_goods_thumb) > 1 && is_file(storage_public($goods_thumb))) {
                            if (!file_exists(storage_public(IMAGE_DIR . '/' . $images_dir . '/thumb_img/'))) {
                                Storage::makeDirectory(IMAGE_DIR . '/' . $images_dir . '/thumb_img/');
                            }

                            $copygoodsthumb = IMAGE_DIR . '/' . $images_dir . '/thumb_img/' . $goods_id . "_copy_" . $copy_goods_thumb[1];

                            Storage::copy($goods_thumb, $copygoodsthumb);

                            $goodsCopy['goods_thumb'] = $copygoodsthumb;
                        }
                    }

                    if ($goodsCopy) {
                        Goods::where('goods_id', $goods_id)->update($goodsCopy);
                    }
                }
            } else {
                /**
                 * 更新购物车
                 * $freight
                 * $tid
                 * $shipping_fee
                 */
                $sql = "UPDATE" . $this->dsc->table("cart") . " SET freight = '$freight', tid = '$tid', shipping_fee = '$shipping_fee' WHERE goods_id = '$goods_id'";
                $this->db->query($sql);

                /**
                 * 更新购物车
                 * 应结佣金比例
                 * $commission_rate
                 */
                if ($old_commission_rate <> $commission_rate) {
                    $sql = "UPDATE " . $this->dsc->table('cart') . " SET commission_rate = '$commission_rate' WHERE ru_id = '" . $adminru['ru_id'] . "' AND goods_id = '$goods_id' AND is_real = 1 AND is_gift = 0";
                    $this->db->query($sql);
                }
            }

            //by wang start
            if ($goods_id) {
                //商品扩展信息
                $is_reality = !empty($_POST['is_reality']) ? intval($_POST['is_reality']) : 0;
                $is_return = !empty($_POST['is_return']) ? intval($_POST['is_return']) : 0;
                $is_fast = !empty($_POST['is_fast']) ? intval($_POST['is_fast']) : 0;
                $extend = $this->db->getOne("select count(*) from " . $this->dsc->table('goods_extend') . " where goods_id='$goods_id'");
                if ($extend > 0) {
                    //跟新商品扩展信息
                    $extend_sql = "update " . $this->dsc->table('goods_extend') . " SET `is_reality`='$is_reality',`is_return`='$is_return',`is_fast`='$is_fast' WHERE goods_id='$goods_id'";
                } else {
                    //插入商品扩展信息
                    $extend_sql = "INSERT INTO " . $this->dsc->table('goods_extend') . "(`goods_id`, `is_reality`, `is_return`, `is_fast`) VALUES ('$goods_id','$is_reality','$is_return','$is_fast')";
                }
                $this->db->query($extend_sql);

                get_updel_goods_attr($goods_id);
            }
            //by wang end

            //扩展信息 by wu start
            $extend_arr = [];
            $extend_arr['width'] = isset($_POST['width']) ? trim($_POST['width']) : ''; //宽度
            $extend_arr['height'] = isset($_POST['height']) ? trim($_POST['height']) : ''; //高度
            $extend_arr['depth'] = isset($_POST['depth']) ? trim($_POST['depth']) : ''; //深度
            $extend_arr['origincountry'] = isset($_POST['origincountry']) ? trim($_POST['origincountry']) : ''; //产国
            $extend_arr['originplace'] = isset($_POST['originplace']) ? trim($_POST['originplace']) : ''; //产地
            $extend_arr['assemblycountry'] = isset($_POST['assemblycountry']) ? trim($_POST['assemblycountry']) : ''; //组装国
            $extend_arr['barcodetype'] = isset($_POST['barcodetype']) ? trim($_POST['barcodetype']) : ''; //条码类型
            $extend_arr['catena'] = isset($_POST['catena']) ? trim($_POST['catena']) : ''; //产品系列
            $extend_arr['isbasicunit'] = isset($_POST['isbasicunit']) ? intval($_POST['isbasicunit']) : 0; //是否是基本单元
            $extend_arr['packagetype'] = isset($_POST['packagetype']) ? trim($_POST['packagetype']) : ''; //包装类型
            $extend_arr['grossweight'] = isset($_POST['grossweight']) ? trim($_POST['grossweight']) : ''; //毛重
            $extend_arr['netweight'] = isset($_POST['netweight']) ? trim($_POST['netweight']) : ''; //净重
            $extend_arr['netcontent'] = isset($_POST['netcontent']) ? trim($_POST['netcontent']) : ''; //净含量
            $extend_arr['licensenum'] = isset($_POST['licensenum']) ? trim($_POST['licensenum']) : ''; //生产许可证
            $extend_arr['healthpermitnum'] = isset($_POST['healthpermitnum']) ? trim($_POST['healthpermitnum']) : ''; //卫生许可证
            $this->db->autoExecute($this->dsc->table('goods_extend'), $extend_arr, "UPDATE", "goods_id = '$goods_id'");
            //扩展信息 by wu end

            //库存日志
            if ($not_number) {
                $logs_other = [
                    'goods_id' => $goods_id,
                    'order_id' => 0,
                    'use_storage' => $use_storage,
                    'admin_id' => session('seller_id'),
                    'number' => $number,
                    'model_inventory' => $model_inventory,
                    'model_attr' => $model_attr,
                    'add_time' => $time
                ];

                $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
            }

            //消费满N金额减N减额
            get_goods_payfull($is_fullcut, $cfull, $creduce, $c_id, $goods_id, 'goods_consumption');
            //消费满N金额减N减运费
            //get_goods_payfull($sfull, $sreduce, $s_id, $goods_id, 'goods_conshipping', 1);

            /* 记录日志 */
            if ($is_insert) {
                //ecmoban模板堂 --zhuo start 仓库
                if ($model_price == 1) {
                    $warehouse_id = isset($_POST['warehouse_id']) ? $_POST['warehouse_id'] : [];

                    if ($warehouse_id) {
                        $warehouse_id = implode(",", $warehouse_id);
                        $this->db->query(" UPDATE " . $this->dsc->table("warehouse_goods") . " SET goods_id = '$goods_id' WHERE w_id " . db_create_in($warehouse_id));
                    }
                } elseif ($model_price == 2) {
                    $warehouse_area_id = isset($_POST['warehouse_area_id']) ? $_POST['warehouse_area_id'] : [];

                    if ($warehouse_area_id) {
                        $warehouse_area_id = implode(",", $warehouse_area_id);
                        $this->db->query(" UPDATE " . $this->dsc->table("warehouse_area_goods") . " SET goods_id = '$goods_id' WHERE a_id " . db_create_in($warehouse_area_id));
                    }
                }
                //ecmoban模板堂 --zhuo end 仓库

                admin_log($_POST['goods_name'], 'add', 'goods');
            } else {
                admin_log($_POST['goods_name'], 'edit', 'goods');
                //by li start
                $shop_price_format = price_format($shop_price);
                //降价通知
                $sql = "SELECT * FROM " . $this->dsc->table('sale_notice') . " WHERE goods_id='$goods_id' AND STATUS!=1";
                $notice_list = $this->db->getAll($sql);

                foreach ($notice_list as $key => $val) {
                    //查询会员名称 by wu
                    $sql = " select user_name from " . $this->dsc->table('users') . " where user_id='" . $val['user_id'] . "' ";
                    $user_info = $this->db->getRow($sql);
                    $user_name = $user_info['user_name'];

                    //短信发送
                    $send_ok = 0;

                    if ($shop_price <= $val['hopeDiscount'] && $val['cellphone'] && $GLOBALS['_CFG']['sms_price_notice'] == '1') {
                        $user_info = get_admin_user_info($val['user_id']);

                        //短信接口参数
                        $smsParams = [
                            'user_name' => $user_info['user_name'],
                            'username' => $user_info['user_name'],
                            'goodsname' => $this->dscRepository->subStr($goods_name, 20),
                            'goodsprice' => $shop_price,
                            'mobile_phone' => $val['cellphone'],
                            'mobilephone' => $val['cellphone']
                        ];

                        $res = $this->commonRepository->smsSend($val['cellphone'], $smsParams, 'sms_price_notic');

                        //记录日志
                        $send_type = 2;
                        if ($res) {
                            $sql = "UPDATE " . $this->dsc->table('sale_notice') . " SET status = 1, send_type=2 WHERE goods_id = '$goods_id' AND user_id='$val[user_id]'";
                            $this->db->query($sql);
                            $send_ok = 1;
                            notice_log($goods_id, $val['cellphone'], $send_ok, $send_type);
                        } else {
                            $sql = "UPDATE " . $this->dsc->table('sale_notice') . " SET status = 3, send_type=2 WHERE goods_id = '$goods_id' AND user_id='$val[user_id]'";
                            $this->db->query($sql);
                            $send_ok = 0;
                            notice_log($goods_id, $val['cellphone'], $send_ok, $send_type);
                        }
                    }

                    //当短信发送失败，邮件发送
                    if ($send_ok == 0 && $shop_price <= $val['hopeDiscount'] && $val['email']) {
                        /* 设置留言回复模板所需要的内容信息 */
                        $template = get_mail_template('sale_notice');

                        $this->smarty->assign('user_name', $user_name);
                        $this->smarty->assign('goods_name', $_POST['goods_name']);
                        $this->smarty->assign('goods_link', $this->dsc->seller_url() . "goods.php?id=" . $goods_id);
                        $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                        $content = $this->smarty->fetch('str:' . $template['template_content']);

                        $send_type = 1;
                        /* 发送邮件 */
                        if ($this->commonRepository->sendEmail($user_name, $val['email'], $template['template_subject'], $content, $template['is_html'])) {
                            $sql = "UPDATE " . $this->dsc->table('sale_notice') . " SET status = 1, send_type=1 WHERE goods_id = '$goods_id' AND user_id='$val[user_id]'";
                            $this->db->query($sql);
                            $send_ok = 1;
                            notice_log($goods_id, $val['email'], $send_ok, $send_type);
                        } else {
                            $sql = "UPDATE " . $this->dsc->table('sale_notice') . " SET status = 3, send_type=1 WHERE goods_id = '$goods_id' AND user_id='$val[user_id]'";
                            $this->db->query($sql);
                            $send_ok = 0;
                            notice_log($goods_id, $val['email'], $send_ok, $send_type);
                        }
                    }
                }
                //by li end
            }

            /* 处理属性 */
            if ((isset($_POST['attr_id_list']) && isset($_POST['attr_value_list'])) || (empty($_POST['attr_id_list']) && empty($_POST['attr_value_list']))) {
                // 取得原有的属性值
                $goods_attr_list = [];

                $sql = "SELECT attr_id, attr_index FROM " . $this->dsc->table('attribute') . " WHERE cat_id = '$goods_type'";
                $attr_res = $this->db->query($sql);

                $attr_list = [];
                foreach ($attr_res as $row) {
                    $attr_list[$row['attr_id']] = $row['attr_index'];
                }

                $sql = "SELECT g.*, a.attr_type
                FROM " . $this->dsc->table('goods_attr') . " AS g
                    LEFT JOIN " . $this->dsc->table('attribute') . " AS a
                        ON a.attr_id = g.attr_id
                WHERE g.goods_id = '$goods_id'";

                $res = $this->db->query($sql);

                foreach ($res as $row) {
                    $goods_attr_list[$row['attr_id']][$row['attr_value']] = ['sign' => 'delete', 'goods_attr_id' => $row['goods_attr_id']];
                }

                // 循环现有的，根据原有的做相应处理
                if (isset($_POST['attr_id_list'])) {
                    foreach ($_POST['attr_id_list'] as $key => $attr_id) {
                        $attr_value = $_POST['attr_value_list'][$key];
                        $attr_price = $_POST['attr_price_list'][$key];
                        $attr_sort = isset($_POST['attr_sort_list'][$key]) ? $_POST['attr_sort_list'][$key] : ''; //ecmoban模板堂 --zhuo
                        if (!empty($attr_value)) {
                            if (isset($goods_attr_list[$attr_id][$attr_value])) {
                                // 如果原来有，标记为更新
                                $goods_attr_list[$attr_id][$attr_value]['sign'] = 'update';
                                $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                                $goods_attr_list[$attr_id][$attr_value]['attr_sort'] = $attr_sort;
                            } else {
                                // 如果原来没有，标记为新增
                                $goods_attr_list[$attr_id][$attr_value]['sign'] = 'insert';
                                $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                                $goods_attr_list[$attr_id][$attr_value]['attr_sort'] = $attr_sort;
                            }
                        }
                    }
                }

                // 循环现有的，根据原有的做相应处理
                if (isset($_POST['gallery_attr_id'])) {
                    foreach ($_POST['gallery_attr_id'] as $key => $attr_id) {
                        $gallery_attr_value = $_POST['gallery_attr_value'][$key] ?? '';
                        $gallery_attr_price = $_POST['gallery_attr_price'][$key] ?? 0;
                        $gallery_attr_sort = $_POST['gallery_attr_sort'][$key];
                        if (!empty($gallery_attr_value)) {
                            if (isset($goods_attr_list[$attr_id][$gallery_attr_value])) {
                                // 如果原来有，标记为更新
                                $goods_attr_list[$attr_id][$gallery_attr_value]['sign'] = 'update';
                                $goods_attr_list[$attr_id][$gallery_attr_value]['attr_price'] = $gallery_attr_price;
                                $goods_attr_list[$attr_id][$gallery_attr_value]['attr_sort'] = $gallery_attr_sort;
                            } else {
                                // 如果原来没有，标记为新增
                                $goods_attr_list[$attr_id][$gallery_attr_value]['sign'] = 'insert';
                                $goods_attr_list[$attr_id][$gallery_attr_value]['attr_price'] = $gallery_attr_price;
                                $goods_attr_list[$attr_id][$gallery_attr_value]['attr_sort'] = $gallery_attr_sort;
                            }
                        }
                    }
                }

                /* 插入、更新、删除数据 */
                foreach ($goods_attr_list as $attr_id => $attr_value_list) {
                    foreach ($attr_value_list as $attr_value => $info) {
                        if ($info['sign'] == 'insert') { //ecmoban模板堂 --zhuo attr_sort
                            $sql = "INSERT INTO " . $this->dsc->table('goods_attr') . " (attr_id, goods_id, attr_value, attr_price, attr_sort)" .
                                "VALUES ('$attr_id', '$goods_id', '$attr_value', '$info[attr_price]', '$info[attr_sort]')";
                        } elseif ($info['sign'] == 'update') { //ecmoban模板堂 --zhuo attr_sort
                            $sql = "UPDATE " . $this->dsc->table('goods_attr') . " SET attr_price = '$info[attr_price]', attr_sort = '$info[attr_sort]' WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1";
                        } else {
                            if ($model_attr == 1) {
                                $prod = ProductsWarehouse::where('goods_id', $goods_id);
                            } elseif ($model_attr == 2) {
                                $prod = ProductsArea::where('goods_id', $goods_id);
                            } else {
                                $prod = Products::where('goods_id', $goods_id);
                            }

                            $prod = $prod->whereRaw("FIND_IN_SET('" . $info['goods_attr_id'] . "', REPLACE(goods_attr, '|', ','))");

                            $prod->delete();

                            $sql = "DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE goods_attr_id = '" . $info['goods_attr_id'] . "' LIMIT 1";
                        }
                        $this->db->query($sql);
                    }
                }
            }

            /* 处理会员价格 */
            if (isset($user_rank) && isset($user_price)) {
                $this->goodsManageService->handle_member_price($goods_id, $user_rank, $user_price, $is_discount);
            }

            /* 处理优惠价格 */
            if (isset($_POST['volume_number']) && isset($_POST['volume_price'])) {
                handle_volume_price($goods_id, $is_volume, $_POST['volume_number'], $_POST['volume_price'], $_POST['id']);
            }

            /* 处理扩展分类 */
            if (isset($_POST['other_cat'])) {
                handle_other_cat($goods_id, array_unique($_POST['other_cat']));
            }

            if ($is_insert) {
                /* 处理关联商品 */
                handle_link_goods($goods_id);

                /* 处理组合商品 */
                handle_group_goods($goods_id);

                /* 处理关联文章 */
                handle_goods_article($goods_id);

                /* 处理关联地区 add by qin */
                handle_goods_area($goods_id);

                /* 处理相册图片 by wu */
                $thumb_img_id = session('thumb_img_id' . session('seller_id'), 0);//处理添加商品时相册图片串图问题   by kong
                if ($thumb_img_id) {
                    $sql = " UPDATE " . $this->dsc->table('goods_gallery') . " SET goods_id = '" . $goods_id . "' WHERE goods_id = 0 AND img_id " . db_create_in($thumb_img_id);
                    $this->db->query($sql);
                }
                session()->forget('thumb_img_id' . session('seller_id'));
            }

            /* 如果有图片，把商品图片加入图片相册 */
            if (!empty($_POST['goods_img_url']) && $is_img_url == 1) {
                /* 重新格式化图片名称 */
                $original_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $original_img, 'source');
                $goods_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $goods_img, 'goods');
                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $goods_id, $goods_thumb, 'thumb');

                // 处理商品图片
                $sql = " UPDATE " . $this->dsc->table('goods') . " SET goods_thumb = '$goods_thumb', goods_img = '$goods_img', original_img = '$original_img' WHERE goods_id = '$goods_id' ";
                $this->db->query($sql);

                if (isset($img)) {
                    // 重新格式化图片名称
                    if (empty($is_url_goods_img)) {
                        $img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $img, 'source');
                        $gallery_img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $gallery_img, 'goods');
                    } else {
                        $img = $original_img;
                        $gallery_img = $goods_img;
                    }

                    $gallery_thumb = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');

                    $sql = "INSERT INTO " . $this->dsc->table('goods_gallery') . " (goods_id, img_url, thumb_url, img_original) " .
                        "VALUES ('$goods_id', '$gallery_img', '$gallery_thumb', '$img')";
                    $this->db->query($sql);
                }

                $this->dscRepository->getOssAddFile([$goods_img, $goods_thumb, $original_img, $gallery_img, $gallery_thumb, $img]);
            } else {
                $this->dscRepository->getOssAddFile([$goods_img, $goods_thumb, $original_img]);
            }

            /** ************* 处理货品数据 start ************** */
            $where_products = "";
            $goods_model = isset($_POST['goods_model']) && !empty($_POST['goods_model']) ? intval($_POST['goods_model']) : 0;
            $warehouse = isset($_POST['warehouse']) && !empty($_POST['warehouse']) ? intval($_POST['warehouse']) : 0;
            $region = isset($_POST['region']) && !empty($_POST['region']) ? intval($_POST['region']) : 0;
            $city_id = isset($_POST['city_region']) && !empty($_POST['city_region']) ? intval($_POST['city_region']) : 0;
            $arrt_page_count = isset($_POST['arrt_page_count']) && !empty($_POST['arrt_page_count']) ? intval($_POST['arrt_page_count']) : 1; //属性分页

            $region_id = 0;
            if ($goods_model == 1) {
                //数据表
                $table = "products_warehouse";
                //地区id
                $region_id = $warehouse;
                //插入补充数据
                $products_extension_insert_name = " , warehouse_id ";
                $products_extension_insert_value = " , '$warehouse' ";
                //补充筛选
                $where_products .= " AND warehouse_id = '$warehouse' ";
            } elseif ($goods_model == 2) {
                $table = "products_area";
                $region_id = $region;
                $products_extension_insert_name = " , area_id , city_id ";
                $products_extension_insert_value = " , '$region' , '$city_id' ";
                $where_products .= " AND area_id = '$region' ";

                if ($GLOBALS['_CFG']['area_pricetype']) {
                    $where_products .= " AND city_id = '$city_id' ";
                }
            } else {
                $table = "products";
                $products_extension_insert_name = "";
                $products_extension_insert_value = "";
            }

            if ($is_insert) {
                $sql = "UPDATE" . $this->dsc->table($table) . " SET goods_id = '$goods_id' WHERE goods_id = 0 AND admin_id = '$admin_id'";
                $this->db->query($sql);
            } else {
                //下架商品购物车设置失效
                if ($is_on_sale == 0) {
                    $this->db->query(" UPDATE " . $this->dsc->table('cart') . " SET is_invalid = 1 WHERE goods_id = '$goods_id' ");
                }
            }

            $product['goods_id'] = $goods_id;
            $product['attr'] = isset($_POST['attr']) && !empty($_POST['attr']) ? $_POST['attr'] : [];
            $product['product_id'] = isset($_POST['product_id']) && !empty($_POST['product_id']) ? $_POST['product_id'] : [];
            $product['product_sn'] = isset($_POST['product_sn']) && !empty($_POST['product_sn']) ? $_POST['product_sn'] : [];
            $product['product_number'] = isset($_POST['product_number']) && !empty($_POST['product_number']) ? $_POST['product_number'] : [];
            $product['product_price'] = isset($_POST['product_price']) && !empty($_POST['product_price']) ? $_POST['product_price'] : []; //货品价格
            $product['product_market_price'] = isset($_POST['product_market_price']) && !empty($_POST['product_market_price']) ? $_POST['product_market_price'] : []; //货品市场价格
            $product['product_promote_price'] = isset($_POST['product_promote_price']) ? $_POST['product_promote_price'] : []; //货品促销价格
            $product['product_warn_number'] = isset($_POST['product_warn_number']) && !empty($_POST['product_warn_number']) ? $_POST['product_warn_number'] : []; //警告库存
            $product['bar_code'] = isset($_POST['product_bar_code']) && !empty($_POST['product_bar_code']) ? $_POST['product_bar_code'] : []; //货品条形码

            /* 是否存在商品id */
            if (empty($product['goods_id'])) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, [], false);
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price, model_inventory, model_attr FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id' LIMIT 1";
            $goods = $this->db->getRow($sql);

            /* 货号 */
            if (empty($product['product_sn'])) {
                $product['product_sn'] = [];
            }

            foreach ($product['product_sn'] as $key => $value) {
                //过滤
                $product['product_number'][$key] = trim($product['product_number'][$key]); //库存
                $product['product_id'][$key] = isset($product['product_id'][$key]) && !empty($product['product_id'][$key]) ? intval($product['product_id'][$key]) : 0; //货品ID

                $logs_other = [
                    'goods_id' => $goods_id,
                    'order_id' => 0,
                    'admin_id' => session('seller_id'),
                    'model_inventory' => $goods['model_inventory'],
                    'model_attr' => $goods['model_attr'],
                    'add_time' => gmtime()
                ];

                if ($goods_model == 1) {
                    $logs_other['warehouse_id'] = $warehouse;
                    $logs_other['area_id'] = 0;
                    $logs_other['city_id'] = 0;
                } elseif ($goods_model == 2) {
                    $logs_other['warehouse_id'] = $warehouse;
                    $logs_other['area_id'] = $region;
                    $logs_other['city_id'] = $city_id;
                } else {
                    $logs_other['warehouse_id'] = 0;
                    $logs_other['area_id'] = 0;
                    $logs_other['city_id'] = 0;
                }

                if ($product['product_id'][$key]) {

                    /* 货品库存 */
                    $goods_product = get_product_info($product['product_id'][$key], 'product_number', $goods_model);

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
                        $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                    }

                    if (empty($value)) {
                        $product_sn = $goods['goods_sn'] . "g_p" . $product['product_id'][$key];
                    } else {
                        $product_sn = $value;
                    }

                    $sql = "UPDATE " . $this->dsc->table($table) . " SET product_number = '" . $product['product_number'][$key] . "', " .
                        " product_market_price = '" . $product['product_market_price'][$key] . "', " .
                        " product_price = '" . $product['product_price'][$key] . "', " .
                        " product_promote_price = '" . $product['product_promote_price'][$key] . "', " .
                        " product_warn_number = '" . $product['product_warn_number'][$key] . "'," .
                        "product_sn = '" . $product_sn . "'" .
                        " WHERE product_id = '" . $product['product_id'][$key] . "'";
                    $this->db->query($sql);
                } else {
                    $number = 0;
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
                    $goods_attr_id = handle_goods_attr($product['goods_id'], $id_list, $is_spec_list, $value_price_list);

                    /* 是否为重复规格的货品 */
                    $goods_attr = $this->goodsAttrService->sortGoodsAttrIdArray($goods_attr_id);

                    if (!empty($goods_attr['sort'])) {
                        $goods_attr = implode('|', $goods_attr['sort']);
                    } else {
                        $goods_attr = "";
                    }

                    if (check_goods_attr_exist($goods_attr, $product['goods_id'], 0, $region_id)) { //by wu
                        continue;
                    }

                    /* 插入货品表 */
                    $product_other = [
                        'goods_id' => $product['goods_id'],
                        'goods_attr' => $goods_attr,
                        'product_sn' => $value,
                        'product_number' => $product['product_number'][$key] ?? 0,
                        'product_price' => $product['product_price'][$key] ?? 0,
                        'product_market_price' => $product['product_market_price'][$key] ?? 0,
                        'product_promote_price' => $product['product_promote_price'][$key] ?? 0,
                        'product_warn_number' => $product['product_warn_number'][$key] ?? 0,
                        'bar_code' => $product['bar_code'][$key] ?? ''
                    ];

                    if ($goods_model == 1) {
                        $product_other['warehouse_id'] = $warehouse;
                    } elseif ($goods_model == 2) {
                        $product_other['area_id'] = $region;
                        $product_other['city_id'] = $city_id;
                    }

                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $product_other, 'INSERT');

                    $product_id = $GLOBALS['db']->insert_id();

                    if (!$product_id) {
                        continue;
                    } else {
                        $product_id = $this->db->insert_id();

                        //货品号为空 自动补货品号
                        if (empty($value)) {
                            $sql = "UPDATE " . $this->dsc->table($table) . "
                                SET product_sn = '" . $goods['goods_sn'] . "g_p" . $this->db->insert_id() . "'
                                WHERE product_id = '$product_id'";
                            $this->db->query($sql);
                        }

                        //库存日志
                        $number = "+ " . $product['product_number'][$key];
                        $logs_other['use_storage'] = 9;
                        $logs_other['product_id'] = $product_id;
                        $logs_other['number'] = $number;
                        $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                    }
                }
            }
            //插入货品零时表数据
            $changelog_where = "WHERE 1 AND admin_id = '$admin_id'";

            if ($is_insert) {
                $changelog_where .= " AND goods_id = 0";
            } else {
                $changelog_where .= " AND goods_id = '$goods_id'";
            }

            if (!empty($changelog_product_id)) {
                $changelog_where .= " AND product_id NOT " . db_create_in($changelog_product_id);
            }

            $sql = "SELECT goods_attr,product_sn,bar_code,product_number,product_price,product_market_price,product_promote_price,product_warn_number,warehouse_id,area_id,admin_id FROM" .
                $this->dsc->table('products_changelog') . $changelog_where . $where_products;
            $products_changelog = $this->db->getAll($sql);

            if (!empty($products_changelog)) {
                foreach ($products_changelog as $k => $v) {
                    if (check_goods_attr_exist($v['goods_attr'], $product['goods_id'], 0, $region_id)) { //检测货品是否存在
                        continue;
                    }
                    $number = 0;
                    $logs_other = [
                        'goods_id' => $goods_id,
                        'order_id' => 0,
                        'admin_id' => $admin_id,
                        'model_inventory' => $goods['model_inventory'],
                        'model_attr' => $goods['model_attr'],
                        'add_time' => gmtime()
                    ];

                    if ($goods_model == 1) {
                        $logs_other['warehouse_id'] = $warehouse;
                        $logs_other['area_id'] = 0;
                        $logs_other['city_id'] = 0;
                    } elseif ($goods_model == 2) {
                        $logs_other['warehouse_id'] = $warehouse;
                        $logs_other['area_id'] = $region;
                        $logs_other['city_id'] = $city_id;
                    } else {
                        $logs_other['warehouse_id'] = 0;
                        $logs_other['area_id'] = 0;
                        $logs_other['city_id'] = 0;
                    }

                    /* 插入货品表 */
                    $sql = "INSERT INTO " . $this->dsc->table($table) .
                        " (goods_id, goods_attr, product_sn, product_number, product_price, product_market_price, product_promote_price, product_warn_number, admin_id, bar_code " . $products_extension_insert_name . ") VALUES " .
                        " ('" . $product['goods_id'] . "', '" . $v['goods_attr'] . "', '" . $v['product_sn'] . "', '" . $v['product_number'] . "', '" . $v['product_price'] . "', '" . $v['product_market_price'] . "', '" . $v['product_promote_price'] . "', '" . $v['product_warn_number'] . "', '" . $v['admin_id'] . "', '" . $v['bar_code'] . "' " . $products_extension_insert_value . ")";
                    if (!$this->db->query($sql)) {
                        continue;
                    } else {
                        $product_id = $this->db->insert_id();

                        //货品号为空 自动补货品号
                        if (empty($v['product_sn'])) {
                            $sql = "UPDATE " . $this->dsc->table($table) . "
                                SET product_sn = '" . $goods['goods_sn'] . "g_p" . $product_id . "'
                                WHERE product_id = '$product_id'";
                            $this->db->query($sql);
                        }

                        //库存日志
                        $number = "+ " . $v['product_number'];
                        $logs_other['use_storage'] = 9;
                        $logs_other['product_id'] = $product_id;
                        $logs_other['number'] = $number;
                        $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                    }
                }
            }

            //清楚商品零时货品表数据
            $pcl_del_res = ProductsChangelog::where('goods_id', $goods_id);
            if (empty($goods_id)) {
                $pcl_del_res = $pcl_del_res->where('admin_id', $admin_id);
            }
            $pcl_del_res->delete();

            /*************** 处理货品数据 end ***************/

            /* 同步前台商品详情价格与商品列表价格一致 start */
            $goods = get_admin_goods_info($goods_id);
            if ($GLOBALS['_CFG']['add_shop_price'] == 0 && $goods['model_attr'] == 0) {
                load_helper('goods');

                $properties = $this->goodsAttrService->getGoodsProperties($goods_id);  // 获得商品的规格和属性
                $spe = !empty($properties['spe']) ? array_values($properties['spe']) : $properties['spe'];

                $arr = [];
                $goodsAttrId = '';
                if ($spe) {
                    foreach ($spe as $key => $val) {
                        if ($val['values']) {
                            if ($val['is_checked']) {
                                $arr[$key]['values'] = get_goods_checked_attr($val['values']);
                            } else {
                                $arr[$key]['values'] = $val['values'][0];
                            }
                        }

                        if ($arr[$key]['values']['id']) {
                            $goodsAttrId .= $arr[$key]['values']['id'] . ",";
                        }
                    }

                    $goodsAttrId = $this->dscRepository->delStrComma($goodsAttrId);
                }

                $time = gmtime();
                if (!empty($goodsAttrId)) {
                    $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $goodsAttrId, 0, 0, 0, $goods['model_attr']);

                    if ($products) {
                        $products['product_market_price'] = isset($products['product_market_price']) ? $products['product_market_price'] : 0;
                        $products['product_price'] = isset($products['product_price']) ? $products['product_price'] : 0;
                        $products['product_promote_price'] = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;

                        $promote_price = 0;
                        if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date']) {
                            $promote_price = $goods['promote_price'];
                        }

                        if ($goods['promote_price'] > 0) {
                            $promote_price = $this->goodsCommonService->getBargainPrice($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
                        } else {
                            $promote_price = 0;
                        }

                        if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date']) {
                            $promote_price = $products['product_promote_price'];
                        }

                        $other = [
                            'product_id' => $products['product_id'],
                            'product_price' => $products['product_price'],
                            'product_promote_price' => $promote_price
                        ];
                        Goods::where('goods_id', $goods_id)->update($other);
                    }
                }
            } else {
                if ($goods['model_attr'] > 0) {
                    $goods_other = [
                        'product_table' => '',
                        'product_id' => 0,
                        'product_price' => 0,
                        'product_promote_price' => 0
                    ];
                    Goods::where('goods_id', $goods_id)->update($goods_other);
                }
            }
            /* 同步前台商品详情价格与商品列表价格一致 end */

            if ($goods_id) {
                $data = ['is_shipping' => $is_shipping];
                Cart::where('goods_id', $goods_id)
                    ->where('extension_code', '<>', 'package_buy')
                    ->update($data);
            }

            if ($is_insert) {
                get_del_update_goods_null($goods_id, 1);
            } else {
                if ($goods_type == 0 && $goods_id > 0) {
                    Products::where('goods_id', $goods_id)->delete();

                    ProductsArea::where('goods_id', $goods_id)->delete();

                    ProductsWarehouse::where('goods_id', $goods_id)->delete();

                    GoodsAttr::where('goods_id', $goods_id)->delete();
                }
            }

            /* 清空缓存 */
            clear_cache_files();

            /* 提示页面 */
            $link = [];

            if ($code == 'virtual_card') {
                $link[1] = ['href' => 'virtual_card.php?act=replenish&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['add_replenish']];
            }
            if ($is_insert) {
                $link[2] = $this->add_link($code);
            }
            $link[3] = $this->list_link($is_insert, $code);

            //$key_array = array_keys($link);
            for ($i = 0; $i < count($link); $i++) {
                $key_array[] = $i;
            }
            krsort($link);
            $link = array_combine($key_array, $link);

            return sys_msg($is_insert ? $GLOBALS['_LANG']['add_goods_ok'] : $GLOBALS['_LANG']['edit_goods_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch') {
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

            /* 取得要操作的商品编号 */
            $goods_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
            if (isset($_POST['type'])) {
                /* 放入回收站 */
                if ($_POST['type'] == 'trash') {
                    /* 检查权限 */
                    admin_priv('remove_back');

                    $is_promotion = is_promotion($goods_id);

                    if ($is_promotion) {
                        foreach ($is_promotion as $res) {
                            $res[$res['type']]['goods_sn'] = isset($res[$res['type']]['goods_sn']) ? $this->dscRepository->delStrComma($res[$res['type']]['goods_sn']) : '';

                            switch ($res['type']) {
                                case 'snatch': //夺宝奇兵
                                    return sys_msg($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_snatch'], 0);
                                    break;

                                case 'group_buy': //团购
                                    return sys_msg($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_group_buy'], 0);
                                    break;

                                case 'auction': //拍卖
                                    return sys_msg($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_auction'], 0);
                                    break;

                                case 'package': //礼包
                                    return sys_msg($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_package'], 0);
                                    break;
                            }
                        }
                    }

                    $seckill = is_seckill($goods_id);
                    if ($seckill) {
                        return sys_msg($GLOBALS['_LANG']['del_goods_sn'] . $seckill . $GLOBALS['_LANG']['del_seckill'], 0); // 秒杀
                    }
                    $presale = is_presale($goods_id);
                    if ($presale) {
                        return sys_msg($GLOBALS['_LANG']['del_goods_sn'] . $presale . $GLOBALS['_LANG']['del_presale'], 0); // 预售
                    }

                    update_goods($goods_id, 'is_delete', '1');

                    /* 记录日志 */
                    admin_log('', 'batch_trash', 'goods');
                } /* 上架 */
                elseif ($_POST['type'] == 'on_sale') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_on_sale', '1');
                } /* 下架 */
                elseif ($_POST['type'] == 'not_on_sale') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_on_sale', '0');
                } /* 设为精品 */
                elseif ($_POST['type'] == 'best') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_best', '1');
                } /* 取消精品 */
                elseif ($_POST['type'] == 'not_best') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_best', '0');
                } /* 设为新品 */
                elseif ($_POST['type'] == 'new') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_new', '1');
                } /* 取消新品 */
                elseif ($_POST['type'] == 'not_new') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_new', '0');
                } /* 设为热销 */
                elseif ($_POST['type'] == 'hot') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_hot', '1');
                } /* 取消热销 */
                elseif ($_POST['type'] == 'not_hot') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'is_hot', '0');
                } /* 转移到分类 */
                elseif ($_POST['type'] == 'move_to') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'cat_id', $_POST['target_cat']);
                } /* 转移到供货商 */
                elseif ($_POST['type'] == 'suppliers_move_to') {
                    /* 检查权限 */
                    admin_priv('goods_manage');
                    update_goods($goods_id, 'suppliers_id', $_POST['suppliers_id']);
                } /* 还原 */
                elseif ($_POST['type'] == 'restore') {
                    /* 检查权限 */
                    admin_priv('remove_back');

                    update_goods($goods_id, 'is_delete', '0');

                    /* 记录日志 */
                    admin_log('', 'batch_restore', 'goods');
                } /* 删除 */
                elseif ($_POST['type'] == 'drop') {
                    /* 检查权限 */
                    admin_priv('remove_back');

                    $this->goodsManageService->deleteGoods($goods_id);

                    /* 记录日志 */
                    admin_log('', 'batch_remove', 'goods');
                } /* 审核商品 ecmoban模板堂 --zhuo */
                elseif ($_POST['type'] == 'review_to') {
                    /* 检查权限 */
                    admin_priv('remove_back');

                    update_goods($goods_id, 'review_status', $_POST['review_status'], $_POST['review_content']);

                    /* 记录日志 */
                    admin_log('', 'review_to', 'goods');
                } /* 运费模板 */
                elseif ($_POST['type'] == 'goods_transport') {
                    /* 检查权限 */
                    admin_priv('goods_manage');

                    $data = [];
                    $data['freight'] = 2;
                    $data['tid'] = $_POST['tid'];
                    $this->db->autoExecute($this->dsc->table('goods'), $data, "UPDATE", "goods_id " . db_create_in($goods_id) . " AND user_id = '" . $adminru['ru_id'] . "'");

                    /**
                     * 更新购物车
                     * $freight
                     * $tid
                     * $shipping_fee
                     */
                    $sql = "UPDATE" . $this->dsc->table("cart") . " SET freight = '" . $data['freight'] . "', tid = '" . $data['tid'] . "' WHERE goods_id " . db_create_in($goods_id) . " AND ru_id = '" . $adminru['ru_id'] . "'";
                    $this->db->query($sql);

                    /* 记录日志 */
                    admin_log('', 'batch_edit', 'goods_transport');
                } //批量设置退换货
                elseif ($_POST['type'] == 'return_type') {
                    //修改退换货标识
                    $sql = "UPDATE" . $this->dsc->table('goods') . "SET goods_cause = '0,1,2,3' WHERE goods_id " . db_create_in($goods_id);
                    $this->db->query($sql);
                    //查找商品拓展
                    $goods_id = explode(',', $goods_id);
                    if (!empty($goods_id)) {
                        foreach ($goods_id as $v) {
                            $sql = "SELECT COUNT(*) FROM" . $this->dsc->table('goods_extend') . "WHERE goods_id = '$v'";
                            $goods_extend = $this->db->getOne($sql);
                            if ($goods_extend > 0) {
                                $sql = " UPDATE" . $this->dsc->table('goods_extend') . "SET is_return = 1 WHERE goods_id = '$v'";
                            } else {
                                $sql = "INSERT INTO" . $this->dsc->table('goods_extend') . "(`goods_id`,`is_return`)VALUES('$v',1)";
                            }
                            $this->db->query($sql);
                        }
                    }
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            if ($_POST['type'] == 'drop' || $_POST['type'] == 'restore') {
                $link[] = ['href' => 'goods.php?act=trash', 'text' => $GLOBALS['_LANG']['11_goods_trash']];
            } else {
                $link[] = $this->list_link(false, $code);
            }
            return sys_msg($GLOBALS['_LANG']['batch_handle_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 显示图片
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'show_image') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) {
                $img_url = $_GET['img_url'];
            } else {
                if (strpos($_GET['img_url'], 'http://') === 0 && strpos($_GET['img_url'], 'https://') === 0) {
                    $img_url = $_GET['img_url'];
                } else {
                    $img_url = '../' . $_GET['img_url'];
                }
            }
            $this->smarty->assign('img_url', $img_url);
            return $this->smarty->display('goods_show_image.dwt');
        }

        /*------------------------------------------------------ */
        //-- 修改商品名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_name') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_name = json_str_iconv(trim($_POST['val']));
            $review_status = 1;
            $old_goods_name = $exc->get_name($goods_id);
            if ($old_goods_name != $goods_name) {
                if ($GLOBALS['_CFG']['review_goods'] == 0) {
                    $review_status = 5;
                } else {
                    if ($adminru['ru_id'] > 0) {
                        $sql = "select review_goods from " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
                        $review_goods = $this->db->getOne($sql); //判断

                        if ($review_goods == 0) {
                            $review_status = 5;
                        }
                    } else {
                        $review_status = 5;
                    }
                }

                $res = Goods::where('goods_id', $goods_id)
                    ->update([
                        'goods_name' => $goods_name,
                        'review_status' => $review_status,
                        'last_update' => gmtime()
                    ]);

                if ($res) {
                    clear_cache_files();
                    return make_json_result(stripslashes($goods_name));
                }
            } else {
                return make_json_result(stripslashes($goods_name));
            }
        }

        /* ------------------------------------------------------ */
        //-- 修改商品货号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_sn = json_str_iconv(trim($_POST['val']));

            $goods_info = get_admin_goods_info($goods_id);

            /* 检查是否重复 */
            if (!$exc->is_only('goods_sn', $goods_sn, $goods_id, "user_id = '" . $goods_info['user_id'] . "'")) {
                return make_json_error($GLOBALS['_LANG']['goods_sn_exists']);
            }

            $where = " AND (SELECT g.user_id FROM " . $this->dsc->table('goods') . " AS g WHERE g.goods_id = p.goods_id LIMIT 1) = '" . $adminru['ru_id'] . "'";
            $sql = "SELECT p.goods_id FROM " . $this->dsc->table('products') . " AS p WHERE p.product_sn='$goods_sn'" . $where;
            if ($this->db->getOne($sql)) {
                return make_json_error($GLOBALS['_LANG']['goods_sn_exists']);
            }
            if ($exc->edit("goods_sn = '$goods_sn', review_status = 1, last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result(stripslashes($goods_sn));
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品条形码
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_bar_code') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $bar_code = json_str_iconv(trim($_POST['val']));

            /* 检查是否重复 */
            if (!$exc->is_only('bar_code', $bar_code, $goods_id, "user_id = '" . $adminru['ru_id'] . "'")) {
                return make_json_error($GLOBALS['_LANG']['goods_bar_code_exists']);
            }

            $where = " AND (SELECT g.user_id FROM " . $this->dsc->table('goods') . " AS g WHERE g.goods_id = p.goods_id LIMIT 1) = '" . $adminru['ru_id'] . "'";
            $sql = "SELECT p.goods_id FROM " . $this->dsc->table('products') . " AS p WHERE p.bar_code = '$bar_code'" . $where;
            if ($this->db->getOne($sql)) {
                return make_json_error($GLOBALS['_LANG']['goods_bar_code_exists']);
            }
            if ($exc->edit("bar_code = '$bar_code', review_status = 1", $goods_id)) {
                clear_cache_files();
                return make_json_result(stripslashes($bar_code));
            }
        }

        /*------------------------------------------------------ */
        //-- 判断商品货号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_goods_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['goods_id']);
            $goods_sn = htmlspecialchars(json_str_iconv(trim($_REQUEST['goods_sn'])));

            if (!empty($goods_sn)) {

                /* 检查是否重复 */
                if (!$exc->is_only('goods_sn', $goods_sn, $goods_id, "user_id = '" . $adminru['ru_id'] . "'")) {
                    return make_json_error($GLOBALS['_LANG']['goods_sn_exists']);
                }

                if (!empty($goods_sn)) {
                    $sql = "SELECT p.product_id FROM " . $this->dsc->table('products') . " AS p," .
                        $this->dsc->table('goods') . " AS g " .
                        "WHERE p.product_sn = '$goods_sn' AND p.goods_id = g.goods_id AND g.user_id = '" . $adminru['ru_id'] . "'";
                    $product_id = $this->db->getOne($sql);

                    if ($product_id) {
                        return make_json_error($GLOBALS['_LANG']['goods_sn_exists']);
                    }
                }

                return make_json_result('');
            }
        }

        /*------------------------------------------------------ */
        //-- 判断商品货品货号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_products_goods_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['goods_id']);
            $goods_sn = json_str_iconv(trim($_REQUEST['goods_sn']));
            $products_sn = explode('||', $goods_sn);
            if (!is_array($products_sn)) {
                return make_json_result('');
            } else {
                foreach ($products_sn as $val) {
                    if (empty($val)) {
                        continue;
                    }
                    if (is_array($int_arry)) {
                        if (in_array($val, $int_arry)) {
                            return make_json_error($val . $GLOBALS['_LANG']['goods_sn_exists']);
                        }
                    }
                    $int_arry[] = $val;
                    if (!$exc->is_only('goods_sn', $val, '0')) {
                        return make_json_error($val . $GLOBALS['_LANG']['goods_sn_exists']);
                    }
                    $sql = "SELECT goods_id FROM " . $this->dsc->table('products') . "WHERE product_sn='$val'";
                    if ($this->db->getOne($sql)) {
                        return make_json_error($val . $GLOBALS['_LANG']['goods_sn_exists']);
                    }
                }
            }
            /* 检查是否重复 */
            return make_json_result('');
        }

        /*------------------------------------------------------ */
        //-- 修改商品价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_price = floatval($_POST['val']);
            $price_rate = floatval($GLOBALS['_CFG']['market_price_rate'] * $goods_price);
            $old_goods_price = $exc->get_name($goods_id, 'shop_price');

            if ($goods_price != $old_goods_price) {
                if ($goods_price < 0 || $goods_price == 0 && $_POST['val'] != "$goods_price") {
                    return make_json_error($GLOBALS['_LANG']['shop_price_invalid']);
                } else {
                    $review_status = 1;
                    if ($GLOBALS['_CFG']['review_goods'] == 0) {
                        $review_status = 5;
                    } else {
                        if ($adminru['ru_id'] > 0) {
                            $sql = "select review_goods from " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
                            $review_goods = $this->db->getOne($sql); //判断

                            if ($review_goods == 0) {
                                $review_status = 5;
                            }
                        } else {
                            $review_status = 5;
                        }
                    }
                    if ($exc->edit("shop_price = '$goods_price', market_price = '$price_rate', review_status = '$review_status', last_update=" . gmtime(), $goods_id)) {
                        clear_cache_files();
                        return make_json_result(number_format($goods_price, 2, '.', ''));
                    }
                }
            } else {
                return make_json_result(number_format($goods_price, 2, '.', ''));
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品库存数量
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_number') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_num = intval($_POST['val']);

            if ($goods_num < 0 || $goods_num == 0 && $_POST['val'] != "$goods_num") {
                return make_json_error($GLOBALS['_LANG']['goods_number_error']);
            }

            $object = Products::whereRaw(1);
            $exist = $this->goodsManageService->checkGoodsProductExist($object, $goods_id);

            if ($exist == 1) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_goods_number']);
            }

            //库存日志
            $goodsInfo = get_admin_goods_info($goods_id);
            if ($goods_num != $goodsInfo['goods_number']) {
                if ($goods_num > $goodsInfo['goods_number']) {
                    $number = $goods_num - $goodsInfo['goods_number'];
                    $number = "+ " . $number;
                    $use_storage = 13;
                } else {
                    $number = $goodsInfo['goods_number'] - $goods_num;
                    $number = "- " . $number;
                    $use_storage = 8;
                }

                $logs_other = [
                    'goods_id' => $goods_id,
                    'order_id' => 0,
                    'use_storage' => $use_storage,
                    'admin_id' => session('seller_id'),
                    'number' => $number,
                    'model_inventory' => $goodsInfo['model_inventory'],
                    'model_attr' => $goodsInfo['model_attr'],
                    'add_time' => gmtime()
                ];

                $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
            }

            $review_status = 1;
            if ($GLOBALS['_CFG']['review_goods'] == 0) {
                $review_status = 5;
            } else {
                if ($adminru['ru_id'] > 0) {
                    $sql = "select review_goods from " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
                    $review_goods = $this->db->getOne($sql); //判断

                    if ($review_goods == 0) {
                        $review_status = 5;
                    }
                } else {
                    $review_status = 5;
                }
            }

            if ($exc->edit("goods_number = '$goods_num', review_status = '$review_status', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($goods_num);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品佣金比例
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_commission_rate') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $commission_rate = floatval($_POST['val']);

            $goods = get_admin_goods_info($goods_id);

            $where = '';
            if ($goods['commission_rate'] != $commission_rate) {
                $review_status = 1;

                if ($GLOBALS['_CFG']['review_goods'] == 0) {
                    $review_status = 5;
                } else {
                    if ($adminru['ru_id'] > 0) {
                        $sql = "select review_goods from " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
                        $review_goods = $this->db->getOne($sql); //判断

                        if ($review_goods == 0) {
                            $review_status = 5;
                        }
                    } else {
                        $review_status = 5;
                    }
                }

                $where = ", review_status = '$review_status'";
            }

            if ($exc->edit("commission_rate = '$commission_rate'" . $where, $goods_id)) {
                clear_cache_files();
                return make_json_result($commission_rate);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改上架状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_sale') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            $sql = "select review_goods from " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
            $review_goods = $this->db->getOne($sql); //判断

            $change = "is_on_sale = '$on_sale', last_update=" . gmtime();
            if ($review_goods && $on_sale == 1) {
                $change .= ", review_status = 1";
            }
            if ($exc->edit($change, $goods_id)) {
                // 下架后清理购物车中的此商品
                if ($on_sale == 0) {
                    $this->db->query(" UPDATE " . $this->dsc->table('cart') . " SET is_invalid = 1  WHERE goods_id = '$goods_id' ");
                } else {
                    $sql = "SELECT act_id FROM " . $this->dsc->table('presale_activity') . " WHERE goods_id = '$goods_id'";
                    if ($this->db->getOne($sql, true)) {
                        $this->db->query("DELETE FROM " . $GLOBALS['dsc']->table('presale_activity') . " WHERE goods_id = '$goods_id' ");
                        $this->db->query("DELETE FROM " . $GLOBALS['dsc']->table('cart') . " WHERE goods_id = '$goods_id' ");
                    }
                }

                clear_cache_files();
                return make_json_result($on_sale);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品显示状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_is_show') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $is_show = intval($_POST['val']);

            $model = Goods::query()->where('goods_id', $goods_id)->first();

            if ($model) {

                // 检测是否加入指定购买成为分销商商品
                if (file_exists(MOBILE_DRP) && $is_show == 1) {
                    if ($model->membership_card_id > 0) {
                        return make_json_error(lang('manage/goods.membership_card_goods_notice'), 0);
                    }
                }

                $data = [
                    'is_show' => $is_show,
                    'last_update' => gmtime()
                ];

                $res = $model->update($data);
                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result($is_show);
                }
            }

            return make_json_error('invalid params');
        }

        /*------------------------------------------------------ */
        //-- 修改相册排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_img_desc') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $img_id = intval($_POST['id']);
            $img_desc = intval($_POST['val']);

            $exc_gallery = new Exchange($this->dsc->table('goods_gallery'), $this->db, 'img_id', 'goods_id');

            if ($exc_gallery->edit("img_desc = '$img_desc'", $img_id)) {
                clear_cache_files();
                return make_json_result($img_desc);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改精品推荐状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_best') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $is_best = intval($_POST['val']);

            if ($exc->edit("is_best = '$is_best', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($is_best);
            }
        } elseif ($_REQUEST['act'] == 'main_dsc') {
            $data = read_static_cache('seller_goods_str');
            if ($data === false) {
                $shop_url = urlencode($this->dsc->seller_url());
                $shop_info = get_shop_info_content(0);
                if ($shop_info) {
                    $shop_country = $shop_info['country'];
                    $shop_province = $shop_info['province'];
                    $shop_city = $shop_info['city'];
                    $shop_address = $shop_info['shop_address'];
                } else {
                    $shop_country = $GLOBALS['_CFG']['shop_country'];
                    $shop_province = $GLOBALS['_CFG']['shop_province'];
                    $shop_city = $GLOBALS['_CFG']['shop_city'];
                    $shop_address = $GLOBALS['_CFG']['shop_address'];
                }

                $qq = !empty($GLOBALS['_CFG']['qq']) ? $GLOBALS['_CFG']['qq'] : $shop_info['kf_qq'];
                $ww = !empty($GLOBALS['_CFG']['ww']) ? $GLOBALS['_CFG']['ww'] : $shop_info['kf_ww'];
                $service_email = !empty($GLOBALS['_CFG']['service_email']) ? $GLOBALS['_CFG']['service_email'] : $shop_info['seller_email'];
                $service_phone = !empty($GLOBALS['_CFG']['service_phone']) ? $GLOBALS['_CFG']['service_phone'] : $shop_info['kf_tel'];

                $shop_country = $this->db->getOne("SELECT region_name FROM " . $this->dsc->table('region') . " WHERE region_id='$shop_country'");
                $shop_province = $this->db->getOne("SELECT region_name FROM " . $this->dsc->table('region') . " WHERE region_id='$shop_province'");
                $shop_city = $this->db->getOne("SELECT region_name FROM " . $this->dsc->table('region') . " WHERE region_id='$shop_city'");

                $httpData = [
                    'domain' => $this->dsc->get_domain(), //当前域名
                    'url' => urldecode($shop_url), //当前url
                    'shop_name' => $GLOBALS['_CFG']['shop_name'],
                    'shop_title' => $GLOBALS['_CFG']['shop_title'],
                    'shop_desc' => $GLOBALS['_CFG']['shop_desc'],
                    'shop_keywords' => $GLOBALS['_CFG']['shop_keywords'],
                    'country' => $shop_country,
                    'province' => $shop_province,
                    'city' => $shop_city,
                    'address' => $shop_address,
                    'qq' => $qq,
                    'ww' => $ww,
                    'ym' => $service_phone, //客服电话
                    'msn' => $GLOBALS['_CFG']['msn'],
                    'email' => $service_email,
                    'phone' => $GLOBALS['_CFG']['sms_shop_mobile'], //手机号
                    'icp' => $GLOBALS['_CFG']['icp_number'],
                    'version' => VERSION,
                    'release' => RELEASE,
                    'language' => $GLOBALS['_CFG']['lang'],
                    'php_ver' => PHP_VERSION,
                    'mysql_ver' => $this->db->version(),
                    'charset' => EC_CHARSET
                ];

                $Http = new Http();
                $Http->doPost($GLOBALS['_CFG']['certi'], $httpData);

                write_static_cache('seller_goods_str', $httpData);
            }
        }
        /*------------------------------------------------------ */
        //-- 修改新品推荐状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_new') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $is_new = intval($_POST['val']);

            if ($exc->edit("is_new = '$is_new', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($is_new);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改热销推荐状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_hot') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $is_hot = intval($_POST['val']);

            if ($exc->edit("is_hot = '$is_hot', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($is_hot);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改店铺精品推荐状态 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_store_best') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $store_best = intval($_POST['val']);

            if ($exc->edit("store_best = '$store_best', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($store_best);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改店铺新品推荐状态 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_store_new') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $store_new = intval($_POST['val']);

            if ($exc->edit("store_new = '$store_new', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($store_new);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改店铺热销推荐状态 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_store_hot') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $store_hot = intval($_POST['val']);

            if ($exc->edit("store_hot = '$store_hot', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($store_hot);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改正品保证状态 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_is_reality') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($exc_extend->edit("is_reality = '$val'", $id)) {
                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改包        退服务状态 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_is_return') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($exc_extend->edit("is_return = '$val'", $id)) {
                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改闪速�        �送状态 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_is_fast') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($exc_extend->edit("is_fast = '$val'", $id)) {
                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改是否为�        �运费商品状态 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_is_shipping') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $is_shipping = intval($_POST['val']);

            if ($exc->edit("is_shipping = '$is_shipping', last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($is_shipping);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $sort_order = intval($_POST['val']);

            if ($exc->edit("sort_order = '$sort_order', review_status = 1, last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                return make_json_result($sort_order);
            }
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $is_delete = empty($_REQUEST['is_delete']) ? 0 : intval($_REQUEST['is_delete']);
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $goods_list = goods_list($is_delete, ($code == '') ? 1 : 0);

            $handler_list = [];
            $handler_list['virtual_card'][] = ['url' => 'virtual_card.php?act=card', 'title' => $GLOBALS['_LANG']['card'], 'img' => 'icon_send_bonus.gif'];
            $handler_list['virtual_card'][] = ['url' => 'virtual_card.php?act=replenish', 'title' => $GLOBALS['_LANG']['replenish'], 'img' => 'icon_add.gif'];
            $handler_list['virtual_card'][] = ['url' => 'virtual_card.php?act=batch_card_add', 'title' => $GLOBALS['_LANG']['batch_card_add'], 'img' => 'icon_output.gif'];

            if (isset($handler_list[$code])) {
                $this->smarty->assign('add_handler', $handler_list[$code]);
            }
            $this->smarty->assign('code', $code);
            $this->smarty->assign('goods_list', $goods_list['goods']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);
            $this->smarty->assign('list_type', $is_delete ? 'trash' : 'goods');
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($goods_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            /* 排序标记 */
            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 获取商品类型存在规格的类型 */
            $specifications = get_goods_type_specifications();
            $this->smarty->assign('specifications', $specifications);

            $tpl = $is_delete ? 'goods_trash.dwt' : 'goods_list.dwt';

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('transport_list', get_table_date("goods_transport", "ru_id='{$adminru['ru_id']}'", ['tid, title'], 1)); //商品运费 by wu

            $this->smarty->assign('nowTime', gmtime());

            return make_json_result(
                $this->smarty->fetch($tpl),
                '',
                ['filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 放�        �回收站
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $goods_id = intval($_REQUEST['id']);

            /* 检查权限 */
            $check_auth = check_authz_json('remove_back');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['id']);

            $order_count = $this->get_order_goods_cout($goods_id);

            if ($order_count > 0) {
                return make_json_error($GLOBALS['_LANG']['del_goods_fail']);
            }

            $sql = "SELECT goods_id, user_id " . "FROM " . $this->dsc->table('goods') .
                " WHERE goods_id = '$goods_id' LIMIT 1";
            $goods = $this->db->getRow($sql);

            if ($goods['user_id'] != $adminru['ru_id']) {
                $url = 'goods.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            if ($adminru['ru_id'] > 0 && $adminru['ru_id'] != $goods['user_id']) {
                return make_json_error($GLOBALS['_LANG']['illegal_operate_info_log']);
            }

            $is_promotion = is_promotion($goods_id);

            if ($is_promotion) {
                foreach ($is_promotion as $res) {
                    $res[$res['type']]['goods_sn'] = isset($res[$res['type']]['goods_sn']) ? $this->dscRepository->delStrComma($res[$res['type']]['goods_sn']) : '';

                    switch ($res['type']) {
                        case 'snatch': //夺宝奇兵
                            return make_json_error($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_snatch'], 0);
                            break;

                        case 'group_buy': //团购
                            return make_json_error($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_group_buy'], 0);
                            break;

                        case 'auction': //拍卖
                            return make_json_error($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_auction'], 0);
                            break;

                        case 'package': //礼包
                            return make_json_error($GLOBALS['_LANG']['del_goods_sn'] . $res[$res['type']]['goods_sn'] . $GLOBALS['_LANG']['del_package'], 0);
                            break;
                    }
                }
            }

            $seckill = is_seckill($goods_id);
            if ($seckill) {
                return make_json_error($GLOBALS['_LANG']['del_goods_sn'] . $seckill . $GLOBALS['_LANG']['del_seckill'], 0); // 秒杀
            }

            $presale = is_presale($goods_id);
            if ($presale) {
                return make_json_error($GLOBALS['_LANG']['del_goods_sn'] . $presale . $GLOBALS['_LANG']['del_presale'], 0); // 预售
            }

            if ($exc->edit("is_delete = 1", $goods_id)) {
                clear_cache_files();
                $goods_name = $exc->get_name($goods_id);

                admin_log(addslashes($goods_name), 'trash', 'goods'); // 记录日志

                $url = 'goods.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 还原回收站中的商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'restore_goods') {
            $goods_id = intval($_REQUEST['id']);

            $check_auth = check_authz_json('remove_back');
            if ($check_auth !== true) {
                return $check_auth;
            } // 检查权限

            $exc->edit("is_delete = 0, add_time = '" . gmtime() . "'", $goods_id);
            clear_cache_files();

            $goods_name = $exc->get_name($goods_id);

            admin_log(addslashes($goods_name), 'restore', 'goods'); // 记录日志

            $url = 'goods.php?act=query&' . str_replace('act=restore_goods', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 彻底删除商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_goods') {
            // 检查权限
            $check_auth = check_authz_json('remove_back');
            if ($check_auth !== true) {
                return $check_auth;
            }

            // 取得参数
            $goods_id = intval($_REQUEST['id']);
            if ($goods_id <= 0) {
                return make_json_error('invalid params');
            }

            /* 取得商品信息 */
            $sql = "SELECT goods_id, goods_name, is_delete, is_real, goods_thumb, user_id, " .
                "goods_img, original_img, goods_desc, goods_video " .
                "FROM " . $this->dsc->table('goods') .
                " WHERE goods_id = '$goods_id'";
            $goods = $this->db->getRow($sql);
            if (empty($goods)) {
                return make_json_error($GLOBALS['_LANG']['goods_not_exist']);
            }

            if ($adminru['ru_id'] > 0 && $adminru['ru_id'] != $goods['user_id']) {
                return make_json_error($GLOBALS['_LANG']['illegal_operate_info_log']);
            }

            if ($goods['is_delete'] != 1) {
                return make_json_error($GLOBALS['_LANG']['goods_not_in_recycle_bin']);
            }

            if ($goods['goods_desc']) {
                $desc_preg = get_goods_desc_images_preg('', $goods['goods_desc']);
                get_desc_images_del($desc_preg['images_list']);
            }

            $arr = [];
            /* 删除商品图片和轮播图片 */
            if (!empty($goods['goods_thumb']) && strpos($goods['goods_thumb'], "data/gallery_album") === false) {
                $arr[] = $goods['goods_thumb'];
                dsc_unlink(storage_public($goods['goods_thumb']));
            }
            if (!empty($goods['goods_img']) && strpos($goods['goods_img'], "data/gallery_album") === false) {
                $arr[] = $goods['goods_img'];
                dsc_unlink(storage_public($goods['goods_img']));
            }
            if (!empty($goods['original_img']) && strpos($goods['original_img'], "data/gallery_album") === false) {
                $arr[] = $goods['original_img'];
                dsc_unlink(storage_public($goods['original_img']));
            }
            if (!empty($arr)) {
                $this->dscRepository->getOssDelFile($arr);
            }

            /* 删除视频 */
            if (!empty($goods['goods_video'])) {
                $arr[] = $goods['goods_video'];
                dsc_unlink(storage_public($goods['goods_video']));

                $video_path = storage_public(DATA_DIR . '/uploads/goods/' . $goods['goods_id']);
                if (file_exists($video_path)) {
                    rmdir($video_path);
                }
            }

            /* 删除商品 */
            $exc->drop($goods_id);

            //删除商品扩展信息by wang
            $sql = "delete from " . $this->dsc->table('goods_extend') . " where goods_id='$goods_id'";
            $this->db->query($sql);

            /* 删除商品的货品记录 */
            $sql = "DELETE FROM " . $this->dsc->table('products') .
                " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);

            $sql = "DELETE FROM " . $this->dsc->table('products_warehouse') .
                " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);

            $sql = "DELETE FROM " . $this->dsc->table('products_area') .
                " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);

            //清楚商品零时货品表数据
            $sql = "DELETE FROM" . $this->dsc->table('products_changelog') .
                " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);

            /* 记录日志 */
            admin_log(addslashes($goods['goods_name']), 'remove', 'goods');

            /* 删除商品相册 */
            $sql = "SELECT img_url, thumb_url, img_original " .
                "FROM " . $this->dsc->table('goods_gallery') .
                " WHERE goods_id = '$goods_id'";
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                $arr = [];
                if (!empty($row['img_url']) && strpos($row['img_url'], "data/gallery_album") === false) {
                    $arr[] = $row['img_url'];
                    dsc_unlink(storage_public($row['img_url']));
                }
                if (!empty($row['thumb_url']) && strpos($row['thumb_url'], "data/gallery_album") === false) {
                    $arr[] = $row['thumb_url'];
                    dsc_unlink(storage_public($row['thumb_url']));
                }
                if (!empty($row['img_original']) && strpos($row['img_original'], "data/gallery_album") === false) {
                    $arr[] = $row['img_original'];
                    dsc_unlink(storage_public($row['img_original']));
                }

                $this->dscRepository->getOssDelFile($arr);
            }

            $sql = "DELETE FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);

            /* 删除相关表记录 */
            $sql = "DELETE FROM " . $this->dsc->table('collect_goods') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('goods_article') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('goods_cat') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('member_price') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('group_goods') . " WHERE parent_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('group_goods') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('link_goods') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('link_goods') . " WHERE link_goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('tag') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('comment') . " WHERE comment_type = 0 AND id_value = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('collect_goods') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('booking_goods') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('goods_activity') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('cart') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);

            $sql = "DELETE FROM " . $this->dsc->table('warehouse_goods') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('warehouse_attr') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('warehouse_area_goods') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);
            $sql = "DELETE FROM " . $this->dsc->table('warehouse_area_attr') . " WHERE goods_id = '$goods_id'";
            $this->db->query($sql);

            /* 如果不是实体商品，删除相应虚拟商品记录 */
            if ($goods['is_real'] != 1) {
                $sql = "DELETE FROM " . $this->dsc->table('virtual_card') . " WHERE goods_id = '$goods_id'";
                if (!$this->db->query($sql, 'SILENT') && $this->db->errno() != 1146) {
                    return $this->db->error();
                }
            }

            clear_cache_files();
            $url = 'goods.php?act=query&' . str_replace('act=drop_goods', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 切换商品类型
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_attr') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = empty($_GET['goods_id']) ? 0 : intval($_GET['goods_id']);
            $goods_type = empty($_GET['goods_type']) ? 0 : intval($_GET['goods_type']);

            //判断商品模式
            $modelAttr = empty($_GET['modelAttr']) ? 0 : intval($_GET['modelAttr']);

            $content = build_attr_html($goods_type, $goods_id, $modelAttr);

            return make_json_result($content);
        }

        /*------------------------------------------------------ */
        //-- 删除图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_image') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $img_id = empty($_REQUEST['img_id']) ? 0 : intval($_REQUEST['img_id']);

            /* 删除图片文件 */
            $sql = "SELECT img_url, thumb_url, img_original " .
                " FROM " . $this->dsc->table('goods_gallery') .
                " WHERE img_id = '$img_id'";
            $row = $this->db->getRow($sql);

            $img_url = storage_public($row['img_url']);
            $thumb_url = storage_public($row['thumb_url']);
            $img_original = storage_public($row['img_original']);

            $arr = [];
            if ($row['img_url'] != '' && strpos($row['img_url'], "data/gallery_album") === false) {
                $arr[] = $row['img_url'];
                dsc_unlink($img_url);
            }
            if ($row['thumb_url'] != '' && strpos($row['img_url'], "data/gallery_album") === false) {
                $arr[] = $row['thumb_url'];
                dsc_unlink($thumb_url);
            }
            if ($row['img_original'] != '' && strpos($row['img_url'], "data/gallery_album") === false) {
                $arr[] = $row['img_original'];
                dsc_unlink($img_original);
            }

            if (!empty($arr)) {
                $this->dscRepository->getOssDelFile($arr);
            }

            /* 删除数据 */
            $sql = "DELETE FROM " . $this->dsc->table('goods_gallery') . " WHERE img_id = '$img_id' LIMIT 1";
            $this->db->query($sql);

            clear_cache_files();
            return make_json_result($img_id);
        }

        /*------------------------------------------------------ */
        //-- 删除仓库库存 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_product') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = empty($_REQUEST['product_id']) ? 0 : intval($_REQUEST['product_id']);
            $group_attr = empty($_REQUEST['group_attr']) ? '' : $_REQUEST['group_attr'];
            $group_attr = dsc_decode($group_attr, true);

            if ($group_attr['goods_model'] == 1) {
                $table = 'products_warehouse';
                $select = "warehouse_id";
            } elseif ($group_attr['goods_model'] == 2) {
                $table = 'products_area';
                $select = "area_id, city_id";
            } else {
                $select = '*';
                $table = 'products';
            }

            $sql = "SELECT $select FROM " . $this->dsc->table($table) . " WHERE product_id = '$product_id' LIMIT 1";
            $product = $this->db->getRow($sql);

            $group_attr['warehouse_id'] = $product['warehouse_id'] ?? 0;
            $group_attr['area_id'] = $product['area_id'] ?? 0;
            $group_attr['city_id'] = $product['city_id'] ?? 0;

            /* 删除数据 */
            $sql = "DELETE FROM " . $this->dsc->table($table) . " WHERE product_id = '$product_id' LIMIT 1";
            $this->db->query($sql);

            clear_cache_files();
            return make_json_result_too($product_id, 0, '', $group_attr);
        }

        /*------------------------------------------------------ */
        //-- 删除仓库库存 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_warehouse') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = empty($_REQUEST['w_id']) ? 0 : intval($_REQUEST['w_id']);

            /* 删除数据 */
            $sql = "DELETE FROM " . $this->dsc->table('warehouse_goods') . " WHERE w_id = '$w_id' LIMIT 1";
            $this->db->query($sql);

            clear_cache_files();
            return make_json_result($w_id);
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库库存 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_number') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = intval($_POST['id']);
            $region_number = intval($_POST['val']);

            $sql = "SELECT goods_id, region_number, region_id FROM " . $this->dsc->table("warehouse_goods") . " WHERE w_id = '$w_id' LIMIT 1";
            $warehouse_goods = $this->db->getRow($sql);

            $goodsInfo = get_admin_goods_info($warehouse_goods['goods_id']);

            //库存日志
            if ($region_number != $warehouse_goods['region_number']) {
                if ($region_number > $warehouse_goods['region_number']) {
                    $number = $region_number - $warehouse_goods['region_number'];
                    $number = "+ " . $number;
                    $use_storage = 13;
                } else {
                    $number = $warehouse_goods['region_number'] - $region_number;
                    $number = "- " . $number;
                    $use_storage = 8;
                }

                $logs_other = [
                    'goods_id' => $warehouse_goods['goods_id'],
                    'order_id' => 0,
                    'use_storage' => $use_storage,
                    'admin_id' => session('seller_id'),
                    'number' => $number,
                    'model_inventory' => $goodsInfo['model_inventory'],
                    'model_attr' => $goodsInfo['model_attr'],
                    'warehouse_id' => $warehouse_goods['region_id'],
                    'add_time' => gmtime()
                ];

                $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
            }

            $sql = "update " . $this->dsc->table('warehouse_goods') . " set region_number = '$region_number' where w_id = '$w_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($region_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库编号 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = intval($_POST['id']);
            $region_sn = addslashes(trim($_POST['val']));

            $sql = "update " . $this->dsc->table('warehouse_goods') . " set region_sn = '$region_sn' where w_id = '$w_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($region_sn);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = intval($_POST['id']);
            $warehouse_price = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_goods') . " set warehouse_price = '$warehouse_price' where w_id = '$w_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($warehouse_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库促销价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_promote_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = intval($_POST['id']);
            $warehouse_promote_price = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_goods') . " set warehouse_promote_price = '$warehouse_promote_price' where w_id = '$w_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($warehouse_promote_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库赠送消费积分数 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_give_integral') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = intval($_POST['id']);
            $give_integral = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_goods') . " set give_integral = '$give_integral' where w_id = '$w_id' ";
            $res = $this->db->query($sql);

            $other = ['w_id', 'user_id', 'warehouse_price', 'warehouse_promote_price'];
            $goods = get_table_date('warehouse_goods', "w_id='$w_id'", $other);
            $goods['user_id'] = !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];

            if ($goods['warehouse_promote_price']) {
                if ($goods['warehouse_promote_price'] < $goods['warehouse_price']) {
                    $shop_price = $goods['warehouse_promote_price'];
                } else {
                    $shop_price = $goods['warehouse_price'];
                }
            } else {
                $shop_price = $goods['warehouse_price'];
            }

            $grade_rank = get_seller_grade_rank($goods['user_id']);
            $give = floor($shop_price * $grade_rank['give_integral']);

            if ($give_integral > $give) {
                return make_json_error(sprintf($GLOBALS['_LANG']['goods_give_integral'], $give));
            }

            if ($res) {
                clear_cache_files();
                return make_json_result($give_integral);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库赠送等级积分数 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_rank_integral') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = intval($_POST['id']);
            $rank_integral = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_goods') . " set rank_integral = '$rank_integral' where w_id = '$w_id' ";
            $res = $this->db->query($sql);

            $other = ['w_id', 'user_id', 'warehouse_price', 'warehouse_promote_price'];
            $goods = get_table_date('warehouse_goods', "w_id='$w_id'", $other);
            $goods['user_id'] = !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];

            if ($goods['warehouse_promote_price']) {
                if ($goods['warehouse_promote_price'] < $goods['warehouse_price']) {
                    $shop_price = $goods['warehouse_promote_price'];
                } else {
                    $shop_price = $goods['warehouse_price'];
                }
            } else {
                $shop_price = $goods['warehouse_price'];
            }

            $grade_rank = get_seller_grade_rank($goods['user_id']);
            $rank = floor($shop_price * $grade_rank['rank_integral']);

            if ($rank_integral > $rank) {
                return make_json_error(sprintf($GLOBALS['_LANG']['goods_rank_integral'], $rank));
            }

            if ($res) {
                clear_cache_files();
                return make_json_result($rank_integral);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库积分购买金额 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_pay_integral') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $w_id = intval($_POST['id']);
            $pay_integral = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_goods') . " set pay_integral = '$pay_integral' where w_id = '$w_id' ";
            $res = $this->db->query($sql);

            $other = ['w_id', 'user_id', 'warehouse_price', 'warehouse_promote_price'];
            $goods = get_table_date('warehouse_goods', "w_id='$w_id'", $other);
            $goods['user_id'] = !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];

            if ($goods['warehouse_promote_price']) {
                if ($goods['warehouse_promote_price'] < $goods['warehouse_price']) {
                    $shop_price = $goods['warehouse_promote_price'];
                } else {
                    $shop_price = $goods['warehouse_price'];
                }
            } else {
                $shop_price = $goods['warehouse_price'];
            }

            $grade_rank = get_seller_grade_rank($goods['user_id']);
            $pay = floor($shop_price * $grade_rank['pay_integral']);

            if ($pay_integral > $pay) {
                return make_json_error(sprintf($GLOBALS['_LANG']['goods_pay_integral'], $pay));
            }

            if ($res) {
                clear_cache_files();
                return make_json_result($pay_integral);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区编号 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $region_sn = addslashes(trim($_POST['val']));

            $sql = "update " . $this->dsc->table('warehouse_area_goods') . " set region_sn = '$region_sn' where a_id = '$a_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($region_sn);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除仓库地区价格 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_warehouse_area') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = empty($_REQUEST['a_id']) ? 0 : intval($_REQUEST['a_id']);

            /* 删除数据 */
            $sql = "DELETE FROM " . $this->dsc->table('warehouse_area_goods') . " WHERE a_id = '$a_id' LIMIT 1";
            $this->db->query($sql);

            clear_cache_files();
            return make_json_result($a_id);
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $region_price = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_area_goods') . " set region_price = '$region_price' where a_id = '$a_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($region_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区库存 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_number') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $region_number = floatval($_POST['val']);

            $sql = "SELECT goods_id, region_number, region_id, city_id FROM " . $this->dsc->table("warehouse_area_goods") . " WHERE a_id = '$a_id' LIMIT 1";
            $area_goods = $this->db->getRow($sql);

            $goodsInfo = get_admin_goods_info($area_goods['goods_id']);

            //库存日志
            if ($region_number != $area_goods['region_number']) {
                if ($region_number > $area_goods['region_number']) {
                    $number = $region_number - $area_goods['region_number'];
                    $number = "+ " . $number;
                    $use_storage = 13;
                } else {
                    $number = $area_goods['region_number'] - $region_number;
                    $number = "- " . $number;
                    $use_storage = 8;
                }

                $logs_other = [
                    'goods_id' => $area_goods['goods_id'],
                    'order_id' => 0,
                    'use_storage' => $use_storage,
                    'admin_id' => session('seller_id'),
                    'number' => $number,
                    'model_inventory' => $goodsInfo['model_inventory'],
                    'model_attr' => $goodsInfo['model_attr'],
                    'area_id' => $area_goods['region_id'],
                    'city_id' => $area_goods['city_id'],
                    'add_time' => gmtime()
                ];

                $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
            }

            $sql = "UPDATE " . $this->dsc->table('warehouse_area_goods') . " SET region_number = '$region_number' WHERE a_id = '$a_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($region_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区促销价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_promote_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $region_promote_price = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_area_goods') . " set region_promote_price = '$region_promote_price' where a_id = '$a_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($region_promote_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 查询该仓库的地区列表 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_area_list') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $key = isset($_REQUEST['key']) ? intval($_REQUEST['key']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $ru_id = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 1;

            if ($id > 0) {
                $area_list = get_warehouse_area_list($id, $type, $goods_id, $ru_id);
                $this->smarty->assign('area_list', $area_list);
                $this->smarty->assign('warehouse_id', $id);
                $this->smarty->assign('akey', $key);
                $this->smarty->assign('goods_id', $goods_id);
                $this->smarty->assign('user_id', $ru_id);
                $this->smarty->assign('type', $type);
                $this->smarty->assign('area_pricetype', $GLOBALS['_CFG']['area_pricetype']);

                $result['error'] = 0;
                $result['key'] = $key;
                $result['html'] = $this->smarty->fetch('library/warehouse_area_list.lbi');
            } else {
                $result['key'] = $key;
                $result['error'] = 1;
            }

            return make_json_result($result);
        }

        /* ------------------------------------------------------ */
        //-- 查询该仓库的地区列表 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warehouse_area_city') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $key = isset($_REQUEST['key']) ? intval($_REQUEST['key']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $ru_id = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 1;

            if ($id > 0) {
                $area_list = get_warehouse_area_list($id, $type, $goods_id, $ru_id);
                $this->smarty->assign('area_list', $area_list);
                $this->smarty->assign('warehouse_id', $id);
                $this->smarty->assign('key', $key);
                $this->smarty->assign('goods_id', $goods_id);
                $this->smarty->assign('user_id', $ru_id);
                $this->smarty->assign('type', $type);

                $result['error'] = 0;
                $result['key'] = $key;
                $result['html'] = $this->smarty->fetch('library/warehouse_area_city.lbi');
            } else {
                $result['key'] = $key;
                $result['error'] = 1;
            }

            return make_json_result($result);
        }

        /* ------------------------------------------------------ */
        //-- 查询该仓库的地区列表 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'city_region') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $area_id = isset($_REQUEST['area_id']) && !empty($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
            $city_id = isset($_REQUEST['city_id']) && !empty($_REQUEST['city_id']) ? intval($_REQUEST['city_id']) : 0;
            $onload = isset($_REQUEST['onload']) && !empty($_REQUEST['onload']) ? intval($_REQUEST['onload']) : 0;

            if ($area_id > 0) {
                $sql = "select region_id, region_name from " . $GLOBALS['dsc']->table('region_warehouse') . " where parent_id = '$area_id'";
                $city_list = $GLOBALS['db']->getAll($sql);
                $this->smarty->assign('city_list', $city_list);

                $this->smarty->assign('city_id', $city_id);

                $result['error'] = 0;
                $result['city_id'] = $city_list ? $city_list[0]['region_id'] : 0;

                $this->smarty->assign('area_id', $area_id);
                $this->smarty->assign('onload', $onload);
                $result['html'] = $this->smarty->fetch('library/goods_city_list.lbi');
            } else {
                $result['city_id'] = 0;
                $result['error'] = 1;
            }

            $result['area_id'] = $area_id;

            return make_json_result($result);
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区赠送消费积分数 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_give_integral') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $give_integral = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_area_goods') . " set give_integral = '$give_integral' where a_id = '$a_id' ";
            $res = $this->db->query($sql);

            $other = ['a_id', 'user_id', 'region_price', 'region_promote_price'];
            $goods = get_table_date('warehouse_area_goods', "a_id='$a_id'", $other);
            $goods['user_id'] = !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];

            if ($goods['region_promote_price']) {
                if ($goods['region_promote_price'] < $goods['region_price']) {
                    $shop_price = $goods['region_promote_price'];
                } else {
                    $shop_price = $goods['region_price'];
                }
            } else {
                $shop_price = $goods['region_price'];
            }

            $grade_rank = get_seller_grade_rank($goods['user_id']);
            $give = floor($shop_price * $grade_rank['give_integral']);

            if ($give_integral > $give) {
                return make_json_error(sprintf($GLOBALS['_LANG']['goods_give_integral'], $give));
            }

            if ($res) {
                clear_cache_files();
                return make_json_result($give_integral);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区赠送等级积分数 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_rank_integral') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $rank_integral = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_area_goods') . " set rank_integral = '$rank_integral' where a_id = '$a_id' ";
            $res = $this->db->query($sql);

            $other = ['a_id', 'user_id', 'region_price', 'region_promote_price'];
            $goods = get_table_date('warehouse_area_goods', "a_id='$a_id'", $other);
            $goods['user_id'] = !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];

            if ($goods['region_promote_price']) {
                if ($goods['region_promote_price'] < $goods['region_price']) {
                    $shop_price = $goods['region_promote_price'];
                } else {
                    $shop_price = $goods['region_price'];
                }
            } else {
                $shop_price = $goods['region_price'];
            }

            $grade_rank = get_seller_grade_rank($goods['user_id']);
            $rank = floor($shop_price * $grade_rank['rank_integral']);

            if ($rank_integral > $rank) {
                return make_json_error(sprintf($GLOBALS['_LANG']['goods_rank_integral'], $rank));
            }

            if ($res) {
                clear_cache_files();
                return make_json_result($rank_integral);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区积分购买金额 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_pay_integral') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $pay_integral = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_area_goods') . " set pay_integral = '$pay_integral' where a_id = '$a_id' ";
            $res = $this->db->query($sql);

            $other = ['a_id', 'user_id', 'region_price', 'region_promote_price'];
            $goods = get_table_date('warehouse_area_goods', "a_id='$a_id'", $other);
            $goods['user_id'] = !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];

            if ($goods['region_promote_price']) {
                if ($goods['region_promote_price'] < $goods['region_price']) {
                    $shop_price = $goods['region_promote_price'];
                } else {
                    $shop_price = $goods['region_price'];
                }
            } else {
                $shop_price = $goods['region_price'];
            }

            $grade_rank = get_seller_grade_rank($goods['user_id']);
            $pay = floor($shop_price * $grade_rank['pay_integral']);

            if ($pay_integral > $pay) {
                return make_json_error(sprintf($GLOBALS['_LANG']['goods_pay_integral'], $pay));
            }

            if ($res) {
                clear_cache_files();
                return make_json_result($pay_integral);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品仓库地区排序 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_region_sort') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $a_id = intval($_POST['id']);
            $region_sort = floatval($_POST['val']);

            $sql = "update " . $this->dsc->table('warehouse_area_goods') . " set region_sort = '$region_sort' where a_id = '$a_id' ";
            $res = $this->db->query($sql);

            if ($res) {
                clear_cache_files();
                return make_json_result($region_sort);
            }
        }

        /*------------------------------------------------------ */
        //-- 添加地区属性价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_area_price') {
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '02_goods_add']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['area_spec_price']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $action_link = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $GLOBALS['_LANG']['goods_info']];

            $goods_attr_id = get_goods_attr_nameId($goods_id, $attr_id, $goods_attr_name); //获取商品的属性ID

            $goods_date = ['goods_name'];
            $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);

            $attr_date = ['attr_name'];
            $attr_info = get_table_date('attribute', "attr_id = '$attr_id'", $attr_date);

            $warehouse_area_list = get_fine_warehouse_area_all(0, $goods_id, $goods_attr_id);

            $this->smarty->assign('goods_info', $goods_info);
            $this->smarty->assign('attr_info', $attr_info);
            $this->smarty->assign('goods_attr_name', $goods_attr_name);
            $this->smarty->assign('warehouse_area_list', $warehouse_area_list);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('goods_attr_id', $goods_attr_id);
            $this->smarty->assign('form_action', 'insert_area_price');
            $this->smarty->assign('action_link', $action_link);

            /* 显示属性地区价格信息页面 */

            return $this->smarty->display('goods_area_price_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 添加地区属性价格 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_area_price') {
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $area_name = isset($_REQUEST['area_name']) ? $_REQUEST['area_name'] : [];
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';

            get_warehouse_area_attr_price_insert($area_name, $goods_id, $goods_attr_id, 'warehouse_area_attr');

            $link[] = ['href' => 'javascript:history.back(-1)', 'text' => $GLOBALS['_LANG']['go_back']]; //by wu
            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 1, $link);
        }

        /*------------------------------------------------------ */
        //-- 添加仓库属性价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_warehouse_price') {
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $action_link = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $GLOBALS['_LANG']['goods_info']];

            $goods_attr_id = get_goods_attr_nameId($goods_id, $attr_id, $goods_attr_name); //获取商品的属性ID

            $goods_date = ['goods_name'];
            $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);

            $attr_date = ['attr_name'];
            $attr_info = get_table_date('attribute', "attr_id = '$attr_id'", $attr_date);

            $warehouse_area_list = get_fine_warehouse_all(0, $goods_id, $goods_attr_id);

            $this->smarty->assign('goods_info', $goods_info);
            $this->smarty->assign('attr_info', $attr_info);
            $this->smarty->assign('goods_attr_name', $goods_attr_name);
            $this->smarty->assign('warehouse_area_list', $warehouse_area_list);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('goods_attr_id', $goods_attr_id);
            $this->smarty->assign('form_action', 'insert_warehouse_price');
            $this->smarty->assign('action_link', $action_link);

            /* 显示属性地区价格信息页面 */

            return make_json_result($this->smarty->fetch('goods_warehouse_price_info.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加仓库属性价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_warehouse_price') {
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $warehouse_name = isset($_REQUEST['warehouse_name']) ? $_REQUEST['warehouse_name'] : [];
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';

            get_warehouse_area_attr_price_insert($warehouse_name, $goods_id, $goods_attr_id, 'warehouse_attr');

            $link[] = ['href' => 'javascript:history.back(-1)', 'text' => $GLOBALS['_LANG']['go_back']]; //by wu
            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 1, $link);
        }

        /*------------------------------------------------------ */
        //-- 添加属性图片 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_attr_img') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $action_link = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $GLOBALS['_LANG']['goods_info']];

            $goods_attr_id = get_goods_attr_nameId($goods_id, $attr_id, $goods_attr_name); //获取商品的属性ID

            $goods_date = ['goods_name'];
            $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);

            $goods_attr_date = ['attr_img_flie, attr_img_site, attr_checked, attr_gallery_flie'];
            $goods_attr_info = get_table_date('goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);

            $attr_date = ['attr_name'];
            $attr_info = get_table_date('attribute', "attr_id = '$attr_id'", $attr_date);

            $this->smarty->assign('goods_info', $goods_info);
            $this->smarty->assign('attr_info', $attr_info);
            $this->smarty->assign('goods_attr_info', $goods_attr_info);
            $this->smarty->assign('goods_attr_name', $goods_attr_name);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('goods_attr_id', $goods_attr_id);
            $this->smarty->assign('form_action', 'insert_attr_img');
            $this->smarty->assign('action_link', $action_link);

            return make_json_result($this->smarty->fetch('goods_attr_img_info.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加属性图片插�        �数据 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_attr_img') {
            admin_priv('goods_manage');

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';
            $img_url = !empty($_REQUEST['img_url']) ? $_REQUEST['img_url'] : '';


            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|JEPG|PNG|';

            $other['attr_img_flie'] = get_upload_pic('attr_img_flie');

            $this->dscRepository->getOssAddFile([$other['attr_img_flie']]);

            $goods_attr_date = ['attr_img_flie, attr_img_site'];
            $goods_attr_info = get_table_date('goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);

            if (empty($other['attr_img_flie'])) {
                $other['attr_img_flie'] = $goods_attr_info['attr_img_flie'];
            }

            $other['attr_img_site'] = !empty($_REQUEST['attr_img_site']) ? $_REQUEST['attr_img_site'] : '';
            $other['attr_checked'] = !empty($_REQUEST['attr_checked']) ? intval($_REQUEST['attr_checked']) : 0;
            $other['attr_gallery_flie'] = $img_url;

            $this->db->autoExecute($this->dsc->table('goods_attr'), $other, 'UPDATE', 'goods_attr_id = ' . $goods_attr_id . ' and attr_id = ' . $attr_id . ' and goods_id = ' . $goods_id);

            $link[0] = ['text' => lang('seller/common.goto_goods'), 'href' => "goods.php?act=edit&goods_id=" . $goods_id . "&extension_code=&properties=1"];
            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除属性图片 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_attr_img') {
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_name = isset($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $sql = "select attr_img_flie from " . $this->dsc->table('goods_attr') . " where goods_attr_id = '$goods_attr_id'";
            $attr_img_flie = $this->db->getOne($sql);

            $this->dscRepository->getOssDelFile([$attr_img_flie]);

            @unlink(storage_public($attr_img_flie));
            $other['attr_img_flie'] = '';
            $this->db->autoExecute($this->dsc->table('goods_attr'), $other, "UPDATE", "goods_attr_id = '$goods_attr_id'");

            $link[0] = ['text' => lang('seller/common.goto_goods'), 'href' => "goods.php?act=edit&goods_id=" . $goods_id . "&extension_code="];
            return sys_msg($GLOBALS['_LANG']['drop_attr_img_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 选择属性图片 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'choose_attrImg') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_attr_id = empty($_REQUEST['goods_attr_id']) ? 0 : intval($_REQUEST['goods_attr_id']);
            $on_img_id = isset($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : 0;

            $sql = "SELECT attr_gallery_flie FROM " . $this->dsc->table('goods_attr') . " WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
            $attr_gallery_flie = $this->db->getOne($sql);

            /* 删除数据 */
            $sql = "SELECT img_id, thumb_url, img_url FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id'";
            $img_list = $this->db->getAll($sql);

            $result = "<ul>";
            foreach ($img_list as $idx => $row) {
                if ($attr_gallery_flie == $row['img_url']) {
                    $result .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')" class="on"><img src="../' . $row['thumb_url'] . '" width="120" /><i><img src="images/gallery_yes.png" width="30" height="30"></i></li>';
                } else {
                    $result .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')"><img src="../' . $row['thumb_url'] . '" width="120" /><i><img src="images/gallery_yes.png" width="30" height="30"></i></li>';
                }
            }
            $result .= "</ul>";

            clear_cache_files();
            return make_json_result($result);
        }

        /*------------------------------------------------------ */
        //-- 选择属性图片 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_gallery_attr') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['goods_id']);
            $goods_attr_id = intval($_REQUEST['goods_attr_id']);
            $gallery_id = intval($_REQUEST['gallery_id']);

            if (!empty($gallery_id)) {
                $sql = "SELECT img_id, img_url FROM " . $this->dsc->table('goods_gallery') . "WHERE img_id='$gallery_id'";
                $img = $this->db->getRow($sql);
                $result = $img['img_id'];

                $sql = "UPDATE " . $this->dsc->table('goods_attr') . " SET attr_gallery_flie = '" . $img['img_url'] . "' WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
                $this->db->query($sql);
            } else {
                return make_json_error(lang('seller/goods.empty_image'));
            }

            return make_json_result($result, '', ['img_url' => $img['img_url']]);
        }

        /*------------------------------------------------------ */
        //-- 搜索商品，仅        返回名称及ID
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_goods_list') {

            $filters = dsc_decode($_GET['JSON']);

            $arr = get_goods_list($filters);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => $val['shop_price']];
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索区域地区，仅        返回名称及ID  //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_area_list') {

            $filters = dsc_decode($_GET['JSON']);

            $arr = $this->commonManageService->getAreaRegionInfoList($filters->ra_id);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['region_id'],
                    'text' => $val['region_name'],
                    'data' => 0];
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 把商品加�        ��        �联
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_link_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $linked_array = dsc_decode($_GET['add_ids'], true);
            $linked_goods = dsc_decode($_GET['JSON'], true);
            $goods_id = $linked_goods[0];
            $is_double = $linked_goods[1] == true ? 0 : 1;

            foreach ($linked_array as $val) {
                if ($is_double) {
                    /* 双向关联 */
                    $sql = "INSERT INTO " . $this->dsc->table('link_goods') . " (goods_id, link_goods_id, is_double, admin_id) " .
                        "VALUES ('$val', '$goods_id', '$is_double', '" . session('seller_id') . "')";
                    $this->db->query($sql, 'SILENT');
                }

                $sql = "INSERT INTO " . $this->dsc->table('link_goods') . " (goods_id, link_goods_id, is_double, admin_id) " .
                    "VALUES ('$goods_id', '$val', '$is_double', '" . session('seller_id') . "')";
                $this->db->query($sql, 'SILENT');
            }

            $linked_goods = get_linked_goods($goods_id);
            $options = [];

            foreach ($linked_goods as $val) {
                $options[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($options);
        }

        /*------------------------------------------------------ */
        //-- 删除�        �联商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_link_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = dsc_decode($_GET['drop_ids'], true);
            $drop_goods_ids = db_create_in($drop_goods);
            $linked_goods = dsc_decode($_GET['JSON'], true);
            $goods_id = $linked_goods[0];
            $is_signle = $linked_goods[1];

            if (!$is_signle) {
                $sql = "DELETE FROM " . $this->dsc->table('link_goods') .
                    " WHERE link_goods_id = '$goods_id' AND goods_id " . $drop_goods_ids;
            } else {
                $sql = "UPDATE " . $this->dsc->table('link_goods') . " SET is_double = 0 " .
                    " WHERE link_goods_id = '$goods_id' AND goods_id " . $drop_goods_ids;
            }
            if ($goods_id == 0) {
                $sql .= " AND admin_id = '" . session('seller_id') . "'";
            }
            $this->db->query($sql);

            $sql = "DELETE FROM " . $this->dsc->table('link_goods') .
                " WHERE goods_id = '$goods_id' AND link_goods_id " . $drop_goods_ids;
            if ($goods_id == 0) {
                $sql .= " AND admin_id = '" . session('seller_id') . "'";
            }
            $this->db->query($sql);

            $linked_goods = get_linked_goods($goods_id);
            $options = [];

            foreach ($linked_goods as $val) {
                $options[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($options);
        }

        /*------------------------------------------------------ */
        //-- 增加一个�        �件
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_group_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $fittings = dsc_decode($_GET['add_ids'], true);
            $arguments = dsc_decode($_GET['JSON'], true);
            $goods_id = $arguments[0];
            $price = $arguments[1];
            $group_id = $arguments[2];//by mike add

            $sql = "select count(*) from " . $this->dsc->table('group_goods') . " where parent_id = '$goods_id' and group_id = '$group_id' and admin_id = '" . session('seller_id') . "'";
            $groupCount = $this->db->getOne($sql);

            $message = "";
            if ($groupCount < 1000) {
                foreach ($fittings as $val) {
                    $sql = "SELECT id FROM " . $this->dsc->table('group_goods') . " WHERE parent_id = '$goods_id' AND goods_id = '$val' AND group_id = '$group_id'";
                    if (!$this->db->getOne($sql)) {
                        $sql = "INSERT INTO " . $this->dsc->table('group_goods') . " (parent_id, goods_id, goods_price, admin_id, group_id) " .
                            "VALUES ('$goods_id', '$val', '$price', '" . session('seller_id') . "', '$group_id')";//by mike add
                        $this->db->query($sql, 'SILENT');
                    }
                }

                $error = 0;
            } else {
                $error = 1;
                $message = $GLOBALS['_LANG']['one_group_peijian_5_goods'];
            }

            $arr = get_group_goods($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => '[' . $val['group_name'] . ']' . $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($opt, $message, ['error' => $error]);
        }

        /*------------------------------------------------------ */
        //-- 删除一个�        �件
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'drop_group_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $fittings = dsc_decode($_GET['drop_ids'], true);
            $arguments = dsc_decode($_GET['JSON'], true);
            $goods_id = $arguments[0];
            $price = $arguments[1];

            $sql = "DELETE FROM " . $this->dsc->table('group_goods') .
                " WHERE parent_id='$goods_id' AND " . db_create_in($fittings, 'goods_id');
            if ($goods_id == 0) {
                $sql .= " AND admin_id = '" . session('seller_id') . "'";
            }
            $this->db->query($sql);

            $arr = get_group_goods($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => '[' . $val['group_name'] . ']' . $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 增加一个�        �联地区 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_area_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $fittings = dsc_decode($_GET['add_ids'], true);
            $arguments = dsc_decode($_GET['JSON'], true);
            $goods_id = $arguments[0];
            $region_id = $arguments[1];

            $sql = "SELECT user_id FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
            $ru_id = $this->db->getOne($sql);

            foreach ($fittings as $val) {
                $sql = "INSERT INTO " . $this->dsc->table('link_area_goods') . " (goods_id, region_id, ru_id) " .
                    "VALUES ('$goods_id', '$val', '$ru_id')";
                $this->db->query($sql, 'SILENT');
            }

            $arr = get_area_goods($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['region_id'],
                    'text' => $val['region_name'],
                    'data' => 0];
            }

            clear_cache_files();
            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 删除一个�        �联地区 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'drop_area_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = dsc_decode($_GET['drop_ids'], true);
            $drop_goods_ids = db_create_in($drop_goods);

            $arguments = dsc_decode($_GET['JSON'], true);
            $goods_id = $arguments[0];
            $region_id = $arguments[1];

            $sql = "DELETE FROM " . $this->dsc->table('link_area_goods') . " WHERE region_id" . $drop_goods_ids . " and goods_id = '$goods_id'";
            if ($goods_id == 0) {
                $sql .= " AND ru_id = " . $adminru['ru_id'];
            }
            $this->db->query($sql);

            $arr = get_area_goods($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['region_id'],
                    'text' => $val['region_name'],
                    'data' => 0];
            }

            clear_cache_files();
            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索文章
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'get_article_list') {

            $filters = (array)dsc_decode(json_str_iconv($_GET['JSON']), true);

            $where = " WHERE cat_id > 0 ";
            if (!empty($filters['title'])) {
                $keyword = trim($filters['title']);
                $where .= " AND title LIKE '%" . mysql_like_quote($keyword) . "%' ";
            }

            $sql = 'SELECT article_id, title FROM ' . $this->dsc->table('article') . $where .
                'ORDER BY article_id DESC LIMIT 50';
            $res = $this->db->query($sql);
            $arr = [];

            foreach ($res as $row) {
                $arr[] = ['value' => $row['article_id'], 'text' => $row['title'], 'data' => ''];
            }

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 添加�        �联文章
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_goods_article') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $articles = dsc_decode($_GET['add_ids'], true);
            $arguments = dsc_decode($_GET['JSON'], true);
            $goods_id = $arguments[0];

            foreach ($articles as $val) {
                $sql = "INSERT INTO " . $this->dsc->table('goods_article') . " (goods_id, article_id, admin_id) " .
                    "VALUES ('$goods_id', '$val', '" . session('seller_id') . "')";
                $this->db->query($sql);
            }

            $arr = get_goods_articles($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['article_id'],
                    'text' => $val['title'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 删除�        �联文章
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_goods_article') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $articles = dsc_decode($_GET['drop_ids'], true);
            $arguments = dsc_decode($_GET['JSON'], true);
            $goods_id = $arguments[0];

            $sql = "DELETE FROM " . $this->dsc->table('goods_article') . " WHERE " . db_create_in($articles, "article_id") . " AND goods_id = '$goods_id'";
            $this->db->query($sql);

            $arr = get_goods_articles($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['article_id'],
                    'text' => $val['title'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 货品列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_list') {
            admin_priv('goods_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);

            /* 是否存在商品id */
            if (empty($_GET['goods_id'])) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['cannot_found_goods']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            } else {
                $goods_id = intval($_GET['goods_id']);
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price, model_attr FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
            $goods = $this->db->getRow($sql);
            if (empty($goods)) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            }
            $this->smarty->assign('sn', sprintf($GLOBALS['_LANG']['good_goods_sn'], $goods['goods_sn']));
            $this->smarty->assign('price', sprintf($GLOBALS['_LANG']['good_shop_price'], $goods['shop_price']));
            $this->smarty->assign('goods_name', sprintf($GLOBALS['_LANG']['products_title'], $goods['goods_name']));
            $this->smarty->assign('goods_sn', sprintf($GLOBALS['_LANG']['products_title_2'], $goods['goods_sn']));
            $this->smarty->assign('model_attr', $goods['model_attr']);


            /* 获取商品规格列表 */
            $attribute = get_goods_specifications_list($goods_id);
            if (empty($attribute)) {
                $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['edit_goods']];
                return sys_msg($GLOBALS['_LANG']['not_exist_goods_attr'], 1, $link);
            }
            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }
            $attribute_count = count($_attribute);

            $this->smarty->assign('attribute_count', $attribute_count);
            $this->smarty->assign('attribute_count_5', ($attribute_count + 5));
            $this->smarty->assign('attribute', $_attribute);
            $this->smarty->assign('product_sn', $goods['goods_sn'] . '_');
            $this->smarty->assign('product_number', $GLOBALS['_CFG']['default_storage']);

            /* 取商品的货品 */
            $product = product_list($goods_id, '');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
            $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
            $this->smarty->assign('product_list', $product['product']);
            $this->smarty->assign('product_null', empty($product['product']) ? 0 : 1);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('filter', $product['filter']);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('product_php', 'goods.php');

            /* 显示商品列表页面 */


            return $this->smarty->display('product_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 货品排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_query') {
            /* 是否存在商品id */
            if (empty($_REQUEST['goods_id'])) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            } else {
                $goods_id = intval($_REQUEST['goods_id']);
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
            $goods = $this->db->getRow($sql);
            if (empty($goods)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            }
            $this->smarty->assign('sn', sprintf($GLOBALS['_LANG']['good_goods_sn'], $goods['goods_sn']));
            $this->smarty->assign('price', sprintf($GLOBALS['_LANG']['good_shop_price'], $goods['shop_price']));
            $this->smarty->assign('goods_name', sprintf($GLOBALS['_LANG']['products_title'], $goods['goods_name']));
            $this->smarty->assign('goods_sn', sprintf($GLOBALS['_LANG']['products_title_2'], $goods['goods_sn']));


            /* 获取商品规格列表 */
            $attribute = get_goods_specifications_list($goods_id);
            if (empty($attribute)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            }
            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }
            $attribute_count = count($_attribute);

            $this->smarty->assign('attribute_count', $attribute_count);
            $this->smarty->assign('attribute', $_attribute);
            $this->smarty->assign('attribute_count_3', ($attribute_count + 10));
            $this->smarty->assign('product_sn', $goods['goods_sn'] . '_');
            $this->smarty->assign('product_number', $GLOBALS['_CFG']['default_storage']);

            /* 取商品的货品 */
            $product = product_list($goods_id, '');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
            $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
            $this->smarty->assign('product_list', $product['product']);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('filter', $product['filter']);

            $this->smarty->assign('product_php', 'goods.php');

            /* 排序标记 */
            $sort_flag = sort_flag($product['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('product_info.dwt'),
                '',
                ['filter' => $product['filter'], 'page_count' => $product['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 货品删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('remove_back');
            if ($check_auth !== true) {
                return $check_auth;
            }

            //ecmoban模板堂 --zhuo satrt
            $id_val = $_REQUEST['id'];
            $id_val = explode(',', $id_val);
            $product_id = intval($id_val[0]);
            $warehouse_id = intval($id_val[1]);
            //ecmoban模板堂 --zhuo end

            /* 是否存在商品id */
            if (empty($product_id)) {
                return make_json_error($GLOBALS['_LANG']['product_id_null']);
            } else {
                $product_id = intval($product_id);
            }

            /* 货品库存 */
            $product = get_product_info($product_id, 'product_number, goods_id');

            /* 删除货品 */
            $result = Products::where('product_id', $product_id)->delete();
            if ($result) {
                $url = 'goods.php?act=product_query&' . str_replace('act=product_remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 修改货品号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);

            $product_sn = json_str_iconv(trim($_POST['val']));
            $product_sn = ($GLOBALS['_LANG']['n_a'] == $product_sn) ? '' : $product_sn;
            $goods_model = isset($_REQUEST['goods_model']) && !empty($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;
            $warehouse_id = isset($_REQUEST['warehouse_id']) && !empty($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;
            $area_id = isset($_REQUEST['area_id']) && !empty($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
            $area_city = isset($_REQUEST['area_city']) && !empty($_REQUEST['area_city']) ? intval($_REQUEST['area_city']) : 0;

            if (check_product_sn_exist($product_sn, $product_id, $adminru['ru_id'], $goods_model, $warehouse_id, $area_id, $area_city)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_product_sn']);
            }

            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;

            if ($changelog == 1) {
                $res = ProductsChangelog::whereRaw(1);
            } else {
                if ($goods_model == 1) {
                    $res = ProductsWarehouse::whereRaw(1);
                } elseif ($goods_model == 2) {
                    $res = ProductsArea::whereRaw(1);
                } else {
                    $res = Products::whereRaw(1);
                }
            }

            /* 修改 */
            $data = ['product_sn' => $product_sn];
            $result = $res->where('product_id', $product_id)->update($data);

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
                    return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_bar_code']);
                }

                $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;

                if ($changelog == 1) {
                    $res = ProductsChangelog::whereRaw(1);
                } else {
                    if ($goods_model == 1) {
                        $res = ProductsWarehouse::whereRaw(1);
                    } elseif ($goods_model == 2) {
                        $res = ProductsArea::whereRaw(1);
                    } else {
                        $res = Products::whereRaw(1);
                    }
                }

                /* 修改 */
                $data = ['bar_code' => $bar_code];
                $result = $res->where('product_id', $product_id)->update($data);

                if ($result) {
                    clear_cache_files();
                    return make_json_result($bar_code);
                }
            } else {
                clear_cache_files();
                return make_json_result('N/A');
            }
        }

        /*------------------------------------------------------ */
        //-- 修改属性价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_attr_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_attr_id = intval($_REQUEST['id']);
            $attr_price = floatval($_POST['val']);

            /* 修改 */
            $data = ['attr_price' => $attr_price];
            $result = GoodsAttr::where('goods_attr_id', $goods_attr_id)->update($data);
            if ($result) {
                clear_cache_files();
                return make_json_result($attr_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改条形码
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_bar_code') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $bar_code = json_str_iconv(trim($_POST['val']));

            if (check_product_sn_exist($bar_code, $product_id, $adminru['ru_id'], 1)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_bar_code']);
            }

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('products') . " SET bar_code = '$bar_code' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($bar_code);
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
            $goods_model = isset($_REQUEST['goods_model']) && !empty($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;

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
                $logs_other = [
                    'goods_id' => $product['goods_id'],
                    'order_id' => 0,
                    'use_storage' => $log_use_storage,
                    'admin_id' => session('seller_id'),
                    'number' => $number,
                    'model_inventory' => $goods['model_inventory'],
                    'model_attr' => $goods['model_attr'],
                    'product_id' => $product_id,
                    'warehouse_id' => $product['warehouse_id'] ?? 0,
                    'area_id' => $product['area_id'] ?? 0,
                    'city_id' => $product['city_id'] ?? 0,
                    'add_time' => gmtime()
                ];

                $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
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
        //-- 批量修改货品数据
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'synchronization_attr') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['content' => '', 'error' => 0];

            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_model = isset($_REQUEST['model']) ? intval($_REQUEST['model']) : 0;
            $warehouse_id = isset($_REQUEST['warehouse_id']) && !empty($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;
            $area_id = isset($_REQUEST['area_id']) && !empty($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
            $area_city = isset($_REQUEST['area_city']) && !empty($_REQUEST['area_city']) ? intval($_REQUEST['area_city']) : 0;
            $changelog = !empty($_REQUEST['changelog']) ? intval($_REQUEST['changelog']) : 0;
            $field = !empty($_REQUEST['field']) ? trim($_REQUEST['field']) : '';
            $val = !empty($_REQUEST['val']) ? intval($_REQUEST['val']) : 0;
            $obj_arr = ['product_market_price', 'product_price', 'product_promote_price'];
            if (in_array($field, $obj_arr)) {
                $val = floatval($_REQUEST['val']);
            }

            if ($field) {
                $changelog_where = " WHERE 1 AND goods_id = '$goods_id'";

                if (empty($goods_id)) {
                    $changelog_where .= " AND admin_id = '$admin_id'";
                }

                if ($goods_model == 1) {
                    $changelog_where .= " AND warehouse_id = '$warehouse_id' ";
                } elseif ($goods_model == 2) {
                    $changelog_where .= " AND area_id = '$area_id' ";

                    if ($GLOBALS['_CFG']['area_pricetype']) {
                        $changelog_where .= " AND city_id = '$area_city'";
                    }
                }

                /* 修改临时表 */
                $sql = "UPDATE " . $this->dsc->table('products_changelog') . " SET $field = '$val'" . $changelog_where;
                $this->db->query($sql);

                $where = " WHERE 1 AND goods_id = '$goods_id'";

                if (empty($goods_id)) {
                    $where .= " AND admin_id = '$admin_id'";
                }

                if ($goods_model == 1) {
                    $where .= " AND warehouse_id = '$warehouse_id' ";
                    $table = "products_warehouse";
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                    $where .= " AND area_id = '$area_id' ";

                    if ($GLOBALS['_CFG']['area_pricetype']) {
                        $where .= " AND city_id = '$area_city'";
                    }
                } else {
                    $table = "products";
                }

                /* 修改 */
                $sql = "UPDATE " . $this->dsc->table($table) . " SET $field = '$val'" . $where;
                if ($this->db->query($sql)) {
                    clear_cache_files();
                }
            } else {
                $result['error'] = 1;
            }
            return response()->json($result);
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

                $goods_other = [
                    'product_table' => $table,
                    'product_price' => $product_price,
                ];
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

                $goods_other = [
                    'product_table' => $table,
                    'product_promote_price' => $promote_price,
                ];
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
        //-- 货品添加 执行
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_add_execute') {
            admin_priv('goods_manage');

            $product['goods_id'] = intval($_POST['goods_id']);
            $product['attr'] = $_POST['attr'];
            $product['product_sn'] = $_POST['product_sn'];
            $product['bar_code'] = $_POST['bar_code'];
            $product['product_price'] = $_POST['product_price'];
            $product['product_number'] = $_POST['product_number'];

            /* 是否存在商品id */
            if (empty($product['goods_id'])) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, [], false);
            }

            /* 判断是否为初次添加 */
            $insert = true;
            if (product_number_count($product['goods_id']) > 0) {
                $insert = false;
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price, model_inventory, model_attr FROM " . $this->dsc->table('goods') . " WHERE goods_id = '" . $product['goods_id'] . "'";
            $goods = $this->db->getRow($sql);
            if (empty($goods)) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, [], false);
            }

            /*  */
            foreach ($product['product_sn'] as $key => $value) {
                //过滤
                $product['product_number'][$key] = empty($product['product_number'][$key]) ? (empty($GLOBALS['_CFG']['use_storage']) ? 0 : $GLOBALS['_CFG']['default_storage']) : trim($product['product_number'][$key]); //库存

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
                $goods_attr_id = handle_goods_attr($product['goods_id'], $id_list, $is_spec_list, $value_price_list);

                /* 是否为重复规格的货品 */
                $goods_attr = $this->goodsAttrService->sortGoodsAttrIdArray($goods_attr_id);
                $goods_attr = implode('|', $goods_attr['sort']);
                if (check_goods_attr_exist($goods_attr, $product['goods_id'])) {
                    continue;
                }
                //货品号不为空
                if (!empty($value)) {
                    /* 检测：货品货号是否在商品表和货品表中重复 */
                    if (check_goods_sn_exist($value)) {
                        continue;
                    }
                    if (check_product_sn_exist($value)) {
                        continue;
                    }
                }

                /* 插入货品表 */
                $sql = "INSERT INTO " . $this->dsc->table('products') . " (goods_id, goods_attr, product_sn, bar_code, product_price, product_number)  VALUES ('" . $product['goods_id'] . "', '$goods_attr', '$value', '" . $product['bar_code'][$key] . "', '" . $product['product_price'][$key] . "', '" . $product['product_number'][$key] . "')";
                if (!$this->db->query($sql)) {
                    continue;
                }

                //库存日志
                $number = "+ " . $product['product_number'][$key];

                if ($product['product_number'][$key]) {
                    $logs_other = [
                        'goods_id' => $product['goods_id'],
                        'order_id' => 0,
                        'use_storage' => 9,
                        'admin_id' => session('seller_id'),
                        'number' => $number,
                        'model_inventory' => $goods['model_inventory'],
                        'model_attr' => $goods['model_attr'],
                        'product_id' => $this->db->insert_id(),
                        'add_time' => gmtime()
                    ];

                    $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                }

                //货品号为空 自动补货品号
                if (empty($value)) {
                    $sql = "UPDATE " . $this->dsc->table('products') . "
                    SET product_sn = '" . $goods['goods_sn'] . "g_p" . $this->db->insert_id() . "'
                    WHERE product_id = '" . $this->db->insert_id() . "'";
                    $this->db->query($sql);
                }

                /* 修改商品表库存 */
                $product_count = product_number_count($product['goods_id']);
                /*if (update_goods($product['goods_id'], 'goods_number', $product_count, '', 'updateNum'))
        {
            //记录日志
            admin_log($product['goods_id'], 'update', 'goods');
        }*/
            }

            clear_cache_files();

            /* 返回 */
            if ($insert) {
                $link[] = ['href' => 'goods.php?act=add', 'text' => $GLOBALS['_LANG']['02_goods_add']];
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                $link[] = ['href' => 'goods.php?act=product_list&goods_id=' . $product['goods_id'], 'text' => $GLOBALS['_LANG']['18_product_list']];
            } else {
                $link[] = ['href' => 'goods.php?act=list&uselastfilter=1', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $product['goods_id'], 'text' => $GLOBALS['_LANG']['edit_goods']];
                $link[] = ['href' => 'goods.php?act=product_list&goods_id=' . $product['goods_id'], 'text' => $GLOBALS['_LANG']['18_product_list']];
            }
            return sys_msg($GLOBALS['_LANG']['save_products'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 货品批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_product') {
            /* 定义返回 */
            $link[] = ['href' => 'goods.php?act=product_list&goods_id=' . $_POST['goods_id'], 'text' => $GLOBALS['_LANG']['item_list']];

            /* 批量操作 - 批量删除 */
            if ($_POST['type'] == 'drop') {
                //检查权限
                admin_priv('remove_back');

                //取得要操作的商品编号
                $product_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
                $product_bound = db_create_in($product_id);

                //取出货品库存总数
                $sum = 0;
                $goods_id = 0;
                $sql = "SELECT product_id, goods_id, product_number FROM  " . $this->dsc->table('products') . " WHERE product_id $product_bound";
                $product_array = $this->db->getAll($sql);
                if (!empty($product_array)) {
                    foreach ($product_array as $value) {
                        $sum += $value['product_number'];
                    }
                    $goods_id = $product_array[0]['goods_id'];

                    /* 删除货品 */
                    $sql = "DELETE FROM " . $this->dsc->table('products') . " WHERE product_id $product_bound";
                    if ($this->db->query($sql)) {
                        //记录日志
                        admin_log('', 'delete', 'products');
                    }

                    /* 修改商品库存 */
                    if (update_goods_stock($goods_id, -$sum)) {
                        //记录日志
                        admin_log('', 'update', 'goods');
                    }

                    /* 返回 */
                    return sys_msg($GLOBALS['_LANG']['product_batch_del_success'], 0, $link);
                } else {
                    /* 错误 */
                    return sys_msg($GLOBALS['_LANG']['cannot_found_products'], 1, $link);
                }
            }

            /* 返回 */
            return sys_msg($GLOBALS['_LANG']['no_operation'], 1, $link);
        } elseif ($_REQUEST['act'] == 'search_cat') {

            $keyword = !empty($_REQUEST['seacrch_key']) ? trim($_REQUEST['seacrch_key']) : '';
            $parent_id = !empty($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            $res = ['error' => 0, 'message' => ''];
            if (!empty($keyword)) {
                if ($adminru['ru_id'] == 0) {
                    $sql = "SELECT `cat_id`,`cat_name` FROM " . $this->dsc->table('category') . "WHERE `cat_name` like '%$keyword%' AND parent_id = '$parent_id'";
                    $options = $this->db->getAll($sql);
                } else {
                    $sql = "select user_shopMain_category from " . $this->dsc->table('merchants_shop_information') . " where user_id = '" . $adminru['ru_id'] . "'";
                    $shopMain_category = $this->db->getOne($sql);
                    $cat_ids = explode(',', get_category_child_tree($shopMain_category));
                    $sql = "SELECT `cat_id`,`cat_name` FROM " . $this->dsc->table('category') . "WHERE `cat_name` like '%$keyword%' and cat_id " . db_create_in($cat_ids) . " AND parent_id = '$parent_id'";
                    $options = $this->db->getAll($sql);
                }

                if ($options) {
                    foreach ($options as $key => $row) {
                        $options[0]['cat_id'] = 0;
                        $options[0]['cat_name'] = $GLOBALS['_LANG']['all_category'];
                        $key += 1;
                        $options[$key] = $row;
                    }
                } else {
                    $res['error'] = 1;
                    $res['message'] = $GLOBALS['_LANG']['no_search_cate'];
                }
            }

            $res['parent_id'] = $parent_id;
            $res['cat_level'] = $cat_level + 1;

            return make_json_result($options, '', $res);
        } // 选择分类 -by qin
        elseif ($_REQUEST['act'] == 'sel_cat') {

            $res = ['error' => 0, 'message' => '', 'cat_level' => 0, 'content' => ''];

            $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            if ($cat_id > 0) {
                $arr = $this->goodsManageService->catListOne($cat_id, $cat_level);
            }

            $res['content'] = $arr;
            $res['parent_id'] = $cat_id;
            $res['cat_level'] = $cat_level;
            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 添加或编辑商品 选择分类 -by qin
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sel_cat1') {

            $res = ['error' => 0, 'message' => '', 'cat_level' => 0, 'content' => ''];

            $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            if ($cat_id > 0) {
                $arr = $this->goodsManageService->catListOne($cat_id, $cat_level, [], 'newcat_list', 'newcatList');
            }

            $res['content'] = $arr;
            $res['parent_id'] = $cat_id;
            $res['cat_level'] = $cat_level;
            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- �        �联或�        �件 选择分类 -by qin
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sel_cat2') {

            $res = ['error' => 0, 'message' => '', 'cat_level' => 0, 'content' => ''];

            $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            if ($cat_id > 0) {
                $arr = $this->goodsManageService->catListOne($cat_id, $cat_level, [], 'new2cat_list', 'new2catList');
            }

            $res['content'] = $arr;
            $res['parent_id'] = $cat_id;
            $res['cat_level'] = $cat_level;
            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 商品批量修改 选择分类 -by qin
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sel_cat_edit') {

            $res = ['error' => 0, 'message' => '', 'cat_level' => 0, 'content' => ''];

            $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            if ($cat_id > 0) {
                $arr = $this->cat_list_one_new($cat_id, $cat_level, 'sel_cat_edit');
            }

            $res['content'] = $arr;
            $res['parent_id'] = $cat_id;
            $res['cat_level'] = $cat_level;
            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 图片批量处理 选择分类 -by qin
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sel_cat_picture') {

            $res = ['error' => 0, 'message' => '', 'cat_level' => 0, 'content' => ''];

            $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            if ($cat_id > 0) {
                $arr = $this->cat_list_one_new($cat_id, $cat_level, 'sel_cat_picture');
            }

            $res['content'] = $arr;
            $res['parent_id'] = $cat_id;
            $res['cat_level'] = $cat_level;
            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 图片批量处理 选择分类 -by qin
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sel_cat_goodslist') {

            $res = ['error' => 0, 'message' => '', 'cat_level' => 0, 'content' => ''];

            $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            if ($cat_id > 0) {
                $arr = $this->cat_list_one_new($cat_id, $cat_level, 'sel_cat_goodslist');
            }

            $res['content'] = $arr;
            $res['parent_id'] = $cat_id;
            $res['cat_level'] = $cat_level;
            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 修改属性排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_attr_sort') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_attr_id = intval($_REQUEST['id']);
            $attr_sort = intval($_POST['val']);

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('goods_attr') . " SET attr_sort = '$attr_sort' WHERE goods_attr_id = '$goods_attr_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($attr_sort);
            }
        }

        /*------------------------------------------------------ */
        //-- 单个添加商品仓库 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addWarehouse') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];
            $ware_name = !empty($_POST['ware_name']) ? $_POST['ware_name'] : '';
            $ware_number = !empty($_POST['ware_number']) ? intval($_POST['ware_number']) : 0;
            $ware_price = !empty($_POST['ware_price']) ? $_POST['ware_price'] : 0;
            $ware_price = floatval($ware_price);
            $ware_promote_price = !empty($_POST['ware_promote_price']) ? $_POST['ware_promote_price'] : 0;
            $ware_promote_price = floatval($ware_promote_price);
            $give_integral = !empty($_POST['give_integral']) ? intval($_POST['give_integral']) : 0;
            $rank_integral = !empty($_POST['rank_integral']) ? intval($_POST['rank_integral']) : 0;
            $pay_integral = !empty($_POST['pay_integral']) ? intval($_POST['pay_integral']) : 0;
            $goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;

            if (empty($ware_name)) {
                $result['error'] = '1';
                $result['massege'] = $GLOBALS['_LANG']['js_languages']['jl_select_warehouse'];
            } else {
                $sql = "select w_id from " . $this->dsc->table('warehouse_goods') . " where goods_id = '$goods_id' and region_id = '" . $ware_name . "' AND user_id = '$user_id'";
                $w_id = $this->db->getOne($sql);
                $add_time = gmtime();
                if ($w_id > 0) {
                    $result['error'] = '1';
                    $result['massege'] = $GLOBALS['_LANG']['this_goods_warehouse_stock_exist'];
                } else {
                    if ($ware_number == 0) {
                        $result['error'] = '1';
                        $result['massege'] = $GLOBALS['_LANG']['warehouse_stock_not_0'];
                    } elseif ($ware_price == 0) {
                        $result['error'] = '1';
                        $result['massege'] = $GLOBALS['_LANG']['warehouse_price_not_0'];
                    } else {
                        $goodsInfo = get_admin_goods_info($goods_id);
                        $goodsInfo['user_id'] = !empty($goodsInfo['user_id']) ? $goodsInfo['user_id'] : $adminru['ru_id'];

                        //库存日志
                        $number = "+ " . $ware_number;
                        $use_storage = 13;

                        $logs_other = [
                            'goods_id' => $goods_id,
                            'order_id' => 0,
                            'use_storage' => $use_storage,
                            'admin_id' => session('seller_id'),
                            'number' => $number,
                            'model_inventory' => $goodsInfo['model_inventory'],
                            'model_attr' => $goodsInfo['model_attr'],
                            'product_id' => 0,
                            'warehouse_id' => $ware_name,
                            'area_id' => 0,
                            'add_time' => $add_time
                        ];

                        $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');

                        $sql = "insert into " . $this->dsc->table('warehouse_goods') .
                            "(goods_id, region_id, region_number, warehouse_price, warehouse_promote_price, give_integral, rank_integral, pay_integral, user_id, add_time)VALUES('" .
                            $goods_id . "','" . $ware_name . "','" . $ware_number . "','" . $ware_price . "','" . $ware_promote_price . "','" . $give_integral . "','" . $rank_integral . "','" . $pay_integral . "','" . $goodsInfo['user_id'] . "','$add_time')";
                        if ($this->db->query($sql) == true) {
                            $result['error'] = '2';
                            $get_warehouse_goods_list = get_warehouse_goods_list($goods_id);
                            $warehouse_id = '';
                            if (!empty($get_warehouse_goods_list)) {
                                foreach ($get_warehouse_goods_list as $k => $v) {
                                    $warehouse_id .= $v['w_id'] . ",";
                                }
                            }
                            $warehouse_id = substr($warehouse_id, 0, strlen($warehouse_id) - 1);
                            $this->smarty->assign("warehouse_id", $warehouse_id);
                            $this->smarty->assign("warehouse_goods_list", $get_warehouse_goods_list);
                            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_warehouse.lbi');
                        }
                    }
                }
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 批量添加商品仓库 ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addBatchWarehouse') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];

            $ware_name = !empty($_POST['ware_name']) ? explode(',', $_POST['ware_name']) : [];
            $ware_number = !empty($_POST['ware_number']) ? explode(',', $_POST['ware_number']) : [0];
            $ware_price = !empty($_POST['ware_price']) ? explode(',', $_POST['ware_price']) : [0];
            $ware_promote_price = !empty($_POST['ware_promote_price']) ? explode(',', $_POST['ware_promote_price']) : [0];
            $give_integral = !empty($_POST['give_integral']) ? explode(',', $_POST['give_integral']) : [0];
            $rank_integral = !empty($_POST['rank_integral']) ? explode(',', $_POST['rank_integral']) : [0];
            $pay_integral = !empty($_POST['pay_integral']) ? explode(',', $_POST['pay_integral']) : [0];
            $goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
            if (empty($ware_name)) {
                $result['error'] = '1';
                $result['massege'] = $GLOBALS['_LANG']['js_languages']['jl_select_warehouse'];
            } else {
                $add_time = gmtime();
                $goodsInfo = get_admin_goods_info($goods_id);
                $goodsInfo['user_id'] = !empty($goodsInfo['user_id']) ? $goodsInfo['user_id'] : $adminru['ru_id'];

                for ($i = 0; $i < count($ware_name); $i++) {
                    if (!empty($ware_name[$i])) {
                        if ($ware_number[$i] == 0) {
                            $ware_number[$i] = 1;
                        }

                        $sql = "SELECT w_id FROM " . $this->dsc->table('warehouse_goods') . " WHERE goods_id = '$goods_id' AND region_id = '" . $ware_name[$i] . "'";
                        $w_id = $this->db->getOne($sql, true);

                        if ($w_id > 0) {
                            $result['error'] = '1';
                            $result['massege'] = $GLOBALS['_LANG']['this_goods_warehouse_stock_exist'];
                            break;
                        } else {
                            $ware_number[$i] = intval($ware_number[$i]);
                            $ware_price[$i] = floatval($ware_price[$i]);
                            $ware_promote_price[$i] = floatval($ware_promote_price[$i]);
                            //库存日志
                            $number = "+ " . $ware_number[$i];
                            $use_storage = 13;

                            if ($ware_number[$i] > 0) {
                                $logs_other = [
                                    'goods_id' => $goods_id,
                                    'order_id' => 0,
                                    'use_storage' => $use_storage,
                                    'admin_id' => session('seller_id'),
                                    'number' => $number,
                                    'model_inventory' => 1,
                                    'model_attr' => 1,
                                    'product_id' => 0,
                                    'warehouse_id' => $ware_name[$i],
                                    'area_id' => 0,
                                    'add_time' => $add_time
                                ];
                            }
                            GoodsInventoryLogs::insert($logs_other);

                            $other = [
                                'goods_id' => $goods_id,
                                'region_id' => $ware_name[$i],
                                'region_number' => $ware_number[$i],
                                'warehouse_price' => floatval($ware_price[$i]),
                                'warehouse_promote_price' => floatval($ware_promote_price[$i]),
                                'user_id' => $goodsInfo['user_id'],
                                'add_time' => $add_time
                            ];
                            WarehouseGoods::insert($other);

                            $get_warehouse_goods_list = get_warehouse_goods_list($goods_id);
                            $warehouse_id = '';
                            if (!empty($get_warehouse_goods_list)) {
                                foreach ($get_warehouse_goods_list as $k => $v) {
                                    $warehouse_id .= $v['w_id'] . ",";
                                }
                            }

                            $warehouse_id = substr($warehouse_id, 0, strlen($warehouse_id) - 1);
                            $this->smarty->assign("warehouse_id", $warehouse_id);
                            $this->smarty->assign("warehouse_goods_list", $get_warehouse_goods_list);
                        }
                    } else {
                        $result['error'] = '1';
                        $result['massege'] = $GLOBALS['_LANG']['js_languages']['jl_select_warehouse'];
                    }
                }
            }
            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_warehouse.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 仓库信息列表 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_warehouse') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $warehouse_goods_list = get_warehouse_goods_list($goods_id);
            $GLOBALS['smarty']->assign('warehouse_goods_list', $warehouse_goods_list);
            $GLOBALS['smarty']->assign('is_list', 1);

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_warehouse.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 仓库信息列表 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_region') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $warehouse_area_goods_list = get_warehouse_area_goods_list($goods_id);
            $GLOBALS['smarty']->assign('warehouse_area_goods_list', $warehouse_area_goods_list);
            $GLOBALS['smarty']->assign('is_list', 1);

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_region.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加商品地区 ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addRegion') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];
            $warehouse_area_name = !empty($_POST['warehouse_area_name']) ? $_POST['warehouse_area_name'] : '';
            $area_name = !empty($_POST['warehouse_area_list']) ? $_POST['warehouse_area_list'] : '';
            $area_city = isset($_POST['warehouse_area_city']) && !empty($_POST['warehouse_area_city']) ? $_POST['warehouse_area_city'] : 0;
            $region_number = !empty($_POST['region_number']) ? intval($_POST['region_number']) : 0;
            $region_price = !empty($_POST['region_price']) ? floatval($_POST['region_price']) : 0;
            $region_promote_price = !empty($_POST['region_promote_price']) ? floatval($_POST['region_promote_price']) : 0;
            $give_integral = !empty($_POST['give_integral']) ? intval($_POST['give_integral']) : 0;
            $rank_integral = !empty($_POST['rank_integral']) ? intval($_POST['rank_integral']) : 0;
            $pay_integral = !empty($_POST['pay_integral']) ? intval($_POST['pay_integral']) : 0;
            $goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;

            if (empty($area_name)) {
                $result['error'] = '1';
                $result['massege'] = $GLOBALS['_LANG']['js_languages']['jl_select_region'];
            } else {
                if ($region_number == 0) {
                    $result['error'] = '1';
                    $result['massege'] = $GLOBALS['_LANG']['region_stock_not_0'];
                } elseif ($region_price == 0) {
                    $result['error'] = '1';
                    $result['massege'] = $GLOBALS['_LANG']['region_price_not_0'];
                } else {
                    $add_time = gmtime();

                    $where = '';
                    if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                        $where .= " AND city_id = '" . $area_city . "'";
                    }

                    $sql = "select a_id from " . $this->dsc->table('warehouse_area_goods') . " where goods_id = '$goods_id' and region_id = '" . $area_name . "'" . $where;
                    $a_id = $this->db->getOne($sql);

                    if ($a_id > 0) {
                        $result['error'] = '1';
                        $result['massege'] = $GLOBALS['_LANG']['goods_region_price_exist'];
                    } else {
                        $goodsInfo = get_admin_goods_info($goods_id);
                        $goodsInfo['user_id'] = !empty($goodsInfo['user_id']) ? $goodsInfo['user_id'] : $adminru['ru_id'];

                        //库存日志
                        $number = "+ " . $region_number;
                        $use_storage = 13;

                        if ($region_number > 0) {
                            $logs_other = [
                                'goods_id' => $goods_id,
                                'order_id' => 0,
                                'use_storage' => $use_storage,
                                'admin_id' => session('seller_id'),
                                'number' => $number,
                                'model_inventory' => 2,
                                'model_attr' => 2,
                                'product_id' => 0,
                                'warehouse_id' => 0,
                                'area_id' => $area_name,
                                'add_time' => $add_time
                            ];

                            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                                $logs_other['city_id'] = $area_city[$i];
                            }

                            GoodsInventoryLogs::insert($logs_other);
                        }

                        $other = [
                            'goods_id' => $goods_id,
                            'region_id' => $area_name,
                            'region_number' => $region_number,
                            'region_price' => floatval($region_price),
                            'region_promote_price' => floatval($region_promote_price),
                            'give_integral' => $give_integral,
                            'rank_integral' => $rank_integral,
                            'pay_integral' => $pay_integral,
                            'user_id' => $goodsInfo['user_id'],
                            'add_time' => $add_time
                        ];

                        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                            $area_other['city_id'] = $area_city;
                        }

                        WarehouseAreaGoods::insert($other);

                        $result['error'] = '2';
                        $warehouse_area_goods_list = get_warehouse_area_goods_list($goods_id);
                        $warehouse_id = '';
                        if (!empty($warehouse_area_goods_list)) {
                            foreach ($warehouse_area_goods_list as $k => $v) {
                                $warehouse_id .= $v['a_id'] . ",";
                            }
                        }
                        $warehouse_area_id = substr($warehouse_id, 0, strlen($warehouse_id) - 1);
                        $this->smarty->assign("warehouse_area_id", $warehouse_area_id);
                        $this->smarty->assign("warehouse_area_goods_list", $warehouse_area_goods_list);

                        $this->smarty->assign("goods", $goodsInfo);

                        $result['content'] = $GLOBALS['smarty']->fetch('library/goods_region.lbi');
                    }
                }
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 批量添加商品地区 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addBatchRegion') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];
            $warehouse_area_name = !empty($_POST['warehouse_area_name']) ? explode(',', $_POST['warehouse_area_name']) : [];
            $area_name = !empty($_POST['warehouse_area_list']) ? explode(',', $_POST['warehouse_area_list']) : [];
            $area_city = isset($_POST['warehouse_area_city']) && !empty($_POST['warehouse_area_city']) ? explode(',', $_POST['warehouse_area_city']) : [];
            $ware_number = !empty($_POST['ware_number']) ? explode(',', $_POST['ware_number']) : [0 => 0];
            $region_number = !empty($_POST['region_number']) ? explode(',', $_POST['region_number']) : [];
            $region_price = !empty($_POST['region_price']) ? explode(',', $_POST['region_price']) : [];
            $region_promote_price = !empty($_POST['region_promote_price']) ? explode(',', $_POST['region_promote_price']) : [];
            $goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;

            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $area_pricetype = $area_city;
            } else {
                $area_pricetype = $area_name;
            }

            if (empty($area_name)) {
                $result['error'] = '1';
                $result['massege'] = $GLOBALS['_LANG']['js_languages']['jl_select_region'];
            } else {
                if (empty($region_number)) {
                    $result['error'] = '1';
                    $result['massege'] = $GLOBALS['_LANG']['region_stock_not_0'];
                } elseif (empty($region_price)) {
                    $result['error'] = '1';
                    $result['massege'] = $GLOBALS['_LANG']['region_price_not_0'];
                } else {
                    $add_time = gmtime();
                    $goodsInfo = get_admin_goods_info($goods_id);
                    $goodsInfo['user_id'] = !empty($goodsInfo['user_id']) ? $goodsInfo['user_id'] : $adminru['ru_id'];

                    for ($i = 0; $i < count($area_pricetype); $i++) {
                        if (!empty($area_pricetype[$i])) {
                            $where = '';
                            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                                $area_city[$i] = $area_city[$i] ?? 0;
                                $where .= " AND city_id = '" . $area_city[$i] . "'";
                            }

                            $sql = "select a_id from " . $this->dsc->table('warehouse_area_goods') . " where goods_id = '$goods_id' and region_id = '" . $area_name[$i] . "'" . $where;
                            $a_id = $this->db->getOne($sql, true);
                            if ($a_id > 0) {
                                $result['error'] = '1';
                                $result['massege'] = $GLOBALS['_LANG']['goods_region_price_exist'];
                                break;
                            } else {
                                $ware_number[$i] = isset($ware_number[$i]) && !empty($ware_number[$i]) ? intval($ware_number[$i]) : 0;
                                $ware_price[$i] = isset($ware_price[$i]) && !empty($ware_price[$i]) ? floatval($ware_price[$i]) : 0;
                                $region_promote_price[$i] = isset($region_promote_price[$i]) && !empty($region_promote_price[$i]) ? floatval($region_promote_price[$i]) : 0;

                                //库存日志
                                $number = "+ " . $ware_number[$i];
                                $use_storage = 13;

                                if ($ware_number[$i] > 0) {
                                    $logs_other = [
                                        'goods_id' => $goods_id,
                                        'order_id' => 0,
                                        'use_storage' => $use_storage,
                                        'admin_id' => session('seller_id'),
                                        'number' => $number,
                                        'model_inventory' => $goodsInfo['model_inventory'],
                                        'model_attr' => $goodsInfo['model_attr'],
                                        'product_id' => 0,
                                        'warehouse_id' => 0,
                                        'area_id' => $area_name[$i],
                                        'add_time' => $add_time
                                    ];

                                    GoodsInventoryLogs::insert($logs_other);
                                }

                                $area_other = [
                                    'goods_id' => $goods_id,
                                    'region_id' => $area_name[$i],
                                    'region_number' => $region_number[$i],
                                    'region_price' => $region_price[$i],
                                    'region_promote_price' => $region_promote_price[$i],
                                    'user_id' => $goodsInfo['user_id'],
                                    'add_time' => $add_time
                                ];

                                if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                                    $area_other['city_id'] = $area_city[$i];
                                }

                                WarehouseAreaGoods::insert($area_other);

                                $get_warehouse_area_goods_list = get_warehouse_area_goods_list($goods_id);
                                $warehouse_id = '';
                                if (!empty($get_warehouse_area_goods_list)) {
                                    foreach ($get_warehouse_area_goods_list as $k => $v) {
                                        $warehouse_id .= $v['a_id'] . ",";
                                    }
                                }
                                $warehouse_area_id = substr($warehouse_id, 0, strlen($warehouse_id) - 1);
                                $this->smarty->assign("warehouse_area_id", $warehouse_area_id);
                                $this->smarty->assign("warehouse_area_goods_list", $get_warehouse_area_goods_list);

                                $this->smarty->assign("goods", $goodsInfo);
                            }
                        } else {
                            $result['error'] = '1';
                            $result['massege'] = $GLOBALS['_LANG']['js_languages']['jl_select_region'];
                            break;
                        }
                    }
                }
            }
            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_region.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 上传商品相册 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addImg') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];
            $goods_id = !empty($_REQUEST['goods_id_img']) ? $_REQUEST['goods_id_img'] : '';
            $img_desc = !empty($_REQUEST['img_desc']) ? $_REQUEST['img_desc'] : '';
            $img_file = !empty($_REQUEST['img_file']) ? $_REQUEST['img_file'] : '';
            $php_maxsize = ini_get('upload_max_filesize');
            $htm_maxsize = '2M';

            if ($_FILES['img_url']) {
                foreach ($_FILES['img_url']['error'] as $key => $value) {
                    if ($value == 0) {
                        if (!$image->check_img_type($_FILES['img_url']['type'][$key])) {
                            $result['error'] = '1';
                            $result['massege'] = sprintf($GLOBALS['_LANG']['invalid_img_url'], $key + 1);
                        } else {
                            $goods_pre = 1;
                        }
                    } elseif ($value == 1) {
                        $result['error'] = '1';
                        $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $php_maxsize);
                    } elseif ($_FILES['img_url']['error'] == 2) {
                        $result['error'] = '1';
                        $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $htm_maxsize);
                    }
                }
            }

            $this->goodsManageService->handleGalleryImageAdd($goods_id, $_FILES['img_url'], $img_desc, $img_file, '', '', 'ajax');

            clear_cache_files();
            if ($goods_id > 0) {
                /* 图片列表 */
                $sql = "SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id'";
            } else {
                $img_id = session()->get('thumb_img_id' . session('seller_id'));
                $where = '';
                if ($img_id) {
                    $where = "AND img_id " . db_create_in($img_id) . "";
                }
                $sql = "SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id='' $where ORDER BY img_desc ASC";
            }
            $img_list = $this->db->getAll($sql);
            /* 格式化相册图片路径 */
            if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                foreach ($img_list as $key => $gallery_img) {
                    //图片显示
                    $gallery_img['img_original'] = get_image_path($gallery_img['img_original']);

                    $img_list[$key]['img_url'] = $gallery_img['img_original'];

                    $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                    $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                }
            } else {
                foreach ($img_list as $key => $gallery_img) {
                    $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                    $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                }
            }
            $goods['goods_id'] = $goods_id;
            $this->smarty->assign('img_list', $img_list);
            $img_desc = [];
            foreach ($img_list as $k => $v) {
                $img_desc[] = $v['img_desc'];
            }
            $img_default = min($img_desc);
            $min_img_id = $this->db->getOne(" SELECT img_id   FROM " . $this->dsc->table("goods_gallery") . " WHERE goods_id = '$goods_id' AND img_desc = '$img_default' ORDER BY img_desc   LIMIT 1");
            $this->smarty->assign('min_img_id', $min_img_id);
            $this->smarty->assign('goods', $goods);
            $result['error'] = '2';
            $result['content'] = $GLOBALS['smarty']->fetch('goods_img_list.dwt');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改默认相册 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'img_default') {
            $result = ['content' => '', 'error' => 0, 'massege' => '', 'img_id' => ''];

            $admin_id = get_admin_id();
            $img_id = !empty($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : '0';
            if ($img_id > 0) {
                $goods_id = $this->db->getOne(" SELECT goods_id FROM" . $this->dsc->table('goods_gallery') . " WHERE img_id= '$img_id'");
                $this->db->query("UPDATE" . $this->dsc->table('goods_gallery') . " SET img_desc = img_desc+1 WHERE goods_id = '$goods_id' ");
                $sql = $this->db->query("UPDATE" . $this->dsc->table('goods_gallery') . " SET img_desc = 1 WHERE img_id = '$img_id'");
                if ($sql = true) {
                    $where = " 1 ";
                    if (empty($goods_id) && session()->has('thumb_img_id' . $admin_id) && session()->get('thumb_img_id' . $admin_id)) {
                        $where .= " AND img_id" . db_create_in(session()->get('thumb_img_id' . $admin_id));
                    } else {
                        $where .= " AND goods_id = '$goods_id'";
                    }

                    $sql = "SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE $where ORDER BY img_desc ASC";
                    $img_list = $this->db->getAll($sql);

                    /* 格式化相册图片路径 */
                    if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                        foreach ($img_list as $key => $gallery_img) {
                            //图片显示
                            $gallery_img['img_original'] = get_image_path($gallery_img['img_original']);

                            $img_list[$key]['img_url'] = $gallery_img['img_original'];

                            $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                        }
                    } else {
                        foreach ($img_list as $key => $gallery_img) {
                            $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                        }
                    }
                    $img_desc = [];
                    foreach ($img_list as $k => $v) {
                        $img_desc[] = $v['img_desc'];
                    }
                    $img_default = min($img_desc);
                    $min_img_id = $this->db->getOne(" SELECT img_id   FROM " . $this->dsc->table("goods_gallery") . " WHERE goods_id = '$goods_id' AND img_desc = '$img_default' ORDER BY img_desc   LIMIT 1");
                    $this->smarty->assign('min_img_id', $min_img_id);
                    $this->smarty->assign('img_list', $img_list);
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['smarty']->fetch('library/gallery_img.lbi');
                } else {
                    $result['error'] = 2;
                    $result['massege'] = lang('seller/common.modify_failure');
                }
            }
            return response()->json($result);
        } elseif ($_REQUEST['act'] == 'remove_consumption') {
            $result = ['error' => 0, 'massege' => '', 'con_id' => ''];

            $con_id = !empty($_REQUEST['con_id']) ? intval($_REQUEST['con_id']) : '0';
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : '0';
            if ($con_id > 0) {
                $sql = "DELETE FROM" . $this->dsc->table('goods_consumption') . " WHERE id = '$con_id' AND goods_id = '$goods_id'";
                if ($this->db->query($sql)) {
                    $result['error'] = 2;
                    $result['con_id'] = $con_id;
                }
            } else {
                $result['error'] = 1;
                $result['massege'] = lang('seller/common.select_delete_target');
            }
            return response()->json($result);
        } // mobile商品详情 添加图片 qin
        elseif ($_REQUEST['act'] == 'gallery_album_dialog') {
            $result = ['error' => 0, 'message' => '', 'log_type' => '', 'content' => ''];
            $content = !empty($_REQUEST['content']) ? $_REQUEST['content'] : '';
            // 获取相册信息 qin
            $sql = "SELECT album_id,ru_id,album_mame,album_cover,album_desc,sort_order FROM " . $this->dsc->table('gallery_album') . " "
                . " WHERE ru_id = '$adminru[ru_id]' ORDER BY sort_order";
            $gallery_album_list = $this->db->getAll($sql);
            $this->smarty->assign('gallery_album_list', $gallery_album_list);

            $log_type = !empty($_GET['log_type']) ? trim($_GET['log_type']) : 'image';
            $result['log_type'] = $log_type;
            $this->smarty->assign('log_type', $log_type);

            $sql = "SELECT * FROM " . $this->dsc->table('pic_album') . " WHERE ru_id = '$adminru[ru_id]'";
            $res = $this->db->getAll($sql);
            $this->smarty->assign('pic_album', $res);
            $this->smarty->assign('content', $content);
            $result['content'] = $this->smarty->fetch('library/album_dialog.lbi');

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 扫码�        �库 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'scan_code') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['error' => 0, 'massege' => '', 'content' => ''];

            $bar_code = empty($_REQUEST['bar_code']) ? '' : trim($_REQUEST['bar_code']);
            $config = get_scan_code_config($adminru['ru_id']);
            $data = get_jsapi(['appkey' => $config['js_appkey'], 'barcode' => $bar_code]);

            if ($data['status'] != 0) {
                $result['error'] = 1;
                $result['message'] = $data['msg'];
            } else {
                //重量（用毛重）
                $goods_weight = 0;
                if (strpos($data['result']['grossweight'], $GLOBALS['_LANG']['kilogram']) !== false) {
                    $goods_weight = floatval(str_replace($GLOBALS['_LANG']['kilogram'], '', $data['result']['grossweight']));
                } elseif (strpos($data['result']['grossweight'], $GLOBALS['_LANG']['gram']) !== false) {
                    $goods_weight = floatval(str_replace($GLOBALS['_LANG']['kilogram'], '', $data['result']['grossweight'])) / 1000;
                }
                //详情
                $goods_desc = "";
                if (!empty($data['result']['description'])) {
                    create_html_editor('goods_desc', trim($data['result']['description']));
                    $goods_desc = $this->smarty->get_template_vars('FCKeditor');
                }

                //初始商品信息
                $goods_info = [];
                $goods_info['goods_name'] = isset($data['result']['name']) ? trim($data['result']['name']) : ''; //名称
                $goods_info['goods_name'] .= isset($data['result']['type']) ? trim($data['result']['type']) : ''; //规格
                $goods_info['shop_price'] = isset($data['result']['price']) ? floatval($data['result']['price']) : '0.00'; //价格
                $goods_info['goods_img_url'] = isset($data['result']['pic']) ? trim($data['result']['pic']) : ''; //价格
                $goods_info['goods_desc'] = $goods_desc; //描述
                $goods_info['goods_weight'] = $goods_weight; //重量
                $goods_info['keywords'] = isset($data['result']['keyword']) ? trim($data['result']['keyword']) : ''; //关键词
                $goods_info['width'] = isset($data['result']['width']) ? trim($data['result']['width']) : ''; //宽度
                $goods_info['height'] = isset($data['result']['height']) ? trim($data['result']['height']) : ''; //高度
                $goods_info['depth'] = isset($data['result']['depth']) ? trim($data['result']['depth']) : ''; //深度
                $goods_info['origincountry'] = isset($data['result']['origincountry']) ? trim($data['result']['origincountry']) : ''; //产国
                $goods_info['originplace'] = isset($data['result']['originplace']) ? trim($data['result']['originplace']) : ''; //产地
                $goods_info['assemblycountry'] = isset($data['result']['assemblycountry']) ? trim($data['result']['assemblycountry']) : ''; //组装国
                $goods_info['barcodetype'] = isset($data['result']['barcodetype']) ? trim($data['result']['barcodetype']) : ''; //条码类型
                $goods_info['catena'] = isset($data['result']['catena']) ? trim($data['result']['catena']) : ''; //产品系列
                $goods_info['isbasicunit'] = isset($data['result']['isbasicunit']) ? intval($data['result']['isbasicunit']) : 0; //是否是基本单元
                $goods_info['packagetype'] = isset($data['result']['packagetype']) ? trim($data['result']['packagetype']) : ''; //包装类型
                $goods_info['grossweight'] = isset($data['result']['grossweight']) ? trim($data['result']['grossweight']) : ''; //毛重
                $goods_info['netweight'] = isset($data['result']['netweight']) ? trim($data['result']['netweight']) : ''; //净重
                $goods_info['netcontent'] = isset($data['result']['netcontent']) ? trim($data['result']['netcontent']) : ''; //净含量
                $goods_info['licensenum'] = isset($data['result']['licensenum']) ? trim($data['result']['licensenum']) : ''; //生产许可证
                $goods_info['healthpermitnum'] = isset($data['result']['healthpermitnum']) ? trim($data['result']['healthpermitnum']) : ''; //卫生许可证
                $result['goods_info'] = $goods_info;
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 查看日志 by liu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view_log') {
            /* 权限的判断 */
            admin_priv('goods_manage');

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['view_log']);
            //$this->smarty->assign('ip_list', $ip_list);
            $this->smarty->assign('full_page', 1);
            $goods_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $action_link = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list'], 'class' => 'icon-reply'];
            $this->smarty->assign('action_link', $action_link);

            $log_list = get_goods_change_logs($goods_id);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($log_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('goods_view_logs.dwt');
        }

        /* ------------------------------------------------------ */
        //-- view_detail 会员价 阶梯价 查看详情
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view_detail') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $log_id = !empty($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;
            $step = !empty($_REQUEST['step']) ? trim($_REQUEST['step']) : '';
            if ($step == 'member') {
                $res = $this->db->getOne(" SELECT member_price FROM " . $this->dsc->table('goods_change_log') . " WHERE log_id = '$log_id' ");
                $res = unserialize($res);
                if ($res) {
                    foreach ($res as $k => $v) {
                        $member_price[$k]['rank_name'] = $this->db->getOne(" SELECT rank_name FROM " . $this->dsc->table('user_rank') . " WHERE rank_id = '$k' ");
                        $member_price[$k]['member_price'] = $v;
                    }
                }
                $this->smarty->assign('res', $member_price);
            } elseif ($step == 'volume') {
                $res = $this->db->getOne(" SELECT volume_price FROM " . $this->dsc->table('goods_change_log') . " WHERE log_id = '$log_id' ");
                $res = unserialize($res);
                if ($res) {
                    foreach ($res as $k => $v) {
                        $volume_price[$k]['volume_num'] = $k;
                        $volume_price[$k]['volume_price'] = $v;
                    }
                }
                $this->smarty->assign('res', $volume_price);
            }

            $this->smarty->assign('step', $step);

            $result['content'] = $GLOBALS['smarty']->fetch('library/view_detail_list.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view_query') {
            $goods_id = !empty($_REQUEST['goodsId']) ? intval($_REQUEST['goodsId']) : 0;
            $log_list = get_goods_change_logs($goods_id);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($log_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('log_list', $log_list['list']);
            $this->smarty->assign('filter', $log_list['filter']);
            $this->smarty->assign('record_count', $log_list['record_count']);
            $this->smarty->assign('page_count', $log_list['page_count']);

            $sort_flag = sort_flag($log_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('goods_view_logs.dwt'),
                '',
                ['filter' => $log_list['filter'], 'page_count' => $log_list['page_count']]
            );
        }
        /* ------------------------------------------------------ */
        //-- 商品�        �件设置
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_gorup_type') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $result = ['error' => '', 'message' => ''];

            $id = intval($_POST['id']);
            $group_id = intval($_POST['group_id']);

            $data = ['group_id' => $group_id];
            GroupGoods::where('id', $id)->update($data);

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 商品配件价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_gorup_price') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $sec_price = floatval($_POST['val']);

            $data = ['goods_price' => $sec_price];
            $res = GroupGoods::where('id', $id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result($sec_price);
            }
        }
        /*------------------------------------------------------ */
        //-- 删除商品�        �件
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_group_type') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['error' => '', 'message' => ''];
            $id = intval($_POST['id']);
            GroupGoods::where('id', $id)->delete();
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 计算分销佣金
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_drp_money') {
            $result = ['error' => '', 'message' => ''];
            $shop_price = request()->input('shop_price', '');
            $dis_commission = request()->input('dis_commission', '');
            $config = DrpConfig::where(['code' => 'drp_affiliate']);
            $config = $this->baseRepository->getToArrayFirst($config);
            $drp_affiliate = isset($config['value']) ? unserialize($config['value']) : [];
            if ($drp_affiliate) {
                $affiliate = $drp_affiliate['item'][0];
                foreach ($affiliate as $val) {
                    // 计算分销佣金
                    $money = $shop_price * $dis_commission / 100;
                    $val = ((float)$val / 100); // 分成比例
                    $setmoney = round($money * $val, 2);// 佣金
                    if ($setmoney < 0.01) {
                        break;
                    }
                }
                if ($setmoney >= 0.01) {
                    $result['error'] = 0;
                    $result['message'] = $GLOBALS['_LANG']['set_drp_money_success'];
                    return response()->json($result);
                } else {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['set_drp_money'];
                    return response()->json($result);
                }
            }
        }

    }

    /**
     * 组合 返回分类列表  图片批量处理和商品批量修改
     *
     */
    protected function cat_list_one_new($cat_id = 0, $cat_level = 0, $sel_cat)
    {
        $arr = $this->categoryService->catList($cat_id);

        if ($cat_id == 0) {
            return $arr;
        } else {
            foreach ($arr as $key => $value) {
                if ($key == $cat_id) {
                    unset($arr[$cat_id]);
                }
            }

            // 拼接字符串
            $str = '';
            if ($arr) {
                $cat_level++;
                switch ($sel_cat) {
                    case 'sel_cat_edit':
                        $str .= "<select name='catList$cat_level' id='cat_list$cat_level' onchange='getGoods(this.value, $cat_level)' class='select'>";
                        break;
                    case 'sel_cat_picture':
                        $str .= "<select name='catList$cat_level' id='cat_list$cat_level' onchange='goods_list(this, $cat_level)' class='select'>";
                        break;
                    case 'sel_cat_goodslist':
                        $str .= "<select class='select mr10' name='movecatList$cat_level' id='move_cat_list$cat_level' onchange='movecatList(this.value, $cat_level)'>";
                        break;

                    default:
                        break;
                }

                $str .= "<option value='0'>" . $GLOBALS['_LANG']['all_category_alt'] . "</option>";
                foreach ($arr as $key1 => $value1) {
                    $str .= "<option value='$value1[cat_id]'>$value1[cat_name]</option>";
                }
                $str .= "</select>";
            }
            return $str;
        }
    }

    /**
     * 添加链接
     * @param string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    protected function add_link($extension_code = '')
    {
        $href = 'goods.php?act=add';
        if (!empty($extension_code)) {
            $href .= '&extension_code=' . $extension_code;
        }

        if ($extension_code == 'virtual_card') {
            $text = $GLOBALS['_LANG']['51_virtual_card_add'];
        } else {
            $text = $GLOBALS['_LANG']['02_goods_add'];
        }

        return ['href' => $href, 'text' => $text, 'class' => 'icon-plus'];
    }

    /**
     * 待评价商品
     * @param int $ru_id
     * @param int $sign
     * @return mixed
     */
    protected function get_order_no_comment_goods($ru_id = 0, $sign = 0)
    {
        $where = " AND oi.order_status " . db_create_in([OS_CONFIRMED, OS_SPLITED]) . "  AND oi.shipping_status = '" . SS_RECEIVED . "' AND oi.pay_status " . db_create_in([PS_PAYED]);
        $where .= " AND oi.ru_id = 0 ";  //主订单下有子订单时，则主订单不显示
        if ($sign == 0) {
            $where .= " AND (SELECT count(*) FROM " . $this->dsc->table('comment') . " AS c WHERE c.comment_type = 0 AND c.id_value = g.goods_id AND c.rec_id = og.rec_id AND c.parent_id = 0 AND c.ru_id = '$ru_id') = 0 ";
        }
        //记录总数
        $sql = "SELECT count(*) FROM " . $this->dsc->table('order_goods') . " AS og " .
            "LEFT JOIN " . $this->dsc->table('order_info') . " AS oi ON og.order_id = oi.order_id " .
            "LEFT JOIN  " . $this->dsc->table('goods') . " AS g ON og.goods_id = g.goods_id " .
            "WHERE og.ru_id = '$ru_id' $where ";
        $filter['record_count'] = $this->db->getOne($sql);
        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT og.*, oi.*,g.goods_thumb, u.user_name FROM " . $this->dsc->table('order_goods') . " AS og " .
            "LEFT JOIN " . $this->dsc->table('order_info') . " AS oi ON og.order_id = oi.order_id " .
            "LEFT JOIN  " . $this->dsc->table('goods') . " AS g ON og.goods_id = g.goods_id " .
            "LEFT JOIN  " . $this->dsc->table('users') . " AS u ON u.user_id = oi.user_id " .
            "WHERE og.ru_id = '$ru_id' $where " .
            " ORDER BY oi.order_id DESC " .
            " LIMIT " . $filter['start'] . ",$filter[page_size]";
        $arr = $this->db->getAll($sql);
        return $arr;
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @param string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    protected function list_link($is_add = true, $extension_code = '')
    {
        $href = 'goods.php?act=list';
        if (!empty($extension_code)) {
            $href .= '&extension_code=' . $extension_code;
        }
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        if ($extension_code == 'virtual_card') {
            $text = $GLOBALS['_LANG']['50_virtual_card_list'];
        } else {
            $text = $GLOBALS['_LANG']['01_goods_list'];
        }

        return ['href' => $href, 'text' => $text];
    }

    /**
     * 获取商品订单是否存在
     */
    protected function get_order_goods_cout($goods_id = 0)
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('order_info') . " AS oi WHERE " .
            "(SELECT order_id FROM " . $GLOBALS['dsc']->table('order_goods') . " AS og WHERE og.goods_id = '$goods_id' LIMIT 1) = oi.order_id";
        $order_count = $GLOBALS['db']->getOne($sql);

        return $order_count;
    }
}
