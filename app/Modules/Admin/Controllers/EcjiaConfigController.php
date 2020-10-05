<?php

namespace App\Modules\Admin\Controllers;

use App\Entities\Article;
use App\Libraries\Image;
use App\Models\Ad;
use App\Models\BonusType;
use App\Models\Region;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\ECJia\EcjiaConfigManageService;

/**
 * 应用配置
 */
class EcjiaConfigController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;
    protected $ecjiaConfigManageService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        EcjiaConfigManageService $ecjiaConfigManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->ecjiaConfigManageService = $ecjiaConfigManageService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        /*------------------------------------------------------ */
        //-- �        �置列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 取得过滤条件 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_ecjia_app_config']);

            // 基本信息
            $this->smarty->assign('shop_app_icon', $this->ecjiaConfigManageService->ecjiaConfig('shop_app_icon'));
            $this->smarty->assign('shop_app_description', $this->ecjiaConfigManageService->ecjiaConfig('shop_app_description')); // 移动应用简介
            $this->smarty->assign('bonus_readme_url', $this->ecjiaConfigManageService->ecjiaConfig('bonus_readme_url')); // 红包使用说明
            $this->smarty->assign('mobile_feedback_autoreply', $this->ecjiaConfigManageService->ecjiaConfig('mobile_feedback_autoreply')); // 咨询默认回复设置
            $this->smarty->assign('mobile_shopkeeper_urlscheme', $this->ecjiaConfigManageService->ecjiaConfig('mobile_shopkeeper_urlscheme')); // 掌柜UrlScheme设置
            $this->smarty->assign('shop_pc_url', $this->ecjiaConfigManageService->ecjiaConfig('shop_pc_url')); // PC商城地址
            $this->smarty->assign('mobile_share_link', $this->ecjiaConfigManageService->ecjiaConfig('mobile_share_link')); // 分享链接
            // 新人有礼红包
            $time = gmtime();
            $res = BonusType::where('use_start_date', '<', $time)->where('use_end_date', '>', $time);
            $bonus_list = $this->baseRepository->getToArrayGet($res);

            $bonus_select = '';
            foreach ($bonus_list as $key => $value) {
                $bonus_select .= '<li><a href="javascript:;" data-value="' . $value['type_id'] . '" class="ftx-01">' . $value['type_name'] . '</a></li>';
            }
            $this->smarty->assign('bonus_select', $bonus_select);

            $bonus_id = $this->ecjiaConfigManageService->ecjiaConfig('mobile_signup_reward');

            $this->smarty->assign('mobile_signup_reward', $bonus_id);// 新人有礼红包

            $this->smarty->assign('mobile_signup_reward_notice', $this->ecjiaConfigManageService->ecjiaConfig('mobile_signup_reward_notice')); // 新人有礼说明

            // APP下载地址
            $this->smarty->assign('mobile_iphone_qr_code', $this->ecjiaConfigManageService->ecjiaConfig('mobile_iphone_qr_code')); // iPhone下载二维码
            $this->smarty->assign('shop_iphone_download', $this->ecjiaConfigManageService->ecjiaConfig('shop_iphone_download')); // iPhone下载地址
            $this->smarty->assign('mobile_android_qr_code', $this->ecjiaConfigManageService->ecjiaConfig('mobile_android_qr_code')); // Android下载二维码
            $this->smarty->assign('shop_android_download', $this->ecjiaConfigManageService->ecjiaConfig('shop_android_download')); // Android下载地址
            $this->smarty->assign('mobile_ipad_qr_code', $this->ecjiaConfigManageService->ecjiaConfig('mobile_ipad_qr_code')); // iPad下载二维码
            $this->smarty->assign('shop_ipad_download', $this->ecjiaConfigManageService->ecjiaConfig('shop_ipad_download')); // iPad下载地址

            // 移动广告位设置
            // 移动启动页广告图
            $res = Ad::where('start_time', '<', $time)->where('end_time', '>', $time);
            $ad_list = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('ad_list', $ad_list);

            $mobile_launch_select = '';
            foreach ($ad_list as $key => $value) {
                $mobile_launch_select .= '<li><a href="javascript:;" data-value="' . $value['ad_id'] . '" class="ftx-01">' . $value['ad_name'] . '</a></li>';
            }
            $this->smarty->assign('mobile_launch_select', $mobile_launch_select);

            $launch_ad_id = $this->ecjiaConfigManageService->ecjiaConfig('mobile_launch_adsense');
            $launch_ad_name = Ad::where('ad_id', $launch_ad_id)->value('ad_name');
            $launch_ad_name = $launch_ad_name ? $launch_ad_name : '';

            $this->smarty->assign('launch_ad_name', $launch_ad_name);
            $this->smarty->assign('launch_ad_id', $launch_ad_id); // 移动启动页广告图

            // 移动首页广告组
            $ads_id = $this->ecjiaConfigManageService->ecjiaConfig('mobile_home_adsense_group');
            $ad_res = Ad::whereRaw(1);
            if ($ads_id) {
                $ads_id = $this->baseRepository->getExplode($ads_id);
                $ad_res = $ad_res->whereIn('ad_id', $ads_id);
            } else {
                $ad_res = $ad_res->where('ad_id', 0);
            }
            $mobile_home_adsense_group = $this->baseRepository->getToArrayGet($ad_res);

            $this->smarty->assign('mobile_home_adsense_group', $mobile_home_adsense_group); // 移动首页广告组

            // 已选择的热门城市
            $regions_id = $this->ecjiaConfigManageService->ecjiaConfig('mobile_recommend_city');

            $res = Region::whereRaw(1);
            if ($regions_id) {
                $regions_id = $this->baseRepository->getExplode($regions_id);
                $res = $res->whereIn('region_id', $regions_id);
            } else {
                $res = $res->where('region_id', 0);
            }

            $regions = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('regions', $regions); // 已选择的热门城市

            // 首页主题类设置
            $mobile_topic_select = '';
            foreach ($ad_list as $key => $value) {
                $mobile_topic_select .= '<li><a href="javascript:;" data-value="' . $value['ad_id'] . '" class="ftx-01">' . $value['ad_name'] . '</a></li>';
            }
            $this->smarty->assign('mobile_topic_select', $mobile_topic_select);

            $topic_ad_id = $this->ecjiaConfigManageService->ecjiaConfig('mobile_topic_adsense');
            $topic_ad_name = Ad::where('ad_id', $topic_ad_id)->value('ad_name');
            $topic_ad_name = $topic_ad_name ? $topic_ad_name : '';

            $this->smarty->assign('topic_ad_name', $topic_ad_name);
            $this->smarty->assign('topic_ad_id', $topic_ad_id); // 首页主题类设置


            // 登录页色值设置
            $this->smarty->assign('mobile_phone_login_fgcolor', $this->ecjiaConfigManageService->ecjiaConfig('mobile_phone_login_fgcolor')); // 手机端登录页前景色
            $this->smarty->assign('mobile_phone_login_bgcolor', $this->ecjiaConfigManageService->ecjiaConfig('mobile_phone_login_bgcolor')); // 手机端登录页背景色
            $this->smarty->assign('mobile_phone_login_bgimage', $this->ecjiaConfigManageService->ecjiaConfig('mobile_phone_login_bgimage')); // 手机端登录页背景图片
            $this->smarty->assign('mobile_pad_login_fgcolor', $this->ecjiaConfigManageService->ecjiaConfig('mobile_pad_login_fgcolor')); // Pad登录页前景色
            $this->smarty->assign('mobile_pad_login_bgcolor', $this->ecjiaConfigManageService->ecjiaConfig('mobile_pad_login_bgcolor')); // Pad登录页背景色
            $this->smarty->assign('mobile_pad_login_bgimage', $this->ecjiaConfigManageService->ecjiaConfig('mobile_pad_login_bgimage')); // Pad登录页背景图片

            // 热门城市设置
            $this->smarty->assign('mobile_recommend_city', $this->ecjiaConfigManageService->ecjiaConfig('mobile_recommend_city')); // 已选择的热门城市 6

            $this->smarty->assign('form_action', 'update');

            return $this->smarty->display('ecjia_config.dwt');
        }

        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('article_manage');
            /* 移动应用 Logo 图片 */
            if ((isset($_FILES['shop_app_icon']['error']) && $_FILES['shop_app_icon']['error'] == 0) || (!isset($_FILES['shop_app_icon']['error']) && isset($_FILES['shop_app_icon']['tmp_name']) && $_FILES['shop_app_icon']['tmp_name'] != 'none')) {
                $img_up_info = basename($image->upload_image($_FILES['shop_app_icon'], 'assets/ecmoban_sc'));

                $code = $this->ecjiaConfigManageService->ecjiaConfig('shop_app_icon');

                if ($code && $code != DATA_DIR . '/assets/ecmoban_sc/' . $img_up_info) {
                    @unlink('../' . $code);
                }

                $this->dscRepository->getOssAddFile([DATA_DIR . '/assets/ecmoban_sc/' . $img_up_info]);

                $shop_app_icon_img_src = DATA_DIR . '/assets/ecmoban_sc/' . $img_up_info;
            }

            /* iPhone下载二维码 图片 */
            if ((isset($_FILES['mobile_iphone_qr_code']['error']) && $_FILES['mobile_iphone_qr_code']['error'] == 0) || (!isset($_FILES['mobile_iphone_qr_code']['error']) && isset($_FILES['mobile_iphone_qr_code']['tmp_name']) && $_FILES['mobile_iphone_qr_code']['tmp_name'] != 'none')) {
                $img_up_info = basename($image->upload_image($_FILES['mobile_iphone_qr_code'], 'assets'));

                $code = $this->ecjiaConfigManageService->ecjiaConfig('mobile_iphone_qr_code');

                if ($code && $code != DATA_DIR . '/assets/' . $img_up_info) {
                    @unlink('../' . $code);
                }

                $this->dscRepository->getOssAddFile([DATA_DIR . '/assets/' . $img_up_info]);

                $mobile_iphone_qr_code_img_src = DATA_DIR . '/assets/' . $img_up_info;
            }
            /* Android下载二维码 图片 */
            if ((isset($_FILES['mobile_android_qr_code']['error']) && $_FILES['mobile_android_qr_code']['error'] == 0) || (!isset($_FILES['mobile_android_qr_code']['error']) && isset($_FILES['mobile_android_qr_code']['tmp_name']) && $_FILES['mobile_android_qr_code']['tmp_name'] != 'none')) {
                $img_up_info = basename($image->upload_image($_FILES['mobile_android_qr_code'], 'assets'));

                $code = $this->ecjiaConfigManageService->ecjiaConfig('mobile_android_qr_code');

                if ($code && $code != DATA_DIR . '/assets/' . $img_up_info) {
                    @unlink('../' . $code);
                }

                $this->dscRepository->getOssAddFile([DATA_DIR . '/assets/' . $img_up_info]);

                $mobile_android_qr_code_img_src = DATA_DIR . '/assets/' . $img_up_info;
            }
            /* iPad下载二维码 图片 */
            if ((isset($_FILES['mobile_ipad_qr_code']['error']) && $_FILES['mobile_ipad_qr_code']['error'] == 0) || (!isset($_FILES['mobile_ipad_qr_code']['error']) && isset($_FILES['mobile_ipad_qr_code']['tmp_name']) && $_FILES['mobile_ipad_qr_code']['tmp_name'] != 'none')) {
                $img_up_info = basename($image->upload_image($_FILES['mobile_ipad_qr_code'], 'assets'));

                $code = $this->ecjiaConfigManageService->ecjiaConfig('mobile_ipad_qr_code');

                if ($code && $code != DATA_DIR . '/assets/' . $img_up_info) {
                    @unlink('../' . $code);
                }

                $this->dscRepository->getOssAddFile([DATA_DIR . '/assets/' . $img_up_info]);

                $mobile_ipad_qr_code_img_src = DATA_DIR . '/assets/' . $img_up_info;
            }

            /* 手机端登录页背景图片 图片 */
            if ((isset($_FILES['mobile_phone_login_bgimage']['error']) && $_FILES['mobile_phone_login_bgimage']['error'] == 0) || (!isset($_FILES['mobile_phone_login_bgimage']['error']) && isset($_FILES['mobile_phone_login_bgimage']['tmp_name']) && $_FILES['mobile_phone_login_bgimage']['tmp_name'] != 'none')) {
                $img_up_info = basename($image->upload_image($_FILES['mobile_phone_login_bgimage'], 'assets'));

                $code = $this->ecjiaConfigManageService->ecjiaConfig('mobile_phone_login_bgimage');

                if ($code && $code != DATA_DIR . '/assets/' . $img_up_info) {
                    @unlink('../' . $code);
                }

                $this->dscRepository->getOssAddFile([DATA_DIR . '/assets/' . $img_up_info]);

                $mobile_phone_login_bgimage_img_src = DATA_DIR . '/assets/' . $img_up_info;
            }

            /* Pad登录页背景图片 图片 */
            if ((isset($_FILES['mobile_pad_login_bgimage']['error']) && $_FILES['mobile_pad_login_bgimage']['error'] == 0) || (!isset($_FILES['mobile_pad_login_bgimage']['error']) && isset($_FILES['mobile_pad_login_bgimage']['tmp_name']) && $_FILES['mobile_pad_login_bgimage']['tmp_name'] != 'none')) {
                $img_up_info = basename($image->upload_image($_FILES['mobile_pad_login_bgimage'], 'assets'));

                $code = $this->ecjiaConfigManageService->ecjiaConfig('mobile_pad_login_bgimage');

                if ($code && $code != DATA_DIR . '/assets/' . $img_up_info) {
                    @unlink('../' . $code);
                }

                $this->dscRepository->getOssAddFile([DATA_DIR . '/assets/' . $img_up_info]);

                $mobile_pad_login_bgimage_img_src = DATA_DIR . '/assets/' . $img_up_info;
            }

            $shop_app_icon_textfile = isset($_POST['shop_app_icon_textfile']) ? trim($_POST['shop_app_icon_textfile']) : '';
            $mobile_iphone_qr_code_textfile = isset($_POST['mobile_iphone_qr_code_textfile']) ? trim($_POST['mobile_iphone_qr_code_textfile']) : '';
            $mobile_android_qr_code_textfile = isset($_POST['mobile_android_qr_code_textfile']) ? trim($_POST['mobile_android_qr_code_textfile']) : '';
            $mobile_ipad_qr_code_textfile = isset($_POST['mobile_ipad_qr_code_textfile']) ? trim($_POST['mobile_ipad_qr_code_textfile']) : '';
            $mobile_phone_login_bgimage_textfile = isset($_POST['mobile_phone_login_bgimage_textfile']) ? trim($_POST['mobile_phone_login_bgimage_textfile']) : '';
            $mobile_pad_login_bgimage_textfile = isset($_POST['mobile_pad_login_bgimage_textfile']) ? trim($_POST['mobile_pad_login_bgimage_textfile']) : '';

            $shop_app_icon = !empty($shop_app_icon_img_src) ? $shop_app_icon_img_src : $shop_app_icon_textfile;
            $mobile_iphone_qr_code = !empty($mobile_iphone_qr_code_img_src) ? $mobile_iphone_qr_code_img_src : $mobile_iphone_qr_code_textfile;
            $mobile_android_qr_code = !empty($mobile_android_qr_code_img_src) ? $mobile_android_qr_code_img_src : $mobile_android_qr_code_textfile;
            $mobile_ipad_qr_code = !empty($mobile_ipad_qr_code_img_src) ? $mobile_ipad_qr_code_img_src : $mobile_ipad_qr_code_textfile;
            $mobile_phone_login_bgimage = !empty($mobile_phone_login_bgimage_img_src) ? $mobile_phone_login_bgimage_img_src : $mobile_phone_login_bgimage_textfile;
            $mobile_pad_login_bgimage = !empty($mobile_pad_login_bgimage_img_src) ? $mobile_pad_login_bgimage_img_src : $mobile_pad_login_bgimage_textfile;

            $shop_app_description = !empty($_POST['shop_app_description']) ? trim($_POST['shop_app_description']) : '';
            $bonus_readme_url = !empty($_POST['bonus_readme_url']) ? trim($_POST['bonus_readme_url']) : '';
            $mobile_feedback_autoreply = !empty($_POST['mobile_feedback_autoreply']) ? trim($_POST['mobile_feedback_autoreply']) : '';
            $mobile_shopkeeper_urlscheme = !empty($_POST['mobile_shopkeeper_urlscheme']) ? trim($_POST['mobile_shopkeeper_urlscheme']) : '';
            $shop_pc_url = !empty($_POST['shop_pc_url']) ? trim($_POST['shop_pc_url']) : '';
            $mobile_share_link = !empty($_POST['mobile_share_link']) ? trim($_POST['mobile_share_link']) : '';
            $mobile_signup_reward = !empty($_POST['mobile_signup_reward']) ? trim($_POST['mobile_signup_reward']) : '';
            $mobile_signup_reward_notice = !empty($_POST['mobile_signup_reward_notice']) ? trim($_POST['mobile_signup_reward_notice']) : '';
            $shop_iphone_download = !empty($_POST['shop_iphone_download']) ? trim($_POST['shop_iphone_download']) : '';
            $shop_android_download = !empty($_POST['shop_android_download']) ? trim($_POST['shop_android_download']) : '';
            $shop_ipad_download = !empty($_POST['shop_ipad_download']) ? trim($_POST['shop_ipad_download']) : '';

            $mobile_launch_adsense = !empty($_POST['mobile_launch_adsense']) ? trim($_POST['mobile_launch_adsense']) : '';
            $mobile_home_adsense_group = !empty($_POST['mobile_home_adsense_group']) ? trim(implode(',', $_POST['mobile_home_adsense_group'])) : '';
            $mobile_topic_adsense = !empty($_POST['mobile_topic_adsense']) ? trim($_POST['mobile_topic_adsense']) : '';

            $mobile_phone_login_fgcolor = !empty($_POST['mobile_phone_login_fgcolor']) ? trim($_POST['mobile_phone_login_fgcolor']) : '';
            $mobile_phone_login_bgcolor = !empty($_POST['mobile_phone_login_bgcolor']) ? trim($_POST['mobile_phone_login_bgcolor']) : '';

            $mobile_pad_login_fgcolor = !empty($_POST['mobile_pad_login_fgcolor']) ? trim($_POST['mobile_pad_login_fgcolor']) : '';
            $mobile_pad_login_bgcolor = !empty($_POST['mobile_pad_login_bgcolor']) ? trim($_POST['mobile_pad_login_bgcolor']) : '';

            $mobile_recommend_city = !empty($_POST['regions']) ? trim(implode(',', $_POST['regions'])) : '';


            // 基本信息设置
            $this->ecjiaConfigManageService->updateConfig('shop_app_icon', $shop_app_icon); // 移动应用 Logo
            $this->ecjiaConfigManageService->updateConfig('shop_app_description', $shop_app_description); // 移动应用简介
            $this->ecjiaConfigManageService->updateConfig('bonus_readme_url', '/index.php?m=article&c=mobile&a=info&id=' . $bonus_readme_url); // 红包使用说明
            $this->ecjiaConfigManageService->updateConfig('mobile_feedback_autoreply', $mobile_feedback_autoreply); // 咨询默认回复设置
            $this->ecjiaConfigManageService->updateConfig('mobile_shopkeeper_urlscheme', $mobile_shopkeeper_urlscheme); // 掌柜UrlScheme设置
            $this->ecjiaConfigManageService->updateConfig('shop_pc_url', $shop_pc_url); // PC商城地址
            $this->ecjiaConfigManageService->updateConfig('mobile_share_link', $mobile_share_link); // 分享链接
            $this->ecjiaConfigManageService->updateConfig('mobile_signup_reward', $mobile_signup_reward); // 新人有礼红包
            $this->ecjiaConfigManageService->updateConfig('mobile_signup_reward_notice', $mobile_signup_reward_notice); // 新人有礼说明

            // 是否开启微商城（不知道code值）
            // 微商城 Logo（不知道code值）
            // 微商城地址 （不知道code值）

            // APP下载地址
            $this->ecjiaConfigManageService->updateConfig('mobile_iphone_qr_code', $mobile_iphone_qr_code); // iPhone下载二维码
            $this->ecjiaConfigManageService->updateConfig('shop_iphone_download', $shop_iphone_download); // iPhone下载地址
            $this->ecjiaConfigManageService->updateConfig('mobile_android_qr_code', $mobile_android_qr_code); // Android下载二维码
            $this->ecjiaConfigManageService->updateConfig('shop_android_download', $shop_android_download); // Android下载地址
            $this->ecjiaConfigManageService->updateConfig('mobile_ipad_qr_code', $mobile_ipad_qr_code);// iPad下载二维码
            $this->ecjiaConfigManageService->updateConfig('shop_ipad_download', $shop_ipad_download);// iPad下载地址

            // 移动广告位设置
            $this->ecjiaConfigManageService->updateConfig('mobile_launch_adsense', $mobile_launch_adsense);// 移动启动页广告图
            $this->ecjiaConfigManageService->updateConfig('mobile_home_adsense_group', $mobile_home_adsense_group);// 移动首页广告组
            $this->ecjiaConfigManageService->updateConfig('mobile_topic_adsense', $mobile_topic_adsense);// 首页主题类设置

            // 登录页色值设置
            $this->ecjiaConfigManageService->updateConfig('mobile_phone_login_fgcolor', $mobile_phone_login_fgcolor);// 手机端登录页前景色
            $this->ecjiaConfigManageService->updateConfig('mobile_phone_login_bgcolor', $mobile_phone_login_bgcolor);// 手机端登录页背景色
            $this->ecjiaConfigManageService->updateConfig('mobile_phone_login_bgimage', $mobile_phone_login_bgimage);// 手机端登录页背景图片
            $this->ecjiaConfigManageService->updateConfig('mobile_pad_login_fgcolor', $mobile_pad_login_fgcolor);// Pad登录页前景色
            $this->ecjiaConfigManageService->updateConfig('mobile_pad_login_bgcolor', $mobile_pad_login_bgcolor);// Pad登录页背景色
            $this->ecjiaConfigManageService->updateConfig('mobile_pad_login_bgimage', $mobile_pad_login_bgimage);// Pad登录页背景图片

            // 热门城市设置
            $this->ecjiaConfigManageService->updateConfig('mobile_recommend_city', $mobile_recommend_city);// 已选择的热门城市


            clear_cache_files(); // 清除缓存文件

            /* 提示信息 */

            $link[0]['text'] = $GLOBALS['_LANG']['back'];
            $link[0]['href'] = 'ecjia_config.php?act=list';
            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
        } // 红包使用说明 文章搜索
        elseif ($_REQUEST['act'] == 'search_article') {
            $result = ['error' => 0, 'msg' => '', 'content' => ''];

            $title = !empty($_REQUEST['article_keywords']) ? trim($_REQUEST['article_keywords']) : '';

            $res = Article::whereRaw(1);
            if ($title) {
                $res = $res->where('title', 'LIKE', '%' . $title . '%');
            }
            $res = $this->baseRepository->getToArrayGet($res);

            $article_str = '<div class="cite">' . $GLOBALS['_LANG']['please_select'] . '</div>
            <ul class="ps-container" style="display: none;">';

            foreach ($res as $key => $value) {
                $article_str .= '<li><a href="javascript:;" data-value="' . $value['article_id'] . '" class="ftx-01">' . $value['title'] . '</a></li>';
            }

            $article_str .= '</ul>
            <input name="bonus_readme_url" type="hidden" value="' . $this->ecjiaConfigManageService->ecjiaConfig('$bonus_readme_url') . '" id="bonus_readme_url">';

            $result['content'] = $article_str;
            return response()->json($result);
        }
    }
}
