<?php

$_LANG['bucket'] = 'Bucket名称'; //从OBS获得的AccessKeyId
$_LANG['keyid'] = 'AccessKeyID'; //从OBS获得的AccessKeyId
$_LANG['keysecret'] = 'AccessKeySecret'; //从OBS获得的AccessKeySecret
$_LANG['endpoint'] = 'EndPoint'; //您选定的OBS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
$_LANG['is_cname'] = '域名绑定'; //是否对Bucket做了域名绑定，并且Endpoint参数填写的是自己的域名
$_LANG['regional'] = 'Bucket地域'; //OBS Bucket所在区域
$_LANG['port'] = '端口号'; //OBS Bucket所在区域

$_LANG['outside_site'] = '外网域名'; //OBS外网域名
$_LANG['inside_site'] = '内网域名'; //OBS外网域名

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

$_LANG['huabei-beijing-four'] = '华北-北京四';
$_LANG['huabei-beijing-one'] = '华北-北京一';
$_LANG['huadong-shanghai-two'] = '华东-上海二';
$_LANG['huadong-guangzhou'] = '华南-广州';
$_LANG['af-south'] = '南非-约翰内斯堡';
$_LANG['xinan-guiyang-one'] = '西南-贵阳一';
$_LANG['ap-bangkok'] = '亚太-曼谷';
$_LANG['ap-hong-kong'] = '亚太-香港';
$_LANG['ap-singapore'] = '亚太-新加坡';

$_LANG['cn-north-4'] = '华北-北京四';
$_LANG['cn-north-1'] = '华北-北京一';
$_LANG['cn-east-2'] = '华东-上海二';
$_LANG['cn-south-1'] = '华南-广州';
$_LANG['af-south-1'] = '南非-约翰内斯堡';
$_LANG['cn-southwest-2'] = '西南-贵阳一';
$_LANG['ap-southeast-2'] = '亚太-曼谷';
$_LANG['ap-southeast-1'] = '亚太-香港';
$_LANG['ap-southeast-3'] = '亚太-新加坡';

$_LANG['http'] = '域名';
$_LANG['delimg'] = '是否删除图片';

$_LANG['delete_Bucket'] = '确定删除该Bucket吗?';

$_LANG['bucket_oss_notic'] = '与华为云OBS开通对象名称一致';
$_LANG['bucket_keyid_notic'] = '填写华为云Access Key管理的(ID)';
$_LANG['bucket_secret_notic'] = '填写华为云Access Key管理的(Secret)';
$_LANG['bucket_url_notic'] = '默认选关闭，官方建议开启绑定域名，域名格式：http://xx.xxxx.com/（不可绑定当前网站域名，建议新开二级域名）';

/*js语言项*/
$_LANG['js_languages']['oss_bucket_null'] = 'Bucket不能为空';
$_LANG['js_languages']['oss_keyid_null'] = 'KeyId不能为空';
$_LANG['js_languages']['oss_keysecret_null'] = 'KeySecret不能为空';

/* 教程名称 */
$_LANG['tutorials_bonus_list_one'] = '华为云OBS配置使用说明';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '填写在华为云创建的OBS信息。';
$_LANG['operation_prompt_content']['info'][1] = '需要在系统设置->商品设置->扩展信息中开启该功能即可使用。';

$_LANG['operation_prompt_content']['list'][0] = '该页面展示OBS配置的列表信息。';
$_LANG['operation_prompt_content']['list'][1] = '可以直接在列表页面进行编辑和删除。';
$_LANG['operation_prompt_content']['list'][2] = 'OBS可用于图片、音视频、日志等海量文件的存储。';

return $_LANG;
