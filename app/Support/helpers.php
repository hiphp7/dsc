<?php

use Illuminate\Support\Arr;

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param string $str 要分割的字符串
 * @param string $glue 分割符
 * @return array
 */
function str2arr($str, $glue = ',')
{
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param array $arr 要连接的数组
 * @param string $glue 分割符
 * @return string
 */
function arr2str($arr, $glue = ',')
{
    return implode($glue, $arr);
}

/**
 * 数据签名认证
 * @param array $data 被认证的数据
 * @return string       签名
 */
function data_sign($data)
{
    // 数据类型检测
    if (!is_array($data)) {
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    return sha1($code); //生成签名
}

/**
 * 格式化字节大小
 * @param number $size 字节数
 * @param string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '')
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 用户资源目录
 * @param $path
 * @return mixed
 */
function storage_public($path = '')
{
    // $path = str_replace('/storage/', '', $path);
    return \Storage::path($path);
}

/**
 * 用户资源url
 * @param $value
 * @return mixed
 */
function storage_url($value = '')
{
    $value = $value ? \Storage::url($value) : '';
    return $value;
}

/**
 * 插件目录
 * @param string $path
 * @return string
 */
function plugin_path($path = '')
{
    return app_path('Plugins/' . $path);
}

/**
 * 返回数据库配置信息
 * @param null $item
 * @return \Illuminate\Config\Repository|mixed
 */
function db_config($item = null)
{
    $type = config('database.default', 'mysql');

    $config = config('database.connections.' . $type);

    return is_null($item) ? $config : $config[$item];
}

/**
 * 是否为移动设备
 * @return mixed
 */
function is_mobile_device()
{
    $detect = new \Mobile_Detect();
    return $detect->isMobile();
}

/**
 * 检查是否是微信浏览器访问
 * @return bool
 */
function is_wechat_browser()
{
    $user_agent = strtolower(request()->userAgent());

    if (strpos($user_agent, 'micromessenger') === false) {
        return false;
    } else {
        return true;
    }
}

/**
 * 判断是否SSL协议  https://
 * @return boolean
 */
function is_ssl()
{
    return request()->isSecure();
}

/**
 * 逆地址解析(坐标位置描述)
 * @param $lat 纬度
 * @param $lng 经度
 * @param $key Key
 * @return null
 * @example geocoder('31.22928', '121.40966', 'XSYBZ-P2G34-3K7UB-XPFZS-TBGHT-CXB4U')
 * @dependency http://lbs.qq.com/tool/component-geolocation.html
 */
function geocoder($lat, $lng, $key)
{
    $url = 'https://apis.map.qq.com/ws/geocoder/v1/?';
    $url .= 'location=' . $lat . ',' . $lng;
    $url .= '&key=' . $key;

    $content = file_get_contents($url);

    $result = dsc_decode($content, true);

    if ($result['status'] != 0) {
        return null;
    }

    return $result['result']['address_component'];
}

/**
 * 加载函数库
 * @param array $files
 * @param string $module
 */
function load_helper($files = [], $module = '')
{
    if (!is_array($files)) {
        $files = [$files];
    }
    if (empty($module)) {
        $base_path = app_path('Helpers/');
    } else {
        $base_path = app_path('Modules/' . ucfirst($module) . '/Helpers/');
    }
    foreach ($files as $vo) {
        $helper = $base_path . $vo . '.php';
        if (file_exists($helper)) {
            require_once $helper;
        }
    }
}

/**
 * 加载语言包
 * @param array $files
 * @param string $module
 * @throws Exception
 */
function load_lang($files = [], $module = '')
{
    $config = cache('shop_config');
    $config = !is_null($config) ? $config : false;
    if ($config === false) {
        $config = app(\App\Services\Common\ConfigService::class)->getConfig();
    }

    if (!is_array($files)) {
        $files = [$files];
    }

    if (empty($config['lang']) || $config['lang'] == 'zh_cn') {
        $config['lang'] = 'zh-CN';
    }

    $lang_path = $config['lang'] ?? 'zh-CN';

    if (empty($module)) {
        $base_path = resource_path('lang/' . $lang_path . '/');
    } else {
        $base_path = app_path('Modules/' . ucfirst($module) . '/Languages/' . $lang_path . '/');
    }

    $arr = [];
    foreach ($files as $key => $vo) {
        $helper = $base_path . $vo . '.php';
        if (file_exists($helper)) {
            $list = require($helper);
            if ($list) {
                $arr[$key] = $list;

                //后台，兼容英文版
                if (!empty($module)) {
                    $arr['js_languages'][$key] = $list['js_languages'] ?? [];
                    unset($arr[$key]['js_languages']);
                }
            }
        }
    }

    //后台，兼容英文版
    if (!empty($module) && isset($arr['js_languages']) && $arr['js_languages']) {
        $arr[]['js_languages'] = Arr::collapse($arr['js_languages']);
    }

    $GLOBALS['_LANG'] = Arr::collapse($arr);
}

/**
 * 输出语言包
 * @param null $key
 * @param array $replace
 * @param null $locale
 * @return array|\Illuminate\Contracts\Translation\Translator|null|string
 * @throws Exception
 */
function lang($key = null, $replace = [], $locale = null)
{
    if (is_null($locale)) {
        $config = cache('shop_config');

        if (empty($config['lang']) || $config['lang'] == 'zh_cn') {
            $locale = config('app.locale');
        } else {
            $locale = $config['lang'];
        }
    }

    return trans($key, $replace, $locale);
}

/**
 * 重定义url
 * @param null $path
 * @param null $parameters
 * @param null $secure
 * @param null $domian
 * @return array|mixed|null|string
 */
function dsc_url($path = null, $parameters = [], $secure = null, $domian = 'mobile')
{
    if (empty(config('app.mobile_domain'))) {
        if ($domian) {
            return url($domian . $path, $parameters, $secure);
        }
    }
    return url($path, $parameters, $secure);
}

/**
 * 获取和设置语言定义
 * @param null $name 语言变量
 * @param null $value 语言值或者变量
 * @return array|mixed|null|string
 */
function L($name = null, $value = null)
{
    static $_lang = [];
    // 空参数返回所有定义
    if (empty($name)) {
        return $_lang;
    }
    // 判断语言获取(或设置)
    // 若不存在,直接返回全大写$name
    if (is_string($name)) {
        $name = strtolower($name);
        if (is_null($value)) {
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        } elseif (is_array($value)) {
            // 支持变量
            $replace = array_keys($value);
            foreach ($replace as &$v) {
                $v = '{$' . $v . '}';
            }
            return str_replace($replace, $value, isset($_lang[$name]) ? $_lang[$name] : $name);
        }
        $_lang[$name] = $value; // 语言定义
        return null;
    }
    // 批量定义
    if (is_array($name)) {
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_LOWER));
    }

    return null;
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @param bool $ucfirst 首字母是否大写（驼峰规则）
 * @return string
 */
function parse_name($name, $type = 0, $ucfirst = true)
{
    if ($type) {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name);
        return $ucfirst ? ucfirst($name) : lcfirst($name);
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * 写入日志文件
 *
 * @param string $message
 * @param array $context
 * @param string $level
 * @param string $channel
 */
function logResult($message = '', $context = [], $level = 'info', $channel = '')
{
    if ($channel) {
        \Illuminate\Support\Facades\Log::channel($channel)->$level($message, $context);
    } else {
        \Illuminate\Support\Facades\Log::$level($message, $context);
    }
}

/**
 * 自动转换字符集 支持数组转换
 * @param $string
 * @param string $from
 * @param string $to
 * @return array|false|string|string[]|null
 */
function autoCharset($string, $from = 'gbk', $to = 'utf-8')
{
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($string) || (is_scalar($string) && !is_string($string))) {
        //如果编码相同或者非字符串标量则不转换
        return $string;
    }
    if (is_string($string)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($string, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $string);
        } else {
            return $string;
        }
    } elseif (is_array($string)) {
        foreach ($string as $key => $val) {
            $_key = autoCharset($key, $from, $to);
            $string[$_key] = autoCharset($val, $from, $to);
            if ($key != $_key) {
                unset($string[$key]);
            }
        }
        return $string;
    } else {
        return $string;
    }
}


