<?php

$_LANG['cfg_name']['cloud_storage'] = '雲存儲';
$_LANG['cfg_range']['cloud_storage']['0'] = '阿里雲';
$_LANG['cfg_range']['cloud_storage']['1'] = '華為雲';
$_LANG['cfg_desc']['cloud_storage'] = '選擇雲存儲類型，目前提供：阿里雲、華為雲等類型';

$_LANG['cfg_name']['tp_api'] = '電子面單';
$_LANG['cfg_range']['tp_api']['0'] = '否';
$_LANG['cfg_range']['tp_api']['1'] = '是';
$_LANG['cfg_desc']['tp_api'] = '設置是否啟用電子面單【快遞鳥】';

$_LANG['cfg_name']['auto_mobile'] = '識別自動跳轉H5';
$_LANG['cfg_range']['auto_mobile']['0'] = '否';
$_LANG['cfg_range']['auto_mobile']['1'] = '是';
$_LANG['cfg_desc']['auto_mobile'] = '設置訪問PC地址是否自動跳轉H5頁面';

$_LANG['cfg_name']['header_region'] = '商城頭部推薦地區';
$_LANG['cfg_desc']['header_region'] = '用於商城頭部地區便於用戶快速進入相應熱門地區（格式：英文逗號）';

$_LANG['cfg_name']['area_pricetype'] = '商品設置地區模式時';
$_LANG['cfg_range']['area_pricetype']['0'] = '精確到省/直轄市';
$_LANG['cfg_range']['area_pricetype']['1'] = '精確到市/縣';
$_LANG['cfg_desc']['area_pricetype'] = '商品【選擇地區模式】，商品價格按地區模式顯示';

$_LANG['cfg_name']['floor_nav_type'] = '首頁樓層左側導航樣式';
$_LANG['cfg_range']['floor_nav_type']['1'] = '樣式一';
$_LANG['cfg_range']['floor_nav_type']['2'] = '樣式二';
$_LANG['cfg_range']['floor_nav_type']['3'] = '樣式三';
$_LANG['cfg_range']['floor_nav_type']['4'] = '樣式四';
$_LANG['cfg_desc']['floor_nav_type'] = '<a href="../storage/images/floor_nav_type_icon.jpg" class="nyroModal"><i class="icon icon-picture" onmouseover="toolTip(\'<img src=../storage/images/floor_nav_type_icon.jpg>\')" onmouseout="toolTip()"></i></a>&nbsp;&nbsp;多項設置首頁樓層左側導航定位不同樣式';

$_LANG['cfg_name']['ip_type'] = '站點IP定位類型';
$_LANG['cfg_range']['ip_type']['0'] = 'IP庫';
$_LANG['cfg_range']['ip_type']['1'] = '騰訊';
$_LANG['cfg_desc']['ip_type'] = 'IP定位主要用於網站頭部和商品詳情地區定位，建議選擇（騰訊或IP庫介面），測試清瀏覽器緩存';

$_LANG['js_languages']['reduce_num_not_null'] = '請輸入類型\n(說明：類型與發票內容一致)\n';
$_LANG['js_languages']['reduce_price_not_null'] = '請輸入稅率';

$_LANG['cfg_name']['tengxun_key'] = '騰訊地圖密鑰';
$_LANG['cfg_desc']['tengxun_key'] = '<a href="http://lbs.qq.com/console/user_info.html" target="_blank">獲取密鑰</a>';

$_LANG['cfg_name']['kuaidi100_key'] = '快遞100密鑰';
$_LANG['cfg_desc']['kuaidi100_key'] = '請輸入<a href="https://www.kuaidi100.com/openapi/applyapi.shtml" target="_blank">快遞100密鑰</a>';

$_LANG['cfg_name']['use_coupons'] = '是否啟用優惠券';
$_LANG['cfg_range']['use_coupons']['0'] = '關閉';
$_LANG['cfg_range']['use_coupons']['1'] = '啟用';

$_LANG['cfg_name']['use_value_card'] = '是否啟用儲值卡';
$_LANG['cfg_range']['use_value_card']['0'] = '關閉';
$_LANG['cfg_range']['use_value_card']['1'] = '啟用';

$_LANG['cfg_name']['add_shop_price'] = '商品SKU價格模式';
$_LANG['cfg_range']['add_shop_price']['0'] = 'SKU價格（屬性貨品價格）';
$_LANG['cfg_range']['add_shop_price']['1'] = 'SKU價格（商品價格 + 屬性貨品價格）';

$_LANG['cfg_name']['goods_attr_price'] = '商品屬性價格模式';
$_LANG['cfg_range']['goods_attr_price']['0'] = '單一模式';
$_LANG['cfg_range']['goods_attr_price']['1'] = '貨品模式';
$_LANG['cfg_desc']['goods_attr_price'] = '商品屬性價格分兩種模式，單一模式是系統原先單個屬性設置價格，貨品模式是以貨品屬性一組設置價格';

$_LANG['cfg_name']['user_login_register'] = '會員登錄/注冊郵箱驗證是否開啟';
$_LANG['cfg_range']['user_login_register']['0'] = '關閉';
$_LANG['cfg_range']['user_login_register']['1'] = '開啟';
$_LANG['cfg_desc']['user_login_register'] = '如果開啟郵箱驗證，用戶在使用郵箱注冊會員成功後必須郵箱驗證才能進入會員中心';

$_LANG['cfg_name']['user_phone'] = '會員賬號安全驗證級別';
$_LANG['cfg_range']['user_phone']['0'] = '郵箱驗證';
$_LANG['cfg_range']['user_phone']['1'] = '手機驗證';
$_LANG['cfg_desc']['user_phone'] = '如果開啟手機驗證並關閉《會員登錄/注冊郵箱驗證是否開啟》情況下，以手機驗證為最高級別';

$_LANG['cfg_name']['server_model'] = '伺服器運行方式';
$_LANG['cfg_range']['server_model']['0'] = '單台伺服器';
$_LANG['cfg_range']['server_model']['1'] = '多台伺服器負載均衡';
$_LANG['cfg_desc']['server_model'] = '如果商城沒有採用負載均衡方式運行(比如阿里雲的負載均衡)，請設置單台伺服器方式';

$_LANG['cfg_name']['exchange_size'] = '積分商品列表頁的數量';

$_LANG['cfg_name']['site_domain'] = '網站域名';
$_LANG['cfg_desc']['site_domain'] = '請輸入您當前網站的域名，避免啟用商家二級域名出現問題（如：http://www.xxxx.com/）';

//by li start
$_LANG['cfg_name']['sms_price_notice'] = '商品降價時是否發送簡訊';
$_LANG['cfg_range']['sms_price_notice']['1'] = '發簡訊';
$_LANG['cfg_range']['sms_price_notice']['0'] = '不發簡訊';
$_LANG['cfg_name']['sms_ecmoban_user'] = '簡訊介面用戶名';
$_LANG['cfg_name']['sms_ecmoban_password'] = '簡訊介面密碼';
//by li end

+$_LANG['cfg_name']['chuanglan_account'] = '253創藍用戶名';
+$_LANG['cfg_name']['chuanglan_password'] = '253創藍密碼';
+$_LANG['cfg_name']['chuanglan_api_url'] = '253創藍請求地址';
+$_LANG['cfg_name']['chuanglan_signa'] = '253創藍簽名';
//退換貨 start
$_LANG['cfg_name']['sign'] = '發貨日期起可退換貨時間';
$_LANG['cfg_desc']['sign'] = '以天數為單位';
//退換貨 end

//ecmoban模板堂 --zhuo start

$_LANG['cfg_name']['sms_type'] = '簡訊類型';
$_LANG['cfg_range']['sms_type']['0'] = '互億無線';
$_LANG['cfg_range']['sms_type']['1'] = '阿里大於';
$_LANG['cfg_range']['sms_type']['2'] = '阿里雲簡訊';
$_LANG['cfg_range']['sms_type']['3'] = "模板堂簡訊 <font class='red'>[推薦]</font>";
$_LANG['cfg_range']['sms_type']['4'] = '華為雲簡訊';
$_LANG['cfg_range']['sms_type']['5'] = '253創藍';

$_LANG['cfg_name']['ali_appkey'] = '阿里大魚(appKey)';
$_LANG['cfg_name']['ali_secretkey'] = '阿里大魚(secretKey)';

$_LANG['cfg_name']['access_key_id'] = 'AccessKeyID';
$_LANG['cfg_name']['access_key_secret'] = 'AccessKeySecret';

$_LANG['cfg_name']['dsc_appkey'] = '模板堂(appKey)';
$_LANG['cfg_name']['dsc_appsecret'] = '模板堂(appSecret)';

$_LANG['cfg_name']['huawei_sms_key'] = '華為雲(appKey)';
$_LANG['cfg_name']['huawei_sms_secret'] = '華為雲(appSecret)';

$_LANG['cfg_name']['sms_find_signin'] = '客戶密碼找回時';
$_LANG['cfg_range']['sms_find_signin']['0'] = '不發簡訊';
$_LANG['cfg_range']['sms_find_signin']['1'] = '發簡訊';

$_LANG['cfg_name']['sms_code'] = '發送驗證碼';
$_LANG['cfg_range']['sms_code']['0'] = '不發簡訊';
$_LANG['cfg_range']['sms_code']['1'] = '發簡訊';
$_LANG['cfg_desc']['sms_code'] = '用於單個參數驗證碼（用戶實名驗證、商家實名驗證等）簡訊發送';

