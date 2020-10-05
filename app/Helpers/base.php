<?php

use App\Libraries\Http;
use App\Libraries\Iconv;
use App\Libraries\Image;
use App\Models\OrderInfo;
use App\Models\PayLog;
use App\Models\ShopConfig;
use App\Models\SourceIp;
use App\Repositories\Common\DscRepository;

/**
 * 获得服务器上的 GD 版本
 *
 * @access      public
 * @return      int         可能的值为0，1，2
 */
function gd_version()
{
    return Image::gd_version();
}

/**
 * 文件或目录权限检查函数
 *
 * @access          public
 * @param string $file_path 文件路径
 * @param bool $rename_prv 是否在检查修改权限时检查执行rename()函数的权限
 *
 * @return          int     返回值的取值范围为{0 <= x <= 15}，每个值表示的含义可由四位二进制数组合推出。
 *                          返回值在二进制计数法中，四位由高到低分别代表
 *                          可执行rename()函数权限、可对文件追加内容权限、可写入文件权限、可读取文件权限。
 */
function file_mode_info($file_path)
{
    /* 如果不存在，则不可读、不可写、不可改 */
    if (!file_exists($file_path)) {
        return false;
    }

    $mark = 0;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        /* 测试文件 */
        $test_file = $file_path . '/cf_test.txt';

        /* 如果是目录 */
        if (is_dir($file_path)) {
            /* 检查目录是否可读 */
            $dir = @opendir($file_path);
            if ($dir === false) {
                return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
            }
            if (@readdir($dir) !== false) {
                $mark ^= 1; //目录可读 001，目录不可读 000
            }
            @closedir($dir);

            /* 检查目录是否可写 */
            $fp = @fopen($test_file, 'wb');
            if ($fp === false) {
                return $mark; //如果目录中的文件创建失败，返回不可写。
            }
            if (@fwrite($fp, 'directory access testing.') !== false) {
                $mark ^= 2; //目录可写可读011，目录可写不可读 010
            }
            @fclose($fp);

            @unlink($test_file);

            /* 检查目录是否可修改 */
            $fp = @fopen($test_file, 'ab+');
            if ($fp === false) {
                return $mark;
            }
            if (@fwrite($fp, "modify test.\r\n") !== false) {
                $mark ^= 4;
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if (@rename($test_file, $test_file) !== false) {
                $mark ^= 8;
            }
            @unlink($test_file);
        } /* 如果是文件 */
        elseif (is_file($file_path)) {
            /* 以读方式打开 */
            $fp = @fopen($file_path, 'rb');
            if ($fp) {
                $mark ^= 1; //可读 001
            }
            @fclose($fp);

            /* 试着修改文件 */
            $fp = @fopen($file_path, 'ab+');
            if ($fp && @fwrite($fp, '') !== false) {
                $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if (@rename($test_file, $test_file) !== false) {
                $mark ^= 8;
            }
        }
    } else {
        if (@is_readable($file_path)) {
            $mark ^= 1;
        }

        if (@is_writable($file_path)) {
            $mark ^= 14;
        }
    }

    return $mark;
}

function log_write($arg, $file = '', $line = '')
{
    $str = "\r\n-- " . date('Y-m-d H:i:s') . " --------------------------------------------------------------\r\n";
    $str .= "FILE: $file\r\nLINE: $line\r\n";

    if (is_array($arg)) {
        $str .= '$arg = array(';
        foreach ($arg as $val) {
            foreach ($val as $key => $list) {
                $str .= "'$key' => '$list'\r\n";
            }
        }
        $str .= ")\r\n";
    } else {
        $str .= $arg;
    }

    file_put_contents(storage_path(DATA_DIR . '/log.txt', $str));
}

/**
 * 检查目标文件夹是否存在，如果不存在则自动创建该目录
 *
 * @access      public
 * @param string      folder     目录路径。不能使用相对于网站根目录的URL
 *
 * @return      bool
 */
function make_dir($folder)
{
    return file_exists($folder) ? is_dir($folder) : mkdir($folder, 0755, true);
}

/**
 * 获得系统是否启用了 gzip
 *
 * @access  public
 *
 * @return  boolean
 */
function gzip_enabled()
{
    static $enabled_gzip = null;

    if ($enabled_gzip === null) {
        $enabled_gzip = ($GLOBALS['_CFG']['enable_gzip'] && function_exists('ob_gzhandler'));
    }

    return $enabled_gzip;
}

/**
 * 处理sql盲注
 * @param $value
 * @return int|string
 */
function dsc_filter($value)
{
    if (stripos($value, ',') === false) {
        $value = intval($value);
    } else {
        $value_arr = explode(',', $value);
        foreach ($value_arr as $k => $v) {
            $value_arr[$k] = intval($v);
        }
        $value = implode(',', $value_arr);
    }

    return $value;
}

/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param mix $value
 *
 * @return  mix
 */
function stripslashes_deep($value)
{
    if (empty($value)) {
        return $value;
    } else {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}

/**
 *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 *
 * @access  public
 * @param string $str 待转换字串
 *
 * @return  string       $str         处理后字串
 */
function make_semiangle($str)
{
    $arr = ['０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
        '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
        'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
        'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
        'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
        'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
        'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z',
        '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
        '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
        '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
        '》' => '>',
        '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
        '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
        '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
        '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
        '　' => ' ', '<' => '＜', '>' => '＞'];

    return strtr($str, $arr);
}

/**
 * 过滤用户输入的基本数据，防止script攻击
 *
 * @access      public
 * @return      string
 */
function compile_str($str)
{
    $arr = ['<' => '＜', '>' => '＞'];

    return strtr($str, $arr);
}

/**
 * 检查文件类型
 *
 * @access      public
 * @param string      filename            文件名
 * @param string      realname            真实文件名
 * @param string      limit_ext_types     允许的文件类型
 * @return      string
 */
function check_file_type($filename, $realname = '', $limit_ext_types = '')
{
    if ($realname) {
        $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
    } else {
        $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false) {
        return '';
    }

    $str = $format = '';

    $file = @fopen($filename, 'rb');
    if ($file) {
        $str = @fread($file, 0x400); // 读取前 1024 个字节
        @fclose($file);
    } else {
        if (stristr($filename, base_path()) === false) {
            if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                $extname == 'xls' || $extname == 'txt' || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                $extname == 'pdf' || $extname == 'rm' || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                $extname == 'swf' || $extname == 'chm' || $extname == 'sql' || $extname == 'cert' || $extname == 'pptx' ||
                $extname == 'xlsx' || $extname == 'docx' || $extname == 'php'
            ) {
                $format = $extname;
            }
        } else {
            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2) {
        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt') {
            $format = 'mid';
        } elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav') {
            $format = 'wav';
        } elseif (substr($str, 0, 3) == "\xFF\xD8\xFF") {
            $format = 'jpg';
        } elseif (substr($str, 0, 4) == 'GIF8' && $extname != 'txt') {
            $format = 'gif';
        } elseif (substr($str, 0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            $format = 'png';
        } elseif (substr($str, 0, 2) == 'BM' && $extname != 'txt') {
            $format = 'bmp';
        } elseif ((substr($str, 0, 3) == 'CWS' || substr($str, 0, 3) == 'FWS') && $extname != 'txt') {
            $format = 'swf';
        } elseif (substr($str, 0, 4) == "\xD0\xCF\x11\xE0") {   // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'doc') {
                $format = 'doc';
            } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xls') {
                $format = 'xls';
            } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt') {
                $format = 'ppt';
            }
        } elseif (substr($str, 0, 4) == "PK\x03\x04") {
            if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'docx') {
                $format = 'docx';
            } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xlsx') {
                $format = 'xlsx';
            } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'pptx') {
                $format = 'pptx';
            } else {
                $format = 'zip';
            }
        } elseif (substr($str, 0, 4) == 'Rar!' && $extname != 'txt') {
            $format = 'rar';
        } elseif (substr($str, 0, 4) == "\x25PDF") {
            $format = 'pdf';
        } elseif (substr($str, 0, 3) == "\x30\x82\x0A") {
            $format = 'cert';
        } elseif (substr($str, 0, 4) == 'ITSF' && $extname != 'txt') {
            $format = 'chm';
        } elseif (substr($str, 0, 4) == "\x2ERMF") {
            $format = 'rm';
        } elseif ($extname == 'sql') {
            $format = 'sql';
        } elseif ($extname == 'txt') {
            $format = 'txt';
        } elseif ($extname == 'php') {
            $format = 'php';
        }
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false) {
        $format = '';
    }

    return $format;
}

