<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Http;
use App\Libraries\Image;
use App\Libraries\Pinyin;
use App\Models\Brand;
use App\Models\GalleryAlbum;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\GoodsConsumption;
use App\Models\GoodsExtend;
use App\Models\GoodsGallery;
use App\Models\GoodsInventoryLogs;
use App\Models\GoodsLib;
use App\Models\GoodsLibGallery;
use App\Models\GoodsTransport;
use App\Models\GoodsType;
use App\Models\MerchantsShopInformation;
use App\Models\PicAlbum;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsChangelog;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\SuppliersGoodsGallery;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsLibManageService;
use App\Services\Goods\GoodsManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 商品库管理程序
 */
class GoodsLibController extends InitController
{
    protected $goodsManageService;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $goodsLibManageService;
    protected $storeCommonService;

    public function __construct(
        GoodsManageService $goodsManageService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GoodsLibManageService $goodsLibManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->goodsManageService = $goodsManageService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->goodsLibManageService = $goodsLibManageService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('goods', 'admin');

        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        /* 管理员ID */
        $admin_id = get_admin_id();

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        $this->smarty->assign('review_goods', $GLOBALS['_CFG']['review_goods']);
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 商品列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            admin_priv('goods_lib_list');

            lib_get_del_goodsimg_null();
            lib_get_del_goods_gallery();

            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['20_goods_lib']);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('list_type', $_REQUEST['act'] == 'list' ? 'goods' : 'trash');

            $goods_list = lib_goods_list();
            $this->smarty->assign('goods_list', $goods_list['goods']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);
            $this->smarty->assign('full_page', 1);

            /* 排序标记 */
            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $this->smarty->assign('action', 'goods_lib');

            $this->smarty->assign('nowTime', gmtime());
            set_default_filter(); //设置默认筛选