/**
 * html代码输入
 *
 * @param $str
 *
 * @return string
 */
function html_in($str)
{
    $str = trim($str);
    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    $str = addslashes($str);
    return $str;
}

/**
 * html代码输出
 *
 * @param $str
 *
 * @return string
 */
function html_out($str)
{
    if (function_exists('htmlspecialchars_decode')) {
        $str = htmlspecialchars_decode($str);
    } else {
        $str = html_entity_decode($str);
    }
    $str = stripslashes($str);
    return $str;
}

/**
 * HTML代码过滤
 * @param string $str 字符串
 * @return string
 */
function escapeHtml($str)
{
    $search = array(
        "'<script[^>]*?>.*?</script>'si",  // 去掉 javascript
        "'<iframe[^>]*?>.*?</iframe>'si", // 去掉iframe
    );

    return preg_replace($search, '', $str);
}

/**
 *  处理post get输入参数 兼容php5.4以上magic_quotes_gpc后默认开启后 处理重复转义的问题
 * @return string $str
 */
function new_html_in($str)
{
    $str = trim($str);
    return htmlspecialchars($str);
}

/**
 * 处理微信素材路径 兼容php5.6+
 * @param string $file 图片完整路径 D:/www/data/123.png
 * @return
 */
function realpath_wechat($file)
{
    if (class_exists('\CURLFile')) {
        return new \CURLFile(realpath($file));
    } else {
        return '@' . realpath($file);
    }
}

