<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Image;
use App\Libraries\Pinyin;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\GalleryAlbum;
use App\Models\GoodsType;
use App\Models\PicAlbum;
use App\Models\RegionWarehouse;
use App\Models\SellerGrade;
use App\Models\SellerShopinfo;
use App\Models\SuppliersGoodsGallery;
use App\Models\Wholesale;
use App\Models\WholesaleExtend;
use App\Models\WholesaleGoodsAttr;
use App\Models\WholesaleProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Wholesale\GoodsManageService as WholesaleGoodsManage;
use App\Services\Wholesale\GoodsService;

/**
 * 记录管理员操作日志
 */
class GoodsController extends InitController
{
    protected $wholesaleGoodsManage;
    protected $baseRepository;
    protected $image;
    protected $pinyin;
    protected $goodsManageService;
    protected $goodsService;
    protected $dscRepository;
    protected $merchantCommonService;

    public function __construct(
        WholesaleGoodsManage $wholesaleGoodsManage,
        BaseRepository $baseRepository,
        Image $image,
        Pinyin $pinyin,
        GoodsManageService $goodsManageService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->wholesaleGoodsManage = $wholesaleGoodsManage;
        $this->image = $image;
        $this->baseRepository = $baseRepository;
        $this->pinyin = $pinyin;
        $this->goodsManageService = $goodsManageService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();
        $admin_id = get_admin_id();
        if ($_REQUEST['act'] == '') {
            $_REQUEST['act'] = 'list';
        }

        if ($_REQUEST['act'] == 'list' || $_REQUEST['act'] == 'trash') {
            admin_priv('suppliers_goods_list');

            WholesaleGoodsAttr::where('goods_id', 0)
                ->where('admin_id', $admin_id)
                ->delete();

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('menu_select', array('action' => '01_suppliers_goods', 'current' => '01_goods_list'));//页面位置标记

            //页面头部tab标记
            if ($_REQUEST['act'] == 'list') {
                //页面分菜单 by wu start
                $tab_menu = array();
                $tab_menu[] = array('curr' => 1, 'text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list');
                $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['11_goods_trash'], 'href' => 'goods.php?act=trash');
                $this->smarty->assign('tab_menu', $tab_menu);
                //页面分菜单 by wu end
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_goods_list']);
            }

            if ($_REQUEST['act'] == 'trash') {
                //页面分菜单 by wu start
                $tab_menu = array();
                $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list');
                $tab_menu[] = array('curr' => 1, 'text' => $GLOBALS['_LANG']['11_goods_trash'], 'href' => 'goods.php?act=trash');
                $this->smarty->assign('tab_menu', $tab_menu);
                //页面分菜单 by wu end
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['11_goods_trash']);
            }

            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

