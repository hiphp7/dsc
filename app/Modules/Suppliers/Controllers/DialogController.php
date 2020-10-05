<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Image;
use App\Models\Attribute;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\SuppliersGoodsGallery;
use App\Models\VolumePrice;
use App\Models\WholesaleGoodsAttr;
use App\Models\WholesaleVolumePrice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsManageService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Wholesale\GoodsManageService as WholesaleGoodsManage;

/**
 * 属性规格管理
 */
class DialogController extends InitController
{
    protected $image;
    protected $wholesaleGoodsManage;
    protected $baseRepository;
    protected $goodsManageService;
    protected $goodsAttrService;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $dscRepository;

    public function __construct(
        Image $image,
        WholesaleGoodsManage $wholesaleGoodsManage,
        BaseRepository $baseRepository,
        GoodsManageService $goodsManageService,
        GoodsAttrService $goodsAttrService,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        DscRepository $dscRepository
    )
    {
        $this->image = $image;
        $this->wholesaleGoodsManage = $wholesaleGoodsManage;
        $this->baseRepository = $baseRepository;
        $this->goodsManageService = $goodsManageService;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();
        $admin_id = $adminru['user_id'];

        /*------------------------------------------------------ */
        //-- 商品单选复选属性手工录入
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'attr_input_type') {
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
            $result = ['content' => '', 'sgs' => ''];

            $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : [];
            $attr_value_list = isset($_REQUEST['attr_value_list']) ? $_REQUEST['attr_value_list'] : [];

            /* 插入、更新、删除数据 */
            foreach ($attr_value_list as $key => $attr_value) {
                if ($attr_value) {

                    $attr_value = trim($attr_value);

                    if ($goods_attr_id[$key]) {
                        WholesaleGoodsAttr::where('goods_attr_id', $goods_attr_id[$key])
                            ->update([
                                'attr_value' => $attr_value
                            ]);
                    } else {
                        $attr_sort = WholesaleGoodsAttr::where('attr_id', $attr_id);

                        if ($goods_id) {
                            $attr_sort = $attr_sort->where('goods_id', $goods_id);
                        } else {
                            $attr_sort = $attr_sort->where('goods_id', 0)
                                ->where('admin_id', $admin_id);
                        }

                        $attr_sort = $attr_sort->max('attr_sort');
                        $max_attr_sort = $attr_sort ? $attr_sort : 0;

                        if ($max_attr_sort) {
                            $key = $max_attr_sort + 1;
                        } else {
                            $key += 1;
                        }

                        WholesaleGoodsAttr::insert([
                            'attr_id' => $attr_id,
                            'goods_id' => $goods_id,
                            'attr_value' => $attr_value,
                            'attr_sort' => $key,
                            'admin_id' => $admin_id
                        ]);
                    }
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

        /* ------------------------------------------------------ */
        //-- 添加属性图片 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_attr_img') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_name = !empty($_REQUEST['goods_name']) ? trim($_REQUEST['goods_name']) : '';
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

            $goods_info = Goods::where('goods_id', $goods_id);
            $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

            if (!isset($goods_info['goods_name'])) {
                $goods_info['goods_name'] = $goods_name;
            }

            $goods_attr_info = GoodsAttr::where('goods_id', $goods_id)
                ->where('attr_id', $attr_id)
                ->where('goods_attr_id', $goods_attr_id);
            $goods_attr_info = $this->baseRepository->getToArrayFirst($goods_attr_info);

            $attr_info = Attribute::where('attr_id', $attr_id);
            $attr_info = $this->baseRepository->getToArrayFirst($attr_info);

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

        /* ------------------------------------------------------ */
        //-- 添加属性图片插入数据 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_attr_img') {
            load_helper('goods');

            $result = array('error' => 0, 'message' => '', 'content' => '', 'is_checked' => 0);

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
            $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
            $img_url = !empty($_REQUEST['img_url']) ? $_REQUEST['img_url'] : '';

            if (!empty($_FILES['attr_img_flie'])) {
                $other['attr_img_flie'] = get_upload_pic('attr_img_flie');
                $this->dscRepository->getOssAddFile(array($other['attr_img_flie']));
            } else {
                $other['attr_img_flie'] = '';
            }

            $goods_attr_info = GoodsAttr::where('goods_id', $goods_id)
                ->where('attr_id', $attr_id)
                ->where('goods_attr_id', $goods_attr_id);
            $goods_attr_info = $this->baseRepository->getToArrayFirst($goods_attr_info);

            if (empty($other['attr_img_flie'])) {
                $other['attr_img_flie'] = $goods_attr_info['attr_img_flie'] ?? '';
            } else {
                if (isset($goods_attr_info['attr_img_flie'])) {
                    dsc_unlink(storage_public($goods_attr_info['attr_img_flie']));
                }
            }

            $other['attr_img_site'] = !empty($_REQUEST['attr_img_site']) ? $_REQUEST['attr_img_site'] : '';
            $other['attr_checked'] = !empty($_REQUEST['attr_checked']) ? intval($_REQUEST['attr_checked']) : 0;
            $other['attr_gallery_flie'] = $img_url;

            if ($other['attr_checked'] == 1) {
                GoodsAttr::where('attr_id', $attr_id)
                    ->where('goods_id', $goods_id)
                    ->update([
                        'attr_checked' => 0
                    ]);

                $result['is_checked'] = 1;
            }

            GoodsAttr::where('goods_attr_id', $goods_attr_id)
                ->where('attr_id', $attr_id)
                ->where('goods_id', $goods_id)
                ->update($other);

            $result['goods_attr_id'] = $goods_attr_id;

            $goods = get_admin_goods_info($goods_id);

            if ($GLOBALS['_CFG']['add_shop_price'] == 0 && $goods['model_attr'] == 0) {
                /* 同步前台商品详情价格与商品列表价格一致 start */
                if ($other['attr_checked'] == 1) {
                    $properties = $this->goodsAttrService->getGoodsProperties($goods_id);  // 获得商品的规格和属性
                    $spe = !empty($properties['spe']) ? array_values($properties['spe']) : $properties['spe'];

                    $arr = array();
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

                            if ($promote_price > 0) {
                                $promote_price = bargain_price($promote_price, $goods['promote_start_date'], $goods['promote_end_date']);
                            } else {
                                $promote_price = 0;
                            }

                            if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date']) {
                                $promote_price = $products['product_promote_price'];
                            }

                            $other = array(
                                'product_id' => $products['product_id'],
                                'product_price' => $products['product_price'],
                                'product_promote_price' => $promote_price
                            );

                            Goods::where('goods_id', $goods_id)
                                ->update($other);
                        }
                    }
                }
            } else {
                if ($goods['model_attr'] > 0) {
                    $goods_other = array(
                        'product_table' => '',
                        'product_id' => 0,
                        'product_price' => 0,
                        'product_promote_price' => 0
                    );

                    Goods::where('goods_id', $goods_id)
                        ->update($goods_other);
                }
            }
            /* 同步前台商品详情价格与商品列表价格一致 end */

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除属性图片 //ecmoban模板堂 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_attr_img') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;