/**
 * 对 MYSQL LIKE 的内容进行转义
 *
 * @access      public
 * @param string      string  内容
 * @return      string
 */
function mysql_like_quote($str)
{
    return strtr($str, ["\\\\" => "\\\\\\\\", '_' => '\_', '%' => '\%', "\'" => "\\\\\'"]);
}

/**
 * 获取服务器的ip
 *
 * @access      public
 *
 * @return string
 **/
function real_server_ip()
{
    static $serverIp = null;

    if ($serverIp !== null) {
        return $serverIp;
    }

    if (request()->server()) {
        if (request()->server('SERVER_ADDR')) {
            $serverIp = request()->server('SERVER_ADDR');
        } else {
            $serverIp = '0.0.0.0';
        }
    } else {
        $serverIp = getenv('SERVER_ADDR');
    }

    return $serverIp;
}

/**
 * 自定义 header 函数，用于过滤可能出现的安全隐患
 *
 * @param $string
 * @param bool $replace
 * @param int $http_response_code
 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|string
 */
function dsc_header($string)
{
    if (strpos($string, '../upgrade/index.php') === 0) {
        return '<script type="text/javascript">window.location.href="' . $string . '";</script>';
    }

    $string = str_replace(["\r", "\n"], '', $string);
    if (preg_match('/^\s*location:/is', $string)) {
        $string = trim(str_ireplace('location:', '', $string));
    }

    $prefix = '';
    if (defined('ECS_ADMIN')) {
        $prefix = ADMIN_PATH . '/';
    } elseif (defined('ECS_SELLER')) {
        $prefix = SELLER_PATH . '/';
    } elseif (defined('ECS_STORE')) {
        $prefix = STORES_PATH . '/';
    } elseif (defined('ECS_SUPPLIER')) {
        $prefix = SUPPLLY_PATH . '/';
    }

    return redirect($prefix . $string);
}

