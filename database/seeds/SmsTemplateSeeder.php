<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;

class SmsTemplateSeeder extends Seeder
{
    private $prefix;
    private $filesystem;

    public function __construct(
        Filesystem $filesystem
    )
    {
        $this->filesystem = $filesystem;
        $this->prefix = config('database.connections.mysql.prefix');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->smsTable();
        $this->smsTempate();
        $this->smsShopConfig();
        $this->delFile();
    }

    /**
     * 修改表名称
     */
    public function smsTable()
    {
        if (Schema::hasTable('alidayu_configure') && !Schema::hasTable('sms_template')) {
            $sql = "RENAME TABLE `" . $this->prefix . "alidayu_configure` TO `" . $this->prefix . "sms_template`";
            DB::statement($sql);
        }

        $name = 'sms_template';
        if (!Schema::hasColumn($name, 'sender')) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('sender', 30)->default('')->comment('短信通道号');;
            });
        }
    }

    /**
     * 删除 alitongxin_configure 表
     */
    public function smsTempate()
    {
        $val = DB::table('shop_config')->where('code', 'sms_type')->value('value');

        if ($val == 2 && Schema::hasTable('alidayu_configure')) {
            $count = DB::table('alitongxin_configure')->count();

            if ($count > 0) {
                $list = DB::table('alitongxin_configure')->get();

                $list = $list ? collect($list)->toArray() : [];

                if ($list && Schema::hasTable('sms_template')) {
                    $sql = "TRUNCATE `" . $this->prefix . "sms_template`";
                    DB::statement($sql);

                    foreach ($list as $key => $value) {
                        $list[$key] = collect($value)->toArray();
                    }

                    DB::table('sms_template')->insert($list);
                }
            }
        }

        if (Schema::hasTable('alidayu_configure')) {
            $sql = "DROP TABLE `" . $this->prefix . "alidayu_configure`";
            DB::statement($sql);
        }

        if (Schema::hasTable('alitongxin_configure')) {
            $sql = "DROP TABLE `" . $this->prefix . "alitongxin_configure`";
            DB::statement($sql);
        }
    }

    /**
     * 添加短信配置
     */
    public function smsShopConfig()
    {
        $parent_id = DB::table('shop_config')->where('code', 'sms')->value('id');

        $sms_key = DB::table('shop_config')->where('code', 'huawei_sms_key')->count();
        if ($sms_key <= 0) {
            DB::table('shop_config')->insert([
                'parent_id' => $parent_id,
                'code' => 'huawei_sms_key',
                'type' => 'text',
                'sort_order' => 10,
                'shop_group' => 'sms'
            ]);
        }

        $sms_secret = DB::table('shop_config')->where('code', 'huawei_sms_secret')->count();
        if ($sms_secret <= 0) {
            DB::table('shop_config')->insert([
                'parent_id' => $parent_id,
                'code' => 'huawei_sms_secret',
                'type' => 'text',
                'sort_order' => 10,
                'shop_group' => 'sms'
            ]);
        }
    }

    /**
     * 删除文件
     */
    public function delFile()
    {

        $list = [

            //数据迁移文件
            base_path('database/migrations/2018_10_19_095059_create_alidayu_configure_table.php'),
            base_path('database/migrations/2018_10_19_095059_create_alitongxin_configure_table.php'),

            //数据模型文件
            app_path('Entities/AlidayuConfigure.php'),
            app_path('Entities/AlitongxinConfigure.php'),
            app_path('Models/AlidayuConfigure.php'),
            app_path('Models/AlitongxinConfigure.php'),

            //后台文件
            app_path('Modules/Admin/Controllers/AlidayuConfigureController.php'),
            app_path('Modules/Admin/Controllers/AlitongxinConfigureController.php'),
            app_path('Modules/Admin/Controllers/HuyiConfigureController.php'),
            app_path('Modules/Admin/Views/alidayu_configure_info.dwt'),
            app_path('Modules/Admin/Views/alidayu_configure_list.dwt'),
            app_path('Modules/Admin/Views/alitongxin_configure_info.dwt'),
            app_path('Modules/Admin/Views/alitongxin_configure_list.dwt'),
            app_path('Modules/Admin/Views/huyi_configure_info.dwt'),
            app_path('Modules/Admin/Views/huyi_configure_list.dwt'),
            app_path('Modules/Admin/Languages/zh_cn/alidayu_configure.php'),
            app_path('Modules/Admin/Languages/zh_cn/alitongxin_configure.php'),
        ];

        foreach ($list as $k => $v) {
            if ($this->filesystem->isFile($v)) {
                $this->filesystem->delete($v);
            }
        }
    }
}