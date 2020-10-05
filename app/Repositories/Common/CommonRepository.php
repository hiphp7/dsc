<?php

namespace App\Repositories\Common;

use App\Kernel\Repositories\Common\CommonRepository as Base;
use App\Models\DrpShop;
use App\Models\Sms;
use App\Models\Users;
use App\Services\Drp\DrpConfigService;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class CommonRepository
 * @method getManageIsOnly($object, $where = []) 查询是否已存在值
 * @method shippingInstance($shipping_code = '') 返回配送方式实例
 * @method paymentInstance($pay_code = '') 返回支付方式实例
 * @method getComboGodosAttr($attr_array = [], $goods_attr_id = 0) 组合购买商品属性
 * @method getAttrValues($values = []) 获取属性设置默认值是否大于0
 * @package App\Repositories\Common
 */
class CommonRepository extends Base
{

    /**
     * 发送短信
     *
     * @param int $mobile
     * @param string $content
     * @param string $send_time
     * @param bool $msg
     * @param array $sms_list
     * @return bool
     * @throws \Exception
     */
    public function smsSend($mobile = 0, $content = '', $send_time = '', $msg = true, $sms_list = [])
    {

        $sms = Sms::query()->get();
        $sms = $sms ? $sms->toArray() : [];

        if (empty($sms_list)) {
            if (!empty($sms)) {
                $sms_list = parent::sms_list($sms);
                $sms_list['is_sms'] = count($sms);
            } else {
                $sms_list = parent::Chuanglan();
            }
        }

        return parent::smsSend($mobile, $content, $send_time, $msg, $sms_list);
    }

