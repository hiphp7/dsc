<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\GoodsActivity;
use App\Models\PackageGoods;
use App\Models\Products;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Package\PackageGoodsService;
use App\Services\Package\PackageManageService;
use App\Services\Store\StoreCommonService;

/**
 * 超值礼包管理程序
 */
class PackageController extends InitController
{
    protected $timeRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $packageManageService;
    protected $storeCommonService;
    protected $goodsProdutsService;
    protected $packageGoodsService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        PackageManageService $packageManageService,
        StoreCommonService $storeCommonService,
        GoodsProdutsService $goodsProdutsService,
        PackageGoodsService $packageGoodsService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->packageManageService = $packageManageService;
        $this->storeCommonService = $storeCommonService;
        $this->goodsProdutsService = $goodsProdutsService;
        $this->packageGoodsService = $packageGoodsService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 活动列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['14_package_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['package_add'], 'href' => 'package.php?act=add']);

            $packages = $this->packageManageService->getPackageList($adminru['ru_id']);

            $this->smarty->assign('package_list', $packages['packages']);
            $this->smarty->assign('filter', $packages['filter']);
            $this->smarty->assign('record_count', $packages['record_count']);
            $this->smarty->assign('page_count', $packages['page_count']);

            $sort_flag = sort_flag($packages['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);
            $this->smarty->assign('full_page', 1);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));


            return $this->smarty->display('package_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询、翻页、排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $packages = $this->packageManageService->getPackageList($adminru['ru_id']);

            $this->smarty->assign('package_list', $packages['packages']);
            $this->smarty->assign('filter', $packages['filter']);
            $this->smarty->assign('record_count', $packages['record_count']);
            $this->smarty->assign('page_count', $packages['page_count']);

            $sort_flag = sort_flag($packages['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return make_json_result(
                $this->smarty->fetch('package_list.dwt'),
                '',
                ['filter' => $packages['filter'], 'page_count' => $packages['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('package_manage');

            /* 组合商品 */
            PackageGoods::where('package_id', 0)
                ->where('admin_id', session('admin_id'))
                ->delete();

            /* 初始化信息 */
            $start_time = $this->timeRepository->getLocalDate('Y-m-d H:i:s');
            $end_time = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime('+1 month'));
            $package = ['package_price' => '', 'start_time' => $start_time, 'end_time' => $end_time, 'ru_id' => $adminru['ru_id']];

            $this->smarty->assign('package', $package);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['package_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list']);

            set_default_filter(); //设置默认筛选

            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('ru_id', $adminru['ru_id']);


            return $this->smarty->display('package_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插�        �活动数据
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('package_manage');
            $package_name = $_POST['package_name'] ?? '';

            $res = GoodsActivity::where('act_type', GAT_PACKAGE)
                ->where('act_name', $package_name)
                ->where('user_id', $adminru['ru_id'])
                ->count();

            if ($res > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['package_exist'], $_POST['package_name']), 1);
            }

            /* 将时间转换成整数 */
            $_POST['start_time'] = $this->timeRepository->getLocalStrtoTime($_POST['start_time']);
            $_POST['end_time'] = $this->timeRepository->getLocalStrtoTime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['package_price'])) {
                $_POST['package_price'] = 0;
            }

            $info = ['package_price' => $_POST['package_price']];

            $activity_thumb = $image->upload_image($_FILES['activity_thumb'], 'activity_thumb');  //图片存放地址

            $this->dscRepository->getOssAddFile([$activity_thumb]);

            /* 插入数据 */
            $record = [
                'act_name' => $_POST['package_name'],
                'act_desc' => $_POST['desc'],
                'act_type' => GAT_PACKAGE,
                'start_time' => $_POST['start_time'],
                'user_id' => $adminru['ru_id'],
                'activity_thumb' => $activity_thumb,  //ecmoban模板堂 --zhuo
                'end_time' => $_POST['end_time'],
                'is_finished' => 0,
                'review_status' => 3,
                'ext_info' => serialize($info)
            ];

            /* 礼包编号 */
            $package_id = GoodsActivity::insertGetId($record);

            $this->packageManageService->handlePackagepGoods($package_id);

            admin_log($_POST['package_name'], 'add', 'package');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list'];
            $link[] = ['text' => $GLOBALS['_LANG']['continue_add'], 'href' => 'package.php?act=add'];
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('package_manage');

            $act_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $package = get_package_info($act_id, 0, 0, 0, "seller");
            $package_goods_list = $this->packageGoodsService->getPackageGoods($act_id, 0, 1); // 礼包商品

            $this->smarty->assign('package', $package);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['package_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list&' . list_link_postfix()]);

            set_default_filter(); //设置默认筛选

            $this->smarty->assign('form_action', 'update');
            $this->smarty->assign('package_goods_list', $package_goods_list);
            $this->smarty->assign('ru_id', $package['ru_id']);


