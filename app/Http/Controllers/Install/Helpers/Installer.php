<?php

namespace App\Http\Controllers\Install\Helpers;


use App\Libraries\Image;
use App\Models\AdminUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Storage;

class Installer
{
    protected $clsMysql;
    protected $sqlExecutor;
    protected $timeRepository;
    protected $image;
    protected $baseRepository;
    protected $prefix;
    protected $dscRepository;

    public function __construct(
        ClsMysql $clsMysql,
        SqlExecutor $sqlExecutor,
        TimeRepository $timeRepository,
        Image $image,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->clsMysql = $clsMysql;
        $this->sqlExecutor = $sqlExecutor;
        $this->timeRepository = $timeRepository;
        $this->image = $image;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;

        $this->prefix = config('database.connections.mysql.prefix');
    }

    /**
     * 获得GD的版本号
     *
     * @access  public
     * @return  string     返回版本号，可能的值为0，1，2
     */
    public function get_gd_version()
    {
        return $this->image->gd_version();
    }

    /**
     * 是否支持GD
     *
     * @access  public
     * @return  boolean     成功返回true，失败返回false
     */
    public function has_supported_gd()
    {
        return $this->get_gd_version() === 0 ? false : true;
    }

    /**
     * 检测服务器上是否存在指定的文件类型
     *
     * @access  public
     * @param array $file_types 文件路径数组，形如array('dwt'=>'', 'lbi'=>'', 'dat'=>'')
     * @return  string    全部可写返回空串，否则返回以逗号分隔的文件类型组成的消息串
     */
    public function file_types_exists($file_types)
    {
        global $_LANG;

        $msg = '';
        foreach ($file_types as $file_type => $file_path) {
            if (!file_exists($file_path)) {
                $msg .= $_LANG['cannt_support_' . $file_type] . ', ';
            }
        }

        $msg = preg_replace("/,\s*$/", '', $msg);

        return $msg;
    }

    /**
     * 获得系统的信息
     *
     * @access  public
     * @return  array     系统各项信息组成的数组
     */
    public function get_system_info()
    {
        global $_LANG;

        $system_info = array();

        /* 检查系统基本参数 */
        $system_info[] = array($_LANG['php_os'], PHP_OS);
        $system_info[] = array($_LANG['php_ver'], PHP_VERSION);

        /* 检查MYSQL支持情况 */
        $mysql_enabled = config('database.connections.mysql.driver') == 'mysql' ? $_LANG['support'] : $_LANG['not_support'];
        $system_info[] = array($_LANG['does_support_mysql'], $mysql_enabled);

        /* 检查图片处理函数库 */
        $gd_ver = $this->get_gd_version();
        $gd_ver = empty($gd_ver) ? $_LANG['not_support'] : $gd_ver;
        if ($gd_ver > 0) {
            if (PHP_VERSION >= '4.3' && function_exists('gd_info')) {
                $gd_info = gd_info();
                $jpeg_enabled = ($gd_info['JPEG Support'] === true) ? $_LANG['support'] : $_LANG['not_support'];
                $gif_enabled = ($gd_info['GIF Create Support'] === true) ? $_LANG['support'] : $_LANG['not_support'];
                $png_enabled = ($gd_info['PNG Support'] === true) ? $_LANG['support'] : $_LANG['not_support'];
            } else {
                if (function_exists('imagetypes')) {
                    $jpeg_enabled = ((imagetypes() & IMG_JPG) > 0) ? $_LANG['support'] : $_LANG['not_support'];
                    $gif_enabled = ((imagetypes() & IMG_GIF) > 0) ? $_LANG['support'] : $_LANG['not_support'];
                    $png_enabled = ((imagetypes() & IMG_PNG) > 0) ? $_LANG['support'] : $_LANG['not_support'];
                } else {
                    $jpeg_enabled = $_LANG['not_support'];
                    $gif_enabled = $_LANG['not_support'];
                    $png_enabled = $_LANG['not_support'];
                }
            }
        } else {
            $jpeg_enabled = $_LANG['not_support'];
            $gif_enabled = $_LANG['not_support'];
            $png_enabled = $_LANG['not_support'];
        }
        $system_info[] = array($_LANG['gd_version'], $gd_ver);
        $system_info[] = array($_LANG['jpeg'], $jpeg_enabled);
        $system_info[] = array($_LANG['gif'], $gif_enabled);
        $system_info[] = array($_LANG['png'], $png_enabled);

        /* 检查系统是否支持以dwt,lib,dat为扩展名的文件 */
        $file_types = array(
            'dwt' => resource_path('views/themes/ecmoban_dsc2017/index.dwt'),
            'lbi' => resource_path('views/themes/ecmoban_dsc2017/library/member_info.lbi'),
            'dat' => resource_path('codetable/ipdata.dat')
        );
        $exists_info = $this->file_types_exists($file_types);
        $exists_info = empty($exists_info) ? $_LANG['support_dld'] : $exists_info;
        $system_info[] = array($_LANG['does_support_dld'], $exists_info);

        /* 服务器是否安全模式开启 */
        $safe_mode = ini_get('safe_mode') == '1' ? $_LANG['safe_mode_on'] : $_LANG['safe_mode_off'];
        $system_info[] = array($_LANG['safe_mode'], $safe_mode);

        return $system_info;
    }

