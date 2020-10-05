<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Image;
use App\Libraries\Pinyin;
use App\Models\Category;
use App\Models\MerchantsNav;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsManageService;
use App\Services\Goods\GoodsWarehouseService;

/**
 * 管理中心品牌管理
 */
class DialogController extends InitController
{
    protected $baseRepository;
    protected $dscRepository;
    protected $categoryService;
    protected $goodsManageService;
    protected $pinyin;
    protected $goodsAttrService;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $cartCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        CategoryService $categoryService,
        GoodsManageService $goodsManageService,
        Pinyin $pinyin,
        GoodsAttrService $goodsAttrService,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        CartCommonService $cartCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->categoryService = $categoryService;
        $this->goodsManageService = $goodsManageService;
        $this->pinyin = $pinyin;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->cartCommonService = $cartCommonService;
    }

    public function index()
    {
        load_helper('goods', 'seller');

        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        load_helper('visual');

        $admin_id = get_admin_id();
        $adminru = get_admin_ru_id();
        /*------------------------------------------------------ */
        //-- 弹出窗口
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'dialog_content') {
            $result = ['content' => '', 'sgs' => ''];
            $temp = !empty($_REQUEST['temp']) ? $_REQUEST['temp'] : '';
            $this->smarty->assign("temp", $temp);
            $result['sgs'] = $temp;
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 仓库弹窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'dialog_warehouse') {
            $result = ['content' => '', 'sgs' => ''];
            $temp = !empty($_REQUEST['temp']) ? $_REQUEST['temp'] : '';
            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : $adminru['ru_id'];
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $this->smarty->assign("temp", $temp);
            $result['sgs'] = $temp;

            $grade_rank = get_seller_grade_rank($user_id);
            $this->smarty->assign('grade_rank', $grade_rank);
            $this->smarty->assign('integral_scale', $GLOBALS['_CFG']['integral_scale']);

            $warehouse_list = get_warehouse_list();
            $this->smarty->assign('warehouse_list', $warehouse_list);

            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('goods_id', $goods_id);

            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'dialog_img') {
            $result = ['content' => '', 'sgs' => ''];
            $temp = !empty($_REQUEST['temp']) ? $_REQUEST['temp'] : '';
            $this->smarty->assign("temp", $temp);
            $goods_id = !empty($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : '';
            $this->smarty->assign('goods_id', $goods_id);
            $result['sgs'] = $temp;

            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加仓库/地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'dialog_add') {
            $result = ['content' => '', 'sgs' => ''];
            $temp = !empty($_REQUEST['temp']) ? $_REQUEST['temp'] : '';
            $this->smarty->assign("temp", $temp);

            $result['sgs'] = $temp;

            /* 取得地区 */
            $country_list = get_regions();
            $this->smarty->assign('countries', $country_list);

            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        } //扩展分类
        elseif ($_REQUEST['act'] == 'extension_category') {
            $result = ['content' => '', 'sgs' => ''];
            $temp = !empty($_REQUEST['temp']) ? $_REQUEST['temp'] : '';
            $this->smarty->assign("temp", $temp);

            $result['sgs'] = $temp;

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);

            $goods = get_admin_goods_info($goods_id);
            $goods['user_id'] = isset($goods['user_id']) && !empty($goods['user_id']) ? $goods['user_id'] : $adminru['ru_id'];

            /* 商家入驻分类 */
            if ($goods['user_id']) {
                $seller_shop_cat = seller_shop_cat($goods['user_id']);
            }

            /* 取得分类 */
            $level_limit = 3;
            $category_level = [];
            for ($i = 1; $i <= $level_limit; $i++) {
                $category_list = [];
                if ($i == 1) {
                    if ($goods['user_id']) {
                        $category_list = get_category_list(0, 0, $seller_shop_cat, $goods['user_id'], $i);
                    } else {
                        $category_list = get_category_list();
                    }
                }
                $this->smarty->assign('cat_level', $i);
                $this->smarty->assign('category_list', $category_list);

                $category_level[$i] = $this->smarty->fetch('library/get_select_category.lbi');
            }
            $this->smarty->assign('category_level', $category_level);

            /* 取得已存在的扩展分类 */
            if ($goods_id > 0) {
                $other_cat_list1 = [];
                $sql = "SELECT ga.cat_id FROM " . $this->dsc->table('goods_cat') . " as ga " .
                    " WHERE ga.goods_id = '$goods_id'";
                $other_cat1 = $this->db->getCol($sql);

                $other_category = [];
                if (!empty($other_cat1)) {
                    foreach ($other_cat1 as $key => $val) {
                        $other_category[$key]['cat_id'] = $val;
                        $other_category[$key]['cat_name'] = get_every_category($val);
                    }
                    $this->smarty->assign('other_category', $other_category);
                }
            }

            $this->smarty->assign('goods_id', $goods_id);
            $result['content'] = $GLOBALS['smarty']->fetch('library/extension_category.lbi');
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 添加属性图片 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_attr_img') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $lib_type = empty($_REQUEST['lib_type']) ? 0 : intval($_REQUEST['lib_type']);
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_name = !empty($_REQUEST['goods_name']) ? trim($_REQUEST['goods_name']) : '';
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $goods_date = ['goods_name'];
            $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);
            if (!isset($goods_info['goods_name'])) {
                $goods_info['goods_name'] = $goods_name;
            }

            $goods_attr_date = ['attr_img_flie, attr_img_site, attr_checked, attr_gallery_flie'];
            $goods_attr_info = get_table_date('goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);

            if ($goods_attr_info) {
                if ($goods_attr_info['attr_img_flie']) {
                    $goods_attr_info['attr_img_flie'] = get_image_path($goods_attr_info['attr_img_flie']);
                }

                if ($goods_attr_info['attr_img_site']) {
                    $goods_attr_info['attr_img_site'] = get_image_path($goods_attr_info['attr_img_site']);
                }

                if ($goods_attr_info['attr_gallery_flie']) {
                    $goods_attr_info['attr_gallery_flie'] = get_image_path($goods_attr_info['attr_gallery_flie']);
                }
            }

            if ($goods_attr_info) {
                if ($goods_attr_info['attr_img_flie']) {
                    $goods_attr_info['attr_img_flie'] = get_image_path($goods_attr_info['attr_img_flie']);
                }

                if ($goods_attr_info['attr_img_site']) {
                    $goods_attr_info['attr_img_site'] = get_image_path($goods_attr_info['attr_img_site']);
                }

                if ($goods_attr_info['attr_gallery_flie']) {
                    $goods_attr_info['attr_gallery_flie'] = get_image_path($goods_attr_info['attr_gallery_flie']);
                }
            }

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
            $this->smarty->assign('lib_type', $lib_type);

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_attr_img_info.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加属性图片插入数据 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_attr_img') {
            load_helper('goods');

            $result = ['error' => 0, 'message' => '', 'content' => '', 'is_checked' => 0];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $img_url = !empty($_REQUEST['img_url']) ? $_REQUEST['img_url'] : '';

            if (!empty($_FILES['attr_img_flie'])) {
                $other['attr_img_flie'] = get_upload_pic('attr_img_flie');
                $this->dscRepository->getOssAddFile([$other['attr_img_flie']]);
            } else {
                $other['attr_img_flie'] = '';
            }

            $goods_attr_date = ['attr_img_flie, attr_img_site'];
            $goods_attr_info = get_table_date('goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);

            if (empty($other['attr_img_flie'])) {
                $other['attr_img_flie'] = $goods_attr_info['attr_img_flie'];
            } else {
                dsc_unlink(storage_public($goods_attr_info['attr_img_flie']));
            }

            $other['attr_img_site'] = !empty($_REQUEST['attr_img_site']) ? $_REQUEST['attr_img_site'] : '';
            $other['attr_checked'] = !empty($_REQUEST['attr_checked']) ? intval($_REQUEST['attr_checked']) : 0;

            if ($img_url) {
                $gallery_flie = explode('/storage/', $img_url);

                if (count($gallery_flie) > 1) {
                    $other['attr_gallery_flie'] = $gallery_flie[1];
                } else {
                    $other['attr_gallery_flie'] = $img_url;
                }
            }

            if ($other['attr_checked'] == 1) {
                $this->db->autoExecute($this->dsc->table('goods_attr'), ['attr_checked' => 0], 'UPDATE', 'attr_id = ' . $attr_id . ' and goods_id = ' . $goods_id);
                $result['is_checked'] = 1;
            }

            $this->db->autoExecute($this->dsc->table('goods_attr'), $other, 'UPDATE', 'goods_attr_id = ' . $goods_attr_id . ' and attr_id = ' . $attr_id . ' and goods_id = ' . $goods_id);

            $result['goods_attr_id'] = $goods_attr_id;

            $goods = get_admin_goods_info($goods_id);
            /* 同步前台商品详情价格与商品列表价格一致 start */
            if ($other['attr_checked'] == 1) {
                if ($GLOBALS['_CFG']['add_shop_price'] == 0 && $goods['model_attr'] == 0) {
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

                            $this->db->autoExecute($this->dsc->table('goods'), $other, 'UPDATE', "goods_id = '$goods_id'");
                        }
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
                    $this->db->autoExecute($this->dsc->table('goods'), $goods_other, 'UPDATE', "goods_id = '$goods_id'");
                }
            }
            /* 同步前台商品详情价格与商品列表价格一致 end */

            clear_cache_files();
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除属性图片 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_attr_img') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

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

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 选择属性图片 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'choose_attrImg') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $admin_id = get_admin_id();

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_attr_id = empty($_REQUEST['goods_attr_id']) ? 0 : intval($_REQUEST['goods_attr_id']);
            $on_img_id = isset($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : 0;

            $sql = "SELECT attr_gallery_flie FROM " . $this->dsc->table('goods_attr') . " WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
            $attr_gallery_flie = $this->db->getOne($sql);

            $thumb_img_id = session('thumb_img_id' . $admin_id, ''); //处理添加商品时相册图片串图问题   by kong
            if (empty($goods_id) && !empty($thumb_img_id)) {
                $where = " goods_id = 0 AND img_id " . db_create_in($thumb_img_id);
            } else {
                $where = " goods_id = '$goods_id'";
            }

            /* 删除数据 */
            $sql = "SELECT img_id, thumb_url, img_url FROM " . $this->dsc->table('goods_gallery') . " WHERE $where";
            $img_list = $this->db->getAll($sql);

            $str = "<ul>";
            foreach ($img_list as $idx => $row) {
                $row['thumb_url'] = get_image_path($row['thumb_url']); //处理图片地址
                if ($attr_gallery_flie == $row['img_url']) {
                    $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')" class="on"><img src="' . $row['thumb_url'] . '" width="87" /><i><img src="' . asset('assets/seller/images/gallery_yes.png') . '" width="30" height="30"></i></li>';
                } else {
                    $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')"><img src="' . $row['thumb_url'] . '" width="87" /><i><img src="' . asset('assets/seller/images/gallery_yes.png') . '" width="30" height="30"></i></li>';
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
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = intval($_REQUEST['goods_id']);
            $goods_attr_id = intval($_REQUEST['goods_attr_id']);
            $gallery_id = intval($_REQUEST['gallery_id']);

            if (!empty($gallery_id)) {
                $sql = "SELECT img_id, img_url FROM " . $this->dsc->table('goods_gallery') . "WHERE img_id='$gallery_id'";
                $img = $this->db->getRow($sql);
                $result['img_id'] = $img['img_id'];
                $result['img_url'] = $img['img_url'];

                $sql = "UPDATE " . $this->dsc->table('goods_attr') . " SET attr_gallery_flie = '" . $img['img_url'] . "' WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
                $this->db->query($sql);
            } else {
                $result['error'] = 1;
            }

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加仓库价格 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_goods_model_price') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $warehouse_id = 0;
            $area_id = 0;

            $goods = get_goods_model($goods_id);
            $this->smarty->assign('goods', $goods);

            $warehouse_list = get_warehouse_list();
            if ($warehouse_list) {
                $warehouse_id = $warehouse_list[0]['region_id'];
                $sql = "SELECT region_id FROM " . $this->dsc->table('region_warehouse') . " WHERE parent_id = '" . $warehouse_list[0]['region_id'] . "'";
                $area_id = $this->db->getOne($sql, true);
            }

            $this->smarty->assign('warehouse_list', $warehouse_list);
            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            $list = get_goods_warehouse_area_list($goods_id, $goods['model_attr'], $warehouse_id);

            $this->smarty->assign('warehouse_area_list', $list['list']);
            $this->smarty->assign('warehouse_area_filter', $list['filter']);
            $this->smarty->assign('warehouse_area_record_count', $list['record_count']);
            $this->smarty->assign('warehouse_area_page_count', $list['page_count']);
            $this->smarty->assign('query', $list['query']);
            $this->smarty->assign('full_page', 1);

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_price_list.lbi');

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加仓库价格
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_wa_query') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $list = get_goods_warehouse_area_list();

            $this->smarty->assign('warehouse_area_list', $list['list']);
            $this->smarty->assign('warehouse_area_filter', $list['filter']);
            $this->smarty->assign('warehouse_area_record_count', $list['record_count']);
            $this->smarty->assign('warehouse_area_page_count', $list['page_count']);
            $this->smarty->assign('query', $list['query']);

            $goods = get_goods_model($list['filter']['goods_id']);
            $this->smarty->assign('goods', $goods);

            return make_json_result($this->smarty->fetch('goods_price_list.lbi'), '', ['pb_filter' => $list['filter'], 'pb_page_count' => $list['page_count'], 'class' => "goodslistDiv"]);
        }

        /* ------------------------------------------------------ */
        //-- 添加仓库属性价格 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_warehouse_price') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $action_link = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $GLOBALS['_LANG']['goods_info']];

            if (empty($goods_attr_id)) {
                $goods_attr_id = get_goods_attr_nameId($goods_id, $attr_id, $goods_attr_name); //获取商品的属性ID
            }

            if (empty($attr_id)) {
                $attr_id = get_goods_attr_nameId($goods_id, $goods_attr_id, $goods_attr_name, 'attr_id', 1);
            }

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

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_warehouse_price_info.lbi');

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加仓库属性价格 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_warehouse_price') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            if (isset($_REQUEST['goods_attr_id']) && is_array($_REQUEST['goods_attr_id'])) {
                $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : [];
            } else {
                $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            }

            if (isset($_REQUEST['attr_id']) && is_array($_REQUEST['attr_id'])) {
                $attr_id = !empty($_REQUEST['attr_id']) ? $_REQUEST['attr_id'] : [];
            } else {
                $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            }

            if (isset($_REQUEST['warehouse_name']) && is_array($_REQUEST['warehouse_name'])) {
                $warehouse_name = !empty($_REQUEST['warehouse_name']) ? $_REQUEST['warehouse_name'] : [];
            } else {
                $warehouse_name = !empty($_REQUEST['warehouse_name']) ? intval($_REQUEST['warehouse_name']) : 0;
            }

            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';

            get_warehouse_area_attr_price_insert($warehouse_name, $goods_id, $goods_attr_id, 'warehouse_attr');

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加地区属性价格 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_area_price') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $action_link = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $GLOBALS['_LANG']['goods_info']];

            if (empty($goods_attr_id)) {
                $goods_attr_id = get_goods_attr_nameId($goods_id, $attr_id, $goods_attr_name); //获取商品的属性ID
            }

            if (empty($attr_id)) {
                $attr_id = get_goods_attr_nameId($goods_id, $goods_attr_id, $goods_attr_name, 'attr_id', 1);
            }

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

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_area_price_info.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除商品勾选属性 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_goods_attr') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $attr_value = isset($_REQUEST['attr_value']) && !empty($_REQUEST['attr_value']) ? addslashes($_REQUEST['attr_value']) : '';
            $goods_model = isset($_REQUEST['model']) && !empty($_REQUEST['model']) ? intval($_REQUEST['model']) : 0;//商品模式
            $region_id = empty($_REQUEST['region_id']) ? 0 : intval($_REQUEST['region_id']); //地区id

            if ($goods_attr_id) {
                $where = "goods_attr_id = '$goods_attr_id'";
            } else {
                $where = "goods_id = '$goods_id' AND attr_value = '$attr_value' AND attr_id = '$attr_id' AND admin_id = '$admin_id'";
            }
            $attr_where = '';
            //判断商品类型
            if ($goods_model == 1) {
                $table = "products_warehouse";
                $attr_where .= " AND warehouse_id = '$region_id' ";
            } elseif ($goods_model == 2) {
                $table = "products_area";
                $attr_where .= " AND area_id = '$region_id' ";
            } else {
                $table = "products";
            }

            //删除相关货品
            $sql = "SELECT product_id,goods_attr FROM" . $this->dsc->table($table) . "WHERE goods_id = '$goods_id' $attr_where";
            $products = $this->db->getAll($sql);
            if (!empty($products)) {
                foreach ($products as $k => $v) {
                    if ($v['goods_attr']) {
                        $goods_attr = explode('|', $v['goods_attr']);
                        if (in_array($goods_attr_id, $goods_attr)) {
                            $sql = "DELETE FROM" . $this->dsc->table("products") . "WHERE product_id = '" . $v['product_id'] . "' AND goods_id = '$goods_id'";
                            $this->db->query($sql);
                        }
                    }
                }
            }
            $admin_id = get_admin_id();
            //删除零时货品表
            $sql = "SELECT product_id,goods_attr FROM" . $this->dsc->table('products_changelog') . "WHERE goods_id = '$goods_id' AND admin_id = '$admin_id' $attr_where";
            $products_changelog = $this->db->getAll($sql);
            if (!empty($products_changelog)) {
                foreach ($products_changelog as $k => $v) {
                    if ($v['goods_attr']) {
                        $goods_attr = explode('|', $v['goods_attr']);
                        if (in_array($goods_attr_id, $goods_attr)) {
                            $sql = "DELETE FROM" . $this->dsc->table("products_changelog") . "WHERE product_id = '" . $v['product_id'] . "' AND goods_id = '$goods_id'";
                            $this->db->query($sql);
                        }
                    }
                }
            }
            $sql = "DELETE FROM " . $this->dsc->table("goods_attr") . " WHERE $where";
            $this->db->query($sql);

            $goods_info = get_admin_goods_info($goods_id);

            if ($goods_info['model_attr'] == 1) {
                $prod = ProductsWarehouse::where('goods_id', $goods_id);
            } elseif ($goods_info['model_attr'] == 2) {
                $prod = ProductsArea::where('goods_id', $goods_id);
            } else {
                $prod = Products::where('goods_id', $goods_id);
            }

            $prod = $prod->whereRaw("FIND_IN_SET('$goods_attr_id', REPLACE(goods_attr, '|', ','))");

            $prod->delete();

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加地区属性价格 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_area_price') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            if (isset($_REQUEST['goods_attr_id']) && is_array($_REQUEST['goods_attr_id'])) {
                $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : [];
            } else {
                $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            }

            if (isset($_REQUEST['attr_id']) && is_array($_REQUEST['attr_id'])) {
                $attr_id = !empty($_REQUEST['attr_id']) ? $_REQUEST['attr_id'] : [];
            } else {
                $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            }

            if (isset($_REQUEST['area_name']) && is_array($_REQUEST['area_name'])) {
                $area_name = !empty($_REQUEST['area_name']) ? $_REQUEST['area_name'] : [];
            } else {
                $area_name = !empty($_REQUEST['area_name']) ? intval($_REQUEST['area_name']) : 0;
            }

            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';

            get_warehouse_area_attr_price_insert($area_name, $goods_id, $goods_attr_id, 'warehouse_area_attr');

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加商品SKU/库存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_sku') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $warehouse_id = 0;
            $area_id = 0;
            $city_id = 0;

            $goods = get_goods_model($goods_id);

            $warehouse_list = get_warehouse_list();
            if ($warehouse_list) {
                $warehouse_id = $warehouse_list[0]['region_id'];
                $sql = "SELECT region_id FROM " . $this->dsc->table('region_warehouse') . " WHERE parent_id = '" . $warehouse_list[0]['region_id'] . "'";
                $area_id = $this->db->getOne($sql, true);

                $sql = "SELECT region_id FROM " . $this->dsc->table('region_warehouse') . " WHERE parent_id = '$area_id'";
                $city_id = $this->db->getOne($sql, true);
            }

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('city_id', $city_id);

            $this->smarty->assign('goods', $goods);

            $this->smarty->assign('warehouse_list', $warehouse_list);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('user_id', $user_id);

            $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

            $product_list = get_goods_product_list($goods_id, $goods['model_attr'], $warehouse_id, $area_id, $city_id);
            $this->smarty->assign('product_list', $product_list['product_list']);
            $this->smarty->assign('sku_filter', $product_list['filter']);
            $this->smarty->assign('sku_record_count', $product_list['record_count']);
            $this->smarty->assign('sku_page_count', $product_list['page_count']);
            $this->smarty->assign('query', $product_list['query']);
            $this->smarty->assign('full_page', 1);

            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_attr_list.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加商品SKU/库存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sku_query') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

            $product_list = get_goods_product_list();

            $this->smarty->assign('product_list', $product_list['product_list']);
            $this->smarty->assign('sku_filter', $product_list['filter']);
            $this->smarty->assign('sku_record_count', $product_list['record_count']);
            $this->smarty->assign('sku_page_count', $product_list['page_count']);
            $this->smarty->assign('query', $product_list['query']);

            $goods = [
                'goods_id' => $product_list['filter']['goods_id'],
                'model_attr' => $product_list['filter']['model'],
                'warehouse_id' => $product_list['filter']['warehouse_id'],
                'area_id' => $product_list['filter']['area_id']
            ];
            $this->smarty->assign('goods', $goods);

            return make_json_result($this->smarty->fetch('library/goods_attr_list.lbi'), '', ['pb_filter' => $product_list['filter'], 'pb_page_count' => $product_list['page_count'], 'class' => "attrlistDiv"]);
        }

        /* ------------------------------------------------------ */
        //-- 添加商品SKU/库存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_attr_sku') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $product_id = !empty($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;

            $goods_info = get_admin_goods_info($goods_id);

            $this->smarty->assign('product_id', $product_id);

            $editInput = "";
            $method = "";
            $filed = "";
            if ($goods_info['model_attr'] == 1) {
                $filed = ", warehouse_id";
                $method = "insert_warehouse_price";
            } elseif ($goods_info['model_attr'] == 2) {
                $filed = ", area_id";
                $method = "insert_area_price";
            } else {
                $editInput = "edit_attr_price";
            }

            /* 货品库存 */
            $product = get_product_info($product_id, 'product_id, product_number, goods_id, product_sn, goods_attr' . $filed, $goods_info['model_attr'], 1);
            $this->smarty->assign('goods_info', $goods_info);
            $this->smarty->assign('product', $product);
            $this->smarty->assign('editInput', $editInput);
            $this->smarty->assign('method', $method);

            $warehouse_id = isset($product['warehouse_id']) && !empty($product['warehouse_id']) ? $product['warehouse_id'] : 0;
            $area_id = isset($product['area_id']) && !empty($product['area_id']) ? $product['area_id'] : 0;

            if (!empty($warehouse_id)) {
                $warehouse_area_id = $warehouse_id;
            } elseif (!empty($area_id)) {
                $warehouse_area_id = $area_id;
            }

            $warehouse = get_area_info($warehouse_area_id, 1);
            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('warehouse', $warehouse);

            $result['method'] = $method;
            $result['content'] = $GLOBALS['smarty']->fetch('library/goods_list_product.lbi');
            return response()->json($result);
        } elseif ($_REQUEST['act'] == 'shop_banner') {
            $result = ['content' => '', 'sgs' => '', 'mode' => ''];
            $this->smarty->assign("temp", "shop_banner");

            $result = ['content' => '', 'mode' => ''];
            $is_vis = isset($_REQUEST['is_vis']) ? intval($_REQUEST['is_vis']) : 0;
            $inid = isset($_REQUEST['inid']) ? trim($_REQUEST['inid']) : '';//div标识
            $image_type = isset($_REQUEST['image_type']) ? intval($_REQUEST['image_type']) : 0;
            if ($is_vis == 0) {
                $uploadImage = isset($_REQUEST['uploadImage']) ? intval($_REQUEST['uploadImage']) : 0;
                $titleup = isset($_REQUEST['titleup']) ? intval($_REQUEST['titleup']) : 0;
                $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
                /*处理数组*/
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
                }
                $defualt = '';
                if ($result['mode'] == 'lunbo') {
                    $defualt = 'shade';
                } elseif ($result['mode'] == 'advImg1') {
                    $defualt = 'yesSlide';
                }
                $spec_attr['is_title'] = isset($spec_attr['is_title']) ? $spec_attr['is_title'] : 0;
                $spec_attr['slide_type'] = isset($spec_attr['slide_type']) ? $spec_attr['slide_type'] : $defualt;
                $spec_attr['target'] = isset($spec_attr['target']) ? addslashes($spec_attr['target']) : '_blank';
                $pic_src = (isset($spec_attr['pic_src']) && $spec_attr['pic_src'] != ',') ? $spec_attr['pic_src'] : [];
                $link = (isset($spec_attr['link']) && $spec_attr['link'] != ',') ? explode(',', $spec_attr['link']) : [];
                $sort = (isset($spec_attr['sort']) && $spec_attr['sort'] != ',') ? $spec_attr['sort'] : [];
                $bg_color = isset($spec_attr['bg_color']) ? $spec_attr['bg_color'] : [];
                $title = (!empty($spec_attr['title']) && $spec_attr['title'] != ',') ? $spec_attr['title'] : [];
                $subtitle = (!empty($spec_attr['subtitle']) && $spec_attr['subtitle'] != ',') ? $spec_attr['subtitle'] : [];
                $pic_number = isset($_REQUEST['pic_number']) ? intval($_REQUEST['pic_number']) : 0;
                $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;

                $count = $pic_src ? COUNT($pic_src) : 0; //数组长度
                /* 合并数组 */
                $arr = [];
                if ($count) {
                    for ($i = 0; $i < $count; $i++) {
                        if (isset($pic_src[$i]) && $pic_src[$i]) {
                            if (strpos($pic_src[$i], 'storage/') === false && (strpos($pic_src[$i], 'http://') === false && strpos($pic_src[$i], 'https://') === false)) {
                                $pic_image = get_image_path($pic_src[$i]);
                            } else {
                                $pic_image = $pic_src[$i];
                            }

                            $arr[$i + 1]['pic_src'] = $pic_image;
                            if (isset($link[$i]) && $link[$i]) {
                                $arr[$i + 1]['link'] = str_replace(['＆'], '&', $link[$i]);
                            } else {
                                $arr[$i + 1]['link'] = isset($link[$i]) ? $link[$i] : '';
                            }
                            $arr[$i + 1]['sort'] = isset($sort[$i]) ? $sort[$i] : '';
                            $arr[$i + 1]['bg_color'] = isset($bg_color[$i]) ? $bg_color[$i] : '';
                            $arr[$i + 1]['title'] = isset($title[$i]) ? $title[$i] : '';
                            $arr[$i + 1]['subtitle'] = isset($subtitle[$i]) ? $subtitle[$i] : '';
                        }
                    }
                }

                $this->smarty->assign('banner_list', $arr);
            }

            $cat_select = gallery_cat_list(0, 0, false, 0, true, $adminru['ru_id']);
            /* 简单处理缩进 */
            $i = 0;
            $default_album = 0;
            foreach ($cat_select as $k => $v) {
                if ($v['level'] == 0 && $i == 0) {
                    $i++;
                    $default_album = $v['album_id'];
                }
                if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
                }
            }
            if ($default_album > 0) {
                $pic_list = getAlbumList($default_album);

                $this->smarty->assign('pic_list', $pic_list['list']);
                $this->smarty->assign('filter', $pic_list['filter']);
                $this->smarty->assign('album_id', $default_album);
            }
            $this->smarty->assign('cat_select', $cat_select);
            $this->smarty->assign('is_vis', $is_vis);
            //可视化入口
            if ($is_vis == 0) {
                $this->smarty->assign('pic_number', $pic_number);
                $this->smarty->assign('mode', $result['mode']);
                $this->smarty->assign('spec_attr', $spec_attr);
                $this->smarty->assign('uploadImage', $uploadImage);
                $this->smarty->assign('titleup', $titleup);
                $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
            } else {
                $this->smarty->assign('image_type', 0);
                $this->smarty->assign('log_type', 'image');
                $this->smarty->assign('image_type', $image_type);
                $this->smarty->assign('inid', $inid);
                $result['content'] = $GLOBALS['smarty']->fetch('library/album_dialog.lbi');
            }

            return response()->json($result);
        } //设置默认模板整体色调
        elseif ($_REQUEST['act'] == 'templateColorSet') {
            $result = ['content' => ''];
            $temp = !empty($_REQUEST['temp']) ? trim($_REQUEST['temp']) : '';
            $spec_attr['typeColor'] = !empty($_REQUEST['typeColor']) ? trim($_REQUEST['typeColor']) : ''; //颜色

            $this->smarty->assign("spec_attr", $spec_attr);
            $this->smarty->assign("temp", $temp);

            $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品单选复选属性手工录入
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'attr_input_type') {
            $result = ['content' => '', 'sgs' => ''];

            $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $this->smarty->assign('attr_id', $attr_id);
            $this->smarty->assign('goods_id', $goods_id);

            $goods_attr = get_dialog_goods_attr_type($attr_id, $goods_id);
            $this->smarty->assign('goods_attr', $goods_attr);

            $result['content'] = $GLOBALS['smarty']->fetch('library/attr_input_type.lbi');
            return response()->json($result);
        }


        /*------------------------------------------------------ */
        //-- 商品单选复选属性手工录入
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_attr_input') {
            $result = ['content' => '', 'sgs' => ''];

            $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : [];
            $attr_value_list = isset($_REQUEST['attr_value_list']) ? $_REQUEST['attr_value_list'] : [];

            $goods_attr_id = isset($_REQUEST['attr_id_val']) ? explode(',', $_REQUEST['attr_id_val']) : $goods_attr_id;
            $attr_value_list = isset($_REQUEST['value_list_val']) ? explode(',', $_REQUEST['value_list_val']) : $attr_value_list;

            if ($goods_id) {
                $where = " AND goods_id = '$goods_id'";
            } else {
                $where = " AND goods_id = 0 AND admin_id = '$admin_id'";
            }

            /* 插入、更新、删除数据 */
            foreach ($attr_value_list as $key => $attr_value) {
                if ($attr_value) {

                    $attr_value = trim($attr_value);

                    if ($goods_attr_id[$key]) {
                        $sql = "UPDATE " . $this->dsc->table('goods_attr') . " SET attr_value = '$attr_value' WHERE goods_attr_id = '" . $goods_attr_id[$key] . "' LIMIT 1";
                        $this->db->query($sql);
                    } else {
                        $sql = "SELECT MAX(attr_sort) AS attr_sort FROM " . $this->dsc->table('goods_attr') . " WHERE attr_id = '$attr_id'" . $where;
                        $max_attr_sort = $this->db->getOne($sql);

                        if ($max_attr_sort) {
                            $key = $max_attr_sort + 1;
                        } else {
                            $key += 1;
                        }

                        $sql = "SELECT goods_attr_id FROM " . $this->dsc->table('goods_attr') . " WHERE attr_value = '$attr_value' AND attr_id = '$attr_id' AND goods_id = '$goods_id'";
                        if (!$this->db->getOne($sql, true)) {
                            $sql = "INSERT INTO " . $this->dsc->table('goods_attr') . " (attr_id, goods_id, attr_value, attr_sort, admin_id)" .
                                "VALUES ('$attr_id', '$goods_id', '$attr_value', '$key', '$admin_id')";
                            $this->db->query($sql);
                        }
                    }
                }
            }

            $result['attr_id'] = $attr_id;
            $result['goods_id'] = $goods_id;

            $goods_attr = get_dialog_goods_attr_type($attr_id, $goods_id);
            $this->smarty->assign('goods_attr', $goods_attr);
            $this->smarty->assign('attr_id', $attr_id);

            $result['content'] = $GLOBALS['smarty']->fetch('library/attr_input_type_list.lbi');

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品单选复选属性手工录入
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_input_type') {
            $result = ['content' => '', 'sgs' => ''];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('goods_attr') . " WHERE goods_attr_id = '$goods_attr_id'";
            $this->db->query($sql);

            if ($goods_id > 0) {

                $goods_info = get_admin_goods_info($goods_id);

                if ($goods_info['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $goods_id);
                } elseif ($goods_info['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $goods_id);
                } else {
                    $prod = Products::where('goods_id', $goods_id);
                }

                $prod = $prod->whereRaw("FIND_IN_SET('$goods_attr_id', REPLACE(goods_attr, '|', ','))");

                $prod->delete();

                $goods_attr = get_dialog_goods_attr_type($attr_id, $goods_id);
            } else {
                $goods_attr = [];
            }

            $this->smarty->assign('goods_attr', $goods_attr);
            $this->smarty->assign('attr_id', $attr_id);

            $result['attr_id'] = $attr_id;

            $result['attr_content'] = $GLOBALS['smarty']->fetch('library/attr_input_type_list.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除商品优惠阶梯价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_volume') {
            $result = ['content' => '', 'sgs' => ''];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('volume_price') . " WHERE id = '$volume_id'";
            $this->db->query($sql);

            $volume_price_list = $this->goodsCommonService->getVolumePriceList($goods_id);
            if (!$volume_price_list) {
                $sql = "UPDATE " . $this->dsc->table('goods') . " SET is_volume = 0 WHERE goods_id = '$goods_id'";
                $this->db->query($sql);
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除批发商品优惠阶梯价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_wholesale_volume') {
            $result = ['content' => '', 'sgs' => ''];

            $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('wholesale_volume_price') . " WHERE id = '$volume_id'";
            $this->db->query($sql);

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除满立减优惠价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_cfull') {
            $result = ['content' => '', 'sgs' => ''];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('goods_consumption') . " WHERE id = '$volume_id'";
            $this->db->query($sql);

            $consumption_list = $this->cartCommonService->getGoodsConList($goods_id, 'goods_consumption'); //满减订单金额
            if (!$consumption_list) {
                $sql = "UPDATE " . $this->dsc->table('goods') . " SET is_fullcut = 0 WHERE goods_id = '$goods_id'";
                $this->db->query($sql);
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加商品图片外链地址
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_external_url') {
            $result = ['content' => '', 'sgs' => '', 'error' => 0];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $this->smarty->assign('goods_id', $goods_id);
            $result['content'] = $GLOBALS['smarty']->fetch('library/external_url_list.lbi');

            $result['goods_id'] = $goods_id;
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 插入商品图片外链地址
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_external_url') {
            $result = ['content' => '', 'sgs' => '', 'error' => 0];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $external_url_list = isset($_REQUEST['external_url_list']) ? $_REQUEST['external_url_list'] : [];

            /* 是否处理缩略图 */
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;

            //当前域名协议
            $http = $this->dsc->http();

            if ($external_url_list) {
                $sql = "SELECT MAX(img_desc) FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id'";
                $desc = $this->db->getOne($sql, true);

                $admin_id = get_admin_id();
                $admin_temp_dir = "seller";
                $admin_temp_dir = storage_public("temp" . '/' . $admin_temp_dir . '/' . "admin_" . $admin_id);

                // 如果目标目录不存在，则创建它
                if (!file_exists($admin_temp_dir)) {
                    make_dir($admin_temp_dir);
                }

                $thumb_img_id = [];
                $img_url = '';
                $thumb_url = '';
                $img_original = '';
                foreach ($external_url_list as $key => $image_urls) {
                    if ($image_urls) {
                        if (!empty($image_urls) && ($image_urls != $GLOBALS['_LANG']['img_file']) && ($image_urls != 'http://') && (strpos($image_urls, 'http://') !== false || strpos($image_urls, 'https://') !== false)) {
                            if (get_http_basename($image_urls, $admin_temp_dir)) {
                                $image_url = trim($image_urls);
                                //定义原图路径
                                $down_img = $admin_temp_dir . "/" . basename($image_url);

                                $img_wh = $image->get_width_to_height($down_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                                $GLOBALS['_CFG']['image_width'] = isset($img_wh['image_width']) ? $img_wh['image_width'] : $GLOBALS['_CFG']['image_width'];
                                $GLOBALS['_CFG']['image_height'] = isset($img_wh['image_height']) ? $img_wh['image_height'] : $GLOBALS['_CFG']['image_height'];

                                if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                                    $goods_img = $image->make_thumb(['img' => $down_img, 'type' => 1], $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                                } else {
                                    $goods_img = $image->make_thumb(['img' => $down_img, 'type' => 1]);
                                }

                                // 生成缩略图
                                if ($proc_thumb) {
                                    $thumb_url = $image->make_thumb(['img' => $down_img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                                    $thumb_url = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                                } else {
                                    $thumb_url = $image->make_thumb(['img' => $down_img, 'type' => 1]);
                                    $thumb_url = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                                }

                                $img_original = $this->goodsManageService->reformatImageName('gallery', $goods_id, $down_img, 'source');
                                $img_url = $this->goodsManageService->reformatImageName('gallery', $goods_id, $goods_img, 'goods');

                                $desc += 1;

                                $sql = "INSERT INTO " . $this->dsc->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                                    "VALUES ('$goods_id', '$img_url', '$desc', '$thumb_url', '$img_original')";
                                $this->db->query($sql);
                                $thumb_img_id[] = $this->db->insert_id();
                                @unlink($down_img);
                            }
                        }

                        $this->dscRepository->getOssAddFile([$img_url, $thumb_url, $img_original]);
                    }
                }

                if (!empty(session('thumb_img_id' . session('seller_id')))) {
                    $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('seller_id')));
                }

                session([
                    'thumb_img_id' . session('seller_id') => $thumb_img_id
                ]);
            }

            /* 图片列表 */
            $img_id = session('thumb_img_id' . session('seller_id'));
            $where = '';
            if ($img_id && $goods_id == 0) {
                $where = "AND img_id " . db_create_in($img_id) . "";
            }
            $sql = "SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id' $where  ORDER BY img_desc";
            $img_list = $this->db->getAll($sql);

            /* 格式化相册图片路径 */
            if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                foreach ($img_list as $key => $gallery_img) {
                    $img_list[$key] = $gallery_img;
                    //图片显示
                    $img_list[$key]['img_url'] = get_image_path($gallery_img['img_original']);
                    $img_list[$key]['thumb_url'] = get_image_path($gallery_img['thumb_url']);
                }
            } else {
                foreach ($img_list as $key => $gallery_img) {
                    $img_list[$key] = $gallery_img;

                    if (!empty($gallery_img['external_url'])) {
                        $img_list[$key]['img_url'] = $gallery_img['external_url'];
                        $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                    } else {
                        $img_list[$key]['thumb_url'] = get_image_path($gallery_img['thumb_url']);
                    }
                }
            }

            $this->smarty->assign('img_list', $img_list);
            $this->smarty->assign('goods_id', $goods_id);
            $result['content'] = $GLOBALS['smarty']->fetch('library/gallery_img.lbi');

            $result['goods_id'] = $goods_id;
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 编辑商品图片外链地址
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_gallery_url') {
            $result = ['content' => '', 'sgs' => '', 'error' => 0];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $img_id = isset($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : 0;
            $external_url = isset($_REQUEST['external_url']) ? addslashes(trim($_REQUEST['external_url'])) : '';

            $sql = "SELECT img_id FROM " . $this->dsc->table('goods_gallery') . " WHERE external_url = '$external_url' AND goods_id = '$goods_id' AND img_id <> $img_id";
            if ($this->db->getOne($sql, true) && !empty($external_url)) {
                $result['error'] = 1;
            } else {
                $sql = "UPDATE " . $this->dsc->table('goods_gallery') . " SET external_url = '$external_url'" . " WHERE img_id = '$img_id'";
                $this->db->query($sql);
            }

            $result['img_id'] = $img_id;

            if (!empty($external_url)) {
                $result['external_url'] = $external_url;
            } else {
                $sql = "SELECT thumb_url FROM " . $this->dsc->table('goods_gallery') . " WHERE img_id = '$img_id'";
                $thumb_url = $this->db->getOne($sql, true);

                $thumb_url = get_image_path($thumb_url);

                $result['external_url'] = $thumb_url;
            }

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 添加图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'pic_album') {
            $result = ['content' => '', 'sgs' => ''];
            $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $this->smarty->assign('album_id', $album_id);
            $cat_select = gallery_cat_list(0, 0, false, 0, true, $adminru['ru_id']);

            /* 简单处理缩进 */
            foreach ($cat_select as $k => $v) {
                if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
                }
            }
            $this->smarty->assign('cat_select', $cat_select);
            $album_mame = get_goods_gallery_album(2, $album_id, ['album_mame']);
            $this->smarty->assign('album_mame', $album_mame['album_mame']);
            $this->smarty->assign('temp', $_REQUEST['act']);
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }
        /*--------------------------------------------------------*/
        //商品模块弹窗
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'goods_info') {
            $result = ['content' => '', 'mode' => ''];

            /*处理数组*/
            $spec_attr = [];
            $search_type = isset($_REQUEST['search_type']) ? trim($_REQUEST['search_type']) : '';
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $goods_type = isset($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
            $good_number = isset($_REQUEST['good_number']) ? intval($_REQUEST['good_number']) : 0;
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
            $spec_attr['goods_ids'] = isset($spec_attr['goods_ids']) ? resetBarnd($spec_attr['goods_ids']) : '';//重置数据
            if ($spec_attr['goods_ids']) {
                $goods_info = explode(',', $spec_attr['goods_ids']);
                foreach ($goods_info as $k => $v) {
                    if (!$v) {
                        unset($goods_info[$k]);
                    }
                }
                if (!empty($goods_info)) {
                    $where = " WHERE g.is_on_sale=1 AND g.is_delete=0 AND g.goods_id" . db_create_in($goods_info) . " AND g.user_id = '" . $adminru['ru_id'] . "'";

                    //ecmoban模板堂 --zhuo start
                    if ($GLOBALS['_CFG']['review_goods'] == 1) {
                        $where .= ' AND g.review_status > 2 ';
                    }
                    //ecmoban模板堂 --zhuo end

                    $sql = "SELECT g.goods_name,g.goods_id,g.goods_thumb,g.original_img,g.shop_price FROM " . $this->dsc->table('goods') . " AS g " . $where;
                    $goods_list = $this->db->getAll($sql);

                    foreach ($goods_list as $k => $v) {
                        $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                        $goods_list[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
                    }

                    $this->smarty->assign('goods_list', $goods_list);
                    $this->smarty->assign('goods_count', count($goods_list));
                }
            }
            /* 取得分类列表 */
            set_default_filter($cat_id, 0, $adminru['ru_id']); //by wu
            $this->smarty->assign('parent_category', get_every_category($cat_id)); //上级分类
            $select_category_html = '';
            $seller_shop_cat = seller_shop_cat($adminru['ru_id']);
            $select_category_html = insert_select_category(0, 0, 0, 'cat_id', 0, 'category', $seller_shop_cat);
            $this->smarty->assign('select_category_html', $select_category_html);
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('arr', $spec_attr);
            $this->smarty->assign("temp", "goods_info");
            $this->smarty->assign("goods_type", $goods_type);
            $this->smarty->assign("mode", $result['mode']);
            $this->smarty->assign("cat_id", $cat_id);
            $this->smarty->assign("lift", $lift);
            $this->smarty->assign("good_number", $good_number);
            $this->smarty->assign("search_type", $search_type);
            $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
            return response()->json($result);
        }
        /*--------------------------------------------------------*/
        //自定义模块弹窗
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'custom') {
            $result = ['content' => '', 'mode' => ''];
            $custom_content = isset($_REQUEST['custom_content']) ? unescape($_REQUEST['custom_content']) : '';
            $custom_content = !empty($custom_content) ? stripslashes($custom_content) : '';
            $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($custom_content) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $custom_content);
                $custom_content = $desc_preg['goods_desc'];
            }

            /* 创建 百度编辑器 wang 商家入驻 */
            $FCKeditor = create_ueditor_editor('custom_content', $custom_content, 486, 1);
            $this->smarty->assign('FCKeditor', $FCKeditor);
            $this->smarty->assign("temp", $_REQUEST['act']);
            $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
            return response()->json($result);
        }
        /*--------------------------------------------------------*/
        //头部模块弹窗
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'header') {
            $result = ['content' => '', 'mode' => ''];
            $arr = [];
            $this->smarty->assign("temp", $_REQUEST['act']);
            $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';

            $_REQUEST['spec_attr'] = urldecode($_REQUEST['spec_attr']);
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            if (!empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
            }

            $spec_attr['header_type'] = isset($spec_attr['header_type']) ? $spec_attr['header_type'] : 'defalt_type';
            $custom_content = (isset($_REQUEST['custom_content']) && $_REQUEST['custom_content'] != 'undefined') ? unescape($_REQUEST['custom_content']) : '';
            $custom_content = !empty($custom_content) ? stripslashes($custom_content) : '';
            $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
            $spec_attr['suffix'] = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : '';
            /* 创建 百度编辑器 wang 商家入驻 */
            $FCKeditor = create_ueditor_editor('custom_content', $custom_content, 486, 1);
            $this->smarty->assign('FCKeditor', $FCKeditor);
            $this->smarty->assign('content', $spec_attr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
            return response()->json($result);
        }
        /*--------------------------------------------------------*/
        //导航模块弹窗
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'navigator') {
            $result = ['content' => '', 'mode' => ''];
            $topic_type = isset($_REQUEST['topic_type']) ? trim($_REQUEST['topic_type']) : '';
            /*处理数组*/
            $spec_attr['target'] = '';
            $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            $_REQUEST['spec_attr'] = urldecode($_REQUEST['spec_attr']);
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            if (!empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
            }
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            if ($topic_type == 'topic_type') {
                unset($spec_attr['target']);
                $navigator = $spec_attr;
            } else {
                $navigator = MerchantsNav::where('ru_id', $adminru['ru_id'])->orderBy('vieworder');
                $navigator = $this->baseRepository->getToArrayGet($navigator);
            }
            $spec_attr['target'] = isset($spec_attr['target']) ? $spec_attr['target'] : '_blank';
            $this->smarty->assign('navigator', $navigator);
            $this->smarty->assign('topic_type', $topic_type);
            $this->smarty->assign("temp", $_REQUEST['act']);

            $sysmain = $this->categoryService->getMerchantsCatList(0, $adminru['ru_id']);
            $this->smarty->assign('sysmain', $sysmain);

            $this->smarty->assign('attr', $spec_attr);
            $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
            $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
            return response()->json($result);
        }
        /*--------------------------------------------------------*/
        //模板信息弹框
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'template_information') {
            $result = ['content' => '', 'mode' => ''];
            $code = isset($_REQUEST['code']) ? addslashes($_REQUEST['code']) : '';
            //$ru_id = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id'])  :  0;
            $adminru = get_admin_ru_id();

            if ($code) {
                $this->smarty->assign('template', get_seller_template_info($code, $adminru['ru_id']));
            }
            $this->smarty->assign('code', $code);
            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign("temp", $_REQUEST['act']);
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 智能权重
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'manual_intervention') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods = get_admin_goods_info($goods_id);
            $this->smarty->assign('goods', $goods);
            $manual_intervention = get_manual_intervention($goods_id);
            $this->smarty->assign('manual_intervention', $manual_intervention);

            $result['content'] = $GLOBALS['smarty']->fetch('library/manual_intervention.lbi');

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 转移相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'album_move') {
            $result = ['content' => '', 'pic_id' => '', 'old_album_id' => ''];
            $pic_id = isset($_REQUEST['pic_id']) ? intval($_REQUEST['pic_id']) : 0;
            $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
            $this->smarty->assign("temp", $temp);

            /*获取全部相册*/
            $cat_select = gallery_cat_list(0, 0, false, 0, true, $adminru['ru_id']);

            /* 简单处理缩进 */
            foreach ($cat_select as $k => $v) {
                if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
                }
            }

            $this->smarty->assign('cat_select', $cat_select);

            /*获取该图片所属相册*/
            $album_id = gallery_pic_album(0, $pic_id, ['album_id']);
            $this->smarty->assign('album_id', $album_id);

            $result['pic_id'] = $pic_id;
            $result['old_album_id'] = $album_id;
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        } /*添加相册*/
        elseif ($_REQUEST['act'] == 'add_albun_pic') {
            $result = ['content' => '', 'pic_id' => '', 'old_album_id' => ''];
            $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
            $this->smarty->assign("temp", $temp);

            $cat_select = gallery_cat_list(0, 0, false, 0, true, $adminru['ru_id']);

            /* 简单处理缩进 */
            if ($cat_select) {
                foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                        $level = str_repeat('&nbsp;', $v['level'] * 4);
                        $cat_select[$k]['name'] = $level . $v['name'];
                    }
                }
            }

            $album_info['parent_album_id'] = 0;
            $album_info['ru_id'] = $adminru['ru_id'];

            $this->smarty->assign('cat_select', $cat_select);
            $this->smarty->assign('album_info', $album_info);


            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }/*--------------------------------------------------------*/
        //首页楼层
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'homeFloor') {
            $result = ['content' => '', 'mode' => ''];
            $result['act'] = $_REQUEST['act'];
            $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $result['hierarchy'] = isset($_REQUEST['hierarchy']) ? trim($_REQUEST['hierarchy']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $result['mode'] = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';

            $_REQUEST['spec_attr'] = urldecode($_REQUEST['spec_attr']);
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            if (!empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
            }

            //处理图片链接
            if ($spec_attr['leftBannerLink']) {
                foreach ($spec_attr['leftBannerLink'] as $k => $v) {
                    $spec_attr['leftBannerLink'][$k] = str_replace(['＆'], '&', $v);
                }
            }
            if ($spec_attr['rightAdvLink']) {
                foreach ($spec_attr['rightAdvLink'] as $k => $v) {
                    $spec_attr['rightAdvLink'][$k] = str_replace(['＆'], '&', $v);
                }
            }
            if ($spec_attr['leftAdvLink']) {
                foreach ($spec_attr['leftAdvLink'] as $k => $v) {
                    $spec_attr['leftAdvLink'][$k] = str_replace(['＆'], '&', $v);
                }
            }
            //验证品牌
            $spec_attr['brand_ids'] = resetBarnd($spec_attr['brand_ids'], 'brand');
            $brand_ids = !empty($spec_attr['brand_ids']) ? trim($spec_attr['brand_ids']) : '';
            $cat_id = !empty($spec_attr['cat_id']) ? intval($spec_attr['cat_id']) : 0;
            $spec_attr['catChild'] = '';
            $spec_attr['Selected'] = '';
            if ($cat_id > 0) {
                $parent = Category::catInfo($spec_attr['cat_id'])->first();
                $parent = $parent ? $parent->toArray() : [];

                if ($parent['parent_id'] > 0) {
                    $spec_attr['catChild'] = $this->categoryService->catList($parent['parent_id']);
                    $spec_attr['Selected'] = $parent['parent_id'];
                } else {
                    $spec_attr['catChild'] = $this->categoryService->catList($spec_attr['cat_id']);
                    $spec_attr['Selected'] = $cat_id;
                }
                $spec_attr['juniorCat'] = $this->categoryService->catList($cat_id);
            }
            $arr = [];
            //处理商品id和分类id关系
            if (isset($spec_attr['cateValue']) && $spec_attr['cateValue']) {
                foreach ($spec_attr['cateValue'] as $k => $v) {
                    $arr[$k]['cat_id'] = $v;
                    $arr[$k]['cat_goods'] = $spec_attr['cat_goods'][$k];
                }
            }
            $spec_attr['catInfo'] = $arr;

            //处理标题特殊字符
            if (isset($spec_attr['rightAdvTitle']) && $spec_attr['rightAdvTitle']) {
                foreach ($spec_attr['rightAdvTitle'] as $k => $v) {
                    if ($v) {
                        $spec_attr['rightAdvTitle'][$k] = $v;
                    }
                }
            }

            if (isset($spec_attr['rightAdvSubtitle']) && $spec_attr['rightAdvSubtitle']) {
                foreach ($spec_attr['rightAdvSubtitle'] as $k => $v) {
                    if ($v) {
                        $spec_attr['rightAdvSubtitle'][$k] = $v;
                    }
                }
            }

            //获取楼层模板广告模式数组
            $floor_style = get_floor_style($result['mode']);

            //获取分类
            $seller_shop_cat = seller_shop_cat($adminru['ru_id']);
            $cat_list = $this->categoryService->catList(0, 0, 0, 'category', $seller_shop_cat, 1);
            //初始化模块图片数量
            $imgNumberArr = getAdvNum($result['mode']);
            $imgNumberArr = json_encode($imgNumberArr);
            $this->smarty->assign('cat_list', $cat_list);
            $this->smarty->assign('temp', $_REQUEST['act']);
            $this->smarty->assign('mode', $result['mode']);
            $this->smarty->assign('lift', $lift);
            $this->smarty->assign('spec_attr', $spec_attr);
            $this->smarty->assign('hierarchy', $result['hierarchy']);
            $this->smarty->assign('floor_style', $floor_style);
            $this->smarty->assign('imgNumberArr', $imgNumberArr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
            return response()->json($result);
        } //商家订单列表导出弹窗
        elseif ($_REQUEST['act'] == 'merchant_download') {
            $result = ['content' => ''];
            $page_count = isset($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0; //总页数
            $filename = !empty($_REQUEST['filename']) ? trim($_REQUEST['filename']) : ''; //处理导出数据的文件
            $fileaction = !empty($_REQUEST['fileaction']) ? trim($_REQUEST['fileaction']) : ''; //处理导出数据的入口
            $lastfilename = !empty($_REQUEST['lastfilename']) ? trim($_REQUEST['lastfilename']) : ''; //最后处理导出的文件
            $lastaction = !empty($_REQUEST['lastaction']) ? trim($_REQUEST['lastaction']) : ''; //最后处理导出的程序入口

            $this->smarty->assign('page_count', $page_count);
            $this->smarty->assign('filename', $filename);
            $this->smarty->assign('fileaction', $fileaction);
            $this->smarty->assign('lastfilename', $lastfilename);
            $this->smarty->assign('lastaction', $lastaction);

            session()->forget('merchants_download_content'); //初始化导出对象
            $result['content'] = $GLOBALS['smarty']->fetch('library/merchant_download.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 地图弹出窗口  by kong
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'getmap_html') {
            $result = ['content' => '', 'sgs' => ''];
            $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
            $this->smarty->assign("temp", $temp);
            $result['sgs'] = $temp;
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        } //包邮券设置不包邮地区弹窗
        elseif ($_REQUEST['act'] == 'set_free_shipping') {
            $result = ['content' => ''];

            $region_ids = !empty($_REQUEST['region_ids']) ? explode(',', trim($_REQUEST['region_ids'])) : [];
            $sql = "SELECT ra_id, ra_name " .
                " FROM " . $this->dsc->table('merchants_region_area');
            $region_list = $this->db->getAll($sql);

            $count = count($region_list);
            for ($i = 0; $i < $count; $i++) {
                $region_list[$i]['add_time'] = local_date("Y-m-d H:i:s", $region_list[$i]['add_time']);
                $area = $this->ajax_get_area_list($region_list[$i]['ra_id'], $region_ids);
                $region_list[$i]['area_list'] = $area;
            }
            $this->smarty->assign('region_list', $region_list);
            $this->smarty->assign('temp', 'set_free_shipping');
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

            return response()->json($result);
        } //商品详情页  添加属性分类，添加类型，添加属性弹窗
        elseif ($_REQUEST['act'] == 'add_goods_type_cat') {
            $result = ['content' => ''];

            $type = !empty($_REQUEST['type']) ? trim($_REQUEST['type']) : '';
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $user_id = $adminru['ru_id'];
            if ($type == 'add_goods_type_cat' || $type == 'add_goods_type') {
                $cat_level = get_type_cat_arr(0, 0, 0, $user_id);
                $this->smarty->assign("cat_level", $cat_level);
            } elseif ($type == 'attribute_add') {

                $this->dscRepository->helpersLang('attribute', 'seller');

                $this->smarty->assign('lang', $GLOBALS['_LANG']);

                $goods_type = isset($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
                $attr = [
                    'attr_id' => 0,
                    'cat_id' => $goods_type,
                    'attr_cat_type' => 0, //by zhang
                    'attr_name' => '',
                    'attr_input_type' => 0,
                    'attr_index' => 0,
                    'attr_values' => '',
                    'attr_type' => 0,
                    'is_linked' => 0,
                ];
                $this->smarty->assign('attr', $attr);
                $this->smarty->assign('attr_groups', get_attr_groups($attr['cat_id']));
                /* 取得商品分类列表 */
                $this->smarty->assign('goods_type_list', goods_type_list($attr['cat_id']));
            }
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('temp', $type);
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

            return response()->json($result);
        }

        /* -------------------------------------------------------- */
        // ajax添加运费模板
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'ajaxTransport') {

            $this->dscRepository->helpersLang('goods_transport', 'seller');

            load_helper('order');
            $result = ['content' => '', 'mode' => ''];

            $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);

            $transport_info = [];
            $shipping_tpl = [];
            if (!$tid) {
                $form_action = 'transport_insert';

                $sql = "DELETE FROM" . $this->dsc->table("goods_transport_tpl") . "WHERE tid = 0 AND admin_id = '$admin_id'";
                $this->db->query($sql);
            } else {
                $form_action = 'transport_update';
                $trow = get_goods_transport($tid);
                if ($tid > 0) {
                    $transport_info = $trow;
                    $shipping_tpl = get_transport_shipping_list($tid, $adminru['ru_id']);
                }
            }

            $this->smarty->assign('shipping_tpl', $shipping_tpl);
            $this->smarty->assign('form_action', $form_action);
            $this->smarty->assign('tid', $tid);
            $this->smarty->assign('transport_info', $transport_info);
            $this->smarty->assign('transport_area', $this->get_transport_area($tid));
            $this->smarty->assign('transport_express', $this->get_transport_express($tid));

            //快递列表
            $shipping_list = shipping_list();
            foreach ($shipping_list as $key => $val) {
                //剔除手机快递
                if (substr($val['shipping_code'], 0, 5) == 'ship_') {
                    unset($shipping_list[$key]);
                    continue;
                }
                /* 剔除上门自提 */
                if ($val['shipping_id'] == 17) {
                    unset($shipping_list[$key]);
                }
            }
            $this->smarty->assign('shipping_list', $shipping_list);

            $this->smarty->assign("temp", $_REQUEST['act']);
            $this->smarty->assign("lang", $GLOBALS['_LANG']);
            $this->smarty->assign('form_action', $form_action);
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

            return response()->json($result);
        }

        /* -------------------------------------------------------- */
        // ajax添加运费模板操作
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'transport_insert' || $_REQUEST['act'] == 'transport_update') {
            $result = ['content' => '', 'message' => '', 'error' => 0];

            $data = [];
            $data['tid'] = !isset($_REQUEST['tid']) && empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
            $data['ru_id'] = $adminru['ru_id'];
            $data['type'] = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);
            $data['title'] = empty($_REQUEST['title']) ? '' : trim($_REQUEST['title']);
            $data['freight_type'] = empty($_REQUEST['freight_type']) ? 0 : intval($_REQUEST['freight_type']);
            $data['update_time'] = gmtime();
            $data['free_money'] = empty($_REQUEST['free_money']) ? 0 : floatval($_REQUEST['free_money']);
            $data['shipping_title'] = empty($_REQUEST['shipping_title']) ? 0 : trim($_REQUEST['shipping_title']);

            $s_tid = $data['tid'];

            if ($_REQUEST['act'] == 'transport_update') {
                $result['message'] = $GLOBALS['_LANG']['edit_express_tpl_success'];
                $this->db->autoExecute($this->dsc->table('goods_transport'), $data, "UPDATE", "tid = '$data[tid]'");
                $tid = $s_tid;

                $where = " tid = '$tid'";
            } else {
                $result['message'] = $GLOBALS['_LANG']['add_express_tpl_success'];
                $this->db->autoExecute($this->dsc->table('goods_transport'), $data, 'INSERT');
                $tid = $this->db->insert_id();
                $this->db->autoExecute($this->dsc->table('goods_transport_extend'), ['tid' => $tid], "UPDATE", "tid = '0' AND admin_id = '$admin_id' ");
                $this->db->autoExecute($this->dsc->table('goods_transport_express'), ['tid' => $tid], "UPDATE", "tid = '0' AND admin_id = '$admin_id' ");

                $where = " admin_id = '$admin_id' AND tid = 0";
            }

            //处理运费模板
            if ($data['freight_type'] > 0) {

                if (!session()->has($s_tid . '.tpl_id') && empty(session()->get($s_tid . '.tpl_id'))) {
                    $sql = "SELECT GROUP_CONCAT(id) AS id FROM " . $GLOBALS['dsc']->table('goods_transport_tpl') . " WHERE " . $where;
                    $tpl_id = $GLOBALS['db']->getOne($sql);
                } else {
                    $tpl_id = session()->get($s_tid . '.tpl_id');
                }

                if (!empty($tpl_id)) {
                    $sql = "UPDATE" . $this->dsc->table("goods_transport_tpl") . " SET tid = '$tid' WHERE admin_id = '$admin_id' AND tid = 0 AND id " . db_create_in($tpl_id);
                    $this->db->query($sql);

                    session()->forget($s_tid . '.tpl_id');
                }
            }

            //处理地区数据
            if (isset($_REQUEST['sprice']) && count($_REQUEST['sprice']) > 0) {
                foreach ($_REQUEST['sprice'] as $key => $val) {
                    $info = [];
                    $info['sprice'] = $val;
                    $this->db->autoExecute($this->dsc->table('goods_transport_extend'), $info, "UPDATE", "id = '$key'");
                }
            }

            //处理快递数据
            if (isset($_REQUEST['shipping_fee']) && count($_REQUEST['shipping_fee']) > 0) {
                foreach ($_REQUEST['shipping_fee'] as $key => $val) {
                    $info = [];
                    $info['shipping_fee'] = $val;
                    $this->db->autoExecute($this->dsc->table('goods_transport_express'), $info, "UPDATE", "id = '$key'");
                }
            }

            $this->smarty->assign("temp", "transport_reload");
            $this->smarty->assign('transport_list', get_table_date("goods_transport", "ru_id='{$adminru['ru_id']}'", ['tid, title'], 1));
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

            return response()->json($result);
        }

        /* -------------------------------------------------------- */
        // ajax添加分类
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'ajaxCate') {

            $this->dscRepository->helpersLang('category', 'seller');

            $result = ['content' => '', 'mode' => ''];

            $parent_id = !empty($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : 0;
            set_seller_default_filter(0, 0, $adminru['ru_id']); //by wu
            if ($parent_id > 0) {
                $this->smarty->assign('parent_id', $parent_id); //上级分类
                $this->smarty->assign('parent_category', get_seller_every_category($parent_id)); //上级分类导航
            }

            //属性分类
            $type_level = get_type_cat_arr(0, 0, 0, $adminru['ru_id']);
            $this->smarty->assign('type_level', $type_level);

            //商品属性
            $sql = "SELECT a.attr_id, a.cat_id, a.attr_name " .
                " FROM " . $GLOBALS['dsc']->table('attribute') . " AS a,  " .
                $GLOBALS['dsc']->table('goods_type') . " AS c " .
                " WHERE  a.cat_id = c.cat_id AND c.enabled = 1 " .
                " ORDER BY a.cat_id , a.sort_order";

            $arr = $GLOBALS['db']->getAll($sql);

            $list = [];

            foreach ($arr as $val) {
                $list[$val['cat_id']][] = [$val['attr_id'] => $val['attr_name']];
            }

            /* 模板赋值 */
            $this->smarty->assign('goods_type_list', goods_type_list(0)); // 取得商品类型
            $this->smarty->assign('attr_list', $list); // 取得商品属性
            $this->smarty->assign('cat_info', ['is_show' => 1]);

            $this->smarty->assign("temp", $_REQUEST['act']);
            $this->smarty->assign("lang", $GLOBALS['_LANG']);
            $this->smarty->assign('form_action', 'cate_insert');
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

            return response()->json($result);
        }

        /* -------------------------------------------------------- */
        // ajax添加分类操作
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'cate_insert') {
            $result = ['content' => '', 'message' => '', 'error' => 0];

            /* 初始化变量 */
            $cat['parent_id'] = isset($_POST['parent_id']) ? trim($_POST['parent_id']) : '0_-1';

            $parent_id = explode('_', $cat['parent_id']);
            $cat['parent_id'] = intval($parent_id[0]);

            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['keywords'] = !empty($_POST['keywords']) ? trim($_POST['keywords']) : '';
            $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
            $cat['measure_unit'] = !empty($_POST['measure_unit']) ? trim($_POST['measure_unit']) : '';
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
            $cat['user_id'] = $adminru['ru_id'];

            // by guan start
            $pinyin = $this->pinyin->Pinyin($cat['cat_name'], 'UTF8');

            $cat['pinyin_keyword'] = $pinyin;
            // by guan end

            $cat['show_in_nav'] = !empty($_POST['show_in_nav']) ? intval($_POST['show_in_nav']) : 0;
            $cat['style'] = !empty($_POST['style']) ? trim($_POST['style']) : '';
            $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;

            /* by zhou */
            $cat['is_top_show'] = !empty($_POST['is_top_show']) ? intval($_POST['is_top_show']) : 0;
            $cat['is_top_style'] = !empty($_POST['is_top_style']) ? intval($_POST['is_top_style']) : 0;
            /* by zhou */
            $cat['grade'] = !empty($_POST['grade']) ? intval($_POST['grade']) : 0;
            $cat['filter_attr'] = !empty($_POST['filter_attr']) ? implode(',', array_unique(array_diff($_POST['filter_attr'], [0]))) : 0;

            $cat['cat_recommend'] = !empty($_POST['cat_recommend']) ? $_POST['cat_recommend'] : [];
            // 上传手机菜单图标 by kong start
            if (!empty($_FILES['touch_icon']['name'])) {
                if ($_FILES["touch_icon"]["size"] > 200000) {
                    $result['error'] = 2;
                    $result['message'] = $GLOBALS['_LANG']['upload_img_max_200kb'];
                    return response()->json($result);
                }
                $type = end(explode('.', $_FILES['touch_icon']['name']));
                if ($type != 'jpg' && $type != 'png' && $type != 'gif') {
                    $result['error'] = 2;
                    $result['message'] = $GLOBALS['_LANG']['please_upload_jpg_gif_png'];
                    return response()->json($result);
                }
                $touch_iconPrefix = time() . mt_rand(1001, 9999);
                // 文件目录
                $touch_iconDir = "../" . DATA_DIR . "/touch_icon";
                if (!file_exists($touch_iconDir)) {
                    mkdir($touch_iconDir);
                }
                // 保存文件
                $touchimgName = $touch_iconDir . "/" . $touch_iconPrefix . '.' . $type;
                $touchsaveDir = DATA_DIR . "/touch_icon" . "/" . $touch_iconPrefix . '.' . $type;
                move_uploaded_file($_FILES["touch_icon"]["tmp_name"], $touchimgName);
                $cat['touch_icon'] = $touchsaveDir;
                $this->dscRepository->getOssAddFile([$cat['touch_icon']]); //oss存储图片
                // 删除文件
                if (!empty($cat_id)) {
                    $cat_info = Category::catInfo($cat_id)->first();
                    $cat_info = $cat_info ? $cat_info->toArray() : [];

                    @unlink(storage_public($cat_info['touch_icon']));
                }
            }

            if (cat_exists($cat['cat_name'], $cat['parent_id'], 0, $adminru['ru_id'])) {
                /* 同级别下不能有重复的分类名称 */
                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['same_level_no_same_name'];
                return response()->json($result);
            }

            if ($cat['grade'] > 10 || $cat['grade'] < 0) {
                /* 价格区间数超过范围 */
                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['price_range_beyond'];
                return response()->json($result);
            }

            /* 入库的操作 */
            $cat_name = explode(',', $cat['cat_name']);

            if (count($cat_name) > 1) {
                $cat['show_in_nav'] = !empty($_POST['is_show_merchants']) ? intval($_POST['is_show_merchants']) : 0;

                get_bacth_category($cat_name, $cat, $adminru['ru_id']);

                clear_cache_files();    // 清除缓存
            } else {
                if ($this->db->autoExecute($this->dsc->table('merchants_category'), $cat) !== false) {
                    $cat_id = $this->db->insert_id();
                    if ($cat['show_in_nav'] == 1) {
                        $vieworder = $this->db->getOne("SELECT max(vieworder) FROM " . $this->dsc->table('merchants_nav') . " WHERE type = 'middle'");
                        $vieworder += 2;
                        // 显示在自定义导航栏中
                        $sql = "INSERT INTO " . $this->dsc->table('merchants_nav') .
                            " (name,ctype,cid,ifshow,vieworder,opennew,url,type,ru_id)" .
                            " VALUES('" . $cat['cat_name'] . "', 'c', '" . $this->db->insert_id() . "','1','$vieworder','0', '" . $this->dscRepository->buildUri('merchants_store', ['cid' => $cat_id, 'urid' => $adminru['ru_id']], $cat['cat_name']) . "','middle','" . $adminru['ru_id'] . "')";
                        $this->db->query($sql);
                    }

                    admin_log($_POST['cat_name'], 'add', 'merchants_category');   // 记录管理员操作

                    clear_cache_files();    // 清除缓存
                }
            }

            $this->smarty->assign('temp', 'cate_reload');
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            $result['message'] = $GLOBALS['_LANG']['add_cate_success_reselect'];

            return response()->json($result);
        }

        /* -------------------------------------------------------- */
        // 上传视频
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'video_box') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $this->smarty->assign("temp", "video_box_load");
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

            return response()->json($result);
        }
    }

    //查询区域地区列表
    private function ajax_get_area_list($ra_id = 0, $region_ids = [])
    {
        $sql = "select r.region_id, r.region_name from " . $this->dsc->table('merchants_region_info') . " as mri" .
            " left join " . $this->dsc->table('region') . " as r on mri.region_id = r.region_id" .
            " where mri.ra_id = '$ra_id'";
        $res = $this->db->getAll($sql);
        if (!empty($region_ids) && !empty($res)) {
            foreach ($res as $k => $v) {
                if (in_array($v['region_id'], $region_ids)) {
                    $res[$k]['is_checked'] = 1;
                }
            }
        }
        return $res;
    }

    /* 地区列表 */
    private function get_transport_area($tid = 0)
    {
        $where = "";
        if ($tid == 0) {
            global $admin_id;
            $where .= " AND admin_id = '$admin_id' ";
        }
        $sql = " SELECT * FROM " . $GLOBALS['dsc']->table('goods_transport_extend') . " WHERE tid = '$tid' $where ORDER BY id DESC ";
        $transport_area = $GLOBALS['db']->getAll($sql);
        foreach ($transport_area as $key => $val) {
            if (!empty($val['top_area_id']) && !empty($val['area_id'])) {
                $area_map = [];
                $top_area_arr = explode(',', $val['top_area_id']);
                foreach ($top_area_arr as $k => $v) {
                    $top_area = get_table_date("region", "region_id='$v'", ['region_name'], 2);
                    $sql = " SELECT region_name FROM " . $GLOBALS['dsc']->table('region') . " WHERE parent_id = '$v' AND region_id IN ($val[area_id]) ";
                    $area_arr = $GLOBALS['db']->getCol($sql);
                    $area_list = implode(',', $area_arr);
                    $area_map[$k]['top_area'] = $top_area;
                    $area_map[$k]['area_list'] = $area_list;
                }
                $transport_area[$key]['area_map'] = $area_map;
            }
        }
        return $transport_area;
    }

    /* 快递列表 */
    private function get_transport_express($tid = 0)
    {
        $where = "";
        if ($tid == 0) {
            global $admin_id;
            $where .= " AND admin_id = '$admin_id' ";
        }
        $sql = " SELECT * FROM " . $GLOBALS['dsc']->table('goods_transport_express') . " WHERE tid = '$tid' $where ORDER BY id DESC ";
        $transport_express = $GLOBALS['db']->getAll($sql);
        foreach ($transport_express as $key => $val) {
            $transport_express[$key]['express_list'] = $this->get_express_list($val['shipping_id']);
        }
        return $transport_express;
    }

    /* 获取快递 */

    private function get_express_list($shipping_id = '')
    {
        $express_list = '';
        if (!empty($shipping_id)) {
            $sql = " SELECT shipping_name FROM " . $GLOBALS['dsc']->table('shipping') . " WHERE shipping_id IN ($shipping_id) ";
            $express_list = $GLOBALS['db']->getCol($sql);
            $express_list = implode(',', $express_list);
        }
        return $express_list;
    }
}
