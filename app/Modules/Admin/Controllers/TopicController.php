<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\Topic;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 专题管理
 */
class TopicController extends InitController
{
    protected $dscRepository;
    protected $merchantCommonService;
    protected $storeCommonService;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        StoreCommonService $storeCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('visual');
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end
        /* 配置风格颜色选项 */
        $topic_style_color = [
            '0' => '008080',
            '1' => '008000',
            '2' => 'ffa500',
            '3' => 'ff0000',
            '4' => 'ffff00',
            '5' => '9acd32',
            '6' => 'ffd700'
        ];

        /*------------------------------------------------------ */
        //-- 专题列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('topic_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_topic']);

            $this->smarty->assign('full_page', 1);
            $list = $this->get_topic_list();

            $this->smarty->assign('topic_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));


            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['topic_add'], 'href' => 'topic.php?act=add']);
            return $this->smarty->display('topic_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑专题
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('topic_manage');

            $isadd = $_REQUEST['act'] == 'add';
            $this->smarty->assign('isadd', $isadd);
            $topic_id = empty($_REQUEST['topic_id']) ? 0 : intval($_REQUEST['topic_id']);


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_topic']);
            $this->smarty->assign('action_link', $this->list_link($isadd));

            set_default_filter(); //设置默认筛选
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('topic_style_color', $topic_style_color);

            $width_height = $this->get_toppic_width_height();
            if (isset($width_height['pic']['width']) && isset($width_height['pic']['height'])) {
                $this->smarty->assign('width_height', sprintf($GLOBALS['_LANG']['tips_width_height'], $width_height['pic']['width'] . 'px', $width_height['pic']['height'] . 'px'));
            }
            if (isset($width_height['title_pic']['width']) && isset($width_height['title_pic']['height'])) {
                $this->smarty->assign('title_width_height', sprintf($GLOBALS['_LANG']['tips_title_width_height'], $width_height['title_pic']['width'] . 'px', $width_height['title_pic']['height'] . 'px'));
            }
            if (!$isadd) {
                $sql = "SELECT * FROM " . $this->dsc->table('topic') . " WHERE topic_id = '$topic_id'";
                $topic = $this->db->getRow($sql);
                $topic['start_time'] = local_date('Y-m-d H:i:s', $topic['start_time']);
                $topic['end_time'] = local_date('Y-m-d H:i:s', $topic['end_time']);
                $this->smarty->assign('topic', $topic);
                $this->smarty->assign('act', "update");
            } else {
                $topic = ['title' => '', 'topic_type' => 0, 'url' => 'http://'];

                $topic['start_time'] = local_date('Y-m-d H:i:s', time());
                $topic['end_time'] = local_date('Y-m-d H:i:s', time() + 4 * 86400);

                $this->smarty->assign('topic', $topic);
                create_html_editor('topic_intro');
                $this->smarty->assign('act', "insert");
            }

            return $this->smarty->display('topic_edit.dwt');
        }

        /*------------------------------------------------------ */
        //-- 专题可视化 by kong
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'visual') {
            $topic_id = !isset($_REQUEST['topic_id']) && empty($_REQUEST['topic_id']) ? 0 : intval($_REQUEST['topic_id']);

            /**
             * 专题可视化
             * 下载OSS模板文件
             */
            get_down_topictemplates($topic_id, $adminru['ru_id']);

            $temp_type = empty($_REQUEST['temp_type']) ? '' : trim($_REQUEST['temp_type']);
            if ($temp_type == 'seller') {
                $arr['tem'] = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';
                //如果存在缓存文件  ，调用缓存文件
                $des = storage_public(DATA_DIR . '/seller_templates/seller_tem' . "/" . $arr['tem']);
            } else {
                $arr['tem'] = "topic_" . $topic_id;
                //如果存在缓存文件  ，调用缓存文件
                $des = storage_public(DATA_DIR . '/topic' . '/topic_' . $adminru['ru_id'] . "/" . $arr['tem']);
            }
            $is_temp = 0;
            if (file_exists($des . "/temp/pc_page.php")) {
                $filename = $des . "/temp/pc_page.php";
                $is_temp = 1;
            } else {
                $filename = $des . '/pc_page.php';
            }
            $arr['out'] = get_html_file($filename);

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($arr['out']) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $arr['out']);
                $arr['out'] = $desc_preg['goods_desc'];
            }

            //判断是否是新模板
            $this->smarty->assign('theme_extension', 1);
            $domain = $this->dsc->url();

            /*获取左侧储存值*/
            $head = getleft_attr("head", $adminru['ru_id'], $arr['tem']);
            $content = getleft_attr("content", $adminru['ru_id'], $arr['tem']);
            $this->smarty->assign('head', $head);
            $this->smarty->assign('content', $content);
            $this->smarty->assign('pc_page', $arr);
            $this->smarty->assign('domain', $domain);
            $this->smarty->assign('is_temp', $is_temp);
            if ($temp_type == 'seller') {
                $this->smarty->assign('vis_section', "vis_seller_store");
                $this->smarty->assign('admin_path', "admin");
            } else {
                $this->smarty->assign('topic_id', $topic_id);
                $this->smarty->assign('vis_section', "vis_topic");
            }
            return $this->smarty->display("visual_topic.dwt");
        }

        /*------------------------------------------------------ */
        //-- 生成缓存 by kong
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'file_put_visual') {
            $result = ['suffix' => '', 'error' => ''];
            $topic_type = isset($_REQUEST['topic_type']) ? addslashes($_REQUEST['topic_type']) : '';
            /*后台缓存内容*/
            $content = isset($_REQUEST['content']) ? unescape($_REQUEST['content']) : '';
            $content = !empty($content) ? stripslashes($content) : '';
            /*前台缓存内容*/
            $content_html = isset($_REQUEST['content_html']) ? unescape($_REQUEST['content_html']) : '';
            $content_html = !empty($content_html) ? stripslashes($content_html) : '';

            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : '';
            $new = isset($_REQUEST['new']) ? intval($_REQUEST['new']) : 0;//CMS频道标识
            $pc_page_name = "pc_page.php";
            $pc_html_name = "pc_html.php";
            $pc_nav_html = "nav_html.php";
            $pc_head_name = "pc_head.php";

            $type = 0;
            $ru_id = 0;
            if ($new == 1) {
                $type = 5;
            } else {
                if ($topic_type == 'topic_type') {
                    $nav_html = isset($_REQUEST['nav_html']) ? unescape($_REQUEST['nav_html']) : '';
                    $nav_html = !empty($nav_html) ? stripslashes($nav_html) : '';
                    $type = 1;
                    create_html($nav_html, $adminru['ru_id'], $pc_nav_html, $suffix, 1);
                    $ru_id = $adminru['ru_id'];
                } else {
                    /*前台头部缓存内容*/
                    $head_html = isset($_REQUEST['head_html']) ? unescape($_REQUEST['head_html']) : '';
                    $head_html = !empty($head_html) ? stripslashes($head_html) : '';
                    create_html($head_html, 0, $pc_head_name, $suffix);
                }
            }

            create_html($content_html, $ru_id, $pc_html_name, $suffix, $type);
            create_html($content, $ru_id, $pc_page_name, $suffix, $type);
            $result['error'] = 0;
            $result['suffix'] = $suffix;

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 图片上传
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'header_bg') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            load_helper('goods', 'admin');
            $result = ['error' => 0, 'prompt' => '', 'content' => ''];
            $hometype = isset($_REQUEST['hometype']) ? intval($_REQUEST['hometype']) : '';
            $type = isset($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';
            $name = isset($_REQUEST['name']) ? addslashes($_REQUEST['name']) : '';
            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : '';
            $topic_type = isset($_REQUEST['topic_type']) ? addslashes($_REQUEST['topic_type']) : '';
            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|';
            if ($_FILES[$name]) {
                $file = $_FILES[$name];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        $result['error'] = 1;
                        $result['prompt'] = $GLOBALS['_LANG']['file_img_success'][$allow_file_types];
                    } else {
                        $file_ext = explode('.', $file['name']);
                        $ext = array_pop($file_ext);
                        $tem = '';
                        if ($type == 'headerbg') {
                            $tem = "/head";
                        } elseif ($type == 'contentbg') {
                            $tem = "/content";
                        }
                        if ($hometype == 1) {
                            $filename = DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . "/" . $suffix . "/images" . $tem;//文件名称
                            $file_dir = storage_public($filename);//文件目录
                        } else {
                            if ($topic_type == 'topic_type') {
                                $filename = DATA_DIR . '/topic/topic_' . $adminru['ru_id'] . "/" . $suffix . "/images" . $tem;
                                $file_dir = storage_public($filename);
                            } else {
                                $filename = DATA_DIR . '/seller_templates/seller_tem' . "/" . $suffix . "/images" . $tem;
                                $file_dir = storage_public($filename);
                            }
                        }

                        if (!is_dir($file_dir)) {
                            make_dir($file_dir);
                        }
                        $bgtype = '';
                        if ($type == 'headerbg') {
                            $bgtype = 'head';
                            $file_path = $file_dir . "/hdfile_" . gmtime() . '.' . $ext;//头部背景图
                            $file_name = $filename . "/hdfile_" . gmtime() . '.' . $ext;
                        } elseif ($type == 'contentbg') {
                            $bgtype = 'content';
                            $file_path = $file_dir . "/confile_" . gmtime() . '.' . $ext;//内容部分背景图
                            $file_name = $filename . "/confile_" . gmtime() . '.' . $ext;
                        } else {
                            $file_path = $file_dir . "/slide_" . gmtime() . '.' . $ext;//头部显示图片
                            $file_name = $filename . "/slide_" . gmtime() . '.' . $ext;
                        }
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_path)) {
                            //$content_file = $file_path;
                            //oss上传  需要的时候打开
                            $oss_img_url = str_replace("../", "", $file_path);

                            $this->dscRepository->getOssAddFile([$file_name]);
                            $content_file = isset($file_name) ? get_image_path($file_name) : '';
                            if ($bgtype) {
                                $theme = '';
                                $tem_RuId = $adminru['ru_id'];
                                if ($hometype == 1) {
                                    $theme = $GLOBALS['_CFG']['template'];
                                    $tem_RuId = 0;
                                }

                                $sql = "SELECT id ,img_file FROM" . $this->dsc->table('templates_left') . " WHERE ru_id = '" . $tem_RuId . "' AND seller_templates = '$suffix' AND type = '$bgtype' AND theme = '$theme'";
                                $templates_left = $this->db->getRow($sql);

                                if ($templates_left['id'] > 0) {
                                    if ($templates_left['img_file'] != '') {
                                        $old_oss_img_url = str_replace("../", "", $templates_left['img_file']);
                                        $this->dscRepository->getOssDelFile([$old_oss_img_url]);
                                        @unlink($templates_left['img_file']);
                                    }
                                    $sql = "UPDATE" . $this->dsc->table('templates_left') . " SET img_file = '$content_file' WHERE ru_id = '" . $tem_RuId . "' AND seller_templates = '$suffix' AND id='" . $templates_left['id'] . "' AND type = '$bgtype' AND theme = '$theme'";
                                    $this->db->query($sql);
                                } else {
                                    $sql = "INSERT INTO" . $this->dsc->table('templates_left') . " (`ru_id`,`seller_templates`,`img_file`,`type`,`theme`) VALUES ('" . $tem_RuId . "','$suffix','$content_file','$bgtype','$theme')";
                                    $this->db->query($sql);
                                }
                            }


                            $result['error'] = 2;
                            $result['content'] = $content_file;
                        } else {
                            $result['error'] = 1;
                            $result['prompt'] = $GLOBALS['_LANG']['system_error_alt'];
                        }
                    }
                }
            } else {
                $result['error'] = 1;
                $result['prompt'] = $GLOBALS['_LANG']['select_file_img'];
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 页面左侧属性
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'generate') {
            $result = ['error' => '', 'content' => ''];

            $hometype = !empty($_REQUEST['hometype']) ? intval($_REQUEST['hometype']) : 0;
            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : 'store_tpl_1';
            $bg_color = isset($_REQUEST['bg_color']) ? stripslashes($_REQUEST['bg_color']) : '';
            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
            $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'hrad';
            $bgshow = isset($_REQUEST['bgshow']) ? addslashes($_REQUEST['bgshow']) : '';
            $bgalign = isset($_REQUEST['bgalign']) ? addslashes($_REQUEST['bgalign']) : '';
            $theme = '';
            $tem_RuId = $adminru['ru_id'];
            if ($hometype == 1) {
                $theme = $GLOBALS['_CFG']['template'];
                $tem_RuId = 0;
            }
            $sql = "SELECT id  FROM" . $this->dsc->table('templates_left') . " WHERE ru_id = '" . $tem_RuId . "' AND seller_templates = '$suffix' AND type='$type' AND theme = '$theme'";
            $id = $this->db->getOne($sql);
            if ($id > 0) {
                $sql = "UPDATE " . $this->dsc->table('templates_left') . " SET seller_templates = '$suffix',bg_color = '$bg_color' ,if_show = '$is_show',bgrepeat='$bgshow',align= '$bgalign',type='$type' WHERE ru_id = '" . $tem_RuId . "' AND seller_templates = '$suffix' AND id='$id' AND type='$type' AND theme = '$theme'";
            } else {
                $sql = "INSERT INTO " . $this->dsc->table('templates_left') . " (`ru_id`,`seller_templates`,`bg_color`,`if_show`,`bgrepeat`,`align`,`type`) VALUES ('" . $tem_RuId . "','$suffix','$bg_color','$is_show','$bgshow','$bgalign','$type')";
            }
            if ($this->db->query($sql) == true) {
                $result['error'] = 1;
            } else {
                $result['error'] = 2;
                $result['content'] = $GLOBALS['_LANG']['system_error'];
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_img') {
            $hometype = !empty($_REQUEST['hometype']) ? intval($_REQUEST['hometype']) : 0;
            $fileimg = isset($_REQUEST['fileimg']) ? addslashes($_REQUEST['fileimg']) : '';
            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : '';
            $type = isset($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';
            $theme = '';
            $tem_RuId = $adminru['ru_id'];
            if ($hometype == 1) {
                $theme = $GLOBALS['_CFG']['template'];
                $tem_RuId = 0;
            }
            if ($fileimg != '') {
                @unlink($fileimg);
            }
            $sql = "UPDATE " . $this->dsc->table('templates_left') . " SET img_file = '' WHERE ru_id = '" . $tem_RuId . "' AND type = '$type' AND seller_templates = '$suffix' AND theme = '$theme'";
            $this->db->query($sql);
        }

        /*------------------------------------------------------ */
        //-- 发布
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'downloadModal') {
            $result = ['error' => '', 'message' => ''];
            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $dir = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/temp");//原目录
            $file = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code);//目标目录
            if (!empty($code)) {
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

            if ($is_oss && $server_model) {
                $dir = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/");
                $path = DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/";
                $file_list = get_recursive_file_oss($dir, $path, true);

                $this->dscRepository->getOssAddFile($file_list);

                dsc_unlink(storage_public(DATA_DIR . '/sc_file/topic/topic_' . $adminru['ru_id'] . "/" . $code . ".php"));

                $id_data = read_static_cache('urlip_list');

                if ($id_data !== false) {
                    $this->dscRepository->getDelVisualTemplates($id_data, $code, 'del_topictemplates', $adminru['ru_id']);
                }
            }
            /* 存入OSS end */

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 还原
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'backmodal') {
            $result = ['error' => '', 'message' => ''];
            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $section = isset($_REQUEST['section']) ? trim($_REQUEST['section']) : '';
            if ($section == 'vis_seller_store') {
                $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem/' . $code . "/temp");//原模板目录
            } else {
                $dir = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/temp");//原目录
            }
            if (!empty($code)) {
                getDelDirAndFile($dir);//删除缓存文件
                $result['error'] = 0;
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 获取可视化头部文件
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_hearder_body') {
            $result = ['error' => '', 'message' => ''];

            $this->smarty->assign("hearder_body", 1);
            $result['content'] = $GLOBALS['smarty']->fetch('library/pc_page.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 插入/更新专题数据
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            admin_priv('topic_manage');

            $is_insert = $_REQUEST['act'] == 'insert';
            $topic_id = empty($_POST['topic_id']) ? 0 : intval($_POST['topic_id']);


            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            $keywords = $_POST['keywords'];
            $description = $_POST['description'];

            /* 插入数据 */
            $record = [
                'title' => $_POST['topic_name'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'keywords' => $keywords,
                'description' => $description,
                'review_status' => 3
            ];

            if ($is_insert) {
                $record['user_id'] = $adminru['ru_id'];
                $this->db->AutoExecute($this->dsc->table('topic'), $record, 'INSERT');
            } else {
                if (isset($_POST['review_status'])) {
                    $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                    $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                    $record['review_status'] = $review_status;
                    $record['review_content'] = $review_content;
                }

                $this->db->AutoExecute($this->dsc->table('topic'), $record, 'UPDATE', "topic_id='$topic_id'");
            }

            clear_cache_files();

            $links[] = ['href' => 'topic.php', 'text' => $GLOBALS['_LANG']['back_list']];
            return sys_msg($GLOBALS['_LANG']['succed'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('topic_manage');

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    //删除图片
                    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                    $this->dscRepository->getDelBatch($_POST['checkboxes'], $id, ['topic_img', 'title_pic'], 'topic_id', Topic::whereRaw(1), 1);
                    $sql = "DELETE FROM " . $this->dsc->table('topic') . " WHERE ";
                    if (!empty($_POST['checkboxes'])) {
                        $sql .= db_create_in($_POST['checkboxes'], 'topic_id');
                        //删除对应模板  by kong
                        foreach ($_POST['checkboxes'] as $v) {
                            if ($v > 0) {
                                $suffix = "topic_" . $v;
                                $dir = storage_public(DATA_DIR . '/topic/topic_' . $adminru['ru_id'] . "/" . $suffix);
                                getDelDirAndFile($dir);
                            }
                        }
                    } elseif (!empty($id)) {
                        $sql .= "topic_id = '$id'";
                        //删除对应模板  by kong
                        $suffix = "topic_" . $id;
                        $dir = storage_public(DATA_DIR . '/topic/topic_' . $adminru['ru_id'] . "/" . $suffix);
                        getDelDirAndFile($dir);
                    }

                    $this->db->query($sql);

                    clear_cache_files();

                    if (!empty($_REQUEST['is_ajax'])) {
                        $url = 'topic.php?act=query&' . str_replace('act=delete', '', request()->server('QUERY_STRING'));
                        return dsc_header("Location: $url\n");
                    }

                    $links[] = ['href' => 'topic.php', 'text' => $GLOBALS['_LANG']['back_list']];
                    return sys_msg($GLOBALS['_LANG']['succed'], 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    $ids = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];

                    $sql = "UPDATE " . $this->dsc->table('topic') . " SET review_status = '$review_status' "
                        . " WHERE topic_id " . db_create_in($ids);

                    if ($this->db->query($sql)) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'topic.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['topic_adopt_status_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST["act"] == "delete") {
            admin_priv('topic_manage');
            $_POST['checkboxes'] = isset($_POST['checkboxes']) && !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : '';
            // 删除图片
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $this->dscRepository->getDelBatch($_POST['checkboxes'], $id, ['topic_img', 'title_pic'], 'topic_id', Topic::whereRaw(1), 1);

            $sql = "DELETE FROM " . $this->dsc->table('topic') . " WHERE ";
            if (!empty($_POST['checkboxes'])) {
                $sql .= db_create_in($_POST['checkboxes'], 'topic_id');
                // 删除对应模板  by kong
                foreach ($_POST['checkboxes'] as $v) {
                    if ($v > 0) {
                        $suffix = "topic_" . $v;
                        $dir = storage_public(DATA_DIR . '/topic/topic_' . $adminru['ru_id'] . "/" . $suffix);
                        getDelDirAndFile($dir);
                    }
                }
            } elseif (!empty($id)) {
                $sql .= "topic_id = '$id'";
                // 删除对应模板  by kong
                $suffix = "topic_" . $id;
                $dir = storage_public('data/topic/topic_' . $adminru['ru_id'] . "/" . $suffix);
                getDelDirAndFile($dir);
            } else {
            }

            $this->db->query($sql);

            clear_cache_files();

            if (!empty($_REQUEST['is_ajax'])) {
                $url = 'topic.php?act=query&' . str_replace('act=delete', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            $links[] = ['href' => 'topic.php', 'text' => $GLOBALS['_LANG']['back_list']];
            return sys_msg($GLOBALS['_LANG']['succed'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 分页查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST["act"] == "query") {
            $topic_list = $this->get_topic_list();
            $this->smarty->assign('topic_list', $topic_list['item']);
            $this->smarty->assign('filter', $topic_list['filter']);
            $this->smarty->assign('record_count', $topic_list['record_count']);
            $this->smarty->assign('page_count', $topic_list['page_count']);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);

            /* 排序标记 */
            $sort_flag = sort_flag($topic_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $tpl = 'topic_list.dwt';
            return make_json_result($this->smarty->fetch($tpl), '', ['filter' => $topic_list['filter'], 'page_count' => $topic_list['page_count']]);
        }
    }

    /**
     * 获取专题列表
     * @access  public
     * @return void
     */
    private function get_topic_list()
    {
        $adminru = get_admin_ru_id();

        $result = get_filter();
        if ($result === false) {
            /* 查询条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 't.topic_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
            $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

            //卖场 start
            $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
            if ($adminru['rs_id'] > 0) {
                $filter['rs_id'] = $adminru['rs_id'];
            }
            //卖场 end

            $where = "1";
            $where .= (!empty($filter['keywords'])) ? " AND t.title like '%" . mysql_like_quote($filter['keywords']) . "%'" : '';

            if ($adminru['ru_id'] > 0) {
                $where .= " AND t.user_id = '" . $adminru['ru_id'] . "' ";
            }

            if ($filter['review_status']) {
                $where .= " AND t.review_status = '" . $filter['review_status'] . "' ";
            }

            //-卖场
            $where .= get_rs_null_where('t.user_id', $filter['rs_id']);

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] > -1) {
                if ($adminru['ru_id'] == 0) {
                    if ($filter['store_search'] > 0) {
                        $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                        if ($store_type) {
                            $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                        }

                        if ($filter['store_search'] == 1) {
                            $where .= " AND t.user_id = '" . $filter['merchant_id'] . "' ";
                        } elseif ($filter['store_search'] == 2) {
                            $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                        } elseif ($filter['store_search'] == 3) {
                            $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                        }

                        if ($filter['store_search'] > 1) {
                            $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                                " WHERE msi.user_id = t.user_id $store_where) > 0 ";
                        }
                    } else {
                        $where .= " AND t.user_id = 0";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end
            $where .= !empty($filter['seller_list']) ? " AND t.user_id > 0 " : " AND t.user_id = 0 "; //区分商家和自营

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('topic') . " AS t " . " WHERE $where";
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT t.* FROM " . $this->dsc->table('topic') . " AS t " . " WHERE $where ORDER BY $filter[sort_by] $filter[sort_order]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $query = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        $res = [];

        foreach ($query as $topic) {
            $topic['start_time'] = local_date('Y-m-d H:i:s', $topic['start_time']);
            $topic['end_time'] = local_date('Y-m-d H:i:s', $topic['end_time']);
            $topic['url'] = $this->dsc->url() . 'topic.php?topic_id=' . $topic['topic_id'];
            $topic['ru_name'] = $this->merchantCommonService->getShopName($topic['user_id'], 1);
            $res[] = $topic;
        }

        $arr = ['item' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @param string $text 文字
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true, $text = '')
    {
        $href = 'topic.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }
        if ($text == '') {
            $text = $GLOBALS['_LANG']['topic_list'];
        }

        return ['href' => $href, 'text' => $text];
    }

    private function get_toppic_width_height()
    {
        $width_height = [];

        $file_path = app_path('Modules/Admin/Views/topic.dwt');
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return $width_height;
        }

        $string = file_get_contents($file_path);

        $pattern_width = '/var\s*topic_width\s*=\s*"(\d+)";/';
        $pattern_height = '/var\s*topic_height\s*=\s*"(\d+)";/';
        preg_match($pattern_width, $string, $width);
        preg_match($pattern_height, $string, $height);
        if (isset($width[1])) {
            $width_height['pic']['width'] = $width[1];
        }
        if (isset($height[1])) {
            $width_height['pic']['height'] = $height[1];
        }
        unset($width, $height);

        $pattern_width = '/TitlePicWidth:\s{1}(\d+)/';
        $pattern_height = '/TitlePicHeight:\s{1}(\d+)/';
        preg_match($pattern_width, $string, $width);
        preg_match($pattern_height, $string, $height);
        if (isset($width[1])) {
            $width_height['title_pic']['width'] = $width[1];
        }
        if (isset($height[1])) {
            $width_height['title_pic']['height'] = $height[1];
        }

        return $width_height;
    }
}