$_LANG['cfg_name']['user_account_code'] = '會員充值/提現時是否發送簡訊';
$_LANG['cfg_range']['user_account_code']['0'] = '不發簡訊';
$_LANG['cfg_range']['user_account_code']['1'] = '發簡訊';

$_LANG['cfg_name']['open_oss'] = '是否開啟雲存儲';
$_LANG['cfg_range']['open_oss']['0'] = '關閉';
$_LANG['cfg_range']['open_oss']['1'] = '開啟';

$_LANG['cfg_name']['open_memcached'] = 'Memcached是否啟用';
$_LANG['cfg_range']['open_memcached']['0'] = '關閉';
$_LANG['cfg_range']['open_memcached']['1'] = '開啟';
$_LANG['cfg_desc']['open_memcached'] = '溫馨提示：啟用前必須確認系統伺服器Memcached服務一切正常';

$_LANG['cfg_name']['ad_reminder'] = '廣告位提示設置';
$_LANG['cfg_range']['ad_reminder']['0'] = '關閉';
$_LANG['cfg_range']['ad_reminder']['1'] = '開啟';
$_LANG['cfg_desc']['ad_reminder'] = '啟用後可以查看頁面廣告位是否設置廣告';

$_LANG['cfg_name']['open_study'] = '查看教程開關設置';
$_LANG['cfg_range']['open_study']['0'] = '關閉';
$_LANG['cfg_range']['open_study']['1'] = '開啟';
$_LANG['cfg_desc']['open_study'] = '啟用後可以查看官網教程';

$_LANG['cfg_name']['show_warehouse'] = '商品詳情開啟可選倉庫';
$_LANG['cfg_range']['show_warehouse']['0'] = '關閉';
$_LANG['cfg_range']['show_warehouse']['1'] = '開啟';

$_LANG['cfg_name']['seller_email'] = '下訂單時是否給入駐商客服發郵件';
$_LANG['cfg_range']['seller_email']['0'] = '關閉';
$_LANG['cfg_range']['seller_email']['1'] = '開啟';
$_LANG['cfg_desc']['seller_email'] = '店鋪基本信息設置的客服郵件地址不為空時，該選項有效。';

$_LANG['cfg_name']['user_helpart'] = '會員幫助文章';
$_LANG['cfg_desc']['user_helpart'] = '此設置為會員中心默認頁面右側幫助文章（格式：1,2,3 註：逗號為英文逗號）';

$_LANG['cfg_name']['single_thumb_width'] = '曬單縮略圖寬度';
$_LANG['cfg_name']['single_thumb_height'] = '曬單縮略圖高度';

$_LANG['cfg_name']['return_pictures'] = '退換貨上傳圖片憑證';
$_LANG['cfg_desc']['return_pictures'] = '設置退換貨上傳圖片憑證數量限制';

$_LANG['cfg_name']['auction_ad'] = '限制顯示廣告數量';

$_LANG['cfg_name']['marticle'] = '店鋪入駐文章列表';
$_LANG['cfg_desc']['marticle'] = '設置店鋪入駐首頁左側文章ID,文本框中填寫文章分類ID';

$_LANG['cfg_name']['marticle_id'] = '店鋪入駐文章默認內容';
$_LANG['cfg_desc']['marticle_id'] = '設置默認顯示店鋪入駐文章內容ID';

$_LANG['cfg_name']['open_delivery_time'] = '是否開啟自動確認收貨';
$_LANG['cfg_range']['open_delivery_time']['0'] = '關閉';
$_LANG['cfg_range']['open_delivery_time']['1'] = '開啟';

$_LANG['cfg_name']['open_area_goods'] = '是否開啟頁面區分地區商品';
$_LANG['cfg_range']['open_area_goods']['0'] = '關閉';
$_LANG['cfg_range']['open_area_goods']['1'] = '開啟';

$_LANG['cfg_name']['choose_process'] = '入駐申請必填功能';
$_LANG['cfg_range']['choose_process']['0'] = '關閉';
$_LANG['cfg_range']['choose_process']['1'] = '開啟';

$_LANG['cfg_name']['freight_model'] = '商品運費模式';
$_LANG['cfg_range']['freight_model']['0'] = '系統默認';
$_LANG['cfg_range']['freight_model']['1'] = '倉庫模式';
$_LANG['cfg_desc']['freight_model'] = '系統默認：按配送方式設置運費，即普通的運費模式，如發往蘇州、上海等的運費設置；<br>倉庫模式：使用倉庫設置運費，如從上海倉庫發往蘇州的運費設置。';

$_LANG['cfg_name']['customer_service'] = '店鋪客服設置';
$_LANG['cfg_range']['customer_service']['0'] = '默認統一客服';
$_LANG['cfg_range']['customer_service']['1'] = '商家自行客服';
$_LANG['cfg_desc']['customer_service'] = '設置客服模式是由管理員設置統一店鋪客服或者是由商家自行設置店鋪客服';

$_LANG['cfg_name']['review_goods'] = '審核店鋪商品';
$_LANG['cfg_range']['review_goods']['0'] = '關閉';
$_LANG['cfg_range']['review_goods']['1'] = '開啟';
$_LANG['cfg_desc']['review_goods'] = '設置是否需要審核商家添加的商品，如果開啟則所有商家添加的商品都需要審核之後才能顯示';

$_LANG['cfg_name']['group_goods'] = '商品配件組名稱';
$_LANG['cfg_desc']['group_goods'] = '以英文逗號分隔字元串抒寫，例：推薦配件,人氣組合';

$_LANG['cfg_desc']['add_shop_price'] = '選擇第一種方式的時候，設置促銷活動會無效，比如分期、促銷價格等；會默認按商品價格來計算促銷活動';

$_LANG['cfg_name']['attr_set_up'] = '商品屬性許可權';
$_LANG['cfg_range']['attr_set_up'][0] = '由網站統一設置';
$_LANG['cfg_range']['attr_set_up'][1] = '商家可自行設置';
$_LANG['cfg_desc']['attr_set_up'] = '設置商品屬性商家是否有許可權添加';

$_LANG['cfg_name']['goods_file'] = '商品編輯審核欄位';
$_LANG['cfg_desc']['goods_file'] = '設置商品編輯時需要審核的欄位，空值為審核已通過，否則未審核' . "<br/>" . '默認欄位：goods_name,shop_price,market_price,promote_price,goods_sn' . "<br/>" . '倉庫或地區模式時：-warehouse_price,warehouse_promote_price,region_price,region_promote_price';

$_LANG['cfg_name']['delete_seller'] = '是否刪除商家所有信息';
$_LANG['cfg_range']['delete_seller']['0'] = '否';
$_LANG['cfg_range']['delete_seller']['1'] = '是';
$_LANG['cfg_desc']['delete_seller'] = '在刪除商家的同時刪除商家的所有信息，請慎重選擇.';
//ecmoban模板堂 --zhuo end

//Powered by ecmoban.com (mike) start
$_LANG['cfg_name']['editing_tools'] = '內容編輯器';
$_LANG['cfg_range']['editing_tools']['fckeditor'] = '默認Fckeditor';
$_LANG['cfg_range']['editing_tools']['ueditor'] = '百度Ueditor(推薦)';

//wang
$_LANG['cfg_name']['sms_signin'] = '客戶注冊時是否發送簡訊驗證碼';
$_LANG['cfg_range']['sms_signin']['1'] = '發簡訊';
$_LANG['cfg_range']['sms_signin']['0'] = '不發簡訊';
$_LANG['cfg_name']['sms_ecmoban_user'] = '互億在線簡訊介面用戶名';
$_LANG['cfg_name']['sms_ecmoban_password'] = '互億在線簡訊介面密碼';
//Powered by ecmoban.com (mike) end

$_LANG['cfg_name']['sms_seller_signin'] = '修改入住商許可權時是否發送簡訊';
$_LANG['cfg_range']['sms_seller_signin']['0'] = '不發簡訊';
$_LANG['cfg_range']['sms_seller_signin']['1'] = '發簡訊';

$_LANG['cfg_name']['ectouch_qrcode'] = '底部二維碼右邊';
$_LANG['cfg_name']['ecjia_qrcode'] = '底部二維碼左邊';
$_LANG['cfg_name']['index_down_logo'] = '首頁下滑彈出logo圖片';
$_LANG['cfg_name']['site_commitment'] = '頭部右側翻轉效果圖片';
$_LANG['cfg_name']['user_login_logo'] = '用戶登錄與注冊頁面LOGO';
$_LANG['cfg_name']['login_logo_pic'] = '用戶登錄與注冊頁面LOGO右側圖';
$_LANG['cfg_name']['business_logo'] = '批發Logo';

$_LANG['cfg_name']['admin_login_logo'] = '平台後台登錄頁Logo';
$_LANG['cfg_name']['admin_logo'] = '平台後台Logo';
$_LANG['cfg_name']['seller_login_logo'] = '商家後台登錄頁Logo';
$_LANG['cfg_name']['seller_logo'] = '商家後台Logo';
$_LANG['cfg_name']['stores_login_logo'] = '門店後台登錄頁Logo';
$_LANG['cfg_name']['stores_logo'] = '門店後台Logo';
$_LANG['cfg_name']['order_print_logo'] = '訂單列印Logo';


