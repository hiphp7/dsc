<?php

//授權
$_LANG['authorization_code'] = '授權碼:';
$_LANG['please_input_code'] = '請輸入<大商創X>授權碼';
$_LANG['get_code'] = '如何獲取商業授權？';

$_LANG['lab_integral'] = '積分購買金額：';
$_LANG['lab_give_integral'] = '贈送消費積分數：';
$_LANG['lab_rank_integral'] = '贈送等級積分數：';

//ecmoban模板堂 --zhuo start 倉庫
$_LANG['warehouse'] = '倉庫';
$_LANG['tab_warehouse_model'] = '倉庫模式';
$_LANG['tab_warehouse'] = '倉庫庫存';
$_LANG['warehouse_name'] = '倉庫名稱';
$_LANG['warehouse_number'] = '倉庫庫存';
$_LANG['warehouse_price'] = '倉庫價格';
$_LANG['rank_volume'] = '優惠等級';
$_LANG['attr_type'] = '顏色類型';
$_LANG['size_type'] = '尺碼類型';
$_LANG['warehouse_promote_price'] = '倉庫促銷價格';
$_LANG['drop_warehouse'] = '您確實要刪除該倉庫庫存嗎？';
$_LANG['drop_warehouse_area'] = '您確實要刪除該倉庫地區價格嗎？';

//地區價格模式
$_LANG['warehouse_region_name'] = '地區名稱';
$_LANG['warehouse_region_model'] = '地區模式';
$_LANG['belongs_to_warehouse'] = '所屬倉庫';
$_LANG['region_price'] = '地區價格';
$_LANG['region_number'] = '地區庫存';
$_LANG['region_promote_price'] = '地區促銷價格';
$_LANG['region_sort'] = '排序';

$_LANG['goods_info'] = '返回商品詳情頁';
$_LANG['tab_goodsModel'] = '設置商品模式';

$_LANG['tab_areaRegion'] = '關聯地區';
//ecmoban模板堂 --zhuo end 倉庫

//ecmoban模板堂 --zhuo start
$_LANG['warehouse_spec_price'] = '倉庫屬性價格';
$_LANG['area_spec_price'] = '地區屬性價格';
$_LANG['add_attr_img'] = '添加屬性圖片';
$_LANG['confirm_drop_img'] = '你確認要刪除該圖標嗎？';
$_LANG['drop_attr_img'] = '刪除圖標';
$_LANG['drop_attr_img_success'] = '刪除屬性圖片成功';
$_LANG['add_brand_success'] = '添加品牌成功！';
$_LANG['add_brand_fail'] = '添加品牌異常！';
$_LANG['brand_name_repeat'] = '品牌名稱重復！';

$_LANG['edit_freight_template_success'] = '編輯運費模板成功！';
$_LANG['article_title_repeat'] = '文章標題重復！';
$_LANG['add_article_success'] = '添加文章成功！';
$_LANG['add_article_fail'] = '添加文章異常！';

$_LANG['region_name_not_null'] = '地區名稱不能為空！';
$_LANG['region_name_repeat'] = '地區名稱重復！';
$_LANG['add_region_fail'] = '添加地區異常！';

$_LANG['cat_prompt_notic_one'] = '平台最多隻能設置三級分類';
$_LANG['cat_prompt_notic_two'] = '您目前的許可權只能添加四級分類';
$_LANG['cat_prompt_file_size'] = '上傳圖片不得大於200kb';
$_LANG['cat_prompt_file_type'] = '請上傳jpg,gif,png,jpeg格式圖片';
$_LANG['commission_rate_prompt'] = '傭金比率為0-100以內，請重新設置';

$_LANG['cate_name_not_repeat'] = '同級別下不能有重復的分類名稱！';
$_LANG['price_section_range'] = '價格區間數超過范圍！';
$_LANG['cat_add_success'] = '分類添加成功！';
//ecmoban模板堂 --zhuo end

/*------------------------------------------------------ */
//-- 商品相冊  by kong  start
/*------------------------------------------------------ */

$_LANG['img_count'] = '圖片排序';
$_LANG['img_url'] = '上傳文件';
$_LANG['img_file'] = '圖片外部鏈接地址';
$_LANG['remind'] = '友情提醒：請勿同時上傳本地圖片與外部鏈接圖片！';

//辦事處
$_LANG['label_region'] = '管轄地區';

//大商創1.5版本新增
$_LANG['category_name'] = '分類名稱';
$_LANG['brand_name'] = '品牌名稱';

//智能權重語言包
$_LANG['intelligent_goods_name'] = '商品名稱：';
$_LANG['intelligent_goods_number'] = '商品購買數量';
$_LANG['intelligent_return_number'] = '商品退換貨數量';
$_LANG['intelligent_user_number'] = '購買此商品的會員數量';
$_LANG['intelligent_goods_comment_number'] = '對此商品評價數量';
$_LANG['intelligent_merchants_comment_number'] = '對此商品的商家評價數量';
$_LANG['intelligent_user_attention_number'] = '會員關注此商品數量';
$_LANG['intelligent_manual_intervention'] = '人工干預值';

$_LANG['intelligent_notice'][0] = '注意：1、智能權重統計購買數量必須是會員「確認收貨」後的訂單。';
$_LANG['intelligent_notice'][1] = '2、智能權重默認不會統計出之前的老數據，在統計過一次後會把老數據統計出來，升級用戶會看到第一次統計值不一致，是因為把老數據載入出來了。';

return $_LANG;
