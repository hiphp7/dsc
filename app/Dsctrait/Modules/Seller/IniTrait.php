<?php

namespace App\Dsctrait\Modules\Seller;

use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use App\Libraries\Template;
use App\Libraries\Transport;
use App\Models\AdminUser;
use App\Models\MerchantsShopInformation;
use App\Models\SellerDomain;
use App\Models\ShopConfig;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;
use Illuminate\Support\Str;

/**
 * 管理中心公用文件
 */
define('ECS_SELLER', true);

trait IniTrait
{
    protected function initialize()
    {
        $php_self = Str::snake(basename($this->getCurrentControllerName(), 'Controller'));
        defined('PHP_SELF') or define('PHP_SELF', '/' . SELLER_PATH . '/' . $php_self . '.php');

        $_GET = request()->query() + request()->route()->parameters();
        $_POST = request()->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

        load_helper(['time', 'base', 'common', 'main', 'scws', 'ecmoban', 'function', 'publicfunc', 'commission']);

        load_helper(['main'], 'seller');

        /* 对用户传入的变量进行转义操作。*/
        if (!empty($_GET)) {
            $_GET = addslashes_deep($_GET);
        }
        if (!empty($_POST)) {
            $_POST = addslashes_deep($_POST);
        }

        $_REQUEST = addslashes_deep($_REQUEST);

        /* 创建 SHOP 对象 */
        $this->dsc = $GLOBALS['dsc'] = new Shop();
        define('DATA_DIR', $this->dsc->data_dir());
        define('IMAGE_DIR', $this->dsc->image_dir());

        /* 初始化数据库类 */
        $this->db = $GLOBALS['db'] = new Mysql();

        /* 创建错误处理对象 */
        $this->err = $GLOBALS['err'] = new Error();

        $config = cache('shop_config');
        $config = !is_null($config) ? $config : false;
        if ($config === false) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        /* 载入系统参数 */
        $GLOBALS['_CFG'] = $config;
        config(['shop' => $GLOBALS['_CFG']]);

        /* 初始化session */
        app(SessionRepository::class)->sessionRepy('ECSCP_SELLER_ID');

        /* 初始化 action */
        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'login' || $_REQUEST['act'] == 'logout' || $_REQUEST['act'] == 'signin') && strpos(PHP_SELF, 'privilege.php') === false) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'forget_pwd' || $_REQUEST['act'] == 'reset_pwd' || $_REQUEST['act'] == 'get_pwd') && strpos(PHP_SELF, 'get_password.php') === false) {
            $_REQUEST['act'] = '';
        }

        config('shop.editing_tools', 'seller_ueditor'); //修改编辑器目录 by wu

        load_lang(['common', 'common_merchants', 'log_action', basename(PHP_SELF, '.php')], 'seller');

        clearstatcache();

        /* 如果有新版本，升级 */
        if (!isset($GLOBALS['_CFG']['dsc_version'])) {
            $GLOBALS['_CFG']['dsc_version'] = 'v1.0';
        }

        define('__ROOT__', url('/') . '/');
        define('__PUBLIC__', asset('/assets'));
        define('__TPL__', asset('/assets/seller'));
        define('__STORAGE__', __ROOT__ . "storage");

        /* 创建 Smarty 对象。 */
        $this->smarty = $GLOBALS['smarty'] = new Template();

        $template_dir = app_path('Modules/' . Str::studly(SELLER_PATH)) . '/Views';
        $this->smarty->template_dir = $template_dir;
        $this->smarty->compile_dir = storage_path('framework/temp/compiled/' . SELLER_PATH);
        if (config('app.debug')) {
            $this->smarty->force_compile = true;
        }

        $this->smarty->assign('lang', $GLOBALS['_LANG']);
        $this->smarty->assign('help_open', $GLOBALS['_CFG']['help_open']);

        if (isset($GLOBALS['_CFG']['enable_order_check'])) {  // 为了从旧版本顺利升级到2.5.0
            $this->smarty->assign('enable_order_check', $GLOBALS['_CFG']['enable_order_check']);
        } else {
            $this->smarty->assign('enable_order_check', 0);
        }

        /* 验证通行证信息 */
        if (isset($_GET['ent_id']) && isset($_GET['ent_ac']) && isset($_GET['ent_sign']) && isset($_GET['ent_email'])) {
            $ent_id = addslashes(trim($_GET['ent_id']));
            $ent_ac = addslashes(trim($_GET['ent_ac']));
            $ent_sign = addslashes(trim($_GET['ent_sign']));
            $ent_email = addslashes(trim($_GET['ent_email']));
            $certificate_id = addslashes(trim($GLOBALS['_CFG']['certificate_id']));
            $domain_url = $this->dsc->url();
            $token = addslashes($_GET['token']);
            if ($token == md5(md5($GLOBALS['_CFG']['token']) . $domain_url . SELLER_PATH)) {
                $t = new Transport('-1', 5);
                $apiget = "act=ent_sign&ent_id=$ent_id&certificate_id=$certificate_id";

                $t->request('', $apiget);

                $configArr = [
                    'ent_id' => $ent_id,
                    'ent_ac' => $ent_ac,
                    'ent_sign' => $ent_sign,
                    'ent_email' => $ent_email,
                ];

                foreach ($configArr as $k => $v) {
                    ShopConfig::where('code', $k)
                        ->update([
                            'value' => $v
                        ]);
                }

                clear_cache_files();
                return dsc_header("Location: index.php\n")->send();
            }
        }

        /* 验证管理员身份 */
        if ((!session()->has('seller_id') || intval(session('seller_id')) <= 0) &&
            $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'check_user_name' && $_REQUEST['act'] != 'check_user_password' && //by wu
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order'
        ) {
            if (!empty($_REQUEST['is_ajax'])) {
                return make_json_error($GLOBALS['_LANG']['priv_error']);
            } else {
                return dsc_header("Location: privilege.php?act=login\n")->send();
            }
        }

        $this->smarty->assign('token', $GLOBALS['_CFG']['token']);

        if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order'
        ) {
            $admin_path = preg_replace('/:\d+/', '', $this->dsc->seller_url()) . SELLER_PATH; //重置路径

            if (request()->server('HTTP_REFERER') && strpos(preg_replace('/:\d+/', '', request()->server('HTTP_REFERER')), $admin_path) === false) {
                if (!empty($_REQUEST['is_ajax'])) {
                    return make_json_error($GLOBALS['_LANG']['priv_error']);
                } else {
                    return dsc_header("Location: privilege.php?act=login\n")->send();
                }
            }
        }

        if (session()->has('seller_name')) {

            $uid = AdminUser::where('user_name', addslashes(session('seller_name')))->value('user_id');
            $uid = $uid ? $uid : 0;

            if (session('seller_id') > 0 && session('seller_id') != $uid) {

                $uname = AdminUser::where('user_id', intval(session('seller_id')))->value('user_name');
                $uname = $uname ? $uname : 0;

                session([
                    'seller_name' => $uname
                ]);
            }
        }

        //header('Cache-control: private');
        header('content-type: text/html; charset=' . EC_CHARSET);
        header('Expires: Fri, 14 Mar 1980 20:53:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        $adminru = [];
        if (session()->has('seller_id') && session('seller_id') > 0) {
            $adminru = get_admin_ru_id();

            if (!isset($adminru['ru_id']) || $adminru['ru_id'] == 0) {
                /* 清除cookie */
                $list = [
                    'ECSCP[seller_id]',
                    'ECSCP[seller_pass]'
                ];

                app(SessionRepository::class)->deleteCookie($list);

                session()->flush();

                return dsc_header("Location: privilege.php?act=login\n")->send();
            }
        }

        $this->smarty->assign('seller_id', session('seller_id', 0));
        $this->smarty->assign('seller_name', session('seller_name', ''));

        $seller_id = session()->has('seller_id') ? intval(session('seller_id')) : 0;

        //页面导航相关 by wu start
        load_helper(['menu', 'priv'], 'seller');

        $modules = $GLOBALS['modules'];
        $purview = $GLOBALS['purview'];

        set_seller_menu(); //顶部菜单
        get_menu_name(); //当前页面
        get_user_menu_pro(); //快捷菜单
        unset($modules, $purview); //用完后清空，避免影响其他功能
        //页面导航相关 by wu end
        //管理员信息 by wu
        if ($seller_id > 0) {
            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign('admin_id', $seller_id);

            $admin_info = AdminUser::where('user_id', intval($seller_id))->first();
            $admin_info = $admin_info ? $admin_info->toArray() : [];

            $admin_info['store_user_img'] = $admin_info && $admin_info['admin_user_img'] ? app(DscRepository::class)->getImagePath($admin_info['admin_user_img']) : '';

            $this->smarty->assign('admin_info', $admin_info);
        }

        /* 商家信息 */
        $this->seller_info = app(CommonManageService::class)->getSellerInfo();
        $this->smarty->assign('seller_info', $this->seller_info);

        $this->smarty->assign('site_url', str_replace(['http://', 'https://'], "", $this->dsc->get_domain()));

        // 分配字母 by zhang start
        $letter = range('A', 'Z');
        $this->smarty->assign('letter', $letter);

        $is_act = ['logout', 'login', 'signin', 'forget_pwd', 'reset_pwd'];

        if (!in_array($_REQUEST['act'], $is_act) && !empty($adminru)) {
            //店铺审核状态
            $merchants_audit = MerchantsShopInformation::where('user_id', intval($adminru['ru_id']))->value('merchants_audit');
            $merchants_audit = $merchants_audit ? $merchants_audit : 0;

            if ($merchants_audit != 1) {
                $link[] = ['href' => 'privilege.php?act=logout', 'text' => $GLOBALS['_LANG']['seller_logout']];
                return sys_msg($GLOBALS['_LANG']['seller_off'], 0, $link);
            }
        }

        //获取店铺链接
        if ($adminru) {
            $head_shop_name = app(MerchantCommonService::class)->getShopName($adminru['ru_id'], 3); //店铺名称
            $head_build_uri = [
                'urid' => $adminru['ru_id']
            ];

            $row = SellerDomain::where('ru_id', $adminru['ru_id'])->first();
            $row = $row ? $row->toArray() : [];

            $domain_name = $row['domain_name'] ?? '';
            if ($domain_name && $row['is_enable']) {
                $head_shop_url = $domain_name;
            } else {
                $head_shop_url = app(DscRepository::class)->buildUri('merchants_store', $head_build_uri, $head_shop_name);
                if (!empty($head_shop_url) && (strpos($head_shop_url, 'http://') === false && strpos($head_shop_url, 'https://') === false)) {
                    $head_shop_url = "../" . $head_shop_url;
                }
            }

            $this->smarty->assign('head_shop_url', $head_shop_url);
        }
    }
}
