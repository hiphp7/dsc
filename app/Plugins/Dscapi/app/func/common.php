<?php

namespace App\Plugins\Dscapi\app\func;

use App\Repositories\Common\DscRepository;

class common
{
    private static $format = 'json';
    private static $page_size = 10;         //每页条数
    private static $page = 1;               //当前页
    private static $charset = 'utf-8';      //数据类型
    private static $result;                 //返回成功与否
    private static $msg;                    //消息提示
    private static $error;                  //错误类型
    private static $code;                   //错误编码 -- cloud
    private static $message;                //错误提示 -- cloud
    private static $info;                //返回详细信息
    private static $allowOutputType = array(
        'xml' => 'application/xml',
        'json' => 'application/json',
        'html' => 'text/html',
    );

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function __construct($data = array())
    {
        self::common($data);
    }

    /**
     * 构造函数
     *
     * @access  public
     * @return  bool
     */
    public static function common($data = array())
    {
        /* 初始查询条件值 */
        self::$format = isset($data['format']) ? $data['format'] : 'josn';
        self::$page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        self::$page = isset($data['page']) ? $data['page'] : 1;
        self::$msg = isset($data['msg']) ? $data['msg'] : '';
        self::$result = isset($data['result']) ? $data['result'] : 'success';
        self::$error = isset($data['error']) ? $data['error'] : 0;
        self::$code = isset($data['code']) ? $data['code'] : 10000;
        self::$message = isset($data['message']) ? $data['message'] : 'success';
        self::$info = isset($data['info']) ? $data['info'] : [];
    }

