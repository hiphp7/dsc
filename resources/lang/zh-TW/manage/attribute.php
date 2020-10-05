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
    'set_gcolor' => '設置屬性顏色',

    'by_goods_type' => '按商品類型顯示',
    'all_goods_type' => '所有商品類型',

    'attr_id' => '編號',
    'cat_id' => '商品類型',
    'attr_name' => '屬性名稱',
    'attr_input_type' => '屬性值的錄入方式',
    'attr_values' => '可選值列表',
    'attr_type' => '購買商品時是否需要選擇該屬性的值',

    'value_attr_input_type' => [
        ATTR_TEXT => '手工錄入',
        ATTR_OPTIONAL => '從列表中選擇',
        ATTR_TEXTAREA => '多行文本框',
    ],

    'drop_confirm' => '您確實要刪除該屬性嗎？',

    /* 添加/編輯 */
    'label_attr_name' => '屬性名稱：',
    'label_cat_id' => '所屬商品類型：',
    'label_attr_index' => '能否進行檢索：',
    'label_is_linked' => '相同屬性值的商品是否關聯：',
    'no_index' => '不需要檢索',
    'keywords_index' => '關鍵字檢索',
    'range_index' => '範圍檢索',
    'note_attr_index' => '不需要該屬性成為檢索商品條件的情況請選擇不需要檢索，需要該屬性進行關鍵字檢索商品時選擇關鍵字檢索，如果該屬性檢索時希望是指定某個範圍時，選擇範圍檢索。',
    'label_attr_input_type' => '該屬性值的錄入方式：',
    'text' => '手工錄入',
    'select' => '從下面的列表中選擇（一行代表一個可選值）',
    'text_area' => '多行文本框',
    'label_attr_values' => '可選值列表：',
    'label_attr_group' => '屬性分組：',
    'label_attr_type' => '屬性是否可選',
    'note_attr_type' => '選擇"單選/複選屬性"時，可以對商品該屬性設置多個值，同時還能對不同屬性值指定不同的價格加價，用戶購買商品時需要選定具體的屬性值。選擇"唯一屬性"時，商品的該屬性值只能設置一個值，用戶只能查看該值。',

    'attr_type_values' => [
        '0' => '唯一屬性',
        '1' => '單選屬性',
        '2' => '複選屬性',
    ],

    'add_next' => '添加下一個屬性',
    'back_list' => '返回屬性列表',
    'edit_attr' => '返回編輯屬性',

    'add_ok' => '添加屬性 [%s] 成功。',
    'edit_ok' => '編輯屬性 [%s] 成功。',

    /* 提示信息 */
    'name_exist' => '該屬性名稱已存在，請您換一個名稱。',
    'drop_confirm' => '您確實要刪除該屬性嗎？',
    'notice_drop_confirm' => '已經有%s個商品使用該屬性，您確實要刪除該屬性嗎？',
    'name_not_null' => '屬性名稱不能為空。',
    'select_color' => '請選擇顏色',
    'attr_color_edit_success' => '屬性顏色修改成功',
    'edit_attr_img' => '編輯屬性圖片',
    'back_attr' => '返回該屬性',
    'edit_success' => '編輯成功',

    'no_select_arrt' => '您沒有選擇需要刪除的屬性名',
    'drop_ok' => '成功刪除了 %d 條商品屬性',

    'js_languages' => [
        'name_not_null' => '請您輸入屬性名稱。',
        'values_not_null' => '請您輸入該屬性的可選值。',
        'cat_id_not_null' => '請您選擇該屬性所屬的商品類型。',
        'js_name_not_null' => '屬性名稱不能為空',
    ],

    'wai_href' => '外鏈地址',
    'add_attribute_img' => '添加屬性圖片',
    'add_attribute_color' => '設置屬性顏色',

    'category_style' => '分類篩選樣式',
    'category_style_one' => '普通',
    'category_style_color' => '顏色',

    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        '0' => '展示了一個商品類型下的商品屬性列表。',
        '1' => '可新增商品屬性。',
        '2' => '請按提示文案正確填寫信息。',
        'color' => [
            '0' => '填寫顏色信息',
        ]
    ],
    // 商家後台
    'label_attr_filter_style' => '分類篩選樣式：',

];