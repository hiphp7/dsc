<?php

namespace App\Modules\Stores\Controllers;

use App\Libraries\Image;
use App\Models\OfflineStore;
use App\Models\StoreUser;
use App\Repositories\Common\DscRepository;
use App\Services\Common\CommonManageService;

/**
 * 门店控制台首页
 */
class IndexController extends InitController
{
    protected $dscRepository;
    protected $config;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    public function index()
    {
        /* act操作项的初始化 */
        $act = request()->input('act', '');

        if ($act == 'merchants_first' || $act == 'shop_top' || $act == 'merchants_second') {
            $this->smarty->assign('action_type', "index");
        } else {
            $this->smarty->assign('action_type', "");
        }

        //设置logo
        $stores_logo = strstr($this->config['stores_logo'], "images");
        $this->smarty->assign('stores_logo', $stores_logo);

        /* ------------------------------------------------------ */
        //-- 框架
        /* ------------------------------------------------------ */
        if ($act == '') {
            return redirect(STORES_PATH . '/goods.php?act=list');
        }

        /* ------------------------------------------------------ */
        //-- 上传门店头像
        /* ------------------------------------------------------ */
        elseif ($act == 'upload_store_img') {
            $result = ["error" => 0, "message" => "", "content" => ""];

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            if (isset($_FILES['img']) && $_FILES['img']['name']) {
                $dir = 'store_user';
                $img_name = $image->upload_image($_FILES['img'], $dir);
                $store_user_id = session('store_user_id', 0);
                if ($img_name) {
                    $result['error'] = 1;
                    $result['content'] = get_image_path($img_name);

                    $model = StoreUser::where('id', $store_user_id)->first();
                    $store_user_img = $model->store_user_img;

                    //删除原图片
                    @unlink(storage_public($store_user_img));

                    //插入新图片
                    $model->store_user_img = $img_name;
                    $model->save();
                }
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 上传门店图片
        /* ------------------------------------------------------ */
        elseif ($act == 'upload_stores_img') {
            $result = ["error" => 0, "message" => "", "content" => ""];

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            if (isset($_FILES['stores_img']) && $_FILES['stores_img']['name']) {
                $dir = 'offline_store';
                $img_name = $image->upload_image($_FILES['stores_img'], $dir);
                if ($img_name) {
                    $result['error'] = 1;
                    $result['content'] = get_image_path($img_name);
                    //删除原图片
                    $model = OfflineStore::where('id', session('stores_id'))->first();
                    $stores_img = $model->stores_img;
                    @unlink(storage_public($stores_img));
                    //插入新图片
                    $model->stores_img = $img_name;
                    $model->save();
                }
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 查询订单
        /* ------------------------------------------------------ */
        elseif ($act == 'check_order') {
            $ru_id = $GLOBALS['db']->getOne(" SELECT ru_id FROM " . $GLOBALS['dsc']->table('offline_store') . " WHERE id = '" . session('stores_id') . "'");
            $where = "";
            //区分自营或是店铺
            if ($ru_id > 0) {
                //只获取上级店铺的订单
                $where .= " AND o.ru_id = '$ru_id' ";
            } else {
                $where .= " AND o.ru_id = 0 ";
            }

            $where .= " AND (select count(*) from " . $this->dsc->table('order_info') . " as oi WHERE oi.main_order_id = o.order_id) = 0 ";  //主订单下有子订单时，则主订单不显示
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('store_order') . " as o, " .
                $this->dsc->table('order_info') . " AS oi1 " .
                " WHERE (o.store_id = '" . session('stores_id') . "' OR (o.store_id = '0' AND o.is_grab_order = '1' AND FIND_IN_SET('" . session('stores_id') . "',grab_store_list)))" . $where . " AND oi1.order_id = o.order_id";
            $arr['new_orders'] = $this->db->getOne($sql);
            return make_json_result('', '', $arr);
        }

        /* ------------------------------------------------------ */
        //-- 登录状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'login_status') {
            $status = app(CommonManageService::class)->loginStatus('store');
            return response()->json(['status' => $status]);
        }
    }
}
