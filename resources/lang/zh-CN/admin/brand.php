<?php

return [

    /*
    |--------------------------------------------------------------------------
    | brand Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during brand for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'brand_separate' => '统一使用平台品牌ID',

    'brand' => '品牌',
    'brand_name' => '品牌名称',
    'brand_img' => '品牌图片',
    'site_url' => '品牌网址',
    'brand_desc' => '品牌描述',
    'brand_logo' => '品牌LOGO',
    'index_img' => '品牌专区大图',
    'brand_bg' => '品牌背景图',
    'sort_order' => '排序',
    'is_show' => '是否显示',
    'drop_brand_logo' => '删除图标',
    'confirm_drop_logo' => '你确认要删除该图标吗？',
    'drop_brand_logo_success' => '删除品牌logo成功',
    'lab_intro' => '加入推荐',
    'brand_first_char' => '品牌首字母',
    'generate_brand_first_char' => '生成品牌首字母',

    'brand_edit_lnk' => '重新编辑该品牌',
    'brand_list_lnk' => '返回列表页面',
    'update_data_success' => '已更新数据成功',
    'update_data_fail' => '已更新数据失败',

    /*帮助信息*/
    'up_brandlogo' => '请上传图片，做为品牌的LOGO！标准尺寸200*88',
    'warn_brandlogo' => '你已经上传过图片。再次上传时将覆盖原图片！标准尺寸200*88',
    'brand_first_char_desc' => '用于解决某些生僻字无法正确生成品牌首字母的情况。',
    'index_img_desc' => '标准尺寸278*285',
    'brand_bg_desc' => '使用于品牌详情页头部分类背景，建议尺寸：1920*200',

    /*提示信息*/
    'brand_edit' => '编辑品牌记录',
    'upload_failure' => '图片上传失败！',
    'brandedit_fail' => '品牌 %s 修改失败！',
    'brandadd_succed' => '新品牌添加成功！',
    'brandedit_succed' => '品牌 %s 修改成功！',
    'brandname_exist' => '品牌 %s 已经存在！',
    'drop_confirm' => '你确认要删除选定的商品品牌吗？',
    'drop_succeed' => '已成功删除！',
    'drop_fail' => '删除失败！',
    'update_type' => '更新状态',

    'no_brandname' => '您必须输入品牌名称！',
    'enter_int' => '请输入一个整数！',

    'back_list' => '返回品牌列表',
    'continue_add' => '继续添加新品牌',

    'upfile_type_error' => '只能上传jpg，gif，png类型的图片',
    'upfile_error' => '图片无法上传，请确保data目录下所有子目录的可写性！',

    'visibility_notes' => '当品牌下还没有商品的时候，首页及分类页的品牌区将不会显示该品牌。',

    'current_modification_data' => '当前需要修改的数据有',

    'current_update_number' => '当前更新商品数量',

    /*JS 语言项*/
    'js_languages' => [
        'no_brandname' => '您必须输入品牌名称！',
        'no_brandletter' => '您必须输入品牌英文名称！',
        'require_num' => '排序序号必须是一个数字',
        'title_name_one' => '已完成更新，请关闭该窗口！',
        'title_name_two' => '正在生成品牌首字母中，请勿关闭该窗口！',
        'brand_zh_name_null' => '请输入中文品牌名称',
        'brand_logo_null' => '请上传品牌logo',
    ],

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        'create_brand_letter' => [
            '0' => '生成品牌的首字母，主要用于后台的品牌搜索按首字母查询',
        ],
        'list' => [
            '0' => '展示了商城自营品牌的相关信息。',
            '1' => '可以通过品牌关键字搜索相关品牌信息。',
        ],
        'info' => [
            '0' => '请按提示文案填写信息，以免出错。',
        ],
        'separate' => [
            '0' => '操作商家商品品牌统一使用平台品牌ID，请耐心等待数据执行完毕。',
        ]
    ]

];
