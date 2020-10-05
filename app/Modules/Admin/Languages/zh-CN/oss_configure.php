<?php

$_LANG['bucket'] = 'Bucket名称'; //从OSS获得的AccessKeyId
$_LANG['keyid'] = 'AccessKeyID'; //从OSS获得的AccessKeyId
$_LANG['keysecret'] = 'AccessKeySecret'; //从OSS获得的AccessKeySecret
$_LANG['endpoint'] = 'EndPoint'; //您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
$_LANG['is_cname'] = '域名绑定'; //是否对Bucket做了域名绑定，并且Endpoint参数填写的是自己的域名
$_LANG['regional'] = 'Bucket地域'; //OSS Bucket所在区域
$_LANG['jinrong-regional'] = '金融地域'; //OSS Bucket所在区域

$_LANG['outside_site'] = '外网域名'; //OSS外网域名
$_LANG['inside_site'] = '内网域名'; //OSS外网域名

$_LANG['01_oss_list'] = '返回Bucket列表';
$_LANG['02_oss_add'] = '添加Bucket';

$_LANG['on'] = '开启';
$_LANG['off'] = '关闭';

$_LANG['add_success'] = 'Bucket添加成功';
$_LANG['add_failure'] = 'Bucket已存在';
$_LANG['button_remove'] = '删除Bucket';
$_LANG['edit_success'] = 'Bucket编辑成功';
$_LANG['remove_success'] = '删除Bucket成功';
$_LANG['file_management'] = '文件管理';

$_LANG['no_select_user'] = '您现在没有需要删除的bucket！';
$_LANG['remove_confirm'] = '您确定要删除该bucket吗？';
$_LANG['is_use'] = '是否使用';

$_LANG['shanghai'] = '中国（上海：华东 2）';
$_LANG['hangzhou'] = '中国（杭州：华东 1）';
$_LANG['shenzhen'] = '中国（深圳：华南 1）';
$_LANG['beijing'] = '中国（北京：华北 2）';
$_LANG['qingdao'] = '中国（青岛：华北 1）';
$_LANG['zhangjiakou'] = '中国（张家口：华北 3）';
$_LANG['huhehaote'] = '中国（呼和浩特：华北 5）';
$_LANG['hongkong'] = '中国（香港）';
$_LANG['us_west_1'] = '美国西部 1 (硅谷)';
$_LANG['us_east_1'] = '美国东部 1 (弗吉尼亚)';
$_LANG['ap_southeast_1'] = '亚太东南 1 (新加坡)';
$_LANG['ap_northeast_1'] = '亚太东北 1 (日本)';

$_LANG['shanghai-finance-1-pub'] = '上海金融云公网';
$_LANG['hzfinance'] = '杭州金融云公网';
$_LANG['szfinance'] = '深圳金融云公网';

$_LANG['regional_name_1'] = '中国（上海）';
$_LANG['regional_name_2'] = '中国（杭州）';
$_LANG['regional_name_3'] = '中国（深圳）';
$_LANG['regional_name_4'] = '中国（北京）';
$_LANG['regional_name_5'] = '中国（青岛）';
$_LANG['regional_name_6'] = '中国（香港）';
$_LANG['regional_name_7'] = '美国(加利福尼亚州)';
$_LANG['regional_name_8'] = '亚洲(新加坡)';

$_LANG['http'] = '域名';
$_LANG['delimg'] = '是否删除图片';

$_LANG['delete_Bucket'] = '确定删除该Bucket吗?';

$_LANG['bucket_oss_notic'] = '与阿里云OSS开通对象名称一致';
$_LANG['bucket_keyid_notic'] = '填写阿里云Access Key管理的(ID)';
$_LANG['bucket_secret_notic'] = '填写阿里云Access Key管理的(Secret)';
$_LANG['bucket_url_notic'] = '默认选关闭，官方建议开启绑定域名，域名格式：http://xx.xxxx.com/（不可绑定当前网站域名，建议新开二级域名）';

/*js语言项*/
$_LANG['js_languages']['oss_bucket_null'] = 'Bucket不能为空';
$_LANG['js_languages']['oss_keyid_null'] = 'KeyId不能为空';
$_LANG['js_languages']['oss_keysecret_null'] = 'KeySecret不能为空';

/* 教程名称 */
$_LANG['tutorials_bonus_list_one'] = '阿里云OSS配置使用说明';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '填写在阿里云创建的OSS信息。';
$_LANG['operation_prompt_content']['info'][1] = '需要在系统设置->商品设置->扩展信息中开启该功能即可使用。';

$_LANG['operation_prompt_content']['list'][0] = '该页面展示OSS配置的列表信息。';
$_LANG['operation_prompt_content']['list'][1] = '可以直接在列表页面进行编辑和删除。';
$_LANG['operation_prompt_content']['list'][2] = 'OSS可用于图片、音视频、日志等海量文件的存储。';

return $_LANG;