    /**
     * 获得数据库列表
     *
     * @access  public
     * @param string $db_host 主机
     * @param string $db_port 端口号
     * @param string $db_user 用户名
     * @param string $db_pass 密码
     * @return  mixed       成功返回数据库列表组成的数组，失败返回false
     */
    public function get_db_list($db_host, $db_port, $db_user, $db_pass)
    {
        global $_LANG;
        $filter_dbs = ['information_schema', 'mysql'];

        try {
            $config = [
                'db_host' => urldecode($db_host),
                'db_port' => $db_port,
                'db_user' => urldecode($db_user),
                'db_pass' => urldecode($db_pass)
            ];

            $pdo = $this->getPdoDb($config);

            $sql = "SHOW DATABASES";
            $result = $pdo->query($sql);

            $pdo = null;

            if ($result === false) {
                $GLOBALS['err']->add($_LANG['query_failed']);
                return false;
            } else {
                $list = $result->fetchAll();

                $databases = [];
                if ($list) {
                    foreach ($list as $key => $row) {
                        if (in_array($row['Database'], $filter_dbs)) {
                            continue;
                        }
                        $databases[] = $row['Database'];
                    }
                }

                return $databases;
            }
        } catch (\PDOException $e) {
            $GLOBALS['err']->add($_LANG['connect_failed']);
            return false;
        }
    }

    /**
     * 获得时区列表，如有重复值，只保留第一个
     *
     * @access  public
     * @return  array
     */
    public function get_timezone_list($lang)
    {
        $langPath = app_path('Http/Controllers/Install/data/inc_timezones_' . $lang . '.php');

        if (file_exists($langPath)) {
            $timezones = include_once($langPath);
        } else {
            $timezones = include_once(app_path('Http/Controllers/Install/data/inc_timezones_zh_cn.php'));
        }

        return array_unique($timezones);
    }

    /**
     * 获得服务器所在时区
     *
     * @access  public
     * @return  string     返回时区串，形如Asia/Shanghai
     */
    public function get_local_timezone()
    {
        if (PHP_VERSION >= '5.1') {
            $local_timezone = @date_default_timezone_get('Asia/Shanghai');
        } else {
            $local_timezone = '';
        }

        return $local_timezone;
    }

