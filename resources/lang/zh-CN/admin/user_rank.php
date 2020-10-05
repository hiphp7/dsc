<?php

$_LANG['rank_name'] = '会员等级名称';
$_LANG['integral_min'] = '所需成长值';
$_LANG['integral_min_notice'] = '修改等级所需成长值后，部分客户会因无法达到该成长值要求而发生会员等级的变化';
$_LANG['integral_max'] = '积分上限';
$_LANG['discount'] = '初始折扣率';
$_LANG['add_user_rank'] = '添加会员等级';
$_LANG['special_rank'] = '特殊会员组';
$_LANG['show_price'] = '在商品详情页显示该会员等级的商品价格';
$_LANG['notice_special'] = '特殊会员组的会员不会随着成长值的变化而变化。';
$_LANG['add_continue'] = '继续添加会员等级';
$_LANG['back_list'] = '返回会员等级列表';
$_LANG['show_price_short'] = '显示价格';
$_LANG['notice_discount'] = '请填写为0-100的整数,如填入80，表示初始折扣率为8折';

$_LANG['rank_name_exists'] = '会员等级名 %s 已经存在。';
$_LANG['add_rank_success'] = '会员等级已经添加成功。';
$_LANG['integral_min_exists'] = '已经存在一个成长值下限为 %d 的会员等级';
$_LANG['integral_max_exists'] = '已经存在一个成长值上限为 %d 的会员等级';
$_LANG['full_max_exists'] = '超出下一等级的最大积分，请修改后重试';
$_LANG['user_rank_set'] = '成长值有效期';
$_LANG['is_open'] = '是否开启：';
$_LANG['expiry_date'] = '有效期：';
$_LANG['user_rank_notice'] = '成长值有效期开启后，会员成长值自获取之日至有效期内有效，超过有效期的成长值将会自动清零，设置之后需清除商城缓存方可生效。';
$_LANG['month'] = '月';

/* JS 语言 */
$_LANG['js_languages']['remove_confirm'] = '您确定要删除选定的会员等级吗？';
$_LANG['js_languages']['rank_name_empty'] = '您没有输入会员等级名称。';
$_LANG['js_languages']['integral_min_invalid'] = '您没有输入积分下限或者积分下限不是一个整数。';
$_LANG['js_languages']['integral_max_invalid'] = '您没有输入积分上限或者积分上限不是一个整数。';
$_LANG['js_languages']['discount_invalid'] = '您没有输入折扣率或者折扣率无效。';
$_LANG['js_languages']['integral_max_small'] = '积分上限必须大于积分下限。';
$_LANG['js_languages']['lang_remove'] = '移除';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['list'][0] = '会员等级是一种会员成长体系的对外展示形式，通过成长值的变动，帮助商家更好的维护会员关系，促进消费转化。';
$_LANG['operation_prompt_content']['list'][1] = '根据会员等级和等级所需的成长值，设置会员达到此等级后，可享受的会员权益。';

$_LANG['user_rights'] = '会员权益';
$_LANG['view_users'] = '查看会员';
$_LANG['set_show'] = '显示设置';
$_LANG['drop_confirm_rank'] = '删除该会员等级的同时该会员等级下的会员权益也将被删除，您确定要删除吗？';

$new_lang = [
    'user' => '会员',
    'edit_user_rank' => '编辑会员等级',
    'user_rank' => '会员等级',
    'user_rank_has_user' => '当前等级下有会员，不可删除',
    'user_rank_rights_tips' => [
        '商品分销/会员卡分销普通会员等级不可使用，必须配合分销权益卡使用'
    ],
    'rights_close' => '【该权益已关闭】',
];

return array_merge($_LANG, $new_lang);
