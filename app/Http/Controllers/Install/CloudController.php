<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Install\Helpers\InitController;
use App\Http\Controllers\Install\Helpers\Template;
use App\Libraries\Http;

/**
 * 安装界面
 */
class CloudController extends InitController
{
    protected $template;

    public function __construct(
        Template $template
    )
    {
        $this->template = $template;
    }

    public function index()
    {
        $this->template->path = app_path('Http/Controllers/Install/Views/');

        defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', base_path()) . '/');
        define('VIEWS_PATH', str_replace('\\', '/', app_path('Http/Controllers/Install/Views')) . '/');

        $data['api_ver'] = '1.0';
        $data['version'] = VERSION;
        $data['charset'] = strtoupper(EC_CHARSET);
        $sc_charset = $data['charset'];

        if (session()->has('sc_lang')) {
            $data['sc_lang'] = session()->get('sc_lang');
        } else {
            $data['sc_lang'] = 'zh_cn';
        }

        $data['release'] = RELEASE;
        $step = isset($_REQUEST['step']) ? trim($_REQUEST['step']) : '';

        $step_arr = array('welcome', 'check', 'setting_ui', 'done', 'active', 'send_code', 'right_ad', 'update_mend', 'check_code');

        if (!in_array($step, $step_arr)) {
            @header('Location: index.php');
        }

        $apiget = "&step= $step &sc_lang= $data[sc_lang] &release= $data[release] &version= $data[version]&charset= $data[charset] &api_ver= $data[api_ver]";

        if (session()->has($step)) {
            $sessValue = session()->get($step);

            foreach ($sessValue as $k => $v) {
                $this->template->assign($k, $v);
                $GLOBALS[$k] = $v;
            }
        }

        $installer_lang_package_path = app_path('Http/Controllers/Install/lang/' . $data['sc_lang'] . '.php');

        if (file_exists($installer_lang_package_path)) {
            $lang = include_once($installer_lang_package_path);
            $this->template->assign('lang', $lang);
        }

        if ($step == 'welcome') {
            $content = $this->api_request('install_agree', $apiget);

            if ($content['error'] == 0 && !empty($content)) {
                return $content['content'];
            } else {
                return $this->template->display($data['sc_lang'] . '_welcome_content.php');
            }
        } elseif ($step == 'right_ad') {
            $content = $this->api_request('install_ad', $apiget);

            if ($content['error'] == 0 && !empty($content)) {
                return $content['content'];
            } else {
                $this->template->display($data['sc_lang'] . '_right_ad.php');
            }
        } elseif ($step == 'update_mend') {
            $apiget = "&version=" . $data['version'];
            $content = $this->api_request('update_mend', $apiget);

            if ($content['error'] == 0 && !empty($content)) {
                return $content['content'];
            } else {
                return '';
            }
        }

        /*------------------------------------------------------ */
        //-- 提交
        /*------------------------------------------------------ */
        elseif ($step == 'check') {
            $this->template->display('checking_content.php');
        } elseif ($step == 'setting_ui') {
            $path = public_path('assets/install/data/demo_zh_cn.sql');
            if (is_file($path)) {
                $is_path = 1;
            } else {
                $is_path = 0;
            }
            $this->template->assign('is_path', $is_path);

            $this->template->display('setting_content.php');
        } elseif ($step == 'done') {
            $this->template->display('done_content.php');
        } elseif ($step == 'active') {
            $this->template->display('active_content.php');
        } elseif ($step == 'check_code') {//检测短信验证码
            $mobile = !empty($_POST['mobile']) ? trim($_POST['mobile']) : '';
            $code = !empty($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';
            $mac = new cls_ecmac(PHP_OS);
            $apiget = "&mobile=$mobile&macaddr=$mac&code=$code&type=1";

            $content = $this->api_request('check_code', $apiget);

            if ($content) {
                return json_encode($content);
            } else {
                return false;
            }
        }

        /*------------------------------------------------------ */
        //-- 发送短信验证码
        /*------------------------------------------------------ */
        elseif ($step == 'send_code') {
            $mobile = !empty($_POST['mobile']) ? trim($_POST['mobile']) : '';

            $mac = new cls_ecmac(PHP_OS);

            session([
                'install_mobile' => $mobile,
                'install_macaddr' => $mac->mac_addr
            ]);

            $apiget = "&mobile=$mobile&macaddr=$mac&type=1";
            $content = $this->api_request('send_code', $apiget);

            if ($content) {
                return json_encode($content);
            } else {
                return 'false';
            }
        }
    }

    private function api_request($url, $apiget)
    {
        global $sc_charset;

        $install_api = 'http://dsc.ecmoban.com/sc_admin.php?c=Api&a=';
        $api_comment = app(Http::class)->doGet($install_api . $url . $apiget);

        $api_str = substr($api_comment, 3);
        $api_arr = dsc_decode($api_str, true);

        if (!empty($api_arr)) {
            if ($sc_charset != 'UTF-8') {
                $api_arr['content'] = sc_iconv('UTF-8', $sc_charset, $api_arr['content']);
            }
            return $api_arr;
        } else {
            return false;
        }
    }
}
