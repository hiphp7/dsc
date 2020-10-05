<?php

namespace App\Modules\Admin\Controllers;

/**
 * 可视化编辑控制器
 */
class VisualEditingController extends InitController
{
    public function index()
    {
        load_helper('visual');

        /* 检查权限 */
        admin_priv('10_visual_editing');

        $adminru = get_admin_ru_id();
        $this->smarty->assign('ru_id', $adminru['ru_id']);

        $allow_file_types = '|PNG|JPG|GIF|GPEG|';

        /*模板管理*/
        if ($_REQUEST['act'] == 'templates') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['temp_operation']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['back'], 'href' => 'merchants_users_list.php?act=list']);
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            //链接基本信息
            $this->smarty->assign('users', get_table_date('merchants_shop_information', "user_id='$id'", ['user_id', 'hopeLoginName', 'merchants_audit']));
            $this->smarty->assign('menu_select', ['action' => 'seller_shopinfo', 'current' => 'templates', 'action' => 'allot']);

            /*获取默认模板*/
            $sql = "SELECT seller_templates FROM" . $this->dsc->table('seller_shopinfo') . " WHERE ru_id=" . $id;
            $default_tem = $this->db->getOne($sql);

            /* 获得可用的模版 */
            $available_templates = [];
            $dir = storage_public('data/seller_templates/seller_tem_' . $id . '/');
            if (file_exists($dir)) {
                $template_dir = @opendir($dir);
                while ($file = readdir($template_dir)) {
                    if ($file != '.' && $file != '..' && $file != '.svn' && $file != 'index.htm') {
                        $available_templates[] = get_seller_template_info($file, $id);
                    }
                }
                $available_templates = get_array_sort($available_templates, 'sort');

                @closedir($template_dir);
            }
            if (!empty($available_templates)) {
                $this->smarty->assign('available_templates', $available_templates);
                $this->smarty->assign('ru_id', $id);
                $this->smarty->assign('default_tem', $default_tem);
                return $this->smarty->display("templates.dwt");
            } else {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'merchants_users_list.php?act=list';
                return sys_msg($GLOBALS['_LANG']['seller_notime_template'], 1, $link);
            }
        } /*模板信息*/
        elseif ($_REQUEST['act'] == 'template_information') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['template_info']);
            $id = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $tem = isset($_REQUEST['tem']) ? addslashes($_REQUEST['tem']) : '';
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['back'], 'href' => 'visual_editing.php?act=templates&&id=' . $id]);
            if ($tem) {
                $this->smarty->assign('template', get_seller_template_info($tem, $id));
            }
            $this->smarty->assign('tem', $tem);
            $this->smarty->assign('ru_id', $id);
            return $this->smarty->display("template_information.dwt");
        } /*编辑模板信息*/
        elseif ($_REQUEST['act'] == 'edit_information') {
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $tem = isset($_REQUEST['tem']) ? addslashes($_REQUEST['tem']) : '';
            $name = isset($_REQUEST['name']) ? "tpl name：" . addslashes($_REQUEST['name']) : 'tpl name：';
            $version = isset($_REQUEST['version']) ? "version：" . addslashes($_REQUEST['version']) : 'version：';
            $author = isset($_REQUEST['author']) ? "author：" . addslashes($_REQUEST['author']) : 'author：';
            $author_url = isset($_REQUEST['author_url']) ? "author url：" . $_REQUEST['author_url'] : 'author url：';
            $description = isset($_REQUEST['description']) ? "description：" . addslashes($_REQUEST['description']) : 'description：';
            $file_url = '';
            $file_dir = storage_public('data/seller_templates/seller_tem_' . $id . "/" . $tem);
            if (!is_dir($file_dir)) {
                mkdir($file_dir, 0777, true);
            }
            if ((isset($_FILES['ten_file']['error']) && $_FILES['ten_file']['error'] == 0) || (!isset($_FILES['ten_file']['error']) && isset($_FILES['ten_file']['tmp_name']) && $_FILES['ten_file']['tmp_name'] != 'none')) {
                //检查文件格式
                if (!check_file_type($_FILES['ten_file']['tmp_name'], $_FILES['ten_file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['image_type_error']);
                }

                if ($_FILES['ten_file']['name']) {
                    $ext = array_pop(explode('.', $_FILES['ten_file']['name']));
                } else {
                    $ext = '';
                }

                $file_name = $file_dir . "/screenshot" . '.' . $ext;//头部显示图片
                if (move_upload_file($_FILES['ten_file']['tmp_name'], $file_name)) {
                    $file_url = $file_name;
                }
            }
            if ($file_url == '') {
                $file_url = $_POST['textfile'];
            }
            if ((isset($_FILES['big_file']['error']) && $_FILES['big_file']['error'] == 0) || (!isset($_FILES['big_file']['error']) && isset($_FILES['big_file']['tmp_name']) && $_FILES['big_file']['tmp_name'] != 'none')) {
                //检查文件格式
                if (!check_file_type($_FILES['big_file']['tmp_name'], $_FILES['big_file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['image_type_error']);
                }

                $ext = array_pop(explode('.', $_FILES['big_file']['name']));

                $file_name = $file_dir . "/template" . '.' . $ext;//头部显示图片
                if (move_upload_file($_FILES['big_file']['tmp_name'], $file_name)) {
                    $big_file = $file_name;
                }
            }
            $end = "------tpl_info------------";
            $tab = "\n";

            $html = $end . $tab . $name . $tab . "tpl url：" . $file_url . $tab . $description . $tab . $version . $tab . $author . $tab . $author_url . $tab . $end;
            $html = write_static_file_cache('tpl_info', iconv("UTF-8", "GB2312", $html), 'txt', $file_dir . '/');
            if ($html === false) {
                return sys_msg("' . $file_dir . '/tpl_info.txt" . $GLOBALS['_LANG']['not_write_power_notic']);
            } else {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'visual_editing.php?act=templates&id=' . $id;
                return sys_msg($GLOBALS['_LANG']['modify_success'], 0, $link);
            }
        } /*删除模板*/
        elseif ($_REQUEST['act'] == 'removeTemplate') {
            $result = ['error' => '', 'content' => '', 'url' => ''];
            $code = isset($_REQUEST['code']) ? addslashes($_REQUEST['code']) : '';
            $ru_id = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;
            $dir = storage_public('data/seller_templates/seller_tem_' . $ru_id . "/" . $code);//模板目录
            $rmdir = getDelDirAndFile($dir);
            if ($rmdir == true) {
                $result['error'] = 0;
                $result['url'] = "visual_editing.php?act=templates&id=" . $ru_id;
            } else {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['system_error'];
            }
            return response()->json($result);
        }
    }
}