    /**
     * 创建指定名字的数据库
     * 创建指定名字的数据库
     *
     * @access  public
     * @param string $db_host 主机
     * @param string $db_port 端口号
     * @param string $db_user 用户名
     * @param string $db_pass 密码
     * @param string $db_name 数据库名
     * @return  boolean     成功返回true，失败返回false
     */
    public function create_database($db_host, $db_port, $db_user, $db_pass, $db_name)
    {
        global $_LANG;

        try {
            $config = [
                'db_host' => urldecode($db_host),
                'db_port' => $db_port,
                'db_user' => urldecode($db_user),
                'db_pass' => urldecode($db_pass)
            ];
            $pdo = $this->getPdoDb($config);

            // 创建数据库
            $sql = "CREATE DATABASE $db_name DEFAULT CHARACTER SET " . DSC_DB_CHARSET;
            $result = $pdo->query($sql);

            $error = $pdo->errorInfo();

            $pdo = null;

            if (isset($error[1]) && $error[1] == 1064) {
                $GLOBALS['err']->add($_LANG['dbname_illegal_character']);
                return false;
            } else {
                if ($result === false) {
                    $GLOBALS['err']->add($_LANG['cannt_create_database']);
                    return false;
                }
            }
        } catch (\PDOException $e) {
            $GLOBALS['err']->add($_LANG['connect_failed']);
            return false;
        }

        return true;
    }

    /**
     * 保证进行正确的数据库连接（如字符集设置）
     *
     * @access  public
     * @param string $conn 数据库连接
     * @param string $mysql_version mysql版本号
     * @return  void
     */
    public function keep_right_conn($conn, $mysql_version = '')
    {
        if ($mysql_version === '') {
            $mysql_version = mysql_get_server_info($conn);
        }

        if ($mysql_version >= '4.1') {
            mysql_query('SET character_set_connection=' . DSC_DB_CHARSET . ', character_set_results=' . DSC_DB_CHARSET . ', character_set_client=binary', $conn);

            if ($mysql_version > '5.0.1') {
                mysql_query("SET sql_mode=''", $conn);
            }
        }
    }

    /**
     * 创建配置文件
     *
     * @access  public
     * @param string $db_host 主机
     * @param string $db_port 端口号
     * @param string $db_user 用户名
     * @param string $db_pass 密码
     * @param string $db_name 数据库名
     * @param string $prefix 数据表前缀
     * @param string $timezone 时区
     * @return  boolean     成功返回true，失败返回false
     */
    public function create_config_file($db_host, $db_port, $db_user, $db_pass, $db_name, $prefix, $timezone)
    {
        global $_LANG;

        $asset = url('/');
        $asset = rtrim($asset, '/');

        $key = 'base64:' . base64_encode(
                Encrypter::generateKey(config('app.cipher'))
            );

        $content = "APP_NAME=DscMall\n";
        $content .= "APP_ENV=production\n";
        $content .= "APP_KEY={$key}\n";
        $content .= "APP_DEBUG=false\n";
        $content .= "DSC_KEY=\n\n";
        $content .= "APP_CLIENT=false\n";
        $content .= "APP_MP_CHECKED=false\n";
        $content .= "APP_URL=" . $asset . "\n";
        $content .= "ASSET_URL=" . $asset . "\n\n";

        $content .= "LOG_CHANNEL=stack\n\n";

        $content .= "DB_CONNECTION=mysql\n";
        $content .= "DB_HOST=" . urldecode($db_host) . "\n";
        $content .= "DB_PORT=$db_port\n";
        $content .= "DB_DATABASE=" . urldecode($db_name) . "\n";
        $content .= "DB_USERNAME=" . urldecode($db_user) . "\n";
        $content .= "DB_PASSWORD='" . urldecode($db_pass) . "'" . "\n";
        $content .= "DB_PREFIX=" . urldecode($prefix) . "\n\n";

        $content .= "BROADCAST_DRIVER=log\n";
        $content .= "CACHE_DRIVER=file\n";
        $content .= "SESSION_DRIVER=file\n";
        $content .= "SESSION_LIFETIME=120\n";
        $content .= "QUEUE_DRIVER=sync\n\n";

        $content .= "FILESYSTEM_DRIVER=public\n\n";

        $content .= "REDIS_HOST=127.0.0.1\n";
        $content .= "REDIS_PASSWORD=null\n";
        $content .= "REDIS_PORT=6379\n\n";

        $content .= "MAIL_DRIVER=smtp\n";
        $content .= "MAIL_HOST=smtp.mailtrap.io\n";
        $content .= "MAIL_PORT=2525\n";
        $content .= "MAIL_USERNAME=null\n";
        $content .= "MAIL_PASSWORD=null\n";
        $content .= "MAIL_ENCRYPTION=null\n\n";

        $content .= "PUSHER_APP_ID=\n";
        $content .= "PUSHER_APP_KEY=\n";
        $content .= "PUSHER_APP_SECRET=\n";
        $content .= "PUSHER_APP_CLUSTER=mt1\n\n";

        $content .= "MIX_PUSHER_APP_KEY = " . '"${PUSHER_APP_KEY}"' . "\n";
        $content .= "MIX_PUSHER_APP_CLUSTER = " . '"${PUSHER_APP_CLUSTER}"' . "\n\n";

        $content .= "OSS_ACCESS_ID=\n";
        $content .= "OSS_ACCESS_KEY =\n";
        $content .= "OSS_BUCKET =\n";
        $content .= "OSS_ENDPOINT =\n";
        $content .= "OSS_ENDPOINT_INTERNAL =\n";
        $content .= "OSS_CDN_DOMAIN =\n";
        $content .= "OSS_SSL = false\n";
        $content .= "OSS_IS_CNAME = false\n\n";

        $content .= "UC_CONNECT = API\n";
        $content .= "UC_KEY = 123456\n";
        $content .= "UC_API = " . $asset . "/uc_server\n";
        $content .= "UC_IP =\n";
        $content .= "UC_CHARSET = utf-8\n";
        $content .= "UC_APPID = 1\n";
        $content .= "UC_PPP = 20\n\n";

        $fp = @fopen(base_path('.env'), 'wb+');
        if (!$fp) {
            $GLOBALS['err']->add($_LANG['open_config_file_failed']);
            return false;
        }
        if (!@fwrite($fp, trim($content))) {
            $GLOBALS['err']->add($_LANG['write_config_file_failed']);
            return false;
        }
        @fclose($fp);

        return true;
    }

