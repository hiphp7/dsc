<?php

namespace App\Services\User;

use App\Models\OrderInvoice;
use App\Models\UsersVatInvoicesInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 会员发票
 * @package App\Services
 */
class InvoiceService
{
    protected $timeRepository;
    protected $config;
    protected $baseRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;

        /* 商城配置信息 */
        $shopConfig = cache('shop_config');
        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    /**
     *  获取会员增值发票信息
     * @param array $info
     * @return  array|bool
     */
    public function addUsersVatInvoices($info = [])
    {
        $time = $this->timeRepository->getGmTime();

        $res = UsersVatInvoicesInfo::select('id')->where('user_id', $info['user_id'])->first();
        $res = $res ? $res->toArray() : [];

        if (!empty($res)) {
            return [
                'code' => 0,
                'msg' => lang('ajax_dialog.exist_invoice')
            ];
        } else {
            $data = [
                'company_name' => $info['company_name'],//公司名称
                'tax_id' => $info['tax_id'],//税号
                'company_address' => $info['company_address'],//公司地址
                'company_telephone' => $info['company_telephone'],//公司电话
                'bank_of_deposit' => $info['bank_of_deposit'],//开户行
                'bank_account' => $info['bank_account'],//银行卡号
                'consignee_name' => $info['consignee_name'],
                'consignee_mobile_phone' => $info['consignee_mobile_phone'],
                'consignee_address' => $info['consignee_address'],
                'country' => $info['country'],
                'province' => $info['province'],
                'add_time' => $time,
                'city' => $info['city'],
                'district' => $info['district'],
                'user_id' => $info['user_id'],
            ];

            $id = UsersVatInvoicesInfo::insertGetId($data);
            if ($id) {
                $result = [
                    'code' => 1,
                    'msg' => lang('common.Submit_Success'),
                ];

                return $result;
            }
        }
    }

    /**
     *  删除会员增值发票信息
     * @param array $info
     * @return  array|bool
     */
    public function invoicesDestroy($info)
    {
        $uid = UsersVatInvoicesInfo::where('id', $info['id'])->value('user_id');

        if ($uid != $info['user_id']) {
            $result = [
                'code' => 0,
                'msg' => lang('ajax_dialog.not_yours_invoice')
            ];
            return $result;
        } else {
            UsersVatInvoicesInfo::where('id', $info['id'])->delete();
            $result = [
                'code' => 1,
                'msg' => lang('common.delete_success')
            ];
            return $result;
        }
    }

    /**
     *  更新会员增值发票信息
     * @param array $info
     * @return  array|bool
     */
    public function updateUsersVatInvoices($info = [])
    {
        if (empty($info)) {
            return [];
        }

        if ($info) {
            $time = $this->timeRepository->getGmTime();

            $data = [
                'company_name' => $info['company_name'],//公司名称
                'tax_id' => $info['tax_id'],//税号
                'company_address' => $info['company_address'],//公司地址
                'company_telephone' => $info['company_telephone'],//公司电话
                'bank_of_deposit' => $info['bank_of_deposit'],//开户行
                'bank_account' => $info['bank_account'],//银行卡号
                'consignee_name' => $info['consignee_name'],
                'consignee_mobile_phone' => $info['consignee_mobile_phone'],
                'consignee_address' => $info['consignee_address'],
                'country' => $info['country'],
                'province' => $info['province'],
                'add_time' => $time,
                'city' => $info['city'],
                'district' => $info['district'],
                'audit_status' => $info['audit_status']
            ];

            UsersVatInvoicesInfo::where('id', $info['id'])
                ->where('user_id', $info['user_id'])
                ->update($data);

            $result = [
                'code' => 0,
                'mes' => lang('common.update_Success')
            ];

            return $result;
        }
    }

    /**
     * 获取会员发票信息
     *
     * @param int $id
     * @return string
     */
    public function userVatConsigneeRegion($id = 0)
    {
        /* 取得区域名 */

        $res = UsersVatInvoicesInfo::where('id', $id);

        $res = $res->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        $address = [];
        if ($res) {
            if ($res['get_region_province']) {
                $province_name = $res['get_region_province']['region_name'];
            } else {
                $province_name = '';
            }
            $address['province_name'] = $province_name;

            if ($res['get_region_city']) {
                $city_name = $res['get_region_city']['region_name'];
            } else {
                $city_name = '';
            }
            $address['city_name'] = $city_name;

            if ($res['get_region_district']) {
                $district_name = $res['get_region_district']['region_name'];
            } else {
                $district_name = '';
            }
            $address['district_name'] = $district_name;

            $region = $province_name . " " . $city_name . " " . $district_name;
            $address['region'] = trim($region);
        }

        return $address;
    }


    /**
     * 新增普通发票信息
     * @param int $user_id
     * @param string $inv_payee
     * @param string $tax_id
     * @return array|string
     */
    public function addOrderInvoice($user_id = 0, $inv_payee = '', $tax_id = '')
    {
        if (empty($user_id) || empty($inv_payee)) {
            return [];
        }

        $result = ['code' => 0];
        $invoice_id = OrderInvoice::where('inv_payee', $inv_payee)->where('user_id', $user_id)->value('invoice_id');
        if (!$invoice_id) {
            $other = [
                'user_id' => $user_id,
                'inv_payee' => $inv_payee,
                'tax_id' => $tax_id
            ];
            $invoice_id = OrderInvoice::insertGetId($other);
            $result['invoice_id'] = $invoice_id;
        } else {
            $result['code'] = 1;
            $result['msg'] = lang('ajax_dialog.invoice_top_exists');
        }
        return $result;
    }

    /**
     * 更新普能发票信息
     * @param int $invoice_id
     * @param array $data
     * @return array
     */
    public function updateOrderInvoice($invoice_id = 0, $data = [])
    {
        if (empty($invoice_id) || empty($data)) {
            return [];
        }

        $other = [
            'inv_payee' => $data['inv_payee'],
            'tax_id' => $data['tax_id']
        ];
        OrderInvoice::where('invoice_id', $invoice_id)->update($other);
        $result['invoice_id'] = $invoice_id;

        return $result;
    }

    /**
     * 普通发票信息 （单位抬头与纳税人识别号）
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function getOrderInvoice($user_id = 0, $limit = 10)
    {
        // 抬头名称
        $order_invoice = OrderInvoice::where('user_id', $user_id)
            ->take($limit)
            ->get();

        return $order_invoice ? $order_invoice->toArray() : [];
    }

    /**
     * 删除发票信息
     * @param int $invoice_id
     * @return array|bool
     */
    public function orderInvoiceDestroy($invoice_id = 0)
    {
        if (empty($invoice_id)) {
            return false;
        }

        OrderInvoice::where('invoice_id', $invoice_id)->delete();

        return ['code' => 1, 'msg' => lang('common.delete_success')];
    }
}
