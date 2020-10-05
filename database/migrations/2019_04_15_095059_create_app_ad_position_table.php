<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAppAdPositionTable extends Migration
{
    protected $table_name = 'app_ad_position';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table_name)) {
            return false;
        }
        Schema::create($this->table_name, function (Blueprint $table) {
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
        DB::statement("ALTER TABLE `" . $prefix . $this->table_name . "` comment 'app广告位'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->table_name);
    }
}
