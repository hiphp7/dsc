<?php

namespace App\Libraries;

use App\Models\ShopConfig;

/**
 * 基础类
 */
class Shop
{
    public $db_name = '';
    public $prefix = 'dsc_';

    /**
     * 构造函数
     *
     * @access  public
     * @param string $ver 版本号
     *
     * @return  void
     */
    public function __construct()
    {
        $this->db_name = config('database.connections.mysql.database');
        $this->prefix = config('database.connections.mysql.prefix');
    }

    /**
     * 将指定的表名加上前缀后返回
     *
     * @access  public
     * @param string $str 表名
     *
     * @return  string
     */
    public function table($str)
    {
        return '`' . $this->db_name . '`.`' . $this->prefix . $str . '`';
    }

    /**
     * DSCMALL 密码编译方法;
     *
     * @access  public
     * @param string $pass 需要编译的原始密码
     *
     * @return  string
     */
    public function compile_password($pass)
    {
        return md5($pass);
    }

    /**
     * 取得当前的域名
     *
     * @access  public
     *
     * @return  string      当前的域名
     */
    public function get_domain()
    {
        /* 协议 */
        $protocol = $this->http();

        /* 域名或IP地址 */
        if (request()->server('HTTP_X_FORWARDED_HOST')) {
            $host = request()->server('HTTP_X_FORWARDED_HOST');
        } elseif (request()->server('HTTP_HOST')) {
            $host = request()->server('HTTP_HOST');
        } else {
            /* 端口 */
            if (request()->server('SERVER_PORT')) {
                $port = ':' . request()->server('SERVER_PORT');

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (request()->server('SERVER_NAME')) {
                $host = request()->server('SERVER_NAME') . $port;
            } elseif (request()->server('SERVER_ADDR')) {
                $host = request()->server('SERVER_ADDR') . $port; //IP
            }
        }

        return $protocol . $host;
    }

    /**
     * 获得 DSC 当前环境的 URL 地址
     *
     * @access  public
     *
     * @return  void
     */
    public function url()
    {
        $root = '';

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return $this->get_domain() . $root;
    }

    /**
     * 获得 DSC 当前环境的 商家后台 URL 地址  by kong
     *
     * @access  public
     *
     * @return  void
     */
    public function seller_url($path = '')
    {
        if ($path == '') {
            $path = SELLER_PATH;
        }
        $curr = strpos(PHP_SELF, $path . '/') !== false ?
            preg_replace('/(.*)(' . $path . ')(\/?)(.)*/i', '\1', dirname(PHP_SELF)) :
            dirname(PHP_SELF);

        $root = str_replace('\\', '/', $curr);

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return $this->get_domain() . $root;
    }


    /**
     * 获得 DSC 当前环境的 门店后台 URL 地址  by kong
     *
     * @access  public
     *
     * @return  void
     */
    public function stores_url()
    {
        $root = '';

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return $this->get_domain() . $root;
    }

    /**
     * 获得 DSC 当前环境的 HTTP 协议方式
     *
     * @access  public
     *
     * @return  void
     */
    public function http()
    {
        if (request()->server('HTTPS')) {
            return (request()->server('HTTPS') && (strtolower(request()->server('HTTPS')) != 'off')) ? 'https://' : 'http://';
        } elseif (request()->server('HTTP_X_FORWARDED_PROTO')) {
            $proto_http = strtolower(request()->server('HTTP_X_FORWARDED_PROTO'));

            if (strpos($proto_http, 'https') !== false) {
                $proto_http = 'https://';
            } else {
                $proto_http = 'http://';
            }

            return $proto_http;
        } else {
            return 'http://';
        }
    }

    /**
     * 转换数据
     */
    public function getCfgValBase($arr = [])
    {
        $cfg = [];
        if ($arr) {
            foreach ($arr as $row) {
                array_push($cfg, $row['code'] . "**" . $row['value']);
            }

            $cfg2 = [];
            foreach ($cfg as $key => $rows) {
                $rows = explode('**', $rows);
                $cfg2[$rows[0]] = $rows[1];
            }

            $cfg = $cfg2;
        }

        return $cfg;
    }

    public function getAmountField()
    {
        return "goods_amount + tax + shipping_fee" .
            " + insure_fee + pay_fee + pack_fee" .
            " + card_fee ";
    }

    /**
     * 获得数据目录的路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    public function data_dir($sid = 0)
    {
        if (empty($sid)) {
            $s = 'data';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= $sid % 3000;
        }
        return $s;
    }

    /**
     * 获得图片的目录路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    public function image_dir($sid = 0)
    {
        if (empty($sid)) {
            $s = 'images';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= ($sid % 3000) . '/';
            $s .= 'images';
        }
        return $s;
    }

    /**
     * 数组分页函数 核心函数 array_slice
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $page_size  每页多少条数据
     * $page  当前第几页
     * $array  查询出来的所有数组
     * order 0 - 不变   1- 反序
     */
    public function page_array($page_size = 1, $page = 1, $array = [], $order = 0, $filter_arr = [])
    {
        $arr = [];
        if ($array) {
            global $countpage; #定全局变量

            $start = ($page - 1) * $page_size; #计算每次分页的开始位置

            if ($order == 1) {
                $array = array_reverse($array);
            }

            $totals = count($array);
            $countpage = ceil($totals / $page_size); #计算总页面数
            $pagedata = array_slice($array, $start, $page_size);

            $filter = [
                'page' => $page,
                'page_size' => $page_size,
                'record_count' => $totals,
                'page_count' => $countpage
            ];

            if ($filter_arr) {
                $filter = array_merge($filter, $filter_arr);
            }

            $arr = ['list' => $pagedata, 'filter' => $filter, 'page_count' => $countpage, 'record_count' => $totals];
        }

        return $arr; #返回查询数据
    }

    /**
     * 版本号
     */
    public function getVersion()
    {
        return VERSION;
    }

    /**
     * 版本时间
     */
    public function getRelease()
    {
        return RELEASE;
    }

    /*
     * 防止SQL注入
     * 过滤数组参数
     */
    public function get_explode_filter($str_arr, $type = 0)
    {
        switch ($type) {
            case 1:
                $str = 1;
                break;
            default:
                $str = $this->return_intval($str_arr);
                break;
        }

        return $str;
    }

    /*
     * 整数类型
     * 返回intval
     */
    public function return_intval($str)
    {
        $new_str = '';
        if ($str) {
            $str = explode(",", $str);

            foreach ($str as $key => $row) {
                $row = intval($row);

                if ($row) {
                    $new_str .= $row . ",";
                }
            }
        }

        $new_str = substr($new_str, 0, -1);
        return $new_str;
    }

    /*
     * 生成短信接口
     * 返回string
     */
    public function getGenerateUrl($arr)
    {
        $arr = unserialize($arr);

        $url = '';
        if ($arr) {
            foreach ($arr as $key => $val) {
                $key = $key + 1;

                $strl = substr($val, 0, 1);
                $strr1 = substr($val, 0, 1);
                $strr2 = substr($val, -1);
                $strr = $strr1 . $strr2;

                if ($key < 10) {
                    if ((int)$strl == $key) {
                        $val = substr($val, 1);
                    }
                } else {
                    if ((int)$strr == $key) {
                        $val = substr($val, 1, -1);
                    }
                }

                $arr[$key] = $val;

                unset($arr[0]);
            }

            $url = $arr[1] . $arr[2] . $arr[3] . $arr[4] . $arr[5] . "." . $arr[6] . $arr[7] . $arr[8] .
                $arr[9] . "." . $arr[10] . "/" . $arr[11] . $arr[12] . $arr[13] . "/" . $arr[14] . $arr[15];
        }

        return $url;
    }

    /**
     * 判断是否纯字母
     */
    public function preg_is_letter($str)
    {
        $preg = '[^A-Za-z]+';
        if (preg_match("/$preg/", $str)) {
            return false;   //不是由纯字母组成
        } else {
            return true;    //全部由字母组成
        }
    }

    /**
     * 获取某个录下有多少个文件
     */
    public function get_dir_file_count($dir)
    {
        $count = sizeof(scandir($dir)) - 2;
        return $count;
    }

    public function byte_format($size, $dec = 2)
    {
        $a = ["B", "KB", "MB", "GB", "TB", "PB"];
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec) . " " . $a[$pos];
    }

    /**
     * 获取某个录下文件名称
     */
    public function get_file_list($dir)
    {
        $arr['all_size'] = 0;
        $arr['all_size_name'] = '';
        if (file_exists($dir)) {
            foreach (scandir($dir) as $v) {
                if (!is_dir($v)) {//如果不是目录，就是文件了
                    $size = filesize($dir . "/" . $v);
                    $arr['all_size'] += $size;
                }
            }
        }

        if ($arr['all_size'] > (1024 * 1024)) {
            $arr['all_size'] = round($arr['all_size'] / 1024 / 1024, 2);
            $arr['all_size_name'] = $arr['all_size'] . " " . "G";
        } else {
            $arr['all_size'] = round($arr['all_size'] / 1024, 2);
            $arr['all_size_name'] = $arr['all_size'] . " " . "MB";
        }

        return $arr;
    }

    /**
     * 查询短信设置类型
     * $code sms_type
     * 0：互亿
     * 1：阿里大于
     * 2：阿里短信服务
     */
    public function get_sms_type($code = 'sms_type')
    {
        $val = ShopConfig::where('code', $code)->value('value');
        return $val ? $val : '';
    }
}
