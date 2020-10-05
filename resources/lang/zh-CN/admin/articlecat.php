<?php
return [

    'cat_name' => '文章分类名称',
    'type' => '分类类型',
    'type_name' => [
        COMMON_CAT => '普通分类',
        SYSTEM_CAT => '系统分类',
        INFO_CAT => '网店信息',
        UPHELP_CAT => '帮助分类',
        HELP_CAT => '网店帮助',
    ],


    'cat_keywords' => '关键字',
    'cat_desc' => '描述',
    'parent_cat' => '上级分类',
    'cat_top' => '顶级分类',
    'not_allow_add' => '你所选分类不允许添加子分类',
    'not_allow_remove' => '系统保留分类，不允许删除',
    'is_fullcat' => '该分类下还有子分类，请先删除其子分类',
    'show_in_nav' => '显示在导航栏',

    'isopen' => '显示',
    'isclose' => '不显示',
    'add_article' => '添加文章',

    'articlecat_edit' => '文章分类编辑',
    'cat_grade' => [
        '1' => '一级分类',
        '2' => '二级分类',
        '3' => '三级分类',
        '4' => '四级分类',
        '5' => '五级分类',
    ],

    /* 提示信息 */
    'catname_exist' => '分类名 %s 已经存在',
    'parent_id_err' => '分类名 %s 的父分类不能设置成本身或本身的子分类',
    'back_list' => '返回分类列表',
    'continue_add' => '继续添加新分类',
    'catadd_succed' => '已成功添加',
    'catedit_succed' => '分类 %s 编辑成功',
    'back_list' => '返回分类列表',
    'continue_add' => '继续添加分类',
    'no_catname' => '请填入分类名',
    'edit_fail' => '编辑失败',
    'enter_int' => '请输入一个整数',
    'not_emptycat' => '分类下还有文章，不允许删除非空分类',
    'has_article_on_this_cat' => '该分类下存在文章，无法添加子分类',

    /*帮助信息*/
    'notice_keywords' => '关键字为选填项，其目的在于方便外部搜索引擎搜索',
    'notice_isopen' => '该文章分类是否显示在前台的主导航栏中。',

    /*JS 语言项*/
    'js_languages' => [
        'no_catname' => '没有输入分类的名称',
        'sys_hold' => '系统保留分类，不允许添加子分类',
        'remove_confirm' => '您确定要删除选定的分类吗？',
    ],


    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        '0' => '该页面展示所有文章分类。',
        '1' => '可添加子分类以及编辑修改、删除分类。',
        '2' => '请注意选择上级分类。',
    ],


];