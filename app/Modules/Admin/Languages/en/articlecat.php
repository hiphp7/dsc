<?php

$_LANG = array(
    'cat_name' => 'Article category name',
    'type' => 'Classification type',
    'type_name' =>
        array(
            1 => 'General classification',
            2 => 'Classification system',
            3 => 'Online information',
            4 => 'Help classification',
            5 => 'Online help',
        ),
    'cat_keywords' => 'The keyword',
    'cat_desc' => 'describe',
    'parent_cat' => 'The higher classification',
    'cat_top' => 'Top classification',
    'not_allow_add' => 'Subcategories are not allowed in the category you selected',
    'not_allow_remove' => 'The system retains categories and does not allow deletion',
    'is_fullcat' => 'There are subcategories under this category. Please delete the subcategories first',
    'show_in_nav' => 'Display in the navigation bar',
    'isopen' => 'According to',
    'isclose' => 'Don\'t show',
    'add_article' => 'Add the article',
    'articlecat_edit' => 'Article classification editor',
    'cat_grade' =>
        array(
            1 => 'The primary classification',
            2 => 'The secondary classification',
            3 => 'Level 3 classification',
            4 => 'Level 4 categories',
            5 => 'Five categories',
        ),
    'catname_exist' => 'The category name %s already exists',
    'parent_id_err' => 'The parent category of the category name %s cannot be set to itself or its child category',
    'back_list' => 'Return category list',
    'continue_add' => 'Go ahead and add categories',
    'catadd_succed' => 'Added successfully',
    'catedit_succed' => 'Category %s edited successfully',
    'no_catname' => 'Please fill in the category name',
    'edit_fail' => 'Edit failure',
    'enter_int' => 'Please enter an integer',
    'not_emptycat' => 'Category there are articles, not allowed to delete non - empty category',
    'has_article_on_this_cat' => 'There are articles under this category. Subcategories cannot be added',
    'notice_keywords' => 'Keywords for the optional field, its purpose is to facilitate external search engine search',
    'notice_isopen' => 'Whether the article category is displayed in the main navigation bar in the foreground.',
    'js_languages' =>
        array(
            'no_catname' => 'No category name was entered',
            'sys_hold' => 'The system retains categories and does not allow subcategories to be added',
            'remove_confirm' => 'Are you sure you want to delete the selected categories?',
        ),
    'operation_prompt_content' =>
        array(
            0 => 'This page shows all categories of articles.',
            1 => 'Subcategories can be added as well as edited, modified, or deleted.',
            2 => 'Please select the superior category carefully.',
        ),
);


return $_LANG;