function dsc_iconv($source_lang, $target_lang, $source_string = '')
{
    static $chs = null;

    /* 如果字符串为空或者字符串不需要转换，直接返回 */
    if ($source_lang == $target_lang || $source_string == '' || preg_match("/[\x80-\xFF]+/", $source_string) == 0) {
        return $source_string;
    }

    if ($chs === null) {
        $chs = app(Iconv::class);
    }

    return $chs->Convert($source_lang, $target_lang, $source_string);
}

function ecs_geoip($ip)
{
    static $fp = null, $offset = [], $index = null;

    $ip = gethostbyname($ip);
    $ipdot = explode('.', $ip);
    $ip = pack('N', ip2long($ip));

    $ipdot[0] = (int)$ipdot[0];
    $ipdot[1] = (int)$ipdot[1];
    if ($ipdot[0] == 10 || $ipdot[0] == 127 || ($ipdot[0] == 192 && $ipdot[1] == 168) || ($ipdot[0] == 172 && ($ipdot[1] >= 16 && $ipdot[1] <= 31))) {
        return 'LAN';
    }

    if ($fp === null) {
        $fp = fopen(resource_path('codetable/ipdata.dat'), 'rb');
        if ($fp === false) {
            return 'Invalid IP data file';
        }
        $offset = unpack('Nlen', fread($fp, 4));
        if ($offset['len'] < 4) {
            return 'Invalid IP data file';
        }
        $index = fread($fp, $offset['len'] - 4);
    }

    $length = $offset['len'] - 1028;
    $start = unpack('Vlen', $index[$ipdot[0] * 4] . $index[$ipdot[0] * 4 + 1] . $index[$ipdot[0] * 4 + 2] . $index[$ipdot[0] * 4 + 3]);
    for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {
        if ($index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3} >= $ip) {
            $index_offset = unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
            $index_length = unpack('Clen', $index{$start + 7});
            break;
        }
    }

    fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
    $area = fread($fp, $index_length['len']);

    fclose($fp);
    $fp = null;

    return $area;
}

/**
 * 将上传文件转移到指定位置
 *
 * @param string $file_name
 * @param string $target_name
 * @return blog
 */
function move_upload_file($file_name, $target_name = '')
{
    if (function_exists("move_uploaded_file")) {
        if (move_uploaded_file($file_name, $target_name)) {
            @chmod($target_name, 0755);
            return true;
        } elseif (copy($file_name, $target_name)) {
            @chmod($target_name, 0755);
            return true;
        }
    } elseif (copy($file_name, $target_name)) {
        @chmod($target_name, 0755);
        return true;
    }
    return false;
}

