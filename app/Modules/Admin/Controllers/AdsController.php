<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\Ad;
use App\Repositories\Common\DscRepository;
use App\Services\Ads\AdsManageService;
use App\Services\Store\StoreCommonService;

/**
 * 广告管理程序
 */
class AdsController extends InitController
{
    protected $adsManageService;
    protected $dscRepository;
    protected $storeCommonService;

    public function __construct(
        AdsManageService $adsManageService,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->adsManageService = $adsManageService;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

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

        /*------------------------------------------------------ */
        //-- 广告列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $urlPid = empty($_REQUEST['pid']) ? '' : '&pid=' . trim($_REQUEST['pid']); //广告位 by wu

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ad_list']);
            if (!empty($urlPid)) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['ads_add'], 'href' => 'ads.php?act=add' . $urlPid]);
            }
            $this->smarty->assign('full_page', 1);

            $position_list = $this->adsManageService->getPositionList($adminru['ru_id']);
            $this->smarty->assign('position_list', $position_list);

            $ads_list = $this->adsManageService->getAdsList();

            $this->smarty->assign('ads_list', $ads_list['ads']);
            $this->smarty->assign('filter', $ads_list['filter']);
            $this->smarty->assign('record_count', $ads_list['record_count']);
            $this->smarty->assign('page_count', $ads_list['page_count']);
            $this->smarty->assign('pid', $ads_list['filter']['pid']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($ads_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('ads_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $ads_list = $this->adsManageService->getAdsList();

            $this->smarty->assign('ads_list', $ads_list['ads']);
            $this->smarty->assign('filter', $ads_list['filter']);
            $this->smarty->assign('record_count', $ads_list['record_count']);
            $this->smarty->assign('page_count', $ads_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($ads_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('ads_list.dwt'),
                '',
                ['filter' => $ads_list['filter'], 'page_count' => $ads_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加新广告页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('ad_manage');

            //广告位 by wu start
            $pid = empty($_REQUEST['pid']) ? '' : trim($_REQUEST['pid']);

            if (!empty($pid)) {
                session([
                    'pid' => $pid
                ]);

                $catFirst = $this->adsManageService->getCatList();
                $this->smarty->assign('catFirst', $catFirst);

                $rec = $this->adsManageService->get_ad_model($pid);

                /* 需要添加第二张图片或标题的广告位传值 */
                $another_pic = in_array($rec['ad_model'], ['recommend_category', 'recommend_merchants', 'expert_field_ad', 'category_top_default_brand']);
                $title = in_array($rec['ad_model'], ['recommend_category', 'expert_field_ad', 'merchants_index_case_ad', 'cat_goods_ad_left', 'cat_goods_ad_right', 'recommend_merchants', 'category_top_default_brand']);
                $this->smarty->assign('another_pic', $another_pic);
                $this->smarty->assign('title', $title);

                $recommend_merchant = isset($recommend_merchant) ? $recommend_merchant : '';
                $is_recommend = isset($is_recommend) ? $is_recommend : '';
                $expert_field = isset($expert_field) ? $expert_field : '';
                $cat_goods_ad = isset($cat_goods_ad) ? $cat_goods_ad : '';
                $merchants_index_case_ad = isset($merchants_index_case_ad) ? $merchants_index_case_ad : '';

                $this->smarty->assign('recommend_merchant', $recommend_merchant);
                $this->smarty->assign('is_recommend', $is_recommend);
                $this->smarty->assign('expert_field', $expert_field);
                $this->smarty->assign('cat_goods_ad', $cat_goods_ad);
                $this->smarty->assign('merchants_index_case_ad', $merchants_index_case_ad);

                $ad_model = json_encode($rec);
                $this->smarty->assign('ad_model', $ad_model);
            }
            //广告位 by wu end

            $ad_link = empty($_GET['ad_link']) ? '' : trim($_GET['ad_link']);
            $ad_name = empty($_GET['ad_name']) ? '' : trim($_GET['ad_name']);

            $start_time = local_date($GLOBALS['_CFG']['time_format']);
            $end_time = local_date($GLOBALS['_CFG']['time_format'], gmtime() + 3600 * 24 * 30);  // 默认结束时间为1个月以后

            $this->smarty->assign('ads', ['ad_link' => $ad_link, 'ad_name' => $ad_name, 'start_time' => $start_time,
                'end_time' => $end_time, 'enabled' => 1, 'position_id' => $pid]);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ads_add']);
            //$this->smarty->assign('action_link',   array('href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']));
            $this->smarty->assign('action_link', ['href' => 'ads.php?act=list' . '&pid=' . $pid, 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('position_list', get_position_list());

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            set_default_filter(); //by wu


            return $this->smarty->display('ads_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 新广告的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('ad_manage');

            /* 初始化变量 */
            $ad_name = !empty($_POST['ad_name']) ? trim($_POST['ad_name']) : '';
            $link_color = !empty($_POST['link_color']) ? trim($_POST['link_color']) : '';
            $b_title = !empty($_POST['b_title']) ? trim($_POST['b_title']) : '';
            $s_title = !empty($_POST['s_title']) ? trim($_POST['s_title']) : '';

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
            $res = Ad::where('ad_name', $ad_name)->whereHas('getAdPosition', function ($query) use ($template) {
                $query->where('theme', $template);
            })->count();
            if ($res > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['ad_name_exist'], 0, $link);
            }
            /* 添加图片类型的广告 */
            if ($_POST['media_type'] == '0') {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $ad_code = basename($image->upload_image($_FILES['ad_img'], 'afficheimg'));
                }
                if (!empty($_POST['img_url'])) {
                    $image_url = $_POST['img_url'];
                    if ($image_url) {
                        if (!empty($image_url) && ($image_url != $GLOBALS['_LANG']['img_file']) && ($image_url != 'http://') && copy(trim($image_url), storage_public('data/afficheimg/' . basename($image_url)))) {
                            $image_url = trim($image_url);
                            //定义原图路径
                            $ad_code = basename($image_url);
                        }
                    }
                }
                if (((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] > 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] == 'none')) && empty($_POST['img_url'])) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_photo_empty'], 0, $link);
                }

                $ad_bg_code = '';

                //推荐广告背景图片 liu
                if ((isset($_FILES['ad_bg_img']['error']) && $_FILES['ad_bg_img']['error'] == 0) || (!isset($_FILES['ad_bg_img']['error']) && isset($_FILES['ad_bg_img']['tmp_name']) && $_FILES['ad_bg_img']['tmp_name'] != 'none')) {
                    $ad_bg_code = $image->upload_image($_FILES['ad_bg_img'], 'afficheimg');
                }
                if (((isset($_FILES['ad_bg_img']['error']) && $_FILES['ad_bg_img']['error'] > 0) || (!isset($_FILES['ad_bg_img']['error']) && isset($_FILES['ad_bg_img']['tmp_name']) && $_FILES['ad_bg_img']['tmp_name'] == 'none')) && empty($_POST['img_url'])) {
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
            $add_file = [];

            if ($ad_code) {
                if (strpos($ad_code, DATA_DIR . '/afficheimg/') === false) {
                    $add_file[] = DATA_DIR . '/afficheimg/' . $ad_code;
                } else {
                    $add_file[] = $ad_code;
                }
            }

            if ($ad_bg_code) {
                $add_file[] = DATA_DIR . '/afficheimg/' . $ad_bg_code;
            }

            $this->dscRepository->getOssAddFile($add_file);

            $public_ruid = $adminru['ru_id'];    //ecmoban模板堂 --zhuo

            $ad_code = str_replace(['../' . DATA_DIR . '/afficheimg/', DATA_DIR . '/afficheimg/', storage_public()], '', $ad_code);

            $ad_bg_code = str_replace(['../' . DATA_DIR . '/afficheimg/', DATA_DIR . '/afficheimg/', storage_public()], '', $ad_bg_code);

            /* 插入数据 */
            $other = [
                'position_id' => isset($_POST['position_id']) ? intval($_POST['position_id']) : 0,
                'ad_name' => isset($_POST['ad_name']) ? addslashes($_POST['ad_name']) : '',
                'media_type' => isset($_POST['media_type']) ? intval($_POST['media_type']) : 0,
                'public_ruid' => $public_ruid,
                'click_count' => 0,
                'enabled' => 1,
                'ad_link' => $ad_link,
                'link_color' => $link_color,
                'b_title' => $b_title,
                's_title' => $s_title,
                'is_new' => $is_new,
                'is_hot' => $is_hot,
                'is_best' => $is_best,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'link_man' => isset($_POST['link_man']) ? addslashes($_POST['link_man']) : '',
                'link_email' => isset($_POST['link_email']) ? addslashes($_POST['link_email']) : '',
                'link_phone' => isset($_POST['link_phone']) ? addslashes($_POST['link_phone']) : '',
                'ad_type' => $ad_type,
                'goods_name' => $goods_name,
                'ad_code' => $ad_code,
                'ad_bg_code' => $ad_bg_code
            ];

            Ad::insert($other);

            /* 记录管理员操作 */
            admin_log($_POST['ad_name'], 'add', 'ads');

            clear_cache_files(); // 清除缓存文件

            /* 提示信息 */

            $link[0]['text'] = $GLOBALS['_LANG']['back_ads_list'];
            $link[0]['href'] = 'ads.php?act=list' . '&pid=' . $_POST['position_id'];

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add_ad'];
            $link[1]['href'] = 'ads.php?act=add' . '&pid=' . $_POST['position_id'];
            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['ad_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 广告编辑页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('ad_manage');

            /* 获取广告数据 */
            $ads_arr = Ad::find(intval($_REQUEST['id']));
            //广告位 by wu start
            $pid = empty($ads_arr['position_id']) ? '' : trim($ads_arr['position_id']);

            if (!empty($pid)) {
                $catFirst = $this->adsManageService->getCatList();
                $this->smarty->assign('catFirst', $catFirst);

                $rec = $this->adsManageService->get_ad_model($pid);

                /* 需要添加第二张图片或标题的广告位传值 */
                $another_pic = in_array($rec['ad_model'], ['recommend_category', 'recommend_merchants', 'expert_field_ad', 'category_top_default_brand']);
                $title = in_array($rec['ad_model'], ['recommend_category', 'expert_field_ad', 'merchants_index_case_ad', 'cat_goods_ad_left', 'cat_goods_ad_right', 'recommend_merchants', 'category_top_default_brand']);
                $this->smarty->assign('another_pic', $another_pic);
                $this->smarty->assign('title', $title);

                $ad_model = json_encode($rec);
                $this->smarty->assign('ad_model', $ad_model);
            }
            //广告位 by wu end

            $ads_arr['ad_name'] = htmlspecialchars($ads_arr['ad_name']);
            /* 格式化广告的有效日期 */
            $ads_arr['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $ads_arr['start_time']);
            $ads_arr['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $ads_arr['end_time']);

            if ($ads_arr['media_type'] == '0') {
                if (strpos($ads_arr['ad_code'], 'http://') === false && strpos($ads_arr['ad_code'], 'https://') === false) {
                    $src = $ads_arr['ad_code'];
                    $src = get_image_path($src);
                    $this->smarty->assign('img_src', $src);
                } else {
                    $src = $ads_arr['ad_code'];
                    $src = str_replace('../', '', $src);
                    $src = get_image_path($src);
                    $this->smarty->assign('url_src', $src);
                }
            }
            if ($ads_arr['media_type'] == '1') {
                if (strpos($ads_arr['ad_code'], 'http://') === false && strpos($ads_arr['ad_code'], 'https://') === false) {
                    $src = $ads_arr['ad_code'];
                    $src = get_image_path($src);
                    $this->smarty->assign('flash_url', $src);
                } else {
                    $src = $ads_arr['ad_code'];
                    $src = str_replace('../', '', $src);
                    $src = get_image_path($src);
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

            //出来广告图片链接
            if ($ads_arr['ad_code']) {
                if (strpos($ads_arr['ad_code'], 'http://') === false && strpos($ads_arr['ad_code'], 'https://') === false) {
                    $src = $ads_arr['ad_code'];
                    $src = get_image_path(DATA_DIR . '/afficheimg/' . $src);
                    $ads_arr['ad_code'] = $src;
                } else {
                    $src = $ads_arr['ad_code'];
                    $src = str_replace('../', '', $src);
                    $src = get_image_path($src);
                    $ads_arr['ad_code'] = $src;
                }
            }
            //出来广告背景链接
            if ($ads_arr['ad_bg_code']) {
                if (strpos($ads_arr['ad_bg_code'], 'http://') === false && strpos($ads_arr['ad_bg_code'], 'https://') === false) {
                    $src = $ads_arr['ad_bg_code'];
                    $src = get_image_path(DATA_DIR . '/afficheimg/' . $src);
                    $ads_arr['ad_bg_code'] = $src;
                } else {
                    $src = $ads_arr['ad_bg_code'];
                    $src = str_replace('../', '', $src);
                    $src = get_image_path($src);
                    $ads_arr['ad_bg_code'] = $src;
                }
            }
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ads_edit']);
            $this->smarty->assign('action_link', ['href' => 'ads.php?act=list' . '&pid=' . $pid, 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');
            $this->smarty->assign('position_list', get_position_list());
            $this->smarty->assign('ads', $ads_arr);

            set_default_filter(); //by wu

            return $this->smarty->display('ads_info.dwt');
        }
        /*------------------------------------------------------ */
        //-- 编辑广告名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_ad_name') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $ad_name = json_str_iconv(trim($_POST['val']));

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 ecmoban模板堂 --zhuo */
            $res = Ad::where('ad_id', '<>', $id)->where('ad_name', $ad_name)
                ->whereHas('getAdPosition', function ($query) use ($template) {
                    $query->where('theme', $template);
                })->count();
            /* 检查广告名称是否重复 */
            if ($res) {
                return make_json_error(sprintf($GLOBALS['_LANG']['ad_name_exist'], $ad_name));
            } else {
                if (Ad::where('ad_id', $id)->update(['ad_name' => $ad_name])) {
                    admin_log($ad_name, 'edit', 'ads');
                }
                return make_json_result(stripslashes($ad_name));
            }
        }

        /*------------------------------------------------------ */
        //-- 广告编辑的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            admin_priv('ad_manage');

            /* 初始化变量 */
            $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type = !empty($_POST['media_type']) ? intval($_POST['media_type']) : 0;

            //ecmoban模板堂 --zhuo start
            $is_new = !empty($_POST['is_new']) ? intval($_POST['is_new']) : 0;
            $is_hot = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
            $is_best = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;

            $_POST['ad_name'] = !empty($_POST['ad_name']) ? trim($_POST['ad_name']) : '';
            $link_color = !empty($_POST['link_color']) ? trim($_POST['link_color']) : '';
            $b_title = !empty($_POST['b_title']) ? trim($_POST['b_title']) : '';
            $s_title = !empty($_POST['s_title']) ? trim($_POST['s_title']) : '';

            $ad_type = !empty($_POST['ad_type']) ? intval($_POST['ad_type']) : 0;
            $goods_name = !empty($_POST['goods_name']) ? trim($_POST['goods_name']) : 0;
            //ecmoban模板堂 --zhuo end

            if ($type == 0) {
                $ad_link = !empty($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
            } else {
                $ad_link = !empty($_POST['ad_link2']) ? trim($_POST['ad_link2']) : '';
            }

            /* 获得广告的开始时期与结束日期 */
            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            /* 编辑图片类型的广告 */
            if ($type == 0) {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $img_up_info = $image->upload_image($_FILES['ad_img'], 'afficheimg');
                    $ad_code = $img_up_info;
                    $ad_images = $img_up_info;
                    $code = Ad::where('ad_id', $id)->value('ad_code');
                    $code = $code ? $code : '';
                    if ($code && $code != $img_up_info) {
                        dsc_unlink(storage_public(DATA_DIR . '/afficheimg/' . $code));
                    }
                } else {
                    $ad_code = '';
                    $ad_images = '';
                }
                if (!empty($_POST['img_url'])) {
                    $image_url = $_POST['img_url'];
                    if ($image_url) {
                        if (!empty($image_url) && ($image_url != $GLOBALS['_LANG']['img_file']) && ($image_url != 'http://') && copy(trim($image_url), storage_public('data/afficheimg/' . basename($image_url)))) {
                            $image_url = trim($image_url);
                            //定义原图路径
                            $ad_code = basename($image_url);
                        }
                    }
                    $ad_images = basename($image_url);
                }

                //推荐广告背景图片 liu
                if ((isset($_FILES['ad_bg_img']['error']) && $_FILES['ad_bg_img']['error'] == 0) || (!isset($_FILES['ad_bg_img']['error']) && isset($_FILES['ad_bg_img']['tmp_name']) && $_FILES['ad_bg_img']['tmp_name'] != 'none')) {
                    $bg_img_up_info = $image->upload_image($_FILES['ad_bg_img'], 'afficheimg');
                    $ad_bg_code = $bg_img_up_info;
                    $ad_bg_images = $bg_img_up_info;

                    $bg_code = Ad::where('ad_id', $id)->value('ad_bg_code');
                    $bg_code = $bg_code ? $bg_code : '';
                    if ($bg_code && $bg_code != $bg_img_up_info) {
                        @unlink(storage_public(DATA_DIR . '/afficheimg/' . $bg_code));
                    }
                } else {
                    $ad_bg_code = '';
                    $ad_bg_images = '';
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
                        $ad_code = $file_name;
                        $ad_images = $file_name;
                    }
                } elseif (!empty($_POST['flash_url'])) {
                    if (substr(strtolower($_POST['flash_url']), strlen($_POST['flash_url']) - 4) != '.swf') {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    $ad_code = $_POST['flash_url'];
                    $ad_images = $_POST['flash_url'];
                } else {
                    $ad_code = '';
                    $ad_images = '';
                }
            } /* 编辑代码类型的广告 */
            elseif ($type == 2) {
                $ad_code = request()->input('ad_code', '');
            }

            /* 编辑文本类型的广告 */
            if ($type == 3) {
                $ad_code = request()->input('ad_text', '');
            }

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 ecmoban模板堂 --zhuo */
            $res = Ad::where('ad_id', '<>', $id)->where('ad_name', $_POST['ad_name'])
                ->whereHas('getAdPosition', function ($query) use ($template) {
                    $query->where('theme', $template);
                })->count();
            if ($res > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'ads.php?act=edit&id=' . $id];
                return sys_msg($GLOBALS['_LANG']['ad_name_exist'], 1, $link);
            }
            $add_file = [];

            if ($ad_images) {
                $add_file[] = $ad_images;
            }

            if ($ad_bg_images) {
                $add_file[] = $ad_bg_images;
            }

            $this->dscRepository->getOssAddFile($add_file);

            $other = [
                'position_id' => isset($_POST['position_id']) ? intval($_POST['position_id']) : 0,
                'ad_name' => isset($_POST['ad_name']) ? addslashes($_POST['ad_name']) : '',
                'ad_link' => $ad_link,
                'link_color' => $link_color,
                'b_title' => $b_title,
                's_title' => $s_title,
                'is_new' => $is_new,
                'is_hot' => $is_hot,
                'is_best' => $is_best,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'link_man' => isset($_POST['link_man']) ? addslashes($_POST['link_man']) : '',
                'link_email' => isset($_POST['link_email']) ? addslashes($_POST['link_email']) : '',
                'link_phone' => isset($_POST['link_phone']) ? addslashes($_POST['link_phone']) : '',
                'enabled' => isset($_POST['enabled']) ? addslashes($_POST['enabled']) : '',
                'ad_type' => $ad_type,
                'goods_name' => $goods_name
            ];

            if ($ad_code) {
                $ad_code = str_replace(['../' . DATA_DIR . '/afficheimg/', DATA_DIR . '/afficheimg/'], '', $ad_code);

                $other['ad_code'] = $ad_code;
            }

            if ($ad_bg_code) {
                $ad_bg_code = str_replace(['../' . DATA_DIR . '/afficheimg/', DATA_DIR . '/afficheimg/'], '', $ad_bg_code);

                $other['ad_bg_code'] = $ad_bg_code;
            }

            Ad::where('ad_id', $id)->update($other);

            /* 记录管理员操作 */
            admin_log($_POST['ad_name'], 'edit', 'ads');

            clear_cache_files(); // 清除模版缓存

            /* 提示信息 */
            $href[] = ['text' => $GLOBALS['_LANG']['back_ads_list'], 'href' => 'ads.php?act=list' . '&pid=' . $_POST['position_id']];
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['ad_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $href);
        }

        /*------------------------------------------------------ */
        //--生成广告的JS代码
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_js') {
            admin_priv('ad_manage');

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
            $this->smarty->assign('action_link', ['href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('url', $site_url);
            $this->smarty->assign('js_code', $js_code);
            $this->smarty->assign('lang_list', $lang_list);


            return $this->smarty->display('ads_js.htm');
        }

        /*------------------------------------------------------ */
        //-- 删除广告位置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id'] ?? 0);
            $img = Ad::where('ad_id', $id)->value('ad_code');
            $img = $img ? $img : '';
            Ad::where('ad_id', $id)->delete();
            if ((strpos($img, 'http://') === false) && (strpos($img, 'https://') === false)) {
                $img_name = basename($img);

                $this->dscRepository->getOssDelFile([DATA_DIR . '/afficheimg/' . $img_name]);
                @unlink(storage_public(DATA_DIR . '/afficheimg/' . $img_name));
            }

            admin_log('', 'remove', 'ads');

            $url = 'ads.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 获取分类列表 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'getCatList') {
            $catId = empty($_REQUEST['catId']) ? '' : trim($_REQUEST['catId']);
            $catList = $this->adsManageService->getCatList($catId);
            return response()->json($catList);
        } /*------------------------------------------------------ */
        //-- 获取广告位结构
        /*------------------------------------------------------ */
        else if ($_REQUEST['act'] == 'get_position') {
            $position_id = !empty($_GET['position_id']) ? intval($_GET['position_id']) : 0;
            $reg = '/\D+/';
            $position_model = Ad::selectRaw('COUNT(*) AS position_num,ad_name')->orderBy('ad_id', 'DESC')->where('position_id', $position_id)->first();
            if (count($position_model) > 0) {
                preg_match_all($reg, $position_model['ad_name'], $res);
                $position_model['ad_name'] = $res[0][0];
            }
            return response()->json($position_model);
        }
    }
}
