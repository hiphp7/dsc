<?php

namespace App\Services\Sms;

use App\Models\Sms;
use App\Models\SmsTemplate;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class SmsManageService
{
    protected $baseRepository;
    protected $config;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;

        /* 商城配置信息 */
        $shopConfig = cache('shop_config');
        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    /**
     * 返回sms列表数据
     *
     * @param array $send_time
     * @return array
     */
    public function getSmsList($send_time = [])
    {
        $smslist = [];

        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $row = SmsTemplate::whereRaw(1);

            $res = $record_count = $row;

            $filter['record_count'] = $record_count->count();

            /* 分页大小 */
            $filter = page_and_size($filter);

            $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

            if ($filter['start'] > 0) {
                $res = $res->skip($filter['start']);
            }

            if ($filter['page_size'] > 0) {
                $res = $res->take($filter['page_size']);
            }

            $smslist = $this->baseRepository->getToArrayGet($res);

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter);
        } else {
            $filter = $result['filter'];
        }

        if ($smslist) {
            foreach ($smslist as $k => $v) {
                $smslist[$k]['send_time'] = array_search($v['send_time'], $send_time);
                $smslist[$k]['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['add_time']);
            }
        }

        $arr = ['smslist' => $smslist, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 短信模板信息
     *
     * @param int $id
     * @return array
     */
    public function getSmsInfo($id = 0)
    {
        $info = SmsTemplate::where('id', $id);
        $info = $this->baseRepository->getToArrayFirst($info);

        return $info;
    }

    /**
     * 测试短信
     */
    public function getTestSmsParams($send_time, $test)
    {
        $smsParams['mobile_phone'] = $this->config['sms_shop_mobile'];

        switch ($send_time) {
            case 'sms_order_placed': //客户下单时

                $smsParams['consignee'] = $test['consignee'] ?? '';
                $smsParams['address'] = $test['address'] ?? '';


                $smsParams['shop_name'] = $test['shop_name'] ?? '';
                $smsParams['order_sn'] = $test['order_sn'] ?? '';
                $smsParams['order_region'] = $test['order_region'] ?? '';
                $smsParams['order_mobile'] = $test['order_mobile'] ?? '';

                $smsParams['shopname'] = $test['shopname'] ?? '';
                $smsParams['ordersn'] = $test['ordersn'] ?? '';
                $smsParams['orderregion'] = $test['orderregion'] ?? '';
                $smsParams['ordermobile'] = $test['ordermobile'] ?? '';

                break;
            case 'sms_order_payed': //客户付款时

                $smsParams['consignee'] = $test['consignee'] ?? '';
                $smsParams['address'] = $test['address'] ?? '';

                $smsParams['shop_name'] = $test['shop_name'] ?? '';
                $smsParams['order_sn'] = $test['order_sn'] ?? '';
                $smsParams['order_region'] = $test['order_region'] ?? '';
                $smsParams['order_mobile'] = $test['order_mobile'] ?? '';

                $smsParams['shopname'] = $test['shopname'] ?? '';
                $smsParams['ordersn'] = $test['ordersn'] ?? '';
                $smsParams['orderregion'] = $test['orderregion'] ?? '';
                $smsParams['ordermobile'] = $test['ordermobile'] ?? '';

                break;
            case 'sms_order_shipped': //商家发货时

                $smsParams['consignee'] = $test['consignee'] ?? '';

                $smsParams['shop_name'] = $test['shop_name'] ?? '';
                $smsParams['order_sn'] = $test['order_sn'] ?? '';
                $smsParams['user_name'] = $test['user_name'] ?? '';

                $smsParams['shopname'] = $test['shopname'] ?? '';
                $smsParams['ordersn'] = $test['ordersn'] ?? '';
                $smsParams['username'] = $test['username'] ?? '';

                break;
            case 'store_order_code': //门店提货码

                $smsParams['code'] = $test['code'] ?? '';

                $smsParams['user_name'] = $test['user_name'] ?? '';
                $smsParams['order_sn'] = $test['order_sn'] ?? '';
                $smsParams['store_address'] = $test['store_address'] ?? '';

                $smsParams['username'] = $test['username'] ?? '';
                $smsParams['ordersn'] = $test['ordersn'] ?? '';
                $smsParams['storeaddress'] = $test['storeaddress'] ?? '';

                break;
            case 'sms_signin': //客户注册时

                $smsParams['code'] = $test['code'] ?? '';
                $smsParams['product'] = $test['product'] ?? '';

                break;
            case 'sms_find_signin': //客户密码找回时

                $smsParams['code'] = $test['code'] ?? '';

                break;
            case 'sms_code': //验证码通知

                $smsParams['code'] = $test['code'] ?? '';

                break;
            case 'sms_price_notic': //商品降价时

                $smsParams['user_name'] = $test['user_name'] ?? '';
                $smsParams['goods_name'] = $test['goods_name'] ?? '';
                $smsParams['goods_sn'] = $test['goods_sn'] ?? '';
                $smsParams['goods_price'] = $test['goods_price'] ?? '';

                $smsParams['username'] = $test['username'] ?? '';
                $smsParams['goodsname'] = $test['goodsname'] ?? '';
                $smsParams['goodssn'] = $test['goodssn'] ?? '';
                $smsParams['goodsprice'] = $test['goodsprice'] ?? '';

                break;
            case 'sms_seller_signin': //修改商家密码时

                $smsParams['password'] = $test['password'] ?? '';

                $smsParams['seller_name'] = $test['seller_name'] ?? '';
                $smsParams['login_name'] = $test['login_name'] ?? '';

                $smsParams['sellername'] = $test['sellername'] ?? '';
                $smsParams['loginname'] = $test['loginname'] ?? '';

                break;
            case 'user_account_code':

                $smsParams['examine'] = $test['examine'] ?? '';

                $smsParams['user_name'] = $test['user_name'] ?? '';
                $smsParams['add_time'] = $test['add_time'] ?? '';
                $smsParams['fmt_amount'] = $test['fmt_amount'] ?? '';
                $smsParams['process_type'] = $test['process_type'] ?? '';
                $smsParams['op_time'] = $test['op_time'] ?? '';
                $smsParams['user_money'] = $test['user_money'] ?? '';

                $smsParams['username'] = $test['username'] ?? '';
                $smsParams['addtime'] = $test['addtime'] ?? '';
                $smsParams['fmtamount'] = $test['fmtamount'] ?? '';
                $smsParams['processtype'] = $test['processtype'] ?? '';
                $smsParams['optime'] = $test['optime'] ?? '';
                $smsParams['usermoney'] = $test['usermoney'] ?? '';

                break;
            case 'sms_order_received':

                $smsParams['consignee'] = $test['consignee'] ?? '';
                $smsParams['ordersn'] = $test['ordersn'] ?? '';
                $smsParams['ordermobile'] = $test['ordermobile'] ?? '';

                break;
            case 'sms_shop_order_received':

                $smsParams['username'] = $test['username'] ?? '';
                $smsParams['shop_name'] = $test['shop_name'] ?? '';
                $smsParams['ordersn'] = $test['ordersn'] ?? '';

                break;
            default:
                $smsParams = [];
        }

        return $smsParams;
    }

    /**
     * 判断是否需要填写短信签名和模板 v1.4.4 remove
     *
     * @return int
     */
    public function isSmsTempSign()
    {
        $arr = [1, 2, 4];
        if (in_array($this->config['sms_type'], $arr)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 当前默认启用短信
     * @return mixed
     */
    public function getDefaultSmsCode()
    {
        // 当前默认启用短信
        $default_code = Sms::query()->where('default', 1)->value('code');

        return $default_code;
    }

    /**
     * 判断是否需要填写短信签名和模板 v1.4.4 add
     *
     * @return int
     */
    public function isShowSmsTempSign()
    {
        // 原 $arr = [1, 2, 4];
        $code_arr = ['alidayu', 'aliyun', 'huawei'];

        $default_code = $this->getDefaultSmsCode();

        if ($default_code && in_array($default_code, $code_arr)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 短信列表
     * @return array
     */
    public function smsList()
    {
        $model = Sms::query();

        $model = $model->limit(100)
            ->orderBy('id', 'DESC')
            ->get();

        $list = $model ? $model->toArray() : [];

        if (!empty($list)) {
            foreach ($list as $k => $value) {
                $list[$k]['sms_configure'] = empty($value['sms_configure']) ? '' : unserialize($value['sms_configure']);
            }
        }

        return $list;
    }

    /**
     * 更新
     * @param string $code
     * @param array $data
     * @return bool
     */
    public function update($code = '', $data = [])
    {
        if (empty($code) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'sms');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        $data['update_time'] = $this->timeRepository->getGmTime();

        return Sms::where('code', $code)->update($data);
    }

    /**
     * 添加
     * @param string $code
     * @param array $data
     * @return bool
     */
    public function create($code = '', $data = [])
    {
        if (empty($code) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'sms');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        $count = $this->smsCount($code);
        if (empty($count)) {

            $data['code'] = $code;
            $data['add_time'] = $this->timeRepository->getGmTime();
            return Sms::create($data);
        }

        return false;
    }

    /**
     * 设置默认
     * @param string $code
     * @return bool
     */
    public function setDefault($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $data['default'] = 0;
        return Sms::where('code', '<>', $code)->update($data);
    }

    /**
     * 查询是否存在
     * @param string $code
     * @return mixed
     */
    public function smsCount($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $count = Sms::query()->where('code', $code)->count();

        return $count;
    }

    /**
     * 查询单条
     * @param string $code
     * @return array
     */
    public function smsInfo($code = '')
    {
        if (empty($code)) {
            return [];
        }

        $model = Sms::query()->where('code', $code)->first();

        $info = $model ? $model->toArray() : [];

        if (!empty($info)) {
            $info['sms_configure'] = empty($info['sms_configure']) ? [] : \Opis\Closure\unserialize($info['sms_configure']);
        }

        return $info;
    }

    /**
     * 获取短信配置
     * @param string $code
     * @return array|mixed
     */
    public function getSmsConfigure($code = '')
    {
        $info = $this->smsInfo($code);

        return $info['sms_configure'] ?? [];
    }

    /**
     * 卸载删除
     * @param string $code
     * @return bool
     */
    public function uninstall($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $model = Sms::where('code', $code);

        if ($model) {
            $res = $model->delete();

            return $res;
        }

        return false;
    }

    /**
     * 旧短信配置
     * @return array
     */
    public function oldSmsConfig()
    {
        $config = $this->config;

        $sms_type = $config['sms_type'] ?? 0; // 0,1,2,3

        $code = '';
        if ($sms_type == 0) {
            // 互亿 sms_type = 0
            $code = 'ihuyi';
        } elseif ($sms_type = 1) {
            //阿里大于 sms_type = 1
            $code = 'alidayu';
        } elseif ($sms_type = 2) {
            // 阿里云 sms_type = 2
            $code = 'aliyun';
        } elseif ($sms_type = 3) {
            // 模板堂 sms_type = 3
            $code = 'dscsms';
        } elseif ($sms_type == 4) {
            // 华为云 sms_type = 4
            $code = 'huawei';
        } elseif ($sms_type == 5) {
            // 创蓝253云短信 sms_type = 5
            $code = 'chuanglan';
        }

        $list = [
            // 互亿 sms_type = 0
            'ihuyi' => [
                'sms_ecmoban_user' => $config['sms_ecmoban_user'] ?? '',
                'sms_ecmoban_password' => $config['sms_ecmoban_password'] ?? '',
            ],
            // 阿里大于 sms_type = 1
            'alidayu' => [
                'ali_appkey' => $config['ali_appkey'] ?? '',
                'ali_secretkey' => $config['ali_secretkey'] ?? '',
            ],
            // 阿里云 sms_type = 2
            'aliyun' => [
                'access_key_id' => $config['access_key_id'] ?? '',
                'access_key_secret' => $config['access_key_secret'] ?? '',
            ],
            // 模板堂 sms_type = 3
            'dscsms' => [
                'dsc_appkey' => $config['dsc_appkey'] ?? '',
                'dsc_appsecret' => $config['dsc_appsecret'] ?? '',
            ],
            // 华为云 sms_type = 4
            'huawei' => [
                'huawei_sms_key' => $config['huawei_sms_key'] ?? '',
                'huawei_sms_secret' => $config['huawei_sms_secret'] ?? '',
            ],
            // 创蓝
            'chuanglan' => [
                'chuanglan_account' => $config['chuanglan_account'] ?? '',
                'chuanglan_password' => $config['chuanglan_password'] ?? '',
                'chuanglan_api_url' => $config['chuanglan_api_url'] ?? '',
                'chuanglan_signa' => $config['chuanglan_signa'] ?? '',
            ],
        ];

        return ['code' => $code, 'list' => $list];
    }
}