    /**
     * 把host、port重组成指定的串
     *
     * @access  public
     * @param string $db_host 主机
     * @param string $db_port 端口号
     * @return  string      host、port重组后的串，形如host:port
     */
    public function construct_db_host($db_host, $db_port)
    {
        return urldecode($db_host) . ':' . urldecode($db_port);
    }

    /**
     * 安装数据
     *
     * @access  public
     * @param array $sql_files SQL文件路径组成的数组
     * @return  boolean       成功返回true，失败返回false
     */
    public function install_data($sql_files, $db_host, $db_port, $db_user, $db_pass, $db_name)
    {
        global $_LANG;

        $dsn = "mysql:host=" . urldecode($db_host) . ";port=" . $db_port . ";dbname=" . urldecode($db_name);

        $this->sqlExecutor->dsn = $dsn;
        $this->sqlExecutor->db_user = urldecode($db_user);
        $this->sqlExecutor->db_pass = urldecode($db_pass);
        $this->sqlExecutor->db_charset = DSC_DB_CHARSET;
        $this->sqlExecutor->sprefix = 'dsc_';
        $this->sqlExecutor->tprefix = config('database.connections.mysql.prefix');
        $this->sqlExecutor->db();

        // 创建数据库表和数据
        $result = $this->sqlExecutor->run_all($sql_files);

        if ($result === false) {
            $GLOBALS['err']->add($_LANG['fail_table_data']);
            return false;
        }

        $this->dscRepository->getPatch();

        return true;
    }