/**
 * 将JSON传递的参数转码
 *
 * @param string $str
 * @return string
 */
function json_str_iconv($str)
{
    if (EC_CHARSET != 'utf-8') {
        if (is_string($str)) {
            return addslashes(stripslashes(dsc_iconv('utf-8', EC_CHARSET, $str)));
        } elseif (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = json_str_iconv($value);
            }
            return $str;
        } elseif (is_object($str)) {
            foreach ($str as $key => $value) {
                $str->$key = json_str_iconv($value);
            }
            return $str;
        } else {
            return $str;
        }
    }
    return $str;
}

/**
 * 循环转码成utf8内容
 *
 * @param string $str
 * @return string
 */
function to_utf8_iconv($str)
{
    if (EC_CHARSET != 'utf-8') {
        if (is_string($str)) {
            return dsc_iconv(EC_CHARSET, 'utf-8', $str);
        } elseif (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = to_utf8_iconv($value);
            }
            return $str;
        } elseif (is_object($str)) {
            foreach ($str as $key => $value) {
                $str->$key = to_utf8_iconv($value);
            }
            return $str;
        } else {
            return $str;
        }
    }
    return $str;
}

/**
 * 获取文件后缀名,并判断是否合法
 *
 * @param string $file_name
 * @param array $allow_type
 * @return blob
 */
