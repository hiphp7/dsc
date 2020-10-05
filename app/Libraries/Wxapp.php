<?php

namespace App\Libraries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Wxapp
{
    /**
     * 微信小程序类
     * 官方文档：https://mp.weixin.qq.com/debug/wxadoc/dev/index.html?t=20161107
     */
    const API_URL_PREFIX = 'https://api.weixin.qq.com';

    const AUTH_ORIZATION = '/sns/jscode2session?'; // 获取登录凭证（code）

    private $wx_mini_appid; // 小程序ID
    private $wx_mini_secret; // 小程序app_secret密钥

    const GET_ACCESS_TOKEN = '/cgi-bin/token?'; // 获取access_token

    const GET_USER_INFO = '/cgi-bin/user/info?'; // 获取unionid

    const GET_WXA_CODE = '/wxa/getwxacode?'; // 获取小程序码 接口A

    const GET_WXA_CODE_UNLIMIT = '/wxa/getwxacodeunlimit?'; // 获取小程序码 接口B

    const GET_WXA_QRCODE = '/cgi-bin/wxaapp/createwxaqrcode?'; // 获取小程序码 接口C

    const GET_WXA_KEYWORD_LIST = '/cgi-bin/wxopen/template/library/get?'; // 获取模板库某个模板标题下关键词库

    const GET_WXA_TEMPLATE_ADD = '/cgi-bin/wxopen/template/add?'; // 组合模板并添加至帐号下的个人模板库

    const GET_WXA_TEMPLATE_DEL = '/cgi-bin/wxopen/template/del?'; // 删除帐号下的某个模板

    const GET_WXA_TEMPLATE_SEND_URL = '/cgi-bin/message/wxopen/template/send?';  //发送模板消息 接口地址

    public $debug = false;

    public $errCode = 40001;

    public $errMsg = "no access";

    public function __construct(array $options)
    {
        $this->wx_mini_appid = isset($options['appid']) ? $options['appid'] : '';
        $this->wx_mini_secret = isset($options['secret']) ? $options['secret'] : '';
    }

    /**
     * code 换取 session_key
     * 调用接口获取登录凭证（code）
     * @param
     * @return
     */
    public function getOauthOrization($code)
    {
        $params = [
            'appid' => $this->wx_mini_appid,
            'secret' => $this->wx_mini_secret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $result = $this->curlGet(self::API_URL_PREFIX . self::AUTH_ORIZATION . http_build_query($params, '', '&'));
        if ($result) {
            $json = dsc_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * code 换取 unionid
     * 调用接口获取登录凭证（code）
     * @param
     * @return
     */
    public function getUnionid($token, $openid)
    {
        $params = [
            'withCredentials' => true,
            'access_token' => $token,
            'openid' => $openid,
            'lang' => 'zh_CN'
        ];
        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_USER_INFO, self::json_encode($params));

        if ($result) {
            $json = dsc_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 换取 access_token
     * @param
     * @return
     */
    public function getAccessToken()
    {
        $result = $this->curlGet(self::API_URL_PREFIX . self::GET_ACCESS_TOKEN . "grant_type=client_credential&appid=" . $this->wx_mini_appid . "&secret=" . $this->wx_mini_secret);
        if ($result) {
            $json = dsc_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json['access_token'];
        }
        return false;
    }

    /**
     * 获取小程序码 接口A
     * 适用于需要的码数量较少的业务场景
     * @param
     * @return
     */
    public function getWaCode($path, $width = '430', $auto_color = true, $line_color = '')
    {
        $data = [
            'path' => $path,
            'width' => $width,
            'auto_color' => $auto_color,
            'line_color' => $line_color
        ];
        if ($auto_color === false) {
            unset($data['line_color']);
        }
        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_WXA_CODE . 'access_token=' . self::getAccessToken(), self::json_encode($data));

        return $result;
    }

    /**
     * 获取小程序码 接口B
     * 适用于需要的码数量极多，或仅临时使用的业务场景
     * @param scene 值 最大32个可见字符，只支持数字 例如：自定义推荐参数 scene=uid
     * @return
     */
    public function getWaCodeUnlimit($scene = '', $path, $width, $auto_color = false, $line_color = '')
    {
        $data = [
            'scene' => $scene,
            'page' => $path,
            'width' => $width,
            'auto_color' => $auto_color,
            'line_color' => $line_color
        ];
        if ($auto_color === false) {
            unset($data['line_color']);
        }

        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_WXA_CODE_UNLIMIT . 'access_token=' . self::getAccessToken(), self::json_encode($data));

        return $result;
    }

    /**
     * 获取小程序码 接口C
     * 适用于需要的码数量较少的业务场景
     * 通过该接口生成的小程序二维码，永久有效，数量限制见文末说明，请谨慎使用。
     * @param
     * @return
     */
    public function getWxaCode($path, $width = '430')
    {
        $data = [
            'path' => $path,
            'width' => $width
        ];
        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_WXA_QRCODE . 'access_token=' . self::getAccessToken(), self::json_encode($data));

        return $result;
    }

    /**
     * 获取模板库某个模板标题下关键词库KeywordList
     * 成功返回消息模板标题下关键词库
     * @param string $tpl_id 模板库中模板的编号
     * @return boolean|string
     */
    public function getWxTemplateKeywordList($tpl_id)
    {
        $tpl_id = [
            'id' => $tpl_id,
        ];

        //获取模板库某个模板标题下关键词库
        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_WXA_KEYWORD_LIST . 'access_token=' . self::getAccessToken(), self::json_encode($tpl_id));
        return dsc_decode($result, true);
    }


