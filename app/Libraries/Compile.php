<?php

namespace App\Libraries;

use Illuminate\Filesystem\Filesystem;

class Compile
{
    public static $savePath = '';

    /**
     * 初始化
     */
    public static function init()
    {
        self::$savePath = storage_path('app/diy');
        if (!is_dir(self::$savePath)) {
            $fs = new Filesystem();
            $fs->makeDirectory(self::$savePath);
        }
    }

    /**
     * 保存可视化编辑的配置数据
     * @param string $file
     * @param array $data
     */
    public static function setModule($file = 'index', $data = [])
    {
        self::init();
        if (!empty($data)) {
            $data = '<?php exit("no access");' . serialize($data);
            file_put_contents(self::$savePath . '/' . $file . '.php', $data);
        }
    }

    /**
     * 获取可视化配置的数据
     * @param string $file
     * @param bool $unserialize
     * @return bool|mixed
     */
    public static function getModule($file = 'index', $unserialize = true)
    {
        self::init();
        $filePath = self::$savePath . '/' . $file . '.php';
        if (is_file($filePath)) {
            $data = file_get_contents($filePath);
            $data = str_replace('<?php exit("no access");', '', $data);
            return $unserialize ? unserialize($data) : $data;
        }
        return false;
    }

    /**
     * 清空模块
     * @param string $file
     * @return bool
     */
    public static function cleanModule($file = 'index')
    {
        self::init();
        $filePath = self::$savePath . '/' . $file . '.php';
        if (is_file($filePath)) {
            return unlink($filePath);
        }
        return true;
    }

    /**
     * 处理默认数据图片路径
     */
    public static function replace_img($data)
    {
        $data = str_replace(['http://localhost/'], '/', $data);
        return str_replace(['/ecmoban0309/', '/dscmall/'], '', $data);
    }

    /**
     * 默认初始化数据
     * @return array
     */
    public static function initModule()
    {
        $data = [];
        $default_filePath = self::$savePath . '/default.php';
        if (file_exists($default_filePath)) {
            $data = unserialize(str_replace('<?php exit("no access");', '', file_get_contents($default_filePath)));
        }

        foreach ($data as $key => $value) {
            $data[$key]['moreLink'] = self::replace_img($value["moreLink"]);
            $data[$key]['icon'] = self::replace_img($value["icon"]);
            if (isset($value['data']["icon"])) {
                $data[$key]['data']['icon'] = self::replace_img($value['data']["icon"]);
            }
            if (isset($value['data']["moreLink"])) {
                $data[$key]['data']['moreLink'] = self::replace_img($value['data']["moreLink"]);
            }
            foreach ($value['data']['imgList'] as $ke => $val) {
                if (isset($val["img"])) {
                    $data[$key]['data']['imgList'][$ke]["img"] = self::replace_img($val["img"]);
                }
                if (isset($val["link"])) {
                    $data[$key]['data']['imgList'][$ke]["link"] = self::replace_img($val["link"]);
                }
            }
            foreach ($value['data']['contList'] as $ke => $val) {
                if (isset($val["url"])) {
                    $data[$key]['data']['contList'][$ke]["url"] = self::replace_img($val["url"]);
                }
            }
        }
        self::setModule('index', $data);
        return $data;
    }
}
