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
    'status' => '狀態',
    'sequence' => '排序',
    'operating' => '操作',
    'open' => '開啟',
    'close' => '關閉',
    'editor' => '編輯',
    'see' => '查看',
    'edit' => '卸載',
    'drop' => '刪除',
    'confirm_delete' => '確定刪除嗎？',
    'button_save' => '保存',
    'add_time' => '添加時間',

    'num_order' => '編號',
    'errcode' => '錯誤代碼：',
    'errmsg' => '錯誤信息：',
    'control_center' => '管理中心',
    'edit_wechat' => '公眾號設置',
    'sort_order' => '排序',
    'empty' => '不能為空',
    'success' => '成功',
    'handler' => '操作',
    'button_search' => '搜索',
    'enabled' => '啟用',
    'disabled' => '禁用',
    'already_enabled' => '已啟用',
    'already_disabled' => '已禁用',
    'to_enabled' => '點擊啟用',
    'to_disabled' => '點擊禁用',

    'wx_menu' => '小程序',

    //模板消息
    'templates' => '消息提醒',
    'template_title' => '模板標題',
    'template_id' => '模板ID',
    'template_code' => '模板編號',
    'template_content' => '模板內容',
    'template_remark' => '備注',
    'template_edit_fail' => '編輯失敗',
    'please_select_industry' => '請選擇主行業和副行業',
    'please_apply_template' => '請登錄微信公眾號平台，申請開通模板消息',

    'confirm_reset_template' => '您確定要重置模板消息嗎？',
    'template_tips' => [
        0 => '消息提醒，即小程序模板消息，需要先登錄小程序微信公眾號平台。',
        1 => '啟用列表所需要的模板消息，即可在相應事件觸發模板消息；編輯模板消息備注，可增加顯示自定義提示消息內容',
        2 => '每個公眾號賬號可以同時使用25個模板，超過將無法使用模板消息功能。',
    ],

    'wx_config' => '配置',
    'wx_config_tips' => [
        0 => '一、配置前先 <a href="https://mp.weixin.qq.com/debug/wxadoc/introduction/index.html" target="_blank">注冊小程序</a>，進行微信認證, 已有微信小程序 <a href="https://mp.weixin.qq.com/" target="_blank">立即登錄</a>',
        1 => '二、登錄 <a href="https://mp.weixin.qq.com/" target="_blank">微信公眾號平台 </a>後，在 設置 - 開發者設置 中，查看到微信小程序的
                    AppID、Appsecret，並配置填寫好域名。（注意不可直接使用微信服務號或訂閱號的 AppID、AppSecret）',
        2 => '三、微信認證後，開通小程序微信支付。開通後，配置小程序微信支付的商戶號和密鑰。',
        3 => '四、小程序退款 需要配置證書，證書與公眾號支付證書相同，安裝支付方式配置證書。',
    ],
    'wx_appname' => '小程序名稱',
    'wx_appid' => '小程序AppID',
    'wx_appsecret' => '小程序AppSecret',
    'wx_mch_id' => '小程序微信支付商戶號',
    'wx_mch_key' => '小程序微信支付密鑰',
    'token_secret' => 'Token授權密鑰',
    'make_token' => '生成Token',
    'copy_token' => '復制Token',

    'wxapp_help1' => '小程序AppID，非微信公眾號AppID',
    'wxapp_help2' => '小程序AppSecret，非微信公眾號AppSecret',
    'wxapp_help3' => '小程序微信支付商戶號',
    'wxapp_help4' => '小程序微信支付密鑰',
    'wxapp_help5' => 'Token授權加密key（32位字元）,用於小程序授權登錄。重要信息，請不要隨意泄露給他人！',

    'must_appid' => '請填寫小程序AppID',
    'must_appsecret' => '請填寫小程序AppSecret',
    'must_mch_id' => '請填寫小程序微信支付商戶號',
    'must_mch_key' => '請填寫小程序微信支付密鑰',
    'must_token_secret' => '請填寫生成32位token密鑰',

    'open_wxapp' => '請配置並開啟小程序',

    'wx_sslcert' => '支付證書cert',
    'wx_sslkey' => '支付證書key',
    'wxapp_sslcert_help' => '證書可選填，用於退款。請在微信商戶後台下載支付證書，用記事本打開apiclient_cert.pem，並復制裡面的內容粘貼到這里',
    'wxapp_sslkey_help' => '證書可選填，用於退款。請在微信商戶後台下載支付證書，用記事本打開apiclient_key.pem，並復制裡面的內容粘貼到這里',

];