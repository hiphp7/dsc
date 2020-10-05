<?php

/* 获取传值 */
$product_id = isset($_REQUEST['product_id']) ? $base->get_intval($_REQUEST['product_id']) : -1;         //货品ID
$goods_id = isset($_REQUEST['goods_id']) ? $base->get_intval($_REQUEST['goods_id']) : -1;               //商品ID
$product_sn = isset($_REQUEST['product_sn']) ? $base->get_addslashes($_REQUEST['product_sn']) : -1;     //货品编码
$bar_code = isset($_REQUEST['bar_code']) ? $base->get_intval($_REQUEST['bar_code']) : -1;               //条形码
$warehouse_id = isset($_REQUEST['warehouse_id']) ? $base->get_intval($_REQUEST['warehouse_id']) : -1;   //仓库ID
$area_id = isset($_REQUEST['area_id']) ? $base->get_intval($_REQUEST['area_id']) : -1;                  //地区ID

$val = array(
    'product_id' => $product_id,
    'goods_id' => $goods_id,
    'product_sn' => $product_sn,
    'bar_code' => $bar_code,
    'warehouse_id' => $warehouse_id,
    'area_id' => $area_id,
    'product_select' => $data,
    'page_size' => $page_size,
    'page' => $page,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'format' => $format
);

/* 初始化商品类 */
$product = new \App\Plugins\Dscapi\app\controller\product($val);

switch ($method) {

    /**
     * 获取商品货品列表
     */
    case 'dsc.product.list.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products'
        );

        $result = $product->get_product_list($table);

        die($result);
        break;

    /**
     * 获取单条商品货品信息
     */
    case 'dsc.product.info.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products'
        );

        $result = $product->get_product_info($table);

        die($result);
        break;

    /**
     * 插入商品货品信息
     */
    case 'dsc.product.insert.post':

        $table = array(
            'goods' => 'goods',
            'product' => 'products'
        );

        $result = $product->get_product_insert($table);

        die($result);
        break;

    /**
     * 更新商品货品信息
     */
    case 'dsc.product.update.post':

        $table = array(
            'goods' => 'goods',
            'product' => 'products'
        );

        $result = $product->get_product_update($table);

        die($result);
        break;

    /**
     * 删除商品货品信息
     */
    case 'dsc.product.del.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products'
        );

        $result = $product->get_product_delete($table);

        die($result);
        break;

    /**
     * 获取商品仓库货品列表
     */
    case 'dsc.product.warehouse.list.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_warehouse'
        );

        $result = $product->get_product_list($table);

        die($result);
        break;

    /**
     * 获取单条商品仓库货品信息
     */
    case 'dsc.product.warehouse.info.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_warehouse'
        );

        $result = $product->get_product_info($table);

        die($result);
        break;

    /**
     * 插入商品仓库货品信息
     */
    case 'dsc.product.warehouse.insert.post':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_warehouse'
        );

        $result = $product->get_product_insert($table);

        die($result);
        break;

    /**
     * 更新商品仓库货品信息
     */
    case 'dsc.product.warehouse.update.post':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_warehouse'
        );

        $result = $product->get_product_update($table);

        die($result);
        break;

    /**
     * 删除商品仓库货品信息
     */
    case 'dsc.product.warehouse.del.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_warehouse'
        );

        $result = $product->get_product_delete($table);

        die($result);
        break;

    /**
     * 获取商品仓库货品列表
     */
    case 'dsc.product.area.list.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_area'
        );

        $result = $product->get_product_list($table);

        die($result);
        break;

    /**
     * 获取单条商品仓库货品信息
     */
    case 'dsc.product.area.info.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_area'
        );

        $result = $product->get_product_info($table);

        die($result);
        break;

    /**
     * 插入商品仓库货品信息
     */
    case 'dsc.product.area.insert.post':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_area'
        );

        $result = $product->get_product_insert($table);

        die($result);
        break;

    /**
     * 更新商品仓库货品信息
     */
    case 'dsc.product.area.update.post':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_area'
        );

        $result = $product->get_product_update($table);

        die($result);
        break;

    /**
     * 删除商品仓库货品信息
     */
    case 'dsc.product.area.del.get':

        $table = array(
            'goods' => 'goods',
            'product' => 'products_area'
        );

        $result = $product->get_product_delete($table);

        die($result);
        break;

    default:

        echo "非法接口连接";
        break;
}
