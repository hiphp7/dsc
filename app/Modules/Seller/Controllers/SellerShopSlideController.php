<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Repositories\Common\DscRepository;

/**
 * 管理中心入驻商家店铺幻灯片管理
 */
class SellerShopSlideController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "index");
        $exc = new Exchange($this->dsc->table("seller_shopslide"), $this->db, 'id', 'img_url');

        $adminru = get_admin_ru_id();

        $sql = "select id,seller_theme,store_style from " . $this->dsc->table('seller_shopinfo') . " where ru_id = '" . $adminru['ru_id'] . "'";
        $shop_info = $this->db->getRow($sql);

        $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '02_merchants_ad']);
        /*------------------------------------------------------ */
        //-- 幻灯片列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            admin_priv('seller_store_other');//by kong
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['shop_ppt_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_shop_ppt'], 'href' => 'seller_shop_slide.php?act=add', 'class' => 'icon-plus']);
            $this->smarty->assign('full_page', 1);

            $slide_list = $this->get_seller_slide($shop_info['seller_theme']);

            $this->smarty->assign('seller_slide_list', $slide_list);


            $this->smarty->assign('current', 'seller_shop_slide');
            return $this->smarty->display('seller_shop_slide.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加幻灯片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_shop_ppt']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['shop_ppt_list'], 'href' => 'seller_shop_slide.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('form_action', 'insert');


            $this->smarty->assign('current', 'seller_shop_slide');
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
                        $ext = array_pop(explode('.', $file['name']));
                        $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_slide_img/seller_' . $adminru['ru_id']);
                        if (!is_dir($file_dir)) {
                            mkdir($file_dir);
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
                return sys_msg($GLOBALS['_LANG']['must_upload_img']);
            }

            /*插入数据*/

            $sql = "INSERT INTO " . $this->dsc->table('seller_shopslide') . "(img_url,img_link, img_desc, is_show, img_order, slide_type,ru_id, seller_theme) " .
                "VALUES ('$img_url', '$img_link', '$img_desc', '$is_show', '$img_order', '$slide_type','" . $adminru['ru_id'] . "','" . $shop_info['seller_theme'] . "')";
            $this->db->query($sql);

            admin_log($GLOBALS['_LANG']['add_slide'], 'add', 'seller_nav');

            /* 清除缓存 */
            clear_cache_files();

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'seller_shop_slide.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'seller_shop_slide.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add_ppt_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑幻灯片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            //admin_priv('brand_manage');

            $sql = "SELECT * FROM " . $this->dsc->table('seller_shopslide') . " WHERE id='$_REQUEST[id]' and ru_id='" . $adminru['ru_id'] . "'";
            $seller_slide = $this->db->GetRow($sql);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['shop_ppt_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['shop_ppt_list'], 'href' => 'seller_shop_slide.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('slide', $seller_slide);
            $this->smarty->assign('form_action', 'updata');


            $this->smarty->assign('current', 'seller_shop_slide');
            return $this->smarty->display('seller_slide_info.dwt');
        } elseif ($_REQUEST['act'] == 'updata') {
            //admin_priv('brand_manage');

            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

            $slide_type = !empty($_POST['slide_type']) ? stripslashes($_POST['slide_type']) : 'left';

            $img_link = !empty($_POST['img_link']) ? stripslashes($_POST['img_link']) : '#';

            $old_img = !empty($_POST['old_img']) ? stripslashes($_POST['old_img']) : '';

            $img_order = isset($_POST['img_order']) ? intval($_POST['img_order']) : 0;

            $img_desc = isset($_REQUEST['img_desc']) ? stripslashes($_REQUEST['img_desc']) : '';

            /*处理图片*/
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';
            if ($_FILES['img_url']['name']) {
                $file = $_FILES['img_url'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $ext = array_pop(explode('.', $file['name']));
                        $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_slide_img/seller_' . $adminru['ru_id']);
                        if (!is_dir($file_dir)) {
                            mkdir($file_dir);
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
                        /* 删除该幻灯片的图片*/
                        $sql = "SELECT img_url FROM " . $this->dsc->table('seller_shopslide') . " WHERE id = '" . $_POST['id'] . "' LIMIT 1";
                        $oss_img_url = $this->db->getOne($sql);
                    }
                }
            }


            if (!empty($oss_img_url)) {
                $oss_img_arr = explode("/", $oss_img_url);
                if ($oss_img_arr[1] != 'seller_themes') {
                    @unlink($oss_img_url);
                }

                $oss_img_url = str_replace("../", "", $oss_img_url);
                $this->dscRepository->getOssDelFile([$oss_img_url]);
            }

            $param = "img_link='$img_link',img_desc='$img_desc', is_show='$is_show',img_order='$img_order',slide_type='$slide_type' ";
            if (!empty($img_url)) {
                //有图片上传
                $param .= " ,img_url = '$img_url' ";
            }

            if ($exc->edit($param, $_POST['id'])) {
                /* 清除缓存 */
                clear_cache_files();

                admin_log($GLOBALS['_LANG']['add_shop_ppt'], 'edit', 'seller_shop_slide');

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'seller_shop_slide.php?act=list';
                return sys_msg($GLOBALS['_LANG']['shop_ppt_edit_success'], 0, $link);
            } else {
                return $this->db->error();
            }
        }
        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $id = intval($_POST['id']);
            $order = intval($_POST['val']);
            $name = $exc->get_name($id);

            if ($exc->edit("img_order = '$order'", $id)) {
                return make_json_result($order);
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['s_edit_fail'], $name));
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("is_show='$val'", $id);

            return make_json_result($val);
        }
        /*------------------------------------------------------ */
        //-- 删除幻灯片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            /* 删除该幻灯片的图片*/
            $sql = "SELECT img_url FROM " . $this->dsc->table('seller_shopslide') . " WHERE id = '$id'";
            $img_url = $this->db->getOne($sql);

            if (!empty($img_url)) {
                $oss_img_arr = explode("/", $img_url);
                if ($oss_img_arr[1] != 'seller_themes') {
                    @unlink($img_url);
                }

                $oss_img_url = str_replace("../", "", $img_url);
                $this->dscRepository->getOssDelFile([$oss_img_url]);
            }

            $exc->drop($id);

            $url = 'seller_shop_slide.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $slide_list = $this->get_seller_slide($shop_info['seller_theme']);
            $this->smarty->assign('seller_slide_list', $slide_list);

            $this->smarty->assign('current', 'seller_shop_slide');
            return make_json_result($this->smarty->fetch('seller_shop_slide.dwt'), '');
        }
    }

    /**
     * 获取幻灯片列表
     *
     * @access  public
     * @return  array
     */
    private function get_seller_slide($seller_theme = '')
    {
        $adminru = get_admin_ru_id();
        $sql = "SELECT * FROM " . $this->dsc->table('seller_shopslide') . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND seller_theme = '$seller_theme'";

        $slide_list = $this->db->getAll($sql);

        foreach ($slide_list as $key => $val) {
            $slide_list[$key]['slide_type'] = $val['slide_type'] == 'roll' ? $GLOBALS['_LANG']['roll'] : ($val['slide_type'] == 'shade' ? $GLOBALS['_LANG']['gradient'] : '');
        }

        return $slide_list;
    }
}