function get_file_suffix($file_name, $allow_type = [])
{
    $file_name_ex = explode('.', $file_name);
    $file_suffix = strtolower(array_pop($file_name_ex));
    if (empty($allow_type)) {
        return $file_suffix;
    } else {
        if (in_array($file_suffix, $allow_type)) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 读结果缓存文件
 *
 * @param $cache_name
 * @return mixed|string
 * @throws Exception
 */
function read_static_cache($cache_name)
{
    $value = cache($cache_name);
    $value = !is_null($value) ? $value : false;

    return $value;
}

/**
 * 写结果缓存文件
 *
 * @param $cache_name
 * @param $caches
 * @throws Exception
 */
function write_static_cache($cache_name, $caches)
{
    cache()->forever($cache_name, $caches);
}

/**
 * 读结果缓存文件
 *
 * @params  string  $cache_name
 * @return  string   $suffix
 * @return  string   $path
 * @params  string  $type           操作类型  1是可视化
 */
function read_static_flie_cache($cache_name = '', $suffix = '', $path = '', $type = 0)
{
    if (empty($suffix)) {
    }

    $data = '';
    static $result = [];
    if (!empty($result[$cache_name])) {
        return $result[$cache_name];
    }

    //ecmoban模板堂 --zhuo memcached start
    $sel_config = get_shop_config_val('open_memcached');
    if ($sel_config['open_memcached'] == 1 && $type == 0) {
        if (empty($suffix)) {
            if ($cache_name) {
                $files = explode(".", $cache_name);

                if (count($files) > 2) {
                    $path = count($files) - 1;

                    $name = '';
                    if ($files[$path]) {
                        foreach ($files[$path] as $row) {
                            $name .= $row . ".";
                        }

                        $name = substr($name, 0, -1);
                    }

                    $file_path = explode("/", $name);
                } else {
                    $file_path = explode("/", $files[0]);
                }

                $path = count($file_path) - 1;
                $cache_name = $file_path[$path];

                $result[$cache_name] = $GLOBALS['cache']->get('static_caches_' . $cache_name);
            } else {
                $result[$cache_name] = '';
            }
        } else {
            $result[$cache_name] = $GLOBALS['cache']->get('static_caches_' . $cache_name);
        }

        return $result[$cache_name];
    } else {
        if (empty($suffix)) {
            $cache_file_path = $cache_name;
        } else {
            $cache_file_path = $path . $cache_name . "." . $suffix;
        }

        if (file_exists($cache_file_path)) {
            $get_data = file_get_contents($cache_file_path);

            if (!$get_data) {
                $server_model = 0;
                if (!isset($GLOBALS['_CFG']['open_oss'])) {
                    $is_oss = ShopConfig::where('code', 'open_oss')->value('value');
                    $server_model = ShopConfig::where('code', 'server_model')->value('value');
                } else {
                    $is_oss = $GLOBALS['_CFG']['open_oss'];
                }

                if ($is_oss == 1 && $server_model) {
                    $oss_file_path = str_replace(base_path(), '', $cache_file_path);
                    $bucket_info = app(DscRepository::class)->getBucketInfo();

                    $oss_file_path = $bucket_info['endpoint'] . $oss_file_path;

                    $data = file_get_contents($oss_file_path);

                    $oss_file_path = storage_public(str_replace($bucket_info['endpoint'], "", $oss_file_path));

                    file_put_contents($oss_file_path, $data, LOCK_EX);

                    return @file_get_contents($cache_file_path);
                }
            } else {
                return $get_data;
            }
        } else {
            return '';
        }
    }
    //ecmoban模板堂 --zhuo memcached end
}

/**
 * 写结果缓存文件
 *
 * @params  string  $cache_name     名称
 * @params  string  $caches         内容
 * @params  string  $suffix         后缀
 * @params  string  $path           路径
 * @params  string  $type           操作类型  1是可视化
 * @return
 */
function write_static_file_cache($cache_name = '', $caches = '', $suffix = '', $path = '', $type = 0)
{
    $sel_config = get_shop_config_val('open_memcached');
    if ($sel_config['open_memcached'] == 1 && $type == 0) {
        return $GLOBALS['cache']->set('static_caches_' . $cache_name, $caches);
    } else {
        $cache_file_path = $path . $cache_name . "." . $suffix;
        $file_put = @file_put_contents($cache_file_path, $caches, LOCK_EX);

        $cache_file_path = str_replace(base_path(), '', $cache_file_path);

        $server_model = 0;
        if (!isset($GLOBALS['_CFG']['open_oss'])) {
            $is_oss = ShopConfig::where('code', 'open_oss')->value('value');
            $server_model = ShopConfig::where('code', 'server_model')->value('value');
        } else {
            $is_oss = $GLOBALS['_CFG']['open_oss'];
        }

        if ($is_oss == 1 && $server_model) {
            app(DscRepository::class)->getOssAddFile([$cache_file_path]);
        }

        return $file_put;
    }
}

/**
 * 使用全局变量
 * 多维数组转为一维数组
 * $cat_list 数组
 * 不推荐使用该方法
 */
function zhuo_arr_foreach($cat_list, $cat_id = 0)
{
    static $tmp = [];

    foreach ($cat_list as $key => $row) {
        if ($row) {
            $row = array_values($row);
            if (!is_array($row[0])) {
                array_unshift($tmp, $row[0]);
            }

            if (isset($row[1]) && is_array($row[1])) {
                zhuo_arr_foreach($row[1]);
            }
        }
    }

    return $tmp;
}

/**
 * 多维数组转为一维数组
 * $arr 数组
 * 推荐使用该方法
 */
function arr_foreach($multi)
{
    $arr = [];
    foreach ($multi as $key => $val) {
        if (is_array($val)) {
            $arr = array_merge($arr, arr_foreach($val));
        } else {
            $arr[] = $val;
        }
    }
    return $arr;
}

/**
 * 删除数组中指定键值
 * $val 键值
 * $arr 数组
 */
function get_array_flip($val = 0, $arr = [])
{
    if (count($arr) > 1) {
        $arr = array_flip($arr);
        unset($arr[$val]);
        $arr = array_flip($arr);
    }

    return $arr;
}

/*
 * 删除目录
 * 删除目录下文件
 * $dir 目录位置
 * $strpos 文件名称
 * $is_rmdir 是否删除目录
 */
function get_deldir($dir, $strpos = '', $is_rmdir = false)
{
    if (file_exists($dir)) {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;

                if ($strpos) { //删除指定名称文件
                    $spos = strpos($fullpath, $strpos);
                    if ($spos !== false) {
                        if (!is_dir($fullpath)) {
                            unlink($fullpath);
                        } else {
                            get_deldir($fullpath);
                        }
                    }
                } else {  //删除所有文件
                    if (!is_dir($fullpath)) {
                        unlink($fullpath);
                    } else {
                        get_deldir($fullpath);
                    }
                }
            }
        }

        closedir($dh);

        //删除当前文件夹
        if ($is_rmdir == true) {
            if (file_exists($dir) && rmdir($dir)) {
                return true;
            } else {
                return false;
            }
        }
    }
}

/* 递归删除目录 */
function file_del($path)
{
    if (is_dir($path)) {
        $file_list = scandir($path);
        foreach ($file_list as $file) {
            if ($file != '.' && $file != '..') {
                file_del($path . '/' . $file);
            }
        }
        @rmdir($path);  //这种方法不用判断文件夹是否为空,  因为不管开始时文件夹是否为空,到达这里的时候,都是空的
    } else {
        @unlink($path);    //这两个地方最好还是要用@屏蔽一下warning错误,看着闹心
    }
}

