<?php

namespace App\Modules\Admin\Controllers;

use App\Models\SellerShopbg;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 控制台首页
 */
class SellerShopBgController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        //获取店铺模板

        $seller_theme = SellerShopinfo::where('ru_id', $adminru['ru_id'])->value('seller_theme');
        $seller_theme = $seller_theme ? $seller_theme : '';

        if ($_REQUEST['act'] == 'first') {
            $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '05_merchants_shop_bg']);
            admin_priv('seller_store_other');//by kong
            //获取入驻商家店铺信息 wang 商家入驻
            $res = SellerShopbg::where('ru_id', $adminru['ru_id'])->where('seller_theme', $seller_theme);
            $seller_shopbg = $this->baseRepository->getToArrayFirst($res);

            $action = 'add';
            if ($seller_shopbg) {
                $action = 'update';
            }

            if (isset($seller_shopbg['bgimg']) && !empty($seller_shopbg['bgimg'])) {
                $seller_shopbg['bgimg'] = '../' . $seller_shopbg['bgimg'];
            }

            $this->smarty->assign('shop_bg', $seller_shopbg);

            $this->smarty->assign('data_op', $action);


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_store_bg']);
            return $this->smarty->display('seller_shopbg.dwt');
        }

        /*------------------------------------------------------ */
        //-- 开店向导第二步
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'second') {
            $bgrepeat = empty($_POST['bgrepeat']) ? 'no-repeat' : trim($_POST['bgrepeat']);
            $bgcolor = empty($_POST['bgcolor']) ? '' : trim($_POST['bgcolor']);
            $show_img = empty($_POST['show_img']) ? '0' : intval($_POST['show_img']);
            $is_custom = empty($_POST['is_custom']) ? '0' : intval($_POST['is_custom']);
            $data_op = empty($_POST['data_op']) ? '' : trim($_POST['data_op']);
            $shop_bg = [
                'ru_id' => $adminru['ru_id'],
                'seller_theme' => $seller_theme,
                'bgrepeat' => $bgrepeat,
                'bgcolor' => $bgcolor,
                'show_img' => $show_img,
                'is_custom' => $is_custom
            ];
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';
            //上传店铺logo
            if (isset($_FILES['bgimg']) && !empty($_FILES['bgimg'])) {
                $file = $_FILES['bgimg'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $file_name = explode('.', $file['name']);
                        $ext = array_pop($file_name);
                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_bg_img/seller_bg_' . $seller_theme . '_' . $adminru['ru_id'] . '.' . $ext);
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $shop_bg['bgimg'] = str_replace('../', '', $file_name);
                            $this->dscRepository->getOssAddFile([$shop_bg['bgimg']]);
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/seller_' . $adminru['ru_id']));
                        }
                    }
                }
            }

            if ($data_op == 'add') {

                SellerShopbg::insert($shop_bg);
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'seller_shop_bg.php?act=first'];
                return sys_msg($GLOBALS['_LANG']['edit_store_bg_success'], 0, $lnk);
            } else {
                SellerShopbg::where('ru_id', $adminru['ru_id'])
                    ->where('seller_theme', $seller_theme)
                    ->update($shop_bg);

                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'seller_shop_bg.php?act=first'];
                return sys_msg($GLOBALS['_LANG']['update_store_bg_success'], 0, $lnk);
            }
        }
    }
}