            return $this->smarty->display('package_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新活动数据
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('package_manage');

            $act_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;

            $package_name = $_POST['package_name'] ?? '';
            /* 将时间转换成整数 */
            $_POST['start_time'] = $this->timeRepository->getLocalStrtoTime($_POST['start_time']);
            $_POST['end_time'] = $this->timeRepository->getLocalStrtoTime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['package_price'])) {
                $_POST['package_price'] = 0;
            }

            /* 检查活动重名 */
            $res = GoodsActivity::where('act_type', GAT_PACKAGE)
                ->where('act_name', $package_name)
                ->where('act_id', '<>', $act_id)
                ->where('user_id', $adminru['ru_id'])
                ->count();

            if ($res > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['package_exist'], $_POST['package_name']), 1);
            }

            $activity_thumb = $image->upload_image($_FILES['activity_thumb'], 'activity_thumb');  //图片存放地址
            $this->dscRepository->getOssAddFile([$activity_thumb]);

            $info = ['package_price' => $_POST['package_price']];

            /* 更新数据 */
            $record = [
                'act_name' => $_POST['package_name'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'act_desc' => $_POST['desc'],
                'review_status' => 3,
                'ext_info' => serialize($info)
            ];

            if (!empty($activity_thumb)) {
                $record['activity_thumb'] = $activity_thumb;
            }

            if (isset($_POST['review_status'])) {
                $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                $record['review_status'] = $review_status;
                $record['review_content'] = $review_content;
            }

            GoodsActivity::where('act_id', $act_id)
                ->where('act_type', GAT_PACKAGE)
                ->update($record);

            admin_log($_POST['package_name'], 'edit', 'package');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除活动图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_thumb') {
            /* 权限判断 */
            admin_priv('package_manage');
            $act_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $ru_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

            /* 取得logo名称 */
            $activity_thumb = GoodsActivity::where('act_id', $act_id)->value('activity_thumb');
            $activity_thumb = $activity_thumb ? $activity_thumb : '';

            if (!empty($activity_thumb)) {
                $this->dscRepository->getDelBatch('', $act_id, ['activity_thumb'], 'act_id', GoodsActivity::whereRaw(1), 1); //删除图片

                $data = ['activity_thumb' => ''];
                GoodsActivity::where('act_id', $act_id)->update($data);
            }
            $link = [['text' => $GLOBALS['_LANG']['edit_package'], 'href' => 'package.php?act=edit&id=' . $act_id], ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_package_thumb_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除指定的活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            $this->dscRepository->getDelBatch('', $id, ['activity_thumb'], 'act_id', GoodsActivity::whereRaw(1), 1); //删除图片

            GoodsActivity::where('act_id', $id)->delete();

            PackageGoods::where('package_id', $id)->delete();

            $url = 'package.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }


        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('package_manage');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg(lang('admin/package.data_null'), 1);
            }
            $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    /* 删除记录 */

                    $ids_array = $this->baseRepository->getExplode($ids);
                    $res = GoodsActivity::whereIn('act_id', $ids_array)->delete();
                    if ($res) {
                        $this->dscRepository->getDelBatch('', $ids, ['activity_thumb'], 'act_id', GoodsActivity::whereRaw(1), 1); //删除图片

                        PackageGoods::whereIn('package_id', $ids_array)->delete();
                    }

                    /* 记日志 */
                    admin_log('', 'batch_remove', 'package_manage');

                    /* 清除缓存 */
                    clear_cache_files();

                    $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];

                    $ids = $this->baseRepository->getExplode($ids);
                    $data = ['review_status' => $review_status];
                    $res = GoodsActivity::whereIn('act_id', $ids)->update($data);

                    if ($res > 0) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['package_adopt_status_set_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑活动名称
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_package_name') {
            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 检查活动重名 */
            $res = GoodsActivity::where('act_type', GAT_PACKAGE)
                ->where('act_name', $val)
                ->where('act_id', '<>', $id)
                ->count();

            if ($res > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['package_exist'], $val));
            }

            $data = ['act_name' => $val];
            GoodsActivity::where('act_id', $id)->update($data);

            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {

            $filters = dsc_decode($_GET['JSON']);

            $arr = get_goods_list($filters);

            $opt = [];
            foreach ($arr as $key => $val) {
                $opt[$key] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => $val['shop_price']];

                $opt[$key]['products'] = $this->goodsProdutsService->getGoodProducts($val['goods_id']);
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 增加一个商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_package_goods') {
            $result = [];
            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0; // 0为多商品，1为单商品

            $goods_ids = !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : '';
            $package_id = isset($_REQUEST['pid']) && !empty($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $number = isset($_REQUEST['num']) ? intval($_REQUEST['num']) : 1;
            $pbtype = !empty($_REQUEST['pbtype']) ? trim($_REQUEST['pbtype']) : '';

            if ($goods_ids) {
                $goods_ids_arr = explode(',', $goods_ids);
                if (!empty($goods_ids_arr)) {
                    foreach ($goods_ids_arr as $v) {
                        $res = PackageGoods::whereRaw(1);
                        if (empty($package_id)) {
                            $res = $res->where('admin_id', session('admin_id'));
                        }

                        $goods_count = $res->where('goods_id', $v)
                            ->where('package_id', $package_id)
                            ->count();

                        if ($goods_count == 0) {
                            $data = [
                                'package_id' => $package_id,
                                'goods_id' => $v,
                                'goods_number' => $number,
                                'admin_id' => session('admin_id')
                            ];
                            PackageGoods::insert($data);
                        }
                    }
                }
            }

            $arr = $this->packageGoodsService->getPackageGoods($package_id, 0, 1);

            $this->smarty->assign('pbtype', $pbtype);
            $this->smarty->assign('package_goods_list', $arr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/getsearchgoodsdiv.lbi');
            $result['status'] = 1;
            clear_cache_files();
            return response()->json($result);
        } elseif ($_REQUEST['act'] == 'add_package_goods_attr') {
            $result = [];
            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_ids = !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : '';
            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $number = isset($_REQUEST['num']) ? intval($_REQUEST['num']) : 1;
            $pbtype = !empty($_REQUEST['pbtype']) ? trim($_REQUEST['pbtype']) : '';

            $product_id = 0;

            if ($goods_ids) {
                $product_num = Products::where('goods_id', $goods_ids)->count();

                $goods_count = PackageGoods::where('goods_id', $goods_ids)
                    ->where('package_id', $package_id)
                    ->where('product_id', $product_id)->count();

                $num = $product_num - $goods_count;

                $goods_counts = PackageGoods::where('goods_id', $goods_ids)
                    ->where('package_id', $package_id)->count();

                $nums = $product_num - $goods_counts;
                if ($nums == 0) {
                    clear_cache_files();
                    $result['status'] = 0;
                    $result['msg'] = $GLOBALS['_LANG']['goods_attr_ok'];
                    return response()->json($result);
                }

                if ($num > 0 && $goods_count == 0) {
                    $data = [
                        'package_id' => $package_id,
                        'goods_id' => $goods_ids,
                        'goods_number' => $number,
                        'admin_id' => session('admin_id')
                    ];
                    PackageGoods::insert($data);

                } else {
                    clear_cache_files();
                    $result['status'] = 0;
                    $result['msg'] = $GLOBALS['_LANG']['goods_attr_once'];
                    return response()->json($result);
                }
            }

            $arr = $this->packageGoodsService->getPackageGoods($package_id, 0, 1);
            $this->smarty->assign('pbtype', $pbtype);
            $this->smarty->assign('package_goods_list', $arr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/getsearchgoodsdiv.lbi');
            $result['status'] = 1;
            clear_cache_files();
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 删除一个商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'drop_package_goods') {
            $result = [];

            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;

            $res = PackageGoods::where('package_id', $package_id)
                ->where('goods_id', $goods_id)
                ->where('product_id', $product_id);

            if ($package_id == 0) {
                $res = $res->where('admin_id', session('admin_id'));
            }
            $res->delete();

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 编辑一个商品数量
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_package_nuber') {
            $result = ['error' => 0];

            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
            $num = isset($_REQUEST['num']) ? intval($_REQUEST['num']) : 0;

            $res = PackageGoods::where('package_id', $package_id)
                ->where('goods_id', $goods_id)
                ->where('product_id', $product_id);
            if ($num > 0) {
                $data = ['goods_number' => $num];
                $res->update($data);
            } else {
                $goods_number = $res->value('goods_number');
                $goods_number = $goods_number ? $goods_number : 0;

                if ($goods_number > 0) {
                    $result['goods_number'] = $goods_number;
                } else {
                    $result['goods_number'] = 1;
                }

                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['goods_number_notic_one'];
            }

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 编辑一个商品属性
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_package_product') {
            $result = ['error' => 0];

            $check_auth = check_authz_json('package_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
            $old_product_id = isset($_REQUEST['old_product_id']) ? intval($_REQUEST['old_product_id']) : 0;

            if ($product_id > 0) {
                //判断改属性商品是否存在
                $product_count = PackageGoods::where('package_id', $package_id)
                    ->where('goods_id', $goods_id)
                    ->where('product_id', $product_id)
                    ->count();

                if ($product_count > 0) {
                    $result['error'] = 1;
                    $result['msg'] = $GLOBALS['_LANG']['goods_number_notic_two'];
                } else {
                    $data = ['product_id' => $product_id];

                    PackageGoods::where('package_id', $package_id)
                        ->where('goods_id', $goods_id)
                        ->where('product_id', $old_product_id)
                        ->update($data);

                }
            } else {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['system_error'];
            }

            return response()->json($result);
        }
    }
}
