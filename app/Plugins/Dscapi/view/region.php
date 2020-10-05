<?php

/* 获取传值 */
$region_id = isset($_REQUEST['region_id']) ? $base->get_intval($_REQUEST['region_id']) : -1;                  //地区ID
$parent_id = isset($_REQUEST['parent_id']) ? $base->get_intval($_REQUEST['parent_id']) : -1;                  //地区父级ID
$region_name = isset($_REQUEST['region_name']) ? $base->get_addslashes($_REQUEST['region_name']) : -1;        //地区名称
$region_type = isset($_REQUEST['region_type']) ? $base->get_intval($_REQUEST['region_type']) : -1;            //地区层级val

$val = array(
    'region_id' => $region_id,
    'parent_id' => $parent_id,
    'region_name' => $region_name,
    'region_type' => $region_type,
    'region_select' => $data,
    'page_size' => $page_size,
    'page' => $page,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'format' => $format
);

/* 初始化商品类 */
$region = new \App\Plugins\Dscapi\app\controller\region($val);

switch ($method) {

    /**
     * 获取地区列表
     */
    case 'dsc.region.list.get':

        $table = array(
            'region' => 'region'
        );

        $result = $region->get_region_list($table);

        die($result);
        break;

    /**
     * 获取单条地区信息
     */
    case 'dsc.region.info.get':

        $table = array(
            'region' => 'region'
        );

        $result = $region->get_region_info($table);

        die($result);
        break;

    /**
     * 插入地区信息
     */
    case 'dsc.region.insert.post':

        $table = array(
            'region' => 'region'
        );

        $result = $region->get_region_insert($table);

        die($result);
        break;

    /**
     * 更新地区信息
     */
    case 'dsc.region.update.post':

        $table = array(
            'region' => 'region'
        );

        $result = $region->get_region_update($table);

        die($result);
        break;

    /**
     * 删除地区信息
     */
    case 'dsc.region.del.get':

        $table = array(
            'region' => 'region'
        );

        $result = $region->get_region_delete($table);

        die($result);
        break;

    default:

        echo "非法接口连接";
        break;
}
