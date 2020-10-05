<?php

namespace App\Modules\Admin\Controllers;

use App\Models\SellerShopinfo;
use App\Models\SellerShopslide;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\SellerShopSlide\SellerShopSlideManageService;

/**
 * 管理中心入驻商家店铺幻灯片管理
 */
class SellerShopSlideController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;
    protected $sellerShopSlideManageService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        SellerShopSlideManageService $sellerShopSlideManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->sellerShopSlideManageService = $sellerShopSlideManageService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        $res = SellerShopinfo::where('ru_id', $adminru['ru_id']);
        $shop_info = $this->baseRepository->getToArrayFirst($res);

        /*------------------------------------------------------ */
        //-- 幻灯片列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_store_other');//by kong
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['store_slide_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_store_slide_list'], 'href' => 'seller_shop_slide.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $slide_list = $this->sellerShopSlideManageService->getSellerSlide($shop_info['seller_theme']);

            $this->smarty->assign('seller_slide_list', $slide_list);


            return $this->smarty->display('seller_shop_slide.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加幻灯片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_store_slide_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['store_slide_list'], 'href' => 'seller_shop_slide.php?act=list']);
            $this->smarty->assign('form_action', 'insert');


            return $this->smarty->display('seller_slide_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

            $slide_type = !empty($_POST['slide_type']) ? stripslashes($_POST['slide_type']) : 'left';

            $img_link = !empty($_POST['img_link']) ? stripslashes($_POST['img_link']) : '#';

            $img_order = isset($_POST['img_order']) ? intval($_POST['img_order']) : 0;

            $img_desc = isset($_REQUEST['img_desc']) ? stripslashes($_REQUEST['img_desc']) : '';

            /*处理图片*/
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';

            if ($_FILES['img_url']) {
                $file = $_FILES['img_url'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {

                        $file_name = explode('.', $file['name']);
                        $ext = array_pop($file_name);
                        $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_slide_img/seller_' . $adminru['ru_id']);

                        if (!is_dir($file_dir)) {
                            mkdir($file_dir, 0777, true);
                        }
                        $file_name = $file_dir . "/slide_" . gmtime() . '.' . $ext;
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $img_url = $file_name;

                            $oss_img_url = str_replace("../", "", $img_url);
                            $this->dscRepository->getOssAddFile([$oss_img_url]);
                        } else {
                            return sys_msg($GLOBALS['_LANG']['img_upload_fail']);
                        }
                    }
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['img_upload_notic']);
            }

            /*插入数据*/
            if (!empty($img_url)) {
                $img_url = str_replace(storage_public(''), '', $img_url);
            }

            $data = [
                'img_url' => $img_url,
                'img_link' => $img_link,
                'img_desc' => $img_desc,
                'is_show' => $is_show,
                'img_order' => $img_order,
                'slide_type' => $slide_type,
                'ru_id' => $adminru['ru_id'],
                'seller_theme' => $shop_info['seller_theme']
            ];
            SellerShopslide::insert($data);

            admin_log('添加幻灯片', 'add', 'seller_nav');

            /* 清除缓存 */
            clear_cache_files();

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'seller_shop_slide.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'seller_shop_slide.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add_slide_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑幻灯片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            //admin_priv('brand_manage');

            $id = request()->input('id', 0);

            $res = SellerShopslide::where('id', $id)->where('ru_id', $adminru['ru_id']);
            $seller_slide = $this->baseRepository->getToArrayFirst($res);
            if (!empty($seller_slide)) {
                $seller_slide['img_url'] = $this->dscRepository->getImagePath($seller_slide['img_url']);
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_store_slide']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['store_slide_list'], 'href' => 'seller_shop_slide.php?act=list']);
            $this->smarty->assign('slide', $seller_slide);
            $this->smarty->assign('form_action', 'updata');


            return $this->smarty->display('seller_slide_info.dwt');
        } elseif ($_REQUEST['act'] == 'updata') {
            //admin_priv('brand_manage');
            $id = request()->input('id', 0);
            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

            $slide_type = !empty($_POST['slide_type']) ? stripslashes($_POST['slide_type']) : 'left';

            $img_link = !empty($_POST['img_link']) ? stripslashes($_POST['img_link']) : '#';

            $old_img = !empty($_POST['old_img']) ? stripslashes($_POST['old_img']) : '';

            $img_order = isset($_POST['img_order']) ? intval($_POST['img_order']) : 0;

            $img_desc = isset($_REQUEST['img_desc']) ? stripslashes($_REQUEST['img_desc']) : '';

            /*处理图片*/
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';

            if ($_FILES['img_url']) {
                $file = $_FILES['img_url'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $file_name = explode('.', $file['name']);
                        $ext = array_pop($file_name);

                        $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_slide_img/seller_' . $adminru['ru_id']);
                        if (!is_dir($file_dir)) {
                            mkdir($file_dir, 0777, true);
                        }
                        $file_name = $file_dir . "/slide_" . gmtime() . '.' . $ext;
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $img_url = $file_name;

                            $oss_img_url = str_replace("../", "", $img_url);
                            $this->dscRepository->getOssAddFile([$oss_img_url]);
                        } else {
                            return sys_msg($GLOBALS['_LANG']['img_upload_fail']);
                        }
                    }
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['img_upload_notic']);
            }

            /* 删除该幻灯片的图片*/
            $oss_img_url = SellerShopslide::where('id', $id)->value('img_url');
            $oss_img_url = $oss_img_url ? $oss_img_url : '';

            if (!empty($oss_img_url)) {
                $oss_img_arr = explode("/", $oss_img_url);
                if ($oss_img_arr[1] != 'seller_themes') {
                    @unlink($oss_img_url);
                }

                $oss_img_url = str_replace("../", "", $oss_img_url);
                $this->dscRepository->getOssDelFile([$oss_img_url]);
            }

            $data = [
                'img_link' => $img_link,
                'img_desc' => $img_desc,
                'is_show' => $is_show,
                'img_order' => $img_order,
                'slide_type' => $slide_type
            ];

            if (!empty($img_url)) {
                //有图片上传
                $img_url = str_replace(storage_public(''), '', $img_url);
                $data['img_url'] = $img_url;
            }

            $res = SellerShopslide::where('id', $id)->update($data);
            if ($res > 0) {
                /* 清除缓存 */
                clear_cache_files();

                admin_log($GLOBALS['_LANG']['add_store_slide_list'], 'edit', 'seller_shop_slide');

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'seller_shop_slide.php?act=list';
                return sys_msg($GLOBALS['_LANG']['edit_store_slide_success'], 0, $link);
            } else {
                return 'error';
            }
        }
        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $id = intval($_POST['id']);
            $order = intval($_POST['val']);

            $name = SellerShopslide::where('id', $id)->value('img_url');
            $name = $name ? $name : '';

            $data = ['img_order' => $order];
            $res = SellerShopslide::where('id', $id)->update($data);
            if ($res > 0) {
                return make_json_result($order);
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['edit_fail'], $name));
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['is_show' => $val];
            $res = SellerShopslide::where('id', $id)->update($data);
            return make_json_result($val);
        }
        /*------------------------------------------------------ */
        //-- 删除幻灯片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            /* 删除该幻灯片的图片*/
            $img_url = SellerShopslide::where('id', $id)->value('img_url');
            $img_url = $img_url ? $img_url : '';

            if (!empty($img_url)) {
                $oss_img_arr = explode("/", $img_url);
                if ($oss_img_arr[1] != 'seller_themes') {
                    @unlink($img_url);
                }

                $oss_img_url = str_replace("../", "", $img_url);
                $this->dscRepository->getOssDelFile([$oss_img_url]);
            }

            SellerShopslide::where('id', $id)->delete();

            $url = 'seller_shop_slide.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $slide_list = $this->sellerShopSlideManageService->getSellerSlide($shop_info['seller_theme']);
            $this->smarty->assign('seller_slide_list', $slide_list);

            return make_json_result($this->smarty->fetch('seller_shop_slide.dwt'), '');
        }
    }
}