    /**
     * 创建管理员帐号
     *
     * @access  public
     * @param string $admin_name
     * @param string $admin_password
     * @param string $admin_password2
     * @param string $admin_email
     * @return  boolean     成功返回true，失败返回false
     */
    public function create_admin_passport($admin_name, $admin_password, $admin_password2, $admin_email, $db = [])
    {
        global $_LANG;

        if (trim($_REQUEST['lang']) != 'zh_cn') {
            $system_lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 'zh_cn';

            $lang = app_path('Http/Controllers/Install/lang/' . $system_lang . '.php');
            include($lang);
        }

        if ($admin_password === '') {
            $GLOBALS['err']->add($_LANG['password_empty_error']);
            return false;
        }

        if ($admin_password === '') {
            $GLOBALS['err']->add($_LANG['password_empty_error']);
            return false;
        }

        if (!(strlen($admin_password) >= 8 && preg_match("/\d+/", $admin_password) && preg_match("/[a-zA-Z]+/", $admin_password))) {
            $GLOBALS['err']->add($_LANG['js_languages']['password_invaild']);
            return false;
        }

        if ($admin_password !== $admin_password2) {
            $GLOBALS['err']->add($_LANG['passwords_not_eq']);
            return false;
        }

        $nav_list = $this->baseRepository->getImplode($_LANG['admin_user']);

        $other = [
            'user_name' => $admin_name,
            'email' => $admin_email,
            'password' => md5($admin_password),
            'add_time' => $this->timeRepository->getGmTime(),
            'action_list' => 'all',
            'nav_list' => $nav_list ?? ''
        ];

        $id = AdminUser::insertGetId($other);

        if ($id < 1) {
            $GLOBALS['err']->add($_LANG['create_passport_failed']);
            return false;
        }

        return true;
    }

    /**
     * 把一个文件从一个目录复制到另一个目录
     *
     * @access  public
     * @param string $source 源目录
     * @param string $target 目标目录
     * @return  boolean     成功返回true，失败返回false
     */
    public function copy_files($source, $target)
    {
        global $_LANG;

        if (!file_exists($target)) {
            //if (!mkdir(rtrim($target, '/'), 0777))
            if (!mkdir($target, 0777)) {
                $GLOBALS['err']->add($_LANG['cannt_mk_dir']);
                return false;
            }
            @chmod($target, 0777);
        }

        $dir = opendir($source);
        while (($file = @readdir($dir)) !== false) {
            if (is_file($source . $file)) {
                if (!copy($source . $file, $target . $file)) {
                    $GLOBALS['err']->add($_LANG['cannt_copy_file']);
                    return false;
                }
                @chmod($target . $file, 0777);
            }
        }
        closedir($dir);

        return true;
    }

    /**
     * 其它设置
     *
     * @access  public
     * @param string $system_lang 系统语言
     * @param string $disable_captcha 是否开启验证码
     * @param string $install_demo 是否安装测试数据
     * @param string $integrate_code 用户接口
     * @return  boolean     成功返回true，失败返回false
     */
    public function do_others($system_lang, $config, $captcha, $install_demo, $integrate_code)
    {
        /* 安装测试数据 */
        if ($install_demo == 1) {
            $demoPath = public_path('assets/install/data/' . $system_lang . '.sql');

            if (file_exists($demoPath)) {
                $sql_files = array($demoPath);
            } else {
                $sql_files = array(public_path('assets/install/data/demo_zh_cn.sql'));
            }

            if (!$this->install_data($sql_files, $config['db_host'], $config['db_port'], $config['db_user'], $config['db_pass'], $config['db_name'])) {
                $GLOBALS['err']->add(implode('', $GLOBALS['err']->last_message()));
                return false;
            }
        }

        $pdo = $this->getPdoDb($config);

        if ($pdo !== false) {
            $sql = "UPDATE " . $this->prefix . "shop_config SET value = '$system_lang' WHERE code = 'lang'";
            $pdo->query($sql);
        }

        /* 更新用户接口 */
        if (!empty($integrate_code)) {
            if ($pdo !== false) {
                $sql = "UPDATE " . $this->prefix . "shop_config SET value = '$integrate_code' WHERE code = 'integrate_code'";
                $pdo->query($sql);
            }
        }

        /* 处理验证码 */
        if (!empty($captcha)) {
            if ($pdo !== false) {
                $sql = "UPDATE " . $this->prefix . "shop_config SET value = '0' WHERE code = 'captcha'";
                $pdo->query($sql);
            }
        }

        $pdo = null;

        return true;
    }