    /**
     * 邮件发送
     *
     * @param string $name 接收人姓名
     * @param string $email 接收人邮件地址
     * @param string $subject 邮件标题
     * @param string $content 邮件内容
     * @param int $type 0 普通邮件， 1 HTML邮件
     * @param bool $notification true 要求回执， false 不用回执
     * @return bool
     * @throws \Exception
     */
    public function sendEmail($name = '', $email = '', $subject = '', $content = '', $type = 0, $notification = false)
    {
        $shop_name = '';
        /* 如果邮件编码不是EC_CHARSET，创建字符集转换对象，转换编码 */
        if ($this->config['mail_charset'] != EC_CHARSET) {
            $name = dsc_iconv(EC_CHARSET, $this->config['mail_charset'], $name);
            $subject = dsc_iconv(EC_CHARSET, $this->config['mail_charset'], $subject);
            $content = dsc_iconv(EC_CHARSET, $this->config['mail_charset'], $content);
            $shop_name = dsc_iconv(EC_CHARSET, $this->config['mail_charset'], $this->config['shop_name']);
        }

        /* 获得邮件服务器的参数设置 */
        $config = [
            'driver' => 'smtp',
            'host' => $this->config['smtp_host'],
            'port' => $this->config['smtp_port'],
            'from' => [
                'address' => $this->config['smtp_mail'],
                'name' => $shop_name,
            ],
            'encryption' => intval($this->config['smtp_ssl']) > 0 ? 'ssl' : null,
            'username' => $this->config['smtp_user'],
            'password' => $this->config['smtp_pass'],
        ];

        config(['mail' => array_merge(config('mail'), $config)]);

        (new MailServiceProvider(app()))->register();

        try {
            Mail::raw($content, function ($m) use ($name, $email, $subject) {
                $m->to($email, $name)->subject($subject);
            });
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            $GLOBALS['err']->add(lang('common.sendemail_false') . "\n" . $exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * 判断是否支持供应链
     */
    public function judgeSupplierEnabled()
    {
        if (is_dir(app_path('Modules/Suppliers'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 保存分销推荐人uid 适用于api
     *
     * @param int $uid
     * @param int $parent_id
     * @return bool
     * @throws \Exception
     */
    public function setDrpShopAffiliate($uid = 0, $parent_id = 0)
    {
        if (empty($uid) || empty($parent_id)) {
            return false;
        }

        if (file_exists(MOBILE_DRP)) {
            // 开启分销
            $drp_config = app(DrpConfigService::class)->drpConfig('drp_affiliate_on');
            $drp_affiliate = $drp_config['value'] ?? 0;
            if ($drp_affiliate == 1) {

                $config = $this->config['affiliate'] ? unserialize($this->config['affiliate']) : [];

                if (!empty($config['config']['expire'])) {
                    if ($config['config']['expire_unit'] == 'hour') {
                        $expiresAt = Carbon::now()->addHours($config['config']['expire']);
                    } elseif ($config['config']['expire_unit'] == 'day') {
                        $expiresAt = Carbon::now()->addDays($config['config']['expire']);
                    } elseif ($config['config']['expire_unit'] == 'week') {
                        $expiresAt = Carbon::now()->addWeeks($config['config']['expire']);
                    } else {
                        $expiresAt = Carbon::now()->addDays(1);
                    }

                    cache()->put('dscmall_affiliate_drp_id' . $uid, intval($parent_id), $expiresAt);
                } else {
                    // 过期时间为 1 天
                    cache()->put('dscmall_affiliate_drp_id' . $uid, intval($parent_id), Carbon::now()->addDays(1));
                }
            }
        }
    }

    /**
     * 获取分销推荐uid 适用于api
     *
     * @param int $uid
     * @param int $forget
     * @return \Illuminate\Cache\CacheManager|int|mixed
     * @throws \Exception
     */
    public function getDrpShopAffiliate($uid = 0, $forget = 0)
    {
        $parent_id = cache('dscmall_affiliate_drp_id' . $uid);

        if (!is_null($parent_id)) {
            $parent_id = intval($parent_id);

            // 检查是否分销商
            $count = DrpShop::where('user_id', $parent_id)->where('audit', 1)->count();

            if ($forget > 0) {
                cache()->forget('dscmall_affiliate_drp_id' . $uid); // 失效
            }

            if ($count > 0) {
                return $parent_id;
            } else {
                return 0;
            }
        }

        return 0;
    }

    /**
     * 保存分销推荐人uid 适用于web
     *
     * @param int $uid
     * @return bool
     * @throws \Exception
     */
    public function setDrpAffiliate($uid = 0)
    {
        if (empty($uid)) {
            return false;
        }

        if (file_exists(MOBILE_DRP)) {
            // 开启分销
            $drp_config = app(DrpConfigService::class)->drpConfig('drp_affiliate_on');
            $drp_affiliate = $drp_config['value'] ?? 0;
            if ($drp_affiliate == 1) {

                $config = $this->config['affiliate'] ? unserialize($this->config['affiliate']) : [];

                if (!empty($config['config']['expire'])) {
                    if ($config['config']['expire_unit'] == 'hour') {
                        $minutes = $config['config']['expire'] * 60; // 小时
                    } elseif ($config['config']['expire_unit'] == 'day') {
                        $minutes = $config['config']['expire'] * 24 * 60;// 天
                    } elseif ($config['config']['expire_unit'] == 'week') {
                        $minutes = $config['config']['expire'] * 7 * 24 * 60; // 周
                    } else {
                        $minutes = 24 * 60;// 天
                    }
                    // 过期时间（以分钟为单位）
                    cookie()->queue('dscmall_affiliate_drp_id', intval($uid), $minutes);
                } else {
                    // 过期时间（以分钟为单位）
                    $minutes = 24 * 60;
                    cookie()->queue('dscmall_affiliate_drp_id', intval($uid), $minutes);
                }
            }
        }
    }

    /**
     * 获取分销推荐uid 适用于 web
     *
     * @return \Illuminate\Cache\CacheManager|int|mixed
     * @throws \Exception
     */
    public function getDrpAffiliate()
    {
        $uid = request()->cookie('dscmall_affiliate_drp_id');

        if (!is_null($uid) && $uid > 0) {
            $uid = intval($uid);

            // 检查是否分销商
            $count = DrpShop::where('user_id', $uid)->where('audit', 1)->count();

            if ($count > 0) {
                return $uid;
            } else {
                cookie()->queue('dscmall_affiliate_drp_id', '', 1);
            }
        }

        return 0;
    }

    /**
     * 保存会员推荐uid
     *
     * @param int $uid
     * @return bool
     * @throws \Exception
     */
    public function setUserAffiliate($uid = 0)
    {
        if (empty($uid)) {
            return false;
        }

        $config = $this->config['affiliate'] ? unserialize($this->config['affiliate']) : [];

        if (!empty($uid) && $config['on'] == 1) {
            if (!empty($config['config']['expire'])) {
                if ($config['config']['expire_unit'] == 'hour') {
                    $minutes = $config['config']['expire'] * 60; // 小时
                } elseif ($config['config']['expire_unit'] == 'day') {
                    $minutes = $config['config']['expire'] * 24 * 60;// 天
                } elseif ($config['config']['expire_unit'] == 'week') {
                    $minutes = $config['config']['expire'] * 7 * 24 * 60; // 周
                } else {
                    $minutes = 24 * 60;// 天
                }

                // 过期时间（以分钟为单位）
                cookie()->queue('dscmall_affiliate_uid', intval($uid), $minutes);
            } else {
                // 过期时间（以分钟为单位）
                $minutes = 24 * 60;
                cookie()->queue('dscmall_affiliate_uid', intval($uid), $minutes);
            }
        }
    }

    /**
     * 获取会员推荐uid
     *
     * @return int
     */
    public function getUserAffiliate()
    {
        $uid = request()->cookie('dscmall_affiliate_uid');

        if (!is_null($uid) && $uid > 0) {
            $uid = intval($uid);

            $count = Users::where('user_id', $uid)->count();

            if ($count > 0) {
                return $uid;
            } else {
                cookie()->queue('dscmall_affiliate_uid', '', 1);
            }
        }

        return 0;
    }

    /**
     * 查询票税金额
     *
     * @param int $goods_price
     * @param string $inv_content
     * @return float|int
     */
    public function orderInvoiceTotal($goods_price = 0, $inv_content = '')
    {
        $invoice = $this->getInvoiceList($this->config['invoice_type'], 1, $inv_content);

        $tax = 0;
        if ($invoice) {
            $rate = floatval($invoice['rate']) / 100;
            if ($rate > 0) {
                $tax = $rate * $goods_price;
            }
        }

        return $tax;
    }

    /**
     * 获取票税列表
     *
     * @param $invoice
     * @param int $order_type
     * @param string $inv_content
     * @return array
     */
    public function getInvoiceList($invoice, $order_type = 0, $inv_content = '')
    {
        $arr = [];
        if (isset($invoice['type']) && $invoice['type']) {
            $type = array_values($invoice['type']);
            $rate = array_values($invoice['rate']);

            for ($i = 0; $i < count($type); $i++) {
                if ($order_type == 1) {
                    if ($type[$i] == $inv_content) {
                        $arr['type'] = $type[$i];
                        $arr['rate'] = $rate[$i];
                    }
                } else {
                    $arr[$i]['type'] = $type[$i];
                    $arr[$i]['rate'] = $rate[$i];
                }
            }
        }

        return $arr;
    }
}