/**
 * 删除文件
 */
function dsc_unlink($file = '', $path = '')
{
    if ($file) {
        if (is_array($file)) {
            foreach ($file as $key => $row) {
                if ($row) {
                    $row = trim($row);
                    $row = $path . $row;
                    if (is_file($row)) {
                        unlink($row);
                    }
                }
            }
        } else {
            $file = trim($file);
            $file = $path . $file;
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

//数组排序--根据键的值的数值排序
function get_array_sort($arr, $keys, $type = 'asc')
{
    $new_array = [];
    if (!empty($arr) && is_array($arr)) {
        $keysvalue = $new_array = [];
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
    }

    return $new_array;
}

/*
 * 获取当前目录下的文件或目录
 */
function get_dir_file_list($dir = '', $type = 0, $explode = '')
{
    if (empty($dir)) {
        return false;
    }

    $arr = [];
    if (file_exists($dir)) {
        if (is_dir($dir)) {
            $idx = 0;
            $dir = opendir($dir);
            while (($file = readdir($dir)) !== false) {

                //by yanxin  去掉目录中的./与../
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if (!is_dir($file)) {
                    if ($type == 1) {
                        $arr[$idx]['file'] = $file;
                        $file = explode($explode, $file);
                        $arr[$idx]['web_type'] = $file[0];
                    } else {
                        $arr[$idx] = $file;
                    }

                    $idx++;
                }
            }

            closedir($dir);
        }

        return $arr;
    }
}

/**
 * 获取缓存url地址
 *
 * return string
 */
function getCacheUrl()
{
    $value = cache('html_content');
    $value = !is_null($value) ? $value : '';

    return $value;
}

/**
 * 过滤 $_REQUEST
 * 解决跨站脚本攻击（XSS）
 * script脚本
 */
function get_request_filter($get = '', $type = 0)
{
    if ($get && $type) {
        foreach ($get as $key => $row) {
            $get[$key] = remove_xss($row);
        }
    } else {
        if ($_REQUEST) {
            foreach ($_REQUEST as $key => $row) {
                $_REQUEST[$key] = remove_xss($row);
            }
        }
    }

    if ($get && $type == 1) {
        $_POST = $get;
        return $_POST;
    } elseif ($get && $type == 2) {
        $_GET = $get;
        return $_GET;
    } else {
        return $_REQUEST;
    }
}

//处理xss字符，敏感字符仍然显示但不起作用，如需不显示结合replace_xss()使用
function remove_xss($string) {
    $preg = "/\s<script[\s\S]*?<\/script>/i";
    if ($string && !is_array($string)) {
        $lower_str = strtolower($string);
        $lower_str = !empty($lower_str) ? preg_replace($preg, "", stripslashes($lower_str)) : '';

        if (strpos($lower_str, "</script>") !== false) {
            $string = compile_str($lower_str);
        } elseif (strpos($lower_str, "alert") !== false) {
            $string = '';
        } elseif (strpos($lower_str, "updatexml") !== false || strpos($lower_str, "extractvalue") !== false || strpos($lower_str, "floor") !== false) {
            $string = '';
        } else {
            $string = $string;
        }
    }

    return $string;
}

//替换xss字符，处理显示问题
function replace_xss($string) {
    $xss_str_arr = ['javascript', 'script', 'alert'];
    if($string && !is_array($string) ) {
        $lower_str = strtolower($string);
        $string = str_replace($xss_str_arr, '', $lower_str);
    }

    return $string;
}

/* 重返序列化 */
function dsc_unserialize($serial_str)
{
    $out = preg_replace_callback('!s:(\d+):"(.*?)";!s', function ($r) {
        return 's:' . strlen($r[2]) . ':"' . $r[2] . '";';
    }, $serial_str);
    return unserialize($out);
}

/**
 * 读取文件大小
 */
function get_file_centent_size($dir)
{
    $filesize = filesize($dir) / 1024;
    return sprintf("%.2f", substr(sprintf("%.3f", $filesize), 0, -1));
}

/**
 * 下载远程图片
 * $url 下载外链文件地址
 * $path 存放的路径
 */
function get_http_basename($url = '', $path = '', $goods_lib = '')
{
    $Http = app(Http::class);
    $return_content = $Http->doGet($url);
    $url = basename($url);
    if ($goods_lib) {
        $filename = $path;
    } else {
        $filename = $path . "/" . $url;
    }

    if (file_put_contents($filename, $return_content)) {
        return $filename;
    } else {
        return false;
    }
}

/**
 * 获取随机数值
 */
function get_mt_rand($ran_num = 4)
{
    $str = '';
    for ($i = 0; $i < $ran_num; $i++) {
        $str .= mt_rand(0, 9);
    }

    return $str;
}

/* 合并二维数组重新数据数组 */
function get_merge_mult_arr($row)
{
    $item = [];
    foreach ($row as $k => $v) {
        if (!isset($item[$v['brand_id']])) {
            $item[$v['brand_id']] = $v;
        } else {
            $item[$v['brand_id']]['number'] += $v['number'];
        }
    }

    return $item;
}

//记录访问者统计
function modifyipcount($ip, $store_id)
{
    $t = time();
    $start = local_mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));//当天的开始时间
    $end = local_mktime(23, 59, 59, date("m", $t), date("d", $t), date("Y", $t));//当天的结束时间

    $row = SourceIp::where('ipdata', $ip)
        ->where('storeid', $store_id)
        ->whereBetween('iptime', [$start, $end]);

    $count = $row->count();

    if (!$count) {
        $iptime = time();
        $other = [
            'ipdata' => $ip,
            'iptime' => $iptime,
            'storeid' => $store_id
        ];
        SourceIp::insert($other);
    }
}

/**
 * 转码
 *
 * @access  public
 * @param string $str 转码内容
 *
 * @return  sring
 *
 * 作用：解密JS传字符串
 */
function unescape($str)
{
    $ret = '';
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        if ($str[$i] == '%' && $str[$i + 1] == 'u') {
            $val = hexdec(substr($str, $i + 2, 4));
            if ($val < 0x7f) {
                $ret .= chr($val);
            } elseif ($val < 0x800) {
                $ret .= chr(0xc0 | ($val >> 6)) . chr(0x80 | ($val & 0x3f));
            } else {
                $ret .= chr(0xe0 | ($val >> 12)) . chr(0x80 | (($val >> 6) & 0x3f)) . chr(0x80 | ($val & 0x3f));
            }
            $i += 5;
        } elseif ($str[$i] == '%') {
            $ret .= urldecode(substr($str, $i, 3));
            $i += 2;
        } else {
            $ret .= $str[$i];
        }
    }
    return $ret;
}

