<?php

return [

    /*
    |--------------------------------------------------------------------------
    | wxapp Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the wxapp
    |
    */

    'add' => '添加',
    'yes' => '是',
    'no' => '否',
    'button_submit' => '提交',
    'button_reset' => '重置',
    'status' => '状态',
    'sequence' => '排序',
    'operating' => '操作',
    'open' => '开启',
    'close' => '关闭',
    'editor' => '编辑',
    'see' => '查看',
    'edit' => '卸载',
    'drop' => '删除',
    'confirm_delete' => '确定删除吗？',
    'button_save' => '保存',
    'add_time' => '添加时间',

    'num_order' => '编号',
    'errcode' => '错误代码：',
    'errmsg' => '错误信息：',
    'control_center' => '管理中心',
    'edit_wechat' => '公众号设置',
    'sort_order' => '排序',
    'empty' => '不能为空',
    'success' => '成功',
    'handler' => '操作',
    'button_search' => '搜索',
    'enabled' => '启用',
    'disabled' => '禁用',
    'already_enabled' => '已启用',
    'already_disabled' => '已禁用',
    'to_enabled' => '点击启用',
    'to_disabled' => '点击禁用',

    'wx_menu' => '小程序',

    //模板消息
    'templates' => '消息提醒',
    'template_title' => '模板标题',
    'template_id' => '模板ID',
    'template_code' => '模板编号',
    'template_content' => '模板内容',
    'template_remark' => '备注',
    'template_edit_fail' => '编辑失败',
    'please_select_industry' => '请选择主行业和副行业',
    'please_apply_template' => '请登录微信公众号平台，申请开通模板消息',

    'confirm_reset_template' => '您确定要重置模板消息吗？',
    'template_tips' => [
        0 => '消息提醒，即小程序模板消息，需要先登录小程序微信公众号平台。',
        1 => '启用列表所需要的模板消息，即可在相应事件触发模板消息；编辑模板消息备注，可增加显示自定义提示消息内容',
        2 => '每个公众号账号可以同时使用25个模板，超过将无法使用模板消息功能。',
    ],

    'wx_config' => '配置',
    'wx_config_tips' => [
        0 => '一、配置前先 <a href="https://mp.weixin.qq.com/debug/wxadoc/introduction/index.html" target="_blank">注册小程序</a>，进行微信认证, 已有微信小程序 <a href="https://mp.weixin.qq.com/" target="_blank">立即登录</a>',
        1 => '二、登录 <a href="https://mp.weixin.qq.com/" target="_blank">微信公众号平台 </a>后，在 设置 - 开发者设置 中，查看到微信小程序的
                    AppID、Appsecret，并配置填写好域名。（注意不可直接使用微信服务号或订阅号的 AppID、AppSecret）',
        2 => '三、微信认证后，开通小程序微信支付。开通后，配置小程序微信支付的商户号和密钥。',
        3 => '四、小程序退款 需要配置证书，证书与公众号支付证书相同，安装支付方式配置证书。',
    ],
    'wx_appname' => '小程序名称',
    'wx_appid' => '小程序AppID',
    'wx_appsecret' => '小程序AppSecret',
    'wx_mch_id' => '小程序微信支付商户号',
    'wx_mch_key' => '小程序微信支付密钥',
    'token_secret' => 'Token授权密钥',
    'make_token' => '生成Token',
    'copy_token' => '复制Token',

    'wxapp_help1' => '小程序AppID，非微信公众号AppID',
    'wxapp_help2' => '小程序AppSecret，非微信公众号AppSecret',
    'wxapp_help3' => '小程序微信支付商户号',
    'wxapp_help4' => '小程序微信支付密钥',
    'wxapp_help5' => 'Token授权加密key（32位字符）,用于小程序授权登录。重要信息，请不要随意泄露给他人！',

    'must_appid' => '请填写小程序AppID',
    'must_appsecret' => '请填写小程序AppSecret',
    'must_mch_id' => '请填写小程序微信支付商户号',
    'must_mch_key' => '请填写小程序微信支付密钥',
    'must_token_secret' => '请填写生成32位token密钥',

    'open_wxapp' => '请配置并开启小程序',

    'wx_sslcert' => '支付证书cert',
    'wx_sslkey' => '支付证书key',
    'wxapp_sslcert_help' => '证书可选填，用于退款。请在微信商户后台下载支付证书，用记事本打开apiclient_cert.pem，并复制里面的内容粘贴到这里',
    'wxapp_sslkey_help' => '证书可选填，用于退款。请在微信商户后台下载支付证书，用记事本打开apiclient_key.pem，并复制里面的内容粘贴到这里',

];