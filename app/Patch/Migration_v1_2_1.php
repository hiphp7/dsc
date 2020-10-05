<?php

namespace App\Patch;

use App\Models\AdminAction;
use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_2_1
{
    public function run()
    {
        $this->smsTable();
        $this->smsTempate();
        $this->shopConfig();
        $this->cloudShopConfig();
        $this->adminAction();
        $this->setGoodsAddFields();
        $this->setBonusTypeAddFields();
        $this->setUserBonusAddFields();
        $this->setObsConfigureAddTable();
    }

    /**
     * 修改表名称
     */
    public function smsTable()
    {
        $prefix = config('database.connections.mysql.prefix');

        if (Schema::hasTable('alidayu_configure') && !Schema::hasTable('sms_template')) {
            $sql = "RENAME TABLE `" . $prefix . "alidayu_configure` TO `" . $prefix . "sms_template`";
            DB::statement($sql);
        }

        $name = 'sms_template';
        if (!Schema::hasColumn($name, 'sender')) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('sender', 30)->default('')->comment('短信通道号');
            });
        }
    }

    /**
     * 删除 alitongxin_configure 表
     */
    public function smsTempate()
    {
        $prefix = config('database.connections.mysql.prefix');

        $val = ShopConfig::where('code', 'sms_type')->value('value');

        if ($val == 2 && Schema::hasTable('alidayu_configure')) {
            $count = DB::table('alitongxin_configure')->count();

            if ($count > 0) {
                $list = DB::table('alitongxin_configure')->get();
                $list = $list ? $list->toArray() : [];

                if ($list && Schema::hasTable('sms_template')) {
                    $sql = "TRUNCATE `" . $prefix . "sms_template`";
                    DB::statement($sql);

                    DB::table('sms_template')->insert($list);
                }
            }
        }

        if (Schema::hasTable('alitongxin_configure')) {
            $sql = "DROP TABLE `" . $prefix . "alitongxin_configure`";
            DB::statement($sql);
        }
    }

    /**
     * 添加短信配置
     */
    public function shopConfig()
    {
        $parent_id = ShopConfig::where('code', 'sms')->value('id');

        $count = ShopConfig::where('code', 'huawei_sms_key')->count();
        if ($count <= 0) {
            ShopConfig::insert([
                'parent_id' => $parent_id,
                'code' => 'huawei_sms_key',
                'type' => 'text',
                'sort_order' => 10,
                'shop_group' => 'sms'
            ]);
        }

        $count = ShopConfig::where('code', 'huawei_sms_secret')->count();
        if ($count <= 0) {
            ShopConfig::insert([
                'parent_id' => $parent_id,
                'code' => 'huawei_sms_secret',
                'type' => 'text',
                'sort_order' => 10,
                'shop_group' => 'sms'
            ]);
        }

        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.2.1'
        ]);

        $config_id = ShopConfig::where('code', 'extend_basic')->value('id');
        $config_id = $config_id ? $config_id : 0;

        /* 等级积分清零开关 */
        $other = [
            'parent_id' => $config_id,
            'type' => 'hidden',
            'store_range' => '0,1',
            'value' => 0,
            'sort_order' => 1
        ];
        $count = ShopConfig::where('code', 'open_user_rank_set')->count();
        if ($count <= 0) {
            $other['code'] = 'open_user_rank_set';
            ShopConfig::insert($other);
        }

        /* 等级积分清零时间 */
        $other = [
            'parent_id' => $config_id,
            'type' => 'hidden',
            'store_range' => '',
            'value' => 12,
            'sort_order' => 1
        ];
        $count = ShopConfig::where('code', 'clear_rank_point')->count();
        if ($count <= 0) {
            $other['code'] = 'clear_rank_point';
            ShopConfig::insert($other);
        }
    }

    /**
     * 添加权限配置
     */
    public function adminAction()
    {
        $parent_id = AdminAction::where('action_code', 'third_party_service')->value('action_id');

        $count = AdminAction::where('action_code', 'obs_configure')->count();
        if ($count <= 0) {
            AdminAction::insert([
                'parent_id' => $parent_id,
                'action_code' => 'obs_configure',
                'seller_show' => '0'
            ]);
        }

        $count = AdminAction::where('action_code', 'cloud_setting')->count();
        if ($count <= 0) {
            $parent_id = AdminAction::where('action_code', 'third_party_service')->value('action_id');

            AdminAction::insert([
                'parent_id' => $parent_id,
                'action_code' => 'cloud_setting',
                'seller_show' => '0'
            ]);
        }
    }

    /**
     * 添加云存储配置
     */
    public function cloudShopConfig()
    {
        $parent_id = ShopConfig::where('code', 'extend_basic')->value('id');

        $count = ShopConfig::where('code', 'cloud_storage')->count();
        if ($count <= 0) {
            ShopConfig::insert([
                'parent_id' => $parent_id,
                'code' => 'cloud_storage',
                'type' => 'select',
                'store_range' => '0,1',
                'sort_order' => 1,
                'value' => 0,
                'shop_group' => 'cloud'
            ]);
        }

        DB::table('shop_config')->where('code', 'open_oss')->update([
            'shop_group' => 'cloud'
        ]);
    }

    /**
     * 商品表添加字段
     */
    public function setGoodsAddFields()
    {
        $name = 'goods';

        if (!Schema::hasColumn($name, 'is_minimum')) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('is_minimum')->unsigned()->default(0)->index('is_minimum')->comment('是否支持最小起订量');
            });
        }
        if (!Schema::hasColumn($name, 'minimum_start_date')) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('minimum_start_date')->unsigned()->default(0)->comment('起订量开始时间');
            });
        }
        if (!Schema::hasColumn($name, 'minimum_end_date')) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('minimum_end_date')->unsigned()->default(0)->comment('起订量结束时间');
            });
        }
        if (!Schema::hasColumn($name, 'minimum')) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('minimum')->unsigned()->default(0)->comment('最小起订量');
            });
        }
    }

    /**
     * 添加红包表字段
     */
    public function setBonusTypeAddFields()
    {
        $name = 'bonus_type';

        if (!Schema::hasColumn($name, 'valid_period')) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('valid_period')->unsigned()->default(0)->index('valid_period')->comment('红包有效期');
            });
        }

        if (!Schema::hasColumn($name, 'date_type')) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('date_type')->default(0)->index('date_type')->comment('时间类型');
            });
        }
    }

    /**
     * 添加用户红包表字段
     */
    public function setUserBonusAddFields()
    {
        $name = 'user_bonus';

        if (!Schema::hasColumn($name, 'start_time')) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('start_time')->unsigned()->default(0)->index('start_time')->comment('使用开始时间');
            });
        }

        if (!Schema::hasColumn($name, 'end_time')) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('end_time')->unsigned()->default(0)->index('end_time')->comment('使用结束时间');
            });
        }

        if (!Schema::hasColumn($name, 'date_type')) {
            Schema::table('user_bonus', function (Blueprint $table) {
                $table->boolean('date_type')->default(0)->index('date_type')->comment('时间类型');
            });
        }
    }

    /**
     * 添加华为云OBS表
     *
     * @return bool
     */
    public function setObsConfigureAddTable()
    {
        $name = 'obs_configure';

        if (!Schema::hasTable($name)) {
            Schema::create($name, function (Blueprint $table) {
                $table->increments('id')->comment('自增ID号');
                $table->string('bucket')->comment('OSS模块对象名称');
                $table->string('keyid')->comment('Key值');
                $table->string('keysecret')->comment('Key密码');
                $table->boolean('is_cname')->default(0)->comment('是否域名绑定');
                $table->string('endpoint')->comment('绑定域名地址');
                $table->string('regional', 100)->comment('OSS绑定区域');
                $table->string('port', 15)->comment('端口号');
                $table->boolean('is_use')->default(0)->index('is_use')->comment('是否启用（0否，1是）');
            });
        }
    }
}
