<?php

namespace App\Modules\Seller\Controllers;

/**
 * 管理中心入驻商家店铺模板管理程序
 */
class MerchantsTemplateController extends InitController
{
    public function index()
    {
        load_helper('template', 'seller');

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "index");
        //获得商家店铺模板信息
        $adminru = get_admin_ru_id();
        $sql = "select id,seller_theme,store_style from " . $this->dsc->table('seller_shopinfo') . " where ru_id = '" . $adminru['ru_id'] . "'";
        $shop_info = $this->db->getRow($sql);

        $sql = "select count(*) from " . $this->dsc->table('seller_shopinfo') . " where ru_id = '" . $adminru['ru_id'] . "'";
        $shop_id = $this->db->getOne($sql);
        if ($shop_id < 1) {
            $lnk[] = ['text' => $GLOBALS['_LANG']['set_shop_info'], 'href' => 'index.php?act=merchants_first'];
            return sys_msg($GLOBALS['_LANG']['please_set_shop_basic_info'], 0, $lnk);
        }
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['19_merchants_store']);
        /*------------------------------------------------------ */
        //-- 店铺橱窗列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_store_other');//by kong
            /* 获得当前的模版的信息 */
            $curr_template = $shop_info['seller_theme'];
            $curr_style = $shop_info['store_style'];

            /*获取商家等级封顶商品数 by kong grade*/
            if ($adminru['ru_id'] > 0) {
                $sql = "SELECT sg.seller_temp FROM" . $this->dsc->table('seller_grade') . " AS sg LEFT JOIN " . $this->dsc->table('merchants_grade') . " AS mg ON sg.id = mg.grade_id WHERE mg.ru_id = '$adminru[ru_id]'";
                $seller_temp = $this->db->getOne($sql);
                $this->smarty->assign('seller_temp', $seller_temp);
            }

            /* 获得可用的模版 */
            $available_templates = [];
            $template_dir = @opendir(storage_public('seller_themes/'));
            while ($file = @readdir($template_dir)) {
                if ($file != '.' && $file != '..' && is_dir(storage_public('seller_themes/' . $file)) && $file != '.svn' && $file != 'index.htm') {
                    $available_templates[] = $this->get_seller_template_info($file);
                }
            }

            $available_templates = get_array_sort($available_templates, 'sort');

            @closedir($template_dir);

            /* 获得可用的模版的可选风格数组 */
            $templates_style = [];
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $templates_style[$value['code']] = $this->read_tpl_style($value['code'], 2);
                }
            }

            $this->db->query($sql);


            $this->smarty->assign('current', 'merchants_template');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['template_manage']);
            $this->smarty->assign('curr_tpl_style', $curr_style);
            $this->smarty->assign('template_style', $templates_style);
            $this->smarty->assign('curr_template', $this->get_seller_template_info($curr_template, $curr_style));
            $this->smarty->assign('available_templates', $available_templates);
            return $this->smarty->display('merchants_template_list.dwt');
        }
        /*------------------------------------------------------ */
        //-- 安装        模版
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'install') {
            $tpl_name = trim($_GET['tpl_name']);
            $tpl_fg = 0;
            $tpl_fg = trim($_GET['tpl_fg']);

            $custom_dirname = $this->dsc->seller_url();

            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";

            $template_info = $this->get_seller_template_info($tpl_name);
            $sql = "UPDATE " . $this->dsc->table('seller_shopinfo') . " SET seller_theme = '$tpl_name', store_style = '$tpl_fg', win_goods_type = '" . $template_info['win_goods_type'] . "'" .
                " WHERE ru_id = '" . $adminru['ru_id'] . "'";
            $step_install = $this->db->query($sql, 'SILENT');

            $sql = " delete from " . $this->dsc->table('seller_shopheader') . " where seller_theme='$tpl_name' and ru_id = '" . $adminru['ru_id'] . "'";
            $this->db->query($sql);

            //安装模板装修代码
            //店铺头部装修
            //检测是否设置过此模板的头部
            $sql = "select count(*) as count, content from " . $this->dsc->table('seller_shopheader') . " where seller_theme='$tpl_name' and ru_id = '" . $adminru['ru_id'] . "'";
            $header_info = $this->db->getRow($sql);

            if ($header_info['count'] == 0) {
                $header_path = storage_public('seller_themes/' . $tpl_name . '/header.txt');

                if (file_exists($header_path)) {
                    $content = file_get_contents($header_path);

                    $header_content = !empty($content) ? preg_replace($preg, "", stripslashes($content)) : '';
                    $header_content = addslashes(iconv("GB2312", "UTF-8", $header_content));

                    if (strlen($header_content) >= 3) {//默认有3个字符
                        $patterns = [];
                        $patterns[0] = '/themes/';;
                        $replacements = [];
                        $replacements[0] = $custom_dirname . 'themes';

                        $header_content = preg_replace($patterns, $replacements, $header_content);
                        $sql = "insert into" . $this->dsc->table('seller_shopheader') . "(content,seller_theme,ru_id) values ('$header_content','$tpl_name'," . $adminru['ru_id'] . ")";

                        $this->db->query($sql);
                    }
                }
            } elseif ($header_info['content'] == '') {
                $header_path = storage_public('seller_themes/' . $tpl_name . '/header.txt');
                if (file_exists($header_path)) {
                    $content = file_get_contents($header_path);

                    $header_content = !empty($content) ? preg_replace($preg, "", stripslashes($content)) : '';
                    $header_content = addslashes(iconv("GB2312", "UTF-8", $header_content));

                    if (strlen($header_content) >= 3) {//默认有3个字符
                        $patterns = [];
                        $patterns[0] = '/themes/';;
                        $replacements = [];
                        $replacements[0] = $custom_dirname . 'themes';

                        $header_content = preg_replace($patterns, $replacements, $header_content);
                        $sql = "UPDATE " . $this->dsc->table('seller_shopheader') . " SET content = '$header_content' WHERE seller_theme = '$tpl_name' AND ru_id = '" . $adminru['ru_id'] . "'";
                        $this->db->query($sql);
                    }
                }
            }

            //模板幻灯片安装
            //检测是否设置过此模板的幻灯片
            $sql = "select count(id) from " . $this->dsc->table('seller_shopslide') . " where seller_theme='$tpl_name' and ru_id = '" . $adminru['ru_id'] . "'";
            $count = $this->db->getOne($sql);

            if ($count == 0) {
                $silde_path = storage_public('seller_themes/' . $tpl_name . '/slides.txt');

                if (file_exists($silde_path)) {
                    $str = $this->mc_read_txt($silde_path);
                    $str = $this->get_preg_replace($str);
                    $slide_arr = explode(',', $str);
                    if ($slide_arr) {
                        $sql = "insert into " . $this->dsc->table('seller_shopslide') . " (ru_id,img_url,img_link,img_desc,is_show,seller_theme,install_img) values ";
                        foreach ($slide_arr as $key => $val) {
                            $val = addslashes($val);
                            if ($key + 1 < count($slide_arr)) {
                                $sql .= "($adminru[ru_id],'$val','','',1,'$tpl_name', 1),";
                            } else {
                                $sql .= "($adminru[ru_id],'$val','','',1,'$tpl_name', 1)";
                            }
                        }
                        $this->db->query($sql);
                    }
                }
            }

            //橱窗自定义装饰代码
            //检测是否设置过此模板的自定义区域
            $sql = "select count(*) from " . $this->dsc->table('seller_shopwindow') . " where seller_theme='$tpl_name' and win_type=0 and ru_id = '" . $adminru['ru_id'] . "'";
            $count = $this->db->getOne($sql);

            if ($count == 0) {
                $custom_path = storage_public('seller_themes/' . $tpl_name . '/custom/');

                $dir = @opendir($custom_path);
                while ($file = @readdir($dir)) {
                    $file = iconv("GB2312", "UTF-8", $file);
                    if ($file != '.' && $file != '..' && !is_dir(storage_public('seller_themes/' . $file))) {
                        $content_path = storage_public('seller_themes/' . $tpl_name . '/custom/' . $file);

                        $ext = pathinfo($content_path);
                        $cus_name = substr($file, 0, strrpos($file, '.')); //文件名作为自定义区域的名称录入数据库
                        $win_order = str_replace('custom', '', $cus_name);

                        if ($ext['extension'] == 'txt') {
                            $content_path = iconv("UTF-8", "GB2312", $content_path);
                            $content = file_get_contents($content_path, true);

                            $custom_content = !empty($content) ? preg_replace($preg, "", stripslashes($content)) : '';
                            $custom_content = addslashes(iconv("GB2312", "UTF-8", $custom_content));

                            if (strlen($custom_content) >= 3) {//默认有3个字符
                                $patterns = [];
                                $patterns[0] = '/themes/';;
                                $replacements = [];
                                $replacements[0] = $custom_dirname . 'themes';

                                $custom_content = preg_replace($patterns, $replacements, $custom_content);

                                $sql = "insert into" . $this->dsc->table('seller_shopwindow') . "(win_type,win_name,win_order,ru_id,is_show,win_custom,seller_theme) values ('0','$cus_name','$win_order'," . $adminru['ru_id'] . ",1,'$custom_content','$tpl_name')";

                                $this->db->query($sql);
                            }
                        }
                    }
                }
                @closedir($custom_path);
            }

            if ($step_install) {
                clear_all_files(); //清除模板编译文件

                $error_msg = '';
                return make_json_result($this->read_style_and_tpl($tpl_name, $tpl_fg), $GLOBALS['_LANG']['tpl_install_success']);
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 设置使用店铺默认模板
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_default') {
            $adminru = get_admin_ru_id();
            $sql = "UPDATE " . $this->dsc->table('seller_shopinfo') . " SET seller_theme = '' WHERE ru_id = '" . $adminru['ru_id'] . "'";
            $this->db->query($sql);

            return make_json_result('', $GLOBALS['_LANG']['default_tpl_set_success']);
        }
    }

    /**
     * 读取模板风格列表
     *
     * @access  public
     * @param string $tpl_name 模版名称
     * @param int $flag 1，AJAX数据；2，Array
     * @return
     */
    private function read_tpl_style($tpl_name, $flag = 1)
    {
        if (empty($tpl_name) && $flag == 1) {
            return 0;
        }

        /* 获得可用的模版 */
        $temp = '';
        $start = 0;
        $available_templates = [];
        $dir = storage_public('seller_themes/' . $tpl_name . '/');
        $tpl_style_dir = @opendir($dir);
        while ($file = readdir($tpl_style_dir)) {
            if ($file != '.' && $file != '..' && is_file($dir . $file) && $file != '.svn' && $file != 'index.htm') {
                if (preg_match("/^(style|style_)(.*)*/i", $file)) { // 取模板风格缩略图
                    $start = strpos($file, '.');
                    $temp = substr($file, 0, $start);
                    $temp = explode('_', $temp);
                    if (count($temp) == 2) {
                        $available_templates[] = $temp[1];
                    }
                }
            }
        }
        @closedir($tpl_style_dir);

        if ($flag == 1) {
            $ec = '<table border="0" width="100%" cellpadding="0" cellspacing="0" class="colortable" onMouseOver="javascript:onSOver(0, this);" onMouseOut="onSOut(this);" onclick="javascript:setupTemplateFG(0);"  bgcolor="#FFFFFF"><tr><td>&nbsp;</td></tr></table>';
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $tpl_info = get_template_info($tpl_name, $value);

                    $ec .= '<table border="0" width="100%" cellpadding="0" cellspacing="0" class="colortable" onMouseOver="javascript:onSOver(\'' . $value . '\', this);" onMouseOut="onSOut(this);" onclick="javascript:setupTemplateFG(\'' . $value . '\');"  bgcolor="' . $tpl_info['type'] . '"><tr><td>&nbsp;</td></tr></table>';

                    unset($tpl_info);
                }
            } else {
                $ec = '0';
            }

            return $ec;
        } elseif ($flag == 2) {
            $templates_temp = [''];
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $templates_temp[] = $value;
                }
            }

            return $templates_temp;
        }
    }

    /**
     * 读取当前风格信息与当前模板风格列表
     *
     * @access  public
     * @param string $tpl_name 模版名称
     * @param string $tpl_style 模版风格名
     * @return
     */
    private function read_style_and_tpl($tpl_name, $tpl_style)
    {
        $style_info = [];
        $style_info = $this->get_seller_template_info($tpl_name, $tpl_style);

        $tpl_style_info = [];
        $tpl_style_info = $this->read_tpl_style($tpl_name, 2);
        $tpl_style_list = '';
        if (count($tpl_style_info) > 1) {
            foreach ($tpl_style_info as $value) {
                $tpl_style_list .= '<span style="cursor:pointer;" onMouseOver="javascript:onSOver(\'screenshot\', \'' . $value . '\', this);" onMouseOut="onSOut(\'screenshot\', this, \'' . $style_info['screenshot'] . '\');" onclick="javascript:setupTemplateFG(\'' . $tpl_name . '\', \'' . $value . '\', \'\');" id="templateType_' . $value . '"><img src="../themes/' . $tpl_name . '/images/type' . $value . '_';

                if ($value == $tpl_style) {
                    $tpl_style_list .= '1';
                } else {
                    $tpl_style_list .= '0';
                }
                $tpl_style_list .= '.gif" border="0"></span>&nbsp;';
            }
        }
        $style_info['tpl_style'] = $tpl_style_list;

        return $style_info;
    }

    /**
     * 获得商家店铺模版的信息 wang店铺模板选择
     *
     * @access  private
     * @param string $template_name 模版名
     * @param string $template_style 模版风格名
     * @return  array
     */
    private function get_seller_template_info($template_name, $template_style = '')
    {
        if (empty($template_style) || $template_style == '') {
            $template_style = '';
        }

        $info = [];
        $ext = ['png', 'gif', 'jpg', 'jpeg'];

        $info['code'] = $template_name;
        $info['screenshot'] = '';
        $info['stylename'] = $template_style;

        if ($template_style == '') {
            foreach ($ext as $val) {
                if (file_exists('../seller_themes/' . $template_name . "/screenshot.$val")) {
                    $info['screenshot'] = '../seller_themes/' . $template_name . "/screenshot.$val";

                    break;
                }
            }
        } else {
            foreach ($ext as $val) {
                if (file_exists('../seller_themes/' . $template_name . "/screenshot_$template_style.$val")) {
                    $info['screenshot'] = '../seller_themes/' . $template_name . "/screenshot_$template_style.$val";

                    break;
                }
            }
        }

        $info_path = '../seller_themes/' . $template_name . '/tpl_info.txt';
        if ($template_style != '') {
            $info_path = '../seller_themes/' . $template_name . "/tpl_info_$template_style.txt";
        }
        if (file_exists($info_path) && !empty($template_name)) {
            $custom_content = addslashes(iconv("GB2312", "UTF-8", $info_path));
            $arr = array_slice(file($info_path), 0, 9);

            //ecmoban模板堂 --zhuo start
            $arr[1] = addslashes(iconv("GB2312", "UTF-8", $arr[1]));
            $arr[2] = addslashes(iconv("GB2312", "UTF-8", $arr[2]));
            $arr[3] = addslashes(iconv("GB2312", "UTF-8", $arr[3]));
            $arr[4] = addslashes(iconv("GB2312", "UTF-8", $arr[4]));
            $arr[5] = addslashes(iconv("GB2312", "UTF-8", $arr[5]));
            $arr[6] = addslashes(iconv("GB2312", "UTF-8", $arr[6]));
            $arr[7] = addslashes(iconv("GB2312", "UTF-8", $arr[7]));
            $arr[8] = addslashes(iconv("GB2312", "UTF-8", $arr[8]));
            //ecmoban模板堂 --zhuo end

            $template_name = explode('：', $arr[1]);
            $template_uri = explode('：', $arr[2]);
            $template_desc = explode('：', $arr[3]);
            $template_version = explode('：', $arr[4]);
            $template_author = explode('：', $arr[5]);
            $author_uri = explode('：', $arr[6]);
            $tpl_dwt_code = explode('：', $arr[7]);
            $win_goods_type = explode('：', $arr[8]);

            $info['name'] = isset($template_name[1]) ? trim($template_name[1]) : '';
            $info['uri'] = isset($template_uri[1]) ? trim($template_uri[1]) : '';
            $info['desc'] = isset($template_desc[1]) ? trim($template_desc[1]) : '';
            $info['version'] = isset($template_version[1]) ? trim($template_version[1]) : '';
            $info['author'] = isset($template_author[1]) ? trim($template_author[1]) : '';
            $info['author_uri'] = isset($author_uri[1]) ? trim($author_uri[1]) : '';
            $info['dwt_code'] = isset($tpl_dwt_code[1]) ? trim($tpl_dwt_code[1]) : '';
            $info['win_goods_type'] = isset($win_goods_type[1]) ? trim($win_goods_type[1]) : '';
            $info['sort'] = substr($info['code'], -1, 1);
        } else {
            $info['name'] = '';
            $info['uri'] = '';
            $info['desc'] = '';
            $info['version'] = '';
            $info['author'] = '';
            $info['author_uri'] = '';
            $info['dwt_code'] = '';
            $info['sort'] = '';
        }

        return $info;
    }

    //回车替换
    private function get_preg_replace($str, $type = '|')
    {
        $str = preg_replace("/\r\n/", ",", $str); //替换空格回车换行符 为 英文逗号
        $str = $this->get_str_trim($str);
        $str = $this->get_str_trim($str, $type);

        return $str;
    }

    private function get_str_trim($str, $type = ',')
    {
        $str = explode($type, $str);
        $str2 = '';

        for ($i = 0; $i < count($str); $i++) {
            $str2 .= trim($str[$i]) . $type;
        }

        return substr($str2, 0, -1);
    }

    //读取文件内    容
    private function mc_read_txt($file)
    {
        $pathfile = $file;
        if (!file_exists($pathfile)) {
            return false;
        }
        $fs = fopen($pathfile, "r+");
        $content = fread($fs, filesize($pathfile));//读文件
        fclose($fs);

        if (!$content) {
            return false;
        }
        return $content;
    }
}
