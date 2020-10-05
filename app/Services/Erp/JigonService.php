<?php

namespace App\Services\Erp;

use App\Libraries\Http;

/**
 * 贡云接口服务类
 * Class JigonService
 * @package App\Services\Erp
 */
class JigonService
{
    private $config;

    /**
     * 签名
     * @var
     */
    private $app_secret = '';

    /**
     * 密钥
     * @var
     */
    private $app_key = '';

    /**
     * @var bool
     */
    private $app_debug = true;

    /**
     * 正式环境地址：http://api.jioao.cn/gy_api2
     * @var string
     */
    private $domain = 'http://api.jioao.cn/gy_api2/';

    /**
     * 测试环境地址1：http://api.test.jioao.cn/gy_api
     * 测试环境地址2：http://api.test.jioao.cn/gy_api2
     * @var string
     */
    private $testDomain = 'http://api.test.jioao.cn/gy_api2/';

    /**
     * 库存查询
     * @var string
     */
    private $queryInventory = "apiGoods/queryInventory";

    /**
     * 添加订单
     * @var string
     */
    private $apiaddOrder = "apiOrder/addOrderObjectMall";

    /**
     * 确认订单
     * @var string
     */
    private $confirmorder = "api/apiPublicNotify";

    /**
     * 推送售后信息
     * @var string
     */
    private $apiAfterSales = "apiAfterSales/saveApply";

    /**
     * 获取售后地址
     * @var string
     */
    private $apiStoreRefundAddress = "apiAfterSales/storeRefundAddress";

    public function __construct()
    {
        /* 商城配置信息 */
        $shopConfig = cache('shop_config');

        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }

        $this->app_secret = $this->config['cloud_client_id'];
        $this->app_key = $this->config['cloud_appkey'];
    }

    /**
     * 发送操作
     * @param $graphUrl
     * @param $data
     * @return bool|mixed]
     */
    public function request($graphUrl, $data)
    {
        // 验证
        if (!$this->domain) {
            return false;
        } elseif (!$graphUrl) {
            return false;
        } elseif (!$data) {
            return false;
        }

        // MD5加密签名
        $sign = MD5($data['data'] . $this->app_secret);
        $data['sign'] = strtoupper($sign);//转换大写
        $data['appKey'] = $this->app_key;
        $dataStr = json_encode($data);

        // 链接处理
        //if ($this->app_debug) {
        //$this->domain = $this->testDomain;
        //}
        $url = $this->domain . $graphUrl;

        // Debug Log
        if ($this->app_debug) {
            // logResult(['url' => $url, 'data' => $data, 'dataStr' => $dataStr]);
        }

        return Http::doPost($url, $dataStr, 5, 'Content-Type:application/json');
    }

    /**
     * 库存查询
     * @param $productIds
     * @return bool|mixed
     */
    public function query($productIds)
    {
        if (!$productIds) {
            return false;
        }

        //$productIds = array(17107);
        // 参数转换为整形
        foreach ($productIds as $k => $v) {
            $productIds[$k] = intval($v);
        }
        //print_arr($productIds);

        // 数据处理
        $data = array();
        $data['productIds'] = $productIds;
        $data = json_encode($data);
        $data = base64_encode($data);

        // 格式数据
        $request = array(
            'appId' => 0,
            'baUserId' => 0,
            'data' => $data,
            'goodsId' => 0,
            'roleId' => 0,
            'storeId' => 0,
            'userId' => 0
        );

        return $this->request($this->queryInventory, $request);
    }

    /**
     * 推送订单
     * @param $order_request
     * @param $order
     * @return bool|mixed
     */
    public function push($order_request, $order)
    {
        if (empty($order_request)) {
            return false;
        }

        // 数据处理
        $data = json_encode($order_request);
        //print_arr($data);
        $data = base64_encode($data);

        // 格式数据
        $request = array(
            'data' => $data
        );

        return $this->request($this->apiaddOrder, $request);
    }

    /**
     * 确认订单
     * @param $order
     * @return bool|mixed
     */
    public function confirm($order)
    {
        if (empty($order)) {
            return false;
        }

        // 数据处理
        $data = json_encode($order);
        $data = base64_encode($data);

        // 格式数据
        $request = array(
            'data' => $data
        );

        return $this->request($this->confirmorder, $request);
    }

    /**
     * 保存售后申请
     * @param $order_return_request
     * @return bool|mixed
     */
    public function saveAfterSales($order_return_request)
    {
        if (empty($order_return_request)) {
            return false;
        }

        // 数据处理
        $data = json_encode($order_return_request);
        $data = base64_encode($data);

        // 格式数据
        $request = array(
            'data' => $data
        );

        return $this->request($this->apiAfterSales, $request);
    }

    /**
     * 获取售后地址
     * @param $store_addres
     * @return bool|mixed
     */
    public function getAfterSalesAddress($store_addres)
    {
        if (empty($store_addres)) {
            return false;
        }

        // 数据处理
        $data = json_encode($store_addres);
        $data = base64_encode($data);

        // 格式数据
        $request = array(
            'data' => $data
        );

        return $this->request($this->apiStoreRefundAddress, $request);
    }
}
