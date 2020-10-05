<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\FileUpload;
use App\Libraries\Image;
use App\Models\GalleryAlbum;
use App\Models\Goods;
use App\Models\PicAlbum;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Gallery\GalleryAlbumManageService;
use App\Services\Goods\GoodsManageService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 图片库管理
 */
class GalleryAlbumController extends InitController
{
    protected $goodsManageService;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $galleryAlbumManageService;

    public function __construct(
        GoodsManageService $goodsManageService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GalleryAlbumManageService $galleryAlbumManageService
    )
    {
        $this->goodsManageService = $goodsManageService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->galleryAlbumManageService = $galleryAlbumManageService;
    }

    public function index()
    {

        /*交换对象*/
        $adminru = get_admin_ru_id();

        $this->smarty->assign("priv_ru", 1);

        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|JPEG|';

        /* -------------------------------------------------------- */
        // 图片库列表
        /* -------------------------------------------------------- */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gallery_album']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_album'], 'href' => 'gallery_album.php?act=add']);

            $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);

            if ($parent_id > 0) {
                $parent_album_id = GalleryAlbum::where('album_id', $parent_id)->value('parent_album_id');

                $this->smarty->assign('action_link1', ['text' => $GLOBALS['_LANG']['return_to_superior'], 'href' => 'gallery_album.php?act=list&parent_id=' . $parent_album_id]);
            }

            $offline_store = $this->galleryAlbumManageService->getGalleryAlbumList($adminru['ru_id']);

            $this->smarty->assign('gallery_album', $offline_store['pzd_list']);
            $this->smarty->assign('filter', $offline_store['filter']);
            $this->smarty->assign('record_count', $offline_store['record_count']);
            $this->smarty->assign('page_count', $offline_store['page_count']);
            $this->smarty->assign('full_page', 1);
            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            return $this->smarty->display("gallery_album.dwt");
        }

        /* -------------------------------------------------------- */
        // 图片库分页
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'query') {
            $offline_store = $this->galleryAlbumManageService->getGalleryAlbumList($adminru['ru_id']);

            $this->smarty->assign('gallery_album', $offline_store['pzd_list']);
            $this->smarty->assign('filter', $offline_store['filter']);
            $this->smarty->assign('record_count', $offline_store['record_count']);
            $this->smarty->assign('page_count', $offline_store['page_count']);

            //跳转页面
            return make_json_result($this->smarty->fetch('gallery_album.dwt'), '', ['filter' => $offline_store['filter'], 'page_count' => $offline_store['page_count']]);
        }

        /* -------------------------------------------------------- */
        // 图片库添加、编辑
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            if ($_REQUEST['act'] == 'add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_album']);
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_album']);
            }

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['gallery_album'], 'href' => 'gallery_album.php?act=list']);
            $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
            $album_info = [
                'ru_id' => 0,
                'suppliers_id' => 0
            ];
            if ($album_id > 0) {
                $album_info = get_goods_gallery_album(2, $album_id, ['suppliers_id', 'ru_id']);
            }
            if ($_REQUEST['act'] == 'add') {
                $cat_select = gallery_cat_list(0, 0, false, 0, true, $album_info['ru_id'], $album_info['suppliers_id']);

                /* 简单处理缩进 */
                foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                        $level = str_repeat('&nbsp;', $v['level'] * 4);
                        $cat_select[$k]['name'] = $level . $v['name'];
                    }
                }
                $album_info['parent_album_id'] = $parent_id;
                $album_info['ru_id'] = $adminru['ru_id'];
                $this->smarty->assign('cat_select', $cat_select);
            } else {
                $cat_info = isset($cat_info) ? $cat_info : ['parent_id' => 0];
                if (is_array($cat_info)) {
                    $cat_select = gallery_cat_list(0, $cat_info['parent_id'], false, 0, true, $album_info['ru_id'], $album_info['suppliers_id']);
                } else {
                    $cat_select = '';
                }

                $cat_child = get_cat_child($album_id);
                /* 简单处理缩进 */
                if (is_array($cat_select)) {
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

            $this->smarty->assign("album_info", $album_info);
            $form_action = ($_REQUEST['act'] == 'add') ? "insert" : "update";
            $this->smarty->assign("form_action", $form_action);

            return $this->smarty->display("gallery_album_info.dwt");
        }

        /* -------------------------------------------------------- */
        // 图片库插入、更新
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $album_mame = isset($_REQUEST['album_mame']) ? addslashes($_REQUEST['album_mame']) : '';
            $album_desc = isset($_REQUEST['album_desc']) ? addslashes($_REQUEST['album_desc']) : '';
            $sort_order = isset($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            $parent_id = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
            $seller_id = isset($_REQUEST['seller_id']) ? intval($_REQUEST['seller_id']) : 0;
            if ($_REQUEST['act'] == 'insert') {
                if (empty($adminru['ru_id'])) {
                    $adminru['ru_id'] = 0;
                }
                /*检查是否重复*/
                $is_only = GalleryAlbum::where('album_mame', $album_mame)->where('ru_id', $adminru['ru_id'])->count();
                if ($is_only > 0) {
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
                    $res = $this->galleryAlbumManageService->uploadArticleFile($_FILES['album_cover']);

                    if ($res != false) {
                        $file_url = $res;
                    }
                }

                if ($file_url == '') {
                    $file_url = $_POST['file_url'];
                }

                $this->dscRepository->getOssAddFile([$file_url]);

                $time = gmtime();
                $file_url = $file_url ?? '';
                $data = [
                    'parent_album_id' => $parent_id,
                    'album_mame' => $album_mame,
                    'album_cover' => $file_url,
                    'album_desc' => $album_desc,
                    'sort_order' => $sort_order,
                    'add_time' => $time,
                ];
                $res = GalleryAlbum::insert($data);
                if ($res > 0) {
                    $link[0]['text'] = $GLOBALS['_LANG']['continue_add_album'];
                    $link[0]['href'] = 'gallery_album.php?act=add';

                    $link[1]['text'] = $GLOBALS['_LANG']['bank_list'];
                    $link[1]['href'] = 'gallery_album.php?act=list';

                    return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
                }
            } else {
                $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
                $album_info = get_goods_gallery_album(2, $album_id, ['suppliers_id', 'ru_id']);

                /* 检查是否重复 */
                $res = GalleryAlbum::whereRaw(1);
                if ($album_info['suppliers_id'] > 0) {
                    $res = $res->where('suppliers_id', $album_info['suppliers_id']);
                } else {
                    $res = $res->where('ru_id', $seller_id);
                }

                $res = $res->where('album_mame', $album_mame)->where('album_id', '<>', $album_id);
                $res = $res->count();
                if ($res > 0) {
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
                    $res = $this->galleryAlbumManageService->uploadArticleFile($_FILES['album_cover']);

                    if ($res != false) {
                        $file_url = $res;
                    }
                }
                if ($file_url == '') {
                    $file_url = $_POST['file_url'];
                }

                $this->dscRepository->getOssAddFile([$file_url]);

                /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
                $old_url = get_goods_gallery_album(0, $album_id, ['album_cover']);
                if ($old_url != '' && $old_url != $file_url && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                    @unlink(storage_public($old_url));
                    $del_arr_img[] = $old_url;

                    $this->dscRepository->getOssDelFile($del_arr_img);
                }

                $file_url = $file_url ?? '';
                $data = [
                    'parent_album_id' => $parent_id,
                    'album_mame' => $album_mame,
                    'album_cover' => $file_url,
                    'album_desc' => $album_desc,
                    'sort_order' => $sort_order
                ];
                $res = GalleryAlbum::where('album_id', $album_id)->update($data);

                if ($res > 0) {
                    $link[0]['text'] = $GLOBALS['_LANG']['bank_list'];
                    $link[0]['href'] = 'gallery_album.php?act=list';

                    return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
                }
            }
        }

        /* -------------------------------------------------------- */
        // 查看图片
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'view') {
            $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $album_mame = GalleryAlbum::where('album_id', $album_id)->value('album_mame');
            $this->smarty->assign('ur_here', sprintf($GLOBALS['_LANG']['view_pic'], stripslashes($album_mame)));
            $this->smarty->assign('action_link', ['text' => lang('admin/gallery_album.uplode_img'), 'spec' => "ectype='addpic_album'", 'href' => "gallery_album.php?act=view&id=$album_id"]);
            $this->smarty->assign('album_id', $album_id);

            $album_info = get_goods_gallery_album(2, $album_id, ['suppliers_id', 'ru_id']);
            $cat_select = gallery_cat_list(0, 0, false, 0, true, $album_info['ru_id'], $album_info['suppliers_id']);

            $res = GalleryAlbum::where('album_id', $album_id);
            $album_info = $this->baseRepository->getToArrayFirst($res);

            $parent_album_id = $album_info ? $album_info['parent_album_id'] : 0;
            $ru_id = $album_info ? $album_info['ru_id'] : 0;
            $this->smarty->assign('album_ru', $ru_id);

            $this->smarty->assign('parent_album_id', $parent_album_id);

            /* 简单处理缩进 */
            foreach ($cat_select as $k => $v) {
                if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
                }
            }
            $this->smarty->assign('cat_select', $cat_select);

            $picAlbumList = $this->galleryAlbumManageService->getPicAlbumList($album_id);

            $gallery_list = gallery_child_cat_list($album_id, $adminru['ru_id']);
            if ($parent_album_id) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['upload_image'], 'spec' => "ectype='addpic_album'", 'href' => "gallery_album.php?act=view&id=$parent_album_id"]);
            } else {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['upload_image'], 'spec' => "ectype='addpic_album'", 'href' => "gallery_album.php?act=list"]);
            }

            $gallery_num = gallery_child_cat_num($album_id);
            $this->smarty->assign('gallery_list', $gallery_list);
            $this->smarty->assign('gallery_num', $gallery_num);
            $this->smarty->assign('pic_album', $picAlbumList['pic_list']);
            $this->smarty->assign('filter', $picAlbumList['filter']);
            $this->smarty->assign('record_count', $picAlbumList['record_count']);
            $this->smarty->assign('page_count', $picAlbumList['page_count']);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display("pic_album.dwt");
        }

        /* -------------------------------------------------------- */
        // 图片列表翻页、查询
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'pic_query') {
            $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $picAlbumList = $this->galleryAlbumManageService->getPicAlbumList($album_id);
            $gallery_list = gallery_child_cat_list($album_id, $adminru['ru_id']);
            $this->smarty->assign('pic_album', $picAlbumList['pic_list']);
            $this->smarty->assign('gallery_list', $gallery_list);
            $this->smarty->assign('filter', $picAlbumList['filter']);
            $this->smarty->assign('record_count', $picAlbumList['record_count']);
            $this->smarty->assign('page_count', $picAlbumList['page_count']);

            //跳转页面
            return make_json_result($this->smarty->fetch('pic_album.dwt'), '', ['filter' => $picAlbumList['filter'], 'page_count' => $picAlbumList['page_count']]);
        }

        /* -------------------------------------------------------- */
        // 图片库删除
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'remove') {
            load_helper('visual');
            $album_id = intval($_GET['id']);

            //获取下级相册数量
            $album_count = GalleryAlbum::where('parent_album_id', $album_id)->count();
            //存在下级相册 不让删除
            if ($album_count > 0) {
                return make_json_error($GLOBALS['_LANG']['not_unremove_succeed']);
            } else {
                $res = GalleryAlbum::where('album_id', $album_id)->value('parent_album_id');
                /* 删除原来的文件 */
                $old_url = get_goods_gallery_album(0, $album_id, ['album_cover']);
                if ($old_url != '' && @strpos($old_url, 'http://') === false && @strpos($old_url, 'https://') === false) {
                    @unlink(storage_public($old_url));
                    $del_arr_img[] = $old_url;

                    $this->dscRepository->getOssDelFile($del_arr_img);
                }
                //删除该相册目录下的所以图片
                $dir = storage_public('data/gallery_album/' . $album_id);//模板目录
                $rmdir = getDelDirAndFile($dir);
                //删除图片数据库
                PicAlbum::where('album_id', $album_id)->delete();
                GalleryAlbum::where('album_id', $album_id)->delete();

                if ($res > 0) {
                    $url = 'gallery_album.php?act=gallery_query&id=' . $res;
                } else {
                    $url = 'gallery_album.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                }

                return dsc_header("Location: $url\n");
            }
        }

        /* -------------------------------------------------------- */
        // 图片库图片
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'gallery_query') {
            $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $this->smarty->assign('album_id', $album_id);

            $album_list = get_goods_gallery_album(1, 0, ['album_id', 'album_mame']);
            $this->smarty->assign('album_list', $album_list);

            $offline_store = $this->galleryAlbumManageService->getPicAlbumList($album_id);

            $gallery_list = gallery_child_cat_list($album_id, $adminru['ru_id']);
            $gallery_num = gallery_child_cat_num($album_id, $adminru['ru_id']);

            $this->smarty->assign('gallery_list', $gallery_list);
            $this->smarty->assign('gallery_num', $gallery_num);
            $this->smarty->assign('pic_album', $offline_store['pic_list']);
            $this->smarty->assign('filter', $offline_store['filter']);
            $this->smarty->assign('record_count', $offline_store['record_count']);
            $this->smarty->assign('page_count', $offline_store['page_count']);

            //跳转页面
            return make_json_result($this->smarty->fetch('pic_album.dwt'), '', ['filter' => $offline_store['filter'], 'page_count' => $offline_store['page_count']]);
        }

        /* -------------------------------------------------------- */
        // 删除图片
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'pic_remove') {
            $result = ['error' => '', 'content' => '', 'url' => ''];
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            /* 删除原来的文件 */

            $pic_info = gallery_pic_album(2, $id, ['pic_file', 'pic_thumb', 'pic_image', 'album_id']);

            $arr_img = [];
            /*删除原图*/
            if ($pic_info['pic_file'] != '' && @strpos($pic_info['pic_file'], 'http://') === false && @strpos($pic_info['pic_file'], 'https://') === false) {
                $arr_img[] = $pic_info['pic_file'];
            }

            /*删除缩略图*/
            if ($pic_info['pic_thumb'] != '' && @strpos($pic_info['pic_thumb'], 'http://') === false && @strpos($pic_info['pic_thumb'], 'https://') === false) {
                $arr_img[] = $pic_info['pic_thumb'];
            }

            /*删除图*/
            if ($pic_info['pic_image'] != '' && @strpos($pic_info['pic_image'], 'http://') === false && @strpos($pic_info['pic_image'], 'https://') === false) {
                $arr_img[] = $pic_info['pic_image'];
            }

            /* 删除OSS图片 */
            $this->dscRepository->getOssDelFile($arr_img);

            /* 删除本地图片 */
            if ($arr_img) {
                foreach ($arr_img as $key => $val) {
                    $arr_img[$key] = storage_public($val);
                }

                dsc_unlink($arr_img);
            }
            $res = PicAlbum::where('pic_id', $id)->delete();
            if ($res > 0) {
                $result['error'] = 0;
                $result['id'] = $id;
            } else {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['system_error_notic'];
            }
            return response()->json($result);
        }

        /* -------------------------------------------------------- */
        // 批量删除
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'remove_batch') {
            load_helper('visual');
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : [];
            if (!empty($checkboxes)) {
                //筛选不是末级相册
                $unremove_arr = []; //不能删除的数组
                foreach ($checkboxes as $k => $v) {
                    //获取下级相册数量
                    $album_count = GalleryAlbum::where('parent_album_id', $v)->count();
                    //不是末级相册  从数组中剔除  加入不能删除数组
                    if ($album_count > 0) {
                        unset($checkboxes[$k]);
                        $unremove_arr[] = $v;
                    } else {
                        //删除该相册目录下的所以图片
                        $dir = storage_public('data/gallery_album/' . $v); //模板目录
                        $rmdir = getDelDirAndFile($dir);
                        //删除图片数据库
                        PicAlbum::where('album_id', $v)->delete();
                    }
                }
                /* 处理上传图片 */
                $res = GalleryAlbum::whereIn('album_id', $checkboxes);
                $album_cover = $this->baseRepository->getToArrayGet($res);

                /* 存在  删除图片 */
                if (!empty($album_cover)) {
                    foreach ($album_cover as $k => $v) {
                        if ($v['album_cover'] != '' && @strpos($v['album_cover'], 'http://') === false && @strpos($v['album_cover'], 'https://') === false) {
                            @unlink(storage_public($v['album_cover']));
                            $del_arr_img[] = $v['album_cover'];

                            $this->dscRepository->getOssDelFile($del_arr_img);
                        }
                    }
                }
                /* 删除活动 */
                $res = GalleryAlbum::whereIn('album_id', $checkboxes)->delete();
                if ($res > 0) {
                    $back_msg = $GLOBALS['_LANG']['delete_succeed'];
                    $link[0] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'gallery_album.php?act=list&' . list_link_postfix()];
                    if (!empty($unremove_arr)) {
                        $unremove_arr = $this->baseRepository->getExplode($unremove_arr);
                        $res = GalleryAlbum::whereIn('album_id', $unremove_arr);
                        $album_mame_arr = $this->baseRepository->getToArrayGet($res);
                        if (!empty($album_mame_arr)) {
                            $album_mame_arr = arr_foreach($album_mame_arr);
                            $album_mame_arr = implode(',', $album_mame_arr);
                            $back_msg = sprintf($GLOBALS['_LANG']['unremove_succeed'], stripslashes($album_mame_arr));
                        }
                    }
                    return sys_msg($back_msg, 0, $link);
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'gallery_album.php?act=list&' . list_link_postfix()];
                    return sys_msg(lang('admin/gallery_album.delete_fail'), 0, $link);
                }
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'gallery_album.php?act=list&' . list_link_postfix()];
                return sys_msg($GLOBALS['_LANG']['delete_fail'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));
            $data = ['sort_order' => $order];
            $res = GalleryAlbum::where('album_id', $id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result(stripslashes($order));
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 上传图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_pic') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            load_helper('goods', 'admin');

            $result = ['error' => 0, 'pic' => '', 'name' => ''];

            $album_id = isset($_REQUEST['album_id']) && !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;

            $bucket_info = $this->dscRepository->getBucketInfo();

            $extname = '';
            if (isset($_FILES['file']['name']) && $_FILES['file']['name']) {
                $extname = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
            }

            if ($extname == 'mp4') {
                $video_path = storage_public(DATA_DIR . '/uploads/pic_album/' . $album_id . "/");

                if (!file_exists($video_path)) {
                    make_dir($video_path);
                }

                $videoOther = [
                    'isRandName' => true,
                    'allowType' => ['mp4'],
                    'FilePath' => $video_path,
                    'MAXSIZE' => 20000000
                ];

                $upload = new FileUpload($videoOther);

                $pic_id = 0;
                if ($upload->uploadFile('file')) {
                    $upload->getFileCache();

                    $video = DATA_DIR . "/uploads/pic_album/" . $album_id . "/" . $upload->getNewFileName();

                    $ru_id = get_goods_gallery_album(0, $album_id, ['ru_id']);

                    $image_name = explode('.', $_FILES["file"]["name"]);
                    $pic_name = $image_name['0']; //文件名称
                    $pic_size = intval($_FILES['file']['size']); //视频大小
                    $add_time = gmtime(); //上传时间

                    //入库
                    $other = [
                        'ru_id' => $ru_id,
                        'album_id' => $album_id,
                        'pic_name' => $pic_name,
                        'pic_file' => $video,
                        'pic_size' => $pic_size,
                        'add_time' => $add_time
                    ];
                    $pic_id = PicAlbum::insertGetId($other);

                    $arr_img = [$video];
                    $this->dscRepository->getOssAddFile($arr_img);

                    $result['picid'] = $pic_id;
                } else {
                    $result['error'] = 1;
                    $result['massege'] = $upload->getErrorMsg();
                }

                if ($pic_id) {

                    /* 删除本地视频 start */
                    if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                        dsc_unlink($video);
                    }
                    /* 删除本地视频 start */
                }
            } else {
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

                /* 取得文件地址 */
                $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
                if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {

                    // 检查文件格式
                    if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['invalid_file']);
                    }
                    $image_name = explode('.', $_FILES["file"]["name"]);
                    $pic_name = $image_name['0']; //文件名称
                    $pic_size = intval($_FILES['file']['size']); //图片大小
                    $dir = DATA_DIR . "/gallery_album/" . $album_id . "/original_img";
                    $original_img = $image->upload_image($_FILES['file'], $dir); // 原始图片
                    $original_img = storage_public($original_img);
                    $images = $original_img;   // 商品图片
                    getimagesize($original_img);
                    if ($proc_thumb && $image->gd_version() > 0 && $image->check_img_function($_FILES['file']['type'])) {
                        // 如果设置缩略图大小不为0，生成缩略图
                        if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                            $goods_thumb = $image->make_thumb($original_img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            if ($goods_thumb === false) {
                                sys_msg($image->error_msg(), 1, [], false);
                            }
                        } else {
                            $goods_thumb = $original_img;
                        }
                        // 如果设置大小不为0，缩放图片
                        if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                            $images = $image->make_thumb($original_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                        } else {
                            $images = $original_img;
                        }
                        if (intval($GLOBALS['_CFG']['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                            if ($image->add_watermark($images, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                sys_msg($image->error_msg(), 1, [], false);
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

                    $result['pic'] = $this->dscRepository->getImagePath($original_img);

                    $ru_id = get_goods_gallery_album(0, $album_id, ['ru_id']);

                    //入库
                    $other = [
                        'ru_id' => $ru_id,
                        'album_id' => $album_id,
                        'pic_name' => $pic_name,
                        'pic_file' => $original_img,
                        'pic_size' => $pic_size,
                        'pic_spec' => $pic_spec,
                        'add_time' => $add_time,
                        'pic_thumb' => $goods_thumb,
                        'pic_image' => $images
                    ];

                    $pic_id = PicAlbum::insertGetId($other);

                    if ($pic_id) {
                        $arr_img = [
                            $original_img,
                            $goods_thumb,
                            $images
                        ];

                        $this->dscRepository->getOssAddFile($arr_img);
                    }
                    $result['picid'] = $pic_id;
                } else {
                    $result['error'] = 1;
                    $result['massege'] = $GLOBALS['_LANG']['upload_error_notic'];
                }
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 上传视频
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_video') {
            $result = ['error' => 0, 'goods_id' => 0, 'massege' => '', 'goods_video' => '', 'goods_video_path' => ''];

            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $result['goods_id'] = $goods_id;

            $bucket_info = $this->dscRepository->getBucketInfo();

            if ($_FILES['file']['name']) {
                $extname = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
            }

            $video_path = storage_public(DATA_DIR . '/uploads/goods/' . $goods_id . "/");

            if (!file_exists($video_path)) {
                make_dir($video_path);
            }

            if (isset($extname) && $extname == 'mp4') {
                $videoOther = [
                    'isRandName' => true,
                    'allowType' => ['mp4'],
                    'FilePath' => $video_path,
                    'MAXSIZE' => 20000000
                ];

                $upload = new FileUpload($videoOther);

                if ($upload->uploadFile('file')) {
                    $upload->getFileCache();

                    $goods_video = DATA_DIR . "/uploads/goods/" . $goods_id . "/" . $upload->getNewFileName();

                    $goodsOther = [$goods_video];
                    $this->dscRepository->getOssAddFile($goodsOther);

                    if ($goods_id) {
                        $video_old = Goods::where('goods_id', $goods_id)->value('goods_video');

                        dsc_unlink($video_old);

                        $arr[] = $video_old;
                        $this->dscRepository->getOssDelFile($arr);

                        $data = ['goods_video' => $goods_video];
                        Goods::where('goods_id', $goods_id)->update($data);
                    } else {
                        $admin_id = get_admin_id();
                        $video[] = $goods_video;
                        session()->put('goods_video.' . $admin_id, $video);
                    }

                    $result['goods_video'] = $goods_video;
                    $result['goods_video_path'] = !empty($goods_video) ? $this->dscRepository->getImagePath($goods_video) : '';
                } else {
                    $result['error'] = 1;
                    $result['massege'] = $upload->getErrorMsg();
                }

                if ($goods_id) {
                    sleep(3);

                    /* 删除本地视频 start */
                    if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                        dsc_unlink($goods_video);
                    }
                    /* 删除本地视频 start */
                }
            } else {
                $result['error'] = 2;
                $result['massege'] = $GLOBALS['_LANG']['upload_format_error'];
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除视频
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_video') {
            $result = ['error' => 0, 'goods_id' => 0, 'massege' => '', 'goods_video' => ''];

            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            if ($goods_id) {
                $video_old = Goods::where('goods_id', $goods_id)->value('goods_video');

                dsc_unlink(storage_public($video_old));

                $arr[] = $video_old;
                $this->dscRepository->getOssDelFile($arr);

                $data = ['goods_video' => ''];
                Goods::where('goods_id', $goods_id)->update($data);
            } else {
                get_del_goods_video();
            }

            return response()->json($result);
        }

        /* -------------------------------------------------------- */
        // 图片批量操作
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'batch') {
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : [];
            $gallery_checkboxes = !empty($_REQUEST['gallery_checkboxes']) ? $_REQUEST['gallery_checkboxes'] : [];
            $pic_checkboxes = !empty($_REQUEST['pic_checkboxes']) ? $_REQUEST['pic_checkboxes'] : [];
            $old_album_id = isset($_REQUEST['old_album_id']) ? intval($_REQUEST['old_album_id']) : 0;
            $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $type = isset($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';
            if (!empty($checkboxes)) {
                if ($type == 'remove') {
                    /* 获取所以图片 */
                    $res = PicAlbum::whereIn('pic_id', $pic_checkboxes);
                    $pic_info = $this->baseRepository->getToArrayGet($res);
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
                    /* 删除图片 */
                    if ($gallery_checkboxes) {
                        $res = GalleryAlbum::whereIn('album_id', $checkboxes)->delete();
                    }
                    if ($pic_checkboxes) {
                        $res = PicAlbum::whereIn('pic_id', $checkboxes)->delete();
                    }

                    if ($res) {
                        $link[] = ['text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id];
                        return sys_msg($GLOBALS['_LANG']['delete_succeed'], 0, $link);
                    }
                } else {
                    /* 转移相册 */
                    if ($album_id > 0) {
                        if ($pic_checkboxes) {
                            $data = ['album_id' => $album_id];
                            $res = PicAlbum::whereIn('pic_id', $checkboxes)->update($data);
                        }
                        if ($gallery_checkboxes) {
                            $data = ['parent_album_id' => $album_id];
                            $res = GalleryAlbum::whereIn('album_id', $checkboxes)->update($data);
                        }
                        if ($res) {
                            $link[] = ['text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id];
                            return sys_msg($GLOBALS['_LANG']['remove_succeed'], 0, $link);
                        }
                    } else {
                        $link[] = ['text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id];
                        return sys_msg($GLOBALS['_LANG']['album_fail'], 1, $link);
                    }
                }
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id];
                return sys_msg($GLOBALS['_LANG']['handle_fail'], 1, $link);
            }
        }

        /* -------------------------------------------------------- */
        // 转移相册弹框
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'move_pic') {
            $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $inherit = !empty($_REQUEST['inherit']) ? intval($_REQUEST['inherit']) : 0;
            $res = GalleryAlbum::where('album_id', $album_id);
            $album_info = $this->baseRepository->getToArrayFirst($res);
            $cat_select = gallery_cat_list(0, 0, false, 0, true, $album_info['ru_id'], $album_info['suppliers_id']);

            /* 简单处理缩进 */
            foreach ($cat_select as $k => $v) {
                if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
                }
            }
            $this->smarty->assign('cat_select', $cat_select);

            $this->smarty->assign('form_act', 'submit_pic');
            $this->smarty->assign('action_type', 'move_pic');
            $this->smarty->assign('album_id', $album_id);
            $this->smarty->assign('inherit', $inherit);
            $html = $this->smarty->fetch("library/move_category.lbi");

            clear_cache_files();
            return make_json_result($html);
        }

        /* -------------------------------------------------------- */
        // 转移相册操作
        /* -------------------------------------------------------- */
        elseif ($_REQUEST['act'] == 'submit_pic') {
            $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;//操作相册
            $inherit = !empty($_REQUEST['inherit']) ? intval($_REQUEST['inherit']) : 0;//子相册是否继承
            $target_album_id = !empty($_REQUEST['target_album_id']) ? intval($_REQUEST['target_album_id']) : 0;//目标相册
            $cat_select = $album_id;
            if ($inherit == 1) {
                $cat_select = $this->galleryAlbumManageService->getGalleryChild($album_id, 1);
            }

            $data = ['album_id' => $target_album_id];
            $cat_select = $this->baseRepository->getExplode($cat_select);
            PicAlbum::whereIn('album_id', $cat_select)->update($data);

            $parent_album_id = GalleryAlbum::where('album_id', $album_id)->value('parent_album_id');
            $link[] = ['text' => $GLOBALS['_LANG']['bank_list'], 'href' => 'gallery_album.php?act=list&parent_id=' . $parent_album_id];
            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }
    }
}
