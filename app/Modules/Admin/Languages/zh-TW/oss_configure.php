<?php

$_LANG['bucket'] = 'Bucket名稱'; //從OSS獲得的AccessKeyId
$_LANG['keyid'] = 'AccessKeyID'; //從OSS獲得的AccessKeyId
$_LANG['keysecret'] = 'AccessKeySecret'; //從OSS獲得的AccessKeySecret
$_LANG['endpoint'] = 'EndPoint'; //您選定的OSS數據中心訪問域名，例如oss-cn-hangzhou.aliyuncs.com
$_LANG['is_cname'] = '域名綁定'; //是否對Bucket做了域名綁定，並且Endpoint參數填寫的是自己的域名
$_LANG['regional'] = 'Bucket地域'; //OSS Bucket所在區域
$_LANG['jinrong-regional'] = '金融地域'; //OSS Bucket所在區域

$_LANG['outside_site'] = '外網域名'; //OSS外網域名
$_LANG['inside_site'] = '內網域名'; //OSS外網域名

$_LANG['01_oss_list'] = '返回Bucket列表';
$_LANG['02_oss_add'] = '添加Bucket';

$_LANG['on'] = '開啟';
$_LANG['off'] = '關閉';

$_LANG['add_success'] = 'Bucket添加成功';
$_LANG['add_failure'] = 'Bucket已存在';
$_LANG['button_remove'] = '刪除Bucket';
$_LANG['edit_success'] = 'Bucket編輯成功';
$_LANG['remove_success'] = '刪除Bucket成功';
$_LANG['file_management'] = '文件管理';

$_LANG['no_select_user'] = '您現在沒有需要刪除的bucket！';
$_LANG['remove_confirm'] = '您確定要刪除該bucket嗎？';
$_LANG['is_use'] = '是否使用';

$_LANG['shanghai'] = '中國（上海：華東 2）';
$_LANG['hangzhou'] = '中國（杭州：華東 1）';
$_LANG['shenzhen'] = '中國（深圳：華南 1）';
$_LANG['beijing'] = '中國（北京：華北 2）';
$_LANG['qingdao'] = '中國（青島：華北 1）';
$_LANG['zhangjiakou'] = '中國（張家口：華北 3）';
$_LANG['huhehaote'] = '中國（呼和浩特：華北 5）';
$_LANG['hongkong'] = '中國（香港）';
$_LANG['us_west_1'] = '美國西部 1 (矽谷)';
$_LANG['us_east_1'] = '美國東部 1 (弗吉尼亞)';
$_LANG['ap_southeast_1'] = '亞太東南 1 (新加坡)';
$_LANG['ap_northeast_1'] = '亞太東北 1 (日本)';

$_LANG['shanghai-finance-1-pub'] = '上海金融雲公網';
$_LANG['hzfinance'] = '杭州金融雲公網';
$_LANG['szfinance'] = '深圳金融雲公網';

$_LANG['regional_name_1'] = '中國（上海）';
$_LANG['regional_name_2'] = '中國（杭州）';
$_LANG['regional_name_3'] = '中國（深圳）';
$_LANG['regional_name_4'] = '中國（北京）';
$_LANG['regional_name_5'] = '中國（青島）';
$_LANG['regional_name_6'] = '中國（香港）';
$_LANG['regional_name_7'] = '美國(加利福尼亞州)';
$_LANG['regional_name_8'] = '亞洲(新加坡)';

$_LANG['http'] = '域名';
$_LANG['delimg'] = '是否刪除圖片';

$_LANG['delete_Bucket'] = '確定刪除該Bucket嗎?';

$_LANG['bucket_oss_notic'] = '與阿里雲OSS開通對象名稱一致';
$_LANG['bucket_keyid_notic'] = '填寫阿里雲Access Key管理的(ID)';
$_LANG['bucket_secret_notic'] = '填寫阿里雲Access Key管理的(Secret)';
$_LANG['bucket_url_notic'] = '默認選關閉，官方建議開啟綁定域名，域名格式：http://xx.xxxx.com/（不可綁定當前網站域名，建議新開二級域名）';

/*js語言項*/
$_LANG['js_languages']['oss_bucket_null'] = 'Bucket不能為空';
$_LANG['js_languages']['oss_keyid_null'] = 'KeyId不能為空';
$_LANG['js_languages']['oss_keysecret_null'] = 'KeySecret不能為空';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '阿里雲OSS配置使用說明';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '填寫在阿里雲創建的OSS信息。';
$_LANG['operation_prompt_content']['info'][1] = '需要在系統設置->商品設置->擴展信息中開啟該功能即可使用。';

$_LANG['operation_prompt_content']['list'][0] = '該頁面展示OSS配置的列表信息。';
$_LANG['operation_prompt_content']['list'][1] = '可以直接在列表頁面進行編輯和刪除。';
$_LANG['operation_prompt_content']['list'][2] = 'OSS可用於圖片、音視頻、日誌等海量文件的存儲。';

return $_LANG;
