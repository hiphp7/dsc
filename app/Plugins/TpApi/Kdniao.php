<?php

namespace App\Plugins\TpApi;

/**
 *
 * 快递鸟电子面单接口
 * @copyright: 上海商创网络科技有限公司
 * @author: missfizz
 * ID和Key请到官网申请：http://kdniao.com/reg
 */

/**
 * 请求url
 * 电子面单
 * 正式环境地址：http://api.kdniao.com/api/Eorderservice
 * 测试环境地址：http://testapi.kdniao.cc:8081/api/EOrderService
 * 申请客户号
 * 正式环境地址：http://api.kdniao.com/api/apiservice
 * 测试环境地址：http://testapi.kdniao.cc:8081/api/apiservice
 */
class Kdniao
{
    private $client_id; //快递鸟id
    private $appkey; //快递鸟key
    private static $instance; //实例

    /**
     * 初始化
     * @param string $client_id 快递鸟id
     * @param string $appkey 快递鸟key
     */
    public function __construct($client_id = "", $appkey = "")
    {
        $this->client_id = $client_id;
        $this->appkey = $appkey;
    }

    /**
     * 单例对象
     * @param string $client_id 快递鸟id
     * @param string $appkey 快递鸟key
     * @return object 单例对象
     */
    public static function getInstance($client_id = "", $appkey = "")
    {
        if (!isset(self::$instance)) {
            self::$instance = new Kdniao($client_id, $appkey);
        }
        return self::$instance;
    }

    /**
     * Json方式 调用电子面单接口
     * @param string $requestData 请求数据
     * @return string 请求结果
     */
    public function submitEOrder($requestData)
    {
        $ReqURL = "https://api.kdniao.com/api/Eorderservice";
        $datas = array(
            'EBusinessID' => $this->client_id,
            'RequestType' => '1007',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->appkey);
        $result = $this->sendPost($ReqURL, $datas);

        //根据公司业务处理返回的信息......

        return $result;
    }

    /**
     * Json方式 调用申请客户号接口
     * @param string $requestData 请求数据
     * @return string 请求结果
     */
    public function applyCustomerAccount($requestData)
    {
        $ReqURL = "https://api.kdniao.com/api/apiservice";
        $datas = array(
            'EBusinessID' => $this->client_id,
            'RequestType' => '1107',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->appkey);
        $result = $this->sendPost($ReqURL, $datas);

        //根据公司业务处理返回的信息......

        return $result;
    }

    /**
     *  post提交数据
     * @param string $url 请求Url
     * @param array $datas 提交的数据
     * @return url响应返回的html
     */
    public function sendPost($url, $datas)
    {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    public function encrypt($data, $appkey)
    {
        return urlencode(base64_encode(md5($data . $appkey)));
    }
}

/**************************************************************
 *
 *  使用特定function对数组中所有元素做处理
 * @param string &$array 要处理的字符串
 * @param string $function 要执行的函数
 * @return boolean $apply_to_keys_also     是否也应用到key上
 * @access public
 *
 *************************************************************/
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}

/**************************************************************
 *
 *  将数组转换为JSON字符串（兼容中文）
 * @param array $array 要转换的数组
 * @return string      转换得到的json字符串
 * @access public
 *
 *************************************************************/
function JSON($array)
{
    arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}
