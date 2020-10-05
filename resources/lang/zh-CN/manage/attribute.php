<?php

return [

    /*
    |--------------------------------------------------------------------------
    | attribute Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during attribute for various
    | messages. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    /* 列表 */
    'set_gcolor' => '设置属性颜色',

    'by_goods_type' => '按商品类型显示',
    'all_goods_type' => '所有商品类型',

    'attr_id' => '编号',
    'cat_id' => '商品类型',
    'attr_name' => '属性名称',
    'attr_input_type' => '属性值的录入方式',
    'attr_values' => '可选值列表',
    'attr_type' => '购买商品时是否需要选择该属性的值',

    'value_attr_input_type' => [
        ATTR_TEXT => '手工录入',
        ATTR_OPTIONAL => '从列表中选择',
        ATTR_TEXTAREA => '多行文本框',
    ],

    'drop_confirm' => '您确实要删除该属性吗？',

    /* 添加/编辑 */
    'label_attr_name' => '属性名称：',
    'label_cat_id' => '所属商品类型：',
    'label_attr_index' => '能否进行检索：',
    'label_is_linked' => '相同属性值的商品是否关联：',
    'no_index' => '不需要检索',
    'keywords_index' => '关键字检索',
    'range_index' => '范围检索',
    'note_attr_index' => '不需要该属性成为检索商品条件的情况请选择不需要检索，需要该属性进行关键字检索商品时选择关键字检索，如果该属性检索时希望是指定某个范围时，选择范围检索。',
    'label_attr_input_type' => '该属性值的录入方式：',
    'text' => '手工录入',
    'select' => '从下面的列表中选择（一行代表一个可选值）',
    'text_area' => '多行文本框',
    'label_attr_values' => '可选值列表：',
    'label_attr_group' => '属性分组：',
    'label_attr_type' => '属性是否可选',
    'note_attr_type' => '选择"单选/复选属性"时，可以对商品该属性设置多个值，同时还能对不同属性值指定不同的价格加价，用户购买商品时需要选定具体的属性值。选择"唯一属性"时，商品的该属性值只能设置一个值，用户只能查看该值。',

    'attr_type_values' => [
        '0' => '唯一属性',
        '1' => '单选属性',
        '2' => '复选属性',
    ],

    'add_next' => '添加下一个属性',
    'back_list' => '返回属性列表',
    'edit_attr' => '返回编辑属性',

    'add_ok' => '添加属性 [%s] 成功。',
    'edit_ok' => '编辑属性 [%s] 成功。',

    /* 提示信息 */
    'name_exist' => '该属性名称已存在，请您换一个名称。',
    'drop_confirm' => '您确实要删除该属性吗？',
    'notice_drop_confirm' => '已经有%s个商品使用该属性，您确实要删除该属性吗？',
    'name_not_null' => '属性名称不能为空。',
    'select_color' => '请选择颜色',
    'attr_color_edit_success' => '属性颜色修改成功',
    'edit_attr_img' => '编辑属性图片',
    'back_attr' => '返回该属性',
    'edit_success' => '编辑成功',

    'no_select_arrt' => '您没有选择需要删除的属性名',
    'drop_ok' => '成功删除了 %d 条商品属性',

    'js_languages' => [
        'name_not_null' => '请您输入属性名称。',
        'values_not_null' => '请您输入该属性的可选值。',
        'cat_id_not_null' => '请您选择该属性所属的商品类型。',
        'js_name_not_null' => '属性名称不能为空',
    ],

    'wai_href' => '外链地址',
    'add_attribute_img' => '添加属性图片',
    'add_attribute_color' => '设置属性颜色',

    'category_style' => '分类筛选样式',
    'category_style_one' => '普通',
    'category_style_color' => '颜色',

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        '0' => '展示了一个商品类型下的商品属性列表。',
        '1' => '可新增商品属性。',
        '2' => '请按提示文案正确填写信息。',
        'color' => [
            '0' => '填写颜色信息',
        ]
    ],
    // 商家后台
    'label_attr_filter_style' => '分类筛选样式：',

];