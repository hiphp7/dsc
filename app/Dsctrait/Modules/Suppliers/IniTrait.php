<?php

namespace App\Dsctrait\Modules\Suppliers;

use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use App\Libraries\Template;
use App\Libraries\Transport;
use App\Models\AdminUser;
use App\Models\ShopConfig;
use App\Repositories\Common\SessionRepository;
use App\Services\Wholesale\CommonManageService;
use Illuminate\Support\Str;

/**
 * 管理中心公用文件
 */
define('ECS_SUPPLIER', true);

trait IniTrait
{
    protected function initialize()
    {
        $php_self = Str::snake(basename($this->getCurrentControllerName(), 'Controller'));
        defined('PHP_SELF') or define('PHP_SELF', '/' . SUPPLLY_PATH . '/' . $php_self . '.php');

        $_GET = request()->query() + request()->route()->parameters();
        $_POST = request()->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

        load_helper([
            'time', 'base', 'common', 'main', 'scws', 'ecmoban',
            'function', 'publicfunc', 'commission', 'wholesale', 'suppliers',
            'visual'
        ]);

        load_helper(['main'], 'suppliers');

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
        app(SessionRepository::class)->sessionRepy('ECSCP_SUPPLY_ID');

        /* 初始化 action */
        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'login' || $_REQUEST['act'] == 'logout' || $_REQUEST['act'] == 'signin') && strpos(PHP_SELF, 'privilege.php') === false) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'forget_pwd' || $_REQUEST['act'] == 'reset_pwd' || $_REQUEST['act'] == 'get_pwd') && strpos(PHP_SELF, 'get_password.php') === false) {
            $_REQUEST['act'] = '';
        }

        load_lang(['common_suppliers', 'log_action', basename(PHP_SELF, '.php')], 'suppliers');

        clearstatcache();

        /* 如果有新版本，升级 */
        if (!isset($GLOBALS['_CFG']['dsc_version'])) {
            $GLOBALS['_CFG']['dsc_version'] = 'v1.0';
        }

        define('__ROOT__', url('/') . '/');
        define('__PUBLIC__', asset('/assets'));
        define('__TPL__', asset('/assets/suppliers'));
        define('__STORAGE__', __ROOT__ . "storage");

        /* 创建 Smarty 对象。 */
        $this->smarty = $GLOBALS['smarty'] = new Template();

        $template_dir = app_path('Modules/' . Str::studly(SUPPLLY_PATH)) . '/Views';
        $this->smarty->template_dir = $template_dir;
        $this->smarty->compile_dir = storage_path('framework/temp/compiled/' . SUPPLLY_PATH);
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
            if ($token == md5(md5($GLOBALS['_CFG']['token']) . $domain_url . SUPPLLY_PATH)) {
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

                return redirect()->route('supplier.home');
            }
        }

        /* 验证管理员身份 */
        if ((!session()->has('supply_id') || intval(session('supply_id')) <= 0) &&
            $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'check_user_name' && $_REQUEST['act'] != 'check_user_password' && //by wu
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order'
        ) {
            if (!empty($_REQUEST['is_ajax'])) {
                return make_json_error($GLOBALS['_LANG']['priv_error']);
            } else {
                return dsc_header("Location: privilege.php?act=login\n");
            }
        }

        $this->smarty->assign('token', $GLOBALS['_CFG']['token']);

        if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order'
        ) {
            $admin_path = preg_replace('/:\d+/', '', $this->dsc->seller_url(SUPPLLY_PATH)) . SUPPLLY_PATH; //重置路径

            if (request()->server('HTTP_REFERER') && strpos(preg_replace('/:\d+/', '', request()->server('HTTP_REFERER')), $admin_path) === false) {
                if (!empty($_REQUEST['is_ajax'])) {
                    return make_json_error($GLOBALS['_LANG']['priv_error']);
                } else {
                    return redirect()->route('supplier.privilege', ['act' => 'login']);
                }
            }
        }

        if (session()->has('supply_name')) {
            $uid = AdminUser::where('user_name', addslashes(session('supply_name')))
                ->value('user_id');
            $uid = $uid ? $uid : 0;

            if (session('supply_id') > 0 && session('supply_id') != $uid) {
                $uname = AdminUser::where('user_id', intval(session('supply_id')))
                    ->value('user_name');
                $uname = $uname ? $uname : '';

                session([
                    'supply_name' => $uname
                ]);
            }
        }

        header('content-type: text/html; charset=' . EC_CHARSET);
        header('Expires: Fri, 14 Mar 1980 20:53:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        $adminru = get_admin_ru_id();

        //页面导航相关 start
        load_helper(['menu', 'priv'], 'suppliers');

        $sellerMenu = app(CommonManageService::class)->setSellerMenu($GLOBALS['modules'], $GLOBALS['purview']); //顶部菜单
        $this->smarty->assign('seller_menu', $sellerMenu['menu']);
        $this->smarty->assign('seller_logo', $sellerMenu['logo']);
        $this->smarty->assign('privilege_seller', $sellerMenu['privilege']);

        $menu_arr = app(CommonManageService::class)->getMenuName($GLOBALS['modules']); //当前页面
        $this->smarty->assign('menu_select', $menu_arr);

        //快捷菜单
        $user_menu_pro = app(CommonManageService::class)->getUserMenuPro($GLOBALS['modules']);
        $this->smarty->assign('user_menu_pro', $user_menu_pro);

        //用完后清空，避免影响其他功能
        unset($modules, $purview);
        //页面导航相关 end

        $supply_id = session()->has('supply_id') ? intval(session('supply_id')) : 0;

        $this->smarty->assign('ru_id', $adminru['ru_id'] ?? 0);
        $this->smarty->assign('admin_id', $supply_id);

        $this->smarty->assign('supply_id', session('supply_id', 0));
        $this->smarty->assign('supply_name', session('supply_name', ''));

        //管理员信息 by wu
        $admin_info = AdminUser::where('user_id', $supply_id)->first();
        $admin_info = $admin_info ? $admin_info->toArray() : [];

        if ($admin_info) {
            $admin_info['admin_user_img'] = get_image_path($admin_info['admin_user_img']);
        }

        $this->smarty->assign('admin_info', $admin_info);

        $this->smarty->assign('site_url', str_replace(['http://', 'https://'], "", $this->dsc->get_domain()));

        // 分配字母 by zhang start
        $letter = range('A', 'Z');
        $this->smarty->assign('letter', $letter);

        if (!session()->has('menus')) {
            session([
                'menus' => []
            ]);
        }
    }
}
