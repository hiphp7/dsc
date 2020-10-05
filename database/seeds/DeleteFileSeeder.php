<?php

use App\Models\PresaleCat;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteFileSeeder extends Seeder
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * DeleteFileSeeder constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function run()
    {
        $this->renameDirectory();
        $this->delSqlData();

        /**
         * 删除文件
         */
        $list = [
            app_path('Helpers/oss.php'),
            app_path('Helpers/input.php'),
            app_path('Repositories/Common/BaseRepositorie.php'),
            app_path('Repositories/Common/DscRepositorie.php'),
            app_path('Repositories/Common/TimeRepositorie.php'),
            app_path('Helpers/commission.php'),
            app_path('Services/User/OrderApiService.php'),
            app_path('Http/Controllers/ObsController.php'),
            app_path('Http/Controllers/OssController.php'),
            app_path('Services/Common/OssService.php'),
            //app_path('Services/Common/CommonService.php'),
            app_path('Services/Cart/WholesaleCartService.php'),
            app_path('Libraries/Rsa.php'),
            app_path('Libraries/Session.php'),
            app_path('Services/CommentSeller/CommentSellerManageService.php'),
            app_path('Services/User/UserCollectService.php')
        ];

        foreach ($list as $k => $v) {
            if ($this->filesystem->isFile($v)) {
                $this->filesystem->delete($v);
            }
        }

        $this->clearCache();
    }

    /**
     * 修改目录名称
     */
    public function renameDirectory()
    {
        $file = Storage::disk('local')->exists('seeder/config-zh-cn.php');
        if (!$file) {
            $rows = [
                'value' => 'zh-CN'
            ];
            DB::table('shop_config')->where('code', 'lang')->update($rows);

            $data = '大商创x https://www.dscmall.cn/';
            Storage::disk('local')->put('seeder/config-zh-cn.php', $data);
        }

        /* 更新后台语言 */
        $admin_lang = app_path('Modules/Admin/Languages/');
        if (is_dir($admin_lang . 'zh_cn')) {
            $this->filesystem->deleteDirectory($admin_lang . 'zh_cn');
        }

        $seller_lang = app_path('Modules/Seller/Languages/');
        if (is_dir($seller_lang . 'zh_cn')) {
            $this->filesystem->deleteDirectory($seller_lang . 'zh_cn');
        }

        $stores_lang = app_path('Modules/Stores/Languages/');
        if (is_dir($stores_lang . 'zh_cn')) {
            $this->filesystem->deleteDirectory($stores_lang . 'zh_cn');
        }

        $suppliers_lang = app_path('Modules/Suppliers/Languages/');
        if (is_dir($suppliers_lang . 'zh_cn')) {
            $this->filesystem->deleteDirectory($suppliers_lang . 'zh_cn');
        }

        /* 更新插件语言 */
        $plugin = [
            'Connect',
            'Cron',
            'Payment',
            'Shipping'
        ];

        foreach ($plugin as $key => $val) {
            $directory = $this->filesystem->directories(plugin_path($val));

            if ($directory) {
                foreach ($directory as $idx => $row) {

                    $lang_path = $row . '/Languages/';

                    if (is_file($lang_path . 'zh_cn.php')) {
                        unlink($lang_path . 'zh_cn.php');
                    }
                }
            }
        }
    }

    /**
     * 删除默认数据的无效数据
     */
    private function delSqlData()
    {
        PresaleCat::where('cat_name', '智能尖货')->delete();
        Category::where('cat_name', '乐视电视')->delete();
    }

    /**
     * 清除缓存
     *
     * @throws Exception
     */
    protected function clearCache()
    {
        cache()->forget('shop_config');
    }
}
