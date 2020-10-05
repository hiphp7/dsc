<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Image;
use App\Models\GalleryAlbum;
use App\Models\PicAlbum;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsManageService;
use App\Services\Other\GalleryAlbumManageService;

/**
 * 图片库管理
 */
class GalleryAlbumController extends InitController
{
    protected $image;
    protected $commonRepository;
    protected $galleryAlbumManageService;
    protected $goodsManageService;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        Image $image,
        CommonRepository $commonRepository,
        GalleryAlbumManageService $galleryAlbumManageService,
        GoodsManageService $goodsManageService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->image = $image;
        $this->commonRepository = $commonRepository;
        $this->galleryAlbumManageService = $galleryAlbumManageService;
        $this->goodsManageService = $goodsManageService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        $this->smarty->assign("priv_ru", 1);
        $this->smarty->assign('menu_select', array('action' => '01_suppliers_goods', 'current' => '04_gallery_album'));

        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|';

        /* ------------------------------------------------------ */
        //-- 列表
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('suppliers_gallery_album');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gallery_album']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_album'], 'href' => 'gallery_album.php?act=add', 'class' => 'icon-plus']);

            $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);

            if ($parent_id > 0) {
                $parent_album_id = GalleryAlbum::where('album_id', $parent_id)->value('parent_album_id');
                $parent_album_id = $parent_album_id ? $parent_album_id : 0;

                $this->smarty->assign('action_link2', array('text' => lang('suppliers/gallery_album.return'), 'href' => 'gallery_album.php?act=list&parent_id=' . $parent_album_id));
            }

            $album_list = $this->galleryAlbumManageService->getGalleryAlbumList($adminru['ru_id']);

            $this->smarty->assign('gallery_album', $album_list['album_list']);
            $this->smarty->assign('filter', $album_list['filter']);
            $this->smarty->assign('record_count', $album_list['record_count']);
            $this->smarty->assign('page_count', $album_list['page_count']);
            $this->smarty->assign('full_page', 1);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($album_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return $this->smarty->display("gallery_album.dwt");
        }

        /* ------------------------------------------------------ */
        //-- 排序、翻页查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('suppliers_gallery_album');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $album_list = $this->galleryAlbumManageService->getGalleryAlbumList($adminru['ru_id']);

            $this->smarty->assign('gallery_album', $album_list['album_list']);
            $this->smarty->assign('filter', $album_list['filter']);
            $this->smarty->assign('record_count', $album_list['record_count']);
            $this->smarty->assign('page_count', $album_list['page_count']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($album_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            //跳转页面
            return make_json_result($this->smarty->fetch('gallery_album.dwt'), '', ['filter' => $album_list['filter'], 'page_count' => $album_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 添加/编辑
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('suppliers_gallery_album');

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_album']);
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['gallery_album'], 'href' => 'gallery_album.php?act=list', 'class' => 'icon-reply'));

            $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
            $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $album_info = [];
            if ($_REQUEST['act'] == 'add') {

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

                $album_info['parent_album_id'] = $parent_id;
                $this->smarty->assign('cat_select', $cat_select);
            } else {
                $cat_select = gallery_cat_list(0, $parent_id, false, 0, true, 0, $adminru['suppliers_id']);
                $cat_child = get_cat_child($album_id);

                /* 简单处理缩进 */
                if ($cat_select) {
                    foreach ($cat_select as $k => $v) {
                        if ($v['level']) {
                            $level = str_repeat('&nbsp;', $v['level'] * 4);
                            $cat_select[$k]['name'] = $level . $v['name'];
                        }
                        if (!empty($cat_child) && in_array($v['album_id'], $cat_child)) {
                            unset($cat_select[$k]);
                        }
                    }
                }

                $this->smarty->assign('cat_select', $cat_select);
            }

            if ($album_id > 0) {
                $album_info = get_goods_gallery_album(2, $album_id);
            }

            $this->smarty->assign("album_info", $album_info);
            $form_action = ($_REQUEST['act'] == 'add') ? "insert" : "update";
            $this->smarty->assign("form_action", $form_action);

            return $this->smarty->display("gallery_album_info.dwt");
        }

        /* ------------------------------------------------------ */
        //-- 插入/更新
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            admin_priv('suppliers_gallery_album');

            $album_mame = isset($_REQUEST['album_mame']) ? addslashes($_REQUEST['album_mame']) : '';
            $album_desc = isset($_REQUEST['album_desc']) ? addslashes($_REQUEST['album_desc']) : '';
            $sort_order = isset($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            $parent_id = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;

            $object = GalleryAlbum::whereRaw(1);

            if ($_REQUEST['act'] == 'insert') {

                /*检查是否重复*/
                $where = [
                    'album_mame' => $album_mame,
                    'suppliers_id' => $adminru['suppliers_id']
                ];
                $is_only = $this->commonRepository->getManageIsOnly($object, $where);

                if ($is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($album_mame)), 1);
                }

                /* 取得文件地址 */
                $file_url = '';
                if ((isset($_FILES['album_cover']['error']) && $_FILES['album_cover']['error'] == 0) || (!isset($_FILES['album_cover']['error']) && isset($_FILES['album_cover']['tmp_name']) && $_FILES['album_cover']['tmp_name'] != 'none')) {
                    // 检查文件格式
                    if (!check_file_type($_FILES['album_cover']['tmp_name'], $_FILES['album_cover']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['invalid_file']);
                    }

                    // 复制文件
                    $res = $this->galleryAlbumManageService->uploadAlbumFile($_FILES['album_cover']);
                    if ($res != false) {
                        $file_url = $res;
                    }
                }

                if ($file_url == '') {
                    $file_url = $_POST['file_url'] ?? '';
                }

                $time = gmtime();
                $album_id = GalleryAlbum::insertGetId([
                    'parent_album_id' => $parent_id,
                    'album_mame' => $album_mame,
                    'album_cover' => $file_url,
                    'album_desc' => $album_desc,
                    'sort_order' => $sort_order,
                    'add_time' => $time,
                    'suppliers_id' => $adminru['suppliers_id'],
                    'ru_id' => $adminru['ru_id']
                ]);

                if ($album_id > 0) {
                    admin_log(addslashes($album_mame), 'add', 'gallery_album');
                }

                $link[0]['text'] = $GLOBALS['_LANG']['continue_add_album'];
                $link[0]['href'] = 'gallery_album.php?act=add';

                $link[1]['text'] = $GLOBALS['_LANG']['bank_list'];
                $link[1]['href'] = 'gallery_album.php?act=list';

                return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
            } else {
                $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

                /*检查是否重复*/
                $where = [
                    'album_mame' => $album_mame,
                    'id' => [
                        'filed' => [
                            'album_id' => $album_id
                        ],
                        'condition' => '<>'
                    ],
                    'suppliers_id' => $adminru['suppliers_id']
                ];
                $is_only = $this->commonRepository->getManageIsOnly($object, $where);

                if ($is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($album_mame)), 1);
                }

                /* 取得文件地址 */
                $file_url = '';
                if ((isset($_FILES['album_cover']['error']) && $_FILES['album_cover']['error'] == 0) || (!isset($_FILES['album_cover']['error']) && isset($_FILES['album_cover']['tmp_name']) && $_FILES['album_cover']['tmp_name'] != 'none')) {
                    // 检查文件格式
                    if (!check_file_type($_FILES['album_cover']['tmp_name'], $_FILES['album_cover']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['invalid_file']);
                    }

                    // 复制文件
                    $res = $this->galleryAlbumManageService->uploadAlbumFile($_FILES['album_cover']);
                    if ($res != false) {
                        $file_url = $res;
                    }
                }

                if ($file_url == '') {
                    $file_url = $_POST['file_url'] ?? '';
                }

                /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
                $old_url = get_goods_gallery_album(0, $album_id, ['album_cover']);
                if ($old_url != '' && $old_url != $file_url && strpos($old_url, 'http: ') === false && strpos($old_url, 'https: ') === false) {
                    dsc_unlink(storage_public($old_url));
                    $del_arr_img[] = $old_url;

                    $this->dscRepository->getOssDelFile($del_arr_img);
                }

                $res = GalleryAlbum::where('album_id', $album_id)
                    ->update([
                        'album_mame' => $album_mame,
                        'album_cover' => $file_url,
                        'album_desc' => $album_desc,
                        'sort_order' => $sort_order,
                        'parent_album_id' => $parent_id
                    ]);

                if ($res) {
                    admin_log(addslashes($album_mame), 'edit', 'gallery_album');
                }

                $link[0]['text'] = $GLOBALS['_LANG']['bank_list'];
                $link[0]['href'] = 'gallery_album.php?act=list';
                return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
            }
        }

        /* ------------------------------------------------------ */
        //-- 查看图片
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view') {
            admin_priv('suppliers_gallery_album');

            $album_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $album_mame = GalleryAlbum::where('album_id', $album_id)->value('album_mame');
            $album_mame = $album_mame ? $album_mame : '';

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', sprintf($GLOBALS['_LANG']['view_pic'], stripslashes($album_mame)));
            $this->smarty->assign('action_link', array('text' => lang('suppliers/gallery_album.upload_img'), 'spec' => "ectype='addpic_album'"));
            $this->smarty->assign('album_id', $album_id);

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

            $pic_album = $this->galleryAlbumManageService->getPicAlbumList($album_id);
            $this->smarty->assign('pic_album', $pic_album['pic_list']);
            $this->smarty->assign('filter', $pic_album['filter']);
            $this->smarty->assign('record_count', $pic_album['record_count']);
            $this->smarty->assign('page_count', $pic_album['page_count']);
            $this->smarty->assign('full_page', 1);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($pic_album, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return $this->smarty->display("pic_album.dwt");
        }

        /* ------------------------------------------------------ */
        //-- 排序、翻页查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'pic_query') {
            $check_auth = check_authz_json('suppliers_gallery_album');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $pic_album = $this->galleryAlbumManageService->getPicAlbumList();
            $this->smarty->assign('pic_album', $pic_album['pic_list']);
            $this->smarty->assign('filter', $pic_album['filter']);
            $this->smarty->assign('record_count', $pic_album['record_count']);
            $this->smarty->assign('page_count', $pic_album['page_count']);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($pic_album, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return make_json_result($this->smarty->fetch('pic_album.dwt'), '', array('filter' => $pic_album['filter'], 'page_count' => $pic_album['page_count']));
        }

        /* ------------------------------------------------------ */
        //-- 删除
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('suppliers_gallery_album');
            if ($check_auth !== true) {
                return $check_auth;
            }

            load_helper(['visual']);

            $album_id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;

            //获取下级相册数量
            $album_count = GalleryAlbum::where('parent_album_id', $album_id)->count();

            //存在下级相册 不让删除
            if ($album_count > 0) {
                return make_json_error(lang('suppliers/gallery_album.not_del'));
            } else {

                /* 删除原来的文件 */
                $old_url = get_goods_gallery_album(0, $album_id, array('album_cover'));
                if ($old_url != '' && @strpos($old_url, 'http://') === false && @strpos($old_url, 'https://') === false) {
                    dsc_unlink(storage_public($old_url));
                }

                //删除该相册目录下的所以图片
                $dir = storage_public(DATA_DIR . '/gallery_album/' . $album_id); //模板目录
                getDelDirAndFile($dir);

                $album_mame = GalleryAlbum::where('album_id', $album_id)->value('album_mame');
                $album_mame = $album_mame ? $album_mame : '';

                //删除图片数据库
                $sql = "DELETE FROM" . $this->dsc->table('pic_album') . "WHERE album_id = " . $album_id;
                $this->db->query($sql);

                PicAlbum::where('album_id', $album_id)->where('ru_id', $adminru['ru_id'])->delete();

                $res = GalleryAlbum::where('album_id', $album_id)->where('ru_id', $adminru['ru_id'])->delete();

                if ($res) {
                    admin_log(addslashes($album_mame), 'remove', 'gallery_album');
                }

                $url = 'gallery_album.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除图片
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'pic_remove') {
            $check_auth = check_authz_json('suppliers_gallery_album');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['error' => '', 'content' => '', 'url' => ''];
            $pic_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 删除原来的文件 */
            $pic_info = gallery_pic_album(2, $pic_id, ['pic_file', 'pic_thumb', 'pic_image', 'album_id']);

            if ($pic_info) {
                /* 删除原图 */
                if ($pic_info['pic_file'] != '' && @strpos($pic_info['pic_file'], 'http://') === false && @strpos($pic_info['pic_file'], 'https://') === false) {
                    dsc_unlink(storage_public($pic_info['pic_file']));
                    $arr_img[] = $pic_info['pic_file'];
                }

                /* 删除缩略图 */
                if ($pic_info['pic_thumb'] != '' && @strpos($pic_info['pic_thumb'], 'http://') === false && @strpos($pic_info['pic_thumb'], 'https://') === false) {
                    dsc_unlink(storage_public($pic_info['pic_thumb']));
                    $arr_img[] = $pic_info['pic_thumb'];
                }

                /* 删除图 */
                if ($pic_info['pic_image'] != '' && @strpos($pic_info['pic_image'], 'http://') === false && @strpos($pic_info['pic_image'], 'https://') === false) {
                    dsc_unlink(storage_public($pic_info['pic_image']));
                    $arr_img[] = $pic_info['pic_image'];
                }

                $this->dscRepository->getOssDelFile($arr_img);
            }

            $result['id'] = $pic_id;

            $res = PicAlbum::where('pic_id', $pic_id)->delete();
            if ($res) {
                $result['error'] = 0;
            } else {
                $result['error'] = 1;
                $result['content'] = lang('suppliers/gallery_album.system_error');
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 上传图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_pic') {
            $check_auth = check_authz_json('suppliers_gallery_album');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $bucket_info = $this->dscRepository->getBucketInfo();

            $result = array('error' => 0, 'pic' => '', 'name' => '');
            $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;

            $path_images = storage_public(DATA_DIR . "/gallery_album/" . $album_id . '/images/');
            $path_original_img = storage_public(DATA_DIR . "/gallery_album/" . $album_id . '/original_img/');
            $path_thumb_img = storage_public(DATA_DIR . "/gallery_album/" . $album_id . '/thumb_img/');

            if (!file_exists($path_images)) {
                make_dir($path_images);
            }

            if (!file_exists($path_original_img)) {
                make_dir($path_original_img);
            }

            if (!file_exists($path_thumb_img)) {
                make_dir($path_thumb_img);
            }

            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
            if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {

                // 检查文件格式
                if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['invalid_file']);
                }
                $image_name = explode('.', $_FILES["file"]["name"]);
                $pic_name = $image_name['0']; //文件名称
                $pic_size = intval($_FILES['file']['size']); //图片大小
                $dir = "gallery_album/" . $album_id . "/original_img";
                $original_img = $this->image->upload_image($_FILES['file'], $dir); // 原始图片
                $original_img = storage_public($original_img);
                $goods_thumb = '';
                $images = $original_img;   // 商品图片
                if ($proc_thumb && $this->image->gd_version() > 0 && $this->image->check_img_function($_FILES['file']['type'])) {
                    //            if ($proc_thumb && !empty($original_img)) {
                    if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                        $goods_thumb = $this->image->make_thumb($original_img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                        if ($goods_thumb === false) {
                            return sys_msg($this->image->error_msg(), 1, array(), false);
                        }
                    } else {
                        $goods_thumb = $original_img;
                    }
                    // 如果设置大小不为0，缩放图片
                    if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                        $images = $this->image->make_thumb($original_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                    } else {
                        $images = $original_img;
                    }
                    if (intval($GLOBALS['_CFG']['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                        if ($this->image->add_watermark($images, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                            return sys_msg($this->image->error_msg(), 1, array(), false);
                        }
                    }
                }

                // 复制文件
                list($width, $height, $type, $attr) = getimagesize($original_img); //获取规格
                $pic_spec = $width . 'x' . $height; //图片规格
                $add_time = gmtime(); //上传时间

                $path_album = DATA_DIR . "/gallery_album/" . $album_id;

                $images = $this->goodsManageService->reformatImageName('gallery', $album_id, $images, 'source', $path_album, 'album');
                $original_img = $this->goodsManageService->reformatImageName('gallery', $album_id, $original_img, 'goods', $path_album, 'album');
                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $album_id, $goods_thumb, 'thumb', $path_album, 'album');

                $result['data'] = [
                    'original_img' => $original_img,
                    'goods_thumb' => $goods_thumb
                ];

                $result['pic'] = get_image_path($original_img);

                $ru_id = get_goods_gallery_album(0, $album_id, array('ru_id'));

                //入库
                $pic_id = PicAlbum::insertGetId([
                    'ru_id' => $ru_id,
                    'album_id' => $album_id,
                    'pic_name' => $pic_name,
                    'pic_file' => $original_img,
                    'pic_size' => $pic_size,
                    'pic_spec' => $pic_spec,
                    'add_time' => $add_time,
                    'pic_thumb' => $goods_thumb,
                    'pic_image' => $images
                ]);

                if ($pic_id) {
                    $arr_img = [
                        $original_img,
                        $goods_thumb,
                        $images
                    ];

                    $this->dscRepository->getOssAddFile($arr_img);

                    /* 删除本地商品图片 start */
                    if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                        $album_images = [
                            storage_public($original_img),
                            storage_public($goods_thumb),
                            storage_public($images)
                        ];
                        dsc_unlink($album_images);
                    }
                    /* 删除本地商品图片 start */
                }
                $result['picid'] = $pic_id;
            } else {
                $result['error'] = '1';
                $result['massege'] = lang('suppliers/gallery_album.upload_error');
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 图片批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            admin_priv('suppliers_gallery_album');
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : array();
            $old_album_id = isset($_REQUEST['old_album_id']) ? intval($_REQUEST['old_album_id']) : 0;
            $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $type = isset($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';

            $checkboxes = $this->baseRepository->getExplode($checkboxes);

            if (!empty($checkboxes)) {
                if ($type == 'remove') {

                    /* 获取所以图片 */
                    $pic_info = PicAlbum::whereIn('pic_id', $checkboxes);

                    /* 存在图片  删除 */
                    if (!empty($pic_info)) {
                        foreach ($pic_info as $v) {
                            if ($v['pic_file'] != '' && @strpos($v['pic_file'], 'http://') === false && @strpos($v['pic_file'], 'https://') === false) {
                                dsc_unlink(storage_public($v['pic_file']));
                                $arr_img[] = $v['pic_file'];
                            }

                            /* 删除缩略图 */
                            if ($v['pic_thumb'] != '' && @strpos($v['pic_thumb'], 'http://') === false && @strpos($v['pic_thumb'], 'https://') === false) {
                                dsc_unlink(storage_public($v['pic_thumb']));
                                $arr_img[] = $v['pic_thumb'];
                            }

                            /* 删除缩略图 */
                            if ($v['pic_image'] != '' && @strpos($v['pic_image'], 'http://') === false && @strpos($v['pic_image'], 'https://') === false) {
                                dsc_unlink(storage_public($v['pic_image']));
                                $arr_img[] = $v['pic_image'];
                            }

                            $this->dscRepository->getOssDelFile($arr_img);
                        }
                    }

                    /* 删除活动 */
                    PicAlbum::where('ru_id', $adminru['ru_id'])
                        ->whereIn('pic_id', $checkboxes)
                        ->delete();

                    $link[] = array('text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
                    return sys_msg($GLOBALS['_LANG']['delete_succeed'], 0, $link);
                } else {
                    /* 转移相册 */
                    if ($album_id > 0) {
                        PicAlbum::whereIn('pic_id', $checkboxes)
                            ->update([
                                'album_id' => $album_id
                            ]);

                        $link[] = array('text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
                        return sys_msg($GLOBALS['_LANG']['remove_succeed'], 0, $link);
                    } else {
                        $link[] = array('text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
                        return sys_msg($GLOBALS['_LANG']['album_fail'], 1, $link);
                    }
                }
            } else {
                $link[] = array('text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
                return sys_msg($GLOBALS['_LANG']['handle_fail'], 1, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 转移相册弹窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'move_pic') {
            $check_auth = check_authz_json('suppliers_gallery_album');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $inherit = !empty($_REQUEST['inherit']) ? intval($_REQUEST['inherit']) : 0;
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
            $this->smarty->assign('form_act', 'submit_pic');
            $this->smarty->assign('action_type', 'move_pic');
            $this->smarty->assign('album_id', $album_id);
            $this->smarty->assign('inherit', $inherit);
            $html = $this->smarty->fetch("category_move.dwt");

            clear_cache_files();
            return make_json_result($html);
        }

        /*------------------------------------------------------ */
        //-- 转移相册操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'submit_pic') {
            admin_priv('suppliers_gallery_album');
            $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;//操作相册
            $inherit = !empty($_REQUEST['inherit']) ? intval($_REQUEST['inherit']) : 0;//子相册是否继承
            $target_album_id = !empty($_REQUEST['target_album_id']) ? intval($_REQUEST['target_album_id']) : 0;//目标相册
            $cat_select = $album_id;

            if ($inherit == 1) {
                $cat_select = $this->galleryAlbumManageService->getGalleryChild($album_id, 1);
            }

            $cat_select = $this->baseRepository->getExplode($cat_select);
            PicAlbum::whereIn('album_id', $cat_select)
                ->update([
                    'album_id' => $target_album_id
                ]);

            $parent_album_id = GalleryAlbum::where('album_id', $album_id)
                ->where('suppliers_id', $adminru['suppliers_id'])
                ->value('parent_album_id');
            $parent_album_id = $parent_album_id ? $parent_album_id : 0;

            $link[] = array('text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=list&parent_id=' . $parent_album_id);
            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }
    }
}
