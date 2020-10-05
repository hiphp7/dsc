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

    'create_region_initial' => '生成地區首字母',

    /* 欄位信息 */
    'region_id' => '地區編號',
    'region_name' => '地區名稱',
    'region_type' => '地區類型',
    'region_hierarchy' => '所在層級',
    'region_belonged' => '所屬地區',

    'city_region_id' => '市級地區ID',
    'city_region_name' => '市級地區名稱',
    'city_region_initial' => '首字母',

    'area' => '地區',
    'area_next' => '以下',
    'country' => '一級地區',
    'province' => '二級地區',
    'city' => '三級地區',
    'cantonal' => '四級地區',
    'street' => '五級地區',
    'back_page' => '返回上一級',
    'manage_area' => '管理',
    'region_name_empty' => '區域名稱不能為空！',
    'add_country' => '新增一級地區',
    'add_province' => '新增二級地區',
    'add_city' => '增加三級地區',
    'add_cantonal' => '增加四級地區',
    'restore_default_set' => '恢復默認設置',
    'region_name_placeholder' => '請輸入地區名稱',
    'add_region' => '新增地區',
    'confirm_set' => '你確定要恢復默認設置嗎？',

    /* JS語言項 */
    'js_languages' => [
        'region_name_empty' => '您必須輸入地區的名稱!',
        'option_name_empty' => '必須輸入調查選項名稱!',
        'drop_confirm' => '您確定要刪除這條記錄嗎?',
        'drop' => '刪除',
        'country' => '一級地區',
        'province' => '二級地區',
        'city' => '三級地區',
        'cantonal' => '四級地區',
    ],

    /* 提示信息 */
    'add_area_error' => '添加新地區失敗!',
    'region_name_exist' => '已經有相同的地區名稱存在!',
    'parent_id_exist' => '該區域下有其它下級地區存在, 不能刪除!',
    'form_notic' => '點擊查看下級地區',
    'area_drop_confirm' => '如果訂單或用戶默認配送方式中使用以下地區，這些地區信息將顯示為空。您確認要刪除這條記錄嗎?',

    /* 恢復默認地區 */
    'restore_region' => '恢復默認地區',
    'restore_success' => '恢復默認地區成功',
    'restore_failure' => '恢復默認地區失敗',

    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        'initial' => [
            '0' => '地區首字母是所有二級市區生成的字母。',
            '1' => '把每個城市按首字母歸類，便於前台查找;注意生成地區首字母是城市不會出現縣級。',
        ],
        'list' => [
            '0' => '在新增一級地區點擊管理進入下一級地區，可進行刪除和編輯。',
            '1' => '地區用於商城定位，請根據商城實際情況謹慎設置。',
            '2' => '生成地區首字母是方便根據地區首字母搜索相對應的地區。',
            '3' => '地區層級關係必須為中國→省/直轄市→市→縣，地區暫只支持到四級地區其後不顯示，暫不支持國外。',
        ],
    ],


];