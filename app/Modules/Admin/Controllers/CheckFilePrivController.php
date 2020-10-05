<?php

namespace App\Modules\Admin\Controllers;

/**
 * DSCMALL 系统文件检测
 */
class CheckFilePrivController extends InitController
{
    public function index()
    {
        if ($_REQUEST['act'] == 'check') {
            /* 检查权限 */
            admin_priv('file_priv');

            /* 要检查目录文件列表 */
            $goods_img_dir = [];
            $folder = @opendir(storage_public('images'));
            while ($dir = @readdir($folder)) {
                if (is_dir(storage_public(IMAGE_DIR . '/' . $dir)) && preg_match('/^[0-9]{6}$/', $dir)) {
                    $goods_img_dir[] = storage_public(IMAGE_DIR . '/' . $dir);
                }
            }
            @closedir($folder);

            $dir_subdir['images'][] = storage_public(IMAGE_DIR);
            $dir_subdir['images'][] = storage_public(IMAGE_DIR . '/upload');
            $dir_subdir['images'][] = storage_public(IMAGE_DIR . '/upload/Image');
            $dir_subdir['images'][] = storage_public(IMAGE_DIR . '/upload/File');
            $dir_subdir['images'][] = storage_public(IMAGE_DIR . '/upload/Flash');
            $dir_subdir['images'][] = storage_public(IMAGE_DIR . '/upload/Media');

            /* 将商品图片目录加入检查范围 */
            foreach ($goods_img_dir as $val) {
                $dir_subdir['images'][] = $val;
            }

            $tpl = resource_path('views/themes/' . $GLOBALS['_CFG']['template'] . '/');

            $list = [];

            /* 检查目录 */
            $dir = [
                'storage/app'
            ];
            foreach ($dir as $val) {
                $mark = file_mode_info(base_path($val));
                $list[] = ['item' => $val . $GLOBALS['_LANG']['dir'], 'r' => $mark & 1, 'w' => $mark & 2, 'm' => $mark & 4];
            }

            /* 检查目录及子目录 */
            $keys = array_unique(array_keys($dir_subdir));
            foreach ($keys as $key) {
                $err_msg = [];
                $mark = $this->check_file_in_array($dir_subdir[$key], $err_msg);
                $list[] = ['item' => $key . $GLOBALS['_LANG']['dir_subdir'], 'r' => $mark & 1, 'w' => $mark & 2, 'm' => $mark & 4, 'err_msg' => $err_msg];
            }

            /* 检查当前模板可写性 */
            $dwt = @opendir($tpl);
            $tpl_file = []; //获取要检查的文件
            while ($file = @readdir($dwt)) {
                if (is_file($tpl . $file) && strrpos($file, '.dwt') > 0) {
                    $tpl_file[] = $tpl . $file;
                }
            }
            @closedir($dwt);
            $lib = @opendir($tpl . 'library/');
            while ($file = @readdir($lib)) {
                if (is_file($tpl . 'library/' . $file) && strrpos($file, '.lbi') > 0) {
                    $tpl_file[] = $tpl . 'library/' . $file;
                }
            }
            @closedir($lib);

            /* 开始检查 */
            $err_msg = [];
            $mark = $this->check_file_in_array($tpl_file, $err_msg);
            $list[] = ['item' => $tpl . $GLOBALS['_LANG']['tpl_file'], 'r' => $mark & 1, 'w' => $mark & 2, 'm' => $mark & 4, 'err_msg' => $err_msg];

            /* 检查smarty的缓存目录和编译目录及image目录是否有执行rename()函数的权限 */
            $tpl_list = [];
            $tpl_dirs = [];
            /*$tpl_dirs[] = 'temp/caches';
            $tpl_dirs[] = 'temp/compiled';
            $tpl_dirs[] = 'temp/compiled/admin';*/

            /* 将商品图片目录加入检查范围 */
            foreach ($goods_img_dir as $val) {
                $tpl_dirs[] = $val;
            }
            foreach ($tpl_dirs as $dir) {
                $mask = file_mode_info($dir);

                if (($mask & 4) > 0) {
                    /* 之前已经检查过修改权限，只有有修改权限才检查rename权限 */
                    if (($mask & 8) < 1) {
                        $tpl_list[] = $dir;
                    }
                }
            }
            $tpl_msg = implode(', ', $tpl_list);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['check_file_priv']);
            $this->smarty->assign('list', $list);
            $this->smarty->assign('tpl_msg', $tpl_msg);
            return $this->smarty->display('file_priv.dwt');
        }
    }

    /**
     *  检查数组中目录权限
     *
     * @access  public
     * @param array $arr 要检查的文件列表数组
     * @param array $err_msg 错误信息回馈数组
     *
     * @return int       $mark          文件权限掩码
     */
    private function check_file_in_array($arr, &$err_msg)
    {
        $read = true;
        $writen = true;
        $modify = true;
        foreach ($arr as $val) {
            $mark = file_mode_info($val);
            if (($mark & 1) < 1) {
                $read = false;
                $err_msg['r'][] = $val;
            }
            if (($mark & 2) < 1) {
                $writen = false;
                $err_msg['w'][] = $val;
            }
            if (($mark & 4) < 1) {
                $modify = false;
                $err_msg['m'][] = $val;
            }
        }

        $mark = 0;
        if ($read) {
            $mark ^= 1;
        }
        if ($writen) {
            $mark ^= 2;
        }
        if ($modify) {
            $mark ^= 4;
        }

        return $mark;
    }
}
