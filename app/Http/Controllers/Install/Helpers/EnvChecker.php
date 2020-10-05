<?php

namespace App\Http\Controllers\Install\Helpers;

class EnvChecker
{
    /**
     * 检查目录的读写权限
     *
     * @access  public
     * @param array $checking_dirs 目录列表
     * @return  array     检查后的消息数组，
     *    成功格式形如array('result' => 'OK', 'detail' => array(array($dir, $_LANG['can_write']), array(), ...))
     *    失败格式形如array('result' => 'ERROR', 'd etail' => array(array($dir, $_LANG['cannt_write']), array(), ...))
     */
    public function getCheckDirsPriv($checking_dirs)
    {
        global $_LANG;

        $msgs = array('result' => 'OK', 'detail' => array());

        if ($checking_dirs) {
            if (isset($checking_dirs['public'])) {
                foreach ($checking_dirs['public'] as $dir) {
                    if (!file_exists(storage_public($dir))) {
                        $msgs['result'] = 'ERROR';
                        $msgs['detail'][] = array($dir, $_LANG['not_exists']);
                        continue;
                    }

                    if (file_mode_info(storage_public($dir)) < 2) {
                        $msgs['result'] = 'ERROR';
                        $msgs['detail'][] = array($dir, $_LANG['cannt_write']);
                    } else {
                        $msgs['detail'][] = array($dir, $_LANG['can_write']);
                    }
                }
            }

            if (isset($checking_dirs['framework'])) {
                foreach ($checking_dirs['framework'] as $dir) {
                    if (!file_exists(storage_path('framework/' . $dir))) {
                        $msgs['result'] = 'ERROR';
                        $msgs['detail'][] = array($dir, $_LANG['not_exists']);
                        continue;
                    }

                    if (file_mode_info(storage_path('framework/' . $dir)) < 2) {
                        $msgs['result'] = 'ERROR';
                        $msgs['detail'][] = array($dir, $_LANG['cannt_write']);
                    } else {
                        $msgs['detail'][] = array($dir, $_LANG['can_write']);
                    }
                }
            }
        }

        return $msgs;
    }

    /**
     * 检查模板的读写权限
     *
     * @access  public
     * @param array $templates_root 模板文件类型所在的根路径数组，形如：array('dwt'=>'', 'lbi'=>'')
     * @return  array      检查后的消息数组，全部可写为空数组，否则是一个以不可写的文件路径组成的数组
     */
    public function getCheckTemplatesPriv($templates_root)
    {
        global $_LANG;

        $msgs = [];

        if ($templates_root) {
            foreach ($templates_root as $tpl_type => $tpl_root) {
                if (!file_exists($tpl_root)) {
                    $msgs[] = str_replace(resource_path(), '', $tpl_root . ' ' . $_LANG['not_exists']);
                    continue;
                }

                $tpl_handle = @opendir($tpl_root);

                while (($filename = @readdir($tpl_handle)) !== false) {
                    $filepath = $tpl_root . $filename;
                    if (is_file($filepath) && strrpos($filename, '.' . $tpl_type) !== false && file_mode_info($filepath) < 7) {
                        $msgs[] = str_replace(resource_path(), '', $filepath . ' ' . $_LANG['cannt_write']);
                    }
                }
                @closedir($tpl_handle);
            }
        }

        return $msgs;
    }

    /**
     *  检查特定目录是否有执行rename函数权限
     *
     * @access  public
     * @param void
     *
     * @return void
     */
    public function getCheckRenamePriv()
    {
        /* 获取要检查的目录 */
        $dir_list = [];
        $dir_list['temp'][0] = 'temp/caches';
        $dir_list['temp'][1] = 'temp/compiled';
        $dir_list['temp'][0] = 'temp/compiled/admin';

        /* 获取images目录下图片目录 */
        $folder = opendir(storage_public('images'));
        while ($dir = readdir($folder)) {
            if (is_dir(storage_public('images/') . $dir) && preg_match('/^[0-9]{6}$/', $dir)) {
                $dir_list['images'][] = 'images/' . $dir;
            }
        }
        closedir($folder);

        /* 检查目录是否有执行rename函数的权限 */
        $msgs = [];
        if (isset($dir_list['images'])) {
            foreach ($dir_list['images'] as $dir) {
                $mask = file_mode_info(storage_public($dir));
                if ((($mask & 2) > 0) && (($mask & 8) < 1)) {
                    /* 只有可写时才检查rename权限 */
                    $msgs[] = $dir . ' ' . $GLOBALS['_LANG']['cannt_modify'];
                }
            }
        }

        if (isset($dir_list['temp'])) {
            foreach ($dir_list['temp'] as $dir) {
                $mask = file_mode_info(storage_path('framework/' . $dir));
                if ((($mask & 2) > 0) && (($mask & 8) < 1)) {
                    /* 只有可写时才检查rename权限 */
                    $msgs[] = $dir . ' ' . $GLOBALS['_LANG']['cannt_modify'];
                }
            }
        }

        return $msgs;
    }
}
