<?php

$_LANG['bucket'] = 'Bucket名稱'; //從OBS獲得的AccessKeyId
$_LANG['keyid'] = 'AccessKeyID'; //從OBS獲得的AccessKeyId
$_LANG['keysecret'] = 'AccessKeySecret'; //從OBS獲得的AccessKeySecret
$_LANG['endpoint'] = 'EndPoint'; //您選定的OBS數據中心訪問域名，例如oss-cn-hangzhou.aliyuncs.com
$_LANG['is_cname'] = '域名綁定'; //是否對Bucket做了域名綁定，並且Endpoint參數填寫的是自己的域名
$_LANG['regional'] = 'Bucket地域'; //OBS Bucket所在區域
$_LANG['port'] = '埠號'; //OBS Bucket所在區域

$_LANG['outside_site'] = '外網域名'; //OBS外網域名
$_LANG['inside_site'] = '內網域名'; //OBS外網域名

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

$_LANG['huabei-beijing-four'] = '華北-北京四';
$_LANG['huabei-beijing-one'] = '華北-北京一';
$_LANG['huadong-shanghai-two'] = '華東-上海二';
$_LANG['huadong-guangzhou'] = '華南-廣州';
$_LANG['af-south'] = '南非-約翰內斯堡';
$_LANG['xinan-guiyang-one'] = '西南-貴陽一';
$_LANG['ap-bangkok'] = '亞太-曼谷';
$_LANG['ap-hong-kong'] = '亞太-香港';
$_LANG['ap-singapore'] = '亞太-新加坡';

$_LANG['cn-north-4'] = '華北-北京四';
$_LANG['cn-north-1'] = '華北-北京一';
$_LANG['cn-east-2'] = '華東-上海二';
$_LANG['cn-south-1'] = '華南-廣州';
$_LANG['af-south-1'] = '南非-約翰內斯堡';
$_LANG['cn-southwest-2'] = '西南-貴陽一';
$_LANG['ap-southeast-2'] = '亞太-曼谷';
$_LANG['ap-southeast-1'] = '亞太-香港';
$_LANG['ap-southeast-3'] = '亞太-新加坡';

$_LANG['http'] = '域名';
$_LANG['delimg'] = '是否刪除圖片';

$_LANG['delete_Bucket'] = '確定刪除該Bucket嗎?';

$_LANG['bucket_oss_notic'] = '與華為雲OBS開通對象名稱一致';
$_LANG['bucket_keyid_notic'] = '填寫華為雲Access Key管理的(ID)';
$_LANG['bucket_secret_notic'] = '填寫華為雲Access Key管理的(Secret)';
$_LANG['bucket_url_notic'] = '默認選關閉，官方建議開啟綁定域名，域名格式：http://xx.xxxx.com/（不可綁定當前網站域名，建議新開二級域名）';

/*js語言項*/
$_LANG['js_languages']['oss_bucket_null'] = 'Bucket不能為空';
$_LANG['js_languages']['oss_keyid_null'] = 'KeyId不能為空';
$_LANG['js_languages']['oss_keysecret_null'] = 'KeySecret不能為空';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '華為雲OBS配置使用說明';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '填寫在華為雲創建的OBS信息。';
$_LANG['operation_prompt_content']['info'][1] = '需要在系統設置->商品設置->擴展信息中開啟該功能即可使用。';

$_LANG['operation_prompt_content']['list'][0] = '該頁面展示OBS配置的列表信息。';
$_LANG['operation_prompt_content']['list'][1] = '可以直接在列表頁面進行編輯和刪除。';
$_LANG['operation_prompt_content']['list'][2] = 'OBS可用於圖片、音視頻、日誌等海量文件的存儲。';

return $_LANG;