/**
 * 将对象成员变量或者数组的特殊字符进行转义
 *
 * @access   public
 * @param mix $obj 对象或者数组
 * @return   mix                  对象或者数组
 * @author   Xuan Yan
 */
function addslashes_deep_obj($obj)
{
    if (is_object($obj) == true) {
        foreach ($obj as $key => $val) {
            $obj->$key = addslashes_deep($val);
        }
    } else {
        $obj = addslashes_deep($obj);
    }

    return $obj;
}

/**
 * 转义decode JSON 对象
 *
 * @param $text
 * @param int $type 0 对象,1数组
 * @return bool|mix|string
 */
function dsc_decode($text, $type = 0)
{
    if (empty($text)) {
        return '';
    } elseif (!is_string($text)) {
        return false;
    }

    return addslashes_deep_obj(json_decode(stripslashes($text), $type));
}


/**
 * 递归方式的对变量中的特殊字符进行转义
 * @param $value
 * @return array|string
 */
function addslashes_deep($value)
{
    if (empty($value)) {
        return $value;
    } else {
        if (is_array($value)) {
            return array_map('addslashes_deep', $value);
        } else {
            $value = simple_remove_xss($value);
            return addslashes($value);
        }
    }
}

/**
 * XSS（跨站脚本攻击）可以用于窃取其他用户的Cookie信息，要避免此类问题，可以采用如下解决方案：
 * 1.直接过滤所有的JavaScript脚本；
 * 2.转义Html元字符，使用htmlentities、htmlspecialchars等函数；
 * 3.系统的扩展函数库提供了XSS安全过滤的remove_xss方法；
 * 4.对URL访问的一些系统变量做XSS处理。
 *
 * 移除Html代码中的XSS攻击
 *
 * @param $val
 * @return string
 */
function simple_remove_xss($val)
{
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <javascript>
    // note that you have to handle splits with
    // $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=@avascript:alert('XSS')>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';

    for ($i = 0; $i < strlen($search); $i++) {
        // ;? matches the ;, which is optional
        // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

        // @ @ search for the hex values
        $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val);
        // @ @ 0{0,7} matches '0' zero to seven times
        $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);
    }

    // now the only remaining whitespace attacks are, and later since they *are* allowed in some inputs
    $ra1 = array('expression', 'applet', 'embed', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra = array_merge($ra1, $ra2);

    $found = true; // keep replacing as long as the previous round replaced something

    while ($found == true) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern .= '/i';
            $replacement = 'data-xss'; // substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
            $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
            if ($val_before == $val) {
                // no replacements were made, so exit the loop
                $found = false;
            }
        }
    }

    return $val;
}

/**
 * 合并多维数组 合并arr2至arr1
 * @param array $arr1
 * @param array $arr2
 * @return array
 */
function merge_arrays($arr1 = [], $arr2 = [])
{
    foreach ($arr2 as $key => $value) {
        if (array_key_exists($key, $arr1) && is_array($value)) {
            $arr1[$key] = merge_arrays($arr1[$key], $arr2[$key]);
        } else {
            $arr1[$key] = $value;
        }
    }

    return $arr1;
}