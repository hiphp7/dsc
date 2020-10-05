<?php

namespace App\Patch;

use App\Models\AdminAction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_2_2
{
    public function run()
    {
        $app_file = app_path('Modules/Admin/Controllers/AppController.php');
        if (file_exists($app_file)) {
            // app广告 数据迁移
            $this->appAd();
            $this->appAdPosition();
        }

        $this->wechatMenu();
        $this->AdminAction();
    }

    /**
     * app广告 数据迁移
     */
    protected function appAd()
    {
        $name = 'app_ad';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('ad_id')->comment('自增ID');
            $table->integer('position_id')->unsigned()->default(0)->index('position_id')->comment('广告位置ID');
            $table->integer('media_type')->default(0)->comment('流媒体类型 默认 0 图片');
            $table->string('ad_name', 60)->default('')->comment('广告名称');
            $table->string('ad_link')->default('')->comment('广告链接');
            $table->text('ad_code')->nullable()->comment('广告编码');
            $table->integer('click_count')->unsigned()->default(0)->comment('点击数');
            $table->integer('sort_order')->nullable()->comment('排序');
            $table->integer('enabled')->default(1)->comment('是否可用： 0不可用、1可用');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $name . "` comment 'app广告'");
    }

    /**
     * app广告 数据迁移
     */
    protected function appAdPosition()
    {
        $name = 'app_ad_position';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('position_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID');
            $table->string('position_name', 60)->default('')->comment('广告位置名称');
            $table->string('ad_width')->default('')->comment('广告位宽度');
            $table->string('ad_height')->default('')->comment('广告位高度');
            $table->string('position_desc')->default('')->comment('广告位描述');
            $table->string('location_type')->default('')->comment('广告位位置 如 left,top');
            $table->string('position_type')->default('')->comment('广告位所属模块 如 app');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $name . "` comment 'app广告位'");
    }

    /**
     * 增加微信小程序字段
     *
     * @return bool
     */
    public function wechatMenu()
    {
        $name = 'wechat_menu';
        if (Schema::hasTable($name)) {
            // 判断字段是否存在添加
            if (!Schema::hasColumn($name, 'pagepath')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->string('pagepath')->default('')->comment('小程序页面');
                });
            }
        }
    }

    /**
     * 去除商家赠品权限
     */
    protected function AdminAction()
    {
        /* 去除商家赠品权限 */
        $count = AdminAction::where('action_code', 'gift_manage')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'seller_show' => '0'
            ];
            AdminAction::where('action_code', 'gift_manage')->update($rows);
        }
    }
}
