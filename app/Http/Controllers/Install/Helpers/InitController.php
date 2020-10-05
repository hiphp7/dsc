<?php

namespace App\Http\Controllers\Install\Helpers;

use App\Http\Controllers\Controller;
use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use Illuminate\Support\Str;

class InitController extends Controller
{
    protected $dsc;
    protected $db;
    protected $err;
    protected $sess;
    protected $smarty;

    protected function initialize()
    {
        $shop = new Shop();
        $mysql = new Mysql();
        $error = new Error();

        $php_self = Str::snake(basename($this->getCurrentControllerName(), 'Controller'));
        defined('PHP_SELF') or define('PHP_SELF', $php_self . '.php');

        $_GET = request()->query() + request()->route()->parameters();
        $_POST = request()->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

        $load_helper = [
            'time', 'base', 'common', 'main', 'insert', 'goods', 'article', 'input',
            'ecmoban', 'function', 'seller_store', 'scws', 'wholesale'
        ];
        load_helper($load_helper);

        /* 创建 SHOP 对象 */
        $this->dsc = $GLOBALS['dsc'] = $shop;
        define('DATA_DIR', $this->dsc->data_dir());
        define('IMAGE_DIR', $this->dsc->image_dir());

        /* 初始化数据库类 */
        $this->db = $GLOBALS['db'] = $mysql;

        /* 创建错误处理对象 */
        $this->err = $GLOBALS['err'] = $error;

        /* 载入语言文件 */
        load_lang(['common', 'js_languages', basename(PHP_SELF, '.php')]);

        define('__ROOT__', url('/') . '/');
        define('__PUBLIC__', asset('/assets'));
        define('__STORAGE__', __ROOT__ . "storage");
    }
}
