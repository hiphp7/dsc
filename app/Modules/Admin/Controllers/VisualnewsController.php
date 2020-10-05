<?php

namespace App\Modules\Admin\Controllers;

/**
 * 管理中心news页面可视化
 */
class VisualnewsController extends InitController
{
    public function index()
    {
        load_helper('visual');
        /* 权限判断 */
        admin_priv('article_manage');
        if ($_REQUEST['act'] == 'visual') {
            $des = storage_public('data/cms_templates/' . $GLOBALS['_CFG']['template']);
            //如果存在缓存文件  ，调用缓存文件
            $code = isset($code) ? $code : '';
            $is_temp = 0;
            if (file_exists($des . "/" . $code . "/temp/pc_page.php")) {
                $filename = $des . "/temp/pc_page.php";
                $is_temp = 1;
            } else {
                $filename = $des . '/pc_page.php';
            }

            $news = get_html_file($filename);
            $this->smarty->assign('pc_page', $news);
            $this->smarty->assign('is_temp', $is_temp);
            return $this->smarty->display('news.dwt');
        } elseif ($_REQUEST['act'] == 'restore') {
            $result = ['error' => '', 'content' => ''];

            $des = storage_public(DATA_DIR . '/cms_templates/' . $GLOBALS['_CFG']['template']);
            getDelDirAndFile($des);
            return response()->json($result);
        }
    }
}
