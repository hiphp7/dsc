<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTouchAdPositionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'touch_ad_position';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('position_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户ID');
            $table->string('position_name', 60)->default('')->comment('广告位置名称');
            $table->integer('ad_width')->unsigned()->default(0)->comment('广告宽度');
            $table->integer('ad_height')->unsigned()->default(0)->comment('广告高度');
            $table->string('position_desc')->default('')->comment('广告位置描述');
            $table->text('position_style')->nullable()->comment('广告位置类型');
            $table->boolean('is_public')->default(0)->comment('是否公共');
            $table->string('theme', 160)->default('')->comment('当前模板名称');
            $table->integer('tc_id')->unsigned()->default(0)->comment('频道id');
            $table->string('tc_type')->default('')->comment('广告类型');
            $table->string('ad_type')->default('')->comment('广告位所属');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment 'H5广告位置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('touch_ad_position');
    }
}
