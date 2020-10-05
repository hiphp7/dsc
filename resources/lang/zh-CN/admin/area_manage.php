<?php
return [
    /*
    |--------------------------------------------------------------------------
    | area_manage Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during area_manage for various
    | messages. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'create_region_initial' => '生成地区首字母',

    /* 字段信息 */
    'region_id' => '地区编号',
    'region_name' => '地区名称',
    'region_type' => '地区类型',
    'region_hierarchy' => '所在层级',
    'region_belonged' => '所属地区',

    'city_region_id' => '市级地区ID',
    'city_region_name' => '市级地区名称',
    'city_region_initial' => '首字母',

    'area' => '地区',
    'area_next' => '以下',
    'country' => '一级地区',
    'province' => '二级地区',
    'city' => '三级地区',
    'cantonal' => '四级地区',
    'street' => '五级地区',
    'back_page' => '返回上一级',
    'manage_area' => '管理',
    'region_name_empty' => '区域名称不能为空！',
    'add_country' => '新增一级地区',
    'add_province' => '新增二级地区',
    'add_city' => '增加三级地区',
    'add_cantonal' => '增加四级地区',
    'restore_default_set' => '恢复默认设置',
    'region_name_placeholder' => '请输入地区名称',
    'add_region' => '新增地区',
    'confirm_set' => '你确定要恢复默认设置吗？',

    /* JS语言项 */
    'js_languages' => [
        'region_name_empty' => '您必须输入地区的名称!',
        'option_name_empty' => '必须输入调查选项名称!',
        'drop_confirm' => '您确定要删除这条记录吗?',
        'drop' => '删除',
        'country' => '一级地区',
        'province' => '二级地区',
        'city' => '三级地区',
        'cantonal' => '四级地区',
    ],

    /* 提示信息 */
    'add_area_error' => '添加新地区失败!',
    'region_name_exist' => '已经有相同的地区名称存在!',
    'parent_id_exist' => '该区域下有其它下级地区存在, 不能删除!',
    'form_notic' => '点击查看下级地区',
    'area_drop_confirm' => '如果订单或用户默认配送方式中使用以下地区，这些地区信息将显示为空。您确认要删除这条记录吗?',

    /* 恢复默认地区 */
    'restore_region' => '恢复默认地区',
    'restore_success' => '恢复默认地区成功',
    'restore_failure' => '恢复默认地区失败',

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        'initial' => [
            '0' => '地区首字母是所有二级市区生成的字母。',
            '1' => '把每个城市按首字母归类，便于前台查找;注意生成地区首字母是城市不会出现县级。',
        ],
        'list' => [
            '0' => '在新增一级地区点击管理进入下一级地区，可进行删除和编辑。',
            '1' => '地区用于商城定位，请根据商城实际情况谨慎设置。',
            '2' => '生成地区首字母是方便根据地区首字母搜索相对应的地区。',
            '3' => '地区层级关系必须为中国→省/直辖市→市→县，地区暂只支持到四级地区其后不显示，暂不支持国外。',
        ],
    ],


];