<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Libraries\Phpzip;
use App\Models\HomeTemplates;
use App\Repositories\Common\DscRepository;

/**
 * 首页可视化
 */
class VisualhomeController extends InitController
{
    protected $dscRepository;
    protected $phpzip;

    public function __construct(
        DscRepository $dscRepository,
        Phpzip $phpzip
    )
    {
        $this->dscRepository = $dscRepository;
        $this->phpzip = $phpzip;
    }

    public function index()
    {
        load_helper('visual');

        admin_priv('visualhome');

        $adminru = get_admin_ru_id();

        //首页模板列表
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('update_home_temp', $GLOBALS['_CFG']['update_home_temp']);
            $template_list = get_home_templates();

            if (empty($template_list['list']) && $adminru['rs_id'] > 0) {
                $des = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template']);
                $new_suffix = get_new_dir_name(0, $des);

                $enableTem = HomeTemplates::where('rs_id', 0)
                    ->where('theme', $GLOBALS['_CFG']['template'])
                    ->where('is_enable', 1)
                    ->value('code');

                $enableTem = $enableTem ? $enableTem : '';

                if (!empty($new_suffix) && $enableTem) {

                    //新建目录
                    if (!is_dir($des . "/" . $new_suffix)) {
                        make_dir($des . "/" . $new_suffix);
                    }
                    recurse_copy($des . "/" . $enableTem, $des . "/" . $new_suffix, 1);

                    $other = [
                        'rs_id' => $adminru['rs_id'],
                        'code' => $new_suffix,
                        'is_enable' => 1,
                        'theme' => $GLOBALS['_CFG']['template']
                    ];
                    HomeTemplates::insert($other);

                    $template_list = get_home_templates();
                }
            }

            $this->smarty->assign('available_templates', $template_list['list']);
            $this->smarty->assign('filter', $template_list['filter']);
            $this->smarty->assign('record_count', $template_list['record_count']);
            $this->smarty->assign('page_count', $template_list['page_count']);
            $this->smarty->assign('default_tem', $template_list['default_tem']);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('visualhome_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $template_list = get_home_templates();
            $this->smarty->assign('available_templates', $template_list['list']);
            $this->smarty->assign('filter', $template_list['filter']);
            $this->smarty->assign('record_count', $template_list['record_count']);
            $this->smarty->assign('page_count', $template_list['page_count']);
            $this->smarty->assign('default_tem', $template_list['default_tem']);

            return make_json_result(
                $this->smarty->fetch('visualhome_list.dwt'),
                '',
                ['filter' => $template_list['filter'], 'page_count' => $template_list['page_count']]
            );
        } elseif ($_REQUEST['act'] == 'visual') {
            $des = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template']);
            $code = isset($_REQUEST['code']) && !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';
            //判断是否是卖场，是则判断模板是否是改卖场下的   start
            if ($adminru['rs_id'] > 0) {
                $sql = "SELECT rs_id FROM" . $this->dsc->table('home_templates') . "WHERE code = '$code'";
                $rs_id = $this->db->getOne($sql);
                if ($rs_id != $adminru['rs_id']) {
                    $links = [
                        ['href' => 'visualhome.php?act=list', 'text' => $GLOBALS['_LANG']['bank_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['not_edit_sell_template'], 0, $links);
                }
            }
            // end

            if (empty($code)) {
                $sql = "SELECT value FROM" . $this->dsc->table('shop_config') . " WHERE code= 'hometheme' AND store_range = '" . $GLOBALS['_CFG']['template'] . "'";
                $code = $this->db->getOne($sql, true);
            }

            /**
             * 首页可视化
             * 下载OSS模板文件
             */
            get_down_hometemplates($code);

            if (!file_exists($des . "/" . $code . "/nav_html.php") && !file_exists($des . "/" . $code . "/temp/nav_html.php")) {
                /* 获取导航数据 */
                $sql = "SELECT id, name, ifshow, vieworder, opennew, url, type" .
                    " FROM " . $this->dsc->table('nav') . "WHERE type = 'middle'";
                $navigator = $this->db->getAll($sql);
                $this->smarty->assign('navigator', $navigator);
            }
            $filename = '';
            $is_temp = 0;
            //如果存在缓存文件  ，调用缓存文件
            if (file_exists($des . "/" . $code . "/temp/pc_page.php")) {
                $filename = $des . "/" . $code . "/temp/pc_page.php";
                $is_temp = 1;
            } else {
                $filename = $des . "/" . $code . '/pc_page.php';
            }
            $arr['tem'] = $code;
            $arr['out'] = get_html_file($filename);

            $replace_data = [
                'http://localhost/ecmoban_dsc2.0.5_20170518/',
                'http://localhost/ecmoban_dsc2.2.6_20170727/',
                'http://localhost/ecmoban_dsc2.3/'
            ];
            $arr['out'] = str_replace($replace_data, $this->dsc->url(), $arr['out']);

            $content = getleft_attr("content", 0, $arr['tem'], $GLOBALS['_CFG']['template']);
            $bonusadv = getleft_attr("bonusadv", 0, $arr['tem'], $GLOBALS['_CFG']['template']);
            $this->smarty->assign('content', $content);
            $this->smarty->assign('bonusadv', $bonusadv);
            $this->smarty->assign('pc_page', $arr);
            $this->smarty->assign('is_temp', $is_temp);
            $this->smarty->assign("shop_name", $GLOBALS['_CFG']['shop_name']);
            $this->smarty->assign("home", "home");
            $this->smarty->assign('vis_section', "vis_home");
            return $this->smarty->display('visualhome.dwt');
        } //生成缓存
        elseif ($_REQUEST['act'] == 'file_put_visual') {
            $result = ['suffix' => '', 'error' => ''];

            $temp = isset($_REQUEST['temp']) ? intval(($_REQUEST['temp'])) : 0;
            /*后台缓存内容*/
            $content = isset($_REQUEST['content']) ? unescape($_REQUEST['content']) : '';
            $content = !empty($content) ? stripslashes($content) : '';
            /*前台缓存内容*/
            $content_html = isset($_REQUEST['content_html']) ? unescape($_REQUEST['content_html']) : '';
            $content_html = !empty($content_html) ? stripslashes($content_html) : '';

            $des = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template']);

            $suffix = !empty($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : get_new_dir_name(0, $des);
            $pc_page_name = "pc_page.php";
            if ($temp == 1) {
                $pc_html_name = "nav_html.php";
            } elseif ($temp == 2) {
                $pc_html_name = "topBanner.php";
            } else {
                $pc_html_name = "pc_html.php";
            }

            $create_html = create_html($content_html, $adminru['ru_id'], $pc_html_name, $suffix, 3);
            $create = create_html($content, $adminru['ru_id'], $pc_page_name, $suffix, 3);
            $result['error'] = 0;
            $result['suffix'] = $suffix;

            return response()->json($result);
        } //修改模板信息
        elseif ($_REQUEST['act'] == 'edit_information') {
            $result = ['suffix' => '', 'error' => ''];
            $allow_file_types = '|GIF|JPG|PNG|';

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            $check = !empty($_REQUEST['check']) ? intval($_REQUEST['check']) : 0;
            $tem = isset($_REQUEST['tem']) ? addslashes($_REQUEST['tem']) : '';
            $name = isset($_REQUEST['name']) ? "tpl name：" . addslashes($_REQUEST['name']) : 'tpl name：';
            $version = isset($_REQUEST['version']) ? "version：" . addslashes($_REQUEST['version']) : 'version：';
            $author = isset($_REQUEST['author']) ? "author：" . addslashes($_REQUEST['author']) : 'author：';
            $author_url = isset($_REQUEST['author_url']) ? "author_uri：" . $_REQUEST['author_url'] : 'author_uri：';
            $description = isset($_REQUEST['description']) ? "description：" . addslashes($_REQUEST['description']) : 'description：';

            //商家默认模板数据
            $template_type = !empty($_REQUEST['template_type']) ? trim($_REQUEST['template_type']) : '';
            $temp_id = !empty($_REQUEST['temp_id']) ? intval($_REQUEST['temp_id']) : 0;
            $temp_mode = !empty($_REQUEST['temp_mode']) ? intval($_REQUEST['temp_mode']) : 0;
            $temp_cost = !empty($_REQUEST['temp_cost']) ? trim($_REQUEST['temp_cost']) : 0;
            $temp_cost = floatval($temp_cost);
            if ($template_type == 'seller') {
                $des = storage_public(DATA_DIR . '/seller_templates/seller_tem');
            } else {
                $des = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template']);
            }

            if ($tem == '') {
                $tem = get_new_dir_name(0, $des);
                $code_dir = $des . '/' . $tem;
                if (!is_dir($code_dir)) {
                    make_dir($code_dir);
                }
            }
            $file_url = '';
            $file_dir = $des . '/' . $tem;
            if (!is_dir($file_dir)) {
                make_dir($file_dir);
            }
            $ext_cover = '';
            if ((isset($_FILES['ten_file']['error']) && $_FILES['ten_file']['error'] == 0) || (!isset($_FILES['ten_file']['error']) && isset($_FILES['ten_file']['tmp_name']) && $_FILES['ten_file']['tmp_name'] != 'none')) {
                //检查文件格式
                if (!check_file_type($_FILES['ten_file']['tmp_name'], $_FILES['ten_file']['name'], $allow_file_types)) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['image_type_error'];
                    return response()->json($result);
                }

                if ($_FILES['ten_file']['name']) {
                    $ext_cover = explode('.', $_FILES['ten_file']['name']);
                    $ext_cover = array_pop($ext_cover);
                } else {
                    $ext_cover = "";
                }

                $file_name = $file_dir . "/screenshot" . '.' . $ext_cover;//头部显示图片
                if (move_upload_file($_FILES['ten_file']['tmp_name'], $file_name)) {
                    $file_url = $file_name;
                }
            }
            if ($file_url == '') {
                $file_url = $_POST['textfile'] ?? '';
            }

            $ext_big = '';
            $big_file = '';
            if ((isset($_FILES['big_file']['error']) && $_FILES['big_file']['error'] == 0) || (!isset($_FILES['big_file']['error']) && isset($_FILES['big_file']['tmp_name']) && $_FILES['big_file']['tmp_name'] != 'none')) {
                //检查文件格式
                if (!check_file_type($_FILES['big_file']['tmp_name'], $_FILES['big_file']['name'], $allow_file_types)) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['image_type_error'];
                    return response()->json($result);
                }

                if ($_FILES['big_file']['name']) {
                    $ext_big = explode('.', $_FILES['big_file']['name']);
                    $ext_big = array_pop($ext_big);
                } else {
                    $ext_big = "";
                }

                $file_name = $file_dir . "/template" . '.' . $ext_big;//头部显示图片
                if (move_upload_file($_FILES['big_file']['tmp_name'], $file_name)) {
                    $big_file = $file_name;
                }
            }

            $images_list = [$file_url, $big_file];

            foreach ($images_list as $key => $val) {
                if ($val) {
                    $images_list[$key] = str_replace(storage_public(), '', $val);
                } else {
                    unset($images_list[$key]);
                }
            }

            $this->dscRepository->getOssAddFile($images_list);

            $end = "------tpl_info------------";
            $tab = "\n";

            $html = $end . $tab . $name . $tab . "tpl url：" . $file_url . $tab . $description . $tab . $version . $tab . $author . $tab . $author_url . $tab . $end;

            $html = write_static_file_cache('tpl_info', iconv("UTF-8", "GB2312", $html), 'txt', $file_dir . '/');

            if ($html === false) {
                $result['error'] = 1;
                $result['message'] = $file_dir . "/tpl_info.txt" . $GLOBALS['_LANG']['not_write_power_notic'];
            } else {
                //首页可视化列表页
                if ($check == 1 && $template_type != 'seller') {

                    //  卖场首页模板 start
                    if ($temp_id == 0) {
                        //模板入库
                        $sql = "INSERT INTO" . $this->dsc->table('home_templates') . "(`rs_id`,`code`,`theme`) VALUES ('" . $adminru['rs_id'] . "','$tem','" . $GLOBALS['_CFG']['template'] . "')";
                        $this->db->query($sql);
                    }
                    //  卖场首页模板 end
                    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
                } //商家模板
                elseif ($template_type == 'seller') {
                    if ($temp_id > 0) {
                        $sql = "UPDATE" . $this->dsc->table('template_mall') . "SET temp_mode = '$temp_mode',temp_cost='$temp_cost' WHERE temp_id = '$temp_id' AND temp_code = '$tem'";
                    } else {
                        $time = gmtime();
                        $sql = "INSERT INTO" . $this->dsc->table('template_mall') . "(`temp_mode`,`temp_cost`,`temp_code`,`add_time`) VALUES('$temp_mode','$temp_cost','$tem','$time')";
                    }
                    $this->db->query($sql);
                }
                $result['error'] = 0;
            }
            return response()->json($result);
        } /*删除模板*/
        elseif ($_REQUEST['act'] == 'removeTemplate') {
            $result = ['error' => '', 'content' => '', 'url' => ''];
            $code = isset($_REQUEST['code']) ? addslashes($_REQUEST['code']) : '';

            $template_type = !empty($_REQUEST['template_type']) ? trim($_REQUEST['template_type']) : '';
            $temp_id = !empty($_REQUEST['temp_id']) ? intval($_REQUEST['temp_id']) : 0;
            $theme = $GLOBALS['_CFG']['template'];

            /* 获取默认模板 */
            $sql = "SELECT value FROM" . $this->dsc->table('shop_config') . " WHERE code= 'hometheme'AND store_range = '" . $GLOBALS['_CFG']['template'] . "'";
            $default_tem = $this->db->getOne($sql);
            //使用中的模板不能删除
            if ($default_tem == $code && $template_type != 'seller') {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['template_use_not_remove'];
            } else {
                if ($template_type == 'seller') {
                    $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem' . '/' . $code);//模板目录
                    $theme = '';
                } else {
                    $dir = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/' . $code);//模板目录
                }

                $file = [];
                $format = ['png', 'gif', 'jpg', 'jpeg'];
                foreach ($format as $key => $val) {
                    $fileDir = str_replace(storage_public(), '', $dir);

                    $file['screenshot'][$key] = $fileDir . '/screenshot.' . $val;
                    $file['template'][$key] = $fileDir . '/template.' . $val;
                }

                $this->dscRepository->getOssDelFile($file['screenshot']);
                $this->dscRepository->getOssDelFile($file['template']);

                $rmdir = getDelDirAndFile($dir);
                if ($rmdir == true) {
                    //删除模板对应的左侧信息
                    $sql = "DELETE FROM" . $this->dsc->table('templates_left') . "WHERE seller_templates = '$code' AND theme = '$theme'";
                    $this->db->query($sql);
                    $result['error'] = 0;

                    if ($template_type == 'seller') {
                        $sql = "DELETE FROM" . $this->dsc->table('template_mall') . "WHERE temp_code = '$code' AND temp_id = '$temp_id'";
                        $this->db->query($sql);
                    } else {
                        //
                        $sql = "DELETE FROM" . $this->dsc->table('home_templates') . "WHERE code = '$code' AND temp_id = '$temp_id'";
                        $this->db->query($sql);
                    }
                } else {
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['_LANG']['system_error'];
                }
            }
            return response()->json($result);
        } //启用模板
        elseif ($_REQUEST['act'] == 'setupTemplate') {
            $result = ['error' => 0, 'content' => '', 'url' => ''];
            $code = isset($_REQUEST['code']) ? trim($_REQUEST['code']) : '';
            $temp_id = isset($_REQUEST['temp_id']) ? intval($_REQUEST['temp_id']) : 0;
            // start
            //获取卖场id
            $sql = "SELECT rs_id FROM" . $this->dsc->table('home_templates') . "WHERE temp_id = '$temp_id'";
            $rs_id = $this->db->getOne($sql);

            if ($rs_id != $adminru['rs_id']) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['not_set_sell_template_enable'];
            } else {
                //判断模板是否存在
                $sql = "UPDATE" . $this->dsc->table('home_templates') . "SET is_enable = 1 WHERE temp_id = '$temp_id' AND theme = '" . $GLOBALS['_CFG']['template'] . "' AND rs_id = '$rs_id'";
                $this->db->query($sql);

                //修正正在使用的模板的状态
                $sql = "UPDATE" . $this->dsc->table('home_templates') . "SET is_enable = 0 WHERE temp_id != '$temp_id' AND is_enable = 1 AND theme = '" . $GLOBALS['_CFG']['template'] . "'  AND rs_id = '$rs_id'";
                $this->db->query($sql);
            }
            // end
            return response()->json($result);
        } //导出模板
        elseif ($_REQUEST['act'] == 'export_tem') {
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : [];
            $template_type = !empty($_REQUEST['template_type']) ? trim($_REQUEST['template_type']) : '';
            if (!empty($checkboxes)) {
                $zip = new Phpzip;
                if ($template_type == 'seller') {
                    $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem' . '/');//模板目录
                } else {
                    $dir = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/');
                }
                $dir_zip = $dir;
                $file_mune = [];
                foreach ($checkboxes as $v) {
                    if ($v) {
                        $addfiletozip = $zip->get_filelist($dir_zip . $v);//获取所有目标文件
                        foreach ($addfiletozip as $k => $val) {
                            if ($v) {
                                $addfiletozip[$k] = $v . "/" . $val;
                            }
                        }
                        $file_mune = array_merge($file_mune, $addfiletozip);
                    }
                }
                /*写入压缩文件*/
                foreach ($file_mune as $v) {
                    if (file_exists($dir . "/" . $v)) {
                        $zip->add_file(file_get_contents($dir . "/" . $v), $v);
                    }
                }

                //下面是输出下载;
                $filename = "templates_list.zip";
                return response()->streamDownload(function () use ($zip) {
                    echo $zip->file();
                }, $filename);
            } else {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'visualhome.php?act=list';
                return sys_msg($GLOBALS['_LANG']['select_import_template'], 1, $link);
            }
        } //删除头部广告
        elseif ($_REQUEST['act'] == 'model_delete') {
            $result = ['error' => '', 'message' => ''];

            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $dir = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/' . $code);//模板目录

            if (empty($code) && file_exists($dir)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['template_not_existent'];
            } else {
                if (file_exists($dir . "/topBanner.php")) {
                    unlink($dir . "/topBanner.php");
                }
                $result['error'] = 0;
            }
            return response()->json($result);
        } //发布
        elseif ($_REQUEST['act'] == 'downloadModal') {
            $result = ['error' => '', 'message' => ''];
            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $adminpath = isset($_REQUEST['adminpath']) ? trim($_REQUEST['adminpath']) : '';
            $new = isset($_REQUEST['new']) ? intval($_REQUEST['new']) : 0;//CMS频道标识
            if ($new == 0) {
                if ($adminpath == 'admin') {
                    $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem' . "/" . $code . '/temp');//原模板目录
                    $file = storage_public(DATA_DIR . '/seller_templates/seller_tem' . "/" . $code);//目标模板目录
                } else {
                    $dir = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . "/" . $code . '/temp');//原模板目录
                    $file = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/' . $code);//目标模板目录
                }
            } else {
                $dir = storage_public(DATA_DIR . '/cms_templates/' . $GLOBALS['_CFG']['template'] . '/temp');//原模板目录
                $file = storage_public(DATA_DIR . '/cms_templates/' . $GLOBALS['_CFG']['template']);//目标模板目录
            }

            if (!empty($code) || $new == 1) {

                //新建目录
                if (!is_dir($dir)) {
                    make_dir($dir);
                }

                recurse_copy($dir, $file, 1);//移动缓存文件
                getDelDirAndFile($dir);//删除缓存文件
                $result['error'] = 0;
            }

            /* 存入OSS start */
            if (!isset($GLOBALS['_CFG']['open_oss'])) {
                $sql = "SELECT value FROM " . $this->dsc->table('shop_config') . " WHERE code = 'open_oss'";
                $is_oss = $this->db->getOne($sql, true);
            } else {
                $is_oss = $GLOBALS['_CFG']['open_oss'];
            }

            if (!isset($GLOBALS['_CFG']['server_model'])) {
                $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'server_model'";
                $server_model = $GLOBALS['db']->getOne($sql, true);
            } else {
                $server_model = $GLOBALS['_CFG']['server_model'];
            }

            if ($is_oss && $server_model && $new == 0) {
                if ($adminpath == 'admin') {
                    $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem' . "/" . $code . '/');
                    $path = DATA_DIR . '/seller_templates/seller_tem' . "/" . $code . '/';//目标模板目录
                    $unlink = storage_public(DATA_DIR . '/sc_file/sellertemplates/seller_tem' . '/' . $code . ".php");
                } else {
                    $dir = storage_public(DATA_DIR . "/home_templates/" . $GLOBALS['_CFG']['template'] . "/" . $code . "/");
                    $path = DATA_DIR . "/home_templates/" . $GLOBALS['_CFG']['template'] . "/" . $code . "/";
                    $unlink = storage_public(DATA_DIR . '/sc_file/hometemplates/' . $code . ".php");
                }

                $file_list = get_recursive_file_oss($dir, $path, true);

                $this->dscRepository->getOssAddFile($file_list);

                dsc_unlink($unlink);

                $id_data = read_static_cache('urlip_list');

                if ($id_data !== false) {
                    $this->dscRepository->getDelVisualTemplates($id_data, $code);
                }
            }
            /* 存入OSS end */

            return response()->json($result);
        } //还原
        elseif ($_REQUEST['act'] == 'backmodal') {
            $result = ['error' => '', 'message' => ''];
            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $new = isset($_REQUEST['new']) ? intval($_REQUEST['new']) : 0;//CMS频道标识
            if ($new == 1) {
                $dir = storage_public(DATA_DIR . '/cms_templates/' . $GLOBALS['_CFG']['template'] . '/temp');//原模板目录
            } else {
                $dir = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . "/" . $code . '/temp');//原模板目录
            }
            if (!empty($code) || $new == 1) {
                getDelDirAndFile($dir);//删除缓存文件
                $result['error'] = 0;
            }

            return response()->json($result);
        } //上传首页弹出广告
        elseif ($_REQUEST['act'] == 'bonusAdv') {

            $result = ['error' => '', 'message' => ''];

            $suffix = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $adv_url = !empty($_REQUEST['adv_url']) ? trim($_REQUEST['adv_url']) : '';
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|';
            //初始化数据
            $oss_img_url = '';
            $bgtype = 'bonusadv';
            $theme = $GLOBALS['_CFG']['template'];
            $file_name = '';

            if (isset($_FILES['advfile']) && !empty($_FILES['advfile'])) {
                if ((isset($_FILES['advfile']['error']) && $_FILES['advfile']['error'] == 0) || (!isset($_FILES['advfile']['error']) && $_FILES['advfile']['tmp_name'] != 'none')) {

                    if (!check_file_type($_FILES['advfile']['tmp_name'], $_FILES['advfile']['name'], $allow_file_types)) {
                        $result['error'] = 1;
                        $result['prompt'] = $GLOBALS['_LANG']['upload_success_img_type'][$allow_file_types];
                        return response()->json($result);
                    } else {
                        $ext_name = explode('.', $_FILES['advfile']['name']);
                        $ext = array_pop($ext_name);
                        $filename = DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . "/" . $suffix . "/images/bonusadv";

                        $file_dir = storage_public($filename);

                        if (!is_dir($file_dir)) {
                            make_dir($file_dir);
                        }

                        $file_path = $file_dir . "/bonusadv_" . gmtime() . "." . $ext;//文件目录
                        $file_name = $filename . "/bonusadv_" . gmtime() . "." . $ext;//文件名称
                        if (move_upload_file($_FILES['advfile']['tmp_name'], $file_path)) {
                            //oss上传  需要的时候打开
                            $oss_img_url = str_replace(storage_public(), '', $file_path);

                            $this->dscRepository->getOssAddFile([$oss_img_url]);
                        }
                    }
                }
            }
            $sql = "SELECT id ,img_file FROM" . $this->dsc->table('templates_left') . " WHERE ru_id = 0 AND seller_templates = '$suffix' AND type = '$bgtype' AND theme = '$theme' LIMIT 1";
            $templates_left = $this->db->getRow($sql);

            if ($templates_left['id'] > 0) {
                $fileurl = '';
                if ($oss_img_url != '') {
                    if ($templates_left['img_file'] != '') {
                        @unlink("../" . $templates_left['img_file']);
                        $this->dscRepository->getOssDelFile([$templates_left['img_file']]);
                    }
                    $fileurl = ",img_file = '$file_name'";
                }
                $sql = "UPDATE" . $this->dsc->table('templates_left') . " SET fileurl = '$adv_url' $fileurl WHERE ru_id = 0 AND seller_templates = '$suffix' AND id='" . $templates_left['id'] . "' AND type = '$bgtype' AND theme = '$theme'";
                $this->db->query($sql);
            } else {
                $sql = "INSERT INTO" . $this->dsc->table('templates_left') . " (`ru_id`,`seller_templates`,`img_file`,`type`,`theme`,`fileurl`) VALUES (0,'$suffix','$file_name','$bgtype','$theme','$adv_url')";
                $this->db->query($sql);
            }

            $result['file'] = isset($file_name) && $file_name ? get_image_path($file_name) : '';

            return response()->json($result);
        } //删除弹出广告
        elseif ($_REQUEST['act'] == 'delete_adv') {
            $result = ['error' => '', 'message' => ''];

            $suffix = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';

            $bgtype = 'bonusadv';
            $theme = $GLOBALS['_CFG']['template'];

            $sql = "SELECT id ,img_file FROM" . $this->dsc->table('templates_left') . " WHERE ru_id = 0 AND seller_templates = '$suffix' AND type = '$bgtype' AND theme = '$theme' LIMIT 1";
            $templates_left = $this->db->getRow($sql);
            if ($templates_left['img_file'] != '') {
                dsc_unlink(storage_public($templates_left['img_file']));
                $this->dscRepository->getOssDelFile([$templates_left['img_file']]);
            }
            $sql = "DELETE FROM" . $this->dsc->table("templates_left") . "WHERE ru_id = 0 AND seller_templates = '$suffix' AND type = '$bgtype' AND theme = '$theme'";
            $this->db->query($sql);
            return response()->json($result);
        } //同步首页可视化模板
        elseif ($_REQUEST['act'] == 'update_home_temp') {
            $result = ['error' => '', 'message' => ''];
            if ($GLOBALS['_CFG']['update_home_temp'] == 1) {
                $enableTem = $this->db->getOne("SELECT value FROM" . $this->dsc->table('shop_config') . " WHERE code= 'hometheme' AND store_range = '" . $GLOBALS['_CFG']['template'] . "'");
                /*默认模板*/
                $dir = storage_public(DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/');
                if (file_exists($dir)) {
                    $template_dir = @opendir($dir);
                    while ($file = readdir($template_dir)) {
                        if ($file != '.' && $file != '..' && $file != '.svn' && $file != 'index.htm') {
                            $is_enable = 0;
                            if ($file == $enableTem) {
                                $is_enable = 1;
                            }
                            $sql = "INSERT INTO" . $this->dsc->table('home_templates') . "(`code`,`theme`,`is_enable`) VALUES('$file','" . $GLOBALS['_CFG']['template'] . "','$is_enable')";
                            $this->db->query($sql);
                        }
                    }
                    @closedir($template_dir);
                }

                $sql = "UPDATE" . $this->dsc->table('shop_config') . "SET value = 0 WHERE code = 'update_home_temp'";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_all_files();
            }

            return response()->json($result);
        }
    }
}
