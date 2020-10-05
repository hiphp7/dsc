<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 广告管理程序
 */
class AdsController extends InitController
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
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        $exc = new Exchange($this->dsc->table("ad"), $this->db, 'ad_id', 'ad_name');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "ads");

        /* act操作项的初始化 */
        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('menu_select', ['action' => '05_banner', 'current' => 'ad_list']);

        /*------------------------------------------------------ */
        //-- 广告列表页面
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['05_banner']);
            //广告位 by wu
            $urlPid = addslashes(trim(request()->input('pid', '')));
            $urlPid = $urlPid ? '&pid=' . $urlPid : '';

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ad_list']);
            if (!empty($urlPid)) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['ads_add'], 'href' => 'ads.php?act=add' . $urlPid, 'class' => 'icon-plus']);
            }
            $this->smarty->assign('full_page', 1);

            //获取位置列表 by wu start
            $where = " WHERE 1 ";
            if ($adminru['ru_id'] > 0) {
                $where .= " AND is_public = 1 ";
            }
            $sql = 'SELECT position_id, position_name, ad_width, ad_height ' .
                'FROM ' . $this->dsc->table('ad_position') . $where;// 改判断条件 商家广告位置由admin设置公共属性 liu
            $position_list = $this->db->getAll($sql);
            $this->smarty->assign('position_list', $position_list);
            //获取位置列表 by wu end

            $ads_list = $this->get_adslist($adminru['ru_id']);

            $page = (int)request()->input('page', 1);
            $page_count_arr = seller_page($ads_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('current', 'ads');
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
        elseif ($act == 'query') {
            $page = (int)request()->input('page', 1);
            $ads_list = $this->get_adslist($adminru['ru_id']);

            $page_count_arr = seller_page($ads_list, $page);

            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('ads_list', $ads_list['ads']);
            $this->smarty->assign('filter', $ads_list['filter']);
            $this->smarty->assign('record_count', $ads_list['record_count']);
            $this->smarty->assign('page_count', $ads_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($ads_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $this->smarty->assign('current', 'ads');
            return make_json_result(
                $this->smarty->fetch('ads_list.dwt'),
                '',
                ['filter' => $ads_list['filter'], 'page_count' => $ads_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加新广告页面
        /*------------------------------------------------------ */
        elseif ($act == 'add') {
            admin_priv('ad_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['05_banner']);

            //广告位 by wu start
            $pid = (int)request()->input('pid', 0);
            if (!empty($pid)) {
                session([
                    'pid' => $pid
                ]);

                $catFirst = $this->getCatList();
                $this->smarty->assign('catFirst', $catFirst);

                $ad_model = json_encode($this->get_ad_model($pid));
                $this->smarty->assign('ad_model', $ad_model);
            }
            //广告位 by wu end

            $ad_link = addslashes(trim(request()->input('ad_link', '')));
            $ad_name = addslashes(trim(request()->input('ad_name', '')));

            $start_time = local_date($GLOBALS['_CFG']['time_format']);
            $end_time = local_date($GLOBALS['_CFG']['time_format'], gmtime() + 3600 * 24 * 30);  // 默认结束时间为1个月以后

            $this->smarty->assign(
                'ads',
                ['ad_link' => $ad_link, 'ad_name' => $ad_name, 'start_time' => $start_time,
                    'end_time' => $end_time, 'enabled' => 1, 'position_id' => $pid]
            );

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ads_add']);
            $this->smarty->assign('action_link', ['href' => 'ads.php?act=list' . '&pid=' . $pid, 'text' => $GLOBALS['_LANG']['ad_list'], 'class' => 'icon-reply']);
            $this->smarty->assign('position_list', get_position_list());

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('position_id', $pid);

            set_default_filter(0, 0, $adminru['ru_id']); //by wu


            $this->smarty->assign('current', 'ads');
            return $this->smarty->display('ads_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 新广告的处理
        /*------------------------------------------------------ */
        elseif ($act == 'insert') {
            admin_priv('ad_manage');

            /* 初始化变量 */
            $flash_url = request()->input('flash_url', '');
            $ad_name = addslashes(trim(request()->input('ad_name', '')));
            $link_color = addslashes(trim(request()->input('link_color', '')));
            $type = (int)request()->input('media_type', 0);
            //ecmoban模板堂 --zhuo start
            $is_new = (int)request()->input('is_new', 0);
            $is_hot = (int)request()->input('is_hot', 0);
            $is_best = (int)request()->input('is_best', 0);
            $ad_type = (int)request()->input('ad_type', 0);
            $goods_name = (int)request()->input('goods_name', 0);
            //ecmoban模板堂 --zhuo end

            if ($type == '0') {
                $ad_link = addslashes(trim(request()->input('ad_link', '')));
            } else {
                $ad_link = addslashes(trim(request()->input('ad_link2', '')));
            }

            /* 获得广告的开始时期与结束日期 */
            $start_time = request()->input('start_time', '');
            $start_time = $start_time ? local_strtotime($start_time) : '';
            $end_time = request()->input('end_time', '');
            $end_time = $end_time ? local_strtotime($end_time) : '';

            $position_id = request()->input('position_id');
            $link_man = request()->input('link_man', '');
            $link_email = request()->input('link_email', '');
            $link_phone = request()->input('link_phone', '');

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('ad') . " AS a, " .
                $this->dsc->table('ad_position') . " AS p " .
                " WHERE a.ad_name = '$ad_name' AND a.position_id = p.position_id AND p.theme = '$template'";
            if ($this->db->getOne($sql) > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['ad_name_exist'], 0, $link);
            }

            /* 添加图片类型的广告 */
            if ($type == '0') {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $ad_code = basename($image->upload_image($_FILES['ad_img'], 'afficheimg'));
                }
                if (!empty(request()->input('img_url', ''))) {
                    $ad_code = request()->input('img_url', '');
                }
                if (((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] > 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] == 'none')) && empty(request()->input('img_url', ''))) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_photo_empty'], 0, $link);
                }
            } /* 如果添加的广告是Flash广告 */
            elseif ($type == '1') {
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
                } elseif (!empty($flash_url)) {
                    if (substr(strtolower($flash_url), strlen($flash_url) - 4) != '.swf') {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    $ad_code = $flash_url;
                }

                if (((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] > 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] == 'none')) && empty($flash_url)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_flash_empty'], 0, $link);
                }
            } /* 如果广告类型为代码广告 */
            elseif ($type == '2') {
                if (request()->has('ad_code')) {
                    $ad_code = request()->input('ad_code', '');
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_code_empty'], 0, $link);
                }
            } /* 广告类型为文本广告 */
            elseif ($type == '3') {
                if (!empty(request()->input('ad_text', ''))) {
                    $ad_code = request()->input('ad_text', '');
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['ad_text_empty'], 0, $link);
                }
            }

            $this->dscRepository->getOssAddFile([DATA_DIR . '/afficheimg/' . $ad_code]);

            $public_ruid = $adminru['ru_id'];    //ecmoban模板堂 --zhuo
            /* 插入数据 */
            $sql = "INSERT INTO " . $this->dsc->table('ad') . " (position_id,media_type,ad_name,is_new,is_hot,is_best,public_ruid,ad_link,ad_code,start_time,end_time,link_man,link_email,link_phone,click_count,enabled, link_color, ad_type, goods_name)
    VALUES ('$position_id',
            '$type',
            '$ad_name',
            '$is_new',
            '$is_hot',
            '$is_best',
            '$public_ruid',
            '$ad_link',
            '$ad_code',
            '$start_time',
            '$end_time',
            '$link_man',
            '$link_email',
            '$link_phone',
            '0',
            '1',
            '$link_color',
            '$ad_type',
            '$goods_name')";

            $this->db->query($sql);
            /* 记录管理员操作 */
            admin_log($ad_name, 'add', 'ads');

            clear_cache_files(); // 清除缓存文件

            /* 提示信息 */

            //$link[0]['text'] = $GLOBALS['_LANG']['show_ads_template'];
            //$link[0]['href'] = 'template.php?act=setup';

            $link[1]['text'] = $GLOBALS['_LANG']['back_ads_list'];
            //$link[1]['href'] = 'ads.php?act=list';
            $link[1]['href'] = 'ads.php?act=list' . '&pid=' . $position_id;

            $link[2]['text'] = $GLOBALS['_LANG']['continue_add_ad'];
            //$link[2]['href'] = 'ads.php?act=add';
            $link[2]['href'] = 'ads.php?act=add' . '&pid=' . $position_id;
            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $ad_name . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 广告编辑页面
        /*------------------------------------------------------ */
        elseif ($act == 'edit') {
            admin_priv('ad_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['05_banner']);
            $id = (int)request()->input('id', 0);
            /* 获取广告数据 */
            $sql = "SELECT * FROM " . $this->dsc->table('ad') . " WHERE ad_id='" . $id . "'";
            $ads_arr = $this->db->getRow($sql);

            //广告位 by wu start
            $pid = empty($ads_arr['position_id']) ? '' : trim($ads_arr['position_id']);

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['ads_edit'], 'href' => 'javascript:;'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            if (!empty($pid)) {
                $catFirst = $this->getCatList();
                $this->smarty->assign('catFirst', $catFirst);

                $ad_model = json_encode($this->get_ad_model($pid));
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

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ads_edit']);
            $this->smarty->assign('action_link', ['href' => 'ads.php?act=list' . '&pid=' . $pid, 'text' => $GLOBALS['_LANG']['ad_list'], 'class' => 'icon-reply']);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');
            $this->smarty->assign('position_list', get_position_list());
            $this->smarty->assign('ads', $ads_arr);
            set_seller_default_filter(0, 0, $adminru['ru_id']); //by wu

            $this->smarty->assign('current', 'ads');
            return $this->smarty->display('ads_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 广告编辑的处理
        /*------------------------------------------------------ */
        elseif ($act == 'update') {
            admin_priv('ad_manage');

            /* 初始化变量 */
            $flash_url = request()->input('flash_url', '');
            $id = (int)request()->input('id', 0);
            $type = (int)request()->input('media_type', 0);

            //ecmoban模板堂 --zhuo start
            $is_new = (int)request()->input('is_new', 0);
            $is_hot = (int)request()->input('is_hot', 0);
            $is_best = (int)request()->input('is_best', 0);

            $ad_name = addslashes(trim(request()->input('ad_name', '')));
            $position_id = request()->input('position_id');
            $link_man = request()->input('link_man', '');
            $link_email = request()->input('link_email', '');
            $link_phone = request()->input('link_phone', '');
            $enabled = request()->input('enabled', '');

            $link_color = addslashes(trim(request()->input('link_color', '')));
            $ad_type = (int)request()->input('ad_type', 0);
            $goods_name = request()->input('goods_name', '');

            //ecmoban模板堂 --zhuo end

            if ($type == '0') {
                $ad_link = addslashes(trim(request()->input('ad_link', '')));
            } else {
                $ad_link = addslashes(trim(request()->input('ad_link2', '')));
            }

            /* 获得广告的开始时期与结束日期 */
            $start_time = request()->input('start_time', '');
            $start_time = $start_time ? local_strtotime($start_time) : '';

            $end_time = request()->input('end_time', '');
            $end_time = $end_time ? local_strtotime($end_time) : '';


            /* 编辑图片类型的广告 */
            if ($type == 0) {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $img_up_info = basename($image->upload_image($_FILES['ad_img'], 'afficheimg'));
                    $ad_code = "ad_code = '" . $img_up_info . "'" . ',';
                } else {
                    $ad_code = '';
                }
                if (!empty(request()->input('img_url', ''))) {
                    $ad_code = "ad_code = '" . request()->input('img_url', '') . "', ";
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
                } elseif (!empty($flash_url)) {
                    if (substr(strtolower($flash_url), strlen($flash_url) - 4) != '.swf') {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    $ad_code = "ad_code = '" . $flash_url . "', ";
                } else {
                    $ad_code = '';
                }
            } /* 编辑代码类型的广告 */
            elseif ($type == 2) {
                $ad_code = "ad_code = '" . request()->input('ad_code', '') . "', ";
            }

            /* 编辑文本类型的广告 */
            if ($type == 3) {
                $ad_code = "ad_code = '" . request()->input('ad_text', '') . "', ";
            }

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 ecmoban模板堂 --zhuo */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('ad') . " AS a, " .
                $this->dsc->table('ad_position') . " AS p " .
                " WHERE a.ad_id <> '$id' AND a.ad_name ='$ad_name' AND a.position_id = p.position_id AND p.theme = '$template'";
            if ($this->db->getOne($sql) > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'ads.php?act=edit&id=' . $id];
                return sys_msg($GLOBALS['_LANG']['ad_name_exist'], 0, $link);
            }

            $ad_code = str_replace('../' . DATA_DIR . '/afficheimg/', '', $ad_code);

            $this->dscRepository->getOssAddFile([DATA_DIR . '/afficheimg/' . $ad_code]);

            /* 更新信息 */
            $sql = "UPDATE " . $this->dsc->table('ad') . " SET " .
                "position_id = '$position_id', " .
                "ad_name     = '$ad_name', " .
                "ad_link     = '$ad_link', " .
                "link_color  = '$link_color', " .
                "is_new     = '$is_new', " .
                "is_hot     = '$is_hot', " .
                "is_best     = '$is_best', " .
                $ad_code .
                "start_time  = '$start_time', " .
                "end_time    = '$end_time', " .
                "link_man    = '$link_man', " .
                "link_email  = '$link_email', " .
                "link_phone  = '$link_phone', " .
                "enabled     = '$enabled', " .
                "ad_type  = '$ad_type', " .
                "goods_name  = '$goods_name' " .
                "WHERE ad_id = '$id'";
            $this->db->query($sql);

            /* 记录管理员操作 */
            admin_log($ad_name, 'edit', 'ads');

            clear_cache_files(); // 清除模版缓存

            /* 提示信息 */
            //$href[] = array('text' => $GLOBALS['_LANG']['back_ads_list'], 'href' => 'ads.php?act=list');
            $href[] = ['text' => $GLOBALS['_LANG']['back_ads_list'], 'href' => 'ads.php?act=list' . '&pid=' . $position_id];
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $ad_name . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $href);
        }

        /*------------------------------------------------------ */
        //--生成广告的JS代码
        /*------------------------------------------------------ */
        elseif ($act == 'add_js') {
            admin_priv('ad_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['05_banner']);
            $type = request()->input('type', '');
            $id = (int)request()->input('id', 0);

            /* 编码 */
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];

            $js_code = "<script type=" . '"' . "text/javascript" . '"';
            $js_code .= ' src=' . '"' . $this->dsc->seller_url() . 'affiche.php?act=js&type=' . $type . '&ad_id=' . $id . '"' . '></script>';

            $site_url = $this->dsc->seller_url() . 'affiche.php?act=js&type=' . $type . '&ad_id=' . $id;

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_js_code']);
            $this->smarty->assign('action_link', ['href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('url', $site_url);
            $this->smarty->assign('js_code', $js_code);
            $this->smarty->assign('lang_list', $lang_list);


            $this->smarty->assign('current', 'ads');
            return $this->smarty->display('ads_js.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑广告名称
        /*------------------------------------------------------ */
        elseif ($act == 'edit_ad_name') {
            $check_auth = check_authz_json('ad_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $ad_name = json_str_iconv(trim(request()->input('val', '')));

            $template = $GLOBALS['_CFG']['template'];

            /* 查看广告名称是否有重复 ecmoban模板堂 --zhuo */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('ad') . " AS a, " .
                $this->dsc->table('ad_position') . " AS p " .
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

            $id = (int)request()->input('id', 0);
            $img = $exc->get_name($id, 'ad_code');

            $exc->drop($id);

            if ((strpos($img, 'http://') === false) && (strpos($img, 'https://') === false)) {
                $img_name = basename($img);

                $this->dscRepository->getOssDelFile([DATA_DIR . '/afficheimg/' . $img_name]);
                @unlink(storage_public('storage/' . DATA_DIR . '/afficheimg/' . $img_name));
            }

            admin_log('', 'remove', 'ads');

            $url = 'ads.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 获取分类列表 by wu
        /*------------------------------------------------------ */
        elseif ($act == 'getCatList') {
            $catId = addslashes(trim(request()->input('catId', '')));
            $catList = $this->getCatList($catId);
            return response()->json($catList);
        }
    }

    /* 获取广告数据列表 */
    private function get_adslist($ru_id)
    {
        /* 过滤查询 */
        $filter = [];

        //ecmoban模板堂 --zhuo start
        $filter['keyword'] = addslashes(trim(request()->input('keyword', '')));
        if (request()->exists('is_ajax') && request()->input('is_ajax') == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //ecmoban模板堂 --zhuo end

        $filter['adName'] = addslashes(trim(request()->input('adName', '')));
        $filter['sort_by'] = addslashes(trim(request()->input('sort_by', 'ad.ad_id')));
        $filter['sort_order'] = addslashes(trim(request()->input('sort_order', 'DESC')));
        $filter['pid'] = (int)request()->input('pid', 0);

        $where = 'WHERE 1 ';
        if (!empty($filter['pid'])) {
            $where .= " AND ad.position_id = '" . $filter['pid'] . "' ";
        }

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $where .= " AND (p.position_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'" . ")";
        }

        /* 广告名称 by wu */
        if (!empty($filter['adName'])) {
            $where .= " AND (ad.ad_name LIKE '%" . mysql_like_quote($filter['adName']) . "%'" . ")";
        }

        //ecmoban模板堂 --zhuo start
        if ($ru_id > 0) {
            $where .= " and (p.user_id = '$ru_id' or (p.is_public = 1 and ad.public_ruid = '$ru_id')) ";
        }
        //ecmoban模板堂 --zhuo end

        //模板类型
        $where .= " AND p.theme = '" . $GLOBALS['_CFG']['template'] . "'";

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = (int)request()->input('store_search', 0);
        $filter['merchant_id'] = (int)request()->input('merchant_id', 0);
        $filter['store_keyword'] = addslashes(trim(request()->input('store_keyword', '')));

        $store_where = '';
        $store_search_where = '';
        if ($filter['store_search'] != 0) {
            if ($ru_id == 0) {
                $store_type = (int)request()->input('store_type', 0);

                if ($store_type) {
                    $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                }

                if ($filter['store_search'] == 1) {
                    $where .= " AND p.user_id = '" . $filter['merchant_id'] . "' ";
                } elseif ($filter['store_search'] == 2) {
                    $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                } elseif ($filter['store_search'] == 3) {
                    $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                }

                if ($filter['store_search'] > 1) {
                    $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                        " WHERE msi.user_id = p.user_id $store_where) > 0 ";
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        /* 获得总记录数据 */
        $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('ad') . ' AS ad ' . 'LEFT JOIN ' . $this->dsc->table('ad_position') . ' AS p ON p.position_id = ad.position_id ' . $where;
        $filter['record_count'] = $this->db->getOne($sql);

        $filter = page_and_size($filter);

        /* 获得广告数据 */
        $arr = [];
        $sql = 'SELECT ad.*, COUNT(o.order_id) AS ad_stats, p.position_name, p.user_id ' .
            'FROM ' . $this->dsc->table('ad') . 'AS ad ' .
            'LEFT JOIN ' . $this->dsc->table('ad_position') . ' AS p ON p.position_id = ad.position_id ' .
            'LEFT JOIN ' . $this->dsc->table('order_info') . " AS o ON o.from_ad = ad.ad_id $where " .
            'GROUP BY ad.ad_id ' .
            'ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];

        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);
        $idx = 0;
        foreach ($res as $rows) {

            /* 广告类型的名称 */
            $rows['type'] = ($rows['media_type'] == 0) ? $GLOBALS['_LANG']['ad_img'] : '';
            $rows['type'] .= ($rows['media_type'] == 1) ? $GLOBALS['_LANG']['ad_flash'] : '';
            $rows['type'] .= ($rows['media_type'] == 2) ? $GLOBALS['_LANG']['ad_html'] : '';
            $rows['type'] .= ($rows['media_type'] == 3) ? $GLOBALS['_LANG']['ad_text'] : '';

            /* 格式化日期 */
            $rows['start_date'] = local_date($GLOBALS['_CFG']['time_format'], $rows['start_time']);
            $rows['end_date'] = local_date($GLOBALS['_CFG']['time_format'], $rows['end_time']);

            if ($rows['public_ruid'] == 0) {
                $user_id = $rows['user_id'];
            } else {
                $user_id = $rows['public_ruid'];
            }

            $rows['user_name'] = $this->merchantCommonService->getShopName($user_id, 1); //ecmoban模板堂 --zhuo

            $rows['ad_code'] = get_image_path(DATA_DIR . '/afficheimg/' . $rows['ad_code']);

            $arr[$idx] = $rows;

            $idx++;
        }

        return ['ads' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    //获取广告位模型信息 by wu
    private function get_ad_model($pid)
    {
        //初始数组
        $ad_arr = [
            'ad_type' => 0,
            'ad_model_init' => '',
            'ad_model' => '',
            'ad_model_structure' => '',
            'cat_id' => ''
        ];

        //模型片段
        $init_model = ['[num_id]', '[cat_id]'];

        //广告位信息
        $sql = " select * from " . $this->dsc->table('ad_position') . " where position_id='" . $pid . "' limit 1";
        $position_info = $this->db->getRow($sql);

        if (!empty($position_info['position_model'])) {
            //$ad_arr['ad_type']=1;

            //初始广告位模型($ad_model)和模型结构($ad_model_structure)
            $ad_model = $position_info['position_model'];
            $ad_model_structure = [];
            $i = 0;
            foreach ($init_model as $model) {
                if (strpos($ad_model, $model)) {
                    if ($model == '[num_id]') {
                        $ad_arr['ad_type'] = 1;
                    }
                    if ($model == '[cat_id]') {
                        $ad_arr['ad_type'] = 2;
                    }
                    //去除[]符号
                    $ad_model_structure[$i] = str_replace(['[', ']'], ['', ''], $model);
                    $i++;
                    $ad_model = str_replace(['_' . $model . '_', '_' . $model, $model . '_', $model], ['', '', '', ''], $ad_model);
                }
            }

            if ($ad_arr['ad_type'] > 0) {
                //赋值数组
                $ad_arr['ad_model_init'] = $position_info['position_model'];
                $ad_arr['ad_model'] = $ad_model;
                $ad_arr['ad_model_structure'] = $init_model;
            }

            if (in_array('cat_id', $ad_model_structure) && in_array('num_id', $ad_model_structure)) {
                $ad_arr['ad_type'] = 3;

                //搜索已添加广告
                $sql = " select ad_name from " . $this->dsc->table('ad') . " where ad_name like '%" . $ad_model . "%'";
                $ad_exist = $this->db->getAll($sql);

                if (!empty($ad_exist)) {
                    $ad_arr['ad_type'] = 4;

                    //处理已存在广告(模型片段)
                    $ad_all = [];
                    foreach ($ad_exist as $key => $val) {
                        $ad_deal = explode('_', str_replace($ad_model, '', $val['ad_name']));
                        for ($j = 0; $j < count($ad_model_structure); $j++) {
                            $ad_all[$key][$ad_model_structure[$j]] = $ad_deal[$j];
                        }
                    }

                    //合并分类下的广告
                    foreach ($ad_all as $key => $val) {
                        $ad_arr['cat_id'][$val['cat_id']]['num_id'][] = $val['num_id'];
                    }
                    foreach ($ad_arr['cat_id'] as $key => $val) {
                        //获取下一个即将添加的num_id
                        $ad_arr['cat_id'][$key]['next'] = null;
                        for ($p = 1; $p < 9999; $p++) {
                            if (!in_array($p, $val['num_id'])) {
                                $ad_arr['cat_id'][$key]['next'] = $p;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $ad_arr;
    }

    private function getCatList($catId = 0)
    {
        $where = ' where 1 ';

        if (empty($catId)) {
            $where .= ' and parent_id=0 ';
        } else {
            $where .= ' and parent_id= ' . $catId;
        }

        $sql = " select cat_id,cat_name from " . $this->dsc->table('category') . $where;
        $catList = $this->db->getAll($sql);

        return $catList;
    }
}
