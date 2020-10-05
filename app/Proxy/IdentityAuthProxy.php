<?php

namespace App\Proxy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;


/**
 * 身份证实名认证
 * Class IdentityAuthProxy
 * @package App\Proxy
 */
class IdentityAuthProxy
{
    public $resultCode = null;
    public $resultMessage = '';

    protected $config;

    /**
     * IdentityAuthProxy constructor.
     */
    public function __construct()
    {
        $this->config = config('services.alicloud');
    }

    /**
     * 验证
     * @param string $userName
     * @param string $identifyNum
     * @param array $extend
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function checkIdentity($userName = '', $identifyNum = '', $extend = [])
    {
        if (!empty($this->config)) {

            $api = 2;

            if ($api == 1) {
                // 官方自营
                return $this->apiQuery($userName, $identifyNum, $extend);

            } elseif ($api == 2) {
                // 上海懿夕
                return $this->apiQueryYx($userName, $identifyNum, $extend);
            } elseif ($api == 3) {
                // 昆明秀派
                return $this->apiQueryXiupai($userName, $identifyNum, $extend);
            }
        }

        return false;
    }

    /**
     * API query
     * 服务商：上海懿夕网络科技有限公司 接口之家 身份证实名认证_身份证二要素一致性验证_身份证实名核验
     * https://market.aliyun.com/products/57000002/cmapi031844.html#sku=yuncode2584400002
     *
     * @param string $userName
     * @param string $identifyNum
     * @param array $extend
     * @return bool
     */
    public function apiQueryYx($userName = '', $identifyNum = '', $extend = [])
    {
        $api = 'https://yxidcard.market.alicloudapi.com/idcard';
        $method = 'GET';

        $request['realname'] = $userName;
        $request['idcard'] = $identifyNum;

        // 合并请求参数
        if (!empty($extend)) {
            $request = array_merge($request, $extend);
        }

        $respond = self::request($api, $request, $method, $this->config['appCode']);
        if ($respond) {
            if (config('app.debug')) {
                Log::info($respond);
            }
            if ($respond['code'] == '200') {
                // 实名认证通过
                return true;
            }
        }

        return false;
    }

