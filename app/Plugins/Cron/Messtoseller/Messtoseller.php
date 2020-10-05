<?php

namespace App\Plugins\Cron\Messtoseller;

use App\Models\AutoSms;
use App\Models\MerchantsGrade;
use App\Models\MerchantsShopInformation;
use App\Repositories\Common\CommonRepository;

$cron_lang = __DIR__ . '/Languages/' . config('shop.lang') . '.php';

if (file_exists($cron_lang)) {
    require_once($cron_lang);
}

$debug = config('app.debug'); // true 开启日志 false 关闭日志

$time = $this->timeRepository->getGmTime();
$limit = !empty($cron['auto_mess_to_seller_count']) ? $cron['auto_mess_to_seller_count'] : 5;//自动操作数量
$before_day = !empty($cron['auto_mess_to_seller_day']) ? $cron['auto_mess_to_seller_day'] : 5; // 到期多少天前提醒


// 查询审核通过商家
$model = MerchantsShopInformation::where('merchants_audit', 1)->where('shop_close', 1);

$model = $model->whereHas('getUsers');
$model = $model->whereHas('sellershopinfo');

$model = $model->with([
    'getUsers' => function ($query) {
        $query->select('user_id', 'user_name', 'mobile_phone', 'email');
    },
    //'sellershopinfo'
]);

$shop_information = $model->orderBy('shop_id', 'ASC')->get();

$shop_information = $shop_information ? $shop_information->toArray() : [];

$autodb = [];
if (!empty($shop_information)) {
    foreach ($shop_information as $key => $value) {

        $value = collect($value)->merge($value['get_users'])->except('get_users')->all();

        // 店铺等级
        $grade_info = get_seller_grade_auto($value['user_id']);
        if (!empty($grade_info)) {
            $grade_end_time = $grade_info['add_time'] + 365 * 24 * 60 * 60 * $grade_info['year_num'];
            if ($time + $before_day * 24 * 60 * 60 == $grade_end_time || $time == $grade_end_time) {

                // 插入消息队列表
                insert_auto_sms($value['user_id'], 5, $time);

                $autodb[$key]['ru_id'] = $value['user_id'];
                $autodb[$key]['grade_end_time'] = $grade_end_time;
                $autodb[$key]['email'] = $value['email'];
                $autodb[$key]['mobile_phone'] = $value['mobile_phone'];
                $autodb[$key]['user_name'] = $value['user_name'];
            }
        }
    }
}

// 查询队列
$item_list = select_auto_sms(count($autodb));

if (!empty($item_list) && !empty($autodb)) {

    $new_autodb = merge_arrays($item_list, $autodb);

    $shop_name = $this->config['shop_name'];// 平台
    $send_date = $this->timeRepository->getLocalDate($this->config['time_format'], $time); // 发送时间

    foreach ($new_autodb as $k => $val) {
        /* 获取用户名和Email地址 */
        $to_email = $val['email'] ?? '';
        $to_mobile = $val['mobile_phone'];
        $user_name = $val['user_name'];

        $grade_end_time = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['grade_end_time']);

        $send_email_ok = 0;
        $send_sms_ok = 0;

        /* 邮件通知处理流程 */
        if (!empty($to_email)) {
            /* 设置留言回复模板所需要的内容信息 */
            $template = [
                'template_subject' => '店铺等级到期时间提醒',
                'is_html' => 1,
                'template_content' => '<p>亲爱的{$user_name}，你好！</p>

                <p>您的店铺等级到期时间快过期了，到期时间：“{$grade_end_time}”</p>

                如需继续使用，请您及时续费，以免造成不必要的损失。<br/><br/>
                {$shop_name}
                <p>{$send_date}</p>'
            ];

            if (!isset($GLOBALS['smarty'])) {

                $template['template_content'] = str_replace('{$user_name}', $user_name, $template['template_content']);
                $template['template_content'] = str_replace('{$grade_end_time}', $grade_end_time, $template['template_content']);
                $template['template_content'] = str_replace('{$shop_name}', "<a href='" . url('/') . "'>" . $shop_name . '</a>', $template['template_content']);
                $template['template_content'] = str_replace('{$send_date}', $send_date, $template['template_content']);

                $content = $template['template_content'];
            } else {

                $GLOBALS['smarty']->assign('user_name', $user_name);
                $GLOBALS['smarty']->assign('grade_end_time', $grade_end_time);
                $GLOBALS['smarty']->assign('shop_name', "<a href='" . url('/') . "'>" . $shop_name . '</a>');
                $GLOBALS['smarty']->assign('send_date', $send_date);

                $content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);
            }

            /* 发送邮件 */
            if (app(CommonRepository::class)->sendEmail($shop_name, $to_email, $template['template_subject'], $content, $template['is_html'])) {
                $send_email_ok = 1;
            } else {
                $send_email_ok = 0;
            }

        }

        // 发送短信提醒
        if (!empty($to_mobile)) {
            //普通订单->短信接口参数
            $smsParams = array(
                'user_name' => $user_name,
                'username' => $user_name,
                'grade_end_time' => $grade_end_time,
                'gradeendtime' => $grade_end_time,
                'shop_name' => $shop_name,
                'shopname' => $shop_name,
                'send_date' => $send_date,
                'senddate' => $send_date,
                'mobile_phone' => $to_mobile,
                'mobilephone' => $to_mobile
            );

            $resp = app(CommonRepository::class)->smsSend($to_mobile, $smsParams, 'sms_seller_grade_time');

            $send_sms_ok = ($resp == true) ? 1 : 0;
        }

        // 发送成功删除消息队列
        if ($send_email_ok == 1 || $send_sms_ok == 1) {
            delete_auto_sms($val['item_id']);
        }
    }
}


/**
 * 插入消息队列表
 * @param int $ru_id
 * @param int $item_type 5 区分 下订单发送消息1和2
 * @param int $time 时间
 * @return
 */
function insert_auto_sms($ru_id = 0, $item_type = 5, $time = 0)
{
    if (empty($ru_id)) {
        return false;
    }

    $data = [
        'item_type' => $item_type,
        'ru_id' => $ru_id,
        'order_id' => 0,
        'add_time' => $time
    ];
    return AutoSms::insert($data);
}

/**
 * 查询消息队列
 * @param integer $limit
 * @param integer $item_type
 * @return
 */
function select_auto_sms($limit = 10, $item_type = 5)
{
    // 获取队列
    $model = AutoSms::where('order_id', 0)->where('ru_id', '>', 0)->where('item_type', $item_type);

    $model = $model->limit($limit)->orderBy('item_id', 'ASC')->get();

    $item_list = $model ? $model->toArray() : [];

    return $item_list;
}

/**
 * 删除队列消息
 * @param int $item_id
 * @param int $item_type
 * @return
 */
function delete_auto_sms($item_id = 0, $item_type = 5)
{
    //发送成功则删除该条数据
    return AutoSms::where('item_id', $item_id)->where('item_type', $item_type)->delete();
}


/**
 * 查询商家等级相关信息
 * @param int $user_id
 * @return
 */
function get_seller_grade_auto($user_id = 0)
{
    $model = MerchantsGrade::where('ru_id', $user_id);

    $model = $model->with([
        'getSellerGrade'
    ]);

    $model = $model->first();

    $row = $model ? $model->toArray() : [];

    return $row;
}


if ($debug == true && $autodb) {
    logResult('==================== cron messtoseller log ====================');
    logResult($autodb);
}

