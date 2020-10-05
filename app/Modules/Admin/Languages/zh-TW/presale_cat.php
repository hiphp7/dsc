<?php

/* 商品分類欄位信息 */
$_LANG['cat_id'] = '編號';
$_LANG['cat_name'] = '分類名稱';
$_LANG['isleaf'] = '不允許';
$_LANG['noleaf'] = '允許';
$_LANG['keywords'] = '關鍵字';
$_LANG['cat_desc'] = '分類描述';
$_LANG['parent_id'] = '上級分類';
$_LANG['sort_order'] = '排序';
$_LANG['measure_unit'] = '數量單位';
$_LANG['delete_info'] = '刪除選中';
$_LANG['category_edit'] = '編輯商品分類';
$_LANG['move_goods'] = '轉移商品';
$_LANG['cat_top'] = '頂級分類';
$_LANG['show_in_nav'] = '是否顯示在導航欄';
$_LANG['cat_style'] = '分類的樣式表文件';
$_LANG['is_show'] = '是否顯示';
$_LANG['show_in_index'] = '設置為首頁推薦';
$_LANG['notice_show_in_index'] = '該設置可以在首頁的最新、熱門、推薦處顯示該分類下的推薦商品';

$_LANG['add_presale_cat'] = '添加預售分類';
$_LANG['presale_cat_list'] = '預售分類列表';
$_LANG['index_new'] = '最新';
$_LANG['index_best'] = '精品';
$_LANG['index_hot'] = '熱門';

$_LANG['back_list'] = '返回分類列表';
$_LANG['continue_add'] = '繼續添加分類';

$_LANG['notice_style'] = '您可以為每一個商品分類指定一個樣式表文件。例如文件存放在 themes 目錄下則輸入：themes/style.css';

/* 操作提示信息 */
$_LANG['catname_empty'] = '分類名稱不能為空!';
$_LANG['catname_exist'] = '已存在相同的分類名稱!';
$_LANG["parent_isleaf"] = '所選分類不能是末級分類!';
$_LANG["cat_isleaf"] = '不是末級分類或者此分類下還存在有商品,您不能刪除!';
$_LANG["cat_noleaf"] = '底下還有其它子分類,不能修改為末級分類!';
$_LANG["is_leaf_error"] = '所選擇的上級分類不能是當前分類或者當前分類的下級分類!';
$_LANG['grade_error'] = '價格分級數量只能是0-10之內的整數';

$_LANG['catadd_succed'] = '新商品分類添加成功!';
$_LANG['catedit_succed'] = '商品分類編輯成功!';
$_LANG['catdrop_succed'] = '商品分類刪除成功!';
$_LANG['catremove_succed'] = '商品分類轉移成功!';
$_LANG['move_cat_success'] = '轉移商品分類已成功完成!';

$_LANG['cat_move_desc'] = '什麼是轉移商品分類?';
$_LANG['select_source_cat'] = '選擇要轉移的分類';
$_LANG['select_target_cat'] = '選擇目標分類';
$_LANG['source_cat'] = '從此分類';
$_LANG['target_cat'] = '轉移到';
$_LANG['start_move_cat'] = '開始轉移';
$_LANG['cat_move_notic'] = '在添加商品或者在商品管理中,如果需要對商品的分類進行變更,那麼你可以通過此功能,正確管理你的商品分類。';

$_LANG['cat_move_empty'] = '你沒有正確選擇商品分類!';

$_LANG['sel_goods_type'] = '請選擇商品類型';
$_LANG['sel_filter_attr'] = '請選擇篩選屬性';
$_LANG['filter_attr'] = '篩選屬性';
$_LANG['filter_attr_notic'] = '篩選屬性可在前分類頁面篩選商品';
$_LANG['filter_attr_not_repeated'] = '篩選屬性不可重復';

/*JS 語言項*/
$_LANG['js_languages']['catname_empty'] = '分類名稱不能為空!';
$_LANG['js_languages']['unit_empyt'] = '數量單位不能為空!';
$_LANG['js_languages']['is_leafcat'] = '您選定的分類是一個末級分類。\r\n新分類的上級分類不能是一個末級分類';
$_LANG['js_languages']['not_leafcat'] = '您選定的分類不是一個末級分類。\r\n商品的分類轉移只能在末級分類之間才可以操作。';
$_LANG['js_languages']['filter_attr_not_repeated'] = '篩選屬性不可重復';
$_LANG['js_languages']['filter_attr_not_selected'] = '請選擇篩選屬性';
$_LANG['js_languages']['cat_name_null'] = '請輸入分類名稱';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['list'][0] = '展示了預售商品分類列表。';
$_LANG['operation_prompt_content']['list'][1] = '可進行刪除或修改預售商品分類名稱。';

$_LANG['operation_prompt_content']['info'][0] = '可選擇已有的頂級預售分類。';

return $_LANG;
