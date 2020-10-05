<?php

$_LANG['goods_report_list'] = '舉報列表';
$_LANG['goods_report_type'] = '舉報類型';
$_LANG['goods_report_title'] = '舉報主題';
$_LANG['back_list'] = '返回列表';
$_LANG['continue_add'] = '繼續添加';
$_LANG['add_succeed'] = '添加成功';
$_LANG['edit_succeed'] = '編輯成功';
$_LANG['is_show'] = "是否顯示";
//投訴類型
$_LANG['type_desc'] = '舉報描述';
$_LANG['type_add'] = '添加類型';
$_LANG['title_exist'] = '舉報類型已存在！';
$_LANG['type_name_null'] = '舉報類型不能為空';
$_LANG['type_desc_null'] = '舉報描述不能為空';

//舉報主題
$_LANG['title_add'] = '添加主題';
$_LANG['exist_title'] = '舉報主題已存在！';
$_LANG['title_name_null'] = '舉報主題不能為空';
$_LANG['exist_title'] = '舉報主題已存在！';

//舉報管理
$_LANG['report_user'] = '舉報人';
$_LANG['report_goods'] = '舉報商品';
$_LANG['report_should'] = "圖證";
$_LANG['report_state'] = "舉報狀態";
$_LANG['involve_shop'] = "涉及店鋪";
$_LANG['report_describe'] = "舉報描述";
$_LANG['handle_type'] = "處理結果";
$_LANG['inform_handle_type'][1] = "無效舉報";
$_LANG['inform_handle_type'][2] = "惡意舉報";
$_LANG['inform_handle_type'][3] = "有效舉報";
$_LANG['handle_type_desc'][1] = "無效舉報--商品會正常銷售";
$_LANG['handle_type_desc'][2] = "惡意舉報--該用戶的所有未處理舉報將被無效處理，用戶將被禁止舉報";
$_LANG['handle_type_desc'][3] = "有效舉報--商品將被違規下架";
$_LANG['handle_message'] = "回復";
$_LANG['handle_report'] = "投訴處理";
$_LANG['handle_report_repeat'] = "已處理或用戶取消，您不能進行此操作";
$_LANG['handle_message_def'] = "您的舉報存在惡意舉報，您的其他未處理的舉報全部無效處理。為了營造更好的購物環境，請素質參與！";
$_LANG['handle_message_goods'] = "會員舉報涉嫌 %s ,據核實予以下架處理，如有疑問請聯系客服！";
$_LANG['handle_type_on'] = "已處理";
$_LANG['handle_type_off'] = "未處理";
$_LANG['action_info'] = '操作信息';
$_LANG['user_cancel'] = '用戶取消';
$_LANG['user_delete'] = '用戶刪除';
$_LANG['irregularities'] = '違規';

//舉報管理js語言項
$_LANG['js_languages']['type_name_null'] = '舉報類型不能為空';
$_LANG['js_languages']['type_desc_null'] = '舉報描述不能為空';
$_LANG['js_languages']['title_name_null'] = '舉報主題不能為空';
$_LANG['js_languages']['handle_type_null'] = '處理結果必填';
$_LANG['js_languages']['handle_message_null'] = '回復必填';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '商品舉報相關信息管理，設置審核。';

$_LANG['operation_prompt_content']['list'][0] = '商品舉報相關信息管理。';
$_LANG['operation_prompt_content']['list'][1] = '舉報類型和舉報主題由管理員在後台設置，在商品信息頁會員可根據舉報主題舉報違規商品，點擊詳細，查看舉報內容。';

$_LANG['operation_prompt_content']['title'][0] = '商品舉報主題管理。';

$_LANG['operation_prompt_content']['title_info'][0] = '添加編輯舉報主題。';

$_LANG['operation_prompt_content']['type'][0] = '商品舉報類型管理。';

$_LANG['operation_prompt_content']['type_info'][0] = '添加/編輯舉報類型。';

return $_LANG;