/**
 * 跳转首页
 * @param int  user_id 会员ID
 */
function get_go_index($type = 0, $var = false)
{
    if ($type == 1) {
        if (!$var) {
            return dsc_header("Location: " . $GLOBALS['dsc']->url() . "\n");
        }
    } else {
        $user_id = session('user_id', 0);

        if (!$user_id) {
            return dsc_header("Location: " . $GLOBALS['dsc']->url() . "\n");
        }
    }
}

/**
 * 递归本地目录上传文件
 * 上传文件至OSS
 *
 * $dir 指定上传内容目录路径，包含（ROOT_PATH）
 * $path 目录路径，即：不包含（ROOT_PATH）
 * $is_recursive 是否允许递归查询目录
 */
function get_recursive_file_oss($dir, $path = '', $is_recursive = false, $type = 0)
{
    $file_list = scandir($dir);

    $arr = [];
    if ($file_list) {
        foreach ($file_list as $key => $row) {
            if ($is_recursive && is_dir($dir . $row) && !in_array($row, ['.', '..', '...'])) {
                $arr[$key]['child'] = get_recursive_file_oss($dir . $row . "/", $path, $is_recursive, 1);
            } elseif (is_file($dir . $row)) {
                if ($type == 1) {
                    $arr[$key] = $dir . $row;
                } else {
                    $arr[$key] = $path . $row;
                }
            }

            if (isset($arr[$key]) && $arr[$key]) {
                $arr[$key] = str_replace(storage_public(), '', $arr[$key]);
            }
        }

        if ($arr) {
            $arr = arr_foreach($arr);
            $arr = array_unique($arr);
        }
    }

    return $arr;
}

/**
 * 校验是否非法操作
 * reg_token
 * $type 0:dwt 1:lib
 */
