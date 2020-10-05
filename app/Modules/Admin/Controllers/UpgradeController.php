<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ShopConfig;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpgradeController extends InitController
{
    protected $httpHandler;
    protected $filesystem;

    /**
     * @var string 补丁地址
     */
    protected $patchUrl;

    public function __construct(
        Client $client,
        Filesystem $filesystem
    )
    {
        $this->httpHandler = $client;
        $this->filesystem = $filesystem;
        $this->patchUrl = '';
    }

    public function index(Request $request)
    {
        $_REQUEST['act'] = $request->get('act', 'index');

        $dsc_version = ShopConfig::where('code', 'dsc_version')->value('value');
        $dsc_version = $dsc_version ? $dsc_version : '';

        // 当前版本
        $current_version = $dsc_version;

        // 补丁地址
        $patch = $this->patchList($current_version);

        /**
         * 在线升级列表
         */
        if ($_REQUEST['act'] == 'index') {
            // 检查权限
            $check_auth = check_authz_json('upgrade_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (empty($patch)) {
                $last_version = $GLOBALS['_LANG']['already_new'];
            } else {
                $last_version = end($patch);
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['list_link']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ecs_version', $current_version);
            $this->smarty->assign('ecs_release', RELEASE);
            $this->smarty->assign('last_version', $last_version);
            $this->smarty->assign('is_writable', $this->filesystem->isWritable(base_path()));
            $this->smarty->assign('patch', $patch);

            return $this->smarty->display('upgrade_index.dwt');
        }

        /**
         * 在线升级功能
         */
        if ($_REQUEST['act'] == 'init') {
            // 检查权限
            $check_auth = check_authz_json('upgrade_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            // 确认是否升级
            $cover = $request->get('cover', 0);
            if (empty($cover)) {
                return sys_msg($GLOBALS['_LANG']['covertemplate'], 1);
            }

            // 获取补丁列表
            if (empty($patch)) {
                return sys_msg($GLOBALS['_LANG']['already_new'], 2);
            }

            // 创建缓存文件夹
            $upgrade_path = storage_path('upgrade');
            if (!$this->filesystem->isDirectory($upgrade_path)) {
                $this->filesystem->makeDirectory($upgrade_path);
            }

            // 更新补丁包
            $this->upgrade($patch[0]);

            // 生成队列url
            if (isset($patch[1])) {
                $url = 'upgrade.php?act=init&cover=' . $cover . '&t=' . time();
            } else {
                $url = 'upgrade.php?act=index';
                // 清除缓存
                clear_all_files();
            }

            // 升级成功
            $links = [
                [
                    'text' => $patch[0] . $GLOBALS['_LANG']['upgrade_success'],
                    'href' => $url,
                ]
            ];

            return sys_msg($GLOBALS['_LANG']['upgradeing'], 2, $links);
        }
    }

    /**
     * 获取补丁列表
     * @param $current_version
     * @return array
     */
    protected function patchList($current_version)
    {
        $metadata = $this->httpHandler->get($this->patchUrl);

        $content = $metadata->getBody()->getContents();

        $metadata = dsc_decode($content, true);

        // 设置版本 hash
        Cache::forever('upgrade_hash', md5($content));

        // 获取可供当前版本升级的压缩包
        $patch = [];
        foreach ($metadata['x'] as $k => $v) {
            if (version_compare($v, $current_version, '>')) {
                $patch[] = $v;
            }
        }

        return $patch;
    }

    /**
     * 更新补丁包
     * @param $version
     * @return string
     */
    protected function upgrade($version)
    {
        // 补丁文件
        $patch = 'patch_' . $version;

        // 获取版本 hash
        $hash = Cache::get('upgrade_hash');

        // 远程压缩包地址
        $url = dirname($this->patchUrl) . '/x/' . substr($version, 0, 2) . '/' . $patch . '.zip?v=' . $hash;
        // 保存到本地地址
        $path = storage_path('upgrade/' . $patch . '.zip');
        // 补丁包解压路径
        $source_path = storage_path('upgrade/' . $patch);

        // 下载补丁压缩包
        $this->filesystem->put($path, $this->httpHandler->get($url)->getBody());

        // 解压缩补丁包
        if ($this->unzip($path, base_path()) === false) {
            Log::error($patch . ' upgrade unpack the failed');
            return redirect()->route('admin.upgrade');
        }

        // 删除文件
        $this->filesystem->delete($path);

        // 删除文件夹
        $this->filesystem->deleteDirectory($source_path);

        // 更新数据库
        $this->migrate($version);

        // 更新版本到数据库
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => $version
        ]);
    }

    /**
     * 更新数据库
     * @param $version
     */
    protected function migrate($version)
    {
        $version = 'App\\Patch\\Migration_' . str_replace('.', '_', $version);
        if (class_exists($version)) {
            app($version)->run();
        }
    }

    /**
     * 解压文件到指定目录
     *
     * @param string   zip压缩文件的路径
     * @param string   解压文件的目的路径
     * @param boolean  是否以压缩文件的名字创建目标文件夹
     * @param boolean  是否重写已经存在的文件
     *
     * @return  boolean  返回成功 或失败
     */
    protected function unzip($src_file, $dest_dir = false, $create_zip_name_dir = true, $overwrite = true)
    {
        if ($zip = zip_open($src_file)) {
            if ($zip) {
                $splitter = ($create_zip_name_dir === true) ? "." : "/";
                if ($dest_dir === false) {
                    $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter)) . "/";
                } else {
                    $dest_dir = rtrim($dest_dir, '/') . '/';
                }

                // 如果不存在 创建目标解压目录
                if (!$this->filesystem->isDirectory($dest_dir)) {
                    $this->filesystem->makeDirectory($dest_dir);
                }

                // 对每个文件进行解压
                while ($zip_entry = zip_read($zip)) {
                    // 文件不在根目录
                    $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                    if ($pos_last_slash !== false) {
                        // 创建目录 在末尾带 /
                        $path = $dest_dir . substr(zip_entry_name($zip_entry), 0, $pos_last_slash + 1);
                        if (!$this->filesystem->isDirectory($path)) {
                            $this->filesystem->makeDirectory($path);
                        }
                    }

                    // 打开包
                    if (zip_entry_open($zip, $zip_entry, "r")) {
                        // 文件名保存在磁盘上
                        $file_name = $dest_dir . zip_entry_name($zip_entry);

                        // 检查文件是否需要重写
                        if ($overwrite === true || $overwrite === false && !is_file($file_name)) {
                            // 读取压缩文件的内容
                            $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                            if (!$this->filesystem->isDirectory($file_name)) {
                                $this->filesystem->put($file_name, $fstream);
                            }
                            // 设置权限
                            chmod($file_name, 0755);
                        }
                        // 关闭入口
                        zip_entry_close($zip_entry);
                    }
                }
                // 关闭压缩包
                zip_close($zip);

                return true;
            }
        }

        return false;
    }
}
