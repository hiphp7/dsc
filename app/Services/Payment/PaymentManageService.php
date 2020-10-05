<?php

namespace App\Services\Payment;


use App\Models\Payment;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;

/**
 * Class PaymentManageService
 * @package App\Services\Payment
 */
class PaymentManageService
{
    protected $baseRepository;
    protected $commonRepository;

    public function __construct(
        BaseRepository $baseRepository,
        CommonRepository $commonRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
    }

    /**
     * 获取支付信息
     * @param array $where
     * @return array
     */
    public function getPaymentInfo($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $res = Payment::whereRaw(1);

        if (isset($where['pay_id'])) {
            $res = $res->where('pay_id', $where['pay_id']);
        }

        if (isset($where['pay_name'])) {
            $res = $res->where('pay_name', $where['pay_name']);
        }

        if (isset($where['pay_code'])) {
            $res = $res->where('pay_code', $where['pay_code']);
        }

        if (isset($where['enabled'])) {
            $res = $res->where('enabled', $where['enabled']);
        }

        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 支付方式列表
     * @return array
     */
    public function paymentList()
    {
        $res = Payment::where('enabled', 1)
            ->orderBy('pay_order', 'ASC')
            ->get();

        $list = $res ? $res->toArray() : [];

        return $list;
    }

    /**
     * 检测支付方式是否重复安装
     * @param string $pay_code
     * @param int $pay_id
     * @return mixed
     */
    public function checkPaymentRepeat($pay_code = '', $pay_id = 0)
    {
        $count = Payment::where('enabled', 1)
            ->where('pay_code', $pay_code)
            ->where('pay_id', '<>', $pay_id)
            ->count();

        return $count;
    }

    /**
     * 检测支付方式是否曾经安装过
     * @param string $pay_code
     * @return mixed
     */
    public function checkPaymentCount($pay_code = '')
    {
        $count = Payment::where('pay_code', $pay_code)
            ->count();

        return $count;
    }

    /**
     * 更新支付方式
     * @param string $pay_code
     * @param array $data
     * @return bool
     */
    public function updatePayment($pay_code = '', $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'payment');

        $res = Payment::where('pay_code', $pay_code)->update($data);

        return $res;

    }

    /**
     * 新增支付方式
     * @param array $data
     * @return mixed
     */
    public function createPayment($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'payment');

        return Payment::create($data);
    }

    /**
     * 保存微信证书（微信公众号支付与小程序微信支付通用）
     * @param string $pay_code
     * @param array $pay_config
     * @return bool
     */
    public function makeWxpayCert($pay_code = '', $pay_config = [])
    {
        if (empty($pay_config)) {
            return false;
        }

        if ($pay_code == 'wxpay') {
            // 保存微信证书
            $file_path = storage_path('app/certs/wxpay/');
            $this->file_write($file_path, "index.html", "");

            if ($pay_config) {
                foreach ($pay_config as $k => $v) {
                    if ($v['name'] == 'wxpay_mchid' && $v['value'] != '') {
                        $wxpay_mchid = $v['value'];
                    }
                    if (!empty($wxpay_mchid)) {
                        if ($v['name'] == 'sslcert' && $v['value'] != '') {
                            $this->file_write($file_path, md5($wxpay_mchid) . "_apiclient_cert.pem", $v['value']);
                        }
                        if ($v['name'] == 'sslkey' && $v['value'] != '') {
                            $this->file_write($file_path, md5($wxpay_mchid) . "_apiclient_key.pem", $v['value']);
                        }
                    }
                }
            }
        }

    }

    /**
     * 生成密钥文件
     * @param string $file_path 目录
     * @param string $filename 文件名
     * @param string $content 内容
     */
    protected function file_write($file_path, $filename, $content = '')
    {
        if (!is_dir($file_path)) {
            @mkdir($file_path);
        }
        $fp = fopen($file_path . $filename, "w+"); // 读写，每次修改会覆盖原内容
        flock($fp, LOCK_EX);
        fwrite($fp, $content);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * 获取支付配置
     * @param string $code
     * @return array|mixed
     */
    public function getPayConfig($code = '')
    {
        if (empty($code)) {
            return [];
        }

        $pay_config = Payment::query()->where('enabled', 1)
            ->where('pay_code', $code)
            ->value('pay_config');

        if (!empty($pay_config)) {
            $pay_config = unserialize($pay_config);
        }

        return $pay_config;
    }


}