            $this->smarty->assign('cfg', $GLOBALS['_CFG']);
            return $this->smarty->display('goods_lib_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加新商品 编辑商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('goods_lib_list');
            lib_get_del_goodsimg_null();
            lib_get_del_goods_gallery();

            $is_add = $_REQUEST['act'] == 'add'; // 添加还是编辑的标识


            $properties = empty($_REQUEST['properties']) ? 0 : intval($_REQUEST['properties']);
            $this->smarty->assign('properties', $properties);

            /* 如果是安全模式，检查目录是否存在 */
            if (ini_get('safe_mode') == 1 && (!file_exists('../' . IMAGE_DIR . '/' . date('Ym')) || !is_dir('../' . IMAGE_DIR . '/' . date('Ym')))) {
                if (@!mkdir('../' . IMAGE_DIR . '/' . date('Ym'), 0777)) {
                    $warning = sprintf($GLOBALS['_LANG']['safe_mode_warning'], '../' . IMAGE_DIR . '/' . date('Ym'));
                    $this->smarty->assign('warning', $warning);
                }
            } /* 如果目录存在但不可写，提示用户 */ elseif (file_exists('../' . IMAGE_DIR . '/' . date('Ym')) && file_mode_info('../' . IMAGE_DIR . '/' . date('Ym')) < 2) {
                $warning = sprintf($GLOBALS['_LANG']['not_writable_warning'], '../' . IMAGE_DIR . '/' . date('Ym'));
                $this->smarty->assign('warning', $warning);
            }

            $adminru = get_admin_ru_id();

            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            /* 取得商品信息 */
            if ($is_add) {
                $goods = [
                    'goods_id' => 0,
                    'user_id' => 0,
                    'goods_desc' => '',
                    'goods_shipai' => '',
                    'cat_id' => '0',
                    'brand_id' => 0,
                    'is_on_sale' => '1',
                    'is_alone_sale' => '1',
                    'is_shipping' => '0',
                    'other_cat' => [], // 扩展分类
                    'goods_type' => 0, // 商品类型
                    'shop_price' => 0,
                    'market_price' => 0,
                    'goods_weight' => 0,
                    'goods_extend' => ['is_reality' => 0, 'is_return' => 0, 'is_fast' => 0]//by wang
                ];

                /* 图片列表 */
                $img_list = [];
            } else {
                /* 商品信息 */
                $res = GoodsLib::where('goods_id', $goods_id);
                $goods = $this->baseRepository->getToArrayFirst($res);

                if (empty($goods)) {
                    $link[] = ['href' => 'goods_lib.php?act=list', 'text' => $GLOBALS['_LANG']['back_goods_list']];
                    return sys_msg($GLOBALS['_LANG']['lab_not_goods'], 0, $link);
                }

                //图片显示
                $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);

                if (empty($goods)) {
                    /* 默认值 */
                    $goods = [
                        'goods_id' => 0,
                        'user_id' => 0,
                        'goods_desc' => '',
                        'cat_id' => 0,
                        'other_cat' => [], // 扩展分类
                        'goods_type' => 0, // 商品类型
                        'shop_price' => 0,
                        'market_price' => 0,
                        'goods_weight' => 0,
                        'goods_extend' => ['is_reality' => 0, 'is_return' => 0, 'is_fast' => 0]
                    ];
                }

                $goods['goods_extend'] = get_goods_extend($goods['goods_id']);

                /* 获取商品类型存在规格的类型 */
                $specifications = get_goods_type_specifications();
                $goods['specifications_id'] = $goods['goods_type'] ? $specifications[$goods['goods_type']] : [];
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

                /* 商品图片路径 */
                if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 10) && !empty($goods['original_img'])) {
                    $goods['goods_img'] = get_image_path($goods['goods_img']);
                    $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
                }

                /* 图片列表 */
                $res = GoodsLibGallery::where('goods_id', $goods_id)->orderBy('img_desc');
                $img_list = $this->baseRepository->getToArrayGet($res);

                //当前域名协议
                $http = $this->dsc->http();

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
                            if (strpos($gallery_img['img_original'], $http) === false) {
                                $gallery_img['img_original'] = $this->dsc->url() . $gallery_img['img_original'];
                            }

                            $img_list[$key]['img_url'] = $gallery_img['img_original'];

                            $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);
                            if (strpos($gallery_img['thumb_url'], $http) === false) {
                                $gallery_img['thumb_url'] = $this->dsc->url() . $gallery_img['thumb_url'];
                            }

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
                            if (strpos($gallery_img['thumb_url'], $http) === false) {
                                $gallery_img['thumb_url'] = $this->dsc->url() . $gallery_img['thumb_url'];
                            }

                            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                        }
                    }
                }
                $img_desc = [];
                foreach ($img_list as $k => $v) {
                    $img_desc[] = $v['img_desc'];
                }

                @$img_default = min($img_desc);

                $min_img_id = GoodsLibGallery::where('goods_id', $goods_id)
                    ->where('img_desc', $img_default)
                    ->orderBy('img_desc')
                    ->value('img_id');
                $min_img_id = $min_img_id ? $min_img_id : 0;
                $this->smarty->assign('min_img_id', $min_img_id);
            }

            $this->smarty->assign('ru_id', $adminru['ru_id']);

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

            /* 创建 html editor */
            create_html_editor('goods_desc', $goods['goods_desc']);

            $this->smarty->assign('integral_scale', $GLOBALS['_CFG']['integral_scale']);

            //取得商品品牌名称
            $brand_name = Brand::where('brand_id', $goods['brand_id'])->orderBy('sort_order')->value('brand_name');
            $brand_name = $brand_name ? addslashes($brand_name) : '';

            /* 模板赋值 */
            $code = '';
            $this->smarty->assign('code', $code);
            $this->smarty->assign('ur_here', $is_add ? (empty($code) ? $GLOBALS['_LANG']['02_goods_add'] : $GLOBALS['_LANG']['51_virtual_card_add']) : ($_REQUEST['act'] == 'edit' ? $GLOBALS['_LANG']['edit_goods'] : $GLOBALS['_LANG']['copy_goods']));
            $this->smarty->assign('action_link', $this->goodsLibManageService->listLink($is_add, $code));
            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('goods_name_color', $goods_name_style[0]);
            $this->smarty->assign('goods_name_style', $goods_name_style[1]);
            $this->smarty->assign('brand_name', $brand_name);

            $unit_list = $this->goodsManageService->getUnitList();
            $this->smarty->assign('unit_list', $unit_list);

            $this->smarty->assign('weight_unit', $is_add ? '1' : ($goods['goods_weight'] >= 1 ? '1' : '0.001'));
            $this->smarty->assign('cfg', $GLOBALS['_CFG']);
            $this->smarty->assign('form_act', $is_add ? 'insert' : ($_REQUEST['act'] == 'edit' ? 'update' : 'insert'));
            $this->smarty->assign('is_add', true);
            $this->smarty->assign('img_list', $img_list);
            $this->smarty->assign('goods_type_list', goods_type_list($goods['goods_type'], $goods['goods_id'], 'array'));
            $cat_name = GoodsType::where('cat_id', $goods['goods_type'])->value('cat_name');
            $cat_name = $cat_name ? $cat_name : '';
            $this->smarty->assign('goods_type_name', $cat_name);
            $this->smarty->assign('gd', gd_version());
            $this->smarty->assign('thumb_width', $GLOBALS['_CFG']['thumb_width']);
            $this->smarty->assign('thumb_height', $GLOBALS['_CFG']['thumb_height']);

            /* 获取下拉列表 by wu start */
            //设置商品分类
            $level_limit = 3;
            $category_level = [];

            if ($is_add) {
                for ($i = 1; $i <= $level_limit; $i++) {
                    $category_list = [];
                    if ($i == 1) {
                        $category_list = get_category_list();
                    }
                    $this->smarty->assign('cat_level', $i);
                    $this->smarty->assign('category_list', $category_list);
                    $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
                }
            } else {
                $parent_cat_list = get_select_category($goods['cat_id'], 1, true);

                for ($i = 1; $i <= $level_limit; $i++) {
                    $category_list = [];
                    if (isset($parent_cat_list[$i])) {
                        $category_list = get_category_list($parent_cat_list[$i], 0, '', 0, $i);
                    } elseif ($i == 1) {
                        if ($goods['user_id']) {
                            $category_list = get_category_list(0, 0, '', 0, $i);
                        } else {
                            $category_list = get_category_list();
                        }
                    }
                    $this->smarty->assign('cat_level', $i);
                    $this->smarty->assign('category_list', $category_list);
                    $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
                }
            }

            $cat_list = get_goods_lib_cat(0, $goods['cat_id'], false);
            $this->smarty->assign('goods_lib_cat', $cat_list);
            $this->smarty->assign('category_level', $category_level);
            /* 获取下拉列表 by wu end */

            set_default_filter($goods_id, 0, 0); //设置默认筛选

            /* 显示商品信息页面 */
            return $this->smarty->display('goods_lib_info.dwt');
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
            $seller_shop_cat = seller_shop_cat($goods['user_id']);

            $this->smarty->assign('cat_id', $cat_id);
            $this->smarty->assign('cat_level', $cat_level + 1);
            $this->smarty->assign('category_list', get_category_list($cat_id, 2, $seller_shop_cat, $goods['user_id'], $cat_level + 1));
            $result['content'] = $this->smarty->fetch('library/get_select_category.lbi');
            return response()->json($result);
        } /* 设置常用分类 */
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

        /*------------------------------------------------------ */
        //-- 插�        �商品 更新商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

            /* 是否处理缩略图 */
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;

            admin_priv('goods_lib_list'); // 检查权限

            /* 插入还是更新的标识 */
            $is_insert = $_REQUEST['act'] == 'insert';

            $original_img = empty($_REQUEST['original_img']) ? '' : trim($_REQUEST['original_img']);
            $goods_img = empty($_REQUEST['goods_img']) ? '' : trim($_REQUEST['goods_img']);
            $goods_thumb = empty($_REQUEST['goods_thumb']) ? '' : trim($_REQUEST['goods_thumb']);

            /* 商品外链图 start */
            $is_img_url = empty($_REQUEST['is_img_url']) ? 0 : intval($_REQUEST['is_img_url']);
            $_POST['goods_img_url'] = isset($_POST['goods_img_url']) && !empty($_POST['goods_img_url']) ? trim($_POST['goods_img_url']) : '';

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
                if ($proc_thumb && $image->gd_version() > 0) {
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
                                if ($image->add_watermark($img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
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

            $goods_img_id = !empty($_REQUEST['img_id']) ? $_REQUEST['img_id'] : ''; //相册

            /* 处理商品数据 */
            $shop_price = !empty($_POST['shop_price']) ? trim($_POST['shop_price']) : 0;
            $shop_price = floatval($shop_price);
            $market_price = !empty($_POST['market_price']) ? trim($_POST['market_price']) : 0;
            $market_price = floatval($market_price);
            $cost_price = !empty($_POST['cost_price']) ? trim($_POST['cost_price']) : 0;
            $cost_price = floatval($cost_price);
            $review_status = isset($_POST['review_status']) ? intval($_POST['review_status']) : 5;
            $review_content = isset($_POST['review_content']) && !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';
            $goods_weight = !empty($_POST['goods_weight']) ? $_POST['goods_weight'] * $_POST['weight_unit'] : 0;
            $bar_code = isset($_POST['bar_code']) && !empty($_POST['bar_code']) ? trim($_POST['bar_code']) : '';
            $_POST['goods_name_color'] = isset($_POST['goods_name_color']) ? $_POST['goods_name_color'] : '';
            $_POST['goods_name_style'] = isset($_POST['goods_name_style']) ? $_POST['goods_name_style'] : '';
            $goods_name_style = $_POST['goods_name_color'] . '+' . $_POST['goods_name_style'];
            $other_catids = isset($_POST['other_catids']) ? trim($_POST['other_catids']) : '';

            $lib_cat_id = isset($_POST['lib_cat_id']) ? intval($_POST['lib_cat_id']) : 0;
            $is_on_sale = isset($_POST['is_on_sale']) ? intval($_POST['is_on_sale']) : 0;
            $catgory_id = empty($_POST['cat_id']) ? '' : intval($_POST['cat_id']);

            $keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : '';
            $goods_brief = isset($_POST['goods_brief']) ? trim($_POST['goods_brief']) : '';
            $goods_desc = isset($_POST['goods_desc']) ? trim($_POST['goods_desc']) : '';
            $desc_mobile = isset($_POST['desc_mobile']) ? trim($_POST['desc_mobile']) : '';

            //常用分类 by wu
            if (empty($catgory_id) && !empty($_POST['common_category'])) {
                $catgory_id = intval($_POST['common_category']);
            }

            $brand_id = empty($_POST['brand_id']) ? '' : intval($_POST['brand_id']);

            $adminru = get_admin_ru_id();

            $model_price = isset($_POST['model_price']) ? intval($_POST['model_price']) : 0;
            $model_inventory = isset($_POST['model_inventory']) ? intval($_POST['model_inventory']) : 0;
            $model_attr = isset($_POST['model_attr']) ? intval($_POST['model_attr']) : 0;
            $goods_name = trim($_POST['goods_name']);
            //by guan start
            $pin = new Pinyin();
            $pinyin = $pin->Pinyin($goods_name, 'UTF8');
            //by guan end

            /* 入库 */
            if ($is_insert) {
                $data = [
                    'goods_name' => $goods_name,
                    'goods_name_style' => $goods_name_style,
                    'bar_code' => $bar_code,
                    'cat_id' => $catgory_id,
                    'lib_cat_id' => $lib_cat_id,
                    'brand_id' => $brand_id,
                    'shop_price' => $shop_price,
                    'market_price' => $market_price,
                    'cost_price' => $cost_price,
                    'goods_img' => $goods_img,
                    'goods_thumb' => $goods_thumb,
                    'original_img' => $original_img,
                    'keywords' => $keywords,
                    'goods_brief' => $goods_brief,
                    'goods_weight' => $goods_weight,
                    'goods_desc' => $goods_desc,
                    'desc_mobile' => $desc_mobile,
                    'add_time' => gmtime(),
                    'last_update' => gmtime(),
                    'pinyin_keyword' => $pinyin,
                    'is_on_sale' => $is_on_sale
                ];
                $goods_id = GoodsLib::insertGetId($data);

            } else {
                $_REQUEST['goods_id'] = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
                $goods_id = $_REQUEST['goods_id'];

                $data = [
                    'goods_name' => $goods_name,
                    'goods_name_style' => $goods_name_style,
                    'bar_code' => $bar_code,
                    'cat_id' => $catgory_id,
                    'lib_cat_id' => $lib_cat_id,
                    'brand_id' => $brand_id,
                    'shop_price' => $shop_price,
                    'market_price' => $market_price,
                    'cost_price' => $cost_price,
                    'keywords' => $keywords,
                    'goods_brief' => $goods_brief,
                    'goods_weight' => $goods_weight,
                    'goods_desc' => $goods_desc,
                    'desc_mobile' => $desc_mobile,
                    'last_update' => gmtime(),
                    'pinyin_keyword' => $pinyin,
                    'is_on_sale' => $is_on_sale
                ];

                /* 如果有上传图片，需要更新数据库 */
                if ($goods_img) {
                    $data['goods_img'] = $goods_img;
                    $data['original_img'] = $original_img;
                }
                if ($goods_thumb) {
                    $data['goods_thumb'] = $goods_thumb;
                }
                if ($code != '') {
                    $data['is_real'] = 0;
                    $data['extension_code'] = $code;
                }

                GoodsLib::where('goods_id', $_REQUEST['goods_id'])->update($data);
            }
            $goods_id = $goods_id ? $goods_id : 0;

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
            GoodsExtend::where('goods_id', $goods_id)->update($extend_arr);
            //扩展信息 by wu end

            /* 记录日志 */
            if ($is_insert) {
                admin_log($_POST['goods_name'], 'add', 'goods_lib');
            } else {
                admin_log($_POST['goods_name'], 'edit', 'goods_lib');
            }

            if ($is_insert) {
                /* 处理扩展分类 */
                if (!empty($other_catids)) {
                    $other_catids = $this->baseRepository->getExplode($other_catids);
                    $data = ['goods_id' => $goods_id];
                    GoodsCat::where('goods_id', 0)->whereIn('cat_id', $other_catids)->update($data);
                }

                /* 处理相册图片 by wu */
                $thumb_img_id = session('thumb_img_id' . session('admin_id'));//处理添加商品时相册图片串图问题   by kong
                if ($thumb_img_id) {
                    $thumb_img_id = $this->baseRepository->getExplode($thumb_img_id);
                    $data = ['goods_id' => $goods_id];
                    GoodsLibGallery::where('goods_id', 0)->whereIn('img_id', $thumb_img_id)->update($data);
                }
                session()->forget('thumb_img_id' . session('admin_id'));
            }

            /* 如果有图片，把商品图片加入图片相册 */
            if (!empty($_POST['goods_img_url']) && $is_img_url == 1) {
                /* 重新格式化图片名称 */
                $original_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $original_img, 'source');
                $goods_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $goods_img, 'goods');
                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $goods_id, $goods_thumb, 'thumb');

                // 处理商品图片
                $data = [
                    'goods_thumb' => $goods_thumb,
                    'goods_img' => $goods_img,
                    'original_img' => $original_img
                ];
                GoodsLib::where('goods_id', $goods_id)->update($data);

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

                    $data = [
                        'goods_id' => $goods_id,
                        'img_url' => $gallery_img,
                        'thumb_url' => $gallery_thumb,
                        'img_original' => $img
                    ];
                    GoodsLibGallery::insert($data);
                }

                $this->dscRepository->getOssAddFile([$goods_img, $goods_thumb, $original_img, $gallery_img, $gallery_thumb, $img]);
            } else {
                $this->dscRepository->getOssAddFile([$goods_img, $goods_thumb, $original_img]);
            }

            /* 清空缓存 */
            clear_cache_files();

            /* 提示页面 */
            $link = [];

            if ($is_insert) {
                $link[2] = $this->goodsLibManageService->addLink($code);
            }
            $link[3] = $this->goodsLibManageService->listLink($is_insert, $code);

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
                /* 上架 */
                if ($_POST['type'] == 'on_sale') {
                    /* 检查权限 */
                    admin_priv('goods_lib_list');
                    $this->goodsLibManageService->libUpdateGoods($goods_id, 'is_on_sale', '1');
                } /* 下架 */
                elseif ($_POST['type'] == 'not_on_sale') {
                    /* 检查权限 */
                    admin_priv('goods_lib_list');
                    $this->goodsLibManageService->libUpdateGoods($goods_id, 'is_on_sale', '0');
                } /* 删除 */
                elseif ($_POST['type'] == 'drop') {
                    /* 检查权限 */
                    admin_priv('goods_lib_list');

                    $this->goodsLibManageService->libDeleteGoods($goods_id);

                    /* 记录日志 */
                    admin_log('', 'batch_remove', 'goods_lib');
                } /* 批量导入 */
                elseif ($_POST['type'] == 'batch_import') {
                    $standard_goods = request()->input('standard_goods', 0);//1为标准库，0为本地库
                    $checkboxes = request()->input('checkboxes', []);
                    if ($checkboxes) {
                        $goods_list = GoodsLib::select('goods_id', 'goods_name')->whereIn("goods_id", $checkboxes);
                        $goods_list = $this->baseRepository->getToArrayGet($goods_list);
                        $this->smarty->assign('goods_list', $goods_list);
                    }

                    $this->smarty->assign('standard_goods', $standard_goods);
                    return $this->smarty->display('goods_lib_batch.dwt');
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            if ($_POST['type'] == 'drop') {
                $link[] = ['href' => 'goods_lib.php?act=list', 'text' => $GLOBALS['_LANG']['20_goods_lib']];
            } else {
                $link[] = $this->goodsLibManageService->listLink(true, $code);
            }
            return sys_msg($GLOBALS['_LANG']['batch_handle_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 修改商品名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_name') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_name = json_str_iconv(trim($_POST['val']));

            $data = [
                'goods_name' => $goods_name,
                'last_update' => gmtime()
            ];
            $res = GoodsLib::where('goods_id', $goods_id)->update($data);


            if ($res > 0) {
                clear_cache_files();
                return make_json_result(stripslashes($goods_name));
            }
        } elseif ($_REQUEST['act'] == 'check_goods_sn') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_REQUEST['goods_id']);
            $goods_sn = htmlspecialchars(json_str_iconv(trim($_REQUEST['goods_sn'])));

            /* 检查是否重复 */
            $res = GoodsLib::where('goods_sn', $goods_sn)->where('goods_id', '<>', $goods_id)->count();
            if ($res > 0) {
                return make_json_error($GLOBALS['_LANG']['goods_sn_exists']);
            }
            if (!empty($goods_sn)) {
                $res = Products::where('product_sn', $goods_sn)->count();
                if ($res > 0) {
                    return make_json_error($GLOBALS['_LANG']['goods_sn_exists']);
                }
            }
            return make_json_result('');
        }

        /*------------------------------------------------------ */
        //-- 修改商品价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_goods_price') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $goods_price = floatval($_POST['val']);
            $price_rate = floatval($GLOBALS['_CFG']['market_price_rate'] * $goods_price);

            if ($goods_price < 0 || $goods_price == 0 && $_POST['val'] != "$goods_price") {
                return make_json_error($GLOBALS['_LANG']['shop_price_invalid']);
            } else {
                $data = [
                    'shop_price' => $goods_price,
                    'market_price' => $price_rate,
                    'last_update' => gmtime()
                ];
                $res = GoodsLib::where('goods_id', $goods_id)->update($data);
                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result(number_format($goods_price, 2, '.', ''));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 修改上架状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_on_sale') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $on_sale = intval($_POST['val']);

            $data = [
                'is_on_sale' => $on_sale,
                'last_update' => gmtime()
            ];
            $res = GoodsLib::where('goods_id', $goods_id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result($on_sale);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改相册排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_img_desc') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $img_id = intval($_POST['id']);
            $img_desc = intval($_POST['val']);

            $data = ['img_desc' => $img_desc];
            $res = GoodsLibGallery::where('img_id', $img_id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result($img_desc);
            }
        } elseif ($_REQUEST['act'] == 'main_dsc') {
            $data = read_static_cache('seller_goods_str');
            if ($data === false) {
                $shop_url = urlencode($this->dsc->url());
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

                $shop_country = Region::where('region_id', $shop_country)->value('region_name');
                $shop_country = $shop_country ? $shop_country : '';

                $shop_province = Region::where('region_id', $shop_province)->value('region_name');
                $shop_province = $shop_province ? $shop_province : '';

                $shop_city = Region::where('region_id', $shop_city)->value('region_name');
                $shop_city = $shop_city ? $shop_city : '';

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
        //-- 修改商品排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = intval($_POST['id']);
            $sort_order = intval($_POST['val']);

            $data = [
                'sort_order' => $sort_order,
                'last_update' => gmtime()
            ];
            $res = GoodsLib::where('goods_id', $goods_id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result($sort_order);
            }
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $goods_list = lib_goods_list();
            $this->smarty->assign('code', $code);
            $this->smarty->assign('goods_list', $goods_list['goods']);
            $this->smarty->assign('filter', $goods_list['filter']);
            $this->smarty->assign('record_count', $goods_list['record_count']);
            $this->smarty->assign('page_count', $goods_list['page_count']);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);

            /* 排序标记 */
            $sort_flag = sort_flag($goods_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 获取商品类型存在规格的类型 */
            $specifications = get_goods_type_specifications();
            $this->smarty->assign('specifications', $specifications);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('nowTime', gmtime());

            set_default_filter(); //设置默认筛选

            return make_json_result(
                $this->smarty->fetch('goods_lib_list.dwt'),
                '',
                ['filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除商品库商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $goods_id = intval($_REQUEST['id']);

            /* 取得商品信息 */
            $res = GoodsLib::where('goods_id', $goods_id);
            $goods = $this->baseRepository->getToArrayFirst($res);

            if (empty($goods)) {
                return make_json_error($GLOBALS['_LANG']['goods_not_exist']);
            }

            $arr = [];
            /* 删除商品图片和轮播图片 */
            if (!empty($goods['goods_thumb']) && strpos($goods['goods_thumb'], "data/gallery_album") === false) {
                $arr[] = $goods['goods_thumb'];
                @unlink('../' . $goods['goods_thumb']);
            }
            if (!empty($goods['goods_img']) && strpos($goods['goods_img'], "data/gallery_album") === false) {
                $arr[] = $goods['goods_img'];
                @unlink('../' . $goods['goods_img']);
            }
            if (!empty($goods['original_img']) && strpos($goods['original_img'], "data/gallery_album") === false) {
                $arr[] = $goods['original_img'];
                @unlink('../' . $goods['original_img']);
            }
            if (!empty($arr)) {
                $this->dscRepository->getOssDelFile($arr);
            }

            /* 检查权限 */
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $res = GoodsLib::where('goods_id', $goods_id)->delete();
            if ($res > 0) {
                //删除商品扩展信息
                GoodsExtend::where('goods_id', $goods_id)->delete();
                //删除商品扩展信息(上面一个在新增的时候没有用到)
                GoodsCat::where('goods_id', $goods_id)->delete();

                /* 删除商品相册 */
                $res = GoodsLibGallery::where('goods_id', $goods_id);
                $res = $this->baseRepository->getToArrayGet($res);
                foreach ($res as $row) {
                    $arr = [];
                    if (!empty($row['img_url']) && strpos($row['img_url'], "data/gallery_album") === false) {
                        $arr[] = $row['img_url'];
                        @unlink('../' . $row['img_url']);
                    }
                    if (!empty($row['thumb_url']) && strpos($row['thumb_url'], "data/gallery_album") === false) {
                        $arr[] = $row['thumb_url'];
                        @unlink('../' . $row['thumb_url']);
                    }
                    if (!empty($row['img_original']) && strpos($row['img_original'], "data/gallery_album") === false) {
                        $arr[] = $row['img_original'];
                        @unlink('../' . $row['img_original']);
                    }
                    if (!empty($arr)) {
                        $this->dscRepository->getOssDelFile($arr);
                    }
                }

                clear_cache_files();

                $url = 'goods_lib.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
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
            $res = GoodsLibGallery::whereRaw(1);
            if ($goods_id > 0) {
                /* 图片列表 */
                $res = $res->where('goods_id', $goods_id);
            } else {
                $img_id = session('thumb_img_id' . session('admin_id'));

                if ($img_id) {
                    $img_id = $this->baseRepository->getExplode($img_id);
                    $res = $res->whereIn('img_id', $img_id);

                }
                $res = $res->where('goods_id', '');
            }
            $res = $res->orderBy('img_desc', 'ASC');
            $img_list = $this->baseRepository->getToArrayGet($res);
            /* 格式化相册图片路径 */
            if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                foreach ($img_list as $key => $gallery_img) {
                    $gallery_img[$key]['img_url'] = get_image_path($gallery_img['img_original']);
                    $gallery_img[$key]['thumb_url'] = get_image_path($gallery_img['img_original']);
                }
            } else {
                foreach ($img_list as $key => $gallery_img) {
                    $gallery_img[$key]['thumb_url'] = '../' . (empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']);
                }
            }
            $goods['goods_id'] = $goods_id;
            $this->smarty->assign('img_list', $img_list);
            $img_desc = [];
            foreach ($img_list as $k => $v) {
                $img_desc[] = $v['img_desc'];
            }
            $img_default = min($img_desc);

            $min_img_id = GoodsGallery::where('goods_id', $goods_id)
                ->where('img_desc', $img_default)
                ->orderBy('img_desc')
                ->value('img_id');
            $min_img_id = $min_img_id ? $min_img_id : 0;

            $this->smarty->assign('min_img_id', $min_img_id);
            $this->smarty->assign('goods', $goods);
            $result['error'] = '2';
            $result['content'] = $GLOBALS['smarty']->fetch('gallery_img.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改默认相册 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'img_default') {
            $result = ['content' => '', 'error' => 0, 'massege' => '', 'img_id' => ''];
            $img_id = !empty($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : '0';

            /* 是否处理缩略图 */
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;

            if ($img_id > 0) {
                $res = GoodsLibGallery::where('img_id', $img_id);
                $goods_gallery = $this->baseRepository->getToArrayFirst($res);
                $goods_id = $goods_gallery['goods_id'];

                /*获取最小的排序*/
                $least_img_desc = GoodsLibGallery::where('goods_id', $goods_id)->min('img_desc');
                $least_img_desc = $least_img_desc ? $least_img_desc : 1;
                /*排序互换*/
                $data = ['img_desc' => $goods_gallery['img_desc']];
                GoodsLibGallery::where('img_desc', $least_img_desc)
                    ->where('goods_id', $goods_id)
                    ->update($data);

                $data = ['img_desc' => $least_img_desc];
                $res = GoodsLibGallery::where('img_id', $img_id)->update($data);
                if (isset($res)) {
                    $res = GoodsLibGallery::whereRaw(1);
                    if ($goods_id > 0) {
                        $res = $res->where('goods_id', $goods_id);
                    } else {
                        $img_id_attr = $this->baseRepository->getExplode(session('thumb_img_id' . session('admin_id')));
                        $res = $res->where('goods_id', 0)->whereIn('img_id', $img_id_attr);
                    }
                    $res = $res->orderBy('img_desc', 'ASC');
                    $img_list = $this->baseRepository->getToArrayGet($res);
                    /* 格式化相册图片路径 */
                    if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                        foreach ($img_list as $key => $gallery_img) {
                            $img_list[$key] = $gallery_img;
                            if (!empty($gallery_img['external_url'])) {
                                $img_list[$key]['img_url'] = get_image_path($gallery_img['external_url']);
                                $img_list[$key]['thumb_url'] = get_image_path($gallery_img['external_url']);
                            } else {
                                $img_list[$key]['img_url'] = get_image_path($gallery_img['img_original']);
                                $img_list[$key]['thumb_url'] = get_image_path($gallery_img['img_original']);
                            }
                        }
                    } else {
                        foreach ($img_list as $key => $gallery_img) {
                            $img_list[$key] = $gallery_img;
                            if (!empty($gallery_img['external_url'])) {
                                $img_list[$key]['img_url'] = $gallery_img['external_url'];
                                $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                            } else {
                                if ($proc_thumb) {
                                    $img_list[$key]['thumb_url'] = get_image_path((empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']));
                                } else {
                                    $img_list[$key]['thumb_url'] = get_image_path((empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']));
                                }
                            }
                        }
                    }
                    $img_desc = [];

                    if (!empty($img_list)) {
                        foreach ($img_list as $k => $v) {
                            $img_desc[] = $v['img_desc'];
                        }
                    }
                    if (!empty($img_desc)) {
                        $img_default = min($img_desc);
                    }

                    $min_img_id = GoodsLibGallery::where('goods_id', $goods_id)
                        ->where('img_desc', $img_default)
                        ->orderBy('img_desc')
                        ->value('img_id');
                    $min_img_id = $min_img_id ? $min_img_id : 0;
                    $this->smarty->assign('min_img_id', $min_img_id);
                    $this->smarty->assign('img_list', $img_list);
                    $result['error'] = 1;
                    $result['content'] = $this->smarty->fetch('library/gallery_img.lbi');
                } else {
                    $result['error'] = 2;
                    $result['massege'] = $GLOBALS['_LANG']['modify_failure'];
                }
            }
            return response()->json($result);
        } elseif ($_REQUEST['act'] == 'remove_consumption') {
            $result = ['error' => 0, 'massege' => '', 'con_id' => ''];

            $con_id = !empty($_REQUEST['con_id']) ? intval($_REQUEST['con_id']) : '0';
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : '0';
            if ($con_id > 0) {
                $res = GoodsConsumption::where('id', $con_id)->where('goods_id', $goods_id)->delete();
                if ($res > 0) {
                    $result['error'] = 2;
                    $result['con_id'] = $con_id;
                }
            } else {
                $result['error'] = 1;
                $result['massege'] = $GLOBALS['_LANG']['select_delete_target'];
            }
            return response()->json($result);
        } // mobile商品详情 添加图片 qin
        elseif ($_REQUEST['act'] == 'gallery_album_dialog') {
            $result = ['error' => 0, 'message' => '', 'log_type' => '', 'content' => ''];

            // 获取相册信息 qin
            $res = GalleryAlbum::where('ru_id', 0)->orderBy('sort_order');
            $gallery_album_list = $this->baseRepository->getToArrayGet($res);
            $this->smarty->assign('gallery_album_list', $gallery_album_list);

            $log_type = !empty($_GET['log_type']) ? trim($_GET['log_type']) : 'image';
            $result['log_type'] = $log_type;
            $this->smarty->assign('log_type', $log_type);

            $res = PicAlbum::where('ru_id', 0);
            $res = $this->baseRepository->getToArrayGet($res);
            $this->smarty->assign('pic_album', $res);
            $result['content'] = $this->smarty->fetch('library/album_dialog.lbi');

            return response()->json($result);
        } // 异步查询相册的图片 qin
        elseif ($_REQUEST['act'] == 'gallery_album_pic') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $album_id = !empty($_GET['album_id']) ? intval($_GET['album_id']) : 0;
            if (empty($album_id)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $res = PicAlbum::where('album_id', $album_id);
            $res = $this->baseRepository->getToArrayGet($res);
            $this->smarty->assign('pic_album', $res);
            $result['content'] = $this->smarty->fetch('library/album_pic.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 扫码�        �库 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'scan_code') {
            $check_auth = check_authz_json('goods_lib');
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
                if (strpos($data['result']['grossweight'], $GLOBALS['_LANG']['unit_kg']) !== false) {
                    $goods_weight = floatval(str_replace($GLOBALS['_LANG']['unit_kg'], '', $data['result']['grossweight']));
                } elseif (strpos($data['result']['grossweight'], $GLOBALS['_LANG']['unit_g']) !== false) {
                    $goods_weight = floatval(str_replace($GLOBALS['_LANG']['unit_kg'], '', $data['result']['grossweight'])) / 1000;
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
        //-- 删除图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_image') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $img_id = empty($_REQUEST['img_id']) ? 0 : intval($_REQUEST['img_id']);

            /* 删除图片文件 */
            $res = GoodsLibGallery::where('img_id', $img_id);
            $row = $this->baseRepository->getToArrayFirst($res);

            $img_url = storage_public($row['img_url']);
            $thumb_url = storage_public($row['thumb_url']);
            $img_original = storage_public($row['img_original']);
            $arr = [];
            if ($row['img_url'] != '' && is_file($img_url) && strpos($row['img_url'], "data/gallery_album") === false) {
                $arr[] = $row['img_url'];
                @unlink($img_url);
            }
            if ($row['thumb_url'] != '' && is_file($thumb_url) && strpos($row['img_url'], "data/gallery_album") === false) {
                $arr[] = $row['thumb_url'];
                @unlink($thumb_url);
            }
            if ($row['img_original'] != '' && is_file($img_original) && strpos($row['img_url'], "data/gallery_album") === false) {
                $arr[] = $row['img_original'];
                @unlink($img_original);
            }
            if (!empty($arr)) {
                $this->dscRepository->getOssDelFile($arr);
            }

            /* 删除数据 */
            GoodsLibGallery::where('img_id', $img_id)->delete();
            clear_cache_files();
            return make_json_result($img_id);
        }

        /*------------------------------------------------------ */
        //-- 导�        �商家商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'import_seller_goods') {
            admin_priv('goods_lib_list');
            $action_link = ['href' => 'goods_lib.php?act=list', 'text' => $GLOBALS['_LANG']['goods_lib_list']];
            $this->smarty->assign('action_link', $action_link);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['import_seller_goods']);
            $res = MerchantsShopInformation::select('user_id');
            $seller_ids = $this->baseRepository->getToArrayGet($res);
            $seller_ids = $this->baseRepository->getFlatten($seller_ids);

            foreach ($seller_ids as $k => $v) {
                $seller_list[$k]['shop_name'] = $this->merchantCommonService->getShopName($v, 1);
                $seller_list[$k]['user_id'] = $v;
            }

            $self = [
                'self_name' => $this->merchantCommonService->getShopName($adminru['ru_id'], 1),
                'self_id' => $adminru['ru_id'],
            ];

            $this->smarty->assign('self', $self);
            $this->smarty->assign('seller_list', $seller_list);
            return $this->smarty->display('goods_lib_import.dwt');
        }

        /*------------------------------------------------------ */
        //-- 导�        �商家商品执行程序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'import_action') {
            admin_priv('goods_lib_list');
            $user_id = $_REQUEST['user_id'] ? intval($_REQUEST['user_id']) : 0;
            $record_count = Goods::where('user_id', $user_id)->count();
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['import_seller_goods']);
            $this->smarty->assign('record_count', $record_count);
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('page', 1);

            return $this->smarty->display('import_action_list.dwt');
        }
        /*------------------------------------------------------ */
        //-- 导�        �商家商品执行程序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'import_action_list') {
            admin_priv('goods_lib_list');

            $user_id = isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;


            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            $goods_list = $this->goodsLibManageService->getImportGoodsList($user_id);

            $goods_list = $this->dsc->page_array($page_size, $page, $goods_list);
            $result['list'] = isset($goods_list['list']) && $goods_list['list'] ? $goods_list['list'][0] : [];

            if ($result['list']) {
                $res = Goods::where('user_id', $user_id)->where('goods_id', $result['list']['goods_id']);
                $goods_info = $this->baseRepository->getToArrayFirst($res);
                $res = GoodsLib::where('lib_goods_id', $goods_info['goods_id'])->count();

                if ($res < 1) {
                    $goods_thumb = $this->goodsLibManageService->copyImg($goods_info['goods_thumb']);
                    $goods_thumb = $goods_thumb ? $goods_thumb : '';
                    $goods_img = $this->goodsLibManageService->copyImg($goods_info['goods_img']);
                    $goods_img = $goods_img ? $goods_img : '';
                    $original_img = $this->goodsLibManageService->copyImg($goods_info['original_img']);
                    $original_img = $original_img ? $original_img : '';
                    $goods_info['cat_id'] = $goods_info['cat_id'] ? $goods_info['cat_id'] : 0;
                    $goods_info['bar_code'] = $goods_info['bar_code'] ? $goods_info['bar_code'] : '';
                    $goods_info['goods_name'] = $goods_info['goods_name'] ? $goods_info['goods_name'] : '';
                    $goods_info['goods_name_style'] = $goods_info['goods_name_style'] ? $goods_info['goods_name_style'] : '';
                    $goods_info['brand_id'] = $goods_info['brand_id'] ? $goods_info['brand_id'] : 0;
                    $goods_info['goods_weight'] = $goods_info['goods_weight'] ? $goods_info['goods_weight'] : 0;
                    $goods_info['market_price'] = $goods_info['market_price'] ? $goods_info['market_price'] : 0;
                    $goods_info['cost_price'] = $goods_info['cost_price'] ? $goods_info['cost_price'] : 0;
                    $goods_info['shop_price'] = $goods_info['shop_price'] ? $goods_info['shop_price'] : 0;
                    $goods_info['keywords'] = $goods_info['keywords'] ? $goods_info['keywords'] : '';
                    $goods_info['goods_brief'] = $goods_info['goods_brief'] ? $goods_info['goods_brief'] : '';
                    $goods_info['goods_desc'] = $goods_info['goods_desc'] ? $goods_info['goods_desc'] : '';
                    $goods_info['desc_mobile'] = $goods_info['desc_mobile'] ? $goods_info['desc_mobile'] : '';
                    $goods_info['is_real'] = $goods_info['is_real'] ? $goods_info['is_real'] : 0;
                    $goods_info['extension_code'] = $goods_info['extension_code'] ? $goods_info['extension_code'] : '';
                    $goods_info['sort_order'] = $goods_info['sort_order'] ? $goods_info['sort_order'] : 0;
                    $goods_info['goods_type'] = $goods_info['goods_type'] ? $goods_info['goods_type'] : 0;
                    $goods_info['is_check'] = $goods_info['is_check'] ? $goods_info['is_check'] : 0;
                    $goods_info['largest_amount'] = $goods_info['largest_amount'] ? $goods_info['largest_amount'] : 0;
                    $goods_info['pinyin_keyword'] = $goods_info['pinyin_keyword'] ? $goods_info['pinyin_keyword'] : '';
                    $goods_info['goods_id'] = $goods_info['goods_id'] ? $goods_info['goods_id'] : 0;
                    $goods_info['from_seller'] = $goods_info['from_seller'] ? $goods_info['from_seller'] : 0;

                    $data['cat_id'] = $goods_info['cat_id'];
                    $data['bar_code'] = $goods_info['bar_code'];
                    $data['goods_name'] = $goods_info['goods_name'];
                    $data['goods_name_style'] = $goods_info['goods_name_style'];
                    $data['brand_id'] = $goods_info['brand_id'];
                    $data['goods_weight'] = $goods_info['goods_weight'];
                    $data['market_price'] = $goods_info['market_price'];
                    $data['cost_price'] = $goods_info['cost_price'];
                    $data['shop_price'] = $goods_info['shop_price'];
                    $data['keywords'] = $goods_info['keywords'];
                    $data['goods_brief'] = $goods_info['goods_brief'];
                    $data['goods_desc'] = $goods_info['goods_desc'];
                    $data['desc_mobile'] = $goods_info['desc_mobile'];
                    $data['goods_thumb'] = $goods_thumb;
                    $data['goods_img'] = $goods_img;
                    $data['original_img'] = $original_img;
                    $data['is_real'] = $goods_info['is_real'];
                    $data['extension_code'] = $goods_info['extension_code'];
                    $data['sort_order'] = $goods_info['sort_order'];
                    $data['goods_type'] = $goods_info['goods_type'];
                    $data['is_check'] = $goods_info['is_check'];
                    $data['largest_amount'] = $goods_info['largest_amount'];
                    $data['pinyin_keyword'] = $goods_info['pinyin_keyword'];
                    $data['lib_goods_id'] = $goods_info['goods_id'];
                    $data['from_seller'] = $user_id;
                    try {
                        $new_goods_id = GoodsLib::insertGetId($data);
                        $res = GoodsGallery::where('goods_id', $goods_info['goods_id']);
                        $res = $this->baseRepository->getToArrayGet($res);
                        if ($res) {
                            foreach ($res as $k => $v) {
                                $img_url = $this->goodsLibManageService->copyImg($v['img_url']);
                                $img_url = $img_url ? $img_url : '';
                                $thumb_url = $this->goodsLibManageService->copyImg($v['thumb_url']);
                                $thumb_url = $thumb_url ? $thumb_url : '';
                                $img_original = $this->goodsLibManageService->copyImg($v['img_original']);
                                $img_original = $img_original ? $img_original : '';
                                $v['img_desc'] = $v['img_desc'] ? $v['img_desc'] : '';
                                $data = [
                                    'goods_id' => $new_goods_id,
                                    'img_desc' => $v['img_desc'],
                                    'img_url' => $img_url,
                                    'thumb_url' => $thumb_url,
                                    'img_original' => $img_original
                                ];
                                $glg_res = GoodsLibGallery::insertGetId($data);
                                if ($glg_res < 1) {
                                    $result['list']['status'] = $GLOBALS['_LANG']['img_import_fail'];
                                }
                            }
                        }
                        $result['list']['status'] = $GLOBALS['_LANG']['prompt_import_success'];
                    } catch (Exception $e) {
                        $result['list']['status'] = $GLOBALS['_LANG']['prompt_import_fail'];
                        //continue;
                    }
                } else {
                    $result['list']['status'] = $GLOBALS['_LANG']['repeat_import'];
                }
            }

            $result['page'] = $goods_list['filter']['page'] + 1;
            $result['page_size'] = $goods_list['filter']['page_size'];
            $result['record_count'] = $goods_list['filter']['record_count'];
            $result['page_count'] = $goods_list['filter']['page_count'];

            $result['is_stop'] = 1;
            if ($page > $goods_list['filter']['page_count']) {
                $result['is_stop'] = 0;
            } else {
                $result['filter_page'] = $goods_list['filter']['page'];
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 搜索店铺名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_shopname') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $shop_name = empty($_REQUEST['shop_name']) ? '' : trim($_REQUEST['shop_name']);

            /* 获取会员列表信息 */
            $res = MerchantsShopInformation::select('user_id');
            $seller_ids = $this->baseRepository->getToArrayGet($res);
            $seller_ids = $this->baseRepository->getFlatten($seller_ids);
            foreach ($seller_ids as $k => $v) {
                if (is_numeric(stripos($this->merchantCommonService->getShopName($v, 1), $shop_name)) || empty($shop_name)) {
                    $seller_list[$k]['shop_name'] = $this->merchantCommonService->getShopName($v, 1);
                    $seller_list[$k]['user_id'] = $v;
                }
            }

            $res = $this->goodsLibManageService->getSearchShopnameList($seller_list);

            clear_cache_files();
            return make_json_result($res);
        }

        /* ------------------------------------------------------ */
        //-- 导入商品库商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'seller_import') {
            $check_auth = check_authz_json('goods_lib');
            if ($check_auth !== true) {
                return $check_auth;
            }

            //清楚商品零时货品表数据
            ProductsChangelog::where("admin_id", $admin_id)->delete();

            //清楚商品零时货品表数据
            GoodsAttr::where('admin_id', $admin_id)->where('goods_id', 0)->delete();

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            //初始化查询表，查询目标,商品值
            $goods = [];
            $table_info = GoodsLib::select('goods_name', 'cat_id', 'brand_id', 'shop_price')->where('goods_id', $goods_id);
            $table_info = $this->baseRepository->getToArrayFirst($table_info);

            $goods['shop_price'] = $table_info['shop_price'] ?? 0;
            $goods['goods_name'] = $table_info['goods_name'] ?? '';
            $goods['goods_cause'] = $table_info['goods_cause'] ?? '';
            $goods['cat_id'] = $table_info['cat_id'] ?? 0;
            $goods['brand_id'] = $table_info['brand_id'] ?? 0;
            /*退换货标志列表*/

            $cause_list = ['0', '1', '2', '3'];
            $res = [];

            /* 判断商品退换货理由 */
            if (!is_null($goods['goods_cause'])) {
                $res = array_intersect(explode(',', $goods['goods_cause']), $cause_list);
            }

            $this->smarty->assign('is_cause', $res ?? []);

            $goods['goods_id'] = $goods_id;

            set_default_filter(0, 0, $adminru['ru_id']); //设置默认筛选

            $transport_list = GoodsTransport::select('tid', 'title')->where('ru_id', $adminru['ru_id']);
            $transport_list = $this->baseRepository->getToArrayGet($transport_list);

            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('transport_list', $transport_list); //商品运费 by wu
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
            $review_status = 3;

            /* 检查是否选择分类 */
            if ($category_id == 0) {
                return sys_msg($GLOBALS['_LANG']['select_cat'], 1, array(), false);
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
                $count = Goods::where('goods_sn', $_POST['goods_sn'])->where('is_delete', 0)->count();
                if ($count > 0) {
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

            $goods_arr = [
                'cat_id' => $category_id,
                'user_id' => $adminru['ru_id'],
                'goods_sn' => $goods_sn,
                'goods_name' => $goods_name,
                'brand_id' => $brand_id,
                'shop_price' => $shop_price,
                'add_time' => gmtime(),
                'goods_number' => $goods_number,
                'store_best' => $store_best,
                'store_new' => $store_new,
                'store_hot' => $store_hot,
                'is_shipping' => $is_shipping,
                'is_on_sale' => $is_on_sale,
                'is_real' => 1,
                'goods_type' => $new_goods_type,
                'last_update' => gmtime(),
                'goods_cause' => $goods_cause,
                'freight' => $freight,
                'shipping_fee' => $shipping_fee,
                'tid' => $tid,
                'user_cat' => $user_cat,
                'review_status' => $review_status
            ];

            if ($standard == 1) {
                $goods = Wholesale::where('goods_id', $lib_goods_id);
                $goods = $this->baseRepository->getToArrayFirst($goods);

                $goods_thumb = $this->goodsLibManageService->copyImg($goods['goods_thumb']);
                $goods_img = $this->goodsLibManageService->copyImg($goods['goods_img']);
                $original_img = $this->goodsLibManageService->copyImg($goods['original_img']);

                if (empty($goods_name)) {
                    $goods_name = $goods['goods_name'];
                }

                $goods_arr['goods_thumb'] = $goods_thumb;
                $goods_arr['goods_img'] = $goods_img;
                $goods_arr['original_img'] = $original_img;
                $goods_arr['bar_code'] = $goods['bar_code'];
                $goods_arr['goods_weight'] = $goods['goods_weight'];
                $goods_arr['retail_price'] = $goods['retail_price'];
                $goods_arr['goods_price'] = $goods['goods_price'];
                $goods_arr['keywords'] = $goods['keywords'];
                $goods_arr['goods_brief'] = $goods['goods_brief'];
                $goods_arr['goods_desc'] = $goods['goods_desc'];
                $goods_arr['desc_mobile'] = $goods['desc_mobile'];
                $goods_arr['sort_order'] = $goods['sort_order'];
                $goods_arr['pinyin_keyword'] = $goods['pinyin_keyword'];
                $goods_arr['warn_number'] = $goods['warn_number'];
                $goods_arr['goods_product_tag'] = $goods['goods_product_tag'];
                $goods_arr['goods_unit'] = $goods['goods_unit'];

                //注释：批发商品建议零售价对应零售商品的市场价和商品价   商品价格对应成本价
                $goods_id = Goods::insertGetId($goods_arr);

                $goods_extend = WholesaleExtend::select('width', 'height', 'depth', 'origincountry', 'originplace', 'assemblycountry', 'barcodetype', 'catena', 'isbasicunit', 'packagetype', 'grossweight', 'netweight', 'netcontent', 'licensenum', 'healthpermitnum')->where('goods_id', $lib_goods_id);
                $goods_extend = $this->baseRepository->getToArrayFirst($goods_extend);
                $goods_extend['goods_id'] = $goods_id;

                //同步扩展信息
                GoodsExtend::insert($goods_extend);

                //获取标准产品库商品相册
                $res = SuppliersGoodsGallery::select('img_desc', 'img_url', ', thumb_url', 'img_original')->where('goods_id', $lib_goods_id);
                $res = $this->baseRepository->getToArrayGet($res);

            } else {
                $goods = GoodsLib::where('goods_id', $lib_goods_id);
                $goods = $this->baseRepository->getToArrayFirst($goods);

                $goods_thumb = $this->goodsLibManageService->copyImg($goods['goods_thumb']);
                $goods_img = $this->goodsLibManageService->copyImg($goods['goods_img']);
                $original_img = $this->goodsLibManageService->copyImg($goods['original_img']);

                if (empty($goods_name)) {
                    $goods_name = $goods['goods_name'];
                }

                $goods['goods_from'] = $goods['goods_from'] ?? 0;

                $count = Goods::where('goods_id', $goods['lib_goods_id'])->where('user_id', $adminru['ru_id'])->count();
                if ($count < 1) {

                    $goods_arr['goods_thumb'] = $goods_thumb;
                    $goods_arr['goods_img'] = $goods_img;
                    $goods_arr['original_img'] = $original_img;
                    $goods_arr['bar_code'] = $goods['bar_code'];
                    $goods_arr['goods_name_style'] = $goods['goods_name_style'];
                    $goods_arr['goods_weight'] = $goods['goods_weight'];
                    $goods_arr['market_price'] = $goods['market_price'];
                    $goods_arr['cost_price'] = $goods['cost_price'];
                    $goods_arr['keywords'] = $goods['keywords'];
                    $goods_arr['goods_brief'] = $goods['goods_brief'];
                    $goods_arr['goods_desc'] = $goods['goods_desc'];
                    $goods_arr['desc_mobile'] = $goods['desc_mobile'];
                    $goods_arr['is_real'] = $goods['is_real'];
                    $goods_arr['extension_code'] = $goods['extension_code'];
                    $goods_arr['sort_order'] = $goods['sort_order'];
                    $goods_arr['is_check'] = $goods['is_check'];
                    $goods_arr['largest_amount'] = $goods['largest_amount'];
                    $goods_arr['pinyin_keyword'] = $goods['pinyin_keyword'];
                    $goods_arr['from_seller'] = $goods['goods_from'];

                    $goods_id = Goods::insertGetId($goods_arr);

                    //获取本地产品库商品相册
                    $res = GoodsLibGallery::select('img_desc', 'img_url', 'thumb_url', 'img_original')->where('goods_id', $lib_goods_id);
                    $res = $this->baseRepository->getToArrayGet($res);
                } else {
                    $link[] = array('text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=list&' . list_link_postfix());
                    return sys_msg(lang('seller/goods_lib.no_import_goods'), 0, $link);
                }
            }

            //商品属性处理
            GoodsAttr::where('goods_id', 0)->where('admin_id', $admin_id)->update(['goods_id' => $goods_id]);

            $products_changelog = ProductsChangelog::select('goods_attr', 'product_sn', 'bar_code', 'product_number', 'product_price', 'product_market_price', 'product_promote_price', 'product_warn_number', 'warehouse_id', 'area_id', 'admin_id')->where('admin_id', session('admin_id'))->where('goods_id', 0);
            $products_changelog = $this->baseRepository->getToArrayGet($products_changelog);

            if (!empty($products_changelog)) {
                foreach ($products_changelog as $k => $v) {
                    if (check_goods_attr_exist($v['goods_attr'], $goods_id, 0, 0)) { //检测货品是否存在
                        continue;
                    }

                    $logs_other = array(
                        'goods_id' => $goods_id,
                        'order_id' => 0,
                        'admin_id' => session('admin_id'),
                        'model_attr' => 0,
                        'add_time' => gmtime()
                    );
                    $table = "products";

                    /* 插入货品表 */
                    $products = [
                        'goods_id' => $goods_id,
                        'goods_attr' => $v['goods_attr'],
                        'product_sn' => $v['product_sn'],
                        'product_number' => $v['product_number'],
                        'product_price' => $v['product_price'],
                        'product_market_price' => $v['product_market_price'],
                        'product_promote_price' => $v['product_promote_price'],
                        'product_warn_number' => $v['product_warn_number'],
                        'bar_code' => $v['bar_code']
                    ];

                    $product_id = Products::insertGetId($products);

                    //货品号为空 自动补货品号
                    if (empty($v['product_sn'])) {
                        Products::where('product_id', $product_id)->update(['product_sn' => $goods_sn . "g_p" . $product_id]);
                    }

                    //库存日志
                    $number = "+ " . $v['product_number'];
                    $logs_other['use_storage'] = 9;
                    $logs_other['product_id'] = $product_id;
                    $logs_other['number'] = $number;
                    GoodsInventoryLogs::insert($logs_other);
                }
            }

            //清楚商品零时货品表数据
            ProductsChangelog::where('goods_id', 0)->where('admin_id', $admin_id)->delete();

            //插入商品扩展信息
            $extend_arr = [
                'goods_id' => $goods_id,
                'is_reality' => $is_reality,
                'is_return' => $is_return,
                'is_fast' => $is_fast
            ];
            GoodsExtend::insert($extend_arr);

            //相册入库
            if ($res) {
                foreach ($res as $k => $v) {
                    $img_url = $this->goodsLibManageService->copyImg($v['img_url']);
                    $thumb_url = $this->goodsLibManageService->copyImg($v['thumb_url']);
                    $img_original = $this->goodsLibManageService->copyImg($v['img_original']);

                    $gallery_arr = [
                        'goods_id' => $goods_id,
                        'img_desc' => $v['img_desc'],
                        'img_url' => $img_url,
                        'thumb_url' => $thumb_url,
                        'img_original' => $img_original
                    ];
                    GoodsGallery::insert($gallery_arr);
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
                    if (!empty($val) && in_array($val, $array)) {
                        return sys_msg('error', 1, [], false);
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
                        $count = Goods::where('goods_sn', $goods_sn)->where('is_delete', 0)->where('goods_id', '<>', $goods_id)->count();
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

                    $review_status = 3;

                    $goods = GoodsLib::where('goods_id', $lib_goods_id);
                    $goods = $this->baseRepository->getToArrayFirst($goods);

                    $goods_count = GoodsLib::where('goods_id', $goods['lib_goods_id'])->where('user_id', $adminru['ru_id'])->count();

                    if ($goods_count <= 0) {

                        $goods_thumb = $this->goodsLibManageService->copyImg($goods['goods_thumb']);
                        $goods_img = $this->goodsLibManageService->copyImg($goods['goods_img']);
                        $original_img = $this->goodsLibManageService->copyImg($goods['original_img']);

                        $goods_arr = [
                            'cat_id' => $goods['cat_id'],
                            'user_id' => $adminru['ru_id'],
                            'goods_sn' => $goods_sn,
                            'bar_code' => $goods['bar_code'],
                            'goods_name' => $goods['goods_name'],
                            'goods_name_style' => $goods['goods_name_style'],
                            'brand_id' => $goods['brand_id'],
                            'goods_weight' => $goods['goods_weight'],
                            'market_price' => $goods['market_price'],
                            'cost_price' => $goods['cost_price'],
                            'shop_price' => $goods['shop_price'],
                            'keywords' => $goods['keywords'],
                            'goods_brief' => $goods['goods_brief'],
                            'goods_desc' => $goods['goods_desc'],
                            'desc_mobile' => $goods['desc_mobile'],
                            'goods_thumb' => $goods_thumb,
                            'goods_img' => $goods_img,
                            'original_img' => $original_img,
                            'add_time' => gmtime(),
                            'goods_number' => $goods_number,
                            'is_shipping' => $is_shipping,
                            'is_on_sale' => $is_on_sale,
                            'is_real' => $goods['is_real'],
                            'extension_code' => $goods['extension_code'],
                            'sort_order' => $goods['sort_order'],
                            'goods_type' => $goods['goods_type'],
                            'is_check' => $goods['is_check'],
                            'largest_amount' => $goods['largest_amount'],
                            'pinyin_keyword' => $goods['pinyin_keyword'],
                            'review_status' => $review_status,
                        ];

                        $goods_id = Goods::insertGetId($goods_arr);

                        //获取本地产品库商品相册
                        $res = GoodsLibGallery::select('img_desc', 'img_url', 'thumb_url', 'img_original')->where('goods_id', $lib_goods_id);
                        $res = $this->baseRepository->getToArrayGet($res);

                        if ($res) {
                            foreach ($res as $k => $v) {
                                $img_url = $this->goodsLibManageService->copyImg($v['img_url']);
                                $thumb_url = $this->goodsLibManageService->copyImg($v['thumb_url']);
                                $img_original = $this->goodsLibManageService->copyImg($v['img_original']);

                                $gallery_arr = [
                                    'goods_id' => $goods_id,
                                    'img_desc' => $v['img_desc'],
                                    'img_url' => $img_url,
                                    'thumb_url' => $thumb_url,
                                    'img_original' => $img_original
                                ];
                                GoodsGallery::insert($gallery_arr);
                            }
                        }
                    }
                }
            }

            $link[] = array('text' => $GLOBALS['_LANG']['20_goods_lib'], 'href' => 'goods_lib.php?act=list&' . list_link_postfix());


            $link[] = array('text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list');
            if ($error > 0) {
                return sys_msg($GLOBALS['_LANG']['import_success'] . 'error1' . $error . 'error2', 0, $link);
            } else {
                return sys_msg($GLOBALS['_LANG']['import_success'], 0, $link);
            }
        }

        /* ------------------------------------------------------ */
        //-- 设置导入属性
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_import_attr') {
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
            $obj = '';
            if ($changelog == 1) {
                $obj = ProductsChangelog::whereRaw('1');
            } else {
                if ($goods_model == 1) {
                    $obj = ProductsWarehouse::whereRaw('1');
                } elseif ($goods_model == 2) {
                    $obj = ProductsArea::whereRaw('1');
                } else {
                    $obj = Products::whereRaw('1');
                }
            }

            if (!empty($obj)) {
                /* 修改 */
                $obj->where('product_id', $product_id)->update(['product_market_price' => $market_price]);
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

            $obj = '';
            if ($changelog == 1) {
                $table = "products_changelog";
                $obj = ProductsChangelog::whereRaw('1');
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                    $obj = ProductsWarehouse::whereRaw('1');
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                    $obj = ProductsArea::whereRaw('1');
                } else {
                    $table = "products";
                    $obj = Products::whereRaw('1');
                }
            }

            if ($GLOBALS['_CFG']['goods_attr_price'] == 1 && $changelog == 0) {
                $goods_id = $obj->where('product_id', $product_id)->value('goods_id');
                $goods_other = array(
                    'product_table' => $table,
                    'product_price' => $product_price,
                );
                Goods::where('goods_id', $goods_id)->where('product_id', $product_id)->where('product_table', $table)->update($goods_other);
            }

            /* 修改货品价格 */
            $obj->where('product_id', $product_id)->update(['product_price' => $product_price]);

            clear_cache_files();
            return make_json_result($product_price);
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

            $obj = '';
            if ($changelog == 1) {
                $table = "products_changelog";
                $obj = ProductsChangelog::whereRaw('1');
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                    $obj = ProductsWarehouse::whereRaw('1');
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                    $obj = ProductsArea::whereRaw('1');
                } else {
                    $table = "products";
                    $obj = Products::whereRaw('1');
                }
            }

            if ($GLOBALS['_CFG']['goods_attr_price'] == 1 && $changelog == 0) {
                $goods_id = $obj->where('product_id', $product_id)->value('goods_id');
                $goods_other = array(
                    'product_table' => $table,
                    'product_promote_price' => $promote_price,
                );
                Goods::where('goods_id', $goods_id)->where('product_id', $product_id)->where('product_table', $table)->update($goods_other);
            }

            /* 修改货品促销价格 */
            $obj->where('product_id', $product_id)->update(['product_promote_price' => $promote_price]);

            clear_cache_files();
            return make_json_result($promote_price);
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
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;
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

                $goods = get_admin_goods_info($product['goods_id'], array('goods_number', 'model_inventory', 'model_attr'));

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

                GoodsInventoryLogs::insert($logs_other);
            }

            $obj = '';
            if ($changelog == 1) {
                $table = "products_changelog";
                $obj = ProductsChangelog::whereRaw('1');
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                    $obj = ProductsWarehouse::whereRaw('1');
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                    $obj = ProductsArea::whereRaw('1');
                } else {
                    $table = "products";
                    $obj = Products::whereRaw('1');
                }
            }

            /* 修改货品库存 */
            $obj->where('product_id', $product_id)->update(['product_number' => $product_number]);

            clear_cache_files();
            return make_json_result($product_number);
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

            $obj = '';
            if ($changelog == 1) {
                $table = "products_changelog";
                $obj = ProductsChangelog::whereRaw('1');
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                    $obj = ProductsWarehouse::whereRaw('1');
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                    $obj = ProductsArea::whereRaw('1');
                } else {
                    $table = "products";
                    $obj = Products::whereRaw('1');
                }
            }

            /* 修改货品预警库存 */
            $obj->where('product_id', $product_id)->update(['product_warn_number' => $product_warn_number]);

            clear_cache_files();
            return make_json_result($product_warn_number);
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

            $obj = '';
            if ($changelog == 1) {
                $table = "products_changelog";
                $obj = ProductsChangelog::whereRaw('1');
            } else {
                if ($goods_model == 1) {
                    $table = "products_warehouse";
                    $obj = ProductsWarehouse::whereRaw('1');
                } elseif ($goods_model == 2) {
                    $table = "products_area";
                    $obj = ProductsArea::whereRaw('1');
                } else {
                    $table = "products";
                    $obj = Products::whereRaw('1');
                }
            }

            /* 修改货品库存 */
            $obj->where('product_id', $product_id)->update(['product_sn' => $product_sn]);

            clear_cache_files();
            return make_json_result($product_sn);
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

                $obj = '';
                if ($changelog == 1) {
                    $table = "products_changelog";
                    $obj = ProductsChangelog::whereRaw('1');
                } else {
                    if ($goods_model == 1) {
                        $table = "products_warehouse";
                        $obj = ProductsWarehouse::whereRaw('1');
                    } elseif ($goods_model == 2) {
                        $table = "products_area";
                        $obj = ProductsArea::whereRaw('1');
                    } else {
                        $table = "products";
                        $obj = Products::whereRaw('1');
                    }
                }

                /* 修改 */
                $obj->where('product_id', $product_id)->update(['bar_code' => $bar_code]);

                clear_cache_files();
                return make_json_result($bar_code);
            }
        }
    }
}