    /**
     * 安装完成后的一些善后处理
     *
     * @access  public
     * @return  boolean     成功返回true，失败返回false
     */
    public function deal_aftermath()
    {
        $time = $this->timeRepository->getGmTime();

        $db_host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $dbname = config('database.connections.mysql.database');

        $config = [
            'db_host' => urldecode($db_host),
            'db_port' => $port,
            'db_user' => urldecode($username),
            'db_pass' => urldecode($password),
            'db_name' => urldecode($dbname)
        ];

        $pdo = $this->getPdoDb($config);

        /* 更新安装日期 */
        if ($pdo !== false) {
            $sql = "UPDATE " . $this->prefix . "shop_config SET value = '$time' WHERE code = 'install_date'";
            $pdo->query($sql);
        }

        /* 更新版本 */
        if ($pdo !== false) {
            $sql = "UPDATE " . $this->prefix . "shop_config SET value = '" . VERSION . "' WHERE code = 'sc_version'";
            $pdo->query($sql);
        }

        /* 写入 hash_code，做为网站唯一性密钥 */
        $hash_code = md5(md5($time) . md5(urldecode($db_host)) . md5($time));

        if ($pdo !== false) {
            $sql = "UPDATE " . $this->prefix . "shop_config SET value = '$hash_code' WHERE code = 'hash_code' AND value = ''";
            $pdo->query($sql);
        }

        /* 写入安装锁定文件 */
        $lockfile = Storage::disk('local')->exists('seeder/install.lock.php');
        if (!$lockfile) {
            $data = '大商创x https://www.dscmall.cn/';
            Storage::disk('local')->put('seeder/install.lock.php', $data);
        }

        return true;
    }

    /**
     * 获得spt代码
     *
     * @access  public
     * @return  string   spt代码
     */
    public function get_spt_code()
    {
        /*$hash_code = ShopConfig::where('code', 'hash_code')->value('value');
        $hash_code = $hash_code ? $hash_code : '';*/
        $hash_code = '';

        $spt = '<script type="text / javascript" src="http://api.ecmoban.com/record.php?';
        $spt .= "url=" . urlencode($GLOBALS['dsc']->url()) . "&mod=install&version=" . VERSION . "&hash_code=" . $hash_code . "&charset=" . EC_CHARSET . "&language=" . $GLOBALS['installer_lang'] . "\"></script>";

        return $spt;
    }

    //获得当前站点的信息by wang
    public function get_web_info()
    {
        $web_info = array(
            'sc_version' => VERSION,
            'user_domain' => url('/') . '/',
            'user_ip' => request()->server('SERVER_ADDR'),
            'user_os' => PHP_OS,
            'user_webserver' => request()->server('SERVER_SOFTWARE'),
            'user_phpversion' => PHP_VERSION,
            'install_time' => time()
        );

        return $web_info;
    }