$_LANG['cfg_name']['basic_logo'] = 'logo設置';
$_LANG['cfg_name']['extend_basic'] = '擴展信息';
$_LANG['cfg_name']['basic'] = '基本設置';
$_LANG['cfg_name']['display'] = '顯示設置';
$_LANG['cfg_name']['shop_info'] = '平台信息';
$_LANG['cfg_name']['shopping_flow'] = '購物流程';
$_LANG['cfg_name']['smtp'] = '郵件伺服器設置';
$_LANG['cfg_name']['goods'] = '商品顯示設置';
$_LANG['cfg_name']['lang'] = '系統語言';
$_LANG['cfg_name']['shop_closed'] = '暫時關閉網站';
$_LANG['cfg_name']['icp_file'] = 'ICP 備案證書文件';
$_LANG['cfg_name']['watermark'] = '水印文件';
$_LANG['cfg_name']['watermark_place'] = '水印位置';
$_LANG['cfg_name']['use_storage'] = '是否啟用庫存管理';
$_LANG['cfg_name']['market_price_rate'] = '市場價格比例';
$_LANG['cfg_name']['rewrite'] = 'URL重寫';
$_LANG['cfg_name']['integral_name'] = '消費積分名稱';
$_LANG['cfg_name']['integral_scale'] = '積分換算比例';
$_LANG['cfg_name']['integral_percent'] = '積分支付比例';
$_LANG['cfg_name']['enable_order_check'] = '是否開啟新訂單提醒';
$_LANG['cfg_name']['default_storage'] = '默認庫存';
$_LANG['cfg_name']['date_format'] = '日期格式';
$_LANG['cfg_name']['time_format'] = '時間格式';
$_LANG['cfg_name']['currency_format'] = '貨幣格式';
$_LANG['cfg_name']['thumb_width'] = '縮略圖寬度';
$_LANG['cfg_name']['thumb_height'] = '縮略圖高度';
$_LANG['cfg_name']['image_width'] = '商品圖片寬度';
$_LANG['cfg_name']['image_height'] = '商品圖片高度';
$_LANG['cfg_name']['best_number'] = '精品推薦數量';
$_LANG['cfg_name']['new_number'] = '新品推薦數量';
$_LANG['cfg_name']['hot_number'] = '熱銷商品數量';
$_LANG['cfg_name']['promote_number'] = '特價商品的數量';
$_LANG['cfg_name']['group_goods_number'] = '團購商品的數量';
$_LANG['cfg_name']['top_number'] = '銷量排行數量';
$_LANG['cfg_name']['history_number'] = '瀏覽歷史數量';
$_LANG['cfg_name']['comments_number'] = '評論數量';
$_LANG['cfg_name']['bought_goods'] = '相關商品數量';
$_LANG['cfg_name']['article_number'] = '最新文章顯示數量';
$_LANG['cfg_name']['order_number'] = '訂單顯示數量';
$_LANG['cfg_name']['shop_name'] = '商店名稱';
$_LANG['cfg_name']['shop_title'] = '商店標題';
$_LANG['cfg_name']['shop_website'] = '商店網址';
$_LANG['cfg_name']['shop_desc'] = '商店描述';
$_LANG['cfg_name']['shop_keywords'] = '商店關鍵字';
$_LANG['cfg_name']['shop_country'] = '所在國家';
$_LANG['cfg_name']['shop_province'] = '所在省份';
$_LANG['cfg_name']['shop_city'] = '所在城市';
$_LANG['cfg_name']['shop_district'] = '所在區域';
$_LANG['cfg_name']['shop_address'] = '詳細地址';
$_LANG['cfg_name']['licensed'] = '是否顯示 Licensed';
$_LANG['cfg_name']['qq'] = '客服QQ號碼';
$_LANG['cfg_name']['ww'] = '淘寶旺旺';
$_LANG['cfg_name']['skype'] = 'Skype';
$_LANG['cfg_name']['ym'] = 'Yahoo Messenger';
$_LANG['cfg_name']['msn'] = 'MSN Messenger';
$_LANG['cfg_name']['service_email'] = '客服郵件地址';
$_LANG['cfg_name']['service_phone'] = '客服電話';
$_LANG['cfg_name']['can_invoice'] = '能否開發票';
$_LANG['cfg_name']['user_notice'] = '用戶中心公告';
$_LANG['cfg_name']['shop_notice'] = '商店公告';
$_LANG['cfg_name']['shop_reg_closed'] = '是否關閉注冊';
$_LANG['cfg_name']['send_mail_on'] = '是否開啟自動發送郵件';
$_LANG['cfg_name']['auto_generate_gallery'] = '上傳商品是否自動生成相冊圖';
$_LANG['cfg_name']['retain_original_img'] = '上傳商品時是否保留原圖';
$_LANG['cfg_name']['member_email_validate'] = '是否開啟會員郵件驗證';
$_LANG['cfg_name']['send_verify_email'] = '用戶注冊時自動發送驗證郵件';
$_LANG['cfg_name']['message_board'] = '是否啟用留言板功能';
$_LANG['cfg_name']['message_check'] = '用戶留言是否需要審核';
//$_LANG['cfg_name']['use_package'] = '是否使用包裝';
//$_LANG['cfg_name']['use_card'] = '是否使用賀卡';
$_LANG['cfg_name']['use_integral'] = '是否使用積分';
$_LANG['cfg_name']['use_bonus'] = '是否使用紅包';
$_LANG['cfg_name']['use_value_card'] = '是否使用儲值卡';
$_LANG['cfg_name']['use_surplus'] = '是否使用余額';
$_LANG['cfg_name']['use_how_oos'] = '是否使用缺貨處理';
$_LANG['cfg_name']['use_paypwd'] = '是否驗證支付密碼';
$_LANG['cfg_name']['send_confirm_email'] = '確認訂單時';
$_LANG['cfg_name']['order_pay_note'] = '設置訂單為「已付款」時';
$_LANG['cfg_name']['order_unpay_note'] = '設置訂單為「未付款」時';
$_LANG['cfg_name']['order_ship_note'] = '設置訂單為「已發貨」時';
$_LANG['cfg_name']['order_unship_note'] = '設置訂單為「未發貨」時';
$_LANG['cfg_name']['when_dec_storage'] = '什麼時候減少庫存';
$_LANG['cfg_name']['send_ship_email'] = '發貨時';
$_LANG['cfg_name']['order_receive_note'] = '設置訂單為「收貨確認」時';
$_LANG['cfg_name']['order_cancel_note'] = '取消訂單時';
$_LANG['cfg_name']['send_cancel_email'] = '取消訂單時';
$_LANG['cfg_name']['order_return_note'] = '退貨時';
$_LANG['cfg_name']['order_invalid_note'] = '把訂單設為無效時';
$_LANG['cfg_name']['send_invalid_email'] = '把訂單設為無效時';
$_LANG['cfg_name']['sn_prefix'] = '商品貨號前綴';
$_LANG['cfg_name']['close_comment'] = '關閉網店的原因';
$_LANG['cfg_name']['watermark_alpha'] = '水印透明度';
$_LANG['cfg_name']['icp_number'] = 'ICP證書或ICP備案證書號';
$_LANG['cfg_name']['invoice_content'] = '發票內容';
$_LANG['cfg_name']['invoice_type'] = '發票類型及稅率';
$_LANG['cfg_name']['stock_dec_time'] = '減庫存的時機';
$_LANG['cfg_name']['sales_volume_time'] = '增加銷量的時機';
$_LANG['cfg_name']['cross_border_article_id'] = '結算頁告知文章欄目';
$_LANG['cfg_name']['comment_check'] = '用戶評論是否需要審核';
$_LANG['cfg_name']['comment_factor'] = '商品評論的條件';
$_LANG['cfg_name']['no_picture'] = '商品的默認圖片';
$_LANG['cfg_name']['no_brand'] = '品牌的默認圖片';
$_LANG['cfg_name']['stats_code'] = '統計代碼';
$_LANG['cfg_name']['cache_time'] = '緩存存活時間（秒）';
$_LANG['cfg_name']['page_size'] = '商品分類頁列表的數量';
$_LANG['cfg_name']['article_page_size'] = '文章分類頁列表的數量';
$_LANG['cfg_name']['page_style'] = '分頁樣式';
$_LANG['cfg_name']['sort_order_type'] = '商品分類頁默認排序類型';
$_LANG['cfg_name']['sort_order_method'] = '商品分類頁默認排序方式';
$_LANG['cfg_name']['show_order_type'] = '商品分類頁默認顯示方式';
$_LANG['cfg_name']['goods_name_length'] = '商品名稱的長度';
$_LANG['cfg_name']['price_format'] = '商品價格顯示規則';
$_LANG['cfg_name']['register_points'] = '會員注冊贈送積分';
$_LANG['cfg_name']['shop_logo'] = '商店 Logo';
$_LANG['cfg_name']['enable_gzip'] = '是否啟用Gzip模式';
$_LANG['cfg_name']['anonymous_buy'] = '是否允許未登錄用戶購物';
$_LANG['cfg_name']['min_goods_amount'] = '最小購物金額';
$_LANG['cfg_name']['one_step_buy'] = '是否一步購物';
$_LANG['cfg_name']['show_goodssn'] = '是否顯示貨號';
$_LANG['cfg_name']['show_brand'] = '是否顯示品牌';
$_LANG['cfg_name']['show_goodsweight'] = '是否顯示重量';
$_LANG['cfg_name']['show_goodsnumber'] = '是否顯示庫存';
$_LANG['cfg_name']['show_addtime'] = '是否顯示上架時間';
$_LANG['cfg_name']['show_rank_price'] = '是否顯示等級價格';
$_LANG['cfg_name']['show_give_integral'] = '是否贈送消費積分';
$_LANG['cfg_name']['show_marketprice'] = '是否顯示市場價格';
$_LANG['cfg_name']['goodsattr_style'] = '商品屬性顯示樣式';
$_LANG['cfg_name']['test_mail_address'] = '郵件地址';
$_LANG['cfg_name']['send'] = '發送測試郵件';
$_LANG['cfg_name']['send_service_email'] = '下訂單時是否給客服發郵件';
$_LANG['cfg_name']['show_goods_in_cart'] = '購物車里顯示商品方式';
$_LANG['cfg_name']['show_attr_in_cart'] = '購物車里是否顯示商品屬性';
$_LANG['test_mail_title'] = '測試郵件';
$_LANG['cfg_name']['email_content'] = '您好！這是一封檢測郵件伺服器設置的測試郵件。收到此郵件，意味著您的郵件伺服器設置正確！您可以進行其它郵件發送的操作了！';
$_LANG['cfg_name']['sms'] = '簡訊設置';
$_LANG['cfg_name']['sms_shop_mobile'] = '商家的手機號碼';
$_LANG['cfg_name']['sms_order_placed'] = '客戶下訂單時是否給商家發簡訊';
$_LANG['cfg_name']['sms_order_payed'] = '客戶付款時是否給商家發簡訊';
$_LANG['cfg_name']['sms_order_shipped'] = '商家發貨時是否給客戶發簡訊';
$_LANG['cfg_name']['sms_order_received'] = '客戶確認收貨時是否給商家發簡訊';
$_LANG['cfg_name']['sms_shop_order_received'] = '商家確認收貨時是否給買家發簡訊';
$_LANG['cfg_name']['attr_related_number'] = '屬性關聯的商品數量';
$_LANG['cfg_name']['top10_time'] = '排行統計的時間';
$_LANG['cfg_name']['goods_gallery_number'] = '商品詳情頁相冊圖片數量';
$_LANG['cfg_name']['article_title_length'] = '文章標題的長度';
$_LANG['cfg_name']['cron_method'] = '是否開啟命令行調用計劃任務';
$_LANG['cfg_name']['timezone'] = '默認時區';
$_LANG['cfg_name']['upload_size_limit'] = '附件上傳大小';
$_LANG['cfg_name']['search_keywords'] = '首頁搜索的關鍵字';
$_LANG['cfg_name']['cart_confirm'] = '購物車確定提示';
$_LANG['cfg_name']['bgcolor'] = '縮略圖背景色';
$_LANG['cfg_name']['name_of_region_1'] = '一級配送區域名稱';
$_LANG['cfg_name']['name_of_region_2'] = '二級配送區域名稱';
$_LANG['cfg_name']['name_of_region_3'] = '三級配送區域名稱';
$_LANG['cfg_name']['name_of_region_4'] = '四級配送區域名稱';
$_LANG['cfg_name']['related_goods_number'] = '關聯商品顯示數量';
$_LANG['cfg_name']['visit_stats'] = '站點訪問統計';
$_LANG['cfg_name']['help_open'] = '用戶幫助是否打開';
$_LANG['cfg_name']['privacy'] = '隱私聲明';

