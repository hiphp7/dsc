<?php

namespace App\Plugins\CloudApi;

use App\Libraries\Http;
use App\Models\ShopConfig;

/**
 * 类
 */
class Cloud
{

    //请求url，正式环境地址：http://api.biz.jioao.cn/gy_api2    测试环境地址1：http://api.test.jioao.cn/gy_api 测试环境地址2：http://api.test.jioao.cn/gy_api2/

    private $app_secret;
    private $AppKey;
    private $domain; //地址

    private $getMethod = "POST"; //传值方式
    private $graphUrl = ""; //接口名称
    private $queryInventory = "apiGoods/queryInventory"; //库存查询
    private $apiaddOrder = "apiOrder/addOrderObjectMall"; //添加订单
    private $confirmorder = "api/apiPublicNotify"; //确认订单
    private $apiAfterSales = "apiAfterSales/saveApply"; //推送售后信息
    private $apiStoreRefundAddress = "apiAfterSales/storeRefundAddress"; //获取售后地址

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */

    public function __construct()
    {
        $this->app_secret = ShopConfig::where('code', 'cloud_client_id')->value('value');
        $this->AppKey = ShopConfig::where('code', 'cloud_appkey')->value('value');
        $this->domain = 'http://api.biz.jioao.cn/gy_api2/';
    }

    /**
     * 发送操作
     */
    public function request($graphUrl, $data)
    {

        //验证
        if (!$this->domain) {
            return false;
        } elseif (!$graphUrl) {
            return false;
        } elseif (!$data) {
            return false;
        }
        //MD5加密签名
        $sign = MD5($data['data'] . $this->app_secret);
        $data['sign'] = strtoupper($sign); //转换大写
        $data['appKey'] = $this->AppKey;
        //链接处理
        $url = $this->domain . $graphUrl;
        return app(Http::class)->doPost($url, $data, 5, 'Content-Type:application/json', 'json');
    }

    /**
     * 库存查询
     */
    public function queryInventoryNum($productIds)
    {
        if (!$productIds) {
            return false;
        }

        //参数转换为整形
        foreach ($productIds as $k => $v) {
            $productIds[$k] = intval($v);
        }

        //数据处理
        $data = [];
        $data['productIds'] = $productIds;
        $data = json_encode($data);
        $data = base64_encode($data);

        //格式数据
        $request = [
            'appId' => 0,
            'baUserId' => 0,
            'data' => $data,
            'goodsId' => 0,
            'roleId' => 0,
            'storeId' => 0,
            'userId' => 0
        ];

        $requ = $this->request($this->queryInventory, $request);
        return $requ;
    }

    /**
     * 推送订单
     */
    public function addOrderMall($order_request, $order)
    {
        if (empty($order_request)) {
            return false;
        }
        //数据处理
        $data = json_encode($order_request);
        //print_arr($data);
        $data = base64_encode($data);

        //格式数据
        $request = [
            'data' => $data
        ];
        $requ = $this->request($this->apiaddOrder, $request);

        return $requ;
    }

    /**
     * 确认订单
     */
    public function confirmorder($order)
    {
        if (empty($order)) {
            return false;
        }
        //数据处理
        $data = json_encode($order);
        $data = base64_encode($data);

        //格式数据
        $request = [
            'data' => $data
        ];
        $requ = $this->request($this->confirmorder, $request);
        return $requ;
    }

    /**
     * 保存售后申请
     */
    public function apiAfterSales($order_return_request)
    {
        if (empty($order_return_request)) {
            return false;
        }
        //数据处理
        $data = json_encode($order_return_request);
        $data = base64_encode($data);

        //格式数据
        $request = [
            'data' => $data
        ];
        $requ = $this->request($this->apiAfterSales, $request);
        return $requ;
    }

    /**
     * 获取售后地址
     */
    public function getStoreRefundAddress($store_addres)
    {
        if (empty($store_addres)) {
            return false;
        }
        //数据处理
        $data = json_encode($store_addres);
        $data = base64_encode($data);

        //格式数据
        $request = [
            'data' => $data
        ];
        $requ = $this->request($this->apiStoreRefundAddress, $request);
        return $requ;
    }
}