            $attr_img_flie = GoodsAttr::where('goods_attr_id', $goods_attr_id)->value('attr_img_flie');
            $attr_img_flie = $attr_img_flie ? $attr_img_flie : '';

            if ($attr_img_flie) {
                $this->dscRepository->getOssDelFile([$attr_img_flie]);
                dsc_unlink(storage_public($attr_img_flie));
            }

            GoodsAttr::where('goods_attr_id', $goods_attr_id)
                ->update([
                    'attr_img_flie' => ''
                ]);

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 选择属性图片 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'choose_attrImg') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_attr_id = empty($_REQUEST['goods_attr_id']) ? 0 : intval($_REQUEST['goods_attr_id']);

            $attr_gallery_flie = GoodsAttr::where('goods_attr_id', $goods_attr_id)
                ->where('goods_id', $goods_id)
                ->value('attr_gallery_flie');
            $attr_gallery_flie = $attr_gallery_flie ?? '';

            /* 删除数据 */
            $img_list = SuppliersGoodsGallery::whereRaw(1);

            //处理添加商品时相册图片串图问题
            $thumb_img_id = session('thumb_img_id' . session('supply_id'));
            if (empty($goods_id) && $thumb_img_id) {
                $thumb_img_id = $this->baseRepository->getExplode($thumb_img_id);
                $img_list = $img_list->where('goods_id', 0)
                    ->whereIn('img_id', $thumb_img_id);
            } else {
                $img_list = $img_list->where('goods_id', $goods_id);
            }

            $img_list = $this->baseRepository->getToArrayGet($img_list);

            $str = "<ul>";
            if ($img_list) {
                foreach ($img_list as $idx => $row) {
                    $row['thumb_url'] = get_image_path(0, $row['thumb_url']); //处理图片地址
                    if ($attr_gallery_flie == $row['img_url']) {
                        $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')" class="on"><img src="' . $row['thumb_url'] . '" width="87" /><i><img src="images/yes.png"></i></li>';
                    } else {
                        $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')"><img src="' . $row['thumb_url'] . '" width="87" /><i><img src="images/gallery_yes.png" width="30" height="30"></i></li>';
                    }
                }
            }
            $str .= "</ul>";

            $result['content'] = $str;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 选择属性图片 --zhuo
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_gallery_attr') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = intval($_REQUEST['goods_id']);
            $goods_attr_id = intval($_REQUEST['goods_attr_id']);
            $gallery_id = intval($_REQUEST['gallery_id']);

            if (!empty($gallery_id)) {
                $img = SuppliersGoodsGallery::where('img_id');
                $img = $this->baseRepository->getToArrayFirst($img);

                $result['img_id'] = $img['img_id'] ?? 0;
                $result['img_url'] = $img['img_url'] ?? '';

                GoodsAttr::where('goods_attr_id', $goods_attr_id)
                    ->where('goods_id', $goods_id)
                    ->update([
                        'attr_gallery_flie' => $img['img_url']
                    ]);
            } else {
                $result['error'] = 1;
            }

            $result['goods_attr_id'] = $goods_attr_id;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加图片
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'pic_album') {
            $result = array('content' => '', 'sgs' => '');
            $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $this->smarty->assign('album_id', $album_id);
            $cat_select = gallery_cat_list($album_id, 0, false, 0, true, 0, $adminru['suppliers_id']);

            /* 简单处理缩进 */
            if ($cat_select) {
                foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                        $level = str_repeat('&nbsp;', $v['level'] * 4);
                        $cat_select[$k]['name'] = $level . $v['name'];
                    }
                }
            }

            $this->smarty->assign('cat_select', $cat_select);
            $album_mame = get_goods_gallery_album(0, $album_id, array('album_mame'));
            $this->smarty->assign('album_mame', $album_mame);
            $this->smarty->assign('temp', $_REQUEST['act']);
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 转移相册
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'album_move') {
            $result = array('content' => '', 'pic_id' => '', 'old_album_id' => '');
            $pic_id = isset($_REQUEST['pic_id']) ? intval($_REQUEST['pic_id']) : 0;
            $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
            $this->smarty->assign("temp", $temp);

            /* 获取全部相册 */
            $cat_select = gallery_cat_list(0, 0, false, 0, true, 0, $adminru['suppliers_id']);

            /* 简单处理缩进 */
            if ($cat_select) {
                foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                        $level = str_repeat('&nbsp;', $v['level'] * 4);
                        $cat_select[$k]['name'] = $level . $v['name'];
                    }
                }
            }

            $this->smarty->assign('cat_select', $cat_select);

            /* 获取该图片所属相册 */
            $album_id = gallery_pic_album(0, $pic_id, array('album_id'));
            $this->smarty->assign('album_id', $album_id);

            $result['pic_id'] = $pic_id;
            $result['old_album_id'] = $album_id;
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加相册
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_albun_pic') {
            $result = array('content' => '', 'pic_id' => '', 'old_album_id' => '');
            $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
            $this->smarty->assign("temp", $temp);

            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 编辑商品图片外链地址
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_gallery_url') {
            $result = array('content' => '', 'sgs' => '', 'error' => 0);

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $img_id = isset($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : 0;
            $external_url = isset($_REQUEST['external_url']) ? addslashes(trim($_REQUEST['external_url'])) : '';

            $count = SuppliersGoodsGallery::where('external_url', $external_url)
                ->where('goods_id', $goods_id)
                ->where('img_id', '<>', $img_id)
                ->count();

            if ($count && !empty($external_url)) {
                $result['error'] = 1;
            } else {
                SuppliersGoodsGallery::where('img_id', $img_id)
                    ->update([
                        'external_url' => $external_url
                    ]);
            }

            $result['img_id'] = $img_id;

            if (!empty($external_url)) {
                $result['external_url'] = $external_url;
            } else {
                $thumb_url = SuppliersGoodsGallery::where('img_id', $img_id)->value('thumb_url');
                $thumb_url = $thumb_url ? get_image_path($thumb_url) : '';

                $result['external_url'] = $thumb_url;
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加商品图片外链地址
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_external_url') {
            $result = ['content' => '', 'sgs' => '', 'error' => 0];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $this->smarty->assign('goods_id', $goods_id);
            $result['content'] = $GLOBALS['smarty']->fetch('library/external_url_list.lbi');

            $result['goods_id'] = $goods_id;
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 插入商品图片外链地址
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_external_url') {
            $result = ['content' => '', 'sgs' => '', 'error' => 0];

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $external_url_list = isset($_REQUEST['external_url_list']) ? $_REQUEST['external_url_list'] : array();

            /* 是否处理缩略图 */
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;

            if ($external_url_list) {
                $desc = SuppliersGoodsGallery::where('goods_id', $goods_id)->max('img_desc');
                $desc = $desc ? $desc : 0;

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
                if ($external_url_list) {
                    foreach ($external_url_list as $key => $image_urls) {
                        if ($image_urls) {
                            if (!empty($image_urls) && ($image_urls != $GLOBALS['_LANG']['img_file']) && ($image_urls != 'http://') && (strpos($image_urls, 'http://') !== false || strpos($image_urls, 'https://') !== false)) {
                                if (get_http_basename($image_urls, $admin_temp_dir)) {
                                    $image_url = trim($image_urls);
                                    //定义原图路径
                                    $down_img = $admin_temp_dir . "/" . basename($image_url);

                                    $img_wh = $this->image->get_width_to_height($down_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                                    $GLOBALS['_CFG']['image_width'] = isset($img_wh['image_width']) ? $img_wh['image_width'] : $GLOBALS['_CFG']['image_width'];
                                    $GLOBALS['_CFG']['image_height'] = isset($img_wh['image_height']) ? $img_wh['image_height'] : $GLOBALS['_CFG']['image_height'];

                                    if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                                        $goods_img = $this->image->make_thumb(array('img' => $down_img, 'type' => 1), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                                    } else {
                                        $goods_img = $this->image->make_thumb(array('img' => $down_img, 'type' => 1));
                                    }

                                    // 生成缩略图
                                    if ($proc_thumb) {
                                        $thumb_url = $this->image->make_thumb(array('img' => $down_img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                                        $thumb_url = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                                    } else {
                                        $thumb_url = $this->image->make_thumb(array('img' => $down_img, 'type' => 1));
                                        $thumb_url = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                                    }

                                    $img_original = $this->goodsManageService->reformatImageName('gallery', $goods_id, $down_img, 'source');
                                    $img_url = $this->goodsManageService->reformatImageName('gallery', $goods_id, $goods_img, 'goods');

                                    $path = storage_public();
                                    $img_url = $img_url ? str_replace($path, '', $img_url) : '';
                                    $thumb_url = $thumb_url ? str_replace($path, '', $thumb_url) : '';
                                    $img_original = $img_original ? str_replace($path, '', $img_original) : '';

                                    $desc += 1;
                                    $thumb_img_id[] = SuppliersGoodsGallery::insertGetId([
                                        'goods_id' => $goods_id,
                                        'img_url' => $img_url,
                                        'img_desc' => $desc,
                                        'thumb_url' => $thumb_url,
                                        'img_original' => $img_original
                                    ]);

                                    dsc_unlink($down_img);
                                }
                            }

                            $this->dscRepository->getOssAddFile([$img_url, $thumb_url, $img_original]);
                        }
                    }
                }

                if (!empty(session('thumb_img_id' . session('supply_id')))) {
                    $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('supply_id')));
                }

                session()->put('thumb_img_id' . session('supply_id'), $thumb_img_id);
            }
            /* 图片列表 */
            $img_list = SuppliersGoodsGallery::where('goods_id', $goods_id);

            $img_id = session('thumb_img_id' . session('supply_id'));
            if ($img_id && $goods_id == 0) {
                $img_id = $this->baseRepository->getExplode($img_id);
                $img_list = $img_list->whereIn('img_id', $img_id);
            }

            $img_list = $img_list->orderBy('img_desc');
            $img_list = $this->baseRepository->getToArrayGet($img_list);

            /* 格式化相册图片路径 */
            if ($img_list) {
                if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                    foreach ($img_list as $key => $gallery_img) {
                        $img_list[$key] = $gallery_img;

                        //图片显示
                        $gallery_img['img_original'] = get_image_path($gallery_img['img_original']);

                        $img_list[$key]['img_url'] = $gallery_img['img_original'];

                        $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                        $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
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
            }

            $this->smarty->assign('img_list', $img_list);
            $this->smarty->assign('goods_id', $goods_id);
            $result['content'] = $GLOBALS['smarty']->fetch('library/gallery_img.lbi');

            $result['goods_id'] = $goods_id;
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 商城轮播图
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'shop_banner') {
            $this->smarty->assign("temp", "shop_banner");

            $result = array('content' => '', 'mode' => '');
            $is_vis = isset($_REQUEST['is_vis']) ? intval($_REQUEST['is_vis']) : 0;
            $inid = isset($_REQUEST['inid']) ? trim($_REQUEST['inid']) : ''; //div标识
            $image_type = isset($_REQUEST['image_type']) ? intval($_REQUEST['image_type']) : 0;

            $cat_select = gallery_cat_list(0, 0, false, 0, true, $adminru['ru_id'], $adminru['suppliers_id']);

            /* 简单处理缩进 */
            $i = 0;
            $default_album = 0;
            if ($cat_select) {
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
            }

            if ($default_album > 0) {
                $pic_list = getAlbumList($default_album);

                $this->smarty->assign('pic_list', $pic_list['list']);
                $this->smarty->assign('filter', $pic_list['filter']);
                $this->smarty->assign('album_id', $default_album);
            }

            $this->smarty->assign('cat_select', $cat_select);
            $this->smarty->assign('is_vis', $is_vis);

            $this->smarty->assign('image_type', 0);
            $this->smarty->assign('log_type', 'image');
            $this->smarty->assign('image_type', $image_type);
            $this->smarty->assign('inid', $inid);
            $result['content'] = $GLOBALS['smarty']->fetch('library/album_dialog.lbi');

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商家订单列表导出弹窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'merchant_download') {
            $result = array('content' => '');
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
        //-- 删除商品优惠阶梯价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_volume') {
            $result = array('content' => '', 'sgs' => '');

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            VolumePrice::where('id', $volume_id)
                ->delete();

            $volume_price_list = $this->goodsCommonService->getVolumePriceList($goods_id);
            if (!$volume_price_list) {
                Goods::where('goods_id', $goods_id)
                    ->update([
                        'is_volume' => 0
                    ]);
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除批发商品优惠阶梯价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_wholesale_volume') {
            $result = array('content' => '', 'sgs' => '');

            $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            WholesaleVolumePrice::where('id', $volume_id)->delete();

            return response()->json($result);
        }
    }
}