    /**
     * 模板消息 添加消息模板
     * 成功返回消息模板的调用id
     * @param string $tpl_id 模板的编号 $keyword_id 关键词库id
     * @return boolean|string
     */
    public function wxaddTemplateMessage($tpl_id, $keyword_id)
    {
        $tpl_id = [
            'id' => $tpl_id,
            'keyword_id_list' => $keyword_id
        ];

        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_WXA_TEMPLATE_ADD . 'access_token=' . self::getAccessToken(), self::json_encode($tpl_id));
        if ($result) {
            $json = dsc_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json['template_id'];
        }
        return false;
    }

    /**
     * 删除模板消息
     * @param string $template_id 模板消息ID
     * @return boolean|array
     */
    public function wxDelTemplate($template_id)
    {
        $data = [
            'template_id' => $template_id
        ];
        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_WXA_TEMPLATE_DEL . 'access_token=' . self::getAccessToken(), self::json_encode($data));
        if ($result) {
            $json = dsc_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 发送模板消息
     * @param array $data 消息结构
     *{
     *"touser": "OPENID",
     * "template_id": "TEMPLATE_ID",
     *"page": "index",
     *"form_id": "FORMID",
     *"data": {
     *   "keyword1": {
     *       "value": "339208499",
     *       "color": "#173177"
     *  },
     * "keyword2": {
     *    "value": "2015年01月05日 12:30",
     *   "color": "#173177"
     * },
     *"keyword3": {
     *   "value": "粤海喜来登酒店",
     *  "color": "#173177"
     *} ,
     *"keyword4": {
     *   "value": "广州市天河区天河路208号",
     *  "color": "#173177"
     *}
     *},
     *"emphasis_keyword": "keyword1.DATA"
     *}
     * @return boolean|array
     */
    public function sendTemplateMessage($data)
    {
        $result = $this->curlPost(self::API_URL_PREFIX . self::GET_WXA_TEMPLATE_SEND_URL . 'access_token=' . self::getAccessToken(), self::json_encode($data));
        if ($result) {
            $json = dsc_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * GET 请求
     * @param $url
     * @param int $timeout
     * @param string $header
     * @return bool|mixed
     */
    protected function curlGet($url, $timeout = 5, $header = "")
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$header]);//模拟的header头
        $result = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if (intval($aStatus["http_code"]) == 200) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * POST 请求
     * @param $url
     * @param $post_data
     * @param int $timeout
     * @return bool|mixed
     */
    protected function curlPost($url, $post_data, $timeout = 5)
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        $header = empty($header) ? '' : $header;
        if (is_string($post_data)) {
            $strPOST = $post_data;
        } else {
            $aPOST = [];
            foreach ($post_data as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));//模拟的header头
        $result = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if (intval($aStatus["http_code"]) == 200) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 微信api不支持中文转义的json结构
     * @param $arr
     * @return string
     */
    public static function json_encode($arr)
    {
        if (count($arr) == 0) {
            return "[]";
        }
        $parts = [];
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys($arr);
        $max_length = count($arr) - 1;
        if (($keys [0] === 0) && ($keys [$max_length] === $max_length)) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for ($i = 0; $i < count($keys); $i++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach ($arr as $key => $value) {
            if (is_array($value)) { //Custom handling for arrays
                if ($is_list) {
                    $parts [] = self::json_encode($value);
                } /* :RECURSION: */
                else {
                    $parts [] = '"' . $key . '":' . self::json_encode($value);
                } /* :RECURSION: */
            } else {
                $str = '';
                if (!$is_list) {
                    $str = '"' . $key . '":';
                }
                //Custom handling for multiple data types
                if (!is_string($value) && is_numeric($value) && $value < 2000000000) {
                    $str .= $value;
                } //Numbers
                elseif ($value === false) {
                    $str .= 'false';
                } //The booleans
                elseif ($value === true) {
                    $str .= 'true';
                } else {
                    $str .= '"' . addslashes($value) . '"';
                } //All other things
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode(',', $parts);
        if ($is_list) {
            return '[' . $json . ']';
        } //Return numerical JSON
        return '{' . $json . '}'; //Return associative JSON
    }

    /**
     * 日志记录
     * @param mixed $log 输入日志
     * @return mixed
     */
    public function log($log)
    {
        $log = is_array($log) ? var_export($log, true) : $log;
        if ($this->debug && function_exists('logResult')) {
            logResult($log);
        }
    }

    /**
     * 设置缓存，按需重载
     * @param string $name
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($name, $value, $expired)
    {
        Cache::put($name, $value, Carbon::now()->addSeconds($expired));
        return true;
    }

    /**
     * 获取缓存，按需重载
     * @param string $name
     * @return mixed
     */
    protected function getCache($name)
    {
        return Cache::get($name);
    }

    /**
     * 清除缓存，按需重载
     * @param string $name
     * @return boolean
     */
    protected function removeCache($name)
    {
        return Cache::forget($name);
    }
}
