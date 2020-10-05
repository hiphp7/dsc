<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Image;
use App\Models\GalleryAlbum;
use App\Models\PicAlbum;
use App\Models\SuppliersGoodsGallery;
use App\Models\Wholesale;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsManageService;

/**
 * 商品管理程序
 */
class GetAjaxContentController extends InitController
{
    protected $image;
    protected $baseRepository;
    protected $commonRepository;
    protected $goodsManageService;
    protected $dscRepository;

    public function __construct(
        Image $image,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        GoodsManageService $goodsManageService,
        DscRepository $dscRepository
    )
    {
        $this->image = $image;
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->goodsManageService = $goodsManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $admin_id = get_admin_id();
        $adminru = get_admin_ru_id();

        /* ------------------------------------------------------ */
        //-- 上传图片
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'upload_img') {
            $act_type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $result = array('error' => 0, 'pic' => '', 'name' => '');
            $typeArr = array("jpg", "png", "gif", "jepg"); //允许上传文件格式

            if (isset($_POST)) {
                $name = $_FILES['file']['name'];
                $size = $_FILES['file']['size'];
                $name_tmp = $_FILES['file']['tmp_name'];
                if (empty($name)) {
                    $result['error'] = $GLOBALS['_LANG']['not_select_img'];
                }
                $type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型
                if (!in_array($type, $typeArr)) {
                    $result['error'] = $GLOBALS['_LANG']['cat_prompt_file_type'];
                }
            }

            if ($act_type == 'goods_img') {
                /* 开始处理 start */
                $_FILES['goods_img'] = $_FILES['file'];
                $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
                $_POST['auto_thumb'] = 1; //自动生成缩略图
                $_REQUEST['goods_id'] = $id;
                $goods_id = $id;
                /* 开始处理 end */

                /* 处理商品图片 */
                $goods_img = '';  // 初始化商品图片
                $goods_thumb = '';  // 初始化商品缩略图
                $original_img = '';  // 初始化原始图片
                $old_original_img = '';  // 初始化原始图片旧图
                $img = '';

                // 如果上传了商品图片，相应处理
                if ($_FILES['goods_img']['tmp_name'] != '' && $_FILES['goods_img']['tmp_name'] != 'none') {
                    if (empty($is_url_goods_img)) {
                        $original_img = $this->image->upload_image($_FILES['goods_img'], array('type' => 1)); // 原始图片
                        $original_img = storage_public($original_img);
                    }

                    $goods_img = $original_img;   // 商品图片

                    /* 复制一份相册图片 */
                    /* 添加判断是否自动生成相册图片 */
                    if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                        $img = $original_img;   // 相册图片
                        $pos = strpos(basename($img), '.');
                        $newname = dirname($img) . '/' . $this->image->random_filename() . substr(basename($img), $pos);
                        copy($img, $newname);
                        $img = $newname;

                        $gallery_img = $img;
                        $gallery_thumb = $img;
                    }

                    // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
                    if ($proc_thumb && $this->image->gd_version() > 0 && $this->image->check_img_function($_FILES['goods_img']['type']) || $is_url_goods_img) {
                        if (empty($is_url_goods_img)) {
                            // 如果设置大小不为0，缩放图片
                            if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                                $goods_img = $this->image->make_thumb(array('img' => $goods_img, 'type' => 1), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                            }

                            /* 添加判断是否自动生成相册图片 */
                            if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                $newname = dirname($img) . '/' . $this->image->random_filename() . substr(basename($img), $pos);
                                copy($img, $newname);
                                $gallery_img = $newname;
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
                        }

                        // 相册缩略图
                        /* 添加判断是否自动生成相册图片 */
                        if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                            if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                                $gallery_thumb = $this->image->make_thumb(array('img' => $img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            }
                        }
                    }
                }


                // 是否上传商品缩略图
                if (isset($_FILES['goods_thumb']) && $_FILES['goods_thumb']['tmp_name'] != '' &&
                    isset($_FILES['goods_thumb']['tmp_name']) && $_FILES['goods_thumb']['tmp_name'] != 'none') {
                    // 上传了，直接使用，原始大小
                    $goods_thumb = $this->image->upload_image($_FILES['goods_thumb'], array('type' => 1));
                } else {
                    // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
                    if ($proc_thumb && isset($_POST['auto_thumb']) && !empty($original_img)) {
                        // 如果设置缩略图大小不为0，生成缩略图
                        if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                            $goods_thumb = $this->image->make_thumb(array('img' => $original_img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                        } else {
                            $goods_thumb = $original_img;
                        }
                    }
                }

                /* 重新格式化图片名称 */
                $original_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $original_img, 'source');
                $goods_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $goods_img, 'goods');
                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $goods_id, $goods_thumb, 'thumb');

                $path = storage_public();
                $original_img = $original_img ? str_replace($path, '', $original_img) : '';
                $goods_img = $goods_img ? str_replace($path, '', $goods_img) : '';
                $goods_thumb = $goods_thumb ? str_replace($path, '', $goods_thumb) : '';

                //将数据保存返回 by wu
                $result['data'] = array(
                    'original_img' => $original_img,
                    'goods_img' => $goods_img,
                    'goods_thumb' => $goods_thumb
                );

                if (empty($goods_id)) {
                    session()->put('goods.' . $admin_id . '.' . $goods_id, $result['data']);
                } else {
                    get_del_edit_goods_img($goods_id, 'wholesale');

                    Wholesale::where('goods_id', $goods_id)
                        ->update($result['data']);
                }

                $this->dscRepository->getOssAddFile($result['data']);

                /* 如果有图片，把商品图片加入图片相册 */
                if ($img) {
                    /* 重新格式化图片名称 */
                    if (empty($is_url_goods_img)) {
                        $img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $img, 'source');
                        $gallery_img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $gallery_img, 'goods');
                    } else {
                        $img = $original_img;
                        $gallery_img = $original_img;
                    }

                    $gallery_thumb = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');

                    $gallery_count = SuppliersGoodsGallery::where('goods_id', $goods_id)->max('img_desc');
                    $gallery_count = $gallery_count ? $gallery_count : 0;
                    $img_desc = $gallery_count + 1;

                    $gallery_img = $gallery_img ? str_replace($path, '', $gallery_img) : '';
                    $gallery_thumb = $gallery_thumb ? str_replace($path, '', $gallery_thumb) : '';
                    $img = $img ? str_replace($path, '', $img) : '';

                    $thumb_img_id[] = SuppliersGoodsGallery::insertGetId([
                        'goods_id' => $goods_id,
                        'img_url' => $gallery_img,
                        'img_desc' => $img_desc,
                        'thumb_url' => $gallery_thumb,
                        'img_original' => $img
                    ]);

                    $this->dscRepository->getOssAddFile(array($gallery_img, $gallery_thumb, $img));
                    if (!empty(session('thumb_img_id' . session('supply_id')))) {
                        $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('supply_id')));
                    }

                    session()->put('thumb_img_id' . session('supply_id'), $thumb_img_id);
                    $result['img_desc'] = $img_desc;
                }

                /* 结束处理 start */
                $pic_name = "";

                $goods_img = get_image_path($goods_img);

                $pic_url = $goods_img;

                $upload_status = 1;
                /* 结束处理 end */
            } elseif ($act_type == 'gallery_img') {
                /* 开始处理 start */
                $_FILES['img_url'] = array(
                    'name' => array($_FILES['file']['name']),
                    'type' => array($_FILES['file']['type']),
                    'tmp_name' => array($_FILES['file']['tmp_name']),
                    'error' => array($_FILES['file']['error']),
                    'size' => array($_FILES['file']['size'])
                );
                $_REQUEST['goods_id_img'] = $id;
                $_REQUEST['img_desc'] = array(array(''));
                $_REQUEST['img_file'] = array(array(''));
                /* 开始处理 end */

                $goods_id = !empty($_REQUEST['goods_id_img']) ? intval($_REQUEST['goods_id_img']) : 0;
                $img_desc = !empty($_REQUEST['img_desc']) ? $_REQUEST['img_desc'] : array();
                $img_file = !empty($_REQUEST['img_file']) ? $_REQUEST['img_file'] : array();
                $php_maxsize = ini_get('upload_max_filesize');
                $htm_maxsize = '2M';
                if ($_FILES['img_url']) {
                    foreach ($_FILES['img_url']['error'] as $key => $value) {
                        if ($value == 0) {
                            if (!$this->image->check_img_type($_FILES['img_url']['type'][$key])) {
                                $result['error'] = '1';
                                $result['massege'] = sprintf($GLOBALS['_LANG']['invalid_img_url'], $key + 1);
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

                $gallery_count = $this->goodsManageService->getGoodsGalleryCount($goods_id, 2);
                $result['img_desc'] = $gallery_count + 1;

                $this->goodsManageService->handleGalleryImageAdd($goods_id, $_FILES['img_url'], $img_desc, $img_file, '', '', 'ajax', $result['img_desc'], 2);

                clear_cache_files();

                $img_list = SuppliersGoodsGallery::whereRaw(1);

                if ($goods_id > 0) {
                    /* 图片列表 */
                    $img_list = $img_list->where('goods_id', $goods_id);
                } else {
                    $img_list = $img_list->where('goods_id', 0);

                    $img_id = session('thumb_img_id' . session('supply_id'));
                    if ($img_id) {
                        $img_id = $this->baseRepository->getExplode($img_id);
                        $img_list = $img_list->whereIn('img_id', $img_id);
                    }
                }

                $img_list = $img_list->orderBy('img_desc');
                $img_list = $this->baseRepository->getToArrayGet($img_list);

                if ($img_list) {
                    /* 格式化相册图片路径 */
                    if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                        foreach ($img_list as $key => $gallery_img) {

                            //图片显示
                            $gallery_img['img_original'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], true);
                            $img_list[$key]['img_url'] = $gallery_img['img_original'];
                            $gallery_img['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['thumb_url'], true);
                            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                        }
                    } else {
                        foreach ($img_list as $key => $gallery_img) {
                            $gallery_img['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['thumb_url'], true);
                            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                        }
                    }
                }

                $goods['goods_id'] = $goods_id;
                $this->smarty->assign('img_list', $img_list);

                $img_desc = [];
                if ($img_list) {
                    foreach ($img_list as $k => $v) {
                        $img_desc[] = $v['img_desc'];
                    }
                }

                $img_default = min($img_desc);

                $min_img_id = SuppliersGoodsGallery::where('goods_id', $goods_id)
                    ->where('img_desc', $img_default)
                    ->min('img_id');

                $this->smarty->assign('goods', $goods);

                /* 结束处理 start */
                $this_img_info = SuppliersGoodsGallery::where('goods_id', $goods_id)
                    ->orderBy('img_id', 'desc');
                $this_img_info = $this->baseRepository->getToArrayFirst($this_img_info);

                $result['img_id'] = $this_img_info['img_id'];
                $result['min_img_id'] = $min_img_id;
                $pic_name = "";

                $this_img_info['thumb_url'] = get_image_path($this_img_info['thumb_url']);

                $pic_url = $this_img_info['thumb_url'];

                $upload_status = 1;
                /* 结束处理 end */

                $result['external_url'] = "";
            }

            if ($upload_status) { //临时文件转移到目标文件夹
                $result['error'] = 0;
                $result['pic'] = $pic_url;
                $result['name'] = $pic_name;
            } else {
                $result['error'] = lang('suppliers/get_ajax_content.upload_error');
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 转移图片相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'album_move_back') {
            $result = array('content' => '', 'pic_id' => '');
            $pic_id = isset($_REQUEST['pic_id']) ? intval($_REQUEST['pic_id']) : 0;
            $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;

            PicAlbum::where('pic_id', $pic_id)
                ->where('ru_id', $adminru['ru_id'])
                ->update([
                    'album_id' => $album_id
                ]);

            $result['pic_id'] = $pic_id;
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_albun_pic') {
            $result = array('error' => '', 'pic_id' => '', 'content' => '');
            $allow_file_types = '|GIF|JPG|PNG|';
            $album_mame = isset($_REQUEST['album_mame']) ? addslashes($_REQUEST['album_mame']) : '';
            $album_desc = isset($_REQUEST['album_desc']) ? addslashes($_REQUEST['album_desc']) : '';
            $sort_order = isset($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;

            $object = GalleryAlbum::whereRaw(1);

            /*检查是否重复*/
            $where = [
                'album_mame' => $album_mame,
                'ru_id' => $adminru['ru_id'],
                'suppliers_id' => $adminru['suppliers_id']
            ];
            $is_only = $this->commonRepository->getManageIsOnly($object, $where);

            if ($is_only) {
                $result['error'] = 0;
                $result['content'] = lang('suppliers/get_ajax_content.album') . $album_mame . lang('suppliers/get_ajax_content.existing');
                return response()->json($result);
            }

            /* 取得文件地址 */
            $file_url = '';
            if ((isset($_FILES['album_cover']['error']) && $_FILES['album_cover']['error'] == 0) || (!isset($_FILES['album_cover']['error']) && isset($_FILES['album_cover']['tmp_name']) && $_FILES['album_cover']['tmp_name'] != 'none')) {
                // 检查文件格式
                if (!check_file_type($_FILES['album_cover']['tmp_name'], $_FILES['album_cover']['name'], $allow_file_types)) {
                    $result['error'] = 0;
                    $result['content'] = lang('suppliers/get_ajax_content.upload_type');
                    return response()->json($result);
                }

                // 复制文件
                $res = $this->upload_article_file($_FILES['album_cover']);
                if ($res != false) {
                    $file_url = $res;
                }
            }

            if ($file_url == '') {
                $file_url = $_POST['file_url'] ?? '';
            }

            $time = gmtime();
            $pic_id = GalleryAlbum::insertGetId([
                'album_mame' => $album_mame,
                'album_cover' => $file_url,
                'album_desc' => $album_desc,
                'sort_order' => $sort_order,
                'add_time' => $time,
                'ru_id' => $adminru['ru_id'],
                'suppliers_id' => $adminru['suppliers_id']
            ]);

            $result['error'] = 1;
            $result['pic_id'] = $pic_id;

            $album_list = get_goods_gallery_album(1, $adminru['ru_id'], ['album_id', 'album_mame'], 'ru_id');

            $html = '<li><a href="javascript:;" data-value="0" class="ftx-01">' . lang('suppliers/get_ajax_content.please_select') . '</a></li>';
            if ($album_list) {
                foreach ($album_list as $v) {
                    $html .= '<li><a href="javascript:;" data-value="' . $v['album_id'] . '" class="ftx-01">' . $v['album_mame'] . '</a></li>';
                }
            }

            $result['content'] = $html;
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 获取相册图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_albun_pic') {
            $result = array('error' => 0, 'message' => '', 'content' => '');
            $is_vis = !empty($_REQUEST['is_vis']) ? intval($_REQUEST['is_vis']) : 0;
            $inid = !empty($_REQUEST['inid']) ? trim($_REQUEST['inid']) : 0;

            $pic_list = getAlbumList();

            $this->smarty->assign('pic_list', $pic_list['list']);
            $this->smarty->assign('filter', $pic_list['filter']);
            $this->smarty->assign('temp', 'ajaxPiclist');
            $this->smarty->assign('is_vis', $is_vis);
            $this->smarty->assign('inid', $inid);

            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品选择相册图
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_goodsImg') {
            $result = array('error' => 0, 'message' => '');
            $pic_id = !empty($_REQUEST['pic_id']) ? trim($_REQUEST['pic_id']) : '';
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $inid = !empty($_REQUEST['inid']) ? trim($_REQUEST['inid']) : '';

            //初始化数据
            $thumb_img_id = [];
            $img_list = [];
            if ($pic_id) {
                $pic_id = $this->baseRepository->getExplode($pic_id);
                $img_list = PicAlbum::whereIn('pic_id', $pic_id);
                $img_list = $this->baseRepository->getToArrayGet($img_list);
            }

            if ($img_list) {
                $j = 0;
                foreach ($img_list as $key => $val) {
                    $j++;
                    //获取排序
                    if ($inid == 'gallery_album') {
                        $img_desc_new = 1;
                        if ($goods_id > 0) {
                            SuppliersGoodsGallery::where('goods_id', $goods_id)
                                ->increment('img_desc', 1);
                        }
                    } else {
                        $gallery_count = $this->goodsManageService->getGoodsGalleryCount($goods_id, 2);
                        $img_desc_new = $gallery_count + 1;
                    }

                    //处理链接
                    $val['pic_file'] = str_replace(' ', "", $val['pic_file'], $i);
                    $val['pic_image'] = str_replace(' ', "", $val['pic_image'], $i);
                    $val['pic_thumb'] = str_replace(' ', "", $val['pic_thumb'], $i);

                    //商品图片初始化
                    if ($j == 1) {
                        $result['data'] = array(
                            'original_img' => $val['pic_file'],
                            'goods_img' => $val['pic_image'],
                            'goods_thumb' => $val['pic_thumb']
                        );
                    } else {
                        $result['data'] = array();
                    }

                    $thumb_img_id[] = SuppliersGoodsGallery::insertGetId([
                        'goods_id' => $goods_id,
                        'img_url' => $val['pic_image'],
                        'img_desc' => $img_desc_new,
                        'thumb_url' => $val['pic_thumb'],
                        'img_original' => $val['pic_file']
                    ]);

                    $this->dscRepository->getOssAddFile($result['data']);
                }
            }

            if (session()->has('thumb_img_id' . session('supply_id')) && !empty(session('thumb_img_id' . session('supply_id'))) && is_array($thumb_img_id) && is_array(session('thumb_img_id' . session('supply_id')))) {
                $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('supply_id')));
            }

            session()->put('thumb_img_id' . session('supply_id'), $thumb_img_id);

            $goods_gallery_list = SuppliersGoodsGallery::whereRaw(1);

            if ($goods_id > 0) {
                /* 图片列表 */
                $goods_gallery_list = $goods_gallery_list->where('goods_id', $goods_id);
            } else {
                $goods_gallery_list = $goods_gallery_list->where('goods_id', 0);
                if (session()->has('thumb_img_id' . session('supply_id')) && !empty(session('thumb_img_id' . session('supply_id')))) {
                    $img_id = $this->baseRepository->getExplode(session('thumb_img_id' . session('supply_id')));
                    $goods_gallery_list = $goods_gallery_list->whereIn('img_id', $img_id);
                }
            }

            $goods_gallery_list = $goods_gallery_list->orderBy('img_desc');
            $goods_gallery_list = $this->baseRepository->getToArrayGet($goods_gallery_list);

            if ($goods_gallery_list) {
                foreach ($goods_gallery_list as $key => $val) {
                    if ($val['thumb_url']) {
                        $goods_gallery_list[$key]['thumb_url'] = get_image_path($val['thumb_url']);
                    }
                }
            }

            $this->smarty->assign('img_list', $goods_gallery_list);
            $result['content'] = $this->smarty->fetch('library/gallery_img.lbi');

            if (isset($result['data']['goods_thumb']) && $result['data']['goods_thumb']) {
                $result['data']['thumb'] = get_image_path($result['data']['goods_thumb']);
            } else {
                $result['data']['thumb'] = '';
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品编辑器
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'getFCKeditor') {
            $result = array('goods_desc' => 0);
            $content = isset($_REQUEST['content']) ? stripslashes($_REQUEST['content']) : '';
            $img_src = isset($_REQUEST['img_src']) ? trim($_REQUEST['img_src']) : '';

            if ($img_src) {
                $img_src = explode(',', $img_src);

                if (!empty($img_src)) {
                    foreach ($img_src as $v) {
                        $content .= "<p><img src='" . $v . "' /></p>";
                    }
                }
            }

            if (!empty($content)) {
                create_html_editor('goods_desc', trim($content));
                $result['goods_desc'] = $this->smarty->get_template_vars('FCKeditor');
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取筛选分类列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'filter_category') {
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $cat_type_show = empty($_REQUEST['cat_type_show']) ? 0 : intval($_REQUEST['cat_type_show']);
            $user_id = 0;
            $result = array('error' => 0, 'message' => '', 'content' => '');
            $table = isset($_REQUEST['table']) && $_REQUEST['table'] != 'undefined' ? trim($_REQUEST['table']) : 'wholesale_cat';

            //上级分类列表
            if ($cat_type_show == 1) {
                $parent_cat_list = get_seller_select_category($cat_id, 1, true, $user_id, $table);
                $filter_category_navigation = get_seller_array_category_info($parent_cat_list, $table);
            } else {
                $parent_cat_list = get_select_category($cat_id, 1, true, 0, $table);
                $filter_category_navigation = get_array_category_info($parent_cat_list, $table);
            }

            $cat_nav = "";
            if ($filter_category_navigation) {
                foreach ($filter_category_navigation as $key => $val) {
                    if ($key == 0) {
                        $cat_nav .= $val['cat_name'];
                    } elseif ($key > 0) {
                        $cat_nav .= " > " . $val['cat_name'];
                    }
                }
            } else {
                $cat_nav = "请选择分类";
            }
            $result['cat_nav'] = $cat_nav;

            //分类级别
            $cat_level = count($parent_cat_list);

            if ($cat_type_show == 1) {
                if ($cat_level <= 3) {
                    $filter_category_list = get_seller_category_list($cat_id, 2, $user_id);
                } else {
                    $filter_category_list = get_seller_category_list($cat_id, 0, $user_id);
                    $cat_level -= 1;
                }
            } else {
                //补充筛选商家分类
                $seller_shop_cat = seller_shop_cat($user_id);

                if ($cat_level <= 3) {
                    $filter_category_list = get_category_list($cat_id, 2, $seller_shop_cat, $user_id, $cat_level, $table);
                } else {
                    $filter_category_list = get_category_list($cat_id, 0, array(), $user_id, 0, $table);
                    $cat_level -= 1;
                }
            }

            $this->smarty->assign('user_id', $user_id); //分类等级

            if ($user_id) {
                $this->smarty->assign('seller_cat_type_show', $cat_type_show);
            } else {
                $this->smarty->assign('cat_type_show', $cat_type_show);
            }

            $this->smarty->assign('filter_category_level', $cat_level);

            if ($cat_type_show) {
                $this->smarty->assign('seller_filter_category_navigation', $filter_category_navigation);
                $this->smarty->assign('seller_filter_category_list', $filter_category_list);
                if (empty($filter_category_list)) {
                    $result['type'] = 1;
                }
                $result['content'] = $this->smarty->fetch('library/filter_category_seller.lbi');
            } else {
                $this->smarty->assign('table', $table);
                $this->smarty->assign('filter_category_navigation', $filter_category_navigation);
                $this->smarty->assign('filter_category_list', $filter_category_list);
                $result['content'] = $this->smarty->fetch('library/filter_category.lbi');
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取品牌列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_brand_list') {
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $this->smarty->assign('filter_brand_list', search_brand_list($goods_id, 0));
            $result['content'] = $this->smarty->fetch('library/search_brand_list.lbi');
            return response()->json($result);
        }
    }

    /* 上传文件 */
    private function upload_article_file($upload, $file = '')
    {
        $file_dir = storage_public(DATA_DIR . "/gallery_album");
        if (!file_exists($file_dir)) {
            if (!make_dir($file_dir)) {
                /* 创建目录失败 */
                return false;
            }
        }

        $filename = app(Image::class)->random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = storage_public(DATA_DIR . "/gallery_album/" . $filename);
        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/gallery_album/" . $filename;
        } else {
            return false;
        }
    }
}