    /**
     * API query
     * 服务商：昆明秀派科技有限公司 身份证实名校验-身份证二要素核验身份证一身份证查询API
     * https://market.aliyun.com/products/57000002/cmapi015837.html?spm=5176.730006-56956004-57000002-cmapi012484.recommend.9.6b2f4d84LMKyiU&innerSource=detailRecommend#sku=yuncode983700006
     *
     * @param string $userName
     * @param string $identifyNum
     * @param array $extend
     * @return bool
     */
    public function apiQueryXiupai($userName = '', $identifyNum = '', $extend = [])
    {
        $api = 'http://idcard3.market.alicloudapi.com/idcardAudit';
        $method = 'GET';

        $request['name'] = $userName;
        $request['idcard'] = $identifyNum;

        // 合并请求参数
        if (!empty($extend)) {
            $request = array_merge($request, $extend);
        }

        $respond = self::request($api, $request, $method, $this->config['appCode']);
        if ($respond) {
            if (config('app.debug')) {
                Log::info($respond);
            }
            if (isset($respond['showapi_res_code']) && $respond['showapi_res_code'] == '0') {
                // 实名认证通过
                $code = $respond['showapi_res_body']['code'] ?? 1; // 0 匹配、 1 不匹配、 2 无此身份证号
                if ($code == 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * API query 参数
     * 服务商：阿里云盾身份认证
     * https://market.aliyun.com/products/57000002/cmapi029454.html?spm=5176.10695662.1194487.3.6191118dqiNT73#sku=yuncode23454000014
     * @param string $userName
     * @param string $identifyNum
     * @param array $extend
     * @return bool
     */
    protected function apiQuery($userName = '', $identifyNum = '', $extend = [])
    {
        $api = 'https://safrvcert.market.alicloudapi.com/safrv_2meta_id_name';
        $method = 'GET';

        $request['userName'] = $userName;
        $request['identifyNum'] = $identifyNum;

        // 请求参数
        $extend = [
            '__userId' => 'userId',
            'customerID' => $customerID ?? '123', // 可选 客户自己的userid，只做透传
            'verifyKey' => 'verifyKey',
        ];

        // 合并请求参数
        if (!empty($extend)) {
            $request = array_merge($request, $extend);
        }

        $respond = self::request($api, $request, $method, $this->config['appCode']);
        if ($respond) {
            if (config('app.debug')) {
                Log::info($respond);
            }
            if ($respond['code'] == '200') {
                // 实名认证通过
                return true;
            }
        }

        return false;
    }

    /**
     * API query 参数
     * 服务商：四川涪擎大数据技术有限公司 身份证认证-身份证二要素核验-身份证一致性验证-身份证实名认证
     * https://market.aliyun.com/products/57000002/cmapi028649.html?spm=5176.2020520132.101.8.39ee72180tsgQY#sku=yuncode2264900001
     * @param string $userName
     * @param string $identifyNum
     * @param array $extend
     * @return bool
     */
    protected function apiQueryFegine($userName = '', $identifyNum = '', $extend = [])
    {
        $api = 'https://naidcard.market.alicloudapi.com/nidCard';
        $method = 'GET';

        $request = [
            'name' => $userName,
            'idCard' => $identifyNum,
        ];

        // 合并请求参数
        if (!empty($extend)) {
            $request = array_merge($request, $extend);
        }

        $respond = self::request($api, $request, $method, $this->config['appCode']);
        if ($respond) {
            if (config('app.debug')) {
                Log::info($respond);
            }
            if ($respond['status'] == '01') {
                // 实名认证通过
                return true;
            }
        }

        return false;
    }

    /**
     * 使用 AppCode 调用（简单身份认证）
     * @param string $api
     * @param array $request
     * @param string $method
     * @param string $appcode
     * @return bool|mixed
     */
    public function request($api = '', $request = [], $method = 'POST', $appcode = '')
    {
        if (empty($request)) {
            return false;
        }

        // 过滤数组null值
        $request = Arr::where($request, function ($value, $key) {
            return !is_null($value);
        });

        // 自定义header
        $configs['headers'] = [
            // 使用 AppCode 调用（简单身份认证）请求Header中添加的Authorization字段；配置Authorization字段的值为“APPCODE ＋ 半角空格 ＋APPCODE值”。
            'Authorization' => 'APPCODE ' . $appcode,
        ];
        // 跳过证书检查
        $configs['verify'] = false;

        $respond = self::httpRequest($api, $request, $method, $configs, 'json');

        if ($respond) {
            if ($respond['status'] === false || $respond['status'] == 'error') {
                $this->resultCode = $respond['errorCode'];
                $this->resultMessage = $respond['errorMessage'];
                return false;
            } else {
                $result = $respond['data'];
                // 状态码，200为成功，其余为失败
                if (isset($result['code']) && $result['code'] != '200') {
                    $this->resultCode = $result['code'];

                    if (isset($result['msg'])) {
                        $this->resultMessage = $result['msg'] ?? '';
                    } else {
                        $this->resultMessage = $result['message'] ?? '';
                    }
                    Log::info($result);
                    return false;
                }
            }

            return is_string($result) ? dsc_decode($result, true) : $result;
        }

        return false;
    }

    /**
     * 发送请求  GET / POST
     *
     * https://guzzle-cn.readthedocs.io/zh_CN/latest/quickstart.html
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @param array $configs
     * @param string $contentType form_params | json
     * @return array
     */
    public static function httpRequest(string $url, array $params = [], string $method = 'POST', array $configs = [], string $contentType = 'form_params')
    {
        try {
            $configs['timeout'] = Arr::get($configs, 'timeout', 60);

            $method = strtoupper($method);
            $params = $method == 'GET' ? ['query' => $params] : [$contentType => $params];

            $client = new Client($configs);

            $resp = $client->request($method, $url, $params);

        } catch (RequestException $exception) {
            $errorCode = $exception->getCode();
            $errorMessage = $exception->getMessage();

            return [
                'status' => false,
                'errorCode' => $errorCode,
                'errorMessage' => $errorMessage,
            ];
        }

        $httpCode = $resp->getStatusCode();
        $return = $resp->getBody()->getContents();

        $success = $httpCode == 200 ? 'success' : 'error';

        if ($httpCode != 200) {
            return [
                'status' => $success,
                'errorCode' => $httpCode,
                'errorMessage' => '',
            ];
        }

        $response = json_decode($return, true, 512, JSON_BIGINT_AS_STRING); //JSON_BIGINT_AS_STRING 用于将大整数转为字符串而非默认的float类型

        // Laravel 开启 debug
        if (config('app.debug')) {
            // 记录日志
            Log::info("请求url：", ['url' => $url]);
            Log::info("请求应用参数：", ['params' => $params]);
            Log::info("返回：", ['response' => $response]);
        }

        return [
            'status' => $success,
            'data' => $response
        ];
    }
}
