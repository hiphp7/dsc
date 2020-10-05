<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\SellerShopinfo;
use App\Models\SellerShopwindow;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantsCustomManageService;

/**
 * 管理中心入驻商家店铺橱窗管理
 */
class MerchantsCustomController extends InitController
{
    protected $baseRepository;
    protected $merchantsCustomManageService;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantsCustomManageService $merchantsCustomManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantsCustomManageService = $merchantsCustomManageService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        //获取店铺模板
        $adminru = get_admin_ru_id();
        $res = SellerShopinfo::where('ru_id', $adminru['ru_id']);
        $seller_shopinfo = $this->baseRepository->getToArrayFirst($res);

        $seller_theme = $seller_shopinfo['seller_theme'];

        $shop_id = SellerShopinfo::where('ru_id', $adminru['ru_id'])->count();
        if ($shop_id < 1) {
            $lnk[] = ['text' => lang('admin/merchants_custom.set_shop'), 'href' => 'index.php?act=merchants_first'];
            return sys_msg(lang('admin/merchants_custom.not_set_shop'), 0, $lnk);
        }

        $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '06_merchants_custom']);
        /*------------------------------------------------------ */
        //-- 店铺橱窗列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_store_other');//by kong
            $this->smarty->assign('ur_here', lang('admin/merchants_custom.window_list'));
            $this->smarty->assign('action_link', ['text' => lang('admin/merchants_custom.add_window'), 'href' => 'merchants_custom.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $win_list = $this->merchantsCustomManageService->getSellerCustom($seller_theme);

            $this->smarty->assign('win_list', $win_list);


            return $this->smarty->display('merchants_custom_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {

            /* 创建 百度编辑器 wang 商家入驻 */
            create_ueditor_editor('win_custom', '', 586);

            $this->smarty->assign('ur_here', lang('admin/merchants_custom.add_window'));
            $this->smarty->assign('action_link', ['text' => lang('admin/merchants_custom.window_list'), 'href' => 'merchants_custom.php?act=list']);
            $this->smarty->assign('form_action', 'insert');

            $type_list = $this->merchantsCustomManageService->winGoodsTypeList($seller_shopinfo['win_goods_type']);
            $this->smarty->assign('type_list', $type_list);


            return $this->smarty->display('merchants_custom_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            $is_show = isset($_REQUEST['isshow']) ? intval($_REQUEST['isshow']) : 0;

            $win_name = !empty($_POST['winname']) ? stripslashes($_POST['winname']) : '';

            $win_img_link = !empty($_POST['winimglink']) ? stripslashes($_POST['winimglink']) : '#';

            $win_order = isset($_POST['winorder']) ? intval($_POST['winorder']) : 0;

            $win_type = isset($_REQUEST['wintype']) ? intval($_REQUEST['wintype']) : 0;

            $win_goods_type = isset($_REQUEST['win_goods_type']) ? intval($_REQUEST['win_goods_type']) : 1;

            $win_color = isset($_REQUEST['wincolor']) ? stripslashes($_REQUEST['wincolor']) : '';

            $preg = "/<script[\s\S]*?<\/script>/i";

            $win_custom = isset($_REQUEST['win_custom']) ? preg_replace($preg, "", stripslashes($_REQUEST['win_custom'])) : '';

            //检查名称是否重复
            $number = SellerShopwindow::where('win_name', $win_name)->count();
            if ($number > 0) {
                return sys_msg(sprintf(lang('admin/merchants_custom.window_name_exist'), stripslashes($_POST['winname'])), 1);
            }

            /*插入数据*/
            if ($win_name) {
                $data = [
                    'win_type' => $win_type,
                    'win_goods_type' => $win_goods_type,
                    'win_order' => $win_order,
                    'win_name' => $win_name,
                    'is_show' => $is_show,
                    'win_color' => $win_color,
                    'win_img_link' => $win_img_link,
                    'ru_id' => $adminru['ru_id'],
                    'win_custom' => $win_custom,
                    'seller_theme' => $seller_theme
                ];
                $id = SellerShopwindow::insertGetId($data);

                admin_log($_POST['winname'], 'add', 'seller_shopwindow');

                /* 清除缓存 */
                clear_cache_files();

                $link[0]['text'] = lang('admin/merchants_custom.continue_add');
                $link[0]['href'] = 'merchants_custom.php?act=add';

                $link[1]['text'] = lang('admin/merchants_custom.back_list');
                $link[1]['href'] = 'merchants_custom.php?act=list';

                $link[2]['text'] = lang('admin/merchants_custom.add_window_goods');
                $link[2]['href'] = 'merchants_custom.php?act=add_win_goods&id=' . $id;

                return sys_msg(lang('admin/merchants_custom.add_window_success'), 0, $link);
            } else {
                $link[0]['text'] = lang('admin/merchants_custom.continue_add');
                $link[0]['href'] = 'merchants_custom.php?act=add';

                return sys_msg(lang('admin/merchants_custom.add_window_fail'), 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */

            //admin_priv('brand_manage');
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $res = SellerShopwindow::where('id', $id)->where('ru_id', $adminru['ru_id']);
            $seller_win = $this->baseRepository->getToArrayFirst($res);

            /* 创建 百度编辑器 wang 商家入驻 */
            create_ueditor_editor('win_custom', $seller_win['win_custom'], 586);

            $this->smarty->assign('ur_here', lang('admin/merchants_custom.edit_window'));
            $this->smarty->assign('action_link', ['text' => lang('admin/merchants_custom.window_list'), 'href' => 'merchants_custom.php?act=list']);
            $this->smarty->assign('seller_win', $seller_win);
            $this->smarty->assign('form_action', 'update');

            $type_list = $this->merchantsCustomManageService->winGoodsTypeList($seller_shopinfo['win_goods_type']);
            $this->smarty->assign('type_list', $type_list);


            return $this->smarty->display('merchants_custom_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            //admin_priv('brand_manage');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $is_show = isset($_REQUEST['isshow']) ? intval($_REQUEST['isshow']) : 0;

            $win_name = !empty($_POST['winname']) ? stripslashes($_POST['winname']) : '';

            $win_img_link = !empty($_POST['winimglink']) ? stripslashes($_POST['winimglink']) : '#';

            $win_order = isset($_POST['winorder']) ? intval($_POST['winorder']) : 0;

            $win_type = isset($_REQUEST['wintype']) ? intval($_REQUEST['wintype']) : 0;

            $win_goods_type = isset($_REQUEST['win_goods_type']) ? intval($_REQUEST['win_goods_type']) : 1;

            $win_color = isset($_REQUEST['wincolor']) ? stripslashes($_REQUEST['wincolor']) : '';

            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";

            $win_custom = isset($_REQUEST['win_custom']) ? preg_replace($preg, "", stripslashes($_REQUEST['win_custom'])) : '';


            /*处理图片*/
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';

            if (isset($_FILES['winimg']) && !empty($_FILES['winimg'])) {
                $file = $_FILES['winimg'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $ext = array_pop(explode('.', $file['name']));
                        $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_win_img/seller_' . $adminru['ru_id']);
                        if (!is_dir($file_dir)) {
                            mkdir($file_dir);
                        }
                        $file_name = $file_dir . "/win_" . gmtime() . '.' . $ext;
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $win_img = $file_name;
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], $file_dir));
                        }
                    }
                }
            }

            $data = [
                'win_type' => $win_type,
                'win_goods_type' => $win_goods_type,
                'win_order' => $win_order,
                'win_name' => $win_name,
                'is_show' => $is_show,
                'win_color' => $win_color,
                'win_img_link' => $win_img_link,
                'win_custom' => $win_custom,
                'seller_theme' => $seller_theme
            ];

            $param = "win_type='$win_type',win_goods_type='$win_goods_type', win_order='$win_order', is_show='$is_show',win_color='$win_color'
            ,win_img_link='$win_img_link',win_custom='$win_custom',seller_theme='$seller_theme' ";
            if (!empty($win_img)) {
                //有图片上传
                $data['win_img'] = $win_img;
            }

            $is_only = SellerShopwindow::where('win_name', $win_name)->count();

            if ($is_only) {
                $data['win_name'] = $win_name;
            }
            $res = SellerShopwindow::where('id', $id)->update($data);

            if ($res >= 0) {
                /* 清除缓存 */
                clear_cache_files();

                admin_log($_POST['winname'], 'edit', 'seller_shopwindow');

                $link[0]['text'] = lang('admin/merchants_custom.back_list');
                $link[0]['href'] = 'merchants_custom.php?act=list';
                return sys_msg(lang('admin/merchants_custom.edit_window_success'), 0, $link);
            } else {
                return $this->db->error();
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑橱窗名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_win_name') {
            $id = intval($_POST['id']);
            $name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否重复 */
            $number = SellerShopwindow::where('win_name', $name)->where('ru_id', session('ru_id'))->count();
            if ($number > 0) {
                return make_json_error(sprintf(lang('admin/merchants_custom.window_exist'), $name));
            } else {
                $data = ['win_name' => $name];
                $res = SellerShopwindow::where('id', $id)->update($data);
                if ($res >= 0) {
                    admin_log($name, 'edit', 'seller_shopwindow');
                    return make_json_result(stripslashes($name));
                } else {
                    return make_json_result(sprintf(lang('admin/merchants_custom.edit_fail'), $name));
                }
            }
        }
        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $id = intval($_POST['id']);
            $order = intval($_POST['val']);
            $name = SellerShopwindow::where('id', $id)->value('win_name');
            $name = $name ? $name : '';

            $data = ['win_order' => $order];
            $res = SellerShopwindow::where('id', $id)->update($data);

            if ($res >= 0) {
                return make_json_result($order);
            } else {
                return make_json_error(sprintf(lang('admin/merchants_custom.edit_fail'), $name));
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['is_show' => $val];
            SellerShopwindow::where('id', $id)->update($data);

            return make_json_result($val);
        }
        /*------------------------------------------------------ */
        //-- 删除橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            /* 删除该橱窗的图标 */
            $res = SellerShopwindow::where('id', $id)->where('ru_id', $adminru['ru_id']);
            $win = $this->baseRepository->getToArrayFirst($res);

            $win_img = $win['win_img'];
            if (!empty($win_img)) {
                @unlink($win_img);
            }

            if ($win['win_custom']) {
                $desc_preg = get_goods_desc_images_preg('', $win['win_custom'], 'win_custom');
                get_desc_images_del($desc_preg['images_list']);
            }

            SellerShopwindow::where('id', $id)->delete();


            $url = 'merchants_custom.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $win_list = $this->merchantsCustomManageService->getSellerCustom($seller_theme);
            $this->smarty->assign('win_list', $win_list);

            return make_json_result($this->smarty->fetch('merchants_custom_list.dwt'), '');
        }

        /*------------------------------------------------------ */
        //-- 添加商品橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_win_goods') {
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $win_goods = $this->merchantsCustomManageService->getWinGoods($id);

            if ($win_goods == 'no_cc') {
                return sys_msg(lang('admin/merchants_custom.no_access'));
            } else {
                $win_info = $this->merchantsCustomManageService->getWinInfo($id);

                $this->smarty->assign('win_info', $win_info);

                $this->smarty->assign('ur_here', lang('admin/merchants_custom.add_window_goods'));

                $this->smarty->assign('goods_count', count($win_goods));
                $this->smarty->assign('win_goods', $win_goods);

                set_default_filter(); //设置默认筛选


                return $this->smarty->display('add_win_goods.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 更新商品橱窗
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_win_goods') {
            $id = !empty($_POST['win_id']) ? intval($_POST['win_id']) : 0;

            $link[0]['text'] = lang('admin/merchants_custom.continue_add');
            $link[0]['href'] = 'merchants_custom.php?act=add_win_goods&id=' . $id;
            $link[1]['text'] = lang('admin/merchants_custom.back_list');
            $link[1]['href'] = 'merchants_custom.php?act=list';
            return sys_msg(lang('admin/merchants_custom.edit_window_goods_success'), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 把商品加�        �商品柜
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_win_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $linked_array = dsc_decode($_GET['add_ids'], true);
            $linked_window = dsc_decode($_GET['JSON'], true);
            $id = $linked_window[0];
            $win_goods = SellerShopwindow::where('id', $id)->value('win_goods');
            $win_goods = $win_goods ? $win_goods : '';
            foreach ($linked_array as $val) {
                if (!strstr($win_goods, $val) && !empty($val)) {
                    $win_goods .= !empty($win_goods) ? ',' . $val : $val;
                }
            }

            $data = ['win_goods' => $win_goods];
            SellerShopwindow::where('id', $id)->update($data);

            $win_goods = $this->merchantsCustomManageService->getWinGoods($id);
            $options = [];

            foreach ($win_goods as $val) {
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
        elseif ($_REQUEST['act'] == 'drop_win_goods') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = dsc_decode($_GET['drop_ids'], true);

            $linked_window = dsc_decode($_GET['JSON'], true);
            $id = $linked_window[0];

            $win_goods = SellerShopwindow::where('id', $id)->value('win_goods');
            $win_goods = $win_goods ? $win_goods : '';
            $win_goods_arr = explode(',', $win_goods);

            foreach ($drop_goods as $val) {
                if (strstr($win_goods, $val) && !empty($val)) {
                    $key = array_search($val, $win_goods_arr);
                    if ($key !== false) {
                        array_splice($win_goods_arr, $key, 1);
                    }
                }
            }
            $new_win_goods = '';
            foreach ($win_goods_arr as $val) {
                if (!strstr($new_win_goods, $val) && !empty($val)) {
                    $new_win_goods .= !empty($new_win_goods) ? ',' . $val : $val;
                }
            }

            $data = ['win_goods' => $new_win_goods];
            SellerShopwindow::where('id', $id)->update($data);

            $win_goods = $this->merchantsCustomManageService->getWinGoods($id);
            $options = [];

            foreach ($win_goods as $val) {
                $options[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($options);
        } elseif ($_REQUEST['act'] = "batch") {
            $checkboxes = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : [];
            $type = isset($_POST['type']) ? intval($_POST['type']) : 0;

            if ($checkboxes) {
                if ($type == 1) {
                    $id = implode(',', $checkboxes);

                    SellerShopwindow::where('win_type', 0)->whereIn('id', $id)->delete();
                }
            }

            $link[0]['text'] = lang('admin/merchants_custom.back_shop_list');
            $link[0]['href'] = 'merchants_custom.php?act=list';

            return sys_msg(lang('admin/merchants_custom.delete_success'), 0, $link);
        }
    }
}