function get_dsc_token()
{
    $sc_rand = rand(100000, 999999);
    $sc_guid = sc_guid();

    if (request()->server('HTTP_USER_AGENT')) {
        $token_agent = MD5($sc_guid . "-" . $sc_rand) . MD5(request()->server('HTTP_USER_AGENT'));
    } else {
        $token_agent = MD5($sc_guid . "-" . $sc_rand);
    }

    $dsc_token = MD5($sc_guid . "-" . $sc_rand);

    session([
        'token_agent' => $token_agent
    ]);

    return $dsc_token;
}

/**
 * 更新订单对应的 pay_log
 * 如果未支付，修改支付金额；否则，生成新的支付log
 * @param int $order_id 订单id
 */
function update_pay_log($order_id)
{
    $order_id = intval($order_id);
    if ($order_id > 0) {
        $order_amount = OrderInfo::where('order_id', $order_id)->value('order_amount');

        if (!is_null($order_amount)) {
            $log_id = PayLog::where('order_id', $order_id)
                ->where('order_type', PAY_ORDER)
                ->where('is_paid', 0)
                ->value('log_id');

            if ($log_id > 0) {

                /* 未付款，更新支付金额 */
                PayLog::where('log_id', $log_id)->update(['order_amount' => $order_amount]);
            } else {
                /* 已付款，生成新的pay_log */
                $other = [
                    'order_id' => $order_id,
                    'order_amount' => $order_amount,
                    'order_type' => PAY_ORDER,
                    'is_paid' => 0
                ];
                PayLog::insert($other);
            }
        }
    }
}

/**
 * 数组转换
 * 三维数组转换成二维数组
 */
function get_three_to_two_array($list = [])
{
    $new_list = [];
    if ($list) {
        foreach ($list as $lkey => $lrow) {
            foreach ($lrow as $ckey => $crow) {
                $new_list[] = $crow;
            }
        }
    }

    return $new_list;
}

//打印日志
if (!function_exists('logResult')) {
    function logResult($word = '', $path = '')
    {
        if (empty($path)) {
            $path = storage_public(DATA_DIR . "/log.txt");
        } else {
            if (!file_exists($path)) {
                make_dir($path);
            }

            $path = $path . "/log.txt";
        }

        $word = is_array($word) ? var_export($word, 1) : $word;
        $fp = fopen($path, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, $GLOBALS['_LANG']['implement_time'] . strftime("%Y%m%d%H%M%S", gmtime()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/**
 * 后台处理缓存
 */
function get_admin_seller_static_cache($cache = [])
{
    if ($cache) {
        if (isset($cache['category']) && isset($cache['category']['cache_path']) && !empty($cache['category']['cache_path'])) {
            if (isset($cache['category']['type']) && $cache['category']['type'] == 'add_edit') {

                /**
                 * 前台首页左侧分类树一级分类
                 * start
                 */
                dsc_unlink(storage_path($cache['category']['cache_path'] . "category_tree_leve_one0.php"));

                $cat_list = get_category_leve_one();
                write_static_cache('category_tree_leve_one0', $cat_list);
                /**
                 * 前台首页左侧分类树一级分类
                 * end
                 */
            }
        }
    }
}

/**
 * 字符串截取，支持中文和其他编码
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符 默认 ***
 * @param string $position 截断显示字符位置 默认 1 为中间 例：刘***然，0 为后缀 刘***
 * @return string
 */
function msubstr_ect($str, $start = 0, $length = 1, $charset = "utf-8", $suffix = '***', $position = 1)
{
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
        $slice_end = mb_substr($str, -$length, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        $slice_end = iconv_substr($str, -$length, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
        $slice_end = join("", array_slice($match[0], -$length, $length));
    }

    return $position == 0 ? $slice . $suffix : $slice . $suffix . $slice_end;
}

/**
 * 将字符串以 * 号格式显示 配合msubstr_ect函数使用
 * @example 例如 string_to_star($str,1)  w******f , string_to_star($str,2) we****af
 *
 * @param string $string
 * @param int $num
 * @param string $start_len
 * @return string
 */
function string_to_star($string = '', $num = 3, $start_len = '')
{
    $string_length = strlen($string);
    if ($string_length >= 3 && $string_length > $num) {
        $lenth = $start_len > 0 ? $start_len : $string_length - $num * 2;
        $star_length = '';
        for ($x = 1; $x <= $lenth; $x++) {
            $star_length .= "*";
        }
        $string = msubstr_ect($string, 0, $num, 'utf-8', $star_length);
    }

    return $string;
}