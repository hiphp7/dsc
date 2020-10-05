<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Goods;
use App\Models\GoodsInventoryLogs;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\RegionWarehouse;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsPsiManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 商品管理程序
 */
class GoodsPsiController extends InitController
{
    protected $categoryService;
    protected $timeRepository;
    protected $dscRepository;
    protected $baseRepository;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $goodsPsiManageService;
    protected $storeCommonService;

    public function __construct(
        CategoryService $categoryService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        GoodsPsiManageService $goodsPsiManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->categoryService = $categoryService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsPsiManageService = $goodsPsiManageService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {

        /* 管理员ID */
        $admin_id = get_admin_id();
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /*------------------------------------------------------ */
        //-- 商品�        �库（进）
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'purchase') {
            admin_priv('goods_psi');
            $this->smarty->assign('menu_select', ['action' => '02_goods_storage', 'current' => '01_psi_purchase']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_psi_purchase']);
            $warehouse_list = area_warehouse_list();// $parent_id = 0,顶级仓库 $param = 1 只取同级仓库不获取子仓库地区
            $this->smarty->assign('warehouse_list', $warehouse_list);
            $suppliers_list = $this->goodsPsiManageService->psiSuppliersList();
            $this->smarty->assign('suppliers_list', $suppliers_list);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('psi_purchase.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商品�        �库（操作）
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'purchase_operate') {
            admin_priv('goods_psi');
            $goods_ids = !empty($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : '';
            $goods_number = !empty($_REQUEST['goods_number']) ? $_REQUEST['goods_number'] : '';
            $product_number = !empty($_REQUEST['product_number']) ? $_REQUEST['product_number'] : '';
            $batch_number = !empty($_REQUEST['batch_number']) ? $_REQUEST['batch_number'] : '';
            $region_id = !empty($_REQUEST['region_id']) ? intval($_REQUEST['region_id']) : '';
            $area_ids = !empty($_REQUEST['area_id']) ? $_REQUEST['area_id'] : '';
            $suppliers_id = !empty($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : '';
            $remark = !empty($_REQUEST['remark']) ? $_REQUEST['remark'] : '';
            $user_id = $adminru['ru_id'];
            $result = ['msg' => '', 'error' => ''];

            $time = $this->timeRepository->getGmTime();

            if ($goods_ids) {
                if ($goods_number) {
                    foreach ($goods_number as $k => $v) {
                        //商品模式
                        $warehouse_id = 0;
                        $area_id = isset($area_ids[$k]) ? intval($area_ids[$k]) : 0;

                        /* 区分商品模式改变仓库库存 */
                        $goods = get_goods_model($k);
                        $warehouse_list = get_warehouse_list();
                        if ($warehouse_list) {
                            $warehouse_id = $region_id;
                            if (empty($area_id)) {
                                $res = RegionWarehouse::where('parent_id', $region_id)->where('region_type', 1);
                                $res = $res->whereHas('getWarehouseAreaGoods', function ($query) use ($k) {
                                    $query->where('goods_id', $k);
                                });
                                $area_id = $res->value('region_id');
                                $area_id = $area_id ? $area_id : 0;
                            }
                        }
                        $res = Goods::whereRaw(1);
                        if ($goods['model_attr'] == 1 && $region_id) {
                            $res = WarehouseGoods::where('region_id', $region_id);
                            $increment_field = 'region_number';
                        } else {
                            $increment_field = 'goods_number';
                        }
                        $res = $res->where('goods_id', $k)->increment($increment_field, $v);

                        if ($res > 0) {
                            if ($v != 0) {
                                //插入日志
                                $new_log = [
                                    'goods_id' => $k,
                                    'use_storage' => 13,
                                    'admin_id' => $admin_id,
                                    'model_inventory' => $goods['model_attr'],
                                    'model_attr' => $goods['model_attr'],
                                    'warehouse_id' => $warehouse_id,
                                    'area_id' => $area_id,
                                    'suppliers_id' => $suppliers_id,
                                    'number' => '+ ' . $v,
                                    'batch_number' => $batch_number[$k],
                                    'remark' => $remark,
                                    'add_time' => $time
                                ];
                                GoodsInventoryLogs::insert($new_log);
                            }
                        } else {
                            $result['error'][] = $k;
                        }
                    }
                }

                if ($product_number) {
                    foreach ($product_number as $k => $v) {//$k goods_id  $v array('product_id'=>'val');
                        //商品模式
                        $warehouse_id = 0;
                        $area_id = isset($area_ids[$k]) ? intval($area_ids[$k]) : 0;

                        /* 区分商品模式改变仓库库存 */
                        $goods = get_goods_model($k);
                        $warehouse_list = get_warehouse_list();

                        if ($region_id) {
                            $warehouse_id = $region_id;
                            if (empty($area_id)) {
                                $res = RegionWarehouse::where('parent_id', $region_id)->where('region_type', 1);
                                $res = $res->whereHas('getWarehouseAreaGoods', function ($query) use ($k) {
                                    $query->where('goods_id', $k);
                                });
                                $area_id = $res->value('region_id');
                                $area_id = $area_id ? $area_id : 0;
                            }
                        }
                        $res = Products::whereRaw(1);
                        if ($goods['model_attr'] == 1) {
                            $res = ProductsWarehouse::where('warehouse_id', $warehouse_id);
                        } elseif ($goods['model_attr'] == 2) {
                            $res = ProductsArea::where('area_id', $area_id);
                        }

                        /* 库存入库 */
                        foreach ($v as $product_id => $val) {
                            $up_res = $res->where('product_id', $product_id)->increment('product_number', $val);

                            if ($up_res > 0) {
                                if ($val != 0) {
                                    //插入日志
                                    $new_log = [
                                        'goods_id' => $k,
                                        'use_storage' => 13,
                                        'admin_id' => $admin_id,
                                        'model_inventory' => $goods['model_attr'],
                                        'model_attr' => $goods['model_attr'],
                                        'warehouse_id' => $warehouse_id,
                                        'area_id' => $area_id,
                                        'suppliers_id' => $suppliers_id,
                                        'number' => '+ ' . $val,
                                        'product_id' => $product_id,
                                        'batch_number' => $batch_number[$k],
                                        'remark' => $remark,
                                        'add_time' => $time
                                    ];
                                    GoodsInventoryLogs::insert($new_log);
                                }
                            } else {
                                $result['error'][] = $product_id;
                            }
                        }
                    }
                }
            }
            /* 提示信息 */
            $links[] = ['href' => 'goods_psi.php?act=purchase', 'text' => $GLOBALS['_LANG']['back_purchase']];
            if ($result['error'] == 0) {
                return sys_msg($GLOBALS['_LANG']['purchase_success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['purchase_failed'], 1, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 库存管理（存）
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'inventory') {
            admin_priv('goods_psi');
            $this->smarty->assign('menu_select', ['action' => '02_goods_storage', 'current' => '03_psi_inventory']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_psi_inventory']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['export_excel'], 'href' => 'javascript:;']);
            $this->smarty->assign('full_page', 1);

            $list = $this->goodsPsiManageService->getPsiInventory();
            $this->smarty->assign('goods_list', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('psi_inventory.dwt');
        }

        /*------------------------------------------------------ */
        //-- 库存管理查询（存）
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'inventory_query') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $list = $this->goodsPsiManageService->getPsiInventory();
            $this->smarty->assign('goods_list', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('psi_inventory.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //-- 添加商品SKU/库存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_sku') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $warehouse_id = 0;
            $area_id = 0;
            $city_id = 0;

            $goods = get_goods_model($goods_id);

            $warehouse_list = get_warehouse_list();
            if ($warehouse_list) {
                $warehouse_id = $warehouse_list[0]['region_id'];
                $area_id = RegionWarehouse::where('parent_id', $warehouse_list[0]['region_id'])->value('region_id');
                $area_id = $area_id ? $area_id : 0;
            }

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            $this->smarty->assign('goods', $goods);

            $this->smarty->assign('warehouse_list', $warehouse_list);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('user_id', $user_id);

            $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

            $product_list = get_goods_product_list($goods_id, $goods['model_attr'], $warehouse_id, $area_id, $city_id);
            $this->smarty->assign('product_list', $product_list['product_list']);
            $this->smarty->assign('sku_filter', $product_list['filter']);
            $this->smarty->assign('sku_record_count', $product_list['record_count']);
            $this->smarty->assign('sku_page_count', $product_list['page_count']);
            $this->smarty->assign('query', $product_list['query']);
            $this->smarty->assign('full_page', 1);

            //是否设置属性 by wu start
            $res = Goods::where('goods_id', $goods_id);
            $goods_info = $this->baseRepository->getToArrayFirst($res);
            if ($goods['model_attr'] == 2) {
                $region_id = $area_id;
                $res = WarehouseAreaGoods::whereRaw(1);
            } elseif ($goods['model_attr'] == 1) {
                $region_id = $warehouse_id;
                $res = WarehouseGoods::whereRaw(1);
            } else {
                $region_id = 0;
                $res = Goods::whereRaw(1);
            }
            if (!empty($goods['model_attr'])) {
                $goods_info['goods_number'] = $res->where('goods_id', $goods_id)
                    ->where('region_id', $region_id)
                    ->value('region_number');
                $goods_info['goods_number'] = $goods_info['goods_number'] ? $goods_info['goods_number'] : 0;
            }
            $this->smarty->assign('goods_info', $goods_info);
            //是否设置属性 by wu end

            //判断是入库还是修改安全库存 by wu start
            $type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            switch ($type) {
                case 'safe':
                    $lbi = 'psi_goods_dialog_safe';
                    break;
                case 'in':
                    $lbi = 'psi_goods_dialog_in';
                    break;
                default:
                    $lbi = 'psi_goods_dialog_safe';
            }
            $this->smarty->assign('type', $type);
            //判断是入库还是修改安全库存 by wu end

            //补充数据 by wu start
            $suppliers_list = $this->goodsPsiManageService->psiSuppliersList();
            $this->smarty->assign('suppliers_list', $suppliers_list);
            //补充数据 by wu end

            $result['content'] = $GLOBALS['smarty']->fetch('library/' . $lbi . '.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加商品SKU/库存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sku_query') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

            $product_list = get_goods_product_list();

            $this->smarty->assign('product_list', $product_list['product_list']);
            $this->smarty->assign('sku_filter', $product_list['filter']);
            $this->smarty->assign('sku_record_count', $product_list['record_count']);
            $this->smarty->assign('sku_page_count', $product_list['page_count']);
            $this->smarty->assign('query', $product_list['query']);

            $goods = [
                'goods_id' => $product_list['filter']['goods_id'],
                'model_attr' => $product_list['filter']['model'],
                'warehouse_id' => $product_list['filter']['warehouse_id'],
                'area_id' => $product_list['filter']['area_id']
            ];
            $this->smarty->assign('goods', $goods);

            //是否设置属性 by wu start
            $goods_id = $goods['goods_id'];
            $res = Goods::where('goods_id', $goods_id);
            $goods_info = $this->baseRepository->getToArrayFirst($res);
            if ($goods['model_attr'] == 2) {
                $region_id = $goods['area_id'];
                $res = WarehouseAreaGoods::whereRaw(1);
            } elseif ($goods['model_attr'] == 1) {
                $region_id = $goods['warehouse_id'];
                $res = WarehouseGoods::whereRaw(1);
            } else {
                $region_id = 0;
                $res = Goods::whereRaw(1);
            }
            if (!empty($goods['model_attr'])) {
                $goods_info['goods_number'] = $res->where('goods_id', $goods_id)->where('region_id', $region_id)->value('region_number');
                $goods_info['goods_number'] = $goods_info['goods_number'] ? $goods_info['goods_number'] : 0;
            }
            $this->smarty->assign('goods_info', $goods_info);
            //是否设置属性 by wu end

            //判断是入库还是修改安全库存 by wu start
            $type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            switch ($type) {
                case 'safe':
                    $lbi = 'psi_goods_dialog_safe';
                    break;
                case 'in':
                    $lbi = 'psi_goods_dialog_in';
                    break;
                default:
                    $lbi = 'psi_goods_dialog_safe';
            }
            $this->smarty->assign('type', $type);
            //判断是入库还是修改安全库存 by wu end

            return make_json_result($this->smarty->fetch('library/' . $lbi . '.lbi'), '', ['pb_filter' => $product_list['filter'], 'pb_page_count' => $product_list['page_count'], 'class' => "attrlistDiv"]);
        }

        /* ------------------------------------------------------ */
        //-- 添加商品SKU/库存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_inventory') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_type = empty($_REQUEST['goods_type']) ? 0 : intval($_REQUEST['goods_type']);
            $goods_model = empty($_REQUEST['goods_model']) ? 0 : intval($_REQUEST['goods_model']);
            $warehouse_id = empty($_REQUEST['warehouse_id']) ? 0 : intval($_REQUEST['warehouse_id']);
            $area_id = empty($_REQUEST['area_id']) ? 0 : intval($_REQUEST['area_id']);
            $goods_number = empty($_REQUEST['goods_number']) ? 0 : intval($_REQUEST['goods_number']);
            $product_number = empty($_REQUEST['product_number']) ? [] : $_REQUEST['product_number'];
            $goods_batch_number = empty($_REQUEST['goods_batch_number']) ? '' : $_REQUEST['goods_batch_number'];
            $product_batch_number = empty($_REQUEST['product_batch_number']) ? [] : $_REQUEST['product_batch_number'];
            $suppliers_id = empty($_REQUEST['suppliers_id']) ? 0 : intval($_REQUEST['suppliers_id']);
            $remark = empty($_REQUEST['remark']) ? '' : trim($_REQUEST['remark']);

            $time = $this->timeRepository->getGmTime();

            if ($goods_id > 0) {
                //判断是地区还是仓库
                if ($goods_model == 2) {
                    $region_id = $area_id;
                    $goods_res = WarehouseAreaGoods::whereRaw(1);
                    $products_res = ProductsArea::whereRaw(1);
                } elseif ($goods_model == 1) {
                    $region_id = $warehouse_id;
                    $goods_res = WarehouseGoods::whereRaw(1);
                    $products_res = ProductsWarehouse::whereRaw(1);
                } else {
                    $region_id = 0;
                    $goods_res = Goods::whereRaw(1);
                    $products_res = Products::whereRaw(1);
                }

                //日志基础数据
                $new_log = [
                    'goods_id' => $goods_id,
                    'use_storage' => 13,
                    'admin_id' => $admin_id,
                    'model_inventory' => $goods_model,
                    'model_attr' => $goods_model,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'suppliers_id' => $suppliers_id,
                    'batch_number' => '',
                    'remark' => $remark,
                    'add_time' => $time
                ];

                //货品还是商品
                if (!empty($goods_type)) {
                    if ($product_number) {
                        foreach ($product_number as $key => $val) {
                            $val = empty($val) ? 0 : intval($val);
                            $batch_number = empty($product_batch_number[$key]) ? '' : $product_batch_number[$key];
                            if (!empty($val)) {
                                $products_res->where('product_id', $key)->increment('product_number', $val);

                                //插入日志
                                $this_log = $new_log;
                                $this_log['number'] = "+ " . $val;
                                $this_log['product_id'] = $key;
                                $this_log['batch_number'] = $batch_number;
                                GoodsInventoryLogs::insert($this_log);
                            }
                        }
                    }
                } else {
                    if (empty($goods_model)) {
                        $goods_res->where('goods_id', $goods_id)->increment('goods_number', $goods_number);
                    } else {
                        $goods_res->where('goods_id', $goods_id)->where('region_id', $region_id)->increment('region_number', $goods_number);
                    }

                    if ($goods_number != 0) {
                        //插入日志
                        $this_log = $new_log;
                        $this_log['number'] = "+ " . $goods_number;
                        $this_log['batch_number'] = $goods_batch_number;
                        GoodsInventoryLogs::insert($this_log);
                    }
                }
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改商品安�        �库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_warn_number') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $goods_id = intval($_POST['id']);
            $warn_number = intval($_POST['val']);
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;

            if ($goods_model == 1) {
                $table = "warehouse_goods";
            } elseif ($goods_model == 2) {
                $table = "warehouse_area_goods";
            } else {
                $table = "goods";
            }

            /* 修改商品安全库存 */
            $data = ['warn_number' => $warn_number];
            $result = Goods::where('goods_id', $goods_id)->update($data);
            if ($result) {
                clear_cache_files();
                return make_json_result($warn_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品安�        �库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_warn_number') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $product_id = intval($_POST['id']);
            $product_warn_number = intval($_POST['val']);
            $goods_model = isset($_REQUEST['goods_model']) ? intval($_REQUEST['goods_model']) : 0;

            if ($goods_model == 1) {
                $res = ProductsWarehouse::whereRaw(1);
            } elseif ($goods_model == 2) {
                $res = ProductsArea::whereRaw(1);
            } else {
                $res = Products::whereRaw(1);
            }

            /* 修改货品安全库存 */
            $data = ['product_warn_number' => $product_warn_number];
            $result = $res->where('product_id', $product_id)->update($data);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_warn_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 库存日志（存）
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'psi_log') {
            admin_priv('goods_psi');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['stock_journal']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['export_excel'], 'href' => 'javascript:;']);
            $this->smarty->assign('full_page', 1);

            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $list = $this->goodsPsiManageService->getPsiLog();
            $this->smarty->assign('goods_list', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('psi_log.dwt');
        }

        /*------------------------------------------------------ */
        //-- 库存日志查询（存）
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'psi_log_query') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $list = $this->goodsPsiManageService->getPsiLog();
            $this->smarty->assign('goods_list', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('psi_log.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 修改批次
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_psi_log') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $number = intval($_POST['val']);

            $time = $this->timeRepository->getGmTime();

            if (!empty($number)) {
                //处理之前先查询原数据(如果在修改时商品模式或者属性发生变化则根据具体情况进行修改)
                $res = GoodsInventoryLogs::where('id', $id);
                $psi_log = $this->baseRepository->getToArrayFirst($res);
                //判断是地区还是仓库
                if (!empty($psi_log['area_id'])) {
                    $region_id = $psi_log['area_id'];
                    $goods_res = WarehouseAreaGoods::whereRaw(1);
                    $products_res = ProductsArea::whereRaw(1);
                } elseif (!empty($psi_log['warehouse_id'])) {
                    $region_id = $psi_log['warehouse_id'];
                    $goods_res = WarehouseGoods::whereRaw(1);
                    $products_res = ProductsWarehouse::whereRaw(1);
                } else {
                    $region_id = 0;
                    $goods_res = Goods::whereRaw(1);
                    $products_res = Products::whereRaw(1);
                }
                //货品还是商品
                if (!empty($psi_log['product_id'])) {
                    $products_res->where('product_id', $psi_log['product_id'])->increment('product_number', $number);
                } else {
                    if (empty($region_id)) {
                        $goods_res->where('goods_id', $psi_log['goods_id'])->increment('goods_number', $number);
                    } else {
                        $goods_res->where('goods_id', $psi_log['goods_id'])
                            ->where('region_id', $region_id)
                            ->increment('region_number', $number);
                    }
                }
                //插入日志
                $new_log = [];
                $new_log['id'] = null;
                $new_log['admin_id'] = $admin_id;
                $new_log['add_time'] = $time;
                if ($number > 0) {
                    $new_log['use_storage'] = 13;
                    $new_log['number'] = "+ " . $number;
                } elseif ($number < 0) {
                    $new_log['use_storage'] = 8;
                    $new_log['number'] = "- " . -$number;
                } else {
                }
                $insert_log = array_merge($psi_log, $new_log);
                GoodsInventoryLogs::insert($insert_log);
                //$id = $this->db->insert_id();
            }

            clear_cache_files();
            return make_json_result($number);
        }

        /*--------------------------------------------------------*/
        //商品模块弹窗
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'goods_info') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $result = ['content' => '', 'mode' => ''];
            /*处理数组*/
            $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if (!empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode(stripslashes($_REQUEST['spec_attr']), true);
            }
            $spec_attr['is_title'] = isset($spec_attr['is_title']) ? $spec_attr['is_title'] : 0;
            $spec_attr['itemsLayout'] = isset($spec_attr['itemsLayout']) ? $spec_attr['itemsLayout'] : 'row4';
            $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $region = !empty($_REQUEST['region_id']) ? intval($_REQUEST['region_id']) : '';

            //取得商品列表
            if ($spec_attr['goods_ids']) {
                $res = Goods::whereRaw(1);
                if ($region) {
                    $res = $res->where('model_price', '>', 0);
                } else {
                    $res = $res->where('model_price', 0);
                }
                $goods_info = explode(',', $spec_attr['goods_ids']);
                foreach ($goods_info as $k => $v) {
                    if (!$v) {
                        unset($goods_info[$k]);
                    }
                }
                if (!empty($goods_info)) {
                    $goods_info = $this->baseRepository->getExplode($goods_info);
                    $res = $res->where('is_on_sale', 1)
                        ->where('is_delete', 0)
                        ->whereIn('goods_id', $goods_info);

                    //ecmoban模板堂 --zhuo start
                    if ($GLOBALS['_CFG']['review_goods'] == 1) {
                        $res = $res->where('review_status', '>', 2);
                    }
                    //ecmoban模板堂 --zhuo end
                    $goods_list = $this->baseRepository->getToArrayGet($res);

                    foreach ($goods_list as $k => $v) {
                        $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                    }

                    $this->smarty->assign('goods_list', $goods_list);
                    $this->smarty->assign('goods_count', count($goods_list));
                }
            }
            /* 取得分类列表 */
            //获取下拉列表 by wu start
            set_default_filter(); //设置默认筛选
            $this->smarty->assign('parent_category', get_every_category()); //上级分类导航
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('arr', $spec_attr);
            $this->smarty->assign("mode", $result['mode']);
            $this->smarty->assign("lift", $lift);
            $result['content'] = $GLOBALS['smarty']->fetch('library/select_purchase_goods.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品模块
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'changedgoods') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            load_helper('goods');
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $spec_attr = [];
            $result['lift'] = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $result['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if ($_REQUEST['spec_attr']) {
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
                }
            }
            $sort_order = isset($_REQUEST['sort_order']) ? $_REQUEST['sort_order'] : 1;
            $cat_id = isset($_REQUEST['cat_id']) ? explode('_', $_REQUEST['cat_id']) : [];
            $brand_id = isset($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
            $keyword = isset($_REQUEST['keyword']) ? addslashes($_REQUEST['keyword']) : '';
            $goodsAttr = isset($spec_attr['goods_ids']) ? explode(',', $spec_attr['goods_ids']) : '';
            $goods_ids = isset($_REQUEST['goods_ids']) ? explode(',', $_REQUEST['goods_ids']) : '';
            $result['goods_ids'] = !empty($goodsAttr) ? $goodsAttr : $goods_ids;
            $result['cat_desc'] = isset($spec_attr['cat_desc']) ? addslashes($spec_attr['cat_desc']) : '';
            $result['cat_name'] = isset($spec_attr['cat_name']) ? addslashes($spec_attr['cat_name']) : '';
            $result['align'] = isset($spec_attr['align']) ? addslashes($spec_attr['align']) : '';
            $result['is_title'] = isset($spec_attr['is_title']) ? intval($spec_attr['is_title']) : 0;
            $result['itemsLayout'] = isset($spec_attr['itemsLayout']) ? addslashes($spec_attr['itemsLayout']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $temp = isset($_REQUEST['temp']) ? $_REQUEST['temp'] : 'goods_list';
            $resetRrl = isset($_REQUEST['resetRrl']) ? intval($_REQUEST['resetRrl']) : 0;
            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $region = !empty($_REQUEST['region_id']) ? intval($_REQUEST['region_id']) : '';//是否选择仓库

            $this->smarty->assign('temp', $temp);

            $where = [
                'sort_order' => $sort_order,
                'brand_id' => $brand_id,
                'cat_id' => $cat_id,
                'type' => $type,
                'goods_ids' => $result['goods_ids'],
                'region' => $region
            ];

            if ($type == 1) {
                $where['is_page'] = 1;

                $list = $this->goodsPsiManageService->getPisGoodsList($where);
                $goods_list = $list['list'];
                $filter = $list['filter'];
                $filter['cat_id'] = $cat_id[0];
                $filter['sort_order'] = $sort_order;
                $filter['keyword'] = $keyword;
                $filter['region_id'] = $region;
                $this->smarty->assign('filter', $filter);
            } else {
                $where['is_page'] = 0;

                $goods_list = $this->goodsPsiManageService->getPisGoodsList($where);
            }

            if (!empty($goods_list)) {
                foreach ($goods_list as $k => $v) {
                    $goods_list[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
                    $goods_list[$k]['original_img'] = get_image_path($v['original_img']);
                    $goods_list[$k]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $v['goods_id']], $v['goods_name']);
                    $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                    if ($v['promote_price'] > 0) {
                        $goods_list[$k]['promote_price'] = $this->goodsCommonService->getBargainPrice($v['promote_price'], $v['promote_start_date'], $v['promote_end_date']);
                    } else {
                        $goods_list[$k]['promote_price'] = 0;
                    }
                    if ($v['goods_id'] > 0 && in_array($v['goods_id'], $result['goods_ids']) && !empty($result['goods_ids'])) {
                        $goods_list[$k]['is_selected'] = 1;
                    }
                }
            }

            $this->smarty->assign("is_title", $result['is_title']);
            $this->smarty->assign('goods_list', $goods_list);

            $this->smarty->assign('goods_count', count($goods_list));
            $this->smarty->assign('attr', $spec_attr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/psi_goods_list.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加�        �库商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_purchase_goods') {
            $check_auth = check_authz_json('goods_psi');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $goods_ids = empty($_REQUEST['goods_ids']) || $_REQUEST['goods_ids'] == 'undefined' ? '' : explode(',', $_REQUEST['goods_ids']);
            $selected = empty($_REQUEST['selected']) ? '' : explode(',', $_REQUEST['selected']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($selected) {
                if ($goods_ids) {
                    $goods_ids_arr = array_unique(array_merge($goods_ids, $selected));
                    $goods_ids = implode(',', $goods_ids_arr);
                } else {
                    $goods_ids = implode(',', $selected);
                }
            } else {
                $goods_ids = implode(',', $goods_ids);
            }
            if ($goods_ids) {
                $list = $this->goodsPsiManageService->getSelectPurchaseGoods($goods_ids);
                $this->smarty->assign('purchase_goods', $list);
                $this->smarty->assign('goods_ids', $goods_ids);
                $result['content'] = $this->smarty->fetch('templates/psi_purchase.dwt');
                return response()->json($result);
            }
        }
    }
}
