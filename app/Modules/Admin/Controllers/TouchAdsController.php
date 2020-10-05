<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Ads\TouchAdsManageService;
use App\Services\Common\CommonManageService;
use App\Services\Store\StoreCommonService;

/**
 * 广告管理程序
 */
class TouchAdsController extends InitController
{
    protected $dscRepository;
    protected $commonRepository;
    protected $storeCommonService;
    protected $commonManageService;
    protected $touchAdsManageService;

    public function __construct(
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        StoreCommonService $storeCommonService,
        CommonManageService $commonManageService,
        TouchAdsManageService $touchAdsManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->storeCommonService = $storeCommonService;
        $this->commonManageService = $commonManageService;
        $this->touchAdsManageService = $touchAdsManageService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        $exc = new Exchange($this->dsc->table("touch_ad"), $this->db, 'ad_id', 'ad_name');

        /* act操作项的初始化 */
        $act = request()->input('act', 'list');

        $adminru = $this->commonManageService->getAdminIdSeller();
        $priv_ru = isset($adminru['ru_id']) && $adminru['ru_id'] == 0 ? 1 : 0;
        $this->smarty->assign('priv_ru', $priv_ru);

        $ad_type = request()->input('ad_type', ''); // 广告位所属模块 如 wxapp、team、drp、seckill
        $this->smarty->assign('ad_type', $ad_type);

        /*------------------------------------------------------ */
        //-- 广告列表页面
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $pid = !empty($_REQUEST['pid']) ? $_REQUEST['pid'] : 0;
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ad_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['ads_add'], 'href' => 'touch_ads.php?act=add&ad_type=' . $ad_type . '&pid=' . $pid]);
            $this->smarty->assign('full_page', 1);

            $ads_list = $this->touchAdsManageService->getTouchAdsList($adminru['ru_id'], $ad_type);

            $position_list = $this->touchAdsManageService->getTouchPositionList($adminru['ru_id'], $ad_type);
            $this->smarty->assign('position_list', $position_list);

            $this->smarty->assign('ads_list', $ads_list['ads']);
            $this->smarty->assign('filter', $ads_list['filter']);
            $this->smarty->assign('record_count', $ads_list['record_count']);
            $this->smarty->assign('page_count', $ads_list['page_count']);
            $this->smarty->assign('pid', $ads_list['filter']['pid']);
            $this->smarty->assign('position_list', $position_list);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);
            $this->smarty->assign('ads_type', 1);

            $sort_flag = sort_flag($ads_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('touch_ads_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            $ads_list = $this->touchAdsManageService->getTouchAdsList($adminru['ru_id'], $ad_type);

            $this->smarty->assign('ads_list', $ads_list['ads']);
            $this->smarty->assign('filter', $ads_list['filter']);
            $this->smarty->assign('record_count', $ads_list['record_count']);
            $this->smarty->assign('page_count', $ads_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);
            $this->smarty->assign('ads_type', 1);

            $sort_flag = sort_flag($ads_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('touch_ads_list.dwt'), '', ['filter' => $ads_list['filter'], 'page_count' => $ads_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加新广告页面
        /*------------------------------------------------------ */
        elseif ($act == 'add') {
            admin_priv('touch_ad');

            $ad_link = empty($_GET['ad_link']) ? '' : trim($_GET['ad_link']);
            $ad_name = empty($_GET['ad_name']) ? '' : trim($_GET['ad_name']);

            $start_time = local_date($GLOBALS['_CFG']['time_format']);
            $end_time = local_date($GLOBALS['_CFG']['time_format'], gmtime() + 3600 * 24 * 30);  // 默认结束时间为1个月以后

            $ads = [
                'ad_link' => $ad_link,
                'ad_name' => $ad_name,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'enabled' => 1
            ];
            $this->smarty->assign('ads', $ads);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ads_add']);
            $this->smarty->assign('action_link', ['href' => 'touch_ads.php?act=list&ad_type=' . $ad_type, 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('position_list', $this->touchAdsManageService->getTouchPositionListFormat($adminru['ru_id'], $ad_type));

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);


            return $this->smarty->display('touch_ads_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 新广告的处理
        /*------------------------------------------------------ */
        elseif ($act == 'insert') {
            admin_priv('touch_ad');

            /* 初始化变量 */
            $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type = !empty($_POST['type']) ? intval($_POST['type']) : 0;
            $ad_name = !empty($_POST['ad_name']) ? trim($_POST['ad_name']) : '';
            $link_color = !empty($_POST['link_color']) ? trim($_POST['link_color']) : '';

            //ecmoban模板堂 --zhuo start
            $is_new = !empty($_POST['is_new']) ? intval($_POST['is_new']) : 0;
            $is_hot = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
            $is_best = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;

            $ad_type = !empty($_POST['ad_type']) ? intval($_POST['ad_type']) : 0;
            $goods_name = !empty($_POST['goods_name']) ? trim($_POST['goods_name']) : 0;
            //ecmoban模板堂 --zhuo end

            if ($_POST['media_type'] == '0') {
                $ad_link = !empty($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
            } else {
                $ad_link = !empty($_POST['ad_link2']) ? trim($_POST['ad_link2']) : '';
            }

            /* 获得广告的开始时期与结束日期 */
            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('touch_ad') . " AS a, " .
                $this->dsc->table('touch_ad_position') . " AS p " .
                " WHERE a.ad_name = '$ad_name' AND a.position_id = p.position_id AND p.theme = '$template'";
            if ($this->db->getOne($sql) > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['ad_name_exist'], 0, $link);
            }

            $ad_code = '';
            /* 添加图片类型的广告 */
            if ($_POST['media_type'] == '0') {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $ad_code = basename($image->upload_image($_FILES['ad_img'], 'afficheimg'));
                }
                if (!empty($_POST['img_url'])) {
                    $ad_code = $_POST['img_url'];
                }
                if (((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] > 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] == 'none')) && empty($_POST['img_url'])) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_photo_empty'], 0, $link);
                }
            } /* 如果添加的广告是Flash广告 */
            elseif ($_POST['media_type'] == '1') {
                if ((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] == 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] != 'none')) {
                    /* 检查文件类型 */
                    if ($_FILES['upfile_flash']['type'] != "application/x-shockwave-flash") {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }

                    /* 生成文件名 */
                    $urlstr = local_date('Ymd');
                    for ($i = 0; $i < 6; $i++) {
                        $urlstr .= chr(mt_rand(97, 122));
                    }

                    $source_file = $_FILES['upfile_flash']['tmp_name'];
                    $target = storage_public(DATA_DIR . '/afficheimg/');
                    $file_name = $urlstr . '.swf';

                    if (!move_upload_file($source_file, $target . $file_name)) {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_error'], 0, $link);
                    } else {
                        $ad_code = $file_name;
                    }
                } elseif (!empty($_POST['flash_url'])) {
                    if (substr(strtolower($_POST['flash_url']), strlen($_POST['flash_url']) - 4) != '.swf') {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    $ad_code = $_POST['flash_url'];
                }

                if (((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] > 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] == 'none')) && empty($_POST['flash_url'])) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_flash_empty'], 0, $link);
                }
            } /* 如果广告类型为代码广告 */
            elseif ($_POST['media_type'] == '2') {
                if (!empty($_POST['ad_code'])) {
                    $ad_code = $_POST['ad_code'];
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_code_empty'], 0, $link);
                }
            } /* 广告类型为文本广告 */
            elseif ($_POST['media_type'] == '3') {
                if (!empty($_POST['ad_text'])) {
                    $ad_code = $_POST['ad_text'];
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_text_empty'], 0, $link);
                }
            }

            $ad_code = str_replace(storage_public(), '', $ad_code);
            $this->dscRepository->getOssAddFile([DATA_DIR . '/afficheimg/' . $ad_code]);

            $public_ruid = $adminru['ru_id'];    //ecmoban模板堂 --zhuo
            /* 插入数据 */
            $sql = "INSERT INTO " . $this->dsc->table('touch_ad') . " (position_id,media_type,ad_name,is_new,is_hot,is_best,public_ruid,ad_link,ad_code,start_time,end_time,link_man,link_email,link_phone,click_count,enabled, link_color, ad_type, goods_name)
    VALUES ('$_POST[position_id]',
            '$_POST[media_type]',
            '$ad_name',
            '$is_new',
            '$is_hot',
            '$is_best',
            '$public_ruid',
            '$ad_link',
            '$ad_code',
            '$start_time',
            '$end_time',
            '$_POST[link_man]',
            '$_POST[link_email]',
            '$_POST[link_phone]',
            '0',
            '1',
            '$link_color',
            '$ad_type',
            '$goods_name')";

            $this->db->query($sql);
            /* 记录管理员操作 */
            admin_log($_POST['ad_name'], 'add', 'ads');

            clear_cache_files(); // 清除缓存文件

            /* 提示信息 */

            $link[0]['text'] = $GLOBALS['_LANG']['show_ads_template'];
            $link[0]['href'] = 'template.php?act=setup';

            $link[1]['text'] = $GLOBALS['_LANG']['back_ads_list'];
            $link[1]['href'] = 'touch_ads.php?act=list&ad_type=' . $ad_type;

            $link[2]['text'] = $GLOBALS['_LANG']['continue_add_ad'];
            $link[2]['href'] = 'touch_ads.php?act=add&ad_type=' . $ad_type;
            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['ad_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 广告编辑页面
        /*------------------------------------------------------ */
        elseif ($act == 'edit') {
            admin_priv('touch_ad');

            /* 获取广告数据 */
            $sql = "SELECT * FROM " . $this->dsc->table('touch_ad') . " WHERE ad_id='" . intval($_REQUEST['id']) . "'";
            $ads_arr = $this->db->getRow($sql);

            $ads_arr['ad_name'] = htmlspecialchars($ads_arr['ad_name']);
            /* 格式化广告的有效日期 */
            $ads_arr['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $ads_arr['start_time']);
            $ads_arr['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $ads_arr['end_time']);

            if ($ads_arr['media_type'] == '0') {
                if (strpos($ads_arr['ad_code'], 'http://') === false && strpos($ads_arr['ad_code'], 'https://') === false) {
                    $src = get_image_path(DATA_DIR . '/afficheimg/' . $ads_arr['ad_code']);
                    $this->smarty->assign('img_src', $src);
                } else {
                    $src = $ads_arr['ad_code'];
                    $this->smarty->assign('url_src', $src);
                }
            }
            if ($ads_arr['media_type'] == '1') {
                if (strpos($ads_arr['ad_code'], 'http://') === false && strpos($ads_arr['ad_code'], 'https://') === false) {
                    $src = get_image_path(DATA_DIR . '/afficheimg/' . $ads_arr['ad_code']);
                    $this->smarty->assign('flash_url', $src);
                } else {
                    $src = $ads_arr['ad_code'];
                    $this->smarty->assign('flash_url', $src);
                }
                $this->smarty->assign('src', $src);
            }
            if ($ads_arr['media_type'] == 0) {
                $this->smarty->assign('media_type', $GLOBALS['_LANG']['ad_img']);
            } elseif ($ads_arr['media_type'] == 1) {
                $this->smarty->assign('media_type', $GLOBALS['_LANG']['ad_flash']);
            } elseif ($ads_arr['media_type'] == 2) {
                $this->smarty->assign('media_type', $GLOBALS['_LANG']['ad_html']);
            } elseif ($ads_arr['media_type'] == 3) {
                $this->smarty->assign('media_type', $GLOBALS['_LANG']['ad_text']);
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ads_edit']);
            $this->smarty->assign('action_link', ['href' => 'touch_ads.php?act=list&ad_type=' . $ad_type, 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');
            $this->smarty->assign('position_list', $this->touchAdsManageService->getTouchPositionListFormat($adminru['ru_id'], $ad_type));

            $ads_arr['ad_code'] = $ads_arr ? get_image_path(DATA_DIR . '/afficheimg/' . $ads_arr['ad_code']) : '';
            $this->smarty->assign('ads', $ads_arr);

            return $this->smarty->display('touch_ads_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 广告编辑的处理
        /*------------------------------------------------------ */
        elseif ($act == 'update') {
            admin_priv('touch_ad');

            /* 初始化变量 */
            $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type = !empty($_POST['media_type']) ? intval($_POST['media_type']) : 0;

            //ecmoban模板堂 --zhuo start
            $is_new = !empty($_POST['is_new']) ? intval($_POST['is_new']) : 0;
            $is_hot = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
            $is_best = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;

            $_POST['ad_name'] = !empty($_POST['ad_name']) ? trim($_POST['ad_name']) : '';
            $link_color = !empty($_POST['link_color']) ? trim($_POST['link_color']) : '';

            $ad_type_old = !empty($_POST['ad_type_old']) ? intval($_POST['ad_type_old']) : 0;
            $goods_name = !empty($_POST['goods_name']) ? trim($_POST['goods_name']) : 0;
            //ecmoban模板堂 --zhuo end

            if ($_POST['media_type'] == '0') {
                $ad_link = !empty($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
            } else {
                $ad_link = !empty($_POST['ad_link2']) ? trim($_POST['ad_link2']) : '';
            }

            /* 获得广告的开始时期与结束日期 */
            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            $ad_images = '';
            /* 编辑图片类型的广告 */
            if ($type == 0) {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $img_up_info = basename($image->upload_image($_FILES['ad_img'], 'afficheimg'));
                    $ad_code = "ad_code = '" . $img_up_info . "'" . ',';
                    $ad_images = $img_up_info;
                } else {
                    $ad_code = '';
                }
                if (!empty($_POST['img_url'])) {
                    $ad_code = "ad_code = '$_POST[img_url]', ";
                    $ad_images = $_POST['img_url'];
                }
            } /* 如果是编辑Flash广告 */
            elseif ($type == 1) {
                if ((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] == 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] != 'none')) {
                    /* 检查文件类型 */
                    if ($_FILES['upfile_flash']['type'] != "application/x-shockwave-flash") {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    /* 生成文件名 */
                    $urlstr = local_date('Ymd');
                    for ($i = 0; $i < 6; $i++) {
                        $urlstr .= chr(mt_rand(97, 122));
                    }

                    $source_file = $_FILES['upfile_flash']['tmp_name'];
                    $target = storage_public(DATA_DIR . '/afficheimg/');
                    $file_name = $urlstr . '.swf';

                    if (!move_upload_file($source_file, $target . $file_name)) {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_error'], 0, $link);
                    } else {
                        $ad_code = "ad_code = '$file_name', ";
                    }

                    $ad_images = $file_name;
                } elseif (!empty($_POST['flash_url'])) {
                    if (substr(strtolower($_POST['flash_url']), strlen($_POST['flash_url']) - 4) != '.swf') {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    $ad_code = "ad_code = '" . $_POST['flash_url'] . "', ";

                    $ad_images = $_POST['flash_url'];
                } else {
                    $ad_code = '';
                }
            } /* 编辑代码类型的广告 */
            elseif ($type == 2) {
                $ad_code = "ad_code = '$_POST[ad_code]', ";
            }

            /* 编辑文本类型的广告 */
            if ($type == 3) {
                $ad_code = "ad_code = '$_POST[ad_text]', ";
            }

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 ecmoban模板堂 --zhuo */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('touch_ad') . " AS a, " .
                $this->dsc->table('touch_ad_position') . " AS p " .
                " WHERE a.ad_id <> '$id' AND a.ad_name ='$_POST[ad_name]' AND a.position_id = p.position_id AND p.theme = '$template'";
            if ($this->db->getOne($sql) > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'touch_ads.php?act=edit&ad_type=' . $ad_type . '&id=' . $id];
                return sys_msg($GLOBALS['_LANG']['ad_name_exist'], 0, $link);
            }


            $ad_images = str_replace('../' . DATA_DIR . '/afficheimg/', '', $ad_images);
            $ad_images = str_replace(storage_public(), '', $ad_images);
            $this->dscRepository->getOssAddFile([DATA_DIR . '/afficheimg/' . $ad_images]);

            $ad_code = str_replace(['../' . DATA_DIR . '/afficheimg/', DATA_DIR . '/afficheimg/', storage_public()], '', $ad_code);

            /* 更新信息 */
            $sql = "UPDATE " . $this->dsc->table('touch_ad') . " SET " .
                "position_id = '$_POST[position_id]', " .
                "ad_name     = '$_POST[ad_name]', " .
                "ad_link     = '$ad_link', " .
                "link_color  = '$link_color', " .
                "is_new     = '$is_new', " .
                "is_hot     = '$is_hot', " .
                "is_best     = '$is_best', " .
                $ad_code .
                "start_time  = '$start_time', " .
                "end_time    = '$end_time', " .
                "link_man    = '$_POST[link_man]', " .
                "link_email  = '$_POST[link_email]', " .
                "link_phone  = '$_POST[link_phone]', " .
                "enabled     = '$_POST[enabled]', " .
                "ad_type  = '$ad_type_old', " .
                "goods_name  = '$goods_name' " .
                "WHERE ad_id = '$id'";
            $this->db->query($sql);

            /* 记录管理员操作 */
            admin_log($_POST['ad_name'], 'edit', 'ads');

            clear_cache_files(); // 清除模版缓存

            /* 提示信息 */
            $href[] = ['text' => $GLOBALS['_LANG']['back_ads_list'], 'href' => 'touch_ads.php?act=list&ad_type=' . $ad_type];
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['ad_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $href);
        }

        /*------------------------------------------------------ */
        //--生成广告的JS代码
        /*------------------------------------------------------ */
        elseif ($act == 'add_js') {
            admin_priv('touch_ad');

            /* 编码 */
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];

            $js_code = "<script type=" . '"' . "text/javascript" . '"';
            $js_code .= ' src=' . '"' . $this->dsc->url() . 'affiche.php?act=js&type=' . $_REQUEST['type'] . '&ad_id=' . intval($_REQUEST['id']) . '"' . '></script>';

            $site_url = $this->dsc->url() . 'affiche.php?act=js&type=' . $_REQUEST['type'] . '&ad_id=' . intval($_REQUEST['id']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_js_code']);
            $this->smarty->assign('action_link', ['href' => 'touch_ads.php?act=list&ad_type=' . $ad_type, 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('url', $site_url);
            $this->smarty->assign('js_code', $js_code);
            $this->smarty->assign('lang_list', $lang_list);

            return $this->smarty->display('touch_ads_js.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑广告名称
        /*------------------------------------------------------ */
        elseif ($act == 'edit_ad_name') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $ad_name = json_str_iconv(trim($_POST['val']));

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 ecmoban模板堂 --zhuo */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('touch_ad') . " AS a, " .
                $this->dsc->table('touch_ad_position') . " AS p " .
                " WHERE a.ad_id <> '$id' AND a.ad_name ='$ad_name' AND a.position_id = p.position_id AND p.theme = '$template'";
            if ($this->db->getOne($sql) > 0) {
                $res = 1;
            } else {
                $res = 0;
            }

            /* 检查广告名称是否重复 */
            if ($res) {
                return make_json_error(sprintf($GLOBALS['_LANG']['ad_name_exist'], $ad_name));
            } else {
                if ($exc->edit("ad_name = '$ad_name'", $id)) {
                    admin_log($ad_name, 'edit', 'ads');
                    return make_json_result(stripslashes($ad_name));
                } else {
                    return make_json_error($this->db->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除广告位置
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            $img = $exc->get_name($id, 'ad_code');

            $exc->drop($id);

            if ((strpos($img, 'http://') === false) && (strpos($img, 'https://') === false)) {
                $img_name = basename($img);
                dsc_unlink(storage_public(DATA_DIR . '/afficheimg/' . $img_name));
            }

            admin_log('', 'remove', 'ads');

            $url = 'touch_ads.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

}