$_LANG['cfg_desc']['privacy'] = '隱私聲明使用的文章id';
$_LANG['cfg_desc']['invoice_type'] = '稅率填寫整數值，例如（稅率：12，商品金額：35.70）則公式：35.70 * （12 / 100） = 4.28元';
$_LANG['cfg_desc']['smtp'] = '設置郵件伺服器基本參數';
$_LANG['cfg_desc']['market_price_rate'] = '輸入商品售價時將自動根據該比例計算市場價格';
$_LANG['cfg_desc']['rewrite'] = 'URL重寫是一種搜索引擎優化技術，可以將動態的地址模擬成靜態的HTML文件。需要Apache的支持。';
$_LANG['cfg_desc']['integral_name'] = '您可以將消費積分重新命名。例如：商豆';
$_LANG['cfg_desc']['integral_scale'] = '每100積分可抵多少現金';
$_LANG['cfg_desc']['integral_percent'] = '每100元商品最多可以使用多少元積分';
$_LANG['cfg_desc']['comments_number'] = '顯示在商品詳情頁的用戶評論數量。';
$_LANG['cfg_desc']['shop_title'] = '商店的標題，將顯示在瀏覽器的標題欄Title';
$_LANG['cfg_desc']['shop_desc'] = '商店描述內容，將顯示在瀏覽器的Description';
$_LANG['cfg_desc']['shop_keywords'] = '商店的關鍵字，將顯示在瀏覽器的Keywords';
$_LANG['cfg_desc']['shop_address'] = '商店詳細地址，不顯示在前台';
$_LANG['cfg_desc']['smtp_host'] = '郵件伺服器主機地址。如果本機可以發送郵件則設置為localhost';
$_LANG['cfg_desc']['smtp_user'] = '發送郵件所需的認證帳號，如果沒有就為空著';
$_LANG['cfg_desc']['bought_goods'] = '顯示多少個購買此商品的人還買過哪些商品';
$_LANG['cfg_desc']['currency_format'] = '顯示商品價格的格式，%s將被替換為相應的價格數字。';
$_LANG['cfg_desc']['image_height'] = '如果您的伺服器支持GD，在您上傳商品圖片的時候將自動將圖片縮小到指定的尺寸。';
$_LANG['cfg_desc']['watermark'] = '水印文件須為gif格式才可支持透明度設置。';
$_LANG['cfg_desc']['watermark_place'] = '水印顯示在圖片上的位置，"無"將不顯示水印。';
$_LANG['cfg_desc']['watermark_alpha'] = '水印的透明度，可選值為0-100。當設置為100時則為不透明。';
$_LANG['cfg_desc']['invoice_content'] = '客戶要求開發票時可以選擇的內容。例如：辦公用品。每一行代表一個選項。';
$_LANG['cfg_desc']['stats_code'] = '您可以將其他訪問統計服務商提供的代碼添加到每一個頁面。';
$_LANG['cfg_desc']['cache_time'] = '前台頁面緩存的存活時間，以秒為單位。';
$_LANG['cfg_desc']['service_email'] = '商店客服郵箱地址，接收發送的信息';
$_LANG['cfg_desc']['service_phone'] = '商店客服電話，聯系平台電話，例：入駐頁面導航顯示的電話';
$_LANG['cfg_desc']['qq'] = 'QQ客服名稱和號碼請用「|」隔開（如：客服1|123456），如果您有多個客服的QQ號碼，請換行。';
$_LANG['cfg_desc']['msn'] = '如果您有多個客服的MSN號碼，請在每個號碼之間使用半形逗號（,）分隔。';
$_LANG['cfg_desc']['ym'] = '如果您有多個客服的 Yahoo Messenger 號碼，請在每個號碼之間使用半形逗號（,）分隔。';
$_LANG['cfg_desc']['ww'] = '旺旺客服名稱和號碼請用「|」隔開（如：客服2|654321），如果您有多個客服的旺旺號碼，請換行。';
$_LANG['cfg_desc']['shop_logo'] = '上傳圖片格式必須是gif,jpg,jpeg,png;圖片大小在200kb之內，建議尺寸：159*100';
$_LANG['cfg_desc']['business_logo'] = '上傳圖片格式必須是gif,jpg,jpeg,png;圖片大小在200kb之內';
$_LANG['cfg_desc']['enable_gzip'] = '啟用Gzip模式可壓縮發送頁面大小，加快網頁傳輸。需要php支持Gzip。如果已經用Apache等對頁面進行Gzip壓縮，請禁止該功能。';
$_LANG['cfg_desc']['skype'] = '如果您有多個客服的Skype號碼，請在每個號碼之間使用半形逗號（,）分隔。提示：你需要在你的Skype隱私設置中啟用狀態顯示功能';
$_LANG['cfg_desc']['attr_related_number'] = '在商品詳情頁面顯示多少個屬性關聯的商品。';
$_LANG['cfg_desc']['user_notice'] = '該信息將在用戶中心歡迎頁面顯示';
$_LANG['cfg_desc']['comment_factor'] = '選取較高的評論條件可以有效的減少垃圾評論的產生。只有用戶訂單完成後才認為該用戶有購買行為';
$_LANG['cfg_desc']['min_goods_amount'] = '達到此購物金額，才能提交訂單。';
$_LANG['cfg_desc']['search_keywords'] = '首頁顯示的搜索關鍵字,請用半形逗號(,)分隔多個關鍵字';
$_LANG['cfg_desc']['shop_notice'] = '以上內容將顯示在首頁商店公告中,注意控制公告內容長度不要超過公告顯示區域大小。';
$_LANG['cfg_desc']['bgcolor'] = '顏色請以#FFFFFF格式填寫';
$_LANG['cfg_desc']['cart_confirm'] = '允許您設置用戶點擊「加入購物車」後是否提示以及隨後的動作。';
$_LANG['cfg_desc']['cross_border_article_id'] = '結算頁顯示，請填寫文章ID，多個文章使用半形逗號（,）分隔';
$_LANG['cfg_desc']['use_how_oos'] = '使用缺貨處理時前台訂單確認頁面允許用戶選擇缺貨時處理方法。';
$_LANG['cfg_desc']['send_service_email'] = '網店信息中的客服郵件地址不為空時，該選項有效。';
$_LANG['cfg_desc']['send_mail_on'] = '啟用該選項登錄後台時，會自動發送郵件隊列中尚未發送的郵件';
$_LANG['cfg_desc']['sms_shop_mobile'] = '請先注冊手機簡訊服務再填寫手機號碼';
$_LANG['cfg_desc']['send_verify_email'] = '「是否開啟會員郵件驗證」設為開啟時才可使用此功能';

