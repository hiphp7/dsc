<?php

return [
    /*
    |--------------------------------------------------------------------------
    | agency Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during agency for various
    | messages. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    /* 菜单 */
    'add_agency' => '添加办事处',
    'edit_agency' => '编辑办事处',
    'agency_list' => '办事处列表',

    /* 列表页 */
    'agency_name' => '办事处名称',
    'agency_desc' => '办事处描述',

    'agency_name_exist' => '该办事处名称已存在，请您换一个名称',
    'agency_edit_fail' => '编辑办事处名称时出错，请您再试一次',


    'no_record_selected' => '没有选择记录',
    'batch_drop_ok' => '批量删除成功',
    'add_region_btn' => '添加地区',

    /* 详情页 */
    'label_agency_name' => '办事处名称：',
    'label_agency_desc' => '办事处描述：',
    'label_admins' => '负责该办事处的管理员：',
    'notice_admins' => '用星号(*)标注的管理员表示已经负责其他的办事处了',
    'label_regions' => '该办事处管辖的地区：',
    'add_region' => '从下面的列表中选择地区，点加号按钮添加到该办事处管辖的地区',
    'label_country' => '一级地区：',
    'label_province' => '二级地区：',
    'label_city' => '三级地区：',
    'label_district' => '四级地区：',

    'no_regions' => '没有设置地区',
    'add_agency_ok' => '添加办事处成功',
    'edit_agency_ok' => '编辑办事处成功',
    'continue_add_agency' => '继续添加新的办事处',
    'back_agency_list' => '返回办事处列表',

    'js_languages' => [
        'batch_drop_confirm' => '您确实要删除选中的办事处吗？',
        'region_exists' => '该地区已存在',
        'no_agencyname' => '没有填办事处名称',
    ],

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        'list' => [
            '0' => '该页面展示了所有办事处的信息。',
            '1' => '可删除、编辑办事处。',
        ],
        'info' => [
            '0' => '办事处隶属于管理员下级的角色，请注意勾选管理员。',
            '1' => '请注意添加办事处地区。',
        ]
    ],

];