    /**
     * 取得当前的域名
     *
     * @access  public
     *
     * @return  string      当前的域名
     */
    public function get_domain()
    {
        /* 协议 */
        $protocol = http();

        /* 域名或IP地址 */
        if (request()->server('HTTP_X_FORWARDED_HOST')) {
            $host = request()->server('HTTP_X_FORWARDED_HOST');
        } elseif (request()->server('HTTP_HOST')) {
            $host = request()->server('HTTP_HOST');
        } else {
            /* 端口 */
            if (request()->server('SERVER_PORT')) {
                $port = ':' . request()->server('SERVER_PORT');

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (request()->server('SERVER_NAME')) {
                $host = request()->server('SERVER_NAME') . $port;
            } elseif (request()->server('SERVER_ADDR')) {
                $host = request()->server('SERVER_ADDR') . $port;
            }
        }

        return $protocol . $host;
    }

    /**
     * 获得 DSCMALL 当前环境的 URL 地址
     *
     * @access  public
     *
     * @return  void
     */
    public function url()
    {
        $PHP_SELF = request()->server('PHP_SELF') ? request()->server('PHP_SELF') : request()->server('SCRIPT_NAME');
        $ecserver = 'http://' . request()->server('HTTP_HOST') . (request()->server('SERVER_PORT') && request()->server('SERVER_PORT') != 80 ? ':' . request()->server('SERVER_PORT') : '');
        $default_appurl = $ecserver . substr($PHP_SELF, 0, strpos($PHP_SELF, 'install/') - 1);

        return $default_appurl;
    }

    /**
     * 获得 DSCMALL 当前环境的 HTTP 协议方式
     *
     * @access  public
     *
     * @return  void
     */
    public function http()
    {
        return (request()->server('HTTPS') && (strtolower(request()->server('HTTPS')) != 'off')) ? 'https://' : 'http://';
    }


    public function insertconfig($s, $find, $replace)
    {
        if (preg_match($find, $s)) {
            $s = preg_replace($find, $replace, $s);
        } else {
            // 插入到最后一行
            $s .= "\r\n" . $replace;
        }
        return $s;
    }

    public function getgpc($k, $var = 'G')
    {
        switch ($var) {
            case 'G':
                $var = &$_GET;
                break;
            case 'P':
                $var = &$_POST;
                break;
            case 'C':
                $var = &request()->cookie();
                break;
            case 'R':
                $var = &$_REQUEST;
                break;
        }

        return isset($var[$k]) ? $var[$k] : '';
    }

    public function var_to_hidden($k, $v)
    {
        return "<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
    }

    public function dfopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = false, $ip = '', $timeout = 15, $block = true)
    {
        $return = '';
        $matches = parse_url($url);
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'] . '?' . $matches['query'] . ($matches['fragment'] ? '#' . $matches['fragment'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;

        if ($post) {
            $out = "POST $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: " . request()->server('HTTP_USER_AGENT') . "\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: ' . strlen($post) . "\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "User-Agent: " . request()->server('HTTP_USER_AGENT') . "\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }
        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if (!$status['timed_out']) {
                while (!feof($fp)) {
                    if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                        break;
                    }
                }

                $stop = false;
                while (!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if ($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);
            return $return;
        }
    }

    public function save_uc_config($config)
    {
        global $_LANG;

        list($appauthkey, $appid, $ucdbhost, $ucdbname, $ucdbuser, $ucdbpw, $ucdbcharset, $uctablepre, $uccharset, $ucapi, $ucip) = explode('|', $config);

        $cfg = array(
            'uc_id' => $appid,
            'uc_key' => $appauthkey,
            'uc_url' => $ucapi,
            'uc_ip' => $ucip,
            'uc_connect' => 'mysql',
            'uc_charset' => $uccharset,
            'db_host' => $ucdbhost,
            'db_user' => $ucdbuser,
            'db_name' => $ucdbname,
            'db_pass' => $ucdbpw,
            'db_pre' => $uctablepre,
            'db_charset' => $ucdbcharset,
        );
        $content = "<?php\r\n";
        $content .= "\$cfg = " . var_export($cfg, true) . ";\r\n";
        $content .= "?>";

        $config_temp = app_path('Http/Controllers/Install/data/config_temp.php');
        $fp = @fopen($config_temp, 'wb+');
        if (!$fp) {
            $result['error'] = 1;
            $result['message'] = $_LANG['ucenter_datadir_access'];
            die($GLOBALS['json']->encode($result));
        }
        if (!@fwrite($fp, $content)) {
            $result['error'] = 1;
            $result['message'] = $_LANG['ucenter_tmp_config_error'];
            die($GLOBALS['json']->encode($result));
        }
        @fclose($fp);

        return true;
    }

    /**
     * 链接数据库
     *
     * @param $db
     * @return $this
     */
    public function getPdoDb($db = [])
    {
        try {

            $db_host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $dbname = config('database.connections.mysql.database');

            if (empty($db)) {
                $db = [
                    'db_host' => urldecode($db_host),
                    'db_port' => $port,
                    'db_user' => urldecode($username),
                    'db_pass' => urldecode($password),
                    'db_name' => urldecode($dbname)
                ];
            }

            if (isset($db['db_name']) && !empty($db['db_name'])) {
                $dsn = "mysql:host=" . $db['db_host'] . ";port=" . $db['db_port'] . ";dbname=" . $db['db_name'];
            } else {
                $dsn = "mysql:host=" . $db['db_host'] . ";port=" . $db['db_port'];
            }

            $charset = array(\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_ERRMODE => 2, \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DSC_DB_CHARSET);
            $pdo = new \PDO($dsn, $db['db_user'], $db['db_pass'], $charset);

            return $pdo;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