$_LANG['cfg_desc']['shop_closed'] = '商店需升級或者其他原因臨時關閉網站';
$_LANG['cfg_desc']['close_comment'] = '商店臨時關閉網站說明原因';
$_LANG['cfg_desc']['shop_reg_closed'] = '商店是否關閉前台會員注冊';

$_LANG['cfg_desc']['lang'] = '商店系統使用的語言，zh-CN 是簡體中文，zh-TW 是繁體中文，en是英文';
$_LANG['cfg_desc']['icp_number'] = 'ICP證書號，顯示在網站前台底部';
$_LANG['cfg_desc']['register_points'] = '前台會員注冊贈送的系統積分數';

//$_LANG['cfg_range']['cart_confirm'][1] = '提示用戶，點擊「確定」進購物車';
//$_LANG['cfg_range']['cart_confirm'][2] = '提示用戶，點擊「取消」進購物車';
$_LANG['cfg_range']['cart_confirm'][3] = '直接進入購物車';
$_LANG['cfg_range']['cart_confirm'][4] = '不提示並停留在當前頁面';
$_LANG['cfg_range']['shop_closed']['0'] = '否';
$_LANG['cfg_range']['shop_closed']['1'] = '是';
$_LANG['cfg_range']['licensed']['0'] = '否';
$_LANG['cfg_range']['licensed']['1'] = '是';
$_LANG['cfg_range']['send_mail_on']['on'] = '開啟';
$_LANG['cfg_range']['send_mail_on']['off'] = '關閉';
$_LANG['cfg_range']['member_email_validate']['1'] = '開啟';
$_LANG['cfg_range']['member_email_validate']['0'] = '關閉';
$_LANG['cfg_range']['send_verify_email']['1'] = '開啟';
$_LANG['cfg_range']['send_verify_email']['0'] = '關閉';
$_LANG['cfg_range']['message_board']['1'] = '開啟';
$_LANG['cfg_range']['message_board']['0'] = '關閉';
$_LANG['cfg_range']['auto_generate_gallery']['1'] = '是';
$_LANG['cfg_range']['auto_generate_gallery']['0'] = '否';
$_LANG['cfg_range']['retain_original_img']['1'] = '是';
$_LANG['cfg_range']['retain_original_img']['0'] = '否';
$_LANG['cfg_range']['watermark_place']['0'] = '無';
$_LANG['cfg_range']['watermark_place']['1'] = '左上';
$_LANG['cfg_range']['watermark_place']['2'] = '右上';
$_LANG['cfg_range']['watermark_place']['3'] = '居中';
$_LANG['cfg_range']['watermark_place']['4'] = '左下';
$_LANG['cfg_range']['watermark_place']['5'] = '右下';
$_LANG['cfg_range']['use_storage']['1'] = '是';
$_LANG['cfg_range']['use_storage']['0'] = '否';
$_LANG['cfg_range']['rewrite']['0'] = '禁用';
$_LANG['cfg_range']['rewrite']['1'] = '簡單重寫';
$_LANG['cfg_range']['rewrite']['2'] = '復雜重寫';
$_LANG['cfg_range']['can_invoice']['0'] = '不能';
$_LANG['cfg_range']['can_invoice']['1'] = '能';
$_LANG['cfg_range']['top10_time']['0'] = '所有';
$_LANG['cfg_range']['top10_time']['1'] = '一年';
$_LANG['cfg_range']['top10_time']['2'] = '半年';
$_LANG['cfg_range']['top10_time']['3'] = '三個月';
$_LANG['cfg_range']['top10_time']['4'] = '一個月';
$_LANG['cfg_range']['use_integral']['1'] = '使用';
$_LANG['cfg_range']['use_integral']['0'] = '不使用';
$_LANG['cfg_range']['use_bonus']['1'] = '使用';
$_LANG['cfg_range']['use_bonus']['0'] = '不使用';
$_LANG['cfg_range']['use_value_card']['1'] = '使用';
$_LANG['cfg_range']['use_value_card']['0'] = '不使用';
$_LANG['cfg_range']['use_paypwd']['1'] = '是';
$_LANG['cfg_range']['use_paypwd']['0'] = '否';
$_LANG['cfg_desc']['use_paypwd'] = '啟用驗證支付密碼，則購物下單時必須驗證會員支付密碼，增強支付安全';
$_LANG['cfg_range']['use_surplus']['1'] = '使用';
$_LANG['cfg_range']['use_surplus']['0'] = '不使用';
$_LANG['cfg_range']['use_how_oos']['1'] = '使用';
$_LANG['cfg_range']['use_how_oos']['0'] = '不使用';
$_LANG['cfg_range']['send_confirm_email']['1'] = '發送郵件';
$_LANG['cfg_range']['send_confirm_email']['0'] = '不發送郵件';
$_LANG['cfg_range']['order_pay_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_pay_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['order_unpay_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_unpay_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['order_ship_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_ship_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['order_unship_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_unship_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['order_receive_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_receive_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['order_cancel_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_cancel_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['order_return_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_return_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['order_invalid_note']['1'] = '必須填寫備注';
$_LANG['cfg_range']['order_invalid_note']['0'] = '無需填寫備注';
$_LANG['cfg_range']['when_dec_storage']['0'] = '下定單時';
$_LANG['cfg_range']['when_dec_storage']['1'] = '發貨時';
$_LANG['cfg_range']['send_ship_email']['1'] = '發送郵件';
$_LANG['cfg_range']['send_ship_email']['0'] = '不發送郵件';
$_LANG['cfg_range']['send_cancel_email']['1'] = '發送郵件';
$_LANG['cfg_range']['send_cancel_email']['0'] = '不發送郵件';
$_LANG['cfg_range']['send_invalid_email']['1'] = '發送郵件';
$_LANG['cfg_range']['send_invalid_email']['0'] = '不發送郵件';
$_LANG['cfg_range']['mail_charset']['UTF8'] = '國際化編碼（utf8）';
$_LANG['cfg_range']['mail_charset']['GB2312'] = '簡體中文';
$_LANG['cfg_range']['mail_charset']['BIG5'] = '繁體中文';
$_LANG['cfg_range']['comment_check']['0'] = '不需要審核';
$_LANG['cfg_range']['comment_check']['1'] = '需要審核';
$_LANG['cfg_range']['message_check']['0'] = '不需要審核';
$_LANG['cfg_range']['message_check']['1'] = '需要審核';
$_LANG['cfg_range']['comment_factor']['0'] = '所有用戶';
$_LANG['cfg_range']['comment_factor']['1'] = '僅登錄用戶';
$_LANG['cfg_range']['comment_factor']['2'] = '有過一次以上購買行為用戶';
$_LANG['cfg_range']['comment_factor']['3'] = '僅購買過該商品用戶';
$_LANG['cfg_range']['enable_gzip']['1'] = '啟用';
$_LANG['cfg_range']['enable_gzip']['0'] = '禁用';
$_LANG['cfg_range']['price_format']['0'] = '不處理';
$_LANG['cfg_range']['price_format']['1'] = '保留不為 0 的尾數';
$_LANG['cfg_range']['price_format']['2'] = '不四捨五入，保留一位小數';
$_LANG['cfg_range']['price_format']['3'] = '不四捨五入，不保留小數';
$_LANG['cfg_range']['price_format']['4'] = '先四捨五入，保留一位小數';
$_LANG['cfg_range']['price_format']['5'] = '先四捨五入，不保留小數 ';
$_LANG['cfg_range']['sort_order_type']['0'] = '按上架時間';
$_LANG['cfg_range']['sort_order_type']['1'] = '按商品價格';
$_LANG['cfg_range']['sort_order_type']['2'] = '按最後更新時間';
$_LANG['cfg_range']['sort_order_method']['0'] = '降序排列';
$_LANG['cfg_range']['sort_order_method']['1'] = '升序排列';
$_LANG['cfg_range']['show_order_type'][0] = '列表顯示';
$_LANG['cfg_range']['show_order_type'][1] = '表格顯示';
$_LANG['cfg_range']['show_order_type'][2] = '文本顯示';
$_LANG['cfg_range']['help_open'][0] = '關閉';
$_LANG['cfg_range']['help_open'][1] = '打開';
$_LANG['cfg_range']['page_style'][0] = '默認經典';
$_LANG['cfg_range']['page_style'][1] = '流行頁碼';

$_LANG['cfg_range']['anonymous_buy']['0'] = '不允許';
$_LANG['cfg_range']['anonymous_buy']['1'] = '允許';
$_LANG['cfg_range']['one_step_buy']['0'] = '否';
$_LANG['cfg_range']['one_step_buy']['1'] = '是';
$_LANG['cfg_range']['show_goodssn']['1'] = '顯示';
$_LANG['cfg_range']['show_goodssn']['0'] = '不顯示';
$_LANG['cfg_range']['show_brand']['1'] = '顯示';
$_LANG['cfg_range']['show_brand']['0'] = '不顯示';
$_LANG['cfg_range']['show_goodsweight']['1'] = '顯示';
$_LANG['cfg_range']['show_goodsweight']['0'] = '不顯示';
$_LANG['cfg_range']['show_goodsnumber']['1'] = '顯示';
$_LANG['cfg_range']['show_goodsnumber']['0'] = '不顯示';
$_LANG['cfg_range']['show_addtime']['1'] = '顯示';
$_LANG['cfg_range']['show_addtime']['0'] = '不顯示';
$_LANG['cfg_range']['show_rank_price']['1'] = '顯示';
$_LANG['cfg_range']['show_rank_price']['0'] = '不顯示';

$_LANG['cfg_range']['show_give_integral']['1'] = '顯示';
$_LANG['cfg_range']['show_give_integral']['0'] = '不顯示';

$_LANG['cfg_range']['goodsattr_style']['1'] = '單選按鈕';
$_LANG['cfg_range']['goodsattr_style']['0'] = '下拉列表';
$_LANG['cfg_range']['show_marketprice']['1'] = '顯示';
$_LANG['cfg_range']['show_marketprice']['0'] = '不顯示';
$_LANG['cfg_range']['sms_order_placed']['1'] = '發簡訊';
$_LANG['cfg_range']['sms_order_placed']['0'] = '不發簡訊';
$_LANG['cfg_range']['sms_order_payed']['1'] = '發簡訊';
$_LANG['cfg_range']['sms_order_payed']['0'] = '不發簡訊';
$_LANG['cfg_range']['sms_order_shipped']['1'] = '發簡訊';
$_LANG['cfg_range']['sms_order_shipped']['0'] = '不發簡訊';
$_LANG['cfg_range']['sms_order_received']['1'] = '發簡訊';
$_LANG['cfg_range']['sms_order_received']['0'] = '不發簡訊';
$_LANG['cfg_range']['sms_shop_order_received']['1'] = '發簡訊';
$_LANG['cfg_range']['sms_shop_order_received']['0'] = '不發簡訊';
$_LANG['cfg_range']['cron_method']['0'] = '否';
$_LANG['cfg_range']['cron_method']['1'] = '是';
$_LANG['cfg_range']['enable_order_check']['0'] = '否';
$_LANG['cfg_range']['enable_order_check']['1'] = '是';
$_LANG['cfg_range']['enable_order_check']['0'] = '否';
$_LANG['cfg_range']['enable_order_check']['1'] = '是';
$_LANG['cfg_range']['stock_dec_time']['0'] = '發貨時';
$_LANG['cfg_range']['stock_dec_time']['1'] = '下訂單時';
$_LANG['cfg_range']['stock_dec_time']['2'] = '付款時';

$_LANG['cfg_range']['sales_volume_time']['0'] = '付款時';
$_LANG['cfg_range']['sales_volume_time']['1'] = '發貨時';

$_LANG['cfg_range']['send_service_email']['0'] = '否';
$_LANG['cfg_range']['send_service_email']['1'] = '是';
$_LANG['cfg_range']['show_goods_in_cart']['1'] = '只顯示文字';
$_LANG['cfg_range']['show_goods_in_cart']['2'] = '只顯示圖片';
$_LANG['cfg_range']['show_goods_in_cart']['3'] = '顯示文字與圖片';
$_LANG['cfg_range']['show_attr_in_cart']['0'] = '否';
$_LANG['cfg_range']['show_attr_in_cart']['1'] = '是';
$_LANG['cfg_range']['shop_reg_closed']['0'] = '否';
$_LANG['cfg_range']['shop_reg_closed']['1'] = '是';
$_LANG['cfg_range']['timezone']['-12'] = '(GMT -12:00) Eniwetok, Kwajalein';
$_LANG['cfg_range']['timezone']['-11'] = '(GMT -11:00) Midway Island, Samoa';
$_LANG['cfg_range']['timezone']['-10'] = '(GMT -10:00) Hawaii';
$_LANG['cfg_range']['timezone']['-9'] = '(GMT -09:00) Alaska';
$_LANG['cfg_range']['timezone']['-8'] = '(GMT -08:00) Pacific Time (US &amp; Canada), Tijuana';
$_LANG['cfg_range']['timezone']['-7'] = '(GMT -07:00) Mountain Time (US &amp; Canada), Arizona';
$_LANG['cfg_range']['timezone']['-6'] = '(GMT -06:00) Central Time (US &amp; Canada), Mexico City';
$_LANG['cfg_range']['timezone']['-5'] = '(GMT -05:00) Eastern Time (US &amp; Canada), Bogota, Lima, Quito';
$_LANG['cfg_range']['timezone']['-4'] = '(GMT -04:00) Atlantic Time (Canada), Caracas, La Paz';
$_LANG['cfg_range']['timezone']['-3.5'] = '(GMT -03:30) Newfoundland';
$_LANG['cfg_range']['timezone']['-3'] = '(GMT -03:00) Brassila, Buenos Aires, Georgetown, Falkland Is';
$_LANG['cfg_range']['timezone']['-2'] = '(GMT -02:00) Mid-Atlantic, Ascension Is., St. Helena';
$_LANG['cfg_range']['timezone']['-1'] = '(GMT -01:00) Azores, Cape Verde Islands';
$_LANG['cfg_range']['timezone']['0'] = '(GMT) Casablanca, Dublin, Edinburgh, London, Lisbon, Monrovia';
$_LANG['cfg_range']['timezone']['1'] = '(GMT +01:00) Amsterdam, Berlin, Brussels, Madrid, Paris, Rome';
$_LANG['cfg_range']['timezone']['2'] = '(GMT +02:00) Cairo, Helsinki, Kaliningrad, South Africa';
$_LANG['cfg_range']['timezone']['3'] = '(GMT +03:00) Baghdad, Riyadh, Moscow, Nairobi';
$_LANG['cfg_range']['timezone']['3.5'] = '(GMT +03:30) Tehran';
$_LANG['cfg_range']['timezone']['4'] = '(GMT +04:00) Abu Dhabi, Baku, Muscat, Tbilisi';
$_LANG['cfg_range']['timezone']['4.5'] = '(GMT +04:30) Kabul';
$_LANG['cfg_range']['timezone']['5'] = '(GMT +05:00) Ekaterinburg, Islamabad, Karachi, Tashkent';
$_LANG['cfg_range']['timezone']['5.5'] = '(GMT +05:30) Bombay, Calcutta, Madras, New Delhi';
$_LANG['cfg_range']['timezone']['5.75'] = '(GMT +05:45) Katmandu';
$_LANG['cfg_range']['timezone']['6'] = '(GMT +06:00) Almaty, Colombo, Dhaka, Novosibirsk';
$_LANG['cfg_range']['timezone']['6.5'] = '(GMT +06:30) Rangoon';
$_LANG['cfg_range']['timezone']['7'] = '(GMT +07:00) Bangkok, Hanoi, Jakarta';
$_LANG['cfg_range']['timezone']['8'] = '(GMT +08:00) Beijing, Hong Kong, Perth, Singapore, Taipei';
$_LANG['cfg_range']['timezone']['9'] = '(GMT +09:00) Osaka, Sapporo, Seoul, Tokyo, Yakutsk';
$_LANG['cfg_range']['timezone']['9.5'] = '(GMT +09:30) Adelaide, Darwin';
$_LANG['cfg_range']['timezone']['10'] = '(GMT +10:00) Canberra, Guam, Melbourne, Sydney, Vladivostok';
$_LANG['cfg_range']['timezone']['11'] = '(GMT +11:00) Magadan, New Caledonia, Solomon Islands';
$_LANG['cfg_range']['timezone']['12'] = '(GMT +12:00) Auckland, Wellington, Fiji, Marshall Island';

$_LANG['cfg_range']['upload_size_limit']['-1'] = '服務默認設置';
$_LANG['cfg_range']['upload_size_limit']['0'] = '0KB';
$_LANG['cfg_range']['upload_size_limit']['64'] = '64KB';
$_LANG['cfg_range']['upload_size_limit']['128'] = '128KB';
$_LANG['cfg_range']['upload_size_limit']['256'] = '256KB';
$_LANG['cfg_range']['upload_size_limit']['512'] = '512KB';
$_LANG['cfg_range']['upload_size_limit']['1024'] = '1MB';
$_LANG['cfg_range']['upload_size_limit']['2048'] = '2MB';
$_LANG['cfg_range']['upload_size_limit']['4096'] = '4MB';
$_LANG['cfg_range']['visit_stats']['on'] = '開啟';
$_LANG['cfg_range']['visit_stats']['off'] = '關閉';

$_LANG['rewrite_confirm_apache'] = "URL Rewrite 功能要求您的 Web Server 必須是 Apache，\\n並且起用了 rewrite 模塊。\\n同時請您確認是否已經將htaccess.txt文件重命名為.htaccess。\\n如果伺服器上還有其他的重寫規則請去掉注釋,請將RewriteBase行的注釋去掉,並將路徑設置為伺服器請求的絕對路徑";
$_LANG['rewrite_confirm_iis'] = "URL Rewrite 功能要求您的 Web Server 必須安裝IIS，\\n並且起用了 ISAPI Rewrite 模塊。\\n如果您使用的是ISAPI Rewrite商業版，請您確認是否已經將httpd.txt文件重命名為httpd.ini。如果您使用的是ISAPI Rewrite免費版，請您確認是否已經將httpd.txt文件內的內容復制到ISAPI Rewrite安裝目錄中httpd.ini里。";
$_LANG['gzip_confirm'] = "GZip 功能需要您的伺服器支持 zlib 擴展庫。\\n如果您發現開啟Gzip後頁面出現亂碼，可能是您的伺服器已經開啟了Gzip，您不需要在 DSC 中再次開啟。";
$_LANG['retain_original_confirm'] = "如果您不保留商品原圖，在「圖片批量處理」的時候，\\n將不會重新生成不包含原圖的商品圖片。請慎重使用該功能！";
$_LANG['msg_invalid_file'] = '您上傳了一個非法的文件類型。該文件名為：%s';
$_LANG['msg_upload_failed'] = '上傳文件 %s 失敗，請檢查 %s 目錄是否可寫。';
$_LANG['smtp_ssl_confirm'] = '此功能要求您的php必須支持OpenSSL模塊, 如果您要使用此功能，請聯系您的空間商確認支持此模塊';

/* 郵件設置語言項 */
$_LANG['cfg_name']['mail_service'] = '郵件服務';
$_LANG['cfg_desc']['mail_service'] = '如果您選擇了採用伺服器內置的 Mail 服務，您不需要填寫下面的內容。';
$_LANG['cfg_range']['mail_service'][0] = '採用伺服器內置的 Mail 服務';
$_LANG['cfg_range']['mail_service'][1] = '採用其他的 SMTP 服務';

$_LANG['cfg_name']['smtp_host'] = '發送郵件伺服器地址(SMTP)';
$_LANG['cfg_name']['smtp_port'] = '伺服器埠';
$_LANG['cfg_name']['smtp_user'] = '郵件發送帳號';
$_LANG['cfg_name']['smtp_pass'] = '帳號密碼';
$_LANG['cfg_name']['smtp_mail'] = '郵件回復地址';
$_LANG['cfg_name']['mail_charset'] = '郵件編碼';
$_LANG['cfg_name']['smtp_ssl'] = '郵件伺服器是否要求加密連接(SSL)';
$_LANG['cfg_range']['smtp_ssl'][0] = '否';
$_LANG['cfg_range']['smtp_ssl'][1] = '是';

$_LANG['mail_settings_note'] = '<li>如果您的伺服器支持 Mail 函數（具體信息請咨詢您的空間提供商）。我們建議您使用系統的 Mail 函數。</li><li>當您的伺服器不支持 Mail 函數的時候您也可以選用 SMTP 作為郵件伺服器。</li>';

$_LANG['save_success'] = '保存商店設置成功。';
$_LANG['mail_save_success'] = '郵件伺服器設置成功。';
$_LANG['sendemail_success'] = '恭喜！測試郵件已成功發送到 ';
$_LANG['sendemail_false'] = '郵件發送失敗，請檢查您的郵件伺服器設置！';
$_LANG['seller_save_success'] = '店鋪設置成功。';

$_LANG['js_languages']['smtp_host_empty'] = '您沒有填寫郵件伺服器地址!';
$_LANG['js_languages']['smtp_port_empty'] = '您沒有填寫伺服器埠!';
$_LANG['js_languages']['reply_email_empty'] = '您沒有填寫郵件回復地址!';
$_LANG['js_languages']['test_email_empty'] = '您沒有填寫發送測試郵件的地址!';
$_LANG['js_languages']['email_address_same'] = '郵件回復地址與發送測試郵件的地址不能相同!';

$_LANG['cfg_name']['wap'] = 'WAP設置';
$_LANG['cfg_name']['wap_config'] = '是否使用WAP功能';
$_LANG['cfg_range']['wap_config'][0] = '關閉';
$_LANG['cfg_range']['wap_config'][1] = '開啟';
$_LANG['cfg_name']['wap_logo'] = '微信自定義分享LOGO圖片';
$_LANG['cfg_desc']['wap_logo'] = '推薦尺寸 800*800，png格式';

//手機端 補丁 start
$_LANG['cfg_name']['wap_index_pro'] = '是否開啟首頁APP廣告位';
$_LANG['cfg_range']['wap_index_pro'][1] = '開啟';
$_LANG['cfg_range']['wap_index_pro'][0] = '關閉';

$_LANG['cfg_name']['wap_category'] = '分類模板';
$_LANG['cfg_range']['wap_category'][0] = '默認模式';
$_LANG['cfg_range']['wap_category'][1] = '簡單模式';

$_LANG['cfg_name']['use_lbs'] = '城市定位';
$_LANG['cfg_range']['use_lbs'][0] = '關閉';
$_LANG['cfg_range']['use_lbs'][1] = '開啟';
$_LANG['cfg_desc']['use_lbs'] = '是否開啟首頁城市定位功能';

$_LANG['cfg_name']['wap_app'] = '首頁APP客戶端下載地址';
$_LANG['cfg_desc']['wap_app'] = '推薦填寫發布在第三方應用市場的下載鏈接';
//手機端 補丁 end

$_LANG['cfg_desc']['wap_config'] = '此功能只支持簡體中文且只在中國大陸區有效';
$_LANG['cfg_name']['recommend_order'] = '推薦商品排序';
$_LANG['cfg_desc']['recommend_order'] = '推薦排序適合少量推薦，隨機顯示大量推薦';
$_LANG['cfg_range']['recommend_order'][0] = '推薦排序';
$_LANG['cfg_range']['recommend_order'][1] = '隨機顯示';

$_LANG['invoice_type'] = '類型';
$_LANG['invoice_rate'] = '稅率（％）';
$_LANG['back_shop_config'] = '返回商店設置';
$_LANG['back_mail_settings'] = '返回郵件伺服器設置';
$_LANG['mail_settings'] = '郵件伺服器設置';

$_LANG['back_seller_settings'] = '返回店鋪設置';

$_LANG['sms_success'] = '簡訊設置成功。';
$_LANG['back_sms_settings'] = '返回簡訊設置';

$_LANG['cloud_success'] = '文件存儲設置成功。';
$_LANG['back_cloud_settings'] = '返迴文件存儲設置';

//@author guan start
$_LANG['cfg_name']['two_code_logo'] = '二維碼中間LOGO';
$_LANG['cfg_name']['two_code_links'] = '網站域名';
$_LANG['cfg_name']['two_code_mouse'] = '提示文字';
$_LANG['cfg_desc']['two_code_mouse'] = '提示文字為滑鼠放在二維碼上面的出現的文字提示';
$_LANG['cfg_desc']['two_code_logo'] = '如果不上傳logo將使用每個商品的圖片作為logo';
$_LANG['cfg_desc']['two_code_links'] = '域名為商品跳轉的地址，系統會自動匹配每個商品的id。域名格式：http://www.xxxxx.com';
$_LANG['cfg_name']['two_code'] = '商品二維碼';
$_LANG['cfg_range']['two_code']['1'] = '開啟';
$_LANG['cfg_range']['two_code']['0'] = '關閉';
$_LANG['delete_two_logo'] = '你確認要刪除LOGO圖片嗎？';
$_LANG['delete_two_text'] = '刪除LOGO圖片';
//@author guan end


//$_LANG['sms_url'] = '<a href="'.$url.'" target="_blank">點此注冊手機簡訊服務</a>';

//分類非同步載入商品設置 by wu
$_LANG['cfg_name']['category_load_type'] = '商品載入方式';
$_LANG['cfg_range']['category_load_type']['0'] = '默認分頁';
$_LANG['cfg_range']['category_load_type']['1'] = 'AJAX瀑布流';
$_LANG['cfg_desc']['category_load_type'] = '設置分類/搜索/促銷活動（團購活動、積分商城、奪寶奇兵、拍賣活動等）頁下商品載入的方式';

$_LANG['cfg_name']['grade_apply_time'] = '店鋪等級/模板未支付申請時效性';//by kong grade
$_LANG['cfg_desc']['grade_apply_time'] = '店鋪等級訂單到期後狀態修改為無效，模板訂單到期後直接刪除。默認（一）。單位（天）';//by kong grade
$_LANG['cfg_name']['apply_options'] = '店鋪升級剩餘預付款處理';
$_LANG['cfg_range']['apply_options']['1'] = '補差價，退款';
$_LANG['cfg_range']['apply_options']['2'] = '補差價，不退款';

//傭金模式 by wu
$_LANG['cfg_name']['commission_model'] = '店鋪傭金模式';
$_LANG['cfg_range']['commission_model']['0'] = '店鋪傭金比例';
$_LANG['cfg_range']['commission_model']['1'] = '分類傭金比例';

$_LANG['cfg_name']['commission_percent'] = '固定傭金百分比';
$_LANG['cfg_desc']['commission_percent'] = '% （店鋪統一傭金比例）';

//商家入駐默認密碼前綴
$_LANG['cfg_name']['merchants_prefix'] = '店鋪密碼前綴';
$_LANG['cfg_desc']['merchants_prefix'] = '店鋪入駐成功後，默認密碼前綴';

//登錄錯誤限制
$_LANG['cfg_name']['login_limited_num'] = '登錄錯誤限制次數';

//導航分類模式 by wu
$_LANG['cfg_name']['nav_cat_model'] = '導航分類模式';
$_LANG['cfg_range']['nav_cat_model']['1'] = '只顯示頂級分類';
$_LANG['cfg_range']['nav_cat_model']['0'] = '顯示頂級分類和次級分類';

//首頁文章分類
$_LANG['cfg_name']['index_article_cat'] = '首頁文章欄目';
$_LANG['cfg_desc']['index_article_cat'] = '首頁輪播圖右側文章欄目，請填寫文章分類ID，多個分類使用半形逗號（,）分隔（註：2017模板使用）';

$_LANG['cfg_name']['marticle_index'] = '入駐首頁底部文章';
$_LANG['cfg_desc']['marticle_index'] = '入駐首頁底部文章，文本框中填寫文章ID（格式：1,2,3 註：逗號為英文逗號）';

//可視化開關
$_LANG['cfg_name']['openvisual'] = '是否啟用首頁可視化';
$_LANG['cfg_range']['openvisual']['0'] = '關閉';
$_LANG['cfg_range']['openvisual']['1'] = '啟用';
$_LANG['cfg_desc']['openvisual'] = "開啟首頁可視化後前台首頁將調用可視化模板，如果沒有可視化模板則繼續調用默認模板";

//首頁彈出廣告
$_LANG['cfg_name']['bonus_adv'] = '是否開啟首頁彈出廣告';
$_LANG['cfg_range']['bonus_adv']['0'] = '關閉';
$_LANG['cfg_range']['bonus_adv']['1'] = '啟用';
$_LANG['cfg_desc']['bonus_adv'] = "可視化首頁彈出廣告在可視化裝修中設置，默認模板彈出在廣告位『首頁天降紅包』中添加";

//會員是否開啟批發求購單
$_LANG['cfg_name']['wholesale_user_rank'] = '是否支持注冊會員使用批發功能';
$_LANG['cfg_range']['wholesale_user_rank']['0'] = '否';
$_LANG['cfg_range']['wholesale_user_rank']['1'] = '是';

//是否開啟舉報功能
$_LANG['cfg_name']['is_illegal'] = '是否開啟舉報功能';
$_LANG['cfg_range']['is_illegal']['0'] = '關閉';
$_LANG['cfg_range']['is_illegal']['1'] = '啟用';

//舉報配置
$_LANG['cfg_name']['report_handle'] = '惡意舉報處罰措施';
$_LANG['cfg_range']['report_handle']['0'] = '關閉';
$_LANG['cfg_range']['report_handle']['1'] = '開啟';
$_LANG['cfg_desc']['report_handle'] = '開啟該配置後，惡意舉報的會員一定時間內將剝奪舉報權利！';

$_LANG['cfg_name']['report_time'] = '舉報時效';
$_LANG['cfg_desc']['report_time'] = '開啟該配置後，舉報將在到期後將修改狀態為無效舉報（單位：天）';

$_LANG['cfg_name']['report_handle_time'] = '惡意舉報處罰時間';
$_LANG['cfg_desc']['report_handle_time'] = '審核為惡意舉報後，該會員將凍結舉報許可權天數（單位：天）';

$_LANG['cfg_name']['receipt_time'] = '訂單投訴時效性';
$_LANG['cfg_desc']['receipt_time'] = '確認收貨後多少天內可申請交易糾紛，默認為15天（單位：天）';

//拼團
$_LANG['cfg_name']['virtual_order'] = '是否顯示首頁訂單提示輪播';
$_LANG['cfg_range']['virtual_order']['0'] = '不顯示';
$_LANG['cfg_range']['virtual_order']['1'] = '顯示';

$_LANG['cfg_name']['virtual_limit_nim'] = '是否顯示寫入虛擬已參團人數';
$_LANG['cfg_range']['virtual_limit_nim']['0'] = '不顯示';
$_LANG['cfg_range']['virtual_limit_nim']['1'] = '顯示';

//b2b 批發首頁文章分類
$_LANG['cfg_name']['wholesale_article_cat'] = '批發首頁文章欄目';
$_LANG['cfg_desc']['wholesale_article_cat'] = '批發首頁輪播圖右側文章欄目，請填寫文章分類ID，多個分類使用半形逗號（,）分隔（註：2017模板使用）';

//商品設置
$_LANG['cfg_name']['goods_base'] = '基本設置';
$_LANG['cfg_name']['goods_display'] = '顯示設置';
$_LANG['cfg_name']['goods_page'] = '頁面設置';
$_LANG['cfg_name']['goods_picture'] = '圖片設置';

$_LANG['cfg_name']['template_pay_type'] = '重復付費設置';
$_LANG['cfg_range']['template_pay_type']['0'] = '開啟';
$_LANG['cfg_range']['template_pay_type']['1'] = '關閉';
$_LANG['cfg_desc']['template_pay_type'] = '開啟後，重復購買模板時需要再次付費。關閉後，重復購買的則直接上傳為商家模板，無需再次付費';

//會員提現手續費
$_LANG['cfg_name']['deposit_fee'] = '會員提現手續';
$_LANG['cfg_desc']['deposit_fee'] = '%';

//會員提現手續費
$_LANG['cfg_name']['activation_number_type'] = '被拒退換貨激活設置';
$_LANG['cfg_desc']['activation_number_type'] = '退換貨如果被拒，會員可以重新激活次數，默認為：2次';

$_LANG['cfg_name']['register_article_id'] = '會員注冊協議文章ID';

//商家後台首頁文章（商家幫助）
$_LANG['cfg_name']['seller_index_article'] = '商家後台首頁文章分類';
$_LANG['cfg_desc']['seller_index_article'] = '商家幫助文章分類ID ';

//會員提現、充值限額
$_LANG['cfg_name']['buyer_cash'] = '買家提現最低金額';
$_LANG['cfg_desc']['buyer_cash'] = '設置買家提現最低金額，0表示不限';

//會員提現、充值限額
$_LANG['cfg_name']['buyer_recharge'] = '買家充值最低金額';
$_LANG['cfg_desc']['buyer_recharge'] = '設置買家充值最低金額，0表示不限';

//首页登录右侧设置
$_LANG['cfg_name']['login_right'] = '首頁登錄右側';
$_LANG['cfg_name']['login_right_link'] = '首頁登錄右側鏈接';

//logo設置
$_LANG['cfg_desc']['index_down_logo'] = '建議圖標尺寸130*33，png格式（1.0老模板使用）';
$_LANG['cfg_desc']['user_login_logo'] = '建議圖標尺寸159*57，png格式';
$_LANG['cfg_desc']['login_logo_pic'] = '上傳圖片格式必須是gif,jpg,jpeg,png;圖片大小在200kb之內';
$_LANG['cfg_desc']['admin_login_logo'] = '建議圖標尺寸130*43，png格式';
$_LANG['cfg_desc']['admin_logo'] = '建議圖標尺寸174*48，png格式';
$_LANG['cfg_desc']['seller_login_logo'] = '建議圖標尺寸172*51，png格式';
$_LANG['cfg_desc']['seller_logo'] = '建議圖標尺寸84*29，png格式';
$_LANG['cfg_desc']['stores_login_logo'] = '建議圖標尺寸140*82，png格式';
$_LANG['cfg_desc']['stores_logo'] = '建議圖標尺寸90*32，png格式';
$_LANG['cfg_desc']['order_print_logo'] = '建議圖標尺寸159*57，png格式';

//賣場-是否開啟
$_LANG['cfg_name']['region_store_enabled'] = '是否開啟賣場功能';
$_LANG['cfg_range']['region_store_enabled']['0'] = '關閉';
$_LANG['cfg_range']['region_store_enabled']['1'] = '啟用';

//延遲收貨申請
$_LANG['cfg_name']['open_order_delay'] = '是否開啟延遲收貨';
$_LANG['cfg_range']['open_order_delay']['0'] = '關閉';
$_LANG['cfg_range']['open_order_delay']['1'] = '啟用';

$_LANG['cfg_name']['order_delay_num'] = '延遲收貨申請次數';
$_LANG['cfg_desc']['order_delay_num'] = '單個訂單可申請延遲收貨次數';

$_LANG['cfg_name']['order_delay_day'] = '申請延遲收貨時間設置';
$_LANG['cfg_desc']['order_delay_day'] = '自動確認收貨前多少天可申請延遲收貨';

$_LANG['cfg_name']['pay_effective_time'] = "支付有效時間（分鍾）";
$_LANG['cfg_desc']['pay_effective_time'] = '下單支付有效時間,過時訂單失效,為0或小於0則不限時間（小數點後無效）';

//商家設置商品分期
$_LANG['cfg_name']['seller_stages'] = '商家設置商品分期';
$_LANG['cfg_range']['seller_stages']['0'] = '關閉';
$_LANG['cfg_range']['seller_stages']['1'] = '開啟';

$_LANG['js_languages']['seller_info_notic'] = '商店名稱不能為空';
$_LANG['btnSubmit_notice'] = '商店設置 -> 購物流程 -> 發票稅率重復：';
$_LANG['type_already_exists'] = '類型已存在';
$_LANG['type_taxrate_not_notic'] = '類型和稅率不能為空';
$_LANG['taxrate_number'] = '稅率必須為數字';

$_LANG['tutorials_bonus_list_one'] = '<a href="http://help.ecmoban.com/article-6273.html" target="_blank">商店設置---平台基本信息設置</a>
                            <a href="http://help.ecmoban.com/article-6274.html" target="_blank">商店設置---客服設置</a>
                            <a href="http://help.ecmoban.com/article-6268.html" target="_blank">商店設置---市場價格比例及積分比例設置</a>
                            <a href="http://help.ecmoban.com/article-6271.html" target="_blank">商店設置---設置區分地區商品</a>
                            <a href="http://help.ecmoban.com/article-6266.html" target="_blank">商店設置---簡訊設置</a>
                            <a href="http://help.ecmoban.com/article-6267.html" target="_blank">商店設置---WAP設置</a>
                            <a href="http://help.ecmoban.com/article-6265.html" target="_blank">商店設置---商品屬性價格模式設置</a>';

$_LANG['tutorials_bonus_list_two'] = '商城郵件伺服器設置';

$_LANG['operation_prompt_content']['shop_config'][0] = '商店相關信息設置，請謹慎填寫信息。';

return $_LANG;
