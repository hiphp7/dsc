<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Goods;
use App\Models\GoodsInventoryLogs;
use App\Models\ProductsArea;
use App\Repositories\Common\BaseRepository;
use App\Services\Goods\GoodsAreaAttrManageService;
use App\Services\Goods\GoodsAttrService;

/**
 * 会员管理程序
 */
class GoodsAreaAttrController extends InitController
{
    protected $baseRepository;
    protected $goodsAreaAttrManageService;
    protected $goodsAttrService;

    public function __construct(
        BaseRepository $baseRepository,
        GoodsAreaAttrManageService $goodsAreaAttrManageService,
        GoodsAttrService $goodsAttrService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->goodsAreaAttrManageService = $goodsAreaAttrManageService;
        $this->goodsAttrService = $goodsAttrService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();
        /*------------------------------------------------------ */
        //-- 用户帐号列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'warehouse_list') {
            /* 检查权限 */
            admin_priv('goods_manage');

            $goods_id = isset($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : 0;

            $date = ['goods_name'];

            $where = "goods_id = '$goods_id'";
            $goods_name = get_table_date('goods', $where, $date, 2);

            $this->smarty->assign('ur_here', $goods_name);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list']);

            $area_list = $this->goodsAreaAttrManageService->areaProductList($goods_id);

            session([
                'warehouse_goods_id' => $goods_id
            ]);

            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('area_list', $area_list['area_list']);
            $this->smarty->assign('filter', $area_list['filter']);
            $this->smarty->assign('record_count', $area_list['record_count']);
            $this->smarty->assign('page_count', $area_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            return $this->smarty->display('goods_area_attr_list.htm');
        }

        /*------------------------------------------------------ */
        //-- ajax返回用户列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $area_list = $this->goodsAreaAttrManageService->areaProductList();
            $this->smarty->assign('goods_id', session('warehouse_goods_id'));
            $this->smarty->assign('area_list', $area_list['area_list']);
            $this->smarty->assign('filter', $area_list['filter']);
            $this->smarty->assign('record_count', $area_list['record_count']);
            $this->smarty->assign('page_count', $area_list['page_count']);

            $sort_flag = sort_flag($area_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('goods_area_attr_list.htm'), '', ['filter' => $area_list['filter'], 'page_count' => $area_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 货品列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_list') {
            admin_priv('goods_manage');

            $goods_id = isset($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : 0;
            $area_id = isset($_REQUEST['area_id']) ? $_REQUEST['area_id'] : 0;

            session([
                'product_area' => $area_id
            ]);

            /* 是否存在商品id */
            if (empty($goods_id)) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['cannot_found_goods']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            } else {
                $goods_id = intval($goods_id);
            }

            /* 取出商品信息 */
            $res = Goods::select('goods_sn', 'goods_name', 'goods_type', 'shop_price', 'model_attr');
            $res = $res->where('goods_id', $goods_id);
            $goods = $this->baseRepository->getToArrayFirst($res);

            if (empty($goods)) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            }

            $this->smarty->assign('sn', sprintf($GLOBALS['_LANG']['good_goods_sn'], $goods['goods_sn']));
            $this->smarty->assign('price', sprintf($GLOBALS['_LANG']['good_shop_price'], $goods['shop_price']));
            $this->smarty->assign('goods_name', sprintf($GLOBALS['_LANG']['products_title'], $goods['goods_name']));
            $this->smarty->assign('goods_sn', sprintf($GLOBALS['_LANG']['products_title_2'], $goods['goods_sn']));
            $this->smarty->assign('model_attr', $goods['model_attr']);

            $region_name = get_table_date('region_warehouse', "region_id = '$area_id'", ['region_name'], 2);
            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('area_id', $area_id);

            /* 获取商品规格列表 */
            $attribute = $this->goodsAreaAttrManageService->getGoodsSpecificationsList($goods_id);

            if (empty($attribute)) {
                $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['edit_goods']];
                return sys_msg($GLOBALS['_LANG']['not_exist_goods_attr'], 1, $link);
            }

            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }
            $attribute_count = count($_attribute);

            $this->smarty->assign('attribute_count', $attribute_count);
            $this->smarty->assign('attribute_count_3', ($attribute_count + 3));
            $this->smarty->assign('attribute', $_attribute);
            $this->smarty->assign('product_sn', $goods['goods_sn'] . '_');
            $this->smarty->assign('product_number', $GLOBALS['_CFG']['default_storage']);

            /* 取商品的货品 */
            $product = $this->goodsAreaAttrManageService->productAreaList($goods_id, $area_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
            $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
            $this->smarty->assign('product_list', $product['product']);
            $this->smarty->assign('product_null', empty($product['product']) ? 0 : 1);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('filter', $product['filter']);
            $this->smarty->assign('full_page', 1);


            $this->smarty->assign('product_php', 'goods_area_attr.php');
            $this->smarty->assign('batch_php', 'goods_produts_area_batch.php');

            /* 显示商品列表页面 */

            return $this->smarty->display('product_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 货品排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_query') {
            $area_id = isset($_REQUEST['area_id']) ? $_REQUEST['area_id'] : 0;

            /* 是否存在商品id */
            if (empty($_REQUEST['goods_id'])) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            } else {
                $goods_id = intval($_REQUEST['goods_id']);
            }

            /* 取出商品信息 */
            $res = Goods::select('goods_sn', 'goods_name', 'goods_type', 'shop_price');
            $res = $res->where('goods_id', $goods_id);
            $goods = $this->baseRepository->getToArrayFirst($res);

            if (empty($goods)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            }
            $this->smarty->assign('sn', sprintf($GLOBALS['_LANG']['good_goods_sn'], $goods['goods_sn']));
            $this->smarty->assign('price', sprintf($GLOBALS['_LANG']['good_shop_price'], $goods['shop_price']));
            $this->smarty->assign('goods_name', sprintf($GLOBALS['_LANG']['products_title'], $goods['goods_name']));
            $this->smarty->assign('goods_sn', sprintf($GLOBALS['_LANG']['products_title_2'], $goods['goods_sn']));

            $region_name = get_table_date('region_warehouse', "region_id = '$area_id'", ['region_name'], 2);
            $this->smarty->assign('region_name', $region_name);

            /* 获取商品规格列表 */
            $attribute = $this->goodsAreaAttrManageService->getGoodsSpecificationsList($goods_id);
            if (empty($attribute)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            }
            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }
            $attribute_count = count($_attribute);

            $this->smarty->assign('attribute_count', $attribute_count);
            $this->smarty->assign('attribute', $_attribute);
            $this->smarty->assign('attribute_count_3', ($attribute_count + 10));
            $this->smarty->assign('product_sn', $goods['goods_sn'] . '_');
            $this->smarty->assign('product_number', $GLOBALS['_CFG']['default_storage']);

            /* 取商品的货品 */
            $product = $this->goodsAreaAttrManageService->productAreaList($goods_id, $area_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
            $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
            $this->smarty->assign('product_list', $product['product']);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('filter', $product['filter']);
            $this->smarty->assign('product_php', 'goods_area_attr.php');

            /* 排序标记 */
            $sort_flag = sort_flag($product['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('product_info.htm'),
                '',
                ['filter' => $product['filter'], 'page_count' => $product['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 修改货品号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $product_sn = json_str_iconv(trim($_POST['val']));
            $product_sn = ($GLOBALS['_LANG']['n_a'] == $product_sn) ? '' : $product_sn;

            if ($this->goodsAreaAttrManageService->checkProductAreaSnExist($product_sn, $product_id, $adminru['ru_id'])) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_product_sn']);
            }

            /* 修改 */
            $data = ['product_sn' => $product_sn];
            $res = ProductsArea::where('product_id', $product_id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result($product_sn);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改条形码
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_bar_code') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $bar_code = json_str_iconv(trim($_POST['val']));

            if ($this->goodsAreaAttrManageService->checkProductAreaSnExist($bar_code, $product_id, $adminru['ru_id'], 1)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_bar_code']);
            }

            /* 修改 */
            $data = ['bar_code' => $bar_code];
            $res = ProductsArea::where('product_id', $product_id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result($bar_code);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_number') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_POST['id']);
            $product_number = intval($_POST['val']);

            /* 货品库存 */
            $selectArray = ['product_number', 'goods_id', 'area_id'];
            $product = $this->goodsAreaAttrManageService->getProductAreaInfo($product_id, $selectArray);

            if ($product['product_number'] != $product_number) {
                if ($product['product_number'] > $product_number) {
                    $number = $product['product_number'] - $product_number;
                    $number = "- " . $number;
                    $log_use_storage = 10;
                } else {
                    $number = $product_number - $product['product_number'];
                    $number = "+ " . $number;
                    $log_use_storage = 11;
                }

                $goods = get_admin_goods_info($product['goods_id']);

                //库存日志
                $logs_other = [
                    'goods_id' => $product['goods_id'],
                    'order_id' => 0,
                    'use_storage' => $log_use_storage,
                    'admin_id' => session('admin_id'),
                    'number' => $number,
                    'model_inventory' => $goods['model_inventory'],
                    'model_attr' => $goods['model_attr'],
                    'product_id' => $product_id,
                    'warehouse_id' => 0,
                    'area_id' => $product['area_id'],
                    'add_time' => gmtime()
                ];

                GoodsInventoryLogs::insert($logs_other);
            }

            /* 修改货品库存 */
            $data = ['product_number' => $product_number];
            $res = ProductsArea::where('product_id', $product_id)->update($data);

            if ($res > 0) {
                clear_cache_files();
                return make_json_result($product_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 货品删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('remove_back');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);

            /* 是否存在商品id */
            if (empty($product_id)) {
                return make_json_error($GLOBALS['_LANG']['product_id_null']);
            } else {
                $product_id = intval($product_id);
            }

            /* 货品库存 */
            $selectArray = ['product_number', 'goods_id'];
            $product = $this->goodsAreaAttrManageService->getProductAreaInfo($product_id, $selectArray);

            /* 删除货品 */
            $res = ProductsArea::where('product_id', $product_id)->delete();
            if ($res > 0) {
                $url = 'goods_warehouse_attr.php?act=product_query&warehouse_id=' . session('product_area') . '&' . str_replace('act=product_remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 货品添加 执行
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_add_execute') {
            admin_priv('goods_manage');

            $product['goods_id'] = intval($_POST['goods_id']);
            $product['attr'] = $_POST['attr'];
            $product['product_sn'] = $_POST['product_sn'];
            $product['bar_code'] = $_POST['bar_code'];
            $product['product_number'] = $_POST['product_number'];
            $product['area_id'] = $_POST['area_id'];

            /* 是否存在商品id */
            if (empty($product['goods_id'])) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, [], false);
            }

            /* 判断是否为初次添加 */
            $insert = true;
            if ($this->goodsAreaAttrManageService->productWarehouseNumberCount($product['goods_id'], '', $product['area_id']) > 0) {
                $insert = false;
            }

            /* 取出商品信息 */
            $res = Goods::select('goods_sn', 'goods_name', 'goods_type', 'shop_price', 'model_inventory', 'model_attr');
            $res = $res->where('goods_id', $product['goods_id']);
            $goods = $this->baseRepository->getToArrayFirst($res);

            if (empty($goods)) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, [], false);
            }

            /*  */
            foreach ($product['product_sn'] as $key => $value) {
                //过滤
                $product['product_number'][$key] = empty($product['product_number'][$key]) ? (empty($GLOBALS['_CFG']['use_storage']) ? 0 : $GLOBALS['_CFG']['default_storage']) : trim($product['product_number'][$key]); //库存

                //获取规格在商品属性表中的id
                foreach ($product['attr'] as $attr_key => $attr_value) {
                    /* 检测：如果当前所添加的货品规格存在空值或0 */
                    if (empty($attr_value[$key])) {
                        continue 2;
                    }

                    $is_spec_list[$attr_key] = 'true';

                    $value_price_list[$attr_key] = $attr_value[$key] . chr(9) . ''; //$key，当前

                    $id_list[$attr_key] = $attr_key;
                }
                $goods_attr_id = $this->goodsAreaAttrManageService->handleGoodsAttr($product['goods_id'], $id_list, $is_spec_list, $value_price_list);

                /* 是否为重复规格的货品 */
                $goods_attr = $this->goodsAttrService->sortGoodsAttrIdArray($goods_attr_id);
                $goods_attr = implode('|', $goods_attr['sort']);
                if ($this->goodsAreaAttrManageService->checkGoodsAttrExist($goods_attr, $product['goods_id'], 0, $product['area_id'])) {
                    continue;
                }

                //货品号不为空
                if (!empty($value)) {
                    /* 检测：货品货号是否在商品表和货品表中重复 */
                    if ($this->goodsAreaAttrManageService->checkGoodsSnExist($value)) {
                        continue;
                    }
                    if ($this->goodsAreaAttrManageService->checkProductSnExist($value)) {
                        continue;
                    }
                }

                /* 插入货品表 */
                $data = [
                    'goods_id' => $product['goods_id'],
                    'goods_attr' => $goods_attr,
                    'product_sn' => $value,
                    'bar_code' => $product['bar_code'][$key],
                    'product_number' => $product['product_number'][$key],
                    'area_id' => $product['area_id']
                ];
                $insert_id = ProductsArea::insertGetId($data);

                if ($insert_id < 1) {
                    continue;
                }

                //库存日志
                $number = "+ " . $product['product_number'][$key];

                if ($product['product_number'][$key]) {
                    $logs_other = [
                        'goods_id' => $product['goods_id'],
                        'order_id' => 0,
                        'use_storage' => 9,
                        'admin_id' => session('admin_id'),
                        'number' => $number,
                        'model_inventory' => $goods['model_inventory'],
                        'model_attr' => $goods['model_attr'],
                        'product_id' => $insert_id,
                        'warehouse_id' => 0,
                        'area_id' => $product['area_id'],
                        'add_time' => gmtime()
                    ];

                    GoodsInventoryLogs::insert($logs_other);
                }

                //货品号为空 自动补货品号
                if (empty($value)) {
                    $data = ['product_sn' => $goods['goods_sn'] . "g_p" . $insert_id];
                    ProductsArea::where('product_id', $insert_id)->update($data);
                }

                /* 修改商品表库存 */
                $product_count = $this->goodsAreaAttrManageService->productWarehouseNumberCount($product['goods_id'], '', $product['area_id']);
                if ($this->goodsAreaAttrManageService->updateWarehouseGoods($product['goods_id'], 'goods_number', $product_count)) {
                    //记录日志
                    admin_log($product['goods_id'], 'update', 'goods');
                }
            }

            clear_cache_files();

            /* 返回 */
            if ($insert) {
                $link[] = ['href' => 'goods.php?act=add', 'text' => $GLOBALS['_LANG']['02_goods_add']];
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                $link[] = ['href' => 'goods_area_attr.php?act=product_list&goods_id=' . $product['goods_id'] . '&area_id=' . $product['area_id'], 'text' => $GLOBALS['_LANG']['18_product_list']];
            } else {
                $link[] = ['href' => 'goods.php?act=list&uselastfilter=1', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $product['goods_id'], 'text' => $GLOBALS['_LANG']['edit_goods']];
                $link[] = ['href' => 'goods_area_attr.php?act=product_list&goods_id=' . $product['goods_id'] . '&area_id=' . $product['area_id'], 'text' => $GLOBALS['_LANG']['18_product_list']];
            }
            return sys_msg($GLOBALS['_LANG']['save_products'], 0, $link);
        }
    }
}