            /* 模板赋值 */
            $action_link = ($_REQUEST['act'] == 'list') ? $this->add_link($code) : array('href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list'], 'class' => 'icon-reply');
            $this->smarty->assign('action_link', $action_link);
            $this->smarty->assign('action_link', $action_link);

            //获取商品列表
            $goods_list = $this->wholesaleGoodsManage->getWholesaleList($_REQUEST['act'] == 'list' ? 0 : 1);

            $this->smarty->assign('goods_list', $goods_list['goods']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);
            $this->smarty->assign('full_page', 1);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($goods_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            /* 排序标记 */
            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $intro_list = $this->goodsManageService->getIntroList();
            $this->smarty->assign('intro_list', $intro_list);

            $this->smarty->assign('nowTime', gmtime());

            $this->smarty->assign('user_id', $adminru['ru_id']);
            set_default_filter(0, 0, 0, 0, 'wholesale_cat'); //设置默认筛选

            $htm_file = ($_REQUEST['act'] == 'list') ?
                'goods_list.dwt' : 'goods_trash.dwt';
            return $this->smarty->display($htm_file);
        }
        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            // 检查权限
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $is_delete = empty($_REQUEST['is_delete']) ? 0 : intval($_REQUEST['is_delete']);
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $goods_list = $this->wholesaleGoodsManage->getWholesaleList($is_delete);

            $handler_list = array();
            $handler_list['virtual_card'][] = array('url' => 'virtual_card.php?act=card', 'title' => $GLOBALS['_LANG']['card'], 'img' => 'icon_send_bonus.gif');
            $handler_list['virtual_card'][] = array('url' => 'virtual_card.php?act=replenish', 'title' => $GLOBALS['_LANG']['replenish'], 'img' => 'icon_add.gif');
            $handler_list['virtual_card'][] = array('url' => 'virtual_card.php?act=batch_card_add', 'title' => $GLOBALS['_LANG']['batch_card_add'], 'img' => 'icon_output.gif');

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

            $tpl = $is_delete ? 'goods_trash.dwt' : 'goods_list.dwt';

            $this->smarty->assign('nowTime', gmtime());

            return make_json_result(
                $this->smarty->fetch($tpl),
                '',
                array('filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 还原回收站中的商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'restore_goods') {
            // 检查权限
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['id']);

            Wholesale::where('goods_id', $goods_id)
                ->update([
                    'is_delete' => 0,
                    'add_time' => gmtime()
                ]);

            clear_cache_files();

            $goods_name = Wholesale::where('goods_id', $goods_id)->value('goods_name');
            admin_log(addslashes($goods_name), 'restore', 'goods'); // 记录日志

            $url = 'goods.php?act=query&' . str_replace('act=restore_goods', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 彻底删除商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_goods') {
            // 检查权限
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            // 取得参数
            $goods_id = intval($_REQUEST['id']);
            if ($goods_id <= 0) {
                return make_json_error('invalid params');
            }

            /* 取得商品信息 */
            $goods = Wholesale::where('goods_id', $goods_id);
            $goods = $this->baseRepository->getToArrayFirst($goods);

            if (empty($goods)) {
                return make_json_error($GLOBALS['_LANG']['goods_not_exist']);
            }

            $adminru = get_admin_ru_id();
            if ($adminru['suppliers_id'] > 0 && $adminru['suppliers_id'] != $goods['suppliers_id']) {
                return make_json_error(lang('suppliers/goods.illegal_operation'));
            }

            if ($goods['is_delete'] != 1) {
                return make_json_error($GLOBALS['_LANG']['goods_not_in_recycle_bin']);
            }

            if ($goods['goods_desc']) {
                $desc_preg = get_goods_desc_images_preg('', $goods['goods_desc']);
                get_desc_images_del($desc_preg['images_list']);
            }

            $arr = array();
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

            /* 删除商品 */
            Wholesale::where('goods_id', $goods_id)->delete();

            /* 删除商品的货品记录 */
            WholesaleProducts::where('goods_id', $goods_id)->delete();

            /* 记录日志 */
            admin_log(addslashes($goods['goods_name']), 'remove', 'goods');

            /* 删除商品相册 */
            $res = SuppliersGoodsGallery::where('goods_id', $goods_id);
            $res = $this->baseRepository->getToArrayGet($res);

            $arr = [];
            if ($res) {
                foreach ($res as $key => $row) {
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
                }
            }

            /* 删除相关表记录 */
            SuppliersGoodsGallery::where('goods_id', $goods_id)->delete();
            WholesaleGoodsAttr::where('goods_id', $goods_id)->delete();

            clear_cache_files();
            $url = 'goods.php?act=query&' . str_replace('act=drop_goods', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 添加新商品 编辑商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit' || $_REQUEST['act'] == 'copy') {
            admin_priv('suppliers_goods_list');

            WholesaleGoodsAttr::where('goods_id', 0)->where('admin_id', $admin_id)->delete();

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            /* 商家入驻分类 */
            $seller_shop_cat = array();
            $this->smarty->assign('menu_select', array('action' => '01_suppliers_goods', 'current' => '01_goods_list'));//页面位置标记

            $is_add = $_REQUEST['act'] == 'add'; // 添加还是编辑的标识

            $properties = empty($_REQUEST['properties']) ? 0 : intval($_REQUEST['properties']);
            $this->smarty->assign('properties', $properties);

            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

            /* 如果是安全模式，检查目录是否存在 */
            if (ini_get('safe_mode') == 1 && (!file_exists(storage_public(IMAGE_DIR . '/' . date('Ym'))) || !is_dir(storage_public(IMAGE_DIR . '/' . date('Ym'))))) {
                if (@!mkdir(storage_public(IMAGE_DIR . '/' . date('Ym')), 0777)) {
                    $warning = sprintf($GLOBALS['_LANG']['safe_mode_warning'], asset('/') . IMAGE_DIR . '/' . date('Ym'));
                    $this->smarty->assign('warning', $warning);
                }
            } /* 如果目录存在但不可写，提示用户 */
            elseif (file_exists(storage_public(IMAGE_DIR . '/' . date('Ym'))) && file_mode_info(storage_public(IMAGE_DIR . '/' . date('Ym'))) < 2) {
                $warning = sprintf($GLOBALS['_LANG']['not_writable_warning'], asset('/') . IMAGE_DIR . '/' . date('Ym'));
                $this->smarty->assign('warning', $warning);
            }

            $adminru = get_admin_ru_id();

            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            /* 取得商品信息 */
            if ($is_add) {

                /*退换货标志列表*/
                $res = array('0', '1', '2', '3');
                $this->smarty->assign('is_cause', $res);

                $goods = array(
                    'goods_id' => 0,
                    'goods_desc' => '',
                    'freight' => 2,
                    'cat_id' => '0',
                    'brand_id' => 0,
                    'enabled' => '1',
                    'goods_type' => 0, // 商品类型
                    'goods_price' => 0,
                    'promote_price' => 0,
                    'retail_price' => 0,
                    'goods_number' => $GLOBALS['_CFG']['default_storage'],
                    'warn_number' => 1,
                    'start_time' => local_date($GLOBALS['_CFG']['time_format']),
                    'end_time' => local_date($GLOBALS['_CFG']['time_format'], local_strtotime('+1 month')),
                    'goods_weight' => 0,
                    'goods_unit' => '个'
                );
                $goods_service = array('is_delivery' => 1, 'is_return' => 1, 'is_free' => 1);
                $goods['goods_extend'] = $goods_service;

                /* 图片列表 */
                $img_list = [];
            } else {

                /* 商品信息 */
                $goods = Wholesale::where('goods_id', $goods_id);
                $goods = $this->baseRepository->getToArrayFirst($goods);

                if (empty($goods)) {
                    $url = 'goods.php?act=list';
                    return dsc_header("Location: $url\n");
                }

                if ($goods['suppliers_id'] != $adminru['suppliers_id']) {
                    $Loaction = "goods.php?act=list";
                    return dsc_header("Location: $Loaction\n");
                }

                /*退换货标志列表*/
                $cause_list = array('0', '1', '2', '3');

                /* 判断商品退换货理由 */
                if ($goods['goods_cause']) {
                    $goods_cause = explode(',', $goods['goods_cause']);
                    $is_cause = array_intersect($goods_cause, $cause_list);
                } else {
                    $is_cause = [];
                }

                $this->smarty->assign('is_cause', $is_cause);

                //图片显示
                $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);

                /* 根据商品重量的单位重新计算 */
                if ($goods['goods_weight'] > 0) {
                    $goods['goods_weight_by_unit'] = ($goods['goods_weight'] >= 1) ? $goods['goods_weight'] : ($goods['goods_weight'] / 0.001);
                }

                if (!empty($goods['goods_brief'])) {
                    $goods['goods_brief'] = $goods['goods_brief'];
                }

                /* 如果不是限购，处理限购日期 */
                if (isset($goods['is_xiangou']) && $goods['is_xiangou'] == '0') {
                    unset($goods['xiangou_start_date']);
                    unset($goods['xiangou_end_date']);
                } else {
                    $goods['xiangou_start_date'] = local_date('Y-m-d H:i:s', $goods['xiangou_start_date']);
                    $goods['xiangou_end_date'] = local_date('Y-m-d H:i:s', $goods['xiangou_end_date']);
                }

                if (!empty($goods['goods_product_tag'])) {
                    $goods['goods_product_tag'] = $goods['goods_product_tag'];
                }

                /* 如果不是促销，处理促销日期 */
                if (isset($goods['is_promote']) && $goods['is_promote'] == '0') {
                    unset($goods['start_time']);
                    unset($goods['end_time']);
                } else {
                    $goods['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $goods['start_time']);
                    $goods['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $goods['end_time']);
                }

                /* 商品图片路径 */
                if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 10) && !empty($goods['original_img'])) {
                    $goods['goods_img'] = get_image_path($goods_id, $goods['goods_img']);
                    $goods['goods_thumb'] = get_image_path($goods_id, $goods['goods_thumb'], true);
                }

                /* 图片列表 */
                $img_list = SuppliersGoodsGallery::where('goods_id', $goods_id);
                $img_list = $this->baseRepository->getToArrayGet($img_list);

                $img_desc = [];
                /* 格式化相册图片路径 */
                if ($img_list) {
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

                    foreach ($img_list as $k => $v) {
                        $img_desc[] = $v['img_desc'];
                    }
                }

                $img_default = $img_desc ? min($img_desc) : 0;

                $min_img_id = SuppliersGoodsGallery::where('goods_id', $goods_id)
                    ->where('img_desc', $img_default)
                    ->min('img_id');

                $this->smarty->assign('min_img_id', $min_img_id);

                //处理商品服务
                $goods['goods_extend'] = $this->goodsService->getWholesaleExtend($goods['goods_id']);
            }

            if (empty($goods['user_id'])) {
                $goods['user_id'] = $adminru['ru_id'];
            }

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

            /* 创建 html editor */
            create_html_editor('goods_desc', $goods['goods_desc']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $is_add ? (empty($code) ? $GLOBALS['_LANG']['02_goods_add'] : $GLOBALS['_LANG']['51_virtual_card_add']) : ($_REQUEST['act'] == 'edit' ? $GLOBALS['_LANG']['edit_goods'] : $GLOBALS['_LANG']['copy_goods']));
            $this->smarty->assign('action_link', $this->list_link($is_add, $code));
            $this->smarty->assign('goods', $goods);

            if ($is_add) {
                $cat_list = $this->goodsManageService->catListOne(0, 0, $seller_shop_cat);
            } else {
                $cat_list = $this->goodsManageService->catListOne($goods['cat_id'], 0, $seller_shop_cat);
            }

            $this->smarty->assign('cat_list', $cat_list);
            $this->smarty->assign('brand_list', get_brand_list($goods_id));

            $brand_name = Brand::where('brand_id', $goods['brand_id'])->value('brand_name');
            $brand_name = $brand_name ? $brand_name : '';

            $this->smarty->assign('brand_name', $brand_name);

            $unit_list = $this->goodsManageService->getUnitList();
            $this->smarty->assign('unit_list', $unit_list);
            $this->smarty->assign('weight_unit', $is_add ? '1' : ($goods['goods_weight'] >= 1 ? '1' : '0.001'));
            $this->smarty->assign('cfg', $GLOBALS['_CFG']);
            $this->smarty->assign('form_act', $is_add ? 'insert' : ($_REQUEST['act'] == 'edit' ? 'update' : 'insert'));
            if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
                $this->smarty->assign('is_add', true);
            }

            $this->smarty->assign('img_list', $img_list);
            $this->smarty->assign('goods_type_list', goods_type_list($goods['goods_type'], $goods['goods_id'], 'array'));


            //获取分类数组
            $type_c_id = GoodsType::where('cat_id', $goods['goods_type'])->value('c_id');
            $type_c_id = $type_c_id ? $type_c_id : 0;

            $type_level = get_type_cat_arr();
            $this->smarty->assign('type_level', $type_level);

            $cat_tree = get_type_cat_arr($type_c_id, 2);

            $cat_tree1 = [
                'checked_id' => $cat_tree['checked_id'] ?? 0
            ];

            if (isset($cat_tree['checked_id']) && $cat_tree['checked_id'] > 0) {
                $cat_tree1 = get_type_cat_arr($cat_tree['checked_id'], 2);
            }

            $this->smarty->assign("type_c_id", $type_c_id);
            $this->smarty->assign("cat_tree", $cat_tree);
            $this->smarty->assign("cat_tree1", $cat_tree1);

            $this->smarty->assign('gd', gd_version());
            $this->smarty->assign('thumb_width', $GLOBALS['_CFG']['thumb_width']);
            $this->smarty->assign('thumb_height', $GLOBALS['_CFG']['thumb_height']);

            $volume_price_list = $this->wholesaleGoodsManage->getWholesaleVolumePriceList($goods_id);
            $this->smarty->assign('volume_price_list', $volume_price_list);

            //设置商品分类
            $level_limit = 3;
            $category_level = array();

            if ($_REQUEST['act'] == 'add') {
                for ($i = 1; $i <= $level_limit; $i++) {
                    $category_list = array();
                    if ($i == 1) {
                        $category_list = get_category_list(0, 0, $seller_shop_cat, $goods['user_id'], 0, 'wholesale_cat');
                    }
                    $this->smarty->assign('cat_level', $i);
                    $this->smarty->assign('category_list', $category_list);
                    $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
                }
            }

            if ($_REQUEST['act'] == 'edit' || $_REQUEST['act'] == 'copy') {
                $parent_cat_list = get_select_category($goods['cat_id'], 1, true);

                for ($i = 1; $i <= $level_limit; $i++) {
                    $category_list = array();
                    if (isset($parent_cat_list[$i])) {
                        $category_list = get_category_list($parent_cat_list[$i], 0, $seller_shop_cat, $goods['user_id'], $i, 'wholesale_cat');
                    } elseif ($i == 1) {
                        if ($goods['user_id']) {
                            $category_list = get_category_list(0, 0, $seller_shop_cat, $goods['user_id'], $i, 'wholesale_cat');
                        } else {
                            $category_list = get_category_list(0, 0, $seller_shop_cat, $adminru['ru_id'], 0, 'wholesale_cat');
                        }
                    }
                    $this->smarty->assign('cat_level', $i);
                    $this->smarty->assign('category_list', $category_list);
                    $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
                }
            }

            $this->smarty->assign('category_level', $category_level);

            set_default_filter(); //by wu

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示商品信息页面 */
            return $this->smarty->display('goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入商品 更新商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $goods_sn = isset($_POST['goods_sn']) && !empty($_POST['goods_sn']) ? addslashes(trim($_POST['goods_sn'])) : '';
            $goods_id = isset($_POST['goods_id']) && !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;

            /* 是否处理缩略图 */
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
            if ($code == 'virtual_card') {
                admin_priv('virualcard'); // 检查权限
            } else {
                admin_priv('suppliers_goods_list'); // 检查权限
            }

            /* 检查货号是否重复 */
            if ($goods_sn) {
                $count = Wholesale::where('goods_sn', $goods_sn)
                    ->where('is_delete', 0)
                    ->where('goods_id', '<>', $goods_id)
                    ->count();

                if ($count > 0) {
                    return sys_msg($GLOBALS['_LANG']['goods_sn_exists'], 1, array(), false);
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
                    return sys_msg($this->image->error_msg(), 1, array(), false);
                }

                $goods_img = $original_img;   // 商品图片

                /* 复制一份相册图片 */
                /* 添加判断是否自动生成相册图片 */
                if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                    $img = $original_img;   // 相册图片
                    $pos = strpos(basename($img), '.');
                    $newname = dirname($img) . '/' . $this->image->random_filename() . substr(basename($img), $pos);
                    if (!copy($img, $newname)) {
                        return sys_msg('fail to copy file: ' . realpath('../' . $img), 1, array(), false);
                    }
                    $img = $newname;

                    $gallery_img = $img;
                    $gallery_thumb = $img;
                }

                // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
                if ($proc_thumb && $this->image->gd_version() > 0) {
                    $img_wh = $this->image->get_width_to_height($goods_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                    $GLOBALS['_CFG']['image_width'] = isset($img_wh['image_width']) ? $img_wh['image_width'] : $GLOBALS['_CFG']['image_width'];
                    $GLOBALS['_CFG']['image_height'] = isset($img_wh['image_height']) ? $img_wh['image_height'] : $GLOBALS['_CFG']['image_height'];

                    // 如果设置大小不为0，缩放图片
                    $goods_img = $this->image->make_thumb(array('img' => $goods_img, 'type' => 1), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                    if ($goods_img === false) {
                        return sys_msg($this->image->error_msg(), 1, array(), false);
                    }

                    $gallery_img = $this->image->make_thumb(array('img' => $gallery_img, 'type' => 1), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);

                    if ($gallery_img === false) {
                        return sys_msg($this->image->error_msg(), 1, array(), false);
                    }

                    // 加水印
                    if (intval($GLOBALS['_CFG']['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                        if ($this->image->add_watermark($goods_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                            return sys_msg($this->image->error_msg(), 1, array(), false);
                        }
                        /* 添加判断是否自动生成相册图片 */
                        if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                            if ($this->image->add_watermark($gallery_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                return sys_msg($this->image->error_msg(), 1, array(), false);
                            }
                        }
                    }

                    // 相册缩略图
                    /* 添加判断是否自动生成相册图片 */
                    if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                        if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                            $gallery_thumb = $this->image->make_thumb(array('img' => $img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            if ($gallery_thumb === false) {
                                return sys_msg($this->image->error_msg(), 1, array(), false);
                            }
                        }
                    }
                }

                // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
                if ($proc_thumb && !empty($original_img)) {
                    // 如果设置缩略图大小不为0，生成缩略图
                    if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                        $goods_thumb = $this->image->make_thumb(array('img' => $original_img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                        if ($goods_thumb === false) {
                            return sys_msg($this->image->error_msg(), 1, array(), false);
                        }
                    } else {
                        $goods_thumb = $original_img;
                    }
                }
            }
            /* 商品外链图 end */

            /* 如果没有输入商品货号则自动生成一个商品货号 */
            if (empty($_POST['goods_sn'])) {
                $max_id = Wholesale::max('goods_id');
                $max_id = $max_id ? $max_id + 1 : $goods_id;

                $goods_sn = $this->goodsManageService->generateGoodSn($max_id, 1);
            } else {
                $goods_sn = trim($goods_sn);
            }

            //阶梯价格
            $price_model = isset($_POST['price_model']) && !empty($_POST['price_model']) ? 1 : 0;
            /* 处理商品数据 */
            $goods_price = !empty($_POST['goods_price']) ? trim($_POST['goods_price']) : 0;//供货价
            $goods_price = floatval($goods_price);

            $promote_price = !empty($_POST['promote_price']) ? trim($_POST['promote_price']) : 0;//促销价
            $promote_price = floatval($promote_price);
            $retail_price = !empty($_POST['retail_price']) ? trim($_POST['retail_price']) : 0;//建议零售价
            $retail_price = floatval($retail_price);

            if (!isset($_POST['is_promote'])) {
                $is_promote = 0;
            } else {
                $is_promote = $_POST['is_promote'];
            }

            $start_time = ($is_promote && !empty($_POST['start_time'])) ? local_strtotime($_POST['start_time']) : 0;
            $end_time = ($is_promote && !empty($_POST['end_time'])) ? local_strtotime($_POST['end_time']) : 0;
            $goods_weight = !empty($_POST['goods_weight']) ? $_POST['goods_weight'] * $_POST['weight_unit'] : 0;
            $is_best = isset($_POST['is_best']) && !empty($_POST['is_best']) ? 1 : 0;
            $is_new = isset($_POST['is_new']) && !empty($_POST['is_new']) ? 1 : 0;
            $is_hot = isset($_POST['is_hot']) && !empty($_POST['is_hot']) ? 1 : 0;
            $enabled = isset($_POST['enabled']) && !empty($_POST['enabled']) ? 1 : 0;
            $is_shipping = isset($_POST['is_shipping']) && !empty($_POST['is_shipping']) ? 1 : 0;
            $goods_number = isset($_POST['goods_number']) && !empty($_POST['goods_number']) ? $_POST['goods_number'] : 0;
            $moq = isset($_POST['moq']) && !empty($_POST['moq']) ? $_POST['moq'] : 1;
            $warn_number = isset($_POST['warn_number']) && !empty($_POST['warn_number']) ? $_POST['warn_number'] : 0;
            $goods_type = isset($_POST['goods_type']) && !empty($_POST['goods_type']) ? $_POST['goods_type'] : 0;
            $suppliers_id = $adminru['suppliers_id'];
            $goods_unit = isset($_POST['goods_unit']) ? trim($_POST['goods_unit']) : '个';//商品单位
            $bar_code = isset($_POST['bar_code']) && !empty($_POST['bar_code']) ? trim($_POST['bar_code']) : '';
            $catgory_id = isset($_POST['cat_id']) && !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $keywords = isset($_POST['keywords']) && !empty($_POST['keywords']) ? addslashes($_POST['keywords']) : '';
            $goods_brief = isset($_POST['goods_brief']) && !empty($_POST['goods_brief']) ? addslashes($_POST['goods_brief']) : '';

            if (empty($catgory_id) && !empty($_POST['common_category'])) {
                $catgory_id = intval($_POST['common_category']);
            }

            $brand_id = empty($_POST['brand_id']) ? '' : intval($_POST['brand_id']);

            $store_category = !empty($_POST['store_category']) ? intval($_POST['store_category']) : 0;
            if ($store_category > 0) {
                $catgory_id = $store_category;
            }

            $review_status = 1;//未审核

            $xiangou_num = isset($_POST['xiangou_num']) && !empty($_POST['xiangou_num']) ? intval($_POST['xiangou_num']) : 0;
            $is_xiangou = empty($xiangou_num) ? 0 : 1;
            $xiangou_start_date = (isset($_POST['xiangou_start_date']) && $is_xiangou && !empty($_POST['xiangou_start_date'])) ? local_strtotime($_POST['xiangou_start_date']) : 0;
            $xiangou_end_date = (isset($_POST['xiangou_end_date']) && $is_xiangou && !empty($_POST['xiangou_end_date'])) ? local_strtotime($_POST['xiangou_end_date']) : 0;

            $goods_name = trim($_POST['goods_name']);
            $pinyin = $this->pinyin->Pinyin($goods_name, 'UTF8');

            $insertOther = [
                'goods_name' => $goods_name,
                'goods_sn' => $goods_sn,
                'bar_code' => $bar_code,
                'cat_id' => $catgory_id,
                'brand_id' => $brand_id,
                'goods_price' => $goods_price,
                'retail_price' => $retail_price,
                'is_promote' => $is_promote,
                'promote_price' => $promote_price,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'goods_img' => $goods_img,
                'goods_thumb' => $goods_thumb,
                'original_img' => $original_img,
                'keywords' => $keywords,
                'goods_brief' => $goods_brief,
                'goods_weight' => $goods_weight,
                'goods_number' => $goods_number,
                'warn_number' => $warn_number,
                'is_best' => $is_best,
                'is_new' => $is_new,
                'is_hot' => $is_hot,
                'enabled' => $enabled,
                'is_shipping' => $is_shipping,
                'goods_desc' => $_POST['goods_desc'] ?? '',
                'desc_mobile' => $_POST['desc_mobile'] ?? '',
                'add_time' => gmtime(),
                'last_update' => gmtime(),
                'goods_type' => $goods_type,
                'suppliers_id' => $suppliers_id,
                'review_status' => $review_status,
                'goods_product_tag' => $_POST['goods_product_tag'] ?? '',
                'is_xiangou' => $is_xiangou,
                'xiangou_num' => $xiangou_num,
                'xiangou_start_date' => $xiangou_start_date,
                'pinyin_keyword' => $pinyin,
                'goods_unit' => $goods_unit,
                'price_model' => $price_model,
                'moq' => $moq
            ];

            $freight = isset($_POST['freight']) && !empty($_POST['freight']) ? intval($_POST['freight']) : 0;
            $shipping_fee = isset($_POST['shipping_fee']) && !empty($_POST['shipping_fee']) && $freight == 1 ? floatval($_POST['shipping_fee']) : '0.00';
            $tid = isset($_POST['tid']) && !empty($_POST['tid']) && $_POST['freight'] == 2 ? intval($_POST['tid']) : 0;

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

                $insertOther['goods_cause'] = $goods_cause;
            }

            $goods_id = 0;
            /* 入库 */
            if ($is_insert) {
                if ($code == '') {
                    $insertOther['goods_cause'] = $goods_cause;
                    $insertOther['freight'] = $freight;
                    $insertOther['shipping_fee'] = $shipping_fee;
                    $insertOther['tid'] = $tid;

                    $goods_id = Wholesale::insertGetId($insertOther);
                }
            } else {
                $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

                /* 微分销 */

                $updateOther = [
                    'goods_name' => $goods_name,
                    'goods_sn' => $goods_sn,
                    'bar_code' => $bar_code,
                    'cat_id' => $catgory_id,
                    'brand_id' => $brand_id,
                    'goods_price' => $goods_price,
                    'retail_price' => $retail_price,
                    'is_promote' => $is_promote,
                    'price_model' => $price_model,
                    'goods_unit' => $goods_unit,
                    'is_best' => $is_best,
                    'is_new' => $is_new,
                    'is_hot' => $is_hot,
                    'freight' => $freight,
                    'shipping_fee' => $shipping_fee,
                    'is_xiangou' => $is_xiangou,
                    'xiangou_num' => $xiangou_num,
                    'xiangou_start_date' => $xiangou_start_date,
                    'xiangou_end_date' => $xiangou_end_date,
                    'goods_product_tag' => $_POST['goods_product_tag'] ?? '',
                    'pinyin_keyword' => $pinyin,
                    'goods_cause' => $goods_cause,
                    'promote_price' => $promote_price,
                    'suppliers_id' => $suppliers_id,
                    'moq' => $moq,
                    'review_status' => 1,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'keywords' => $keywords,
                    'goods_brief' => $goods_brief,
                    'goods_weight' => $goods_weight,
                    'goods_number' => $goods_number,
                    'moq' => $moq,
                    'warn_number' => $warn_number,
                    'enabled' => $enabled,
                    'is_shipping' => $is_shipping,
                    'goods_desc' => $_POST['goods_desc'] ?? '',
                    'desc_mobile' => $_POST['desc_mobile'] ?? '',
                    'last_update' => gmtime(),
                    'goods_type' => $goods_type
                ];

                $path = storage_public();
                /* 如果有上传图片，需要更新数据库 */
                if ($goods_img) {
                    $goodsImg = $goods_img ? str_replace($path, '', $goods_img) : '';
                    $originalImg = $original_img ? str_replace($path, '', $original_img) : '';

                    $updateOther['goods_img'] = $goodsImg;
                    $updateOther['original_img'] = $originalImg;
                }
                if ($goods_thumb) {
                    $goodsThumb = $goods_thumb ? str_replace($path, '', $goods_thumb) : '';
                    $updateOther['goods_thumb'] = $goodsThumb;
                }

                Wholesale::where('goods_id', $goods_id)
                    ->update($updateOther);
            }

            /* 处理优惠价格 */
            if (intval($_POST['price_model']) && isset($_POST['volume_number']) && isset($_POST['volume_price'])) {
                $this->wholesaleGoodsManage->handleWholesaleVolumePrice($goods_id, intval($_POST['price_model']), $_POST['volume_number'], $_POST['volume_price'], $_POST['id']);
            }

            if ($goods_id) {
                //商品扩展信息
                $is_delivery = isset($_POST['is_delivery']) && !empty($_POST['is_delivery']) ? intval($_POST['is_delivery']) : 0;
                $is_return = isset($_POST['is_return']) && !empty($_POST['is_return']) ? intval($_POST['is_return']) : 0;
                $is_free = isset($_POST['is_free']) && !empty($_POST['is_free']) ? intval($_POST['is_free']) : 0;

                $extend = WholesaleExtend::where('goods_id', $goods_id)->count();

                if ($extend > 0) {
                    //跟新商品扩展信息
                    WholesaleExtend::where('goods_id', $goods_id)
                        ->update([
                            'is_delivery' => $is_delivery,
                            'is_return' => $is_return,
                            'is_free' => $is_free
                        ]);
                } else {
                    //插入商品扩展信息
                    WholesaleExtend::insert([
                        'goods_id' => $goods_id,
                        'is_delivery' => $is_delivery,
                        'is_return' => $is_return,
                        'is_free' => $is_free
                    ]);
                }

                get_updel_goods_attr($goods_id);
            }

            $extend_arr = array();
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

            WholesaleExtend::where('goods_id', $goods_id)->update($extend_arr);

            if ($is_insert) {
                /* 处理相册图片 by wu */
                $thumb_img_id = session('thumb_img_id' . session('supply_id'), []);//处理添加商品时相册图片串图问题   by kong
                if ($thumb_img_id) {
                    $thumb_img_id = $this->baseRepository->getExplode($thumb_img_id);
                    SuppliersGoodsGallery::where('goods_id', 0)
                        ->whereIn('img_id', $thumb_img_id)
                        ->update([
                            'goods_id' => $goods_id
                        ]);
                }
                session()->forget('thumb_img_id' . session('supply_id'));
            }

            /* 如果有图片，把商品图片加入图片相册 */
            if (!empty($_POST['goods_img_url']) && $is_img_url == 1) {

                /* 重新格式化图片名称 */
                $original_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $original_img, 'source');
                $goods_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $goods_img, 'goods');
                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $goods_id, $goods_thumb, 'thumb');

                $path = storage_public();
                $original_img = $original_img ? str_replace($path, '', $original_img) : '';
                $goods_img = $goods_img ? str_replace($path, '', $goods_img) : '';
                $goods_thumb = $goods_thumb ? str_replace($path, '', $goods_thumb) : '';

                // 处理商品图片
                Wholesale::where('goods_id', $goods_id)
                    ->update([
                        'goods_thumb' => $goods_thumb,
                        'goods_img' => $goods_img,
                        'original_img' => $original_img
                    ]);

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

                    $img = $img ? str_replace($path, '', $img) : '';
                    $gallery_img = $gallery_img ? str_replace($path, '', $gallery_img) : '';
                    $gallery_thumb = $gallery_thumb ? str_replace($path, '', $gallery_thumb) : '';

                    SuppliersGoodsGallery::insert([
                        'goods_id' => $goods_id,
                        'img_url' => $gallery_img,
                        'thumb_url' => $gallery_thumb,
                        'img_original' => $img
                    ]);
                }

                $this->dscRepository->getOssAddFile([$goods_img, $goods_thumb, $original_img, $gallery_img, $gallery_thumb, $img]);
            } else {
                $path = storage_public();
                $goodsImg = $goods_img ? str_replace($path, '', $goods_img) : '';
                $goodsThumb = $goods_thumb ? str_replace($path, '', $goods_thumb) : '';
                $originalImg = $original_img ? str_replace($path, '', $original_img) : '';

                $this->dscRepository->getOssAddFile([$goodsImg, $goodsThumb, $originalImg]);
            }

            /** ************* 处理货品数据 start ************** */

            if ($is_insert) {
                $other['goods_id'] = $goods_id;

                WholesaleProducts::where('goods_id', 0)
                    ->where('admin_id', $admin_id)
                    ->update($other);

                WholesaleGoodsAttr::where('goods_id', 0)
                    ->where('admin_id', $admin_id)
                    ->update($other);
            }

            $where_products = "";
            $goods_model = isset($_POST['goods_model']) && !empty($_POST['goods_model']) ? intval($_POST['goods_model']) : 0;
            $warehouse = isset($_POST['warehouse']) && !empty($_POST['warehouse']) ? intval($_POST['warehouse']) : 0;
            $region = isset($_POST['region']) && !empty($_POST['region']) ? intval($_POST['region']) : 0;

            /* 处理属性 */
            if ((isset($_POST['attr_id_list']) && isset($_POST['attr_value_list'])) || (empty($_POST['attr_id_list']) && empty($_POST['attr_value_list']))) {
                // 取得原有的属性值
                $goods_attr_list = array();

                $attr_res = Attribute::where('cat_id', $goods_type);
                $attr_res = $this->baseRepository->getToArrayGet($attr_res);

                $attr_list = [];
                if ($attr_res) {
                    foreach ($attr_res as $key => $row) {
                        $attr_list[$row['attr_id']] = $row['attr_index'];
                    }
                }

                $res = WholesaleGoodsAttr::where('goods_id', $goods_id);
                $res = $this->baseRepository->getToArrayGet($res);

                if ($res) {
                    foreach ($res as $key => $row) {
                        $goods_attr_list[$row['attr_id']][$row['attr_value']] = [
                            'sign' => 'delete',
                            'goods_attr_id' => $row['goods_attr_id']
                        ];
                    }
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
                if ($goods_attr_list) {
                    foreach ($goods_attr_list as $attr_id => $attr_value_list) {
                        foreach ($attr_value_list as $attr_value => $info) {
                            if ($info['sign'] == 'insert') {
                                WholesaleGoodsAttr::insert([
                                    'attr_id' => $attr_id,
                                    'goods_id' => $goods_id,
                                    'attr_value' => $attr_value,
                                    'attr_sort' => $info['attr_sort'],
                                    'admin_id' => $admin_id
                                ]);
                            } elseif ($info['sign'] == 'update') {
                                WholesaleGoodsAttr::where('goods_attr_id', $info['goods_attr_id'])
                                    ->update([
                                        'attr_sort' => $info['attr_sort']
                                    ]);
                            } else {
                                WholesaleProducts::where('goods_id', $goods_id)
                                    ->whereRaw("FIND_IN_SET('" . $info['goods_attr_id'] . "', REPLACE(goods_attr, '|', ','))")
                                    ->delete();

                                WholesaleGoodsAttr::where('goods_attr_id', $info['goods_attr_id'])->delete();
                            }
                        }
                    }
                }
            }

            if ($is_insert) {
                WholesaleProducts::where('goods_id', 0)
                    ->where('admin_id', $admin_id)
                    ->update(['goods_id' => $goods_id]);

                admin_log($_POST['goods_name'], 'add', 'goods');
            } else {
                admin_log($_POST['goods_name'], 'edit', 'goods');
            }

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
            $goods = Wholesale::where('goods_id', $goods_id);
            $goods = $this->baseRepository->getToArrayFirst($goods);

            /* 货号 */
            if (empty($product['product_sn'])) {
                $product['product_sn'] = array();
            }

            if ($product['product_sn']) {
                foreach ($product['product_sn'] as $key => $value) {
                    //过滤
                    $product['product_number'][$key] = trim($product['product_number'][$key]); //库存
                    $product['product_id'][$key] = isset($product['product_id'][$key]) && !empty($product['product_id'][$key]) ? intval($product['product_id'][$key]) : 0; //货品ID

                    if ($product['product_id'][$key]) {
                        WholesaleProducts::where('product_id', $product['product_id'][$key])
                            ->update([
                                'product_number' => $product['product_number'][$key]
                            ]);
                    } else {
                        //获取规格在商品属性表中的id
                        $id_list = [];
                        $is_spec_list = [];
                        $value_price_list = [];
                        if ($product['attr']) {
                            foreach ($product['attr'] as $attr_key => $attr_value) {
                                /* 检测：如果当前所添加的货品规格存在空值或0 */
                                if (empty($attr_value[$key])) {
                                    continue 2;
                                }

                                $is_spec_list[$attr_key] = 'true';

                                $value_price_list[$attr_key] = $attr_value[$key] . chr(9) . ''; //$key，当前

                                $id_list[$attr_key] = $attr_key;
                            }
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
                        $product_id = WholesaleProducts::insertGetId([
                            'goods_id' => $product['goods_id'],
                            'goods_attr' => $goods_attr,
                            'product_sn' => $value,
                            'product_number' => $product['product_number'][$key]
                        ]);

                        if (!$product_id) {
                            continue;
                        } else {
                            //货品号为空 自动补货品号
                            if (empty($value)) {
                                WholesaleProducts::where('product_id', $product_id)
                                    ->update([
                                        'product_sn' => $goods['goods_sn'] . "g_p" . $product_id
                                    ]);
                            }
                        }
                    }
                }
            }
            /*************** 处理货品数据 end ***************/

            /* 同步前台商品详情价格与商品列表价格一致 end */

            /* 清空缓存 */
            clear_cache_files();

            /* 提示页面 */
            $link = array();

            if ($code == 'virtual_card') {
                $link[1] = array('href' => 'virtual_card.php?act=replenish&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['add_replenish']);
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

                /* 检查权限 */
                admin_priv('suppliers_goods_list');

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
                } /* 设为精品 */
                elseif ($_POST['type'] == 'best') {
                    $other = [
                        'is_best' => 1
                    ];
                } /* 取消精品 */
                elseif ($_POST['type'] == 'not_best') {
                    $other = [
                        'is_best' => 0
                    ];
                } /* 设为新品 */
                elseif ($_POST['type'] == 'new') {
                    $other = [
                        'is_new' => 1
                    ];
                } /* 取消新品 */
                elseif ($_POST['type'] == 'not_new') {
                    $other = [
                        'is_new' => 0
                    ];
                } /* 设为热销 */
                elseif ($_POST['type'] == 'hot') {
                    $other = [
                        'is_hot' => 1
                    ];
                } /* 取消热销 */
                elseif ($_POST['type'] == 'not_hot') {
                    $other = [
                        'is_hot' => 0
                    ];
                } /* 转移到分类 */
                elseif ($_POST['type'] == 'move_to') {
                    $other = [
                        'cat_id' => $_POST['target_cat']
                    ];
                } /* 还原 */
                elseif ($_POST['type'] == 'restore') {
                    $other = [
                        'is_delete' => 0
                    ];

                    /* 记录日志 */
                    admin_log('', 'batch_restore', 'suppliers_goods');
                } /* 删除 */
                elseif ($_POST['type'] == 'drop') {
                    $this->wholesaleGoodsManage->deleteGoods($goods_id);
                    /* 记录日志 */
                    admin_log('', 'batch_remove', 'goods');
                } /* 审核商品 ecmoban模板堂 --zhuo */
                elseif ($_POST['type'] == 'review_to') {
                    $other = [
                        'review_status' => intval($_POST['review_status']),
                        'review_content' => addslashes($_POST['review_content'])
                    ];

                    /* 记录日志 */
                    admin_log('', 'review_to', 'goods');
                } /* 运费模板 */
                elseif ($_POST['type'] == 'goods_transport') {
                    $other = [
                        'freight' => 2,
                        'tid' => intval($_POST['tid'])
                    ];

                    /* 记录日志 */
                    admin_log('', 'batch_edit', 'goods_transport');
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

                if ($_POST['type'] != 'drop') {
                    $this->wholesaleGoodsManage->updateWholesaleGoods($goods_id, $other, $adminru['suppliers_id']);
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            if ($_POST['type'] == 'drop' || $_POST['type'] == 'restore') {
                $link[] = array('href' => 'goods.php?act=trash', 'text' => $GLOBALS['_LANG']['11_goods_trash']);
            } else {
                $link[] = $this->list_link(true, $code);
            }
            return sys_msg($GLOBALS['_LANG']['batch_handle_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 修改默认相册 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'img_default') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = array('content' => '', 'error' => 0, 'massege' => '', 'img_id' => '');

            $admin_id = get_admin_id();
            $img_id = !empty($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : '0';
            if ($img_id > 0) {
                $goods_id = SuppliersGoodsGallery::where('img_id', $img_id)->value('goods_id');
                $goods_id = $goods_id ? $goods_id : 0;

                SuppliersGoodsGallery::where('goods_id', $goods_id)
                    ->increment('img_desc', 1);

                $res = SuppliersGoodsGallery::where('img_id', $img_id)->update(['img_desc' => 1]);

                if ($res) {
                    $img_list = SuppliersGoodsGallery::whereRaw(1);

                    if (empty($goods_id) && session()->has('thumb_img_id' . $admin_id) && session('thumb_img_id' . $admin_id)) {
                        $img_list = $img_list->whereIn('img_id', session('thumb_img_id' . $admin_id));
                    } else {
                        $img_list = $img_list->where('goods_id', $goods_id);
                    }

                    $img_list = $img_list->orderBy('img_desc');

                    $img_list = $this->baseRepository->getToArrayGet($img_list);

                    $img_desc = [];
                    if ($img_list) {
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

                        foreach ($img_list as $k => $v) {
                            $img_desc[] = $v['img_desc'];
                        }
                    }

                    $img_default = $img_desc ? min($img_desc) : 0;

                    $min_img_id = SuppliersGoodsGallery::where('goods_id', $goods_id)
                        ->where('img_desc', $img_default)
                        ->min('img_id');

                    $this->smarty->assign('min_img_id', $min_img_id);
                    $this->smarty->assign('img_list', $img_list);
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['smarty']->fetch('gallery_img.lbi');
                } else {
                    $result['error'] = 2;
                    $result['massege'] = lang('suppliers/goods.edit_fail');
                }
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改默认相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'gallery_album_dialog') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = array('error' => 0, 'message' => '', 'log_type' => '', 'content' => '');
            $content = !empty($_REQUEST['content']) ? addslashes($_REQUEST['content']) : '';

            // 获取相册信息
            $gallery_album_list = GalleryAlbum::where('suppliers_id', $adminru['suppliers_id'])
                ->orderBy('sort_order');
            $gallery_album_list = $this->baseRepository->getToArrayGet($gallery_album_list);

            $this->smarty->assign('gallery_album_list', $gallery_album_list);

            $log_type = !empty($_GET['log_type']) ? trim($_GET['log_type']) : 'image';
            $result['log_type'] = $log_type;
            $this->smarty->assign('log_type', $log_type);

            $pic_album = [];
            if ($gallery_album_list) {
                $album_id = $gallery_album_list[0]['album_id'] ?? 0;
                $pic_album = PicAlbum::where('album_id', $album_id);
                $pic_album = $this->baseRepository->getToArrayGet($pic_album);
            }

            $this->smarty->assign('pic_album', $pic_album);
            $this->smarty->assign('content', $content);
            $result['content'] = $this->smarty->fetch('library/album_dialog.lbi');

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_image') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $img_id = empty($_REQUEST['img_id']) ? 0 : intval($_REQUEST['img_id']);

            /* 删除图片文件 */
            $row = SuppliersGoodsGallery::where('img_id', $img_id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row) {
                $img_url = storage_public($row['img_url']);
                $thumb_url = storage_public($row['thumb_url']);
                $img_original = storage_public($row['img_original']);

                $arr = [];
                if ($row['img_url'] != '' && is_file($img_url) && strpos($row['img_url'], "data/gallery_album") === false) {
                    $arr[] = $row['img_url'];
                    dsc_unlink($img_url);
                }
                if ($row['thumb_url'] != '' && is_file($thumb_url) && strpos($row['img_url'], "data/gallery_album") === false) {
                    $arr[] = $row['thumb_url'];
                    dsc_unlink($thumb_url);
                }
                if ($row['img_original'] != '' && is_file($img_original) && strpos($row['img_url'], "data/gallery_album") === false) {
                    $arr[] = $row['img_original'];
                    dsc_unlink($img_original);
                }

                if (!empty($arr)) {
                    $this->dscRepository->getOssDelFile($arr);
                }
            }

            /* 删除数据 */
            SuppliersGoodsGallery::where('img_id', $img_id)->delete();

            clear_cache_files();
            return make_json_result($img_id);
        }

        /*------------------------------------------------------ */
        //-- 修改商品名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_name') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_name = json_str_iconv(trim($_POST['val']));

            $res = Wholesale::where('goods_id', $goods_id)
                ->update([
                    'goods_name' => $goods_name,
                    'last_update' => gmtime()
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result(stripslashes($goods_name));
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_price') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_price = floatval($_POST['val']);

            if ($goods_price < 0 || $goods_price == 0 && $_POST['val'] != "$goods_price") {
                return make_json_error($GLOBALS['_LANG']['shop_price_invalid']);
            } else {
                $res = Wholesale::where('goods_id', $goods_id)
                    ->update([
                        'goods_price' => $goods_price,
                        'last_update' => gmtime()
                    ]);

                if ($res) {
                    //更新价格需要审核
                    $other = [
                        'review_status' => 1
                    ];
                    $this->wholesaleGoodsManage->updateWholesaleGoods($goods_id, $other, $adminru['suppliers_id']);

                    clear_cache_files();
                    return make_json_result(number_format($goods_price, 2, '.', ''));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品库存数量
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_number') {
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

            $res = Wholesale::where('goods_id', $goods_id)
                ->update([
                    'goods_number' => $goods_num,
                    'last_update' => gmtime()
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($goods_num);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改商品排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $sort_order = intval($_POST['val']);

            $res = Wholesale::where('goods_id', $goods_id)
                ->update([
                    'sort_order' => $sort_order,
                    'last_update' => gmtime()
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($sort_order);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改上架状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_sale') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            $res = Wholesale::where('goods_id', $goods_id)
                ->update([
                    'enabled' => $on_sale,
                    'last_update' => gmtime()
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($on_sale);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除属性
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_product') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $group_attr = empty($_REQUEST['group_attr']) ? '' : $_REQUEST['group_attr'];
            $group_attr = $group_attr ? dsc_decode($group_attr, true) : [];
            $product_id = empty($_REQUEST['product_id']) ? 0 : intval($_REQUEST['product_id']);

            /* 删除数据 */
            WholesaleProducts::where('product_id', $product_id)->delete();

            clear_cache_files();
            make_json_result_too($product_id, 0, '', $group_attr);
        }

        /*--------------------------------------------------------*/
        // 设置属性表格
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'set_attribute_table' || $_REQUEST['act'] == 'wholesale_attribute_query') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_type = empty($_REQUEST['goods_type']) ? 0 : intval($_REQUEST['goods_type']);
            $attr_id_arr = empty($_REQUEST['attr_id']) ? array() : explode(',', $_REQUEST['attr_id']);
            $attr_value_arr = empty($_REQUEST['attr_value']) ? array() : explode(',', $_REQUEST['attr_value']);
            $goods_model = 0; //商品模式
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
                $model_name = lang('suppliers/goods.warehouse');
            } elseif ($goods_model == 2) {
                $model_name = lang('suppliers/goods.region');
            }

            $region_name = RegionWarehouse::where('region_id', $region_id)->value('region_name');

            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('goods_model', $goods_model);
            $this->smarty->assign('model_name', $model_name);

            //商品基本信息
            $goods_info = Wholesale::where('goods_id', $goods_id);
            $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

            $this->smarty->assign('goods_info', $goods_info);

            $attr_arr = [];
            //将属性归类
            if ($attr_id_arr) {
                foreach ($attr_id_arr as $key => $val) {
                    $attr_arr[$val][] = $attr_value_arr[$key];
                }
            }

            $attr_spec = array();
            $attribute_array = array();

            if (count($attr_arr) > 0) {
                //属性数据
                $i = 0;
                foreach ($attr_arr as $key => $val) {
                    $attr_info = Attribute::where('attr_id', $key);
                    $attr_info = $this->baseRepository->getToArrayFirst($attr_info);

                    $attribute_array[$i]['attr_id'] = $key;
                    $attribute_array[$i]['attr_name'] = $attr_info['attr_name'];
                    $attribute_array[$i]['attr_value'] = $val;
                    /* 处理属性图片 start */
                    $attr_values_arr = array();
                    foreach ($val as $k => $v) {

                        $v = trim($v);

                        $data = $this->wholesaleGoodsManage->getWholesaleGoodsAttrId(['attr_id' => $key, 'attr_value' => $v, 'goods_id' => $goods_id], [1, 2], 1);
                        if (!$data) {
                            $max_goods_attr_id = WholesaleGoodsAttr::max('goods_attr_id');
                            $max_goods_attr_id = $max_goods_attr_id ? $max_goods_attr_id : 0;

                            $attr_sort = $max_goods_attr_id + 1;

                            $data['goods_attr_id'] = WholesaleGoodsAttr::insertGetId([
                                'goods_id' => $goods_id,
                                'attr_id' => $key,
                                'attr_value' => $v,
                                'attr_sort' => $attr_sort,
                                'admin_id' => $admin_id
                            ]);

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

        /*------------------------------------------------------ */
        //-- 切换商品类型
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_attribute') {
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
        //-- 修改货品库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_number') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $product_number = intval($_POST['val']);

            /* 修改货品库存 */
            WholesaleProducts::where('product_id', $product_id)
                ->update([
                    'product_number' => $product_number
                ]);

            clear_cache_files();
            return make_json_result($product_number);
        }

        /* ------------------------------------------------------ */
        //-- 修改货品号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_sn') {
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
            WholesaleProducts::where('product_id', $product_id)
                ->update([
                    'product_sn' => $product_sn
                ]);

            clear_cache_files();
            return make_json_result($product_sn);
        }


        /*------------------------------------------------------ */
        //-- 放入回收站
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['id']);

            $goods = Wholesale::where('goods_id', $goods_id);
            $goods = $this->baseRepository->getToArrayFirst($goods);

            if (empty($goods)) {
                return make_json_error(lang('suppliers/goods.goods_empty'));
            }

            $adminru = get_admin_ru_id();
            if ($adminru['suppliers_id'] > 0 && $adminru['suppliers_id'] != $goods['suppliers_id']) {
                return make_json_error(lang('suppliers/goods.illegal_operation'));
            }

            $res = Wholesale::where('goods_id', $goods_id)
                ->update([
                    'is_delete' => 1
                ]);

            if ($res) {
                clear_cache_files();

                admin_log(addslashes($goods['goods_name']), 'trash', 'wholesale'); // 记录日志

                $url = 'goods.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 获取分类列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_select_category_pro') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $cat_level = empty($_REQUEST['cat_level']) ? 0 : intval($_REQUEST['cat_level']);
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $seller_shop_cat = array();
            $this->smarty->assign('cat_id', $cat_id);
            $this->smarty->assign('cat_level', $cat_level + 1);

            $category_list = get_category_list($cat_id, 2, $seller_shop_cat, 0, $cat_level + 1, 'wholesale_cat');

            $this->smarty->assign('category_list', $category_list);
            $result['content'] = $this->smarty->fetch('library/get_select_category.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 设置可导出的商家
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'select_merchants') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = array('error' => 0, 'message' => '', 'content' => '');
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $goods = Wholesale::where('goods_id', $goods_id);
            $goods = $this->baseRepository->getToArrayFirst($goods);

            //商家等级列表
            $seller_grade = SellerGrade::whereRaw(1);
            $seller_grade = $this->baseRepository->getToArrayGet($seller_grade);

            //商家列表
            $seller_list = SellerShopinfo::where('ru_id', '>', 0);
            $seller_list = $this->baseRepository->getToArrayGet($seller_list);

            if ($seller_list) {
                foreach ($seller_list as $k => $v) {
                    $seller_list[$k]['shop_name'] = $this->merchantCommonService->getShopName($v['ru_id'], 1);
                }
            }

            //商家授权
            if ($goods['export_type'] == 0 && !empty($goods['export_type_ext'])) {
                if ($goods['export_type_ext'] == 'all') {
                    foreach ($seller_grade as $k => $v) {
                        $seller_grade[$k]['is_checked'] = 1;
                    }
                } else {
                    $arr = explode(',', $goods['export_type_ext']);
                    foreach ($seller_grade as $k => $v) {
                        if (in_array($v['id'], $arr)) {
                            $seller_grade[$k]['is_checked'] = 1;
                        }
                    }
                }
            } elseif ($goods['export_type'] == 1 && !empty($goods['export_type_ext'])) {
                if ($goods['export_type_ext'] == 'all') {
                    foreach ($seller_list as $k => $v) {
                        $seller_list[$k]['is_checked'] = 1;
                    }
                } else {
                    $arr = explode(',', $goods['export_type_ext']);
                    foreach ($seller_list as $k => $v) {
                        if (in_array($v['ru_id'], $arr)) {
                            $seller_list[$k]['is_checked'] = 1;
                        }
                    }
                }
            }

            $this->smarty->assign('seller_list', $seller_list);
            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('seller_grade', $seller_grade);
            $result['content'] = $GLOBALS['smarty']->fetch('library/select_merchants_list.lbi');

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 设置可导出的商家
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_merchants') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $export_type = isset($_POST['export_type']) ? intval($_POST['export_type']) : 0;
            $grade_ext = isset($_POST['grade_ext']) && is_array($_POST['grade_ext']) ? $_POST['grade_ext'] : [];
            $merchants_ext = isset($_POST['merchants_ext']) && is_array($_POST['merchants_ext']) ? $_POST['merchants_ext'] : [];
            $goods_id = isset($_POST['goods_id']) && !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;


            if ($export_type == 1) {
                if (empty($merchants_ext)) {
                    $export_type_ext = 'all';
                } else {
                    $export_type_ext = implode(',', $merchants_ext);
                }
            } else {
                if (empty($grade_ext)) {
                    $export_type_ext = 'all';
                } else {
                    $export_type_ext = implode(',', $grade_ext);
                }
            }

            Wholesale::where('goods_id', $goods_id)
                ->update([
                    'export_type' => $export_type,
                    'export_type_ext' => $export_type_ext
                ]);

            return sys_msg(lang('suppliers/goods.authorization_success'), 0);
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

            $res = Wholesale::where('goods_id', $goods_id)
                ->update([
                    'standard_goods' => 1
                ]);

            if ($res) {
                clear_cache_files();
                $url = 'goods.php?act=query&' . str_replace('act=standard_goods', '', request()->server('QUERY_STRING'));

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

            $res = Wholesale::where('goods_id', $goods_id)
                ->update([
                    'standard_goods' => 0
                ]);

            if ($res) {
                clear_cache_files();
                $url = 'goods.php?act=query&' . str_replace('act=no_standard_goods', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 修改属性排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_attr_sort') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_attr_id = intval($_REQUEST['id']);
            $attr_sort = intval($_POST['val']);

            /* 修改 */
            $res = WholesaleGoodsAttr::where('goods_attr_id', $goods_attr_id)
                ->update([
                    'attr_sort' => $attr_sort
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($attr_sort);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改相册排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_img_desc') {
            $check_auth = check_authz_json('suppliers_goods_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $img_id = intval($_POST['id']);
            $img_desc = intval($_POST['val']);

            $res = SuppliersGoodsGallery::where('img_id', $img_id)
                ->update([
                    'img_desc' => $img_desc
                ]);

            if ($res) {
                clear_cache_files();
                return make_json_result($img_desc);
            }
        }
    }

    /**
     * 列表链接
     * @param   bool $is_add 是否添加（插入）
     * @param   string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true, $extension_code = '')
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

        return array('href' => $href, 'text' => $text);
    }

    /**
     * 添加链接
     * @param   string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    private function add_link($extension_code = '')
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

        return array('href' => $href, 'text' => $text, 'class' => 'icon-plus');
    }
}
