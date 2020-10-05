<?php

namespace App\Plugins\Cron\Sms;

use App\Models\AutoSms;
use App\Models\MailTemplates;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;

$cron_lang = __DIR__ . '/Languages/' . config('shop.lang') . '.php';

if (file_exists($cron_lang)) {
    require_once($cron_lang);
}

$limit = !empty($cron['auto_sms_count']) ? $cron['auto_sms_count'] : 5;

$user_id = session('user_id', 0);
$adminru = get_admin_ru_id();


if ($user_id > 0 || $adminru) {
    //获取队列(倒序，优先处理新订单)

    $item_list = AutoSms::where('order_id', '>', 0);

    if (!empty($user_id)) {
        $item_list = $item_list->where('user_id', $user_id);
    }

    $item_list = $item_list->orderBy('item_id', 'desc');

    $item_list = $item_list->take($limit);

    $item_list = app(BaseRepository::class)->getToArrayGet($item_list);

    if (count($item_list) > 0) {
        //循环处理
        foreach ($item_list as $key => $val) {

            //获取订单信息
            $row = OrderInfo::where('order_id', $val['order_id']);
            $row = app(BaseRepository::class)->getToArrayFirst($row);

            //获取商家手机、邮箱
            if ($val['ru_id'] == 0) {
                $sms_shop_mobile = $this->config['sms_shop_mobile']; //手机
                $service_email = $this->config['service_email']; //邮箱
                $shop_name = $this->config['shop_name'];
            } else {
                $seller_shopinfo = SellerShopinfo::where('ru_id', $val['ru_id']);
                $seller_shopinfo = app(BaseRepository::class)->getToArrayFirst($seller_shopinfo);

                //手机
                $sms_shop_mobile = isset($seller_shopinfo['mobile']) && !empty($seller_shopinfo['mobile']) ? $seller_shopinfo['mobile'] : '';
                //邮箱
                $service_email = isset($seller_shopinfo['seller_email']) && !empty($seller_shopinfo['seller_email']) ? $seller_shopinfo['seller_email'] : '';

                $shop_name = app(MerchantCommonService::class)->getShopName($val['ru_id'], 1);
            }

            //给商家发短信
            if ($this->config['sms_order_placed'] == '1' && $sms_shop_mobile != '' && $val['item_type'] == 1) {
                $order_region = app(OrderService::class)->getOrderUserRegion($row['order_id']);

                //普通订单->短信接口参数
                $smsParams = array(
                    'shop_name' => $shop_name,
                    'shopname' => $shop_name,
                    'order_sn' => $row['order_sn'],
                    'ordersn' => $row['order_sn'],
                    'consignee' => $row['consignee'],
                    'order_region' => $order_region,
                    'orderregion' => $order_region,
                    'address' => $row['address'],
                    'order_mobile' => $row['mobile'],
                    'ordermobile' => $row['mobile'],
                    'mobile_phone' => $sms_shop_mobile,
                    'mobilephone' => $sms_shop_mobile
                );

                app(CommonRepository::class)->smsSend($sms_shop_mobile, $smsParams, 'sms_order_placed');
            }

            //给商家发邮件
            if ($this->config['send_service_email'] == '1' && $service_email != '' && $val['item_type'] == 2) {

                //获取订单商品信息
                $cart_goods = OrderGoods::where('order_id', $val['order_id']);
                $cart_goods = app(BaseRepository::class)->getToArrayGet($cart_goods);

                $tpl = get_mail_template_cron('remind_of_new_order');

                $GLOBALS['smarty']->assign('order', $row);
                $GLOBALS['smarty']->assign('goods_list', $cart_goods);
                $GLOBALS['smarty']->assign('shop_name', $this->config['shop_name']);
                $GLOBALS['smarty']->assign('send_date', $this->timeRepository->getLocalDate($this->config['time_format']));
                $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);

                /* 发送邮件 */
                if (app(CommonRepository::class)->sendEmail($this->config['shop_name'], $service_email, $tpl['template_subject'], $content, $tpl['is_html'])) {
                    //发送成功则删除该条数据
                    AutoSms::where('item_id', $val['item_id'])->delete();
                }
            }
        }
    }
}


/**
 * 获取邮件模板
 * @param string $tpl_name
 * @return mixed
 */
function get_mail_template_cron($tpl_name = '')
{
    $row = MailTemplates::where('template_code', $tpl_name);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    return $row;
}