    /**
     *  返回结果集
     *
     * @param   mixed $info 返回的有效数据集或是错误说明
     * @param   string $msg 为空或是错误类型代号
     * @param   string $result 请求成功或是失败的标识
     *
     */
    public static function data_back($info = array(), $arr_type = 0)
    {

        /* 二维数组数据 */
        if ($arr_type == 1) {
            $list = self::page_array(self::$page_size, self::$page, $info);    //分页处理
            $info = $list;
        }

        if ($arr_type == 2) {
            $data_arr = array('code' => self::$code, 'message' => self::$message);
        } else {
            $data_arr = array('result' => self::$result, 'error' => self::$error, 'msg' => self::$msg);
        }

        if ($info) {
            $data_arr['info'] = $info;
        } else {
            $data_arr['info'] = self::$info;
        }

        $data_arr = self::to_utf8_iconv($data_arr);  //确保传递的编码为UTF-8

        /* 分为xml和json两种方式 */
        if (self::$format == 'xml') {

            /* xml方式 */
            if (isset(self::$allowOutputType[self::$format])) { //过滤content_type
                header('Content-Type: ' . self::$allowOutputType[self::$format] . '; charset=' . self::$charset);
            }

            return self::xml_encode($data_arr);
        } else {
            /* json方式 */
            if (isset(self::$allowOutputType[self::$format])) { //过滤content_type
                header('Content-Type: ' . self::$allowOutputType[self::$format] . '; charset=' . self::$charset);
            }

            return json_encode($data_arr);
        }
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id 数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    public static function xml_encode($data, $root = 'dsc', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8')
    {
        if (is_array($attr)) {
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}{$attr}>";
        $xml .= self::data_to_xml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
    }

    /**
     * 数据XML编码
     * @param mixed $data 数据
     * @param string $item 数字索引时的节点名称
     * @param string $id 数字索引key转换为的属性名
     * @return string
     */
    public static function data_to_xml($data, $item = 'item', $id = 'id')
    {
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id && $attr = " {$id}=\"{$key}\"";
                $key = $item;
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= (is_array($val) || is_object($val)) ? self::data_to_xml($val, $item, $id) : $val;
            $xml .= "</{$key}>";
        }
        return $xml;
    }

    /**
     * 循环转码成utf8内容
     *
     * @param string $str
     * @return string
     */
    public static function to_utf8_iconv($str)
    {
        if (EC_CHARSET != 'utf-8') {
            if (is_string($str)) {
                return ecs_iconv(EC_CHARSET, 'utf-8', $str);
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
     * 数组分页函数 核心函数 array_slice
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $page_size  每页多少条数据
     * $page  当前第几页
     * $array  查询出来的所有数组
     * order 0 - 不变   1- 反序
     */
    public static function page_array($page_size = 1, $page = 1, $array = array(), $order = 0)
    {
        $arr = array();
        $pagedata = array();
        if ($array) {
            global $countpage; #定全局变量

            $start = ($page - 1) * $page_size; #计算每次分页的开始位置

            if ($order == 1) {
                $array = array_reverse($array);
            }

            if (isset($array['record_count'])) {
                $totals = $array['record_count'];
                $countpage = ceil($totals / $page_size); #计算总页面数
                $pagedata = $array['list'];
            } else {
                $totals = count($array);
                $countpage = ceil($totals / $page_size); #计算总页面数
                $pagedata = array_slice($array, $start, $page_size);
            }

            $filter = array(
                'page' => $page,
                'page_size' => $page_size,
                'record_count' => $totals,
                'page_count' => $countpage
            );

            $arr = array('list' => $pagedata, 'filter' => $filter, 'page_count' => $countpage, 'record_count' => $totals);
        }

        //返回查询数据
        return $arr;
    }

    /**
     * 过滤已存在会员索引值
     * user_name
     */
    public static function get_reference_only($table, $where = 1, $select = '', $type = 0)
    {
        if (!empty($select) && is_array($select)) {
            $select = implode(",", $select);
        } else {
            $select = '*';
        }

        $sql = "SELECT $select FROM " . $GLOBALS['dsc']->table($table) . " WHERE $where";

        if ($type == 1) {
            return $GLOBALS['db']->getRow($sql);
        } else {
            return $GLOBALS['db']->getOne($sql);
        }
    }

    /**
     * 格式化商品图片名称（按目录存储）
     *
     */
    public static function reformat_image_name($type = 0, $goods_id = 0, $source_img = '', $position = '')
    {
        $rand_name = time() . sprintf("%03d", mt_rand(1, 999));
        $img_ext = substr($source_img, strrpos($source_img, '.'));
        $dir = 'images';
        if (defined('IMAGE_DIR')) {
            $dir = IMAGE_DIR;
        }
        $sub_dir = local_date('Ym', time());
        if (!self::make_dir(storage_public($dir . '/' . $sub_dir))) {
            return false;
        }
        if (!self::make_dir(storage_public($dir . '/' . $sub_dir . '/source_img'))) {
            return false;
        }
        if (!self::make_dir(storage_public($dir . '/' . $sub_dir . '/goods_img'))) {
            return false;
        }
        if (!self::make_dir(storage_public($dir . '/' . $sub_dir . '/thumb_img'))) {
            return false;
        }
        switch ($type) {
            case 'goods':
                $img_name = $goods_id . '_G_' . $rand_name;
                break;
            case 'goods_thumb':
                $img_name = $goods_id . '_thumb_G_' . $rand_name;
                break;
            case 'gallery':
                $img_name = $goods_id . '_P_' . $rand_name;
                break;
            case 'gallery_thumb':
                $img_name = $goods_id . '_thumb_P_' . $rand_name;
                break;
        }

        if (strpos($source_img, 'temp') !== false) {
            $ex_img = explode('temp', $source_img);
            $source_img = "temp" . $ex_img[1];
        } else {
            if (strpos($source_img, storage_public()) !== false) {
                $source_img = !empty($source_img) ? str_replace(storage_public(), '', $source_img) : '';
            }
        }

        $source_img = storage_public($source_img);
        if ($position == 'source') {
            if (self::move_image_file($source_img, storage_public($dir . '/' . $sub_dir . '/source_img/' . $img_name . $img_ext))) {
                return $dir . '/' . $sub_dir . '/source_img/' . $img_name . $img_ext;
            }
        } elseif ($position == 'thumb') {
            if (self::move_image_file($source_img, storage_public($dir . '/' . $sub_dir . '/thumb_img/' . $img_name . $img_ext))) {
                return $dir . '/' . $sub_dir . '/thumb_img/' . $img_name . $img_ext;
            }
        } else {
            if (self::move_image_file($source_img, storage_public($dir . '/' . $sub_dir . '/goods_img/' . $img_name . $img_ext))) {
                return $dir . '/' . $sub_dir . '/goods_img/' . $img_name . $img_ext;
            }
        }
        return false;
    }

    /**
     * 移动图片
     */
    public static function move_image_file($source, $dest)
    {
        if (@copy($source, $dest)) {
            @unlink($source);
            return true;
        }
        return false;
    }

    /**
     *  获取图片的高度和宽度
     *
     * @access  public
     * @param   string $path 图片路径
     *
     * @return boolen
     */
    public static function get_width_to_height($path, $image_width = 0, $image_height = 0)
    {
        $width = 0;
        $height = 0;

        $img = @getimagesize($path);
        if ($img) {
            $width = $img[0];
            $height = $img[1];

            if ($image_width > $width) {
                $image_width = $width;
            }

            if ($image_height > $height) {
                $image_height = $height;
            }

            $arr = array(
                'width' => $width,
                'height' => $height,
                'image_width' => $image_width,
                'image_height' => $image_height
            );
        }

        return $arr;
    }

    /**
     * 根据来源文件的文件类型创建一个图像操作的标识符
     *
     * @access  public
     * @param   string $img_file 图片文件的路径
     * @param   string $mime_type 图片文件的文件类型
     * @return  resource    如果成功则返回图像操作标志符，反之则返回错误代码
     */
    public static function img_resource($img_file, $mime_type)
    {
        switch ($mime_type) {
            case 1:
            case 'image/gif':
                $res = imagecreatefromgif($img_file);
                break;

            case 2:
            case 'image/pjpeg':
            case 'image/jpeg':
                $res = imagecreatefromjpeg($img_file);
                break;

            case 3:
            case 'image/x-png':
            case 'image/png':
                $res = imagecreatefrompng($img_file);
                break;

            default:
                return false;
        }

        return $res;
    }

    /**
     * 检查目标文件夹是否存在，如果不存在则自动创建该目录
     *
     * @access      public
     * @param       string      folder     目录路径。不能使用相对于网站根目录的URL
     *
     * @return      bool
     */
    public static function make_dir($folder)
    {
        $reval = false;

        if (!file_exists($folder)) {
            /* 如果目录不存在则尝试创建该目录 */
            @umask(0);

            /* 将目录路径拆分成数组 */
            preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

            /* 如果第一个字符为/则当作物理路径处理 */
            $base = ($atmp[0][0] == '/') ? '/' : '';

            /* 遍历包含路径信息的数组 */
            foreach ($atmp[1] as $val) {
                if ('' != $val) {
                    $base .= $val;

                    if ('..' == $val || '.' == $val) {
                        /* 如果目录为.或者..则直接补/继续下一个循环 */
                        $base .= '/';

                        continue;
                    }
                } else {
                    continue;
                }

                $base .= '/';

                if (!file_exists($base)) {
                    /* 尝试创建目录，如果创建失败则继续循环 */
                    if (@mkdir(rtrim($base, '/'), 0777)) {
                        @chmod($base, 0777);
                        $reval = true;
                    }
                }
            }
        } else {
            /* 路径已经存在。返回该路径是不是一个目录 */
            $reval = is_dir($folder);
        }

        clearstatcache();

        return $reval;
    }

    /**
     * 创建图片的缩略图
     *
     * @access  public
     * @param   string $img 原始图片的路径
     * @param   int $thumb_width 缩略图宽度
     * @param   int $thumb_height 缩略图高度
     * @param   strint $path 指定生成图片的目录名
     * @return  mix         如果成功返回缩略图的路径，失败则返回false
     */
    public static function make_thumb($img, $thumb_width = 0, $thumb_height = 0, $path = '', $bgcolor = '', $filename = '')
    {
        $upload_type = 0;
        if ($img && is_array($img)) {
            $upload_type = $img['type'];
            $img = isset($img['img']) ? $img['img'] : '';
        }

        $gd = self::gd_version(); //获取 GD 版本。0 表示没有 GD 库，1 表示 GD 1.x，2 表示 GD 2.x
        if ($gd == 0) {
            return false;
        }

        /* 检查缩略图宽度和高度是否合法 */
        if ($thumb_width == 0 && $thumb_height == 0) {
            return str_replace(storage_public(), '', str_replace('\\', '/', realpath($img)));
        }

        /* 检查原始文件是否存在及获得原始文件的信息 */
        $org_info = @getimagesize($img);
        if (!$org_info) {
            return false;
        }

        if (!self::check_img_function($org_info[2])) {
            return false;
        }

        $img_org = self::img_resource($img, $org_info[2]);

        /* 原始图片以及缩略图的尺寸比例 */
        $scale_org = $org_info[0] / $org_info[1];
        /* 处理只有缩略图宽和高有一个为0的情况，这时背景和缩略图一样大 */
        if ($thumb_width == 0) {
            $thumb_width = $thumb_height * $scale_org;
        }
        if ($thumb_height == 0) {
            $thumb_height = $thumb_width / $scale_org;
        }

        /* 创建缩略图的标志符 */
        if ($gd == 2) {
            $img_thumb = imagecreatetruecolor($thumb_width, $thumb_height);
        } else {
            $img_thumb = imagecreate($thumb_width, $thumb_height);
        }

        /* 背景颜色 */
        if (empty($bgcolor)) {
            $bgcolor = '#FFFFFF';
        }
        $bgcolor = trim($bgcolor, "#");
        sscanf($bgcolor, "%2x%2x%2x", $red, $green, $blue);
        $clr = imagecolorallocate($img_thumb, $red, $green, $blue);
        imagefilledrectangle($img_thumb, 0, 0, $thumb_width, $thumb_height, $clr);

        if ($org_info[0] / $thumb_width > $org_info[1] / $thumb_height) {
            $lessen_width = $thumb_width;
            $lessen_height = $thumb_width / $scale_org;
        } else {
            /* 原始图片比较高，则以高度为准 */
            $lessen_width = $thumb_height * $scale_org;
            $lessen_height = $thumb_height;
        }

        $dst_x = ($thumb_width - $lessen_width) / 2;
        $dst_y = ($thumb_height - $lessen_height) / 2;

        /* 将原始图片进行缩放处理 */
        if ($gd == 2) {
            imagecopyresampled($img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1]);
        } else {
            imagecopyresized($img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1]);
        }

        /* 创建当月目录 */
        if (empty($path)) {
            $admin_dir = local_date('Ym');
            $admin_dir = storage_public(IMAGE_DIR . '/' . $admin_dir . '/' . "admin_0");

            // 如果目标目录不存在，则创建它
            if (!file_exists($admin_dir)) {
                self::make_dir($admin_dir);
            }

            $dir = storage_public(IMAGE_DIR . '/' . date('Ym') . '/' . "admin_0/");
        } else {
            $dir = $path;
        }


        /* 如果目标目录不存在，则创建它 */
        if (!file_exists($dir)) {
            if (!self::make_dir($dir)) {
                return false;
            }
        }

        /* 如果文件名为空，生成不重名随机文件名 */
        if ($filename == '') {
            $filename = self::unique_name($dir);

            /* 生成文件 */
            if (function_exists('imagejpeg')) {
                $filename .= '.jpg';
                imagejpeg($img_thumb, $dir . $filename, 90);
            } elseif (function_exists('imagegif')) {
                $filename .= '.gif';
                imagegif($img_thumb, $dir . $filename);
            } elseif (function_exists('imagepng')) {
                $filename .= '.png';
                imagepng($img_thumb, $dir . $filename);
            } else {
                return false;
            }
        } else {
            imagepng($img_thumb, $dir . $filename);
        }


        imagedestroy($img_thumb);
        imagedestroy($img_org);

        //确认文件是否生成
        if (file_exists($dir . $filename)) {
            if ($upload_type) {
                return $dir . $filename;
            } else {
                return str_replace(storage_public(), '', $dir) . $filename;
            }
        } else {
            return false;
        }
    }

    /**
     *  生成指定目录不重名的文件名
     *
     * @access  public
     * @param   string $dir 要检查是否有同名文件的目录
     *
     * @return  string      文件名
     */
    public static function unique_name($dir)
    {
        $filename = '';
        while (empty($filename)) {
            $filename = self::random_filename();
            if (file_exists($dir . $filename . '.jpg') || file_exists($dir . $filename . '.gif') || file_exists($dir . $filename . '.png')) {
                $filename = '';
            }
        }

        return $filename;
    }

    /**
     * 生成随机的数字串
     *
     * @author: weber liu
     * @return string
     */
    public static function random_filename()
    {
        $str = '';
        for ($i = 0; $i < 9; $i++) {
            $str .= mt_rand(0, 9);
        }

        return time() . $str;
    }

    /**
     * 获得服务器上的 GD 版本
     *
     * @access      public
     * @return      int         可能的值为0，1，2
     */
    public static function gd_version()
    {
        static $version = -1;

        if ($version >= 0) {
            return $version;
        }

        if (!extension_loaded('gd')) {
            $version = 0;
        } else {
            // 尝试使用gd_info函数
            if (PHP_VERSION >= '4.3') {
                if (function_exists('gd_info')) {
                    $ver_info = gd_info();
                    preg_match('/\d/', $ver_info['GD Version'], $match);
                    $version = $match[0];
                } else {
                    if (function_exists('imagecreatetruecolor')) {
                        $version = 2;
                    } elseif (function_exists('imagecreate')) {
                        $version = 1;
                    }
                }
            } else {
                if (preg_match('/phpinfo/', ini_get('disable_functions'))) {
                    /* 如果phpinfo被禁用，无法确定gd版本 */
                    $version = 1;
                } else {
                    // 使用phpinfo函数
                    ob_start();
                    phpinfo(8);
                    $info = ob_get_contents();
                    ob_end_clean();
                    $info = stristr($info, 'gd version');
                    preg_match('/\d/', $info, $match);
                    $version = $match[0];
                }
            }
        }

        return $version;
    }

    /**
     * 检查图片处理能力
     *
     * @access  public
     * @param   string $img_type 图片类型
     * @return  void
     */
    public static function check_img_function($img_type)
    {
        switch ($img_type) {
            case 'image/gif':
            case 1:

                if (PHP_VERSION >= '4.3') {
                    return function_exists('imagecreatefromgif');
                } else {
                    return (imagetypes() & IMG_GIF) > 0;
                }
                break;

            case 'image/pjpeg':
            case 'image/jpeg':
            case 2:
                if (PHP_VERSION >= '4.3') {
                    return function_exists('imagecreatefromjpeg');
                } else {
                    return (imagetypes() & IMG_JPG) > 0;
                }
                break;

            case 'image/x-png':
            case 'image/png':
            case 3:
                if (PHP_VERSION >= '4.3') {
                    return function_exists('imagecreatefrompng');
                } else {
                    return (imagetypes() & IMG_PNG) > 0;
                }
                break;

            default:
                return false;
        }
    }

    /**
     * 下载远程图片
     * $url 下载外链文件地址
     * $path 存放的路径
     */
    public static function get_http_basename($url = '', $path = '', $goods_lib = '')
    {
        $return_content = self::doGet($url);

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
     * 通过get方式获取数据
     * @param string $url
     * @param type $timeout
     * @param type $header
     * @return boolean
     */
    public static function doGet($url, $timeout = 5, $header = "")
    {
        if (empty($url) || empty($timeout)) {
            return false;
        }
        if (!preg_match('/^(http|https)/is', $url)) {
            $url = "http://" . $url;
        }
        $code = self::getSupport();
        switch ($code) {
            case 1:
                return self::curlGet($url, $timeout, $header);
                break;
            case 2:
                return self::socketGet($url, $timeout, $header);
                break;
            case 3:
                return self::phpGet($url, $timeout, $header);
                break;
            default:
                return false;
        }
    }

    /**
     * 通过file_get_contents函数get数据
     * @param type $url
     * @param type $timeout
     * @param type $header
     * @return type
     */
    public static function phpGet($url, $timeout = 5, $header = "")
    {
        $header = empty($header) ? self::defaultHeader() : $header;
        $opts = array(
            'http' => array(
                'protocol_version' => '1.0', //http协议版本(若不指定php5.2系默认为http1.0)
                'method' => "GET", //获取方式
                'timeout' => $timeout, //超时时间
                'header' => $header)
        );
        $context = stream_context_create($opts);
        return @file_get_contents($url, false, $context);
    }

    /**
     * 默认模拟的header头
     * @return string
     */
    public static function defaultHeader()
    {
        $header = "User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12\r\n";
        $header .= "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
        $header .= "Accept-language: zh-cn,zh;q=0.5\r\n";
        $header .= "Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";
        return $header;
    }

    /**
     * 返回请求类型
     * @return int
     */
    public static function getSupport()
    {
        //如果指定访问方式，则按指定的方式去访问
        if (isset(self::$way) && in_array(self::$way, array(1, 2, 3))) {
            return self::$way;
        }

        //自动获取最佳访问方式
        if (function_exists('curl_init')) {//curl方式
            return 1;
        } elseif (function_exists('fsockopen')) {//socket
            return 2;
        } elseif (function_exists('file_get_contents')) {//php系统函数file_get_contents
            return 3;
        } else {
            return 0;
        }
    }

    /**
     * 通过curl get数据
     * @param type $url
     * @param type $timeout
     * @param type $header
     * @return type
     */
    public static function curlGet($url, $timeout = 5, $header = "")
    {
        $header = empty($header) ? self::defaultHeader() : $header;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header)); //模拟的header头
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 通过socket get数据
     * @param type $url
     * @param type $timeout
     * @param type $header
     * @return boolean
     */
    public static function socketGet($url, $timeout = 5, $header = "")
    {
        $header = empty($header) ? self::defaultHeader() : $header;
        $url2 = parse_url($url);
        $url2["path"] = isset($url2["path"]) ? $url2["path"] : "/";
        $url2["port"] = isset($url2["port"]) ? $url2["port"] : 80;
        $url2["query"] = isset($url2["query"]) ? "?" . $url2["query"] : "";
        $host_ip = @gethostbyname($url2["host"]);

        if (($fsock = fsockopen($host_ip, $url2['port'], $errno, $errstr, $timeout)) < 0) {
            return false;
        }
        $request = $url2["path"] . $url2["query"];
        $in = "GET " . $request . " HTTP/1.0\r\n";
        if (false === strpos($header, "Host:")) {
            $in .= "Host: " . $url2["host"] . "\r\n";
        }
        $in .= $header;
        $in .= "Connection: Close\r\n\r\n";

        if (!@fwrite($fsock, $in, strlen($in))) {
            @fclose($fsock);
            return false;
        }
        return self::GetHttpContent($fsock);
    }

    /**
     * 获取通过socket方式get和post页面的返回数据
     * @param type $fsock
     * @return boolean
     */
    public static function GetHttpContent($fsock = null)
    {
        $out = null;
        while ($buff = @fgets($fsock, 2048)) {
            $out .= $buff;
        }
        fclose($fsock);
        $pos = strpos($out, "\r\n\r\n");
        $head = substr($out, 0, $pos);    //http head
        $status = substr($head, 0, strpos($head, "\r\n"));    //http status line
        $body = substr($out, $pos + 4, strlen($out) - ($pos + 4)); //page body
        if (preg_match("/^HTTP\/\d\.\d\s([\d]+)\s.*$/", $status, $matches)) {
            if (intval($matches[1]) / 100 == 2) {
                return $body;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 为某商品生成唯一的货号
     * @param   int $goods_id 商品编号
     * @return  string  唯一的货号
     */
    public static function generate_goods_sn($goods_id)
    {
        $goods_sn = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

        $sql = "SELECT goods_sn FROM " . $GLOBALS['dsc']->table('goods') .
            " WHERE goods_sn LIKE '" . mysql_like_quote($goods_sn) . "%' AND goods_id <> '$goods_id' " .
            " ORDER BY LENGTH(goods_sn) DESC";
        $sn_list = $GLOBALS['db']->getCol($sql);

        if (!empty($sn_list) && in_array($goods_sn, $sn_list)) {
            $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
            $new_sn = $goods_sn . mt_rand(0, $max);
            while (in_array($new_sn, $sn_list)) {
                $new_sn = $goods_sn . mt_rand(0, $max);
            }
            $goods_sn = $new_sn;
        }

        return $goods_sn;
    }

    /**
     * 取得某订单应该赠送的积分数
     * @param   array $order 订单
     * @return  int     积分数
     */
    public static function integral_to_give($order)
    {
        $leftJoin = '';

        /* 判断是否团购 */
        if ($order['extension_code'] == 'group_buy') {
            $group_buy = self::group_buy_info(intval($order['extension_id']));
            $sql = "SELECT ext_info FROM" . $GLOBALS['dsc']->table('goods_activity') . "WHERE act_id = '" . $order['extension_id'] . "'";
            $ext_info = $GLOBALS['dn']->getOne($sql);
            $ext_info = unserialize($ext_info);
            return array('custom_points' => $ext_info['gift_integral'], 'rank_points' => $order['goods_amount']);
        } else {
            $leftJoin .= " left join " . $GLOBALS['dsc']->table('warehouse_goods') . " as wg on g.goods_id = wg.goods_id and wg.region_id = og.warehouse_id ";
            $leftJoin .= " left join " . $GLOBALS['dsc']->table('warehouse_area_goods') . " as wag on g.goods_id = wag.goods_id and wag.region_id = og.area_id ";

            $give_integral = "IF(og.ru_id > 0, (SELECT sg.give_integral / 100 FROM " . $GLOBALS['dsc']->table('merchants_grade') . " AS mg, " .
                $GLOBALS['dsc']->table('seller_grade') . " AS sg " .
                " WHERE mg.grade_id = sg.id AND mg.ru_id = og.ru_id LIMIT 1), 1)";

            $rank_integral = "IF(og.ru_id > 0, (SELECT sg.rank_integral / 100 FROM " . $GLOBALS['dsc']->table('merchants_grade') . " AS mg, " .
                $GLOBALS['dsc']->table('seller_grade') . " AS sg " .
                " WHERE mg.grade_id = sg.id AND mg.ru_id = og.ru_id LIMIT 1), 1)";

            $sql = "SELECT SUM(og.goods_number * IF(IF(g.model_price < 1, g.give_integral, IF(g.model_price < 2, wg.give_integral, wag.give_integral)) > -1, IF(g.model_price < 1, g.give_integral, IF(g.model_price < 2, wg.give_integral, wag.give_integral)), og.goods_price * $give_integral)) AS custom_points," .
                " SUM(og.goods_number * IF(IF(g.model_price < 1, g.rank_integral, IF(g.model_price < 2, wg.rank_integral, wag.rank_integral)) > -1, IF(g.model_price < 1, g.rank_integral, IF(g.model_price < 2, wg.rank_integral, wag.rank_integral)), og.goods_price * $rank_integral)) AS rank_points " .
                " FROM " . $GLOBALS['dsc']->table('order_goods') . " AS og " .
                "LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON og.goods_id = g.goods_id " .
                $leftJoin .
                "WHERE og.order_id = '" . $order['order_id'] . "' " .
                "AND og.goods_id > 0 " .
                "AND og.parent_id = 0 " .
                "AND og.is_gift = 0 AND og.extension_code != 'package_buy'";

            $row = $GLOBALS['db']->getRow($sql);
            if ($row) {
                $row['custom_points'] = intval($row['custom_points']);
                $row['rank_points'] = intval($row['rank_points']);
            }

            return $row;
        }
    }

    /**
     * 记录帐户变动
     * @param   int $user_id 用户id
     * @param   float $user_money 可用余额变动
     * @param   float $frozen_money 冻结余额变动
     * @param   int $rank_points 等级积分变动
     * @param   int $pay_points 消费积分变动
     * @param   string $change_desc 变动说明
     * @param   int $change_type 变动类型：参见常量文件
     * @return  void
     */
    public static function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER)
    {
        $is_go = true;
        $is_user_money = 0;
        $is_pay_points = 0;
        $deposit_fee = 0;
        if ($is_go && ($user_money || $frozen_money || $rank_points || $pay_points)) {

            /* 插入帐户变动记录 */
            $account_log = array(
                'user_id' => $user_id,
                'user_money' => $user_money,
                'frozen_money' => $frozen_money,
                'rank_points' => $rank_points,
                'pay_points' => $pay_points,
                'change_time' => gmtime(),
                'change_desc' => $change_desc,
                'change_type' => $change_type,
                'deposit_fee' => $deposit_fee
            );

            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('account_log'), $account_log, 'INSERT');

            /* 更新用户信息 */
            $sql = "UPDATE " . $GLOBALS['dsc']->table('users') .
                " SET user_money = user_money + ('$user_money'+ '$deposit_fee')," .
                " frozen_money = frozen_money + ('$frozen_money')," .
                " rank_points = rank_points + ('$rank_points')," .
                " pay_points = pay_points + ('$pay_points')" .
                " WHERE user_id = '$user_id' LIMIT 1";
            $GLOBALS['db']->query($sql);

            /* 更新会员当前等级 start */
            $sql = "SELECT rank_points FROM " . $GLOBALS['dsc']->table("users") . " WHERE user_id = '$user_id'";
            $user_rank_points = $GLOBALS['db']->getOne($sql, true);

            $sql = 'SELECT rank_id, discount FROM ' . $GLOBALS['dsc']->table('user_rank') . " WHERE special_rank = 0 AND min_points <= '" . $user_rank_points . "' ORDER BY min_points DESC LIMIT 1";
            $rank_row = $GLOBALS['db']->getRow($sql);

            if ($rank_row) {
                $rank_row['discount'] = $rank_row['discount'] / 100.00;
            } else {
                $rank_row['discount'] = 1;
                $rank_row['rank_id'] = 0;
            }
            /* 更新会员当前等级 end */

            $sql = "UPDATE " . $GLOBALS['dsc']->table('users') . "SET user_rank = '" . $rank_row['rank_id'] . "' WHERE user_id = '$user_id'";
            $GLOBALS['db']->query($sql);

            $sql = "UPDATE " . $GLOBALS['dsc']->table('sessions') . "SET user_rank = '" . $rank_row['rank_id'] . "', discount= '" . $rank_row['discount'] . "' WHERE userid = '$user_id' AND adminid = 0";
            $GLOBALS['db']->query($sql);
        }
    }

    /**
     * 发红包：发货时发红包
     * @param   int $order_id 订单号
     * @return  bool
     */
    public static function send_order_bonus($order_id)
    {
        /* 取得订单应该发放的红包 */
        $bonus_list = self::order_bonus($order_id);
        /* 如果有红包，统计并发送 */
        if ($bonus_list) {
            /* 用户信息 */
            $sql = "SELECT u.user_id, u.user_name, u.email " .
                "FROM " . $GLOBALS['dsc']->table('order_info') . " AS o, " .
                $GLOBALS['dsc']->table('users') . " AS u " .
                "WHERE o.order_id = '$order_id' " .
                "AND o.user_id = u.user_id ";
            $user = $GLOBALS['db']->getRow($sql);

            /* 统计 */
            $count = 0;
            $money = '';
            foreach ($bonus_list as $bonus) {
                //$count += $bonus['number'];
                //优化一个订单只能发一个红包
                if ($bonus['number']) {
                    $count = 1;
                    $bonus['number'] = 1;
                }
                $money .= self::price_format($bonus['type_money']) . ' [' . $bonus['number'] . '], ';

                /* 修改用户红包 */
                $sql = "INSERT INTO " . $GLOBALS['dsc']->table('user_bonus') . " (bonus_type_id, user_id) " .
                    "VALUES('$bonus[type_id]', '$user[user_id]')";
                $GLOBALS['db']->query($sql);
            }
        }

        return true;
    }

    /**
     * 取得订单总金额
     * @param   int $order_id 订单id
     * @param   bool $include_gift 是否包括赠品
     * @return  float   订单总金额
     */
    public static function order_amount($order_id, $include_gift = true)
    {
        $sql = "SELECT SUM(goods_price * goods_number) " .
            "FROM " . $GLOBALS['dsc']->table('order_goods') .
            " WHERE order_id = '$order_id'";
        if (!$include_gift) {
            $sql .= " AND is_gift = 0";
        }

        return floatval($GLOBALS['db']->getOne($sql));
    }

    /**
     * 取得订单应该发放的红包
     * @param   int $order_id 订单id
     * @return  array
     */
    public static function order_bonus($order_id)
    {
        /* 查询按商品发的红包 */
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $sql = "SELECT b.type_id, b.type_money, SUM(o.goods_number) AS number " .
            "FROM " . $GLOBALS['dsc']->table('order_goods') . " AS o, " .
            $GLOBALS['dsc']->table('goods') . " AS g, " .
            $GLOBALS['dsc']->table('bonus_type') . " AS b " .
            " WHERE o.order_id = '$order_id' " .
            " AND o.is_gift = 0 " .
            " AND o.goods_id = g.goods_id " .
            " AND g.bonus_type_id = b.type_id " .
            " AND b.send_type = '" . SEND_BY_GOODS . "' " .
            " AND b.send_start_date <= '$today' " .
            " AND b.send_end_date >= '$today' " .
            " GROUP BY b.type_id ";
        $list = $GLOBALS['db']->getAll($sql);

        /* 查询定单中非赠品总金额 */
        $amount = self::order_amount($order_id, false);

        /* 查询订单日期 */
        $sql = "SELECT oi.add_time, og.ru_id " .
            " FROM " . $GLOBALS['dsc']->table('order_info') . "AS oi," .
            $GLOBALS['dsc']->table('order_goods') . "AS og" .
            " WHERE oi.order_id = og.order_id AND oi.order_id = '$order_id' LIMIT 1";
        $order = $GLOBALS['db']->getRow($sql);

        $order_time = $order['add_time'];
        $ru_id = $order['ru_id'];

        /* 查询按订单发的红包 */
        $sql = "SELECT type_id, type_name, type_money, IFNULL(FLOOR('$amount' / min_amount), 1) AS number " .
            "FROM " . $GLOBALS['dsc']->table('bonus_type') .
            "WHERE send_type = '" . SEND_BY_ORDER . "' " .
            "AND send_start_date <= '$order_time' " .
            "AND send_end_date >= '$order_time' AND user_id = '$ru_id' ";
        $list = array_merge($list, $GLOBALS['db']->getAll($sql));

        return $list;
    }

    /**
     * 格式化商品价格
     *
     * @access  public
     * @param   float $price 商品价格
     * @return  string
     */
    public static function price_format($price = 0, $change_price = true)
    {
        if (empty($price)) {
            $price = 0;
        }

        if ($change_price && defined('ECS_ADMIN') === false) {
            switch ($GLOBALS['_CFG']['price_format']) {
                case 0:
                    $price = number_format($price, 2, '.', '');
                    break;
                case 1: // 保留不为 0 的尾数
                    $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                    if (substr($price, -1) == '.') {
                        $price = substr($price, 0, -1);
                    }
                    break;
                case 2: // 不四舍五入，保留1位
                    $price = substr(number_format($price, 2, '.', ''), 0, -1);
                    break;
                case 3: // 直接取整
                    $price = intval($price);
                    break;
                case 4: // 四舍五入，保留 1 位
                    $price = number_format($price, 1, '.', '');
                    break;
                case 5: // 先四舍五入，不保留小数
                    $price = round($price);
                    break;
            }
        } else {
            @$price = number_format($price, 2, '.', '');
        }

        return sprintf($GLOBALS['_CFG']['currency_format'], $price);
    }

    /**
     * [优惠券发放 (发货的时候)]达到条件的的订单,反购物券 bylu
     * @param $order_id ID
     */
    public static function send_order_coupons($order)
    {

        //获优惠券信息
        //获取格林尼治时间戳(用于判断优惠券是否已过期)
        $time = gmtime();

        $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('coupons') .
            "WHERE review_status = 3 AND cou_type = 2 AND $time<cou_end_time ";

        $coupons_buy_info = $GLOBALS['db']->getAll($sql);

        //生成优惠券编号
        foreach ($coupons_buy_info as $k => $v) {
            $coupons_buy_info[$k]['uc_sn'] = $time . rand(10, 99);
        }

        //获取会员等级
        $user_rank = self::get_one_user_rank($order['user_id']);

        foreach ($coupons_buy_info as $k => $v) {

            //判断当前会员等级能不能领取
            $cou_ok_user = !empty($v['cou_ok_user']) ? explode(",", $v['cou_ok_user']) : '';

            if ($cou_ok_user) {
                if (!in_array($user_rank, $cou_ok_user)) {
                    continue;
                }
            } else {
                continue;
            }

            //获取当前的注册券已被发放的数量(防止发放数量超过设定发放数量)
            $num = $GLOBALS['db']->getOne(" SELECT COUNT(uc_id) FROM " . $GLOBALS['dsc']->table('coupons_user') . " WHERE cou_id='" . $v['cou_id'] . "'");
            if ($v['cou_total'] <= $num) {
                continue;
            }

            //当前用户已经领取的数量,超过允许领取的数量则不再返券
            $cou_user_num = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('coupons_user') . " WHERE user_id='" . $order['user_id'] . "' AND cou_id ='" . $v['cou_id'] . "' AND is_use = 0");

            if ($cou_user_num < $v['cou_user_num']) {

                //获取订单商品详情
                $sql = " SELECT GROUP_CONCAT(og.goods_id) AS goods_id, GROUP_CONCAT(g.cat_id) AS cat_id FROM " . $GLOBALS['dsc']->table('order_goods') . " AS og," . $GLOBALS['dsc']->table('goods') . " AS g" . " WHERE og.goods_id = g.goods_id AND order_id='" . $order['order_id'] . "'";
                $goods = $GLOBALS['db']->getRow($sql);
                $goods_ids = !empty($goods['goods_id']) ? array_unique(explode(",", $goods['goods_id'])) : array();
                $goods_cats = !empty($goods['cat_id']) ? array_unique(explode(",", $goods['cat_id'])) : array();
                $flag = false;

                //返券的金额门槛满足
                if ($order['goods_amount'] >= $v['cou_get_man']) {
                    if ($v['cou_ok_goods']) {
                        $cou_ok_goods = explode(",", $v['cou_ok_goods']);

                        if ($goods_ids) {
                            foreach ($goods_ids as $m => $n) {
                                //商品门槛满足(如果当前订单有多件商品,只要有一件商品满足条件,那么当前订单即反当前券)
                                if (in_array($n, $cou_ok_goods)) {
                                    $flag = true;
                                    break;
                                }
                            }
                        }
                    } elseif ($v['cou_ok_cat']) {
                        $cou_ok_cat = self::get_cou_children($v['cou_ok_cat']);

                        $cou_ok_cat = explode(",", $cou_ok_cat);

                        if ($goods_cats) {
                            foreach ($goods_cats as $m => $n) {
                                //商品门槛满足(如果当前订单有多件商品 ,只要有一件商品的分类满足条件,那么当前订单即反当前券)
                                if (in_array($n, $cou_ok_cat)) {
                                    $flag = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        $flag = true;
                    }

                    //返券
                    if ($flag) {
                        $GLOBALS['db']->query("INSERT INTO " . $GLOBALS['dsc']->table('coupons_user') . " (`user_id`,`cou_id`,`uc_sn`) VALUES ('" . $order['user_id'] . "','" . $v['cou_id'] . "','" . $v['uc_sn'] . "') ");
                    }
                }
            }
        }
    }

    /* 优惠券分类 */

    public static function get_cou_children($cat = '')
    {
        $catlist = '';
        if ($cat) {
            $cat = explode(",", $cat);
            foreach ($cat as $key => $row) {
                $catlist .= self::get_children($row, 2) . ",";
            }

            $catlist = app(DscRepository::class)->delStrComma($catlist, 0, -1);
            $catlist = array_unique(explode(",", $catlist));
            $catlist = implode(",", $catlist);
            $cat = implode(",", $cat);
            $catlist = !empty($catlist) ? $catlist . "," . $cat : $cat;

            $catlist = app(DscRepository::class)->delStrComma($catlist);
        }

        return $catlist;
    }

    /**
     * 根据用户ID获取用户等级 bylu
     * @param $user_id 用户ID
     * @return bool
     */
    public static function get_one_user_rank($user_id)
    {
        if (!$user_id) {
            return false;
        }

        /* 查询会员信息 */
        $time = local_date('Y-m-d');
        $sql = 'SELECT u.user_money,u.email, u.pay_points, u.user_rank, u.rank_points, ' .
            ' IFNULL(b.type_money, 0) AS user_bonus, u.last_login, u.last_ip' .
            ' FROM ' . $GLOBALS['dsc']->table('users') . ' AS u ' .
            ' LEFT JOIN ' . $GLOBALS['dsc']->table('user_bonus') . ' AS ub' .
            ' ON ub.user_id = u.user_id AND ub.used_time = 0 ' .
            ' LEFT JOIN ' . $GLOBALS['dsc']->table('bonus_type') . ' AS b' .
            " ON b.type_id = ub.bonus_type_id AND b.use_start_date <= '$time' AND b.use_end_date >= '$time' " .
            " WHERE u.user_id = '$user_id'";
        if ($row = $GLOBALS['db']->getRow($sql)) {

            /* 判断是否是特殊等级，可能后台把特殊会员组更改普通会员组 */
            if ($row['user_rank'] > 0) {
                $sql = "SELECT special_rank from " . $GLOBALS['dsc']->table('user_rank') . "where rank_id='$row[user_rank]'";
                if ($GLOBALS['db']->getOne($sql) === '0' || $GLOBALS['db']->getOne($sql) === null) {
                    $sql = "update " . $GLOBALS['dsc']->table('users') . "set user_rank='0' where user_id='$user_id'";
                    $GLOBALS['db']->query($sql);
                    $row['user_rank'] = 0;
                }
            }

            /* 取得用户等级和折扣 */
            if ($row['user_rank'] == 0) {
                // 非特殊等级，根据等级积分计算用户等级（注意：不包括特殊等级）
                $sql = 'SELECT rank_id, discount FROM ' . $GLOBALS['dsc']->table('user_rank') . " WHERE special_rank = 0 AND min_points <= '" . intval($row['rank_points']) . "' ORDER BY min_points DESC LIMIT 1";
                if ($row = $GLOBALS['db']->getRow($sql)) {
                    return $row['rank_id'];
                } else {
                    return false;
                }
            } else {
                // 特殊等级
                $sql = 'SELECT rank_id, discount FROM ' . $GLOBALS['dsc']->table('user_rank') . " WHERE rank_id = '$row[user_rank]' LIMIT 1";
                if ($row = $GLOBALS['db']->getRow($sql)) {
                    return $row['rank_id'];
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * 判断返回的快递方式是否支持
     * @param $code 贡云快递code
     * @return bool
     */
    public static function get_shipping_info($code)
    {
        $shipping_info = [];
        $control_table = [
            [
                'cloud_code' => 'sf',
                'name' => '顺丰快递',
                'code' => 'sf_express'
            ],
            [
                'cloud_code' => 'sto',
                'name' => '申通',
                'code' => 'sto_express'
            ],
            [
                'cloud_code' => 'yt',
                'name' => '圆通',
                'code' => 'yto'
            ],
            [
                'cloud_code' => 'yd',
                'name' => '韵达',
                'code' => 'yunda'
            ],
            [
                'cloud_code' => 'tt',
                'name' => '天天',
                'code' => 'tiantian'
            ],
            [
                'cloud_code' => 'ems',
                'name' => 'EMS',
                'code' => 'ems'
            ],
            [
                'cloud_code' => 'zto',
                'name' => '中通',
                'code' => 'zto'
            ],
            [
                'cloud_code' => 'qf',
                'name' => '全峰',
                'code' => 'quanfeng'
            ],
            [
                'cloud_code' => 'db',
                'name' => '德邦',
                'code' => ''
            ],
            [
                'cloud_code' => 'yousu',
                'name' => '优速',
                'code' => ''
            ],
            [
                'cloud_code' => 'ht',
                'name' => '汇通',
                'code' => 'huitong'
            ],
            [
                'cloud_code' => 'gt',
                'name' => '国通',
                'code' => ''
            ],
            [
                'cloud_code' => 'zjs',
                'name' => '宅急送',
                'code' => 'zjs'
            ],
            [
                'cloud_code' => 'kuaijie',
                'name' => '快捷',
                'code' => ''
            ],
            [
                'cloud_code' => 'yzgn',
                'name' => '邮政国内',
                'code' => 'ems'
            ],
            [
                'cloud_code' => 'XloboEX',
                'name' => '贝海快递',
                'code' => ''
            ],
            [
                'cloud_code' => '8dt',
                'name' => '八达通',
                'code' => ''
            ],
            [
                'cloud_code' => 'ANE',
                'name' => '安能',
                'code' => ''
            ],
            [
                'cloud_code' => 'jdwl',
                'name' => '京东物流',
                'code' => ''
            ],
            [
                'cloud_code' => 'EWE',
                'name' => 'EWE国际快递',
                'code' => ''
            ],
            [
                'cloud_code' => 'WXWL',
                'name' => '万象物流',
                'code' => ''
            ],
            [
                'cloud_code' => 'CHINAPOST',
                'name' => '邮政包裹',
                'code' => ''
            ],
            [
                'cloud_code' => 'AuExpress',
                'name' => '澳邮中国',
                'code' => ''
            ],
            [
                'cloud_code' => 'sure',
                'name' => '速尔快递',
                'code' => ''
            ],
        ];
        if ($code) {
            foreach ($control_table as $k => $v) {
                if ($v['cloud_code'] == $code && $v['code']) {
                    $sql = "SELECT * FROM" . $GLOBALS['dsc']->table('shipping') . "WHERE shipping_code = '" . $v['code'] . "' LIMIT 1";
                    $shipping_info = $GLOBALS['db']->getRow($sql);
                    break;
                }
            }
        }
        return $shipping_info;
    }

    /**
     * 获得指定地区信息
     *
     * @access      public
     * @param       int     country    国家的编号
     * @return      array
     */
    public static function get_regions_info($region_id = 0)
    {
        $sql = 'SELECT region_id, region_name FROM ' . $GLOBALS['dsc']->table('region') .
            " WHERE region_id = '$region_id' LIMIT 1";

        return $GLOBALS['db']->getRow($sql);
    }

    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array(array(self, $method), $arguments);
    }
}
